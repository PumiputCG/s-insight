<?php

namespace Tests\Unit\OtApproval;

use App\Models\Insight\Setting;
use App\Support\OtApproval\OtEmployeeEligibility;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * ตำแหน่งที่ admin ติ๊กซ่อนปุ่ม `ขอลา` ในหน้าตั้งค่า (หัวข้อ 8)
 *
 * คู่แฝดของหัวข้อ 7 (ฝั่ง OT) แต่ต้อง **แยกกันจริง**: บางตำแหน่งขอ OT ไม่ได้แต่ยังลาได้
 * เทสต์ชุดนี้จึงเน้นพิสูจน์ว่าสองคีย์ไม่ปนกัน
 */
class LeaveHiddenRequestPositionTest extends TestCase
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

        $this->resetCaches();
    }

    public function test_position_cannot_request_leave_when_admin_ticks_it(): void
    {
        Setting::put(Setting::LEAVE_REQUEST_HIDDEN_POSITIONS, ['Y1']);
        $this->resetCaches();

        $result = OtEmployeeEligibility::evaluateLeave(['job_code' => 'Y1']);

        $this->assertFalse($result['eligible']);
        $this->assertSame(OtEmployeeEligibility::REASON_POSITION_NOT_LEAVE_ELIGIBLE, $result['reason']);
    }

    public function test_position_not_ticked_can_still_request_leave(): void
    {
        Setting::put(Setting::LEAVE_REQUEST_HIDDEN_POSITIONS, ['Y1']);
        $this->resetCaches();

        $this->assertTrue(OtEmployeeEligibility::canRequestLeave(['job_code' => 'W1']));
    }

    public function test_new_position_can_request_leave_when_nothing_was_ticked(): void
    {
        $this->assertTrue(
            OtEmployeeEligibility::canRequestLeave(['job_code' => 'NEWCODE']),
            'ยังไม่ตั้งค่า = ทุกตำแหน่งขอลาได้',
        );
    }

    /** หัวใจของหัวข้อ 8: ห้ามขอ OT ไม่ได้แปลว่าห้ามลาไปด้วย */
    public function test_ot_and_leave_blocklists_are_independent(): void
    {
        Setting::put(Setting::OT_REQUEST_HIDDEN_POSITIONS, ['Y1']);
        Setting::put(Setting::LEAVE_REQUEST_HIDDEN_POSITIONS, ['Z9']);
        $this->resetCaches();

        $this->assertFalse(OtEmployeeEligibility::canRequest(['job_code' => 'Y1']), 'Y1 ถูกห้ามขอ OT');
        $this->assertTrue(OtEmployeeEligibility::canRequestLeave(['job_code' => 'Y1']), 'แต่ Y1 ยังลาได้');

        $this->assertTrue(OtEmployeeEligibility::canRequest(['job_code' => 'Z9']), 'Z9 ยังขอ OT ได้');
        $this->assertFalse(OtEmployeeEligibility::canRequestLeave(['job_code' => 'Z9']), 'แต่ Z9 ถูกห้ามลา');
    }

    /** ล้างค่าที่จำไว้ทั้งสองฝั่ง เพื่อให้แต่ละเคสอ่านค่าที่เพิ่งตั้ง */
    private function resetCaches(): void
    {
        OtEmployeeEligibility::forgetHiddenJobCodes();
        OtEmployeeEligibility::forgetHiddenLeaveJobCodes();
    }
}
