<?php

namespace Tests\Feature\OtApproval;

use App\Mail\OtApproval\LeaveRequestSubmittedMail;
use App\Models\Insight\AppUser;
use App\Models\OtApproval\LeaveRequest;
use App\Models\OtApproval\OtDepartmentAssignment;
use App\Models\OtApproval\OtExportDownload;
use App\Models\OtApproval\OtNotification;
use App\Services\OtApproval\ExportDownloadRecorder;
use App\Services\OtApproval\Leave75ExportService;
use App\Services\OtApproval\LeaveNotificationService;
use App\Services\OtApproval\LeaveRequestWorkflowService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\Concerns\BuildsOtApprovalSchema;
use Tests\TestCase;

/**
 * เดินครบวงจรลาหยุดงานตามมาตรา 75: ยื่น → แจ้ง Supervisor → อนุมัติ → ออก Excel → ปิด exported
 *
 * ต่างจาก OT ตรงที่ไม่มี attendance gate — อนุมัติแล้วพร้อมส่ง HR ทันที
 * และคำขอที่ยื่นถึงวันลารวมเป็นไฟล์เดียว (ดูรายละเอียดการจัดไฟล์ใน Leave75DownloadDocumentTest)
 */
class Leave75LifecycleTest extends TestCase
{
    use BuildsOtApprovalSchema;

    private const LEAVE_DATE = '2026-08-20';

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildOtApprovalSchema();

        Schema::create('app_users', function ($table) {
            $table->id();
            $table->string('employee_code')->nullable();
            $table->string('full_name_th')->nullable();
            $table->string('full_name_en')->nullable();
            $table->string('email')->nullable();
            $table->timestamps();
        });

        // ขอลาต้องยืนยันว่าพนักงานยังทำงานอยู่ทุกวันในช่วงที่เลือก จึงต้องมี employee master
        Schema::create('employees', function ($table) {
            $table->id();
            $table->string('company');
            $table->string('employee_code');
            $table->string('license_id')->nullable();
            $table->string('title')->nullable();
            $table->string('name_th')->nullable();
            $table->string('surname_th')->nullable();
            $table->string('name_en')->nullable();
            $table->string('job_code')->nullable();
            $table->string('job_th')->nullable();
            $table->string('job_en')->nullable();
            $table->string('dept_code')->nullable();
            $table->string('dept_th')->nullable();
            $table->string('dept_en')->nullable();
            $table->date('hire_date')->nullable();
            $table->date('pending_resign_date')->nullable();
            $table->date('manual_resign_date')->nullable();
            $table->date('resign_date')->nullable();
            $table->string('emp_status')->default('1');
            $table->timestamps();
        });

        \App\Models\Insight\Employee::create([
            'company' => 'SUPAVUT_INDUSTRY',
            'employee_code' => '70001',
            'name_th' => 'พนักงาน',
            'surname_th' => 'ทดสอบ',
            'dept_code' => 'SPV010',
            'dept_th' => 'ฝ่ายผลิต',
            'job_code' => 'OP01',
            'hire_date' => '2024-01-01',
            'emp_status' => '1',
        ]);

