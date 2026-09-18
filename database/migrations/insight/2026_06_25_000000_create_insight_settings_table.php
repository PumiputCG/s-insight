<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ตารางตั้งค่ากลางของ Insight (key-value)
 *
 * ใช้เก็บค่าตั้งระบบ เช่น `login_allowed_job_codes` (รหัสตำแหน่งที่อนุญาตให้ล็อกอิน)
 * value เก็บเป็น JSON string ; null/ไม่มี key = ใช้ค่าเริ่มต้น (อนุญาตทั้งหมด)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('insight_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->longText('value')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('insight_settings');
    }
};
