<?php

namespace App\Mail\OtApproval;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

/**
 * แจ้ง Supervisor ว่ามีคำขอ OT รออนุมัติ
 * 1 ฉบับต่อการกดส่ง 1 ครั้งต่อแผนก รวมพนักงานทั้งล็อตไว้ในตารางเดียว
 *
 * ตั้งใจ "ไม่" ใส่ ShouldQueue เพราะเซิร์ฟเวอร์ไม่มี queue worker รันค้างไว้
 * ถ้าเข้าคิวเมลจะค้างในตาราง jobs ตลอดไปโดยไม่มีใครส่ง
 * ปริมาณเมลน้อยมาก (ไม่กี่ฉบับต่อวัน) ส่งตรงจึงเหมาะกว่า และตัวเรียกจับ
 * exception ไว้แล้ว เมลล้มจึงไม่ทำให้การส่งคำขอพัง
 */
class OtRequestSubmittedMail extends Mailable
{
    use Queueable, SerializesModels;

    /** @param  Collection<int, \App\Models\OtApproval\OtRequest>  $requests */
    public function __construct(
        public string $supervisorName,
        public string $foremanName,
        public string $departmentName,
        public string $workDate,
        public Collection $requests,
        public string $actionUrl,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '[Time & Leave Approval] มีคำขอ OT รออนุมัติ '.$this->requests->count()
                .' รายการ · '.$this->departmentName,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.ot_approval.request-submitted',
        );
    }
}
