<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Atlas Voie repository for the UCKK faculty pages contract.
 *
 * @package    local_uckk
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace local_uckk\local\atlas;

defined('MOODLE_INTERNAL') || die();

/**
 * Reads canonical Atlas Voie JSON files listed in atlas_manifest.json.
 *
 * This repository never renders HTML and never queries Moodle. It only resolves
 * manifest-approved Voie JSON files, decodes them, validates their raw shape,
 * and returns normalized arrays for downstream builders and mappers.
 */
final class voie_repository {
    /** Canonical Atlas schema version. */
    public const ATLAS_SCHEMA_VERSION = 'UCKK-ATLAS-0.2-draft';

    /** Canonical number of conceptual courses in one Voie. */
    public const EXPECTED_COURSE_COUNT = 10;

    /** Required top-level Atlas Voie fields. */
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

    /** Required course fields in cours_conceptuels. */
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

    /** Required concept_associe fields. */
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
     * Optional external validator service.
     *
     * @var object|null
     */
    private ?object $validator;

    /**
     * Optional external normalizer service.
     *
     * @var object|null
     */
    private ?object $normalizer;

    /**
     * In-request cache by Voie JSON file name.
     *
     * @var array<string, array<string, mixed>>
     */
    private array $cachebyfile = [];

    /**
     * In-request cache by voie_id.
     *
     * @var array<string, array<string, mixed>>
     */
    private array $cachebyvoieid = [];

    /**
     * Constructor.
     *
     * @param object|null $validator Optional validator implementing validate(array $voie, array $manifestitem = []).
     * @param object|null $normalizer Optional normalizer implementing normalize(array $voie, array $manifestitem = []).
     */
    public function __construct(?object $validator = null, ?object $normalizer = null) {
        $this->validator = $validator ?? self::create_optional_service(voie_validator::class);
        $this->normalizer = $normalizer ?? self::create_optional_service(voie_normalizer::class);
    }

    /**
     * Return every canonical Atlas Voie, in manifest sortorder.
     *
     * @return array<int, array<string, mixed>>
     */
    public function all(): array {
        $voies = [];

        foreach (atlas_manifest::all() as $item) {
            $voies[] = $this->get_by_file($item['file']);
        }

        return $voies;
    }

    /**
     * Return one Atlas Voie by canonical voie_id.
     *
     * @param string $voieid Canonical Voie id.
     * @return array<string, mixed>
     */
    public function get_by_voie_id(string $voieid): array {
        $voieid = self::clean_voie_id($voieid);

        if (isset($this->cachebyvoieid[$voieid])) {
            return $this->cachebyvoieid[$voieid];
        }

        $manifestitem = atlas_manifest::get_by_voie_id($voieid);

        return $this->get_by_file($manifestitem['file']);
    }

    /**
     * Return one Atlas Voie by canonical file name.
     *
     * @param string $filename File name such as voie_grand_jeu_social.json.
     * @return array<string, mixed>
     */
    public function get_by_file(string $filename): array {
        $manifestitem = atlas_manifest::get_by_file($filename);
        $filename = $manifestitem['file'];

        if (isset($this->cachebyfile[$filename])) {
            return $this->cachebyfile[$filename];
        }

        $path = atlas_manifest::voie_path($filename);
        $voie = $this->read_json_file($path);

        $this->validate_manifest_match($voie, $manifestitem, $filename);
        $this->validate_voie($voie, $manifestitem);

        $voie = $this->normalize_voie($voie, $manifestitem);

        if (!is_array($voie)) {
            throw new \coding_exception('Atlas Voie normalizer must return an array for ' . $filename);
        }

        $this->validate_manifest_match($voie, $manifestitem, $filename);

        $this->cachebyfile[$filename] = $voie;
        $this->cachebyvoieid[$voie['voie_id']] = $voie;

        return $voie;
    }

    /**
     * Clear this repository instance cache.
     *
     * @return void
     */
    public function reset_cache(): void {
        $this->cachebyfile = [];
        $this->cachebyvoieid = [];
    }

