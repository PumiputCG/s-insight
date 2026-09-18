<?php

namespace Tests\Feature\OtApproval;

use App\Models\OtApproval\OtRequest;
use App\Models\OtApproval\OtRequestApproval;
use App\Services\OtApproval\OtAttendanceEvaluator;
use Carbon\CarbonImmutable;
use Tests\Concerns\BuildsOtApprovalSchema;
use Tests\TestCase;

/**
 * กฎเวลาสแกนที่ตัดสินว่า OT "ผ่าน" หรือไม่ — ตามที่ Manager กำหนด
 *
 *   1. เวลาเข้า = สแกนครั้งแรกของวัน · เวลาออก = สแกนครั้งล่าสุดของวัน
 *      (ตรวจที่ระดับ normalize ใน BplusAttendanceServiceTest แล้ว ที่นี่ตรวจผลลัพธ์ปลายทาง)
 *   2. OT นับจากการสแกนหลังเวลาสิ้นสุด OT ที่ขอไว้
 *   3. Supervisor อนุมัติ + สแกนออกครอบคลุมช่วง OT = ผ่าน พร้อมส่ง Bplus
 *   4. เวลาสแกนไม่ถึง = ยังไม่ปิดผลให้รอ, ปิดผลแล้วให้ตก และระบบปฏิเสธเอง
 *
 * ใช้ schema เดียวกับตารางจริงผ่าน BuildsOtApprovalSchema เพื่อให้ auto-reject
 * ถูกทดสอบบนเงื่อนไข NOT NULL ชุดเดียวกับ production
 */
class OtAttendanceRuleTest extends TestCase
{
    use BuildsOtApprovalSchema;

    private const WORK_DATE = '2026-08-13';

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildOtApprovalSchema();
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    // ── กฎที่ 2: สแกนออกต้องเลยเวลาสิ้นสุด OT ────────────────────────────

    public function test_scan_out_after_the_requested_ot_end_passes(): void
    {
        $request = $this->makeRequest();

        $status = $this->evaluate($request, [
            'clock_in' => '07:50',
            'clock_out' => '20:05',   // ขอถึง 20:00 สแกนออก 20:05 = ครอบคลุม
            'clock_out_is_final' => true,
        ]);

        $this->assertSame(OtRequest::ATTENDANCE_PASSED, $status);
    }

    public function test_scan_out_exactly_at_the_requested_end_passes(): void
    {
        $request = $this->makeRequest();

        $status = $this->evaluate($request, [
            'clock_in' => '07:50',
            'clock_out' => '20:00',   // ขอบพอดี ต้องนับว่าผ่าน ไม่ปัดตก
            'clock_out_is_final' => true,
        ]);

        $this->assertSame(OtRequest::ATTENDANCE_PASSED, $status);
    }

    public function test_scan_out_before_the_requested_end_fails_once_the_day_is_closed(): void
    {
        $request = $this->makeRequest();

        $status = $this->evaluate($request, [
            'clock_in' => '07:50',
            'clock_out' => '19:30',   // กลับก่อนจบ OT
            'clock_out_is_final' => true,
        ]);

        $this->assertSame(OtRequest::ATTENDANCE_FAILED, $status);
    }

    public function test_scan_out_before_the_requested_end_still_waits_while_the_day_is_open(): void
    {
        $request = $this->makeRequest();

        // ยังอยู่ในวันงานและ Bplus ยังไม่ปิดผล = อาจมีสแกนอีก ห้ามด่วนตัดสินว่าไม่ผ่าน
        $status = $this->evaluate($request, [
            'clock_in' => '07:50',
            'clock_out' => '19:30',
            'clock_out_is_final' => false,
            'attendance_state' => 'latest_scan',
        ], CarbonImmutable::parse(self::WORK_DATE.' 19:35:00'));

        $this->assertSame(OtRequest::ATTENDANCE_WAITING, $status);
    }

