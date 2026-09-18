<?php

namespace App\Http\Controllers\OtApproval;

use App\Http\Controllers\Controller;
use App\Http\Controllers\OtApproval\Concerns\HandlesOtApprovalAccess;
use App\Http\Requests\OtApproval\DownloadLeave75Request;
use App\Services\OtApproval\ExportDownloadRecorder;
use App\Services\OtApproval\Leave75ExportService;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class Leave75ExportController extends Controller
{
  use HandlesOtApprovalAccess;

  public function download(
    DownloadLeave75Request $request,
    Leave75ExportService $exporter,
    ExportDownloadRecorder $recorder,
  ): BinaryFileResponse|RedirectResponse|JsonResponse {
    abort_unless($this->isOtAdmin(), 403);
    $scope = (string) $request->validated('scope');
    $dateValue = $request->validated('date');
    $date = $scope === 'date' && is_string($dateValue)
      ? CarbonImmutable::createFromFormat('Y-m-d', $dateValue)->startOfDay()
      : null;

    $submittedValue = $request->validated('submitted_on');
    $submittedOn = is_string($submittedValue)
      ? CarbonImmutable::createFromFormat('Y-m-d', $submittedValue)->startOfDay()
      : null;

    /* ไฟล์รวมของวันลา — รวมคำขอที่ยื่นถึงวันลาไว้ฉบับเดียวตามที่ Manager กำหนด
       log บันทึก submitted_on = วันสุดท้ายของขอบเขต เพื่อให้หน้าปฏิทินจับคู่ไฟล์ได้ถูกฉบับ */
    $untilValue = $request->validated('submitted_until');
    $submittedUntil = is_string($untilValue)
      ? CarbonImmutable::createFromFormat('Y-m-d', $untilValue)->startOfDay()
      : null;

    try {
      $export = $exporter->export($scope, $date, $request->validated('cycle'), $submittedOn, $submittedUntil);
    } catch (RuntimeException $exception) {
      return $this->failed($request, $dateValue, $exception->getMessage());
    } catch (Throwable $exception) {
      report($exception);
      return $this->failed($request, $dateValue, 'ไม่สามารถสร้างเอกสารลาได้ในขณะนี้ กรุณาลองใหม่อีกครั้ง');
    }

    /* บันทึกหลังสร้างไฟล์สำเร็จเท่านั้น เพื่อให้หน้าปฏิทินรู้ว่าวันนี้ส่งให้ HR ไปแล้ว
       และนับจำนวนครั้งที่โหลดซ้ำได้ */
    $recorder->recordLeave(
      $export['ids'] ?? [],
      $scope,
      $date,
      $request->validated('cycle'),
      $this->me(),
      $submittedUntil ?: $submittedOn,
    );

    return response()->download(
      $export['path'],
      $export['filename'],
      ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
    )->deleteFileAfterSend(true);
  }

  /**
   * หน้าดาวน์โหลดเรียกผ่าน fetch เพื่อขึ้นกล่องแจ้งผล จึงต้องได้ข้อความจริงกลับไปเป็น JSON
   * ส่วนลิงก์ปกติในหน้าภาพรวมยังเด้งกลับพร้อม toast เหมือนเดิม
   */
  private function failed(
    DownloadLeave75Request $request,
    mixed $date,
    string $message,
  ): RedirectResponse|JsonResponse {
    if ($request->expectsJson()) {
      return response()->json(['ok' => false, 'message' => $message], 422);
    }

    return redirect()->route(
      'ot-approval.leave-overview',
      is_string($date) ? ['date' => $date] : [],
    )->with('toast_fallback', $message)->with('toast_type', 'error');
  }
}
