<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * โครงสร้างแปลนบริษัท (Manager สั่ง 2026-07-19): อาคาร/โซน -> ชั้น -> ห้อง/Layout เดิม
 * ถาวร ไม่ผูกรอบเดือน (ต่างจาก a5s_layouts ที่ยังผูก round_id เหมือนเดิม)
 */
return new class extends Migration
{
    private const CONN = 'mysql_area5s';

    public function up(): void
    {
        Schema::connection(self::CONN)->create('a5s_company_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('image_path');
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by');
            $table->timestamps();
        });

        // โซน/อาคารบนแปลน — พิกัดกรอบ polygon เก็บเป็น % ของภาพ (เหมือนพิกัดจุด 5ส เดิม)
        Schema::connection(self::CONN)->create('a5s_zones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_plan_id')->constrained('a5s_company_plans')->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->json('shape_points'); // [{"x":12.5,"y":30.2}, ...]
            $table->string('color', 20)->nullable();
            $table->unsignedInteger('sort')->default(0);
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by');
            $table->timestamps();
        });

        Schema::connection(self::CONN)->create('a5s_floors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('zone_id')->constrained('a5s_zones')->cascadeOnDelete();
            $table->string('name');
            $table->unsignedInteger('sort')->default(0);
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by');
            $table->timestamps();
            $table->unique(['zone_id', 'name']);
        });

        // ผูก Layout (ห้อง/พื้นที่) เข้ากับชั้น — nullable: ข้อมูลเก่ายัง map ไม่เสร็จได้ ไม่กระทบการใช้งาน
        Schema::connection(self::CONN)->table('a5s_layouts', function (Blueprint $table) {
            $table->unsignedBigInteger('floor_id')->nullable()->after('round_id')->index();
        });
    }

    public function down(): void
    {
        Schema::connection(self::CONN)->table('a5s_layouts', function (Blueprint $table) {
            $table->dropColumn('floor_id');
        });
        Schema::connection(self::CONN)->dropIfExists('a5s_floors');
        Schema::connection(self::CONN)->dropIfExists('a5s_zones');
        Schema::connection(self::CONN)->dropIfExists('a5s_company_plans');
    }
};
