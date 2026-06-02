# tools/uckk-ops/lib/UckkOps.Git.psm1
Set-StrictMode -Version Latest
$ErrorActionPreference = "Stop"

$CommonPath = Join-Path $PSScriptRoot "UckkOps.Common.psm1"
Import-Module $CommonPath -Force -DisableNameChecking

function Get-UckkGitSettings {
    [pscustomobject]@{
        RepoRoot = Get-UckkOpsVar "GitRepoRoot"
        Remote   = Get-UckkOpsVar "GitRemote" -Default "origin"
        Branch   = Get-UckkOpsVar "GitBranch" -Default "main"
    }
}

function Invoke-UckkGit {
    param(
        [Parameter(Mandatory = $true)][string[]]$Arguments
    )

    $settings = Get-UckkGitSettings

    if (-not (Test-Path -LiteralPath $settings.RepoRoot)) {
        throw "Git repo path not found: $($settings.RepoRoot)"
    }

    $output = & git -C $settings.RepoRoot @Arguments 2>&1
    $exitCode = $LASTEXITCODE

    [pscustomobject]@{
        Command  = "git -C `"$($settings.RepoRoot)`" $($Arguments -join ' ')"
        ExitCode = $exitCode
        Success  = ($exitCode -eq 0)
        Output   = ($output -join [Environment]::NewLine)
    }
}

function Test-UckkGitAvailable {
    $output = & git --version 2>&1
    [pscustomobject]@{
        Success  = ($LASTEXITCODE -eq 0)
        ExitCode = $LASTEXITCODE
        Output   = ($output -join [Environment]::NewLine)
    }
}

function Test-UckkGitRepo {
    $settings = Get-UckkGitSettings
    if (-not (Test-Path -LiteralPath $settings.RepoRoot)) {
        return [pscustomobject]@{ Success = $false; Output = "Repo path not found: $($settings.RepoRoot)"; RepoRoot = $settings.RepoRoot }
    }
    $result = Invoke-UckkGit -Arguments @("rev-parse", "--show-toplevel")
    [pscustomobject]@{ Success = $result.Success; Output = $result.Output; RepoRoot = $settings.RepoRoot }
}

function Get-UckkGitStatus { Invoke-UckkGit -Arguments @("status", "--short", "--branch") }
function Get-UckkGitBranch { Invoke-UckkGit -Arguments @("branch", "--show-current") }
function Get-UckkGitLastCommit { Invoke-UckkGit -Arguments @("log", "-1", "--oneline") }

function Get-UckkGitDiff {
    param([switch]$Patch)
    if ($Patch) { Invoke-UckkGit -Arguments @("diff") } else { Invoke-UckkGit -Arguments @("diff", "--stat") }
}

function Get-UckkGitStagedDiff {
    param([switch]$Patch)
    if ($Patch) { Invoke-UckkGit -Arguments @("diff", "--cached") } else { Invoke-UckkGit -Arguments @("diff", "--cached", "--stat") }
}

function Add-UckkGitAll { Invoke-UckkGit -Arguments @("add", "-A") }

function Test-UckkGitHasChanges {
    $result = Invoke-UckkGit -Arguments @("status", "--porcelain")
    [pscustomobject]@{ HasChanges = ($result.Output.Trim() -ne ""); Success = $result.Success; Output = $result.Output }
}

function New-UckkGitCommit {
    param(
        [Parameter(Mandatory = $true)][string]$Message,
        [switch]$AllowEmpty
    )

    if ([string]::IsNullOrWhiteSpace($Message)) {
        throw "Commit message is required."
    }

    if ($AllowEmpty) {
        Invoke-UckkGit -Arguments @("commit", "--allow-empty", "-m", $Message)
    }
    else {
        Invoke-UckkGit -Arguments @("commit", "-m", $Message)
    }
}

function Push-UckkGit {
    $settings = Get-UckkGitSettings
    Invoke-UckkGit -Arguments @("push", $settings.Remote, $settings.Branch)
}

function Pull-UckkGit {
    $settings = Get-UckkGitSettings
    Invoke-UckkGit -Arguments @("pull", $settings.Remote, $settings.Branch)
}

function Invoke-UckkGitCommitPush {
    param(
        [Parameter(Mandatory = $true)][string]$Message
    )

    $changes = Test-UckkGitHasChanges
    if (-not $changes.HasChanges) {
        return [pscustomobject]@{ Success = $true; Step = "NoChanges"; Output = "No local changes to commit." }
    }

    $add = Add-UckkGitAll
    if (-not $add.Success) { return [pscustomobject]@{ Success = $false; Step = "Add"; Output = $add.Output } }

    $commit = New-UckkGitCommit -Message $Message
    if (-not $commit.Success) { return [pscustomobject]@{ Success = $false; Step = "Commit"; Output = $commit.Output } }

    $push = Push-UckkGit
    if (-not $push.Success) { return [pscustomobject]@{ Success = $false; Step = "Push"; Output = $push.Output } }

    [pscustomobject]@{
        Success = $true
        Step    = "Done"
        Output  = @($add.Output, $commit.Output, $push.Output) -join [Environment]::NewLine
    }
}

Export-ModuleMember -Function `
    Get-UckkGitSettings, `
    Invoke-UckkGit, `
    Test-UckkGitAvailable, `
    Test-UckkGitRepo, `
    Get-UckkGitStatus, `
    Get-UckkGitBranch, `
    Get-UckkGitLastCommit, `
    Get-UckkGitDiff, `
    Get-UckkGitStagedDiff, `
    Add-UckkGitAll, `
    Test-UckkGitHasChanges, `
    New-UckkGitCommit, `
    Push-UckkGit, `
    Pull-UckkGit, `
    Invoke-UckkGitCommitPush
