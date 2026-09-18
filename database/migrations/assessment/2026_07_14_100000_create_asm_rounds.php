<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * รอบประเมิน (asm_rounds) — admin ต้องเปิดรอบก่อนถึงจัดการระบบได้
 *
 * ข้อมูลที่แยกตามรอบ (รีเซ็ตเมื่อเปิดรอบใหม่ / switch กลับไปดูรอบเดิมได้):
 *   - คะแนน (asm_employee_scores) · ผู้ประเมิน/ลำดับชั้น (asm_hierarchy) · ระดับตำแหน่ง (asm_position_levels)
 * ข้อมูล global ทุกโรบ: โครงคอลัมน์ + สัดส่วน (asm_score_boxes / asm_level_props) + คำถาม
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('mysql_assessment')->create('asm_rounds', function (Blueprint $table) {
            $table->id();
            $table->string('name');                              // เช่น "ประเมินประจำปี 2026 รอบ 1"
            $table->unsignedSmallInteger('year');                // ปีของรอบ (แท็บย่อยผลลัพธ์)
            $table->string('status', 10)->default('open');       // open | closed
            $table->unsignedBigInteger('opened_by')->nullable();
            $table->string('opened_by_name')->nullable();
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
            $table->index(['year', 'status']);
        });

        foreach (['asm_employee_scores', 'asm_hierarchy', 'asm_position_levels'] as $t) {
            Schema::connection('mysql_assessment')->table($t, function (Blueprint $table) {
                $table->unsignedBigInteger('round_id')->nullable()->after('id')->index();
            });
        }

        // รอบเริ่มต้น: ผูกข้อมูลเดิมทั้งหมดไว้กับรอบแรก (เปิดอยู่)
        $now = now();
        $roundId = DB::connection('mysql_assessment')->table('asm_rounds')->insertGetId([
            'name' => 'รอบเริ่มต้น '.$now->year,
            'year' => $now->year,
            'status' => 'open',
            'opened_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        foreach (['asm_employee_scores', 'asm_hierarchy', 'asm_position_levels'] as $t) {
            DB::connection('mysql_assessment')->table($t)->update(['round_id' => $roundId]);
        }

        // unique เดิม (ต่อคน) → ต่อรอบ+คน
        Schema::connection('mysql_assessment')->table('asm_employee_scores', function (Blueprint $table) {
            $table->dropUnique('uq_asm_emp_box');
            $table->unique(['round_id', 'employee_code', 'box_id'], 'uq_asm_round_emp_box');
        });
        Schema::connection('mysql_assessment')->table('asm_hierarchy', function (Blueprint $table) {
            $table->dropUnique('asm_hierarchy_employee_code_unique');
            $table->unique(['round_id', 'employee_code'], 'uq_asm_hier_round_emp');
        });
        Schema::connection('mysql_assessment')->table('asm_position_levels', function (Blueprint $table) {
            $table->dropUnique('asm_position_levels_job_code_unique');
            $table->unique(['round_id', 'job_code'], 'uq_asm_lvl_round_job');
        });
    }

    public function down(): void
    {
        Schema::connection('mysql_assessment')->table('asm_employee_scores', function (Blueprint $table) {
            $table->dropUnique('uq_asm_round_emp_box');
            $table->unique(['employee_code', 'box_id'], 'uq_asm_emp_box');
            $table->dropColumn('round_id');
        });
        Schema::connection('mysql_assessment')->table('asm_hierarchy', function (Blueprint $table) {
            $table->dropUnique('uq_asm_hier_round_emp');
            $table->unique('employee_code');
            $table->dropColumn('round_id');
        });
        Schema::connection('mysql_assessment')->table('asm_position_levels', function (Blueprint $table) {
            $table->dropUnique('uq_asm_lvl_round_job');
            $table->unique('job_code');
            $table->dropColumn('round_id');
        });
        Schema::connection('mysql_assessment')->dropIfExists('asm_rounds');
    }
};
