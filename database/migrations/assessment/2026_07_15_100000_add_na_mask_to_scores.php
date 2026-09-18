<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * หัวข้อ 4/ผลลัพธ์ — ผู้ใช้พิมพ์ "N/A" ในช่องคะแนน = ตั้งใจไม่คำนวณช่องนั้น (ค่าเท่ากับว่าง แต่ต้องแสดง N/A ค้างไว้)
 * na_mask = bitmask ราย slot (bit0=value, bit1=value2, bit2=value3, bit3=value4) เช่น "8,N/A" → value=8, na_mask=2
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::connection('mysql_assessment')->hasColumn('asm_employee_scores', 'na_mask')) {
            return;
        }
        Schema::connection('mysql_assessment')->table('asm_employee_scores', function (Blueprint $table) {
            $table->unsignedTinyInteger('na_mask')->default(0)->after('value4');
        });
    }

    public function down(): void
    {
        Schema::connection('mysql_assessment')->table('asm_employee_scores', function (Blueprint $table) {
            $table->dropColumn('na_mask');
        });
    }
};
