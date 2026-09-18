<?php

namespace App\Http\Controllers\Area5s;

use App\Http\Controllers\Area5s\Concerns\HandlesArea5sAccess;
use App\Http\Controllers\Controller;
use App\Models\Area5s\A5sCompanyPlan;
use App\Models\Area5s\A5sLayout;
use App\Models\Area5s\A5sMember;
use App\Models\Area5s\A5sPoint;
use App\Models\Area5s\A5sPointAssignee;
use App\Models\Area5s\A5sRound;
use App\Models\Area5s\A5sTask;
use App\Models\Area5s\A5sZone;
use App\Models\Area5s\A5sZoneMapArea;
use App\Services\Area5s\A5sRoundService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View as ViewContract;

/**
 * SUPAVUT 5S AREA — หน้าภาพรวม (Dashboard เฟส 3 จะขยายเป็นการ์ด Layout เต็มรูปแบบ)
 */
class Area5sController extends Controller
{
    use HandlesArea5sAccess;

    /** หน้าหลัก 5ส — info รวมภาพคำแนะนำ ทุก user เห็นเป็นหน้าแรกเมื่อเข้าระบบ */
    public function home(): ViewContract|RedirectResponse
    {
        if ($redirect = $this->gateEnter()) {
            return $redirect;
        }

        // ภาพประกาศแยกตามปี — เพิ่มปีใหม่ได้โดยเติม key + วางไฟล์ใน assets/area5s/home/<ปี>/ (Manager 2026-07-23)
        $base = 'assets/area5s/home';
        $img = fn (string $sub, array $files) => array_map(fn ($f) => asset("$base/$sub$f"), $files);
        $imagesByYear = [
            '2026' => $img('', ['1.png', '2.png', '3.jpg', '4.jpg', '5.jpg', '6.jpg']),
            '2025' => $img('2025/', ['1.png', '2.png', '3.png', '4.png', '5.jpg', '6.png', '7.png']),
        ];
        krsort($imagesByYear); // ปีใหม่สุดขึ้นก่อน
        $currentYear = (string) date('Y');
        $defaultYear = isset($imagesByYear[$currentYear]) ? $currentYear : (string) array_key_first($imagesByYear);

        return view('area5s.home', [
            'me' => $this->me(),
            // ประกาศเด้งเฉพาะครั้งแรกหลังล็อกอิน (consume flag ทันที) — เข้าซ้ำในล็อกอินเดิมไม่เด้ง
            'showWelcome' => (bool) session()->pull('a5s_welcome_pending', false),
            'megaphone' => asset('assets/area5s/home/megaphone.png'),
            'imagesByYear' => $imagesByYear,
            'defaultYear' => $defaultYear,
        ]);
    }

