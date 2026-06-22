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
 * Faculty cache service for local_uckk.
 *
 * This class wraps Moodle MUC cache definitions used by the public faculty
 * page system.
 *
 * It caches only public/read-only derived data:
 * - normalized faculty profiles;
 * - built faculty page contexts;
 * - public dynamic block payloads.
 *
 * It does not:
 * - cache private user data;
 * - cache grades, completion, enrolment state or submissions;
 * - render HTML;
 * - read JSON files directly;
 * - validate schemas;
 * - query Moodle categories/courses/forums/calendars;
 * - mutate Moodle data;
 * - replace Moodle global cache purge.
 *
 * Required cache definitions in local/uckk/db/caches.php:
 * - faculty_profile
 * - faculty_page
 * - faculty_dynamic_block
 *
 * @package    local_uckk
 * @copyright  2026 Univers-Cité King Klown
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace local_uckk\local\faculty;

use cache;
use coding_exception;
use Throwable;

defined('MOODLE_INTERNAL') || die();

/**
 * Cache wrapper for public faculty pages.
 */
final class faculty_cache {
    /** Moodle component name. */
    public const COMPONENT = 'local_uckk';

    /** MUC area for faculty profiles. */
    public const AREA_FACULTY_PROFILE = 'faculty_profile';

    /** MUC area for built faculty page contexts. */
    public const AREA_FACULTY_PAGE = 'faculty_page';

    /** MUC area for public dynamic blocks. */
    public const AREA_DYNAMIC_BLOCK = 'faculty_dynamic_block';

    /** Default TTL when profile cache config is absent. */
    public const DEFAULT_TTL_SECONDS = 3600;

    /** No-expiry marker. */
    private const EXPIRES_NEVER = 0;

    /** Index key prefix. */
    private const INDEX_PREFIX = 'index';

    /**
     * Cache instances keyed by area.
     *
     * @var array<string, cache>
     */
    private array $stores = [];

    /**
     * Constructor.
     *
     * @param array<string, cache> $stores Optional injected stores, useful for tests.
     */
    public function __construct(array $stores = []) {
        $this->stores = $stores;
    }

    /**
     * Build the canonical source hash for file contents.
     *
     * DOC_12 requires sha256(file contents).
     *
     * @param string $filepath Absolute file path.
     * @return string
     */
    public static function hash_file(string $filepath): string {
        if (!is_readable($filepath)) {
            throw new coding_exception('Cannot hash unreadable file: ' . $filepath);
        }

        $hash = hash_file('sha256', $filepath);
        if ($hash === false) {
            throw new coding_exception('Unable to hash file: ' . $filepath);
        }

        return $hash;
    }

    /**
     * Build a sha256 hash for already loaded content.
     *
     * @param string $content Content.
     * @return string
     */
    public static function hash_content(string $content): string {
        return hash('sha256', $content);
    }

