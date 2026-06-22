<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// at your option any later version.

/**
 * Atlas Voie validator for the UCKK faculty pages contract.
 *
 * @package    local_uckk
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace local_uckk\local\atlas;

defined('MOODLE_INTERNAL') || die();

/**
 * Validates one canonical Atlas Voie JSON payload.
 *
 * This class validates data only. It does not render HTML, query Moodle courses,
 * create Moodle records, or modify JSON files.
 */
final class voie_validator {
    /** Plugin component. */
    public const COMPONENT = 'local_uckk';

    /** Canonical Atlas schema version. */
    public const ATLAS_SCHEMA_VERSION = 'UCKK-ATLAS-0.2-draft';

    /** Canonical number of conceptual courses in one Voie. */
    public const EXPECTED_COURSE_COUNT = 10;

    /** Canonical concept type for the master concept. */
    public const CONCEPT_TYPE_MASTER = 'concept_maitre';

    /** Canonical concept type for associated concepts. */
    public const CONCEPT_TYPE_ASSOCIATED = 'concept_associe';

    /** Required top-level fields. */
    private const REQUIRED_TOP_LEVEL_FIELDS = [
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
    ];

    /** Required top-level string fields. */
    private const REQUIRED_TOP_LEVEL_STRING_FIELDS = [
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
    ];

    /** Required top-level array fields. */
    private const REQUIRED_TOP_LEVEL_ARRAY_FIELDS = [
        'seuils_progression',
        'cours_conceptuels',
        'projet_final',
        'limites_ethiques',
        'relations_intervoies',
        'tags',
    ];

    /** Optional recommended string fields. */
    private const OPTIONAL_STRING_FIELDS = [
        'titre_interne_vise',
        'version',
        'role_dans_atlas',
        'principe_fondateur',
    ];

    /** Optional recommended array fields. */
    private const OPTIONAL_ARRAY_FIELDS = [
        'distinctions_clefs',
        'risques_specifiques',
        'exigences_gouvernance',
    ];

    /** Required course fields. */
    private const REQUIRED_COURSE_FIELDS = [
        'cours_id',
        'ordre',
        'nom',
        'concept_maitre',
        'concepts_associes',
        'artefact_maitrise',
        'criteres_passage',
        'relations',
    ];

    /** Required concept_maitre fields. */
    private const REQUIRED_MASTER_CONCEPT_FIELDS = [
        'concept_id',
        'nom',
        'type',
        'definition_courte',
        'fonction_pedagogique',
    ];

    /** Required associated concept fields. */
    private const REQUIRED_ASSOCIATED_CONCEPT_FIELDS = [
        'concept_id',
        'nom',
        'type',
        'notions_fines',
    ];

    /** Required artefact_maitrise fields. */
    private const REQUIRED_ARTEFACT_FIELDS = [
        'type',
        'nom',
        'description',
    ];

    /**
     * Validate one decoded Atlas Voie payload.
     *
     * @param array<string, mixed> $voie Decoded Atlas Voie JSON.
     * @param array<string, mixed> $manifestitem Optional manifest item for this Voie.
     * @return void
     * @throws \coding_exception When validation fails.
     */
    public function validate(array $voie, array $manifestitem = []): void {
        $errors = $this->collect_errors($voie, $manifestitem);

        if ($errors !== []) {
            throw new \coding_exception('Invalid Atlas Voie: ' . implode('; ', $errors));
        }
    }

    /**
     * Validate a JSON file and return the decoded payload when valid.
     *
     * @param string $path Absolute file path.
     * @param array<string, mixed> $manifestitem Optional manifest item for this Voie.
     * @return array<string, mixed>
     * @throws \coding_exception When the file cannot be read, decoded, or validated.
     */
    public function validate_file(string $path, array $manifestitem = []): array {
        if (!is_readable($path)) {
            throw new \coding_exception('Atlas Voie file is not readable: ' . $path);
        }

        $raw = file_get_contents($path);
        if ($raw === false) {
            throw new \coding_exception('Unable to read Atlas Voie file: ' . $path);
        }

        try {
            $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new \coding_exception('Invalid Atlas Voie JSON in ' . $path . ': ' . $e->getMessage());
        }

        if (!is_array($decoded) || self::is_non_empty_list($decoded)) {
            throw new \coding_exception('Atlas Voie JSON must decode to an object: ' . $path);
        }

        $this->validate($decoded, $manifestitem);

        return $decoded;
    }

