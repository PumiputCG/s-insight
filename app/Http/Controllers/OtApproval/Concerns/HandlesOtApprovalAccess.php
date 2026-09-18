<?php

namespace App\Http\Controllers\OtApproval\Concerns;

use App\Models\Insight\Employee;
use App\Models\Insight\Setting;
use App\Models\OtApproval\OtDepartmentAssignment;
use App\Models\OtApproval\OtMember;
use App\Support\OtApproval\OtShiftGroup;
use Illuminate\Http\RedirectResponse;

trait HandlesOtApprovalAccess
{
    protected function me()
    {
        return app('current_user');
    }

    protected function isInsightAdmin(): bool
    {
        $me = $this->me();

        return (bool) ($me && $me->isAdmin());
    }

    protected function isOtMemberAdmin(): bool
    {
        $me = $this->me();

        return (bool) ($me && ! $me->isAdmin() && OtMember::isAdmin((int) $me->id));
    }

    protected function isOtAdmin(): bool
    {
        return $this->isInsightAdmin() || $this->isOtMemberAdmin();
    }

    protected function isOtDepartmentRole(): bool
    {
        $me = $this->me();

        return $me && OtDepartmentAssignment::hasAnyRole((int) $me->id);
    }

    /**
     * ตรวจบทบาทของระบบใดระบบหนึ่ง — OT กับการลากำหนดคนแยกกัน
     *
     * ไม่ระบุ module ถือว่าเป็นของ OT เพื่อให้โค้ดเดิมที่เรียกอยู่ทำงานเหมือนเดิม
     */
    protected function hasOtRole(string $role, string $module = OtDepartmentAssignment::MODULE_OT): bool
    {
        if ($this->isInsightAdmin()) {
            return true;
        }

        $me = $this->me();

        return (bool) ($me && OtDepartmentAssignment::query()
            ->forModule($module)
            ->where('app_user_id', (int) $me->id)
            ->where('role', $role)
            ->exists());
    }

    protected function gateOtRole(string $role, string $module = OtDepartmentAssignment::MODULE_OT): ?RedirectResponse
    {
        return $this->hasOtRole($role, $module)
            ? null
            : $this->denyToSystems('toast.pageDenied', 'คุณไม่มีสิทธิ์เข้าหน้านี้');
    }

    /**
     * Insight Admin ทำ Workflow ได้ทุกแผนก ส่วนผู้ใช้อื่นเห็นเฉพาะ Assignment ของบทบาทนั้น
     *
     * @return array{all:bool,departments:array<int,array{company:string,dept_code:string}>,employees:array<int,mixed>}
     */
    protected function workflowScope(string $role, string $module = OtDepartmentAssignment::MODULE_OT): array
    {
        if ($this->isInsightAdmin()) {
            return ['all' => true, 'departments' => [], 'employees' => []];
        }

        $me = $this->me();
        $departments = OtDepartmentAssignment::query()
            ->forModule($module)
            ->where('app_user_id', (int) $me->id)
            ->where('role', $role)
            ->get(['company', 'dept_code'])
            ->map(fn (OtDepartmentAssignment $assignment) => [
                'company' => $assignment->company,
                'dept_code' => trim((string) $assignment->dept_code),
            ])
            ->values()
            ->all();

        return ['all' => false, 'departments' => $departments, 'employees' => []];
    }

    /**
     * กะที่ Admin กำหนดให้ Foreman คนนี้ดูแล แยกตามแผนกและตาม module
     * (คนเดียวเป็น Foreman ได้หลายแผนกคนละกะ และ OT กับการลาเซ็ตแยกกันได้)
     *
     * @return array<string, array<string, string>>
     */
    protected function myShiftByDepartment(string $module = OtDepartmentAssignment::MODULE_OT): array
    {
        $me = $this->me();
        if (! $me) {
            return [];
        }

        return OtDepartmentAssignment::query()
            ->forModule($module)
            ->where('app_user_id', (int) $me->id)
            ->where('role', OtDepartmentAssignment::ROLE_FOREMAN)
            ->whereNotNull('shift_group')
            ->get(['company', 'dept_code', 'shift_group'])
            ->mapWithKeys(fn (OtDepartmentAssignment $assignment) => [
                $assignment->company.'|'.trim((string) $assignment->dept_code) => [
                    'key' => $assignment->shift_group,
                    'filter_value' => OtShiftGroup::filterValue($assignment->shift_group),
                    'label_th' => OtShiftGroup::label($assignment->shift_group, 'th'),
                    'label_en' => OtShiftGroup::label($assignment->shift_group, 'en'),
                    'label_my' => OtShiftGroup::label($assignment->shift_group, 'my'),
                ],
            ])
            ->all();
    }

    /** Time & Leave Approval ใช้ default deny: ยังไม่ตั้งค่าตำแหน่ง = คนทั่วไปยังเข้าไม่ได้ */
    protected function otPositionAllowed(): bool
    {
        $allowed = Setting::get(Setting::OT_APPROVAL_POSITIONS, []);
        if (! is_array($allowed) || $allowed === []) {
            return false;
        }

        $me = $this->me();
        if (! $me) {
            return false;
        }

        $identity = trim((string) ($me->id_thai_hash ?? ''));
        if ($identity !== '') {
            return Employee::active()
                ->where('license_id', $identity)
                ->whereIn('job_code', $allowed)
                ->exists();
        }

        return Employee::active()
            ->where('employee_code', (string) $me->employee_code)
            ->whereIn('job_code', $allowed)
            ->exists();
    }

    protected function gateOtEnter(): ?RedirectResponse
    {
        if ($this->isOtAdmin() || $this->isOtDepartmentRole() || $this->otPositionAllowed()) {
            return null;
        }

        return $this->denyToSystems(
            'toast.otApprovalDenied',
            'คุณยังไม่ได้รับสิทธิ์เข้าใช้ระบบ Time & Leave Approval',
        );
    }

    protected function gateOtAdmin(): ?RedirectResponse
    {
        if ($this->isOtAdmin()) {
            return null;
        }

        return $this->denyToSystems('toast.pageDenied', 'คุณไม่มีสิทธิ์เข้าหน้านี้');
    }

    protected function denyToSystems(string $key, string $fallback): RedirectResponse
    {
        return redirect()
            ->route('systems.index')
            ->with('toast_key', $key)
            ->with('toast_fallback', $fallback)
            ->with('toast_type', 'error');
    }
}
