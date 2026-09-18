<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * OT Approval phase 1 — module administrators and department role assignments.
 * No cross-database foreign keys are used because employee data remains in Insight.
 */
return new class extends Migration
{
    private const CONN = 'mysql_ot_approval';

    public function up(): void
    {
        Schema::connection(self::CONN)->create('ot_members', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('app_user_id');
            $table->string('employee_code', 30);
            $table->string('display_name')->nullable();
            $table->string('position')->nullable();
            $table->string('role', 20)->default('admin');
            $table->unsignedBigInteger('assigned_by')->nullable();
            $table->timestamps();

            $table->unique(['app_user_id', 'role']);
            $table->index('employee_code');
        });

        Schema::connection(self::CONN)->create('ot_department_assignments', function (Blueprint $table) {
            $table->id();
            $table->string('company', 50);
            $table->string('dept_code', 50)->default('');
            $table->string('role', 20);
            $table->unsignedBigInteger('app_user_id');
            $table->string('employee_code', 30);
            $table->unsignedBigInteger('assigned_by')->nullable();
            $table->timestamps();

            $table->unique(['company', 'dept_code', 'role'], 'ot_department_role_unique');
            $table->index(['app_user_id', 'role']);
            $table->index(['company', 'employee_code']);
        });
    }

    public function down(): void
    {
        Schema::connection(self::CONN)->dropIfExists('ot_department_assignments');
        Schema::connection(self::CONN)->dropIfExists('ot_members');
    }
};
