<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * บันทึกประวัติการดาวน์โหลดเอกสารส่ง HR
 *
 * เดิมการกดดาวน์โหลดไม่ได้บันทึกอะไรเลย ทั้งฝั่ง OT และการลา
 * ทำให้หน้า `ดาวน์โหลด` ตอบไม่ได้ว่าวันไหนเคยโหลดไปแล้ว ใครโหลด และโหลดไปกี่ครั้ง
 * และสถานะ `โหลดแล้ว` / `ต้องโหลดซ้ำ` ไม่มีทางเกิดขึ้นจริง
 *
 * เพิ่ม `exported_at` ให้ leave_requests ด้วย เพื่อให้ตรวจ "มีของใหม่หลังโหลด" ได้เหมือน OT
 */
return new class extends Migration
{
    private const CONN = 'mysql_ot_approval';

    public function up(): void
    {
        if (! Schema::connection(self::CONN)->hasTable('ot_export_downloads')) {
            Schema::connection(self::CONN)->create('ot_export_downloads', function (Blueprint $table) {
                $table->id();
                $table->string('module', 10)->comment('ot | leave');
                $table->string('scope', 10)->comment('date | cycle');
                $table->date('target_date')->nullable()->comment('วันที่ทำ OT / วันที่ลา เมื่อ scope=date');
                $table->string('cycle_key', 20)->nullable()->comment('รอบเงินเดือน เมื่อ scope=cycle');
                $table->unsignedInteger('row_count')->default(0);
                $table->unsignedBigInteger('downloaded_by_app_user_id')->nullable();
                $table->string('downloaded_by_employee_code', 30)->nullable();
                $table->timestamp('downloaded_at');
                $table->timestamps();

                $table->index(['module', 'target_date'], 'ot_export_downloads_module_date_index');
                $table->index('cycle_key', 'ot_export_downloads_cycle_index');
            });
        }

        if (! Schema::connection(self::CONN)->hasColumn('leave_requests', 'exported_at')) {
            Schema::connection(self::CONN)->table('leave_requests', function (Blueprint $table) {
                $table->timestamp('exported_at')->nullable()->after('decided_at');
            });
        }
    }

    public function down(): void
    {
        Schema::connection(self::CONN)->dropIfExists('ot_export_downloads');

        if (Schema::connection(self::CONN)->hasColumn('leave_requests', 'exported_at')) {
            Schema::connection(self::CONN)->table('leave_requests', function (Blueprint $table) {
                $table->dropColumn('exported_at');
            });
        }
    }
};
