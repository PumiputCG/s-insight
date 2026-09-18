<?php

namespace App\Http\Controllers\Area5s;

use App\Http\Controllers\Area5s\Concerns\HandlesArea5sAccess;
use App\Http\Controllers\Controller;
use App\Models\Area5s\A5sActivityLog;
use App\Models\Area5s\A5sEvaluatorScope;
use App\Models\Area5s\A5sLayout;
use App\Models\Area5s\A5sMember;
use App\Models\Area5s\A5sPoint;
use App\Models\Area5s\A5sPointAssignee;
use App\Models\Area5s\A5sRound;
use App\Models\Area5s\A5sZoneMapArea;
use App\Models\Insight\AppUser;
use App\Models\Insight\Employee;
use App\Models\Insight\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View as ViewContract;

/**
 * ตั้งค่าระบบ SUPAVUT 5S AREA (เฉพาะ admin) — แบบเดียวกับ Assessment
 *
 * 1) มอบบทบาท admin / allocator / evaluator ให้พนักงาน (a5s_members)
 * 2) เลือกตำแหน่ง (job_code จาก employees) ที่อนุญาตให้เข้าระบบ
 */
class Area5sSettingController extends Controller
{
    use HandlesArea5sAccess;

    public function index(): ViewContract|RedirectResponse
    {
        if ($redirect = $this->gateAdmin()) {
            return $redirect;
        }

        $members = A5sMember::orderByDesc('id')->get();
        $appUsers = AppUser::whereIn('id', $members->pluck('app_user_id'))->get()->keyBy('id');

        return view('area5s.settings', [
            'me' => $this->me(),
            'members' => $members->map(function (A5sMember $m) use ($appUsers) {
                $user = $appUsers->get($m->app_user_id);
                $person = $this->a5sAppUserPayload($user, $m->employee_code);
                if (! $user && $m->display_name) {
                    $person['name'] = $m->display_name;
                    $person['name_th'] = $m->display_name;
                    $person['name_en'] = $m->display_name;
                    $person['name_my'] = $m->display_name;
                }

                return array_merge($person, [
                    'id' => $m->id,
                    'app_user_id' => $m->app_user_id,
                    'position' => $m->position ?: $person['position'],
                    'role' => $m->role,
                    'avatar' => ($pic = $user?->profile_picture) ? asset('storage/'.$pic) : null,
                ]);
            })->values()->all(),
            'assignmentAreas' => $this->assignmentAreas(),
            'positions' => $this->positionList(),
            'allowed' => Setting::get(Setting::AREA5S_POSITIONS, null),
        ]);
    }

