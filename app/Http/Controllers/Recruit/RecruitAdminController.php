<?php

namespace App\Http\Controllers\Recruit;

use App\Http\Controllers\Controller;
use App\Models\Insight\AppUser;
use App\Models\Insight\Employee;
use App\Models\Recruit\RecruitMember;
use App\Models\Recruit\RecruitRoute;
use App\Models\Recruit\RecruitStep;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View as ViewContract;

/**
 * Admin จัดการเส้นทางอนุมัติ Recruit (route → การ์ด=ขั้น) + สมาชิก + แผนกที่ DCC คุม
 *
 * - มีได้หลายเส้นทาง (route) — DCC เลือกเองตอนส่งคำขอ
 * - อ่าน app_users / employees จาก core (read-only) เก็บลง insight_recruit เท่านั้น
 */
class RecruitAdminController extends Controller
{
    public function index(): ViewContract
    {
        return view('recruit.settings', [
            'me' => app('current_user'),
            'routes' => $this->routesData(),
            'roles' => RecruitStep::ROLES,
        ]);
    }

    /** โครงข้อมูลเส้นทางทั้งหมด + ขั้น + สมาชิก */
    private function routesData(): array
    {
        $routes = RecruitRoute::with(['steps' => fn ($q) => $q->orderBy('position')->orderBy('id')->with('members')])
            ->orderBy('position')->orderBy('id')->get();

        $avatars = AppUser::whereIn('id', $routes->flatMap->steps->flatMap->members->pluck('app_user_id')->unique())
            ->pluck('profile_picture', 'id');

        return $routes->map(fn (RecruitRoute $r) => [
            'id' => $r->id,
            'name' => $r->name,
            'steps' => $r->steps->map(fn (RecruitStep $s) => [
                'id' => $s->id,
                'role' => $s->role,
                'members' => $s->members->map(fn (RecruitMember $m) => $this->memberArray($m, $avatars[$m->app_user_id] ?? null))->values()->all(),
            ])->values()->all(),
        ])->values()->all();
    }

    private function memberArray(RecruitMember $m, ?string $pic): array
    {
        return [
            'id' => $m->id,
            'app_user_id' => $m->app_user_id,
            'code' => $m->employee_code,
            'name' => $m->display_name,
            'position' => $m->position ?: '',
            'department' => $m->department ?: '',
            'dept_codes' => $m->dept_codes ?: [],
            'avatar' => $pic ? asset('storage/'.$pic) : null,
        ];
    }

