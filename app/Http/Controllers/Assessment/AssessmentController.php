<?php

namespace App\Http\Controllers\Assessment;

use App\Http\Controllers\Assessment\Concerns\HandlesAssessmentAccess;
use App\Http\Controllers\Controller;
use App\Models\Assessment\AsmBoxNote;
use App\Models\Assessment\AsmEmployeeScore;
use App\Models\Assessment\AsmLevelProp;
use App\Models\Assessment\AsmMember;
use App\Models\Assessment\AsmPositionLevel;
use App\Models\Assessment\AsmQuestion;
use App\Models\Assessment\AsmRound;
use App\Models\Assessment\AsmScoreBox;
use App\Models\Insight\Employee;
use App\Services\Assessment\AssessmentOverview;
use App\Services\Assessment\HierarchyAccess;
use App\Services\Assessment\ResultCalculator;
use App\Services\Assessment\ReviewResultList;
use App\Services\Assessment\SelfAssessmentRoster;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View as ViewContract;

/**
 * โมดูล Assessment (ระบบประเมินผล) — หน้าภาพรวม + ตัวคุมสิทธิ์
 *
 * ใช้ DB connection แยก (mysql_assessment)
 */
class AssessmentController extends Controller
{
    use HandlesAssessmentAccess;

    /** ภาพรวม — admin / HR member / พนักงานที่ตำแหน่งได้รับอนุญาต */
    public function index(SelfAssessmentRoster $selfRoster): ViewContract|RedirectResponse
    {
        $me = $this->me();
        $isAsmAdmin = $me->isAdmin();
        $isAsmHr = AsmMember::isHr($me->id);

        $hierarchyAccess = app(HierarchyAccess::class)->accessFor($me);
        $canSelfAssess = $selfRoster->canAssess($me);

        // ตำแหน่งที่เข้าใช้ระบบได้ = เงื่อนไขบังคับ (Manager 2026-07-18) — บทบาท HR/สายบังคับบัญชาไม่ bypass · admin ยกเว้น
        if (! $isAsmAdmin && ! $this->positionAllowed() && ! $canSelfAssess) {
            return $this->denyToSystems(
                'toast.assessmentDenied',
                'คุณยังไม่ได้รับสิทธิ์เข้าใช้ระบบ Assessment',
            );
        }

        $openRound = AsmRound::open(AsmRound::TYPE_EMPLOYEE);
        $overview = app(AssessmentOverview::class)->forUser($me, $openRound);

        return view('assessment.index', [
            'me' => $me,
            'isAsmAdmin' => $isAsmAdmin,
            'isAsmHr' => $isAsmHr,
            'roleLabel' => $isAsmAdmin ? 'Admin' : ($isAsmHr ? 'HR' : 'User'),
            'openRound' => $openRound,
            'latestRound' => $openRound ?: AsmRound::orderByDesc('id')->first(),
            'hrMemberCount' => AsmMember::where('role', 'hr')->count(),
            'boxCount' => AsmScoreBox::whereNull('parent_id')->count(),
            'employeeCount' => Employee::active()->count(),
            'overview' => $overview,
            'canSelfAssess' => $canSelfAssess,
        ]);
    }

    /** ประเมินพนักงาน — ผู้ประเมินลำดับ 1,2 ของรอบ employee */
    public function evaluate(HierarchyAccess $access): ViewContract|RedirectResponse
    {
        $round = AsmRound::open(AsmRound::TYPE_EMPLOYEE);
        $assignments = $access->assignmentsFor($this->me(), [1, 2], $round);

        if ($assignments->isEmpty()) {
            return $this->denyToSystems('toast.pageDenied', 'คุณไม่มีรายการประเมินพนักงาน');
        }

        return view('assessment.evaluate', [
            'me' => $this->me(),
            'openRound' => $round,
            'assignments' => $assignments,
        ]);
    }

