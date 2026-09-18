<?php

namespace App\Http\Controllers\Assessment;

use App\Http\Controllers\Assessment\Concerns\HandlesAssessmentAccess;
use App\Http\Controllers\Controller;
use App\Models\Assessment\AsmEmployeeScore;
use App\Models\Assessment\AsmHierarchy;
use App\Models\Assessment\AsmImportBatch;
use App\Models\Assessment\AsmLevelProp;
use App\Models\Assessment\AsmPositionLevel;
use App\Models\Assessment\AsmRound;
use App\Models\Assessment\AsmScoreBox;
use App\Models\Insight\Employee;
use App\Services\Assessment\ResultCalculator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View as ViewContract;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * จัดการคะแนน Assessment (admin + HR) — โมเดล "คอลัมน์ชุดเดียว (global) + ระดับ 1–5"
 *
 * - การ์ดระดับ 1–5 : จัดตำแหน่ง (job_code) ลงระดับ → เติมคอลัมน์ "ระดับตำแหน่ง" ตอนดาวน์โหลด
 * - คอลัมน์คะแนน global : กล่องใหญ่ + คอลัมน์ย่อย ใช้ร่วมทุกคน
 * - Template Excel : base + ลำดับชั้น 1–4 (ว่างให้ HR กรอก) + ระดับ + คอลัมน์คะแนน
 * - ดูตัวอย่าง (preview) + import + แก้คะแนนในเว็บ
 */
class AssessmentScoreController extends Controller
{
    use HandlesAssessmentAccess;

    /** คอลัมน์พนักงานคงที่ (row2 header) */
    private const FIXED_BASE = ['รหัสพนักงาน', 'ชื่อ-สกุล(ไทย)', 'ชื่อ-สกุล(Eng)', 'ตำแหน่ง', 'แผนก'];

    /** ลำดับชั้น 1–4 : [กลุ่ม(row1), หัว(row2)] — ในดาวน์โหลดเว้นว่างให้ HR กรอก */
    private const HIER = [
        ['ลำดับชั้น 1', 'ID Supervisor'], ['ลำดับชั้น 1', 'Supervisor Name'],
        ['ลำดับชั้น 2', 'ID Dept.MGR'], ['ลำดับชั้น 2', 'Division Manager Name'],
        ['ลำดับชั้น 3', 'ID Dept.MGR'], ['ลำดับชั้น 3', 'Dept.MGR Name'],
        ['ลำดับชั้น 4', 'ID Plant MGR'], ['ลำดับชั้น 4', 'Plant Manager Name'],
    ];

    private const LEVEL_HEADER = 'ระดับตำแหน่ง (1–5)';

    private const RESULT_FILTER_EMPTY = '__asm_empty__';

    private const RESULT_FILTER_NONE = '__asm_none__';

    /** รอบที่เปิดอยู่ (null = ยังไม่เปิดรอบ — จัดการระบบไม่ได้) */
    private function openRound(): ?AsmRound
    {
        return AsmRound::open();
    }

    /** รอบที่กำลังดู: ?round= ในหน้า ผลลัพธ์ ; ค่าเริ่มต้น = รอบที่เปิด */
    private function viewRound(Request $request): ?AsmRound
    {
        $id = (int) $request->query('round');
        // รับเฉพาะรอบชนิด employee — กัน id ของรอบประเมินตัวเองหลุดเข้ามาทางพารามิเตอร์
        $round = $id
            ? AsmRound::where('type', AsmRound::TYPE_EMPLOYEE)->find($id)
            : null;

        return $round ?: ($id ? null : AsmRound::open());
    }

    public function index(Request $request): ViewContract|RedirectResponse
    {
        if ($redirect = $this->gateAdminOrHr()) {
            return $redirect;
        }

        $round = $this->openRound();
        if (! $round) {
            return redirect()->route('assessment.rounds.index')
                ->with('round_warn', 'ต้องเปิดรอบก่อนจึงจะจัดการระบบภายในได้');
        }

        if ($request->query->has('q')) {
            return redirect()->to($request->fullUrlWithoutQuery('q'));
        }

        $levelMap = AsmPositionLevel::map($round->id);
        $positions = array_map(
            fn ($p) => $p + ['level' => $levelMap[$p['code']] ?? null],
            $this->positionOptions(),
        );

        // ระดับสูงสุดที่ใช้อยู่ — ระดับ 0 (ไม่ถูกประเมิน) เป็น default มีเสมอ ; ระดับ 1,2,3,... admin เพิ่มเองด้วยปุ่ม "+" ใน 1.1
        // 0 = ยังไม่เพิ่มระดับเลย ; ระดับคงอยู่เมื่อมีการจัดตำแหน่งลงระดับ หรือกำหนดสัดส่วนใน 3.1 แล้ว
        $maxLevel = $this->currentMaxLevel($round->id);

        // แบ่งหน้า — กัน DOM ใหญ่จนเบราว์เซอร์ค้าง (2 ตาราง × พนักงานทั้งหมด)
        $perPage = (int) $request->query('per_page', 100);
        $perPage = in_array($perPage, [100, 200, 500, 1000], true) ? $perPage : 100;

        // ตัวกรองรายคอลัมน์ (funnel) — กรองข้ามทุกหน้าเหมือนหน้า "ผลลัพธ์" (คีย์ = ดัชนีคอลัมน์ผลลัพธ์)
        $filters = $this->resultFilterState($request);

        if ($filters !== []) {
            // มีตัวกรอง → โหลดทุกคนแล้วกรอง ก่อนแบ่งหน้าเอง
            $dataset = $this->scoresDataset($round, $filters);
            $filtered = $dataset['employees'];
            $page = max(1, (int) $request->query('page', 1));
            $employees = new LengthAwarePaginator(
                $filtered->slice(($page - 1) * $perPage, $perPage)->values(),
                $filtered->count(),
                $perPage,
                $page,
                ['path' => $request->url(), 'query' => $request->query()],
            );
        } else {
            $employees = Employee::active()
                ->orderBy('employee_code')
                ->paginate($perPage)
                ->withQueryString();
        }

        $pageCodes = $employees->getCollection()->pluck('employee_code')->map(fn ($c) => (string) $c)->unique()->values();
        $hierMap = $pageCodes->isEmpty()
            ? collect()
            : AsmHierarchy::where('round_id', $round->id)->whereIn('employee_code', $pageCodes->all())->get()->keyBy('employee_code');

        return view('assessment.scores', [
            'me' => app('current_user'),
            'openRound' => $round,
            'positions' => $positions,
            'levelMap' => $levelMap,
            'hierMap' => $hierMap,
            'employees' => $employees,
            'perPage' => $perPage,
            'q' => '',
            'filters' => $filters,
            'activeFilterCount' => count($filters),
            'maxLevel' => $maxLevel,
            // หัวข้อ 2 จัดการคอลัมน์ — การ์ดหัวข้อหลัก + คอลัมน์รองข้างใน
            'columnBoxes' => AsmScoreBox::whereNull('parent_id')->with('children')->orderBy('sort')->orderBy('id')->get(),
            // หัวข้อ 3.1 สัดส่วนของระดับ — {level: {box_id: {mode, weight}}}
            'levelPropMatrix' => AsmLevelProp::matrix() ?: new \stdClass,
            // หัวข้อ 3.2 การคำนวณคอลัมน์รอง — {sub_id: {type, att_form, rate, grade_cap}}
            'subCalcMatrix' => AsmScoreBox::whereNotNull('parent_id')->get()->mapWithKeys(fn ($b) => [$b->id => [
                'type' => $b->type,
                'att_form' => $b->att_form,
                'rate' => $b->rate === null ? null : (float) $b->rate,
                'grade_cap' => $b->grade_cap,
                'full_score' => $b->full_score === null ? null : (float) $b->full_score,
                'input_levels' => $b->input_levels,
            ]]) ?: new \stdClass,
        ] + $this->resultSectionData($employees, $levelMap, $round->id, $hierMap));
    }

    /**
     * ข้อมูลตารางคะแนน+ผลลัพธ์ของรอบหนึ่ง — ใช้ทั้งหัวข้อ 4 (จัดการ) และหน้าผลลัพธ์
     *
     * @return array<string,mixed>
     */
    private function resultSectionData($employees, array $levelMap, int $roundId, $hierMap = null): array
    {
        $cols = $this->buildColumns();
        $calc = new ResultCalculator;
        $employeeRows = method_exists($employees, 'getCollection') ? $employees->getCollection() : collect($employees);
        $employeeCodes = $employeeRows
            ->pluck('employee_code')
            ->filter(fn ($code) => $code !== null && $code !== '')
            ->map(fn ($code) => (string) $code)
            ->unique()
            ->values();

        if ($hierMap === null) {
            $hierMap = $employeeCodes->isEmpty()
                ? collect()
                : AsmHierarchy::where('round_id', $roundId)->whereIn('employee_code', $employeeCodes->all())->get()->keyBy('employee_code');
        }

        $scoreMap4 = [];
        $scoreQuery = AsmEmployeeScore::where('round_id', $roundId);
        if ($employeeCodes->isNotEmpty()) {
            $scoreQuery->whereIn('employee_code', $employeeCodes->all());
        } else {
            $scoreQuery->whereRaw('1 = 0');
        }

        foreach ($scoreQuery->get() as $s) {
            $scoreMap4[$s->employee_code][$s->box_id] = [
                'v' => $s->value === null ? null : (float) $s->value,
                'v2' => $s->value2 === null ? null : (float) $s->value2,
                'v3' => $s->value3 === null ? null : (float) $s->value3,
                'v4' => $s->value4 === null ? null : (float) $s->value4,
                'na' => (int) $s->na_mask,   // bitmask ราย slot: ผู้ใช้ตั้งใจใส่ N/A (แสดงค้าง แต่ไม่คำนวณเหมือนว่าง)
            ];
        }

        $results4 = [];
        $resultHeaders = $calc->resultHeaders();
        foreach ($employees as $e) {
            if (isset($results4[$e->employee_code])) {
                continue;
            }
            $lv = $this->employeeLevel($e, $levelMap);
            $h = $hierMap[$e->employee_code] ?? null;
            $results4[$e->employee_code] = ($lv === 0 || ! $this->hasCompleteHierarchy($h))
                ? array_fill(0, count($resultHeaders), '-')
                : $calc->rowCells($calc->compute($lv, $scoreMap4[$e->employee_code] ?? []));
        }

        return [
            'zones4' => $cols['zones'],
            'leaves4' => $cols['leaves'],
            'scoreMap4' => $scoreMap4,
            'results4' => $results4,
            'resultHeaders4' => $resultHeaders,
            'resultGroups4' => $calc->resultHeaderGroups(),
            'evalLevels4' => $calc->evalLevels(),
        ];
    }

    private function resultEmployeeQuery()
    {
        return Employee::active()
            ->select([
                'id',
                'employee_code',
                'title',
                'name_th',
                'surname_th',
                'name_en',
                'job_code',
                'job_th',
                'job_en',
                'dept_th',
                'dept_en',
                'emp_status',
            ])
            ->orderBy('employee_code');
    }

    /**
     * @return array<int,array<int,string>>
     */
    private function resultFilterState(Request $request): array
    {
        $raw = $request->query('f', []);
        if (! is_array($raw)) {
            return [];
        }

        $filters = [];
        foreach ($raw as $col => $values) {
            $col = (int) $col;
            if ($col < 1 || $col > 300) {
                continue;
            }

            $values = is_array($values) ? $values : [$values];
            $clean = [];
            foreach ($values as $value) {
                $value = trim((string) $value);
                if ($value !== '') {
                    $clean['v:'.$value] = $value;
                }
            }
            if ($clean !== []) {
                $filters[$col] = array_values($clean);
            }
        }

        return $filters;
    }

    private function normalizeResultFilterValue(?string $value): string
    {
        return trim(preg_replace('/\s+/u', ' ', (string) $value));
    }

    private function encodeResultFilterValue(?string $value): string
    {
        $value = $this->normalizeResultFilterValue($value);

        return $value === '' ? self::RESULT_FILTER_EMPTY : $value;
    }

    private function employeeLevel($emp, array $levelMap): ?int
    {
        $lv = $levelMap[$emp->job_code] ?? null;

        return $lv === null ? null : (int) $lv;
    }

