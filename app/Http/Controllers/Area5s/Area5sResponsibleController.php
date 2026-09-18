<?php

namespace App\Http\Controllers\Area5s;

use App\Http\Controllers\Area5s\Concerns\HandlesArea5sAccess;
use App\Http\Controllers\Controller;
use App\Models\Area5s\A5sActivityLog;
use App\Models\Area5s\A5sCard;
use App\Models\Area5s\A5sCardImage;
use App\Models\Area5s\A5sCompanyPlan;
use App\Models\Area5s\A5sEvaluatorScope;
use App\Models\Area5s\A5sLayout;
use App\Models\Area5s\A5sNotification;
use App\Models\Area5s\A5sPoint;
use App\Models\Area5s\A5sPointAssignee;
use App\Models\Area5s\A5sRound;
use App\Models\Area5s\A5sTask;
use App\Models\Area5s\A5sTaskAttempt;
use App\Models\Area5s\A5sZone;
use App\Models\Area5s\A5sZoneMapArea;
use App\Models\Insight\AppUser;
use App\Models\Insight\Employee;
use App\Services\Area5s\A5sRoundService;
use App\Services\Area5s\A5sScoreService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View as ViewContract;

class Area5sResponsibleController extends Controller
{
    use HandlesArea5sAccess;

    public function index(Request $request): ViewContract|RedirectResponse
    {
        if ($redirect = $this->gateMyWork()) {
            return $redirect;
        }

        $me = $this->me();
        $myCode = $this->myCode();
        $mode = $request->query('mode') === 'review' ? 'review' : 'work';
        $myPointIds = $this->myPointIds($myCode);
        $layoutIds = A5sPoint::whereIn('id', $myPointIds)
            ->where('is_active', true)
            ->pluck('layout_id')
            ->unique();

        $reviewLayoutIds = $this->reviewLayoutIds($myCode);
        $openRound = A5sRound::open();
        $openRoundId = (int) ($openRound?->id ?? 0);

        // เดือนและวันที่ตรวจถูกจำกัดเฉพาะรอบที่ผู้ใช้มีสิทธิ์เห็นตามโหมดงาน/ตรวจประเมิน
        $workspaceLayoutIds = $mode === 'review' ? $reviewLayoutIds : $layoutIds;
        $workspaceRoundIds = A5sLayout::query()
            ->whereIn('id', $workspaceLayoutIds)
            ->where('is_active', true)
            ->pluck('round_id')
            ->filter()
            ->unique()
            ->values();
        if ($openRoundId > 0) {
            $workspaceRoundIds->push($openRoundId);
            $workspaceRoundIds = $workspaceRoundIds->unique()->values();
        }
        $workspaceRounds = A5sRound::query()
            ->whereIn('id', $workspaceRoundIds)
            ->get()
            ->sortByDesc(fn (A5sRound $round) => sprintf(
                '%05d%02d%03d%010d',
                (int) $round->year,
                (int) $round->month,
                (int) ($round->seq ?? 1),
                (int) $round->id
            ))
            ->values();
        $workspaceRoundsByMonth = $workspaceRounds
            ->groupBy(fn (A5sRound $round) => $round->year.'-'.$round->month);
        $requestedMonth = trim((string) $request->query('month'));
        $requestedRoundId = (int) $request->query('round');
        $requestedRound = $requestedRoundId
            ? $workspaceRounds->firstWhere('id', $requestedRoundId)
            : null;
        $requestedMonthRounds = $requestedMonth !== ''
            ? $workspaceRoundsByMonth->get($requestedMonth)
            : null;

        if ($requestedMonthRounds instanceof Collection && $requestedMonthRounds->isNotEmpty()) {
            $viewRound = $requestedRound
                && $requestedMonthRounds->contains(fn (A5sRound $round) => (int) $round->id === (int) $requestedRound->id)
                    ? $requestedRound
                    : ($requestedMonthRounds->firstWhere('id', $openRoundId) ?? $requestedMonthRounds->first());
        } elseif ($requestedRound) {
            $viewRound = $requestedRound;
        } else {
            $viewRound = $workspaceRounds->firstWhere('id', $openRoundId) ?? $workspaceRounds->first();
        }

        $viewRoundId = (int) ($viewRound?->id ?? 0);
        $isWorkspaceOpenRound = $viewRoundId > 0 && $viewRoundId === $openRoundId;
        $selectedMonthKey = $viewRound ? $viewRound->year.'-'.$viewRound->month : null;
        $workspaceMonthOptions = $workspaceRoundsByMonth
            ->map(function (Collection $rounds, string $key) use ($selectedMonthKey) {
                $round = $rounds->first();

                return [
                    'key' => $key,
                    'month' => (int) $round->month,
                    'year' => (int) $round->year,
                    'label' => A5sRoundService::monthLabel((int) $round->month).' '.$round->year,
                    'is_selected' => $key === $selectedMonthKey,
                ];
            })
            ->values();
        $workspaceInspectionOptions = $viewRound
            ? $workspaceRoundsByMonth
                ->get($selectedMonthKey, collect())
                ->sortBy(fn (A5sRound $round) => sprintf('%03d%010d', (int) ($round->seq ?? 1), (int) $round->id))
                ->map(function (A5sRound $round) use ($viewRoundId, $openRoundId) {
                    $inspectionDate = $round->inspected_on ?? $round->opened_at;

                    return [
                        'round_id' => (int) $round->id,
                        'date_label' => $inspectionDate
                            ? $inspectionDate->format('d/m/').($inspectionDate->year + 543)
                            : '-',
                        'status' => (string) $round->status,
                        'is_selected' => (int) $round->id === $viewRoundId,
                        'is_open' => (int) $round->id === $openRoundId,
                    ];
                })
                ->values()
            : collect();

        // คะแนน % ของฉัน — รอบนี้ + รวมทุกรอบ (เฉลี่ยคะแนนจุด ข้ามจุดที่ยังไม่ตัดสิน) (Manager 2026-07-24)
        $myAllTasks = A5sTask::whereIn('point_id', $myPointIds)->get(['id', 'round_id']);
        $myScoresByTask = A5sScoreService::taskScores($myAllTasks->pluck('id'));
        $myRoundScores = $myAllTasks->groupBy('round_id')->map(fn ($g) => A5sScoreService::average(
            $g->pluck('id')->mapWithKeys(fn ($id) => [$id => ($myScoresByTask[$id] ?? null)])->all()
        ));
        $myScoreThisRound = $viewRoundId ? ($myRoundScores[$viewRoundId] ?? null) : null;
        $myScoreOverall = A5sScoreService::average($myRoundScores->all());
        // บริบทของคะแนน — ให้ผู้ใช้รู้ว่าตัวเลขมาจากไหน (Manager 2026-07-31)
        $workspaceRound = $viewRound ? [
            'id' => $viewRoundId,
            'month' => (int) $viewRound->month,
            'year' => (int) $viewRound->year,
            'status' => (string) $viewRound->status,
            'is_open' => $isWorkspaceOpenRound,
            'inspection_date' => $viewRound->inspected_on
                ? $viewRound->inspected_on->format('d/m/').($viewRound->inspected_on->year + 543)
                : null,
            'opened_at' => $viewRound->opened_at
                ? $viewRound->opened_at->format('d/m/').($viewRound->opened_at->year + 543).' '.$viewRound->opened_at->format('H:i')
                : null,
        ] : null;
        $scoredTaskIdsThisRound = $myAllTasks->where('round_id', $viewRoundId)
            ->filter(fn ($task) => ($myScoresByTask[$task->id] ?? null) !== null);
        $scoreContext = [
            // เอาแค่ชื่อเดือน/ปี ไม่ต้องมีวันที่ตรวจ — การ์ดคะแนนต้องสั้น
            'round_label' => $viewRound ? A5sRoundService::monthLabel((int) $viewRound->month).' '.$viewRound->year : null,
            'scored_points' => $scoredTaskIdsThisRound->count(),
            'total_points' => $myAllTasks->where('round_id', $viewRoundId)->count(),
            'round_count' => $myRoundScores->filter(fn ($score) => $score !== null)->count(),
        ];
        $workspaceCalendar = $mode === 'work'
            ? $this->calendarDataForPoints($myPointIds, $workspaceRounds)
            : ['months' => [], 'overall' => null];
        $layouts = A5sLayout::whereIn('id', $layoutIds)
            ->where('round_id', $viewRoundId)
            ->where('is_active', true)
            ->with(['floor.area.zoneMap.zone'])
            ->withCount('points')
            ->orderByDesc('updated_at')
            ->get();

        $myByLayout = A5sPoint::whereIn('id', $myPointIds)
            ->where('is_active', true)
            ->pluck('layout_id')
            ->countBy();
        $myPointIdsByLayout = A5sPoint::whereIn('id', $myPointIds)
            ->where('is_active', true)
            ->get(['id', 'layout_id'])
            ->groupBy('layout_id')
            ->map(fn (Collection $points) => $points->pluck('id')->values());
        $taskByPoint = $this->tasksByPoint($myPointIds, $viewRoundId);

        $cardsByLayout = A5sTask::whereIn('point_id', $myPointIds)
            ->where('round_id', $viewRoundId)
            ->withCount('cards')
            ->get()
            ->groupBy('layout_id')
            ->map(fn (Collection $tasks) => $tasks->sum('cards_count'));
        $workSummaryByLayout = $layouts->mapWithKeys(fn (A5sLayout $layout) => [
            $layout->id => $this->workLayoutSummary(
                $layout,
                $myPointIdsByLayout->get($layout->id, collect()),
                $taskByPoint
            ),
        ]);
        $reviewLayouts = A5sLayout::whereIn('id', $reviewLayoutIds)
            ->where('round_id', $viewRoundId)
            ->where('is_active', true)
            ->with(['floor.area.zoneMap.zone'])
            ->withCount('points')
            ->orderByDesc('updated_at')
            ->get();
        $reviewSummaryByLayout = $this->reviewLayoutSummaries($reviewLayouts, $myCode, $viewRoundId);
        $workPointDetails = $mode === 'work'
            ? $this->entryPointDetails($workSummaryByLayout, $viewRoundId)
            : collect();
        $reviewPointDetails = $mode === 'review'
            ? $this->entryPointDetails($reviewSummaryByLayout, $viewRoundId)
            : collect();
        $reviewPointIdsByLayout = $reviewSummaryByLayout->map(fn ($summary) => (int) ($summary['total'] ?? 0));
        $reviewPendingByLayout = $reviewSummaryByLayout->map(fn ($summary) => (int) ($summary['waiting'] ?? 0));
        // โหลดโครงแปลนเต็ม (โซน → ภาพโซนย่อย → อาคาร) — ใช้ทั้งวาดโซนบนแปลน และ list อาคารใน modal
        $plan = A5sCompanyPlan::where('is_active', true)
            ->with([
                'zones' => fn ($q) => $q->where('is_active', true)->orderBy('sort')->orderBy('id'),
                'zones.zoneMaps' => fn ($q) => $q->where('is_active', true)->orderBy('sort')->orderBy('id'),
                'zones.zoneMaps.areas' => fn ($q) => $q->where('is_active', true)->orderBy('sort')->orderBy('id'),
            ])
            ->orderByDesc('id')
            ->first();
        $planZones = $plan?->zones ?? collect();

        $workSubZoneBoards = $this->subZoneBoardsForLayouts($layouts, $workSummaryByLayout, reviewMode: false, cardsByLayout: $cardsByLayout, plan: $plan);
        $reviewSubZoneBoards = $this->subZoneBoardsForLayouts($reviewLayouts, $reviewSummaryByLayout, reviewMode: true, plan: $plan);

        // สรุปสถานะรวมต่ออาคาร/โซน — โชว์บนภาพแปลนบริษัทด้านบน list เดิม (Manager 2026-07-19)
        $zoneMap = $this->zoneLookupByLayoutIds($layouts->pluck('id')->merge($reviewLayouts->pluck('id')));
        $zoneSummary = [
            'work' => $this->zoneWorkSummary($layouts, $workSummaryByLayout, $zoneMap),
            'review' => $this->zoneReviewSummary($reviewLayouts, $reviewSummaryByLayout, $zoneMap),
        ];

        return view('area5s.responsible.index', [
            'me' => $me,
            'layouts' => $layouts,
            'myByLayout' => $myByLayout,
            'cardsByLayout' => $cardsByLayout,
            'workSummaryByLayout' => $workSummaryByLayout,
            'workPointDetails' => $workPointDetails,
            'reviewLayouts' => $reviewLayouts,
            'reviewPointIdsByLayout' => $reviewPointIdsByLayout,
            'reviewPendingByLayout' => $reviewPendingByLayout,
            'reviewSummaryByLayout' => $reviewSummaryByLayout,
            'reviewPointDetails' => $reviewPointDetails,
            'mode' => $mode,
            'plan' => $plan,
            'planZones' => $planZones,
            'zoneSummary' => $zoneSummary,
            'workSubZoneBoards' => $workSubZoneBoards,
            'reviewSubZoneBoards' => $reviewSubZoneBoards,
            'myScoreThisRound' => A5sScoreService::label($myScoreThisRound),
            'myScoreOverall' => A5sScoreService::label($myScoreOverall),
            // ค่าดิบ + บริบท — view ใช้ทำสีตามเกณฑ์และบอกที่มาของตัวเลข
            'myScoreThisRoundRaw' => $myScoreThisRound,
            'myScoreOverallRaw' => $myScoreOverall,
            'scoreContext' => $scoreContext,
            'workspaceRound' => $workspaceRound,
            'workspaceMonthOptions' => $workspaceMonthOptions,
            'workspaceInspectionOptions' => $workspaceInspectionOptions,
            'workspaceCalendar' => $workspaceCalendar,
            // รายการงานแบบแบน (พื้นที่ + จุด + สถานะ) — ใช้ทำแผง "งานที่ต้องทำ" ที่กดเข้างานได้ตรง
            'myTaskRows' => $mode === 'review'
                ? $this->flatTaskRows($reviewLayouts, $reviewSummaryByLayout, fn (A5sLayout $l) => route('area5s.evaluations.show', $l))
                : $this->flatTaskRows($layouts, $workSummaryByLayout, fn (A5sLayout $l) => route('area5s.responsible.show', $l)),
        ]);
    }

