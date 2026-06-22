<?php
// This file is part of UCKK-Moodle.
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.
//
// UCKK-Moodle adapts Moodle as the pedagogical campus of the
// Univers-Cité King Klown.

/**
 * Faculty-to-Moodle mapper for local_uckk.
 *
 * This class prepares deterministic Moodle mappings from:
 * - faculty profile JSON;
 * - Atlas voie JSON;
 * - faculty manifest / registry metadata.
 *
 * It maps:
 * - faculty Moodle category;
 * - faculty hub course;
 * - Atlas conceptual courses;
 * - course idnumber / shortname / fullname;
 * - custom fields derived from Atlas public course fields;
 * - dry-run sync comparisons against existing Moodle records.
 *
 * It does not:
 * - create categories;
 * - create courses;
 * - update Moodle records;
 * - enrol users;
 * - change visibility;
 * - create custom fields;
 * - expose private notes, grades, completion, participants or progress.
 *
 * @package    local_uckk
 * @copyright  2026 Univers-Cité King Klown
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace local_uckk\local\faculty;

use coding_exception;
use moodle_database;
use moodle_url;
use stdClass;

defined('MOODLE_INTERNAL') || die();

/**
 * Maps faculty and Atlas data to Moodle-native category/course structures.
 */
final class faculty_moodle_mapper {
    /** Moodle component name. */
    public const COMPONENT = 'local_uckk';

    /** Default Moodle course format for mapped courses. */
    public const DEFAULT_COURSE_FORMAT = 'uckk';

    /** Default category parent id when a category does not yet exist. */
    public const DEFAULT_CATEGORY_PARENT = 0;

    /** Dry-run action: no change. */
    public const ACTION_NO_CHANGE = 'no_change';

    /** Dry-run action: create. */
    public const ACTION_CREATE = 'create';

    /** Dry-run action: update. */
    public const ACTION_UPDATE = 'update';

    /** Dry-run action: warning. */
    public const ACTION_WARNING = 'warning';

    /** Dry-run action: error. */
    public const ACTION_ERROR = 'error';

    /** Moodle custom field for Atlas concept id. */
    public const CUSTOMFIELD_CONCEPT_MAITRE_ID = 'uckk_concept_maitre_id';

    /** Moodle custom field for Atlas concept name. */
    public const CUSTOMFIELD_CONCEPT_MAITRE_NOM = 'uckk_concept_maitre_nom';

    /** Moodle custom field for Atlas artifact type. */
    public const CUSTOMFIELD_ARTEFACT_TYPE = 'uckk_artefact_type';

    /** Moodle custom field for Atlas artifact name. */
    public const CUSTOMFIELD_ARTEFACT_NOM = 'uckk_artefact_nom';

    /** Moodle custom field for Atlas voie id. */
    public const CUSTOMFIELD_VOIE_ID = 'uckk_voie_id';

    /** Moodle custom field for Faculty id. */
    public const CUSTOMFIELD_FACULTY_ID = 'uckk_faculty_id';

    /** Moodle custom field for public faculty slug. */
    public const CUSTOMFIELD_FACULTY_SLUG = 'uckk_faculty_slug';

    /**
     * Optional Moodle database handle.
     *
     * @var moodle_database|null
     */
    private ?moodle_database $db;

    /**
     * Constructor.
     *
     * @param moodle_database|null $db Optional DB handle for read-only lookup, useful for tests.
     */
    public function __construct(?moodle_database $db = null) {
        global $DB;

        $this->db = $db ?? ($DB instanceof moodle_database ? $DB : null);
    }