    /** หน้าประเมินรายคน — ฟอร์มกระดาษ: ติ๊ก checklist ต่อคอลัมน์ Input แล้วให้คะแนน 0-10 หรือ N/A */
    public function evaluateShow(string $employee, int $level, HierarchyAccess $access): ViewContract|RedirectResponse
    {
        if (! in_array($level, [1, 2], true)) {
            return $this->denyToSystems('toast.pageDenied', 'ลำดับผู้ประเมินไม่ถูกต้อง');
        }

        $round = AsmRound::open(AsmRound::TYPE_EMPLOYEE);
        $assignments = $access->assignmentsFor($this->me(), [$level], $round);
        $assignment = $assignments->first(function (array $row) use ($employee, $level): bool {
            return (string) $row['employee_code'] === (string) $employee
                && (bool) ($row['level_status'][$level]['available'] ?? false);
        });

        if (! $round || ! $assignment) {
            return $this->denyToSystems('toast.pageDenied', 'คุณไม่มีสิทธิ์ประเมินรายการนี้');
        }

        $roleAssignment = $access->assignmentsFor($this->me(), [1, 2], $round)
            ->first(fn (array $row): bool => (string) $row['employee_code'] === (string) $employee
                && (bool) ($row['level_status'][$level]['available'] ?? false));
        if ($roleAssignment) {
            $assignment['groups'] = $roleAssignment['groups'] ?? ($assignment['groups'] ?? []);
            $assignment['level_status'] = $roleAssignment['level_status'] ?? ($assignment['level_status'] ?? []);
        }

        $levelState = $assignment['level_status'][$level] ?? [];
        $covers = $levelState['covers'] ?? [$level];

        // คอลัมน์ Input ที่ผู้ประเมินชุดนี้ต้องให้คะแนน (input_levels มีลำดับใดในกลุ่ม)
        $boxes = AsmScoreBox::whereNotNull('parent_id')->where('type', 'input')
            ->orderBy('sort')->orderBy('id')->get()
            ->filter(fn ($box) => array_intersect(
                $covers,
                array_map('intval', array_filter(explode(',', $box->input_levels ?: '1,2'))),
            ) !== [])
            ->values();

        // คำอธิบายที่ admin เขียนไว้ที่หน้า "กำหนดคำถาม"
        // ผูกกับ "ระดับตำแหน่งของพนักงานที่ถูกประเมิน" (ชุดเดียวกับสัดส่วนคะแนน หัวข้อ 3.1)
        // ปิดสวิตช์ไว้ หรือไม่มีข้อความ = ไม่แสดง
        $noteMatrix = AsmBoxNote::matrix();

        // คำอธิบาย — คืนครบ 3 ภาษาให้ view สลับตามธง (ภาษาที่ไม่ได้กรอกจะถอยไปใช้ที่มี)
        // แสดงเมื่อมีข้อความ · ไม่เกี่ยวกับสวิตช์ซ่อนคอลัมน์
        $noteText = function (?int $boxId, ?int $lv) use ($noteMatrix): ?array {
            if ($boxId === null || $lv === null || $lv < 1) {
                return null;
            }
            $note = $noteMatrix[$boxId][(int) $lv] ?? null;
            if (! $note || ! $note->hasContent()) {
                return null;
            }

            $th = trim((string) $note->desc_th);
            $en = trim((string) $note->desc_en);
            $my = trim((string) $note->desc_my);
            $any = $th ?: ($en ?: $my);

            return [
                'th' => $th ?: $any,
                'en' => $en ?: ($th ?: $any),
                'my' => $my ?: ($en ?: ($th ?: $any)),
            ];
        };

        // คำอธิบายของ "จุดพิเศษ" ที่ไม่ใช่คอลัมน์ (ตอนนี้มี 'total' = คะแนนรวมในหัวฟอร์ม)
        $slotMatrix = AsmBoxNote::slotMatrix();
        $slotText = function (string $slot, ?int $lv) use ($slotMatrix): ?array {
            if ($lv === null || $lv < 1) {
                return null;
            }
            $note = $slotMatrix[$slot][(int) $lv] ?? null;
            if (! $note || ! $note->is_visible || ! $note->hasContent()) {
                return null;
            }

            $th = trim((string) $note->desc_th);
            $en = trim((string) $note->desc_en);
            $my = trim((string) $note->desc_my);
            $any = $th ?: ($en ?: $my);

            return [
                'th' => $th ?: $any,
                'en' => $en ?: ($th ?: $any),
                'my' => $my ?: ($en ?: ($th ?: $any)),
            ];
        };

        // สวิตช์ของ admin: is_visible = false → ซ่อน "ทั้งคอลัมน์" จากผู้ประเมิน (คะแนนยังคำนวณตามปกติ)
        $isHidden = function (?int $boxId, ?int $lv) use ($noteMatrix): bool {
            if ($boxId === null || $lv === null || $lv < 1) {
                return false;
            }
            $note = $noteMatrix[$boxId][(int) $lv] ?? null;

            return $note !== null && ! $note->is_visible;
        };

        // คำถาม checklist ของลำดับหลัก (ไม่มี → ใช้ของลำดับอื่นที่กลุ่มครอบ)
        $questions = AsmQuestion::whereIn('box_id', $boxes->pluck('id'))
            ->whereIn('level', $covers)
            ->orderBy('sort')->orderBy('id')->get()
            ->groupBy('box_id')
            ->map(fn ($qs) => $qs->groupBy('level')->get($level) ?? $qs->groupBy('level')->first());

        // คะแนนเดิม: ค่า slot แรกของกลุ่ม (merge = ทุก slot ค่าเดียวกัน) + na bit
        $scores = AsmEmployeeScore::where('round_id', $round->id)
            ->where('employee_code', $assignment['employee_code'])
            ->whereIn('box_id', $boxes->pluck('id'))
            ->get()->keyBy('box_id');

        $current = [];
        foreach ($boxes as $box) {
            $slots = array_map('intval', array_filter(explode(',', $box->input_levels ?: '1,2')));
            $part = null;
            foreach ($covers as $lv) {
                $idx = array_search($lv, $slots, true);
                if ($idx !== false) {
                    $part = $idx + 1;
                    break;
                }
            }
            $score = $scores->get($box->id);
            $value = null;
            $isNa = false;
            if ($score && $part !== null) {
                $field = $part === 1 ? 'value' : 'value'.$part;
                $value = $score->{$field} === null ? null : (float) $score->{$field};
                $isNa = (((int) ($score->na_mask ?? 0)) & (1 << ($part - 1))) !== 0;
            }
            $current[$box->id] = ['value' => $value, 'na' => $isNa];
        }

        $subjectEmployee = Employee::active()
            ->where('employee_code', $assignment['employee_code'])
            ->first();
        $levelMap = AsmPositionLevel::map($round->id);
        $employeeLevel = $subjectEmployee && array_key_exists((string) $subjectEmployee->job_code, $levelMap)
            ? (int) $levelMap[(string) $subjectEmployee->job_code]
            : null;
        $mainBoxes = AsmScoreBox::with('children')
            ->whereNull('parent_id')
            ->orderBy('sort')->orderBy('id')->get()
            ->values();
        $propMap = ($employeeLevel !== null && $employeeLevel > 0 && $mainBoxes->isNotEmpty())
            ? AsmLevelProp::where('level', $employeeLevel)
                ->whereIn('box_id', $mainBoxes->pluck('id')->all())
                ->get()
                ->keyBy('box_id')
            : collect();
        $proportionRows = $mainBoxes->map(function (AsmScoreBox $box) use ($propMap, $employeeLevel): array {
            $prop = $propMap->get($box->id);
            $mode = $employeeLevel !== null && $employeeLevel > 0 && $prop
                ? (in_array($prop->mode, ['extra', 'none'], true) ? $prop->mode : 'percent')
                : null;

            $isBonus = $box->type === 'bonus' || strcasecmp(trim($box->name), 'Bonus score') === 0;

            return [
                'name' => $box->name,
                'mode' => $mode,
                'weight' => $prop ? (float) $prop->weight : null,
                'show_value' => ! $isBonus,
            ];
        })->filter(fn (array $row): bool => ! ($row['show_value'] ?? true) || ($row['mode'] ?? null) !== 'none')->values();
        $columnLeafIds = $mainBoxes
            ->flatMap(fn (AsmScoreBox $box) => $box->children->isNotEmpty() ? $box->children->pluck('id') : [$box->id])
            ->unique()
            ->values();
        $columnScores = $columnLeafIds->isEmpty()
            ? collect()
            : AsmEmployeeScore::where('round_id', $round->id)
                ->where('employee_code', $assignment['employee_code'])
                ->whereIn('box_id', $columnLeafIds->all())
                ->get()
                ->keyBy('box_id');
        $resultValues = $columnScores->mapWithKeys(fn (AsmEmployeeScore $score): array => [
            $score->box_id => [
                'v' => $score->value === null ? null : (float) $score->value,
                'v2' => $score->value2 === null ? null : (float) $score->value2,
                'v3' => $score->value3 === null ? null : (float) $score->value3,
                'v4' => $score->value4 === null ? null : (float) $score->value4,
                'na' => (int) ($score->na_mask ?? 0),
            ],
        ])->all();
        $result = (new ResultCalculator)->compute($employeeLevel, $resultValues);
        $visibleResultLevels = array_values(array_intersect($covers, [1, 2], $result['levels']));
        $totalScoreValue = collect($visibleResultLevels)
            ->map(fn (int $resultLevel) => $result['totals'][$resultLevel] ?? null)
            ->first(fn ($score): bool => $score !== null);
        $totalScoreDisplay = $visibleResultLevels === []
            ? null
            : ($totalScoreValue === null ? '-' : number_format((float) $totalScoreValue, 2, '.', ''));
        $scoreDisplay = function (?AsmEmployeeScore $score): string {
            if (! $score) {
                return '-';
            }

            $values = [];
            $mask = (int) ($score->na_mask ?? 0);
            for ($part = 1; $part <= 4; $part++) {
                $field = $part === 1 ? 'value' : 'value'.$part;
                if (($mask & (1 << ($part - 1))) !== 0) {
                    $values[] = 'N/A';

                    continue;
                }
                if ($score->{$field} !== null) {
                    $values[] = rtrim(rtrim(number_format((float) $score->{$field}, 4, '.', ''), '0'), '.');
                }
            }

            return $values === [] ? '-' : implode(', ', $values);
        };
        $formatSingleScore = fn ($value): string => rtrim(rtrim(number_format((float) $value, 4, '.', ''), '0'), '.');
        $scoreDisplayForCovers = function (AsmScoreBox $box, ?AsmEmployeeScore $score) use ($covers, $formatSingleScore, $scoreDisplay): string {
            if ($box->type !== 'input') {
                return $scoreDisplay($score);
            }
            if (! $score) {
                return '-';
            }

            $slots = array_map('intval', array_filter(explode(',', $box->input_levels ?: '1,2')));
            foreach ($covers as $coveredLevel) {
                $index = array_search((int) $coveredLevel, $slots, true);
                if ($index === false) {
                    continue;
                }

                $part = $index + 1;
                if (((int) ($score->na_mask ?? 0) & (1 << ($part - 1))) !== 0) {
                    return 'N/A';
                }

                $field = $part === 1 ? 'value' : 'value'.$part;
                if ($score->{$field} !== null) {
                    return $formatSingleScore($score->{$field});
                }
            }

            return '-';
        };
        $sectionSummaries = $this->sectionSummaries($employeeLevel, $mainBoxes, $columnScores, $propMap, $covers);
        $displayMainBoxes = $mainBoxes->filter(function (AsmScoreBox $box) use ($employeeLevel, $propMap): bool {
            if ($employeeLevel === null || $employeeLevel < 1) {
                return false;
            }

            $prop = $propMap->get($box->id);
            if (! $prop) {
                return false;
            }

            $mode = in_array($prop->mode, ['extra', 'none'], true) ? $prop->mode : 'percent';
            if ($mode === 'none') {
                return false;
            }

            return $mode === 'extra' || (float) ($prop->weight ?? 0) > 0;
        })->values();

        $columnSections = $displayMainBoxes
            // หัวข้อที่ admin กดซ่อน → ไม่แสดงให้ผู้ประเมินเห็นทั้งหัวข้อ
            ->reject(fn (AsmScoreBox $box): bool => $isHidden((int) $box->id, $employeeLevel))
            ->map(function (AsmScoreBox $box) use ($columnScores, $current, $formatSingleScore, $scoreDisplayForCovers, $sectionSummaries, $noteText, $isHidden, $employeeLevel): array {
                $children = $box->children->isNotEmpty() ? $box->children : collect([$box]);
                // คอลัมน์ย่อยที่ admin กดซ่อน → ตัดออกจากตาราง (คะแนนยังคำนวณตามปกติ)
                $children = $children->reject(fn (AsmScoreBox $kid): bool => $isHidden((int) $kid->id, $employeeLevel))->values();
            $isAttendance = $box->type === 'attendance'
                || strcasecmp(trim($box->name), 'Attendance') === 0
                || $children->contains(fn (AsmScoreBox $child): bool => $child->type === 'attendance');
            $columns = $children->map(function (AsmScoreBox $child) use ($columnScores, $current, $isAttendance, $formatSingleScore, $scoreDisplayForCovers, $noteText, $employeeLevel): array {
                $value = $scoreDisplayForCovers($child, $columnScores->get($child->id));
                $inputState = $current[$child->id] ?? null;

                return [
                    'id' => $child->id,
                    'name' => $child->name,
                    'type' => $child->type,
                    'description' => $noteText((int) $child->id, $employeeLevel),
                    'value' => $isAttendance && $value === '-' ? '0' : $value,
                    'can_input' => $child->type === 'input' && $inputState !== null,
                    // ชุดตัวเลือกคะแนนที่ admin ตั้งไว้ (ยังไม่ตั้ง = ชุดเริ่มต้น)
                    'scale' => $child->type === 'input' ? $child->scale() : null,
                    'input_value' => $inputState && ! ($inputState['na'] ?? false) && $inputState['value'] !== null
                        ? $formatSingleScore($inputState['value'])
                        : '',
                    'input_na' => (bool) ($inputState['na'] ?? false),
                ];
            })->values();

            return [
                'id' => $box->id,
                'name' => $box->name,
                'is_attendance' => $isAttendance,
                'description' => $noteText((int) $box->id, $employeeLevel),
                'summary' => $sectionSummaries[$box->id] ?? null,
                'columns' => $columns,
                'inputs' => $columns->filter(fn (array $column): bool => (bool) ($column['can_input'] ?? false))->values(),
            ];
            })->values();

        return view('assessment.evaluate-form', [
            'me' => $this->me(),
            'openRound' => $round,
            'assignment' => $assignment,
            'level' => $level,
            'levelState' => $levelState,
            'covers' => $covers,
            'boxes' => $boxes,
            'questions' => $questions,
            'current' => $current,
            'employeeLevel' => $employeeLevel,
            'proportionRows' => $proportionRows,
            'totalScoreDisplay' => $totalScoreDisplay,
            'columnSections' => $columnSections,
            'totalNote' => $slotText('total', $employeeLevel),
        ]);
    }

