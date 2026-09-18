<?php

namespace App\Services\OtApproval;

use App\Mail\OtApproval\LeaveRequestSubmittedMail;
use App\Models\Insight\AppUser;
use App\Models\OtApproval\LeaveRequest;
use App\Models\OtApproval\OtDepartmentAssignment;
use App\Models\OtApproval\OtNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class LeaveNotificationService
{
  /** @param array<int, int|string> $requestIds */
  public function notifySubmitted(array $requestIds, AppUser $foreman): void
  {
    $requests = LeaveRequest::query()->whereIn('id', $requestIds)->get();
    if ($requests->isEmpty()) {
      return;
    }

    $requests
      ->groupBy(fn (LeaveRequest $request) => $request->company.'|'.trim((string) $request->dept_code))
      ->each(function (Collection $group) use ($foreman) {
        /** @var LeaveRequest $sample */
        $sample = $group->first();
        // ผู้อนุมัติของการลากำหนดแยกจาก OT จึงต้องกรอง module ด้วย
        $assignment = OtDepartmentAssignment::query()
          ->forModule(OtDepartmentAssignment::MODULE_LEAVE)
          ->where('company', $sample->company)
          ->where('dept_code', trim((string) $sample->dept_code))
          ->where('role', OtDepartmentAssignment::ROLE_SUPERVISOR)
          ->first();
        $supervisor = $assignment ? AppUser::find($assignment->app_user_id) : null;
        if (! $supervisor) {
          Log::info('leave.notify: ยังไม่ได้กำหนด Supervisor ของแผนก', [
            'company' => $sample->company,
            'dept_code' => $sample->dept_code,
          ]);
          return;
        }

        $deptName = $sample->department_name ?: $sample->dept_code;
        $this->store(
          (string) $supervisor->employee_code,
          OtNotification::TYPE_SUBMITTED,
          'มีคำขอลา 75% รออนุมัติ '.$group->count().' รายการ',
          $deptName.' · ส่งโดย '.$this->displayName($foreman),
          route('ot-approval.leave-approvals.index'),
          $group->count(),
        );
        $this->mailSupervisor($supervisor, $group, $foreman, $deptName);
      });
  }

  public function notifyDecided(LeaveRequest $request, AppUser $supervisor): void
  {
    $foremanCode = trim((string) $request->created_by_employee_code);
    if ($foremanCode === '') {
      return;
    }

    $approved = $request->approval_status === LeaveRequest::APPROVAL_APPROVED;
    $this->store(
      $foremanCode,
      OtNotification::TYPE_DECIDED,
      $approved ? 'คำขอลา 75% ได้รับอนุมัติแล้ว' : 'คำขอลา 75% ไม่ได้รับอนุมัติ',
      ($request->employee_name ?: $request->employee_code)
        .' · วันที่ '.$request->leave_date->format('d/m/Y')
        .' · โดย '.$this->displayName($supervisor)
        .($request->decision_note ? ' · '.$request->decision_note : ''),
      route('ot-approval.leave-requests.index', ['date' => $request->leave_date->format('Y-m-d')]),
      1,
    );
  }

  private function store(string $employeeCode, string $type, string $title, ?string $body, string $link, int $count): void
  {
    OtNotification::create([
      'employee_code' => $employeeCode,
      'type' => $type,
      'category' => OtNotification::CATEGORY_LEAVE,
      'title' => mb_substr($title, 0, 190),
      'body' => $body ? mb_substr($body, 0, 500) : null,
      'link' => $link,
      'item_count' => $count,
    ]);
  }

  /** @param Collection<int, LeaveRequest> $requests */
  private function mailSupervisor(AppUser $supervisor, Collection $requests, AppUser $foreman, string $deptName): void
  {
    $email = trim((string) $supervisor->email);
    if ($email === '') {
      Log::info('leave.notify: Supervisor ยังไม่ได้ผูกอีเมล', ['employee_code' => $supervisor->employee_code]);
      return;
    }

    try {
      Mail::to($email)->send(new LeaveRequestSubmittedMail(
        supervisorName: $this->displayName($supervisor),
        foremanName: $this->displayName($foreman),
        departmentName: $deptName,
        requests: $requests->values(),
        actionUrl: route('ot-approval.leave-approvals.index'),
      ));
    } catch (Throwable $exception) {
      Log::error('leave.notify: ส่งอีเมลแจ้ง Supervisor ไม่สำเร็จ', [
        'employee_code' => $supervisor->employee_code,
        'error' => $exception->getMessage(),
      ]);
    }
  }

  private function displayName(AppUser $user): string
  {
    return $user->fullNameTh() ?: $user->full_name_en ?: (string) $user->employee_code;
  }
}
