<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

/**
 * Competency preset seeder for the UCKK seed admin tool.
 *
 * @package    tool_uckkseed
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace tool_uckkseed\local;

defined('MOODLE_INTERNAL') || die();

use context_system;
use core_competency\api;
use core_competency\competency;
use core_competency\competency_framework;
use stdClass;

/**
 * Seeds UCKK competency frameworks and competencies.
 *
 * Expected preset item shape:
 *
 * [
 *     'key' => 'COMP_READ_GAME',
 *     'idnumber' => 'UCKK-COMP-001',
 *     'shortname' => 'Read the game',
 *     'description' => '...',
 *     'framework' => 'uckk_competency_framework',
 *     'parent' => '',
 *     'scale' => '',
 *     'sortorder' => 10,
 *     'metadata' => []
 * ]
 *
 * This class is intentionally idempotent:
 * - frameworks are matched by idnumber;
 * - competencies are matched by idnumber inside their framework;
 * - dry-run mode reports planned changes without writing data.
 */
final class competency_seed {
    /** Component name. */
    private const COMPONENT = 'tool_uckkseed';

    /** Preset id. */
    private const PRESET = 'competencies';

    /** Target type. */
    private const TARGET_TYPE = 'competency';

    /** Default framework key/idnumber. */
    private const DEFAULT_FRAMEWORK = 'uckk_competency_framework';

    /** Default framework shortname. */
    private const DEFAULT_FRAMEWORK_SHORTNAME = 'UCKK competency framework';

    /** Execution mode: dry run. */
    private const MODE_DRY_RUN = 'dry_run';

    /** Execution mode: apply. */
    private const MODE_APPLY = 'apply';

    /** Execution mode: report. */
    private const MODE_REPORT = 'report';

    /** Severity: info. */
    private const SEVERITY_INFO = 'info';

    /** Severity: success. */
    private const SEVERITY_SUCCESS = 'success';

    /** Severity: warning. */
    private const SEVERITY_WARNING = 'warning';

    /** Severity: error. */
    private const SEVERITY_ERROR = 'error';

    /** Severity: blocker. */
    private const SEVERITY_BLOCKER = 'blocker';

    /**
     * Validate competency preset items.
     *
     * @param array<int, array<string, mixed>|stdClass> $items Preset items.
     * @param array<string, mixed> $options Runtime options.
     * @return validation_result
     */
    public function validate(array $items, array $options = []): validation_result {
        $result = $this->new_result('validate');

        if (!$this->competency_api_available()) {
            $this->add_message(
                $result,
                self::SEVERITY_BLOCKER,
                'core_competency API is not available.',
                '',
                []
            );

            return $this->finalise_result($result);
        }

        $seen = [];

        foreach ($items as $index => $item) {
            $item = $this->normalise_item($item);
            $targetkey = $item['key'] !== '' ? $item['key'] : 'index_' . $index;

            if ($item['key'] === '') {
                $this->add_message(
                    $result,
                    self::SEVERITY_ERROR,
                    get_string('validation:missingkey', self::COMPONENT),
                    $targetkey,
                    ['index' => $index]
                );
            }

            if ($item['idnumber'] === '') {
                $this->add_message(
                    $result,
                    self::SEVERITY_ERROR,
                    'Competency item is missing idnumber.',
                    $targetkey,
                    ['index' => $index]
                );
            }

            if ($item['shortname'] === '') {
                $this->add_message(
                    $result,
                    self::SEVERITY_ERROR,
                    'Competency item is missing shortname.',
                    $targetkey,
                    ['index' => $index]
                );
            }

            if ($item['framework'] === '') {
                $this->add_message(
                    $result,
                    self::SEVERITY_ERROR,
                    'Competency item is missing framework.',
                    $targetkey,
                    ['index' => $index]
                );
            }

            if ($item['idnumber'] !== '') {
                $duplicatekey = $item['framework'] . ':' . $item['idnumber'];

                if (isset($seen[$duplicatekey])) {
                    $this->add_message(
                        $result,
                        self::SEVERITY_ERROR,
                        get_string('validation:duplicatekey', self::COMPONENT, $item['idnumber']),
                        $targetkey,
                        ['idnumber' => $item['idnumber'], 'framework' => $item['framework']]
                    );
                }

                $seen[$duplicatekey] = true;
            }

            if (!$this->is_uckk_competency_idnumber($item['idnumber'])) {
                $this->add_message(
                    $result,
                    self::SEVERITY_WARNING,
                    'Competency idnumber is not in the canonical UCKK-COMP-### form.',
                    $targetkey,
                    ['idnumber' => $item['idnumber']]
                );
            }
        }

        if (empty($items)) {
            $this->add_message(
                $result,
                self::SEVERITY_WARNING,
                'No competency preset items were provided.',
                '',
                []
            );
        }

        return $this->finalise_result($result);
    }

