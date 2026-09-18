<?php

namespace App\Http\Controllers\Area5s;

use App\Http\Controllers\Area5s\Concerns\HandlesArea5sAccess;
use App\Http\Controllers\Controller;
use App\Models\Area5s\A5sLayout;
use App\Models\Area5s\A5sRound;
use App\Models\Area5s\A5sTask;
use App\Models\Insight\AppUser;
use App\Services\Area5s\A5sRoundService;
use App\Services\Area5s\A5sScoreService;
use App\Services\Area5s\A5sXlsxWriter;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * ดาวน์โหลดเอกสารผลตรวจ 5ส (admin) — เลือกรอบเดือน ดูตัวอย่าง แล้วดาวน์โหลด CSV (เปิดใน Excel ได้ มี BOM)
 */
class Area5sDownloadController extends Controller
{
    use HandlesArea5sAccess;

    private const STATUS_TH = [
        'not_started' => 'ยังไม่ดำเนินการ',
        'draft' => 'ฉบับร่าง',
        'submitted' => 'รอตรวจ',
        'resubmitted' => 'ส่งแก้ไข รอตรวจ',
        'failed' => 'ปฏิเสธ',
        'passed' => 'ผ่าน',
    ];

    public function index(Request $request)
    {
        if ($redirect = $this->gateAdmin()) {
            return $redirect;
        }

        $rounds = A5sRound::orderByDesc('year')->orderByDesc('month')->get();
        $round = null;
        if ($rounds->isNotEmpty()) {
            $round = $rounds->firstWhere('id', (int) $request->query('round'))
                ?: (A5sRound::open() ?: $rounds->first());
        }

        return view('area5s.downloads', [
            'me' => $this->me(),
            'rounds' => $rounds,
            'round' => $round,
            'monthNames' => A5sRoundService::MONTHS_TH,
        ]);
    }

