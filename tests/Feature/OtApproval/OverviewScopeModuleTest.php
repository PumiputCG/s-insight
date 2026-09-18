<?php

namespace Tests\Feature\OtApproval;

use App\Http\Controllers\OtApproval\LeaveApprovalController;
use App\Http\Controllers\OtApproval\OtApprovalController;
use App\Models\Insight\AppUser;
use App\Models\OtApproval\OtDepartmentAssignment;
use Illuminate\Support\Facades\Schema;
use ReflectionMethod;
use Tests\Concerns\BuildsOtApprovalSchema;
use Tests\TestCase;

/**
 * ขอบเขตพนักงานของหน้าภาพรวมต้องแยกตาม module
 *
 * ตั้งเป็น Foreman/Supervisor ฝั่ง OT ของแผนกไหน = เห็นแผนกนั้นในหน้าภาพรวม OT เท่านั้น
 * สลับไปหน้าภาพรวมการลาต้องไม่ติดแผนกนั้นมาด้วย เพราะเป็นสิทธิ์คนละชุด (Manager 2026-08-18)
 * คนที่ไม่มีสิทธิ์ฝั่งนั้นยังเข้าหน้าได้ แต่เห็นเฉพาะข้อมูลของตัวเอง
 */
class OverviewScopeModuleTest extends TestCase
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
            $table->string('email')->nullable();
            $table->string('role')->nullable();
            $table->string('id_thai_hash')->nullable();
            $table->text('companies')->nullable();
            $table->timestamps();
        });

        Schema::create('employees', function ($table) {
            $table->id();
            $table->string('company');
            $table->string('employee_code');
            $table->string('license_id')->nullable();
            $table->string('dept_code')->nullable();
            $table->date('hire_date')->nullable();
            $table->date('pending_resign_date')->nullable();
            $table->date('manual_resign_date')->nullable();
            $table->date('resign_date')->nullable();
            $table->string('emp_status')->default('1');
            $table->timestamps();
        });
    }

    public function test_an_ot_only_assignee_sees_the_department_on_the_ot_overview(): void
    {
        $user = $this->userWith(OtDepartmentAssignment::MODULE_OT);

        $scope = $this->scopeOf(OtApprovalController::class, 'attendanceScope', $user);

        $this->assertFalse($scope['all']);
        $this->assertSame(
            [['company' => 'SUPAVUT_INDUSTRY', 'dept_code' => 'SPV010']],
            $scope['departments'],
        );
    }

    public function test_an_ot_only_assignee_does_not_see_the_department_on_the_leave_overview(): void
    {
        $user = $this->userWith(OtDepartmentAssignment::MODULE_OT);

        $scope = $this->scopeOf(LeaveApprovalController::class, 'overviewScope', $user);

        $this->assertSame([], $scope['departments'], 'สิทธิ์ฝั่ง OT ต้องไม่ลากแผนกมาโผล่ในหน้าภาพรวมการลา');
    }

    public function test_a_leave_only_assignee_does_not_see_the_department_on_the_ot_overview(): void
    {
        $user = $this->userWith(OtDepartmentAssignment::MODULE_LEAVE);

        $scope = $this->scopeOf(OtApprovalController::class, 'attendanceScope', $user);

        $this->assertSame([], $scope['departments'], 'สิทธิ์ฝั่งการลาต้องไม่ลากแผนกมาโผล่ในหน้าภาพรวม OT');
    }

    public function test_a_leave_only_assignee_sees_the_department_on_the_leave_overview(): void
    {
        $user = $this->userWith(OtDepartmentAssignment::MODULE_LEAVE);

        $scope = $this->scopeOf(LeaveApprovalController::class, 'overviewScope', $user);

        $this->assertSame(
            [['company' => 'SUPAVUT_INDUSTRY', 'dept_code' => 'SPV010']],
            $scope['departments'],
        );
    }

    public function test_someone_assigned_on_both_sides_sees_the_department_on_both_overviews(): void
    {
        $user = $this->userWith(OtDepartmentAssignment::MODULE_OT);
        $this->assign($user, OtDepartmentAssignment::MODULE_LEAVE);

        $this->assertCount(1, $this->scopeOf(OtApprovalController::class, 'attendanceScope', $user)['departments']);
        $this->assertCount(1, $this->scopeOf(LeaveApprovalController::class, 'overviewScope', $user)['departments']);
    }

    /**
     * เรียก private scope ของ controller ตรง ๆ โดยสวมบทเป็นผู้ใช้คนนั้น
     *
     * @return array<string, mixed>
     */
    private function scopeOf(string $controller, string $method, AppUser $user): array
    {
        app()->instance('current_user', $user);

        $reflection = new ReflectionMethod($controller, $method);
        $reflection->setAccessible(true);

        return $reflection->invoke(app($controller));
    }

    private function userWith(string $module): AppUser
    {
        $user = AppUser::create([
            'employee_code' => 'FM100',
            'full_name_th' => 'หัวหน้างานทดสอบ',
            'role' => 'user',
        ]);

        $this->assign($user, $module);

        return $user;
    }

    private function assign(AppUser $user, string $module): void
    {
        OtDepartmentAssignment::create([
            'module' => $module,
            'company' => 'SUPAVUT_INDUSTRY',
            'dept_code' => 'SPV010',
            'role' => OtDepartmentAssignment::ROLE_FOREMAN,
            'app_user_id' => $user->id,
            'employee_code' => $user->employee_code,
        ]);
    }
}
