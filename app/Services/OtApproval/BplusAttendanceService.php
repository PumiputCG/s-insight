<?php

namespace App\Services\OtApproval;

use App\Models\Insight\AppUser;
use App\Models\Insight\Employee;
use App\Models\OtApproval\OtAttendanceSnapshot;
use App\Models\OtApproval\OtDepartmentAssignment;
use App\Support\OtApproval\OtPositionRank;
use App\Support\OtApproval\OtShiftGroup;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * อ่านเวลาเข้า-ออกจาก Bplus แบบ read-only สำหรับหน้า OT Approval
 *
 * - TMSTAMP: เวลาสแกนดิบของวันปัจจุบัน (Bplus ยังไม่ประมวลผล TMTRAN)
 * - TMTRAN: กะที่ถูกจัดให้พนักงานและเวลาเข้า-ออกที่ Bplus ประมวลผลแล้ว
 * - TMSHIFT: ชื่อกะ เวลาเริ่ม/เลิก และเวลาพัก
 * - PERSONALINFO: ใช้ PRS_NO จับคู่ employee_code ของ Insight
 */
class BplusAttendanceService
{
    /** ช่องว่างขั้นต่ำจากรูดเข้า ที่จะยอมนับรูดนั้นเป็น "ขาออก" — ดู clockOutFrom() */
    private const OUT_MIN_GAP_MINUTES = 30;

    /**
     * Local-only punch fixtures for checking the OT workflow without Bplus.
     * The key is work date + employee code so historical demo data remains deterministic.
     *
     * @var array<string, array{clock_in?:string,clock_out?:string,work_hours?:string}>
     */
    private const LOCAL_ATTENDANCE_FIXTURES = [
        '2026-08-09|70125' => [
            'clock_in' => '07:49',
            'clock_out' => '20:03',
            'work_hours' => '10.00',
        ],
        '2026-08-09|69938' => [
            'clock_out' => '17:01',
        ],
    ];

    /** @var array<string, string> */
    public const DATABASES = [
        'SUPAVUT_INDUSTRY' => 'BPLUSHRM_SUPAVUT_INDUSTRY',
        'MOLDVANTO' => 'BPLUSHRM_MOLDVANTO',
        'SUPAVUT_INNOMED' => 'BPLUSHRM_SUPAVUT_INNOMED',
    ];

    private bool $bplusUnavailable = false;

    private function attendanceSource(): string
    {
        $source = strtolower(trim((string) Config::get('ot_approval.attendance_source', 'bplus')));

        return in_array($source, ['bplus', 'local', 'auto'], true) ? $source : 'bplus';
    }

    private function canUseLocalFallback(): bool
    {
        return $this->attendanceSource() === 'auto' && app()->environment('local');
    }

    private function sourceLabel(string $sourceMode): string
    {
        return match ($sourceMode) {
            'local' => 'Local demo attendance',
            'snapshot' => 'Bplus snapshot in local database',
            'mixed' => 'Bplus + Local fallback',
            default => 'Bplus TMSTAMP + TMTRAN + TMSHIFT',
        };
    }

