<?php

namespace App\Services\OtApproval;

use App\Models\Insight\AppUser;
use App\Models\Insight\Employee;
use App\Models\OtApproval\LeaveRequest;
use App\Models\OtApproval\LeaveRequestApproval;
use App\Models\OtApproval\OtAttendanceSnapshot;
use App\Support\OtApproval\OtBranchFilter;
use App\Support\OtApproval\OtEmployeeEligibility;
use App\Support\OtApproval\OtPositionRank;
use App\Support\OtApproval\OtShiftGroup;
use App\Support\OtApproval\PayrollCycle;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/** Workflow ลา 75 แยกจาก OT โดยตั้งใจ: ไม่มีการอ่าน Attendance และอนุมัติแล้วผ่านทันที */
class LeaveRequestWorkflowService
{
  /** @return array<string, array<string, mixed>> */
  public function types(): array
  {
    return collect((array) config('leave_approval.types', []))
      ->map(fn (array $type, string $code) => array_merge($type, ['code' => $code]))
      ->all();
  }

  /** @param array<string, mixed> $scope */
  public function summary(CarbonInterface $date, array $scope): array
  {
    $employeesQuery = Employee::activeAt($date);
    $this->applyEmployeeScope($employeesQuery, $scope);
    $employees = $employeesQuery
      ->orderBy('company')->orderBy('dept_code')->orderBy('employee_code')
      ->get(['company', 'employee_code', 'dept_code', 'dept_th', 'dept_en']);

    $requestQuery = LeaveRequest::query()->notCancelled()->whereDate('leave_date', $date->toDateString());
    $this->applyRequestScope($requestQuery, $scope);
    // shift_code ใช้แยกยอด "ขอลา" ตามกะบนการ์ดแผนก ให้อ่านคู่กับยอดพนักงานรายกะ
    $requests = $requestQuery->get(['company', 'dept_code', 'shift_code', 'approval_status']);
    $requestGroups = $requests->groupBy(fn (LeaveRequest $row) => $row->company.'|'.trim((string) $row->dept_code));
    $shiftGroups = $this->shiftGroupMap($date, $employees);

    $companies = $employees
      ->groupBy('company')
      ->map(function (Collection $companyEmployees, string $company) use ($requestGroups, $shiftGroups) {
        $departments = $companyEmployees
          ->groupBy(fn (Employee $employee) => trim((string) $employee->dept_code))
          ->map(function (Collection $departmentEmployees, string $deptCode) use ($company, $requestGroups, $shiftGroups) {
            /** @var Employee $sample */
            $sample = $departmentEmployees->first();
            $items = $requestGroups->get($company.'|'.$deptCode, collect());

            /* ยอดรายกะให้การ์ดแผนกอ่านเป็นแถวเดียวกับหน้าภาพรวม
               ฝั่งพนักงานใช้กะจาก snapshot ส่วนฝั่งคำขอใช้กะที่ตรึงไว้ในตัวคำขอ
               เพราะคำขอที่ส่งไปแล้วต้องคงยอดตามเอกสารจริง ไม่วิ่งตามกะที่เปลี่ยนทีหลัง */
            $shifts = [];
            foreach ([OtShiftGroup::MORNING, OtShiftGroup::NIGHT, OtShiftGroup::UNKNOWN] as $group) {
              $shifts[$group] = [
                'total' => $departmentEmployees->filter(
                  fn (Employee $employee) => ($shiftGroups[$company.'|'.$employee->employee_code] ?? OtShiftGroup::UNKNOWN) === $group,
                )->count(),
                'requested' => $items->filter(
                  fn (LeaveRequest $row) => OtShiftGroup::of($row->shift_code) === $group,
                )->count(),
              ];
            }

            return [
              'code' => $deptCode,
              'name_th' => $sample->departmentLabel() ?: 'ไม่ระบุแผนก',
              'name_en' => $sample->departmentLabel() ?: 'Unassigned department',
              'name_my' => $sample->departmentLabel() ?: 'Unassigned department',
              'total' => $departmentEmployees->count(),
              'requested' => $items->count(),
              'pending' => $items->where('approval_status', LeaveRequest::APPROVAL_SUBMITTED)->count(),
              'approved' => $items->where('approval_status', LeaveRequest::APPROVAL_APPROVED)->count(),
              'rejected' => $items->where('approval_status', LeaveRequest::APPROVAL_REJECTED)->count(),
              'shifts' => $shifts,
            ];
          })->values();

        return [
          'code' => $company,
          'name' => \App\Models\OtApproval\OtDepartmentAssignment::COMPANIES[$company] ?? $company,
          'total' => $companyEmployees->count(),
          'departments' => $departments,
        ];
      })
      // เรียงตามลำดับใน COMPANIES (Supavut Industry มาก่อน Moldvanto) ไม่ใช่ตามลำดับที่ groupBy เจอ
      ->sortBy(fn (array $company) => array_search(
        $company['code'],
        array_keys(\App\Models\OtApproval\OtDepartmentAssignment::COMPANIES),
        true,
      ))
      ->values();

    return [
      'date' => $date->format('Y-m-d'),
      'generated_at' => now()->toIso8601String(),
      'totals' => [
        'employees' => $employees->count(),
        'requested' => $requests->count(),
        'pending' => $requests->where('approval_status', LeaveRequest::APPROVAL_SUBMITTED)->count(),
        'approved' => $requests->where('approval_status', LeaveRequest::APPROVAL_APPROVED)->count(),
        'rejected' => $requests->where('approval_status', LeaveRequest::APPROVAL_REJECTED)->count(),
      ],
      'companies' => $companies,
    ];
  }

