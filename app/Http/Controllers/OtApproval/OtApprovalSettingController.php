<?php

namespace App\Http\Controllers\OtApproval;

use App\Http\Controllers\Controller;
use App\Http\Controllers\OtApproval\Concerns\HandlesOtApprovalAccess;
use App\Models\Insight\AppUser;
use App\Models\Insight\Employee;
use App\Models\Insight\Setting;
use App\Models\OtApproval\OtDepartmentAssignment;
use App\Models\OtApproval\OtMember;
use App\Support\OtApproval\OtEmployeeEligibility;
use App\Support\OtApproval\OtShiftGroup;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View as ViewContract;

class OtApprovalSettingController extends Controller
{
    use HandlesOtApprovalAccess;

    /** ใช้ชื่อบริษัทชุดเดียวกับ OtDepartmentAssignment::COMPANIES */
    private const COMPANIES = OtDepartmentAssignment::COMPANIES;

    public function index(): ViewContract|RedirectResponse
    {
        if ($redirect = $this->gateOtAdmin()) {
            return $redirect;
        }

        $members = OtMember::orderByDesc('id')->get();
        $memberUsers = AppUser::whereIn('id', $members->pluck('app_user_id'))->get()->keyBy('id');

        return view('ot_approval.settings', [
            'me' => $this->me(),
            'members' => $members->map(function (OtMember $member) use ($memberUsers) {
                $user = $memberUsers->get($member->app_user_id);

                return $this->appUserPayload($user, $member->employee_code, [
                    'id' => (int) $member->id,
                    'position' => $member->position ?: ($user?->position ?? ''),
                ]);
            })->values()->all(),
            'positions' => $this->positionList(),
            'allowed' => Setting::get(Setting::OT_APPROVAL_POSITIONS, []),
            'hiddenRequestPositions' => Setting::get(Setting::OT_REQUEST_HIDDEN_POSITIONS, []),
            // หัวข้อ 8 — คู่แฝดของหัวข้อ 7 แต่เป็นฝั่งการลา 75 ตั้งค่าแยกกันได้
            'hiddenLeavePositions' => Setting::get(Setting::LEAVE_REQUEST_HIDDEN_POSITIONS, []),
            'companies' => $this->companyDirectory(),
            'shiftGroups' => OtShiftGroup::options(),
        ]);
    }

    public function searchUsers(Request $request): JsonResponse
    {
        if (! $this->isOtAdmin()) {
            return response()->json(['ok' => false, 'message' => 'no permission'], 403);
        }

        $queryText = trim((string) $request->query('q', ''));
        $users = AppUser::query()
            ->where('employee_code', '!=', 'Admin')
            ->when($queryText !== '', fn ($query) => $query->where(fn ($where) => $where
                ->where('full_name_th', 'like', "%{$queryText}%")
                ->orWhere('full_name_en', 'like', "%{$queryText}%")
                ->orWhere('employee_code', 'like', "%{$queryText}%")))
            ->orderBy('full_name_th')
            ->limit(25)
            ->get();

        return response()->json([
            'users' => $users->map(fn (AppUser $user) => $this->appUserPayload($user, $user->employee_code, [
                'app_user_id' => (int) $user->id,
            ]))->values(),
        ]);
    }

    public function addMember(Request $request): JsonResponse
    {
        if (! $this->isOtAdmin()) {
            return response()->json(['ok' => false, 'message' => 'no permission'], 403);
        }

        $data = $request->validate(['app_user_id' => ['required', 'integer']]);
        $user = AppUser::find($data['app_user_id']);
        if (! $user || $user->employee_code === 'Admin') {
            throw ValidationException::withMessages(['app_user_id' => 'Employee account was not found.']);
        }

        $member = OtMember::firstOrCreate(
            ['app_user_id' => $user->id, 'role' => OtMember::ROLE_ADMIN],
            [
                'employee_code' => $user->employee_code,
                'display_name' => $user->fullNameTh() ?: $user->full_name_en ?: $user->employee_code,
                'position' => $user->position ?: null,
                'assigned_by' => $this->me()->id,
            ],
        );

        return response()->json(['ok' => true, 'created' => $member->wasRecentlyCreated]);
    }

    public function removeMember(OtMember $member): JsonResponse
    {
        if (! $this->isOtAdmin()) {
            return response()->json(['ok' => false, 'message' => 'no permission'], 403);
        }

        $member->delete();

        return response()->json(['ok' => true]);
    }

