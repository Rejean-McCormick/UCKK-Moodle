<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// at your option any later version.

/**
 * Atlas cache helper for the UCKK faculty pages contract.
 *
 * @package    local_uckk
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace local_uckk\local\atlas;

defined('MOODLE_INTERNAL') || die();

/**
 * Provides deterministic cache keys and accessors for canonical Atlas Voies.
 *
 * This class stores already validated/normalized Atlas arrays. It does not
 * validate JSON, normalize JSON, render pages, query Moodle courses, or apply
 * sync operations.
 */
final class atlas_cache {
    /** Plugin component. */
    public const COMPONENT = 'local_uckk';

    /** Moodle MUC cache area. */
    public const CACHE_AREA = 'atlas_voie';

    /** Human-readable cache definition name from the contract. */
    public const CACHE_DEFINITION = 'local_uckk/atlas_voie';

    /** Canonical key prefix. */
    public const KEY_PREFIX = 'atlas_voie';

    /** Hash algorithm required by the contract. */
    public const HASH_ALGORITHM = 'sha256';

    /** Expected sha256 hash length. */
    private const SHA256_LENGTH = 64;

    /**
     * Request-local fallback cache.
     *
     * Used only when the MUC definition has not been installed yet, or when the
     * cache API is unavailable during isolated tests.
     *
     * @var array<string, mixed>
     */
    private static array $requestcache = [];

    /**
     * Whether MUC was already attempted in this request.
     *
     * @var bool
     */
    private bool $mucattempted = false;

    /**
     * MUC cache instance when available.
     *
     * @var mixed
     */
    private $muccache = null;

    /**
     * Return one cached Atlas Voie by voie_id and source hash.
     *
     * @param string $voieid Canonical Voie id.
     * @param string|null $sourcehash Optional sha256 source hash. Current file hash is used when omitted.
     * @return array<string, mixed>|null
     */
    public function get_voie(string $voieid, ?string $sourcehash = null): ?array {
        $key = $this->make_voie_key($voieid, $sourcehash);
        $value = $this->get($key);

        if ($value === null) {
            return null;
        }

        if (!is_array($value)) {
            $this->delete($key);
            return null;
        }

        return $value;
    }

    /**
     * Store one validated/normalized Atlas Voie.
     *
     * @param array<string, mixed> $voie Validated/normalized Atlas Voie.
     * @param string|null $sourcehash Optional sha256 source hash. Current file hash is used when omitted.
     * @return void
     */
    public function set_voie(array $voie, ?string $sourcehash = null): void {
        if (!isset($voie['voie_id']) || !is_string($voie['voie_id']) || trim($voie['voie_id']) === '') {
            throw new \coding_exception('Cannot cache Atlas Voie without voie_id.');
        }

        $key = $this->make_voie_key($voie['voie_id'], $sourcehash);
        $this->set($key, $voie);
    }

    /**
     * Return a cached Atlas Voie or load, validate, normalize, cache, and return it.
     *
     * The loader must return an array. This method does not inspect JSON files
     * beyond computing the current source hash for the key.
     *
     * @param string $voieid Canonical Voie id.
     * @param callable $loader Loader returning array<string, mixed>.
     * @return array<string, mixed>
     */
    public function get_or_load_voie(string $voieid, callable $loader): array {
        $voieid = $this->clean_voie_id($voieid);
        $sourcehash = $this->source_hash_for_voie_id($voieid);

        $cached = $this->get_voie($voieid, $sourcehash);
        if ($cached !== null) {
            return $cached;
        }

        $loaded = $loader($voieid);
        if (!is_array($loaded)) {
            throw new \coding_exception('Atlas cache loader must return an array for ' . $voieid);
        }

        if (!isset($loaded['voie_id']) || $loaded['voie_id'] !== $voieid) {
            throw new \coding_exception('Atlas cache loader returned a Voie with the wrong voie_id for ' . $voieid);
        }

        $this->set_voie($loaded, $sourcehash);

        return $loaded;
    }

    /**
     * Delete the current cached entry for one Voie.
     *
     * Because keys include the source hash, this deletes the current file-hash
     * key. Use purge_all() after source changes to remove stale hash keys.
     *
     * @param string $voieid Canonical Voie id.
     * @param string|null $sourcehash Optional source hash.
     * @return void
     */
    public function invalidate_voie(string $voieid, ?string $sourcehash = null): void {
        $key = $this->make_voie_key($voieid, $sourcehash);
        $this->delete($key);
    }

