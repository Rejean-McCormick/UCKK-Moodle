<?php
// This file is part of UCKK Moodle.
//
// Ops importer for uckk_inventory.json into mod_uckkarchive media tables.

/**
 * Import UCKK media inventory into mod_uckkarchive.
 *
 * This ops command is intentionally kept outside mod/uckkarchive/cli.
 * Recommended runtime path:
 *   tools/uckk-ops/import/import_uckkarchive_media.php
 *
 * It stores original files via Moodle File API, never directly under public/.
 * It writes metadata to the uckkarchive media tables using schema-aware inserts:
 * only fields that exist in the installed schema are populated.
 *
 */

define('CLI_SCRIPT', true);

/**
 * Parse --key=value and --key value arguments before Moodle is loaded.
 *
 * @param array $argv Raw argv.
 * @return array
 */
function uckkarchive_cli_preparse(array $argv): array {
    $out = [];
    $count = count($argv);
    for ($i = 1; $i < $count; $i++) {
        $arg = $argv[$i];
        if (substr($arg, 0, 2) !== '--') {
            continue;
        }
        $arg = substr($arg, 2);
        if (strpos($arg, '=') !== false) {
            [$key, $value] = explode('=', $arg, 2);
            $out[$key] = $value;
            continue;
        }
        $next = $argv[$i + 1] ?? null;
        if ($next !== null && substr($next, 0, 2) !== '--') {
            $out[$arg] = $next;
            $i++;
        } else {
            $out[$arg] = 1;
        }
    }
    return $out;
}

$preargs = uckkarchive_cli_preparse($argv);
$moodleroot = $preargs['moodle-root'] ?? null;
if ($moodleroot) {
    $configfile = rtrim($moodleroot, DIRECTORY_SEPARATOR . '/\\') . DIRECTORY_SEPARATOR . 'config.php';
} else {
    $configfile = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'config.php';
}

if (!is_readable($configfile)) {
    fwrite(STDERR, "Moodle config.php not found or unreadable: {$configfile}\n");
    exit(1);
}

require($configfile);
require_once($CFG->libdir . '/clilib.php');
require_once($CFG->libdir . '/filelib.php');
require_once($CFG->dirroot . '/course/lib.php');
if (is_readable($CFG->dirroot . '/mod/uckkarchive/locallib.php')) {
    require_once($CFG->dirroot . '/mod/uckkarchive/locallib.php');
}

[$options, $unrecognized] = cli_get_params([
    'help' => false,
    'moodle-root' => '',
    'inventory' => '',
    'originals' => '',
    'archiveid' => 0,
    'courseid' => 0,
    'cmid' => 0,
    'contextid' => 0,
    'userid' => 0,
    'dry-run' => 1,
    'allow-missing-files' => 0,
    'allow-system-context' => 0,
    'update-metadata' => 0,
    'force-new-version' => 0,
    'offset' => 0,
    'limit' => 0,
    'report' => '',
    'component' => 'mod_uckkarchive',
    'filearea' => 'media_original',
], [
    'h' => 'help',
]);

if (!empty($options['help'])) {
    $help = <<<HELP
Import UCKK inventory media into mod_uckkarchive from tools/uckk-ops/import.

Required:
  --inventory=/path/to/uckk_inventory.json
  --originals=/path/to/original/files

Context, choose at least one:
  --archiveid=1      Resolve the uckkarchive module context from activity instance.
  --cmid=42          Use an existing course module id.
  --contextid=123    Use an explicit Moodle context id.

Optional:
  --courseid=2
  --userid=2
  --dry-run=1|0
  --allow-missing-files=1|0
  --allow-system-context=1|0    Only for diagnostics; module context is preferred.
  --update-metadata=1|0
  --force-new-version=1|0
  --offset=0
  --limit=0
  --report=/path/to/report.json

Examples:
  php tools/uckk-ops/import/import_uckkarchive_media.php \
    --inventory=/srv/uckk/import/uckkarchive/uckk_inventory.json \
    --originals=/srv/uckk/import/uckkarchive/originals \
    --archiveid=1 \
    --dry-run=1

  php tools/uckk-ops/import/import_uckkarchive_media.php \
    --inventory=/srv/uckk/import/uckkarchive/uckk_inventory.json \
    --originals=/srv/uckk/import/uckkarchive/originals \
    --cmid=42 \
    --dry-run=0
HELP;
    cli_writeln($help);
    exit(0);
}

/**
 * Convert CLI option to bool.
 *
 * @param mixed $value
 * @return bool
 */
function uckkarchive_cli_bool($value): bool {
    if (is_bool($value)) {
        return $value;
    }
    $value = strtolower(trim((string)$value));
    return in_array($value, ['1', 'true', 'yes', 'y', 'on'], true);
}

/**
 * Fail with a message.
 *
 * @param string $message
 * @return never
 */
function uckkarchive_fail(string $message) {
    fwrite(STDERR, $message . PHP_EOL);
    exit(1);
}

/**
 * Decode JSON file.
 *
 * @param string $path
 * @return array
 */
function uckkarchive_read_json(string $path): array {
    if ($path === '' || !is_readable($path)) {
        uckkarchive_fail("JSON file not readable: {$path}");
    }
    $raw = file_get_contents($path);
    $data = json_decode($raw, true);
    if (!is_array($data)) {
        uckkarchive_fail("Invalid JSON in {$path}: " . json_last_error_msg());
    }
    return $data;
}