    /**
     * Build a complete read-only Moodle mapping for one faculty.
     *
     * @param array<string, mixed> $faculty Normalized faculty profile.
     * @param array<string, mixed> $atlas Decoded Atlas voie.
     * @param array<string, mixed>|null $registryrecord Optional registry record.
     * @return array<string, mixed>
     */
    public function map(array $faculty, array $atlas, ?array $registryrecord = null): array {
        $this->assert_identity_consistency($faculty, $atlas, $registryrecord);

        $category = $this->map_category($faculty, $atlas, $registryrecord);
        $hubcourse = $this->map_hub_course($faculty, $atlas, $category, $registryrecord);
        $courses = $this->map_courses($faculty, $atlas, $category, $registryrecord);

        return [
            'component' => self::COMPONENT,
            'faculty_id' => $this->string_value($faculty, 'faculty_id'),
            'voie_id' => $this->string_value($faculty, 'voie_id'),
            'slug' => $this->string_value($faculty, 'slug'),
            'course_prefix' => $this->string_value($faculty['moodle'] ?? [], 'course_prefix'),
            'category' => $category,
            'hub_course' => $hubcourse,
            'courses' => $courses,
            'expected_course_idnumbers' => $this->get_expected_course_idnumbers($faculty, $atlas),
            'custom_fields' => $this->get_required_custom_fields(),
            'readonly' => true,
        ];
    }

    /**
     * Build a dry-run sync report by comparing mapped records with Moodle DB.
     *
     * This method performs read-only DB lookups. It never creates or updates records.
     *
     * @param array<string, mixed> $faculty Normalized faculty profile.
     * @param array<string, mixed> $atlas Decoded Atlas voie.
     * @param array<string, mixed>|null $registryrecord Optional registry record.
     * @return array<string, mixed>
     */
    public function dry_run(array $faculty, array $atlas, ?array $registryrecord = null): array {
        $mapping = $this->map($faculty, $atlas, $registryrecord);

        $categorycheck = $this->compare_category($mapping['category']);
        $hubcheck = $this->compare_course($mapping['hub_course']);

        $coursechecks = [];
        foreach ($mapping['courses'] as $course) {
            $coursechecks[] = $this->compare_course($course);
        }

        $summary = [
            'create' => 0,
            'update' => 0,
            'no_change' => 0,
            'warning' => 0,
            'error' => 0,
        ];

        foreach (array_merge([$categorycheck, $hubcheck], $coursechecks) as $check) {
            $action = $check['action'] ?? self::ACTION_ERROR;
            if (!array_key_exists($action, $summary)) {
                $summary['error']++;
                continue;
            }

            $summary[$action]++;
        }

        return [
            'component' => self::COMPONENT,
            'faculty_id' => $mapping['faculty_id'],
            'voie_id' => $mapping['voie_id'],
            'slug' => $mapping['slug'],
            'readonly' => true,
            'valid' => $summary['error'] === 0,
            'summary' => $summary,
            'category' => $categorycheck,
            'hub_course' => $hubcheck,
            'courses' => $coursechecks,
            'mapping' => $mapping,
        ];
    }

    /**
     * Map the faculty Moodle category.
     *
     * @param array<string, mixed> $faculty Normalized faculty profile.
     * @param array<string, mixed> $atlas Decoded Atlas voie.
     * @param array<string, mixed>|null $registryrecord Optional registry record.
     * @return array<string, mixed>
     */
    public function map_category(array $faculty, array $atlas = [], ?array $registryrecord = null): array {
        $moodle = $this->array_value($faculty, 'moodle');
        $identity = $this->array_value($faculty, 'identity');

        $idnumber = $this->first_non_empty([
            $this->string_value($moodle, 'category_idnumber'),
            $this->string_value($registryrecord ?? [], 'category_idnumber'),
        ]);

        $name = $this->first_non_empty([
            $this->string_value($identity, 'name'),
            $this->string_value($atlas, 'nom'),
            $idnumber,
        ]);

        $description = $this->first_non_empty([
            $this->string_value($identity, 'one_sentence'),
            $this->string_value($atlas, 'definition_courte'),
        ]);

        return [
            'target_type' => 'course_category',
            'id' => $this->nullable_int_value($moodle, 'category_id'),
            'idnumber' => $idnumber,
            'name' => $name,
            'description' => $description,
            'parent' => self::DEFAULT_CATEGORY_PARENT,
            'visible' => $this->category_visible($faculty),
            'sortorder' => $this->int_value($registryrecord ?? [], 'sortorder', 0),
            'path' => '',
            'metadata' => [
                'component' => self::COMPONENT,
                'faculty_id' => $this->string_value($faculty, 'faculty_id'),
                'voie_id' => $this->string_value($faculty, 'voie_id'),
                'slug' => $this->string_value($faculty, 'slug'),
                'course_prefix' => $this->string_value($moodle, 'course_prefix'),
            ],
        ];
    }

