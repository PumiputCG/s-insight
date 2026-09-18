<?php

use App\Http\Controllers\Insight\Auth\ChangePasswordController;
use App\Http\Controllers\Insight\Auth\ForgotPasswordController;
use App\Http\Controllers\Insight\Auth\LoginController;
use App\Http\Controllers\Insight\DashboardController;
use App\Http\Controllers\Insight\ProfileController;
use App\Http\Controllers\Insight\SettingController;
use Illuminate\Support\Facades\Route;

/*
| โมดูล Insight (core portal) — landing + login (สาธารณะ) + dashboard/logout/เปลี่ยนรหัส (ต้องล็อกอิน)
| Auth เป็น custom session (ดู AuthenticateEmployee) เทียบ password plaintext (D-007)
*/

// หน้าแรก (landing) — สาธารณะ มีปุ่ม "เริ่มใช้งาน" -> login
Route::view('/', 'insight.landing')->name('landing');

// Login (สาธารณะ)
Route::get('/login', [LoginController::class, 'show'])->name('login');
Route::post('/login', [LoginController::class, 'login'])->name('login.attempt');

/*
| ลืมรหัสผ่าน (สาธารณะ) — ยืนยันด้วยรหัสพนักงาน + เลขบัตรประชาชน
| จำกัดจำนวนครั้งต่อ IP เพราะเป็นเส้นทางที่เปลี่ยนรหัสผ่านได้โดยไม่ต้องล็อกอิน
*/
Route::middleware('throttle:8,1')->group(function () {
    Route::post('/forgot-password/verify', [ForgotPasswordController::class, 'verify'])->name('password.forgot.verify');
    Route::put('/forgot-password', [ForgotPasswordController::class, 'update'])->name('password.forgot.update');
});

// ต้องล็อกอินก่อน
Route::middleware('insight.auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

    // เปลี่ยนรหัสผ่าน (รวมกรณีบังคับครั้งแรก)
    Route::get('/change-password', [ChangePasswordController::class, 'edit'])->name('password.edit');
    Route::post('/change-password/verify', [ChangePasswordController::class, 'verify'])->name('password.verify');
    Route::put('/change-password', [ChangePasswordController::class, 'update'])->name('password.update');

    // dashboard — user และ admin เห็นหน้าโปรไฟล์เดียวกัน
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // ระบบทั้งหมด (รวมทางเข้าระบบย่อย) — $me share จาก middleware
    Route::view('/systems', 'insight.systems')->name('systems.index');

    // อัปโหลดรูปโปรไฟล์
    Route::post('/profile/picture', [ProfileController::class, 'updatePicture'])->name('profile.picture');

    // ลายเซ็น (เก็บลง app_users.signature เพื่อประทับเอกสาร)
    Route::post('/profile/signature', [ProfileController::class, 'updateSignature'])->name('profile.signature');
    Route::delete('/profile/signature', [ProfileController::class, 'deleteSignature'])->name('profile.signature.delete');

    // อีเมล (เก็บลง app_users.email)
    Route::post('/profile/email', [ProfileController::class, 'updateEmail'])->name('profile.email');

    // ฟังก์ชันเฉพาะผู้ดูแลระบบ
    Route::middleware('insight.admin')->group(function () {
        Route::get('/admin/overview', [DashboardController::class, 'adminOverview'])->name('admin.overview');
        Route::get('/admin/department', [DashboardController::class, 'departmentEmployees'])->name('admin.department');
        Route::get('/admin/employees/search', [DashboardController::class, 'searchActiveEmployees'])->name('admin.employee-search');
        Route::get('/admin/employee/leave-rights', [DashboardController::class, 'employeeLeaveRights'])->name('admin.employee-leave-rights');

        // ตั้งค่าระบบ (Setting) — คุมสิทธิ์ตามตำแหน่ง: scope = login | email | signature
        Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
        Route::put('/settings/positions/{scope}', [SettingController::class, 'updatePositions'])->name('settings.positions');

        // ดึงข้อมูลพนักงานจาก Bplus แบบ manual (2 ปุ่มในหน้า settings)
        Route::post('/settings/bplus/pull', [SettingController::class, 'pullBplus'])->name('settings.bplus.pull');
        Route::post('/settings/bplus/appusers', [SettingController::class, 'syncAppUsers'])->name('settings.bplus.appusers');
        Route::get('/settings/bplus/report/{kind}', [SettingController::class, 'report'])->name('settings.bplus.report');

        // ดาวน์โหลดข้อมูลพนักงานทั้งหมดเป็น Excel
        // ดึงรูปพนักงานจากโฟลเดอร์ของ HR แบบกดเอง (ปกติมี Scheduled Task ทำให้อยู่แล้ว)
        Route::post('/settings/photos/pull', [SettingController::class, 'pullPhotos'])->name('settings.photos.pull');

        Route::get('/settings/employees/export', [SettingController::class, 'exportEmployees'])->name('settings.employees.export');
    });
});
