<?php

namespace App\Services\OtApproval;

use App\Models\Insight\AppUser;
use App\Models\OtApproval\LeaveRequest;
use App\Models\OtApproval\OtExportDownload;
use App\Models\OtApproval\OtNotification;
use App\Models\OtApproval\OtRequest;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * บันทึกว่า admin กดดาวน์โหลดเอกสารอะไรไปแล้วบ้าง
 *
 * ทำ 2 อย่างในทรานแซกชันเดียว
 *   1. ปั๊ม `exported_at` + `export_status` ลงแถวที่อยู่ในไฟล์ เพื่อให้หน้าปฏิทินรู้ว่าวันนั้นโหลดไปแล้ว
 *   2. เก็บ log ว่าใครโหลด เมื่อไหร่ กี่แถว เพื่อบอกจำนวนครั้งที่โหลดซ้ำ
 *
 * ต้องเรียกหลังสร้างไฟล์สำเร็จเท่านั้น ไม่งั้นจะขึ้นว่าโหลดแล้วทั้งที่ไฟล์ยังไม่ถึงมือ HR
 */
class ExportDownloadRecorder
{
    /** @param array<int, int> $requestIds */
    public function recordOt(
        array $requestIds,
        string $scope,
        ?CarbonImmutable $date,
        ?string $cycleKey,
        ?AppUser $actor,
        ?CarbonImmutable $submittedOn = null,
    ): void {
        $this->record(OtExportDownload::MODULE_OT, $requestIds, $scope, $date, $cycleKey, $actor, $submittedOn, function (array $ids) {
            OtRequest::query()->whereIn('id', $ids)->update([
                'export_status' => OtRequest::EXPORT_EXPORTED,
                'exported_at' => now(),
            ]);
        });
    }

    /** @param array<int, int> $requestIds */
    public function recordLeave(
        array $requestIds,
        string $scope,
        ?CarbonImmutable $date,
        ?string $cycleKey,
        ?AppUser $actor,
        ?CarbonImmutable $submittedOn = null,
    ): void {
        $this->record(OtExportDownload::MODULE_LEAVE, $requestIds, $scope, $date, $cycleKey, $actor, $submittedOn, function (array $ids) {
            LeaveRequest::query()->whereIn('id', $ids)->update([
                'export_status' => LeaveRequest::EXPORT_EXPORTED,
                'exported_at' => now(),
            ]);
        });
    }

    /**
     * ปิดแจ้งเตือนหมวดดาวน์โหลดของเดือนที่เพิ่งโหลดไป
     *
     * แจ้งเตือนถูกสร้างตอนคำขอพร้อมส่ง โดยลิงก์ชี้ไปหน้าดาวน์โหลดพร้อม ?month=
     * จึงจับคู่ด้วยเดือนในลิงก์ ไม่ต้องเก็บ id ของคำขอไว้ในแจ้งเตือน
     */
    private function clearDownloadNotifications(?CarbonImmutable $date): void
    {
        if (! $date) {
            return;
        }

        OtNotification::query()
            ->where('category', OtNotification::CATEGORY_DOWNLOAD)
            ->whereNull('read_at')
            ->where('link', 'like', '%month='.$date->format('Y-m').'%')
            ->update(['read_at' => now()]);
    }

    /**
     * @param  array<int, int>  $requestIds
     * @param  callable(array<int, int>): void  $markRows
     */
    private function record(
        string $module,
        array $requestIds,
        string $scope,
        ?CarbonImmutable $date,
        ?string $cycleKey,
        ?AppUser $actor,
        ?CarbonImmutable $submittedOn,
        callable $markRows,
    ): void {
        $ids = array_values(array_unique(array_map('intval', $requestIds)));
        if ($ids === []) {
            return;
        }

        DB::connection('mysql_ot_approval')->transaction(function () use ($module, $ids, $scope, $date, $cycleKey, $actor, $submittedOn, $markRows) {
            $markRows($ids);

            /* โหลดไฟล์ของเดือนนี้แล้ว = งานหมวดดาวน์โหลดของเดือนนั้นถือว่ารับทราบ
               ให้กระดิ่งดาวน์โหลดข้างบนเคลียร์ตามไปด้วย จะได้ไม่ค้างเป็นเลขแดงทั้งที่โหลดไปแล้ว */
            $this->clearDownloadNotifications($date ?? $submittedOn);

            OtExportDownload::create([
                'module' => $module,
                'scope' => $scope,
                'target_date' => $scope === 'date' ? $date?->toDateString() : null,
                'submitted_on' => $submittedOn?->toDateString(),
                'cycle_key' => $scope === 'cycle' ? $cycleKey : null,
                'row_count' => count($ids),
                'downloaded_by_app_user_id' => $actor?->id,
                'downloaded_by_employee_code' => $actor?->employee_code,
                'downloaded_at' => now(),
            ]);
        });
    }
}
