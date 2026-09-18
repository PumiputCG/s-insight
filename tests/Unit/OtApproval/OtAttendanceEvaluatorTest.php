<?php

namespace Tests\Unit\OtApproval;

use App\Models\OtApproval\OtRequest;
use App\Services\OtApproval\OtAttendanceEvaluator;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class OtAttendanceEvaluatorTest extends TestCase
{
    #[DataProvider('clockOutCases')]
    public function test_after_shift_ot_requires_clock_out_at_or_after_requested_end(
        string $clockOut,
        string $expected,
    ): void {
        $request = $this->morningShiftRequest();

        $actual = (new OtAttendanceEvaluator)->evaluate(
            $request,
            ['clock_in' => '07:56', 'clock_out' => $clockOut],
            CarbonImmutable::parse('2026-08-08 21:00:00'),
        );

        $this->assertSame($expected, $actual);
    }

    /** @return array<string, array{string, string}> */
    public static function clockOutCases(): array
    {
        return [
            '20:00 is enough for the 18:00-20:00 request' => ['20:00', OtRequest::ATTENDANCE_PASSED],
            '20:03 passes the 18:00-20:00 request' => ['20:03', OtRequest::ATTENDANCE_PASSED],
            '19:59 is one minute short and remains waiting on the same day' => ['19:59', OtRequest::ATTENDANCE_WAITING],
        ];
    }

    public function test_early_latest_scan_remains_waiting_during_the_work_day(): void
    {
        $request = $this->morningShiftRequest();

        $actual = (new OtAttendanceEvaluator)->evaluate(
            $request,
            ['clock_in' => '07:48', 'clock_out' => '17:05'],
            CarbonImmutable::parse('2026-08-08 17:10:00'),
        );

        $this->assertSame(OtRequest::ATTENDANCE_WAITING, $actual);
    }

    public function test_cross_midnight_request_uses_the_nearest_scan_date(): void
    {
        $request = $this->morningShiftRequest();
        $request->requested_start_at = '2026-08-08 22:30:00';
        $request->requested_end_at = '2026-08-09 01:15:00';

        $actual = (new OtAttendanceEvaluator)->evaluate(
            $request,
            ['clock_in' => '07:48', 'clock_out' => '23:00'],
            CarbonImmutable::parse('2026-08-08 23:05:00'),
        );

        $this->assertSame(OtRequest::ATTENDANCE_WAITING, $actual);
    }

    #[DataProvider('requestAvailabilityCases')]
    public function test_new_request_is_available_regardless_of_punches(
        ?string $clockIn,
        ?string $clockOut,
        bool $canCreate,
        string $status,
    ): void {
        $evaluator = new OtAttendanceEvaluator;
        $attendance = ['clock_in' => $clockIn, 'clock_out' => $clockOut];

        $this->assertSame($canCreate, $evaluator->canCreateRequest($attendance));
        $this->assertSame($status, $evaluator->statusWithoutRequest($attendance));
    }

    /** @return array<string, array{?string, ?string, bool, string}> */
    public static function requestAvailabilityCases(): array
    {
        return [
            'no clock in' => [null, null, true, 'not_requested'],
            'clocked in and still working' => ['07:49', null, true, 'not_requested'],
            'has another scan during the day' => ['07:48', '17:05', true, 'not_requested'],
        ];
    }

    public function test_processed_clock_out_before_ot_end_is_a_final_failure(): void
    {
        $actual = (new OtAttendanceEvaluator)->evaluate(
            $this->morningShiftRequest(),
            [
                'clock_in' => '07:48',
                'clock_out' => '17:05',
                'clock_out_is_final' => true,
                'attendance_state' => 'final_out',
            ],
            CarbonImmutable::parse('2026-08-08 17:10:00'),
        );

        $this->assertSame(OtRequest::ATTENDANCE_FAILED, $actual);
    }

    public function test_latest_scan_turns_from_waiting_to_passed_when_it_reaches_ot_end(): void
    {
        $request = $this->morningShiftRequest();
        $request->work_date = '2026-08-11';
        $request->requested_start_at = '2026-08-11 18:00:00';
        $request->requested_end_at = '2026-08-11 20:00:00';
        $evaluator = new OtAttendanceEvaluator;

        $this->assertSame(
            OtRequest::ATTENDANCE_WAITING,
            $evaluator->evaluate(
                $request,
                ['clock_in' => '07:15', 'clock_out' => '09:52', 'clock_out_is_final' => false],
                CarbonImmutable::parse('2026-08-11 10:00:00'),
            ),
        );
        $this->assertSame(
            OtRequest::ATTENDANCE_PASSED,
            $evaluator->evaluate(
                $request,
                ['clock_in' => '07:15', 'clock_out' => '20:00', 'clock_out_is_final' => false],
                CarbonImmutable::parse('2026-08-11 20:00:00'),
            ),
        );
    }

    /* กลับก่อนเลิกกะยังตัดสิทธิ์ OT เหมือนเดิม เพราะแปลว่าไม่ได้อยู่ทำงานจริง */
    public function test_leaving_before_the_shift_ends_still_fails(): void
    {
        $request = $this->beforeShiftRequest();

        $actual = (new OtAttendanceEvaluator)->evaluate(
            $request,
            ['clock_in' => '05:50', 'clock_out' => '15:00'],
        );

        $this->assertSame(OtRequest::ATTENDANCE_FAILED, $actual);
    }

    /* ── มาสายไม่ตัดสิทธิ์ OT ────────────────────────────────────────────
       เคสจริงของ 69859: กะ 08:00-17:00 เข้า 08:04 ออก 20:02 ขอ OT 18:00-20:00
       เดิมนับเวลาทำงานจากเวลาสแกนจริง สายแม้ 1 นาทีก็ทำงานไม่ครบกะและโดนตัด OT
       ทั้งก้อน ทั้งที่การมาสายเป็นเรื่องของ payroll คนละส่วนกับการทำ OT */
    #[DataProvider('lateArrivalCases')]
    public function test_late_arrival_does_not_void_the_overtime(
        string $clockIn,
        string $clockOut,
        string $expected,
        string $why,
    ): void {
        $request = $this->morningShiftRequest();
        $request->ot_type = 'weekday_after_work';

        $actual = (new OtAttendanceEvaluator)->evaluate(
            $request,
            ['clock_in' => $clockIn, 'clock_out' => $clockOut],
        );

        $this->assertSame($expected, $actual, $why);
    }

    /** @return array<string, array{string, string, string, string}> */
    public static function lateArrivalCases(): array
    {
        return [
            'มาตรงเวลา' => ['08:00', '20:02', OtRequest::ATTENDANCE_PASSED, 'เคสปกติต้องผ่าน'],
            'สาย 1 นาที' => ['08:01', '20:02', OtRequest::ATTENDANCE_PASSED, 'สายนิดเดียวต้องไม่ถูกตัด OT'],
            'สาย 4 นาที (69859)' => ['08:04', '20:02', OtRequest::ATTENDANCE_PASSED, 'เคสที่ Manager แจ้ง'],
            'สาย 1 ชม. ครึ่ง' => ['09:30', '20:02', OtRequest::ATTENDANCE_PASSED, 'สายมากก็ยังทำ OT ได้'],
            'กลับก่อนเลิกกะ' => ['08:00', '16:00', OtRequest::ATTENDANCE_FAILED, 'ขากลับยังต้องอยู่ครบ'],
            'กลับก่อน OT จบ' => ['08:00', '19:30', OtRequest::ATTENDANCE_FAILED, 'ต้องอยู่จนจบช่วง OT'],
        ];
    }

    public function test_approved_request_is_success_only_after_attendance_passes(): void
    {
        $evaluator = new OtAttendanceEvaluator;
        $request = $this->morningShiftRequest();
        $request->approval_status = OtRequest::APPROVAL_APPROVED;
        $request->attendance_status = OtRequest::ATTENDANCE_WAITING;

        $this->assertSame('approved_waiting_scan', $evaluator->displayStatusKey($request));

        $request->attendance_status = OtRequest::ATTENDANCE_PASSED;

        $this->assertSame('success', $evaluator->displayStatusKey($request));
    }

    public function test_failed_attendance_overrides_draft_status(): void
    {
        $request = $this->morningShiftRequest();
        $request->approval_status = OtRequest::APPROVAL_DRAFT;
        $request->attendance_status = OtRequest::ATTENDANCE_FAILED;

        $this->assertSame('failed_time', (new OtAttendanceEvaluator)->displayStatusKey($request));
    }

    /* ── OT ก่อนเข้างาน: หลักฐานอยู่ที่เวลาสแกนเข้า ไม่ใช่ขาออก ──────────
       กะ 08:00-17:00 ขอ OT 06:00-07:00 ต้องสแกนเข้าไม่เกิน 06:00 ถึงจะนับว่าทำจริง
       ก่อนแก้ ระบบดูแต่ขาออกจึงผ่านหมดแม้มาสายกว่าช่วง OT ทั้งช่วง */
    #[DataProvider('beforeShiftClockInCases')]
    public function test_before_shift_ot_requires_clock_in_at_or_before_requested_start(
        string $clockIn,
        string $expected,
    ): void {
        $actual = (new OtAttendanceEvaluator)->evaluate(
            $this->beforeShiftRequest(),
            ['clock_in' => $clockIn, 'clock_out' => '17:05'],
        );

        $this->assertSame($expected, $actual);
    }

    /** @return array<string, array{string, string}> */
    public static function beforeShiftClockInCases(): array
    {
        return [
            'มาก่อน OT เริ่ม 1 นาที' => ['05:59', OtRequest::ATTENDANCE_PASSED],
            'มาตรงเวลา OT เริ่มพอดี' => ['06:00', OtRequest::ATTENDANCE_PASSED],
            'สายกว่า OT เริ่ม 1 นาที' => ['06:01', OtRequest::ATTENDANCE_FAILED],
            'มาหลัง OT จบไปแล้วทั้งช่วง' => ['07:20', OtRequest::ATTENDANCE_FAILED],
        ];
    }

    public function test_before_shift_ot_still_needs_the_full_regular_shift(): void
    {
        // มาทัน OT แต่กลับก่อนเลิกกะ ต้องไม่ผ่านเหมือนเดิม
        $actual = (new OtAttendanceEvaluator)->evaluate(
            $this->beforeShiftRequest(),
            ['clock_in' => '05:50', 'clock_out' => '16:00'],
        );

        $this->assertSame(OtRequest::ATTENDANCE_FAILED, $actual);
    }

    public function test_holiday_ot_checks_both_scan_directions(): void
    {
        $request = $this->beforeShiftRequest();
        $request->ot_type = 'holiday_ot';          // timing = manual, วันหยุดไม่มีกะ
        $request->shift_in = null;
        $request->shift_out = null;
        $request->break_in = null;
        $request->break_out = null;
        $request->requested_start_at = '2026-08-08 08:00:00';
        $request->requested_end_at = '2026-08-08 17:00:00';

        $evaluator = new OtAttendanceEvaluator;

        $this->assertSame(
            OtRequest::ATTENDANCE_FAILED,
            $evaluator->evaluate($request, ['clock_in' => '08:30', 'clock_out' => '17:05']),
            'มาสายกว่าเวลาเริ่ม OT วันหยุดต้องไม่ผ่าน',
        );
        $this->assertSame(
            OtRequest::ATTENDANCE_FAILED,
            $evaluator->evaluate($request, ['clock_in' => '07:55', 'clock_out' => '16:30']),
            'กลับก่อนเวลาจบ OT วันหยุดต้องไม่ผ่าน',
        );
        $this->assertSame(
            OtRequest::ATTENDANCE_PASSED,
            $evaluator->evaluate($request, ['clock_in' => '07:55', 'clock_out' => '17:05']),
            'มาก่อนและกลับหลังช่วง OT ต้องผ่าน',
        );
    }

    public function test_after_shift_ot_ignores_the_clock_in_rule(): void
    {
        // สแกนเข้าตอนเช้าอยู่ก่อนช่วง OT เย็นเสมอ กฎขาเข้าจึงต้องไม่มาทำให้ไม่ผ่าน
        $request = $this->morningShiftRequest();
        $request->ot_type = 'weekday_after_work';

        $actual = (new OtAttendanceEvaluator)->evaluate(
            $request,
            ['clock_in' => '07:56', 'clock_out' => '20:00'],
        );

        $this->assertSame(OtRequest::ATTENDANCE_PASSED, $actual);
    }

    /* ── กวาดครบทุกประเภท OT ที่เลือกได้ในฟอร์ม ────────────────────────
       จุดประสงค์คือกันไม่ให้ประเภทใดประเภทหนึ่งหลุดกฎ เวลาเพิ่มประเภทใหม่
       ใน config แล้วลืมคิดเรื่องทิศทางการสแกน เทสต์นี้จะจับได้ทันที */
    public function test_every_ot_type_checks_the_right_scan_direction(): void
    {
        $pass = OtRequest::ATTENDANCE_PASSED;
        $fail = OtRequest::ATTENDANCE_FAILED;

        // กะ 08:00-17:00 พัก 12:00-13:00 · หลังเลิกงาน OT 18:00-20:00 · ก่อนเข้างาน OT 06:00-07:00 · วันหยุด OT 08:00-17:00
        $byTiming = [
            'after_shift' => [
                ['07:49', '20:00', $pass, 'มาปกติ อยู่จนจบ OT'],
                ['07:49', '19:59', $fail, 'ออกก่อนจบ OT 1 นาที'],
                ['08:01', '20:00', $pass, 'มาสายแต่อยู่จนจบ OT ต้องยังผ่าน'],
            ],
            'before_shift' => [
                ['06:00', '17:05', $pass, 'สแกนเข้าตรงเวลาเริ่ม OT พอดี'],
                ['06:01', '17:05', $fail, 'สแกนเข้าสายกว่าเวลาเริ่ม OT 1 นาที'],
                ['05:50', '16:59', $fail, 'มาทัน OT แต่กลับก่อนเลิกกะ'],
            ],
            'manual' => [
                ['07:55', '17:05', $pass, 'ครอบคลุมช่วง OT ทั้งหมด'],
                ['08:05', '17:05', $fail, 'สแกนเข้าสายกว่าเวลาเริ่ม OT'],
                ['07:55', '16:55', $fail, 'สแกนออกก่อนเวลาจบ OT'],
            ],
        ];

        $evaluator = new OtAttendanceEvaluator;
        $checked = 0;

        foreach (config('ot_approval.types') as $otType => $type) {
            $timing = $type['timing'];
            $this->assertArrayHasKey($timing, $byTiming, $otType.': timing "'.$timing.'" ยังไม่มีเคสทดสอบ');

            foreach ($byTiming[$timing] as [$clockIn, $clockOut, $expected, $why]) {
                $request = match ($timing) {
                    'manual' => $this->holidayRequest($otType),
                    'before_shift' => $this->beforeShiftRequest($otType),
                    default => $this->afterShiftRequest($otType),
                };

                $this->assertSame(
                    $expected,
                    $evaluator->evaluate($request, ['clock_in' => $clockIn, 'clock_out' => $clockOut]),
                    sprintf('%s (%s) เข้า %s ออก %s — %s', $otType, $timing, $clockIn, $clockOut, $why),
                );
                $checked++;
            }
        }

        $this->assertSame(count(config('ot_approval.types')) * 3, $checked, 'ต้องทดสอบครบทุกประเภทใน config');
    }

    public function test_holiday_ot_that_spans_exactly_the_shift_hours_passes(): void
    {
        // เคสจริงของ 65381: กะ 05:00-14:00 ขอ OT วันหยุดคาบเกี่ยวกะพอดีทั้งช่วง
        $request = $this->holidayRequest('holiday_regular');
        $request->shift_in = '05:00:00';
        $request->shift_out = '14:00:00';
        $request->requested_start_at = '2026-08-08 05:00:00';
        $request->requested_end_at = '2026-08-08 14:00:00';

        $evaluator = new OtAttendanceEvaluator;

        $this->assertSame(
            OtRequest::ATTENDANCE_PASSED,
            $evaluator->evaluate($request, ['clock_in' => '04:58', 'clock_out' => '14:05']),
            'มาก่อนและกลับหลังช่วง OT ที่ตรงกับกะพอดี ต้องผ่าน',
        );
        $this->assertSame(
            OtRequest::ATTENDANCE_FAILED,
            $evaluator->evaluate($request, ['clock_in' => '05:02', 'clock_out' => '14:05']),
            'สแกนเข้าหลังกะเริ่ม ต้องไม่ผ่าน',
        );
    }

    private function afterShiftRequest(string $otType): OtRequest
    {
        $request = $this->morningShiftRequest();
        $request->ot_type = $otType;

        return $request;
    }

    /** วันหยุดไม่มีกะ จึงเหลือแค่กฎช่วงเวลา OT ล้วน ๆ */
    private function holidayRequest(string $otType): OtRequest
    {
        return new OtRequest([
            'work_date' => '2026-08-08',
            'ot_type' => $otType,
            'shift_in' => null,
            'shift_out' => null,
            'break_in' => null,
            'break_out' => null,
            'requested_start_at' => '2026-08-08 08:00:00',
            'requested_end_at' => '2026-08-08 17:00:00',
            'requested_hours' => 9,
            'approval_status' => OtRequest::APPROVAL_SUBMITTED,
            'attendance_status' => OtRequest::ATTENDANCE_WAITING,
            'export_status' => OtRequest::EXPORT_NOT_READY,
        ]);
    }

    private function beforeShiftRequest(string $otType = 'weekday_before_work'): OtRequest
    {
        return new OtRequest([
            'work_date' => '2026-08-08',
            'ot_type' => $otType,
            'shift_in' => '08:00:00',
            'shift_out' => '17:00:00',
            'break_in' => '12:00:00',
            'break_out' => '13:00:00',
            'requested_start_at' => '2026-08-08 06:00:00',
            'requested_end_at' => '2026-08-08 07:00:00',
            'requested_hours' => 1,
            'approval_status' => OtRequest::APPROVAL_SUBMITTED,
            'attendance_status' => OtRequest::ATTENDANCE_WAITING,
            'export_status' => OtRequest::EXPORT_NOT_READY,
        ]);
    }

    private function morningShiftRequest(): OtRequest
    {
        return new OtRequest([
            'work_date' => '2026-08-08',
            'shift_in' => '08:00:00',
            'shift_out' => '17:00:00',
            'break_in' => '12:00:00',
            'break_out' => '13:00:00',
            'requested_start_at' => '2026-08-08 18:00:00',
            'requested_end_at' => '2026-08-08 20:00:00',
            'requested_hours' => 2,
            'approval_status' => OtRequest::APPROVAL_SUBMITTED,
            'attendance_status' => OtRequest::ATTENDANCE_WAITING,
            'export_status' => OtRequest::EXPORT_NOT_READY,
        ]);
    }
}
