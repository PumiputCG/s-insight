<?php

namespace Tests\Feature\OtApproval;

use App\Models\Insight\AppUser;
use App\Models\Insight\Employee;
use App\Models\OtApproval\LeaveRequest;
use App\Models\OtApproval\LeaveRequestApproval;
use App\Services\OtApproval\LeaveRequestWorkflowService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class LeaveWorkflowTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['database.connections.mysql_ot_approval' => config('database.connections.sqlite')]);
        DB::purge('mysql_ot_approval');
        CarbonImmutable::setTestNow('2026-08-14 10:00:00');

        Schema::create('app_users', function ($table) {
            $table->id();
            $table->string('employee_code')->nullable();
            $table->string('full_name_th')->nullable();
            $table->string('full_name_en')->nullable();
            $table->string('email')->nullable();
            $table->timestamps();
        });

        Schema::create('employees', function ($table) {
            $table->id();
            $table->string('company');
            $table->string('employee_code');
            $table->string('license_id')->nullable();
            $table->string('title')->nullable();
            $table->string('name_th')->nullable();
            $table->string('surname_th')->nullable();
            $table->string('name_en')->nullable();
            $table->string('job_code')->nullable();
            $table->string('job_th')->nullable();
            $table->string('job_en')->nullable();
            $table->string('dept_code')->nullable();
            $table->string('dept_th')->nullable();
            $table->string('dept_en')->nullable();
            $table->date('hire_date')->nullable();
            $table->date('pending_resign_date')->nullable();
            $table->date('manual_resign_date')->nullable();
            $table->date('resign_date')->nullable();
            $table->string('emp_status')->default('1');
            $table->timestamps();
        });

        $ot = Schema::connection('mysql_ot_approval');
        $ot->create('leave_requests', function ($table) {
            $table->id();
            $table->uuid('batch_uuid');
            $table->string('company');
            $table->string('dept_code')->default('');
            $table->string('employee_code');
            $table->string('employee_name')->nullable();
            $table->string('position_name')->nullable();
            $table->string('department_name')->nullable();
            $table->date('leave_date');
            $table->date('range_start');
            $table->date('range_end');
            $table->string('leave_type');
            $table->string('bplus_stamp_type_key');
            $table->string('deduction_agreement_code');
            $table->string('shift_code');
            $table->string('swipe_character_code');
            $table->string('approval_method');
            $table->decimal('leave_quantity', 8, 2);
            $table->text('note')->nullable();
            $table->string('approval_status');
            $table->string('export_status');
            $table->unsignedBigInteger('created_by_app_user_id');
            $table->string('created_by_employee_code')->nullable();
            $table->unsignedBigInteger('decided_by_app_user_id')->nullable();
            $table->string('decided_by_employee_code')->nullable();
            $table->text('decision_note')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancel_reason', 500)->nullable();
            $table->unsignedBigInteger('cancelled_by_app_user_id')->nullable();
            $table->string('cancelled_by_employee_code', 20)->nullable();
            $table->unsignedTinyInteger('active_slot')->nullable()->default(1);
            $table->timestamps();
            $table->unique(['company', 'employee_code', 'leave_date', 'leave_type']);
        });
        $ot->create('leave_request_approvals', function ($table) {
            $table->id();
            $table->unsignedBigInteger('leave_request_id');
            $table->string('decision');
            $table->unsignedBigInteger('actor_app_user_id');
            $table->string('actor_employee_code')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    public function test_full_day_range_is_split_into_one_bplus_row_per_day_without_attendance(): void
    {
        $actor = AppUser::create(['employee_code' => 'FM001', 'full_name_th' => 'Foreman ทดสอบ']);
        $employee = Employee::create([
            'company' => 'SUPAVUT_INDUSTRY',
            'employee_code' => '71001',
            'title' => 'นาย',
            'name_th' => 'พนักงาน',
            'surname_th' => 'ทดสอบ',
            'job_th' => 'Operator',
            'dept_code' => 'SPV001',
            'dept_th' => 'Assembly',
            'hire_date' => '2026-01-01',
            'emp_status' => '1',
        ]);
        $scope = ['all' => false, 'departments' => [[
            'company' => $employee->company,
            'dept_code' => $employee->dept_code,
        ]], 'employees' => []];

        $rows = app(LeaveRequestWorkflowService::class)->saveDraft([
            'company' => $employee->company,
            'dept_code' => $employee->dept_code,
            'employee_code' => $employee->employee_code,
            'leave_type' => 'section_75',
            'start_date' => '2026-08-13',
            'end_date' => '2026-08-14',
            'note' => 'ทดสอบลาเต็มวัน',
        ], $actor, $scope);

        $this->assertCount(2, $rows);
        $this->assertSame(['2026-08-13', '2026-08-14'], $rows->pluck('leave_date')->map->format('Y-m-d')->all());
        $this->assertTrue($rows->every(fn (LeaveRequest $row) => $row->approval_status === LeaveRequest::APPROVAL_DRAFT));
        $this->assertTrue($rows->every(fn (LeaveRequest $row) => $row->deduction_agreement_code === '020008(1)'));
    }

    public function test_supervisor_approval_passes_immediately_and_becomes_export_ready(): void
    {
        $supervisor = AppUser::create(['employee_code' => 'SV001', 'full_name_th' => 'Supervisor ทดสอบ']);
        $request = $this->makeRequest('submitted');

        $updated = app(LeaveRequestWorkflowService::class)->decide(
            $request,
            $supervisor,
            ['all' => true, 'departments' => [], 'employees' => []],
            LeaveRequest::APPROVAL_APPROVED,
            'อนุมัติ',
        );

        $this->assertSame(LeaveRequest::APPROVAL_APPROVED, $updated->approval_status);
        $this->assertSame(LeaveRequest::EXPORT_READY, $updated->export_status);
        $this->assertSame('ผ่าน', app(LeaveRequestWorkflowService::class)->requestPayload($updated)['status_label']);
        $this->assertSame(1, LeaveRequestApproval::query()->count());
    }

    public function test_bulk_rejection_applies_the_required_reason_to_every_selected_request(): void
    {
        $supervisor = AppUser::create(['employee_code' => 'SV001', 'full_name_th' => 'Supervisor ทดสอบ']);
        $first = $this->makeRequest('submitted', '71001');
        $second = $this->makeRequest('submitted', '71002');

        $result = app(LeaveRequestWorkflowService::class)->decideMany(
            [$first->id, $second->id],
            $supervisor,
            ['all' => true, 'departments' => [], 'employees' => []],
            LeaveRequest::APPROVAL_REJECTED,
            'บริษัทไม่หยุดสายการผลิตในวันนี้',
        );

        $this->assertCount(2, $result['updated']);
        $this->assertSame([], $result['skipped']);
        $this->assertTrue(LeaveRequest::query()->get()->every(
            fn (LeaveRequest $row) => $row->approval_status === LeaveRequest::APPROVAL_REJECTED
              && $row->decision_note === 'บริษัทไม่หยุดสายการผลิตในวันนี้',
        ));
    }

    public function test_supervisor_can_change_an_approved_leave_to_rejected(): void
    {
        $supervisor = AppUser::create(['employee_code' => 'SV001', 'full_name_th' => 'Supervisor ทดสอบ']);
        $request = $this->makeRequest('approved');

        $updated = app(LeaveRequestWorkflowService::class)->decide(
            $request,
            $supervisor,
            ['all' => true, 'departments' => [], 'employees' => []],
            LeaveRequest::APPROVAL_REJECTED,
            'พนักงานไม่ต้องการใช้สิทธิ์ลานี้แล้ว',
        );

        $this->assertSame(LeaveRequest::APPROVAL_REJECTED, $updated->approval_status);
        $this->assertSame(LeaveRequest::EXPORT_NOT_READY, $updated->export_status);
        $this->assertSame('พนักงานไม่ต้องการใช้สิทธิ์ลานี้แล้ว', $updated->decision_note);
        $this->assertDatabaseHas('leave_request_approvals', [
            'leave_request_id' => $request->id,
            'decision' => LeaveRequest::APPROVAL_REJECTED,
            'actor_employee_code' => 'SV001',
            'note' => 'พนักงานไม่ต้องการใช้สิทธิ์ลานี้แล้ว',
        ], 'mysql_ot_approval');
    }

    public function test_supervisor_can_change_a_rejected_leave_to_approved(): void
    {
        $supervisor = AppUser::create(['employee_code' => 'SV001', 'full_name_th' => 'Supervisor ทดสอบ']);
        $request = $this->makeRequest('rejected');

        $updated = app(LeaveRequestWorkflowService::class)->decide(
            $request,
            $supervisor,
            ['all' => true, 'departments' => [], 'employees' => []],
            LeaveRequest::APPROVAL_APPROVED,
            'ตรวจสอบแล้ว อนุมัติได้',
        );

        $this->assertSame(LeaveRequest::APPROVAL_APPROVED, $updated->approval_status);
        $this->assertSame(LeaveRequest::EXPORT_READY, $updated->export_status);
        $this->assertSame('ตรวจสอบแล้ว อนุมัติได้', $updated->decision_note);
        $this->assertDatabaseHas('leave_request_approvals', [
            'leave_request_id' => $request->id,
            'decision' => LeaveRequest::APPROVAL_APPROVED,
            'actor_employee_code' => 'SV001',
            'note' => 'ตรวจสอบแล้ว อนุมัติได้',
        ], 'mysql_ot_approval');
    }

    public function test_leave_75_can_be_drafted_in_advance_for_future_dates(): void
    {
        $actor = AppUser::create(['employee_code' => 'FM001', 'full_name_th' => 'Foreman ทดสอบ']);
        $employee = $this->makeEmployee();
        $scope = ['all' => false, 'departments' => [[
            'company' => $employee->company,
            'dept_code' => $employee->dept_code,
        ]], 'employees' => []];

        $rows = app(LeaveRequestWorkflowService::class)->saveDraft([
            'company' => $employee->company,
            'dept_code' => $employee->dept_code,
            'employee_code' => $employee->employee_code,
            'leave_type' => 'section_75',
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-02',
            'note' => 'ลาล่วงหน้า',
        ], $actor, $scope);

        $this->assertSame(['2026-09-01', '2026-09-02'], $rows->pluck('leave_date')->map->format('Y-m-d')->all());
    }

    /**
     * ยกเลิกแล้วต้องเก็บแถวไว้เป็นประวัติ ไม่ลบทิ้ง
     *
     * ของเดิมใช้ `deleteDrafts()` ลบแถวจริง ซึ่งตาราง audit ตั้ง cascadeOnDelete ไว้
     * ประวัติจึงหายพร้อมกันทั้งหมด ตรวจย้อนไม่ได้ว่าเคยส่งคำขอนั้นให้ Supervisor
     */
    public function test_cancelling_keeps_the_row_and_writes_an_audit_trail(): void
    {
        $scope = ['all' => true, 'departments' => [], 'employees' => []];
        $actor = AppUser::create(['employee_code' => 'FM001', 'full_name_th' => 'Foreman ทดสอบ']);
        $submitted = $this->makeRequest('submitted', '71002');

        $count = app(LeaveRequestWorkflowService::class)
            ->cancelRequests([$submitted->id], 'พนักงานยกเลิกวันลาเอง', $actor, $scope);

        $this->assertSame(1, $count);

        $row = LeaveRequest::find($submitted->id);
        $this->assertNotNull($row, 'แถวต้องยังอยู่ ไม่ใช่ถูกลบทิ้ง');
        $this->assertSame(LeaveRequest::APPROVAL_CANCELLED, $row->approval_status);
        $this->assertSame('พนักงานยกเลิกวันลาเอง', $row->cancel_reason);
        $this->assertSame('FM001', $row->cancelled_by_employee_code);
        $this->assertNotNull($row->cancelled_at);
        // ปิดทางส่งออก ไม่งั้นคำขอที่ยกเลิกแล้วยังหลุดเข้าไฟล์ลา 75 ได้
        $this->assertSame(LeaveRequest::EXPORT_NOT_READY, $row->export_status);

        $audit = LeaveRequestApproval::where('leave_request_id', $submitted->id)
            ->where('decision', 'cancelled')
            ->first();
        $this->assertNotNull($audit);
        $this->assertSame('พนักงานยกเลิกวันลาเอง', $audit->note);
    }

    /** ที่ Supervisor ตัดสินไปแล้วต้องยกเลิกไม่ได้ — ต้องให้ Supervisor เป็นคนแก้ ไม่ใช่ Foreman ถอยเอง */
    public function test_a_decided_request_cannot_be_cancelled(): void
    {
        $actor = AppUser::create(['employee_code' => 'FM001', 'full_name_th' => 'Foreman ทดสอบ']);
        $approved = $this->makeRequest('approved', '71003');

        $this->expectException(ValidationException::class);
        app(LeaveRequestWorkflowService::class)->cancelRequests(
            [$approved->id],
            'อยากถอย',
            $actor,
            ['all' => true, 'departments' => [], 'employees' => []],
        );
    }

    public function test_cancelling_outside_the_foreman_scope_is_rejected(): void
    {
        $actor = AppUser::create(['employee_code' => 'FM001', 'full_name_th' => 'Foreman ทดสอบ']);
        $draft = $this->makeRequest('draft', '71001');

        $this->expectException(ValidationException::class);
        app(LeaveRequestWorkflowService::class)->cancelRequests([$draft->id], 'ทดสอบสิทธิ์', $actor, [
            'all' => false,
            'departments' => [['company' => 'SUPAVUT_INDUSTRY', 'dept_code' => 'SPV999']],
            'employees' => [],
        ]);
    }

    /** เหตุผลบังคับกรอก เพราะเก็บไว้เป็นประวัติให้ตรวจย้อนได้ว่าใครยกเลิกเพราะอะไร */
    public function test_cancelling_without_a_reason_is_rejected(): void
    {
        $actor = AppUser::create(['employee_code' => 'FM001', 'full_name_th' => 'Foreman ทดสอบ']);
        $draft = $this->makeRequest('draft', '71001');

        $this->expectException(ValidationException::class);
        app(LeaveRequestWorkflowService::class)->cancelRequests(
            [$draft->id],
            '   ',
            $actor,
            ['all' => true, 'departments' => [], 'employees' => []],
        );
    }

    public function test_payload_marks_an_expired_submitted_leave_as_not_decidable(): void
    {
        CarbonImmutable::setTestNow('2026-08-24 00:00:00');
        $request = $this->makeRequest('submitted');

        $payload = app(LeaveRequestWorkflowService::class)->requestPayload($request);

        $this->assertFalse($payload['can_decide']);
    }

    public function test_approval_queue_includes_localized_employee_names_from_employee_master(): void
    {
        AppUser::create(['employee_code' => 'FM001', 'full_name_th' => 'Foreman ทดสอบ']);
        $this->makeEmployee();
        $this->makeRequest('submitted');

        $row = app(LeaveRequestWorkflowService::class)
          ->approvalQueue(['all' => true, 'departments' => [], 'employees' => []], 'pending')[0];

        $this->assertSame('นายพนักงาน ทดสอบ', $row['employee_name_th']);
        $this->assertSame('Test Employee', $row['employee_name_en']);
        $this->assertSame('Test Employee', $row['employee_name_my']);
    }

    private function makeEmployee(string $employeeCode = '71001'): Employee
    {
        return Employee::create([
            'company' => 'SUPAVUT_INDUSTRY',
            'employee_code' => $employeeCode,
            'title' => 'นาย',
            'name_th' => 'พนักงาน',
            'surname_th' => 'ทดสอบ',
            'name_en' => 'Test Employee',
            'job_th' => 'Operator',
            'dept_code' => 'SPV001',
            'dept_th' => 'Assembly',
            'hire_date' => '2026-01-01',
            'emp_status' => '1',
        ]);
    }

    private function makeRequest(string $status, string $employeeCode = '71001'): LeaveRequest
    {
        return LeaveRequest::create([
            'batch_uuid' => fake()->uuid(),
            'company' => 'SUPAVUT_INDUSTRY',
            'dept_code' => 'SPV001',
            'employee_code' => $employeeCode,
            'employee_name' => 'พนักงาน '.$employeeCode,
            'position_name' => 'Operator',
            'department_name' => 'Assembly',
            'leave_date' => '2026-08-14',
            'range_start' => '2026-08-14',
            'range_end' => '2026-08-14',
            'leave_type' => 'section_75',
            'bplus_stamp_type_key' => '20030',
            'deduction_agreement_code' => '020008(1)',
            'shift_code' => '00',
            'swipe_character_code' => '0',
            'approval_method' => '1',
            'leave_quantity' => '1',
            'approval_status' => $status,
            'export_status' => LeaveRequest::EXPORT_NOT_READY,
            'created_by_app_user_id' => 1,
            'created_by_employee_code' => 'FM001',
            'submitted_at' => now(),
        ]);
    }
}
