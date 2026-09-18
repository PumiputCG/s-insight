<?php

namespace App\Services\OtApproval;

use App\Models\OtApproval\LeaveRequest;
use App\Support\OtApproval\PayrollCycle;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use RuntimeException;

class Leave75ExportService
{
  /** @return array{path:string,filename:string,count:int} */
  public function export(
    string $scope,
    ?CarbonImmutable $date = null,
    ?string $cycleMonth = null,
    ?CarbonImmutable $submittedOn = null,
    ?CarbonImmutable $submittedUntil = null,
  ): array {
    $query = LeaveRequest::query()->where('approval_status', LeaveRequest::APPROVAL_APPROVED);
    $scopeLabel = '';

    /* วันลาเป็นวันอนาคตที่ประกาศล่วงหน้า Foreman ทยอยยื่นได้หลายวันก่อนถึงวันลา
       คำขอที่ยื่นถึงวันลาจึงเป็นไฟล์รวมฉบับเดียว ไม่แตกเป็นไฟล์รายวัน (Manager 2026-08-17)
       แถวเก่าที่ไม่มีวันที่ยื่นถือว่าอยู่ในไฟล์รวมด้วย จะได้ไม่ตกหล่น */
    if ($submittedUntil) {
      $query->where(function ($group) use ($submittedUntil) {
        $group->whereNull('submitted_at')
          ->orWhereDate('submitted_at', '<=', $submittedUntil->toDateString());
      });
    } elseif ($submittedOn) {
      /* ยื่นหลังวันลาไปแล้ว = ย้อนหลัง แยกไฟล์ตามวันที่ยื่นเหมือน OT
         เพราะ Bplus บวกจำนวนสะสมเมื่อ import ซ้ำ คนที่ส่งไปแล้วต้องไม่อยู่ในไฟล์รอบใหม่ */
      $query->whereDate('submitted_at', $submittedOn->toDateString());
    }

    if ($scope === 'date' && $date) {
      $query->whereDate('leave_date', $date->toDateString());
      $scopeLabel = $date->format('Ymd');
    } elseif ($scope === 'cycle' && $cycleMonth) {
      $cycle = PayrollCycle::endingIn($cycleMonth);
      $query->whereBetween('leave_date', [$cycle['start']->toDateString(), $cycle['end']->toDateString()]);
      $scopeLabel = 'CYCLE_'.$cycle['end']->format('Ym');
    } else {
      throw new RuntimeException('รูปแบบการดาวน์โหลดเอกสารลาไม่ถูกต้อง');
    }

    $requests = $query->orderBy('leave_date')->orderBy('company')->orderBy('employee_code')->get();
    if ($requests->isEmpty()) {
      throw new RuntimeException('ไม่พบรายการลาที่อนุมัติแล้วในช่วงที่เลือก');
    }

    return $this->createWorkbook($requests, $scopeLabel);
  }

  /** @param Collection<int, LeaveRequest> $requests @return array{path:string,filename:string,count:int} */
  public function createWorkbook(Collection $requests, string $scopeLabel): array
  {
    $templatePath = (string) config('leave_approval.export.template_path');
    if (! is_file($templatePath)) {
      throw new RuntimeException('ไม่พบไฟล์ต้นแบบนำเข้าการลา 75 กรุณาติดต่อผู้ดูแลระบบ');
    }

    $spreadsheet = IOFactory::load($templatePath);
    $sheetName = (string) config('leave_approval.export.sheet_name', 'BplusData');
    $sheet = $spreadsheet->getSheetByName($sheetName);
    if (! $sheet) {
      $spreadsheet->disconnectWorksheets();
      throw new RuntimeException("ไม่พบชีต {$sheetName} ในไฟล์ต้นแบบการลา 75");
    }

    $lastRow = max(2, $sheet->getHighestDataRow('G'), $requests->count() + 1);
    for ($row = 2; $row <= $lastRow; $row++) {
      foreach (range('A', 'G') as $column) {
        $sheet->setCellValue("{$column}{$row}", null);
      }
    }

    foreach ($requests->values() as $offset => $request) {
      $rowNumber = $offset + 2;
      if ($rowNumber > 2) {
        $this->copyTemplateRowStyle($spreadsheet, $rowNumber);
      }
      foreach ($this->bplusRow($request) as $column => $value) {
        $numericColumn = in_array($column, ['E', 'F', 'G'], true);
        $sheet->setCellValueExplicit(
          "{$column}{$rowNumber}",
          $numericColumn ? (float) $value : $value,
          $numericColumn ? DataType::TYPE_NUMERIC : DataType::TYPE_STRING,
        );
      }
    }

    $directory = (string) config(
      'leave_approval.export.directory',
      storage_path('app/private/ot-approval/leave-exports'),
    );
    File::ensureDirectoryExists($directory);
    $filename = "LEAVE75_{$scopeLabel}_".now()->format('Ymd_His').'.xlsx';
    $path = $directory.DIRECTORY_SEPARATOR.Str::uuid().'.xlsx';
    IOFactory::createWriter($spreadsheet, 'Xlsx')->save($path);
    $spreadsheet->disconnectWorksheets();

    // id ของแถวที่เข้าไฟล์จริง ใช้บันทึกว่าโหลดอะไรไปแล้วบ้าง
    return ['path' => $path, 'filename' => $filename, 'count' => $requests->count(), 'ids' => $requests->pluck('id')->all()];
  }

  /** @return array<string,string> */
  private function bplusRow(LeaveRequest $request): array
  {
    $agreementCode = trim((string) $request->deduction_agreement_code);
    if ($agreementCode === '') {
      throw new RuntimeException('ยังไม่ได้กำหนดรหัสผลข้อตกลงเงินหักของการลา 75');
    }

    return [
      'A' => trim((string) $request->employee_code),
      'B' => $request->leave_date->format('Ymd'),
      'C' => trim((string) $request->shift_code) ?: '00',
      'D' => $agreementCode,
      'E' => trim((string) $request->swipe_character_code) ?: '0',
      'F' => trim((string) $request->approval_method) ?: '1',
      'G' => rtrim(rtrim(number_format((float) $request->leave_quantity, 2, '.', ''), '0'), '.'),
    ];
  }

  private function copyTemplateRowStyle(Spreadsheet $spreadsheet, int $targetRow): void
  {
    $sheet = $spreadsheet->getSheetByName((string) config('leave_approval.export.sheet_name', 'BplusData'));
    foreach (range('A', 'G') as $column) {
      $sheet->duplicateStyle($sheet->getStyle("{$column}2"), "{$column}{$targetRow}");
    }
    $sheet->getRowDimension($targetRow)->setRowHeight($sheet->getRowDimension(2)->getRowHeight());
  }
}
