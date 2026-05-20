<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

/**
 * Main orchestration service for the UCKK Seed tool.
 *
 * @package    tool_uckkseed
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace tool_uckkseed\local;

use coding_exception;
use moodle_exception;
use stdClass;

defined('MOODLE_INTERNAL') || die();

/**
 * Main seeder for the UCKK Moodle distribution.
 *
 * This class coordinates seed, reset, validate, and preset export operations.
 * It does not own domain logic for courses, challenges, assemblies, archives,
 * reports, integrity, badges, competencies, or roles. Those are delegated to
 * specialised seed classes and Moodle APIs.
 */
final class seeder {
    /** Plugin component. */
    public const COMPONENT = 'tool_uckkseed';

    /** Default academic registry JSON directory, relative to Moodle dirroot. */
    public const DEFAULT_PRESET_PATH = 'academic_registry_json';

    /** Run table. */
    public const TABLE_RUN = 'tool_uckkseed_run';

    /** Log table. */
    public const TABLE_LOG = 'tool_uckkseed_log';

    /** Preset schema id. */
    public const PRESET_SCHEMA = 'uckkseed.preset.v1';

    /** Plugin version used by presets. */
    public const PRESET_VERSION = 2026051200;

    /** Action: seed. */
    public const ACTION_SEED = 'seed';

    /** Action: reset. */
    public const ACTION_RESET = 'reset';

    /** Action: validate. */
    public const ACTION_VALIDATE = 'validate';

    /** Action: export preset. */
    public const ACTION_EXPORT_PRESET = 'export_preset';

    /** Mode: dry run. */
    public const MODE_DRY_RUN = 'dry_run';

    /** Mode: apply. */
    public const MODE_APPLY = 'apply';

    /** Mode: report. */
    public const MODE_REPORT = 'report';

    /** Mode: rollback plan. */
    public const MODE_ROLLBACK_PLAN = 'rollback_plan';

    /** Run status: pending. */
    public const STATUS_PENDING = 'pending';

    /** Run status: running. */
    public const STATUS_RUNNING = 'running';

    /** Run status: completed. */
    public const STATUS_COMPLETED = 'completed';

    /** Run status: failed. */
    public const STATUS_FAILED = 'failed';

    /** Run status: cancelled. */
    public const STATUS_CANCELLED = 'cancelled';

    /** Run status: skipped. */
    public const STATUS_SKIPPED = 'skipped';

    /** Run status: warning. */
    public const STATUS_WARNING = 'warning';

    /** Severity: info. */
    public const SEVERITY_INFO = 'info';

    /** Severity: success. */
    public const SEVERITY_SUCCESS = 'success';

    /** Severity: warning. */
    public const SEVERITY_WARNING = 'warning';

    /** Severity: error. */
    public const SEVERITY_ERROR = 'error';

    /** Severity: blocker. */
    public const SEVERITY_BLOCKER = 'blocker';

    /** Preset: categories. */
    public const PRESET_CATEGORIES = 'categories';

    /** Preset: programs. */
    public const PRESET_PROGRAMS = 'programs';

    /** Preset: pathways. */
    public const PRESET_PATHWAYS = 'pathways';

    /** Preset: courses. */
    public const PRESET_COURSES = 'courses';

    /** Preset: cohorts. */
    public const PRESET_COHORTS = 'cohorts';

    /** Preset: roles. */
    public const PRESET_ROLES = 'roles';

    /** Preset: capabilities. */
    public const PRESET_CAPABILITIES = 'capabilities';

    /** Preset: competencies. */
    public const PRESET_COMPETENCIES = 'competencies';

    /** Preset: badges. */
    public const PRESET_BADGES = 'badges';

    /** Preset: reports. */
    public const PRESET_REPORTS = 'reports';

    /** Preset: course templates. */
    public const PRESET_COURSE_TEMPLATES = 'course_templates';

    /** Preset: challenge templates. */
    public const PRESET_CHALLENGE_TEMPLATES = 'challenge_templates';

    /** Preset: assembly templates. */
    public const PRESET_ASSEMBLY_TEMPLATES = 'assembly_templates';

    /** Preset: archive templates. */
    public const PRESET_ARCHIVE_TEMPLATES = 'archive_templates';

    /** @var string Absolute preset directory path. */
    private string $presetpath;

    /**
     * Constructor.
     *
     * @param string|null $presetpath Optional preset directory path.
     */
    public function __construct(?string $presetpath = null) {
        $this->presetpath = $this->resolve_preset_path($presetpath);
    }

    /**
     * Seed the UCKK distribution.
     *
     * @param array<string, mixed> $options Options.
     * @return validation_result
     */
    public function seed(array $options): validation_result {
        $options = $this->normalise_options($options, self::ACTION_SEED);

        if (!$this->tool_is_enabled($options)) {
            return $this->new_result([
                'status' => self::STATUS_FAILED,
                'ok' => false,
                'haserrors' => true,
                'summary' => get_string('seedtooldisabled', self::COMPONENT),
                'messages' => [
                    $this->message(
                        self::SEVERITY_BLOCKER,
                        get_string('seedtooldisabled', self::COMPONENT),
                        ['action' => self::ACTION_SEED]
                    ),
                ],
            ]);
        }

        $runid = $this->create_run(self::ACTION_SEED, $options['mode'], $options);

        try {
            $this->log_step($runid, self::SEVERITY_INFO, get_string('seedstarted', self::COMPONENT), [
                'action' => self::ACTION_SEED,
                'mode' => $options['mode'],
            ]);

            $presets = $this->load_presets($options['presets']);
            $children = [];

            foreach ($presets as $presetid => $presetdata) {
                $children[] = $this->run_preset($presetid, [
                    ...$options,
                    'runid' => $runid,
                    'action' => self::ACTION_SEED,
                    'presetdata' => $presetdata,
                ]);
            }

            $result = $this->aggregate_results($children, get_string('seedcompleted', self::COMPONENT));

            $this->finish_run($runid, $this->status_from_result($result), $result);
            return $result;
        } catch (\Throwable $exception) {
            $result = $this->exception_result($exception, get_string('seedfailed', self::COMPONENT));
            $this->log_step($runid, self::SEVERITY_ERROR, $exception->getMessage(), [
                'exception' => get_class($exception),
            ]);
            $this->finish_run($runid, self::STATUS_FAILED, $result);
            return $result;
        }
    }

