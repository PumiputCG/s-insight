<?php

use App\Http\Controllers\OtApproval\OtApprovalController;
use App\Http\Controllers\OtApproval\Leave75ExportController;
use App\Http\Controllers\OtApproval\LeaveApprovalController;
use App\Http\Controllers\OtApproval\LeaveRequestWorkflowController;
use App\Http\Controllers\OtApproval\OtNotificationController;
use App\Http\Controllers\OtApproval\OtApprovalSettingController;
use App\Http\Controllers\OtApproval\OtRequestWorkflowController;
use App\Http\Controllers\OtApproval\V74ExportController;
use Illuminate\Support\Facades\Route;

/* Time & Leave Approval — คง prefix เดิมเพื่อรักษาลิงก์และสิทธิ์ของ OT ที่ใช้งานอยู่ */
Route::prefix('ot-approval')->name('ot-approval.')->group(function () {
    // หน้าหลัก = หน้าแรกที่เจอเมื่อเข้าระบบ (แสดงสิทธิ์ของผู้ใช้)
    Route::get('/', [OtApprovalController::class, 'home'])->name('home');
    Route::get('/overview', [OtApprovalController::class, 'index'])->name('index');
    Route::get('/overview/data', [OtApprovalController::class, 'attendanceSummary'])->name('attendance.summary');
    Route::get('/overview/employees', [OtApprovalController::class, 'attendanceEmployees'])->name('attendance.employees');
    Route::get('/exports/v74', [V74ExportController::class, 'download'])->name('exports.v74');
    // หน้ารวมการดาวน์โหลดเอกสาร (admin เท่านั้น) — โหลดย้อนหลังได้ทุกวันในเดือน
    Route::get('/downloads', [OtApprovalController::class, 'downloads'])->name('downloads.index');
    Route::get('/downloads/calendar', [OtApprovalController::class, 'downloadCalendar'])->name('downloads.calendar');
    Route::get('/downloads/day', [OtApprovalController::class, 'downloadDay'])->name('downloads.day');
    // รายละเอียดการทำงานของตัวเอง — ทุกคนที่ผูกกับพนักงานเข้าดูได้ ไม่ต้องมีสิทธิ์ OT
    Route::get('/work-detail', [OtApprovalController::class, 'myWorkDetail'])->name('work-detail');
    // ประวัติ OT + การลารายคนทั้งเดือน เปิดจากหน้าภาพรวมเป็นแท็บใหม่
    Route::get('/employees/{company}/{employeeCode}', [OtApprovalController::class, 'employeeMonth'])->name('employees.month');
    Route::get('/exports/leave75', [Leave75ExportController::class, 'download'])->name('exports.leave75');

    // การลา 75 แยกหน้า/ตาราง/Service จาก OT เพื่อไม่เรียก Attendance โดยไม่จำเป็น
    Route::get('/leave-overview', [LeaveApprovalController::class, 'overview'])->name('leave-overview');
    Route::get('/leave-overview/employees', [LeaveApprovalController::class, 'overviewEmployees'])->name('leave-overview.employees');
    Route::get('/leave-requests', [LeaveApprovalController::class, 'requests'])->name('leave-requests.index');
    Route::get('/leave-requests/employees', [LeaveRequestWorkflowController::class, 'employees'])->name('leave-requests.employees');
    Route::post('/leave-requests', [LeaveRequestWorkflowController::class, 'store'])->name('leave-requests.store');
    Route::post('/leave-requests/submit', [LeaveRequestWorkflowController::class, 'submit'])->name('leave-requests.submit');
    Route::post('/leave-requests/bulk', [LeaveRequestWorkflowController::class, 'bulkStore'])->name('leave-requests.bulk');
    Route::post('/leave-requests/cancel', [LeaveRequestWorkflowController::class, 'cancel'])->name('leave-requests.cancel');
    Route::get('/leave-approvals', [LeaveApprovalController::class, 'approvals'])->name('leave-approvals.index');
    Route::get('/leave-approvals/data', [LeaveRequestWorkflowController::class, 'queue'])->name('leave-approvals.data');
    Route::post('/leave-approvals/{leaveRequest}/decision', [LeaveRequestWorkflowController::class, 'decide'])->name('leave-approvals.decision');
    Route::post('/leave-approvals/bulk-decision', [LeaveRequestWorkflowController::class, 'bulkDecide'])->name('leave-approvals.bulk-decision');

    Route::get('/requests', [OtApprovalController::class, 'requests'])->name('requests.index');
    Route::get('/requests/employees', [OtRequestWorkflowController::class, 'employees'])->name('requests.employees');
    Route::post('/requests', [OtRequestWorkflowController::class, 'store'])->name('requests.store');
    Route::post('/requests/submit', [OtRequestWorkflowController::class, 'submit'])->name('requests.submit');
    Route::post('/requests/bulk', [OtRequestWorkflowController::class, 'bulkStore'])->name('requests.bulk');
    Route::post('/requests/cancel', [OtRequestWorkflowController::class, 'cancel'])->name('requests.cancel');
    Route::get('/approvals', [OtApprovalController::class, 'approvals'])->name('approvals.index');
    Route::get('/approvals/data', [OtRequestWorkflowController::class, 'queue'])->name('approvals.data');
    Route::post('/approvals/{otRequest}/decision', [OtRequestWorkflowController::class, 'decide'])->name('approvals.decision');
    Route::post('/approvals/bulk-decision', [OtRequestWorkflowController::class, 'bulkDecide'])->name('approvals.bulk-decision');

    // กระดิ่งแจ้งเตือน — เรียกได้จากทุกหน้าของ Insight เพราะกระดิ่งอยู่บน portal
    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/', [OtNotificationController::class, 'index'])->name('index');
        Route::post('/read-all', [OtNotificationController::class, 'readAll'])->name('read-all');
        Route::post('/{notification}/read', [OtNotificationController::class, 'read'])->name('read');
    });

    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('/', [OtApprovalSettingController::class, 'index'])->name('index');
        Route::get('/users', [OtApprovalSettingController::class, 'searchUsers'])->name('users');
        Route::post('/members', [OtApprovalSettingController::class, 'addMember'])->name('members.add');
        Route::delete('/members/{member}', [OtApprovalSettingController::class, 'removeMember'])->name('members.remove');
        Route::put('/positions', [OtApprovalSettingController::class, 'savePositions'])->name('positions');
        Route::put('/hidden-request-positions', [OtApprovalSettingController::class, 'saveHiddenRequestPositions'])->name('hidden-request-positions');
        Route::put('/hidden-leave-positions', [OtApprovalSettingController::class, 'saveHiddenLeavePositions'])->name('hidden-leave-positions');
        Route::get('/department-employees', [OtApprovalSettingController::class, 'departmentEmployees'])->name('department-employees');
        Route::put('/assignments', [OtApprovalSettingController::class, 'saveAssignment'])->name('assignments.save');
        Route::delete('/assignments/{assignment}', [OtApprovalSettingController::class, 'removeAssignment'])->name('assignments.remove');
    });
});
