# tools/uckk-ops/lib/UckkOps.Server.psm1
Set-StrictMode -Version Latest
$ErrorActionPreference = "Stop"

$CommonPath = Join-Path $PSScriptRoot "UckkOps.Common.psm1"
Import-Module $CommonPath -Force -DisableNameChecking

function Get-UckkServerSettings {
    [pscustomobject]@{
        SshTarget         = Get-UckkOpsVar "ServerSshTarget"
        SourceRoot        = Get-UckkOpsVar "ServerSourceRoot"
        RuntimeRoot       = Get-UckkOpsVar "ServerRuntimeRoot"
        MoodleRoot        = Get-UckkOpsVar "ServerMoodleRoot"
        MoodleCliRoot     = Get-UckkOpsVar "ServerMoodleCliRoot" -Default ""
        MoodleDataRoot    = Get-UckkOpsVar "ServerMoodleDataRoot"
        PhpFpmService     = Get-UckkOpsVar "ServerPhpFpmService" -Default "php8.3-fpm"
        PublicUrl         = Get-UckkOpsVar "ServerPublicUrl"
        SeedPresetPath    = Get-UckkOpsVar "SeedPresetPathServer"
        SeedCliRelative   = Get-UckkOpsVar "SeedCliRelative" -Default "admin/tool/uckkseed/cli/seed.php"
        Components        = @((Get-UckkOpsVar "UckkComponents" -Default @()))
        SmokeUrls         = @((Get-UckkOpsVar "SmokeServerUrls" -Default @()))
    }
}

function Assert-UckkOpsServerConfig {
    $settings = Get-UckkServerSettings
    foreach ($name in @("SshTarget", "SourceRoot", "RuntimeRoot", "PhpFpmService", "PublicUrl")) {
        if ([string]::IsNullOrWhiteSpace([string]$settings.$name)) {
            throw "Missing server config variable: $name"
        }
    }
    return $true
}

function Invoke-UckkServerCommand {
    param(
        [Parameter(Mandatory = $true)][string]$Command,
        [switch]$RequireSudo,
        [switch]$RequireSuccess
    )

    Assert-UckkOpsServerConfig | Out-Null
    $settings = Get-UckkServerSettings

    $remoteCommand = $Command
    if ($RequireSudo) {
        $remoteCommand = "sudo bash -lc " + (ConvertTo-UckkShellQuoted $Command)
    }

    Write-UckkLog "ssh $($settings.SshTarget) $remoteCommand" "CMD"
    $output = & ssh $settings.SshTarget $remoteCommand 2>&1
    $exitCode = $LASTEXITCODE

    if ($RequireSuccess -and $exitCode -ne 0) {
        throw "Server command failed with exit code $exitCode.`n$($output -join [Environment]::NewLine)"
    }

    [pscustomobject]@{
        Command  = $remoteCommand
        ExitCode = $exitCode
        Success  = ($exitCode -eq 0)
        Output   = ($output -join [Environment]::NewLine)
    }
}

function Test-UckkServerSsh {
    Invoke-UckkServerCommand -Command "hostname && whoami && pwd" -RequireSuccess
}

function Update-UckkServerSource {
    $settings = Get-UckkServerSettings
    Invoke-UckkServerCommand -Command "cd '$($settings.SourceRoot)' && git status --short && git pull" -RequireSuccess
}

function Invoke-UckkServerPull { Update-UckkServerSource }

function Sync-UckkServerSourceToRuntime {
    $settings = Get-UckkServerSettings

    if (-not $settings.Components -or $settings.Components.Count -eq 0) {
        throw "Missing component list: UckkComponents"
    }

    $results = @()
    foreach ($component in $settings.Components) {
        $source = "$($settings.SourceRoot)/$component"
        $target = "$($settings.RuntimeRoot)/$component"
        $targetParent = ($target -replace "/[^/]+$", "")
        $cmd = "if [ -e '$source' ]; then mkdir -p '$targetParent'; rsync -a --delete '$source/' '$target/'; chown -R www-data:www-data '$target'; echo 'synced: $component'; else echo 'missing source: $source'; fi"
        $results += Invoke-UckkServerCommand -Command $cmd -RequireSudo -RequireSuccess
    }

    return $results
}

