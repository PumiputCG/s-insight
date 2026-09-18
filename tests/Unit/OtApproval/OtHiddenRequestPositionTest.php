<?php

namespace Tests\Unit\OtApproval;

use App\Models\Insight\Setting;
use App\Support\OtApproval\OtEmployeeEligibility;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * ตำแหน่งที่ admin ติ๊กซ่อนปุ่ม `ขอ OT` ในหน้าตั้งค่า (หัวข้อ 7)
 *
 * เป็น blacklist ตรงข้ามกับ "ตำแหน่งที่เข้าใช้ระบบได้" — ไม่ติ๊ก = ขอ OT ได้ตามปกติ
 * เพื่อไม่ให้ตำแหน่งใหม่ที่ Bplus เพิ่มมาถูกบล็อกโดยไม่ตั้งใจ
 */
class OtHiddenRequestPositionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('insight_settings', function ($table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        // ค่าถูกจำไว้ต่อรีเควสต์ ต้องล้างก่อนทุกเคสไม่งั้นเคสหลังอ่านค่าเก่า
        $this->resetEligibilityCache();
    }

    public function test_position_is_blocked_when_admin_ticks_it(): void
    {
        Setting::put(Setting::OT_REQUEST_HIDDEN_POSITIONS, ['Y1']);
        $this->resetEligibilityCache();

        $result = OtEmployeeEligibility::evaluate([
            'company' => 'SUPAVUT_INDUSTRY',
            'job_code' => 'Y1',
            'job_en' => 'Some position',
        ]);

        $this->assertFalse($result['eligible']);
        $this->assertSame(OtEmployeeEligibility::REASON_POSITION_NOT_ELIGIBLE, $result['reason']);
    }

    public function test_position_not_ticked_can_still_request(): void
    {
        Setting::put(Setting::OT_REQUEST_HIDDEN_POSITIONS, ['Y1']);
        $this->resetEligibilityCache();

        $result = OtEmployeeEligibility::evaluate([
            'company' => 'SUPAVUT_INDUSTRY',
            'job_code' => 'W1',
            'job_en' => 'Operator',
        ]);

        $this->assertTrue($result['eligible'], 'ตำแหน่งที่ไม่ได้ติ๊กต้องขอ OT ได้ตามปกติ');
    }

    public function test_new_position_is_allowed_when_nothing_was_ticked(): void
    {
        $result = OtEmployeeEligibility::evaluate([
            'company' => 'SUPAVUT_INDUSTRY',
            'job_code' => 'NEWCODE',
            'job_en' => 'Brand new position',
        ]);

        $this->assertTrue($result['eligible'], 'ยังไม่ตั้งค่า = ทุกตำแหน่งขอ OT ได้');
    }

    /** ล้างค่าที่จำไว้ เพื่อให้แต่ละเคสอ่านค่าที่เพิ่งตั้ง */
    private function resetEligibilityCache(): void
    {
        OtEmployeeEligibility::forgetHiddenJobCodes();
    }
}
