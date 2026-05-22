<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

/**
 * CLI seed runner for the UCKK distribution.
 *
 * @package    tool_uckkseed
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../../config.php');
require_once($CFG->libdir . '/clilib.php');

use tool_uckkseed\local\seeder;
use tool_uckkseed\local\validation_result;

/**
 * CLI action name.
 */
const TOOL_UCKKSEED_ACTION_SEED = 'seed';

/**
 * CLI modes.
 */
const TOOL_UCKKSEED_MODE_APPLY = 'apply';
const TOOL_UCKKSEED_MODE_DRY_RUN = 'dry_run';
const TOOL_UCKKSEED_MODE_REPORT = 'report';
const TOOL_UCKKSEED_MODE_ROLLBACK_PLAN = 'rollback_plan';

/**
 * Exit codes.
 */
const TOOL_UCKKSEED_EXIT_SUCCESS = 0;
const TOOL_UCKKSEED_EXIT_WARNING = 1;
const TOOL_UCKKSEED_EXIT_ERROR = 2;

/**
 * Academic registry JSON directory.
 */
const TOOL_UCKKSEED_DEFAULT_PRESETPATH = 'academic_registry_json';

/**
 * Legacy plugin-local preset directory.
 */
const TOOL_UCKKSEED_LEGACY_PRESETPATH = 'admin/tool/uckkseed/presets';

/**
 * Supported preset ids.
 */
const TOOL_UCKKSEED_PRESETS = [
    'categories',
    'programs',
    'pathways',
    'courses',
    'cohorts',
    'roles',
    'capabilities',
    'competencies',
    'badges',
    'reports',
    'course_templates',
    'challenge_templates',
    'assembly_templates',
    'archive_templates',
];

/**
 * Supported target components for selective seeding.
 */
const TOOL_UCKKSEED_COMPONENTS = [
    'local_uckk',
    'theme_uckk',
    'format_uckk',
    'block_uckk_dashboard',
    'mod_uckkchallenge',
    'mod_uckkassembly',
    'mod_uckkarchive',
    'tool_uckkintegrity',
    'report_uckk',
];

/**
 * Print help and exit.
 *
 * @return never
 */
function tool_uckkseed_cli_seed_help(): never {
    global $argv;

    $script = basename($argv[0]);

    cli_writeln("Seed the UCKK-Moodle distribution.");
    cli_writeln('');
    cli_writeln("Usage:");
    cli_writeln("  php {$script} [--dry-run|--report|--rollback-plan] [--preset=<ids>] [--component=<components>] [--target=<key>] [--presetpath=<path>] [--force] [--json]");
    cli_writeln('');
    cli_writeln("Modes:");
    cli_writeln("  --dry-run          Validate and simulate seed actions without writing records.");
    cli_writeln("  --report           Produce a seed readiness report without writing records.");
    cli_writeln("  --rollback-plan    Produce a rollback plan without deleting or changing records.");
    cli_writeln("  --force            Required when the resolved mode is apply.");
    cli_writeln('');
    cli_writeln("Selection:");
    cli_writeln("  --preset           Comma-separated preset ids.");
    cli_writeln("                     Allowed: " . implode(', ', TOOL_UCKKSEED_PRESETS));
    cli_writeln("  --component        Comma-separated component names.");
    cli_writeln("                     Allowed: " . implode(', ', TOOL_UCKKSEED_COMPONENTS));
    cli_writeln("  --target           Optional target key, such as a course shortname or preset item key.");
    cli_writeln('');
    cli_writeln("Academic registry JSON:");
    cli_writeln("  --presetpath       Directory containing the academic registry JSON files.");
    cli_writeln("                     Relative paths are resolved from Moodle root.");
    cli_writeln("                     Default: " . TOOL_UCKKSEED_DEFAULT_PRESETPATH);
    cli_writeln('');
    cli_writeln("Output:");
    cli_writeln("  --json             Print machine-readable JSON.");
    cli_writeln("  --no-progress      Suppress progress lines in text mode.");
    cli_writeln('');
    cli_writeln("Examples:");
    cli_writeln("  php {$script} --dry-run");
    cli_writeln("  php {$script} --presetpath=academic_registry_json --dry-run");
    cli_writeln("  php {$script} --preset=categories,programs,pathways,courses,competencies --dry-run");
    cli_writeln("  php {$script} --preset=programs,pathways --report");
    cli_writeln("  php {$script} --component=mod_uckkarchive --report");
    cli_writeln("  php {$script} --force");
    cli_writeln('');
    cli_writeln("Safety:");
    cli_writeln("  Apply mode writes Moodle records and requires --force.");
    cli_writeln("  Dry-run, report, and rollback-plan modes never write distribution records.");
    cli_writeln('');

    exit(TOOL_UCKKSEED_EXIT_SUCCESS);
}

