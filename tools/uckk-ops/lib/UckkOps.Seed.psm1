# tools/uckk-ops/lib/UckkOps.Seed.psm1
Set-StrictMode -Version Latest
$ErrorActionPreference = "Stop"

$CommonPath = Join-Path $PSScriptRoot "UckkOps.Common.psm1"
Import-Module $CommonPath -Force -DisableNameChecking

function Get-UckkSeedAvailablePresets {
    @((Get-UckkOpsVar "SeedPresets" -Default @("categories", "programs", "pathways", "courses", "cohorts", "competencies", "badges")))
}

function Assert-UckkSeedPreset {
    param([Parameter(Mandatory = $true)][string[]]$Preset)

    $allowed = Get-UckkSeedAvailablePresets
    foreach ($item in $Preset) {
        if ($allowed -notcontains $item) {
            throw "Invalid seed preset '$item'. Allowed presets: $($allowed -join ', ')"
        }
    }
}

function Get-UckkSeedMode {
    param([switch]$DryRun, [switch]$Apply)
    if ($Apply) { return "Apply" }
    return "DryRun"
}

function Get-UckkSeedCliLocalPath {
    $runtimeRoot = Get-UckkOpsVar "LocalRuntimeRoot"
    $seedCliRelative = Get-UckkOpsVar "SeedCliRelative" -Default "admin/tool/uckkseed/cli/seed.php"
    return Join-Path $runtimeRoot ($seedCliRelative -replace "/", [IO.Path]::DirectorySeparatorChar)
}

function Invoke-UckkSeedLocal {
    [CmdletBinding(SupportsShouldProcess = $true)]
    param(
        [string[]]$Preset = @("categories"),
        [switch]$DryRun,
        [switch]$Apply,
        [switch]$Json
    )

    Assert-UckkSeedPreset -Preset $Preset

    $mode = Get-UckkSeedMode -DryRun:$DryRun -Apply:$Apply
    $runtimeRoot = Get-UckkOpsVar "LocalRuntimeRoot"
    $phpExe = Get-UckkOpsVar "LocalPhpExe" -Default "php"
    $presetPath = Get-UckkOpsVar "SeedPresetPathLocal"
    $seedCli = Get-UckkSeedCliLocalPath

    if (-not (Test-Path -LiteralPath $runtimeRoot)) { throw "Local runtime root not found: $runtimeRoot" }
    if (-not (Test-Path -LiteralPath $presetPath)) { throw "Local seed preset path not found: $presetPath" }
    if (-not (Test-Path -LiteralPath $seedCli)) { throw "Local seed CLI not found: $seedCli" }

    $presetArg = $Preset -join ","
    $seedArgs = @($seedCli, "--presetpath=$presetPath", "--preset=$presetArg")
    if ($mode -eq "DryRun") { $seedArgs += "--dry-run" } else { $seedArgs += "--force" }
    if ($Json) { $seedArgs += "--json" }

    $label = "local seed $mode for preset(s): $presetArg"
    if ($mode -eq "Apply" -and -not $PSCmdlet.ShouldProcess($label, "Modify local Moodle database")) { return }

    Push-Location $runtimeRoot
    try {
        & $phpExe @seedArgs
        if ($LASTEXITCODE -ne 0) { throw "Local seed failed with exit code $LASTEXITCODE." }
    }
    finally {
        Pop-Location
    }

    "Local seed completed: $label"
}

function Invoke-UckkSeedServer {
    [CmdletBinding(SupportsShouldProcess = $true)]
    param(
        [string[]]$Preset = @("categories"),
        [switch]$DryRun,
        [switch]$Apply,
        [switch]$Json
    )

    Assert-UckkSeedPreset -Preset $Preset

    $mode = Get-UckkSeedMode -DryRun:$DryRun -Apply:$Apply
    $sshTarget = Get-UckkOpsVar "ServerSshTarget"
    $runtimeRoot = Get-UckkOpsVar "ServerRuntimeRoot"
    $presetPath = Get-UckkOpsVar "SeedPresetPathServer"
    $seedCliRelative = Get-UckkOpsVar "SeedCliRelative" -Default "admin/tool/uckkseed/cli/seed.php"

    if ([string]::IsNullOrWhiteSpace($sshTarget)) { throw "ServerSshTarget is not defined." }
    if ([string]::IsNullOrWhiteSpace($runtimeRoot)) { throw "ServerRuntimeRoot is not defined." }
    if ([string]::IsNullOrWhiteSpace($presetPath)) { throw "SeedPresetPathServer is not defined." }

    $presetArg = $Preset -join ","
    $modeArg = if ($mode -eq "DryRun") { "--dry-run" } else { "--force" }
    $jsonArg = if ($Json) { " --json" } else { "" }
    $seedCli = "$runtimeRoot/$seedCliRelative"
    $label = "server seed $mode for preset(s): $presetArg"

    if ($mode -eq "Apply" -and -not $PSCmdlet.ShouldProcess($label, "Modify uckk.org Moodle database")) { return }

    $runtimeQ = ConvertTo-UckkShellQuoted $runtimeRoot
    $presetPathQ = ConvertTo-UckkShellQuoted $presetPath
    $seedCliQ = ConvertTo-UckkShellQuoted $seedCli
    $remoteCommand = "cd $runtimeQ && sudo -u www-data php $seedCliQ --presetpath=$presetPathQ --preset=$presetArg $modeArg$jsonArg"

    Write-UckkLog "ssh $sshTarget $remoteCommand" "CMD"
    $output = & ssh $sshTarget $remoteCommand 2>&1
    if ($LASTEXITCODE -ne 0) {
        throw "Server seed failed with exit code $LASTEXITCODE.`n$($output -join [Environment]::NewLine)"
    }

    $output
    "Server seed completed: $label"
}