    public function savePositions(Request $request): JsonResponse
    {
        if (! $this->isOtAdmin()) {
            return response()->json(['ok' => false, 'message' => 'no permission'], 403);
        }

        $data = $request->validate([
            'job_codes' => ['array'],
            'job_codes.*' => ['string', 'max:50'],
        ]);
        $codes = collect($data['job_codes'] ?? [])->map(fn ($code) => trim($code))->filter()->unique()->values();
        $valid = Employee::active()->whereIn('job_code', $codes)->pluck('job_code')->unique();
        if ($valid->count() !== $codes->count()) {
            throw ValidationException::withMessages(['job_codes' => 'One or more positions are invalid.']);
        }

        Setting::put(Setting::OT_APPROVAL_POSITIONS, $codes->all());

        return response()->json(['ok' => true]);
    }

    /**
     * ตำแหน่งที่จะซ่อนปุ่ม `ขอ OT` — blacklist ตรงข้ามกับ savePositions()
     *
     * ไม่ติ๊ก = ขอ OT ได้ตามปกติ ตำแหน่งใหม่จาก Bplus จึงไม่ถูกบล็อกโดยไม่ตั้งใจ
     */
    public function saveHiddenRequestPositions(Request $request): JsonResponse
    {
        if (! $this->isOtAdmin()) {
            return response()->json(['ok' => false, 'message' => 'no permission'], 403);
        }

        $data = $request->validate([
            'job_codes' => ['array'],
            'job_codes.*' => ['string', 'max:50'],
        ]);
        $codes = collect($data['job_codes'] ?? [])->map(fn ($code) => trim($code))->filter()->unique()->values();
        $valid = Employee::active()->whereIn('job_code', $codes)->pluck('job_code')->unique();
        if ($valid->count() !== $codes->count()) {
            throw ValidationException::withMessages(['job_codes' => 'One or more positions are invalid.']);
        }

        Setting::put(Setting::OT_REQUEST_HIDDEN_POSITIONS, $codes->all());
        OtEmployeeEligibility::forgetHiddenJobCodes();

        return response()->json(['ok' => true]);
    }

    /**
     * ตำแหน่งที่จะซ่อนปุ่ม `ขอลา` — คู่แฝดของ saveHiddenRequestPositions() แต่เป็นระบบลา 75
     *
     * แยกคีย์กันเพราะบางตำแหน่งขอ OT ไม่ได้แต่ยังลาได้ (และกลับกัน)
     */
    public function saveHiddenLeavePositions(Request $request): JsonResponse
    {
        if (! $this->isOtAdmin()) {
            return response()->json(['ok' => false, 'message' => 'no permission'], 403);
        }

        $data = $request->validate([
            'job_codes' => ['array'],
            'job_codes.*' => ['string', 'max:50'],
        ]);
        $codes = collect($data['job_codes'] ?? [])->map(fn ($code) => trim($code))->filter()->unique()->values();
        $valid = Employee::active()->whereIn('job_code', $codes)->pluck('job_code')->unique();
        if ($valid->count() !== $codes->count()) {
            throw ValidationException::withMessages(['job_codes' => 'One or more positions are invalid.']);
        }

        Setting::put(Setting::LEAVE_REQUEST_HIDDEN_POSITIONS, $codes->all());
        OtEmployeeEligibility::forgetHiddenLeaveJobCodes();

        return response()->json(['ok' => true]);
    }

