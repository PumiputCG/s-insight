<?php

namespace App\Services\OtApproval;

use App\Mail\OtApproval\OtRequestSubmittedMail;
use App\Models\Insight\AppUser;
use App\Models\OtApproval\OtDepartmentAssignment;
use App\Models\OtApproval\OtMember;
use App\Models\OtApproval\OtNotification;
use App\Models\OtApproval\OtRequest;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * แจ้งเตือนของโมดูล OT — กระดิ่ง in-app ทุกบทบาท + อีเมลเฉพาะ "ขาส่ง"
 *
 * กติกาที่ Manager กำหนด:
 *  - ส่งอีเมลเฉพาะตอน Foreman กดส่งขออนุมัติ ไปหา Supervisor ของแผนกนั้น
 *  - ตอนอนุมัติ/ไม่อนุมัติ ไม่ส่งอีเมล แต่ยังเด้งกระดิ่งกลับให้ Foreman
 *  - 1 อีเมลต่อการกดส่ง 1 ครั้งต่อแผนก (รวมพนักงานทั้งล็อตเป็นตารางเดียว) กันเมลถี่
 */
class OtNotificationService
{
    /**
     * เรียกหลัง submitDrafts สำเร็จ และต้องอยู่ "นอก" transaction
     * เพื่อไม่ให้ส่งเมลออกไปแล้ว transaction ดันโรลแบ็ก
     *
     * @param  array<int, int|string>  $requestIds
     */
    public function notifySubmitted(array $requestIds, AppUser $foreman): void
    {
        $requests = OtRequest::query()->whereIn('id', $requestIds)->get();
        if ($requests->isEmpty()) {
            return;
        }

        $foremanName = $this->displayName($foreman);

        $requests
            ->groupBy(fn (OtRequest $request) => $request->company.'|'.trim((string) $request->dept_code))
            ->each(function (Collection $group) use ($foremanName) {
                /** @var OtRequest $sample */
                $sample = $group->first();
                $supervisor = $this->assignedUser(
                    $sample->company,
                    trim((string) $sample->dept_code),
                    OtDepartmentAssignment::ROLE_SUPERVISOR,
                );

                if (! $supervisor) {
                    Log::info('ot.notify: ยังไม่ได้กำหนด Supervisor ของแผนกนี้ ข้ามการแจ้งเตือน', [
                        'company' => $sample->company,
                        'dept_code' => $sample->dept_code,
                    ]);

                    return;
                }

                $workDate = $sample->work_date->format('Y-m-d');
                $deptName = $sample->department_name ?: $sample->dept_code;

                $this->store(
                    employeeCode: (string) $supervisor->employee_code,
                    type: OtNotification::TYPE_SUBMITTED,
                    title: 'มีคำขอ OT รออนุมัติ '.$group->count().' รายการ',
                    body: $deptName.' · วันที่ '.$sample->work_date->format('d/m/Y').' · ส่งโดย '.$foremanName,
                    link: route('ot-approval.approvals.index'),
                    itemCount: $group->count(),
                );

                $this->mailSupervisor($supervisor, $group, $foremanName, $workDate, $deptName);
            });
    }

    /** เรียกหลัง decide สำเร็จ — แจ้งกระดิ่งกลับหา Foreman ผู้สร้างคำขอ ไม่ส่งอีเมล */
    public function notifyDecided(OtRequest $request, AppUser $supervisor): void
    {
        $foremanCode = trim((string) $request->created_by_employee_code);
        if ($foremanCode === '') {
            return;
        }

        $approved = $request->approval_status === OtRequest::APPROVAL_APPROVED;

        $this->store(
            employeeCode: $foremanCode,
            type: OtNotification::TYPE_DECIDED,
            title: $approved ? 'คำขอ OT ได้รับอนุมัติแล้ว' : 'คำขอ OT ไม่ได้รับอนุมัติ',
            body: ($request->employee_name ?: $request->employee_code)
                .' · วันที่ '.$request->work_date->format('d/m/Y')
                .' · โดย '.$this->displayName($supervisor)
                .($request->decision_note ? ' · '.$request->decision_note : ''),
            link: route('ot-approval.requests.index', ['date' => $request->work_date->format('Y-m-d')]),
            itemCount: 1,
        );
    }

