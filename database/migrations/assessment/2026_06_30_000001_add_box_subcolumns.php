<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * คอลัมน์ย่อยของกล่องคะแนน + ประเภทกล่อง + คะแนนเต็มต่อหมวด
 *
 * - asm_score_boxes.parent_id : กล่องนี้เป็น "คอลัมน์ย่อย" ของกล่องอื่น (self-ref) — null = คอลัมน์ใหญ่/เดี่ยว
 * - asm_score_boxes.type      : ประเภท เช่น score / attendance / okr / bonus (สูตรคำนวณเฟสถัดไป)
 * - asm_position_groups.full_score : คะแนนเต็มของหมวด (เช่น 100 หรือ 105) ใช้แสดง ตั้งไปแล้ว/เหลือ
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('mysql_assessment')->table('asm_score_boxes', function (Blueprint $table) {
            $table->unsignedBigInteger('parent_id')->nullable()->after('group_id')->index();
            $table->string('type')->default('score')->after('name');
            $table->foreign('parent_id')->references('id')->on('asm_score_boxes')->cascadeOnDelete();
        });

        Schema::connection('mysql_assessment')->table('asm_position_groups', function (Blueprint $table) {
            $table->decimal('full_score', 8, 2)->default(100)->after('name');
        });
    }

    public function down(): void
    {
        Schema::connection('mysql_assessment')->table('asm_score_boxes', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
            $table->dropColumn(['parent_id', 'type']);
        });

        Schema::connection('mysql_assessment')->table('asm_position_groups', function (Blueprint $table) {
            $table->dropColumn('full_score');
        });
    }
};
