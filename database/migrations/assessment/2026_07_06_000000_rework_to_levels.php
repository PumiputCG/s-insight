<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * รื้อเป็นโมเดล "คอลัมน์ชุดเดียว (global) + ระดับตำแหน่ง 1–5"
 *
 * - เลิกใช้ asm_position_groups (คอลัมน์ผูกตามหมวด) → คอลัมน์เป็น global ใช้ร่วมทุกคน
 * - asm_score_boxes ตัด group_id ทิ้ง (เหลือ parent_id สำหรับคอลัมน์ใหญ่/ย่อย)
 * - เพิ่ม asm_position_levels : map ตำแหน่ง (job_code) → ระดับ 1–5 (การ์ดระดับ)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('mysql_assessment')->table('asm_score_boxes', function (Blueprint $table) {
            $table->dropForeign(['group_id']);
            $table->dropColumn('group_id');
        });

        Schema::connection('mysql_assessment')->dropIfExists('asm_position_groups');

        Schema::connection('mysql_assessment')->create('asm_position_levels', function (Blueprint $table) {
            $table->id();
            $table->string('job_code')->unique();        // ตำแหน่งจาก employees
            $table->unsignedTinyInteger('level');        // 1–5
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection('mysql_assessment')->dropIfExists('asm_position_levels');

        Schema::connection('mysql_assessment')->create('asm_position_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->decimal('full_score', 8, 2)->default(100);
            $table->json('job_codes')->nullable();
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::connection('mysql_assessment')->table('asm_score_boxes', function (Blueprint $table) {
            $table->unsignedBigInteger('group_id')->nullable()->after('id')->index();
            $table->foreign('group_id')->references('id')->on('asm_position_groups')->nullOnDelete();
        });
    }
};