  /**
   * จับคู่พนักงานกับกลุ่มกะ (เช้า/ดึก/ไม่ระบุ) สำหรับยอดรายกะบนการ์ดแผนก
   *
   * อ่านจาก Snapshot ท้องถิ่นเท่านั้น หน้าลาห้ามเรียก Bplus ตามข้อกำหนดเดิม
   * ลา 75 ขอล่วงหน้าเป็นปกติ วันลาส่วนใหญ่จึงยังไม่มี snapshot ถ้าไม่ถอยไปหากะล่าสุด
   * ทุกคนจะตกกลุ่ม "ไม่ระบุกะ" แล้วการ์ดจะไร้ประโยชน์ทันที — ใช้เกณฑ์ย้อนหลัง 90 วัน
   * ชุดเดียวกับ departmentEmployees() เพื่อให้ยอดบนการ์ดตรงกับตัวกรองกะในเอกสาร
   *
   * @param  Collection<int, Employee>  $employees
   * @return array<string, string>  คีย์เป็น `บริษัท|รหัสพนักงาน`
   */
  private function shiftGroupMap(CarbonInterface $date, Collection $employees): array
  {
    $map = [];

    foreach ($employees->groupBy('company') as $company => $companyEmployees) {
      $codes = $companyEmployees->pluck('employee_code')->all();
      $found = OtAttendanceSnapshot::query()
        ->where('company', $company)
        ->whereDate('work_date', $date->toDateString())
        ->whereIn('employee_code', $codes)
        ->pluck('shift_code', 'employee_code');

      $missing = array_values(array_diff($codes, $found->keys()->all()));

      if ($missing !== []) {
        $fallback = OtAttendanceSnapshot::query()
          ->where('company', $company)
          ->whereIn('employee_code', $missing)
          ->whereDate('work_date', '<', $date->toDateString())
          ->whereDate('work_date', '>=', $date->copy()->subDays(90)->toDateString())
          ->orderByDesc('work_date')
          ->get(['employee_code', 'shift_code']);

        foreach ($fallback as $row) {
          // เรียงวันจากใหม่ไปเก่า แถวแรกของแต่ละคนจึงเป็นกะล่าสุด
          if (! $found->has($row->employee_code)) {
            $found[$row->employee_code] = $row->shift_code;
          }
        }
      }

      foreach ($codes as $code) {
        $map[$company.'|'.$code] = OtShiftGroup::of($found[$code] ?? null);
      }
    }

    return $map;
  }

