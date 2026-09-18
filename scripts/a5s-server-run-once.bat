@echo off
REM Non-interactive 5S server setup for Task Scheduler.
REM Runs on 192.168.7.12 from C:\xampp\htdocs\Insight.

cd /d "C:\xampp\htdocs\Insight"

set PHP=C:\xampp\php\php.exe
set LOG=storage\logs\a5s-setup.log
echo ============================================ >> "%LOG%"
echo  a5s server run-once  %DATE% %TIME% >> "%LOG%"
echo ============================================ >> "%LOG%"

echo [1/8] php version >> "%LOG%"
"%PHP%" -v >> "%LOG%" 2>&1
if errorlevel 1 exit /b 1

echo [2/8] clear config/cache/route/view before migration >> "%LOG%"
"%PHP%" artisan config:clear >> "%LOG%" 2>&1
"%PHP%" artisan cache:clear >> "%LOG%" 2>&1
"%PHP%" artisan route:clear >> "%LOG%" 2>&1
"%PHP%" artisan view:clear >> "%LOG%" 2>&1

echo [3/8] migrate --force >> "%LOG%"
"%PHP%" artisan migrate --path=database/migrations/area5s --force >> "%LOG%" 2>&1
if errorlevel 1 exit /b 1

echo [4/8] a5s:doctor before fix >> "%LOG%"
"%PHP%" artisan a5s:doctor >> "%LOG%" 2>&1

echo [5/8] a5s:doctor --fix >> "%LOG%"
"%PHP%" artisan a5s:doctor --fix >> "%LOG%" 2>&1
if errorlevel 1 exit /b 1

echo [6/8] a5s:doctor after fix >> "%LOG%"
"%PHP%" artisan a5s:doctor >> "%LOG%" 2>&1

echo [7/8] cache views >> "%LOG%"
"%PHP%" artisan view:cache >> "%LOG%" 2>&1
if errorlevel 1 exit /b 1

echo [8/8] done %DATE% %TIME% >> "%LOG%"
exit /b 0



