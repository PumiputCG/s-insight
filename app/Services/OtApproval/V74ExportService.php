<?php

namespace App\Services\OtApproval;

use App\Models\OtApproval\OtRequest;
use App\Support\OtApproval\PayrollCycle;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use RuntimeException;

class V74ExportService
{
    /**
     * @return array{path:string,filename:string,count:int}
     */
    public function export(
        string $scope,
        ?CarbonImmutable $date = null,
        ?string $cycleMonth = null,
        ?CarbonImmutable $submittedOn = null,
    ): array {
        $query = OtRequest::query()
            ->where('approval_status', OtRequest::APPROVAL_APPROVED)
            ->where('attendance_status', OtRequest::ATTENDANCE_PASSED);

        /* แยกไฟล์ตามวันที่ยื่นคำขอ — Bplus บวกชั่วโมงสะสมเมื่อ import ซ้ำ
           ถ้ารวมทุกคนของวันทำงานเดียวกัน คนที่ HR import ไปแล้วจะได้ชั่วโมงสองเท่า */
        if ($submittedOn) {
            $sameDay = $date && $date->toDateString() === $submittedOn->toDateString();

            $query->where(function ($group) use ($submittedOn, $sameDay) {
                $group->whereDate('submitted_at', $submittedOn->toDateString());

                // แถวเก่าที่ไม่มีวันที่ยื่น ถือว่ายื่นวันเดียวกับวันทำงาน ตรงกับที่ปฏิทินจัดกลุ่มไว้
                if ($sameDay) {
                    $group->orWhereNull('submitted_at');
                }
            });
        }

        if ($scope === 'date' && $date) {
            $query->whereDate('work_date', $date->toDateString());
        } elseif ($scope === 'cycle' && $cycleMonth) {
            $cycle = PayrollCycle::endingIn($cycleMonth);
            $query->whereBetween('work_date', [
                $cycle['start']->toDateString(),
                $cycle['end']->toDateString(),
            ]);
        }

        $requests = $query
            ->orderBy('work_date')
            ->orderBy('company')
            ->orderBy('employee_code')
            ->get();

        if ($requests->isEmpty()) {
            throw new RuntimeException(
                $scope === 'date'
                    ? 'ไม่พบรายการ OT ที่ผ่านในวันที่เลือก'
                    : 'ยังไม่มีรายการ OT ที่ผ่านในรอบที่เลือก',
            );
        }

        return $this->createWorkbook($requests, $scope, $date, $cycleMonth);
    }