    /**
     * Apply competency seed items.
     *
     * @param array<int, array<string, mixed>|stdClass> $items Preset items.
     * @param array<string, mixed> $options Runtime options.
     * @return validation_result
     */
    public function apply(array $items, array $options = []): validation_result {
        $mode = $this->normalise_mode((string)($options['mode'] ?? self::MODE_APPLY));
        $dryrun = $mode === self::MODE_DRY_RUN || !empty($options['dryrun']);
        $result = $this->new_result($dryrun ? self::MODE_DRY_RUN : self::MODE_APPLY);

        $validation = $this->validate($items, $options);

        $this->merge_result($result, $validation);

        if ($this->result_has_blocking_errors($validation)) {
            return $this->finalise_result($result);
        }

        $frameworkcache = [];

        foreach ($items as $item) {
            $item = $this->normalise_item($item);

            if ($item['idnumber'] === '' || $item['shortname'] === '' || $item['framework'] === '') {
                $this->increment_count($result, 'failed');
                continue;
            }

            $frameworkidnumber = $item['framework'];

            if (!isset($frameworkcache[$frameworkidnumber])) {
                $frameworkcache[$frameworkidnumber] = $this->ensure_framework($frameworkidnumber, $options, $dryrun, $result);
            }

            $frameworkid = $frameworkcache[$frameworkidnumber];

            if ($dryrun) {
                $exists = $this->find_competency($item['idnumber'], $frameworkid);

                $this->add_message(
                    $result,
                    self::SEVERITY_INFO,
                    $exists
                        ? 'Dry run: competency would be updated.'
                        : 'Dry run: competency would be created.',
                    $item['idnumber'],
                    [
                        'framework' => $frameworkidnumber,
                        'shortname' => $item['shortname'],
                    ]
                );

                $this->increment_count($result, $exists ? 'updated' : 'created');
                continue;
            }

            try {
                $existing = $this->find_competency($item['idnumber'], $frameworkid);

                if ($existing) {
                    $this->update_competency($existing, $item, $frameworkid);
                    $this->increment_count($result, 'updated');

                    $this->add_message(
                        $result,
                        self::SEVERITY_SUCCESS,
                        get_string('seed:competencyupdated', self::COMPONENT, $item['idnumber']),
                        $item['idnumber'],
                        ['framework' => $frameworkidnumber]
                    );
                } else {
                    $this->create_competency($item, $frameworkid);
                    $this->increment_count($result, 'created');

                    $this->add_message(
                        $result,
                        self::SEVERITY_SUCCESS,
                        get_string('seed:competencycreated', self::COMPONENT, $item['idnumber']),
                        $item['idnumber'],
                        ['framework' => $frameworkidnumber]
                    );
                }
            } catch (\Throwable $exception) {
                $this->increment_count($result, 'failed');

                $this->add_message(
                    $result,
                    self::SEVERITY_ERROR,
                    $exception->getMessage(),
                    $item['idnumber'],
                    [
                        'framework' => $frameworkidnumber,
                        'exception' => get_class($exception),
                    ]
                );
            }
        }

        return $this->finalise_result($result);
    }

