<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

/**
 * CLI Atlas to Moodle sync command for UCKK faculty pathways.
 *
 * This command is read-only by default. It creates or updates Moodle records
 * only when called with --apply --force.
 *
 * @package    local_uckk
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

define('CLI_SCRIPT', true);

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');
require_once($CFG->dirroot . '/course/lib.php');

use core_course_category;
use context_system;

const LOCAL_UCKK_CLI_SYNC_ATLAS_COMPONENT = 'local_uckk';
const LOCAL_UCKK_CLI_SYNC_ATLAS_ACTION = 'sync_atlas';

const LOCAL_UCKK_CLI_SYNC_ATLAS_CAPABILITY = 'local/uckk:syncatlasmoodle';

const LOCAL_UCKK_CLI_SYNC_ATLAS_MODE_DRY_RUN = 'dry_run';
const LOCAL_UCKK_CLI_SYNC_ATLAS_MODE_APPLY = 'apply';

const LOCAL_UCKK_CLI_SYNC_ATLAS_STATUS_COMPLETED = 'completed';
const LOCAL_UCKK_CLI_SYNC_ATLAS_STATUS_WARNING = 'warning';
const LOCAL_UCKK_CLI_SYNC_ATLAS_STATUS_FAILED = 'failed';

const LOCAL_UCKK_CLI_SYNC_ATLAS_SEVERITY_INFO = 'info';
const LOCAL_UCKK_CLI_SYNC_ATLAS_SEVERITY_SUCCESS = 'success';
const LOCAL_UCKK_CLI_SYNC_ATLAS_SEVERITY_WARNING = 'warning';
const LOCAL_UCKK_CLI_SYNC_ATLAS_SEVERITY_ERROR = 'error';
const LOCAL_UCKK_CLI_SYNC_ATLAS_SEVERITY_BLOCKER = 'blocker';

const LOCAL_UCKK_CLI_SYNC_ATLAS_EXIT_OK = 0;
const LOCAL_UCKK_CLI_SYNC_ATLAS_EXIT_ERROR = 1;

const LOCAL_UCKK_CLI_SYNC_ATLAS_EXPECTED_VOIE_COUNT = 10;
const LOCAL_UCKK_CLI_SYNC_ATLAS_EXPECTED_COURSE_COUNT = 10;

const LOCAL_UCKK_CLI_SYNC_ATLAS_SCHEMA_VERSION = 'UCKK-ATLAS-0.2-draft';
const LOCAL_UCKK_CLI_SYNC_ATLAS_MANIFEST_SCHEMA_VERSION = 'UCKK-ATLAS-MANIFEST-0.1';

const LOCAL_UCKK_CLI_SYNC_ATLAS_MANIFEST_FILE = 'atlas/atlas_manifest.json';
const LOCAL_UCKK_CLI_SYNC_ATLAS_VOIE_DIR = 'atlas/voies';

const LOCAL_UCKK_CLI_SYNC_ATLAS_DEFAULT_COURSE_FORMAT = 'uckk';

const LOCAL_UCKK_CLI_SYNC_ATLAS_SYNC_NOT_SYNCED = 'not_synced';
const LOCAL_UCKK_CLI_SYNC_ATLAS_SYNC_IN_SYNC = 'in_sync';
const LOCAL_UCKK_CLI_SYNC_ATLAS_SYNC_CHANGED_IN_JSON = 'changed_in_json';
const LOCAL_UCKK_CLI_SYNC_ATLAS_SYNC_CHANGED_IN_MOODLE = 'changed_in_moodle';
const LOCAL_UCKK_CLI_SYNC_ATLAS_SYNC_CONFLICT = 'conflict';
const LOCAL_UCKK_CLI_SYNC_ATLAS_SYNC_MISSING_IN_MOODLE = 'missing_in_moodle';
const LOCAL_UCKK_CLI_SYNC_ATLAS_SYNC_MISSING_IN_JSON = 'missing_in_json';
const LOCAL_UCKK_CLI_SYNC_ATLAS_SYNC_ERROR = 'sync_error';

const LOCAL_UCKK_CLI_SYNC_ATLAS_MARKER_RE = '/<!--\s*local_uckk_atlas_sync:source=([a-f0-9]{64});body=([a-f0-9]{64})\s*-->/';

const LOCAL_UCKK_CLI_SYNC_ATLAS_CUSTOM_FIELDS = [
    'uckk_voie_id',
    'uckk_domaine_operatoire',
    'uckk_niveau_vise',
    'uckk_titre_symbolique',
];

/**
 * Render CLI help.
 *
 * @return string
 */
function local_uckk_cli_sync_atlas_help(): string {
    return <<<HELP
Synchronise UCKK Atlas JSON pathways with Moodle categories and course shells.

Default mode is dry-run. No Moodle record is created or updated unless both
--apply and --force are present.

This command reads:
  local/uckk/atlas/atlas_manifest.json
  local/uckk/atlas/voies/voie_*.json

Supported write operations in --apply mode:
  - create missing Moodle course categories from Atlas manifest items
  - update managed Moodle course category name/description
  - create missing Moodle course shells from cours_conceptuels
  - update managed Moodle course shell fullname/summary/category/visibility/format

Reported but not created by this command:
  - missing Moodle custom fields
  - missing Parchemin badges

Conflict policy:
  If both the Atlas JSON changed and Moodle content changed since the last
  managed sync marker, the item is reported as conflict and is not overwritten
  unless --allow-conflicts is explicitly provided.

Usage:
  php local/uckk/cli/sync_atlas.php [options]

Options:
  --dry-run                    Force dry-run mode. This is the default.
  --apply                      Apply supported safe changes. Requires --force.
  --force                      Required with --apply.
  --allow-conflicts            Allow apply to overwrite conflict items.
  --voie=<id|code|file>        Limit sync to one voie_id, code, or atlas file.
  --parent-category=<idnumber> Parent category idnumber for created categories.
                               Empty value means Moodle top level.
  --course-format=<format>     Course format for created/updated courses.
                               Default: uckk
  --hidden                     Create/update synced categories and courses hidden.
                               Default is visible.
  --json                       Print machine-readable JSON.
  --strict                     Return exit code 1 when warnings are present.
  --quiet                      Print only final status unless --json is used.
  -h, --help                   Print this help.

Examples:
  php local/uckk/cli/sync_atlas.php
  php local/uckk/cli/sync_atlas.php --json
  php local/uckk/cli/sync_atlas.php --voie=GJS
  php local/uckk/cli/sync_atlas.php --apply --force
  php local/uckk/cli/sync_atlas.php --apply --force --allow-conflicts

HELP;
}

/**
 * Create base result payload.
 *
 * @param string $mode Execution mode.
 * @return array<string, mixed>
 */
function local_uckk_cli_sync_atlas_new_result(string $mode): array {
    return [
        'ok' => false,
        'component' => LOCAL_UCKK_CLI_SYNC_ATLAS_COMPONENT,
        'action' => LOCAL_UCKK_CLI_SYNC_ATLAS_ACTION,
        'mode' => $mode,
        'status' => LOCAL_UCKK_CLI_SYNC_ATLAS_STATUS_FAILED,
        'summary' => '',
        'hash' => '',
        'counts' => [
            'manifest_items' => 0,
            'voies_checked' => 0,
            'courses_checked' => 0,
            'categories_to_create' => 0,
            'categories_to_update' => 0,
            'categories_created' => 0,
            'categories_updated' => 0,
            'courses_to_create' => 0,
            'courses_to_update' => 0,
            'courses_created' => 0,
            'courses_updated' => 0,
            'custom_fields_missing' => 0,
            'badges_to_create' => 0,
            'conflicts' => 0,
            'errors' => 0,
            'warnings' => 0,
        ],
        'items' => [],
        'messages' => [],
    ];
}

