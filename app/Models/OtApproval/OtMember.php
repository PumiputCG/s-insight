<?php

namespace App\Models\OtApproval;

use Illuminate\Database\Eloquent\Model;

/** ผู้ดูแลเฉพาะระบบ OT Approval — Insight admin มีสิทธิ์นี้โดยอัตโนมัติอยู่แล้ว */
class OtMember extends Model
{
    public const ROLE_ADMIN = 'admin';

    protected $connection = 'mysql_ot_approval';

    protected $table = 'ot_members';

    protected $fillable = [
        'app_user_id', 'employee_code', 'display_name', 'position', 'role', 'assigned_by',
    ];

    public static function isAdmin(int $appUserId): bool
    {
        return static::query()
            ->where('app_user_id', $appUserId)
            ->where('role', self::ROLE_ADMIN)
            ->exists();
    }
}
