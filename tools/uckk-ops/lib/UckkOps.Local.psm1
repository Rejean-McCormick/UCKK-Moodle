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

function Resolve-UckkLocalPath {
    param(
        [Parameter(Mandatory = $true)][string]$Root,
        [Parameter(Mandatory = $true)][string]$RelativePath
    )
    return Join-UckkPath -Root $Root -Child $RelativePath
}

function Get-UckkExistingLocalPath {
    param([Parameter(Mandatory = $true)][string[]]$Paths)
    foreach ($path in $Paths) {
        if (-not [string]::IsNullOrWhiteSpace($path) -and (Test-Path -LiteralPath $path)) { return $path }
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

function Test-UckkLocalPaths {
    $sourceRoot = Get-UckkLocalSourceRoot
    $runtimeRoot = Get-UckkLocalRuntimeRoot
    $phpExe = Get-UckkLocalPhpExe
    $moodleRoot = Get-UckkLocalMoodleRoot
    $cliRoot = Get-UckkLocalMoodleCliRoot
    $purge = Get-UckkLocalMoodleCliScript -ScriptName "purge_caches.php"
    $upgrade = Get-UckkLocalMoodleCliScript -ScriptName "upgrade.php"

    @(
        [pscustomobject]@{ Name = "Local source root";          Path = $sourceRoot;  Exists = (Test-Path -LiteralPath $sourceRoot) },
        [pscustomobject]@{ Name = "Local Moodle runtime/web root"; Path = $runtimeRoot; Exists = (Test-Path -LiteralPath $runtimeRoot) },
        [pscustomobject]@{ Name = "Local Moodle root"; Path = $moodleRoot; Exists = (Test-Path -LiteralPath $moodleRoot) },
        [pscustomobject]@{ Name = "Local Moodle CLI root"; Path = $cliRoot; Exists = (Test-Path -LiteralPath $cliRoot) },
        [pscustomobject]@{ Name = "Moodle config.php";         Path = (Join-Path $runtimeRoot "config.php"); Exists = (Test-Path -LiteralPath (Join-Path $runtimeRoot "config.php")) },
        [pscustomobject]@{ Name = "Moodle purge_caches.php";   Path = $purge; Exists = [bool]$purge },
        [pscustomobject]@{ Name = "Moodle upgrade.php";        Path = $upgrade; Exists = [bool]$upgrade },
        [pscustomobject]@{ Name = "PHP executable";            Path = $phpExe; Exists = [bool](Get-Command $phpExe -ErrorAction SilentlyContinue) }
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

function Sync-UckkLocalComponent {
    param(
        [Parameter(Mandatory = $true)][string]$Component
    )

    Assert-UckkLocalReady | Out-Null

    if ([string]::IsNullOrWhiteSpace($Component) -or $Component -match '(^|[\/])\.\.([\/]|$)' -or [System.IO.Path]::IsPathRooted($Component)) {
        throw "Unsafe component path: $Component"
    }

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

function Sync-UckkLocalSourceToRuntime { Sync-UckkLocalAll }

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

function Clear-UckkLocalCaches { Invoke-UckkLocalPurgeCaches }

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

function Start-UckkLocalMoodle { Start-UckkLocalMoodleServer }

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

function Stop-UckkLocalMoodle { Stop-UckkLocalMoodleServer }

function Invoke-UckkLocalFullSync {
    $sync = Sync-UckkLocalAll
    Invoke-UckkLocalPurgeCaches | Out-Null

    [pscustomobject]@{
        Status     = "completed"
        Synced     = @($sync | Where-Object { $_.Status -eq "synced" }).Count
        Failed     = @($sync | Where-Object { $_.Status -eq "failed" }).Count
        Components = $sync
    }
}

function Get-UckkLocalStatus {
    [pscustomobject]@{
        SourceRoot  = Get-UckkLocalSourceRoot
        RuntimeRoot = Get-UckkLocalRuntimeRoot
        MoodleRoot  = Get-UckkLocalMoodleRoot
        CliRoot     = Get-UckkLocalMoodleCliRoot
        PhpExe      = Get-UckkLocalPhpExe
        LocalUrl    = Get-UckkLocalUrl
        Components  = Get-UckkLocalComponents
        Checks      = Test-UckkLocalPaths
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
    Resolve-UckkLocalPath, `
    Get-UckkExistingLocalPath, `
    Get-UckkLocalMoodleCliScript, `
    Test-UckkLocalPaths, `
    Assert-UckkLocalReady, `
    Sync-UckkLocalComponent, `
    Sync-UckkLocalAll, `
    Sync-UckkLocalSourceToRuntime, `
    Invoke-UckkLocalPurgeCaches, `
    Clear-UckkLocalCaches, `
    Start-UckkLocalMoodleServer, `
    Start-UckkLocalMoodle, `
    Stop-UckkLocalMoodleServer, `
    Stop-UckkLocalMoodle, `
    Invoke-UckkLocalFullSync, `
    Get-UckkLocalStatus