    public function index(Request $request): ViewContract|RedirectResponse
    {
        if ($redirect = $this->gateEnter()) {
            return $redirect;
        }

        $me = $this->me();
        $myCode = trim((string) ($me->employee_code ?? ''));
        $myPointIds = $myCode === ''
            ? collect()
            : A5sPointAssignee::where('employee_code', $myCode)->whereNull('removed_at')->pluck('point_id');
        // จำนวนจุดของฉันต่อ layout — ใช้ขึ้น badge บนการ์ดข่าว
        $myByLayout = $myPointIds->isEmpty()
            ? collect()
            : A5sPoint::whereIn('id', $myPointIds)->where('is_active', true)->pluck('layout_id')->countBy();

        /* หน้านี้ไม่มีตัวเลือกเดือน/วันที่ตรวจแล้ว (Manager 2026-08-27) แต่ยังรับ ?month= / ?round=
           จาก deep link ของหน้าอื่น เพื่อเลือกรอบที่จะพาไปหน้าอาคาร */
        $openRound = A5sRound::open();
        $rounds = A5sRound::orderByDesc('year')->orderByDesc('month')->get();
        $openRoundId = (int) ($openRound?->id ?? 0);
        $roundsByMonth = $rounds->groupBy(fn (A5sRound $round) => $round->year.'-'.$round->month);

        $requestedMonth = (string) $request->query('month', '');
        $requestedRoundId = (int) $request->query('round');
        $viewRound = $rounds->firstWhere('id', $requestedRoundId);

        // เดือนที่เลือกต้องคุมรอบที่เลือกเสมอ กันแก้ URL ให้ไม่สอดคล้องกัน
        if ($requestedMonth !== '' && $viewRound && ($viewRound->year.'-'.$viewRound->month) !== $requestedMonth) {
            $viewRound = null;
        }
        if (! $viewRound && $requestedMonth !== '' && $roundsByMonth->has($requestedMonth)) {
            $monthRounds = $roundsByMonth->get($requestedMonth);
            // เดือนที่มีรอบเปิดอยู่ให้เลือกรอบเปิดก่อน ไม่งั้นเอาครั้งตรวจล่าสุด
            $viewRound = $monthRounds->firstWhere('id', $openRoundId) ?: $monthRounds->first();
        }
        $viewRound = $viewRound ?: ($openRound ?: $rounds->first());
        $viewRoundId = (int) ($viewRound?->id ?? 0);
        $layoutQuery = fn () => A5sLayout::where('is_active', true)->where('round_id', $viewRoundId);

        $layouts = $layoutQuery()
            ->with(['floor.zone', 'floor.area.zoneMap.zone'])
            ->withCount('points')
            ->orderByDesc('updated_at')
            ->get();

        $plan = A5sCompanyPlan::where('is_active', true)
            ->with([
                'zones' => fn ($query) => $query->where('is_active', true)->orderBy('sort')->orderBy('id'),
                'zones.zoneMaps' => fn ($query) => $query->where('is_active', true)->orderBy('sort')->orderBy('id'),
                'zones.zoneMaps.areas' => fn ($query) => $query->where('is_active', true)->orderBy('sort')->orderBy('id'),
                'zones.zoneMaps.areas.floors' => fn ($query) => $query->where('is_active', true)->orderBy('sort')->orderBy('name'),
            ])
            ->orderByDesc('id')
            ->first();
        $planZones = $plan?->zones ?? collect();
        $layoutCountByArea = $layouts
            ->filter(fn (A5sLayout $layout) => $layout->floor?->area)
            ->countBy(fn (A5sLayout $layout) => $layout->floor->area->id);

        // จัดกลุ่มการ์ดตามโครงสร้างแปลนบริษัทจริง: โซน/อาคาร -> ชั้น (floor_id) — Layout ที่ยังไม่ผูกชั้น = กลุ่ม "ยังไม่จัดชั้น" ท้ายสุด
        $layoutsByZone = $this->groupLayoutsByZone($layouts);

        return view('area5s.index', [
            'me' => $me,
            'isA5sAdmin' => $this->isA5sAdmin(),
            'isAllocator' => $this->isAllocator(),
            'isEvaluator' => $this->isEvaluator(),
            'openRound' => $openRound,
            'rounds' => $rounds,
            'viewRound' => $viewRound,
            'isPastRound' => $viewRound && (! $openRound || $viewRound->id !== $openRound->id),
            'monthNames' => A5sRoundService::MONTHS_TH,
            'layoutCount' => $layoutQuery()->count(),
            'pointCount' => A5sPoint::where('is_active', true)->whereIn('layout_id', $layoutQuery()->pluck('id'))->count(),
            'memberCount' => A5sMember::count(),
            'myPointCount' => $myPointIds->count(),
            'myByLayout' => $myByLayout,
            'layouts' => $layouts,
            'layoutsByZone' => $layoutsByZone,
            'plan' => $plan,
            'planZones' => $planZones,
            'layoutCountByArea' => $layoutCountByArea,
            // อาคาร/พื้นที่ย่อยที่ crop จากภาพโซน — โผล่หลังกดภาพบริษัท (Manager 2026-08-27)
            'planAreas' => $planZones->flatMap(fn ($zone) => collect($zone->zoneMaps ?? [])
                ->flatMap(fn ($zoneMap) => collect($zoneMap->areas ?? [])->map(fn ($area) => [
                    'id' => (int) $area->id,
                    'name' => (string) $area->name,
                    'zone_name' => (string) $zone->name,
                    'image_url' => $zoneMap->image_path ? asset('storage/'.$zoneMap->image_path) : null,
                    'shape_points' => $area->shape_points ?? [],
                    'floor_count' => collect($area->floors ?? [])->count(),
                    'layout_count' => (int) ($layoutCountByArea[$area->id] ?? 0),
                    'url' => route('area5s.areas.show', ['area' => $area->id, 'round' => $viewRoundId]),
                ])))->values(),
        ]);
    }

