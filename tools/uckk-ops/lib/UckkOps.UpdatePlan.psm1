# tools/uckk-ops/lib/UckkOps.UpdatePlan.psm1
# Detects local repository changes and proposes the required UCKK Ops update steps.
#
# This module is intentionally read-only:
# - it reads git status;
# - it classifies changed paths;
# - it returns a plan for the Simple tab;
# - it can recommend UCKK site-profile checks;
# - it does not sync, build, upgrade, purge, commit, push, seed, SSH or deploy.

Set-StrictMode -Version Latest
$ErrorActionPreference = "Stop"

$CommonPath = Join-Path $PSScriptRoot "UckkOps.Common.psm1"
Import-Module $CommonPath -Force -DisableNameChecking

function ConvertTo-UckkOpsRelativePath {
    param(
        [AllowNull()][string]$Path
    )

    if ($null -eq $Path) {
        return ""
    }

    $value = ([string]$Path).Trim()

    if ($value.StartsWith('"') -and $value.EndsWith('"') -and $value.Length -ge 2) {
        $value = $value.Substring(1, $value.Length - 2)
    }

    $value = $value -replace "\\", "/"
    $value = $value -replace "^\./", ""

    return $value.Trim()
}

function ConvertFrom-UckkOpsPorcelainPath {
    param(
        [Parameter(Mandatory = $true)][string]$RawPath
    )

    $path = $RawPath.Trim()

    # Porcelain v1 rename/copy format: old/path -> new/path.
    if ($path -match "\s+->\s+") {
        $parts = $path -split "\s+->\s+", 2
        $path = $parts[1]
    }

    return ConvertTo-UckkOpsRelativePath -Path $path
}

function Get-UckkOpsChangedFiles {
    [CmdletBinding()]
    param()

    $repoRoot = [string](Get-UckkOpsVar "GitRepoRoot")

    if ([string]::IsNullOrWhiteSpace($repoRoot)) {
        throw "Missing GitRepoRoot."
    }

    if (-not (Test-Path -LiteralPath $repoRoot)) {
        throw "Git repo path not found: $repoRoot"
    }

    $output = & git -C $repoRoot status --porcelain=v1 2>&1
    $exitCode = $LASTEXITCODE

    if ($exitCode -ne 0) {
        throw "git status --porcelain=v1 failed in $repoRoot. Output: $($output -join [Environment]::NewLine)"
    }

    $items = @()

    foreach ($line in @($output)) {
        $text = [string]$line

        if ([string]::IsNullOrWhiteSpace($text)) {
            continue
        }

        if ($text.Length -lt 4) {
            continue
        }

        $status = $text.Substring(0, 2)
        $rawpath = $text.Substring(3)
        $path = ConvertFrom-UckkOpsPorcelainPath -RawPath $rawpath

        if ([string]::IsNullOrWhiteSpace($path)) {
            continue
        }

        $items += [pscustomobject]@{
            Status      = $status
            Path        = $path
            RawPath     = $rawpath.Trim()
            IsUntracked = ($status -eq "??")
            IsDeleted   = ($status -match "D")
            IsRenamed   = ($status -match "R")
            Component   = Resolve-UckkOpsChangedComponent -Path $path
        }
    }

    return $items
}

function Resolve-UckkOpsChangedComponent {
    param(
        [Parameter(Mandatory = $true)][string]$Path
    )

    $relative = ConvertTo-UckkOpsRelativePath -Path $Path

    if ([string]::IsNullOrWhiteSpace($relative)) {
        return ""
    }

    # Tooling and documentation are not Moodle components, but they must be
    # classified explicitly so the Simple tab can explain that no Moodle action
    # is required.
    if ($relative -eq "tools/uckk-ops" -or $relative.StartsWith("tools/uckk-ops/")) {
        return "tools/uckk-ops"
    }

    if ($relative -eq "docs" -or $relative.StartsWith("docs/")) {
        return "docs"
    }

    if ($relative -eq "academic_registry_json" -or $relative.StartsWith("academic_registry_json/")) {
        return "academic_registry_json"
    }

    $components = @((Get-UckkOpsVar "UckkComponents" -Default @()))
    $matches = @()

    foreach ($component in $components) {
        $componentPath = ConvertTo-UckkOpsRelativePath -Path ([string]$component)

        if ([string]::IsNullOrWhiteSpace($componentPath)) {
            continue
        }

        if ($relative -eq $componentPath -or $relative.StartsWith("$componentPath/")) {
            $matches += $componentPath
        }
    }

    if ($matches.Count -eq 0) {
        return ""
    }

    return @($matches | Sort-Object Length -Descending)[0]
}

function Test-UckkOpsPathLike {
    param(
        [Parameter(Mandatory = $true)][string]$Path,
        [Parameter(Mandatory = $true)][string[]]$Patterns
    )

    $normalised = (ConvertTo-UckkOpsRelativePath -Path $Path).ToLowerInvariant()

    foreach ($pattern in @($Patterns)) {
        if ([string]::IsNullOrWhiteSpace([string]$pattern)) {
            continue
        }

        $normalisedPattern = (ConvertTo-UckkOpsRelativePath -Path ([string]$pattern)).ToLowerInvariant()

        if ($normalised -like $normalisedPattern) {
            return $true
        }
    }

    return $false
}

