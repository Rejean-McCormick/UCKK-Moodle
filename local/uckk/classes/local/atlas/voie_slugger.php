<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// at your option any later version.

/**
 * Atlas Voie slug and identifier helper for the UCKK faculty pages contract.
 *
 * @package    local_uckk
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace local_uckk\local\atlas;

defined('MOODLE_INTERNAL') || die();

/**
 * Converts between canonical Atlas Voie identifiers.
 *
 * This class is deliberately pure:
 * - no file reads;
 * - no database access;
 * - no Moodle course/category lookup;
 * - no rendering;
 * - no translation of IDs or slugs.
 */
final class voie_slugger {
    /** Plugin component. */
    public const COMPONENT = 'local_uckk';

    /** Prefix used by canonical Atlas voie_id values. */
    public const VOIE_ID_PREFIX = 'voie_';

    /** Prefix used by canonical public faculty_id values. */
    public const FACULTY_ID_PREFIX = 'faculty_';

    /** Atlas JSON file extension. */
    public const ATLAS_FILE_EXTENSION = '.json';

    /** Faculty JSON file extension. */
    public const FACULTY_FILE_EXTENSION = '.faculty.json';

    /** Canonical slug pattern. */
    private const SLUG_PATTERN = '/^[a-z0-9]+(?:-[a-z0-9]+)*$/';

    /** Canonical voie_id pattern. */
    private const VOIE_ID_PATTERN = '/^voie_[a-z0-9]+(?:_[a-z0-9]+)*$/';

    /** Canonical faculty_id pattern. */
    private const FACULTY_ID_PATTERN = '/^faculty_[a-z0-9]+(?:_[a-z0-9]+)*$/';

    /** Canonical Atlas file pattern. */
    private const ATLAS_FILE_PATTERN = '/^voie_[a-z0-9]+(?:_[a-z0-9]+)*\.json$/';

    /** Canonical Faculty file pattern. */
    private const FACULTY_FILE_PATTERN = '/^[a-z0-9]+(?:-[a-z0-9]+)*\.faculty\.json$/';

    /**
     * Convert a canonical public slug to a canonical Atlas voie_id.
     *
     * @param string $slug Public faculty slug, for example grand-jeu-social.
     * @return string Canonical voie_id, for example voie_grand_jeu_social.
     */
    public static function slug_to_voie_id(string $slug): string {
        $slug = self::clean_slug($slug);

        return self::VOIE_ID_PREFIX . str_replace('-', '_', $slug);
    }

    /**
     * Convert a canonical Atlas voie_id to a canonical public slug.
     *
     * @param string $voieid Canonical voie_id, for example voie_grand_jeu_social.
     * @return string Public faculty slug, for example grand-jeu-social.
     */
    public static function voie_id_to_slug(string $voieid): string {
        $voieid = self::clean_voie_id($voieid);
        $base = substr($voieid, strlen(self::VOIE_ID_PREFIX));

        return str_replace('_', '-', $base);
    }

    /**
     * Convert a canonical Atlas voie_id to an Atlas JSON file name.
     *
     * @param string $voieid Canonical voie_id.
     * @return string Atlas JSON file name, for example voie_grand_jeu_social.json.
     */
    public static function voie_id_to_file(string $voieid): string {
        $voieid = self::clean_voie_id($voieid);

        return $voieid . self::ATLAS_FILE_EXTENSION;
    }

    /**
     * Convert an Atlas JSON file name to a canonical Atlas voie_id.
     *
     * @param string $filename Atlas JSON file name.
     * @return string Canonical voie_id.
     */
    public static function file_to_voie_id(string $filename): string {
        $filename = self::clean_atlas_file($filename);

        return substr($filename, 0, -strlen(self::ATLAS_FILE_EXTENSION));
    }

    /**
     * Convert an Atlas JSON file name to a canonical public slug.
     *
     * @param string $filename Atlas JSON file name.
     * @return string Public faculty slug.
     */
    public static function file_to_slug(string $filename): string {
        return self::voie_id_to_slug(self::file_to_voie_id($filename));
    }

    /**
     * Convert a canonical public slug to an Atlas JSON file name.
     *
     * @param string $slug Public faculty slug.
     * @return string Atlas JSON file name.
     */
    public static function slug_to_file(string $slug): string {
        return self::voie_id_to_file(self::slug_to_voie_id($slug));
    }

