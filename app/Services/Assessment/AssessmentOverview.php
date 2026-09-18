<?php

namespace App\Services\Assessment;

use App\Models\Assessment\AsmEmployeeScore;
use App\Models\Assessment\AsmHierarchy;
use App\Models\Assessment\AsmRound;
use App\Models\Assessment\AsmScoreBox;
use App\Models\Insight\AppUser;
use App\Models\Insight\Employee;
use Illuminate\Support\Collection;

class AssessmentOverview
{
    /**
     * @return array{
     *   round:?AsmRound,
     *   subject:array<string,mixed>,
     *   reviewers:Collection<int,array<string,mixed>>,
     *   has_reviewers:bool,
     *   has_hierarchy:bool,
     *   input_box_count:int
     * }
     */
    public function forUser(AppUser $user, ?AsmRound $round = null): array
    {
        $round ??= AsmRound::open(AsmRound::TYPE_EMPLOYEE);
        $codes = app(HierarchyAccess::class)->employeeCodes($user);
        $subjectEmployee = $this->firstEmployeeForCodes($codes);
        $hierarchy = $round ? $this->hierarchyForCodes($round, $codes) : null;
        $subjectCode = (string) ($hierarchy?->employee_code ?: $subjectEmployee?->employee_code ?: ($codes[0] ?? $user->employee_code));

        $reviewerCodes = $hierarchy
            ? collect([1, 2])
                ->map(fn (int $level): string => trim((string) $hierarchy->{'l'.$level.'_id'}))
                ->filter()
                ->values()
                ->all()
            : [];

        $employeesByCode = $this->employeesByCode(array_values(array_unique(array_merge($reviewerCodes, [$subjectCode]))));
        $avatarsByCode = $this->avatarsByEmployeeCode($employeesByCode);
        $inputBoxes = $this->inputBoxes();
        $scoresByBox = $round && $subjectCode !== ''
            ? AsmEmployeeScore::where('round_id', $round->id)
                ->where('employee_code', $subjectCode)
                ->whereIn('box_id', $inputBoxes->pluck('id')->all())
                ->get()
                ->keyBy('box_id')
            : collect();

        $reviewers = $hierarchy
            ? $this->reviewersForHierarchy($hierarchy, $employeesByCode, $avatarsByCode, $inputBoxes, $scoresByBox)
            : collect();

        $subject = $this->personCard(
            $employeesByCode->get($subjectCode) ?: $subjectEmployee,
            $subjectCode,
            $user->fullNameTh() ?: (string) $user->full_name_en,
            $user->profile_picture ? asset('storage/'.$user->profile_picture) : $avatarsByCode->get($subjectCode),
            $user
        );

        return [
            'round' => $round,
            'subject' => $subject,
            'reviewers' => $reviewers,
            'has_reviewers' => $reviewers->isNotEmpty(),
            'has_hierarchy' => (bool) $hierarchy,
            'input_box_count' => $inputBoxes->count(),
        ];
    }

    /**
     * @param  array<int,string>  $codes
     */
    private function firstEmployeeForCodes(array $codes): ?Employee
    {
        if ($codes === []) {
            return null;
        }

        $employees = $this->employeesByCode($codes);
        foreach ($codes as $code) {
            if ($employees->has($code)) {
                return $employees->get($code);
            }
        }

        return null;
    }

    /**
     * @param  array<int,string>  $codes
     */
    private function hierarchyForCodes(AsmRound $round, array $codes): ?AsmHierarchy
    {
        if ($codes === []) {
            return null;
        }

        $rows = AsmHierarchy::where('round_id', $round->id)
            ->whereIn('employee_code', $codes)
            ->get()
            ->keyBy('employee_code');

        foreach ($codes as $code) {
            if ($rows->has($code)) {
                return $rows->get($code);
            }
        }

        return null;
    }

    /**
     * @param  array<int,string>  $codes
     * @return Collection<string,Employee>
     */
    private function employeesByCode(array $codes): Collection
    {
        $codes = array_values(array_filter(array_unique(array_map('strval', $codes))));

        if ($codes === []) {
            return collect();
        }

        return Employee::active()
            ->whereIn('employee_code', $codes)
            ->get()
            ->unique('employee_code')
            ->keyBy('employee_code');
    }