    /** พื้นที่ย่อย/อาคาร -> ชั้น -> Layout -> จุด สำหรับหน้าแอดมิน (เฉพาะรอบที่เปิดอยู่) */
    private function assignmentAreas(): array
    {
        $availableAreas = A5sZoneMapArea::with([
            'zoneMap.zone',
            'floors' => fn ($query) => $query->where('is_active', true)->orderBy('sort')->orderBy('name'),
        ])
            ->where('is_active', true)
            ->whereHas('zoneMap', fn ($query) => $query
                ->where('is_active', true)
                ->whereHas('zone', fn ($zoneQuery) => $zoneQuery
                    ->where('is_active', true)
                    ->whereHas('plan', fn ($planQuery) => $planQuery->where('is_active', true))))
            ->get();
        $layouts = A5sLayout::with(['points', 'floor.area.zoneMap.zone', 'floor.zone'])
            ->where('round_id', (int) A5sRound::open()?->id)
            ->get();
        $points = $layouts->flatMap(fn (A5sLayout $layout) => $layout->points);

        $assignees = $points->isEmpty()
            ? collect()
            : A5sPointAssignee::whereIn('point_id', $points->pluck('id'))
                ->whereNull('removed_at')
                ->orderBy('id')
                ->get();
        $scopes = $layouts->isEmpty()
            ? collect()
            : A5sEvaluatorScope::whereIn('layout_id', $layouts->pluck('id'))
                ->orderBy('id')
                ->get();

        $codes = $assignees->pluck('employee_code')->merge($scopes->pluck('employee_code'))->unique()->values();
        $employees = $codes->isEmpty()
            ? collect()
            : Employee::active()->whereIn('employee_code', $codes)->get()->keyBy('employee_code');
        $avatars = $codes->isEmpty()
            ? collect()
            : AppUser::whereIn('employee_code', $codes)->pluck('profile_picture', 'employee_code');

        $person = function (string $code, int $id, array $extra = []) use ($employees, $avatars) {
            return array_merge($this->a5sEmployeePayload($employees->get($code), $code), [
                'id' => $id,
                'avatar' => ($pic = $avatars->get($code)) ? asset('storage/'.$pic) : null,
            ], $extra);
        };

        $layoutPayload = fn (A5sLayout $layout) => [
            'id' => (int) $layout->id,
            'name' => $layout->name,
            'image_path' => $layout->image_path,
            'is_active' => (bool) $layout->is_active,
            'points' => $layout->points->map(fn (A5sPoint $point) => [
                'id' => $point->id,
                'code' => $point->code,
                'name' => $point->name,
                'is_active' => $point->is_active,
                'assignees' => $assignees->where('point_id', $point->id)
                    ->map(fn (A5sPointAssignee $a) => $person($a->employee_code, (int) $a->id))
                    ->values()->all(),
                // ผู้ประเมินที่ครอบจุดนี้: scope เจาะจุด + scope ทั้ง layout (layout_wide)
                'evaluators' => $scopes
                    ->filter(fn ($s) => (int) $s->layout_id === (int) $layout->id
                        && ($s->point_id === null || (int) $s->point_id === (int) $point->id))
                    ->map(fn ($s) => $person($s->employee_code, (int) $s->id, ['layout_wide' => $s->point_id === null]))
                    ->values()->all(),
            ])->values()->all(),
        ];

        $areas = [];
        foreach ($availableAreas as $area) {
            $zoneMap = $area->zoneMap;
            $zone = $zoneMap?->zone;
            $areaKey = 'area-'.$area->id;
            $areas[$areaKey] = [
                'id' => (string) $area->id,
                'name' => $area->name,
                'zone_name' => $zone?->name,
                'color' => $area->color ?: '#8f9d72',
                'is_active' => true,
                'zone_sort' => (int) ($zone?->sort ?? PHP_INT_MAX),
                'zone_map_sort' => (int) ($zoneMap?->sort ?? PHP_INT_MAX),
                'sort' => (int) ($area->sort ?? PHP_INT_MAX),
                'floors' => [],
            ];

            foreach ($area->floors as $floor) {
                $areas[$areaKey]['floors']['floor-'.$floor->id] = [
                    'id' => (string) $floor->id,
                    'name' => $floor->name,
                    'is_active' => true,
                    'sort' => (int) ($floor->sort ?? PHP_INT_MAX),
                    'layouts' => [],
                ];
            }
        }

        foreach ($layouts as $layout) {
            $floor = $layout->floor;
            $area = $floor?->area;
            $zoneMap = $area?->zoneMap;
            $zone = $zoneMap?->zone ?? $floor?->zone;
            $areaKey = $area ? 'area-'.$area->id : 'unmapped';
            $floorKey = $floor ? 'floor-'.$floor->id : 'unmapped';

            $areas[$areaKey] ??= [
                'id' => $area ? (string) $area->id : 'unmapped',
                'name' => $area?->name ?? 'ยังไม่จัดพื้นที่',
                'zone_name' => $zone?->name,
                'color' => $area?->color ?: '#8f9d72',
                'is_active' => $area
                    ? (bool) ($area->is_active && $zoneMap?->is_active && $zone?->is_active)
                    : true,
                'zone_sort' => (int) ($zone?->sort ?? PHP_INT_MAX),
                'zone_map_sort' => (int) ($zoneMap?->sort ?? PHP_INT_MAX),
                'sort' => (int) ($area?->sort ?? PHP_INT_MAX),
                'floors' => [],
            ];

            $areas[$areaKey]['floors'][$floorKey] ??= [
                'id' => $floor ? (string) $floor->id : 'unmapped',
                'name' => $floor?->name ?? 'ยังไม่จัดชั้น',
                'is_active' => (bool) ($floor?->is_active ?? true),
                'sort' => (int) ($floor?->sort ?? PHP_INT_MAX),
                'layouts' => [],
            ];
            $areas[$areaKey]['floors'][$floorKey]['layouts'][] = $layoutPayload($layout);
        }

        return collect($areas)
            ->sort(fn (array $a, array $b) => [
                $a['zone_sort'],
                $a['zone_map_sort'],
                $a['sort'],
                $a['name'],
            ] <=> [
                $b['zone_sort'],
                $b['zone_map_sort'],
                $b['sort'],
                $b['name'],
            ])
            ->map(function (array $area) {
                $area['floors'] = collect($area['floors'])
                    ->sort(fn (array $a, array $b) => [$a['sort'], $a['name']] <=> [$b['sort'], $b['name']])
                    ->map(function (array $floor) {
                        $floor['layouts'] = collect($floor['layouts'])
                            ->sort(fn (array $a, array $b) => $a['id'] <=> $b['id'])
                            ->values()
                            ->all();
                        $floor['layout_count'] = count($floor['layouts']);
                        unset($floor['sort']);

                        return $floor;
                    })
                    ->values()
                    ->all();
                $area['floor_count'] = count($area['floors']);
                $area['layout_count'] = collect($area['floors'])->sum('layout_count');
                unset($area['zone_sort'], $area['zone_map_sort'], $area['sort']);

                return $area;
            })
            ->values()
            ->all();
    }

