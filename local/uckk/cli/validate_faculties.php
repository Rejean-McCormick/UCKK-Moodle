<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

/**
 * CLI validation entry point for UCKK faculty public profiles.
 *
 * @package    local_uckk
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

define('CLI_SCRIPT', true);

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');

const LOCAL_UCKK_CLI_VALIDATE_FACULTIES_ACTION = 'validate_faculties';
const LOCAL_UCKK_CLI_VALIDATE_FACULTIES_COMPONENT = 'local_uckk';

const LOCAL_UCKK_CLI_VALIDATE_FACULTIES_STATUS_COMPLETED = 'completed';
const LOCAL_UCKK_CLI_VALIDATE_FACULTIES_STATUS_WARNING = 'warning';
const LOCAL_UCKK_CLI_VALIDATE_FACULTIES_STATUS_FAILED = 'failed';

const LOCAL_UCKK_CLI_VALIDATE_FACULTIES_SEVERITY_INFO = 'info';
const LOCAL_UCKK_CLI_VALIDATE_FACULTIES_SEVERITY_SUCCESS = 'success';
const LOCAL_UCKK_CLI_VALIDATE_FACULTIES_SEVERITY_WARNING = 'warning';
const LOCAL_UCKK_CLI_VALIDATE_FACULTIES_SEVERITY_ERROR = 'error';

const LOCAL_UCKK_CLI_VALIDATE_FACULTIES_EXIT_OK = 0;
const LOCAL_UCKK_CLI_VALIDATE_FACULTIES_EXIT_ERROR = 1;

const LOCAL_UCKK_CLI_VALIDATE_FACULTIES_EXPECTED_COUNT = 10;

const LOCAL_UCKK_CLI_VALIDATE_FACULTY_SCHEMA_VERSION = 'UCKK-FACULTY-0.1';
const LOCAL_UCKK_CLI_VALIDATE_FACULTY_MANIFEST_SCHEMA_VERSION = 'UCKK-FACULTY-MANIFEST-0.1';
const LOCAL_UCKK_CLI_VALIDATE_ATLAS_SCHEMA_VERSION = 'UCKK-ATLAS-0.2-draft';
const LOCAL_UCKK_CLI_VALIDATE_ATLAS_MANIFEST_SCHEMA_VERSION = 'UCKK-ATLAS-MANIFEST-0.1';

const LOCAL_UCKK_CLI_VALIDATE_FACULTY_DIR = 'content/faculties';
const LOCAL_UCKK_CLI_VALIDATE_ATLAS_DIR = 'atlas/voies';
const LOCAL_UCKK_CLI_VALIDATE_FACULTY_MANIFEST = 'content/faculties/faculty_manifest.json';
const LOCAL_UCKK_CLI_VALIDATE_ATLAS_MANIFEST = 'atlas/atlas_manifest.json';

const LOCAL_UCKK_CLI_VALIDATE_ALLOWED_FACULTY_STATUS = [
    'draft',
    'published',
    'archived',
];

const LOCAL_UCKK_CLI_VALIDATE_ALLOWED_FACULTY_VISIBILITY = [
    'public',
    'hidden',
    'restricted',
];

const LOCAL_UCKK_CLI_VALIDATE_ALLOWED_SYNC_MODE = [
    'read_only',
    'preview_only',
    'moodle_sync_allowed',
];

const LOCAL_UCKK_CLI_VALIDATE_ALLOWED_ENROLMENT_VISIBILITY = [
    'hidden',
    'public_info_only',
    'login_required',
    'enrolment_required',
];

const LOCAL_UCKK_CLI_VALIDATE_ALLOWED_SECTION_TYPES = [
    'text',
    'markdown',
    'quote',
    'principle',
    'notice',
    'cards',
    'callout',
    'two_column',
];

const LOCAL_UCKK_CLI_VALIDATE_REQUIRED_ATLAS_PROJECTION_KEYS = [
    'show_definition_courte',
    'show_angle_fondamental',
    'show_competence_centrale',
    'show_seuils_progression',
    'show_courses',
    'show_course_codes',
    'show_concept_maitre',
    'show_concepts_associes',
    'show_artefacts',
    'show_criteres_passage',
    'show_projet_final',
    'show_limites_ethiques',
    'show_relations_intervoies',
    'show_tags',
];

const LOCAL_UCKK_CLI_VALIDATE_ALLOWED_DYNAMIC_BLOCK_TYPES = [
    'announcements',
    'events',
    'moodle_course_list',
    'featured_courses',
    'faculty_news',
    'related_faculties',
    'public_resources',
    'cta_panel',
];

const LOCAL_UCKK_CLI_VALIDATE_ALLOWED_DYNAMIC_PROVIDERS = [
    'moodle_forum',
    'moodle_calendar',
    'moodle_category',
    'moodle_course_customfield',
    'local_uckk_news',
    'local_uckk_manual',
    'none',
];

const LOCAL_UCKK_CLI_VALIDATE_ALLOWED_FEATURED_BLOCK_TYPES = [
    'principle',
    'notice',
    'warning',
    'quote',
    'stat',
    'method',
    'ethics',
    'cta',
];

const LOCAL_UCKK_CLI_VALIDATE_ALLOWED_EDITORIAL_STATUS = [
    'draft',
    'review',
    'approved',
    'needs_update',
    'archived',
];

const LOCAL_UCKK_CLI_VALIDATE_ALLOWED_OVERRIDES = [
    'identity.name',
    'identity.short_name',
    'identity.title_symbolique',
    'identity.domain',
    'hero.title',
    'hero.subtitle',
    'seo.title',
    'seo.description',
];

const LOCAL_UCKK_CLI_VALIDATE_FORBIDDEN_TOP_LEVEL_DUPLICATION_FIELDS = [
    'courses',
    'cours',
];

/**
 * Render CLI help.
 *
 * @return string
 */
function local_uckk_cli_validate_faculties_help(): string {
    return <<<HELP
Validate UCKK faculty public profile JSON files.

This command validates:
  - local/uckk/content/faculties/faculty_manifest.json
  - local/uckk/atlas/atlas_manifest.json
  - the 10 *.faculty.json files listed in the faculty manifest
  - the linked Atlas voie_*.json files
  - cross-file consistency between Faculty and Atlas JSON data

The command is read-only. It does not create courses, categories, badges,
custom fields, events, enrolments, completion data, or Moodle records.

Usage:
  php local/uckk/cli/validate_faculties.php [options]

Options:
  --slug=<slug>   Validate one faculty slug after validating both manifests.
                  The slug must exist in faculty_manifest.json.
  --json          Print machine-readable JSON.
  --strict        Return exit code 1 when warnings are present.
  --quiet         Print only final status unless --json is used.
  -h, --help      Print this help.

Examples:
  php local/uckk/cli/validate_faculties.php
  php local/uckk/cli/validate_faculties.php --strict
  php local/uckk/cli/validate_faculties.php --json
  php local/uckk/cli/validate_faculties.php --slug=grand-jeu-social

HELP;
}

/**
 * Create the base result payload.
 *
 * @return array<string, mixed>
 */
function local_uckk_cli_validate_faculties_new_result(): array {
    return [
        'ok' => false,
        'component' => LOCAL_UCKK_CLI_VALIDATE_FACULTIES_COMPONENT,
        'action' => LOCAL_UCKK_CLI_VALIDATE_FACULTIES_ACTION,
        'status' => LOCAL_UCKK_CLI_VALIDATE_FACULTIES_STATUS_FAILED,
        'summary' => '',
        'counts' => [
            'manifest_items' => 0,
            'atlas_manifest_items' => 0,
            'faculties_checked' => 0,
            'atlas_files_checked' => 0,
            'errors' => 0,
            'warnings' => 0,
        ],
        'messages' => [],
    ];
}

/**
 * Add a validation message.
 *
 * @param array<string, mixed> $result Result payload.
 * @param string $severity Message severity.
 * @param string $message Message text.
 * @param string $target Message target.
 * @param array<string, mixed> $metadata Safe metadata.
 */
function local_uckk_cli_validate_faculties_add_message(
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

    if ($severity === LOCAL_UCKK_CLI_VALIDATE_FACULTIES_SEVERITY_ERROR) {
        $result['counts']['errors']++;
    } else if ($severity === LOCAL_UCKK_CLI_VALIDATE_FACULTIES_SEVERITY_WARNING) {
        $result['counts']['warnings']++;
    }
}

/**
 * Finalise result state.
 *
 * @param array<string, mixed> $result Result payload.
 * @return array<string, mixed>
 */
function local_uckk_cli_validate_faculties_finalise(array $result): array {
    if ($result['counts']['errors'] > 0) {
        $result['ok'] = false;
        $result['status'] = LOCAL_UCKK_CLI_VALIDATE_FACULTIES_STATUS_FAILED;
        $result['summary'] = 'UCKK faculty validation failed.';
    } else if ($result['counts']['warnings'] > 0) {
        $result['ok'] = true;
        $result['status'] = LOCAL_UCKK_CLI_VALIDATE_FACULTIES_STATUS_WARNING;
        $result['summary'] = 'UCKK faculty validation completed with warnings.';
    } else {
        $result['ok'] = true;
        $result['status'] = LOCAL_UCKK_CLI_VALIDATE_FACULTIES_STATUS_COMPLETED;
        $result['summary'] = 'UCKK faculty validation completed successfully.';
    }

    return $result;
}

/**
 * Return whether a string starts with a prefix.
 *
 * @param string $value Value.
 * @param string $prefix Prefix.
 * @return bool
 */
function local_uckk_cli_validate_faculties_starts_with(string $value, string $prefix): bool {
    return substr($value, 0, strlen($prefix)) === $prefix;
}

/**
 * Return whether a string ends with a suffix.
 *
 * @param string $value Value.
 * @param string $suffix Suffix.
 * @return bool
 */
