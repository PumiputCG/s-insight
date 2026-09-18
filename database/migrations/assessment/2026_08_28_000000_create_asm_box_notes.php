<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * คำอธิบายประกอบฟอร์มประเมิน (asm_box_notes) — admin เขียนที่หน้า "กำหนดคำถาม"
 * แล้วไปแสดงในแบบฟอร์มที่ผู้ประเมินเห็น (/assessment/evaluate/{emp}/{level})
 *
 * - ผูกได้ทั้ง "หัวข้อหลัก" (parent_id = null) และ "คอลัมน์ย่อย" ของ asm_score_boxes
 * - แยกตาม level = ลำดับผู้ประเมิน (Manager 2026-08-28: ลำดับ 1 กับ 2 เขียนคนละชุดได้)
 * - is_visible = สวิตช์เปิด/ปิดว่าจะให้ผู้ประเมินเห็นหรือไม่ (ปิด = ซ่อนโดยไม่ต้องลบข้อความ)
 * - image_path / file_path เตรียมไว้ล่วงหน้า ยังไม่เปิดใช้ในรอบนี้
 *   (Manager: "ทำเผื่อไว้ แต่ถ้าไม่มีการแนบ ก็ไม่ต้องไปแสดงหน้า User")
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('mysql_assessment')->create('asm_box_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('box_id')->constrained('asm_score_boxes')->cascadeOnDelete();
            $table->unsignedTinyInteger('level');            // ลำดับผู้ประเมิน 1–4
            $table->text('desc_th')->nullable();
            $table->text('desc_en')->nullable();
            $table->text('desc_my')->nullable();
            $table->string('image_path')->nullable();        // เผื่ออนาคต
            $table->string('file_path')->nullable();         // เผื่ออนาคต
            $table->boolean('is_visible')->default(true);
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->unique(['box_id', 'level'], 'uq_asm_note_box_level');
        });
    }

    public function down(): void
    {
        Schema::connection('mysql_assessment')->dropIfExists('asm_box_notes');
    }
};