    /**
     * Return whether a decoded Atlas Voie payload is valid.
     *
     * @param array<string, mixed> $voie Decoded Atlas Voie JSON.
     * @param array<string, mixed> $manifestitem Optional manifest item for this Voie.
     * @return bool
     */
    public function is_valid(array $voie, array $manifestitem = []): bool {
        return $this->collect_errors($voie, $manifestitem) === [];
    }

    /**
     * Collect validation errors for one decoded Atlas Voie payload.
     *
     * @param array<string, mixed> $voie Decoded Atlas Voie JSON.
     * @param array<string, mixed> $manifestitem Optional manifest item for this Voie.
     * @return array<int, string>
     */
    public function collect_errors(array $voie, array $manifestitem = []): array {
        $errors = [];

        $this->validate_top_level($voie, $manifestitem, $errors);
        $this->validate_courses($voie, $manifestitem, $errors);
        $this->validate_relations_intervoies($voie, $errors);

        return $errors;
    }

    /**
     * Validate top-level Voie fields.
     *
     * @param array<string, mixed> $voie Decoded Atlas Voie JSON.
     * @param array<string, mixed> $manifestitem Optional manifest item.
     * @param array<int, string> $errors Error accumulator.
     * @return void
     */
    private function validate_top_level(array $voie, array $manifestitem, array &$errors): void {
        if (self::is_non_empty_list($voie)) {
            $errors[] = 'top-level JSON value must be an object';
            return;
        }

        self::require_fields($voie, self::REQUIRED_TOP_LEVEL_FIELDS, 'voie', $errors);

        foreach (self::REQUIRED_TOP_LEVEL_STRING_FIELDS as $field) {
            self::require_non_empty_string($voie, $field, 'voie.' . $field, $errors);
        }

        foreach (self::REQUIRED_TOP_LEVEL_ARRAY_FIELDS as $field) {
            self::require_array($voie, $field, 'voie.' . $field, $errors);
        }

        foreach (self::OPTIONAL_STRING_FIELDS as $field) {
            if (array_key_exists($field, $voie) && !is_string($voie[$field])) {
                $errors[] = 'voie.' . $field . ' must be a string when present';
            }
        }

        foreach (self::OPTIONAL_ARRAY_FIELDS as $field) {
            if (array_key_exists($field, $voie) && !is_array($voie[$field])) {
                $errors[] = 'voie.' . $field . ' must be an array when present';
            }
        }

        if (($voie['schema_version'] ?? null) !== self::ATLAS_SCHEMA_VERSION) {
            $errors[] = 'schema_version must be ' . self::ATLAS_SCHEMA_VERSION;
        }

        if (isset($voie['voie_id']) && is_string($voie['voie_id']) && !preg_match('/^voie_[a-z0-9_]+$/', $voie['voie_id'])) {
            $errors[] = 'voie_id must match /^voie_[a-z0-9_]+$/';
        }

        if (isset($voie['code']) && is_string($voie['code']) && !preg_match('/^[A-Z][A-Z0-9]{1,15}$/', $voie['code'])) {
            $errors[] = 'code must be an uppercase course prefix';
        }

        if ($manifestitem !== []) {
            $this->validate_manifest_match($voie, $manifestitem, $errors);
        }

        if (isset($voie['seuils_progression']) && is_array($voie['seuils_progression'])) {
            self::require_list_array($voie['seuils_progression'], 'voie.seuils_progression', $errors);
        }

        if (isset($voie['cours_conceptuels']) && is_array($voie['cours_conceptuels'])) {
            self::require_list_array($voie['cours_conceptuels'], 'voie.cours_conceptuels', $errors);
        }

        if (isset($voie['projet_final']) && is_array($voie['projet_final'])) {
            self::require_object_or_empty_array($voie['projet_final'], 'voie.projet_final', $errors);
        }

        if (isset($voie['limites_ethiques']) && is_array($voie['limites_ethiques'])) {
            self::require_list_array($voie['limites_ethiques'], 'voie.limites_ethiques', $errors);
            self::require_non_empty_array($voie['limites_ethiques'], 'voie.limites_ethiques', $errors);
            self::require_string_items($voie['limites_ethiques'], 'voie.limites_ethiques', $errors);
        }

        if (isset($voie['relations_intervoies']) && is_array($voie['relations_intervoies'])) {
            self::require_list_array($voie['relations_intervoies'], 'voie.relations_intervoies', $errors);
        }

        if (isset($voie['tags']) && is_array($voie['tags'])) {
            self::require_list_array($voie['tags'], 'voie.tags', $errors);
            self::require_string_items($voie['tags'], 'voie.tags', $errors);
        }
    }