  /** @param array<string, mixed> $scope */
  public function departmentEmployees(
    CarbonInterface $date,
    string $company,
    string $deptCode,
    array $scope,
  ): array {
    if (! $this->scopeAllowsViewDepartment($scope, $company, $deptCode)) {
      throw ValidationException::withMessages(['department' => 'คุณไม่มีสิทธิ์ดูแลแผนกนี้']);
    }

    $query = Employee::activeAt($date)->where('company', $company);
    $this->whereDepartment($query, $deptCode);
    $this->applyEmployeeScope($query, $scope);
    $employees = $query->orderBy('employee_code')->get([
      'company', 'employee_code', 'license_id', 'title', 'name_th', 'surname_th', 'name_en',
      'job_code', 'job_th', 'job_en', 'dept_code', 'dept_th', 'dept_en',
      'branch_code', 'branch_th', 'branch_en',
    ]);

    $requestQuery = LeaveRequest::query()
      ->notCancelled()
      ->where('company', $company)
      ->whereDate('leave_date', $date->toDateString());
    $this->whereDepartment($requestQuery, $deptCode);
    $requests = $requestQuery->get()->keyBy('employee_code');
    $users = $this->profileUsers($employees);

    /* กะของวันนั้นอ่านจาก Snapshot ท้องถิ่นเท่านั้น หน้าลาห้ามเรียก Bplus ตามข้อกำหนดเดิม
       ใช้แค่ทำตัวกรองกะบนหน้าจอ ไม่ได้ใช้เป็นเงื่อนไขอนุมัติลา (ลาในวันหยุด/วันไม่มีกะได้) */
    $codes = $employees->pluck('employee_code')->all();
    $shiftColumns = ['employee_code', 'work_date', 'shift_code', 'shift_name_th', 'shift_name_en', 'shift_in', 'shift_out'];

    $shifts = OtAttendanceSnapshot::query()
      ->where('company', $company)
      ->whereDate('work_date', $date->toDateString())
      ->whereIn('employee_code', $codes)
      ->get($shiftColumns)
      ->keyBy('employee_code');

    /* ลา 75 ขอล่วงหน้าเป็นปกติ วันลาส่วนใหญ่จึงยังไม่มี snapshot ทำให้ช่องกะว่างเปล่า
       ถอยไปใช้กะล่าสุดที่เคยบันทึกไว้ก่อนวันลาแทน เพื่อให้พอเห็นว่าคนนี้อยู่กะไหน
       และตัวกรองกะยังใช้งานได้ — ติดธง shift_is_estimated ไว้ให้หน้าจอบอกผู้ใช้ว่าเป็นกะล่าสุด ไม่ใช่กะของวันนั้นจริง */
    $estimated = [];
    $missing = array_values(array_diff($codes, $shifts->keys()->all()));

    if ($missing !== []) {
      $fallback = OtAttendanceSnapshot::query()
        ->where('company', $company)
        ->whereIn('employee_code', $missing)
        ->whereDate('work_date', '<', $date->toDateString())
        ->whereDate('work_date', '>=', $date->subDays(90)->toDateString())
        ->orderByDesc('work_date')
        ->get($shiftColumns);

      foreach ($fallback as $row) {
        // เรียงวันจากใหม่ไปเก่า แถวแรกของแต่ละคนจึงเป็นกะล่าสุด
        if (! isset($estimated[$row->employee_code])) {
          $estimated[$row->employee_code] = $row;
        }
      }
    }

    return [
      'date' => $date->format('Y-m-d'),
      'company' => $company,
      'dept_code' => $deptCode,
      'employees' => $employees->map(function (Employee $employee) use ($requests, $users, $shifts, $estimated) {
        $user = $employee->license_id ? $users->get((string) $employee->license_id) : null;
        $request = $requests->get($employee->employee_code);
        $shift = $shifts->get($employee->employee_code);
        $isEstimated = false;

        if (! $shift && isset($estimated[$employee->employee_code])) {
          $shift = $estimated[$employee->employee_code];
          $isEstimated = true;
        }
        $nameTh = $employee->fullNameTh() ?: $employee->fullNameEn() ?: $employee->employee_code;
        $nameEn = $employee->fullNameEn() ?: $nameTh;

        return [
          'company' => $employee->company,
          'code' => $employee->employee_code,
          'name_th' => $nameTh,
          'name_en' => $nameEn,
          'name_my' => $nameEn,
          'position_th' => $employee->job_th ?: $employee->job_en ?: '-',
          'position_en' => $employee->job_en ?: $employee->job_th ?: '-',
          'position_my' => $employee->job_en ?: $employee->job_th ?: '-',
          'department_th' => $employee->deptThClean() ?: $employee->dept_en ?: '-',
          'department_en' => $employee->dept_en ?: $employee->deptThClean() ?: '-',
          'department_my' => $employee->dept_en ?: $employee->deptThClean() ?: '-',
          'dept_code' => trim((string) $employee->dept_code),
          // สาขาใช้ทำตัวกรอง `โรงงาน` / `โรงงาน-พม่า` ชุดเดียวกับหน้าขอ OT
          // ลำดับอาวุโสของตำแหน่ง ใช้เรียงสูง→ต่ำ ชุดเดียวกับหน้าภาพรวม OT (ดู OtPositionRank)
          'position_rank' => OtPositionRank::of($employee->job_code),
          'branch_code' => trim((string) $employee->branch_code),
          'branch_th' => $employee->branch_th ?: $employee->branch_en ?: '',
          'branch_en' => $employee->branch_en ?: $employee->branch_th ?: '',
          'avatar' => $user?->profile_picture ? asset('storage/'.$user->profile_picture) : null,
          'shift_code' => trim((string) ($shift->shift_code ?? '')),
          'shift_name_th' => trim((string) ($shift->shift_name_th ?? '')),
          'shift_name_en' => trim((string) ($shift->shift_name_en ?? '')),
          'shift_in' => $shift?->shift_in ? substr((string) $shift->shift_in, 0, 5) : null,
          'shift_out' => $shift?->shift_out ? substr((string) $shift->shift_out, 0, 5) : null,
          'shift_group' => OtShiftGroup::of($shift->shift_code ?? null),
          // true = ไม่ใช่กะของวันลาจริง แต่เป็นกะล่าสุดที่เคยบันทึกไว้
          'shift_is_estimated' => $isEstimated,
          'shift_source_date' => $isEstimated ? $shift->work_date->format('Y-m-d') : null,
          /* ตำแหน่งนี้ถูก admin สั่งห้ามขอลาไว้ไหม (หัวข้อ 8 ในหน้าตั้งค่า)
             ส่งมาเป็นธงพร้อมเหตุผล ไม่ตัดคนออกจากรายชื่อ เพราะต้องเห็นว่าทำไมกดไม่ได้ */
          'can_request_leave' => OtEmployeeEligibility::canRequestLeave(['job_code' => $employee->job_code]),
          'leave_block_reason' => OtEmployeeEligibility::evaluateLeave(['job_code' => $employee->job_code])['reason'],
          'leave_request' => $request ? $this->requestPayload($request) : null,
        ];
      })->values(),
    ];
  }

