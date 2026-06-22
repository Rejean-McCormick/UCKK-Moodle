<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Atlas manifest reader for the UCKK faculty pages contract.
 *
 * @package    local_uckk
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace local_uckk\local\atlas;

defined('MOODLE_INTERNAL') || die();

/**
 * Reads and validates local/uckk/atlas/atlas_manifest.json.
 *
 * The Atlas manifest is an index of the canonical Voie files. It must not
 * contain courses, public faculty sections, announcements, or rendered content.
 */
final class atlas_manifest {
    /** Plugin component. */
    public const COMPONENT = 'local_uckk';

    /** Expected manifest schema version. */
    public const SCHEMA_VERSION = 'UCKK-ATLAS-MANIFEST-0.1';

    /** Expected Atlas Voie schema version. */
    public const ATLAS_SCHEMA_VERSION = 'UCKK-ATLAS-0.2-draft';

    /** Relative manifest path from Moodle dirroot. */
    public const MANIFEST_RELATIVE_PATH = 'local/uckk/atlas/atlas_manifest.json';

    /** Relative Atlas schema path from Moodle dirroot. */
    public const SCHEMA_RELATIVE_PATH = 'local/uckk/atlas/atlas_schema.json';

    /** Relative Voies directory from Moodle dirroot. */
    public const VOIES_RELATIVE_DIR = 'local/uckk/atlas/voies';

    /** Canonical number of Atlas Voies. */
    public const EXPECTED_ITEM_COUNT = 10;

    /** Required top-level manifest fields. */
    private const REQUIRED_MANIFEST_FIELDS = [
        'schema_version',
        'atlas_schema_version',
        'items',
    ];

    /** Required fields for each manifest item. */
    private const REQUIRED_ITEM_FIELDS = [
        'voie_id',
        'code',
        'nom',
        'file',
        'course_prefix',
        'category_idnumber',
        'sortorder',
    ];

    /**
     * Cached normalized manifest.
     *
     * @var array<string, mixed>|null
     */
    private static ?array $manifest = null;

    /**
     * Cached source hash.
     *
     * @var string|null
     */
    private static ?string $sourcehash = null;

    /**
     * Return the normalized manifest.
     *
     * @return array<string, mixed>
     */
    public static function get(): array {
        if (self::$manifest === null) {
            self::$manifest = self::load();
        }

        return self::$manifest;
    }

    /**
     * Return all manifest items sorted by sortorder.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function all(): array {
        $manifest = self::get();

        return $manifest['items'];
    }

    /**
     * Return a manifest item by voie_id.
     *
     * @param string $voieid Canonical Voie id.
     * @return array<string, mixed>
     */
    public static function get_by_voie_id(string $voieid): array {
        $voieid = trim($voieid);

        foreach (self::all() as $item) {
            if ($item['voie_id'] === $voieid) {
                return $item;
            }
        }

        throw new \coding_exception('Unknown Atlas voie_id: ' . $voieid);
    }

    /**
     * Return a manifest item by file name.
     *
     * @param string $filename File name such as voie_grand_jeu_social.json.
     * @return array<string, mixed>
     */
    public static function get_by_file(string $filename): array {
        $filename = self::clean_file_name($filename);

        foreach (self::all() as $item) {
            if ($item['file'] === $filename) {
                return $item;
            }
        }

        throw new \coding_exception('Unknown Atlas Voie file: ' . $filename);
    }

    /**
     * Whether a voie_id is listed in the manifest.
     *
     * @param string $voieid Canonical Voie id.
     * @return bool
     */
    public static function exists_voie_id(string $voieid): bool {
        $voieid = trim($voieid);

        foreach (self::all() as $item) {
            if ($item['voie_id'] === $voieid) {
                return true;
            }
        }

        return false;
    }

    /**
     * Whether a Voie JSON file is listed in the manifest.
     *
     * @param string $filename File name such as voie_grand_jeu_social.json.
     * @return bool
     */
    public static function exists_file(string $filename): bool {
        try {
            $filename = self::clean_file_name($filename);
        } catch (\coding_exception $e) {
            return false;
        }

        foreach (self::all() as $item) {
            if ($item['file'] === $filename) {
                return true;
            }
        }

        return false;
    }

