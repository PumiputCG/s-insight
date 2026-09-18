<?php

namespace App\Http\Controllers\Recruit;

use App\Http\Controllers\Controller;
use App\Models\Insight\AppUser;
use App\Models\Insight\Employee;
use App\Models\Recruit\RecruitMember;
use App\Models\Recruit\RecruitRoute;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\View;
use Illuminate\View\View as ViewContract;

/**
 * หน้าระบบย่อย Recruit — แท็บ sidebar แยกตาม role (1 คน 1 role; admin เห็นทุกแท็บ)
 *
 * รอบนี้เป็นโครงหน้า (stub) — เนื้อหาจริงของแต่ละ role ทำเฟสถัดไป
 * เริ่มจาก DCC ก่อน ดู RECRUIT_SYSTEM.md §7A
 */
class RecruitController extends Controller
{
    /** กลุ่ม role ผู้อนุมัติ (เห็นแท็บ "ตรวจเอกสาร") */
    private const APPROVER_ROLES = ['manager', 'general_manager', 'hr_manager'];

    private const ROLE_LABELS = [
        'dcc' => 'DCC',
        'manager' => 'Manager',
        'general_manager' => 'General Manager',
        'hr_manager' => 'HR Manager',
        'recruit' => 'Recruit',
    ];

    /** ภาพรวม — ทุกสมาชิก Recruit (และ admin) เข้าได้ */
    public function index(): ViewContract|RedirectResponse
    {
        $me = app('current_user');
        if (! $me->isAdmin() && ! RecruitMember::isMember($me->id)) {
            return $this->denyToSystems(
                'toast.recruitDenied',
                'คุณยังไม่ได้รับสิทธิ์เข้าใช้ระบบ Recruit System',
            );
        }

        return View::make('recruit.index', [
            'myRecruitRoles' => RecruitMember::rolesFor($me->id),
        ]);
    }

    /** DCC — แผนกภายใต้สังกัด */
    public function departments(): ViewContract|RedirectResponse
    {
        if ($redirect = $this->gateRole(['dcc'])) {
            return $redirect;
        }

        return View::make('recruit.departments');
    }

    /** DCC — ขออัตรากำลังคน (ฟอร์ม) */
    public function requestForm(): ViewContract|RedirectResponse
    {
        if ($redirect = $this->gateRole(['dcc'])) {
            return $redirect;
        }

        $me = app('current_user');

        return View::make('recruit.request', [
            'requesterName' => $me->fullNameTh() ?: $me->full_name_en ?: $me->employee_code,
            'requestMockData' => $this->requestMockData($me),
        ]);
    }

    /** Manager / General Manager / HR Manager — ตรวจเอกสาร */
    public function review(): ViewContract|RedirectResponse
    {
        if ($redirect = $this->gateRole(self::APPROVER_ROLES)) {
            return $redirect;
        }

        return View::make('recruit.review');
    }

    /** Recruit — กล่องเอกสาร */
    public function inbox(): ViewContract|RedirectResponse
    {
        if ($redirect = $this->gateRole(['recruit'])) {
            return $redirect;
        }

        return View::make('recruit.inbox');
    }

    /** Recruit — ดาวน์โหลด */
    public function downloads(): ViewContract|RedirectResponse
    {
        if ($redirect = $this->gateRole(['recruit'])) {
            return $redirect;
        }

        return View::make('recruit.downloads');
    }

    /** อนุญาตเฉพาะ admin หรือผู้ที่มี role ในชุดที่กำหนด */
    private function gateRole(array $roles): ?RedirectResponse
    {
        $me = app('current_user');

        if ($me->isAdmin() || RecruitMember::hasAnyRole($me->id, $roles)) {
            return null;
        }

        return $this->denyToSystems(
            RecruitMember::isMember($me->id) ? 'toast.pageDenied' : 'toast.recruitDenied',
            RecruitMember::isMember($me->id) ? 'คุณไม่มีสิทธิ์เข้าหน้านี้' : 'คุณยังไม่ได้รับสิทธิ์เข้าใช้ระบบ Recruit System',
        );
    }

