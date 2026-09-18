<?php

namespace App\Http\Controllers\OtApproval;

use App\Http\Controllers\Controller;
use App\Http\Controllers\OtApproval\Concerns\HandlesOtApprovalAccess;
use App\Http\Requests\OtApproval\BulkDecideLeaveRequestsRequest;
use App\Http\Requests\OtApproval\BulkStoreLeaveRequestsRequest;
use App\Http\Requests\OtApproval\CancelLeaveRequestsRequest;
use App\Http\Requests\OtApproval\DecideLeaveRequest;
use App\Http\Requests\OtApproval\SaveLeaveRequest;
use App\Http\Requests\OtApproval\SubmitLeaveRequestsRequest;
use App\Models\OtApproval\LeaveRequest;
use App\Models\OtApproval\OtDepartmentAssignment;
use App\Support\OtApproval\OtBranchFilter;
use App\Services\OtApproval\LeaveNotificationService;
use App\Services\OtApproval\LeaveRequestWorkflowService;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class LeaveRequestWorkflowController extends Controller
{
  use HandlesOtApprovalAccess;

  public function employees(Request $request, LeaveRequestWorkflowService $workflow): JsonResponse
  {
    if (! $this->hasOtRole(OtDepartmentAssignment::ROLE_FOREMAN, OtDepartmentAssignment::MODULE_LEAVE)) {
      return $this->forbiddenJson();
    }

    $data = $request->validate([
      'company' => ['required', 'string', 'in:'.implode(',', array_keys(OtDepartmentAssignment::COMPANIES))],
      'dept_code' => ['nullable', 'string', 'max:50'],
      'date' => ['nullable', 'date_format:Y-m-d'],
    ]);
    $date = isset($data['date'])
      ? CarbonImmutable::createFromFormat('Y-m-d', $data['date'])->startOfDay()
      : CarbonImmutable::today();

    return response()->json([
      'ok' => true,
      'leave' => $workflow->departmentEmployees(
        $date,
        $data['company'],
        trim((string) ($data['dept_code'] ?? '')),
        $this->workflowScope(OtDepartmentAssignment::ROLE_FOREMAN, OtDepartmentAssignment::MODULE_LEAVE),
      ),
    ]);
  }

  /**
   * ขอลารายคน — สร้างแล้วส่งขออนุมัติในคำสั่งเดียว ไม่ค้างเป็นร่าง
   *
   * เดิมจบแค่ร่าง แล้วต้องกลับไปติ๊กในตารางกดส่งอีกรอบที่แถบล่าง
   * ทำให้คำขอค้างเป็นร่างโดยที่ Foreman เข้าใจว่าส่งไปแล้ว Supervisor จึงไม่เห็น
   * ช่วงวันเดียวขอได้หลายวัน saveDraft จึงคืนมาหลายแถว ต้องส่งให้ครบทุกแถว
   */
  public function store(
    SaveLeaveRequest $request,
    LeaveRequestWorkflowService $workflow,
    LeaveNotificationService $notifier,
  ): JsonResponse {
    if (! $this->hasOtRole(OtDepartmentAssignment::ROLE_FOREMAN, OtDepartmentAssignment::MODULE_LEAVE)) {
      return $this->forbiddenJson();
    }

    $scope = $this->workflowScope(OtDepartmentAssignment::ROLE_FOREMAN, OtDepartmentAssignment::MODULE_LEAVE);
    $requests = $workflow->saveDraft($request->validated(), $this->me(), $scope);
    $ids = $requests->pluck('id')->map(fn ($id) => (int) $id)->all();
    $submitted = $workflow->submitDrafts($ids, $this->me(), $scope);
    $notifier->notifySubmitted($ids, $this->me());

    return response()->json([
      'ok' => true,
      'message' => 'ส่งคำขอลาให้ Supervisor แล้ว '.count($ids).' วัน',
      'requests' => $submitted,
    ]);
  }

  /**
   * ยกเลิกคำขอที่ยังไม่มีการตัดสิน — เก็บแถวไว้เป็นประวัติ ไม่ลบทิ้ง
   */
  public function cancel(
    CancelLeaveRequestsRequest $request,
    LeaveRequestWorkflowService $workflow,
  ): JsonResponse {
    if (! $this->hasOtRole(OtDepartmentAssignment::ROLE_FOREMAN, OtDepartmentAssignment::MODULE_LEAVE)) {
      return $this->forbiddenJson();
    }

    $cancelled = $workflow->cancelRequests(
      $request->validated('request_ids'),
      (string) $request->validated('reason'),
      $this->me(),
      $this->workflowScope(OtDepartmentAssignment::ROLE_FOREMAN, OtDepartmentAssignment::MODULE_LEAVE),
    );

    return response()->json([
      'ok' => true,
      'message' => 'ยกเลิกคำขอลาแล้ว '.$cancelled.' รายการ',
    ]);
  }

  public function submit(
    SubmitLeaveRequestsRequest $request,
    LeaveRequestWorkflowService $workflow,
    LeaveNotificationService $notifier,
  ): JsonResponse {
    if (! $this->hasOtRole(OtDepartmentAssignment::ROLE_FOREMAN, OtDepartmentAssignment::MODULE_LEAVE)) {
      return $this->forbiddenJson();
    }

    $ids = $request->validated('request_ids');
    $submitted = $workflow->submitDrafts(
      $ids,
      $this->me(),
      $this->workflowScope(OtDepartmentAssignment::ROLE_FOREMAN, OtDepartmentAssignment::MODULE_LEAVE),
    );
    $notifier->notifySubmitted($ids, $this->me());

    return response()->json(['ok' => true, 'message' => 'ส่งคำขอลาให้ Supervisor แล้ว', 'requests' => $submitted]);
  }

  /**
   * "ขอลาทั้งหมด" — สร้างแล้วส่งขออนุมัติในคำสั่งเดียว ไม่ค้างเป็นร่าง
   * คนที่ติดกฎ (เช่นมีคำขอวันนั้นแล้ว) จะถูกข้ามพร้อมเหตุผล ไม่ทำให้ทั้งชุดล้ม
   * เหมือน bulkStore ของหน้าขอ OT
   */
  public function bulkStore(
    BulkStoreLeaveRequestsRequest $request,
    LeaveRequestWorkflowService $workflow,
    LeaveNotificationService $notifier,
  ): JsonResponse {
    if (! $this->hasOtRole(OtDepartmentAssignment::ROLE_FOREMAN, OtDepartmentAssignment::MODULE_LEAVE)) {
      return $this->forbiddenJson();
    }

    $scope = $this->workflowScope(OtDepartmentAssignment::ROLE_FOREMAN, OtDepartmentAssignment::MODULE_LEAVE);
    $company = $request->validated('company');
    $deptCode = trim((string) $request->validated('dept_code'));

    $created = [];
    $skipped = [];

    foreach ($request->validated('items') as $item) {
      try {
        $drafts = $workflow->saveDraft([
          'company' => $company,
          'dept_code' => $deptCode,
          'employee_code' => $item['employee_code'],
          'leave_type' => $item['leave_type'],
          'start_date' => $item['start_date'],
          'end_date' => $item['end_date'],
          'note' => $item['note'] ?? null,
        ], $this->me(), $scope);

        foreach ($drafts as $draft) {
          $created[] = (int) $draft->id;
        }
      } catch (ValidationException $exception) {
        $skipped[] = [
          'employee_code' => $item['employee_code'],
          'reason' => collect($exception->errors())->flatten()->first() ?: 'สร้างคำขอไม่สำเร็จ',
        ];
      }
    }

    if ($created === []) {
      return response()->json([
        'ok' => false,
        'message' => 'ไม่มีรายการที่สร้างได้',
        'created_count' => 0,
        'skipped' => $skipped,
      ]);
    }

    $workflow->submitDrafts($created, $this->me(), $scope);
    // แจ้ง Supervisor หลัง transaction ปิดแล้ว เหมือนเส้นทางส่งปกติ
    $notifier->notifySubmitted($created, $this->me());

    $message = 'ส่งขออนุมัติแล้ว '.count($created).' รายการ';
    if ($skipped !== []) {
      $message .= ' · ข้าม '.count($skipped).' คน';
    }

    return response()->json([
      'ok' => true,
      'message' => $message,
      'created_count' => count($created),
      'skipped' => $skipped,
    ]);
  }

  /* `destroy()` เดิมถูกแทนที่ด้วย `cancel()` — การลบจริงพาประวัติหายตาม cascadeOnDelete
     จนตรวจย้อนไม่ได้ว่าเคยส่งคำขอนั้นให้ Supervisor */

  public function queue(Request $request, LeaveRequestWorkflowService $workflow): JsonResponse
  {
    if (! $this->hasOtRole(OtDepartmentAssignment::ROLE_SUPERVISOR, OtDepartmentAssignment::MODULE_LEAVE)) {
      return $this->forbiddenJson();
    }

    $data = $request->validate([
      'filter' => ['nullable', 'string', 'in:pending,approved,rejected,cancelled,all'],
      'date' => ['nullable', 'date_format:Y-m-d'],
      'page' => ['nullable', 'integer', 'min:1'],
      'per_page' => ['nullable', 'integer', 'min:1', 'max:200'],
      // ค้นหารหัสพนักงาน/ชื่อ — ต้องกรองที่เซิร์ฟเวอร์เพราะคิวแบ่งหน้า
      'q' => ['nullable', 'string', 'max:60'],
      // กรองสาขา (โรงงาน / โรงงาน-พม่า) — ต้องกรองที่เซิร์ฟเวอร์ด้วยเหตุผลเดียวกับ q
      'branch' => ['nullable', 'string', 'max:20'],
      // ประเภทการลา — กรองที่เซิร์ฟเวอร์เช่นกัน เพราะคิวแบ่งหน้า
      'leave_type' => ['nullable', 'string', 'max:40'],
    ]);
    $date = isset($data['date'])
      ? CarbonImmutable::createFromFormat('Y-m-d', $data['date'])->startOfDay()
      : null;

    $result = $workflow->approvalQueuePage(
      $this->workflowScope(OtDepartmentAssignment::ROLE_SUPERVISOR, OtDepartmentAssignment::MODULE_LEAVE),
      (string) ($data['filter'] ?? 'pending'),
      $date,
      (int) ($data['page'] ?? 1),
      (int) ($data['per_page'] ?? 20),
      (string) ($data['q'] ?? ''),
      (string) ($data['branch'] ?? OtBranchFilter::ALL),
      (string) ($data['leave_type'] ?? 'all'),
    );

    return response()->json(['ok' => true] + $result);
  }

  public function decide(
    DecideLeaveRequest $request,
    LeaveRequest $leaveRequest,
    LeaveRequestWorkflowService $workflow,
    LeaveNotificationService $notifier,
  ): JsonResponse {
    if (! $this->hasOtRole(OtDepartmentAssignment::ROLE_SUPERVISOR, OtDepartmentAssignment::MODULE_LEAVE)) {
      return $this->forbiddenJson();
    }

    $updated = $workflow->decide(
      $leaveRequest,
      $this->me(),
      $this->workflowScope(OtDepartmentAssignment::ROLE_SUPERVISOR, OtDepartmentAssignment::MODULE_LEAVE),
      $request->validated('decision'),
      $request->validated('note'),
    );
    $notifier->notifyDecided($updated, $this->me());

    return response()->json(['ok' => true, 'message' => 'บันทึกผลอนุมัติแล้ว', 'request' => $workflow->requestPayload($updated)]);
  }

  public function bulkDecide(
    BulkDecideLeaveRequestsRequest $request,
    LeaveRequestWorkflowService $workflow,
    LeaveNotificationService $notifier,
  ): JsonResponse {
    if (! $this->hasOtRole(OtDepartmentAssignment::ROLE_SUPERVISOR, OtDepartmentAssignment::MODULE_LEAVE)) {
      return $this->forbiddenJson();
    }

    $result = $workflow->decideMany(
      $request->validated('request_ids'),
      $this->me(),
      $this->workflowScope(OtDepartmentAssignment::ROLE_SUPERVISOR, OtDepartmentAssignment::MODULE_LEAVE),
      $request->validated('decision'),
      $request->validated('note'),
    );
    foreach ($result['updated'] as $updated) {
      $notifier->notifyDecided($updated, $this->me());
    }

    return response()->json([
      'ok' => $result['updated'] !== [],
      'message' => 'ดำเนินการแล้ว '.count($result['updated']).' รายการ'.($result['skipped'] ? ' · ข้าม '.count($result['skipped']).' รายการ' : ''),
      'requests' => array_map(fn (LeaveRequest $row) => $workflow->requestPayload($row), $result['updated']),
      'skipped' => $result['skipped'],
    ]);
  }

  private function forbiddenJson(): JsonResponse
  {
    return response()->json(['ok' => false, 'message' => 'คุณไม่มีสิทธิ์ดำเนินการ'], 403);
  }
}