    /**
     * Build a sha256 hash for an array payload.
     *
     * This is suitable for merged_page_hash where the page context is derived
     * from more than one source.
     *
     * @param array<string, mixed> $payload Payload.
     * @return string
     */
    public static function hash_payload(array $payload): string {
        return hash('sha256', json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }

    /**
     * Return a normalized faculty profile from cache.
     *
     * @param string $slug Faculty slug.
     * @param string $facultyhash sha256 hash of the faculty JSON source.
     * @return array<string, mixed>|null
     */
    public function get_profile(string $slug, string $facultyhash): ?array {
        return $this->get_payload(
            self::AREA_FACULTY_PROFILE,
            self::profile_key($slug, $facultyhash)
        );
    }

    /**
     * Store a normalized faculty profile.
     *
     * @param string $slug Faculty slug.
     * @param string $facultyhash sha256 hash of the faculty JSON source.
     * @param array<string, mixed> $profile Normalized public profile.
     * @param int|null $ttlseconds TTL seconds.
     */
    public function set_profile(string $slug, string $facultyhash, array $profile, ?int $ttlseconds = null): void {
        $key = self::profile_key($slug, $facultyhash);

        $this->set_payload(
            self::AREA_FACULTY_PROFILE,
            $key,
            $profile,
            $ttlseconds
        );

        $this->add_index_key(self::AREA_FACULTY_PROFILE, $this->profile_index_key($slug), $key);
    }

    /**
     * Delete a cached profile entry.
     *
     * @param string $slug Faculty slug.
     * @param string $facultyhash sha256 hash of the faculty JSON source.
     */
    public function delete_profile(string $slug, string $facultyhash): void {
        $this->delete_key(self::AREA_FACULTY_PROFILE, self::profile_key($slug, $facultyhash));
    }

    /**
     * Return a built faculty page context from cache.
     *
     * @param string $slug Faculty slug.
     * @param string $atlashash sha256 hash of the Atlas source.
     * @param string $facultyhash sha256 hash of the faculty JSON source.
     * @return array<string, mixed>|null
     */
    public function get_page(string $slug, string $atlashash, string $facultyhash): ?array {
        return $this->get_payload(
            self::AREA_FACULTY_PAGE,
            self::page_key($slug, $atlashash, $facultyhash)
        );
    }

    /**
     * Store a built faculty page context.
     *
     * @param string $slug Faculty slug.
     * @param string $atlashash sha256 hash of the Atlas source.
     * @param string $facultyhash sha256 hash of the faculty JSON source.
     * @param array<string, mixed> $page Built public page context.
     * @param int|null $ttlseconds TTL seconds.
     */
    public function set_page(
        string $slug,
        string $atlashash,
        string $facultyhash,
        array $page,
        ?int $ttlseconds = null
    ): void {
        $key = self::page_key($slug, $atlashash, $facultyhash);

        $this->set_payload(
            self::AREA_FACULTY_PAGE,
            $key,
            $page,
            $ttlseconds
        );

        $this->add_index_key(self::AREA_FACULTY_PAGE, $this->page_index_key($slug), $key);
    }

    /**
     * Delete a cached page context.
     *
     * @param string $slug Faculty slug.
     * @param string $atlashash sha256 hash of the Atlas source.
     * @param string $facultyhash sha256 hash of the faculty JSON source.
     */
    public function delete_page(string $slug, string $atlashash, string $facultyhash): void {
        $this->delete_key(self::AREA_FACULTY_PAGE, self::page_key($slug, $atlashash, $facultyhash));
    }

    /**
     * Return a cached public dynamic block payload.
     *
     * @param string $slug Faculty slug.
     * @param string $blockid Dynamic block id.
     * @param string $provider Provider id.
     * @param string $sourcehash Source hash for the block input.
     * @return array<string, mixed>|null
     */
    public function get_dynamic_block(string $slug, string $blockid, string $provider, string $sourcehash): ?array {
        return $this->get_payload(
            self::AREA_DYNAMIC_BLOCK,
            self::dynamic_block_key($slug, $blockid, $provider, $sourcehash)
        );
    }

    /**
     * Store a public dynamic block payload.
     *
     * @param string $slug Faculty slug.
     * @param string $blockid Dynamic block id.
     * @param string $provider Provider id.
     * @param string $sourcehash Source hash for the block input.
     * @param array<string, mixed> $block Public block payload.
     * @param int|null $ttlseconds TTL seconds.
     */
    public function set_dynamic_block(
        string $slug,
        string $blockid,
        string $provider,
        string $sourcehash,
        array $block,
        ?int $ttlseconds = null
    ): void {
        $key = self::dynamic_block_key($slug, $blockid, $provider, $sourcehash);

        $this->set_payload(
            self::AREA_DYNAMIC_BLOCK,
            $key,
            $block,
            $ttlseconds
        );

        $this->add_index_key(self::AREA_DYNAMIC_BLOCK, $this->dynamic_index_key($slug), $key);
    }

    /**
     * Delete a cached dynamic block payload.
     *
     * @param string $slug Faculty slug.
     * @param string $blockid Dynamic block id.
     * @param string $provider Provider id.
     * @param string $sourcehash Source hash for the block input.
     */
    public function delete_dynamic_block(string $slug, string $blockid, string $provider, string $sourcehash): void {
        $this->delete_key(
            self::AREA_DYNAMIC_BLOCK,
            self::dynamic_block_key($slug, $blockid, $provider, $sourcehash)
        );
    }

    /**
     * Purge all faculty-related cache areas owned by this class.
     *
     * @param bool $triggerevent Whether to trigger faculty_cache_purged if available.
     * @return array<string, mixed>
     */
    public function purge_all(bool $triggerevent = true): array {
        $areas = [
            self::AREA_FACULTY_PROFILE,
            self::AREA_FACULTY_PAGE,
            self::AREA_DYNAMIC_BLOCK,
        ];

        foreach ($areas as $area) {
            $this->store($area)->purge();
        }

        $report = [
            'component' => self::COMPONENT,
            'scope' => 'all',
            'slug' => '',
            'areas' => $areas,
            'keys_deleted' => 0,
        ];

        if ($triggerevent) {
            $this->trigger_purged_event($report);
        }

        return $report;
    }

    /**
     * Purge all known cached keys for one faculty slug.
     *
     * This uses per-slug indexes because MUC does not provide a portable
     * wildcard delete API for arbitrary keys.
     *
     * @param string $slug Faculty slug.
     * @param bool $triggerevent Whether to trigger faculty_cache_purged if available.
     * @return array<string, mixed>
     */
    public function purge_faculty(string $slug, bool $triggerevent = true): array {
        $slug = self::clean_slug($slug);

        if ($slug === '') {
            throw new coding_exception('Invalid faculty slug for cache purge.');
        }

        $deleted = 0;

        $deleted += $this->delete_indexed_keys(self::AREA_FACULTY_PROFILE, $this->profile_index_key($slug));
        $deleted += $this->delete_indexed_keys(self::AREA_FACULTY_PAGE, $this->page_index_key($slug));
        $deleted += $this->delete_indexed_keys(self::AREA_DYNAMIC_BLOCK, $this->dynamic_index_key($slug));

        $report = [
            'component' => self::COMPONENT,
            'scope' => 'faculty',
            'slug' => $slug,
            'areas' => [
                self::AREA_FACULTY_PROFILE,
                self::AREA_FACULTY_PAGE,
                self::AREA_DYNAMIC_BLOCK,
            ],
            'keys_deleted' => $deleted,
        ];

        if ($triggerevent) {
            $this->trigger_purged_event($report);
        }

        return $report;
    }

    /**
     * Purge cached profiles for one faculty slug.
     *
     * @param string $slug Faculty slug.
     * @return int Number of keys deleted from the index.
     */
    public function purge_profiles_for_slug(string $slug): int {
        return $this->delete_indexed_keys(
            self::AREA_FACULTY_PROFILE,
            $this->profile_index_key(self::clean_slug($slug))
        );
    }

    /**
     * Purge cached page contexts for one faculty slug.
     *
     * @param string $slug Faculty slug.
     * @return int Number of keys deleted from the index.
     */
    public function purge_pages_for_slug(string $slug): int {
        return $this->delete_indexed_keys(
            self::AREA_FACULTY_PAGE,
            $this->page_index_key(self::clean_slug($slug))
        );
    }

    /**
     * Purge cached dynamic blocks for one faculty slug.
     *
     * @param string $slug Faculty slug.
     * @return int Number of keys deleted from the index.
     */
    public function purge_dynamic_blocks_for_slug(string $slug): int {
        return $this->delete_indexed_keys(
            self::AREA_DYNAMIC_BLOCK,
            $this->dynamic_index_key(self::clean_slug($slug))
        );
    }

    /**
     * Return the canonical faculty profile cache key.
     *
     * @param string $slug Faculty slug.
     * @param string $facultyhash sha256 source hash.
     * @return string
     */
    public static function profile_key(string $slug, string $facultyhash): string {
        return 'faculty_profile:' . self::clean_slug_or_fail($slug) . ':' . self::clean_hash_or_fail($facultyhash);
    }

    /**
     * Return the canonical faculty page cache key.
     *
     * @param string $slug Faculty slug.
     * @param string $atlashash sha256 Atlas source hash.
     * @param string $facultyhash sha256 faculty source hash.
     * @return string
     */
    public static function page_key(string $slug, string $atlashash, string $facultyhash): string {
        return 'faculty_page:'
            . self::clean_slug_or_fail($slug)
            . ':'
            . self::clean_hash_or_fail($atlashash)
            . ':'
            . self::clean_hash_or_fail($facultyhash);
    }

    /**
     * Return the canonical dynamic block cache key.
     *
     * @param string $slug Faculty slug.
     * @param string $blockid Dynamic block id.
     * @param string $provider Provider id.
     * @param string $sourcehash sha256 source hash.
     * @return string
     */
    public static function dynamic_block_key(
        string $slug,
        string $blockid,
        string $provider,
        string $sourcehash
    ): string {
        return 'dynamic_block:'
            . self::clean_slug_or_fail($slug)
            . ':'
            . self::clean_anchor_or_fail($blockid, 'block id')
            . ':'
            . self::clean_provider_or_fail($provider)
            . ':'
            . self::clean_hash_or_fail($sourcehash);
    }

    /**
     * Return a cached payload if present and not expired.
     *
     * @param string $area Cache area.
     * @param string $key Cache key.
     * @return array<string, mixed>|null
     */
    private function get_payload(string $area, string $key): ?array {
        $entry = $this->store($area)->get($key);

        if (!is_array($entry)) {
            return null;
        }

        if (!array_key_exists('payload', $entry) || !is_array($entry['payload'])) {
            $this->delete_key($area, $key);
            return null;
        }

        $expiresat = (int)($entry['expires_at'] ?? self::EXPIRES_NEVER);
        if ($expiresat !== self::EXPIRES_NEVER && $expiresat < time()) {
            $this->delete_key($area, $key);
            return null;
        }

        return $entry['payload'];
    }

    /**
     * Store a cache payload with an explicit metadata envelope.
     *
     * @param string $area Cache area.
     * @param string $key Cache key.
     * @param array<string, mixed> $payload Payload.
     * @param int|null $ttlseconds TTL seconds.
     */
    private function set_payload(string $area, string $key, array $payload, ?int $ttlseconds): void {
        $ttlseconds = $this->normalize_ttl($ttlseconds);
        $time = time();

        $entry = [
            'component' => self::COMPONENT,
            'area' => $area,
            'key' => $key,
            'created_at' => $time,
            'ttl_seconds' => $ttlseconds,
            'expires_at' => $ttlseconds > 0 ? $time + $ttlseconds : self::EXPIRES_NEVER,
            'payload' => $payload,
        ];

        $this->store($area)->set($key, $entry);
    }

    /**
     * Delete one key.
     *
     * @param string $area Cache area.
     * @param string $key Cache key.
     */
    private function delete_key(string $area, string $key): void {
        $this->store($area)->delete($key);
    }

    /**
     * Add a concrete key to a slug-level index.
     *
     * @param string $area Cache area.
     * @param string $indexkey Index key.
     * @param string $key Concrete cache key.
     */
    private function add_index_key(string $area, string $indexkey, string $key): void {
        $store = $this->store($area);
        $index = $store->get($indexkey);

        if (!is_array($index)) {
            $index = [];
        }

        $index[$key] = true;

        $store->set($indexkey, $index);
    }

    /**
     * Delete all keys registered in an index, then delete the index.
     *
     * @param string $area Cache area.
     * @param string $indexkey Index key.
     * @return int Number of keys deleted from the index.
     */
    private function delete_indexed_keys(string $area, string $indexkey): int {
        if ($indexkey === '') {
            return 0;
        }

        $store = $this->store($area);
        $index = $store->get($indexkey);

        if (!is_array($index)) {
            $store->delete($indexkey);
            return 0;
        }

        $deleted = 0;

        foreach (array_keys($index) as $key) {
            if (!is_string($key) || $key === '') {
                continue;
            }

            $store->delete($key);
            $deleted++;
        }

        $store->delete($indexkey);

        return $deleted;
    }

    /**
     * Return the MUC cache store for an area.
     *
     * @param string $area Cache area.
     * @return cache
     */
    private function store(string $area): cache {
        if (!in_array($area, [self::AREA_FACULTY_PROFILE, self::AREA_FACULTY_PAGE, self::AREA_DYNAMIC_BLOCK], true)) {
            throw new coding_exception('Unknown faculty cache area: ' . $area);
        }

        if (!array_key_exists($area, $this->stores)) {
            $this->stores[$area] = cache::make(self::COMPONENT, $area);
        }

        return $this->stores[$area];
    }

    /**
     * Trigger the cache purged event when the event class exists.
     *
     * Event payload must not contain JSON content or private data.
     *
     * @param array<string, mixed> $report Purge report.
     */
    private function trigger_purged_event(array $report): void {
        $eventclass = '\\local_uckk\\event\\faculty_cache_purged';

        if (!class_exists($eventclass)) {
            return;
        }

        try {
            $event = $eventclass::create([
                'context' => \context_system::instance(),
                'other' => [
                    'scope' => (string)($report['scope'] ?? ''),
                    'slug' => (string)($report['slug'] ?? ''),
                    'keys_deleted' => (int)($report['keys_deleted'] ?? 0),
                    'areas' => implode(',', $report['areas'] ?? []),
                ],
            ]);
            $event->trigger();
        } catch (Throwable $exception) {
            // Cache purge must not fail because optional logging failed.
        }
    }

    /**
     * Normalize TTL.
     *
     * @param int|null $ttlseconds TTL seconds.
     * @return int
     */
    private function normalize_ttl(?int $ttlseconds): int {
        if ($ttlseconds === null) {
            return self::DEFAULT_TTL_SECONDS;
        }

        return max(0, $ttlseconds);
    }

    /**
     * Return profile index key for a slug.
     *
     * @param string $slug Faculty slug.
     * @return string
     */
    private function profile_index_key(string $slug): string {
        return self::INDEX_PREFIX . ':faculty_profile:' . self::clean_slug_or_fail($slug);
    }

    /**
     * Return page index key for a slug.
     *
     * @param string $slug Faculty slug.
     * @return string
     */
    private function page_index_key(string $slug): string {
        return self::INDEX_PREFIX . ':faculty_page:' . self::clean_slug_or_fail($slug);
    }

    /**
     * Return dynamic block index key for a slug.
     *
     * @param string $slug Faculty slug.
     * @return string
     */
    private function dynamic_index_key(string $slug): string {
        return self::INDEX_PREFIX . ':dynamic_block:' . self::clean_slug_or_fail($slug);
    }

    /**
     * Clean a faculty slug.
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

    /**
     * Clean a faculty slug or fail.
     *
     * @param string $slug Raw slug.
     * @return string
     */
    private static function clean_slug_or_fail(string $slug): string {
        $slug = self::clean_slug($slug);

        if ($slug === '') {
            throw new coding_exception('Invalid faculty cache slug.');
        }

        return $slug;
    }

    /**
     * Clean a sha256 hash or fail.
     *
     * @param string $hash Hash.
     * @return string
     */
    private static function clean_hash_or_fail(string $hash): string {
        $hash = strtolower(trim($hash));

        if (!preg_match('/^[a-f0-9]{64}$/', $hash)) {
            throw new coding_exception('Invalid faculty cache hash.');
        }

        return $hash;
    }

    /**
     * Clean an anchor-like id or fail.
     *
     * @param string $value Value.
     * @param string $label Human label.
     * @return string
     */
    private static function clean_anchor_or_fail(string $value, string $label): string {
        $value = strtolower(trim($value));

        if (!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $value)) {
            throw new coding_exception('Invalid faculty cache ' . $label . '.');
        }

        return $value;
    }

    /**
     * Clean provider id or fail.
     *
     * Provider ids in DOC_12 include Moodle/native provider names with
     * underscores.
     *
     * @param string $provider Provider id.
     * @return string
     */
    private static function clean_provider_or_fail(string $provider): string {
        $provider = strtolower(trim($provider));

        if (!preg_match('/^[a-z0-9_]+$/', $provider)) {
            throw new coding_exception('Invalid faculty dynamic block provider cache key.');
        }

        return $provider;
    }
}
