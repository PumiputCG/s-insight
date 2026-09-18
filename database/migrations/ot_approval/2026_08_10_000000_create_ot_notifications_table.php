<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** แจ้งเตือน in-app ของโมดูล OT — ผูกกับพนักงานรายคน เห็นเฉพาะของตัวเอง */
return new class extends Migration
{
    public function getConnection(): string
    {
        return 'mysql_ot_approval';
    }

    public function up(): void
    {
        Schema::connection($this->getConnection())->create('ot_notifications', function (Blueprint $table) {
            $table->id();
            $table->string('employee_code', 50);          // ผู้รับ
            $table->string('type', 40);                   // request_submitted | request_decided
            $table->string('title', 190);
            $table->string('body', 500)->nullable();
            $table->string('link', 255)->nullable();      // กดแล้วไปหน้าไหน
            $table->unsignedInteger('item_count')->default(1);
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            // ดึงของฉันที่ยังไม่อ่าน เรียงใหม่สุด — ใช้บ่อยสุดเพราะกระดิ่งอยู่ทุกหน้า
            $table->index(['employee_code', 'read_at'], 'idx_ot_noti_owner_unread');
            $table->index(['employee_code', 'created_at'], 'idx_ot_noti_owner_recent');
        });
    }

    public function down(): void
    {
        Schema::connection($this->getConnection())->dropIfExists('ot_notifications');
    }
};
