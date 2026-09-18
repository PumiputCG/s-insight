<?php

namespace App\Http\Controllers\OtApproval;

use App\Http\Controllers\Controller;
use App\Http\Controllers\OtApproval\Concerns\HandlesOtApprovalAccess;
use App\Models\OtApproval\OtNotification;
use App\Services\OtApproval\OtNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * กระดิ่งแจ้งเตือน OT — ผู้ใช้เห็นเฉพาะของตัวเองเสมอ (scope ด้วย employee_code)
 * ไม่ต้องมีบทบาท OT ก็เรียกได้ เพราะคนที่ไม่เกี่ยวข้องจะไม่มีรายการอยู่แล้ว
 */
class OtNotificationController extends Controller
{
    use HandlesOtApprovalAccess;

    public function index(OtNotificationService $notifications): JsonResponse
    {
        $code = $this->myEmployeeCode();

        /* แยกสองกองตั้งแต่ที่นี่ เพราะหน้าจอมีสองกล่อง (กระดิ่ง กับ กล่องเอกสารของ admin)
           ถ้าดึงรวมแล้วตัด 12 รายการ กล่องที่รายการเก่ากว่าจะว่างทั้งที่มีข้อมูลจริง */
        $bellCategories = [OtNotification::CATEGORY_OT, OtNotification::CATEGORY_LEAVE];

        return response()->json([
            'ok' => true,
            'unread' => $notifications->unreadCountFor($code, $bellCategories),
            'items' => $notifications->recentFor($code, 12, $bellCategories)
                ->map(fn (OtNotification $item) => $this->notificationPayload($item))
                ->values(),
            'download_unread' => $notifications->unreadCountFor($code, OtNotification::CATEGORY_DOWNLOAD),
            'downloads' => $notifications->recentFor($code, 12, OtNotification::CATEGORY_DOWNLOAD)
                ->map(fn (OtNotification $item) => $this->notificationPayload($item))
                ->values(),
        ]);
    }

    /** @return array<string, mixed> */
    private function notificationPayload(OtNotification $item): array
    {
        return [
            'id' => $item->id,
            'type' => $item->type,
            'category' => $item->category ?: OtNotification::CATEGORY_OT,
            'title' => $item->title,
            'body' => $item->body,
            'link' => $item->link,
            'item_count' => $item->item_count,
            'is_read' => $item->read_at !== null,
            'created_at' => $item->created_at?->toIso8601String(),
            'created_label' => $item->created_at?->format('d/m/Y H:i'),
        ];
    }

    public function read(int $notification, OtNotificationService $notifications): JsonResponse
    {
        $notifications->markRead($this->myEmployeeCode(), $notification);

        return response()->json(['ok' => true]);
    }

    /**
     * อ่านทั้งหมด — จำกัดเฉพาะหมวดที่ส่งมาได้
     *
     * กระดิ่งแจ้งเตือนกับกล่องเอกสารของ admin เป็นคนละกล่องบนหน้าจอ
     * ถ้าไม่แยกหมวด กด "อ่านทั้งหมด" ที่กล่องหนึ่งจะไปล้างของอีกกล่องด้วย
     */
    public function readAll(Request $request, OtNotificationService $notifications): JsonResponse
    {
        $data = $request->validate([
            'category' => ['nullable', 'string', 'in:ot,leave,download'],
        ]);

        return response()->json([
            'ok' => true,
            'marked' => $notifications->markAllRead($this->myEmployeeCode(), $data['category'] ?? null),
        ]);
    }

    private function myEmployeeCode(): string
    {
        return trim((string) ($this->me()->employee_code ?? ''));
    }
}
