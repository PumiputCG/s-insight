<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * recruit_settings — ค่าตั้งของระบบ Recruit (key-value, value เป็น JSON) ใน insight_recruit
 *
 * ใช้เก็บ เช่น `route_order` = ลำดับเส้นทางเอกสาร (admin จัดเอง)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('mysql_recruit')->create('recruit_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->longText('value')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection('mysql_recruit')->dropIfExists('recruit_settings');
    }
};
