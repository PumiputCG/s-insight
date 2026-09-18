<?php

namespace Tests\Unit\OtApproval;

use App\Models\Insight\AppUser;
use App\Models\OtApproval\OtRequest;
use App\Services\OtApproval\BplusAttendanceService;
use App\Services\OtApproval\OtAttendanceEvaluator;
use App\Services\OtApproval\OtRequestWorkflowService;
use App\Services\OtApproval\OtTimeRangeCalculator;
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;
use ReflectionMethod;
use Tests\TestCase;

class OtRequestWorkflowServiceTest extends TestCase
{
    public function test_request_form_excludes_the_retired_attendance_day_count_type(): void
    {
        $types = collect($this->service()->types());

        $this->assertCount(7, $types);
        $this->assertFalse($types->contains('key', 'attendance_day_count'));
    }

    public function test_payload_separates_foreman_and_supervisor_notes(): void
    {
        $request = new OtRequest([
            'work_date' => '2026-08-09',
            'requested_start_at' => '2026-08-09 18:00:00',
            'requested_end_at' => '2026-08-09 20:00:00',
            'requested_hours' => 2,
            'requested_minutes' => 0,
            'ot_type' => 'weekday_after_work',
            'note' => 'งานด่วนจาก Foreman',
            'decision_note' => 'อนุมัติให้ตามแผน',
            'approval_status' => OtRequest::APPROVAL_APPROVED,
            'attendance_status' => OtRequest::ATTENDANCE_WAITING,
            'export_status' => OtRequest::EXPORT_NOT_READY,
        ]);

        $payload = $this->service()->requestPayload($request);

        $this->assertSame('งานด่วนจาก Foreman', $payload['request_note']);
        $this->assertSame('อนุมัติให้ตามแผน', $payload['decision_note']);
        $this->assertSame('งานด่วนจาก Foreman', $payload['note']);
    }

    public function test_failed_attendance_does_not_block_selecting_a_draft_for_submission(): void
    {
        $request = new OtRequest([
            'work_date' => '2026-08-09',
            'requested_start_at' => '2026-08-09 18:00:00',
            'requested_end_at' => '2026-08-09 20:00:00',
            'requested_hours' => 2,
            'requested_minutes' => 0,
            'approval_status' => OtRequest::APPROVAL_DRAFT,
            'attendance_status' => OtRequest::ATTENDANCE_FAILED,
            'export_status' => OtRequest::EXPORT_NOT_READY,
        ]);

        $payload = $this->service()->requestPayload($request);

        $this->assertTrue($payload['can_edit']);
        $this->assertTrue($payload['can_select']);
    }

    /**
     * ตำแหน่งที่ admin ติ๊กไว้ในหน้าตั้งค่า (หัวข้อ 7) ต้องถูกปฏิเสธที่ service ด้วย
     * ไม่ใช่แค่ซ่อนปุ่มบนหน้าจอ — กันการยิง API ตรง
     */
    public function test_employee_with_disabilities_cannot_create_a_request_through_the_service(): void
    {
        \Illuminate\Support\Facades\Schema::create('insight_settings', function ($table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });
        \App\Models\Insight\Setting::put(\App\Models\Insight\Setting::OT_REQUEST_HIDDEN_POSITIONS, ['Y1']);
        \App\Support\OtApproval\OtEmployeeEligibility::forgetHiddenJobCodes();

        $attendance = \Mockery::mock(BplusAttendanceService::class);
        $attendance->shouldReceive('employees')->once()->andReturn([
            'company' => 'SUPAVUT_INDUSTRY',
            'employees' => [[
                'company' => 'SUPAVUT_INDUSTRY',
                'code' => 'Y1001',
                'job_code' => 'Y1',
                'position_th' => 'Employee with Disabilities',
            ]],
        ]);

        $service = new OtRequestWorkflowService(
            $attendance,
            new OtAttendanceEvaluator,
            new OtTimeRangeCalculator,
        );

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('ตำแหน่ง Employee with Disabilities ไม่มีสิทธิ์ขอ OT');

        $service->saveDraft([
            'company' => 'SUPAVUT_INDUSTRY',
            'dept_code' => 'SPV017',
            'work_date' => '2026-08-11',
            'employee_code' => 'Y1001',
            'ot_type' => 'weekday_after_work',
            'start_time' => '18:00',
            'end_time' => '20:00',
            'requested_hours' => 2,
            'requested_minutes' => 0,
        ], new AppUser(['employee_code' => 'FM001']), ['all' => true]);
    }

    /* Punch ใช้ประเมินผลจริงภายหลัง แต่ไม่ใช้บล็อก Foreman ตอนสร้างคำขอ */
    public function test_before_shift_request_is_not_blocked_by_a_late_clock_in(): void
    {
        $period = $this->requestedPeriod('06:00', '07:00', '07:20', 'weekday_before_work');

        $this->assertSame('06:00', $period['start']->format('H:i'));
        $this->assertSame(1, $period['hours']);
    }

