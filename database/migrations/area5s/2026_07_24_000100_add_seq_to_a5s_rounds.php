<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * round = "ครั้งตรวจ" — 1 เดือนเปิดได้หลายรอบ
 * เพิ่ม seq (ครั้งที่ในเดือน) + inspected_on (วันที่ตรวจ) และเปลี่ยน unique(year,month) -> unique(year,month,seq)
 */
return new class extends Migration
{
    private const CONN = 'mysql_area5s';

    public function up(): void
    {
        Schema::connection(self::CONN)->table('a5s_rounds', function (Blueprint $table) {
            $table->unsignedTinyInteger('seq')->default(1)->after('month');
            $table->date('inspected_on')->nullable()->after('seq');
        });
        Schema::connection(self::CONN)->table('a5s_rounds', function (Blueprint $table) {
            $table->dropUnique('a5s_rounds_year_month_unique');
            $table->unique(['year', 'month', 'seq'], 'a5s_rounds_ym_seq_unique');
        });
    }

    public function down(): void
    {
        Schema::connection(self::CONN)->table('a5s_rounds', function (Blueprint $table) {
            $table->dropUnique('a5s_rounds_ym_seq_unique');
            $table->unique(['year', 'month'], 'a5s_rounds_year_month_unique');
        });
        Schema::connection(self::CONN)->table('a5s_rounds', function (Blueprint $table) {
            $table->dropColumn(['seq', 'inspected_on']);
        });
    }
};
