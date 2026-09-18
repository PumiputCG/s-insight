<?php

namespace App\Services\Assessment;

use App\Models\Assessment\AsmEmployeeScore;
use App\Models\Assessment\AsmHierarchy;
use App\Models\Assessment\AsmRound;
use App\Models\Assessment\AsmScoreBox;
use App\Models\Insight\AppUser;
use App\Models\Insight\Employee;
use App\Support\PositionRank;
use Illuminate\Support\Collection;

/**
 * สิทธิ์จากลำดับชั้นผู้ประเมินใน asm_hierarchy
 *
 * - ลำดับ 1,2 = ประเมินพนักงาน
 * - ลำดับ 3,4 = ตรวจสอบผลลัพธ์
 */
class HierarchyAccess
{
    /**
     * @return array{can_evaluate:bool,can_review:bool,evaluate_count:int,review_count:int,codes:array<int,string>}
     */
    public function accessFor(AppUser $user, ?AsmRound $round = null): array
    {
        $round ??= AsmRound::open(AsmRound::TYPE_EMPLOYEE);
        $codes = $this->employeeCodes($user);

        if (! $round || $codes === []) {
            return [
                'can_evaluate' => false,
                'can_review' => false,
                'evaluate_count' => 0,
                'review_count' => 0,
                'codes' => $codes,
            ];
        }

        $evaluateCount = $this->countForLevels($round->id, $codes, [1, 2]);
        $reviewCount = $this->countForLevels($round->id, $codes, [3, 4]);

        return [
            'can_evaluate' => $evaluateCount > 0,
            'can_review' => $reviewCount > 0,
            'evaluate_count' => $evaluateCount,
            'review_count' => $reviewCount,
            'codes' => $codes,
        ];
    }