    public function departmentEmployees(Request $request): JsonResponse
    {
        if (! $this->isOtAdmin()) {
            return response()->json(['ok' => false, 'message' => 'no permission'], 403);
        }

        $data = $request->validate([
            'company' => ['required', 'string', 'max:50'],
            'dept_code' => ['nullable', 'string', 'max:50'],
        ]);
        if (! array_key_exists($data['company'], self::COMPANIES)) {
            throw ValidationException::withMessages(['company' => 'Company is invalid.']);
        }

        $allCompanies = $request->boolean('all_companies') || $request->boolean('all_company');
        $employees = $allCompanies
            ? Employee::active()->whereIn('company', array_keys(self::COMPANIES))
            : $this->departmentEmployeeQuery($data['company'], $data['dept_code'] ?? '');
        $employees = $employees
            ->when($allCompanies, fn ($query) => $query->orderBy('company'))
            ->orderBy('name_th')
            ->orderBy('name_en')
            ->get();
        $identities = $employees->pluck('license_id')->filter()->unique()->values();
        $users = $identities->isEmpty()
            ? collect()
            : AppUser::whereIn('id_thai_hash', $identities)->get()->keyBy('id_thai_hash');

        return response()->json([
            'employees' => $employees->map(function (Employee $employee) use ($users) {
                $user = $employee->license_id ? $users->get($employee->license_id) : null;
                $nameTh = $employee->fullNameTh() ?: $employee->fullNameEn() ?: $employee->employee_code;
                $nameEn = $employee->fullNameEn() ?: $nameTh;
                $companyLabel = self::COMPANIES[$employee->company] ?? $employee->company;

                return [
                    'app_user_id' => $user ? (int) $user->id : null,
                    'code' => $employee->employee_code,
                    'company_code' => $employee->company,
                    'company_th' => $companyLabel,
                    'company_en' => $companyLabel,
                    'company_my' => $companyLabel,
                    'name_th' => $nameTh,
                    'name_en' => $nameEn,
                    'name_my' => $nameEn,
                    'position_th' => $employee->job_th ?: $employee->job_en ?: '',
                    'position_en' => $employee->job_en ?: $employee->job_th ?: '',
                    'position_my' => $employee->job_en ?: $employee->job_th ?: '',
                    'department_th' => $employee->dept_th ?: $employee->dept_en ?: '',
                    'department_en' => $employee->dept_en ?: $employee->dept_th ?: '',
                    'department_my' => $employee->dept_en ?: $employee->dept_th ?: '',
                    'avatar' => $user?->profile_picture ? asset('storage/'.$user->profile_picture) : null,
                    'account_ready' => (bool) $user,
                ];
            })->values(),
        ]);
    }

    public function saveAssignment(Request $request): JsonResponse
    {
        if (! $this->isOtAdmin()) {
            return response()->json(['ok' => false, 'message' => 'no permission'], 403);
        }

        $data = $request->validate([
            'company' => ['required', 'string', 'max:50'],
            'dept_code' => ['nullable', 'string', 'max:50'],
            'role' => ['required', 'in:'.implode(',', OtDepartmentAssignment::ROLES)],
            // ระบบที่สิทธิ์นี้ใช้ได้ — ไม่ส่งมาถือเป็นของ OT เพื่อความเข้ากันได้กับของเดิม
            'module' => ['nullable', 'in:'.implode(',', OtDepartmentAssignment::MODULES)],
            // ป้ายกะใช้กับ Foreman เท่านั้น และเป็นข้อมูลแสดงผล ไม่ใช่สิทธิ์
            'shift_group' => ['nullable', 'in:'.implode(',', OtShiftGroup::keys())],
            'app_user_id' => ['required', 'integer'],
            'employee_company' => ['required', 'string', 'max:50'],
            'employee_code' => ['required', 'string', 'max:30'],
        ]);
        if (! array_key_exists($data['company'], self::COMPANIES)) {
            throw ValidationException::withMessages(['company' => 'Company is invalid.']);
        }
        if (! array_key_exists($data['employee_company'], self::COMPANIES)) {
            throw ValidationException::withMessages(['employee_company' => 'Employee company is invalid.']);
        }

        $deptCode = trim((string) ($data['dept_code'] ?? ''));
        if (! $this->departmentEmployeeQuery($data['company'], $deptCode)->exists()) {
            throw ValidationException::withMessages(['dept_code' => 'Department is not active in this company.']);
        }

        $user = AppUser::whereKey($data['app_user_id'])
            ->where('employee_code', '!=', 'Admin')
            ->first();
        $identity = trim((string) ($user?->id_thai_hash ?? ''));
        if (! $user || $identity === '') {
            throw ValidationException::withMessages(['app_user_id' => 'Employee account was not found.']);
        }

        $employee = Employee::active()
            ->where('company', $data['employee_company'])
            ->where('employee_code', $data['employee_code'])
            ->where('license_id', $identity)
            ->first();
        if (! $employee) {
            throw ValidationException::withMessages(['employee_code' => 'Employee is not active in the selected source company.']);
        }

        $isForeman = $data['role'] === OtDepartmentAssignment::ROLE_FOREMAN;

        /* Supervisor ยังเป็น 1 คนต่อแผนกตามที่ Manager กำหนด — MySQL ทำ partial
           unique index ไม่ได้ จึงบังคับที่นี่ กำหนดคนใหม่ = แทนที่คนเดิม
           ส่วน Foreman เพิ่มได้เรื่อย ๆ โดย unique 4 คีย์กันคนเดิมซ้ำอยู่แล้ว */
        $module = $data['module'] ?? OtDepartmentAssignment::MODULE_OT;

        if (! $isForeman) {
            OtDepartmentAssignment::query()
                ->forModule($module)
                ->where('company', $data['company'])
                ->where('dept_code', $deptCode)
                ->where('role', $data['role'])
                ->where('app_user_id', '!=', $user->id)
                ->delete();
        }

        $assignment = OtDepartmentAssignment::updateOrCreate(
            [
                'module' => $module,
                'company' => $data['company'],
                'dept_code' => $deptCode,
                'role' => $data['role'],
                'app_user_id' => $user->id,
            ],
            [
                'shift_group' => $isForeman ? ($data['shift_group'] ?? null) : null,
                'employee_code' => $employee->employee_code,
                'assigned_by' => $this->me()->id,
            ],
        );

        return response()->json([
            'ok' => true,
            'assignment_id' => (int) $assignment->id,
            'shift_group' => $assignment->shift_group,
        ]);
    }

