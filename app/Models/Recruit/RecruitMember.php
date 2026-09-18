<?php

namespace App\Models\Recruit;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * สมาชิกในขั้นเส้นทาง Recruit (ผูกกับ step) — admin เพิ่มพนักงานเข้าการ์ด
 *
 * - มี record = เข้าระบบ Recruit ได้
 * - dept_codes : สำหรับ DCC — แผนกที่ user คนนี้คุม/มองเห็นได้ (หลายแผนก)
 */
class RecruitMember extends Model
{
    protected $connection = 'mysql_recruit';

    protected $table = 'recruit_members';

    protected $fillable = [
        'step_id', 'app_user_id', 'employee_code', 'display_name', 'position', 'department', 'dept_codes', 'assigned_by',
    ];

    protected $casts = ['dept_codes' => 'array'];

    public function step(): BelongsTo
    {
        return $this->belongsTo(RecruitStep::class, 'step_id');
    }

    /** เป็นสมาชิก Recruit หรือไม่ (อยู่ในขั้นใดขั้นหนึ่ง) */
    public static function isMember(int $appUserId): bool
    {
        return static::query()->where('app_user_id', $appUserId)->exists();
    }

    /** role ทั้งหมดของ user (จาก step ที่สังกัด) */
    public static function rolesFor(int $appUserId): array
    {
        return static::query()
            ->where('recruit_members.app_user_id', $appUserId)
            ->join('recruit_steps', 'recruit_steps.id', '=', 'recruit_members.step_id')
            ->whereNotNull('recruit_steps.role')
            ->pluck('recruit_steps.role')
            ->unique()->values()->all();
    }

    /** role หลักของ user (ตาม policy 1 คน 1 role — เอาตัวแรก) */
    public static function primaryRoleFor(int $appUserId): ?string
    {
        return static::rolesFor($appUserId)[0] ?? null;
    }

    /** มี role นี้หรือไม่ */
    public static function hasRole(int $appUserId, string $role): bool
    {
        return in_array($role, static::rolesFor($appUserId), true);
    }

    /** มี role อย่างน้อยหนึ่งตัวในชุดนี้หรือไม่ */
    public static function hasAnyRole(int $appUserId, array $roles): bool
    {
        return count(array_intersect($roles, static::rolesFor($appUserId))) > 0;
    }

    /** แผนกที่ DCC คนนี้คุม (รวมจากทุกการ์ด DCC ที่อยู่) */
    public static function deptCodesFor(int $appUserId): array
    {
        $rows = static::query()
            ->where('recruit_members.app_user_id', $appUserId)
            ->join('recruit_steps', 'recruit_steps.id', '=', 'recruit_members.step_id')
            ->where('recruit_steps.role', 'dcc')
            ->pluck('recruit_members.dept_codes');

        $codes = [];
        foreach ($rows as $json) {
            foreach ((array) (is_array($json) ? $json : json_decode((string) $json, true)) as $c) {
                $codes[$c] = true;
            }
        }

        return array_keys($codes);
    }
}