    /**
     * Validate that a Voie matches the manifest item used to load it.
     *
     * @param array<string, mixed> $voie Decoded Atlas Voie JSON.
     * @param array<string, mixed> $manifestitem Manifest item.
     * @param array<int, string> $errors Error accumulator.
     * @return void
     */
    private function validate_manifest_match(array $voie, array $manifestitem, array &$errors): void {
        if (isset($manifestitem['voie_id']) && ($voie['voie_id'] ?? null) !== $manifestitem['voie_id']) {
            $errors[] = 'voie_id must match manifest voie_id';
        }

        if (isset($manifestitem['code']) && ($voie['code'] ?? null) !== $manifestitem['code']) {
            $errors[] = 'code must match manifest code';
        }

        if (
            isset($manifestitem['course_prefix'], $manifestitem['code']) &&
            $manifestitem['course_prefix'] !== $manifestitem['code']
        ) {
            $errors[] = 'manifest course_prefix must match manifest code';
        }
    }

    /**
     * Validate conceptual courses.
     *
     * @param array<string, mixed> $voie Decoded Atlas Voie JSON.
     * @param array<string, mixed> $manifestitem Optional manifest item.
     * @param array<int, string> $errors Error accumulator.
     * @return void
     */
    private function validate_courses(array $voie, array $manifestitem, array &$errors): void {
        if (!isset($voie['cours_conceptuels']) || !is_array($voie['cours_conceptuels'])) {
            return;
        }

        $courses = $voie['cours_conceptuels'];
        if (count($courses) !== self::EXPECTED_COURSE_COUNT) {
            $errors[] = 'cours_conceptuels must contain exactly ' . self::EXPECTED_COURSE_COUNT . ' courses';
        }

        $code = '';
        if (isset($manifestitem['code']) && is_string($manifestitem['code'])) {
            $code = $manifestitem['code'];
        } else if (isset($voie['code']) && is_string($voie['code'])) {
            $code = $voie['code'];
        }

        $seenorders = [];
        $seencourseids = [];

        foreach ($courses as $index => $course) {
            $path = 'voie.cours_conceptuels[' . $index . ']';

            if (!is_array($course) || self::is_non_empty_list($course)) {
                $errors[] = $path . ' must be an object';
                continue;
            }

            self::require_fields($course, self::REQUIRED_COURSE_FIELDS, $path, $errors);
            self::require_non_empty_string($course, 'cours_id', $path . '.cours_id', $errors);
            self::require_non_empty_string($course, 'nom', $path . '.nom', $errors);

            $ordre = null;
            if (!array_key_exists('ordre', $course)) {
                $errors[] = $path . '.ordre is required';
            } else if (!is_int($course['ordre'])) {
                $errors[] = $path . '.ordre must be an integer';
            } else {
                $ordre = $course['ordre'];

                if ($ordre < 1 || $ordre > self::EXPECTED_COURSE_COUNT) {
                    $errors[] = $path . '.ordre must be between 1 and ' . self::EXPECTED_COURSE_COUNT;
                }

                if (isset($seenorders[$ordre])) {
                    $errors[] = 'duplicate course ordre: ' . $ordre;
                }

                $seenorders[$ordre] = true;
            }

            if (isset($course['cours_id']) && is_string($course['cours_id'])) {
                if (isset($seencourseids[$course['cours_id']])) {
                    $errors[] = 'duplicate cours_id: ' . $course['cours_id'];
                }
                $seencourseids[$course['cours_id']] = true;

                if ($code !== '' && $ordre !== null) {
                    $expectedid = $code . (string)(100 + $ordre);
                    if ($course['cours_id'] !== $expectedid) {
                        $errors[] = $path . '.cours_id must be ' . $expectedid;
                    }
                }
            }

            $this->validate_master_concept($course, $path, $errors);
            $this->validate_associated_concepts($course, $path, $errors);
            $this->validate_artefact($course, $path, $errors);
            $this->validate_course_arrays($course, $path, $errors);
        }

        for ($order = 1; $order <= self::EXPECTED_COURSE_COUNT; $order++) {
            if (!isset($seenorders[$order])) {
                $errors[] = 'missing course ordre: ' . $order;
            }
        }
    }

