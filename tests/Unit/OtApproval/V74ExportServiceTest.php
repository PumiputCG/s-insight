<?php

namespace Tests\Unit\OtApproval;

use App\Http\Requests\OtApproval\DownloadV74Request;
use App\Models\OtApproval\OtRequest;
use App\Services\OtApproval\V74ExportService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;

class V74ExportServiceTest extends TestCase
{
    public function test_download_contract_accepts_only_a_date_or_payroll_cycle(): void
    {
        $allRequest = DownloadV74Request::create('/ot-approval/exports/v74', 'GET', ['scope' => 'all']);
        $this->assertTrue(Validator::make($allRequest->all(), $allRequest->rules())->fails());

        $dateRequest = DownloadV74Request::create('/ot-approval/exports/v74', 'GET', [
            'scope' => 'date',
            'date' => '2026-08-13',
        ]);
        $this->assertFalse(Validator::make($dateRequest->all(), $dateRequest->rules())->fails());

        $cycleRequest = DownloadV74Request::create('/ot-approval/exports/v74', 'GET', [
            'scope' => 'cycle',
            'cycle' => '2026-08',
        ]);
        $this->assertFalse(Validator::make($cycleRequest->all(), $cycleRequest->rules())->fails());
    }

    public function test_it_fills_passed_requests_into_the_original_v74_template(): void
    {
        $requests = collect([
            $this->request('000123', '2026-08-08', 2, 0),
            $this->request('000456', '2026-08-09', 1, 30),
        ]);

        $export = (new V74ExportService)->createWorkbook(
            $requests,
            'all',
            CarbonImmutable::parse('2026-08-09'),
        );

        try {
            $spreadsheet = IOFactory::load($export['path']);
            $sheet = $spreadsheet->getSheetByName('BplusData');

            $this->assertNotNull($sheet);
            $this->assertSame('วันที่ได้อนุมติ', $sheet->getCell('B1')->getValue());
            $this->assertSame('000123', $sheet->getCell('A2')->getValue());
            $this->assertSame('20260808', $sheet->getCell('B2')->getValue());
            $this->assertSame('00', $sheet->getCell('C2')->getValue());
            $this->assertSame('10101', $sheet->getCell('D2')->getValue());
            $this->assertSame('0', $sheet->getCell('E2')->getValue());
            $this->assertSame('1', $sheet->getCell('F2')->getValue());
            $this->assertSame('2', $sheet->getCell('G2')->getValue());
            $this->assertSame('1.5', $sheet->getCell('G3')->getValue());
            $this->assertSame('@', $sheet->getStyle('A3')->getNumberFormat()->getFormatCode());
            $this->assertSame('FFFFFF00', $sheet->getStyle('C3')->getFill()->getStartColor()->getARGB());
            $this->assertSame('FFFFFF00', $sheet->getStyle('F3')->getFill()->getStartColor()->getARGB());
            $this->assertNotSame('', trim((string) $sheet->getCell('K2')->getValue()));
            $this->assertSame(2, $export['count']);

            $spreadsheet->disconnectWorksheets();
        } finally {
            File::delete($export['path']);
        }
    }

    #[DataProvider('confirmedAgreementCodeProvider')]
    public function test_it_maps_every_confirmed_bplus_agreement_code(
        string $company,
        string $otType,
        string $expectedCode,
    ): void {
        $export = (new V74ExportService)->createWorkbook(
            collect([$this->request('000123', '2026-08-09', 2, 0, $otType, $company)]),
            'date',
            CarbonImmutable::parse('2026-08-09'),
        );

        try {
            $spreadsheet = IOFactory::load($export['path']);
            $sheet = $spreadsheet->getSheetByName('BplusData');

            $this->assertNotNull($sheet);
            $this->assertSame($expectedCode, $sheet->getCell('D2')->getValue());
            $this->assertSame('0', $sheet->getCell('E2')->getValue());
            $this->assertSame('2', $sheet->getCell('G2')->getValue());

            $spreadsheet->disconnectWorksheets();
        } finally {
            File::delete($export['path']);
        }
    }

