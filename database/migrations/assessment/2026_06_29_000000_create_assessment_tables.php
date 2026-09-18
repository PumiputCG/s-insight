<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ตารางระบบ Assessment (เก็บใน insight_assessment — connection mysql_assessment)
 *
 * - asm_members        : สิทธิ์เข้าระบบ + role (admin มอบ role HR) — เหมือน recruit_members
 * - asm_score_boxes    : "กล่องคะแนน" ที่ admin/HR สร้างเอง (ชื่อ + สัดส่วนน้ำหนัก) = 1 คอลัมน์ใน Excel
 * - asm_employee_scores: คะแนนรายพนักงานต่อกล่อง (EAV, ทศนิยม 4 ตำแหน่ง) — import หรือแก้ในระบบได้
 * - asm_import_batches : log การ import แต่ละครั้ง (กี่แถว match/ไม่ match)
 *
 * อ้าง employees/app_users จาก core (insight) แบบ logical id (read-only) ไม่ใช้ FK ข้าม DB
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('mysql_assessment')->create('asm_members', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('app_user_id')->index();   // อ้าง insight.app_users.id (logical)
            $table->string('employee_code')->nullable();          // snapshot
            $table->string('display_name')->nullable();           // snapshot
            $table->string('position')->nullable();               // snapshot ตำแหน่ง
            $table->string('role')->default('hr');                // hr (admin มอบให้) — เผื่อขยาย supervisor/division
            $table->unsignedBigInteger('assigned_by')->nullable(); // admin app_user_id
            $table->timestamps();

            $table->unique('app_user_id');
            $table->index('role');
        });

        Schema::connection('mysql_assessment')->create('asm_score_boxes', function (Blueprint $table) {
            $table->id();
            $table->string('name');                               // ชื่อกล่อง = หัวคอลัมน์ใน Excel (ต้องตรงกัน)
            $table->string('code')->nullable();                   // slug สำรอง
            $table->decimal('weight', 8, 4)->default(0);          // สัดส่วนน้ำหนักคะแนน
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();

            $table->index('sort');
        });

        Schema::connection('mysql_assessment')->create('asm_employee_scores', function (Blueprint $table) {
            $table->id();
            $table->string('employee_code')->index();             // อ้าง insight.employees.employee_code (logical)
            $table->unsignedBigInteger('box_id')->index();
            $table->decimal('value', 16, 4)->nullable();          // ทศนิยม 4 ตำแหน่ง ; null = ยังไม่มีคะแนน
            $table->unsignedBigInteger('updated_by')->nullable(); // app_user_id ที่แก้ล่าสุด
            $table->timestamps();

            $table->unique(['employee_code', 'box_id'], 'uq_asm_emp_box');
            $table->foreign('box_id')->references('id')->on('asm_score_boxes')->cascadeOnDelete();
        });

        Schema::connection('mysql_assessment')->create('asm_import_batches', function (Blueprint $table) {
            $table->id();
            $table->string('filename')->nullable();
            $table->unsignedBigInteger('uploaded_by')->nullable();
            $table->unsignedInteger('total_rows')->default(0);
            $table->unsignedInteger('matched_rows')->default(0);
            $table->unsignedInteger('updated_cells')->default(0);
            $table->json('columns_matched')->nullable();          // หัวคอลัมน์ที่ map ได้
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection('mysql_assessment')->dropIfExists('asm_employee_scores');
        Schema::connection('mysql_assessment')->dropIfExists('asm_import_batches');
        Schema::connection('mysql_assessment')->dropIfExists('asm_score_boxes');
        Schema::connection('mysql_assessment')->dropIfExists('asm_members');
    }
};
