# tools/uckk-ops/lib/UckkOps.Local.psm1
Set-StrictMode -Version Latest
$ErrorActionPreference = "Stop"

$CommonPath = Join-Path $PSScriptRoot "UckkOps.Common.psm1"
Import-Module $CommonPath -Force -DisableNameChecking

function Get-UckkLocalSourceRoot { Get-UckkOpsVar "LocalSourceRoot" }
function Get-UckkLocalRuntimeRoot { Get-UckkOpsVar "LocalRuntimeRoot" }
function Get-UckkLocalMoodleRoot { Get-UckkOpsVar "LocalMoodleRoot" -Default (Split-Path -Parent (Get-UckkLocalRuntimeRoot)) }

function Get-UckkLocalMoodleCliRoot {
    $configured = Get-UckkOpsVar "LocalMoodleCliRoot" -Default ""
    if (-not [string]::IsNullOrWhiteSpace($configured)) { return $configured }
    return Join-Path (Get-UckkLocalMoodleRoot) "admin/cli"
}

function Get-UckkLocalPhpExe { Get-UckkOpsVar "LocalPhpExe" -Default "php" }
function Get-UckkLocalUrl { Get-UckkOpsVar "LocalUrl" -Default "http://127.0.0.1:8000" }
function Get-UckkLocalComponents { @((Get-UckkOpsVar "UckkComponents" -Default @())) }

function Get-UckkLocalSiteProfile {
    $config = Get-UckkOpsConfig

    $siteConfig = Get-UckkConfigProperty $config "siteProfile.config" $null
    $configItems = [ordered]@{}

    if ($null -ne $siteConfig) {
        foreach ($property in $siteConfig.PSObject.Properties) {
            $configItems[$property.Name] = $property.Value
        }
    }
    else {
        $configItems["autologinguests"] = 0
        $configItems["guestloginbutton"] = 0
    }

    [pscustomobject]@{
        Fullname  = Get-UckkConfigProperty $config "siteProfile.fullname" "Univers-Cité King Klown"
        Shortname = Get-UckkConfigProperty $config "siteProfile.shortname" "UCKK"
        Config    = $configItems
    }
}

function Get-UckkLocalSiteProfileCommandPreview {
    $phpExe = Get-UckkLocalPhpExe
    $moodleRoot = Get-UckkLocalMoodleRoot
    $profile = Get-UckkLocalSiteProfile
    $configSummary = @($profile.Config.Keys | ForEach-Object { "$($_)=$($profile.Config[$_])" }) -join "; "

    [pscustomobject]@{
        Action           = "Apply UCKK site profile"
        WorkingDirectory = $moodleRoot
        Command          = $phpExe
        Arguments        = @(".uckk_site_profile_tmp.php", ".uckk_site_profile_tmp.json")
        DisplayCommand   = "$phpExe .uckk_site_profile_tmp.php .uckk_site_profile_tmp.json"
        Fullname         = $profile.Fullname
        Shortname        = $profile.Shortname
        Config           = $configSummary
        Executes         = $false
    }
}

function Resolve-UckkLocalPath {
    param(
        [Parameter(Mandatory = $true)][string]$Root,
        [Parameter(Mandatory = $true)][string]$RelativePath
    )

    return Join-UckkPath -Root $Root -Child $RelativePath
}

function ConvertTo-UckkLocalForwardSlashPath {
    param(
        [Parameter(Mandatory = $true)][string]$Path
    )

    return ($Path -replace "\\", "/")
}

