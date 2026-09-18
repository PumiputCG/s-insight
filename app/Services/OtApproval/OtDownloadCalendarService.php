<?php

namespace App\Services\OtApproval;

use App\Models\OtApproval\LeaveRequest;
use App\Models\OtApproval\OtExportDownload;
use App\Models\OtApproval\OtRequest;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * สถานะการดาวน์โหลดเอกสารรายวัน สำหรับหน้า `ดาวน์โหลด`
 *
 * รวม 2 ชนิดเอกสารไว้ในปฏิทินเดียว เพราะ admin คนเดียวกันเป็นคนส่งให้ HR ทั้งคู่
 *   - OT  : ต้องอนุมัติแล้ว + เวลาสแกนผ่าน
 *   - ลา 75: ต้องอนุมัติแล้ว (ไม่มี attendance gate)
 *
 * จุดสำคัญคือ **มีของใหม่เข้ามาหลังจากโหลดไฟล์ของวันนั้นไปแล้ว** ซึ่งเกิดได้ 2 ทาง
 *   1. Foreman ยื่นย้อนหลัง (ทำ OT วันที่ 14 แต่มายื่นวันที่ 17)
 *   2. คำขอเดิมถูกแก้จำนวนชั่วโมงแล้วอนุมัติใหม่
 * ทั้งสองกรณี Bplus จะได้ข้อมูลไม่ครบถ้าไม่โหลดซ้ำ จึงต้องเตือนให้ชัด
 *
 * อ่านอย่างเดียว ไม่แก้ข้อมูล และไม่แตะ Bplus
 */
class OtDownloadCalendarService
{
    public const STATUS_EMPTY = 'empty';

    public const STATUS_WAITING = 'waiting';

    public const STATUS_READY = 'ready';

    public const STATUS_RESTALE = 'restale';

    public const STATUS_DONE = 'done';

    /**
     * @param  array<string, mixed>  $scope
     * @return array<string, mixed>
     */
    public function month(CarbonImmutable $month, array $scope): array
    {
        $start = $month->startOfMonth();
        $end = $month->endOfMonth();

        /* ช่องปฏิทิน = วันที่ทำ OT / วันลา คือวันของ "เอกสาร" ที่ HR จะนำเข้า Bplus
           ส่วนวันที่ยื่นคำขอไปเป็นแถวในโมดัล เพราะวันเดียวกันอาจยื่นหลายรอบ
           แต่ละรอบต้องเป็นคนละไฟล์ ไม่งั้น Bplus จะบวกชั่วโมงซ้ำตอน import */
        $otByDate = $this->otQuery($scope)
            ->whereBetween('work_date', [$start->toDateString(), $end->toDateString()])
            ->get()
            ->groupBy(fn (OtRequest $row) => $row->work_date->format('Y-m-d'));

        $leaveByDate = $this->leaveQuery($scope)
            ->whereBetween('leave_date', [$start->toDateString(), $end->toDateString()])
            ->get()
            ->groupBy(fn (LeaveRequest $row) => $row->leave_date->format('Y-m-d'));

        $downloads = OtExportDownload::query()
            ->where('scope', 'date')
            ->whereBetween('target_date', [$start->toDateString(), $end->toDateString()])
            ->orderBy('downloaded_at')
            ->get()
            ->groupBy(fn (OtExportDownload $row) => $row->target_date->format('Y-m-d'));

        $days = [];
        for ($date = $start; $date->lessThanOrEqualTo($end); $date = $date->addDay()) {
            $key = $date->format('Y-m-d');
            $days[] = $this->dayPayload(
                $date,
                $otByDate->get($key, collect()),
                $leaveByDate->get($key, collect()),
                $downloads->get($key, collect()),
            );
        }

        return [
            'month' => $start->format('Y-m'),
            'days' => $days,
            'totals' => [
                'ot_ready' => collect($days)->sum(fn (array $day) => $day['ot']['ready']),
                'leave_ready' => collect($days)->sum(fn (array $day) => $day['leave']['ready']),
                'backdated' => collect($days)->sum(fn (array $day) => $day['ot']['backdated'] + $day['leave']['backdated']),
                'need_download' => collect($days)->filter(
                    fn (array $day) => in_array($day['status'], [self::STATUS_READY, self::STATUS_RESTALE], true),
                )->count(),
                'downloads' => collect($days)->sum(fn (array $day) => $day['downloads']['count']),
            ],
        ];
    }