    /**
     * @param  array{all:bool,departments:array<int,array{company:string,dept_code:string}>,employees:array<int,array{company:string,employee_code:string}>}  $scope
     * @return array<string, mixed>
     */
    public function summary(CarbonInterface $date, array $scope): array
    {
        $employees = $this->scopedEmployeeQuery($scope, $date)
            ->orderBy('company')
            ->orderBy('dept_code')
            ->orderBy('employee_code')
            ->get([
                'company', 'employee_code', 'dept_code', 'dept_th', 'dept_en',
            ]);

        $companies = [];
        $sourceModes = [];

        foreach (self::DATABASES as $company => $database) {
            $companyEmployees = $employees->where('company', $company)->values();
            if ($companyEmployees->isEmpty()) {
                continue;
            }

            $attendance = $this->attendanceRows(
                $company,
                $date,
                $companyEmployees->pluck('employee_code')->all(),
            );
            $sourceModes[] = $attendance['source_mode'] ?? 'bplus';
            $attendanceMap = collect($attendance['rows'])->keyBy('employee_code');

            $departments = $companyEmployees
                ->groupBy(fn (Employee $employee) => trim((string) $employee->dept_code))
                ->map(function ($departmentEmployees, string $deptCode) use ($attendanceMap) {
                    /** @var Employee $sample */
                    $sample = $departmentEmployees->first();
                    $clockedIn = $departmentEmployees->filter(
                        fn (Employee $employee) => filled($attendanceMap->get($employee->employee_code)['clock_in'] ?? null),
                    )->count();
                    $clockedOut = $departmentEmployees->filter(
                        fn (Employee $employee) => filled($attendanceMap->get($employee->employee_code)['clock_out'] ?? null),
                    )->count();

                    return [
                        'code' => $deptCode,
                        'name_th' => $sample->departmentLabel() ?: 'ไม่ระบุแผนก',
                        'name_en' => $sample->departmentLabel() ?: 'Unassigned department',
                        'name_my' => $sample->departmentLabel() ?: 'Unassigned department',
                        'total' => $departmentEmployees->count(),
                        'clocked_in' => $clockedIn,
                        'clocked_out' => $clockedOut,
                        'shifts' => $this->shiftBreakdown($departmentEmployees, $attendanceMap),
                        'ot_requested' => 0,
                        'approved' => 0,
                    ];
                })
                ->sortByDesc('total')
                ->values();

            /* ยอดรายกะระดับบริษัท รวมจากแผนกที่นับไว้แล้ว ไม่วนพนักงานซ้ำอีกรอบ
               หัวการ์ดบริษัทและแถบสรุปรวมด้านบนใช้ค่าชุดนี้ จะได้ไม่คำนวณคนละที่แล้วเพี้ยนกัน */
            $companyShifts = [];
            foreach ([OtShiftGroup::MORNING, OtShiftGroup::NIGHT, OtShiftGroup::UNKNOWN] as $group) {
                $companyShifts[$group] = [
                    'total' => $departments->sum(fn (array $department) => $department['shifts'][$group]['total'] ?? 0),
                    'clocked_in' => $departments->sum(fn (array $department) => $department['shifts'][$group]['clocked_in'] ?? 0),
                    'clocked_out' => $departments->sum(fn (array $department) => $department['shifts'][$group]['clocked_out'] ?? 0),
                ];
            }

            $companies[] = [
                'code' => $company,
                'label' => OtDepartmentAssignment::COMPANIES[$company] ?? $company,
                'available' => $attendance['available'],
                'error' => $attendance['error'],
                'total' => $companyEmployees->count(),
                'clocked_in' => $departments->sum('clocked_in'),
                'clocked_out' => $departments->sum('clocked_out'),
                'ot_requested' => 0,
                'approved' => 0,
                'shifts' => $companyShifts,
                'departments' => $departments->all(),
            ];
        }

        $uniqueSourceModes = array_values(array_unique($sourceModes));
        $sourceMode = count($uniqueSourceModes) === 1
            ? $uniqueSourceModes[0]
            : (count($uniqueSourceModes) === 0 ? $this->attendanceSource() : 'mixed');

        return [
            'date' => $date->format('Y-m-d'),
            'is_today' => $date->isToday(),
            'generated_at' => now()->toIso8601String(),
            'source' => $this->sourceLabel($sourceMode),
            'source_mode' => $sourceMode,
            'companies' => $companies,
        ];
    }

    /**
     * ยอดคนและยอดสแกนของแผนก แยกตามกลุ่มกะ
     *
     * ใช้ทำการ์ดหน้าภาพรวมให้เห็นว่ากะไหนยังเข้าไม่ครบ ไม่ใช่เห็นแค่ยอดรวมของแผนก
     * วันที่ข้อมูลมาจาก snapshot ท้องถิ่นจะไม่มีรหัสกะจริง ทุกคนจึงตกกลุ่ม unknown
     * ฝั่งหน้าจอต้องเช็คก่อนแสดง ไม่งั้นจะขึ้นกะเวลา A 0 ทั้งที่มีคนมาทำงาน
     *
     * @param  \Illuminate\Support\Collection<int, Employee>  $employees
     * @param  \Illuminate\Support\Collection<string, array<string, mixed>>  $attendanceMap
     * @return array<string, array{total:int,clocked_in:int,clocked_out:int}>
     */
    private function shiftBreakdown($employees, $attendanceMap): array
    {
        $groups = [OtShiftGroup::MORNING, OtShiftGroup::NIGHT, OtShiftGroup::UNKNOWN];
        $breakdown = [];

        foreach ($groups as $group) {
            $members = $employees->filter(
                fn (Employee $employee) => OtShiftGroup::of(
                    $attendanceMap->get($employee->employee_code)['shift_code'] ?? null,
                ) === $group,
            );

            $breakdown[$group] = [
                'total' => $members->count(),
                'clocked_in' => $members->filter(
                    fn (Employee $employee) => filled($attendanceMap->get($employee->employee_code)['clock_in'] ?? null),
                )->count(),
                'clocked_out' => $members->filter(
                    fn (Employee $employee) => filled($attendanceMap->get($employee->employee_code)['clock_out'] ?? null),
                )->count(),
                'times' => $this->shiftTimes($members, $attendanceMap),
            ];
        }

        return $breakdown;
    }

    /**
     * ช่วงเวลาของกะทุกรหัสในกลุ่มเดียวกัน เรียงจากกะที่มีคนมากที่สุด
     *
     * กลุ่มเดียวมีได้หลายรหัส เช่นกะเวลา Aของบางแผนกมีทั้ง 08:00 และ 07:30
     * หน้าจอจึงต้องได้รายการทั้งหมด ไม่ใช่หยิบกะแรกมาแสดงแล้วเข้าใจว่าทั้งกลุ่มเข้าเวลาเดียวกัน
     *
     * @param  \Illuminate\Support\Collection<int, Employee>  $members
     * @param  \Illuminate\Support\Collection<string, array<string, mixed>>  $attendanceMap
     * @return array<int, array{code:string,range:string,people:int}>
     */
    private function shiftTimes($members, $attendanceMap): array
    {
        $times = [];

        foreach ($members as $employee) {
            $row = $attendanceMap->get($employee->employee_code) ?? [];
            $in = substr((string) ($row['shift_in'] ?? ''), 0, 5);
            $out = substr((string) ($row['shift_out'] ?? ''), 0, 5);
            if ($in === '' || $out === '') {
                continue;
            }

            $range = $in.'–'.$out;
            if (! isset($times[$range])) {
                $times[$range] = [
                    'code' => trim((string) ($row['shift_code'] ?? '')),
                    'range' => $range,
                    'people' => 0,
                ];
            }
            $times[$range]['people']++;
        }

        return collect($times)->sortByDesc('people')->values()->all();
    }

