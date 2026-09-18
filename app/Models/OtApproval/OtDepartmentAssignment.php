<?php

namespace App\Models\OtApproval;

use App\Support\OtApproval\OtShiftGroup;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * ผู้รับผิดชอบ OT รายบริษัท/แผนก
 *
 * Foreman กำหนดได้หลายคนต่อแผนก และติดป้ายกะ (ทั้งหมด/เช้า/ดึก) ให้แต่ละคนได้
 * ส่วน Supervisor ยังเป็น 1 คนต่อแผนกตามที่ Manager กำหนด — บังคับที่
 * OtApprovalSettingController เพราะ MySQL ทำ partial unique index ไม่ได้
 *
 * ป้ายกะเป็นข้อมูลแสดงผลอย่างเดียว ไม่ใช่สิทธิ์ — Foreman กะเวลา Aยังกดขอ OT
 * ให้กะเวลา Bได้ เพื่อกันเคสที่ Foreman กะเวลา Bลาป่วย
 */
class OtDepartmentAssignment extends Model
{
    public const ROLE_FOREMAN = 'foreman';

    public const ROLE_SUPERVISOR = 'supervisor';

    public const ROLES = [self::ROLE_FOREMAN, self::ROLE_SUPERVISOR];

    /** ระบบที่สิทธิ์แถวนี้ใช้ได้ — OT กับการลากำหนดคนแยกกัน */
    public const MODULE_OT = 'ot';

    public const MODULE_LEAVE = 'leave';

    public const MODULES = [self::MODULE_OT, self::MODULE_LEAVE];

    /** ชื่อบริษัทสำหรับแสดงผล — แหล่งเดียวที่ใช้ร่วมกันทั้งโมดูล */
    public const COMPANIES = [
        'SUPAVUT_INDUSTRY' => 'Supavut Industry',
        'MOLDVANTO' => 'Moldvanto',
        'SUPAVUT_INNOMED' => 'Supavut Innomed',
    ];

    protected $connection = 'mysql_ot_approval';

    protected $table = 'ot_department_assignments';

    protected $fillable = [
        'module', 'company', 'dept_code', 'role', 'shift_group', 'app_user_id', 'employee_code', 'assigned_by',
    ];

    /**
     * จำกัดเฉพาะสิทธิ์ของระบบใดระบบหนึ่ง
     *
     * แถวเก่าที่ยังไม่มีค่า module ถือเป็นของ OT เพื่อให้สิทธิ์เดิมไม่หายระหว่างอัปเกรด
     */
    public function scopeForModule(Builder $query, string $module): Builder
    {
        return $module === self::MODULE_OT
            ? $query->where(fn (Builder $where) => $where->where('module', self::MODULE_OT)->orWhereNull('module'))
            : $query->where('module', $module);
    }

    public static function hasAnyRole(int $appUserId, ?string $module = null): bool
    {
        return static::query()
            ->where('app_user_id', $appUserId)
            ->when($module !== null, fn (Builder $query) => $query->forModule($module))
            ->exists();
    }

    /** ป้ายกะมีความหมายเฉพาะ Foreman — Supervisor ดูแลทั้งแผนกอยู่แล้ว */
    public function usesShiftGroup(): bool
    {
        return $this->role === self::ROLE_FOREMAN;
    }

    public function shiftGroupLabel(string $lang = 'th'): string
    {
        return OtShiftGroup::label($this->shift_group, $lang);
    }
}