function Get-UckkOpsActionPatterns {
    param(
        [Parameter(Mandatory = $true)][string]$ActionName,
        [Parameter(Mandatory = $true)][string[]]$FallbackPatterns
    )

    $config = Get-UckkOpsConfig
    $enabled = Get-UckkConfigProperty -Object $config -Path "updatePlan.actions.$ActionName.enabled" -Default $true

    if (-not [bool]$enabled) {
        return @()
    }

    $patterns = @((Get-UckkConfigProperty -Object $config -Path "updatePlan.actions.$ActionName.patterns" -Default $FallbackPatterns))

    if ($patterns.Count -eq 0) {
        return @($FallbackPatterns)
    }

    return @($patterns | Where-Object { -not [string]::IsNullOrWhiteSpace([string]$_) })
}

function Get-UckkOpsActionExcludePatterns {
    param(
        [Parameter(Mandatory = $true)][string]$ActionName
    )

    $config = Get-UckkOpsConfig
    return @((Get-UckkConfigProperty -Object $config -Path "updatePlan.actions.$ActionName.excludePatterns" -Default @()))
}

function Test-UckkOpsActionMatch {
    param(
        [Parameter(Mandatory = $true)][string]$Path,
        [Parameter(Mandatory = $true)][string]$ActionName,
        [Parameter(Mandatory = $true)][string[]]$FallbackPatterns
    )

    $patterns = @(Get-UckkOpsActionPatterns -ActionName $ActionName -FallbackPatterns $FallbackPatterns)

    if ($patterns.Count -eq 0) {
        return $false
    }

    $excludePatterns = @(Get-UckkOpsActionExcludePatterns -ActionName $ActionName)

    if ($excludePatterns.Count -gt 0 -and (Test-UckkOpsPathLike -Path $Path -Patterns $excludePatterns)) {
        return $false
    }

    return Test-UckkOpsPathLike -Path $Path -Patterns $patterns
}

function Test-UckkOpsSiteProfileEnabled {
    [CmdletBinding()]
    param()

    $config = Get-UckkOpsConfig
    $profile = Get-UckkConfigProperty -Object $config -Path "siteProfile" -Default $null

    if ($null -eq $profile) {
        return $false
    }

    return [bool](Get-UckkConfigProperty -Object $config -Path "siteProfile.enabled" -Default $true)
}

function New-UckkOpsActionState {
    [pscustomobject]@{
        NeedsLocalSync     = $false
        NeedsAmdBuild      = $false
        NeedsMoodleUpgrade = $false
        NeedsPurgeCaches   = $false
        NeedsSmokeTests    = $false
        NeedsSeedApply     = $false
        NeedsSiteProfileLocal = $false

        NeedsServerSync    = $false
        NeedsServerUpgrade = $false
        NeedsServerPurge   = $false
        NeedsServerSmoke   = $false
        NeedsSiteProfileServer = $false

        Reasons            = @()
        Warnings           = @()
    }
}

