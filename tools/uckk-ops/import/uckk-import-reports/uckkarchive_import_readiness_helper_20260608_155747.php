<?php
define('CLI_SCRIPT', true);

function read_arg(string $name, $default = null) {
    global $argv;
    $prefix = '--' . $name . '=';
    foreach ($argv as $arg) {
        if (strpos($arg, $prefix) === 0) {
            return substr($arg, strlen($prefix));
        }
    }
    return $default;
}

function add_issue(array &$result, string $severity, string $stage, string $code, string $message, string $fix = ''): void {
    $result['issues'][] = [
        'severity' => $severity,
        'stage' => $stage,
        'code' => $code,
        'message' => $message,
        'fix' => $fix,
    ];
}

function has_column(array $columns, string $name): bool {
    return array_key_exists($name, $columns);
}

function first_existing_column(array $columns, array $names): ?string {
    foreach ($names as $name) {
        if (array_key_exists($name, $columns)) {
            return $name;
        }
    }
    return null;
}

function table_exists_safe($dbman, string $table): bool {
    if (class_exists('xmldb_table')) {
        return $dbman->table_exists(new xmldb_table($table));
    }
    return $dbman->table_exists($table);
}

function list_columns_safe($DB, string $table): array {
    try {
        $cols = $DB->get_columns($table, true);
        return is_array($cols) ? $cols : [];
    } catch (Throwable $e) {
        return [];
    }
}

function column_summary(array $columns): array {
    $out = [];
    foreach ($columns as $name => $field) {
        $arr = is_object($field) ? get_object_vars($field) : (array)$field;
        $out[] = [
            'name' => $name,
            'type' => $arr['type'] ?? '',
            'max_length' => $arr['max_length'] ?? ($arr['max_length'] ?? ''),
            'not_null' => $arr['not_null'] ?? '',
            'default_value' => $arr['default_value'] ?? '',
            'has_default' => $arr['has_default'] ?? '',
        ];
    }
    return $out;
}

function distinct_values($DB, string $table, array $columns, string $column, int $limit = 50): array {
    if (!array_key_exists($column, $columns)) {
        return [];
    }
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $column)) {
        return [];
    }
    try {
        $sql = "SELECT DISTINCT {$column} AS value FROM {{$table}} WHERE {$column} IS NOT NULL ORDER BY {$column}";
        $records = $DB->get_records_sql($sql, [], 0, $limit);
        $out = [];
        foreach ($records as $record) {
            if ($record->value !== '') {
                $out[] = (string)$record->value;
            }
        }
        return $out;
    } catch (Throwable $e) {
        return [];
    }
}

function file_size_or_null(string $path): ?int {
    return is_file($path) ? filesize($path) : null;
}

function normalise_slashes(string $path): string {
    return str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
}

$moodleroot = read_arg('moodleroot');
$uckkrepo = read_arg('uckkrepo', '');
$inventorypath = read_arg('inventory', '');
$originalsdir = read_arg('originals', '');
$cmid = (int)read_arg('cmid', 0);
$archiveid = (int)read_arg('archiveid', 0);

$result = [
    'generated_at' => date('c'),
    'ok' => false,
    'summary' => [],
    'issues' => [],
    'paths' => [
        'moodle_root' => $moodleroot,
        'uckk_repo' => $uckkrepo,
        'inventory_path' => $inventorypath,
        'originals_dir' => $originalsdir,
    ],
    'moodle' => [],
    'component_presence' => [],
    'table_presence' => [],
    'columns' => [],
    'schema_contract' => [],
    'archive_targets' => [],
    'selected_target' => null,
    'distinct_values' => [],
    'external_functions' => [],
    'inventory' => null,
    'recommended_commands' => [],
];