    /** หน้ารวม Layout ภายในพื้นที่ย่อย/อาคาร — เลือกครั้งตรวจ (รอบ) แล้วดูประวัติการประเมินทุกจุด */
    public function area(Request $request, A5sZoneMapArea $area): ViewContract|RedirectResponse
    {
        if ($redirect = $this->gateEnter()) {
            return $redirect;
        }

        $area->load([
            'zoneMap.zone',
            'floors' => fn ($query) => $query->where('is_active', true)->orderBy('sort')->orderBy('name'),
        ]);
        abort_unless($area->is_active && $area->zoneMap?->is_active && $area->zoneMap?->zone?->is_active, 404);

        $floorIds = $area->floors->pluck('id');
        $roundIds = A5sLayout::whereIn('floor_id', $floorIds)
            ->where('is_active', true)
            ->pluck('round_id')->unique()->filter()->values();

        $openRound = A5sRound::open();
        $rounds = A5sRound::whereIn('id', $roundIds)->get();
        $viewRound = $this->resolveViewRound($rounds, $openRound, (int) $request->query('round'));

        if (! $viewRound) {
            return redirect()->route('area5s.index')->with('error', 'ยังไม่มีข้อมูลในพื้นที่นี้');
        }
        $viewRoundId = (int) $viewRound->id;
        $isOpenRound = $openRound && $viewRoundId === (int) $openRound->id;

        $floorPos = $area->floors->pluck('id')->values()->flip();
        $layouts = A5sLayout::whereIn('floor_id', $floorIds)
            ->where('round_id', $viewRoundId)
            ->where('is_active', true)
            ->with('floor')
            ->withCount('points')
            ->get()
            ->sortBy(fn (A5sLayout $layout) => sprintf('%03d|%s', $floorPos[$layout->floor_id] ?? 999, mb_strtolower($layout->name)))
            ->values();

        $pointsByLayout = A5sPoint::whereIn('layout_id', $layouts->pluck('id'))
            ->where('is_active', true)
            ->orderBy('sort')->orderBy('code')
            ->get(['id', 'layout_id', 'code', 'name'])
            ->groupBy('layout_id');
        $taskByPoint = A5sTask::where('round_id', $viewRoundId)
            ->whereIn('point_id', $pointsByLayout->flatten()->pluck('id'))
            ->withCount('cards')
            ->get();
        $cardCountByLayout = $taskByPoint->groupBy('layout_id')->map(fn (Collection $tasks) => $tasks->sum('cards_count'));
        $taskByPoint = $taskByPoint->keyBy('point_id');

        $panel = [
            'back_url' => route('area5s.index', ['round' => $viewRoundId]),
            'title_key' => 'a5s.area.title',
            'title_default' => 'พื้นที่ย่อย',
            'hint_key' => 'a5s.history.hintOverview',
            'hint_default' => 'เลือกครั้งตรวจเพื่อดูประวัติการประเมินของทุกจุดในพื้นที่นี้',
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
                fn (A5sLayout $layout) => route('area5s.layouts.show', $layout),
                'a5s.common.viewLayout',
                'ดูพื้นที่'
            ),
            'empty_key' => 'a5s.common.noLayout',
            'empty_default' => 'ยังไม่มีพื้นที่ในระบบ',
        ];

        return view('area5s.area-overview', [
            'me' => $this->me(),
            'panel' => $panel,
        ]);
    }

    /**
     * จัดกลุ่ม Layout ตามโซน -> ชั้น จริง (ไม่ใช่เดาจากชื่อ) — เรียงตาม sort ของโซน/ชั้นที่ Admin ตั้งไว้
     * Layout ที่ floor_id ยังเป็น null (รอ Admin map) = กลุ่ม "ยังไม่จัดชั้น" ท้ายสุด ไม่ error ไม่หาย
     */
    private function groupLayoutsByZone(Collection $layouts): array
    {
        $mapped = $layouts->filter(fn (A5sLayout $l) => $this->layoutZone($l));
        $unmapped = $layouts->filter(fn (A5sLayout $l) => ! $this->layoutZone($l));

        $groups = $mapped
            ->groupBy(fn (A5sLayout $l) => $this->layoutZone($l)->id)
            ->map(function (Collection $zoneLayouts) {
                $zone = $this->layoutZone($zoneLayouts->first());
                $areas = $zoneLayouts
                    ->groupBy(function (A5sLayout $l) {
                        $area = $l->floor?->area;

                        return $area ? 'area-'.$area->id : 'legacy-'.$l->floor?->zone_id;
                    })
                    ->map(function (Collection $areaLayouts) {
                        $first = $areaLayouts->first();
                        $area = $first->floor?->area;
                        $zoneMap = $area?->zoneMap;
                        $floors = $areaLayouts
                            ->groupBy(fn (A5sLayout $l) => $l->floor_id ?: 'none')
                            ->map(function (Collection $floorLayouts) {
                                $floor = $floorLayouts->first()->floor;

                                return [
                                    'floor_id' => $floor?->id,
                                    'floor_name' => $floor?->name,
                                    'sort' => $floor?->sort ?? PHP_INT_MAX,
                                    'layouts' => $floorLayouts->values(),
                                ];
                            })
                            ->sortBy('sort')
                            ->values();

                        return [
                            'area_id' => $area?->id,
                            'area_name' => $area?->name,
                            'zone_map_id' => $zoneMap?->id,
                            'zone_map_name' => $zoneMap?->name,
                            'sort' => $area?->sort ?? PHP_INT_MAX,
                            'layout_count' => $areaLayouts->count(),
                            'floors' => $floors,
                        ];
                    })
                    ->sortBy('sort')
                    ->values();

                return [
                    'zone_id' => $zone->id,
                    'zone_name' => $zone->name,
                    'sort' => $zone->sort,
                    'areas' => $areas,
                ];
            })
            ->sortBy('sort')
            ->values();

        if ($unmapped->isNotEmpty()) {
            $groups->push([
                'zone_id' => null,
                'zone_name' => null,
                'sort' => PHP_INT_MAX,
                'areas' => [[
                    'area_id' => null,
                    'area_name' => null,
                    'zone_map_id' => null,
                    'zone_map_name' => null,
                    'sort' => PHP_INT_MAX,
                    'layout_count' => $unmapped->count(),
                    'floors' => [['floor_id' => null, 'floor_name' => null, 'sort' => 0, 'layouts' => $unmapped->values()]],
                ]],
            ]);
        }

        return $groups->all();
    }

    private function layoutZone(A5sLayout $layout): ?A5sZone
    {
        return $layout->floor?->area?->zoneMap?->zone ?? $layout->floor?->zone;
    }
}