function Resolve-UckkOpsRequiredActions {
    [CmdletBinding()]
    param(
        [Parameter(Mandatory = $true)][object[]]$ChangedFiles
    )

    $state = New-UckkOpsActionState

    foreach ($file in @($ChangedFiles)) {
        $path = [string]$file.Path
        $component = [string]$file.Component

        if ([string]::IsNullOrWhiteSpace($path)) {
            continue
        }

        if ($component -eq "tools/uckk-ops") {
            $state.Reasons += "Ops tooling changed: $path"
            continue
        }

        if ($component -eq "docs") {
            $state.Reasons += "Documentation changed: $path"
            continue
        }

        if ([string]::IsNullOrWhiteSpace($component)) {
            $state.Warnings += "Unmapped changed path: $path"
        }

        if (Test-UckkOpsActionMatch -Path $path -ActionName "amdBuild" -FallbackPatterns @("*/amd/src/*.js")) {
            $state.NeedsAmdBuild = $true
            $state.NeedsLocalSync = $true
            $state.NeedsPurgeCaches = $true
            $state.NeedsSmokeTests = $true
            $state.NeedsServerSync = $true
            $state.NeedsServerPurge = $true
            $state.NeedsServerSmoke = $true
            $state.Reasons += "AMD source changed: $path"
            continue
        }

        if (Test-UckkOpsPathLike -Path $path -Patterns @("*/amd/build/*.js", "*/amd/build/*.map")) {
            $state.NeedsLocalSync = $true
            $state.NeedsSmokeTests = $true
            $state.NeedsServerSync = $true
            $state.NeedsServerSmoke = $true
            $state.Reasons += "AMD build artifact changed: $path"
            continue
        }

        if (Test-UckkOpsActionMatch -Path $path -ActionName "moodleUpgrade" -FallbackPatterns @("*/db/*.php", "*/db/install.xml", "*/version.php")) {
            $state.NeedsMoodleUpgrade = $true
            $state.NeedsLocalSync = $true
            $state.NeedsPurgeCaches = $true
            $state.NeedsSmokeTests = $true
            $state.NeedsServerSync = $true
            $state.NeedsServerUpgrade = $true
            $state.NeedsServerPurge = $true
            $state.NeedsServerSmoke = $true
            $state.Reasons += "Moodle upgrade-sensitive file changed: $path"
            continue
        }

        if (Test-UckkOpsPathLike -Path $path -Patterns @("*/lang/*.php")) {
            $state.NeedsLocalSync = $true
            $state.NeedsPurgeCaches = $true
            $state.NeedsServerSync = $true
            $state.NeedsServerPurge = $true
            $state.Reasons += "Language file changed: $path"
            continue
        }

        if (Test-UckkOpsPathLike -Path $path -Patterns @("*/templates/*.mustache", "*/styles.css", "theme/uckk/*", "theme/uckk/**")) {
            $state.NeedsLocalSync = $true
            $state.NeedsPurgeCaches = $true
            $state.NeedsSmokeTests = $true
            $state.NeedsServerSync = $true
            $state.NeedsServerPurge = $true
            $state.NeedsServerSmoke = $true
            $state.Reasons += "Template/style/theme file changed: $path"
            continue
        }

        if (Test-UckkOpsActionMatch -Path $path -ActionName "seedApply" -FallbackPatterns @("academic_registry_json/*.json")) {
            $state.NeedsLocalSync = $true
            $state.NeedsSeedApply = $true
            $state.NeedsPurgeCaches = $true
            $state.NeedsSmokeTests = $true
            $state.NeedsServerSync = $true
            $state.NeedsServerPurge = $true
            $state.NeedsServerSmoke = $true
            $state.Reasons += "Academic registry preset changed: $path"
            continue
        }

        if (Test-UckkOpsActionMatch -Path $path -ActionName "localSync" -FallbackPatterns @(
            "local/uckk/**",
            "mod/uckkarchive/**",
            "mod/uckkchallenge/**",
            "mod/uckkassembly/**",
            "theme/uckk/**",
            "admin/tool/uckkseed/**",
            "admin/tool/uckkintegrity/**",
            "course/format/uckk/**",
            "ai/provider/uckk/**",
            "blocks/uckk_dashboard/**",
            "report/uckk/**",
            "academic_registry_json/*.json"
        )) {
            $state.NeedsLocalSync = $true
            $state.NeedsPurgeCaches = $true
            $state.NeedsServerSync = $true
            $state.NeedsServerPurge = $true
            $state.Reasons += "Moodle component file changed: $path"
            continue
        }

        if (-not [string]::IsNullOrWhiteSpace($component) -and $component -notin @("tools/uckk-ops", "docs")) {
            $state.NeedsLocalSync = $true
            $state.NeedsPurgeCaches = $true
            $state.NeedsServerSync = $true
            $state.NeedsServerPurge = $true
            $state.Reasons += "Component file changed: $path"
        }
    }

    if (Test-UckkOpsSiteProfileEnabled) {
        $needsLocalProfile = (
            [bool]$state.NeedsLocalSync -or
            [bool]$state.NeedsAmdBuild -or
            [bool]$state.NeedsMoodleUpgrade -or
            [bool]$state.NeedsPurgeCaches -or
            [bool]$state.NeedsSmokeTests -or
            [bool]$state.NeedsSeedApply
        )

        $needsServerProfile = (
            [bool]$state.NeedsServerSync -or
            [bool]$state.NeedsServerUpgrade -or
            [bool]$state.NeedsServerPurge -or
            [bool]$state.NeedsServerSmoke
        )

        if ($needsLocalProfile) {
            $state.NeedsSiteProfileLocal = $true
            $state.Reasons += "UCKK site profile should be checked locally during prepare."
        }

        if ($needsServerProfile) {
            $state.NeedsSiteProfileServer = $true
            $state.Reasons += "UCKK site profile should be checked on server during publish."
        }
    }

    if ($state.NeedsAmdBuild) {
        $hasBuildOutput = @(
            $ChangedFiles | Where-Object {
                Test-UckkOpsPathLike -Path ([string]$_.Path) -Patterns @("*/amd/build/*.js", "*/amd/build/*.map")
            }
        ).Count -gt 0

        if (-not $hasBuildOutput) {
            $state.Warnings += "AMD source changed but no AMD build output is currently changed. Sync source to local runtime, then build AMD before committing."
        }
    }

    if ($state.NeedsMoodleUpgrade) {
        $state.Warnings += "Moodle upgrade may require --allow-unstable on dev builds."
    }

    [pscustomobject]@{
        NeedsLocalSync     = [bool]$state.NeedsLocalSync
        NeedsAmdBuild      = [bool]$state.NeedsAmdBuild
        NeedsMoodleUpgrade = [bool]$state.NeedsMoodleUpgrade
        NeedsPurgeCaches   = [bool]$state.NeedsPurgeCaches
        NeedsSmokeTests    = [bool]$state.NeedsSmokeTests
        NeedsSeedApply     = [bool]$state.NeedsSeedApply
        NeedsSiteProfileLocal = [bool]$state.NeedsSiteProfileLocal

        NeedsServerSync    = [bool]$state.NeedsServerSync
        NeedsServerUpgrade = [bool]$state.NeedsServerUpgrade
        NeedsServerPurge   = [bool]$state.NeedsServerPurge
        NeedsServerSmoke   = [bool]$state.NeedsServerSmoke
        NeedsSiteProfileServer = [bool]$state.NeedsSiteProfileServer

        Reasons            = @($state.Reasons | Select-Object -Unique)
        Warnings           = @($state.Warnings | Select-Object -Unique)
    }
}

