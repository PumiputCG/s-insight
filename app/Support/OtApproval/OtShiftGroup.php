<?php

namespace App\Support\OtApproval;

/**
 * จัดกลุ่มกะเป็น "เช้า" / "ดึก" จากรหัสกะของ Bplus และเก็บป้าย
 * "ทั้งหมด" สำหรับ Foreman ที่ดูแลทุกกะในแผนก
 *
 * ใช้ร่วมกันทั้งหน้าตั้งค่า ฟิลเตอร์ 3 หน้า และการขอ OT ทั้งกะ
 * mapping จริงอยู่ใน config/ot_approval.php เพื่อให้แก้ที่เดียวเมื่อมีกะใหม่
 */
class OtShiftGroup
{
    /** ป้ายผู้รับผิดชอบทุกกะ ใช้กับ Assignment เท่านั้น ไม่ใช่กลุ่มกะของ Bplus */
    public const ALL = 'all';

    public const MORNING = 'morning';

    public const NIGHT = 'night';

    /** ยังไม่รู้ว่ากะนี้อยู่กลุ่มไหน หรือพนักงานไม่มีกะในวันนั้น */
    public const UNKNOWN = 'unknown';

    /** @return array<int, string> */
    public static function keys(): array
    {
        return [self::ALL, self::MORNING, self::NIGHT];
    }

    /** @return array<int, string> */
    private static function attendanceKeys(): array
    {
        return [self::MORNING, self::NIGHT];
    }

    /**
     * หากลุ่มของรหัสกะ — ตัดท้าย O / OX ออกก่อนเทียบ เพราะ Bplus แตกทุกกะ
     * เป็น 3 แบบย่อยตามชนิดของวัน (วันงาน / วันหยุด / นักขัตฤกษ์) โดยเวลาเท่ากัน
     */
    public static function of(?string $shiftCode): string
    {
        $base = self::baseCode($shiftCode);
        if ($base === '') {
            return self::UNKNOWN;
        }

        foreach (self::attendanceKeys() as $group) {
            $codes = (array) config('ot_approval.shift_groups.'.$group.'.codes', []);
            if (in_array($base, $codes, true)) {
                return $group;
            }
        }

        return self::UNKNOWN;
    }

    /** รหัสฐานของกะ เช่น AD03O -> AD03 · AD03OX -> AD03 */
    public static function baseCode(?string $shiftCode): string
    {
        $code = strtoupper(trim((string) $shiftCode));
        if ($code === '') {
            return '';
        }

        foreach (['OX', 'O'] as $suffix) {
            if (str_ends_with($code, $suffix) && strlen($code) > strlen($suffix)) {
                return substr($code, 0, -strlen($suffix));
            }
        }

        return $code;
    }

    public static function label(?string $group, string $lang = 'th'): string
    {
        $group = (string) $group;
        if (! in_array($group, self::keys(), true)) {
            return match ($lang) {
                'en' => 'Unassigned shift',
                'my' => 'ဆိုင်းမသတ်မှတ်ရသေး',
                default => 'ไม่ระบุกะ',
            };
        }

        return (string) config(
            'ot_approval.shift_groups.'.$group.'.label_'.$lang,
            config('ot_approval.shift_groups.'.$group.'.label_th', $group),
        );
    }

    /**
     * ค่าของตัวกรองกะในตารางขอ OT ที่ต้องเลือกอัตโนมัติเมื่อ Foreman เปิดแผนก
     *
     * "ทั้งหมด" และค่าที่ไม่ได้กำหนดต้องกลับไปที่ทุกกะ ส่วนเช้า/ดึกเลือกทั้งกลุ่ม
     * ไม่เจาะจงรหัสกะย่อย เพราะหนึ่งกลุ่มมีได้หลายรหัสจาก Bplus
     */
    public static function filterValue(?string $group): string
    {
        return in_array($group, self::attendanceKeys(), true)
            ? 'g:'.$group
            : self::ALL;
    }

    /**
     * ตัวเลือกสำหรับ dropdown ในหน้าตั้งค่า
     *
     * @return array<int, array{key: string, label_th: string, label_en: string, label_my: string}>
     */
    public static function options(): array
    {
        return array_map(fn (string $group) => [
            'key' => $group,
            'label_th' => self::label($group, 'th'),
            'label_en' => self::label($group, 'en'),
            'label_my' => self::label($group, 'my'),
        ], self::keys());
    }
}
