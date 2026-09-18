<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const CONNECTION = 'mysql_assessment';

    public function up(): void
    {
        if (! Schema::connection(self::CONNECTION)->hasColumn('asm_self_questions', 'full_score')) {
            Schema::connection(self::CONNECTION)->table('asm_self_questions', function (Blueprint $table): void {
                $table->decimal('full_score', 8, 2)->default(5)->after('q_my');
            });
        }

        if (! Schema::connection(self::CONNECTION)->hasTable('asm_self_choices')) {
            return;
        }

        DB::connection(self::CONNECTION)
            ->table('asm_self_questions')
            ->select('id')
            ->orderBy('id')
            ->chunkById(200, function ($questions): void {
                foreach ($questions as $question) {
                    $maximumChoiceScore = DB::connection(self::CONNECTION)
                        ->table('asm_self_choices')
                        ->where('question_id', $question->id)
                        ->where('is_na', false)
                        ->max('score');

                    DB::connection(self::CONNECTION)
                        ->table('asm_self_questions')
                        ->where('id', $question->id)
                        ->update([
                            'full_score' => $maximumChoiceScore !== null && (float) $maximumChoiceScore > 0
                                ? (float) $maximumChoiceScore
                                : 5,
                        ]);
                }
            });
    }

    public function down(): void
    {
        if (Schema::connection(self::CONNECTION)->hasColumn('asm_self_questions', 'full_score')) {
            Schema::connection(self::CONNECTION)->table('asm_self_questions', function (Blueprint $table): void {
                $table->dropColumn('full_score');
            });
        }
    }
};