function Test-UckkOpsPlanNeedsLocalAction {
    param(
        [Parameter(Mandatory = $true)][object]$Actions
    )

    return (
        [bool]$Actions.NeedsLocalSync -or
        [bool]$Actions.NeedsAmdBuild -or
        [bool]$Actions.NeedsMoodleUpgrade -or
        [bool]$Actions.NeedsPurgeCaches -or
        [bool]$Actions.NeedsSmokeTests -or
        [bool]$Actions.NeedsSeedApply -or
        [bool]$Actions.NeedsSiteProfileLocal
    )
}

function Test-UckkOpsPlanNeedsServerAction {
    param(
        [Parameter(Mandatory = $true)][object]$Actions
    )

    return (
        [bool]$Actions.NeedsServerSync -or
        [bool]$Actions.NeedsServerUpgrade -or
        [bool]$Actions.NeedsServerPurge -or
        [bool]$Actions.NeedsServerSmoke -or
        [bool]$Actions.NeedsSiteProfileServer
    )
}

function Test-UckkOpsPlanNeedsMoodleAction {
    param(
        [Parameter(Mandatory = $true)][object]$Actions
    )

    return (
        (Test-UckkOpsPlanNeedsLocalAction -Actions $Actions) -or
        (Test-UckkOpsPlanNeedsServerAction -Actions $Actions)
    )
}

function Get-UckkOpsRecommendedOrder {
    param(
        [Parameter(Mandatory = $true)][object]$Actions,
        [bool]$HasChanges = $true
    )

    if (-not $HasChanges) {
        return @()
    }

    $order = @()
    $hasLocalAction = Test-UckkOpsPlanNeedsLocalAction -Actions $Actions
    $hasServerAction = Test-UckkOpsPlanNeedsServerAction -Actions $Actions

    if ($hasLocalAction) {
        $order += "Validate source"
    }

    # AMD builds run against the local runtime component root. Therefore the
    # source repository must be synced to the runtime before invoking grunt.
    if ($Actions.NeedsLocalSync) {
        $order += "Sync source to local runtime"
    }

    if ($Actions.NeedsAmdBuild) {
        $order += "Build AMD from local runtime"
    }

    if ($Actions.NeedsMoodleUpgrade) {
        $order += "Run Moodle upgrade local"
    }

    if ($Actions.NeedsSiteProfileLocal) {
        $order += "Apply UCKK site profile local"
    }

    if ($Actions.NeedsSeedApply) {
        $order += "Run seed dry-run review"
    }

    if ($Actions.NeedsPurgeCaches) {
        $order += "Purge local caches"
    }

    if ($Actions.NeedsSmokeTests) {
        $order += "Run local smoke tests"
    }

    $order += "Review Git diff/status"
    $order += "Commit"
    $order += "Push"

    if ($hasServerAction) {
        $order += "Deploy server"
    }

    if ($Actions.NeedsServerSync) {
        $order += "Sync server source to runtime"
    }

    if ($Actions.NeedsServerUpgrade) {
        $order += "Run Moodle upgrade server"
    }

    if ($Actions.NeedsSiteProfileServer) {
        $order += "Apply UCKK site profile server"
    }

    if ($Actions.NeedsServerPurge) {
        $order += "Purge server caches"
    }

    if ($Actions.NeedsServerSmoke) {
        $order += "Run server smoke tests"
    }

    return $order
}

