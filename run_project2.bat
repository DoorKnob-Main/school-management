@echo off
title School Management ERP - Biometric Attendance System (Instance 2)
setlocal enabledelayedexpansion

cd /d "%~dp0"

echo ===============================================================================
echo        School Management ERP ^& M50 Biometric Attendance System
echo        (Instance 2 - Port 8000)
echo ===============================================================================
echo.

:: 1. Locate PHP Binary
set "PHP_EXE=php"
where php >nul 2>nul
if %errorlevel% neq 0 (
    if exist "%LOCALAPPDATA%\Microsoft\WinGet\Packages\PHP.PHP.8.2_Microsoft.Winget.Source_8wekyb3d8bbwe\php.exe" (
        set "PHP_EXE=%LOCALAPPDATA%\Microsoft\WinGet\Packages\PHP.PHP.8.2_Microsoft.Winget.Source_8wekyb3d8bbwe\php.exe"
    ) else if exist "C:\php\php.exe" (
        set "PHP_EXE=C:\php\php.exe"
    ) else if exist "C:\xampp\php\php.exe" (
        set "PHP_EXE=C:\xampp\php\php.exe"
    ) else (
        echo [ERROR] PHP executable was not found on your system.
        echo Please ensure PHP 8.1+ is installed.
        pause
        exit /b 1
    )
)

echo [OK] Using PHP: %PHP_EXE%

:: 2. Ensure .env2 exists and configure for Instance 2
if not exist ".env2" (
    echo [INFO] Creating .env2 file from .env.example...
    copy .env.example .env2 >nul
)

set "DB_PATH=%CD:\=/%/database/database_instance2.sqlite"
powershell -NoProfile -Command ^
  "(Get-Content .env2) |" ^
  "ForEach-Object { $_ -replace '^DB_CONNECTION=.*', 'DB_CONNECTION=sqlite' } |" ^
  "ForEach-Object { $_ -replace '^DB_DATABASE=.*', 'DB_DATABASE=!DB_PATH!' } |" ^
  "ForEach-Object { $_ -replace '^APP_URL=.*', 'APP_URL=http://localhost:8000' } |" ^
  "Set-Content .env2"

copy /y .env2 .env >nul

findstr /b "APP_KEY=" .env | findstr /r "APP_KEY=.\+" >nul 2>nul
if %errorlevel% neq 0 (
    echo [INFO] Generating application key...
    "%PHP_EXE%" artisan key:generate --force
    copy /y .env .env2 >nul
)

:: 3. Ensure SQLite Database File Exists for Instance 2
if not exist "database\database_instance2.sqlite" (
    echo [INFO] Initializing SQLite database for Instance 2...
    type nul > "database\database_instance2.sqlite"
    echo [INFO] Running database migrations ^& seeders...
    "%PHP_EXE%" artisan migrate:fresh --seed --force
)

:: 4. Clear Caches
echo [INFO] Optimizing application caches...
"%PHP_EXE%" artisan config:clear >nul 2>nul
"%PHP_EXE%" artisan route:clear >nul 2>nul
"%PHP_EXE%" artisan view:clear >nul 2>nul

:: 5. Open Browser in Background
echo [INFO] Launching browser to http://127.0.0.1:8000 ...
start "" http://127.0.0.1:8000

echo.
echo ===============================================================================
echo   Server (Instance 2) is running at: http://127.0.0.1:8000
echo ===============================================================================
echo   Admin Login:
echo     Email:    admin@ut.com
echo     Password: password
echo.
echo   Super Admin Login:
echo     Email:    superadmin@ut.com
echo     Password: password
echo.
echo   Features:
echo     - Biometric Dashboard:   http://127.0.0.1:8000/biometric/dashboard
echo     - Device Management:     http://127.0.0.1:8000/biometric/devices
echo     - Today's Attendance:    http://127.0.0.1:8000/biometric/today
echo     - Student Enrollment:    http://127.0.0.1:8000/biometric/enrollment
echo     - Leave Management:      http://127.0.0.1:8000/leaves
echo     - Reports Hub:           http://127.0.0.1:8000/biometric/reports
echo ===============================================================================
echo   Press Ctrl+C in this terminal window to stop the server anytime.
echo ===============================================================================
echo.

:: 6. Set multi-worker environment for responsive concurrency and start server
set PHP_CLI_SERVER_WORKERS=4
"%PHP_EXE%" artisan serve --host=127.0.0.1 --port=8000

pause