    /**
     * Reset UCKK seeded content.
     *
     * Reset is intentionally conservative. It must only affect records created
     * or explicitly marked by the seed tool or by seed handlers.
     *
     * @param array<string, mixed> $options Options.
     * @return validation_result
     */
    public function reset(array $options): validation_result {
        $options = $this->normalise_options($options, self::ACTION_RESET);

        if (!$this->tool_is_enabled($options)) {
            return $this->new_result([
                'status' => self::STATUS_FAILED,
                'ok' => false,
                'haserrors' => true,
                'summary' => get_string('seedtooldisabled', self::COMPONENT),
                'messages' => [
                    $this->message(
                        self::SEVERITY_BLOCKER,
                        get_string('seedtooldisabled', self::COMPONENT),
                        ['action' => self::ACTION_RESET]
                    ),
                ],
            ]);
        }

        if (!$this->reset_is_allowed($options)) {
            return $this->new_result([
                'status' => self::STATUS_FAILED,
                'ok' => false,
                'haserrors' => true,
                'summary' => get_string('resetdisabled', self::COMPONENT),
                'messages' => [
                    $this->message(
                        self::SEVERITY_BLOCKER,
                        get_string('resetdisabled', self::COMPONENT),
                        ['action' => self::ACTION_RESET]
                    ),
                ],
            ]);
        }

        if (!$this->has_reset_confirmation($options)) {
            return $this->new_result([
                'status' => self::STATUS_FAILED,
                'ok' => false,
                'haserrors' => true,
                'summary' => get_string('confirmationrequired', self::COMPONENT),
                'messages' => [
                    $this->message(
                        self::SEVERITY_BLOCKER,
                        get_string('confirmationrequired', self::COMPONENT),
                        ['action' => self::ACTION_RESET]
                    ),
                ],
            ]);
        }

        $runid = $this->create_run(self::ACTION_RESET, $options['mode'], $options);

        try {
            $this->log_step($runid, self::SEVERITY_WARNING, get_string('resetstarted', self::COMPONENT), [
                'action' => self::ACTION_RESET,
                'mode' => $options['mode'],
            ]);

            $presets = $this->load_presets($options['presets']);
            $children = [];

            foreach (array_reverse($presets, true) as $presetid => $presetdata) {
                $children[] = $this->run_preset($presetid, [
                    ...$options,
                    'runid' => $runid,
                    'action' => self::ACTION_RESET,
                    'presetdata' => $presetdata,
                ]);
            }

            $result = $this->aggregate_results($children, get_string('resetcompleted', self::COMPONENT));

            $this->finish_run($runid, $this->status_from_result($result), $result);
            return $result;
        } catch (\Throwable $exception) {
            $result = $this->exception_result($exception, get_string('resetfailed', self::COMPONENT));
            $this->log_step($runid, self::SEVERITY_ERROR, $exception->getMessage(), [
                'exception' => get_class($exception),
            ]);
            $this->finish_run($runid, self::STATUS_FAILED, $result);
            return $result;
        }
    }

    /**
     * Validate UCKK seed presets and target environment.
     *
     * @param array<string, mixed> $options Options.
     * @return validation_result
     */
    public function validate(array $options): validation_result {
        $options = $this->normalise_options($options, self::ACTION_VALIDATE);
        $options['mode'] = self::MODE_REPORT;

        $runid = $this->create_run(self::ACTION_VALIDATE, self::MODE_REPORT, $options);

        try {
            $this->log_step($runid, self::SEVERITY_INFO, get_string('validationstarted', self::COMPONENT), [
                'action' => self::ACTION_VALIDATE,
            ]);

            $presets = $this->load_presets($options['presets']);
            $children = [];

            foreach ($presets as $presetid => $presetdata) {
                $children[] = $this->run_preset($presetid, [
                    ...$options,
                    'runid' => $runid,
                    'action' => self::ACTION_VALIDATE,
                    'mode' => self::MODE_REPORT,
                    'presetdata' => $presetdata,
                ]);
            }

            $result = $this->aggregate_results($children, get_string('validationcompleted', self::COMPONENT));

            $this->finish_run($runid, $this->status_from_result($result), $result);
            return $result;
        } catch (\Throwable $exception) {
            $result = $this->exception_result($exception, get_string('validationfailed', self::COMPONENT));
            $this->log_step($runid, self::SEVERITY_ERROR, $exception->getMessage(), [
                'exception' => get_class($exception),
            ]);
            $this->finish_run($runid, self::STATUS_FAILED, $result);
            return $result;
        }
    }

