<?php

namespace App\Services\Assessment;

use App\Models\Assessment\AsmEmployeeScore;
use App\Models\Assessment\AsmPositionLevel;
use App\Models\Assessment\AsmRound;
use App\Models\Assessment\AsmSelfSubmission;
use App\Models\Insight\Employee;
use Illuminate\Support\Collection;

/**
 * เตรียมคะแนนสรุปสำหรับรายชื่อผู้ตรวจสอบลำดับ 3–4 โดยใช้สูตรเดียวกับหน้าผลลัพธ์
 */
class ReviewResultList
{
    public function __construct(private readonly ResultCalculator $calculator) {}

    /**
     * @param  Collection<int,array<string,mixed>>  $assignments
     * @return Collection<int,array<string,mixed>>
     */
    public function enrich(Collection $assignments, AsmRound $round): Collection
    {
        $employeeCodes = $assignments
            ->pluck('employee_code')
            ->filter()
            ->map(fn ($code): string => (string) $code)
            ->unique()
            ->values();

        if ($employeeCodes->isEmpty()) {
            return $assignments;
        }

        $employees = Employee::active()
            ->whereIn('employee_code', $employeeCodes->all())
            ->get(['employee_code', 'job_code'])
            ->keyBy('employee_code');
        $positionLevels = AsmPositionLevel::map($round->id);
        $scoresByEmployee = AsmEmployeeScore::where('round_id', $round->id)
            ->whereIn('employee_code', $employeeCodes->all())
            ->get()
            ->groupBy('employee_code');
        // คะแนนประเมินตัวเองมาจาก "รอบ self" ที่เปิดอยู่ (คนละรอบกับรอบประเมินพนักงาน)
        $selfRound = AsmRound::open(AsmRound::TYPE_SELF);
        $selfPercents = $selfRound === null
            ? collect()
            : AsmSelfSubmission::where('round_id', $selfRound->id)
                ->whereIn('employee_code', $employeeCodes->all())
                ->get()
                ->keyBy('employee_code');

        return $assignments->map(function (array $row) use ($employees, $positionLevels, $scoresByEmployee, $selfPercents): array {
            $employeeCode = (string) $row['employee_code'];
            $employee = $employees->get($employeeCode);
            $employeeLevel = $employee && array_key_exists((string) $employee->job_code, $positionLevels)
                ? (int) $positionLevels[(string) $employee->job_code]
                : null;
            $values = collect($scoresByEmployee->get($employeeCode, collect()))
                ->mapWithKeys(fn (AsmEmployeeScore $score): array => [
                    $score->box_id => [
                        'v' => $score->value === null ? null : (float) $score->value,
                        'v2' => $score->value2 === null ? null : (float) $score->value2,
                        'v3' => $score->value3 === null ? null : (float) $score->value3,
                        'v4' => $score->value4 === null ? null : (float) $score->value4,
                        'na' => (int) ($score->na_mask ?? 0),
                    ],
                ])
                ->all();
            $result = $this->calculator->compute($employeeLevel, $values);
            $levelOneTotal = $result['totals'][1] ?? null;

            $row['level_one_total'] = $levelOneTotal;
            $row['level_one_total_display'] = $levelOneTotal === null
                ? '-'
                : number_format((float) $levelOneTotal, 2, '.', '');

            // คะแนนตนเอง — ค่าเดียวกับคอลัมน์ "คะแนนประเมินตัวเอง" ของ /assessment/self?tab=results
            $selfPercent = $selfPercents->get($employeeCode)?->total_percent;
            $row['self_score'] = $selfPercent === null ? null : (float) $selfPercent;
            $row['self_score_display'] = $row['self_score'] === null
                ? '-'
                : number_format($row['self_score'], 2, '.', '');

            return $row;
        });
    }
}
