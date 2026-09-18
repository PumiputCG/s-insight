<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * รองรับคำอธิบายของ "จุดพิเศษ" ในฟอร์มที่ไม่ใช่คอลัมน์คะแนน (Manager 2026-08-31)
 *
 * จุดแรกที่ใช้คือ slot = 'total' → คำอธิบายข้าง "คะแนนรวม" ในหัวฟอร์ม
 * box_id จึงต้อง nullable (แถวชนิด slot ไม่ผูกกับ asm_score_boxes)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('mysql_assessment')->table('asm_box_notes', function (Blueprint $table) {
            $table->dropForeign(['box_id']);
        });

        Schema::connection('mysql_assessment')->table('asm_box_notes', function (Blueprint $table) {
            $table->unsignedBigInteger('box_id')->nullable()->change();
            $table->string('slot', 32)->nullable()->after('box_id');
            $table->foreign('box_id')->references('id')->on('asm_score_boxes')->cascadeOnDelete();
            $table->unique(['slot', 'level'], 'uq_asm_note_slot_level');
        });
    }

    public function down(): void
    {
        Schema::connection('mysql_assessment')->table('asm_box_notes', function (Blueprint $table) {
            $table->dropUnique('uq_asm_note_slot_level');
            $table->dropForeign(['box_id']);
            $table->dropColumn('slot');
        });

        Schema::connection('mysql_assessment')->table('asm_box_notes', function (Blueprint $table) {
            $table->unsignedBigInteger('box_id')->nullable(false)->change();
            $table->foreign('box_id')->references('id')->on('asm_score_boxes')->cascadeOnDelete();
        });
    }
};
