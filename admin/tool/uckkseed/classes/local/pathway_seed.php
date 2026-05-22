<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

/**
 * Pathway preset seeder for the UCKK seed tool.
 *
 * @package    tool_uckkseed
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace tool_uckkseed\local;

use context_system;
use stdClass;

defined('MOODLE_INTERNAL') || die();

/**
 * Seeds UCKK pathway registry rows owned by local_uckk.
 *
 * This seeder only creates/updates pathway registry definitions in
 * local_uckk_pathway. It does not enrol users, assign Moodle roles, award
 * badges, mark competencies complete, validate archive items, publish assembly
 * decisions, or resolve integrity cases.
 */
final class pathway_seed {
    /** Owning component. */
    public const COMPONENT = 'tool_uckkseed';

    /** Preset id. */
    public const PRESET = 'pathways';

    /** Target type for messages. */
    public const TARGET_TYPE = 'pathway';

    /** Preset schema id. */
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

    /** Pathway table. */
    private const TABLE_PATHWAY = 'local_uckk_pathway';

    /** Program table. */
    private const TABLE_PROGRAM = 'local_uckk_program';

    /** Moodle course table. */
    private const TABLE_COURSE = 'course';

    /** Moodle badge table. */
    private const TABLE_BADGE = 'badge';

    /** Seed manager marker. */
    private const MANAGED_BY = 'tool_uckkseed';

    /** Supported statuses. */
    private const STATUSES = [
        'draft',
        'active',
        'hidden',
        'archived',
        'deleted',
    ];

    /** Supported visibility values. */
    private const VISIBILITIES = [
        'private',
        'course',
        'category',
        'institution',
        'public',
        'hidden',
    ];

    /** Supported pathway type / sequence model values. */
    private const PATHWAY_TYPES = [
        'ordered_courses',
        'cycles',
        'phases',
        'modules',
        'portfolio_based',
        'tronc_commun',
        'baccalaureat',
        'mineure',
        'seminaire',
        'laboratoire',
        'voie_uckk',
        'parcours_transversal',
    ];

    /**
     * Validate pathway preset items.
     *
     * @param array<int, mixed> $items Preset item rows.
     * @param array<string, mixed> $options Runtime options.
     * @return validation_result
     */
    public function validate(array $items, array $options = []): validation_result {
        $result = $this->new_result(self::PRESET . ' preset validated.');

        if (!$this->table_exists(self::TABLE_PATHWAY)) {
            $this->add_message(
                $result,
                self::SEVERITY_BLOCKER,
                '',
                'Required table local_uckk_pathway does not exist.'
            );
        }

        if (!$this->table_exists(self::TABLE_PROGRAM)) {
            $this->add_message(
                $result,
                self::SEVERITY_BLOCKER,
                '',
                'Required table local_uckk_program does not exist.'
            );
        }

        if (empty($items)) {
            $this->add_message($result, self::SEVERITY_WARNING, '', 'Pathway preset is empty.');
            $this->finish_result($result);
            return $result;
        }

        $maps = $this->build_reference_maps($options);
        $seenidnumbers = [];
        $seenprogramshortnames = [];

        foreach ($items as $index => $rawitem) {
            $item = $this->normalise_item($rawitem, $index);
            $targetkey = $item['key'] !== '' ? $item['key'] : 'row_' . ($index + 1);

            if ($item['key'] === '') {
                $this->add_message($result, self::SEVERITY_ERROR, $targetkey, 'Pathway key is required.');
            }

            if ($item['shortname'] === '') {
                $this->add_message($result, self::SEVERITY_ERROR, $targetkey, 'Pathway shortname is required.');
            }

            if ($item['fullname'] === '') {
                $this->add_message($result, self::SEVERITY_ERROR, $targetkey, 'Pathway fullname is required.');
            }

            if ($item['idnumber'] !== '') {
                if (isset($seenidnumbers[$item['idnumber']])) {
                    $this->add_message($result, self::SEVERITY_ERROR, $targetkey, 'Duplicate pathway idnumber: ' . $item['idnumber']);
                }
                $seenidnumbers[$item['idnumber']] = true;
            }

            if ($item['status'] === '' || !in_array($item['status'], self::STATUSES, true)) {
                $this->add_message($result, self::SEVERITY_ERROR, $targetkey, 'Invalid pathway status: ' . $item['status']);
            }

            if ($item['visibility'] === '' || !in_array($item['visibility'], self::VISIBILITIES, true)) {
                $this->add_message($result, self::SEVERITY_WARNING, $targetkey, 'Non-standard pathway visibility: ' . $item['visibility']);
            }

            if ($item['pathwaytype'] === '' || !in_array($item['pathwaytype'], self::PATHWAY_TYPES, true)) {
                $this->add_message($result, self::SEVERITY_WARNING, $targetkey, 'Non-standard pathway type: ' . $item['pathwaytype']);
            }

            $programid = $this->resolve_program_id($item, $maps);
            $programref = $this->program_reference_label($item);

            if ($programid <= 0 && !$this->program_exists_in_preset($item, $maps)) {
                $this->add_message($result, self::SEVERITY_ERROR, $targetkey, 'Unknown program reference: ' . $programref);
            }

            $dedupekey = ($programid > 0 ? 'db:' . $programid : 'ref:' . $programref) . ':' . $item['shortname'];
            if ($item['shortname'] !== '' && isset($seenprogramshortnames[$dedupekey])) {
                $this->add_message($result, self::SEVERITY_ERROR, $targetkey, 'Duplicate pathway shortname within program: ' . $item['shortname']);
            }
            $seenprogramshortnames[$dedupekey] = true;

            foreach ($item['course_refs'] as $courseref) {
                if (!$this->course_reference_exists($courseref, $maps)) {
                    $this->add_message($result, self::SEVERITY_WARNING, $targetkey, 'Unknown course reference: ' . $courseref, [
                        'course' => $courseref,
                    ]);
                }
            }

            foreach ($item['badge_refs'] as $badgeref) {
                if (!$this->badge_reference_exists($badgeref, $maps)) {
                    $this->add_message($result, self::SEVERITY_WARNING, $targetkey, 'Unknown badge reference: ' . $badgeref, [
                        'badge' => $badgeref,
                    ]);
                }
            }

            foreach ($item['competency_refs'] as $competencyref) {
                if (!$this->competency_reference_exists($competencyref, $maps)) {
                    $this->add_message($result, self::SEVERITY_WARNING, $targetkey, 'Unknown competency reference: ' . $competencyref, [
                        'competency' => $competencyref,
                    ]);
                }
            }

            $result->increment('skipped');
        }

        $this->finish_result($result);
        return $result;
    }

