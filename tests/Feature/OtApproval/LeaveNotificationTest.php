<?php

namespace Tests\Feature\OtApproval;

use App\Mail\OtApproval\LeaveRequestSubmittedMail;
use App\Models\Insight\AppUser;
use App\Models\OtApproval\LeaveRequest;
use App\Models\OtApproval\OtDepartmentAssignment;
use App\Models\OtApproval\OtNotification;
use App\Services\OtApproval\LeaveNotificationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class LeaveNotificationTest extends TestCase
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

    $ot = Schema::connection('mysql_ot_approval');
    $ot->create('ot_department_assignments', function ($table) {
      $table->id();
      // สิทธิ์ OT กับการลาแยกกันด้วย module ตารางจริงจึงมีคอลัมน์นี้
      $table->string('module', 20)->default('ot');
      $table->string('company');
      $table->string('dept_code')->nullable();
      $table->string('role');
      $table->unsignedBigInteger('app_user_id');
      $table->string('employee_code')->nullable();
      $table->timestamps();
    });
    $ot->create('ot_notifications', function ($table) {
      $table->id();
      $table->string('employee_code', 50);
      $table->string('type', 40);
      $table->string('category', 20)->default('ot');
      $table->string('title', 190);
      $table->string('body', 500)->nullable();
      $table->string('link', 255)->nullable();
      $table->unsignedInteger('item_count')->default(1);
      $table->timestamp('read_at')->nullable();
      $table->timestamps();
    });
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
      $table->timestamps();
    });
  }

  public function test_submit_sends_one_leave_email_and_leave_bell_per_department(): void
  {
    Mail::fake();
    [$foreman, $supervisor] = $this->users();
    $first = $this->request('71001', $foreman);
    $second = $this->request('71002', $foreman);

    app(LeaveNotificationService::class)->notifySubmitted([$first->id, $second->id], $foreman);

    $notification = OtNotification::query()->first();
    $this->assertNotNull($notification);
    $this->assertSame(OtNotification::CATEGORY_LEAVE, $notification->category);
    $this->assertSame($supervisor->employee_code, $notification->employee_code);
    $this->assertSame(2, $notification->item_count);
    Mail::assertSent(LeaveRequestSubmittedMail::class, 1);
  }

  public function test_decision_rings_leave_bell_to_foreman_without_sending_email(): void
  {
    Mail::fake();
    [$foreman, $supervisor] = $this->users();
    $request = $this->request('71001', $foreman);
    $request->forceFill([
      'approval_status' => LeaveRequest::APPROVAL_REJECTED,
      'decision_note' => 'ไม่อนุมัติในรอบนี้',
    ])->save();

    app(LeaveNotificationService::class)->notifyDecided($request->refresh(), $supervisor);

    $notification = OtNotification::query()->where('employee_code', $foreman->employee_code)->first();
    $this->assertNotNull($notification);
    $this->assertSame(OtNotification::CATEGORY_LEAVE, $notification->category);
    $this->assertStringContainsString('ไม่อนุมัติในรอบนี้', (string) $notification->body);
    Mail::assertNothingSent();
  }

  /** @return array{0:AppUser,1:AppUser} */
  private function users(): array
  {
    $foreman = AppUser::create(['employee_code' => 'FM001', 'full_name_th' => 'Foreman ทดสอบ']);
    $supervisor = AppUser::create([
      'employee_code' => 'SV001',
      'full_name_th' => 'Supervisor ทดสอบ',
      'email' => 'supervisor@example.test',
    ]);

    OtDepartmentAssignment::create([
      // ผู้อนุมัติของ "การลา" ต้องอยู่ module leave ไม่งั้นระบบจะหาไม่เจอ
      'module' => OtDepartmentAssignment::MODULE_LEAVE,
      'company' => 'SUPAVUT_INDUSTRY',
      'dept_code' => 'SPV001',
      'role' => OtDepartmentAssignment::ROLE_SUPERVISOR,
      'app_user_id' => $supervisor->id,
      'employee_code' => $supervisor->employee_code,
    ]);

    return [$foreman, $supervisor];
  }

  private function request(string $employeeCode, AppUser $foreman): LeaveRequest
  {
    return LeaveRequest::create([
      'batch_uuid' => fake()->uuid(),
      'company' => 'SUPAVUT_INDUSTRY',
      'dept_code' => 'SPV001',
      'employee_code' => $employeeCode,
      'employee_name' => 'พนักงาน '.$employeeCode,
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
      'approval_status' => LeaveRequest::APPROVAL_SUBMITTED,
      'export_status' => LeaveRequest::EXPORT_NOT_READY,
      'created_by_app_user_id' => $foreman->id,
      'created_by_employee_code' => $foreman->employee_code,
      'submitted_at' => now(),
    ]);
  }
}
