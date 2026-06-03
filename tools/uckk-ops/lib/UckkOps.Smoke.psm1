# tools/uckk-ops/lib/UckkOps.Smoke.psm1
Set-StrictMode -Version Latest
$ErrorActionPreference = "Stop"

$CommonPath = Join-Path $PSScriptRoot "UckkOps.Common.psm1"
Import-Module $CommonPath -Force -DisableNameChecking

function Test-UckkUrl {
    param(
        [Parameter(Mandatory = $true)][string]$Url,
        [int]$TimeoutSec = 15
    )

    try {
        $response = Invoke-WebRequest -Uri $Url -UseBasicParsing -TimeoutSec $TimeoutSec -ErrorAction Stop

        [pscustomobject]@{
            Url        = $Url
            Ok         = ($response.StatusCode -ge 200 -and $response.StatusCode -lt 400)
            StatusCode = [int]$response.StatusCode
            Error      = $null
        }
    }
    catch {
        [pscustomobject]@{
            Url        = $Url
            Ok         = $false
            StatusCode = $null
            Error      = $_.Exception.Message
        }
    }
}

function Get-UckkSmokeDefaultUrls {
    param(
        [ValidateSet("local", "server")][string]$Target = "local"
    )

    if ($Target -eq "server") {
        $configured = @((Get-UckkOpsVar "SmokeServerUrls" -Default @()))
        if ($configured -and $configured.Count -gt 0) {
            return $configured
        }

        $base = Get-UckkOpsVar "ServerPublicUrl" -Default "https://uckk.org"

        return @(
            $base,
            "$base/course/index.php",
            "$base/local/uckk/programs.php",
            "$base/local/uckk/courses.php",
            "$base/local/uckk/mediatheque.php"
        )
    }

    $configured = @((Get-UckkOpsVar "SmokeLocalUrls" -Default @()))
    if ($configured -and $configured.Count -gt 0) {
        return $configured
    }

    $base = Get-UckkOpsVar "LocalUrl" -Default "http://localhost:8000"

    return @(
        $base,
        "$base/course/index.php",
        "$base/local/uckk/programs.php",
        "$base/local/uckk/courses.php",
        "$base/local/uckk/mediatheque.php"
    )
}

function Get-UckkSmokeBaseUrl {
    param(
        [ValidateSet("local", "server")][string]$Target = "local"
    )

    if ($Target -eq "server") {
        return Get-UckkOpsVar "ServerPublicUrl" -Default "https://uckk.org"
    }

    return Get-UckkOpsVar "LocalUrl" -Default "http://localhost:8000"
}

function Normalize-UckkSmokeChangedPath {
    param([Parameter(Mandatory = $true)][string]$Path)

    return (($Path -replace "\\", "/").Trim())
}

