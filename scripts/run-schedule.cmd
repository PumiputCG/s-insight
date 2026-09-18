@echo off
REM ============================================================
REM  run-schedule.cmd — ตัวเคาะ Laravel Scheduler (เรียกทุก 1 นาทีโดย Task Scheduler)
REM  Laravel จะดูเองว่าถึงเวลา bplus:sync (ทุก 15 นาที) หรือยัง ตาม routes\console.php
REM
REM  ไฟล์นี้ถูกเรียกโดย task "InsightBplusSync" ที่สร้างด้วย register-sync-task.bat
REM  ไม่ต้องแก้อะไร ยกเว้น PHP_EXE ถ้า PHP ไม่ได้อยู่ที่ C:\xampp\php
REM ============================================================
setlocal

REM ---- โฟลเดอร์โปรเจค = โฟลเดอร์แม่ของ scripts\ ----
set "PROJ=%~dp0.."

REM ---- พาธ PHP (แก้ตรงนี้ถ้าเซิร์ฟเวอร์ติดตั้ง PHP ที่อื่น) ----
set "PHP_EXE=C:\xampp\php\php.exe"
if not exist "%PHP_EXE%" set "PHP_EXE=php"

cd /d "%PROJ%"
"%PHP_EXE%" artisan schedule:run >> "%PROJ%\storage\logs\schedule_run.log" 2>&1

endlocal
