<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// at your option any later version.

/**
 * Atlas Voie to Moodle mapping service for the UCKK faculty pages contract.
 *
 * @package    local_uckk
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace local_uckk\local\atlas;

defined('MOODLE_INTERNAL') || die();

/**
 * Maps canonical Atlas Voie arrays to Moodle-native target definitions.
 *
 * This class prepares deterministic Moodle mapping data only. It does not:
 * - create categories;
 * - create courses;
 * - update Moodle records;
 * - read the Moodle database;
 * - render HTML;
 * - decide permissions.
 */
final class voie_moodle_mapper {
    /** Plugin component. */
    public const COMPONENT = 'local_uckk';

    /** Default course format for mapped UCKK courses. */
    public const DEFAULT_COURSE_FORMAT = 'uckk';

    /** Default language for mapped course records. */
    public const DEFAULT_LANGUAGE = '';

    /** Default Moodle summary format. FORMAT_HTML is normally 1. */
    public const DEFAULT_SUMMARY_FORMAT = 1;

    /** Default course visibility. */
    public const DEFAULT_VISIBLE = 1;

    /** Default completion setting for UCKK courses. */
    public const DEFAULT_ENABLE_COMPLETION = 1;

    /** Atlas-managed category operation key for sync plans. */
    public const OPERATION_ENSURE_CATEGORY = 'ensure_category';

    /** Atlas-managed course operation key for sync plans. */
    public const OPERATION_ENSURE_COURSE = 'ensure_course';

    /** Metadata key for generated mapping source. */
    public const METADATA_SOURCE = 'source';

    /** Metadata value for Atlas source. */
    public const METADATA_SOURCE_ATLAS = 'atlas';

    /** Metadata key for owner. */
    public const METADATA_OWNER = 'owner';

    /** Metadata key for source schema. */
    public const METADATA_SCHEMA_VERSION = 'schema_version';

    /** Metadata key for source voie_id. */
    public const METADATA_VOIE_ID = 'voie_id';

    /** Moodle custom field shortname for the Atlas voie_id. */
    public const CUSTOMFIELD_VOIE_ID = 'uckk_voie_id';

    /** Moodle custom field shortname for the Atlas domain. */
    public const CUSTOMFIELD_DOMAIN = 'uckk_domain';

    /** Moodle custom field shortname for the Atlas level. */
    public const CUSTOMFIELD_LEVEL = 'uckk_level';

    /** Moodle custom field shortname for the Atlas symbolic title. */
    public const CUSTOMFIELD_SYMBOLIC_TITLE = 'uckk_title_symbolique';

    /** Moodle custom field shortname for the Atlas course id. */
    public const CUSTOMFIELD_COURS_ID = 'uckk_cours_id';

    /** Moodle custom field shortname for the Atlas master concept id. */
    public const CUSTOMFIELD_MASTER_CONCEPT_ID = 'uckk_concept_maitre_id';

    /** Moodle custom field shortname for the Atlas master concept name. */
    public const CUSTOMFIELD_MASTER_CONCEPT_NAME = 'uckk_concept_maitre';

    /** Course summary section separator. */
    private const SUMMARY_SEPARATOR = "\n\n";

    /**
     * Map one Atlas Voie to a full Moodle target bundle.
     *
     * @param array<string, mixed> $voie Normalized Atlas Voie.
     * @param array<string, mixed> $manifestitem Optional Atlas manifest item.
     * @return array<string, mixed>
     */
    public function map_voie(array $voie, array $manifestitem = []): array {
        $manifestitem = $this->complete_manifest_item($voie, $manifestitem);

        return [
            'voie_id' => $this->require_string($voie, 'voie_id', 'voie.voie_id'),
            'code' => $this->get_course_prefix($voie, $manifestitem),
            'category_idnumber' => $this->get_category_idnumber($voie, $manifestitem),
            'category' => $this->map_category($voie, $manifestitem),
            'courses' => $this->map_courses($voie, $manifestitem),
            'customfields' => $this->map_voie_custom_fields($voie),
            'metadata' => $this->map_metadata($voie, $manifestitem),
        ];
    }