    /**
     * Validate one course master concept.
     *
     * @param array<string, mixed> $course Course payload.
     * @param string $path Course path for error messages.
     * @param array<int, string> $errors Error accumulator.
     * @return void
     */
    private function validate_master_concept(array $course, string $path, array &$errors): void {
        if (!isset($course['concept_maitre'])) {
            return;
        }

        if (!is_array($course['concept_maitre']) || self::is_non_empty_list($course['concept_maitre'])) {
            $errors[] = $path . '.concept_maitre must be an object';
            return;
        }

        $concept = $course['concept_maitre'];
        $conceptpath = $path . '.concept_maitre';

        self::require_fields($concept, self::REQUIRED_MASTER_CONCEPT_FIELDS, $conceptpath, $errors);

        foreach (self::REQUIRED_MASTER_CONCEPT_FIELDS as $field) {
            self::require_non_empty_string($concept, $field, $conceptpath . '.' . $field, $errors);
        }

        if (($concept['type'] ?? null) !== self::CONCEPT_TYPE_MASTER) {
            $errors[] = $conceptpath . '.type must be ' . self::CONCEPT_TYPE_MASTER;
        }
    }

    /**
     * Validate one course associated concepts.
     *
     * @param array<string, mixed> $course Course payload.
     * @param string $path Course path for error messages.
     * @param array<int, string> $errors Error accumulator.
     * @return void
     */
    private function validate_associated_concepts(array $course, string $path, array &$errors): void {
        if (!isset($course['concepts_associes'])) {
            return;
        }

        if (!is_array($course['concepts_associes'])) {
            $errors[] = $path . '.concepts_associes must be an array';
            return;
        }

        self::require_list_array($course['concepts_associes'], $path . '.concepts_associes', $errors);

        $seenconceptids = [];

        if (
            isset($course['concept_maitre']) &&
            is_array($course['concept_maitre']) &&
            isset($course['concept_maitre']['concept_id']) &&
            is_string($course['concept_maitre']['concept_id'])
        ) {
            $masterid = trim($course['concept_maitre']['concept_id']);
            if ($masterid !== '') {
                $seenconceptids[$masterid] = true;
            }
        }

        foreach ($course['concepts_associes'] as $index => $concept) {
            $conceptpath = $path . '.concepts_associes[' . $index . ']';

            if (!is_array($concept) || self::is_non_empty_list($concept)) {
                $errors[] = $conceptpath . ' must be an object';
                continue;
            }

            self::require_fields($concept, self::REQUIRED_ASSOCIATED_CONCEPT_FIELDS, $conceptpath, $errors);
            self::require_non_empty_string($concept, 'concept_id', $conceptpath . '.concept_id', $errors);
            self::require_non_empty_string($concept, 'nom', $conceptpath . '.nom', $errors);
            self::require_non_empty_string($concept, 'type', $conceptpath . '.type', $errors);

            if (($concept['type'] ?? null) !== self::CONCEPT_TYPE_ASSOCIATED) {
                $errors[] = $conceptpath . '.type must be ' . self::CONCEPT_TYPE_ASSOCIATED;
            }

            if (!array_key_exists('notions_fines', $concept)) {
                $errors[] = $conceptpath . '.notions_fines is required';
            } else if (!is_array($concept['notions_fines'])) {
                $errors[] = $conceptpath . '.notions_fines must be an array';
            } else {
                self::require_list_array($concept['notions_fines'], $conceptpath . '.notions_fines', $errors);

                if ($concept['notions_fines'] !== []) {
                    $errors[] = $conceptpath . '.notions_fines must be empty in ' . self::ATLAS_SCHEMA_VERSION;
                }
            }

            if (isset($concept['concept_id']) && is_string($concept['concept_id'])) {
                $conceptid = trim($concept['concept_id']);

                if ($conceptid !== '') {
                    if (isset($seenconceptids[$conceptid])) {
                        $errors[] = 'duplicate concept_id in course: ' . $conceptid;
                    }

                    $seenconceptids[$conceptid] = true;
                }
            }
        }
    }

