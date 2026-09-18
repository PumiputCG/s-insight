<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * หัวข้อ 3.2 การคำนวณคอลัมน์รอง — ชนิดข้อมูล Attendance มี 2 รูปแบบ
 *
 *   type (คอลัมน์เดิม) = ชนิดข้อมูลของคอลัมน์รอง: score (คะแนน) | attendance
 *   att_form  = รูปแบบ Attendance: score (จำนวน × rate หักจากคะแนนตั้งต้น 100) | grade (จำกัดเกรดสูงสุด)
 *   rate      = อัตราต่อหน่วย เช่น -0.25, -3 (ใช้เมื่อ att_form = score)
 *   grade_cap = เกรดสูงสุดที่ได้ เช่น C (หนังสือเตือน), D (พักงาน) (ใช้เมื่อ att_form = grade)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('mysql_assessment')->table('asm_score_boxes', function (Blueprint $table) {
            $table->string('att_form', 10)->nullable()->after('weight');
            $table->decimal('rate', 8, 2)->nullable()->after('att_form');
            $table->string('grade_cap', 2)->nullable()->after('rate');
        });
    }

    public function down(): void
    {
        Schema::connection('mysql_assessment')->table('asm_score_boxes', function (Blueprint $table) {
            $table->dropColumn(['att_form', 'rate', 'grade_cap']);
        });
    }
};
