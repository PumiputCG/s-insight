@echo off
REM ============================================================
REM  register-sync-task.bat — ลงทะเบียน Windows Task Scheduler ครั้งเดียวตอน deploy
REM
REM  สร้าง task "InsightBplusSync" ที่เคาะ Laravel Scheduler ทุก 1 นาที
REM  -> Laravel จะรัน bplus:sync ทุก 15 นาที (ดึงพนักงานจาก Bplus เข้า Insight)
REM
REM  คุณสมบัติ:
REM   - รันด้วยบัญชี SYSTEM (ไม่ต้องใส่รหัสผ่าน, ทำงานแม้ไม่มีใคร login)
REM   - อยู่รอดเมื่อ reboot / ไฟดับ — Windows เก็บ task ไว้ในระบบ ไม่ใช่ใน CMD
REM
REM  วิธีใช้: ดับเบิลคลิกไฟล์นี้ 1 ครั้ง (จะขอสิทธิ์ Administrator อัตโนมัติ)
REM
REM  ก่อนรัน: กรอก BPLUS_PASSWORD ในไฟล์ .env ของเซิร์ฟเวอร์ให้เรียบร้อย (gitignored)
REM ============================================================

set "TASK_NAME=InsightBplusSync"
set "RUNNER=%~dp0run-schedule.cmd"

REM ---- ขอสิทธิ์ Administrator ถ้ายังไม่มี ----
net session >nul 2>&1
if %errorlevel% neq 0 (
  echo [i] ต้องใช้สิทธิ์ Administrator - กำลังขอสิทธิ์...
  powershell -Command "Start-Process -FilePath '%~f0' -Verb RunAs"
  exit /b
)

echo.
echo === ลงทะเบียน Task: %TASK_NAME% ===
echo     ตัวรัน: %RUNNER%
echo.

schtasks /Create ^
  /TN "%TASK_NAME%" ^
  /TR "\"%RUNNER%\"" ^
  /SC MINUTE /MO 1 ^
  /RU SYSTEM /RL HIGHEST ^
  /F

if %errorlevel% equ 0 (
  echo.
  echo [OK] ลงทะเบียนสำเร็จ! ระบบจะดึงข้อมูลจาก Bplus ทุก 15 นาทีอัตโนมัติ
  echo      - ดู task ได้ใน Task Scheduler ชื่อ "%TASK_NAME%"
  echo      - log การรัน: storage\logs\bplus_sync.log
  echo      - ยกเลิก: schtasks /Delete /TN "%TASK_NAME%" /F
) else (
  echo.
  echo [X] ลงทะเบียนไม่สำเร็จ - ตรวจสอบสิทธิ์ Administrator
)

echo.
pause