    /** หน้า Layout เฉพาะพื้นที่ย่อยที่ผู้ใช้มีจุดรับผิดชอบ — เลือกครั้งตรวจ (รอบ) แล้วดูประวัติการประเมิน */
    public function area(Request $request, A5sZoneMapArea $area): ViewContract|RedirectResponse
    {
        if ($redirect = $this->gateResponsible()) {
            return $redirect;
        }

        $area->load([
            'zoneMap.zone',
            'floors' => fn ($query) => $query->where('is_active', true)->orderBy('sort')->orderBy('name'),
        ]);
        abort_unless($area->is_active && $area->zoneMap?->is_active && $area->zoneMap?->zone?->is_active, 404);

        $myCode = $this->myCode();
        $myPointIds = $this->myPointIds($myCode);
        $floorIds = $area->floors->pluck('id');

        // Layout ของฉัน (ข้ามทุกรอบ) ในพื้นที่นี้ → รอบที่มีงานของฉัน = แท็บเดือน/ครั้งตรวจ
        $myLayoutIds = A5sPoint::whereIn('id', $myPointIds)->where('is_active', true)->pluck('layout_id')->unique();
        $roundIds = A5sLayout::whereIn('id', $myLayoutIds)
            ->whereIn('floor_id', $floorIds)
            ->where('is_active', true)
            ->pluck('round_id')->unique()->filter()->values();

        $openRound = A5sRound::open();
        $rounds = A5sRound::whereIn('id', $roundIds)->get();
        $viewRound = $this->resolveViewRound($rounds, $openRound, (int) $request->query('round'));

        if (! $viewRound) {
            return redirect()
                ->route('area5s.responsible.index')
                ->with('error', 'คุณยังไม่มีงานที่รับผิดชอบในพื้นที่นี้');
        }
        $viewRoundId = (int) $viewRound->id;
        $isOpenRound = $openRound && $viewRoundId === (int) $openRound->id;

        // Layout ของฉันในรอบที่เลือก เรียงตามชั้น (sort ของ floor) แล้วชื่อ Layout
        $floorPos = $area->floors->pluck('id')->values()->flip();
        $layouts = A5sLayout::whereIn('id', $myLayoutIds)
            ->whereIn('floor_id', $floorIds)
            ->where('round_id', $viewRoundId)
            ->where('is_active', true)
            ->with('floor')
            ->withCount('points')
            ->get()
            ->sortBy(fn (A5sLayout $layout) => sprintf('%03d|%s', $floorPos[$layout->floor_id] ?? 999, mb_strtolower($layout->name)))
            ->values();

        $areaPointRows = A5sPoint::whereIn('id', $myPointIds)
            ->whereIn('layout_id', $layouts->pluck('id'))
            ->where('is_active', true)
            ->orderBy('sort')->orderBy('code')
            ->get(['id', 'layout_id', 'code', 'name']);
        $pointsByLayout = $areaPointRows->groupBy('layout_id');
        $taskByPoint = A5sTask::where('round_id', $viewRoundId)
            ->whereIn('point_id', $areaPointRows->pluck('id'))
            ->withCount('cards')
            ->get();
        $cardCountByLayout = $taskByPoint->groupBy('layout_id')->map(fn (Collection $tasks) => $tasks->sum('cards_count'));
        $taskByPoint = $taskByPoint->keyBy('point_id');

        $panel = [
            'back_url' => route('area5s.responsible.index'),
            'title_key' => 'a5s.myWork.layoutsTitle',
            'title_default' => 'พื้นที่ที่มีจุดของคุณ',
            'hint_key' => 'a5s.history.hintWork',
            'hint_default' => 'เลือกครั้งตรวจเพื่อดูประวัติการประเมินของจุดที่คุณรับผิดชอบในรอบนั้น',
            'context_zone' => $area->zoneMap->zone->name,
            'context_area' => $area->name,
            'round_nav' => $this->a5sRoundNav($rounds, $viewRound, $openRound),
            'is_open_round' => $isOpenRound,
            'view_round_label' => $this->a5sRoundLabel($viewRound),
            'floors' => $this->a5sHistoryFloors(
                $layouts,
                $pointsByLayout,
                $taskByPoint,
                $cardCountByLayout,
                fn (A5sLayout $layout) => $isOpenRound ? route('area5s.responsible.show', $layout) : null,
                'a5s.myWork.openWork',
                'เปิดบันทึกงาน'
            ),
            'empty_key' => 'a5s.myWork.noAssigned',
            'empty_default' => 'ยังไม่มีจุดพื้นที่ที่มอบหมายให้คุณ',
        ];

        return view('area5s.responsible.area', [
            'me' => $this->me(),
            'panel' => $panel,
        ]);
    }

    /** หน้า Layout เฉพาะพื้นที่ย่อยที่ผู้ใช้ได้รับมอบหมายให้ตรวจประเมิน — เลือกครั้งตรวจ (รอบ) แล้วดูประวัติผลตรวจ */
    public function reviewArea(Request $request, A5sZoneMapArea $area): ViewContract|RedirectResponse
    {
        if ($redirect = $this->gateEvaluator()) {
            return $redirect;
        }

        $area->load([
            'zoneMap.zone',
            'floors' => fn ($query) => $query->where('is_active', true)->orderBy('sort')->orderBy('name'),
        ]);
        abort_unless($area->is_active && $area->zoneMap?->is_active && $area->zoneMap?->zone?->is_active, 404);

        $myCode = $this->myCode();
        $floorIds = $area->floors->pluck('id');

        // Layout ที่อยู่ในขอบเขตตรวจของฉัน (ข้ามทุกรอบ) ในพื้นที่นี้ → รอบที่มีงานตรวจ = แท็บเดือน/ครั้งตรวจ
        $scopeLayoutIds = $this->isA5sAdmin()
            ? A5sLayout::whereIn('floor_id', $floorIds)->where('is_active', true)->pluck('id')
            : A5sEvaluatorScope::where('employee_code', $myCode)->pluck('layout_id')->unique();
        $roundIds = A5sLayout::whereIn('id', $scopeLayoutIds)
            ->whereIn('floor_id', $floorIds)
            ->where('is_active', true)
            ->pluck('round_id')->unique()->filter()->values();

        $openRound = A5sRound::open();
        $rounds = A5sRound::whereIn('id', $roundIds)->get();
        $viewRound = $this->resolveViewRound($rounds, $openRound, (int) $request->query('round'));

        if (! $viewRound) {
            return redirect()
                ->route('area5s.responsible.index', ['mode' => 'review'])
                ->with('error', 'คุณยังไม่มีรายการตรวจประเมินในพื้นที่นี้');
        }
        $viewRoundId = (int) $viewRound->id;
        $isOpenRound = $openRound && $viewRoundId === (int) $openRound->id;

        $floorPos = $area->floors->pluck('id')->values()->flip();
        $layouts = A5sLayout::whereIn('id', $scopeLayoutIds)
            ->whereIn('floor_id', $floorIds)
            ->where('round_id', $viewRoundId)
            ->where('is_active', true)
            ->with('floor')
            ->withCount(['points' => fn ($query) => $query->where('is_active', true)])
            ->get()
            ->sortBy(fn (A5sLayout $layout) => sprintf('%03d|%s', $floorPos[$layout->floor_id] ?? 999, mb_strtolower($layout->name)))
            ->values();

        // จุดที่อยู่ในขอบเขตตรวจของฉันต่อ Layout (admin = ทุกจุด active / evaluator = เฉพาะ scope)
        $reviewPointIdsByLayout = $layouts->mapWithKeys(fn (A5sLayout $layout) => [
            $layout->id => $this->reviewPointIdsForLayout($layout, $myCode),
        ]);
        $allPointIds = $reviewPointIdsByLayout->flatten()->unique()->values();
        $pointsByLayout = A5sPoint::whereIn('id', $allPointIds)
            ->where('is_active', true)
            ->orderBy('sort')->orderBy('code')
            ->get(['id', 'layout_id', 'code', 'name'])
            ->groupBy('layout_id');

        // เอาเฉพาะ Layout ที่มีจุดตรวจของฉันจริง
        $layouts = $layouts
            ->filter(fn (A5sLayout $layout) => ($pointsByLayout->get($layout->id)?->count() ?? 0) > 0)
            ->values();

        if ($layouts->isEmpty()) {
            return redirect()
                ->route('area5s.responsible.index', ['mode' => 'review'])
                ->with('error', 'คุณยังไม่มีรายการตรวจประเมินในพื้นที่นี้');
        }

        $taskByPoint = A5sTask::where('round_id', $viewRoundId)
            ->whereIn('point_id', $allPointIds)
            ->withCount('cards')
            ->get();
        $cardCountByLayout = $taskByPoint->groupBy('layout_id')->map(fn (Collection $tasks) => $tasks->sum('cards_count'));
        $taskByPoint = $taskByPoint->keyBy('point_id');

        $panel = [
            'back_url' => route('area5s.responsible.index', ['mode' => 'review']),
            'title_key' => 'a5s.myWork.reviewAreaTitle',
            'title_default' => 'พื้นที่ที่ต้องตรวจประเมิน',
            'hint_key' => 'a5s.history.hintReview',
            'hint_default' => 'เลือกครั้งตรวจเพื่อดูประวัติผลการประเมินของจุดที่คุณรับผิดชอบตรวจ',
            'context_zone' => $area->zoneMap->zone->name,
            'context_area' => $area->name,
            'round_nav' => $this->a5sRoundNav($rounds, $viewRound, $openRound),
            'is_open_round' => $isOpenRound,
            'view_round_label' => $this->a5sRoundLabel($viewRound),
            'floors' => $this->a5sHistoryFloors(
                $layouts,
                $pointsByLayout,
                $taskByPoint,
                $cardCountByLayout,
                fn (A5sLayout $layout) => $isOpenRound ? route('area5s.evaluations.show', $layout) : null,
                'a5s.myWork.reviewTitle',
                'ตรวจประเมิน'
            ),
            'empty_key' => 'a5s.myWork.noReviewAssigned',
            'empty_default' => 'ยังไม่มีพื้นที่ที่ได้รับมอบหมายให้ตรวจประเมิน',
        ];

        return view('area5s.responsible.review-area', [
            'me' => $this->me(),
            'panel' => $panel,
        ]);
    }

