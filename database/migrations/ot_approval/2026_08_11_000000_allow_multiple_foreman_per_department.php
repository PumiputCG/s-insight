<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * เปิดให้กำหนด Foreman ได้หลายคนต่อบริษัท+แผนก และติดป้ายกะให้แต่ละคน
 *
 * ของเดิม unique (company, dept_code, role) บังคับไว้ 1 บทบาท = 1 คน
 * เปลี่ยนเป็นรวม app_user_id เข้าไปด้วย จึงยังกันคนเดิมซ้ำในแผนกเดิมได้
 * แต่เพิ่มคนใหม่ได้
 *
 * ข้อจำกัด "Supervisor 1 คนต่อแผนก" ยังอยู่ตามที่ Manager สั่ง แต่ MySQL ทำ
 * partial unique index ไม่ได้ จึงบังคับที่ระดับโค้ดใน OtApprovalSettingController
 * แทน ดูหัวข้อ 25.2 ใน OT_APPROVAL_SYSTEM.md
 */
return new class extends Migration
{
    private const CONN = 'mysql_ot_approval';

    public function up(): void
    {
        Schema::connection(self::CONN)->table('ot_department_assignments', function (Blueprint $table) {
            $table->dropUnique('ot_department_role_unique');
        });

        Schema::connection(self::CONN)->table('ot_department_assignments', function (Blueprint $table) {
            // ป้ายกะที่ Admin เลือกให้ Foreman — ใช้แสดงผลอย่างเดียว ไม่ใช่สิทธิ์
            $table->string('shift_group', 20)->nullable()->after('role');

            $table->unique(
                ['company', 'dept_code', 'role', 'app_user_id'],
                'ot_department_role_user_unique',
            );
        });
    }

    public function down(): void
    {
        Schema::connection(self::CONN)->table('ot_department_assignments', function (Blueprint $table) {
            $table->dropUnique('ot_department_role_user_unique');
            $table->dropColumn('shift_group');
        });

        Schema::connection(self::CONN)->table('ot_department_assignments', function (Blueprint $table) {
            $table->unique(['company', 'dept_code', 'role'], 'ot_department_role_unique');
        });
    }
};
