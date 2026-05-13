<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

/**
 * CLI validation entry point for the UCKK seed tool.
 *
 * @package    tool_uckkseed
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require_once(__DIR__ . '/../../../../config.php');
require_once($CFG->libdir . '/clilib.php');

use tool_uckkseed\local\seeder;
use tool_uckkseed\local\validation_result;

const TOOL_UCKKSEED_CLI_ACTION_VALIDATE = 'validate';
const TOOL_UCKKSEED_CLI_MODE_REPORT = 'report';
const TOOL_UCKKSEED_CLI_MODE_DRY_RUN = 'dry_run';
const TOOL_UCKKSEED_CLI_STATUS_COMPLETED = 'completed';
const TOOL_UCKKSEED_CLI_STATUS_FAILED = 'failed';

/**
 * Render CLI help.
 *
 * @return string
 */
function tool_uckkseed_cli_validate_help(): string {
    return <<<HELP
Validate UCKK seed presets and distribution state.

This command validates the UCKK seed configuration without creating, resetting,
or modifying Moodle records. It checks preset structure, dependency readiness,
stable keys, and existing seeded objects when supported by the seeder service.

Usage:
  php admin/tool/uckkseed/cli/validate.php [options]

Options:
  --preset=<name>       Validate one preset only.
                        Examples: categories, courses, roles, badges.
  --component=<name>    Limit validation to a Moodle component.
                        Example: mod_uckkarchive.
  --target=<key>        Limit validation to one target key when supported.
                        Example: UCKK-TC101.
  --dry-run             Mark execution mode as dry_run.
  --strict              Return exit code 1 when warnings are present.
  --json                Print machine-readable JSON.
  --quiet               Print only final status unless --json is used.
  -h, --help            Print this help.

Canonical action:
  validate

Canonical modes:
  report
  dry_run

Examples:
  php admin/tool/uckkseed/cli/validate.php
  php admin/tool/uckkseed/cli/validate.php --preset=courses
  php admin/tool/uckkseed/cli/validate.php --preset=courses --target=UCKK-TC101
  php admin/tool/uckkseed/cli/validate.php --json
  php admin/tool/uckkseed/cli/validate.php --strict

HELP;
}

/**
 * Convert a comma-separated option to a clean array.
 *
 * @param string|null $value Raw CLI value.
 * @return string[]
 */
function tool_uckkseed_cli_validate_list(?string $value): array {
    if ($value === null || trim($value) === '') {
        return [];
    }

    $items = array_map('trim', explode(',', $value));
    $items = array_filter($items, static fn(string $item): bool => $item !== '');

    return array_values(array_unique($items));
}

/**
 * Convert a validation result to a plain array for JSON output.
 *
 * @param mixed $result Validation result object.
 * @return array<string, mixed>
 */
function tool_uckkseed_cli_validate_result_to_array(mixed $result): array {
    if ($result instanceof validation_result && method_exists($result, 'to_array')) {
        return $result->to_array();
    }

    if (is_object($result) && method_exists($result, 'to_array')) {
        return $result->to_array();
    }

    if (is_object($result)) {
        $data = get_object_vars($result);
    } else if (is_array($result)) {
        $data = $result;
    } else {
        $data = [
            'status' => TOOL_UCKKSEED_CLI_STATUS_FAILED,
            'ok' => false,
            'summary' => (string)$result,
        ];
    }

    return [
        'status' => (string)($data['status'] ?? TOOL_UCKKSEED_CLI_STATUS_COMPLETED),
        'ok' => (bool)($data['ok'] ?? empty($data['haserrors'])),
        'haserrors' => (bool)($data['haserrors'] ?? false),
        'haswarnings' => (bool)($data['haswarnings'] ?? false),
        'summary' => (string)($data['summary'] ?? ''),
        'counts' => $data['counts'] ?? [],
        'messages' => $data['messages'] ?? [],
        'metadata' => $data['metadata'] ?? [],
    ];
}

/**
 * Read a boolean-like value from result data.
 *
 * @param array<string, mixed> $data Result data.
 * @param string $key Data key.
 * @return bool
 */
function tool_uckkseed_cli_validate_bool(array $data, string $key): bool {
    return !empty($data[$key]);
}

/**
 * Render human-readable result output.
 *
 * @param array<string, mixed> $data Result data.
 * @param bool $quiet Whether to suppress detailed output.
 * @return void
 */
