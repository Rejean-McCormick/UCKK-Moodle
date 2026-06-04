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

function Get-UckkObjectBooleanFlag {
    param(
        [AllowNull()][object]$Object,
        [Parameter(Mandatory = $true)][string]$Name,
        [bool]$Default = $false
    )

    if ($null -eq $Object) {
        return $Default
    }

    if ($Object -is [System.Collections.IDictionary]) {
        if ($Object.Contains($Name)) {
            return [bool]$Object[$Name]
        }

        if ($Object.ContainsKey($Name)) {
            return [bool]$Object[$Name]
        }

        return $Default
    }

    $property = $Object.PSObject.Properties[$Name]

    if ($null -eq $property) {
        return $Default
    }

    return [bool]$property.Value
}

function Get-UckkObjectBooleanAnyFlag {
    param(
        [AllowNull()][object]$Object,
        [Parameter(Mandatory = $true)][string[]]$Names,
        [bool]$Default = $false
    )

    if ($null -eq $Object) {
        return $Default
    }

    foreach ($name in $Names) {
        if (Get-UckkObjectBooleanFlag -Object $Object -Name $name -Default $false) {
            return $true
        }
    }

    return $Default
}

function Get-UckkServerMoodlePaths {
    $settings = Get-UckkServerSettings

    $workRoot = if ([string]::IsNullOrWhiteSpace([string]$settings.MoodleRoot)) {
        $settings.RuntimeRoot
    } else {
        $settings.MoodleRoot
    }

    $cliRoot = if ([string]::IsNullOrWhiteSpace([string]$settings.MoodleCliRoot)) {
        "$($settings.RuntimeRoot)/admin/cli"
    } else {
        $settings.MoodleCliRoot
    }

    if ([string]::IsNullOrWhiteSpace([string]$workRoot)) {
        throw "Missing server Moodle root: ServerMoodleRoot or ServerRuntimeRoot"
    }

    if ([string]::IsNullOrWhiteSpace([string]$cliRoot)) {
        throw "Missing server Moodle CLI root: ServerMoodleCliRoot or ServerRuntimeRoot/admin/cli"
    }

    [pscustomobject]@{
        WorkRoot = ([string]$workRoot).TrimEnd('/')
        CliRoot  = ([string]$cliRoot).TrimEnd('/')
    }
}

