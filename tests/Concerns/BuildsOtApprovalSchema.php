<?php

namespace Tests\Concerns;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * สร้างตารางของ OT Approval สำหรับเทสต์ให้ตรงกับ MySQL จริงทุกคอลัมน์
 *
 * **กฎเหล็ก: ห้ามผ่อนความเข้มของคอลัมน์ให้หลวมกว่าฐานจริง**
 * เคยมีบั๊กที่หลุดขึ้น production เพราะเทสต์สร้างตารางแคบกว่าจริง
 * (`actor_app_user_id` ตั้ง NOT NULL ในฐานจริงแต่เทสต์ปล่อย nullable)
 * เทสต์จึงผ่านหมดทั้งที่โค้ดเขียน null ลงไปไม่ได้จริง
 *
 * ไฟล์นี้ถูกสร้างจาก `SHOW COLUMNS` ของฐาน `insight_ot_approval` โดยตรง
 * เมื่อเพิ่ม migration ใหม่ให้เพิ่มคอลัมน์ที่นี่ด้วยเสมอ
 */
trait BuildsOtApprovalSchema
{
    private const CONN = 'mysql_ot_approval';

    protected function buildOtApprovalSchema(): void
    {
        /* phpunit.xml ตั้งแค่ connection หลักเป็น sqlite `:memory:` ส่วน `mysql_ot_approval`
           ยังชี้ MySQL จริงตาม config ถ้าไม่ override ตรงนี้ เทสต์จะไปสร้าง/ลบตารางในฐานจริง
           ซึ่งอันตรายมากเพราะมีข้อมูลคำขอและ snapshot ของจริงอยู่ */
        Config::set('database.connections.'.self::CONN, [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => false,
        ]);
        DB::purge(self::CONN);


        Schema::connection(self::CONN)->create('leave_request_approvals', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('leave_request_id');
                $table->string('decision', 20);
                $table->unsignedBigInteger('actor_app_user_id');
                $table->string('actor_employee_code', 30)->nullable();
                $table->text('note')->nullable();
                $table->timestamps();
        });

        Schema::connection(self::CONN)->create('leave_requests', function (Blueprint $table) {
                $table->id();
                $table->uuid('batch_uuid');
                $table->string('company', 50);
                $table->string('dept_code', 50)->default('');
                $table->string('employee_code', 30);
                $table->string('employee_name', 255)->nullable();
                $table->string('position_name', 255)->nullable();
                $table->string('department_name', 255)->nullable();
                $table->date('leave_date');
                $table->date('range_start');
                $table->date('range_end');
                $table->string('leave_type', 50)->default('section_75');
                $table->string('bplus_stamp_type_key', 30)->default(20030);
                $table->string('deduction_agreement_code', 30)->default('020008(1)');
                $table->string('shift_code', 10)->default(00);
                $table->string('swipe_character_code', 10)->default(0);
                $table->string('approval_method', 10)->default(1);
                $table->decimal('leave_quantity', 8, 2)->default(1.00);
                $table->text('note')->nullable();
                $table->string('approval_status', 20)->default('draft');
                $table->string('export_status', 20)->default('not_ready');
                $table->unsignedBigInteger('created_by_app_user_id');
                $table->string('created_by_employee_code', 30)->nullable();
                $table->unsignedBigInteger('decided_by_app_user_id')->nullable();
                $table->string('decided_by_employee_code', 30)->nullable();
                $table->text('decision_note')->nullable();
                $table->timestamp('submitted_at')->nullable();
                $table->timestamp('decided_at')->nullable();
                $table->timestamp('cancelled_at')->nullable();
                $table->string('cancel_reason', 500)->nullable();
                $table->unsignedBigInteger('cancelled_by_app_user_id')->nullable();
                $table->string('cancelled_by_employee_code', 20)->nullable();
                $table->unsignedTinyInteger('active_slot')->nullable()->default(1);
                $table->timestamp('exported_at')->nullable();
                $table->timestamps();
        });

        Schema::connection(self::CONN)->create('ot_attendance_snapshots', function (Blueprint $table) {
                $table->id();
                $table->string('company', 50);
                $table->string('employee_code', 30);
                $table->date('work_date');
                $table->string('shift_code', 30)->nullable();
                $table->string('shift_name_th', 255)->nullable();
                $table->string('shift_name_en', 255)->nullable();
                $table->time('shift_in')->nullable();
                $table->time('shift_out')->nullable();
                $table->time('break_in')->nullable();
                $table->time('break_out')->nullable();
                $table->time('clock_in')->nullable();
                $table->time('clock_out')->nullable();
                $table->unsignedInteger('punch_count')->default(0);
                $table->text('punches')->nullable();
                $table->decimal('work_hours', 10, 2)->nullable();
                $table->string('attendance_source', 20)->default('raw');
                $table->string('attendance_state', 20)->default('not_scanned');
                $table->timestamp('synced_at')->default('current_timestamp()');
                $table->timestamps();
        });

