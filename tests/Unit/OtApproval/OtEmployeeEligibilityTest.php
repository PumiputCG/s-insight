<?php

namespace Tests\Unit\OtApproval;

use App\Models\Insight\Setting;
use App\Support\OtApproval\OtEmployeeEligibility;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * สิทธิ์เห็นปุ่ม `ขอ OT` ตัดสินจากหน้าตั้งค่าหัวข้อ 7 เท่านั้น
 *
 * เดิมบล็อกด้วยรหัส Y1 ใน config และชื่อตำแหน่งเป็น safety net ซึ่ง admin แก้เองไม่ได้
 * ตอนนี้ย้ายมาให้ admin ติ๊กเอง: ไม่ติ๊ก = ขอ OT ได้ตามปกติ
 */
class OtEmployeeEligibilityTest extends TestCase
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

        OtEmployeeEligibility::forgetHiddenJobCodes();
    }

    public function test_employee_with_disabilities_cannot_request_when_admin_ticks_the_position(): void
    {
        Setting::put(Setting::OT_REQUEST_HIDDEN_POSITIONS, ['Y1']);
        OtEmployeeEligibility::forgetHiddenJobCodes();

        $result = OtEmployeeEligibility::evaluate([
            'company' => 'SUPAVUT_INDUSTRY',
            'job_code' => 'Y1',
            'position_th' => 'Employee with Disabilities',
        ]);

        $this->assertFalse($result['eligible']);
        $this->assertSame(OtEmployeeEligibility::REASON_POSITION_NOT_ELIGIBLE, $result['reason']);
    }

    public function test_employee_with_disabilities_can_request_until_admin_ticks_the_position(): void
    {
        // ยังไม่ได้ตั้งค่าอะไรเลย = ทุกตำแหน่งขอได้ ตามที่เจ้าของกำหนด
        $this->assertTrue(OtEmployeeEligibility::canRequest([
            'company' => 'SUPAVUT_INDUSTRY',
            'job_code' => 'Y1',
            'position_th' => 'Employee with Disabilities',
        ]));
    }

    public function test_only_the_ticked_job_code_is_blocked(): void
    {
        Setting::put(Setting::OT_REQUEST_HIDDEN_POSITIONS, ['Y1']);
        OtEmployeeEligibility::forgetHiddenJobCodes();

        // ชื่อตรงกันแต่รหัสไม่ตรง ไม่ถูกบล็อกแล้ว เพราะตัดสินจากรหัสที่ติ๊กเท่านั้น
        $this->assertTrue(OtEmployeeEligibility::canRequest([
            'company' => 'SUPAVUT_INNOMED',
            'job_code' => 'W1',
            'position_en' => ' Employee   with Disabilities ',
        ]));
    }

    public function test_operator_without_shift_or_punches_can_still_request_ot(): void
    {
        $this->assertTrue(OtEmployeeEligibility::canRequest([
            'company' => 'SUPAVUT_INDUSTRY',
            'job_code' => 'W1',
            'position_en' => 'Operator',
            'shift_code' => null,
            'clock_in' => null,
            'clock_out' => null,
        ]));
    }
}
