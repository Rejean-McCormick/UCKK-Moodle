<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

/**
 * CLI reset entry point for the UCKK seed tool.
 *
 * @package    tool_uckkseed
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../../config.php');
require_once($CFG->libdir . '/clilib.php');

/**
 * CLI action name.
 */
const TOOL_UCKKSEED_CLI_ACTION_RESET = 'reset';

/**
 * Seed mode: dry run.
 */
const TOOL_UCKKSEED_CLI_MODE_DRY_RUN = 'dry_run';

/**
 * Seed mode: apply.
 */
const TOOL_UCKKSEED_CLI_MODE_APPLY = 'apply';

/**
 * Seed mode: rollback plan.
 */
const TOOL_UCKKSEED_CLI_MODE_ROLLBACK_PLAN = 'rollback_plan';

/**
 * Default reset scope.
 */
const TOOL_UCKKSEED_CLI_DEFAULT_SCOPE = 'reset_seeded_content';

/**
 * Supported reset scopes.
 */
const TOOL_UCKKSEED_CLI_RESET_SCOPES = [
    'reset_seed_logs',
    'reset_seeded_content',
    'reset_seeded_courses',
    'reset_seeded_roles',
    'reset_seeded_badges',
    'reset_all_uckk_seeded_content',
];

/**
 * Supported preset identifiers.
 */
const TOOL_UCKKSEED_CLI_PRESETS = [
    'categories',
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
 * Supported distribution components.
 */
const TOOL_UCKKSEED_CLI_COMPONENTS = [
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

[$options, $unrecognized] = cli_get_params(
    [
        'help' => false,
        'scope' => TOOL_UCKKSEED_CLI_DEFAULT_SCOPE,
        'preset' => null,
        'component' => null,
        'target' => null,
        'dry-run' => false,
        'rollback-plan' => false,
        'force' => false,
        'confirm' => false,
        'json' => false,
        'quiet' => false,
        'verbose' => false,
    ],
    [
        'h' => 'help',
    ]
);

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized), 2);
}

if (!empty($options['help'])) {
    tool_uckkseed_cli_reset_print_help();
    exit(0);
}

if (moodle_needs_upgrading()) {
    cli_error('Moodle upgrade pending, script execution suspended.', 3);
}

$admin = get_admin();

if (!$admin) {
    cli_error('Error: No admin account was found.', 4);
}

// Run CLI reset as the primary Moodle administrator.
\core\session\manager::set_user($admin);

if (!is_siteadmin($admin)) {
    cli_error('Error: The selected CLI user is not a site administrator.', 5);
}

if (!tool_uckkseed_cli_config_enabled('enabletool', true)) {
    cli_error('The UCKK seed tool is disabled in plugin settings.', 6);
}

if (!tool_uckkseed_cli_config_enabled('allowcli', true)) {
    cli_error('CLI execution for the UCKK seed tool is disabled in plugin settings.', 7);
}

if (!tool_uckkseed_cli_config_enabled('allowreset', false)) {
    cli_error('Reset operations are disabled in UCKK seed tool settings.', 8);
}

$scope = clean_param((string)$options['scope'], PARAM_ALPHANUMEXT);

if (!in_array($scope, TOOL_UCKKSEED_CLI_RESET_SCOPES, true)) {
    cli_error(
        'Invalid reset scope: ' . $scope . "\nAllowed scopes: " . implode(', ', TOOL_UCKKSEED_CLI_RESET_SCOPES),
        9
    );
}

$presets = tool_uckkseed_cli_parse_list_option($options['preset'], TOOL_UCKKSEED_CLI_PRESETS, 'preset');
$components = tool_uckkseed_cli_parse_list_option($options['component'], TOOL_UCKKSEED_CLI_COMPONENTS, 'component');
$targets = tool_uckkseed_cli_parse_free_list_option($options['target']);

$dryrun = !empty($options['dry-run']);
$rollbackplan = !empty($options['rollback-plan']);