    /**
     * Map the public or semi-public faculty hub course.
     *
     * @param array<string, mixed> $faculty Normalized faculty profile.
     * @param array<string, mixed> $atlas Decoded Atlas voie.
     * @param array<string, mixed> $category Mapped category.
     * @param array<string, mixed>|null $registryrecord Optional registry record.
     * @return array<string, mixed>
     */
    public function map_hub_course(
        array $faculty,
        array $atlas,
        array $category,
        ?array $registryrecord = null
    ): array {
        $moodle = $this->array_value($faculty, 'moodle');
        $identity = $this->array_value($faculty, 'identity');

        $courseprefix = $this->first_non_empty([
            $this->string_value($moodle, 'course_prefix'),
            $this->string_value($registryrecord ?? [], 'course_prefix'),
            $this->string_value($atlas, 'code'),
        ]);

        $idnumber = $this->first_non_empty([
            $this->string_value($moodle, 'hub_course_idnumber'),
            $courseprefix !== '' ? $courseprefix . '-HUB' : '',
        ]);

        $shortname = $idnumber;
        $fullname = $this->first_non_empty([
            $this->string_value($identity, 'name'),
            $this->string_value($atlas, 'nom'),
            $courseprefix,
        ]);

        if ($fullname !== '') {
            $fullname .= ' — Hub';
        }

        return [
            'target_type' => 'course',
            'course_kind' => 'faculty_hub',
            'idnumber' => $idnumber,
            'shortname' => $shortname,
            'fullname' => $fullname,
            'summary' => $this->first_non_empty([
                $this->string_value($faculty['hero'] ?? [], 'summary'),
                $this->string_value($identity, 'one_sentence'),
                $this->string_value($atlas, 'definition_courte'),
            ]),
            'category_idnumber' => $this->string_value($category, 'idnumber'),
            'format' => self::DEFAULT_COURSE_FORMAT,
            'visible' => $this->course_visible($faculty),
            'sortorder' => 0,
            'url' => $this->course_url_by_idnumber($idnumber),
            'customfields' => [
                self::CUSTOMFIELD_VOIE_ID => $this->string_value($faculty, 'voie_id'),
                self::CUSTOMFIELD_FACULTY_ID => $this->string_value($faculty, 'faculty_id'),
                self::CUSTOMFIELD_FACULTY_SLUG => $this->string_value($faculty, 'slug'),
            ],
            'metadata' => [
                'component' => self::COMPONENT,
                'faculty_id' => $this->string_value($faculty, 'faculty_id'),
                'voie_id' => $this->string_value($faculty, 'voie_id'),
                'slug' => $this->string_value($faculty, 'slug'),
                'course_prefix' => $courseprefix,
            ],
        ];
    }

    /**
     * Map all Atlas conceptual courses.
     *
     * @param array<string, mixed> $faculty Normalized faculty profile.
     * @param array<string, mixed> $atlas Decoded Atlas voie.
     * @param array<string, mixed>|null $category Optional mapped category.
     * @param array<string, mixed>|null $registryrecord Optional registry record.
     * @return array<int, array<string, mixed>>
     */
    public function map_courses(
        array $faculty,
        array $atlas,
        ?array $category = null,
        ?array $registryrecord = null
    ): array {
        $category = $category ?? $this->map_category($faculty, $atlas, $registryrecord);
        $courses = $this->list_value($atlas, 'cours_conceptuels');

        $mapped = [];

        foreach ($courses as $course) {
            if (!is_array($course)) {
                continue;
            }

            $mapped[] = $this->map_course($faculty, $atlas, $course, $category, $registryrecord);
        }

        usort($mapped, static function (array $left, array $right): int {
            return ((int)($left['sortorder'] ?? 0)) <=> ((int)($right['sortorder'] ?? 0));
        });

        return array_values($mapped);
    }

