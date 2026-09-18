<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * SUPAVUT 5S AREA — โครงตารางทั้งระบบ (ดูภาพรวมที่ AREA5S_SYSTEM.md)
 *
 * หัวใจ: ประวัติรายเดือนไม่ขยับ — งานรายเดือน (a5s_tasks) เก็บ snapshot จุด/ผู้รับผิดชอบ/ผู้ประเมิน
 * ณ ตอนเปิดรอบ · การถอดคน/ปิดจุด ใช้ flag ไม่ delete
 */
return new class extends Migration
{
    private const CONN = 'mysql_area5s';

    public function up(): void
    {
        // สิทธิ์: admin (แอดมินเฉพาะระบบ 5S) / allocator (ผู้จัดสรรพื้นที่) / evaluator (ผู้ตรวจประเมิน)
        Schema::connection(self::CONN)->create('a5s_members', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('app_user_id');          // insight.app_users
            $table->string('employee_code', 20);
            $table->string('display_name')->nullable();
            $table->string('position')->nullable();
            $table->string('role', 20);                          // admin | allocator | evaluator
            $table->unsignedBigInteger('assigned_by')->nullable();
            $table->timestamps();
            $table->unique(['app_user_id', 'role']);
        });

        // Layout = ภาพแผนผังพื้นที่ (ปก = ภาพเดียวกัน)
        Schema::connection(self::CONN)->create('a5s_layouts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('image_path');
            $table->unsignedBigInteger('created_by');            // app_user_id ของ allocator/admin เจ้าของ
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // จุดบน Layout — พิกัดเก็บเป็น % ของภาพ (responsive ทุกจอ)
        Schema::connection(self::CONN)->create('a5s_points', function (Blueprint $table) {
            $table->id();
            $table->foreignId('layout_id')->constrained('a5s_layouts')->cascadeOnDelete();
            $table->string('code', 10);                          // A, B, C, ... ไม่จำกัด
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('x', 8, 4);                          // % จากซ้าย 0-100
            $table->decimal('y', 8, 4);                          // % จากบน 0-100
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
            $table->unique(['layout_id', 'code']);
        });

        // ผู้รับผิดชอบจุด — ledger: ถอดคน = set removed_at ไม่ delete (ประวัติไม่หาย)
        Schema::connection(self::CONN)->create('a5s_point_assignees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('point_id')->constrained('a5s_points')->cascadeOnDelete();
            $table->string('employee_code', 20);
            $table->unsignedBigInteger('assigned_by');
            $table->timestamp('assigned_at');
            $table->timestamp('removed_at')->nullable();
            $table->unsignedBigInteger('removed_by')->nullable();
            $table->timestamps();
            $table->index(['employee_code', 'removed_at']);
        });

        // ขอบเขตผู้ตรวจ — point_id null = ประเมินทั้ง layout ; ระบุ = เจาะจุด (เจาะจงชนะ)
        Schema::connection(self::CONN)->create('a5s_evaluator_scopes', function (Blueprint $table) {
            $table->id();
            $table->string('employee_code', 20);
            $table->foreignId('layout_id')->constrained('a5s_layouts')->cascadeOnDelete();
            $table->foreignId('point_id')->nullable()->constrained('a5s_points')->cascadeOnDelete();
            $table->unsignedBigInteger('created_by');
            $table->timestamps();
            $table->index(['employee_code']);
        });

        // รอบรายเดือน — เปิดอัตโนมัติต้นเดือน + admin ปิดเองได้ (D2)
        Schema::connection(self::CONN)->create('a5s_rounds', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('year');                // พ.ศ.
            $table->unsignedTinyInteger('month');                // 1-12
            $table->string('status', 10)->default('open');       // open | closed
            $table->timestamp('deadline_at')->nullable();        // default = สิ้นเดือน
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->unsignedBigInteger('closed_by')->nullable();
            $table->timestamps();
            $table->unique(['year', 'month']);
        });

        // งานประจำเดือนต่อจุด — snapshot ณ ตอนเปิดรอบ (D5: จุดละ 1 งาน หลายคนใช้ร่วมกัน)
        Schema::connection(self::CONN)->create('a5s_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('round_id')->constrained('a5s_rounds')->cascadeOnDelete();
            $table->foreignId('point_id')->constrained('a5s_points')->cascadeOnDelete();
            $table->unsignedBigInteger('layout_id');             // snapshot
            $table->string('layout_name');
            $table->string('point_code', 10);
            $table->string('point_name');
            $table->text('point_description')->nullable();
            $table->json('assignees_json')->nullable();          // [{code,name}]
            $table->json('evaluators_json')->nullable();         // [{code,name}]
            $table->string('status', 20)->default('not_started'); // not_started|draft|submitted|failed|resubmitted|passed
            $table->timestamp('submitted_at')->nullable();
            $table->unsignedInteger('submit_count')->default(0);
            $table->string('result', 10)->nullable();            // pass | fail
            $table->text('fail_reason')->nullable();
            $table->text('advice')->nullable();
            $table->unsignedBigInteger('evaluated_by')->nullable();
            $table->timestamp('evaluated_at')->nullable();
            $table->timestamps();
            $table->unique(['round_id', 'point_id']);
        });

        // การ์ดข้อมูลที่ผู้รับผิดชอบส่ง (หลายการ์ด/งาน แต่ละคนเพิ่มของตัวเอง)
        Schema::connection(self::CONN)->create('a5s_cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained('a5s_tasks')->cascadeOnDelete();
            $table->string('title');
            $table->text('detail')->nullable();
            $table->unsignedBigInteger('created_by');            // app_user_id
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::connection(self::CONN)->create('a5s_card_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('card_id')->constrained('a5s_cards')->cascadeOnDelete();
            $table->string('path');
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });

        // แจ้งเตือน in-app (เห็นเฉพาะของตัวเอง)
        Schema::connection(self::CONN)->create('a5s_notifications', function (Blueprint $table) {
            $table->id();
            $table->string('employee_code', 20);
            $table->string('type', 30);
            $table->string('title');
            $table->text('body')->nullable();
            $table->string('link')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            $table->index(['employee_code', 'read_at']);
        });

        // ประวัติการแก้ไข (เปลี่ยนผู้รับผิดชอบ/แก้จุด/เปิดปิด ฯลฯ)
        Schema::connection(self::CONN)->create('a5s_activity_log', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('action', 50);
            $table->string('subject_type', 30);
            $table->unsignedBigInteger('subject_id');
            $table->json('detail_json')->nullable();
            $table->timestamps();
            $table->index(['subject_type', 'subject_id']);
        });
    }

    public function down(): void
    {
        foreach (['a5s_activity_log', 'a5s_notifications', 'a5s_card_images', 'a5s_cards', 'a5s_tasks', 'a5s_rounds', 'a5s_evaluator_scopes', 'a5s_point_assignees', 'a5s_points', 'a5s_layouts', 'a5s_members'] as $t) {
            Schema::connection(self::CONN)->dropIfExists($t);
        }
    }
};
