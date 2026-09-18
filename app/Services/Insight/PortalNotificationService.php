<?php

namespace App\Services\Insight;

use App\Models\Area5s\A5sNotification;
use App\Models\OtApproval\OtNotification;
use App\Services\OtApproval\OtNotificationService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * กระดิ่งแจ้งเตือนกลางของ portal — รวมแจ้งเตือนของทุกระบบย่อยไว้ที่เดียว
 *
 * โครง (Manager สั่ง 2026-08-27):
 *   กระดิ่ง → ตัวเลขรวมทุกแอป
 *     └─ dropdown เลือกแอป (แต่ละแอปมีตัวเลขของตัวเอง)
 *          └─ แท็บหมวดในแอปนั้น เช่น Time & Leave Approval = ทั้งหมด / OT / การลา
 *
 * เพิ่มแอปใหม่ = เพิ่มรายการใน APPS + เขียน adapter 4 เมธอด (count/recent/markRead/markAllRead)
 * ⚠️ หมวด `download` ของ OT **ไม่นับ** ในกระดิ่ง เพราะมีไอคอนดาวน์โหลดแยกของตัวเองอยู่แล้ว
 */
class PortalNotificationService
{
    public const APP_OT = 'ot-approval';

    public const APP_AREA5S = 'area5s';

    /** หมวดของ OT ที่ถือว่าเป็น "กระดิ่ง" (ตัด download ออก) */
    private const OT_BELL_CATEGORIES = [OtNotification::CATEGORY_OT, OtNotification::CATEGORY_LEAVE];

    public function __construct(private readonly OtNotificationService $otNotifications) {}

    /** รายการแอปที่มีแจ้งเตือน พร้อมตัวเลขของแต่ละแอปและแท็บหมวด */
    public function apps(string $employeeCode): array
    {
        if ($employeeCode === '') {
            return [];
        }

        return [
            [
                'key' => self::APP_OT,
                'label' => 'Time & Leave Approval',
                'label_key' => 'noti.appOt',
                'icon' => asset('assets/systems/ot-approval.png'),
                'unread' => $this->otNotifications->unreadCountFor($employeeCode, self::OT_BELL_CATEGORIES),
                'tabs' => [
                    ['key' => 'all', 'label' => 'ทั้งหมด', 'label_key' => 'noti.all'],
                    ['key' => 'ot', 'label' => 'OT', 'label_key' => null],
                    ['key' => 'leave', 'label' => 'การลา', 'label_key' => 'noti.leave'],
                ],
            ],
            [
                'key' => self::APP_AREA5S,
                'label' => 'SUPAVUT 5S AREA',
                'label_key' => 'noti.app5s',
                'icon' => asset('assets/area5s/logo-5s.png'),
                'unread' => $this->area5sQuery($employeeCode)->whereNull('read_at')->count(),
                // 5ส ยังไม่มีหมวดย่อยใน `a5s_notifications` จึงมีแท็บเดียว
                'tabs' => [
                    ['key' => 'all', 'label' => 'ทั้งหมด', 'label_key' => 'noti.all'],
                ],
            ],
        ];
    }

    /** ตัวเลขบนกระดิ่ง = รวมทุกแอป */
    public function totalUnread(string $employeeCode): int
    {
        return collect($this->apps($employeeCode))->sum('unread');
    }

    /** รายการแจ้งเตือนของแอปหนึ่ง (category = null/'all' คือทั้งหมด) */
    public function items(string $employeeCode, string $app, ?string $category = null, int $limit = 15): Collection
    {
        if ($employeeCode === '') {
            return collect();
        }

        if ($app === self::APP_OT) {
            $categories = ($category && $category !== 'all')
                ? [$category]
                : self::OT_BELL_CATEGORIES;

            // กันคนยิง category=download เข้ามาทางกระดิ่ง
            $categories = array_values(array_intersect($categories, self::OT_BELL_CATEGORIES));

            return $this->otNotifications->recentFor($employeeCode, $limit, $categories)
                ->map(fn (OtNotification $item) => [
                    'id' => (int) $item->id,
                    'category' => $item->category ?: OtNotification::CATEGORY_OT,
                    'title' => (string) $item->title,
                    'body' => (string) $item->body,
                    'link' => $item->link,
                    'item_count' => $item->item_count,
                    'is_read' => $item->read_at !== null,
                    'created_label' => $item->created_at?->format('d/m/').(((int) ($item->created_at?->year ?? 0)) + 543).' '.($item->created_at?->format('H:i') ?? ''),
                ])->values();
        }

        if ($app === self::APP_AREA5S) {
            return $this->area5sQuery($employeeCode)
                ->orderByDesc('id')
                ->limit($limit)
                ->get()
                ->map(fn (A5sNotification $item) => [
                    'id' => (int) $item->id,
                    'category' => 'all',
                    'title' => (string) $item->title,
                    'body' => (string) $item->body,
                    'link' => $item->link,
                    'item_count' => null,
                    'is_read' => $item->read_at !== null,
                    'created_label' => $item->created_at?->format('d/m/').(((int) ($item->created_at?->year ?? 0)) + 543).' '.($item->created_at?->format('H:i') ?? ''),
                ])->values();
        }

        return collect();
    }

    public function markRead(string $employeeCode, string $app, int $id): void
    {
        if ($employeeCode === '') {
            return;
        }

        if ($app === self::APP_OT) {
            $this->otNotifications->markRead($employeeCode, $id);

            return;
        }

        if ($app === self::APP_AREA5S) {
            $this->area5sQuery($employeeCode)->whereKey($id)->whereNull('read_at')->update(['read_at' => now()]);
        }
    }

    public function markAllRead(string $employeeCode, string $app, ?string $category = null): int
    {
        if ($employeeCode === '') {
            return 0;
        }

        if ($app === self::APP_OT) {
            $only = ($category && $category !== 'all' && in_array($category, self::OT_BELL_CATEGORIES, true))
                ? $category
                : null;

            if ($only !== null) {
                return $this->otNotifications->markAllRead($employeeCode, $only);
            }

            // ไม่ระบุหมวด = อ่านทั้ง OT และการลา แต่ไม่แตะกล่องดาวน์โหลด
            return collect(self::OT_BELL_CATEGORIES)
                ->sum(fn (string $one) => $this->otNotifications->markAllRead($employeeCode, $one));
        }

        if ($app === self::APP_AREA5S) {
            return $this->area5sQuery($employeeCode)->whereNull('read_at')->update(['read_at' => now()]);
        }

        return 0;
    }

    /** @return Builder<A5sNotification> */
    private function area5sQuery(string $employeeCode)
    {
        return A5sNotification::query()->where('employee_code', $employeeCode);
    }
}