    /**
     * Map one Atlas conceptual course to Moodle course fields.
     *
     * @param array<string, mixed> $faculty Normalized faculty profile.
     * @param array<string, mixed> $atlas Decoded Atlas voie.
     * @param array<string, mixed> $course Atlas course.
     * @param array<string, mixed> $category Mapped category.
     * @param array<string, mixed>|null $registryrecord Optional registry record.
     * @return array<string, mixed>
     */
    public function map_course(
        array $faculty,
        array $atlas,
        array $course,
        array $category,
        ?array $registryrecord = null
    ): array {
        $coursid = $this->string_value($course, 'cours_id');
        $name = $this->string_value($course, 'nom');
        $ordre = $this->int_value($course, 'ordre', 0);

        $concept = $this->array_value($course, 'concept_maitre');
        $artefact = $this->array_value($course, 'artefact_maitrise');

        return [
            'target_type' => 'course',
            'course_kind' => 'atlas_conceptual_course',
            'idnumber' => $coursid,
            'shortname' => $coursid,
            'fullname' => $this->course_fullname($coursid, $name),
            'summary' => $this->build_course_summary($course),
            'category_idnumber' => $this->string_value($category, 'idnumber'),
            'format' => self::DEFAULT_COURSE_FORMAT,
            'visible' => $this->course_visible($faculty),
            'sortorder' => $ordre,
            'url' => $this->course_url_by_idnumber($coursid),
            'customfields' => [
                self::CUSTOMFIELD_VOIE_ID => $this->string_value($faculty, 'voie_id'),
                self::CUSTOMFIELD_FACULTY_ID => $this->string_value($faculty, 'faculty_id'),
                self::CUSTOMFIELD_FACULTY_SLUG => $this->string_value($faculty, 'slug'),
                self::CUSTOMFIELD_CONCEPT_MAITRE_ID => $this->string_value($concept, 'concept_id'),
                self::CUSTOMFIELD_CONCEPT_MAITRE_NOM => $this->string_value($concept, 'nom'),
                self::CUSTOMFIELD_ARTEFACT_TYPE => $this->string_value($artefact, 'type'),
                self::CUSTOMFIELD_ARTEFACT_NOM => $this->string_value($artefact, 'nom'),
            ],
            'source' => [
                'atlas_voie_id' => $this->string_value($atlas, 'voie_id'),
                'atlas_code' => $this->string_value($atlas, 'code'),
                'cours_id' => $coursid,
                'ordre' => $ordre,
                'concept_maitre' => [
                    'concept_id' => $this->string_value($concept, 'concept_id'),
                    'nom' => $this->string_value($concept, 'nom'),
                    'type' => $this->string_value($concept, 'type'),
                ],
                'artefact_maitrise' => [
                    'type' => $this->string_value($artefact, 'type'),
                    'nom' => $this->string_value($artefact, 'nom'),
                ],
            ],
            'metadata' => [
                'component' => self::COMPONENT,
                'faculty_id' => $this->string_value($faculty, 'faculty_id'),
                'voie_id' => $this->string_value($faculty, 'voie_id'),
                'slug' => $this->string_value($faculty, 'slug'),
                'course_prefix' => $this->first_non_empty([
                    $this->string_value($faculty['moodle'] ?? [], 'course_prefix'),
                    $this->string_value($registryrecord ?? [], 'course_prefix'),
                    $this->string_value($atlas, 'code'),
                ]),
            ],
        ];
    }

