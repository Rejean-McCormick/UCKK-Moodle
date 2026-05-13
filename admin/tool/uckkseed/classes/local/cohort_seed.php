<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

/**
 * Cohort preset seeder for the UCKK seed tool.
 *
 * @package    tool_uckkseed
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace tool_uckkseed\local;

use context;
use context_coursecat;
use context_system;
use core_component;
use stdClass;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/cohort/lib.php');

/**
 * Seeds canonical Moodle cohorts for the UCKK distribution.
 *
 * Cohorts are used for institutional populations, program groups, symbolic
 * distinctions, workflow populations, and restricted integrity review groups.
 *
 * This class must not create Moodle roles for symbolic identities. Technical
 * role creation belongs to role_seed. Symbolic identities remain badges,
 * profile fields, cohorts, competencies, portfolio titles, and archive
 * distinctions.
 */
final class cohort_seed {
    /** Component owning this seeder. */
    public const COMPONENT = 'tool_uckkseed';

    /** Preset id. */
    public const PRESET = 'cohorts';

    /** Target type for logs/results. */
    public const TARGET_TYPE = 'cohort';

    /** Preset schema. */
    public const SCHEMA = 'uckkseed.preset.v1';

    /** Mode: dry run. */
    public const MODE_DRY_RUN = 'dry_run';

    /** Mode: apply. */
    public const MODE_APPLY = 'apply';

    /** Mode: report. */
    public const MODE_REPORT = 'report';

    /** Mode: rollback plan. */
    public const MODE_ROLLBACK_PLAN = 'rollback_plan';

    /** Result status: completed. */
    public const STATUS_COMPLETED = 'completed';

    /** Result status: failed. */
    public const STATUS_FAILED = 'failed';

    /** Result status: warning. */
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

    /** Default seeded cohort idnumber prefix. */
    private const IDNUMBER_PREFIX = 'UCKK-COHORT-';

    /** Technical roles allowed as cohort metadata references. */
    private const TECHNICAL_ROLES = [
        'uckkmanager',
        'uckkmentor',
        'uckkplayer',
        'uckkarchivist',
        'uckkinquisitor',
        'uckkobserver',
        'uckkpublicguest',
    ];

    /** Symbolic roles allowed as cohort metadata references. */
    private const SYMBOLIC_ROLES = [
        'joueur',
        'joueur_lucide',
        'batisseur',
        'archiviste',
        'inquisiteur',
        'cartographe',
        'architecte_sens',
        'architecte_opportunites',
        'gardien_systemes_vivants',
    ];

    /** Canonical UCKK program keys. */
    private const PROGRAMS = [
        'tronc_commun',
        'grand_jeu_social',
        'architecture_ecosysteme_digital_koa',
        'architecture_sociotechnique',
        'sciences_politiques',
        'economie',
        'ecologie',
        'metaphysique',
        'intelligence_artificielle_gouvernable',
        'linguistique_architecture_du_sens',
        'intervention_sociale',
        'medias_vivants_theatre_public',
        'seminaires_avances_laboratoires',
    ];

