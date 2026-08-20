@echo off
title School Management ERP - Biometric Attendance System (Instance 1 - Port 8000)
setlocal enabledelayedexpansion

cd /d "%~dp0"

echo ===============================================================================
echo        School Management ERP ^& M50 Biometric Attendance System
echo        (Instance 1 - Port 8000)
echo ===============================================================================
echo.

:: 1. Locate PHP Binary
set "PHP_EXE=php"
if exist "C:\xampp\php\php.exe" (
    set "PHP_EXE=C:\xampp\php\php.exe"
) else (
    where php >nul 2>nul
    if !errorlevel! neq 0 (
        echo [ERROR] PHP was not found. Please install PHP or XAMPP: https://www.apachefriends.org
        pause
        exit /b 1
    )
)
echo [OK] Using PHP: %PHP_EXE%

:: 2. Ensure .env exists
if not exist ".env" (
    echo [INFO] Creating .env file from .env.example...
    copy .env.example .env >nul
)

:: 3. Test MySQL Connection and Setup Database
set "USE_MYSQL=0"
"%PHP_EXE%" -r "try { $p = new PDO('mysql:host=127.0.0.1;port=3306', 'root', ''); echo 'OK'; } catch (Exception $e) { exit(1); }" >nul 2>nul
if %errorlevel% equ 0 (
    set "USE_MYSQL=1"
    echo [OK] MySQL server detected at 127.0.0.1:3306. Using database "doorknob_school".
    "%PHP_EXE%" -r "try { $p = new PDO('mysql:host=127.0.0.1;port=3306', 'root', ''); $p->exec('CREATE DATABASE IF NOT EXISTS doorknob_school'); } catch (Exception $e) {}" >nul 2>nul
    
    powershell -NoProfile -Command ^
      "(Get-Content .env) |" ^
      "ForEach-Object { $_ -replace '^DB_CONNECTION=.*', 'DB_CONNECTION=mysql' } |" ^
      "ForEach-Object { $_ -replace '^DB_HOST=.*', 'DB_HOST=127.0.0.1' } |" ^
      "ForEach-Object { $_ -replace '^DB_PORT=.*', 'DB_PORT=3306' } |" ^
      "ForEach-Object { $_ -replace '^DB_DATABASE=.*', 'DB_DATABASE=doorknob_school' } |" ^
      "ForEach-Object { $_ -replace '^DB_USERNAME=.*', 'DB_USERNAME=root' } |" ^
      "ForEach-Object { $_ -replace '^DB_PASSWORD=.*', 'DB_PASSWORD=' } |" ^
      "ForEach-Object { $_ -replace '^APP_URL=.*', 'APP_URL=http://localhost:8000' } |" ^
      "Set-Content .env"
) else (
    echo [INFO] MySQL server not reachable on port 3306. Using SQLite database...
    if not exist "database\database.sqlite" (
        type nul > "database\database.sqlite"
    )
    set "DB_PATH=%CD:\=/%/database/database.sqlite"
    powershell -NoProfile -Command ^
      "(Get-Content .env) |" ^
      "ForEach-Object { $_ -replace '^DB_CONNECTION=.*', 'DB_CONNECTION=sqlite' } |" ^
      "ForEach-Object { $_ -replace '^DB_DATABASE=.*', 'DB_DATABASE=!DB_PATH!' } |" ^
      "ForEach-Object { $_ -replace '^APP_URL=.*', 'APP_URL=http://localhost:8000' } |" ^
      "Set-Content .env"
)

:: 4. APP_KEY check
findstr /b "APP_KEY=" .env | findstr /r "APP_KEY=.\+" >nul 2>nul
if %errorlevel% neq 0 (
    echo [INFO] Generating application key...
    "%PHP_EXE%" artisan key:generate --force
)

:: 5. Run migrations & seeders
echo [INFO] Running database migrations...
"%PHP_EXE%" artisan migrate --force

"%PHP_EXE%" artisan tinker --execute="exit(\DB::table('users')->count() > 0 ? 1 : 0);" >nul 2>nul
if %errorlevel% neq 0 (
    echo [INFO] First run detected - seeding default accounts...
    "%PHP_EXE%" artisan db:seed --force
)

:: 6. Clear Caches
echo [INFO] Optimizing application caches...
"%PHP_EXE%" artisan config:clear >nul 2>nul
"%PHP_EXE%" artisan route:clear >nul 2>nul
"%PHP_EXE%" artisan view:clear >nul 2>nul

:: 7. Launch browser
echo [INFO] Launching browser to http://127.0.0.1:8000 ...
start "" http://127.0.0.1:8000

echo.
echo ===============================================================================
echo   Server (Instance 1) is running at: http://127.0.0.1:8000
echo   Other computers on this network can reach it at: http://^<ANY IP^>:8000
ipconfig | findstr /i "IPv4"
echo ===============================================================================
echo   Admin Login:
echo     Email:    admin@ut.com
echo     Password: password
echo.
echo   Super Admin Login:
echo     Email:    superadmin@ut.com
echo     Password: password
echo ===============================================================================
echo   Press Ctrl+C in this terminal window to stop the server anytime.
echo ===============================================================================
echo.

set PHP_CLI_SERVER_WORKERS=4
"%PHP_EXE%" artisan serve --host=0.0.0.0 --port=8000

pause
