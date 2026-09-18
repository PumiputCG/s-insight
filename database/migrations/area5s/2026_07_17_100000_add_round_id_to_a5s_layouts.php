<?php

use App\Models\Area5s\A5sRound;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Layout ผูกกับรอบเดือน (Manager สั่ง 2026-07-17 รอบสาม):
 * เปิดเดือนใหม่ = เริ่มว่างตั้งแต่เพิ่ม Layout · สลับกลับเดือนเก่า = Layout+ข้อมูลเดือนนั้นกลับมา
 * backfill: layout เดิมทั้งหมดผูกกับรอบที่เปิดอยู่ (หรือรอบล่าสุด)
 */
return new class extends Migration            
{
    private const CONN = 'mysql_area5s';

    public function up(): void
    {
        Schema::connection(self::CONN)->table('a5s_layouts', function (Blueprint $table) {
            $table->unsignedBigInteger('round_id')->nullable()->after('id')->index();
        });

        $round = A5sRound::open() ?: A5sRound::query()->orderByDesc('year')->orderByDesc('month')->first();
        if ($round) {
            A5sLayoutBackfill::run($round->id);
        }
    }

    public function down(): void
    {
        Schema::connection(self::CONN)->table('a5s_layouts', function (Blueprint $table) {
            $table->dropColumn('round_id');
        });
    }
};

/** helper เล็ก ๆ กัน query builder ปนใน anonymous class */
final class A5sLayoutBackfill
{
    public static function run(int $roundId): void
    {
        DB::connection('mysql_area5s')
            ->table('a5s_layouts')
            ->whereNull('round_id')
            ->update(['round_id' => $roundId]);
    }
}
