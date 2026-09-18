<?php

namespace Tests\Feature\OtApproval;

use App\Models\OtApproval\LeaveRequest;
use App\Services\OtApproval\Leave75ExportService;
use App\Support\OtApproval\PayrollCycle;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use ReflectionMethod;
use Tests\TestCase;

class Leave75ExportTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['database.connections.mysql_ot_approval' => config('database.connections.sqlite')]);
        DB::purge('mysql_ot_approval');
        config([
            'leave_approval.export.template_path' => base_path('resources/templates/ot_approval/leave75-template.xlsx'),
            'leave_approval.export.directory' => base_path('.tmp/leave-exports'),
        ]);

        Schema::connection('mysql_ot_approval')->create('leave_requests', function ($table) {
            $table->id();
            $table->uuid('batch_uuid');
            $table->string('company');
            $table->string('dept_code')->default('');
            $table->string('employee_code');
            $table->string('employee_name')->nullable();
            $table->string('position_name')->nullable();
            $table->string('department_name')->nullable();
            $table->date('leave_date');
            $table->date('range_start');
            $table->date('range_end');
            $table->string('leave_type');
            $table->string('bplus_stamp_type_key');
            $table->string('deduction_agreement_code');
            $table->string('shift_code');
            $table->string('swipe_character_code');
            $table->string('approval_method');
            $table->decimal('leave_quantity', 8, 2);
            $table->text('note')->nullable();
            $table->string('approval_status');
            $table->string('export_status');
            $table->unsignedBigInteger('created_by_app_user_id');
            $table->string('created_by_employee_code')->nullable();
            $table->unsignedBigInteger('decided_by_app_user_id')->nullable();
            $table->string('decided_by_employee_code')->nullable();
            $table->text('decision_note')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();
        });
    }

    public function test_payroll_cycle_uses_day_21_through_day_20_and_approves_until_day_23(): void
    {
        $cycle = PayrollCycle::containing('2026-08-20');
        $this->assertSame('2026-07-21', $cycle['start']->format('Y-m-d'));
        $this->assertSame('2026-08-20', $cycle['end']->format('Y-m-d'));
        $this->assertSame('2026-08-23 23:59:59', $cycle['approval_deadline']->format('Y-m-d H:i:s'));

        $next = PayrollCycle::containing('2026-08-21');
        $this->assertSame('2026-08-21', $next['start']->format('Y-m-d'));
        $this->assertSame('2026-09-20', $next['end']->format('Y-m-d'));

        $this->assertTrue(PayrollCycle::canApprove(
            '2026-08-20',
            CarbonImmutable::parse('2026-08-23 23:59:59'),
        ));
        $this->assertFalse(PayrollCycle::canApprove(
            '2026-08-20',
            CarbonImmutable::parse('2026-08-24 00:00:00'),
        ));
        $this->assertTrue(PayrollCycle::canApprove(
            '2026-08-21',
            CarbonImmutable::parse('2026-08-24 00:00:00'),
        ));
    }

    public function test_export_mapping_and_template_keep_the_exact_bplus_leave75_columns(): void
    {
        $request = LeaveRequest::create([
            'batch_uuid' => fake()->uuid(),
            'company' => 'SUPAVUT_INDUSTRY',
            'dept_code' => 'SPV001',
            'employee_code' => '71001',
            'employee_name' => 'พนักงานทดสอบ',
            'leave_date' => '2026-08-14',
            'range_start' => '2026-08-14',
            'range_end' => '2026-08-14',
            'leave_type' => 'section_75',
            'bplus_stamp_type_key' => '20030',
            'deduction_agreement_code' => '020008(1)',
            'shift_code' => '00',
            'swipe_character_code' => '0',
            'approval_method' => '1',
            'leave_quantity' => '1',
            'approval_status' => LeaveRequest::APPROVAL_APPROVED,
            'export_status' => LeaveRequest::EXPORT_READY,
            'created_by_app_user_id' => 1,
            'created_by_employee_code' => 'FM001',
        ]);

        $mapping = new ReflectionMethod(Leave75ExportService::class, 'bplusRow');
        $mapping->setAccessible(true);
        $row = $mapping->invoke(app(Leave75ExportService::class), $request);
        $this->assertSame([
            'A' => '71001',
            'B' => '20260814',
            'C' => '00',
            'D' => '020008(1)',
            'E' => '0',
            'F' => '1',
            'G' => '1',
        ], $row);

        $book = IOFactory::load(config('leave_approval.export.template_path'));
        $sheet = $book->getSheetByName('BplusData');
        $this->assertSame(['BplusData', 'Sheet1'], $book->getSheetNames());
        $this->assertSame([
            'รหัสพนักงาน', 'วันที่ลา', 'รหัสกะ', 'รหัสผลข้อตกลงเงินหัก',
            'รหัสลักษณะการรูดบัตร', 'วิธีลา', 'จำนวนที่ลา',
        ], array_map(
            fn (string $column) => (string) $sheet->getCell($column.'1')->getValue(),
            range('A', 'G'),
        ));
        $book->disconnectWorksheets();

        $export = app(Leave75ExportService::class)->createWorkbook(collect([$request]), '20260814');

        try {
            $generatedBook = IOFactory::load($export['path']);
            $generatedSheet = $generatedBook->getSheetByName('BplusData');

            $this->assertNotNull($generatedSheet);
            $this->assertSame(['BplusData', 'Sheet1'], $generatedBook->getSheetNames());
            $this->assertSame('71001', $generatedSheet->getCell('A2')->getValue());
            $this->assertSame('20260814', $generatedSheet->getCell('B2')->getValue());
            $this->assertSame('00', $generatedSheet->getCell('C2')->getValue());
            $this->assertSame('020008(1)', $generatedSheet->getCell('D2')->getValue());
            $this->assertSame(0.0, $generatedSheet->getCell('E2')->getValue());
            $this->assertSame(1.0, $generatedSheet->getCell('F2')->getValue());
            $this->assertSame(1.0, $generatedSheet->getCell('G2')->getValue());

            foreach (range('A', 'D') as $column) {
                $this->assertSame(DataType::TYPE_STRING, $generatedSheet->getCell($column.'2')->getDataType());
            }
            foreach (range('E', 'G') as $column) {
                $this->assertSame(DataType::TYPE_NUMERIC, $generatedSheet->getCell($column.'2')->getDataType());
                $this->assertSame('@', $generatedSheet->getStyle($column.'2')->getNumberFormat()->getFormatCode());
            }

            $generatedBook->disconnectWorksheets();
        } finally {
            File::delete($export['path']);
        }
    }
}