    /**
     * Export canonical preset data.
     *
     * @param array<string, mixed> $options Options.
     * @return validation_result
     */
    public function export_preset(array $options): validation_result {
        $options = $this->normalise_options($options, self::ACTION_EXPORT_PRESET);
        $options['mode'] = self::MODE_REPORT;

        $runid = $this->create_run(self::ACTION_EXPORT_PRESET, self::MODE_REPORT, $options);

        try {
            $presets = $this->normalise_preset_ids($options['presets']);
            $children = [];
            $exports = [];

            foreach ($presets as $presetid) {
                $handler = $this->get_handler($presetid);

                if ($handler === null || !method_exists($handler, 'export')) {
                    $children[] = $this->new_result([
                        'status' => self::STATUS_FAILED,
                        'ok' => false,
                        'haserrors' => true,
                        'summary' => get_string('presetexportunsupported', self::COMPONENT, $presetid),
                        'messages' => [
                            $this->message(
                                self::SEVERITY_ERROR,
                                get_string('presetexportunsupported', self::COMPONENT, $presetid),
                                ['preset' => $presetid]
                            ),
                        ],
                    ]);
                    continue;
                }

                $export = $handler->export([
                    ...$options,
                    'preset' => $presetid,
                    'runid' => $runid,
                ]);

                $exports[$presetid] = $this->normalise_export_payload($presetid, $export);

                $children[] = $this->new_result([
                    'status' => self::STATUS_COMPLETED,
                    'ok' => true,
                    'summary' => get_string('presetexported', self::COMPONENT, $presetid),
                    'counts' => [
                        'created' => 0,
                        'updated' => 0,
                        'skipped' => 0,
                        'failed' => 0,
                        'warnings' => 0,
                        'errors' => 0,
                    ],
                    'messages' => [
                        $this->message(
                            self::SEVERITY_SUCCESS,
                            get_string('presetexported', self::COMPONENT, $presetid),
                            ['preset' => $presetid]
                        ),
                    ],
                    'metadata' => [
                        'preset' => $presetid,
                    ],
                ]);
            }

            $result = $this->aggregate_results($children, get_string('presetexportcompleted', self::COMPONENT));
            $result = $this->with_metadata($result, ['exports' => $exports]);

            $this->finish_run($runid, $this->status_from_result($result), $result);
            return $result;
        } catch (\Throwable $exception) {
            $result = $this->exception_result($exception, get_string('presetexportfailed', self::COMPONENT));
            $this->log_step($runid, self::SEVERITY_ERROR, $exception->getMessage(), [
                'exception' => get_class($exception),
            ]);
            $this->finish_run($runid, self::STATUS_FAILED, $result);
            return $result;
        }
    }

    /**
     * Load one or more seed preset JSON files.
     *
     * @param array<int, string>|string|null $presetids Preset ids.
     * @return array<string, array<string, mixed>>
     */
    public function load_presets(array|string|null $presetids = []): array {
        $ids = $this->normalise_preset_ids($presetids);

        $presets = [];

        foreach ($ids as $presetid) {
            $filename = $this->get_preset_filename($presetid);
            $path = $this->presetpath . DIRECTORY_SEPARATOR . $filename;

            if (!is_readable($path)) {
                throw new moodle_exception('presetfilenotfound', self::COMPONENT, '', $path);
            }

            $presets[$presetid] = $this->read_preset_file($presetid, $path);
        }

        return $presets;
    }

    /**
     * Run one preset according to the requested action/mode.
     *
     * @param string $presetid Preset id.
     * @param array<string, mixed> $options Options.
     * @return validation_result
     */
    public function run_preset(string $presetid, array $options): validation_result {
        $presetid = $this->normalise_preset_id($presetid);
        $presetdata = $options['presetdata'] ?? null;

        if (!empty($options['presetpath'])) {
            $this->presetpath = $this->resolve_preset_path($options['presetpath']);
        }

        if (!is_array($presetdata)) {
            $loaded = $this->load_presets([$presetid]);
            $presetdata = $loaded[$presetid];
        }

        $schemaresult = $this->validate_preset_schema($presetid, $presetdata);

        if ($this->result_has_errors($schemaresult)) {
            return $schemaresult;
        }

        $handler = $this->get_handler($presetid);

        if ($handler === null) {
            return $this->new_result([
                'status' => self::STATUS_FAILED,
                'ok' => false,
                'haserrors' => true,
                'summary' => get_string('presethandlermissing', self::COMPONENT, $presetid),
                'messages' => [
                    $this->message(
                        self::SEVERITY_ERROR,
                        get_string('presethandlermissing', self::COMPONENT, $presetid),
                        [
                            'preset' => $presetid,
                            'targettype' => 'preset',
                            'targetkey' => $presetid,
                        ]
                    ),
                ],
            ]);
        }

        $action = (string)($options['action'] ?? self::ACTION_VALIDATE);
        $mode = (string)($options['mode'] ?? self::MODE_REPORT);
        $method = $this->get_handler_method($action, $mode);

        if (!method_exists($handler, $method)) {
            return $this->new_result([
                'status' => self::STATUS_FAILED,
                'ok' => false,
                'haserrors' => true,
                'summary' => get_string('presetmethodmissing', self::COMPONENT, [
                    'preset' => $presetid,
                    'method' => $method,
                ]),
                'messages' => [
                    $this->message(
                        self::SEVERITY_ERROR,
                        get_string('presetmethodmissing', self::COMPONENT, [
                            'preset' => $presetid,
                            'method' => $method,
                        ]),
                        [
                            'preset' => $presetid,
                            'targettype' => 'preset',
                            'targetkey' => $presetid,
                        ]
                    ),
                ],
            ]);
        }

        $items = $presetdata['items'] ?? [];

        $this->log_step(
            (int)($options['runid'] ?? 0),
            self::SEVERITY_INFO,
            get_string('presetrunning', self::COMPONENT, $presetid),
            [
                'preset' => $presetid,
                'action' => $action,
                'mode' => $mode,
                'itemcount' => count($items),
            ]
        );

        /** @var validation_result $result */
        $result = $handler->{$method}($items, [
            ...$options,
            'preset' => $presetid,
            'presetdata' => $presetdata,
            'schema' => self::PRESET_SCHEMA,
        ]);

        $this->log_result_messages((int)($options['runid'] ?? 0), $result, $presetid);

        return $result;
    }


/**
 * Create a seed run record.
 *
 * @param string $action Action.
 * @param string $mode Mode.
 * @param array<string, mixed> $options Options.
 * @return int Run id, or 0 when table is unavailable.
 */
public function create_run(string $action, string $mode, array $options): int {
    global $USER;

    $now = time();
    $presets = $options['presets'] ?? [];

    if (!is_array($presets)) {
        $presets = [$presets];
    }

    $presets = array_values(array_filter(array_map(
        static fn($preset): string => trim((string)$preset),
        $presets
    )));

    $primarypreset = '';

    if (count($presets) === 1) {
        $primarypreset = $presets[0];
    }

    $record = new stdClass();
    $record->action = $this->normalise_action($action);
    $record->mode = $this->normalise_mode($mode);
    $record->status = self::STATUS_RUNNING;
    $record->component = self::COMPONENT;
    $record->source = (string)($options['source'] ?? 'cli');

    // IMPORTANT:
    // tool_uckkseed_run.preset is char(100), so never store the full preset list here.
    // Full list belongs in the text field tool_uckkseed_run.presets.
    $record->preset = $primarypreset;
    $record->presets = $this->encode_json($presets);
    $record->components = $this->encode_json($options['components'] ?? []);

    $record->summary = (string)($options['summary'] ?? '');
    $record->details = null;

    $record->created = 0;
    $record->updated = 0;
    $record->skipped = 0;
    $record->failed = 0;
    $record->warnings = 0;
    $record->errors = 0;

    $record->userid = (int)($options['userid'] ?? $USER->id ?? 0);
    $record->createdby = $record->userid;
    $record->modifiedby = $record->userid;

    $record->timecreated = $now;
    $record->timemodified = $now;
    $record->timestarted = $now;
    $record->timefinished = 0;
    $record->duration = 0;

    $record->metadata = $this->encode_json([
        'source' => $record->source,
        'mode' => $record->mode,
        'action' => $record->action,
        'dryrun' => !empty($options['dryrun']) || !empty($options['dry_run']),
        'force' => !empty($options['force']),
        'presetpath' => (string)($options['presetpath'] ?? $this->presetpath),
    ]);

    return $this->safe_insert_record(self::TABLE_RUN, $record);
}