    /**
     * Map one Atlas Voie to a Moodle course category target.
     *
     * @param array<string, mixed> $voie Normalized Atlas Voie.
     * @param array<string, mixed> $manifestitem Optional Atlas manifest item.
     * @return array<string, mixed>
     */
    public function map_category(array $voie, array $manifestitem = []): array {
        $manifestitem = $this->complete_manifest_item($voie, $manifestitem);

        $name = $this->first_non_empty_string([
            $manifestitem['nom'] ?? null,
            $voie['nom'] ?? null,
        ]);

        return [
            'key' => $this->get_category_idnumber($voie, $manifestitem),
            'idnumber' => $this->get_category_idnumber($voie, $manifestitem),
            'name' => $name,
            'description' => $this->clean_string((string)($voie['definition_courte'] ?? '')),
            'visible' => self::DEFAULT_VISIBLE,
            'sortorder' => $this->get_sortorder($manifestitem),
            'customfields' => $this->map_voie_custom_fields($voie),
            'metadata' => $this->map_metadata($voie, $manifestitem),
        ];
    }

    /**
     * Map all conceptual courses from one Atlas Voie.
     *
     * @param array<string, mixed> $voie Normalized Atlas Voie.
     * @param array<string, mixed> $manifestitem Optional Atlas manifest item.
     * @return array<int, array<string, mixed>>
     */
    public function map_courses(array $voie, array $manifestitem = []): array {
        $manifestitem = $this->complete_manifest_item($voie, $manifestitem);

        if (!isset($voie['cours_conceptuels']) || !is_array($voie['cours_conceptuels'])) {
            return [];
        }

        $courses = [];
        foreach ($voie['cours_conceptuels'] as $course) {
            if (!is_array($course)) {
                continue;
            }

            $courses[] = $this->map_course($voie, $course, $manifestitem);
        }

        usort($courses, static function(array $left, array $right): int {
            $leftorder = (int)($left['sortorder'] ?? 0);
            $rightorder = (int)($right['sortorder'] ?? 0);

            if ($leftorder === $rightorder) {
                return strcmp((string)($left['idnumber'] ?? ''), (string)($right['idnumber'] ?? ''));
            }

            return $leftorder <=> $rightorder;
        });

        return array_values($courses);
    }

    /**
     * Map one Atlas conceptual course to a Moodle course target.
     *
     * @param array<string, mixed> $voie Normalized Atlas Voie.
     * @param array<string, mixed> $course Atlas conceptual course.
     * @param array<string, mixed> $manifestitem Optional Atlas manifest item.
     * @return array<string, mixed>
     */
    public function map_course(array $voie, array $course, array $manifestitem = []): array {
        $manifestitem = $this->complete_manifest_item($voie, $manifestitem);

        $coursid = $this->get_course_idnumber($voie, $course);
        $coursename = $this->first_non_empty_string([
            $course['nom'] ?? null,
            $coursid,
        ]);

        return [
            'key' => $coursid,
            'fullname' => $coursename,
            'shortname' => $coursid,
            'idnumber' => $coursid,
            'category' => $this->get_category_idnumber($voie, $manifestitem),
            'category_idnumber' => $this->get_category_idnumber($voie, $manifestitem),
            'format' => self::DEFAULT_COURSE_FORMAT,
            'summary' => $this->build_course_summary($voie, $course),
            'summaryformat' => self::DEFAULT_SUMMARY_FORMAT,
            'visible' => self::DEFAULT_VISIBLE,
            'lang' => self::DEFAULT_LANGUAGE,
            'enablecompletion' => self::DEFAULT_ENABLE_COMPLETION,
            'sortorder' => $this->get_course_order($course),
            'tags' => $this->map_course_tags($voie, $course),
            'customfields' => $this->map_course_custom_fields($voie, $course),
            'sections' => $this->map_course_sections($course),
            'metadata' => $this->map_course_metadata($voie, $course, $manifestitem),
        ];
    }

