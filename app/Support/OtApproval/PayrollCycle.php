<?php

namespace App\Support\OtApproval;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/** รอบส่งข้อมูล Bplus ใช้ร่วมกันทั้ง OT และการลา โดยอ้างวันที่ทำรายการ ไม่ใช่วันที่อนุมัติ */
final class PayrollCycle
{
    /** @return array{key:string,label:string,start:CarbonImmutable,end:CarbonImmutable,approval_deadline:CarbonImmutable} */
    public static function containing(CarbonInterface|string $date): array
    {
        $date = CarbonImmutable::parse($date)->startOfDay();
        $end = $date->day <= 20
          ? $date->day(20)
          : $date->addMonthNoOverflow()->day(20);
        $start = $end->subMonthNoOverflow()->day(21);
        $deadline = $end->day(23)->endOfDay();

        return [
            'key' => $end->format('Y-m'),
            'label' => $start->format('d/m/Y').'–'.$end->format('d/m/Y'),
            'start' => $start,
            'end' => $end,
            'approval_deadline' => $deadline,
        ];
    }

    /** เดือนที่ส่งเข้ามาหมายถึงเดือนของวันปิดรอบ เช่น 2026-08 = 21/07–20/08 */
    public static function endingIn(string $month): array
    {
        $end = CarbonImmutable::createFromFormat('!Y-m', $month)->day(20);

        return self::containing($end);
    }

    public static function canApprove(CarbonInterface|string $workDate, ?CarbonInterface $now = null): bool
    {
        $now = $now ? CarbonImmutable::instance($now) : CarbonImmutable::now();

        return $now->lessThanOrEqualTo(self::containing($workDate)['approval_deadline']);
    }
}
