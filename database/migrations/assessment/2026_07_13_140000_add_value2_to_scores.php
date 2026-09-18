<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * หัวข้อ 4 — คอลัมน์ชนิด Input รับคะแนนผู้ประเมิน 2 คน (ลำดับ 1 = Supervisor, ลำดับ 2 = Division)
 * ในช่องเดียวรูปแบบ "8.5,7.5" → value = คนที่ 1, value2 = คนที่ 2
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('mysql_assessment')->table('asm_employee_scores', function (Blueprint $table) {
            $table->decimal('value2', 16, 4)->nullable()->after('value');
        });
    }

    public function down(): void
    {
        Schema::connection('mysql_assessment')->table('asm_employee_scores', function (Blueprint $table) {
            $table->dropColumn('value2');
        });
    }
};