    /**
     * Validate cohort preset items.
     *
     * @param array $items Preset item rows.
     * @param array<string, mixed> $options Runtime options.
     * @return validation_result
     */
    public function validate(array $items, array $options = []): validation_result {
        $result = $this->new_result(get_string('cohortsvalidated', 'tool_uckkseed'));

        if (empty($items)) {
            $this->add_message(
                $result,
                self::SEVERITY_WARNING,
                '',
                get_string('presetempty', 'tool_uckkseed', self::PRESET)
            );

            $this->finish_result($result);
            return $result;
        }

        $seenkeys = [];
        $seenidnumbers = [];

        foreach ($items as $index => $item) {
            $item = $this->normalise_item($item);
            $targetkey = $item['key'] !== '' ? $item['key'] : 'row_' . $index;

            if ($item['key'] === '') {
                $this->add_message(
                    $result,
                    self::SEVERITY_ERROR,
                    $targetkey,
                    get_string('cohortseedmissingkey', 'tool_uckkseed')
                );
            }

            if ($item['name'] === '') {
                $this->add_message(
                    $result,
                    self::SEVERITY_ERROR,
                    $targetkey,
                    get_string('cohortseedmissingname', 'tool_uckkseed')
                );
            }

            if ($item['idnumber'] === '') {
                $this->add_message(
                    $result,
                    self::SEVERITY_ERROR,
                    $targetkey,
                    get_string('cohortseedmissingidnumber', 'tool_uckkseed')
                );
            }

            if ($item['idnumber'] !== '' && !str_starts_with($item['idnumber'], self::IDNUMBER_PREFIX)) {
                $this->add_message(
                    $result,
                    self::SEVERITY_WARNING,
                    $targetkey,
                    get_string('cohortseedidnumberprefixwarning', 'tool_uckkseed', self::IDNUMBER_PREFIX)
                );
            }

            if (!in_array($item['context'], ['system', 'course_category'], true)) {
                $this->add_message(
                    $result,
                    self::SEVERITY_ERROR,
                    $targetkey,
                    get_string('cohortseedinvalidcontext', 'tool_uckkseed')
                );
            }

            if ($item['context'] === 'course_category' && $item['category'] === '') {
                $this->add_message(
                    $result,
                    self::SEVERITY_ERROR,
                    $targetkey,
                    get_string('cohortseedmissingcategory', 'tool_uckkseed')
                );
            }

            if ($item['context'] === 'course_category' && $item['category'] !== '' && !$this->category_exists($item['category'])) {
                $this->add_message(
                    $result,
                    self::SEVERITY_WARNING,
                    $targetkey,
                    get_string('cohortseedcategorymissing', 'tool_uckkseed', $item['category'])
                );
            }

            $metadata = $item['metadata'];

            if (!empty($metadata['technical_role']) && !in_array($metadata['technical_role'], self::TECHNICAL_ROLES, true)) {
                $this->add_message(
                    $result,
                    self::SEVERITY_ERROR,
                    $targetkey,
                    get_string('cohortseedinvalidtechnicalrole', 'tool_uckkseed', $metadata['technical_role'])
                );
            }

            if (!empty($metadata['symbolic_role']) && !in_array($metadata['symbolic_role'], self::SYMBOLIC_ROLES, true)) {
                $this->add_message(
                    $result,
                    self::SEVERITY_ERROR,
                    $targetkey,
                    get_string('cohortseedinvalidsymbolicrole', 'tool_uckkseed', $metadata['symbolic_role'])
                );
            }

            if (!empty($metadata['symbolic_role']) && empty($metadata['not_moodle_role']) && $this->is_symbolic_distinction($item)) {
                $this->add_message(
                    $result,
                    self::SEVERITY_WARNING,
                    $targetkey,
                    get_string('cohortseedsymbolicnotrolewarning', 'tool_uckkseed')
                );
            }

            if (!empty($metadata['program']) && !in_array($metadata['program'], self::PROGRAMS, true)) {
                $this->add_message(
                    $result,
                    self::SEVERITY_WARNING,
                    $targetkey,
                    get_string('cohortseedunknownprogram', 'tool_uckkseed', $metadata['program'])
                );
            }

            if (!empty($metadata['related_component']) && !$this->component_installed((string)$metadata['related_component'])) {
                $this->add_message(
                    $result,
                    self::SEVERITY_WARNING,
                    $targetkey,
                    get_string('cohortseedcomponentmissing', 'tool_uckkseed', $metadata['related_component'])
                );
            }

            if (isset($seenkeys[$item['key']])) {
                $this->add_message(
                    $result,
                    self::SEVERITY_ERROR,
                    $targetkey,
                    get_string('cohortseedduplicatekey', 'tool_uckkseed', $item['key'])
                );
            }

            if (isset($seenidnumbers[$item['idnumber']])) {
                $this->add_message(
                    $result,
                    self::SEVERITY_ERROR,
                    $targetkey,
                    get_string('cohortseedduplicateidnumber', 'tool_uckkseed', $item['idnumber'])
                );
            }

            if ($item['key'] !== '') {
                $seenkeys[$item['key']] = true;
            }

            if ($item['idnumber'] !== '') {
                $seenidnumbers[$item['idnumber']] = true;
            }

            $this->increment($result, 'skipped');
        }

        $this->finish_result($result);
        return $result;
    }

