<?php

namespace App\Providers;

use App\Models\Insight\AppUser;
use App\Models\Insight\Employee;
use App\Models\OtApproval\OtNotification;
use App\Observers\PrCompareObserver;
use App\Services\Insight\PortalNotificationService;
use App\Services\OtApproval\OtNotificationService;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // โหลด migration แยกตามระบบ (ดู database/migrations/{insight,recruit,assessment,area5s,ot_approval})
        // ตัว Laravel โหลด database/migrations (root = framework infra) ให้อยู่แล้ว
        $this->loadMigrationsFrom([
            database_path('migrations/insight'),
            database_path('migrations/recruit'),
            database_path('migrations/assessment'),
            database_path('migrations/area5s'),
            database_path('migrations/ot_approval'),
        ]);

        // แก้ข้อมูลพนักงานที่นี่แล้ว บอก PR Compare ให้ดึงไปอัปเดตทันที
        // ปิดได้โดยเว้น PR_COMPARE_URL ว่างใน .env (ดู config/insight.php)
        AppUser::observe(PrCompareObserver::class);
        Employee::observe(PrCompareObserver::class);

        /* กระดิ่งแจ้งเตือนกับกล่องเอกสารของ admin อยู่บน portal ทั้งคู่
           ต้องนับแยกหมวด ไม่งั้นตัวเลขของเอกสารที่รอดาวน์โหลดจะไปโผล่บนกระดิ่งด้วย */
        View::composer('layouts.portal', function ($view) {
            $me = app()->bound('current_user') ? app('current_user') : null;
            $code = trim((string) ($me->employee_code ?? ''));
            $notifications = app(OtNotificationService::class);

            $view->with([
                // ตัวเลขบนกระดิ่ง = รวมทุกระบบย่อย (Manager 2026-08-27) — เดิมนับเฉพาะ OT
                'portalUnreadCount' => $code === '' ? 0 : app(PortalNotificationService::class)->totalUnread($code),
                'otUnreadCount' => $code === '' ? 0 : $notifications->unreadCountFor($code, [
                    OtNotification::CATEGORY_OT,
                    OtNotification::CATEGORY_LEAVE,
                ]),
                'otDownloadUnreadCount' => $code === '' ? 0 : $notifications->unreadCountFor(
                    $code,
                    OtNotification::CATEGORY_DOWNLOAD,
                ),
            ]);
        });
    }
}
