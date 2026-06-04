[CmdletBinding()]
param(
    [string]$ConfigPath = ""
)

Set-StrictMode -Version Latest
$ErrorActionPreference = "Stop"

$Script:OpsRoot = Split-Path -Parent $MyInvocation.MyCommand.Path
$Script:LibRoot = Join-Path $Script:OpsRoot "lib"

if ([string]::IsNullOrWhiteSpace($ConfigPath)) {
    $ConfigPath = Join-Path $Script:OpsRoot "uckk-ops.config.json"
}

$Script:ConfigPath = $ConfigPath

# -----------------------------------------------------------------------------
# Self-contained runtime alignment layer
# -----------------------------------------------------------------------------
# This GUI intentionally loads all operational variables from one source:
# tools/uckk-ops/uckk-ops.config.json
# It does not rely on cross-module $Script:* variables. The .psm1 files may still
# exist, but this file defines the command names used by the buttons directly.

function Get-UckkConfigValue {
    param(
        [Parameter(Mandatory = $true)][object]$Config,
        [Parameter(Mandatory = $true)][string]$Path,
        [object]$Default = $null
    )

    $value = $Config

    foreach ($part in ($Path -split "\.")) {
        if ($null -eq $value) {
            return $Default
        }

        if ($value.PSObject.Properties.Name -notcontains $part) {
            return $Default
        }

        $value = $value.$part
    }

    if ($null -eq $value) {
        return $Default
    }

    return $value
}

if (-not (Test-Path -LiteralPath $Script:ConfigPath)) {
    throw "Config introuvable : $Script:ConfigPath"
}

$Script:OpsConfig = Get-Content -LiteralPath $Script:ConfigPath -Raw -Encoding UTF8 | ConvertFrom-Json
$Global:UckkOpsConfig = $Script:OpsConfig

$Script:AppName = [string](Get-UckkConfigValue $Script:OpsConfig "app.name" "UCKK Ops Console")
$Script:AppVersion = [string](Get-UckkConfigValue $Script:OpsConfig "app.version" "0.1.1")
$Script:Environment = [string](Get-UckkConfigValue $Script:OpsConfig "app.environment" "dev")

$Script:LocalSourceRoot = [string](Get-UckkConfigValue $Script:OpsConfig "local.sourceRoot" "C:\mycode\UCKK\uckk-moodle")
$Script:LocalRuntimeRoot = [string](Get-UckkConfigValue $Script:OpsConfig "local.runtimeRoot" "C:\mycode\UCKK\moodle\moodle\public")
$Script:LocalMoodleRoot = [string](Get-UckkConfigValue $Script:OpsConfig "local.moodleRoot" (Split-Path -Parent $Script:LocalRuntimeRoot))
$Script:LocalMoodleCliRoot = [string](Get-UckkConfigValue $Script:OpsConfig "local.moodleCliRoot" (Join-Path $Script:LocalMoodleRoot "admin\cli"))
$Script:LocalPhpExe = [string](Get-UckkConfigValue $Script:OpsConfig "local.phpExe" "php")
$Script:LocalUrl = [string](Get-UckkConfigValue $Script:OpsConfig "local.localUrl" "http://127.0.0.1:8000")

$Script:GitRepoRoot = [string](Get-UckkConfigValue $Script:OpsConfig "git.repoRoot" $Script:LocalSourceRoot)
$Script:GitRemote = [string](Get-UckkConfigValue $Script:OpsConfig "git.remote" "origin")
$Script:GitBranch = [string](Get-UckkConfigValue $Script:OpsConfig "git.branch" "main")
$Script:DefaultCommitMessage = [string](Get-UckkConfigValue $Script:OpsConfig "git.defaultCommitMessage" "Update UCKK ops")

$Script:ServerSshTarget = [string](Get-UckkConfigValue $Script:OpsConfig "server.sshTarget" "ubuntu@57.129.115.159")
$Script:ServerSourceRoot = [string](Get-UckkConfigValue $Script:OpsConfig "server.sourceRoot" "/opt/uckk/uckk-moodle")
$Script:ServerRuntimeRoot = [string](Get-UckkConfigValue $Script:OpsConfig "server.runtimeRoot" "/var/www/moodle/public")
$Script:ServerMoodleRoot = [string](Get-UckkConfigValue $Script:OpsConfig "server.moodleRoot" (Split-Path -Parent $Script:ServerRuntimeRoot))
$Script:ServerMoodleCliRoot = [string](Get-UckkConfigValue $Script:OpsConfig "server.moodleCliRoot" "$Script:ServerMoodleRoot/admin/cli")
$Script:ServerPhpFpmService = [string](Get-UckkConfigValue $Script:OpsConfig "server.phpFpmService" "php8.3-fpm")
$Script:ServerPublicUrl = [string](Get-UckkConfigValue $Script:OpsConfig "server.publicUrl" "https://uckk.org")

$Script:SeedPresetPathLocal = [string](Get-UckkConfigValue $Script:OpsConfig "seed.presetPathLocal" (Join-Path $Script:LocalSourceRoot "academic_registry_json"))
$Script:SeedPresetPathServer = [string](Get-UckkConfigValue $Script:OpsConfig "seed.presetPathServer" "/opt/uckk/uckk-moodle/academic_registry_json")
$Script:SeedCliRelative = [string](Get-UckkConfigValue $Script:OpsConfig "seed.seedCliRelative" "admin/tool/uckkseed/cli/seed.php")

$Script:UckkComponents = @((Get-UckkConfigValue $Script:OpsConfig "components" @()))
$Script:SmokeLocalUrls = @((Get-UckkConfigValue $Script:OpsConfig "smoke.localUrls" @($Script:LocalUrl, "$Script:LocalUrl/course/index.php", "$Script:LocalUrl/local/uckk/programs.php")))
$Script:SmokeServerUrls = @((Get-UckkConfigValue $Script:OpsConfig "smoke.serverUrls" @($Script:ServerPublicUrl, "$Script:ServerPublicUrl/course/index.php", "$Script:ServerPublicUrl/local/uckk/programs.php")))
$Script:LastUpdatePlan = $null

$Script:SiteProfileFullname = [string](Get-UckkConfigValue $Script:OpsConfig "siteProfile.fullname" "Univers-Cité King Klown")
$Script:SiteProfileShortname = [string](Get-UckkConfigValue $Script:OpsConfig "siteProfile.shortname" "UCKK")
$Script:SiteProfileConfig = Get-UckkConfigValue $Script:OpsConfig "siteProfile.config" ([pscustomobject]@{
    autologinguests = 0
    guestloginbutton = 0
})

function Resolve-UckkLocalPath {
    param([string]$Root, [string]$RelativePath)
    return Join-Path $Root ($RelativePath -replace "/", [IO.Path]::DirectorySeparatorChar)
}

function Get-UckkExistingLocalPath {
    param([string[]]$Paths)

    foreach ($path in $Paths) {
        if (-not [string]::IsNullOrWhiteSpace($path) -and (Test-Path -LiteralPath $path)) {
            return $path
        }
    }

    return $null
}

function Get-UckkLocalMoodleConfigPath {
    return Get-UckkExistingLocalPath @(
        (Join-Path $Script:LocalRuntimeRoot "config.php"),
        (Join-Path $Script:LocalMoodleRoot "config.php")
    )
}

function Get-UckkLocalMoodleCliScript {
    param([Parameter(Mandatory = $true)][string]$ScriptName)

    return Get-UckkExistingLocalPath @(
        (Join-Path $Script:LocalMoodleCliRoot $ScriptName),
        (Join-Path $Script:LocalRuntimeRoot "admin/cli/$ScriptName"),
        (Join-Path $Script:LocalMoodleRoot "admin/cli/$ScriptName")
    )
}

