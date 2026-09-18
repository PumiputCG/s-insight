<?php

namespace Tests\Feature\OtApproval;

use App\Models\Insight\AppUser;
use App\Models\OtApproval\OtDepartmentAssignment;
use App\Models\OtApproval\OtRequest;
use App\Models\OtApproval\OtRequestApproval;
use App\Services\OtApproval\BplusAttendanceService;
use App\Services\OtApproval\OtRequestWorkflowService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * อนุมัติหลายรายการรวดเดียว (ติ๊กเลือกในตารางแล้วกดอนุมัติ)
 *
 * โฟกัสที่ "ความทนทานของทั้งล็อต": รายการที่ทำไม่ได้ต้องถูกข้ามและรายงานกลับ
 * ไม่ใช่โยน exception ทิ้งทั้งล็อต ส่วนเส้นทางอนุมัติสำเร็จต้องพึ่งข้อมูลสแกน
 * จาก Bplus จึงทดสอบที่นี่ไม่ได้
 *
 * โปรเจคนี้ test DB เป็น sqlite :memory: และไม่ได้รันมมิเกรชัน จึงสร้างเฉพาะ
 * ตารางที่จำเป็นเองในเทสต์ และชี้ connection ของโมดูลมาที่ sqlite เดียวกัน
 */
class OtBulkDecideTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['database.connections.mysql_ot_approval' => config('database.connections.sqlite')]);
        DB::purge('mysql_ot_approval');

        Schema::create('app_users', function ($table) {
            $table->id();
            $table->string('employee_code')->nullable();
            $table->string('full_name_th')->nullable();
            $table->string('full_name_en')->nullable();
            $table->string('email')->nullable();
            $table->timestamps();
        });

        Schema::connection('mysql_ot_approval')->create('ot_requests', function ($table) {
            $table->id();
            $table->string('company');
            $table->string('dept_code')->nullable();
            $table->string('employee_code');
            $table->string('employee_name')->nullable();
            $table->string('department_name')->nullable();
            $table->date('work_date');
            $table->string('ot_type')->nullable();
            $table->dateTime('requested_start_at')->nullable();
            $table->dateTime('requested_end_at')->nullable();
            $table->integer('requested_hours')->default(0);
            $table->integer('requested_minutes')->default(0);
            $table->string('approval_status')->default('draft');
            $table->string('attendance_status')->nullable();
            $table->string('export_status')->nullable();
            $table->string('created_by_employee_code')->nullable();
            $table->string('decision_note')->nullable();
            $table->unsignedBigInteger('decided_by_app_user_id')->nullable();
            $table->string('decided_by_employee_code')->nullable();
            $table->dateTime('decided_at')->nullable();
            $table->timestamps();
        });

        Schema::connection('mysql_ot_approval')->create('ot_request_approvals', function ($table) {
            $table->id();
            $table->unsignedBigInteger('ot_request_id');
            $table->string('decision');
            $table->unsignedBigInteger('actor_app_user_id')->nullable();
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

    public function test_supervisor_can_decide_old_cycle_until_the_end_of_day_23(): void
    {
        CarbonImmutable::setTestNow('2026-08-23 23:59:59');
        $actor = AppUser::create(['employee_code' => 'SV001', 'full_name_th' => 'ผู้อนุมัติทดสอบ']);
        $company = array_key_first(OtDepartmentAssignment::COMPANIES);
        $request = $this->makeRequest($company, 'MYDEPT', 'TEST0021');
        $request->forceFill(['work_date' => '2026-08-20'])->save();

        $this->mock(BplusAttendanceService::class)
            ->shouldReceive('employees')
            ->once()
            ->andReturn(['employees' => []]);

        $result = app(OtRequestWorkflowService::class)->decideMany(
            [$request->id],
            $actor,
            ['all' => true],
            OtRequest::APPROVAL_APPROVED,
            null,
        );

        $this->assertCount(1, $result['updated']);
        $this->assertSame(OtRequest::APPROVAL_APPROVED, $request->refresh()->approval_status);
    }

    public function test_supervisor_cannot_decide_old_cycle_from_day_24(): void
    {
        CarbonImmutable::setTestNow('2026-08-24 00:00:00');
        $actor = AppUser::create(['employee_code' => 'SV001', 'full_name_th' => 'ผู้อนุมัติทดสอบ']);
        $company = array_key_first(OtDepartmentAssignment::COMPANIES);
        $request = $this->makeRequest($company, 'MYDEPT', 'TEST0022');
        $request->forceFill(['work_date' => '2026-08-20'])->save();

        $this->mock(BplusAttendanceService::class)->shouldNotReceive('employees');

        $result = app(OtRequestWorkflowService::class)->decideMany(
            [$request->id],
            $actor,
            ['all' => true],
            OtRequest::APPROVAL_APPROVED,
            null,
        );

        $this->assertSame([], $result['updated']);
        $this->assertCount(1, $result['skipped']);
        $this->assertStringContainsString('พ้นกำหนดอนุมัติ', $result['skipped'][0]['reason']);
        $this->assertSame(OtRequest::APPROVAL_SUBMITTED, $request->refresh()->approval_status);
    }

    public function test_payload_marks_an_expired_submitted_request_as_not_decidable(): void
    {
        CarbonImmutable::setTestNow('2026-08-24 00:00:00');
        $company = array_key_first(OtDepartmentAssignment::COMPANIES);
        $request = $this->makeRequest($company, 'MYDEPT', 'TESTPAYLOAD');
        $request->forceFill(['work_date' => '2026-08-20'])->save();

        $payload = app(OtRequestWorkflowService::class)->requestPayload($request->refresh());

        $this->assertFalse($payload['can_decide']);
    }

    public function test_foreman_cannot_submit_an_expired_ot_draft(): void
    {
        CarbonImmutable::setTestNow('2026-08-24 00:00:00');
        $actor = AppUser::create(['employee_code' => 'FM001', 'full_name_th' => 'ผู้ขอทดสอบ']);
        $company = array_key_first(OtDepartmentAssignment::COMPANIES);
        $request = $this->makeRequest($company, 'MYDEPT', 'TESTDRAFT');
        $request->forceFill([
            'work_date' => '2026-08-20',
            'approval_status' => OtRequest::APPROVAL_DRAFT,
        ])->save();

        $this->mock(BplusAttendanceService::class)->shouldNotReceive('employees');

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('พ้นกำหนดอนุมัติ');

        app(OtRequestWorkflowService::class)->submitDrafts(
            [$request->id],
            $actor,
            ['all' => true],
        );
    }

    public function test_skips_rows_outside_the_supervisor_scope_without_failing_the_batch(): void
    {
        $actor = AppUser::create(['employee_code' => 'SV001', 'full_name_th' => 'ผู้อนุมัติทดสอบ']);
        $company = array_key_first(OtDepartmentAssignment::COMPANIES);
        $outsider = $this->makeRequest($company, 'OTHERDEPT', 'TEST0001');

        $scope = ['all' => false, 'departments' => [['company' => $company, 'dept_code' => 'MYDEPT']]];

        $result = app(OtRequestWorkflowService::class)->decideMany(
            [$outsider->id],
            $actor,
            $scope,
            OtRequest::APPROVAL_APPROVED,
            null,
        );

        $this->assertSame([], $result['updated'], 'นอกแผนกที่รับผิดชอบต้องไม่ถูกอนุมัติ');
        $this->assertCount(1, $result['skipped']);
        $this->assertSame($outsider->id, $result['skipped'][0]['id']);
        $this->assertSame('คุณไม่มีสิทธิ์อนุมัติคำขอนี้', $result['skipped'][0]['reason']);
        $this->assertSame(
            OtRequest::APPROVAL_SUBMITTED,
            $outsider->refresh()->approval_status,
            'สถานะเดิมต้องไม่ถูกแตะ',
        );
    }

    public function test_reports_unknown_ids_instead_of_throwing(): void
    {
        $actor = AppUser::create(['employee_code' => 'SV001', 'full_name_th' => 'ผู้อนุมัติทดสอบ']);

        $result = app(OtRequestWorkflowService::class)->decideMany(
            [999999],
            $actor,
            ['all' => true],
            OtRequest::APPROVAL_APPROVED,
            null,
        );

        $this->assertSame([], $result['updated']);
        $this->assertSame([['id' => 999999, 'reason' => 'ไม่พบคำขอนี้']], $result['skipped']);
    }

    public function test_bulk_rejection_distributes_the_required_reason_to_every_selected_employee(): void
    {
        $actor = AppUser::create(['employee_code' => 'SV001', 'full_name_th' => 'ผู้อนุมัติทดสอบ']);
        $company = array_key_first(OtDepartmentAssignment::COMPANIES);
        $first = $this->makeRequest($company, 'MYDEPT', 'TEST0001');
        $second = $this->makeRequest($company, 'MYDEPT', 'TEST0002');
        $reason = 'กำลังคนเพียงพอ จึงไม่อนุมัติ OT';

        $this->mock(BplusAttendanceService::class)
            ->shouldReceive('employees')
            ->once()
            ->andReturn(['employees' => []]);

        $result = app(OtRequestWorkflowService::class)->decideMany(
            [$first->id, $second->id],
            $actor,
            ['all' => true],
            OtRequest::APPROVAL_REJECTED,
            $reason,
        );

        $this->assertCount(2, $result['updated']);
        $this->assertSame([], $result['skipped']);
        foreach ([$first, $second] as $request) {
            $this->assertSame(OtRequest::APPROVAL_REJECTED, $request->refresh()->approval_status);
            $this->assertSame($reason, $request->decision_note);
            $this->assertDatabaseHas('ot_request_approvals', [
                'ot_request_id' => $request->id,
                'decision' => OtRequest::APPROVAL_REJECTED,
                'actor_employee_code' => 'SV001',
                'note' => $reason,
            ], 'mysql_ot_approval');
        }

        $this->assertSame(2, OtRequestApproval::query()->count());
    }

    public function test_supervisor_can_change_an_approved_request_to_rejected(): void
    {
        $actor = AppUser::create(['employee_code' => 'SV001', 'full_name_th' => 'ผู้อนุมัติทดสอบ']);
        $company = array_key_first(OtDepartmentAssignment::COMPANIES);
        $request = $this->makeRequest($company, 'MYDEPT', 'TEST0045');
        $request->forceFill([
            'approval_status' => OtRequest::APPROVAL_APPROVED,
            'attendance_status' => OtRequest::ATTENDANCE_FAILED,
            'export_status' => OtRequest::EXPORT_NOT_READY,
        ])->save();

        $this->mock(BplusAttendanceService::class)
            ->shouldReceive('employees')
            ->once()
            ->andReturn(['employees' => []]);

        $updated = app(OtRequestWorkflowService::class)->decide(
            $request,
            $actor,
            ['all' => true],
            OtRequest::APPROVAL_REJECTED,
            'พนักงานไม่ได้อยู่ทำ OT ตามที่ขอ',
        );

        $this->assertSame(OtRequest::APPROVAL_REJECTED, $updated->approval_status);
        $this->assertSame(OtRequest::EXPORT_NOT_READY, $updated->export_status);
        $this->assertSame('พนักงานไม่ได้อยู่ทำ OT ตามที่ขอ', $updated->decision_note);
        $this->assertDatabaseHas('ot_request_approvals', [
            'ot_request_id' => $request->id,
            'decision' => OtRequest::APPROVAL_REJECTED,
            'actor_employee_code' => 'SV001',
            'note' => 'พนักงานไม่ได้อยู่ทำ OT ตามที่ขอ',
        ], 'mysql_ot_approval');
    }

    public function test_supervisor_can_change_a_rejected_request_to_approved_when_attendance_allows(): void
    {
        $actor = AppUser::create(['employee_code' => 'SV001', 'full_name_th' => 'ผู้อนุมัติทดสอบ']);
        $company = array_key_first(OtDepartmentAssignment::COMPANIES);
        $request = $this->makeRequest($company, 'MYDEPT', 'TEST0046');
        $request->forceFill([
            'approval_status' => OtRequest::APPROVAL_REJECTED,
            'attendance_status' => OtRequest::ATTENDANCE_PASSED,
            'export_status' => OtRequest::EXPORT_NOT_READY,
            'decision_note' => 'ปฏิเสธผิดรายการ',
        ])->save();

        $this->mock(BplusAttendanceService::class)
            ->shouldReceive('employees')
            ->once()
            ->andReturn(['employees' => []]);

        $updated = app(OtRequestWorkflowService::class)->decide(
            $request,
            $actor,
            ['all' => true],
            OtRequest::APPROVAL_APPROVED,
            'ตรวจสอบแล้ว อนุมัติถูกต้อง',
        );

        $this->assertSame(OtRequest::APPROVAL_APPROVED, $updated->approval_status);
        $this->assertSame(OtRequest::EXPORT_READY, $updated->export_status);
        $this->assertSame('ตรวจสอบแล้ว อนุมัติถูกต้อง', $updated->decision_note);
        $this->assertDatabaseHas('ot_request_approvals', [
            'ot_request_id' => $request->id,
            'decision' => OtRequest::APPROVAL_APPROVED,
            'actor_employee_code' => 'SV001',
            'note' => 'ตรวจสอบแล้ว อนุมัติถูกต้อง',
        ], 'mysql_ot_approval');
    }

    private function makeRequest(string $company, string $deptCode, string $employeeCode): OtRequest
    {
        return OtRequest::create([
            'company' => $company,
            'dept_code' => $deptCode,
            'employee_code' => $employeeCode,
            'employee_name' => 'พนักงานทดสอบ '.$employeeCode,
            'department_name' => 'แผนกทดสอบ',
            'work_date' => now()->toDateString(),
            'ot_type' => 'weekday_after_work',
            'requested_start_at' => now()->setTime(18, 0),
            'requested_end_at' => now()->setTime(20, 0),
            'requested_hours' => 2,
            'requested_minutes' => 0,
            'approval_status' => OtRequest::APPROVAL_SUBMITTED,
            'attendance_status' => OtRequest::ATTENDANCE_WAITING,
            'export_status' => OtRequest::EXPORT_NOT_READY,
        ]);
    }
}