    /**
     * Apply cohort preset items.
     *
     * @param array $items Preset item rows.
     * @param array<string, mixed> $options Runtime options.
     * @return validation_result
     */
    public function apply(array $items, array $options = []): validation_result {
        $mode = $this->normalise_mode((string)($options['mode'] ?? self::MODE_APPLY));
        $dryrun = $mode === self::MODE_DRY_RUN || !empty($options['dryrun']);
        $rollbackplan = $mode === self::MODE_ROLLBACK_PLAN || !empty($options['rollbackplan']);
        $force = !empty($options['force']);

        $validation = $this->validate($items, $options);

        if ($validation->haserrors) {
            return $validation;
        }

        $result = $this->new_result(
            $dryrun
                ? get_string('cohortsdryruncomplete', 'tool_uckkseed')
                : get_string('cohortsseeded', 'tool_uckkseed')
        );

        foreach ($items as $item) {
            $item = $this->normalise_item($item);
            $targetkey = $item['key'] !== '' ? $item['key'] : $item['idnumber'];

            if ($item['idnumber'] === '') {
                $this->increment($result, 'failed');
                continue;
            }

            $context = $this->resolve_context($item);

            if ($context === null) {
                $this->add_message(
                    $result,
                    self::SEVERITY_ERROR,
                    $targetkey,
                    get_string('cohortseedcontextunresolved', 'tool_uckkseed')
                );
                $this->increment($result, 'failed');
                continue;
            }

            $existing = $this->get_existing_cohort($item['idnumber']);
            $record = $this->build_cohort_record($item, $context, $existing);

            if ($dryrun || $rollbackplan) {
                $this->add_message(
                    $result,
                    self::SEVERITY_INFO,
                    $targetkey,
                    $existing === null
                        ? get_string('cohortseedwouldcreate', 'tool_uckkseed', $item['idnumber'])
                        : get_string('cohortseedwouldupdate', 'tool_uckkseed', $item['idnumber']),
                    [
                        'existing' => $existing,
                        'proposed' => $record,
                    ]
                );
                $this->increment($result, 'skipped');
                continue;
            }

            if ($existing === null) {
                $cohortid = cohort_add_cohort($record);

                $this->add_message(
                    $result,
                    self::SEVERITY_SUCCESS,
                    $targetkey,
                    get_string('cohortseedcreated', 'tool_uckkseed', $item['idnumber']),
                    ['cohortid' => $cohortid]
                );

                $this->increment($result, 'created');
                continue;
            }

            if (!$this->is_seed_managed($existing) && !$force) {
                $this->add_message(
                    $result,
                    self::SEVERITY_WARNING,
                    $targetkey,
                    get_string('cohortseednotmanagedskip', 'tool_uckkseed', $item['idnumber']),
                    ['cohortid' => $existing->id]
                );

                $this->increment($result, 'skipped');
                continue;
            }

            $record->id = $existing->id;
            cohort_update_cohort($record);

            $this->add_message(
                $result,
                self::SEVERITY_SUCCESS,
                $targetkey,
                get_string('cohortseedupdated', 'tool_uckkseed', $item['idnumber']),
                ['cohortid' => $existing->id]
            );

            $this->increment($result, 'updated');
        }

        $this->finish_result($result);
        return $result;
    }

    /**
     * Reset seed-managed cohorts.
     *
     * Reset deletes only cohorts managed by tool_uckkseed. It must not delete
     * arbitrary manually-created Moodle cohorts.
     *
     * @param array $items Preset item rows.
     * @param array<string, mixed> $options Runtime options.
     * @return validation_result
     */
    public function reset(array $items, array $options = []): validation_result {
        $mode = $this->normalise_mode((string)($options['mode'] ?? self::MODE_DRY_RUN));
        $dryrun = $mode === self::MODE_DRY_RUN || !empty($options['dryrun']);
        $confirmed = !empty($options['confirm']);

        $result = $this->new_result(
            $dryrun
                ? get_string('cohortsresetdryruncomplete', 'tool_uckkseed')
                : get_string('cohortsresetcomplete', 'tool_uckkseed')
        );

        if (!$dryrun && !$confirmed) {
            $this->add_message(
                $result,
                self::SEVERITY_BLOCKER,
                '',
                get_string('confirmationrequired', 'tool_uckkseed')
            );
            $this->finish_result($result);
            return $result;
        }

        $idnumbers = $this->get_reset_idnumbers($items);

        foreach ($idnumbers as $idnumber) {
            $existing = $this->get_existing_cohort($idnumber);

            if ($existing === null) {
                $this->add_message(
                    $result,
                    self::SEVERITY_INFO,
                    $idnumber,
                    get_string('cohortseedalreadyabsent', 'tool_uckkseed', $idnumber)
                );
                $this->increment($result, 'skipped');
                continue;
            }

            if (!$this->is_seed_managed($existing)) {
                $this->add_message(
                    $result,
                    self::SEVERITY_WARNING,
                    $idnumber,
                    get_string('cohortseednotmanagedskip', 'tool_uckkseed', $idnumber),
                    ['cohortid' => $existing->id]
                );
                $this->increment($result, 'skipped');
                continue;
            }

            if ($dryrun) {
                $this->add_message(
                    $result,
                    self::SEVERITY_INFO,
                    $idnumber,
                    get_string('cohortseedwouldremove', 'tool_uckkseed', $idnumber),
                    ['cohortid' => $existing->id]
                );
                $this->increment($result, 'skipped');
                continue;
            }

            cohort_delete_cohort($existing);

            $this->add_message(
                $result,
                self::SEVERITY_SUCCESS,
                $idnumber,
                get_string('cohortseedremoved', 'tool_uckkseed', $idnumber),
                ['cohortid' => $existing->id]
            );

            $this->increment($result, 'updated');
        }

        $this->finish_result($result);
        return $result;
    }

