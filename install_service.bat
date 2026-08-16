@echo off
:: Registers the school app as a background Windows service using NSSM,
:: so it keeps running after reboot without anyone leaving a terminal open.
:: Download NSSM first: https://nssm.cc/download  (place nssm.exe on PATH or next to this file)

setlocal enabledelayedexpansion
cd /d "%~dp0"

set "NSSM_EXE=nssm"
if exist "%~dp0nssm.exe" set "NSSM_EXE=%~dp0nssm.exe"

where %NSSM_EXE% >nul 2>nul
if %errorlevel% neq 0 (
    echo [ERROR] nssm.exe not found. Download it from https://nssm.cc/download
    echo and place nssm.exe in this folder, then re-run this script.
    pause
    exit /b 1
)

set "PHP_EXE=C:\xampp\php\php.exe"
if not exist "%PHP_EXE%" (
    echo [ERROR] XAMPP PHP not found at %PHP_EXE%. Install XAMPP first.
    pause
    exit /b 1
)

echo [INFO] Installing "DoorKnobSchoolApp" as a Windows service...
%NSSM_EXE% install DoorKnobSchoolApp "%PHP_EXE%" "artisan serve --host=0.0.0.0 --port=8000"
%NSSM_EXE% set DoorKnobSchoolApp AppDirectory "%~dp0"
%NSSM_EXE% set DoorKnobSchoolApp AppStdout "%~dp0storage\logs\service-out.log"
%NSSM_EXE% set DoorKnobSchoolApp AppStderr "%~dp0storage\logs\service-error.log"
%NSSM_EXE% set DoorKnobSchoolApp Start SERVICE_AUTO_START
%NSSM_EXE% start DoorKnobSchoolApp

echo.
echo [OK] Service installed and started. The app will now auto-start on every reboot.
echo Manage it via: services.msc  (look for "DoorKnobSchoolApp")
echo To remove:  nssm remove DoorKnobSchoolApp confirm
pause
