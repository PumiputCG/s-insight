<?php

namespace Tests\Unit\Assessment;

use App\Models\Assessment\AsmSelfChoice;
use App\Models\Assessment\AsmSelfQuestion;
use App\Services\Assessment\SelfAssessmentService;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class SelfAssessmentServiceTest extends TestCase
{
    public function test_calculates_each_question_and_total_from_question_full_score(): void
    {
        $questions = collect([
            $this->question(1, 11),
            $this->question(2, 22),
            $this->question(3, 33),
        ]);

        $result = app(SelfAssessmentService::class)->calculate($questions, [
            1 => 12,
            2 => 24,
            3 => 36,
        ]);

        $this->assertSame(9.0, $result['earned_score']);
        $this->assertSame(15.0, $result['possible_score']);
        $this->assertSame(60.0, $result['total_percent']);
        $this->assertSame([80.0, 60.0, 40.0], array_column($result['answers'], 'percent'));
    }

    public function test_na_is_answered_but_excluded_from_earned_and_possible_scores(): void
    {
        $questions = collect([
            $this->question(1, 11),
            $this->question(2, 22),
            $this->question(3, 33),
        ]);

        $result = app(SelfAssessmentService::class)->calculate($questions, [
            1 => 11,
            2 => 30,
            3 => 36,
        ]);

        $this->assertSame(7.0, $result['earned_score']);
        $this->assertSame(10.0, $result['possible_score']);
        $this->assertSame(70.0, $result['total_percent']);
        $this->assertNull($result['answers'][1]['percent']);
        $this->assertTrue($result['answers'][1]['is_na']);
    }

    public function test_all_na_answers_have_no_calculated_total(): void
    {
        $questions = collect([$this->question(1, 11), $this->question(2, 22)]);

        $result = app(SelfAssessmentService::class)->calculate($questions, [
            1 => 19,
            2 => 30,
        ]);

        $this->assertNull($result['earned_score']);
        $this->assertNull($result['possible_score']);
        $this->assertNull($result['total_percent']);
    }

    public function test_question_full_score_is_independent_from_highest_choice_score(): void
    {
        $result = app(SelfAssessmentService::class)->calculate(
            collect([$this->question(1, 11, 10)]),
            [1 => 12],
        );

        $this->assertSame(4.0, $result['earned_score']);
        $this->assertSame(10.0, $result['possible_score']);
        $this->assertSame(40.0, $result['total_percent']);
        $this->assertSame(40.0, $result['answers'][0]['percent']);
    }

    public function test_rejects_choice_score_above_question_full_score(): void
    {
        $this->expectException(ValidationException::class);

        app(SelfAssessmentService::class)->calculate(
            collect([$this->question(1, 11, 3)]),
            [1 => 12],
        );
    }

    private function question(int $questionId, int $choiceBase, float $fullScore = 5): AsmSelfQuestion
    {
        $question = (new AsmSelfQuestion)->forceFill([
            'id' => $questionId,
            'full_score' => $fullScore,
        ]);
        $choices = new Collection;
        foreach ([5, 4, 3, 2, 1] as $offset => $score) {
            $choices->push((new AsmSelfChoice)->forceFill([
                'id' => $choiceBase + (5 - $score),
                'question_id' => $questionId,
                'score' => $score,
                'is_na' => false,
            ]));
        }
        $choices->push((new AsmSelfChoice)->forceFill([
            'id' => $choiceBase + 8,
            'question_id' => $questionId,
            'score' => null,
            'is_na' => true,
        ]));
        $question->setRelation('choices', $choices);

        return $question;
    }
}
