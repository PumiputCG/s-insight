<?php

namespace App\Http\Controllers\Area5s\Concerns;

use App\Models\Area5s\A5sActivityLog;
use App\Models\Area5s\A5sEvaluatorScope;
use App\Models\Area5s\A5sLayout;
use App\Models\Area5s\A5sMember;
use App\Models\Area5s\A5sPoint;
use App\Models\Area5s\A5sPointAssignee;
use App\Models\Area5s\A5sRound;
use App\Models\Area5s\A5sTask;
use App\Models\Area5s\A5sTaskAttempt;
use App\Models\Insight\AppUser;
use App\Models\Insight\Employee;
use App\Models\Insight\Setting;
use App\Services\Area5s\A5sRoundService;
use App\Services\Area5s\A5sScoreService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;

/**
 * ตรวจสิทธิ์เข้าระบบ SUPAVUT 5S AREA (แบบเดียวกับ Assessment)
 *
 * - admin ของ Insight หรือ a5s_members role=admin = จัดการได้ทุกอย่าง
 * - allocator = จัดการ Layout ของตัวเอง · evaluator = ตรวจตามขอบเขต
 * - พนักงานทั่วไป = เข้าได้ถ้าตำแหน่งอยู่ในรายการที่อนุญาต หรือถูกมอบหมายพื้นที่/เป็นผู้ตรวจ
 */
trait HandlesArea5sAccess
{
    protected function me()
    {
        return app('current_user');
    }

    /** admin ของระบบ 5S = admin Insight หรือถูกตั้ง role admin ใน a5s_members */
    protected function isA5sAdmin(): bool
    {
        $me = $this->me();

        return $me && ($me->isAdmin() || A5sMember::hasRole($me->id, A5sMember::ROLE_ADMIN));
    }

    protected function isAllocator(): bool
    {
        $me = $this->me();

        return $me && A5sMember::hasRole($me->id, A5sMember::ROLE_ALLOCATOR);
    }

    protected function isEvaluator(): bool
    {
        $me = $this->me();

        return $me && A5sMember::hasRole($me->id, A5sMember::ROLE_EVALUATOR);
    }

    /** มีจุดที่ตัวเองรับผิดชอบอยู่ (active) หรือไม่ */
    protected function isResponsible(): bool
    {
        $code = trim((string) ($this->me()->employee_code ?? ''));

        return $code !== '' && A5sPointAssignee::where('employee_code', $code)->whereNull('removed_at')->exists();
    }

    /** ตำแหน่งของผู้ใช้ได้รับอนุญาตเข้าระบบหรือไม่ (null = อนุญาตทั้งหมด) */
    protected function positionAllowed(): bool
    {
        $jobCode = optional($this->me()->employee)->job_code;

        return Setting::isPositionAllowed(Setting::AREA5S_POSITIONS, $jobCode);
    }

    /**
     * เข้าระบบ 5S ได้ไหม — "ตำแหน่งที่เข้าใช้ระบบได้" เป็นเงื่อนไขบังคับ (Manager 2026-07-18)
     * บทบาท allocator/evaluator/ผู้รับผิดชอบ ไม่ bypass รายการตำแหน่ง · admin ยกเว้น
     */
    protected function gateEnter(): ?RedirectResponse
    {
        if ($this->isA5sAdmin() || $this->positionAllowed()) {
            return null;
        }

        return $this->denyToSystems('toast.area5sDenied', 'คุณยังไม่ได้รับสิทธิ์เข้าใช้ระบบ SUPAVUT 5S AREA');
    }

    /** เฉพาะ admin (ตั้งค่าระบบ/รอบ) */
    protected function gateAdmin(): ?RedirectResponse
    {
        if ($this->isA5sAdmin()) {
            return null;
        }

        return $this->denyToSystems('toast.pageDenied', 'คุณไม่มีสิทธิ์เข้าหน้านี้');
    }

    /** จัดการพื้นที่ — admin หรือ allocator (ต้องผ่านรายการตำแหน่งก่อน) */
    protected function gateManage(): ?RedirectResponse
    {
        if ($redirect = $this->gateEnter()) {
            return $redirect;
        }

        if ($this->isA5sAdmin() || $this->isAllocator()) {
            return null;
        }

        return $this->denyToSystems('toast.pageDenied', 'คุณไม่มีสิทธิ์เข้าหน้านี้');
    }