function Assert-UckkLocalComponentTargetSafe {
    param([Parameter(Mandatory = $true)][string]$Component)

    if ([string]::IsNullOrWhiteSpace($Component)) {
        throw "Component vide dans la config."
    }
    if ($Component -match '(^|[\/])\.\.([\/]|$)' -or [System.IO.Path]::IsPathRooted($Component)) {
        throw "Component non securitaire dans la config : $Component"
    }
}

function Invoke-UckkCheckedNative {
    param(
        [Parameter(Mandatory = $true)][scriptblock]$ScriptBlock,
        [string]$ErrorMessage = "Commande echouee."
    )

    # Some native tools, especially git push/pull over HTTPS/SSH, write normal
    # progress lines to STDERR even when the command succeeds. With the global
    # $ErrorActionPreference set to Stop, PowerShell can treat those STDERR lines
    # as terminating NativeCommandError exceptions. Capture them as output and
    # decide success/failure only from the native exit code.
    $previousErrorActionPreference = $ErrorActionPreference
    try {
        $ErrorActionPreference = "Continue"
        $output = & $ScriptBlock 2>&1
        $exit = $LASTEXITCODE
    }
    finally {
        $ErrorActionPreference = $previousErrorActionPreference
    }

    if ($null -ne $output) {
        $output
    }

    if ($exit -ne 0 -and $null -ne $exit) {
        throw "$ErrorMessage Exit code: $exit"
    }
}

function Test-UckkOpsPaths {
    $configPath = Get-UckkLocalMoodleConfigPath
    $purgePath = Get-UckkLocalMoodleCliScript -ScriptName "purge_caches.php"
    $upgradePath = Get-UckkLocalMoodleCliScript -ScriptName "upgrade.php"

    $items = @(
        [pscustomobject]@{ Name = "Local source root"; Path = $Script:LocalSourceRoot; Exists = Test-Path -LiteralPath $Script:LocalSourceRoot },
        [pscustomobject]@{ Name = "Local runtime/web root"; Path = $Script:LocalRuntimeRoot; Exists = Test-Path -LiteralPath $Script:LocalRuntimeRoot },
        [pscustomobject]@{ Name = "Local Moodle root"; Path = $Script:LocalMoodleRoot; Exists = Test-Path -LiteralPath $Script:LocalMoodleRoot },
        [pscustomobject]@{ Name = "Local Moodle CLI root"; Path = $Script:LocalMoodleCliRoot; Exists = Test-Path -LiteralPath $Script:LocalMoodleCliRoot },
        [pscustomobject]@{ Name = "Git repo root"; Path = $Script:GitRepoRoot; Exists = Test-Path -LiteralPath $Script:GitRepoRoot },
        [pscustomobject]@{ Name = "Seed preset path local"; Path = $Script:SeedPresetPathLocal; Exists = Test-Path -LiteralPath $Script:SeedPresetPathLocal },
        [pscustomobject]@{ Name = "Moodle config.php"; Path = $configPath; Exists = [bool]$configPath },
        [pscustomobject]@{ Name = "Moodle purge CLI"; Path = $purgePath; Exists = [bool]$purgePath },
        [pscustomobject]@{ Name = "Moodle upgrade CLI"; Path = $upgradePath; Exists = [bool]$upgradePath },
        [pscustomobject]@{ Name = "PHP executable"; Path = $Script:LocalPhpExe; Exists = [bool](Get-Command $Script:LocalPhpExe -ErrorAction SilentlyContinue) }
    )

    return $items
}

function Sync-UckkLocalSourceToRuntime {
    $results = @()

    foreach ($component in $Script:UckkComponents) {
        Assert-UckkLocalComponentTargetSafe -Component $component
        $source = Resolve-UckkLocalPath $Script:LocalSourceRoot $component
        $target = Resolve-UckkLocalPath $Script:LocalRuntimeRoot $component
        $parent = Split-Path $target -Parent

        if (-not (Test-Path -LiteralPath $source)) {
            $results += "missing source: $source"
            continue
        }

        if (-not (Test-Path -LiteralPath $parent)) {
            New-Item -ItemType Directory -Path $parent -Force | Out-Null
        }

        if (Test-Path -LiteralPath $target) {
            Remove-Item -LiteralPath $target -Recurse -Force
        }

        Copy-Item -LiteralPath $source -Destination $target -Recurse -Force
        $results += "synced: $component"
    }

    return $results
}

function Clear-UckkLocalCaches {
    $purge = Get-UckkLocalMoodleCliScript -ScriptName "purge_caches.php"

    if (-not $purge) {
        throw "Purge CLI introuvable. Chemins verifies : $Script:LocalMoodleCliRoot, $Script:LocalRuntimeRoot\admin\cli, $Script:LocalMoodleRoot\admin\cli"
    }

    Push-Location (Split-Path -Parent $purge)
    try {
        Invoke-UckkCheckedNative { & $Script:LocalPhpExe $purge } "Purge caches local echoue."
    }
    finally {
        Pop-Location
    }
}

function Start-UckkLocalMoodle {
    $process = Start-Process `
        -FilePath $Script:LocalPhpExe `
        -ArgumentList @("-S", "127.0.0.1:8000", "-t", ".") `
        -WorkingDirectory $Script:LocalRuntimeRoot `
        -PassThru

    return "started local Moodle: http://127.0.0.1:8000 process=$($process.Id)"
}

function Stop-UckkLocalMoodle {
    $connections = Get-NetTCPConnection -LocalPort 8000 -State Listen -ErrorAction SilentlyContinue

    if (-not $connections) {
        return "no local server listening on port 8000"
    }

    foreach ($pidValue in ($connections | Select-Object -ExpandProperty OwningProcess -Unique)) {
        Stop-Process -Id $pidValue -Force
        "stopped process $pidValue"
    }
}

function Invoke-UckkGitCommand {
    param([string[]]$Arguments)

    if (-not (Test-Path -LiteralPath $Script:GitRepoRoot)) {
        throw "Repo Git introuvable : $Script:GitRepoRoot"
    }

    Invoke-UckkCheckedNative { & git -c core.safecrlf=false -C $Script:GitRepoRoot @Arguments } "Git command failed."
}

function Get-UckkGitStatus { Invoke-UckkGitCommand @("status", "--short", "--branch") }
function Get-UckkGitDiff { Invoke-UckkGitCommand @("diff", "--stat") }
function Get-UckkGitPorcelainStatus { Invoke-UckkGitCommand @("status", "--porcelain=v1") }

function Invoke-UckkGitCommitPush {
    param([string]$Message)

    if ([string]::IsNullOrWhiteSpace($Message)) {
        throw "Message de commit requis."
    }

    $status = Invoke-UckkGitCommand @("status", "--porcelain")
    if ([string]::IsNullOrWhiteSpace(($status -join "`n"))) {
        "No local changes to commit."
    }
    else {
        Invoke-UckkGitCommand @("add", "-A")
        Invoke-UckkGitCommand @("commit", "-m", $Message)
    }

    Invoke-UckkGitCommand @("push", $Script:GitRemote, $Script:GitBranch)
}

function Invoke-UckkSshCommand {
    param([string]$Command)
    Invoke-UckkCheckedNative { & ssh $Script:ServerSshTarget $Command } "SSH command failed."
}

function Test-UckkServerSsh { Invoke-UckkSshCommand "hostname && whoami && pwd" }
function Invoke-UckkServerPull { Invoke-UckkSshCommand "cd '$Script:ServerSourceRoot' && git pull" }

function Sync-UckkServerSourceToRuntime {
    foreach ($component in $Script:UckkComponents) {
        $source = "$Script:ServerSourceRoot/$component"
        $target = "$Script:ServerRuntimeRoot/$component"

        # Keep the remote command on one physical line. This avoids CRLF/newline issues
        # when PowerShell on Windows sends the command through SSH to Linux bash.
        $cmdTemplate = 'if [ -e "{0}" ]; then sudo mkdir -p "$(dirname "{1}")" && sudo rsync -a --delete "{0}/" "{1}/" && sudo chown -R www-data:www-data "{1}" && echo "synced: {2}"; else echo "missing source: {0}"; fi'
        $cmd = $cmdTemplate -f $source, $target, $component
        Invoke-UckkSshCommand $cmd
    }
}

