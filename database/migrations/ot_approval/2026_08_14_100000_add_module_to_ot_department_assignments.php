<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * แยกสิทธิ์ Foreman/Supervisor ของ OT ออกจากการลา
 *
 * เดิมทั้งสองระบบใช้แถวเดียวกัน ใครเป็น Foreman ของ OT จึงกลายเป็น Foreman ของการลาไปด้วย
 * เพิ่ม `module` แบบ additive: ของเดิมทั้งหมดเป็นของ OT แล้วคัดลอกเป็นชุดของการลาให้ตั้งต้น
 * ตามที่เจ้าของสั่ง จะได้ไม่มีใครเสียสิทธิ์ที่เคยมีตอนอัปเกรด
 */
return new class extends Migration
{
    private const CONN = 'mysql_ot_approval';

    private const TABLE = 'ot_department_assignments';

    public function up(): void
    {
        if (! Schema::connection(self::CONN)->hasTable(self::TABLE)) {
            return;
        }

        if (! Schema::connection(self::CONN)->hasColumn(self::TABLE, 'module')) {
            Schema::connection(self::CONN)->table(self::TABLE, function (Blueprint $table) {
                $table->string('module', 20)->default('ot')->after('id');
                $table->index(['module', 'company', 'dept_code', 'role'], 'idx_assignment_module_scope');
            });
        }

        /* unique เดิมไม่รวม module คนเดิมจึงเป็น Foreman ได้ระบบเดียว
           ต้องเปลี่ยนก่อนคัดลอกสิทธิ์ ไม่งั้นแถวของการลาจะชนกับของ OT */
        $indexes = collect(DB::connection(self::CONN)->select('SHOW INDEX FROM '.self::TABLE))
            ->pluck('Key_name')
            ->unique();

        if ($indexes->contains('ot_department_role_user_unique')) {
            Schema::connection(self::CONN)->table(self::TABLE, function (Blueprint $table) {
                $table->dropUnique('ot_department_role_user_unique');
            });
        }

        if (! $indexes->contains('ot_department_module_role_user_unique')) {
            Schema::connection(self::CONN)->table(self::TABLE, function (Blueprint $table) {
                $table->unique(
                    ['module', 'company', 'dept_code', 'role', 'app_user_id'],
                    'ot_department_module_role_user_unique',
                );
            });
        }

        // คัดลอกสิทธิ์ OT ที่มีอยู่ไปเป็นชุดของการลา เฉพาะแถวที่ยังไม่มีคู่ของตัวเอง
        $existing = DB::connection(self::CONN)->table(self::TABLE)->where('module', 'ot')->get();

        foreach ($existing as $row) {
            $duplicate = DB::connection(self::CONN)->table(self::TABLE)
                ->where('module', 'leave')
                ->where('company', $row->company)
                ->where('dept_code', $row->dept_code)
                ->where('role', $row->role)
                ->where('app_user_id', $row->app_user_id)
                ->exists();

            if ($duplicate) {
                continue;
            }

            DB::connection(self::CONN)->table(self::TABLE)->insert([
                'module' => 'leave',
                'company' => $row->company,
                'dept_code' => $row->dept_code,
                'role' => $row->role,
                'shift_group' => $row->shift_group,
                'app_user_id' => $row->app_user_id,
                'employee_code' => $row->employee_code,
                'assigned_by' => $row->assigned_by,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        if (! Schema::connection(self::CONN)->hasTable(self::TABLE)) {
            return;
        }

        DB::connection(self::CONN)->table(self::TABLE)->where('module', 'leave')->delete();

        if (Schema::connection(self::CONN)->hasColumn(self::TABLE, 'module')) {
            Schema::connection(self::CONN)->table(self::TABLE, function (Blueprint $table) {
                $table->dropUnique('ot_department_module_role_user_unique');
                $table->dropIndex('idx_assignment_module_scope');
                $table->dropColumn('module');
                $table->unique(['company', 'dept_code', 'role', 'app_user_id'], 'ot_department_role_user_unique');
            });
        }
    }
};