    /**
     * Apply pathway preset items.
     *
     * @param array<int, mixed> $items Preset item rows.
     * @param array<string, mixed> $options Runtime options.
     * @return validation_result
     */
    public function apply(array $items, array $options = []): validation_result {
        $mode = $this->normalise_mode((string)($options['mode'] ?? self::MODE_APPLY));
        $dryrun = $mode === self::MODE_DRY_RUN || !empty($options['dryrun']) || !empty($options['dry_run']);
        $rollbackplan = $mode === self::MODE_ROLLBACK_PLAN || !empty($options['rollbackplan']);

        $validation = $this->validate($items, $options);
        if ($validation->has_errors()) {
            return $validation;
        }

        $result = $this->new_result($dryrun || $rollbackplan ? 'Pathway seed dry run complete.' : 'Pathways seeded.');
        $maps = $this->build_reference_maps($options);

        foreach ($items as $index => $rawitem) {
            $item = $this->normalise_item($rawitem, $index);
            $targetkey = $item['key'] !== '' ? $item['key'] : 'row_' . ($index + 1);
            $programid = $this->resolve_program_id($item, $maps);

            if ($programid <= 0) {
                $this->add_message($result, self::SEVERITY_ERROR, $targetkey, 'Cannot apply pathway without an existing local_uckk program record.');
                $result->increment('failed');
                continue;
            }

            $existing = $this->get_existing_pathway($programid, $item['shortname']);
            $record = $this->build_pathway_record($item, $programid, $maps, $existing);

            if ($dryrun || $rollbackplan) {
                $this->add_message(
                    $result,
                    self::SEVERITY_INFO,
                    $targetkey,
                    $existing ? 'Pathway would be updated.' : 'Pathway would be created.',
                    ['record' => $record]
                );
                $result->increment('skipped');
                continue;
            }

            if ($existing) {
                $record->id = (int)$existing->id;
                $this->update_pathway_record($record);
                $this->add_message($result, self::SEVERITY_SUCCESS, $targetkey, 'Pathway updated.', ['id' => $record->id]);
                $result->increment('updated');
            } else {
                $record->id = $this->insert_pathway_record($record);
                $this->add_message($result, self::SEVERITY_SUCCESS, $targetkey, 'Pathway created.', ['id' => $record->id]);
                $result->increment('created');
            }
        }

        $this->finish_result($result);
        return $result;
    }

