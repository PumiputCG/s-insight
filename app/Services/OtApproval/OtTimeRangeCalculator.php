<?php

namespace App\Services\OtApproval;

use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;

class OtTimeRangeCalculator
{
    /**
     * @return array{start: CarbonImmutable, end: CarbonImmutable, total_minutes: int, hours: int, minutes: int}
     */
    public function calculate(CarbonImmutable $workDate, string $startTime, string $endTime): array
    {
        $start = CarbonImmutable::parse($workDate->toDateString().' '.$startTime);
        $end = CarbonImmutable::parse($workDate->toDateString().' '.$endTime);

        if ($start->equalTo($end)) {
            throw ValidationException::withMessages([
                'end_time' => 'เวลาสิ้นสุดต้องไม่เท่ากับเวลาเริ่ม',
            ]);
        }

        if ($end->lt($start)) {
            $end = $end->addDay();
        }

        $totalMinutes = (int) $start->diffInMinutes($end);

        return [
            'start' => $start,
            'end' => $end,
            'total_minutes' => $totalMinutes,
            'hours' => intdiv($totalMinutes, 60),
            'minutes' => $totalMinutes % 60,
        ];
    }
}