    /**
     * Reset seeded competencies.
     *
     * Reset is conservative: it only deletes competencies whose idnumbers are
     * explicitly present in the preset and only when force is enabled. Without
     * force, this returns a rollback plan.
     *
     * @param array<int, array<string, mixed>|stdClass> $items Preset items.
     * @param array<string, mixed> $options Runtime options.
     * @return validation_result
     */
    public function reset(array $items, array $options = []): validation_result {
        $mode = $this->normalise_mode((string)($options['mode'] ?? 'rollback_plan'));
        $dryrun = $mode === self::MODE_DRY_RUN || $mode === 'rollback_plan' || empty($options['force']);
        $result = $this->new_result('reset');

        if (!$this->competency_api_available()) {
            $this->add_message(
                $result,
                self::SEVERITY_BLOCKER,
                'core_competency API is not available.',
                '',
                []
            );

            return $this->finalise_result($result);
        }

        foreach ($items as $item) {
            $item = $this->normalise_item($item);

            if ($item['idnumber'] === '' || $item['framework'] === '') {
                $this->increment_count($result, 'skipped');
                continue;
            }

            $framework = $this->find_framework($item['framework']);
            $frameworkid = $framework ? (int)$framework->get('id') : 0;
            $competency = $frameworkid > 0 ? $this->find_competency($item['idnumber'], $frameworkid) : null;

            if (!$competency) {
                $this->increment_count($result, 'skipped');

                $this->add_message(
                    $result,
                    self::SEVERITY_INFO,
                    'Competency does not exist; reset skipped.',
                    $item['idnumber'],
                    ['framework' => $item['framework']]
                );

                continue;
            }

            if ($dryrun) {
                $this->increment_count($result, 'skipped');

                $this->add_message(
                    $result,
                    self::SEVERITY_WARNING,
                    'Rollback plan: competency would be deleted only with explicit force.',
                    $item['idnumber'],
                    ['framework' => $item['framework']]
                );

                continue;
            }

            try {
                api::delete_competency((int)$competency->get('id'));
                $this->increment_count($result, 'updated');

                $this->add_message(
                    $result,
                    self::SEVERITY_SUCCESS,
                    'Competency deleted.',
                    $item['idnumber'],
                    ['framework' => $item['framework']]
                );
            } catch (\Throwable $exception) {
                $this->increment_count($result, 'failed');

                $this->add_message(
                    $result,
                    self::SEVERITY_ERROR,
                    $exception->getMessage(),
                    $item['idnumber'],
                    ['framework' => $item['framework']]
                );
            }
        }

        return $this->finalise_result($result);
    }

    /**
     * Export existing UCKK competencies into preset-compatible rows.
     *
     * @param array<string, mixed> $options Runtime options.
     * @return array<string, mixed>
     */
    public function export(array $options = []): array {
        $frameworkidnumber = clean_param(
            (string)($options['framework'] ?? self::DEFAULT_FRAMEWORK),
            PARAM_ALPHANUMEXT
        );

        $items = [];

        if ($this->competency_api_available()) {
            $framework = $this->find_framework($frameworkidnumber);

            if ($framework) {
                $competencies = competency::get_records([
                    'competencyframeworkid' => (int)$framework->get('id'),
                ], 'sortorder ASC, idnumber ASC');

                foreach ($competencies as $competency) {
                    $items[] = [
                        'key' => $this->make_key((string)$competency->get('idnumber')),
                        'idnumber' => (string)$competency->get('idnumber'),
                        'shortname' => (string)$competency->get('shortname'),
                        'description' => (string)$competency->get('description'),
                        'framework' => $frameworkidnumber,
                        'parent' => '',
                        'scale' => '',
                        'sortorder' => (int)$competency->get('sortorder'),
                        'metadata' => [
                            'exported_from' => 'core_competency',
                        ],
                    ];
                }
            }
        }

        return [
            'schema' => 'uckkseed.preset.v1',
            'component' => self::COMPONENT,
            'preset' => self::PRESET,
            'version' => 2026051200,
            'items' => $items,
        ];
    }