try {
    if ($moodleroot === null || $moodleroot === '') {
        add_issue($result, 'error', 'config', 'MOODLE_ROOT_EMPTY', 'No --moodleroot argument was provided.');
        echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit(2);
    }

    $config = rtrim($moodleroot, "\\/") . DIRECTORY_SEPARATOR . 'config.php';
    if (!is_file($config)) {
        add_issue($result, 'error', 'config', 'CONFIG_NOT_FOUND', "config.php not found at {$config}.", 'Pass the Moodle root that contains the real config.php.');
        echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit(2);
    }

    chdir($moodleroot);
    require($config);

    global $CFG, $DB;
    if (!isset($DB)) {
        add_issue($result, 'error', 'moodle', 'DB_NOT_AVAILABLE', 'Moodle $DB is not available after requiring config.php.');
        echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit(2);
    }

    $xmldbTablePath = $CFG->libdir . '/ddl/xmldb_table.php';
    if (is_file($xmldbTablePath)) {
        require_once($xmldbTablePath);
    }

    $result['moodle'] = [
        'cfg_dirroot' => $CFG->dirroot ?? null,
        'cfg_dataroot' => $CFG->dataroot ?? null,
        'wwwroot' => $CFG->wwwroot ?? null,
        'release' => $release ?? null,
        'version' => $CFG->version ?? null,
        'dbtype' => $CFG->dbtype ?? null,
        'dbname' => $CFG->dbname ?? null,
        'dbhost' => $CFG->dbhost ?? null,
        'prefix' => $CFG->prefix ?? null,
    ];

    $componentFiles = [
        'mod_uckkarchive_version' => ($CFG->dirroot . '/mod/uckkarchive/version.php'),
        'mod_uckkarchive_lib' => ($CFG->dirroot . '/mod/uckkarchive/lib.php'),
        'mod_uckkarchive_locallib' => ($CFG->dirroot . '/mod/uckkarchive/locallib.php'),
        'local_uckk_mediatheque_page' => ($CFG->dirroot . '/local/uckk/mediatheque.php'),
        'local_uckk_mediatheque_explorer_amd' => ($CFG->dirroot . '/local/uckk/amd/src/mediatheque_explorer.js'),
        'mod_uckkarchive_search_mediatheque_external' => ($CFG->dirroot . '/mod/uckkarchive/classes/external/search_mediatheque.php'),
        'mod_uckkarchive_public_mediatheque_repository' => ($CFG->dirroot . '/mod/uckkarchive/classes/local/public_mediatheque_repository.php'),
        'mod_uckkarchive_public_mediatheque_service' => ($CFG->dirroot . '/mod/uckkarchive/classes/local/public_mediatheque_service.php'),
    ];
    foreach ($componentFiles as $key => $path) {
        $result['component_presence'][] = [
            'key' => $key,
            'path' => $path,
            'exists' => is_file($path),
        ];
    }

    $dbman = $DB->get_manager();
    $expectedTables = [
        'uckkarchive' => ['required' => true, 'purpose' => 'Archive activity instance table.'],
        'uckkarchive_media' => ['required' => true, 'purpose' => 'Media records imported from inventory.'],
        'uckkarchive_media_version' => ['required' => true, 'purpose' => 'File/version records used as File API itemid anchor.'],
        'uckkarchive_media_source' => ['required' => true, 'purpose' => 'Source/provenance metadata.'],
        'uckkarchive_media_tag' => ['required' => true, 'purpose' => 'Inventory tags.'],
        'uckkarchive_content_marker' => ['required' => false, 'purpose' => 'Content advisories when inventory has advisories.'],
        'uckkarchive_content_tag' => ['required' => false, 'purpose' => 'Content advisory taxonomy.'],
        'uckkarchive_content_review' => ['required' => false, 'purpose' => 'Review state for advisories.'],
        'uckkarchive_media_relation' => ['required' => false, 'purpose' => 'Relations if inventory has relations.'],
        'uckkarchive_media_collection' => ['required' => false, 'purpose' => 'Collections/facets.'],
        'uckkarchive_media_collection_item' => ['required' => false, 'purpose' => 'Collection membership.'],
        'uckkarchive_external_work' => ['required' => false, 'purpose' => 'External works referenced by media.'],
    ];

    $columnsByTable = [];
    foreach ($expectedTables as $table => $meta) {
        $exists = table_exists_safe($dbman, $table);
        $count = null;
        $columns = [];
        if ($exists) {
            $columns = list_columns_safe($DB, $table);
            try { $count = $DB->count_records($table); } catch (Throwable $e) { $count = null; }
        }
        $columnsByTable[$table] = $columns;
        $result['table_presence'][] = [
            'table' => $table,
            'required' => $meta['required'],
            'purpose' => $meta['purpose'],
            'exists' => $exists,
            'row_count' => $count,
            'column_count' => count($columns),
        ];
        $result['columns'][$table] = column_summary($columns);
        if ($meta['required'] && !$exists) {
            add_issue($result, 'error', 'schema', 'REQUIRED_TABLE_MISSING', "Required table is missing: {$table}.", 'Install/upgrade mod_uckkarchive before importing.');
        }
    }

    $contract = [
        'uckkarchive_media' => [
            'required' => ['uuid', 'archiveid', 'contextid', 'title', 'mediatype', 'status', 'visibility'],
            'recommended' => ['courseid', 'cmid', 'description', 'mimetype', 'audiencesuitability', 'language', 'retentionclass', 'redactionstate', 'createdby', 'modifiedby', 'timecreated', 'timemodified', 'metadata', 'currentversionid'],
        ],
        'uckkarchive_media_version' => [
            'required' => ['uuid', 'mediaid', 'archiveid', 'contextid', 'versionnumber', 'filename', 'filearea', 'status'],
            'recommended' => ['courseid', 'cmid', 'versionno', 'filepath', 'filesize', 'mimetype', 'contenthash', 'iscurrent', 'visibility', 'createdby', 'modifiedby', 'timecreated', 'timemodified', 'metadata'],
        ],
        'uckkarchive_media_source' => [
            'required' => ['mediaid', 'archiveid', 'contextid', 'sourcetype'],
            'recommended' => ['uuid', 'courseid', 'cmid', 'sourceownership', 'sourceauthor', 'attribution', 'sourcecomponent', 'createdby', 'modifiedby', 'timecreated', 'timemodified', 'metadata'],
        ],
        'uckkarchive_media_tag' => [
            'required' => ['mediaid', 'archiveid', 'contextid'],
            'recommended' => ['uuid', 'courseid', 'cmid', 'tagkey', 'tag', 'tagtype', 'status', 'visibility', 'createdby', 'modifiedby', 'timecreated', 'timemodified', 'metadata'],
        ],
        'uckkarchive_content_marker' => [
            'required' => ['archiveid', 'contextid', 'targettype', 'targetid', 'tagkey', 'severity'],
            'recommended' => ['uuid', 'courseid', 'cmid', 'mediaid', 'tag', 'description', 'note', 'visibility', 'reviewstate', 'locatortype', 'locator', 'createdby', 'modifiedby', 'timecreated', 'timemodified', 'metadata'],
        ],
        'uckkarchive_media_relation' => [
            'required' => ['archiveid', 'contextid', 'mediaid', 'relationtype'],
            'recommended' => ['uuid', 'courseid', 'cmid', 'targettype', 'targetid', 'targetmediaid', 'description', 'status', 'visibility', 'createdby', 'modifiedby', 'timecreated', 'timemodified', 'metadata'],
        ],
    ];

    foreach ($contract as $table => $rules) {
        $columns = $columnsByTable[$table] ?? [];
        $exists = count($columns) > 0;
        $missingRequired = [];
        $missingRecommended = [];
        foreach ($rules['required'] as $column) {
            if (!has_column($columns, $column)) { $missingRequired[] = $column; }
        }
        foreach ($rules['recommended'] as $column) {
            if (!has_column($columns, $column)) { $missingRecommended[] = $column; }
        }
        $row = [
            'table' => $table,
            'exists' => $exists,
            'required_ok' => $exists && count($missingRequired) === 0,
            'missing_required' => implode(', ', $missingRequired),
            'missing_recommended' => implode(', ', $missingRecommended),
        ];
        $result['schema_contract'][] = $row;
        if ($exists && count($missingRequired) > 0) {
            add_issue($result, 'error', 'schema_contract', 'IMPORT_REQUIRED_COLUMNS_MISSING', "{$table} is missing required import columns: " . implode(', ', $missingRequired) . '.', 'Upgrade the plugin schema or adapt importer mappings.');
        }
    }

    $archiveTargets = [];
    try {
        $contextlevel = defined('CONTEXT_MODULE') ? CONTEXT_MODULE : 70;
        $sql = "SELECT cm.id AS cmid,
                       a.id AS archiveid,
                       c.id AS courseid,
                       c.fullname AS coursename,
                       c.shortname AS courseshortname,
                       a.name AS archivename,
                       cm.visible AS cmvisible,
                       c.visible AS coursevisible,
                       ctx.id AS contextid
                  FROM {course_modules} cm
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                  JOIN {uckkarchive} a ON a.id = cm.instance
                  JOIN {course} c ON c.id = cm.course
             LEFT JOIN {context} ctx ON ctx.contextlevel = :contextlevel AND ctx.instanceid = cm.id
              ORDER BY c.fullname ASC, a.name ASC, cm.id ASC";
        $records = $DB->get_records_sql($sql, ['modname' => 'uckkarchive', 'contextlevel' => $contextlevel]);
        foreach ($records as $record) {
            $archiveTargets[] = [
                'cmid' => (int)$record->cmid,
                'archiveid' => (int)$record->archiveid,
                'courseid' => (int)$record->courseid,
                'contextid' => $record->contextid !== null ? (int)$record->contextid : null,
                'coursevisible' => (int)$record->coursevisible,
                'cmvisible' => (int)$record->cmvisible,
                'courseshortname' => (string)$record->courseshortname,
                'coursename' => (string)$record->coursename,
                'archivename' => (string)$record->archivename,
            ];
        }
    } catch (Throwable $e) {
        add_issue($result, 'error', 'archive_targets', 'ARCHIVE_TARGET_QUERY_FAILED', $e->getMessage(), 'Confirm mod_uckkarchive is installed and upgraded.');
    }
    $result['archive_targets'] = $archiveTargets;
    if (count($archiveTargets) === 0) {
        add_issue($result, 'error', 'archive_targets', 'NO_ARCHIVE_TARGETS_FOUND', 'No mod_uckkarchive course module instances were found.', 'Create at least one UCKK Archive activity in Moodle before importing media.');
    }

    if ($cmid > 0) {
        foreach ($archiveTargets as $target) {
            if ((int)$target['cmid'] === $cmid) {
                $result['selected_target'] = $target;
                break;
            }
        }
        if ($result['selected_target'] === null) {
            add_issue($result, 'error', 'archive_targets', 'CMID_NOT_FOUND', "CMID {$cmid} is not a valid mod_uckkarchive course module.", 'Use one of the CMID values listed in archive_targets.');
        }
    }

    if ($archiveid > 0) {
        $matches = array_values(array_filter($archiveTargets, function($target) use ($archiveid) {
            return (int)$target['archiveid'] === $archiveid;
        }));
        if (count($matches) === 0) {
            add_issue($result, 'error', 'archive_targets', 'ARCHIVEID_NOT_FOUND', "ArchiveID {$archiveid} was not found in mod_uckkarchive instances.", 'Use one of the ArchiveID values listed in archive_targets.');
        }
    }

    $distinctChecks = [
        'uckkarchive_media' => ['mediatype', 'status', 'visibility', 'audiencesuitability', 'language', 'retentionclass', 'redactionstate'],
        'uckkarchive_media_version' => ['filearea', 'status', 'visibility', 'mimetype'],
        'uckkarchive_media_source' => ['sourcetype', 'sourceownership'],
        'uckkarchive_media_tag' => ['tagkey', 'tagtype', 'status', 'visibility'],
        'uckkarchive_content_marker' => ['tagkey', 'severity', 'visibility', 'reviewstate', 'locatortype'],
    ];
    foreach ($distinctChecks as $table => $columns) {
        $result['distinct_values'][$table] = [];
        foreach ($columns as $column) {
            $result['distinct_values'][$table][$column] = distinct_values($DB, $table, $columnsByTable[$table] ?? [], $column, 50);
        }
    }

    foreach (['mod_uckkarchive_search_mediatheque', 'mod_uckkarchive_get_media', 'mod_uckkarchive_get_media_versions'] as $fname) {
        $exists = false;
        $enabled = null;
        try {
            if (table_exists_safe($dbman, 'external_functions')) {
                $rec = $DB->get_record('external_functions', ['name' => $fname], '*', IGNORE_MISSING);
                if ($rec) {
                    $exists = true;
                    $enabled = property_exists($rec, 'enabled') ? (int)$rec->enabled : null;
                }
            }
        } catch (Throwable $e) {
            add_issue($result, 'warning', 'services', 'EXTERNAL_FUNCTION_CHECK_FAILED', "Could not check external function {$fname}: " . $e->getMessage());
        }
        $result['external_functions'][] = [
            'name' => $fname,
            'declared_in_db' => $exists,
            'enabled' => $enabled,
        ];
    }

    $inventory = [
        'path' => $inventorypath,
        'exists' => is_file($inventorypath),
        'parse_ok' => false,
        'file_count' => 0,
        'missing_files' => [],
        'duplicates' => [],
        'extension_counts' => [],
        'mimetype_counts' => [],
        'action_counts' => [],
        'sample_files' => [],
    ];
    if (is_file($inventorypath)) {
        $raw = file_get_contents($inventorypath);
        $decoded = json_decode($raw, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            add_issue($result, 'error', 'inventory', 'INVENTORY_JSON_INVALID', json_last_error_msg(), 'Fix the JSON before importing.');
        } else {
            $inventory['parse_ok'] = true;
            $files = [];
            if (isset($decoded['files']) && is_array($decoded['files'])) {
                $files = $decoded['files'];
            } elseif (is_array($decoded)) {
                $files = $decoded;
            }
            $inventory['file_count'] = count($files);
            $seenProposed = [];
            $seenOriginal = [];
            $supportedExts = ['docx' => true, 'pdf' => true];
            $i = 0;
            foreach ($files as $entry) {
                $i++;
                $ops = $entry['file_operations'] ?? [];
                $original = (string)($ops['original_filename'] ?? '');
                $proposed = (string)($ops['proposed_filename'] ?? '');
                $mimetype = (string)($ops['mimetype'] ?? '');
                $action = (string)($ops['action'] ?? '');
                $filenameForExt = $proposed !== '' ? $proposed : $original;
                $ext = strtolower(pathinfo($filenameForExt, PATHINFO_EXTENSION));
                if ($ext === '') { $ext = '(none)'; }
                $inventory['extension_counts'][$ext] = ($inventory['extension_counts'][$ext] ?? 0) + 1;
                $inventory['mimetype_counts'][$mimetype] = ($inventory['mimetype_counts'][$mimetype] ?? 0) + 1;
                $inventory['action_counts'][$action] = ($inventory['action_counts'][$action] ?? 0) + 1;
                if ($proposed !== '') { $seenProposed[$proposed] = ($seenProposed[$proposed] ?? 0) + 1; }
                if ($original !== '') { $seenOriginal[$original] = ($seenOriginal[$original] ?? 0) + 1; }

                $candidates = [];
                if ($original !== '') { $candidates[] = rtrim($originalsdir, "\\/") . DIRECTORY_SEPARATOR . normalise_slashes($original); }
                if ($proposed !== '') { $candidates[] = rtrim($originalsdir, "\\/") . DIRECTORY_SEPARATOR . normalise_slashes($proposed); }
                $found = false;
                $foundPath = null;
                foreach (array_unique($candidates) as $candidate) {
                    if (is_file($candidate)) {
                        $found = true;
                        $foundPath = $candidate;
                        break;
                    }
                }
                if (!$found) {
                    $inventory['missing_files'][] = [
                        'index' => $i,
                        'original_filename' => $original,
                        'proposed_filename' => $proposed,
                    ];
                }
                if (!isset($supportedExts[$ext])) {
                    add_issue($result, 'warning', 'inventory', 'UNSUPPORTED_EXTENSION', "Inventory item {$i} has extension '{$ext}'.", 'Default import supports .docx and .pdf.');
                }
                if (count($inventory['sample_files']) < 10) {
                    $inventory['sample_files'][] = [
                        'index' => $i,
                        'original_filename' => $original,
                        'proposed_filename' => $proposed,
                        'mimetype' => $mimetype,
                        'exists' => $found,
                        'path' => $foundPath,
                        'size' => $foundPath ? file_size_or_null($foundPath) : null,
                    ];
                }
            }
            foreach ($seenProposed as $name => $count) {
                if ($count > 1) { $inventory['duplicates'][] = ['field' => 'proposed_filename', 'value' => $name, 'count' => $count]; }
            }
            foreach ($seenOriginal as $name => $count) {
                if ($count > 1) { $inventory['duplicates'][] = ['field' => 'original_filename', 'value' => $name, 'count' => $count]; }
            }
            if (count($inventory['missing_files']) > 0) {
                add_issue($result, 'error', 'inventory', 'ORIGINAL_FILES_MISSING', count($inventory['missing_files']) . ' inventory files are missing from originals_dir.', 'Put the files in OriginalsDir or fix the filenames in inventory.');
            }
            if (count($inventory['duplicates']) > 0) {
                add_issue($result, 'warning', 'inventory', 'DUPLICATE_FILENAMES', 'Duplicate filenames were found in the inventory.', 'Review duplicates before applying import.');
            }
        }
    } else {
        add_issue($result, 'error', 'inventory', 'INVENTORY_NOT_FOUND', "Inventory file not found: {$inventorypath}", 'Pass -InventoryPath with the real uckk_inventory.json path.');
    }
    $result['inventory'] = $inventory;

    $bat = rtrim($uckkrepo, "\\/") . DIRECTORY_SEPARATOR . 'tools' . DIRECTORY_SEPARATOR . 'uckk-ops' . DIRECTORY_SEPARATOR . 'import' . DIRECTORY_SEPARATOR . 'Run-UckkArchiveImport.bat';
    if (count($archiveTargets) > 0) {
        foreach (array_slice($archiveTargets, 0, 10) as $target) {
            $result['recommended_commands'][] = [
                'purpose' => 'dryrun',
                'command' => 'Run-UckkArchiveImport.bat dryrun ' . $target['cmid'] . ' "' . $moodleroot . '"',
                'target' => $target['coursename'] . ' / ' . $target['archivename'],
            ];
        }
    }
    if ($result['selected_target'] !== null) {
        $result['recommended_commands'][] = [
            'purpose' => 'apply_selected_target',
            'command' => 'Run-UckkArchiveImport.bat apply ' . $result['selected_target']['cmid'] . ' "' . $moodleroot . '"',
            'target' => $result['selected_target']['coursename'] . ' / ' . $result['selected_target']['archivename'],
        ];
    }

    $errorCount = 0;
    $warningCount = 0;
    foreach ($result['issues'] as $issue) {
        if (($issue['severity'] ?? '') === 'error') { $errorCount++; }
        if (($issue['severity'] ?? '') === 'warning') { $warningCount++; }
    }
    $result['summary'] = [
        'error_count' => $errorCount,
        'warning_count' => $warningCount,
        'archive_target_count' => count($archiveTargets),
        'inventory_file_count' => $inventory['file_count'] ?? 0,
        'inventory_missing_file_count' => isset($inventory['missing_files']) ? count($inventory['missing_files']) : 0,
        'selected_cmid' => $cmid,
        'selected_archiveid' => $archiveid,
        'ready_for_dryrun' => ($errorCount === 0 && count($archiveTargets) > 0),
        'ready_for_apply' => ($errorCount === 0 && $cmid > 0 && $result['selected_target'] !== null),
    ];
    $result['ok'] = ($errorCount === 0);

} catch (Throwable $e) {
    add_issue($result, 'error', 'fatal', 'PHP_FATAL', $e->getMessage(), 'Review PHP stdout/stderr and Moodle config.');
}

echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
