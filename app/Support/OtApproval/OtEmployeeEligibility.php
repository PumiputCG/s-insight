<?php

namespace App\Support\OtApproval;

use App\Models\Insight\Setting;

final class OtEmployeeEligibility
{
    public const REASON_POSITION_NOT_ELIGIBLE = 'position_not_ot_eligible';

    /** เหตุผลฝั่งการลา 75 — ตั้งค่าแยกจาก OT ได้ (หัวข้อ 8 ในหน้าตั้งค่า) */
    public const REASON_POSITION_NOT_LEAVE_ELIGIBLE = 'position_not_leave_eligible';

    /** จำรหัสตำแหน่งที่ถูกซ่อนไว้ต่อรีเควสต์ เพราะถูกเรียกเช็คทีละคนเป็นร้อยแถว */
    private static ?array $hiddenCache = null;

    /** ชุดเดียวกันแต่ของฝั่งการลา — แยก cache กันคนละตัว ไม่งั้นค่าจะปนกัน */
    private static ?array $hiddenLeaveCache = null;

    /**
     * @param  array<string, mixed>  $employee
     * @return array{eligible: bool, reason: ?string}
     */
    public static function evaluate(array $employee): array
    {
        $jobCode = strtoupper(trim((string) ($employee['job_code'] ?? '')));

        /* ตัดสินจาก "ตำแหน่งที่ไม่ให้ขอ OT" ที่ admin ติ๊กเองในหน้าตั้งค่า (หัวข้อ 7) เท่านั้น
           เดิมบล็อกด้วยรหัส Y1 ใน config และชื่อตำแหน่งเป็น safety net ซึ่งแก้ได้เฉพาะคนเขียนโค้ด
           ตอนนี้ย้ายมาให้ admin คุมเอง: ไม่ติ๊ก = ขอ OT ได้ตามปกติ รวมถึงตำแหน่งใหม่จาก Bplus
           ผลข้างเคียงที่ต้องรู้: ถ้ายังไม่ติ๊ก Employee with Disabilities คนกลุ่มนี้จะขอ OT ได้ */
        if ($jobCode !== '' && in_array($jobCode, self::hiddenJobCodes(), true)) {
            return ['eligible' => false, 'reason' => self::REASON_POSITION_NOT_ELIGIBLE];
        }

        return ['eligible' => true, 'reason' => null];
    }

    /** @param array<string, mixed> $employee */
    public static function canRequest(array $employee): bool
    {
        return self::evaluate($employee)['eligible'];
    }

    /**
     * ตำแหน่งนี้ขอลา 75 ได้ไหม — คนละชุดกับ OT (admin ติ๊กแยกกันที่หัวข้อ 8)
     *
     * @param  array<string, mixed>  $employee
     * @return array{eligible: bool, reason: ?string}
     */
    public static function evaluateLeave(array $employee): array
    {
        $jobCode = strtoupper(trim((string) ($employee['job_code'] ?? '')));

        if ($jobCode !== '' && in_array($jobCode, self::hiddenLeaveJobCodes(), true)) {
            return ['eligible' => false, 'reason' => self::REASON_POSITION_NOT_LEAVE_ELIGIBLE];
        }

        return ['eligible' => true, 'reason' => null];
    }

    /** @param array<string, mixed> $employee */
    public static function canRequestLeave(array $employee): bool
    {
        return self::evaluateLeave($employee)['eligible'];
    }

    /**
     * รหัสตำแหน่งที่ admin สั่งซ่อนปุ่ม `ขอ OT`
     *
     * จำไว้ต่อรีเควสต์ เพราะ decorateEmployeePayload เรียกเช็คทีละคนเป็นร้อยแถว
     *
     * @return array<int, string>
     */
    private static function hiddenJobCodes(): array
    {
        $cache = self::$hiddenCache;

        if ($cache === null) {
            /* อ่านค่าไม่ได้ (เช่น ตารางตั้งค่ายังไม่ถูกสร้าง) ให้ถือว่าไม่มีตำแหน่งถูกซ่อน
               ดีกว่าปล่อยให้ทั้งหน้าล่มเพราะอ่าน setting ตัวเดียวไม่ผ่าน */
            $cache = rescue(
                static fn (): array => collect((array) Setting::get(Setting::OT_REQUEST_HIDDEN_POSITIONS, []))
                    ->map(static fn (mixed $code): string => strtoupper(trim((string) $code)))
                    ->filter()
                    ->values()
                    ->all(),
                [],
                false,
            );
            self::$hiddenCache = $cache;
        }

        return $cache;
    }

    /**
     * รหัสตำแหน่งที่ admin สั่งซ่อนปุ่ม `ขอลา` — ตรรกะเดียวกับ hiddenJobCodes() แต่คนละคีย์
     *
     * @return array<int, string>
     */
    private static function hiddenLeaveJobCodes(): array
    {
        $cache = self::$hiddenLeaveCache;

        if ($cache === null) {
            $cache = rescue(
                static fn (): array => collect((array) Setting::get(Setting::LEAVE_REQUEST_HIDDEN_POSITIONS, []))
                    ->map(static fn (mixed $code): string => strtoupper(trim((string) $code)))
                    ->filter()
                    ->values()
                    ->all(),
                [],
                false,
            );
            self::$hiddenLeaveCache = $cache;
        }

        return $cache;
    }

    /** ล้างค่าที่จำไว้ — ใช้หลัง admin บันทึกค่าใหม่ และในเทสต์ */
    public static function forgetHiddenJobCodes(): void
    {
        self::$hiddenCache = null;
    }

    /** ล้างค่าที่จำไว้ของฝั่งการลา — ใช้หลัง admin บันทึกค่าใหม่ และในเทสต์ */
    public static function forgetHiddenLeaveJobCodes(): void
    {
        self::$hiddenLeaveCache = null;
    }

    private static function normalizeName(mixed $value): string
    {
        return mb_strtolower(trim((string) preg_replace('/\s+/u', ' ', (string) $value)));
    }
}