    /**
     * Ensure a competency framework exists.
     *
     * @param string $idnumber Framework idnumber.
     * @param array<string, mixed> $options Runtime options.
     * @param bool $dryrun Whether this is a dry run.
     * @param validation_result $result Result object.
     * @return int Framework id, or 0 during dry-run creation.
     */
    private function ensure_framework(
        string $idnumber,
        array $options,
        bool $dryrun,
        validation_result $result
    ): int {
        $framework = $this->find_framework($idnumber);

        if ($framework) {
            return (int)$framework->get('id');
        }

        if ($dryrun) {
            $this->add_message(
                $result,
                self::SEVERITY_INFO,
                'Dry run: competency framework would be created.',
                $idnumber,
                ['framework' => $idnumber]
            );

            return 0;
        }

        $context = context_system::instance();

        $record = new stdClass();
        $record->shortname = (string)($options['frameworkshortname'] ?? self::DEFAULT_FRAMEWORK_SHORTNAME);
        $record->idnumber = $idnumber;
        $record->description = (string)($options['frameworkdescription'] ?? 'UCKK canonical competency framework.');
        $record->descriptionformat = FORMAT_HTML;
        $record->contextid = $context->id;
        $record->visible = 1;
        $record->scaleid = 0;
        $record->scaleconfiguration = '';

        $created = api::create_framework($record);

        $this->add_message(
            $result,
            self::SEVERITY_SUCCESS,
            'Competency framework created.',
            $idnumber,
            ['framework' => $idnumber]
        );

        return (int)$created->get('id');
    }

    /**
     * Create one competency.
     *
     * @param array<string, mixed> $item Normalised item.
     * @param int $frameworkid Framework id.
     */
    private function create_competency(array $item, int $frameworkid): void {
        $record = $this->build_competency_record($item, $frameworkid);
        api::create_competency($record);
    }

    /**
     * Update one competency.
     *
     * @param competency $existing Existing competency object.
     * @param array<string, mixed> $item Normalised item.
     * @param int $frameworkid Framework id.
     */
    private function update_competency(competency $existing, array $item, int $frameworkid): void {
        $record = $this->build_competency_record($item, $frameworkid);
        $record->id = (int)$existing->get('id');

        api::update_competency($record);
    }

    /**
     * Build Moodle competency record.
     *
     * @param array<string, mixed> $item Normalised item.
     * @param int $frameworkid Framework id.
     * @return stdClass
     */
    private function build_competency_record(array $item, int $frameworkid): stdClass {
        $record = new stdClass();
        $record->competencyframeworkid = $frameworkid;
        $record->parentid = $this->resolve_parentid((string)$item['parent'], $frameworkid);
        $record->shortname = $item['shortname'];
        $record->idnumber = $item['idnumber'];
        $record->description = $item['description'];
        $record->descriptionformat = FORMAT_HTML;
        $record->sortorder = $item['sortorder'];
        $record->scaleid = 0;
        $record->scaleconfiguration = '';
        $record->ruletype = null;
        $record->ruleoutcome = 0;
        $record->ruleconfig = null;

        return $record;
    }

    /**
     * Find a framework by idnumber.
     *
     * @param string $idnumber Framework idnumber.
     * @return competency_framework|null
     */
    private function find_framework(string $idnumber): ?competency_framework {
        if ($idnumber === '' || !$this->competency_api_available()) {
            return null;
        }

        $records = competency_framework::get_records(['idnumber' => $idnumber], '', 0, 1);

        if (empty($records)) {
            return null;
        }

        return reset($records) ?: null;
    }

    /**
     * Find a competency by idnumber and framework.
     *
     * @param string $idnumber Competency idnumber.
     * @param int $frameworkid Framework id.
     * @return competency|null
     */
    private function find_competency(string $idnumber, int $frameworkid): ?competency {
        if ($idnumber === '' || $frameworkid <= 0 || !$this->competency_api_available()) {
            return null;
        }

        $records = competency::get_records([
            'idnumber' => $idnumber,
            'competencyframeworkid' => $frameworkid,
        ], '', 0, 1);

        if (empty($records)) {
            return null;
        }

        return reset($records) ?: null;
    }