    /**
     * Log one seed step.
     *
     * @param int $runid Run id.
     * @param string $level Severity/level.
     * @param string $message Message.
     * @param array<string, mixed> $metadata Metadata.
     */
    public function log_step(int $runid, string $level, string $message, array $metadata = []): void {
        global $USER;

        if (!$this->audit_log_enabled()) {
            return;
        }

        $level = $this->normalise_severity($level);
        $now = time();

        $record = new stdClass();
        $record->runid = $runid;
        $record->action = (string)($metadata['action'] ?? '');
        $record->mode = (string)($metadata['mode'] ?? '');
        $record->status = $level;
        $record->level = $level;
        $record->component = (string)($metadata['component'] ?? self::COMPONENT);
        $record->preset = (string)($metadata['preset'] ?? '');
        $record->targettype = (string)($metadata['targettype'] ?? '');
        $record->targetkey = (string)($metadata['targetkey'] ?? '');
        $record->targetid = (int)($metadata['targetid'] ?? 0);
        $record->summary = $message;
        $record->message = $message;
        $record->details = null;
        $record->userid = (int)($metadata['userid'] ?? $USER->id ?? 0);
        $record->createdby = $record->userid;
        $record->modifiedby = $record->userid;
        $record->timecreated = $now;
        $record->timemodified = $now;
        $record->metadata = $this->encode_json($metadata);

        $this->safe_insert_record(self::TABLE_LOG, $record);
    }

    /**
     * Finish a seed run.
     *
     * @param int $runid Run id.
     * @param string $status Final status.
     * @param validation_result $result Result.
     */
    public function finish_run(int $runid, string $status, validation_result $result): void {
        if ($runid <= 0) {
            return;
        }

        $payload = $this->result_to_array($result);

        $record = new stdClass();
        $record->id = $runid;
        $record->status = $this->normalise_run_status($status);
        $record->summary = (string)($payload['summary'] ?? '');
        $record->details = $this->encode_json([
            'counts' => $payload['counts'] ?? [],
            'haserrors' => $payload['haserrors'] ?? false,
            'haswarnings' => $payload['haswarnings'] ?? false,
        ]);
        $record->timemodified = time();
        $record->metadata = $this->encode_json($payload['metadata'] ?? []);

        $this->safe_update_record(self::TABLE_RUN, $record);
    }

