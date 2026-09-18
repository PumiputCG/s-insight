<?php

namespace Tests\Feature\OtApproval;

use App\Models\Insight\Employee;
use App\Models\OtApproval\OtDepartmentAssignment;
use App\Models\OtApproval\OtRequest;
use App\Services\OtApproval\BplusAttendanceService;
use App\Services\OtApproval\OtRequestWorkflowService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * ปฏิทินกรองวันที่ในหน้าอนุมัติ OT
 *
 * Supervisor คนเดียวรับคำขอจากหลายแผนกทุกวัน ถ้าไม่แบ่งเป็นวันจะยาวจนหาไม่เจอ
 * กติกาคือ ไม่ส่ง date = ทุกวันเหมือนเดิม (กันงานค้างจากวันก่อนหลุดสายตา)
 * ส่ง date = เห็นเฉพาะ OT ที่ "ทำ" วันนั้น ซึ่งคือ work_date ไม่ใช่วันที่ส่งคำขอ
 *
 * โปรเจคนี้ test DB เป็น sqlite :memory: และไม่ได้รันมมิเกรชัน จึงสร้างเฉพาะ
 * ตารางที่จำเป็นเองในเทสต์ และชี้ connection ของโมดูลมาที่ sqlite เดียวกัน
 */
class OtApprovalQueueDateTest extends TestCase
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
            $table->string('id_thai_hash')->nullable();
            $table->string('profile_picture')->nullable();
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
            $table->date('hire_date')->nullable();
            $table->date('resign_date')->nullable();
            $table->date('pending_resign_date')->nullable();
            $table->date('manual_resign_date')->nullable();
            $table->string('emp_status')->nullable();
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
            $table->string('shift_code')->nullable();
            $table->string('shift_in')->nullable();
            $table->string('shift_out')->nullable();
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
            $table->dateTime('submitted_at')->nullable();
            $table->timestamps();
        });
    }

    public function test_queue_without_a_date_still_returns_every_day(): void
    {
        $company = array_key_first(OtDepartmentAssignment::COMPANIES);
        $this->makeRequest($company, '2026-08-11', 'TEST0001');
        $this->makeRequest($company, '2026-08-13', 'TEST0002');
        $this->mockAttendance();

        $queue = app(OtRequestWorkflowService::class)->approvalQueue(['all' => true], 'pending');

        $this->assertCount(2, $queue, 'ไม่ส่งวันที่ต้องได้ทุกวันเหมือนพฤติกรรมเดิม');
    }

    public function test_queue_includes_localized_employee_names_from_employee_master(): void
    {
        $company = array_key_first(OtDepartmentAssignment::COMPANIES);
        Employee::create([
            'company' => $company,
            'employee_code' => 'TEST0001',
            'title' => 'นาย',
            'name_th' => 'ทดสอบ',
            'surname_th' => 'ระบบ',
            'name_en' => 'System Tester',
            'hire_date' => '2026-01-01',
            'emp_status' => '1',
        ]);
        $this->makeRequest($company, '2026-08-13', 'TEST0001');
        $this->mockAttendance();

        $row = app(OtRequestWorkflowService::class)->approvalQueue(['all' => true], 'pending')[0];

        $this->assertSame('นายทดสอบ ระบบ', $row['employee_name_th']);
        $this->assertSame('System Tester', $row['employee_name_en']);
        $this->assertSame('System Tester', $row['employee_name_my']);
    }

    public function test_queue_filters_by_the_ot_work_date(): void
    {
        $company = array_key_first(OtDepartmentAssignment::COMPANIES);
        $this->makeRequest($company, '2026-08-11', 'TEST0001');
        $wanted = $this->makeRequest($company, '2026-08-13', 'TEST0002');
        $this->mockAttendance();

        $queue = app(OtRequestWorkflowService::class)->approvalQueue(
            ['all' => true],
            'pending',
            CarbonImmutable::createFromFormat('Y-m-d', '2026-08-13')->startOfDay(),
        );

        $this->assertCount(1, $queue);
        $this->assertSame($wanted->id, $queue[0]['id']);
        $this->assertSame('2026-08-13', $queue[0]['work_date']);
    }

    public function test_date_and_status_filters_work_together(): void
    {
        $company = array_key_first(OtDepartmentAssignment::COMPANIES);
        $this->makeRequest($company, '2026-08-13', 'TEST0001');
        $approved = $this->makeRequest($company, '2026-08-13', 'TEST0002');
        $approved->update(['approval_status' => OtRequest::APPROVAL_APPROVED]);
        $this->makeRequest($company, '2026-08-11', 'TEST0003')
            ->update(['approval_status' => OtRequest::APPROVAL_APPROVED]);
        $this->mockAttendance();

        $queue = app(OtRequestWorkflowService::class)->approvalQueue(
            ['all' => true],
            'approved',
            CarbonImmutable::createFromFormat('Y-m-d', '2026-08-13')->startOfDay(),
        );

        $this->assertCount(1, $queue, 'ต้องกรองทั้งสองแกนพร้อมกัน ไม่ใช่เลือกอย่างใดอย่างหนึ่ง');
        $this->assertSame($approved->id, $queue[0]['id']);
    }

    public function test_approved_request_waiting_for_scan_stays_in_approved_filter(): void
    {
        $company = array_key_first(OtDepartmentAssignment::COMPANIES);
        $waitingScan = $this->makeRequest($company, '2026-08-13', 'TEST0001');
        $waitingScan->update([
            'approval_status' => OtRequest::APPROVAL_APPROVED,
            'attendance_status' => OtRequest::ATTENDANCE_WAITING,
        ]);
        $passed = $this->makeRequest($company, '2026-08-13', 'TEST0002');
        $passed->update([
            'approval_status' => OtRequest::APPROVAL_APPROVED,
            'attendance_status' => OtRequest::ATTENDANCE_PASSED,
        ]);
        $this->mockAttendance();

        $pending = app(OtRequestWorkflowService::class)->approvalQueue(['all' => true], 'pending');
        $approved = app(OtRequestWorkflowService::class)->approvalQueue(['all' => true], 'approved');

        $this->assertNotContains($waitingScan->id, array_column($pending, 'id'));
        $this->assertContains($waitingScan->id, array_column($approved, 'id'));
        $this->assertContains($passed->id, array_column($approved, 'id'));
    }

    private function mockAttendance(): void
    {
        $this->mock(BplusAttendanceService::class)
            ->shouldReceive('employees')
            ->andReturn(['employees' => []]);
    }

    private function makeRequest(string $company, string $workDate, string $employeeCode): OtRequest
    {
        return OtRequest::create([
            'company' => $company,
            'dept_code' => 'MYDEPT',
            'employee_code' => $employeeCode,
            'employee_name' => 'พนักงานทดสอบ '.$employeeCode,
            'department_name' => 'แผนกทดสอบ',
            'work_date' => $workDate,
            'ot_type' => 'weekday_after_work',
            'requested_start_at' => $workDate.' 18:00:00',
            'requested_end_at' => $workDate.' 20:00:00',
            'requested_hours' => 2,
            'requested_minutes' => 0,
            'approval_status' => OtRequest::APPROVAL_SUBMITTED,
            'attendance_status' => OtRequest::ATTENDANCE_WAITING,
            'export_status' => OtRequest::EXPORT_NOT_READY,
            'submitted_at' => $workDate.' 17:30:00',
        ]);
    }
}