    /** ค้นหาพนักงานจาก app_users (อ่านอย่างเดียว) */
    public function searchUsers(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));

        $users = AppUser::query()
            ->where('employee_code', '!=', 'Admin')
            ->when($q !== '', fn ($query) => $query->where(fn ($w) => $w
                ->where('full_name_th', 'like', "%{$q}%")
                ->orWhere('full_name_en', 'like', "%{$q}%")
                ->orWhere('employee_code', 'like', "%{$q}%")))
            ->orderBy('full_name_th')->limit(25)->get();

        return response()->json([
            'users' => $users->map(fn (AppUser $u) => [
                'app_user_id' => $u->id,
                'code' => $u->employee_code,
                'name' => $u->fullNameTh() ?: $u->full_name_en ?: $u->employee_code,
                'position' => $u->position ?: '',
                'department' => $u->department ?: '',
                'avatar' => $u->profile_picture ? asset('storage/'.$u->profile_picture) : null,
            ])->values(),
        ]);
    }

    /** รายชื่อแผนก (จาก employees, อ่านอย่างเดียว) — สำหรับ DCC เลือกแผนกที่คุม */
    public function departments(): JsonResponse
    {
        $depts = Employee::active()
            ->selectRaw("COALESCE(NULLIF(dept_code,''),'__none__') as code, MAX(dept_th) as th, MAX(dept_en) as en")
            ->groupBy('code')->orderBy('th')->get()
            ->filter(fn ($d) => $d->code !== '__none__')
            ->map(fn ($d) => ['code' => $d->code, 'name' => $d->th ?: $d->en ?: $d->code])
            ->values();

        return response()->json(['departments' => $depts]);
    }

    /** เพิ่มเส้นทางอนุมัติใหม่ */
    public function addRoute(): JsonResponse
    {
        $pos = (int) RecruitRoute::max('position') + 1;
        $route = RecruitRoute::create(['name' => 'เส้นทาง '.$pos, 'position' => $pos]);

        return response()->json(['ok' => true, 'route' => ['id' => $route->id, 'name' => $route->name, 'steps' => []]]);
    }

    /** เปลี่ยนชื่อเส้นทาง */
    public function renameRoute(Request $request, RecruitRoute $route): JsonResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:60']]);
        $route->update(['name' => trim($data['name'])]);

        return response()->json(['ok' => true, 'name' => $route->name]);
    }

    /** ลบเส้นทาง (พร้อมการ์ด + สมาชิกในเส้นทางนั้น) */
    public function deleteRoute(RecruitRoute $route): JsonResponse
    {
        $stepIds = $route->steps()->pluck('id');
        RecruitMember::whereIn('step_id', $stepIds)->delete();
        $route->steps()->delete();
        $route->delete();

        return response()->json(['ok' => true]);
    }

    /** เพิ่มการ์ด (ขั้นใหม่) ต่อท้ายภายในเส้นทาง */
    public function addStep(RecruitRoute $route): JsonResponse
    {
        $pos = (int) $route->steps()->max('position') + 1;
        $step = $route->steps()->create(['position' => $pos, 'role' => null]);

        return response()->json(['ok' => true, 'step' => ['id' => $step->id, 'role' => null, 'members' => []]]);
    }

    /** ลบการ์ด (พร้อมสมาชิกในการ์ด) */
    public function deleteStep(RecruitStep $step): JsonResponse
    {
        $step->members()->delete();
        $step->delete();

        return response()->json(['ok' => true]);
    }

    /** ตั้ง role ของการ์ด */
    public function setRole(Request $request, RecruitStep $step): JsonResponse
    {
        $data = $request->validate(['role' => ['nullable', Rule::in(RecruitStep::ROLES)]]);
        $step->update(['role' => $data['role'] ?? null]);

        return response()->json(['ok' => true]);
    }

    /** บันทึกลำดับการ์ด (เส้นทาง) */
    public function reorder(Request $request): JsonResponse
    {
        $data = $request->validate(['order' => ['required', 'array'], 'order.*' => ['integer']]);
        foreach (array_values($data['order']) as $i => $id) {
            RecruitStep::where('id', $id)->update(['position' => $i + 1]);
        }

        return response()->json(['ok' => true]);
    }

    /** เพิ่มสมาชิกเข้าการ์ด */
    public function addMember(Request $request, RecruitStep $step): JsonResponse
    {
        $data = $request->validate(['app_user_id' => ['required', 'integer']]);
        $user = AppUser::find($data['app_user_id']);
        if (! $user || $user->employee_code === 'Admin') {
            return response()->json(['ok' => false, 'message' => 'ไม่พบพนักงาน'], 422);
        }

        $member = RecruitMember::firstOrCreate(
            ['step_id' => $step->id, 'app_user_id' => $user->id],
            [
                'employee_code' => $user->employee_code,
                'display_name' => $user->fullNameTh() ?: $user->full_name_en ?: $user->employee_code,
                'position' => $user->position ?: null,
                'department' => $user->department ?: null,
                'assigned_by' => app('current_user')->id,
            ],
        );

        return response()->json([
            'ok' => true,
            'member' => $this->memberArray($member, $user->profile_picture),
        ]);
    }

    /** ลบสมาชิก */
    public function removeMember(RecruitMember $member): JsonResponse
    {
        $member->delete();

        return response()->json(['ok' => true]);
    }

    /** ตั้งแผนกที่สมาชิก (DCC) คุม/มองเห็นได้ */
    public function setDepartments(Request $request, RecruitMember $member): JsonResponse
    {
        $data = $request->validate(['dept_codes' => ['array'], 'dept_codes.*' => ['string']]);
        $member->update(['dept_codes' => array_values(array_unique($data['dept_codes'] ?? []))]);

        return response()->json(['ok' => true, 'dept_codes' => $member->dept_codes]);
    }
}
