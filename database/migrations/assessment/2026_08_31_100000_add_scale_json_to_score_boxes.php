<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ตัวเลือกคะแนนของคอลัมน์ชนิด Input (Manager 2026-08-31)
 *
 * เดิมชุด "เสมอ 10,9,8 · บ่อย 7,6,5 · ..." ฮาร์ดโค้ดอยู่ใน evaluate-form.blade.php
 * ย้ายมาเก็บเป็น JSON ต่อคอลัมน์ เพื่อให้ admin เพิ่ม/ลบตัวเลือกและคะแนนเองได้
 *
 * โครง: [{"th":"เสมอ","en":"Always","my":"...","scores":["10","9","8"],"na":false}, ...]
 * null = ยังไม่ตั้ง → ใช้ชุดเริ่มต้นใน AsmScoreBox::defaultScale()
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('mysql_assessment')->table('asm_score_boxes', function (Blueprint $table) {
            $table->json('scale_json')->nullable()->after('input_levels');
        });
    }

    public function down(): void
    {
        Schema::connection('mysql_assessment')->table('asm_score_boxes', function (Blueprint $table) {
            $table->dropColumn('scale_json');
        });
    }
};
