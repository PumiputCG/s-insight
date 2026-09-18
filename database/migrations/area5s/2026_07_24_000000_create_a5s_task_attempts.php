<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ประวัติการส่ง→ตรวจต่อครั้ง (append-only) — เก็บทุก attempt ของ task ไม่ถูกเขียนทับ
 * ใช้: (1) โชว์ครั้งก่อนที่ไม่ผ่าน + เหตุผลผู้ตรวจ (ไม่เก็บภาพ)  (2) คิด % (ผ่าน=100/ไม่ผ่าน=0)
 */
return new class extends Migration
{
    private const CONN = 'mysql_area5s';

    public function up(): void
    {
        Schema::connection(self::CONN)->create('a5s_task_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained('a5s_tasks')->cascadeOnDelete();
            $table->unsignedInteger('seq');            // ครั้งที่ส่งภายใน task (1,2,3…)
            $table->string('result', 10);              // pass | fail
            $table->text('fail_reason')->nullable();
            $table->text('advice')->nullable();
            $table->json('cards_json')->nullable();    // snapshot [{title, detail}] — ไม่เก็บภาพ
            $table->unsignedBigInteger('evaluated_by')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('evaluated_at')->nullable();
            $table->timestamps();

            $table->index(['task_id', 'seq']);
        });
    }

    public function down(): void
    {
        Schema::connection(self::CONN)->dropIfExists('a5s_task_attempts');
    }
};