    /** บันทึกคะแนนจากฟอร์มประเมิน (ผู้ประเมินตามลำดับชั้น ไม่ใช่ admin/HR) — เติมทุก slot ที่กลุ่มครอบคลุม */
    public function evaluateSave(Request $request, HierarchyAccess $access): JsonResponse
    {
        $data = $request->validate([
            'employee_code' => ['required', 'string'],
            'box_id' => ['required', 'integer'],
            'level' => ['required', 'integer', 'between:1,4'],
            'value' => ['nullable', 'string', 'max:10'],   // '', '0'-'10' (ทศนิยมได้), 'N/A'
        ]);

        $round = AsmRound::open(AsmRound::TYPE_EMPLOYEE);
        if (! $round) {
            return response()->json(['ok' => false, 'message' => 'ยังไม่มีรอบประเมินที่เปิดอยู่'], 422);
        }

        $assignments = $access->assignmentsFor($this->me(), [(int) $data['level']], $round);
        $assignment = $assignments->first(fn (array $row) => (string) $row['employee_code'] === (string) $data['employee_code']
            && (bool) ($row['level_status'][(int) $data['level']]['available'] ?? false));
        if (! $assignment) {
            return response()->json(['ok' => false, 'message' => 'คุณไม่มีสิทธิ์ประเมินรายการนี้'], 403);
        }
        $roleAssignment = $access->assignmentsFor($this->me(), [1, 2], $round)
            ->first(fn (array $row): bool => (string) $row['employee_code'] === (string) $data['employee_code']
                && (bool) ($row['level_status'][(int) $data['level']]['available'] ?? false));
        if ($roleAssignment) {
            $assignment['level_status'] = $roleAssignment['level_status'] ?? ($assignment['level_status'] ?? []);
        }
        $covers = $assignment['level_status'][(int) $data['level']]['covers'] ?? [(int) $data['level']];

        $box = AsmScoreBox::whereNotNull('parent_id')->where('type', 'input')->find((int) $data['box_id']);
        if (! $box) {
            return response()->json(['ok' => false, 'message' => 'ไม่พบคอลัมน์ประเมิน'], 422);
        }
        $slots = array_map('intval', array_filter(explode(',', $box->input_levels ?: '1,2')));
        $parts = [];
        foreach ($covers as $lv) {
            $idx = array_search($lv, $slots, true);
            if ($idx !== false) {
                $parts[] = $idx + 1;
            }
        }
        if ($parts === []) {
            return response()->json(['ok' => false, 'message' => 'คอลัมน์นี้ไม่อยู่ในลำดับที่คุณประเมิน'], 422);
        }

        // ค่า: '' = ลบ, N/A = ไม่คำนวณ (na bit), ตัวเลข 0-10
        $raw = trim((string) ($data['value'] ?? ''));
        $isNa = in_array(strtolower(str_replace(' ', '', $raw)), ['na', 'n/a'], true);
        $value = null;
        if ($raw !== '' && ! $isNa) {
            if (! is_numeric($raw) || (float) $raw < 0 || (float) $raw > 10) {
                return response()->json(['ok' => false, 'message' => 'คะแนนต้องอยู่ระหว่าง 0-10 หรือ N/A'], 422);
            }
            $value = round((float) $raw, 4);
        }

        $score = AsmEmployeeScore::firstOrNew([
            'round_id' => $round->id,
            'employee_code' => (string) $data['employee_code'],
            'box_id' => $box->id,
        ]);
        $mask = (int) ($score->na_mask ?? 0);
        foreach ($parts as $part) {
            $field = $part === 1 ? 'value' : 'value'.$part;
            $score->{$field} = $value;
            $mask = $isNa ? ($mask | (1 << ($part - 1))) : ($mask & ~(1 << ($part - 1)));
        }
        $score->na_mask = $mask;
        $score->updated_by = $this->me()->id;
        $score->save();

        $employeeCode = (string) $data['employee_code'];
        $employeeLevel = $this->employeeLevelForCode($round, $employeeCode);
        $resultValues = $this->resultValueMap($round->id, $employeeCode);
        $mainBoxes = AsmScoreBox::with('children')
            ->whereNull('parent_id')
            ->orderBy('sort')->orderBy('id')->get()
            ->values();
        $propMap = ($employeeLevel !== null && $employeeLevel > 0 && $mainBoxes->isNotEmpty())
            ? AsmLevelProp::where('level', $employeeLevel)
                ->whereIn('box_id', $mainBoxes->pluck('id')->all())
                ->get()
                ->keyBy('box_id')
            : collect();
        $leafIds = $mainBoxes
            ->flatMap(fn (AsmScoreBox $box) => $box->children->isNotEmpty() ? $box->children->pluck('id') : [$box->id])
            ->unique()
            ->values();
        $columnScores = $leafIds->isEmpty()
            ? collect()
            : AsmEmployeeScore::where('round_id', $round->id)
                ->where('employee_code', $employeeCode)
                ->whereIn('box_id', $leafIds->all())
                ->get()
                ->keyBy('box_id');

        return response()->json([
            'ok' => true,
            'display' => $isNa ? 'N/A' : ($value === null ? '' : rtrim(rtrim(number_format($value, 4, '.', ''), '0'), '.')),
            'total_score_display' => $this->totalScoreDisplay($employeeLevel, $resultValues, $covers),
            'section_summaries' => $this->sectionSummaries($employeeLevel, $mainBoxes, $columnScores, $propMap, $covers),
        ]);
    }