function tool_uckkseed_cli_validate_print_result(array $data, bool $quiet): void {
    $ok = tool_uckkseed_cli_validate_bool($data, 'ok');
    $haserrors = tool_uckkseed_cli_validate_bool($data, 'haserrors');
    $haswarnings = tool_uckkseed_cli_validate_bool($data, 'haswarnings');

    $status = (string)($data['status'] ?? ($ok ? TOOL_UCKKSEED_CLI_STATUS_COMPLETED : TOOL_UCKKSEED_CLI_STATUS_FAILED));
    $summary = (string)($data['summary'] ?? '');

    cli_writeln('');
    cli_writeln('UCKK seed validation');
    cli_writeln('====================');
    cli_writeln('Status: ' . $status);
    cli_writeln('Result: ' . ($ok ? 'OK' : 'FAILED'));

    if ($summary !== '') {
        cli_writeln('Summary: ' . $summary);
    }

    if ($quiet) {
        return;
    }

    if (!empty($data['counts']) && is_array($data['counts'])) {
        cli_writeln('');
        cli_writeln('Counts:');

        foreach ($data['counts'] as $key => $value) {
            cli_writeln('  - ' . $key . ': ' . $value);
        }
    }

    if (!empty($data['messages']) && is_array($data['messages'])) {
        cli_writeln('');
        cli_writeln('Messages:');

        foreach ($data['messages'] as $message) {
            if (is_object($message)) {
                $message = get_object_vars($message);
            }

            if (!is_array($message)) {
                cli_writeln('  - ' . (string)$message);
                continue;
            }

            $severity = (string)($message['severity'] ?? 'info');
            $preset = (string)($message['preset'] ?? '');
            $targettype = (string)($message['targettype'] ?? '');
            $targetkey = (string)($message['targetkey'] ?? '');
            $text = (string)($message['message'] ?? '');

            $prefix = '[' . strtoupper($severity) . ']';

            if ($preset !== '') {
                $prefix .= ' [' . $preset . ']';
            }

            if ($targettype !== '' || $targetkey !== '') {
                $prefix .= ' [' . trim($targettype . ':' . $targetkey, ':') . ']';
            }

            cli_writeln('  - ' . $prefix . ' ' . $text);
        }
    }

    if ($haserrors) {
        cli_writeln('');
        cli_writeln('Validation completed with errors.');
    } else if ($haswarnings) {
        cli_writeln('');
        cli_writeln('Validation completed with warnings.');
    } else {
        cli_writeln('');
        cli_writeln('Validation completed successfully.');
    }
}

[$options, $unrecognised] = cli_get_params(
    [
        'help' => false,
        'preset' => null,
        'component' => null,
        'target' => null,
        'dry-run' => false,
        'strict' => false,
        'json' => false,
        'quiet' => false,
    ],
    [
        'h' => 'help',
    ]
);

if (!empty($unrecognised)) {
    $unrecognised = implode("\n  ", $unrecognised);
    cli_error("Unknown option(s):\n  {$unrecognised}");
}

if (!empty($options['help'])) {
    cli_writeln(tool_uckkseed_cli_validate_help());
    exit(0);
}

if (!empty($options['json']) && !empty($options['quiet'])) {
    cli_error('Options --json and --quiet cannot be used together.');
}

$enabled = get_config('tool_uckkseed', 'enabletool');
$allowcli = get_config('tool_uckkseed', 'allowcli');

if ($enabled !== false && (int)$enabled === 0) {
    cli_error('The UCKK seed tool is disabled in site configuration.');
}

if ($allowcli !== false && (int)$allowcli === 0) {
    cli_error('CLI execution is disabled for the UCKK seed tool.');
}

\core\session\manager::set_user(get_admin());

$mode = !empty($options['dry-run'])
    ? TOOL_UCKKSEED_CLI_MODE_DRY_RUN
    : TOOL_UCKKSEED_CLI_MODE_REPORT;

$validateoptions = [
    'action' => TOOL_UCKKSEED_CLI_ACTION_VALIDATE,
    'mode' => $mode,
    'source' => 'cli',
    'preset' => $options['preset'] !== null ? clean_param((string)$options['preset'], PARAM_ALPHANUMEXT) : '',
    'presets' => tool_uckkseed_cli_validate_list($options['preset']),
    'component' => $options['component'] !== null ? clean_param((string)$options['component'], PARAM_COMPONENT) : '',
    'components' => tool_uckkseed_cli_validate_list($options['component']),
    'target' => $options['target'] !== null ? clean_param((string)$options['target'], PARAM_TEXT) : '',
    'strict' => !empty($options['strict']),
    'json' => !empty($options['json']),
    'quiet' => !empty($options['quiet']),
];

try {
    $seeder = new seeder();
    $result = $seeder->validate($validateoptions);
    $data = tool_uckkseed_cli_validate_result_to_array($result);

    if (!empty($options['json'])) {
        cli_writeln(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    } else {
        tool_uckkseed_cli_validate_print_result($data, !empty($options['quiet']));
    }

    $haserrors = tool_uckkseed_cli_validate_bool($data, 'haserrors') || !tool_uckkseed_cli_validate_bool($data, 'ok');
    $haswarnings = tool_uckkseed_cli_validate_bool($data, 'haswarnings');

    if ($haserrors || (!empty($options['strict']) && $haswarnings)) {
        exit(1);
    }

    exit(0);
} catch (\Throwable $exception) {
    if (!empty($options['json'])) {
        cli_writeln(json_encode([
            'status' => TOOL_UCKKSEED_CLI_STATUS_FAILED,
            'ok' => false,
            'haserrors' => true,
            'haswarnings' => false,
            'summary' => $exception->getMessage(),
            'exception' => get_class($exception),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    } else {
        cli_writeln('');
        cli_writeln('UCKK seed validation failed.');
        cli_writeln($exception->getMessage());

        if (!empty($CFG->debugdeveloper)) {
            cli_writeln($exception->getTraceAsString());
        }
    }

    exit(1);
}