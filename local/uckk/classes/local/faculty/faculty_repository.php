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
 * Faculty profile repository for local_uckk.
 *
 * This repository loads canonical *.faculty.json files listed in
 * content/faculties/faculty_manifest.json.
 *
 * It does not:
 * - resolve slugs outside faculty_manifest.json;
 * - accept file paths from request parameters;
 * - load full Atlas voie JSON files;
 * - query Moodle categories, courses, forums or calendars;
 * - render HTML;
 * - decide permissions;
 * - mutate Moodle data.
 *
 * @package    local_uckk
 * @copyright  2026 Univers-Cité King Klown
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace local_uckk\local\faculty;

use coding_exception;
use JsonException;

defined('MOODLE_INTERNAL') || die();

/**
 * Repository for public faculty profile JSON files.
 */
final class faculty_repository {
    /** Moodle component name. */
    public const COMPONENT = 'local_uckk';

    /** Canonical faculty profile schema version. */
    public const SCHEMA_VERSION = 'UCKK-FACULTY-0.1';

    /** Source atlas sync mode allowed for public profiles. */
    public const SOURCE_ATLAS_SYNC_MODE_READ_ONLY = 'read_only';

    /**
     * Faculty registry.
     *
     * @var faculty_registry
     */
    private faculty_registry $registry;

    /**
     * Faculty manifest loader.
     *
     * @var faculty_manifest
     */
    private faculty_manifest $manifest;

    /**
     * In-memory profile cache keyed by faculty file name.
     *
     * @var array<string, array<string, mixed>>
     */
    private array $profiles = [];

    /**
     * Constructor.
     *
     * @param faculty_registry|null $registry Optional registry, useful for tests.
     * @param faculty_manifest|null $manifest Optional manifest, useful for tests.
     */
    public function __construct(?faculty_registry $registry = null, ?faculty_manifest $manifest = null) {
        $this->manifest = $manifest ?? new faculty_manifest();
        $this->registry = $registry ?? new faculty_registry($this->manifest);
    }

    /**
     * Load all faculty profiles listed in the manifest.
     *
     * @param bool $force Force reload from disk.
     * @return array<int, array<string, mixed>>
     */
    public function all(bool $force = false): array {
        $profiles = [];

        foreach ($this->registry->get_all() as $record) {
            $profiles[] = $this->load_from_registry_record($record, $force);
        }

        return $profiles;
    }

    /**
     * Alias for all().
     *
     * @param bool $force Force reload from disk.
     * @return array<int, array<string, mixed>>
     */
    public function load_all(bool $force = false): array {
        return $this->all($force);
    }

    /**
     * Load all published public faculty profiles.
     *
     * @param bool $force Force reload from disk.
     * @return array<int, array<string, mixed>>
     */
    public function public_profiles(bool $force = false): array {
        $profiles = [];

        foreach ($this->registry->get_public_items() as $record) {
            $profiles[] = $this->load_from_registry_record($record, $force);
        }

        return $profiles;
    }

    /**
     * Alias for public_profiles().
     *
     * @param bool $force Force reload from disk.
     * @return array<int, array<string, mixed>>
     */
    public function load_public(bool $force = false): array {
        return $this->public_profiles($force);
    }

    /**
     * Load one faculty profile by canonical slug.
     *
     * @param string $slug Faculty slug.
     * @param bool $force Force reload from disk.
     * @return array<string, mixed>
     */
    public function get_by_slug(string $slug, bool $force = false): array {
        $record = $this->registry->resolve_by_slug($slug);

        return $this->load_from_registry_record($record, $force);
    }

    /**
     * Alias for get_by_slug().
     *
     * @param string $slug Faculty slug.
     * @param bool $force Force reload from disk.
     * @return array<string, mixed>
     */
    public function load_by_slug(string $slug, bool $force = false): array {
        return $this->get_by_slug($slug, $force);
    }

    /**
     * Load one faculty profile by canonical faculty_id.
     *
     * @param string $facultyid Faculty id.
     * @param bool $force Force reload from disk.
     * @return array<string, mixed>
     */
    public function get_by_faculty_id(string $facultyid, bool $force = false): array {
        $record = $this->registry->resolve_by_faculty_id($facultyid);

        return $this->load_from_registry_record($record, $force);
    }

    /**
     * Alias for get_by_faculty_id().
     *
     * @param string $facultyid Faculty id.
     * @param bool $force Force reload from disk.
     * @return array<string, mixed>
     */
    public function load_by_faculty_id(string $facultyid, bool $force = false): array {
        return $this->get_by_faculty_id($facultyid, $force);
    }

