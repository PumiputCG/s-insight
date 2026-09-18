<?php

namespace App\Http\Controllers\OtApproval;

use App\Http\Controllers\Controller;
use App\Http\Controllers\OtApproval\Concerns\HandlesOtApprovalAccess;
use App\Models\Insight\Employee;
use App\Models\OtApproval\OtDepartmentAssignment;
use App\Services\OtApproval\LeaveRequestWorkflowService;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LeaveApprovalController extends Controller
{
  use HandlesOtApprovalAccess;

  public function overview(Request $request, LeaveRequestWorkflowService $workflow): View|RedirectResponse
  {
    if ($redirect = $this->gateOtEnter()) {
      return $redirect;
    }

    $date = $this->selectedDate($request);

    return view('ot_approval.leave-overview', [
      'me' => $this->me(),
      'isOtAdmin' => $this->isOtAdmin(),
      'selectedDate' => $date,
      'leaveSummary' => $workflow->summary($date, $this->overviewScope()),
    ]);
  }

  public function overviewEmployees(Request $request, LeaveRequestWorkflowService $workflow): JsonResponse
  {
    if ($this->gateOtEnter()) {
      return response()->json(['ok' => false, 'message' => 'no permission'], 403);
    }

    $data = $request->validate([
      'company' => ['required', 'string', 'in:'.implode(',', array_keys(OtDepartmentAssignment::COMPANIES))],
      'dept_code' => ['nullable', 'string', 'max:50'],
      'date' => ['nullable', 'date_format:Y-m-d'],
    ]);

    return response()->json([
      'ok' => true,
      'leave' => $workflow->departmentEmployees(
        $this->selectedDate($request),
        $data['company'],
        trim((string) ($data['dept_code'] ?? '')),
        $this->overviewScope(),
      ),
    ]);
  }

  public function requests(Request $request, LeaveRequestWorkflowService $workflow): View|RedirectResponse
  {
    if ($redirect = $this->gateOtEnter()) {
      return $redirect;
    }
    if ($redirect = $this->gateOtRole(OtDepartmentAssignment::ROLE_FOREMAN, OtDepartmentAssignment::MODULE_LEAVE)) {
      return $redirect;
    }

    $date = $this->selectedDate($request);

    return view('ot_approval.leave-requests', [
      'me' => $this->me(),
      'isOtAdmin' => $this->isOtAdmin(),
      'selectedDate' => $date,
      'leaveTypes' => $workflow->types(),
      'leaveSummary' => $workflow->summary($date, $this->workflowScope(OtDepartmentAssignment::ROLE_FOREMAN, OtDepartmentAssignment::MODULE_LEAVE)),
      // กะที่ Admin เซ็ตไว้ของ module ลา ต้องตรงกับหน้าขอ OT ที่มีป้าย "กะที่คุณดูแล"
      'myShiftByDept' => $this->myShiftByDepartment(OtDepartmentAssignment::MODULE_LEAVE),
    ]);
  }

  public function approvals(Request $request, LeaveRequestWorkflowService $workflow): View|RedirectResponse
  {
    if ($redirect = $this->gateOtEnter()) {
      return $redirect;
    }
    if ($redirect = $this->gateOtRole(OtDepartmentAssignment::ROLE_SUPERVISOR, OtDepartmentAssignment::MODULE_LEAVE)) {
      return $redirect;
    }

    return view('ot_approval.leave-approvals', [
      'me' => $this->me(),
      'isOtAdmin' => $this->isOtAdmin(),
      'selectedDate' => $this->optionalDate($request),
      // ใช้ทำตัวเลือกของตัวกรอง "ประเภทการลา" ในแถบเครื่องมือ
      'leaveTypes' => $workflow->types(),
    ]);
  }

  private function selectedDate(Request $request): CarbonImmutable
  {
    $data = $request->validate(['date' => ['nullable', 'date_format:Y-m-d']]);

    return isset($data['date'])
      ? CarbonImmutable::createFromFormat('Y-m-d', $data['date'])->startOfDay()
      : CarbonImmutable::today();
  }

  private function optionalDate(Request $request): ?CarbonImmutable
  {
    $raw = trim((string) $request->query('date', ''));
    if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $raw)) {
      return null;
    }

    return rescue(fn () => CarbonImmutable::createFromFormat('Y-m-d', $raw)->startOfDay(), null, false);
  }

  /** @return array{all:bool,departments:array<int,array<string,string>>,employees:array<int,array<string,string>>} */
  private function overviewScope(): array
  {
    if ($this->isOtAdmin()) {
      return ['all' => true, 'departments' => [], 'employees' => []];
    }

    /* ต้องกรอง module ด้วย ไม่งั้นคนที่ถูกตั้งไว้เฉพาะฝั่ง OT
       จะเห็นพนักงานของแผนกนั้นในหน้าภาพรวมการลาทั้งที่ไม่มีสิทธิ์ฝั่งลา
       ถ้าไม่มีสิทธิ์ฝั่งนี้เลย จะตกไปใช้ scope ของตัวเองด้านล่างเอง */
    $me = $this->me();
    $departments = OtDepartmentAssignment::query()
      ->forModule(OtDepartmentAssignment::MODULE_LEAVE)
      ->where('app_user_id', $me->id)
      ->get(['company', 'dept_code'])
      ->map(fn (OtDepartmentAssignment $assignment) => [
        'company' => $assignment->company,
        'dept_code' => trim((string) $assignment->dept_code),
      ])->unique(fn (array $row) => $row['company'].'|'.$row['dept_code'])->values()->all();

    if ($departments !== []) {
      return ['all' => false, 'departments' => $departments, 'employees' => []];
    }

    $identity = trim((string) ($me->id_thai_hash ?? ''));
    $employeeRows = Employee::active()
      ->when(
        $identity !== '',
        fn ($query) => $query->where('license_id', $identity),
        fn ($query) => $query->where('employee_code', (string) $me->employee_code),
      )
      ->get(['company', 'employee_code', 'dept_code'])
      ->map(fn (Employee $employee) => [
        'company' => $employee->company,
        'employee_code' => $employee->employee_code,
        'dept_code' => trim((string) $employee->dept_code),
      ])
      ->values()->all();

    return ['all' => false, 'departments' => [], 'employees' => $employeeRows];
  }
}
