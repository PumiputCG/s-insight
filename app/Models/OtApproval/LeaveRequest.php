<?php

namespace App\Models\OtApproval;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LeaveRequest extends Model
{
  public const APPROVAL_DRAFT = 'draft';
  public const APPROVAL_SUBMITTED = 'submitted';
  public const APPROVAL_APPROVED = 'approved';
  public const APPROVAL_REJECTED = 'rejected';

  /** ยกเลิกโดย Foreman หลังส่งแล้วแต่ยังไม่มีการตัดสิน — เก็บแถวไว้เป็นประวัติ */
  public const APPROVAL_CANCELLED = 'cancelled';
  public const EXPORT_NOT_READY = 'not_ready';
  public const EXPORT_READY = 'ready';
  /** ส่งไฟล์ให้ HR ไปแล้ว — ปั๊มตอน admin กดดาวน์โหลด ไม่ใช่ตอนอนุมัติ */
  public const EXPORT_EXPORTED = 'exported';

  protected $connection = 'mysql_ot_approval';
  protected $table = 'leave_requests';
  protected $guarded = ['id'];

  protected $casts = [
    'leave_date' => 'immutable_date',
    'range_start' => 'immutable_date',
    'range_end' => 'immutable_date',
    'leave_quantity' => 'decimal:2',
    'submitted_at' => 'immutable_datetime',
    'decided_at' => 'immutable_datetime',
    'cancelled_at' => 'immutable_datetime',
    'exported_at' => 'immutable_datetime',
  ];

  /**
   * คำขอที่ยังมีผลอยู่ — ตัดที่ยกเลิกแล้วออก
   *
   * ใช้กับทุกจุดที่นับยอด/ผูกคำขอกับพนักงาน เพราะแถวที่ยกเลิกยังอยู่ในฐานเป็นประวัติ
   * ถ้าไม่กรองจะไปโผล่ในยอด `ขอลา` และบังไม่ให้ขอใหม่ได้
   */
  public function scopeNotCancelled($query)
  {
    return $query->where("approval_status", "!=", self::APPROVAL_CANCELLED);
  }

  public function approvals(): HasMany
  {
    return $this->hasMany(LeaveRequestApproval::class, 'leave_request_id');
  }

  public function leaveTypeLabel(string $lang = 'th'): string
  {
    $type = (array) config('leave_approval.types.'.trim((string) $this->leave_type), []);
    $fallback = $lang === 'my' ? ($type['label_en'] ?? $this->leave_type) : $this->leave_type;

    return (string) ($type['label_'.$lang] ?? $fallback ?: '-');
  }
}
