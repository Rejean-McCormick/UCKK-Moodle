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
 * Faculty manifest loader for local_uckk.
 *
 * The faculty manifest is the only allowed index for public faculty slugs.
 * It lists the canonical faculty profile files, Atlas files, status,
 * visibility, Moodle category idnumbers, course prefixes and display order.
 *
 * This class does not:
 * - load full faculty profile JSON files;
 * - load Atlas voie JSON files;
 * - render HTML;
 * - query Moodle courses;
 * - check capabilities;
 * - write to the database;
 * - infer slugs from request parameters outside the manifest.
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
 * Loader and validator for content/faculties/faculty_manifest.json.
 */
final class faculty_manifest {
    /** Moodle component name. */
    public const COMPONENT = 'local_uckk';

    /** Faculty manifest schema version. */
    public const SCHEMA_VERSION = 'UCKK-FACULTY-MANIFEST-0.1';

    /** Canonical manifest path, relative to dirroot. */
    public const MANIFEST_RELATIVE_PATH = 'local/uckk/content/faculties/faculty_manifest.json';

    /** Canonical faculty profile directory, relative to dirroot. */
    public const PROFILE_DIR_RELATIVE_PATH = 'local/uckk/content/faculties';

    /** Canonical Atlas voie directory, relative to dirroot. */
    public const ATLAS_VOIE_DIR_RELATIVE_PATH = 'local/uckk/atlas/voies';

    /** Number of faculty entries required by DOC_12. */
    public const EXPECTED_ITEM_COUNT = 10;

    /** Faculty status: draft. */
    public const STATUS_DRAFT = 'draft';

    /** Faculty status: published. */
    public const STATUS_PUBLISHED = 'published';

    /** Faculty status: archived. */
    public const STATUS_ARCHIVED = 'archived';

    /** Faculty visibility: public. */
    public const VISIBILITY_PUBLIC = 'public';

    /** Faculty visibility: hidden. */
    public const VISIBILITY_HIDDEN = 'hidden';

    /** Faculty visibility: restricted. */
    public const VISIBILITY_RESTRICTED = 'restricted';

    /**
     * Cached loaded manifest.
     *
     * @var array<string, mixed>|null
     */
    private ?array $manifest = null;

    /**
     * Moodle dirroot.
     *
     * @var string
     */
    private string $dirroot;

    /**
     * Constructor.
     *
     * @param string|null $dirroot Optional Moodle dirroot, useful for tests.
     */
    public function __construct(?string $dirroot = null) {
        global $CFG;

        $this->dirroot = rtrim($dirroot ?? $CFG->dirroot, DIRECTORY_SEPARATOR);
    }

