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
        [pscustomobject]@{ Url = $Url; Ok = ($response.StatusCode -ge 200 -and $response.StatusCode -lt 400); StatusCode = [int]$response.StatusCode; Error = $null }
    }
    catch {
        [pscustomobject]@{ Url = $Url; Ok = $false; StatusCode = $null; Error = $_.Exception.Message }
    }
}

function Invoke-UckkSmokeLocal {
    param([string[]]$Urls = @())

    if (-not $Urls -or $Urls.Count -eq 0) {
        $Urls = @((Get-UckkOpsVar "SmokeLocalUrls" -Default @()))
    }
    if (-not $Urls -or $Urls.Count -eq 0) {
        $base = Get-UckkOpsVar "LocalUrl" -Default "http://localhost:8000"
        $Urls = @($base, "$base/course/index.php", "$base/local/uckk/programs.php")
    }

    foreach ($url in $Urls) { Test-UckkUrl -Url $url }
}

function Invoke-UckkSmokeServer {
    param([string[]]$Urls = @())

    if (-not $Urls -or $Urls.Count -eq 0) {
        $Urls = @((Get-UckkOpsVar "SmokeServerUrls" -Default @()))
    }
    if (-not $Urls -or $Urls.Count -eq 0) {
        $base = Get-UckkOpsVar "ServerPublicUrl" -Default "https://uckk.org"
        $Urls = @($base, "$base/course/index.php", "$base/local/uckk/programs.php")
    }

    foreach ($url in $Urls) { Test-UckkUrl -Url $url }
}

function Test-UckkSmokeLocal { Invoke-UckkSmokeLocal }
function Test-UckkSmokeServer { Invoke-UckkSmokeServer }

function Invoke-UckkSmoke {
    param(
        [ValidateSet("local", "server")][string]$Target = "local"
    )

    if ($Target -eq "server") { Invoke-UckkSmokeServer } else { Invoke-UckkSmokeLocal }
}

function Format-UckkSmokeResult {
    param([Parameter(Mandatory = $true)][object[]]$Results)

    foreach ($item in $Results) {
        if ($item.Ok) { "[OK]   $($item.StatusCode)  $($item.Url)" }
        else { "[FAIL] $($item.StatusCode)  $($item.Url)  $($item.Error)" }
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
    Invoke-UckkSmokeLocal, `
    Invoke-UckkSmokeServer, `
    Test-UckkSmokeLocal, `
    Test-UckkSmokeServer, `
    Invoke-UckkSmoke, `
    Format-UckkSmokeResult, `
    Assert-UckkSmoke
