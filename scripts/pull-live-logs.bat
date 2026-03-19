@echo off
REM Batch wrapper for pull-live-logs.ps1
REM Quick access script for pulling production logs

cd /d "%~dp0\.."

echo.
echo ========================================
echo    Pulling Logs from Live Server
echo ========================================
echo.

powershell -ExecutionPolicy Bypass -File "%~dp0pull-live-logs.ps1" %*

if %ERRORLEVEL% EQU 0 (
    echo.
    echo Logs downloaded successfully!
    echo Location: storage\livelog
) else (
    echo.
    echo Log download failed. Check the output above for details.
)

echo.
pause
