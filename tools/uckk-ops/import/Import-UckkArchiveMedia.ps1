#Requires -Version 7.0
# UCKK_IMPORT_WRAPPER_VERSION=8
<#
.SYNOPSIS
  Imports UCKK inventory media files into mod_uckkarchive through an ops-side Moodle bootstrapper.

.DESCRIPTION
  PowerShell 7 orchestration wrapper for uckk_inventory.json.

  This script performs local preflight validation, resolves source files,
  checks supported formats, writes a preflight report, then calls the ops-side
  PHP Moodle bootstrapper that performs DB and Moodle File API writes.

  It does not write directly to the Moodle database.
  It does not place original files under public/.

.EXAMPLE
  pwsh -NoProfile -ExecutionPolicy Bypass -File .\Import-UckkArchiveMedia.ps1 `
    -MoodleRoot "C:\mycode\UCKK\moodle\moodle" `
    -InventoryPath "C:\mycode\UCKK\uckk-import\uckkarchive\uckk_inventory.json" `
    -OriginalsDir "C:\mycode\UCKK\uckk-import\uckkarchive\originals" `
    -ArchiveId 1 `
    -Mode DryRun

.EXAMPLE
  pwsh -NoProfile -ExecutionPolicy Bypass -File .\Import-UckkArchiveMedia.ps1 `
    -MoodleRoot "C:\mycode\UCKK\moodle\moodle" `
    -InventoryPath "C:\mycode\UCKK\uckk-import\uckkarchive\uckk_inventory.json" `
    -OriginalsDir "C:\mycode\UCKK\uckk-import\uckkarchive\originals" `
    -CmId 42 `
    -Mode Apply
#>

[CmdletBinding()]
param(
  [Parameter(Mandatory = $true)]
  [ValidateNotNullOrEmpty()]
  [string]$MoodleRoot,

  [Parameter(Mandatory = $true)]
  [ValidateNotNullOrEmpty()]
  [string]$InventoryPath,

  [Parameter(Mandatory = $true)]
  [ValidateNotNullOrEmpty()]
  [string]$OriginalsDir,

  [Parameter(Mandatory = $false)]
  [ValidateSet('DryRun', 'Apply')]
  [string]$Mode = 'DryRun',

  [Parameter(Mandatory = $false)]
  [ValidateNotNullOrEmpty()]
  [string]$PhpPath = 'php',

  [Parameter(Mandatory = $false)]
  [ValidateNotNullOrEmpty()]
  [string]$CliScriptRelativePath = 'tools/uckk-ops/import/import_uckkarchive_media.php',

  [Parameter(Mandatory = $false)]
  [string]$ImporterPath = '',

  [Parameter(Mandatory = $false)]
  [int]$ArchiveId = 0,

  [Parameter(Mandatory = $false)]
  [int]$CourseId = 0,

  [Parameter(Mandatory = $false)]
  [int]$CmId = 0,

  [Parameter(Mandatory = $false)]
  [int]$ContextId = 0,

  [Parameter(Mandatory = $false)]
  [int]$UserId = 0,

  [Parameter(Mandatory = $false)]
  [int]$Offset = 0,

  [Parameter(Mandatory = $false)]
  [int]$Limit = 0,

  [Parameter(Mandatory = $false)]
  [ValidateNotNullOrEmpty()]
  [string[]]$SupportedExtensions = @('docx', 'pdf'),

  [Parameter(Mandatory = $false)]
  [switch]$AllowMissingFiles,

  [Parameter(Mandatory = $false)]
  [switch]$UpdateMetadata,

  [Parameter(Mandatory = $false)]
  [switch]$ForceNewVersion,

  [Parameter(Mandatory = $false)]
  [switch]$AllowSystemContext,

  [Parameter(Mandatory = $false)]
  [ValidateNotNullOrEmpty()]
  [string]$ReportPath = ''
)

Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