function Get-UckkSmokeUrlsForChangedFiles {
    param(
        [string[]]$ChangedFiles = @(),
        [ValidateSet("local", "server")][string]$Target = "local"
    )

    $base = Get-UckkSmokeBaseUrl -Target $Target
    $defaultUrls = @(Get-UckkSmokeDefaultUrls -Target $Target)

    if (-not $ChangedFiles -or $ChangedFiles.Count -eq 0) {
        return $defaultUrls | Select-Object -Unique
    }

    $urls = New-Object System.Collections.Generic.List[string]
    $changed = @($ChangedFiles | ForEach-Object { Normalize-UckkSmokeChangedPath -Path $_ })

    $needsHome = $false
    $needsCourseIndex = $false
    $needsPrograms = $false
    $needsCourses = $false
    $needsMediatheque = $false
    $needsFullDefault = $false

    foreach ($file in $changed) {
        if ([string]::IsNullOrWhiteSpace($file)) {
            continue
        }

        if ($file -like "theme/uckk/*") {
            $needsFullDefault = $true
            continue
        }

        if ($file -like "local/uckk/styles.css") {
            $needsFullDefault = $true
            continue
        }

        if ($file -like "local/uckk/templates/public_page.mustache" -or
            $file -like "local/uckk/templates/public/*" -or
            $file -like "local/uckk/classes/output/public_page.php" -or
            $file -like "local/uckk/classes/local/public_pages.php") {
            $needsFullDefault = $true
            continue
        }

        if ($file -like "local/uckk/courses.php" -or
            $file -like "local/uckk/templates/pages/course_explorer.mustache" -or
            $file -like "local/uckk/amd/src/course_explorer.js" -or
            $file -like "local/uckk/amd/build/course_explorer.min.js" -or
            $file -like "local/uckk/amd/build/course_explorer.min.js.map" -or
            $file -like "local/uckk/classes/external/search_public_courses.php" -or
            $file -like "local/uckk/classes/local/public_pages/courses.php") {
            $needsCourses = $true
            continue
        }

        if ($file -like "local/uckk/mediatheque.php" -or
            $file -like "local/uckk/templates/pages/mediatheque_explorer.mustache" -or
            $file -like "local/uckk/amd/src/mediatheque_explorer.js" -or
            $file -like "local/uckk/amd/build/mediatheque_explorer.min.js" -or
            $file -like "local/uckk/amd/build/mediatheque_explorer.min.js.map") {
            $needsMediatheque = $true
            continue
        }

        if ($file -like "local/uckk/programs.php" -or
            $file -like "local/uckk/classes/local/public_pages/programs.php") {
            $needsPrograms = $true
            continue
        }

        if ($file -like "local/uckk/db/*" -or
            $file -like "local/uckk/version.php") {
            $needsHome = $true
            $needsCourseIndex = $true
            continue
        }

        if ($file -like "local/uckk/lang/*") {
            $needsHome = $true
            continue
        }

        if ($file -like "blocks/uckk_dashboard/lang/*") {
            $needsHome = $true
            continue
        }

        if ($file -like "academic_registry_json/*") {
            $needsCourseIndex = $true
            $needsPrograms = $true
            $needsCourses = $true
            continue
        }

        if ($file -like "course/format/uckk/*") {
            $needsCourseIndex = $true
            continue
        }

        if ($file -like "mod/uckkarchive/*") {
            $needsMediatheque = $true
            continue
        }

        if ($file -like "tools/uckk-ops/*") {
            continue
        }

        if ($file -like "local/uckk/*") {
            $needsHome = $true
            continue
        }
    }

    if ($needsFullDefault) {
        return $defaultUrls | Select-Object -Unique
    }

    if ($needsHome) {
        $urls.Add($base)
    }

    if ($needsCourseIndex) {
        $urls.Add("$base/course/index.php")
    }

    if ($needsPrograms) {
        $urls.Add("$base/local/uckk/programs.php")
    }

    if ($needsCourses) {
        $urls.Add("$base/local/uckk/courses.php")
    }

    if ($needsMediatheque) {
        $urls.Add("$base/local/uckk/mediatheque.php")
    }

    if ($urls.Count -eq 0) {
        return @()
    }

    return @($urls) | Select-Object -Unique
}

function Invoke-UckkSmokeLocal {
    param([string[]]$Urls = @())

    if (-not $Urls -or $Urls.Count -eq 0) {
        $Urls = @(Get-UckkSmokeDefaultUrls -Target "local")
    }

    foreach ($url in $Urls) {
        Test-UckkUrl -Url $url
    }
}

function Invoke-UckkSmokeServer {
    param([string[]]$Urls = @())

    if (-not $Urls -or $Urls.Count -eq 0) {
        $Urls = @(Get-UckkSmokeDefaultUrls -Target "server")
    }

    foreach ($url in $Urls) {
        Test-UckkUrl -Url $url
    }
}

function Test-UckkSmokeLocal {
    Invoke-UckkSmokeLocal
}

function Test-UckkSmokeServer {
    Invoke-UckkSmokeServer
}

function Invoke-UckkSmoke {
    param(
        [ValidateSet("local", "server")][string]$Target = "local",
        [string[]]$Urls = @()
    )

    if ($Target -eq "server") {
        Invoke-UckkSmokeServer -Urls $Urls
    }
    else {
        Invoke-UckkSmokeLocal -Urls $Urls
    }
}

function Format-UckkSmokeResult {
    param([Parameter(Mandatory = $true)][object[]]$Results)

    foreach ($item in $Results) {
        if ($item.Ok) {
            "[OK]   $($item.StatusCode)  $($item.Url)"
        }
        else {
            "[FAIL] $($item.StatusCode)  $($item.Url)  $($item.Error)"
        }
    }
}

function Assert-UckkSmoke {
    param([Parameter(Mandatory = $true)][object[]]$Results)

    $failed = @($Results | Where-Object { -not $_.Ok })
    if ($failed.Count -gt 0) {
        throw "Smoke test failed: $($failed.Count) URL(s) failed."
    }

    return $true
}

Export-ModuleMember -Function `
    Test-UckkUrl, `
    Get-UckkSmokeDefaultUrls, `
    Get-UckkSmokeUrlsForChangedFiles, `
    Invoke-UckkSmokeLocal, `
    Invoke-UckkSmokeServer, `
    Test-UckkSmokeLocal, `
    Test-UckkSmokeServer, `
    Invoke-UckkSmoke, `
    Format-UckkSmokeResult, `
    Assert-UckkSmoke