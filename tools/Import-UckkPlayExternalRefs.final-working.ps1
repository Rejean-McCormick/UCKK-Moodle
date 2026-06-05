#Requires -Version 7.0
<#
.SYNOPSIS
  Import /play external media references into UCKK Moodle mod_uckkarchive.

.DESCRIPTION
  Strict importer for external references only: YouTube, Spotify, SoundCloud,
  Medium, GitHub, PhilPapers, Amazon print links, websites, etc.

  The script generates and runs a temporary PHP helper that:
    - defines CLI_SCRIPT;
    - loads Moodle config.php;
    - validates the real mod_uckkarchive schema;
    - reads inventory.*.catalog.json files;
    - imports or updates external_work, media, media_source, tags, and collections;
    - does not download third-party media;
    - stores original /play item metadata for provenance/idempotence.

  By default this runs in dry-run mode. It writes to the DB only when -Apply is used.

.PARAMETER MoodleRoot
  Moodle public/root directory containing config.php.

.PARAMETER SourceDir
  Directory containing:
    inventory.root.json
    inventory.youtube.catalog.json
    inventory.articles.catalog.json
    inventory.audio.catalog.json
    inventory.code-tech.catalog.json

.PARAMETER OutputDir
  Directory where helper and import reports are written.

.PARAMETER ArchiveId
  Target uckkarchive id. Default: 115.

.PARAMETER CourseId
  Target Moodle course id. Default: 116.

.PARAMETER UserId
  Optional Moodle user id to use as createdby/modifiedby/ownerid.
  If omitted, the PHP helper uses get_admin()->id.

.PARAMETER Apply
  Actually write to the Moodle DB. Without this switch, the importer performs a dry-run.