    /**
     * @param  array{all:bool,departments:array<int,array{company:string,dept_code:string}>,employees:array<int,array{company:string,employee_code:string}>}  $scope
     * @return array<string, mixed>
     */
    public function employees(
        CarbonInterface $date,
        string $company,
        string $deptCode,
        array $scope,
    ): array {
        if (! array_key_exists($company, self::DATABASES)) {
            return $this->emptyEmployeePayload($date, $company, $deptCode, false);
        }

        $query = $this->scopedEmployeeQuery($scope, $date)->where('company', $company);
        $this->whereDepartment($query, $deptCode);

        $employees = $query
            ->orderBy('employee_code')
            ->get([
                'company', 'employee_code', 'license_id', 'name_th', 'surname_th', 'title', 'name_en',
                'job_code', 'job_th', 'job_en', 'dept_code', 'dept_th', 'dept_en',
                'branch_code', 'branch_th', 'branch_en',
            ]);

        if ($employees->isEmpty()) {
            return $this->emptyEmployeePayload($date, $company, $deptCode, true);
        }

        $attendance = $this->attendanceRows($company, $date, $employees->pluck('employee_code')->all());
        $attendanceMap = collect($attendance['rows'])->keyBy('employee_code');
        $identities = $employees->pluck('license_id')->filter()->unique()->values();
        $users = $identities->isEmpty()
            ? collect()
            : AppUser::whereIn('id_thai_hash', $identities)->get()->keyBy('id_thai_hash');

        /** @var Employee $sample */
        $sample = $employees->first();
        $rows = $employees->map(function (Employee $employee) use ($attendanceMap, $users) {
            $attendanceRow = $attendanceMap->get($employee->employee_code, $this->emptyAttendanceRow($employee->employee_code));
            $user = $employee->license_id ? $users->get($employee->license_id) : null;
            $nameTh = $employee->fullNameTh() ?: $employee->fullNameEn() ?: $employee->employee_code;
            $nameEn = $employee->fullNameEn() ?: $nameTh;

            return array_merge($attendanceRow, [
                'company' => $employee->company,
                'code' => $employee->employee_code,
                'job_code' => $employee->job_code,
                'name_th' => $nameTh,
                'name_en' => $nameEn,
                'name_my' => $nameEn,
                'position_th' => $employee->job_th ?: $employee->job_en ?: '-',
                'position_en' => $employee->job_en ?: $employee->job_th ?: '-',
                'position_my' => $employee->job_en ?: $employee->job_th ?: '-',
                'department_th' => $employee->deptThClean() ?: $employee->dept_en ?: '-',
                'department_en' => $employee->dept_en ?: $employee->deptThClean() ?: '-',
                'department_my' => $employee->dept_en ?: $employee->deptThClean() ?: '-',
                /* สาขาใช้ทำตัวกรอง `โรงงาน` กับ `โรงงาน-พม่า` — Bplus ไม่มีฟิลด์สัญชาติที่ใช้ได้
                   บริษัทแบ่งคนไทย/คนพม่าด้วยสาขาแทน (ดู migration 2026_08_19_020000) */
                /* สถานะมาทำงาน — Bplus ไม่มีฟิลด์นี้ ต้องสรุปเอง
                   "ไม่มีสแกน" ไม่เท่ากับ "ขาดงาน" เสมอไป วันหยุดของคนนั้นก็ไม่มีสแกน
                   จึงอ่านชนิดวันจากชื่อกะของ Bplus (วันงาน / วันหยุด / วันหยุดนักขัตฤกษ์) มาแยกก่อน */
                'presence' => $this->presenceOf($attendanceRow),
                // ลำดับอาวุโสของตำแหน่ง ใช้เรียงจากสูงไปต่ำ (ดู OtPositionRank)
                'position_rank' => OtPositionRank::of($employee->job_code),
                'branch_code' => trim((string) $employee->branch_code),
                'branch_th' => $employee->branch_th ?: $employee->branch_en ?: '',
                'branch_en' => $employee->branch_en ?: $employee->branch_th ?: '',
                'avatar' => $user?->profile_picture ? asset('storage/'.$user->profile_picture) : null,
                'ot_status' => 'not_requested',
                'approval_status' => 'not_requested',
            ]);
        })->values();

        return [
            'date' => $date->format('Y-m-d'),
            'company' => $company,
            'company_label' => OtDepartmentAssignment::COMPANIES[$company] ?? $company,
            'dept_code' => $deptCode,
            'department_th' => $sample->deptThClean() ?: $sample->dept_en ?: 'ไม่ระบุแผนก',
            'department_en' => $sample->dept_en ?: $sample->deptThClean() ?: 'Unassigned department',
            'department_my' => $sample->dept_en ?: $sample->deptThClean() ?: 'Unassigned department',
            'available' => $attendance['available'],
            'error' => $attendance['error'],
            'source_mode' => $attendance['source_mode'] ?? 'bplus',
            'generated_at' => now()->toIso8601String(),
            'employees' => $rows->all(),
        ];
    }