    /** layout_id -> ['zone_id'=>..,'zone_name'=>..] ผ่าน floor->zone (ยังไม่ map = ไม่ขึ้นในสรุปโซน) */
    private function zoneLookupByLayoutIds(Collection $layoutIds): array
    {
        return A5sLayout::whereIn('id', $layoutIds->unique())
            ->whereNotNull('floor_id')
            ->with(['floor.zone', 'floor.area.zoneMap.zone'])
            ->get()
            ->filter(fn (A5sLayout $l) => $this->layoutZone($l))
            ->mapWithKeys(function (A5sLayout $l) {
                $zone = $this->layoutZone($l);

                return [$l->id => ['zone_id' => $zone->id, 'zone_name' => $zone->name]];
            })
            ->all();
    }

    /** สรุปต่อโซน (โหมดงานของฉัน) — นับ Layout ตาม status_class จาก workLayoutSummary() */
    private function zoneWorkSummary(Collection $layouts, Collection $summaryByLayout, array $zoneMap): array
    {
        $byZone = [];
        foreach ($layouts as $layout) {
            $z = $zoneMap[$layout->id] ?? null;
            if (! $z) {
                continue;
            }
            $zoneId = $z['zone_id'];
            $byZone[$zoneId] ??= ['zone_id' => $zoneId, 'zone_name' => $z['zone_name'], 'total' => 0, 'pass' => 0, 'fail' => 0, 'pending' => 0];
            $byZone[$zoneId]['total']++;
            $class = $summaryByLayout[$layout->id]['status_class'] ?? 'idle';
            if ($class === 'pass') {
                $byZone[$zoneId]['pass']++;
            } elseif ($class === 'fail') {
                $byZone[$zoneId]['fail']++;
            } elseif (in_array($class, ['pending', 'draft'], true)) {
                $byZone[$zoneId]['pending']++;
            }
        }

        return array_values($byZone);
    }

    /** สรุปต่อโซน (โหมดตรวจประเมิน) — รวม waiting/passed/rejected จาก reviewLayoutSummaries() ของทุก Layout ในโซน */
    private function zoneReviewSummary(Collection $layouts, Collection $summaryByLayout, array $zoneMap): array
    {
        $byZone = [];
        foreach ($layouts as $layout) {
            $z = $zoneMap[$layout->id] ?? null;
            if (! $z) {
                continue;
            }
            $zoneId = $z['zone_id'];
            $byZone[$zoneId] ??= ['zone_id' => $zoneId, 'zone_name' => $z['zone_name'], 'total' => 0, 'waiting' => 0, 'passed' => 0, 'rejected' => 0];
            $s = $summaryByLayout[$layout->id] ?? ['waiting' => 0, 'passed' => 0, 'rejected' => 0];
            $byZone[$zoneId]['total']++;
            $byZone[$zoneId]['waiting'] += (int) ($s['waiting'] ?? 0);
            $byZone[$zoneId]['passed'] += (int) ($s['passed'] ?? 0);
            $byZone[$zoneId]['rejected'] += (int) ($s['rejected'] ?? 0);
        }

        return array_values($byZone);
    }

    private function layoutZone(A5sLayout $layout): ?A5sZone
    {
        return $layout->floor?->area?->zoneMap?->zone ?? $layout->floor?->zone;
    }

    /**
     * บอร์ดโซนย่อย/อาคาร สำหรับ modal บนหน้า my-work
     * โครงมาจาก "แปลนจริง" (โซน → ภาพโซนย่อย → อาคารทุกอันที่ active) แล้วค่อยแปะงานของผู้ใช้ลงอาคารที่ตรงกัน
     * — อาคารที่ผู้ใช้ยังไม่มีงานก็ต้องขึ้นในรายการ (Manager 2026-07-21) ไม่ใช่สร้างจาก layout ของผู้ใช้อย่างเดียว
     */
    private function subZoneBoardsForLayouts(Collection $layouts, Collection $summaryByLayout, bool $reviewMode, ?Collection $cardsByLayout = null, ?A5sCompanyPlan $plan = null): array
    {
        // 1) แถวงานของผู้ใช้ จัดกลุ่มตามอาคาร (zone_map_area)
        $rowsByArea = [];
        foreach ($layouts as $layout) {
            $layout->loadMissing('floor.area');
            $floor = $layout->floor;
            $area = $floor?->area;
            $points = collect($summaryByLayout->get($layout->id)['points'] ?? [])->values();

            if (! $floor?->is_active || ! $area?->is_active || $points->isEmpty()) {
                continue;
            }

            $summary = $summaryByLayout->get($layout->id) ?? [];
            $rowsByArea[(int) $area->id][] = [
                'id' => $layout->id,
                'name' => $layout->name,
                'image_path' => $layout->image_path,
                'floor_name' => $floor->name,
                'url' => $reviewMode
                    ? route('area5s.evaluations.show', $layout)
                    : route('area5s.responsible.show', $layout),
                'point_count' => $points->count(),
                'total_point_count' => (int) $layout->points_count,
                'card_count' => (int) ($cardsByLayout?->get($layout->id) ?? 0),
                'status_label' => $summary['status_label'] ?? null,
                'status_class' => $summary['status_class'] ?? null,
                'waiting' => $summary['waiting'] ?? null,
                'passed' => $summary['passed'] ?? null,
                'rejected' => $summary['rejected'] ?? null,
                'points' => $points->all(),
            ];
        }

        // 2) ไล่โครงจากแปลนจริง — ทุกอาคารในโซนขึ้นครบ แม้ยังไม่มีงานของผู้ใช้
        $boards = [];
        foreach (($plan?->zones ?? collect()) as $zone) {
            foreach (($zone->zoneMaps ?? collect()) as $zoneMap) {
                if (blank($zoneMap->image_path)) {
                    continue;
                }

                $areas = [];
                $pointTotal = 0;
                $layoutTotal = 0;
                foreach (($zoneMap->areas ?? collect()) as $area) {
                    $rows = collect($rowsByArea[(int) $area->id] ?? [])->sortBy('name')->values()->all();
                    $areaPoints = array_sum(array_column($rows, 'point_count'));
                    $pointTotal += $areaPoints;
                    $layoutTotal += count($rows);

                    $areas[] = [
                        'id' => (int) $area->id,
                        'name' => $area->name,
                        'shape_points' => $area->shape_points ?: [],
                        'color' => $area->color ?: '#5b7343',
                        'point_count' => $areaPoints,
                        'layouts' => $rows,
                    ];
                }

                if (! $areas) {
                    continue;
                }

                $boards[] = [
                    'id' => (int) $zoneMap->id,
                    'name' => $zoneMap->name,
                    'zone_id' => (int) $zone->id,
                    'zone_name' => $zone->name,
                    'image_path' => $zoneMap->image_path,
                    'point_count' => $pointTotal,
                    'layout_count' => $layoutTotal,
                    'areas' => $areas,
                ];
            }
        }

        return $boards;
    }

    public function show(A5sLayout $layout, Request $request): ViewContract|RedirectResponse
    {
        if ($redirect = $this->gateResponsible()) {
            return $redirect;
        }

        $myCode = $this->myCode();
        $points = $layout->points()->where('is_active', true)->get();
        $myPointIds = $this->myPointIds($myCode)->intersect($points->pluck('id'))->values();

        // ทำงานได้เฉพาะ layout ของรอบที่เปิดอยู่ (เดือนอื่นดูผ่านภาพรวมแบบ read-only)
        if (! $layout->inOpenRound()) {
            return redirect()
                ->route('area5s.responsible.index')
                ->with('error', 'พื้นที่นี้ไม่อยู่ในรอบเดือนที่เปิดอยู่');
        }

        if (! $layout->is_active || $myPointIds->isEmpty()) {
            return redirect()
                ->route('area5s.responsible.index')
                ->with('error', 'คุณยังไม่ได้รับผิดชอบจุดในพื้นที่นี้');
        }

        $cardsByPoint = $this->cardsByPoint($points->pluck('id'));
        $taskByPoint = $this->tasksByPoint($points->pluck('id'));
        $pointsPayload = $points->map(function (A5sPoint $point) use ($myPointIds, $cardsByPoint, $taskByPoint) {
            $task = $taskByPoint->get($point->id);
            $history = $this->attemptHistory($task);
            $score = $this->historyScore($history);

            return [
                'id' => $point->id,
                'code' => $point->code,
                'name' => $point->name,
                'x' => (float) $point->x,
                'y' => (float) $point->y,
                'is_mine' => $myPointIds->contains($point->id),
                'cards' => $cardsByPoint->get($point->id, collect())->values()->all(),
                // สถานะงานเดือนนี้ของจุด (D5: งานเดียวร่วมกันต่อจุด)
                'status' => $task?->status ?? 'not_started',
                'status_label' => self::STATUS_LABELS[$task?->status ?? 'not_started'] ?? 'ยังไม่ดำเนินการ',
                'submit_count' => (int) ($task?->submit_count ?? 0),
                'fail_reason' => $task?->fail_reason,
                'advice' => $task?->advice,
                'locked' => ($task?->status ?? '') === 'passed',
                // เวลาล่าสุดของจุด — แสดงเป็นแถว "อัปเดตล่าสุด" ในพาเนลขวา (Manager 2026-08-27)
                'updated_at' => $this->a5sBeDateTime($task?->submitted_at ?? $task?->updated_at),
                'submit_url' => route('area5s.responsible.submit', $point),
                // ประวัติการส่งครั้งก่อน (ไม่มีภาพ) — บอกผู้รับผิดชอบว่าครั้งก่อนไม่ผ่านเพราะอะไร (Manager 2026-07-24)
                'history' => $history,
                'score' => $score,
                'score_label' => A5sScoreService::label($score),
            ];
        })->values();

        // ปุ่ม "กลับ" ย้อนไปหน้างานพื้นที่ของฉัน พร้อมคง อาคาร/เดือน/วันที่ตรวจ เดิมไว้
        // (Manager 2026-08-27 — เดิมย้อนไปหน้ารวมของอาคาร `/my-work/areas/{id}` ตั้งแต่ 2026-07-22
        //  แต่หลังรื้อเป็น workspace ผู้ใช้เข้ามาจาก `/my-work` ตรง ๆ กดกลับจึงควรกลับที่เดิม)
        return view('area5s.responsible.show', [
            'me' => $this->me(),
            'layout' => $layout,
            'points' => $pointsPayload,
            // ประวัติแบบละเอียดสำหรับ modal ปุ่ม "ประวัติ" — ชุดเดียวกับหน้า workspace (Manager 2026-07-31)
            'historyDetails' => $this->pointHistoryDetails($layout, $pointsPayload->pluck('id')),
            'myPointCount' => $myPointIds->count(),
            'restorePoint' => (int) ($request->query('point') ?: session('responsible_point_id')),
            'backUrl' => $this->workspaceBackUrl($layout),
        ]);
    }