    /**
     * เอกสารของวันที่กดเลือก แยกเป็นไฟล์ตาม "รอบที่ยื่น"
     *
     * OT — วันทำงานเป็นอดีตเสมอ ไฟล์จึงแยกตามวันที่ยื่นทีละวัน
     *   ทำ OT วันที่ 17 แล้วมีคนยื่นเพิ่มวันที่ 20 จะเป็นคนละไฟล์
     *   เพราะ Bplus บวกชั่วโมงสะสมเมื่อ import ซ้ำ คนที่ส่งไปแล้วต้องไม่อยู่ในไฟล์รอบใหม่
     *
     * ลา 75 — วันลาเป็นวันอนาคตที่ประกาศไว้ล่วงหน้า Foreman ทยอยยื่นได้หลายวันก่อนถึงวันลา
     *   ทุกคำขอที่ยื่น "ถึงวันลา" จึงเป็นเอกสารฉบับเดียว ปุ่มเดียว (Manager 2026-08-17)
     *   ที่ยื่นหลังวันลาไปแล้วถือเป็นย้อนหลัง แยกไฟล์รายวันเหมือน OT
     *
     * @param  array<string, mixed>  $scope
     * @return array<string, mixed>
     */
    public function day(CarbonImmutable $date, array $scope): array
    {
        $key = $date->format('Y-m-d');

        return [
            'date' => $key,
            'ot' => ['documents' => $this->otDocuments($key, $scope)],
            'leave' => ['documents' => $this->leaveDocuments($key, $scope)],
        ];
    }

    /**
     * @param  array<string, mixed>  $scope
     * @return array<int, array<string, mixed>>
     */
    private function otDocuments(string $key, array $scope): array
    {
        $rows = $this->otQuery($scope)->whereDate('work_date', $key)->get();

        return $this->documents(
            $rows->groupBy(fn (OtRequest $row) => $this->otDocKey($row, $key)),
            $key,
            OtExportDownload::MODULE_OT,
            false,
            fn (OtRequest $row) => $this->otPassed($row),
            fn (OtRequest $row) => $row->export_status === OtRequest::EXPORT_EXPORTED,
        );
    }

    /**
     * @param  array<string, mixed>  $scope
     * @return array<int, array<string, mixed>>
     */
    private function leaveDocuments(string $key, array $scope): array
    {
        $rows = $this->leaveQuery($scope)->whereDate('leave_date', $key)->get();

        return $this->documents(
            $rows->groupBy(fn (LeaveRequest $row) => $this->leaveDocKey($row, $key)),
            $key,
            OtExportDownload::MODULE_LEAVE,
            true,
            fn (LeaveRequest $row) => $row->approval_status === LeaveRequest::APPROVAL_APPROVED,
            fn (LeaveRequest $row) => $row->export_status === LeaveRequest::EXPORT_EXPORTED,
        );
    }