    /** กลับหน้าระบบทั้งหมดพร้อม toast แทนหน้า 403 */
    protected function a5sEmployeePayload(?Employee $employee, string $fallbackCode = ''): array
    {
        $code = trim((string) ($employee?->employee_code ?: $fallbackCode));
        $nameTh = trim((string) ($employee?->fullNameTh() ?? ''));
        $nameEn = trim((string) ($employee?->fullNameEn() ?? ''));
        $positionTh = trim((string) ($employee?->job_th ?? ''));
        $positionEn = trim((string) ($employee?->job_en ?? ''));
        $departmentTh = trim((string) ($employee?->deptThClean() ?? ''));
        $departmentEn = trim((string) ($employee?->dept_en ?? ''));

        return [
            'code' => $code,
            'name' => $nameTh ?: $nameEn ?: $code,
            'name_th' => $nameTh ?: $nameEn ?: $code,
            'name_en' => $nameEn ?: $nameTh ?: $code,
            'name_my' => $nameEn ?: $nameTh ?: $code,
            'position' => $positionTh ?: $positionEn,
            'position_th' => $positionTh ?: $positionEn,
            'position_en' => $positionEn ?: $positionTh,
            'position_my' => $positionEn ?: $positionTh,
            'department' => $departmentTh ?: $departmentEn,
            'department_th' => $departmentTh ?: $departmentEn,
            'department_en' => $departmentEn ?: $departmentTh,
            'department_my' => $departmentEn ?: $departmentTh,
        ];
    }

    protected function a5sAppUserPayload(?AppUser $user, string $fallbackCode = ''): array
    {
        $code = trim((string) ($user?->employee_code ?: $fallbackCode));
        $nameTh = trim((string) ($user?->fullNameTh() ?? ''));
        $nameEn = trim((string) ($user?->full_name_en ?? ''));
        $position = trim((string) ($user?->position ?? ''));
        $department = trim((string) ($user?->department ?? ''));

        return [
            'code' => $code,
            'name' => $nameTh ?: $nameEn ?: $code,
            'name_th' => $nameTh ?: $nameEn ?: $code,
            'name_en' => $nameEn ?: $nameTh ?: $code,
            'name_my' => $nameEn ?: $nameTh ?: $code,
            'position' => $position,
            'position_th' => $position,
            'position_en' => $position,
            'position_my' => $position,
            'department' => $department,
            'department_th' => $department,
            'department_en' => $department,
            'department_my' => $department,
        ];
    }

    // ── ประวัติการตรวจประเมินรายรอบ (แท็บเดือน → ครั้งตรวจ + ตารางกางประวัติ) ───────────────

    private const A5S_MONTH_ABBR_TH = [
        1 => 'ม.ค.', 2 => 'ก.พ.', 3 => 'มี.ค.', 4 => 'เม.ย.', 5 => 'พ.ค.', 6 => 'มิ.ย.',
        7 => 'ก.ค.', 8 => 'ส.ค.', 9 => 'ก.ย.', 10 => 'ต.ค.', 11 => 'พ.ย.', 12 => 'ธ.ค.',
    ];

    /**
     * วันที่+เวลาแบบปี พ.ศ. เช่น "18/07/2569 09:00"
     * ทั้งหน้าใช้ พ.ศ. อยู่แล้ว (เดือน/วันที่ตรวจ) แต่ timestamp ของงานเคยเป็น ค.ศ. จึงดูไม่เป็นชุดเดียวกัน
     */
    protected function a5sBeDateTime($date): ?string
    {
        if (! $date) {
            return null;
        }

        return $date->format('d/m/').((int) $date->year + 543).' '.$date->format('H:i');
    }

    /** วันที่แบบไทยย่อ เช่น "20 ก.ค. 2569" (ปี พ.ศ.) — ใช้กับ inspected_on / evaluated_at */
    protected function a5sThaiDate($date): string
    {
        if (! $date) {
            return '-';
        }

        return $date->day.' '.(self::A5S_MONTH_ABBR_TH[(int) $date->month] ?? $date->month).' '.((int) $date->year + 543);
    }

