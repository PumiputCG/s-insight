<?php

namespace App\Http\Controllers\Area5s;

use App\Http\Controllers\Area5s\Concerns\HandlesArea5sAccess;
use App\Http\Controllers\Controller;
use App\Models\Area5s\A5sActivityLog;
use App\Models\Area5s\A5sCard;
use App\Models\Area5s\A5sCardImage;
use App\Models\Area5s\A5sCompanyPlan;
use App\Models\Area5s\A5sEvaluatorScope;
use App\Models\Area5s\A5sFloor;
use App\Models\Area5s\A5sLayout;
use App\Models\Area5s\A5sPoint;
use App\Models\Area5s\A5sPointAssignee;
use App\Models\Area5s\A5sRound;
use App\Models\Area5s\A5sTask;
use App\Models\Area5s\A5sZone;
use App\Models\Area5s\A5sZoneMapArea;
use App\Models\Insight\AppUser;
use App\Models\Insight\Employee;
use App\Services\Area5s\A5sScoreService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View as ViewContract;

/**
 * จัดการพื้นที่ 5S — Layout + จุด (marker) + ผู้รับผิดชอบ
 *
 * สิทธิ์ (D4): allocator สร้าง/แก้เฉพาะ Layout ที่ตัวเองสร้าง · admin แก้ได้ทุกอัน
 * ถอดผู้รับผิดชอบ = set removed_at ไม่ delete (ประวัติไม่หาย)
 */
class Area5sLayoutController extends Controller
{
    use HandlesArea5sAccess;

    /** เจ้าของหรือ admin เท่านั้นที่แก้ Layout นี้ได้ — และต้องเป็น Layout ของรอบที่เปิดอยู่ (เดือนปิด/เดือนอื่น = read-only) */
    private function canEdit(A5sLayout $layout): bool
    {
        return $layout->inOpenRound()
            && ($this->isA5sAdmin() || ($this->isAllocator() && (int) $layout->created_by === (int) $this->me()->id));
    }

    // ===== จัดการพื้นที่: เลือกโซนก่อน -> list Layout ของโซนนั้น (โครงสร้างแปลนบริษัท) =====

    /** หน้าแรกของ "จัดการพื้นที่" — เลือกอาคาร/โซนจากภาพแปลนบริษัทก่อนเข้า Layout รายห้อง */
    public function manage(): ViewContract|RedirectResponse
    {
        if ($redirect = $this->gateManage()) {
            return $redirect;
        }

        $plan = A5sCompanyPlan::where('is_active', true)->orderByDesc('id')->first();
        // eager-load zone_maps + areas + floors ไว้ crop ภาพอาคารและนับชั้น/พื้นที่บนการ์ด
        $zones = $plan ? $plan->zones()->where('is_active', true)->with([
            'floors',
            'zoneMaps' => fn ($q) => $q->where('is_active', true)->orderBy('sort')->orderBy('id'),
            'zoneMaps.areas' => fn ($q) => $q->where('is_active', true)->orderBy('sort')->orderBy('id'),
            'zoneMaps.areas.floors' => fn ($q) => $q->where('is_active', true),
        ])->get() : collect();
        $openRoundId = (int) A5sRound::open()?->id;

        // จำนวน Layout ต่อชั้น (รอบเปิด) — เอาไปรวมเป็นยอด "พื้นที่" ของแต่ละอาคารบนการ์ด
        $layoutCountByFloor = A5sLayout::where('round_id', $openRoundId)->whereNotNull('floor_id')
            ->selectRaw('floor_id, COUNT(*) as c')->groupBy('floor_id')->pluck('c', 'floor_id');
        $unmappedCount = A5sLayout::where('round_id', $openRoundId)
            ->where(function ($query) {
                $query->whereNull('floor_id')
                    ->orWhereHas('floor', fn ($floorQuery) => $floorQuery->whereNull('zone_map_area_id'));
            })
            ->count();

        /* อาคารที่ crop จากภาพโซน — ใช้กับบล็อก "ภาพบริษัท → เผยอาคาร" ชุดเดียวกับหน้า /area5s
           ต่างกันแค่ปลายทาง: หน้านี้พาเข้าหน้าจัดการ Layout ของอาคารนั้น (Manager 2026-08-27) */
        $planAreas = $zones->flatMap(fn (A5sZone $z) => $z->zoneMaps->flatMap(fn ($zoneMap) => $zoneMap->areas->map(fn ($area) => [
            'id' => (int) $area->id,
            'name' => (string) $area->name,
            'zone_name' => (string) $z->name,
            'image_url' => $zoneMap->image_path ? asset('storage/'.$zoneMap->image_path) : null,
            'shape_points' => $area->shape_points ?? [],
            'floor_count' => $area->floors->count(),
            'layout_count' => (int) $area->floors->sum(fn ($floor) => (int) ($layoutCountByFloor[$floor->id] ?? 0)),
            'url' => route('area5s.manage.zone.area', ['zone' => $z->id, 'area' => $area->id]),
        ])))->values();

        return view('area5s.manage-zones', [
            'me' => $this->me(),
            'isA5sAdmin' => $this->isA5sAdmin(),
            'plan' => $plan,
            'planAreas' => $planAreas,
            'unmappedCount' => $unmappedCount,
        ]);
    }