  /** @param array<string, mixed> $data @param array<string, mixed> $scope @return Collection<int, LeaveRequest> */
  public function saveDraft(array $data, AppUser $actor, array $scope): Collection
  {
    $company = trim((string) $data['company']);
    $deptCode = trim((string) ($data['dept_code'] ?? ''));
    if (! $this->scopeAllowsDepartment($scope, $company, $deptCode)) {
      throw ValidationException::withMessages(['department' => 'คุณไม่มีสิทธิ์ดูแลแผนกนี้']);
    }

    $start = CarbonImmutable::createFromFormat('Y-m-d', $data['start_date'])->startOfDay();
    $end = CarbonImmutable::createFromFormat('Y-m-d', $data['end_date'])->startOfDay();
    if ($start->diffInDays($end) > 30) {
      throw ValidationException::withMessages(['end_date' => 'หนึ่งคำขอเลือกช่วงวันได้ไม่เกิน 31 วัน']);
    }

    $employee = Employee::activeAt($start)
      ->where('company', $company)
      ->where('employee_code', trim((string) $data['employee_code']))
      ->first();
    if (! $employee || trim((string) $employee->dept_code) !== $deptCode) {
      throw ValidationException::withMessages(['employee_code' => 'ไม่พบพนักงานที่ยังทำงานอยู่ในแผนกนี้']);
    }

    $typeCode = trim((string) $data['leave_type']);
    $type = (array) config('leave_approval.types.'.$typeCode, []);
    if ($type === []) {
      throw ValidationException::withMessages(['leave_type' => 'ไม่พบประเภทการลาที่เลือก']);
    }

    $dates = collect();
    for ($cursor = $start; $cursor->lessThanOrEqualTo($end); $cursor = $cursor->addDay()) {
      if (! PayrollCycle::canApprove($cursor)) {
        throw ValidationException::withMessages(['start_date' => 'คำขอนี้พ้นกำหนดอนุมัติของรอบเงินเดือนแล้ว']);
      }

      if (! Employee::activeAt($cursor)
        ->where('company', $company)
        ->where('employee_code', $employee->employee_code)
        ->exists()) {
        throw ValidationException::withMessages([
          'end_date' => 'พนักงานไม่ได้อยู่ในสถานะทำงานตลอดช่วงวันที่เลือก',
        ]);
      }
      $dates->push($cursor);
    }

    $batchUuid = (string) Str::uuid();

    return DB::connection('mysql_ot_approval')->transaction(function () use (
      $dates, $batchUuid, $company, $deptCode, $employee, $typeCode, $type, $start, $end, $data, $actor,
    ) {
      return $dates->map(function (CarbonImmutable $date) use (
        $batchUuid, $company, $deptCode, $employee, $typeCode, $type, $start, $end, $data, $actor,
      ) {
        /* ข้ามคำขอที่ยกเลิกแล้ว ไม่งั้นจะไปเขียนทับแถวประวัติแทนที่จะสร้างใบใหม่ */
        $existing = LeaveRequest::query()
          ->notCancelled()
          ->where('company', $company)
          ->where('employee_code', $employee->employee_code)
          ->whereDate('leave_date', $date->toDateString())
          ->where('leave_type', $typeCode)
          ->lockForUpdate()
          ->first();

        if ($existing && in_array($existing->approval_status, [
          LeaveRequest::APPROVAL_SUBMITTED,
          LeaveRequest::APPROVAL_APPROVED,
        ], true)) {
          throw ValidationException::withMessages([
            'start_date' => 'วันที่ '.$date->format('d/m/Y').' มีคำขอที่ส่งอนุมัติแล้ว',
          ]);
        }

        $values = [
          'batch_uuid' => $batchUuid,
          'company' => $company,
          'dept_code' => $deptCode,
          'employee_code' => $employee->employee_code,
          'employee_name' => $employee->fullNameTh() ?: $employee->fullNameEn() ?: $employee->employee_code,
          'position_name' => $employee->job_th ?: $employee->job_en,
          'department_name' => $employee->deptThClean() ?: $employee->dept_en,
          'leave_date' => $date->toDateString(),
          'range_start' => $start->toDateString(),
          'range_end' => $end->toDateString(),
          'leave_type' => $typeCode,
          'bplus_stamp_type_key' => (string) ($type['bplus_stamp_type_key'] ?? ''),
          'deduction_agreement_code' => (string) ($type['deduction_agreement_code'] ?? ''),
          'shift_code' => (string) config('leave_approval.export.shift_code', '00'),
          'swipe_character_code' => (string) config('leave_approval.export.swipe_character_code', '0'),
          'approval_method' => (string) config('leave_approval.export.approval_method', '1'),
          'leave_quantity' => (string) ($type['quantity'] ?? '1'),
          'note' => trim((string) ($data['note'] ?? '')) ?: null,
          'approval_status' => LeaveRequest::APPROVAL_DRAFT,
          'export_status' => LeaveRequest::EXPORT_NOT_READY,
          'created_by_app_user_id' => $actor->id,
          'created_by_employee_code' => $actor->employee_code,
          'decided_by_app_user_id' => null,
          'decided_by_employee_code' => null,
          'decision_note' => null,
          'submitted_at' => null,
          'decided_at' => null,
        ];

        if ($existing) {
          $existing->forceFill($values)->save();

          return $existing->refresh();
        }

        return LeaveRequest::create($values);
      })->values();
    });
  }

  /** @param array<int, int|string> $ids @param array<string, mixed> $scope @return array<int, array<string, mixed>> */
  public function submitDrafts(array $ids, AppUser $actor, array $scope): array
  {
    return DB::connection('mysql_ot_approval')->transaction(function () use ($ids, $actor, $scope) {
      $requests = LeaveRequest::query()->whereIn('id', $ids)->lockForUpdate()->get();
      if ($requests->count() !== count(array_unique(array_map('intval', $ids)))) {
        throw ValidationException::withMessages(['request_ids' => 'มีคำขอบางรายการไม่อยู่ในระบบ']);
      }

      foreach ($requests as $request) {
        if ($request->approval_status !== LeaveRequest::APPROVAL_DRAFT) {
          throw ValidationException::withMessages(['request_ids' => 'ส่งได้เฉพาะคำขอที่เป็นร่าง']);
        }
        if (! $this->scopeAllowsDepartment($scope, $request->company, (string) $request->dept_code)) {
          throw ValidationException::withMessages(['request_ids' => 'มีคำขออยู่นอกแผนกที่รับผิดชอบ']);
        }
        if (! PayrollCycle::canApprove($request->leave_date)) {
          throw ValidationException::withMessages(['request_ids' => 'คำขอนี้พ้นกำหนดอนุมัติของรอบเงินเดือนแล้ว']);
        }
      }

      foreach ($requests as $request) {
        $request->forceFill([
          'approval_status' => LeaveRequest::APPROVAL_SUBMITTED,
          'submitted_at' => now(),
        ])->save();
      }

      return $requests->map(fn (LeaveRequest $request) => $this->requestPayload($request->refresh()))->all();
    });
  }