/**
 * Parse comma-separated CLI option.
 *
 * @param string|null $value Raw option value.
 * @return string[]
 */
function tool_uckkseed_cli_parse_list(?string $value): array {
    if ($value === null || trim($value) === '') {
        return [];
    }

    $items = preg_split('/\s*,\s*/', trim($value), -1, PREG_SPLIT_NO_EMPTY);

    if ($items === false) {
        return [];
    }

    return array_values(array_unique(array_map(static function (string $item): string {
        return trim($item);
    }, $items)));
}

/**
 * Return whether a path is absolute on Unix, Windows drive, or Windows UNC.
 *
 * @param string $path Path.
 * @return bool
 */
function tool_uckkseed_cli_is_absolute_path(string $path): bool {
    return preg_match('~^(?:[A-Za-z]:[\\\\/]|[\\\\/]{2}|[\\\\/])~', $path) === 1;
}

/**
 * Resolve academic registry JSON path.
 *
 * @param mixed $clioption CLI --presetpath value.
 * @return string Absolute directory path.
 */
function tool_uckkseed_cli_resolve_presetpath(mixed $clioption): string {
    global $CFG;

    $path = is_string($clioption) ? trim($clioption) : '';

    if ($path === '') {
        $configured = get_config('tool_uckkseed', 'presetpath');
        $path = is_string($configured) ? trim($configured) : '';
    }

    if ($path === '' || $path === TOOL_UCKKSEED_LEGACY_PRESETPATH) {
        $path = TOOL_UCKKSEED_DEFAULT_PRESETPATH;
    }

    $path = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);

    if (tool_uckkseed_cli_is_absolute_path($path)) {
        return rtrim($path, DIRECTORY_SEPARATOR);
    }

    return rtrim($CFG->dirroot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . trim($path, DIRECTORY_SEPARATOR);
}

/**
 * Validate the academic registry JSON directory.
 *
 * @param string $presetpath Absolute preset path.
 */
function tool_uckkseed_cli_validate_presetpath(string $presetpath): void {
    if (!is_dir($presetpath)) {
        cli_error(
            'Academic registry JSON directory not found: ' . $presetpath,
            TOOL_UCKKSEED_EXIT_ERROR
        );
    }

    if (!is_readable($presetpath)) {
        cli_error(
            'Academic registry JSON directory is not readable: ' . $presetpath,
            TOOL_UCKKSEED_EXIT_ERROR
        );
    }
}

/**
 * Resolve seed mode from options and plugin config.
 *
 * @param array<string, mixed> $options CLI options.
 * @return string
 */
function tool_uckkseed_cli_resolve_mode(array $options): string {
    $selected = [];

    if (!empty($options['dry-run'])) {
        $selected[] = TOOL_UCKKSEED_MODE_DRY_RUN;
    }

    if (!empty($options['report'])) {
        $selected[] = TOOL_UCKKSEED_MODE_REPORT;
    }

    if (!empty($options['rollback-plan'])) {
        $selected[] = TOOL_UCKKSEED_MODE_ROLLBACK_PLAN;
    }

    if (count($selected) > 1) {
        cli_error('Choose only one mode: --dry-run, --report, or --rollback-plan.', TOOL_UCKKSEED_EXIT_ERROR);
    }

    if (!empty($selected)) {
        return $selected[0];
    }

    $default = (string)get_config('tool_uckkseed', 'defaultmode');

    return match ($default) {
        TOOL_UCKKSEED_MODE_APPLY,
        TOOL_UCKKSEED_MODE_DRY_RUN,
        TOOL_UCKKSEED_MODE_REPORT,
        TOOL_UCKKSEED_MODE_ROLLBACK_PLAN => $default,
        default => TOOL_UCKKSEED_MODE_DRY_RUN,
    };
}

/**
 * Validate preset ids.
 *
 * @param string[] $presets Preset ids.
 */
function tool_uckkseed_cli_validate_presets(array $presets): void {
    $invalid = array_values(array_diff($presets, TOOL_UCKKSEED_PRESETS));

    if (!empty($invalid)) {
        cli_error(
            'Invalid preset id(s): ' . implode(', ', $invalid) . PHP_EOL .
            'Allowed preset id(s): ' . implode(', ', TOOL_UCKKSEED_PRESETS),
            TOOL_UCKKSEED_EXIT_ERROR
        );
    }
}