.EXAMPLE
  pwsh -NoProfile -ExecutionPolicy Bypass -File .\Import-UckkPlayExternalRefs.ps1 `
    -MoodleRoot "C:\mycode\UCKK\moodle\moodle\public" `
    -SourceDir "C:\mycode\HomePage\HomePage\public" `
    -OutputDir "C:\mycode\UCKK\uckk-play-import-check"

.EXAMPLE
  pwsh -NoProfile -ExecutionPolicy Bypass -File .\Import-UckkPlayExternalRefs.ps1 `
    -MoodleRoot "C:\mycode\UCKK\moodle\moodle\public" `
    -SourceDir "C:\mycode\HomePage\HomePage\public" `
    -OutputDir "C:\mycode\UCKK\uckk-play-import-check" `
    -Apply
#>

[CmdletBinding()]
param(
  [Parameter(Mandatory = $true)]
  [ValidateNotNullOrEmpty()]
  [string]$MoodleRoot,

  [Parameter(Mandatory = $true)]
  [ValidateNotNullOrEmpty()]
  [string]$SourceDir,

  [Parameter(Mandatory = $false)]
  [ValidateNotNullOrEmpty()]
  [string]$OutputDir = ".\uckk-play-import-report",

  [Parameter(Mandatory = $false)]
  [int]$ArchiveId = 115,

  [Parameter(Mandatory = $false)]
  [int]$CourseId = 116,

  [Parameter(Mandatory = $false)]
  [int]$UserId = 0,

  [Parameter(Mandatory = $false)]
  [ValidateNotNullOrEmpty()]
  [string]$PhpPath = "php",

  [Parameter(Mandatory = $false)]
  [switch]$Apply,

  [Parameter(Mandatory = $false)]
  [switch]$OpenReport
)

Set-StrictMode -Version Latest
$ErrorActionPreference = "Stop"

# Force UTF-8 for native process stdout/stderr decoding on Windows.
# Without this, PHP JSON output can be decoded by PowerShell as the OEM codepage,
# producing mojibake such as "bâ”œÂ®ton" or "Ã”Ã‡Ã–".
$script:Utf8NoBom = [System.Text.UTF8Encoding]::new($false)
[Console]::InputEncoding = $script:Utf8NoBom
[Console]::OutputEncoding = $script:Utf8NoBom
$OutputEncoding = $script:Utf8NoBom

function Stop-WithMessage {
  param([string]$Message)
  throw "[UCKK /play import] $Message"
}

function Test-CommandExists {
  param([Parameter(Mandatory = $true)][string]$Command)
  return ($null -ne (Get-Command $Command -ErrorAction SilentlyContinue))
}

$resolvedMoodleRoot = (Resolve-Path -LiteralPath $MoodleRoot).Path
$resolvedSourceDir = (Resolve-Path -LiteralPath $SourceDir).Path
$outputDirItem = New-Item -ItemType Directory -Force -Path $OutputDir

$configPath = Join-Path $resolvedMoodleRoot "config.php"
if (-not (Test-Path -LiteralPath $configPath)) {
  Stop-WithMessage "config.php not found at: $configPath"
}

$requiredCatalogs = @(
  "inventory.root.json",
  "inventory.youtube.catalog.json",
  "inventory.articles.catalog.json",
  "inventory.audio.catalog.json",
  "inventory.code-tech.catalog.json"
)

$missingCatalogs = @()
foreach ($catalog in $requiredCatalogs) {
  $path = Join-Path $resolvedSourceDir $catalog
  if (-not (Test-Path -LiteralPath $path)) {
    $missingCatalogs += $path
  }
}

if ($missingCatalogs.Count -gt 0) {
  Stop-WithMessage "Missing required catalog file(s): $($missingCatalogs -join '; ')"
}

if (-not (Test-CommandExists -Command $PhpPath)) {
  Stop-WithMessage "PHP CLI was not found at '$PhpPath'. Install PHP CLI or pass -PhpPath."
}

$mode = if ($Apply) { "apply" } else { "dry-run" }
$helperPath = Join-Path $outputDirItem.FullName "import_uckk_play_external_refs.php"
$jsonReportPath = Join-Path $outputDirItem.FullName "uckk_play_external_refs_import_report.json"
$mdReportPath = Join-Path $outputDirItem.FullName "uckk_play_external_refs_import_report.md"

$phpCode = @'
<?php
define('CLI_SCRIPT', true);

$result = [
    'generated_at' => date('c'),
    'ok' => false,
    'mode' => null,
    'apply' => false,
    'moodle' => [],
    'input' => [],
    'target' => [],
    'schema' => [
        'ok' => false,
        'issues' => [],
        'required_columns' => [],
    ],
    'catalogs' => [],
    'summary' => [
        'loaded_items' => 0,
        'unique_items' => 0,
        'duplicates_skipped' => 0,
        'would_create_external_work' => 0,
        'would_update_external_work' => 0,
        'would_create_media' => 0,
        'would_update_media' => 0,
        'would_create_media_source' => 0,
        'would_update_media_source' => 0,
        'would_create_tag' => 0,
        'would_create_collection' => 0,
        'would_create_collection_item' => 0,
        'created_external_work' => 0,
        'updated_external_work' => 0,
        'created_media' => 0,
        'updated_media' => 0,
        'created_media_source' => 0,
        'updated_media_source' => 0,
        'created_tag' => 0,
        'created_collection' => 0,
        'created_collection_item' => 0,
        'errors' => 0,
        'skipped' => 0,
    ],
    'items' => [],
    'errors' => [],
    'warnings' => [],
];

function add_error(&$result, $stage, $code, $message, $context = []) {
    $result['errors'][] = [
        'stage' => $stage,
        'code' => $code,
        'message' => $message,
        'context' => $context,
    ];
    if (isset($result['summary']['errors'])) {
        $result['summary']['errors']++;
    }
}

function add_warning(&$result, $stage, $code, $message, $context = []) {
    $result['warnings'][] = [
        'stage' => $stage,
        'code' => $code,
        'message' => $message,
        'context' => $context,
    ];
}

function require_arg($argv, $index, $name) {
    if (!isset($argv[$index]) || $argv[$index] === '') {
        throw new RuntimeException("Missing required argument: {$name}");
    }
    return $argv[$index];
}

function uuidv4() {
    $data = random_bytes(16);
    $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
    $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

function now_ts() {
    return time();
}

function as_array($value) {
    return is_array($value) ? $value : [];
}

function obj_get($array, $key, $default = null) {
    if (is_array($array) && array_key_exists($key, $array)) {
        return $array[$key];
    }
    return $default;
}

function nested_get($array, $path, $default = null) {
    $cursor = $array;
    foreach ($path as $key) {
        if (!is_array($cursor) || !array_key_exists($key, $cursor)) {
            return $default;
        }
        $cursor = $cursor[$key];
    }
    return $cursor;
}

function safe_string($value, $max = null) {
    if ($value === null) {
        return null;
    }
    if (is_bool($value)) {
        $value = $value ? '1' : '0';
    }
    if (is_array($value) || is_object($value)) {
        $value = json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
    $value = trim((string)$value);
    if ($max !== null && function_exists('mb_substr')) {
        return mb_substr($value, 0, $max, 'UTF-8');
    }
    if ($max !== null) {
        return substr($value, 0, $max);
    }
    return $value;
}

function summary_text($description) {
    $text = safe_string($description, null);
    if ($text === null || $text === '') {
        return null;
    }
    $text = preg_replace('/\s+/u', ' ', $text);
    return safe_string($text, 700);
}

function json_text($value) {
    return json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}

function slug_key($value) {
    $value = safe_string($value, null);
    if ($value === null || $value === '') {
        return 'unknown';
    }
    $value = strtolower($value);
    $value = preg_replace('/[^\p{L}\p{N}]+/u', '_', $value);
    $value = trim($value, '_');
    return $value !== '' ? $value : 'unknown';
}

function item_identity($item) {
    $legacyid = safe_string(obj_get($item, 'id'));
    $url = safe_string(obj_get($item, 'url'));
    if ($legacyid !== null && $legacyid !== '') {
        return 'id:' . $legacyid;
    }
    if ($url !== null && $url !== '') {
        return 'url:' . strtolower($url);
    }
    return null;
}

function parse_year($item) {
    $published = obj_get($item, 'publishedAt');
    if ($published === null) {
        $published = nested_get($item, ['youtube', 'publishedAt']);
    }
    if ($published === null) {
        return null;
    }
    if (preg_match('/(19|20)\d{2}/', (string)$published, $m)) {
        return (int)$m[0];
    }
    return null;
}

function map_worktype($item) {
    $type = safe_string(obj_get($item, 'type'));
    $platform = safe_string(obj_get($item, 'platform'));

    if ($type === 'youtube_video' || $platform === 'youtube') {
        return 'external_video';
    }
    if ($platform === 'spotify' || $platform === 'spotify_podcast' || $type === 'spotify_podcast') {
        return 'podcast';
    }
    if ($platform === 'soundcloud' || $type === 'audio') {
        return 'audio';
    }
    if ($type === 'medium_article' || $platform === 'medium' || $platform === 'philpapers') {
        return 'article';
    }
    if ($type === 'github_wiki' || $platform === 'github') {
        return 'website';
    }
    if ($type === 'wiki_article' || $platform === 'wiki') {
        return 'website';
    }
    if ($type === 'pdf_document') {
        return 'document';
    }
    if ($type === 'print_book' || $platform === 'amazon_print') {
        return 'book';
    }
    return 'external_reference';
}

function map_mediatype($item) {
    $type = safe_string(obj_get($item, 'type'));
    $platform = safe_string(obj_get($item, 'platform'));

    if ($type === 'youtube_video' || $platform === 'youtube') {
        return 'video';
    }
    if ($platform === 'spotify' || $platform === 'spotify_podcast' || $platform === 'soundcloud' || $type === 'spotify_podcast' || $type === 'audio') {
        return 'audio';
    }
    if ($type === 'github_wiki' || $platform === 'github') {
        return 'code';
    }
    if ($type === 'pdf_document') {
        return 'document';
    }
    if ($type === 'print_book') {
        return 'book';
    }
    return 'text';
}

function map_identifier($item) {
    $embedid = nested_get($item, ['embed', 'id']);
    $embedprovider = nested_get($item, ['embed', 'provider']);
    $platform = safe_string(obj_get($item, 'platform'));
    $legacyid = safe_string(obj_get($item, 'id'));
    $url = safe_string(obj_get($item, 'url'));

    if ($embedid !== null && $embedid !== '') {
        $kind = $embedprovider ?: $platform ?: 'embed';
        return [safe_string($embedid, 255), safe_string($kind . '_id', 100)];
    }

    if ($legacyid !== null && $legacyid !== '') {
        return [safe_string($legacyid, 255), 'play_legacy_id'];
    }

    if ($url !== null && $url !== '') {
        return [safe_string($url, 255), 'url'];
    }

    return [null, null];
}

function rights_statement() {
    return 'External reference only; third-party media is not copied into Moodle.';
}

function catalog_files() {
    return [
        'inventory.youtube.catalog.json' => 'youtube',
        'inventory.articles.catalog.json' => 'articles',
        'inventory.audio.catalog.json' => 'audio',
        'inventory.code-tech.catalog.json' => 'code-tech',
    ];
}

function load_json_file($path) {
    if (!is_file($path)) {
        throw new RuntimeException("Missing JSON file: {$path}");
    }
    $raw = file_get_contents($path);
    $json = json_decode($raw, true);
    if (!is_array($json)) {
        throw new RuntimeException("Invalid JSON file: {$path}");
    }
    return $json;
}

function looks_mojibake($value) {
    if (!is_string($value) || $value === '') {
        return false;
    }
    return preg_match('/(â”œ|â”¬|â”¼|Ã”Ã‡|Ã¢â‚¬â„¢|Ã¢â‚¬Å“|Ã¢â‚¬Â|Ã¢â‚¬â€œ|Ã¢â‚¬â€|ÃƒÂ©|ÃƒÂ¨|ÃƒÂª|ÃƒÂ«|Ãƒ |ÃƒÂ¢|ÃƒÂ´|ÃƒÂ»|ÃƒÂ§)/u', $value) === 1;
}

function collect_encoding_issues($rows, $limit = 25) {
    $issues = [];
    foreach ($rows as $identity => $row) {
        foreach (['title', 'description', 'summary', 'citation'] as $field) {
            if (array_key_exists($field, $row) && looks_mojibake($row[$field])) {
                $issues[] = [
                    'identity' => $identity,
                    'legacy_id' => $row['legacy_id'] ?? null,
                    'field' => $field,
                    'value' => mb_substr((string)$row[$field], 0, 240),
                ];
                if (count($issues) >= $limit) {
                    return $issues;
                }
            }
        }
    }
    return $issues;
}

function topic_label($root, $topic) {
    $labels = nested_get($root, ['taxonomies', 'topic_labels', $topic], null);
    if (is_array($labels)) {
        return obj_get($labels, 'fr') ?: obj_get($labels, 'en') ?: $topic;
    }
    return $topic;
}

function album_label($youtubeCatalog, $album) {
    $labels = nested_get($youtubeCatalog, ['taxonomies', 'album_labels', $album], null);
    if (is_array($labels)) {
        return obj_get($labels, 'fr') ?: obj_get($labels, 'en') ?: $album;
    }
    return $album;
}

function validate_columns($DB, &$result) {
    $required = [
        'uckkarchive' => ['id', 'course', 'name'],
        'uckkarchive_external_work' => [
            'id', 'uuid', 'archiveid', 'courseid', 'cmid', 'contextid', 'ownerid', 'createdby', 'modifiedby',
            'worktype', 'status', 'visibility', 'audiencesuitability', 'rightsstatus', 'title', 'creator',
            'publisher', 'publicationyear', 'language', 'sourceurl', 'identifier', 'identifiertype',
            'citation', 'rightsstatement', 'licensekey', 'sourcenote', 'description', 'metadata',
            'timecreated', 'timemodified'
        ],
        'uckkarchive_media' => [
            'id', 'uuid', 'archiveid', 'courseid', 'cmid', 'contextid', 'userid', 'ownerid', 'createdby', 'modifiedby',
            'externalworkid', 'sourceid', 'mediatype', 'status', 'visibility', 'audiencesuitability', 'source',
            'title', 'summary', 'description', 'metadata', 'restricted', 'timecreated', 'timemodified',
            'sourcetype', 'sourceurl', 'license', 'licensekey', 'rightsstatement', 'rightsstatus'
        ],
        'uckkarchive_media_source' => [
            'id', 'uuid', 'archiveid', 'mediaid', 'externalworkid', 'sourcecomponent', 'sourceitemid',
            'sourcetype', 'sourceurl', 'title', 'citation', 'rightsstatus', 'metadata',
            'createdby', 'modifiedby', 'timecreated', 'timemodified'
        ],
        'uckkarchive_media_tag' => [
            'id', 'uuid', 'archiveid', 'mediaid', 'tag', 'tagkey', 'name', 'rawname', 'tagtype', 'type',
            'createdby', 'timecreated', 'timemodified'
        ],
        'uckkarchive_media_collection' => [
            'id', 'uuid', 'archiveid', 'courseid', 'cmid', 'contextid', 'ownerid', 'createdby', 'modifiedby',
            'title', 'summary', 'description', 'status', 'visibility', 'sortorder', 'metadata',
            'timecreated', 'timemodified'
        ],
        'uckkarchive_media_collection_item' => [
            'id', 'collectionid', 'mediaid', 'sortorder', 'addedby', 'timecreated'
        ],
    ];

    $ok = true;
    foreach ($required as $table => $columns) {
        try {
            if (!$DB->get_manager()->table_exists(new xmldb_table($table))) {
                $result['schema']['issues'][] = [
                    'table' => $table,
                    'missing_table' => true,
                    'missing_columns' => $columns,
                ];
                $ok = false;
                continue;
            }
            $actual = array_keys($DB->get_columns($table));
            $missing = [];
            foreach ($columns as $column) {
                if (!in_array($column, $actual, true)) {
                    $missing[] = $column;
                }
            }
            $result['schema']['required_columns'][$table] = $columns;
            if (count($missing) > 0) {
                $result['schema']['issues'][] = [
                    'table' => $table,
                    'missing_table' => false,
                    'missing_columns' => $missing,
                    'actual_columns' => $actual,
                ];
                $ok = false;
            }
        } catch (Throwable $e) {
            $result['schema']['issues'][] = [
                'table' => $table,
                'error' => $e->getMessage(),
            ];
            $ok = false;
        }
    }

    $result['schema']['ok'] = $ok;
    return $ok;
}

function get_context_info($DB, $archiveid, $courseid) {
    $cmid = 0;
    $contextid = 0;

    try {
        $moduleid = $DB->get_field('modules', 'id', ['name' => 'uckkarchive'], IGNORE_MISSING);
        if ($moduleid) {
            $cm = $DB->get_record('course_modules', [
                'module' => $moduleid,
                'instance' => $archiveid,
                'course' => $courseid,
            ], '*', IGNORE_MISSING);
            if ($cm) {
                $cmid = (int)$cm->id;
                if (class_exists('context_module')) {
                    $context = context_module::instance($cmid, IGNORE_MISSING);
                    if ($context) {
                        $contextid = (int)$context->id;
                    }
                }
            }
        }
    } catch (Throwable $e) {
        // Keep zero fallback. The insert will fail if the schema forbids it.
    }

    return [$cmid, $contextid];
}

function get_admin_userid() {
    try {
        $admin = get_admin();
        if ($admin && !empty($admin->id)) {
            return (int)$admin->id;
        }
    } catch (Throwable $e) {
        return 2;
    }
    return 2;
}

function row_from_item($item, $sourcecatalog, $root, $youtubeCatalog, $target) {
    [$identifier, $identifiertype] = map_identifier($item);

    $url = safe_string(obj_get($item, 'url'), 1333);
    $title = safe_string(obj_get($item, 'title'), 255);
    $description = safe_string(obj_get($item, 'description'));
    $platform = safe_string(obj_get($item, 'platform'), 100);
    $type = safe_string(obj_get($item, 'type'), 100);
    $language = safe_string(obj_get($item, 'language'), 20);

    $metadata = [
        'migration' => [
            'source' => 'play_inventory',
            'source_catalog' => $sourcecatalog,
            'legacy_play_id' => obj_get($item, 'id'),
            'imported_at' => date('c'),
            'target_archiveid' => $target['archiveid'],
            'target_courseid' => $target['courseid'],
        ],
        'play_item' => $item,
    ];

    return [
        'legacy_id' => safe_string(obj_get($item, 'id'), 255),
        'url' => $url,
        'title' => $title,
        'description' => $description,
        'summary' => summary_text($description),
        'platform' => $platform,
        'type' => $type,
        'language' => $language,
        'worktype' => map_worktype($item),
        'mediatype' => map_mediatype($item),
        'publicationyear' => (parse_year($item) ?: 0),
        'identifier' => $identifier,
        'identifiertype' => $identifiertype,
        'citation' => $title && $url ? $title . ' â€” ' . $url : $url,
        'metadata' => $metadata,
        'topics' => as_array(obj_get($item, 'topics', [])),
        'sections' => as_array(obj_get($item, 'sections', [])),
        'primarySection' => obj_get($item, 'primarySection'),
        'featured' => (bool)obj_get($item, 'featured', false),
        'albums' => as_array(obj_get($item, 'albums', [])),
        'albumTrack' => obj_get($item, 'albumTrack'),
        'root' => $root,
        'youtubeCatalog' => $youtubeCatalog,
        'sourcecatalog' => $sourcecatalog,
    ];
}

function find_external_work($DB, $archiveid, $url, $identifier) {
    if ($url !== null && $url !== '') {
        $record = $DB->get_record('uckkarchive_external_work', [
            'archiveid' => $archiveid,
            'sourceurl' => $url,
        ], '*', IGNORE_MISSING);
        if ($record) {
            return $record;
        }
    }

    if ($identifier !== null && $identifier !== '') {
        return $DB->get_record('uckkarchive_external_work', [
            'archiveid' => $archiveid,
            'identifier' => $identifier,
        ], '*', IGNORE_MISSING);
    }

    return false;
}

function find_media($DB, $archiveid, $url, $externalworkid) {
    if ($externalworkid) {
        $record = $DB->get_record('uckkarchive_media', [
            'archiveid' => $archiveid,
            'externalworkid' => $externalworkid,
        ], '*', IGNORE_MISSING);
        if ($record) {
            return $record;
        }
    }

    if ($url !== null && $url !== '') {
        return $DB->get_record('uckkarchive_media', [
            'archiveid' => $archiveid,
            'sourceurl' => $url,
        ], '*', IGNORE_MISSING);
    }

    return false;
}

function find_media_source($DB, $archiveid, $mediaid, $url) {
    if ($mediaid) {
        $record = $DB->get_record('uckkarchive_media_source', [
            'archiveid' => $archiveid,
            'mediaid' => $mediaid,
        ], '*', IGNORE_MISSING);
        if ($record) {
            return $record;
        }
    }

    if ($url !== null && $url !== '') {
        return $DB->get_record('uckkarchive_media_source', [
            'archiveid' => $archiveid,
            'sourceurl' => $url,
        ], '*', IGNORE_MISSING);
    }

    return false;
}

function record_changes($old, $new, $fields) {
    $changes = [];
    if (!$old) {
        return $fields;
    }
    foreach ($fields as $field) {
        $oldvalue = property_exists($old, $field) ? $old->$field : null;
        $newvalue = property_exists($new, $field) ? $new->$field : null;
        if ((string)$oldvalue !== (string)$newvalue) {
            $changes[] = $field;
        }
    }
    return $changes;
}

function table_column_names($DB, $table) {
    static $cache = [];
    if (!array_key_exists($table, $cache)) {
        $cache[$table] = array_keys($DB->get_columns($table));
    }
    return $cache[$table];
}

function prune_record_for_table($DB, $table, $record) {
    $columns = table_column_names($DB, $table);
    $out = new stdClass();
    foreach ($record as $key => $value) {
        if (in_array($key, $columns, true)) {
            $out->$key = $value;
        }
    }
    return $out;
}

function db_insert_record($DB, $table, $record) {
    return $DB->insert_record($table, prune_record_for_table($DB, $table, $record));
}

function db_update_record($DB, $table, $record) {
    return $DB->update_record($table, prune_record_for_table($DB, $table, $record));
}

function throwable_details($e) {
    $details = [
        'exception_class' => get_class($e),
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
    ];

    foreach (['errorcode', 'debuginfo', 'sql', 'params'] as $prop) {
        if (property_exists($e, $prop)) {
            $value = $e->$prop;
            if (is_string($value)) {
                $details[$prop] = mb_substr($value, 0, 4000);
            } else {
                $details[$prop] = $value;
            }
        }
    }

    $details['trace_head'] = mb_substr($e->getTraceAsString(), 0, 4000);
    return $details;
}

function apply_external_work($DB, &$result, $row, $target, $apply) {
    $now = now_ts();
    $existing = find_external_work($DB, $target['archiveid'], $row['url'], $row['identifier']);

    $record = new stdClass();
    $record->uuid = $existing ? $existing->uuid : uuidv4();
    $record->archiveid = $target['archiveid'];
    $record->courseid = $target['courseid'];
    $record->cmid = $target['cmid'];
    $record->contextid = $target['contextid'];
    $record->ownerid = $target['userid'];
    $record->createdby = $existing ? $existing->createdby : $target['userid'];
    $record->modifiedby = $target['userid'];
    $record->worktype = safe_string($row['worktype'], 80);
    $record->status = 'active';
    $record->visibility = 'public';
    $record->audiencesuitability = 'general';
    $record->rightsstatus = 'external_reference';
    $record->title = $row['title'];
    $record->subtitle = '';
    $record->creator = '';
    $record->publisher = $row['platform'];
    $record->publicationyear = ($row['publicationyear'] === null ? 0 : (int)$row['publicationyear']);
    $record->language = ($row['language'] ?: 'und');
    $record->sourceurl = $row['url'];
    $record->identifier = $row['identifier'];
    $record->identifiertype = $row['identifiertype'];
    $record->citation = safe_string($row['citation']);
    $record->rightsstatement = rights_statement();
    $record->licensekey = '';
    $record->sourcenote = 'Imported from /play inventory as an external reference. Media file was not copied.';
    $record->teachingnote = '';
    $record->culturalprotocolnote = '';
    $record->description = $row['description'];
    $record->metadata = json_text($row['metadata']);
    $record->timecreated = $existing ? $existing->timecreated : $now;
    $record->timemodified = $now;

    if ($existing) {
        $record->id = $existing->id;
        $changes = record_changes($existing, $record, [
            'worktype', 'status', 'visibility', 'audiencesuitability', 'rightsstatus', 'title', 'publisher',
            'publicationyear', 'language', 'sourceurl', 'identifier', 'identifiertype', 'citation',
            'rightsstatement', 'sourcenote', 'description', 'metadata'
        ]);
        if (count($changes) > 0) {
            if ($apply) {
                db_update_record($DB, 'uckkarchive_external_work', $record);
                $result['summary']['updated_external_work']++;
            } else {
                $result['summary']['would_update_external_work']++;
            }
        }
        return [(int)$existing->id, count($changes) > 0 ? 'updated' : 'unchanged', $changes];
    }

    if ($apply) {
        $id = db_insert_record($DB, 'uckkarchive_external_work', $record);
        $result['summary']['created_external_work']++;
        return [(int)$id, 'created', []];
    }

    $result['summary']['would_create_external_work']++;
    return [0, 'would_create', []];
}

function apply_media($DB, &$result, $row, $target, $externalworkid, $apply) {
    $now = now_ts();
    $existing = find_media($DB, $target['archiveid'], $row['url'], $externalworkid);

    $metadata = $row['metadata'];
    $metadata['migration']['externalworkid'] = $externalworkid;

    $record = new stdClass();
    $record->uuid = $existing ? $existing->uuid : uuidv4();
    $record->archiveid = $target['archiveid'];
    $record->courseid = $target['courseid'];
    $record->cmid = $target['cmid'];
    $record->contextid = $target['contextid'];
    $record->userid = $target['userid'];
    $record->ownerid = $target['userid'];
    $record->createdby = $existing ? $existing->createdby : $target['userid'];
    $record->modifiedby = $target['userid'];
    $record->externalworkid = $externalworkid ?: null;
    $record->sourceid = $existing ? $existing->sourceid : 0;
    $record->mediatype = safe_string($row['mediatype'], 80);
    $record->mimetype = '';
    $record->status = 'active';
    $record->visibility = 'public';
    $record->audiencesuitability = 'general';
    $record->source = $row['platform'];
    $record->title = $row['title'];
    $record->summary = $row['summary'];
    $record->description = $row['description'];
    $record->metadata = json_text($metadata);
    $record->restricted = 0;
    $record->culturalprotocol = '';
    $record->timecreated = $existing ? $existing->timecreated : $now;
    $record->timemodified = $now;
    $record->sourcetype = 'external_reference_only';
    $record->sourceurl = $row['url'];
    $record->license = '';
    $record->licensekey = '';
    $record->rightsstatement = rights_statement();
    $record->rightsstatus = 'external_reference';

    if ($existing) {
        $record->id = $existing->id;
        $changes = record_changes($existing, $record, [
            'externalworkid', 'mediatype', 'status', 'visibility', 'audiencesuitability', 'source', 'title',
            'summary', 'description', 'metadata', 'restricted', 'sourcetype', 'sourceurl',
            'rightsstatement', 'rightsstatus'
        ]);
        if (count($changes) > 0) {
            if ($apply) {
                db_update_record($DB, 'uckkarchive_media', $record);
                $result['summary']['updated_media']++;
            } else {
                $result['summary']['would_update_media']++;
            }
        }
        return [(int)$existing->id, count($changes) > 0 ? 'updated' : 'unchanged', $changes];
    }

    if ($apply) {
        $id = db_insert_record($DB, 'uckkarchive_media', $record);
        $result['summary']['created_media']++;
        return [(int)$id, 'created', []];
    }

    $result['summary']['would_create_media']++;
    return [0, 'would_create', []];
}

function apply_media_source($DB, &$result, $row, $target, $mediaid, $externalworkid, $apply) {
    $now = now_ts();
    $existing = find_media_source($DB, $target['archiveid'], $mediaid, $row['url']);

    $metadata = [
        'migration' => [
            'source' => 'play_inventory',
            'source_catalog' => $row['sourcecatalog'],
            'legacy_play_id' => $row['legacy_id'],
            'mediaid' => $mediaid,
            'externalworkid' => $externalworkid,
        ],
        'embed' => nested_get($row['metadata']['play_item'], ['embed']),
        'platform' => $row['platform'],
        'type' => $row['type'],
    ];

    $record = new stdClass();
    $record->uuid = $existing ? $existing->uuid : uuidv4();
    $record->archiveid = $target['archiveid'];
    $record->mediaid = $mediaid ?: null;
    $record->externalworkid = $externalworkid ?: null;
    $record->sourcecomponent = 'play_inventory';
    $record->sourceitemid = 0; // Moodle sourceitemid is numeric; legacy /play id is preserved in metadata.
    $record->sourcetype = 'external_reference_only';
    $record->sourceurl = $row['url'];
    $record->title = $row['title'];
    $record->citation = safe_string($row['citation']);
    $record->rightsstatus = 'external_reference';
    $record->metadata = json_text($metadata);
    $record->createdby = $existing ? $existing->createdby : $target['userid'];
    $record->modifiedby = $target['userid'];
    $record->timecreated = $existing ? $existing->timecreated : $now;
    $record->timemodified = $now;

    if ($existing) {
        $record->id = $existing->id;
        $changes = record_changes($existing, $record, [
            'mediaid', 'externalworkid', 'sourcecomponent', 'sourceitemid', 'sourcetype',
            'sourceurl', 'title', 'citation', 'rightsstatus', 'metadata'
        ]);
        if (count($changes) > 0) {
            if ($apply) {
                db_update_record($DB, 'uckkarchive_media_source', $record);
                $result['summary']['updated_media_source']++;
            } else {
                $result['summary']['would_update_media_source']++;
            }
        }
        return [(int)$existing->id, count($changes) > 0 ? 'updated' : 'unchanged', $changes];
    }

    if ($apply) {
        $id = db_insert_record($DB, 'uckkarchive_media_source', $record);
        $result['summary']['created_media_source']++;
        return [(int)$id, 'created', []];
    }

    $result['summary']['would_create_media_source']++;
    return [0, 'would_create', []];
}

function link_media_source($DB, $mediaid, $sourceid, $apply) {
    if (!$apply || !$mediaid || !$sourceid) {
        return;
    }
    $media = $DB->get_record('uckkarchive_media', ['id' => $mediaid], '*', MUST_EXIST);
    if ((int)$media->sourceid !== (int)$sourceid) {
        $media->sourceid = $sourceid;
        $media->timemodified = now_ts();
        db_update_record($DB, 'uckkarchive_media', $media);
    }
}

function apply_tag($DB, &$result, $row, $target, $mediaid, $tagkey, $name, $tagtype, $apply) {
    if (!$mediaid && !$apply) {
        $result['summary']['would_create_tag']++;
        return [0, 'would_create'];
    }

    $existing = false;
    if ($mediaid) {
        $existing = $DB->get_record('uckkarchive_media_tag', [
            'archiveid' => $target['archiveid'],
            'mediaid' => $mediaid,
            'tagkey' => $tagkey,
            'tagtype' => $tagtype,
        ], '*', IGNORE_MISSING);
    }

    if ($existing) {
        return [(int)$existing->id, 'unchanged'];
    }

    $record = new stdClass();
    $record->uuid = uuidv4();
    $record->archiveid = $target['archiveid'];
    $record->mediaid = $mediaid;
    $record->tag = $tagkey;
    $record->tagkey = $tagkey;
    $record->name = safe_string($name, 255);
    $record->rawname = safe_string($name, 255);
    $record->tagtype = $tagtype;
    $record->type = $tagtype;
    $record->createdby = $target['userid'];
    $record->timecreated = now_ts();
    $record->timemodified = now_ts();

    if ($apply) {
        $id = db_insert_record($DB, 'uckkarchive_media_tag', $record);
        $result['summary']['created_tag']++;
        return [(int)$id, 'created'];
    }

    $result['summary']['would_create_tag']++;
    return [0, 'would_create'];
}

function find_collection($DB, $archiveid, $title) {
    return $DB->get_record('uckkarchive_media_collection', [
        'archiveid' => $archiveid,
        'title' => $title,
    ], '*', IGNORE_MISSING);
}

function apply_collection($DB, &$result, $target, $title, $summary, $kind, $legacykey, $sortorder, $apply) {
    $existing = find_collection($DB, $target['archiveid'], $title);
    if ($existing) {
        return [(int)$existing->id, 'unchanged'];
    }

    $record = new stdClass();
    $record->uuid = uuidv4();
    $record->archiveid = $target['archiveid'];
    $record->courseid = $target['courseid'];
    $record->cmid = $target['cmid'];
    $record->contextid = $target['contextid'];
    $record->ownerid = $target['userid'];
    $record->createdby = $target['userid'];
    $record->modifiedby = $target['userid'];
    $record->title = safe_string($title, 255);
    $record->summary = safe_string($summary, 700);
    $record->description = safe_string($summary);
    $record->status = 'active';
    $record->visibility = 'public';
    $record->sortorder = $sortorder;
    $record->metadata = json_text([
        'migration' => [
            'source' => 'play_inventory',
            'collection_kind' => $kind,
            'legacy_key' => $legacykey,
        ],
    ]);
    $record->timecreated = now_ts();
    $record->timemodified = now_ts();

    if ($apply) {
        $id = db_insert_record($DB, 'uckkarchive_media_collection', $record);
        $result['summary']['created_collection']++;
        return [(int)$id, 'created'];
    }

    $result['summary']['would_create_collection']++;
    return [0, 'would_create'];
}

function apply_collection_item($DB, &$result, $target, $collectionid, $mediaid, $sortorder, $apply) {
    if (!$collectionid || !$mediaid) {
        if (!$apply) {
            $result['summary']['would_create_collection_item']++;
            return [0, 'would_create'];
        }
        return [0, 'skipped'];
    }

    $existing = $DB->get_record('uckkarchive_media_collection_item', [
        'collectionid' => $collectionid,
        'mediaid' => $mediaid,
    ], '*', IGNORE_MISSING);

    if ($existing) {
        return [(int)$existing->id, 'unchanged'];
    }

    $record = new stdClass();
    $record->collectionid = $collectionid;
    $record->mediaid = $mediaid;
    $record->sortorder = $sortorder;
    $record->addedby = $target['userid'];
    $record->timecreated = now_ts();

    if ($apply) {
        $id = db_insert_record($DB, 'uckkarchive_media_collection_item', $record);
        $result['summary']['created_collection_item']++;
        return [(int)$id, 'created'];
    }

    $result['summary']['would_create_collection_item']++;
    return [0, 'would_create'];
}

function import_item($DB, &$result, $row, $target, $apply) {
    if ($row['url'] === null || $row['url'] === '' || $row['title'] === null || $row['title'] === '') {
        $result['summary']['skipped']++;
        return [
            'legacy_id' => $row['legacy_id'],
            'title' => $row['title'],
            'url' => $row['url'],
            'status' => 'skipped',
            'reason' => 'missing_title_or_url',
        ];
    }

    [$externalworkid, $externalStatus, $externalChanges] = apply_external_work($DB, $result, $row, $target, $apply);
    [$mediaid, $mediaStatus, $mediaChanges] = apply_media($DB, $result, $row, $target, $externalworkid, $apply);
    [$sourceid, $sourceStatus, $sourceChanges] = apply_media_source($DB, $result, $row, $target, $mediaid, $externalworkid, $apply);
    link_media_source($DB, $mediaid, $sourceid, $apply);

    $tagResults = [];
    foreach ($row['topics'] as $topic) {
        $topic = safe_string($topic, 255);
        if ($topic === null || $topic === '') {
            continue;
        }
        [$tagid, $tagstatus] = apply_tag($DB, $result, $row, $target, $mediaid, slug_key($topic), topic_label($row['root'], $topic), 'topic', $apply);
        $tagResults[] = ['key' => slug_key($topic), 'status' => $tagstatus, 'id' => $tagid];
    }

    if ($row['platform']) {
        [$tagid, $tagstatus] = apply_tag($DB, $result, $row, $target, $mediaid, slug_key($row['platform']), $row['platform'], 'platform', $apply);
        $tagResults[] = ['key' => slug_key($row['platform']), 'status' => $tagstatus, 'id' => $tagid];
    }

    if ($row['type']) {
        [$tagid, $tagstatus] = apply_tag($DB, $result, $row, $target, $mediaid, slug_key($row['type']), $row['type'], 'type', $apply);
        $tagResults[] = ['key' => slug_key($row['type']), 'status' => $tagstatus, 'id' => $tagid];
    }

    $collectionResults = [];
    $collectionSort = 0;

    foreach ($row['sections'] as $section) {
        $section = safe_string($section, 100);
        if (!$section) {
            continue;
        }
        $title = 'Section: ' . $section;
        [$collectionid, $collectionstatus] = apply_collection($DB, $result, $target, $title, 'Imported /play section collection.', 'section', $section, 1000 + $collectionSort, $apply);
        [$itemid, $itemstatus] = apply_collection_item($DB, $result, $target, $collectionid, $mediaid, 0, $apply);
        $collectionResults[] = ['title' => $title, 'collection_status' => $collectionstatus, 'item_status' => $itemstatus];
        $collectionSort++;
    }

    $primary = safe_string($row['primarySection'], 100);
    if ($primary) {
        $title = 'Primary section: ' . $primary;
        [$collectionid, $collectionstatus] = apply_collection($DB, $result, $target, $title, 'Imported /play primary-section collection.', 'primary_section', $primary, 2000, $apply);
        [$itemid, $itemstatus] = apply_collection_item($DB, $result, $target, $collectionid, $mediaid, 0, $apply);
        $collectionResults[] = ['title' => $title, 'collection_status' => $collectionstatus, 'item_status' => $itemstatus];
    }

    if ($row['featured']) {
        $title = 'Featured /play';
        [$collectionid, $collectionstatus] = apply_collection($DB, $result, $target, $title, 'Featured items imported from /play.', 'featured', 'featured', 10, $apply);
        [$itemid, $itemstatus] = apply_collection_item($DB, $result, $target, $collectionid, $mediaid, 0, $apply);
        $collectionResults[] = ['title' => $title, 'collection_status' => $collectionstatus, 'item_status' => $itemstatus];
    }

    foreach ($row['albums'] as $album) {
        $album = safe_string($album, 120);
        if (!$album) {
            continue;
        }
        $label = album_label($row['youtubeCatalog'], $album);
        $title = 'Album: ' . $label;
        $sort = is_numeric($row['albumTrack']) ? (int)$row['albumTrack'] : 0;
        [$collectionid, $collectionstatus] = apply_collection($DB, $result, $target, $title, 'YouTube album imported from /play inventory.', 'album', $album, 3000, $apply);
        [$itemid, $itemstatus] = apply_collection_item($DB, $result, $target, $collectionid, $mediaid, $sort, $apply);
        $collectionResults[] = ['title' => $title, 'collection_status' => $collectionstatus, 'item_status' => $itemstatus, 'sortorder' => $sort];
    }

    return [
        'legacy_id' => $row['legacy_id'],
        'title' => $row['title'],
        'url' => $row['url'],
        'platform' => $row['platform'],
        'type' => $row['type'],
        'worktype' => $row['worktype'],
        'mediatype' => $row['mediatype'],
        'external_work' => ['id' => $externalworkid, 'status' => $externalStatus, 'changes' => $externalChanges],
        'media' => ['id' => $mediaid, 'status' => $mediaStatus, 'changes' => $mediaChanges],
        'media_source' => ['id' => $sourceid, 'status' => $sourceStatus, 'changes' => $sourceChanges],
        'tags' => $tagResults,
        'collections' => $collectionResults,
    ];
}

try {
    $moodleroot = require_arg($argv, 1, 'moodleRoot');
    $sourcedir = require_arg($argv, 2, 'sourceDir');
    $archiveid = (int)require_arg($argv, 3, 'archiveId');
    $courseid = (int)require_arg($argv, 4, 'courseId');
    $useridarg = (int)require_arg($argv, 5, 'userId');
    $mode = require_arg($argv, 6, 'mode');

    $apply = ($mode === 'apply');

    $result['mode'] = $mode;
    $result['apply'] = $apply;
    $result['input'] = [
        'moodle_root' => $moodleroot,
        'source_dir' => $sourcedir,
    ];

    $moodleroot = rtrim($moodleroot, "\\/");
    $sourcedir = rtrim($sourcedir, "\\/");
    $configpath = $moodleroot . DIRECTORY_SEPARATOR . 'config.php';

    if (!is_file($configpath)) {
        throw new RuntimeException("config.php not found at {$configpath}");
    }

    require($configpath);

    global $CFG, $DB;

    $userid = $useridarg > 0 ? $useridarg : get_admin_userid();
    [$cmid, $contextid] = get_context_info($DB, $archiveid, $courseid);

    $target = [
        'archiveid' => $archiveid,
        'courseid' => $courseid,
        'cmid' => $cmid,
        'contextid' => $contextid,
        'userid' => $userid,
    ];

    $result['moodle'] = [
        'release' => $CFG->release ?? null,
        'version' => $CFG->version ?? null,
        'dbtype' => $CFG->dbtype ?? null,
        'dbhost' => $CFG->dbhost ?? null,
        'dbname' => $CFG->dbname ?? null,
        'dbuser' => $CFG->dbuser ?? null,
        'prefix' => $CFG->prefix ?? '',
    ];
    $result['target'] = $target;

    if (!validate_columns($DB, $result)) {
        $result['ok'] = false;
        echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit(0);
    }

    $archive = $DB->get_record('uckkarchive', ['id' => $archiveid], '*', IGNORE_MISSING);
    if (!$archive) {
        throw new RuntimeException("Target archiveid {$archiveid} not found in {uckkarchive}.");
    }
    if ((int)$archive->course !== $courseid) {
        throw new RuntimeException("Target archiveid {$archiveid} belongs to course {$archive->course}, not requested course {$courseid}.");
    }

    $root = load_json_file($sourcedir . DIRECTORY_SEPARATOR . 'inventory.root.json');
    $youtubeCatalog = load_json_file($sourcedir . DIRECTORY_SEPARATOR . 'inventory.youtube.catalog.json');

    $loaded = [];
    foreach (catalog_files() as $filename => $sourcecatalog) {
        $catalog = load_json_file($sourcedir . DIRECTORY_SEPARATOR . $filename);
        $items = as_array(obj_get($catalog, 'items', []));
        $result['catalogs'][] = [
            'file' => $filename,
            'source_catalog' => $sourcecatalog,
            'items' => count($items),
        ];
        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }
            $result['summary']['loaded_items']++;
            $identity = item_identity($item);
            if ($identity === null) {
                $result['summary']['skipped']++;
                add_warning($result, 'catalog', 'ITEM_WITHOUT_IDENTITY', 'Item has neither id nor url.', ['catalog' => $filename, 'title' => obj_get($item, 'title')]);
                continue;
            }
            if (array_key_exists($identity, $loaded)) {
                $result['summary']['duplicates_skipped']++;
                continue;
            }
            $loaded[$identity] = row_from_item($item, $sourcecatalog, $root, $youtubeCatalog, $target);
        }
    }

    $result['summary']['unique_items'] = count($loaded);

    $encodingIssues = collect_encoding_issues($loaded);
    if (count($encodingIssues) > 0) {
        add_error($result, 'encoding', 'MOJIBAKE_DETECTED', 'Potential UTF-8 mojibake detected in imported text. Import aborted before any DB write.', [
            'examples' => $encodingIssues,
            'hint' => 'Verify source JSON encoding and PowerShell/PHP UTF-8 handling before using -Apply.'
        ]);
        $result['ok'] = false;
        echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit(0);
    }

    $transaction = null;
    if ($apply) {
        $transaction = $DB->start_delegated_transaction();
    }

    foreach ($loaded as $identity => $row) {
        try {
            $result['items'][] = import_item($DB, $result, $row, $target, $apply);
        } catch (Throwable $e) {
            add_error($result, 'item', 'ITEM_IMPORT_FAILED', $e->getMessage(), [
                'identity' => $identity,
                'legacy_id' => $row['legacy_id'],
                'title' => $row['title'],
                'url' => $row['url'],
                'exception' => throwable_details($e),
            ]);
            if ($apply) {
                throw $e;
            }
        }
    }

    if ($apply && $transaction) {
        $transaction->allow_commit();
    }

    $result['ok'] = count($result['errors']) === 0 && $result['schema']['ok'];
} catch (Throwable $e) {
    add_error($result, 'fatal', 'IMPORT_FAILED', $e->getMessage(), ['exception' => throwable_details($e)]);
    $result['ok'] = false;
}

echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
'@

Set-Content -LiteralPath $helperPath -Value $phpCode -Encoding UTF8

Write-Host ""
Write-Host "UCKK /play external-reference importer"
Write-Host "Mode: $mode"
Write-Host "Moodle root: $resolvedMoodleRoot"
Write-Host "Source dir: $resolvedSourceDir"
Write-Host "Target archiveid: $ArchiveId"
Write-Host "Target courseid: $CourseId"
Write-Host ""

$phpArgs = @(
  $helperPath,
  $resolvedMoodleRoot,
  $resolvedSourceDir,
  [string]$ArchiveId,
  [string]$CourseId,
  [string]$UserId,
  $mode
)

$raw = & $PhpPath @phpArgs 2>&1
$exitCode = $LASTEXITCODE
$rawText = ($raw | Out-String).Trim()

if ($exitCode -ne 0) {
  $rawText | Set-Content -LiteralPath (Join-Path $outputDirItem.FullName "php_import_error_output.txt") -Encoding UTF8
  Stop-WithMessage "PHP helper exited with code $exitCode. See php_import_error_output.txt in $($outputDirItem.FullName)."
}

if ([string]::IsNullOrWhiteSpace($rawText)) {
  Stop-WithMessage "PHP helper returned empty output."
}

try {
  $report = $rawText | ConvertFrom-Json
}
catch {
  $rawText | Set-Content -LiteralPath (Join-Path $outputDirItem.FullName "php_import_raw_output.txt") -Encoding UTF8
  Stop-WithMessage "PHP helper did not return valid JSON. See php_import_raw_output.txt in $($outputDirItem.FullName)."
}

$rawText | Set-Content -LiteralPath $jsonReportPath -Encoding UTF8

$lines = [System.Collections.Generic.List[string]]::new()
$lines.Add("# UCKK /play external references import report")
$lines.Add("")
$lines.Add("- Generated at: $($report.generated_at)")
$lines.Add("- Mode: $($report.mode)")
$lines.Add("- Apply writes: $($report.apply)")
$lines.Add("- Moodle release: $($report.moodle.release)")
$lines.Add("- DB: $($report.moodle.dbtype) / $($report.moodle.dbname)")
$lines.Add("- Prefix: $($report.moodle.prefix)")
$lines.Add("- Target archiveid: $($report.target.archiveid)")
$lines.Add("- Target courseid: $($report.target.courseid)")
$lines.Add("- Target cmid: $($report.target.cmid)")
$lines.Add("- Target contextid: $($report.target.contextid)")
$lines.Add("- Acting userid: $($report.target.userid)")
$lines.Add("")

$lines.Add("## Summary")
$lines.Add("")
$lines.Add("| Metric | Count |")
$lines.Add("|---|---:|")
foreach ($prop in $report.summary.PSObject.Properties) {
  $lines.Add("| $($prop.Name) | $($prop.Value) |")
}

$lines.Add("")
$lines.Add("## Catalogs")
$lines.Add("")
$lines.Add("| File | Source catalog | Items |")
$lines.Add("|---|---|---:|")
foreach ($catalog in $report.catalogs) {
  $lines.Add("| $($catalog.file) | $($catalog.source_catalog) | $($catalog.items) |")
}

$lines.Add("")
$lines.Add("## Schema")
$lines.Add("")
$lines.Add("- Schema OK: $($report.schema.ok)")
if ($report.schema.issues.Count -gt 0) {
  $lines.Add("")
  $lines.Add("| Table | Missing columns |")
  $lines.Add("|---|---|")
  foreach ($issue in $report.schema.issues) {
    $missing = if ($issue.missing_columns) { $issue.missing_columns -join ", " } else { $issue.error }
    $lines.Add("| $($issue.table) | $missing |")
  }
}

$lines.Add("")
$lines.Add("## Errors")
$lines.Add("")
if ($report.errors.Count -eq 0) {
  $lines.Add("No errors.")
}
else {
  $lines.Add("| Stage | Code | Message |")
  $lines.Add("|---|---|---|")
  foreach ($err in $report.errors) {
    $message = ([string]$err.message).Replace("|", "\|").Replace("`r", " ").Replace("`n", " ")
    $lines.Add("| $($err.stage) | $($err.code) | $message |")
  }
}

$lines.Add("")
$lines.Add("## First 40 item results")
$lines.Add("")
$lines.Add("| Title | Platform | Type | External work | Media | Source |")
$lines.Add("|---|---|---|---|---|---|")
$count = 0
foreach ($item in $report.items) {
  if ($count -ge 40) { break }
  $title = ([string]$item.title).Replace("|", "\|").Replace("`r", " ").Replace("`n", " ")
  $platform = if ($item.PSObject.Properties.Name -contains "platform") { $item.platform } else { "" }
  $type = if ($item.PSObject.Properties.Name -contains "type") { $item.type } else { "" }
  $externalStatus = if (($item.PSObject.Properties.Name -contains "external_work") -and $null -ne $item.external_work) { $item.external_work.status } else { $item.status }
  $mediaStatus = if (($item.PSObject.Properties.Name -contains "media") -and $null -ne $item.media) { $item.media.status } else { "" }
  $sourceStatus = if (($item.PSObject.Properties.Name -contains "media_source") -and $null -ne $item.media_source) { $item.media_source.status } else { "" }
  $lines.Add("| $title | $platform | $type | $externalStatus | $mediaStatus | $sourceStatus |")
  $count++
}

$lines | Set-Content -LiteralPath $mdReportPath -Encoding UTF8

Write-Host ""
Write-Host "Import report written."
Write-Host "JSON: $jsonReportPath"
Write-Host "Markdown: $mdReportPath"
Write-Host "OK: $($report.ok)"
Write-Host "Mode: $($report.mode)"
Write-Host "Loaded items: $($report.summary.loaded_items)"
Write-Host "Unique items: $($report.summary.unique_items)"
Write-Host "Errors: $($report.summary.errors)"
Write-Host ""

if (-not $report.ok) {
  Write-Host "The import did not complete cleanly. Open the Markdown/JSON report before using -Apply."
}

if ($OpenReport) {
  Invoke-Item -LiteralPath $mdReportPath
}




