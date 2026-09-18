<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ไฟล์ที่ส่ง HR แยกตาม "วันที่ยื่นคำขอ" ด้วย ไม่ใช่แค่วันที่ทำงาน
 *
 * เพราะ Bplus บวกชั่วโมงสะสมเมื่อ import ซ้ำ ถ้าไฟล์ของวันทำงานเดียวกันรวมทุกคน
 * คนที่ HR import ไปแล้วรอบก่อนจะได้ชั่วโมงเป็นสองเท่า
 * จึงแยกเป็นไฟล์ละ (วันที่ทำงาน + วันที่ยื่น) และต้องเก็บ log แยกคู่กันด้วย
 */
return new class extends Migration
{
    private const CONN = 'mysql_ot_approval';

    private const TABLE = 'ot_export_downloads';

    public function up(): void
    {
        if (! Schema::connection(self::CONN)->hasColumn(self::TABLE, 'submitted_on')) {
            Schema::connection(self::CONN)->table(self::TABLE, function (Blueprint $table) {
                $table->date('submitted_on')->nullable()->after('target_date')
                    ->comment('วันที่ยื่นคำขอของรายการในไฟล์ — แยกไฟล์ของวันทำงานเดียวกันออกจากกัน');
            });
        }
    }

    public function down(): void
    {
        if (Schema::connection(self::CONN)->hasColumn(self::TABLE, 'submitted_on')) {
            Schema::connection(self::CONN)->table(self::TABLE, function (Blueprint $table) {
                $table->dropColumn('submitted_on');
            });
        }
    }
};
