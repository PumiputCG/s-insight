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
 * สิทธิ์ OT กับสิทธิ์การลาเป็นคนละชุด — คุมด้วยคอลัมน์ `module` ของ ot_department_assignments
 *
 * แผนกหนึ่งตั้ง Supervisor ของ OT กับของการลาเป็นคนละคนได้ ระบบจึงต้องไม่หยิบข้ามฝั่ง
 * ทั้งตอนหาผู้อนุมัติและตอนส่งอีเมล/กระดิ่ง
 */
class OtModulePermissionTest extends TestCase
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

    public function test_an_assignment_saved_without_a_module_defaults_to_ot_and_never_to_leave(): void
    {
        // คอลัมน์ module เป็น NOT NULL default 'ot' — สิทธิ์ที่ไม่ระบุต้องตกเป็นของ OT เท่านั้น
        OtDepartmentAssignment::query()->insert([
            'company' => 'SUPAVUT_INDUSTRY',
            'dept_code' => 'SPV010',
            'role' => OtDepartmentAssignment::ROLE_FOREMAN,
            'app_user_id' => 11,
            'employee_code' => 'FM001',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertSame(
            1,
            OtDepartmentAssignment::query()->forModule(OtDepartmentAssignment::MODULE_OT)->count(),
            'แถว module = null ต้องนับเป็นสิทธิ์ OT',
        );
        $this->assertSame(
            0,
            OtDepartmentAssignment::query()->forModule(OtDepartmentAssignment::MODULE_LEAVE)->count(),
            'แถว module = null ต้องไม่กลายเป็นสิทธิ์การลา',
        );
    }

    public function test_a_leave_only_foreman_holds_no_ot_role(): void
    {
        $this->assign(OtDepartmentAssignment::MODULE_LEAVE, OtDepartmentAssignment::ROLE_FOREMAN, 21, 'FM002');

        $this->assertTrue(
            OtDepartmentAssignment::hasAnyRole(21),
            'ยังเข้าระบบได้เพราะมีสิทธิ์ฝั่งการลา',
        );
        $this->assertSame(
            0,
            OtDepartmentAssignment::query()
                ->forModule(OtDepartmentAssignment::MODULE_OT)
                ->where('app_user_id', 21)
                ->where('role', OtDepartmentAssignment::ROLE_FOREMAN)
                ->count(),
            'Foreman ของการลาต้องไม่มีสิทธิ์ขอ OT',
        );
    }

    public function test_ot_submit_notifies_the_ot_supervisor_not_the_leave_supervisor(): void
    {
        Mail::fake();

        /* สถานการณ์จริงหลังแยก module: แผนกเดียวกันตั้งคนละคน
           แถวของการลาถูกสร้างไว้ก่อน (id น้อยกว่า) เพื่อจำลองกรณีที่ admin
           มาเปลี่ยนผู้อนุมัติ OT ทีหลัง แถวใหม่จึงมี id มากกว่า */
        $leaveSupervisor = $this->user(101, 'SUP_LEAVE', 'leave-sup@example.test');
        $otSupervisor = $this->user(102, 'SUP_OT', 'ot-sup@example.test');

        $this->assign(OtDepartmentAssignment::MODULE_LEAVE, OtDepartmentAssignment::ROLE_SUPERVISOR, $leaveSupervisor->id, 'SUP_LEAVE');
        $this->assign(OtDepartmentAssignment::MODULE_OT, OtDepartmentAssignment::ROLE_SUPERVISOR, $otSupervisor->id, 'SUP_OT');

        $foreman = $this->user(103, 'FM003', 'fm@example.test');
        $request = $this->otRequest();

        (new OtNotificationService)->notifySubmitted([$request->id], $foreman);

        $this->assertTrue(
            OtNotification::query()->where('employee_code', 'SUP_OT')->exists(),
            'กระดิ่งต้องเข้าหา Supervisor ของ OT',
        );
        $this->assertFalse(
            OtNotification::query()->where('employee_code', 'SUP_LEAVE')->exists(),
            'Supervisor ของการลาต้องไม่ได้รับแจ้งเตือน OT',
        );

        Mail::assertSent(
            OtRequestSubmittedMail::class,
            fn (OtRequestSubmittedMail $mail) => $mail->hasTo('ot-sup@example.test'),
        );
        Mail::assertNotSent(
            OtRequestSubmittedMail::class,
            fn (OtRequestSubmittedMail $mail) => $mail->hasTo('leave-sup@example.test'),
        );
    }

    public function test_ot_submit_stays_silent_when_only_a_leave_supervisor_exists(): void
    {
        Mail::fake();

        $leaveSupervisor = $this->user(201, 'SUP_LEAVE2', 'leave-only@example.test');
        $this->assign(OtDepartmentAssignment::MODULE_LEAVE, OtDepartmentAssignment::ROLE_SUPERVISOR, $leaveSupervisor->id, 'SUP_LEAVE2');

        $foreman = $this->user(202, 'FM004', 'fm4@example.test');

        (new OtNotificationService)->notifySubmitted([$this->otRequest()->id], $foreman);

        $this->assertSame(0, OtNotification::query()->count(), 'ยังไม่ได้ตั้ง Supervisor ของ OT จึงต้องไม่แจ้งใครเลย');
        Mail::assertNothingSent();
    }

    private function assign(?string $module, string $role, int $userId, string $employeeCode): OtDepartmentAssignment
    {
        return OtDepartmentAssignment::create([
            'module' => $module,
            'company' => 'SUPAVUT_INDUSTRY',
            'dept_code' => 'SPV010',
            'role' => $role,
            'app_user_id' => $userId,
            'employee_code' => $employeeCode,
        ]);
    }

    private function user(int $id, string $code, ?string $email): AppUser
    {
        return AppUser::create([
            'id' => $id,
            'employee_code' => $code,
            'full_name_th' => 'ผู้ใช้ '.$code,
            'email' => $email,
        ]);
    }

    private function otRequest(): OtRequest
    {
        return OtRequest::create([
            'company' => 'SUPAVUT_INDUSTRY',
            'dept_code' => 'SPV010',
            'employee_code' => 'EMP001',
            'employee_name' => 'พนักงานทดสอบ',
            'department_name' => 'ฝ่ายผลิต',
            'work_date' => '2026-08-13',
            'ot_type' => 'weekday_after_work',
            'multiplier' => 1.5,
            'requested_start_at' => '2026-08-13 18:00:00',
            'requested_end_at' => '2026-08-13 20:00:00',
            'requested_hours' => 2,
            'requested_minutes' => 0,
            'approval_status' => OtRequest::APPROVAL_SUBMITTED,
            'attendance_status' => OtRequest::ATTENDANCE_WAITING,
            'export_status' => OtRequest::EXPORT_NOT_READY,
            'created_by_app_user_id' => 103,
            'created_by_employee_code' => 'FM003',
            'submitted_at' => '2026-08-13 17:30:00',
        ]);
    }
}