    /** Layout ทั้งหมดของโซนที่เลือก (flow เดิม: list + สร้าง + แก้ไข/ปักจุด) */
    public function zoneLayouts(Request $request, A5sZone $zone): ViewContract|RedirectResponse
    {
        if ($redirect = $this->gateManage()) {
            return $redirect;
        }

        if ((int) $request->query('area') > 0) {
            return redirect()->route('area5s.manage.zone.area', [
                'zone' => $zone,
                'area' => (int) $request->query('area'),
            ]);
        }

        $zone->loadMissing([
            'zoneMaps' => fn ($query) => $query->where('is_active', true)->orderBy('sort')->orderBy('id'),
            'zoneMaps.areas' => fn ($query) => $query->where('is_active', true)->orderBy('sort')->orderBy('id'),
            'zoneMaps.areas.floors' => fn ($query) => $query->where('is_active', true)->orderBy('sort')->orderBy('name'),
        ]);

        return view('area5s.manage-subzones', [
            'me' => $this->me(),
            'isA5sAdmin' => $this->isA5sAdmin(),
            'zone' => $zone,
            'zoneMaps' => $zone->zoneMaps,
        ]);
    }

    /** Layouts of the selected sub-zone building (zone_map_area) */
    public function zoneAreaLayouts(A5sZone $zone, A5sZoneMapArea $area): ViewContract|RedirectResponse
    {
        if ($redirect = $this->gateManage()) {
            return $redirect;
        }

        $area->loadMissing([
            'zoneMap',
            'floors' => fn ($query) => $query->where('is_active', true)->orderBy('sort')->orderBy('name'),
        ]);

        if (
            ! $area->is_active
            || ! $area->zoneMap
            || ! $area->zoneMap->is_active
            || (int) $area->zoneMap->zone_id !== (int) $zone->id
        ) {
            abort(404);
        }

        $isA5sAdmin = $this->isA5sAdmin();
        $openRoundId = (int) A5sRound::open()?->id;
        $floors = $area->floors()->where('is_active', true)->get();
        $floorIds = $floors->pluck('id');

        // เรียง Layout ตามลำดับชั้นจริงใน DB (a5s_floors.sort) แล้วค่อยชื่อ — ให้ view จัดกลุ่มหัวข้อชั้นได้
        $floorPos = $floorIds->values()->flip();
        $byFloorThenName = fn ($items) => $items
            ->sortBy(fn (A5sLayout $l) => sprintf('%03d|%s', $floorPos[$l->floor_id] ?? 999, mb_strtolower($l->name)))
            ->values();

        $myLayouts = $byFloorThenName(
            A5sLayout::with('floor')->withCount('points')
                ->where('round_id', $openRoundId)
                ->whereIn('floor_id', $floorIds)
                ->where('created_by', $this->me()->id)
                ->get()
        );

        $allLayouts = $isA5sAdmin
            ? $byFloorThenName(
                A5sLayout::with('floor')->withCount('points')
                    ->where('round_id', $openRoundId)->whereIn('floor_id', $floorIds)->get()
            )
            : $myLayouts;

        $owners = AppUser::whereIn('id', $allLayouts->merge($myLayouts)->pluck('created_by')->unique())
            ->get()->mapWithKeys(fn ($u) => [$u->id => $this->a5sAppUserPayload($u, $u->employee_code)]);

        return view('area5s.manage', [
            'me' => $this->me(),
            'isA5sAdmin' => $isA5sAdmin,
            'zone' => $zone,
            'selectedArea' => $area,
            'floors' => $floors,
            'layouts' => $isA5sAdmin ? $allLayouts : $myLayouts,
            'allLayouts' => $allLayouts,
            'myLayouts' => $myLayouts,
            'owners' => $owners,
        ]);
    }