    public function removeAssignment(OtDepartmentAssignment $assignment): JsonResponse
    {
        if (! $this->isOtAdmin()) {
            return response()->json(['ok' => false, 'message' => 'no permission'], 403);
        }

        $assignment->delete();

        return response()->json(['ok' => true]);
    }

    private function departmentEmployeeQuery(string $company, string $deptCode)
    {
        return $this->companyEmployeeQuery($company)
            ->when(
                $deptCode === '',
                fn ($query) => $query->where(fn ($where) => $where->whereNull('dept_code')->orWhere('dept_code', '')),
                fn ($query) => $query->where('dept_code', $deptCode),
            );
    }

    private function companyEmployeeQuery(string $company)
    {
        return Employee::active()->where('company', $company);
    }

    private function positionList(): array
    {
        return Employee::active()
            ->selectRaw('job_code as code, MAX(job_th) as th, MAX(job_en) as en, COUNT(*) as cnt')
            ->whereNotNull('job_code')
            ->where('job_code', '!=', '')
            ->groupBy('job_code')
            ->orderBy('th')
            ->get()
            ->map(fn ($position) => [
                'code' => $position->code,
                'name_th' => $position->th ?: $position->en ?: $position->code,
                'name_en' => $position->en ?: $position->th ?: $position->code,
                'name_my' => $position->en ?: $position->th ?: $position->code,
                'count' => (int) $position->cnt,
            ])
            ->values()
            ->all();
    }

    private function companyDirectory(): array
    {
        $assignments = OtDepartmentAssignment::orderBy('company')->orderBy('dept_code')->get();
        $users = AppUser::whereIn('id', $assignments->pluck('app_user_id'))->get()->keyBy('id');
        $identities = $users->pluck('id_thai_hash')->filter()->unique()->values();
        $employees = $identities->isEmpty()
            ? collect()
            : Employee::active()->whereIn('license_id', $identities)->get()->keyBy(
                fn (Employee $employee) => $employee->license_id.'|'.$employee->employee_code,
            );
        /* จัดกลุ่มแทนการ keyBy เพราะ Foreman มีได้หลายคนต่อแผนกแล้ว
           Supervisor ยังมีได้คนเดียว แต่ใช้โครงเดียวกันเพื่อไม่ต้องแยก 2 ทางในหน้าจอ */
        /* คีย์รวม module ด้วย เพราะ OT กับการลากำหนดคนแยกกันแล้ว
           แถวเก่าที่ยังไม่มีค่าถือเป็นของ OT */
        $assignmentMap = $assignments->groupBy(fn (OtDepartmentAssignment $item) => implode('|', [
            $item->module ?: OtDepartmentAssignment::MODULE_OT,
            $item->company,
            $item->dept_code,
            $item->role,
        ]));

        return collect(self::COMPANIES)->map(function (string $label, string $company) use ($assignmentMap, $users, $employees) {
            $departments = Employee::active()
                ->where('company', $company)
                ->selectRaw("COALESCE(NULLIF(dept_code, ''), '__none__') as dcode, MAX(dept_th) as dept_th, MAX(dept_en) as dept_en, COUNT(*) as cnt")
                ->groupBy('dcode')
                ->orderByDesc('cnt')
                ->orderBy('dept_th')
                ->get()
                ->map(function ($department) use ($company, $assignmentMap, $users, $employees) {
                    $deptCode = $department->dcode === '__none__' ? '' : $department->dcode;
                    // โครงเป็น assignments[module][role] เพื่อให้หน้าตั้งค่ามีชุดของ OT และของการลาแยกกัน
                    $roles = [];
                    foreach (OtDepartmentAssignment::MODULES as $module) {
                        foreach (OtDepartmentAssignment::ROLES as $role) {
                            $roles[$module][$role] = collect($assignmentMap->get(implode('|', [$module, $company, $deptCode, $role]), []))
                                ->map(function (OtDepartmentAssignment $assignment) use ($users, $employees) {
                                    $user = $users->get($assignment->app_user_id);

                                    return $this->assignmentPayload(
                                        $assignment,
                                        $user,
                                        $user ? $employees->get($user->id_thai_hash.'|'.$assignment->employee_code) : null,
                                    );
                                })
                                ->sortBy('name_th', SORT_NATURAL)
                                ->values()
                                ->all();
                        }
                    }

                    return [
                        'dept_code' => $deptCode,
                        'name_th' => $department->dept_th ?: $department->dept_en ?: 'ไม่ระบุแผนก',
                        'name_en' => $department->dept_en ?: $department->dept_th ?: 'Unassigned department',
                        'name_my' => $department->dept_en ?: $department->dept_th ?: 'Unassigned department',
                        'count' => (int) $department->cnt,
                        'assignments' => $roles,
                    ];
                })
                ->values()
                ->all();

            return [
                'code' => $company,
                'label' => $label,
                'department_count' => count($departments),
                'departments' => $departments,
            ];
        })->values()->all();
    }

