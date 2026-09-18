<?php

use App\Http\Controllers\Area5s\Area5sCompanyPlanController;
use App\Http\Controllers\Area5s\Area5sController;
use App\Http\Controllers\Area5s\Area5sDownloadController;
use App\Http\Controllers\Area5s\Area5sLayoutController;
use App\Http\Controllers\Area5s\Area5sResponsibleController;
use App\Http\Controllers\Area5s\Area5sReviewController;
use App\Http\Controllers\Area5s\Area5sRoundController;
use App\Http\Controllers\Area5s\Area5sSettingController;
use Illuminate\Support\Facades\Route;

/*
| โมดูล SUPAVUT 5S AREA — อยู่ในกลุ่ม middleware insight.auth (ครอบจาก routes/web.php)
| สิทธิ์จริงคุมใน controller: admin (Insight admin หรือ a5s role admin) / allocator / evaluator /
| ผู้รับผิดชอบพื้นที่ / ตำแหน่งที่อนุญาต — ดู AREA5S_SYSTEM.md
*/
Route::prefix('area5s')->name('area5s.')->group(function () {
    // หน้าหลัก (info 5ส) — ทุก user เห็นได้เสมอ แม้ยังไม่เปิดรอบ (นอก guard `area5s.round`)
    Route::get('/home', [Area5sController::class, 'home'])->name('home');

    // รอบรายเดือน (เฉพาะ admin) — อยู่นอก guard `area5s.round` เพื่อให้เปิดรอบได้เสมอ
    Route::get('/rounds', [Area5sRoundController::class, 'index'])->name('rounds.index');
        Route::post('/rounds/setup', [Area5sRoundController::class, 'setup'])->name('rounds.setup');
        Route::post('/rounds/{round}/open', [Area5sRoundController::class, 'openRound'])->name('rounds.openRound');
    Route::post('/rounds/{round}/close', [Area5sRoundController::class, 'close'])->name('rounds.close');
        Route::delete('/rounds/{round}', [Area5sRoundController::class, 'destroy'])->name('rounds.destroy');

    // แปลนบริษัท -> โซน/อาคาร -> ชั้น (เฉพาะ admin) — ถาวร ไม่ผูกรอบ จึงอยู่นอก guard เช่นกัน
    Route::prefix('plan')->name('plan.')->group(function () {
        Route::get('/', [Area5sCompanyPlanController::class, 'index'])->name('index');
        Route::post('/image', [Area5sCompanyPlanController::class, 'storePlan'])->name('image');
        Route::post('/{plan}/zones', [Area5sCompanyPlanController::class, 'zoneStore'])->name('zones.store');
        Route::put('/zones/{zone}', [Area5sCompanyPlanController::class, 'zoneUpdate'])->name('zones.update');
        Route::post('/zones/{zone}/reset', [Area5sCompanyPlanController::class, 'zoneReset'])->name('zones.reset');
        Route::delete('/zones/{zone}', [Area5sCompanyPlanController::class, 'zoneDestroy'])->name('zones.destroy');
        Route::post('/zones/{zone}/floors', [Area5sCompanyPlanController::class, 'floorStore'])->name('floors.store');
        Route::delete('/floors/{floor}', [Area5sCompanyPlanController::class, 'floorDestroy'])->name('floors.destroy');
        Route::post('/zones/{zone}/maps', [Area5sCompanyPlanController::class, 'zoneMapStore'])->name('zone_maps.store');
        Route::put('/zone-maps/{zoneMap}', [Area5sCompanyPlanController::class, 'zoneMapUpdate'])->name('zone_maps.update');
        Route::delete('/zone-maps/{zoneMap}', [Area5sCompanyPlanController::class, 'zoneMapDestroy'])->name('zone_maps.destroy');
        Route::post('/zone-maps/{zoneMap}/areas', [Area5sCompanyPlanController::class, 'zoneMapAreaStore'])->name('zone_map_areas.store');
        Route::put('/zone-map-areas/{area}', [Area5sCompanyPlanController::class, 'zoneMapAreaUpdate'])->name('zone_map_areas.update');
        Route::delete('/zone-map-areas/{area}', [Area5sCompanyPlanController::class, 'zoneMapAreaDestroy'])->name('zone_map_areas.destroy');
        Route::post('/zone-map-areas/{area}/floors', [Area5sCompanyPlanController::class, 'areaFloorStore'])->name('zone_map_areas.floors.store');
        Route::get('/mapping', [Area5sCompanyPlanController::class, 'mapping'])->name('mapping');
        Route::post('/mapping', [Area5sCompanyPlanController::class, 'mappingSave'])->name('mapping.save');
    });

    // ทุกหน้าที่เหลือถูกบล็อกเมื่อไม่มีรอบเปิด (Manager สั่ง: ปิดรอบ = เข้าถึงไม่ได้เลย)
    Route::middleware('area5s.round')->group(function () {
        Route::get('/', [Area5sController::class, 'index'])->name('index');
        Route::get('/areas/{area}', [Area5sController::class, 'area'])->name('areas.show');
        Route::get('/my-work', [Area5sResponsibleController::class, 'index'])->name('responsible.index');
        Route::get('/my-work/areas/{area}/evaluations', [Area5sResponsibleController::class, 'reviewArea'])->name('evaluations.area');
        Route::get('/my-work/areas/{area}', [Area5sResponsibleController::class, 'area'])->name('responsible.area');
        Route::get('/my-work/evaluations/{layout}', [Area5sResponsibleController::class, 'evaluate'])->name('evaluations.show');
        Route::get('/my-work/layouts/{layout}', [Area5sResponsibleController::class, 'show'])->name('responsible.show');
        Route::post('/my-work/points/{point}/cards', [Area5sResponsibleController::class, 'storeCard'])->name('responsible.cards.store');
        // ส่งตรวจ / ส่งแก้ไข (D5: กดส่งซ้ำ = อัปเดต submission)
        Route::post('/my-work/points/{point}/submit', [Area5sResponsibleController::class, 'submit'])->name('responsible.submit');
        Route::put('/my-work/cards/{card}', [Area5sResponsibleController::class, 'updateCard'])->name('responsible.cards.update');
        Route::delete('/my-work/cards/{card}', [Area5sResponsibleController::class, 'destroyCard'])->name('responsible.cards.destroy');

        // จัดการพื้นที่: เลือกโซนจากแปลนบริษัทก่อน แล้วเข้า Layout รายห้อง (admin ทุกอัน / allocator ของตัวเอง — D4) + Marker editor
        Route::get('/manage', [Area5sLayoutController::class, 'manage'])->name('manage');
        Route::get('/manage/unmapped', [Area5sLayoutController::class, 'unmappedLayouts'])->name('manage.unmapped');
        Route::get('/manage/zones/{zone}/areas/{area}', [Area5sLayoutController::class, 'zoneAreaLayouts'])->name('manage.zone.area');
        Route::get('/manage/zones/{zone}', [Area5sLayoutController::class, 'zoneLayouts'])->name('manage.zone');
        Route::post('/layouts', [Area5sLayoutController::class, 'store'])->name('layouts.store');
        Route::get('/layouts/{layout}/editor', [Area5sLayoutController::class, 'editor'])->name('layouts.editor');
        Route::put('/layouts/{layout}', [Area5sLayoutController::class, 'update'])->name('layouts.update');
        Route::delete('/layouts/{layout}', [Area5sLayoutController::class, 'destroy'])->name('layouts.destroy');
        Route::post('/layouts/{layout}/image', [Area5sLayoutController::class, 'updateImage'])->name('layouts.image');
        Route::put('/layouts/{layout}/toggle', [Area5sLayoutController::class, 'toggle'])->name('layouts.toggle');
        // จุด + ผู้รับผิดชอบ
        Route::get('/employees/search', [Area5sLayoutController::class, 'employeeSearch'])->name('employees.search');
        Route::post('/layouts/{layout}/points', [Area5sLayoutController::class, 'pointStore'])->name('points.store');
        Route::put('/points/{point}', [Area5sLayoutController::class, 'pointUpdate'])->name('points.update');
        Route::delete('/points/{point}', [Area5sLayoutController::class, 'pointDestroy'])->name('points.destroy');
        Route::put('/points/{point}/toggle', [Area5sLayoutController::class, 'pointToggle'])->name('points.toggle');
        Route::post('/points/{point}/assignees', [Area5sLayoutController::class, 'assigneeAdd'])->name('assignees.add');
        Route::delete('/assignees/{assignee}', [Area5sLayoutController::class, 'assigneeRemove'])->name('assignees.remove');
        Route::post('/points/{point}/evaluators', [Area5sLayoutController::class, 'evaluatorAdd'])->name('evaluators.add');
        Route::delete('/evaluators/{scope}', [Area5sLayoutController::class, 'evaluatorRemove'])->name('evaluators.remove');
        // Viewer — คลิกจุดเห็นชื่อคนคุม (ทุกคนดูได้)
        Route::get('/layouts/{layout}', [Area5sLayoutController::class, 'show'])->name('layouts.show');

        // ตรวจประเมิน (evaluator ตามขอบเขต / admin ทุกงาน) — ผ่าน = ล็อกถาวร, ไม่ผ่าน = บังคับเหตุผล
        Route::get('/review', [Area5sReviewController::class, 'index'])->name('review.index');
        Route::get('/review/{task}', [Area5sReviewController::class, 'show'])->name('review.show');
        Route::post('/review/{task}/decide', [Area5sReviewController::class, 'decide'])->name('review.decide');

        // ดาวน์โหลดเอกสารผลตรวจ 5ส (เฉพาะ admin)
        Route::get('/downloads', [Area5sDownloadController::class, 'index'])->name('downloads.index');
        Route::get('/downloads/export', [Area5sDownloadController::class, 'export'])->name('downloads.export');
        Route::get('/downloads/export-year', [Area5sDownloadController::class, 'exportYear'])->name('downloads.export.year');

        // ตั้งค่าระบบ (เฉพาะ admin) — บทบาท admin/allocator + ตำแหน่งที่เข้าระบบได้
        Route::prefix('settings')->name('settings.')->group(function () {
            Route::get('/', [Area5sSettingController::class, 'index'])->name('index');
            Route::get('/users', [Area5sSettingController::class, 'searchUsers'])->name('users');
            Route::post('/members', [Area5sSettingController::class, 'addMember'])->name('members.add');
            Route::delete('/members/{member}', [Area5sSettingController::class, 'removeMember'])->name('members.remove');
            Route::put('/positions', [Area5sSettingController::class, 'savePositions'])->name('positions');
        });
    }); // ปิด group area5s.round
});