function local_uckk_cli_validate_faculties_ends_with(string $value, string $suffix): bool {
    if ($suffix === '') {
        return true;
    }

    return substr($value, -strlen($suffix)) === $suffix;
}

/**
 * Join a base path and a relative path.
 *
 * @param string $base Base path.
 * @param string $relative Relative path using slash separators.
 * @return string
 */
function local_uckk_cli_validate_faculties_path(string $base, string $relative): string {
    return rtrim($base, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);
}

/**
 * Validate that a manifest filename is local and cannot escape its directory.
 *
 * @param string $filename Filename.
 * @param string $suffix Required suffix.
 * @return bool
 */
function local_uckk_cli_validate_faculties_safe_filename(string $filename, string $suffix): bool {
    if ($filename === '' || strpos($filename, "\0") !== false) {
        return false;
    }

    if (basename($filename) !== $filename) {
        return false;
    }

    if (strpos($filename, '/') !== false || strpos($filename, '\\') !== false) {
        return false;
    }

    return local_uckk_cli_validate_faculties_ends_with($filename, $suffix);
}

/**
 * Validate slug syntax.
 *
 * @param string $slug Slug.
 * @return bool
 */
function local_uckk_cli_validate_faculties_valid_slug(string $slug): bool {
    return preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug) === 1;
}

/**
 * Decode a JSON file as an associative array.
 *
 * @param string $path Absolute path.
 * @param array<string, mixed> $result Result payload.
 * @param string $target Message target.
 * @return array<string, mixed>|null
 */
function local_uckk_cli_validate_faculties_decode_json_file(
    string $path,
    array &$result,
    string $target
): ?array {
    if (!is_file($path)) {
        local_uckk_cli_validate_faculties_add_message(
            $result,
            LOCAL_UCKK_CLI_VALIDATE_FACULTIES_SEVERITY_ERROR,
            'Required JSON file does not exist.',
            $target
        );
        return null;
    }

    if (!is_readable($path)) {
        local_uckk_cli_validate_faculties_add_message(
            $result,
            LOCAL_UCKK_CLI_VALIDATE_FACULTIES_SEVERITY_ERROR,
            'Required JSON file is not readable.',
            $target
        );
        return null;
    }

    $raw = file_get_contents($path);
    if ($raw === false) {
        local_uckk_cli_validate_faculties_add_message(
            $result,
            LOCAL_UCKK_CLI_VALIDATE_FACULTIES_SEVERITY_ERROR,
            'Unable to read JSON file.',
            $target
        );
        return null;
    }

    try {
        $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
    } catch (JsonException $exception) {
        local_uckk_cli_validate_faculties_add_message(
            $result,
            LOCAL_UCKK_CLI_VALIDATE_FACULTIES_SEVERITY_ERROR,
            'Invalid JSON syntax: ' . $exception->getMessage(),
            $target
        );
        return null;
    }

    if (!is_array($decoded)) {
        local_uckk_cli_validate_faculties_add_message(
            $result,
            LOCAL_UCKK_CLI_VALIDATE_FACULTIES_SEVERITY_ERROR,
            'JSON root must be an object.',
            $target
        );
        return null;
    }

    return $decoded;
}

/**
 * Get a nested value by dot path.
 *
 * @param array<string, mixed> $data Data.
 * @param string $path Dot path.
 * @return mixed
 */
function local_uckk_cli_validate_faculties_get_path(array $data, string $path) {
    $current = $data;
    foreach (explode('.', $path) as $part) {
        if (!is_array($current) || !array_key_exists($part, $current)) {
            return null;
        }
        $current = $current[$part];
    }

    return $current;
}

/**
 * Add an error if a required key is missing.
 *
 * @param array<string, mixed> $data Data.
 * @param string $key Key.
 * @param array<string, mixed> $result Result payload.
 * @param string $target Target.
 * @return bool
 */
function local_uckk_cli_validate_faculties_require_key(
    array $data,
    string $key,
    array &$result,
    string $target
): bool {
    if (!array_key_exists($key, $data)) {
        local_uckk_cli_validate_faculties_add_message(
            $result,
            LOCAL_UCKK_CLI_VALIDATE_FACULTIES_SEVERITY_ERROR,
            'Missing required key: ' . $key,
            $target
        );
        return false;
    }

    return true;
}

/**
 * Add an error if a required string field is missing or empty.
 *
 * @param array<string, mixed> $data Data.
 * @param string $key Key.
 * @param array<string, mixed> $result Result payload.
 * @param string $target Target.
 * @return bool
 */
function local_uckk_cli_validate_faculties_require_string(
    array $data,
    string $key,
    array &$result,
    string $target
): bool {
    if (!local_uckk_cli_validate_faculties_require_key($data, $key, $result, $target)) {
        return false;
    }

    if (!is_string($data[$key]) || trim($data[$key]) === '') {
        local_uckk_cli_validate_faculties_add_message(
            $result,
            LOCAL_UCKK_CLI_VALIDATE_FACULTIES_SEVERITY_ERROR,
            'Required key must be a non-empty string: ' . $key,
            $target
        );
        return false;
    }

    return true;
}

/**
 * Add an error if a required array field is missing.
 *
 * @param array<string, mixed> $data Data.
 * @param string $key Key.
 * @param array<string, mixed> $result Result payload.
 * @param string $target Target.
 * @return bool
 */
function local_uckk_cli_validate_faculties_require_array(
    array $data,
    string $key,
    array &$result,
    string $target
): bool {
    if (!local_uckk_cli_validate_faculties_require_key($data, $key, $result, $target)) {
        return false;
    }

    if (!is_array($data[$key])) {
        local_uckk_cli_validate_faculties_add_message(
            $result,
            LOCAL_UCKK_CLI_VALIDATE_FACULTIES_SEVERITY_ERROR,
            'Required key must be an array/object: ' . $key,
            $target
        );
        return false;
    }

    return true;
}

/**
 * Add an error if a value is not in an allowed list.
 *
 * @param mixed $value Value.
 * @param array<int, string> $allowed Allowed values.
 * @param string $label Label.
 * @param array<string, mixed> $result Result payload.
 * @param string $target Target.
 */
function local_uckk_cli_validate_faculties_validate_allowed(
    $value,
    array $allowed,
    string $label,
    array &$result,
    string $target
): void {
    if (!is_string($value) || !in_array($value, $allowed, true)) {
        local_uckk_cli_validate_faculties_add_message(
            $result,
            LOCAL_UCKK_CLI_VALIDATE_FACULTIES_SEVERITY_ERROR,
            $label . ' has an invalid value.',
            $target,
            ['value' => is_scalar($value) ? (string)$value : gettype($value)]
        );
    }
}

/**
 * Validate an allowed CTA target.
 *
 * @param mixed $targetvalue CTA target.
 * @param array<string, mixed> $result Result payload.
 * @param string $target Message target.
 * @param string $label Field label.
 */
function local_uckk_cli_validate_faculties_validate_cta_target(
    $targetvalue,
    array &$result,
    string $target,
    string $label
): void {
    if (!is_string($targetvalue) || trim($targetvalue) === '') {
        local_uckk_cli_validate_faculties_add_message(
            $result,
            LOCAL_UCKK_CLI_VALIDATE_FACULTIES_SEVERITY_ERROR,
            $label . ' must be a non-empty string.',
            $target
        );
        return;
    }

    $lower = strtolower(trim($targetvalue));

    if (
        local_uckk_cli_validate_faculties_starts_with($lower, 'javascript:') ||
        local_uckk_cli_validate_faculties_starts_with($lower, 'data:') ||
        local_uckk_cli_validate_faculties_starts_with($lower, 'file:')
    ) {
        local_uckk_cli_validate_faculties_add_message(
            $result,
            LOCAL_UCKK_CLI_VALIDATE_FACULTIES_SEVERITY_ERROR,
            $label . ' uses a forbidden URI scheme.',
            $target,
            ['target' => $targetvalue]
        );
        return;
    }

    if (
        local_uckk_cli_validate_faculties_starts_with($targetvalue, '#') ||
        local_uckk_cli_validate_faculties_starts_with($targetvalue, '/local/uckk/') ||
        local_uckk_cli_validate_faculties_starts_with($targetvalue, 'https://')
    ) {
        return;
    }

    local_uckk_cli_validate_faculties_add_message(
        $result,
        LOCAL_UCKK_CLI_VALIDATE_FACULTIES_SEVERITY_ERROR,
        $label . ' must start with #anchor, /local/uckk/, or https://.',
        $target,
        ['target' => $targetvalue]
    );
}

/**
 * Return whether a faculty override is explicitly enabled for a field path.
 *
 * @param array<string, mixed> $faculty Faculty data.
 * @param string $path Field path.
 * @return bool
 */
function local_uckk_cli_validate_faculties_has_enabled_override(array $faculty, string $path): bool {
    if (empty($faculty['overrides']) || !is_array($faculty['overrides'])) {
        return false;
    }

    if (empty($faculty['overrides'][$path]) || !is_array($faculty['overrides'][$path])) {
        return false;
    }

    return !empty($faculty['overrides'][$path]['enabled']);
}

/**
 * Validate optional override definitions.
 *
 * @param array<string, mixed> $faculty Faculty data.
 * @param array<string, mixed> $result Result payload.
 * @param string $target Target.
 */