    /**
     * Validate preset schema.
     *
     * @param string $presetid Preset id.
     * @param array<string, mixed> $presetdata Preset data.
     * @return validation_result
     */
    private function validate_preset_schema(string $presetid, array $presetdata): validation_result {
        $messages = [];
        $errors = 0;

        if (($presetdata['schema'] ?? '') !== self::PRESET_SCHEMA) {
            $errors++;
            $messages[] = $this->message(
                self::SEVERITY_ERROR,
                get_string('invalidpresetschema', self::COMPONENT, $presetid),
                ['preset' => $presetid]
            );
        }

        if (($presetdata['component'] ?? '') !== self::COMPONENT) {
            $errors++;
            $messages[] = $this->message(
                self::SEVERITY_ERROR,
                get_string('invalidpresetcomponent', self::COMPONENT, $presetid),
                ['preset' => $presetid]
            );
        }

        if (($presetdata['preset'] ?? '') !== $presetid) {
            $errors++;
            $messages[] = $this->message(
                self::SEVERITY_ERROR,
                get_string('invalidpresetid', self::COMPONENT, $presetid),
                ['preset' => $presetid]
            );
        }

        if (!array_key_exists('items', $presetdata) || !is_array($presetdata['items'])) {
            $errors++;
            $messages[] = $this->message(
                self::SEVERITY_ERROR,
                get_string('presetitemsmissing', self::COMPONENT, $presetid),
                ['preset' => $presetid]
            );
        }

        if ($errors === 0) {
            $messages[] = $this->message(
                self::SEVERITY_SUCCESS,
                get_string('presetschemavalid', self::COMPONENT, $presetid),
                ['preset' => $presetid]
            );
        }

        return $this->new_result([
            'status' => $errors > 0 ? self::STATUS_FAILED : self::STATUS_COMPLETED,
            'ok' => $errors === 0,
            'haserrors' => $errors > 0,
            'haswarnings' => false,
            'summary' => $errors > 0
                ? get_string('presetschemainvalid', self::COMPONENT, $presetid)
                : get_string('presetschemavalid', self::COMPONENT, $presetid),
            'counts' => [
                'created' => 0,
                'updated' => 0,
                'skipped' => 0,
                'failed' => $errors,
                'warnings' => 0,
                'errors' => $errors,
            ],
            'messages' => $messages,
            'metadata' => [
                'preset' => $presetid,
            ],
        ]);
    }

    /**
     * Read and decode one preset file.
     *
     * @param string $presetid Preset id.
     * @param string $path Absolute path.
     * @return array<string, mixed>
     */
    private function read_preset_file(string $presetid, string $path): array {
        $raw = file_get_contents($path);

        if ($raw === false) {
            throw new moodle_exception('presetfilenotreadable', self::COMPONENT, '', basename($path));
        }

        $decoded = json_decode($raw, true);

        if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
            throw new moodle_exception('presetjsoninvalid', self::COMPONENT, '', [
                'preset' => $presetid,
                'error' => json_last_error_msg(),
            ]);
        }

        $decoded['filename'] = basename($path);
        $decoded['filepath'] = $path;

