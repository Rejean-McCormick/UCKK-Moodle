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
 * Supported preset item shapes:
 *
 * Framework row:
 * [
 *     'object_type' => 'competency_framework',
 *     'key' => 'uckk_competency_framework',
 *     'idnumber' => 'UCKK-COMP-FRAMEWORK',
 *     'shortname' => 'UCKK competency framework',
 *     'description' => '...',
 *     'metadata' => []
 * ]
 *
 * Competency row:
 * [
 *     'key' => 'uckk_comp_tc101',
 *     'idnumber' => 'UCKK-COMP-TC101',
 *     'shortname' => 'Read the game',
 *     'description' => '...',
 *     'framework' => 'UCKK-COMP-FRAMEWORK',
 *     'framework_id' => 'UCKK-COMP-FRAMEWORK',
 *     'parent' => '',
 *     'parent_idnumber' => '',
 *     'scale' => '',
 *     'sortorder' => 10,
 *     'metadata' => []
 * ]
 *
 * Idempotency:
 * - frameworks are matched by idnumber;
 * - competencies are matched by idnumber inside their framework;
 * - dry-run/report mode reports planned changes without writing data.
 */
final class competency_seed {
    /** Component name. */
    private const COMPONENT = 'tool_uckkseed';

    /** Preset id. */
    private const PRESET = 'competencies';

    /** Target type. */
    private const TARGET_TYPE = 'competency';

    /** Default framework idnumber. */
    private const DEFAULT_FRAMEWORK = 'UCKK-COMP-FRAMEWORK';

    /** Default framework shortname. */
    private const DEFAULT_FRAMEWORK_SHORTNAME = 'UCKK competency framework';

    /** Execution mode: dry run. */
    private const MODE_DRY_RUN = 'dry_run';

    /** Execution mode: apply. */
    private const MODE_APPLY = 'apply';

    /** Execution mode: report. */
    private const MODE_REPORT = 'report';

    /** Execution mode: rollback plan. */
    private const MODE_ROLLBACK_PLAN = 'rollback_plan';

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

    /** Supported framework object marker. */
    private const OBJECT_FRAMEWORK = 'competency_framework';

    /** Supported competency object marker. */
    private const OBJECT_COMPETENCY = 'competency';

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

        if (empty($items)) {
            $this->add_message(
                $result,
                self::SEVERITY_WARNING,
                'No competency preset items were provided.',
                '',
                []
            );

            return $this->finalise_result($result);
        }

        $seen = [];
        $frameworks = [];