/**
 * Encode JSON for DB metadata/report files.
 *
 * @param mixed $data
 * @return string
 */
function uckkarchive_json($data): string {
    return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
}

/**
 * Return table columns or empty array if absent.
 *
 * @param string $table Table without prefix.
 * @return array
 */
function uckkarchive_columns(string $table): array {
    global $DB;
    static $cache = [];
    if (array_key_exists($table, $cache)) {
        return $cache[$table];
    }
    try {
        $cache[$table] = $DB->get_columns($table);
    } catch (Throwable $e) {
        $cache[$table] = [];
    }
    return $cache[$table];
}

/**
 * True when table exists.
 *
 * @param string $table Table without prefix.
 * @return bool
 */
function uckkarchive_table_exists(string $table): bool {
    return count(uckkarchive_columns($table)) > 0;
}

/**
 * True when table contains field.
 *
 * @param string $table
 * @param string $field
 * @return bool
 */
function uckkarchive_has_field(string $table, string $field): bool {
    $columns = uckkarchive_columns($table);
    return array_key_exists($field, $columns);
}

/**
 * Return first field present in table.
 *
 * @param string $table
 * @param array $fields
 * @return string|null
 */
function uckkarchive_first_field(string $table, array $fields): ?string {
    foreach ($fields as $field) {
        if (uckkarchive_has_field($table, $field)) {
            return $field;
        }
    }
    return null;
}

/**
 * Convert associative array to stdClass containing only fields present in table.
 *
 * @param string $table
 * @param array $data
 * @return stdClass
 */
function uckkarchive_filter_record(string $table, array $data): stdClass {
    $columns = uckkarchive_columns($table);
    $record = new stdClass();
    foreach ($data as $field => $value) {
        if (array_key_exists($field, $columns)) {
            $record->{$field} = $value;
        }
    }
    return $record;
}

/**
 * Count public properties.
 *
 * @param stdClass $record
 * @return int
 */
function uckkarchive_record_field_count(stdClass $record): int {
    return count(get_object_vars($record));
}

/**
 * Safe filename leaf.
 *
 * @param string $filename
 * @return string
 */
function uckkarchive_safe_leaf(string $filename): string {
    $leaf = basename(str_replace('\\', '/', $filename));
    if ($leaf === '' || $leaf === '.' || $leaf === '..') {
        uckkarchive_fail("Unsafe or empty filename: {$filename}");
    }
    return $leaf;
}

/**
 * Find source file by proposed filename first, then original filename.
 *
 * @param string $dir
 * @param string $original
 * @param string $proposed
 * @return string|null
 */
function uckkarchive_find_source_file(string $dir, string $original, string $proposed): ?string {
    $dir = rtrim($dir, DIRECTORY_SEPARATOR . '/\\');
    foreach ([uckkarchive_safe_leaf($proposed), uckkarchive_safe_leaf($original)] as $leaf) {
        $path = $dir . DIRECTORY_SEPARATOR . $leaf;
        if (is_file($path) && is_readable($path)) {
            return realpath($path);
        }
    }

    $targets = [uckkarchive_safe_leaf($proposed), uckkarchive_safe_leaf($original)];
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );
    foreach ($iterator as $file) {
        if (!$file->isFile()) {
            continue;
        }
        if (in_array($file->getFilename(), $targets, true)) {
            return $file->getRealPath();
        }
    }

    return null;
}

/**
 * Return known MIME type by extension.
 *
 * @param string $filename
 * @return string|null
 */
function uckkarchive_expected_mimetype(string $filename): ?string {
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    $map = [
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'pdf' => 'application/pdf',
    ];
    return $map[$ext] ?? null;
}

/**
 * Slug-like stable key.
 *
 * @param string $value
 * @return string
 */
function uckkarchive_stable_key(string $value): string {
    $value = strtolower(trim($value));
    $value = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value) ?: $value;
    $value = preg_replace('/[^a-z0-9]+/', '_', $value);
    $value = trim($value, '_');
    return $value !== '' ? $value : 'uckk_media_' . substr(sha1($value), 0, 12);
}

/**
 * Convert UUID to raw bytes.
 *
 * @param string $uuid
 * @return string
 */
function uckkarchive_uuid_bytes(string $uuid): string {
    $hex = str_replace('-', '', strtolower($uuid));
    if (!preg_match('/^[0-9a-f]{32}$/', $hex)) {
        uckkarchive_fail("Invalid UUID namespace: {$uuid}");
    }
    return hex2bin($hex);
}

/**
 * Generate deterministic UUID v5.
 *
 * @param string $namespace
 * @param string $name
 * @return string
 */
function uckkarchive_uuid_v5(string $namespace, string $name): string {
    $hash = sha1(uckkarchive_uuid_bytes($namespace) . $name);
    return sprintf('%08s-%04s-%04x-%04x-%12s',
        substr($hash, 0, 8),
        substr($hash, 8, 4),
        (hexdec(substr($hash, 12, 4)) & 0x0fff) | 0x5000,
        (hexdec(substr($hash, 16, 4)) & 0x3fff) | 0x8000,
        substr($hash, 20, 12)
    );
}

