<?php

namespace Tests\Feature\OtApproval;

use App\Models\Insight\AppUser;
use App\Models\OtApproval\OtRequest;
use App\Services\OtApproval\BplusAttendanceService;
use App\Services\OtApproval\OtAttendanceEvaluator;
use App\Services\OtApproval\OtRequestWorkflowService;
use App\Services\OtApproval\OtTimeRangeCalculator;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Schema;
use Tests\Concerns\BuildsOtApprovalSchema;
use Tests\TestCase;

/**
 * HR แก้กะใน Bplus หลังจากคำขอ OT ถูกยื่นไปแล้ว
 *
 * ตอนกดบันทึกคำขอ ระบบก๊อปกะของวันนั้นมาแช่ไว้ในตัวคำขอ และตัวประเมินเวลาสแกน
 * ใช้กะที่แช่ไว้นี้ ไม่ได้ถาม Bplus ใหม่ทุกครั้ง
 *
 * กติกาที่ Manager เลือก (2026-08-19):
 *   - คำขอที่ยัง "รออนุมัติ" → ดึงกะใหม่มาทับ แล้วประเมินเวลาสแกนด้วยกะที่ถูกต้อง
 *   - คำขอที่ "อนุมัติแล้ว"  → แช่แข็งไว้ ต้องตรวจสอบย้อนหลังได้ว่าอนุมัติบนพื้นฐานกะอะไร
 */
class OtShiftChangeSyncTest extends TestCase
{
    use BuildsOtApprovalSchema;

    private const WORK_DATE = '2026-08-13';

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildOtApprovalSchema();

        Schema::create('app_users', function ($table) {
            $table->id();
            $table->string('employee_code')->nullable();
            $table->string('full_name_th')->nullable();
            $table->timestamps();
        });

        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-08-14 09:00:00'));
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    public function test_a_pending_request_picks_up_the_new_shift_from_bplus(): void
    {
        // ยื่นตอน Bplus บอกกะ 08:00-17:00 · OT 18:00-20:00 (เลิกกะ + พัก 1 ชม.)
        $request = $this->submittedRequest();

        // HR แก้กะเป็น 08:00-16:00 ทีหลัง
        $this->workflowWithShift('08:00', '16:00', '20:05')->decide(
            $request,
            $this->supervisor(),
            ['all' => true, 'departments' => [], 'employees' => []],
            OtRequest::APPROVAL_APPROVED,
            null,
        );

        $fresh = $request->fresh();
        $this->assertSame('16:00', substr((string) $fresh->shift_out, 0, 5), 'คำขอที่ยังไม่อนุมัติต้องรับกะใหม่');
        $this->assertSame('AD99', $fresh->shift_code);
    }

    public function test_an_approved_request_keeps_the_shift_it_was_approved_with(): void
    {
        $request = $this->submittedRequest([
            'approval_status' => OtRequest::APPROVAL_APPROVED,
            'attendance_status' => OtRequest::ATTENDANCE_PASSED,
        ]);

        // เรียก refresh ผ่านหน้าคิวอนุมัติ (decorate) โดย Bplus ส่งกะใหม่มา
        $this->refreshThrough($this->workflowWithShift('08:00', '16:00', '20:05'), $request);

        $fresh = $request->fresh();
        $this->assertSame('17:00', substr((string) $fresh->shift_out, 0, 5), 'อนุมัติแล้วต้องคงกะเดิมไว้');
        $this->assertSame('AD03', $fresh->shift_code);
    }

