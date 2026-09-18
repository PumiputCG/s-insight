<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * recruit_members — บทบาทของผู้ใช้ในระบบ Recruit (เก็บใน insight_recruit)
 *
 * - admin กำหนด role ให้พนักงานแต่ละคน (เลือกจาก insight.app_users)
 * - มี record = เข้าระบบ Recruit ได้ ; ไม่มี = เข้าไม่ได้
 * - 1 คนมีได้หลาย role (1 row = 1 role) ; ไม่แตะ app_users.role ของ core
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('mysql_recruit')->create('recruit_members', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('app_user_id')->index();   // อ้าง insight.app_users.id (logical)
            $table->string('employee_code')->nullable();          // snapshot
            $table->string('display_name')->nullable();           // snapshot
            $table->string('position')->nullable();               // snapshot ตำแหน่ง (job_en/th)
            $table->string('role');                               // dcc | manager | hr_manager | recruit
            $table->unsignedBigInteger('assigned_by')->nullable(); // admin app_user_id
            $table->timestamps();

            $table->unique(['app_user_id', 'role']);
            $table->index('role');
        });
    }

    public function down(): void
    {
        Schema::connection('mysql_recruit')->dropIfExists('recruit_members');
    }
};