/**
 * Resolve execution context.
 *
 * @param array $options
 * @return array{context:context,cmid:int,archiveid:int,courseid:int}
 */
function uckkarchive_resolve_context(array $options): array {
    global $DB;

    $archiveid = (int)$options['archiveid'];
    $courseid = (int)$options['courseid'];
    $cmid = (int)$options['cmid'];
    $contextid = (int)$options['contextid'];
    $allowsystem = uckkarchive_cli_bool($options['allow-system-context']);

    if ($contextid > 0) {
        $context = context::instance_by_id($contextid, MUST_EXIST);
        return ['context' => $context, 'cmid' => $cmid, 'archiveid' => $archiveid, 'courseid' => $courseid];
    }

    if ($cmid > 0) {
        $cm = get_coursemodule_from_id('uckkarchive', $cmid, 0, false, MUST_EXIST);
        $context = context_module::instance($cm->id);
        return ['context' => $context, 'cmid' => (int)$cm->id, 'archiveid' => (int)$cm->instance, 'courseid' => (int)$cm->course];
    }

    if ($archiveid > 0) {
        $cm = get_coursemodule_from_instance('uckkarchive', $archiveid, $courseid, false, IGNORE_MISSING);
        if ($cm) {
            $context = context_module::instance($cm->id);
            return ['context' => $context, 'cmid' => (int)$cm->id, 'archiveid' => $archiveid, 'courseid' => (int)$cm->course];
        }

        if (uckkarchive_table_exists('uckkarchive')) {
            $archive = $DB->get_record('uckkarchive', ['id' => $archiveid], '*', IGNORE_MISSING);
            if ($archive && !empty($archive->course)) {
                $courseid = (int)$archive->course;
            }
        }
    }

    if ($allowsystem) {
        $context = context_system::instance();
        return ['context' => $context, 'cmid' => $cmid, 'archiveid' => $archiveid, 'courseid' => $courseid];
    }

    uckkarchive_fail('Could not resolve a mod_uckkarchive context. Pass --cmid, --contextid, or --archiveid. Use --allow-system-context=1 only for diagnostic imports.');
}

/**
 * Select a user for createdby fields.
 *
 * @param int $userid
 * @return stdClass
 */
function uckkarchive_resolve_user(int $userid): stdClass {
    global $DB;
    if ($userid > 0) {
        return $DB->get_record('user', ['id' => $userid, 'deleted' => 0], '*', MUST_EXIST);
    }
    $admin = get_admin();
    if (!$admin) {
        uckkarchive_fail('Could not resolve admin user. Pass --userid explicitly.');
    }
    return $admin;
}

/**
 * Find an existing media record.
 *
 * @param string $mediauuid
 * @param string $title
 * @return stdClass|null
 */
function uckkarchive_find_media(string $mediauuid, string $title): ?stdClass {
    global $DB;
    if (!uckkarchive_table_exists('uckkarchive_media')) {
        return null;
    }
    if ($mediauuid !== '' && uckkarchive_has_field('uckkarchive_media', 'uuid')) {
        $record = $DB->get_record('uckkarchive_media', ['uuid' => $mediauuid], '*', IGNORE_MISSING);
        if ($record) {
            return $record;
        }
    }
    return null;
}

/**
 * Find an existing media version by hash.
 *
 * @param int $mediaid
 * @param string $sha256
 * @return stdClass|null
 */
function uckkarchive_find_version_by_hash(int $mediaid, string $sha256): ?stdClass {
    global $DB;
    if (!uckkarchive_table_exists('uckkarchive_media_version')) {
        return null;
    }
    if (uckkarchive_has_field('uckkarchive_media_version', 'mediaid') && uckkarchive_has_field('uckkarchive_media_version', 'contenthash')) {
        return $DB->get_record('uckkarchive_media_version', ['mediaid' => $mediaid, 'contenthash' => $sha256], '*', IGNORE_MISSING) ?: null;
    }
    return null;
}

/**
 * Get next version number.
 *
 * @param int $mediaid
 * @return int
 */
function uckkarchive_next_version_number(int $mediaid): int {
    global $DB;
    $field = uckkarchive_first_field('uckkarchive_media_version', ['versionno', 'versionnumber']);
    if ($field === null || !uckkarchive_has_field('uckkarchive_media_version', 'mediaid')) {
        return 1;
    }
    $max = $DB->get_field_sql("SELECT MAX({$field}) FROM {uckkarchive_media_version} WHERE mediaid = ?", [$mediaid]);
    return ((int)$max) + 1;
}

/**
 * Insert a record when not in dry-run mode.
 *
 * @param string $table
 * @param array $data
 * @param bool $dryrun
 * @return int
 */
function uckkarchive_insert_record(string $table, array $data, bool $dryrun): int {
    global $DB;
    $record = uckkarchive_filter_record($table, $data);
    if (uckkarchive_record_field_count($record) === 0) {
        return 0;
    }
    if ($dryrun) {
        return 0;
    }
    return (int)$DB->insert_record($table, $record);
}

/**
 * Update a record dynamically.
 *
 * @param string $table
 * @param int $id
 * @param array $data
 * @param bool $dryrun
 * @return void
 */
function uckkarchive_update_record(string $table, int $id, array $data, bool $dryrun): void {
    global $DB;
    $data['id'] = $id;
    $record = uckkarchive_filter_record($table, $data);
    if (!property_exists($record, 'id') || uckkarchive_record_field_count($record) <= 1) {
        return;
    }
    if (!$dryrun) {
        $DB->update_record($table, $record);
    }
}