    /**
     * Read and decode a JSON file.
     *
     * @param string $path Absolute file path.
     * @return array<string, mixed>
     */
    private function read_json_file(string $path): array {
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

        if (!is_array($decoded) || self::is_list($decoded)) {
            throw new \coding_exception('Atlas Voie JSON must decode to an object: ' . $path);
        }

        return $decoded;
    }

    /**
     * Validate that the loaded Voie matches its manifest item.
     *
     * @param array<string, mixed> $voie Loaded Voie data.
     * @param array<string, mixed> $manifestitem Manifest item.
     * @param string $filename Canonical file name.
     * @return void
     */
    private function validate_manifest_match(array $voie, array $manifestitem, string $filename): void {
        if (!isset($voie['voie_id']) || trim((string)$voie['voie_id']) !== $manifestitem['voie_id']) {
            throw new \coding_exception('Atlas Voie file does not match manifest voie_id: ' . $filename);
        }

        if (!isset($voie['code']) || trim((string)$voie['code']) !== $manifestitem['code']) {
            throw new \coding_exception('Atlas Voie file does not match manifest code: ' . $filename);
        }
    }

    /**
     * Validate the loaded Voie through the optional validator, or minimal local checks.
     *
     * @param array<string, mixed> $voie Loaded Voie data.
     * @param array<string, mixed> $manifestitem Manifest item.
     * @return void
     */
    private function validate_voie(array $voie, array $manifestitem): void {
        if ($this->validator !== null && method_exists($this->validator, 'validate')) {
            $method = new \ReflectionMethod($this->validator, 'validate');

            if ($method->getNumberOfParameters() >= 2) {
                $this->validator->validate($voie, $manifestitem);
            } else {
                $this->validator->validate($voie);
            }

            return;
        }

        self::validate_minimal($voie, $manifestitem);
    }

    /**
     * Normalize the loaded Voie through the optional normalizer, or minimal local ordering.
     *
     * @param array<string, mixed> $voie Loaded Voie data.
     * @param array<string, mixed> $manifestitem Manifest item.
     * @return array<string, mixed>
     */
    private function normalize_voie(array $voie, array $manifestitem): array {
        if ($this->normalizer !== null && method_exists($this->normalizer, 'normalize')) {
            $method = new \ReflectionMethod($this->normalizer, 'normalize');

            if ($method->getNumberOfParameters() >= 2) {
                $normalized = $this->normalizer->normalize($voie, $manifestitem);
            } else {
                $normalized = $this->normalizer->normalize($voie);
            }

            if (!is_array($normalized)) {
                throw new \coding_exception('Atlas Voie normalizer must return an array.');
            }

            return $normalized;
        }

        usort($voie['cours_conceptuels'], static function(array $left, array $right): int {
            return (int)$left['ordre'] <=> (int)$right['ordre'];
        });

        return $voie;
    }

    /**
     * Minimal local validation used when voie_validator is not available yet.
     *
     * @param array<string, mixed> $voie Loaded Voie data.
     * @param array<string, mixed> $manifestitem Manifest item.
     * @return void
     */
    private static function validate_minimal(array $voie, array $manifestitem): void {
        foreach (self::REQUIRED_TOP_LEVEL_FIELDS as $field) {
            if (!array_key_exists($field, $voie)) {
                throw new \coding_exception('Atlas Voie missing required field: ' . $field);
            }
        }

        if ((string)$voie['schema_version'] !== self::ATLAS_SCHEMA_VERSION) {
            throw new \coding_exception('Invalid Atlas Voie schema_version for ' . $manifestitem['voie_id']);
        }

        foreach (['seuils_progression', 'cours_conceptuels', 'limites_ethiques', 'relations_intervoies', 'tags'] as $field) {
            if (!is_array($voie[$field])) {
                throw new \coding_exception('Atlas Voie field must be an array: ' . $field);
            }
        }

        if (!is_array($voie['projet_final']) || self::is_list($voie['projet_final'])) {
            throw new \coding_exception('Atlas Voie projet_final must be an object for ' . $manifestitem['voie_id']);
        }

        if (count($voie['cours_conceptuels']) !== self::EXPECTED_COURSE_COUNT) {
            throw new \coding_exception(
                'Atlas Voie must contain exactly ' . self::EXPECTED_COURSE_COUNT .
                ' conceptual courses: ' . $manifestitem['voie_id']
            );
        }

        self::validate_courses($voie, $manifestitem);
        self::validate_relations_intervoies($voie, $manifestitem);
    }

