<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * rate ของ 3.2 รองรับทศนิยม 2–3 ตำแหน่ง (เช่น -0.125) — ขยายจาก decimal(8,2) เป็น (8,3)
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::connection('mysql_assessment')->statement('ALTER TABLE asm_score_boxes MODIFY rate DECIMAL(8,3) NULL');
    }

    public function down(): void
    {
        DB::connection('mysql_assessment')->statement('ALTER TABLE asm_score_boxes MODIFY rate DECIMAL(8,2) NULL');
    }
};