        // ก่อนวันปิดรอบ (21 ก.ย.) ของรอบที่ 20 ส.ค. อยู่ จึงยังอนุมัติได้
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-08-18 09:00:00'));
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    public function test_approved_leave_needs_no_scan_and_flows_into_the_excel_file(): void
    {
        $supervisor = $this->user('SV002', 'sup-leave@example.test');
        $request = $this->submittedLeave();

        $decided = app(LeaveRequestWorkflowService::class)->decide(
            $request,
            $supervisor,
            ['all' => true, 'departments' => [], 'employees' => []],
            LeaveRequest::APPROVAL_APPROVED,
            null,
        );

        $this->assertSame(LeaveRequest::APPROVAL_APPROVED, $decided->approval_status);
        $this->assertSame(
            LeaveRequest::EXPORT_READY,
            $decided->export_status,
            'การลาไม่มี attendance gate อนุมัติแล้วต้องพร้อมส่ง HR ทันที',
        );

        $export = app(Leave75ExportService::class)->export('date', CarbonImmutable::parse(self::LEAVE_DATE));
        $this->assertSame(1, $export['count']);
        $this->assertStringStartsWith('LEAVE75_', $export['filename']);

        $sheet = IOFactory::load($export['path'])->getSheetByName('BplusData');
        $this->assertSame('70001', (string) $sheet->getCell('A2')->getValue(), 'A = รหัสพนักงาน');
        $this->assertSame('20260820', (string) $sheet->getCell('B2')->getValue(), 'B = วันลา Ymd');
        $this->assertSame('00', (string) $sheet->getCell('C2')->getValue(), 'C = รหัสกะคงที่');
        $this->assertSame('020008(1)', (string) $sheet->getCell('D2')->getValue(), 'D = รหัสผลข้อตกลงเงินหักของมาตรา 75');
        // E/F/G ของไฟล์ลาเป็นตัวเลข ต่างจาก V74 ที่เป็นข้อความทั้งแถว — Bplus ยึดชนิดเซลล์
        $this->assertSame(0.0, $sheet->getCell('E2')->getValue());
        $this->assertSame(1.0, $sheet->getCell('F2')->getValue());
        $this->assertSame(1.0, $sheet->getCell('G2')->getValue(), 'G = จำนวนวันลา เต็มวัน = 1');

        app(ExportDownloadRecorder::class)->recordLeave(
            $export['ids'],
            'date',
            CarbonImmutable::parse(self::LEAVE_DATE),
            null,
            $supervisor,
            CarbonImmutable::parse(self::LEAVE_DATE),
        );

        $this->assertSame(LeaveRequest::EXPORT_EXPORTED, $decided->fresh()->export_status);
        $this->assertDatabaseHas('ot_export_downloads', [
            'module' => OtExportDownload::MODULE_LEAVE,
            'row_count' => 1,
        ], 'mysql_ot_approval');

        File::delete($export['path']);
    }

    public function test_a_date_range_becomes_one_row_per_day_sharing_a_batch(): void
    {
        $foreman = $this->user('FM010', 'fm10@example.test');

        $rows = app(LeaveRequestWorkflowService::class)->saveDraft([
            'company' => 'SUPAVUT_INDUSTRY',
            'dept_code' => 'SPV010',
            'employee_code' => '70001',
            'leave_type' => 'section_75',
            'start_date' => '2026-08-20',
            'end_date' => '2026-08-22',
            'note' => 'หยุดทั้งบริษัท',
        ], $foreman, ['all' => true, 'departments' => [], 'employees' => []]);

        $this->assertCount(3, $rows, 'ช่วง 3 วันต้องแตกเป็น 3 แถว หนึ่งแถวต่อหนึ่งวันตาม Template Bplus');
        $this->assertCount(1, $rows->pluck('batch_uuid')->unique(), 'ทุกแถวจากการกรอกครั้งเดียวต้องใช้ batch เดียวกัน');
        $this->assertSame(
            ['2026-08-20', '2026-08-21', '2026-08-22'],
            $rows->map(fn (LeaveRequest $row) => $row->leave_date->format('Y-m-d'))->sort()->values()->all(),
        );

        // ค่าคงที่ของมาตรา 75 ต้องถูกปั๊มให้ครบ ไม่งั้นไฟล์ที่ HR นำเข้าจะผิด
        $first = $rows->first();
        $this->assertSame('020008(1)', $first->deduction_agreement_code);
        $this->assertSame('00', $first->shift_code);
        $this->assertSame('1', $first->approval_method);
        $this->assertSame('1.00', (string) $first->leave_quantity);
    }

    public function test_the_same_employee_cannot_hold_two_submitted_requests_for_one_day(): void
    {
        $foreman = $this->user('FM011', 'fm11@example.test');
        $scope = ['all' => true, 'departments' => [], 'employees' => []];
        $payload = [
            'company' => 'SUPAVUT_INDUSTRY',
            'dept_code' => 'SPV010',
            'employee_code' => '70001',
            'leave_type' => 'section_75',
            'start_date' => self::LEAVE_DATE,
            'end_date' => self::LEAVE_DATE,
        ];

        $rows = app(LeaveRequestWorkflowService::class)->saveDraft($payload, $foreman, $scope);
        app(LeaveRequestWorkflowService::class)->submitDrafts($rows->pluck('id')->all(), $foreman, $scope);

        $this->expectExceptionMessage('มีคำขอที่ส่งอนุมัติแล้ว');
        app(LeaveRequestWorkflowService::class)->saveDraft($payload, $foreman, $scope);
    }

