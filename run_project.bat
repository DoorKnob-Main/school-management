@echo off
title School Management ERP - Biometric Attendance System
setlocal enabledelayedexpansion

cd /d "%~dp0"

echo ===============================================================================
echo        School Management ERP ^& M50 Biometric Attendance System
echo        (On-Premise Setup - requires XAMPP: https://www.apachefriends.org)
echo ===============================================================================
echo.

:: 1. Locate PHP Binary (XAMPP first - this is the supported production path)
set "PHP_EXE=php"
if exist "C:\xampp\php\php.exe" (
    set "PHP_EXE=C:\xampp\php\php.exe"
) else (
    where php >nul 2>nul
    if !errorlevel! neq 0 (
        echo [ERROR] PHP was not found. Please install XAMPP first: https://www.apachefriends.org
        pause
        exit /b 1
    )
)
echo [OK] Using PHP: %PHP_EXE%

:: 2. Locate XAMPP MySQL client
set "MYSQL_EXE=C:\xampp\mysql\bin\mysql.exe"
if not exist "%MYSQL_EXE%" (
    echo [ERROR] XAMPP MySQL not found at %MYSQL_EXE%
    echo Please install XAMPP first: https://www.apachefriends.org
    pause
    exit /b 1
)
echo [OK] Using MySQL: %MYSQL_EXE%

:: 3. Ensure .env exists and is pinned to MySQL (single DB engine, dev = prod)
if not exist ".env" (
    echo [INFO] Creating .env file from .env.example...
    copy .env.example .env >nul
)

powershell -NoProfile -Command ^
  "(Get-Content .env) |" ^
  "ForEach-Object { $_ -replace '^DB_CONNECTION=.*', 'DB_CONNECTION=mysql' } |" ^
  "ForEach-Object { $_ -replace '^DB_HOST=.*', 'DB_HOST=127.0.0.1' } |" ^
  "ForEach-Object { $_ -replace '^DB_PORT=.*', 'DB_PORT=3306' } |" ^
  "ForEach-Object { $_ -replace '^DB_DATABASE=.*', 'DB_DATABASE=doorknob_school' } |" ^
  "ForEach-Object { $_ -replace '^DB_USERNAME=.*', 'DB_USERNAME=root' } |" ^
  "ForEach-Object { $_ -replace '^DB_PASSWORD=.*', 'DB_PASSWORD=' } |" ^
  "Set-Content .env"

findstr /b "APP_KEY=" .env | findstr /r "APP_KEY=.\+" >nul 2>nul
if %errorlevel% neq 0 (
    echo [INFO] Generating application key...
    "%PHP_EXE%" artisan key:generate --force
)

:: 4. Ensure the database exists (idempotent - safe to run every time)
echo [INFO] Ensuring database "doorknob_school" exists...
"%MYSQL_EXE%" -h 127.0.0.1 -P 3306 -u root -e "CREATE DATABASE IF NOT EXISTS doorknob_school;" 2>nul
if %errorlevel% neq 0 (
    echo [ERROR] Could not reach MySQL. Start "MySQL" in the XAMPP Control Panel first, then re-run this script.
    pause
    exit /b 1
)

:: 5. Run migrations (safe to re-run - only applies new migrations)
echo [INFO] Running database migrations...
"%PHP_EXE%" artisan migrate --force

:: First-time seed only (won't wipe existing client data on subsequent runs)
"%PHP_EXE%" artisan tinker --execute="exit(\DB::table('users')->count() > 0 ? 1 : 0);" >nul 2>nul
if %errorlevel% neq 0 (
    echo [INFO] First run detected - seeding default accounts...
    "%PHP_EXE%" artisan db:seed --force
)

:: 6. Clear caches
echo [INFO] Optimizing application caches...
"%PHP_EXE%" artisan config:clear >nul 2>nul
"%PHP_EXE%" artisan route:clear >nul 2>nul
"%PHP_EXE%" artisan view:clear >nul 2>nul

:: 7. Open Browser in Background
echo [INFO] Launching browser to http://127.0.0.1:8000 ...
start "" http://127.0.0.1:8000

echo.
echo ===============================================================================
echo   Server is running at: http://127.0.0.1:8000
echo   Other computers on this school's network can reach it at:
echo   http://ANY IP shown below^>:8000
ipconfig | findstr /i "IPv4"
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
echo   NOTE: this window must stay open (or run install_service.bat for a
echo   persistent background service that survives reboots).
echo ===============================================================================
echo.

:: 8. Serve on all network interfaces so other school PCs can connect (LAN)
set PHP_CLI_SERVER_WORKERS=4
"%PHP_EXE%" artisan serve --host=0.0.0.0 --port=8000

pause
