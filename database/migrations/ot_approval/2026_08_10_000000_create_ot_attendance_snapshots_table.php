<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** เก็บสำเนาเวลาเข้า-ออกและกะจาก Bplus เพื่อใช้ทดสอบ/ทำงานต่อใน Local */
return new class extends Migration
{
    private const CONN = 'mysql_ot_approval';

    public function up(): void
    {
        Schema::connection(self::CONN)->create('ot_attendance_snapshots', function (Blueprint $table) {
            $table->id();
            $table->string('company', 50);
            $table->string('employee_code', 30);
            $table->date('work_date');
            $table->string('shift_code', 30)->nullable();
            $table->string('shift_name_th')->nullable();
            $table->string('shift_name_en')->nullable();
            $table->time('shift_in')->nullable();
            $table->time('shift_out')->nullable();
            $table->time('break_in')->nullable();
            $table->time('break_out')->nullable();
            $table->time('clock_in')->nullable();
            $table->time('clock_out')->nullable();
            $table->unsignedTinyInteger('punch_count')->default(0);
            $table->json('punches')->nullable();
            $table->decimal('work_hours', 10, 2)->nullable();
            $table->string('attendance_source', 20)->default('raw');
            $table->string('attendance_state', 20)->default('not_scanned');
            $table->timestamp('synced_at');
            $table->timestamps();

            $table->unique(
                ['company', 'employee_code', 'work_date'],
                'ot_attendance_snapshot_employee_day_unique',
            );
            $table->index(['work_date', 'company'], 'ot_attendance_snapshot_day_company_idx');
        });
    }

    public function down(): void
    {
        Schema::connection(self::CONN)->dropIfExists('ot_attendance_snapshots');
    }
};