    /** Layout เก่าที่ยังไม่ผูกชั้น (ระหว่างรอ Admin map) — จัดการได้ปกติ ไม่ตกหล่น แต่สร้างใหม่ที่นี่ไม่ได้ */
    public function unmappedLayouts(): ViewContract|RedirectResponse
    {
        if ($redirect = $this->gateManage()) {
            return $redirect;
        }

        $isA5sAdmin = $this->isA5sAdmin();
        $openRoundId = (int) A5sRound::open()?->id;

        $myLayouts = A5sLayout::withCount('points')
            ->where('round_id', $openRoundId)
            ->where(function ($query) {
                $query->whereNull('floor_id')
                    ->orWhereHas('floor', fn ($floorQuery) => $floorQuery->whereNull('zone_map_area_id'));
            })
            ->where('created_by', $this->me()->id)
            ->orderByDesc('updated_at')->get();
        $allLayouts = $isA5sAdmin
            ? A5sLayout::withCount('points')
                ->where('round_id', $openRoundId)
                ->where(function ($query) {
                    $query->whereNull('floor_id')
                        ->orWhereHas('floor', fn ($floorQuery) => $floorQuery->whereNull('zone_map_area_id'));
                })
                ->orderByDesc('updated_at')->get()
            : $myLayouts;
        $owners = AppUser::whereIn('id', $allLayouts->merge($myLayouts)->pluck('created_by')->unique())
            ->get()->mapWithKeys(fn ($u) => [$u->id => $this->a5sAppUserPayload($u, $u->employee_code)]);

        return view('area5s.manage', [
            'me' => $this->me(),
            'isA5sAdmin' => $isA5sAdmin,
            'zone' => null,
            'floors' => collect(),
            'isUnmapped' => true,
            'layouts' => $isA5sAdmin ? $allLayouts : $myLayouts,
            'allLayouts' => $allLayouts,
            'myLayouts' => $myLayouts,
            'owners' => $owners,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        if ($redirect = $this->gateManage()) {
            return $redirect;
        }

        $data = $request->validate([
            'zone_id' => ['required', 'integer', 'exists:mysql_area5s.a5s_zones,id'],
            'zone_map_area_id' => ['required', 'integer', 'exists:mysql_area5s.a5s_zone_map_areas,id'],
            'floor_id' => ['required', 'integer', 'exists:mysql_area5s.a5s_floors,id'],
            'name' => ['required', 'string', 'max:191'],
            'description' => ['nullable', 'string', 'max:2000'],
            'image' => ['required', 'image', 'max:8192'],
        ]);

        $floor = A5sFloor::with('area.zoneMap')->find($data['floor_id']);
        $isValidFloor = $floor
            && $floor->is_active
            && (int) $floor->zone_id === (int) $data['zone_id']
            && (int) $floor->zone_map_area_id === (int) $data['zone_map_area_id']
            && (int) $floor->area?->zoneMap?->zone_id === (int) $data['zone_id'];

        if (! $isValidFloor) {
            return back()
                ->withErrors(['floor_id' => 'ชั้นที่เลือกไม่ตรงกับโซน/พื้นที่ย่อย กรุณาเลือกจากหน้าที่ระบบกำหนดให้'])
                ->withInput();
        }

        $path = $request->file('image')->store('area5s/layouts', 'public');
        $layout = A5sLayout::create([
            'round_id' => A5sRound::open()?->id, // Layout ผูกกับรอบเดือนที่เปิดอยู่
            'floor_id' => $floor->id,
            'name' => trim($data['name']),
            'description' => trim((string) ($data['description'] ?? '')) ?: null,
            'image_path' => $path,
            'created_by' => $this->me()->id,
            'is_active' => true,
        ]);
        A5sActivityLog::write($this->me()->id, 'layout.create', 'layout', $layout->id, ['name' => $layout->name, 'floor' => $floor->name]);

        return redirect()
            ->route('area5s.manage.zone.area', ['zone' => $floor->zone_id, 'area' => $floor->zone_map_area_id])
            ->with('a5m_ok_title', 'สร้างพื้นที่แล้ว')
            ->with('a5m_ok_body', 'เลือก "แก้ไข / ปักจุด" เพื่อกำหนดจุดพื้นที่')
            ->with('created_layout_id', $layout->id);
    }

    public function update(Request $request, A5sLayout $layout): JsonResponse
    {
        if (! $this->canEdit($layout)) {
            return response()->json(['ok' => false, 'message' => 'no permission'], 403);
        }

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:191'],
            'description' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'floor_id' => ['sometimes', 'integer', 'exists:mysql_area5s.a5s_floors,id'],
        ]);
        if (array_key_exists('name', $data) && trim($data['name']) !== '') {
            $layout->name = trim($data['name']);
        }
        if (array_key_exists('description', $data)) {
            $layout->description = trim((string) ($data['description'] ?? '')) ?: null;
        }
        if (array_key_exists('floor_id', $data)) {
            $layout->floor_id = (int) $data['floor_id'];
        }
        $layout->save();
        A5sActivityLog::write($this->me()->id, 'layout.update', 'layout', $layout->id, ['name' => $layout->name]);

        return response()->json(['ok' => true]);
    }

    public function destroy(A5sLayout $layout): JsonResponse
    {
        if (! $this->canEdit($layout)) {
            return response()->json(['ok' => false, 'message' => 'no permission'], 403);
        }

        $pointIds = $layout->points()->pluck('id');
        if ($pointIds->isNotEmpty() && A5sTask::whereIn('point_id', $pointIds)->exists()) {
            return response()->json([
                'ok' => false,
                'message' => 'พื้นที่นี้มีประวัติรอบรายเดือนแล้ว ให้ใช้ปิดใช้งานแทน',
            ], 422);
        }

        A5sActivityLog::write($this->me()->id, 'layout.delete', 'layout', $layout->id, [
            'name' => $layout->name,
        ]);
        $layout->delete();

        return response()->json(['ok' => true]);
    }

