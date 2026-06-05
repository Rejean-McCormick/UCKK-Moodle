#Requires -Version 7.0
<#
.SYNOPSIS
  Minimal Moodle DB write test for UCKK archive tables.

.DESCRIPTION
  Read/write diagnostic that creates one temporary row in
  {uckkarchive_media_collection} inside a Moodle delegated transaction,
  verifies the row can be read, then performs an intentional rollback.

  If successful, no row remains in the database.
#>

[CmdletBinding()]
param(
  [Parameter(Mandatory = $true)]
  [ValidateNotNullOrEmpty()]
  [string]$MoodleRoot,

  [Parameter(Mandatory = $false)]
  [ValidateNotNullOrEmpty()]
  [string]$OutputDir = ".\uckk-write-test",

  [Parameter(Mandatory = $false)]
  [ValidateNotNullOrEmpty()]
  [string]$PhpPath = "php",

  [Parameter(Mandatory = $false)]
  [int]$ArchiveId = 115,

  [Parameter(Mandatory = $false)]
  [int]$CourseId = 116,

  [Parameter(Mandatory = $false)]
  [int]$Cmid = 339,

  [Parameter(Mandatory = $false)]
  [int]$ContextId = 491,

  [Parameter(Mandatory = $false)]
  [int]$UserId = 2
)

Set-StrictMode -Version Latest
$ErrorActionPreference = "Stop"

$utf8 = [System.Text.UTF8Encoding]::new($false)
[Console]::InputEncoding = $utf8
[Console]::OutputEncoding = $utf8
$OutputEncoding = $utf8

function Test-CommandExists {
  param([Parameter(Mandatory = $true)][string]$Command)
  return ($null -ne (Get-Command $Command -ErrorAction SilentlyContinue))
}

function MdCell {
  param([AllowNull()][object]$Value)
  if ($null -eq $Value) { return "" }
  return ([string]$Value).Replace("|", "\|").Replace("`r", " ").Replace("`n", " ")
}

$outputDirItem = New-Item -ItemType Directory -Force -Path $OutputDir
$jsonPath = Join-Path $outputDirItem.FullName "uckk_moodle_db_write_test_report.json"
$mdPath = Join-Path $outputDirItem.FullName "uckk_moodle_db_write_test_report.md"
$helperPath = Join-Path $outputDirItem.FullName "test_uckk_moodle_db_write.php"

$issues = [System.Collections.Generic.List[object]]::new()

try {
  $resolvedMoodleRoot = (Resolve-Path -LiteralPath $MoodleRoot).Path
}
catch {
  $resolvedMoodleRoot = $MoodleRoot
  $issues.Add([pscustomobject]@{
    stage = "input"
    code = "MOODLE_ROOT_INVALID"
    message = "Could not resolve MoodleRoot: $MoodleRoot"
    detail = $_.Exception.Message
  }) | Out-Null
}

$configPath = Join-Path $resolvedMoodleRoot "config.php"
if (-not (Test-Path -LiteralPath $configPath)) {
  $issues.Add([pscustomobject]@{
    stage = "input"
    code = "CONFIG_NOT_FOUND"
    message = "config.php not found at: $configPath"
    detail = "Pass the Moodle root that contains config.php."
  }) | Out-Null
}

if (-not (Test-CommandExists -Command $PhpPath)) {
  $issues.Add([pscustomobject]@{
    stage = "prerequisite"
    code = "PHP_NOT_FOUND"
    message = "PHP CLI not found: $PhpPath"
    detail = "Pass -PhpPath with the full php.exe path."
  }) | Out-Null
}

$php = @'
<?php
define('CLI_SCRIPT', true);

function issue(&$result, $stage, $code, $message, $context = []) {
    $result['errors'][] = [
        'stage' => $stage,
        'code' => $code,
        'message' => $message,
        'context' => $context,
    ];
    $result['ok'] = false;
}

