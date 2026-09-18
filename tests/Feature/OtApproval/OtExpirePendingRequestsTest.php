<?php

namespace Tests\Feature\OtApproval;

use App\Models\OtApproval\OtDepartmentAssignment;
use App\Models\OtApproval\OtRequest;
use App\Models\OtApproval\OtRequestApproval;
use App\Services\OtApproval\OtPendingExpiryService;
use Carbon\CarbonImmutable;
use Tests\Concerns\BuildsOtApprovalSchema;
use Tests\TestCase;

class OtExpirePendingRequestsTest extends TestCase
{
    use BuildsOtApprovalSchema;

    protected function setUp(): void
    {
        parent::setUp();

        $this->buildOtApprovalSchema();
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    public function test_it_rejects_submitted_ot_after_day_23_deadline(): void
    {
        CarbonImmutable::setTestNow('2026-08-24 00:00:00');
        $expired = $this->makeRequest('2026-08-20', OtRequest::APPROVAL_SUBMITTED, 'EXPIRED');
        $nextCycle = $this->makeRequest('2026-08-21', OtRequest::APPROVAL_SUBMITTED, 'NEXTCYCLE');
        $draft = $this->makeRequest('2026-08-20', OtRequest::APPROVAL_DRAFT, 'DRAFT');

        $result = app(OtPendingExpiryService::class)->rejectExpired();

        $this->assertSame(2, $result['checked']);
        $this->assertSame(1, $result['rejected']);
        $this->assertSame([$expired->id], $result['ids']);

        $expired->refresh();
        $this->assertSame(OtRequest::APPROVAL_REJECTED, $expired->approval_status);
        $this->assertSame(OtPendingExpiryService::AUTO_REJECT_NOTE, $expired->decision_note);
        $this->assertNull($expired->decided_by_app_user_id);
        $this->assertSame(OtRequest::EXPORT_NOT_READY, $expired->export_status);

        $this->assertSame(OtRequest::APPROVAL_SUBMITTED, $nextCycle->refresh()->approval_status);
        $this->assertSame(OtRequest::APPROVAL_DRAFT, $draft->refresh()->approval_status);
        $this->assertSame(1, OtRequestApproval::query()->where('ot_request_id', $expired->id)->count());
    }

    public function test_command_runs_the_same_expiry_rule(): void
    {
        CarbonImmutable::setTestNow('2026-08-24 00:00:00');
        $expired = $this->makeRequest('2026-08-20', OtRequest::APPROVAL_SUBMITTED, 'COMMAND');

        $this->artisan('ot-approval:expire-pending')
            ->expectsOutput('Checked 1 submitted OT request(s), auto-rejected 1.')
            ->assertExitCode(0);

        $this->assertSame(OtRequest::APPROVAL_REJECTED, $expired->refresh()->approval_status);
    }

    private function makeRequest(string $workDate, string $status, string $employeeCode): OtRequest
    {
        $request = new OtRequest;
        $request->forceFill([
            'company' => array_key_first(OtDepartmentAssignment::COMPANIES),
            'dept_code' => 'MYDEPT',
            'employee_code' => $employeeCode,
            'employee_name' => 'พนักงานทดสอบ',
            'department_name' => 'แผนกทดสอบ',
            'work_date' => $workDate,
            'ot_type' => 'weekday_after_work',
            'multiplier' => 1.5,
            'requested_start_at' => $workDate.' 18:00:00',
            'requested_end_at' => $workDate.' 20:00:00',
            'requested_hours' => 2,
            'requested_minutes' => 0,
            'approval_status' => $status,
            'attendance_status' => OtRequest::ATTENDANCE_WAITING,
            'export_status' => OtRequest::EXPORT_READY,
            'created_by_app_user_id' => 1,
        ]);

        $request->save();

        return $request;
    }
}