  /**
   * ลบร่างคำขอลาที่ยังไม่ส่ง — จำเป็นเพราะ "ขอลาทั้งหมด" สร้างทีเดียวหลายคนหลายวัน
   * ถ้าเลือกผิดต้องถอยได้ ส่วนที่ส่งอนุมัติแล้วให้ Supervisor ปฏิเสธแทน ห้ามลบทิ้ง
   *
   * @param  array<int, int|string>  $ids
   * @param  array<string, mixed>  $scope
   */
  /**
   * ยกเลิกคำขอลาที่ยังไม่มีการตัดสิน — เก็บแถวไว้เป็นประวัติ ไม่ลบทิ้ง
   *
   * ของเดิมชื่อ `deleteDrafts()` และลบแถวจริง ตาราง `leave_request_approvals`
   * ตั้ง `cascadeOnDelete()` ไว้ ประวัติจึงหายไปพร้อมกันทั้งหมด
   * ตรวจย้อนไม่ได้ว่าเคยส่งคำขอนั้นให้ Supervisor ทั้งที่อีเมลออกไปแล้ว
   *
   * ยกเลิกได้เฉพาะ draft/submitted — ที่ตัดสินไปแล้วต้องให้ Supervisor เป็นคนแก้
   *
   * @param int[] $ids
   * @param array<string, mixed> $scope
   */
  public function cancelRequests(array $ids, string $reason, AppUser $actor, array $scope): int
  {
    $reason = trim($reason);
    if ($reason === '') {
      throw ValidationException::withMessages(['reason' => 'กรุณาระบุเหตุผลที่ยกเลิก']);
    }

    return DB::connection('mysql_ot_approval')->transaction(function () use ($ids, $reason, $actor, $scope) {
      $requests = LeaveRequest::query()->whereIn('id', $ids)->lockForUpdate()->get();
      if ($requests->count() !== count(array_unique(array_map('intval', $ids)))) {
        throw ValidationException::withMessages(['request_ids' => 'มีคำขอบางรายการไม่อยู่ในระบบ']);
      }

      foreach ($requests as $request) {
        if (! $this->scopeAllowsDepartment($scope, $request->company, (string) $request->dept_code)) {
          throw ValidationException::withMessages(['request_ids' => 'มีคำขออยู่นอกแผนกที่รับผิดชอบ']);
        }
        if (! in_array($request->approval_status, [LeaveRequest::APPROVAL_DRAFT, LeaveRequest::APPROVAL_SUBMITTED], true)) {
          throw ValidationException::withMessages(['request_ids' => 'ยกเลิกได้เฉพาะคำขอที่ยังไม่ถูกตัดสิน']);
        }

        $request->approval_status = LeaveRequest::APPROVAL_CANCELLED;
        $request->cancelled_at = now();
        $request->cancel_reason = $reason;
        $request->cancelled_by_app_user_id = $actor->id;
        $request->cancelled_by_employee_code = $actor->employee_code;
        // ปิดทางส่งออก ไม่งั้นคำขอที่ยกเลิกแล้วยังหลุดเข้าไฟล์ลา 75 ได้
        $request->export_status = LeaveRequest::EXPORT_NOT_READY;
        /* คืนช่อง unique ให้ขอใหม่วันเดิมได้ — ดู migration 2026_08_19_010000 */
        $request->active_slot = null;
        $request->save();

        LeaveRequestApproval::create([
          'leave_request_id' => $request->id,
          'decision' => 'cancelled',
          'note' => $reason,
          'actor_app_user_id' => $actor->id,
          'actor_employee_code' => $actor->employee_code,
        ]);
      }

      return $requests->count();
    });
  }

  /** @param array<string, mixed> $scope @return array<int, array<string, mixed>> */
  public function approvalQueue(array $scope, string $filter, ?CarbonImmutable $leaveDate = null): array
  {
    return $this->approvalQueuePage($scope, $filter, $leaveDate, 1, 0)['requests'];
  }