    /**
     * Return required Moodle custom fields for mapped UCKK faculty courses.
     *
     * @return array<int, array<string, mixed>>
     */
    public function get_required_custom_fields(): array {
        return [
            [
                'shortname' => self::CUSTOMFIELD_VOIE_ID,
                'name' => 'UCKK voie id',
                'type' => 'text',
            ],
            [
                'shortname' => self::CUSTOMFIELD_FACULTY_ID,
                'name' => 'UCKK faculty id',
                'type' => 'text',
            ],
            [
                'shortname' => self::CUSTOMFIELD_FACULTY_SLUG,
                'name' => 'UCKK faculty slug',
                'type' => 'text',
            ],
            [
                'shortname' => self::CUSTOMFIELD_CONCEPT_MAITRE_ID,
                'name' => 'UCKK concept maître id',
                'type' => 'text',
            ],
            [
                'shortname' => self::CUSTOMFIELD_CONCEPT_MAITRE_NOM,
                'name' => 'UCKK concept maître nom',
                'type' => 'text',
            ],
            [
                'shortname' => self::CUSTOMFIELD_ARTEFACT_TYPE,
                'name' => 'UCKK artefact type',
                'type' => 'text',
            ],
            [
                'shortname' => self::CUSTOMFIELD_ARTEFACT_NOM,
                'name' => 'UCKK artefact nom',
                'type' => 'text',
            ],
        ];
    }

    /**
     * Return expected course idnumbers for one faculty.
     *
     * @param array<string, mixed> $faculty Normalized faculty profile.
     * @param array<string, mixed> $atlas Decoded Atlas voie.
     * @return array<int, string>
     */
    public function get_expected_course_idnumbers(array $faculty, array $atlas): array {
        $idnumbers = [];

        $hubidnumber = $this->string_value($faculty['moodle'] ?? [], 'hub_course_idnumber');
        if ($hubidnumber !== '') {
            $idnumbers[] = $hubidnumber;
        }

        foreach ($this->list_value($atlas, 'cours_conceptuels') as $course) {
            if (!is_array($course)) {
                continue;
            }

            $coursid = $this->string_value($course, 'cours_id');
            if ($coursid !== '') {
                $idnumbers[] = $coursid;
            }
        }

        return array_values(array_unique($idnumbers));
    }

    /**
     * Compare a mapped category with existing Moodle data.
     *
     * @param array<string, mixed> $mappedcategory Mapped category.
     * @return array<string, mixed>
     */
    public function compare_category(array $mappedcategory): array {
        $idnumber = $this->string_value($mappedcategory, 'idnumber');

        if ($idnumber === '') {
            return $this->comparison(
                'course_category',
                '',
                self::ACTION_ERROR,
                'Category idnumber is empty.',
                null,
                $mappedcategory
            );
        }

        $existing = $this->find_category_by_idnumber($idnumber);

        if ($existing === null) {
            return $this->comparison(
                'course_category',
                $idnumber,
                self::ACTION_CREATE,
                'Category does not exist and would be created by sync.',
                null,
                $mappedcategory
            );
        }

        $changes = $this->category_changes($existing, $mappedcategory);

        return $this->comparison(
            'course_category',
            $idnumber,
            empty($changes) ? self::ACTION_NO_CHANGE : self::ACTION_UPDATE,
            empty($changes) ? 'Category is aligned.' : 'Category exists but differs from mapping.',
            $existing,
            $mappedcategory,
            $changes
        );
    }

    /**
     * Compare a mapped Moodle course with existing Moodle data.
     *
     * @param array<string, mixed> $mappedcourse Mapped course.
     * @return array<string, mixed>
     */
    public function compare_course(array $mappedcourse): array {
        $idnumber = $this->string_value($mappedcourse, 'idnumber');

        if ($idnumber === '') {
            return $this->comparison(
                'course',
                '',
                self::ACTION_ERROR,
                'Course idnumber is empty.',
                null,
                $mappedcourse
            );
        }

        $existing = $this->find_course_by_idnumber($idnumber);

        if ($existing === null) {
            return $this->comparison(
                'course',
                $idnumber,
                self::ACTION_CREATE,
                'Course does not exist and would be created by sync.',
                null,
                $mappedcourse
            );
        }

        $changes = $this->course_changes($existing, $mappedcourse);

        return $this->comparison(
            'course',
            $idnumber,
            empty($changes) ? self::ACTION_NO_CHANGE : self::ACTION_UPDATE,
            empty($changes) ? 'Course is aligned.' : 'Course exists but differs from mapping.',
            $existing,
            $mappedcourse,
            $changes
        );
    }