    public static function confirmedAgreementCodeProvider(): array
    {
        return [
            'after work x1.5' => ['SUPAVUT_INDUSTRY', 'weekday_after_work', '10101'],
            'before work x1.5' => ['SUPAVUT_INDUSTRY', 'weekday_before_work', '10102'],
            'monthly holiday x1' => ['SUPAVUT_INDUSTRY', 'holiday_regular', '10103'],
            'daily public holiday in Supavut' => ['SUPAVUT_INDUSTRY', 'holiday_public_daily', '10103-1'],
            'daily public holiday in Moldvanto' => ['MOLDVANTO', 'holiday_public_daily', '10103-1'],
            'daily holiday x2' => ['SUPAVUT_INDUSTRY', 'holiday_ot', '10104'],
            'before holiday work x3' => ['SUPAVUT_INDUSTRY', 'holiday_before_work', '10105'],
            'after holiday work x3' => ['SUPAVUT_INDUSTRY', 'holiday_after_work', '10106'],
        ];
    }

    public function test_it_blocks_an_unconfirmed_innomed_public_holiday_code(): void
    {
        $request = $this->request(
            '000123',
            '2026-08-09',
            8,
            0,
            'holiday_public_daily',
            'SUPAVUT_INNOMED',
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('SUPAVUT_INNOMED');

        (new V74ExportService)->createWorkbook(collect([$request]), 'date');
    }

    public function test_it_refuses_the_retired_attendance_day_count_type(): void
    {
        $request = $this->request(
            '000123',
            '2026-08-09',
            2,
            0,
            'attendance_day_count',
            'SUPAVUT_INDUSTRY',
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('ยังไม่ได้กำหนดรหัสข้อตกลงเงินเพิ่ม V74');

        (new V74ExportService)->createWorkbook(collect([$request]), 'all');
    }

    public function test_employee_70125_two_hour_approved_ot_maps_to_v74(): void
    {
        $export = (new V74ExportService)->createWorkbook(
            collect([$this->request('70125', '2026-08-09', 2, 0)]),
            'date',
            CarbonImmutable::parse('2026-08-09'),
        );

        try {
            $spreadsheet = IOFactory::load($export['path']);
            $sheet = $spreadsheet->getSheetByName('BplusData');

            $this->assertNotNull($sheet);
            $this->assertSame('70125', $sheet->getCell('A2')->getValue());
            $this->assertSame('20260809', $sheet->getCell('B2')->getValue());
            $this->assertSame('00', $sheet->getCell('C2')->getValue());
            $this->assertSame('10101', $sheet->getCell('D2')->getValue());
            $this->assertSame('0', $sheet->getCell('E2')->getValue());
            $this->assertSame('1', $sheet->getCell('F2')->getValue());
            $this->assertSame('2', $sheet->getCell('G2')->getValue());

            $spreadsheet->disconnectWorksheets();
        } finally {
            File::delete($export['path']);
        }
    }

    public function test_confirmed_master_mapping_overrides_a_stale_saved_code(): void
    {
        $request = $this->request('70125', '2026-08-09', 2, 0);
        $request->agreement_code = '020017';
        $export = (new V74ExportService)->createWorkbook(collect([$request]), 'date');

        try {
            $spreadsheet = IOFactory::load($export['path']);
            $sheet = $spreadsheet->getSheetByName('BplusData');

            $this->assertNotNull($sheet);
            $this->assertSame('10101', $sheet->getCell('D2')->getValue());

            $spreadsheet->disconnectWorksheets();
        } finally {
            File::delete($export['path']);
        }
    }

    public function test_employee_69938_before_work_ot_maps_to_v74(): void
    {
        $export = (new V74ExportService)->createWorkbook(
            collect([$this->request('69938', '2026-08-09', 1, 0, 'weekday_before_work')]),
            'date',
            CarbonImmutable::parse('2026-08-09'),
        );

        try {
            $spreadsheet = IOFactory::load($export['path']);
            $sheet = $spreadsheet->getSheetByName('BplusData');

            $this->assertNotNull($sheet);
            $this->assertSame('69938', $sheet->getCell('A2')->getValue());
            $this->assertSame('10102', $sheet->getCell('D2')->getValue());
            $this->assertSame('1', $sheet->getCell('G2')->getValue());

            $spreadsheet->disconnectWorksheets();
        } finally {
            File::delete($export['path']);
        }
    }

    private function request(
        string $employeeCode,
        string $date,
        int $hours,
        int $minutes,
        string $otType = 'weekday_after_work',
        string $company = 'SUPAVUT_INDUSTRY',
    ): OtRequest {
        return new OtRequest([
            'company' => $company,
            'employee_code' => $employeeCode,
            'work_date' => $date,
            'ot_type' => $otType,
            'agreement_code' => null,
            'requested_hours' => $hours,
            'requested_minutes' => $minutes,
            'approval_status' => OtRequest::APPROVAL_APPROVED,
            'attendance_status' => OtRequest::ATTENDANCE_PASSED,
        ]);
    }
}