    /**
     * แปลงแถว Bplus เป็นข้อมูลเวลาเดียวกันสำหรับทั้งข้อมูลสดและข้อมูลย้อนหลัง
     *
     * @param  array<string, mixed>|object  $row
     * @return array<string, mixed>
     */
    public function normalizeAttendanceRow(array|object $row): array
    {
        $row = (array) $row;
        $shiftIn = $this->timeOnly($row['shift_in'] ?? null);
        $shiftOut = $this->timeOnly($row['shift_out'] ?? null);
        // SF_OUT_DAY = 2 คือกะที่เลิกงานวันถัดไป (กะเวลา B) — Bplus เป็นคนบอก ไม่ใช่เราเดาจากเวลา
        $crossesMidnight = ((int) ($row['shift_out_day'] ?? 1)) >= 2
            || ($shiftIn !== null && $shiftOut !== null && $shiftOut <= $shiftIn);

        $punches = [];       // รูดของวันทำงาน (ใช้แสดงผลและนับจำนวน)
        $nextPunches = [];   // รูดของวันถัดไป ใช้เฉพาะกะข้ามคืน

        for ($number = 1; $number <= 14; $number++) {
            $time = $this->timeOnly($row['raw_stamp_'.$number] ?? null);
            if ($time !== null) {
                $punches[] = $time;
            }

            $nextTime = $this->timeOnly($row['next_stamp_'.$number] ?? null);
            if ($nextTime !== null) {
                $nextPunches[] = $nextTime;
            }
        }

        $processedIn = $this->timeOnly($row['processed_in'] ?? null);
        $processedOut = $this->timeOnly($row['processed_out'] ?? null);

        /* Bplus ประมวลผลวันนั้นแล้ว = ตรวจรูดบัตรครบและตัดสินแล้วว่าอันไหนคือขาเข้า/ขาออก
           ถ้ามันเว้นช่องไหนไว้ว่าง แปลว่าไม่มีรูดที่ใช้ได้สำหรับช่องนั้น (เช่น กะ 08:00
           แต่รูดครั้งแรก 15:02 ซึ่งสายเกินกว่าจะนับเป็นขาเข้า) เราจึงห้ามเดาจากรูดดิบมาเติม
           เพราะจะขัดกับบันทึกทางการที่ HR ใช้คิดค่าแรง */
        $isProcessed = $processedIn !== null || $processedOut !== null;

        if ($isProcessed) {
            $clockIn = $processedIn;
            $clockOut = $processedOut;
        } elseif ($crossesMidnight) {
            /* กะเวลา B: รูดของวันทำงานมีทั้ง "ขาออกของกะเมื่อวาน" (ตอนเช้า) ปนกับ
               "ขาเข้าของกะคืนนี้" (ตอนเย็น) ถ้าหยิบตัวแรกจะได้ของกะเมื่อวานมาผิด ๆ
               จึงตัดเฉพาะรูดที่อยู่ตั้งแต่ใกล้เวลาเข้ากะเป็นต้นไป */
            $shiftPunches = $this->punchesFromShiftStart($punches, $shiftIn);
            $clockIn = $shiftPunches[0] ?? null;
            // ขาออกอยู่ในวันถัดไป เอารูดสุดท้ายที่ยังไม่เลยกรอบกะไปไกล
            $outCandidates = $this->punchesBeforeShiftEnd($nextPunches, $shiftOut);

            /* ไม่มีขาเข้า = ไม่ได้เริ่มกะนี้ รูดตอนเช้าของวันถัดไปจึงเป็นของกะอื่น
               (เช่น ขาเข้าของกะเวลา Aวันถัดไป) ห้ามนับเป็นขาออกของกะนี้ */
            if ($clockIn === null) {
                $outCandidates = [];
            }

            // ขาออกของกะเวลา Bอยู่คนละวันกับขาเข้า ต้องบวก 24 ชม. ตอนวัดช่วงห่าง
            $clockOut = $this->clockOutFrom($clockIn, $outCandidates, crossesMidnight: true);

            /* รายการรูดที่เก็บไว้ต้องเป็นของกะนี้เท่านั้น ไม่ใช่ทุกรูดที่บังเอิญลงวันเดียวกัน
               ไม่งั้นหน้าจอจะโชว์ขาออกของเมื่อคืนปนอยู่ในแถวของวันนี้ */
            $punches = array_merge($shiftPunches, $outCandidates);
        } else {
            $clockIn = $punches[0] ?? null;
            $clockOut = $this->clockOutFrom($clockIn, $punches);
        }

        $clockOutIsFinal = $processedOut !== null;

        return [
            'employee_code' => trim((string) ($row['employee_code'] ?? '')),
            'shift_code' => trim((string) ($row['shift_code'] ?? '')),
            'shift_name_th' => trim((string) ($row['shift_name_th'] ?? '')),
            'shift_name_en' => trim((string) ($row['shift_name_en'] ?? '')),
            'shift_in' => $shiftIn,
            'shift_out' => $shiftOut,
            'break_in' => $this->timeOnly($row['break_in'] ?? null),
            'break_out' => $this->timeOnly($row['break_out'] ?? null),
            'clock_in' => $clockIn,
            'clock_out' => $clockOut,
            'punch_count' => count($punches),
            'punches' => $punches,
            'work_hours' => $this->workHours($row['work_hours'] ?? null),
            'attendance_source' => $isProcessed ? 'processed' : 'raw',
            'attendance_state' => $clockIn === null
                ? 'not_scanned'
                : ($clockOut === null ? 'in_only' : ($clockOutIsFinal ? 'final_out' : 'latest_scan')),
            'clock_out_is_final' => $clockOutIsFinal,
        ];
    }

