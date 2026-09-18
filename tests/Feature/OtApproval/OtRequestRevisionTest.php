<?php

namespace Tests\Feature\OtApproval;

use App\Models\OtApproval\OtRequest;
use App\Services\OtApproval\OtDownloadCalendarService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * ขอ OT ซ้ำวันเดิม = แก้ของเดิม ไม่ใช่เพิ่มแถวใหม่
 *
 * Bplus บวกชั่วโมงสะสมเมื่อ import พนักงานซ้ำวันเดียวกัน ถ้าระบบสร้างสองแถว
 * (2 ชม. + 4 ชม.) พนักงานจะได้ 6 ชม. ทั้งที่หัวหน้าตั้งใจแก้เป็น 4
 *
 * เทสต์นี้ยืนยันฝั่งปฏิทินดาวน์โหลดว่าวันที่เคยส่งไฟล์ให้ HR แล้วแต่มีรายการ
 * ถูกแก้ทีหลัง ต้องขึ้นสถานะ `restale` เพื่อเตือน admin ให้โหลดไฟล์ใหม่ทับ
 */
class OtRequestRevisionTest extends TestCase
{
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
            $table->integer('requested_hours')->default(0);
            $table->integer('requested_minutes')->default(0);
            $table->string('approval_status')->default('draft');
            $table->string('attendance_status')->nullable();
            $table->string('export_status')->nullable();
            $table->dateTime('submitted_at')->nullable();
            $table->dateTime('exported_at')->nullable();
            $table->timestamps();
        });

        // ปฏิทินดาวน์โหลดรวม OT + การลา + ประวัติการโหลดไว้ในวันเดียวกันแล้ว
        Schema::connection('mysql_ot_approval')->create('leave_requests', function ($table) {
            $table->id();
            $table->string('company');
            $table->string('dept_code')->nullable();
            $table->string('employee_code');
            $table->date('leave_date');
            $table->string('leave_type')->nullable();
            $table->string('approval_status')->default('draft');
            $table->string('export_status')->nullable();
            $table->dateTime('submitted_at')->nullable();
            $table->dateTime('exported_at')->nullable();
            $table->timestamps();
        });

        Schema::connection('mysql_ot_approval')->create('ot_export_downloads', function ($table) {
            $table->id();
            $table->string('module');
            $table->string('scope');
            $table->date('target_date')->nullable();
            $table->string('cycle_key')->nullable();
            $table->unsignedInteger('row_count')->default(0);
            $table->unsignedBigInteger('downloaded_by_app_user_id')->nullable();
            $table->string('downloaded_by_employee_code')->nullable();
            $table->dateTime('downloaded_at');
            $table->timestamps();
        });
    }

    public function test_day_needs_redownload_when_an_exported_request_is_revised(): void
    {
        // เคยโหลดส่ง HR ไปแล้ว จากนั้นถูกแก้ชั่วโมงจึงกลับมารออนุมัติใหม่
        $this->makeRequest([
            'export_status' => OtRequest::EXPORT_NOT_READY,
            'exported_at' => now()->subDay(),
            'approval_status' => OtRequest::APPROVAL_SUBMITTED,
            'attendance_status' => OtRequest::ATTENDANCE_PASSED,
        ]);
        // อีกรายการของวันเดียวกันที่อนุมัติแล้วและยังไม่ได้โหลด
        $this->makeRequest([
            'employee_code' => 'TEST0002',
            'export_status' => OtRequest::EXPORT_READY,
            'approval_status' => OtRequest::APPROVAL_APPROVED,
            'attendance_status' => OtRequest::ATTENDANCE_PASSED,
        ]);

        $day = $this->dayFor('2026-08-13');

        $this->assertSame(OtDownloadCalendarService::STATUS_RESTALE, $day['status'], 'วันที่เคยส่งไฟล์แล้วมีของค้างต้องเตือนให้โหลดซ้ำ');
        $this->assertSame(1, $day['ot']['ready'], 'รายการที่ยังไม่ได้โหลดต้องเหลือค้างให้เห็น');
        $this->assertSame(2, $day['ot']['total'], 'ต้องนับทั้งของเดิมที่ถูกแก้และของใหม่');
    }

    public function test_day_is_done_when_everything_was_exported(): void
    {
        $this->makeRequest([
            'export_status' => OtRequest::EXPORT_EXPORTED,
            'exported_at' => now()->subHour(),
            'approval_status' => OtRequest::APPROVAL_APPROVED,
            'attendance_status' => OtRequest::ATTENDANCE_PASSED,
        ]);

        $this->assertSame(OtDownloadCalendarService::STATUS_DONE, $this->dayFor('2026-08-13')['status']);
    }

    /**
     * ช่องปฏิทิน = วันที่ทำ OT ส่วนวันที่ยื่นเป็นแถวในโมดัล
     *
     * ทำ OT วันที่ 13 แต่ยื่นวันที่ 17 ต้องอยู่ในช่องวันที่ 13 (วันของเอกสารที่ HR import)
     * และแตกเป็นแถวของวันที่ยื่น เพื่อให้แต่ละรอบที่ยื่นเป็นคนละไฟล์
     * ถ้ารวมเป็นไฟล์เดียว คนที่ HR import ไปแล้วจะถูกบวกชั่วโมงซ้ำใน Bplus
     */
    public function test_backdated_request_shows_under_its_work_date_grouped_by_submit_day(): void
    {
        $this->makeRequest([
            'work_date' => '2026-08-13',
            'submitted_at' => '2026-08-17 09:00:00',
            'approval_status' => OtRequest::APPROVAL_APPROVED,
            'attendance_status' => OtRequest::ATTENDANCE_PASSED,
        ]);

        $this->assertSame(1, $this->dayFor('2026-08-13')['ot']['total'], 'ต้องอยู่ช่องวันที่ทำ OT');
        $this->assertSame(0, $this->dayFor('2026-08-17')['ot']['total'], 'ช่องวันที่ยื่นต้องไม่นับซ้ำ');

        $documents = app(OtDownloadCalendarService::class)->day(
            \Carbon\CarbonImmutable::createFromFormat('Y-m-d', '2026-08-13')->startOfDay(),
            ['all' => true, 'departments' => [], 'employees' => []],
        )['ot']['documents'];

        $this->assertCount(1, $documents);
        $this->assertSame('2026-08-17', $documents[0]['date'], 'แถวต้องกำกับด้วยวันที่ยื่นคำขอ');
        $this->assertTrue($documents[0]['is_other_date'], 'ยื่นคนละวันกับวันทำ OT ต้องขึ้นป้ายย้อนหลัง');
    }

    /** @return array<string, mixed> */
    private function dayFor(string $date): array
    {
        $payload = app(OtDownloadCalendarService::class)->month(
            \Carbon\CarbonImmutable::createFromFormat('Y-m-d', $date)->startOfMonth(),
            ['all' => true, 'departments' => [], 'employees' => []],
        );

        return collect($payload['days'])->firstWhere('date', $date);
    }

    /** @param array<string, mixed> $overrides */
    private function makeRequest(array $overrides = []): OtRequest
    {
        return OtRequest::create(array_merge([
            'company' => 'SUPAVUT_INDUSTRY',
            'dept_code' => 'SPV010',
            'employee_code' => 'TEST0001',
            'work_date' => '2026-08-13',
            'requested_hours' => 2,
            'requested_minutes' => 0,
            'approval_status' => OtRequest::APPROVAL_SUBMITTED,
            'attendance_status' => OtRequest::ATTENDANCE_PASSED,
            'export_status' => OtRequest::EXPORT_NOT_READY,
            'submitted_at' => '2026-08-13 18:00:00',
        ], $overrides));
    }
}