    /**
     * Reset seed-managed pathway rows.
     *
     * @param array<int, mixed> $items Preset item rows.
     * @param array<string, mixed> $options Runtime options.
     * @return validation_result
     */
    public function reset(array $items, array $options = []): validation_result {
        global $DB;

        $mode = $this->normalise_mode((string)($options['mode'] ?? self::MODE_DRY_RUN));
        $dryrun = $mode === self::MODE_DRY_RUN || !empty($options['dryrun']) || !empty($options['dry_run']);
        $confirmed = !empty($options['confirm']) || !empty($options['force']);

        $result = $this->new_result($dryrun ? 'Pathway reset dry run complete.' : 'Pathway reset complete.');

        if (!$this->table_exists(self::TABLE_PATHWAY)) {
            $this->add_message($result, self::SEVERITY_BLOCKER, '', 'Required table local_uckk_pathway does not exist.');
            $this->finish_result($result);
            return $result;
        }

        if (!$dryrun && !$confirmed) {
            $this->add_message($result, self::SEVERITY_BLOCKER, '', 'Confirmation is required before resetting pathways.');
            $this->finish_result($result);
            return $result;
        }

        $keys = [];
        foreach ($items as $index => $rawitem) {
            $item = $this->normalise_item($rawitem, $index);
            if ($item['shortname'] !== '') {
                $keys[$item['shortname']] = true;
            }
        }

        $records = [];
        if (!empty($keys)) {
            [$insql, $params] = $DB->get_in_or_equal(array_keys($keys), SQL_PARAMS_NAMED, 'sn');
            $records = $DB->get_records_select(self::TABLE_PATHWAY, 'shortname ' . $insql, $params);
        } else {
            $records = $this->get_seed_managed_pathways();
        }

        foreach ($records as $record) {
            $targetkey = (string)$record->shortname;

            if (!$this->is_seed_managed($record) && empty($keys[$targetkey])) {
                $this->add_message($result, self::SEVERITY_WARNING, $targetkey, 'Pathway is not seed-managed; skipped.');
                $result->increment('skipped');
                continue;
            }

            if ($dryrun) {
                $this->add_message($result, self::SEVERITY_INFO, $targetkey, 'Pathway would be deleted.', ['id' => (int)$record->id]);
                $result->increment('skipped');
                continue;
            }

            $DB->delete_records(self::TABLE_PATHWAY, ['id' => (int)$record->id]);
            $this->add_message($result, self::SEVERITY_SUCCESS, $targetkey, 'Pathway deleted.', ['id' => (int)$record->id]);
            $result->increment('updated');
        }

        $this->finish_result($result);
        return $result;
    }

    /**
     * Export current seed-managed pathways or a minimal empty preset.
     *
     * @param array<string, mixed> $options Runtime options.
     * @return array<string, mixed>
     */
    public function export(array $options = []): array {
        $items = [];

        if ($this->table_exists(self::TABLE_PATHWAY)) {
            foreach ($this->get_seed_managed_pathways() as $record) {
                $items[] = $this->record_to_item($record);
            }
        }

        return [
            'schema' => self::SCHEMA,
            'component' => self::COMPONENT,
            'preset' => self::PRESET,
            'version' => 2026051200,
            'items' => $items,
        ];
    }

    /**
     * Normalise one pathway preset item.
     *
     * @param mixed $rawitem Raw preset row.
     * @param int $index Row index.
     * @return array<string, mixed>
     */
    private function normalise_item(mixed $rawitem, int $index = 0): array {
        if ($rawitem instanceof stdClass) {
            $rawitem = (array)$rawitem;
        }

        if (!is_array($rawitem)) {
            $rawitem = [];
        }

        $metadata = $this->normalise_assoc($rawitem['metadata'] ?? []);
        $metadata['managedby'] = self::MANAGED_BY;
        $metadata['sourcepreset'] = self::PRESET;

        foreach ([
            'id',
            'idnumber',
            'program_id',
            'program_code',
            'sequence_model',
            'prerequisite_pathway_refs',
            'cycles',
            'completion_rule',
            'evidence_requirements',
            'source',
        ] as $field) {
            if (array_key_exists($field, $rawitem) && !array_key_exists($field, $metadata)) {
                $metadata[$field] = $rawitem[$field];
            }
        }

        $shortname = $this->normalise_key((string)($rawitem['shortname'] ?? $rawitem['key'] ?? $rawitem['idnumber'] ?? ''));
        $key = $this->normalise_key((string)($rawitem['key'] ?? $shortname));
        $fullname = trim((string)($rawitem['fullname'] ?? $rawitem['name'] ?? $rawitem['title'] ?? $shortname));
        $pathwaytype = $this->normalise_key((string)($rawitem['pathway_type'] ?? $rawitem['sequence_model'] ?? 'ordered_courses'));

        return [
            'key' => $key,
            'id' => trim((string)($rawitem['id'] ?? '')),
            'idnumber' => trim((string)($rawitem['idnumber'] ?? '')),
            'shortname' => $shortname,
            'fullname' => $fullname,
            'program_id' => trim((string)($rawitem['program_id'] ?? '')),
            'program_code' => trim((string)($rawitem['program_code'] ?? '')),
            'pathwaytype' => $pathwaytype,
            'description' => (string)($rawitem['description'] ?? $rawitem['summary'] ?? ''),
            'descriptionformat' => (int)($rawitem['descriptionformat'] ?? FORMAT_HTML),
            'status' => $this->normalise_status((string)($rawitem['status'] ?? 'active')),
            'visibility' => $this->normalise_visibility((string)($rawitem['visibility'] ?? 'institution')),
            'sortorder' => (int)($rawitem['sortorder'] ?? (($index + 1) * 10)),
            'course_refs' => $this->flatten_course_refs($rawitem['cycles'] ?? [], $rawitem['course_refs'] ?? []),
            'badge_refs' => $this->normalise_string_array($rawitem['badge_refs'] ?? $rawitem['badges'] ?? []),
            'competency_refs' => $this->normalise_string_array($rawitem['competency_refs'] ?? $rawitem['competencies'] ?? []),
            'cycles' => $this->normalise_array($rawitem['cycles'] ?? []),
            'completion_rule' => $this->normalise_assoc($rawitem['completion_rule'] ?? []),
            'evidence_requirements' => $this->normalise_array($rawitem['evidence_requirements'] ?? []),
            'metadata' => $metadata,
        ];
    }