        return $decoded;
    }

    /**
     * Get handler method for an action/mode pair.
     *
     * @param string $action Action.
     * @param string $mode Mode.
     * @return string
     */
    private function get_handler_method(string $action, string $mode): string {
        $action = $this->normalise_action($action);
        $mode = $this->normalise_mode($mode);

        if ($action === self::ACTION_RESET) {
            return 'reset';
        }

        if ($action === self::ACTION_VALIDATE) {
            return 'validate';
        }

        if ($mode === self::MODE_APPLY && $action === self::ACTION_SEED) {
            return 'apply';
        }

        return 'validate';
    }

    /**
     * Instantiate the correct handler for a preset.
     *
     * @param string $presetid Preset id.
     * @return object|null
     */
    private function get_handler(string $presetid): ?object {
        $class = $this->get_handler_class($presetid);

        if ($class === null || !class_exists($class)) {
            return null;
        }

        return new $class($this);
    }

    /**
     * Map preset id to handler class.
     *
     * @param string $presetid Preset id.
     * @return class-string|null
     */
    private function get_handler_class(string $presetid): ?string {
        return match ($presetid) {
            self::PRESET_CATEGORIES => category_seed::class,
            self::PRESET_PROGRAMS => program_seed::class,
            self::PRESET_PATHWAYS => pathway_seed::class,
            self::PRESET_COURSES => course_seed::class,
            self::PRESET_COHORTS => cohort_seed::class,
            self::PRESET_ROLES => role_seed::class,
            self::PRESET_CAPABILITIES => capability_seed::class,
            self::PRESET_COMPETENCIES => competency_seed::class,
            self::PRESET_BADGES => badge_seed::class,
            self::PRESET_REPORTS => report_seed::class,
            self::PRESET_COURSE_TEMPLATES => course_template_seed::class,
            self::PRESET_CHALLENGE_TEMPLATES => challenge_template_seed::class,
            self::PRESET_ASSEMBLY_TEMPLATES => assembly_template_seed::class,
            self::PRESET_ARCHIVE_TEMPLATES => archive_template_seed::class,
            default => null,
        };
    }

    /**
     * Get canonical preset filename.
     *
     * @param string $presetid Preset id.
     * @return string
     */
    private function get_preset_filename(string $presetid): string {
        $presetid = $this->normalise_preset_id($presetid);

        return $presetid . '.json';
    }

    /**
     * Normalise options.
     *
     * @param array<string, mixed> $options Raw options.
     * @param string $defaultaction Default action.
     * @return array<string, mixed>
     */
    private function normalise_options(array $options, string $defaultaction): array {
        global $USER;

        $action = $this->normalise_action((string)($options['action'] ?? $defaultaction));
        $mode = $this->normalise_mode((string)($options['mode'] ?? $this->get_default_mode()));

        if (!empty($options['dryrun'])) {
            $mode = self::MODE_DRY_RUN;
        }

        if (!empty($options['report'])) {
            $mode = self::MODE_REPORT;
        }

        if (!empty($options['rollbackplan'])) {
            $mode = self::MODE_ROLLBACK_PLAN;
        }

        $presets = $this->normalise_preset_ids($options['presets'] ?? []);
        $presetpath = $this->resolve_preset_path($options['presetpath'] ?? null);
        $this->presetpath = $presetpath;

        return [
            ...$options,
            'action' => $action,
            'mode' => $mode,
            'presets' => $presets,
            'presetpath' => $presetpath,
            'components' => $this->normalise_list($options['components'] ?? []),
            'dryrun' => $mode === self::MODE_DRY_RUN,
            'report' => $mode === self::MODE_REPORT,
            'rollbackplan' => $mode === self::MODE_ROLLBACK_PLAN,
            'force' => !empty($options['force']),
            'confirm' => !empty($options['confirm']),
            'source' => (string)($options['source'] ?? 'web'),
            'userid' => (int)($options['userid'] ?? $USER->id ?? 0),
        ];
    }

    /**
     * Normalise action.
     *
     * @param string $action Action.
     * @return string
     */
    private function normalise_action(string $action): string {
        $action = clean_param($action, PARAM_ALPHANUMEXT);

        $allowed = [
            self::ACTION_SEED,
            self::ACTION_RESET,
            self::ACTION_VALIDATE,
            self::ACTION_EXPORT_PRESET,
        ];

        return in_array($action, $allowed, true) ? $action : self::ACTION_VALIDATE;
    }

    /**
     * Normalise mode.
     *
     * @param string $mode Mode.
     * @return string
     */
    private function normalise_mode(string $mode): string {
        $mode = clean_param($mode, PARAM_ALPHANUMEXT);

        $allowed = [
            self::MODE_DRY_RUN,
            self::MODE_APPLY,
            self::MODE_REPORT,
            self::MODE_ROLLBACK_PLAN,
        ];

        return in_array($mode, $allowed, true) ? $mode : self::MODE_DRY_RUN;
    }

    /**
     * Normalise run status.
     *
     * @param string $status Status.
     * @return string
     */
    private function normalise_run_status(string $status): string {
        $status = clean_param($status, PARAM_ALPHANUMEXT);

        $allowed = [
            self::STATUS_PENDING,
            self::STATUS_RUNNING,
            self::STATUS_COMPLETED,
            self::STATUS_FAILED,
            self::STATUS_CANCELLED,
            self::STATUS_SKIPPED,
            self::STATUS_WARNING,
        ];

        return in_array($status, $allowed, true) ? $status : self::STATUS_FAILED;
    }

    /**
     * Normalise severity.
     *
     * @param string $severity Severity.
     * @return string
     */
    private function normalise_severity(string $severity): string {
        $severity = clean_param($severity, PARAM_ALPHANUMEXT);

        $allowed = [
            self::SEVERITY_INFO,
            self::SEVERITY_SUCCESS,
            self::SEVERITY_WARNING,
            self::SEVERITY_ERROR,
            self::SEVERITY_BLOCKER,
        ];

        return in_array($severity, $allowed, true) ? $severity : self::SEVERITY_INFO;
    }

    /**
     * Normalise preset ids.
     *
     * @param array<int, string>|string|null $presetids Raw preset ids.
     * @return string[]
     */
    private function normalise_preset_ids(array|string|null $presetids): array {
        if ($presetids === null || $presetids === '' || $presetids === []) {
            return $this->get_default_presets();
        }

        $items = $this->normalise_list($presetids);
        $allowed = $this->get_allowed_presets();
        $normalised = [];

        foreach ($items as $item) {
            $presetid = $this->normalise_preset_id($item);

            if (in_array($presetid, $allowed, true)) {
                $normalised[] = $presetid;
            }
        }

        return array_values(array_unique($normalised));
    }

    /**
     * Normalise one preset id.
     *
     * @param string $presetid Preset id.
     * @return string
     */
    private function normalise_preset_id(string $presetid): string {
        return clean_param(trim($presetid), PARAM_ALPHANUMEXT);
    }

    /**
     * Normalise a string/list option.
     *
     * @param array<int, string>|string|mixed $value Raw value.
     * @return string[]
     */
    private function normalise_list(mixed $value): array {
        if (is_string($value)) {
            $value = trim($value);

            if ($value === '') {
                return [];
            }

            $value = preg_split('/[,;\s]+/', $value) ?: [];
        }

        if (!is_array($value)) {
            return [];
        }

        $items = [];

        foreach ($value as $item) {
            $item = trim((string)$item);

            if ($item !== '') {
                $items[] = $item;
            }
        }

        return array_values(array_unique($items));
    }

    /**
     * Get allowed preset ids.
     *
     * @return string[]
     */
    private function get_allowed_presets(): array {
        return [
            self::PRESET_CATEGORIES,
            self::PRESET_PROGRAMS,
            self::PRESET_PATHWAYS,
            self::PRESET_COHORTS,
            self::PRESET_ROLES,
            self::PRESET_CAPABILITIES,
            self::PRESET_COMPETENCIES,
            self::PRESET_BADGES,
            self::PRESET_COURSE_TEMPLATES,
            self::PRESET_CHALLENGE_TEMPLATES,
            self::PRESET_ASSEMBLY_TEMPLATES,
            self::PRESET_ARCHIVE_TEMPLATES,
            self::PRESET_COURSES,
            self::PRESET_REPORTS,
        ];
    }

    /**
     * Get default presets in dependency-safe order.
     *
     * @return string[]
     */
    private function get_default_presets(): array {
        return [
            self::PRESET_CATEGORIES,
            self::PRESET_PROGRAMS,
            self::PRESET_PATHWAYS,
            self::PRESET_COHORTS,
            self::PRESET_ROLES,
            self::PRESET_CAPABILITIES,
            self::PRESET_COMPETENCIES,
            self::PRESET_BADGES,
            self::PRESET_COURSE_TEMPLATES,
            self::PRESET_CHALLENGE_TEMPLATES,
            self::PRESET_ASSEMBLY_TEMPLATES,
            self::PRESET_ARCHIVE_TEMPLATES,
            self::PRESET_COURSES,
            self::PRESET_REPORTS,
        ];
    }

    /**
     * Get default mode from config.
     *
     * @return string
     */
    private function get_default_mode(): string {
        $mode = (string)get_config(self::COMPONENT, 'defaultmode');

        return $this->normalise_mode($mode !== '' ? $mode : self::MODE_DRY_RUN);
    }

    /**
     * Whether the tool is enabled.
     *
     * @param array<string, mixed> $options Options.
     * @return bool
     */
    private function tool_is_enabled(array $options): bool {
        if (!empty($options['force'])) {
            return true;
        }

        return (bool)get_config(self::COMPONENT, 'enabletool');
    }

    /**
     * Whether reset is allowed.
     *
     * @param array<string, mixed> $options Options.
     * @return bool
     */
    private function reset_is_allowed(array $options): bool {
        if (!empty($options['force'])) {
            return true;
        }

        return (bool)get_config(self::COMPONENT, 'allowreset');
    }

    /**
     * Whether reset confirmation is present.
     *
     * @param array<string, mixed> $options Options.
     * @return bool
     */
    private function has_reset_confirmation(array $options): bool {
        if ((string)($options['mode'] ?? '') !== self::MODE_APPLY) {
            return true;
        }

        if (!empty($options['force'])) {
            return true;
        }

        return !empty($options['confirm']);
    }

    /**
     * Whether audit logging is enabled.
     *
     * @return bool
     */
    private function audit_log_enabled(): bool {
        $config = get_config(self::COMPONENT, 'auditlogenabled');

        return $config === false || (bool)$config;
    }

    /**
     * Aggregate child results.
     *
     * @param validation_result[] $children Child results.
     * @param string $summary Summary.
     * @return validation_result
     */
    private function aggregate_results(array $children, string $summary): validation_result {
        $counts = [
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'failed' => 0,
            'warnings' => 0,
            'errors' => 0,
        ];

        $messages = [];
        $metadata = [
            'children' => [],
        ];

        foreach ($children as $child) {
            $payload = $this->result_to_array($child);
            $childcounts = $payload['counts'] ?? [];

            foreach ($counts as $key => $value) {
                $counts[$key] += (int)($childcounts[$key] ?? 0);
            }

            foreach (($payload['messages'] ?? []) as $message) {
                $messages[] = $message;
            }

            $metadata['children'][] = [
                'status' => $payload['status'] ?? self::STATUS_COMPLETED,
                'summary' => $payload['summary'] ?? '',
                'metadata' => $payload['metadata'] ?? [],
            ];
        }

        $haserrors = $counts['errors'] > 0 || $counts['failed'] > 0;
        $haswarnings = $counts['warnings'] > 0;
        $status = $haserrors ? self::STATUS_FAILED : ($haswarnings ? self::STATUS_WARNING : self::STATUS_COMPLETED);

        return $this->new_result([
            'status' => $status,
            'ok' => !$haserrors,
            'haserrors' => $haserrors,
            'haswarnings' => $haswarnings,
            'summary' => $summary,
            'counts' => $counts,
            'messages' => $messages,
            'metadata' => $metadata,
        ]);
    }

    /**
     * Create a result from an exception.
     *
     * @param \Throwable $exception Exception.
     * @param string $summary Summary.
     * @return validation_result
     */
    private function exception_result(\Throwable $exception, string $summary): validation_result {
        return $this->new_result([
            'status' => self::STATUS_FAILED,
            'ok' => false,
            'haserrors' => true,
            'haswarnings' => false,
            'summary' => $summary,
            'counts' => [
                'created' => 0,
                'updated' => 0,
                'skipped' => 0,
                'failed' => 1,
                'warnings' => 0,
                'errors' => 1,
            ],
            'messages' => [
                $this->message(self::SEVERITY_ERROR, $exception->getMessage(), [
                    'exception' => get_class($exception),
                    'code' => $exception->getCode(),
                ]),
            ],
            'metadata' => [
                'exception' => get_class($exception),
            ],
        ]);
    }

    /**
     * Create a message row.
     *
     * @param string $severity Severity.
     * @param string $message Message text.
     * @param array<string, mixed> $metadata Metadata.
     * @return array<string, mixed>
     */
    private function message(string $severity, string $message, array $metadata = []): array {
        return [
            'severity' => $this->normalise_severity($severity),
            'component' => (string)($metadata['component'] ?? self::COMPONENT),
            'preset' => (string)($metadata['preset'] ?? ''),
            'targettype' => (string)($metadata['targettype'] ?? ''),
            'targetkey' => (string)($metadata['targetkey'] ?? ''),
            'message' => $message,
            'metadata' => $metadata,
        ];
    }

    /**
     * Create validation result.
     *
     * @param array<string, mixed> $payload Payload.
     * @return validation_result
     */
    private function new_result(array $payload): validation_result {
        $payload += [
            'status' => self::STATUS_COMPLETED,
            'ok' => true,
            'haserrors' => false,
            'haswarnings' => false,
            'summary' => '',
            'counts' => [
                'created' => 0,
                'updated' => 0,
                'skipped' => 0,
                'failed' => 0,
                'warnings' => 0,
                'errors' => 0,
            ],
            'messages' => [],
            'created' => [],
            'updated' => [],
            'skipped' => [],
            'failed' => [],
            'metadata' => [],
        ];

        return validation_result::from_data($payload);
    }

    /**
     * Convert result to array.
     *
     * @param validation_result $result Result object.
     * @return array<string, mixed>
     */
    private function result_to_array(validation_result $result): array {
        if (method_exists($result, 'to_array')) {
            return $result->to_array();
        }

        return (array)$result;
    }

    /**
     * Add metadata to a result.
     *
     * @param validation_result $result Result.
     * @param array<string, mixed> $metadata Metadata to merge.
     * @return validation_result
     */
    private function with_metadata(validation_result $result, array $metadata): validation_result {
        $payload = $this->result_to_array($result);
        $payload['metadata'] = array_merge($payload['metadata'] ?? [], $metadata);

        return $this->new_result($payload);
    }

    /**
     * Check whether a result has errors.
     *
     * @param validation_result $result Result.
     * @return bool
     */
    private function result_has_errors(validation_result $result): bool {
        $payload = $this->result_to_array($result);

        return !empty($payload['haserrors'])
            || !empty($payload['counts']['errors'])
            || !empty($payload['counts']['failed']);
    }

    /**
     * Determine final run status from result.
     *
     * @param validation_result $result Result.
     * @return string
     */
    private function status_from_result(validation_result $result): string {
        $payload = $this->result_to_array($result);

        if (!empty($payload['haserrors'])) {
            return self::STATUS_FAILED;
        }

        if (!empty($payload['haswarnings'])) {
            return self::STATUS_WARNING;
        }

        return self::STATUS_COMPLETED;
    }

    /**
     * Log result messages.
     *
     * @param int $runid Run id.
     * @param validation_result $result Result.
     * @param string $presetid Preset id.
     */
    private function log_result_messages(int $runid, validation_result $result, string $presetid): void {
        $payload = $this->result_to_array($result);

        foreach (($payload['messages'] ?? []) as $message) {
            $row = (array)$message;

            $this->log_step(
                $runid,
                (string)($row['severity'] ?? self::SEVERITY_INFO),
                (string)($row['message'] ?? ''),
                [
                    ...((array)($row['metadata'] ?? [])),
                    'preset' => (string)($row['preset'] ?? $presetid),
                    'component' => (string)($row['component'] ?? self::COMPONENT),
                    'targettype' => (string)($row['targettype'] ?? ''),
                    'targetkey' => (string)($row['targetkey'] ?? ''),
                ]
            );
        }
    }

    /**
     * Insert record with unknown-field protection.
     *
     * @param string $table Table name.
     * @param stdClass $record Record.
     * @return int Inserted id or 0.
     */
    private function safe_insert_record(string $table, stdClass $record): int {
        global $DB;

        if (!$DB->get_manager()->table_exists($table)) {
            return 0;
        }

        $filtered = $this->filter_record_fields($table, $record);

        if (empty((array)$filtered)) {
            return 0;
        }

        return (int)$DB->insert_record($table, $filtered);
    }

    /**
     * Update record with unknown-field protection.
     *
     * @param string $table Table name.
     * @param stdClass $record Record.
     */
    private function safe_update_record(string $table, stdClass $record): void {
        global $DB;

        if (empty($record->id) || !$DB->get_manager()->table_exists($table)) {
            return;
        }

        $filtered = $this->filter_record_fields($table, $record);

        if (!empty($filtered->id)) {
            $DB->update_record($table, $filtered);
        }
    }

    /**
     * Filter object fields to columns that exist in the table.
     *
     * @param string $table Table name.
     * @param stdClass $record Record.
     * @return stdClass
     */
    private function filter_record_fields(string $table, stdClass $record): stdClass {
        global $DB;

        $columns = $DB->get_columns($table);
        $filtered = new stdClass();

        foreach ((array)$record as $key => $value) {
            if (array_key_exists($key, $columns)) {
                $filtered->{$key} = $value;
            }
        }

        return $filtered;
    }

    /**
     * Normalise export payload.
     *
     * @param string $presetid Preset id.
     * @param mixed $export Export data.
     * @return array<string, mixed>
     */
    private function normalise_export_payload(string $presetid, mixed $export): array {
        if (is_array($export)) {
            return [
                'schema' => self::PRESET_SCHEMA,
                'component' => self::COMPONENT,
                'preset' => $presetid,
                'version' => self::PRESET_VERSION,
                'items' => $export['items'] ?? $export,
                'metadata' => $export['metadata'] ?? [],
            ];
        }

        return [
            'schema' => self::PRESET_SCHEMA,
            'component' => self::COMPONENT,
            'preset' => $presetid,
            'version' => self::PRESET_VERSION,
            'items' => [],
            'metadata' => [],
        ];
    }

    /**
     * Encode JSON metadata.
     *
     * @param mixed $value Value.
     * @return string|null
     */
    private function encode_json(mixed $value): ?string {
        if ($value === null || $value === '') {
            return null;
        }

        $encoded = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return $encoded === false ? null : $encoded;
    }

    /**
     * Convert list to comma string.
     *
     * @param mixed $value Value.
     * @return string
     */
    private function implode_list(mixed $value): string {
        return implode(',', $this->normalise_list($value));
    }

    /**
     * Resolve the academic registry JSON directory.
     *
     * The path may be absolute, or relative to Moodle dirroot.
     *
     * @param string|null $presetpath Optional path override.
     * @return string Absolute directory path without trailing separator.
     */
    private function resolve_preset_path(?string $presetpath = null): string {
        global $CFG;

        $configured = trim((string)($presetpath ?? ''));

        if ($configured === '') {
            $configured = trim((string)get_config(self::COMPONENT, 'presetpath'));
        }

        if ($configured === '') {
            $configured = self::DEFAULT_PRESET_PATH;
        }

        $configured = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $configured);
        $configured = rtrim($configured, DIRECTORY_SEPARATOR);

        if ($this->is_absolute_path($configured)) {
            return $configured;
        }

        return rtrim($CFG->dirroot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $configured;
    }

    /**
     * Is path absolute.
     *
     * @param string $path Path.
     * @return bool
     */
    private function is_absolute_path(string $path): bool {
        return str_starts_with($path, DIRECTORY_SEPARATOR)
            || preg_match('/^[A-Za-z]:[\/\\\\]/', $path) === 1;
    }
}