    /**
     * Validate conceptual courses.
     *
     * @param array<string, mixed> $voie Loaded Voie data.
     * @param array<string, mixed> $manifestitem Manifest item.
     * @return void
     */
    private static function validate_courses(array $voie, array $manifestitem): void {
        $seenorders = [];
        $code = (string)$manifestitem['code'];

        foreach ($voie['cours_conceptuels'] as $index => $course) {
            if (!is_array($course) || self::is_list($course)) {
                throw new \coding_exception('Atlas course must be an object at index ' . $index);
            }

            foreach (self::REQUIRED_COURSE_FIELDS as $field) {
                if (!array_key_exists($field, $course)) {
                    throw new \coding_exception('Atlas course missing required field ' . $field . ' at index ' . $index);
                }
            }

            $ordre = self::clean_course_order($course['ordre'], $index);
            if ($ordre < 1 || $ordre > self::EXPECTED_COURSE_COUNT) {
                throw new \coding_exception('Invalid Atlas course ordre at index ' . $index);
            }
            if (isset($seenorders[$ordre])) {
                throw new \coding_exception('Duplicate Atlas course ordre ' . $ordre . ' for ' . $manifestitem['voie_id']);
            }
            $seenorders[$ordre] = true;

            $expectedid = $code . (100 + $ordre);
            if ((string)$course['cours_id'] !== $expectedid) {
                throw new \coding_exception(
                    'Invalid Atlas cours_id for ' . $manifestitem['voie_id'] . ': expected ' . $expectedid
                );
            }

            self::validate_course_concepts($course, $manifestitem, $index);

            if (!is_array($course['artefact_maitrise']) || self::is_list($course['artefact_maitrise'])) {
                throw new \coding_exception('Atlas course artefact_maitrise must be an object for ' . $course['cours_id']);
            }
            foreach (self::REQUIRED_ARTEFACT_FIELDS as $field) {
                if (!array_key_exists($field, $course['artefact_maitrise'])) {
                    throw new \coding_exception('Atlas course artefact_maitrise missing field ' . $field);
                }
            }

            if (!is_array($course['criteres_passage'])) {
                throw new \coding_exception('Atlas course criteres_passage must be an array for ' . $course['cours_id']);
            }
            if (!is_array($course['relations'])) {
                throw new \coding_exception('Atlas course relations must be an array for ' . $course['cours_id']);
            }
        }

        for ($ordre = 1; $ordre <= self::EXPECTED_COURSE_COUNT; $ordre++) {
            if (!isset($seenorders[$ordre])) {
                throw new \coding_exception('Atlas Voie missing course ordre ' . $ordre . ' for ' . $manifestitem['voie_id']);
            }
        }
    }

