<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

/**
 * Export UCKK seed presets from the command line.
 *
 * @package    tool_uckkseed
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../../config.php');
require_once($CFG->libdir . '/clilib.php');

use tool_uckkseed\local\seeder;

const TOOL_UCKKSEED_CLI_ACTION = 'export_preset';
const TOOL_UCKKSEED_CLI_MODE = 'report';
const TOOL_UCKKSEED_PRESET_SCHEMA = 'uckkseed.preset.v1';
const TOOL_UCKKSEED_COMPONENT = 'tool_uckkseed';

/**
 * Return canonical preset ids.
 *
 * @return string[]
 */
function tool_uckkseed_cli_exportable_presets(): array {
    return [
        'categories',
        'programs',
        'pathways',
        'cohorts',
        'roles',
        'capabilities',
        'competencies',
        'badges',
        'course_templates',
        'challenge_templates',
        'assembly_templates',
        'archive_templates',
        'courses',
        'reports',
    ];
}

/**
 * Print CLI help.
 *
 * @return void
 */
function tool_uckkseed_cli_export_preset_help(): void {
    global $CFG;

    $script = 'admin/tool/uckkseed/cli/export_preset.php';

    cli_writeln("Export UCKK seed presets.");
    cli_writeln('');
    cli_writeln("Usage:");
    cli_writeln("  php {$script} --preset=categories");
    cli_writeln("  php {$script} --preset=all --output=/path/to/export-dir");
    cli_writeln("  php {$script} --preset=programs --output=/path/to/programs.json --pretty");
    cli_writeln("  php {$script} --preset=pathways --output=/path/to/pathways.json --pretty");
    cli_writeln("  php {$script} --preset=courses --output=/path/to/courses.json --pretty");
    cli_writeln("  php {$script} --list");
    cli_writeln('');
    cli_writeln("Options:");
    cli_writeln("  --preset=<id>       Preset id to export. Use 'all' to export all presets.");
    cli_writeln("  --output=<path>     Optional output file or directory.");
    cli_writeln("  --component=<name>  Optional component filter.");
    cli_writeln("  --pretty           Pretty-print JSON output.");
    cli_writeln("  --json             Print JSON only, without headings.");
    cli_writeln("  --force            Overwrite existing output files.");
    cli_writeln("  --list             List canonical preset ids.");
    cli_writeln("  --help             Print this help.");
    cli_writeln('');
    cli_writeln("Canonical presets:");
    foreach (tool_uckkseed_cli_exportable_presets() as $preset) {
        cli_writeln("  - {$preset}");
    }
    cli_writeln('');
    cli_writeln("Moodle root:");
    cli_writeln("  {$CFG->dirroot}");
}

/**
 * Normalise a preset id.
 *
 * @param string $preset Raw preset id.
 * @return string
 */
function tool_uckkseed_cli_normalise_preset(string $preset): string {
    $preset = trim($preset);

    if ($preset === '') {
        return 'all';
    }

    $preset = strtolower($preset);
    $preset = str_replace('-', '_', $preset);

    return clean_param($preset, PARAM_ALPHANUMEXT);
}

/**
 * Validate requested preset.
 *
 * @param string $preset Preset id or all.
 * @return void
 */
function tool_uckkseed_cli_validate_preset(string $preset): void {
    if ($preset === 'all') {
        return;
    }

    if (!in_array($preset, tool_uckkseed_cli_exportable_presets(), true)) {
        cli_error("Unknown preset '{$preset}'. Use --list to see valid preset ids.", 2);
    }
}

/**
 * Return whether CLI use is enabled.
 *
 * Missing config is treated as enabled so fresh installs and PHPUnit fixtures
 * can run before settings are saved.
 *
 * @return bool
 */
function tool_uckkseed_cli_is_enabled(): bool {
    $enabled = get_config(TOOL_UCKKSEED_COMPONENT, 'enabletool');
    $allowcli = get_config(TOOL_UCKKSEED_COMPONENT, 'allowcli');

    if ($enabled !== false && (string)$enabled === '0') {
        return false;
    }

    if ($allowcli !== false && (string)$allowcli === '0') {
        return false;
    }

    return true;
}

/**
 * Convert a validation_result-like value to array.
 *
 * @param mixed $result Result object or array.
 * @return array<string, mixed>
 */
function tool_uckkseed_cli_result_to_array(mixed $result): array {
    if (is_array($result)) {
        return $result;
    }

    if (is_object($result)) {
        if (method_exists($result, 'to_array')) {
            $data = $result->to_array();

            if (is_array($data)) {
                return $data;
            }
        }

        if (method_exists($result, 'export_for_cli')) {
            $data = $result->export_for_cli();

            if (is_array($data)) {
                return $data;
            }
        }

        if (method_exists($result, 'to_record')) {
            $data = $result->to_record();

            if ($data instanceof stdClass) {
                return (array)$data;
            }

            if (is_array($data)) {
                return $data;
            }
        }

        return get_object_vars($result);
    }

    return [
        'ok' => false,
        'status' => 'failed',
        'summary' => 'Seeder returned an unsupported result type.',
        'metadata' => [
            'type' => get_debug_type($result),
        ],
    ];
}

