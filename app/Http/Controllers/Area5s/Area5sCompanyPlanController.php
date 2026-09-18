<?php

namespace App\Http\Controllers\Area5s;

use App\Http\Controllers\Area5s\Concerns\HandlesArea5sAccess;
use App\Http\Controllers\Controller;
use App\Models\Area5s\A5sActivityLog;
use App\Models\Area5s\A5sCompanyPlan;
use App\Models\Area5s\A5sFloor;
use App\Models\Area5s\A5sLayout;
use App\Models\Area5s\A5sZone;
use App\Models\Area5s\A5sZoneMap;
use App\Models\Area5s\A5sZoneMapArea;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View as ViewContract;

/**
 * แปลนบริษัท → โซน/อาคาร → ชั้น (Admin เท่านั้น) — ถาวร ไม่ผูกรอบเดือน
 * ผู้จัดสรรพื้นที่ "ดู" ได้ผ่านหน้าเลือกโซน (Area5sZonePickController) แต่แก้ไม่ได้
 */
class Area5sCompanyPlanController extends Controller
{
    use HandlesArea5sAccess;

    // ===== แปลนบริษัท + วาดโซน + จัดการชั้น (หน้าเดียว auto-save ทุกจุด) =====

    public function index(): ViewContract|RedirectResponse
    {
        if ($redirect = $this->gateAdmin()) {
            return $redirect;
        }

        $plan = A5sCompanyPlan::where('is_active', true)->orderByDesc('id')->first();

        return view('area5s.plan.index', [
            'me' => $this->me(),
            'plan' => $plan,
            'zones' => $plan ? $this->zonesPayload($plan) : [],
        ]);
    }

    private function zonesPayload(A5sCompanyPlan $plan): array
    {
        return $plan->zones()->with(['floors', 'zoneMaps.areas.floors'])->get()->map(fn (A5sZone $z) => [
            'id' => $z->id,
            'name' => $z->name,
            'description' => $z->description,
            'shape_points' => $z->shape_points,
            'color' => $z->color,
            'is_active' => $z->is_active,
            'floors' => $z->floors->map(fn (A5sFloor $f) => $this->floorPayload($f))->values()->all(),
            'zone_maps' => $z->zoneMaps->map(fn (A5sZoneMap $m) => $this->zoneMapPayload($m))->values()->all(),
        ])->values()->all();
    }

    private function zoneMapPayload(A5sZoneMap $map): array
    {
        $map->loadMissing('areas.floors');

        return [
            'id' => $map->id,
            'zone_id' => $map->zone_id,
            'name' => $map->name,
            'image_path' => $map->image_path,
            'image_url' => asset('storage/'.$map->image_path),
            'is_active' => $map->is_active,
            'areas' => $map->areas->map(fn (A5sZoneMapArea $a) => $this->zoneMapAreaPayload($a))->values()->all(),
        ];
    }

    private function zoneMapAreaPayload(A5sZoneMapArea $area): array
    {
        $area->loadMissing('floors');

        return [
            'id' => $area->id,
            'zone_map_id' => $area->zone_map_id,
            'name' => $area->name,
            'description' => $area->description,
            'shape_points' => $area->shape_points,
            'color' => $area->color,
            'is_active' => $area->is_active,
            'floors' => $area->floors->map(fn (A5sFloor $f) => $this->floorPayload($f))->values()->all(),
        ];
    }

    private function floorPayload(A5sFloor $floor): array
    {
        return [
            'id' => $floor->id,
            'name' => $floor->name,
            'is_active' => $floor->is_active,
            'zone_map_area_id' => $floor->zone_map_area_id,
        ];
    }