    /**
     * @param  Collection<string, Collection<int, mixed>>  $grouped  จัดกลุ่มด้วย doc key แล้ว
     * @param  bool  $mergeOnTime  true = ลา 75 (คำขอที่ยื่นถึงวันลารวมเป็นไฟล์เดียว)
     * @return array<int, array<string, mixed>>
     */
    private function documents(
        Collection $grouped,
        string $openedDate,
        string $module,
        bool $mergeOnTime,
        callable $isPassed,
        callable $isExported,
    ): array {
        if ($grouped->isEmpty()) {
            return [];
        }

        /* log ต้องจับคู่ทั้งวันของเอกสารและรอบที่ยื่น เพราะวันเดียวกัน
           อาจถูกส่งเป็นหลายไฟล์ตามรอบที่ยื่น จำนวนครั้งจึงต้องนับแยกไฟล์ */
        $downloads = OtExportDownload::query()
            ->where('module', $module)
            ->where('scope', 'date')
            ->whereDate('target_date', $openedDate)
            ->get();

        return $grouped
            ->map(function (Collection $rows, string $docKey) use ($openedDate, $downloads, $mergeOnTime, $isPassed, $isExported) {
                $passed = $rows->filter($isPassed);
                $ready = $passed->reject($isExported);
                $isMain = $mergeOnTime && $docKey === $openedDate;
                $log = $this->documentLog($downloads, $docKey, $isMain);

                return [
                    'date' => $docKey,
                    'ready' => $ready->count(),
                    'total' => $rows->count(),
                    /* จำนวนที่จะอยู่ในไฟล์จริง = รายการที่ผ่านทั้งหมดของรอบนั้น
                       ไม่ใช่เฉพาะที่ยังไม่เคยโหลด เพราะ export ไม่ได้ตัดรายการที่โหลดไปแล้วออก
                       (HR ต้อง import ซ้ำได้ทั้งไฟล์ กันรายการตกหล่น) */
                    'passed' => $passed->count(),
                    // ไฟล์รวมของลา 75 — ทุกคำขอที่ยื่นถึงวันลาอยู่ในฉบับนี้
                    'is_main' => $isMain,
                    // ยื่นคนละวันกับวันของเอกสาร = ยื่นย้อนหลัง ต้องเตือน admin
                    'is_other_date' => ! $isMain && $docKey !== $openedDate,
                    'status' => $this->moduleStatus([
                        'total' => $rows->count(),
                        'ready' => $ready->count(),
                        'ever_exported' => $rows->filter(fn ($row) => $row->exported_at !== null)->count(),
                    ]),
                    'download_count' => $log->count(),
                    'last_at' => $this->thaiDateTime($log->max('downloaded_at')),
                    /* จุดแดงที่ปุ่ม = ยังมีรายการที่ผ่านแล้วแต่ไม่เคยเข้าไฟล์
                       ครอบคลุมทั้ง "ยังไม่เคยโหลด" และ "โหลดไปแล้วแต่มีของใหม่เข้ามาทีหลัง" */
                    'needs_download' => $ready->count() > 0,
                    /* พารามิเตอร์ของปุ่มโหลด — ให้ฝั่งหน้าเว็บส่งต่อตรง ๆ ไม่ต้องเดาเอง
                       date = วันของเอกสาร (work_date/leave_date), ส่วนรอบที่ยื่นแยก 2 แบบ
                       submitted_until = รวมทุกคำขอที่ยื่นถึงวันลา · submitted_on = เฉพาะวันที่ยื่นวันนั้น */
                    'params' => $isMain
                        ? ['scope' => 'date', 'date' => $openedDate, 'submitted_until' => $openedDate]
                        : ['scope' => 'date', 'date' => $openedDate, 'submitted_on' => $docKey],
                    /* แยกตามแผนกไว้ให้กด "ดูรายละเอียด" เพราะรายชื่อรายคนยาวเกินอ่าน
                       (ลา 75 วันหยุดทั้งบริษัทมีเป็นพันคน)
                       นับจากรายการที่ผ่าน = ตรงกับที่อยู่ในไฟล์ที่จะโหลด */
                    'departments' => $passed
                        ->groupBy(fn ($row) => trim((string) ($row->department_name ?: $row->dept_code)) ?: '-')
                        ->map->count()
                        ->sortDesc()
                        ->map(fn (int $count, string $name) => ['name' => $name, 'count' => $count])
                        ->values()
                        ->all(),
                ];
            })
            /* ไฟล์รวมของลา 75 ต้องอยู่บนสุดเสมอ แล้วค่อยตามด้วยรอบย้อนหลังเรียงตามวัน */
            ->sortBy(fn (array $doc) => ($doc['is_main'] ? '0' : '1').$doc['date'])
            ->values()
            ->all();
    }

