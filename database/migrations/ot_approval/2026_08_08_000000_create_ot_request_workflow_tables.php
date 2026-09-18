<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** OT Approval phase 2C: คำขอรายวัน การอนุมัติ และ Attendance snapshot */
return new class extends Migration
{
    private const CONN = 'mysql_ot_approval';

    public function up(): void
    {
        Schema::connection(self::CONN)->create('ot_requests', function (Blueprint $table) {
            $table->id();
            $table->string('company', 50);
            $table->string('dept_code', 50)->default('');
            $table->string('employee_code', 30);
            $table->string('employee_name')->nullable();
            $table->string('position_name')->nullable();
            $table->string('department_name')->nullable();
            $table->date('work_date');

            $table->string('shift_code', 30)->nullable();
            $table->string('shift_name')->nullable();
            $table->time('shift_in')->nullable();
            $table->time('shift_out')->nullable();
            $table->time('break_in')->nullable();
            $table->time('break_out')->nullable();

            $table->string('ot_type', 50);
            $table->decimal('multiplier', 4, 2);
            $table->string('agreement_code', 30)->nullable();
            $table->dateTime('requested_start_at');
            $table->dateTime('requested_end_at');
            $table->unsignedTinyInteger('requested_hours');
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
            $table->timestamp('exported_at')->nullable();
            $table->timestamps();

            $table->unique(['company', 'employee_code', 'work_date'], 'ot_request_employee_day_unique');
            $table->index(['company', 'dept_code', 'work_date'], 'ot_request_department_day_idx');
            $table->index(['approval_status', 'work_date'], 'ot_request_approval_day_idx');
            $table->index(['attendance_status', 'work_date'], 'ot_request_attendance_day_idx');
            $table->index(['export_status', 'work_date'], 'ot_request_export_day_idx');
        });

        Schema::connection(self::CONN)->create('ot_request_approvals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ot_request_id');
            $table->string('decision', 20);
            $table->unsignedBigInteger('actor_app_user_id');
            $table->string('actor_employee_code', 30)->nullable();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->foreign('ot_request_id')
                ->references('id')
                ->on('ot_requests')
                ->cascadeOnDelete();
            $table->index(['ot_request_id', 'created_at'], 'ot_request_approval_history_idx');
        });
    }

    public function down(): void
    {
        Schema::connection(self::CONN)->dropIfExists('ot_request_approvals');
        Schema::connection(self::CONN)->dropIfExists('ot_requests');
    }
};