    public function evaluate(A5sLayout $layout, Request $request): ViewContract|RedirectResponse
    {
        if ($redirect = $this->gateEvaluator()) {
            return $redirect;
        }

        $myCode = $this->myCode();
        $points = $layout->points()->where('is_active', true)->get();
        $reviewPointIds = $this->reviewPointIdsForLayout($layout, $myCode)->intersect($points->pluck('id'))->values();

        if (! $layout->inOpenRound()) {
            return redirect()
                ->route('area5s.responsible.index', ['mode' => 'review'])
                ->with('error', 'พื้นที่นี้ไม่อยู่ในรอบเดือนที่เปิดอยู่');
        }

        if (! $layout->is_active || $reviewPointIds->isEmpty()) {
            return redirect()
                ->route('area5s.responsible.index', ['mode' => 'review'])
                ->with('error', 'คุณยังไม่ได้รับมอบหมายให้ตรวจประเมินพื้นที่นี้');
        }

        // โหลดรายละเอียดงานเฉพาะจุดใน scope ของผู้ประเมิน ป้องกันข้อมูล task ของจุดอื่นติดไปกับ payload หน้าเว็บ
        $cardsByPoint = $this->cardsByPoint($reviewPointIds);
        $taskByPoint = $this->tasksByPoint($reviewPointIds);
        $round = A5sRound::open();
        $pointsPayload = $points->map(function (A5sPoint $point) use ($reviewPointIds, $cardsByPoint, $taskByPoint, $round) {
            $isReviewable = $reviewPointIds->contains($point->id);
            $task = $isReviewable ? $taskByPoint->get($point->id) : null;
            $history = $this->attemptHistory($task);
            $score = $this->historyScore($history);
            $status = $isReviewable ? ($task?->status ?? 'not_started') : 'not_started';
            $cards = $isReviewable ? $cardsByPoint->get($point->id, collect())->values() : collect();
            $hasSubmission = $task
                && $isReviewable
                && in_array($status, ['submitted', 'resubmitted', 'passed', 'failed'], true)
                && $cards->isNotEmpty();

            return [
                'id' => $point->id,
                'code' => $point->code,
                'name' => $point->name,
                'x' => (float) $point->x,
                'y' => (float) $point->y,
                'is_reviewable' => $isReviewable,
                'has_submission' => $hasSubmission,
                'cards' => $hasSubmission ? $cards->all() : [],
                'status' => $status,
                'status_label' => self::STATUS_LABELS[$status] ?? 'ยังไม่ดำเนินการ',
                'review_status_label' => $isReviewable ? $this->publicStatusLabel($task) : null,
                'submit_count' => (int) ($task?->submit_count ?? 0),
                'submitted_at' => $this->a5sBeDateTime($task?->submitted_at),
                'fail_reason' => $task?->fail_reason,
                'advice' => $task?->advice,
                // แก้ไขผลได้แม้ตัดสินไปแล้ว (ผ่าน/ปฏิเสธ) ตราบใดที่รอบยังเปิด (Manager 2026-07-23)
                'can_decide' => $hasSubmission
                    && $task
                    && $isReviewable
                    && in_array($status, ['submitted', 'resubmitted', 'passed', 'failed'], true)
                    && $round?->isOpen(),
                'decision_url' => $isReviewable && $task ? route('area5s.review.decide', $task) : null,
                // ประวัติการส่ง→ตรวจครั้งก่อน (ไม่เก็บภาพ) — โชว์ให้ผู้ตรวจ/ผู้รับผิดชอบเห็น (Manager 2026-07-24)
                'history' => $history,
                'score' => $score,
                'score_label' => A5sScoreService::label($score),
            ];
        })->values();

        // ปุ่ม "กลับ" ย้อนไปหน้าตรวจประเมิน พร้อมคง อาคาร/เดือน/วันที่ตรวจ เดิมไว้ (Manager 2026-08-27)
        return view('area5s.responsible.evaluate', [
            'me' => $this->me(),
            'layout' => $layout,
            'points' => $pointsPayload,
            // ประวัติแบบละเอียด (ผู้ส่ง/ผู้ประเมิน/รายการที่ส่ง/หมายเหตุ) สำหรับ modal ปุ่ม "ประวัติ"
            // ใช้ payload ชุดเดียวกับหน้า workspace เพื่อให้หน้าตาเหมือนกัน (Manager 2026-07-31)
            'historyDetails' => $this->pointHistoryDetails($layout, $pointsPayload->pluck('id')),
            'reviewPointCount' => $reviewPointIds->count(),
            'restorePoint' => (int) ($request->query('point') ?: session('evaluation_point_id')),
            'backUrl' => $this->workspaceBackUrl($layout, reviewMode: true),
        ]);
    }

    /** สถานะงาน → ป้ายไทย (overdue เป็น badge คำนวณสด ไม่ใช่สถานะ) */
    public const STATUS_LABELS = [
        'not_started' => 'ยังไม่ดำเนินการ',
        'draft' => 'ฉบับร่าง',
        'submitted' => 'รอดำเนินการ',
        'failed' => 'ปฏิเสธ',
        'resubmitted' => 'รอดำเนินการ',
        'passed' => 'ผ่าน',
    ];

    /** task ของรอบที่เปิดอยู่ ต่อจุด */
    private function tasksByPoint(Collection $pointIds, ?int $roundId = null): Collection
    {
        $roundId ??= (int) (A5sRound::open()?->id ?? 0);
        if (! $roundId) {
            return collect();
        }

        return A5sTask::where('round_id', $roundId)->whereIn('point_id', $pointIds)->get()->keyBy('point_id');
    }

    /**
     * ประวัติการส่ง→ตรวจต่อครั้งของ task (append-only) สำหรับแสดงผล — เก็บหัวข้อ+รายละเอียด ไม่มีภาพ
     * ใช้ทั้งหน้าตรวจประเมิน และงานพื้นที่ของฉัน เพื่อบอกว่าครั้งก่อนส่งอะไร ผู้ตรวจให้เหตุผลอะไร (Manager 2026-07-24)
     */
    private function attemptHistory(?A5sTask $task): array
    {
        if (! $task) {
            return [];
        }

        return $task->attempts()->orderBy('seq')->get()->map(fn (A5sTaskAttempt $a) => [
            'seq' => (int) $a->seq,
            'result' => $a->result,
            'result_key' => $a->result === 'pass' ? 'passed' : 'failed',
            'fail_reason' => $a->fail_reason,
            'advice' => $a->advice,
            'cards' => collect($a->cards_json ?? [])->map(fn ($c) => [
                'title' => (string) ($c['title'] ?? ''),
                'detail' => (string) ($c['detail'] ?? ''),
            ])->values()->all(),
            'evaluated_at' => $this->a5sBeDateTime($a->evaluated_at),
        ])->all();
    }

    /** คะแนน % ของจุดในรอบนี้ = เฉลี่ยทุกครั้งที่ส่ง (ผ่าน100/ไม่ผ่าน0) จากประวัติที่โหลดมาแล้ว — ไม่มีประวัติ = null */
    /**
     * คะแนนของจุด = เฉลี่ยผลตรวจทุกครั้งในประวัติ (ผ่าน=100 · ไม่ผ่าน=0)
     * เช่น ส่ง 3 ครั้งได้ ผ่าน/ไม่ผ่าน/ผ่าน → (100+0+100)/3 = 66.67
     *
     * นับเฉพาะครั้งที่ **ตัดสินแล้ว** — ครั้งที่ยังรอตรวจ (`state = ready`) ไม่นับ
     * ไม่มีครั้งที่ตัดสินเลย → null แล้วหน้าจอแสดง "-"
     *
     * (แก้ 2026-08-26: เดิมอ่านคีย์ `result` ซึ่งประวัติไม่มี — ทุกครั้งจึงถูกตีเป็น 0 คะแนนออกมาเป็น 0% เสมอ)
     */
    /**
     * URL ปุ่ม "กลับ" — ย้อนไปหน้า workspace เดิมพร้อมคง อาคาร / เดือน / วันที่ตรวจ
     * ผู้ใช้จึงกลับไปเจอตารางชุดเดิมที่กดเข้ามา ไม่ใช่รอบเปิดล่าสุดเสมอ
     */
    private function workspaceBackUrl(A5sLayout $layout, bool $reviewMode = false): string
    {
        $layout->loadMissing(['floor.area', 'round']);
        $round = $layout->round;

        return route('area5s.responsible.index', array_filter([
            'mode' => $reviewMode ? 'review' : null,
            'building' => $layout->floor?->area?->id,
            'month' => $round ? $round->year.'-'.$round->month : null,
            'round' => $round?->id,
        ]));
    }

    private function historyScore(array $history): ?float
    {
        $decided = collect($history)->filter(
            fn ($entry) => in_array($entry['state'] ?? '', ['passed', 'failed'], true)
        );

        if ($decided->isEmpty()) {
            return null;
        }

        return round($decided->avg(fn ($entry) => ($entry['state'] === 'passed') ? 100 : 0), 2);
    }