    /**
     * Export current seed-managed cohorts or default canonical data.
     *
     * @param array<string, mixed> $options Runtime options.
     * @return array<string, mixed>
     */
    public function export(array $options = []): array {
        $items = [];

        if (empty($options['defaults'])) {
            foreach ($this->get_seed_managed_cohorts() as $cohort) {
                $items[] = $this->cohort_to_preset_item($cohort);
            }
        }

        if (empty($items)) {
            $items = $this->get_default_items();
        }

        return [
            'schema' => self::SCHEMA,
            'component' => self::COMPONENT,
            'preset' => self::PRESET,
            'version' => 2026051200,
            'items' => array_values(array_map([$this, 'normalise_item'], $items)),
        ];
    }

    /**
     * Normalise a preset item.
     *
     * @param mixed $item Raw item.
     * @return array<string, mixed>
     */
    private function normalise_item(mixed $item): array {
        if ($item instanceof stdClass) {
            $item = (array)$item;
        }

        if (!is_array($item)) {
            $item = [];
        }

        $metadata = $this->normalise_metadata($item['metadata'] ?? []);

        return [
            'key' => clean_param((string)($item['key'] ?? ''), PARAM_ALPHANUMEXT),
            'name' => trim(clean_param((string)($item['name'] ?? ''), PARAM_TEXT)),
            'idnumber' => trim(clean_param((string)($item['idnumber'] ?? ''), PARAM_TEXT)),
            'context' => $this->normalise_context((string)($item['context'] ?? 'system')),
            'category' => clean_param((string)($item['category'] ?? $metadata['category'] ?? ''), PARAM_ALPHANUMEXT),
            'description' => trim((string)($item['description'] ?? '')),
            'visible' => $this->normalise_bool($item['visible'] ?? true),
            'status' => clean_param((string)($item['status'] ?? 'active'), PARAM_ALPHANUMEXT),
            'visibility' => clean_param((string)($item['visibility'] ?? 'institution'), PARAM_ALPHANUMEXT),
            'metadata' => $metadata,
        ];
    }

