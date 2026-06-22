<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

/**
 * CLI purge command for UCKK faculty public page caches.
 *
 * @package    local_uckk
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

define('CLI_SCRIPT', true);

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');
require_once($CFG->dirroot . '/cache/lib.php');

use context_system;

const LOCAL_UCKK_CLI_PURGE_FACULTY_CACHE_COMPONENT = 'local_uckk';
const LOCAL_UCKK_CLI_PURGE_FACULTY_CACHE_ACTION = 'purge_faculty_cache';
const LOCAL_UCKK_CLI_PURGE_FACULTY_CACHE_CAPABILITY = 'local/uckk:purgefacultycache';

const LOCAL_UCKK_CLI_PURGE_FACULTY_CACHE_MANIFEST = 'content/faculties/faculty_manifest.json';

const LOCAL_UCKK_CLI_PURGE_FACULTY_CACHE_STATUS_COMPLETED = 'completed';
const LOCAL_UCKK_CLI_PURGE_FACULTY_CACHE_STATUS_WARNING = 'warning';
const LOCAL_UCKK_CLI_PURGE_FACULTY_CACHE_STATUS_FAILED = 'failed';

const LOCAL_UCKK_CLI_PURGE_FACULTY_CACHE_SEVERITY_INFO = 'info';
const LOCAL_UCKK_CLI_PURGE_FACULTY_CACHE_SEVERITY_SUCCESS = 'success';
const LOCAL_UCKK_CLI_PURGE_FACULTY_CACHE_SEVERITY_WARNING = 'warning';
const LOCAL_UCKK_CLI_PURGE_FACULTY_CACHE_SEVERITY_ERROR = 'error';

const LOCAL_UCKK_CLI_PURGE_FACULTY_CACHE_EXIT_OK = 0;
const LOCAL_UCKK_CLI_PURGE_FACULTY_CACHE_EXIT_ERROR = 1;

/**
 * Canonical MUC cache areas for Faculty pages.
 */
const LOCAL_UCKK_CLI_PURGE_FACULTY_CACHE_AREAS = [
    'faculty_profile',
    'atlas_voie',
    'faculty_page',
    'faculty_dynamic_block',
];

/**
 * Render CLI help.
 *
 * @return string
 */
function local_uckk_cli_purge_faculty_cache_help(): string {
    return <<<HELP
Purge UCKK Faculty page caches.

This command purges Moodle MUC cache definitions owned by local_uckk:
  - local_uckk/faculty_profile
  - local_uckk/atlas_voie
  - local_uckk/faculty_page
  - local_uckk/faculty_dynamic_block

The documented cache keys include content hashes, so this command purges whole
cache definitions. When --slug is provided, the slug is validated against
faculty_manifest.json and included in the report/event metadata, but MUC purge
still clears the selected cache area definitions.

Usage:
  php local/uckk/cli/purge_faculty_cache.php [options]

Options:
  --slug=<slug>   Validate a faculty slug and record it as purge scope.
  --area=<area>   Purge one cache area only.
                  Allowed: faculty_profile, atlas_voie, faculty_page,
                  faculty_dynamic_block, all.
                  Default: all.
  --json          Print machine-readable JSON.
  --strict        Return exit code 1 when warnings are present.
  --quiet         Print only final status unless --json is used.
  -h, --help      Print this help.

Examples:
  php local/uckk/cli/purge_faculty_cache.php
  php local/uckk/cli/purge_faculty_cache.php --slug=grand-jeu-social
  php local/uckk/cli/purge_faculty_cache.php --area=faculty_page
  php local/uckk/cli/purge_faculty_cache.php --json

HELP;
}

/**
 * Create base result payload.
 *
 * @param string $scope Scope label.
 * @param string $area Area option.
 * @return array<string, mixed>
 */