    private function isLevelZero($emp, array $levelMap): bool
    {
        return $this->employeeLevel($emp, $levelMap) === 0;
    }

    private function hasCompleteHierarchy($hier): bool
    {
        if (! $hier) {
            return false;
        }

        return $this->hasCompleteHierarchyValues([
            'l1_id' => $hier->l1_id ?? null,
            'l1_name' => $hier->l1_name ?? null,
            'l2_id' => $hier->l2_id ?? null,
            'l2_name' => $hier->l2_name ?? null,
            'l3_id' => $hier->l3_id ?? null,
            'l3_name' => $hier->l3_name ?? null,
            'l4_id' => $hier->l4_id ?? null,
            'l4_name' => $hier->l4_name ?? null,
        ]);
    }

    private function hasCompleteHierarchyValues(array $values): bool
    {
        foreach (['l1_id', 'l1_name', 'l2_id', 'l2_name', 'l3_id', 'l3_name', 'l4_id', 'l4_name'] as $field) {
            $value = trim((string) ($values[$field] ?? ''));
            if ($value === '' || $value === '-') {
                return false;
            }
        }

        return true;
    }

    private function levelPropMode($matrix, ?int $level, int $boxId): ?string
    {
        if ($level === null || $level < 1) {
            return null;
        }

        $levelRows = null;
        if (is_array($matrix)) {
            $levelRows = $matrix[$level] ?? $matrix[(string) $level] ?? null;
        } elseif (is_object($matrix)) {
            $levelRows = $matrix->{$level} ?? $matrix->{(string) $level} ?? null;
        }

        if (is_object($levelRows)) {
            $levelRows = (array) $levelRows;
        }
        if (! is_array($levelRows)) {
            return null;
        }

        $cfg = $levelRows[$boxId] ?? $levelRows[(string) $boxId] ?? null;
        if (is_object($cfg)) {
            $cfg = (array) $cfg;
        }

        return is_array($cfg) ? (string) ($cfg['mode'] ?? '') : null;
    }

    private function scoreBoxTopId($box): int
    {
        return (int) ($box->parent_id ?: $box->id);
    }

    private function isScoreBoxNotCalculatedForLevel($box, ?int $level, $matrix = null): bool
    {
        if (! $box || $level === null || $level < 1) {
            return false;
        }

        $matrix ??= AsmLevelProp::matrix();

        return $this->levelPropMode($matrix, $level, $this->scoreBoxTopId($box)) === 'none';
    }

    private function isScoreBoxNotCalculatedForEmployee($emp, $box, array $levelMap, $matrix = null): bool
    {
        return $this->isScoreBoxNotCalculatedForLevel($box, $this->employeeLevel($emp, $levelMap), $matrix);
    }

    private function resultColumnValue($emp, int $col, array $ctx): string
    {
        $code = (string) $emp->employee_code;
        $hierFields = ['l1_id', 'l1_name', 'l2_id', 'l2_name', 'l3_id', 'l3_name', 'l4_id', 'l4_name'];
        $levelMap = $ctx['levelMap'] ?? [];
        $lv = $this->employeeLevel($emp, $levelMap);
        $isLevelZero = $lv === 0;
        $hier = $ctx['hierMap'][$code] ?? null;
        $hasCompleteHierarchy = $this->hasCompleteHierarchy($hier);

        if ($col === 1) {
            return $code;
        }
        if ($col === 2) {
            return $emp->fullNameTh() ?: (string) $emp->name_en;
        }
        if ($col === 3) {
            return (string) ($emp->job_th ?: $emp->job_en ?: '');
        }
        if ($col === 4) {
            return (string) ($emp->deptThClean() ?: $emp->dept_en ?: '');
        }
        if ($col >= 5 && $col <= 12) {
            if ($isLevelZero) {
                return '-';
            }
            $field = $hierFields[$col - 5] ?? null;
            if (! $field || ! $hier) {
                return '-';
            }
            $value = trim((string) ($hier->$field ?? ''));

            return $value === '' ? '-' : $value;
        }
        if ($col === 13) {
            return $lv === null ? '' : (string) $lv;
        }

        $scoreStart = 14;
        $leaves = $ctx['leaves4'];
        $leafCount = $leaves->count();
        if ($col >= $scoreStart && $col < $scoreStart + $leafCount) {
            $leaf = $leaves->values()[$col - $scoreStart] ?? null;
            if (! $leaf) {
                return '';
            }
            if ($isLevelZero
                || ! $hasCompleteHierarchy
                || $this->isScoreBoxNotCalculatedForLevel($leaf, $lv, $ctx['levelPropMatrix'] ?? null)) {
                return '-';
            }
            $pv = $ctx['scoreMap4'][$code][$leaf->id] ?? null;

            return $pv ? (string) ($this->partsDisplay($pv['v'], $pv['v2'], $pv['v3'], $pv['v4'], (int) ($pv['na'] ?? 0)) ?? '') : '';
        }

        $resultIndex = $col - ($scoreStart + $leafCount);
        if ($resultIndex >= 0) {
            if ($isLevelZero || ! $hasCompleteHierarchy) {
                return '-';
            }

            return (string) ($ctx['results4'][$code][$resultIndex] ?? '');
        }

        return '';
    }

    private function resultMatchesFilters($emp, array $ctx, array $filters, ?int $excludeCol = null): bool
    {
        foreach ($filters as $col => $values) {
            if ($excludeCol !== null && (int) $col === $excludeCol) {
                continue;
            }
            if (in_array(self::RESULT_FILTER_NONE, $values, true)) {
                return false;
            }

            $actual = $this->encodeResultFilterValue($this->resultColumnValue($emp, (int) $col, $ctx));
            if (! in_array($actual, $values, true)) {
                return false;
            }
        }

        return true;
    }

    private function resultDataset(AsmRound $round, array $filters = [], ?int $excludeCol = null): array
    {
        $levelMap = AsmPositionLevel::map($round->id);
        $employees = $this->resultEmployeeQuery()->get();
        $visibleCodes = $employees
            ->pluck('employee_code')
            ->filter(fn ($code) => $code !== null && $code !== '')
            ->map(fn ($code) => (string) $code)
            ->unique()
            ->values();

        $hierMap = $visibleCodes->isEmpty()
            ? collect()
            : AsmHierarchy::where('round_id', $round->id)->whereIn('employee_code', $visibleCodes->all())->get()->keyBy('employee_code');

        $levelPropMatrix = AsmLevelProp::matrix();
        $section = $this->resultSectionData($employees, $levelMap, $round->id, $hierMap);
        $ctx = $section + [
            'levelMap' => $levelMap,
            'hierMap' => $hierMap,
            'levelPropMatrix' => $levelPropMatrix,
        ];

        if ($filters !== []) {
            $employees = $employees
                ->filter(fn ($emp) => $this->resultMatchesFilters($emp, $ctx, $filters, $excludeCol))
                ->values();
        }

        return [
            'employees' => $employees,
            'levelMap' => $levelMap,
            'hierMap' => $hierMap,
            'ctx' => $ctx,
        ];
    }

    /**
     * ชุดข้อมูลหน้า "จัดการข้อมูลพนักงาน" (ตาราง 1.2 + 4.1)
     * คีย์ตัวกรอง = ดัชนีคอลัมน์ผลลัพธ์ (resultColumnValue) ใช้ร่วมทั้ง 2 ตาราง
     *
     * @param  array<int,array<int,string>>  $filters
     * @return array{employees:Collection,levelMap:array,hierMap:mixed,ctx:array}
     */
    private function scoresDataset(AsmRound $round, array $filters = [], ?int $excludeCol = null): array
    {
        $levelMap = AsmPositionLevel::map($round->id);
        $employees = $this->resultEmployeeQuery()
            ->get();

        $visibleCodes = $employees
            ->pluck('employee_code')
            ->filter(fn ($code) => $code !== null && $code !== '')
            ->map(fn ($code) => (string) $code)
            ->unique()
            ->values();

        $hierMap = $visibleCodes->isEmpty()
            ? collect()
            : AsmHierarchy::where('round_id', $round->id)->whereIn('employee_code', $visibleCodes->all())->get()->keyBy('employee_code');

        $levelPropMatrix = AsmLevelProp::matrix();
        $section = $this->resultSectionData($employees, $levelMap, $round->id, $hierMap);
        $ctx = $section + [
            'levelMap' => $levelMap,
            'hierMap' => $hierMap,
            'levelPropMatrix' => $levelPropMatrix,
        ];

        if ($filters !== []) {
            $employees = $employees
                ->filter(fn ($emp) => $this->resultMatchesFilters($emp, $ctx, $filters, $excludeCol))
                ->values();
        }

        return [
            'employees' => $employees,
            'levelMap' => $levelMap,
            'hierMap' => $hierMap,
            'ctx' => $ctx,
        ];
    }

    /** ตัวเลือกตัวกรองรายคอลัมน์ของหน้าจัดการฯ (funnel) — คิดจากทุกคนหลังกรองคอลัมน์อื่น */
    public function scoresFilterOptions(Request $request): JsonResponse
    {
        if ($this->gateAdminOrHr()) {
            return response()->json(['ok' => false, 'message' => 'no permission'], 403);
        }

        $round = $this->openRound();
        if (! $round) {
            return response()->json(['ok' => false, 'message' => 'ต้องเปิดรอบก่อน'], 422);
        }

        $col = (int) $request->query('col');
        if ($col < 1 || $col > 300) {
            return response()->json(['ok' => false, 'message' => 'คอลัมน์ไม่ถูกต้อง'], 422);
        }

        $filters = $this->resultFilterState($request);
        $dataset = $this->scoresDataset($round, $filters, $col);
        $ctx = $dataset['ctx'];
        $selected = $filters[$col] ?? null;
        $values = [];

        foreach ($dataset['employees'] as $emp) {
            $raw = $this->normalizeResultFilterValue($this->resultColumnValue($emp, $col, $ctx));
            $key = $this->encodeResultFilterValue($raw);
            if (! isset($values[$key])) {
                $values[$key] = [
                    'value' => $key,
                    'label' => $raw === '' ? '(ว่าง)' : $raw,
                    'raw' => $raw,
                    'count' => 0,
                    'selected' => $selected === null || in_array($key, $selected, true),
                ];
            }
            $values[$key]['count']++;
        }

        uasort($values, function (array $a, array $b): int {
            if ($a['value'] === self::RESULT_FILTER_EMPTY) {
                return -1;
            }
            if ($b['value'] === self::RESULT_FILTER_EMPTY) {
                return 1;
            }

            return strnatcasecmp($a['raw'], $b['raw']);
        });

        return response()->json([
            'ok' => true,
            'emptyValue' => self::RESULT_FILTER_EMPTY,
            'noneValue' => self::RESULT_FILTER_NONE,
            'options' => array_values($values),
        ]);
    }

    /** หัวข้อ 5 — คำนวณผลลัพธ์ใหม่ทุกคน + หัวคอลัมน์ (refresh หลังแก้ลำดับผู้ประเมินใน 3.2 ไม่ต้อง reload) */
    public function resultAll(Request $request): JsonResponse
    {
        if ($this->gateAdminOrHr()) {
            return response()->json(['ok' => false, 'message' => 'no permission'], 403);
        }

        $round = $this->viewRound($request);
        if (! $round) {
            return response()->json(['ok' => false, 'message' => 'ไม่พบรอบ'], 422);
        }

        $calc = new ResultCalculator;
        $levelMap = AsmPositionLevel::map($round->id);
        $hierMap = AsmHierarchy::where('round_id', $round->id)->get()->keyBy('employee_code');

        $scores = [];
        foreach (AsmEmployeeScore::where('round_id', $round->id)->get() as $s) {
            $scores[$s->employee_code][$s->box_id] = [
                'v' => $s->value === null ? null : (float) $s->value,
                'v2' => $s->value2 === null ? null : (float) $s->value2,
                'v3' => $s->value3 === null ? null : (float) $s->value3,
                'v4' => $s->value4 === null ? null : (float) $s->value4,
            ];
        }

        $rows = [];
        $headers = $calc->resultHeaders();
        foreach (Employee::active()->orderBy('employee_code')->get() as $e) {
            if (isset($rows[$e->employee_code])) {
                continue;
            }
            $lv = $this->employeeLevel($e, $levelMap);
            $h = $hierMap[$e->employee_code] ?? null;
            $rows[$e->employee_code] = ($lv === 0 || ! $this->hasCompleteHierarchy($h))
                ? array_fill(0, count($headers), '-')
                : $calc->rowCells($calc->compute($lv, $scores[$e->employee_code] ?? []));
        }

        return response()->json([
            'ok' => true,
            'groups' => $calc->resultHeaderGroups(),
            'headers' => $headers,
            'rows' => $rows,
        ]);
    }

