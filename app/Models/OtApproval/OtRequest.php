<?php

namespace App\Models\OtApproval;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OtRequest extends Model
{
    public const APPROVAL_DRAFT = 'draft';

    public const APPROVAL_SUBMITTED = 'submitted';

    public const APPROVAL_APPROVED = 'approved';

    public const APPROVAL_REJECTED = 'rejected';

    /** ยกเลิกโดย Foreman หลังส่งแล้วแต่ยังไม่มีการตัดสิน — เก็บแถวไว้เป็นประวัติ */
    public const APPROVAL_CANCELLED = 'cancelled';

    public const ATTENDANCE_WAITING = 'waiting_scan';

    public const ATTENDANCE_PASSED = 'passed';

    public const ATTENDANCE_FAILED = 'failed';

    public const EXPORT_NOT_READY = 'not_ready';

    public const EXPORT_READY = 'ready';

    public const EXPORT_EXPORTED = 'exported';

    protected $connection = 'mysql_ot_approval';

    protected $table = 'ot_requests';

    protected $guarded = ['id'];

    protected $casts = [
        'work_date' => 'immutable_date',
        'requested_start_at' => 'immutable_datetime',
        'requested_end_at' => 'immutable_datetime',
        'requested_hours' => 'integer',
        'requested_minutes' => 'integer',
        'multiplier' => 'decimal:2',
        'is_special' => 'boolean',
        'attendance_checked_at' => 'immutable_datetime',
        'submitted_at' => 'immutable_datetime',
        'decided_at' => 'immutable_datetime',
        'cancelled_at' => 'immutable_datetime',
        'exported_at' => 'immutable_datetime',
    ];

    /**
     * คำขอที่ยังมีผลอยู่ — ตัดที่ยกเลิกแล้วออก
     *
     * ใช้กับทุกจุดที่นับยอด/ผูกคำขอกับพนักงาน/ทำคิวอนุมัติ เพราะแถวที่ยกเลิกยังอยู่ในฐาน
     * (เก็บไว้เป็นประวัติ) ถ้าไม่กรองจะไปโผล่ในยอด `ขอ OT` และบังไม่ให้ขอใหม่ได้
     */
    public function scopeNotCancelled($query)
    {
        return $query->where("approval_status", "!=", self::APPROVAL_CANCELLED);
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(OtRequestApproval::class, 'ot_request_id');
    }

    /**
     * ชื่อประเภท OT ที่คนอ่านรู้เรื่อง เช่น "ค่าล่วงเวลาX1.5 (ก่อนเข้างาน)"
     *
     * รวมไว้ที่เดียวเพื่อไม่ให้หน้าไหนเผลอโชว์รหัสดิบอย่าง weekday_before_work อีก
     * ประเภทที่ไม่รู้จักจะคืนรหัสดิบไว้ให้ตามต่อได้ ดีกว่าโชว์ค่าว่าง
     */
    public function otTypeLabel(string $lang = 'th'): string
    {
        $code = trim((string) $this->ot_type);
        if ($code === '') {
            return '-';
        }

        $type = (array) config('ot_approval.types.'.$code, []);
        // ภาษาพม่ายังแปลไม่ครบทุกประเภท ถ้าไม่มีให้ตกไปอังกฤษก่อนค่อยเป็นรหัส
        $fallback = $lang === 'my' ? ($type['label_en'] ?? $code) : $code;

        return $type['label_'.$lang] ?? $fallback;
    }
}