        Schema::connection(self::CONN)->create('ot_department_assignments', function (Blueprint $table) {
                $table->id();
                $table->string('module', 20)->default('ot');
                $table->string('company', 50);
                $table->string('dept_code', 50)->default('');
                $table->string('role', 20);
                $table->string('shift_group', 20)->nullable();
                $table->unsignedBigInteger('app_user_id');
                $table->string('employee_code', 30);
                $table->unsignedBigInteger('assigned_by')->nullable();
                $table->timestamps();
        });

        Schema::connection(self::CONN)->create('ot_export_downloads', function (Blueprint $table) {
                $table->id();
                $table->string('module', 10);
                $table->string('scope', 10);
                $table->date('target_date')->nullable();
                $table->date('submitted_on')->nullable();
                $table->string('cycle_key', 20)->nullable();
                $table->unsignedInteger('row_count')->default(0);
                $table->unsignedBigInteger('downloaded_by_app_user_id')->nullable();
                $table->string('downloaded_by_employee_code', 30)->nullable();
                $table->timestamp('downloaded_at')->default('current_timestamp()');
                $table->timestamps();
        });

        Schema::connection(self::CONN)->create('ot_members', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('app_user_id');
                $table->string('employee_code', 30);
                $table->string('display_name', 255)->nullable();
                $table->string('position', 255)->nullable();
                $table->string('role', 20)->default('admin');
                $table->unsignedBigInteger('assigned_by')->nullable();
                $table->timestamps();
        });

        Schema::connection(self::CONN)->create('ot_notifications', function (Blueprint $table) {
                $table->id();
                $table->string('employee_code', 50);
                $table->string('type', 40);
                $table->string('category', 20)->default('ot');
                $table->string('title', 190);
                $table->string('body', 500)->nullable();
                $table->string('link', 255)->nullable();
                $table->unsignedInteger('item_count')->default(1);
                $table->timestamp('read_at')->nullable();
                $table->timestamps();
        });

        Schema::connection(self::CONN)->create('ot_request_approvals', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('ot_request_id');
                $table->string('decision', 20);
                $table->unsignedBigInteger('actor_app_user_id')->nullable();
                $table->string('actor_employee_code', 30)->nullable();
                $table->text('note')->nullable();
                $table->timestamps();
        });

        Schema::connection(self::CONN)->create('ot_requests', function (Blueprint $table) {
                $table->id();
                $table->string('company', 50);
                $table->string('dept_code', 50)->default('');
                $table->string('employee_code', 30);
                $table->string('employee_name', 255)->nullable();
                $table->string('position_name', 255)->nullable();
                $table->string('department_name', 255)->nullable();
                $table->date('work_date');
                $table->string('shift_code', 30)->nullable();
                $table->string('shift_name', 255)->nullable();
                $table->time('shift_in')->nullable();
                $table->time('shift_out')->nullable();
                $table->time('break_in')->nullable();
                $table->time('break_out')->nullable();
                $table->string('ot_type', 50);
                $table->decimal('multiplier', 4, 2);
                $table->string('agreement_code', 30)->nullable();
                $table->dateTime('requested_start_at');
                $table->dateTime('requested_end_at');
                $table->unsignedInteger('requested_hours');
                $table->unsignedInteger('requested_minutes')->default(0);
                $table->boolean('is_special')->default(false);
                $table->text('note')->nullable();
                $table->string('approval_status', 20)->default('draft');
                $table->string('attendance_status', 20)->default('waiting_scan');
                $table->string('export_status', 20)->default('not_ready');
                $table->unsignedBigInteger('created_by_app_user_id');
                $table->string('created_by_employee_code', 30)->nullable();
                $table->unsignedBigInteger('decided_by_app_user_id')->nullable();
                $table->string('decided_by_employee_code', 30)->nullable();
                $table->text('decision_note')->nullable();
                $table->time('last_clock_in')->nullable();
                $table->time('last_clock_out')->nullable();
                $table->string('attendance_source', 20)->nullable();
                $table->timestamp('attendance_checked_at')->nullable();
                $table->timestamp('submitted_at')->nullable();
                $table->timestamp('decided_at')->nullable();
                $table->timestamp('cancelled_at')->nullable();
                $table->string('cancel_reason', 500)->nullable();
                $table->unsignedBigInteger('cancelled_by_app_user_id')->nullable();
                $table->string('cancelled_by_employee_code', 20)->nullable();
                $table->unsignedTinyInteger('active_slot')->nullable()->default(1);
                $table->timestamp('exported_at')->nullable();
                $table->timestamps();
        });
    }
}