function Invoke-UckkServerMoodleUpgrade {
    $settings = Get-UckkServerSettings
    $cliRoot = if ([string]::IsNullOrWhiteSpace([string]$settings.MoodleCliRoot)) { "$($settings.RuntimeRoot)/admin/cli" } else { $settings.MoodleCliRoot }
    $workRoot = if ([string]::IsNullOrWhiteSpace([string]$settings.MoodleRoot)) { $settings.RuntimeRoot } else { $settings.MoodleRoot }
    Invoke-UckkServerCommand -Command "cd '$workRoot' && sudo -u www-data php '$cliRoot/upgrade.php' --non-interactive" -RequireSuccess
}

function Clear-UckkServerMoodleCaches {
    $settings = Get-UckkServerSettings
    $cliRoot = if ([string]::IsNullOrWhiteSpace([string]$settings.MoodleCliRoot)) { "$($settings.RuntimeRoot)/admin/cli" } else { $settings.MoodleCliRoot }
    $workRoot = if ([string]::IsNullOrWhiteSpace([string]$settings.MoodleRoot)) { $settings.RuntimeRoot } else { $settings.MoodleRoot }
    Invoke-UckkServerCommand -Command "cd '$workRoot' && sudo -u www-data php '$cliRoot/purge_caches.php'" -RequireSuccess
}

function Clear-UckkServerCaches { Clear-UckkServerMoodleCaches }

function Restart-UckkServerPhpFpm {
    $settings = Get-UckkServerSettings
    Invoke-UckkServerCommand -Command "systemctl reload '$($settings.PhpFpmService)'" -RequireSudo -RequireSuccess
}

function Invoke-UckkServerSeedDryRun {
    param([Parameter(Mandatory = $true)][string]$Preset)
    $settings = Get-UckkServerSettings
    $seedCli = "$($settings.RuntimeRoot)/$($settings.SeedCliRelative)"
    Invoke-UckkServerCommand -Command "sudo -u www-data php '$seedCli' --presetpath='$($settings.SeedPresetPath)' --preset='$Preset' --dry-run" -RequireSuccess
}

function Invoke-UckkServerSeedApply {
    param([Parameter(Mandatory = $true)][string]$Preset)
    $settings = Get-UckkServerSettings
    $seedCli = "$($settings.RuntimeRoot)/$($settings.SeedCliRelative)"
    Invoke-UckkServerCommand -Command "sudo -u www-data php '$seedCli' --presetpath='$($settings.SeedPresetPath)' --preset='$Preset' --force" -RequireSuccess
}

function Test-UckkServerUrl {
    $settings = Get-UckkServerSettings
    $urls = @($settings.SmokeUrls)
    if (-not $urls -or $urls.Count -eq 0) {
        $urls = @($settings.PublicUrl, "$($settings.PublicUrl)/course/index.php", "$($settings.PublicUrl)/local/uckk/programs.php")
    }

    foreach ($url in $urls) {
        try {
            $response = Invoke-WebRequest -Uri $url -UseBasicParsing -TimeoutSec 15
            [pscustomobject]@{ Url = $url; Ok = $true; StatusCode = [int]$response.StatusCode; Error = $null }
        }
        catch {
            [pscustomobject]@{ Url = $url; Ok = $false; StatusCode = $null; Error = $_.Exception.Message }
        }
    }
}

function Invoke-UckkServerDeployBasic {
    Update-UckkServerSource
    Sync-UckkServerSourceToRuntime
    Invoke-UckkServerMoodleUpgrade
    Clear-UckkServerMoodleCaches
    Restart-UckkServerPhpFpm
    Test-UckkServerUrl
}

Export-ModuleMember -Function `
    Get-UckkServerSettings, `
    Assert-UckkOpsServerConfig, `
    Invoke-UckkServerCommand, `
    Test-UckkServerSsh, `
    Update-UckkServerSource, `
    Invoke-UckkServerPull, `
    Sync-UckkServerSourceToRuntime, `
    Invoke-UckkServerMoodleUpgrade, `
    Clear-UckkServerMoodleCaches, `
    Clear-UckkServerCaches, `
    Restart-UckkServerPhpFpm, `
    Invoke-UckkServerSeedDryRun, `
    Invoke-UckkServerSeedApply, `
    Test-UckkServerUrl, `
    Invoke-UckkServerDeployBasic