function Get-UckkOpsRuntimeComponentRoot {
    param(
        [Parameter(Mandatory = $true)][string]$Component
    )

    $localMoodleRoot = [string](Get-UckkOpsVar "LocalMoodleRoot" -Default "")
    $localRuntimeRoot = [string](Get-UckkOpsVar "LocalRuntimeRoot" -Default "")

    if ([string]::IsNullOrWhiteSpace($localMoodleRoot) -or [string]::IsNullOrWhiteSpace($localRuntimeRoot)) {
        return $Component
    }

    try {
        $rootPath = [System.IO.Path]::GetFullPath($localMoodleRoot).TrimEnd("\", "/")
        $runtimePath = [System.IO.Path]::GetFullPath($localRuntimeRoot).TrimEnd("\", "/")
        $componentRuntimePath = [System.IO.Path]::GetFullPath((Join-Path $localRuntimeRoot ($Component -replace "/", [IO.Path]::DirectorySeparatorChar))).TrimEnd("\", "/")

        if ($componentRuntimePath.StartsWith($rootPath, [System.StringComparison]::OrdinalIgnoreCase)) {
            return ($componentRuntimePath.Substring($rootPath.Length).TrimStart("\", "/") -replace "\\", "/")
        }

        if ($componentRuntimePath.StartsWith($runtimePath, [System.StringComparison]::OrdinalIgnoreCase)) {
            return ($componentRuntimePath.Substring($runtimePath.Length).TrimStart("\", "/") -replace "\\", "/")
        }
    }
    catch {
        return $Component
    }

    return $Component
}

function Get-UckkOpsAmdBuildComponents {
    param(
        [Parameter(Mandatory = $true)][object[]]$ChangedFiles
    )

    $components = @(
        $ChangedFiles |
            Where-Object {
                (Test-UckkOpsPathLike -Path ([string]$_.Path) -Patterns @("*/amd/src/*.js")) -and
                -not [string]::IsNullOrWhiteSpace([string]$_.Component) -and
                ([string]$_.Component) -notin @("tools/uckk-ops", "docs", "academic_registry_json")
            } |
            ForEach-Object { [string]$_.Component } |
            Select-Object -Unique |
            Sort-Object
    )

    if ($components.Count -eq 0) {
        return @("local/uckk")
    }

    return $components
}

function Get-UckkOpsCommandPreview {
    param(
        [Parameter(Mandatory = $true)][object]$Actions,
        [object[]]$ChangedFiles = @()
    )

    $localMoodleRoot = [string](Get-UckkOpsVar "LocalMoodleRoot" -Default "")
    $localMoodleCliRoot = [string](Get-UckkOpsVar "LocalMoodleCliRoot" -Default "")
    $phpExe = [string](Get-UckkOpsVar "LocalPhpExe" -Default "php")

    $commands = @()

    if ($Actions.NeedsLocalSync) {
        $commands += "Sync-UckkLocalSourceToRuntime"
    }

    if ($Actions.NeedsAmdBuild) {
        foreach ($component in @(Get-UckkOpsAmdBuildComponents -ChangedFiles $ChangedFiles)) {
            $amdRoot = Get-UckkOpsRuntimeComponentRoot -Component $component
            $commands += "cd `"$localMoodleRoot`""
            $commands += "Get-Process node, rollup, grunt -ErrorAction SilentlyContinue | Stop-Process -Force"
            $commands += "npx grunt amd --root=$amdRoot --no-color"
        }
    }

    if ($Actions.NeedsMoodleUpgrade) {
        if (-not [string]::IsNullOrWhiteSpace($localMoodleCliRoot)) {
            $commands += "cd `"$localMoodleRoot`""
            $commands += "$phpExe admin\cli\upgrade.php --non-interactive --allow-unstable"
        }
        else {
            $commands += "$phpExe admin\cli\upgrade.php --non-interactive --allow-unstable"
        }
    }

    if ($Actions.NeedsSiteProfileLocal) {
        $commands += "Invoke-UckkLocalApplySiteProfile"
    }

    if ($Actions.NeedsSeedApply) {
        $commands += "Invoke-UckkSeedLocal -DryRun"
    }

    if ($Actions.NeedsPurgeCaches) {
        $commands += "Clear-UckkLocalCaches"
    }

    if ($Actions.NeedsSmokeTests) {
        $commands += "Test-UckkSmokeLocal"
    }

    if ($Actions.NeedsServerSync) {
        $commands += "Invoke-UckkServerDeployPlanned -Plan `$Script:LastUpdatePlan"
    }
    elseif ($Actions.NeedsServerPurge -or $Actions.NeedsServerSmoke) {
        $commands += "Invoke-UckkServerDeployPlanned -Plan `$Script:LastUpdatePlan -SkipPull -SkipSync -AlwaysPurge -AlwaysSmoke"
    }

    return $commands
}

function Get-UckkOpsSuggestedLocalSmokeUrls {
    param(
        [Parameter(Mandatory = $true)][object[]]$ChangedFiles
    )

    $urls = @()
    $localUrl = [string](Get-UckkOpsVar "LocalUrl" -Default "http://127.0.0.1:8000")
    $configuredUrls = @((Get-UckkOpsVar "SmokeLocalUrls" -Default @()))

    foreach ($url in $configuredUrls) {
        if (-not [string]::IsNullOrWhiteSpace([string]$url)) {
            $urls += [string]$url
        }
    }

    foreach ($file in @($ChangedFiles)) {
        $path = [string]$file.Path

        if (Test-UckkOpsPathLike -Path $path -Patterns @("theme/uckk/*", "theme/uckk/**")) {
            $urls += "$localUrl/login/index.php"
        }

        if (Test-UckkOpsPathLike -Path $path -Patterns @("local/uckk/courses.php", "local/uckk/templates/pages/course_explorer.mustache", "local/uckk/amd/src/course_explorer.js")) {
            $urls += "$localUrl/local/uckk/courses.php"
        }

        if (Test-UckkOpsPathLike -Path $path -Patterns @("local/uckk/mediatheque.php", "local/uckk/amd/src/mediatheque_explorer.js", "local/uckk/templates/pages/mediatheque_explorer.mustache")) {
            $urls += "$localUrl/local/uckk/mediatheque.php"
        }

        if (Test-UckkOpsPathLike -Path $path -Patterns @("local/uckk/styles.css", "theme/uckk/*", "theme/uckk/**")) {
            $urls += "$localUrl/"
            $urls += "$localUrl/local/uckk/courses.php"
            $urls += "$localUrl/local/uckk/mediatheque.php"
        }
    }

    return @($urls | Where-Object { -not [string]::IsNullOrWhiteSpace($_) } | Select-Object -Unique)
}

function Get-UckkOpsSuggestedServerSmokeUrls {
    param(
        [Parameter(Mandatory = $true)][object[]]$ChangedFiles
    )

    $urls = @()
    $serverUrl = [string](Get-UckkOpsVar "ServerPublicUrl" -Default "https://uckk.org")
    $configuredUrls = @((Get-UckkOpsVar "SmokeServerUrls" -Default @()))

    foreach ($url in $configuredUrls) {
        if (-not [string]::IsNullOrWhiteSpace([string]$url)) {
            $urls += [string]$url
        }
    }

    foreach ($file in @($ChangedFiles)) {
        $path = [string]$file.Path

        if (Test-UckkOpsPathLike -Path $path -Patterns @("theme/uckk/*", "theme/uckk/**")) {
            $urls += "$serverUrl/login/index.php"
        }

        if (Test-UckkOpsPathLike -Path $path -Patterns @("local/uckk/courses.php", "local/uckk/templates/pages/course_explorer.mustache", "local/uckk/amd/src/course_explorer.js")) {
            $urls += "$serverUrl/local/uckk/courses.php"
        }

        if (Test-UckkOpsPathLike -Path $path -Patterns @("local/uckk/mediatheque.php", "local/uckk/amd/src/mediatheque_explorer.js", "local/uckk/templates/pages/mediatheque_explorer.mustache")) {
            $urls += "$serverUrl/local/uckk/mediatheque.php"
        }
    }

    return @($urls | Where-Object { -not [string]::IsNullOrWhiteSpace($_) } | Select-Object -Unique)
}

function Get-UckkOpsSuggestedSmokeUrls {
    param(
        [Parameter(Mandatory = $true)][object[]]$ChangedFiles
    )

    return @(
        Get-UckkOpsSuggestedLocalSmokeUrls -ChangedFiles $ChangedFiles
        Get-UckkOpsSuggestedServerSmokeUrls -ChangedFiles $ChangedFiles
    ) | Where-Object { -not [string]::IsNullOrWhiteSpace($_) } | Select-Object -Unique
}

function Get-UckkOpsSimpleStatus {
    param(
        [Parameter(Mandatory = $true)][object]$Plan
    )

    if (-not $Plan.HasChanges) {
        return "Aucun changement detecte. Rien a publier."
    }

    if (-not $Plan.HasMoodleChanges) {
        return "Changements outils/docs seulement. Publier GitHub si tu veux versionner ces fichiers."
    }

    return "Changements Moodle detectes. Utiliser : Preparer local -> Publier GitHub -> Publier OVH."
}

function Get-UckkOpsSpecialInstructions {
    param(
        [Parameter(Mandatory = $true)][object]$Plan
    )

    $lines = @()
    $prepare = @()
    $publishOvh = @()

    if ($Plan.NeedsLocalSync) {
        $prepare += "sync source -> runtime local"
    }

    if ($Plan.NeedsAmdBuild) {
        $prepare += "build AMD depuis runtime local"
    }

    if ($Plan.NeedsMoodleUpgrade) {
        $prepare += "upgrade Moodle local"
    }

    if ($Plan.NeedsSiteProfileLocal) {
        $prepare += "apply site profile local"
    }

    if ($Plan.NeedsSeedApply) {
        $prepare += "seed dry-run review"
    }

    if ($Plan.NeedsPurgeCaches) {
        $prepare += "purge caches local"
    }

    if ($Plan.NeedsSmokeTests) {
        $prepare += "smoke local"
    }

    if ($prepare.Count -gt 0) {
        $lines += "Preparer local fera : $($prepare -join ', ')."
    }

    if ($Plan.HasChanges) {
        $lines += "Publier GitHub fera : commit + push du code."
    }

    if ($Plan.NeedsServerSync) {
        $publishOvh += "pull/sync serveur"
    }

    if ($Plan.NeedsServerUpgrade) {
        $publishOvh += "upgrade Moodle serveur"
    }

    if ($Plan.NeedsSiteProfileServer) {
        $publishOvh += "apply site profile serveur"
    }

    if ($Plan.NeedsServerPurge) {
        $publishOvh += "purge caches serveur"
    }

    if ($Plan.NeedsServerSmoke) {
        $publishOvh += "smoke serveur"
    }

    if ($publishOvh.Count -gt 0) {
        $lines += "Publier OVH fera : $($publishOvh -join ', ')."
    }

    if ($Plan.NeedsSeedApply) {
        $lines += "Seed detecte : garder le dry-run visible; ne pas appliquer automatiquement depuis Simple."
    }

    if ($lines.Count -eq 0) {
        $lines += "Aucune action Moodle speciale requise."
    }

    return $lines
}

function Format-UckkOpsUpdatePlan {
    [CmdletBinding()]
    param(
        [Parameter(Mandatory = $true)][object]$Plan
    )

    $lines = @()

    $lines += "UCKK Ops Update Plan"
    $lines += "===================="
    $lines += "Has changes: $($Plan.HasChanges)"
    $lines += "Has Moodle changes: $($Plan.HasMoodleChanges)"
    $lines += ""

    if (-not $Plan.HasChanges) {
        $lines += "No changes detected."
        return $lines
    }

    if (-not [string]::IsNullOrWhiteSpace([string]$Plan.SimpleStatus)) {
        $lines += "Simple status:"
        $lines += "- $($Plan.SimpleStatus)"
        $lines += ""
    }

    $lines += "Changed components:"
    foreach ($component in @($Plan.ChangedComponents)) {
        $lines += "- $component"
    }

    if (@($Plan.UnmappedFiles).Count -gt 0) {
        $lines += ""
        $lines += "Unmapped files:"
        foreach ($file in @($Plan.UnmappedFiles)) {
            $lines += "- $file"
        }
    }

    $lines += ""
    $lines += "Changed files:"
    foreach ($file in @($Plan.ChangedFiles)) {
        $lines += "- [$($file.Status)] $($file.Path) [$($file.Component)]"
    }

    $lines += ""
    $lines += "Required local actions:"
    $lines += "- Local sync: $($Plan.NeedsLocalSync)"
    $lines += "- AMD build: $($Plan.NeedsAmdBuild)"
    $lines += "- Moodle upgrade local: $($Plan.NeedsMoodleUpgrade)"
    $lines += "- Purge local caches: $($Plan.NeedsPurgeCaches)"
    $lines += "- Smoke local: $($Plan.NeedsSmokeTests)"
    $lines += "- Seed dry-run: $($Plan.NeedsSeedApply)"
    $lines += "- Site profile local: $($Plan.NeedsSiteProfileLocal)"

    $lines += ""
    $lines += "Required server actions:"
    $lines += "- Server sync: $($Plan.NeedsServerSync)"
    $lines += "- Moodle upgrade server: $($Plan.NeedsServerUpgrade)"
    $lines += "- Purge server caches: $($Plan.NeedsServerPurge)"
    $lines += "- Smoke server: $($Plan.NeedsServerSmoke)"
    $lines += "- Site profile server: $($Plan.NeedsSiteProfileServer)"

    if (@($Plan.SpecialInstructions).Count -gt 0) {
        $lines += ""
        $lines += "Simple instructions:"
        foreach ($instruction in @($Plan.SpecialInstructions)) {
            $lines += "- $instruction"
        }
    }

    if (@($Plan.Reasons).Count -gt 0) {
        $lines += ""
        $lines += "Reasons:"
        foreach ($reason in @($Plan.Reasons)) {
            $lines += "- $reason"
        }
    }

    if (@($Plan.Warnings).Count -gt 0) {
        $lines += ""
        $lines += "Warnings:"
        foreach ($warning in @($Plan.Warnings)) {
            $lines += "- $warning"
        }
    }

    if (@($Plan.RecommendedOrder).Count -gt 0) {
        $lines += ""
        $lines += "Recommended order:"
        $index = 1
        foreach ($step in @($Plan.RecommendedOrder)) {
            $lines += "$index. $step"
            $index++
        }
    }

    if (@($Plan.CommandPreview).Count -gt 0) {
        $lines += ""
        $lines += "Command preview:"
        foreach ($command in @($Plan.CommandPreview)) {
            $lines += "- $command"
        }
    }

    if (@($Plan.SuggestedLocalSmokeUrls).Count -gt 0) {
        $lines += ""
        $lines += "Suggested local smoke URLs:"
        foreach ($url in @($Plan.SuggestedLocalSmokeUrls)) {
            $lines += "- $url"
        }
    }

    if (@($Plan.SuggestedServerSmokeUrls).Count -gt 0) {
        $lines += ""
        $lines += "Suggested server smoke URLs:"
        foreach ($url in @($Plan.SuggestedServerSmokeUrls)) {
            $lines += "- $url"
        }
    }

    return $lines
}

function Get-UckkOpsUpdatePlan {
    [CmdletBinding()]
    param(
        [switch]$Formatted
    )

    Assert-UckkOpsInitialized

    $changedFiles = @(Get-UckkOpsChangedFiles)
    $hasChanges = ($changedFiles.Count -gt 0)

    $changedComponents = @(
        $changedFiles |
            Where-Object { -not [string]::IsNullOrWhiteSpace([string]$_.Component) } |
            ForEach-Object { [string]$_.Component } |
            Select-Object -Unique |
            Sort-Object
    )

    $unmappedFiles = @(
        $changedFiles |
            Where-Object { [string]::IsNullOrWhiteSpace([string]$_.Component) } |
            ForEach-Object { [string]$_.Path } |
            Select-Object -Unique |
            Sort-Object
    )

    $actions = Resolve-UckkOpsRequiredActions -ChangedFiles $changedFiles
    $hasMoodleChanges = Test-UckkOpsPlanNeedsMoodleAction -Actions $actions
    $recommendedOrder = @(Get-UckkOpsRecommendedOrder -Actions $actions -HasChanges:$hasChanges)
    $commandPreview = @(Get-UckkOpsCommandPreview -Actions $actions -ChangedFiles $changedFiles)
    $suggestedLocalSmokeUrls = @(Get-UckkOpsSuggestedLocalSmokeUrls -ChangedFiles $changedFiles)
    $suggestedServerSmokeUrls = @(Get-UckkOpsSuggestedServerSmokeUrls -ChangedFiles $changedFiles)
    $suggestedSmokeUrls = @(Get-UckkOpsSuggestedSmokeUrls -ChangedFiles $changedFiles)

    if (-not $hasChanges) {
        $recommendedOrder = @()
        $commandPreview = @()
        $suggestedLocalSmokeUrls = @()
        $suggestedServerSmokeUrls = @()
        $suggestedSmokeUrls = @()
        $hasMoodleChanges = $false
    }

    $plan = [pscustomobject]@{
        HasChanges              = $hasChanges
        HasMoodleChanges        = $hasMoodleChanges
        IsOpsOnly               = ($hasChanges -and -not $hasMoodleChanges)
        ChangedFiles            = $changedFiles
        ChangedComponents       = $changedComponents
        UnmappedFiles           = $unmappedFiles

        NeedsLocalSync          = [bool]$actions.NeedsLocalSync
        NeedsAmdBuild           = [bool]$actions.NeedsAmdBuild
        NeedsMoodleUpgrade      = [bool]$actions.NeedsMoodleUpgrade
        NeedsPurgeCaches        = [bool]$actions.NeedsPurgeCaches
        NeedsSmokeTests         = [bool]$actions.NeedsSmokeTests
        NeedsSeedApply          = [bool]$actions.NeedsSeedApply
        NeedsSiteProfileLocal   = [bool]$actions.NeedsSiteProfileLocal

        NeedsServerSync         = [bool]$actions.NeedsServerSync
        NeedsServerUpgrade      = [bool]$actions.NeedsServerUpgrade
        NeedsServerPurge        = [bool]$actions.NeedsServerPurge
        NeedsServerSmoke        = [bool]$actions.NeedsServerSmoke
        NeedsSiteProfileServer  = [bool]$actions.NeedsSiteProfileServer

        Reasons                 = @($actions.Reasons)
        Warnings                = @($actions.Warnings)
        RecommendedOrder        = $recommendedOrder
        CommandPreview          = $commandPreview
        SuggestedSmokeUrls      = $suggestedSmokeUrls
        SuggestedLocalSmokeUrls = $suggestedLocalSmokeUrls
        SuggestedServerSmokeUrls = $suggestedServerSmokeUrls

        VisibleActions          = [pscustomobject]@{
            BuildAmd          = [bool]$actions.NeedsAmdBuild
            MoodleUpgrade     = [bool]$actions.NeedsMoodleUpgrade
            PurgeCaches       = [bool]$actions.NeedsPurgeCaches
            SmokeLocal        = [bool]$actions.NeedsSmokeTests
            SeedDryRun        = [bool]$actions.NeedsSeedApply
            SiteProfileLocal  = [bool]$actions.NeedsSiteProfileLocal
            ServerDeploy      = [bool]$actions.NeedsServerSync
            ServerUpgrade     = [bool]$actions.NeedsServerUpgrade
            ServerPurgeCaches = [bool]$actions.NeedsServerPurge
            ServerSmoke       = [bool]$actions.NeedsServerSmoke
            SiteProfileServer = [bool]$actions.NeedsSiteProfileServer
        }

        SimpleActions           = [pscustomobject]@{
            Scanner       = $true
            PrepareLocal  = [bool](Test-UckkOpsPlanNeedsLocalAction -Actions $actions)
            PublishGitHub = [bool]$hasChanges
            PublishOvh    = [bool](Test-UckkOpsPlanNeedsServerAction -Actions $actions)
            OpenLocal     = $true
        }

        SimpleStatus            = ""
        SpecialInstructions     = @()
    }

    $plan.SimpleStatus = Get-UckkOpsSimpleStatus -Plan $plan
    $plan.SpecialInstructions = @(Get-UckkOpsSpecialInstructions -Plan $plan)

    if ($Formatted) {
        return Format-UckkOpsUpdatePlan -Plan $plan
    }

    return $plan
}

Export-ModuleMember -Function `
    Get-UckkOpsUpdatePlan, `
    Get-UckkOpsChangedFiles, `
    Resolve-UckkOpsChangedComponent, `
    Resolve-UckkOpsRequiredActions, `
    Test-UckkOpsSiteProfileEnabled, `
    Get-UckkOpsRecommendedOrder, `
    Format-UckkOpsUpdatePlan