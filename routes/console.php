<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Scheduled Tasks
|--------------------------------------------------------------------------
| ดึงพนักงานจาก Bplus ทุก 15 นาที (วิธี B — ต่อตรง pdo_sqlsrv)
| ต้องมี Task Scheduler รัน `php artisan schedule:run` ทุก 1 นาที
|   -> ลงทะเบียนครั้งเดียวด้วย scripts\register-sync-task.bat
| ความถี่/เวลาทั้งหมดคุมจากไฟล์นี้ (แก้แล้ว deploy ทับ ไม่ต้องแตะเซิร์ฟเวอร์)
*/
Schedule::command('bplus:sync')
    ->everyFifteenMinutes()
    ->withoutOverlapping()          // กัน sync รอบใหม่ทับรอบเก่าที่ยังไม่เสร็จ
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/bplus_sync.log'));

/*
| เวลาสแกนเข้า-ออกของ "วันนี้" ทุก 1 นาที
|
| พนักงานสแกนหน้าที่เครื่อง -> เครื่องส่งเข้า Bplus -> รอบนี้ดึงเข้ามาไม่เกิน 1 นาที
| วัดแล้วรอบหนึ่งใช้เวลา ~2 วินาทีสำหรับ 3 บริษัทรวม ~1,500 คน จึงเบาพอที่จะถี่ระดับนี้
| ระบบ OT ใช้เวลาสแกนเป็นตัวตัดสินว่าผ่าน/ไม่ผ่าน ข้อมูลจึงต้องสดที่สุดเท่าที่ทำได้
*/
Schedule::command('ot-approval:sync-attendance')
    ->everyMinute()
    ->withoutOverlapping()          // เน็ตช้าแล้วรอบเก่ายังไม่จบ ให้ข้ามรอบใหม่ไปเลย
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/attendance_sync.log'));

Schedule::command('ot-approval:expire-pending')
    ->everyMinute()
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/ot_expire_pending.log'));

/*
| ดึง "เมื่อวาน" ซ้ำอีกรอบตอนเช้า
|
| ระหว่างวัน Bplus ให้แค่สแกนดิบ (attendance_state = latest_scan) ระบบจึงยังไม่ฟันธงผล
| พอ Bplus ประมวลผลข้ามคืนถึงจะมี TMT_STAMP_OUT ที่เป็นเวลาออกจริง (final_out)
| รอบนี้จึงจำเป็นเพื่อให้คำขอที่ค้างอยู่ถูกตัดสินให้ถูกต้อง ไม่ค้างเป็น "รอสแกน" ตลอดไป
*/
Schedule::call(function () {
    Artisan::call('ot-approval:sync-attendance', ['date' => now()->subDay()->format('Y-m-d')]);
})->dailyAt('06:10')->name('sync-attendance-yesterday')->withoutOverlapping();

/*
| ดึงรูปพนักงานจากโฟลเดอร์ของ HR วันละ 3 รอบ (09:00 · 13:00 · 17:00)
|
| ทำไมไม่ถี่กว่านี้: โฟลเดอร์ต้นทางเป็น network share ที่ช้ามาก
| รอบที่มีรูปใหม่ใช้เวลาราวๆ 2-3 นาที ส่วนรอบที่ไม่มีอะไรเปลี่ยนจบใน ~1 วินาที
| (เพราะจำไว้ใน employee_photo_files ว่าไฟล์ไหนดึงไปแล้ว)
| เจ้าของสั่งไว้ว่า "เห็นภายในวันก็พอ" 3 รอบจึงเหลือเฟือ
|
| ถ้าเข้า share ไม่ได้ คำสั่งจะบันทึกไว้ในตาราง employee_photo_runs แล้วจบเฉยๆ
| ไม่ทำให้รูปเดิมหายและไม่ทำให้ schedule ตัวอื่นพัง
*/
Schedule::command('photos:pull')
    ->cron('0 9,13,17 * * *')
    ->when(fn (): bool => (bool) config('insight.hr_photos.schedule'))
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/hr_photos.log'));

/*
| กวาดทุกโฟลเดอร์ย้อนหลังสัปดาห์ละครั้ง (อาทิตย์ ตี 2)
|
| โฟลเดอร์ปีเก่ามีรูปของคนที่ยังไม่มีรูปในระบบอยู่ รอบรายวันไม่ได้แตะเพราะช้าเกินไป
| รอบนี้จึงไล่ให้ครบทีเดียวตอนไม่มีคนใช้งาน — ใช้เวลานาน (หลายนาทีถึงหลายสิบนาที)
| แต่รันเป็น background และข้ามไฟล์ที่เคยดึงแล้ว จึงไม่กระทบงานอื่น
*/
Schedule::command('photos:pull --all')
    ->weeklyOn(0, '02:00')
    ->when(fn (): bool => (bool) config('insight.hr_photos.schedule'))
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/hr_photos.log'));
