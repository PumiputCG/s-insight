<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * หัวข้อ 3.1 กำหนดสัดส่วนของระดับ — ตั้งค่าต่อ "ระดับ (1–5) × หัวข้อหลัก" (ระดับ 0 ไม่คำนวณ)
 *
 *   mode: percent = สัดส่วน (weight เก็บตัวเลข รวมกันควรได้ 100 ต่อระดับ)
 *         extra   = เพิ่มเติม (ค่าคอลัมน์ +/- รวมแยกกับคะแนนสัดส่วนโดยตรง)
 *
 * แทนที่ asm_score_boxes.calc_mode (ตั้งรวมทุกระดับ) — ย้ายค่าที่ตั้งไว้ไป seed ทุกระดับ 1–5 ก่อน drop
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('mysql_assessment')->create('asm_level_props', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('level');                      // 1–5
            $table->foreignId('box_id')->constrained('asm_score_boxes')->cascadeOnDelete();
            $table->string('mode', 10)->default('percent');
            $table->decimal('weight', 8, 2)->default(0);
            $table->timestamps();
            $table->unique(['level', 'box_id']);
        });

        // ย้ายค่า calc_mode/weight เดิม (ตั้งรวม) ไปเป็นค่าตั้งต้นของทุกระดับ 1–5
        $now = now();
        $boxes = DB::connection('mysql_assessment')->table('asm_score_boxes')
            ->whereNull('parent_id')->whereNotNull('calc_mode')->get();
        foreach ($boxes as $box) {
            foreach (range(1, 5) as $level) {
                DB::connection('mysql_assessment')->table('asm_level_props')->insert([
                    'level' => $level,
                    'box_id' => $box->id,
                    'mode' => $box->calc_mode === 'extra' ? 'extra' : 'percent',
                    'weight' => (float) $box->weight,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        Schema::connection('mysql_assessment')->table('asm_score_boxes', function (Blueprint $table) {
            $table->dropColumn('calc_mode');
        });
    }

    public function down(): void
    {
        Schema::connection('mysql_assessment')->table('asm_score_boxes', function (Blueprint $table) {
            $table->string('calc_mode', 10)->nullable()->after('weight');
        });

        Schema::connection('mysql_assessment')->dropIfExists('asm_level_props');
    }
};