    /**
     * Read an existing Moodle category by idnumber.
     *
     * @param string $idnumber Category idnumber.
     * @return stdClass|null
     */
    public function find_category_by_idnumber(string $idnumber): ?stdClass {
        if ($this->db === null || trim($idnumber) === '') {
            return null;
        }

        $record = $this->db->get_record('course_categories', ['idnumber' => $idnumber], '*', IGNORE_MISSING);

        return $record instanceof stdClass ? $record : null;
    }

    /**
     * Read an existing Moodle course by idnumber.
     *
     * @param string $idnumber Course idnumber.
     * @return stdClass|null
     */
    public function find_course_by_idnumber(string $idnumber): ?stdClass {
        if ($this->db === null || trim($idnumber) === '') {
            return null;
        }

        $record = $this->db->get_record('course', ['idnumber' => $idnumber], '*', IGNORE_MISSING);

        return $record instanceof stdClass ? $record : null;
    }

    /**
     * Return known existing Moodle courses for a list of idnumbers.
     *
     * @param array<int, string> $idnumbers Course idnumbers.
     * @return array<string, stdClass>
     */
    public function find_courses_by_idnumbers(array $idnumbers): array {
        if ($this->db === null) {
            return [];
        }

        $idnumbers = array_values(array_filter(array_unique(array_map('trim', $idnumbers))));
        if (empty($idnumbers)) {
            return [];
        }

        [$insql, $params] = $this->db->get_in_or_equal($idnumbers, SQL_PARAMS_NAMED, 'idn');
        $records = $this->db->get_records_select('course', 'idnumber ' . $insql, $params);

        $indexed = [];
        foreach ($records as $record) {
            if ($record instanceof stdClass && isset($record->idnumber)) {
                $indexed[(string)$record->idnumber] = $record;
            }
        }

        return $indexed;
    }

    /**
     * Build differences between a category record and a mapped category.
     *
     * @param stdClass $existing Existing category.
     * @param array<string, mixed> $mapped Mapped category.
     * @return array<string, array<string, mixed>>
     */
    private function category_changes(stdClass $existing, array $mapped): array {
        $changes = [];

        $this->compare_field($changes, 'name', $existing->name ?? null, $mapped['name'] ?? null);
        $this->compare_field($changes, 'idnumber', $existing->idnumber ?? null, $mapped['idnumber'] ?? null);
        $this->compare_field($changes, 'visible', (int)($existing->visible ?? 0), (int)($mapped['visible'] ?? 0));

        if (isset($mapped['parent']) && (int)($mapped['parent']) > 0) {
            $this->compare_field($changes, 'parent', (int)($existing->parent ?? 0), (int)$mapped['parent']);
        }

        return $changes;
    }

    /**
     * Build differences between a course record and a mapped course.
     *
     * @param stdClass $existing Existing course.
     * @param array<string, mixed> $mapped Mapped course.
     * @return array<string, array<string, mixed>>
     */
    private function course_changes(stdClass $existing, array $mapped): array {
        $changes = [];

        $this->compare_field($changes, 'shortname', $existing->shortname ?? null, $mapped['shortname'] ?? null);
        $this->compare_field($changes, 'fullname', $existing->fullname ?? null, $mapped['fullname'] ?? null);
        $this->compare_field($changes, 'idnumber', $existing->idnumber ?? null, $mapped['idnumber'] ?? null);
        $this->compare_field($changes, 'visible', (int)($existing->visible ?? 0), (int)($mapped['visible'] ?? 0));
        $this->compare_field($changes, 'format', $existing->format ?? null, $mapped['format'] ?? null);

        return $changes;
    }

    /**
     * Add one field difference.
     *
     * @param array<string, array<string, mixed>> $changes Changes.
     * @param string $field Field name.
     * @param mixed $existing Existing value.
     * @param mixed $mapped Mapped value.
     */
    private function compare_field(array &$changes, string $field, $existing, $mapped): void {
        if ((string)$existing === (string)$mapped) {
            return;
        }

        $changes[$field] = [
            'existing' => $existing,
            'mapped' => $mapped,
        ];
    }