    /**
     * Build a dry-run/apply target plan for one Voie.
     *
     * The returned data is intentionally declarative. A sync service may compare
     * these targets with real Moodle records and decide whether to apply changes.
     *
     * @param array<string, mixed> $voie Normalized Atlas Voie.
     * @param array<string, mixed> $manifestitem Optional Atlas manifest item.
     * @return array<string, mixed>
     */
    public function map_sync_plan(array $voie, array $manifestitem = []): array {
        $bundle = $this->map_voie($voie, $manifestitem);

        $targets = [
            [
                'operation' => self::OPERATION_ENSURE_CATEGORY,
                'targettype' => 'course_category',
                'targetkey' => $bundle['category']['idnumber'],
                'data' => $bundle['category'],
            ],
        ];

        foreach ($bundle['courses'] as $course) {
            $targets[] = [
                'operation' => self::OPERATION_ENSURE_COURSE,
                'targettype' => 'course',
                'targetkey' => $course['idnumber'],
                'data' => $course,
            ];
        }

        return [
            'voie_id' => $bundle['voie_id'],
            'category_idnumber' => $bundle['category_idnumber'],
            'course_prefix' => $bundle['code'],
            'targetcount' => count($targets),
            'targets' => $targets,
        ];
    }

    /**
     * Return the expected Moodle course idnumber for one Atlas course.
     *
     * @param array<string, mixed> $voie Normalized Atlas Voie.
     * @param array<string, mixed> $course Atlas conceptual course.
     * @return string
     */
    public function get_course_idnumber(array $voie, array $course): string {
        if (isset($course['cours_id']) && trim((string)$course['cours_id']) !== '') {
            return $this->clean_course_idnumber((string)$course['cours_id']);
        }

        $prefix = $this->get_course_prefix($voie);
        $order = $this->get_course_order($course);

        return $this->clean_course_idnumber($prefix . (string)(100 + $order));
    }

    /**
     * Return the expected Moodle category idnumber for one Voie.
     *
     * @param array<string, mixed> $voie Normalized Atlas Voie.
     * @param array<string, mixed> $manifestitem Optional Atlas manifest item.
     * @return string
     */
    public function get_category_idnumber(array $voie, array $manifestitem = []): string {
        if (isset($manifestitem['category_idnumber']) && trim((string)$manifestitem['category_idnumber']) !== '') {
            return $this->clean_category_idnumber((string)$manifestitem['category_idnumber']);
        }

        return voie_slugger::course_prefix_to_category_idnumber($this->get_course_prefix($voie, $manifestitem));
    }

    /**
     * Return the expected Moodle course prefix for one Voie.
     *
     * @param array<string, mixed> $voie Normalized Atlas Voie.
     * @param array<string, mixed> $manifestitem Optional Atlas manifest item.
     * @return string
     */
    public function get_course_prefix(array $voie, array $manifestitem = []): string {
        if (isset($manifestitem['course_prefix']) && trim((string)$manifestitem['course_prefix']) !== '') {
            return voie_slugger::clean_course_prefix((string)$manifestitem['course_prefix']);
        }

        if (isset($manifestitem['code']) && trim((string)$manifestitem['code']) !== '') {
            return voie_slugger::clean_course_prefix((string)$manifestitem['code']);
        }

        return voie_slugger::clean_course_prefix($this->require_string($voie, 'code', 'voie.code'));
    }

    /**
     * Map Atlas top-level values to Moodle custom fields.
     *
     * @param array<string, mixed> $voie Normalized Atlas Voie.
     * @return array<int, array<string, string>>
     */
    public function map_voie_custom_fields(array $voie): array {
        return $this->clean_custom_fields([
            [
                'shortname' => self::CUSTOMFIELD_VOIE_ID,
                'value' => $this->require_string($voie, 'voie_id', 'voie.voie_id'),
            ],
            [
                'shortname' => self::CUSTOMFIELD_DOMAIN,
                'value' => $this->clean_string((string)($voie['domaine_operatoire'] ?? '')),
            ],
            [
                'shortname' => self::CUSTOMFIELD_LEVEL,
                'value' => $this->clean_string((string)($voie['niveau_vise'] ?? '')),
            ],
            [
                'shortname' => self::CUSTOMFIELD_SYMBOLIC_TITLE,
                'value' => $this->clean_string((string)($voie['titre_symbolique'] ?? '')),
            ],
        ]);
    }