    /**
     * Validate one course mastery artefact.
     *
     * @param array<string, mixed> $course Course payload.
     * @param string $path Course path for error messages.
     * @param array<int, string> $errors Error accumulator.
     * @return void
     */
    private function validate_artefact(array $course, string $path, array &$errors): void {
        if (!isset($course['artefact_maitrise'])) {
            return;
        }

        if (!is_array($course['artefact_maitrise']) || self::is_non_empty_list($course['artefact_maitrise'])) {
            $errors[] = $path . '.artefact_maitrise must be an object';
            return;
        }

        $artefact = $course['artefact_maitrise'];
        $artefactpath = $path . '.artefact_maitrise';

        self::require_fields($artefact, self::REQUIRED_ARTEFACT_FIELDS, $artefactpath, $errors);

        foreach (self::REQUIRED_ARTEFACT_FIELDS as $field) {
            self::require_non_empty_string($artefact, $field, $artefactpath . '.' . $field, $errors);
        }
    }

    /**
     * Validate course array fields.
     *
     * @param array<string, mixed> $course Course payload.
     * @param string $path Course path for error messages.
     * @param array<int, string> $errors Error accumulator.
     * @return void
     */
    private function validate_course_arrays(array $course, string $path, array &$errors): void {
        if (!array_key_exists('criteres_passage', $course)) {
            return;
        }

        if (!is_array($course['criteres_passage'])) {
            $errors[] = $path . '.criteres_passage must be an array';
        } else {
            self::require_list_array($course['criteres_passage'], $path . '.criteres_passage', $errors);
            self::require_non_empty_array($course['criteres_passage'], $path . '.criteres_passage', $errors);
            self::require_string_items($course['criteres_passage'], $path . '.criteres_passage', $errors);
        }

        if (!array_key_exists('relations', $course)) {
            return;
        }

        if (!is_array($course['relations'])) {
            $errors[] = $path . '.relations must be an array';
        } else {
            self::require_list_array($course['relations'], $path . '.relations', $errors);
        }
    }

    /**
     * Validate inter-Voie relations.
     *
     * @param array<string, mixed> $voie Decoded Atlas Voie JSON.
     * @param array<int, string> $errors Error accumulator.
     * @return void
     */
    private function validate_relations_intervoies(array $voie, array &$errors): void {
        if (!isset($voie['relations_intervoies']) || !is_array($voie['relations_intervoies'])) {
            return;
        }

        foreach ($voie['relations_intervoies'] as $index => $relation) {
            $path = 'voie.relations_intervoies[' . $index . ']';
            $target = null;

            if (is_string($relation)) {
                $target = trim($relation);
            } else if (is_array($relation)) {
                if (self::is_non_empty_list($relation)) {
                    $errors[] = $path . ' must be an object when not a string';
                    continue;
                }

                if (!array_key_exists('voie_id', $relation)) {
                    $errors[] = $path . '.voie_id is required';
                    continue;
                }

                if (!is_string($relation['voie_id']) || trim($relation['voie_id']) === '') {
                    $errors[] = $path . '.voie_id must be a non-empty string';
                    continue;
                }

                $target = trim($relation['voie_id']);

                if (array_key_exists('relation', $relation) && !is_string($relation['relation'])) {
                    $errors[] = $path . '.relation must be a string when present';
                }
            } else {
                $errors[] = $path . ' must be an object or a voie_id string';
                continue;
            }

            if ($target === null || $target === '') {
                $errors[] = $path . ' must reference a non-empty voie_id';
                continue;
            }

            if (!preg_match('/^voie_[a-z0-9_]+$/', $target)) {
                $errors[] = $path . ' has invalid voie_id format: ' . $target;
                continue;
            }

            try {
                if (!atlas_manifest::exists_voie_id($target)) {
                    $errors[] = $path . ' references unknown voie_id: ' . $target;
                }
            } catch (\Throwable $e) {
                $errors[] = $path . ' could not be checked against atlas_manifest: ' . $e->getMessage();
            }
        }
    }