function Get-UckkServerSiteProfile {
    $config = Get-UckkOpsConfig

    $fullname = Get-UckkConfigProperty `
        -Object $config `
        -Path "siteProfile.fullname" `
        -Default "Univers-Cité King Klown"

    $shortname = Get-UckkConfigProperty `
        -Object $config `
        -Path "siteProfile.shortname" `
        -Default "UCKK"

    $rawConfig = Get-UckkConfigProperty `
        -Object $config `
        -Path "siteProfile.config" `
        -Default $null

    $siteConfig = [ordered]@{}

    if ($null -ne $rawConfig) {
        if ($rawConfig -is [System.Collections.IDictionary]) {
            foreach ($key in $rawConfig.Keys) {
                $value = $rawConfig[$key]
                if ($value -is [bool]) {
                    $value = [int]$value
                }
                $siteConfig[[string]$key] = $value
            }
        }
        else {
            foreach ($property in $rawConfig.PSObject.Properties) {
                $value = $property.Value
                if ($value -is [bool]) {
                    $value = [int]$value
                }
                $siteConfig[[string]$property.Name] = $value
            }
        }
    }

    if (-not $siteConfig.Contains("autologinguests")) {
        $siteConfig["autologinguests"] = 0
    }

    if (-not $siteConfig.Contains("guestloginbutton")) {
        $siteConfig["guestloginbutton"] = 0
    }

    [pscustomobject]@{
        Fullname  = [string]$fullname
        Shortname = [string]$shortname
        Config    = $siteConfig
    }
}

function Invoke-UckkServerApplySiteProfile {
    $settings = Get-UckkServerSettings

    $workRoot = if ([string]::IsNullOrWhiteSpace([string]$settings.MoodleRoot)) {
        $settings.RuntimeRoot
    } else {
        $settings.MoodleRoot
    }

    if ([string]::IsNullOrWhiteSpace([string]$workRoot)) {
        throw "Missing server Moodle root: ServerMoodleRoot or ServerRuntimeRoot"
    }

    $profile = Get-UckkServerSiteProfile

    $moodleRootForPhp = ([string]$workRoot).TrimEnd("/")
    $moodleRootForPhp = $moodleRootForPhp.Replace("\", "/")
    $moodleRootForPhpEscaped = $moodleRootForPhp.Replace("'", "\'")

    $payload = [ordered]@{
        fullname  = $profile.Fullname
        shortname = $profile.Shortname
        config    = $profile.Config
    }

    $payloadJson = $payload | ConvertTo-Json -Depth 20 -Compress
    $payload64 = [Convert]::ToBase64String([Text.Encoding]::UTF8.GetBytes($payloadJson))

    $php = @"
<?php
define('CLI_SCRIPT', true);
`$moodleroot = '$moodleRootForPhpEscaped';
`$configfile = `$moodleroot . '/config.php';

if (!is_readable(`$configfile)) {
    fwrite(STDERR, "Moodle config.php not found or not readable: " . `$configfile . "\n");
    exit(1);
}

require_once(`$configfile);
global `$DB;

`$payload = base64_decode('$payload64', true);
if (`$payload === false) {
    fwrite(STDERR, "Invalid UCKK site profile payload base64.\n");
    exit(1);
}

`$profile = json_decode(`$payload, true);
if (!is_array(`$profile)) {
    fwrite(STDERR, "Invalid UCKK site profile JSON.\n");
    exit(1);
}

if (!empty(`$profile['config']) && is_array(`$profile['config'])) {
    foreach (`$profile['config'] as `$name => `$value) {
        if (is_bool(`$value)) {
            `$value = `$value ? 1 : 0;
        }
        set_config((string)`$name, `$value);
        echo 'config: ' . `$name . '=' . (is_scalar(`$value) ? (string)`$value : json_encode(`$value)) . PHP_EOL;
    }
}

`$site = get_site();
`$changed = false;

if (isset(`$profile['fullname']) && (string)`$site->fullname !== (string)`$profile['fullname']) {
    `$site->fullname = (string)`$profile['fullname'];
    `$changed = true;
}

if (isset(`$profile['shortname']) && (string)`$site->shortname !== (string)`$profile['shortname']) {
    `$site->shortname = (string)`$profile['shortname'];
    `$changed = true;
}

if (`$changed) {
    `$site->timemodified = time();
    `$DB->update_record('course', `$site);
    echo 'site: fullname=' . `$site->fullname . PHP_EOL;
    echo 'site: shortname=' . `$site->shortname . PHP_EOL;
} else {
    echo "site: unchanged\n";
}

echo "UCKK site profile applied.\n";
"@

    $encoded = [Convert]::ToBase64String([Text.Encoding]::UTF8.GetBytes($php))
    $tmp = "/tmp/uckk_site_profile_$([Guid]::NewGuid().ToString('N')).php"

    Invoke-UckkServerCommand `
        -Command "cd '$workRoot' && printf %s '$encoded' | base64 -d > '$tmp' && chmod 0644 '$tmp' && sudo -u www-data php '$tmp'; rc=`$?; rm -f '$tmp'; exit `$rc" `
        -RequireSuccess
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

    Invoke-UckkServerCommand `
        -Command "cd '$($settings.SourceRoot)' && git status --short && git pull" `
        -RequireSuccess
}

function Invoke-UckkServerPull {
    Update-UckkServerSource
}

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

        $results += Invoke-UckkServerCommand `
            -Command $cmd `
            -RequireSudo `
            -RequireSuccess
    }

    return $results
}

function Invoke-UckkServerMoodleUpgrade {
    $paths = Get-UckkServerMoodlePaths

    Invoke-UckkServerCommand `
        -Command "cd '$($paths.WorkRoot)' && sudo -u www-data php '$($paths.CliRoot)/upgrade.php' --non-interactive" `
        -RequireSuccess
}

function Clear-UckkServerMoodleCaches {
    $paths = Get-UckkServerMoodlePaths

    Invoke-UckkServerCommand `
        -Command "cd '$($paths.WorkRoot)' && sudo -u www-data php '$($paths.CliRoot)/purge_caches.php'" `
        -RequireSuccess
}

function Clear-UckkServerCaches {
    Clear-UckkServerMoodleCaches
}

function Restart-UckkServerPhpFpm {
    $settings = Get-UckkServerSettings

    Invoke-UckkServerCommand `
        -Command "systemctl reload '$($settings.PhpFpmService)'" `
        -RequireSudo `
        -RequireSuccess
}

function Invoke-UckkServerSeedDryRun {
    param(
        [Parameter(Mandatory = $true)][string]$Preset
    )

    $settings = Get-UckkServerSettings
    $seedCli = "$($settings.RuntimeRoot)/$($settings.SeedCliRelative)"

    Invoke-UckkServerCommand `
        -Command "sudo -u www-data php '$seedCli' --presetpath='$($settings.SeedPresetPath)' --preset='$Preset' --dry-run" `
        -RequireSuccess
}

function Invoke-UckkServerSeedApply {
    param(
        [Parameter(Mandatory = $true)][string]$Preset
    )

    $settings = Get-UckkServerSettings
    $seedCli = "$($settings.RuntimeRoot)/$($settings.SeedCliRelative)"

    Invoke-UckkServerCommand `
        -Command "sudo -u www-data php '$seedCli' --presetpath='$($settings.SeedPresetPath)' --preset='$Preset' --force" `
        -RequireSuccess
}

function Test-UckkServerUrl {
    $settings = Get-UckkServerSettings
    $urls = @($settings.SmokeUrls)

    if (-not $urls -or $urls.Count -eq 0) {
        $urls = @(
            $settings.PublicUrl,
            "$($settings.PublicUrl)/login/index.php",
            "$($settings.PublicUrl)/course/index.php",
            "$($settings.PublicUrl)/local/uckk/programs.php"
        )
    }

    foreach ($url in $urls) {
        try {
            $response = Invoke-WebRequest `
                -Uri $url `
                -UseBasicParsing `
                -TimeoutSec 15 `
                -ErrorAction Stop

            [pscustomobject]@{
                Url        = $url
                Ok         = ($response.StatusCode -ge 200 -and $response.StatusCode -lt 400)
                StatusCode = [int]$response.StatusCode
                Error      = $null
            }
        }
        catch {
            [pscustomobject]@{
                Url        = $url
                Ok         = $false
                StatusCode = $null
                Error      = $_.Exception.Message
            }
        }
    }
}

function Invoke-UckkServerDeployPlanned {
    param(
        [AllowNull()][object]$Plan = $null,
        [switch]$SkipPull,
        [switch]$SkipSync,
        [Alias("ForceUpgrade")][switch]$AlwaysUpgrade,
        [Alias("ForcePurge")][switch]$AlwaysPurge,
        [Alias("ForceSmoke")][switch]$AlwaysSmoke,
        [switch]$SkipSiteProfile,
        [switch]$ReloadPhpFpm,
        [switch]$SafeWhenNoPlan
    )

    $hasPlan = ($null -ne $Plan)
    $needsSiteProfile = (-not $SkipSiteProfile.IsPresent)

    $planNeedsUpgrade = $hasPlan -and (Get-UckkObjectBooleanAnyFlag `
        -Object $Plan `
        -Names @("NeedsServerUpgrade", "NeedsMoodleUpgrade"))

    $planNeedsPurge = $hasPlan -and (Get-UckkObjectBooleanAnyFlag `
        -Object $Plan `
        -Names @("NeedsServerPurge", "NeedsPurgeCaches"))

    $planNeedsSmoke = $hasPlan -and (Get-UckkObjectBooleanAnyFlag `
        -Object $Plan `
        -Names @("NeedsServerSmoke", "NeedsSmokeTests"))

    $needsUpgrade = $AlwaysUpgrade.IsPresent -or $planNeedsUpgrade

    # Safe default: if no plan is supplied, purge server caches after deploy.
    # This prevents mixed states such as new PHP/templates with old Moodle caches.
    $needsPurge = $AlwaysPurge.IsPresent -or `
        $needsSiteProfile -or `
        $needsUpgrade -or `
        $planNeedsPurge -or `
        (-not $hasPlan) -or `
        ($SafeWhenNoPlan.IsPresent -and -not $hasPlan)

    # Smoke after purge/upgrade by default, because deploy verification should test
    # the state users will actually hit after server cache invalidation.
    $needsSmoke = $AlwaysSmoke.IsPresent -or `
        $needsPurge -or `
        $planNeedsSmoke -or `
        (-not $hasPlan) -or `
        ($SafeWhenNoPlan.IsPresent -and -not $hasPlan)

    "Server deploy planned:"
    "- Pull source: $(-not $SkipPull.IsPresent)"
    "- Sync runtime: $(-not $SkipSync.IsPresent)"
    "- Moodle upgrade: $needsUpgrade"
    "- Apply UCKK site profile: $needsSiteProfile"
    "- Purge server caches: $needsPurge"
    "- Reload PHP-FPM: $($ReloadPhpFpm.IsPresent)"
    "- Smoke server: $needsSmoke"

    if (-not $SkipPull.IsPresent) {
        "Server step: git pull"
        Update-UckkServerSource
    }

    if (-not $SkipSync.IsPresent) {
        "Server step: sync source to runtime"
        Sync-UckkServerSourceToRuntime
    }

    if ($needsUpgrade) {
        "Server step: Moodle upgrade"
        Invoke-UckkServerMoodleUpgrade
    }

    if ($needsSiteProfile) {
        "Server step: apply UCKK site profile"
        Invoke-UckkServerApplySiteProfile
    }

    if ($needsPurge) {
        "Server step: purge Moodle caches"
        Clear-UckkServerMoodleCaches
    }

    if ($ReloadPhpFpm.IsPresent) {
        "Server step: reload PHP-FPM"
        Restart-UckkServerPhpFpm
    }

    if ($needsSmoke) {
        "Server step: smoke server URLs"
        Test-UckkServerUrl
    }

    "Server deploy planned termine."
}

function Invoke-UckkServerDeployBasic {
    Invoke-UckkServerDeployPlanned `
        -AlwaysUpgrade `
        -AlwaysPurge `
        -AlwaysSmoke `
        -ReloadPhpFpm
}

Export-ModuleMember -Function `
    Get-UckkServerSettings, `
    Assert-UckkOpsServerConfig, `
    Get-UckkObjectBooleanFlag, `
    Get-UckkObjectBooleanAnyFlag, `
    Get-UckkServerMoodlePaths, `
    Get-UckkServerSiteProfile, `
    Invoke-UckkServerApplySiteProfile, `
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
    Invoke-UckkServerDeployPlanned, `
    Invoke-UckkServerDeployBasic