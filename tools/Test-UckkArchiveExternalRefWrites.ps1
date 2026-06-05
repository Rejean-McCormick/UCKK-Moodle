#Requires -Version 7.0
<#
.SYNOPSIS
  Probe UCKK archive media import table writes using Moodle DB API.

.DESCRIPTION
  Inserts a minimal external-reference chain in a Moodle delegated transaction:
    uckkarchive_external_work
    uckkarchive_media
    uckkarchive_media_source
    uckkarchive_media_tag
    uckkarchive_media_collection
    uckkarchive_media_collection_item

  It then rolls back intentionally. No data should remain if the transaction works.

  This is designed to isolate which table/field/value blocks the /play importer.

.EXAMPLE
  pwsh -NoProfile -ExecutionPolicy Bypass -File .\Test-UckkArchiveExternalRefWrites.ps1 `
    -MoodleRoot "C:\mycode\UCKK\moodle\moodle\public" `
    -OutputDir "C:\mycode\UCKK\uckk-play-import-check"
#>

[CmdletBinding()]
param(
  [Parameter(Mandatory = $true)]
  [ValidateNotNullOrEmpty()]
  [string]$MoodleRoot,

  [Parameter(Mandatory = $false)]
  [ValidateNotNullOrEmpty()]
  [string]$OutputDir = ".\uckk-play-import-check",

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
$jsonPath = Join-Path $outputDirItem.FullName "uckk_archive_external_ref_write_probe_report.json"
$mdPath = Join-Path $outputDirItem.FullName "uckk_archive_external_ref_write_probe_report.md"
$helperPath = Join-Path $outputDirItem.FullName "test_uckk_archive_external_ref_writes.php"

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

function add_error(&$result, $stage, $code, $message, $context = []) {
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
        'trace_head' => array_slice(explode("\n", $e->getTraceAsString()), 0, 18),
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

function make_uuid() {
    $data = random_bytes(16);
    $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
    $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

function table_columns($DB, $table) {
    return $DB->get_columns($table);
}

function col_exists($columns, $name) {
    return array_key_exists($name, $columns);
}

function set_col(&$record, $columns, $name, $value) {
    if (col_exists($columns, $name)) {
        $record->$name = $value;
    }
}

function filter_record($record, $columns) {
    $out = new stdClass();
    foreach ($record as $k => $v) {
        if (col_exists($columns, $k)) {
            $out->$k = $v;
        }
    }
    return $out;
}

function insert_checked(&$result, $DB, $table, $record, $label) {
    $columns = table_columns($DB, $table);
    $record = filter_record($record, $columns);

    $step = [
        'label' => $label,
        'table' => $table,
        'physical_table' => null,
        'record' => $record,
        'id' => null,
        'read_after_insert' => false,
        'ok' => false,
        'error' => null,
    ];

    try {
        $id = $DB->insert_record($table, $record);
        $step['id'] = $id;
        $step['read_after_insert'] = $DB->record_exists($table, ['id' => $id]);
        $step['ok'] = true;
        $result['steps'][] = $step;
        return $id;
    } catch (Throwable $e) {
        $step['error'] = throwable_details($e);
        $result['steps'][] = $step;
        add_error($result, $label, 'INSERT_FAILED', $e->getMessage(), [
            'table' => $table,
            'record' => $record,
            'exception' => throwable_details($e),
        ]);
        throw $e;
    }
}

$result = [
    'generated_at' => date('c'),
    'ok' => true,
    'mode' => 'external_ref_chain_write_then_rollback',
    'moodle' => [],
    'target' => [],
    'steps' => [],
    'rollback_attempted' => false,
    'rollback_exception_expected' => false,
    'post_rollback_checks' => [],
    'errors' => [],
];

$moodleroot = $argv[1] ?? '';
$archiveid = (int)($argv[2] ?? 115);
$courseid = (int)($argv[3] ?? 116);
$cmid = (int)($argv[4] ?? 339);
$contextid = (int)($argv[5] ?? 491);
$userid = (int)($argv[6] ?? 2);
$now = time();

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
        add_error($result, 'config', 'CONFIG_NOT_FOUND', 'config.php not found', ['path' => $config]);
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

    $tables = [
        'uckkarchive_external_work',
        'uckkarchive_media',
        'uckkarchive_media_source',
        'uckkarchive_media_tag',
        'uckkarchive_media_collection',
        'uckkarchive_media_collection_item',
    ];

    $dbman = $DB->get_manager();
    foreach ($tables as $table) {
        if (!$dbman->table_exists(new xmldb_table($table))) {
            add_error($result, 'schema', 'TABLE_NOT_FOUND', 'Required table missing for probe', ['table' => $table]);
        }
    }
    if (!$result['ok']) {
        echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit(0);
    }

    $transaction = null;
    $created = [];

    try {
        $transaction = $DB->start_delegated_transaction();

        $ewcols = table_columns($DB, 'uckkarchive_external_work');
        $ew = new stdClass();
        set_col($ew, $ewcols, 'uuid', make_uuid());
        set_col($ew, $ewcols, 'archiveid', $archiveid);
        set_col($ew, $ewcols, 'courseid', $courseid);
        set_col($ew, $ewcols, 'cmid', $cmid);
        set_col($ew, $ewcols, 'contextid', $contextid);
        set_col($ew, $ewcols, 'ownerid', $userid);
        set_col($ew, $ewcols, 'createdby', $userid);
        set_col($ew, $ewcols, 'modifiedby', $userid);
        set_col($ew, $ewcols, 'worktype', 'external_video');
        set_col($ew, $ewcols, 'status', 'active');
        set_col($ew, $ewcols, 'visibility', 'public');
        set_col($ew, $ewcols, 'audiencesuitability', 'general');
        set_col($ew, $ewcols, 'rightsstatus', 'external_reference');
        set_col($ew, $ewcols, 'title', 'Probe - Livre Plateforme Connaissances');
        set_col($ew, $ewcols, 'creator', '');
        set_col($ew, $ewcols, 'publisher', 'youtube');
        set_col($ew, $ewcols, 'publicationyear', 2026);
        set_col($ew, $ewcols, 'language', 'fr');
        set_col($ew, $ewcols, 'sourceurl', 'https://www.youtube.com/watch?v=BgtqbQ65wic');
        set_col($ew, $ewcols, 'identifier', 'youtube_video-livre-plateforme-connaissances-8ec22ddf');
        set_col($ew, $ewcols, 'identifiertype', 'play_legacy_id');
        set_col($ew, $ewcols, 'citation', 'https://www.youtube.com/watch?v=BgtqbQ65wic');
        set_col($ew, $ewcols, 'rightsstatement', 'External reference only; media not copied.');
        set_col($ew, $ewcols, 'licensekey', 'unknown');
        set_col($ew, $ewcols, 'sourcenote', 'Imported from /play inventory as an external reference.');
        set_col($ew, $ewcols, 'description', 'Minimal probe item with UTF-8 accents: béton, organisée, Éternel.');
        set_col($ew, $ewcols, 'metadata', json_encode(['probe' => true, 'source' => 'Test-UckkArchiveExternalRefWrites.ps1'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        set_col($ew, $ewcols, 'timecreated', $now);
        set_col($ew, $ewcols, 'timemodified', $now);
        $externalworkid = insert_checked($result, $DB, 'uckkarchive_external_work', $ew, 'external_work');
        $created['external_work'] = ['table' => 'uckkarchive_external_work', 'id' => $externalworkid];

        $mcols = table_columns($DB, 'uckkarchive_media');
        $media = new stdClass();
        set_col($media, $mcols, 'uuid', make_uuid());
        set_col($media, $mcols, 'archiveid', $archiveid);
        set_col($media, $mcols, 'courseid', $courseid);
        set_col($media, $mcols, 'cmid', $cmid);
        set_col($media, $mcols, 'contextid', $contextid);
        set_col($media, $mcols, 'userid', $userid);
        set_col($media, $mcols, 'ownerid', $userid);
        set_col($media, $mcols, 'createdby', $userid);
        set_col($media, $mcols, 'modifiedby', $userid);
        set_col($media, $mcols, 'externalworkid', $externalworkid);
        set_col($media, $mcols, 'mediatype', 'video');
        set_col($media, $mcols, 'status', 'active');
        set_col($media, $mcols, 'visibility', 'public');
        set_col($media, $mcols, 'audiencesuitability', 'general');
        set_col($media, $mcols, 'source', 'youtube');
        set_col($media, $mcols, 'title', 'Probe - Livre Plateforme Connaissances');
        set_col($media, $mcols, 'summary', 'Temporary probe media.');
        set_col($media, $mcols, 'description', 'Temporary probe media with UTF-8 accents: béton, organisée, Éternel.');
        set_col($media, $mcols, 'metadata', json_encode(['probe' => true, 'legacy_id' => 'youtube_video-livre-plateforme-connaissances-8ec22ddf'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        set_col($media, $mcols, 'restricted', 0);
        set_col($media, $mcols, 'timecreated', $now);
        set_col($media, $mcols, 'timemodified', $now);
        set_col($media, $mcols, 'sourcetype', 'external_reference_only');
        set_col($media, $mcols, 'sourceurl', 'https://www.youtube.com/watch?v=BgtqbQ65wic');
        set_col($media, $mcols, 'license', 'unknown');
        set_col($media, $mcols, 'licensekey', 'unknown');
        set_col($media, $mcols, 'rightsstatement', 'External reference only; media not copied.');
        set_col($media, $mcols, 'rightsstatus', 'external_reference');
        $mediaid = insert_checked($result, $DB, 'uckkarchive_media', $media, 'media');
        $created['media'] = ['table' => 'uckkarchive_media', 'id' => $mediaid];

        $scols = table_columns($DB, 'uckkarchive_media_source');
        $source = new stdClass();
        set_col($source, $scols, 'uuid', make_uuid());
        set_col($source, $scols, 'archiveid', $archiveid);
        set_col($source, $scols, 'mediaid', $mediaid);
        set_col($source, $scols, 'externalworkid', $externalworkid);
        set_col($source, $scols, 'sourcecomponent', 'play_inventory');
        set_col($source, $scols, 'sourceitemid', 'youtube_video-livre-plateforme-connaissances-8ec22ddf');
        set_col($source, $scols, 'sourcetype', 'external_reference_only');
        set_col($source, $scols, 'sourceurl', 'https://www.youtube.com/watch?v=BgtqbQ65wic');
        set_col($source, $scols, 'title', 'Probe - Livre Plateforme Connaissances');
        set_col($source, $scols, 'citation', 'https://www.youtube.com/watch?v=BgtqbQ65wic');
        set_col($source, $scols, 'rightsstatus', 'external_reference');
        set_col($source, $scols, 'metadata', json_encode(['probe' => true, 'provider' => 'youtube', 'id' => 'BgtqbQ65wic'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        set_col($source, $scols, 'createdby', $userid);
        set_col($source, $scols, 'modifiedby', $userid);
        set_col($source, $scols, 'timecreated', $now);
        set_col($source, $scols, 'timemodified', $now);
        $sourceid = insert_checked($result, $DB, 'uckkarchive_media_source', $source, 'media_source');
        $created['media_source'] = ['table' => 'uckkarchive_media_source', 'id' => $sourceid];

        // If media.sourceid exists, test update too.
        if (col_exists($mcols, 'sourceid')) {
            $upd = new stdClass();
            $upd->id = $mediaid;
            $upd->sourceid = $sourceid;
            try {
                $DB->update_record('uckkarchive_media', $upd);
                $result['steps'][] = [
                    'label' => 'media_update_sourceid',
                    'table' => 'uckkarchive_media',
                    'record' => $upd,
                    'id' => $mediaid,
                    'read_after_insert' => true,
                    'ok' => true,
                    'error' => null,
                ];
            } catch (Throwable $e) {
                add_error($result, 'media_update_sourceid', 'UPDATE_FAILED', $e->getMessage(), ['exception' => throwable_details($e), 'record' => $upd]);
                throw $e;
            }
        }

        $tagcols = table_columns($DB, 'uckkarchive_media_tag');
        $tag = new stdClass();
        set_col($tag, $tagcols, 'uuid', make_uuid());
        set_col($tag, $tagcols, 'archiveid', $archiveid);
        set_col($tag, $tagcols, 'mediaid', $mediaid);
        set_col($tag, $tagcols, 'tag', 'youtube');
        set_col($tag, $tagcols, 'tagkey', 'youtube');
        set_col($tag, $tagcols, 'name', 'YouTube');
        set_col($tag, $tagcols, 'rawname', 'youtube');
        set_col($tag, $tagcols, 'tagtype', 'platform');
        set_col($tag, $tagcols, 'type', 'platform');
        set_col($tag, $tagcols, 'createdby', $userid);
        set_col($tag, $tagcols, 'timecreated', $now);
        set_col($tag, $tagcols, 'timemodified', $now);
        $tagid = insert_checked($result, $DB, 'uckkarchive_media_tag', $tag, 'media_tag');
        $created['media_tag'] = ['table' => 'uckkarchive_media_tag', 'id' => $tagid];

        $ccols = table_columns($DB, 'uckkarchive_media_collection');
        $collection = new stdClass();
        set_col($collection, $ccols, 'uuid', make_uuid());
        set_col($collection, $ccols, 'archiveid', $archiveid);
        set_col($collection, $ccols, 'courseid', $courseid);
        set_col($collection, $ccols, 'cmid', $cmid);
        set_col($collection, $ccols, 'contextid', $contextid);
        set_col($collection, $ccols, 'ownerid', $userid);
        set_col($collection, $ccols, 'createdby', $userid);
        set_col($collection, $ccols, 'modifiedby', $userid);
        set_col($collection, $ccols, 'title', 'Probe Collection');
        set_col($collection, $ccols, 'summary', 'Temporary probe collection.');
        set_col($collection, $ccols, 'description', 'Temporary probe collection.');
        set_col($collection, $ccols, 'status', 'active');
        set_col($collection, $ccols, 'visibility', 'private');
        set_col($collection, $ccols, 'sortorder', 999999);
        set_col($collection, $ccols, 'metadata', json_encode(['probe' => true, 'collection_kind' => 'probe'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        set_col($collection, $ccols, 'timecreated', $now);
        set_col($collection, $ccols, 'timemodified', $now);
        $collectionid = insert_checked($result, $DB, 'uckkarchive_media_collection', $collection, 'media_collection');
        $created['media_collection'] = ['table' => 'uckkarchive_media_collection', 'id' => $collectionid];

        $cicols = table_columns($DB, 'uckkarchive_media_collection_item');
        $ci = new stdClass();
        set_col($ci, $cicols, 'collectionid', $collectionid);
        set_col($ci, $cicols, 'mediaid', $mediaid);
        set_col($ci, $cicols, 'sortorder', 1);
        set_col($ci, $cicols, 'addedby', $userid);
        set_col($ci, $cicols, 'timecreated', $now);
        $ciid = insert_checked($result, $DB, 'uckkarchive_media_collection_item', $ci, 'media_collection_item');
        $created['media_collection_item'] = ['table' => 'uckkarchive_media_collection_item', 'id' => $ciid];

        $result['rollback_attempted'] = true;
        try {
            $transaction->rollback(new Exception('Intentional rollback after successful external-ref write probe.'));
        } catch (Throwable $rollbackException) {
            $result['rollback_exception_expected'] = true;
            $result['rollback_exception'] = throwable_details($rollbackException);
        }

        foreach ($created as $label => $entry) {
            $exists = $DB->record_exists($entry['table'], ['id' => $entry['id']]);
            $result['post_rollback_checks'][] = [
                'label' => $label,
                'table' => $entry['table'],
                'id' => $entry['id'],
                'exists_after_rollback' => $exists,
            ];
            if ($exists) {
                add_error($result, 'rollback', 'ROLLBACK_DID_NOT_REMOVE_ROW', 'A probe row still exists after rollback.', $entry);
            }
        }
    } catch (Throwable $e) {
        if ($transaction) {
            try {
                $transaction->rollback($e);
            } catch (Throwable $rollbackAfterError) {
                $result['rollback_after_error'] = throwable_details($rollbackAfterError);
            }
        }

        if ($result['ok']) {
            add_error($result, 'fatal', 'PROBE_FAILED', $e->getMessage(), ['exception' => throwable_details($e)]);
        }
    }
} catch (Throwable $e) {
    add_error($result, 'fatal', 'PROBE_FATAL', $e->getMessage(), ['exception' => throwable_details($e)]);
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

$result | ConvertTo-Json -Depth 80 | Set-Content -LiteralPath $jsonPath -Encoding UTF8

$lines = [System.Collections.Generic.List[string]]::new()
$lines.Add("# UCKK archive external-reference write probe")
$lines.Add("")
$lines.Add("- Generated at: " + $result.generated_at)
$lines.Add("- OK: " + $result.ok)
$lines.Add("- Mode: " + $result.mode)
$lines.Add("")
$lines.Add("## Steps")
$lines.Add("")
if (($result.PSObject.Properties.Name -contains "steps") -and (($result.steps | Measure-Object).Count -gt 0)) {
  $lines.Add("| Label | Table | ID | Read after insert | OK | Error |")
  $lines.Add("|---|---|---:|---:|---:|---|")
  foreach ($step in $result.steps) {
    $err = ""
    if ($null -ne $step.error) { $err = $step.error.message }
    $lines.Add("| $(MdCell $step.label) | $(MdCell $step.table) | $(MdCell $step.id) | $(MdCell $step.read_after_insert) | $(MdCell $step.ok) | $(MdCell $err) |")
  }
}
else {
  $lines.Add("No steps collected.")
}
$lines.Add("")
$lines.Add("## Errors")
$lines.Add("")
$errorCount = 0
if ($null -ne $result.errors) { $errorCount = ($result.errors | Measure-Object).Count }
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
$lines.Add("## Rollback checks")
$lines.Add("")
if (($result.PSObject.Properties.Name -contains "post_rollback_checks") -and (($result.post_rollback_checks | Measure-Object).Count -gt 0)) {
  $lines.Add("| Label | Table | ID | Exists after rollback |")
  $lines.Add("|---|---|---:|---:|")
  foreach ($row in $result.post_rollback_checks) {
    $lines.Add("| $(MdCell $row.label) | $(MdCell $row.table) | $(MdCell $row.id) | $(MdCell $row.exists_after_rollback) |")
  }
}
else {
  $lines.Add("No rollback checks collected.")
}
$lines.Add("")
$lines.Add("Full exception details are in the JSON report.")

$lines | Set-Content -LiteralPath $mdPath -Encoding UTF8

Write-Host ""
Write-Host "UCKK archive external-reference write probe complete."
Write-Host "OK: $($result.ok)"
Write-Host "JSON: $jsonPath"
Write-Host "Markdown: $mdPath"
Write-Host ""

if ($result.PSObject.Properties.Name -contains "steps") {
  foreach ($step in $result.steps) {
    $status = if ($step.ok) { "OK" } else { "FAILED" }
    Write-Host "$($step.label): $status"
  }
}

if (-not $result.ok) {
  Write-Host ""
  Write-Host "Probe failed. Open the JSON report for the exact failing table and DB exception details."
}