function local_uckk_cli_validate_faculties_validate_overrides(
    array $faculty,
    array &$result,
    string $target
): void {
    if (!array_key_exists('overrides', $faculty)) {
        return;
    }

    if (!is_array($faculty['overrides'])) {
        local_uckk_cli_validate_faculties_add_message(
            $result,
            LOCAL_UCKK_CLI_VALIDATE_FACULTIES_SEVERITY_ERROR,
            'overrides must be an object when present.',
            $target
        );
        return;
    }

    foreach ($faculty['overrides'] as $path => $definition) {
        if (!is_string($path) || !in_array($path, LOCAL_UCKK_CLI_VALIDATE_ALLOWED_OVERRIDES, true)) {
            local_uckk_cli_validate_faculties_add_message(
                $result,
                LOCAL_UCKK_CLI_VALIDATE_FACULTIES_SEVERITY_ERROR,
                'Override path is not allowed.',
                $target,
                ['path' => is_scalar($path) ? (string)$path : gettype($path)]
            );
            continue;
        }

        if (!is_array($definition)) {
            local_uckk_cli_validate_faculties_add_message(
                $result,
                LOCAL_UCKK_CLI_VALIDATE_FACULTIES_SEVERITY_ERROR,
                'Override definition must be an object.',
                $target,
                ['path' => $path]
            );
            continue;
        }

        if (!array_key_exists('enabled', $definition) || !is_bool($definition['enabled'])) {
            local_uckk_cli_validate_faculties_add_message(
                $result,
                LOCAL_UCKK_CLI_VALIDATE_FACULTIES_SEVERITY_ERROR,
                'Override enabled flag must be boolean.',
                $target,
                ['path' => $path]
            );
        }

        if (!empty($definition['enabled'])) {
            foreach (['reason', 'source_value', 'public_value'] as $required) {
                if (!array_key_exists($required, $definition) || !is_string($definition[$required])) {
                    local_uckk_cli_validate_faculties_add_message(
                        $result,
                        LOCAL_UCKK_CLI_VALIDATE_FACULTIES_SEVERITY_ERROR,
                        'Enabled override is missing required string field: ' . $required,
                        $target,
                        ['path' => $path]
                    );
                }
            }
        }
    }
}

/**
 * Validate the faculty manifest and return normalized indexes.
 *
 * @param array<string, mixed> $manifest Faculty manifest.
 * @param array<string, mixed> $result Result payload.
 * @return array<string, mixed>
 */
function local_uckk_cli_validate_faculties_validate_faculty_manifest(
    array $manifest,
    array &$result
): array {
    $target = LOCAL_UCKK_CLI_VALIDATE_FACULTY_MANIFEST;

    if (($manifest['schema_version'] ?? null) !== LOCAL_UCKK_CLI_VALIDATE_FACULTY_MANIFEST_SCHEMA_VERSION) {
        local_uckk_cli_validate_faculties_add_message(
            $result,
            LOCAL_UCKK_CLI_VALIDATE_FACULTIES_SEVERITY_ERROR,
            'faculty_manifest.json has invalid schema_version.',
            $target,
            ['expected' => LOCAL_UCKK_CLI_VALIDATE_FACULTY_MANIFEST_SCHEMA_VERSION]
        );
    }

    if (empty($manifest['items']) || !is_array($manifest['items'])) {
        local_uckk_cli_validate_faculties_add_message(
            $result,
            LOCAL_UCKK_CLI_VALIDATE_FACULTIES_SEVERITY_ERROR,
            'faculty_manifest.json must contain a non-empty items array.',
            $target
        );
        return [
            'items' => [],
            'by_slug' => [],
            'by_voie_id' => [],
            'by_faculty_file' => [],
            'by_atlas_file' => [],
        ];
    }

    $items = $manifest['items'];
    $result['counts']['manifest_items'] = count($items);

    if (count($items) !== LOCAL_UCKK_CLI_VALIDATE_FACULTIES_EXPECTED_COUNT) {
        local_uckk_cli_validate_faculties_add_message(
            $result,
            LOCAL_UCKK_CLI_VALIDATE_FACULTIES_SEVERITY_ERROR,
            'faculty_manifest.json must list exactly 10 faculties.',
            $target,
            ['count' => count($items)]
        );
    }

    $unique = [
        'faculty_id' => [],
        'voie_id' => [],
        'slug' => [],
        'faculty_file' => [],
        'atlas_file' => [],
        'category_idnumber' => [],
        'course_prefix' => [],
        'sortorder' => [],
    ];

    $by_slug = [];
    $by_voie_id = [];
    $by_faculty_file = [];
    $by_atlas_file = [];

    foreach ($items as $index => $item) {
        $itemtarget = $target . ':items[' . $index . ']';

        if (!is_array($item)) {
            local_uckk_cli_validate_faculties_add_message(
                $result,
                LOCAL_UCKK_CLI_VALIDATE_FACULTIES_SEVERITY_ERROR,
                'Manifest item must be an object.',
                $itemtarget
            );
            continue;
        }

        foreach (['faculty_id', 'voie_id', 'slug', 'faculty_file', 'atlas_file', 'status', 'visibility',
            'category_idnumber', 'course_prefix'] as $field) {
            local_uckk_cli_validate_faculties_require_string($item, $field, $result, $itemtarget);
        }

        if (!array_key_exists('sortorder', $item) || !is_int($item['sortorder'])) {
            local_uckk_cli_validate_faculties_add_message(
                $result,
                LOCAL_UCKK_CLI_VALIDATE_FACULTIES_SEVERITY_ERROR,
                'Manifest item sortorder must be an integer.',
                $itemtarget
            );
        } else if ($item['sortorder'] < 1 || $item['sortorder'] > LOCAL_UCKK_CLI_VALIDATE_FACULTIES_EXPECTED_COUNT) {
            local_uckk_cli_validate_faculties_add_message(
                $result,
                LOCAL_UCKK_CLI_VALIDATE_FACULTIES_SEVERITY_ERROR,
                'Manifest item sortorder must be between 1 and 10.',
                $itemtarget,
                ['sortorder' => $item['sortorder']]
            );
        }

        if (!empty($item['slug']) && !local_uckk_cli_validate_faculties_valid_slug((string)$item['slug'])) {
            local_uckk_cli_validate_faculties_add_message(
                $result,
                LOCAL_UCKK_CLI_VALIDATE_FACULTIES_SEVERITY_ERROR,
                'Manifest item slug has invalid syntax.',
                $itemtarget,
                ['slug' => (string)$item['slug']]
            );
        }

        if (!empty($item['faculty_file']) &&
                !local_uckk_cli_validate_faculties_safe_filename((string)$item['faculty_file'], '.faculty.json')) {
            local_uckk_cli_validate_faculties_add_message(
                $result,
                LOCAL_UCKK_CLI_VALIDATE_FACULTIES_SEVERITY_ERROR,
                'Manifest faculty_file must be a local *.faculty.json filename.',
                $itemtarget,
                ['faculty_file' => (string)$item['faculty_file']]
            );
        }

        if (!empty($item['atlas_file']) &&
                !local_uckk_cli_validate_faculties_safe_filename((string)$item['atlas_file'], '.json')) {
            local_uckk_cli_validate_faculties_add_message(
                $result,
                LOCAL_UCKK_CLI_VALIDATE_FACULTIES_SEVERITY_ERROR,
                'Manifest atlas_file must be a local JSON filename.',
                $itemtarget,
                ['atlas_file' => (string)$item['atlas_file']]
            );
        }

        foreach ($unique as $field => $seen) {
            if (!array_key_exists($field, $item)) {
                continue;
            }

            $value = $item[$field];
            if (!is_scalar($value)) {
                continue;
            }

            $key = (string)$value;
            if ($key === '') {
                continue;
            }

            if (array_key_exists($key, $unique[$field])) {
                local_uckk_cli_validate_faculties_add_message(
                    $result,
                    LOCAL_UCKK_CLI_VALIDATE_FACULTIES_SEVERITY_ERROR,
                    'Manifest field must be unique: ' . $field,
                    $itemtarget,
                    ['value' => $key]
                );
            } else {
                $unique[$field][$key] = true;
            }
        }

        if (!empty($item['slug']) && is_string($item['slug'])) {
            $by_slug[$item['slug']] = $item;
        }

        if (!empty($item['voie_id']) && is_string($item['voie_id'])) {
            $by_voie_id[$item['voie_id']] = $item;
        }

        if (!empty($item['faculty_file']) && is_string($item['faculty_file'])) {
            $by_faculty_file[$item['faculty_file']] = $item;
        }

        if (!empty($item['atlas_file']) && is_string($item['atlas_file'])) {
            $by_atlas_file[$item['atlas_file']] = $item;
        }
    }

    return [
        'items' => $items,
        'by_slug' => $by_slug,
        'by_voie_id' => $by_voie_id,
        'by_faculty_file' => $by_faculty_file,
        'by_atlas_file' => $by_atlas_file,
    ];
}

/**
 * Validate the Atlas manifest and return indexes.
 *
 * @param array<string, mixed> $manifest Atlas manifest.
 * @param array<string, mixed> $result Result payload.
 * @return array<string, mixed>
 */