function Invoke-UckkServerMoodleUpgrade { Invoke-UckkSshCommand "cd '$Script:ServerMoodleRoot' && sudo -u www-data php '$Script:ServerMoodleCliRoot/upgrade.php' --non-interactive" }
function Clear-UckkServerCaches { Invoke-UckkSshCommand "cd '$Script:ServerMoodleRoot' && sudo -u www-data php '$Script:ServerMoodleCliRoot/purge_caches.php'" }
function Restart-UckkServerPhpFpm { Invoke-UckkSshCommand "sudo systemctl reload '$Script:ServerPhpFpmService'" }

function Test-UckkPlanFlag {
    param(
        [object]$Plan,
        [Parameter(Mandatory = $true)][string]$PropertyName
    )

    if ($null -eq $Plan) {
        return $false
    }

    if ($Plan.PSObject.Properties.Name -notcontains $PropertyName) {
        return $false
    }

    return [bool]$Plan.$PropertyName
}

function Invoke-UckkServerDeployPlanned {
    param(
        [object]$Plan = $null,
        [switch]$SafeWhenNoPlan
    )

    $hasPlan = ($null -ne $Plan)

    if ($hasPlan) {
        "Server deploy uses scanned update plan."
    }
    elseif ($SafeWhenNoPlan) {
        "No scanned update plan available. Server deploy will run safe purge and smoke after sync."
    }
    else {
        "No scanned update plan available. Server deploy will run pull and sync only."
    }

    "1/6 Git pull serveur"
    Invoke-UckkServerPull

    "2/6 Sync source -> runtime serveur"
    Sync-UckkServerSourceToRuntime

    $needsUpgrade = Test-UckkPlanFlag -Plan $Plan -PropertyName "NeedsMoodleUpgrade"
    $needsPurge = (Test-UckkPlanFlag -Plan $Plan -PropertyName "NeedsPurgeCaches") -or $needsUpgrade
    $needsSmoke = (Test-UckkPlanFlag -Plan $Plan -PropertyName "NeedsSmokeTests") -or $needsPurge

    if (-not $hasPlan -and $SafeWhenNoPlan) {
        $needsPurge = $true
        $needsSmoke = $true
    }

    if ($needsUpgrade) {
        "3/6 Upgrade Moodle serveur"
        Invoke-UckkServerMoodleUpgrade
    }
    else {
        "3/6 Upgrade Moodle serveur : skipped"
    }

    "4/6 Apply UCKK site profile serveur"
    Invoke-UckkServerApplySiteProfile

    if ($needsPurge -or $SafeWhenNoPlan) {
        "5/6 Purge caches serveur"
        Clear-UckkServerCaches
    }
    else {
        "5/6 Purge caches serveur : skipped"
    }

    if ($needsSmoke) {
        "6/6 Smoke tests serveur"
        Test-UckkSmokeServer
    }
    else {
        "6/6 Smoke tests serveur : skipped"
    }

    "Server deploy planned termine."
}

function Invoke-UckkSeedLocal {
    param([string]$Preset = "categories", [switch]$DryRun, [switch]$Apply)

    $seedCli = Resolve-UckkLocalPath $Script:LocalRuntimeRoot $Script:SeedCliRelative
    if (-not (Test-Path -LiteralPath $seedCli)) { throw "Seed CLI local introuvable : $seedCli" }
    if (-not (Test-Path -LiteralPath $Script:SeedPresetPathLocal)) { throw "Preset path local introuvable : $Script:SeedPresetPathLocal" }

    $mode = if ($Apply) { "--force" } else { "--dry-run" }

    Push-Location $Script:LocalRuntimeRoot
    try {
        Invoke-UckkCheckedNative { & $Script:LocalPhpExe $seedCli "--presetpath=$Script:SeedPresetPathLocal" "--preset=$Preset" $mode } "Seed local echoue."
    }
    finally {
        Pop-Location
    }
}

function Invoke-UckkSeedServer {
    param([string]$Preset = "categories", [switch]$DryRun, [switch]$Apply)

    $mode = if ($Apply) { "--force" } else { "--dry-run" }
    $seedCli = "$Script:ServerRuntimeRoot/$Script:SeedCliRelative"
    $cmd = "cd '$Script:ServerRuntimeRoot' && sudo -u www-data php '$seedCli' --presetpath='$Script:SeedPresetPathServer' --preset='$Preset' $mode"
    Invoke-UckkSshCommand $cmd
}

function Test-UckkUrl {
    param([string]$Url)

    try {
        $response = Invoke-WebRequest -Uri $Url -UseBasicParsing -TimeoutSec 15 -ErrorAction Stop
        return "[OK] $($response.StatusCode) $Url"
    }
    catch {
        return "[FAIL] $Url $($_.Exception.Message)"
    }
}

function Test-UckkSmokeLocal { foreach ($url in $Script:SmokeLocalUrls) { Test-UckkUrl $url } }
function Test-UckkSmokeServer { foreach ($url in $Script:SmokeServerUrls) { Test-UckkUrl $url } }

function Get-UckkOpsConfigSummary {
    [pscustomobject]@{
        App                 = "$Script:AppName $Script:AppVersion"
        Environment         = $Script:Environment
        LocalSourceRoot     = $Script:LocalSourceRoot
        LocalRuntimeRoot    = $Script:LocalRuntimeRoot
        LocalMoodleRoot     = $Script:LocalMoodleRoot
        LocalMoodleCliRoot  = $Script:LocalMoodleCliRoot
        GitRepoRoot         = $Script:GitRepoRoot
        ServerSshTarget     = $Script:ServerSshTarget
        ServerSourceRoot    = $Script:ServerSourceRoot
        ServerRuntimeRoot   = $Script:ServerRuntimeRoot
        ServerMoodleRoot    = $Script:ServerMoodleRoot
        ServerMoodleCliRoot = $Script:ServerMoodleCliRoot
        SeedPresetPathLocal = $Script:SeedPresetPathLocal
        Components          = ($Script:UckkComponents -join ", ")
    }
}

function ConvertTo-UckkOpsRelativePath {
    param([Parameter(Mandatory = $true)][string]$Path)

    $clean = $Path.Trim()

    if ($clean -match "\s->\s") {
        $parts = $clean -split "\s->\s"
        $clean = $parts[-1].Trim()
    }

    $clean = $clean -replace "\\", "/"

    while ($clean.StartsWith("./")) {
        $clean = $clean.Substring(2)
    }

    return $clean
}

function Resolve-UckkOpsChangedComponent {
    param([Parameter(Mandatory = $true)][string]$Path)

    $normalPath = ($Path -replace "\\", "/").Trim().ToLowerInvariant()
    $components = @($Script:UckkComponents | Where-Object { -not [string]::IsNullOrWhiteSpace([string]$_) })

    foreach ($component in ($components | Sort-Object { ([string]$_).Length } -Descending)) {
        $normalComponent = ([string]$component -replace "\\", "/").Trim().ToLowerInvariant()

        if ($normalPath -eq $normalComponent -or $normalPath.StartsWith("$normalComponent/")) {
            return [string]$component
        }
    }

    if ($normalPath -eq "academic_registry_json" -or $normalPath.StartsWith("academic_registry_json/")) {
        return "academic_registry_json"
    }

    if ($normalPath -eq "tools/uckk-ops" -or $normalPath.StartsWith("tools/uckk-ops/")) {
        return "tools/uckk-ops"
    }

    if ($normalPath -eq "docs" -or $normalPath.StartsWith("docs/")) {
        return "docs"
    }

    return "(outside configured components)"
}