    /**
     * log การโหลดของเอกสารฉบับหนึ่ง
     *
     * ไฟล์รวมของลา 75 นับ log ทุกครั้งที่ขอบเขตยังอยู่ในช่วง "ยื่นถึงวันลา"
     * รวม log เก่าที่ยังไม่มี `submitted_on` ด้วย จะได้ไม่ทำให้ประวัติที่เคยโหลดหายไป
     *
     * @param  Collection<int, OtExportDownload>  $downloads
     * @return Collection<int, OtExportDownload>
     */
    private function documentLog(Collection $downloads, string $docKey, bool $isMain): Collection
    {
        return $downloads->filter(function (OtExportDownload $row) use ($docKey, $isMain) {
            $submittedOn = $row->submitted_on?->format('Y-m-d');

            return $isMain
                ? ($submittedOn === null || $submittedOn <= $docKey)
                : $submittedOn === $docKey;
        })->values();
    }

    /** OT แยกไฟล์ตามวันที่ยื่น — ไม่มีวันที่ยื่นถือว่ายื่นวันเดียวกับวันทำงาน */
    private function otDocKey(OtRequest $row, string $workDate): string
    {
        return $row->submitted_at?->format('Y-m-d') ?: $workDate;
    }

    /** ลา 75 ยื่นถึงวันลา = ไฟล์รวมฉบับเดียว (key = วันลา) · ยื่นหลังวันลา = แยกไฟล์รายวัน */
    private function leaveDocKey(LeaveRequest $row, string $leaveDate): string
    {
        $submittedOn = $row->submitted_at?->format('Y-m-d') ?: $leaveDate;

        // Y-m-d เทียบเป็น string ได้ตรงกับเทียบวันที่ เพราะความยาวคงที่และเรียงจากหน่วยใหญ่
        return $submittedOn <= $leaveDate ? $leaveDate : $submittedOn;
    }

    /**
     * @param  Collection<int, OtRequest>  $ot
     * @param  Collection<int, LeaveRequest>  $leave
     * @param  Collection<int, OtExportDownload>  $downloads
     * @return array<string, mixed>
     */
    private function dayPayload(
        CarbonImmutable $date,
        Collection $ot,
        Collection $leave,
        Collection $downloads,
    ): array {
        $otStats = $this->stats(
            $ot,
            fn (OtRequest $row) => $this->otPassed($row),
            fn (OtRequest $row) => $row->export_status === OtRequest::EXPORT_EXPORTED,
            fn (OtRequest $row) => $row->exported_at !== null,
            fn (OtRequest $row) => $this->isBackdated($row->submitted_at, $row->work_date),
        );

        $leaveStats = $this->stats(
            $leave,
            fn (LeaveRequest $row) => $row->approval_status === LeaveRequest::APPROVAL_APPROVED,
            fn (LeaveRequest $row) => $row->export_status === LeaveRequest::EXPORT_EXPORTED,
            fn (LeaveRequest $row) => $row->exported_at !== null,
            fn (LeaveRequest $row) => $this->isBackdated($row->submitted_at, $row->leave_date),
        );

        $otDownloads = $downloads->where('module', OtExportDownload::MODULE_OT);
        $leaveDownloads = $downloads->where('module', OtExportDownload::MODULE_LEAVE);

        /* สถานะแยกรายชนิดเอกสาร เพราะหน้าดาวน์โหลดแบ่งเป็น 2 แท็บ
           OT อาจส่งครบแล้วขณะที่การลายังค้าง จะยุบเป็นสถานะเดียวไม่ได้ */
        $otStats['status'] = $this->moduleStatus($otStats);
        $otStats['downloads'] = [
            'count' => $otDownloads->count(),
            'last_at' => $this->thaiDateTime($otDownloads->max('downloaded_at')),
        ];
        $leaveStats['status'] = $this->moduleStatus($leaveStats);
        $leaveStats['downloads'] = [
            'count' => $leaveDownloads->count(),
            'last_at' => $this->thaiDateTime($leaveDownloads->max('downloaded_at')),
        ];

        /* วงกลมแดงบนช่องปฏิทิน = จำนวน "ไฟล์" ที่ยังต้องโหลด ไม่ใช่จำนวนคน
           นับด้วย doc key ชุดเดียวกับตารางในโมดัล ตัวเลขบนปฏิทินจึงตรงกับจำนวนปุ่มที่ยังแดง
           OT แยกไฟล์ตามวันที่ยื่น ส่วนลา 75 รวมคำขอที่ยื่นถึงวันลาเป็นไฟล์เดียว */
        $key = $date->format('Y-m-d');
        $otPassed = fn (OtRequest $row) => $this->otPassed($row);
        $otExported = fn (OtRequest $row) => $row->export_status === OtRequest::EXPORT_EXPORTED;
        $otKey = fn (OtRequest $row) => $this->otDocKey($row, $key);
        $leavePassed = fn (LeaveRequest $row) => $row->approval_status === LeaveRequest::APPROVAL_APPROVED;
        $leaveExported = fn (LeaveRequest $row) => $row->export_status === LeaveRequest::EXPORT_EXPORTED;
        $leaveKey = fn (LeaveRequest $row) => $this->leaveDocKey($row, $key);

        $otStats['pending_files'] = $this->pendingFiles($ot, $otPassed, $otExported, $otKey);
        $otStats['files'] = $this->fileCount($ot, $otPassed, $otKey);
        $leaveStats['pending_files'] = $this->pendingFiles($leave, $leavePassed, $leaveExported, $leaveKey);
        $leaveStats['files'] = $this->fileCount($leave, $leavePassed, $leaveKey);

        return [
            'date' => $date->format('Y-m-d'),
            'day' => (int) $date->format('j'),
            'weekday' => (int) $date->format('w'),
            'is_future' => $date->isFuture(),
            'ot' => $otStats,
            'leave' => $leaveStats,
            'downloads' => [
                'count' => $downloads->count(),
                'last_at' => $this->thaiDateTime($downloads->max('downloaded_at')),
            ],
            'status' => $this->dayStatus($otStats, $leaveStats),
        ];
    }

