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
 * Event emitted when UCKK faculty page caches are purged.
 *
 * This event is administrative and must not log private data, full JSON
 * content, rendered page payloads, Moodle course contents, forum contents,
 * calendar contents, user progress, grades, enrolments, or submissions.
 *
 * Allowed event metadata:
 * - scope;
 * - slug;
 * - cache areas;
 * - purged count;
 * - source;
 * - reason;
 * - hashes.
 *
 * @package    local_uckk
 * @copyright  2026 Univers-Cité King Klown
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace local_uckk\event;

use coding_exception;
use moodle_url;

defined('MOODLE_INTERNAL') || die();

/**
 * Faculty cache purged event.
 *
 * @package local_uckk
 */
final class faculty_cache_purged extends \core\event\base {
    /** Cache purge scope: all faculty/atlas caches. */
    public const SCOPE_ALL = 'all';

    /** Cache purge scope: one faculty page. */
    public const SCOPE_FACULTY = 'faculty';

    /** Cache purge scope: one Atlas Voie. */
    public const SCOPE_ATLAS = 'atlas';

    /** Cache purge scope: one dynamic block. */
    public const SCOPE_DYNAMIC_BLOCK = 'dynamic_block';

    /** Cache purge scope: manifest-level purge. */
    public const SCOPE_MANIFEST = 'manifest';

    /** Cache purge source: admin page. */
    public const SOURCE_ADMIN = 'admin';

    /** Cache purge source: CLI. */
    public const SOURCE_CLI = 'cli';

    /** Cache purge source: sync apply. */
    public const SOURCE_SYNC = 'sync';

    /** Cache purge source: validation. */
    public const SOURCE_VALIDATION = 'validation';

    /**
     * Initialise event metadata.
     *
     * @return void
     */
    protected function init(): void {
        $this->data['crud'] = 'd';
        $this->data['edulevel'] = self::LEVEL_OTHER;
    }

    /**
     * Return the event name.
     *
     * @return string
     */
    public static function get_name(): string {
        if (get_string_manager()->string_exists('event_faculty_cache_purged', 'local_uckk')) {
            return get_string('event_faculty_cache_purged', 'local_uckk');
        }

        return 'UCKK faculty cache purged';
    }

    /**
     * Return a privacy-safe description.
     *
     * @return string
     */
    public function get_description(): string {
        $scope = $this->get_scope();
        $slug = $this->get_slug();
        $source = $this->get_source();
        $purgedcount = $this->get_purged_count();
        $areas = $this->get_cache_areas();

        $parts = [
            "The UCKK faculty cache was purged by user '{$this->userid}'",
            "scope '{$scope}'",
        ];

        if ($slug !== '') {
            $parts[] = "slug '{$slug}'";
        }

        if ($source !== '') {
            $parts[] = "source '{$source}'";
        }

        if (!empty($areas)) {
            $parts[] = 'areas ' . implode(',', $areas);
        }

        if ($purgedcount >= 0) {
            $parts[] = "purged count '{$purgedcount}'";
        }

        return implode(', ', $parts) . '.';
    }

    /**
     * Return the admin cache page URL.
     *
     * @return moodle_url
     */
    public function get_url(): moodle_url {
        $params = [];

        $slug = $this->get_slug();

        if ($slug !== '') {
            $params['slug'] = $slug;
        }

        return new moodle_url('/local/uckk/faculty_cache.php', $params);
    }

    /**
     * Validate event data.
     *
     * @return void
     */
    protected function validate_data(): void {
        parent::validate_data();

        if ($this->contextlevel !== CONTEXT_SYSTEM) {
            throw new coding_exception('faculty_cache_purged event requires system context.');
        }

        if (!array_key_exists('scope', $this->other)) {
            throw new coding_exception('faculty_cache_purged event requires scope in other[].');
        }

        $scope = $this->get_scope();

        if (!in_array($scope, self::allowed_scopes(), true)) {
            throw new coding_exception('faculty_cache_purged event received an invalid scope.');
        }

        if (array_key_exists('slug', $this->other) && $this->get_slug() === '' && (string)$this->other['slug'] !== '') {
            throw new coding_exception('faculty_cache_purged event received an invalid slug.');
        }

        if (array_key_exists('purgedcount', $this->other) && $this->get_purged_count() < 0) {
            throw new coding_exception('faculty_cache_purged event received an invalid purgedcount.');
        }
    }

    /**
     * Return the cache purge scope.
     *
     * @return string
     */
    public function get_scope(): string {
        return self::clean_key((string)($this->other['scope'] ?? self::SCOPE_ALL));
    }

    /**
     * Return the affected faculty slug, when applicable.
     *
     * @return string
     */
    public function get_slug(): string {
        return self::clean_slug((string)($this->other['slug'] ?? ''));
    }