function New-UckkIssue {
  param(
    [Parameter(Mandatory = $true)][string]$Severity,
    [Parameter(Mandatory = $true)][string]$Code,
    [Parameter(Mandatory = $true)][string]$Message,
    [Parameter(Mandatory = $false)][string]$Item = ''
  )

  return [pscustomobject]@{
    severity = $Severity
    code     = $Code
    message  = $Message
    item     = $Item
  }
}

function Test-CommandExists {
  param([Parameter(Mandatory = $true)][string]$Command)
  return ($null -ne (Get-Command $Command -ErrorAction SilentlyContinue))
}

function Get-SafeLeafName {
  param([Parameter(Mandatory = $true)][string]$Name)

  $leaf = [System.IO.Path]::GetFileName($Name)
  if ([string]::IsNullOrWhiteSpace($leaf) -or $leaf -ne $Name.Replace('/', [System.IO.Path]::DirectorySeparatorChar).Split([System.IO.Path]::DirectorySeparatorChar)[-1]) {
    return $null
  }
  return $leaf
}

function Find-InventorySourceFile {
  param(
    [Parameter(Mandatory = $true)][string]$BaseDir,
    [Parameter(Mandatory = $true)][string]$OriginalName,
    [Parameter(Mandatory = $true)][string]$ProposedName
  )

  $safeOriginal = Get-SafeLeafName -Name $OriginalName
  $safeProposed = Get-SafeLeafName -Name $ProposedName

  foreach ($candidateName in @($safeProposed, $safeOriginal)) {
    if ([string]::IsNullOrWhiteSpace($candidateName)) {
      continue
    }
    $candidate = Join-Path $BaseDir $candidateName
    if (Test-Path -LiteralPath $candidate -PathType Leaf) {
      return (Resolve-Path -LiteralPath $candidate).Path
    }
  }

  # Fallback: case-insensitive leaf-name match, one level recursive.
  $wanted = @($safeProposed, $safeOriginal) | Where-Object { -not [string]::IsNullOrWhiteSpace($_) }
  if ($wanted.Count -eq 0) {
    return $null
  }

  $match = Get-ChildItem -LiteralPath $BaseDir -File -Recurse -ErrorAction SilentlyContinue |
    Where-Object { $wanted -contains $_.Name } |
    Select-Object -First 1

  if ($null -ne $match) {
    return $match.FullName
  }

  return $null
}

function ConvertTo-JsonUtf8File {
  param(
    [Parameter(Mandatory = $true)][object]$InputObject,
    [Parameter(Mandatory = $true)][string]$Path
  )

  $json = $InputObject | ConvertTo-Json -Depth 20
  [System.IO.File]::WriteAllText($Path, $json, [System.Text.UTF8Encoding]::new($false))
}

$issues = [System.Collections.Generic.List[object]]::new()
$warnings = [System.Collections.Generic.List[object]]::new()

$resolvedMoodleRoot = (Resolve-Path -LiteralPath $MoodleRoot).Path
$resolvedInventoryPath = (Resolve-Path -LiteralPath $InventoryPath).Path
$resolvedOriginalsDir = (Resolve-Path -LiteralPath $OriginalsDir).Path
$configPath = Join-Path $resolvedMoodleRoot 'config.php'

if ([string]::IsNullOrWhiteSpace($ImporterPath)) {
  # The ops importer is an ops/repo tool, not a Moodle runtime file.
  # Keep it beside this PowerShell wrapper unless an explicit -ImporterPath is supplied.
  $localImporter = Join-Path $PSScriptRoot 'import_uckkarchive_media.php'
  $cliScriptPath = $localImporter
} else {
  $cliScriptPath = $ImporterPath
}

if (Test-Path -LiteralPath $cliScriptPath -PathType Leaf) {
  $cliScriptPath = (Resolve-Path -LiteralPath $cliScriptPath).Path
}

if (-not (Test-Path -LiteralPath $configPath -PathType Leaf)) {
  $issues.Add((New-UckkIssue -Severity 'error' -Code 'MOODLE_CONFIG_NOT_FOUND' -Message "config.php not found at '$configPath'. MoodleRoot must be the Moodle application root containing config.php, not necessarily the plugin source repo.")) | Out-Null
}

