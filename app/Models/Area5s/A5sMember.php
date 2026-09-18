<?php

namespace App\Models\Area5s;

use Illuminate\Database\Eloquent\Model;

/**
 * สมาชิกระบบ SUPAVUT 5S AREA (insight_area5s.a5s_members)
 *
 * role: admin (แอดมินเฉพาะระบบ 5S — admin ของ Insight เป็นแอดมินเสมออยู่แล้ว)
 *       allocator (ผู้จัดสรรพื้นที่) · evaluator (ผู้ตรวจประเมิน)
 * 1 คนมีหลาย role ได้ (หลายแถว unique ที่ app_user_id+role)
 */
class A5sMember extends Model
{
    public const ROLE_ADMIN = 'admin';

    public const ROLE_ALLOCATOR = 'allocator';

    public const ROLE_EVALUATOR = 'evaluator';

    public const ROLES = [self::ROLE_ADMIN, self::ROLE_ALLOCATOR, self::ROLE_EVALUATOR];

    protected $connection = 'mysql_area5s';

    protected $table = 'a5s_members';

    protected $fillable = [
        'app_user_id', 'employee_code', 'display_name', 'position', 'role', 'assigned_by',
    ];

    public static function hasRole(int $appUserId, string $role): bool
    {
        return static::query()->where('app_user_id', $appUserId)->where('role', $role)->exists();
    }

    public static function isMember(int $appUserId): bool
    {
        return static::query()->where('app_user_id', $appUserId)->exists();
    }
}
