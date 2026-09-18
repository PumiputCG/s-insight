<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ลำดับชั้นผู้ประเมินต่อพนักงาน (asm_hierarchy) — HR กรอกเอง (Bplus ไม่มีผังสายบังคับบัญชา)
 *
 * l1 = ลำดับชั้น 1 (Supervisor) · l2 = ลำดับชั้น 2 (Division MGR)
 * l3 = ลำดับชั้น 3 (Dept MGR)  · l4 = ลำดับชั้น 4 (Plant MGR) — เก็บ id + ชื่อ
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('mysql_assessment')->create('asm_hierarchy', function (Blueprint $table) {
            $table->id();
            $table->string('employee_code')->unique();
            $table->string('l1_id')->nullable();
            $table->string('l1_name')->nullable();
            $table->string('l2_id')->nullable();
            $table->string('l2_name')->nullable();
            $table->string('l3_id')->nullable();
            $table->string('l3_name')->nullable();
            $table->string('l4_id')->nullable();
            $table->string('l4_name')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection('mysql_assessment')->dropIfExists('asm_hierarchy');
    }
};