    /**
     * Build database record for insert/update.
     *
     * @param array<string, mixed> $item Normalised item.
     * @param int $programid Program id.
     * @param array<string, mixed> $maps Reference maps.
     * @param stdClass|null $existing Existing pathway.
     * @return stdClass
     */
    private function build_pathway_record(array $item, int $programid, array $maps, ?stdClass $existing = null): stdClass {
        global $USER;

        $now = time();
        $requiredcourseids = [];

        foreach ($item['course_refs'] as $courseref) {
            $courseid = $this->resolve_course_id($courseref, $maps);
            if ($courseid > 0) {
                $requiredcourseids[] = $courseid;
            }
        }

        $metadata = $item['metadata'];
        $metadata['cycles'] = $item['cycles'];
        $metadata['completion_rule'] = $item['completion_rule'];
        $metadata['evidence_requirements'] = $item['evidence_requirements'];
        $metadata['course_refs'] = $item['course_refs'];
        $metadata['badge_refs'] = $item['badge_refs'];
        $metadata['competency_refs'] = $item['competency_refs'];

        $record = new stdClass();
        $record->programid = $programid;
        $record->shortname = $item['shortname'];
        $record->fullname = $item['fullname'];
        $record->pathwaytype = $item['pathwaytype'];
        $record->requiredcourseids = $this->encode_json(array_values(array_unique($requiredcourseids)));
        $record->requiredbadges = $this->encode_json($item['badge_refs']);
        $record->requiredcompetencies = $this->encode_json($item['competency_refs']);
        $record->description = $item['description'];
        $record->descriptionformat = $item['descriptionformat'];
        $record->sortorder = $item['sortorder'];
        $record->contextid = (int)($existing->contextid ?? context_system::instance()->id);
        $record->createdby = (int)($existing->createdby ?? $USER->id ?? 0);
        $record->modifiedby = (int)($USER->id ?? 0);
        $record->timecreated = (int)($existing->timecreated ?? $now);
        $record->timemodified = $now;
        $record->status = $item['status'];
        $record->visibility = $item['visibility'];
        $record->versionno = (int)($existing->versionno ?? 0) + 1;
        $record->provenancehash = sha1($this->encode_json([
            'programid' => $programid,
            'shortname' => $item['shortname'],
            'item' => $item,
        ]));
        $record->metadata = $this->encode_json($metadata);

        return $record;
    }

    /**
     * Insert pathway record with existing-column protection.
     *
     * @param stdClass $record Record.
     * @return int New id.
     */
    private function insert_pathway_record(stdClass $record): int {
        global $DB;

        $filtered = $this->filter_record_fields(self::TABLE_PATHWAY, $record);
        return (int)$DB->insert_record(self::TABLE_PATHWAY, $filtered);
    }

    /**
     * Update pathway record with existing-column protection.
     *
     * @param stdClass $record Record.
     */
    private function update_pathway_record(stdClass $record): void {
        global $DB;

        $filtered = $this->filter_record_fields(self::TABLE_PATHWAY, $record);
        $DB->update_record(self::TABLE_PATHWAY, $filtered);
    }