    public function test_submitting_leave_emails_the_leave_supervisor_once_per_department(): void
    {
        Mail::fake();

        $supervisor = $this->user('SV003', 'leave-sup@example.test');
        OtDepartmentAssignment::create([
            'module' => OtDepartmentAssignment::MODULE_LEAVE,
            'company' => 'SUPAVUT_INDUSTRY',
            'dept_code' => 'SPV010',
            'role' => OtDepartmentAssignment::ROLE_SUPERVISOR,
            'app_user_id' => $supervisor->id,
            'employee_code' => $supervisor->employee_code,
        ]);

        $foreman = $this->user('FM012', 'fm12@example.test');
        $first = $this->submittedLeave(['employee_code' => '70001']);
        $second = $this->submittedLeave(['employee_code' => '70002']);

        app(LeaveNotificationService::class)->notifySubmitted([$first->id, $second->id], $foreman);

        // สองคนในแผนกเดียวกัน = อีเมลฉบับเดียว กันแจ้งเตือนถี่ตามที่ Manager กำหนด
        Mail::assertSent(LeaveRequestSubmittedMail::class, 1);
        Mail::assertSent(
            LeaveRequestSubmittedMail::class,
            fn (LeaveRequestSubmittedMail $mail) => $mail->hasTo('leave-sup@example.test'),
        );

        $bell = OtNotification::query()->where('employee_code', 'SV003')->first();
        $this->assertNotNull($bell, 'Supervisor ต้องได้กระดิ่งด้วย');
        $this->assertSame(OtNotification::CATEGORY_LEAVE, $bell->category, 'ต้องอยู่หมวดการลา ไม่ปนกับ OT');
        $this->assertSame(2, $bell->item_count);
    }

    public function test_rejecting_leave_requires_a_reason_and_blocks_the_export(): void
    {
        $supervisor = $this->user('SV004', 'sup4@example.test');
        $request = $this->submittedLeave();
        $scope = ['all' => true, 'departments' => [], 'employees' => []];

        try {
            app(LeaveRequestWorkflowService::class)->decide(
                $request,
                $supervisor,
                $scope,
                LeaveRequest::APPROVAL_REJECTED,
                null,
            );
            $this->fail('ไม่อนุมัติต้องบังคับให้ระบุเหตุผล');
        } catch (ValidationException $exception) {
            $this->assertStringContainsString('เหตุผล', $exception->getMessage());
        }

        $decided = app(LeaveRequestWorkflowService::class)->decide(
            $request,
            $supervisor,
            $scope,
            LeaveRequest::APPROVAL_REJECTED,
            'ยกเลิกวันหยุด',
        );

        $this->assertSame(LeaveRequest::APPROVAL_REJECTED, $decided->approval_status);
        $this->assertSame(LeaveRequest::EXPORT_NOT_READY, $decided->export_status);

        $this->expectExceptionMessage('ไม่พบรายการลาที่อนุมัติแล้ว');
        app(Leave75ExportService::class)->export('date', CarbonImmutable::parse(self::LEAVE_DATE));
    }

    private function user(string $code, ?string $email): AppUser
    {
        return AppUser::create([
            'employee_code' => $code,
            'full_name_th' => 'ผู้ใช้ '.$code,
            'email' => $email,
        ]);
    }

    /** @param array<string, mixed> $overrides */
    private function submittedLeave(array $overrides = []): LeaveRequest
    {
        return LeaveRequest::create(array_merge([
            'batch_uuid' => (string) \Illuminate\Support\Str::uuid(),
            'company' => 'SUPAVUT_INDUSTRY',
            'dept_code' => 'SPV010',
            'employee_code' => '70001',
            'employee_name' => 'พนักงานทดสอบ',
            'department_name' => 'ฝ่ายผลิต',
            'leave_date' => self::LEAVE_DATE,
            'range_start' => self::LEAVE_DATE,
            'range_end' => self::LEAVE_DATE,
            'leave_type' => 'section_75',
            'bplus_stamp_type_key' => '20030',
            'deduction_agreement_code' => '020008(1)',
            'shift_code' => '00',
            'swipe_character_code' => '0',
            'approval_method' => '1',
            'leave_quantity' => 1,
            'approval_status' => LeaveRequest::APPROVAL_SUBMITTED,
            'export_status' => LeaveRequest::EXPORT_NOT_READY,
            'created_by_app_user_id' => 1,
            'created_by_employee_code' => 'FM001',
            'submitted_at' => '2026-08-17 10:00:00',
        ], $overrides));
    }
}
