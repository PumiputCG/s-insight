<?php

use App\Http\Controllers\Insight\PortalNotificationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Insight — Central HR Hub : ตัวโหลด route (loader)
|--------------------------------------------------------------------------
| route จริงแยกไฟล์ตาม 3 ระบบใน routes/web/  (Insight / Recruit / Assessment)
| Auth เป็น custom session (ดู AuthenticateEmployee) เทียบ password plaintext (D-007)
*/

// Insight (core portal): landing + login (สาธารณะ) + dashboard/logout/เปลี่ยนรหัส (จัดการ middleware เองในไฟล์)
require __DIR__.'/web/insight.php';

// โมดูลที่ต้องล็อกอินก่อน — ครอบ middleware insight.auth ให้ทุกไฟล์ในกลุ่มนี้
Route::middleware('insight.auth')->group(function () {
    // กระดิ่งแจ้งเตือนกลาง (รวมทุกระบบย่อย) — อยู่บน portal จึงต้องเรียกได้จากทุกหน้า
    Route::prefix('notifications')->name('portal.notifications.')->group(function () {
        Route::get('/apps', [PortalNotificationController::class, 'apps'])->name('apps');
        Route::get('/items', [PortalNotificationController::class, 'items'])->name('items');
        Route::post('/read', [PortalNotificationController::class, 'read'])->name('read');
        Route::post('/read-all', [PortalNotificationController::class, 'readAll'])->name('read-all');
    });

    require __DIR__.'/web/recruit.php';
    require __DIR__.'/web/assessment.php';
    require __DIR__.'/web/area5s.php';
    require __DIR__.'/web/ot_approval.php';
});