    /**
     * Return the absolute path to atlas_manifest.json.
     *
     * @return string
     */
    public static function manifest_path(): string {
        global $CFG;

        return $CFG->dirroot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, self::MANIFEST_RELATIVE_PATH);
    }

    /**
     * Return the absolute path to atlas_schema.json.
     *
     * @return string
     */
    public static function schema_path(): string {
        global $CFG;

        return $CFG->dirroot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, self::SCHEMA_RELATIVE_PATH);
    }

    /**
     * Return the absolute path to the Atlas Voies directory.
     *
     * @return string
     */
    public static function voies_dir(): string {
        global $CFG;

        return $CFG->dirroot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, self::VOIES_RELATIVE_DIR);
    }

    /**
     * Return the absolute path to a Voie JSON file listed in the manifest.
     *
     * @param string $filename File name such as voie_grand_jeu_social.json.
     * @return string
     */
    public static function voie_path(string $filename): string {
        $filename = self::clean_file_name($filename);

        return self::voies_dir() . DIRECTORY_SEPARATOR . $filename;
    }

    /**
     * Return a sha256 hash of the manifest source file.
     *
     * @return string
     */
    public static function source_hash(): string {
        if (self::$sourcehash === null) {
            $path = self::manifest_path();
            if (!is_readable($path)) {
                throw new \coding_exception('Atlas manifest is not readable: ' . $path);
            }

            $hash = hash_file('sha256', $path);
            if ($hash === false) {
                throw new \coding_exception('Unable to hash Atlas manifest: ' . $path);
            }

            self::$sourcehash = $hash;
        }

        return self::$sourcehash;
    }

    /**
     * Clear the in-request manifest cache.
     *
     * This is useful for PHPUnit, CLI validation, and cache purge flows.
     *
     * @return void
     */
    public static function reset_cache(): void {
        self::$manifest = null;
        self::$sourcehash = null;
    }

    /**
     * Load, decode, validate, and normalize the manifest.
     *
     * @return array<string, mixed>
     */
    private static function load(): array {
        $path = self::manifest_path();

        if (!is_readable($path)) {
            throw new \coding_exception('Atlas manifest is not readable: ' . $path);
        }

        $raw = file_get_contents($path);
        if ($raw === false) {
            throw new \coding_exception('Unable to read Atlas manifest: ' . $path);
        }

        try {
            $manifest = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new \coding_exception('Invalid Atlas manifest JSON: ' . $e->getMessage());
        }

        if (!is_array($manifest)) {
            throw new \coding_exception('Atlas manifest must decode to a JSON object.');
        }

        $manifest = self::normalize_manifest($manifest);
        self::validate_manifest($manifest);

        return $manifest;
    }

    /**
     * Normalize the manifest into stable scalar values.
     *
     * @param array<string, mixed> $manifest Raw manifest.
     * @return array<string, mixed>
     */
    private static function normalize_manifest(array $manifest): array {
        foreach (self::REQUIRED_MANIFEST_FIELDS as $field) {
            if (!array_key_exists($field, $manifest)) {
                throw new \coding_exception('Atlas manifest missing required field: ' . $field);
            }
        }

        $manifest['schema_version'] = trim((string)$manifest['schema_version']);
        $manifest['atlas_schema_version'] = trim((string)$manifest['atlas_schema_version']);

        if (!is_array($manifest['items'])) {
            throw new \coding_exception('Atlas manifest items must be an array.');
        }

        $items = [];
        foreach ($manifest['items'] as $index => $item) {
            if (!is_array($item)) {
                throw new \coding_exception('Atlas manifest item at index ' . $index . ' must be an object.');
            }

            $items[] = self::normalize_item($item, (int)$index);
        }

        usort($items, static function(array $left, array $right): int {
            return $left['sortorder'] <=> $right['sortorder'];
        });

        $manifest['items'] = $items;

        return $manifest;
    }

    /**
     * Normalize one manifest item.
     *
     * @param array<string, mixed> $item Raw item.
     * @param int $index Raw item index.
     * @return array<string, mixed>
     */
    private static function normalize_item(array $item, int $index): array {
        foreach (self::REQUIRED_ITEM_FIELDS as $field) {
            if (!array_key_exists($field, $item)) {
                throw new \coding_exception('Atlas manifest item ' . $index . ' missing required field: ' . $field);
            }
        }

        $normalized = [
            'voie_id' => trim((string)$item['voie_id']),
            'code' => trim((string)$item['code']),
            'nom' => trim((string)$item['nom']),
            'file' => self::clean_file_name((string)$item['file']),
            'course_prefix' => trim((string)$item['course_prefix']),
            'category_idnumber' => trim((string)$item['category_idnumber']),
            'sortorder' => self::clean_sortorder($item['sortorder'], $index),
        ];

        return $normalized;
    }

    /**
     * Validate the normalized manifest.
     *
     * @param array<string, mixed> $manifest Normalized manifest.
     * @return void
     */
    private static function validate_manifest(array $manifest): void {
        if ($manifest['schema_version'] !== self::SCHEMA_VERSION) {
            throw new \coding_exception(
                'Atlas manifest schema_version must be ' . self::SCHEMA_VERSION . '; got ' . $manifest['schema_version']
            );
        }

        if ($manifest['atlas_schema_version'] !== self::ATLAS_SCHEMA_VERSION) {
            throw new \coding_exception(
                'Atlas manifest atlas_schema_version must be ' . self::ATLAS_SCHEMA_VERSION . '; got ' .
                $manifest['atlas_schema_version']
            );
        }

        if (count($manifest['items']) !== self::EXPECTED_ITEM_COUNT) {
            throw new \coding_exception(
                'Atlas manifest must contain exactly ' . self::EXPECTED_ITEM_COUNT . ' items; got ' .
                count($manifest['items'])
            );
        }

        $seen = [
            'voie_id' => [],
            'code' => [],
            'file' => [],
            'course_prefix' => [],
            'category_idnumber' => [],
            'sortorder' => [],
        ];

        foreach ($manifest['items'] as $item) {
            self::validate_item($item);

            foreach (array_keys($seen) as $field) {
                $value = (string)$item[$field];
                if (isset($seen[$field][$value])) {
                    throw new \coding_exception('Duplicate Atlas manifest ' . $field . ': ' . $value);
                }
                $seen[$field][$value] = true;
            }

            $filepath = self::voie_path($item['file']);
            if (!is_readable($filepath)) {
                throw new \coding_exception('Atlas Voie file referenced by manifest is not readable: ' . $filepath);
            }
        }

        for ($sortorder = 1; $sortorder <= self::EXPECTED_ITEM_COUNT; $sortorder++) {
            if (!isset($seen['sortorder'][(string)$sortorder])) {
                throw new \coding_exception('Atlas manifest missing sortorder: ' . $sortorder);
            }
        }
    }

    /**
     * Validate one normalized manifest item.
     *
     * @param array<string, mixed> $item Normalized item.
     * @return void
     */
    private static function validate_item(array $item): void {
        if (!preg_match('/^voie_[a-z0-9_]+$/', $item['voie_id'])) {
            throw new \coding_exception('Invalid Atlas voie_id: ' . $item['voie_id']);
        }

        if (!preg_match('/^[A-Z][A-Z0-9]{1,15}$/', $item['code'])) {
            throw new \coding_exception('Invalid Atlas code for ' . $item['voie_id'] . ': ' . $item['code']);
        }

        if ($item['nom'] === '') {
            throw new \coding_exception('Atlas manifest item name cannot be empty for ' . $item['voie_id']);
        }

        if ($item['course_prefix'] !== $item['code']) {
            throw new \coding_exception('Atlas course_prefix must match code for ' . $item['voie_id']);
        }

        if (!preg_match('/^UCKK-[A-Z0-9-]+$/', $item['category_idnumber'])) {
            throw new \coding_exception(
                'Invalid Atlas category_idnumber for ' . $item['voie_id'] . ': ' . $item['category_idnumber']
            );
        }

        if ($item['sortorder'] < 1 || $item['sortorder'] > self::EXPECTED_ITEM_COUNT) {
            throw new \coding_exception('Invalid Atlas sortorder for ' . $item['voie_id'] . ': ' . $item['sortorder']);
        }
    }

    /**
     * Validate and normalize an Atlas Voie file name.
     *
     * @param string $filename Raw file name.
     * @return string
     */
    private static function clean_file_name(string $filename): string {
        $filename = trim($filename);

        if ($filename === '' || basename($filename) !== $filename) {
            throw new \coding_exception('Invalid Atlas Voie file name: ' . $filename);
        }

        if (!preg_match('/^voie_[a-z0-9_]+\.json$/', $filename)) {
            throw new \coding_exception('Invalid Atlas Voie file name: ' . $filename);
        }

        return $filename;
    }

    /**
     * Validate and normalize sortorder.
     *
     * @param mixed $sortorder Raw sortorder.
     * @param int $index Raw item index.
     * @return int
     */
    private static function clean_sortorder($sortorder, int $index): int {
        if (is_int($sortorder)) {
            return $sortorder;
        }

        if (is_string($sortorder) && preg_match('/^[0-9]+$/', $sortorder)) {
            return (int)$sortorder;
        }

        throw new \coding_exception('Atlas manifest item ' . $index . ' has invalid sortorder.');
    }
}