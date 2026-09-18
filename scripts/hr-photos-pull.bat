@echo off
REM ============================================================
REM  hr-photos-pull.bat - ดึงรูปพนักงานจากโฟลเดอร์ HR เข้าระบบ
REM
REM  ถูกเรียกโดย Task "InsightHrPhotosPull" ที่สร้างด้วย register-photos-task.bat
REM  ต้องรันด้วยบัญชีที่เข้า \\192.168.5.1\_DriveZ ได้ (บัญชี SYSTEM เข้าไม่ได้)
REM
REM  รอบที่ไม่มีรูปใหม่จบใน ~1 วินาที (จำไว้แล้วว่าไฟล์ไหนดึงไปแล้ว)
REM  รอบที่มีรูปใหม่ใช้เวลาราว 2-3 นาที
REM  log: storage\logs\hr_photos.log
REM ============================================================
setlocal

set "PROJ=%~dp0.."
set "PHP_EXE=C:\xampp\php\php.exe"
if not exist "%PHP_EXE%" set "PHP_EXE=php"

cd /d "%PROJ%"

echo ============================================ >> "%PROJ%\storage\logs\hr_photos.log"
echo  hr-photos-pull %DATE% %TIME% (task) >> "%PROJ%\storage\logs\hr_photos.log"
echo ============================================ >> "%PROJ%\storage\logs\hr_photos.log"

REM ดึงรูปจากโฟลเดอร์ปีปัจจุบัน แล้วผูกเข้ากับทะเบียนพนักงาน
"%PHP_EXE%" artisan photos:pull >> "%PROJ%\storage\logs\hr_photos.log" 2>&1

REM ผูกไฟล์ที่ได้เข้ากับ employees.photo_path / app_users.profile_picture
"%PHP_EXE%" artisan profiles:sync >> "%PROJ%\storage\logs\hr_photos.log" 2>&1

endlocal