    public function test_missing_scan_out_waits_during_the_day_and_fails_the_next_day(): void
    {
        $request = $this->makeRequest();
        $attendance = ['clock_in' => '07:50', 'clock_out' => null, 'attendance_state' => 'in_only'];

        $this->assertSame(
            OtRequest::ATTENDANCE_WAITING,
            $this->evaluate($request, $attendance, CarbonImmutable::parse(self::WORK_DATE.' 21:00:00')),
            'ยังไม่ข้ามวัน ต้องรอสแกนออกก่อน',
        );

        $this->assertSame(
            OtRequest::ATTENDANCE_FAILED,
            $this->evaluate($request, $attendance, CarbonImmutable::parse('2026-08-14 08:00:00')),
            'ข้ามวันแล้วยังไม่มีสแกนออก ถือว่าไม่ผ่าน',
        );
    }

    public function test_no_scan_at_all_fails_once_the_day_is_closed(): void
    {
        $request = $this->makeRequest();

        $status = $this->evaluate($request, ['clock_in' => null, 'clock_out' => null], CarbonImmutable::parse('2026-08-15 08:00:00'));

        $this->assertSame(OtRequest::ATTENDANCE_FAILED, $status);
    }

    // ── OT ก่อนเข้างาน: หลักฐานอยู่ที่ "ขาเข้า" ────────────────────────────

    public function test_before_shift_ot_fails_when_the_first_scan_is_later_than_the_ot_start(): void
    {
        $request = $this->makeRequest([
            'ot_type' => 'weekday_before_work',
            'requested_start_at' => self::WORK_DATE.' 06:00:00',
            'requested_end_at' => self::WORK_DATE.' 07:00:00',
            'requested_hours' => 1,
        ]);

        // มาสแกน 07:20 ทั้งที่ขอ OT 06:00 = ไม่ได้มาทำจริง ต้องตกทันทีไม่ต้องรอ
        $status = $this->evaluate($request, [
            'clock_in' => '07:20',
            'clock_out' => '17:05',
            'clock_out_is_final' => true,
        ]);

        $this->assertSame(OtRequest::ATTENDANCE_FAILED, $status);
    }

    public function test_before_shift_ot_passes_when_the_first_scan_is_before_the_ot_start(): void
    {
        $request = $this->makeRequest([
            'ot_type' => 'weekday_before_work',
            'requested_start_at' => self::WORK_DATE.' 06:00:00',
            'requested_end_at' => self::WORK_DATE.' 07:00:00',
            'requested_hours' => 1,
        ]);

        $status = $this->evaluate($request, [
            'clock_in' => '05:55',
            'clock_out' => '17:05',
            'clock_out_is_final' => true,
        ]);

        $this->assertSame(OtRequest::ATTENDANCE_PASSED, $status);
    }

    public function test_after_shift_ot_ignores_a_late_arrival(): void
    {
        // มาสายแต่ยังอยู่ทำ OT จนครบ — กฎ OT หลังเลิกงานดูขาออกอย่างเดียว
        $request = $this->makeRequest();

        $status = $this->evaluate($request, [
            'clock_in' => '09:30',
            'clock_out' => '20:10',
            'clock_out_is_final' => true,
        ]);

        $this->assertSame(OtRequest::ATTENDANCE_PASSED, $status);
    }

    // ── กะดึกข้ามเที่ยงคืน ────────────────────────────────────────────────

    public function test_night_shift_ot_ending_after_midnight_passes(): void
    {
        $request = $this->makeRequest([
            'shift_in' => '20:00:00',
            'shift_out' => '05:00:00',
            'break_in' => '00:00:00',
            'break_out' => '01:00:00',
            'requested_start_at' => '2026-08-14 06:00:00',
            'requested_end_at' => '2026-08-14 08:00:00',
        ]);

        // สแกนออก 08:05 ของวันรุ่งขึ้น ต้องจับคู่กับช่วง OT ที่ข้ามเที่ยงคืนได้
        $status = $this->evaluate($request, [
            'clock_in' => '19:50',
            'clock_out' => '08:05',
            'clock_out_is_final' => true,
        ]);

        $this->assertSame(OtRequest::ATTENDANCE_PASSED, $status);
    }

    // ── กฎที่ 3: อนุมัติ + สแกนผ่าน = ผ่าน พร้อมส่ง Bplus ──────────────────

