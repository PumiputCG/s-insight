<?php

namespace Tests\Feature\OtApproval;

use App\Mail\OtApproval\OtRequestSubmittedMail;
use App\Models\Insight\AppUser;
use App\Models\OtApproval\OtDepartmentAssignment;
use App\Models\OtApproval\OtNotification;
use App\Models\OtApproval\OtRequest;
use App\Services\OtApproval\OtNotificationService;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Tests\Concerns\BuildsOtApprovalSchema;
use Tests\TestCase;

/**
 * กติกาแจ้งเตือน OT ที่ Manager กำหนด:
 *   - Foreman กดส่ง  -> Supervisor ได้ทั้งกระดิ่งและอีเมล
 *   - ไม่มีอีเมลผูกไว้ -> ยังได้กระดิ่ง แต่ไม่ส่งเมล
 *   - อนุมัติ/ไม่อนุมัติ -> Foreman ได้แค่กระดิ่ง ไม่ส่งเมล (กันแจ้งเตือนถี่)
 *
 * โปรเจคนี้ test DB เป็น sqlite :memory: และรัน migration จริงไม่ได้ (migration ใช้ SQL เฉพาะ MySQL)
 * จึงสร้างตารางจาก BuildsOtApprovalSchema ซึ่งสะท้อน schema จริงคอลัมน์ต่อคอลัมน์
 */
class OtNotificationTest extends TestCase
{
    use BuildsOtApprovalSchema;

    protected function setUp(): void
    {
        parent::setUp();

        $this->buildOtApprovalSchema();

        Schema::create('app_users', function ($table) {
            $table->id();
            $table->string('employee_code')->nullable();
            $table->string('full_name_th')->nullable();
            $table->string('full_name_en')->nullable();
            $table->string('email')->nullable();
            $table->timestamps();
        });
    }

    public function test_submit_sends_bell_and_email_to_supervisor(): void
    {
        Mail::fake();
        [$request, $foreman, $supervisor] = $this->scenario('sup@example.test');

        app(OtNotificationService::class)->notifySubmitted([$request->id], $foreman);

        $notification = OtNotification::where('employee_code', $supervisor->employee_code)->first();
        $this->assertNotNull($notification, 'Supervisor ต้องได้กระดิ่ง');
        $this->assertSame(OtNotification::TYPE_SUBMITTED, $notification->type);
        $this->assertSame(1, $notification->item_count);
        $this->assertNull($notification->read_at, 'แจ้งเตือนใหม่ต้องยังไม่อ่าน');

        Mail::assertSent(OtRequestSubmittedMail::class, 1);
    }

    public function test_supervisor_without_email_gets_bell_but_no_mail(): void
    {
        Mail::fake();
        [$request, $foreman, $supervisor] = $this->scenario(null);

        app(OtNotificationService::class)->notifySubmitted([$request->id], $foreman);

        $this->assertDatabaseHas('ot_notifications', [
            'employee_code' => $supervisor->employee_code,
            'type' => OtNotification::TYPE_SUBMITTED,
        ], 'mysql_ot_approval');

        Mail::assertNothingSent();
    }

    public function test_decision_only_rings_bell_and_never_mails(): void
    {
        Mail::fake();
        [$request, $foreman, $supervisor] = $this->scenario('sup@example.test');

        $request->forceFill([
            'approval_status' => OtRequest::APPROVAL_APPROVED,
            'created_by_employee_code' => $foreman->employee_code,
        ])->save();

        app(OtNotificationService::class)->notifyDecided($request->refresh(), $supervisor);

        $notification = OtNotification::where('employee_code', $foreman->employee_code)
            ->where('type', OtNotification::TYPE_DECIDED)
            ->first();

        $this->assertNotNull($notification, 'Foreman ต้องได้กระดิ่งผลอนุมัติ');
        Mail::assertNothingSent();
    }

    public function test_one_mail_per_department_batch_not_per_employee(): void
    {
        Mail::fake();
        [$first, $foreman] = $this->scenario('sup@example.test');
        $second = $this->makeRequest($first->company, (string) $first->dept_code, 'TEST0002');

        app(OtNotificationService::class)->notifySubmitted([$first->id, $second->id], $foreman);

        // รวมทั้งล็อตเป็นฉบับเดียว ตามที่ Manager ขอให้กันเมลถี่
        Mail::assertSent(OtRequestSubmittedMail::class, 1);
        $this->assertSame(2, OtNotification::first()->item_count);
    }

    public function test_unread_count_and_mark_all_read(): void
    {
        [$request, $foreman, $supervisor] = $this->scenario(null);
        $service = app(OtNotificationService::class);
        $service->notifySubmitted([$request->id], $foreman);

        $code = (string) $supervisor->employee_code;
        $this->assertSame(1, $service->unreadCountFor($code));

        $service->markAllRead($code);
        $this->assertSame(0, $service->unreadCountFor($code));
    }

    /** @return array{0: OtRequest, 1: AppUser, 2: AppUser} */
    private function scenario(?string $supervisorEmail): array
    {
        $foreman = AppUser::create([
            'employee_code' => 'FM001',
            'full_name_th' => 'หัวหน้างานทดสอบ',
        ]);

        $supervisor = AppUser::create([
            'employee_code' => 'SV001',
            'full_name_th' => 'ผู้อนุมัติทดสอบ',
            'email' => $supervisorEmail,
        ]);

        $company = array_key_first(OtDepartmentAssignment::COMPANIES);

        OtDepartmentAssignment::create([
            'company' => $company,
            'dept_code' => 'TESTDEPT',
            'role' => OtDepartmentAssignment::ROLE_SUPERVISOR,
            'app_user_id' => $supervisor->id,
            'employee_code' => $supervisor->employee_code,
        ]);

        return [$this->makeRequest($company, 'TESTDEPT', 'TEST0001'), $foreman, $supervisor];
    }

    private function makeRequest(string $company, string $deptCode, string $employeeCode): OtRequest
    {
        $request = new OtRequest;
        $request->forceFill([
            'company' => $company,
            'dept_code' => $deptCode,
            'employee_code' => $employeeCode,
            'employee_name' => 'พนักงานทดสอบ '.$employeeCode,
            'department_name' => 'แผนกทดสอบ',
            'work_date' => now()->toDateString(),
            'ot_type' => 'weekday_after_work',
            'multiplier' => 1.5,
            'requested_start_at' => now()->setTime(18, 0),
            'requested_end_at' => now()->setTime(20, 0),
            'requested_hours' => 2,
            'requested_minutes' => 0,
            'approval_status' => OtRequest::APPROVAL_SUBMITTED,
            'attendance_status' => OtRequest::ATTENDANCE_WAITING,
            'export_status' => OtRequest::EXPORT_NOT_READY,
            'created_by_app_user_id' => 1,
        ]);

        $request->save();

        return $request;
    }
}