    private function assignmentPayload(OtDepartmentAssignment $assignment, ?AppUser $user, ?Employee $employee): array
    {
        $nameTh = $employee?->fullNameTh() ?: $user?->fullNameTh() ?: $assignment->employee_code;
        $nameEn = $employee?->fullNameEn() ?: $user?->full_name_en ?: $nameTh;
        $companyCode = $employee?->company ?: '';
        $companyLabel = self::COMPANIES[$companyCode] ?? $companyCode;

        return [
            'id' => (int) $assignment->id,
            'code' => $assignment->employee_code,
            // ต้องส่งออกไปด้วย ไม่งั้นตอนเปลี่ยนกะใน dropdown จะไม่มี id ของคนที่จะอัปเดต
            'app_user_id' => (int) $assignment->app_user_id,
            'shift_group' => $assignment->usesShiftGroup() ? $assignment->shift_group : null,
            'shift_label_th' => $assignment->usesShiftGroup() ? $assignment->shiftGroupLabel('th') : '',
            'shift_label_en' => $assignment->usesShiftGroup() ? $assignment->shiftGroupLabel('en') : '',
            'shift_label_my' => $assignment->usesShiftGroup() ? $assignment->shiftGroupLabel('my') : '',
            'company_code' => $companyCode,
            'company_th' => $companyLabel,
            'company_en' => $companyLabel,
            'company_my' => $companyLabel,
            'name_th' => $nameTh,
            'name_en' => $nameEn,
            'name_my' => $nameEn,
            'position_th' => $employee?->job_th ?: $employee?->job_en ?: $user?->position ?: '',
            'position_en' => $employee?->job_en ?: $employee?->job_th ?: $user?->position ?: '',
            'position_my' => $employee?->job_en ?: $employee?->job_th ?: $user?->position ?: '',
            'department_th' => $employee?->dept_th ?: $employee?->dept_en ?: '',
            'department_en' => $employee?->dept_en ?: $employee?->dept_th ?: '',
            'department_my' => $employee?->dept_en ?: $employee?->dept_th ?: '',
            'avatar' => $user?->profile_picture ? asset('storage/'.$user->profile_picture) : null,
        ];
    }

    private function appUserPayload(?AppUser $user, string $fallbackCode, array $extra = []): array
    {
        $nameTh = $user?->fullNameTh() ?: $user?->full_name_en ?: $fallbackCode;
        $nameEn = $user?->full_name_en ?: $nameTh;

        return array_merge([
            'code' => $user?->employee_code ?: $fallbackCode,
            'name_th' => $nameTh,
            'name_en' => $nameEn,
            'name_my' => $nameEn,
            'position' => $user?->position ?: '',
            'position_th' => $user?->position ?: '',
            'position_en' => $user?->position ?: '',
            'position_my' => $user?->position ?: '',
            'avatar' => $user?->profile_picture ? asset('storage/'.$user->profile_picture) : null,
        ], $extra);
    }
}