    private function employeeLevelForCode(?AsmRound $round, string $employeeCode): ?int
    {
        if (! $round) {
            return null;
        }

        $employee = Employee::active()->where('employee_code', $employeeCode)->first();
        $levelMap = AsmPositionLevel::map($round->id);

        return $employee && array_key_exists((string) $employee->job_code, $levelMap)
            ? (int) $levelMap[(string) $employee->job_code]
            : null;
    }

    /**
     * @return array<int,array{v:?float,v2:?float,v3:?float,v4:?float,na:int}>
     */
    private function resultValueMap(int $roundId, string $employeeCode): array
    {
        return AsmEmployeeScore::where('round_id', $roundId)
            ->where('employee_code', $employeeCode)
            ->get()
            ->mapWithKeys(fn (AsmEmployeeScore $score): array => [
                $score->box_id => [
                    'v' => $score->value === null ? null : (float) $score->value,
                    'v2' => $score->value2 === null ? null : (float) $score->value2,
                    'v3' => $score->value3 === null ? null : (float) $score->value3,
                    'v4' => $score->value4 === null ? null : (float) $score->value4,
                    'na' => (int) ($score->na_mask ?? 0),
                ],
            ])->all();
    }

    /**
     * @param  array<int,array{v:?float,v2:?float,v3:?float,v4:?float,na:int}>  $resultValues
     * @param  array<int,int>  $covers
     */
    private function totalScoreDisplay(?int $employeeLevel, array $resultValues, array $covers): ?string
    {
        $result = (new ResultCalculator)->compute($employeeLevel, $resultValues);
        $visibleLevels = array_values(array_intersect($covers, [1, 2], $result['levels']));
        $totalScore = collect($visibleLevels)
            ->map(fn (int $resultLevel) => $result['totals'][$resultLevel] ?? null)
            ->first(fn ($score): bool => $score !== null);

        return $visibleLevels === []
            ? null
            : ($totalScore === null ? '-' : number_format((float) $totalScore, 2, '.', ''));
    }