/**
 * Find content tag by tagkey or create it.
 *
 * @param string $tagkey
 * @param array $advisory
 * @param int $now
 * @param bool $dryrun
 * @return int
 */
function uckkarchive_ensure_content_tag(string $tagkey, array $advisory, int $now, bool $dryrun): int {
    global $DB;
    if (!uckkarchive_table_exists('uckkarchive_content_tag') || !uckkarchive_has_field('uckkarchive_content_tag', 'tagkey')) {
        return 0;
    }
    $existing = $DB->get_record('uckkarchive_content_tag', ['tagkey' => $tagkey], '*', IGNORE_MISSING);
    if ($existing) {
        return (int)$existing->id;
    }
    $uuid = uckkarchive_uuid_v5(UCKKARCHIVE_IMPORT_UUID_NAMESPACE, 'content-tag:' . $tagkey);
    return uckkarchive_insert_record('uckkarchive_content_tag', [
        'uuid' => $uuid,
        'tagkey' => $tagkey,
        'name' => $tagkey,
        'description' => $advisory['advisorytext'] ?? '',
        'descriptionformat' => FORMAT_PLAIN,
        'tagtype' => 'advisory',
        'category' => 'advisory',
        'defaultseverity' => $advisory['severity'] ?? 'notice',
        'severity' => $advisory['severity'] ?? 'notice',
        'defaultaudiencesuitability' => $advisory['audiencesuitability'] ?? null,
        'audiencesuitability' => $advisory['audiencesuitability'] ?? null,
        'defaultreviewstate' => 'pending_review',
        'requiresreview' => 1,
        'status' => 'active',
        'visibility' => 'institution',
        'metadata' => uckkarchive_json(['source' => 'uckk_inventory']),
        'timecreated' => $now,
        'timemodified' => $now,
    ], $dryrun);
}

const UCKKARCHIVE_IMPORT_UUID_NAMESPACE = 'b7d33e70-f3b1-5e87-9a4f-0dd4d92f42d7';

$inventorypath = (string)$options['inventory'];
$originalsdir = (string)$options['originals'];
$dryrun = uckkarchive_cli_bool($options['dry-run']);
$allowmissing = uckkarchive_cli_bool($options['allow-missing-files']);
$updatemetadata = uckkarchive_cli_bool($options['update-metadata']);
$forcenewversion = uckkarchive_cli_bool($options['force-new-version']);
$offset = max(0, (int)$options['offset']);
$limit = max(0, (int)$options['limit']);
$component = (string)$options['component'];
$filearea = (string)$options['filearea'];
$reportpath = (string)$options['report'];

if ($inventorypath === '' || $originalsdir === '') {
    uckkarchive_fail('Missing --inventory or --originals. Use --help for examples.');
}
if (!is_dir($originalsdir)) {
    uckkarchive_fail("Originals directory not found: {$originalsdir}");
}

if (!uckkarchive_table_exists('uckkarchive_media')) {
    uckkarchive_fail('Required table uckkarchive_media is missing. Run Moodle upgrade/install for mod_uckkarchive first.');
}
if (!uckkarchive_table_exists('uckkarchive_media_version')) {
    uckkarchive_fail('Required table uckkarchive_media_version is missing. Run Moodle upgrade/install for mod_uckkarchive first.');
}

$inventory = uckkarchive_read_json($inventorypath);
$files = $inventory['files'] ?? null;
if (!is_array($files)) {
    uckkarchive_fail('Inventory JSON must contain a files array.');
}

if ($offset > 0 || $limit > 0) {
    $files = array_slice($files, $offset, $limit > 0 ? $limit : null);
}

$resolved = uckkarchive_resolve_context($options);
$context = $resolved['context'];
$cmid = $resolved['cmid'];
$archiveid = $resolved['archiveid'];
$courseid = $resolved['courseid'];
$user = uckkarchive_resolve_user((int)$options['userid']);
set_user($user);

$report = [
    'generated_at' => date('c'),
    'mode' => $dryrun ? 'dry-run' : 'apply',
    'inventory' => $inventorypath,
    'originals' => $originalsdir,
    'component' => $component,
    'filearea' => $filearea,
    'contextid' => (int)$context->id,
    'contextlevel' => (int)$context->contextlevel,
    'archiveid' => $archiveid,
    'courseid' => $courseid,
    'cmid' => $cmid,
    'userid' => (int)$user->id,
    'summary' => [
        'listed' => count($files),
        'created_media' => 0,
        'updated_media' => 0,
        'created_versions' => 0,
        'skipped_existing_versions' => 0,
        'missing_files' => 0,
        'errors' => 0,
        'warnings' => 0,
    ],
    'items' => [],
    'schema' => [
        'uckkarchive_media_fields' => array_keys(uckkarchive_columns('uckkarchive_media')),
        'uckkarchive_media_version_fields' => array_keys(uckkarchive_columns('uckkarchive_media_version')),
        'optional_tables_present' => [
            'uckkarchive_media_source' => uckkarchive_table_exists('uckkarchive_media_source'),
            'uckkarchive_media_tag' => uckkarchive_table_exists('uckkarchive_media_tag'),
            'uckkarchive_content_tag' => uckkarchive_table_exists('uckkarchive_content_tag'),
            'uckkarchive_content_marker' => uckkarchive_table_exists('uckkarchive_content_marker'),
            'uckkarchive_media_relation' => uckkarchive_table_exists('uckkarchive_media_relation'),
        ],
    ],
];

