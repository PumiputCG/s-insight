<?php

namespace App\Services\OtApproval;

use App\Models\Insight\AppUser;
use App\Models\Insight\Employee;
use App\Models\OtApproval\OtRequest;
use App\Models\OtApproval\OtRequestApproval;
use App\Support\OtApproval\OtBranchFilter;
use App\Support\OtApproval\OtEmployeeEligibility;
use App\Support\OtApproval\OtShiftGroup;
use App\Support\OtApproval\PayrollCycle;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OtRequestWorkflowService
{
    /** เหตุผลที่ระบบใส่ให้เอง เมื่อปฏิเสธอัตโนมัติเพราะเวลาสแกนตกกฎ */
    public const AUTO_REJECT_NOTE = 'ระบบปฏิเสธอัตโนมัติ: เวลาสแกนไม่ครอบคลุมช่วง OT ที่ขอ';

    /** @var array<string, Collection<string, array<string, mixed>>> */
    private array $attendanceCache = [];

    /** @var array<string, string> ชื่อผู้ดำเนินการต่อรหัสพนักงาน จำไว้ในรีเควสต์เดียว */
    private array $actorNameCache = [];

    public function __construct(
        private readonly BplusAttendanceService $attendance,
        private readonly OtAttendanceEvaluator $evaluator,
        private readonly OtTimeRangeCalculator $timeRange,
    ) {}

    /** @return array<int, array<string, mixed>> */
    public function types(): array
    {
        return collect((array) config('ot_approval.types', []))
            ->filter(fn (array $type) => ($type['selectable'] ?? true) === true)
            ->map(fn (array $type, string $key) => array_merge($type, ['key' => $key]))
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $summary
     * @param  array<string, mixed>  $scope
     * @return array<string, mixed>
     */
    public function decorateSummary(array $summary, CarbonInterface $date, array $scope): array
    {
        $query = OtRequest::query()->notCancelled()->whereDate('work_date', $date->format('Y-m-d'));
        $this->applyScope($query, $scope);
        // ต้องได้ shift_code มาด้วย เพราะการ์ดแผนกแยกยอด "ขอ OT" ตามกะเวลา A/กะเวลา B
        $requests = $query->get(['company', 'dept_code', 'shift_code', 'approval_status', 'attendance_status']);

        if (! isset($summary['companies']) || ! is_array($summary['companies'])) {
            return $summary;
        }

        foreach ($summary['companies'] as &$company) {
            $companyRequests = $requests->where('company', $company['code']);
            $company['ot_requested'] = $companyRequests->count();
            $company['completed'] = $companyRequests->filter(
                fn (OtRequest $request) => $request->approval_status === OtRequest::APPROVAL_APPROVED
                    && $request->attendance_status === OtRequest::ATTENDANCE_PASSED,
            )->count();

            foreach ($company['departments'] ?? [] as &$department) {
                $departmentRequests = $companyRequests->filter(
                    fn (OtRequest $request) => trim((string) $request->dept_code) === trim((string) $department['code']),
                );
                $department['ot_requested'] = $departmentRequests->count();
                $department['completed'] = $departmentRequests->filter(
                    fn (OtRequest $request) => $request->approval_status === OtRequest::APPROVAL_APPROVED
                        && $request->attendance_status === OtRequest::ATTENDANCE_PASSED,
                )->count();

                /* ยอดขอ OT แยกกะ ให้การ์ดแผนกอ่านคู่กับยอดสแกนเข้าที่ BplusAttendanceService ใส่มาแล้ว
                   จัดกลุ่มด้วยกะที่เก็บไว้ในตัวคำขอ ไม่ใช่กะปัจจุบันของพนักงาน เพราะคำขอที่อนุมัติแล้ว
                   ตรึงกะไว้ตามตอนอนุมัติ (ดู syncShiftFromBplus) ยอดจึงตรงกับเอกสารที่ออกจริง */
                foreach ($department['shifts'] ?? [] as $group => $bucket) {
                    $department['shifts'][$group]['ot_requested'] = $departmentRequests->filter(
                        fn (OtRequest $request) => OtShiftGroup::of($request->shift_code) === $group,
                    )->count();
                }
            }
            unset($department);
        }
        unset($company);

        return $summary;
    }

    /**
     * @param  array<string, mixed>  $scope
     * @return array<string, mixed>
     */
    public function departmentEmployees(
        CarbonInterface $date,
        string $company,
        string $deptCode,
        array $scope,
    ): array {
        $payload = $this->attendance->employees($date, $company, $deptCode, $scope);

        return $this->decorateEmployeePayload($payload, $date);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function decorateEmployeePayload(array $payload, CarbonInterface $date): array
    {
        $employees = collect($payload['employees'] ?? []);
        $requests = OtRequest::query()
            ->notCancelled()
            ->where('company', (string) ($payload['company'] ?? ''))
            ->whereDate('work_date', $date->format('Y-m-d'))
            ->whereIn('employee_code', $employees->pluck('code')->filter()->all())
            ->get()
            ->keyBy('employee_code');

        $payload['employees'] = $employees->map(function (array $employee) use ($requests) {
            /** @var OtRequest|null $request */
            $request = $requests->get($employee['code']);
            if ($request) {
                $this->refreshAttendance($request, $employee);
            }

            // จัดกลุ่มกะที่ backend ที่เดียว หน้าจอ 3 หน้าจะได้ไม่ต้องทำ mapping ซ้ำกันเอง
            $employee['shift_group'] = OtShiftGroup::of($employee['shift_code'] ?? null);
            $employee['request'] = $request ? $this->requestPayload($request) : null;
            $employee['status_key'] = $request
                ? $this->evaluator->displayStatusKey($request)
                : $this->evaluator->statusWithoutRequest($employee);
            $eligibility = OtEmployeeEligibility::evaluate($employee);
            $employee['ot_eligible'] = $eligibility['eligible'];
            $employee['ot_block_reason'] = $eligibility['reason'];
            /* Punch ใช้ตัดสินผลหลังทำ OT เท่านั้น ไม่ใช้บล็อกการสร้าง/แก้/ส่งคำขอ
               คำขอที่ส่งหรืออนุมัติไปแล้วก็ยังกดแก้ได้ เพราะหัวหน้าเปลี่ยนหน้างานกลางคันได้
               (ขอไว้ 2 ชม. แต่ให้ทำจริง 4 ชม.) การแก้จะเขียนทับแถวเดิมแล้วเข้าอนุมัติใหม่
               ไม่สร้างแถวซ้ำ เพราะ Bplus จะบวกชั่วโมงสะสมจนได้ 6 ชม. */
            $employee['can_request'] = $eligibility['eligible'];
            $employee['is_revision'] = (bool) ($request && $request->approval_status !== OtRequest::APPROVAL_DRAFT);
            $employee['was_exported'] = (bool) ($request && $request->exported_at !== null);
            /* ติ๊กเลือกไว้เพื่อ "ยกเลิก" ได้ตราบใดที่ Supervisor ยังไม่ตัดสิน
               (เดิมช่องติ๊กมีไว้ส่งร่าง แต่ตอนนี้กดขอแล้วส่งทันที ไม่มีร่างค้างอีก) */
            $employee['can_cancel'] = $request !== null && in_array(
                $request->approval_status,
                [OtRequest::APPROVAL_DRAFT, OtRequest::APPROVAL_SUBMITTED],
                true,
            );

            return $employee;
        })->values()->all();
        $payload['ot_types'] = $this->types();

        return $payload;
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $scope
     */
    public function saveDraft(array $data, AppUser $actor, array $scope): OtRequest
    {
        $company = $data['company'];
        $deptCode = trim((string) ($data['dept_code'] ?? ''));
        if (! $this->scopeAllowsDepartment($scope, $company, $deptCode)) {
            throw ValidationException::withMessages(['department' => 'คุณไม่มีสิทธิ์สร้างคำขอของแผนกนี้']);
        }

        $workDate = CarbonImmutable::createFromFormat('Y-m-d', $data['work_date'])->startOfDay();
        $attendancePayload = $this->attendance->employees($workDate, $company, $deptCode, $scope);
        $employee = collect($attendancePayload['employees'] ?? [])->firstWhere('code', $data['employee_code']);
        if (! $employee) {
            throw ValidationException::withMessages(['employee_code' => 'ไม่พบพนักงานในแผนกที่รับผิดชอบ']);
        }
        $this->assertOtEligible($employee);
        if (! PayrollCycle::canApprove($workDate)) {
            throw ValidationException::withMessages(['work_date' => 'คำขอนี้พ้นกำหนดอนุมัติของรอบเงินเดือนแล้ว']);
        }

        $type = (array) config('ot_approval.types.'.$data['ot_type']);
        $agreementCode = trim((string) ($type['v74_agreement_codes'][$company] ?? ''));
        if ($agreementCode === '') {
            throw ValidationException::withMessages([
                'ot_type' => 'ประเภท OT นี้ยังไม่ได้ตั้งค่าใน Bplus ของบริษัทที่เลือก',
            ]);
        }
        $period = $this->requestedPeriod(
            $workDate,
            $data['start_time'],
            $data['end_time'],
            $employee,
            $type,
        );
        $requestedStart = $period['start'];
        $requestedEnd = $period['end'];

        /* จำนวนที่ Foreman กรอกเองมาก่อนค่าที่คำนวณจากช่วงเวลา เพราะ OT ที่คาบเกี่ยว
           กะทั้งวันต้องหักเวลาพักออก เช่น 08:00-17:00 = 9 ชั่วโมง แต่ขอจริง 8
           ห้ามเกินช่วงเวลาที่เลือก กันกรอกเกินจริงแล้วหลุดไปถึงไฟล์ V74 */
        [$requestedHours, $requestedMinutes] = $this->requestedAmount($data, $period);

        return DB::connection('mysql_ot_approval')->transaction(function () use (
            $data,
            $actor,
            $company,
            $deptCode,
            $employee,
            $type,
            $agreementCode,
            $workDate,
            $requestedStart,
            $requestedEnd,
            $requestedHours,
            $requestedMinutes,
        ) {
            /* ข้ามคำขอที่ยกเลิกแล้ว ไม่งั้นจะไปเขียนทับแถวประวัติแทนที่จะสร้างใบใหม่ */
            $request = OtRequest::query()
                ->notCancelled()
                ->where('company', $company)
                ->where('employee_code', $data['employee_code'])
                ->whereDate('work_date', $workDate->format('Y-m-d'))
                ->lockForUpdate()
                ->first();

            /* ขอซ้ำวันเดิม = แก้คำขอเดิม ไม่สร้างแถวใหม่
               เพราะ Bplus บวกชั่วโมงสะสมเมื่อ import พนักงานซ้ำวันเดียวกัน
               สองแถว 2 ชม. + 4 ชม. จะกลายเป็น 6 ชม. ทั้งที่ความจริงคือ 4
               จึงเขียนทับของเดิมแล้วส่งเข้ากระบวนการอนุมัติใหม่ทั้งรอบ */
            $revision = null;
            if ($request && $request->approval_status !== OtRequest::APPROVAL_DRAFT) {
                $revision = [
                    'hours' => (int) $request->requested_hours,
                    'minutes' => (int) $request->requested_minutes,
                    'status' => (string) $request->approval_status,
                    'was_exported' => $request->exported_at !== null,
                ];

                // ล้างผลการตัดสินเดิม รายการนี้ต้องถูกอนุมัติใหม่ก่อนออก V74 อีกครั้ง
                $request->decided_by_app_user_id = null;
                $request->decided_by_employee_code = null;
                $request->decided_at = null;
                $request->decision_note = null;
                $request->export_status = OtRequest::EXPORT_NOT_READY;
                // ไม่ล้าง exported_at เพื่อให้หน้าดาวน์โหลดรู้ว่าวันนี้เคยส่งไฟล์ให้ HR ไปแล้ว
            }

            $request ??= new OtRequest;
            $request->fill([
                'company' => $company,
                'dept_code' => $deptCode,
                'employee_code' => $data['employee_code'],
                'employee_name' => $employee['name_th'] ?? $employee['name_en'] ?? $data['employee_code'],
                'position_name' => $employee['position_th'] ?? $employee['position_en'] ?? null,
                'department_name' => $employee['department_th'] ?? $employee['department_en'] ?? null,
                'work_date' => $workDate,
                'shift_code' => $employee['shift_code'] ?: null,
                'shift_name' => $employee['shift_name_th'] ?: ($employee['shift_name_en'] ?: null),
                'shift_in' => $employee['shift_in'],
                'shift_out' => $employee['shift_out'],
                'break_in' => $employee['break_in'],
                'break_out' => $employee['break_out'],
                'ot_type' => $data['ot_type'],
                'multiplier' => $type['multiplier'],
                'agreement_code' => $agreementCode,
                'requested_start_at' => $requestedStart,
                'requested_end_at' => $requestedEnd,
                'requested_hours' => $requestedHours,
                'requested_minutes' => $requestedMinutes,
                'is_special' => false,
                'note' => filled($data['note'] ?? null) ? trim((string) $data['note']) : null,
                'approval_status' => OtRequest::APPROVAL_DRAFT,
                'created_by_app_user_id' => $request->exists ? $request->created_by_app_user_id : $actor->id,
                'created_by_employee_code' => $request->exists ? $request->created_by_employee_code : $actor->employee_code,
            ]);
            $request->attendance_status = $this->evaluator->evaluate($request, $employee);
            $request->last_clock_in = $employee['clock_in'];
            $request->last_clock_out = $employee['clock_out'];
            $request->attendance_source = $employee['attendance_source'] ?? null;
            $request->attendance_checked_at = now();
            $request->save();

            /* เก็บ Audit ว่าถูกแก้จากเท่าไรเป็นเท่าไร ใครแก้ และของเดิมเคยส่งไฟล์ไปแล้วหรือยัง
               จำเป็นเวลาตรวจย้อนหลังว่าทำไมชั่วโมงใน Bplus ไม่ตรงกับที่อนุมัติรอบแรก */
            if ($revision !== null) {
                OtRequestApproval::create([
                    'ot_request_id' => $request->id,
                    'decision' => 'revised',
                    'actor_app_user_id' => $actor->id,
                    'actor_employee_code' => $actor->employee_code,
                    'note' => sprintf(
                        'แก้จาก %d ชม. %d นาที เป็น %d ชม. %d นาที (สถานะเดิม: %s)%s',
                        $revision['hours'],
                        $revision['minutes'],
                        (int) $request->requested_hours,
                        (int) $request->requested_minutes,
                        $revision['status'],
                        $revision['was_exported'] ? ' · เคยดาวน์โหลดส่ง HR แล้ว ต้องโหลดไฟล์ใหม่ทับ' : '',
                    ),
                ]);
            }

            return $request->refresh();
        });
    }

    /**
     * @param  int[]  $ids
     * @param  array<string, mixed>  $scope
     * @return array<int, array<string, mixed>>
     */
    public function submitDrafts(array $ids, AppUser $actor, array $scope): array
    {
        $preflightRequests = OtRequest::query()->whereIn('id', $ids)->get();
        if ($preflightRequests->count() !== count(array_unique($ids))) {
            throw ValidationException::withMessages(['request_ids' => 'พบคำขอบางรายการไม่ถูกต้อง']);
        }
        foreach ($preflightRequests as $request) {
            if (! $this->scopeAllowsDepartment($scope, $request->company, (string) $request->dept_code)) {
                throw ValidationException::withMessages(['request_ids' => 'คุณไม่มีสิทธิ์ส่งคำขอบางรายการ']);
            }
            if (! PayrollCycle::canApprove($request->work_date)) {
                throw ValidationException::withMessages(['request_ids' => 'คำขอบางรายการพ้นกำหนดอนุมัติของรอบเงินเดือนแล้ว']);
            }
        }
        $preflightRequests->groupBy(fn (OtRequest $request) => implode('|', [
            $request->work_date->format('Y-m-d'), $request->company, trim((string) $request->dept_code),
        ]))->each(function ($group) use ($scope) {
            /** @var OtRequest $sample */
            $sample = $group->first();
            $payload = $this->attendance->employees(
                $sample->work_date,
                $sample->company,
                trim((string) $sample->dept_code),
                $scope,
            );
            $attendanceMap = collect($payload['employees'] ?? [])->keyBy('code');
            foreach ($group as $request) {
                $attendance = $attendanceMap->get($request->employee_code);
                if ($attendance) {
                    $this->assertOtEligible($attendance, 'request_ids');
                    $this->refreshAttendance($request, $attendance);
                }
            }
        });

        return DB::connection('mysql_ot_approval')->transaction(function () use ($ids, $actor, $scope) {
            $requests = OtRequest::query()->whereIn('id', $ids)->lockForUpdate()->get();
            if ($requests->count() !== count(array_unique($ids))) {
                throw ValidationException::withMessages(['request_ids' => 'พบคำขอบางรายการไม่ถูกต้อง']);
            }

            foreach ($requests as $request) {
                if (! $this->scopeAllowsDepartment($scope, $request->company, (string) $request->dept_code)) {
                    throw ValidationException::withMessages(['request_ids' => 'คุณไม่มีสิทธิ์ส่งคำขอบางรายการ']);
                }
                if ($request->approval_status !== OtRequest::APPROVAL_DRAFT) {
                    throw ValidationException::withMessages(['request_ids' => 'คำขอบางรายการถูกส่งไปแล้ว']);
                }
                if (! PayrollCycle::canApprove($request->work_date)) {
                    throw ValidationException::withMessages(['request_ids' => 'คำขอบางรายการพ้นกำหนดอนุมัติของรอบเงินเดือนแล้ว']);
                }
                $request->approval_status = OtRequest::APPROVAL_SUBMITTED;
                $request->submitted_at = now();
                $request->save();
                OtRequestApproval::create([
                    'ot_request_id' => $request->id,
                    'decision' => 'submitted',
                    'actor_app_user_id' => $actor->id,
                    'actor_employee_code' => $actor->employee_code,
                ]);
            }

            return $requests->map(fn (OtRequest $request) => $this->requestPayload($request->refresh()))->all();
        });
    }

    /**
     * ยกเลิกคำขอที่ยังไม่มีการตัดสิน — เก็บแถวไว้เป็นประวัติ ไม่ลบทิ้ง
     *
     * เดิมถอยได้เฉพาะร่างและใช้วิธี `delete()` จริง ๆ ซึ่งตาราง audit ตั้ง
     * `cascadeOnDelete()` ไว้ ประวัติจึงหายไปทั้งก้อนพร้อมเหตุผลที่กรอก
     * ตรวจย้อนไม่ได้ว่าเคยส่งคำขอนั้นให้ Supervisor ทั้งที่อีเมลออกไปแล้ว
     *
     * ยกเลิกได้เฉพาะสถานะ draft/submitted — ที่อนุมัติหรือปฏิเสธไปแล้วถือว่าจบเรื่อง
     * ต้องให้ Supervisor เป็นคนแก้ ไม่ใช่ Foreman ถอยเอง
     *
     * @param  int[]  $ids
     * @param  array<string, mixed>  $scope
     */
    public function cancelRequests(array $ids, string $reason, AppUser $actor, array $scope): int
    {
        $reason = trim($reason);
        if ($reason === '') {
            throw ValidationException::withMessages(['reason' => 'กรุณาระบุเหตุผลที่ยกเลิก']);
        }

        return DB::connection('mysql_ot_approval')->transaction(function () use ($ids, $reason, $actor, $scope) {
            $requests = OtRequest::query()->whereIn('id', $ids)->lockForUpdate()->get();
            if ($requests->count() !== count(array_unique(array_map('intval', $ids)))) {
                throw ValidationException::withMessages(['request_ids' => 'มีคำขอบางรายการไม่อยู่ในระบบ']);
            }

            foreach ($requests as $request) {
                if (! $this->scopeAllowsDepartment($scope, $request->company, (string) $request->dept_code)) {
                    throw ValidationException::withMessages(['request_ids' => 'มีคำขออยู่นอกแผนกที่รับผิดชอบ']);
                }
                if (! in_array($request->approval_status, [OtRequest::APPROVAL_DRAFT, OtRequest::APPROVAL_SUBMITTED], true)) {
                    throw ValidationException::withMessages(['request_ids' => 'ยกเลิกได้เฉพาะคำขอที่ยังไม่ถูกตัดสิน']);
                }

                $request->approval_status = OtRequest::APPROVAL_CANCELLED;
                $request->cancelled_at = now();
                $request->cancel_reason = $reason;
                $request->cancelled_by_app_user_id = $actor->id;
                $request->cancelled_by_employee_code = $actor->employee_code;
                /* ต้องปิดทางส่งออกด้วย ไม่งั้นคำขอที่ยกเลิกแล้วยังหลุดเข้าไฟล์ V74 ได้
                   ถ้าเคยผ่านเกณฑ์เวลาจนถูกตั้งเป็น ready ไว้ก่อนหน้า */
                $request->export_status = OtRequest::EXPORT_NOT_READY;
                /* คืนช่อง unique ให้ขอใหม่วันเดิมได้ — ดู migration 2026_08_19_010000
                   ถ้าไม่ปล่อยเป็น null แถวที่ยกเลิกจะยังกินช่องจนขอใหม่ชน 1062 */
                $request->active_slot = null;
                $request->save();

                OtRequestApproval::create([
                    'ot_request_id' => $request->id,
                    'decision' => 'cancelled',
                    'note' => $reason,
                    'actor_app_user_id' => $actor->id,
                    'actor_employee_code' => $actor->employee_code,
                ]);
            }

            return $requests->count();
        });
    }

    /**
     * @param  array<string, mixed>  $scope
     * @param  CarbonImmutable|null  $workDate  วันที่ทำ OT ที่ต้องการดู — null คือทุกวัน
     * @return array<int, array<string, mixed>>
     */
    public function approvalQueue(array $scope, string $filter, ?CarbonImmutable $workDate = null): array
    {
        return $this->approvalQueuePage($scope, $filter, $workDate, 1, 0)['requests'];
    }

    /**
     * คิวอนุมัติแบบแบ่งหน้าที่ฐานข้อมูล
     *
     * ของเดิมดึงมาทีเดียวแล้ว LIMIT 300 ตายตัว พอคำขอเยอะกว่านั้นรายการจะหายเงียบ ๆ
     * และเบราว์เซอร์ต้องวาดทุกแถวจนค้าง จึงย้ายมาแบ่งหน้าที่ SQL
     * ส่ง $perPage = 0 เพื่อเอาทั้งหมด (ใช้ในเทสต์และงานที่ต้องการชุดเต็ม)
     *
     * @param  array<string, mixed>  $scope
     * @param  string  $shift  ค่าเดียวกับตัวกรองกะบนหน้าจอ: all | none | g:<group> | s:<code>
     * @return array{requests:array<int, array<string, mixed>>, total:int, page:int, per_page:int, last_page:int, shifts:array<int, array<string, mixed>>}
     */
    public function approvalQueuePage(
        array $scope,
        string $filter,
        ?CarbonImmutable $workDate,
        int $page,
        int $perPage,
        string $shift = 'all',
        string $search = '',
        string $branch = OtBranchFilter::ALL,
        string $otType = 'all',
    ): array {
        $query = OtRequest::query()->where('approval_status', '!=', OtRequest::APPROVAL_DRAFT);
        $this->applyScope($query, $scope);
        $this->applyEmployeeSearch($query, $search);
        // คิวแบ่งหน้าที่ SQL จึงต้องกรองสาขาที่เซิร์ฟเวอร์ ไม่งั้นจะกรองได้แค่หน้าที่เปิดอยู่
        OtBranchFilter::apply($query, $branch);
        // ประเภท OT ก็ต้องกรองที่เซิร์ฟเวอร์ด้วยเหตุผลเดียวกัน (กรองในเครื่องจะได้แค่หน้าที่เปิดอยู่)
        $otType = trim($otType);
        if ($otType !== '' && $otType !== 'all') {
            $query->where('ot_type', $otType);
        }

        /* `รอดำเนินการ` ตัดรายการที่เวลาสแกนไม่ผ่านออก แล้วรวมไปที่ตัวกรอง `ไม่อนุมัติ`
           เพราะอนุมัติไปก็ออก V74 ไม่ได้จนกว่า HR จะแก้เวลาใน Bplus ให้ก่อน
           ถ้าปล่อยปนกัน คิวรออนุมัติจะเต็มไปด้วยรายการที่กดยังไงก็ไม่จบ */
        match ($filter) {
            'pending' => $query->where('approval_status', OtRequest::APPROVAL_SUBMITTED)
                ->where(function (Builder $where) {
                    $where->where('attendance_status', '!=', OtRequest::ATTENDANCE_FAILED)
                        ->orWhereNull('attendance_status');
                }),
            // ถูกปฏิเสธอัตโนมัติไปแล้ว แต่ยังต้องเห็นรวมกันเพื่อให้ HR ตามแก้เวลาใน Bplus ต่อ
            'approved' => $query->where('approval_status', OtRequest::APPROVAL_APPROVED),
            'rejected' => $query->where(function (Builder $where) {
                $where->where('approval_status', OtRequest::APPROVAL_REJECTED)
                    ->orWhere(function (Builder $failed) {
                        $failed->where('approval_status', '!=', OtRequest::APPROVAL_CANCELLED)
                            ->where('attendance_status', OtRequest::ATTENDANCE_FAILED);
                    });
            }),
            'cancelled' => $query->where('approval_status', OtRequest::APPROVAL_CANCELLED),
            default => null,
        };

        // กรองตามวันที่ทำ OT ให้ Supervisor แบ่งงานเป็นวัน ๆ ได้ ไม่ต้องเลื่อนดูรวมทุกวัน
        if ($workDate) {
            $query->whereDate('work_date', $workDate->format('Y-m-d'));
        }

        /* ตัวเลือกกะต้องนับจากคิวทั้งหมดก่อนแบ่งหน้า ไม่งั้นพอเปิดหน้า 2 ตัวเลือกจะหาย
           ใช้ GROUP BY ที่ฐานข้อมูล ไม่ต้องดึงแถวจริงมานับใน PHP */
        $shifts = (clone $query)
            ->selectRaw('shift_code, shift_in, shift_out, COUNT(*) as total')
            ->groupBy('shift_code', 'shift_in', 'shift_out')
            ->get()
            ->map(fn ($row) => [
                'code' => trim((string) $row->shift_code),
                'group' => OtShiftGroup::of($row->shift_code),
                'shift_in' => $row->shift_in ? substr((string) $row->shift_in, 0, 5) : null,
                'shift_out' => $row->shift_out ? substr((string) $row->shift_out, 0, 5) : null,
                'total' => (int) $row->total,
            ])
            ->sortByDesc('total')
            ->values()
            ->all();

        $this->applyShiftFilter($query, $shift, $shifts);

        $total = (clone $query)->count();
        $perPage = max(0, $perPage);
        $lastPage = $perPage > 0 ? max(1, (int) ceil($total / $perPage)) : 1;
        $page = $perPage > 0 ? min(max(1, $page), $lastPage) : 1;

        $query->orderByDesc('work_date')->orderByDesc('submitted_at');
        if ($perPage > 0) {
            $query->forPage($page, $perPage);
        }

        $requests = $query->get();
        $requests->groupBy(fn (OtRequest $request) => implode('|', [
            $request->work_date->format('Y-m-d'), $request->company, trim((string) $request->dept_code),
        ]))->each(function ($group) use ($scope) {
            /** @var OtRequest $sample */
            $sample = $group->first();
            $payload = $this->attendance->employees(
                $sample->work_date,
                $sample->company,
                trim((string) $sample->dept_code),
                $scope,
            );
            $attendanceMap = collect($payload['employees'] ?? [])->keyBy('code');
            foreach ($group as $request) {
                $attendance = $attendanceMap->get($request->employee_code);
                if ($attendance) {
                    $this->refreshAttendance($request, $attendance);
                }
            }
        });

        $employeeRows = collect();
        foreach ($requests->groupBy('company') as $company => $companyRequests) {
            $employeeRows = $employeeRows->concat(
                Employee::active()
                    ->where('company', $company)
                    ->whereIn('employee_code', $companyRequests->pluck('employee_code')->unique()->all())
                    ->get(['company', 'employee_code', 'license_id', 'title', 'name_th', 'surname_th', 'name_en']),
            );
        }
        $identities = $employeeRows->pluck('license_id')->filter()->unique()->values();
        $users = $identities->isEmpty()
            ? collect()
            : AppUser::query()->whereIn('id_thai_hash', $identities->all())->get(['id_thai_hash', 'profile_picture'])->keyBy('id_thai_hash');
        $employeePresentations = $employeeRows->mapWithKeys(function (Employee $employee) use ($users) {
            $picture = $employee->license_id ? $users->get($employee->license_id)?->profile_picture : null;
            $nameTh = $employee->fullNameTh() ?: ($employee->fullNameEn() ?: $employee->employee_code);
            $nameEn = $employee->fullNameEn() ?: $nameTh;

            return [$employee->company.'|'.$employee->employee_code => [
                'avatar' => $picture ? asset('storage/'.$picture) : null,
                'employee_name_th' => $nameTh,
                'employee_name_en' => $nameEn,
                'employee_name_my' => $nameEn,
            ]];
        });

        return [
            'requests' => $requests->map(function (OtRequest $request) use ($employeePresentations) {
                $payload = $this->requestPayload($request);

                return array_merge($payload, $employeePresentations->get(
                    $request->company.'|'.$request->employee_code,
                    ['avatar' => null],
                ));
            })->values()->all(),
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'last_page' => $lastPage,
            'shifts' => $shifts,
        ];
    }

    /**
     * กรองตามกะด้วยค่าเดียวกับที่หน้าจอส่งมา
     * `g:<group>` ต้องแปลงเป็นรายการรหัสกะก่อน เพราะการจับกลุ่มเป็นตรรกะใน PHP ไม่ใช่คอลัมน์ใน DB
     *
     * @param  array<int, array<string, mixed>>  $shifts
     */
    private function applyShiftFilter(Builder $query, string $shift, array $shifts): void
    {
        $value = trim($shift);
        if ($value === '' || $value === 'all') {
            return;
        }

        if ($value === 'none') {
            $query->where(function (Builder $where) {
                $where->whereNull('shift_code')->orWhere('shift_code', '');
            });

            return;
        }

        if (str_starts_with($value, 's:')) {
            $query->where('shift_code', substr($value, 2));

            return;
        }

        if (str_starts_with($value, 'g:')) {
            $group = substr($value, 2);
            $codes = collect($shifts)
                ->filter(fn (array $row) => $row['group'] === $group && $row['code'] !== '')
                ->pluck('code')
                ->all();

            // ไม่มีรหัสกะไหนอยู่ในกลุ่มนี้ = ต้องได้ผลลัพธ์ว่าง ไม่ใช่คืนทั้งหมด
            $query->whereIn('shift_code', $codes ?: ['__none__']);
        }
    }

    /** @param array<string, mixed> $scope */
    public function decide(
        OtRequest $request,
        AppUser $actor,
        array $scope,
        string $decision,
        ?string $note,
    ): OtRequest {
        if (! $this->scopeAllowsDepartment($scope, $request->company, (string) $request->dept_code)) {
            throw ValidationException::withMessages(['request' => 'คุณไม่มีสิทธิ์อนุมัติคำขอนี้']);
        }
        if (! PayrollCycle::canApprove($request->work_date)) {
            throw ValidationException::withMessages(['request' => 'คำขอนี้พ้นกำหนดอนุมัติของรอบเงินเดือนแล้ว']);
        }

        $attendance = $this->attendanceForRequest($request, $scope);
        if ($attendance) {
            $this->refreshAttendance($request, $attendance);
        }

        // เช็กหลังรีเฟรช เพราะเวลาสแกนอาจเพิ่งตกกฎแล้วระบบปฏิเสธให้เองไปแล้ว
        if ($decision === OtRequest::APPROVAL_APPROVED && $request->attendance_status === OtRequest::ATTENDANCE_FAILED) {
            throw ValidationException::withMessages([
                'request' => 'เวลาสแกนไม่ครอบคลุมช่วง OT ระบบปฏิเสธคำขอนี้อัตโนมัติแล้ว',
            ]);
        }

        return DB::connection('mysql_ot_approval')->transaction(function () use ($request, $actor, $decision, $note) {
            /** @var OtRequest $locked */
            $locked = OtRequest::query()->whereKey($request->id)->lockForUpdate()->firstOrFail();
            $isApprovedToRejected = $locked->approval_status === OtRequest::APPROVAL_APPROVED
                && $decision === OtRequest::APPROVAL_REJECTED;
            $isRejectedToApproved = $locked->approval_status === OtRequest::APPROVAL_REJECTED
                && $decision === OtRequest::APPROVAL_APPROVED;
            if ($locked->approval_status !== OtRequest::APPROVAL_SUBMITTED && ! $isApprovedToRejected && ! $isRejectedToApproved) {
                throw ValidationException::withMessages(['request' => 'รายการนี้ถูกดำเนินการแล้ว']);
            }
            if (! PayrollCycle::canApprove($locked->work_date)) {
                throw ValidationException::withMessages(['request' => 'คำขอนี้พ้นกำหนดอนุมัติของรอบเงินเดือนแล้ว']);
            }

            $locked->approval_status = $decision;
            $locked->decided_by_app_user_id = $actor->id;
            $locked->decided_by_employee_code = $actor->employee_code;
            $locked->decision_note = filled($note) ? trim((string) $note) : null;
            $locked->decided_at = now();
            $locked->export_status = $decision === OtRequest::APPROVAL_APPROVED
                && $locked->attendance_status === OtRequest::ATTENDANCE_PASSED
                    ? OtRequest::EXPORT_READY
                    : OtRequest::EXPORT_NOT_READY;
            $locked->save();

            OtRequestApproval::create([
                'ot_request_id' => $locked->id,
                'decision' => $decision,
                'actor_app_user_id' => $actor->id,
                'actor_employee_code' => $actor->employee_code,
                'note' => filled($note) ? trim((string) $note) : null,
            ]);

            return $locked->refresh();
        });
    }

    /**
     * ตัดสินหลายคำขอรวดเดียว (อนุมัติหรือปฏิเสธรายการที่เลือก)
     *
     * ตั้งใจวนเรียก decide() ทีละรายการแทนที่จะเขียน mass update ใหม่
     * เพื่อให้ล็อกแถว ตรวจสิทธิ์แผนก รีเฟรชเวลาสแกน และบันทึกประวัติ
     * เหมือนการอนุมัติทีละรายการเป๊ะ ๆ ปริมาณต่อครั้งไม่กี่สิบแถวจึงไม่หนัก
     *
     * รายการที่ทำไม่ได้ (คนอื่นตัดสินไปแล้ว / นอกแผนกที่รับผิดชอบ) จะถูกข้าม
     * และรายงานกลับ ไม่ทำให้ทั้งล็อตล้ม
     *
     * @param  array<int, int|string>  $ids
     * @return array{updated: array<int, OtRequest>, skipped: array<int, array{id: int, reason: string}>}
     */
    public function decideMany(array $ids, AppUser $actor, array $scope, string $decision, ?string $note): array
    {
        $updated = [];
        $skipped = [];

        $requests = OtRequest::query()
            ->whereIn('id', $ids)
            ->orderBy('work_date')
            ->orderBy('employee_code')
            ->get()
            ->keyBy('id');

        foreach ($ids as $id) {
            $id = (int) $id;
            $request = $requests->get($id);

            if (! $request) {
                $skipped[] = ['id' => $id, 'reason' => 'ไม่พบคำขอนี้'];

                continue;
            }

            try {
                $updated[] = $this->decide($request, $actor, $scope, $decision, $note);
            } catch (ValidationException $exception) {
                $skipped[] = [
                    'id' => $id,
                    'reason' => collect($exception->errors())->flatten()->first() ?: 'ดำเนินการไม่สำเร็จ',
                ];
            }
        }

        return ['updated' => $updated, 'skipped' => $skipped];
    }

    /** @return array<string, mixed> */
    public function requestPayload(OtRequest $request): array
    {
        return [
            'id' => $request->id,
            'company' => $request->company,
            'dept_code' => (string) $request->dept_code,
            'employee_code' => $request->employee_code,
            'employee_name' => $request->employee_name,
            'employee_name_th' => $request->employee_name,
            'employee_name_en' => $request->employee_name,
            'employee_name_my' => $request->employee_name,
            'position_name' => $request->position_name,
            'department_name' => $request->department_name,
            'work_date' => $request->work_date->format('Y-m-d'),
            'shift_code' => $request->shift_code,
            'shift_group' => OtShiftGroup::of($request->shift_code),
            'shift_name' => $request->shift_name,
            'shift_in' => $this->timeOnly($request->shift_in),
            'shift_out' => $this->timeOnly($request->shift_out),
            'ot_type' => $request->ot_type,
            'ot_type_label_th' => $request->otTypeLabel('th'),
            'ot_type_label_en' => $request->otTypeLabel('en'),
            'ot_type_label_my' => $request->otTypeLabel('my'),
            'multiplier' => (string) $request->multiplier,
            'requested_start' => $request->requested_start_at->format('H:i'),
            'requested_end' => $request->requested_end_at->format('H:i'),
            'requested_hours' => $request->requested_hours,
            'requested_minutes' => $request->requested_minutes,
            'requested_total_minutes' => ($request->requested_hours * 60) + $request->requested_minutes,
            // แยกหมายเหตุจาก Foreman และ Supervisor ให้ UI ไม่สับสนกัน
            'request_note' => $request->note,
            'decision_note' => $request->decision_note,
            'cancel_reason' => $request->cancel_reason,
            'note' => $request->note,
            'approval_status' => $request->approval_status,
            'attendance_status' => $request->attendance_status,
            'export_status' => $request->export_status,
            'status_key' => $this->evaluator->displayStatusKey($request),
            'clock_in' => $this->timeOnly($request->last_clock_in),
            'clock_out' => $this->timeOnly($request->last_clock_out),
            'created_at' => $request->created_at?->toIso8601String(),
            'submitted_at' => $request->submitted_at?->toIso8601String(),
            'decided_at' => $request->decided_at?->toIso8601String(),
            'cancelled_at' => $request->cancelled_at?->toIso8601String(),
            'attendance_checked_at' => $request->attendance_checked_at?->toIso8601String(),
            // ผู้ดำเนินการ — แสดงคนกรอกฟอร์ม ไม่ใช่คนกดส่ง เพราะเป็นคนรับผิดชอบเนื้อหาคำขอ
            'created_by_code' => $request->created_by_employee_code,
            'created_by_name' => $this->actorName($request->created_by_employee_code),
            'decided_by_code' => $request->decided_by_employee_code,
            'decided_by_name' => $this->actorName($request->decided_by_employee_code),
            'cancelled_by_code' => $request->cancelled_by_employee_code,
            'cancelled_by_name' => $this->actorName($request->cancelled_by_employee_code),
            'can_edit' => $request->approval_status === OtRequest::APPROVAL_DRAFT,
            'can_select' => $request->approval_status === OtRequest::APPROVAL_DRAFT,
            'can_decide' => $request->approval_status === OtRequest::APPROVAL_SUBMITTED
                && PayrollCycle::canApprove($request->work_date),
        ];
    }

    /** @param array<string, mixed> $attendance */
    /**
     * ชื่อผู้ขอ/ผู้อนุมัติจากรหัสพนักงาน
     *
     * จำไว้ต่อรหัสภายในรีเควสต์เดียว เพราะ approvalQueue วนเรียก requestPayload
     * เป็นร้อยแถวและมักเป็นคนเดิมซ้ำ ๆ ถ้าไม่จำจะยิง query เท่าจำนวนแถว
     */
    private function actorName(?string $employeeCode): string
    {
        $code = trim((string) $employeeCode);
        if ($code === '') {
            return '';
        }

        if (! array_key_exists($code, $this->actorNameCache)) {
            $user = AppUser::query()->where('employee_code', $code)->first(['full_name_th', 'full_name_en']);
            $this->actorNameCache[$code] = $user?->fullNameTh() ?: ($user?->full_name_en ?: $code);
        }

        return $this->actorNameCache[$code];
    }

    private function refreshAttendance(OtRequest $request, array $attendance): void
    {
        // ต้องอัปเดตกะก่อนประเมิน ไม่งั้นจะตัดสินเวลาสแกนด้วยกะเก่าที่ HR แก้ไปแล้ว
        $this->syncShiftFromBplus($request, $attendance);

        $request->attendance_status = $this->evaluator->evaluate($request, $attendance);
        $request->last_clock_in = $attendance['clock_in'] ?? null;
        $request->last_clock_out = $attendance['clock_out'] ?? null;
        $request->attendance_source = $attendance['attendance_source'] ?? null;
        $request->attendance_checked_at = now();

        /* เวลาสแกนตกกฎแล้วยังไงก็ออก V74 ไม่ได้ ปฏิเสธให้อัตโนมัติเลย
           ไม่ต้องรอ Supervisor มากดซ้ำในสิ่งที่ระบบตัดสินได้เอง */
        if (
            $request->approval_status === OtRequest::APPROVAL_SUBMITTED
            && $request->attendance_status === OtRequest::ATTENDANCE_FAILED
        ) {
            $request->approval_status = OtRequest::APPROVAL_REJECTED;
            $request->decision_note = self::AUTO_REJECT_NOTE;
            $request->decided_at = now();
            $request->decided_by_app_user_id = null;
            $request->decided_by_employee_code = null;
        }

        if ($request->export_status !== OtRequest::EXPORT_EXPORTED) {
            $request->export_status = $request->approval_status === OtRequest::APPROVAL_APPROVED
                && $request->attendance_status === OtRequest::ATTENDANCE_PASSED
                    ? OtRequest::EXPORT_READY
                    : OtRequest::EXPORT_NOT_READY;
        }
        $request->save();

        if ($request->wasChanged('approval_status') && $request->approval_status === OtRequest::APPROVAL_REJECTED) {
            OtRequestApproval::create([
                'ot_request_id' => $request->id,
                'decision' => OtRequest::APPROVAL_REJECTED,
                'actor_app_user_id' => null,
                'actor_employee_code' => null,
                'note' => self::AUTO_REJECT_NOTE,
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $scope
     *
     * จำผลไว้ต่อ วันที่|บริษัท|แผนก ภายในรีเควสต์เดียว เพราะตอนอนุมัติทีละหลายรายการ
     * ทุกแถวมักเป็นแผนกและวันเดียวกัน ถ้าไม่จำจะยิงถาม Bplus ซ้ำเท่าจำนวนแถว
     */
    /**
     * ดึงกะล่าสุดจาก Bplus มาทับในคำขอ เมื่อ HR แก้กะหลังจากยื่นไปแล้ว
     *
     * ตอนกดบันทึกคำขอ ระบบก๊อปกะของวันนั้นมาแช่ไว้ในตัวคำขอ และตัวประเมินเวลาสแกน
     * ใช้กะที่แช่ไว้นี้ ไม่ได้ถาม Bplus ใหม่ ถ้า HR แก้กะทีหลัง (เช่น แก้ข้อมูลที่ลงผิด)
     * คำขอจะถูกตัดสินด้วยกะเก่าซึ่งอาจให้ผลผิด
     *
     * **คำขอที่อนุมัติแล้วไม่แตะ** — ต้องตรวจสอบย้อนหลังได้ว่า Supervisor อนุมัติ
     * บนพื้นฐานกะอะไร ถ้าให้เปลี่ยนตามทีหลังจะกลายเป็นแก้ประวัติการอนุมัติ
     *
     * @param  array<string, mixed>  $attendance
     */
    private function syncShiftFromBplus(OtRequest $request, array $attendance): void
    {
        if ($request->approval_status === OtRequest::APPROVAL_APPROVED) {
            return;
        }

        $shiftIn = $attendance['shift_in'] ?? null;
        $shiftOut = $attendance['shift_out'] ?? null;

        // Bplus ไม่มีข้อมูลกะของวันนั้น (วันหยุด/ยังไม่จัดกะ) อย่าไปล้างของเดิมทิ้ง
        if ($shiftIn === null || $shiftOut === null) {
            return;
        }

        if (
            $this->sameClockTime($request->shift_in, $shiftIn)
            && $this->sameClockTime($request->shift_out, $shiftOut)
        ) {
            return;
        }

        $request->shift_code = ($attendance['shift_code'] ?? '') ?: null;
        $request->shift_name = ($attendance['shift_name_th'] ?? '') ?: (($attendance['shift_name_en'] ?? '') ?: null);
        $request->shift_in = $shiftIn;
        $request->shift_out = $shiftOut;
        $request->break_in = $attendance['break_in'] ?? null;
        $request->break_out = $attendance['break_out'] ?? null;
    }

    /** เทียบเวลาแบบไม่สนวินาที เพราะฐานเก็บ 20:00:00 แต่ Bplus ส่ง 20:00 มา */
    private function sameClockTime(mixed $stored, mixed $incoming): bool
    {
        $normalize = static fn (mixed $value) => $value === null
            ? null
            : substr((string) $value, 0, 5);

        return $normalize($stored) === $normalize($incoming);
    }

    private function attendanceForRequest(OtRequest $request, array $scope): ?array
    {
        $deptCode = trim((string) $request->dept_code);
        $key = $request->work_date->format('Y-m-d').'|'.$request->company.'|'.$deptCode;

        if (! array_key_exists($key, $this->attendanceCache)) {
            $payload = $this->attendance->employees($request->work_date, $request->company, $deptCode, $scope);
            $this->attendanceCache[$key] = collect($payload['employees'] ?? [])->keyBy('code');
        }

        return $this->attendanceCache[$key]->get($request->employee_code);
    }

    /** @param array<string, mixed> $type */
    private function requestedPeriod(
        CarbonImmutable $workDate,
        string $startTime,
        string $endTime,
        array $employee,
        array $type,
    ): array {
        $period = $this->timeRange->calculate($workDate, $startTime, $endTime);
        $requestedStart = $period['start'];
        $requestedEnd = $period['end'];
        $shiftIn = $this->timeOnly($employee['shift_in'] ?? null);
        $shiftOut = $this->timeOnly($employee['shift_out'] ?? null);
        if (($type['timing'] ?? 'manual') === 'after_shift') {
            if ($shiftIn === null || $shiftOut === null) {
                throw ValidationException::withMessages(['start_time' => 'ไม่พบเวลาเลิกกะจาก Bplus']);
            }
            $shiftStart = CarbonImmutable::parse($workDate->toDateString().' '.$shiftIn);
            $shiftEnd = CarbonImmutable::parse($workDate->toDateString().' '.$shiftOut);
            if ($shiftEnd->lessThanOrEqualTo($shiftStart)) {
                $shiftEnd = $shiftEnd->addDay();
            }
            if ($shiftEnd->toDateString() !== $workDate->toDateString() && $requestedStart->lt($shiftEnd)) {
                $requestedStart = $requestedStart->addDay();
                $requestedEnd = $requestedEnd->addDay();
            }
            $earliest = $shiftEnd->addMinutes((int) config('ot_approval.post_shift_break_minutes', 60));
            if ($requestedStart->lt($earliest)) {
                throw ValidationException::withMessages([
                    'start_time' => 'เวลาเริ่ม OT ต้องหลังเวลาเลิกกะและพัก 1 ชั่วโมง ('.$earliest->format('H:i').')',
                ]);
            }
        }

        if (($type['timing'] ?? 'manual') === 'before_shift' && $shiftIn !== null) {
            $shiftStart = CarbonImmutable::parse($workDate->toDateString().' '.$shiftIn);
            if ($requestedEnd->gt($shiftStart)) {
                throw ValidationException::withMessages(['end_time' => 'OT ก่อนเข้างานต้องสิ้นสุดไม่เกินเวลาเริ่มกะ']);
            }
        }

        return array_merge($period, [
            'start' => $requestedStart,
            'end' => $requestedEnd,
        ]);
    }

    /**
     * จำนวนชั่วโมง/นาทีที่ขอจริง — ใช้ค่าที่กรอกมาถ้ามี ไม่งั้นคิดเต็มช่วงเวลา
     *
     * @param  array<string, mixed>  $data
     * @param  array{total_minutes: int, hours: int, minutes: int}  $period
     * @return array{0: int, 1: int}
     */
    private function requestedAmount(array $data, array $period): array
    {
        $hours = $data['requested_hours'] ?? null;
        $minutes = $data['requested_minutes'] ?? null;
        if ($hours === null && $minutes === null) {
            return [$period['hours'], $period['minutes']];
        }

        $total = ((int) $hours * 60) + (int) $minutes;
        if ($total <= 0) {
            throw ValidationException::withMessages([
                'requested_hours' => 'กรุณากรอกจำนวนชั่วโมงที่ขอ OT',
            ]);
        }

        if ($total > (int) $period['total_minutes']) {
            throw ValidationException::withMessages([
                'requested_hours' => 'จำนวนที่ขอต้องไม่เกินช่วงเวลาที่เลือก ('
                    .intdiv((int) $period['total_minutes'], 60).' ชั่วโมง '
                    .((int) $period['total_minutes'] % 60).' นาที)',
            ]);
        }

        return [intdiv($total, 60), $total % 60];
    }

    /** @param array<string, mixed> $scope */
    private function scopeAllowsDepartment(array $scope, string $company, string $deptCode): bool
    {
        if ($scope['all'] ?? false) {
            return true;
        }

        return collect($scope['departments'] ?? [])->contains(
            fn (array $department) => $department['company'] === $company
                && trim((string) $department['dept_code']) === trim($deptCode),
        );
    }

    /**
     * ค้นหาด้วยรหัสพนักงานหรือชื่อ-สกุล
     *
     * คิวอนุมัติแบ่งหน้าที่เซิร์ฟเวอร์ ถ้ากรองในเครื่องจะกรองได้แค่หน้าที่เปิดอยู่
     * ชื่อถูกก๊อปมาเก็บใน `employee_name` ตอนสร้างคำขอ จึงค้นจากตารางเดียวได้ ไม่ต้อง join
     */
    private function applyEmployeeSearch(Builder $query, string $search): void
    {
        $needle = trim($search);
        if ($needle === '') {
            return;
        }

        // escape % และ _ ไม่งั้นผู้ใช้พิมพ์ _ แล้วกลายเป็น wildcard จับได้ทุกตัวอักษร
        $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $needle).'%';

        $query->where(function (Builder $where) use ($like) {
            $where->where('employee_code', 'like', $like)
                ->orWhere('employee_name', 'like', $like);
        });
    }

    /** @param array<string, mixed> $scope */
    private function applyScope(Builder $query, array $scope): void
    {
        if ($scope['all'] ?? false) {
            return;
        }

        $departments = $scope['departments'] ?? [];
        $employees = $scope['employees'] ?? [];
        if ($departments === [] && $employees === []) {
            $query->whereRaw('1 = 0');

            return;
        }

        $query->where(function (Builder $where) use ($departments, $employees) {
            foreach ($departments as $department) {
                $where->orWhere(function (Builder $departmentQuery) use ($department) {
                    $departmentQuery
                        ->where('company', $department['company'])
                        ->where('dept_code', trim((string) $department['dept_code']));
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

    /** @param array<string, mixed> $employee */
    private function assertOtEligible(array $employee, string $field = 'employee_code'): void
    {
        if (OtEmployeeEligibility::canRequest($employee)) {
            return;
        }

        throw ValidationException::withMessages([
            $field => 'ตำแหน่ง Employee with Disabilities ไม่มีสิทธิ์ขอ OT',
        ]);
    }

    private function timeOnly(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        preg_match('/(\d{2}):(\d{2})/', (string) $value, $matches);

        return isset($matches[1], $matches[2]) ? $matches[1].':'.$matches[2] : null;
    }
}