function throwable_details($e) {
    $out = [
        'class' => get_class($e),
        'message' => $e->getMessage(),
        'code' => $e->getCode(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace_head' => array_slice(explode("\n", $e->getTraceAsString()), 0, 12),
    ];

    foreach (['debuginfo', 'errorcode', 'module', 'link'] as $prop) {
        if (property_exists($e, $prop)) {
            $out[$prop] = $e->$prop;
        }
    }

    if (class_exists('dml_exception') && $e instanceof dml_exception) {
        $out['dml_exception'] = true;
    }

    return $out;
}

function has_col($columns, $name) {
    return array_key_exists($name, $columns);
}

function set_if_col(&$record, $columns, $name, $value) {
    if (has_col($columns, $name)) {
        $record->$name = $value;
    }
}

function make_uuid() {
    $data = random_bytes(16);
    $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
    $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

$result = [
    'generated_at' => date('c'),
    'ok' => true,
    'mode' => 'transactional_write_then_rollback',
    'moodle' => [],
    'target' => [],
    'table' => 'uckkarchive_media_collection',
    'physical_table' => null,
    'insert_record' => null,
    'inserted_id' => null,
    'read_after_insert' => false,
    'rollback_attempted' => false,
    'rollback_exception_expected' => false,
    'record_exists_after_rollback' => null,
    'errors' => [],
];

$moodleroot = $argv[1] ?? '';
$archiveid = (int)($argv[2] ?? 115);
$courseid = (int)($argv[3] ?? 116);
$cmid = (int)($argv[4] ?? 339);
$contextid = (int)($argv[5] ?? 491);
$userid = (int)($argv[6] ?? 2);

$result['target'] = [
    'archiveid' => $archiveid,
    'courseid' => $courseid,
    'cmid' => $cmid,
    'contextid' => $contextid,
    'userid' => $userid,
];

try {
    $config = rtrim($moodleroot, "\\/") . DIRECTORY_SEPARATOR . 'config.php';
    if (!is_file($config)) {
        issue($result, 'config', 'CONFIG_NOT_FOUND', 'config.php not found', ['path' => $config]);
        echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit(0);
    }

    require($config);
    global $CFG, $DB;

    $result['moodle'] = [
        'release' => $CFG->release ?? null,
        'version' => $CFG->version ?? null,
        'dbtype' => $CFG->dbtype ?? null,
        'dbhost' => $CFG->dbhost ?? null,
        'dbname' => $CFG->dbname ?? null,
        'dbuser' => $CFG->dbuser ?? null,
        'prefix' => $CFG->prefix ?? '',
    ];

    $table = 'uckkarchive_media_collection';
    $result['physical_table'] = ($CFG->prefix ?? '') . $table;

    $dbman = $DB->get_manager();
    if (!$dbman->table_exists(new xmldb_table($table))) {
        issue($result, 'schema', 'TABLE_NOT_FOUND', 'Test table does not exist', ['table' => $table]);
        echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit(0);
    }

    $columns = $DB->get_columns($table);
    $now = time();

    $record = new stdClass();
    set_if_col($record, $columns, 'uuid', make_uuid());
    set_if_col($record, $columns, 'archiveid', $archiveid);
    set_if_col($record, $columns, 'courseid', $courseid);
    set_if_col($record, $columns, 'cmid', $cmid);
    set_if_col($record, $columns, 'contextid', $contextid);
    set_if_col($record, $columns, 'ownerid', $userid);
    set_if_col($record, $columns, 'createdby', $userid);
    set_if_col($record, $columns, 'modifiedby', $userid);
    set_if_col($record, $columns, 'title', 'DB write test - rollback ' . date('Y-m-d H:i:s'));
    set_if_col($record, $columns, 'summary', 'Temporary write-access test. This row should be rolled back.');
    set_if_col($record, $columns, 'description', 'Temporary write-access test created by Test-UckkMoodleDbWrite.ps1. Rollback is intentional.');
    set_if_col($record, $columns, 'status', 'active');
    set_if_col($record, $columns, 'visibility', 'private');
    set_if_col($record, $columns, 'sortorder', 999999);
    set_if_col($record, $columns, 'metadata', json_encode([
        'source' => 'Test-UckkMoodleDbWrite.ps1',
        'purpose' => 'write_access_test_rolled_back',
        'created_at' => date('c'),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    set_if_col($record, $columns, 'timecreated', $now);
    set_if_col($record, $columns, 'timemodified', $now);

    $result['insert_record'] = $record;

    $transaction = null;
    try {
        $transaction = $DB->start_delegated_transaction();

        $id = $DB->insert_record($table, $record);
        $result['inserted_id'] = $id;
        $result['read_after_insert'] = $DB->record_exists($table, ['id' => $id]);

        $result['rollback_attempted'] = true;

        try {
            $transaction->rollback(new Exception('Intentional rollback after successful UCKK DB write test.'));
        } catch (Throwable $rollbackException) {
            // Moodle normally rethrows the exception passed to rollback().
            $result['rollback_exception_expected'] = true;
            $result['rollback_exception'] = throwable_details($rollbackException);
        }

        $result['record_exists_after_rollback'] = $DB->record_exists($table, ['id' => $id]);

        if (!$result['read_after_insert']) {
            issue($result, 'write', 'INSERT_NOT_READABLE', 'Insert returned an id, but the row could not be read back.', ['id' => $id]);
        }

        if ($result['record_exists_after_rollback']) {
            issue($result, 'rollback', 'ROLLBACK_DID_NOT_REMOVE_ROW', 'The test row still exists after rollback.', ['id' => $id]);
        }
    } catch (Throwable $e) {
        if ($transaction) {
            try {
                $transaction->rollback($e);
            } catch (Throwable $rollbackAfterError) {
                $result['rollback_after_error'] = throwable_details($rollbackAfterError);
            }
        }

        issue($result, 'write', 'WRITE_TEST_FAILED', $e->getMessage(), [
            'exception' => throwable_details($e),
            'record_attempted' => $record,
        ]);
    }
} catch (Throwable $e) {
    issue($result, 'fatal', 'WRITE_TEST_FATAL', $e->getMessage(), ['exception' => throwable_details($e)]);
}

echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
'@

Set-Content -LiteralPath $helperPath -Value $php -Encoding UTF8

if ($issues.Count -gt 0) {
  $result = [ordered]@{
    generated_at = (Get-Date).ToString("o")
    ok = $false
    mode = "preflight"
    errors = @($issues)
  }
}
else {
  $raw = & $PhpPath $helperPath $resolvedMoodleRoot $ArchiveId $CourseId $Cmid $ContextId $UserId 2>&1
  $exit = $LASTEXITCODE
  $rawText = ($raw | Out-String).Trim()

  if ($exit -ne 0) {
    $result = [ordered]@{
      generated_at = (Get-Date).ToString("o")
      ok = $false
      mode = "php_helper"
      errors = @([ordered]@{
        stage = "php"
        code = "PHP_EXITED_NONZERO"
        message = "PHP exited with code $exit"
        output = $rawText
      })
    }
  }
  else {
    try {
      $result = $rawText | ConvertFrom-Json
    }
    catch {
      $result = [ordered]@{
        generated_at = (Get-Date).ToString("o")
        ok = $false
        mode = "parse_php_json"
        errors = @([ordered]@{
          stage = "powershell"
          code = "PHP_JSON_PARSE_FAILED"
          message = $_.Exception.Message
          output = $rawText
        })
      }
    }
  }
}

$result | ConvertTo-Json -Depth 50 | Set-Content -LiteralPath $jsonPath -Encoding UTF8

$lines = [System.Collections.Generic.List[string]]::new()
$lines.Add("# UCKK Moodle DB write test")
$lines.Add("")
$lines.Add("- Generated at: " + $result.generated_at)
$lines.Add("- OK: " + $result.ok)
$lines.Add("- Mode: " + $result.mode)
if ($result.PSObject.Properties.Name -contains "physical_table") {
  $lines.Add("- Physical table: " + $result.physical_table)
}
if ($result.PSObject.Properties.Name -contains "inserted_id") {
  $lines.Add("- Inserted id: " + $result.inserted_id)
  $lines.Add("- Read after insert: " + $result.read_after_insert)
  $lines.Add("- Rollback attempted: " + $result.rollback_attempted)
  $lines.Add("- Record exists after rollback: " + $result.record_exists_after_rollback)
}
$lines.Add("")
$lines.Add("## Errors")
$lines.Add("")
$errorCount = 0
if ($null -ne $result.errors) {
  $errorCount = ($result.errors | Measure-Object).Count
}
if ($errorCount -eq 0) {
  $lines.Add("No errors.")
}
else {
  $lines.Add("| Stage | Code | Message |")
  $lines.Add("|---|---|---|")
  foreach ($err in $result.errors) {
    $lines.Add("| $(MdCell $err.stage) | $(MdCell $err.code) | $(MdCell $err.message) |")
  }
}
$lines.Add("")
$lines.Add("## Notes")
$lines.Add("")
$lines.Add("- This test writes one row in a transaction and then rolls it back.")
$lines.Add("- A successful test should show Read after insert: True and Record exists after rollback: False.")
$lines.Add("- Full exception details, if any, are in the JSON report.")

$lines | Set-Content -LiteralPath $mdPath -Encoding UTF8

Write-Host ""
Write-Host "UCKK Moodle DB write test complete."
Write-Host "OK: $($result.ok)"
if ($result.PSObject.Properties.Name -contains "inserted_id") {
  Write-Host "Inserted id: $($result.inserted_id)"
  Write-Host "Read after insert: $($result.read_after_insert)"
  Write-Host "Record exists after rollback: $($result.record_exists_after_rollback)"
}
Write-Host "JSON: $jsonPath"
Write-Host "Markdown: $mdPath"
Write-Host ""
if (-not $result.ok) {
  Write-Host "The write test failed. Open the JSON report for exact DB exception details."
}
