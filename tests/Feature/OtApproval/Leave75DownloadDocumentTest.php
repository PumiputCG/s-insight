<?php

namespace Tests\Feature\OtApproval;

use App\Models\OtApproval\LeaveRequest;
use App\Models\OtApproval\OtExportDownload;
use App\Services\OtApproval\OtDownloadCalendarService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * ลา 75 รวมคำขอที่ยื่นถึงวันลาเป็นเอกสารฉบับเดียว (Manager 2026-08-17)
 *
 * ต่างจาก OT ตรงที่วันลาเป็นวันอนาคตที่ประกาศไว้ล่วงหน้า Foreman จึงทยอยยื่นได้
 * หลายวันก่อนถึงวันลา ถ้าแตกเป็นไฟล์รายวันแบบ OT admin จะต้องกดโหลดวันละไฟล์โดยไม่จำเป็น
 * ส่วนคำขอที่ยื่น "หลัง" วันลาไปแล้วถือเป็นย้อนหลัง ต้องแยกไฟล์รายวันเหมือน OT
 * เพราะ Bplus บวกจำนวนสะสมเมื่อ import ซ้ำ คนที่ HR รับไปแล้วต้องไม่อยู่ในไฟล์รอบใหม่
 */
class Leave75DownloadDocumentTest extends TestCase
{
    private const LEAVE_DATE = '2026-08-20';

    protected function setUp(): void
    {
        parent::setUp();

        config(['database.connections.mysql_ot_approval' => config('database.connections.sqlite')]);
        DB::purge('mysql_ot_approval');

        Schema::connection('mysql_ot_approval')->create('ot_requests', function ($table) {
            $table->id();
            $table->string('company');
            $table->string('dept_code')->nullable();
            $table->string('employee_code');
            $table->date('work_date');
            $table->string('approval_status')->default('draft');
            $table->string('attendance_status')->nullable();
            $table->string('export_status')->nullable();
            $table->dateTime('submitted_at')->nullable();
            $table->dateTime('exported_at')->nullable();
            $table->timestamps();
        });

        /* ต้องประกาศคอลัมน์ให้ครบเท่าตารางจริง แม้เทสต์นี้ใช้ไม่กี่ช่อง
           เพราะ Eloquent แคชรายชื่อคอลัมน์ของโมเดลไว้เป็น static (guardableColumns)
           ถ้าตารางในเทสต์นี้ขาดคอลัมน์ เทสต์คลาสอื่นที่รันทีหลังจะถูกตัดค่าที่ใส่มาเงียบ ๆ */
        Schema::connection('mysql_ot_approval')->create('leave_requests', function ($table) {
            $table->id();
            $table->uuid('batch_uuid')->nullable();
            $table->string('company');
            $table->string('dept_code')->nullable();
            $table->string('employee_name')->nullable();
            $table->string('position_name')->nullable();
            $table->string('department_name')->nullable();
            $table->string('employee_code');
            $table->date('leave_date');
            $table->date('range_start')->nullable();
            $table->date('range_end')->nullable();
            $table->string('leave_type')->nullable();
            $table->string('bplus_stamp_type_key')->nullable();
            $table->string('deduction_agreement_code')->nullable();
            $table->string('shift_code')->nullable();
            $table->string('swipe_character_code')->nullable();
            $table->string('approval_method')->nullable();
            $table->decimal('leave_quantity', 8, 2)->nullable();
            $table->text('note')->nullable();
            $table->string('approval_status')->default('draft');
            $table->string('export_status')->nullable();
            $table->unsignedBigInteger('created_by_app_user_id')->nullable();
            $table->string('created_by_employee_code')->nullable();
            $table->unsignedBigInteger('decided_by_app_user_id')->nullable();
            $table->string('decided_by_employee_code')->nullable();
            $table->text('decision_note')->nullable();
            $table->dateTime('submitted_at')->nullable();
            $table->dateTime('decided_at')->nullable();
            $table->dateTime('exported_at')->nullable();
            $table->timestamps();
        });

        Schema::connection('mysql_ot_approval')->create('ot_export_downloads', function ($table) {
            $table->id();
            $table->string('module');
            $table->string('scope');
            $table->date('target_date')->nullable();
            $table->date('submitted_on')->nullable();
            $table->string('cycle_key')->nullable();
            $table->unsignedInteger('row_count')->default(0);
            $table->unsignedBigInteger('downloaded_by_app_user_id')->nullable();
            $table->string('downloaded_by_employee_code')->nullable();
            $table->dateTime('downloaded_at');
            $table->timestamps();
        });
    }

