@echo off
:: Nightly backup: MySQL dump + storage folder zip.
:: Set up once via Windows Task Scheduler to run this daily, e.g. 2 AM.
:: (Task Scheduler > Create Task > Trigger: Daily > Action: run this file)

setlocal enabledelayedexpansion
cd /d "%~dp0"

set "MYSQLDUMP_EXE=C:\xampp\mysql\bin\mysqldump.exe"
set "BACKUP_DIR=%~dp0storage\backups"
set "TIMESTAMP=%date:~-4%-%date:~4,2%-%date:~7,2%_%time:~0,2%-%time:~3,2%"
set "TIMESTAMP=%TIMESTAMP: =0%"

if not exist "%BACKUP_DIR%" mkdir "%BACKUP_DIR%"

if not exist "%MYSQLDUMP_EXE%" (
    echo [ERROR] mysqldump not found at %MYSQLDUMP_EXE%
    exit /b 1
)

echo [INFO] Dumping database...
"%MYSQLDUMP_EXE%" -h 127.0.0.1 -P 3306 -u root doorknob_school > "%BACKUP_DIR%\db_%TIMESTAMP%.sql"

echo [INFO] Zipping storage folder (uploaded photos, receipts, logs)...
powershell -NoProfile -Command "Compress-Archive -Path '%~dp0storage\app' -DestinationPath '%BACKUP_DIR%\storage_%TIMESTAMP%.zip' -Force"

:: Keep only the last 14 days of local backups
forfiles /p "%BACKUP_DIR%" /m *.sql /d -14 /c "cmd /c del @path" 2>nul
forfiles /p "%BACKUP_DIR%" /m *.zip /d -14 /c "cmd /c del @path" 2>nul

echo [OK] Backup complete: %BACKUP_DIR%
:: TODO (optional, later): upload db_%TIMESTAMP%.sql and storage_%TIMESTAMP%.zip
:: to a cloud location (Google Drive/S3) via rclone or similar, for offsite copies.