    /** อัปโหลดภาพแปลน (ครั้งแรก หรือเปลี่ยนภาพ — จุด/โซนเดิมคงพิกัด % ไว้) */
    public function storePlan(Request $request): RedirectResponse
    {
        if ($redirect = $this->gateAdmin()) {
            return $redirect;
        }

        $data = $request->validate([
            'name' => ['nullable', 'string', 'max:191'],
            'image' => ['required', 'image', 'max:8192'],
        ]);

        $existing = A5sCompanyPlan::where('is_active', true)->orderByDesc('id')->first();
        $path = $request->file('image')->store('area5s/plans', 'public');

        if ($existing) {
            $existing->image_path = $path;
            if (! empty($data['name'])) {
                $existing->name = trim($data['name']);
            }
            $existing->save();
            A5sActivityLog::write($this->me()->id, 'plan.image', 'plan', $existing->id, []);
        } else {
            $plan = A5sCompanyPlan::create([
                'name' => trim((string) ($data['name'] ?? '')) ?: 'แปลนบริษัท',
                'image_path' => $path,
                'created_by' => $this->me()->id,
                'is_active' => true,
            ]);
            A5sActivityLog::write($this->me()->id, 'plan.create', 'plan', $plan->id, []);
        }

        return redirect()->route('area5s.plan.index')->with('success', 'บันทึกภาพแปลนแล้ว');
    }

    // ===== โซน (polygon) =====

    public function zoneStore(Request $request, A5sCompanyPlan $plan): JsonResponse
    {
        if ($redirect = $this->gateAdmin()) {
            return response()->json(['ok' => false, 'message' => 'no permission'], 403);
        }

        $data = $request->validate([
            'shape_points' => ['required', 'array', 'min:3'],
            'shape_points.*.x' => ['required', 'numeric', 'between:0,100'],
            'shape_points.*.y' => ['required', 'numeric', 'between:0,100'],
        ]);

        $palette = ['#5b7343', '#2f6ba8', '#c8964a', '#a3475f', '#3f8f8a', '#7a5aa8'];
        $n = $plan->zones()->count();

        $zone = A5sZone::create([
            'company_plan_id' => $plan->id,
            'name' => 'โซน '.($n + 1),
            'shape_points' => $data['shape_points'],
            'color' => $palette[$n % count($palette)],
            'sort' => $n + 1,
            'is_active' => true,
            'created_by' => $this->me()->id,
        ]);
        A5sActivityLog::write($this->me()->id, 'zone.create', 'zone', $zone->id, []);

        return response()->json(['ok' => true, 'zone' => [
            'id' => $zone->id, 'name' => $zone->name, 'description' => null,
            'shape_points' => $zone->shape_points, 'color' => $zone->color,
            'is_active' => true, 'floors' => [], 'zone_maps' => [],
        ]]);
    }

    public function zoneUpdate(Request $request, A5sZone $zone): JsonResponse
    {
        if ($redirect = $this->gateAdmin()) {
            return response()->json(['ok' => false, 'message' => 'no permission'], 403);
        }

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:191'],
            'description' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'color' => ['sometimes', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'shape_points' => ['sometimes', 'array', 'min:3'],
            'shape_points.*.x' => ['numeric', 'between:0,100'],
            'shape_points.*.y' => ['numeric', 'between:0,100'],
        ]);

        if (array_key_exists('name', $data) && trim($data['name']) !== '') {
            $zone->name = trim($data['name']);
        }
        if (array_key_exists('description', $data)) {
            $zone->description = trim((string) ($data['description'] ?? '')) ?: null;
        }
        if (array_key_exists('color', $data)) {
            $zone->color = strtolower($data['color']);
        }
        if (array_key_exists('shape_points', $data)) {
            $zone->shape_points = $data['shape_points'];
        }
        $zone->save();
        A5sActivityLog::write($this->me()->id, 'zone.update', 'zone', $zone->id, ['name' => $zone->name]);

