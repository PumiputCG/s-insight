<?php

namespace App\Models\Assessment;

use Illuminate\Database\Eloquent\Model;

/**
 * สมาชิกระบบ Assessment (เก็บใน insight_assessment)
 *
 * - admin มอบ role ให้พนักงาน (เลือกจาก insight.app_users)
 * - มี record = เข้าระบบ Assessment ได้ (นอกจาก admin)
 * - role ตอนนี้ใช้ 'hr' (เผื่อขยาย supervisor/division ภายหลัง)
 */
class AsmMember extends Model
{
    protected $connection = 'mysql_assessment';

    protected $table = 'asm_members';

    protected $fillable = [
        'app_user_id', 'employee_code', 'display_name', 'position', 'role', 'assigned_by',
    ];

    /** เป็นสมาชิก Assessment หรือไม่ */
    public static function isMember(int $appUserId): bool
    {
        return static::query()->where('app_user_id', $appUserId)->exists();
    }

    /** มี role HR หรือไม่ */
    public static function isHr(int $appUserId): bool
    {
        return static::query()->where('app_user_id', $appUserId)->where('role', 'hr')->exists();
    }
}