/**
 * Add a report message.
 *
 * @param array<string, mixed> $result Result payload.
 * @param string $severity Severity.
 * @param string $message Message.
 * @param string $target Target.
 * @param array<string, mixed> $metadata Safe metadata.
 */
function local_uckk_cli_sync_atlas_add_message(
    array &$result,
    string $severity,
    string $message,
    string $target = '',
    array $metadata = []
): void {
    $result['messages'][] = [
        'severity' => $severity,
        'target' => $target,
        'message' => $message,
        'metadata' => $metadata,
    ];

    if ($severity === LOCAL_UCKK_CLI_SYNC_ATLAS_SEVERITY_ERROR ||
            $severity === LOCAL_UCKK_CLI_SYNC_ATLAS_SEVERITY_BLOCKER) {
        $result['counts']['errors']++;
    } else if ($severity === LOCAL_UCKK_CLI_SYNC_ATLAS_SEVERITY_WARNING) {
        $result['counts']['warnings']++;
    }
}

/**
 * Finalise result.
 *
 * @param array<string, mixed> $result Result payload.
 * @return array<string, mixed>
 */
function local_uckk_cli_sync_atlas_finalise(array $result): array {
    $result['hash'] = hash('sha256', json_encode([
        'component' => $result['component'],
        'action' => $result['action'],
        'mode' => $result['mode'],
        'counts' => $result['counts'],
        'items' => array_map(static function(array $item): array {
            return [
                'type' => $item['type'] ?? '',
                'key' => $item['key'] ?? '',
                'status' => $item['status'] ?? '',
                'operation' => $item['operation'] ?? '',
                'source_hash' => $item['source_hash'] ?? '',
            ];
        }, $result['items']),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

    if ($result['counts']['errors'] > 0) {
        $result['ok'] = false;
        $result['status'] = LOCAL_UCKK_CLI_SYNC_ATLAS_STATUS_FAILED;
        $result['summary'] = 'UCKK Atlas sync failed.';
    } else if ($result['counts']['warnings'] > 0 || $result['counts']['conflicts'] > 0) {
        $result['ok'] = true;
        $result['status'] = LOCAL_UCKK_CLI_SYNC_ATLAS_STATUS_WARNING;
        $result['summary'] = 'UCKK Atlas sync completed with warnings.';
    } else {
        $result['ok'] = true;
        $result['status'] = LOCAL_UCKK_CLI_SYNC_ATLAS_STATUS_COMPLETED;
        $result['summary'] = 'UCKK Atlas sync completed successfully.';
    }

    return $result;
}

/**
 * Join Moodle-root relative path.
 *
 * @param string $base Base path.
 * @param string $relative Slash-separated path.
 * @return string
 */
function local_uckk_cli_sync_atlas_path(string $base, string $relative): string {
    return rtrim($base, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);
}

/**
 * Validate local filename.
 *
 * @param string $filename Filename.
 * @return bool
 */
function local_uckk_cli_sync_atlas_safe_json_filename(string $filename): bool {
    if ($filename === '' || strpos($filename, "\0") !== false) {
        return false;
    }

    if (basename($filename) !== $filename) {
        return false;
    }

    if (strpos($filename, '/') !== false || strpos($filename, '\\') !== false) {
        return false;
    }

    return substr($filename, -5) === '.json';
}

/**
 * Decode JSON file.
 *
 * @param string $path Absolute file path.
 * @param string $target Target.
 * @param array<string, mixed> $result Result payload.
 * @return array<string, mixed>|null
 */
function local_uckk_cli_sync_atlas_decode_json_file(
    string $path,
    string $target,
    array &$result
): ?array {
    if (!is_file($path)) {
        local_uckk_cli_sync_atlas_add_message(
            $result,
            LOCAL_UCKK_CLI_SYNC_ATLAS_SEVERITY_ERROR,
            'Required JSON file does not exist.',
            $target
        );
        return null;
    }

    if (!is_readable($path)) {
        local_uckk_cli_sync_atlas_add_message(
            $result,
            LOCAL_UCKK_CLI_SYNC_ATLAS_SEVERITY_ERROR,
            'Required JSON file is not readable.',
            $target
        );
        return null;
    }

    $raw = file_get_contents($path);
    if ($raw === false) {
        local_uckk_cli_sync_atlas_add_message(
            $result,
            LOCAL_UCKK_CLI_SYNC_ATLAS_SEVERITY_ERROR,
            'Unable to read JSON file.',
            $target
        );
        return null;
    }

    try {
        $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
    } catch (JsonException $exception) {
        local_uckk_cli_sync_atlas_add_message(
            $result,
            LOCAL_UCKK_CLI_SYNC_ATLAS_SEVERITY_ERROR,
            'Invalid JSON syntax: ' . $exception->getMessage(),
            $target
        );
        return null;
    }

    if (!is_array($decoded)) {
        local_uckk_cli_sync_atlas_add_message(
            $result,
            LOCAL_UCKK_CLI_SYNC_ATLAS_SEVERITY_ERROR,
            'JSON root must be an object.',
            $target
        );
        return null;
    }

    return $decoded;
}

/**
 * Determine whether a value is a non-empty string.
 *
 * @param mixed $value Value.
 * @return bool
 */
function local_uckk_cli_sync_atlas_non_empty_string($value): bool {
    return is_string($value) && trim($value) !== '';
}

/**
 * Validate Atlas manifest.
 *
 * @param array<string, mixed> $manifest Manifest.
 * @param array<string, mixed> $result Result.
 * @return array<int, array<string, mixed>>
 */
function local_uckk_cli_sync_atlas_validate_manifest(array $manifest, array &$result): array {
    $target = LOCAL_UCKK_CLI_SYNC_ATLAS_MANIFEST_FILE;

    if (($manifest['schema_version'] ?? null) !== LOCAL_UCKK_CLI_SYNC_ATLAS_MANIFEST_SCHEMA_VERSION) {
        local_uckk_cli_sync_atlas_add_message(
            $result,
            LOCAL_UCKK_CLI_SYNC_ATLAS_SEVERITY_ERROR,
            'atlas_manifest.json has invalid schema_version.',
            $target,
            ['expected' => LOCAL_UCKK_CLI_SYNC_ATLAS_MANIFEST_SCHEMA_VERSION]
        );
    }

    if (($manifest['atlas_schema_version'] ?? null) !== LOCAL_UCKK_CLI_SYNC_ATLAS_SCHEMA_VERSION) {
        local_uckk_cli_sync_atlas_add_message(
            $result,
            LOCAL_UCKK_CLI_SYNC_ATLAS_SEVERITY_ERROR,
            'atlas_manifest.json has invalid atlas_schema_version.',
            $target,
            ['expected' => LOCAL_UCKK_CLI_SYNC_ATLAS_SCHEMA_VERSION]
        );
    }

    if (empty($manifest['items']) || !is_array($manifest['items'])) {
        local_uckk_cli_sync_atlas_add_message(
            $result,
            LOCAL_UCKK_CLI_SYNC_ATLAS_SEVERITY_ERROR,
            'atlas_manifest.json must contain a non-empty items array.',
            $target
        );
        return [];
    }

    $items = $manifest['items'];
    $result['counts']['manifest_items'] = count($items);

    if (count($items) !== LOCAL_UCKK_CLI_SYNC_ATLAS_EXPECTED_VOIE_COUNT) {
        local_uckk_cli_sync_atlas_add_message(
            $result,
            LOCAL_UCKK_CLI_SYNC_ATLAS_SEVERITY_ERROR,
            'atlas_manifest.json must list exactly 10 Atlas voies.',
            $target,
            ['count' => count($items)]
        );
    }

    $seen = [
        'voie_id' => [],
        'code' => [],
        'file' => [],
        'course_prefix' => [],
        'category_idnumber' => [],
        'sortorder' => [],
    ];

    $validitems = [];

    foreach ($items as $index => $item) {
        $itemtarget = $target . ':items[' . $index . ']';

        if (!is_array($item)) {
            local_uckk_cli_sync_atlas_add_message(
                $result,
                LOCAL_UCKK_CLI_SYNC_ATLAS_SEVERITY_ERROR,
                'Atlas manifest item must be an object.',
                $itemtarget
            );
            continue;
        }

        foreach (['voie_id', 'code', 'nom', 'file', 'course_prefix', 'category_idnumber'] as $field) {
            if (!local_uckk_cli_sync_atlas_non_empty_string($item[$field] ?? null)) {
                local_uckk_cli_sync_atlas_add_message(
                    $result,
                    LOCAL_UCKK_CLI_SYNC_ATLAS_SEVERITY_ERROR,
                    'Atlas manifest item is missing required string field: ' . $field,
                    $itemtarget
                );
            }
        }

        if (!isset($item['sortorder']) || !is_int($item['sortorder'])) {
            local_uckk_cli_sync_atlas_add_message(
                $result,
                LOCAL_UCKK_CLI_SYNC_ATLAS_SEVERITY_ERROR,
                'Atlas manifest item sortorder must be an integer.',
                $itemtarget
            );
        }

        if (!empty($item['file']) && !local_uckk_cli_sync_atlas_safe_json_filename((string)$item['file'])) {
            local_uckk_cli_sync_atlas_add_message(
                $result,
                LOCAL_UCKK_CLI_SYNC_ATLAS_SEVERITY_ERROR,
                'Atlas manifest file must be a local JSON filename.',
                $itemtarget,
                ['file' => (string)$item['file']]
            );
        }

        foreach ($seen as $field => $values) {
            if (!array_key_exists($field, $item) || !is_scalar($item[$field])) {
                continue;
            }

            $value = (string)$item[$field];
            if ($value === '') {
                continue;
            }

            if (array_key_exists($value, $seen[$field])) {
                local_uckk_cli_sync_atlas_add_message(
                    $result,
                    LOCAL_UCKK_CLI_SYNC_ATLAS_SEVERITY_ERROR,
                    'Atlas manifest field must be unique: ' . $field,
                    $itemtarget,
                    ['value' => $value]
                );
            } else {
                $seen[$field][$value] = true;
            }
        }

        $validitems[] = $item;
    }

    return $validitems;
}

/**
 * Filter manifest items by --voie value.
 *
 * @param array<int, array<string, mixed>> $items Items.
 * @param string $filter Filter.
 * @param array<string, mixed> $result Result.
 * @return array<int, array<string, mixed>>
 */
function local_uckk_cli_sync_atlas_filter_items(array $items, string $filter, array &$result): array {
    if ($filter === '') {
        return $items;
    }

    $matched = [];

    foreach ($items as $item) {
        if (
            strcasecmp((string)($item['voie_id'] ?? ''), $filter) === 0 ||
            strcasecmp((string)($item['code'] ?? ''), $filter) === 0 ||
            strcasecmp((string)($item['course_prefix'] ?? ''), $filter) === 0 ||
            strcasecmp((string)($item['file'] ?? ''), $filter) === 0
        ) {
            $matched[] = $item;
        }
    }

    if (empty($matched)) {
        local_uckk_cli_sync_atlas_add_message(
            $result,
            LOCAL_UCKK_CLI_SYNC_ATLAS_SEVERITY_ERROR,
            'No Atlas manifest item matches --voie.',
            LOCAL_UCKK_CLI_SYNC_ATLAS_MANIFEST_FILE,
            ['voie' => $filter]
        );
    }

    return $matched;
}

/**
 * Validate one Atlas voie file.
 *
 * @param array<string, mixed> $voie Atlas data.
 * @param array<string, mixed> $manifestitem Manifest item.
 * @param array<string, mixed> $result Result.
 * @param string $target Target.
 * @return bool
 */
function local_uckk_cli_sync_atlas_validate_voie(
    array $voie,
    array $manifestitem,
    array &$result,
    string $target
): bool {
    $ok = true;

    foreach ([
        'schema_version',
        'voie_id',
        'code',
        'nom',
        'domaine_operatoire',
        'niveau_vise',
        'titre_symbolique',
        'parchemin',
        'statut',
        'definition_courte',
        'angle_fondamental',
        'competence_centrale',
    ] as $field) {
        if (!local_uckk_cli_sync_atlas_non_empty_string($voie[$field] ?? null)) {
            local_uckk_cli_sync_atlas_add_message(
                $result,
                LOCAL_UCKK_CLI_SYNC_ATLAS_SEVERITY_ERROR,
                'Atlas voie is missing required string field: ' . $field,
                $target
            );
            $ok = false;
        }
    }

    foreach ([
        'seuils_progression',
        'cours_conceptuels',
        'limites_ethiques',
        'relations_intervoies',
        'tags',
    ] as $field) {
        if (!array_key_exists($field, $voie) || !is_array($voie[$field])) {
            local_uckk_cli_sync_atlas_add_message(
                $result,
                LOCAL_UCKK_CLI_SYNC_ATLAS_SEVERITY_ERROR,
                'Atlas voie field must be an array: ' . $field,
                $target
            );
            $ok = false;
        }
    }

    if (!array_key_exists('projet_final', $voie) || !is_array($voie['projet_final'])) {
        local_uckk_cli_sync_atlas_add_message(
            $result,
            LOCAL_UCKK_CLI_SYNC_ATLAS_SEVERITY_ERROR,
            'Atlas voie projet_final must be an object.',
            $target
        );
        $ok = false;
    }

    if (($voie['schema_version'] ?? null) !== LOCAL_UCKK_CLI_SYNC_ATLAS_SCHEMA_VERSION) {
        local_uckk_cli_sync_atlas_add_message(
            $result,
            LOCAL_UCKK_CLI_SYNC_ATLAS_SEVERITY_ERROR,
            'Atlas voie has invalid schema_version.',
            $target,
            ['expected' => LOCAL_UCKK_CLI_SYNC_ATLAS_SCHEMA_VERSION]
        );
        $ok = false;
    }

    foreach (['voie_id', 'code', 'course_prefix'] as $field) {
        $voiefield = $field === 'course_prefix' ? 'code' : $field;
        if (($voie[$voiefield] ?? null) !== ($manifestitem[$field] ?? null)) {
            local_uckk_cli_sync_atlas_add_message(
                $result,
                LOCAL_UCKK_CLI_SYNC_ATLAS_SEVERITY_ERROR,
                'Atlas voie field does not match atlas_manifest.json: ' . $voiefield,
                $target
            );
            $ok = false;
        }
    }

    if (!empty($voie['cours_conceptuels']) && is_array($voie['cours_conceptuels'])) {
        if (count($voie['cours_conceptuels']) !== LOCAL_UCKK_CLI_SYNC_ATLAS_EXPECTED_COURSE_COUNT) {
            local_uckk_cli_sync_atlas_add_message(
                $result,
                LOCAL_UCKK_CLI_SYNC_ATLAS_SEVERITY_ERROR,
                'Each Atlas voie must contain exactly 10 cours_conceptuels.',
                $target,
                ['count' => count($voie['cours_conceptuels'])]
            );
            $ok = false;
        }

        $seenorders = [];
        $seencourseids = [];

        foreach ($voie['cours_conceptuels'] as $index => $course) {
            $coursetarget = $target . ':cours_conceptuels[' . $index . ']';

            if (!is_array($course)) {
                local_uckk_cli_sync_atlas_add_message(
                    $result,
                    LOCAL_UCKK_CLI_SYNC_ATLAS_SEVERITY_ERROR,
                    'Cours conceptuel must be an object.',
                    $coursetarget
                );
                $ok = false;
                continue;
            }

            foreach (['cours_id', 'nom'] as $field) {
                if (!local_uckk_cli_sync_atlas_non_empty_string($course[$field] ?? null)) {
                    local_uckk_cli_sync_atlas_add_message(
                        $result,
                        LOCAL_UCKK_CLI_SYNC_ATLAS_SEVERITY_ERROR,
                        'Cours conceptuel is missing required string field: ' . $field,
                        $coursetarget
                    );
                    $ok = false;
                }
            }

            if (!isset($course['ordre']) || !is_int($course['ordre'])) {
                local_uckk_cli_sync_atlas_add_message(
                    $result,
                    LOCAL_UCKK_CLI_SYNC_ATLAS_SEVERITY_ERROR,
                    'Cours conceptuel ordre must be an integer.',
                    $coursetarget
                );
                $ok = false;
            } else {
                $ordre = $course['ordre'];
                if ($ordre < 1 || $ordre > LOCAL_UCKK_CLI_SYNC_ATLAS_EXPECTED_COURSE_COUNT) {
                    local_uckk_cli_sync_atlas_add_message(
                        $result,
                        LOCAL_UCKK_CLI_SYNC_ATLAS_SEVERITY_ERROR,
                        'Cours conceptuel ordre must be between 1 and 10.',
                        $coursetarget
                    );
                    $ok = false;
                }

                if (array_key_exists((string)$ordre, $seenorders)) {
                    local_uckk_cli_sync_atlas_add_message(
                        $result,
                        LOCAL_UCKK_CLI_SYNC_ATLAS_SEVERITY_ERROR,
                        'Cours conceptuel ordre must be unique inside one voie.',
                        $coursetarget,
                        ['ordre' => $ordre]
                    );
                    $ok = false;
                }
                $seenorders[(string)$ordre] = true;
            }

            $expectedid = (string)($voie['code'] ?? '') . sprintf('%03d', 100 + (int)($course['ordre'] ?? 0));
            if (!empty($course['cours_id']) && $course['cours_id'] !== $expectedid) {
                local_uckk_cli_sync_atlas_add_message(
                    $result,
                    LOCAL_UCKK_CLI_SYNC_ATLAS_SEVERITY_ERROR,
                    'cours_id must equal CODE + three-digit number.',
                    $coursetarget,
                    [
                        'expected' => $expectedid,
                        'actual' => (string)$course['cours_id'],
                    ]
                );
                $ok = false;
            }

            if (!empty($course['cours_id'])) {
                if (array_key_exists((string)$course['cours_id'], $seencourseids)) {
                    local_uckk_cli_sync_atlas_add_message(
                        $result,
                        LOCAL_UCKK_CLI_SYNC_ATLAS_SEVERITY_ERROR,
                        'cours_id must be unique inside one voie.',
                        $coursetarget,
                        ['cours_id' => (string)$course['cours_id']]
                    );
                    $ok = false;
                }
                $seencourseids[(string)$course['cours_id']] = true;
            }

            if (empty($course['concept_maitre']) || !is_array($course['concept_maitre'])) {
                local_uckk_cli_sync_atlas_add_message(
                    $result,
                    LOCAL_UCKK_CLI_SYNC_ATLAS_SEVERITY_ERROR,
                    'Cours conceptuel concept_maitre must exist.',
                    $coursetarget
                );
                $ok = false;
            } else if (($course['concept_maitre']['type'] ?? null) !== 'concept_maitre') {
                local_uckk_cli_sync_atlas_add_message(
                    $result,
                    LOCAL_UCKK_CLI_SYNC_ATLAS_SEVERITY_ERROR,
                    'concept_maitre.type must be concept_maitre.',
                    $coursetarget
                );
                $ok = false;
            }

            if (!array_key_exists('concepts_associes', $course) || !is_array($course['concepts_associes'])) {
                local_uckk_cli_sync_atlas_add_message(
                    $result,
                    LOCAL_UCKK_CLI_SYNC_ATLAS_SEVERITY_ERROR,
                    'concepts_associes must be an array.',
                    $coursetarget
                );
                $ok = false;
            } else {
                foreach ($course['concepts_associes'] as $conceptindex => $concept) {
                    if (!is_array($concept)) {
                        local_uckk_cli_sync_atlas_add_message(
                            $result,
                            LOCAL_UCKK_CLI_SYNC_ATLAS_SEVERITY_ERROR,
                            'concepts_associes item must be an object.',
                            $coursetarget . ':concepts_associes[' . $conceptindex . ']'
                        );
                        $ok = false;
                        continue;
                    }

                    if (($concept['type'] ?? null) !== 'concept_associe') {
                        local_uckk_cli_sync_atlas_add_message(
                            $result,
                            LOCAL_UCKK_CLI_SYNC_ATLAS_SEVERITY_ERROR,
                            'Associated concept type must be concept_associe.',
                            $coursetarget . ':concepts_associes[' . $conceptindex . ']'
                        );
                        $ok = false;
                    }

                    if (!array_key_exists('notions_fines', $concept) || !is_array($concept['notions_fines'])) {
                        local_uckk_cli_sync_atlas_add_message(
                            $result,
                            LOCAL_UCKK_CLI_SYNC_ATLAS_SEVERITY_ERROR,
                            'Associated concept notions_fines must be an array.',
                            $coursetarget . ':concepts_associes[' . $conceptindex . ']'
                        );
                        $ok = false;
                    }
                }
            }
        }
    }

    return $ok;
}

/**
 * Escape text for HTML summary storage.
 *
 * @param mixed $value Value.
 * @return string
 */
function local_uckk_cli_sync_atlas_s($value): string {
    return s(is_scalar($value) ? (string)$value : '');
}

/**
 * Build category desired state.
 *
 * @param array<string, mixed> $manifestitem Manifest item.
 * @param array<string, mixed> $voie Atlas voie.
 * @param int $parent Parent category id.
 * @param bool $visible Visible.
 * @return array<string, mixed>
 */
function local_uckk_cli_sync_atlas_build_category_state(
    array $manifestitem,
    array $voie,
    int $parent,
    bool $visible
): array {
    $body = html_writer::tag('p', local_uckk_cli_sync_atlas_s($voie['definition_courte'] ?? ''));
    $body .= html_writer::tag(
        'p',
        html_writer::tag('strong', 'Voie UCKK : ') . local_uckk_cli_sync_atlas_s($voie['voie_id'] ?? '')
    );
    $body .= html_writer::tag(
        'p',
        'Reconnaissance interne UCKK; ne constitue pas un diplôme public accrédité.'
    );

    $source = [
        'type' => 'category',
        'voie_id' => $voie['voie_id'] ?? '',
        'code' => $voie['code'] ?? '',
        'nom' => $voie['nom'] ?? '',
        'category_idnumber' => $manifestitem['category_idnumber'] ?? '',
        'description' => $body,
    ];

    $sourcehash = hash('sha256', json_encode($source, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    $bodyhash = hash('sha256', $body);

    return [
        'name' => (string)($voie['nom'] ?? $manifestitem['nom'] ?? ''),
        'idnumber' => (string)($manifestitem['category_idnumber'] ?? ''),
        'parent' => $parent,
        'description' => $body . "\n" . local_uckk_cli_sync_atlas_marker($sourcehash, $bodyhash),
        'descriptionformat' => FORMAT_HTML,
        'visible' => $visible ? 1 : 0,
        'source_hash' => $sourcehash,
        'body_hash' => $bodyhash,
    ];
}

/**
 * Build course desired state.
 *
 * @param array<string, mixed> $course Course conceptuel.
 * @param array<string, mixed> $voie Atlas voie.
 * @param int $categoryid Category id.
 * @param string $format Course format.
 * @param bool $visible Visible.
 * @return array<string, mixed>
 */
function local_uckk_cli_sync_atlas_build_course_state(
    array $course,
    array $voie,
    int $categoryid,
    string $format,
    bool $visible
): array {
    $concept = is_array($course['concept_maitre'] ?? null) ? $course['concept_maitre'] : [];
    $artefact = is_array($course['artefact_maitrise'] ?? null) ? $course['artefact_maitrise'] : [];

    $body = html_writer::tag('p', local_uckk_cli_sync_atlas_s($concept['definition_courte'] ?? $voie['definition_courte'] ?? ''));

    if (!empty($concept['nom'])) {
        $body .= html_writer::tag(
            'p',
            html_writer::tag('strong', 'Concept maître : ') . local_uckk_cli_sync_atlas_s($concept['nom'])
        );
    }

    if (!empty($concept['fonction_pedagogique'])) {
        $body .= html_writer::tag(
            'p',
            html_writer::tag('strong', 'Fonction pédagogique : ') . local_uckk_cli_sync_atlas_s($concept['fonction_pedagogique'])
        );
    }

    if (!empty($artefact['nom'])) {
        $body .= html_writer::tag(
            'p',
            html_writer::tag('strong', 'Artefact de maîtrise : ') . local_uckk_cli_sync_atlas_s($artefact['nom'])
        );
    }

    $body .= html_writer::tag(
        'p',
        'Reconnaissance interne UCKK; ne constitue pas un diplôme public accrédité.'
    );

    $source = [
        'type' => 'course',
        'voie_id' => $voie['voie_id'] ?? '',
        'code' => $voie['code'] ?? '',
        'cours_id' => $course['cours_id'] ?? '',
        'ordre' => $course['ordre'] ?? 0,
        'nom' => $course['nom'] ?? '',
        'concept_maitre' => $concept,
        'artefact_maitrise' => $artefact,
        'summary' => $body,
    ];

    $sourcehash = hash('sha256', json_encode($source, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    $bodyhash = hash('sha256', $body);

    return [
        'fullname' => (string)($course['nom'] ?? ''),
        'shortname' => (string)($course['cours_id'] ?? ''),
        'idnumber' => (string)($course['cours_id'] ?? ''),
        'category' => $categoryid,
        'summary' => $body . "\n" . local_uckk_cli_sync_atlas_marker($sourcehash, $bodyhash),
        'summaryformat' => FORMAT_HTML,
        'format' => $format,
        'visible' => $visible ? 1 : 0,
        'source_hash' => $sourcehash,
        'body_hash' => $bodyhash,
    ];
}

/**
 * Build sync marker.
 *
 * @param string $sourcehash Source hash.
 * @param string $bodyhash Body hash.
 * @return string
 */
function local_uckk_cli_sync_atlas_marker(string $sourcehash, string $bodyhash): string {
    return '<!-- local_uckk_atlas_sync:source=' . $sourcehash . ';body=' . $bodyhash . ' -->';
}

/**
 * Extract marker from HTML text.
 *
 * @param string $text Text.
 * @return array{source:string,body:string}|null
 */
function local_uckk_cli_sync_atlas_extract_marker(string $text): ?array {
    if (preg_match(LOCAL_UCKK_CLI_SYNC_ATLAS_MARKER_RE, $text, $matches) !== 1) {
        return null;
    }

    return [
        'source' => $matches[1],
        'body' => $matches[2],
    ];
}

/**
 * Strip sync marker.
 *
 * @param string $text Text.
 * @return string
 */
function local_uckk_cli_sync_atlas_strip_marker(string $text): string {
    return trim((string)preg_replace(LOCAL_UCKK_CLI_SYNC_ATLAS_MARKER_RE, '', $text));
}

/**
 * Resolve sync status for an existing managed text field.
 *
 * @param string $existing Existing text.
 * @param string $desired Desired text with marker.
 * @param string $desiredsourcehash Desired source hash.
 * @return string
 */
function local_uckk_cli_sync_atlas_resolve_status(
    string $existing,
    string $desired,
    string $desiredsourcehash
): string {
    $marker = local_uckk_cli_sync_atlas_extract_marker($existing);
    $existingbody = local_uckk_cli_sync_atlas_strip_marker($existing);
    $desiredbody = local_uckk_cli_sync_atlas_strip_marker($desired);

    if ($marker === null) {
        if ($existingbody === $desiredbody) {
            return LOCAL_UCKK_CLI_SYNC_ATLAS_SYNC_NOT_SYNCED;
        }

        return LOCAL_UCKK_CLI_SYNC_ATLAS_SYNC_CHANGED_IN_MOODLE;
    }

    $jsonchanged = $marker['source'] !== $desiredsourcehash;
    $moodlechanged = hash('sha256', $existingbody) !== $marker['body'];

    if (!$jsonchanged && !$moodlechanged) {
        return LOCAL_UCKK_CLI_SYNC_ATLAS_SYNC_IN_SYNC;
    }

    if ($jsonchanged && !$moodlechanged) {
        return LOCAL_UCKK_CLI_SYNC_ATLAS_SYNC_CHANGED_IN_JSON;
    }

    if (!$jsonchanged && $moodlechanged) {
        return LOCAL_UCKK_CLI_SYNC_ATLAS_SYNC_CHANGED_IN_MOODLE;
    }

    return LOCAL_UCKK_CLI_SYNC_ATLAS_SYNC_CONFLICT;
}

/**
 * Determine if an item should be updated.
 *
 * @param string $status Sync status.
 * @param bool $allowconflicts Allow conflicts.
 * @return bool
 */
function local_uckk_cli_sync_atlas_may_update(string $status, bool $allowconflicts): bool {
    if ($status === LOCAL_UCKK_CLI_SYNC_ATLAS_SYNC_IN_SYNC) {
        return false;
    }

    if ($status === LOCAL_UCKK_CLI_SYNC_ATLAS_SYNC_CONFLICT ||
            $status === LOCAL_UCKK_CLI_SYNC_ATLAS_SYNC_CHANGED_IN_MOODLE) {
        return $allowconflicts;
    }

    return true;
}

/**
 * Resolve parent category id.
 *
 * @param string $idnumber Parent category idnumber.
 * @param array<string, mixed> $result Result.
 * @return int
 */
function local_uckk_cli_sync_atlas_resolve_parent_category(string $idnumber, array &$result): int {
    global $DB;

    if ($idnumber === '') {
        return 0;
    }

    $record = $DB->get_record('course_categories', ['idnumber' => $idnumber], '*', IGNORE_MISSING);
    if (!$record) {
        local_uckk_cli_sync_atlas_add_message(
            $result,
            LOCAL_UCKK_CLI_SYNC_ATLAS_SEVERITY_ERROR,
            'Parent category idnumber does not exist.',
            'parent-category',
            ['idnumber' => $idnumber]
        );
        return 0;
    }

    return (int)$record->id;
}

/**
 * Check course format availability.
 *
 * @param string $format Course format.
 * @param array<string, mixed> $result Result.
 */
function local_uckk_cli_sync_atlas_check_course_format(string $format, array &$result): void {
    global $CFG;

    $formatdir = $CFG->dirroot . '/course/format/' . $format;
    if (!is_dir($formatdir)) {
        local_uckk_cli_sync_atlas_add_message(
            $result,
            LOCAL_UCKK_CLI_SYNC_ATLAS_SEVERITY_WARNING,
            'Requested course format plugin directory does not exist.',
            'course-format',
            ['format' => $format]
        );
    }
}

/**
 * Check required Moodle custom fields.
 *
 * @param array<string, mixed> $result Result.
 */
function local_uckk_cli_sync_atlas_check_custom_fields(array &$result): void {
    global $DB;

    $manager = $DB->get_manager();

    if (!$manager->table_exists(new xmldb_table('customfield_category')) ||
            !$manager->table_exists(new xmldb_table('customfield_field'))) {
        foreach (LOCAL_UCKK_CLI_SYNC_ATLAS_CUSTOM_FIELDS as $field) {
            $result['counts']['custom_fields_missing']++;
            local_uckk_cli_sync_atlas_add_message(
                $result,
                LOCAL_UCKK_CLI_SYNC_ATLAS_SEVERITY_WARNING,
                'Moodle custom field table is missing; custom field cannot be checked.',
                'customfield:' . $field
            );
        }
        return;
    }

    foreach (LOCAL_UCKK_CLI_SYNC_ATLAS_CUSTOM_FIELDS as $shortname) {
        $exists = $DB->record_exists_sql(
            "SELECT 1
               FROM {customfield_field} f
               JOIN {customfield_category} c ON c.id = f.categoryid
              WHERE f.shortname = :shortname
                AND c.component = :component
                AND c.area = :area",
            [
                'shortname' => $shortname,
                'component' => 'core_course',
                'area' => 'course',
            ]
        );

        if (!$exists) {
            $result['counts']['custom_fields_missing']++;
            local_uckk_cli_sync_atlas_add_message(
                $result,
                LOCAL_UCKK_CLI_SYNC_ATLAS_SEVERITY_WARNING,
                'Required course custom field is missing.',
                'customfield:' . $shortname
            );
        }
    }
}

/**
 * Check if a Parchemin badge appears to exist.
 *
 * @param string $name Badge name.
 * @return bool
 */
function local_uckk_cli_sync_atlas_badge_exists(string $name): bool {
    global $DB;

    if ($name === '') {
        return false;
    }

    $manager = $DB->get_manager();
    if (!$manager->table_exists(new xmldb_table('badge'))) {
        return false;
    }

    return $DB->record_exists('badge', ['name' => $name]);
}

/**
 * Add report item.
 *
 * @param array<string, mixed> $result Result.
 * @param array<string, mixed> $item Item.
 */
function local_uckk_cli_sync_atlas_add_item(array &$result, array $item): void {
    $result['items'][] = $item;
}

/**
 * Sync one category.
 *
 * @param array<string, mixed> $state Desired state.
 * @param string $mode Mode.
 * @param bool $allowconflicts Allow conflicts.
 * @param array<string, mixed> $result Result.
 * @return int|null Category id.
 */
function local_uckk_cli_sync_atlas_sync_category(
    array $state,
    string $mode,
    bool $allowconflicts,
    array &$result
): ?int {
    global $DB;

    $target = 'category:' . $state['idnumber'];
    $existing = $DB->get_record('course_categories', ['idnumber' => $state['idnumber']], '*', IGNORE_MISSING);

    if (!$existing) {
        $result['counts']['categories_to_create']++;

        local_uckk_cli_sync_atlas_add_item($result, [
            'type' => 'category',
            'key' => $state['idnumber'],
            'status' => LOCAL_UCKK_CLI_SYNC_ATLAS_SYNC_MISSING_IN_MOODLE,
            'operation' => 'create',
            'source_hash' => $state['source_hash'],
        ]);

        if ($mode === LOCAL_UCKK_CLI_SYNC_ATLAS_MODE_APPLY) {
            $created = core_course_category::create([
                'name' => $state['name'],
                'idnumber' => $state['idnumber'],
                'parent' => $state['parent'],
                'description' => $state['description'],
                'descriptionformat' => $state['descriptionformat'],
                'visible' => $state['visible'],
            ]);

            $result['counts']['categories_created']++;

            local_uckk_cli_sync_atlas_add_message(
                $result,
                LOCAL_UCKK_CLI_SYNC_ATLAS_SEVERITY_SUCCESS,
                'Category created.',
                $target,
                ['id' => (int)$created->id]
            );

            return (int)$created->id;
        }

        return null;
    }

    $status = local_uckk_cli_sync_atlas_resolve_status(
        (string)$existing->description,
        (string)$state['description'],
        (string)$state['source_hash']
    );

    if ($status === LOCAL_UCKK_CLI_SYNC_ATLAS_SYNC_CONFLICT) {
        $result['counts']['conflicts']++;
    }

    $needsupdate = (
        (string)$existing->name !== (string)$state['name'] ||
        (int)$existing->parent !== (int)$state['parent'] ||
        (int)$existing->visible !== (int)$state['visible'] ||
        $status !== LOCAL_UCKK_CLI_SYNC_ATLAS_SYNC_IN_SYNC
    );

    if (!$needsupdate) {
        local_uckk_cli_sync_atlas_add_item($result, [
            'type' => 'category',
            'key' => $state['idnumber'],
            'status' => LOCAL_UCKK_CLI_SYNC_ATLAS_SYNC_IN_SYNC,
            'operation' => 'none',
            'source_hash' => $state['source_hash'],
        ]);

        return (int)$existing->id;
    }

    $mayupdate = local_uckk_cli_sync_atlas_may_update($status, $allowconflicts);
    $operation = $mayupdate ? 'update' : 'blocked';

    if ($mayupdate) {
        $result['counts']['categories_to_update']++;
    }

    local_uckk_cli_sync_atlas_add_item($result, [
        'type' => 'category',
        'key' => $state['idnumber'],
        'status' => $status,
        'operation' => $operation,
        'source_hash' => $state['source_hash'],
    ]);

    if (!$mayupdate) {
        local_uckk_cli_sync_atlas_add_message(
            $result,
            LOCAL_UCKK_CLI_SYNC_ATLAS_SEVERITY_WARNING,
            'Category update blocked by conflict policy.',
            $target,
            ['status' => $status]
        );
        return (int)$existing->id;
    }

    if ($mode === LOCAL_UCKK_CLI_SYNC_ATLAS_MODE_APPLY) {
        $category = core_course_category::get((int)$existing->id, MUST_EXIST, true);
        $category->update([
            'name' => $state['name'],
            'idnumber' => $state['idnumber'],
            'parent' => $state['parent'],
            'description' => $state['description'],
            'descriptionformat' => $state['descriptionformat'],
            'visible' => $state['visible'],
        ]);

        $result['counts']['categories_updated']++;

        local_uckk_cli_sync_atlas_add_message(
            $result,
            LOCAL_UCKK_CLI_SYNC_ATLAS_SEVERITY_SUCCESS,
            'Category updated.',
            $target,
            ['id' => (int)$existing->id]
        );
    }

    return (int)$existing->id;
}

/**
 * Sync one course.
 *
 * @param array<string, mixed> $state Desired state.
 * @param string $mode Mode.
 * @param bool $allowconflicts Allow conflicts.
 * @param array<string, mixed> $result Result.
 */
function local_uckk_cli_sync_atlas_sync_course(
    array $state,
    string $mode,
    bool $allowconflicts,
    array &$result
): void {
    global $DB;

    $target = 'course:' . $state['idnumber'];
    $existing = $DB->get_record('course', ['idnumber' => $state['idnumber']], '*', IGNORE_MISSING);

    if (!$existing) {
        $result['counts']['courses_to_create']++;

        local_uckk_cli_sync_atlas_add_item($result, [
            'type' => 'course',
            'key' => $state['idnumber'],
            'status' => LOCAL_UCKK_CLI_SYNC_ATLAS_SYNC_MISSING_IN_MOODLE,
            'operation' => 'create',
            'source_hash' => $state['source_hash'],
        ]);

        if ($mode === LOCAL_UCKK_CLI_SYNC_ATLAS_MODE_APPLY) {
            $course = (object)[
                'fullname' => $state['fullname'],
                'shortname' => $state['shortname'],
                'idnumber' => $state['idnumber'],
                'category' => $state['category'],
                'summary' => $state['summary'],
                'summaryformat' => $state['summaryformat'],
                'format' => $state['format'],
                'visible' => $state['visible'],
            ];

            $created = create_course($course);
            $result['counts']['courses_created']++;

            local_uckk_cli_sync_atlas_add_message(
                $result,
                LOCAL_UCKK_CLI_SYNC_ATLAS_SEVERITY_SUCCESS,
                'Course created.',
                $target,
                ['id' => (int)$created->id]
            );
        }

        return;
    }

    $status = local_uckk_cli_sync_atlas_resolve_status(
        (string)$existing->summary,
        (string)$state['summary'],
        (string)$state['source_hash']
    );

    if ($status === LOCAL_UCKK_CLI_SYNC_ATLAS_SYNC_CONFLICT) {
        $result['counts']['conflicts']++;
    }

    $needsupdate = (
        (string)$existing->fullname !== (string)$state['fullname'] ||
        (string)$existing->shortname !== (string)$state['shortname'] ||
        (int)$existing->category !== (int)$state['category'] ||
        (string)$existing->format !== (string)$state['format'] ||
        (int)$existing->visible !== (int)$state['visible'] ||
        $status !== LOCAL_UCKK_CLI_SYNC_ATLAS_SYNC_IN_SYNC
    );

    if (!$needsupdate) {
        local_uckk_cli_sync_atlas_add_item($result, [
            'type' => 'course',
            'key' => $state['idnumber'],
            'status' => LOCAL_UCKK_CLI_SYNC_ATLAS_SYNC_IN_SYNC,
            'operation' => 'none',
            'source_hash' => $state['source_hash'],
        ]);

        return;
    }

    $mayupdate = local_uckk_cli_sync_atlas_may_update($status, $allowconflicts);
    $operation = $mayupdate ? 'update' : 'blocked';

    if ($mayupdate) {
        $result['counts']['courses_to_update']++;
    }

    local_uckk_cli_sync_atlas_add_item($result, [
        'type' => 'course',
        'key' => $state['idnumber'],
        'status' => $status,
        'operation' => $operation,
        'source_hash' => $state['source_hash'],
    ]);

    if (!$mayupdate) {
        local_uckk_cli_sync_atlas_add_message(
            $result,
            LOCAL_UCKK_CLI_SYNC_ATLAS_SEVERITY_WARNING,
            'Course update blocked by conflict policy.',
            $target,
            ['status' => $status]
        );
        return;
    }

    if ($mode === LOCAL_UCKK_CLI_SYNC_ATLAS_MODE_APPLY) {
        $course = (object)[
            'id' => (int)$existing->id,
            'fullname' => $state['fullname'],
            'shortname' => $state['shortname'],
            'idnumber' => $state['idnumber'],
            'category' => $state['category'],
            'summary' => $state['summary'],
            'summaryformat' => $state['summaryformat'],
            'format' => $state['format'],
            'visible' => $state['visible'],
        ];

        update_course($course);
        $result['counts']['courses_updated']++;

        local_uckk_cli_sync_atlas_add_message(
            $result,
            LOCAL_UCKK_CLI_SYNC_ATLAS_SEVERITY_SUCCESS,
            'Course updated.',
            $target,
            ['id' => (int)$existing->id]
        );
    }
}

/**
 * Trigger a sync event if its class exists.
 *
 * @param string $mode Mode.
 * @param array<string, mixed> $result Result.
 */
function local_uckk_cli_sync_atlas_trigger_event(string $mode, array $result): void {
    $classname = $mode === LOCAL_UCKK_CLI_SYNC_ATLAS_MODE_APPLY
        ? '\\local_uckk\\event\\atlas_sync_applied'
        : '\\local_uckk\\event\\atlas_sync_dryrun_completed';

    if (!class_exists($classname)) {
        return;
    }

    $event = $classname::create([
        'context' => context_system::instance(),
        'other' => [
            'status' => $result['status'],
            'mode' => $result['mode'],
            'hash' => $result['hash'],
            'counts' => $result['counts'],
        ],
    ]);
    $event->trigger();
}

/**
 * Print JSON output.
 *
 * @param array<string, mixed> $result Result.
 */
function local_uckk_cli_sync_atlas_print_json(array $result): void {
    cli_writeln(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
}

/**
 * Print text output.
 *
 * @param array<string, mixed> $result Result.
 * @param bool $quiet Quiet.
 */
function local_uckk_cli_sync_atlas_print_text(array $result, bool $quiet): void {
    cli_writeln('UCKK Atlas sync');
    cli_writeln('Mode: ' . $result['mode']);
    cli_writeln('Status: ' . $result['status']);
    cli_writeln('Summary: ' . $result['summary']);
    cli_writeln('Hash: ' . $result['hash']);
    cli_writeln('');

    cli_writeln('Counts:');
    foreach ($result['counts'] as $key => $value) {
        cli_writeln('  ' . $key . ': ' . $value);
    }

    if ($quiet) {
        return;
    }

    if (!empty($result['items'])) {
        cli_writeln('');
        cli_writeln('Items:');
        foreach ($result['items'] as $item) {
            cli_writeln(
                '  [' . ($item['type'] ?? '') . '] ' .
                ($item['key'] ?? '') .
                ' status=' . ($item['status'] ?? '') .
                ' operation=' . ($item['operation'] ?? '')
            );
        }
    }

    if (!empty($result['messages'])) {
        cli_writeln('');
        cli_writeln('Messages:');
        foreach ($result['messages'] as $message) {
            $target = empty($message['target']) ? '' : ' ' . $message['target'] . ':';
            cli_writeln('  [' . strtoupper((string)$message['severity']) . ']' . $target . ' ' . $message['message']);
        }
    }
}

/**
 * Return exit code.
 *
 * @param array<string, mixed> $result Result.
 * @param bool $strict Strict mode.
 * @return int
 */
function local_uckk_cli_sync_atlas_exit_code(array $result, bool $strict): int {
    if ($result['counts']['errors'] > 0) {
        return LOCAL_UCKK_CLI_SYNC_ATLAS_EXIT_ERROR;
    }

    if ($strict && ($result['counts']['warnings'] > 0 || $result['counts']['conflicts'] > 0)) {
        return LOCAL_UCKK_CLI_SYNC_ATLAS_EXIT_ERROR;
    }

    return LOCAL_UCKK_CLI_SYNC_ATLAS_EXIT_OK;
}

[$options, $unrecognised] = cli_get_params(
    [
        'help' => false,
        'dry-run' => false,
        'apply' => false,
        'force' => false,
        'allow-conflicts' => false,
        'voie' => '',
        'parent-category' => '',
        'course-format' => LOCAL_UCKK_CLI_SYNC_ATLAS_DEFAULT_COURSE_FORMAT,
        'hidden' => false,
        'json' => false,
        'strict' => false,
        'quiet' => false,
    ],
    [
        'h' => 'help',
    ]
);

if (!empty($unrecognised)) {
    cli_error('Unknown option(s): ' . implode(', ', $unrecognised), LOCAL_UCKK_CLI_SYNC_ATLAS_EXIT_ERROR);
}

if (!empty($options['help'])) {
    cli_writeln(local_uckk_cli_sync_atlas_help());
    exit(LOCAL_UCKK_CLI_SYNC_ATLAS_EXIT_OK);
}

$mode = !empty($options['apply'])
    ? LOCAL_UCKK_CLI_SYNC_ATLAS_MODE_APPLY
    : LOCAL_UCKK_CLI_SYNC_ATLAS_MODE_DRY_RUN;

if (!empty($options['apply']) && !empty($options['dry-run'])) {
    cli_error('Use either --apply or --dry-run, not both.', LOCAL_UCKK_CLI_SYNC_ATLAS_EXIT_ERROR);
}

if ($mode === LOCAL_UCKK_CLI_SYNC_ATLAS_MODE_APPLY && empty($options['force'])) {
    cli_error('--apply requires --force.', LOCAL_UCKK_CLI_SYNC_ATLAS_EXIT_ERROR);
}

$result = local_uckk_cli_sync_atlas_new_result($mode);

try {
    $admin = get_admin();
    if (!$admin || empty($admin->id)) {
        throw new moodle_exception('Unable to load Moodle admin user.');
    }

    cron_setup_user($admin);

    if (function_exists('get_capability_info') && get_capability_info(LOCAL_UCKK_CLI_SYNC_ATLAS_CAPABILITY)) {
        require_capability(LOCAL_UCKK_CLI_SYNC_ATLAS_CAPABILITY, context_system::instance());
    } else if ($mode === LOCAL_UCKK_CLI_SYNC_ATLAS_MODE_APPLY) {
        local_uckk_cli_sync_atlas_add_message(
            $result,
            LOCAL_UCKK_CLI_SYNC_ATLAS_SEVERITY_ERROR,
            'Required sync capability is not installed.',
            LOCAL_UCKK_CLI_SYNC_ATLAS_CAPABILITY
        );
    } else {
        local_uckk_cli_sync_atlas_add_message(
            $result,
            LOCAL_UCKK_CLI_SYNC_ATLAS_SEVERITY_WARNING,
            'Sync capability is not installed yet; dry-run continues.',
            LOCAL_UCKK_CLI_SYNC_ATLAS_CAPABILITY
        );
    }

    $basepath = local_uckk_cli_sync_atlas_path($CFG->dirroot, 'local/uckk');
    $manifestpath = local_uckk_cli_sync_atlas_path($basepath, LOCAL_UCKK_CLI_SYNC_ATLAS_MANIFEST_FILE);
    $manifest = local_uckk_cli_sync_atlas_decode_json_file(
        $manifestpath,
        LOCAL_UCKK_CLI_SYNC_ATLAS_MANIFEST_FILE,
        $result
    );

    $items = [];
    if ($manifest !== null) {
        $items = local_uckk_cli_sync_atlas_validate_manifest($manifest, $result);
        $items = local_uckk_cli_sync_atlas_filter_items($items, trim((string)$options['voie']), $result);
    }

    $parentid = local_uckk_cli_sync_atlas_resolve_parent_category(trim((string)$options['parent-category']), $result);
    $courseformat = trim((string)$options['course-format']);
    if ($courseformat === '') {
        $courseformat = LOCAL_UCKK_CLI_SYNC_ATLAS_DEFAULT_COURSE_FORMAT;
    }

    local_uckk_cli_sync_atlas_check_course_format($courseformat, $result);
    local_uckk_cli_sync_atlas_check_custom_fields($result);

    $visible = empty($options['hidden']);
    $allowconflicts = !empty($options['allow-conflicts']);

    if ($result['counts']['errors'] === 0) {
        foreach ($items as $manifestitem) {
            $file = (string)($manifestitem['file'] ?? '');
            if (!local_uckk_cli_sync_atlas_safe_json_filename($file)) {
                continue;
            }

            $target = LOCAL_UCKK_CLI_SYNC_ATLAS_VOIE_DIR . '/' . $file;
            $path = local_uckk_cli_sync_atlas_path($basepath, $target);
            $voie = local_uckk_cli_sync_atlas_decode_json_file($path, $target, $result);

            if ($voie === null) {
                continue;
            }

            $result['counts']['voies_checked']++;

            if (!local_uckk_cli_sync_atlas_validate_voie($voie, $manifestitem, $result, $target)) {
                continue;
            }

            if (!local_uckk_cli_sync_atlas_badge_exists((string)($voie['parchemin'] ?? ''))) {
                $result['counts']['badges_to_create']++;
                local_uckk_cli_sync_atlas_add_message(
                    $result,
                    LOCAL_UCKK_CLI_SYNC_ATLAS_SEVERITY_WARNING,
                    'Parchemin badge appears to be missing. This command reports it but does not create badges.',
                    $target,
                    ['parchemin' => (string)($voie['parchemin'] ?? '')]
                );
            }

            $categorystate = local_uckk_cli_sync_atlas_build_category_state($manifestitem, $voie, $parentid, $visible);
            $categoryid = local_uckk_cli_sync_atlas_sync_category(
                $categorystate,
                $mode,
                $allowconflicts,
                $result
            );

            if ($categoryid === null) {
                $existingcategory = $DB->get_record(
                    'course_categories',
                    ['idnumber' => $categorystate['idnumber']],
                    '*',
                    IGNORE_MISSING
                );
                $categoryid = $existingcategory ? (int)$existingcategory->id : 0;
            }

            if ($categoryid <= 0) {
                local_uckk_cli_sync_atlas_add_message(
                    $result,
                    LOCAL_UCKK_CLI_SYNC_ATLAS_SEVERITY_WARNING,
                    'Course shell diff skipped because category does not exist in dry-run.',
                    $target,
                    ['category_idnumber' => $categorystate['idnumber']]
                );
                continue;
            }

            foreach ($voie['cours_conceptuels'] as $course) {
                if (!is_array($course)) {
                    continue;
                }

                $result['counts']['courses_checked']++;

                $coursestate = local_uckk_cli_sync_atlas_build_course_state(
                    $course,
                    $voie,
                    $categoryid,
                    $courseformat,
                    $visible
                );

                local_uckk_cli_sync_atlas_sync_course($coursestate, $mode, $allowconflicts, $result);
            }
        }
    }

    $result = local_uckk_cli_sync_atlas_finalise($result);
    local_uckk_cli_sync_atlas_trigger_event($mode, $result);

    if (!empty($options['json'])) {
        local_uckk_cli_sync_atlas_print_json($result);
    } else {
        local_uckk_cli_sync_atlas_print_text($result, !empty($options['quiet']));
    }

    exit(local_uckk_cli_sync_atlas_exit_code($result, !empty($options['strict'])));
} catch (Throwable $exception) {
    $result['ok'] = false;
    $result['status'] = LOCAL_UCKK_CLI_SYNC_ATLAS_STATUS_FAILED;
    $result['summary'] = 'UCKK Atlas sync crashed.';

    local_uckk_cli_sync_atlas_add_message(
        $result,
        LOCAL_UCKK_CLI_SYNC_ATLAS_SEVERITY_ERROR,
        $exception->getMessage(),
        LOCAL_UCKK_CLI_SYNC_ATLAS_ACTION,
        ['exception' => get_class($exception)]
    );

    $result = local_uckk_cli_sync_atlas_finalise($result);

    if (!empty($options['json'])) {
        local_uckk_cli_sync_atlas_print_json($result);
    } else {
        local_uckk_cli_sync_atlas_print_text($result, !empty($options['quiet']));

        if (debugging('', DEBUG_DEVELOPER)) {
            cli_writeln('');
            cli_writeln($exception->getTraceAsString());
        }
    }

    exit(LOCAL_UCKK_CLI_SYNC_ATLAS_EXIT_ERROR);
}