/**
 * Return whether a result is successful.
 *
 * @param array<string, mixed> $result Result array.
 * @return bool
 */
function tool_uckkseed_cli_result_is_ok(array $result): bool {
    if (array_key_exists('ok', $result)) {
        return (bool)$result['ok'];
    }

    if (array_key_exists('haserrors', $result)) {
        return !(bool)$result['haserrors'];
    }

    return ($result['status'] ?? 'failed') !== 'failed';
}

/**
 * Extract exported preset payload from a seeder result.
 *
 * The canonical result should place exported data under metadata.export or
 * metadata.presets. This helper also accepts direct data/preset keys to keep
 * CLI output tolerant during early implementation.
 *
 * @param array<string, mixed> $result Result array.
 * @param string $preset Requested preset.
 * @return array<string, mixed>
 */
function tool_uckkseed_cli_extract_export_payload(array $result, string $preset): array {
    $metadata = $result['metadata'] ?? [];

    if ($metadata instanceof stdClass) {
        $metadata = (array)$metadata;
    }

    if (!is_array($metadata)) {
        $metadata = [];
    }

    foreach (['export', 'presets', 'preset', 'data'] as $key) {
        if (array_key_exists($key, $metadata) && is_array($metadata[$key])) {
            return tool_uckkseed_cli_normalise_export_payload($metadata[$key], $preset);
        }
    }

    foreach (['export', 'presets', 'preset', 'data'] as $key) {
        if (array_key_exists($key, $result) && is_array($result[$key])) {
            return tool_uckkseed_cli_normalise_export_payload($result[$key], $preset);
        }
    }

    return tool_uckkseed_cli_normalise_export_payload([
        'schema' => TOOL_UCKKSEED_PRESET_SCHEMA,
        'component' => TOOL_UCKKSEED_COMPONENT,
        'preset' => $preset,
        'version' => 2026051200,
        'items' => [],
        'messages' => $result['messages'] ?? [],
    ], $preset);
}

/**
 * Ensure exported payload has canonical top-level fields.
 *
 * @param array<string, mixed> $payload Export payload.
 * @param string $preset Requested preset.
 * @return array<string, mixed>
 */
function tool_uckkseed_cli_normalise_export_payload(array $payload, string $preset): array {
    if ($preset === 'all') {
        if (!isset($payload['schema'])) {
            $payload = [
                'schema' => TOOL_UCKKSEED_PRESET_SCHEMA,
                'component' => TOOL_UCKKSEED_COMPONENT,
                'preset' => 'all',
                'version' => 2026051200,
                'presets' => $payload,
            ];
        }

        $payload['schema'] = $payload['schema'] ?? TOOL_UCKKSEED_PRESET_SCHEMA;
        $payload['component'] = $payload['component'] ?? TOOL_UCKKSEED_COMPONENT;
        $payload['preset'] = $payload['preset'] ?? 'all';
        $payload['version'] = $payload['version'] ?? 2026051200;

        return $payload;
    }

    $payload['schema'] = $payload['schema'] ?? TOOL_UCKKSEED_PRESET_SCHEMA;
    $payload['component'] = $payload['component'] ?? TOOL_UCKKSEED_COMPONENT;
    $payload['preset'] = $payload['preset'] ?? $preset;
    $payload['version'] = $payload['version'] ?? 2026051200;
    $payload['items'] = $payload['items'] ?? [];

    if (!is_array($payload['items'])) {
        $payload['items'] = [];
    }

    return $payload;
}

/**
 * Encode JSON for CLI output.
 *
 * @param array<string, mixed> $payload Payload.
 * @param bool $pretty Pretty-print.
 * @return string
 */
function tool_uckkseed_cli_encode_json(array $payload, bool $pretty): string {
    $flags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;

    if ($pretty) {
        $flags |= JSON_PRETTY_PRINT;
    }

    $json = json_encode($payload, $flags);

    if ($json === false) {
        cli_error('Unable to encode preset export as JSON: ' . json_last_error_msg(), 3);
    }

    return $json . PHP_EOL;
}

/**
 * Write export payload to disk.
 *
 * If the requested preset is all and the output path is a directory, each
 * preset is written to its own <preset>.json file when payload.presets exists.
 * Otherwise one JSON file is written.
 *
 * @param array<string, mixed> $payload Export payload.
 * @param string $output Output path.
 * @param string $preset Requested preset.
 * @param bool $pretty Pretty-print.
 * @param bool $force Overwrite existing files.
 * @return string[] Written file paths.
 */
