<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// at your option any later version.

/**
 * Atlas Voie normalizer for the UCKK faculty pages contract.
 *
 * @package    local_uckk
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace local_uckk\local\atlas;

defined('MOODLE_INTERNAL') || die();

/**
 * Normalizes decoded Atlas Voie JSON payloads.
 *
 * This class is deliberately pure:
 * - no file reads;
 * - no database access;
 * - no Moodle course lookup;
 * - no HTML rendering;
 * - no Faculty Profile merging.
 */
final class voie_normalizer {
    /** Plugin component. */
    public const COMPONENT = 'local_uckk';

    /** Canonical Atlas schema version. */
    public const ATLAS_SCHEMA_VERSION = 'UCKK-ATLAS-0.2-draft';

    /** Canonical number of courses per Voie. */
    public const EXPECTED_COURSE_COUNT = 10;

    /** Canonical master concept type. */
    public const CONCEPT_TYPE_MASTER = 'concept_maitre';

    /** Canonical associated concept type. */
    public const CONCEPT_TYPE_ASSOCIATED = 'concept_associe';

    /** Canonical required top-level field order. */
    private const TOP_LEVEL_FIELD_ORDER = [
        'schema_version',
        'voie_id',
        'code',
        'nom',
        'domaine_operatoire',
        'niveau_vise',
        'titre_symbolique',
        'parchemin',
        'statut',
        'definition_courte',
        'angle_fondamental',
        'competence_centrale',
        'seuils_progression',
        'cours_conceptuels',
        'projet_final',
        'limites_ethiques',
        'relations_intervoies',
        'tags',
        'titre_interne_vise',
        'version',
        'role_dans_atlas',
        'principe_fondateur',
        'distinctions_clefs',
        'risques_specifiques',
        'exigences_gouvernance',
    ];

    /** Canonical course field order. */
    private const COURSE_FIELD_ORDER = [
        'cours_id',
        'ordre',
        'nom',
        'concept_maitre',
        'concepts_associes',
        'artefact_maitrise',
        'criteres_passage',
        'relations',
    ];

    /** Canonical master concept field order. */
    private const MASTER_CONCEPT_FIELD_ORDER = [
        'concept_id',
        'nom',
        'type',
        'definition_courte',
        'fonction_pedagogique',
    ];

    /** Canonical associated concept field order. */
    private const ASSOCIATED_CONCEPT_FIELD_ORDER = [
        'concept_id',
        'nom',
        'type',
        'definition_courte',
        'role_dans_le_concept_maitre',
        'notions_fines',
    ];

    /** Canonical mastery artifact field order. */
    private const ARTEFACT_FIELD_ORDER = [
        'type',
        'nom',
        'description',
    ];

    /**
     * Normalize one decoded Atlas Voie payload.
     *
     * @param array<string, mixed> $voie Decoded Atlas Voie JSON.
     * @param array<string, mixed> $manifestitem Optional manifest item for this Voie.
     * @return array<string, mixed>
     */
    public function normalize(array $voie, array $manifestitem = []): array {
        $voie = self::trim_strings_recursive($voie);

        if ($manifestitem !== []) {
            $voie = $this->apply_manifest_values($voie, $manifestitem);
        }

        $voie = $this->normalize_top_level_defaults($voie);
        $voie = $this->normalize_top_level_arrays($voie);
        $voie = $this->normalize_courses($voie);
        $voie = $this->normalize_relations_intervoies($voie);
        $voie = $this->normalize_string_lists($voie);
        $voie = $this->order_fields($voie, self::TOP_LEVEL_FIELD_ORDER);

        return $voie;
    }

    /**
     * Normalize several decoded Atlas Voie payloads.
     *
     * @param array<int, array<string, mixed>> $voies Decoded Atlas Voies.
     * @return array<int, array<string, mixed>>
     */
    public function normalize_many(array $voies): array {
        $normalized = [];

        foreach ($voies as $voie) {
            if (!is_array($voie)) {
                throw new \coding_exception('Each Atlas Voie passed to normalize_many() must be an array.');
            }

            $normalized[] = $this->normalize($voie);
        }

        usort($normalized, static function(array $left, array $right): int {
            return strcmp((string)($left['voie_id'] ?? ''), (string)($right['voie_id'] ?? ''));
        });

        return array_values($normalized);
    }