    /**
     * Map Atlas course values to Moodle custom fields.
     *
     * @param array<string, mixed> $voie Normalized Atlas Voie.
     * @param array<string, mixed> $course Atlas conceptual course.
     * @return array<int, array<string, string>>
     */
    public function map_course_custom_fields(array $voie, array $course): array {
        $master = [];
        if (isset($course['concept_maitre']) && is_array($course['concept_maitre'])) {
            $master = $course['concept_maitre'];
        }

        return $this->clean_custom_fields(array_merge($this->map_voie_custom_fields($voie), [
            [
                'shortname' => self::CUSTOMFIELD_COURS_ID,
                'value' => $this->get_course_idnumber($voie, $course),
            ],
            [
                'shortname' => self::CUSTOMFIELD_MASTER_CONCEPT_ID,
                'value' => $this->clean_string((string)($master['concept_id'] ?? '')),
            ],
            [
                'shortname' => self::CUSTOMFIELD_MASTER_CONCEPT_NAME,
                'value' => $this->clean_string((string)($master['nom'] ?? '')),
            ],
        ]));
    }

    /**
     * Map one Atlas course to stable Moodle course sections.
     *
     * @param array<string, mixed> $course Atlas conceptual course.
     * @return array<int, array<string, mixed>>
     */
    public function map_course_sections(array $course): array {
        $master = [];
        if (isset($course['concept_maitre']) && is_array($course['concept_maitre'])) {
            $master = $course['concept_maitre'];
        }

        return [
            [
                'section' => 0,
                'name' => 'Orientation',
                'summary' => $this->build_orientation_section_summary($course, $master),
                'summaryformat' => self::DEFAULT_SUMMARY_FORMAT,
                'visible' => self::DEFAULT_VISIBLE,
            ],
            [
                'section' => 1,
                'name' => 'Concept maître',
                'summary' => $this->build_master_concept_section_summary($master),
                'summaryformat' => self::DEFAULT_SUMMARY_FORMAT,
                'visible' => self::DEFAULT_VISIBLE,
            ],
            [
                'section' => 2,
                'name' => 'Concepts associés',
                'summary' => $this->build_associated_concepts_section_summary($course),
                'summaryformat' => self::DEFAULT_SUMMARY_FORMAT,
                'visible' => self::DEFAULT_VISIBLE,
            ],
            [
                'section' => 3,
                'name' => 'Artefact de maîtrise',
                'summary' => $this->build_artefact_section_summary($course),
                'summaryformat' => self::DEFAULT_SUMMARY_FORMAT,
                'visible' => self::DEFAULT_VISIBLE,
            ],
            [
                'section' => 4,
                'name' => 'Critères de passage',
                'summary' => $this->build_criteria_section_summary($course),
                'summaryformat' => self::DEFAULT_SUMMARY_FORMAT,
                'visible' => self::DEFAULT_VISIBLE,
            ],
        ];
    }

    /**
     * Build safe stable metadata for one Voie mapping.
     *
     * @param array<string, mixed> $voie Normalized Atlas Voie.
     * @param array<string, mixed> $manifestitem Manifest item.
     * @return array<string, mixed>
     */
    public function map_metadata(array $voie, array $manifestitem = []): array {
        return [
            self::METADATA_SOURCE => self::METADATA_SOURCE_ATLAS,
            self::METADATA_OWNER => self::COMPONENT,
            self::METADATA_SCHEMA_VERSION => $this->clean_string((string)($voie['schema_version'] ?? '')),
            self::METADATA_VOIE_ID => $this->require_string($voie, 'voie_id', 'voie.voie_id'),
            'atlas_file' => $this->clean_string((string)($manifestitem['file'] ?? '')),
            'course_prefix' => $this->get_course_prefix($voie, $manifestitem),
            'category_idnumber' => $this->get_category_idnumber($voie, $manifestitem),
        ];
    }

    /**
     * Build safe stable metadata for one course mapping.
     *
     * @param array<string, mixed> $voie Normalized Atlas Voie.
     * @param array<string, mixed> $course Atlas conceptual course.
     * @param array<string, mixed> $manifestitem Manifest item.
     * @return array<string, mixed>
     */
    public function map_course_metadata(array $voie, array $course, array $manifestitem = []): array {
        $metadata = $this->map_metadata($voie, $manifestitem);
        $metadata['cours_id'] = $this->get_course_idnumber($voie, $course);
        $metadata['ordre'] = $this->get_course_order($course);

        if (isset($course['concept_maitre']) && is_array($course['concept_maitre'])) {
            $metadata['concept_maitre_id'] = $this->clean_string((string)($course['concept_maitre']['concept_id'] ?? ''));
        }

        return $metadata;
    }