function local_uckk_cli_validate_faculties_validate_atlas_manifest(
    array $manifest,
    array &$result
): array {
    $target = LOCAL_UCKK_CLI_VALIDATE_ATLAS_MANIFEST;

    if (($manifest['schema_version'] ?? null) !== LOCAL_UCKK_CLI_VALIDATE_ATLAS_MANIFEST_SCHEMA_VERSION) {
        local_uckk_cli_validate_faculties_add_message(
            $result,
            LOCAL_UCKK_CLI_VALIDATE_FACULTIES_SEVERITY_ERROR,
            'atlas_manifest.json has invalid schema_version.',
            $target,
            ['expected' => LOCAL_UCKK_CLI_VALIDATE_ATLAS_MANIFEST_SCHEMA_VERSION]
        );
    }

    if (($manifest['atlas_schema_version'] ?? null) !== LOCAL_UCKK_CLI_VALIDATE_ATLAS_SCHEMA_VERSION) {
        local_uckk_cli_validate_faculties_add_message(
            $result,
            LOCAL_UCKK_CLI_VALIDATE_FACULTIES_SEVERITY_ERROR,
            'atlas_manifest.json has invalid atlas_schema_version.',
            $target,
            ['expected' => LOCAL_UCKK_CLI_VALIDATE_ATLAS_SCHEMA_VERSION]
        );
    }

    if (empty($manifest['items']) || !is_array($manifest['items'])) {
        local_uckk_cli_validate_faculties_add_message(
            $result,
            LOCAL_UCKK_CLI_VALIDATE_FACULTIES_SEVERITY_ERROR,
            'atlas_manifest.json must contain a non-empty items array.',
            $target
        );
        return [
            'items' => [],
            'by_file' => [],
            'by_voie_id' => [],
        ];
    }

    $items = $manifest['items'];
    $result['counts']['atlas_manifest_items'] = count($items);

    if (count($items) !== LOCAL_UCKK_CLI_VALIDATE_FACULTIES_EXPECTED_COUNT) {
        local_uckk_cli_validate_faculties_add_message(
            $result,
            LOCAL_UCKK_CLI_VALIDATE_FACULTIES_SEVERITY_ERROR,
            'atlas_manifest.json must list exactly 10 Atlas voies.',
            $target,
            ['count' => count($items)]
        );
    }

    $by_file = [];
    $by_voie_id = [];
    $unique = [
        'voie_id' => [],
        'file' => [],
        'code' => [],
        'course_prefix' => [],
        'category_idnumber' => [],
        'sortorder' => [],
    ];

    foreach ($items as $index => $item) {
        $itemtarget = $target . ':items[' . $index . ']';

        if (!is_array($item)) {
            local_uckk_cli_validate_faculties_add_message(
                $result,
                LOCAL_UCKK_CLI_VALIDATE_FACULTIES_SEVERITY_ERROR,
                'Atlas manifest item must be an object.',
                $itemtarget
            );
            continue;
        }

        foreach (['voie_id', 'code', 'nom', 'file', 'course_prefix', 'category_idnumber'] as $field) {
            local_uckk_cli_validate_faculties_require_string($item, $field, $result, $itemtarget);
        }

        if (!array_key_exists('sortorder', $item) || !is_int($item['sortorder'])) {
            local_uckk_cli_validate_faculties_add_message(
                $result,
                LOCAL_UCKK_CLI_VALIDATE_FACULTIES_SEVERITY_ERROR,
                'Atlas manifest item sortorder must be an integer.',
                $itemtarget
            );
        }

        if (!empty($item['file']) &&
                !local_uckk_cli_validate_faculties_safe_filename((string)$item['file'], '.json')) {
            local_uckk_cli_validate_faculties_add_message(
                $result,
                LOCAL_UCKK_CLI_VALIDATE_FACULTIES_SEVERITY_ERROR,
                'Atlas manifest file must be a local JSON filename.',
                $itemtarget,
                ['file' => (string)$item['file']]
            );
        }

        foreach ($unique as $field => $seen) {
            if (!array_key_exists($field, $item) || !is_scalar($item[$field])) {
                continue;
            }

            $value = (string)$item[$field];
            if ($value === '') {
                continue;
            }

            if (array_key_exists($value, $unique[$field])) {
                local_uckk_cli_validate_faculties_add_message(
                    $result,
                    LOCAL_UCKK_CLI_VALIDATE_FACULTIES_SEVERITY_ERROR,
                    'Atlas manifest field must be unique: ' . $field,
                    $itemtarget,
                    ['value' => $value]
                );
            } else {
                $unique[$field][$value] = true;
            }
        }

        if (!empty($item['file']) && is_string($item['file'])) {
            $by_file[$item['file']] = $item;
        }

        if (!empty($item['voie_id']) && is_string($item['voie_id'])) {
            $by_voie_id[$item['voie_id']] = $item;
        }
    }

    return [
        'items' => $items,
        'by_file' => $by_file,
        'by_voie_id' => $by_voie_id,
    ];
}

/**
 * Validate a Faculty JSON profile against its manifest item.
 *
 * @param array<string, mixed> $faculty Faculty data.
 * @param array<string, mixed> $manifestitem Manifest item.
 * @param array<string, mixed> $result Result payload.
 * @param string $target Target.
 */
function local_uckk_cli_validate_faculties_validate_profile(
    array $faculty,
    array $manifestitem,
    array &$result,
    string $target
): void {
    foreach (LOCAL_UCKK_CLI_VALIDATE_FORBIDDEN_TOP_LEVEL_DUPLICATION_FIELDS as $field) {
        if (array_key_exists($field, $faculty)) {
            local_uckk_cli_validate_faculties_add_message(
                $result,
                LOCAL_UCKK_CLI_VALIDATE_FACULTIES_SEVERITY_ERROR,
                'Faculty profile must not duplicate Atlas courses at top level.',
                $target,
                ['field' => $field]
            );
        }
    }

    foreach ([
        'schema_version',
        'faculty_id',
        'voie_id',
        'slug',
        'status',
        'visibility',
    ] as $field) {
        local_uckk_cli_validate_faculties_require_string($faculty, $field, $result, $target);
    }

    foreach ([
        'source_atlas',
        'moodle',
        'identity',
        'seo',
        'hero',
        'navigation',
        'sections',
        'atlas_projection',
        'dynamic_blocks',
        'featured_blocks',
        'faq',
        'contact',
        'governance',
        'cache',
    ] as $field) {
        local_uckk_cli_validate_faculties_require_array($faculty, $field, $result, $target);
    }

    if (($faculty['schema_version'] ?? null) !== LOCAL_UCKK_CLI_VALIDATE_FACULTY_SCHEMA_VERSION) {
        local_uckk_cli_validate_faculties_add_message(
            $result,
            LOCAL_UCKK_CLI_VALIDATE_FACULTIES_SEVERITY_ERROR,
            'Faculty profile has invalid schema_version.',
            $target,
            ['expected' => LOCAL_UCKK_CLI_VALIDATE_FACULTY_SCHEMA_VERSION]
        );
    }

    foreach (['faculty_id', 'voie_id', 'slug'] as $field) {
        if (($faculty[$field] ?? null) !== ($manifestitem[$field] ?? null)) {
            local_uckk_cli_validate_faculties_add_message(
                $result,
                LOCAL_UCKK_CLI_VALIDATE_FACULTIES_SEVERITY_ERROR,
                'Faculty profile field does not match manifest: ' . $field,
                $target,
                [
                    'profile' => is_scalar($faculty[$field] ?? null) ? (string)$faculty[$field] : '',
                    'manifest' => is_scalar($manifestitem[$field] ?? null) ? (string)$manifestitem[$field] : '',
                ]
            );
        }
    }

    local_uckk_cli_validate_faculties_validate_allowed(
        $faculty['status'] ?? null,
        LOCAL_UCKK_CLI_VALIDATE_ALLOWED_FACULTY_STATUS,
        'status',
        $result,
        $target
    );

    local_uckk_cli_validate_faculties_validate_allowed(
        $faculty['visibility'] ?? null,
        LOCAL_UCKK_CLI_VALIDATE_ALLOWED_FACULTY_VISIBILITY,
        'visibility',
        $result,
        $target
    );

    if (isset($faculty['source_atlas']) && is_array($faculty['source_atlas'])) {
        local_uckk_cli_validate_faculties_validate_source_atlas($faculty['source_atlas'], $manifestitem, $result, $target);
    }

    if (isset($faculty['moodle']) && is_array($faculty['moodle'])) {
        local_uckk_cli_validate_faculties_validate_moodle($faculty['moodle'], $manifestitem, $result, $target);
    }

    if (isset($faculty['identity']) && is_array($faculty['identity'])) {
        foreach (['eyebrow', 'name', 'short_name', 'title_symbolique', 'domain', 'level', 'faculty_role', 'one_sentence'] as $field) {
            local_uckk_cli_validate_faculties_require_string($faculty['identity'], $field, $result, $target . ':identity');
        }
    }

    if (isset($faculty['seo']) && is_array($faculty['seo'])) {
        foreach (['title', 'description'] as $field) {
            local_uckk_cli_validate_faculties_require_string($faculty['seo'], $field, $result, $target . ':seo');
        }

        if (!array_key_exists('keywords', $faculty['seo']) || !is_array($faculty['seo']['keywords'])) {
            local_uckk_cli_validate_faculties_add_message(
                $result,
                LOCAL_UCKK_CLI_VALIDATE_FACULTIES_SEVERITY_ERROR,
                'seo.keywords must be an array.',
                $target
            );
        }

        $seo = strtolower((string)($faculty['seo']['title'] ?? '') . ' ' . (string)($faculty['seo']['description'] ?? ''));
        if (strpos($seo, 'université accréditée') !== false || strpos($seo, 'diplôme public accrédité') !== false) {
            local_uckk_cli_validate_faculties_add_message(
                $result,
                LOCAL_UCKK_CLI_VALIDATE_FACULTIES_SEVERITY_ERROR,
                'SEO fields must not promise public accreditation.',
                $target
            );
        }
    }

    if (isset($faculty['hero']) && is_array($faculty['hero'])) {
        local_uckk_cli_validate_faculties_validate_hero($faculty['hero'], $result, $target);
    }

    $renderanchors = local_uckk_cli_validate_faculties_collect_render_anchors($faculty);
    local_uckk_cli_validate_faculties_validate_navigation($faculty['navigation'] ?? null, $renderanchors, $result, $target);

    if (isset($faculty['sections']) && is_array($faculty['sections'])) {
        local_uckk_cli_validate_faculties_validate_sections($faculty['sections'], $result, $target);
    }

    if (isset($faculty['atlas_projection']) && is_array($faculty['atlas_projection'])) {
        local_uckk_cli_validate_faculties_validate_atlas_projection($faculty['atlas_projection'], $result, $target);
    }

    if (isset($faculty['dynamic_blocks']) && is_array($faculty['dynamic_blocks'])) {
        local_uckk_cli_validate_faculties_validate_dynamic_blocks($faculty['dynamic_blocks'], $result, $target);
    }

    if (isset($faculty['featured_blocks']) && is_array($faculty['featured_blocks'])) {
        local_uckk_cli_validate_faculties_validate_featured_blocks($faculty['featured_blocks'], $result, $target);
    }

    if (isset($faculty['faq']) && is_array($faculty['faq'])) {
        local_uckk_cli_validate_faculties_validate_faq($faculty['faq'], $result, $target);
    }

    if (isset($faculty['contact']) && is_array($faculty['contact'])) {
        local_uckk_cli_validate_faculties_validate_contact($faculty['contact'], $result, $target);
    }

    if (isset($faculty['governance']) && is_array($faculty['governance'])) {
        local_uckk_cli_validate_faculties_validate_governance($faculty['governance'], $result, $target);
    }

    if (isset($faculty['cache']) && is_array($faculty['cache'])) {
        local_uckk_cli_validate_faculties_validate_cache($faculty['cache'], $result, $target);
    }

    local_uckk_cli_validate_faculties_validate_overrides($faculty, $result, $target);
}