/**
 * Validate component names.
 *
 * @param string[] $components Component names.
 */
function tool_uckkseed_cli_validate_components(array $components): void {
    $invalid = array_values(array_diff($components, TOOL_UCKKSEED_COMPONENTS));

    if (!empty($invalid)) {
        cli_error(
            'Invalid component(s): ' . implode(', ', $invalid) . PHP_EOL .
            'Allowed component(s): ' . implode(', ', TOOL_UCKKSEED_COMPONENTS),
            TOOL_UCKKSEED_EXIT_ERROR
        );
    }
}

/**
 * Return whether the tool is enabled.
 *
 * @return bool
 */
function tool_uckkseed_cli_tool_enabled(): bool {
    $enabled = get_config('tool_uckkseed', 'enabletool');

    return $enabled === false || (bool)$enabled;
}

/**
 * Return whether CLI use is enabled.
 *
 * @return bool
 */
function tool_uckkseed_cli_allowed(): bool {
    $allowcli = get_config('tool_uckkseed', 'allowcli');

    return $allowcli === false || (bool)$allowcli;
}

/**
 * Prepare a system/admin user for audited CLI operations.
 */
function tool_uckkseed_cli_set_admin_user(): void {
    $admin = get_admin();

    if (!$admin) {
        cli_error('No Moodle admin user is available for CLI seed auditing.', TOOL_UCKKSEED_EXIT_ERROR);
    }

    \core\session\manager::set_user($admin);
}

/**
 * Convert a validation result to a plain export array.
 *
 * @param mixed $result Result object/array.
 * @return array<string, mixed>
 */
function tool_uckkseed_cli_result_to_array(mixed $result): array {
    if ($result instanceof validation_result && method_exists($result, 'to_array')) {
        return $result->to_array();
    }

    if (is_object($result) && method_exists($result, 'to_export')) {
        return (array)$result->to_export();
    }

    if (is_object($result)) {
        return get_object_vars($result);
    }

    if (is_array($result)) {
        return $result;
    }

    return [
        'ok' => false,
        'status' => 'failed',
        'summary' => 'Seeder returned an unsupported result.',
        'messages' => [],
        'counts' => [],
    ];
}

/**
 * Print result as JSON.
 *
 * @param mixed $result Result.
 */
function tool_uckkseed_cli_print_json(mixed $result): void {
    $data = tool_uckkseed_cli_result_to_array($result);

    cli_writeln(json_encode(
        $data,
        JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    ));
}

/**
 * Print result as human-readable text.
 *
 * @param mixed $result Result.
 * @param bool $showprogress Whether progress output is enabled.
 */
function tool_uckkseed_cli_print_text(mixed $result, bool $showprogress = true): void {
    if (!$showprogress) {
        return;
    }

    $data = tool_uckkseed_cli_result_to_array($result);

    $status = (string)($data['status'] ?? 'unknown');
    $summary = (string)($data['summary'] ?? '');

    cli_writeln('');
    cli_writeln('UCKK seed result');
    cli_writeln('----------------');
    cli_writeln('Status: ' . $status);

    if ($summary !== '') {
        cli_writeln('Summary: ' . $summary);
    }

    $counts = $data['counts'] ?? [];

    if (is_array($counts) && !empty($counts)) {
        cli_writeln('');
        cli_writeln('Counts:');

        foreach ($counts as $key => $value) {
            if (is_scalar($value)) {
                cli_writeln('  ' . $key . ': ' . $value);
            }
        }
    }

    $messages = $data['messages'] ?? [];

    if (is_array($messages) && !empty($messages)) {
        cli_writeln('');
        cli_writeln('Messages:');

        foreach ($messages as $message) {
            $row = is_object($message) ? get_object_vars($message) : (array)$message;
            $severity = (string)($row['severity'] ?? 'info');
            $target = (string)($row['targetkey'] ?? $row['preset'] ?? '');
            $text = (string)($row['message'] ?? '');

            $prefix = strtoupper($severity);

            if ($target !== '') {
                cli_writeln("  [{$prefix}] {$target}: {$text}");
            } else {
                cli_writeln("  [{$prefix}] {$text}");
            }
        }
    }

    cli_writeln('');
}

/**
 * Return process exit code from result.
 *
 * @param mixed $result Result.
 * @return int
 */