    /**
     * Complete a manifest item from the Voie when it was not passed in.
     *
     * This fallback does not read files. It only derives stable identifiers.
     *
     * @param array<string, mixed> $voie Normalized Atlas Voie.
     * @param array<string, mixed> $manifestitem Optional manifest item.
     * @return array<string, mixed>
     */
    private function complete_manifest_item(array $voie, array $manifestitem): array {
        $voieid = $this->require_string($voie, 'voie_id', 'voie.voie_id');
        $code = $this->require_string($voie, 'code', 'voie.code');

        if (!isset($manifestitem['voie_id']) || trim((string)$manifestitem['voie_id']) === '') {
            $manifestitem['voie_id'] = $voieid;
        }

        if (!isset($manifestitem['code']) || trim((string)$manifestitem['code']) === '') {
            $manifestitem['code'] = $code;
        }

        if (!isset($manifestitem['course_prefix']) || trim((string)$manifestitem['course_prefix']) === '') {
            $manifestitem['course_prefix'] = $code;
        }

        if (!isset($manifestitem['category_idnumber']) || trim((string)$manifestitem['category_idnumber']) === '') {
            $manifestitem['category_idnumber'] = voie_slugger::course_prefix_to_category_idnumber($code);
        }

        if (!isset($manifestitem['nom']) || trim((string)$manifestitem['nom']) === '') {
            $manifestitem['nom'] = $this->clean_string((string)($voie['nom'] ?? ''));
        }

        if (!isset($manifestitem['file']) || trim((string)$manifestitem['file']) === '') {
            $manifestitem['file'] = voie_slugger::voie_id_to_file($voieid);
        }

        return $manifestitem;
    }

    /**
     * Build a Moodle course summary from Atlas data.
     *
     * @param array<string, mixed> $voie Normalized Atlas Voie.
     * @param array<string, mixed> $course Atlas conceptual course.
     * @return string
     */
    private function build_course_summary(array $voie, array $course): string {
        $parts = [];

        if (!empty($voie['nom'])) {
            $parts[] = 'Voie : ' . $this->clean_string((string)$voie['nom']);
        }

        if (!empty($voie['definition_courte'])) {
            $parts[] = $this->clean_string((string)$voie['definition_courte']);
        }

        if (isset($course['concept_maitre']) && is_array($course['concept_maitre'])) {
            $master = $course['concept_maitre'];

            if (!empty($master['nom'])) {
                $parts[] = 'Concept maître : ' . $this->clean_string((string)$master['nom']);
            }

            if (!empty($master['definition_courte'])) {
                $parts[] = $this->clean_string((string)$master['definition_courte']);
            }
        }

        if (isset($course['artefact_maitrise']) && is_array($course['artefact_maitrise'])) {
            $artefact = $course['artefact_maitrise'];

            if (!empty($artefact['nom'])) {
                $parts[] = 'Artefact de maîtrise : ' . $this->clean_string((string)$artefact['nom']);
            }

            if (!empty($artefact['description'])) {
                $parts[] = $this->clean_string((string)$artefact['description']);
            }
        }

        return implode(self::SUMMARY_SEPARATOR, $this->clean_string_list($parts));
    }

    /**
     * Build course tags from Voie and course data.
     *
     * @param array<string, mixed> $voie Normalized Atlas Voie.
     * @param array<string, mixed> $course Atlas conceptual course.
     * @return array<int, string>
     */
    private function map_course_tags(array $voie, array $course): array {
        $tags = [];

        if (isset($voie['tags']) && is_array($voie['tags'])) {
            $tags = array_merge($tags, $voie['tags']);
        }

        if (isset($voie['code'])) {
            $tags[] = (string)$voie['code'];
        }

        if (isset($course['cours_id'])) {
            $tags[] = (string)$course['cours_id'];
        }

        if (isset($course['concept_maitre']) && is_array($course['concept_maitre'])) {
            if (!empty($course['concept_maitre']['concept_id'])) {
                $tags[] = (string)$course['concept_maitre']['concept_id'];
            }
            if (!empty($course['concept_maitre']['nom'])) {
                $tags[] = (string)$course['concept_maitre']['nom'];
            }
        }

        return array_values(array_unique($this->clean_string_list($tags)));
    }