function Invoke-UckkPurgeCachesLocal {
    $runtimeRoot = Get-UckkOpsVar "LocalRuntimeRoot"
    $moodleRoot = Get-UckkOpsVar "LocalMoodleRoot" -Default (Split-Path -Parent $runtimeRoot)
    $cliRoot = Get-UckkOpsVar "LocalMoodleCliRoot" -Default (Join-Path $moodleRoot "admin/cli")
    $phpExe = Get-UckkOpsVar "LocalPhpExe" -Default "php"
    $candidates = @(
        (Join-Path $cliRoot "purge_caches.php"),
        (Join-Path $runtimeRoot "admin/cli/purge_caches.php"),
        (Join-Path $moodleRoot "admin/cli/purge_caches.php")
    )
    $purgeCli = $candidates | Where-Object { Test-Path -LiteralPath $_ } | Select-Object -First 1

    if (-not $purgeCli) { throw "Local purge CLI not found. Checked: $($candidates -join ', ')" }

    Push-Location (Split-Path -Parent $purgeCli)
    try {
        & $phpExe $purgeCli
        if ($LASTEXITCODE -ne 0) { throw "Local cache purge failed with exit code $LASTEXITCODE." }
    }
    finally {
        Pop-Location
    }

    "Local caches purged."
}

function Invoke-UckkPurgeCachesServer {
    $sshTarget = Get-UckkOpsVar "ServerSshTarget"
    $runtimeRoot = Get-UckkOpsVar "ServerRuntimeRoot"
    $service = Get-UckkOpsVar "ServerPhpFpmService" -Default "php8.3-fpm"
    $cliRoot = Get-UckkOpsVar "ServerMoodleCliRoot" -Default "$runtimeRoot/admin/cli"
    $purgeCli = "$cliRoot/purge_caches.php"

    $runtimeQ = ConvertTo-UckkShellQuoted $runtimeRoot
    $purgeCliQ = ConvertTo-UckkShellQuoted $purgeCli
    $remoteCommand = "cd $runtimeQ && sudo -u www-data php $purgeCliQ && sudo systemctl reload $service"

    $output = & ssh $sshTarget $remoteCommand 2>&1
    if ($LASTEXITCODE -ne 0) { throw "Server cache purge failed with exit code $LASTEXITCODE.`n$($output -join [Environment]::NewLine)" }
    $output
    "Server caches purged."
}

function Invoke-UckkSeedCategoriesLocalDryRun { Invoke-UckkSeedLocal -Preset @("categories") -DryRun }
function Invoke-UckkSeedCategoriesLocalApply { Invoke-UckkSeedLocal -Preset @("categories") -Apply -Confirm:$false; Invoke-UckkPurgeCachesLocal }
function Invoke-UckkSeedCategoriesServerDryRun { Invoke-UckkSeedServer -Preset @("categories") -DryRun }
function Invoke-UckkSeedCategoriesServerApply { Invoke-UckkSeedServer -Preset @("categories") -Apply -Confirm:$false; Invoke-UckkPurgeCachesServer }

Export-ModuleMember -Function `
    Get-UckkSeedAvailablePresets, `
    Assert-UckkSeedPreset, `
    Invoke-UckkSeedLocal, `
    Invoke-UckkSeedServer, `
    Invoke-UckkPurgeCachesLocal, `
    Invoke-UckkPurgeCachesServer, `
    Invoke-UckkSeedCategoriesLocalDryRun, `
    Invoke-UckkSeedCategoriesLocalApply, `
    Invoke-UckkSeedCategoriesServerDryRun, `
    Invoke-UckkSeedCategoriesServerApply