    /** คะแนนรายวัน/รายเดือนของผู้รับผิดชอบ สำหรับปฏิทินในหน้า work */
    private function calendarDataForPoints(Collection $pointIds, Collection $rounds): array
    {
        if ($pointIds->isEmpty() || $rounds->isEmpty()) {
            return ['months' => [], 'overall' => null];
        }

        $rounds = $rounds->sortByDesc(fn (A5sRound $round) => sprintf(
            '%05d%02d%03d%010d',
            (int) $round->year,
            (int) $round->month,
            (int) ($round->seq ?? 1),
            (int) $round->id
        ))->values();
        $roundById = $rounds->keyBy('id');
        $tasks = A5sTask::query()
            ->whereIn('point_id', $pointIds)
            ->whereIn('round_id', $rounds->pluck('id'))
            ->with(['attempts:id,task_id,seq,result,evaluated_at,submitted_at'])
            ->get(['id', 'point_id', 'round_id']);
        $entries = $tasks->flatMap(function (A5sTask $task) use ($roundById) {
            $round = $roundById->get($task->round_id);
            if (! $round) {
                return [];
            }

            return $task->attempts
                ->filter(fn (A5sTaskAttempt $attempt) => $attempt->evaluated_at && in_array($attempt->result, ['pass', 'fail'], true))
                ->map(function (A5sTaskAttempt $attempt) use ($task, $round) {
                    $date = $attempt->evaluated_at;
                    $dateKey = $date->format('Y-m-d');

                    return [
                        'point_id' => (int) $task->point_id,
                        'round_id' => (int) $round->id,
                        'month_key' => $round->year.'-'.$round->month,
                        'date_key' => $dateKey,
                        'date_label' => $date->format('d/m/').($date->year + 543),
                        'score' => $attempt->result === 'pass' ? 100 : 0,
                    ];
                });
        })->values();

        $averagePointScore = fn (Collection $items): ?float => $items->isEmpty()
            ? null
            : round((float) $items
                ->groupBy('point_id')
                ->map(fn (Collection $pointEntries) => (float) $pointEntries->avg('score'))
                ->avg(), 2);
        $dateRows = fn (Collection $items) => $items
            ->groupBy('date_key')
            ->map(function (Collection $dateEntries, string $dateKey) use ($averagePointScore) {
                return [
                    'date_key' => $dateKey,
                    'date_label' => (string) $dateEntries->first()['date_label'],
                    'score' => $averagePointScore($dateEntries),
                    'evaluated_count' => $dateEntries->count(),
                ];
            })
            ->sortKeysDesc()
            ->values()
            ->all();

        $months = $rounds
            ->groupBy(fn (A5sRound $round) => $round->year.'-'.$round->month)
            ->map(function (Collection $monthRounds, string $monthKey) use ($entries, $dateRows, $averagePointScore) {
                $monthEntries = $entries->where('month_key', $monthKey)->values();
                $roundRows = $monthRounds->map(function (A5sRound $round) use ($monthEntries, $averagePointScore) {
                    $roundEntries = $monthEntries->where('round_id', (int) $round->id);
                    $inspectionDate = $round->inspected_on ?? $round->opened_at;

                    return [
                        'round_id' => (int) $round->id,
                        'seq' => (int) ($round->seq ?? 1),
                        'date_label' => $inspectionDate
                            ? $inspectionDate->format('d/m/').($inspectionDate->year + 543)
                            : '-',
                        'is_open' => $round->status === 'open',
                        'score' => $averagePointScore($roundEntries),
                    ];
                })->values()->all();
                $roundScores = collect($roundRows)->pluck('score')->filter(fn ($score) => $score !== null);

                return [
                    'key' => $monthKey,
                    'label' => A5sRoundService::monthLabel((int) $monthRounds->first()->month).' '.$monthRounds->first()->year,
                    'score' => $roundScores->isEmpty() ? null : round((float) $roundScores->avg(), 2),
                    'rounds' => $roundRows,
                    'dates' => $dateRows($monthEntries),
                ];
            })
            ->values()
            ->all();
        $openRound = $rounds->first(fn (A5sRound $round) => $round->status === 'open') ?? $rounds->first();
        $openDate = $openRound?->inspected_on ?? $openRound?->opened_at;
        $openRoundEntries = $openRound
            ? $entries->where('round_id', (int) $openRound->id)
            : collect();

        return [
            'months' => $months,
            'overall' => [
                'month_label' => $openRound
                    ? A5sRoundService::monthLabel((int) $openRound->month).' '.$openRound->year
                    : '-',
                'date_label' => $openDate
                    ? $openDate->format('d/m/').($openDate->year + 543)
                    : '-',
                'score' => $averagePointScore($openRoundEntries),
            ],
        ];
    }

    private function workLayoutSummary(A5sLayout $layout, Collection $pointIds, Collection $taskByPoint): array
    {
        $statuses = $pointIds->map(fn ($pointId) => $taskByPoint->get($pointId)?->status ?? 'not_started');
        $latestEvaluatedAt = $pointIds
            ->map(fn ($pointId) => $taskByPoint->get($pointId)?->evaluated_at)
            ->filter()
            ->sortByDesc(fn ($date) => $date->timestamp)
            ->first();
        $latestRejectedTask = $pointIds
            ->map(fn ($pointId) => $taskByPoint->get($pointId))
            ->filter(fn ($task) => $task?->status === 'failed' && filled($task->fail_reason))
            ->sortByDesc(fn ($task) => $task->evaluated_at?->timestamp ?? $task->updated_at?->timestamp ?? 0)
            ->first();
        $status = $this->workStatusSummary($statuses, $pointIds->count());

        return [
            'point_count' => $pointIds->count(),
            'status_label' => $status['label'],
            'status_class' => $status['class'],
            'evaluator_names' => $this->evaluatorNamesForPointIds($layout, $pointIds),
            'evaluated_at' => $this->a5sBeDateTime($latestEvaluatedAt) ?? '-',
            'reject_note' => $latestRejectedTask?->fail_reason,
            'reject_point' => $latestRejectedTask ? trim(($latestRejectedTask->point_name ?: '').' จุด '.$latestRejectedTask->point_code) : null,
            'points' => $this->pointStatusRows($pointIds, $taskByPoint),
        ];
    }

    private function pointStatusRows(Collection $pointIds, Collection $taskByPoint, bool $forReview = false): array
    {
        $ids = $pointIds
            ->map(fn ($pointId) => (int) $pointId)
            ->filter()
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return [];
        }

