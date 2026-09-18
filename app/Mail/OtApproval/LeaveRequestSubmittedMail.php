<?php

namespace App\Mail\OtApproval;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

/** แจ้ง Supervisor เมื่อ Foreman ส่งคำขอลา 75 — ส่งตรงเหมือน OT เพราะเซิร์ฟเวอร์ไม่มี queue worker */
class LeaveRequestSubmittedMail extends Mailable
{
  use Queueable, SerializesModels;

  /** @param Collection<int, \App\Models\OtApproval\LeaveRequest> $requests */
  public function __construct(
    public string $supervisorName,
    public string $foremanName,
    public string $departmentName,
    public Collection $requests,
    public string $actionUrl,
  ) {}

  public function envelope(): Envelope
  {
    return new Envelope(
      subject: '[Time & Leave Approval] มีคำขอลา 75% รออนุมัติ '.$this->requests->count().' รายการ · '.$this->departmentName,
    );
  }

  public function content(): Content
  {
    return new Content(view: 'emails.ot_approval.leave-request-submitted');
  }
}