        foreach ($items as $index => $rawitem) {
            $item = $this->normalise_item($rawitem);
            $targetkey = $item['key'] !== '' ? $item['key'] : 'index_' . $index;

            if ($item['key'] === '') {
                $this->add_message(
                    $result,
                    self::SEVERITY_ERROR,
                    'Competency item is missing key.',
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

            if ($item['idnumber'] !== '' && !$this->is_uckk_competency_idnumber($item['idnumber'])) {
                $this->add_message(
                    $result,
                    self::SEVERITY_WARNING,
                    'Competency idnumber is not in the canonical UCKK-COMP-[A-Z0-9-]+ form.',
                    $targetkey,
                    ['idnumber' => $item['idnumber']]
                );
            }

            if ($item['object_type'] === self::OBJECT_FRAMEWORK) {
                $duplicatekey = 'framework:' . $item['idnumber'];
                $frameworks[$item['idnumber']] = true;
            } else {
                if ($item['framework'] === '') {
                    $this->add_message(
                        $result,
                        self::SEVERITY_ERROR,
                        'Competency item is missing framework.',
                        $targetkey,
                        ['index' => $index]
                    );
                }

                $duplicatekey = $item['framework'] . ':' . $item['idnumber'];
            }

            if ($item['idnumber'] !== '' && isset($seen[$duplicatekey])) {
                $this->add_message(
                    $result,
                    self::SEVERITY_ERROR,
                    'Duplicate competency/framework idnumber in preset.',
                    $targetkey,
                    [
                        'idnumber' => $item['idnumber'],
                        'framework' => $item['framework'],
                    ]
                );
            }

            if ($item['idnumber'] !== '') {
                $seen[$duplicatekey] = true;
            }
        }

        foreach ($items as $index => $rawitem) {
            $item = $this->normalise_item($rawitem);
            $targetkey = $item['key'] !== '' ? $item['key'] : 'index_' . $index;

            if ($item['object_type'] === self::OBJECT_FRAMEWORK) {
                continue;
            }

            if ($item['framework'] !== '' && !isset($frameworks[$item['framework']]) && !$this->find_framework($item['framework'])) {
                $this->add_message(
                    $result,
                    self::SEVERITY_INFO,
                    'Referenced framework is not in this preset and does not exist yet; apply mode will create it.',
                    $targetkey,
                    ['framework' => $item['framework']]
                );
            }
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
        $dryrun = $mode === self::MODE_DRY_RUN || $mode === self::MODE_REPORT || !empty($options['dryrun']);
        $result = $this->new_result($dryrun ? self::MODE_DRY_RUN : self::MODE_APPLY);

        $validation = $this->validate($items, $options);
        $this->merge_result($result, $validation);

        if ($this->result_has_blocking_errors($validation)) {
            return $this->finalise_result($result);
        }

        $frameworkcache = [];

        foreach ($items as $rawitem) {
            $item = $this->normalise_item($rawitem);

            if ($item['idnumber'] === '' || $item['shortname'] === '') {
                $this->increment_count($result, 'failed');
                continue;
            }

            if ($item['object_type'] === self::OBJECT_FRAMEWORK) {
                $frameworkidnumber = $item['idnumber'];
                $frameworkcache[$frameworkidnumber] = $this->ensure_framework($frameworkidnumber, $item, $dryrun, $result);
                continue;
            }

            if ($item['framework'] === '') {
                $this->increment_count($result, 'failed');
                continue;
            }

            $frameworkidnumber = $item['framework'];

            if (!isset($frameworkcache[$frameworkidnumber])) {
                $frameworkcache[$frameworkidnumber] = $this->ensure_framework($frameworkidnumber, $options, $dryrun, $result);
            }

            $frameworkid = (int)$frameworkcache[$frameworkidnumber];

            if ($dryrun) {
                $exists = $frameworkid > 0 ? $this->find_competency($item['idnumber'], $frameworkid) : null;

                $this->add_message(
                    $result,
                    self::SEVERITY_INFO,
                    $exists ? 'Dry run: competency would be updated.' : 'Dry run: competency would be created.',
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
                        'Competency updated.',
                        $item['idnumber'],
                        ['framework' => $frameworkidnumber]
                    );
                } else {
                    $this->create_competency($item, $frameworkid);
                    $this->increment_count($result, 'created');

                    $this->add_message(
                        $result,
                        self::SEVERITY_SUCCESS,
                        'Competency created.',
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
        $mode = $this->normalise_mode((string)($options['mode'] ?? self::MODE_ROLLBACK_PLAN));
        $dryrun = $mode === self::MODE_DRY_RUN || $mode === self::MODE_ROLLBACK_PLAN || empty($options['force']);
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

        foreach ($items as $rawitem) {
            $item = $this->normalise_item($rawitem);

            if ($item['object_type'] === self::OBJECT_FRAMEWORK) {
                $this->increment_count($result, 'skipped');
                $this->add_message(
                    $result,
                    self::SEVERITY_INFO,
                    'Framework reset is skipped by design.',
                    $item['idnumber'],
                    []
                );
                continue;
            }

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
                api::delete_competency($competency->get('id'));
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
                    ['exception' => get_class($exception)]
                );
            }
        }

        return $this->finalise_result($result);
    }

    /**
     * Export current competencies in preset shape.
     *
     * @param array<string, mixed> $options Runtime options.
     * @return array<string, mixed>
     */
    public function export(array $options = []): array {
        $frameworkidnumber = clean_param(
            (string)($options['framework'] ?? self::DEFAULT_FRAMEWORK),
            PARAM_TEXT
        );

        $items = [];

        if ($this->competency_api_available()) {
            $framework = $this->find_framework($frameworkidnumber);

            if ($framework) {
                $items[] = [
                    'object_type' => self::OBJECT_FRAMEWORK,
                    'key' => $this->make_key((string)$framework->get('idnumber')),
                    'idnumber' => (string)$framework->get('idnumber'),
                    'shortname' => (string)$framework->get('shortname'),
                    'description' => (string)$framework->get('description'),
                    'metadata' => [
                        'exported_from' => 'core_competency',
                    ],
                ];

                $competencies = competency::get_records([
                    'competencyframeworkid' => (int)$framework->get('id'),
                ], 'sortorder ASC, idnumber ASC');

                foreach ($competencies as $competency) {
                    $items[] = [
                        'object_type' => self::OBJECT_COMPETENCY,
                        'key' => $this->make_key((string)$competency->get('idnumber')),
                        'idnumber' => (string)$competency->get('idnumber'),
                        'shortname' => (string)$competency->get('shortname'),
                        'description' => (string)$competency->get('description'),
                        'framework' => $frameworkidnumber,
                        'framework_id' => $frameworkidnumber,
                        'parent' => '',
                        'parent_idnumber' => '',
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
     * @param array<string, mixed> $source Runtime options or framework item.
     * @param bool $dryrun Whether this is a dry run.
     * @param validation_result $result Result object.
     * @return int Framework id, or 0 during dry-run creation.
     */
    private function ensure_framework(
        string $idnumber,
        array $source,
        bool $dryrun,
        validation_result $result
    ): int {
        $framework = $this->find_framework($idnumber);

        if ($framework) {
            $this->ensure_framework_scale_configured($framework, $source, $dryrun, $result);
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
        $scaleid = $this->resolve_framework_scaleid($source);

        $record = new stdClass();
        $record->shortname = (string)($source['shortname'] ?? $source['frameworkshortname'] ?? self::DEFAULT_FRAMEWORK_SHORTNAME);
        $record->idnumber = $idnumber;
        $record->description = (string)($source['description'] ?? $source['frameworkdescription'] ?? 'UCKK canonical competency framework.');
        $record->descriptionformat = FORMAT_HTML;
        $record->contextid = $context->id;
        $record->visible = 1;
        $record->scaleid = $scaleid;
        $record->scaleconfiguration = $this->build_scale_configuration($scaleid);

        $created = api::create_framework($record);

        $this->add_message(
            $result,
            self::SEVERITY_SUCCESS,
            'Competency framework created.',
            $idnumber,
            ['framework' => $idnumber, 'scaleid' => $scaleid]
        );

        return (int)$created->get('id');
    }

    /**
     * Ensure an existing competency framework has a valid Moodle scale configuration.
     *
     * @param competency_framework $framework Existing framework.
     * @param array<string, mixed> $source Runtime options or framework item.
     * @param bool $dryrun Whether this is a dry run.
     * @param validation_result $result Result object.
     */
    private function ensure_framework_scale_configured(
        competency_framework $framework,
        array $source,
        bool $dryrun,
        validation_result $result
    ): void {
        $scaleid = (int)$framework->get('scaleid');
        $scaleconfiguration = (string)$framework->get('scaleconfiguration');

        if (
            $scaleid > 0
            && $this->scale_configuration_has_default_and_proficient($scaleconfiguration, $scaleid)
        ) {
            return;
        }

        $idnumber = (string)$framework->get('idnumber');

        if ($dryrun) {
            $this->add_message(
                $result,
                self::SEVERITY_WARNING,
                'Dry run: competency framework scale configuration would be repaired.',
                $idnumber,
                ['framework' => $idnumber]
            );

            return;
        }

        $scaleid = $this->resolve_framework_scaleid($source);
        $record = $framework->to_record();
        $record->scaleid = $scaleid;
        $record->scaleconfiguration = $this->build_scale_configuration($scaleid);

        if (method_exists(api::class, 'update_framework')) {
            api::update_framework($record);
        } else {
            $framework->set('scaleid', $record->scaleid);
            $framework->set('scaleconfiguration', $record->scaleconfiguration);
            $framework->update();
        }

        $this->add_message(
            $result,
            self::SEVERITY_SUCCESS,
            'Competency framework scale configuration repaired.',
            $idnumber,
            ['framework' => $idnumber, 'scaleid' => $scaleid]
        );
    }

    /**
     * Resolve the Moodle scale id to use for UCKK competency frameworks.
     *
     * @param array<string, mixed> $source Runtime options or framework item.
     * @return int Moodle scale id.
     */
    private function resolve_framework_scaleid(array $source): int {
        global $DB;

        $metadata = $this->normalise_metadata($source['metadata'] ?? []);

        $scaleid = max(0, (int)(
            $source['scaleid']
            ?? $source['frameworkscaleid']
            ?? $metadata['scaleid']
            ?? $metadata['frameworkscaleid']
            ?? 0
        ));

        if ($scaleid > 0 && $DB->record_exists('scale', ['id' => $scaleid])) {
            return $scaleid;
        }

        $existing = $this->find_existing_competency_scaleid();

        if ($existing > 0) {
            return $existing;
        }

        return $this->create_uckk_competency_scale();
    }

    /**
     * Find an existing competency-like Moodle scale that has at least two levels.
     *
     * @return int Moodle scale id, or 0 if none was found.
     */
    private function find_existing_competency_scaleid(): int {
        global $DB;

        $records = $DB->get_records('scale', [], 'id ASC', 'id,name,scale');

        foreach ($records as $record) {
            $name = strtolower((string)$record->name);

            if (
                (strpos($name, 'uckk') !== false || strpos($name, 'compet') !== false || strpos($name, 'compét') !== false)
                && count($this->scale_items((string)$record->scale)) >= 2
            ) {
                return (int)$record->id;
            }
        }

        return 0;
    }

    /**
     * Create the default UCKK competency scale.
     *
     * @return int New Moodle scale id.
     */
    private function create_uckk_competency_scale(): int {
        global $DB;

        $admin = get_admin();

        $record = new stdClass();
        $record->courseid = 0;
        $record->userid = $admin ? (int)$admin->id : 0;
        $record->name = 'UCKK competence scale';
        $record->scale = 'Non encore démontré,Démontré';
        $record->description = 'Scale created automatically by tool_uckkseed for UCKK competency frameworks.';
        $record->descriptionformat = FORMAT_HTML;
        $record->timemodified = time();

        return (int)$DB->insert_record('scale', $record);
    }

    /**
     * Build Moodle competency scale configuration JSON.
     *
     * The first scale value is the default. The last scale value is proficient.
     *
     * @param int $scaleid Moodle scale id.
     * @return string JSON scale configuration.
     */
    private function build_scale_configuration(int $scaleid): string {
        global $DB;

        $scale = $DB->get_record('scale', ['id' => $scaleid], '*', MUST_EXIST);
        $items = $this->scale_items((string)$scale->scale);

        if (count($items) < 2) {
            throw new \coding_exception('Competency scale requires at least two values.');
        }

        $lastposition = count($items);
        $config = [
            ['scaleid' => $scaleid],
        ];

        foreach ($items as $index => $name) {
            $position = $index + 1;

            $config[] = [
                'id' => $position,
                'name' => $name,
                'scaledefault' => $position === 1 ? 1 : 0,
                'proficient' => $position === $lastposition ? 1 : 0,
            ];
        }

        $json = json_encode($config, JSON_UNESCAPED_UNICODE);

        if ($json === false) {
            throw new \coding_exception('Could not encode competency scale configuration.');
        }

        return $json;
    }

    /**
     * Split a Moodle scale string into clean item names.
     *
     * @param string $scale Scale value.
     * @return array<int, string> Scale items.
     */
    private function scale_items(string $scale): array {
        $items = array_map('trim', explode(',', $scale));

        return array_values(array_filter($items, static function (string $item): bool {
            return $item !== '';
        }));
    }

    /**
     * Check whether scale configuration has both a default and a proficient value.
     *
     * @param string $value Scale configuration JSON.
     * @param int $scaleid Expected Moodle scale id.
     * @return bool
     */
    private function scale_configuration_has_default_and_proficient(string $value, int $scaleid): bool {
        $defaultselected = false;
        $proficientselected = false;

        $config = json_decode($value);

        if (!is_array($config) || empty($config)) {
            return false;
        }

        $scaleinfo = array_shift($config);

        if (empty($scaleinfo) || !isset($scaleinfo->scaleid) || (int)$scaleinfo->scaleid !== $scaleid) {
            return false;
        }

        foreach ($config as $item) {
            if (!empty($item->scaledefault)) {
                $defaultselected = true;
            }

            if (!empty($item->proficient)) {
                $proficientselected = true;
            }
        }

        return $defaultselected && $proficientselected;
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
        $scaleid = (int)($item['scaleid'] ?? 0);

        if ($scaleid > 0) {
            $record->scaleid = $scaleid;
            $record->scaleconfiguration = $this->build_scale_configuration($scaleid);
        } else {
            $record->scaleid = null;
            $record->scaleconfiguration = null;
        }
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

        $framework = reset($records);
        return $framework instanceof competency_framework ? $framework : null;
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

        $competency = reset($records);
        return $competency instanceof competency ? $competency : null;
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
        $metadata = $this->normalise_metadata($item['metadata'] ?? []);
        $moodle = $this->normalise_metadata($item['moodle'] ?? []);

        $objecttype = clean_param((string)($item['object_type'] ?? self::OBJECT_COMPETENCY), PARAM_ALPHANUMEXT);
        $objecttype = $objecttype === self::OBJECT_FRAMEWORK ? self::OBJECT_FRAMEWORK : self::OBJECT_COMPETENCY;

        $idnumber = trim((string)($item['idnumber'] ?? $item['key'] ?? ''));

        // The UCKK registry JSON may contain internal registry references such as
        // "competency_framework:uckk-academic" in framework/framework_id. Moodle
        // needs the actual competency framework idnumber instead.
        $framework = trim((string)(
            $item['framework_idnumber']
            ?? $metadata['framework_idnumber']
            ?? $moodle['framework_idnumber']
            ?? $moodle['framework']
            ?? $item['framework']
            ?? $item['framework_id']
            ?? self::DEFAULT_FRAMEWORK
        ));

        if ($framework === '' || str_starts_with($framework, 'competency_framework:')) {
            $framework = self::DEFAULT_FRAMEWORK;
        }

        // Prefer Moodle parent idnumbers over registry ids. Top-level programme
        // synthesis competencies should not use the framework idnumber as parent.
        $parent = trim((string)(
            $moodle['parent_idnumber']
            ?? $item['parent_idnumber']
            ?? $item['parent']
            ?? $item['parent_competency_id']
            ?? ''
        ));

        if (
            $parent === self::DEFAULT_FRAMEWORK
            || str_starts_with($parent, 'competency_framework:')
            || str_starts_with($parent, 'competency:')
        ) {
            $parent = '';
        }

        $scale = $item['scale'] ?? '';
        $scaleid = (int)(
            $item['scaleid']
            ?? $metadata['scaleid']
            ?? $moodle['scaleid']
            ?? 0
        );

        if (is_array($scale) || $scale instanceof stdClass) {
            $scaledata = $this->normalise_metadata($scale);
            $scale = (string)($scaledata['idnumber'] ?? $scaledata['id'] ?? '');

            if ($scaleid <= 0) {
                $scaleid = (int)(
                    $scaledata['scaleid']
                    ?? $scaledata['moodle_scaleid']
                    ?? 0
                );
            }
        } else {
            $scale = (string)$scale;
        }

        $shortname = trim((string)(
            $item['shortname']
            ?? $item['name']
            ?? $item['short_title']
            ?? $item['title']
            ?? $idnumber
        ));

        $fullname = trim((string)(
            $item['fullname']
            ?? $item['title']
            ?? $item['name']
            ?? $shortname
        ));

        return [
            'object_type' => $objecttype,
            'key' => clean_param((string)($item['key'] ?? $this->make_key($idnumber)), PARAM_ALPHANUMEXT),
            'idnumber' => clean_param($idnumber, PARAM_TEXT),
            'shortname' => clean_param($shortname !== '' ? $shortname : $fullname, PARAM_TEXT),
            'fullname' => clean_param($fullname, PARAM_TEXT),
            'description' => (string)($item['description'] ?? ''),
            'framework' => $objecttype === self::OBJECT_FRAMEWORK ? '' : clean_param($framework, PARAM_TEXT),
            'parent' => clean_param($parent, PARAM_TEXT),
            'scale' => clean_param($scale, PARAM_TEXT),
            'scaleid' => max(0, $scaleid),
            'sortorder' => max(0, (int)($item['sortorder'] ?? 0)),
            'metadata' => $metadata,
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
            self::MODE_ROLLBACK_PLAN,
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
     * Final contract accepts semantic/non-numeric suffixes such as
     * UCKK-COMP-TC101, UCKK-COMP-GJS-SYNTHESIS, and UCKK-COMP-FRAMEWORK.
     *
     * @param string $idnumber Idnumber.
     * @return bool
     */
    private function is_uckk_competency_idnumber(string $idnumber): bool {
        return preg_match('/^UCKK-COMP-[A-Z0-9-]+$/', $idnumber) === 1;
    }

    /**
     * Make a stable preset key from an idnumber.
     *
     * @param string $idnumber Idnumber.
     * @return string
     */
    private function make_key(string $idnumber): string {
        $key = strtolower($idnumber);
        $key = preg_replace('/^uckk-comp-/', '', $key) ?? $key;
        $key = preg_replace('/[^a-z0-9_]+/', '_', $key) ?? $key;

        return trim((string)$key, '_');
    }

    /**
     * Create a result object.
     *
     * @param string $action Action/mode name.
     * @return validation_result
     */
    private function new_result(string $action): validation_result {
        return new validation_result(validation_result::STATUS_PENDING, '', [
            'component' => self::COMPONENT,
            'preset' => self::PRESET,
            'targettype' => self::TARGET_TYPE,
            'action' => $action,
        ]);
    }

    /**
     * Add a canonical result message.
     *
     * @param validation_result $result Result object.
     * @param string $severity Severity.
     * @param string $message Message text.
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
        $result->add_message(
            $severity,
            $message,
            self::COMPONENT,
            self::PRESET,
            self::TARGET_TYPE,
            $targetkey,
            $metadata
        );
    }

    /**
     * Merge another validation result into this result.
     *
     * @param validation_result $target Target result.
     * @param validation_result $source Source result.
     */
    private function merge_result(validation_result $target, validation_result $source): void {
        $target->merge($source);
    }

    /**
     * Increment a count field.
     *
     * @param validation_result $result Result object.
     * @param string $key Count key.
     * @param int $amount Increment.
     */
    private function increment_count(validation_result $result, string $key, int $amount = 1): void {
        $result->increment($key, $amount);
    }

    /**
     * Return whether the result has blocking errors.
     *
     * @param validation_result $result Result object.
     * @return bool
     */
    private function result_has_blocking_errors(validation_result $result): bool {
        foreach ($result->get_messages() as $message) {
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
        if ($result->has_errors()) {
            $result->set_status(validation_result::STATUS_FAILED);
            $result->set_summary('Competency seed completed with errors.');
        } else if ($result->has_warnings()) {
            $result->set_status(validation_result::STATUS_WARNING);
            $result->set_summary('Competency seed completed with warnings.');
        } else {
            $result->set_status(validation_result::STATUS_COMPLETED);
            $result->set_summary('Competency seed completed successfully.');
        }

        return $result;
    }
}