    /**
     * Apply manifest-derived values when a manifest item is provided.
     *
     * The manifest is authoritative for voie_id and code. The normalizer does
     * not invent pedagogical content from the manifest.
     *
     * @param array<string, mixed> $voie Voie payload.
     * @param array<string, mixed> $manifestitem Manifest item.
     * @return array<string, mixed>
     */
    private function apply_manifest_values(array $voie, array $manifestitem): array {
        if (isset($manifestitem['voie_id'])) {
            $voie['voie_id'] = trim((string)$manifestitem['voie_id']);
        }

        if (isset($manifestitem['code'])) {
            $voie['code'] = trim((string)$manifestitem['code']);
        }

        if (isset($manifestitem['nom']) && (!isset($voie['nom']) || trim((string)$voie['nom']) === '')) {
            $voie['nom'] = trim((string)$manifestitem['nom']);
        }

        return $voie;
    }

    /**
     * Normalize safe top-level defaults.
     *
     * Required pedagogical content is not generated here. Only contract-level
     * schema/status defaults and empty list/object defaults are added.
     *
     * @param array<string, mixed> $voie Voie payload.
     * @return array<string, mixed>
     */
    private function normalize_top_level_defaults(array $voie): array {
        $voie['schema_version'] = isset($voie['schema_version'])
            ? trim((string)$voie['schema_version'])
            : self::ATLAS_SCHEMA_VERSION;

        $voie['niveau_vise'] = isset($voie['niveau_vise'])
            ? trim((string)$voie['niveau_vise'])
            : 'Puissance opératoire';

        $voie['statut'] = isset($voie['statut'])
            ? trim((string)$voie['statut'])
            : 'Voie fondatrice UCKK';

        foreach ([
            'voie_id',
            'code',
            'nom',
            'domaine_operatoire',
            'titre_symbolique',
            'parchemin',
            'definition_courte',
            'angle_fondamental',
            'competence_centrale',
        ] as $field) {
            if (array_key_exists($field, $voie)) {
                $voie[$field] = trim((string)$voie[$field]);
            }
        }

        foreach ([
            'titre_interne_vise',
            'version',
            'role_dans_atlas',
            'principe_fondateur',
        ] as $field) {
            if (array_key_exists($field, $voie)) {
                $voie[$field] = trim((string)$voie[$field]);
            }
        }

        return $voie;
    }

    /**
     * Normalize top-level arrays and object-like arrays.
     *
     * @param array<string, mixed> $voie Voie payload.
     * @return array<string, mixed>
     */
    private function normalize_top_level_arrays(array $voie): array {
        foreach ([
            'seuils_progression',
            'cours_conceptuels',
            'limites_ethiques',
            'relations_intervoies',
            'tags',
            'distinctions_clefs',
            'risques_specifiques',
            'exigences_gouvernance',
        ] as $field) {
            if (!array_key_exists($field, $voie) || $voie[$field] === null) {
                $voie[$field] = [];
            }

            if (!is_array($voie[$field])) {
                $voie[$field] = [$voie[$field]];
            }

            if (self::is_list_array($voie[$field])) {
                $voie[$field] = array_values($voie[$field]);
            }
        }

        if (!array_key_exists('projet_final', $voie) || $voie['projet_final'] === null || !is_array($voie['projet_final'])) {
            $voie['projet_final'] = [];
        } else {
            $voie['projet_final'] = self::trim_strings_recursive($voie['projet_final']);
        }

        return $voie;
    }

    /**
     * Normalize conceptual courses.
     *
     * @param array<string, mixed> $voie Voie payload.
     * @return array<string, mixed>
     */
    private function normalize_courses(array $voie): array {
        if (!isset($voie['cours_conceptuels']) || !is_array($voie['cours_conceptuels'])) {
            $voie['cours_conceptuels'] = [];
            return $voie;
        }

        $courses = [];
        $code = isset($voie['code']) ? trim((string)$voie['code']) : '';

        foreach ($voie['cours_conceptuels'] as $index => $course) {
            if (!is_array($course)) {
                continue;
            }

            $course = $this->normalize_course($course, $index, $code);
            $courses[] = $course;
        }

        usort($courses, static function(array $left, array $right): int {
            $leftorder = isset($left['ordre']) ? (int)$left['ordre'] : PHP_INT_MAX;
            $rightorder = isset($right['ordre']) ? (int)$right['ordre'] : PHP_INT_MAX;

            if ($leftorder === $rightorder) {
                return strcmp((string)($left['cours_id'] ?? ''), (string)($right['cours_id'] ?? ''));
            }

            return $leftorder <=> $rightorder;
        });

        $voie['cours_conceptuels'] = array_values($courses);

        return $voie;
    }