    /**
     * Build Moodle cohort record.
     *
     * @param array<string, mixed> $item Normalised item.
     * @param context $context Cohort context.
     * @param stdClass|null $existing Existing cohort, if any.
     * @return stdClass
     */
    private function build_cohort_record(array $item, context $context, ?stdClass $existing = null): stdClass {
        $record = new stdClass();

        if ($existing !== null) {
            $record->id = $existing->id;
        }

        $description = $item['description'];
        $metadata = $item['metadata'];

        $metadata['managedby'] = $metadata['managedby'] ?? self::COMPONENT;
        $metadata['seedkey'] = $item['key'];
        $metadata['status'] = $item['status'];
        $metadata['visibility'] = $item['visibility'];

        if (!str_contains($description, 'UCKK seed metadata:')) {
            $description = trim($description);
            $description .= $description === '' ? '' : "\n\n";
            $description .= 'UCKK seed metadata: '
                . json_encode($metadata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        $record->contextid = $context->id;
        $record->name = $item['name'];
        $record->idnumber = $item['idnumber'];
        $record->description = $description;
        $record->descriptionformat = FORMAT_MARKDOWN;
        $record->visible = $item['visible'] ? 1 : 0;
        $record->component = self::COMPONENT;

        return $record;
    }

    /**
     * Convert a Moodle cohort to canonical preset item data.
     *
     * @param stdClass $cohort Cohort record.
     * @return array<string, mixed>
     */
    private function cohort_to_preset_item(stdClass $cohort): array {
        $metadata = $this->extract_metadata((string)($cohort->description ?? ''));

        return [
            'key' => clean_param((string)($metadata['seedkey'] ?? strtolower(str_replace('-', '_', $cohort->idnumber))), PARAM_ALPHANUMEXT),
            'name' => (string)$cohort->name,
            'idnumber' => (string)$cohort->idnumber,
            'context' => $this->context_name_from_id((int)$cohort->contextid),
            'description' => $this->strip_metadata_from_description((string)($cohort->description ?? '')),
            'visible' => !empty($cohort->visible),
            'status' => (string)($metadata['status'] ?? 'active'),
            'visibility' => (string)($metadata['visibility'] ?? 'institution'),
            'metadata' => $metadata,
        ];
    }

    /**
     * Get existing cohort by idnumber.
     *
     * @param string $idnumber Cohort idnumber.
     * @return stdClass|null
     */
    private function get_existing_cohort(string $idnumber): ?stdClass {
        global $DB;

        if ($idnumber === '') {
            return null;
        }

        $record = $DB->get_record('cohort', ['idnumber' => $idnumber], '*', IGNORE_MISSING);

        return $record ?: null;
    }

    /**
     * Get seed-managed cohorts.
     *
     * @return stdClass[]
     */
    private function get_seed_managed_cohorts(): array {
        global $DB;

        return $DB->get_records_select(
            'cohort',
            'component = :component OR ' . $DB->sql_like('idnumber', ':prefix', false, false),
            [
                'component' => self::COMPONENT,
                'prefix' => $DB->sql_like_escape(self::IDNUMBER_PREFIX) . '%',
            ],
            'idnumber ASC'
        );
    }

    /**
     * Whether a cohort is seed-managed.
     *
     * @param stdClass $cohort Cohort record.
     * @return bool
     */
    private function is_seed_managed(stdClass $cohort): bool {
        if (($cohort->component ?? '') === self::COMPONENT) {
            return true;
        }

        if (str_starts_with((string)($cohort->idnumber ?? ''), self::IDNUMBER_PREFIX)) {
            return true;
        }

        $metadata = $this->extract_metadata((string)($cohort->description ?? ''));

        return ($metadata['managedby'] ?? '') === self::COMPONENT;
    }

    /**
     * Resolve context for a cohort item.
     *
     * @param array<string, mixed> $item Normalised item.
     * @return context|null
     */
    private function resolve_context(array $item): ?context {
        if ($item['context'] === 'system') {
            return context_system::instance();
        }

        if ($item['context'] === 'course_category') {
            $category = $this->get_category_by_key($item['category']);

            if (!$category) {
                return null;
            }

            return context_coursecat::instance((int)$category->id);
        }

        return null;
    }

    /**
     * Whether a category exists.
     *
     * @param string $categorykey Category key or idnumber.
     * @return bool
     */
    private function category_exists(string $categorykey): bool {
        return $this->get_category_by_key($categorykey) !== null;
    }

    /**
     * Get category by idnumber or name-like key.
     *
     * @param string $categorykey Category key.
     * @return stdClass|null
     */
    private function get_category_by_key(string $categorykey): ?stdClass {
        global $DB;

        $categorykey = trim($categorykey);

        if ($categorykey === '') {
            return null;
        }

        $record = $DB->get_record('course_categories', ['idnumber' => $categorykey], '*', IGNORE_MISSING);

        if ($record) {
            return $record;
        }

        $idnumber = 'UCKK-CAT-' . strtoupper(str_replace('_', '-', $categorykey));
        $record = $DB->get_record('course_categories', ['idnumber' => $idnumber], '*', IGNORE_MISSING);

        return $record ?: null;
    }

    /**
     * Return reset idnumbers.
     *
     * @param array $items Preset items.
     * @return string[]
     */
    private function get_reset_idnumbers(array $items): array {
        if (empty($items)) {
            return array_map(
                static fn(stdClass $cohort): string => (string)$cohort->idnumber,
                $this->get_seed_managed_cohorts()
            );
        }

        $idnumbers = [];

        foreach ($items as $item) {
            $item = $this->normalise_item($item);

            if ($item['idnumber'] !== '') {
                $idnumbers[] = $item['idnumber'];
            }
        }

        return array_values(array_unique($idnumbers));
    }

    /**
     * Return whether item is a symbolic distinction cohort.
     *
     * @param array<string, mixed> $item Normalised item.
     * @return bool
     */
    private function is_symbolic_distinction(array $item): bool {
        return ($item['metadata']['type'] ?? '') === 'symbolic_distinction'
            || str_starts_with($item['key'], 'symbolic_');
    }

    /**
     * Normalise context value.
     *
     * @param string $context Raw context.
     * @return string
     */
    private function normalise_context(string $context): string {
        $context = clean_param($context, PARAM_ALPHANUMEXT);

        return in_array($context, ['system', 'course_category'], true) ? $context : 'system';
    }

    /**
     * Get context name from id.
     *
     * @param int $contextid Context id.
     * @return string
     */
    private function context_name_from_id(int $contextid): string {
        $context = context::instance_by_id($contextid, IGNORE_MISSING);

        if ($context instanceof context_coursecat) {
            return 'course_category';
        }

        return 'system';
    }

    /**
     * Normalise boolean values.
     *
     * @param mixed $value Raw value.
     * @return bool
     */
    private function normalise_bool(mixed $value): bool {
        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value)) {
            return $value === 1;
        }

        if (is_string($value)) {
            return in_array(strtolower($value), ['1', 'true', 'yes', 'enabled', 'visible', 'on'], true);
        }

        return false;
    }

