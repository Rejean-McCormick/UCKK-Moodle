# tools/uckk-ops/lib/UckkOps.Common.psm1
# Shared config, variables, logging, command execution.

Set-StrictMode -Version Latest
$ErrorActionPreference = "Stop"

$Script:LibRoot = $PSScriptRoot
$Script:OpsRoot = Split-Path -Parent $Script:LibRoot
$Script:DefaultConfigPath = Join-Path $Script:OpsRoot "uckk-ops.config.json"

function Initialize-UckkOpsState {
    $existing = Get-Variable -Name UckkOps -Scope Global -ErrorAction SilentlyContinue

    if ($null -eq $existing) {
        $Global:UckkOps = [ordered]@{
            ConfigPath   = $null
            Config       = $null
            Vars         = [ordered]@{}
            Initialized  = $false
            LastCommand  = $null
            LastExitCode = $null
        }
    }
    elseif ($null -eq $Global:UckkOps) {
        $Global:UckkOps = [ordered]@{
            ConfigPath   = $null
            Config       = $null
            Vars         = [ordered]@{}
            Initialized  = $false
            LastCommand  = $null
            LastExitCode = $null
        }
    }

    if (-not ($Global:UckkOps -is [System.Collections.IDictionary])) {
        throw "Global UckkOps state exists but is not a dictionary."
    }

    foreach ($key in @("ConfigPath", "Config", "Vars", "Initialized", "LastCommand", "LastExitCode")) {
        if (-not $Global:UckkOps.Contains($key)) {
            switch ($key) {
                "Vars"        { $Global:UckkOps[$key] = [ordered]@{} }
                "Initialized" { $Global:UckkOps[$key] = $false }
                default       { $Global:UckkOps[$key] = $null }
            }
        }
    }
}

Initialize-UckkOpsState

function Write-UckkLog {
    param(
        [Parameter(Mandatory = $true)][string]$Message,
        [ValidateSet("INFO", "OK", "WARN", "ERROR", "CMD")][string]$Level = "INFO"
    )

    $timestamp = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
    Write-Host "[$timestamp][$Level] $Message"
}

function Read-UckkJson {
    param(
        [Parameter(Mandatory = $true)][string]$Path
    )

    if (-not (Test-Path -LiteralPath $Path)) {
        throw "Config file not found: $Path"
    }

    return (Get-Content -LiteralPath $Path -Raw -Encoding UTF8 | ConvertFrom-Json)
}

function Get-UckkConfigProperty {
    param(
        [Parameter(Mandatory = $true)][object]$Object,
        [Parameter(Mandatory = $true)][string]$Path,
        [object]$Default = $null
    )

    $value = $Object
    foreach ($part in ($Path -split "\.")) {
        if ($null -eq $value) {
            return $Default
        }

        $prop = $value.PSObject.Properties[$part]
        if ($null -eq $prop) {
            return $Default
        }

        $value = $prop.Value
    }

    if ($null -eq $value) {
        return $Default
    }

    return $value
}

function Add-UckkVar {
    param(
        [Parameter(Mandatory = $true)][System.Collections.IDictionary]$Vars,
        [Parameter(Mandatory = $true)][string]$Name,
        [object]$Value
    )

    if ($null -eq $Value) {
        $Value = ""
    }

    $Vars[$Name] = $Value
}