    public function test_approved_plus_passed_scan_shows_success_and_becomes_export_ready(): void
    {
        $request = $this->makeRequest([
            'approval_status' => OtRequest::APPROVAL_APPROVED,
            'attendance_status' => OtRequest::ATTENDANCE_PASSED,
            'export_status' => OtRequest::EXPORT_READY,
        ]);

        $this->assertSame('success', (new OtAttendanceEvaluator)->displayStatusKey($request));
    }

    public function test_approved_but_scan_not_yet_passed_is_not_success(): void
    {
        $request = $this->makeRequest([
            'approval_status' => OtRequest::APPROVAL_APPROVED,
            'attendance_status' => OtRequest::ATTENDANCE_WAITING,
        ]);

        $this->assertSame('approved_waiting_scan', (new OtAttendanceEvaluator)->displayStatusKey($request));
    }

    public function test_scan_passed_but_not_yet_approved_is_not_success(): void
    {
        $request = $this->makeRequest([
            'approval_status' => OtRequest::APPROVAL_SUBMITTED,
            'attendance_status' => OtRequest::ATTENDANCE_PASSED,
        ]);

        $this->assertSame('waiting_approval', (new OtAttendanceEvaluator)->displayStatusKey($request));
    }

    public function test_failed_scan_outranks_an_approval_in_the_status_label(): void
    {
        $request = $this->makeRequest([
            'approval_status' => OtRequest::APPROVAL_APPROVED,
            'attendance_status' => OtRequest::ATTENDANCE_FAILED,
        ]);

        $this->assertSame('failed_time', (new OtAttendanceEvaluator)->displayStatusKey($request));
    }

    // ── Regression: audit ของการปฏิเสธอัตโนมัติต้องเขียนลงตารางจริงได้ ─────

    public function test_system_rejection_can_write_its_audit_row_without_an_actor(): void
    {
        /* บั๊กจริงที่เคยหลุด: `actor_app_user_id` เป็น NOT NULL บน MySQL
           แต่ระบบปฏิเสธเองโดยไม่มีคนกด จึงเขียน null → SQLSTATE[23000] บน production
           เทสต์เดิมไม่จับเพราะสร้างตารางเองแบบ nullable */
        $request = $this->makeRequest();

        $audit = OtRequestApproval::create([
            'ot_request_id' => $request->id,
            'decision' => OtRequest::APPROVAL_REJECTED,
            'actor_app_user_id' => null,
            'actor_employee_code' => null,
            'note' => 'ระบบปฏิเสธอัตโนมัติ: เวลาสแกนไม่ครอบคลุมช่วง OT ที่ขอ',
        ]);

        $this->assertNull($audit->fresh()->actor_app_user_id);
    }

    /** @param array<string, mixed> $attendance */
    private function evaluate(OtRequest $request, array $attendance, ?CarbonImmutable $now = null): string
    {
        return (new OtAttendanceEvaluator)->evaluate(
            $request,
            $attendance,
            $now ?? CarbonImmutable::parse('2026-08-14 09:00:00'),
        );
    }

    /** @param array<string, mixed> $overrides */
    private function makeRequest(array $overrides = []): OtRequest
    {
        return OtRequest::create(array_merge([
            'company' => 'SUPAVUT_INDUSTRY',
            'dept_code' => 'SPV010',
            'employee_code' => 'T'.random_int(10000, 99999),
            'work_date' => self::WORK_DATE,
            'shift_code' => 'AD03',
            'shift_in' => '08:00:00',
            'shift_out' => '17:00:00',
            'break_in' => '12:00:00',
            'break_out' => '13:00:00',
            'ot_type' => 'weekday_after_work',
            'multiplier' => 1.5,
            'requested_start_at' => self::WORK_DATE.' 18:00:00',
            'requested_end_at' => self::WORK_DATE.' 20:00:00',
            'requested_hours' => 2,
            'requested_minutes' => 0,
            'approval_status' => OtRequest::APPROVAL_SUBMITTED,
            'attendance_status' => OtRequest::ATTENDANCE_WAITING,
            'export_status' => OtRequest::EXPORT_NOT_READY,
            'created_by_app_user_id' => 1,
            'created_by_employee_code' => 'FM001',
            'submitted_at' => self::WORK_DATE.' 17:30:00',
        ], $overrides));
    }
}