    /**
     * Purge every Atlas Voie cache entry.
     *
     * @return void
     */
    public function purge_all(): void {
        self::$requestcache = [];

        $cache = $this->get_muc_cache();
        if ($cache === null) {
            return;
        }

        if (method_exists($cache, 'purge')) {
            $cache->purge();
        }
    }

    /**
     * Reset only request-local fallback/static state.
     *
     * @return void
     */
    public static function reset_request_cache(): void {
        self::$requestcache = [];
    }

    /**
     * Build the canonical Atlas Voie cache key.
     *
     * Canonical format:
     * atlas_voie:{voie_id}:{hash}
     *
     * @param string $voieid Canonical Voie id.
     * @param string|null $sourcehash Optional sha256 source hash. Current file hash is used when omitted.
     * @return string
     */
    public function make_voie_key(string $voieid, ?string $sourcehash = null): string {
        $voieid = $this->clean_voie_id($voieid);

        if ($sourcehash === null) {
            $sourcehash = $this->source_hash_for_voie_id($voieid);
        }

        $sourcehash = $this->clean_hash($sourcehash);

        return self::KEY_PREFIX . ':' . $voieid . ':' . $sourcehash;
    }

    /**
     * Return sha256 hash for one Atlas Voie by voie_id.
     *
     * @param string $voieid Canonical Voie id.
     * @return string
     */
    public function source_hash_for_voie_id(string $voieid): string {
        $voieid = $this->clean_voie_id($voieid);
        $manifestitem = atlas_manifest::get_by_voie_id($voieid);

        return $this->source_hash_for_file((string)$manifestitem['file']);
    }

    /**
     * Return sha256 hash for one Atlas Voie file name.
     *
     * @param string $filename Canonical file name such as voie_grand_jeu_social.json.
     * @return string
     */
    public function source_hash_for_file(string $filename): string {
        $filename = $this->clean_atlas_file($filename);
        $path = atlas_manifest::voie_path($filename);

        return $this->source_hash_for_path($path);
    }

    /**
     * Return sha256 hash for atlas_manifest.json.
     *
     * @return string
     */
    public function manifest_source_hash(): string {
        return $this->source_hash_for_path(atlas_manifest::manifest_path());
    }

    /**
     * Return sha256 hash for atlas_schema.json.
     *
     * @return string
     */
    public function schema_source_hash(): string {
        return $this->source_hash_for_path(atlas_manifest::schema_path());
    }

    /**
     * Return a stable aggregate hash for all Atlas inputs.
     *
     * The aggregate hash changes when atlas_manifest.json, atlas_schema.json,
     * or any manifest-listed voie_*.json file changes.
     *
     * @return string
     */
    public function atlas_source_hash(): string {
        $parts = [
            'manifest:' . $this->manifest_source_hash(),
            'schema:' . $this->schema_source_hash(),
        ];

        foreach (atlas_manifest::all() as $item) {
            if (!isset($item['voie_id'], $item['file'])) {
                continue;
            }

            $parts[] = (string)$item['voie_id'] . ':' . $this->source_hash_for_file((string)$item['file']);
        }

        return hash(self::HASH_ALGORITHM, implode("\n", $parts));
    }

    /**
     * Return basic cache status useful for CLI validation and dry-run reports.
     *
     * @return array<string, mixed>
     */
    public function status(): array {
        return [
            'component' => self::COMPONENT,
            'area' => self::CACHE_AREA,
            'definition' => self::CACHE_DEFINITION,
            'muc_available' => $this->get_muc_cache() !== null,
            'request_cache_items' => count(self::$requestcache),
            'atlas_source_hash' => $this->safe_atlas_source_hash(),
        ];
    }

    /**
     * Generic get by canonical key.
     *
     * @param string $key Cache key.
     * @return mixed|null
     */
    public function get(string $key) {
        $key = $this->clean_key($key);

        $cache = $this->get_muc_cache();
        if ($cache !== null) {
            $value = $cache->get($key);
            if ($value !== false) {
                return $value;
            }
        }

        return self::$requestcache[$key] ?? null;
    }