    /**
     * Return the purge source.
     *
     * @return string
     */
    public function get_source(): string {
        return self::clean_key((string)($this->other['source'] ?? ''));
    }

    /**
     * Return the privacy-safe purge reason.
     *
     * @return string
     */
    public function get_reason(): string {
        return self::clean_text((string)($this->other['reason'] ?? ''));
    }

    /**
     * Return the number of purged cache entries, if known.
     *
     * Returns -1 when the count was not provided.
     *
     * @return int
     */
    public function get_purged_count(): int {
        if (!array_key_exists('purgedcount', $this->other)) {
            return -1;
        }

        return (int)$this->other['purgedcount'];
    }

    /**
     * Return affected cache areas.
     *
     * @return string[]
     */
    public function get_cache_areas(): array {
        $areas = $this->other['areas'] ?? [];

        if (!is_array($areas)) {
            return [];
        }

        $out = [];

        foreach ($areas as $area) {
            $area = self::clean_key((string)$area);

            if ($area !== '') {
                $out[] = $area;
            }
        }

        return array_values(array_unique($out));
    }

    /**
     * Return source hashes attached to the event.
     *
     * @return array<string, string>
     */
    public function get_hashes(): array {
        $hashes = $this->other['hashes'] ?? [];

        if (!is_array($hashes)) {
            return [];
        }

        $out = [];

        foreach ($hashes as $name => $hash) {
            $name = self::clean_key((string)$name);
            $hash = self::clean_hash((string)$hash);

            if ($name !== '' && $hash !== '') {
                $out[$name] = $hash;
            }
        }

        return $out;
    }

    /**
     * Create a system-context faculty cache purge event.
     *
     * @param string $scope Purge scope.
     * @param array<string, mixed> $other Extra privacy-safe event metadata.
     * @return self
     */
    public static function create_from_purge(string $scope, array $other = []): self {
        $other['scope'] = self::clean_key($scope);

        if (isset($other['slug'])) {
            $other['slug'] = self::clean_slug((string)$other['slug']);
        }

        if (isset($other['source'])) {
            $other['source'] = self::clean_key((string)$other['source']);
        }

        if (isset($other['reason'])) {
            $other['reason'] = self::clean_text((string)$other['reason']);
        }

        if (isset($other['areas']) && is_array($other['areas'])) {
            $other['areas'] = array_values(array_unique(array_filter(array_map(
                static fn($area): string => self::clean_key((string)$area),
                $other['areas']
            ))));
        }

        if (isset($other['hashes']) && is_array($other['hashes'])) {
            $hashes = [];

            foreach ($other['hashes'] as $name => $hash) {
                $name = self::clean_key((string)$name);
                $hash = self::clean_hash((string)$hash);

                if ($name !== '' && $hash !== '') {
                    $hashes[$name] = $hash;
                }
            }

            $other['hashes'] = $hashes;
        }

        if (isset($other['purgedcount'])) {
            $other['purgedcount'] = max(0, (int)$other['purgedcount']);
        }

        return self::create([
            'context' => \context_system::instance(),
            'other' => $other,
        ]);
    }

    /**
     * Return allowed event scopes.
     *
     * @return string[]
     */
    private static function allowed_scopes(): array {
        return [
            self::SCOPE_ALL,
            self::SCOPE_FACULTY,
            self::SCOPE_ATLAS,
            self::SCOPE_DYNAMIC_BLOCK,
            self::SCOPE_MANIFEST,
        ];
    }

    /**
     * Clean an event key.
     *
     * @param string $value Raw value.
     * @return string
     */
    private static function clean_key(string $value): string {
        $value = trim(\core_text::strtolower($value));

        if ($value === '') {
            return '';
        }

        return clean_param($value, PARAM_ALPHANUMEXT);
    }

    /**
     * Clean a faculty slug.
     *
     * @param string $value Raw slug.
     * @return string
     */
    private static function clean_slug(string $value): string {
        $value = trim(\core_text::strtolower($value));

        if ($value === '') {
            return '';
        }

        return clean_param($value, PARAM_ALPHANUMEXT);
    }

    /**
     * Clean short public event text.
     *
     * @param string $value Raw value.
     * @return string
     */
    private static function clean_text(string $value): string {
        $value = trim(strip_tags($value));

        if ($value === '') {
            return '';
        }

        return clean_param($value, PARAM_TEXT);
    }

    /**
     * Clean a hash value.
     *
     * @param string $value Raw hash.
     * @return string
     */
    private static function clean_hash(string $value): string {
        $value = trim(\core_text::strtolower($value));

        if ($value === '') {
            return '';
        }

        if (preg_match('/^[a-f0-9]{32,128}$/', $value) !== 1) {
            return '';
        }

        return $value;
    }
}