    /**
     * @param  array<int,int>  $levels
     * @return Collection<int,array{employee_code:string,name:string,position:string,department:string,avatar:?string,initial:string,levels:array<int,int>,groups:array<int,array<string,mixed>>,evaluators:array<int,array<string,mixed>>,level_status:array<int,array<string,mixed>>}>
     */
    public function assignmentsFor(AppUser $user, array $levels, ?AsmRound $round = null): Collection
    {
        $round ??= AsmRound::open(AsmRound::TYPE_EMPLOYEE);
        $codes = $this->employeeCodes($user);

        if (! $round || $codes === []) {
            return collect();
        }

        $rows = AsmHierarchy::query()
            ->where('round_id', $round->id)
            ->where(fn ($query) => $this->whereReviewer($query, $codes, $levels))
            ->orderBy('employee_code')
            ->get();

        $hierarchyEmployeeCodes = $rows->pluck('employee_code')->filter()->unique()->values()->all();
        $employees = $hierarchyEmployeeCodes === []
            ? collect()
            : Employee::active()->whereIn('employee_code', $hierarchyEmployeeCodes)->get()->keyBy('employee_code');

        // hierarchy และคะแนนเป็นข้อมูลตามรอบ จึงเก็บไว้เป็นประวัติได้ แต่หน้ารอบปัจจุบันต้องไม่สร้างงาน
        // ให้ผู้ประเมินจากพนักงานที่ลาออก/พ้นสภาพแล้ว
        $rows = $rows
            ->filter(fn (AsmHierarchy $row): bool => $employees->has((string) $row->employee_code))
            ->values();
        if ($rows->isEmpty()) {
            return collect();
        }

        $employeeCodes = $rows->pluck('employee_code')->filter()->unique()->values()->all();
        $reviewerCodes = $rows
            ->flatMap(function (AsmHierarchy $row) use ($levels): array {
                return array_map(
                    fn (int $level): string => trim((string) $row->{'l'.$level.'_id'}),
                    $levels,
                );
            })
            ->filter(fn (string $code): bool => $code !== '' && $code !== '-')
            ->unique()
            ->values()
            ->all();
        $reviewerEmployees = Employee::active()
            ->whereIn('employee_code', $reviewerCodes)
            ->get()
            ->unique('employee_code')
            ->keyBy('employee_code');
        $reviewerAvatars = $this->avatarsByEmployeeCode($reviewerEmployees);
        $avatars = $this->avatarsByEmployeeCode($employees);
        $inputBoxes = $this->inputBoxes();
        $scoresByEmployee = ($employeeCodes === [] || $inputBoxes->isEmpty())
            ? collect()
            : AsmEmployeeScore::where('round_id', $round->id)
                ->whereIn('employee_code', $employeeCodes)
                ->whereIn('box_id', $inputBoxes->pluck('id')->all())
                ->get()
                ->groupBy('employee_code')
                ->map(fn (Collection $scores): Collection => $scores->keyBy('box_id'));

        return $rows
            ->map(function (AsmHierarchy $row) use ($user, $codes, $levels, $employees, $reviewerEmployees, $reviewerAvatars, $avatars, $inputBoxes, $scoresByEmployee) {
                $employee = $employees[$row->employee_code] ?? null;
                $employeeCode = (string) $row->employee_code;
                $name = $employee?->fullNameTh() ?: $employeeCode;
                // กลุ่มลำดับ — ชื่อผู้ประเมินซ้ำกันหลายลำดับ = ปุ่มเดียว (ลำดับต่ำสุด) ครอบคลุมทุกลำดับที่ซ้ำ
                $groups = array_map(function (array $group) use ($user, $codes, $reviewerEmployees, $reviewerAvatars): array {
                    $reviewerCode = (string) ($group['employee_code'] ?? '');
                    $group['reviewer'] = $this->reviewerPerson(
                        $reviewerEmployees->get($reviewerCode),
                        in_array($reviewerCode, $codes, true) ? $user : null,
                        $reviewerCode,
                        (string) ($group['name'] ?? ''),
                        $reviewerAvatars->get($reviewerCode),
                    );

                    return $group;
                }, $this->matchedGroups($row, $codes, $levels));
                $scoresByBox = $scoresByEmployee->get($employeeCode) ?: collect();
                $allEvaluatorGroups = $this->hierarchyGroups($row, $levels);
                $allEvaluatorStatuses = $this->levelStatuses(
                    $levels,
                    $allEvaluatorGroups,
                    $inputBoxes,
                    $scoresByBox,
                );
                $evaluators = array_map(function (array $group) use ($user, $codes, $reviewerEmployees, $reviewerAvatars, $allEvaluatorStatuses): array {
                    $reviewerCode = (string) ($group['employee_code'] ?? '');
                    $group['reviewer'] = $this->reviewerPerson(
                        $reviewerEmployees->get($reviewerCode),
                        in_array($reviewerCode, $codes, true) ? $user : null,
                        $reviewerCode,
                        (string) ($group['name'] ?? ''),
                        $reviewerAvatars->get($reviewerCode),
                    );
                    $group['state'] = $allEvaluatorStatuses[$group['level']] ?? [
                        'assessed' => false,
                        'partial' => false,
                        'status' => 'pending',
                        'label' => 'รอประเมิน',
                    ];

                    return $group;
                }, $allEvaluatorGroups);

                return [
                    'employee_code' => $employeeCode,
                    'name' => $name,
                    'position' => (string) ($employee?->job_th ?: $employee?->job_en ?: ''),
                    'job_code' => (string) ($employee?->job_code ?? ''),
                    // ลำดับอาวุโสของตำแหน่ง ใช้เรียงสูง→ต่ำ ชุดเดียวกับระบบ OT (ดู App\Support\PositionRank)
                    'position_rank' => PositionRank::of($employee?->job_code),
                    'department' => (string) ($employee?->deptThClean() ?: $employee?->dept_en ?: ''),
                    // ชุดภาษาอังกฤษ — ใช้ตอนสลับธงเป็น EN/MY (ไม่มี EN ให้ถอยมาใช้ TH)
                    'name_en' => (string) ($employee?->fullNameEn() ?: $name),
                    'position_en' => (string) ($employee?->job_en ?: $employee?->job_th ?: ''),
                    'department_en' => (string) ($employee?->dept_en ?: $employee?->deptThClean() ?: ''),
                    'avatar' => $avatars->get($employeeCode),
                    'initial' => $this->initialFor($name, $employeeCode),
                    'levels' => array_column($groups, 'level'),
                    'groups' => $groups,
                    'evaluators' => $evaluators,
                    'level_status' => $this->levelStatuses($levels, $groups, $inputBoxes, $scoresByBox),
                ];
            })
            ->filter(fn (array $row) => $row['levels'] !== [])
            ->values();
    }

