<?php

namespace App\Http\Controllers\OtApproval;

use App\Http\Controllers\Controller;
use App\Http\Controllers\OtApproval\Concerns\HandlesOtApprovalAccess;
use App\Models\Insight\Employee;
use App\Models\OtApproval\LeaveRequest;
use App\Models\OtApproval\OtAttendanceSnapshot;
use App\Models\OtApproval\OtDepartmentAssignment;
use App\Models\OtApproval\OtRequest;
use App\Services\OtApproval\BplusAttendanceService;
use App\Services\OtApproval\OtDownloadCalendarService;
use App\Services\OtApproval\OtRequestWorkflowService;
use App\Support\OtApproval\OtShiftGroup;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View as ViewContract;

class OtApprovalController extends Controller
{
    use HandlesOtApprovalAccess;

    /** หน้าหลัก — ใช้ภาพรวมเวลาเข้า-ออกตาม URL หลักของแอป */
    public function home(
        Request $request,
        BplusAttendanceService $attendance,
        OtRequestWorkflowService $workflow,
    ): ViewContract|RedirectResponse {
        return $this->index($request, $attendance, $workflow);
    }

    /** ภาพรวมเวลาเข้า-ออกจาก Bplus แยกตามวัน บริษัท และแผนก */
    public function index(
        Request $request,
        BplusAttendanceService $attendance,
        OtRequestWorkflowService $workflow,
    ): ViewContract|RedirectResponse {
        if ($redirect = $this->gateOtEnter()) {
            return $redirect;
        }

        $date = $this->attendanceDate($request);
        $scope = $this->attendanceScope();

        $summary = $workflow->decorateSummary($attendance->summary($date, $scope), $date, $scope);

        return view('ot_approval.index', [
            'me' => $this->me(),
            'isOtAdmin' => $this->isOtAdmin(),
            'attendance' => $summary,
            'selectedDate' => $date,
        ]);
    }

    /** หน้าสำหรับ Foreman เลือกพนักงานและชั่วโมง OT ที่ต้องการขอ */
    public function requests(
        Request $request,
        BplusAttendanceService $attendance,
        OtRequestWorkflowService $workflow,
    ): ViewContract|RedirectResponse {
        if ($redirect = $this->gateOtEnter()) {
            return $redirect;
        }

        if ($redirect = $this->gateOtRole(OtDepartmentAssignment::ROLE_FOREMAN)) {
            return $redirect;
        }

        $date = $this->attendanceDate($request);
        $scope = $this->workflowScope(OtDepartmentAssignment::ROLE_FOREMAN);
        $summary = $workflow->decorateSummary($attendance->summary($date, $scope), $date, $scope);

        return view('ot_approval.requests', [
            'me' => $this->me(),
            'isOtAdmin' => $this->isOtAdmin(),
            'attendance' => $summary,
            'selectedDate' => $date,
            'otTypes' => $workflow->types(),
            'myShiftByDept' => $this->myShiftByDepartment(),
        ]);
    }

    /**
     * กะที่ Foreman คนนี้ถูกกำหนดให้ดูแล แยกตามแผนก
     *
     * คนเดียวเป็น Foreman ได้หลายแผนกและคนละกะกัน (เช่น 69739 คุมกะเวลา Aที่ SPV004
     * และกะเวลา Bที่ SPV010) หน้าจอจึงต้องแสดงกะ "ของแผนกที่เปิดอยู่" ไม่ใช่เอามารวมกัน
     * ไม่งั้นจะขึ้นทั้งเช้าและดึกพร้อมกันจนดูไม่ออกว่า Admin เซ็ตอะไรไว้
     *
     * ใช้เป็นค่าเริ่มต้นของตัวกรอง ไม่ใช่สิทธิ์ — ค่า all หมายถึงดูแลทุกกะ ส่วน
     * Foreman ยังเปลี่ยนตัวกรองไปดูกะอื่นได้ตามกฎข้ามกะเดิม
     *
     * @return array<string, array{key: string, filter_value: string, label_th: string, label_en: string, label_my: string}>
     */
    /** หน้าสำหรับ Supervisor ตรวจสอบและอนุมัติคำขอ OT */
    public function approvals(Request $request, OtRequestWorkflowService $workflow): ViewContract|RedirectResponse
    {
        if ($redirect = $this->gateOtEnter()) {
            return $redirect;
        }

        if ($redirect = $this->gateOtRole(OtDepartmentAssignment::ROLE_SUPERVISOR)) {
            return $redirect;
        }

        return view('ot_approval.approvals', [
            'me' => $this->me(),
            'isOtAdmin' => $this->isOtAdmin(),
            'selectedDate' => $this->approvalDate($request),
            // ใช้ทำตัวเลือกของตัวกรอง "ประเภท OT" ในแถบเครื่องมือ
            'otTypes' => $workflow->types(),
        ]);
    }