function Get-UckkOpsChangedFiles {
    $statusLines = @(Get-UckkGitPorcelainStatus)
    $items = @()

    foreach ($line in $statusLines) {
        $text = [string]$line

        if ([string]::IsNullOrWhiteSpace($text) -or $text.Length -lt 4) {
            continue
        }

        $status = $text.Substring(0, 2)
        $path = ConvertTo-UckkOpsRelativePath ($text.Substring(3))

        if ([string]::IsNullOrWhiteSpace($path)) {
            continue
        }

        $items += [pscustomobject]@{
            Status    = $status
            Path      = $path
            Component = Resolve-UckkOpsChangedComponent -Path $path
        }
    }

    return $items
}

function Test-UckkOpsPathMatch {
    param(
        [Parameter(Mandatory = $true)][string]$Path,
        [Parameter(Mandatory = $true)][string]$Pattern
    )

    $normalPath = ($Path -replace "\\", "/").Trim().ToLowerInvariant()
    return ($normalPath -match $Pattern)
}

function Resolve-UckkOpsRecommendedOrder {
    param(
        [Parameter(Mandatory = $true)][object]$Plan
    )

    $steps = @()

    if (-not $Plan.HasChanges) {
        return @("No changes detected")
    }

    $steps += "Scan changes"
    $steps += "Prepare local"

    if ($Plan.NeedsAmdBuild) {
        $steps += "  - Build AMD"
    }

    if ($Plan.NeedsLocalSync) {
        $steps += "  - Sync source to local runtime"
    }

    if ($Plan.NeedsMoodleUpgrade) {
        $steps += "  - Run local Moodle upgrade"
    }

    $steps += "  - Apply UCKK site profile local"
    $steps += "  - Purge local caches"

    if ($Plan.NeedsSeedApply) {
        $steps += "  - Review seed dry-run manually"
    }

    if ($Plan.NeedsSmokeTests) {
        $steps += "  - Run local smoke tests"
    }

    $steps += "Publish GitHub"
    $steps += "Publish OVH"
    $steps += "  - Pull server"
    $steps += "  - Sync server source to runtime"

    if ($Plan.NeedsMoodleUpgrade) {
        $steps += "  - Run server Moodle upgrade"
    }

    $steps += "  - Apply UCKK site profile server"
    $steps += "  - Purge server caches"

    if ($Plan.NeedsSmokeTests -or $Plan.NeedsPurgeCaches -or $Plan.NeedsMoodleUpgrade) {
        $steps += "  - Run server smoke tests"
    }

    return $steps
}

function Get-UckkOpsUpdatePlan {
    $changedFiles = @(Get-UckkOpsChangedFiles)
    $hasChanges = ($changedFiles.Count -gt 0)

    $needsLocalSync = $false
    $needsAmdBuild = $false
    $needsMoodleUpgrade = $false
    $needsPurgeCaches = $false
    $needsSmokeTests = $false
    $needsSeedApply = $false
    $warnings = @()

    foreach ($file in $changedFiles) {
        $path = [string]$file.Path
        $component = [string]$file.Component

        if ($component -ne "tools/uckk-ops" -and $component -ne "docs" -and $component -ne "(outside configured components)") {
            $needsLocalSync = $true
        }

        if (Test-UckkOpsPathMatch -Path $path -Pattern '(^|/)amd/src/.*\.js$') {
            $needsAmdBuild = $true
            $needsLocalSync = $true
            $needsPurgeCaches = $true
            $needsSmokeTests = $true
        }

        if (Test-UckkOpsPathMatch -Path $path -Pattern '(^|/)amd/build/.*\.(js|map)$') {
            $needsLocalSync = $true
            $needsSmokeTests = $true
        }

        if (
            (Test-UckkOpsPathMatch -Path $path -Pattern '(^|/)db/.*\.php$') -or
            (Test-UckkOpsPathMatch -Path $path -Pattern '(^|/)db/install\.xml$') -or
            (Test-UckkOpsPathMatch -Path $path -Pattern '(^|/)version\.php$')
        ) {
            $needsMoodleUpgrade = $true
            $needsLocalSync = $true
            $needsPurgeCaches = $true
        }

        if (Test-UckkOpsPathMatch -Path $path -Pattern '(^|/)lang/.*\.php$') {
            $needsLocalSync = $true
            $needsPurgeCaches = $true
        }

        if (
            (Test-UckkOpsPathMatch -Path $path -Pattern '(^|/)templates/.*\.mustache$') -or
            (Test-UckkOpsPathMatch -Path $path -Pattern '(^|/)styles\.css$') -or
            (Test-UckkOpsPathMatch -Path $path -Pattern '^theme/uckk/')
        ) {
            $needsLocalSync = $true
            $needsPurgeCaches = $true
            $needsSmokeTests = $true
        }

        if (Test-UckkOpsPathMatch -Path $path -Pattern '^academic_registry_json/.*\.json$') {
            $needsSeedApply = $true
            $needsPurgeCaches = $true
            $needsSmokeTests = $true
        }

        if ($component -eq "(outside configured components)") {
            $warnings += "Unmapped path: $path"
        }
    }

    if ($needsAmdBuild) {
        $hasBuildOutput = @($changedFiles | Where-Object {
            Test-UckkOpsPathMatch -Path ([string]$_.Path) -Pattern '(^|/)amd/build/.*\.(js|map)$'
        }).Count -gt 0

        if (-not $hasBuildOutput) {
            $warnings += "AMD source changed but no AMD build output is currently changed."
        }
    }

    if ($needsMoodleUpgrade) {
        $warnings += "Moodle upgrade may require --allow-unstable on dev builds."
    }

    $changedComponents = @(
        $changedFiles |
            Select-Object -ExpandProperty Component -Unique |
            Where-Object { -not [string]::IsNullOrWhiteSpace([string]$_) } |
            Sort-Object
    )

    $plan = [pscustomobject]@{
        HasChanges         = $hasChanges
        ChangedFiles       = $changedFiles
        ChangedComponents  = $changedComponents
        NeedsLocalSync     = $needsLocalSync
        NeedsAmdBuild      = $needsAmdBuild
        NeedsMoodleUpgrade = $needsMoodleUpgrade
        NeedsPurgeCaches   = $needsPurgeCaches
        NeedsSmokeTests    = $needsSmokeTests
        NeedsSeedApply     = $needsSeedApply
        Warnings           = @($warnings | Select-Object -Unique)
        RecommendedOrder   = @()
    }

    $plan.RecommendedOrder = @(Resolve-UckkOpsRecommendedOrder -Plan $plan)

    return $plan
}

