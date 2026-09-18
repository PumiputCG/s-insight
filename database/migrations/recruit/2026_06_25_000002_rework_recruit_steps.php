<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * เปลี่ยนโครงสร้าง Recruit roles → "ขั้นเส้นทาง (steps)" ที่เรียงลำดับได้
 *
 * - recruit_steps : 1 step = 1 การ์ดในเส้นทาง (position + role ที่ admin เลือกเอง)
 * - recruit_members : ผูกกับ step (step_id) แทน role ; เพิ่ม department + dept_codes (DCC คุมแผนกใดบ้าง)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('mysql_recruit')->create('recruit_steps', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('position')->default(0);
            $table->string('role')->nullable(); // dcc | manager | hr_manager | recruit (admin เลือกเอง)
            $table->timestamps();
        });

        Schema::connection('mysql_recruit')->table('recruit_members', function (Blueprint $table) {
            $table->unsignedBigInteger('step_id')->nullable()->after('id')->index();
            $table->string('department')->nullable()->after('position');
            $table->json('dept_codes')->nullable()->after('department'); // DCC: แผนกที่มองเห็นได้ (หลายแผนก)
        });

        // ย้ายข้อมูลเดิม (role-based) → สร้าง step ต่อ role แล้วผูก member
        $order = ['dcc', 'manager', 'hr_manager', 'recruit'];
        $existing = DB::connection('mysql_recruit')->table('recruit_members')->distinct()->pluck('role')->all();
        $pos = 1;
        foreach ($order as $r) {
            if (in_array($r, $existing, true)) {
                $id = DB::connection('mysql_recruit')->table('recruit_steps')->insertGetId([
                    'position' => $pos++, 'role' => $r, 'created_at' => now(), 'updated_at' => now(),
                ]);
                DB::connection('mysql_recruit')->table('recruit_members')->where('role', $r)->update(['step_id' => $id]);
            }
        }

        Schema::connection('mysql_recruit')->table('recruit_members', function (Blueprint $table) {
            $table->dropUnique(['app_user_id', 'role']);
        });
        Schema::connection('mysql_recruit')->table('recruit_members', function (Blueprint $table) {
            $table->dropColumn('role');
            $table->unique(['step_id', 'app_user_id']);
        });
    }

    public function down(): void
    {
        Schema::connection('mysql_recruit')->table('recruit_members', function (Blueprint $table) {
            $table->dropUnique(['step_id', 'app_user_id']);
            $table->string('role')->nullable();
            $table->dropColumn(['step_id', 'department', 'dept_codes']);
        });
        Schema::connection('mysql_recruit')->dropIfExists('recruit_steps');
    }
};
