<?php

namespace Tests\Unit\Insight;

use App\Services\Insight\BplusLeaveRightService;
use PHPUnit\Framework\TestCase;

class BplusLeaveRightServiceTest extends TestCase
{
    public function test_it_combines_all_bplus_usage_stages_without_losing_source_breakdown(): void
    {
        $rows = (new BplusLeaveRightService)->normalizeRows([
            (object) [
                'SYSLKUP_KEY' => '7',
                'SYSLKUP_T_DESC' => 'ลาป่วย',
                'SYSLKUP_E_DESC' => 'Sick',
                'DAYS_PER_YEAR' => '30.0000',
                'B4_TIMES' => '1.0000',
                'B4_DAYS' => '1.0000',
                'PRR_TIMES' => '1.0000',
                'PRR_DAYS' => '2.0000',
                'PRT_TIMES' => '1.0000',
                'PRT_DAYS' => '.5000',
                'TMR_TIMES' => '0.0000',
                'TMR_DAYS' => '0.0000',
                'APT_TIMES' => '1.0000',
                'APT_DAYS' => '.5000',
            ],
        ]);

        $this->assertSame('ลาป่วย', $rows[0]['name_th']);
        $this->assertSame(30.0, $rows[0]['entitled']);
        $this->assertSame(4.0, $rows[0]['used']);
        $this->assertSame(26.0, $rows[0]['remaining']);
        $this->assertSame(4.0, $rows[0]['used_times']);
        $this->assertSame(0.5, $rows[0]['usage']['apt']['units']);
    }

    public function test_it_preserves_an_overused_negative_balance_for_hr_visibility(): void
    {
        $rows = (new BplusLeaveRightService)->normalizeRows([
            (object) [
                'SYSLKUP_KEY' => '14',
                'SYSLKUP_T_DESC' => 'ลากิจ',
                'DAYS_PER_YEAR' => '24',
                'B4_DAYS' => '25',
            ],
        ]);

        $this->assertSame(-1.0, $rows[0]['remaining']);
    }
}