/**
 * Validate source_atlas block.
 *
 * @param array<string, mixed> $source Source block.
 * @param array<string, mixed> $manifestitem Manifest item.
 * @param array<string, mixed> $result Result payload.
 * @param string $target Target.
 */
function local_uckk_cli_validate_faculties_validate_source_atlas(
    array $source,
    array $manifestitem,
    array &$result,
    string $target
): void {
    foreach (['file', 'schema_version_expected', 'sync_mode'] as $field) {
        local_uckk_cli_validate_faculties_require_string($source, $field, $result, $target . ':source_atlas');
    }

    if (($source['file'] ?? null) !== ($manifestitem['atlas_file'] ?? null)) {
        local_uckk_cli_validate_faculties_add_message(
            $result,
            LOCAL_UCKK_CLI_VALIDATE_FACULTIES_SEVERITY_ERROR,
            'source_atlas.file must match manifest atlas_file.',
            $target,
            [
                'source_atlas_file' => is_scalar($source['file'] ?? null) ? (string)$source['file'] : '',
                'manifest_atlas_file' => is_scalar($manifestitem['atlas_file'] ?? null) ? (string)$manifestitem['atlas_file'] : '',
            ]
        );
    }

    if (($source['schema_version_expected'] ?? null) !== LOCAL_UCKK_CLI_VALIDATE_ATLAS_SCHEMA_VERSION) {
        local_uckk_cli_validate_faculties_add_message(
            $result,
            LOCAL_UCKK_CLI_VALIDATE_FACULTIES_SEVERITY_ERROR,
            'source_atlas.schema_version_expected has invalid value.',
            $target,
            ['expected' => LOCAL_UCKK_CLI_VALIDATE_ATLAS_SCHEMA_VERSION]
        );
    }

    local_uckk_cli_validate_faculties_validate_allowed(
        $source['sync_mode'] ?? null,
        LOCAL_UCKK_CLI_VALIDATE_ALLOWED_SYNC_MODE,
        'source_atlas.sync_mode',
        $result,
        $target
    );
}

/**
 * Validate moodle block.
 *
 * @param array<string, mixed> $moodle Moodle block.
 * @param array<string, mixed> $manifestitem Manifest item.
 * @param array<string, mixed> $result Result payload.
 * @param string $target Target.
 */
function local_uckk_cli_validate_faculties_validate_moodle(
    array $moodle,
    array $manifestitem,
    array &$result,
    string $target
): void {
    foreach (['category_idnumber', 'course_prefix', 'enrolment_visibility', 'hub_course_idnumber'] as $field) {
        local_uckk_cli_validate_faculties_require_string($moodle, $field, $result, $target . ':moodle');
    }

    if (!array_key_exists('category_id', $moodle)) {
        local_uckk_cli_validate_faculties_add_message(
            $result,
            LOCAL_UCKK_CLI_VALIDATE_FACULTIES_SEVERITY_ERROR,
            'moodle.category_id key is required and may be null.',
            $target
        );
    } else if ($moodle['category_id'] !== null && !is_int($moodle['category_id'])) {
        local_uckk_cli_validate_faculties_add_message(
            $result,
            LOCAL_UCKK_CLI_VALIDATE_FACULTIES_SEVERITY_ERROR,
            'moodle.category_id must be null or integer.',
            $target
        );
    }

    if (!array_key_exists('public_course_listing', $moodle) || !is_bool($moodle['public_course_listing'])) {
        local_uckk_cli_validate_faculties_add_message(
            $result,
            LOCAL_UCKK_CLI_VALIDATE_FACULTIES_SEVERITY_ERROR,
            'moodle.public_course_listing must be boolean.',
            $target
        );
    }

    if (($moodle['category_idnumber'] ?? null) !== ($manifestitem['category_idnumber'] ?? null)) {
        local_uckk_cli_validate_faculties_add_message(
            $result,
            LOCAL_UCKK_CLI_VALIDATE_FACULTIES_SEVERITY_ERROR,
            'moodle.category_idnumber must match manifest category_idnumber.',
            $target
        );
    }

    if (($moodle['course_prefix'] ?? null) !== ($manifestitem['course_prefix'] ?? null)) {
        local_uckk_cli_validate_faculties_add_message(
            $result,
            LOCAL_UCKK_CLI_VALIDATE_FACULTIES_SEVERITY_ERROR,
            'moodle.course_prefix must match manifest course_prefix.',
            $target
        );
    }

    local_uckk_cli_validate_faculties_validate_allowed(
        $moodle['enrolment_visibility'] ?? null,
        LOCAL_UCKK_CLI_VALIDATE_ALLOWED_ENROLMENT_VISIBILITY,
        'moodle.enrolment_visibility',
        $result,
        $target
    );
}

/**
 * Validate hero block.
 *
 * @param array<string, mixed> $hero Hero block.
 * @param array<string, mixed> $result Result payload.
 * @param string $target Target.
 */
function local_uckk_cli_validate_faculties_validate_hero(
    array $hero,
    array &$result,
    string $target
): void {
    foreach (['title', 'subtitle', 'summary'] as $field) {
        local_uckk_cli_validate_faculties_require_string($hero, $field, $result, $target . ':hero');
    }

    foreach (['primary_cta', 'secondary_cta'] as $ctakey) {
        if (!array_key_exists($ctakey, $hero) || !is_array($hero[$ctakey])) {
            local_uckk_cli_validate_faculties_add_message(
                $result,
                LOCAL_UCKK_CLI_VALIDATE_FACULTIES_SEVERITY_ERROR,
                'hero.' . $ctakey . ' must be an object.',
                $target
            );
            continue;
        }

        local_uckk_cli_validate_faculties_require_string($hero[$ctakey], 'label', $result, $target . ':hero.' . $ctakey);
        local_uckk_cli_validate_faculties_validate_cta_target(
            $hero[$ctakey]['target'] ?? null,
            $result,
            $target,
            'hero.' . $ctakey . '.target'
        );
    }
}

/**
 * Collect valid anchors rendered from sections and documented generated blocks.
 *
 * @param array<string, mixed> $faculty Faculty profile.
 * @return array<string, bool>
 */
function local_uckk_cli_validate_faculties_collect_render_anchors(array $faculty): array {
    $anchors = [
        'programme' => true,
        'cours' => true,
        'projet-final' => true,
        'faq' => true,
        'contact' => true,
    ];

    if (!empty($faculty['sections']) && is_array($faculty['sections'])) {
        foreach ($faculty['sections'] as $section) {
            if (is_array($section) && !empty($section['id']) && is_string($section['id'])) {
                $anchors[$section['id']] = true;
            }
        }
    }

    if (!empty($faculty['dynamic_blocks']) && is_array($faculty['dynamic_blocks'])) {
        foreach ($faculty['dynamic_blocks'] as $block) {
            if (is_array($block) && !empty($block['id']) && is_string($block['id'])) {
                $anchors[$block['id']] = true;
            }
        }
    }

    return $anchors;
}

/**
 * Validate navigation targets.
 *
 * @param mixed $navigation Navigation value.
 * @param array<string, bool> $anchors Valid anchors.
 * @param array<string, mixed> $result Result payload.
 * @param string $target Target.
 */
function local_uckk_cli_validate_faculties_validate_navigation(
    $navigation,
    array $anchors,
    array &$result,
    string $target
): void {
    if (!is_array($navigation)) {
        return;
    }

    foreach ($navigation as $index => $item) {
        $itemtarget = $target . ':navigation[' . $index . ']';

        if (!is_array($item)) {
            local_uckk_cli_validate_faculties_add_message(
                $result,
                LOCAL_UCKK_CLI_VALIDATE_FACULTIES_SEVERITY_ERROR,
                'Navigation item must be an object.',
                $itemtarget
            );
            continue;
        }

        local_uckk_cli_validate_faculties_require_string($item, 'label', $result, $itemtarget);
        local_uckk_cli_validate_faculties_require_string($item, 'target', $result, $itemtarget);

        if (!empty($item['target']) && is_string($item['target']) &&
                local_uckk_cli_validate_faculties_starts_with($item['target'], '#')) {
            $anchor = substr($item['target'], 1);
            if ($anchor === '' || empty($anchors[$anchor])) {
                local_uckk_cli_validate_faculties_add_message(
                    $result,
                    LOCAL_UCKK_CLI_VALIDATE_FACULTIES_SEVERITY_ERROR,
                    'Navigation target is orphaned.',
                    $itemtarget,
                    ['target' => $item['target']]
                );
            }
        } else if (!empty($item['target'])) {
            local_uckk_cli_validate_faculties_validate_cta_target($item['target'], $result, $itemtarget, 'navigation.target');
        }
    }
}

/**
 * Validate sections.
 *
 * @param array<int, mixed> $sections Sections.
 * @param array<string, mixed> $result Result payload.
 * @param string $target Target.
 */