    /**
     * Convert a canonical public slug to a canonical public faculty_id.
     *
     * @param string $slug Public faculty slug.
     * @return string Canonical faculty_id.
     */
    public static function slug_to_faculty_id(string $slug): string {
        $slug = self::clean_slug($slug);

        return self::FACULTY_ID_PREFIX . str_replace('-', '_', $slug);
    }

    /**
     * Convert a canonical public faculty_id to a canonical public slug.
     *
     * @param string $facultyid Canonical faculty_id.
     * @return string Public faculty slug.
     */
    public static function faculty_id_to_slug(string $facultyid): string {
        $facultyid = self::clean_faculty_id($facultyid);
        $base = substr($facultyid, strlen(self::FACULTY_ID_PREFIX));

        return str_replace('_', '-', $base);
    }

    /**
     * Convert a canonical public faculty_id to the linked Atlas voie_id.
     *
     * @param string $facultyid Canonical faculty_id.
     * @return string Canonical voie_id.
     */
    public static function faculty_id_to_voie_id(string $facultyid): string {
        return self::slug_to_voie_id(self::faculty_id_to_slug($facultyid));
    }

    /**
     * Convert a canonical Atlas voie_id to the linked public faculty_id.
     *
     * @param string $voieid Canonical voie_id.
     * @return string Canonical faculty_id.
     */
    public static function voie_id_to_faculty_id(string $voieid): string {
        return self::slug_to_faculty_id(self::voie_id_to_slug($voieid));
    }

    /**
     * Convert a canonical public slug to a Faculty JSON file name.
     *
     * @param string $slug Public faculty slug.
     * @return string Faculty JSON file name, for example grand-jeu-social.faculty.json.
     */
    public static function slug_to_faculty_file(string $slug): string {
        return self::clean_slug($slug) . self::FACULTY_FILE_EXTENSION;
    }

    /**
     * Convert a Faculty JSON file name to a canonical public slug.
     *
     * @param string $filename Faculty JSON file name.
     * @return string Public faculty slug.
     */
    public static function faculty_file_to_slug(string $filename): string {
        $filename = self::clean_faculty_file($filename);

        return substr($filename, 0, -strlen(self::FACULTY_FILE_EXTENSION));
    }

    /**
     * Convert a Faculty JSON file name to the linked Atlas voie_id.
     *
     * @param string $filename Faculty JSON file name.
     * @return string Canonical voie_id.
     */
    public static function faculty_file_to_voie_id(string $filename): string {
        return self::slug_to_voie_id(self::faculty_file_to_slug($filename));
    }

    /**
     * Convert a canonical Atlas voie_id to the linked Faculty JSON file name.
     *
     * @param string $voieid Canonical voie_id.
     * @return string Faculty JSON file name.
     */
    public static function voie_id_to_faculty_file(string $voieid): string {
        return self::slug_to_faculty_file(self::voie_id_to_slug($voieid));
    }

    /**
     * Return a canonical Moodle category idnumber from a course prefix.
     *
     * @param string $courseprefix Canonical Atlas course prefix.
     * @return string Category idnumber, for example UCKK-GJS.
     */
    public static function course_prefix_to_category_idnumber(string $courseprefix): string {
        $courseprefix = self::clean_course_prefix($courseprefix);

        return 'UCKK-' . $courseprefix;
    }

    /**
     * Return a course prefix from a canonical Moodle category idnumber.
     *
     * @param string $categoryidnumber Canonical category idnumber, for example UCKK-GJS.
     * @return string Course prefix, for example GJS.
     */
    public static function category_idnumber_to_course_prefix(string $categoryidnumber): string {
        $categoryidnumber = trim($categoryidnumber);

        if (!preg_match('/^UCKK-([A-Z][A-Z0-9]{1,15})$/', $categoryidnumber, $matches)) {
            throw new \coding_exception('Invalid UCKK category idnumber: ' . $categoryidnumber);
        }

        return $matches[1];
    }

    /**
     * Check whether a string is a valid canonical public slug.
     *
     * @param string $slug Candidate slug.
     * @return bool
     */
    public static function is_valid_slug(string $slug): bool {
        $slug = trim($slug);

        return preg_match(self::SLUG_PATTERN, $slug) === 1;
    }

    /**
     * Check whether a string is a valid canonical voie_id.
     *
     * @param string $voieid Candidate voie_id.
     * @return bool
     */
    public static function is_valid_voie_id(string $voieid): bool {
        $voieid = trim($voieid);

        return preg_match(self::VOIE_ID_PATTERN, $voieid) === 1;
    }