    /**
     * โครงแท็บเลือกรอบ: เดือน (ใหม่→เก่า) → ครั้งตรวจ (seq เรียงน้อย→มาก ป้ายด้วย inspected_on)
     * URL แต่ละครั้งตรวจ = หน้าปัจจุบัน + ?round=<id> (คงพารามิเตอร์อื่นไว้) · is_past = รอบที่เลือกไม่ใช่รอบเปิด
     */
    protected function a5sRoundNav(Collection $rounds, ?A5sRound $viewRound, ?A5sRound $openRound): array
    {
        $openId = (int) ($openRound?->id ?? 0);
        $selectedId = (int) ($viewRound?->id ?? 0);
        $selectedKey = $viewRound ? $viewRound->year.'-'.$viewRound->month : null;

        $months = $rounds
            ->sortByDesc(fn (A5sRound $round) => sprintf('%05d%02d', (int) $round->year, (int) $round->month))
            ->groupBy(fn (A5sRound $round) => $round->year.'-'.$round->month)
            ->map(function (Collection $group, string $key) use ($openId, $selectedId, $selectedKey) {
                $first = $group->first();
                $inspections = $group
                    ->sortBy(fn (A5sRound $round) => (int) ($round->seq ?? 1))
                    ->map(fn (A5sRound $round) => [
                        'id' => (int) $round->id,
                        'seq' => (int) ($round->seq ?? 1),
                        'date_label' => $round->inspected_on ? $this->a5sThaiDate($round->inspected_on) : null,
                        'is_open' => (int) $round->id === $openId,
                        'is_selected' => (int) $round->id === $selectedId,
                        'url' => request()->fullUrlWithQuery(['round' => $round->id]),
                    ])
                    ->values()
                    ->all();

                return [
                    'key' => $key,
                    'label' => A5sRoundService::monthLabel((int) $first->month).' '.$first->year,
                    'is_selected' => $key === $selectedKey,
                    'inspections' => $inspections,
                ];
            })
            ->values()
            ->all();

        return [
            'months' => $months,
            'selected_id' => $selectedId,
            'has_selection' => $selectedId > 0,
            'is_past' => $viewRound && $selectedId !== $openId,
        ];
    }

    /**
     * รายการประวัติการส่ง→ตรวจของจุดหนึ่ง (attempts เรียง seq) สำหรับ modal ประวัติ
     * แต่ละครั้ง: ครั้งที่ · ผ่าน/ปฏิเสธ · วันประเมิน · หมายเหตุ (advice ตอนผ่าน / fail_reason ตอนปฏิเสธ)
     */
    protected function a5sPointAttempts(?A5sTask $task): array
    {
        if (! $task) {
            return [];
        }

        return $task->attempts()->orderBy('seq')->get()->map(function (A5sTaskAttempt $attempt) {
            $pass = $attempt->result === 'pass';

            return [
                'seq' => (int) $attempt->seq,
                'result' => $attempt->result,
                'status_key' => $pass ? 'a5s.status.passed' : 'a5s.status.failed',
                'status_default' => $pass ? 'ผ่าน' : 'ปฏิเสธ',
                'status_class' => $pass ? 'pass' : 'fail',
                'date' => $this->a5sThaiDate($attempt->evaluated_at),
                'note' => $pass ? $attempt->advice : $attempt->fail_reason,
            ];
        })->all();
    }

    /**
     * สรุปสถานะจุดในรอบที่เลือก (สถานะ/วันประเมินล่าสุด) + แนบ attempts ไว้ให้ modal ประวัติ
     * status ยึดจากสถานะ task ปัจจุบัน · วันประเมิน = ครั้งล่าสุดที่ถูกตรวจ
     */
    protected function a5sPointSummary(?A5sTask $task): array
    {
        $attempts = $this->a5sPointAttempts($task);
        $status = $task?->status ?? 'not_started';
        [$class, $key, $default] = match (true) {
            $status === 'passed' => ['pass', 'a5s.status.passed', 'ผ่าน'],
            $status === 'failed' => ['fail', 'a5s.status.failed', 'ปฏิเสธ'],
            in_array($status, ['submitted', 'resubmitted'], true) => ['pending', 'a5s.status.submitted', 'รอดำเนินการ'],
            default => ['none', 'a5s.status.no_data', 'ยังไม่มีข้อมูล'],
        };

        return [
            'status_class' => $class,
            'status_key' => $key,
            'status_default' => $default,
            'evaluated_on' => $attempts ? end($attempts)['date'] : '-',
            'attempts' => $attempts,
            'attempt_count' => count($attempts),
        ];
    }