        return A5sPoint::whereIn('id', $ids)
            ->orderBy('sort')
            ->orderBy('code')
            ->get()
            ->map(fn (A5sPoint $point) => $this->pointStatusRow($point, $taskByPoint->get($point->id), $forReview))
            ->values()
            ->all();
    }

    /**
     * รายการงานแบบแบน "พื้นที่ → จุด → สถานะ" เรียงตามความเร่งด่วน
     * (ปฏิเสธ → รอตรวจ → ยังไม่ส่ง → ผ่าน) เพื่อให้ผู้ใช้เห็นว่าต้องทำอะไรต่อทันที (Manager 2026-07-31)
     */
    private function flatTaskRows(Collection $layouts, Collection $summaryByLayout, callable $urlOf): array
    {
        $order = ['fail' => 0, 'pending' => 1, 'draft' => 2, 'none' => 3, 'idle' => 3, 'pass' => 4];

        return $layouts
            ->flatMap(function (A5sLayout $layout) use ($summaryByLayout, $urlOf) {
                $summary = $summaryByLayout[$layout->id] ?? [];

                return collect($summary['points'] ?? [])->map(fn (array $point) => [
                    'layout_name' => $layout->name,
                    'floor_name' => $layout->floor?->name,
                    'area_name' => $layout->floor?->area?->name,
                    'url' => $urlOf($layout),
                    'code' => $point['code'] ?? '-',
                    'name' => $point['name'] ?? '-',
                    'status' => $point['status'] ?? 'not_started',
                    'status_class' => $point['status_class'] ?? 'none',
                    'status_label' => $point['status_label'] ?? '-',
                    'note' => $point['reject_note'] ?: ($point['pass_note'] ?: null),
                    'note_kind' => $point['reject_note'] ? 'fail' : ($point['pass_note'] ? 'pass' : null),
                ]);
            })
            ->sortBy(fn (array $row) => sprintf('%d|%s|%s', $order[$row['status_class']] ?? 9, $row['layout_name'], $row['code']))
            ->values()
            ->all();
    }

    private function pointStatusRow(A5sPoint $point, ?A5sTask $task, bool $forReview = false): array
    {
        $status = $task?->status ?? 'not_started';

        return [
            'id' => $point->id,
            'code' => $point->code,
            'name' => $point->name,
            'status' => $status,
            'status_label' => $this->pointListStatusLabel($status, $forReview),
            'status_class' => $this->pointListStatusClass($status, $forReview),
            'reject_note' => $status === 'failed' ? $task?->fail_reason : null,
            // หมายเหตุจากผู้ประเมินตอนให้ผ่าน — กดอ่านในตาราง Note ได้ (Manager 2026-07-18)
            'pass_note' => $status === 'passed' ? $task?->advice : null,
            'reject_point' => trim(($point->name ?: '').' จุด '.$point->code),
        ];
    }

    /** จุดที่ยังไม่ถูกส่งตรวจ (ไม่มี task / not_started / draft) = "ยังไม่มีข้อมูล" ทั้งฝั่งงานของฉันและฝั่งตรวจ (Manager 2026-07-18) */
    private function pointListStatusLabel(string $status, bool $forReview = false): string
    {
        if (! in_array($status, ['submitted', 'resubmitted', 'passed', 'failed'], true)) {
            return 'ยังไม่มีข้อมูล';
        }

        return match ($status) {
            'passed' => 'ผ่าน',
            'failed' => 'ปฏิเสธ',
            default => 'รอการดำเนินการ',
        };
    }

    private function pointListStatusClass(string $status, bool $forReview = false): string
    {
        if (! in_array($status, ['submitted', 'resubmitted', 'passed', 'failed'], true)) {
            return 'none';
        }

        return match ($status) {
            'passed' => 'pass',
            'failed' => 'fail',
            default => 'pending',
        };
    }

    private function workStatusSummary(Collection $statuses, int $pointCount): array
    {
        if ($pointCount === 0) {
            return ['label' => '-', 'class' => 'idle'];
        }

        if ($statuses->contains('failed')) {
            return ['label' => 'ปฏิเสธ', 'class' => 'fail'];
        }

        if ($statuses->every(fn ($status) => $status === 'passed')) {
            return ['label' => 'ผ่าน', 'class' => 'pass'];
        }

        if ($statuses->contains(fn ($status) => in_array($status, ['submitted', 'resubmitted'], true))) {
            return ['label' => 'รอดำเนินการ', 'class' => 'pending'];
        }

        if ($statuses->contains('draft')) {
            return ['label' => 'กำลังดำเนินการ', 'class' => 'draft'];
        }

        return ['label' => 'รอดำเนินการ', 'class' => 'idle'];
    }

    private function evaluatorNamesForPointIds(A5sLayout $layout, Collection $pointIds): string
    {
        if ($pointIds->isEmpty()) {
            return '-';
        }

        $scopes = A5sEvaluatorScope::where('layout_id', $layout->id)
            ->where(fn ($query) => $query->whereNull('point_id')->orWhereIn('point_id', $pointIds))
            ->get()
            ->unique('employee_code');
        if ($scopes->isEmpty()) {
            return '-';
        }

        $employees = Employee::active()
            ->whereIn('employee_code', $scopes->pluck('employee_code'))
            ->get()
            ->keyBy('employee_code');
        $names = $scopes->map(function (A5sEvaluatorScope $scope) use ($employees) {
            $employee = $employees->get($scope->employee_code);

            $person = $this->a5sEmployeePayload($employee, $scope->employee_code);

            return $person['name'];
        })->values();
        $display = $names->take(3)->implode(', ');

        return $names->count() > 3 ? $display.' +'.($names->count() - 3) : $display;
    }

    /** สรุป Layout ที่ต้องตรวจแบบ batch เพื่อไม่ query ซ้ำราย Layout */
    private function reviewLayoutSummaries(Collection $layouts, string $employeeCode, ?int $roundId = null): Collection
    {
        $layoutIds = $layouts
            ->pluck('id')
            ->map(fn ($layoutId) => (int) $layoutId)
            ->filter()
            ->unique()
            ->values();
        if ($layoutIds->isEmpty()) {
            return collect();
        }

        $activePointsByLayout = A5sPoint::query()
            ->whereIn('layout_id', $layoutIds)
            ->where('is_active', true)
            ->orderBy('layout_id')
            ->orderBy('sort')
            ->orderBy('code')
            ->get()
            ->groupBy('layout_id');
        $scopePointIdsByLayout = collect();

        if ($this->isA5sAdmin()) {
            $scopePointIdsByLayout = $layoutIds->mapWithKeys(fn ($layoutId) => [
                $layoutId => $activePointsByLayout->get($layoutId, collect())->pluck('id')->values(),
            ]);
        } elseif ($employeeCode !== '') {
            $scopesByLayout = A5sEvaluatorScope::query()
                ->where('employee_code', $employeeCode)
                ->whereIn('layout_id', $layoutIds)
                ->get(['layout_id', 'point_id'])
                ->groupBy('layout_id');
            $scopePointIdsByLayout = $layoutIds->mapWithKeys(function ($layoutId) use ($activePointsByLayout, $scopesByLayout) {
                $activePointIds = $activePointsByLayout->get($layoutId, collect())->pluck('id')->values();
                $scopes = $scopesByLayout->get($layoutId, collect());
                $pointIds = $scopes->contains(fn (A5sEvaluatorScope $scope) => $scope->point_id === null)
                    ? $activePointIds
                    : $scopes->pluck('point_id')->filter()->intersect($activePointIds)->unique()->values();

                return [$layoutId => $pointIds];
            });
        }

        $allPointIds = $scopePointIdsByLayout->flatten()->unique()->values();
        $taskByPoint = $this->tasksByPoint($allPointIds, $roundId);

        return $layoutIds->mapWithKeys(function ($layoutId) use ($activePointsByLayout, $scopePointIdsByLayout, $taskByPoint) {
            $pointIds = $scopePointIdsByLayout->get($layoutId, collect());
            $pointIdSet = $pointIds->mapWithKeys(fn ($pointId) => [(int) $pointId => true]);
            $pointRows = $activePointsByLayout
                ->get($layoutId, collect())
                ->filter(fn (A5sPoint $point) => isset($pointIdSet[(int) $point->id]))
                ->map(fn (A5sPoint $point) => $this->pointStatusRow($point, $taskByPoint->get($point->id), forReview: true))
                ->values()
                ->all();
            $waiting = $pointIds->filter(fn ($pointId) => in_array($taskByPoint->get($pointId)?->status, ['submitted', 'resubmitted'], true))->count();
            $passed = $pointIds->filter(fn ($pointId) => $taskByPoint->get($pointId)?->status === 'passed')->count();
            $rejected = $pointIds->filter(fn ($pointId) => $taskByPoint->get($pointId)?->status === 'failed')->count();

            return [$layoutId => [
                'total' => $pointIds->count(),
                'waiting' => $waiting,
                'passed' => $passed,
                'rejected' => $rejected,
                'no_data' => max(0, $pointIds->count() - $waiting - $passed - $rejected),
                'points' => $pointRows,
            ]];
        });
    }

    /**
     * ข้อมูลสำหรับหน้าเข้าตรวจประเมินแบบ batch: ผู้รับผิดชอบ ผู้ส่งล่าสุด
     * และประวัติการส่ง/ผลประเมินรายครั้ง โดยจำกัดเฉพาะจุดที่อยู่ใน scope ผู้ประเมินแล้ว
     */
    /**
     * ประวัติรายจุดแบบละเอียดของ Layout เดียว (หน้าบันทึกงาน / หน้าตรวจ)
     * ใช้ payload ตัวเดียวกับหน้า workspace เพื่อให้ modal ประวัติหน้าตาเหมือนกันทุกหน้า
     */
    private function pointHistoryDetails(A5sLayout $layout, Collection $pointIds): Collection
    {
        $summary = collect([
            (int) $layout->id => [
                'points' => $pointIds->map(fn ($pointId) => ['id' => (int) $pointId])->values()->all(),
            ],
        ]);

        return $this->entryPointDetails($summary, (int) $layout->round_id);
    }

    private function entryPointDetails(Collection $summaryByLayout, ?int $roundId = null): Collection
    {
        $pointIds = $summaryByLayout
            ->flatMap(fn (array $summary) => collect($summary['points'] ?? [])->pluck('id'))
            ->map(fn ($pointId) => (int) $pointId)
            ->filter()
            ->unique()
            ->values();
        $roundId ??= (int) (A5sRound::open()?->id ?? 0);

        if ($pointIds->isEmpty() || ! $roundId) {
            return collect();
        }

        $tasksByPoint = A5sTask::query()
            ->where('round_id', $roundId)
            ->whereIn('point_id', $pointIds)
            ->with([
                'attempts',
                'cards' => fn ($query) => $query
                    ->select(['id', 'task_id', 'title', 'detail', 'sort'])
                    ->orderBy('sort')
                    ->orderBy('id'),
            ])
            ->get()
            ->keyBy('point_id');

        $pointLayouts = A5sPoint::query()
            ->whereIn('id', $pointIds)
            ->get(['id', 'layout_id'])
            ->keyBy('id');
        $layoutIds = $pointLayouts->pluck('layout_id')->map(fn ($layoutId) => (int) $layoutId)->unique()->values();
        $assignments = A5sPointAssignee::query()
            ->whereIn('point_id', $pointIds)
            ->whereNull('removed_at')
            ->get(['point_id', 'employee_code']);
        $evaluatorScopes = A5sEvaluatorScope::query()
            ->whereIn('layout_id', $layoutIds)
            ->where(fn ($query) => $query->whereNull('point_id')->orWhereIn('point_id', $pointIds))
            ->get(['layout_id', 'point_id', 'employee_code']);
        $employees = Employee::query()
            ->whereIn(
                'employee_code',
                $assignments->pluck('employee_code')
                    ->merge($evaluatorScopes->pluck('employee_code'))
                    ->filter()
                    ->unique()
            )
            ->get()
            ->keyBy('employee_code');
        $snapshotEmployeeCodes = $tasksByPoint
            ->flatMap(fn (A5sTask $task) => collect($task->assignees_json ?? [])
                ->merge($task->evaluators_json ?? [])
                ->pluck('code'));
        $personEmployeeCodes = $assignments->pluck('employee_code')
            ->merge($evaluatorScopes->pluck('employee_code'))
            ->merge($snapshotEmployeeCodes)
            ->map(fn ($employeeCode) => trim((string) $employeeCode))
            ->filter()
            ->unique()
            ->values();
        $avatarsByEmployeeCode = $personEmployeeCodes->isEmpty()
            ? collect()
            : AppUser::query()
                ->whereIn('employee_code', $personEmployeeCodes)
                ->pluck('profile_picture', 'employee_code')
                ->map(fn ($profilePicture) => $profilePicture ? asset('storage/'.$profilePicture) : null);
        $currentAssigneesByPoint = $assignments
            ->groupBy('point_id')
            ->map(fn (Collection $rows) => $rows
                ->map(fn (A5sPointAssignee $row) => array_merge(
                    $this->a5sEmployeePayload(
                        $employees->get($row->employee_code),
                        (string) $row->employee_code
                    ),
                    ['avatar' => $avatarsByEmployeeCode->get((string) $row->employee_code)]
                ))
                ->values()
                ->all());
        $scopesByLayout = $evaluatorScopes->groupBy('layout_id');
        $currentEvaluatorsByPoint = $pointIds->mapWithKeys(function (int $pointId) use (
            $pointLayouts,
            $scopesByLayout,
            $employees,
            $avatarsByEmployeeCode
        ) {
            $layoutId = (int) ($pointLayouts->get($pointId)?->layout_id ?? 0);
            $evaluators = $scopesByLayout
                ->get($layoutId, collect())
                ->filter(fn (A5sEvaluatorScope $scope) => $scope->point_id === null || (int) $scope->point_id === $pointId)
                ->unique('employee_code')
                ->map(fn (A5sEvaluatorScope $scope) => array_merge(
                    $this->a5sEmployeePayload(
                        $employees->get($scope->employee_code),
                        (string) $scope->employee_code
                    ),
                    ['avatar' => $avatarsByEmployeeCode->get((string) $scope->employee_code)]
                ))
                ->values()
                ->all();

            return [$pointId => $evaluators];
        });

        $taskIds = $tasksByPoint->pluck('id')->map(fn ($taskId) => (int) $taskId)->filter()->values();
        $submitLogs = $taskIds->isEmpty()
            ? collect()
            : A5sActivityLog::query()
                ->where('subject_type', 'task')
                ->whereIn('subject_id', $taskIds)
                ->whereIn('action', ['task.submit', 'task.resubmit'])
                ->orderBy('created_at')
                ->orderBy('id')
                ->get(['id', 'user_id', 'subject_id', 'detail_json', 'created_at']);
        $logsByTask = $submitLogs->groupBy('subject_id');
        $userIds = $submitLogs
            ->pluck('user_id')
            ->merge($tasksByPoint->flatMap(fn (A5sTask $task) => $task->attempts->pluck('evaluated_by')))
            ->map(fn ($userId) => (int) $userId)
            ->filter()
            ->unique()
            ->values();
        $usersById = $userIds->isEmpty()
            ? collect()
            : AppUser::query()->whereIn('id', $userIds)->get()->keyBy('id');

        $personFromLog = function (?A5sActivityLog $log, array $fallback = []) use ($usersById, $avatarsByEmployeeCode): ?array {
            $snapshot = (array) data_get($log?->detail_json, 'submitter', []);
            if ($snapshot) {
                $snapshot['avatar'] = $snapshot['avatar']
                    ?? $avatarsByEmployeeCode->get(trim((string) ($snapshot['code'] ?? '')));

                return $snapshot;
            }

            $user = $log ? $usersById->get((int) $log->user_id) : null;
            if ($user) {
                return array_merge(
                    $this->a5sAppUserPayload($user, (string) $user->employee_code),
                    ['avatar' => $user->profile_picture ? asset('storage/'.$user->profile_picture) : null]
                );
            }

            return $fallback ?: null;
        };

        return $pointIds->mapWithKeys(function (int $pointId) use (
            $tasksByPoint,
            $currentAssigneesByPoint,
            $currentEvaluatorsByPoint,
            $logsByTask,
            $usersById,
            $personFromLog,
            $avatarsByEmployeeCode
        ) {
            /** @var A5sTask|null $task */
            $task = $tasksByPoint->get($pointId);
            $status = (string) ($task?->status ?? 'not_started');
            $bucket = match ($status) {
                'submitted', 'resubmitted' => 'ready',
                'failed' => 'rework',
                'passed' => 'done',
                default => 'waiting',
            };
            $taskAssignees = collect($task?->assignees_json ?? [])
                ->filter(fn ($person) => is_array($person))
                ->map(function (array $person) use ($avatarsByEmployeeCode) {
                    $person['avatar'] = $person['avatar']
                        ?? $avatarsByEmployeeCode->get(trim((string) ($person['code'] ?? '')));

                    return $person;
                })
                ->values()
                ->all();
            $assignees = $taskAssignees ?: ($currentAssigneesByPoint->get($pointId, []));
            $taskEvaluators = collect($task?->evaluators_json ?? [])
                ->filter(fn ($person) => is_array($person))
                ->map(function (array $person) use ($avatarsByEmployeeCode) {
                    $person['avatar'] = $person['avatar']
                        ?? $avatarsByEmployeeCode->get(trim((string) ($person['code'] ?? '')));

                    return $person;
                })
                ->values()
                ->all();
            $evaluators = $taskEvaluators ?: ($currentEvaluatorsByPoint->get($pointId, []));

            if (! $task) {
                return [$pointId => [
                    'task_id' => null,
                    'status' => $status,
                    'bucket' => $bucket,
                    'submitted_at' => null,
                    'updated_at' => null,
                    'submit_count' => 0,
                    'cards_count' => 0,
                    'assignees' => $assignees,
                    'evaluators' => $evaluators,
                    'latest_submitter' => null,
                    'history' => [],
                    'score' => null,
                    'score_label' => A5sScoreService::label(null),
                ]];
            }

            $taskLogs = $logsByTask->get($task->id, collect());
            $fallbackSubmitter = $assignees[0] ?? [];
            $submitLogForSeq = fn (int $seq) => $taskLogs
                ->last(fn (A5sActivityLog $log) => (int) data_get($log->detail_json, 'count', 0) === $seq);
            $history = $task->attempts->map(function (A5sTaskAttempt $attempt) use (
                $submitLogForSeq,
                $personFromLog,
                $fallbackSubmitter,
                $usersById
            ) {
                $submitLog = $submitLogForSeq((int) $attempt->seq);
                $evaluator = $usersById->get((int) $attempt->evaluated_by);

                return [
                    'seq' => (int) $attempt->seq,
                    'state' => $attempt->result === 'pass' ? 'passed' : 'failed',
                    'submitted_at' => $this->a5sBeDateTime($attempt->submitted_at)
                        ?? $this->a5sBeDateTime($submitLog?->created_at),
                    'evaluated_at' => $this->a5sBeDateTime($attempt->evaluated_at),
                    'submitter' => $personFromLog($submitLog, $fallbackSubmitter),
                    'evaluator' => $evaluator
                        ? array_merge(
                            $this->a5sAppUserPayload($evaluator, (string) $evaluator->employee_code),
                            ['avatar' => $evaluator->profile_picture ? asset('storage/'.$evaluator->profile_picture) : null]
                        )
                        : null,
                    'cards' => collect($attempt->cards_json ?? [])->map(fn ($card) => [
                        'title' => (string) ($card['title'] ?? ''),
                        'detail' => (string) ($card['detail'] ?? ''),
                    ])->values()->all(),
                    'note' => $attempt->result === 'pass' ? $attempt->advice : $attempt->fail_reason,
                ];
            });

            $currentSeq = max(1, (int) $task->submit_count);
            $currentSubmitLog = $submitLogForSeq($currentSeq) ?: $taskLogs->last();
            if (in_array($status, ['submitted', 'resubmitted'], true)
                && ! $history->contains(fn (array $item) => (int) $item['seq'] === $currentSeq)) {
                $history->push([
                    'seq' => $currentSeq,
                    'state' => 'ready',
                    'submitted_at' => $this->a5sBeDateTime($task->submitted_at)
                        ?? $this->a5sBeDateTime($currentSubmitLog?->created_at),
                    'evaluated_at' => null,
                    'submitter' => $personFromLog($currentSubmitLog, $fallbackSubmitter),
                    'evaluator' => null,
                    'cards' => $task->cards->map(fn (A5sCard $card) => [
                        'title' => (string) $card->title,
                        'detail' => (string) $card->detail,
                    ])->values()->all(),
                    'note' => null,
                ]);
            }

            return [$pointId => [
                'task_id' => (int) $task->id,
                'status' => $status,
                'bucket' => $bucket,
                'submitted_at' => $this->a5sBeDateTime($task->submitted_at),
                'submit_count' => (int) $task->submit_count,
                'cards_count' => $task->cards->count(),
                'assignees' => $assignees,
                'evaluators' => $evaluators,
                'updated_at' => $this->a5sBeDateTime($task->updated_at),
                'latest_submitter' => (int) $task->submit_count > 0
                    ? $personFromLog($currentSubmitLog, $fallbackSubmitter)
                    : null,
                'history' => $history->sortByDesc('seq')->values()->all(),
                'score' => $this->historyScore($history->all()),
                'score_label' => A5sScoreService::label($this->historyScore($history->all())),
            ]];
        });
    }

    /**
     * ส่งตรวจ / ส่งอัปเดต (D5: กดส่งซ้ำ = อัปเดต submission) — ตรวจความครบก่อน:
     * มีการ์ด >= 1, ทุกการ์ดมีรูป >= 1, มีผู้ประเมินอย่างน้อย 1 คน · refresh snapshot ผู้รับผิดชอบ/ผู้ประเมิน ณ ตอนส่ง
     */
    public function submit(A5sPoint $point): RedirectResponse
    {
        if (! $this->canWorkPoint($point)) {
            return back()->with('error', 'คุณไม่มีสิทธิ์ส่งงานจุดนี้');
        }

        if (! A5sRound::open()) {
            return back()
                ->with('error', 'ยังไม่มีการเปิดรอบเดือน — ส่งตรวจได้เมื่อแอดมินเปิดรอบ')
                ->with('responsible_point_id', $point->id);
        }

        $task = $this->taskForPoint($point);
        if ($task->status === 'passed') {
            return back()->with('error', 'งานจุดนี้ผ่านการประเมินแล้ว — ล็อกถาวรสำหรับเดือนนี้')->with('responsible_point_id', $point->id);
        }

        $cards = $task->cards()->withCount('images')->get();
        if ($cards->isEmpty()) {
            return back()->with('error', 'ต้องมีการ์ดอย่างน้อย 1 ใบก่อนส่งตรวจ')->with('responsible_point_id', $point->id);
        }
        if ($cards->contains(fn ($c) => $c->images_count === 0)) {
            return back()->with('error', 'ทุกการ์ดต้องมีรูปภาพอย่างน้อย 1 รูปก่อนส่งตรวจ')->with('responsible_point_id', $point->id);
        }

        // refresh snapshot ณ ตอนส่ง (กันเคสเพิ่มผู้ประเมินหลัง task ถูกสร้าง)
        $task->assignees_json = $this->snapshotAssignees($point);
        $task->evaluators_json = $this->snapshotEvaluators($point);
        if (empty($task->evaluators_json)) {
            $task->save();

            return back()->with('error', 'จุดนี้ยังไม่มีผู้ประเมิน — แจ้งผู้จัดสรรพื้นที่หรือแอดมินให้กำหนดผู้ประเมินก่อน')->with('responsible_point_id', $point->id);
        }

        $wasFailed = $task->status === 'failed' || $task->result === 'fail';
        $task->status = 'submitted';
        if ($wasFailed) {
            $task->result = null;
            $task->fail_reason = null;
            $task->advice = null;
            $task->evaluated_by = null;
            $task->evaluated_at = null;
        }
        $task->submit_count = (int) $task->submit_count + 1;
        $task->submitted_at = now();
        $task->save();

        // แจ้งเตือนผู้ประเมิน (in-app — แสดงผลเฟสถัดไป)
        foreach ((array) $task->evaluators_json as $ev) {
            A5sNotification::create([
                'employee_code' => $ev['code'],
                'type' => 'task.submitted',
                'title' => ($wasFailed ? 'ส่งแก้ไข' : 'ส่งตรวจ').": {$task->layout_name} จุด {$task->point_code}",
                'body' => $task->point_name,
                'link' => route('area5s.evaluations.show', ['layout' => $task->layout_id, 'point' => $task->point_id]),
            ]);
        }
        $submitter = $this->a5sAppUserPayload($this->me(), (string) ($this->me()->employee_code ?? ''));
        A5sActivityLog::write(
            $this->me()->id,
            $wasFailed ? 'task.resubmit' : 'task.submit',
            'task',
            $task->id,
            [
                'point' => $task->point_code,
                'count' => $task->submit_count,
                // snapshot ชื่อผู้ส่ง ณ เวลาส่ง เพื่อให้ประวัติยังอ่านได้แม้ข้อมูลผู้ใช้เปลี่ยนภายหลัง
                'submitter' => collect($submitter)->only(['code', 'name', 'name_th', 'name_en', 'name_my'])->all(),
            ]
        );

        return back()
            ->with('success', $wasFailed ? 'ส่งแก้ไขให้ผู้ประเมินแล้ว' : 'ส่งตรวจแล้ว — รอผู้ประเมิน')
            ->with('responsible_point_id', $point->id);
    }

    public function storeCard(Request $request, A5sPoint $point): RedirectResponse
    {
        if (! $this->canWorkPoint($point)) {
            return back()->with('error', 'คุณไม่มีสิทธิ์บันทึกการ์ดในจุดนี้');
        }

        $data = $request->validate([
            'title' => ['required', 'string', 'max:191'],
            'detail' => ['nullable', 'string', 'max:3000'],
            'images' => ['required', 'array', 'min:1', 'max:8'],
            'images.*' => $this->cardImageRules(required: true),
            '_point_id' => ['nullable', 'integer'],
            '_form_mode' => ['nullable', 'string'],
        ]);

        $card = DB::connection('mysql_area5s')->transaction(function () use ($data, $point, $request) {
            $task = $this->taskForPoint($point);
            if ($task->status === 'passed') {
                throw ValidationException::withMessages(['task' => 'งานจุดนี้ผ่านการประเมินแล้ว — ล็อกถาวรสำหรับเดือนนี้']);
            }
            $card = A5sCard::create([
                'task_id' => $task->id,
                'title' => trim($data['title']),
                'detail' => trim((string) ($data['detail'] ?? '')) ?: null,
                'created_by' => $this->me()->id,
                'sort' => (int) $task->cards()->max('sort') + 1,
            ]);

            foreach ($request->file('images', []) as $index => $image) {
                A5sCardImage::create([
                    'card_id' => $card->id,
                    'path' => $image->store('area5s/cards', 'public'),
                    'sort' => $index + 1,
                ]);
            }

            if ($task->status === 'not_started') {
                $task->status = 'draft';
                $task->save();
            }

            A5sActivityLog::write($this->me()->id, 'card.create', 'card', $card->id, [
                'point' => $point->code,
                'title' => $card->title,
            ]);

            return $card;
        });

        return back()
            ->with('success', 'บันทึกการ์ดแล้ว')
            ->with('responsible_point_id', $point->id)
            ->with('created_card_id', $card->id);
    }

    public function updateCard(Request $request, A5sCard $card): RedirectResponse
    {
        if (! $this->canManageCard($card)) {
            return back()->with('error', 'คุณไม่มีสิทธิ์แก้ไข card นี้');
        }

        $data = $request->validate([
            'title' => ['required', 'string', 'max:191'],
            'detail' => ['nullable', 'string', 'max:3000'],
            'images' => ['nullable', 'array', 'max:8'],
            'images.*' => $this->cardImageRules(required: false),
            'remove_images' => ['nullable', 'array', 'max:20'],
            'remove_images.*' => ['integer'],
            '_point_id' => ['nullable', 'integer'],
            '_card_id' => ['nullable', 'integer'],
            '_form_mode' => ['nullable', 'string'],
        ]);

        $removeImageIds = collect($data['remove_images'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values();

        // การ์ดต้องเหลือรูปอย่างน้อย 1 รูปเสมอ (เงื่อนไขเดียวกับตอนสร้าง/ส่งตรวจ)
        $removingCount = $removeImageIds->isNotEmpty()
            ? $card->images()->whereIn('id', $removeImageIds)->count()
            : 0;
        if ($card->images()->count() - $removingCount + count($request->file('images', [])) < 1) {
            return back()
                ->with('error', 'การ์ดต้องมีรูปภาพอย่างน้อย 1 รูป — เพิ่มรูปใหม่ก่อนลบรูปเดิมออกทั้งหมด หรือลบการ์ดทั้งใบแทน')
                ->with('responsible_point_id', $card->task?->point_id)
                ->with('created_card_id', $card->id);
        }

        DB::connection('mysql_area5s')->transaction(function () use ($data, $card, $request, $removeImageIds) {
            $card->title = trim($data['title']);
            $card->detail = trim((string) ($data['detail'] ?? '')) ?: null;
            $card->save();

            if ($removeImageIds->isNotEmpty()) {
                $imagesToRemove = $card->images()
                    ->whereIn('id', $removeImageIds)
                    ->get();
                foreach ($imagesToRemove as $image) {
                    Storage::disk('public')->delete($image->path);
                    $image->delete();
                }
            }

            $nextSort = (int) $card->images()->max('sort');
            foreach ($request->file('images', []) as $image) {
                $nextSort++;
                A5sCardImage::create([
                    'card_id' => $card->id,
                    'path' => $image->store('area5s/cards', 'public'),
                    'sort' => $nextSort,
                ]);
            }

            A5sActivityLog::write($this->me()->id, 'card.update', 'card', $card->id, [
                'title' => $card->title,
            ]);
        });

        return back()
            ->with('success', 'แก้ไข card แล้ว')
            ->with('responsible_point_id', $card->task?->point_id)
            ->with('created_card_id', $card->id);
    }

    public function destroyCard(A5sCard $card): RedirectResponse
    {
        if (! $this->canManageCard($card)) {
            return back()->with('error', 'คุณไม่มีสิทธิ์ลบ card นี้');
        }

        $pointId = $card->task?->point_id;
        $taskReset = false;
        DB::connection('mysql_area5s')->transaction(function () use ($card, &$taskReset) {
            $task = $card->task;
            foreach ($card->images as $image) {
                Storage::disk('public')->delete($image->path);
            }

            A5sActivityLog::write($this->me()->id, 'card.delete', 'card', $card->id, [
                'title' => $card->title,
            ]);
            $card->delete();

            // ลบการ์ดใบสุดท้าย = งานไม่มีข้อมูลให้ตรวจแล้ว → รีเซ็ตกลับ "ยังไม่ดำเนินการ"
            // กันงานค้างสถานะรอตรวจบนหน้าตรวจประเมินทั้งที่ไม่มีการ์ด (passed/รอบปิดถูกบล็อกที่ canManageCard แล้ว)
            if ($task && in_array($task->status, ['draft', 'submitted', 'resubmitted'], true) && ! $task->cards()->exists()) {
                $task->status = 'not_started';
                $task->submitted_at = null;
                $task->submit_count = 0;
                $task->save();
                $taskReset = true;
                A5sActivityLog::write($this->me()->id, 'task.reset', 'task', $task->id, [
                    'point' => $task->point_code,
                    'reason' => 'ลบการ์ดใบสุดท้ายหลังส่งตรวจ',
                ]);
            }
        });

        return back()
            ->with('success', $taskReset ? 'ลบ card แล้ว — จุดนี้ไม่เหลือการ์ด สถานะกลับเป็นยังไม่ดำเนินการ' : 'ลบ card แล้ว')
            ->with('responsible_point_id', $pointId);
    }

    private function gateResponsible(): ?RedirectResponse
    {
        if ($redirect = $this->gateEnter()) {
            return $redirect;
        }

        if ($this->isResponsible()) {
            return null;
        }

        return $this->denyToSystems('toast.pageDenied', 'คุณยังไม่มีจุดพื้นที่ที่ได้รับมอบหมาย');
    }

    private function gateMyWork(): ?RedirectResponse
    {
        if ($redirect = $this->gateEnter()) {
            return $redirect;
        }

        if ($this->isResponsible() || $this->canReviewAnyLayout()) {
            return null;
        }

        return $this->denyToSystems('toast.pageDenied', 'คุณยังไม่มีงานพื้นที่หรือรายการตรวจประเมิน');
    }

    private function gateEvaluator(): ?RedirectResponse
    {
        if ($redirect = $this->gateEnter()) {
            return $redirect;
        }

        if ($this->canReviewAnyLayout()) {
            return null;
        }

        return $this->denyToSystems('toast.pageDenied', 'คุณยังไม่ได้รับมอบหมายให้ตรวจประเมินพื้นที่');
    }

    private function canReviewAnyLayout(): bool
    {
        $myCode = $this->myCode();

        return $this->isA5sAdmin()
            || ($myCode !== '' && A5sEvaluatorScope::where('employee_code', $myCode)->exists());
    }

    private function reviewLayoutIds(string $employeeCode): Collection
    {
        if ($this->isA5sAdmin()) {
            return A5sLayout::where('is_active', true)
                ->pluck('id');
        }

        if ($employeeCode === '') {
            return collect();
        }

        return A5sEvaluatorScope::where('employee_code', $employeeCode)
            ->pluck('layout_id')
            ->unique()
            ->values();
    }

    private function reviewPointIdsForLayout(A5sLayout $layout, string $employeeCode): Collection
    {
        if ($this->isA5sAdmin()) {
            return $layout->points()->where('is_active', true)->pluck('id');
        }

        if ($employeeCode === '') {
            return collect();
        }

        $scopes = A5sEvaluatorScope::where('employee_code', $employeeCode)
            ->where('layout_id', $layout->id)
            ->get();

        if ($scopes->contains(fn (A5sEvaluatorScope $scope) => $scope->point_id === null)) {
            return $layout->points()->where('is_active', true)->pluck('id');
        }

        return $scopes->pluck('point_id')->filter()->unique()->values();
    }

    private function publicStatusLabel(?A5sTask $task): string
    {
        return match ($task?->status) {
            'passed' => 'ผ่าน',
            'failed' => 'ปฏิเสธ',
            default => 'รอดำเนินการ',
        };
    }

    private function canWorkPoint(A5sPoint $point): bool
    {
        $point->loadMissing('layout');
        $myCode = $this->myCode();

        return $myCode !== ''
            && $point->is_active
            && (bool) $point->layout?->is_active
            && (bool) $point->layout?->inOpenRound() // กันบันทึกข้ามรอบ (จุดของเดือนอื่น)
            && A5sPointAssignee::where('point_id', $point->id)
                ->where('employee_code', $myCode)
                ->whereNull('removed_at')
                ->exists();
    }

    private function canManageCard(A5sCard $card): bool
    {
        $card->loadMissing('task.point.layout', 'images');
        $point = $card->task?->point;

        // D7: งานที่ผ่านแล้ว หรือรอบปิดแล้ว = ล็อกถาวร (แก้/ลบไม่ได้)
        if ($this->taskLocked($card->task)) {
            return false;
        }

        return (int) $card->created_by === (int) $this->me()->id
            && $point instanceof A5sPoint
            && $this->canWorkPoint($point);
    }

    /** งานถูกล็อกไหม — ผ่านแล้ว หรือรอบของงานปิดแล้ว (D6/D7) */
    private function taskLocked(?A5sTask $task): bool
    {
        if (! $task) {
            return false;
        }
        if ($task->status === 'passed') {
            return true;
        }
        $round = A5sRound::find($task->round_id);

        return $round !== null && ! $round->isOpen();
    }

    private function taskForPoint(A5sPoint $point): A5sTask
    {
        $point->loadMissing('layout');
        $round = $this->currentRound();

        return A5sTask::firstOrCreate(
            ['round_id' => $round->id, 'point_id' => $point->id],
            [
                'layout_id' => $point->layout_id,
                'layout_name' => $point->layout?->name ?? '-',
                'point_code' => $point->code,
                'point_name' => $point->name,
                'point_description' => $point->description,
                'assignees_json' => $this->snapshotAssignees($point),
                'evaluators_json' => $this->snapshotEvaluators($point),
                'status' => 'not_started',
            ]
        );
    }

    /** รอบที่เปิดอยู่ — ไม่มี = บล็อกบันทึก (admin เปิดรอบเองที่ /area5s/rounds ไม่ auto-create แล้ว) */
    private function currentRound(): A5sRound
    {
        $open = A5sRound::open();
        if (! $open) {
            throw ValidationException::withMessages([
                'round' => 'ยังไม่มีการเปิดรอบเดือน — บันทึกได้เมื่อแอดมินเปิดรอบ',
            ]);
        }

        return $open;
    }

    private function snapshotAssignees(A5sPoint $point): array
    {
        $assignees = A5sPointAssignee::where('point_id', $point->id)
            ->whereNull('removed_at')
            ->get();
        $employees = Employee::active()
            ->whereIn('employee_code', $assignees->pluck('employee_code')->unique())
            ->get()
            ->keyBy('employee_code');

        return $assignees->map(function (A5sPointAssignee $assignee) use ($employees) {
            $employee = $employees->get($assignee->employee_code);

            return $this->a5sEmployeePayload($employee, $assignee->employee_code);
        })->values()->all();
    }

    private function snapshotEvaluators(A5sPoint $point): array
    {
        $scopes = A5sEvaluatorScope::where('layout_id', $point->layout_id)
            ->where(fn ($query) => $query->where('point_id', $point->id)->orWhereNull('point_id'))
            ->get()
            ->unique('employee_code');
        $employees = Employee::active()
            ->whereIn('employee_code', $scopes->pluck('employee_code')->unique())
            ->get()
            ->keyBy('employee_code');

        return $scopes->map(function (A5sEvaluatorScope $scope) use ($employees) {
            $employee = $employees->get($scope->employee_code);

            return $this->a5sEmployeePayload($employee, $scope->employee_code);
        })->values()->all();
    }

    private function cardImageRules(bool $required): array
    {
        $allowed = ['jpg', 'jpeg', 'png', 'webp', 'heic', 'heif'];

        return [
            $required ? 'required' : 'nullable',
            'file',
            'max:12288',
            function (string $attribute, mixed $value, $fail) use ($allowed): void {
                if (! $value instanceof UploadedFile) {
                    $fail('ไฟล์ภาพไม่ถูกต้อง');

                    return;
                }

                $extension = strtolower($value->getClientOriginalExtension());
                if (! in_array($extension, $allowed, true)) {
                    $fail('รองรับเฉพาะไฟล์ JPG, PNG, WEBP, HEIC หรือ HEIF');
                }
            },
        ];
    }

    private function cardsByPoint(Collection $pointIds): Collection
    {
        $round = A5sRound::open();
        if (! $round) {
            return collect();
        }

        $tasks = A5sTask::where('round_id', $round->id)
            ->whereIn('point_id', $pointIds)
            ->get()
            ->keyBy('id');
        $cards = A5sCard::with('images')
            ->whereIn('task_id', $tasks->keys())
            ->orderByDesc('created_at')
            ->get();
        $owners = AppUser::whereIn('id', $cards->pluck('created_by')->unique())
            ->get()
            ->keyBy('id');

        return $cards
            ->map(function (A5sCard $card) use ($tasks, $owners) {
                $task = $tasks->get($card->task_id);
                $owner = $owners->get($card->created_by);

                $ownerPayload = $this->a5sAppUserPayload($owner, $owner?->employee_code ?? '');

                return [
                    'id' => $card->id,
                    'point_id' => $task?->point_id,
                    'title' => $card->title,
                    'detail' => $card->detail,
                    'owner' => $ownerPayload['name'] ?: '-',
                    'owner_th' => $ownerPayload['name_th'] ?: '-',
                    'owner_en' => $ownerPayload['name_en'] ?: '-',
                    'owner_my' => $ownerPayload['name_my'] ?: '-',
                    'created_at' => $this->a5sBeDateTime($card->created_at),
                    'images' => $card->images->map(fn (A5sCardImage $image) => asset('storage/'.$image->path))->values()->all(),
                    'image_items' => $card->images->map(fn (A5sCardImage $image) => [
                        'id' => $image->id,
                        'src' => asset('storage/'.$image->path),
                    ])->values()->all(),
                    'can_manage' => (int) $card->created_by === (int) $this->me()->id
                        && ! $this->taskLocked($task),
                    'update_url' => route('area5s.responsible.cards.update', $card),
                    'delete_url' => route('area5s.responsible.cards.destroy', $card),
                ];
            })
            ->filter(fn (array $card) => $card['point_id'] !== null)
            ->groupBy('point_id');
    }

    private function myCode(): string
    {
        return trim((string) ($this->me()->employee_code ?? ''));
    }

    private function myPointIds(string $employeeCode): Collection
    {
        if ($employeeCode === '') {
            return collect();
        }

        return A5sPointAssignee::where('employee_code', $employeeCode)
            ->whereNull('removed_at')
            ->pluck('point_id');
    }
}