    /** ตำแหน่งทั้งหมด (job_code) จาก employees */
    private function positionList(): array
    {
        return Employee::active()
            ->selectRaw('job_code as code, MAX(job_th) as th, MAX(job_en) as en, COUNT(*) as cnt')
            ->whereNotNull('job_code')->where('job_code', '!=', '')
            ->groupBy('job_code')
            ->orderBy('th')
            ->get()
            ->map(fn ($p) => [
                'code' => $p->code,
                'name' => $p->th ?: $p->en ?: $p->code,
                'name_th' => $p->th ?: $p->en ?: $p->code,
                'name_en' => $p->en ?: $p->th ?: $p->code,
                'name_my' => $p->en ?: $p->th ?: $p->code,
                'count' => (int) $p->cnt,
            ])
            ->values()->all();
    }

    /** ค้นหาพนักงานจาก app_users (อ่านอย่างเดียว) */
    public function searchUsers(Request $request): JsonResponse
    {
        if (! $this->isA5sAdmin()) {
            return response()->json(['ok' => false, 'message' => 'no permission'], 403);
        }

        $q = trim((string) $request->query('q', ''));

        $users = AppUser::query()
            ->where('employee_code', '!=', 'Admin')
            ->when($q !== '', fn ($query) => $query->where(fn ($w) => $w
                ->where('full_name_th', 'like', "%{$q}%")
                ->orWhere('full_name_en', 'like', "%{$q}%")
                ->orWhere('employee_code', 'like', "%{$q}%")))
            ->orderBy('full_name_th')->limit(25)->get();

        return response()->json([
            'users' => $users->map(fn (AppUser $u) => array_merge($this->a5sAppUserPayload($u, $u->employee_code), [
                'app_user_id' => $u->id,
                'avatar' => $u->profile_picture ? asset('storage/'.$u->profile_picture) : null,
            ]))->values(),
        ]);
    }

    /** มอบบทบาท (admin/allocator/evaluator) ให้พนักงาน */
    public function addMember(Request $request): JsonResponse
    {
        if (! $this->isA5sAdmin()) {
            return response()->json(['ok' => false, 'message' => 'no permission'], 403);
        }

        $data = $request->validate([
            'app_user_id' => ['required', 'integer'],
            'role' => ['required', 'in:'.implode(',', A5sMember::ROLES)],
        ]);

        $user = AppUser::find($data['app_user_id']);
        if (! $user || $user->employee_code === 'Admin') {
            return response()->json(['ok' => false, 'message' => 'ไม่พบพนักงาน'], 422);
        }

        $member = A5sMember::firstOrCreate(
            ['app_user_id' => $user->id, 'role' => $data['role']],
            [
                'employee_code' => $user->employee_code,
                'display_name' => $user->fullNameTh() ?: $user->full_name_en ?: $user->employee_code,
                'position' => $user->position ?: null,
                'assigned_by' => $this->me()->id,
            ],
        );
        if ($member->wasRecentlyCreated) {
            A5sActivityLog::write($this->me()->id, 'member.add', 'member', $member->id, ['role' => $member->role, 'code' => $member->employee_code]);
        }

        $person = $this->a5sAppUserPayload($user, $user->employee_code);

        return response()->json([
            'ok' => true,
            'member' => [
                'id' => $member->id,
                'app_user_id' => $member->app_user_id,
                'code' => $member->employee_code,
                'name' => $person['name'],
                'name_th' => $person['name_th'],
                'name_en' => $person['name_en'],
                'name_my' => $person['name_my'],
                'position' => $member->position ?: $person['position'],
                'position_th' => $member->position ?: $person['position_th'],
                'position_en' => $person['position_en'] ?: ($member->position ?: ''),
                'position_my' => $person['position_my'] ?: ($member->position ?: ''),
                'role' => $member->role,
                'avatar' => $user->profile_picture ? asset('storage/'.$user->profile_picture) : null,
            ],
        ]);
    }

    /** ถอนบทบาท (ประวัติงานเดิมไม่หาย — snapshot อยู่ใน tasks/log) */
    public function removeMember(A5sMember $member): JsonResponse
    {
        if (! $this->isA5sAdmin()) {
            return response()->json(['ok' => false, 'message' => 'no permission'], 403);
        }

        A5sActivityLog::write($this->me()->id, 'member.remove', 'member', $member->id, ['role' => $member->role, 'code' => $member->employee_code]);
        $member->delete();

        return response()->json(['ok' => true]);
    }

    /** บันทึกตำแหน่งที่อนุญาตเข้าระบบ */
    public function savePositions(Request $request): JsonResponse
    {
        if (! $this->isA5sAdmin()) {
            return response()->json(['ok' => false, 'message' => 'no permission'], 403);
        }

        $data = $request->validate([
            'job_codes' => ['array'],
            'job_codes.*' => ['string'],
        ]);

        Setting::put(Setting::AREA5S_POSITIONS, array_values(array_unique($data['job_codes'] ?? [])));

        return response()->json(['ok' => true]);
    }
}