    /** หัวข้อ 5 — คำนวณผลลัพธ์ใหม่ 1 คน (refresh หลังแก้คะแนน inline) */
    public function resultRow(Request $request): JsonResponse
    {
        if ($this->gateAdminOrHr()) {
            return response()->json(['ok' => false, 'message' => 'no permission'], 403);
        }

        $code = trim((string) $request->query('code'));
        $emp = Employee::active()->where('employee_code', $code)->first();
        if (! $emp) {
            return response()->json(['ok' => false, 'message' => 'ไม่พบพนักงาน'], 422);
        }

        $round = $this->viewRound($request);
        if (! $round) {
            return response()->json(['ok' => false, 'message' => 'ไม่พบรอบ'], 422);
        }

        $levelMap = AsmPositionLevel::map($round->id);
        $lv = $this->employeeLevel($emp, $levelMap);
        $vals = AsmEmployeeScore::where('round_id', $round->id)->where('employee_code', $code)->get()->mapWithKeys(fn ($s) => [$s->box_id => [
            'v' => $s->value === null ? null : (float) $s->value,
            'v2' => $s->value2 === null ? null : (float) $s->value2,
            'v3' => $s->value3 === null ? null : (float) $s->value3,
            'v4' => $s->value4 === null ? null : (float) $s->value4,
        ]])->all();

        $calc = new ResultCalculator;
        $hier = AsmHierarchy::where('round_id', $round->id)->where('employee_code', $code)->first();
        if ($lv === 0 || ! $this->hasCompleteHierarchy($hier)) {
            return response()->json(['ok' => true, 'cells' => array_fill(0, count($calc->resultHeaders()), '-')]);
        }

        $cells = $this->mergeDupEvalCells($calc->rowCells($calc->compute($lv, $vals)), $calc->evalLevels(), $hier);

        return response()->json(['ok' => true, 'cells' => $cells]);
    }

    /** แท็บผลลัพธ์ — คืน HTML แถวเดียวสด (หลังแก้ผู้ประเมิน: ปลดล็อก/ล็อกช่องคะแนน + merge + ผลลัพธ์ใหม่ โดยไม่ reload) */
    public function resultRowHtml(Request $request): JsonResponse
    {
        if ($this->gateAdminOrHr()) {
            return response()->json(['ok' => false, 'message' => 'no permission'], 403);
        }

        $round = $this->viewRound($request) ?? AsmRound::orderByDesc('id')->first();
        if (! $round) {
            return response()->json(['ok' => false, 'message' => 'ไม่พบรอบ'], 422);
        }

        $code = trim((string) $request->query('code'));
        $emp = Employee::active()->where('employee_code', $code)->first();
        if (! $emp) {
            return response()->json(['ok' => false, 'message' => 'ไม่พบพนักงาน'], 422);
        }

        $levelMap = AsmPositionLevel::map($round->id);
        $hier = AsmHierarchy::where('round_id', $round->id)->where('employee_code', $code)->first();
        $hierMap = collect($hier ? [$code => $hier] : []);
        $section = $this->resultSectionData(collect([$emp]), $levelMap, $round->id, $hierMap);

        $html = view('assessment.partials.resultrow', [
            'emp' => $emp,
            'no' => null,   // JS คงเลขแถวเดิมตอน swap
            'levelMap' => $levelMap,
            'hierMap' => $hierMap,
            'levelPropMatrix' => AsmLevelProp::matrix() ?: new \stdClass,
            'editable' => $round->isOpen(),
        ] + $section)->render();

        return response()->json(['ok' => true, 'html' => $html]);
    }

    /**
     * ยุบผลลัพธ์ลำดับที่ผู้ประเมิน (ชื่อลำดับชั้น) ซ้ำกับลำดับก่อนหน้า → "—"
     * cells เรียงต่อลำดับ (คะแนนรวม/เกรด/คำบรรยาย × N ลำดับ)
     *
     * @param  string[]  $cells
     * @param  int[]  $evalLevels
     * @return string[]
     */
    private function mergeDupEvalCells(array $cells, array $evalLevels, $hier): array
    {
        $seen = [];
        foreach ($evalLevels as $idx => $lv) {
            $name = $hier ? trim((string) ($hier->{'l'.$lv.'_name'} ?? '')) : '';
            if ($name !== '' && in_array($name, $seen, true)) {
                for ($c = 0; $c < 3; $c++) {
                    if (isset($cells[$idx * 3 + $c])) {
                        $cells[$idx * 3 + $c] = '—';
                    }
                }
            }
            if ($name !== '') {
                $seen[] = $name;
            }
        }

        return $cells;
    }

    /** แท็บผลลัพธ์ — ตารางรวมทุกคอลัมน์ของรอบที่เลือก (แท็บย่อยรายปี + switch รอบ) */
    public function resultsPage(Request $request): ViewContract|RedirectResponse
    {
        if ($redirect = $this->gateAdminOrHr()) {
            return $redirect;
        }

        $round = $this->viewRound($request) ?? AsmRound::orderByDesc('id')->first();
        if (! $round) {
            return redirect()->route('assessment.rounds.index')->with('round_warn', 'ต้องเปิดรอบก่อนจึงจะดูผลลัพธ์ได้');
        }

        $perPage = (int) $request->query('per_page', 100);
        $perPage = in_array($perPage, [100, 200, 500, 1000], true) ? $perPage : 100;
        $filters = $this->resultFilterState($request);
        $levelMap = AsmPositionLevel::map($round->id);

        $summaryBase = null;   // มีตัวกรอง → reuse ชุดที่กรองแล้ว ไม่ query ซ้ำให้สรุปข้างบน

        if ($filters !== []) {
            $dataset = $this->resultDataset($round, $filters);
            $filteredEmployees = $dataset['employees'];
            $summaryBase = $filteredEmployees;
            $page = max(1, (int) $request->query('page', 1));
            $employees = new LengthAwarePaginator(
                $filteredEmployees->slice(($page - 1) * $perPage, $perPage)->values(),
                $filteredEmployees->count(),
                $perPage,
                $page,
                ['path' => $request->url(), 'query' => $request->query()],
            );
        } else {
            $employees = $this->resultEmployeeQuery()
                ->paginate($perPage)
                ->withQueryString();
        }

        $visibleCodes = $employees->getCollection()
            ->pluck('employee_code')
            ->filter(fn ($code) => $code !== null && $code !== '')
            ->map(fn ($code) => (string) $code)
            ->unique()
            ->values();

        $hierMap = $visibleCodes->isEmpty()
            ? collect()
            : AsmHierarchy::where('round_id', $round->id)->whereIn('employee_code', $visibleCodes->all())->get()->keyBy('employee_code');

        return view('assessment.results', [
            'me' => app('current_user'),
            'openRound' => $this->openRound(),
            'round' => $round,
            // เฉพาะรอบ "ประเมินพนักงาน" — ปุ่มปี/ตัวเลือกรอบของหน้านี้ต้องไม่ปนรอบประเมินตัวเอง
            'rounds' => AsmRound::where('type', AsmRound::TYPE_EMPLOYEE)
                ->orderByDesc('year')->orderByDesc('id')->get(),
            'employees' => $employees,
            'perPage' => $perPage,
            'filters' => $filters,
            'activeFilterCount' => count($filters),
            'levelMap' => $levelMap,
            'hierMap' => $hierMap,
            'levelPropMatrix' => AsmLevelProp::matrix() ?: new \stdClass,
            'editable' => $round->isOpen(),
            'levelSummary' => $this->levelSummary($round, $filters, $summaryBase),
        ] + $this->resultSectionData($employees, $levelMap, $round->id, $hierMap));
    }

    /**
     * สรุปค่าเฉลี่ยคะแนนต่อ "ระดับตำแหน่ง" (หัวหน้าผลลัพธ์) — เดินตามตัวกรองเดียวกับตารางข้างล่าง
     *
     * - จำนวนคน = พนักงานที่ยังทำงานจริง (Employee::active) ที่ผ่านตัวกรอง → อัปเดตเองทุกครั้งที่โหลดหน้า
     * - "มีการประเมิน" = ผู้ประเมินครบทั้ง 4 ลำดับ (เกณฑ์เดียวกับที่ engine ใช้ตัดสินว่าคำนวณให้หรือไม่)
     * - ค่าเฉลี่ยแยกตามลำดับผู้ประเมิน เพราะตัวหารของแต่ละลำดับไม่เท่ากัน (คอลัมน์ Input ที่เป็น N/A ถูกตัดออก)
     * - ระดับ 0 / ยังไม่จัดระดับ = ไม่ถูกประเมิน · ระดับที่สัดส่วนเป็น none ทั้งหมด = ไม่มีการคำนวณคะแนน
     *
     * @param  array<int,array<int,string>>  $filters
     * @return array<string,mixed>
     */
    private function levelSummary(AsmRound $round, array $filters, ?Collection $preloaded = null): array
    {
        $employees = $preloaded ?? ($filters !== []
            ? $this->resultDataset($round, $filters)['employees']
            : $this->resultEmployeeQuery()->get());

        $calc = new ResultCalculator;
        $evalLevels = $calc->evalLevels();
        $levelMap = AsmPositionLevel::map($round->id);
        $propMatrix = AsmLevelProp::matrix();

        $codes = $employees->pluck('employee_code')
            ->filter(fn ($c) => $c !== null && $c !== '')
            ->map(fn ($c) => (string) $c)
            ->unique()
            ->values();

        $hierMap = $codes->isEmpty()
            ? collect()
            : AsmHierarchy::where('round_id', $round->id)->whereIn('employee_code', $codes->all())->get()->keyBy('employee_code');

        $vals = [];
        if ($codes->isNotEmpty()) {
            foreach (AsmEmployeeScore::where('round_id', $round->id)->whereIn('employee_code', $codes->all())->get() as $s) {
                $vals[$s->employee_code][$s->box_id] = [
                    'v' => $s->value === null ? null : (float) $s->value,
                    'v2' => $s->value2 === null ? null : (float) $s->value2,
                    'v3' => $s->value3 === null ? null : (float) $s->value3,
                    'v4' => $s->value4 === null ? null : (float) $s->value4,
                    'na' => (int) $s->na_mask,
                ];
            }
        }

        $rows = [];
        $assessed = 0;

        foreach ($employees as $e) {
            $code = (string) $e->employee_code;
            $lv = $levelMap[(string) $e->job_code] ?? null;
            $key = ($lv === null || (int) $lv < 1) ? 0 : (int) $lv;   // 0 = ระดับ 0 + ยังไม่จัดระดับ

            $rows[$key] ??= [
                'count' => 0,
                'sum' => array_fill_keys($evalLevels, 0.0),
                'n' => array_fill_keys($evalLevels, 0),
            ];
            $rows[$key]['count']++;

            if (! $this->hasCompleteHierarchy($hierMap[$code] ?? null)) {
                continue;   // ผู้ประเมินไม่ครบ = ระบบไม่คำนวณให้ จึงไม่นับว่าประเมินแล้ว
            }
            $assessed++;

            if ($key < 1) {
                continue;   // ระดับ 0 ไม่ถูกประเมิน
            }

            $r = $calc->compute($key, $vals[$code] ?? []);
            foreach ($evalLevels as $L) {
                $t = $r['totals'][$L] ?? null;
                if ($t !== null) {
                    $rows[$key]['sum'][$L] += (float) $t;
                    $rows[$key]['n'][$L]++;
                }
            }
        }

        krsort($rows);   // ระดับสูง → ต่ำ

        $levels = [];
        foreach ($rows as $lv => $r) {
            $avg = [];
            foreach ($evalLevels as $L) {
                $avg[$L] = $r['n'][$L] > 0 ? round($r['sum'][$L] / $r['n'][$L], 2) : null;
            }
            $levels[] = [
                'level' => $lv,
                'count' => $r['count'],
                'scored' => $r['n'] === [] ? 0 : max($r['n']),
                'avg' => $avg,
                'calculable' => $lv >= 1 && $this->levelHasFormula($propMatrix, $lv),
            ];
        }

        return [
            'evalLevels' => $evalLevels,
            'total' => $employees->count(),
            'assessed' => $assessed,
            'levels' => $levels,
        ];
    }