    /**
     * Get existing pathway by program and shortname.
     *
     * @param int $programid Program id.
     * @param string $shortname Shortname.
     * @return stdClass|null
     */
    private function get_existing_pathway(int $programid, string $shortname): ?stdClass {
        global $DB;

        if ($programid <= 0 || $shortname === '' || !$this->table_exists(self::TABLE_PATHWAY)) {
            return null;
        }

        return $DB->get_record(self::TABLE_PATHWAY, [
            'programid' => $programid,
            'shortname' => $shortname,
        ], '*', IGNORE_MISSING) ?: null;
    }

    /**
     * Get seed-managed pathways.
     *
     * @return array<int, stdClass>
     */
    private function get_seed_managed_pathways(): array {
        global $DB;

        if (!$this->table_exists(self::TABLE_PATHWAY)) {
            return [];
        }

        $records = $DB->get_records(self::TABLE_PATHWAY, null, 'programid ASC, sortorder ASC, shortname ASC');
        return array_values(array_filter($records, fn(stdClass $record): bool => $this->is_seed_managed($record)));
    }

    /**
     * Whether a record is seed-managed.
     *
     * @param stdClass $record Pathway record.
     * @return bool
     */
    private function is_seed_managed(stdClass $record): bool {
        $metadata = $this->decode_json((string)($record->metadata ?? ''));
        return ($metadata['managedby'] ?? '') === self::MANAGED_BY
            || ($metadata['sourcepreset'] ?? '') === self::PRESET;
    }

    /**
     * Convert DB record to preset item.
     *
     * @param stdClass $record DB record.
     * @return array<string, mixed>
     */
    private function record_to_item(stdClass $record): array {
        $metadata = $this->decode_json((string)($record->metadata ?? ''));

        return [
            'key' => (string)$record->shortname,
            'id' => (string)($metadata['id'] ?? ''),
            'idnumber' => (string)($metadata['idnumber'] ?? ''),
            'shortname' => (string)$record->shortname,
            'fullname' => (string)$record->fullname,
            'program_id' => (string)($metadata['program_id'] ?? ''),
            'program_code' => (string)($metadata['program_code'] ?? ''),
            'pathway_type' => (string)$record->pathwaytype,
            'sequence_model' => (string)($metadata['sequence_model'] ?? $record->pathwaytype),
            'status' => (string)$record->status,
            'visibility' => (string)$record->visibility,
            'sortorder' => (int)$record->sortorder,
            'cycles' => $metadata['cycles'] ?? [],
            'completion_rule' => $metadata['completion_rule'] ?? [],
            'competency_refs' => $metadata['competency_refs'] ?? $this->decode_json((string)($record->requiredcompetencies ?? '')),
            'badge_refs' => $metadata['badge_refs'] ?? $this->decode_json((string)($record->requiredbadges ?? '')),
            'evidence_requirements' => $metadata['evidence_requirements'] ?? [],
            'description' => (string)($record->description ?? ''),
            'metadata' => $metadata,
        ];
    }

    /**
     * Build reference maps from DB and sibling JSON presets.
     *
     * @param array<string, mixed> $options Runtime options.
     * @return array<string, mixed>
     */
    private function build_reference_maps(array $options): array {
        return [
            'programs' => $this->build_program_map($options),
            'courses' => $this->build_course_map($options),
            'badges' => $this->build_badge_map($options),
            'competencies' => $this->build_competency_map($options),
        ];
    }

    /**
     * Build program reference map.
     *
     * @param array<string, mixed> $options Runtime options.
     * @return array<string, mixed>
     */
    private function build_program_map(array $options): array {
        global $DB;

        $map = ['db' => [], 'preset' => []];

        if ($this->table_exists(self::TABLE_PROGRAM)) {
            foreach ($DB->get_records(self::TABLE_PROGRAM) as $program) {
                foreach (['id', 'shortname'] as $field) {
                    if (!empty($program->{$field})) {
                        $map['db'][(string)$program->{$field}] = (int)$program->id;
                    }
                }
            }
        }

        foreach ($this->read_preset_items($options, 'programs') as $program) {
            if ($program instanceof stdClass) {
                $program = (array)$program;
            }
            if (!is_array($program)) {
                continue;
            }
            $refs = [
                $program['id'] ?? null,
                $program['key'] ?? null,
                $program['shortname'] ?? null,
                $program['idnumber'] ?? null,
                $program['code'] ?? null,
            ];
            foreach ($refs as $ref) {
                if ($ref !== null && trim((string)$ref) !== '') {
                    $map['preset'][(string)$ref] = true;
                }
            }
        }

        return $map;
    }