function tool_uckkseed_cli_write_output(
    array $payload,
    string $output,
    string $preset,
    bool $pretty,
    bool $force
): array {
    $output = trim($output);

    if ($output === '') {
        return [];
    }

    if ($preset === 'all' && is_dir($output) && !empty($payload['presets']) && is_array($payload['presets'])) {
        $written = [];

        foreach ($payload['presets'] as $presetid => $presetpayload) {
            $presetid = tool_uckkseed_cli_normalise_preset((string)$presetid);

            if ($presetid === '' || $presetid === 'all') {
                continue;
            }

            if (!is_array($presetpayload)) {
                continue;
            }

            $file = rtrim($output, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $presetid . '.json';
            $written[] = tool_uckkseed_cli_write_json_file(
                $file,
                tool_uckkseed_cli_normalise_export_payload($presetpayload, $presetid),
                $pretty,
                $force
            );
        }

        return $written;
    }

    if (is_dir($output)) {
        $filename = $preset === 'all' ? 'uckkseed_presets.json' : $preset . '.json';
        $output = rtrim($output, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $filename;
    }

    return [
        tool_uckkseed_cli_write_json_file($output, $payload, $pretty, $force),
    ];
}

/**
 * Write one JSON file.
 *
 * @param string $file File path.
 * @param array<string, mixed> $payload Payload.
 * @param bool $pretty Pretty-print.
 * @param bool $force Overwrite existing file.
 * @return string Written file path.
 */
function tool_uckkseed_cli_write_json_file(string $file, array $payload, bool $pretty, bool $force): string {
    $directory = dirname($file);

    if (!is_dir($directory)) {
        if (!mkdir($directory, $CFG->directorypermissions ?? 0777, true) && !is_dir($directory)) {
            cli_error("Unable to create output directory: {$directory}", 4);
        }
    }

    if (file_exists($file) && !$force) {
        cli_error("Output file already exists: {$file}. Use --force to overwrite.", 4);
    }

    $json = tool_uckkseed_cli_encode_json($payload, $pretty);

    if (file_put_contents($file, $json) === false) {
        cli_error("Unable to write output file: {$file}", 4);
    }

    return $file;
}

[$options, $unrecognised] = cli_get_params(
    [
        'help' => false,
        'preset' => 'all',
        'output' => '',
        'component' => '',
        'pretty' => false,
        'json' => false,
        'force' => false,
        'list' => false,
    ],
    [
        'h' => 'help',
        'p' => 'preset',
        'o' => 'output',
    ]
);

if ($unrecognised) {
    $unrecognised = implode(PHP_EOL . '  ', $unrecognised);
    cli_error("Unknown option(s):" . PHP_EOL . "  {$unrecognised}", 2);
}

if (!empty($options['help'])) {
    tool_uckkseed_cli_export_preset_help();
    exit(0);
}

if (!empty($options['list'])) {
    foreach (tool_uckkseed_cli_exportable_presets() as $preset) {
        cli_writeln($preset);
    }
    exit(0);
}

$preset = tool_uckkseed_cli_normalise_preset((string)$options['preset']);
$component = clean_param((string)$options['component'], PARAM_COMPONENT);
$output = trim((string)$options['output']);
$pretty = !empty($options['pretty']);
$jsononly = !empty($options['json']);
$force = !empty($options['force']);

tool_uckkseed_cli_validate_preset($preset);

if (!tool_uckkseed_cli_is_enabled()) {
    cli_error('tool_uckkseed CLI execution is disabled by plugin configuration.', 1);
}

$admin = get_admin();

if (!$admin || empty($admin->id)) {
    cli_error('Unable to load Moodle admin user for seed export logging.', 1);
}

cron_setup_user($admin);

try {
    $seeder = new seeder();

    $result = $seeder->export_preset([
        'action' => TOOL_UCKKSEED_CLI_ACTION,
        'mode' => TOOL_UCKKSEED_CLI_MODE,
        'source' => 'cli',
        'preset' => $preset,
        'component' => $component,
        'format' => 'json',
        'schema' => TOOL_UCKKSEED_PRESET_SCHEMA,
        'output' => $output,
        'force' => $force,
    ]);

    $resultdata = tool_uckkseed_cli_result_to_array($result);

    if (!tool_uckkseed_cli_result_is_ok($resultdata)) {
        $summary = $resultdata['summary'] ?? 'Preset export failed.';
        cli_error((string)$summary, 1);
    }

    $payload = tool_uckkseed_cli_extract_export_payload($resultdata, $preset);

    if ($output !== '') {
        $written = tool_uckkseed_cli_write_output($payload, $output, $preset, $pretty, $force);

        if (!$jsononly) {
            cli_heading(get_string('exportpreset', 'tool_uckkseed'));
            cli_writeln(get_string('status_completed', 'tool_uckkseed'));

            foreach ($written as $file) {
                cli_writeln($file);
            }
        }

        exit(0);
    }

    if (!$jsononly) {
        cli_heading(get_string('exportpreset', 'tool_uckkseed'));
    }

    cli_write(tool_uckkseed_cli_encode_json($payload, $pretty));

    exit(0);
} catch (Throwable $exception) {
    if (!empty($CFG->debugdeveloper)) {
        cli_error($exception->getMessage() . PHP_EOL . $exception->getTraceAsString(), 1);
    }

    cli_error($exception->getMessage(), 1);
}