function local_uckk_cli_validate_faculties_validate_sections(
    array $sections,
    array &$result,
    string $target
): void {
    $seenids = [];

    foreach ($sections as $index => $section) {
        $itemtarget = $target . ':sections[' . $index . ']';

        if (!is_array($section)) {
            local_uckk_cli_validate_faculties_add_message(
                $result,
                LOCAL_UCKK_CLI_VALIDATE_FACULTIES_SEVERITY_ERROR,
                'Section must be an object.',
                $itemtarget
            );
            continue;
        }

        foreach (['id', 'type', 'title'] as $field) {
            local_uckk_cli_validate_faculties_require_string($section, $field, $result, $itemtarget);
        }

        if (!empty($section['id']) && is_string($section['id'])) {
            if (array_key_exists($section['id'], $seenids)) {
                local_uckk_cli_validate_faculties_add_message(
                    $result,
                    LOCAL_UCKK_CLI_VALIDATE_FACULTIES_SEVERITY_ERROR,
                    'Section id must be unique.',
                    $itemtarget,
                    ['id' => $section['id']]
                );
            }
            $seenids[$section['id']] = true;
        }

        local_uckk_cli_validate_faculties_validate_allowed(
            $section['type'] ?? null,
            LOCAL_UCKK_CLI_VALIDATE_ALLOWED_SECTION_TYPES,
            'section.type',
            $result,
            $itemtarget
        );
    }
}

/**
 * Validate atlas_projection.
 *
 * @param array<string, mixed> $projection Projection block.
 * @param array<string, mixed> $result Result payload.
 * @param string $target Target.
 */
function local_uckk_cli_validate_faculties_validate_atlas_projection(
    array $projection,
    array &$result,
    string $target
): void {
    foreach (LOCAL_UCKK_CLI_VALIDATE_REQUIRED_ATLAS_PROJECTION_KEYS as $key) {
        if (!array_key_exists($key, $projection)) {
            local_uckk_cli_validate_faculties_add_message(
                $result,
                LOCAL_UCKK_CLI_VALIDATE_FACULTIES_SEVERITY_ERROR,
                'atlas_projection is missing required key: ' . $key,
                $target
            );
            continue;
        }

        if (!is_bool($projection[$key])) {
            local_uckk_cli_validate_faculties_add_message(
                $result,
                LOCAL_UCKK_CLI_VALIDATE_FACULTIES_SEVERITY_ERROR,
                'atlas_projection key must be boolean: ' . $key,
                $target
            );
        }
    }
}

/**
 * Validate dynamic blocks.
 *
 * @param array<int, mixed> $blocks Dynamic blocks.
 * @param array<string, mixed> $result Result payload.
 * @param string $target Target.
 */
function local_uckk_cli_validate_faculties_validate_dynamic_blocks(
    array $blocks,
    array &$result,
    string $target
): void {
    $seenids = [];

    foreach ($blocks as $index => $block) {
        $itemtarget = $target . ':dynamic_blocks[' . $index . ']';

        if (!is_array($block)) {
            local_uckk_cli_validate_faculties_add_message(
                $result,
                LOCAL_UCKK_CLI_VALIDATE_FACULTIES_SEVERITY_ERROR,
                'Dynamic block must be an object.',
                $itemtarget
            );
            continue;
        }

        foreach (['id', 'type', 'title', 'visibility', 'empty_state'] as $field) {
            local_uckk_cli_validate_faculties_require_string($block, $field, $result, $itemtarget);
        }

        if (!empty($block['id']) && is_string($block['id'])) {
            if (array_key_exists($block['id'], $seenids)) {
                local_uckk_cli_validate_faculties_add_message(
                    $result,
                    LOCAL_UCKK_CLI_VALIDATE_FACULTIES_SEVERITY_ERROR,
                    'Dynamic block id must be unique.',
                    $itemtarget,
                    ['id' => $block['id']]
                );
            }
            $seenids[$block['id']] = true;
        }

        local_uckk_cli_validate_faculties_validate_allowed(
            $block['type'] ?? null,
            LOCAL_UCKK_CLI_VALIDATE_ALLOWED_DYNAMIC_BLOCK_TYPES,
            'dynamic_blocks.type',
            $result,
            $itemtarget
        );

        local_uckk_cli_validate_faculties_validate_allowed(
            $block['visibility'] ?? null,
            LOCAL_UCKK_CLI_VALIDATE_ALLOWED_FACULTY_VISIBILITY,
            'dynamic_blocks.visibility',
            $result,
            $itemtarget
        );

        if (!array_key_exists('limit', $block) || !is_int($block['limit']) || $block['limit'] < 0) {
            local_uckk_cli_validate_faculties_add_message(
                $result,
                LOCAL_UCKK_CLI_VALIDATE_FACULTIES_SEVERITY_ERROR,
                'dynamic_blocks.limit must be a non-negative integer.',
                $itemtarget
            );
        }

        if (empty($block['source']) || !is_array($block['source'])) {
            local_uckk_cli_validate_faculties_add_message(
                $result,
                LOCAL_UCKK_CLI_VALIDATE_FACULTIES_SEVERITY_ERROR,
                'dynamic_blocks.source must be an object.',
                $itemtarget
            );
            continue;
        }

        local_uckk_cli_validate_faculties_require_string($block['source'], 'provider', $result, $itemtarget . ':source');
        local_uckk_cli_validate_faculties_validate_allowed(
            $block['source']['provider'] ?? null,
            LOCAL_UCKK_CLI_VALIDATE_ALLOWED_DYNAMIC_PROVIDERS,
            'dynamic_blocks.source.provider',
            $result,
            $itemtarget
        );
    }
}

/**
 * Validate featured blocks.
 *
 * @param array<int, mixed> $blocks Featured blocks.
 * @param array<string, mixed> $result Result payload.
 * @param string $target Target.
 */
function local_uckk_cli_validate_faculties_validate_featured_blocks(
    array $blocks,
    array &$result,
    string $target
): void {
    foreach ($blocks as $index => $block) {
        $itemtarget = $target . ':featured_blocks[' . $index . ']';

        if (!is_array($block)) {
            local_uckk_cli_validate_faculties_add_message(
                $result,
                LOCAL_UCKK_CLI_VALIDATE_FACULTIES_SEVERITY_ERROR,
                'Featured block must be an object.',
                $itemtarget
            );
            continue;
        }

        foreach (['type', 'title', 'body'] as $field) {
            local_uckk_cli_validate_faculties_require_string($block, $field, $result, $itemtarget);
        }

        local_uckk_cli_validate_faculties_validate_allowed(
            $block['type'] ?? null,
            LOCAL_UCKK_CLI_VALIDATE_ALLOWED_FEATURED_BLOCK_TYPES,
            'featured_blocks.type',
            $result,
            $itemtarget
        );
    }
}

/**
 * Validate FAQ items.
 *
 * @param array<int, mixed> $faq FAQ array.
 * @param array<string, mixed> $result Result payload.
 * @param string $target Target.
 */
function local_uckk_cli_validate_faculties_validate_faq(
    array $faq,
    array &$result,
    string $target
): void {
    foreach ($faq as $index => $item) {
        $itemtarget = $target . ':faq[' . $index . ']';

        if (!is_array($item)) {
            local_uckk_cli_validate_faculties_add_message(
                $result,
                LOCAL_UCKK_CLI_VALIDATE_FACULTIES_SEVERITY_ERROR,
                'FAQ item must be an object.',
                $itemtarget
            );
            continue;
        }

        local_uckk_cli_validate_faculties_require_string($item, 'question', $result, $itemtarget);
        local_uckk_cli_validate_faculties_require_string($item, 'answer', $result, $itemtarget);

        $answer = strtolower((string)($item['answer'] ?? ''));
        if (strpos($answer, 'diplôme public accrédité') !== false || strpos($answer, 'université accréditée') !== false) {
            local_uckk_cli_validate_faculties_add_message(
                $result,
                LOCAL_UCKK_CLI_VALIDATE_FACULTIES_SEVERITY_ERROR,
                'FAQ answer must not promise public accreditation.',
                $itemtarget
            );
        }
    }
}

/**
 * Validate contact block.
 *
 * @param array<string, mixed> $contact Contact block.
 * @param array<string, mixed> $result Result payload.
 * @param string $target Target.
 */
function local_uckk_cli_validate_faculties_validate_contact(
    array $contact,
    array &$result,
    string $target
): void {
    foreach (['label', 'body'] as $field) {
        local_uckk_cli_validate_faculties_require_string($contact, $field, $result, $target . ':contact');
    }

    if (!array_key_exists('email', $contact) || !is_string($contact['email'])) {
        local_uckk_cli_validate_faculties_add_message(
            $result,
            LOCAL_UCKK_CLI_VALIDATE_FACULTIES_SEVERITY_ERROR,
            'contact.email must exist and may be an empty string.',
            $target
        );
    }

    if (!array_key_exists('cta', $contact) || !is_array($contact['cta'])) {
        local_uckk_cli_validate_faculties_add_message(
            $result,
            LOCAL_UCKK_CLI_VALIDATE_FACULTIES_SEVERITY_ERROR,
            'contact.cta must be an object.',
            $target
        );
        return;
    }

    local_uckk_cli_validate_faculties_require_string($contact['cta'], 'label', $result, $target . ':contact.cta');

    if (array_key_exists('target', $contact['cta']) && is_string($contact['cta']['target']) && $contact['cta']['target'] !== '') {
        local_uckk_cli_validate_faculties_validate_cta_target($contact['cta']['target'], $result, $target, 'contact.cta.target');
    } else if (!array_key_exists('target', $contact['cta'])) {
        local_uckk_cli_validate_faculties_add_message(
            $result,
            LOCAL_UCKK_CLI_VALIDATE_FACULTIES_SEVERITY_ERROR,
            'contact.cta.target key is required and may be an empty string.',
            $target
        );
    }
}