    /**
     * Require a set of fields to exist.
     *
     * @param array<string, mixed> $data Data to inspect.
     * @param array<int, string> $fields Required field names.
     * @param string $path Data path for error messages.
     * @param array<int, string> $errors Error accumulator.
     * @return void
     */
    private static function require_fields(array $data, array $fields, string $path, array &$errors): void {
        foreach ($fields as $field) {
            if (!array_key_exists($field, $data)) {
                $errors[] = $path . '.' . $field . ' is required';
            }
        }
    }

    /**
     * Require a field to be a non-empty string.
     *
     * @param array<string, mixed> $data Data to inspect.
     * @param string $field Field name.
     * @param string $path Data path for error messages.
     * @param array<int, string> $errors Error accumulator.
     * @return void
     */
    private static function require_non_empty_string(array $data, string $field, string $path, array &$errors): void {
        if (!array_key_exists($field, $data)) {
            return;
        }

        if (!is_string($data[$field]) || trim($data[$field]) === '') {
            $errors[] = $path . ' must be a non-empty string';
        }
    }

    /**
     * Require a field to be an array.
     *
     * @param array<string, mixed> $data Data to inspect.
     * @param string $field Field name.
     * @param string $path Data path for error messages.
     * @param array<int, string> $errors Error accumulator.
     * @return void
     */
    private static function require_array(array $data, string $field, string $path, array &$errors): void {
        if (!array_key_exists($field, $data)) {
            return;
        }

        if (!is_array($data[$field])) {
            $errors[] = $path . ' must be an array';
        }
    }

    /**
     * Require a list-style array.
     *
     * @param array<mixed> $array Array to inspect.
     * @param string $path Data path for error messages.
     * @param array<int, string> $errors Error accumulator.
     * @return void
     */
    private static function require_list_array(array $array, string $path, array &$errors): void {
        if (!self::is_list_array($array)) {
            $errors[] = $path . ' must be a JSON array';
        }
    }

    /**
     * Require an array to be non-empty.
     *
     * @param array<mixed> $array Array to inspect.
     * @param string $path Data path for error messages.
     * @param array<int, string> $errors Error accumulator.
     * @return void
     */
    private static function require_non_empty_array(array $array, string $path, array &$errors): void {
        if ($array === []) {
            $errors[] = $path . ' must not be empty';
        }
    }

    /**
     * Require all items in an array to be strings.
     *
     * @param array<mixed> $array Array to inspect.
     * @param string $path Data path for error messages.
     * @param array<int, string> $errors Error accumulator.
     * @return void
     */
    private static function require_string_items(array $array, string $path, array &$errors): void {
        foreach ($array as $index => $item) {
            if (!is_string($item) || trim($item) === '') {
                $errors[] = $path . '[' . $index . '] must be a non-empty string';
            }
        }
    }

    /**
     * Require a decoded JSON object or empty JSON object placeholder.
     *
     * Empty JSON objects decode to [] when json_decode(..., true) is used, so
     * empty arrays are accepted here.
     *
     * @param array<mixed> $array Array to inspect.
     * @param string $path Data path for error messages.
     * @param array<int, string> $errors Error accumulator.
     * @return void
     */
    private static function require_object_or_empty_array(array $array, string $path, array &$errors): void {
        if ($array !== [] && self::is_list_array($array)) {
            $errors[] = $path . ' must be a JSON object';
        }
    }

    /**
     * Whether an array has list-style numeric keys.
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

    /**
     * Whether an array is a non-empty list.
     *
     * @param array<mixed> $array Array to inspect.
     * @return bool
     */
    private static function is_non_empty_list(array $array): bool {
        return $array !== [] && self::is_list_array($array);
    }
}