function Format-UckkOpsUpdatePlan {
    param([Parameter(Mandatory = $true)][object]$Plan)

    $lines = @()

    $lines += "Update plan"
    $lines += "==========="
    $lines += "Has changes: $($Plan.HasChanges)"

    if (-not $Plan.HasChanges) {
        $lines += ""
        $lines += "No local changes detected."
        return $lines
    }

    $lines += ""
    $lines += "Changed components:"
    foreach ($component in @($Plan.ChangedComponents)) {
        $lines += "- $component"
    }

    $lines += ""
    $lines += "Changed files:"
    foreach ($file in @($Plan.ChangedFiles)) {
        $lines += "- $($file.Status) $($file.Path) [$($file.Component)]"
    }

    $lines += ""
    $lines += "Required actions:"
    $lines += "- Local sync: $($Plan.NeedsLocalSync)"
    $lines += "- AMD build: $($Plan.NeedsAmdBuild)"
    $lines += "- Moodle upgrade: $($Plan.NeedsMoodleUpgrade)"
    $lines += "- Purge caches: $($Plan.NeedsPurgeCaches)"
    $lines += "- Smoke tests: $($Plan.NeedsSmokeTests)"
    $lines += "- Seed apply: $($Plan.NeedsSeedApply)"
    $lines += ""
    $lines += "Server actions after push:"
    $lines += "- Server deploy: True"
    $lines += "- Server upgrade: $($Plan.NeedsMoodleUpgrade)"
    $lines += "- Server purge caches: $(($Plan.NeedsPurgeCaches -or $Plan.NeedsMoodleUpgrade))"
    $lines += "- Server smoke tests: $(($Plan.NeedsSmokeTests -or $Plan.NeedsPurgeCaches -or $Plan.NeedsMoodleUpgrade))"

    if (@($Plan.Warnings).Count -gt 0) {
        $lines += ""
        $lines += "Warnings:"
        foreach ($warning in @($Plan.Warnings)) {
            $lines += "- $warning"
        }
    }

    $lines += ""
    $lines += "Recommended order:"
    $index = 1
    foreach ($step in @($Plan.RecommendedOrder)) {
        $lines += "$index. $step"
        $index++
    }

    if ($Plan.NeedsAmdBuild) {
        $lines += ""
        $lines += "AMD command:"
        $lines += "cd `"$Script:LocalMoodleRoot`""
        $lines += "npx grunt amd --root=public/local/uckk --no-color"
    }

    if ($Plan.NeedsMoodleUpgrade) {
        $lines += ""
        $lines += "Upgrade command:"
        $lines += "cd `"$Script:LocalMoodleRoot`""
        $lines += "$Script:LocalPhpExe admin\cli\upgrade.php --non-interactive --allow-unstable"
    }

    return $lines
}

Add-Type -AssemblyName System.Windows.Forms
Add-Type -AssemblyName System.Drawing

function New-UckkButton {
    param(
        [string]$Text,
        [int]$X,
        [int]$Y,
        [scriptblock]$Action
    )

    $button = New-Object System.Windows.Forms.Button
    $button.Text = $Text
    $button.Location = New-Object System.Drawing.Point($X, $Y)
    $button.Size = New-Object System.Drawing.Size(220, 36)
    $button.Add_Click($Action)
    return $button
}

function Write-UckkGuiLog {
    param(
        [string]$Message
    )

    $timestamp = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
    $Script:LogBox.AppendText("[$timestamp] $Message`r`n")
    $Script:LogBox.SelectionStart = $Script:LogBox.Text.Length
    $Script:LogBox.ScrollToCaret()
    [System.Windows.Forms.Application]::DoEvents()
}

function Invoke-UckkGuiAction {
    param(
        [string]$Title,
        [scriptblock]$Action,
        [switch]$Confirm
    )

    try {
        if ($Confirm) {
            $result = [System.Windows.Forms.MessageBox]::Show(
                "Continuer : $Title ?",
                "Confirmation UCKK",
                [System.Windows.Forms.MessageBoxButtons]::YesNo,
                [System.Windows.Forms.MessageBoxIcon]::Warning
            )

            if ($result -ne [System.Windows.Forms.DialogResult]::Yes) {
                Write-UckkGuiLog "Annule : $Title"
                return
            }
        }

        Write-UckkGuiLog "Debut : $Title"

        & $Action 2>&1 | ForEach-Object {
            if ($null -ne $_) {
                Write-UckkGuiLog ([string]$_)
            }
        }

        Write-UckkGuiLog "Termine : $Title"
    }
    catch {
        Write-UckkGuiLog "ERREUR : $Title"
        Write-UckkGuiLog $_.Exception.Message

        [System.Windows.Forms.MessageBox]::Show(
            $_.Exception.Message,
            "Erreur UCKK Ops",
            [System.Windows.Forms.MessageBoxButtons]::OK,
            [System.Windows.Forms.MessageBoxIcon]::Error
        ) | Out-Null
    }
}