    /**
     * Check whether a string is a valid canonical faculty_id.
     *
     * @param string $facultyid Candidate faculty_id.
     * @return bool
     */
    public static function is_valid_faculty_id(string $facultyid): bool {
        $facultyid = trim($facultyid);

        return preg_match(self::FACULTY_ID_PATTERN, $facultyid) === 1;
    }

    /**
     * Check whether a string is a valid canonical Atlas JSON file name.
     *
     * @param string $filename Candidate Atlas file name.
     * @return bool
     */
    public static function is_valid_atlas_file(string $filename): bool {
        try {
            self::clean_atlas_file($filename);
            return true;
        } catch (\coding_exception $e) {
            return false;
        }
    }

    /**
     * Check whether a string is a valid canonical Faculty JSON file name.
     *
     * @param string $filename Candidate Faculty file name.
     * @return bool
     */
    public static function is_valid_faculty_file(string $filename): bool {
        try {
            self::clean_faculty_file($filename);
            return true;
        } catch (\coding_exception $e) {
            return false;
        }
    }

    /**
     * Normalize and validate a canonical slug.
     *
     * @param string $slug Raw slug.
     * @return string Clean slug.
     */
    public static function clean_slug(string $slug): string {
        $slug = trim($slug);

        if ($slug === '') {
            throw new \coding_exception('Voie slug cannot be empty.');
        }

        if (basename($slug) !== $slug) {
            throw new \coding_exception('Voie slug must not contain a path: ' . $slug);
        }

        if (!self::is_ascii_lowercase($slug)) {
            throw new \coding_exception('Voie slug must be lowercase ASCII: ' . $slug);
        }

        if (preg_match(self::SLUG_PATTERN, $slug) !== 1) {
            throw new \coding_exception('Invalid Voie slug: ' . $slug);
        }

        return $slug;
    }

    /**
     * Normalize and validate a canonical voie_id.
     *
     * @param string $voieid Raw voie_id.
     * @return string Clean voie_id.
     */
    public static function clean_voie_id(string $voieid): string {
        $voieid = trim($voieid);

        if ($voieid === '') {
            throw new \coding_exception('Atlas voie_id cannot be empty.');
        }

        if (basename($voieid) !== $voieid) {
            throw new \coding_exception('Atlas voie_id must not contain a path: ' . $voieid);
        }

        if (!self::is_ascii_lowercase($voieid)) {
            throw new \coding_exception('Atlas voie_id must be lowercase ASCII: ' . $voieid);
        }

        if (preg_match(self::VOIE_ID_PATTERN, $voieid) !== 1) {
            throw new \coding_exception('Invalid Atlas voie_id: ' . $voieid);
        }

        return $voieid;
    }

    /**
     * Normalize and validate a canonical faculty_id.
     *
     * @param string $facultyid Raw faculty_id.
     * @return string Clean faculty_id.
     */
    public static function clean_faculty_id(string $facultyid): string {
        $facultyid = trim($facultyid);

        if ($facultyid === '') {
            throw new \coding_exception('Faculty id cannot be empty.');
        }

        if (basename($facultyid) !== $facultyid) {
            throw new \coding_exception('Faculty id must not contain a path: ' . $facultyid);
        }

        if (!self::is_ascii_lowercase($facultyid)) {
            throw new \coding_exception('Faculty id must be lowercase ASCII: ' . $facultyid);
        }

        if (preg_match(self::FACULTY_ID_PATTERN, $facultyid) !== 1) {
            throw new \coding_exception('Invalid faculty_id: ' . $facultyid);
        }

        return $facultyid;
    }

    /**
     * Normalize and validate a canonical Atlas JSON file name.
     *
     * @param string $filename Raw file name.
     * @return string Clean Atlas file name.
     */
    public static function clean_atlas_file(string $filename): string {
        $filename = trim($filename);

        if ($filename === '') {
            throw new \coding_exception('Atlas file name cannot be empty.');
        }

        if (basename($filename) !== $filename) {
            throw new \coding_exception('Atlas file name must not contain a path: ' . $filename);
        }

        if (!self::is_ascii_lowercase($filename)) {
            throw new \coding_exception('Atlas file name must be lowercase ASCII: ' . $filename);
        }

        if (preg_match(self::ATLAS_FILE_PATTERN, $filename) !== 1) {
            throw new \coding_exception('Invalid Atlas file name: ' . $filename);
        }

        return $filename;
    }