    /** ป้ายรอบเต็ม เช่น "กรกฎาคม 2569 · 20 ก.ค. 2569" (มีวันตรวจ) หรือ "· ครั้งที่ N" */
    protected function a5sRoundLabel(A5sRound $round): string
    {
        $base = A5sRoundService::monthLabel((int) $round->month).' '.$round->year;
        if ($round->inspected_on) {
            return $base.' · '.$this->a5sThaiDate($round->inspected_on);
        }
        if ((int) ($round->seq ?? 1) > 1) {
            return $base.' · ครั้งที่ '.$round->seq;
        }

        return $base;
    }

    /** เลือกรอบที่จะแสดง: ?round= ที่อยู่ในชุด → รอบเปิด(ถ้าอยู่ในชุด) → ล่าสุด(ปี/เดือน/ครั้ง) */
    protected function resolveViewRound(Collection $rounds, ?A5sRound $openRound, int $requestedId): ?A5sRound
    {
        if ($rounds->isEmpty()) {
            return null;
        }
        if ($requestedId && ($round = $rounds->firstWhere('id', $requestedId))) {
            return $round;
        }
        if ($openRound && ($round = $rounds->firstWhere('id', $openRound->id))) {
            return $round;
        }

        return $rounds
            ->sortByDesc(fn (A5sRound $round) => sprintf('%05d%02d%02d', (int) $round->year, (int) $round->month, (int) ($round->seq ?? 1)))
            ->first();
    }

