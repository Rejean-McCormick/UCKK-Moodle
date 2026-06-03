# tools/uckk-ops/lib/UckkOps.UpdatePlan.psm1
# Detects local repository changes and proposes the required UCKK Ops update steps.
#
# This module is intentionally read-only:
# - it reads git status;
# - it classifies changed paths;
# - it returns a plan;
# - it does not sync, build, upgrade, purge, commit, push, seed, SSH or deploy.

Set-StrictMode -Version Latest
$ErrorActionPreference = "Stop"

$CommonPath = Join-Path $PSScriptRoot "UckkOps.Common.psm1"
Import-Module $CommonPath -Force -DisableNameChecking

function ConvertTo-UckkOpsRelativePath {
    param(
        [Parameter(Mandatory = $true)][string]$Path
    )

    $value = $Path.Trim()

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

    foreach ($pattern in $Patterns) {
        $normalisedPattern = (ConvertTo-UckkOpsRelativePath -Path $pattern).ToLowerInvariant()

        if ($normalised -like $normalisedPattern) {
            return $true
        }
    }

    return $false
}

function Resolve-UckkOpsRequiredActions {
    [CmdletBinding()]
    param(
        [Parameter(Mandatory = $true)][object[]]$ChangedFiles
    )

    $needsLocalSync = $false
    $needsAmdBuild = $false
    $needsMoodleUpgrade = $false
    $needsPurgeCaches = $false
    $needsSmokeTests = $false
    $needsSeedApply = $false

    $reasons = @()
    $warnings = @()

    foreach ($file in @($ChangedFiles)) {
        $path = [string]$file.Path
        $component = [string]$file.Component

        if ([string]::IsNullOrWhiteSpace($path)) {
            continue
        }

        if ([string]::IsNullOrWhiteSpace($component)) {
            $warnings += "Unmapped changed path: $path"
        }

        if (Test-UckkOpsPathLike -Path $path -Patterns @("tools/uckk-ops/*", "docs/uckk_ops_update_plan.md")) {
            $reasons += "Ops tooling/doc changed: $path"
            continue
        }

        if (Test-UckkOpsPathLike -Path $path -Patterns @("*/amd/src/*.js")) {
            $needsAmdBuild = $true
            $needsLocalSync = $true
            $needsPurgeCaches = $true
            $needsSmokeTests = $true
            $reasons += "AMD source changed: $path"
            continue
        }

        if (Test-UckkOpsPathLike -Path $path -Patterns @("*/amd/build/*.js", "*/amd/build/*.map")) {
            $needsLocalSync = $true
            $needsSmokeTests = $true
            $reasons += "AMD build artifact changed: $path"
            continue
        }

        if (Test-UckkOpsPathLike -Path $path -Patterns @("*/db/*.php", "*/db/install.xml", "*/version.php")) {
            $needsMoodleUpgrade = $true
            $needsLocalSync = $true
            $needsPurgeCaches = $true
            $needsSmokeTests = $true
            $reasons += "Moodle upgrade-sensitive file changed: $path"
            continue
        }

        if (Test-UckkOpsPathLike -Path $path -Patterns @("*/lang/*.php")) {
            $needsLocalSync = $true
            $needsPurgeCaches = $true
            $reasons += "Language file changed: $path"
            continue
        }

        if (Test-UckkOpsPathLike -Path $path -Patterns @("*/templates/*.mustache", "*/styles.css", "theme/uckk/*")) {
            $needsLocalSync = $true
            $needsPurgeCaches = $true
            $needsSmokeTests = $true
            $reasons += "Template/style/theme file changed: $path"
            continue
        }

        if (Test-UckkOpsPathLike -Path $path -Patterns @("academic_registry_json/*.json")) {
            $needsSeedApply = $true
            $needsPurgeCaches = $true
            $needsSmokeTests = $true
            $reasons += "Academic registry preset changed: $path"
            continue
        }

        if (-not [string]::IsNullOrWhiteSpace($component)) {
            $needsLocalSync = $true
            $needsPurgeCaches = $true
            $reasons += "Component file changed: $path"
        }
    }

    [pscustomobject]@{
        NeedsLocalSync     = $needsLocalSync
        NeedsAmdBuild      = $needsAmdBuild
        NeedsMoodleUpgrade = $needsMoodleUpgrade
        NeedsPurgeCaches   = $needsPurgeCaches
        NeedsSmokeTests    = $needsSmokeTests
        NeedsSeedApply     = $needsSeedApply
        Reasons            = @($reasons | Select-Object -Unique)
        Warnings           = @($warnings | Select-Object -Unique)
    }
}

