<?php

namespace App\Http\Controllers\Insight;

use App\Http\Controllers\Controller;
use App\Models\Insight\AppUser;
use App\Models\Insight\Employee;
use App\Services\Insight\BplusLeaveRightService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\View;
use Illuminate\View\View as ViewContract;

/**
 * แยกหน้าตาม role: admin -> หน้าผู้ดูแล, user -> หน้าพนักงานทั่วไป
 */
class DashboardController extends Controller
{
    /** บริษัทที่ระบบรู้จัก (แสดงทุกตัวแม้ active = 0 เช่น INNOMED) */
    private const COMPANIES = [
        'SUPAVUT_INDUSTRY' => 'Supavut Industry',
        'MOLDVANTO' => 'Moldvanto',
        'SUPAVUT_INNOMED' => 'Supavut Innomed',
    ];

    /**
     * หน้าหลักหลังล็อกอิน — user และ admin เห็นหน้าโปรไฟล์เดียวกัน
     * (ฟังก์ชันเฉพาะ admin แยกไปที่ admin.overview)
     */
    public function index(): ViewContract
    {
        $me = app('current_user');

        return View::make('insight.dashboard.user', [
            'me' => $me,
            'emp' => $me->employee,
        ]);
    }

    public function adminOverview(): ViewContract
    {
        // นับเฉพาะพนักงานที่ยังทำงาน (active) ; employees ยังเก็บคนลาออกไว้เพื่อย้อนหลัง
        $activeByCompany = Employee::active()->selectRaw('company, count(*) as n')
            ->groupBy('company')->pluck('n', 'company')->all();
        $resignedByCompany = Employee::resignationRecorded()->selectRaw('company, count(*) as n')
            ->groupBy('company')->pluck('n', 'company')->all();
        $employeeTotalsByCompany = Employee::query()->selectRaw('company, count(*) as n')
            ->groupBy('company')->pluck('n', 'company')->all();
        $pendingResignationCount = Employee::pendingResignation()->count();
        $payrollClosedCount = Employee::payrollClosedResignation()->count();

        // โครง บริษัท -> แผนก (เฉพาะ active) แสดงครบทุกบริษัทที่รู้จัก แม้ 0 คน
        $companies = [];
        foreach (self::COMPANIES as $code => $label) {
            $depts = Employee::active()->where('company', $code)
                ->selectRaw("COALESCE(NULLIF(dept_code, ''), '__none__') as dcode,
                             MAX(dept_th) as dept_th, MAX(dept_en) as dept_en,
                             count(*) as n")
                ->groupBy('dcode')
                ->orderByDesc('n')
                ->get()
                ->map(fn ($d) => [
                    'dept_code' => $d->dcode === '__none__' ? '' : $d->dcode,
                    'dept_th' => $d->dept_th,
                    'dept_en' => $d->dept_en,
                    'count' => (int) $d->n,
                ])->all();

            $companies[] = [
                'code' => $code,
                'label' => $label,
                'active' => (int) ($activeByCompany[$code] ?? 0),
                'resigned' => (int) ($resignedByCompany[$code] ?? 0),
                'total' => (int) ($employeeTotalsByCompany[$code] ?? 0),
                'dept_count' => count($depts),
                'departments' => $depts,
            ];
        }

        // รายชื่อที่มีรายการลาออกทั้งหมด: รวมรอปิดงวด, ปิดงวด และ Local override
        $resignedEmployees = Employee::resignationRecorded()
            ->orderByRaw('COALESCE(resign_date, manual_resign_date, pending_resign_date) IS NULL')
            ->orderByRaw('COALESCE(resign_date, manual_resign_date, pending_resign_date) DESC')
            ->orderBy('employee_code')
            ->get();
        $resignedList = $this->resignedEmployeeRows($resignedEmployees);

        $resignedDepartments = collect($resignedList)
            ->pluck('department')
            ->filter(fn (string $value): bool => $value !== '' && $value !== '-')
            ->unique()
            ->sort(SORT_NATURAL | SORT_FLAG_CASE)
            ->values()
            ->all();
        $resignedPositions = collect($resignedList)
            ->pluck('position')
            ->filter(fn (string $value): bool => $value !== '' && $value !== '-')
            ->unique()
            ->sort(SORT_NATURAL | SORT_FLAG_CASE)
            ->values()
            ->all();

        $activeDepartments = collect($companies)
            ->flatMap(function (array $company): array {
                return collect($company['departments'])
                    ->filter(fn (array $department): bool => $department['dept_code'] !== '')
                    ->map(fn (array $department): array => [
                        'key' => $company['code'].'|'.$department['dept_code'],
                        'name_th' => $department['dept_th'] ?: $department['dept_en'] ?: $department['dept_code'],
                        'name_en' => $department['dept_en'] ?: $department['dept_th'] ?: $department['dept_code'],
                        'company' => $company['label'],
                    ])->all();
            })
            ->sortBy(fn (array $department): string => mb_strtolower($department['name_th'].' '.$department['company']))
            ->values()
            ->all();

        $activePositions = Employee::active()
            ->whereNotNull('job_code')
            ->where('job_code', '!=', '')
            ->selectRaw('job_code as code, MAX(job_th) as name_th, MAX(job_en) as name_en')
            ->groupBy('job_code')
            ->get()
            ->map(fn ($position): array => [
                'code' => (string) $position->code,
                'name_th' => $position->name_th ?: $position->name_en ?: $position->code,
                'name_en' => $position->name_en ?: $position->name_th ?: $position->code,
            ])
            ->sortBy(fn (array $position): string => mb_strtolower($position['name_th']))
            ->values()
            ->all();

        $stats = [
            'active' => Employee::active()->count(),
            'resigned' => count($resignedList),
            'pending_resignation' => $pendingResignationCount,
            'payroll_closed' => $payrollClosedCount,
            'company_count' => count(self::COMPANIES),
            'companies' => $companies,
            'active_departments' => $activeDepartments,
            'active_positions' => $activePositions,
            'resigned_list' => $resignedList,
            'resigned_departments' => $resignedDepartments,
            'resigned_positions' => $resignedPositions,
        ];

        return View::make('insight.dashboard.admin', [
            'me' => app('current_user'),
            'stats' => $stats,
        ]);
    }

    /**
     * @param  Collection<int,Employee>  $employees
     * @return array<int,array<string,mixed>>
     */
    private function resignedEmployeeRows(Collection $employees): array
    {
        return $employees->map(function (Employee $employee): array {
            $effectiveResignDate = $employee->effectiveResignDate();
            $nameTh = $employee->fullNameTh() ?: $employee->employee_code;
            $nameEn = $employee->fullNameEn() ?: $employee->employee_code;

            return [
                'code' => (string) $employee->employee_code,
                'company_code' => (string) $employee->company,
                'company' => self::COMPANIES[$employee->company] ?? $employee->company,
                'name_th' => $nameTh,
                'name_en' => $nameEn,
                'search_text' => mb_strtolower(trim(implode(' ', [
                    $employee->employee_code,
                    $nameTh,
                    $nameEn,
                ]))),
                'position' => $employee->job_th ?: $employee->job_en ?: '-',
                'department' => $employee->dept_th ?: $employee->dept_en ?: '-',
                'resign_date' => $effectiveResignDate?->format('d/m/Y') ?? '-',
                'pending_resignation' => (string) $employee->emp_status === '1'
                    && $employee->manual_resign_date === null
                    && $employee->pending_resign_date !== null,
                'payroll_closed' => (string) $employee->emp_status === '2',
                // รูปเกาะกับ employees แล้ว จึงยังแสดงได้แม้บัญชีถูกลบตอนลาออก
                'avatar' => $employee->photoUrl(),
            ];
        })->all();
    }

    /** ค้นหาพนักงานที่ยังทำงานอยู่ตามรหัส/ชื่อ แผนก และตำแหน่ง */
    public function searchActiveEmployees(Request $request): JsonResponse
    {
        $data = $request->validate([
            'query' => ['nullable', 'string', 'max:100'],
            'department' => ['nullable', 'string', 'max:100'],
            'position' => ['nullable', 'string', 'max:50'],
        ]);

        $search = trim((string) ($data['query'] ?? ''));
        $department = trim((string) ($data['department'] ?? ''));
        $position = trim((string) ($data['position'] ?? ''));

        if ($search === '' && $department === '' && $position === '') {
            return response()->json(['message' => 'กรุณาระบุคำค้นหา แผนก หรือตำแหน่ง'], 422);
        }

        $query = Employee::active();

        if ($search !== '') {
            foreach (preg_split('/\s+/u', $search, -1, PREG_SPLIT_NO_EMPTY) ?: [] as $token) {
                $like = '%'.addcslashes($token, '%_\\').'%';
                $query->where(function ($employee) use ($like): void {
                    $employee->where('employee_code', 'like', $like)
                        ->orWhere('title', 'like', $like)
                        ->orWhere('name_th', 'like', $like)
                        ->orWhere('surname_th', 'like', $like)
                        ->orWhere('name_en', 'like', $like);
                });
            }
        }

        if ($department !== '') {
            [$company, $deptCode] = array_pad(explode('|', $department, 2), 2, '');
            $query->where('company', $company)->where('dept_code', $deptCode);
        }

        if ($position !== '') {
            $query->where('job_code', $position);
        }

        $total = (clone $query)->count();
        $employees = $query->orderBy('name_th')->orderBy('name_en')->limit(300)->get();

        return response()->json([
            'employees' => $this->employeeDirectoryRows($employees),
            'total' => $total,
            'limited' => $total > $employees->count(),
        ]);
    }

    /**
     * รายชื่อพนักงาน active ในบริษัท+แผนกหนึ่ง (AJAX) — สำหรับ drill-down ในหน้า admin overview
     * เชื่อมรูปโปรไฟล์จาก app_users ผ่าน license_id = id_thai_hash (รองรับคนข้ามบริษัทด้วย)
     */
    public function departmentEmployees(Request $request): JsonResponse
    {
        $company = (string) $request->query('company', '');
        $deptCode = (string) $request->query('dept_code', '');

        abort_unless(array_key_exists($company, self::COMPANIES), 404);

        $query = Employee::active()->where('company', $company);
        if ($deptCode === '') {
            $query->where(fn ($q) => $q->whereNull('dept_code')->orWhere('dept_code', ''));
        } else {
            $query->where('dept_code', $deptCode);
        }

        $employees = $query->orderBy('name_th')->orderBy('name_en')->get();

        return response()->json(['employees' => $this->employeeDirectoryRows($employees)]);
    }

    /** รายละเอียดพนักงานและสิทธิ์ลาปัจจุบันจาก B Plus (ผู้ดูแลระบบเท่านั้น) */
    public function employeeLeaveRights(
        Request $request,
        BplusLeaveRightService $leaveRightService,
    ): JsonResponse {
        $data = $request->validate([
            'company' => ['required', 'string', 'in:'.implode(',', array_keys(self::COMPANIES))],
            'employee_code' => ['required', 'string', 'max:30'],
        ]);

        $employee = Employee::active()
            ->where('company', $data['company'])
            ->where('employee_code', $data['employee_code'])
            ->firstOrFail();
        $directoryRow = $this->employeeDirectoryRows(collect([$employee]))[0];

        return response()->json([
            'employee' => array_merge($directoryRow, [
                'company_label' => self::COMPANIES[$employee->company] ?? $employee->company,
            ]),
            'leave' => $leaveRightService->forEmployee(
                (string) $employee->company,
                (string) $employee->employee_code,
            ),
        ]);
    }

    /**
     * แปลงพนักงานเป็นข้อมูล Directory พร้อมรูปโปรไฟล์
     *
     * @param  Collection<int,Employee>  $employees
     * @return array<int,array<string,mixed>>
     */
    private function employeeDirectoryRows(Collection $employees): array
    {
        // รูปโปรไฟล์: license_id (employees) <-> id_thai_hash (app_users)
        $licenses = $employees->pluck('license_id')->filter()->unique()->values();
        $avatars = $licenses->isEmpty()
            ? collect()
            : AppUser::whereIn('id_thai_hash', $licenses)
                ->pluck('profile_picture', 'id_thai_hash');

        $list = $employees->map(function (Employee $e) use ($avatars) {
            $pic = $e->license_id ? ($avatars[$e->license_id] ?? null) : null;
            $nameTh = trim((string) $e->fullNameTh());
            $nameEn = trim((string) ($e->name_en ?? ''));

            return [
                'company' => $e->company,
                'code' => $e->employee_code,
                'name_th' => $nameTh ?: $nameEn,
                'name_en' => $nameEn ?: $nameTh,
                'position_th' => $e->job_th ?: $e->job_en ?: '',
                'position_en' => $e->job_en ?: $e->job_th ?: '',
                'dept_th' => $e->dept_th ?: $e->dept_en ?: '',
                'dept_en' => $e->dept_en ?: $e->dept_th ?: '',
                'hire_date' => $e->hire_date ? $e->hire_date->format('d/m/Y') : '-',
                // employees.photo_path มาก่อน แล้วค่อยถอยไปรูปในบัญชี (คนใหม่ที่ยังไม่มีบัญชีจึงมีรูปได้)
                'avatar' => $e->photoUrl($pic),
                'initial' => mb_strtoupper(mb_substr($nameTh ?: $nameEn ?: $e->employee_code, 0, 1)),
            ];
        })->values();

        return $list->all();
    }
}
