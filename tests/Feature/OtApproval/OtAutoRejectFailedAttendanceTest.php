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
 * เวลาสแกนไม่ครอบคลุมช่วง OT = ระบบปฏิเสธให้เอง
 *
 * เดิม Supervisor ต้องมานั่งกดปฏิเสธเองเมื่อเวลา OT ไม่ครบ ทั้งที่ผลตายตัวอยู่แล้ว
 * เพราะอนุมัติไปก็ออก V74 ไม่ได้จนกว่า HR จะแก้เวลาใน Bplus ให้ก่อน
 */
class OtAutoRejectFailedAttendanceTest extends TestCase
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
            $table->timestamps();
        });

        Schema::create('employees', function ($table) {
            $table->id();
            $table->string('company');
            $table->string('employee_code');
            $table->string('license_id')->nullable();
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
            $table->string('attendance_source')->nullable();
            $table->dateTime('attendance_checked_at')->nullable();
            $table->string('last_clock_in')->nullable();
            $table->string('last_clock_out')->nullable();
            $table->string('export_status')->nullable();
            $table->unsignedBigInteger('decided_by_app_user_id')->nullable();
            $table->string('decided_by_employee_code')->nullable();
            $table->string('decision_note')->nullable();
            $table->dateTime('decided_at')->nullable();
            $table->dateTime('submitted_at')->nullable();
            $table->dateTime('exported_at')->nullable();
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

    public function test_failed_attendance_rejects_the_request_without_a_supervisor(): void
    {
        $request = $this->makeRequest();
        // สแกนออก 18:30 แต่ขอ OT ถึง 20:00 — ไม่ครอบคลุมช่วงที่ขอ
        $this->mockAttendance('07:30', '18:30');

        try {
            app(OtRequestWorkflowService::class)->decide(
                $request,
                AppUser::create(['employee_code' => 'SV001', 'full_name_th' => 'Supervisor']),
                ['all' => true, 'departments' => [], 'employees' => []],
                OtRequest::APPROVAL_APPROVED,
                null,
            );
            $this->fail('ต้องกันไม่ให้อนุมัติรายการที่เวลาสแกนตกกฎ');
        } catch (ValidationException $exception) {
            $this->assertStringContainsString('อัตโนมัติ', collect($exception->errors())->flatten()->first());
        }

        $request->refresh();
        $this->assertSame(OtRequest::APPROVAL_REJECTED, $request->approval_status);
        $this->assertSame(OtRequest::ATTENDANCE_FAILED, $request->attendance_status);
        $this->assertSame(OtRequestWorkflowService::AUTO_REJECT_NOTE, $request->decision_note);
        $this->assertNull($request->decided_by_app_user_id, 'ระบบเป็นคนปฏิเสธ ไม่ใช่คน จึงไม่ต้องมีผู้ตัดสิน');
        $this->assertSame(OtRequest::EXPORT_NOT_READY, $request->export_status);
    }

    public function test_the_auto_rejection_is_written_to_the_audit_trail(): void
    {
        $request = $this->makeRequest();
        $this->mockAttendance('07:30', '18:30');

        rescue(fn () => app(OtRequestWorkflowService::class)->decide(
            $request,
            AppUser::create(['employee_code' => 'SV001', 'full_name_th' => 'Supervisor']),
            ['all' => true, 'departments' => [], 'employees' => []],
            OtRequest::APPROVAL_REJECTED,
            null,
        ), null, false);

        $audit = OtRequestApproval::query()->where('ot_request_id', $request->id)->get();

        $this->assertCount(1, $audit);
        $this->assertSame(OtRequest::APPROVAL_REJECTED, $audit->first()->decision);
        $this->assertNull($audit->first()->actor_app_user_id);
    }

    public function test_the_rejected_tab_still_lists_the_auto_rejected_rows(): void
    {
        $request = $this->makeRequest();
        $request->update([
            'approval_status' => OtRequest::APPROVAL_REJECTED,
            'attendance_status' => OtRequest::ATTENDANCE_FAILED,
        ]);
        $this->mockAttendance(null, null);

        $queue = app(OtRequestWorkflowService::class)->approvalQueue(['all' => true], 'rejected');

        $this->assertCount(1, $queue, 'ปฏิเสธไปแล้วยังต้องเห็นในแท็บนี้ เพื่อให้ HR ตามแก้เวลาใน Bplus');
        $this->assertSame($request->id, $queue[0]['id']);
    }

    private function mockAttendance(?string $clockIn, ?string $clockOut): void
    {
        $this->mock(BplusAttendanceService::class)
            ->shouldReceive('employees')
            ->andReturn(['employees' => [[
                'code' => 'TEST0001',
                'clock_in' => $clockIn,
                'clock_out' => $clockOut,
                'attendance_source' => 'processed',
            ]]]);
    }

    private function makeRequest(): OtRequest
    {
        return OtRequest::create([
            'company' => array_key_first(OtDepartmentAssignment::COMPANIES),
            'dept_code' => 'MYDEPT',
            'employee_code' => 'TEST0001',
            'employee_name' => 'พนักงานทดสอบ',
            'department_name' => 'แผนกทดสอบ',
            'work_date' => '2026-08-13',
            'ot_type' => 'weekday_after_work',
            'requested_start_at' => '2026-08-13 18:00:00',
            'requested_end_at' => '2026-08-13 20:00:00',
            'requested_hours' => 2,
            'requested_minutes' => 0,
            'approval_status' => OtRequest::APPROVAL_SUBMITTED,
            'attendance_status' => OtRequest::ATTENDANCE_WAITING,
            'export_status' => OtRequest::EXPORT_NOT_READY,
            'submitted_at' => '2026-08-13 17:30:00',
        ]);
    }
}