    /**
     * สรุปว่ามาทำงานหรือไม่ จากเวลาสแกนและชนิดของวันที่ Bplus จัดให้
     *
     * คืน `present` / `absent` / `dayoff` — แยก "วันหยุด" ออกจาก "ขาดงาน"
     * เพราะทั้งสองอย่างไม่มีเวลาสแกนเหมือนกัน ถ้าไม่แยกวันหยุดทั้งแผนกจะขึ้นว่าขาดงาน
     *
     * @param  array<string, mixed>  $row
     */
    private function presenceOf(array $row): string
    {
        if (($row['clock_in'] ?? null) !== null) {
            return 'present';
        }

        /* ชื่อกะของ Bplus ขึ้นต้นด้วยชนิดของวันเสมอ เช่น `วันงาน 08.00-17.00 น.`
           หรือ `วันหยุด 08.00-17.00 น.` — ใช้ตรงนี้แยกวันหยุดออกจากการขาดงาน
           วันที่ยังไม่มีข้อมูลกะเลย (ยังไม่จัดกะ) ไม่ฟันธงว่าขาด ให้เป็นวันหยุดไว้ก่อน */
        $shiftName = trim((string) ($row['shift_name_th'] ?? ''));
        if ($shiftName === '' || str_contains($shiftName, 'วันหยุด')) {
            return 'dayoff';
        }

        return 'absent';
    }

    /**
     * เลือกรูดที่นับเป็น "ขาออก" ได้จริง — ไม่ใช่หยิบรูดสุดท้ายมาดื้อ ๆ
     *
     * เครื่องสแกนหน้าเก็บทุกครั้งที่รูด พนักงานที่รูดซ้ำเพราะเครื่องไม่ตอบสนอง
     * (เช่น 07:39 แล้วรูดใหม่ 07:40) จะมี 2 รูดทั้งที่ยังไม่ได้ออกไปไหน
     * กฎเดิม "มีรูด >= 2 ครั้ง แปลว่ารูดสุดท้ายคือขาออก" จึงทำให้หน้าภาพรวม
     * ขึ้นว่าออกงานแล้วตั้งแต่ 07:40 ทั้งที่กะเพิ่งจะเริ่ม
     *
     * เส้นแบ่งมาจากข้อมูลจริงของ Bplus 7 วัน (กะปกติ 3,397 แถว · กะเวลา B 2,023 แถว):
     *   - ช่วงเข้า-ออกที่สั้นที่สุดที่ Bplus เคยรับคือ 41 นาที (เข้า 07:18 ออก 07:59)
     *   - กลุ่มรูดซ้ำหน้าเครื่องที่ห่างกันมากที่สุดที่พบคือ 17 นาที
     * 30 นาทีจึงอยู่กลางระหว่างสองค่านี้ ตัดรูดซ้ำได้หมดโดยไม่ทับของจริง
     *
     * **ห้ามเปลี่ยนไปใช้เกณฑ์ "ขาออกต้องใกล้เวลาเลิกกะ"** เพราะข้อมูลจริงมีคนออก
     * ก่อนเลิกกะหลายชั่วโมงแล้ว Bplus รับเป็นขาออกจริง (เช่น เข้า 07:48 ออก 09:36)
     * ถ้าไปตัดทิ้ง คนที่ออกก่อนเวลาจะกลายเป็น "ยังไม่ออก" ค้างทั้งวัน
     *
     * @param  array<int, string>  $candidates  รูดที่เป็นไปได้ เรียงตามเวลาแล้ว
     * @param  bool  $crossesMidnight  ขาออกอยู่วันถัดไป (กะเวลา B) ต้องบวก 24 ชม.
     */
    private function clockOutFrom(?string $clockIn, array $candidates, bool $crossesMidnight = false): ?string
    {
        if ($clockIn === null || $candidates === []) {
            return null;
        }

        $last = $candidates[array_key_last($candidates)];
        if ($last === $clockIn) {
            return null;
        }

        $gap = $this->minutesOf($last) - $this->minutesOf($clockIn);
        if ($crossesMidnight) {
            $gap += 24 * 60;
        }

        return $gap >= self::OUT_MIN_GAP_MINUTES ? $last : null;
    }