function local_uckk_cli_purge_faculty_cache_new_result(string $scope, string $area): array {
    return [
        'ok' => false,
        'component' => LOCAL_UCKK_CLI_PURGE_FACULTY_CACHE_COMPONENT,
        'action' => LOCAL_UCKK_CLI_PURGE_FACULTY_CACHE_ACTION,
        'status' => LOCAL_UCKK_CLI_PURGE_FACULTY_CACHE_STATUS_FAILED,
        'summary' => '',
        'scope' => $scope,
        'area' => $area,
        'hash' => '',
        'counts' => [
            'areas_requested' => 0,
            'areas_purged' => 0,
            'slugs_known' => 0,
            'errors' => 0,
            'warnings' => 0,
        ],
        'areas' => [],
        'messages' => [],
    ];
}

/**
 * Add a message to the report.
 *
 * @param array<string, mixed> $result Result payload.
 * @param string $severity Severity.
 * @param string $message Message.
 * @param string $target Target.
 * @param array<string, mixed> $metadata Safe metadata.
 */
function local_uckk_cli_purge_faculty_cache_add_message(
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

    if ($severity === LOCAL_UCKK_CLI_PURGE_FACULTY_CACHE_SEVERITY_ERROR) {
        $result['counts']['errors']++;
    } else if ($severity === LOCAL_UCKK_CLI_PURGE_FACULTY_CACHE_SEVERITY_WARNING) {
        $result['counts']['warnings']++;
    }
}

/**
 * Finalise result status and hash.
 *
 * @param array<string, mixed> $result Result payload.
 * @return array<string, mixed>
 */