    /**
     * Return the canonical manifest absolute path.
     *
     * @return string
     */
    public function get_manifest_path(): string {
        return $this->dirroot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, self::MANIFEST_RELATIVE_PATH);
    }

    /**
     * Return the canonical faculty profile directory absolute path.
     *
     * @return string
     */
    public function get_profile_dir(): string {
        return $this->dirroot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, self::PROFILE_DIR_RELATIVE_PATH);
    }

    /**
     * Return the canonical Atlas voie directory absolute path.
     *
     * @return string
     */
    public function get_atlas_voie_dir(): string {
        return $this->dirroot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, self::ATLAS_VOIE_DIR_RELATIVE_PATH);
    }

    /**
     * Load and validate the manifest.
     *
     * @param bool $force Force reload from disk.
     * @return array<string, mixed>
     */
    public function load(bool $force = false): array {
        if ($this->manifest !== null && !$force) {
            return $this->manifest;
        }

        $path = $this->get_manifest_path();

        if (!is_readable($path)) {
            throw new coding_exception('Faculty manifest file is not readable: ' . $path);
        }

        $raw = file_get_contents($path);
        if ($raw === false) {
            throw new coding_exception('Unable to read faculty manifest file: ' . $path);
        }

        try {
            $manifest = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new coding_exception(
                'Invalid JSON in faculty manifest file: ' . $path . ' — ' . $exception->getMessage()
            );
        }

        if (!is_array($manifest)) {
            throw new coding_exception('Faculty manifest root must be a JSON object: ' . $path);
        }

        $this->validate($manifest);
        $manifest['items'] = $this->sort_items($manifest['items']);

        $this->manifest = $manifest;

        return $this->manifest;
    }

    /**
     * Return all manifest items sorted by sortorder.
     *
     * @return array<int, array<string, mixed>>
     */
    public function get_items(): array {
        $manifest = $this->load();

        return $manifest['items'];
    }

    /**
     * Alias for get_items().
     *
     * @return array<int, array<string, mixed>>
     */
    public function all(): array {
        return $this->get_items();
    }

    /**
     * Return only entries that are published and public.
     *
     * @return array<int, array<string, mixed>>
     */
    public function get_public_items(): array {
        return array_values(array_filter(
            $this->get_items(),
            static function (array $item): bool {
                return $item['status'] === self::STATUS_PUBLISHED
                    && $item['visibility'] === self::VISIBILITY_PUBLIC;
            }
        ));
    }

    /**
     * Return a manifest item by slug, or null when the slug is not listed.
     *
     * @param string $slug Faculty slug.
     * @return array<string, mixed>|null
     */
    public function get_by_slug(string $slug): ?array {
        $slug = self::clean_slug($slug);

        if ($slug === '') {
            return null;
        }

        foreach ($this->get_items() as $item) {
            if ($item['slug'] === $slug) {
                return $item;
            }
        }

        return null;
    }

    /**
     * Return a manifest item by faculty_id, or null when not listed.
     *
     * @param string $facultyid Faculty id.
     * @return array<string, mixed>|null
     */
    public function get_by_faculty_id(string $facultyid): ?array {
        $facultyid = trim($facultyid);

        if ($facultyid === '') {
            return null;
        }

        foreach ($this->get_items() as $item) {
            if ($item['faculty_id'] === $facultyid) {
                return $item;
            }
        }

        return null;
    }

    /**
     * Return a manifest item by voie_id, or null when not listed.
     *
     * @param string $voieid Voie id.
     * @return array<string, mixed>|null
     */
    public function get_by_voie_id(string $voieid): ?array {
        $voieid = trim($voieid);

        if ($voieid === '') {
            return null;
        }

        foreach ($this->get_items() as $item) {
            if ($item['voie_id'] === $voieid) {
                return $item;
            }
        }

        return null;
    }

    /**
     * Return true when a slug is listed in the manifest.
     *
     * @param string $slug Faculty slug.
     * @return bool
     */
    public function has_slug(string $slug): bool {
        return $this->get_by_slug($slug) !== null;
    }

    /**
     * Return true when a slug is listed, published and public.
     *
     * @param string $slug Faculty slug.
     * @return bool
     */
    public function is_public_slug(string $slug): bool {
        $item = $this->get_by_slug($slug);

        return $item !== null
            && $item['status'] === self::STATUS_PUBLISHED
            && $item['visibility'] === self::VISIBILITY_PUBLIC;
    }

    /**
     * Return manifest slugs sorted by sortorder.
     *
     * @return array<int, string>
     */
    public function get_slugs(): array {
        return array_values(array_map(
            static function (array $item): string {
                return $item['slug'];
            },
            $this->get_items()
        ));
    }

    /**
     * Return an associative index keyed by slug.
     *
     * @return array<string, array<string, mixed>>
     */
    public function get_index_by_slug(): array {
        $index = [];

        foreach ($this->get_items() as $item) {
            $index[$item['slug']] = $item;
        }

        return $index;
    }

    /**
     * Return an associative index keyed by faculty_id.
     *
     * @return array<string, array<string, mixed>>
     */
    public function get_index_by_faculty_id(): array {
        $index = [];

        foreach ($this->get_items() as $item) {
            $index[$item['faculty_id']] = $item;
        }

        return $index;
    }

    /**
     * Return the full absolute path for a manifest faculty file.
     *
     * @param array<string, mixed> $item Manifest item.
     * @return string
     */
    public function get_faculty_file_path(array $item): string {
        if (empty($item['faculty_file']) || !is_string($item['faculty_file'])) {
            throw new coding_exception('Manifest item has no valid faculty_file value.');
        }

        return $this->get_profile_dir() . DIRECTORY_SEPARATOR . $item['faculty_file'];
    }

    /**
     * Return the full absolute path for a manifest Atlas voie file.
     *
     * @param array<string, mixed> $item Manifest item.
     * @return string
     */
    public function get_atlas_file_path(array $item): string {
        if (empty($item['atlas_file']) || !is_string($item['atlas_file'])) {
            throw new coding_exception('Manifest item has no valid atlas_file value.');
        }

        return $this->get_atlas_voie_dir() . DIRECTORY_SEPARATOR . $item['atlas_file'];
    }

    /**
     * Validate a decoded manifest.
     *
     * @param array<string, mixed> $manifest Decoded manifest.
     */
    private function validate(array $manifest): void {
        $errors = [];

        if (($manifest['schema_version'] ?? null) !== self::SCHEMA_VERSION) {
            $errors[] = 'schema_version must be ' . self::SCHEMA_VERSION;
        }

        if (!array_key_exists('generated_from', $manifest) || !is_string($manifest['generated_from'])) {
            $errors[] = 'generated_from must be present and must be a string';
        }

        if (!array_key_exists('items', $manifest) || !is_array($manifest['items'])) {
            $errors[] = 'items must be present and must be an array';
        } else {
            if (count($manifest['items']) !== self::EXPECTED_ITEM_COUNT) {
                $errors[] = 'items must contain exactly ' . self::EXPECTED_ITEM_COUNT . ' faculties';
            }

            $errors = array_merge($errors, $this->validate_items($manifest['items']));
        }

        if (!empty($errors)) {
            throw new coding_exception('Invalid faculty manifest: ' . implode('; ', $errors));
        }
    }

    /**
     * Validate all manifest items.
     *
     * @param array<int|string, mixed> $items Manifest items.
     * @return array<int, string>
     */
    private function validate_items(array $items): array {
        $errors = [];
        $seen = [
            'faculty_id' => [],
            'voie_id' => [],
            'slug' => [],
            'faculty_file' => [],
            'atlas_file' => [],
            'category_idnumber' => [],
            'course_prefix' => [],
            'sortorder' => [],
        ];

        foreach ($items as $position => $item) {
            if (!is_array($item)) {
                $errors[] = 'items[' . $position . '] must be an object';
                continue;
            }

            $errors = array_merge($errors, $this->validate_item($item, (int)$position));

            foreach (array_keys($seen) as $field) {
                if (!array_key_exists($field, $item)) {
                    continue;
                }

                $value = (string)$item[$field];

                if ($value === '') {
                    continue;
                }

                if (isset($seen[$field][$value])) {
                    $errors[] = $field . ' must be unique: ' . $value;
                }

                $seen[$field][$value] = true;
            }
        }

        return $errors;
    }

    /**
     * Validate one manifest item.
     *
     * @param array<string, mixed> $item Manifest item.
     * @param int $position Item position.
     * @return array<int, string>
     */
    private function validate_item(array $item, int $position): array {
        $errors = [];
        $prefix = 'items[' . $position . ']';

        $required = [
            'faculty_id',
            'voie_id',
            'slug',
            'faculty_file',
            'atlas_file',
            'status',
            'visibility',
            'category_idnumber',
            'course_prefix',
            'sortorder',
        ];

        foreach ($required as $field) {
            if (!array_key_exists($field, $item)) {
                $errors[] = $prefix . '.' . $field . ' is required';
            }
        }

        if (!$this->is_non_empty_string($item['faculty_id'] ?? null)) {
            $errors[] = $prefix . '.faculty_id must be a non-empty string';
        } else if (!preg_match('/^faculty_[a-z0-9_]+$/', $item['faculty_id'])) {
            $errors[] = $prefix . '.faculty_id has an invalid format';
        }

        if (!$this->is_non_empty_string($item['voie_id'] ?? null)) {
            $errors[] = $prefix . '.voie_id must be a non-empty string';
        } else if (!preg_match('/^voie_[a-z0-9_]+$/', $item['voie_id'])) {
            $errors[] = $prefix . '.voie_id has an invalid format';
        }

        if (!$this->is_non_empty_string($item['slug'] ?? null)) {
            $errors[] = $prefix . '.slug must be a non-empty string';
        } else if (!self::is_valid_slug($item['slug'])) {
            $errors[] = $prefix . '.slug has an invalid format';
        }

        if (!$this->is_non_empty_string($item['faculty_file'] ?? null)) {
            $errors[] = $prefix . '.faculty_file must be a non-empty string';
        } else if (!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*\.faculty\.json$/', $item['faculty_file'])) {
            $errors[] = $prefix . '.faculty_file has an invalid format';
        } else if (!is_readable($this->get_profile_dir() . DIRECTORY_SEPARATOR . $item['faculty_file'])) {
            $errors[] = $prefix . '.faculty_file does not exist or is not readable: ' . $item['faculty_file'];
        }

        if (!$this->is_non_empty_string($item['atlas_file'] ?? null)) {
            $errors[] = $prefix . '.atlas_file must be a non-empty string';
        } else if (!preg_match('/^voie_[a-z0-9_]+\.json$/', $item['atlas_file'])) {
            $errors[] = $prefix . '.atlas_file has an invalid format';
        } else if (!is_readable($this->get_atlas_voie_dir() . DIRECTORY_SEPARATOR . $item['atlas_file'])) {
            $errors[] = $prefix . '.atlas_file does not exist or is not readable: ' . $item['atlas_file'];
        }

        if (!$this->is_non_empty_string($item['status'] ?? null)) {
            $errors[] = $prefix . '.status must be a non-empty string';
        } else if (!in_array($item['status'], self::valid_statuses(), true)) {
            $errors[] = $prefix . '.status has an invalid value: ' . $item['status'];
        }

        if (!$this->is_non_empty_string($item['visibility'] ?? null)) {
            $errors[] = $prefix . '.visibility must be a non-empty string';
        } else if (!in_array($item['visibility'], self::valid_visibilities(), true)) {
            $errors[] = $prefix . '.visibility has an invalid value: ' . $item['visibility'];
        }

        if (!$this->is_non_empty_string($item['category_idnumber'] ?? null)) {
            $errors[] = $prefix . '.category_idnumber must be a non-empty string';
        } else if (!preg_match('/^UCKK-[A-Z0-9]+$/', $item['category_idnumber'])) {
            $errors[] = $prefix . '.category_idnumber has an invalid format';
        }

        if (!$this->is_non_empty_string($item['course_prefix'] ?? null)) {
            $errors[] = $prefix . '.course_prefix must be a non-empty string';
        } else if (!preg_match('/^[A-Z0-9]+$/', $item['course_prefix'])) {
            $errors[] = $prefix . '.course_prefix has an invalid format';
        }

        if (!is_int($item['sortorder'] ?? null)) {
            $errors[] = $prefix . '.sortorder must be an integer';
        } else if ($item['sortorder'] < 1 || $item['sortorder'] > self::EXPECTED_ITEM_COUNT) {
            $errors[] = $prefix . '.sortorder must be between 1 and ' . self::EXPECTED_ITEM_COUNT;
        }

        return $errors;
    }

    /**
     * Sort manifest items by sortorder.
     *
     * @param array<int, array<string, mixed>> $items Manifest items.
     * @return array<int, array<string, mixed>>
     */
    private function sort_items(array $items): array {
        usort($items, static function (array $left, array $right): int {
            return $left['sortorder'] <=> $right['sortorder'];
        });

        return array_values($items);
    }

    /**
     * Return valid manifest statuses.
     *
     * @return array<int, string>
     */
    public static function valid_statuses(): array {
        return [
            self::STATUS_DRAFT,
            self::STATUS_PUBLISHED,
            self::STATUS_ARCHIVED,
        ];
    }

    /**
     * Return valid manifest visibilities.
     *
     * @return array<int, string>
     */
    public static function valid_visibilities(): array {
        return [
            self::VISIBILITY_PUBLIC,
            self::VISIBILITY_HIDDEN,
            self::VISIBILITY_RESTRICTED,
        ];
    }

    /**
     * Clean a faculty slug without inventing or mapping aliases.
     *
     * @param string $slug Raw slug.
     * @return string
     */
    public static function clean_slug(string $slug): string {
        $slug = trim(\core_text::strtolower($slug));

        if (!self::is_valid_slug($slug)) {
            return '';
        }

        return $slug;
    }

    /**
     * Return true when a slug has the canonical faculty slug shape.
     *
     * @param string $slug Slug.
     * @return bool
     */
    public static function is_valid_slug(string $slug): bool {
        return preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug) === 1;
    }

    /**
     * Return true for a non-empty string.
     *
     * @param mixed $value Value.
     * @return bool
     */
    private function is_non_empty_string($value): bool {
        return is_string($value) && trim($value) !== '';
    }
}