    /** ระดับนี้ตั้งสัดส่วนไว้ให้คำนวณหรือยัง — ทุกหัวข้อเป็น none/ไม่ตั้ง = ไม่มีการคำนวณคะแนน */
    private function levelHasFormula(array $propMatrix, int $level): bool
    {
        foreach ($propMatrix[$level] ?? [] as $p) {
            $mode = $p['mode'] ?? 'none';
            if ($mode === 'extra' || ($mode === 'percent' && (float) ($p['weight'] ?? 0) > 0)) {
                return true;
            }
        }

        return false;
    }

    public function resultFilterOptions(Request $request): JsonResponse
    {
        if ($this->gateAdminOrHr()) {
            return response()->json(['ok' => false, 'message' => 'no permission'], 403);
        }

        $round = $this->viewRound($request) ?? AsmRound::orderByDesc('id')->first();
        if (! $round) {
            return response()->json(['ok' => false, 'message' => 'ไม่พบรอบ'], 422);
        }

        $col = (int) $request->query('col');
        if ($col < 1 || $col > 300) {
            return response()->json(['ok' => false, 'message' => 'คอลัมน์ไม่ถูกต้อง'], 422);
        }

        $filters = $this->resultFilterState($request);
        $dataset = $this->resultDataset($round, $filters, $col);
        $ctx = $dataset['ctx'];
        $selected = $filters[$col] ?? null;
        $values = [];

        foreach ($dataset['employees'] as $emp) {
            $raw = $this->normalizeResultFilterValue($this->resultColumnValue($emp, $col, $ctx));
            $key = $this->encodeResultFilterValue($raw);
            if (! isset($values[$key])) {
                $values[$key] = [
                    'value' => $key,
                    'label' => $raw === '' ? '(ว่าง)' : $raw,
                    'raw' => $raw,
                    'count' => 0,
                    'selected' => $selected === null || in_array($key, $selected, true),
                ];
            }
            $values[$key]['count']++;
        }

        uasort($values, function (array $a, array $b): int {
            if ($a['value'] === self::RESULT_FILTER_EMPTY) {
                return -1;
            }
            if ($b['value'] === self::RESULT_FILTER_EMPTY) {
                return 1;
            }

            return strnatcasecmp($a['raw'], $b['raw']);
        });

        return response()->json([
            'ok' => true,
            'emptyValue' => self::RESULT_FILTER_EMPTY,
            'noneValue' => self::RESULT_FILTER_NONE,
            'options' => array_values($values),
        ]);
    }

    /** แท็บผลลัพธ์ กลุ่ม 2 — Export ผลลัพธ์เต็ม (รวมคอลัมน์ผลลัพธ์ ลำดับ 1–4) */
    public function resultsDownload(Request $request): StreamedResponse|RedirectResponse
    {
        if ($redirect = $this->gateAdminOrHr()) {
            return $redirect;
        }

        $round = $this->viewRound($request);
        if (! $round) {
            return redirect()->route('assessment.rounds.index')->with('error', 'ไม่พบรอบ');
        }

        return $this->streamResultsExport($round, true);
    }

    /** แท็บผลลัพธ์ กลุ่ม 1 — Template นำเข้าโดยรวม (โครงเดียวกับผลลัพธ์ แต่ตัดคอลัมน์ผลลัพธ์ออก ให้กรอกแล้ว import กลับ) */
    public function resultsTemplateDownload(): StreamedResponse|RedirectResponse
    {
        if ($redirect = $this->gateAdminOrHr()) {
            return $redirect;
        }

        $round = $this->openRound();
        if (! $round) {
            return redirect()->route('assessment.rounds.index')->with('round_warn', 'ต้องเปิดรอบก่อนจึงจะดาวน์โหลด Template ได้');
        }

        return $this->streamResultsExport($round, false);
    }

    /** สร้างไฟล์โครงตารางผลลัพธ์ — $includeResults = รวมคอลัมน์ผลลัพธ์ (ลำดับ 1–4) หรือไม่ (Template นำเข้า = ไม่รวม) */
    private function streamResultsExport(AsmRound $round, bool $includeResults): StreamedResponse
    {
        $levelMap = AsmPositionLevel::map($round->id);
        $employees = Employee::active()->orderBy('employee_code')->get();
        $hierMap = AsmHierarchy::where('round_id', $round->id)->get()->keyBy('employee_code');
        $d = $this->resultSectionData($employees, $levelMap, $round->id, $hierMap);
        $calc = new ResultCalculator;
        $levelPropMatrix = AsmLevelProp::matrix();

        $ss = new Spreadsheet;
        $sheet = $ss->getActiveSheet();
        $sheet->setTitle('Results');

        // ----- หัว 2 แถว -----
        $col = 1;
        $put2 = function (int $c, string $h) use ($sheet) {
            $sheet->setCellValueExplicit([$c, 2], $h, DataType::TYPE_STRING);
        };
        foreach (self::FIXED_BASE as $h) {
            $put2($col++, $h);
        }
        // ระดับ อยู่ขวาของ แผนก (ก่อนลำดับชั้น) — ให้ตรงกับตารางบนแท็บผลลัพธ์
        $levelCol = $col;
        $put2($col++, self::LEVEL_HEADER);
        $hierStart = $col;
        foreach (self::HIER as $pair) {
            $put2($col++, $pair[1]);
        }
        for ($k = 0; $k < 4; $k++) {
            $s = $hierStart + $k * 2;
            $sheet->mergeCells([$s, 1, $s + 1, 1]);
            $sheet->setCellValueExplicit([$s, 1], 'ลำดับชั้น '.($k + 1), DataType::TYPE_STRING);
        }
        $firstScoreCol = $col;
        foreach ($d['zones4'] as $z) {
            $start = $col;
            foreach ($z['leaves'] as $leaf) {
                $put2($col++, $leaf->name);
            }
            $sheet->mergeCells([$start, 1, $col - 1, 1]);
            $sheet->setCellValueExplicit([$start, 1], $z['name'], DataType::TYPE_STRING);
        }
        if ($includeResults) {
            $firstResultCol = $col;
            foreach ($calc->resultHeaderGroups() as $g) {
                $sheet->mergeCells([$col, 1, $col + $g['span'] - 1, 1]);
                $sheet->setCellValueExplicit([$col, 1], $g['label'], DataType::TYPE_STRING);
                $col += $g['span'];
            }
            $c2 = $firstResultCol;
            foreach ($calc->resultHeaders() as $rh) {
                $put2($c2++, $rh);
            }
        }
        $lastCol = $col - 1;

        // ----- data -----
        $rowNo = 3;
        $seen = [];
        foreach ($employees as $emp) {
            $code = (string) $emp->employee_code;
            if (isset($seen[$code])) {
                continue;
            }
            $seen[$code] = true;
            $isLevelZero = $this->isLevelZero($emp, $levelMap);
            $h = $hierMap[$code] ?? null;
            $hasCompleteHierarchy = $this->hasCompleteHierarchy($h);
            $sheet->setCellValueExplicit([1, $rowNo], $code, DataType::TYPE_STRING);
            $sheet->setCellValue([2, $rowNo], $emp->fullNameTh());
            $sheet->setCellValue([3, $rowNo], (string) ($emp->name_en ?? ''));
            $sheet->setCellValue([4, $rowNo], (string) ($emp->job_th ?: $emp->job_en ?: ''));
            $sheet->setCellValue([5, $rowNo], (string) ($emp->deptThClean() ?: $emp->dept_en ?: ''));
            if ($isLevelZero) {
                for ($k = 0; $k < 8; $k++) {
                    $sheet->setCellValueExplicit([$hierStart + $k, $rowNo], '-', DataType::TYPE_STRING);
                }
            } elseif ($h) {
                $vals = [$h->l1_id, $h->l1_name, $h->l2_id, $h->l2_name, $h->l3_id, $h->l3_name, $h->l4_id, $h->l4_name];
                foreach ($vals as $k => $v) {
                    if ($v !== null && $v !== '') {
                        $sheet->setCellValueExplicit([$hierStart + $k, $rowNo], (string) $v, DataType::TYPE_STRING);
                    }
                }
            }
            $lvl = $this->employeeLevel($emp, $levelMap);
            if ($lvl !== null) {
                $sheet->setCellValue([$levelCol, $rowNo], $lvl);
            }
            $c = $firstScoreCol;
            foreach ($d['leaves4'] as $leaf) {
                if ($isLevelZero || ! $hasCompleteHierarchy) {
                    $sheet->setCellValueExplicit([$c, $rowNo], '-', DataType::TYPE_STRING);
                } elseif ($this->isScoreBoxNotCalculatedForLevel($leaf, $lvl, $levelPropMatrix)) {
                    // ตรงกับหน้าจอ: ช่องไม่ถูกคำนวณของระดับนั้น = N/A (import กลับถูก guard ข้ามเหมือนเดิม)
                    $sheet->setCellValueExplicit([$c, $rowNo], 'N/A', DataType::TYPE_STRING);
                } else {
                    $pv = $d['scoreMap4'][$code][$leaf->id] ?? null;
                    // Input: ยุบค่าตามกลุ่มผู้ประเมิน (คนเดียวกันหลายลำดับ = ค่าเดียว) ; ชนิดอื่นแสดงตาม slot
                    $txt = $leaf->type === 'input'
                        ? $this->groupedPartsDisplay($pv, $this->inputGroupsFor($h, $leaf))
                        : ($pv ? $this->partsDisplay($pv['v'], $pv['v2'], $pv['v3'], $pv['v4'], (int) ($pv['na'] ?? 0)) : null);
                    if ($txt !== null) {
                        $sheet->setCellValueExplicit([$c, $rowNo], $txt, DataType::TYPE_STRING);
                    }
                }
                $c++;
            }
            if ($includeResults) {
                foreach ($d['results4'][$code] ?? [] as $cell) {
                    $sheet->setCellValueExplicit([$c++, $rowNo], (string) $cell, DataType::TYPE_STRING);
                }
            }
            $rowNo++;
        }

        $this->styleHeaderRows($sheet, $lastCol);

        $prefix = $includeResults ? 'assessment_results_' : 'assessment_import_template_';

        return $this->streamTemplate($ss, $prefix.$round->year.'_รอบ'.$round->id.'_');
    }

    /** ตำแหน่งทั้งหมด (job_code + ชื่อ + จำนวน) จาก employees */
    private function positionOptions(): array
    {
        return Employee::active()
            ->selectRaw('job_code as code, MAX(job_th) as th, MAX(job_en) as en, COUNT(*) as cnt')
            ->whereNotNull('job_code')->where('job_code', '!=', '')
            ->groupBy('job_code')->orderBy('th')->get()
            ->map(fn ($p) => ['code' => $p->code, 'name' => $p->th ?: $p->en ?: $p->code, 'count' => (int) $p->cnt])
            ->values()->all();
    }

    /** คอลัมน์คะแนน: leaves (คอลัมน์จริง) + zones (กล่องใหญ่ครอบย่อย) — global */
    private function buildColumns(): array
    {
        $all = AsmScoreBox::orderBy('sort')->orderBy('id')->get();
        $children = $all->whereNotNull('parent_id')->groupBy('parent_id');
        $tops = $all->whereNull('parent_id');

        $leaves = collect();
        $zones = [];
        foreach ($tops as $tb) {
            $kids = ($children[$tb->id] ?? collect())->values();
            if ($kids->count()) {
                $zones[] = ['name' => $tb->name, 'box_id' => $tb->id, 'leaves' => $kids, 'span' => $kids->count(), 'standalone' => false];
                $leaves = $leaves->concat($kids);
            } else {
                $zones[] = ['name' => $tb->name, 'box_id' => $tb->id, 'leaves' => collect([$tb]), 'span' => 1, 'standalone' => true];
                $leaves->push($tb);
            }
        }

        return ['leaves' => $leaves->values(), 'zones' => $zones];
    }