if (-not (Test-Path -LiteralPath $cliScriptPath -PathType Leaf)) {
  $issues.Add((New-UckkIssue -Severity 'error' -Code 'CLI_IMPORTER_NOT_FOUND' -Message "Ops PHP importer not found at '$cliScriptPath'. Keep import_uckkarchive_media.php beside Import-UckkArchiveMedia.ps1 in tools/uckk-ops/import, or pass -ImporterPath explicitly.")) | Out-Null
}

if (-not (Test-CommandExists -Command $PhpPath)) {
  $issues.Add((New-UckkIssue -Severity 'error' -Code 'PHP_NOT_FOUND' -Message "PHP executable '$PhpPath' was not found in PATH.")) | Out-Null
}

try {
  $inventoryRaw = Get-Content -LiteralPath $resolvedInventoryPath -Raw -Encoding UTF8
  $inventory = $inventoryRaw | ConvertFrom-Json -Depth 50
} catch {
  $issues.Add((New-UckkIssue -Severity 'error' -Code 'INVENTORY_JSON_INVALID' -Message "Inventory JSON could not be parsed: $($_.Exception.Message)")) | Out-Null
  $inventory = $null
}

$fileRows = @()
if ($null -ne $inventory) {
  if ($null -eq $inventory.files -or $inventory.files.Count -eq 0) {
    $issues.Add((New-UckkIssue -Severity 'error' -Code 'INVENTORY_EMPTY' -Message 'Inventory does not contain a non-empty files array.')) | Out-Null
  } else {
    $index = 0
    foreach ($entry in $inventory.files) {
      $index++
      $ops = $entry.file_operations
      $originalName = [string]$ops.original_filename
      $proposedName = [string]$ops.proposed_filename
      $action = [string]$ops.action
      $mimetype = [string]$ops.mimetype
      $extension = [System.IO.Path]::GetExtension($proposedName).TrimStart('.').ToLowerInvariant()
      $itemKey = if (-not [string]::IsNullOrWhiteSpace($proposedName)) { $proposedName } else { "entry_$index" }

      if ($action -ne 'import_to_media_library') {
        $warnings.Add((New-UckkIssue -Severity 'warning' -Code 'ACTION_NOT_IMPORT' -Message "Action is '$action', not 'import_to_media_library'." -Item $itemKey)) | Out-Null
      }

      if ([string]::IsNullOrWhiteSpace($originalName) -or [string]::IsNullOrWhiteSpace($proposedName)) {
        $issues.Add((New-UckkIssue -Severity 'error' -Code 'FILENAME_MISSING' -Message 'original_filename and proposed_filename are required.' -Item $itemKey)) | Out-Null
      }

      if ($SupportedExtensions -notcontains $extension) {
        $issues.Add((New-UckkIssue -Severity 'error' -Code 'UNSUPPORTED_EXTENSION' -Message "Extension '.$extension' is not in the supported list: $($SupportedExtensions -join ', ')." -Item $itemKey)) | Out-Null
      }

      $sourcePath = Find-InventorySourceFile -BaseDir $resolvedOriginalsDir -OriginalName $originalName -ProposedName $proposedName
      if ([string]::IsNullOrWhiteSpace($sourcePath)) {
        $severity = if ($AllowMissingFiles.IsPresent) { 'warning' } else { 'error' }
        $target = if ($AllowMissingFiles.IsPresent) { $warnings } else { $issues }
        $target.Add((New-UckkIssue -Severity $severity -Code 'SOURCE_FILE_NOT_FOUND' -Message "Could not find original or proposed filename under '$resolvedOriginalsDir'." -Item $itemKey)) | Out-Null
      }

      $fileRows += [pscustomobject]@{
        index             = $index
        original_filename = $originalName
        proposed_filename = $proposedName
        mimetype          = $mimetype
        extension         = $extension
        source_path       = $sourcePath
        title             = [string]$entry.uckkarchive_media.title
        visibility        = [string]$entry.uckkarchive_media.visibility
        status            = [string]$entry.uckkarchive_media.status
      }
    }

    $duplicateProposed = $fileRows | Group-Object proposed_filename | Where-Object { $_.Count -gt 1 -and -not [string]::IsNullOrWhiteSpace($_.Name) }
    foreach ($dup in $duplicateProposed) {
      $warnings.Add((New-UckkIssue -Severity 'warning' -Code 'DUPLICATE_PROPOSED_FILENAME' -Message "Proposed filename appears $($dup.Count) times." -Item $dup.Name)) | Out-Null
    }
  }
}

