<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * เปิดให้ audit row มี "ผู้ตัดสิน = ระบบ" ได้
 *
 * `OtRequestWorkflowService::refreshAttendance()` ปฏิเสธคำขออัตโนมัติเมื่อเวลาสแกน
 * ไม่ครอบคลุมช่วง OT แล้วเขียน audit ด้วย `actor_app_user_id = null` เพราะไม่มีคนกด
 * แต่คอลัมน์นี้ถูกสร้างเป็น NOT NULL ตั้งแต่ migration แรก
 * MySQL ของโปรเจคเปิด STRICT_TRANS_TABLES ทำให้ insert ระเบิดเป็น SQLSTATE[23000]
 * ทุกครั้งที่มีคำขอสถานะ `submitted` ตกเกณฑ์เวลา — ล้มทั้งหน้าอนุมัติและหน้าขอ OT
 *
 * ตารางฝั่งคำขอยอมรับ null อยู่แล้ว (`ot_requests.decided_by_app_user_id`)
 * แถวนี้จึงแค่ทำให้ audit สอดคล้องกัน ไม่เปลี่ยนความหมายของข้อมูลเดิม
 */
return new class extends Migration
{
    private const CONN = 'mysql_ot_approval';

    private const TABLE = 'ot_request_approvals';

    public function up(): void
    {
        if (! Schema::connection(self::CONN)->hasTable(self::TABLE)) {
            return;
        }

        Schema::connection(self::CONN)->table(self::TABLE, function (Blueprint $table) {
            $table->unsignedBigInteger('actor_app_user_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        /* ย้อนกลับไม่ได้อย่างปลอดภัย: ถ้ามี audit ของระบบอยู่แล้วการบังคับ NOT NULL
           จะทำให้ migrate:rollback ล้ม จึงปล่อยให้ยอมรับ null ต่อไป */
    }
};