  /**
   * คิวอนุมัติแบบแบ่งหน้าที่ฐานข้อมูล
   *
   * ลา 75 วันหยุดทั้งบริษัทสร้างทีเดียวเป็นพันแถว ถ้าดึงมาทั้งก้อนแล้วค่อยตัดหน้าใน JS
   * เบราว์เซอร์จะค้างตั้งแต่ตอน parse JSON จึงต้อง LIMIT/OFFSET ที่ SQL
   * ส่ง $perPage = 0 เพื่อเอาทั้งหมด (ใช้ในเทสต์และงานที่ต้องการชุดเต็ม)
   *
   * @param  array<string, mixed>  $scope
   * @return array{requests:array<int, array<string, mixed>>, total:int, page:int, per_page:int, last_page:int}
   */
  public function approvalQueuePage(
    array $scope,
    string $filter,
    ?CarbonImmutable $leaveDate,
    int $page,
    int $perPage,
    string $search = '',
    string $branch = OtBranchFilter::ALL,
    string $leaveType = 'all',
  ): array {
    $query = LeaveRequest::query();
    $this->applyRequestScope($query, $scope);
    $this->applyEmployeeSearch($query, $search);
    // คิวแบ่งหน้าที่ SQL จึงต้องกรองสาขาที่เซิร์ฟเวอร์ ไม่งั้นจะกรองได้แค่หน้าที่เปิดอยู่
    OtBranchFilter::apply($query, $branch);
    // ประเภทการลาก็ต้องกรองที่เซิร์ฟเวอร์ด้วยเหตุผลเดียวกัน (กรองในเครื่องจะได้แค่หน้าที่เปิดอยู่)
    $leaveType = trim($leaveType);
    if ($leaveType !== '' && $leaveType !== 'all') {
      $query->where('leave_type', $leaveType);
    }
    if ($leaveDate) {
      $query->whereDate('leave_date', $leaveDate->toDateString());
    }
    if (in_array($filter, ['pending', 'approved', 'rejected', 'cancelled'], true)) {
      $query->where('approval_status', match ($filter) {
        'approved' => LeaveRequest::APPROVAL_APPROVED,
        'rejected' => LeaveRequest::APPROVAL_REJECTED,
        'cancelled' => LeaveRequest::APPROVAL_CANCELLED,
        default => LeaveRequest::APPROVAL_SUBMITTED,
      });
    } else {
      $query->whereIn('approval_status', [
        LeaveRequest::APPROVAL_SUBMITTED,
        LeaveRequest::APPROVAL_APPROVED,
        LeaveRequest::APPROVAL_REJECTED,
        LeaveRequest::APPROVAL_CANCELLED,
      ]);
    }

    $total = (clone $query)->count();
    $perPage = max(0, $perPage);
    $lastPage = $perPage > 0 ? max(1, (int) ceil($total / $perPage)) : 1;
    $page = $perPage > 0 ? min(max(1, $page), $lastPage) : 1;

    $query->orderByDesc('leave_date')->orderBy('company')->orderBy('dept_code')->orderBy('employee_code');
    if ($perPage > 0) {
      $query->forPage($page, $perPage);
    }

    $requests = $query->get();
    $users = AppUser::query()->whereIn('id', $requests->pluck('created_by_app_user_id')
      ->merge($requests->pluck('decided_by_app_user_id'))
      ->merge($requests->pluck('cancelled_by_app_user_id'))
      ->filter()
      ->unique())->get()->keyBy('id');
    $employeePresentations = $this->employeePresentations($requests);

    return [
      'requests' => $requests->map(function (LeaveRequest $request) use ($users, $employeePresentations) {
        $payload = $this->requestPayload(
          $request,
          $users->get($request->created_by_app_user_id),
          $users->get($request->decided_by_app_user_id),
          $users->get($request->cancelled_by_app_user_id),
        );

        return array_merge($payload, $employeePresentations->get(
          $request->company.'|'.$request->employee_code,
          ['avatar' => null],
        ));
      })->all(),
      'total' => $total,
      'page' => $page,
      'per_page' => $perPage,
      'last_page' => $lastPage,
    ];
  }

  /**
   * รูปของ "พนักงานที่ลา" แยกจากรูปผู้ขอ/ผู้อนุมัติใน userPayload
   *
   * ดึงเป็นชุดเดียวแล้ว map ด้วย company|employee_code เพราะรหัสพนักงานซ้ำข้ามบริษัทได้
   * และเลี่ยงการยิง query ต่อแถวเวลาคิวยาว
   *
   * @param  Collection<int, LeaveRequest>  $requests
   * @return Collection<string, array<string, string|null>>
   */
  private function employeePresentations(Collection $requests): Collection
  {
    if ($requests->isEmpty()) {
      return collect();
    }

    $employees = Employee::query()
      ->whereIn('company', $requests->pluck('company')->unique()->all())
      ->whereIn('employee_code', $requests->pluck('employee_code')->unique()->all())
      ->get(['company', 'employee_code', 'license_id', 'title', 'name_th', 'surname_th', 'name_en']);
    $profiles = $this->profileUsers($employees);

    return $employees->mapWithKeys(function (Employee $employee) use ($profiles) {
      $picture = $employee->license_id ? $profiles->get($employee->license_id)?->profile_picture : null;
      $nameTh = $employee->fullNameTh() ?: ($employee->fullNameEn() ?: $employee->employee_code);
      $nameEn = $employee->fullNameEn() ?: $nameTh;

      return [$employee->company.'|'.$employee->employee_code => [
        'avatar' => $picture ? asset('storage/'.$picture) : null,
        'employee_name_th' => $nameTh,
        'employee_name_en' => $nameEn,
        'employee_name_my' => $nameEn,
      ]];
    });
  }

