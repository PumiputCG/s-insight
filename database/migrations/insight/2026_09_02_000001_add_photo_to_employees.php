<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * เก็บรูปพนักงานไว้ที่ `employees` ไม่ใช่ `app_users`
     *
     * ทำไม: เดิมรูปอยู่ที่ `app_users.profile_picture` อย่างเดียว พอพนักงานลาออกแล้วบัญชีถูกลบ
     * รูปก็หายตามไปด้วย (จากที่ตรวจ 2026-09-02: ลาออก 264 คน เหลือบัญชีแค่ 74
     * ทั้งที่ไฟล์รูปยังอยู่ในเครื่อง 125 คน) และพนักงานใหม่ที่ยังไม่เคยล็อกอินก็ไม่มีที่เก็บรูป
     * ย้ายมาเกาะกับตัวพนักงานแทน รูปจึงติดตัวตั้งแต่ก่อนมีบัญชีจนหลังลาออก
     */
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            // path ใน disk `public` เช่น profiles/emp_71100.jpg
            $table->string('photo_path')->nullable()->after('emp_status');
            // มาจากไหน: hr = โฟลเดอร์รูปของ HR · upload = พนักงานอัปโหลดเอง
            $table->string('photo_source', 16)->nullable()->after('photo_path');
            // วันที่ของไฟล์ต้นทาง — ใช้ตัดสินว่ารูปไหนใหม่กว่ากัน
            $table->timestamp('photo_taken_at')->nullable()->after('photo_source');
            $table->timestamp('photo_synced_at')->nullable()->after('photo_taken_at');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn(['photo_path', 'photo_source', 'photo_taken_at', 'photo_synced_at']);
        });
    }
};
