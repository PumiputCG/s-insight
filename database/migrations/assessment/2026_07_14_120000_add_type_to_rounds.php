<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * แยกรอบเป็น 2 ชนิด: employee (ประเมินพนักงาน) / self (ประเมินตัวเอง)
 * เปิดได้ทีละรอบต่อชนิด — รอบเดิมทั้งหมดเป็น employee
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('mysql_assessment')->table('asm_rounds', function (Blueprint $table) {
            $table->string('type', 12)->default('employee')->after('id')->index();
        });
    }

    public function down(): void
    {
        Schema::connection('mysql_assessment')->table('asm_rounds', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};