    /**
     * ไฟล์ Excel (.xlsx) ตามฟอร์ม Manager: ลำดับ(ต่อ layout)/รอบเดือน(ครั้งเดียว)/โซน(merge)/อาคาร(merge)/
     * พื้นที่ห้อง(merge ต่อ layout)/จุด/ผู้รับผิดชอบ/ผู้ประเมินที่กำหนด/สถานะ(ผ่าน·ไม่ผ่าน·รอดำเนินการ)/วันตรวจ/
     * เหตุผลปฏิเสธ(เฉพาะที่ถูกปฏิเสธ) — เซลล์ที่ค่าซ้ำในกลุ่มเดียวกันจะถูก merge (โซน/อาคาร/พื้นที่ห้อง/ลำดับ)
     */
    public function export(Request $request)
    {
        if ($this->gateAdmin()) {
            abort(403);
        }

        $round = A5sRound::findOrFail((int) $request->query('round'));
        // เรียง โซน → อาคาร → Layout → รหัสจุด เพื่อให้แถวที่ merge อยู่ติดกัน
        $rows = $this->reportRows($round)
            ->sortBy([
                ['zone_sort', 'asc'], ['area_sort', 'asc'], ['layout', 'asc'], ['point', 'asc'],
            ])
            ->values();
        $roundLabel = A5sRoundService::monthLabel($round->month).' '.$round->year;
        $filename = sprintf('5S-report-%d-%02d.xlsx', $round->year, $round->month);

        $headers = ['ลำดับ', 'รอบเดือน', 'โซน', 'อาคาร', 'พื้นที่ห้อง', 'จุด', 'ผู้รับผิดชอบ', 'ผู้ประเมินที่กำหนด', 'สถานะ', 'วันตรวจ', 'เหตุผลปฏิเสธ', 'คะแนน', 'จำนวนครั้งส่ง'];
        $data = [];
        $merges = [];
        $rowNum = 1; // แถว 1 = หัวตาราง

        // ตัวช่วยเก็บช่วง merge ของแต่ละคอลัมน์เมื่อค่าที่ใช้จัดกลุ่มเปลี่ยน
        $spans = ['C' => null, 'D' => null, 'A' => null]; // C=โซน, D=อาคาร, A+E=พื้นที่ห้อง(Layout)
        $keys = ['C' => null, 'D' => null, 'A' => null];
        $layoutNo = 0;
        // ปิดช่วง merge ของคอลัมน์ โดยจบที่แถว $endRow (merge เฉพาะเมื่อครอบมากกว่า 1 แถว)
        $closeSpan = function (string $col, int $endRow) use (&$spans, &$merges) {
            if ($spans[$col] !== null && $endRow > $spans[$col]) {
                $merges[] = "{$col}{$spans[$col]}:{$col}{$endRow}";
                if ($col === 'A') {
                    $merges[] = "E{$spans[$col]}:E{$endRow}";
                }
            }
        };

        foreach ($rows as $row) {
            $rowNum++;
            // คีย์กลุ่ม: โซน (C) · อาคาร (D, ต้องอยู่ในโซนเดียวกัน) · Layout (A+E, ต้องอยู่ในอาคารเดียวกัน)
            $zoneKey = $row['zone_sort'];
            $areaKey = $zoneKey.'||'.$row['area_sort'];
            $layoutKey = $areaKey.'||'.$row['layout'];
            $newZone = $keys['C'] !== $zoneKey;
            $newArea = $newZone || $keys['D'] !== $areaKey;
            $newLayout = $newArea || $keys['A'] !== $layoutKey;

            if ($newZone) {
                $closeSpan('C', $rowNum - 1);
                $spans['C'] = $rowNum;
                $keys['C'] = $zoneKey;
            }
            if ($newArea) {
                $closeSpan('D', $rowNum - 1);
                $spans['D'] = $rowNum;
                $keys['D'] = $areaKey;
            }
            if ($newLayout) {
                $closeSpan('A', $rowNum - 1);
                $spans['A'] = $rowNum;
                $keys['A'] = $layoutKey;
                $layoutNo++;
            }

            $data[] = [
                $newLayout ? (string) $layoutNo : '',
                '', // รอบเดือน — เติมเฉพาะแถวข้อมูลแรกด้านล่าง
                $newZone ? (string) $row['zone'] : '',
                $newArea ? (string) $row['area'] : '',
                $newLayout ? (string) $row['layout'] : '',
                $row['point'],
                $row['assignees'],
                $row['evaluators'],
                match ($row['status_key']) {
                    'passed' => 'ผ่าน', 'failed' => 'ไม่ผ่าน', default => 'รอดำเนินการ',
                },
                $row['evaluated_at'] === '-' ? '' : $row['evaluated_at'],
                $row['status_key'] === 'failed' && $row['fail_reason'] !== '-' ? $row['fail_reason'] : '',
                $row['score_label'] ?? '—',
                (string) ($row['submit_count'] ?? 0),
            ];
        }
        // ปิดช่วง merge ที่ยังค้างของแถวสุดท้าย
        $closeSpan('C', $rowNum);
        $closeSpan('D', $rowNum);
        $closeSpan('A', $rowNum);

        if ($data !== []) {
            $data[0][1] = $roundLabel;
            if ($rowNum > 2) {
                $merges[] = "B2:B{$rowNum}";
            }
        }

        $file = tempnam(sys_get_temp_dir(), 'a5s_xlsx_');
        A5sXlsxWriter::write(
            $file,
            'ผลตรวจ 5ส '.$roundLabel,
            $headers,
            $data,
            $merges,
            [6, 16, 10, 14, 22, 7, 26, 26, 14, 17, 34, 9, 11],
            [3, 3, 3, 3, 3, 3, 2, 2, 3, 3, 2, 3, 3], // A-F,I,J,L,M กึ่งกลาง · ผู้รับผิดชอบ/ผู้ประเมิน/เหตุผล wrap
        );

        return response()->download($file, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    /**
     * ดาวน์โหลดทั้งปี (flat หนึ่งชีต) — ทุกรอบ/ครั้งตรวจของปีนั้น พร้อมคอลัมน์ รอบ · คะแนน% · จำนวนครั้งส่ง
     * ไม่ merge (เรียง/กรองง่าย) — ครอบข้อมูลการส่งของทุกคนทั้งปี (Manager 2026-07-24)
     */
    public function exportYear(Request $request)
    {
        if ($this->gateAdmin()) {
            abort(403);
        }

        $year = (int) ($request->query('year') ?: A5sRoundService::currentYearBe());
        $rounds = A5sRound::where('year', $year)->orderBy('month')->orderBy('seq')->get();

        $headers = ['รอบ', 'โซน', 'อาคาร', 'พื้นที่ห้อง', 'จุด', 'ผู้รับผิดชอบ', 'ผู้ประเมินที่กำหนด', 'สถานะ', 'คะแนน', 'จำนวนครั้งส่ง', 'วันตรวจ', 'เหตุผลปฏิเสธ'];
        $data = [];
        foreach ($rounds as $round) {
            $label = A5sRoundService::monthLabel($round->month).' '.$round->year
                .(((int) ($round->seq ?? 1)) > 1 ? ' · ครั้งที่ '.$round->seq : '');
            $rows = $this->reportRows($round)
                ->sortBy([['zone_sort', 'asc'], ['area_sort', 'asc'], ['layout', 'asc'], ['point', 'asc']])
                ->values();
            foreach ($rows as $row) {
                $data[] = [
                    $label,
                    (string) $row['zone'],
                    (string) $row['area'],
                    (string) $row['layout'],
                    (string) $row['point'],
                    (string) $row['assignees'],
                    (string) $row['evaluators'],
                    match ($row['status_key']) {
                        'passed' => 'ผ่าน', 'failed' => 'ไม่ผ่าน', default => 'รอดำเนินการ',
                    },
                    $row['score_label'] ?? '—',
                    (string) ($row['submit_count'] ?? 0),
                    $row['evaluated_at'] === '-' ? '' : $row['evaluated_at'],
                    $row['status_key'] === 'failed' && $row['fail_reason'] !== '-' ? $row['fail_reason'] : '',
                ];
            }
        }

        $file = tempnam(sys_get_temp_dir(), 'a5s_xlsx_');
        A5sXlsxWriter::write(
            $file,
            'ผลตรวจ 5ส ทั้งปี '.$year,
            $headers,
            $data,
            [],
            [18, 10, 14, 22, 7, 26, 26, 14, 9, 11, 17, 34],
            [3, 3, 3, 3, 3, 2, 2, 3, 3, 3, 3, 2],
        );

        return response()->download($file, sprintf('5S-report-year-%d.xlsx', $year), [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    /** แถวรายงานต่อจุด (เรียงตาม โซน → อาคาร → Layout → รหัสจุด) */
    private function reportRows(A5sRound $round): Collection
    {
        $tasks = A5sTask::where('round_id', $round->id)
            ->withCount('cards')
            ->get();

        // คะแนน % ต่อจุด (เฉลี่ยทุกครั้งที่ส่ง ผ่าน100/ไม่ผ่าน0) (Manager 2026-07-24)
        $scoresByTask = A5sScoreService::taskScores($tasks->pluck('id'));

        // แผนที่ layout_id → โซน/อาคาร ที่ Layout สังกัด (ใช้เติมคอลัมน์ โซน/อาคาร และจัดลำดับ merge)
        $zoneAreaByLayout = A5sLayout::whereIn('id', $tasks->pluck('layout_id')->filter()->unique())
            ->with('floor.area.zoneMap.zone')
            ->get()
            ->mapWithKeys(function (A5sLayout $layout) {
                $area = $layout->floor?->area;
                $zone = $area?->zoneMap?->zone;

                return [$layout->id => [
                    'zone' => $zone?->name ?? '-',
                    'zone_sort' => sprintf('%03d|%03d', $zone?->sort ?? 999, $zone?->id ?? 999),
                    'area' => $area?->name ?? '-',
                    'area_sort' => sprintf('%03d|%03d', $area?->sort ?? 999, $area?->id ?? 999),
                ]];
            });
        $evaluators = AppUser::whereIn('id', $tasks->pluck('evaluated_by')->filter()->unique())
            ->get()
            ->keyBy('id');
        $peoplePayload = fn ($json) => collect((array) $json)
            ->map(function ($person) {
                $name = (string) ($person['name'] ?? ($person['code'] ?? '-'));
                $nameTh = (string) ($person['name_th'] ?? $name);
                $nameEn = (string) ($person['name_en'] ?? $nameTh);
                $nameMy = (string) ($person['name_my'] ?? $nameEn);

                return [
                    'code' => (string) ($person['code'] ?? ''),
                    'name' => $name,
                    'name_th' => $nameTh,
                    'name_en' => $nameEn,
                    'name_my' => $nameMy,
                ];
            })
            ->filter(fn ($person) => ($person['name'] ?? '') !== '')
            ->values()
            ->all();
        // นำหน้าชื่อด้วยรหัสพนักงานในวงเล็บ เช่น "[71056] นายภูมิพัฒน์ ไชยชาติ" (Manager 2026-07-22)
        $names = fn (array $people) => collect($people)
            ->map(function ($person) {
                $name = trim((string) ($person['name'] ?? ''));
                $code = trim((string) ($person['code'] ?? ''));

                return $code !== '' ? "[{$code}] {$name}" : $name;
            })
            ->filter(fn ($label) => trim($label, '[] ') !== '')
            ->implode(', ') ?: '-';

        return $tasks->map(function (A5sTask $task) use ($evaluators, $names, $peoplePayload, $zoneAreaByLayout, $scoresByTask) {
            $evaluator = $task->evaluated_by ? $evaluators->get($task->evaluated_by) : null;
            $assignees = $peoplePayload($task->assignees_json);
            $evaluatorsSnapshot = $peoplePayload($task->evaluators_json);
            $evaluatedByPayload = $evaluator ? $this->a5sAppUserPayload($evaluator, $evaluator->employee_code) : null;
            $zoneArea = $zoneAreaByLayout->get($task->layout_id, [
                'zone' => '-', 'zone_sort' => '999|999', 'area' => '-', 'area_sort' => '999|999',
            ]);

            return [
                'zone' => $zoneArea['zone'],
                'zone_sort' => $zoneArea['zone_sort'],
                'area' => $zoneArea['area'],
                'area_sort' => $zoneArea['area_sort'],
                'layout' => $task->layout_name,
                'point' => $task->point_code,
                'point_name' => $task->point_name,
                'assignees_people' => $assignees,
                'evaluators_people' => $evaluatorsSnapshot,
                'assignees' => $names($assignees),
                'evaluators' => $names($evaluatorsSnapshot),
                'cards' => (int) $task->cards_count,
                'score_label' => A5sScoreService::label($scoresByTask[$task->id] ?? null),
                'submit_count' => (int) $task->submit_count,
                'status_key' => $task->status,
                'status' => self::STATUS_TH[$task->status] ?? $task->status,
                'result' => match ($task->result) {
                    'pass' => 'ผ่าน', 'fail' => 'ปฏิเสธ', default => '-',
                },
                'evaluated_by_person' => $evaluatedByPayload,
                'evaluated_by' => $evaluatedByPayload['name'] ?? '-',
                'evaluated_at' => $task->evaluated_at?->format('d/m/Y H:i') ?? '-',
                'submit_count' => (int) $task->submit_count,
                'submitted_at' => $task->submitted_at?->format('d/m/Y H:i') ?? '-',
                'fail_reason' => $task->fail_reason ?: '-',
                'advice' => $task->advice ?: '-',
            ];
        });
    }
}