    /**
     * @param  array<int,int>  $covers
     * @return array<int,string>
     */
    private function sectionSummaries(?int $employeeLevel, $mainBoxes, $columnScores, $propMap, array $covers): array
    {
        if ($employeeLevel === null || $employeeLevel < 1) {
            return [];
        }

        $summaries = [];
        foreach ($mainBoxes as $box) {
            $prop = $propMap->get($box->id);
            $mode = $prop && in_array($prop->mode, ['extra', 'none'], true) ? $prop->mode : 'percent';
            if (! $prop || $mode === 'none') {
                continue;
            }

            $children = $box->children->isNotEmpty() ? $box->children : collect([$box]);
            if ($mode === 'extra') {
                $extra = 0.0;
                $hasExtra = false;
                foreach ($children as $child) {
                    $value = $this->scorePartValue($columnScores->get($child->id), 1);
                    if ($value !== null) {
                        $extra += $value;
                        $hasExtra = true;
                    }
                }
                $summaries[$box->id] = 'คะแนนเพิ่มเติม '.($hasExtra ? $this->formatLoose($extra) : '-');

                continue;
            }

            $weight = (float) ($prop->weight ?? 0);
            if ($weight <= 0) {
                continue;
            }

            $deduct = null;
            foreach ($children as $child) {
                if ($child->type !== 'attendance' || ($child->att_form ?? 'score') !== 'score' || $child->rate === null) {
                    continue;
                }

                $deduct = ($deduct ?? 0.0)
                    + (($this->scorePartValue($columnScores->get($child->id), 1) ?? 0.0) * (float) $child->rate);
            }

            if ($deduct !== null) {
                $summaries[$box->id] = $this->weightedSummary((max(0.0, 100.0 + $deduct) / 100.0) * $weight, $weight);

                continue;
            }

            $base = 0.0;
            $baseFull = 0.0;
            $sumByLevel = [];
            $fullByLevel = [];
            foreach ($children as $child) {
                if ($child->type === 'input' && (float) ($child->full_score ?? 0) > 0) {
                    $slots = array_map('intval', array_filter(explode(',', $child->input_levels ?: '1,2')));
                    foreach ($covers as $coveredLevel) {
                        $index = array_search((int) $coveredLevel, $slots, true);
                        if ($index === false) {
                            continue;
                        }

                        $value = $this->scorePartValue($columnScores->get($child->id), $index + 1);
                        if ($value === null) {
                            continue;
                        }

                        $sumByLevel[$coveredLevel] = ($sumByLevel[$coveredLevel] ?? 0.0) + $value;
                        $fullByLevel[$coveredLevel] = ($fullByLevel[$coveredLevel] ?? 0.0) + (float) $child->full_score;
                    }
                } elseif ($child->type === 'score' && (float) ($child->full_score ?? 0) > 0) {
                    $value = $this->scorePartValue($columnScores->get($child->id), 1);
                    if ($value === null) {
                        continue;
                    }
                    $base += $value;
                    $baseFull += (float) $child->full_score;
                }
            }

            $score = null;
            foreach ($covers as $coveredLevel) {
                $denom = $baseFull + ($fullByLevel[$coveredLevel] ?? 0.0);
                if ($denom <= 0) {
                    continue;
                }
                $score = (($base + ($sumByLevel[$coveredLevel] ?? 0.0)) / $denom) * $weight;
                break;
            }
            $summaries[$box->id] = $this->weightedSummary($score, $weight);
        }

        return $summaries;
    }

