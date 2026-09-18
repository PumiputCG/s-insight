<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * คำถามประเมิน (แท็บกำหนดคำถาม) — ผูกกับคอลัมน์ชนิด Input × ลำดับผู้ประเมิน
 * admin กำหนดว่าลำดับ 1 เห็นคำถามชุดไหน / ลำดับ 2 เห็นชุดไหน · รองรับ TH/EN/MY
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('mysql_assessment')->create('asm_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('box_id')->constrained('asm_score_boxes')->cascadeOnDelete();
            $table->unsignedTinyInteger('level');                // ลำดับผู้ประเมิน 1–4 ที่จะเห็นคำถามนี้
            $table->unsignedInteger('sort')->default(0);
            $table->text('q_th');
            $table->text('q_en')->nullable();
            $table->text('q_my')->nullable();
            $table->timestamps();
            $table->index(['box_id', 'level']);
        });
    }

    public function down(): void
    {
        Schema::connection('mysql_assessment')->dropIfExists('asm_questions');
    }
};