    /**
     * Build orientation section summary.
     *
     * @param array<string, mixed> $course Atlas conceptual course.
     * @param array<string, mixed> $master Master concept.
     * @return string
     */
    private function build_orientation_section_summary(array $course, array $master): string {
        $parts = [];

        if (!empty($course['nom'])) {
            $parts[] = $this->clean_string((string)$course['nom']);
        }

        if (!empty($master['definition_courte'])) {
            $parts[] = $this->clean_string((string)$master['definition_courte']);
        }

        return implode(self::SUMMARY_SEPARATOR, $this->clean_string_list($parts));
    }

    /**
     * Build master concept section summary.
     *
     * @param array<string, mixed> $master Master concept.
     * @return string
     */
    private function build_master_concept_section_summary(array $master): string {
        $parts = [];

        if (!empty($master['nom'])) {
            $parts[] = 'Concept maître : ' . $this->clean_string((string)$master['nom']);
        }

        if (!empty($master['definition_courte'])) {
            $parts[] = $this->clean_string((string)$master['definition_courte']);
        }

        if (!empty($master['fonction_pedagogique'])) {
            $parts[] = 'Fonction pédagogique : ' . $this->clean_string((string)$master['fonction_pedagogique']);
        }

        return implode(self::SUMMARY_SEPARATOR, $this->clean_string_list($parts));
    }

    /**
     * Build associated concepts section summary.
     *
     * @param array<string, mixed> $course Atlas conceptual course.
     * @return string
     */
    private function build_associated_concepts_section_summary(array $course): string {
        if (!isset($course['concepts_associes']) || !is_array($course['concepts_associes'])) {
            return '';
        }

        $items = [];
        foreach ($course['concepts_associes'] as $concept) {
            if (!is_array($concept)) {
                continue;
            }

            $name = $this->clean_string((string)($concept['nom'] ?? ''));
            if ($name === '') {
                continue;
            }

            $definition = $this->clean_string((string)($concept['definition_courte'] ?? ''));
            $items[] = $definition === '' ? '- ' . $name : '- ' . $name . ' : ' . $definition;
        }

        return implode("\n", $items);
    }

    /**
     * Build mastery artifact section summary.
     *
     * @param array<string, mixed> $course Atlas conceptual course.
     * @return string
     */
    private function build_artefact_section_summary(array $course): string {
        if (!isset($course['artefact_maitrise']) || !is_array($course['artefact_maitrise'])) {
            return '';
        }

        $artefact = $course['artefact_maitrise'];
        $parts = [];

        if (!empty($artefact['nom'])) {
            $parts[] = $this->clean_string((string)$artefact['nom']);
        }

        if (!empty($artefact['type'])) {
            $parts[] = 'Type : ' . $this->clean_string((string)$artefact['type']);
        }

        if (!empty($artefact['description'])) {
            $parts[] = $this->clean_string((string)$artefact['description']);
        }

        return implode(self::SUMMARY_SEPARATOR, $this->clean_string_list($parts));
    }

    /**
     * Build criteria section summary.
     *
     * @param array<string, mixed> $course Atlas conceptual course.
     * @return string
     */
    private function build_criteria_section_summary(array $course): string {
        if (!isset($course['criteres_passage']) || !is_array($course['criteres_passage'])) {
            return '';
        }

        $items = [];
        foreach ($course['criteres_passage'] as $criterion) {
            if (is_array($criterion) || is_object($criterion)) {
                continue;
            }

            $criterion = $this->clean_string((string)$criterion);
            if ($criterion !== '') {
                $items[] = '- ' . $criterion;
            }
        }

        return implode("\n", $items);
    }

    /**
     * Return a positive course order.
     *
     * @param array<string, mixed> $course Atlas conceptual course.
     * @return int
     */
    private function get_course_order(array $course): int {
        if (!array_key_exists('ordre', $course)) {
            throw new \coding_exception('Atlas course ordre is required for Moodle mapping.');
        }

        if (is_int($course['ordre'])) {
            $order = $course['ordre'];
        } else if (is_string($course['ordre']) && preg_match('/^[0-9]+$/', $course['ordre'])) {
            $order = (int)$course['ordre'];
        } else {
            throw new \coding_exception('Atlas course ordre must be an integer for Moodle mapping.');
        }

        if ($order < 1) {
            throw new \coding_exception('Atlas course ordre must be positive for Moodle mapping.');
        }

        return $order;
    }