    /**
     * Generic set by canonical key.
     *
     * @param string $key Cache key.
     * @param mixed $value Cache value.
     * @return void
     */
    public function set(string $key, $value): void {
        $key = $this->clean_key($key);
        self::$requestcache[$key] = $value;

        $cache = $this->get_muc_cache();
        if ($cache !== null) {
            $cache->set($key, $value);
        }
    }

    /**
     * Generic delete by canonical key.
     *
     * @param string $key Cache key.
     * @return void
     */
    public function delete(string $key): void {
        $key = $this->clean_key($key);
        unset(self::$requestcache[$key]);

        $cache = $this->get_muc_cache();
        if ($cache !== null) {
            $cache->delete($key);
        }
    }

    /**
     * Return sha256 hash for a readable file path.
     *
     * @param string $path Absolute file path.
     * @return string
     */
    private function source_hash_for_path(string $path): string {
        if (!is_readable($path)) {
            throw new \coding_exception('Cannot compute Atlas source hash; file is not readable: ' . $path);
        }

        $hash = hash_file(self::HASH_ALGORITHM, $path);
        if ($hash === false) {
            throw new \coding_exception('Cannot compute Atlas source hash for: ' . $path);
        }

        return $this->clean_hash($hash);
    }

    /**
     * Return a MUC cache instance when the definition exists.
     *
     * @return mixed|null
     */
    private function get_muc_cache() {
        if ($this->mucattempted) {
            return $this->muccache;
        }

        $this->mucattempted = true;

        if (!class_exists('cache')) {
            $this->muccache = null;
            return null;
        }

        try {
            $this->muccache = \cache::make(self::COMPONENT, self::CACHE_AREA);
        } catch (\Throwable $e) {
            $this->muccache = null;
        }

        return $this->muccache;
    }

    /**
     * Return aggregate hash without throwing from status().
     *
     * @return string
     */
    private function safe_atlas_source_hash(): string {
        try {
            return $this->atlas_source_hash();
        } catch (\Throwable $e) {
            return '';
        }
    }

    /**
     * Normalize and validate a cache key.
     *
     * @param string $key Raw key.
     * @return string
     */
    private function clean_key(string $key): string {
        $key = trim($key);

        if ($key === '') {
            throw new \coding_exception('Atlas cache key cannot be empty.');
        }

        if (preg_match('/^[a-z0-9_:\.-]+$/', $key) !== 1) {
            throw new \coding_exception('Invalid Atlas cache key: ' . $key);
        }

        return $key;
    }

    /**
     * Normalize and validate a sha256 hash.
     *
     * @param string $hash Raw hash.
     * @return string
     */
    private function clean_hash(string $hash): string {
        $hash = strtolower(trim($hash));

        if (strlen($hash) !== self::SHA256_LENGTH || preg_match('/^[a-f0-9]{64}$/', $hash) !== 1) {
            throw new \coding_exception('Invalid Atlas source hash.');
        }

        return $hash;
    }

    /**
     * Normalize and validate a canonical voie_id.
     *
     * @param string $voieid Raw voie_id.
     * @return string
     */
    private function clean_voie_id(string $voieid): string {
        if (class_exists(voie_slugger::class)) {
            return voie_slugger::clean_voie_id($voieid);
        }

        $voieid = trim($voieid);

        if (preg_match('/^voie_[a-z0-9]+(?:_[a-z0-9]+)*$/', $voieid) !== 1) {
            throw new \coding_exception('Invalid Atlas voie_id: ' . $voieid);
        }

        return $voieid;
    }

    /**
     * Normalize and validate a canonical Atlas file name.
     *
     * @param string $filename Raw file name.
     * @return string
     */
    private function clean_atlas_file(string $filename): string {
        if (class_exists(voie_slugger::class)) {
            return voie_slugger::clean_atlas_file($filename);
        }

        $filename = trim($filename);

        if (basename($filename) !== $filename) {
            throw new \coding_exception('Atlas file name must not contain a path: ' . $filename);
        }

        if (preg_match('/^voie_[a-z0-9]+(?:_[a-z0-9]+)*\.json$/', $filename) !== 1) {
            throw new \coding_exception('Invalid Atlas file name: ' . $filename);
        }

        return $filename;
    }
}