if ($dryrun && $rollbackplan) {
    cli_error('Use either --dry-run or --rollback-plan, not both.', 10);
}

$mode = TOOL_UCKKSEED_CLI_MODE_APPLY;

if ($dryrun) {
    $mode = TOOL_UCKKSEED_CLI_MODE_DRY_RUN;
}

if ($rollbackplan) {
    $mode = TOOL_UCKKSEED_CLI_MODE_ROLLBACK_PLAN;
}

$force = !empty($options['force']);
$confirmed = !empty($options['confirm']);
$json = !empty($options['json']);
$quiet = !empty($options['quiet']);
$verbose = !empty($options['verbose']);

if ($quiet && $verbose) {
    cli_error('Use either --quiet or --verbose, not both.', 11);
}

if (!$dryrun && !$rollbackplan && !$confirmed) {
    tool_uckkseed_cli_reset_confirm_or_exit($scope, $force);
}

if (!class_exists(\tool_uckkseed\local\seeder::class)) {
    cli_error('Missing class: tool_uckkseed\local\seeder. Generate classes/local/seeder.php before using this CLI script.', 12);
}

$resetoptions = [
    'action' => TOOL_UCKKSEED_CLI_ACTION_RESET,
    'mode' => $mode,
    'scope' => $scope,
    'presets' => $presets,
    'components' => $components,
    'targets' => $targets,
    'dryrun' => $dryrun,
    'dry_run' => $dryrun,
    'rollbackplan' => $rollbackplan,
    'rollback_plan' => $rollbackplan,
    'force' => $force,
    'confirm' => $confirmed,
    'source' => 'cli',
    'userid' => (int)$admin->id,
    'json' => $json,
    'quiet' => $quiet,
    'verbose' => $verbose,
];

