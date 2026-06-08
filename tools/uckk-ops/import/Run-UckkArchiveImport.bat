@echo off
setlocal EnableExtensions EnableDelayedExpansion

REM ============================================================
REM UCKK Archive Media Import Launcher v8
REM
REM No config.php guessing.
REM Moodle root is explicit and must contain the real Moodle config.php.
REM The PHP importer is repo-side, beside this .bat and .ps1.
REM
REM Usage:
REM   Run-UckkArchiveImport.bat
REM   Run-UckkArchiveImport.bat dryrun
REM   Run-UckkArchiveImport.bat apply
REM   Run-UckkArchiveImport.bat dryrun 42
REM   Run-UckkArchiveImport.bat apply 42
REM   Run-UckkArchiveImport.bat dryrun 42 "C:\path\to\moodle-root"
REM   Run-UckkArchiveImport.bat apply 42 "C:\path\to\moodle-root"
REM ============================================================

REM --- Fixed local paths ---------------------------------------
set "UCKK_REPO=C:\mycode\UCKK\uckk-moodle"
set "MOODLE_ROOT=C:\mycode\UCKK\moodle\moodle"
set "INVENTORY_PATH=C:\mycode\UCKK\uckk-import\uckkarchive\uckk_inventory.json"
set "ORIGINALS_DIR=C:\mycode\UCKK\uckk-import\uckkarchive\originals"

REM Prefer CMID. If unknown, set CMID=0 and set ARCHIVE_ID instead.
set "CMID=42"
set "ARCHIVE_ID=0"

REM Usually these can stay as-is if php and pwsh are in PATH.
set "PHP_PATH=php"
set "PWSH_PATH=pwsh"

REM Optional flags: 1 = enabled, 0 = disabled.
set "UPDATE_METADATA=0"
set "FORCE_NEW_VERSION=0"
set "ALLOW_MISSING_FILES=0"
set "ALLOW_SYSTEM_CONTEXT=0"
REM -------------------------------------------------------------

REM Mode: default DryRun. Pass "apply" as first argument for real import.
set "RUN_MODE=DryRun"
if /I "%~1"=="apply" set "RUN_MODE=Apply"
if /I "%~1"=="dryrun" set "RUN_MODE=DryRun"

REM Optional second argument overrides CMID.
if not "%~2"=="" set "CMID=%~2"

REM Optional third argument explicitly overrides MOODLE_ROOT. No search is performed.
if not "%~3"=="" set "MOODLE_ROOT=%~3"

REM Resolve script directory. The importer lives beside this .bat.
set "SCRIPT_DIR=%~dp0"
set "SCRIPT_PS1=%SCRIPT_DIR%Import-UckkArchiveMedia.ps1"
set "SCRIPT_PHP=%SCRIPT_DIR%import_uckkarchive_media.php"

set "CONTEXT_ARGS="
if not "%CMID%"=="0" set "CONTEXT_ARGS=-CmId %CMID%"
if "%CMID%"=="0" if not "%ARCHIVE_ID%"=="0" set "CONTEXT_ARGS=-ArchiveId %ARCHIVE_ID%"

set "EXTRA_ARGS="
if "%UPDATE_METADATA%"=="1" set "EXTRA_ARGS=!EXTRA_ARGS! -UpdateMetadata"
if "%FORCE_NEW_VERSION%"=="1" set "EXTRA_ARGS=!EXTRA_ARGS! -ForceNewVersion"
if "%ALLOW_MISSING_FILES%"=="1" set "EXTRA_ARGS=!EXTRA_ARGS! -AllowMissingFiles"
if "%ALLOW_SYSTEM_CONTEXT%"=="1" set "EXTRA_ARGS=!EXTRA_ARGS! -AllowSystemContext"

cls
echo ============================================================
echo UCKK Archive Media Import
echo ============================================================
echo Mode:          %RUN_MODE%
echo UCKK repo:     %UCKK_REPO%
echo Moodle root:   %MOODLE_ROOT%
echo Inventory:     %INVENTORY_PATH%
echo Originals:     %ORIGINALS_DIR%
echo Importer PHP:  %SCRIPT_PHP%
echo CMID:          %CMID%
echo Archive ID:    %ARCHIVE_ID%
echo.

if "%CONTEXT_ARGS%"=="" (
  echo ERROR: Aucun contexte Moodle configure.
  echo Configure CMID ou ARCHIVE_ID dans ce .bat.
  echo.
  pause
  exit /b 1
)

