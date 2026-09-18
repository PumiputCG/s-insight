<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * คอลัมน์ชนิด Input รองรับผู้ประเมินได้ถึง 4 ลำดับ — เพิ่มช่องเก็บคะแนนที่ 3,4
 * (value = ช่องที่ 1, value2 = ช่องที่ 2 ตามตำแหน่งของลำดับที่ตั้งใน input_levels)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('mysql_assessment')->table('asm_employee_scores', function (Blueprint $table) {
            $table->decimal('value3', 16, 4)->nullable()->after('value2');
            $table->decimal('value4', 16, 4)->nullable()->after('value3');
        });
    }

    public function down(): void
    {
        Schema::connection('mysql_assessment')->table('asm_employee_scores', function (Blueprint $table) {
            $table->dropColumn(['value3', 'value4']);
        });
    }
};