    /**
     * Normalise metadata.
     *
     * @param mixed $metadata Raw metadata.
     * @return array<string, mixed>
     */
    private function normalise_metadata(mixed $metadata): array {
        if ($metadata === null || $metadata === '') {
            return [];
        }

        if (is_string($metadata)) {
            $decoded = json_decode($metadata, true);

            if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
                return [];
            }

            return $decoded;
        }

        if ($metadata instanceof stdClass) {
            return (array)$metadata;
        }

        if (is_array($metadata)) {
            return $metadata;
        }

        return [];
    }

    /**
     * Extract seed metadata from cohort description.
     *
     * @param string $description Description.
     * @return array<string, mixed>
     */
    private function extract_metadata(string $description): array {
        $marker = 'UCKK seed metadata:';
        $position = strpos($description, $marker);

        if ($position === false) {
            return [];
        }

        $json = trim(substr($description, $position + strlen($marker)));
        $decoded = json_decode($json, true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Strip seed metadata from description.
     *
     * @param string $description Description.
     * @return string
     */
    private function strip_metadata_from_description(string $description): string {
        $marker = 'UCKK seed metadata:';
        $position = strpos($description, $marker);

        if ($position === false) {
            return trim($description);
        }

        return trim(substr($description, 0, $position));
    }

    /**
     * Return whether a component is installed.
     *
     * @param string $component Component name.
     * @return bool
     */
    private function component_installed(string $component): bool {
        $component = clean_param($component, PARAM_COMPONENT);

        if ($component === '') {
            return false;
        }

        [$type, $name] = core_component::normalize_component($component);

        if ($type === 'core' || $name === null) {
            return false;
        }

        return core_component::get_plugin_directory($type, $name) !== null;
    }

    /**
     * Normalise mode.
     *
     * @param string $mode Raw mode.
     * @return string
     */
    private function normalise_mode(string $mode): string {
        $mode = clean_param($mode, PARAM_ALPHANUMEXT);

        $allowed = [
            self::MODE_APPLY,
            self::MODE_DRY_RUN,
            self::MODE_REPORT,
            self::MODE_ROLLBACK_PLAN,
        ];

        return in_array($mode, $allowed, true) ? $mode : self::MODE_DRY_RUN;
    }

    /**
     * Default export data.
     *
     * @return array<int, array<string, mixed>>
     */
    private function get_default_items(): array {
        return [
            [
                'key' => 'uckk_all',
                'name' => 'UCKK — Communauté complète',
                'idnumber' => 'UCKK-COHORT-ALL',
                'context' => 'system',
                'description' => 'Cohorte globale pour les personnes rattachées au campus UCKK-Moodle.',
                'visible' => true,
                'status' => 'active',
                'visibility' => 'institution',
                'metadata' => [
                    'managedby' => self::COMPONENT,
                    'type' => 'institutional',
                    'scope' => 'campus',
                    'language' => 'fr',
                    'public_accreditation_claim' => false,
                ],
            ],
            [
                'key' => 'uckk_players',
                'name' => 'UCKK — Joueurs',
                'idnumber' => 'UCKK-COHORT-PLAYERS',
                'context' => 'system',
                'description' => 'Cohorte principale des Joueurs UCKK.',
                'visible' => true,
                'status' => 'active',
                'visibility' => 'institution',
                'metadata' => [
                    'managedby' => self::COMPONENT,
                    'type' => 'technical_population',
                    'technical_role' => 'uckkplayer',
                    'symbolic_role' => 'joueur',
                ],
            ],
            [
                'key' => 'uckk_mentors',
                'name' => 'UCKK — Mentors',
                'idnumber' => 'UCKK-COHORT-MENTORS',
                'context' => 'system',
                'description' => 'Cohorte des Mentors UCKK.',
                'visible' => true,
                'status' => 'active',
                'visibility' => 'institution',
                'metadata' => [
                    'managedby' => self::COMPONENT,
                    'type' => 'technical_staff',
                    'technical_role' => 'uckkmentor',
                ],
            ],
            [
                'key' => 'uckk_inquisitors',
                'name' => 'UCKK — Inquisiteurs méthodologiques',
                'idnumber' => 'UCKK-COHORT-INQUISITORS',
                'context' => 'system',
                'description' => 'Cohorte restreinte pour les réviseurs d’intégrité.',
                'visible' => false,
                'status' => 'active',
                'visibility' => 'restricted_integrity',
                'metadata' => [
                    'managedby' => self::COMPONENT,
                    'type' => 'technical_staff',
                    'technical_role' => 'uckkinquisitor',
                    'restricted' => true,
                    'related_component' => 'tool_uckkintegrity',
                ],
            ],
            [
                'key' => 'symbolic_joueur_lucide',
                'name' => 'Distinction — Joueurs lucides',
                'idnumber' => 'UCKK-COHORT-SYMBOLIC-JOUEUR-LUCIDE',
                'context' => 'system',
                'description' => 'Cohorte de distinction pour les Joueurs lucides. Cette cohorte ne crée pas de rôle technique Moodle.',
                'visible' => true,
                'status' => 'active',
                'visibility' => 'institution',
                'metadata' => [
                    'managedby' => self::COMPONENT,
                    'type' => 'symbolic_distinction',
                    'symbolic_role' => 'joueur_lucide',
                    'not_moodle_role' => true,
                    'related_badge' => 'joueur_lucide',
                ],
            ],
        ];
    }

    /**
     * Create a validation result.
     *
     * @param string $summary Summary.
     * @return validation_result
     */
    private function new_result(string $summary): validation_result {
        $result = new validation_result();
        $result->status = self::STATUS_COMPLETED;
        $result->ok = true;
        $result->haserrors = false;
        $result->haswarnings = false;
        $result->summary = $summary;
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
        ];

        return $result;
    }

    /**
     * Add a message to a validation result.
     *
     * @param validation_result $result Result object.
     * @param string $severity Severity.
     * @param string $targetkey Target key.
     * @param string $message Message.
     * @param array<string, mixed> $metadata Metadata.
     */
    private function add_message(
        validation_result $result,
        string $severity,
        string $targetkey,
        string $message,
        array $metadata = []
    ): void {
        $result->messages[] = [
            'severity' => $severity,
            'component' => self::COMPONENT,
            'preset' => self::PRESET,
            'targettype' => self::TARGET_TYPE,
            'targetkey' => $targetkey,
            'message' => $message,
            'metadata' => $metadata,
        ];

        if (in_array($severity, [self::SEVERITY_ERROR, self::SEVERITY_BLOCKER], true)) {
            $result->haserrors = true;
            $this->increment($result, 'errors');
        }

        if ($severity === self::SEVERITY_WARNING) {
            $result->haswarnings = true;
            $this->increment($result, 'warnings');
        }
    }

    /**
     * Increment a result count.
     *
     * @param validation_result $result Result object.
     * @param string $key Count key.
     */
    private function increment(validation_result $result, string $key): void {
        if (!isset($result->counts[$key])) {
            $result->counts[$key] = 0;
        }

        $result->counts[$key]++;
    }

    /**
     * Finalise result status flags.
     *
     * @param validation_result $result Result object.
     */
    private function finish_result(validation_result $result): void {
        $result->ok = !$result->haserrors;

        if ($result->haserrors) {
            $result->status = self::STATUS_FAILED;
        } else if ($result->haswarnings) {
            $result->status = self::STATUS_WARNING;
        } else {
            $result->status = self::STATUS_COMPLETED;
        }
    }
}
```

Add these strings to `admin/tool/uckkseed/lang/en/tool_uckkseed.php`:

```php id="rn6m6c"
$string['cohortseedalreadyabsent'] = 'Cohort is already absent: {$a}';
$string['cohortseedcategorymissing'] = 'Course category for cohort context is missing: {$a}';
$string['cohortseedcomponentmissing'] = 'Related component for cohort metadata is not installed: {$a}';
$string['cohortseedcontextunresolved'] = 'The cohort context could not be resolved.';
$string['cohortseedcreated'] = 'Cohort created: {$a}';
$string['cohortseedduplicateidnumber'] = 'Duplicate cohort idnumber: {$a}';
$string['cohortseedduplicatekey'] = 'Duplicate cohort key: {$a}';
$string['cohortseedidnumberprefixwarning'] = 'Cohort idnumber does not use the recommended prefix {$a}.';
$string['cohortseedinvalidcontext'] = 'Cohort context must be system or course_category.';
$string['cohortseedinvalidsymbolicrole'] = 'Invalid symbolic role reference in cohort metadata: {$a}';
$string['cohortseedinvalidtechnicalrole'] = 'Invalid technical role reference in cohort metadata: {$a}';
$string['cohortseedmissingcategory'] = 'Course-category cohorts must include a category key or idnumber.';
$string['cohortseedmissingidnumber'] = 'Cohort definition is missing an idnumber.';
$string['cohortseedmissingkey'] = 'Cohort definition is missing a key.';
$string['cohortseedmissingname'] = 'Cohort definition is missing a name.';
$string['cohortseednotmanagedskip'] = 'Cohort is not marked as seed-managed and was skipped: {$a}';
$string['cohortseedremoved'] = 'Cohort removed: {$a}';
$string['cohortseedsymbolicnotrolewarning'] = 'Symbolic distinction cohorts should explicitly set metadata.not_moodle_role to true.';
$string['cohortseedunknownprogram'] = 'Unknown UCKK program reference in cohort metadata: {$a}';
$string['cohortseedupdated'] = 'Cohort updated: {$a}';
$string['cohortseedwouldcreate'] = 'Cohort would be created: {$a}';
$string['cohortseedwouldremove'] = 'Cohort would be removed: {$a}';
$string['cohortseedwouldupdate'] = 'Cohort would be updated: {$a}';
$string['cohortsdryruncomplete'] = 'Cohort seed dry run completed.';
$string['cohortsresetcomplete'] = 'Seed-managed cohorts reset.';
$string['cohortsresetdryruncomplete'] = 'Cohort reset dry run completed.';
$string['cohortsseeded'] = 'Cohorts seeded.';
$string['cohortsvalidated'] = 'Cohort presets validated.';
```

Add these strings to `admin/tool/uckkseed/lang/fr/tool_uckkseed.php`:

```php id="vwccnm"
$string['cohortseedalreadyabsent'] = 'La cohorte est déjà absente : {$a}';
$string['cohortseedcategorymissing'] = 'La catégorie de cours pour le contexte de cohorte est manquante : {$a}';
$string['cohortseedcomponentmissing'] = 'Le composant lié dans les métadonnées de cohorte n’est pas installé : {$a}';
$string['cohortseedcontextunresolved'] = 'Le contexte de cohorte n’a pas pu être résolu.';
$string['cohortseedcreated'] = 'Cohorte créée : {$a}';
$string['cohortseedduplicateidnumber'] = 'Identifiant de cohorte en double : {$a}';
$string['cohortseedduplicatekey'] = 'Clé de cohorte en double : {$a}';
$string['cohortseedidnumberprefixwarning'] = 'L’identifiant de cohorte n’utilise pas le préfixe recommandé {$a}.';
$string['cohortseedinvalidcontext'] = 'Le contexte de cohorte doit être system ou course_category.';
$string['cohortseedinvalidsymbolicrole'] = 'Référence de rôle symbolique invalide dans les métadonnées de cohorte : {$a}';
$string['cohortseedinvalidtechnicalrole'] = 'Référence de rôle technique invalide dans les métadonnées de cohorte : {$a}';
$string['cohortseedmissingcategory'] = 'Les cohortes de catégorie de cours doivent inclure une clé ou un identifiant de catégorie.';
$string['cohortseedmissingidnumber'] = 'La définition de cohorte n’a pas d’identifiant.';
$string['cohortseedmissingkey'] = 'La définition de cohorte n’a pas de clé.';
$string['cohortseedmissingname'] = 'La définition de cohorte n’a pas de nom.';
$string['cohortseednotmanagedskip'] = 'La cohorte n’est pas marquée comme gérée par la génération et a été ignorée : {$a}';
$string['cohortseedremoved'] = 'Cohorte retirée : {$a}';
$string['cohortseedsymbolicnotrolewarning'] = 'Les cohortes de distinction symbolique devraient définir explicitement metadata.not_moodle_role à true.';
$string['cohortseedunknownprogram'] = 'Référence de programme UCKK inconnue dans les métadonnées de cohorte : {$a}';
$string['cohortseedupdated'] = 'Cohorte mise à jour : {$a}';
$string['cohortseedwouldcreate'] = 'La cohorte serait créée : {$a}';
$string['cohortseedwouldremove'] = 'La cohorte serait retirée : {$a}';
$string['cohortseedwouldupdate'] = 'La cohorte serait mise à jour : {$a}';
$string['cohortsdryruncomplete'] = 'Simulation de génération des cohortes terminée.';
$string['cohortsresetcomplete'] = 'Cohortes gérées par la génération réinitialisées.';
$string['cohortsresetdryruncomplete'] = 'Simulation de réinitialisation des cohortes terminée.';
$string['cohortsseeded'] = 'Cohortes générées.';
$string['cohortsvalidated'] = 'Préréglages de cohortes validés.';