$fs = get_file_storage();
$transaction = null;
if (!$dryrun) {
    $transaction = $DB->start_delegated_transaction();
}

try {
    $now = time();
    $index = $offset;

    foreach ($files as $entry) {
        $index++;
        $itemreport = [
            'index' => $index,
            'status' => 'pending',
            'messages' => [],
        ];

        $ops = $entry['file_operations'] ?? [];
        $media = $entry['uckkarchive_media'] ?? [];
        $source = $entry['uckkarchive_media_source'] ?? [];
        $tags = $entry['uckkarchive_media_tags'] ?? [];
        $advisories = $entry['uckkarchive_content_advisories'] ?? [];
        $relations = $entry['uckkarchive_relations'] ?? [];

        $originalname = (string)($ops['original_filename'] ?? '');
        $proposedname = (string)($ops['proposed_filename'] ?? '');
        $mimetype = (string)($ops['mimetype'] ?? '');
        $action = (string)($ops['action'] ?? '');
        $title = (string)($media['title'] ?? $proposedname);
        $safeproposed = uckkarchive_safe_leaf($proposedname);
        $safeoriginal = uckkarchive_safe_leaf($originalname);
        $stablekey = (string)($entry['inventory_key'] ?? uckkarchive_stable_key(pathinfo($safeproposed, PATHINFO_FILENAME)));
        $expectedmime = uckkarchive_expected_mimetype($safeproposed);

        $itemreport['inventory_key'] = $stablekey;
        $itemreport['original_filename'] = $safeoriginal;
        $itemreport['proposed_filename'] = $safeproposed;
        $itemreport['title'] = $title;

        if ($action !== 'import_to_media_library') {
            $itemreport['messages'][] = "Skipped action '{$action}'.";
            $itemreport['status'] = 'skipped_action';
            $report['items'][] = $itemreport;
            continue;
        }

        if ($expectedmime === null) {
            throw new coding_exception("Unsupported extension for {$safeproposed}. Supported: docx, pdf.");
        }
        if ($mimetype !== '' && $mimetype !== $expectedmime) {
            $itemreport['messages'][] = "Inventory MIME '{$mimetype}' differs from expected '{$expectedmime}'. Expected MIME will be used.";
            $report['summary']['warnings']++;
        }
        $mimetype = $expectedmime;

        $sourcepath = uckkarchive_find_source_file($originalsdir, $safeoriginal, $safeproposed);
        if ($sourcepath === null) {
            $report['summary']['missing_files']++;
            $itemreport['status'] = 'missing_file';
            $itemreport['messages'][] = 'Source file not found.';
            if (!$allowmissing) {
                $report['summary']['errors']++;
            }
            $report['items'][] = $itemreport;
            if (!$allowmissing) {
                throw new RuntimeException('Source file not found for ' . $safeproposed);
            }
            continue;
        }

        $sha256 = hash_file('sha256', $sourcepath);
        $filesize = filesize($sourcepath);
        $mediauuid = (string)($entry['mediauuid'] ?? ($media['uuid'] ?? ''));
        if ($mediauuid === '') {
            $mediauuid = uckkarchive_uuid_v5(UCKKARCHIVE_IMPORT_UUID_NAMESPACE, 'media:' . $stablekey);
        }
        $versionuuid = (string)($entry['versionuuid'] ?? '');
        if ($versionuuid === '') {
            $versionuuid = uckkarchive_uuid_v5(UCKKARCHIVE_IMPORT_UUID_NAMESPACE, 'media-version:' . $mediauuid . ':' . $sha256);
        }

        $itemreport['source_path'] = $sourcepath;
        $itemreport['sha256'] = $sha256;
        $itemreport['mediauuid'] = $mediauuid;
        $itemreport['versionuuid'] = $versionuuid;

        $existingmedia = uckkarchive_find_media($mediauuid, $title);
        $mediaid = $existingmedia ? (int)$existingmedia->id : 0;

        $metadatapayload = [
            'inventory_key' => $stablekey,
            'inventory_file_operations' => $ops,
            'inventory_media' => $media,
            'sha256' => $sha256,
            'importer' => 'tools_uckk_ops_import_uckkarchive_media',
        ];

        if ($mediaid === 0) {
            $mediarecord = [
                'uuid' => $mediauuid,
                'archiveid' => $archiveid,
                'uckkarchiveid' => $archiveid,
                'courseid' => $courseid,
                'cmid' => $cmid,
                'contextid' => (int)$context->id,
                'ownerid' => (int)$user->id,
                'userid' => (int)$user->id,
                'createdby' => (int)$user->id,
                'modifiedby' => (int)$user->id,
                'title' => $title,
                'subtitle' => (string)($media['subtitle'] ?? ''),
                'description' => (string)($media['description'] ?? ''),
                'descriptionformat' => FORMAT_PLAIN,
                'mediatype' => (string)($media['mediatype'] ?? 'document'),
                'mimetype' => $mimetype,
                'source' => 'uckk_inventory',
                'sourcetype' => (string)($source['sourcetype'] ?? 'produced_by_uckk'),
                'sourcecomponent' => 'uckk_inventory',
                'status' => (string)($media['status'] ?? 'active'),
                'visibility' => (string)($media['visibility'] ?? 'institution'),
                'audiencesuitability' => (string)($media['audiencesuitability'] ?? 'general'),
                'language' => (string)($media['language'] ?? ''),
                'retentionclass' => (string)($media['retentionclass'] ?? ''),
                'redactionstate' => (string)($media['redactionstate'] ?? ''),
                'licensekey' => (string)($media['licensekey'] ?? ''),
                'rightsstatement' => (string)($media['rightsstatement'] ?? ''),
                'hashoriginal' => $sha256,
                'metadata' => uckkarchive_json($metadatapayload),
                'timecreated' => $now,
                'timemodified' => $now,
            ];

            if ($dryrun) {
                $itemreport['messages'][] = 'Would create media record.';
            } else {
                $mediaid = uckkarchive_insert_record('uckkarchive_media', $mediarecord, false);
            }
            $report['summary']['created_media']++;
        } else {
            if ($updatemetadata) {
                $mediaupdate = [
                    'modifiedby' => (int)$user->id,
                    'title' => $title,
                    'description' => (string)($media['description'] ?? ''),
                    'descriptionformat' => FORMAT_PLAIN,
                    'mediatype' => (string)($media['mediatype'] ?? 'document'),
                    'mimetype' => $mimetype,
                    'status' => (string)($media['status'] ?? 'active'),
                    'visibility' => (string)($media['visibility'] ?? 'institution'),
                    'audiencesuitability' => (string)($media['audiencesuitability'] ?? 'general'),
                    'language' => (string)($media['language'] ?? ''),
                    'retentionclass' => (string)($media['retentionclass'] ?? ''),
                    'redactionstate' => (string)($media['redactionstate'] ?? ''),
                    'hashoriginal' => $sha256,
                    'metadata' => uckkarchive_json($metadatapayload),
                    'timemodified' => $now,
                ];
                uckkarchive_update_record('uckkarchive_media', $mediaid, $mediaupdate, $dryrun);
                $report['summary']['updated_media']++;
                $itemreport['messages'][] = 'Media metadata update scheduled/applied.';
            } else {
                $itemreport['messages'][] = 'Existing media found; metadata left unchanged.';
            }
        }

        if ($dryrun) {
            $itemreport['status'] = 'dry_run_ok';
            $report['items'][] = $itemreport;
            continue;
        }

        $existingversion = $forcenewversion ? null : uckkarchive_find_version_by_hash($mediaid, $sha256);
        if ($existingversion) {
            $report['summary']['skipped_existing_versions']++;
            $itemreport['status'] = 'skipped_existing_version';
            $itemreport['versionid'] = (int)$existingversion->id;
            $itemreport['messages'][] = 'Existing media version with same sha256/contenthash found.';
            $report['items'][] = $itemreport;
            continue;
        }

        $versionnumber = uckkarchive_next_version_number($mediaid);
        $versionrecord = [
            'uuid' => $versionuuid,
            'mediaid' => $mediaid,
            'archiveid' => $archiveid,
            'uckkarchiveid' => $archiveid,
            'courseid' => $courseid,
            'cmid' => $cmid,
            'contextid' => (int)$context->id,
            'versionno' => $versionnumber,
            'versionnumber' => $versionnumber,
            'versionlabel' => 'v' . $versionnumber,
            'label' => 'v' . $versionnumber,
            'userid' => (int)$user->id,
            'createdby' => (int)$user->id,
            'status' => (string)($media['status'] ?? 'active'),
            'visibility' => (string)($media['visibility'] ?? 'institution'),
            'filearea' => $filearea,
            'fileitemid' => 0,
            'filename' => $safeproposed,
            'filepath' => '/',
            'originalfilename' => $safeoriginal,
            'mimetype' => $mimetype,
            'filesize' => $filesize,
            'contenthash' => $sha256,
            'reason' => 'inventory_import',
            'changereason' => 'inventory_import',
            'description' => (string)($media['description'] ?? ''),
            'descriptionformat' => FORMAT_PLAIN,
            'changesummary' => 'Initial import from uckk_inventory.json',
            'metadata' => uckkarchive_json([
                'inventory_key' => $stablekey,
                'original_filename' => $safeoriginal,
                'proposed_filename' => $safeproposed,
                'source_path_basename' => basename($sourcepath),
                'sha256' => $sha256,
            ]),
            'timecreated' => $now,
            'timemodified' => $now,
        ];
        $versionid = uckkarchive_insert_record('uckkarchive_media_version', $versionrecord, false);

        $filerecord = [
            'contextid' => (int)$context->id,
            'component' => $component,
            'filearea' => $filearea,
            'itemid' => $versionid,
            'filepath' => '/',
            'filename' => $safeproposed,
            'userid' => (int)$user->id,
            'license' => $CFG->sitedefaultlicense ?? 'unknown',
            'author' => (string)($source['attribution'] ?? fullname($user)),
        ];

        $existingfile = $fs->get_file($filerecord['contextid'], $component, $filearea, $versionid, '/', $safeproposed);
        if ($existingfile) {
            $existingfile->delete();
        }
        $storedfile = $fs->create_file_from_pathname($filerecord, $sourcepath);

        uckkarchive_update_record('uckkarchive_media_version', $versionid, [
            'fileitemid' => $versionid,
            'filesize' => $storedfile->get_filesize(),
            'contenthash' => $sha256,
        ], false);
        uckkarchive_update_record('uckkarchive_media', $mediaid, [
            'currentversionid' => $versionid,
            'hashoriginal' => $sha256,
            'timemodified' => $now,
        ], false);
        $report['summary']['created_versions']++;

        if (uckkarchive_table_exists('uckkarchive_media_source')) {
            $sourceuuid = uckkarchive_uuid_v5(UCKKARCHIVE_IMPORT_UUID_NAMESPACE, 'media-source:' . $mediauuid . ':' . sha1(uckkarchive_json($source)));
            $sourceid = uckkarchive_insert_record('uckkarchive_media_source', [
                'uuid' => $sourceuuid,
                'mediaid' => $mediaid,
                'uckkarchiveid' => $archiveid,
                'archiveid' => $archiveid,
                'courseid' => $courseid,
                'cmid' => $cmid,
                'contextid' => (int)$context->id,
                'userid' => (int)$user->id,
                'sourcetype' => (string)($source['sourcetype'] ?? 'imported'),
                'sourceownership' => (string)($source['sourceownership'] ?? 'unknown_source'),
                'attribution' => (string)($source['attribution'] ?? ''),
                'sourceauthor' => (string)($source['attribution'] ?? ''),
                'citation' => (string)($source['citation'] ?? $source['attribution'] ?? ''),
                'sourcecomponent' => 'uckk_inventory',
                'rightsstatus' => (string)($source['rightsstatus'] ?? 'unknown'),
                'license' => (string)($source['license'] ?? ''),
                'status' => 'active',
                'visibility' => (string)($media['visibility'] ?? 'institution'),
                'metadata' => uckkarchive_json(['inventory_source' => $source]),
                'timecreated' => $now,
                'timemodified' => $now,
            ], false);
            if ($sourceid > 0) {
                uckkarchive_update_record('uckkarchive_media', $mediaid, ['sourceid' => $sourceid], false);
            }
        }

        if (uckkarchive_table_exists('uckkarchive_media_tag') && is_array($tags)) {
            foreach ($tags as $tag) {
                $tagkey = trim((string)$tag);
                if ($tagkey === '') {
                    continue;
                }
                $exists = null;
                if (uckkarchive_has_field('uckkarchive_media_tag', 'mediaid') && uckkarchive_has_field('uckkarchive_media_tag', 'tagkey')) {
                    $exists = $DB->get_record('uckkarchive_media_tag', ['mediaid' => $mediaid, 'tagkey' => $tagkey], '*', IGNORE_MISSING);
                }
                if ($exists) {
                    continue;
                }
                $taguuid = uckkarchive_uuid_v5(UCKKARCHIVE_IMPORT_UUID_NAMESPACE, 'media-tag:' . $mediauuid . ':' . $tagkey);
                uckkarchive_insert_record('uckkarchive_media_tag', [
                    'uuid' => $taguuid,
                    'archiveid' => $archiveid,
                    'uckkarchiveid' => $archiveid,
                    'courseid' => $courseid,
                    'cmid' => $cmid,
                    'contextid' => (int)$context->id,
                    'mediaid' => $mediaid,
                    'tagkey' => $tagkey,
                    'tag' => $tagkey,
                    'tagvalue' => $tagkey,
                    'rawname' => $tagkey,
                    'tagtype' => 'inventory',
                    'source' => 'uckk_inventory',
                    'status' => 'active',
                    'visibility' => (string)($media['visibility'] ?? 'institution'),
                    'userid' => (int)$user->id,
                    'createdby' => (int)$user->id,
                    'modifiedby' => (int)$user->id,
                    'metadata' => uckkarchive_json(['source' => 'uckk_inventory']),
                    'timecreated' => $now,
                    'timemodified' => $now,
                ], false);
            }
        }

        if (is_array($advisories) && count($advisories) > 0) {
            foreach ($advisories as $advisory) {
                if (!is_array($advisory)) {
                    continue;
                }
                $tagkey = trim((string)($advisory['tagkey'] ?? 'requires_context'));
                if ($tagkey === '') {
                    $tagkey = 'requires_context';
                }
                $tagid = uckkarchive_ensure_content_tag($tagkey, $advisory, $now, false);
                if (uckkarchive_table_exists('uckkarchive_content_marker')) {
                    $markeruuid = uckkarchive_uuid_v5(UCKKARCHIVE_IMPORT_UUID_NAMESPACE, 'content-marker:' . $mediauuid . ':' . $tagkey . ':' . sha1(uckkarchive_json($advisory)));
                    uckkarchive_insert_record('uckkarchive_content_marker', [
                        'uuid' => $markeruuid,
                        'archiveid' => $archiveid,
                        'uckkarchiveid' => $archiveid,
                        'courseid' => $courseid,
                        'cmid' => $cmid,
                        'contextid' => (int)$context->id,
                        'mediaid' => $mediaid,
                        'mediaversionid' => $versionid,
                        'versionid' => $versionid,
                        'targettype' => 'media',
                        'targetid' => $mediaid,
                        'tagid' => $tagid,
                        'tagkey' => $tagkey,
                        'tag' => $tagkey,
                        'category' => (string)($advisory['category'] ?? 'advisory'),
                        'severity' => (string)($advisory['severity'] ?? 'notice'),
                        'audiencesuitability' => (string)($advisory['audiencesuitability'] ?? $media['audiencesuitability'] ?? 'general'),
                        'reviewstate' => (string)($advisory['reviewstate'] ?? 'pending_review'),
                        'visibility' => (string)($media['visibility'] ?? 'institution'),
                        'locator_type' => (string)($advisory['locator_type'] ?? 'manual_reference'),
                        'locatortype' => (string)($advisory['locator_type'] ?? 'manual_reference'),
                        'locator' => (string)($advisory['locator'] ?? ''),
                        'locatorlabel' => (string)($advisory['locatorlabel'] ?? ''),
                        'advisorytext' => (string)($advisory['advisorytext'] ?? ''),
                        'description' => (string)($advisory['advisorytext'] ?? ''),
                        'note' => (string)($advisory['note'] ?? $advisory['advisorytext'] ?? ''),
                        'markertext' => (string)($advisory['advisorytext'] ?? ''),
                        'status' => 'active',
                        'requirescontext' => (int)(($tagkey === 'requires_context') || !empty($advisory['requirescontext'])),
                        'restricted' => (int)!empty($advisory['restricted']),
                        'culturalprotocol' => (int)!empty($advisory['culturalprotocol']),
                        'createdby' => (int)$user->id,
                        'modifiedby' => (int)$user->id,
                        'userid' => (int)$user->id,
                        'metadata' => uckkarchive_json(['inventory_advisory' => $advisory]),
                        'timecreated' => $now,
                        'timemodified' => $now,
                    ], false);
                }
            }
        }

        if (uckkarchive_table_exists('uckkarchive_media_relation') && is_array($relations) && count($relations) > 0) {
            foreach ($relations as $relation) {
                if (!is_array($relation)) {
                    continue;
                }
                $relationtype = (string)($relation['relationtype'] ?? $relation['type'] ?? 'references');
                $targetuuid = (string)($relation['targetuuid'] ?? $relation['touuid'] ?? '');
                $relationuuid = uckkarchive_uuid_v5(UCKKARCHIVE_IMPORT_UUID_NAMESPACE, 'media-relation:' . $mediauuid . ':' . $relationtype . ':' . $targetuuid);
                uckkarchive_insert_record('uckkarchive_media_relation', [
                    'uuid' => $relationuuid,
                    'uckkarchiveid' => $archiveid,
                    'archiveid' => $archiveid,
                    'courseid' => $courseid,
                    'cmid' => $cmid,
                    'contextid' => (int)$context->id,
                    'mediaid' => $mediaid,
                    'sourcemediaid' => $mediaid,
                    'frommediaid' => $mediaid,
                    'fromuuid' => $mediauuid,
                    'touuid' => $targetuuid,
                    'relationtype' => $relationtype,
                    'targettype' => (string)($relation['targettype'] ?? 'media'),
                    'targetid' => (int)($relation['targetid'] ?? 0),
                    'targetmediaid' => (int)($relation['targetmediaid'] ?? $relation['tomediaid'] ?? 0),
                    'targetuuid' => $targetuuid,
                    'description' => (string)($relation['description'] ?? ''),
                    'status' => 'active',
                    'visibility' => (string)($media['visibility'] ?? 'institution'),
                    'createdby' => (int)$user->id,
                    'modifiedby' => (int)$user->id,
                    'metadata' => uckkarchive_json(['inventory_relation' => $relation]),
                    'timecreated' => $now,
                    'timemodified' => $now,
                ], false);
            }
        }

        $itemreport['status'] = 'imported';
        $itemreport['mediaid'] = $mediaid;
        $itemreport['versionid'] = $versionid;
        $itemreport['fileapi'] = [
            'contextid' => (int)$context->id,
            'component' => $component,
            'filearea' => $filearea,
            'itemid' => $versionid,
            'filename' => $safeproposed,
        ];
        $report['items'][] = $itemreport;
    }

    if (!$dryrun && $transaction) {
        $transaction->allow_commit();
    }
} catch (Throwable $e) {
    if (!$dryrun && $transaction) {
        $transaction->rollback($e);
    }
    $report['summary']['errors']++;
    $report['fatal_error'] = [
        'class' => get_class($e),
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
    ];
    if ($reportpath !== '') {
        file_put_contents($reportpath, uckkarchive_json($report));
    }
    throw $e;
}

if ($reportpath !== '') {
    file_put_contents($reportpath, uckkarchive_json($report));
}

cli_writeln('UCKK archive media import completed.');
cli_writeln('Mode: ' . ($dryrun ? 'dry-run' : 'apply'));
cli_writeln('Listed: ' . $report['summary']['listed']);
cli_writeln('Created media: ' . $report['summary']['created_media']);
cli_writeln('Updated media: ' . $report['summary']['updated_media']);
cli_writeln('Created versions: ' . $report['summary']['created_versions']);
cli_writeln('Skipped existing versions: ' . $report['summary']['skipped_existing_versions']);
cli_writeln('Missing files: ' . $report['summary']['missing_files']);
if ($reportpath !== '') {
    cli_writeln('Report: ' . $reportpath);
}