    private function scorePartValue(?AsmEmployeeScore $score, int $part): ?float
    {
        if (! $score || (((int) ($score->na_mask ?? 0)) & (1 << ($part - 1))) !== 0) {
            return null;
        }

        $field = $part === 1 ? 'value' : 'value'.$part;

        return $score->{$field} === null ? null : (float) $score->{$field};
    }

    private function weightedSummary(?float $score, float $weight): string
    {
        return 'ได้คะแนน '.($score === null ? '-' : number_format($score, 2, '.', '')).' เต็ม '.$this->formatLoose($weight).' เปอร์เซ็นต์';
    }

    private function formatLoose(float $value): string
    {
        return rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.');
    }

    /** ตรวจสอบผลลัพธ์ — ผู้ประเมินลำดับ 3,4 ของรอบ employee */
    public function review(HierarchyAccess $access, ReviewResultList $results): ViewContract|RedirectResponse
    {
        $round = AsmRound::open(AsmRound::TYPE_EMPLOYEE);
        $assignments = $access->assignmentsFor($this->me(), [3, 4], $round);

        if ($assignments->isEmpty()) {
            return $this->denyToSystems('toast.pageDenied', 'คุณไม่มีรายการตรวจสอบผลลัพธ์');
        }

        $assignments = $results->enrich($assignments, $round);

        return view('assessment.review', [
            'me' => $this->me(),
            'openRound' => $round,
            'assignments' => $assignments,
        ]);
    }

}
