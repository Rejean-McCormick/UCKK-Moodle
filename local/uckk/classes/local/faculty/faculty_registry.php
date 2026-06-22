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
 * Faculty registry for local_uckk.
 *
 * The registry is the slug resolution layer for public faculty pages.
 * It reads only the faculty manifest index and returns minimal metadata
 * needed by controllers and repositories.
 *
 * It does not:
 * - read full *.faculty.json profile files;
 * - read full voie_*.json Atlas files;
 * - render HTML;
 * - query Moodle courses, forums, calendars or categories;
 * - check capabilities;
 * - accept file paths from request parameters;
 * - infer unknown slugs.
 *
 * @package    local_uckk
 * @copyright  2026 Univers-Cité King Klown
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace local_uckk\local\faculty;

use coding_exception;

defined('MOODLE_INTERNAL') || die();

/**
 * Registry for canonical faculty manifest entries.
 */
final class faculty_registry {
    /** Moodle component name. */
    public const COMPONENT = 'local_uckk';

    /** Status: draft. */
    public const STATUS_DRAFT = faculty_manifest::STATUS_DRAFT;

    /** Status: published. */
    public const STATUS_PUBLISHED = faculty_manifest::STATUS_PUBLISHED;

    /** Status: archived. */
    public const STATUS_ARCHIVED = faculty_manifest::STATUS_ARCHIVED;

    /** Visibility: public. */
    public const VISIBILITY_PUBLIC = faculty_manifest::VISIBILITY_PUBLIC;

    /** Visibility: hidden. */
    public const VISIBILITY_HIDDEN = faculty_manifest::VISIBILITY_HIDDEN;

    /** Visibility: restricted. */
    public const VISIBILITY_RESTRICTED = faculty_manifest::VISIBILITY_RESTRICTED;

    /**
     * Manifest loader.
     *
     * @var faculty_manifest
     */
    private faculty_manifest $manifest;

    /**
     * Constructor.
     *
     * @param faculty_manifest|null $manifest Optional manifest loader, useful for tests.
     */
    public function __construct(?faculty_manifest $manifest = null) {
        $this->manifest = $manifest ?? new faculty_manifest();
    }

    /**
     * Return all canonical faculty registry entries.
     *
     * Canonical static method required by DOC_12.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function all(): array {
        return self::instance()->get_all();
    }

    /**
     * Resolve a slug to its canonical registry entry.
     *
     * Canonical static method required by DOC_12.
     *
     * @param string $slug Faculty slug.
     * @return array<string, mixed>
     */
    public static function get_by_slug(string $slug): array {
        return self::instance()->resolve_by_slug($slug);
    }

    /**
     * Resolve a faculty id to its canonical registry entry.
     *
     * Canonical static method required by DOC_12.
     *
     * @param string $facultyid Faculty id.
     * @return array<string, mixed>
     */
    public static function get_by_faculty_id(string $facultyid): array {
        return self::instance()->resolve_by_faculty_id($facultyid);
    }

    /**
     * Return whether a slug exists in faculty_manifest.json.
     *
     * Canonical static method required by DOC_12.
     *
     * @param string $slug Faculty slug.
     * @return bool
     */
    public static function exists_slug(string $slug): bool {
        return self::instance()->has_slug($slug);
    }

    /**
     * Return whether a faculty id exists in faculty_manifest.json.
     *
     * @param string $facultyid Faculty id.
     * @return bool
     */
    public static function exists_faculty_id(string $facultyid): bool {
        return self::instance()->has_faculty_id($facultyid);
    }

    /**
     * Return whether a slug is published and public.
     *
     * @param string $slug Faculty slug.
     * @return bool
     */
    public static function is_public_slug(string $slug): bool {
        return self::instance()->is_public($slug);
    }

    /**
     * Return only published public registry entries.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function public_items(): array {
        return self::instance()->get_public_items();
    }

    /**
     * Return all slugs listed in the manifest.
     *
     * @return array<int, string>
     */
    public static function slugs(): array {
        return self::instance()->get_slugs();
    }

    /**
     * Create the default registry instance.
     *
     * @return self
     */
    private static function instance(): self {
        return new self();
    }

    /**
     * Instance implementation for all().
     *
     * @return array<int, array<string, mixed>>
     */
    public function get_all(): array {
        return array_values(array_map(
            function (array $item): array {
                return $this->to_registry_record($item);
            },
            $this->manifest->get_items()
        ));
    }

    /**
     * Instance implementation for public_items().
     *
     * @return array<int, array<string, mixed>>
     */
    public function get_public_items(): array {
        return array_values(array_filter(
            $this->get_all(),
            static function (array $item): bool {
                return $item['status'] === self::STATUS_PUBLISHED
                    && $item['visibility'] === self::VISIBILITY_PUBLIC;
            }
        ));
    }

    /**
     * Instance implementation for get_by_slug().
     *
     * @param string $slug Faculty slug.
     * @return array<string, mixed>
     */
    public function resolve_by_slug(string $slug): array {
        $cleaned = self::clean_slug($slug);

        if ($cleaned === '') {
            throw new coding_exception('Invalid faculty slug format.');
        }

        $item = $this->manifest->get_by_slug($cleaned);

        if ($item === null) {
            throw new coding_exception('Unknown faculty slug: ' . $cleaned);
        }

        return $this->to_registry_record($item);
    }

