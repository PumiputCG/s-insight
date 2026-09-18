<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * เลิกใช้ตัวย่อแผนก (dept_abbr) ทั้งระบบ — ใช้ "แผนก" (dept_th/dept_en) แทน
 * ลบคอลัมน์ dept_abbr ออกจาก employees + app_users
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('employees', 'dept_abbr')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->dropIndex(['dept_abbr']);
                $table->dropColumn('dept_abbr');
            });
        }

        if (Schema::hasColumn('app_users', 'dept_abbr')) {
            Schema::table('app_users', function (Blueprint $table) {
                $table->dropColumn('dept_abbr');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('employees', 'dept_abbr')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->string('dept_abbr', 30)->nullable()->index();
            });
        }
        if (! Schema::hasColumn('app_users', 'dept_abbr')) {
            Schema::table('app_users', function (Blueprint $table) {
                $table->string('dept_abbr', 100)->nullable();
            });
        }
    }
};