    /**
     * Build a standard comparison row.
     *
     * @param string $targettype Target type.
     * @param string $targetkey Target key.
     * @param string $action Action.
     * @param string $message Message.
     * @param stdClass|null $existing Existing record.
     * @param array<string, mixed> $mapped Mapped data.
     * @param array<string, mixed> $changes Changes.
     * @return array<string, mixed>
     */
    private function comparison(
        string $targettype,
        string $targetkey,
        string $action,
        string $message,
        ?stdClass $existing,
        array $mapped,
        array $changes = []
    ): array {
        return [
            'target_type' => $targettype,
            'target_key' => $targetkey,
            'action' => $action,
            'message' => $message,
            'existing_id' => $existing !== null && isset($existing->id) ? (int)$existing->id : null,
            'changes' => $changes,
            'mapped' => $mapped,
        ];
    }

    /**
     * Build a Moodle course fullname.
     *
     * DOC_12 convention: fullname = cours_id + " — " + nom.
     *
     * @param string $coursid Course id.
     * @param string $name Course name.
     * @return string
     */
    private function course_fullname(string $coursid, string $name): string {
        if ($coursid === '') {
            return $name;
        }

        if ($name === '') {
            return $coursid;
        }

        return $coursid . ' — ' . $name;
    }

    /**
     * Build a conservative Moodle summary for one Atlas course.
     *
     * @param array<string, mixed> $course Atlas course.
     * @return string
     */
    private function build_course_summary(array $course): string {
        $concept = $this->array_value($course, 'concept_maitre');
        $artefact = $this->array_value($course, 'artefact_maitrise');

        $parts = [];

        $conceptname = $this->string_value($concept, 'nom');
        $conceptdefinition = $this->string_value($concept, 'definition_courte');
        if ($conceptname !== '') {
            $parts[] = 'Concept maître : ' . $conceptname;
        }
        if ($conceptdefinition !== '') {
            $parts[] = $conceptdefinition;
        }

        $artefactname = $this->string_value($artefact, 'nom');
        $artefactdescription = $this->string_value($artefact, 'description');
        if ($artefactname !== '') {
            $parts[] = 'Artefact de maîtrise : ' . $artefactname;
        }
        if ($artefactdescription !== '') {
            $parts[] = $artefactdescription;
        }

        return implode("\n\n", $parts);
    }

    /**
     * Return whether the category should be visible.
     *
     * @param array<string, mixed> $faculty Faculty profile.
     * @return int
     */
    private function category_visible(array $faculty): int {
        return $this->string_value($faculty, 'status') === 'published'
            && in_array($this->string_value($faculty, 'visibility'), ['public', 'restricted'], true)
            ? 1
            : 0;
    }

    /**
     * Return whether mapped courses should be visible.
     *
     * @param array<string, mixed> $faculty Faculty profile.
     * @return int
     */
    private function course_visible(array $faculty): int {
        $moodle = $this->array_value($faculty, 'moodle');

        if ($this->string_value($faculty, 'status') !== 'published') {
            return 0;
        }

        if ($this->string_value($faculty, 'visibility') === 'hidden') {
            return 0;
        }

        if ($this->string_value($moodle, 'enrolment_visibility') === 'hidden') {
            return 0;
        }

        return 1;
    }

    /**
     * Return course URL by idnumber when the course exists.
     *
     * @param string $idnumber Course idnumber.
     * @return string
     */
    private function course_url_by_idnumber(string $idnumber): string {
        $course = $this->find_course_by_idnumber($idnumber);

        if ($course === null || empty($course->id)) {
            return '';
        }

        return (new moodle_url('/course/view.php', ['id' => (int)$course->id]))->out(false);
    }

