<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 3.2 — คอลัมน์ชนิด Input เลือกได้ว่าลำดับชั้นไหนเป็นผู้ประเมิน (เช่น "1,2") กด + เพิ่มได้ถึงลำดับ 4
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('mysql_assessment')->table('asm_score_boxes', function (Blueprint $table) {
            $table->string('input_levels', 20)->nullable()->after('full_score');
        });
    }

    public function down(): void
    {
        Schema::connection('mysql_assessment')->table('asm_score_boxes', function (Blueprint $table) {
            $table->dropColumn('input_levels');
        });
    }
};