    /**
     * @param  Collection<int, mixed>  $rows
     * @return array<string, mixed>
     */
    private function stats(
        Collection $rows,
        callable $isPassed,
        callable $isExported,
        callable $everExported,
        callable $isBackdated,
    ): array {
        $passed = $rows->filter($isPassed);

        /* นับ "เคยส่งไฟล์" จาก exported_at ไม่ใช่ export_status เพราะคำขอที่ถูกแก้แล้วอนุมัติใหม่
           จะถูกรีเซ็ต export_status กลับ แต่ไฟล์เก่ายังอยู่ในมือ HR ต้องเตือนให้โหลดทับ */
        $ever = $rows->filter($everExported);

        return [
            'total' => $rows->count(),
            'ready' => $passed->reject($isExported)->count(),
            'exported' => $passed->filter($isExported)->count(),
            'waiting' => $rows->reject($isPassed)->count(),
            'backdated' => $rows->filter($isBackdated)->count(),
            'ever_exported' => $ever->count(),
            'rows' => [],
        ];
    }

    /**
     * สถานะของเอกสารชนิดเดียว (ใช้กับแท็บ OT และแท็บการลาแยกกัน)
     *
     * @param  array<string, mixed>  $stats
     */
    private function moduleStatus(array $stats): string
    {
        if ($stats['total'] < 1) {
            return self::STATUS_EMPTY;
        }

        // เคยโหลดไปแล้วแต่ยังมีรายการค้าง = ของใหม่เข้ามาทีหลัง ต้องโหลดซ้ำ
        if ($stats['ready'] > 0 && $stats['ever_exported'] > 0) {
            return self::STATUS_RESTALE;
        }

        if ($stats['ready'] > 0) {
            return self::STATUS_READY;
        }

        return $stats['ever_exported'] > 0 ? self::STATUS_DONE : self::STATUS_WAITING;
    }