    /**
     * วันที่เริ่มต้นของปฏิทินหน้าอนุมัติ — null คือ "ทุกวัน" ซึ่งเป็นค่าตั้งต้น
     *
     * ต่างจาก attendanceDate() ตรงที่ไม่ throw เมื่อค่าผิดรูปแบบ เพราะลิงก์ที่ถูกแชร์
     * หรือ bookmark เก่าไม่ควรทำให้หน้าอนุมัติเปิดไม่ขึ้น — ตกกลับไปเป็นทุกวันแทน
     */
    private function approvalDate(Request $request): ?CarbonImmutable
    {
        $raw = trim((string) $request->query('date', ''));

        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $raw)) {
            return null;
        }

        $date = rescue(
            fn () => CarbonImmutable::createFromFormat('Y-m-d', $raw)->startOfDay(),
            null,
            false,
        );

        return $date && $date->lessThanOrEqualTo(CarbonImmutable::today()) ? $date : null;
    }

    /** ข้อมูลสรุปสำหรับรีเฟรชวันปัจจุบันโดยไม่ Reload หน้า */
    public function attendanceSummary(
        Request $request,
        BplusAttendanceService $attendance,
        OtRequestWorkflowService $workflow,
    ): JsonResponse {
        if ($this->gateOtEnter()) {
            return response()->json(['ok' => false, 'message' => 'no permission'], 403);
        }

        $date = $this->attendanceDate($request);

        $scope = $this->attendanceScope();

        return response()->json([
            'ok' => true,
            'attendance' => $workflow->decorateSummary($attendance->summary($date, $scope), $date, $scope),
        ]);
    }

    /** รายชื่อพนักงานและเวลาเข้า-ออกของแผนกที่ผู้ใช้กดเปิด */
    public function attendanceEmployees(
        Request $request,
        BplusAttendanceService $attendance,
        OtRequestWorkflowService $workflow,
    ): JsonResponse {
        if ($this->gateOtEnter()) {
            return response()->json(['ok' => false, 'message' => 'no permission'], 403);
        }

        $data = $request->validate([
            'company' => ['required', 'string', 'in:'.implode(',', array_keys(OtDepartmentAssignment::COMPANIES))],
            'dept_code' => ['nullable', 'string', 'max:50'],
        ]);
        $date = $this->attendanceDate($request);

        $payload = $attendance->employees(
            $date,
            $data['company'],
            trim((string) ($data['dept_code'] ?? '')),
            $this->attendanceScope(),
        );

        return response()->json([
            'ok' => true,
            'attendance' => $workflow->decorateEmployeePayload($payload, $date),
        ]);
    }

    /**
     * หน้ารวมการดาวน์โหลดเอกสาร OT — เห็นเฉพาะ admin
     *
     * แยกออกมาจากหน้าภาพรวมเพราะการโหลดไฟล์ให้ HR เป็นงานคนละจังหวะกับการดูสถานะรายวัน
     * และต้องย้อนไปโหลดวันไหนก็ได้ในเดือน ไม่ใช่เฉพาะวันที่เปิดค้างอยู่
     */
    public function downloads(Request $request, OtDownloadCalendarService $calendar): ViewContract|RedirectResponse
    {
        if ($redirect = $this->gateOtEnter()) {
            return $redirect;
        }

        if ($redirect = $this->gateOtAdmin()) {
            return $redirect;
        }

        $month = $this->calendarMonth($request);

        return view('ot_approval.downloads', [
            'me' => $this->me(),
            'isOtAdmin' => $this->isOtAdmin(),
            'selectedMonth' => $month,
            'calendar' => $calendar->month($month, $this->downloadScope()),
        ]);
    }

    /** ข้อมูลปฏิทินรายเดือนสำหรับเปลี่ยนเดือนโดยไม่ Reload หน้า */
    public function downloadCalendar(Request $request, OtDownloadCalendarService $calendar): JsonResponse
    {
        if ($this->gateOtEnter() || ! $this->isOtAdmin()) {
            return response()->json(['ok' => false, 'message' => 'no permission'], 403);
        }

        return response()->json([
            'ok' => true,
            'calendar' => $calendar->month($this->calendarMonth($request), $this->downloadScope()),
        ]);
    }

    /** รายละเอียดของวันเดียวสำหรับ modal — ตารางรายคนทั้ง OT และการลา พร้อมประวัติการโหลด */
    public function downloadDay(Request $request, OtDownloadCalendarService $calendar): JsonResponse
    {
        if ($this->gateOtEnter() || ! $this->isOtAdmin()) {
            return response()->json(['ok' => false, 'message' => 'no permission'], 403);
        }

        $data = $request->validate(['date' => ['required', 'date_format:Y-m-d']]);
        $date = CarbonImmutable::createFromFormat('Y-m-d', $data['date'])->startOfDay();

        return response()->json([
            'ok' => true,
            'day' => $calendar->day($date, $this->downloadScope()),
        ]);
    }

    /**
     * ขอบเขตข้อมูลของหน้าดาวน์โหลด — admin ทุกคนเห็นเท่ากันทั้งบริษัท
     *
     * ต่างจาก scope ของหน้าอนุมัติที่ผูกกับแผนกที่รับผิดชอบ เพราะการโหลดไฟล์ส่ง HR
     * เป็นงานที่ admin ทำแทนกันได้ ถ้าใช้ scope ของ Supervisor คนที่เป็น OT Admin
     * แต่ไม่ได้เป็น Insight Admin จะเห็นแค่แผนกตัวเอง แล้วไฟล์ที่ส่งให้ HR จะขาดแผนกอื่น
     * เข้าถึงได้เฉพาะคนที่ผ่าน gateOtAdmin() มาแล้วเท่านั้น
     *
     * @return array{all:bool,departments:array<int,mixed>,employees:array<int,mixed>}
     */
    private function downloadScope(): array
    {
        return ['all' => true, 'departments' => [], 'employees' => []];
    }

    /**
     * ประวัติ OT และการลาของพนักงานรายคน ทั้งเดือน
     *
     * เปิดจากหน้าภาพรวมเป็นแท็บใหม่ เพื่อดูว่าคนคนนี้ทำ OT และลาไปกี่ครั้งในรอบเดือน
     * โดยไม่ต้องไล่เปิดทีละวัน — อ่านอย่างเดียว ไม่มีปุ่มแก้ไข
     */
    public function employeeMonth(
        Request $request,
        string $company,
        string $employeeCode,
    ): ViewContract|RedirectResponse {
        if ($redirect = $this->gateOtEnter()) {
            return $redirect;
        }

        $employee = Employee::query()
            ->where('company', $company)
            ->where('employee_code', trim($employeeCode))
            ->first();

        if (! $employee) {
            return $this->denyToSystems('toast.pageDenied', 'ไม่พบพนักงานคนนี้');
        }

        return $this->renderWorkDetail($request, $employee, route('ot-approval.employees.month', [
            'company' => $employee->company,
            'employeeCode' => $employee->employee_code,
        ]), false);
    }

    /**
     * รายละเอียดการทำงานของตัวเอง — ผู้ใช้ทุกคนที่ผูกกับพนักงานเข้าดูได้
     * ไม่ผ่าน gateOtEnter เพราะเป็นข้อมูลของตัวเอง ไม่ใช่ของทีม
     */
    public function myWorkDetail(Request $request): ViewContract|RedirectResponse
    {
        $me = $this->me();
        $code = trim((string) ($me->employee_code ?? ''));

        $employee = $code === '' ? null : Employee::query()
            ->where('employee_code', $code)
            ->when(
                $me->company,
                fn ($query) => $query->orderByRaw('CASE WHEN company = ? THEN 0 ELSE 1 END', [$me->company]),
            )
            ->first();

        /* บัญชีผู้ดูแลระบบไม่ได้ผูกกับพนักงาน จึงไม่มีประวัติ OT/ลาของตัวเอง
           เดิมเด้งออกไป /systems ซึ่งดูเหมือนกดแล้วระบบพัง เปลี่ยนเป็นหน้าสถานะว่างที่บอกเหตุผล */
        if (! $employee) {
            return view('ot_approval.work-detail-empty', ['me' => $me]);
        }

        return $this->renderWorkDetail($request, $employee, route('ot-approval.work-detail'), true);
    }

    /** ตารางรายละเอียดการทำงานรายเดือน — ใช้ร่วมกันทั้งหน้าของตัวเองและของพนักงานคนอื่น */
    private function renderWorkDetail(
        Request $request,
        Employee $employee,
        string $pageUrl,
        bool $isSelf,
    ): ViewContract {
        $month = $this->calendarMonth($request);
        $start = $month->startOfMonth();
        $end = $month->endOfMonth();
        $company = $employee->company;
        $code = $employee->employee_code;

        // ตารางนี้เป็นสรุปแบบ SelfAttendance — แสดงเฉพาะรายการที่อนุมัติแล้วเท่านั้น
        $otRequests = OtRequest::query()
            ->where('company', $company)
            ->where('employee_code', $code)
            ->where('approval_status', OtRequest::APPROVAL_APPROVED)
            ->where('attendance_status', OtRequest::ATTENDANCE_PASSED)
            ->whereBetween('work_date', [$start->toDateString(), $end->toDateString()])
            ->orderBy('work_date')
            ->get();

        $leaveRequests = LeaveRequest::query()
            ->where('company', $company)
            ->where('employee_code', $code)
            ->where('approval_status', LeaveRequest::APPROVAL_APPROVED)
            ->whereBetween('leave_date', [$start->toDateString(), $end->toDateString()])
            ->orderBy('leave_date')
            ->get();

        $markedDays = $otRequests->pluck('work_date')
            ->merge($leaveRequests->pluck('leave_date'))
            ->map(fn ($date) => $date->format('Y-m-d'))
            ->unique()
            ->count();

        /*
         * เวลาสแกนเข้า-ออกอ่านจาก snapshot ท้องถิ่นที่ ot-approval:sync-attendance ดึงไว้
         * ไม่ยิง Bplus รายวันทั้งเดือน เพราะ query ของ Bplus แยกตามวัน จะกลายเป็น 31 รอบต่อการเปิดหนึ่งครั้ง
         */
        $attendance = OtAttendanceSnapshot::query()
            ->where('company', $company)
            ->where('employee_code', $code)
            ->whereBetween('work_date', [$start->toDateString(), $end->toDateString()])
            ->get()
            ->keyBy(fn (OtAttendanceSnapshot $row) => $row->work_date->format('Y-m-d'));

        return view('ot_approval.employee-month', [
            'me' => $this->me(),
            'isOtAdmin' => $this->isOtAdmin(),
            'employee' => $employee,
            'selectedMonth' => $month,
            'otRequests' => $otRequests,
            'leaveRequests' => $leaveRequests,
            'attendance' => $attendance,
            'pageUrl' => $pageUrl,
            'isSelf' => $isSelf,
            'summary' => [
                'ot_count' => $otRequests->count(),
                'ot_minutes' => $otRequests->sum(
                    fn (OtRequest $row) => ((int) $row->requested_hours * 60) + (int) $row->requested_minutes,
                ),
                'leave_count' => $leaveRequests->count(),
                'marked_days' => $markedDays,
            ],
        ]);
    }

    /** เดือนที่กำลังดู — ค่าผิดรูปแบบให้ตกกลับเป็นเดือนปัจจุบัน ไม่ throw ให้หน้าพัง */
    private function calendarMonth(Request $request): CarbonImmutable
    {
        $raw = trim((string) $request->query('month', ''));

        if (preg_match('/^\d{4}-\d{2}$/', $raw)) {
            $month = rescue(
                fn () => CarbonImmutable::createFromFormat('!Y-m-d', $raw.'-01'),
                null,
                false,
            );

            if ($month) {
                return $month->startOfMonth();
            }
        }

        return CarbonImmutable::today()->startOfMonth();
    }

    /**
     * วันที่ของภาพรวม OT — เวลาสแกนของวันข้างหน้ายังไม่เกิด จึงดูไม่ได้
     * แต่ห้าม throw ทิ้ง เพราะหน้าการลาเปิดวันล่วงหน้าได้ (ลา 75 ขอล่วงหน้า)
     * แล้วผู้ใช้กดแท็บกลับมาฝั่ง OT จะเจอหน้าพังแทนที่จะได้ภาพรวมของวันนี้
     */
    private function attendanceDate(Request $request): CarbonImmutable
    {
        $data = $request->validate([
            'date' => ['nullable', 'date_format:Y-m-d'],
        ]);

        $today = CarbonImmutable::today();
        if (! isset($data['date'])) {
            return $today;
        }

        $date = CarbonImmutable::createFromFormat('Y-m-d', $data['date'])->startOfDay();

        return $date->greaterThan($today) ? $today : $date;
    }

    /**
     * ขอบเขตข้อมูล: Insight Admin เห็นทั้งหมด, Foreman/Supervisor เห็นแผนกที่รับผิดชอบ,
     * พนักงานทั่วไปเห็นเฉพาะข้อมูลของตนเอง
     *
     * @return array{all:bool,departments:array<int,array{company:string,dept_code:string}>,employees:array<int,array{company:string,employee_code:string}>}
     */
    private function attendanceScope(): array
    {
        if ($this->isOtAdmin()) {
            return ['all' => true, 'departments' => [], 'employees' => []];
        }

        /* ต้องกรอง module ด้วย ไม่งั้นคนที่ถูกตั้งไว้เฉพาะฝั่งการลา
           จะเห็นพนักงานของแผนกนั้นในหน้าภาพรวม OT ทั้งที่ไม่มีสิทธิ์ฝั่ง OT
           ถ้าไม่มีสิทธิ์ฝั่งนี้เลย จะตกไปใช้ scope ของตัวเองด้านล่างเอง */
        $me = $this->me();
        $assignments = OtDepartmentAssignment::query()
            ->forModule(OtDepartmentAssignment::MODULE_OT)
            ->where('app_user_id', $me->id)
            ->get(['company', 'dept_code'])
            ->map(fn (OtDepartmentAssignment $assignment) => [
                'company' => $assignment->company,
                'dept_code' => trim((string) $assignment->dept_code),
            ])
            ->unique(fn (array $row) => $row['company'].'|'.$row['dept_code'])
            ->values()
            ->all();

        if ($assignments !== []) {
            return ['all' => false, 'departments' => $assignments, 'employees' => []];
        }

        $identity = trim((string) ($me->id_thai_hash ?? ''));
        $employeeRows = Employee::active()
            ->when(
                $identity !== '',
                fn ($query) => $query->where('license_id', $identity),
                fn ($query) => $query->whereIn('employee_code', array_values((array) ($me->companies ?? []))),
            )
            ->get(['company', 'employee_code'])
            ->map(fn (Employee $employee) => [
                'company' => $employee->company,
                'employee_code' => $employee->employee_code,
            ])
            ->values()
            ->all();

        return ['all' => false, 'departments' => [], 'employees' => $employeeRows];
    }

    /**
     * แถวสิทธิ์ที่พร้อมแสดงผล: ชื่อบทบาท + ขอบเขต (ชื่อแผนก ไม่ใช่รหัส) + ชื่อบริษัท
     *
     * @param  Collection<int, OtDepartmentAssignment>  $assignments
     * @return array<int, array<string, string>>
     */
    private function roleRows($assignments): array
    {
        // assignment เก็บแค่ dept_code จึงต้องดึงชื่อแผนกจาก employees มาประกอบ
        $names = [];
        $codes = $assignments->pluck('dept_code')->filter(fn ($code) => trim((string) $code) !== '')->unique();

        if ($codes->isNotEmpty()) {
            Employee::query()
                ->whereIn('dept_code', $codes->all())
                ->selectRaw('company, dept_code, MAX(dept_th) as dept_th, MAX(dept_en) as dept_en')
                ->groupBy('company', 'dept_code')
                ->get()
                ->each(function ($row) use (&$names) {
                    $names[$row->company.'|'.$row->dept_code] = [
                        'th' => $row->dept_th ?: $row->dept_en ?: $row->dept_code,
                        'en' => $row->dept_en ?: $row->dept_th ?: $row->dept_code,
                    ];
                });
        }

        $rows = [];

        if ($this->isInsightAdmin()) {
            $rows[] = [
                'role_key' => 'ot.role.admin',
                'role_label' => 'OT Admin',
                'scope_key' => 'ot.home.allDepartments',
                'scope_th' => 'ทุกแผนก',
                'scope_en' => 'All departments',
                'company' => '',
            ];
        }

        foreach ($assignments as $assignment) {
            $deptCode = trim((string) $assignment->dept_code);
            $name = $names[$assignment->company.'|'.$deptCode] ?? null;

            // ชื่อแผนกมี TH/EN ต่างกัน จึงปล่อยให้ Blade สลับด้วย data-val ไม่ผ่าน i18n dict
            $rows[] = [
                'role_key' => 'ot.role.'.$assignment->role,
                'role_label' => ucfirst($assignment->role),
                'scope_key' => '',
                'scope_th' => $name['th'] ?? ($deptCode ?: 'ไม่ระบุแผนก'),
                'scope_en' => $name['en'] ?? ($deptCode ?: 'Unassigned department'),
                'company' => OtDepartmentAssignment::COMPANIES[$assignment->company] ?? $assignment->company,
            ];
        }

        if ($rows === []) {
            $rows[] = [
                'role_key' => 'ot.role.employee',
                'role_label' => 'Employee',
                'scope_key' => 'ot.home.positionAccess',
                'scope_th' => 'สิทธิ์ตามตำแหน่ง',
                'scope_en' => 'Access by position',
                'company' => '',
            ];
        }

        return $rows;
    }
}