    /**
     * รูดของ "วันทำงาน" ที่นับเป็นขาเข้าของกะเวลา Bได้
     *
     * ตัดรูดตอนเช้าทิ้ง เพราะนั่นคือขาออกของกะเมื่อวานที่มาลงวันเดียวกัน
     * เผื่อมาก่อนเวลาเข้ากะได้ 4 ชั่วโมง (กะ 20:00 จึงรับตั้งแต่ 16:00)
     * แต่ไม่ว่ากรณีใดต้องไม่รับรูดก่อนเที่ยง เพราะกะเวลา Bไม่มีทางเริ่มตอนเช้า
     * (กะ 15:00 ลบ 4 ชม. จะได้ 11:00 ซึ่งเสี่ยงไปคาบเกี่ยวรูดขาออกของกะเมื่อวาน)
     *
     * **ห้ามมี fallback คืนรูดทั้งหมดเมื่อกรองแล้วไม่เหลือ** — ตอนเช้าของวันที่กะเวลา B
     * ยังไม่เริ่ม (เช่น 08:30 ของกะ 20:00) จะมีแต่รูดขาออกของเมื่อคืนอยู่ในวันนั้น
     * ถ้าคืนทั้งหมดจะกลายเป็น "เข้างานแล้ว 08:04" ทั้งที่กะยังไม่เริ่ม
     *
     * @param  array<int, string>  $punches
     * @return array<int, string>
     */
    private function punchesFromShiftStart(array $punches, ?string $shiftIn): array
    {
        if ($shiftIn === null) {
            return $punches;
        }

        $earliest = max($this->minutesOf($shiftIn) - (4 * 60), 12 * 60);

        return array_values(array_filter(
            $punches,
            fn (string $punch) => $this->minutesOf($punch) >= $earliest,
        ));
    }

    /**
     * รูดของ "วันถัดไป" ที่นับเป็นขาออกของกะเวลา Bได้
     *
     * เผื่อออกช้ากว่าเวลาเลิกกะได้ 6 ชั่วโมง เพื่อรองรับ OT หลังเลิกกะ
     * (กะเลิก 05:00 ทำ OT ต่อถึง 08:00 แล้วรูดออก 08:26 ต้องยังนับ)
     * แต่ต้องไม่ไปกินรูดเข้าของกะคืนถัดไปที่ลงวันเดียวกัน
     *
     * @param  array<int, string>  $punches
     * @return array<int, string>
     */
    private function punchesBeforeShiftEnd(array $punches, ?string $shiftOut): array
    {
        if ($shiftOut === null) {
            return $punches;
        }

        $latest = $this->minutesOf($shiftOut) + (6 * 60);

        return array_values(array_filter(
            $punches,
            fn (string $punch) => $this->minutesOf($punch) <= $latest,
        ));
    }

    /** 08:26 -> 506 นาทีนับจากเที่ยงคืน ใช้เทียบช่วงเวลาแบบไม่ต้องแปลงเป็นวันที่ */
    private function minutesOf(string $time): int
    {
        [$hour, $minute] = array_pad(explode(':', $time), 2, '0');

        return ((int) $hour * 60) + (int) $minute;
    }

