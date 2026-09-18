<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** โมดูลลา 75 แยกตารางจาก OT เพื่อไม่ให้กฎ Attendance หรือสถานะเดิมปะปนกัน */
return new class extends Migration
{
  private const CONN = 'mysql_ot_approval';

  public function up(): void
  {
    Schema::connection(self::CONN)->create('leave_requests', function (Blueprint $table) {
      $table->id();
      $table->uuid('batch_uuid');
      $table->string('company', 50);
      $table->string('dept_code', 50)->default('');
      $table->string('employee_code', 30);
      $table->string('employee_name')->nullable();
      $table->string('position_name')->nullable();
      $table->string('department_name')->nullable();
      $table->date('leave_date');
      $table->date('range_start');
      $table->date('range_end');
      $table->string('leave_type', 50)->default('section_75');
      $table->string('bplus_stamp_type_key', 30)->default('20030');
      $table->string('deduction_agreement_code', 30)->default('020008(1)');
      $table->string('shift_code', 10)->default('00');
      $table->string('swipe_character_code', 10)->default('0');
      $table->string('approval_method', 10)->default('1');
      $table->decimal('leave_quantity', 8, 2)->default(1);
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
      $table->timestamps();

      $table->unique(['company', 'employee_code', 'leave_date', 'leave_type'], 'leave_request_employee_day_unique');
      $table->index(['company', 'dept_code', 'leave_date'], 'leave_request_department_day_idx');
      $table->index(['approval_status', 'leave_date'], 'leave_request_approval_day_idx');
      $table->index(['export_status', 'leave_date'], 'leave_request_export_day_idx');
      $table->index(['batch_uuid', 'leave_date'], 'leave_request_batch_idx');
    });

    Schema::connection(self::CONN)->create('leave_request_approvals', function (Blueprint $table) {
      $table->id();
      $table->unsignedBigInteger('leave_request_id');
      $table->string('decision', 20);
      $table->unsignedBigInteger('actor_app_user_id');
      $table->string('actor_employee_code', 30)->nullable();
      $table->text('note')->nullable();
      $table->timestamps();

      $table->foreign('leave_request_id')->references('id')->on('leave_requests')->cascadeOnDelete();
      $table->index(['leave_request_id', 'created_at'], 'leave_request_approval_history_idx');
    });

    if (Schema::connection(self::CONN)->hasTable('ot_notifications')
      && ! Schema::connection(self::CONN)->hasColumn('ot_notifications', 'category')) {
      Schema::connection(self::CONN)->table('ot_notifications', function (Blueprint $table) {
        $table->string('category', 20)->default('ot')->after('type');
        $table->index(['employee_code', 'category', 'read_at'], 'idx_workflow_noti_category');
      });
    }
  }

  public function down(): void
  {
    if (Schema::connection(self::CONN)->hasTable('ot_notifications')
      && Schema::connection(self::CONN)->hasColumn('ot_notifications', 'category')) {
      Schema::connection(self::CONN)->table('ot_notifications', function (Blueprint $table) {
        $table->dropIndex('idx_workflow_noti_category');
        $table->dropColumn('category');
      });
    }

    Schema::connection(self::CONN)->dropIfExists('leave_request_approvals');
    Schema::connection(self::CONN)->dropIfExists('leave_requests');
  }
};
