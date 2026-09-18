<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * เปิดให้ยกเลิกคำขอที่ส่งไปแล้วแต่ยังไม่มีการตัดสิน โดยยังเก็บประวัติไว้
 *
 * เดิมถอยคำขอได้เฉพาะตอนเป็นร่าง และวิธีถอยคือ `delete()` จริง ๆ
 * ตาราง audit ตั้ง `cascadeOnDelete()` ไว้ การลบจึงพาประวัติหายไปทั้งก้อน
 * ตรวจย้อนไม่ได้ว่าเคยส่งคำขอนั้นให้ Supervisor ทั้งที่อีเมลแจ้งออกไปแล้ว
 *
 * จึงเปลี่ยนเป็น "ยกเลิก" — เก็บแถวไว้แต่เปลี่ยน `approval_status` เป็น `cancelled`
 * พร้อมเหตุผลและผู้กดยกเลิก ส่วนหน้าจอผู้ใช้จะกรองออกให้เหมือนหายไปแล้ว
 *
 * `approval_status` เป็น varchar(20) อยู่แล้ว ไม่ใช่ enum จึงรับค่าใหม่ได้ทันที
 * ไม่ต้องแก้ชนิดคอลัมน์และไม่กระทบข้อมูลเดิม
 */
return new class extends Migration
{
    private const CONN = 'mysql_ot_approval';

    private const TABLES = ['ot_requests', 'leave_requests'];

    public function up(): void
    {
        foreach (self::TABLES as $table) {
            if (! Schema::connection(self::CONN)->hasTable($table)) {
                continue;
            }

            Schema::connection(self::CONN)->table($table, function (Blueprint $blueprint) use ($table) {
                if (! Schema::connection(self::CONN)->hasColumn($table, 'cancelled_at')) {
                    $blueprint->timestamp('cancelled_at')->nullable()->after('decided_at');
                }
                if (! Schema::connection(self::CONN)->hasColumn($table, 'cancel_reason')) {
                    // เหตุผลบังคับกรอกที่หน้าจอ แต่เปิด null ไว้เผื่อข้อมูลเก่าและการยกเลิกโดยระบบ
                    $blueprint->string('cancel_reason', 500)->nullable()->after('cancelled_at');
                }
                if (! Schema::connection(self::CONN)->hasColumn($table, 'cancelled_by_app_user_id')) {
                    $blueprint->unsignedBigInteger('cancelled_by_app_user_id')->nullable()->after('cancel_reason');
                }
                if (! Schema::connection(self::CONN)->hasColumn($table, 'cancelled_by_employee_code')) {
                    $blueprint->string('cancelled_by_employee_code', 20)->nullable()->after('cancelled_by_app_user_id');
                }
            });
        }
    }

    public function down(): void
    {
        foreach (self::TABLES as $table) {
            if (! Schema::connection(self::CONN)->hasTable($table)) {
                continue;
            }

            Schema::connection(self::CONN)->table($table, function (Blueprint $blueprint) {
                $blueprint->dropColumn([
                    'cancelled_at',
                    'cancel_reason',
                    'cancelled_by_app_user_id',
                    'cancelled_by_employee_code',
                ]);
            });
        }
    }
};
