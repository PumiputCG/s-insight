<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ตารางหลักของ Insight (รวมไว้ไฟล์เดียวเพื่อดูง่าย — โปรเจคยังเริ่มต้น)
 *
 * 1) employees  — "มาสเตอร์พนักงานกลาง" mirror จาก Bplus view EMP_MAIN (รวมคนลาออก)
 * 2) app_users  — "บัญชีล็อกอิน" (เฉพาะคน active ; ลาออกแล้วลบทิ้ง — ดู AppUserSync)
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── 1) employees : สำเนาข้อมูล Bplus ล้วน (ไม่มีเรื่อง login) ──────────────
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('no')->nullable()->index(); // เลขลำดับแสดงผล 1..N

            // ตัวตน / บริษัท
            $table->string('company', 30)->index();          // SUPAVUT_INDUSTRY / MOLDVANTO
            $table->string('employee_code', 30);             // EMP_CODE
            $table->string('license_id', 30)->nullable();    // LICENSE_ID เลขบัตรประชาชน (ต้นทางรหัสผ่าน app_users)

            // ชื่อ (mirror view)
            $table->string('title', 30)->nullable();         // TITLE คำนำหน้า
            $table->string('gender', 10)->nullable();        // TITLE_ID = เพศ (ชื่อคอลัมน์หลอกใน Bplus)
            $table->string('name_th', 100)->nullable();      // NAME_T
            $table->string('surname_th', 100)->nullable();   // SURNAME_T
            $table->string('name_en', 150)->nullable();      // NAME_E

            // ตำแหน่ง (mirror view)
            $table->string('job_code', 30)->nullable();      // JOB_CODE
            $table->string('job_th', 191)->nullable();       // JOB_T
            $table->string('job_en', 191)->nullable();       // JOB_E

            // แผนก (mirror view)
            $table->string('dept_code', 20)->nullable()->index(); // DEPT_CODE
            $table->string('dept_th', 191)->nullable();      // DEPT_T
            $table->string('dept_en', 191)->nullable();      // DEPT_E

            // PAYROLLINFO
            $table->date('hire_date')->nullable();           // PRI_START_D วันเริ่มงาน
            $table->date('probation_end_date')->nullable();  // PRI_PROB_D วันพ้นทดลองงาน
            $table->date('resign_date')->nullable();         // PRI_RES_D วันลาออก
            $table->string('emp_status', 10)->nullable()->index(); // PRI_STATUS: 1=ทำงาน, 2=ลาออก

            // sync metadata
            $table->json('source_raw')->nullable();          // แถวดิบจาก Bplus
            $table->timestamp('synced_at')->nullable();      // เวลา sync ล่าสุด

            $table->timestamps();

            $table->unique(['company', 'employee_code']);
        });

        // ── 2) app_users : บัญชีล็อกอิน (สร้างจาก employees ที่ active เท่านั้น) ─────
        //  ตัวตน = เลขบัตรประชาชน (id_thai_hash) ; 1 คน = 1 บัญชี แม้อยู่หลายบริษัท
        Schema::create('app_users', function (Blueprint $table) {
            $table->id();

            $table->string('id_thai_hash')->nullable()->unique(); // เลขบัตรประชาชน (ตัวตนหลัก) — plaintext ไม่ hash
            $table->string('company')->index();              // บริษัทที่บัญชีนี้คุม (คั่นด้วย , ได้หลายบริษัท)
            $table->string('employee_code', 30);             // รหัสหลัก (บริษัทแรก) ไว้แสดงผล
            $table->json('companies')->nullable();           // map บริษัท -> รหัสพนักงาน {"SUPAVUT_INDUSTRY":"71019",...} ใช้ล็อกอิน
            $table->string('password')->nullable();          // plaintext เริ่มต้น = เลขบัตร (เปลี่ยนได้) — ใช้ร่วมทุกบริษัท
            $table->string('role', 50)->default('user');     // admin / user

            // ข้อมูลแสดงผล (denormalize จาก employees)
            $table->string('full_name_th', 255)->nullable();
            $table->string('full_name_en', 255)->nullable();
            $table->string('position', 255)->nullable();     // = employees.job_en
            $table->string('department', 255)->nullable();   // = employees.dept_en

            // ช่องทางติดต่อ / โปรไฟล์ / reset (เผื่อใช้)
            $table->string('email', 255)->nullable();        // Bplus ว่าง — เก็บ null
            $table->string('profile_picture')->nullable();
            $table->string('reset_token', 100)->nullable();
            $table->timestamp('reset_token_expiry')->nullable();

            $table->timestamp('registered_at')->nullable();  // เวลาที่สร้างบัญชี (= เวลาปัจจุบันตอน sync เข้าใหม่)

            $table->rememberToken();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_users');
        Schema::dropIfExists('employees');
    }
};
