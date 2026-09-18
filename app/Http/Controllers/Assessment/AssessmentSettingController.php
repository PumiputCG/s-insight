<?php

namespace App\Http\Controllers\Assessment;

use App\Http\Controllers\Controller;
use App\Models\Assessment\AsmMember;
use App\Models\Insight\AppUser;
use App\Models\Insight\Employee;
use App\Models\Insight\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View as ViewContract;

/**
 * ตั้งค่าระบบ Assessment (เฉพาะ admin)
 *
 * 1) มอบ role HR ให้พนักงาน (asm_members)
 * 2) เลือกตำแหน่ง (job_code จาก employees) ที่อนุญาตให้เข้าระบบ
 */
class AssessmentSettingController extends Controller
{
    public function index(): ViewContract
    {
        $members = AsmMember::orderByDesc('id')->get();
        $avatars = AppUser::whereIn('id', $members->pluck('app_user_id'))->pluck('profile_picture', 'id');

        return view('assessment.settings', [
            'me' => app('current_user'),
            'members' => $members->map(fn (AsmMember $m) => [
                'id' => $m->id,
                'app_user_id' => $m->app_user_id,
                'code' => $m->employee_code,
                'name' => $m->display_name ?: $m->employee_code,
                'position' => $m->position ?: '',
                'role' => $m->role,
                'avatar' => ($pic = $avatars[$m->app_user_id] ?? null) ? asset('storage/'.$pic) : null,
            ])->values()->all(),
            'positions' => $this->positionList(),
            'allowed' => Setting::get(Setting::ASSESSMENT_POSITIONS, null),
        ]);
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
                'count' => (int) $p->cnt,
            ])
            ->values()->all();
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
                'avatar' => $u->profile_picture ? asset('storage/'.$u->profile_picture) : null,
            ])->values(),
        ]);
    }

    /** มอบ role HR ให้พนักงาน */
    public function addMember(Request $request): JsonResponse
    {
        $data = $request->validate(['app_user_id' => ['required', 'integer']]);
        $user = AppUser::find($data['app_user_id']);
        if (! $user || $user->employee_code === 'Admin') {
            return response()->json(['ok' => false, 'message' => 'ไม่พบพนักงาน'], 422);
        }

        $member = AsmMember::firstOrCreate(
            ['app_user_id' => $user->id],
            [
                'employee_code' => $user->employee_code,
                'display_name' => $user->fullNameTh() ?: $user->full_name_en ?: $user->employee_code,
                'position' => $user->position ?: null,
                'role' => 'hr',
                'assigned_by' => app('current_user')->id,
            ],
        );

        return response()->json([
            'ok' => true,
            'member' => [
                'id' => $member->id,
                'app_user_id' => $member->app_user_id,
                'code' => $member->employee_code,
                'name' => $member->display_name,
                'position' => $member->position ?: '',
                'role' => $member->role,
                'avatar' => $user->profile_picture ? asset('storage/'.$user->profile_picture) : null,
            ],
        ]);
    }

    /** ถอนสิทธิ์ */
    public function removeMember(AsmMember $member): JsonResponse
    {
        $member->delete();

        return response()->json(['ok' => true]);
    }

    /** บันทึกตำแหน่งที่อนุญาตเข้าระบบ */
    public function savePositions(Request $request): JsonResponse
    {
        $data = $request->validate([
            'job_codes' => ['array'],
            'job_codes.*' => ['string'],
        ]);

        Setting::put(Setting::ASSESSMENT_POSITIONS, array_values(array_unique($data['job_codes'] ?? [])));

        return response()->json(['ok' => true]);
    }
}