function local_uckk_cli_purge_faculty_cache_finalise(array $result): array {
    $result['hash'] = hash('sha256', json_encode([
        'component' => $result['component'],
        'action' => $result['action'],
        'scope' => $result['scope'],
        'area' => $result['area'],
        'counts' => $result['counts'],
        'areas' => $result['areas'],
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

    if ($result['counts']['errors'] > 0) {
        $result['ok'] = false;
        $result['status'] = LOCAL_UCKK_CLI_PURGE_FACULTY_CACHE_STATUS_FAILED;
        $result['summary'] = 'UCKK faculty cache purge failed.';
    } else if ($result['counts']['warnings'] > 0) {
        $result['ok'] = true;
        $result['status'] = LOCAL_UCKK_CLI_PURGE_FACULTY_CACHE_STATUS_WARNING;
        $result['summary'] = 'UCKK faculty cache purge completed with warnings.';
    } else {
        $result['ok'] = true;
        $result['status'] = LOCAL_UCKK_CLI_PURGE_FACULTY_CACHE_STATUS_COMPLETED;
        $result['summary'] = 'UCKK faculty cache purge completed successfully.';
    }

    return $result;
}

/**
 * Join base path and relative path.
 *
 * @param string $base Base path.
 * @param string $relative Slash-separated relative path.
 * @return string
 */
function local_uckk_cli_purge_faculty_cache_path(string $base, string $relative): string {
    return rtrim($base, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);
}

/**
 * Validate canonical faculty slug syntax.
 *
 * @param string $slug Slug.
 * @return bool
 */
function local_uckk_cli_purge_faculty_cache_valid_slug(string $slug): bool {
    return preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug) === 1;
}

/**
 * Decode JSON file.
 *
 * @param string $path Absolute path.
 * @param string $target Report target.
 * @param array<string, mixed> $result Result payload.
 * @return array<string, mixed>|null
 */
function local_uckk_cli_purge_faculty_cache_decode_json_file(
    string $path,
    string $target,
    array &$result
): ?array {
    if (!is_file($path)) {
        local_uckk_cli_purge_faculty_cache_add_message(
            $result,
            LOCAL_UCKK_CLI_PURGE_FACULTY_CACHE_SEVERITY_ERROR,
            'Required JSON file does not exist.',
            $target
        );
        return null;
    }

    if (!is_readable($path)) {
        local_uckk_cli_purge_faculty_cache_add_message(
            $result,
            LOCAL_UCKK_CLI_PURGE_FACULTY_CACHE_SEVERITY_ERROR,
            'Required JSON file is not readable.',
            $target
        );
        return null;
    }

    $raw = file_get_contents($path);
    if ($raw === false) {
        local_uckk_cli_purge_faculty_cache_add_message(
            $result,
            LOCAL_UCKK_CLI_PURGE_FACULTY_CACHE_SEVERITY_ERROR,
            'Unable to read JSON file.',
            $target
        );
        return null;
    }

    try {
        $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
    } catch (JsonException $exception) {
        local_uckk_cli_purge_faculty_cache_add_message(
            $result,
            LOCAL_UCKK_CLI_PURGE_FACULTY_CACHE_SEVERITY_ERROR,
            'Invalid JSON syntax: ' . $exception->getMessage(),
            $target
        );
        return null;
    }

    if (!is_array($decoded)) {
        local_uckk_cli_purge_faculty_cache_add_message(
            $result,
            LOCAL_UCKK_CLI_PURGE_FACULTY_CACHE_SEVERITY_ERROR,
            'JSON root must be an object.',
            $target
        );
        return null;
    }

    return $decoded;
}

/**
 * Load valid slugs from faculty_manifest.json.
 *
 * @param string $basepath local/uckk absolute path.
 * @param array<string, mixed> $result Result payload.
 * @return array<string, array<string, mixed>>
 */
function local_uckk_cli_purge_faculty_cache_load_manifest_slugs(string $basepath, array &$result): array {
    $path = local_uckk_cli_purge_faculty_cache_path($basepath, LOCAL_UCKK_CLI_PURGE_FACULTY_CACHE_MANIFEST);
    $manifest = local_uckk_cli_purge_faculty_cache_decode_json_file(
        $path,
        LOCAL_UCKK_CLI_PURGE_FACULTY_CACHE_MANIFEST,
        $result
    );

    if ($manifest === null) {
        return [];
    }

    if (empty($manifest['items']) || !is_array($manifest['items'])) {
        local_uckk_cli_purge_faculty_cache_add_message(
            $result,
            LOCAL_UCKK_CLI_PURGE_FACULTY_CACHE_SEVERITY_ERROR,
            'faculty_manifest.json must contain a non-empty items array.',
            LOCAL_UCKK_CLI_PURGE_FACULTY_CACHE_MANIFEST
        );
        return [];
    }

    $slugs = [];

    foreach ($manifest['items'] as $index => $item) {
        if (!is_array($item)) {
            local_uckk_cli_purge_faculty_cache_add_message(
                $result,
                LOCAL_UCKK_CLI_PURGE_FACULTY_CACHE_SEVERITY_WARNING,
                'Manifest item is not an object.',
                LOCAL_UCKK_CLI_PURGE_FACULTY_CACHE_MANIFEST . ':items[' . $index . ']'
            );
            continue;
        }

        if (empty($item['slug']) || !is_string($item['slug'])) {
            local_uckk_cli_purge_faculty_cache_add_message(
                $result,
                LOCAL_UCKK_CLI_PURGE_FACULTY_CACHE_SEVERITY_WARNING,
                'Manifest item has no slug.',
                LOCAL_UCKK_CLI_PURGE_FACULTY_CACHE_MANIFEST . ':items[' . $index . ']'
            );
            continue;
        }

        if (!local_uckk_cli_purge_faculty_cache_valid_slug($item['slug'])) {
            local_uckk_cli_purge_faculty_cache_add_message(
                $result,
                LOCAL_UCKK_CLI_PURGE_FACULTY_CACHE_SEVERITY_WARNING,
                'Manifest item slug has invalid syntax.',
                LOCAL_UCKK_CLI_PURGE_FACULTY_CACHE_MANIFEST . ':items[' . $index . ']',
                ['slug' => $item['slug']]
            );
            continue;
        }

        $slugs[$item['slug']] = $item;
    }

    $result['counts']['slugs_known'] = count($slugs);

    return $slugs;
}

/**
 * Resolve requested areas.
 *
 * @param string $area Area option.
 * @param array<string, mixed> $result Result payload.
 * @return array<int, string>
 */
function local_uckk_cli_purge_faculty_cache_resolve_areas(string $area, array &$result): array {
    if ($area === '' || $area === 'all') {
        return LOCAL_UCKK_CLI_PURGE_FACULTY_CACHE_AREAS;
    }

    if (!in_array($area, LOCAL_UCKK_CLI_PURGE_FACULTY_CACHE_AREAS, true)) {
        local_uckk_cli_purge_faculty_cache_add_message(
            $result,
            LOCAL_UCKK_CLI_PURGE_FACULTY_CACHE_SEVERITY_ERROR,
            'Unknown cache area.',
            'area',
            ['area' => $area]
        );
        return [];
    }

    return [$area];
}

/**
 * Purge one MUC cache definition.
 *
 * @param string $area Cache area.
 * @param array<string, mixed> $result Result payload.
 */
function local_uckk_cli_purge_faculty_cache_purge_area(string $area, array &$result): void {
    $target = LOCAL_UCKK_CLI_PURGE_FACULTY_CACHE_COMPONENT . '/' . $area;

    try {
        if (class_exists('cache_helper') && method_exists('cache_helper', 'purge_by_definition')) {
            cache_helper::purge_by_definition(LOCAL_UCKK_CLI_PURGE_FACULTY_CACHE_COMPONENT, $area);
        } else {
            $cache = cache::make(LOCAL_UCKK_CLI_PURGE_FACULTY_CACHE_COMPONENT, $area);

            if (!method_exists($cache, 'purge')) {
                local_uckk_cli_purge_faculty_cache_add_message(
                    $result,
                    LOCAL_UCKK_CLI_PURGE_FACULTY_CACHE_SEVERITY_ERROR,
                    'Cache loader does not expose purge().',
                    $target
                );
                return;
            }

            $cache->purge();
        }

        $result['counts']['areas_purged']++;
        $result['areas'][] = [
            'component' => LOCAL_UCKK_CLI_PURGE_FACULTY_CACHE_COMPONENT,
            'area' => $area,
            'status' => 'purged',
        ];

        local_uckk_cli_purge_faculty_cache_add_message(
            $result,
            LOCAL_UCKK_CLI_PURGE_FACULTY_CACHE_SEVERITY_SUCCESS,
            'Cache area purged.',
            $target
        );
    } catch (Throwable $exception) {
        $result['areas'][] = [
            'component' => LOCAL_UCKK_CLI_PURGE_FACULTY_CACHE_COMPONENT,
            'area' => $area,
            'status' => 'error',
        ];

        local_uckk_cli_purge_faculty_cache_add_message(
            $result,
            LOCAL_UCKK_CLI_PURGE_FACULTY_CACHE_SEVERITY_ERROR,
            'Unable to purge cache area: ' . $exception->getMessage(),
            $target,
            ['exception' => get_class($exception)]
        );
    }
}

/**
 * Trigger cache purged event if the event class exists.
 *
 * @param array<string, mixed> $result Result payload.
 * @param string $slug Slug scope.
 */
function local_uckk_cli_purge_faculty_cache_trigger_event(array $result, string $slug): void {
    $eventclass = '\\local_uckk\\event\\faculty_cache_purged';

    if (!class_exists($eventclass)) {
        return;
    }

    $event = $eventclass::create([
        'context' => context_system::instance(),
        'other' => [
            'scope' => $result['scope'],
            'slug' => $slug,
            'area' => $result['area'],
            'status' => $result['status'],
            'counts' => $result['counts'],
            'hash' => $result['hash'],
        ],
    ]);

    $event->trigger();
}

/**
 * Print JSON output.
 *
 * @param array<string, mixed> $result Result payload.
 */
function local_uckk_cli_purge_faculty_cache_print_json(array $result): void {
    cli_writeln(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
}

/**
 * Print text output.
 *
 * @param array<string, mixed> $result Result payload.
 * @param bool $quiet Quiet output.
 */
function local_uckk_cli_purge_faculty_cache_print_text(array $result, bool $quiet): void {
    cli_writeln('UCKK faculty cache purge');
    cli_writeln('Status: ' . $result['status']);
    cli_writeln('Summary: ' . $result['summary']);
    cli_writeln('Scope: ' . $result['scope']);
    cli_writeln('Area: ' . $result['area']);
    cli_writeln('Hash: ' . $result['hash']);
    cli_writeln('');

    cli_writeln('Counts:');
    foreach ($result['counts'] as $key => $value) {
        cli_writeln('  ' . $key . ': ' . $value);
    }

    if ($quiet) {
        return;
    }

    if (!empty($result['areas'])) {
        cli_writeln('');
        cli_writeln('Areas:');
        foreach ($result['areas'] as $area) {
            cli_writeln(
                '  ' . $area['component'] . '/' . $area['area'] .
                ' status=' . $area['status']
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
 * Resolve exit code.
 *
 * @param array<string, mixed> $result Result payload.
 * @param bool $strict Strict mode.
 * @return int
 */
function local_uckk_cli_purge_faculty_cache_exit_code(array $result, bool $strict): int {
    if ($result['counts']['errors'] > 0) {
        return LOCAL_UCKK_CLI_PURGE_FACULTY_CACHE_EXIT_ERROR;
    }

    if ($strict && $result['counts']['warnings'] > 0) {
        return LOCAL_UCKK_CLI_PURGE_FACULTY_CACHE_EXIT_ERROR;
    }

    return LOCAL_UCKK_CLI_PURGE_FACULTY_CACHE_EXIT_OK;
}

[$options, $unrecognised] = cli_get_params(
    [
        'help' => false,
        'slug' => '',
        'area' => 'all',
        'json' => false,
        'strict' => false,
        'quiet' => false,
    ],
    [
        'h' => 'help',
    ]
);

if (!empty($unrecognised)) {
    cli_error('Unknown option(s): ' . implode(', ', $unrecognised), LOCAL_UCKK_CLI_PURGE_FACULTY_CACHE_EXIT_ERROR);
}

if (!empty($options['help'])) {
    cli_writeln(local_uckk_cli_purge_faculty_cache_help());
    exit(LOCAL_UCKK_CLI_PURGE_FACULTY_CACHE_EXIT_OK);
}

$slug = trim((string)$options['slug']);
$area = trim((string)$options['area']);
$scope = $slug === '' ? 'all' : 'slug:' . $slug;

$result = local_uckk_cli_purge_faculty_cache_new_result($scope, $area);

try {
    $admin = get_admin();
    if (!$admin || empty($admin->id)) {
        throw new moodle_exception('Unable to load Moodle admin user.');
    }

    cron_setup_user($admin);

    $context = context_system::instance();

    if (function_exists('get_capability_info') && get_capability_info(LOCAL_UCKK_CLI_PURGE_FACULTY_CACHE_CAPABILITY)) {
        require_capability(LOCAL_UCKK_CLI_PURGE_FACULTY_CACHE_CAPABILITY, $context);
    } else if (!is_siteadmin()) {
        local_uckk_cli_purge_faculty_cache_add_message(
            $result,
            LOCAL_UCKK_CLI_PURGE_FACULTY_CACHE_SEVERITY_ERROR,
            'Required cache purge capability is not installed and current user is not site admin.',
            LOCAL_UCKK_CLI_PURGE_FACULTY_CACHE_CAPABILITY
        );
    } else {
        local_uckk_cli_purge_faculty_cache_add_message(
            $result,
            LOCAL_UCKK_CLI_PURGE_FACULTY_CACHE_SEVERITY_WARNING,
            'Required cache purge capability is not installed yet; site admin fallback used.',
            LOCAL_UCKK_CLI_PURGE_FACULTY_CACHE_CAPABILITY
        );
    }

    $basepath = local_uckk_cli_purge_faculty_cache_path($CFG->dirroot, 'local/uckk');

    if ($slug !== '') {
        if (!local_uckk_cli_purge_faculty_cache_valid_slug($slug)) {
            local_uckk_cli_purge_faculty_cache_add_message(
                $result,
                LOCAL_UCKK_CLI_PURGE_FACULTY_CACHE_SEVERITY_ERROR,
                'Invalid slug syntax.',
                'slug',
                ['slug' => $slug]
            );
        } else {
            $slugs = local_uckk_cli_purge_faculty_cache_load_manifest_slugs($basepath, $result);

            if (!empty($slugs) && empty($slugs[$slug])) {
                local_uckk_cli_purge_faculty_cache_add_message(
                    $result,
                    LOCAL_UCKK_CLI_PURGE_FACULTY_CACHE_SEVERITY_ERROR,
                    'Requested slug is not listed in faculty_manifest.json.',
                    LOCAL_UCKK_CLI_PURGE_FACULTY_CACHE_MANIFEST,
                    ['slug' => $slug]
                );
            }

            local_uckk_cli_purge_faculty_cache_add_message(
                $result,
                LOCAL_UCKK_CLI_PURGE_FACULTY_CACHE_SEVERITY_INFO,
                'Slug scope validated. Cache definitions will still be purged globally because documented MUC keys are hash-based.',
                'slug',
                ['slug' => $slug]
            );
        }
    } else {
        local_uckk_cli_purge_faculty_cache_load_manifest_slugs($basepath, $result);
    }

    $areas = local_uckk_cli_purge_faculty_cache_resolve_areas($area, $result);
    $result['counts']['areas_requested'] = count($areas);

    if ($result['counts']['errors'] === 0) {
        foreach ($areas as $cachearea) {
            local_uckk_cli_purge_faculty_cache_purge_area($cachearea, $result);
        }
    }

    $result = local_uckk_cli_purge_faculty_cache_finalise($result);

    if ($result['counts']['errors'] === 0) {
        local_uckk_cli_purge_faculty_cache_trigger_event($result, $slug);
    }

    if (!empty($options['json'])) {
        local_uckk_cli_purge_faculty_cache_print_json($result);
    } else {
        local_uckk_cli_purge_faculty_cache_print_text($result, !empty($options['quiet']));
    }

    exit(local_uckk_cli_purge_faculty_cache_exit_code($result, !empty($options['strict'])));
} catch (Throwable $exception) {
    $result['ok'] = false;
    $result['status'] = LOCAL_UCKK_CLI_PURGE_FACULTY_CACHE_STATUS_FAILED;
    $result['summary'] = 'UCKK faculty cache purge crashed.';

    local_uckk_cli_purge_faculty_cache_add_message(
        $result,
        LOCAL_UCKK_CLI_PURGE_FACULTY_CACHE_SEVERITY_ERROR,
        $exception->getMessage(),
        LOCAL_UCKK_CLI_PURGE_FACULTY_CACHE_ACTION,
        ['exception' => get_class($exception)]
    );

    $result = local_uckk_cli_purge_faculty_cache_finalise($result);

    if (!empty($options['json'])) {
        local_uckk_cli_purge_faculty_cache_print_json($result);
    } else {
        local_uckk_cli_purge_faculty_cache_print_text($result, !empty($options['quiet']));

        if (debugging('', DEBUG_DEVELOPER)) {
            cli_writeln('');
            cli_writeln($exception->getTraceAsString());
        }
    }

    exit(LOCAL_UCKK_CLI_PURGE_FACULTY_CACHE_EXIT_ERROR);
}