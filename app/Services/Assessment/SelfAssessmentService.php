<?php

namespace App\Services\Assessment;

use App\Models\Assessment\AsmRound;
use App\Models\Assessment\AsmSelfAnswer;
use App\Models\Assessment\AsmSelfQuestion;
use App\Models\Assessment\AsmSelfSubmission;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SelfAssessmentService
{
    /**
     * @param  Collection<int,AsmSelfQuestion>  $questions
     * @param  array<int|string,int|string>  $selectedChoices  question_id => choice_id
     * @return array{earned_score:?float,possible_score:?float,total_percent:?float,answers:array<int,array<string,mixed>>}
     */
    public function calculate(Collection $questions, array $selectedChoices): array
    {
        $earned = 0.0;
        $possible = 0.0;
        $answerRows = [];

        foreach ($questions->values() as $index => $question) {
            $choiceId = (int) ($selectedChoices[$question->id] ?? 0);
            $choice = $question->choices->firstWhere('id', $choiceId);
            if (! $choice) {
                throw ValidationException::withMessages([
                    'answers.'.$question->id => 'กรุณาเลือกคำตอบข้อที่ '.($index + 1),
                ]);
            }

            $fullScore = (float) ($question->full_score ?? 0);

            if (! $choice->is_na && $fullScore <= 0) {
                throw ValidationException::withMessages([
                    'answers.'.$question->id => 'คำถามข้อที่ '.($index + 1).' ยังไม่ได้กำหนดคะแนนเต็มที่มากกว่า 0',
                ]);
            }

            $score = $choice->is_na ? null : (float) $choice->score;
            if (! $choice->is_na && $score > $fullScore) {
                throw ValidationException::withMessages([
                    'answers.'.$question->id => 'คะแนนตัวเลือกของคำถามข้อที่ '.($index + 1).' เกินคะแนนเต็ม',
                ]);
            }
            $percent = $choice->is_na ? null : round(($score / $fullScore) * 100, 2);

            if (! $choice->is_na) {
                $earned += $score;
                $possible += $fullScore;
            }

            $answerRows[] = [
                'question_id' => (int) $question->id,
                'choice_id' => (int) $choice->id,
                'question_no' => $index + 1,
                'is_na' => (bool) $choice->is_na,
                'score' => $score,
                'full_score' => $choice->is_na ? null : $fullScore,
                'percent' => $percent,
            ];
        }

        return [
            'earned_score' => $possible > 0 ? round($earned, 2) : null,
            'possible_score' => $possible > 0 ? round($possible, 2) : null,
            'total_percent' => $possible > 0 ? round(($earned / $possible) * 100, 2) : null,
            'answers' => $answerRows,
        ];
    }

    /**
     * @param  Collection<int,AsmSelfQuestion>  $questions
     * @param  array<int|string,int|string>  $selectedChoices
     */
    public function submit(
        AsmRound $round,
        string $employeeCode,
        int $level,
        Collection $questions,
        array $selectedChoices,
    ): AsmSelfSubmission {
        if ($questions->isEmpty()) {
            throw ValidationException::withMessages([
                'answers' => 'ระดับของคุณยังไม่มีคำถาม กรุณาติดต่อ Admin',
            ]);
        }

        $calculation = $this->calculate($questions, $selectedChoices);

        return DB::connection('mysql_assessment')->transaction(function () use (
            $round,
            $employeeCode,
            $level,
            $calculation,
        ): AsmSelfSubmission {
            $submission = AsmSelfSubmission::updateOrCreate(
                [
                    'round_id' => $round->id,
                    'employee_code' => $employeeCode,
                ],
                [
                    'level' => $level,
                    'earned_score' => $calculation['earned_score'],
                    'possible_score' => $calculation['possible_score'],
                    'total_percent' => $calculation['total_percent'],
                    'submitted_at' => now(),
                ],
            );

            AsmSelfAnswer::query()->where('submission_id', $submission->id)->delete();
            foreach ($calculation['answers'] as $answer) {
                $submission->answers()->create($answer);
            }

            return $submission->load('answers');
        });
    }
}
