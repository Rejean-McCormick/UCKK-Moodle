@echo off
setlocal

REM UCKK Ops Console launcher.
REM Runs the PowerShell GUI from this folder.
REM Prefers PowerShell 7+ when available, falls back to Windows PowerShell.

set "APP_DIR=%~dp0"
set "APP_PS1=%APP_DIR%uckk_ops_gui.ps1"
set "CONFIG_JSON=%APP_DIR%uckk-ops.config.json"

if not exist "%APP_PS1%" (
    echo [ERROR] Missing file:
    echo %APP_PS1%
    pause
    exit /b 1
)

if not exist "%CONFIG_JSON%" (
    echo [ERROR] Missing config:
    echo %CONFIG_JSON%
    pause
    exit /b 1
)

where pwsh.exe >nul 2>nul
if "%ERRORLEVEL%"=="0" (
    set "PS_EXE=pwsh.exe"
) else (
    where powershell.exe >nul 2>nul
    if errorlevel 1 (
        echo [ERROR] Neither pwsh.exe nor powershell.exe was found.
        pause
        exit /b 1
    )
    set "PS_EXE=powershell.exe"
)

"%PS_EXE%" ^
  -NoProfile ^
  -ExecutionPolicy Bypass ^
  -File "%APP_PS1%" ^
  -ConfigPath "%CONFIG_JSON%" %*

set "EXITCODE=%ERRORLEVEL%"

if not "%EXITCODE%"=="0" (
    echo.
    echo [ERROR] UCKK Ops Console exited with code %EXITCODE%.
    pause
)

exit /b %EXITCODE%