    public function test_the_new_shift_changes_the_attendance_verdict(): void
    {
        /* OT ก่อนเข้างาน 06:00-07:00 · สแกนเข้า 05:55 ออก 16:30
           กะเดิม 08:00-17:00 (หักพัก = ต้องทำ 8 ชม.) -> ออก 16:30 ยังไม่ครบกะ -> ไม่ผ่าน
           HR แก้กะเป็น 08:00-16:00 (ต้องทำ 7 ชม.)   -> ออก 16:30 ครบแล้ว     -> ผ่าน */
        $request = $this->submittedRequest([
            'ot_type' => 'weekday_before_work',
            'requested_start_at' => self::WORK_DATE.' 06:00:00',
            'requested_end_at' => self::WORK_DATE.' 07:00:00',
            'requested_hours' => 1,
        ]);

        $oldShiftVerdict = (new OtAttendanceEvaluator)->evaluate($request, [
            'clock_in' => '05:55', 'clock_out' => '16:30', 'clock_out_is_final' => true,
        ]);
        $this->assertSame(OtRequest::ATTENDANCE_FAILED, $oldShiftVerdict, 'กะเดิมยาวกว่า จึงยังทำไม่ครบ');

        $this->refreshThrough($this->workflowWithShift('08:00', '16:00', '16:30', '05:55'), $request);

        $fresh = $request->fresh();
        $this->assertSame('16:00', substr((string) $fresh->shift_out, 0, 5));
        $this->assertSame(OtRequest::ATTENDANCE_PASSED, $fresh->attendance_status, 'กะใหม่สั้นลง ทำครบแล้วจึงผ่าน');
    }

    public function test_a_day_without_shift_data_in_bplus_does_not_wipe_the_stored_shift(): void
    {
        // วันหยุด/ยังไม่จัดกะ Bplus ส่งกะว่างมา ต้องไม่ไปล้างกะที่เก็บไว้ทิ้ง
        $request = $this->submittedRequest();

        $this->refreshThrough($this->workflowWithShift(null, null, '20:05'), $request);

        $fresh = $request->fresh();
        $this->assertSame('08:00', substr((string) $fresh->shift_in, 0, 5));
        $this->assertSame('17:00', substr((string) $fresh->shift_out, 0, 5));
    }

    /** เรียก refreshAttendance ผ่าน decide() ซึ่งเป็นทางเข้าปกติของระบบ */
    private function refreshThrough(OtRequestWorkflowService $workflow, OtRequest $request): void
    {
        $reflection = new \ReflectionMethod($workflow, 'refreshAttendance');
        $reflection->setAccessible(true);
        $attendance = new \ReflectionMethod($workflow, 'attendanceForRequest');
        $attendance->setAccessible(true);

        $payload = $attendance->invoke($workflow, $request, ['all' => true, 'departments' => [], 'employees' => []]);
        if ($payload !== null) {
            $reflection->invoke($workflow, $request, $payload);
        }
    }

    /** Bplus ปลอมที่ส่งกะตามที่กำหนด เพื่อจำลองว่า HR แก้กะแล้ว */
    private function workflowWithShift(?string $shiftIn, ?string $shiftOut, string $clockOut, string $clockIn = '07:50'): OtRequestWorkflowService
    {
        $attendance = new class($shiftIn, $shiftOut, $clockOut, $clockIn) extends BplusAttendanceService
        {
            public function __construct(
                private readonly ?string $shiftIn,
                private readonly ?string $shiftOut,
                private readonly string $clockOut,
                private readonly string $clockIn,
            ) {}

            public function employees($date, string $company, string $deptCode, array $scope): array
            {
                return ['employees' => [[
                    'code' => '70001',
                    'shift_code' => $this->shiftIn === null ? '' : 'AD99',
                    'shift_name_th' => $this->shiftIn === null ? '' : 'วันงาน แก้ใหม่',
                    'shift_in' => $this->shiftIn,
                    'shift_out' => $this->shiftOut,
                    'break_in' => $this->shiftIn === null ? null : '12:00',
                    'break_out' => $this->shiftIn === null ? null : '13:00',
                    'clock_in' => $this->clockIn,
                    'clock_out' => $this->clockOut,
                    'clock_out_is_final' => true,
                    'attendance_state' => 'final_out',
                    'attendance_source' => 'processed',
                ]]];
            }
        };

        return new OtRequestWorkflowService($attendance, new OtAttendanceEvaluator, new OtTimeRangeCalculator);
    }

    private function supervisor(): AppUser
    {
        return AppUser::create(['employee_code' => 'SV001', 'full_name_th' => 'ผู้อนุมัติ']);
    }

    /** @param array<string, mixed> $overrides */
    private function submittedRequest(array $overrides = []): OtRequest
    {
        return OtRequest::create(array_merge([
            'company' => 'SUPAVUT_INDUSTRY',
            'dept_code' => 'SPV010',
            'employee_code' => '70001',
            'work_date' => self::WORK_DATE,
            'shift_code' => 'AD03',
            'shift_name' => 'วันงาน 08.00-17.00 น.',
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
