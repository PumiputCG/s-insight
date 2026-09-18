<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * หัวข้อ 3 สัดส่วนและการคำนวณ — ชนิดการคิดของหัวข้อหลัก (asm_score_boxes ระดับ parent_id = null)
 *
 *   calc_mode: percent = สัดส่วน (weight เก็บ %) | add = นำคะแนนมาบวก | deduct = นำคะแนนมาหักลบ
 *   null = ยังไม่ตั้ง (UI แสดงเป็น "สัดส่วน" โดยยังไม่กรอก %)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('mysql_assessment')->table('asm_score_boxes', function (Blueprint $table) {
            $table->string('calc_mode', 10)->nullable()->after('weight');
        });
    }

    public function down(): void
    {
        Schema::connection('mysql_assessment')->table('asm_score_boxes', function (Blueprint $table) {
            $table->dropColumn('calc_mode');
        });
    }
};
