<?php

namespace App\Services\Assessment;

use App\Models\Assessment\AsmPositionLevel;
use App\Models\Assessment\AsmRound;
use App\Models\Assessment\AsmSelfParticipant;
use App\Models\Assessment\AsmSelfQuestion;
use App\Models\Assessment\AsmSelfSubmission;
use App\Models\Insight\AppUser;
use App\Models\Insight\Employee;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * รายชื่อผู้มีสิทธิ์ประเมินตนเองและข้อมูลตาราง Admin ของรอบ Self
 */
class SelfAssessmentRoster
{
    public function __construct(private readonly HierarchyAccess $hierarchyAccess) {}

    public function participantForUser(AppUser $user, ?AsmRound $round = null): ?AsmSelfParticipant
    {
        $round ??= AsmRound::open(AsmRound::TYPE_SELF);
        if (! $round) {
            return null;
        }

        $codes = $this->hierarchyAccess->employeeCodes($user);
        if ($codes === []) {
            return null;
        }

        $activeCodes = Employee::active()
            ->whereIn('employee_code', $codes)
            ->pluck('employee_code')
            ->map(fn ($code): string => (string) $code)
            ->all();

        if ($activeCodes === []) {
            return null;
        }

        return AsmSelfParticipant::query()
            ->where('round_id', $round->id)
            ->selected()
            ->whereIn('employee_code', $activeCodes)
            ->orderBy('employee_code')
            ->first();
    }

    public function canAssess(AppUser $user, ?AsmRound $round = null): bool
    {
        return $this->participantForUser($user, $round) !== null;
    }

    public function selectedCount(AsmRound $round): int
    {
        $selectedCodes = AsmSelfParticipant::query()
            ->where('round_id', $round->id)
            ->selected()
            ->pluck('employee_code')
            ->all();

        if ($selectedCodes === []) {
            return 0;
        }

        return Employee::active()
            ->whereIn('employee_code', $selectedCodes)
            ->count();
    }

    /**
     * @return Collection<int,array<string,mixed>>
     */
    public function rows(AsmRound $round): Collection
    {
        $participants = AsmSelfParticipant::query()
            ->where('round_id', $round->id)
            ->get()
            ->keyBy('employee_code');
        $levelMap = AsmPositionLevel::map($round->id);
        // จำนวนคำถามของแต่ละระดับ — ใช้ตัดคอลัมน์ "ข้อที่" ที่ระดับนั้นไม่มี ไม่ให้ขึ้น — เกินจริง
        $questionCountByLevel = AsmSelfQuestion::query()
            ->where('round_id', $round->id)
            ->get(['level'])
            ->groupBy(fn ($question): int => (int) $question->level)
            ->map->count()
            ->all();
        $submissions = AsmSelfSubmission::query()
            ->with('answers')
            ->where('round_id', $round->id)
            ->get()
            ->keyBy('employee_code');

        return Employee::active()
            ->orderBy('employee_code')
            ->get()
            ->map(function (Employee $employee) use ($participants, $levelMap, $questionCountByLevel, $submissions): array {
                $employeeCode = (string) $employee->employee_code;
                $participant = $participants->get($employeeCode);
                $submission = $submissions->get($employeeCode);
                $employeeLevel = array_key_exists((string) $employee->job_code, $levelMap)
                    ? (int) $levelMap[(string) $employee->job_code]
                    : null;

                return [
                    'employee_code' => $employeeCode,
                    'name' => $employee->fullNameTh(),
                    'name_en' => $employee->fullNameEn() ?: $employee->fullNameTh(),
                    'position' => (string) ($employee->job_th ?: $employee->job_en ?: '-'),
                    'position_en' => (string) ($employee->job_en ?: $employee->job_th ?: '-'),
                    'department' => (string) ($employee->deptThClean() ?: $employee->dept_en ?: '-'),
                    'department_en' => (string) ($employee->dept_en ?: $employee->deptThClean() ?: '-'),
                    'job_code' => (string) ($employee->job_code ?? ''),
                    'level' => $employeeLevel,
                    // จำนวนข้อที่ระดับนี้ต้องตอบจริง — คอลัมน์ถัดจากนี้ปล่อยว่าง
                    'level_question_count' => $employeeLevel === null
                        ? 0
                        : (int) ($questionCountByLevel[$employeeLevel] ?? 0),
                    'is_selected' => (bool) ($participant?->is_selected ?? false),
                    'self_score' => $submission?->total_percent === null
                        ? null
                        : (float) $submission->total_percent,
                    'self_earned_score' => $submission?->earned_score === null
                        ? null
                        : (float) $submission->earned_score,
                    'self_possible_score' => $submission?->possible_score === null
                        ? null
                        : (float) $submission->possible_score,
                    'question_scores' => $submission
                        ? $submission->answers
                            ->mapWithKeys(fn ($answer): array => [
                                (int) $answer->question_no => $answer->is_na
                                    ? 'N/A'
                                    : (float) $answer->percent,
                            ])
                            ->all()
                        : [],
                    'submitted_at' => $submission?->submitted_at,
                ];
            })
            ->values();
    }

    public function resultQuestionCount(AsmRound $round): int
    {
        $configured = AsmSelfQuestion::query()
            ->where('round_id', $round->id)
            ->get(['level', 'id'])
            ->groupBy('level')
            ->map->count()
            ->max() ?? 0;
        $answered = (int) DB::connection('mysql_assessment')
            ->table('asm_self_answers as answer')
            ->join('asm_self_submissions as submission', 'submission.id', '=', 'answer.submission_id')
            ->where('submission.round_id', $round->id)
            ->max('answer.question_no');

        return max((int) $configured, $answered);
    }

    /**
     * @param  array<int,array{employee_code:string,is_selected:bool}>  $changes
     */
    public function saveSelectionChanges(AsmRound $round, array $changes, int $actorId): int
    {
        $normalized = collect($changes)
            ->mapWithKeys(fn (array $change): array => [
                trim((string) $change['employee_code']) => (bool) $change['is_selected'],
            ])
            ->filter(fn (bool $selected, string $employeeCode): bool => $employeeCode !== '');

        $activeCodes = Employee::active()
            ->whereIn('employee_code', $normalized->keys()->all())
            ->pluck('employee_code')
            ->map(fn ($code): string => (string) $code)
            ->unique()
            ->values();

        if ($activeCodes->count() !== $normalized->count()) {
            throw ValidationException::withMessages([
                'changes' => 'พบพนักงานที่ไม่ได้ทำงานอยู่หรือไม่อยู่ใน Employee master ปัจจุบัน',
            ]);
        }

        DB::connection('mysql_assessment')->transaction(function () use ($round, $normalized, $actorId): void {
            foreach ($normalized as $employeeCode => $selected) {
                AsmSelfParticipant::updateOrCreate(
                    [
                        'round_id' => $round->id,
                        'employee_code' => $employeeCode,
                    ],
                    [
                        'is_selected' => $selected,
                        'selected_by' => $actorId,
                        'selected_at' => $selected ? now() : null,
                    ],
                );
            }
        });

        return $this->selectedCount($round);
    }
}