where "%PWSH_PATH%" >nul 2>nul
if errorlevel 1 (
  echo ERROR: PowerShell 7 introuvable: %PWSH_PATH%
  echo Installe PowerShell 7 ou ajuste PWSH_PATH dans ce .bat.
  echo.
  pause
  exit /b 1
)

where "%PHP_PATH%" >nul 2>nul
if errorlevel 1 (
  echo ERROR: PHP introuvable: %PHP_PATH%
  echo Ajoute PHP au PATH ou ajuste PHP_PATH dans ce .bat.
  echo.
  pause
  exit /b 1
)

if not exist "%SCRIPT_PS1%" (
  echo ERROR: Script PowerShell introuvable:
  echo %SCRIPT_PS1%
  echo.
  pause
  exit /b 1
)

if not exist "%SCRIPT_PHP%" (
  echo ERROR: Script PHP import introuvable:
  echo %SCRIPT_PHP%
  echo.
  echo Les 3 fichiers doivent etre dans le meme dossier:
  echo   Run-UckkArchiveImport.bat
  echo   Import-UckkArchiveMedia.ps1
  echo   import_uckkarchive_media.php
  echo.
  pause
  exit /b 1
)

findstr /C:"UCKK_IMPORT_WRAPPER_VERSION=8" "%SCRIPT_PS1%" >nul 2>nul
if errorlevel 1 (
  echo ERROR: Import-UckkArchiveMedia.ps1 est trop vieux ou ne correspond pas a ce .bat.
  echo Remplace aussi le .ps1 avec la version v8 du paquet.
  echo Chemin detecte:
  echo %SCRIPT_PS1%
  echo.
  pause
  exit /b 1
)

if not exist "%MOODLE_ROOT%\config.php" (
  echo ERROR: config.php introuvable dans MOODLE_ROOT:
  echo %MOODLE_ROOT%
  echo.
  echo Chemin attendu localement:
  echo C:\mycode\UCKK\moodle\moodle\config.php
  echo.
  echo Pour utiliser un autre chemin explicite:
  echo Run-UckkArchiveImport.bat dryrun %CMID% "C:\chemin\du\dossier\qui\contient\config.php"
  echo.
  pause
  exit /b 1
)

REM Refuse known false roots, even if they contain a config.php.
echo %MOODLE_ROOT% | findstr /I /C:"\.github" /C:"\archives\" /C:"\migration-backups\" /C:"\theme\" /C:"\local\" /C:"\mod\" >nul
if not errorlevel 1 (
  echo ERROR: MOODLE_ROOT pointe vers un dossier refuse:
  echo %MOODLE_ROOT%
  echo.
  echo Utilise le dossier Moodle racine, par exemple:
  echo C:\mycode\UCKK\moodle\moodle
  echo.
  pause
  exit /b 1
)

findstr /L /C:"$CFG->dbtype" "%MOODLE_ROOT%\config.php" >nul 2>nul
if errorlevel 1 (
  echo ERROR: Le config.php trouve n'est pas le vrai config.php Moodle.
  echo Chemin refuse:
  echo %MOODLE_ROOT%\config.php
  echo.
  echo Le vrai fichier doit contenir ^$CFG-^>dbtype.
  echo.
  pause
  exit /b 1
)

if not exist "%INVENTORY_PATH%" (
  echo ERROR: Inventaire introuvable:
  echo %INVENTORY_PATH%
  echo.
  pause
  exit /b 1
)

if not exist "%ORIGINALS_DIR%" (
  echo ERROR: Dossier des originaux introuvable:
  echo %ORIGINALS_DIR%
  echo.
  pause
  exit /b 1
)

if /I "%RUN_MODE%"=="Apply" (
  echo ATTENTION: Mode Apply = import reel dans Moodle.
  echo Appuie sur Ctrl+C pour annuler, ou une touche pour continuer.
  pause >nul
)

"%PWSH_PATH%" -NoProfile -ExecutionPolicy Bypass -File "%SCRIPT_PS1%" ^
  -MoodleRoot "%MOODLE_ROOT%" ^
  -InventoryPath "%INVENTORY_PATH%" ^
  -OriginalsDir "%ORIGINALS_DIR%" ^
  -Mode "%RUN_MODE%" ^
  -PhpPath "%PHP_PATH%" ^
  -ImporterPath "%SCRIPT_PHP%" ^
  %CONTEXT_ARGS% ^
  %EXTRA_ARGS%

set "EXITCODE=%ERRORLEVEL%"
echo.
echo ============================================================
if "%EXITCODE%"=="0" (
  echo Termine avec succes.
) else (
  echo Termine avec erreur. Code: %EXITCODE%
)
echo ============================================================
pause
exit /b %EXITCODE%