try {
    if (!$quiet && !$json) {
        cli_heading('UCKK seed reset');
        cli_writeln('Action: reset');
        cli_writeln('Mode: ' . $mode);
        cli_writeln('Scope: ' . $scope);

        if (!empty($presets)) {
            cli_writeln('Presets: ' . implode(', ', $presets));
        }

        if (!empty($components)) {
            cli_writeln('Components: ' . implode(', ', $components));
        }

        if (!empty($targets)) {
            cli_writeln('Targets: ' . implode(', ', $targets));
        }

        cli_writeln('');
    }

    $seeder = new \tool_uckkseed\local\seeder();
    $result = $seeder->reset($resetoptions);
    $resultdata = tool_uckkseed_cli_result_to_array($result);

    if ($json) {
        cli_writeln(json_encode($resultdata, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    } else if (!$quiet) {
        tool_uckkseed_cli_print_result($resultdata, $verbose);
    }

    if (tool_uckkseed_cli_result_has_errors($resultdata)) {
        exit(1);
    }

    exit(0);
} catch (\Throwable $exception) {
    if ($json) {
        cli_writeln(json_encode([
            'ok' => false,
            'status' => 'failed',
            'summary' => $exception->getMessage(),
            'exception' => get_class($exception),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        exit(1);
    }

    cli_error($exception->getMessage(), 1);
}

/**
 * Print CLI help.
 *
 * @return void
 */
function tool_uckkseed_cli_reset_print_help(): void {
    $help = <<<EOL
Reset UCKK seeded content.

This command delegates reset planning and execution to tool_uckkseed\\local\\seeder.
By default, destructive reset requires explicit confirmation. Use --dry-run to inspect
what would happen, or --rollback-plan to request a non-destructive rollback plan.

Options:
-h, --help                 Print this help.

--scope=<scope>            Reset scope. Default: reset_seeded_content.
                           Allowed:
                             reset_seed_logs
                             reset_seeded_content
                             reset_seeded_courses
                             reset_seeded_roles
                             reset_seeded_badges
                             reset_all_uckk_seeded_content

--preset=<list>            Comma-separated preset ids to reset or inspect.
                           Allowed:
                             categories, courses, cohorts, roles, capabilities,
                             competencies, badges, reports, course_templates,
                             challenge_templates, assembly_templates, archive_templates

--component=<list>         Comma-separated UCKK components to target.
                           Allowed:
                             local_uckk, theme_uckk, format_uckk,
                             block_uckk_dashboard, mod_uckkchallenge,
                             mod_uckkassembly, mod_uckkarchive,
                             tool_uckkintegrity, report_uckk

--target=<list>            Comma-separated target keys/idnumbers/shortnames.
--dry-run                  Do not change data. Report what would be reset.
--rollback-plan            Do not change data. Produce a rollback plan.
--force                    Allow broader reset scopes when confirmed.
--confirm                  Confirm destructive reset without interactive prompt.
--json                     Emit machine-readable JSON output.
--quiet                    Suppress normal text output.
--verbose                  Print detailed messages.

Examples:
php admin/tool/uckkseed/cli/reset.php --dry-run
php admin/tool/uckkseed/cli/reset.php --scope=reset_seed_logs --confirm
php admin/tool/uckkseed/cli/reset.php --scope=reset_seeded_courses --preset=courses,course_templates --dry-run
php admin/tool/uckkseed/cli/reset.php --scope=reset_all_uckk_seeded_content --force --confirm
php admin/tool/uckkseed/cli/reset.php --rollback-plan --json

EOL;

    echo $help;
}

/**
 * Return whether a boolean plugin config is enabled.
 *
 * @param string $name Config name.
 * @param bool $default Default value when config is unset.
 * @return bool
 */
function tool_uckkseed_cli_config_enabled(string $name, bool $default): bool {
    $value = get_config('tool_uckkseed', $name);

    if ($value === false || $value === null) {
        return $default;
    }

    return (bool)$value;
}

/**
 * Parse and validate a comma-separated list option.
 *
 * @param mixed $value Raw option value.
 * @param string[] $allowed Allowed values.
 * @param string $label Human label for errors.
 * @return string[]
 */
function tool_uckkseed_cli_parse_list_option(mixed $value, array $allowed, string $label): array {
    if ($value === null || $value === false || $value === '') {
        return [];
    }

    if (!is_string($value)) {
        cli_error('Invalid ' . $label . ' option.', 20);
    }

    $items = array_values(array_filter(array_map(static function (string $item): string {
        return clean_param(trim($item), PARAM_ALPHANUMEXT);
    }, explode(',', $value))));

    $unknown = array_values(array_diff($items, $allowed));

    if (!empty($unknown)) {
        cli_error(
            'Invalid ' . $label . ' value(s): ' . implode(', ', $unknown) .
            "\nAllowed values: " . implode(', ', $allowed),
            21
        );
    }

    return array_values(array_unique($items));
}

/**
 * Parse a comma-separated free target list.
 *
 * @param mixed $value Raw option value.
 * @return string[]
 */
function tool_uckkseed_cli_parse_free_list_option(mixed $value): array {
    if ($value === null || $value === false || $value === '') {
        return [];
    }

    if (!is_string($value)) {
        cli_error('Invalid target option.', 22);
    }

    $items = array_values(array_filter(array_map(static function (string $item): string {
        return clean_param(trim($item), PARAM_TEXT);
    }, explode(',', $value))));

    return array_values(array_unique($items));
}

/**
 * Require interactive confirmation for destructive reset when --confirm was not provided.
 *
 * @param string $scope Reset scope.
 * @param bool $force Force flag.
 * @return void
 */
function tool_uckkseed_cli_reset_confirm_or_exit(string $scope, bool $force): void {
    cli_writeln('This command may reset UCKK seeded Moodle content.');
    cli_writeln('Scope: ' . $scope);

    if ($scope === 'reset_all_uckk_seeded_content' && !$force) {
        cli_error('The scope reset_all_uckk_seeded_content requires --force and --confirm.', 30);
    }

    cli_writeln('');
    cli_writeln('Use --dry-run to inspect the operation without changing data.');
    cli_writeln('Use --confirm to run non-interactively.');
    cli_writeln('');

    $answer = cli_input('Type RESET to continue', '', ['RESET', 'reset', '']);

    if (strtoupper($answer) !== 'RESET') {
        cli_writeln('Reset cancelled.');
        exit(0);
    }
}

/**
 * Convert a validation_result or result-like value into an array.
 *
 * @param mixed $result Result object/array/scalar.
 * @return array<string, mixed>
 */
function tool_uckkseed_cli_result_to_array(mixed $result): array {
    if (is_array($result)) {
        return $result;
    }

    if (is_object($result)) {
        if (method_exists($result, 'to_array')) {
            $data = $result->to_array();
            return is_array($data) ? $data : ['ok' => false, 'summary' => 'Invalid result from to_array().'];
        }

        if (method_exists($result, 'export')) {
            $data = $result->export();
            return is_array($data) ? $data : (array)$data;
        }

        if ($result instanceof \JsonSerializable) {
            $data = $result->jsonSerialize();
            return is_array($data) ? $data : (array)$data;
        }

        return get_object_vars($result);
    }

    return [
        'ok' => (bool)$result,
        'status' => $result ? 'completed' : 'failed',
        'summary' => '',
        'messages' => [],
        'counts' => [],
    ];
}

/**
 * Print result output.
 *
 * @param array<string, mixed> $result Result data.
 * @param bool $verbose Whether to print detailed messages.
 * @return void
 */
function tool_uckkseed_cli_print_result(array $result, bool $verbose): void {
    $status = (string)($result['status'] ?? ($result['ok'] ?? false ? 'completed' : 'failed'));
    $summary = (string)($result['summary'] ?? '');

    cli_heading('Reset result');
    cli_writeln('Status: ' . $status);

    if ($summary !== '') {
        cli_writeln('Summary: ' . $summary);
    }

    if (!empty($result['counts']) && is_array($result['counts'])) {
        cli_writeln('');
        cli_writeln('Counts:');

        foreach ($result['counts'] as $key => $value) {
            cli_writeln('  ' . $key . ': ' . $value);
        }
    }

    if (!empty($result['messages']) && is_array($result['messages'])) {
        cli_writeln('');
        cli_writeln($verbose ? 'Messages:' : 'Messages summary:');

        foreach ($result['messages'] as $message) {
            $row = is_array($message) ? $message : (array)$message;
            $severity = (string)($row['severity'] ?? 'info');
            $target = (string)($row['targetkey'] ?? $row['preset'] ?? $row['component'] ?? '');
            $text = (string)($row['message'] ?? '');

            if (!$verbose && !in_array($severity, ['warning', 'error', 'blocker'], true)) {
                continue;
            }

            $prefix = '[' . strtoupper($severity) . ']';

            if ($target !== '') {
                $prefix .= ' ' . $target . ':';
            }

            cli_writeln('  ' . $prefix . ' ' . $text);
        }
    }
}

/**
 * Return whether the result contains errors.
 *
 * @param array<string, mixed> $result Result data.
 * @return bool
 */
function tool_uckkseed_cli_result_has_errors(array $result): bool {
    if (array_key_exists('ok', $result) && empty($result['ok'])) {
        return true;
    }

    if (!empty($result['haserrors'])) {
        return true;
    }

    if (!empty($result['counts']) && is_array($result['counts'])) {
        if (!empty($result['counts']['errors']) || !empty($result['counts']['failed'])) {
            return true;
        }
    }

    if (!empty($result['messages']) && is_array($result['messages'])) {
        foreach ($result['messages'] as $message) {
            $row = is_array($message) ? $message : (array)$message;
            $severity = (string)($row['severity'] ?? '');

            if (in_array($severity, ['error', 'blocker'], true)) {
                return true;
            }
        }
    }

    return false;
}