    // ===== ระดับตำแหน่ง (1–5) =====

    public function setLevel(Request $request): JsonResponse
    {
        if ($this->gateAdminOrHr()) {
            return response()->json(['ok' => false, 'message' => 'no permission'], 403);
        }

        $data = $request->validate([
            'job_code' => ['required', 'string'],
            'level' => ['nullable', 'integer', 'between:0,99'],   // 0 = ไม่ถูกประเมิน ; null = ยังไม่จัดระดับ ; เกิน 5 = ระดับที่ admin เพิ่มเอง
        ]);

        $round = $this->openRound();
        if (! $round) {
            return response()->json(['ok' => false, 'message' => 'ต้องเปิดรอบก่อน'], 422);
        }

        $level = $data['level'] ?? null;
        if ($level === null) {
            AsmPositionLevel::where('round_id', $round->id)->where('job_code', $data['job_code'])->delete();
        } else {
            AsmPositionLevel::updateOrCreate(
                ['round_id' => $round->id, 'job_code' => $data['job_code']],
                ['level' => (int) $level],
            );
        }

        return response()->json(['ok' => true]);
    }

    /** ระดับสูงสุดที่มีข้อมูลจริง (สัดส่วน 3.1 หรือตำแหน่งที่จัดไว้ในรอบ) — 0 = มีแค่ระดับ 0 default (ยังไม่เพิ่มระดับเอง) */
    private function currentMaxLevel(int $roundId): int
    {
        $propMax = AsmLevelProp::max('level');
        $posMax = AsmPositionLevel::where('round_id', $roundId)->max('level');

        return (int) max(0, $propMax ?? 0, $posMax ?? 0);
    }

    /**
     * ลบระดับ (ปุ่ม ✕ บนการ์ด 1.1) — ลบได้ทุกการ์ดยกเว้นระดับ 0 (default)
     * ลบระดับกลางแล้ว "เลื่อนเลขระดับที่สูงกว่าลงมาแทน" ให้เรียงต่อเนื่อง เช่น 0,1,2,3 ลบ 2 → เหลือ 0,1,2
     * (สัดส่วน 3.1 ของระดับที่ลบถูกลบ, ระดับที่สูงกว่าเลื่อนตามทั้งสัดส่วน (global) และตำแหน่งที่จัดไว้ (รอบปัจจุบัน))
     */
    public function deleteLevel(Request $request): JsonResponse
    {
        if ($this->gateAdminOrHr()) {
            return response()->json(['ok' => false, 'message' => 'no permission'], 403);
        }

        $data = $request->validate([
            'level' => ['required', 'integer', 'between:1,99'],   // ระดับ 0 = default ห้ามลบ
        ]);

        $round = $this->openRound();
        if (! $round) {
            return response()->json(['ok' => false, 'message' => 'ต้องเปิดรอบก่อน'], 422);
        }

        $level = (int) $data['level'];

        AsmLevelProp::where('level', $level)->delete();
        $unassigned = AsmPositionLevel::where('round_id', $round->id)->where('level', $level)->delete();

        // เลื่อนระดับที่สูงกว่าลงมาแทน — เรียงจากน้อยไปมาก กันชน unique (level, box_id)
        $shifted = AsmLevelProp::where('level', '>', $level)->orderBy('level')->decrement('level');
        AsmPositionLevel::where('round_id', $round->id)->where('level', '>', $level)->orderBy('level')->decrement('level');

        return response()->json(['ok' => true, 'unassigned' => $unassigned, 'shifted' => $shifted]);
    }

    // ===== คอลัมน์คะแนน =====

    public function storeBox(Request $request): JsonResponse
    {
        if ($this->gateAdminOrHr()) {
            return response()->json(['ok' => false, 'message' => 'no permission'], 403);
        }

        $parentId = $request->input('parent_id') ? (int) $request->input('parent_id') : null;
        $pos = (int) AsmScoreBox::where('parent_id', $parentId)->max('sort') + 1;

        // ชื่อไม่ซ้ำภายในชั้นเดียวกัน: หลักซ้ำหลัก / รองซ้ำรอง (หลัก-รองชื่อเดียวกันได้)
        $name = trim((string) $request->input('name'));
        if ($name !== '') {
            $dup = $parentId === null
                ? AsmScoreBox::whereNull('parent_id')->where('name', $name)->exists()
                : AsmScoreBox::whereNotNull('parent_id')->where('name', $name)->exists();
            if ($dup) {
                return response()->json(['ok' => false, 'message' => "มีคอลัมน์ชื่อ \"{$name}\" อยู่แล้ว"], 422);
            }
        }

        $box = AsmScoreBox::create([
            'parent_id' => $parentId,
            'name' => trim((string) $request->input('name')) ?: ($parentId ? 'คอลัมน์ย่อย '.$pos : 'กล่องที่ '.$pos),
            'type' => $this->boxType($request->input('type')),
            'weight' => $this->num($request->input('weight')) ?? 0,
            'sort' => $pos,
        ]);

        return response()->json(['ok' => true, 'box' => $this->boxArray($box)]);
    }