    /**
     * Assert minimal identity consistency.
     *
     * @param array<string, mixed> $faculty Faculty profile.
     * @param array<string, mixed> $atlas Atlas voie.
     * @param array<string, mixed>|null $registryrecord Registry record.
     */
    private function assert_identity_consistency(array $faculty, array $atlas, ?array $registryrecord): void {
        if ($registryrecord !== null) {
            foreach (['faculty_id', 'voie_id', 'slug'] as $field) {
                if ($this->string_value($registryrecord, $field) !== $this->string_value($faculty, $field)) {
                    throw new coding_exception('Faculty Moodle mapping identity mismatch on field: ' . $field);
                }
            }

            if (
                $this->string_value($registryrecord, 'category_idnumber') !== ''
                && $this->string_value($faculty['moodle'] ?? [], 'category_idnumber') !== ''
                && $this->string_value($registryrecord, 'category_idnumber')
                    !== $this->string_value($faculty['moodle'] ?? [], 'category_idnumber')
            ) {
                throw new coding_exception('Faculty Moodle mapping category_idnumber does not match registry.');
            }
        }

        if (
            $this->string_value($atlas, 'voie_id') !== ''
            && $this->string_value($faculty, 'voie_id') !== ''
            && $this->string_value($atlas, 'voie_id') !== $this->string_value($faculty, 'voie_id')
        ) {
            throw new coding_exception('Faculty Moodle mapping voie_id does not match Atlas voie_id.');
        }

        if (
            $this->string_value($atlas, 'code') !== ''
            && $this->string_value($faculty['moodle'] ?? [], 'course_prefix') !== ''
            && $this->string_value($atlas, 'code') !== $this->string_value($faculty['moodle'] ?? [], 'course_prefix')
        ) {
            throw new coding_exception('Faculty Moodle mapping course_prefix does not match Atlas code.');
        }
    }

    /**
     * Return a first non-empty string from candidates.
     *
     * @param array<int, mixed> $values Values.
     * @return string
     */
    private function first_non_empty(array $values): string {
        foreach ($values as $value) {
            if (!is_string($value)) {
                continue;
            }

            $value = trim($value);
            if ($value !== '') {
                return $value;
            }
        }

        return '';
    }

    /**
     * Return string value from array.
     *
     * @param array<string, mixed> $data Data.
     * @param string $key Key.
     * @param string $default Default.
     * @return string
     */
    private function string_value(array $data, string $key, string $default = ''): string {
        if (!array_key_exists($key, $data) || !is_string($data[$key])) {
            return $default;
        }

        return trim($data[$key]);
    }

    /**
     * Return nullable integer value from array.
     *
     * @param array<string, mixed> $data Data.
     * @param string $key Key.
     * @return int|null
     */
    private function nullable_int_value(array $data, string $key): ?int {
        if (!array_key_exists($key, $data) || $data[$key] === null) {
            return null;
        }

        if (is_int($data[$key])) {
            return $data[$key];
        }

        if (is_string($data[$key]) && preg_match('/^\d+$/', $data[$key])) {
            return (int)$data[$key];
        }

        return null;
    }

    /**
     * Return integer value from array.
     *
     * @param array<string, mixed> $data Data.
     * @param string $key Key.
     * @param int $default Default.
     * @return int
     */
    private function int_value(array $data, string $key, int $default = 0): int {
        if (!array_key_exists($key, $data)) {
            return $default;
        }

        if (is_int($data[$key])) {
            return $data[$key];
        }

        if (is_string($data[$key]) && preg_match('/^\d+$/', $data[$key])) {
            return (int)$data[$key];
        }

        return $default;
    }

    /**
     * Return an associative array field.
     *
     * @param array<string, mixed> $data Data.
     * @param string $key Key.
     * @return array<string, mixed>
     */
    private function array_value(array $data, string $key): array {
        if (!array_key_exists($key, $data) || !is_array($data[$key])) {
            return [];
        }

        if ($this->is_list_array($data[$key])) {
            return [];
        }

        return $data[$key];
    }

    /**
     * Return a list field.
     *
     * @param array<string, mixed> $data Data.
     * @param string $key Key.
     * @return array<int, mixed>
     */
    private function list_value(array $data, string $key): array {
        if (!array_key_exists($key, $data) || !is_array($data[$key])) {
            return [];
        }

        return array_values($data[$key]);
    }

    /**
     * Return whether an array is a list.
     *
     * @param array<mixed> $array Array.
     * @return bool
     */
    private function is_list_array(array $array): bool {
        if ($array === []) {
            return true;
        }

        return array_keys($array) === range(0, count($array) - 1);
    }
}