function tool_uckkseed_cli_exit_code(mixed $result): int {
    $data = tool_uckkseed_cli_result_to_array($result);

    $ok = (bool)($data['ok'] ?? false);
    $haserrors = (bool)($data['haserrors'] ?? false);
    $haswarnings = (bool)($data['haswarnings'] ?? false);
    $status = (string)($data['status'] ?? '');

    if (!$ok || $haserrors || $status === 'failed') {
        return TOOL_UCKKSEED_EXIT_ERROR;
    }

    if ($haswarnings || $status === 'warning') {
        return TOOL_UCKKSEED_EXIT_WARNING;
    }

    return TOOL_UCKKSEED_EXIT_SUCCESS;
}

[$options, $unrecognised] = cli_get_params(
    [
        'help' => false,
        'dry-run' => false,
        'report' => false,
        'rollback-plan' => false,
        'preset' => null,
        'component' => null,
        'target' => null,
        'presetpath' => null,
        'force' => false,
        'json' => false,
        'no-progress' => false,
    ],
    [
        'h' => 'help',
    ]
);

if (!empty($unrecognised)) {
    cli_error('Unknown option(s): ' . implode(', ', $unrecognised), TOOL_UCKKSEED_EXIT_ERROR);
}

if (!empty($options['help'])) {
    tool_uckkseed_cli_seed_help();
}

if (!tool_uckkseed_cli_tool_enabled()) {
    cli_error('tool_uckkseed is disabled in plugin settings.', TOOL_UCKKSEED_EXIT_ERROR);
}

if (!tool_uckkseed_cli_allowed()) {
    cli_error('CLI execution is disabled for tool_uckkseed.', TOOL_UCKKSEED_EXIT_ERROR);
}

$mode = tool_uckkseed_cli_resolve_mode($options);
$presetpath = tool_uckkseed_cli_resolve_presetpath($options['presetpath']);
$presets = tool_uckkseed_cli_parse_list($options['preset']);
$components = tool_uckkseed_cli_parse_list($options['component']);
$target = is_string($options['target']) ? trim($options['target']) : '';

tool_uckkseed_cli_validate_presetpath($presetpath);
tool_uckkseed_cli_validate_presets($presets);
tool_uckkseed_cli_validate_components($components);

if ($mode === TOOL_UCKKSEED_MODE_APPLY && empty($options['force'])) {
    cli_error(
        'Apply mode writes records and requires --force. Use --dry-run first to preview changes.',
        TOOL_UCKKSEED_EXIT_ERROR
    );
}

tool_uckkseed_cli_set_admin_user();

$showprogress = empty($options['json']) && empty($options['no-progress']);

if ($showprogress) {
    cli_writeln('Starting UCKK distribution seed.');
    cli_writeln('Mode: ' . $mode);
    cli_writeln('Academic JSON path: ' . $presetpath);

    if (!empty($presets)) {
        cli_writeln('Preset(s): ' . implode(', ', $presets));
    }

    if (!empty($components)) {
        cli_writeln('Component(s): ' . implode(', ', $components));
    }

    if ($target !== '') {
        cli_writeln('Target: ' . $target);
    }

    if ($mode !== TOOL_UCKKSEED_MODE_APPLY) {
        cli_writeln('No distribution records will be written in this mode.');
    }
}

$seedoptions = [
    'action' => TOOL_UCKKSEED_ACTION_SEED,
    'mode' => $mode,
    'presetpath' => $presetpath,
    'presets' => $presets,
    'components' => $components,
    'target' => $target,
    'force' => !empty($options['force']),
    'source' => 'cli',
    'json' => !empty($options['json']),
    'progress' => $showprogress,
];

try {
    $seeder = new seeder($presetpath);
    $result = $seeder->seed($seedoptions);

    if (!empty($options['json'])) {
        tool_uckkseed_cli_print_json($result);
    } else {
        tool_uckkseed_cli_print_text($result, $showprogress);
    }

    exit(tool_uckkseed_cli_exit_code($result));
} catch (Throwable $exception) {
    if (!empty($options['json'])) {
        cli_writeln(json_encode([
            'ok' => false,
            'status' => 'failed',
            'summary' => $exception->getMessage(),
            'exception' => get_class($exception),
            'presetpath' => $presetpath,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    } else {
        cli_writeln('');
        cli_writeln('UCKK seed failed.');
        cli_writeln($exception->getMessage());
        cli_writeln('Academic JSON path: ' . $presetpath);

        if (debugging('', DEBUG_DEVELOPER)) {
            cli_writeln($exception->getTraceAsString());
        }
    }

    exit(TOOL_UCKKSEED_EXIT_ERROR);
}