    /** @param string|array<int, string>|null $category */
    public function unreadCountFor(string $employeeCode, string|array|null $category = null): int
    {
        if ($employeeCode === '') {
            return 0;
        }

        return OtNotification::query()
            ->ownedBy($employeeCode)
            ->when($category !== null, fn ($query) => $query->whereIn('category', (array) $category))
            ->unread()
            ->count();
    }

    /** @return Collection<int, OtNotification> */
    /**
     * @param  string|array<int, string>|null  $category  จำกัดเฉพาะหมวดที่ต้องการ
     *
     * ต้องดึงแยกหมวด เพราะกระดิ่งกับกล่องเอกสารของ admin เป็นคนละกล่องบนหน้าจอ
     * ถ้าดึงรวมแล้วตัด 12 รายการ กล่องที่มีรายการเก่ากว่าจะว่างเปล่าทั้งที่มีข้อมูลอยู่
     */
    public function recentFor(string $employeeCode, int $limit = 12, string|array|null $category = null): Collection
    {
        if ($employeeCode === '') {
            return collect();
        }

        return OtNotification::query()
            ->ownedBy($employeeCode)
            ->when($category !== null, fn ($query) => $query->whereIn('category', (array) $category))
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }

    public function markAllRead(string $employeeCode, ?string $category = null): int
    {
        if ($employeeCode === '') {
            return 0;
        }

        return OtNotification::query()
            ->ownedBy($employeeCode)
            ->unread()
            // ระบุหมวด = ล้างเฉพาะกล่องนั้น ไม่ไปแตะกล่องอื่นบนหน้าจอ
            ->when($category !== null, fn ($query) => $query->where('category', $category))
            ->update(['read_at' => now()]);
    }

    public function markRead(string $employeeCode, int $id): bool
    {
        return OtNotification::query()
            ->ownedBy($employeeCode)
            ->whereKey($id)
            ->unread()
            ->update(['read_at' => now()]) > 0;
    }

    /**
     * แจ้ง admin ว่ามีเอกสาร OT พร้อมส่งให้ HR แล้ว
     *
     * ส่งถึง Insight Admin และ OT Admin ทุกคน เพราะเป็นกลุ่มเดียวที่ดาวน์โหลด V74 ได้
     * รายการที่ยื่นคนละวันกับวันทำ OT ถือเป็น "ย้อนหลัง" ซึ่ง admin ต้องรู้เป็นพิเศษ
     * เพราะถ้าวันนั้นเคยส่งไฟล์ให้ HR ไปแล้ว จะต้องโหลดใหม่ทับ ไม่งั้น Bplus ได้ข้อมูลไม่ครบ
     */
    public function notifyDownloadReady(OtRequest $request): void
    {
        $backdated = $request->submitted_at !== null
            && $request->submitted_at->toDateString() !== $request->work_date->toDateString();
        $workDate = $request->work_date->format('d/m/Y');

        $title = $backdated
            ? 'มีเอกสาร OT ย้อนหลังพร้อมดาวน์โหลด'
            : 'มีเอกสาร OT พร้อมดาวน์โหลด';
        $hours = (int) $request->requested_hours;
        $minutes = (int) $request->requested_minutes;
        $body = ($request->employee_name ?: $request->employee_code)
            .' · '.trim((string) $request->department_name)
            .' · วันที่ทำ OT '.$workDate
            .' · '.$hours.' ชม.'.($minutes > 0 ? ' '.$minutes.' นาที' : '')
            .($backdated ? ' · ยื่นย้อนหลังวันที่ '.$request->submitted_at->format('d/m/Y') : '');

        foreach ($this->downloadAdmins() as $employeeCode) {
            $this->store(
                employeeCode: $employeeCode,
                type: OtNotification::TYPE_DOWNLOAD_READY,
                title: $title,
                body: $body,
                link: route('ot-approval.downloads.index', ['month' => $request->work_date->format('Y-m')]),
                itemCount: 1,
                category: OtNotification::CATEGORY_DOWNLOAD,
            );
        }
    }

