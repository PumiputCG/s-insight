<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * เพิ่มชั้น "เส้นทางอนุมัติ (routes)" เหนือ steps
 *
 * - recruit_routes : 1 route = 1 เส้นทางอนุมัติ (มีหลายเส้นทางได้) — DCC เลือกเองตอนส่งคำขอ
 * - recruit_steps  : เพิ่ม route_id ผูกการ์ดเข้ากับเส้นทาง ; position = ลำดับภายในเส้นทางนั้น
 * - ย้าย steps เดิม (ถ้ามี) ไปอยู่ใต้ "เส้นทาง 1" อัตโนมัติ
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('mysql_recruit')->create('recruit_routes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });

        Schema::connection('mysql_recruit')->table('recruit_steps', function (Blueprint $table) {
            $table->unsignedBigInteger('route_id')->nullable()->after('id')->index();
        });

        // ย้าย steps เดิมทั้งหมดไปอยู่ใต้เส้นทางเริ่มต้น (ถ้ามีของเก่า)
        if (DB::connection('mysql_recruit')->table('recruit_steps')->exists()) {
            $routeId = DB::connection('mysql_recruit')->table('recruit_routes')->insertGetId([
                'name' => 'เส้นทาง 1', 'position' => 1, 'created_at' => now(), 'updated_at' => now(),
            ]);
            DB::connection('mysql_recruit')->table('recruit_steps')->update(['route_id' => $routeId]);
        }
    }

    public function down(): void
    {
        Schema::connection('mysql_recruit')->table('recruit_steps', function (Blueprint $table) {
            $table->dropColumn('route_id');
        });
        Schema::connection('mysql_recruit')->dropIfExists('recruit_routes');
    }
};