function Get-UckkLocalRelativePath {
    param(
        [Parameter(Mandatory = $true)][string]$Root,
        [Parameter(Mandatory = $true)][string]$Path
    )

    $rootFull = [System.IO.Path]::GetFullPath($Root).TrimEnd([char[]]@("\", "/"))
    $pathFull = [System.IO.Path]::GetFullPath($Path).TrimEnd([char[]]@("\", "/"))

    if ($pathFull.Equals($rootFull, [System.StringComparison]::OrdinalIgnoreCase)) {
        return "."
    }

    $rootPrefix = "$rootFull$([System.IO.Path]::DirectorySeparatorChar)"

    if (-not $pathFull.StartsWith($rootPrefix, [System.StringComparison]::OrdinalIgnoreCase)) {
        throw "Path is not inside root. Root: $rootFull Path: $pathFull"
    }

    $relative = $pathFull.Substring($rootPrefix.Length)
    return ConvertTo-UckkLocalForwardSlashPath -Path $relative
}

function Assert-UckkLocalComponentPathSafe {
    param(
        [Parameter(Mandatory = $true)][string]$Component
    )

    if (
        [string]::IsNullOrWhiteSpace($Component) -or
        $Component -match '(^|[\/])\.\.([\/]|$)' -or
        [System.IO.Path]::IsPathRooted($Component)
    ) {
        throw "Unsafe component path: $Component"
    }

    return $true
}

function Get-UckkExistingLocalPath {
    param([Parameter(Mandatory = $true)][string[]]$Paths)

    foreach ($path in $Paths) {
        if (-not [string]::IsNullOrWhiteSpace($path) -and (Test-Path -LiteralPath $path)) {
            return $path
        }
    }

    return $null
}

function Get-UckkLocalMoodleCliScript {
    param([Parameter(Mandatory = $true)][string]$ScriptName)

    return Get-UckkExistingLocalPath @(
        (Join-Path (Get-UckkLocalMoodleCliRoot) $ScriptName),
        (Join-Path (Get-UckkLocalRuntimeRoot) "admin/cli/$ScriptName"),
        (Join-Path (Get-UckkLocalMoodleRoot) "admin/cli/$ScriptName")
    )
}

function Get-UckkLocalRuntimeComponentRoot {
    param(
        [Parameter(Mandatory = $true)][string]$Component
    )

    Assert-UckkLocalComponentPathSafe -Component $Component | Out-Null

    $moodleRoot = Get-UckkLocalMoodleRoot
    $runtimeRoot = Get-UckkLocalRuntimeRoot
    $componentRuntimePath = Resolve-UckkLocalPath -Root $runtimeRoot -RelativePath $Component

    return Get-UckkLocalRelativePath -Root $moodleRoot -Path $componentRuntimePath
}

function Get-UckkLocalAmdBuildRoot {
    param(
        [string]$Component = "local/uckk"
    )

    return Get-UckkLocalRuntimeComponentRoot -Component $Component
}

function Get-UckkLocalAmdBuildCommandPreview {
    param(
        [string]$Component = "local/uckk"
    )

    $root = Get-UckkLocalAmdBuildRoot -Component $Component
    $workingDirectory = Get-UckkLocalMoodleRoot
    $arguments = @("grunt", "amd", "--root=$root", "--no-color")

    [pscustomobject]@{
        Action           = "Build AMD"
        Component        = $Component
        WorkingDirectory = $workingDirectory
        Command          = "npx"
        Arguments        = $arguments
        DisplayCommand   = "npx $($arguments -join ' ')"
        Executes         = $false
    }
}

function Get-UckkLocalUpgradeCommandPreview {
    param(
        [switch]$AllowUnstable
    )

    $phpExe = Get-UckkLocalPhpExe
    $moodleRoot = Get-UckkLocalMoodleRoot
    $script = Get-UckkLocalMoodleCliScript -ScriptName "upgrade.php"

    if (-not $script) {
        throw "Local upgrade CLI not found. Checked LocalMoodleCliRoot, LocalRuntimeRoot/admin/cli, and LocalMoodleRoot/admin/cli."
    }

    $displayScript = Get-UckkLocalRelativePath -Root $moodleRoot -Path $script
    $arguments = @($script, "--non-interactive")

    if ($AllowUnstable) {
        $arguments += "--allow-unstable"
    }

    $displayArguments = @($displayScript, "--non-interactive")

    if ($AllowUnstable) {
        $displayArguments += "--allow-unstable"
    }

    [pscustomobject]@{
        Action           = "Run Moodle upgrade"
        WorkingDirectory = $moodleRoot
        Command          = $phpExe
        Arguments        = $arguments
        DisplayCommand   = "$phpExe $($displayArguments -join ' ')"
        Executes         = $false
    }
}

function Get-UckkLocalPurgeCommandPreview {
    $phpExe = Get-UckkLocalPhpExe
    $moodleRoot = Get-UckkLocalMoodleRoot
    $script = Get-UckkLocalMoodleCliScript -ScriptName "purge_caches.php"

    if (-not $script) {
        throw "Local purge CLI not found. Checked LocalMoodleCliRoot, LocalRuntimeRoot/admin/cli, and LocalMoodleRoot/admin/cli."
    }

    $displayScript = Get-UckkLocalRelativePath -Root $moodleRoot -Path $script

    [pscustomobject]@{
        Action           = "Purge Moodle caches"
        WorkingDirectory = $moodleRoot
        Command          = $phpExe
        Arguments        = @($script)
        DisplayCommand   = "$phpExe $displayScript"
        Executes         = $false
    }
}

function Test-UckkLocalPlanFlag {
    param(
        [AllowNull()][object]$Plan,
        [Parameter(Mandatory = $true)][string]$PropertyName,
        [bool]$Default = $false
    )

    if ($null -eq $Plan) {
        return $Default
    }

    if ($Plan.PSObject.Properties.Name -notcontains $PropertyName) {
        return $Default
    }

    return [bool]$Plan.$PropertyName
}

function Stop-UckkLocalAmdBuildProcesses {
    param(
        [int]$GraceSeconds = 1
    )

    $results = @()
    $names = @("node", "grunt", "rollup")

    foreach ($name in $names) {
        $processes = @(Get-Process -Name $name -ErrorAction SilentlyContinue)

        foreach ($process in $processes) {
            try {
                $id = $process.Id
                $processName = $process.ProcessName
                Stop-Process -Id $id -Force -ErrorAction Stop

                $results += [pscustomobject]@{
                    Name   = $processName
                    Id     = $id
                    Status = "stopped"
                }
            }
            catch {
                $results += [pscustomobject]@{
                    Name   = $name
                    Id     = $process.Id
                    Status = "failed"
                    Error  = $_.Exception.Message
                }
            }
        }
    }

    if ($GraceSeconds -gt 0 -and $results.Count -gt 0) {
        Start-Sleep -Seconds $GraceSeconds
    }

    return $results
}

function Test-UckkLocalPaths {
    $sourceRoot = Get-UckkLocalSourceRoot
    $runtimeRoot = Get-UckkLocalRuntimeRoot
    $phpExe = Get-UckkLocalPhpExe
    $moodleRoot = Get-UckkLocalMoodleRoot
    $cliRoot = Get-UckkLocalMoodleCliRoot
    $purge = Get-UckkLocalMoodleCliScript -ScriptName "purge_caches.php"
    $upgrade = Get-UckkLocalMoodleCliScript -ScriptName "upgrade.php"

    @(
        [pscustomobject]@{ Name = "Local source root"; Path = $sourceRoot; Exists = (Test-Path -LiteralPath $sourceRoot) },
        [pscustomobject]@{ Name = "Local Moodle runtime/web root"; Path = $runtimeRoot; Exists = (Test-Path -LiteralPath $runtimeRoot) },
        [pscustomobject]@{ Name = "Local Moodle root"; Path = $moodleRoot; Exists = (Test-Path -LiteralPath $moodleRoot) },
        [pscustomobject]@{ Name = "Local Moodle CLI root"; Path = $cliRoot; Exists = (Test-Path -LiteralPath $cliRoot) },
        [pscustomobject]@{ Name = "Moodle config.php"; Path = (Join-Path $runtimeRoot "config.php"); Exists = (Test-Path -LiteralPath (Join-Path $runtimeRoot "config.php")) },
        [pscustomobject]@{ Name = "Moodle purge_caches.php"; Path = $purge; Exists = [bool]$purge },
        [pscustomobject]@{ Name = "Moodle upgrade.php"; Path = $upgrade; Exists = [bool]$upgrade },
        [pscustomobject]@{ Name = "PHP executable"; Path = $phpExe; Exists = [bool](Get-Command $phpExe -ErrorAction SilentlyContinue) }
    )
}

function Assert-UckkLocalReady {
    $failed = @(Test-UckkLocalPaths | Where-Object { -not $_.Exists })

    if ($failed.Count -gt 0) {
        $message = ($failed | ForEach-Object { "$($_.Name): $($_.Path)" }) -join "`n"
        throw "Local environment is not ready:`n$message"
    }

    return $true
}

function Invoke-UckkLocalAmdBuild {
    param(
        [string]$Component = "local/uckk",
        [int]$Retries = 1,
        [switch]$SkipProcessCleanup
    )

    Assert-UckkLocalReady | Out-Null
    Assert-UckkLocalComponentPathSafe -Component $Component | Out-Null

    if (-not (Get-Command "npx" -ErrorAction SilentlyContinue)) {
        throw "npx introuvable. Installe Node.js/npm ou lance depuis un environnement où npx est disponible."
    }

    $preview = Get-UckkLocalAmdBuildCommandPreview -Component $Component
    $attempt = 0
    $maxAttempts = [Math]::Max(1, $Retries + 1)
    $lastExitCode = $null

    while ($attempt -lt $maxAttempts) {
        $attempt++

        if (-not $SkipProcessCleanup.IsPresent) {
            Stop-UckkLocalAmdBuildProcesses | Out-Null
        }

        $arguments = @($preview.Arguments)

        Push-Location $preview.WorkingDirectory
        try {
            & $preview.Command @arguments
            $lastExitCode = $LASTEXITCODE
        }
        finally {
            Pop-Location
        }

        if ($lastExitCode -eq 0) {
            return [pscustomobject]@{
                Status           = "completed"
                Component        = $Component
                Attempts         = $attempt
                WorkingDirectory = $preview.WorkingDirectory
                Command          = $preview.DisplayCommand
            }
        }

        if ($attempt -lt $maxAttempts) {
            Start-Sleep -Seconds 2
        }
    }

    throw "Build AMD echoue pour $Component. Exit code: $lastExitCode"
}

function Invoke-UckkLocalMoodleUpgrade {
    param(
        [switch]$AllowUnstable
    )

    Assert-UckkLocalReady | Out-Null

    $phpExe = Get-UckkLocalPhpExe
    $script = Get-UckkLocalMoodleCliScript -ScriptName "upgrade.php"
    $moodleRoot = Get-UckkLocalMoodleRoot

    if (-not $script) {
        throw "Local upgrade CLI not found. Checked LocalMoodleCliRoot, LocalRuntimeRoot/admin/cli, and LocalMoodleRoot/admin/cli."
    }

    $arguments = @($script, "--non-interactive")

    if ($AllowUnstable) {
        $arguments += "--allow-unstable"
    }

    Push-Location $moodleRoot
    try {
        & $phpExe @arguments
        if ($LASTEXITCODE -ne 0) {
            throw "upgrade.php failed with exit code $LASTEXITCODE"
        }
    }
    finally {
        Pop-Location
    }

    [pscustomobject]@{
        Status        = "completed"
        Script        = $script
        AllowUnstable = [bool]$AllowUnstable
    }
}

function Sync-UckkLocalComponent {
    param(
        [Parameter(Mandatory = $true)][string]$Component
    )

    Assert-UckkLocalReady | Out-Null
    Assert-UckkLocalComponentPathSafe -Component $Component | Out-Null

    $sourcePath = Resolve-UckkLocalPath -Root (Get-UckkLocalSourceRoot) -RelativePath $Component
    $targetPath = Resolve-UckkLocalPath -Root (Get-UckkLocalRuntimeRoot) -RelativePath $Component
    $targetParent = Split-Path $targetPath -Parent

    if (-not (Test-Path -LiteralPath $sourcePath)) {
        throw "Source component not found: $sourcePath"
    }

    if (-not (Test-Path -LiteralPath $targetParent)) {
        New-Item -ItemType Directory -Path $targetParent -Force | Out-Null
    }

    if (Test-Path -LiteralPath $targetPath) {
        Remove-Item -LiteralPath $targetPath -Recurse -Force
    }

    Copy-Item -LiteralPath $sourcePath -Destination $targetPath -Recurse -Force

    [pscustomobject]@{
        Component = $Component
        Source    = $sourcePath
        Target    = $targetPath
        Status    = "synced"
    }
}

function Sync-UckkLocalAll {
    Assert-UckkLocalReady | Out-Null

    $results = @()

    foreach ($component in (Get-UckkLocalComponents)) {
        try {
            $results += Sync-UckkLocalComponent -Component $component
        }
        catch {
            $results += [pscustomobject]@{
                Component = $component
                Source    = $null
                Target    = $null
                Status    = "failed"
                Error     = $_.Exception.Message
            }
        }
    }

    return $results
}

function Sync-UckkLocalSourceToRuntime {
    Sync-UckkLocalAll
}

function Invoke-UckkLocalPurgeCaches {
    Assert-UckkLocalReady | Out-Null

    $phpExe = Get-UckkLocalPhpExe
    $script = Get-UckkLocalMoodleCliScript -ScriptName "purge_caches.php"

    if (-not $script) {
        throw "Local purge CLI not found. Checked LocalMoodleCliRoot, LocalRuntimeRoot/admin/cli, and LocalMoodleRoot/admin/cli."
    }

    Push-Location (Split-Path -Parent $script)
    try {
        & $phpExe $script
        if ($LASTEXITCODE -ne 0) {
            throw "purge_caches.php failed with exit code $LASTEXITCODE"
        }
    }
    finally {
        Pop-Location
    }

    "Local Moodle caches purged."
}

function Clear-UckkLocalCaches {
    Invoke-UckkLocalPurgeCaches
}

function Invoke-UckkLocalApplySiteProfile {
    Assert-UckkLocalReady | Out-Null

    $phpExe = Get-UckkLocalPhpExe
    $moodleRoot = Get-UckkLocalMoodleRoot
    $profile = Get-UckkLocalSiteProfile

    $payloadConfig = [ordered]@{}
    foreach ($key in $profile.Config.Keys) {
        $payloadConfig[$key] = $profile.Config[$key]
    }

    $payload = [ordered]@{
        fullname  = $profile.Fullname
        shortname = $profile.Shortname
        config    = $payloadConfig
    }

    $jsonPath = Join-Path $moodleRoot ".uckk_site_profile_tmp.json"
    $phpPath = Join-Path $moodleRoot ".uckk_site_profile_tmp.php"

    $phpScript = @'
<?php
define('CLI_SCRIPT', true);
require_once(__DIR__ . '/config.php');
global $DB;

if ($argc < 2) {
    fwrite(STDERR, "Missing site profile JSON path.\n");
    exit(1);
}

$json = file_get_contents($argv[1]);
$payload = json_decode($json, true);

if (!is_array($payload)) {
    fwrite(STDERR, "Invalid site profile JSON.\n");
    exit(1);
}

if (isset($payload['config']) && is_array($payload['config'])) {
    foreach ($payload['config'] as $name => $value) {
        set_config((string)$name, $value);
    }
}

$site = get_site();
$changedsite = false;

if (array_key_exists('fullname', $payload) && trim((string)$payload['fullname']) !== '') {
    $site->fullname = (string)$payload['fullname'];
    $changedsite = true;
}

if (array_key_exists('shortname', $payload) && trim((string)$payload['shortname']) !== '') {
    $site->shortname = (string)$payload['shortname'];
    $changedsite = true;
}

if ($changedsite) {
    $site->timemodified = time();
    $DB->update_record('course', $site);
}

purge_all_caches();

echo "UCKK site profile applied.\n";
echo "fullname=" . $site->fullname . "\n";
echo "shortname=" . $site->shortname . "\n";

if (isset($payload['config']) && is_array($payload['config'])) {
    foreach ($payload['config'] as $name => $value) {
        echo $name . "=" . $value . "\n";
    }
}
'@

    try {
        $payload | ConvertTo-Json -Depth 8 | Set-Content -LiteralPath $jsonPath -Encoding UTF8
        Set-Content -LiteralPath $phpPath -Value $phpScript -Encoding UTF8

        Push-Location $moodleRoot
        try {
            & $phpExe $phpPath $jsonPath
            if ($LASTEXITCODE -ne 0) {
                throw "UCKK local site profile failed with exit code $LASTEXITCODE"
            }
        }
        finally {
            Pop-Location
        }
    }
    finally {
        if (Test-Path -LiteralPath $jsonPath) {
            Remove-Item -LiteralPath $jsonPath -Force
        }
        if (Test-Path -LiteralPath $phpPath) {
            Remove-Item -LiteralPath $phpPath -Force
        }
    }

    [pscustomobject]@{
        Status    = "completed"
        Fullname  = $profile.Fullname
        Shortname = $profile.Shortname
        Config    = $payloadConfig
    }
}

function Start-UckkLocalMoodleServer {
    param([int]$Port = 8000)

    Assert-UckkLocalReady | Out-Null

    $runtimeRoot = Get-UckkLocalRuntimeRoot
    $phpExe = Get-UckkLocalPhpExe

    $process = Start-Process -FilePath $phpExe -ArgumentList @("-S", "127.0.0.1:$Port", "-t", ".") -WorkingDirectory $runtimeRoot -PassThru

    [pscustomobject]@{
        ProcessId = $process.Id
        Url       = "http://127.0.0.1:$Port"
        Root      = $runtimeRoot
        Status    = "started"
    }
}

function Start-UckkLocalMoodle {
    Start-UckkLocalMoodleServer
}

function Stop-UckkLocalMoodleServer {
    param([int]$Port = 8000)

    $connections = Get-NetTCPConnection -LocalPort $Port -State Listen -ErrorAction SilentlyContinue

    if (-not $connections) {
        return "No local Moodle server found on port $Port."
    }

    $processIds = $connections | Select-Object -ExpandProperty OwningProcess -Unique

    foreach ($pidValue in $processIds) {
        Stop-Process -Id $pidValue -Force
        "Stopped local process $pidValue on port $Port."
    }
}

function Stop-UckkLocalMoodle {
    Stop-UckkLocalMoodleServer
}

function Invoke-UckkLocalPrepare {
    param(
        [AllowNull()][object]$Plan = $null,
        [string[]]$AmdComponents = @("local/uckk"),
        [switch]$SkipSiteProfile,
        [switch]$AllowUnstable
    )

    Assert-UckkLocalReady | Out-Null

    $hasPlan = ($null -ne $Plan)
    $needsLocalSync = Test-UckkLocalPlanFlag -Plan $Plan -PropertyName "NeedsLocalSync" -Default (-not $hasPlan)
    $needsAmdBuild = Test-UckkLocalPlanFlag -Plan $Plan -PropertyName "NeedsAmdBuild" -Default $false
    $needsMoodleUpgrade = Test-UckkLocalPlanFlag -Plan $Plan -PropertyName "NeedsMoodleUpgrade" -Default $false
    $needsPurgeCaches = Test-UckkLocalPlanFlag -Plan $Plan -PropertyName "NeedsPurgeCaches" -Default (-not $hasPlan)

    if ($needsAmdBuild) {
        $needsPurgeCaches = $true
    }

    if ($needsMoodleUpgrade) {
        $needsPurgeCaches = $true
    }

    $syncResults = @()
    $amdResults = @()
    $upgradeResult = $null
    $siteProfileResult = $null
    $purgeResult = $null

    if ($needsLocalSync) {
        $syncResults = @(Sync-UckkLocalSourceToRuntime)
    }

    if ($needsAmdBuild) {
        foreach ($component in $AmdComponents) {
            $amdResults += Invoke-UckkLocalAmdBuild -Component $component
        }
    }

    if ($needsMoodleUpgrade) {
        $upgradeResult = Invoke-UckkLocalMoodleUpgrade -AllowUnstable:$AllowUnstable
    }

    if (-not $SkipSiteProfile.IsPresent) {
        $siteProfileResult = Invoke-UckkLocalApplySiteProfile
    }

    if ($needsPurgeCaches) {
        $purgeResult = Invoke-UckkLocalPurgeCaches
    }

    [pscustomobject]@{
        Status              = "completed"
        NeedsLocalSync      = $needsLocalSync
        NeedsAmdBuild       = $needsAmdBuild
        NeedsMoodleUpgrade  = $needsMoodleUpgrade
        NeedsPurgeCaches    = $needsPurgeCaches
        Synced              = @($syncResults | Where-Object { $_.Status -eq "synced" }).Count
        FailedSync          = @($syncResults | Where-Object { $_.Status -eq "failed" }).Count
        SyncResults         = $syncResults
        AmdBuildResults     = $amdResults
        MoodleUpgrade       = $upgradeResult
        SiteProfile         = $siteProfileResult
        PurgeCaches         = $purgeResult
    }
}

function Invoke-UckkLocalFullSync {
    $sync = Sync-UckkLocalAll
    $profile = Invoke-UckkLocalApplySiteProfile
    Invoke-UckkLocalPurgeCaches | Out-Null

    [pscustomobject]@{
        Status      = "completed"
        Synced      = @($sync | Where-Object { $_.Status -eq "synced" }).Count
        Failed      = @($sync | Where-Object { $_.Status -eq "failed" }).Count
        SiteProfile = $profile
        Components  = $sync
    }
}

function Get-UckkLocalStatus {
    [pscustomobject]@{
        SourceRoot   = Get-UckkLocalSourceRoot
        RuntimeRoot  = Get-UckkLocalRuntimeRoot
        MoodleRoot   = Get-UckkLocalMoodleRoot
        CliRoot      = Get-UckkLocalMoodleCliRoot
        PhpExe       = Get-UckkLocalPhpExe
        LocalUrl     = Get-UckkLocalUrl
        SiteProfile  = Get-UckkLocalSiteProfile
        Components   = Get-UckkLocalComponents
        Checks       = Test-UckkLocalPaths
    }
}

Export-ModuleMember -Function `
    Get-UckkLocalSourceRoot, `
    Get-UckkLocalRuntimeRoot, `
    Get-UckkLocalMoodleRoot, `
    Get-UckkLocalMoodleCliRoot, `
    Get-UckkLocalPhpExe, `
    Get-UckkLocalUrl, `
    Get-UckkLocalComponents, `
    Get-UckkLocalSiteProfile, `
    Get-UckkLocalSiteProfileCommandPreview, `
    Resolve-UckkLocalPath, `
    ConvertTo-UckkLocalForwardSlashPath, `
    Get-UckkLocalRelativePath, `
    Get-UckkExistingLocalPath, `
    Get-UckkLocalMoodleCliScript, `
    Get-UckkLocalRuntimeComponentRoot, `
    Get-UckkLocalAmdBuildRoot, `
    Get-UckkLocalAmdBuildCommandPreview, `
    Get-UckkLocalUpgradeCommandPreview, `
    Get-UckkLocalPurgeCommandPreview, `
    Test-UckkLocalPlanFlag, `
    Stop-UckkLocalAmdBuildProcesses, `
    Test-UckkLocalPaths, `
    Assert-UckkLocalReady, `
    Invoke-UckkLocalAmdBuild, `
    Invoke-UckkLocalMoodleUpgrade, `
    Sync-UckkLocalComponent, `
    Sync-UckkLocalAll, `
    Sync-UckkLocalSourceToRuntime, `
    Invoke-UckkLocalPurgeCaches, `
    Clear-UckkLocalCaches, `
    Invoke-UckkLocalApplySiteProfile, `
    Start-UckkLocalMoodleServer, `
    Start-UckkLocalMoodle, `
    Stop-UckkLocalMoodleServer, `
    Stop-UckkLocalMoodle, `
    Invoke-UckkLocalPrepare, `
    Invoke-UckkLocalFullSync, `
    Get-UckkLocalStatus