    /**
     * Return manifest sortorder.
     *
     * @param array<string, mixed> $manifestitem Manifest item.
     * @return int
     */
    private function get_sortorder(array $manifestitem): int {
        if (!isset($manifestitem['sortorder'])) {
            return 0;
        }

        if (is_int($manifestitem['sortorder'])) {
            return $manifestitem['sortorder'];
        }

        if (is_string($manifestitem['sortorder']) && preg_match('/^[0-9]+$/', $manifestitem['sortorder'])) {
            return (int)$manifestitem['sortorder'];
        }

        return 0;
    }

    /**
     * Require a non-empty string field.
     *
     * @param array<string, mixed> $data Data source.
     * @param string $field Field name.
     * @param string $path Field path for errors.
     * @return string
     */
    private function require_string(array $data, string $field, string $path): string {
        if (!array_key_exists($field, $data) || !is_string($data[$field]) || trim($data[$field]) === '') {
            throw new \coding_exception($path . ' is required for Moodle mapping.');
        }

        return trim($data[$field]);
    }

    /**
     * Return first non-empty string from a candidate list.
     *
     * @param array<int, mixed> $values Candidate values.
     * @return string
     */
    private function first_non_empty_string(array $values): string {
        foreach ($values as $value) {
            if (is_string($value) || is_numeric($value)) {
                $value = $this->clean_string((string)$value);
                if ($value !== '') {
                    return $value;
                }
            }
        }

        return '';
    }

    /**
     * Normalize a string for Moodle mapping arrays.
     *
     * @param string $value Raw value.
     * @return string
     */
    private function clean_string(string $value): string {
        $value = str_replace(["\r\n", "\r"], "\n", $value);
        $value = preg_replace('/[ \t]+/', ' ', $value);
        $value = preg_replace("/\n{3,}/", "\n\n", $value);

        if ($value === null) {
            return '';
        }

        return trim($value);
    }

    /**
     * Normalize a list of strings.
     *
     * @param array<int, mixed> $items Raw values.
     * @return array<int, string>
     */
    private function clean_string_list(array $items): array {
        $clean = [];

        foreach ($items as $item) {
            if (is_array($item) || is_object($item)) {
                continue;
            }

            $item = $this->clean_string((string)$item);
            if ($item !== '') {
                $clean[] = $item;
            }
        }

        return array_values($clean);
    }

    /**
     * Normalize custom field mapping entries.
     *
     * @param array<int, array<string, mixed>> $fields Raw custom field items.
     * @return array<int, array<string, string>>
     */
    private function clean_custom_fields(array $fields): array {
        $clean = [];
        $seen = [];

        foreach ($fields as $field) {
            $shortname = $this->clean_string((string)($field['shortname'] ?? ''));
            $value = $this->clean_string((string)($field['value'] ?? ''));

            if ($shortname === '' || isset($seen[$shortname])) {
                continue;
            }

            $clean[] = [
                'shortname' => $shortname,
                'value' => $value,
            ];
            $seen[$shortname] = true;
        }

        return $clean;
    }

    /**
     * Validate Moodle category idnumber shape.
     *
     * @param string $categoryidnumber Raw idnumber.
     * @return string
     */
    private function clean_category_idnumber(string $categoryidnumber): string {
        $categoryidnumber = trim($categoryidnumber);

        if (!preg_match('/^UCKK-[A-Z0-9-]+$/', $categoryidnumber)) {
            throw new \coding_exception('Invalid Moodle category idnumber for Atlas mapping: ' . $categoryidnumber);
        }

        return $categoryidnumber;
    }

    /**
     * Validate Moodle course idnumber shape for Atlas conceptual courses.
     *
     * @param string $courseidnumber Raw idnumber.
     * @return string
     */
    private function clean_course_idnumber(string $courseidnumber): string {
        $courseidnumber = trim($courseidnumber);

        if (!preg_match('/^[A-Z][A-Z0-9]{1,15}[0-9]{3}$/', $courseidnumber)) {
            throw new \coding_exception('Invalid Moodle course idnumber for Atlas mapping: ' . $courseidnumber);
        }

        return $courseidnumber;
    }
}

