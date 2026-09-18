<?php

namespace Tests\Feature\OtApproval;

use App\Models\Insight\AppUser;
use App\Models\OtApproval\OtExportDownload;
use App\Models\OtApproval\OtRequest;
use App\Models\OtApproval\OtRequestApproval;
use App\Services\OtApproval\BplusAttendanceService;
use App\Services\OtApproval\ExportDownloadRecorder;
use App\Services\OtApproval\OtAttendanceEvaluator;
use App\Services\OtApproval\OtRequestWorkflowService;
use App\Services\OtApproval\OtTimeRangeCalculator;
use App\Services\OtApproval\V74ExportService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\Concerns\BuildsOtApprovalSchema;
use Tests\TestCase;

/**
 * เดินครบวงจร OT หนึ่งใบ: ยื่น → Supervisor อนุมัติ → เวลาสแกนผ่าน → ออก Excel → ปิดสถานะ exported
 *
 * เป็นเทสต์ที่ผูกทุกชิ้นเข้าด้วยกัน เพื่อจับกรณีที่แต่ละชิ้นถูกต้องแต่ต่อกันแล้วพัง
 * เวลาสแกนถูกฉีดผ่าน BplusAttendanceService ปลอม จึงไม่แตะ Bplus จริงและผลคงที่ทุกครั้ง
 */