    /**
     * Build course reference map.
     *
     * @param array<string, mixed> $options Runtime options.
     * @return array<string, mixed>
     */
    private function build_course_map(array $options): array {
        global $DB;

        $map = ['db' => [], 'preset' => []];

        if ($this->table_exists(self::TABLE_COURSE)) {
            foreach ($DB->get_records(self::TABLE_COURSE, null, '', 'id, shortname, idnumber') as $course) {
                $map['db'][(string)$course->id] = (int)$course->id;
                if (!empty($course->shortname)) {
                    $map['db'][(string)$course->shortname] = (int)$course->id;
                }
                if (!empty($course->idnumber)) {
                    $map['db'][(string)$course->idnumber] = (int)$course->id;
                }
            }
        }

        foreach ($this->read_preset_items($options, 'courses') as $course) {
            if ($course instanceof stdClass) {
                $course = (array)$course;
            }
            if (!is_array($course)) {
                continue;
            }
            foreach (['id', 'key', 'code', 'shortname', 'idnumber'] as $field) {
                if (!empty($course[$field])) {
                    $map['preset'][(string)$course[$field]] = true;
                }
            }
            if (!empty($course['moodle']) && is_array($course['moodle'])) {
                foreach (['shortname', 'idnumber'] as $field) {
                    if (!empty($course['moodle'][$field])) {
                        $map['preset'][(string)$course['moodle'][$field]] = true;
                    }
                }
            }
        }

        return $map;
    }

/**
 * Build badge reference map.
 *
 * Moodle badge table schemas differ between versions. Do not assume
 * non-core/generated columns such as uniquehash or idnumber exist.
 *
 * @param array<string, mixed> $options Runtime options.
 * @return array<string, bool>
 */
private function build_badge_map(array $options): array {
    global $DB;

    $map = [];

    if ($this->table_exists(self::TABLE_BADGE)) {
        $columns = $DB->get_columns(self::TABLE_BADGE);
        $fields = [];

        foreach (['id', 'name', 'uniquehash', 'idnumber'] as $field) {
            if (array_key_exists($field, $columns)) {
                $fields[] = $field;
            }
        }

        if (!empty($fields)) {
            foreach ($DB->get_records(self::TABLE_BADGE, null, '', implode(', ', $fields)) as $badge) {
                foreach (['id', 'name', 'uniquehash', 'idnumber'] as $field) {
                    if (property_exists($badge, $field) && !empty($badge->{$field})) {
                        $map[(string)$badge->{$field}] = true;
                    }
                }
            }
        }
    }

    foreach ($this->read_preset_items($options, 'badges') as $badge) {
        if ($badge instanceof stdClass) {
            $badge = (array)$badge;
        }
        if (!is_array($badge)) {
            continue;
        }
        foreach (['id', 'key', 'idnumber', 'shortname', 'name'] as $field) {
            if (!empty($badge[$field])) {
                $map[(string)$badge[$field]] = true;
            }
        }
    }

    return $map;
}

    /**
     * Build competency reference map.
     *
     * @param array<string, mixed> $options Runtime options.
     * @return array<string, bool>
     */
    private function build_competency_map(array $options): array {
        $map = [];

        foreach ($this->read_preset_items($options, 'competencies') as $competency) {
            if ($competency instanceof stdClass) {
                $competency = (array)$competency;
            }
            if (!is_array($competency)) {
                continue;
            }
            foreach (['id', 'key', 'idnumber', 'shortname'] as $field) {
                if (!empty($competency[$field])) {
                    $map[(string)$competency[$field]] = true;
                }
            }
        }

        return $map;
    }

    /**
     * Resolve program id for pathway item.
     *
     * @param array<string, mixed> $item Normalised item.
     * @param array<string, mixed> $maps Reference maps.
     * @return int Program id, or 0 when not in DB.
     */
    private function resolve_program_id(array $item, array $maps): int {
        foreach ([$item['program_id'], $item['program_code']] as $ref) {
            if ($ref !== '' && !empty($maps['programs']['db'][$ref])) {
                return (int)$maps['programs']['db'][$ref];
            }
        }

        return 0;
    }