    /**
     * รหัสทั้งหมดของ user: รหัสหลัก + map บริษัทใน companies JSON
     *
     * @return array<int,string>
     */
    public function employeeCodes(AppUser $user): array
    {
        $codes = [];
        $primary = trim((string) $user->employee_code);
        if ($primary !== '' && strtolower($primary) !== 'admin') {
            $codes[] = $primary;
        }

        foreach ((array) ($user->companies ?? []) as $code) {
            $code = trim((string) $code);
            if ($code !== '') {
                $codes[] = $code;
            }
        }

        return array_values(array_unique($codes));
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

    private function initialFor(string $name, string $employeeCode): string
    {
        $text = trim($name) !== '' ? trim($name) : trim($employeeCode);

        return $text !== '' ? mb_substr($text, 0, 1, 'UTF-8') : '?';
    }

    /**
     * ตัวตนผู้ประเมินของกลุ่มลำดับนี้
     *
     * ชื่อไทยยึด snapshot ที่ Admin กำหนดใน asm_hierarchy เพื่อให้ตรงกับหน้า Results
     * ส่วนชื่ออังกฤษ ตำแหน่ง แผนก และรูป ใช้ Employee/AppUser master ปัจจุบัน
     *
     * @return array{employee_code:string,name:string,name_en:string,position:string,position_en:string,department:string,department_en:string,avatar:?string}
     */
    private function reviewerPerson(
        ?Employee $employee,
        ?AppUser $user,
        string $employeeCode,
        string $assignedName,
        ?string $avatar,
    ): array {
        $name = trim($assignedName);
        if ($name === '') {
            $name = trim((string) ($employee?->fullNameTh() ?: $user?->fullNameTh()));
        }

        $nameEn = trim((string) ($employee?->fullNameEn() ?: $user?->full_name_en ?: $name));
        $position = trim((string) ($employee?->job_th ?: $employee?->job_en ?: $user?->position));
        $positionEn = trim((string) ($employee?->job_en ?: $position));
        $department = trim((string) ($employee?->deptThClean() ?: $employee?->dept_en ?: $user?->department));
        $departmentEn = trim((string) ($employee?->dept_en ?: $department));
        $profile = trim((string) ($user?->profile_picture ?? ''));
        if (($avatar === null || trim($avatar) === '') && $profile !== '') {
            $avatar = asset('storage/'.$profile);
        }

        return [
            'employee_code' => $employeeCode !== '' ? $employeeCode : '-',
            'name' => $name !== '' ? $name : '-',
            'name_en' => $nameEn !== '' ? $nameEn : ($name !== '' ? $name : '-'),
            'position' => $position !== '' ? $position : '-',
            'position_en' => $positionEn !== '' ? $positionEn : ($position !== '' ? $position : '-'),
            'department' => $department !== '' ? $department : '-',
            'department_en' => $departmentEn !== '' ? $departmentEn : ($department !== '' ? $department : '-'),
            'avatar' => $avatar,
        ];
    }

    /**
     * @param  array<int,string>  $codes
     * @param  array<int,int>  $levels
     */
    private function countForLevels(int $roundId, array $codes, array $levels): int
    {
        $assignedEmployeeCodes = AsmHierarchy::query()
            ->where('round_id', $roundId)
            ->where(fn ($query) => $this->whereReviewer($query, $codes, $levels))
            ->distinct()
            ->pluck('employee_code')
            ->filter()
            ->unique()
            ->values();

        if ($assignedEmployeeCodes->isEmpty()) {
            return 0;
        }

        return (int) Employee::active()
            ->whereIn('employee_code', $assignedEmployeeCodes->all())
            ->distinct()
            ->count('employee_code');
    }

    /**
     * @param  array<int,string>  $codes
     * @param  array<int,int>  $levels
     */
    private function whereReviewer($query, array $codes, array $levels): void
    {
        $query->where(function ($outer) use ($codes, $levels) {
            foreach ($levels as $level) {
                $idField = 'l'.$level.'_id';
                $nameField = 'l'.$level.'_name';

                $outer->orWhere(function ($inner) use ($codes, $idField, $nameField) {
                    $inner
                        ->whereIn($idField, $codes)
                        ->whereNotNull($nameField)
                        ->whereRaw("TRIM(`{$nameField}`) <> ''")
                        ->where($nameField, '!=', '-');
                });
            }
        });
    }

    /**
     * กลุ่มลำดับที่ user นี้เป็นผู้ประเมิน — ชื่อคนเดียวกันหลายลำดับยุบเหลือกลุ่มเดียว (ลำดับต่ำสุดเป็นตัวแทน)
     * เช่น l1_name = l2_name = คนเดียวกัน → [['level' => 1, 'covers' => [1, 2], 'name' => ...]] = ปุ่มเดียว
     *
     * @param  array<int,string>  $codes
     * @param  array<int,int>  $levels
     * @return array<int,array{level:int,covers:array<int,int>,employee_code:string,name:string}>
     */
    private function matchedGroups(AsmHierarchy $row, array $codes, array $levels): array
    {
        $groups = array_values(array_filter(
            $this->hierarchyGroups($row, $levels),
            fn (array $group): bool => array_intersect($group['employee_codes'], $codes) !== [],
        ));

        return array_map(function (array $group) use ($codes): array {
            $matchedCodes = array_values(array_intersect($group['employee_codes'], $codes));
            if ($matchedCodes !== []) {
                $group['employee_code'] = $matchedCodes[0];
            }

            return $group;
        }, $groups);
    }

    /**
     * ผู้ประเมินทุกคนตาม hierarchy เรียงลำดับจากน้อยไปมาก
     * ชื่อเดียวกันหลายลำดับยุบเป็นคนเดียว เพื่อรักษาพฤติกรรมเดียวกับฟอร์มและ Results
     *
     * @param  array<int,int>  $levels
     * @return array<int,array{level:int,covers:array<int,int>,employee_code:string,employee_codes:array<int,string>,name:string}>
     */
    private function hierarchyGroups(AsmHierarchy $row, array $levels): array
    {
        $groups = [];

        foreach ($levels as $level) {
            $id = trim((string) $row->{'l'.$level.'_id'});
            $name = trim((string) $row->{'l'.$level.'_name'});
            if ($id === '' || $id === '-' || $name === '' || $name === '-') {
                continue;
            }

            $found = false;
            foreach ($groups as &$group) {
                if ($group['name'] !== $name) {
                    continue;
                }

                $group['covers'][] = $level;
                if (! in_array($id, $group['employee_codes'], true)) {
                    $group['employee_codes'][] = $id;
                }
                $found = true;
                break;
            }
            unset($group);

            if (! $found) {
                $groups[] = [
                    'level' => $level,
                    'covers' => [$level],
                    'employee_code' => $id,
                    'employee_codes' => [$id],
                    'name' => $name,
                ];
            }
        }

        return $groups;
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
     * สถานะรายลำดับ — available เฉพาะลำดับตัวแทนกลุ่ม (ลำดับที่ถูกยุบเพราะชื่อซ้ำ = none)
     * assessed = กรอก "ครบทุกคอลัมน์ Input" ของลำดับที่กลุ่มครอบคลุม (คะแนน 0-10 หรือ N/A นับเป็นกรอกแล้ว)
     * กรอกบางคอลัมน์ = partial → ยังไม่ขึ้นประเมินแล้ว แต่ view โชว์ "ยังประเมินไม่ครบ (x/y)"
     *
     * @param  array<int,int>  $levels
     * @param  array<int,array{level:int,covers:array<int,int>,name:string}>  $groups
     * @param  Collection<int,AsmScoreBox>  $inputBoxes
     * @param  Collection<int,AsmEmployeeScore>  $scoresByBox
     * @return array<int,array<string,mixed>>
     */
    private function levelStatuses(array $levels, array $groups, Collection $inputBoxes, Collection $scoresByBox): array
    {
        $byPrimary = [];
        foreach ($groups as $g) {
            $byPrimary[$g['level']] = $g;
        }

        $statuses = [];
        foreach ($levels as $level) {
            $group = $byPrimary[$level] ?? null;
            $available = $group !== null;
            $progress = $available
                ? $this->inputProgress($group['covers'], $inputBoxes, $scoresByBox)
                : ['filled' => 0, 'total' => 0];
            $assessed = $available && $progress['total'] > 0 && $progress['filled'] >= $progress['total'];
            $partial = $available && ! $assessed && $progress['filled'] > 0;

            $statuses[$level] = [
                'available' => $available,
                'assessed' => $assessed,
                'partial' => $partial,
                'progress' => $progress,
                'covers' => $group['covers'] ?? [],
                'status' => $available ? ($assessed ? 'done' : 'pending') : 'none',
                'label' => $available ? ($assessed ? 'ประเมินแล้ว' : 'รอประเมิน') : '-',
            ];
        }

        return $statuses;
    }

    /**
     * ความคืบหน้าการประเมินของกลุ่มลำดับ — นับต่อ "คอลัมน์ Input" ที่มีลำดับในกลุ่ม
     * คอลัมน์นับว่ากรอกแล้วเมื่อ slot ใดของลำดับที่ครอบคลุมมีค่า (ตัวเลข หรือ N/A)
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
}
