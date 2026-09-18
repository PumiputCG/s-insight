<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * เพิ่มคอลัมน์ลายเซ็นใน app_users
 *
 * - เก็บลายเซ็นที่ตัดพื้นหลังออกแล้ว (โปร่งใส) เป็น PNG data URL (base64)
 * - ใช้ longText เพราะ base64 อาจยาว ; ค่า null = ยังไม่เซ็น
 * - วัตถุประสงค์: นำไปประทับเอกสารในอนาคต
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('app_users', function (Blueprint $table) {
            $table->longText('signature')->nullable()->after('profile_picture');
        });
    }

    public function down(): void
    {
        Schema::table('app_users', function (Blueprint $table) {
            $table->dropColumn('signature');
        });
    }
};
