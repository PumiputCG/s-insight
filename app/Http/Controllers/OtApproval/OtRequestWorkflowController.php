<?php

namespace App\Http\Controllers\OtApproval;

use App\Http\Controllers\Controller;
use App\Http\Controllers\OtApproval\Concerns\HandlesOtApprovalAccess;
use App\Http\Requests\OtApproval\BulkDecideOtRequestsRequest;
use App\Http\Requests\OtApproval\BulkStoreOtRequestsRequest;
use App\Http\Requests\OtApproval\CancelOtRequestsRequest;
use App\Http\Requests\OtApproval\DecideOtRequestRequest;
use App\Http\Requests\OtApproval\SaveOtRequestRequest;
use App\Http\Requests\OtApproval\SubmitOtRequestsRequest;
use App\Models\OtApproval\OtDepartmentAssignment;
use App\Models\OtApproval\OtRequest;
use App\Services\OtApproval\OtNotificationService;
use App\Services\OtApproval\OtRequestWorkflowService;
use App\Support\OtApproval\OtBranchFilter;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class OtRequestWorkflowController extends Controller
{
    use HandlesOtApprovalAccess;

    public function employees(Request $request, OtRequestWorkflowService $workflow): JsonResponse
    {
        if (! $this->hasOtRole(OtDepartmentAssignment::ROLE_FOREMAN)) {
            return $this->forbiddenJson();
        }

        $data = $request->validate([
            'company' => ['required', 'string', 'in:'.implode(',', array_keys(OtDepartmentAssignment::COMPANIES))],
            'dept_code' => ['nullable', 'string', 'max:50'],
            'date' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:today'],
        ]);
        $date = isset($data['date'])
            ? CarbonImmutable::createFromFormat('Y-m-d', $data['date'])->startOfDay()
            : CarbonImmutable::today();

        return response()->json([
            'ok' => true,
            'attendance' => $workflow->departmentEmployees(
                $date,
                $data['company'],
                trim((string) ($data['dept_code'] ?? '')),
                $this->workflowScope(OtDepartmentAssignment::ROLE_FOREMAN),
            ),
        ]);
    }

    /**
     * ขอ OT รายคน — สร้างแล้วส่งขออนุมัติในคำสั่งเดียว ไม่ค้างเป็นร่าง
     *
     * เดิมจบแค่ร่าง แล้วต้องกลับไปติ๊กในตารางกดส่งอีกรอบที่แถบล่าง
     * ทำให้คำขอค้างเป็นร่างโดยที่ Foreman เข้าใจว่าส่งไปแล้ว Supervisor จึงไม่เห็น
     */
    public function store(
        SaveOtRequestRequest $request,
        OtRequestWorkflowService $workflow,
        OtNotificationService $notifier,
    ): JsonResponse {
        if (! $this->hasOtRole(OtDepartmentAssignment::ROLE_FOREMAN)) {
            return $this->forbiddenJson();
        }

        $scope = $this->workflowScope(OtDepartmentAssignment::ROLE_FOREMAN);
        $otRequest = $workflow->saveDraft($request->validated(), $this->me(), $scope);
        $workflow->submitDrafts([$otRequest->id], $this->me(), $scope);

        // แจ้ง Supervisor หลัง transaction ปิดแล้ว เพื่อไม่ให้ส่งเมลออกไปแล้วโรลแบ็ก
        $notifier->notifySubmitted([$otRequest->id], $this->me());

        return response()->json([
            'ok' => true,
            'message' => 'ส่งคำขอ OT ให้ Supervisor แล้ว',
            'request' => $workflow->requestPayload($otRequest->refresh()),
        ]);
    }

    /**
     * ยกเลิกคำขอที่ยังไม่มีการตัดสิน — เก็บแถวไว้เป็นประวัติ ไม่ลบทิ้ง
     */
    public function cancel(
        CancelOtRequestsRequest $request,
        OtRequestWorkflowService $workflow,
    ): JsonResponse {
        if (! $this->hasOtRole(OtDepartmentAssignment::ROLE_FOREMAN)) {
            return $this->forbiddenJson();
        }

        $cancelled = $workflow->cancelRequests(
            $request->validated('request_ids'),
            (string) $request->validated('reason'),
            $this->me(),
            $this->workflowScope(OtDepartmentAssignment::ROLE_FOREMAN),
        );

        return response()->json([
            'ok' => true,
            'message' => 'ยกเลิกคำขอ OT แล้ว '.$cancelled.' รายการ',
        ]);
    }

    public function submit(
        SubmitOtRequestsRequest $request,
        OtRequestWorkflowService $workflow,
        OtNotificationService $notifier,
    ): JsonResponse {
        if (! $this->hasOtRole(OtDepartmentAssignment::ROLE_FOREMAN)) {
            return $this->forbiddenJson();
        }

        $requestIds = $request->validated('request_ids');

        $submitted = $workflow->submitDrafts(
            $requestIds,
            $this->me(),
            $this->workflowScope(OtDepartmentAssignment::ROLE_FOREMAN),
        );

        // แจ้งเตือน Supervisor หลัง transaction ปิดแล้ว เพื่อไม่ให้ส่งเมลออกไปแล้วโรลแบ็ก
        $notifier->notifySubmitted($requestIds, $this->me());

        return response()->json([
            'ok' => true,
            'message' => 'ส่งคำขอให้ Supervisor แล้ว',
            'requests' => $submitted,
        ]);
    }

    /**
     * ขอ OT ทั้งกะ — สร้างคำขอทุกคนที่ติ๊กไว้แล้วส่งให้ Supervisor ในครั้งเดียว
     *
     * Manager กำหนดให้จบในปุ่มเดียว ไม่มีขั้นบันทึกร่างให้กลับมาตรวจ
     * เพราะตรวจในตารางขั้นที่ 2 ไปแล้วก่อนกดส่ง
     *
     * แถวที่ทำไม่ได้ (ช่วงเวลา/จำนวนไม่ถูกต้อง หรือมีคำขออยู่แล้ว)
     * จะถูกข้ามและรายงานกลับ ไม่ทำให้ทั้งล็อตล้ม
     */
    public function bulkStore(
        BulkStoreOtRequestsRequest $request,
        OtRequestWorkflowService $workflow,
        OtNotificationService $notifier,
    ): JsonResponse {
        if (! $this->hasOtRole(OtDepartmentAssignment::ROLE_FOREMAN)) {
            return $this->forbiddenJson();
        }

        $scope = $this->workflowScope(OtDepartmentAssignment::ROLE_FOREMAN);
        $company = $request->validated('company');
        $deptCode = trim((string) $request->validated('dept_code'));
        $workDate = $request->validated('work_date');

        $created = [];
        $skipped = [];

        foreach ($request->validated('items') as $item) {
            try {
                $draft = $workflow->saveDraft([
                    'company' => $company,
                    'dept_code' => $deptCode,
                    'work_date' => $workDate,
                    'employee_code' => $item['employee_code'],
                    'ot_type' => $item['ot_type'],
                    'start_time' => $item['start_time'],
                    'end_time' => $item['end_time'],
                    'requested_hours' => $item['requested_hours'] ?? null,
                    'requested_minutes' => $item['requested_minutes'] ?? null,
                    'note' => $item['note'] ?? null,
                ], $this->me(), $scope);

                $created[] = (int) $draft->id;
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
            $message .= ' · ข้าม '.count($skipped).' รายการ';
        }

        return response()->json([
            'ok' => true,
            'message' => $message,
            'created_count' => count($created),
            'skipped' => $skipped,
        ]);
    }

    public function queue(Request $request, OtRequestWorkflowService $workflow): JsonResponse
    {
        if (! $this->hasOtRole(OtDepartmentAssignment::ROLE_SUPERVISOR)) {
            return $this->forbiddenJson();
        }

        $data = $request->validate([
            'filter' => ['nullable', 'string', 'in:pending,approved,rejected,cancelled,all'],
            'date' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:today'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:200'],
            'shift' => ['nullable', 'string', 'max:40'],
            // ค้นหารหัสพนักงาน/ชื่อ — ต้องกรองที่เซิร์ฟเวอร์เพราะคิวแบ่งหน้า
            'q' => ['nullable', 'string', 'max:60'],
            // กรองสาขา (โรงงาน / โรงงาน-พม่า) — ต้องกรองที่เซิร์ฟเวอร์ด้วยเหตุผลเดียวกับ q
            'branch' => ['nullable', 'string', 'max:20'],
            // ประเภท OT — กรองที่เซิร์ฟเวอร์เช่นกัน เพราะคิวแบ่งหน้า
            'ot_type' => ['nullable', 'string', 'max:40'],
        ]);

        // ไม่ส่ง date มา = ดูทุกวันเหมือนเดิม กันงานค้างจากวันก่อน ๆ หลุดสายตา
        $workDate = isset($data['date'])
            ? CarbonImmutable::createFromFormat('Y-m-d', $data['date'])->startOfDay()
            : null;

        $result = $workflow->approvalQueuePage(
            $this->workflowScope(OtDepartmentAssignment::ROLE_SUPERVISOR),
            $data['filter'] ?? 'pending',
            $workDate,
            (int) ($data['page'] ?? 1),
            (int) ($data['per_page'] ?? 20),
            (string) ($data['shift'] ?? 'all'),
            (string) ($data['q'] ?? ''),
            (string) ($data['branch'] ?? OtBranchFilter::ALL),
            (string) ($data['ot_type'] ?? 'all'),
        );

        return response()->json(['ok' => true] + $result);
    }

    public function decide(
        DecideOtRequestRequest $request,
        OtRequest $otRequest,
        OtRequestWorkflowService $workflow,
        OtNotificationService $notifier,
    ): JsonResponse {
        if (! $this->hasOtRole(OtDepartmentAssignment::ROLE_SUPERVISOR)) {
            return $this->forbiddenJson();
        }

        $updated = $workflow->decide(
            $otRequest,
            $this->me(),
            $this->workflowScope(OtDepartmentAssignment::ROLE_SUPERVISOR),
            $request->validated('decision'),
            $request->validated('note'),
        );

        // เด้งกระดิ่งกลับหา Foreman (ไม่ส่งอีเมล ตามที่ Manager กำหนด เพื่อกันแจ้งเตือนถี่)
        $notifier->notifyDecided($updated, $this->me());

        // พร้อมออก V74 แล้ว = admin ต้องรู้ว่ามีไฟล์ให้โหลดส่ง HR (โดยเฉพาะรายการย้อนหลัง)
        if ($updated->export_status === OtRequest::EXPORT_READY) {
            $notifier->notifyDownloadReady($updated);
        }

        return response()->json([
            'ok' => true,
            'message' => $updated->approval_status === OtRequest::APPROVAL_APPROVED
                ? 'อนุมัติคำขอ OT แล้ว'
                : 'ไม่อนุมัติคำขอ OT แล้ว',
            'request' => $workflow->requestPayload($updated),
        ]);
    }

    /** อนุมัติ/ไม่อนุมัติหลายคำขอที่ติ๊กเลือกไว้ในตารางรวดเดียว */
    public function bulkDecide(
        BulkDecideOtRequestsRequest $request,
        OtRequestWorkflowService $workflow,
        OtNotificationService $notifier,
    ): JsonResponse {
        if (! $this->hasOtRole(OtDepartmentAssignment::ROLE_SUPERVISOR)) {
            return $this->forbiddenJson();
        }

        $decision = $request->validated('decision');

        $result = $workflow->decideMany(
            $request->validated('request_ids'),
            $this->me(),
            $this->workflowScope(OtDepartmentAssignment::ROLE_SUPERVISOR),
            $decision,
            $request->validated('note'),
        );

        // เด้งกระดิ่งกลับหา Foreman หลัง transaction ของแต่ละรายการปิดแล้ว (ไม่ส่งอีเมล)
        foreach ($result['updated'] as $updated) {
            $notifier->notifyDecided($updated, $this->me());

            if ($updated->export_status === OtRequest::EXPORT_READY) {
                $notifier->notifyDownloadReady($updated);
            }
        }

        $count = count($result['updated']);
        $verb = $decision === OtRequest::APPROVAL_APPROVED ? 'อนุมัติ' : 'ไม่อนุมัติ';
        $message = $count > 0
            ? $verb.'คำขอ OT แล้ว '.$count.' รายการ'
            : 'ไม่มีรายการที่ดำเนินการได้';

        if ($result['skipped'] !== []) {
            $message .= ' · ข้าม '.count($result['skipped']).' รายการ';
        }

        return response()->json([
            'ok' => $count > 0,
            'message' => $message,
            'approved_count' => $count,
            'skipped' => $result['skipped'],
            'requests' => array_map(
                fn (OtRequest $updated) => $workflow->requestPayload($updated),
                $result['updated'],
            ),
        ]);
    }

    private function forbiddenJson(): JsonResponse
    {
        return response()->json(['ok' => false, 'message' => 'คุณไม่มีสิทธิ์ดำเนินการ'], 403);
    }
}