    /**
     * รหัสพนักงานของ admin ที่ควรได้รับแจ้งเตือนหมวดดาวน์โหลด
     *
     * @return array<int, string>
     */
    private function downloadAdmins(): array
    {
        $insightAdmins = AppUser::query()
            ->where('role', 'admin')
            ->pluck('employee_code');
        $otAdmins = OtMember::query()
            ->where('role', OtMember::ROLE_ADMIN)
            ->pluck('employee_code');

        return $insightAdmins
            ->merge($otAdmins)
            ->map(fn ($code) => trim((string) $code))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function store(
        string $employeeCode,
        string $type,
        string $title,
        ?string $body,
        ?string $link,
        int $itemCount,
        string $category = OtNotification::CATEGORY_OT,
    ): void {
        OtNotification::create([
            'employee_code' => $employeeCode,
            'type' => $type,
            'category' => $category,
            'title' => mb_substr($title, 0, 190),
            'body' => $body ? mb_substr($body, 0, 500) : null,
            'link' => $link,
            'item_count' => $itemCount,
        ]);
    }

    /** @param  Collection<int, OtRequest>  $group */
    private function mailSupervisor(
        AppUser $supervisor,
        Collection $group,
        string $foremanName,
        string $workDate,
        string $deptName,
    ): void {
        $email = trim((string) $supervisor->email);
        if ($email === '') {
            // ยังไม่ได้ผูกอีเมลในโปรไฟล์ Insight — ข้ามเงียบ ๆ แต่บันทึกไว้ให้ตามได้
            Log::info('ot.notify: Supervisor ยังไม่ได้ผูกอีเมล ข้ามการส่งเมล', [
                'employee_code' => $supervisor->employee_code,
            ]);

            return;
        }

        try {
            // ส่งตรงไม่เข้าคิว เพราะไม่มี queue worker รันค้างไว้บนเซิร์ฟเวอร์
            Mail::to($email)->send(new OtRequestSubmittedMail(
                supervisorName: $this->displayName($supervisor),
                foremanName: $foremanName,
                departmentName: $deptName,
                workDate: $workDate,
                requests: $group->values(),
                actionUrl: route('ot-approval.approvals.index'),
            ));
        } catch (Throwable $exception) {
            // เมลล้มต้องไม่ทำให้การส่งคำขอพัง
            Log::error('ot.notify: ส่งอีเมลแจ้ง Supervisor ไม่สำเร็จ', [
                'employee_code' => $supervisor->employee_code,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    /**
     * ผู้รับแจ้งเตือนฝั่ง OT — ต้องกรอง module ด้วย
     *
     * สิทธิ์ OT กับสิทธิ์การลาแยกกันตั้งแต่มีคอลัมน์ `module` แผนกเดียวกันจึงตั้ง
     * Supervisor คนละคนได้ ถ้าไม่กรองจะได้แถวแรกตาม id ซึ่งอาจเป็นของการลา
     * แล้วอีเมล/กระดิ่ง OT จะวิ่งไปผิดคน (ฝั่งการลากรองอยู่แล้วใน LeaveNotificationService)
     */
    private function assignedUser(string $company, string $deptCode, string $role): ?AppUser
    {
        $assignment = OtDepartmentAssignment::query()
            ->forModule(OtDepartmentAssignment::MODULE_OT)
            ->where('company', $company)
            ->where('dept_code', trim($deptCode))
            ->where('role', $role)
            ->first();

        return $assignment ? AppUser::find($assignment->app_user_id) : null;
    }

    private function displayName(AppUser $user): string
    {
        return $user->fullNameTh() ?: $user->full_name_en ?: (string) $user->employee_code;
    }
}
