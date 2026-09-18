<?php

namespace App\Support\OtApproval;

use App\Models\Insight\Employee;
use Illuminate\Database\Eloquent\Builder;

/**
 * ตัวกรอง "สาขา" ของหน้า Time & Leave — ใช้แยกคนไทยกับคนพม่า
 *
 * Bplus **ไม่มีฟิลด์สัญชาติที่ใช้งานได้เลย**: ตาราง `FOREIGNINFO` มี 838 แถวแต่ว่างทั้งหมด
 * (passport 0 · work permit 0 · `FRN_FOREIGNER` = 'N' ทุกแถว) ส่วน `EMP_ADDR_COUNTRY`
 * กรอกไม่ถึง 10% และไม่มีค่า "พม่า" สักแถว
 *
 * สิ่งที่บริษัทใช้แบ่งจริงคือ **สาขา** ในตาราง `BRANCH` ของ Bplus
 * Supavut Industry แยก `10 = โรงงาน` กับ `11 = โรงงาน-พม่า` ไว้ชัดเจน
 * (โรงงาน-พม่า 384 คน ซึ่งสอดคล้องกับจำนวนคนที่เลขบัตรขึ้นต้น 0/6/7)
 * ค่านี้ถูกซิงค์เข้า `employees.branch_code` โดย `bplus:sync`
 */
class OtBranchFilter
{
    /** ค่าที่แปลว่า "ทุกสาขา" — ใช้ตรงกันทั้ง query string และ dropdown */
    public const ALL = 'all';

    /**
     * ตัวเลือกสาขาสำหรับ dropdown — เฉพาะสาขาที่มีพนักงานอยู่จริง
     *
     * ไม่ล็อกเป็น "ไทย/พม่า" ตายตัว เพราะแต่ละบริษัทแบ่งสาขาไม่เหมือนกัน
     * (Moldvanto มีแค่ สำนักงานใหญ่/โรงงาน) และ HR เพิ่มสาขาใหม่ได้ตลอด
     *
     * @return array<int, array{code:string,label_th:string,label_en:string}>
     */
    public static function options(): array
    {
        /* จัดกลุ่มด้วย "ชื่อสาขา" ไม่ใช่รหัส เพราะรหัสซ้ำความหมายข้ามบริษัท
           (Supavut ใช้ 10 = โรงงาน · Moldvanto ใช้ 02 = โรงงาน) ถ้าใช้รหัสเป็นค่า
           dropdown จะมี "โรงงาน" สองบรรทัดให้ผู้ใช้เดาเองว่าอันไหนของใคร */
        return Employee::query()
            ->whereNotNull('branch_th')
            ->where('branch_th', '<>', '')
            ->selectRaw('branch_th, MAX(branch_en) AS branch_en, COUNT(*) AS people')
            ->groupBy('branch_th')
            ->orderByDesc('people')
            ->get()
            ->map(fn ($row) => [
                'code' => trim((string) $row->branch_th),
                'label_th' => trim((string) $row->branch_th),
                'label_en' => trim((string) ($row->branch_en ?: $row->branch_th)),
            ])
            ->values()
            ->all();
    }

    /**
     * กรองคิวอนุมัติตามสาขา
     *
     * ตารางคำขออยู่คนละฐานกับ `employees` จึง join ตรง ๆ ไม่ได้
     * ต้องดึงรหัสพนักงานของสาขานั้นมาก่อนแล้วค่อย `whereIn`
     * สาขาที่ไม่มีใครเลยต้องคืนผลว่าง ไม่ใช่คืนทุกคน ไม่งั้นตัวกรองจะดูเหมือนไม่ทำงาน
     */
    public static function apply(Builder $query, ?string $branch): void
    {
        $branch = trim((string) $branch);
        if ($branch === '' || $branch === self::ALL) {
            return;
        }

        $codes = Employee::query()
            ->where('branch_th', $branch)
            ->pluck('employee_code')
            ->filter()
            ->unique()
            ->values()
            ->all();

        if ($codes === []) {
            $query->whereRaw('1 = 0');

            return;
        }

        $query->whereIn('employee_code', $codes);
    }
}