    /**
     * Normalize one conceptual course.
     *
     * @param array<string, mixed> $course Course payload.
     * @param int $index Original course index.
     * @param string $code Voie course prefix.
     * @return array<string, mixed>
     */
    private function normalize_course(array $course, int $index, string $code): array {
        $course = self::trim_strings_recursive($course);

        if (isset($course['ordre'])) {
            $course['ordre'] = self::normalize_integer($course['ordre'], $index + 1);
        } else {
            $course['ordre'] = $index + 1;
        }

        if (isset($course['cours_id'])) {
            $course['cours_id'] = trim((string)$course['cours_id']);
        } else if ($code !== '' && $course['ordre'] >= 1 && $course['ordre'] <= self::EXPECTED_COURSE_COUNT) {
            $course['cours_id'] = $code . (string)(100 + (int)$course['ordre']);
        }

        if (isset($course['nom'])) {
            $course['nom'] = trim((string)$course['nom']);
        }

        $course['concept_maitre'] = $this->normalize_master_concept(
            isset($course['concept_maitre']) && is_array($course['concept_maitre']) ? $course['concept_maitre'] : []
        );

        $course['concepts_associes'] = $this->normalize_associated_concepts(
            isset($course['concepts_associes']) && is_array($course['concepts_associes']) ? $course['concepts_associes'] : []
        );

        $course['artefact_maitrise'] = $this->normalize_artefact(
            isset($course['artefact_maitrise']) && is_array($course['artefact_maitrise']) ? $course['artefact_maitrise'] : []
        );

        $course['criteres_passage'] = $this->normalize_string_list($course['criteres_passage'] ?? []);
        $course['relations'] = $this->normalize_relations($course['relations'] ?? []);

        $course = $this->order_fields($course, self::COURSE_FIELD_ORDER);

        return $course;
    }

    /**
     * Normalize a master concept object.
     *
     * @param array<string, mixed> $concept Concept payload.
     * @return array<string, mixed>
     */
    private function normalize_master_concept(array $concept): array {
        $concept = self::trim_strings_recursive($concept);

        foreach ([
            'concept_id',
            'nom',
            'definition_courte',
            'fonction_pedagogique',
        ] as $field) {
            if (array_key_exists($field, $concept)) {
                $concept[$field] = trim((string)$concept[$field]);
            }
        }

        $concept['type'] = self::CONCEPT_TYPE_MASTER;

        return $this->order_fields($concept, self::MASTER_CONCEPT_FIELD_ORDER);
    }

    /**
     * Normalize associated concepts.
     *
     * @param array<int, mixed> $concepts Associated concept payloads.
     * @return array<int, array<string, mixed>>
     */
    private function normalize_associated_concepts(array $concepts): array {
        $normalized = [];

        foreach ($concepts as $concept) {
            if (!is_array($concept)) {
                continue;
            }

            $concept = self::trim_strings_recursive($concept);

            foreach ([
                'concept_id',
                'nom',
                'definition_courte',
                'role_dans_le_concept_maitre',
            ] as $field) {
                if (array_key_exists($field, $concept)) {
                    $concept[$field] = trim((string)$concept[$field]);
                }
            }

            $concept['type'] = self::CONCEPT_TYPE_ASSOCIATED;

            if (!array_key_exists('notions_fines', $concept) || $concept['notions_fines'] === null) {
                $concept['notions_fines'] = [];
            } else {
                $concept['notions_fines'] = $this->normalize_string_list($concept['notions_fines']);
            }

            $normalized[] = $this->order_fields($concept, self::ASSOCIATED_CONCEPT_FIELD_ORDER);
        }

        return array_values($normalized);
    }

    /**
     * Normalize a mastery artifact object.
     *
     * @param array<string, mixed> $artefact Artifact payload.
     * @return array<string, mixed>
     */
    private function normalize_artefact(array $artefact): array {
        $artefact = self::trim_strings_recursive($artefact);

        foreach (self::ARTEFACT_FIELD_ORDER as $field) {
            if (array_key_exists($field, $artefact)) {
                $artefact[$field] = trim((string)$artefact[$field]);
            }
        }

        return $this->order_fields($artefact, self::ARTEFACT_FIELD_ORDER);
    }

