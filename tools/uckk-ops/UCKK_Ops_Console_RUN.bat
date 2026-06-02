@echo off
setlocal

REM UCKK Ops Console launcher
REM Runs the PowerShell GUI from this folder.

set "APP_DIR=%~dp0"
set "APP_PS1=%APP_DIR%uckk_ops_gui.ps1"

if not exist "%APP_PS1%" (
    echo [ERROR] Missing file:
    echo %APP_PS1%
    pause
    exit /b 1
)

where powershell.exe >nul 2>nul
if errorlevel 1 (
    echo [ERROR] powershell.exe not found.
    pause
    exit /b 1
)

powershell.exe ^
  -NoProfile ^
  -ExecutionPolicy Bypass ^
  -File "%APP_PS1%" %*

set "EXITCODE=%ERRORLEVEL%"

if not "%EXITCODE%"=="0" (
    echo.
    echo [ERROR] UCKK Ops Console exited with code %EXITCODE%.
    pause
)

exit /b %EXITCODE%