    /** กลับไปหน้าระบบทั้งหมดพร้อม toast แทนหน้า 403 แข็ง ๆ */
    private function denyToSystems(string $key, string $fallback): RedirectResponse
    {
        return redirect()
            ->route('systems.index')
            ->with('toast_key', $key)
            ->with('toast_fallback', $fallback)
            ->with('toast_type', 'error');
    }

    /** ข้อมูล mockup สำหรับหน้า DCC request: อ่าน settings เดิมเท่านั้น ไม่เขียน DB */
    private function requestMockData(AppUser $me): array
    {
        $deptCodes = RecruitMember::deptCodesFor($me->id);
        if ($me->isAdmin() && $deptCodes === []) {
            $deptCodes = $this->allConfiguredDccDeptCodes();
        }

        $departments = $this->departmentOptions($deptCodes);
        $routeIdsByDept = [];
        foreach ($deptCodes as $deptCode) {
            $routeIdsByDept[$deptCode] = RecruitRoute::availableForDeptUser($me->id, $deptCode)
                ->pluck('id')->map(fn ($id) => (int) $id)->values()->all();
        }

        $routes = RecruitRoute::with(['steps' => fn ($q) => $q->orderBy('position')->orderBy('id')->with('members')])
            ->orderBy('position')->orderBy('id')->get()
            ->filter(function (RecruitRoute $route) use ($me, $routeIdsByDept) {
                if ($me->isAdmin()) {
                    return true;
                }

                $availableIds = array_values(array_unique(array_merge(...array_values($routeIdsByDept ?: [[]]))));

                return in_array((int) $route->id, $availableIds, true);
            })
            ->map(function (RecruitRoute $route) {
                $steps = $route->steps->map(function ($step) {
                    return [
                        'id' => (int) $step->id,
                        'role' => $step->role,
                        'label' => self::ROLE_LABELS[$step->role] ?? 'Step',
                        'members' => $step->members->map(fn ($member) => [
                            'id' => (int) $member->app_user_id,
                            'code' => $member->employee_code,
                            'name' => $member->display_name ?: $member->employee_code,
                            'position' => $member->position ?: '',
                            'department' => $member->department ?: '',
                            'dept_codes' => $member->dept_codes ?: [],
                        ])->values()->all(),
                    ];
                })->values();

                $deptCodes = $steps
                    ->where('role', 'dcc')
                    ->flatMap(fn ($step) => collect($step['members'])->flatMap(fn ($member) => $member['dept_codes']))
                    ->unique()->values()->all();

                return [
                    'id' => (int) $route->id,
                    'name' => $route->name,
                    'dept_codes' => $deptCodes,
                    'path' => $steps->pluck('label')->implode(' > '),
                    'steps' => $steps->all(),
                    'managers' => $steps->where('role', 'manager')
                        ->flatMap(fn ($step) => $step['members'])
                        ->values()->all(),
                ];
            })
            ->values()->all();

        return [
            'departments' => $departments,
            'routes' => $routes,
            'routeIdsByDept' => $routeIdsByDept,
        ];
    }

    private function allConfiguredDccDeptCodes(): array
    {
        $rows = RecruitMember::query()
            ->join('recruit_steps', 'recruit_steps.id', '=', 'recruit_members.step_id')
            ->where('recruit_steps.role', 'dcc')
            ->pluck('recruit_members.dept_codes');

        $codes = [];
        foreach ($rows as $json) {
            foreach ((array) (is_array($json) ? $json : json_decode((string) $json, true)) as $code) {
                if ($code !== null && $code !== '') {
                    $codes[(string) $code] = true;
                }
            }
        }

        return array_keys($codes);
    }

    private function departmentOptions(array $deptCodes): array
    {
        if ($deptCodes === []) {
            return [];
        }

        return Employee::active()
            ->whereIn('dept_code', $deptCodes)
            ->selectRaw('dept_code as code, MAX(dept_th) as th, MAX(dept_en) as en')
            ->groupBy('dept_code')
            ->orderBy('th')
            ->get()
            ->map(fn ($department) => [
                'code' => $department->code,
                'name' => $department->th ?: $department->en ?: $department->code,
            ])
            ->values()->all();
    }
}