    /**
     * ประกอบโครง floors ให้ partial history-panel
     *
     * @param  Collection  $layouts  A5sLayout เรียงตามชั้นแล้ว (มี floor, points_count)
     * @param  Collection  $pointsByLayout  layout_id => Collection<A5sPoint(id,code,name)>
     * @param  Collection  $taskByPoint  point_id => A5sTask ของรอบที่เลือก
     * @param  Collection  $cardCountByLayout  layout_id => int
     * @param  callable  $workUrl  fn(A5sLayout): ?string (null = รอบปิด อ่านอย่างเดียว ไม่มีปุ่ม)
     */
    protected function a5sHistoryFloors(
        Collection $layouts,
        Collection $pointsByLayout,
        Collection $taskByPoint,
        Collection $cardCountByLayout,
        callable $workUrl,
        string $workKey,
        string $workDefault
    ): array {
        /* คะแนน + ผู้รับผิดชอบต่อจุด — ชุดข้อมูลเดียวกับตารางหน้างานพื้นที่ของฉัน (Manager 2026-08-27)
           จุดที่มี task ใช้สำเนา assignees_json ตอนส่ง · จุดที่ยังไม่มี task ใช้ผู้รับผิดชอบปัจจุบัน */
        $scoreByTask = A5sScoreService::taskScores($taskByPoint->pluck('id'));
        $pointIds = collect($pointsByLayout)->flatten(1)->pluck('id')->filter()->values();
        $pointsWithoutTask = $pointIds->reject(fn ($id) => $taskByPoint->has($id))->values();
        $currentAssignees = $pointsWithoutTask->isEmpty()
            ? collect()
            : A5sPointAssignee::whereIn('point_id', $pointsWithoutTask->all())
                ->whereNull('removed_at')
                ->get()
                ->groupBy('point_id');
        $codes = $taskByPoint
            ->flatMap(fn (A5sTask $task) => collect($task->assignees_json ?? [])->pluck('code'))
            ->merge($currentAssignees->flatten(1)->pluck('employee_code'))
            ->map(fn ($code) => trim((string) $code))
            ->filter()
            ->unique()
            ->values();
        $employees = $codes->isEmpty()
            ? collect()
            : Employee::active()->whereIn('employee_code', $codes->all())->get()->keyBy('employee_code');
        $avatars = $codes->isEmpty()
            ? collect()
            : AppUser::whereIn('employee_code', $codes->all())
                ->pluck('profile_picture', 'employee_code')
                ->map(fn ($picture) => $picture ? asset('storage/'.$picture) : null);

        /* ปฏิทินคะแนนรายจุด — ต้องมองข้ามรอบที่กำลังดู จึงโหลด task ทุกรอบของจุดเหล่านี้
           โครงเดียวกับปฏิทินหน้างานพื้นที่ของฉัน: เดือน → รอบ → รายละเอียดแต่ละครั้งที่ส่ง (Manager 2026-08-27) */
        $calendarByPoint = $this->a5sPointCalendars($pointIds);

        $peopleFor = function (int $pointId) use ($taskByPoint, $currentAssignees, $employees, $avatars) {
            $rows = collect($taskByPoint->get($pointId)?->assignees_json ?? [])
                ->filter(fn ($person) => is_array($person))
                ->values();
            if ($rows->isEmpty()) {
                $rows = collect($currentAssignees->get($pointId, collect()))
                    ->map(fn (A5sPointAssignee $row) => $this->a5sEmployeePayload(
                        $employees->get($row->employee_code),
                        (string) $row->employee_code
                    ));
            }

            return $rows
                ->map(fn (array $person) => array_merge($person, [
                    'avatar' => $person['avatar'] ?? $avatars->get(trim((string) ($person['code'] ?? ''))),
                ]))
                ->values()
                ->all();
        };

        return $layouts
            ->groupBy(fn (A5sLayout $layout) => (int) $layout->floor_id)
            ->map(function (Collection $group) use ($pointsByLayout, $taskByPoint, $cardCountByLayout, $workUrl, $workKey, $workDefault, $scoreByTask, $peopleFor, $calendarByPoint) {
                $floor = $group->first()?->floor;

                return [
                    'name' => $floor?->name,
                    'layouts' => $group->map(function (A5sLayout $layout) use ($pointsByLayout, $taskByPoint, $cardCountByLayout, $workUrl, $workKey, $workDefault, $scoreByTask, $peopleFor, $calendarByPoint) {
                        $points = collect($pointsByLayout->get($layout->id, collect()))
                            ->map(function ($point) use ($taskByPoint, $scoreByTask, $peopleFor, $calendarByPoint) {
                                $task = $taskByPoint->get($point->id);
                                $summary = $this->a5sPointSummary($task);
                                $score = $task ? ($scoreByTask[(int) $task->id] ?? null) : null;

                                return array_merge($summary, [
                                    'id' => (int) $point->id,
                                    'code' => $point->code,
                                    'name' => $point->name,
                                    'point_label' => trim(($point->name ?: '').' · จุด '.$point->code),
                                    // 'none' -> 'no_data' ให้ตรงกับคีย์แท็บตัวกรอง
                                    'bucket' => $summary['status_class'] === 'none' ? 'no_data' : $summary['status_class'],
                                    'assignees' => $peopleFor((int) $point->id),
                                    'score' => $score,
                                    'score_label' => A5sScoreService::label($score),
                                    'calendar' => $calendarByPoint[(int) $point->id] ?? ['score' => null, 'months' => []],
                                ]);
                            })
                            ->values()
                            ->all();

                        return [
                            'id' => $layout->id,
                            'name' => $layout->name,
                            'image' => $layout->image_path ? asset('storage/'.$layout->image_path) : null,
                            'points_count' => (int) $layout->points_count,
                            'card_count' => (int) ($cardCountByLayout->get($layout->id) ?? 0),
                            'work_url' => $workUrl($layout),
                            'work_key' => $workKey,
                            'work_default' => $workDefault,
                            'points' => $points,
                        ];
                    })->values()->all(),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * ปฏิทินคะแนนรายจุด: [point_id => ['score' => float|null, 'months' => [...]]]
     *
     * เดือน (ใหม่→เก่า) → รอบ (seq น้อย→มาก) → แต่ละครั้งที่ถูกตรวจ (ผล/วันที่/หมายเหตุ)
     * คะแนนใช้ A5sScoreService ชุดเดียวกับที่อื่น — รอบ = คะแนนของ task นั้น · เดือน/รวม = เฉลี่ยของรอบ
     */
    protected function a5sPointCalendars(Collection $pointIds): array
    {
        if ($pointIds->isEmpty()) {
            return [];
        }

        /* ปฏิทินต้องขึ้น "ทุกวันที่ตรวจ" ของแต่ละเดือน แม้จุดนั้นยังไม่มี task ในรอบนั้น (Manager 2026-08-27)
           จึงยึดตารางรอบเป็นโครง แล้วค่อยเอา task ของจุดมาเสียบ */
        $rounds = A5sRound::all()->keyBy('id');
        if ($rounds->isEmpty()) {
            return [];
        }
        $openRoundId = (int) (A5sRound::open()?->id ?? 0);

        $tasks = A5sTask::whereIn('point_id', $pointIds->all())
            ->with(['attempts' => fn ($query) => $query->orderBy('seq')])
            ->get();
        $tasks->loadMissing('cards');
        $scores = A5sScoreService::taskScores($tasks->pluck('id'));
        $taskByPointRound = $tasks->keyBy(fn (A5sTask $task) => $task->point_id.'-'.$task->round_id);

        // เดือน (ใหม่→เก่า) → วันที่ตรวจ (seq น้อย→มาก) — โครงเดียวกันทุกจุด
        $monthSkeleton = $rounds
            ->sortByDesc(fn (A5sRound $round) => sprintf('%05d%02d%03d', (int) $round->year, (int) $round->month, (int) ($round->seq ?? 1)))
            ->groupBy(fn (A5sRound $round) => $round->year.'-'.$round->month)
            ->map(fn (Collection $group) => $group->sortBy(fn (A5sRound $round) => (int) ($round->seq ?? 1))->values());

        /* ผู้ส่งของแต่ละครั้งอยู่ใน activity log ตอนกดส่ง · ผู้ประเมินอยู่ที่ attempt.evaluated_by */
        $submitLogs = A5sActivityLog::query()
            ->where('subject_type', 'task')
            ->whereIn('subject_id', $tasks->pluck('id')->all())
            ->whereIn('action', ['task.submit', 'task.resubmit'])
            ->orderBy('created_at')->orderBy('id')
            ->get(['id', 'user_id', 'subject_id', 'detail_json', 'created_at']);
        $logsByTask = $submitLogs->groupBy('subject_id');
        $userIds = $submitLogs->pluck('user_id')
            ->merge($tasks->flatMap(fn (A5sTask $task) => $task->attempts->pluck('evaluated_by')))
            ->map(fn ($id) => (int) $id)->filter()->unique()->values();
        $users = $userIds->isEmpty() ? collect() : AppUser::whereIn('id', $userIds->all())->get()->keyBy('id');
        $userPayload = fn (?AppUser $user) => $user
            ? $this->a5sAppUserPayload($user, (string) $user->employee_code)
            : null;

        return $pointIds
            ->mapWithKeys(function ($pointId) use (
                $monthSkeleton,
                $taskByPointRound,
                $scores,
                $openRoundId,
                $logsByTask,
                $users,
                $userPayload
            ) {
                $pointId = (int) $pointId;

                $months = $monthSkeleton
                    ->map(function (Collection $monthRounds, string $key) use (
                        $pointId,
                        $taskByPointRound,
                        $scores,
                        $openRoundId,
                        $logsByTask,
                        $users,
                        $userPayload
                    ) {
                        $first = $monthRounds->first();
                        $roundRows = $monthRounds
                            ->map(function (A5sRound $round) use (
                                $pointId,
                                $taskByPointRound,
                                $scores,
                                $openRoundId,
                                $logsByTask,
                                $users,
                                $userPayload
                            ) {
                                $task = $taskByPointRound->get($pointId.'-'.$round->id);
                                $date = $round->inspected_on ?? $round->opened_at;
                                $status = (string) ($task?->status ?? 'not_started');

                                return [
                                    'round_id' => (int) $round->id,
                                    'seq' => (int) ($round->seq ?? 1),
                                    'is_open' => (int) $round->id === $openRoundId,
                                    'date_label' => $date ? $date->format('d/m/').((int) $date->year + 543) : null,
                                    'date_full' => $date
                                        ? $date->day.' '.A5sRoundService::monthLabel((int) $date->month).' '.((int) $date->year + 543)
                                        : null,
                                    'score' => $task ? ($scores[(int) $task->id] ?? null) : null,
                                    // "ส่งไปกี่ครั้ง" = ครั้งที่ถูกตรวจแล้ว + ใบที่ยังรอตรวจอยู่อีก 1
                                    'submit_count' => $task
                                        ? $task->attempts->count() + (in_array($status, ['submitted', 'resubmitted'], true) ? 1 : 0)
                                        : 0,
                                    'status_key' => match (true) {
                                        $status === 'passed' => 'a5s.status.passed',
                                        $status === 'failed' => 'a5s.status.failed',
                                        in_array($status, ['submitted', 'resubmitted'], true) => 'a5s.status.submitted',
                                        default => 'a5s.status.no_data',
                                    },
                                    'attempts' => $task
                                        ? $this->a5sCalendarAttempts($task, $logsByTask->get($task->id, collect()), $users, $userPayload)
                                        : [],
                                ];
                            })
                            ->values()
                            ->all();

                        return [
                            'key' => $key,
                            'label' => A5sRoundService::monthLabel((int) $first->month).' '.$first->year,
                            'score' => A5sScoreService::average(array_column($roundRows, 'score')),
                            'rounds' => $roundRows,
                        ];
                    })
                    ->values()
                    ->all();
                return [$pointId => [
                    'score' => A5sScoreService::average(array_column($months, 'score')),
                    'months' => $months,
                ]];
            })
            ->all();
    }
    /**
     * รายละเอียดของแต่ละครั้งที่ส่ง สำหรับปฏิทินคะแนน
     * ผู้ส่ง / ส่งเมื่อ / ผู้ประเมิน / วันประเมิน / ข้อมูลที่ส่งมา (การ์ด ณ ครั้งนั้น) / หมายเหตุ
     * ครั้งที่ส่งแล้วแต่ยังไม่ถูกตัดสินไม่มีใน a5s_task_attempts จึงต่อท้ายให้เอง (state = pending)
     */
    protected function a5sCalendarAttempts(A5sTask $task, Collection $logs, Collection $users, callable $userPayload): array
    {
        $logForSeq = fn (int $seq) => $logs->last(
            fn (A5sActivityLog $log) => (int) data_get($log->detail_json, 'count', 0) === $seq
        );
        $submitterOf = function (?A5sActivityLog $log) use ($users, $userPayload): ?string {
            $snapshot = (array) data_get($log?->detail_json, 'submitter', []);
            if (! empty($snapshot['name'])) {
                return (string) $snapshot['name'];
            }
            $person = $log ? $userPayload($users->get((int) $log->user_id)) : null;

            return $person['name'] ?? null;
        };

        $rows = $task->attempts
            ->sortBy('seq')
            ->values()
            ->map(function (A5sTaskAttempt $attempt) use ($logForSeq, $submitterOf, $users, $userPayload) {
                $pass = $attempt->result === 'pass';
                $log = $logForSeq((int) $attempt->seq);
                $evaluator = $userPayload($users->get((int) $attempt->evaluated_by));

                return [
                    'state' => $pass ? 'pass' : 'fail',
                    'result' => $attempt->result,
                    'status_key' => $pass ? 'a5s.status.passed' : 'a5s.status.failed',
                    'status_default' => $pass ? 'ผ่าน' : 'ปฏิเสธ',
                    'score' => $pass ? 100 : 0,
                    'submitter' => $submitterOf($log),
                    'submitted_at' => $this->a5sBeDateTime($attempt->submitted_at) ?? $this->a5sBeDateTime($log?->created_at),
                    'evaluator' => $evaluator['name'] ?? null,
                    'evaluated_at' => $this->a5sBeDateTime($attempt->evaluated_at),
                    'cards' => collect($attempt->cards_json ?? [])->map(fn ($card) => [
                        'title' => (string) ($card['title'] ?? ''),
                        'detail' => (string) ($card['detail'] ?? ''),
                    ])->values()->all(),
                    'note' => $pass ? $attempt->advice : $attempt->fail_reason,
                ];
            });

        if (in_array($task->status, ['submitted', 'resubmitted'], true)) {
            $log = $logForSeq(max(1, (int) $task->submit_count)) ?: $logs->last();
            $rows->push([
                'state' => 'pending',
                'result' => null,
                'status_key' => 'a5s.status.submitted',
                'status_default' => 'รอดำเนินการ',
                'score' => null,
                'submitter' => $submitterOf($log),
                'submitted_at' => $this->a5sBeDateTime($task->submitted_at) ?? $this->a5sBeDateTime($log?->created_at),
                'evaluator' => null,
                'evaluated_at' => null,
                'cards' => $task->cards->map(fn ($card) => [
                    'title' => (string) $card->title,
                    'detail' => (string) $card->detail,
                ])->values()->all(),
                'note' => null,
            ]);
        }

        return $rows->values()->all();
    }
    protected function denyToSystems(string $key, string $fallback): RedirectResponse
    {
        return redirect()
            ->route('systems.index')
            ->with('toast_key', $key)
            ->with('toast_fallback', $fallback)
            ->with('toast_type', 'error');
    }

    /**
     * ซิงก์สำเนาผู้รับผิดชอบ/ผู้ประเมินใน a5s_tasks ของจุดนี้ให้ตรงกับของจริงทันที
     *
     * เดิมสำเนา (assignees_json/evaluators_json) เขียนแค่ 3 จังหวะ: เปิดรอบ / สร้าง task / กดส่งตรวจ
     * ถ้าแอดมินเปลี่ยนคนกลางรอบแล้วยังไม่มีใครส่งตรวจ → ดาวน์โหลดเอกสาร, คิวตรวจของผู้ประเมิน
     * และแจ้งเตือนตอนผ่าน/ปฏิเสธ จะยังอิงชื่อคนเก่า (Manager 2026-07-31)
     *
     * งานที่ "ผ่าน" แล้วถือเป็นประวัติถาวรตาม D7 — ไม่แตะ
     */
    protected function a5sSyncTaskPeople(A5sPoint $point): void
    {
        $point->loadMissing('layout');
        $roundId = (int) ($point->layout?->round_id ?? 0);
        if ($roundId <= 0) {
            return;
        }

        $task = A5sTask::where('round_id', $roundId)->where('point_id', $point->id)->first();
        if (! $task || $task->status === 'passed') {
            return;
        }

        $task->assignees_json = $this->a5sPeopleSnapshot(
            A5sPointAssignee::where('point_id', $point->id)->whereNull('removed_at')->pluck('employee_code')
        );
        $task->evaluators_json = $this->a5sPeopleSnapshot(
            A5sEvaluatorScope::where('layout_id', $point->layout_id)
                ->where(fn ($query) => $query->where('point_id', $point->id)->orWhereNull('point_id'))
                ->pluck('employee_code')
        );
        $task->save();
    }

    /** ผู้ประเมินแบบครอบทั้ง Layout (point_id = null) กระทบทุกจุด จึงต้องซิงก์ทั้งใบ */
    protected function a5sSyncLayoutTaskPeople(A5sLayout $layout): void
    {
        A5sPoint::where('layout_id', $layout->id)
            ->get()
            ->each(fn (A5sPoint $point) => $this->a5sSyncTaskPeople($point->setRelation('layout', $layout)));
    }

    /** [{code, name, name_th, ...}] จากรหัสพนักงาน — รูปแบบเดียวกับสำเนาตอนกดส่งตรวจ */
    protected function a5sPeopleSnapshot(Collection $codes): array
    {
        $codes = $codes->map(fn ($code) => trim((string) $code))->filter()->unique()->values();
        if ($codes->isEmpty()) {
            return [];
        }

        $employees = Employee::active()->whereIn('employee_code', $codes)->get()->keyBy('employee_code');

        return $codes->map(fn (string $code) => $this->a5sEmployeePayload($employees->get($code), $code))->all();
    }
}