    /**
     * Whether program exists in JSON preset.
     *
     * @param array<string, mixed> $item Normalised item.
     * @param array<string, mixed> $maps Reference maps.
     * @return bool
     */
    private function program_exists_in_preset(array $item, array $maps): bool {
        foreach ([$item['program_id'], $item['program_code']] as $ref) {
            if ($ref !== '' && !empty($maps['programs']['preset'][$ref])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Resolve course id for storage.
     *
     * @param string $ref Course reference.
     * @param array<string, mixed> $maps Reference maps.
     * @return int Course id, or 0 when not in DB.
     */
    private function resolve_course_id(string $ref, array $maps): int {
        return !empty($maps['courses']['db'][$ref]) ? (int)$maps['courses']['db'][$ref] : 0;
    }

    /**
     * Whether course reference exists in DB or preset.
     *
     * @param string $ref Course reference.
     * @param array<string, mixed> $maps Reference maps.
     * @return bool
     */
    private function course_reference_exists(string $ref, array $maps): bool {
        return $ref === '' || !empty($maps['courses']['db'][$ref]) || !empty($maps['courses']['preset'][$ref]);
    }

    /**
     * Whether badge reference exists in DB or preset.
     *
     * @param string $ref Badge reference.
     * @param array<string, mixed> $maps Reference maps.
     * @return bool
     */
    private function badge_reference_exists(string $ref, array $maps): bool {
        return $ref === '' || !empty($maps['badges'][$ref]);
    }

    /**
     * Whether competency reference exists in preset or uses final idnumber syntax.
     *
     * @param string $ref Competency reference.
     * @param array<string, mixed> $maps Reference maps.
     * @return bool
     */
    private function competency_reference_exists(string $ref, array $maps): bool {
        return $ref === ''
            || !empty($maps['competencies'][$ref])
            || preg_match('/^UCKK-COMP-[A-Z0-9-]+$/', $ref) === 1
            || preg_match('/^competency:[a-z0-9:_-]+$/', strtolower($ref)) === 1;
    }

    /**
     * Get display label for program reference.
     *
     * @param array<string, mixed> $item Normalised item.
     * @return string
     */
    private function program_reference_label(array $item): string {
        return $item['program_id'] !== '' ? $item['program_id'] : $item['program_code'];
    }

    /**
     * Flatten cycle course refs.
     *
     * @param mixed $cycles Cycles payload.
     * @param mixed $fallback Direct course refs.
     * @return string[]
     */
    private function flatten_course_refs(mixed $cycles, mixed $fallback = []): array {
        $refs = $this->normalise_string_array($fallback);

        if ($cycles instanceof stdClass) {
            $cycles = (array)$cycles;
        }

        if (!is_array($cycles)) {
            return array_values(array_unique($refs));
        }

        foreach ($cycles as $cycle) {
            if ($cycle instanceof stdClass) {
                $cycle = (array)$cycle;
            }
            if (!is_array($cycle)) {
                continue;
            }
            $courserefs = $cycle['course_refs'] ?? $cycle['courses'] ?? [];
            if ($courserefs instanceof stdClass) {
                $courserefs = (array)$courserefs;
            }
            if (!is_array($courserefs)) {
                $courserefs = [$courserefs];
            }
            foreach ($courserefs as $courseref) {
                if ($courseref instanceof stdClass) {
                    $courseref = (array)$courseref;
                }
                if (is_array($courseref)) {
                    $ref = (string)($courseref['course_code'] ?? $courseref['course_id'] ?? $courseref['idnumber'] ?? $courseref['shortname'] ?? '');
                } else {
                    $ref = (string)$courseref;
                }
                $ref = trim($ref);
                if ($ref !== '') {
                    $refs[] = $ref;
                }
            }
        }

        return array_values(array_unique($refs));
    }

    /**
     * Read sibling preset items.
     *
     * @param array<string, mixed> $options Runtime options.
     * @param string $preset Preset id.
     * @return array<int, mixed>
     */
    private function read_preset_items(array $options, string $preset): array {
        $presetpath = $this->resolve_preset_path($options);
        $file = $presetpath . DIRECTORY_SEPARATOR . $preset . '.json';

        if (!is_readable($file)) {
            return [];
        }

        $raw = file_get_contents($file);
        if ($raw === false) {
            return [];
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return [];
        }

        $items = $decoded['items'] ?? [];
        return is_array($items) ? $items : [];
    }

    /**
     * Resolve preset directory path.
     *
     * @param array<string, mixed> $options Runtime options.
     * @return string
     */
    private function resolve_preset_path(array $options): string {
        global $CFG;

        $path = trim((string)($options['presetpath'] ?? get_config(self::COMPONENT, 'presetpath') ?: 'academic_registry_json'));

        if ($path === '') {
            $path = 'academic_registry_json';
        }

        if ($this->is_absolute_path($path)) {
            return rtrim($path, DIRECTORY_SEPARATOR . '/\\');
        }

        return rtrim($CFG->dirroot . DIRECTORY_SEPARATOR . $path, DIRECTORY_SEPARATOR . '/\\');
    }

    /**
     * Whether path is absolute.
     *
     * @param string $path Path.
     * @return bool
     */
    private function is_absolute_path(string $path): bool {
        return str_starts_with($path, DIRECTORY_SEPARATOR)
            || str_starts_with($path, '/')
            || str_starts_with($path, '\\')
            || preg_match('/^[A-Za-z]:[\/\\\\]/', $path) === 1;
    }

    /**
     * Normalise key.
     *
     * @param string $value Raw value.
     * @return string
     */
    private function normalise_key(string $value): string {
        $value = strtolower(trim($value));
        $value = str_replace(['-', ' ', ':'], '_', $value);
        $value = preg_replace('/[^a-z0-9_]+/', '_', $value) ?? $value;
        $value = preg_replace('/_+/', '_', $value) ?? $value;
        return trim($value, '_');
    }

    /**
     * Normalise status.
     *
     * @param string $status Raw status.
     * @return string
     */
    private function normalise_status(string $status): string {
        $status = $this->normalise_key($status);
        return in_array($status, self::STATUSES, true) ? $status : 'active';
    }

    /**
     * Normalise visibility.
     *
     * @param string $visibility Raw visibility.
     * @return string
     */
    private function normalise_visibility(string $visibility): string {
        $visibility = $this->normalise_key($visibility);
        return $visibility !== '' ? $visibility : 'institution';
    }

    /**
     * Normalise mode.
     *
     * @param string $mode Raw mode.
     * @return string
     */
    private function normalise_mode(string $mode): string {
        $mode = clean_param($mode, PARAM_ALPHANUMEXT);
        $allowed = [self::MODE_APPLY, self::MODE_DRY_RUN, self::MODE_REPORT, self::MODE_ROLLBACK_PLAN];
        return in_array($mode, $allowed, true) ? $mode : self::MODE_DRY_RUN;
    }

    /**
     * Normalise associative array.
     *
     * @param mixed $value Raw value.
     * @return array<string, mixed>
     */
    private function normalise_assoc(mixed $value): array {
        if ($value instanceof stdClass) {
            $value = (array)$value;
        }
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            return is_array($decoded) ? $decoded : [];
        }
        return is_array($value) ? $value : [];
    }

    /**
     * Normalise array.
     *
     * @param mixed $value Raw value.
     * @return array<int, mixed>
     */
    private function normalise_array(mixed $value): array {
        if ($value instanceof stdClass) {
            $value = (array)$value;
        }
        if ($value === null || $value === '') {
            return [];
        }
        if (!is_array($value)) {
            return [$value];
        }
        return array_values($value);
    }

    /**
     * Normalise string array.
     *
     * @param mixed $value Raw value.
     * @return string[]
     */
    private function normalise_string_array(mixed $value): array {
        $items = $this->normalise_array($value);
        $strings = [];

        foreach ($items as $item) {
            if ($item instanceof stdClass) {
                $item = (array)$item;
            }
            if (is_array($item)) {
                $item = $item['id'] ?? $item['key'] ?? $item['idnumber'] ?? $item['shortname'] ?? $item['course_code'] ?? $item['course_id'] ?? '';
            }
            $item = trim((string)$item);
            if ($item !== '') {
                $strings[] = $item;
            }
        }

        return array_values(array_unique($strings));
    }

    /**
     * Encode JSON payload.
     *
     * @param mixed $value Value.
     * @return string
     */
    private function encode_json(mixed $value): string {
        $json = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        return $json === false ? '[]' : $json;
    }

    /**
     * Decode JSON payload.
     *
     * @param string $value Raw JSON.
     * @return array<string, mixed>|array<int, mixed>
     */
    private function decode_json(string $value): array {
        $decoded = json_decode($value, true);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Check table exists.
     *
     * @param string $table Table name.
     * @return bool
     */
    private function table_exists(string $table): bool {
        global $DB;
        return $DB->get_manager()->table_exists($table);
    }

    /**
     * Filter record fields to existing table columns.
     *
     * @param string $table Table name.
     * @param stdClass $record Record.
     * @return stdClass
     */
    private function filter_record_fields(string $table, stdClass $record): stdClass {
        global $DB;

        $columns = $DB->get_columns($table);
        $filtered = new stdClass();

        foreach ((array)$record as $field => $value) {
            if (array_key_exists($field, $columns)) {
                $filtered->{$field} = $value;
            }
        }

        return $filtered;
    }

    /**
     * Create result.
     *
     * @param string $summary Summary.
     * @return validation_result
     */
    private function new_result(string $summary): validation_result {
        return new validation_result(self::STATUS_COMPLETED, $summary, [
            'component' => self::COMPONENT,
            'preset' => self::PRESET,
            'targettype' => self::TARGET_TYPE,
        ]);
    }

    /**
     * Add message.
     *
     * @param validation_result $result Result.
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
     * Finalise result status.
     *
     * @param validation_result $result Result.
     */
    private function finish_result(validation_result $result): void {
        if ($result->has_errors()) {
            $result->set_status(self::STATUS_FAILED);
        } else if ($result->has_warnings()) {
            $result->set_status(self::STATUS_WARNING);
        } else {
            $result->set_status(self::STATUS_COMPLETED);
        }
    }
}