        return response()->json(['ok' => true]);
    }

    public function zoneDestroy(A5sZone $zone): JsonResponse
    {
        if ($redirect = $this->gateAdmin()) {
            return response()->json(['ok' => false, 'message' => 'no permission'], 403);
        }

        $floorIds = $zone->floors()->pluck('id');
        if ($floorIds->isNotEmpty() && A5sLayout::whereIn('floor_id', $floorIds)->exists()) {
            return response()->json([
                'ok' => false,
                'message' => 'โซนนี้มีพื้นที่ผูกอยู่แล้ว ให้กด "รีเซ็ตโซน" เพื่อถอดพื้นที่ออกก่อน แล้วค่อยลบโซนนี้',
            ], 422);
        }

        A5sActivityLog::write($this->me()->id, 'zone.delete', 'zone', $zone->id, ['name' => $zone->name]);
        $zone->zoneMaps()->get()->each(fn (A5sZoneMap $map) => Storage::disk('public')->delete($map->image_path));
        $zone->delete();

        return response()->json(['ok' => true]);
    }

    public function zoneReset(A5sZone $zone): JsonResponse
    {
        if ($redirect = $this->gateAdmin()) {
            return response()->json(['ok' => false, 'message' => 'no permission'], 403);
        }

        $floorIds = $zone->floors()->pluck('id');
        $layoutCount = 0;
        $floorCount = $floorIds->count();

        DB::connection('mysql_area5s')->transaction(function () use ($zone, $floorIds, &$layoutCount, &$floorCount) {
            if ($floorIds->isNotEmpty()) {
                $layoutCount = A5sLayout::whereIn('floor_id', $floorIds)->update(['floor_id' => null]);
                $floorCount = A5sFloor::whereIn('id', $floorIds)->delete();
            }

            A5sActivityLog::write($this->me()->id, 'zone.reset', 'zone', $zone->id, [
                'name' => $zone->name,
                'layouts_unmapped' => $layoutCount,
                'floors_deleted' => $floorCount,
            ]);
        });

        return response()->json([
            'ok' => true,
            'layouts_unmapped' => $layoutCount,
            'floors_deleted' => $floorCount,
            'message' => "รีเซ็ตโซนแล้ว: ถอดพื้นที่ {$layoutCount} รายการ และลบชั้น {$floorCount} รายการ ตอนนี้สามารถลบโซนนี้ได้",
        ]);
    }

    // ===== ชั้น (ใต้โซน) =====

    // ===== Sub-zone images + drawable areas =====

    public function zoneMapStore(Request $request, A5sZone $zone): JsonResponse
    {
        if ($redirect = $this->gateAdmin()) {
            return response()->json(['ok' => false, 'message' => 'no permission'], 403);
        }

        $data = $request->validate([
            'name' => ['nullable', 'string', 'max:191'],
            'image' => ['required', 'image', 'max:12288'],
        ]);

        $sort = (int) $zone->zoneMaps()->max('sort') + 1;
        $name = trim((string) ($data['name'] ?? ''));
        if ($name === '') {
            $name = pathinfo($request->file('image')->getClientOriginalName(), PATHINFO_FILENAME) ?: 'โซนย่อย '.$sort;
        }

        $map = A5sZoneMap::create([
            'zone_id' => $zone->id,
            'name' => $name,
            'image_path' => $request->file('image')->store('area5s/zone-maps', 'public'),
            'sort' => $sort,
            'is_active' => true,
            'created_by' => $this->me()->id,
        ]);
        A5sActivityLog::write($this->me()->id, 'zone_map.create', 'zone_map', $map->id, [
            'zone' => $zone->name,
            'name' => $map->name,
        ]);

        return response()->json(['ok' => true, 'map' => $this->zoneMapPayload($map)]);
    }

    public function zoneMapUpdate(Request $request, A5sZoneMap $zoneMap): JsonResponse
    {
        if ($redirect = $this->gateAdmin()) {
            return response()->json(['ok' => false, 'message' => 'no permission'], 403);
        }

        $data = $request->validate([
            'name' => ['sometimes', 'nullable', 'string', 'max:191'],
            'image' => ['sometimes', 'image', 'max:12288'],
        ]);

        if (array_key_exists('name', $data) && trim((string) $data['name']) !== '') {
            $zoneMap->name = trim((string) $data['name']);
        }

        if ($request->hasFile('image')) {
            $oldPath = $zoneMap->image_path;
            $zoneMap->image_path = $request->file('image')->store('area5s/zone-maps', 'public');
            Storage::disk('public')->delete($oldPath);
        }

        $zoneMap->save();
        A5sActivityLog::write($this->me()->id, 'zone_map.update', 'zone_map', $zoneMap->id, ['name' => $zoneMap->name]);

        return response()->json(['ok' => true, 'map' => $this->zoneMapPayload($zoneMap->fresh())]);
    }

    public function zoneMapDestroy(A5sZoneMap $zoneMap): JsonResponse
    {
        if ($redirect = $this->gateAdmin()) {
            return response()->json(['ok' => false, 'message' => 'no permission'], 403);
        }

        $areaIds = $zoneMap->areas()->pluck('id');
        $floorIds = A5sFloor::whereIn('zone_map_area_id', $areaIds)->pluck('id');
        if ($floorIds->isNotEmpty() && A5sLayout::whereIn('floor_id', $floorIds)->exists()) {
            return response()->json([
                'ok' => false,
                'message' => 'โซนย่อยนี้มีพื้นที่ผูกกับชั้นอยู่ ให้ย้าย mapping ออกก่อนลบโซนย่อยนี้',
            ], 422);
        }

        A5sActivityLog::write($this->me()->id, 'zone_map.delete', 'zone_map', $zoneMap->id, ['name' => $zoneMap->name]);
        DB::connection('mysql_area5s')->transaction(function () use ($zoneMap, $floorIds) {
            if ($floorIds->isNotEmpty()) {
                A5sFloor::whereIn('id', $floorIds)->delete();
            }
            Storage::disk('public')->delete($zoneMap->image_path);
            $zoneMap->delete();
        });

        return response()->json(['ok' => true]);
    }

    public function zoneMapAreaStore(Request $request, A5sZoneMap $zoneMap): JsonResponse
    {
        if ($redirect = $this->gateAdmin()) {
            return response()->json(['ok' => false, 'message' => 'no permission'], 403);
        }

        $data = $request->validate([
            'name' => ['nullable', 'string', 'max:191'],
            'description' => ['nullable', 'string', 'max:2000'],
            'shape_points' => ['required', 'array', 'min:3'],
            'shape_points.*.x' => ['required', 'numeric', 'between:0,100'],
            'shape_points.*.y' => ['required', 'numeric', 'between:0,100'],
        ]);

        $palette = ['#5b7343', '#2f6ba8', '#c8964a', '#a3475f', '#3f8f8a', '#7a5aa8'];
        $sort = (int) $zoneMap->areas()->max('sort') + 1;
        $area = A5sZoneMapArea::create([
            'zone_map_id' => $zoneMap->id,
            'name' => trim((string) ($data['name'] ?? '')) ?: 'พื้นที่ย่อย '.$sort,
            'description' => trim((string) ($data['description'] ?? '')) ?: null,
            'shape_points' => $data['shape_points'],
            'color' => $palette[($sort - 1) % count($palette)],
            'sort' => $sort,
            'is_active' => true,
            'created_by' => $this->me()->id,
        ]);
        A5sActivityLog::write($this->me()->id, 'zone_map_area.create', 'zone_map_area', $area->id, ['map' => $zoneMap->name]);

        return response()->json(['ok' => true, 'area' => $this->zoneMapAreaPayload($area)]);
    }

    public function zoneMapAreaUpdate(Request $request, A5sZoneMapArea $area): JsonResponse
    {
        if ($redirect = $this->gateAdmin()) {
            return response()->json(['ok' => false, 'message' => 'no permission'], 403);
        }

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:191'],
            'description' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'color' => ['sometimes', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'shape_points' => ['sometimes', 'array', 'min:3'],
            'shape_points.*.x' => ['required_with:shape_points', 'numeric', 'between:0,100'],
            'shape_points.*.y' => ['required_with:shape_points', 'numeric', 'between:0,100'],
        ]);

        if (array_key_exists('name', $data) && trim($data['name']) !== '') {
            $area->name = trim($data['name']);
        }
        if (array_key_exists('description', $data)) {
            $area->description = trim((string) ($data['description'] ?? '')) ?: null;
        }
        if (array_key_exists('color', $data)) {
            $area->color = strtolower($data['color']);
        }
        if (array_key_exists('shape_points', $data)) {
            $area->shape_points = $data['shape_points'];
        }
        $area->save();
        A5sActivityLog::write($this->me()->id, 'zone_map_area.update', 'zone_map_area', $area->id, ['name' => $area->name]);

        return response()->json(['ok' => true, 'area' => $this->zoneMapAreaPayload($area)]);
    }

    public function zoneMapAreaDestroy(A5sZoneMapArea $area): JsonResponse
    {
        if ($redirect = $this->gateAdmin()) {
            return response()->json(['ok' => false, 'message' => 'no permission'], 403);
        }

        $floorIds = $area->floors()->pluck('id');
        if ($floorIds->isNotEmpty() && A5sLayout::whereIn('floor_id', $floorIds)->exists()) {
            return response()->json([
                'ok' => false,
                'message' => 'พื้นที่ย่อยนี้มีพื้นที่ผูกกับชั้นอยู่ ให้ย้าย mapping ออกก่อนลบพื้นที่ย่อยนี้',
            ], 422);
        }

        A5sActivityLog::write($this->me()->id, 'zone_map_area.delete', 'zone_map_area', $area->id, ['name' => $area->name]);
        DB::connection('mysql_area5s')->transaction(function () use ($area, $floorIds) {
            if ($floorIds->isNotEmpty()) {
                A5sFloor::whereIn('id', $floorIds)->delete();
            }
            $area->delete();
        });

        return response()->json(['ok' => true]);
    }

    public function floorStore(Request $request, A5sZone $zone): JsonResponse
    {
        if ($redirect = $this->gateAdmin()) {
            return response()->json(['ok' => false, 'message' => 'no permission'], 403);
        }

        $data = $request->validate(['name' => ['required', 'string', 'max:100']]);
        $name = trim($data['name']);

        if ($zone->floors()->whereNull('zone_map_area_id')->where('name', $name)->exists()) {
            return response()->json(['ok' => false, 'message' => 'มีชั้นชื่อนี้อยู่แล้วในโซนนี้'], 422);
        }

        $floor = A5sFloor::create([
            'zone_id' => $zone->id,
            'name' => $name,
            'sort' => (int) $zone->floors()->whereNull('zone_map_area_id')->max('sort') + 1,
            'is_active' => true,
            'created_by' => $this->me()->id,
        ]);
        A5sActivityLog::write($this->me()->id, 'floor.create', 'floor', $floor->id, ['zone' => $zone->name, 'name' => $name]);

        return response()->json(['ok' => true, 'floor' => ['id' => $floor->id, 'name' => $floor->name, 'is_active' => true]]);
    }

    public function areaFloorStore(Request $request, A5sZoneMapArea $area): JsonResponse
    {
        if ($redirect = $this->gateAdmin()) {
            return response()->json(['ok' => false, 'message' => 'no permission'], 403);
        }

        $data = $request->validate(['name' => ['required', 'string', 'max:100']]);
        $name = trim($data['name']);

        if ($area->floors()->where('name', $name)->exists()) {
            return response()->json(['ok' => false, 'message' => 'มีชั้นชื่อนี้อยู่แล้วในพื้นที่ย่อยนี้'], 422);
        }

        $area->loadMissing('zoneMap.zone');
        $floor = A5sFloor::create([
            'zone_id' => $area->zoneMap->zone_id,
            'zone_map_area_id' => $area->id,
            'name' => $name,
            'sort' => (int) $area->floors()->max('sort') + 1,
            'is_active' => true,
            'created_by' => $this->me()->id,
        ]);
        A5sActivityLog::write($this->me()->id, 'floor.create', 'floor', $floor->id, [
            'zone' => $area->zoneMap->zone?->name,
            'area' => $area->name,
            'name' => $name,
        ]);

        return response()->json(['ok' => true, 'floor' => $this->floorPayload($floor)]);
    }

    public function floorDestroy(A5sFloor $floor): JsonResponse
    {
        if ($redirect = $this->gateAdmin()) {
            return response()->json(['ok' => false, 'message' => 'no permission'], 403);
        }

        if (A5sLayout::where('floor_id', $floor->id)->exists()) {
            return response()->json([
                'ok' => false,
                'message' => 'ชั้นนี้มีพื้นที่ผูกอยู่แล้ว ให้ย้ายพื้นที่ออกก่อนลบ',
            ], 422);
        }

        A5sActivityLog::write($this->me()->id, 'floor.delete', 'floor', $floor->id, ['name' => $floor->name]);
        $floor->delete();

        return response()->json(['ok' => true]);
    }

    // ===== Mapping ข้อมูลเก่า: ตรวจ/แก้ Layout ที่ยังไม่ผูกชั้น =====

    public function mapping(Request $request): ViewContract|RedirectResponse
    {
        if ($redirect = $this->gateAdmin()) {
            return $redirect;
        }

        $plan = A5sCompanyPlan::where('is_active', true)->orderByDesc('id')->first();
        $zones = $plan ? $plan->zones()->with(['floors', 'zoneMaps.areas.floors'])->where('is_active', true)->get() : collect();

        $onlyUnmapped = $request->boolean('unmapped', false);
        $layouts = A5sLayout::with(['floor.zone', 'floor.area.zoneMap.zone', 'round'])
            ->withCount('points')
            ->when($onlyUnmapped, fn ($q) => $q->where(function ($query) {
                $query->whereNull('floor_id')
                    ->orWhereHas('floor', fn ($floorQuery) => $floorQuery->whereNull('zone_map_area_id'));
            }))
            ->orderByDesc('round_id')
            ->orderBy('name')
            ->get()
            ->map(function (A5sLayout $l) {
                $area = $l->floor?->area;

                return [
                    'id' => $l->id,
                    'name' => $l->name,
                    'round_label' => $l->round ? ($l->round->month.'/'.$l->round->year) : '-',
                    'points_count' => $l->points_count,
                    'floor_id' => $l->floor_id,
                    'zone_id' => $area?->zoneMap?->zone_id ?? $l->floor?->zone_id,
                    'zone_map_id' => $area?->zone_map_id,
                    'area_id' => $area?->id,
                    'guessed_floor_name' => $this->guessFloorName($l->name),
                ];
            });

        return view('area5s.plan.mapping', [
            'me' => $this->me(),
            'plan' => $plan,
            'zones' => $zones,
            'layouts' => $layouts,
            'onlyUnmapped' => $onlyUnmapped,
        ]);
    }

    public function mappingSave(Request $request): RedirectResponse
    {
        if ($redirect = $this->gateAdmin()) {
            return $redirect;
        }

        $data = $request->validate([
            'floor_id' => ['required', 'array'],
            'floor_id.*' => ['nullable', 'integer', 'exists:mysql_area5s.a5s_floors,id'],
        ]);

        $updated = 0;
        foreach ($data['floor_id'] as $layoutId => $floorId) {
            if ($floorId === null || $floorId === '') {
                continue;
            }
            $layout = A5sLayout::find((int) $layoutId);
            if (! $layout) {
                continue;
            }
            $layout->floor_id = (int) $floorId;
            $layout->save();
            $updated++;
        }
        A5sActivityLog::write($this->me()->id, 'plan.mapping_save', 'plan', 0, ['updated' => $updated]);

        return back()->with('success', "บันทึก mapping แล้ว {$updated} พื้นที่");
    }

    /** เดาชื่อชั้นจากชื่อ Layout เดิม — "Floor N" / "ชั้น N" (ใช้ร่วมกับ Area5sController::floorOf) */
    public static function guessFloorName(?string $layoutName): ?string
    {
        if (preg_match('/Floor\s*(\d+)/i', (string) $layoutName, $m)) {
            return 'ชั้น '.$m[1];
        }
        if (preg_match('/ชั้น\s*(\d+)/u', (string) $layoutName, $m)) {
            return 'ชั้น '.$m[1];
        }

        return null;
    }
}