    /**
     * Normalize and validate a canonical Faculty JSON file name.
     *
     * @param string $filename Raw file name.
     * @return string Clean Faculty file name.
     */
    public static function clean_faculty_file(string $filename): string {
        $filename = trim($filename);

        if ($filename === '') {
            throw new \coding_exception('Faculty file name cannot be empty.');
        }

        if (basename($filename) !== $filename) {
            throw new \coding_exception('Faculty file name must not contain a path: ' . $filename);
        }

        if (!self::is_ascii_lowercase($filename)) {
            throw new \coding_exception('Faculty file name must be lowercase ASCII: ' . $filename);
        }

        if (preg_match(self::FACULTY_FILE_PATTERN, $filename) !== 1) {
            throw new \coding_exception('Invalid Faculty file name: ' . $filename);
        }

        return $filename;
    }

    /**
     * Normalize and validate a course prefix.
     *
     * @param string $courseprefix Raw course prefix.
     * @return string Clean course prefix.
     */
    public static function clean_course_prefix(string $courseprefix): string {
        $courseprefix = trim($courseprefix);

        if ($courseprefix === '') {
            throw new \coding_exception('Course prefix cannot be empty.');
        }

        if (preg_match('/^[A-Z][A-Z0-9]{1,15}$/', $courseprefix) !== 1) {
            throw new \coding_exception('Invalid course prefix: ' . $courseprefix);
        }

        return $courseprefix;
    }

    /**
     * Build the canonical derived identifier bundle from a slug.
     *
     * @param string $slug Public faculty slug.
     * @return array<string, string>
     */
    public static function identifiers_from_slug(string $slug): array {
        $slug = self::clean_slug($slug);
        $voieid = self::slug_to_voie_id($slug);
        $facultyid = self::slug_to_faculty_id($slug);

        return [
            'slug' => $slug,
            'voie_id' => $voieid,
            'faculty_id' => $facultyid,
            'atlas_file' => self::voie_id_to_file($voieid),
            'faculty_file' => self::slug_to_faculty_file($slug),
        ];
    }

    /**
     * Build the canonical derived identifier bundle from a voie_id.
     *
     * @param string $voieid Canonical voie_id.
     * @return array<string, string>
     */
    public static function identifiers_from_voie_id(string $voieid): array {
        return self::identifiers_from_slug(self::voie_id_to_slug($voieid));
    }

    /**
     * Build the canonical derived identifier bundle from an Atlas file name.
     *
     * @param string $filename Atlas JSON file name.
     * @return array<string, string>
     */
    public static function identifiers_from_atlas_file(string $filename): array {
        return self::identifiers_from_voie_id(self::file_to_voie_id($filename));
    }

    /**
     * Ensure a manifest item is internally consistent for derived identifiers.
     *
     * This checks only derived identifiers. It does not read files and does not
     * replace the Atlas manifest validator.
     *
     * @param array<string, mixed> $manifestitem Manifest item.
     * @return void
     */
    public static function assert_manifest_item_consistent(array $manifestitem): void {
        foreach (['voie_id', 'file'] as $field) {
            if (!array_key_exists($field, $manifestitem)) {
                throw new \coding_exception('Manifest item missing required slugger field: ' . $field);
            }
        }

        $voieid = self::clean_voie_id((string)$manifestitem['voie_id']);
        $file = self::clean_atlas_file((string)$manifestitem['file']);

        if (self::voie_id_to_file($voieid) !== $file) {
            throw new \coding_exception('Manifest item file does not match voie_id: ' . $voieid);
        }

        if (array_key_exists('slug', $manifestitem)) {
            $slug = self::clean_slug((string)$manifestitem['slug']);

            if (self::voie_id_to_slug($voieid) !== $slug) {
                throw new \coding_exception('Manifest item slug does not match voie_id: ' . $voieid);
            }
        }

        if (array_key_exists('faculty_id', $manifestitem)) {
            $facultyid = self::clean_faculty_id((string)$manifestitem['faculty_id']);

            if (self::voie_id_to_faculty_id($voieid) !== $facultyid) {
                throw new \coding_exception('Manifest item faculty_id does not match voie_id: ' . $voieid);
            }
        }

        if (array_key_exists('faculty_file', $manifestitem)) {
            $facultyfile = self::clean_faculty_file((string)$manifestitem['faculty_file']);

            if (self::voie_id_to_faculty_file($voieid) !== $facultyfile) {
                throw new \coding_exception('Manifest item faculty_file does not match voie_id: ' . $voieid);
            }
        }
    }

    /**
     * Check whether a string is lowercase ASCII.
     *
     * @param string $value Candidate value.
     * @return bool
     */
    private static function is_ascii_lowercase(string $value): bool {
        return preg_match('/^[a-z0-9._-]+$/', $value) === 1;
    }
}