    /**
     * @param  array<string, mixed>  $ot
     * @param  array<string, mixed>  $leave
     */
    private function dayStatus(array $ot, array $leave): string
    {
        $total = $ot['total'] + $leave['total'];
        if ($total < 1) {
            return self::STATUS_EMPTY;
        }

        $ready = $ot['ready'] + $leave['ready'];
        $ever = $ot['ever_exported'] + $leave['ever_exported'];

        // เคยโหลดไปแล้วแต่ยังมีรายการค้าง = ของใหม่เข้ามาทีหลัง ต้องโหลดซ้ำ
        if ($ready > 0 && $ever > 0) {
            return self::STATUS_RESTALE;
        }

        if ($ready > 0) {
            return self::STATUS_READY;
        }

        if ($ever > 0) {
            return self::STATUS_DONE;
        }

        return self::STATUS_WAITING;
    }

    /**
     * จำนวนไฟล์ทั้งหมดของวันนั้น = จำนวน doc key ที่ไม่ซ้ำกันในกลุ่มที่ผ่านแล้ว
     *
     * @param  Collection<int, mixed>  $rows
     */
    private function fileCount(Collection $rows, callable $isPassed, callable $docKey): int
    {
        return $rows->filter($isPassed)->map($docKey)->unique()->count();
    }

    /**
     * ไฟล์ที่ยังต้องโหลด — ใช้เป็นตัวเลขในวงกลมแดง
     *
     * นับจาก export_status ไม่ใช่ log การโหลด เพราะไฟล์ที่โหลดไปแล้ว
     * แต่มีคำขอใหม่เข้ามาทีหลังต้องกลับมาแดงอีกครั้ง (ตรงกับ needs_download ของแต่ละปุ่ม)
     *
     * @param  Collection<int, mixed>  $rows
     */
    private function pendingFiles(
        Collection $rows,
        callable $isPassed,
        callable $isExported,
        callable $docKey,
    ): int {
        return $rows->filter($isPassed)
            ->reject($isExported)
            ->map($docKey)
            ->unique()
            ->count();
    }

    /** 17/08/2569 14.32 น. — ปี พ.ศ. และใช้จุดคั่นเวลาแบบไทย */
    private function thaiDateTime(mixed $value): ?string
    {
        if (! $value) {
            return null;
        }

        return $value->format('d/m/').((int) $value->format('Y') + 543).$value->format(' H.i').' น.';
    }

    private function otPassed(OtRequest $request): bool
    {
        return $request->approval_status === OtRequest::APPROVAL_APPROVED
            && $request->attendance_status === OtRequest::ATTENDANCE_PASSED;
    }

    /** ยื่นคนละวันกับวันที่ทำงาน/วันลา = ย้อนหลัง ตามที่ Manager กำหนด ไม่มีเกณฑ์จำนวนวัน */
    private function isBackdated(mixed $submittedAt, mixed $targetDate): bool
    {
        return $submittedAt !== null && $submittedAt->toDateString() !== $targetDate->toDateString();
    }

    /** @param array<string, mixed> $scope */
    private function otQuery(array $scope): Builder
    {
        return $this->applyScope(
            OtRequest::query()->notCancelled()->where('approval_status', '!=', OtRequest::APPROVAL_DRAFT),
            $scope,
        );
    }

    /** @param array<string, mixed> $scope */
    private function leaveQuery(array $scope): Builder
    {
        return $this->applyScope(
            LeaveRequest::query()->notCancelled()->where('approval_status', '!=', LeaveRequest::APPROVAL_DRAFT),
            $scope,
        );
    }

    /** @param array<string, mixed> $scope */
    private function applyScope(Builder $query, array $scope): Builder
    {
        if ($scope['all'] ?? false) {
            return $query;
        }

        $departments = $scope['departments'] ?? [];
        if ($departments === []) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function (Builder $where) use ($departments) {
            foreach ($departments as $department) {
                $where->orWhere(function (Builder $match) use ($department) {
                    $match->where('company', $department['company'])
                        ->where('dept_code', trim((string) $department['dept_code']));
                });
            }
        });
    }
}