    /**
     * Validate master and associated concepts for one course.
     *
     * @param array<string, mixed> $course Course data.
     * @param array<string, mixed> $manifestitem Manifest item.
     * @param int $index Course index.
     * @return void
     */
    private static function validate_course_concepts(array $course, array $manifestitem, int $index): void {
        if (!is_array($course['concept_maitre']) || self::is_list($course['concept_maitre'])) {
            throw new \coding_exception('Atlas course concept_maitre must be an object at index ' . $index);
        }

        foreach (self::REQUIRED_MASTER_CONCEPT_FIELDS as $field) {
            if (!array_key_exists($field, $course['concept_maitre'])) {
                throw new \coding_exception('Atlas concept_maitre missing required field: ' . $field);
            }
        }

        if ((string)$course['concept_maitre']['type'] !== 'concept_maitre') {
            throw new \coding_exception('Atlas concept_maitre.type must be concept_maitre for ' . $manifestitem['voie_id']);
        }

        if (!is_array($course['concepts_associes'])) {
            throw new \coding_exception('Atlas course concepts_associes must be an array for ' . $course['cours_id']);
        }

        $seenconcepts = [];
        $masterid = trim((string)$course['concept_maitre']['concept_id']);
        if ($masterid !== '') {
            $seenconcepts[$masterid] = true;
        }

        foreach ($course['concepts_associes'] as $conceptindex => $concept) {
            if (!is_array($concept) || self::is_list($concept)) {
                throw new \coding_exception('Atlas associated concept must be an object at index ' . $conceptindex);
            }

            foreach (self::REQUIRED_ASSOCIATED_CONCEPT_FIELDS as $field) {
                if (!array_key_exists($field, $concept)) {
                    throw new \coding_exception('Atlas associated concept missing required field: ' . $field);
                }
            }

            if ((string)$concept['type'] !== 'concept_associe') {
                throw new \coding_exception('Atlas associated concept type must be concept_associe for ' . $course['cours_id']);
            }

            if (!is_array($concept['notions_fines'])) {
                throw new \coding_exception('Atlas associated concept notions_fines must be an array for ' . $course['cours_id']);
            }

            $conceptid = trim((string)$concept['concept_id']);
            if ($conceptid !== '') {
                if (isset($seenconcepts[$conceptid])) {
                    throw new \coding_exception('Duplicate Atlas concept_id ' . $conceptid . ' for ' . $course['cours_id']);
                }
                $seenconcepts[$conceptid] = true;
            }
        }
    }

    /**
     * Validate inter-Voie relations when relation targets are explicit.
     *
     * @param array<string, mixed> $voie Loaded Voie data.
     * @param array<string, mixed> $manifestitem Manifest item.
     * @return void
     */
    private static function validate_relations_intervoies(array $voie, array $manifestitem): void {
        foreach ($voie['relations_intervoies'] as $index => $relation) {
            $target = null;

            if (is_string($relation)) {
                $target = trim($relation);
            } else if (is_array($relation) && isset($relation['voie_id'])) {
                $target = trim((string)$relation['voie_id']);
            }

            if ($target !== null && $target !== '' && !atlas_manifest::exists_voie_id($target)) {
                throw new \coding_exception(
                    'Invalid Atlas relation voie_id ' . $target . ' at index ' . $index . ' for ' . $manifestitem['voie_id']
                );
            }
        }
    }

    /**
     * Normalize and validate a canonical voie_id.
     *
     * @param string $voieid Raw Voie id.
     * @return string
     */
    private static function clean_voie_id(string $voieid): string {
        $voieid = trim($voieid);

        if (!preg_match('/^voie_[a-z0-9_]+$/', $voieid)) {
            throw new \coding_exception('Invalid Atlas voie_id: ' . $voieid);
        }

        return $voieid;
    }

    /**
     * Normalize a course order value.
     *
     * @param mixed $order Raw order.
     * @param int $index Course index.
     * @return int
     */
    private static function clean_course_order($order, int $index): int {
        if (is_int($order)) {
            return $order;
        }

        if (is_string($order) && preg_match('/^[0-9]+$/', $order)) {
            return (int)$order;
        }

        throw new \coding_exception('Invalid Atlas course ordre at index ' . $index);
    }

    /**
     * Create an optional local service when its class is already available.
     *
     * @param string $classname Fully qualified class name.
     * @return object|null
     */
    private static function create_optional_service(string $classname): ?object {
        if (!class_exists($classname)) {
            return null;
        }

        try {
            return new $classname();
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * PHP-version-stable array list check.
     *
     * @param array<mixed> $array Array to inspect.
     * @return bool
     */
    private static function is_list(array $array): bool {
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