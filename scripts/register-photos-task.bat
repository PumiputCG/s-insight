@echo off
REM ============================================================
REM  register-photos-task.bat
REM  สร้าง Windows Task ให้ดึงรูปพนักงานจากโฟลเดอร์ HR อัตโนมัติ
REM
REM  🔴 ต้องรันบนเครื่องเซิร์ฟ 192.168.7.12 ด้วยสิทธิ์ Administrator
REM     (คลิกขวา -> Run as administrator)
REM
REM  ทำไมต้องมี task แยก:
REM    Laravel scheduler ตัวหลัก (InsightBplusSync) รันด้วยบัญชี SYSTEM
REM    ซึ่ง Windows ไม่ให้เข้า network share \\192.168.5.1\_DriveZ
REM    รูปพนักงานจึงไม่เคยถูกดึงบนเซิร์ฟเลย
REM
REM  สคริปต์นี้จะถามรหัสผ่านตอนรัน แล้วให้ Windows เก็บเอง
REM  ** รหัสผ่านไม่ถูกบันทึกลงไฟล์ใดๆ ในโปรเจค **
REM ============================================================
setlocal

set "TASKNAME=InsightHrPhotosPull"
set "RUNNER=%~dp0hr-photos-pull.bat"

echo.
echo  ==========================================================
echo   ตั้ง Task ดึงรูปพนักงาน - %TASKNAME%
echo  ==========================================================
echo.
echo   ไฟล์ที่จะให้รัน : %RUNNER%
echo   ความถี่         : ทุก 1 ชั่วโมง
echo.
echo   ต้องใช้บัญชีที่เปิด \\192.168.5.1\_DriveZ ได้
echo   (ลองเปิดโฟลเดอร์นี้ด้วยบัญชีนั้นก่อน ถ้าเปิดได้ค่อยมาตั้ง task)
echo.

set "ACCT="
set /p ACCT="  ใส่ชื่อบัญชี (เช่น SUPAVUT\svc_insight หรือ .\hruser) : "
if "%ACCT%"=="" (
  echo.
  echo   ยกเลิก - ไม่ได้ใส่ชื่อบัญชี
  goto :end
)

echo.
echo   ต่อไป Windows จะถามรหัสผ่านของบัญชี %ACCT%
echo.

schtasks /Create /TN "%TASKNAME%" /TR "\"%RUNNER%\"" /SC HOURLY /MO 1 /RU "%ACCT%" /RP * /RL HIGHEST /F
if errorlevel 1 (
  echo.
  echo   ❌ สร้าง task ไม่สำเร็จ - เช็คชื่อบัญชี/รหัสผ่าน หรือรันด้วย Administrator หรือยัง
  goto :end
)

echo.
echo   ✅ สร้าง task แล้ว - กำลังทดสอบรันหนึ่งรอบ...
schtasks /Run /TN "%TASKNAME%"

echo.
echo   รอสักครู่แล้วเปิดดูผลที่:
echo     C:\xampp\htdocs\Insight\storage\logs\hr_photos.log
echo.
echo   ถ้าขึ้น "เข้าโฟลเดอร์ไม่ได้" = บัญชีนี้ยังไม่มีสิทธิ์เข้า share
echo   ถ้าขึ้นตัวเลข "ดึงเข้าใหม่ / อัปเดตรูปเดิม" = ใช้ได้แล้ว
echo.

:end
pause
endlocal
