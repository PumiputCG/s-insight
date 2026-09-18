<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 3.2 — คะแนนเต็มของคอลัมน์รองชนิด "คะแนน"/"Input" (เช่น 10, 5, 100)
 * ใช้แสดง summary `_/10` และ normalize ตอนคิดสัดส่วนในอนาคต
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('mysql_assessment')->table('asm_score_boxes', function (Blueprint $table) {
            $table->decimal('full_score', 8, 2)->nullable()->after('grade_cap');
        });
    }

    public function down(): void
    {
        Schema::connection('mysql_assessment')->table('asm_score_boxes', function (Blueprint $table) {
            $table->dropColumn('full_score');
        });
    }
};
