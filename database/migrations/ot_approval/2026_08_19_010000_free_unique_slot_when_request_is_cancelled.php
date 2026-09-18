<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * ปลดล็อกช่อง unique ให้ขอใหม่ได้หลังยกเลิกคำขอของวันเดียวกัน
 *
 * รอบก่อนเปลี่ยนจาก "ลบแถว" เป็น "ยกเลิกแล้วเก็บแถวไว้" แต่ unique index เดิม
 * (`company + employee_code + วันที่ [+ leave_type]`) ไม่รู้จักสถานะ
 * แถวที่ยกเลิกแล้วจึงยังกินช่องอยู่ พอขอใหม่วันเดิมจะชน 1062 Duplicate entry
 * เป็น Server Error ทันที — Manager เจอของจริงตอนกดส่งแล้วกดยกเลิก
 *
 * วิธีแก้ใช้ pattern มาตรฐานของ MySQL: เพิ่มคอลัมน์ `active_slot` เข้าไปใน unique key
 *   - แถวที่ยังมีผล  -> active_slot = 1    (ชนกันเองได้ตามเดิม = กันขอซ้ำ)
 *   - แถวที่ยกเลิก   -> active_slot = NULL (MySQL ไม่ตรวจ unique กับ NULL = ปล่อยช่องคืน)
 * ยกเลิกกี่ครั้งก็ได้เพราะทุกแถวที่ยกเลิกเป็น NULL หมดและไม่ชนกันเอง
 */
return new class extends Migration
{
    private const CONN = 'mysql_ot_approval';

    /** ตาราง => [ชื่อ unique index, คอลัมน์ของ key เดิม] */
    private const TARGETS = [
        'leave_requests' => [
            'leave_request_employee_day_unique',
            ['company', 'employee_code', 'leave_date', 'leave_type'],
        ],
        'ot_requests' => [
            'ot_request_employee_day_unique',
            ['company', 'employee_code', 'work_date'],
        ],
    ];

    public function up(): void
    {
        foreach (self::TARGETS as $table => [$index, $columns]) {
            if (! Schema::connection(self::CONN)->hasTable($table)) {
                continue;
            }

            if (! Schema::connection(self::CONN)->hasColumn($table, 'active_slot')) {
                Schema::connection(self::CONN)->table($table, function (Blueprint $blueprint) {
                    $blueprint->unsignedTinyInteger('active_slot')->nullable()->default(1);
                });
            }

            // แถวเดิมที่ยกเลิกไปแล้วก่อน migration นี้ ต้องคืนช่องด้วย
            DB::connection(self::CONN)->table($table)
                ->where('approval_status', 'cancelled')
                ->update(['active_slot' => null]);

            if ($this->hasIndex($table, $index)) {
                DB::connection(self::CONN)->statement("ALTER TABLE `{$table}` DROP INDEX `{$index}`");
            }

            $list = implode(', ', array_map(fn (string $c) => "`{$c}`", array_merge($columns, ['active_slot'])));
            DB::connection(self::CONN)->statement("ALTER TABLE `{$table}` ADD UNIQUE `{$index}` ({$list})");
        }
    }

    public function down(): void
    {
        foreach (self::TARGETS as $table => [$index, $columns]) {
            if (! Schema::connection(self::CONN)->hasTable($table)) {
                continue;
            }

            if ($this->hasIndex($table, $index)) {
                DB::connection(self::CONN)->statement("ALTER TABLE `{$table}` DROP INDEX `{$index}`");
            }

            /* ย้อนกลับได้เฉพาะตอนไม่มีแถวที่ยกเลิกซ้ำวันกัน ไม่งั้น ADD UNIQUE จะล้ม
               จึงลบคอลัมน์ก่อนแล้วค่อยสร้าง index เดิม */
            if (Schema::connection(self::CONN)->hasColumn($table, 'active_slot')) {
                Schema::connection(self::CONN)->table($table, function (Blueprint $blueprint) {
                    $blueprint->dropColumn('active_slot');
                });
            }

            $list = implode(', ', array_map(fn (string $c) => "`{$c}`", $columns));
            DB::connection(self::CONN)->statement("ALTER TABLE `{$table}` ADD UNIQUE `{$index}` ({$list})");
        }
    }

    private function hasIndex(string $table, string $index): bool
    {
        return DB::connection(self::CONN)
            ->select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$index]) !== [];
    }
};