    /**
     * Instance implementation for get_by_faculty_id().
     *
     * @param string $facultyid Faculty id.
     * @return array<string, mixed>
     */
    public function resolve_by_faculty_id(string $facultyid): array {
        $facultyid = trim($facultyid);

        if ($facultyid === '') {
            throw new coding_exception('Faculty id cannot be empty.');
        }

        $item = $this->manifest->get_by_faculty_id($facultyid);

        if ($item === null) {
            throw new coding_exception('Unknown faculty id: ' . $facultyid);
        }

        return $this->to_registry_record($item);
    }

    /**
     * Resolve a voie id to its canonical registry entry.
     *
     * @param string $voieid Voie id.
     * @return array<string, mixed>
     */
    public function resolve_by_voie_id(string $voieid): array {
        $voieid = trim($voieid);

        if ($voieid === '') {
            throw new coding_exception('Voie id cannot be empty.');
        }

        $item = $this->manifest->get_by_voie_id($voieid);

        if ($item === null) {
            throw new coding_exception('Unknown voie id: ' . $voieid);
        }

        return $this->to_registry_record($item);
    }

    /**
     * Return whether a slug exists.
     *
     * @param string $slug Faculty slug.
     * @return bool
     */
    public function has_slug(string $slug): bool {
        $cleaned = self::clean_slug($slug);

        if ($cleaned === '') {
            return false;
        }

        return $this->manifest->get_by_slug($cleaned) !== null;
    }

    /**
     * Return whether a faculty id exists.
     *
     * @param string $facultyid Faculty id.
     * @return bool
     */
    public function has_faculty_id(string $facultyid): bool {
        $facultyid = trim($facultyid);

        if ($facultyid === '') {
            return false;
        }

        return $this->manifest->get_by_faculty_id($facultyid) !== null;
    }

    /**
     * Return whether a slug is published and public.
     *
     * @param string $slug Faculty slug.
     * @return bool
     */
    public function is_public(string $slug): bool {
        $cleaned = self::clean_slug($slug);

        if ($cleaned === '') {
            return false;
        }

        $item = $this->manifest->get_by_slug($cleaned);

        if ($item === null) {
            return false;
        }

        return $item['status'] === self::STATUS_PUBLISHED
            && $item['visibility'] === self::VISIBILITY_PUBLIC;
    }

    /**
     * Return whether a slug is published and restricted.
     *
     * Restricted pages are valid registry entries but must be handled by
     * the controller with require_login().
     *
     * @param string $slug Faculty slug.
     * @return bool
     */
    public function is_restricted(string $slug): bool {
        $cleaned = self::clean_slug($slug);

        if ($cleaned === '') {
            return false;
        }

        $item = $this->manifest->get_by_slug($cleaned);

        if ($item === null) {
            return false;
        }

        return $item['status'] === self::STATUS_PUBLISHED
            && $item['visibility'] === self::VISIBILITY_RESTRICTED;
    }

    /**
     * Return all slugs sorted by manifest sortorder.
     *
     * @return array<int, string>
     */
    public function get_slugs(): array {
        return array_values(array_map(
            static function (array $item): string {
                return $item['slug'];
            },
            $this->get_all()
        ));
    }

    /**
     * Return the canonical faculty file for a slug.
     *
     * @param string $slug Faculty slug.
     * @return string
     */
    public function get_faculty_file_by_slug(string $slug): string {
        $item = $this->resolve_by_slug($slug);

        return $item['faculty_file'];
    }

    /**
     * Return the canonical Atlas file for a slug.
     *
     * @param string $slug Faculty slug.
     * @return string
     */
    public function get_atlas_file_by_slug(string $slug): string {
        $item = $this->resolve_by_slug($slug);

        return $item['atlas_file'];
    }

    /**
     * Return the canonical faculty file for a faculty id.
     *
     * @param string $facultyid Faculty id.
     * @return string
     */
    public function get_faculty_file_by_faculty_id(string $facultyid): string {
        $item = $this->resolve_by_faculty_id($facultyid);

        return $item['faculty_file'];
    }

    /**
     * Return the canonical Atlas file for a faculty id.
     *
     * @param string $facultyid Faculty id.
     * @return string
     */
    public function get_atlas_file_by_faculty_id(string $facultyid): string {
        $item = $this->resolve_by_faculty_id($facultyid);

        return $item['atlas_file'];
    }

    /**
     * Convert a manifest item into the minimal registry record.
     *
     * This method intentionally keeps the same canonical field names as the
     * manifest so downstream services do not need another naming contract.
     *
     * @param array<string, mixed> $item Manifest item.
     * @return array<string, mixed>
     */
    private function to_registry_record(array $item): array {
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
                throw new coding_exception('Invalid faculty registry item; missing field: ' . $field);
            }
        }

        return [
            'faculty_id' => $item['faculty_id'],
            'voie_id' => $item['voie_id'],
            'slug' => $item['slug'],
            'faculty_file' => $item['faculty_file'],
            'atlas_file' => $item['atlas_file'],
            'status' => $item['status'],
            'visibility' => $item['visibility'],
            'category_idnumber' => $item['category_idnumber'],
            'course_prefix' => $item['course_prefix'],
            'sortorder' => $item['sortorder'],
        ];
    }

    /**
     * Clean a faculty slug without mapping aliases.
     *
     * @param string $slug Raw slug.
     * @return string
     */
    private static function clean_slug(string $slug): string {
        $slug = strtolower(trim($slug));

        if ($slug === '') {
            return '';
        }

        if (!faculty_manifest::is_valid_slug($slug)) {
            return '';
        }

        return $slug;
    }
}