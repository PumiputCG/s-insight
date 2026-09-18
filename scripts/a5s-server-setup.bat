@echo off
REM ============================================================
REM  ตั้งค่าระบบ 5S บนเซิร์ฟให้ครบ — รันบนเครื่องเซิร์ฟ 192.168.7.12
REM  ดับเบิลคลิกไฟล์นี้ได้เลย ผลลัพธ์เก็บไว้ที่ storage\logs\a5s-setup.log
REM
REM  ทำอะไรบ้าง
REM    1) migrate --force        เพิ่มตาราง a5s_task_attempts + คอลัมน์ seq/inspected_on
REM    2) a5s:doctor             ตรวจก่อน (ไม่แก้อะไร)
REM    3) a5s:doctor --fix       map ข้อมูลเก่าที่ชี้ขาดได้
REM    4) a5s:doctor             ตรวจซ้ำหลังแก้
REM    5) view:clear             ล้าง blade ที่คอมไพล์ไว้
REM  migration เป็นแบบเพิ่มอย่างเดียว ไม่ลบข้อมูลเดิม
REM ============================================================

cd /d "%~dp0.."


set PHP=C:\xampp\php\php.exe
set LOG=storage\logs\a5s-setup.log
echo ============================================ >> "%LOG%"
echo  a5s server setup  %DATE% %TIME% >> "%LOG%"
echo ============================================ >> "%LOG%"

echo.
echo [1/5] รัน migration ที่ค้าง...
"%PHP%" artisan migrate --path=database/migrations/area5s --force >> "%LOG%" 2>&1
if errorlevel 1 goto failed

echo [2/5] ตรวจสถานะก่อนแก้...
"%PHP%" artisan a5s:doctor >> "%LOG%" 2>&1

echo [3/5] map ข้อมูลเก่า...
"%PHP%" artisan a5s:doctor --fix >> "%LOG%" 2>&1
if errorlevel 1 goto failed

echo [4/5] ตรวจซ้ำหลังแก้...
"%PHP%" artisan a5s:doctor >> "%LOG%" 2>&1

echo [5/5] ล้าง cache ของ view...
"%PHP%" artisan view:clear >> "%LOG%" 2>&1

echo.
echo เสร็จแล้ว — ดูผลเต็ม ๆ ที่ %LOG%
echo.
type "%LOG%"
echo.
pause
exit /b 0

:failed
echo.
echo !! มีขั้นตอนที่ล้มเหลว — เปิดดูรายละเอียดที่ %LOG%
echo.
type "%LOG%"
echo.
pause
exit /b 1



