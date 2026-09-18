<?php

namespace Tests\Unit\OtApproval;

use App\Services\OtApproval\OtTimeRangeCalculator;
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class OtTimeRangeCalculatorTest extends TestCase
{
    #[DataProvider('timeRanges')]
    public function test_it_calculates_daily_hours_and_minutes(
        string $start,
        string $end,
        int $expectedHours,
        int $expectedMinutes,
        string $expectedEndDateTime,
    ): void {
        $period = (new OtTimeRangeCalculator)->calculate(
            CarbonImmutable::parse('2026-08-09'),
            $start,
            $end,
        );

        $this->assertSame($expectedHours, $period['hours']);
        $this->assertSame($expectedMinutes, $period['minutes']);
        $this->assertSame($expectedHours * 60 + $expectedMinutes, $period['total_minutes']);
        $this->assertSame($expectedEndDateTime, $period['end']->format('Y-m-d H:i'));
    }

    /** @return array<string, array{string, string, int, int, string}> */
    public static function timeRanges(): array
    {
        return [
            'standard 18:00 to 20:00' => ['18:00', '20:00', 2, 0, '2026-08-09 20:00'],
            'minute precision' => ['18:30', '20:00', 1, 30, '2026-08-09 20:00'],
            'cross midnight' => ['22:30', '01:15', 2, 45, '2026-08-10 01:15'],
        ];
    }

    public function test_equal_start_and_end_is_rejected(): void
    {
        $this->expectException(ValidationException::class);

        (new OtTimeRangeCalculator)->calculate(
            CarbonImmutable::parse('2026-08-09'),
            '18:00',
            '18:00',
        );
    }
}
