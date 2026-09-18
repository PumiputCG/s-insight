<?php

namespace App\Http\Controllers\Insight;

use App\Http\Controllers\Controller;
use App\Services\Insight\PortalNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * กระดิ่งแจ้งเตือนกลางของ portal — รวมทุกระบบย่อย (Manager สั่ง 2026-08-27)
 * ผู้ใช้เห็นเฉพาะของตัวเองเสมอ เพราะทุก query scope ด้วย employee_code ของ session
 */
class PortalNotificationController extends Controller
{
    public function __construct(private readonly PortalNotificationService $notifications) {}

    /** รายชื่อแอป + ตัวเลขของแต่ละแอป (ใช้กับ dropdown ชั้นแรก) */
    public function apps(): JsonResponse
    {
        $apps = $this->notifications->apps($this->myCode());

        return response()->json([
            'ok' => true,
            'total_unread' => collect($apps)->sum('unread'),
            'apps' => $apps,
        ]);
    }

    /** รายการแจ้งเตือนของแอปที่เลือก (ชั้นที่สอง) */
    public function items(Request $request): JsonResponse
    {
        $data = $request->validate([
            'app' => ['required', 'string', 'max:40'],
            'category' => ['nullable', 'string', 'max:40'],
        ]);

        $code = $this->myCode();
        $apps = collect($this->notifications->apps($code));

        return response()->json([
            'ok' => true,
            'items' => $this->notifications->items($code, $data['app'], $data['category'] ?? null),
            'unread' => (int) ($apps->firstWhere('key', $data['app'])['unread'] ?? 0),
            'total_unread' => $apps->sum('unread'),
        ]);
    }

    public function read(Request $request): JsonResponse
    {
        $data = $request->validate([
            'app' => ['required', 'string', 'max:40'],
            'id' => ['required', 'integer', 'min:1'],
        ]);

        $this->notifications->markRead($this->myCode(), $data['app'], (int) $data['id']);

        return response()->json(['ok' => true, 'total_unread' => $this->notifications->totalUnread($this->myCode())]);
    }

    public function readAll(Request $request): JsonResponse
    {
        $data = $request->validate([
            'app' => ['required', 'string', 'max:40'],
            'category' => ['nullable', 'string', 'max:40'],
        ]);

        $code = $this->myCode();
        $marked = $this->notifications->markAllRead($code, $data['app'], $data['category'] ?? null);

        return response()->json([
            'ok' => true,
            'marked' => $marked,
            'total_unread' => $this->notifications->totalUnread($code),
        ]);
    }

    private function myCode(): string
    {
        $me = app()->bound('current_user') ? app('current_user') : null;

        return trim((string) ($me->employee_code ?? ''));
    }
}