    /**
     * Load one faculty profile by canonical voie_id.
     *
     * @param string $voieid Voie id.
     * @param bool $force Force reload from disk.
     * @return array<string, mixed>
     */
    public function get_by_voie_id(string $voieid, bool $force = false): array {
        $record = $this->registry->resolve_by_voie_id($voieid);

        return $this->load_from_registry_record($record, $force);
    }

    /**
     * Alias for get_by_voie_id().
     *
     * @param string $voieid Voie id.
     * @param bool $force Force reload from disk.
     * @return array<string, mixed>
     */
    public function load_by_voie_id(string $voieid, bool $force = false): array {
        return $this->get_by_voie_id($voieid, $force);
    }

    /**
     * Return true when a slug exists in the manifest.
     *
     * This does not load the profile file.
     *
     * @param string $slug Faculty slug.
     * @return bool
     */
    public function exists_slug(string $slug): bool {
        return $this->registry->has_slug($slug);
    }

    /**
     * Return true when a faculty id exists in the manifest.
     *
     * This does not load the profile file.
     *
     * @param string $facultyid Faculty id.
     * @return bool
     */
    public function exists_faculty_id(string $facultyid): bool {
        return $this->registry->has_faculty_id($facultyid);
    }

    /**
     * Return the canonical absolute profile path for a slug.
     *
     * This path is derived only from the manifest registry record.
     *
     * @param string $slug Faculty slug.
     * @return string
     */
    public function get_profile_path_by_slug(string $slug): string {
        $record = $this->registry->resolve_by_slug($slug);

        return $this->resolve_profile_path($record);
    }

    /**
     * Return the canonical absolute profile path for a faculty id.
     *
     * This path is derived only from the manifest registry record.
     *
     * @param string $facultyid Faculty id.
     * @return string
     */
    public function get_profile_path_by_faculty_id(string $facultyid): string {
        $record = $this->registry->resolve_by_faculty_id($facultyid);

        return $this->resolve_profile_path($record);
    }

    /**
     * Return a stable hash for a profile file.
     *
     * Useful for validation reports and cache invalidation. The JSON content
     * itself is not logged or exposed by this method.
     *
     * @param string $slug Faculty slug.
     * @return string
     */
    public function get_profile_hash_by_slug(string $slug): string {
        $path = $this->get_profile_path_by_slug($slug);

        $hash = sha1_file($path);
        if ($hash === false) {
            throw new coding_exception('Unable to hash faculty profile file: ' . $path);
        }

        return $hash;
    }

    /**
     * Clear the in-memory repository cache.
     */
    public function clear_cache(): void {
        $this->profiles = [];
    }

    /**
     * Load one profile from a resolved registry record.
     *
     * @param array<string, mixed> $record Registry record.
     * @param bool $force Force reload from disk.
     * @return array<string, mixed>
     */
    private function load_from_registry_record(array $record, bool $force = false): array {
        $filename = $this->get_registry_string($record, 'faculty_file');

        if (!$force && array_key_exists($filename, $this->profiles)) {
            return $this->profiles[$filename];
        }

        $path = $this->resolve_profile_path($record);
        $profile = $this->read_json_file($path);

        $this->assert_profile_matches_registry($profile, $record, $path);

        $this->profiles[$filename] = $profile;

        return $profile;
    }

    /**
     * Resolve an absolute profile path from a registry record.
     *
     * @param array<string, mixed> $record Registry record.
     * @return string
     */
    private function resolve_profile_path(array $record): string {
        $filename = $this->get_registry_string($record, 'faculty_file');

        $this->assert_safe_faculty_filename($filename);

        $path = rtrim($this->manifest->get_profile_dir(), DIRECTORY_SEPARATOR)
            . DIRECTORY_SEPARATOR
            . $filename;

        if (!is_readable($path)) {
            throw new coding_exception('Faculty profile file is not readable: ' . $path);
        }

        return $path;
    }

    /**
     * Read a JSON object file.
     *
     * @param string $path Absolute file path.
     * @return array<string, mixed>
     */
    private function read_json_file(string $path): array {
        $raw = file_get_contents($path);

        if ($raw === false) {
            throw new coding_exception('Unable to read faculty profile file: ' . $path);
        }

        try {
            $data = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new coding_exception(
                'Invalid JSON in faculty profile file: ' . $path . ' — ' . $exception->getMessage()
            );
        }

        if (!is_array($data)) {
            throw new coding_exception('Faculty profile root must be a JSON object: ' . $path);
        }

        return $data;
    }