    /**
     * Resolve parent competency id.
     *
     * @param string $parent Parent idnumber/key.
     * @param int $frameworkid Framework id.
     * @return int
     */
    private function resolve_parentid(string $parent, int $frameworkid): int {
        if ($parent === '' || $frameworkid <= 0) {
            return 0;
        }

        $parentcompetency = $this->find_competency($parent, $frameworkid);

        return $parentcompetency ? (int)$parentcompetency->get('id') : 0;
    }

    /**
     * Normalise one preset item.
     *
     * @param array<string, mixed>|stdClass $item Raw item.
     * @return array<string, mixed>
     */
    private function normalise_item(array|stdClass $item): array {
        $item = (array)$item;

        $idnumber = trim((string)($item['idnumber'] ?? $item['key'] ?? ''));
        $framework = trim((string)($item['framework'] ?? self::DEFAULT_FRAMEWORK));

        return [
            'key' => clean_param((string)($item['key'] ?? $this->make_key($idnumber)), PARAM_ALPHANUMEXT),
            'idnumber' => clean_param($idnumber, PARAM_TEXT),
            'shortname' => trim(clean_param((string)($item['shortname'] ?? $item['name'] ?? ''), PARAM_TEXT)),
            'description' => (string)($item['description'] ?? ''),
            'framework' => clean_param($framework, PARAM_ALPHANUMEXT),
            'parent' => clean_param((string)($item['parent'] ?? ''), PARAM_TEXT),
            'scale' => clean_param((string)($item['scale'] ?? ''), PARAM_TEXT),
            'sortorder' => max(0, (int)($item['sortorder'] ?? 0)),
            'metadata' => $this->normalise_metadata($item['metadata'] ?? []),
        ];
    }

    /**
     * Normalise metadata.
     *
     * @param mixed $metadata Metadata.
     * @return array<string, mixed>
     */
    private function normalise_metadata(mixed $metadata): array {
        if ($metadata === null || $metadata === '') {
            return [];
        }

        if (is_string($metadata)) {
            $decoded = json_decode($metadata, true);

            return is_array($decoded) ? $decoded : [];
        }

        if ($metadata instanceof stdClass) {
            return (array)$metadata;
        }

        return is_array($metadata) ? $metadata : [];
    }

    /**
     * Normalise execution mode.
     *
     * @param string $mode Raw mode.
     * @return string
     */
    private function normalise_mode(string $mode): string {
        $mode = clean_param($mode, PARAM_ALPHANUMEXT);

        return in_array($mode, [
            self::MODE_APPLY,
            self::MODE_DRY_RUN,
            self::MODE_REPORT,
            'rollback_plan',
        ], true) ? $mode : self::MODE_APPLY;
    }

    /**
     * Return whether Moodle competency API classes are available.
     *
     * @return bool
     */
    private function competency_api_available(): bool {
        return class_exists(api::class)
            && class_exists(competency_framework::class)
            && class_exists(competency::class);
    }

    /**
     * Validate canonical UCKK competency idnumber shape.
     *
     * @param string $idnumber Idnumber.
     * @return bool
     */
    private function is_uckk_competency_idnumber(string $idnumber): bool {
        return preg_match('/^UCKK-COMP-\d{3}$/', $idnumber) === 1;
    }

    /**
     * Make a stable preset key from an idnumber.
     *
     * @param string $idnumber Idnumber.
     * @return string
     */
    private function make_key(string $idnumber): string {
        $key = strtolower($idnumber);
        $key = str_replace(['uckk-comp-', '-'], ['', '_'], $key);
        $key = preg_replace('/[^a-z0-9_]+/', '_', $key);

        return trim((string)$key, '_');
    }