    /**
     * @param  Collection<int, OtRequest>  $requests
     * @return array{path:string,filename:string,count:int}
     */
    public function createWorkbook(
        Collection $requests,
        string $scope,
        ?CarbonImmutable $date = null,
        ?string $cycleMonth = null,
    ): array
    {
        $rows = $requests->map(fn (OtRequest $request) => $this->v74Row($request));
        $templatePath = (string) config('ot_approval.v74.template_path');

        if (! is_file($templatePath)) {
            throw new RuntimeException('ไม่พบไฟล์ต้นแบบ V74 กรุณาติดต่อผู้ดูแลระบบ');
        }

        $spreadsheet = IOFactory::load($templatePath);
        $sheetName = (string) config('ot_approval.v74.sheet_name', 'BplusData');
        $sheet = $spreadsheet->getSheetByName($sheetName);

        if (! $sheet) {
            $spreadsheet->disconnectWorksheets();

            throw new RuntimeException("ไม่พบชีต {$sheetName} ในไฟล์ต้นแบบ V74");
        }

        $lastExistingRow = max(2, $sheet->getHighestDataRow('G'));
        $lastOutputRow = max($lastExistingRow, $rows->count() + 1);

        // ล้างถึง H ด้วย เพราะมีคอลัมน์ `วันที่ทำรายการ` เพิ่มสำหรับ HR ตรวจ
        for ($row = 2; $row <= $lastOutputRow; $row++) {
            foreach (range('A', 'H') as $column) {
                $sheet->setCellValue("{$column}{$row}", null);
            }
        }

        /* หัวคอลัมน์ H ต้องเขียนเอง เพราะไฟล์ต้นแบบของ HR มีแค่ A–G
           วางไว้แถว 1 เหมือนหัวอื่น ๆ และไม่แตะคอลัมน์ A–G ที่ Bplus ใช้ */
        $sheet->setCellValueExplicit('H1', 'วันที่ทำรายการ', DataType::TYPE_STRING);

        foreach ($rows->values() as $offset => $values) {
            $rowNumber = $offset + 2;

            if ($rowNumber > 2) {
                $this->copyTemplateRowStyle($spreadsheet, $rowNumber);
            }

            foreach ($values as $column => $value) {
                $sheet->setCellValueExplicit(
                    "{$column}{$rowNumber}",
                    $value,
                    DataType::TYPE_STRING,
                );
            }
        }

        $directory = storage_path('app/private/ot-approval/exports');
        File::ensureDirectoryExists($directory);

        $scopeLabel = match (true) {
            $scope === 'date' && $date => $date->format('Ymd'),
            $scope === 'cycle' && $cycleMonth => 'CYCLE_'.str_replace('-', '', $cycleMonth),
            default => 'ALL',
        };
        $filename = "V74_OT_{$scopeLabel}_".now()->format('Ymd_His').'.xlsx';
        $path = $directory.DIRECTORY_SEPARATOR.Str::uuid().'.xlsx';

        IOFactory::createWriter($spreadsheet, 'Xlsx')->save($path);
        $spreadsheet->disconnectWorksheets();

        return [
            'path' => $path,
            'filename' => $filename,
            'count' => $rows->count(),
            // id ของแถวที่เข้าไฟล์จริง ใช้บันทึกว่าโหลดอะไรไปแล้วบ้าง
            'ids' => $requests->pluck('id')->all(),
        ];
    }

    /**
     * @return array<string, string>
     */
    private function v74Row(OtRequest $request): array
    {
        $type = config("ot_approval.types.{$request->ot_type}", []);
        $agreementCode = trim((string) ($type['v74_agreement_codes'][$request->company] ?? ''));

        if ($agreementCode === '') {
            throw new RuntimeException(
                'ยังไม่ได้กำหนดรหัสข้อตกลงเงินเพิ่ม V74 สำหรับประเภท '
                .$request->otTypeLabel().' ของบริษัท '.$request->company,
            );
        }

        $totalMinutes = ((int) $request->requested_hours * 60) + (int) $request->requested_minutes;
        $approvedAmount = rtrim(rtrim(number_format($totalMinutes / 60, 4, '.', ''), '0'), '.');

        return [
            'A' => trim((string) $request->employee_code),
            'B' => $request->work_date->format('Ymd'),
            'C' => (string) config('ot_approval.v74.shift_code', '00'),
            'D' => $agreementCode,
            'E' => (string) config('ot_approval.v74.swipe_character_code', '0'),
            'F' => (string) config('ot_approval.v74.approval_method', '1'),
            'G' => $approvedAmount,
            /* คอลัมน์เสริมสำหรับ HR ตรวจ ไม่ใช่ข้อมูลที่ Bplus ใช้
               บอกว่า Foreman ยื่นคำขอวันไหน เทียบกับวันที่ทำ OT (คอลัมน์ B)
               เช่น ทำ OT 13 ส.ค. แต่ยื่นย้อนหลัง 17 ส.ค. */
            'H' => $request->submitted_at?->format('d/m/Y') ?? '',
        ];
    }

    private function copyTemplateRowStyle(Spreadsheet $spreadsheet, int $targetRow): void
    {
        $sheet = $spreadsheet->getSheetByName((string) config('ot_approval.v74.sheet_name', 'BplusData'));

        foreach (range('A', 'G') as $column) {
            $sheet->duplicateStyle($sheet->getStyle("{$column}2"), "{$column}{$targetRow}");
        }

        $sheet->getRowDimension($targetRow)->setRowHeight($sheet->getRowDimension(2)->getRowHeight());
    }
}