    /**
     * Validate repository-level identity against the manifest registry record.
     *
     * Full schema validation belongs to faculty_validator. This repository
     * enforces only the invariants required to ensure the loaded file is the
     * canonical file listed for the resolved slug/faculty id.
     *
     * @param array<string, mixed> $profile Decoded faculty profile.
     * @param array<string, mixed> $record Registry record.
     * @param string $path Absolute profile path.
     */
    private function assert_profile_matches_registry(array $profile, array $record, string $path): void {
        $errors = [];

        $this->assert_required_top_level_fields($profile, $path);

        if (($profile['schema_version'] ?? null) !== self::SCHEMA_VERSION) {
            $errors[] = 'schema_version must be ' . self::SCHEMA_VERSION;
        }

        $stringfields = [
            'faculty_id',
            'voie_id',
            'slug',
            'status',
            'visibility',
        ];

        foreach ($stringfields as $field) {
            if (!array_key_exists($field, $profile) || !is_string($profile[$field]) || trim($profile[$field]) === '') {
                $errors[] = $field . ' must be a non-empty string';
            }
        }

        $comparisons = [
            'faculty_id' => 'faculty_id',
            'voie_id' => 'voie_id',
            'slug' => 'slug',
            'status' => 'status',
            'visibility' => 'visibility',
        ];

        foreach ($comparisons as $profilefield => $recordfield) {
            if (
                array_key_exists($profilefield, $profile)
                && array_key_exists($recordfield, $record)
                && $profile[$profilefield] !== $record[$recordfield]
            ) {
                $errors[] = $profilefield . ' does not match manifest value';
            }
        }

        if (!isset($profile['source_atlas']) || !is_array($profile['source_atlas'])) {
            $errors[] = 'source_atlas must be an object';
        } else {
            if (($profile['source_atlas']['file'] ?? null) !== ($record['atlas_file'] ?? null)) {
                $errors[] = 'source_atlas.file does not match manifest atlas_file';
            }

            if (
                array_key_exists('sync_mode', $profile['source_atlas'])
                && $profile['source_atlas']['sync_mode'] !== self::SOURCE_ATLAS_SYNC_MODE_READ_ONLY
            ) {
                $errors[] = 'source_atlas.sync_mode must be ' . self::SOURCE_ATLAS_SYNC_MODE_READ_ONLY;
            }
        }

        if (!isset($profile['moodle']) || !is_array($profile['moodle'])) {
            $errors[] = 'moodle must be an object';
        } else {
            if (($profile['moodle']['category_idnumber'] ?? null) !== ($record['category_idnumber'] ?? null)) {
                $errors[] = 'moodle.category_idnumber does not match manifest category_idnumber';
            }

            if (($profile['moodle']['course_prefix'] ?? null) !== ($record['course_prefix'] ?? null)) {
                $errors[] = 'moodle.course_prefix does not match manifest course_prefix';
            }
        }

        if (!empty($errors)) {
            throw new coding_exception(
                'Invalid faculty profile file: ' . $path . ' — ' . implode('; ', $errors)
            );
        }
    }

    /**
     * Ensure the profile contains the main top-level sections used downstream.
     *
     * @param array<string, mixed> $profile Decoded profile.
     * @param string $path Absolute profile path.
     */
    private function assert_required_top_level_fields(array $profile, string $path): void {
        $required = [
            'schema_version',
            'faculty_id',
            'voie_id',
            'slug',
            'status',
            'visibility',
            'source_atlas',
            'moodle',
            'identity',
            'seo',
            'hero',
            'navigation',
            'sections',
            'atlas_projection',
            'dynamic_blocks',
            'featured_blocks',
            'faq',
            'contact',
            'governance',
            'cache',
        ];

        $missing = [];

        foreach ($required as $field) {
            if (!array_key_exists($field, $profile)) {
                $missing[] = $field;
            }
        }

        if (!empty($missing)) {
            throw new coding_exception(
                'Faculty profile file is missing required top-level fields: '
                . implode(', ', $missing)
                . ' — '
                . $path
            );
        }
    }

    /**
     * Return a non-empty string from a registry record.
     *
     * @param array<string, mixed> $record Registry record.
     * @param string $field Field name.
     * @return string
     */
    private function get_registry_string(array $record, string $field): string {
        if (!array_key_exists($field, $record) || !is_string($record[$field]) || trim($record[$field]) === '') {
            throw new coding_exception('Invalid faculty registry record; missing field: ' . $field);
        }

        return $record[$field];
    }

    /**
     * Ensure a faculty file name is safe and canonical.
     *
     * @param string $filename File name.
     */
    private function assert_safe_faculty_filename(string $filename): void {
        if (basename($filename) !== $filename) {
            throw new coding_exception('Faculty file must be a file name, not a path: ' . $filename);
        }

        if (str_contains($filename, '/') || str_contains($filename, '\\')) {
            throw new coding_exception('Faculty file must not contain path separators: ' . $filename);
        }

        if (!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*\.faculty\.json$/', $filename)) {
            throw new coding_exception('Invalid faculty file name: ' . $filename);
        }
    }
}