  /** @param array<string, mixed> $scope */
  public function decide(LeaveRequest $request, AppUser $actor, array $scope, string $decision, ?string $note): LeaveRequest
  {
    return DB::connection('mysql_ot_approval')->transaction(function () use ($request, $actor, $scope, $decision, $note) {
      $request = LeaveRequest::query()->lockForUpdate()->findOrFail($request->id);
      if (! $this->scopeAllowsDepartment($scope, $request->company, (string) $request->dept_code)) {
        throw ValidationException::withMessages(['request' => 'คำขออยู่นอกแผนกที่รับผิดชอบ']);
      }
      $isApprovedToRejected = $request->approval_status === LeaveRequest::APPROVAL_APPROVED
        && $decision === LeaveRequest::APPROVAL_REJECTED;
      $isRejectedToApproved = $request->approval_status === LeaveRequest::APPROVAL_REJECTED
        && $decision === LeaveRequest::APPROVAL_APPROVED;
      if ($request->approval_status !== LeaveRequest::APPROVAL_SUBMITTED && ! $isApprovedToRejected && ! $isRejectedToApproved) {
        throw ValidationException::withMessages(['request' => 'ดำเนินการได้เฉพาะคำขอที่รออนุมัติ']);
      }
      if (! PayrollCycle::canApprove($request->leave_date)) {
        throw ValidationException::withMessages(['request' => 'พ้นกำหนดอนุมัติของรอบเงินเดือนแล้ว']);
      }
      $note = trim((string) $note);
      if ($decision === LeaveRequest::APPROVAL_REJECTED && $note === '') {
        throw ValidationException::withMessages(['note' => 'กรุณาระบุเหตุผลที่ไม่อนุมัติ']);
      }

      $request->forceFill([
        'approval_status' => $decision,
        'export_status' => $decision === LeaveRequest::APPROVAL_APPROVED
          ? LeaveRequest::EXPORT_READY
          : LeaveRequest::EXPORT_NOT_READY,
        'decided_by_app_user_id' => $actor->id,
        'decided_by_employee_code' => $actor->employee_code,
        'decision_note' => $note ?: null,
        'decided_at' => now(),
      ])->save();

      LeaveRequestApproval::create([
        'leave_request_id' => $request->id,
        'decision' => $decision,
        'actor_app_user_id' => $actor->id,
        'actor_employee_code' => $actor->employee_code,
        'note' => $note ?: null,
      ]);

      return $request->refresh();
    });
  }

  /** @param array<int, int|string> $ids @param array<string, mixed> $scope @return array{updated:array<int, LeaveRequest>,skipped:array<int, array<string, mixed>>} */
  public function decideMany(array $ids, AppUser $actor, array $scope, string $decision, ?string $note): array
  {
    $updated = [];
    $skipped = [];
    $normalizedIds = collect($ids)->map(fn ($id) => (int) $id)->unique()->values();
    $requests = LeaveRequest::query()->whereIn('id', $normalizedIds)->get()->keyBy('id');

    foreach ($normalizedIds as $id) {
      $request = $requests->get($id);
      if (! $request) {
        $skipped[] = ['id' => $id, 'message' => 'ไม่พบคำขอนี้'];
        continue;
      }

      try {
        $updated[] = $this->decide($request, $actor, $scope, $decision, $note);
      } catch (\Throwable $exception) {
        $message = $exception instanceof ValidationException
          ? collect($exception->errors())->flatten()->first()
          : $exception->getMessage();
        $skipped[] = ['id' => $request->id, 'message' => (string) ($message ?: 'ดำเนินการไม่สำเร็จ')];
      }
    }

    return ['updated' => $updated, 'skipped' => $skipped];
  }

  public function requestPayload(LeaveRequest $request, ?AppUser $creator = null, ?AppUser $decider = null, ?AppUser $canceller = null): array
  {
    $creator ??= $request->created_by_app_user_id ? AppUser::find($request->created_by_app_user_id) : null;
    $decider ??= $request->decided_by_app_user_id ? AppUser::find($request->decided_by_app_user_id) : null;
    $canceller ??= $request->cancelled_by_app_user_id ? AppUser::find($request->cancelled_by_app_user_id) : null;
    $status = $request->approval_status;
    $statusLabel = match ($status) {
      LeaveRequest::APPROVAL_DRAFT => 'รอดำเนินการ',
      LeaveRequest::APPROVAL_SUBMITTED => 'รอดำเนินการ',
      LeaveRequest::APPROVAL_APPROVED => 'ผ่าน',
      LeaveRequest::APPROVAL_REJECTED => 'ไม่อนุมัติ',
      LeaveRequest::APPROVAL_CANCELLED => 'ยกเลิก',
      default => '-',
    };
    $tone = match ($status) {
      LeaveRequest::APPROVAL_SUBMITTED => 'warning',
      LeaveRequest::APPROVAL_APPROVED => 'success',
      LeaveRequest::APPROVAL_REJECTED => 'danger',
      LeaveRequest::APPROVAL_CANCELLED => 'neutral',
      LeaveRequest::APPROVAL_DRAFT => 'warning',
      default => 'neutral',
    };

    return [
      'id' => $request->id,
      'batch_uuid' => $request->batch_uuid,
      'company' => $request->company,
      'dept_code' => $request->dept_code,
      'employee_code' => $request->employee_code,
      'employee_name' => $request->employee_name ?: $request->employee_code,
      'employee_name_th' => $request->employee_name ?: $request->employee_code,
      'employee_name_en' => $request->employee_name ?: $request->employee_code,
      'employee_name_my' => $request->employee_name ?: $request->employee_code,
      'position_name' => $request->position_name ?: '-',
      'department_name' => $request->department_name ?: '-',
      'leave_date' => $request->leave_date->format('Y-m-d'),
      'leave_date_label' => $request->leave_date->format('d/m/Y'),
      'range_start' => $request->range_start->format('Y-m-d'),
      'range_end' => $request->range_end->format('Y-m-d'),
      'leave_type' => $request->leave_type,
      'leave_type_label_th' => $request->leaveTypeLabel('th'),
      'leave_type_label_en' => $request->leaveTypeLabel('en'),
      'leave_type_label_my' => $request->leaveTypeLabel('my'),
      'leave_quantity' => (float) $request->leave_quantity,
      'note' => $request->note,
      'decision_note' => $request->decision_note,
      'cancel_reason' => $request->cancel_reason,
      'approval_status' => $status,
      'status_label' => $statusLabel,
      'status_tone' => $tone,
      'status_detail' => $status === LeaveRequest::APPROVAL_SUBMITTED ? '(รอ Supervisor อนุมัติ)' : null,
      'can_decide' => $status === LeaveRequest::APPROVAL_SUBMITTED
        && PayrollCycle::canApprove($request->leave_date),
      'submitted_at' => $request->submitted_at?->format('d/m/Y H:i'),
      // วันที่ยื่นคำขอ — ร่างที่ยังไม่ส่งใช้วันที่สร้างไปก่อน เพื่อให้ตารางไม่มีช่องว่าง
      'requested_date_label' => ($request->submitted_at ?: $request->created_at)?->format('d/m/Y'),
      'decided_at' => $request->decided_at?->format('d/m/Y H:i'),
      'cancelled_at' => $request->cancelled_at?->format('d/m/Y H:i'),
      'requester' => $this->userPayload($creator, $request->created_by_employee_code),
      'approver' => $this->userPayload($decider, $request->decided_by_employee_code),
      'canceller' => $this->userPayload($canceller, $request->cancelled_by_employee_code),
      'payroll_cycle' => PayrollCycle::containing($request->leave_date)['label'],
    ];
  }