    /**
     * @param  array{all:bool,departments:array<int,array{company:string,dept_code:string}>,employees:array<int,array{company:string,employee_code:string}>}  $scope
     */
    private function scopedEmployeeQuery(array $scope, CarbonInterface $date): Builder
    {
        $query = Employee::activeAt($date);
        if ($scope['all'] ?? false) {
            return $query;
        }

        $departments = $scope['departments'] ?? [];
        $employees = $scope['employees'] ?? [];

        if ($departments === [] && $employees === []) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function (Builder $where) use ($departments, $employees) {
            foreach ($departments as $department) {
                $where->orWhere(function (Builder $departmentQuery) use ($department) {
                    $departmentQuery->where('company', $department['company']);
                    $this->whereDepartment($departmentQuery, trim((string) $department['dept_code']));
                });
            }

            foreach ($employees as $employee) {
                $where->orWhere(function (Builder $employeeQuery) use ($employee) {
                    $employeeQuery
                        ->where('company', $employee['company'])
                        ->where('employee_code', $employee['employee_code']);
                });
            }
        });
    }

    private function whereDepartment(Builder $query, string $deptCode): void
    {
        if ($deptCode === '') {
            $query->where(fn (Builder $where) => $where->whereNull('dept_code')->orWhere('dept_code', ''));

            return;
        }

        $query->where('dept_code', $deptCode);
    }

    /**
     * @param  string[]  $employeeCodes
     * @return array{available:bool,error:?string,source_mode:string,rows:array<int,array<string,mixed>>}
     */
    private function attendanceRows(string $company, CarbonInterface $date, array $employeeCodes): array
    {
        $database = self::DATABASES[$company] ?? null;
        if (! $database || $employeeCodes === []) {
            return ['available' => true, 'error' => null, 'source_mode' => 'bplus', 'rows' => []];
        }

        if ($this->attendanceSource() === 'local') {
            return $this->localAttendanceRows($company, $date, $employeeCodes);
        }

        if ($this->canUseLocalFallback() && $this->bplusUnavailable) {
            return $this->localAttendanceRows($company, $date, $employeeCodes);
        }

        try {
            $rows = $this->readBplusAttendanceRows($company, $date, $employeeCodes);

            return ['available' => true, 'error' => null, 'source_mode' => 'bplus', 'rows' => $rows];
        } catch (Throwable) {
            if ($this->canUseLocalFallback()) {
                $this->bplusUnavailable = true;

                return $this->localAttendanceRows($company, $date, $employeeCodes);
            }

            return [
                'available' => false,
                'error' => 'Bplus attendance is temporarily unavailable.',
                'source_mode' => 'bplus',
                'rows' => [],
            ];
        }
    }

    /**
     * อ่าน Attendance จริงจาก Bplus โดยไม่สนค่า source ใน config สำหรับคำสั่งสร้าง Local Snapshot
     *
     * @param  string[]  $employeeCodes
     * @return array<int,array<string,mixed>>
     */
    public function readBplusAttendanceRows(
        string $company,
        CarbonInterface $date,
        array $employeeCodes,
    ): array {
        $database = self::DATABASES[$company] ?? null;
        if ($database === null || $employeeCodes === []) {
            return [];
        }

        Config::set('database.connections.bplus.database', $database);
        DB::purge('bplus');
        $connection = DB::connection('bplus');
        $rows = [];

        foreach (array_chunk(array_values(array_unique($employeeCodes)), 1000) as $codes) {
            $placeholders = implode(',', array_fill(0, count($codes), '?'));
            $stampColumns = collect(range(1, 14))
                ->map(fn (int $number) => "CONVERT(varchar(19), m.TMM_STAMP_{$number}, 120) AS raw_stamp_{$number}")
                ->implode(', ');
            /* กะข้ามคืน (SF_OUT_DAY = 2) รูดออกจะไปอยู่ใน TMSTAMP ของ "วันถัดไป"
               ถ้าอ่านแค่วันเดียวจะได้รูดออกของกะเมื่อวานมาเป็นเวลาเข้าแทน จึงต้องดึงวันถัดไปมาด้วย */
            $nextStampColumns = collect(range(1, 14))
                ->map(fn (int $number) => "CONVERT(varchar(19), mn.TMM_STAMP_{$number}, 120) AS next_stamp_{$number}")
                ->implode(', ');
            $sql = "SELECT p.PRS_NO AS employee_code,
                s.SF_CODE AS shift_code,
                s.SF_NAME AS shift_name_th,
                s.SF_E_NAME AS shift_name_en,
                CONVERT(varchar(8), s.SF_IN_TIME, 108) AS shift_in,
                CONVERT(varchar(8), s.SF_OUT_TIME, 108) AS shift_out,
                CONVERT(varchar(8), s.SF_BRKI_TIME, 108) AS break_in,
                CONVERT(varchar(8), s.SF_BRKO_TIME, 108) AS break_out,
                s.SF_OUT_DAY AS shift_out_day,
                CONVERT(varchar(19), t.TMT_STAMP_IN, 120) AS processed_in,
                CONVERT(varchar(19), t.TMT_STAMP_OUT, 120) AS processed_out,
                CAST(t.TMT_WORK_HOUR AS decimal(10,2)) AS work_hours,
                {$stampColumns},
                {$nextStampColumns}
            FROM dbo.PERSONALINFO p
            LEFT JOIN dbo.TMTRAN t ON t.TMT_EMP = p.PRS_EMP AND t.TMT_DATE = ?
            LEFT JOIN dbo.TMSHIFT s ON s.SF_KEY = t.TMT_SF
            LEFT JOIN dbo.TMSTAMP m ON m.TMM_EMP = p.PRS_EMP AND m.TMM_DATE = ?
            LEFT JOIN dbo.TMSTAMP mn ON mn.TMM_EMP = p.PRS_EMP AND mn.TMM_DATE = ?
            WHERE p.PRS_NO IN ({$placeholders})";

            $bindings = array_merge([
                $date->format('Y-m-d'),
                $date->format('Y-m-d'),
                $date->copy()->addDay()->format('Y-m-d'),
            ], $codes);
            foreach ($connection->select($sql, $bindings) as $row) {
                $rows[] = $this->normalizeAttendanceRow($row);
            }
        }

        return $rows;
    }

    /**
     * Deterministic Local-only attendance for UI and workflow development.
     * It is never used unless OT_APPROVAL_ATTENDANCE_SOURCE=local (or auto in
     * the local Laravel environment), so it cannot replace Bplus in production.
     *
     * @param  string[]  $employeeCodes
     * @return array{available:bool,error:?string,source_mode:string,rows:array<int,array<string,mixed>>}
     */
    private function localAttendanceRows(
        string $company,
        CarbonInterface $date,
        array $employeeCodes,
    ): array {
        $snapshotQuery = OtAttendanceSnapshot::query()
            ->where('company', $company)
            ->whereDate('work_date', $date->format('Y-m-d'));

        if ((clone $snapshotQuery)->exists()) {
            $rows = $snapshotQuery
                ->whereIn('employee_code', array_values(array_unique($employeeCodes)))
                ->get()
                ->map(fn (OtAttendanceSnapshot $snapshot) => $this->snapshotAttendanceRow($snapshot))
                ->all();

            return [
                'available' => true,
                'error' => null,
                'source_mode' => 'snapshot',
                'rows' => $rows,
            ];
        }

        $rows = [];

        foreach (array_values(array_unique($employeeCodes)) as $employeeCode) {
            $seed = abs(crc32($date->format('Y-m-d').'|'.$employeeCode));
            $bucket = $seed % 10;
            $clockIn = null;
            $clockOut = null;
            $workHours = null;

            if ($bucket === 1) {
                $clockIn = sprintf('07:%02d', 48 + ($seed % 10));
            } elseif ($bucket >= 2) {
                $clockIn = $bucket === 8 ? '08:15' : sprintf('07:%02d', 45 + ($seed % 12));
                $clockOut = $bucket === 9 ? '19:10' : ($bucket === 8 ? '16:50' : '17:05');
                $workHours = $bucket === 9 ? '10.17' : ($bucket === 8 ? '7.58' : '8.08');
            }

            $fixture = self::LOCAL_ATTENDANCE_FIXTURES[$date->format('Y-m-d').'|'.$employeeCode] ?? null;
            if ($fixture !== null) {
                $clockIn = $fixture['clock_in'] ?? $clockIn;
                $clockOut = $fixture['clock_out'] ?? $clockOut;
                $workHours = $fixture['work_hours'] ?? $workHours;
            }

            $normalized = $this->normalizeAttendanceRow([
                'employee_code' => $employeeCode,
                'shift_code' => 'LOCAL-0800',
                'shift_name_th' => '',
                'shift_name_en' => '',
                'shift_in' => '08:00:00',
                'shift_out' => '17:00:00',
                'break_in' => '12:00:00',
                'break_out' => '13:00:00',
                'raw_stamp_1' => $clockIn,
                'raw_stamp_2' => $clockOut,
                'work_hours' => $workHours,
            ]);
            $normalized['attendance_source'] = 'local';
            $rows[] = $normalized;
        }

        return ['available' => true, 'error' => null, 'source_mode' => 'local', 'rows' => $rows];
    }

    /** @return array<string, mixed> */
    private function snapshotAttendanceRow(OtAttendanceSnapshot $snapshot): array
    {
        return [
            'employee_code' => $snapshot->employee_code,
            'shift_code' => (string) ($snapshot->shift_code ?? ''),
            'shift_name_th' => (string) ($snapshot->shift_name_th ?? ''),
            'shift_name_en' => (string) ($snapshot->shift_name_en ?? ''),
            'shift_in' => $this->timeOnly($snapshot->shift_in),
            'shift_out' => $this->timeOnly($snapshot->shift_out),
            'break_in' => $this->timeOnly($snapshot->break_in),
            'break_out' => $this->timeOnly($snapshot->break_out),
            'clock_in' => $this->timeOnly($snapshot->clock_in),
            'clock_out' => $this->timeOnly($snapshot->clock_out),
            'punch_count' => (int) $snapshot->punch_count,
            'punches' => array_values((array) $snapshot->punches),
            'work_hours' => $snapshot->work_hours,
            'attendance_source' => $snapshot->attendance_source,
            'attendance_state' => $snapshot->attendance_state,
            // Snapshot รุ่นเก่าใช้ has_pair + processed ก่อนมี state final_out
            'clock_out_is_final' => $snapshot->attendance_state === 'final_out'
                || ($snapshot->attendance_state === 'has_pair' && $snapshot->attendance_source === 'processed'),
        ];
    }

    private function timeOnly(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $text = trim((string) $value);
        if (preg_match('/(\d{2}):(\d{2})(?::\d{2})?$/', $text, $matches) !== 1) {
            return null;
        }

        return $matches[1].':'.$matches[2];
    }

    private function workHours(mixed $value): ?string
    {
        if ($value === null || $value === '' || (float) $value <= 0) {
            return null;
        }

        return number_format((float) $value, 2, '.', '');
    }

    /** @return array<string, mixed> */
    public function emptyAttendanceRow(string $employeeCode): array
    {
        return [
            'employee_code' => $employeeCode,
            'shift_code' => '',
            'shift_name_th' => '',
            'shift_name_en' => '',
            'shift_in' => null,
            'shift_out' => null,
            'break_in' => null,
            'break_out' => null,
            'clock_in' => null,
            'clock_out' => null,
            'punch_count' => 0,
            'punches' => [],
            'work_hours' => null,
            'attendance_source' => 'raw',
            'attendance_state' => 'not_scanned',
            'clock_out_is_final' => false,
        ];
    }

    /** @return array<string, mixed> */
    private function emptyEmployeePayload(
        CarbonInterface $date,
        string $company,
        string $deptCode,
        bool $available,
    ): array {
        return [
            'date' => $date->format('Y-m-d'),
            'company' => $company,
            'company_label' => OtDepartmentAssignment::COMPANIES[$company] ?? $company,
            'dept_code' => $deptCode,
            'department_th' => 'ไม่พบแผนก',
            'department_en' => 'Department not found',
            'department_my' => 'Department not found',
            'available' => $available,
            'error' => null,
            'source_mode' => $this->attendanceSource(),
            'generated_at' => now()->toIso8601String(),
            'employees' => [],
        ];
    }
}