    public function test_requests_filed_up_to_the_leave_date_become_one_document(): void
    {
        // ยื่น 3 วันคนละรอบ แต่ทั้งหมดยังไม่เกินวันลา จึงต้องเป็นไฟล์เดียว
        $this->makeLeave(['employee_code' => 'T0001', 'submitted_at' => '2026-08-17 09:00:00']);
        $this->makeLeave(['employee_code' => 'T0002', 'submitted_at' => '2026-08-19 09:00:00']);
        $this->makeLeave(['employee_code' => 'T0003', 'submitted_at' => '2026-08-20 09:00:00']);

        $documents = $this->documentsFor(self::LEAVE_DATE);

        $this->assertCount(1, $documents, 'คำขอที่ยื่นถึงวันลาต้องรวมเป็นเอกสารฉบับเดียว');
        $this->assertTrue($documents[0]['is_main']);
        $this->assertFalse($documents[0]['is_other_date'], 'ไฟล์รวมไม่ใช่การยื่นย้อนหลัง');
        $this->assertSame(3, $documents[0]['passed']);
        $this->assertSame(self::LEAVE_DATE, $documents[0]['date'], 'ไฟล์รวมกำกับด้วยวันลา ไม่ใช่วันที่ยื่น');
        $this->assertSame(
            ['scope' => 'date', 'date' => self::LEAVE_DATE, 'submitted_until' => self::LEAVE_DATE],
            $documents[0]['params'],
            'ปุ่มโหลดต้องขอทั้งช่วงที่ยื่นถึงวันลา',
        );
    }

    public function test_requests_filed_after_the_leave_date_split_into_daily_documents(): void
    {
        $this->makeLeave(['employee_code' => 'T0001', 'submitted_at' => '2026-08-18 09:00:00']);
        $this->makeLeave(['employee_code' => 'T0002', 'submitted_at' => '2026-08-21 09:00:00']);
        $this->makeLeave(['employee_code' => 'T0003', 'submitted_at' => '2026-08-22 09:00:00']);

        $documents = $this->documentsFor(self::LEAVE_DATE);

        $this->assertCount(3, $documents, 'ไฟล์รวม 1 ฉบับ + ย้อนหลังวันละฉบับ');
        $this->assertTrue($documents[0]['is_main'], 'ไฟล์รวมต้องอยู่บนสุด');
        $this->assertSame(['2026-08-21', '2026-08-22'], [$documents[1]['date'], $documents[2]['date']]);
        $this->assertTrue($documents[1]['is_other_date']);
        $this->assertSame(
            ['scope' => 'date', 'date' => self::LEAVE_DATE, 'submitted_on' => '2026-08-21'],
            $documents[1]['params'],
            'รอบย้อนหลังต้องโหลดเฉพาะวันที่ยื่นวันนั้น กัน Bplus บวกซ้ำ',
        );
    }

    public function test_merged_document_turns_red_again_when_a_new_request_arrives_after_download(): void
    {
        $this->makeLeave([
            'employee_code' => 'T0001',
            'submitted_at' => '2026-08-17 09:00:00',
            'export_status' => LeaveRequest::EXPORT_EXPORTED,
            'exported_at' => '2026-08-17 15:00:00',
        ]);

        OtExportDownload::create([
            'module' => OtExportDownload::MODULE_LEAVE,
            'scope' => 'date',
            'target_date' => self::LEAVE_DATE,
            'submitted_on' => self::LEAVE_DATE,
            'row_count' => 1,
            'downloaded_at' => '2026-08-17 15:00:00',
        ]);

        $downloaded = $this->documentsFor(self::LEAVE_DATE);
        $this->assertFalse($downloaded[0]['needs_download'], 'โหลดครบแล้วต้องไม่ค้างจุดแดง');
        $this->assertSame(1, $downloaded[0]['download_count'], 'ต้องจับคู่ log ของไฟล์รวมได้');

        // Foreman ยื่นเพิ่มวันรุ่งขึ้น ก่อนถึงวันลา — ไฟล์เดิมที่ HR ถืออยู่ยังไม่มีคนนี้
        $this->makeLeave(['employee_code' => 'T0002', 'submitted_at' => '2026-08-18 09:00:00']);

        $updated = $this->documentsFor(self::LEAVE_DATE);
        $this->assertCount(1, $updated, 'ยังเป็นเอกสารฉบับเดียว ไม่แตกไฟล์ใหม่');
        $this->assertTrue($updated[0]['needs_download'], 'มีของใหม่หลังโหลดต้องกลับมาแดง');
        $this->assertSame(1, $updated[0]['ready']);
        $this->assertSame(1, $this->dayFor(self::LEAVE_DATE)['leave']['pending_files'], 'ตัวเลขบนปฏิทินต้องตรงกับปุ่มที่ยังแดง');
    }

    /** @return array<int, array<string, mixed>> */
    private function documentsFor(string $date): array
    {
        return app(OtDownloadCalendarService::class)->day(
            CarbonImmutable::createFromFormat('Y-m-d', $date)->startOfDay(),
            ['all' => true, 'departments' => [], 'employees' => []],
        )['leave']['documents'];
    }

    /** @return array<string, mixed> */
    private function dayFor(string $date): array
    {
        $payload = app(OtDownloadCalendarService::class)->month(
            CarbonImmutable::createFromFormat('Y-m-d', $date)->startOfMonth(),
            ['all' => true, 'departments' => [], 'employees' => []],
        );

        return collect($payload['days'])->firstWhere('date', $date);
    }

    /** @param array<string, mixed> $overrides */
    private function makeLeave(array $overrides = []): LeaveRequest
    {
        return LeaveRequest::create(array_merge([
            'company' => 'MOLDVANTO',
            'dept_code' => 'LVT001',
            'department_name' => 'ROVENTO/AME',
            'employee_code' => 'T0001',
            'leave_date' => self::LEAVE_DATE,
            'leave_type' => 'section_75',
            'approval_status' => LeaveRequest::APPROVAL_APPROVED,
            'export_status' => LeaveRequest::EXPORT_READY,
        ], $overrides));
    }
}