  private function userPayload(?AppUser $user, ?string $fallbackCode): array
  {
    return [
      'code' => $user?->employee_code ?: $fallbackCode,
      'name' => $user ? ($user->fullNameTh() ?: $user->full_name_en ?: $user->employee_code) : ($fallbackCode ?: '-'),
      'avatar' => $user?->profile_picture ? asset('storage/'.$user->profile_picture) : null,
    ];
  }

  /** @param Collection<int, Employee> $employees @return Collection<string, AppUser> */
  private function profileUsers(Collection $employees): Collection
  {
    $identities = $employees->pluck('license_id')->filter()->unique()->values();

    return $identities->isEmpty()
      ? collect()
      : AppUser::query()->whereIn('id_thai_hash', $identities)->get()->keyBy('id_thai_hash');
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

  /** ผู้ใช้ทั่วไปเปิดได้เฉพาะแผนกของตน และ applyEmployeeScope จะคืนเฉพาะแถวของตน */
  private function scopeAllowsViewDepartment(array $scope, string $company, string $deptCode): bool
  {
    if ($this->scopeAllowsDepartment($scope, $company, $deptCode)) {
      return true;
    }

    return collect($scope['employees'] ?? [])->contains(
      fn (array $employee) => $employee['company'] === $company
        && trim((string) ($employee['dept_code'] ?? '')) === trim($deptCode)
        && trim((string) ($employee['employee_code'] ?? '')) !== '',
    );
  }

  /** @param array<string, mixed> $scope */
  private function applyEmployeeScope(Builder $query, array $scope): void
  {
    if ($scope['all'] ?? false) {
      return;
    }

    $departments = $scope['departments'] ?? [];
    $employees = $scope['employees'] ?? [];
    $query->where(function (Builder $where) use ($departments, $employees) {
      foreach ($departments as $department) {
        $where->orWhere(function (Builder $departmentQuery) use ($department) {
          $departmentQuery->where('company', $department['company']);
          $this->whereDepartment($departmentQuery, trim((string) $department['dept_code']));
        });
      }
      foreach ($employees as $employee) {
        $where->orWhere(function (Builder $employeeQuery) use ($employee) {
          $employeeQuery->where('company', $employee['company'])->where('employee_code', $employee['employee_code']);
        });
      }
    });
  }

  /** @param array<string, mixed> $scope */
  /**
   * ค้นหาด้วยรหัสพนักงานหรือชื่อ-สกุล
   *
   * คิวอนุมัติลาแบ่งหน้าที่ฐานข้อมูล (ลา 75 ทั้งบริษัทเป็นพันแถว) ถ้ากรองในเครื่อง
   * จะกรองได้แค่หน้าที่เปิดอยู่ จึงต้องกรองที่ SQL
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

  private function applyRequestScope(Builder $query, array $scope): void
  {
    if ($scope['all'] ?? false) {
      return;
    }

    $departments = $scope['departments'] ?? [];
    $employees = $scope['employees'] ?? [];
    $query->where(function (Builder $where) use ($departments, $employees) {
      foreach ($departments as $department) {
        $where->orWhere(function (Builder $departmentQuery) use ($department) {
          $departmentQuery->where('company', $department['company']);
          $this->whereDepartment($departmentQuery, trim((string) $department['dept_code']));
        });
      }
      foreach ($employees as $employee) {
        $where->orWhere(function (Builder $employeeQuery) use ($employee) {
          $employeeQuery->where('company', $employee['company'])->where('employee_code', $employee['employee_code']);
        });
      }
    });
  }

  private function whereDepartment(Builder $query, string $deptCode): void
  {
    $deptCode === '' ? $query->where(function (Builder $empty) {
      $empty->whereNull('dept_code')->orWhere('dept_code', '');
    }) : $query->where('dept_code', $deptCode);
  }
}