    /** เปลี่ยนภาพ Layout (จุดเดิมคงพิกัด % ไว้ — ผู้ใช้ปรับเองถ้าภาพใหม่สัดส่วนต่าง) */
    public function updateImage(Request $request, A5sLayout $layout): RedirectResponse
    {
        if (! $this->canEdit($layout)) {
            return back()->with('error', 'ไม่มีสิทธิ์แก้พื้นที่นี้');
        }

        $request->validate(['image' => ['required', 'image', 'max:8192']]);
        $layout->image_path = $request->file('image')->store('area5s/layouts', 'public');
        $layout->save();
        A5sActivityLog::write($this->me()->id, 'layout.image', 'layout', $layout->id, []);

        return back()->with('success', 'เปลี่ยนภาพแล้ว');
    }

    public function toggle(A5sLayout $layout): JsonResponse
    {
        if (! $this->canEdit($layout)) {
            return response()->json(['ok' => false, 'message' => 'no permission'], 403);
        }

        $layout->is_active = ! $layout->is_active;
        $layout->save();
        A5sActivityLog::write($this->me()->id, $layout->is_active ? 'layout.enable' : 'layout.disable', 'layout', $layout->id, []);

        return response()->json(['ok' => true, 'is_active' => $layout->is_active]);
    }

    // ===== Marker editor =====

    public function editor(A5sLayout $layout): ViewContract|RedirectResponse
    {
        if ($redirect = $this->gateManage()) {
            return $redirect;
        }

        if (! $this->canEdit($layout)) {
            return $this->denyToSystems('toast.pageDenied', 'คุณไม่มีสิทธิ์แก้ Layout นี้');
        }

        // ปุ่ม "กลับ" ให้ย้อนไปหน้ารายการ Layout ของอาคารต้นทาง ไม่ใช่หน้าเลือกโซน (Manager 2026-07-22)
        $layout->loadMissing('floor.area.zoneMap.zone');
        $backFloor = $layout->floor;
        $backArea = $backFloor?->area;
        $backZoneMap = $backArea?->zoneMap;
        $backZone = $backZoneMap?->zone;

        return view('area5s.editor', [
            'me' => $this->me(),
            'layout' => $layout,
            'points' => $this->pointsPayload($layout),
            // แถบตำแหน่งพื้นที่ (แสดงอย่างเดียว): โซน → อาคาร → ชั้น (Manager 2026-07-31)
            'trail' => [
                'zone' => $backZone?->name,
                'area' => $backArea?->name,
                'floor' => $backFloor?->name,
            ],
            'backUrl' => $backArea && $backZone
                ? route('area5s.manage.zone.area', ['zone' => $backZone->id, 'area' => $backArea->id])
                : route('area5s.manage'),
        ]);
    }

    /** จุดทั้งหมด + ผู้รับผิดชอบ (active) พร้อมชื่อจริง — ใช้ทั้ง editor และ viewer */
    private function pointsPayload(A5sLayout $layout, bool $activeOnly = false): array
    {
        $points = $layout->points()->when($activeOnly, fn ($q) => $q->where('is_active', true))->get();
        $pointIds = $points->pluck('id');
        $assignees = A5sPointAssignee::whereIn('point_id', $pointIds)->whereNull('removed_at')->get();
        $evaluatorScopes = A5sEvaluatorScope::where('layout_id', $layout->id)
            ->where(fn ($q) => $q->whereIn('point_id', $pointIds)->orWhereNull('point_id'))
            ->get();
        $employeeCodes = $assignees->pluck('employee_code')->merge($evaluatorScopes->pluck('employee_code'))->unique()->values();
        $names = Employee::active()->whereIn('employee_code', $employeeCodes)
            ->get()->keyBy('employee_code');
        $avatars = AppUser::whereIn('employee_code', $employeeCodes)
            ->pluck('profile_picture', 'employee_code');
        // ใช้รอบของ layout เอง (ไม่ใช่รอบเปิด) — viewer เดือนเก่าเห็นสถานะ/การ์ดของเดือนนั้นถูกต้อง
        $layoutRound = $layout->round_id ? A5sRound::find($layout->round_id) : A5sRound::open();
        $cardsByPointAndEmployee = $this->cardsByPointAndEmployee($pointIds, $layoutRound);
        $taskByPoint = $this->tasksByPoint($pointIds, $layoutRound);

        return $points->map(function (A5sPoint $p) use ($assignees, $names, $avatars, $cardsByPointAndEmployee, $evaluatorScopes, $taskByPoint) {
            $task = $taskByPoint->get($p->id);
            $showDetails = $task?->status === 'passed';

            return [
                'id' => $p->id,
                'code' => $p->code,
                'name' => $p->name,
                'description' => $p->description,
                'x' => (float) $p->x,
                'y' => (float) $p->y,
                'is_active' => $p->is_active,
                'status' => $task?->status ?? 'not_started',
                'status_label' => $this->publicStatusLabel($task),
                // key สำหรับ i18n: ยังไม่ส่งตรวจ (not_started/draft/ไม่มี task) = no_data (Manager 2026-07-18)
                'status_key' => $this->publicStatusKey($task),
                'status_class' => match ($task?->status) {
                    'passed' => 'is-pass',
                    'failed' => 'is-fail',
                    'submitted', 'resubmitted' => 'is-pending',
                    default => 'is-none',
                },
                // ประวัติการส่ง→ตรวจของจุด (รอบของ layout นี้) — เปิดใน modal ปุ่ม "ประวัติ" (Manager 2026-07-30)
                'history' => $this->a5sPointAttempts($task),
                'show_details' => $showDetails,
                'assignees' => $assignees->where('point_id', $p->id)->map(function ($a) use ($names, $avatars, $cardsByPointAndEmployee, $p, $showDetails) {
                    $employee = $names->get($a->employee_code);
                    $avatar = $avatars->get($a->employee_code);

                    return array_merge($this->a5sEmployeePayload($employee, $a->employee_code), [
                        'id' => $a->id,
                        'avatar' => $avatar ? asset('storage/'.$avatar) : null,
                        'cards' => $showDetails ? $cardsByPointAndEmployee
                            ->get($p->id, collect())
                            ->get($a->employee_code, collect())
                            ->values()
                            ->all() : [],
                    ]);
                })->values()->all(),
                'evaluators' => $evaluatorScopes
                    ->filter(fn ($scope) => $scope->point_id === null || (int) $scope->point_id === (int) $p->id)
                    ->unique('employee_code')
                    ->map(function ($scope) use ($names, $avatars) {
                        $employee = $names->get($scope->employee_code);
                        $avatar = $avatars->get($scope->employee_code);

                        return array_merge($this->a5sEmployeePayload($employee, $scope->employee_code), [
                            'id' => $scope->id,
                            'avatar' => $avatar ? asset('storage/'.$avatar) : null,
                        ]);
                    })->values()->all(),
            ];
        })->values()->all();
    }

