<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const CONNECTION = 'mysql_assessment';

    public function up(): void
    {
        Schema::connection(self::CONNECTION)->create('asm_self_questions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('round_id');
            $table->unsignedTinyInteger('level');
            $table->unsignedInteger('sort')->default(1);
            $table->text('q_th');
            $table->text('q_en');
            $table->text('q_my');
            $table->timestamps();

            $table->index(['round_id', 'level', 'sort'], 'asm_self_questions_round_level_sort_index');
        });

        Schema::connection(self::CONNECTION)->create('asm_self_choices', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('question_id');
            $table->unsignedInteger('sort')->default(1);
            $table->string('choice_th', 255);
            $table->string('choice_en', 255);
            $table->string('choice_my', 255);
            $table->decimal('score', 8, 2)->nullable();
            $table->boolean('is_na')->default(false);
            $table->timestamps();

            $table->index(['question_id', 'sort'], 'asm_self_choices_question_sort_index');
        });

        Schema::connection(self::CONNECTION)->create('asm_self_submissions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('round_id');
            $table->string('employee_code', 50);
            $table->unsignedTinyInteger('level');
            $table->decimal('earned_score', 10, 2)->nullable();
            $table->decimal('possible_score', 10, 2)->nullable();
            $table->decimal('total_percent', 7, 2)->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();

            $table->unique(['round_id', 'employee_code'], 'asm_self_submissions_round_employee_unique');
            $table->index(['round_id', 'level'], 'asm_self_submissions_round_level_index');
        });

        Schema::connection(self::CONNECTION)->create('asm_self_answers', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('submission_id');
            $table->unsignedBigInteger('question_id');
            $table->unsignedBigInteger('choice_id');
            $table->unsignedInteger('question_no');
            $table->boolean('is_na')->default(false);
            $table->decimal('score', 8, 2)->nullable();
            $table->decimal('full_score', 8, 2)->nullable();
            $table->decimal('percent', 7, 2)->nullable();
            $table->timestamps();

            $table->unique(['submission_id', 'question_id'], 'asm_self_answers_submission_question_unique');
            $table->index(['question_id', 'choice_id'], 'asm_self_answers_question_choice_index');
        });
    }

    public function down(): void
    {
        Schema::connection(self::CONNECTION)->dropIfExists('asm_self_answers');
        Schema::connection(self::CONNECTION)->dropIfExists('asm_self_submissions');
        Schema::connection(self::CONNECTION)->dropIfExists('asm_self_choices');
        Schema::connection(self::CONNECTION)->dropIfExists('asm_self_questions');
    }
};