    /**
     * @param  Collection<string,Employee>  $employees
     * @return Collection<string,string>
     */
    private function avatarsByEmployeeCode(Collection $employees): Collection
    {
        if ($employees->isEmpty()) {
            return collect();
        }

        $licenses = $employees
            ->pluck('license_id')
            ->filter()
            ->unique()
            ->values();

        $appUsersByLicense = $licenses->isEmpty()
            ? collect()
            : AppUser::whereIn('id_thai_hash', $licenses->all())->get()->keyBy('id_thai_hash');

        return $employees->mapWithKeys(function (Employee $employee) use ($appUsersByLicense) {
            $profile = $employee->license_id
                ? ($appUsersByLicense->get($employee->license_id)?->profile_picture)
                : null;

            return [
                $employee->employee_code => $profile ? asset('storage/'.$profile) : null,
            ];
        })->filter();
    }

    /**
     * @return Collection<int,AsmScoreBox>
     */
    private function inputBoxes(): Collection
    {
        return AsmScoreBox::where('type', 'input')
            ->whereNotNull('parent_id')
            ->orderBy('sort')
            ->orderBy('id')
            ->get();
    }

    /**
     * @param  Collection<string,Employee>  $employeesByCode
     * @param  Collection<string,string>  $avatarsByCode
     * @param  Collection<int,AsmScoreBox>  $inputBoxes
     * @param  Collection<int,AsmEmployeeScore>  $scoresByBox
     * @return Collection<int,array<string,mixed>>
     */
    private function reviewersForHierarchy(
        AsmHierarchy $hierarchy,
        Collection $employeesByCode,
        Collection $avatarsByCode,
        Collection $inputBoxes,
        Collection $scoresByBox
    ): Collection {
        $groups = [];
        $order = [];

        foreach ([1, 2] as $level) {
            $code = trim((string) $hierarchy->{'l'.$level.'_id'});
            $name = trim((string) $hierarchy->{'l'.$level.'_name'});

            if (! $this->hasReviewer($code, $name)) {
                continue;
            }

            $key = $code !== '' ? 'code:'.$code : 'name:'.$name;
            if (! isset($groups[$key])) {
                $groups[$key] = [
                    'code' => $code,
                    'name' => $name,
                    'levels' => [],
                ];
                $order[] = $key;
            }

            $groups[$key]['levels'][] = $level;
        }

        return collect($order)
            ->map(function (string $key) use ($groups, $employeesByCode, $avatarsByCode, $inputBoxes, $scoresByBox) {
                $group = $groups[$key];
                $code = $group['code'];
                $employee = $employeesByCode->get($code);
                $levels = array_values(array_unique($group['levels']));
                // ประเมินแล้ว = กรอกครบทุกคอลัมน์ Input ของลำดับที่คุม (คะแนนหรือ N/A) ; กรอกบางส่วน = ยังประเมินไม่ครบ
                $progress = $this->inputProgress($levels, $inputBoxes, $scoresByBox);
                $assessed = $progress['total'] > 0 && $progress['filled'] >= $progress['total'];
                $partial = ! $assessed && $progress['filled'] > 0;

                return $this->personCard($employee, $code, $group['name'], $avatarsByCode->get($code)) + [
                    'level' => $levels[0] ?? null,
                    'levels' => $levels,
                    'level_label' => $this->evaluatorRoleLabel($levels),
                    'level_label_key' => $this->evaluatorRoleKey($levels),
                    'assessed' => $assessed,
                    'partial' => $partial,
                    'progress' => $progress,
                    'status' => $assessed ? 'done' : 'pending',
                    'status_key' => $assessed ? 'assessment.evaluate.status.done' : ($partial ? 'assessment.evaluate.status.partial' : 'assessment.evaluate.status.pending'),
                    'status_label' => $assessed ? 'ประเมินแล้ว' : ($partial ? 'ยังประเมินไม่ครบ' : 'รอการดำเนินการ'),
                ];
            })
            ->values();
    }

    /**
     * @param  array<int,int>  $levels
     */
    private function evaluatorRoleLabel(array $levels): string
    {
        $levels = $this->normalizedLevels($levels);

        if ($levels === [1]) {
            return 'หัวหน้างานโดยตรง';
        }

        if ($levels === [2]) {
            return 'ผู้ประเมินระดับฝ่าย';
        }

        if (in_array(1, $levels, true) && in_array(2, $levels, true)) {
            return 'หัวหน้างานโดยตรง / ผู้ประเมินระดับฝ่าย';
        }

        return 'ผู้ประเมินลำดับที่ '.implode(', ', $levels);
    }

