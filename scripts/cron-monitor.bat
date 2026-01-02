@echo off
REM SafariChat Cron Monitor Script for Windows
REM Usage: cron-monitor.bat [status|health|logs|start|stop]

set "SCRIPT_DIR=%~dp0"
set "LARAVEL_ROOT=%SCRIPT_DIR%.."

REM Check if Laravel artisan exists
if not exist "%LARAVEL_ROOT%\artisan" (
    echo [ERROR] Laravel artisan not found. Make sure you're in the correct directory.
    exit /b 1
)

REM Change to Laravel root directory
cd /d "%LARAVEL_ROOT%"

set "ACTION=%1"
if "%ACTION%"=="" set "ACTION=status"

if "%ACTION%"=="status" goto STATUS
if "%ACTION%"=="health" goto HEALTH
if "%ACTION%"=="logs" goto LOGS
if "%ACTION%"=="start" goto START
if "%ACTION%"=="stop" goto STOP
if "%ACTION%"=="restart" goto RESTART
if "%ACTION%"=="test" goto TEST
goto HELP

:STATUS
echo === Cron Job Status ===
php artisan cron:monitor --action=status
goto END

:HEALTH
echo === Cron Health Check ===
php artisan cron:monitor --action=health
goto END

:LOGS
echo === Recent Cron Logs ===
php artisan cron:monitor --action=logs
goto END

:START
echo === Starting Laravel Scheduler ===
echo [INFO] Starting Laravel scheduler...

REM Check if already running
tasklist /FI "IMAGENAME eq php.exe" /FI "WINDOWTITLE eq *schedule*" 2>NUL | find /I "php.exe" >NUL
if "%ERRORLEVEL%"=="0" (
    echo [WARN] PHP processes found. Scheduler might already be running.
)

REM Start scheduler in background
echo [INFO] Starting schedule worker in background...
start /B "" php artisan schedule:work >> storage\logs\schedule-work.log 2>&1

echo [INFO] Scheduler started. Check storage\logs\schedule-work.log for output
goto END

:STOP
echo === Stopping Laravel Scheduler ===
echo [INFO] Stopping scheduler processes...
taskkill /F /IM php.exe /FI "WINDOWTITLE eq *schedule*" 2>NUL
echo [INFO] Scheduler processes stopped
goto END

:RESTART
echo === Restarting Laravel Scheduler ===
taskkill /F /IM php.exe /FI "WINDOWTITLE eq *schedule*" 2>NUL
timeout /t 2 >NUL
start /B "" php artisan schedule:work >> storage\logs\schedule-work.log 2>&1
echo [INFO] Scheduler restarted
goto END

:TEST
echo === Testing Cron Configuration ===
echo [INFO] Running scheduler once...
php artisan schedule:run -v

echo [INFO] Checking log permissions...
if exist "storage\logs" (
    echo [INFO] Log directory exists
) else (
    echo [ERROR] Log directory not found
)

echo [INFO] Testing specific commands...
php artisan cron:monitor --action=health
goto END

:HELP
echo === SafariChat Cron Monitor ===
echo Usage: %0 [command]
echo.
echo Commands:
echo   status   - Show cron job status
echo   health   - Run health check  
echo   logs     - Show recent logs
echo   start    - Start Laravel scheduler
echo   stop     - Stop Laravel scheduler
echo   restart  - Restart Laravel scheduler
echo   test     - Test cron configuration
echo.
echo Example: %0 health
echo.
echo Note: On Windows, you should run this from an elevated command prompt
echo and ensure PHP is in your PATH.
goto END

:END
exit /b 0