    /**
     * Normalize inter-Voie relations.
     *
     * @param array<string, mixed> $voie Voie payload.
     * @return array<string, mixed>
     */
    private function normalize_relations_intervoies(array $voie): array {
        $voie['relations_intervoies'] = $this->normalize_relations($voie['relations_intervoies'] ?? []);

        return $voie;
    }

    /**
     * Normalize relation arrays while preserving relation semantics.
     *
     * @param mixed $relations Raw relations value.
     * @return array<int, mixed>
     */
    private function normalize_relations($relations): array {
        if ($relations === null) {
            return [];
        }

        if (!is_array($relations)) {
            return [trim((string)$relations)];
        }

        $normalized = [];

        foreach ($relations as $relation) {
            if (is_string($relation) || is_numeric($relation)) {
                $value = trim((string)$relation);
                if ($value !== '') {
                    $normalized[] = $value;
                }
                continue;
            }

            if (is_array($relation)) {
                $relation = self::trim_strings_recursive($relation);

                if (isset($relation['voie_id'])) {
                    $relation['voie_id'] = trim((string)$relation['voie_id']);
                }

                if (isset($relation['relation'])) {
                    $relation['relation'] = trim((string)$relation['relation']);
                }

                $normalized[] = $relation;
            }
        }

        return array_values($normalized);
    }

    /**
     * Normalize known top-level string-list fields.
     *
     * @param array<string, mixed> $voie Voie payload.
     * @return array<string, mixed>
     */
    private function normalize_string_lists(array $voie): array {
        foreach ([
            'seuils_progression',
            'limites_ethiques',
            'tags',
            'distinctions_clefs',
            'risques_specifiques',
            'exigences_gouvernance',
        ] as $field) {
            if (array_key_exists($field, $voie)) {
                $voie[$field] = $this->normalize_string_list($voie[$field]);
            }
        }

        return $voie;
    }

    /**
     * Normalize a scalar/list value into a clean list of non-empty strings.
     *
     * @param mixed $value Raw value.
     * @return array<int, string>
     */
    private function normalize_string_list($value): array {
        if ($value === null) {
            return [];
        }

        if (!is_array($value)) {
            $value = [$value];
        }

        $normalized = [];

        foreach ($value as $item) {
            if (is_array($item) || is_object($item)) {
                continue;
            }

            $item = trim((string)$item);

            if ($item !== '') {
                $normalized[] = $item;
            }
        }

        return array_values($normalized);
    }

    /**
     * Return an array ordered with known fields first, unknown fields preserved after.
     *
     * @param array<string, mixed> $data Data to order.
     * @param array<int, string> $fieldorder Canonical field order.
     * @return array<string, mixed>
     */
    private function order_fields(array $data, array $fieldorder): array {
        $ordered = [];

        foreach ($fieldorder as $field) {
            if (array_key_exists($field, $data)) {
                $ordered[$field] = $data[$field];
            }
        }

        foreach ($data as $field => $value) {
            if (!array_key_exists($field, $ordered)) {
                $ordered[$field] = $value;
            }
        }

        return $ordered;
    }

    /**
     * Trim every string recursively.
     *
     * @param mixed $value Raw value.
     * @return mixed
     */
    private static function trim_strings_recursive($value) {
        if (is_string($value)) {
            return trim($value);
        }

        if (!is_array($value)) {
            return $value;
        }

        $normalized = [];

        foreach ($value as $key => $item) {
            $normalized[$key] = self::trim_strings_recursive($item);
        }

        return $normalized;
    }

    /**
     * Normalize an integer value.
     *
     * @param mixed $value Raw value.
     * @param int $fallback Fallback value.
     * @return int
     */
    private static function normalize_integer($value, int $fallback): int {
        if (is_int($value)) {
            return $value;
        }

        if (is_string($value) && preg_match('/^[0-9]+$/', trim($value))) {
            return (int)trim($value);
        }

        if (is_float($value) && floor($value) === $value) {
            return (int)$value;
        }

        return $fallback;
    }

    /**
     * Whether an array has JSON-list style numeric keys.
     *
     * @param array<mixed> $array Array to inspect.
     * @return bool
     */
    private static function is_list_array(array $array): bool {
        $expected = 0;

        foreach (array_keys($array) as $key) {
            if ($key !== $expected) {
                return false;
            }

            $expected++;
        }

        return true;
    }
}