/**
 * Validate governance block.
 *
 * @param array<string, mixed> $governance Governance block.
 * @param array<string, mixed> $result Result payload.
 * @param string $target Target.
 */
function local_uckk_cli_validate_faculties_validate_governance(
    array $governance,
    array &$result,
    string $target
): void {
    foreach (['owner', 'editorial_status', 'review_notes'] as $field) {
        local_uckk_cli_validate_faculties_require_string($governance, $field, $result, $target . ':governance');
    }

    if (($governance['owner'] ?? null) !== LOCAL_UCKK_CLI_VALIDATE_FACULTIES_COMPONENT) {
        local_uckk_cli_validate_faculties_add_message(
            $result,
            LOCAL_UCKK_CLI_VALIDATE_FACULTIES_SEVERITY_ERROR,
            'governance.owner must be local_uckk.',
            $target
        );
    }

    local_uckk_cli_validate_faculties_validate_allowed(
        $governance['editorial_status'] ?? null,
        LOCAL_UCKK_CLI_VALIDATE_ALLOWED_EDITORIAL_STATUS,
        'governance.editorial_status',
        $result,
        $target
    );

    if (!array_key_exists('last_reviewed', $governance)) {
        local_uckk_cli_validate_faculties_add_message(
            $result,
            LOCAL_UCKK_CLI_VALIDATE_FACULTIES_SEVERITY_ERROR,
            'governance.last_reviewed key is required and may be null.',
            $target
        );
    } else if ($governance['last_reviewed'] !== null && !is_string($governance['last_reviewed'])) {
        local_uckk_cli_validate_faculties_add_message(
            $result,
            LOCAL_UCKK_CLI_VALIDATE_FACULTIES_SEVERITY_ERROR,
            'governance.last_reviewed must be null or string.',
            $target
        );
    }

    if (empty($governance['public_claims_guardrails']) || !is_array($governance['public_claims_guardrails'])) {
        local_uckk_cli_validate_faculties_add_message(
            $result,
            LOCAL_UCKK_CLI_VALIDATE_FACULTIES_SEVERITY_ERROR,
            'governance.public_claims_guardrails must be a non-empty array.',
            $target
        );
    }
}

/**
 * Validate cache block.
 *
 * @param array<string, mixed> $cache Cache block.
 * @param array<string, mixed> $result Result payload.
 * @param string $target Target.
 */
function local_uckk_cli_validate_faculties_validate_cache(
    array $cache,
    array &$result,
    string $target
): void {
    if (!array_key_exists('enabled', $cache) || !is_bool($cache['enabled'])) {
        local_uckk_cli_validate_faculties_add_message(
            $result,
            LOCAL_UCKK_CLI_VALIDATE_FACULTIES_SEVERITY_ERROR,
            'cache.enabled must be boolean.',
            $target
        );
    }

    if (!array_key_exists('ttl_seconds', $cache) || !is_int($cache['ttl_seconds']) || $cache['ttl_seconds'] < 0) {
        local_uckk_cli_validate_faculties_add_message(
            $result,
            LOCAL_UCKK_CLI_VALIDATE_FACULTIES_SEVERITY_ERROR,
            'cache.ttl_seconds must be a non-negative integer.',
            $target
        );
    }
}

/**
 * Validate minimal Atlas file fields required by Faculty cross-validation.
 *
 * @param array<string, mixed> $atlas Atlas data.
 * @param array<string, mixed> $atlasmanifestitem Atlas manifest item.
 * @param array<string, mixed> $result Result payload.
 * @param string $target Target.
 */
function local_uckk_cli_validate_faculties_validate_atlas_file(
    array $atlas,
    array $atlasmanifestitem,
    array &$result,
    string $target
): void {
    foreach (['schema_version', 'voie_id', 'code'] as $field) {
        local_uckk_cli_validate_faculties_require_string($atlas, $field, $result, $target);
    }

    if (($atlas['schema_version'] ?? null) !== LOCAL_UCKK_CLI_VALIDATE_ATLAS_SCHEMA_VERSION) {
        local_uckk_cli_validate_faculties_add_message(
            $result,
            LOCAL_UCKK_CLI_VALIDATE_FACULTIES_SEVERITY_ERROR,
            'Atlas file has invalid schema_version.',
            $target,
            ['expected' => LOCAL_UCKK_CLI_VALIDATE_ATLAS_SCHEMA_VERSION]
        );
    }

    foreach (['voie_id', 'code'] as $field) {
        if (($atlas[$field] ?? null) !== ($atlasmanifestitem[$field] ?? null)) {
            local_uckk_cli_validate_faculties_add_message(
                $result,
                LOCAL_UCKK_CLI_VALIDATE_FACULTIES_SEVERITY_ERROR,
                'Atlas file field does not match atlas_manifest.json: ' . $field,
                $target
            );
        }
    }

    foreach (['titre_symbolique', 'domaine_operatoire', 'niveau_vise'] as $field) {
        if (!array_key_exists($field, $atlas) || !is_string($atlas[$field]) || trim($atlas[$field]) === '') {
            local_uckk_cli_validate_faculties_add_message(
                $result,
                LOCAL_UCKK_CLI_VALIDATE_FACULTIES_SEVERITY_WARNING,
                'Atlas file is missing a field used for Faculty cross-validation: ' . $field,
                $target
            );
        }
    }
}

/**
 * Validate Faculty/Atlas cross-file consistency.
 *
 * @param array<string, mixed> $faculty Faculty data.
 * @param array<string, mixed> $atlas Atlas data.
 * @param array<string, mixed> $atlasmanifestitem Atlas manifest item.
 * @param array<string, mixed> $validvoieids Valid voie_id index.
 * @param array<string, mixed> $result Result payload.
 * @param string $target Faculty target.
 */
function local_uckk_cli_validate_faculties_validate_cross_file(
    array $faculty,
    array $atlas,
    array $atlasmanifestitem,
    array $validvoieids,
    array &$result,
    string $target
): void {
    if (($faculty['voie_id'] ?? null) !== ($atlas['voie_id'] ?? null)) {
        local_uckk_cli_validate_faculties_add_message(
            $result,
            LOCAL_UCKK_CLI_VALIDATE_FACULTIES_SEVERITY_ERROR,
            'faculty.voie_id must match atlas.voie_id.',
            $target
        );
    }

    if (($faculty['moodle']['course_prefix'] ?? null) !== ($atlas['code'] ?? null)) {
        local_uckk_cli_validate_faculties_add_message(
            $result,
            LOCAL_UCKK_CLI_VALIDATE_FACULTIES_SEVERITY_ERROR,
            'faculty.moodle.course_prefix must match atlas.code.',
            $target
        );
    }

    if (($faculty['source_atlas']['file'] ?? null) !== ($atlasmanifestitem['file'] ?? null)) {
        local_uckk_cli_validate_faculties_add_message(
            $result,
            LOCAL_UCKK_CLI_VALIDATE_FACULTIES_SEVERITY_ERROR,
            'faculty.source_atlas.file must match the linked Atlas manifest file.',
            $target
        );
    }

    local_uckk_cli_validate_faculties_compare_atlas_identity(
        $faculty,
        $atlas,
        'identity.title_symbolique',
        'titre_symbolique',
        $result,
        $target
    );

    local_uckk_cli_validate_faculties_compare_atlas_identity(
        $faculty,
        $atlas,
        'identity.domain',
        'domaine_operatoire',
        $result,
        $target
    );

    local_uckk_cli_validate_faculties_compare_atlas_identity(
        $faculty,
        $atlas,
        'identity.level',
        'niveau_vise',
        $result,
        $target
    );

    local_uckk_cli_validate_faculties_validate_atlas_relations($atlas, $validvoieids, $result, $target);
}

/**
 * Compare a Faculty field with an Atlas field unless an override is enabled.
 *
 * @param array<string, mixed> $faculty Faculty data.
 * @param array<string, mixed> $atlas Atlas data.
 * @param string $facultypath Faculty dot path.
 * @param string $atlasfield Atlas field.
 * @param array<string, mixed> $result Result payload.
 * @param string $target Target.
 */
function local_uckk_cli_validate_faculties_compare_atlas_identity(
    array $faculty,
    array $atlas,
    string $facultypath,
    string $atlasfield,
    array &$result,
    string $target
): void {
    if (local_uckk_cli_validate_faculties_has_enabled_override($faculty, $facultypath)) {
        return;
    }

    $facultyvalue = local_uckk_cli_validate_faculties_get_path($faculty, $facultypath);
    $atlasvalue = $atlas[$atlasfield] ?? null;

    if (!is_string($facultyvalue) || !is_string($atlasvalue) || $atlasvalue === '') {
        return;
    }

    if ($facultyvalue !== $atlasvalue) {
        local_uckk_cli_validate_faculties_add_message(
            $result,
            LOCAL_UCKK_CLI_VALIDATE_FACULTIES_SEVERITY_ERROR,
            $facultypath . ' must match atlas.' . $atlasfield . ' unless an explicit override is declared.',
            $target,
            [
                'faculty_field' => $facultypath,
                'atlas_field' => $atlasfield,
            ]
        );
    }
}

/**
 * Validate Atlas relations_intervoies references when present.
 *
 * @param array<string, mixed> $atlas Atlas data.
 * @param array<string, mixed> $validvoieids Valid voie IDs.
 * @param array<string, mixed> $result Result payload.
 * @param string $target Target.
 */