function Initialize-UckkOpsConfig {
    param(
        [string]$ConfigPath = $Script:DefaultConfigPath
    )

    Initialize-UckkOpsState

    if ([string]::IsNullOrWhiteSpace($ConfigPath)) {
        $ConfigPath = $Script:DefaultConfigPath
    }

    $resolvedConfigPath = [System.IO.Path]::GetFullPath($ConfigPath)
    $config = Read-UckkJson -Path $resolvedConfigPath

    $vars = [ordered]@{}

    Add-UckkVar $vars "ConfigPath" $resolvedConfigPath
    Add-UckkVar $vars "OpsRoot" $Script:OpsRoot
    Add-UckkVar $vars "LibRoot" $Script:LibRoot

    Add-UckkVar $vars "AppName"        (Get-UckkConfigProperty $config "app.name" "UCKK Ops Console")
    Add-UckkVar $vars "AppVersion"     (Get-UckkConfigProperty $config "app.version" "0.1.0")
    Add-UckkVar $vars "Environment"    (Get-UckkConfigProperty $config "app.environment" "dev")

    Add-UckkVar $vars "LocalSourceRoot"  (Get-UckkConfigProperty $config "local.sourceRoot" "")
    Add-UckkVar $vars "LocalRuntimeRoot" (Get-UckkConfigProperty $config "local.runtimeRoot" "")
    Add-UckkVar $vars "LocalMoodleRoot"  (Get-UckkConfigProperty $config "local.moodleRoot" "")
    Add-UckkVar $vars "LocalPhpExe"      (Get-UckkConfigProperty $config "local.phpExe" "php")
    Add-UckkVar $vars "LocalUrl"         (Get-UckkConfigProperty $config "local.localUrl" "http://127.0.0.1:8000")
    Add-UckkVar $vars "LocalMoodleCliRoot" (Get-UckkConfigProperty $config "local.moodleCliRoot" "")

    Add-UckkVar $vars "GitRepoRoot"      (Get-UckkConfigProperty $config "git.repoRoot" (Get-UckkConfigProperty $config "local.sourceRoot" ""))
    Add-UckkVar $vars "GitRemote"        (Get-UckkConfigProperty $config "git.remote" "origin")
    Add-UckkVar $vars "GitBranch"        (Get-UckkConfigProperty $config "git.branch" "main")
    Add-UckkVar $vars "GitDefaultBranch" (Get-UckkConfigProperty $config "git.branch" "main")
    Add-UckkVar $vars "GitDefaultCommitMessage" (Get-UckkConfigProperty $config "git.defaultCommitMessage" "Update UCKK Moodle ops files")

    Add-UckkVar $vars "ServerSshTarget"      (Get-UckkConfigProperty $config "server.sshTarget" "")
    Add-UckkVar $vars "SshTarget"            (Get-UckkConfigProperty $config "server.sshTarget" "")
    Add-UckkVar $vars "ServerSourceRoot"     (Get-UckkConfigProperty $config "server.sourceRoot" "")
    Add-UckkVar $vars "ServerRuntimeRoot"    (Get-UckkConfigProperty $config "server.runtimeRoot" "")
    Add-UckkVar $vars "ServerMoodleRoot"     (Get-UckkConfigProperty $config "server.moodleRoot" "")
    Add-UckkVar $vars "ServerMoodleCliRoot"  (Get-UckkConfigProperty $config "server.moodleCliRoot" "")
    Add-UckkVar $vars "ServerMoodleDataRoot" (Get-UckkConfigProperty $config "server.moodleDataRoot" "")
    Add-UckkVar $vars "ServerPhpFpmService"  (Get-UckkConfigProperty $config "server.phpFpmService" "php8.3-fpm")
    Add-UckkVar $vars "ServerPublicUrl"      (Get-UckkConfigProperty $config "server.publicUrl" "")

    Add-UckkVar $vars "SeedPresetPathLocal"  (Get-UckkConfigProperty $config "seed.presetPathLocal" "")
    Add-UckkVar $vars "SeedPresetPathServer" (Get-UckkConfigProperty $config "seed.presetPathServer" "")
    Add-UckkVar $vars "SeedCliRelative"      (Get-UckkConfigProperty $config "seed.seedCliRelative" "admin/tool/uckkseed/cli/seed.php")
    Add-UckkVar $vars "SeedPresets"          @((Get-UckkConfigProperty $config "seed.defaultPresets" @("categories", "programs", "pathways", "courses", "cohorts", "competencies", "badges")))

    Add-UckkVar $vars "UckkComponents"       @((Get-UckkConfigProperty $config "components" @()))
    Add-UckkVar $vars "Components"           @((Get-UckkConfigProperty $config "components" @()))

    Add-UckkVar $vars "SmokeLocalUrls"        @((Get-UckkConfigProperty $config "smoke.localUrls" @()))
    Add-UckkVar $vars "SmokeServerUrls"       @((Get-UckkConfigProperty $config "smoke.serverUrls" @()))

    Add-UckkVar $vars "RequireConfirmationForServer"    (Get-UckkConfigProperty $config "safety.requireConfirmationForServer" $true)
    Add-UckkVar $vars "RequireConfirmationForSeedApply" (Get-UckkConfigProperty $config "safety.requireConfirmationForSeedApply" $true)
    Add-UckkVar $vars "RequireGitCleanBeforeServerDeploy" (Get-UckkConfigProperty $config "safety.requireGitCleanBeforeServerDeploy" $true)

    $Global:UckkOps.ConfigPath = $resolvedConfigPath
    $Global:UckkOps.Config = $config
    $Global:UckkOps.Vars = $vars
    $Global:UckkOps.Initialized = $true

    Write-UckkLog "UCKK Ops initialized from $resolvedConfigPath" "OK"
    return $Global:UckkOps
}

function Initialize-UckkOps {
    param(
        [string]$ConfigPath = $Script:DefaultConfigPath
    )

    return Initialize-UckkOpsConfig -ConfigPath $ConfigPath
}

function Assert-UckkOpsInitialized {
    Initialize-UckkOpsState
    if (-not $Global:UckkOps.Initialized) {
        Initialize-UckkOpsConfig | Out-Null
    }
}

function Get-UckkOpsConfig {
    Assert-UckkOpsInitialized
    return $Global:UckkOps.Config
}

function Get-UckkOpsVars {
    Assert-UckkOpsInitialized
    return $Global:UckkOps.Vars
}

function Get-UckkOpsVar {
    param(
        [Parameter(Mandatory = $true)][string]$Name,
        [object]$Default = $null
    )

    Assert-UckkOpsInitialized

    if ($Global:UckkOps.Vars.Contains($Name)) {
        return $Global:UckkOps.Vars[$Name]
    }

    if ($PSBoundParameters.ContainsKey("Default")) {
        return $Default
    }

    throw "Unknown UCKK Ops variable: $Name"
}