    /**
     * @param  array<int,int>  $levels
     */
    private function evaluatorRoleKey(array $levels): ?string
    {
        $levels = $this->normalizedLevels($levels);

        if ($levels === [1]) {
            return 'assessment.evaluate.role.directSupervisor';
        }

        if ($levels === [2]) {
            return 'assessment.evaluate.role.division';
        }

        if (in_array(1, $levels, true) && in_array(2, $levels, true)) {
            return 'assessment.evaluate.role.directAndDivision';
        }

        return null;
    }

    /**
     * @param  array<int,int>  $levels
     * @return array<int,int>
     */
    private function normalizedLevels(array $levels): array
    {
        $levels = array_values(array_unique(array_filter(array_map('intval', $levels))));
        sort($levels);

        return $levels;
    }

    /**
     * ความคืบหน้าการประเมิน — นับต่อคอลัมน์ Input ที่มีลำดับในชุดนี้ (กรอกแล้ว = มีค่าหรือ N/A ใน slot ของลำดับใดก็ได้)
     *
     * @param  array<int,int>  $evalLevels
     * @param  Collection<int,AsmScoreBox>  $inputBoxes
     * @param  Collection<int,AsmEmployeeScore>  $scoresByBox
     * @return array{filled:int,total:int}
     */
    private function inputProgress(array $evalLevels, Collection $inputBoxes, Collection $scoresByBox): array
    {
        $total = 0;
        $filled = 0;
        foreach ($inputBoxes as $box) {
            $slots = $this->inputLevels($box);
            $parts = [];
            foreach ($evalLevels as $level) {
                $index = array_search($level, $slots, true);
                if ($index !== false) {
                    $parts[] = $index + 1;
                }
            }
            if ($parts === []) {
                continue;
            }
            $total++;
            $score = $scoresByBox->get($box->id);
            if ($score) {
                foreach ($parts as $part) {
                    if ($this->scorePartFilled($score, $part)) {
                        $filled++;
                        break;
                    }
                }
            }
        }

        return ['filled' => $filled, 'total' => $total];
    }

    /**
     * @return array<int,int>
     */
    private function inputLevels(AsmScoreBox $box): array
    {
        $raw = trim((string) $box->input_levels);
        $levels = array_values(array_filter(
            array_map('intval', explode(',', $raw !== '' ? $raw : '1,2')),
            fn (int $level): bool => $level >= 1 && $level <= 4
        ));

        return $levels === [] ? [1, 2] : $levels;
    }

    /** slot นี้ถูกกรอกแล้วหรือยัง — มีค่าตัวเลข หรือผู้ใช้ใส่ N/A (na_mask) = ประเมินแล้ว */
    private function scorePartFilled(AsmEmployeeScore $score, int $part): bool
    {
        $field = $part === 1 ? 'value' : 'value'.$part;
        $value = $score->{$field} ?? null;
        if ($value !== null && $value !== '') {
            return true;
        }

        return (((int) ($score->na_mask ?? 0)) & (1 << ($part - 1))) !== 0;
    }

    private function hasReviewer(string $code, string $name): bool
    {
        return $code !== '' && $code !== '-' && $name !== '' && $name !== '-';
    }

    /**
     * @return array<string,mixed>
     */
    private function personCard(?Employee $employee, string $code, string $fallbackName, ?string $avatar, ?AppUser $user = null): array
    {
        $name = trim((string) ($employee?->fullNameTh() ?: $fallbackName));
        $position = trim((string) ($employee?->job_th ?: $employee?->job_en ?: $user?->position));
        $department = trim((string) ($employee?->deptThClean() ?: $employee?->dept_en ?: $user?->department));

        // ชุดภาษาอังกฤษ — ใช้ตอนสลับธงเป็น EN/MY (ไม่มี EN ให้ถอยมาใช้ TH)
        $nameEn = trim((string) ($employee?->fullNameEn() ?: ($user?->full_name_en ?: $name)));
        $positionEn = trim((string) ($employee?->job_en ?: $position));
        $departmentEn = trim((string) ($employee?->dept_en ?: $department));

        return [
            'employee_code' => $code !== '' ? $code : '-',
            'name' => $name !== '' ? $name : '-',
            'position' => $position !== '' ? $position : '-',
            'department' => $department !== '' ? $department : '-',
            'name_en' => $nameEn !== '' ? $nameEn : ($name !== '' ? $name : '-'),
            'position_en' => $positionEn !== '' ? $positionEn : ($position !== '' ? $position : '-'),
            'department_en' => $departmentEn !== '' ? $departmentEn : ($department !== '' ? $department : '-'),
            'avatar' => $avatar,
            'initial' => $name !== '' ? mb_substr($name, 0, 1, 'UTF-8') : '?',
        ];
    }
}