    /**
     * Create a result object.
     *
     * @param string $action Action/mode name.
     * @return validation_result
     */
    private function new_result(string $action): validation_result {
        $result = new validation_result();

        $result->status = 'pending';
        $result->ok = true;
        $result->haserrors = false;
        $result->haswarnings = false;
        $result->summary = '';
        $result->counts = [
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'failed' => 0,
            'warnings' => 0,
            'errors' => 0,
        ];
        $result->messages = [];
        $result->created = [];
        $result->updated = [];
        $result->skipped = [];
        $result->failed = [];
        $result->metadata = [
            'component' => self::COMPONENT,
            'preset' => self::PRESET,
            'targettype' => self::TARGET_TYPE,
            'action' => $action,
        ];

        return $result;
    }

    /**
     * Add a message to a validation result.
     *
     * @param validation_result $result Result object.
     * @param string $severity Severity.
     * @param string $message Message.
     * @param string $targetkey Target key.
     * @param array<string, mixed> $metadata Metadata.
     */
    private function add_message(
        validation_result $result,
        string $severity,
        string $message,
        string $targetkey = '',
        array $metadata = []
    ): void {
        $row = [
            'severity' => $severity,
            'component' => self::COMPONENT,
            'preset' => self::PRESET,
            'targettype' => self::TARGET_TYPE,
            'targetkey' => $targetkey,
            'message' => $message,
            'metadata' => $metadata,
        ];

        if (method_exists($result, 'add_message')) {
            $result->add_message($row);
        } else {
            $result->messages[] = $row;
        }

        if ($severity === self::SEVERITY_WARNING) {
            $result->haswarnings = true;
            $this->increment_count($result, 'warnings');
        }

        if (in_array($severity, [self::SEVERITY_ERROR, self::SEVERITY_BLOCKER], true)) {
            $result->haserrors = true;
            $result->ok = false;
            $this->increment_count($result, 'errors');
        }
    }

    /**
     * Merge another validation result into this result.
     *
     * @param validation_result $target Target result.
     * @param validation_result $source Source result.
     */
    private function merge_result(validation_result $target, validation_result $source): void {
        if (method_exists($target, 'merge')) {
            $target->merge($source);
            return;
        }

        $target->messages = array_merge($target->messages ?? [], $source->messages ?? []);

        foreach (($source->counts ?? []) as $key => $value) {
            $target->counts[$key] = ($target->counts[$key] ?? 0) + (int)$value;
        }

        $target->ok = !empty($target->ok) && !empty($source->ok);
        $target->haserrors = !empty($target->haserrors) || !empty($source->haserrors);
        $target->haswarnings = !empty($target->haswarnings) || !empty($source->haswarnings);
    }

    /**
     * Increment a count field.
     *
     * @param validation_result $result Result object.
     * @param string $key Count key.
     * @param int $amount Increment.
     */
    private function increment_count(validation_result $result, string $key, int $amount = 1): void {
        if (!isset($result->counts) || !is_array($result->counts)) {
            $result->counts = [];
        }

        $result->counts[$key] = ($result->counts[$key] ?? 0) + $amount;
    }

    /**
     * Return whether the result has blocking errors.
     *
     * @param validation_result $result Result object.
     * @return bool
     */
    private function result_has_blocking_errors(validation_result $result): bool {
        foreach (($result->messages ?? []) as $message) {
            $message = (array)$message;

            if (in_array($message['severity'] ?? '', [self::SEVERITY_ERROR, self::SEVERITY_BLOCKER], true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Finalise result status and summary.
     *
     * @param validation_result $result Result object.
     * @return validation_result
     */
    private function finalise_result(validation_result $result): validation_result {
        $result->haserrors = !empty($result->haserrors);
        $result->haswarnings = !empty($result->haswarnings);
        $result->ok = !$result->haserrors;

        if ($result->haserrors) {
            $result->status = 'failed';
            $result->summary = 'Competency seed completed with errors.';
        } else if ($result->haswarnings) {
            $result->status = 'warning';
            $result->summary = 'Competency seed completed with warnings.';
        } else {
            $result->status = 'completed';
            $result->summary = 'Competency seed completed successfully.';
        }

        return $result;
    }
}