function Get-UckkOpsConfigSummary {
    Assert-UckkOpsInitialized
    $vars = Get-UckkOpsVars
    foreach ($key in $vars.Keys) {
        $value = $vars[$key]
        if ($value -is [System.Array]) {
            "$key = $($value -join ', ')"
        } else {
            "$key = $value"
        }
    }
}

function Join-UckkPath {
    param(
        [Parameter(Mandatory = $true)][string]$Root,
        [Parameter(Mandatory = $true)][string]$Child
    )

    return Join-Path $Root ($Child -replace "/", [IO.Path]::DirectorySeparatorChar)
}

function Test-UckkOpsPaths {
    Assert-UckkOpsInitialized

    $checks = @(
        @{ Name = "LocalSourceRoot";      Path = Get-UckkOpsVar "LocalSourceRoot" },
        @{ Name = "LocalRuntimeRoot";     Path = Get-UckkOpsVar "LocalRuntimeRoot" },
        @{ Name = "LocalMoodleRoot";      Path = Get-UckkOpsVar "LocalMoodleRoot" -Default "" },
        @{ Name = "LocalMoodleCliRoot";   Path = Get-UckkOpsVar "LocalMoodleCliRoot" -Default "" },
        @{ Name = "GitRepoRoot";          Path = Get-UckkOpsVar "GitRepoRoot" },
        @{ Name = "SeedPresetPathLocal";  Path = Get-UckkOpsVar "SeedPresetPathLocal" }
    )

    foreach ($check in $checks) {
        [pscustomobject]@{
            Name   = $check.Name
            Path   = $check.Path
            Exists = (Test-Path -LiteralPath $check.Path)
        }
    }
}

function Confirm-UckkAction {
    param(
        [Parameter(Mandatory = $true)][string]$Message,
        [string]$RequiredText = "YES"
    )

    Write-UckkLog $Message "WARN"
    $answer = Read-Host "Type $RequiredText to continue"
    return ($answer -eq $RequiredText)
}

function Invoke-UckkCommand {
    param(
        [Parameter(Mandatory = $true)][string]$FilePath,
        [string[]]$Arguments = @(),
        [string]$WorkingDirectory = "",
        [switch]$RequireSuccess
    )

    Assert-UckkOpsInitialized

    $psi = New-Object System.Diagnostics.ProcessStartInfo
    $psi.FileName = $FilePath
    $psi.Arguments = ($Arguments -join " ")
    $psi.RedirectStandardOutput = $true
    $psi.RedirectStandardError = $true
    $psi.UseShellExecute = $false
    $psi.CreateNoWindow = $true

    if (-not [string]::IsNullOrWhiteSpace($WorkingDirectory)) {
        $psi.WorkingDirectory = $WorkingDirectory
        Write-UckkLog "cd $WorkingDirectory" "CMD"
    }

    $cmdLine = "$FilePath $($Arguments -join ' ')"
    Write-UckkLog $cmdLine "CMD"

    $process = New-Object System.Diagnostics.Process
    $process.StartInfo = $psi

    [void]$process.Start()
    $stdout = $process.StandardOutput.ReadToEnd()
    $stderr = $process.StandardError.ReadToEnd()
    $process.WaitForExit()

    $Global:UckkOps.LastCommand = $cmdLine
    $Global:UckkOps.LastExitCode = $process.ExitCode

    if ($RequireSuccess -and $process.ExitCode -ne 0) {
        throw "Command failed with exit code $($process.ExitCode): $cmdLine`n$stderr"
    }

    return [pscustomobject]@{
        Command  = $cmdLine
        ExitCode = $process.ExitCode
        Success  = ($process.ExitCode -eq 0)
        StdOut   = $stdout
        StdErr   = $stderr
    }
}

function ConvertTo-UckkShellQuoted {
    param(
        [Parameter(Mandatory = $true)][string]$Value
    )

    return "'" + ($Value -replace "'", "'\''") + "'"
}

function Invoke-UckkSsh {
    param(
        [Parameter(Mandatory = $true)][string]$Command,
        [switch]$RequireSuccess
    )

    $target = Get-UckkOpsVar "ServerSshTarget"
    return Invoke-UckkCommand -FilePath "ssh" -Arguments @($target, $Command) -RequireSuccess:$RequireSuccess
}

Export-ModuleMember -Function `
    Initialize-UckkOpsConfig, `
    Initialize-UckkOps, `
    Assert-UckkOpsInitialized, `
    Write-UckkLog, `
    Read-UckkJson, `
    Get-UckkConfigProperty, `
    Get-UckkOpsConfig, `
    Get-UckkOpsVars, `
    Get-UckkOpsVar, `
    Get-UckkOpsConfigSummary, `
    Join-UckkPath, `
    Test-UckkOpsPaths, `
    Confirm-UckkAction, `
    Invoke-UckkCommand, `
    ConvertTo-UckkShellQuoted, `
    Invoke-UckkSsh