function local_uckk_cli_validate_faculties_validate_atlas_relations(
    array $atlas,
    array $validvoieids,
    array &$result,
    string $target
): void {
    if (!array_key_exists('relations_intervoies', $atlas)) {
        return;
    }

    if (!is_array($atlas['relations_intervoies'])) {
        local_uckk_cli_validate_faculties_add_message(
            $result,
            LOCAL_UCKK_CLI_VALIDATE_FACULTIES_SEVERITY_ERROR,
            'atlas.relations_intervoies must be an array when present.',
            $target
        );
        return;
    }

    foreach ($atlas['relations_intervoies'] as $index => $relation) {
        $relationid = null;

        if (is_string($relation)) {
            $relationid = $relation;
        } else if (is_array($relation) && !empty($relation['voie_id']) && is_string($relation['voie_id'])) {
            $relationid = $relation['voie_id'];
        }

        if ($relationid === null) {
            local_uckk_cli_validate_faculties_add_message(
                $result,
                LOCAL_UCKK_CLI_VALIDATE_FACULTIES_SEVERITY_WARNING,
                'atlas.relations_intervoies item has no voie_id that can be checked.',
                $target,
                ['index' => $index]
            );
            continue;
        }

        if (empty($validvoieids[$relationid])) {
            local_uckk_cli_validate_faculties_add_message(
                $result,
                LOCAL_UCKK_CLI_VALIDATE_FACULTIES_SEVERITY_ERROR,
                'atlas.relations_intervoies references an unknown voie_id.',
                $target,
                ['voie_id' => $relationid]
            );
        }
    }
}

/**
 * Print JSON result.
 *
 * @param array<string, mixed> $result Result payload.
 */
function local_uckk_cli_validate_faculties_print_json(array $result): void {
    cli_writeln(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
}

/**
 * Print text result.
 *
 * @param array<string, mixed> $result Result payload.
 * @param bool $quiet Whether to suppress message list.
 */
function local_uckk_cli_validate_faculties_print_text(array $result, bool $quiet): void {
    cli_writeln('UCKK faculty validation');
    cli_writeln('Status: ' . $result['status']);
    cli_writeln('Summary: ' . $result['summary']);
    cli_writeln('');

    cli_writeln('Counts:');
    foreach ($result['counts'] as $key => $value) {
        cli_writeln('  ' . $key . ': ' . $value);
    }

    if ($quiet) {
        return;
    }

    if (empty($result['messages'])) {
        cli_writeln('');
        cli_writeln('No validation messages.');
        return;
    }

    cli_writeln('');
    cli_writeln('Messages:');

    foreach ($result['messages'] as $message) {
        $prefix = '[' . strtoupper((string)$message['severity']) . ']';
        $target = empty($message['target']) ? '' : ' ' . $message['target'] . ':';
        cli_writeln($prefix . $target . ' ' . $message['message']);
    }
}

/**
 * Resolve CLI exit code.
 *
 * @param array<string, mixed> $result Result payload.
 * @param bool $strict Strict mode.
 * @return int
 */
function local_uckk_cli_validate_faculties_exit_code(array $result, bool $strict): int {
    if ($result['counts']['errors'] > 0) {
        return LOCAL_UCKK_CLI_VALIDATE_FACULTIES_EXIT_ERROR;
    }

    if ($strict && $result['counts']['warnings'] > 0) {
        return LOCAL_UCKK_CLI_VALIDATE_FACULTIES_EXIT_ERROR;
    }

    return LOCAL_UCKK_CLI_VALIDATE_FACULTIES_EXIT_OK;
}

[$options, $unrecognized] = cli_get_params(
    [
        'help' => false,
        'slug' => null,
        'json' => false,
        'strict' => false,
        'quiet' => false,
    ],
    [
        'h' => 'help',
    ]
);

if (!empty($unrecognized)) {
    cli_error('Unknown option(s): ' . implode(', ', $unrecognized), LOCAL_UCKK_CLI_VALIDATE_FACULTIES_EXIT_ERROR);
}

if (!empty($options['help'])) {
    cli_writeln(local_uckk_cli_validate_faculties_help());
    exit(LOCAL_UCKK_CLI_VALIDATE_FACULTIES_EXIT_OK);
}

$slug = $options['slug'] === null ? null : trim((string)$options['slug']);
if ($slug !== null && $slug !== '' && !local_uckk_cli_validate_faculties_valid_slug($slug)) {
    cli_error('Invalid --slug value.', LOCAL_UCKK_CLI_VALIDATE_FACULTIES_EXIT_ERROR);
}

$result = local_uckk_cli_validate_faculties_new_result();
$basepath = local_uckk_cli_validate_faculties_path($CFG->dirroot, 'local/uckk');

$facultymanifestpath = local_uckk_cli_validate_faculties_path($basepath, LOCAL_UCKK_CLI_VALIDATE_FACULTY_MANIFEST);
$atlasmanifestpath = local_uckk_cli_validate_faculties_path($basepath, LOCAL_UCKK_CLI_VALIDATE_ATLAS_MANIFEST);

try {
    $facultymanifest = local_uckk_cli_validate_faculties_decode_json_file(
        $facultymanifestpath,
        $result,
        LOCAL_UCKK_CLI_VALIDATE_FACULTY_MANIFEST
    );

    $atlasmanifest = local_uckk_cli_validate_faculties_decode_json_file(
        $atlasmanifestpath,
        $result,
        LOCAL_UCKK_CLI_VALIDATE_ATLAS_MANIFEST
    );

    $facultyindex = [
        'items' => [],
        'by_slug' => [],
        'by_voie_id' => [],
        'by_faculty_file' => [],
        'by_atlas_file' => [],
    ];

    $atlasindex = [
        'items' => [],
        'by_file' => [],
        'by_voie_id' => [],
    ];

    if ($facultymanifest !== null) {
        $facultyindex = local_uckk_cli_validate_faculties_validate_faculty_manifest($facultymanifest, $result);
    }

    if ($atlasmanifest !== null) {
        $atlasindex = local_uckk_cli_validate_faculties_validate_atlas_manifest($atlasmanifest, $result);
    }

    $items = $facultyindex['items'];

    if ($slug !== null && $slug !== '') {
        if (empty($facultyindex['by_slug'][$slug])) {
            local_uckk_cli_validate_faculties_add_message(
                $result,
                LOCAL_UCKK_CLI_VALIDATE_FACULTIES_SEVERITY_ERROR,
                'Requested slug is not listed in faculty_manifest.json.',
                LOCAL_UCKK_CLI_VALIDATE_FACULTY_MANIFEST,
                ['slug' => $slug]
            );
            $items = [];
        } else {
            $items = [$facultyindex['by_slug'][$slug]];
        }
    }

    foreach ($items as $item) {
        if (!is_array($item)) {
            continue;
        }

        $facultyfile = (string)($item['faculty_file'] ?? '');
        $atlasfile = (string)($item['atlas_file'] ?? '');

        if (!local_uckk_cli_validate_faculties_safe_filename($facultyfile, '.faculty.json')) {
            continue;
        }

        if (!local_uckk_cli_validate_faculties_safe_filename($atlasfile, '.json')) {
            continue;
        }

        $facultytarget = LOCAL_UCKK_CLI_VALIDATE_FACULTY_DIR . '/' . $facultyfile;
        $atlastarget = LOCAL_UCKK_CLI_VALIDATE_ATLAS_DIR . '/' . $atlasfile;

        $facultypath = local_uckk_cli_validate_faculties_path($basepath, $facultytarget);
        $atlaspath = local_uckk_cli_validate_faculties_path($basepath, $atlastarget);

        $faculty = local_uckk_cli_validate_faculties_decode_json_file($facultypath, $result, $facultytarget);
        if ($faculty === null) {
            continue;
        }

        $result['counts']['faculties_checked']++;

        local_uckk_cli_validate_faculties_validate_profile($faculty, $item, $result, $facultytarget);

        $atlasmanifestitem = $atlasindex['by_file'][$atlasfile] ?? null;
        if (!is_array($atlasmanifestitem)) {
            local_uckk_cli_validate_faculties_add_message(
                $result,
                LOCAL_UCKK_CLI_VALIDATE_FACULTIES_SEVERITY_ERROR,
                'Faculty manifest atlas_file is not listed in atlas_manifest.json.',
                $facultytarget,
                ['atlas_file' => $atlasfile]
            );
            continue;
        }

        $atlas = local_uckk_cli_validate_faculties_decode_json_file($atlaspath, $result, $atlastarget);
        if ($atlas === null) {
            continue;
        }

        $result['counts']['atlas_files_checked']++;

        local_uckk_cli_validate_faculties_validate_atlas_file($atlas, $atlasmanifestitem, $result, $atlastarget);
        local_uckk_cli_validate_faculties_validate_cross_file(
            $faculty,
            $atlas,
            $atlasmanifestitem,
            $atlasindex['by_voie_id'],
            $result,
            $facultytarget
        );
    }

    $result = local_uckk_cli_validate_faculties_finalise($result);

    if (!empty($options['json'])) {
        local_uckk_cli_validate_faculties_print_json($result);
    } else {
        local_uckk_cli_validate_faculties_print_text($result, !empty($options['quiet']));
    }

    exit(local_uckk_cli_validate_faculties_exit_code($result, !empty($options['strict'])));
} catch (Throwable $exception) {
    $result['ok'] = false;
    $result['status'] = LOCAL_UCKK_CLI_VALIDATE_FACULTIES_STATUS_FAILED;
    $result['summary'] = 'UCKK faculty validation crashed.';
    local_uckk_cli_validate_faculties_add_message(
        $result,
        LOCAL_UCKK_CLI_VALIDATE_FACULTIES_SEVERITY_ERROR,
        $exception->getMessage(),
        LOCAL_UCKK_CLI_VALIDATE_FACULTIES_ACTION,
        ['exception' => get_class($exception)]
    );

    if (!empty($options['json'])) {
        local_uckk_cli_validate_faculties_print_json($result);
    } else {
        local_uckk_cli_validate_faculties_print_text($result, !empty($options['quiet']));

        if (debugging('', DEBUG_DEVELOPER)) {
            cli_writeln('');
            cli_writeln($exception->getTraceAsString());
        }
    }

    exit(LOCAL_UCKK_CLI_VALIDATE_FACULTIES_EXIT_ERROR);
}