    private function tasksByPoint($pointIds, ?A5sRound $round = null)
    {
        $round = $round ?: A5sRound::open();
        if (! $round || $pointIds->isEmpty()) {
            return collect();
        }

        return A5sTask::where('round_id', $round->id)
            ->whereIn('point_id', $pointIds)
            ->get()
            ->keyBy('point_id');
    }

    private function publicStatusLabel(?A5sTask $task): string
    {
        return match ($task?->status) {
            'passed' => 'ผ่าน',
            'failed' => 'ไม่ผ่าน',
            'submitted', 'resubmitted' => 'รอดำเนินการ',
            default => 'ยังไม่มีข้อมูล',
        };
    }

    /** key สถานะสำหรับ i18n — ยังไม่ส่งตรวจ (not_started/draft/ไม่มี task) = no_data */
    private function publicStatusKey(?A5sTask $task): string
    {
        return match ($task?->status) {
            'passed', 'failed', 'submitted', 'resubmitted' => $task->status,
            default => 'no_data',
        };
    }

    private function cardsByPointAndEmployee($pointIds, ?A5sRound $round = null)
    {
        $round = $round ?: A5sRound::open();
        if (! $round || $pointIds->isEmpty()) {
            return collect();
        }

        $tasks = A5sTask::where('round_id', $round->id)
            ->whereIn('point_id', $pointIds)
            ->get()
            ->keyBy('id');
        if ($tasks->isEmpty()) {
            return collect();
        }

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

                return [
                    'id' => $card->id,
                    'point_id' => $task?->point_id,
                    'employee_code' => $owner?->employee_code,
                    'title' => $card->title,
                    'detail' => $card->detail,
                    'created_at' => $card->created_at?->format('d/m/Y H:i'),
                    'images' => $card->images->map(fn (A5sCardImage $image) => asset('storage/'.$image->path))->values()->all(),
                ];
            })
            ->filter(fn (array $card) => $card['point_id'] !== null && $card['employee_code'])
            ->groupBy('point_id')
            ->map(fn ($pointCards) => $pointCards->groupBy('employee_code'));
    }

    public function pointStore(Request $request, A5sLayout $layout): JsonResponse
    {
        if (! $this->canEdit($layout)) {
            return response()->json(['ok' => false, 'message' => 'no permission'], 403);
        }

        $data = $request->validate([
            'x' => ['required', 'numeric', 'between:0,100'],
            'y' => ['required', 'numeric', 'between:0,100'],
        ]);

        // รหัสจุดถัดไป: A..Z, AA.. (ข้ามที่ใช้แล้ว รวม inactive)
        $used = $layout->points()->pluck('code')->map(fn ($c) => strtoupper($c))->all();
        $n = 0;
        do {
            $n++;
            $code = '';
            $k = $n;
            while ($k > 0) {
                $k--;
                $code = chr(65 + ($k % 26)).$code;
                $k = intdiv($k, 26);
            }
        } while (in_array($code, $used, true));

        $point = A5sPoint::create([
            'layout_id' => $layout->id,
            'code' => $code,
            'name' => 'จุด '.$code,
            'x' => round((float) $data['x'], 4),
            'y' => round((float) $data['y'], 4),
            'is_active' => true,
            'sort' => (int) $layout->points()->max('sort') + 1,
        ]);
        A5sActivityLog::write($this->me()->id, 'point.create', 'point', $point->id, ['layout' => $layout->name, 'code' => $code]);

        return response()->json(['ok' => true, 'point' => [
            'id' => $point->id, 'code' => $point->code, 'name' => $point->name, 'description' => null,
            'x' => (float) $point->x, 'y' => (float) $point->y, 'is_active' => true, 'assignees' => [], 'evaluators' => [],
        ]]);
    }

    public function pointUpdate(Request $request, A5sPoint $point): JsonResponse
    {
        if (! $this->canEdit($point->layout)) {
            return response()->json(['ok' => false, 'message' => 'no permission'], 403);
        }

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:191'],
            'x' => ['sometimes', 'numeric', 'between:0,100'],
            'y' => ['sometimes', 'numeric', 'between:0,100'],
        ]);

        if (array_key_exists('name', $data) && trim($data['name']) !== '') {
            $point->name = trim($data['name']);
        }
        if (array_key_exists('x', $data)) {
            $point->x = round((float) $data['x'], 4);
        }
        if (array_key_exists('y', $data)) {
            $point->y = round((float) $data['y'], 4);
        }
        $point->save();
        A5sActivityLog::write($this->me()->id, 'point.update', 'point', $point->id, ['code' => $point->code]);

        return response()->json(['ok' => true, 'point' => ['id' => $point->id, 'code' => $point->code, 'name' => $point->name]]);
    }

    public function pointDestroy(A5sPoint $point): JsonResponse
    {
        if (! $this->canEdit($point->layout)) {
            return response()->json(['ok' => false, 'message' => 'no permission'], 403);
        }

        if (A5sTask::where('point_id', $point->id)->exists()) {
            return response()->json([
                'ok' => false,
                'message' => 'จุดนี้มีประวัติรอบรายเดือนแล้ว จึงลบไม่ได้',
            ], 422);
        }

        A5sActivityLog::write($this->me()->id, 'point.delete', 'point', $point->id, [
            'layout' => $point->layout?->name,
            'code' => $point->code,
            'name' => $point->name,
        ]);
        $point->delete();

        return response()->json(['ok' => true]);
    }

    public function pointToggle(A5sPoint $point): JsonResponse
    {
        if (! $this->canEdit($point->layout)) {
            return response()->json(['ok' => false, 'message' => 'no permission'], 403);
        }

        $point->is_active = ! $point->is_active;
        $point->save();
        A5sActivityLog::write($this->me()->id, $point->is_active ? 'point.enable' : 'point.disable', 'point', $point->id, ['code' => $point->code]);

        return response()->json(['ok' => true, 'is_active' => $point->is_active]);
    }

    // ===== ผู้รับผิดชอบจุด =====

    /** ค้นหาพนักงานจาก employees (Bplus mirror): รหัส / ชื่อ / แผนก / ตำแหน่ง */
    public function employeeSearch(Request $request): JsonResponse
    {
        if (! ($this->isA5sAdmin() || $this->isAllocator())) {
            return response()->json(['ok' => false, 'message' => 'no permission'], 403);
        }

        $q = trim((string) $request->query('q', ''));
        $rows = Employee::active()
            ->when($q !== '', fn ($query) => $query->where(fn ($w) => $w
                ->where('employee_code', 'like', "%{$q}%")
                ->orWhere('name_th', 'like', "%{$q}%")
                ->orWhere('surname_th', 'like', "%{$q}%")
                ->orWhere('name_en', 'like', "%{$q}%")
                ->orWhere('job_th', 'like', "%{$q}%")
                ->orWhere('dept_th', 'like', "%{$q}%")))
            ->orderBy('employee_code')->limit(20)->get();

        $avatars = AppUser::whereIn('employee_code', $rows->pluck('employee_code')->unique())
            ->pluck('profile_picture', 'employee_code');

        return response()->json(['ok' => true, 'employees' => $rows->map(function ($e) use ($avatars) {
            $avatar = $avatars->get($e->employee_code);

            return array_merge($this->a5sEmployeePayload($e, $e->employee_code), [
                'avatar' => $avatar ? asset('storage/'.$avatar) : null,
            ]);
        })->values()]);
    }

    public function assigneeAdd(Request $request, A5sPoint $point): JsonResponse
    {
        if (! $this->canEdit($point->layout)) {
            return response()->json(['ok' => false, 'message' => 'no permission'], 403);
        }

        $data = $request->validate(['employee_code' => ['required', 'string', 'max:20']]);
        $emp = Employee::active()->where('employee_code', $data['employee_code'])->first();
        if (! $emp) {
            return response()->json(['ok' => false, 'message' => 'ไม่พบพนักงาน'], 422);
        }

        $exists = A5sPointAssignee::where('point_id', $point->id)
            ->where('employee_code', $emp->employee_code)->whereNull('removed_at')->exists();
        if ($exists) {
            return response()->json(['ok' => false, 'message' => 'พนักงานคนนี้รับผิดชอบจุดนี้อยู่แล้ว'], 422);
        }

        $assignee = A5sPointAssignee::create([
            'point_id' => $point->id,
            'employee_code' => $emp->employee_code,
            'assigned_by' => $this->me()->id,
            'assigned_at' => now(),
        ]);
        A5sActivityLog::write($this->me()->id, 'assignee.add', 'point', $point->id, ['code' => $point->code, 'employee' => $emp->employee_code]);
        $this->a5sSyncTaskPeople($point);   // สำเนาใน task ต้องตรงทันที (รายงาน/คิวตรวจ/แจ้งเตือน)
        $avatar = AppUser::where('employee_code', $emp->employee_code)->value('profile_picture');

        return response()->json(['ok' => true, 'assignee' => array_merge($this->a5sEmployeePayload($emp, $emp->employee_code), [
            'id' => $assignee->id,
            'avatar' => $avatar ? asset('storage/'.$avatar) : null,
        ])]);
    }

    /** ถอดผู้รับผิดชอบ — set removed_at (ledger ประวัติไม่หาย) */
    public function assigneeRemove(A5sPointAssignee $assignee): JsonResponse
    {
        $point = A5sPoint::find($assignee->point_id);
        if (! $point || ! $this->canEdit($point->layout)) {
            return response()->json(['ok' => false, 'message' => 'no permission'], 403);
        }

        $assignee->removed_at = now();
        $assignee->removed_by = $this->me()->id;
        $assignee->save();
        A5sActivityLog::write($this->me()->id, 'assignee.remove', 'point', $point->id, ['code' => $point->code, 'employee' => $assignee->employee_code]);
        $this->a5sSyncTaskPeople($point);

        return response()->json(['ok' => true]);
    }

    public function evaluatorAdd(Request $request, A5sPoint $point): JsonResponse
    {
        if (! $this->canEdit($point->layout)) {
            return response()->json(['ok' => false, 'message' => 'no permission'], 403);
        }

        $data = $request->validate(['employee_code' => ['required', 'string', 'max:20']]);
        $emp = Employee::active()->where('employee_code', $data['employee_code'])->first();
        if (! $emp) {
            return response()->json(['ok' => false, 'message' => 'ไม่พบพนักงาน'], 422);
        }

        $exists = A5sEvaluatorScope::where('layout_id', $point->layout_id)
            ->where('point_id', $point->id)
            ->where('employee_code', $emp->employee_code)
            ->exists();
        if ($exists) {
            return response()->json(['ok' => false, 'message' => 'พนักงานคนนี้เป็นผู้ประเมินจุดนี้อยู่แล้ว'], 422);
        }

        $scope = A5sEvaluatorScope::create([
            'employee_code' => $emp->employee_code,
            'layout_id' => $point->layout_id,
            'point_id' => $point->id,
            'created_by' => $this->me()->id,
        ]);
        A5sActivityLog::write($this->me()->id, 'evaluator.add', 'point', $point->id, ['code' => $point->code, 'employee' => $emp->employee_code]);
        $this->a5sSyncTaskPeople($point);
        $avatar = AppUser::where('employee_code', $emp->employee_code)->value('profile_picture');

        return response()->json(['ok' => true, 'evaluator' => array_merge($this->a5sEmployeePayload($emp, $emp->employee_code), [
            'id' => $scope->id,
            'avatar' => $avatar ? asset('storage/'.$avatar) : null,
        ])]);
    }

    public function evaluatorRemove(A5sEvaluatorScope $scope): JsonResponse
    {
        $layout = A5sLayout::find($scope->layout_id);
        if (! $layout || ! $this->canEdit($layout)) {
            return response()->json(['ok' => false, 'message' => 'no permission'], 403);
        }

        A5sActivityLog::write($this->me()->id, 'evaluator.remove', $scope->point_id ? 'point' : 'layout', $scope->point_id ?: $scope->layout_id, [
            'employee' => $scope->employee_code,
        ]);
        $pointId = $scope->point_id;
        $scope->delete();

        // scope ครอบทั้ง Layout (point_id = null) กระทบทุกจุด จึงต้องซิงก์ทั้งใบ
        if ($pointId && $point = A5sPoint::find($pointId)) {
            $this->a5sSyncTaskPeople($point);
        } else {
            $this->a5sSyncLayoutTaskPeople($layout);
        }

        return response()->json(['ok' => true]);
    }

    // ===== Viewer (ทุกคนดูได้) =====

    public function show(Request $request, A5sLayout $layout): ViewContract|RedirectResponse
    {
        if ($redirect = $this->gateEnter()) {
            return $redirect;
        }
        if (! $layout->is_active && ! $this->canEdit($layout)) {
            return $this->denyToSystems('toast.pageDenied', 'พื้นที่นี้ถูกปิดใช้งาน');
        }

        $myCode = trim((string) ($this->me()->employee_code ?? ''));

        // ปุ่ม "กลับ" ให้ย้อนไปหน้าพื้นที่ย่อย/อาคารที่ Layout นี้อยู่ (ถ้ายังไม่ผูกชั้น = กลับภาพรวม)
        $layout->loadMissing('floor.area');
        $backArea = $layout->floor?->area;
        $backUrl = $backArea
            ? route('area5s.areas.show', ['area' => $backArea->id, 'round' => $layout->round_id])
            : route('area5s.index');

        /* เลือกเดือน/วันที่ตรวจได้จากหน้านี้ (Manager 2026-08-27)
           🔴 Layout ถูก clone ต่อรอบ — เปลี่ยนรอบ = ย้ายไป layout พี่น้องที่ floor_id + name เดียวกัน
           รอบไหนไม่มีใบของพื้นที่นี้ (เช่น สร้างทีหลัง) จะไม่ขึ้นในตัวเลือก */
        $siblings = A5sLayout::where('floor_id', $layout->floor_id)
            ->where('name', $layout->name)
            ->pluck('id', 'round_id');
        $rounds = A5sRound::whereIn('id', $siblings->keys())
            ->orderByDesc('year')->orderByDesc('month')->orderBy('seq')
            ->get();
        $openRound = A5sRound::open();
        $viewRound = $rounds->firstWhere('id', $layout->round_id);
        $roundNav = $this->a5sRoundNav($rounds, $viewRound, $openRound);
        // URL ของแต่ละครั้งตรวจต้องชี้ไป layout ของรอบนั้น ไม่ใช่ ?round= บน layout เดิม
        $roundNav['months'] = collect($roundNav['months'])->map(function (array $month) use ($siblings) {
            $month['inspections'] = collect($month['inspections'])->map(function (array $inspection) use ($siblings) {
                $targetId = $siblings->get($inspection['id']);
                $inspection['url'] = $targetId ? route('area5s.layouts.show', ['layout' => $targetId]) : null;

                return $inspection;
            })->filter(fn (array $inspection) => $inspection['url'] !== null)->values()->all();

            return $month;
        })->filter(fn (array $month) => count($month['inspections']) > 0)->values()->all();

        $points = $this->pointsPayload($layout, true);
        $calendars = $this->a5sPointCalendars(collect($points)->pluck('id'));
        $taskIds = A5sTask::where('round_id', $layout->round_id)
            ->whereIn('point_id', collect($points)->pluck('id'))
            ->pluck('id', 'point_id');
        $scores = A5sScoreService::taskScores($taskIds->values());
        $points = collect($points)->map(function (array $point) use ($calendars, $taskIds, $scores) {
            $taskId = (int) ($taskIds[$point['id']] ?? 0);
            $score = $taskId ? ($scores[$taskId] ?? null) : null;

            return array_merge($point, [
                'score' => $score,
                'score_label' => A5sScoreService::label($score),
                'calendar' => $calendars[(int) $point['id']] ?? ['score' => null, 'months' => []],
            ]);
        })->values()->all();

        /* ประวัติการส่งของแต่ละจุด (รอบนี้) สำหรับ partials/history-modal — โครงเดียวกับหน้าตรวจประเมิน
           ดึงจาก calendar ที่คำนวณไว้แล้ว ไม่ต้อง query ซ้ำ (Manager 2026-08-27) */
        $historyDetails = collect($points)->mapWithKeys(function (array $point) use ($layout) {
            $round = collect($point['calendar']['months'] ?? [])
                ->flatMap(fn (array $month) => $month['rounds'] ?? [])
                ->firstWhere('round_id', (int) $layout->round_id);

            return [(int) $point['id'] => [
                'history' => collect($round['attempts'] ?? [])->values()->map(fn (array $attempt, int $index) => [
                    'seq' => $index + 1,
                    'state' => match ($attempt['state'] ?? '') {
                        'pass' => 'passed',
                        'fail' => 'failed',
                        default => 'pending',
                    },
                    'submitted_at' => $attempt['submitted_at'] ?? null,
                    'evaluated_at' => $attempt['evaluated_at'] ?? null,
                    'submitter' => $attempt['submitter'] ? ['name' => $attempt['submitter']] : null,
                    'evaluator' => $attempt['evaluator'] ? ['name' => $attempt['evaluator']] : null,
                    'cards' => $attempt['cards'] ?? [],
                    'note' => $attempt['note'] ?? null,
                ])->all(),
            ]];
        })->all();

        $requestedPoint = (int) $request->query('point');
        $selectedPoint = collect($points)->firstWhere('id', $requestedPoint)
            ?: collect($points)->first();

        return view('area5s.show', [
            'me' => $this->me(),
            'layout' => $layout,
            'points' => $points,
            'myCode' => $myCode,
            'canEdit' => $this->canEdit($layout),
            'backUrl' => $backUrl,
            'backToArea' => (bool) $backArea,
            'roundNav' => $roundNav,
            'viewRoundLabel' => $viewRound ? $this->a5sRoundLabel($viewRound) : null,
            'selectedPointId' => (int) ($selectedPoint['id'] ?? 0),
            'historyDetails' => $historyDetails,
        ]);
    }
}