    public function test_before_shift_ot_is_accepted_when_the_employee_arrived_in_time(): void
    {
        $period = $this->requestedPeriod('06:00', '07:00', '05:59', 'weekday_before_work');

        $this->assertSame('06:00', $period['start']->format('H:i'));
        $this->assertSame(1, $period['hours']);
    }

    public function test_after_shift_ot_is_not_blocked_by_the_morning_clock_in(): void
    {
        // สแกนเข้า 07:49 อยู่ก่อนช่วง OT เย็นเสมอ กฎขาเข้าต้องไม่มาขวาง
        $period = $this->requestedPeriod('18:00', '20:00', '07:49', 'weekday_after_work');

        $this->assertSame('18:00', $period['start']->format('H:i'));
        $this->assertSame(2, $period['hours']);
    }

    /* ── จำนวนต่อวันที่ Foreman กรอกเอง ────────────────────────────────
       ระบบเติมค่าตามช่วงเวลาให้ก่อน แต่ OT ที่คาบเกี่ยวกะทั้งวันต้องหักเวลาพัก
       ออกเอง เช่น 08:00-17:00 auto ได้ 9 ชั่วโมง แล้ว Foreman แก้เป็น 8
       ตัวเลขนี้ไหลต่อไปเป็น "จำนวนที่อนุมัติ" ในไฟล์ V74 ที่ส่งเข้า Bplus */
    public function test_foreman_can_reduce_the_requested_amount_below_the_time_range(): void
    {
        [$hours, $minutes] = $this->requestedAmount(
            ['requested_hours' => 8, 'requested_minutes' => 0],
            ['total_minutes' => 540, 'hours' => 9, 'minutes' => 0],
        );

        $this->assertSame(8, $hours);
        $this->assertSame(0, $minutes);
    }

    public function test_missing_amount_falls_back_to_the_full_time_range(): void
    {
        [$hours, $minutes] = $this->requestedAmount([], ['total_minutes' => 540, 'hours' => 9, 'minutes' => 0]);

        $this->assertSame(9, $hours, 'ไม่ส่งจำนวนมาต้องคิดเต็มช่วงเวลาเหมือนเดิม');
        $this->assertSame(0, $minutes);
    }

    public function test_amount_larger_than_the_time_range_is_rejected(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('จำนวนที่ขอต้องไม่เกินช่วงเวลาที่เลือก (9 ชั่วโมง 0 นาที)');

        $this->requestedAmount(
            ['requested_hours' => 10, 'requested_minutes' => 0],
            ['total_minutes' => 540, 'hours' => 9, 'minutes' => 0],
        );
    }

    public function test_zero_amount_is_rejected(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('กรุณากรอกจำนวนชั่วโมงที่ขอ OT');

        $this->requestedAmount(
            ['requested_hours' => 0, 'requested_minutes' => 0],
            ['total_minutes' => 540, 'hours' => 9, 'minutes' => 0],
        );
    }

    public function test_loose_minutes_roll_up_into_hours(): void
    {
        // กรอก 7 ชม. 90 นาที ต้องเก็บเป็น 8 ชม. 30 นาที
        [$hours, $minutes] = $this->requestedAmount(
            ['requested_hours' => 7, 'requested_minutes' => 90],
            ['total_minutes' => 540, 'hours' => 9, 'minutes' => 0],
        );

        $this->assertSame(8, $hours);
        $this->assertSame(30, $minutes);
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, int>  $period
     * @return array{0: int, 1: int}
     */
    private function requestedAmount(array $data, array $period): array
    {
        $method = new ReflectionMethod(OtRequestWorkflowService::class, 'requestedAmount');

        return $method->invoke($this->service(), $data, $period);
    }

    /** @return array{start: CarbonImmutable, end: CarbonImmutable, hours: int, minutes: int, total_minutes: int} */
    private function requestedPeriod(string $start, string $end, string $clockIn, string $otType): array
    {
        $method = new ReflectionMethod(OtRequestWorkflowService::class, 'requestedPeriod');

        return $method->invoke(
            $this->service(),
            CarbonImmutable::parse('2026-08-08')->startOfDay(),
            $start,
            $end,
            [
                'shift_in' => '08:00:00',
                'shift_out' => '17:00:00',
                'break_in' => '12:00:00',
                'break_out' => '13:00:00',
                'clock_in' => $clockIn,
            ],
            (array) config('ot_approval.types.'.$otType),
        );
    }

    private function service(): OtRequestWorkflowService
    {
        return new OtRequestWorkflowService(
            new BplusAttendanceService,
            new OtAttendanceEvaluator,
            new OtTimeRangeCalculator,
        );
    }
}