function ConvertTo-UckkPhpSingleQuotedString {
    param([AllowNull()][string]$Value)

    if ($null -eq $Value) {
        $Value = ""
    }

    $escaped = $Value.Replace("\", "\\").Replace("'", "\'")
    return "'$escaped'"
}

function ConvertTo-UckkPhpLiteral {
    param([AllowNull()][object]$Value)

    if ($null -eq $Value) {
        return "null"
    }

    if ($Value -is [bool]) {
        if ($Value) { return "1" }
        return "0"
    }

    if ($Value -is [byte] -or $Value -is [int16] -or $Value -is [int] -or $Value -is [int64] -or $Value -is [decimal] -or $Value -is [double] -or $Value -is [single]) {
        return [Convert]::ToString($Value, [Globalization.CultureInfo]::InvariantCulture)
    }

    return ConvertTo-UckkPhpSingleQuotedString -Value ([string]$Value)
}

function New-UckkSiteProfilePhpCode {
    $lines = @()

    $lines += "<?php"
    $lines += "define('CLI_SCRIPT', true);"
    $lines += "require_once(__DIR__ . '/config.php');"
    $lines += 'global $DB;'
    $lines += ""

    foreach ($property in @($Script:SiteProfileConfig.PSObject.Properties)) {
        if ([string]::IsNullOrWhiteSpace([string]$property.Name)) {
            continue
        }

        $nameLiteral = ConvertTo-UckkPhpSingleQuotedString -Value ([string]$property.Name)
        $valueLiteral = ConvertTo-UckkPhpLiteral -Value $property.Value
        $lines += "set_config($nameLiteral, $valueLiteral);"
    }

    $lines += ""
    $lines += '$site = get_site();'
    $lines += '$site->fullname = ' + (ConvertTo-UckkPhpSingleQuotedString -Value $Script:SiteProfileFullname) + ';'
    $lines += '$site->shortname = ' + (ConvertTo-UckkPhpSingleQuotedString -Value $Script:SiteProfileShortname) + ';'
    $lines += '$site->timemodified = time();'
    $lines += '$DB->update_record(' + (ConvertTo-UckkPhpSingleQuotedString -Value 'course') + ', $site);'
    $lines += ""
    $lines += "purge_all_caches();"
    $lines += 'echo "UCKK site profile applied\n";'

    return ($lines -join "`n")
}

function Invoke-UckkLocalApplySiteProfile {
    $configPath = Get-UckkLocalMoodleConfigPath

    if (-not $configPath) {
        throw "config.php Moodle local introuvable."
    }

    $moodleRoot = Split-Path -Parent $configPath
    $tmp = Join-Path $moodleRoot "uckk_site_profile_tmp.php"
    $script = New-UckkSiteProfilePhpCode

    try {
        Set-Content -LiteralPath $tmp -Value $script -Encoding UTF8
        Push-Location $moodleRoot
        try {
            Invoke-UckkCheckedNative { & $Script:LocalPhpExe $tmp } "Apply site profile local echoue."
        }
        finally {
            Pop-Location
        }
    }
    finally {
        if (Test-Path -LiteralPath $tmp) {
            Remove-Item -LiteralPath $tmp -Force
        }
    }
}

function Invoke-UckkServerApplySiteProfile {
    $script = New-UckkSiteProfilePhpCode
    $encoded = [Convert]::ToBase64String([Text.Encoding]::UTF8.GetBytes($script))
    $remoteTmp = "/tmp/uckk_site_profile.php"
    $cmd = "cd '$Script:ServerMoodleRoot' && printf '%s' '$encoded' | base64 -d | sudo tee '$remoteTmp' >/dev/null && sudo -u www-data php '$remoteTmp' && sudo rm -f '$remoteTmp'"

    Invoke-UckkSshCommand $cmd
}

function Start-UckkLocalMoodleIfNeeded {
    $connections = Get-NetTCPConnection -LocalPort 8000 -State Listen -ErrorAction SilentlyContinue

    if ($connections) {
        return "local Moodle already listening on port 8000"
    }

    return Start-UckkLocalMoodle
}

function Format-UckkSimplePlanSummary {
    param([Parameter(Mandatory = $true)][object]$Plan)

    $lines = @()

    if (-not $Plan.HasChanges) {
        $lines += "Etat : aucun changement detecte."
        $lines += "Prochaine action : rien a publier."
    }
    elseif (
        -not $Plan.NeedsLocalSync -and
        -not $Plan.NeedsAmdBuild -and
        -not $Plan.NeedsMoodleUpgrade -and
        -not $Plan.NeedsPurgeCaches -and
        -not $Plan.NeedsSmokeTests -and
        -not $Plan.NeedsSeedApply
    ) {
        $lines += "Etat : changements outils/docs seulement."
        $lines += "Prochaine action : Publier GitHub si tu veux versionner ces changements."
    }
    else {
        $lines += "Etat : changements Moodle detectes."
        $lines += "Prochaine action : Preparer local."
    }

    $lines += ""
    $lines += "Composants :"

    if (@($Plan.ChangedComponents).Count -gt 0) {
        foreach ($component in @($Plan.ChangedComponents)) {
            $lines += "- $component"
        }
    }
    else {
        $lines += "- aucun"
    }

    $lines += ""
    $lines += "Preparer local fera :"

    if ($Plan.NeedsAmdBuild) { $lines += "- build AMD" }
    if ($Plan.NeedsLocalSync) { $lines += "- sync source -> runtime local" }
    if ($Plan.NeedsMoodleUpgrade) { $lines += "- upgrade Moodle local" }
    $lines += "- apply UCKK site profile local"
    $lines += "- purge caches local"
    $lines += "- ouvrir Moodle local"
    if ($Plan.NeedsSmokeTests) { $lines += "- smoke local" }
    if ($Plan.NeedsSeedApply) { $lines += "- seed detecte : dry-run manuel dans l'onglet Seed DB" }

    $lines += ""
    $lines += "Publier OVH fera :"
    $lines += "- git pull serveur"
    $lines += "- sync source -> runtime serveur"
    if ($Plan.NeedsMoodleUpgrade) { $lines += "- upgrade Moodle serveur" }
    $lines += "- apply UCKK site profile serveur"
    $lines += "- purge caches serveur"
    if ($Plan.NeedsSmokeTests -or $Plan.NeedsPurgeCaches -or $Plan.NeedsMoodleUpgrade) { $lines += "- smoke serveur" }

    if (@($Plan.Warnings).Count -gt 0) {
        $lines += ""
        $lines += "Avertissements :"
        foreach ($warning in @($Plan.Warnings)) {
            $lines += "- $warning"
        }
    }

    return ($lines -join [Environment]::NewLine)
}

function Invoke-UckkSimplePrepareLocal {
    if ($null -eq $Script:LastUpdatePlan) {
        "No scanned plan available. Scanning first."
        $Script:LastUpdatePlan = Get-UckkOpsUpdatePlan
        Update-UckkSimplePlanUi -Plan $Script:LastUpdatePlan
    }

    $plan = $Script:LastUpdatePlan

    if ($plan.NeedsAmdBuild) {
        "Build AMD local"
        Invoke-UckkLocalAmdBuild
    }

    if ($plan.NeedsLocalSync -or $plan.HasChanges) {
        "Sync source -> runtime local"
        Sync-UckkLocalSourceToRuntime
    }
    else {
        "Sync local : skipped"
    }

    if ($plan.NeedsMoodleUpgrade) {
        "Upgrade Moodle local"
        Invoke-UckkLocalMoodleUpgrade -AllowUnstable
    }
    else {
        "Upgrade Moodle local : skipped"
    }

    "Apply UCKK site profile local"
    Invoke-UckkLocalApplySiteProfile

    "Launch/open Moodle local"
    Start-UckkLocalMoodleIfNeeded
    Start-Process $Script:LocalUrl | Out-Null
    "opened: $Script:LocalUrl"

    if ($plan.NeedsSmokeTests) {
        "Smoke local"
        Test-UckkSmokeLocal
    }
    else {
        "Smoke local : skipped"
    }

    if ($plan.NeedsSeedApply) {
        "Seed change detected. Use Seed DB tab for dry-run/apply; automatic seed apply is intentionally disabled."
    }

    "Preparer local termine."
}

function Invoke-UckkSimplePublishGithub {
    param([Parameter(Mandatory = $true)][string]$CommitMessage)

    Invoke-UckkGitCommitPush -Message $CommitMessage
}

function Invoke-UckkSimplePublishServer {
    if ($null -eq $Script:LastUpdatePlan) {
        "No scanned plan available. Safe server deploy will purge and smoke after sync."
    }

    Invoke-UckkServerDeployPlanned -Plan $Script:LastUpdatePlan -SafeWhenNoPlan
}

function Invoke-UckkNormalWorkflowMinimal {
    param(
        [string]$CommitMessage
    )

    "Workflow normal minimal : Sync local -> Launch local -> GitHub -> OVH"

    "1/4 Sync local -> Moodle local"
    Sync-UckkLocalSourceToRuntime

    "2/4 Launch Moodle local"
    Start-UckkLocalMoodle
    Start-Process $Script:LocalUrl | Out-Null
    "opened: $Script:LocalUrl"

    "3/4 Sync GitHub"
    Invoke-UckkGitCommitPush -Message $CommitMessage

    "4/4 Trigger sync OVH"
    Invoke-UckkServerDeployPlanned -Plan $Script:LastUpdatePlan -SafeWhenNoPlan

    "Workflow normal minimal termine."
}


function Invoke-UckkLocalAmdBuild {
    $moodleRoot = $Script:LocalMoodleRoot
    $componentRoot = "public/local/uckk"

    if (-not (Test-Path -LiteralPath $moodleRoot)) {
        throw "Moodle root local introuvable : $moodleRoot"
    }

    Push-Location $moodleRoot
    try {
        Invoke-UckkCheckedNative {
            & npx grunt amd "--root=$componentRoot" "--no-color"
        } "Build AMD echoue."
    }
    finally {
        Pop-Location
    }
}

function Invoke-UckkLocalMoodleUpgrade {
    param([switch]$AllowUnstable)

    $upgrade = Get-UckkLocalMoodleCliScript -ScriptName "upgrade.php"

    if (-not $upgrade) {
        throw "Upgrade CLI introuvable. Chemins verifies : $Script:LocalMoodleCliRoot, $Script:LocalRuntimeRoot\admin\cli, $Script:LocalMoodleRoot\admin\cli"
    }

    $arguments = @($upgrade, "--non-interactive")
    if ($AllowUnstable) {
        $arguments += "--allow-unstable"
    }

    Push-Location $Script:LocalMoodleRoot
    try {
        Invoke-UckkCheckedNative {
            & $Script:LocalPhpExe @arguments
        } "Upgrade Moodle local echoue."
    }
    finally {
        Pop-Location
    }
}

function Invoke-UckkSimpleScanUpdatePlan {
    $plan = Get-UckkOpsUpdatePlan
    $Script:LastUpdatePlan = $plan

    Update-UckkSimplePlanUi -Plan $plan

    return Format-UckkOpsUpdatePlan -Plan $plan
}

function Update-UckkSimplePlanUi {
    param([Parameter(Mandatory = $true)][object]$Plan)

    if ($null -eq $Script:SimplePlanStatusLabel) {
        return
    }

    if (-not $Plan.HasChanges) {
        $Script:SimplePlanStatusLabel.Text = "Aucun changement detecte."
    }
    elseif (
        -not $Plan.NeedsLocalSync -and
        -not $Plan.NeedsAmdBuild -and
        -not $Plan.NeedsMoodleUpgrade -and
        -not $Plan.NeedsPurgeCaches -and
        -not $Plan.NeedsSmokeTests -and
        -not $Plan.NeedsSeedApply
    ) {
        $Script:SimplePlanStatusLabel.Text = "Changements outils/docs seulement."
    }
    else {
        $Script:SimplePlanStatusLabel.Text = "Changements Moodle detectes. Prochaine action : Preparer local."
    }

    if ($null -ne $Script:SimplePlanSummaryBox) {
        $Script:SimplePlanSummaryBox.Text = Format-UckkSimplePlanSummary -Plan $Plan
    }
}


$form = New-Object System.Windows.Forms.Form
$form.Text = "$Script:AppName $Script:AppVersion"
$form.Size = New-Object System.Drawing.Size(920, 720)
$form.MinimumSize = New-Object System.Drawing.Size(920, 720)
$form.StartPosition = "CenterScreen"
$form.AutoScaleMode = [System.Windows.Forms.AutoScaleMode]::Dpi

$tabs = New-Object System.Windows.Forms.TabControl
$tabs.Location = New-Object System.Drawing.Point(10, 10)
$tabs.Size = New-Object System.Drawing.Size(880, 420)

$Script:LogBox = New-Object System.Windows.Forms.TextBox
$Script:LogBox.Multiline = $true
$Script:LogBox.ScrollBars = "Vertical"
$Script:LogBox.ReadOnly = $true
$Script:LogBox.Location = New-Object System.Drawing.Point(10, 440)
$Script:LogBox.Size = New-Object System.Drawing.Size(880, 220)
$Script:LogBox.Font = New-Object System.Drawing.Font("Consolas", 9)

# -----------------------------
# Simple
# -----------------------------

$tabSimple = New-Object System.Windows.Forms.TabPage
$tabSimple.Text = "Simple"
$tabSimple.AutoScroll = $true

$simpleTitle = New-Object System.Windows.Forms.Label
$simpleTitle.Text = "Workflow simple"
$simpleTitle.Location = New-Object System.Drawing.Point(20, 18)
$simpleTitle.Size = New-Object System.Drawing.Size(260, 24)
$tabSimple.Controls.Add($simpleTitle)

$simpleDescription = New-Object System.Windows.Forms.Label
$simpleDescription.Text = "Scanner -> Preparer local -> Publier GitHub -> Publier OVH"
$simpleDescription.Location = New-Object System.Drawing.Point(20, 44)
$simpleDescription.Size = New-Object System.Drawing.Size(760, 24)
$tabSimple.Controls.Add($simpleDescription)

$simpleCommitLabel = New-Object System.Windows.Forms.Label
$simpleCommitLabel.Text = "Message de commit"
$simpleCommitLabel.Location = New-Object System.Drawing.Point(20, 82)
$simpleCommitLabel.Size = New-Object System.Drawing.Size(180, 24)
$tabSimple.Controls.Add($simpleCommitLabel)

$Script:SimpleCommitMessageBox = New-Object System.Windows.Forms.TextBox
$Script:SimpleCommitMessageBox.Location = New-Object System.Drawing.Point(20, 106)
$Script:SimpleCommitMessageBox.Size = New-Object System.Drawing.Size(820, 26)
$Script:SimpleCommitMessageBox.Text = $Script:DefaultCommitMessage
$tabSimple.Controls.Add($Script:SimpleCommitMessageBox)

$scanChangesButton = New-UckkButton "1. Scanner" 20 150 {
    Invoke-UckkGuiAction "Scan update plan" {
        Invoke-UckkSimpleScanUpdatePlan
    }
}
$scanChangesButton.Size = New-Object System.Drawing.Size(180, 42)
$tabSimple.Controls.Add($scanChangesButton)

$prepareLocalButton = New-UckkButton "2. Preparer local" 220 150 {
    Invoke-UckkGuiAction "Preparer local" {
        Invoke-UckkSimplePrepareLocal
    }
}
$prepareLocalButton.Size = New-Object System.Drawing.Size(180, 42)
$tabSimple.Controls.Add($prepareLocalButton)

$publishGithubButton = New-UckkButton "3. Publier GitHub" 420 150 {
    Invoke-UckkGuiAction "Publier GitHub" {
        Invoke-UckkSimplePublishGithub -CommitMessage $Script:SimpleCommitMessageBox.Text
    } -Confirm
}
$publishGithubButton.Size = New-Object System.Drawing.Size(180, 42)
$tabSimple.Controls.Add($publishGithubButton)

$publishOvhButton = New-UckkButton "4. Publier OVH" 620 150 {
    Invoke-UckkGuiAction "Publier OVH" {
        Invoke-UckkSimplePublishServer
    } -Confirm
}
$publishOvhButton.Size = New-Object System.Drawing.Size(180, 42)
$tabSimple.Controls.Add($publishOvhButton)

$openLocalButton = New-UckkButton "Ouvrir local" 20 202 {
    Invoke-UckkGuiAction "Ouvrir Moodle local" {
        Start-UckkLocalMoodleIfNeeded
        Start-Process $Script:LocalUrl | Out-Null
        "opened: $Script:LocalUrl"
    }
}
$openLocalButton.Size = New-Object System.Drawing.Size(180, 36)
$tabSimple.Controls.Add($openLocalButton)

$Script:SimplePlanStatusLabel = New-Object System.Windows.Forms.Label
$Script:SimplePlanStatusLabel.Text = "Lancer Scanner avant de publier."
$Script:SimplePlanStatusLabel.Location = New-Object System.Drawing.Point(220, 207)
$Script:SimplePlanStatusLabel.Size = New-Object System.Drawing.Size(620, 28)
$tabSimple.Controls.Add($Script:SimplePlanStatusLabel)

$planSummaryLabel = New-Object System.Windows.Forms.Label
$planSummaryLabel.Text = "Resume du plan"
$planSummaryLabel.Location = New-Object System.Drawing.Point(20, 252)
$planSummaryLabel.Size = New-Object System.Drawing.Size(200, 24)
$tabSimple.Controls.Add($planSummaryLabel)

$Script:SimplePlanSummaryBox = New-Object System.Windows.Forms.TextBox
$Script:SimplePlanSummaryBox.Multiline = $true
$Script:SimplePlanSummaryBox.ScrollBars = "Vertical"
$Script:SimplePlanSummaryBox.ReadOnly = $true
$Script:SimplePlanSummaryBox.WordWrap = $true
$Script:SimplePlanSummaryBox.Location = New-Object System.Drawing.Point(20, 278)
$Script:SimplePlanSummaryBox.Size = New-Object System.Drawing.Size(820, 105)
$Script:SimplePlanSummaryBox.Font = New-Object System.Drawing.Font("Segoe UI", 9)
$Script:SimplePlanSummaryBox.Text = "Aucun scan lance. Clique sur Scanner."
$tabSimple.Controls.Add($Script:SimplePlanSummaryBox)

# -----------------------------
# Local Dev
# -----------------------------

$tabLocal = New-Object System.Windows.Forms.TabPage
$tabLocal.Text = "Local Dev"

$tabLocal.Controls.Add((New-UckkButton "Verifier chemins" 20 30 {
    Invoke-UckkGuiAction "Verifier chemins locaux" {
        Test-UckkOpsPaths
    }
}))

$tabLocal.Controls.Add((New-UckkButton "Scan update plan" 320 30 {
    Invoke-UckkGuiAction "Scan update plan" {
        Format-UckkOpsUpdatePlan -Plan (Get-UckkOpsUpdatePlan)
    }
}))

$tabLocal.Controls.Add((New-UckkButton "Sync source -> Moodle local" 20 80 {
    Invoke-UckkGuiAction "Sync source vers runtime local" {
        Sync-UckkLocalSourceToRuntime
    }
}))

$tabLocal.Controls.Add((New-UckkButton "Purger caches local" 20 130 {
    Invoke-UckkGuiAction "Purge caches Moodle local" {
        Clear-UckkLocalCaches
    }
}))

$tabLocal.Controls.Add((New-UckkButton "Apply site profile local" 320 80 {
    Invoke-UckkGuiAction "Apply UCKK site profile local" {
        Invoke-UckkLocalApplySiteProfile
    }
}))


$tabLocal.Controls.Add((New-UckkButton "Lancer Moodle local" 20 180 {
    Invoke-UckkGuiAction "Lancer Moodle local" {
        Start-UckkLocalMoodle
    }
}))

$tabLocal.Controls.Add((New-UckkButton "Stopper Moodle local" 20 230 {
    Invoke-UckkGuiAction "Stopper Moodle local" {
        Stop-UckkLocalMoodle
    }
}))

# -----------------------------
# Git
# -----------------------------

$tabGit = New-Object System.Windows.Forms.TabPage
$tabGit.Text = "Git"

$commitLabel = New-Object System.Windows.Forms.Label
$commitLabel.Text = "Message de commit"
$commitLabel.Location = New-Object System.Drawing.Point(20, 30)
$commitLabel.Size = New-Object System.Drawing.Size(180, 24)
$tabGit.Controls.Add($commitLabel)

$Script:CommitMessageBox = New-Object System.Windows.Forms.TextBox
$Script:CommitMessageBox.Location = New-Object System.Drawing.Point(20, 55)
$Script:CommitMessageBox.Size = New-Object System.Drawing.Size(520, 26)
$Script:CommitMessageBox.Text = $Script:DefaultCommitMessage
$tabGit.Controls.Add($Script:CommitMessageBox)

$tabGit.Controls.Add((New-UckkButton "Git status" 20 105 {
    Invoke-UckkGuiAction "Git status" {
        Get-UckkGitStatus
    }
}))

$tabGit.Controls.Add((New-UckkButton "Git diff" 20 155 {
    Invoke-UckkGuiAction "Git diff" {
        Get-UckkGitDiff
    }
}))

$tabGit.Controls.Add((New-UckkButton "Commit + push" 20 205 {
    Invoke-UckkGuiAction "Commit + push" {
        Invoke-UckkGitCommitPush -Message $Script:CommitMessageBox.Text
    } -Confirm
}))

# -----------------------------
# uckk.org
# -----------------------------

$tabServer = New-Object System.Windows.Forms.TabPage
$tabServer.Text = "uckk.org"

$tabServer.Controls.Add((New-UckkButton "Tester SSH" 20 30 {
    Invoke-UckkGuiAction "Tester SSH serveur" {
        Test-UckkServerSsh
    }
}))

$tabServer.Controls.Add((New-UckkButton "Pull serveur" 20 80 {
    Invoke-UckkGuiAction "Git pull serveur" {
        Invoke-UckkServerPull
    } -Confirm
}))

$tabServer.Controls.Add((New-UckkButton "Sync source -> runtime" 20 130 {
    Invoke-UckkGuiAction "Sync serveur source vers runtime" {
        Sync-UckkServerSourceToRuntime
    } -Confirm
}))

$tabServer.Controls.Add((New-UckkButton "Upgrade Moodle serveur" 20 180 {
    Invoke-UckkGuiAction "Upgrade Moodle serveur" {
        Invoke-UckkServerMoodleUpgrade
    } -Confirm
}))

$tabServer.Controls.Add((New-UckkButton "Purger caches serveur" 20 230 {
    Invoke-UckkGuiAction "Purge caches serveur" {
        Clear-UckkServerCaches
    } -Confirm
}))

$tabServer.Controls.Add((New-UckkButton "Reload PHP-FPM" 20 280 {
    Invoke-UckkGuiAction "Reload PHP-FPM" {
        Restart-UckkServerPhpFpm
    } -Confirm
}))

$tabServer.Controls.Add((New-UckkButton "Deploy planned server" 320 30 {
    Invoke-UckkGuiAction "Deploy serveur avec plan" {
        Invoke-UckkServerDeployPlanned -Plan $Script:LastUpdatePlan -SafeWhenNoPlan
    } -Confirm
}))

$tabServer.Controls.Add((New-UckkButton "Apply site profile serveur" 320 80 {
    Invoke-UckkGuiAction "Apply UCKK site profile serveur" {
        Invoke-UckkServerApplySiteProfile
    } -Confirm
}))

# -----------------------------
# Seed DB
# -----------------------------

$tabSeed = New-Object System.Windows.Forms.TabPage
$tabSeed.Text = "Seed DB"

$tabSeed.Controls.Add((New-UckkButton "Dry-run categories local" 20 30 {
    Invoke-UckkGuiAction "Dry-run categories local" {
        Invoke-UckkSeedLocal -Preset "categories" -DryRun
    }
}))

$tabSeed.Controls.Add((New-UckkButton "Apply categories local" 20 80 {
    Invoke-UckkGuiAction "Apply categories local" {
        Invoke-UckkSeedLocal -Preset "categories" -Apply
    } -Confirm
}))

$tabSeed.Controls.Add((New-UckkButton "Dry-run categories serveur" 20 150 {
    Invoke-UckkGuiAction "Dry-run categories serveur" {
        Invoke-UckkSeedServer -Preset "categories" -DryRun
    }
}))

$tabSeed.Controls.Add((New-UckkButton "Apply categories serveur" 20 200 {
    Invoke-UckkGuiAction "Apply categories serveur" {
        Invoke-UckkSeedServer -Preset "categories" -Apply
    } -Confirm
}))

# -----------------------------
# Smoke tests
# -----------------------------

$tabSmoke = New-Object System.Windows.Forms.TabPage
$tabSmoke.Text = "Smoke"

$tabSmoke.Controls.Add((New-UckkButton "Smoke local" 20 30 {
    Invoke-UckkGuiAction "Smoke test local" {
        Test-UckkSmokeLocal
    }
}))

$tabSmoke.Controls.Add((New-UckkButton "Smoke serveur" 20 80 {
    Invoke-UckkGuiAction "Smoke test serveur" {
        Test-UckkSmokeServer
    }
}))

# -----------------------------
# Diagnostics
# -----------------------------

$tabDiag = New-Object System.Windows.Forms.TabPage
$tabDiag.Text = "Diagnostics"

$tabDiag.Controls.Add((New-UckkButton "Afficher config" 20 30 {
    Invoke-UckkGuiAction "Afficher configuration" {
        Get-UckkOpsConfigSummary
    }
}))

$tabDiag.Controls.Add((New-UckkButton "Ouvrir dossier app" 20 80 {
    Invoke-UckkGuiAction "Ouvrir dossier app" {
        Start-Process explorer.exe $Script:OpsRoot
    }
}))

$tabDiag.Controls.Add((New-UckkButton "Ouvrir repo source" 20 130 {
    Invoke-UckkGuiAction "Ouvrir repo source" {
        Start-Process explorer.exe $Script:LocalSourceRoot
    }
}))

$tabDiag.Controls.Add((New-UckkButton "Ouvrir runtime local" 20 180 {
    Invoke-UckkGuiAction "Ouvrir runtime local" {
        Start-Process explorer.exe $Script:LocalRuntimeRoot
    }
}))

$tabs.TabPages.Add($tabSimple)
$tabs.TabPages.Add($tabLocal)
$tabs.TabPages.Add($tabGit)
$tabs.TabPages.Add($tabServer)
$tabs.TabPages.Add($tabSeed)
$tabs.TabPages.Add($tabSmoke)
$tabs.TabPages.Add($tabDiag)

$form.Controls.Add($tabs)
$form.Controls.Add($Script:LogBox)

Write-UckkGuiLog "UCKK Ops Console charge."
Write-UckkGuiLog "Config : $Script:ConfigPath"
Write-UckkGuiLog "Source locale : $Script:LocalSourceRoot"
Write-UckkGuiLog "Runtime local : $Script:LocalRuntimeRoot"
Write-UckkGuiLog "Moodle root local : $Script:LocalMoodleRoot"
Write-UckkGuiLog "CLI local : $Script:LocalMoodleCliRoot"
Write-UckkGuiLog "Serveur : $Script:ServerSshTarget"

[void]$form.ShowDialog()

