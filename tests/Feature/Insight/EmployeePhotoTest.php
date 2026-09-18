<?php

namespace Tests\Feature\Insight;

use App\Models\Insight\AppUser;
use App\Models\Insight\Employee;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * รูปพนักงานต้องเกาะกับ employees ไม่ใช่ app_users
 *
 * ที่ต้องมีเทสต์คุม: ก่อนหน้านี้รูปอยู่ที่ app_users.profile_picture อย่างเดียว
 * พอพนักงานลาออกแล้วบัญชีถูกลบ รูปก็หายทั้งที่ไฟล์ยังอยู่ในเครื่อง (264 คนลาออก เหลือบัญชี 74)
 */
class EmployeePhotoTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('employees');
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('company', 30);
            $table->string('employee_code', 30);
            $table->string('license_id', 30)->nullable();
            $table->string('title', 30)->nullable();
            $table->string('name_th', 100)->nullable();
            $table->string('surname_th', 100)->nullable();
            $table->string('name_en', 100)->nullable();
            $table->string('job_th', 100)->nullable();
            $table->string('job_en', 100)->nullable();
            $table->string('dept_code', 30)->nullable();
            $table->string('dept_th', 100)->nullable();
            $table->string('dept_en', 100)->nullable();
            $table->date('hire_date')->nullable();
            $table->date('resign_date')->nullable();
            $table->date('pending_resign_date')->nullable();
            $table->date('manual_resign_date')->nullable();
            $table->string('emp_status', 10)->nullable();
            $table->string('photo_path')->nullable();
            $table->string('photo_source', 16)->nullable();
            $table->timestamp('photo_taken_at')->nullable();
            $table->timestamp('photo_synced_at')->nullable();
            $table->timestamps();
        });

        // ตารางบัญชี — ใช้ยืนยันว่าคนลาออกที่ไม่มีบัญชีแล้วยังมีรูป
        Schema::dropIfExists('app_users');
        Schema::create('app_users', function (Blueprint $table) {
            $table->id();
            $table->string('company', 30)->nullable();
            $table->string('employee_code', 30)->nullable();
            $table->string('profile_picture')->nullable();
            $table->timestamps();
        });
    }

    public function test_photo_url_uses_employee_photo_first(): void
    {
        $employee = Employee::unguarded(fn () => Employee::create([
            'company' => 'SUPAVUT_INDUSTRY',
            'employee_code' => '71100',
            'emp_status' => '1',
            'photo_path' => 'profiles/emp_71100.jpg',
            'photo_source' => 'hr',
        ]));

        // รูปจาก HR ต้องชนะรูปที่พนักงานอัปโหลดเอง ตามที่เจ้าของสั่ง "เอารูปตามโฟลเดอร์"
        $this->assertSame(
            asset('storage/profiles/emp_71100.jpg'),
            $employee->photoUrl('profiles/self-upload.png'),
        );
    }

    public function test_photo_url_falls_back_to_account_picture(): void
    {
        $employee = Employee::unguarded(fn () => Employee::create([
            'company' => 'SUPAVUT_INDUSTRY',
            'employee_code' => '71101',
            'emp_status' => '1',
        ]));

        $this->assertSame(
            asset('storage/profiles/self-upload.png'),
            $employee->photoUrl('profiles/self-upload.png'),
        );
    }

    public function test_photo_url_is_null_when_no_photo_anywhere(): void
    {
        $employee = Employee::unguarded(fn () => Employee::create([
            'company' => 'SUPAVUT_INDUSTRY',
            'employee_code' => '71102',
            'emp_status' => '1',
        ]));

        $this->assertNull($employee->photoUrl());
        $this->assertNull($employee->photoUrl(''));
    }

    /** คนลาออกที่บัญชีถูกลบไปแล้ว ต้องยังมีรูป — นี่คือเคสที่พังก่อนหน้านี้ */
    public function test_resigned_employee_keeps_photo_without_account(): void
    {
        $employee = Employee::unguarded(fn () => Employee::create([
            'company' => 'SUPAVUT_INDUSTRY',
            'employee_code' => '64198',
            'emp_status' => '2',
            'resign_date' => '2026-04-25',
            'photo_path' => 'profiles/emp_64198.jpg',
            'photo_source' => 'hr',
        ]));

        $this->assertSame(0, AppUser::query()->where('employee_code', '64198')->count());
        $this->assertNotNull($employee->photoUrl());
    }
}
