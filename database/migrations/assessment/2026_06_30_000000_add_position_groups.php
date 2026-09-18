<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * หมวดตำแหน่ง (asm_position_groups) — "card" ที่รวมหลายตำแหน่งไว้ด้วยกัน
 * + ผูกกล่องคะแนน (asm_score_boxes.group_id) ให้อยู่ใต้หมวด
 *
 * หมายเหตุ: เป็นแค่การจัดกลุ่ม "สัดส่วนคะแนน" ตามตำแหน่ง — ไม่ได้แบ่งพนักงานออกจากกัน
 * ตารางคะแนนรายพนักงานยังเป็นก้อนเดียว (ทุกคนรวมกัน)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('mysql_assessment')->create('asm_position_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name');                 // ชื่อหมวด เช่น "ระดับปฏิบัติการ"
            $table->json('job_codes')->nullable();  // ตำแหน่ง (job_code) ที่อยู่ในหมวดนี้ (หลายตำแหน่ง)
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();

            $table->index('sort');
        });

        Schema::connection('mysql_assessment')->table('asm_score_boxes', function (Blueprint $table) {
            $table->unsignedBigInteger('group_id')->nullable()->after('id')->index();
            $table->foreign('group_id')->references('id')->on('asm_position_groups')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::connection('mysql_assessment')->table('asm_score_boxes', function (Blueprint $table) {
            $table->dropForeign(['group_id']);
            $table->dropColumn('group_id');
        });

        Schema::connection('mysql_assessment')->dropIfExists('asm_position_groups');
    }
};
