<?php

use App\Http\Controllers\Recruit\RecruitAdminController;
use App\Http\Controllers\Recruit\RecruitController;
use Illuminate\Support\Facades\Route;

/*
| โมดูล Recruit System (ระบบขออัตรากำลังคน) — อยู่ในกลุ่ม middleware insight.auth (ครอบจาก routes/web.php)
| การเข้าถึงจริงคุมใน controller: เฉพาะคนที่ admin เพิ่มเข้า recruit_members หรือ admin เท่านั้น
*/
Route::prefix('recruit')->name('recruit.')->group(function () {
    Route::get('/', [RecruitController::class, 'index'])->name('index');

    // แท็บแยกตาม role (กันสิทธิ์ใน controller) — โครงหน้า stub รอเฟสถัดไป
    Route::get('/departments', [RecruitController::class, 'departments'])->name('departments'); // DCC
    Route::get('/request', [RecruitController::class, 'requestForm'])->name('request');         // DCC
    Route::get('/review', [RecruitController::class, 'review'])->name('review');                // Manager/GM/HR
    Route::get('/inbox', [RecruitController::class, 'inbox'])->name('inbox');                   // Recruit
    Route::get('/downloads', [RecruitController::class, 'downloads'])->name('downloads');       // Recruit

    // ตั้งค่าระบบ Recruit (เฉพาะ admin) — เส้นทางอนุมัติ (route) → การ์ด=ขั้น + สมาชิก + แผนกที่ DCC คุม
    Route::middleware('insight.admin')->prefix('settings')->name('settings.')->group(function () {
        Route::get('/', [RecruitAdminController::class, 'index'])->name('index');
        Route::get('/users', [RecruitAdminController::class, 'searchUsers'])->name('users');
        Route::get('/departments', [RecruitAdminController::class, 'departments'])->name('departments');

        // เส้นทางอนุมัติ (มีได้หลายเส้นทาง)
        Route::post('/routes', [RecruitAdminController::class, 'addRoute'])->name('routes.add');
        Route::put('/routes/{route}', [RecruitAdminController::class, 'renameRoute'])->name('routes.rename');
        Route::delete('/routes/{route}', [RecruitAdminController::class, 'deleteRoute'])->name('routes.delete');
        Route::post('/routes/{route}/steps', [RecruitAdminController::class, 'addStep'])->name('steps.add');

        // การ์ด (ขั้น) ภายในเส้นทาง
        Route::put('/steps/reorder', [RecruitAdminController::class, 'reorder'])->name('steps.reorder');
        Route::delete('/steps/{step}', [RecruitAdminController::class, 'deleteStep'])->name('steps.delete');
        Route::put('/steps/{step}/role', [RecruitAdminController::class, 'setRole'])->name('steps.role');
        Route::post('/steps/{step}/members', [RecruitAdminController::class, 'addMember'])->name('members.add');
        Route::delete('/members/{member}', [RecruitAdminController::class, 'removeMember'])->name('members.remove');
        Route::put('/members/{member}/departments', [RecruitAdminController::class, 'setDepartments'])->name('members.departments');
    });
});