if ([string]::IsNullOrWhiteSpace($ReportPath)) {
  $reportDir = Join-Path (Get-Location).Path 'uckk-import-reports'
  New-Item -ItemType Directory -Force -Path $reportDir | Out-Null
  $stamp = Get-Date -Format 'yyyyMMdd_HHmmss'
  $ReportPath = Join-Path $reportDir "uckkarchive_media_import_preflight_$stamp.json"
} else {
  $parent = Split-Path -Parent $ReportPath
  if (-not [string]::IsNullOrWhiteSpace($parent)) {
    New-Item -ItemType Directory -Force -Path $parent | Out-Null
  }
}

$preflight = [pscustomobject]@{
  generated_at         = (Get-Date).ToString('o')
  mode                 = $Mode
  moodle_root          = $resolvedMoodleRoot
  inventory_path       = $resolvedInventoryPath
  originals_dir        = $resolvedOriginalsDir
  cli_script_path      = $cliScriptPath
  supported_extensions = $SupportedExtensions
  archiveid            = $ArchiveId
  courseid             = $CourseId
  cmid                 = $CmId
  contextid            = $ContextId
  userid               = $UserId
  file_count           = $fileRows.Count
  issues               = @($issues)
  warnings             = @($warnings)
  files                = @($fileRows)
}

ConvertTo-JsonUtf8File -InputObject $preflight -Path $ReportPath
Write-Host "Preflight report: $ReportPath"
Write-Host "Files listed: $($fileRows.Count); errors: $($issues.Count); warnings: $($warnings.Count)"

if ($issues.Count -gt 0) {
  $issues | Format-Table severity, code, item, message -AutoSize | Out-String | Write-Host
  throw "Preflight failed. Fix errors or rerun with -AllowMissingFiles only if missing files are intentional for this pass."
}

$argsList = @(
  $cliScriptPath,
  "--moodle-root=$resolvedMoodleRoot",
  "--inventory=$resolvedInventoryPath",
  "--originals=$resolvedOriginalsDir",
  "--dry-run=$(if ($Mode -eq 'DryRun') { 1 } else { 0 })",
  "--allow-missing-files=$(if ($AllowMissingFiles.IsPresent) { 1 } else { 0 })",
  "--update-metadata=$(if ($UpdateMetadata.IsPresent) { 1 } else { 0 })",
  "--force-new-version=$(if ($ForceNewVersion.IsPresent) { 1 } else { 0 })",
  "--allow-system-context=$(if ($AllowSystemContext.IsPresent) { 1 } else { 0 })",
  "--offset=$Offset",
  "--limit=$Limit",
  "--report=$ReportPath.php-import.json"
)

if ($ArchiveId -gt 0) { $argsList += "--archiveid=$ArchiveId" }
if ($CourseId -gt 0) { $argsList += "--courseid=$CourseId" }
if ($CmId -gt 0) { $argsList += "--cmid=$CmId" }
if ($ContextId -gt 0) { $argsList += "--contextid=$ContextId" }
if ($UserId -gt 0) { $argsList += "--userid=$UserId" }

Write-Host "Calling ops importer in $Mode mode..."
& $PhpPath @argsList
$exitCode = $LASTEXITCODE

if ($exitCode -ne 0) {
  throw "ops importer exited with code $exitCode."
}

Write-Host "Done. ops importer report: $ReportPath.php-import.json"