    public function updateBox(Request $request, AsmScoreBox $box): JsonResponse
    {
        if ($this->gateAdminOrHr()) {
            return response()->json(['ok' => false, 'message' => 'no permission'], 403);
        }

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:191'],
            'weight' => ['sometimes', 'nullable'],
            'type' => ['sometimes', 'string'],
            // 3.2 การคำนวณคอลัมน์รอง (ชนิด Attendance)
            'att_form' => ['sometimes', 'nullable', 'in:score,grade'],
            'rate' => ['sometimes', 'nullable', 'numeric'],
            'grade_cap' => ['sometimes', 'nullable', 'in:A,B,C,D,F'],
            'full_score' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'input_levels' => ['sometimes', 'nullable', 'string', 'max:20'],
        ]);

        if (array_key_exists('name', $data)) {
            $newName = trim($data['name']);
            if ($newName !== '' && $newName !== $box->name) {
                $dup = $box->parent_id === null
                    ? AsmScoreBox::whereNull('parent_id')->where('name', $newName)->where('id', '!=', $box->id)->exists()
                    : AsmScoreBox::whereNotNull('parent_id')->where('name', $newName)->where('id', '!=', $box->id)->exists();
                if ($dup) {
                    return response()->json(['ok' => false, 'message' => "มีคอลัมน์ชื่อ \"{$newName}\" อยู่แล้ว"], 422);
                }
            }
            $box->name = $newName ?: $box->name;
        }
        if (array_key_exists('weight', $data)) {
            $box->weight = $this->num($data['weight']) ?? 0;
        }
        if (array_key_exists('type', $data)) {
            $box->type = $this->boxType($data['type']);
        }
        if (array_key_exists('att_form', $data)) {
            $box->att_form = $data['att_form'] ?: null;
        }
        if (array_key_exists('rate', $data)) {
            $box->rate = $data['rate'] === null || $data['rate'] === '' ? null : (float) $data['rate'];
        }
        if (array_key_exists('grade_cap', $data)) {
            $box->grade_cap = $data['grade_cap'] ?: null;
        }
        if (array_key_exists('full_score', $data)) {
            $box->full_score = $data['full_score'] === null || $data['full_score'] === '' ? null : (float) $data['full_score'];
        }
        if (array_key_exists('input_levels', $data)) {
            // ลำดับชั้นผู้ประเมิน 1–4 ไม่ซ้ำ เรียงจากน้อย เช่น "1,2"
            $lv = array_values(array_unique(array_filter(
                array_map('intval', explode(',', (string) $data['input_levels'])),
                fn ($n) => $n >= 1 && $n <= 4,
            )));
            sort($lv);
            $box->input_levels = $lv === [] ? null : implode(',', $lv);
        }
        $box->save();

        return response()->json(['ok' => true, 'box' => $this->boxArray($box)]);
    }

    public function deleteBox(AsmScoreBox $box): JsonResponse
    {
        if ($this->gateAdminOrHr()) {
            return response()->json(['ok' => false, 'message' => 'no permission'], 403);
        }

        $box->delete(); // cascade ลบคอลัมน์ย่อย + คะแนน

        return response()->json(['ok' => true]);
    }

    private function boxType($t): string
    {
        // score = ค่าตรง (ทศนิยม/ติดลบได้ ; '-'/N/A = ไม่เข้าสัดส่วน) · attendance = rate/เกรด · input = ผู้ประเมินกรอกในระบบ
        return in_array($t, ['score', 'attendance', 'input', 'okr', 'bonus'], true) ? $t : 'score';
    }

    private function boxArray(AsmScoreBox $box): array
    {
        return [
            'id' => $box->id,
            'parent_id' => $box->parent_id,
            'name' => $box->name,
            'type' => $box->type,
            'weight' => rtrim(rtrim(number_format((float) $box->weight, 4, '.', ''), '0'), '.'),
            'att_form' => $box->att_form,
            'rate' => $box->rate === null ? null : (float) $box->rate,
            'grade_cap' => $box->grade_cap,
            'full_score' => $box->full_score === null ? null : (float) $box->full_score,
            'input_levels' => $box->input_levels,
        ];
    }

    /** หัวข้อ 3.1 — บันทึกสัดส่วน/เพิ่มเติมของ "ระดับ × หัวข้อหลัก" (upsert รายช่อง) */
    public function saveLevelProp(Request $request): JsonResponse
    {
        if ($this->gateAdminOrHr()) {
            return response()->json(['ok' => false, 'message' => 'no permission'], 403);
        }

        $data = $request->validate([
            'level' => ['required', 'integer', 'between:1,99'],   // ระดับ 0 ไม่คำนวณ ; เกิน 5 = ระดับที่ admin เพิ่มเอง
            'box_id' => ['required', 'integer'],
            'mode' => ['sometimes', 'nullable', 'in:percent,extra,none'],   // none = ไม่คำนวณ (ข้อมูลเป็น - หรือ N/A)
            'weight' => ['sometimes', 'nullable', 'numeric'],
        ]);

        if (! AsmScoreBox::whereNull('parent_id')->whereKey($data['box_id'])->exists()) {
            return response()->json(['ok' => false, 'message' => 'ไม่พบหัวข้อหลัก'], 422);
        }

        $vals = [];
        if (array_key_exists('mode', $data)) {
            $vals['mode'] = in_array($data['mode'], ['extra', 'none'], true) ? $data['mode'] : 'percent';
        }
        if (array_key_exists('weight', $data)) {
            $vals['weight'] = (float) ($data['weight'] ?? 0);
        }

        $prop = AsmLevelProp::updateOrCreate(
            ['level' => $data['level'], 'box_id' => $data['box_id']],
            $vals,
        );

        return response()->json(['ok' => true, 'prop' => [
            'level' => $prop->level, 'box_id' => $prop->box_id,
            'mode' => $prop->mode, 'weight' => (float) $prop->weight,
        ]]);
    }

    // ===== ดาวน์โหลด Template =====

    /** ดาวน์โหลด Excel เดียว: base + ลำดับชั้น (เติมที่กรอกไว้) + ระดับ + คะแนน — ไปแก้ข้างนอกแล้วนำเข้ากลับได้ */
    public function downloadForm(): StreamedResponse
    {
        return $this->streamTemplate($this->buildTemplate(true), 'assessment_data_');
    }

    /** ดาวน์โหลดไฟล์สำหรับหัวข้อ 4.1: รหัส + ชื่อ + ระดับ + คอลัมน์คะแนน เพื่อนำไปกรอกคะแนนแล้ว import กลับ */
    public function downloadScoreTemplate(): StreamedResponse|RedirectResponse
    {
        if ($redirect = $this->gateAdminOrHr()) {
            return $redirect;
        }

        $round = $this->openRound();
        if (! $round) {
            return redirect()->route('assessment.rounds.index')->with('error', 'ต้องเปิดรอบก่อนจึงจะดาวน์โหลดไฟล์คะแนนได้');
        }

        $levelMap = AsmPositionLevel::map($round->id);
        $columns = $this->buildColumns();
        $employees = Employee::active()->orderBy('employee_code')->get();
        $codes = $employees->pluck('employee_code')->map(fn ($code) => (string) $code)->unique()->values();
        $hierMap = $codes->isEmpty()
            ? collect()
            : AsmHierarchy::where('round_id', $round->id)->whereIn('employee_code', $codes->all())->get()->keyBy('employee_code');
        $scoreMap = [];
        $scoreQuery = AsmEmployeeScore::where('round_id', $round->id);
        if ($codes->isNotEmpty()) {
            $scoreQuery->whereIn('employee_code', $codes->all());
        }
        foreach ($scoreQuery->get() as $score) {
            $scoreMap[$score->employee_code][$score->box_id] = [
                'v' => $score->value === null ? null : (float) $score->value,
                'v2' => $score->value2 === null ? null : (float) $score->value2,
                'v3' => $score->value3 === null ? null : (float) $score->value3,
                'v4' => $score->value4 === null ? null : (float) $score->value4,
                'na' => (int) $score->na_mask,
            ];
        }
        $levelPropMatrix = AsmLevelProp::matrix();

        $ss = new Spreadsheet;
        $sheet = $ss->getActiveSheet();
        $sheet->setTitle('Score Import');

        // โครงเดียวกับตาราง 4.1: รหัส | ชื่อ-สกุล | ตำแหน่ง | แผนก | ระดับ | คอลัมน์คะแนน (โซนแถว 1 + ชื่อคอลัมน์แถว 2)
        $baseHeaders = ['รหัสพนักงาน', 'ชื่อ-สกุล', 'ตำแหน่ง', 'แผนก', self::LEVEL_HEADER];
        $baseSpan = count($baseHeaders);
        $sheet->mergeCells([1, 1, $baseSpan, 1]);
        $sheet->setCellValueExplicit([1, 1], 'ข้อมูลพนักงาน', DataType::TYPE_STRING);

        $col = 1;
        foreach ($baseHeaders as $header) {
            $sheet->setCellValueExplicit([$col++, 2], $header, DataType::TYPE_STRING);
        }
        $firstScoreCol = $col;
        foreach ($columns['zones'] as $zone) {
            $start = $col;
            foreach ($zone['leaves'] as $leaf) {
                $sheet->setCellValueExplicit([$col++, 2], $leaf->name, DataType::TYPE_STRING);
            }
            if ($col - $start > 1) {
                $sheet->mergeCells([$start, 1, $col - 1, 1]);
            }
            $sheet->setCellValueExplicit([$start, 1], $zone['name'], DataType::TYPE_STRING);
        }
        $lastCol = max($col - 1, $baseSpan);

        $rowNo = 3;
        $seen = [];
        foreach ($employees as $emp) {
            $code = (string) $emp->employee_code;
            if ($code === '' || isset($seen[$code])) {
                continue;
            }
            $seen[$code] = true;

            $lvl = $this->employeeLevel($emp, $levelMap);
            $hier = $hierMap[$code] ?? null;
            $hasCompleteHierarchy = $this->hasCompleteHierarchy($hier);
            $isLevelZero = $lvl !== null && (int) $lvl === 0;

            $sheet->setCellValueExplicit([1, $rowNo], $code, DataType::TYPE_STRING);
            $sheet->setCellValueExplicit([2, $rowNo], (string) ($emp->fullNameTh() ?: $emp->name_en), DataType::TYPE_STRING);
            $sheet->setCellValueExplicit([3, $rowNo], (string) ($emp->job_th ?: $emp->job_en ?: ''), DataType::TYPE_STRING);
            $sheet->setCellValueExplicit([4, $rowNo], (string) ($emp->deptThClean() ?: $emp->dept_en ?: ''), DataType::TYPE_STRING);
            if ($lvl !== null) {
                $sheet->setCellValue([5, $rowNo], $lvl);
            }

            $c = $firstScoreCol;
            foreach ($columns['leaves'] as $leaf) {
                if ($isLevelZero || ! $hasCompleteHierarchy) {
                    $sheet->setCellValueExplicit([$c, $rowNo], '-', DataType::TYPE_STRING);
                } elseif ($this->isScoreBoxNotCalculatedForLevel($leaf, $lvl, $levelPropMatrix)) {
                    // ตรงกับหน้าจอ 4.1: ช่องไม่ถูกคำนวณของระดับนั้น = N/A (import กลับจะถูกข้ามโดย guard เดิม)
                    $sheet->setCellValueExplicit([$c, $rowNo], 'N/A', DataType::TYPE_STRING);
                } else {
                    $pv = $scoreMap[$code][$leaf->id] ?? null;
                    // Input: ยุบค่าตามกลุ่มผู้ประเมิน (คนเดียวกันหลายลำดับ = ค่าเดียว) ; ชนิดอื่นแสดงตาม slot
                    $txt = $leaf->type === 'input'
                        ? $this->groupedPartsDisplay($pv, $this->inputGroupsFor($hier, $leaf))
                        : ($pv ? $this->partsDisplay($pv['v'], $pv['v2'], $pv['v3'], $pv['v4'], (int) ($pv['na'] ?? 0)) : null);
                    if ($txt !== null) {
                        $sheet->setCellValueExplicit([$c, $rowNo], $txt, DataType::TYPE_STRING);
                    }
                }
                $c++;
            }

            $rowNo++;
        }

        // ไม่ freeze pane — คอลัมน์ฐานกว้างจนกินครึ่งจอ ทำให้เลื่อนดูคอลัมน์ขวาไม่สะดวก
        $this->styleHeaderRows($sheet, $lastCol);

        return $this->streamTemplate($ss, 'assessment_score_import_'.$round->year.'_รอบ'.$round->id.'_');
    }

    /** จัดหัวตาราง export ทุกไฟล์: ตัวหนา + กึ่งกลาง (แถวโซน merge + แถวชื่อคอลัมน์) + autosize */
    private function styleHeaderRows($sheet, int $lastCol): void
    {
        $style = $sheet->getStyle([1, 1, $lastCol, 2]);
        $style->getFont()->setBold(true);
        $style->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        foreach (range(1, $lastCol) as $ci) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($ci))->setAutoSize(true);
        }
    }

    private function streamTemplate(Spreadsheet $ss, string $prefix): StreamedResponse
    {
        $filename = $prefix.now()->format('Ymd_His').'.xlsx';

        return response()->streamDownload(function () use ($ss) {
            (new Xlsx($ss))->save('php://output');
        }, $filename, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }

    /** สร้างไฟล์ Template (base + ลำดับชั้น 1–4 + ระดับ) — จบที่คอลัมน์ระดับ ; เติมลำดับชั้นถ้า $fillHierarchy */
    private function buildTemplate(bool $fillHierarchy): Spreadsheet
    {
        $roundId = $this->openRound()?->id ?? 0;
        $levelMap = AsmPositionLevel::map($roundId);

        $ss = new Spreadsheet;
        $sheet = $ss->getActiveSheet();
        $sheet->setTitle('Template');

        // ----- header row 2 (หัวจริง) + row 1 (กลุ่ม) -----
        $col = 1;
        foreach (self::FIXED_BASE as $h) {
            $sheet->setCellValueExplicit([$col, 2], $h, DataType::TYPE_STRING);
            $col++;
        }
        $hierStart = $col;
        foreach (self::HIER as $pair) {
            $sheet->setCellValueExplicit([$col, 2], $pair[1], DataType::TYPE_STRING);
            $col++;
        }
        for ($k = 0; $k < 4; $k++) {
            $s = $hierStart + $k * 2;
            $sheet->mergeCells([$s, 1, $s + 1, 1]);
            $sheet->setCellValueExplicit([$s, 1], 'ลำดับชั้น '.($k + 1), DataType::TYPE_STRING);
        }
        $levelCol = $col;
        $sheet->setCellValueExplicit([$col, 2], self::LEVEL_HEADER, DataType::TYPE_STRING);
        $lastCol = $col;

        // ----- data (row 3+) -----
        $hierMap = $fillHierarchy ? AsmHierarchy::where('round_id', $roundId)->get()->keyBy('employee_code') : collect();

        $rowNo = 3;
        Employee::active()->orderBy('employee_code')->chunk(500, function ($chunk) use ($sheet, $levelMap, $levelCol, $hierStart, $hierMap, $fillHierarchy, &$rowNo) {
            foreach ($chunk as $emp) {
                $code = (string) $emp->employee_code;
                $sheet->setCellValueExplicit([1, $rowNo], $code, DataType::TYPE_STRING);
                $sheet->setCellValue([2, $rowNo], $emp->fullNameTh());
                $sheet->setCellValue([3, $rowNo], (string) ($emp->name_en ?? ''));
                $sheet->setCellValue([4, $rowNo], (string) ($emp->job_th ?: $emp->job_en ?: ''));
                $sheet->setCellValue([5, $rowNo], (string) ($emp->deptThClean() ?: $emp->dept_en ?: ''));

                $lvl = $this->employeeLevel($emp, $levelMap);
                $isLevelZero = $lvl === 0;

                // ลำดับชั้น 1–4 (เว้นว่าง หรือเติมค่าที่บันทึกไว้)
                if ($isLevelZero) {
                    for ($k = 0; $k < 8; $k++) {
                        $sheet->setCellValueExplicit([$hierStart + $k, $rowNo], '-', DataType::TYPE_STRING);
                    }
                } elseif ($fillHierarchy && ($h = $hierMap[$code] ?? null)) {
                    $vals = [$h->l1_id, $h->l1_name, $h->l2_id, $h->l2_name, $h->l3_id, $h->l3_name, $h->l4_id, $h->l4_name];
                    foreach ($vals as $k => $v) {
                        if ($v !== null && $v !== '') {
                            $sheet->setCellValueExplicit([$hierStart + $k, $rowNo], (string) $v, DataType::TYPE_STRING);
                        }
                    }
                }

                if ($isLevelZero) {
                    $sheet->setCellValueExplicit([$levelCol, $rowNo], '-', DataType::TYPE_STRING);
                } elseif ($lvl !== null) {
                    $sheet->setCellValue([$levelCol, $rowNo], $lvl);
                }
                $rowNo++;
            }
        });

        $this->styleHeaderRows($sheet, max($lastCol, 15));

        return $ss;
    }

    // ===== นำเข้า / บันทึกลำดับชั้น =====

    /** บันทึกลำดับชั้น 1 ช่อง (inline จากสเปรดชีตบนเว็บ) */
    public function saveHierarchy(Request $request): JsonResponse
    {
        if ($this->gateAdminOrHr()) {
            return response()->json(['ok' => false, 'message' => 'no permission'], 403);
        }

        $fields = ['l1_id', 'l1_name', 'l2_id', 'l2_name', 'l3_id', 'l3_name', 'l4_id', 'l4_name'];
        $data = $request->validate([
            'employee_code' => ['required', 'string'],
            'field' => ['required', 'in:'.implode(',', $fields)],
            'value' => ['nullable', 'string', 'max:191'],
        ]);

        $round = $this->openRound();
        if (! $round) {
            return response()->json(['ok' => false, 'message' => 'ต้องเปิดรอบก่อน'], 422);
        }

        $emp = Employee::active()->where('employee_code', $data['employee_code'])->first();
        if (! $emp) {
            return response()->json(['ok' => false, 'message' => 'ไม่พบพนักงาน'], 422);
        }
        $levelMap = AsmPositionLevel::map($round->id);
        if ($this->isLevelZero($emp, $levelMap)) {
            return response()->json(['ok' => false, 'message' => 'พนักงานระดับ 0 ไม่ถูกประเมิน จึงไม่สามารถบันทึกลำดับชั้นได้'], 422);
        }

        $hier = AsmHierarchy::updateOrCreate(
            ['round_id' => $round->id, 'employee_code' => $data['employee_code']],
            [$data['field'] => trim((string) ($data['value'] ?? '')) ?: null, 'updated_by' => app('current_user')->id],
        );

        // complete = ผู้ประเมิน 1-4 ครบทั้ง 8 ช่อง → หน้าผลลัพธ์ใช้ตัดสินใจปลดล็อกช่องคะแนนของแถวสด
        return response()->json(['ok' => true, 'complete' => $this->hasCompleteHierarchy($hier)]);
    }

    /** ล้างลำดับชั้น (คนประเมิน) ทั้งหมด — ลบทุกแถวใน asm_hierarchy (ผู้ใช้สั่งจากปุ่มพร้อมยืนยัน) */
    public function clearHierarchy(): JsonResponse
    {
        if ($this->gateAdminOrHr()) {
            return response()->json(['ok' => false, 'message' => 'no permission'], 403);
        }

        $round = $this->openRound();
        if (! $round) {
            return response()->json(['ok' => false, 'message' => 'ต้องเปิดรอบก่อน'], 422);
        }

        $cleared = AsmHierarchy::where('round_id', $round->id)->delete();

        return response()->json(['ok' => true, 'cleared' => $cleared]);
    }

    /**
     * นำเข้า "ไฟล์คอลัมน์" (หัวข้อ 2 จัดการคอลัมน์) — อ่านเฉพาะแถว 1–2 สร้างหัวคอลัมน์เท่านั้น ไม่แตะข้อมูล
     *
     * แถว 1 = หัวข้อหลัก (merge ได้ → carry-forward) ; แถว 2 = หัวคอลัมน์รอง
     * แถว 1 = แถว 2 หรือว่าง → หัวข้อหลักเดี่ยว ; ชื่อซ้ำของเดิม → ข้าม (import ซ้ำไม่สร้างซ้ำ)
     */
    public function importColumns(Request $request): RedirectResponse
    {
        if ($redirect = $this->gateAdminOrHr()) {
            return $redirect;
        }

        $request->validate(['file' => ['required', 'file', 'mimes:xlsx,xls,csv,txt', 'max:20480']]);

        $path = $request->file('file')->getRealPath();
        $rows = IOFactory::createReaderForFile($path)->setReadDataOnly(true)
            ->load($path)->getActiveSheet()->toArray(null, true, false, false);

        if (count($rows) < 2) {
            return back()->with('error', 'ไฟล์ต้องมีหัวคอลัมน์ 2 แถว (แถว 1 = หัวข้อหลัก, แถว 2 = หัวคอลัมน์รอง)');
        }

        $groupRow = array_map(fn ($c) => trim((string) $c), $rows[0]);
        $nameRow = array_map(fn ($c) => trim((string) $c), $rows[1]);

        // กันไฟล์ผิดประเภท: คอลัมน์ระบบ (รหัสพนักงาน/ลำดับชั้น/ระดับ) ไม่ใช่คอลัมน์คะแนน
        $reserved = array_merge(self::FIXED_BASE, array_column(self::HIER, 1), [self::LEVEL_HEADER]);
        // ชื่อซ้ำตรวจแยกชั้น: หัวข้อหลักซ้ำกับหลักด้วยกัน / คอลัมน์รองซ้ำกับรองด้วยกัน
        // (หลักกับรองชื่อเดียวกันได้ — กรณีแถว 1 = แถว 2 จะสร้างหลัก + รองชื่อเดียวกันข้างใน)
        $topByName = AsmScoreBox::whereNull('parent_id')->pluck('id', 'name')->all();
        $subNames = AsmScoreBox::whereNotNull('parent_id')->pluck('id', 'name')->all();
        $created = 0;
        $skippedDup = 0;
        $carry = '';

        foreach ($nameRow as $i => $name) {
            $g = $groupRow[$i] ?? '';
            if ($g !== '') {
                $carry = $g;
            }
            if ($name === '' || mb_strlen($name) > 191 || in_array($name, $reserved, true)) {
                continue;
            }
            if (str_starts_with($carry, 'ลำดับชั้น') || str_contains($name, 'ระดับตำแหน่ง')) {
                continue;
            }

            // แถว 1 = แถว 2 (หรือแถว 1 ว่าง) → หัวข้อหลักชื่อเดียวกับรอง — ยังคงสร้างคอลัมน์รองข้างใน
            $mainName = ($carry !== '' && $carry !== $name) ? $carry : $name;

            if (! isset($topByName[$mainName])) {
                $top = AsmScoreBox::create([
                    'parent_id' => null, 'name' => $mainName, 'type' => 'score', 'weight' => 0,
                    'sort' => (int) AsmScoreBox::whereNull('parent_id')->max('sort') + 1,
                ]);
                $topByName[$mainName] = $top->id;
                $created++;
            }

            if (isset($subNames[$name])) {
                $skippedDup++;

                continue;
            }

            $box = AsmScoreBox::create([
                'parent_id' => $topByName[$mainName],
                'name' => $name,
                'type' => 'score',
                'weight' => 0,
                'sort' => (int) AsmScoreBox::where('parent_id', $topByName[$mainName])->max('sort') + 1,
            ]);
            $subNames[$name] = $box->id;
            $created++;
        }

        if ($created === 0) {
            return back()->with('error', $skippedDup > 0
                ? 'ไม่มีคอลัมน์ใหม่ — ทุกคอลัมน์ในไฟล์มีอยู่ในระบบแล้ว'
                : 'ไม่พบหัวคอลัมน์ในไฟล์');
        }

        $msg = "นำเข้าคอลัมน์สำเร็จ: สร้าง {$created} รายการ";
        if ($skippedDup > 0) {
            $msg .= " · ข้าม {$skippedDup} (มีอยู่แล้ว)";
        }

        return back()->with('success', $msg)->with('assessment_columns_imported', true);
    }

    // ===== นำเข้าข้อมูลจาก Excel เดียว (คะแนน + ลำดับชั้น) =====

    public function import(Request $request): RedirectResponse
    {
        $request->validate(['file' => ['required', 'file', 'mimes:xlsx,xls,csv,txt', 'max:20480']]);

        $openRound = $this->openRound();
        if (! $openRound) {
            return back()->with('error', 'ต้องเปิดรอบก่อนจึงจะนำเข้าคะแนนได้');
        }
        $roundId = $openRound->id;

        $path = $request->file('file')->getRealPath();
        $rows = IOFactory::createReaderForFile($path)->setReadDataOnly(true)
            ->load($path)->getActiveSheet()->toArray(null, true, false, false);

        if (empty($rows)) {
            return back()->with('error', 'ไฟล์ว่างเปล่า');
        }

        // หาแถวหัว (แถวที่มี "รหัสพนักงาน") — รองรับหัว 1 หรือ 2 แถว
        $header = null;
        $headerIdx = null;
        foreach (array_slice($rows, 0, 6, true) as $i => $row) {
            $trim = array_map(fn ($c) => trim((string) $c), $row);
            if (in_array('รหัสพนักงาน', $trim, true)) {
                $header = $trim;
                $headerIdx = $i;
                break;
            }
        }
        if ($header === null) {
            return back()->with('error', 'ไม่พบคอลัมน์ "รหัสพนักงาน" ในไฟล์');
        }

        $codeCol = array_search('รหัสพนักงาน', $header, true);

        // คอลัมน์คะแนน (จับตามชื่อกล่อง) + คอลัมน์ลำดับชั้น (เริ่มที่ ID Supervisor)
        $columns = $this->buildColumns();
        $boxByCol = [];
        $leafById = [];
        $matchedNames = [];
        foreach ($columns['leaves'] as $leaf) {
            $leafById[(int) $leaf->id] = $leaf;
            $idx = array_search($leaf->name, $header, true);
            if ($idx !== false) {
                $boxByCol[$idx] = $leaf->id;
                $matchedNames[] = $leaf->name;
            }
        }
        $hStart = array_search('ID Supervisor', $header, true);
        $hFields = ['l1_id', 'l1_name', 'l2_id', 'l2_name', 'l3_id', 'l3_name', 'l4_id', 'l4_name'];

        if (empty($boxByCol) && $hStart === false) {
            return back()->with('error', 'ไม่พบคอลัมน์คะแนนหรือลำดับชั้นในไฟล์');
        }

        $levelMap = AsmPositionLevel::map($roundId);
        $employeeLevels = Employee::active()
            ->select(['employee_code', 'job_code'])
            ->get()
            ->mapWithKeys(fn ($emp) => [(string) $emp->employee_code => $this->employeeLevel($emp, $levelMap)])
            ->all();
        $hierByCode = AsmHierarchy::where('round_id', $roundId)->get()->keyBy('employee_code');
        $me = app('current_user');
        $levelPropMatrix = AsmLevelProp::matrix();
        $matched = 0;
        $skipped = 0;
        $excludedLevelZero = 0;
        $excludedNotCalculated = 0;
        $excludedIncompleteHierarchy = 0;
        $cells = 0;
        $clearedNa = 0;
        $hierRows = 0;

        DB::connection('mysql_assessment')->transaction(function () use ($rows, $headerIdx, $codeCol, $boxByCol, $leafById, $hStart, $hFields, $employeeLevels, $hierByCode, $levelPropMatrix, $me, $roundId, &$matched, &$skipped, &$excludedLevelZero, &$excludedNotCalculated, &$excludedIncompleteHierarchy, &$cells, &$clearedNa, &$hierRows) {
            $now = now();
            foreach (array_slice($rows, $headerIdx + 1) as $row) {
                $code = trim((string) ($row[$codeCol] ?? ''));
                if ($code === '') {
                    continue;
                }
                if (! array_key_exists($code, $employeeLevels)) {
                    $skipped++;

                    continue;
                }
                $matched++;
                $employeeLevel = $employeeLevels[$code];
                if ($employeeLevel === 0) {
                    $excludedLevelZero++;

                    continue;
                }

                // คะแนน — รองรับถึง 4 ค่าในช่องเดียว "8.5,7.5,6" (ชนิด Input: ตามผู้ประเมินลำดับที่ตั้ง)
                $hierValues = [];
                $existingHier = $hierByCode[$code] ?? null;
                foreach ($hFields as $field) {
                    $hierValues[$field] = $existingHier ? ($existingHier->$field ?? null) : null;
                }
                if ($hStart !== false) {
                    foreach ($hFields as $k => $field) {
                        $v = trim((string) ($row[$hStart + $k] ?? ''));
                        $hierValues[$field] = ($v === '' || $v === '-') ? null : $v;
                    }
                }
                $hasCompleteHierarchy = $this->hasCompleteHierarchyValues($hierValues);

                foreach ($boxByCol as $c => $boxId) {
                    $leafBox = $leafById[(int) $boxId] ?? null;
                    // Input: ค่าที่ i เป็นของกลุ่มผู้ประเมินที่ i (ชื่อซ้ำ = กลุ่มเดียว เติมทุก slot) — กลับด้านกับ export ที่ยุบแล้ว
                    [$v1, $v2, $v3, $v4, $naMask] = $leafBox && $leafBox->type === 'input'
                        ? $this->partsFromGroups($row[$c] ?? null, $this->inputGroupsFor($hierValues, $leafBox))
                        : $this->numParts($row[$c] ?? null);
                    $allNull = $v1 === null && $v2 === null && $v3 === null && $v4 === null;
                    // เซลล์ว่าง/ข้อความมั่ว = ข้าม (คงคะแนนเดิม) ; เซลล์เขียน N/A ชัดเจน = บันทึก N/A (ไม่คำนวณ)
                    if ($allNull && $naMask === 0) {
                        continue;
                    }
                    $isClearNa = $allNull;
                    if (! $hasCompleteHierarchy) {
                        $excludedIncompleteHierarchy++;

                        continue;
                    }
                    if ($this->isScoreBoxNotCalculatedForLevel($leafById[(int) $boxId] ?? null, $employeeLevel, $levelPropMatrix)) {
                        $excludedNotCalculated++;

                        continue;
                    }
                    $rnd = fn (?float $v) => $v === null ? null : round($v, 4);
                    AsmEmployeeScore::updateOrCreate(
                        ['round_id' => $roundId, 'employee_code' => $code, 'box_id' => $boxId],
                        [
                            'value' => $rnd($v1), 'value2' => $rnd($v2), 'value3' => $rnd($v3), 'value4' => $rnd($v4),
                            'na_mask' => $naMask, 'updated_by' => $me->id, 'updated_at' => $now,
                        ],
                    );
                    if ($isClearNa) {
                        $clearedNa++;
                    } else {
                        $cells++;
                    }
                }

                // ลำดับชั้น
                if ($hStart !== false) {
                    $vals = [];
                    foreach ($hFields as $k => $f) {
                        $v = trim((string) ($row[$hStart + $k] ?? ''));
                        $vals[$f] = ($v === '' || $v === '-') ? null : $v;
                    }
                    if (array_filter($vals, fn ($v) => $v !== null) || AsmHierarchy::where('round_id', $roundId)->where('employee_code', $code)->exists()) {
                        $vals['updated_by'] = $me->id;
                        AsmHierarchy::updateOrCreate(['round_id' => $roundId, 'employee_code' => $code], $vals);
                        $hierRows++;
                    }
                }
            }
        });

        AsmImportBatch::create([
            'filename' => $request->file('file')->getClientOriginalName(),
            'uploaded_by' => $me->id,
            'total_rows' => max(0, count($rows) - $headerIdx - 1),
            'matched_rows' => $matched,
            'updated_cells' => $cells,
            'columns_matched' => $matchedNames,
        ]);

        $msg = "นำเข้าสำเร็จ: จับคู่ {$matched} คน";
        if ($matchedNames) {
            $msg .= " · คะแนน {$cells} ช่อง";
        }
        if ($clearedNa > 0) {
            $msg .= " · บันทึก N/A (ไม่คำนวณ) {$clearedNa} ช่อง";
        }
        if ($hStart !== false) {
            $msg .= " · ลำดับชั้น {$hierRows} คน";
        }
        if ($excludedLevelZero > 0) {
            $msg .= " · ข้ามระดับ 0 {$excludedLevelZero} แถว";
        }
        if ($excludedNotCalculated > 0) {
            $msg .= " · ข้ามช่องไม่ถูกคำนวณ {$excludedNotCalculated} ช่อง";
        }
        if ($excludedIncompleteHierarchy > 0) {
            $msg .= " · ข้ามช่องผู้ประเมินไม่ครบ {$excludedIncompleteHierarchy} ช่อง";
        }
        if ($skipped > 0) {
            $msg .= " · ข้าม {$skipped} แถว (รหัสไม่อยู่ในระบบ)";
        }

        return back()
            ->with('success', $msg)
            ->with('assessment_imported', true);
    }

    // ===== แก้คะแนนในเว็บ =====

    public function updateValue(Request $request): JsonResponse
    {
        if ($this->gateAdminOrHr()) {
            return response()->json(['ok' => false, 'message' => 'no permission'], 403);
        }

        $data = $request->validate([
            'employee_code' => ['required', 'string'],
            'box_id' => ['required', 'integer'],
        ]);

        $round = $this->openRound();
        if (! $round) {
            return response()->json(['ok' => false, 'message' => 'ต้องเปิดรอบก่อน'], 422);
        }

        $emp = Employee::active()->where('employee_code', $data['employee_code'])->first();
        if (! $emp) {
            return response()->json(['ok' => false, 'message' => 'ไม่พบพนักงาน'], 422);
        }
        $levelMap = AsmPositionLevel::map($round->id);
        if ($this->isLevelZero($emp, $levelMap)) {
            return response()->json(['ok' => false, 'message' => 'พนักงานระดับ 0 ไม่ถูกประเมิน จึงไม่สามารถบันทึกคะแนนได้'], 422);
        }

        // ชนิด Input รับได้ถึง 4 คะแนนในช่องเดียว "8.5,7.5,6" ตามผู้ประเมินลำดับที่ตั้งไว้
        $box = AsmScoreBox::find((int) $data['box_id']);
        if (! $box) {
            return response()->json(['ok' => false, 'message' => 'ไม่พบคอลัมน์คะแนน'], 422);
        }
        // คะแนนทุกคอลัมน์ต้องรอ hierarchy ลำดับ 1-4 ครบ
        $hier = AsmHierarchy::where('round_id', $round->id)->where('employee_code', $data['employee_code'])->first();
        if (! $this->hasCompleteHierarchy($hier)) {
            return response()->json(['ok' => false, 'message' => 'ต้องมีผู้ประเมินลำดับ 1-4 ครบก่อน จึงจะบันทึกคะแนนนี้ได้'], 422);
        }
        if ($this->isScoreBoxNotCalculatedForEmployee($emp, $box, $levelMap, AsmLevelProp::matrix())) {
            return response()->json(['ok' => false, 'message' => 'คอลัมน์นี้ถูกตั้งค่าเป็นไม่ถูกคำนวณ จึงไม่สามารถบันทึกคะแนนได้'], 422);
        }

        [$v1, $v2, $v3, $v4, $naMask] = $this->numParts($request->input('value'));
        $r = fn (?float $v) => $v === null ? null : round($v, 4);
        $score = AsmEmployeeScore::updateOrCreate(
            ['round_id' => $round->id, 'employee_code' => $data['employee_code'], 'box_id' => (int) $data['box_id']],
            [
                'value' => $r($v1), 'value2' => $r($v2), 'value3' => $r($v3), 'value4' => $r($v4),
                'na_mask' => $naMask, 'updated_by' => app('current_user')->id,
            ],
        );

        return response()->json(['ok' => true, 'value' => $this->partsDisplay(
            $score->value === null ? null : (float) $score->value,
            $score->value2 === null ? null : (float) $score->value2,
            $score->value3 === null ? null : (float) $score->value3,
            $score->value4 === null ? null : (float) $score->value4,
            (int) $score->na_mask,
        )]);
    }

    /**
     * แยกค่า "v1,v2,v3,v4" → [v1,v2,v3,v4, naMask] ตามตำแหน่ง (สูงสุด 4 ช่อง ตามผู้ประเมินลำดับที่ตั้งไว้)
     * ค่าเดี่ยว → [v,null,null,null,0] ; ช่องว่างในตำแหน่งใด = null ; N/A/- ในตำแหน่งใด = null + ติด bit ใน naMask
     * (naMask: bit0=ช่อง 1 ... bit3=ช่อง 4 — เก็บว่าผู้ใช้ตั้งใจใส่ N/A ไม่ใช่แค่เว้นว่าง)
     *
     * @return array{0:?float,1:?float,2:?float,3:?float,4:int}
     */
    private function numParts($raw): array
    {
        $parts = array_slice(explode(',', trim((string) $raw)), 0, 4);
        $out = [];
        $mask = 0;
        for ($i = 0; $i < 4; $i++) {
            $out[$i] = array_key_exists($i, $parts) ? $this->num($parts[$i]) : null;
            if ($out[$i] === null && array_key_exists($i, $parts) && $this->isNaToken($parts[$i])) {
                $mask |= 1 << $i;
            }
        }
        $out[4] = $mask;

        return $out;
    }

    /** รวมค่า 4 ช่องกลับเป็นข้อความ "v1,v2,..." (slot ที่ติด na_mask แสดง N/A) ตัดท้ายที่ว่าง ; ไม่มีอะไรเลย = null */
    private function partsDisplay(?float $v1, ?float $v2, ?float $v3, ?float $v4, int $naMask = 0): ?string
    {
        $fmt = fn ($v) => $v === null ? '' : rtrim(rtrim(number_format((float) $v, 4, '.', ''), '0'), '.');
        $vals = [$v1, $v2, $v3, $v4];
        $last = -1;
        foreach ($vals as $i => $v) {
            if ($v !== null || ($naMask & (1 << $i))) {
                $last = $i;
            }
        }
        if ($last < 0) {
            return null;
        }

        $parts = [];
        for ($i = 0; $i <= $last; $i++) {
            $parts[] = ($naMask & (1 << $i)) ? 'N/A' : $fmt($vals[$i]);
        }

        return implode(',', $parts);
    }

    /**
     * กลุ่มผู้ประเมินของคอลัมน์ชนิด Input — slot ที่ชื่อผู้ประเมิน (l{n}_name) ซ้ำกัน = กลุ่มเดียว (ตรงกับช่องที่ merge ใน UI)
     * $hier รับได้ทั้ง object (AsmHierarchy) และ array field => value
     *
     * @return array<int,array{parts:array<int,int>,name:string}> parts = ตำแหน่ง slot (1-based)
     */
    private function inputGroupsFor($hier, AsmScoreBox $leaf): array
    {
        $slots = array_values(array_filter(explode(',', $leaf->input_levels ?: '1,2')));
        $groups = [];
        $order = [];
        foreach ($slots as $i => $lvSlot) {
            $field = 'l'.$lvSlot.'_name';
            $name = trim((string) (is_array($hier) ? ($hier[$field] ?? '') : ($hier->{$field} ?? '')));
            $key = $name !== '' ? 'n:'.$name : 'p:'.$i;
            if (! isset($groups[$key])) {
                $groups[$key] = ['parts' => [], 'name' => $name];
                $order[] = $key;
            }
            $groups[$key]['parts'][] = $i + 1;
        }

        return array_map(fn ($k) => $groups[$k], $order);
    }

    /**
     * แสดงค่าคอลัมน์ Input แบบยุบตามกลุ่มผู้ประเมิน (ใช้ตอน export) — คนเดียวกันประเมินลำดับ 1,2 → "10" ไม่ใช่ "10,10"
     * คนละคน → ค่าตามลำดับ เช่น "10,9" ; slot ที่ติด N/A แสดง N/A
     * ช่องที่ยังไม่ประเมินแสดงคำว่า "Input" (ตรง placeholder บนเว็บ) บอก user ว่าคอลัมน์นี้ประเมินในระบบ
     * เช่น ยังไม่ประเมินเลย 2 คน → "Input,Input" — import กลับ ข้อความ Input ถูกมองเป็นช่องว่าง (คงค่าเดิม ไม่ล้าง)
     */
    private function groupedPartsDisplay(?array $pv, array $groups): ?string
    {
        if ($groups === []) {
            return null;
        }
        $fmt = fn ($v) => rtrim(rtrim(number_format((float) $v, 4, '.', ''), '0'), '.');
        $mask = (int) ($pv['na'] ?? 0);
        $vals = [];
        foreach (array_values($groups) as $i => $g) {
            $part = $g['parts'][0];
            $v = $pv[$part === 1 ? 'v' : 'v'.$part] ?? null;
            $isNa = ($mask & (1 << ($part - 1))) !== 0;
            $vals[$i] = $isNa ? 'N/A' : ($v === null ? 'Input' : $fmt($v));
        }

        return implode(',', $vals);
    }

    /**
     * แปลงค่าเซลล์ import ของคอลัมน์ Input ตามกลุ่มผู้ประเมิน — ค่าที่ i เป็นของกลุ่มที่ i แล้วเติมลงทุก slot ของกลุ่ม
     * (กลับด้านของ groupedPartsDisplay — ชื่อผู้ประเมินไม่ซ้ำ = 1 กลุ่มต่อ slot ทำงานเหมือน positional เดิมทุกประการ)
     *
     * @return array{0:?float,1:?float,2:?float,3:?float,4:int}
     */
    private function partsFromGroups($raw, array $groups): array
    {
        $tokens = array_slice(explode(',', trim((string) $raw)), 0, count($groups));
        $out = [null, null, null, null, 0];
        foreach (array_values($groups) as $i => $g) {
            if (! array_key_exists($i, $tokens)) {
                continue;
            }
            $num = $this->num($tokens[$i]);
            $isNa = $num === null && $this->isNaToken($tokens[$i]);
            foreach ($g['parts'] as $part) {
                if ($num !== null) {
                    $out[$part - 1] = $num;
                } elseif ($isNa) {
                    $out[4] |= 1 << ($part - 1);
                }
            }
        }

        return $out;
    }

    /** ข้อความ 1 ช่องเป็น N/A/- ชัดเจนหรือไม่ (ต่างจากช่องว่างหรือข้อความมั่ว) */
    private function isNaToken($v): bool
    {
        $k = strtolower(str_replace(' ', '', trim((string) $v)));

        return in_array($k, ['na', 'n/a', 'n\\a', '-', '--', '–', '—'], true);
    }

    /** แปลงเป็นตัวเลข ; ว่าง/N/A/- → null */
    private function num($v): ?float
    {
        if ($v === null) {
            return null;
        }
        if (is_int($v) || is_float($v)) {
            return (float) $v;
        }
        $s = trim((string) $v);
        if ($s === '') {
            return null;
        }
        $k = strtolower(str_replace(' ', '', $s));
        if (in_array($k, ['na', 'n/a', 'n\\a', '-', '--', '–', '—'], true)) {
            return null;
        }
        $s = str_replace([',', ' '], '', $s);

        return is_numeric($s) ? (float) $s : null;
    }
}