function Get-UckkOpsRecommendedOrder {
    param(
        [Parameter(Mandatory = $true)][object]$Actions
    )

    $order = @()

    if ($Actions.NeedsAmdBuild) {
        $order += "Build AMD"
    }

    if ($Actions.NeedsLocalSync) {
        $order += "Sync source to local runtime"
    }

    if ($Actions.NeedsMoodleUpgrade) {
        $order += "Run Moodle upgrade"
    }

    if ($Actions.NeedsSeedApply) {
        $order += "Run seed apply or seed dry-run review"
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

    return $order
}

function Get-UckkOpsCommandPreview {
    param(
        [Parameter(Mandatory = $true)][object]$Actions
    )

    $localMoodleRoot = [string](Get-UckkOpsVar "LocalMoodleRoot" -Default "")
    $localRuntimeRoot = [string](Get-UckkOpsVar "LocalRuntimeRoot" -Default "")
    $localMoodleCliRoot = [string](Get-UckkOpsVar "LocalMoodleCliRoot" -Default "")
    $phpExe = [string](Get-UckkOpsVar "LocalPhpExe" -Default "php")

    $commands = @()

    if ($Actions.NeedsAmdBuild) {
        $amdRoot = ""

        if (-not [string]::IsNullOrWhiteSpace($localMoodleRoot) -and -not [string]::IsNullOrWhiteSpace($localRuntimeRoot)) {
            $relativeRuntime = ""

            try {
                $rootPath = [System.IO.Path]::GetFullPath($localMoodleRoot).TrimEnd("\", "/")
                $runtimePath = [System.IO.Path]::GetFullPath($localRuntimeRoot).TrimEnd("\", "/")

                if ($runtimePath.StartsWith($rootPath, [System.StringComparison]::OrdinalIgnoreCase)) {
                    $relativeRuntime = $runtimePath.Substring($rootPath.Length).TrimStart("\", "/")
                    $relativeRuntime = $relativeRuntime -replace "\\", "/"
                }
            }
            catch {
                $relativeRuntime = ""
            }

            if ([string]::IsNullOrWhiteSpace($relativeRuntime)) {
                $amdRoot = "local/uckk"
            }
            else {
                $amdRoot = "$relativeRuntime/local/uckk"
            }
        }
        else {
            $amdRoot = "public/local/uckk"
        }

        $commands += "cd `"$localMoodleRoot`""
        $commands += "npx grunt amd --root=$amdRoot --no-color"
    }

    if ($Actions.NeedsLocalSync) {
        $commands += "Sync-UckkLocalSourceToRuntime"
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

    if ($Actions.NeedsSeedApply) {
        $commands += "Invoke-UckkSeedLocal -Apply"
    }

    if ($Actions.NeedsPurgeCaches) {
        $commands += "Clear-UckkLocalCaches"
    }

    if ($Actions.NeedsSmokeTests) {
        $commands += "Test-UckkSmokeLocal"
    }

    return $commands
}

function Get-UckkOpsSuggestedSmokeUrls {
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

        if (Test-UckkOpsPathLike -Path $path -Patterns @("local/uckk/courses.php", "local/uckk/templates/pages/course_explorer.mustache", "local/uckk/amd/src/course_explorer.js")) {
            $urls += "$localUrl/local/uckk/courses.php"
        }

        if (Test-UckkOpsPathLike -Path $path -Patterns @("local/uckk/mediatheque.php", "local/uckk/amd/src/mediatheque_explorer.js", "local/uckk/templates/pages/mediatheque_explorer.mustache")) {
            $urls += "$localUrl/local/uckk/mediatheque.php"
        }

        if (Test-UckkOpsPathLike -Path $path -Patterns @("local/uckk/styles.css", "theme/uckk/*")) {
            $urls += "$localUrl/"
            $urls += "$localUrl/local/uckk/courses.php"
            $urls += "$localUrl/local/uckk/mediatheque.php"
        }
    }

    return @($urls | Where-Object { -not [string]::IsNullOrWhiteSpace($_) } | Select-Object -Unique)
}

function Format-UckkOpsUpdatePlan {
    [CmdletBinding()]
    param(
        [Parameter(Mandatory = $true)][object]$Plan
    )

    $lines = @()

    $lines += "UCKK Ops Update Plan"
    $lines += "===================="
    $lines += ""

    if (-not $Plan.HasChanges) {
        $lines += "No changes detected."
        return $lines
    }

    $lines += "Changed components:"
    foreach ($component in @($Plan.ChangedComponents)) {
        $lines += "- $component"
    }

    if ($Plan.UnmappedFiles.Count -gt 0) {
        $lines += ""
        $lines += "Unmapped files:"
        foreach ($file in @($Plan.UnmappedFiles)) {
            $lines += "- $file"
        }
    }

    $lines += ""
    $lines += "Changed files:"
    foreach ($file in @($Plan.ChangedFiles)) {
        $lines += "- [$($file.Status)] $($file.Path)"
    }

    $lines += ""
    $lines += "Required actions:"
    $lines += "- AMD build: $($Plan.NeedsAmdBuild)"
    $lines += "- Local sync: $($Plan.NeedsLocalSync)"
    $lines += "- Moodle upgrade: $($Plan.NeedsMoodleUpgrade)"
    $lines += "- Purge caches: $($Plan.NeedsPurgeCaches)"
    $lines += "- Smoke tests: $($Plan.NeedsSmokeTests)"
    $lines += "- Seed apply: $($Plan.NeedsSeedApply)"

    if ($Plan.Reasons.Count -gt 0) {
        $lines += ""
        $lines += "Reasons:"
        foreach ($reason in @($Plan.Reasons)) {
            $lines += "- $reason"
        }
    }

    if ($Plan.Warnings.Count -gt 0) {
        $lines += ""
        $lines += "Warnings:"
        foreach ($warning in @($Plan.Warnings)) {
            $lines += "- $warning"
        }
    }

    if ($Plan.RecommendedOrder.Count -gt 0) {
        $lines += ""
        $lines += "Recommended order:"
        $index = 1
        foreach ($step in @($Plan.RecommendedOrder)) {
            $lines += "$index. $step"
            $index++
        }
    }

    if ($Plan.CommandPreview.Count -gt 0) {
        $lines += ""
        $lines += "Command preview:"
        foreach ($command in @($Plan.CommandPreview)) {
            $lines += "- $command"
        }
    }

    if ($Plan.SuggestedSmokeUrls.Count -gt 0) {
        $lines += ""
        $lines += "Suggested smoke URLs:"
        foreach ($url in @($Plan.SuggestedSmokeUrls)) {
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
    $recommendedOrder = @(Get-UckkOpsRecommendedOrder -Actions $actions)
    $commandPreview = @(Get-UckkOpsCommandPreview -Actions $actions)
    $suggestedSmokeUrls = @(Get-UckkOpsSuggestedSmokeUrls -ChangedFiles $changedFiles)

    if (-not $hasChanges) {
        $recommendedOrder = @()
        $commandPreview = @()
        $suggestedSmokeUrls = @()
    }

    $plan = [pscustomobject]@{
        HasChanges          = $hasChanges
        ChangedFiles        = $changedFiles
        ChangedComponents   = $changedComponents
        UnmappedFiles       = $unmappedFiles
        NeedsLocalSync      = [bool]$actions.NeedsLocalSync
        NeedsAmdBuild       = [bool]$actions.NeedsAmdBuild
        NeedsMoodleUpgrade  = [bool]$actions.NeedsMoodleUpgrade
        NeedsPurgeCaches    = [bool]$actions.NeedsPurgeCaches
        NeedsSmokeTests     = [bool]$actions.NeedsSmokeTests
        NeedsSeedApply      = [bool]$actions.NeedsSeedApply
        Reasons             = @($actions.Reasons)
        Warnings            = @($actions.Warnings)
        RecommendedOrder    = $recommendedOrder
        CommandPreview      = $commandPreview
        SuggestedSmokeUrls  = $suggestedSmokeUrls
    }

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
    Format-UckkOpsUpdatePlan