class OtLifecycleTest extends TestCase
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
            $table->string('full_name_en')->nullable();
            $table->string('email')->nullable();
            $table->timestamps();
        });

        // อยู่ในรอบเงินเดือนที่ยังอนุมัติได้ (ปิดรอบวันที่ 21)
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-08-14 09:00:00'));
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    public function test_an_approved_request_with_a_passing_scan_reaches_the_excel_file_and_is_marked_exported(): void
    {
        $supervisor = $this->supervisor();
        $request = $this->submittedRequest(['attendance_status' => OtRequest::ATTENDANCE_PASSED]);

        // 1) Supervisor อนุมัติ
        $decided = $this->workflow('20:05')->decide(
            $request,
            $supervisor,
            ['all' => true, 'departments' => [], 'employees' => []],
            OtRequest::APPROVAL_APPROVED,
            null,
        );

        $this->assertSame(OtRequest::APPROVAL_APPROVED, $decided->approval_status);
        $this->assertSame(OtRequest::ATTENDANCE_PASSED, $decided->attendance_status, 'สแกนออก 20:05 ครอบคลุม OT ถึง 20:00');
        $this->assertSame(OtRequest::EXPORT_READY, $decided->export_status, 'อนุมัติ + สแกนผ่าน = พร้อมส่ง HR');
        $this->assertSame($supervisor->id, $decided->decided_by_app_user_id);

        $this->assertDatabaseHas('ot_request_approvals', [
            'ot_request_id' => $decided->id,
            'decision' => OtRequest::APPROVAL_APPROVED,
            'actor_app_user_id' => $supervisor->id,
        ], 'mysql_ot_approval');

        // 2) ออกไฟล์ V74
        $export = app(V74ExportService::class)->export(
            'date',
            CarbonImmutable::parse(self::WORK_DATE),
            null,
            null,
        );

        $this->assertSame(1, $export['count']);
        $this->assertStringStartsWith('V74_OT_', $export['filename']);

        $row = $this->firstDataRow($export['path']);
        $this->assertSame('70001', $row['A'], 'A = รหัสพนักงาน เก็บเป็นข้อความคงเลขศูนย์นำหน้า');
        $this->assertSame('20260813', $row['B'], 'B = วันที่ทำ OT รูปแบบ Ymd');
        $this->assertSame('00', $row['C']);
        $this->assertSame('10101', $row['D'], 'D = รหัสข้อตกลงของ OT หลังเลิกงาน บริษัท Industry');
        $this->assertSame('0', $row['E']);
        $this->assertSame('1', $row['F']);
        $this->assertSame('2', $row['G'], 'G = จำนวนชั่วโมงแบบทศนิยม');

        // 3) บันทึกว่าโหลดแล้ว
        app(ExportDownloadRecorder::class)->recordOt(
            $export['ids'],
            'date',
            CarbonImmutable::parse(self::WORK_DATE),
            null,
            $supervisor,
            CarbonImmutable::parse(self::WORK_DATE),
        );

        $this->assertSame(OtRequest::EXPORT_EXPORTED, $decided->fresh()->export_status);
        $this->assertNotNull($decided->fresh()->exported_at);
        $this->assertDatabaseHas('ot_export_downloads', [
            'module' => OtExportDownload::MODULE_OT,
            'scope' => 'date',
            'row_count' => 1,
        ], 'mysql_ot_approval');

        File::delete($export['path']);
    }

    public function test_a_request_whose_scan_falls_short_is_auto_rejected_instead_of_approved(): void
    {
        $supervisor = $this->supervisor();
        $request = $this->submittedRequest();

        // สแกนออก 19:30 แต่ขอ OT ถึง 20:00 และวันปิดผลแล้ว
        try {
            $this->workflow('19:30')->decide(
                $request,
                $supervisor,
                ['all' => true, 'departments' => [], 'employees' => []],
                OtRequest::APPROVAL_APPROVED,
                null,
            );
            $this->fail('เวลาสแกนไม่ถึงต้องอนุมัติไม่ได้');
        } catch (ValidationException $exception) {
            $this->assertStringContainsString('ระบบปฏิเสธคำขอนี้อัตโนมัติแล้ว', $exception->getMessage());
        }

        $fresh = $request->fresh();
        $this->assertSame(OtRequest::APPROVAL_REJECTED, $fresh->approval_status);
        $this->assertSame(OtRequest::ATTENDANCE_FAILED, $fresh->attendance_status);
        $this->assertSame(OtRequest::EXPORT_NOT_READY, $fresh->export_status);
        $this->assertNull($fresh->decided_by_app_user_id, 'ระบบตัดสินเอง ไม่ใช่คน');

        // audit ของการปฏิเสธอัตโนมัติต้องเขียนลงตารางที่ actor เป็น null ได้
        $audit = OtRequestApproval::query()->where('ot_request_id', $fresh->id)->latest('id')->first();
        $this->assertNotNull($audit);
        $this->assertSame(OtRequest::APPROVAL_REJECTED, $audit->decision);
        $this->assertNull($audit->actor_app_user_id);
    }

    public function test_a_rejected_request_never_reaches_the_excel_file(): void
    {
        $this->submittedRequest([
            'approval_status' => OtRequest::APPROVAL_REJECTED,
            'attendance_status' => OtRequest::ATTENDANCE_PASSED,
        ]);

        $this->expectExceptionMessage('ไม่พบรายการ OT ที่ผ่านในวันที่เลือก');

        app(V74ExportService::class)->export('date', CarbonImmutable::parse(self::WORK_DATE), null, null);
    }

    public function test_an_approved_request_whose_scan_has_not_passed_never_reaches_the_excel_file(): void
    {
        $this->submittedRequest([
            'approval_status' => OtRequest::APPROVAL_APPROVED,
            'attendance_status' => OtRequest::ATTENDANCE_WAITING,
        ]);

        $this->expectExceptionMessage('ไม่พบรายการ OT ที่ผ่านในวันที่เลือก');

        app(V74ExportService::class)->export('date', CarbonImmutable::parse(self::WORK_DATE), null, null);
    }

    public function test_each_filing_round_of_the_same_work_date_is_a_separate_file(): void
    {
        // Bplus บวกชั่วโมงสะสมเมื่อ import ซ้ำ คนที่ส่งไปแล้วต้องไม่อยู่ในไฟล์รอบถัดไป
        $this->submittedRequest([
            'employee_code' => '70001',
            'approval_status' => OtRequest::APPROVAL_APPROVED,
            'attendance_status' => OtRequest::ATTENDANCE_PASSED,
            'submitted_at' => self::WORK_DATE.' 17:30:00',
        ]);
        $this->submittedRequest([
            'employee_code' => '70002',
            'approval_status' => OtRequest::APPROVAL_APPROVED,
            'attendance_status' => OtRequest::ATTENDANCE_PASSED,
            'submitted_at' => '2026-08-16 09:00:00',
        ]);

        $sameDay = app(V74ExportService::class)->export(
            'date',
            CarbonImmutable::parse(self::WORK_DATE),
            null,
            CarbonImmutable::parse(self::WORK_DATE),
        );
        $this->assertSame(1, $sameDay['count'], 'ไฟล์ของรอบที่ยื่นวันทำงานต้องมีเฉพาะคนที่ยื่นวันนั้น');

        $backdated = app(V74ExportService::class)->export(
            'date',
            CarbonImmutable::parse(self::WORK_DATE),
            null,
            CarbonImmutable::parse('2026-08-16'),
        );
        $this->assertSame(1, $backdated['count'], 'รอบยื่นย้อนหลังต้องแยกเป็นอีกไฟล์');
        $this->assertNotSame($sameDay['ids'], $backdated['ids'], 'สองไฟล์ต้องไม่มีรายการซ้ำกัน');

        File::delete($sameDay['path']);
        File::delete($backdated['path']);
    }

    /** ฉีดเวลาสแกนแบบกำหนดเองแทนการต่อ Bplus จริง */
    private function workflow(string $clockOut): OtRequestWorkflowService
    {
        $attendance = new class($clockOut) extends BplusAttendanceService
        {
            public function __construct(private readonly string $clockOut) {}

            public function employees($date, string $company, string $deptCode, array $scope): array
            {
                return ['employees' => [[
                    'code' => '70001',
                    'clock_in' => '07:50',
                    'clock_out' => $this->clockOut,
                    'clock_out_is_final' => true,
                    'attendance_state' => 'final_out',
                    'attendance_source' => 'processed',
                ]]];
            }
        };

        return new OtRequestWorkflowService(
            $attendance,
            app(OtAttendanceEvaluator::class),
            app(OtTimeRangeCalculator::class),
        );
    }

    /** @return array<string, string> คอลัมน์ A..G ของแถวข้อมูลแรก */
    private function firstDataRow(string $path): array
    {
        $sheet = IOFactory::load($path)->getSheetByName('BplusData');
        $row = [];
        foreach (range('A', 'G') as $column) {
            $row[$column] = (string) $sheet->getCell($column.'2')->getValue();
        }

        return $row;
    }

    private function supervisor(): AppUser
    {
        return AppUser::create([
            'employee_code' => 'SV001',
            'full_name_th' => 'ผู้อนุมัติทดสอบ',
            'email' => 'sv@example.test',
        ]);
    }

    /** @param array<string, mixed> $overrides */
    private function submittedRequest(array $overrides = []): OtRequest
    {
        $request = new OtRequest;
        $request->forceFill(array_merge([
            'company' => 'SUPAVUT_INDUSTRY',
            'dept_code' => 'SPV010',
            'employee_code' => '70001',
            'employee_name' => 'พนักงานทดสอบ',
            'department_name' => 'ฝ่ายผลิต',
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

        $request->save();

        return $request;
    }
}
