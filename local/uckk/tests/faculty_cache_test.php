<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

/**
 * Unit tests for the UCKK faculty cache service.
 *
 * @package    local_uckk
 * @category   test
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace local_uckk\local\faculty;

use cache;
use cache_helper;
use coding_exception;

defined('MOODLE_INTERNAL') || die();

/**
 * Unit tests for {@see faculty_cache}.
 *
 * @package local_uckk
 * @covers  \local_uckk\local\faculty\faculty_cache
 */
final class faculty_cache_test extends \advanced_testcase {
    /**
     * Ensure each test starts with clean MUC definitions.
     */
    protected function setUp(): void {
        parent::setUp();

        $this->resetAfterTest(true);
        $this->purge_all_faculty_cache_definitions();
    }

    /**
     * Purge all cache areas owned by the faculty/Atlas public-page contract.
     */
    protected function tearDown(): void {
        $this->purge_all_faculty_cache_definitions();

        parent::tearDown();
    }

    /**
     * The four canonical cache definitions must exist in local_uckk/db/caches.php.
     */
    public function test_cache_definitions_are_available(): void {
        $this->assertInstanceOf(cache::class, cache::make('local_uckk', 'faculty_profile'));
        $this->assertInstanceOf(cache::class, cache::make('local_uckk', 'atlas_voie'));
        $this->assertInstanceOf(cache::class, cache::make('local_uckk', 'faculty_page'));
        $this->assertInstanceOf(cache::class, cache::make('local_uckk', 'faculty_dynamic_block'));
    }

    /**
     * Source hashes must be stable SHA-256 hashes of exact file contents.
     */
    public function test_hash_source_returns_sha256_of_exact_contents(): void {
        $contents = "{\n  \"slug\": \"grand-jeu-social\"\n}\n";

        $this->assertSame(hash('sha256', $contents), faculty_cache::hash_source($contents));
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', faculty_cache::hash_source($contents));
    }

    /**
     * A tiny content change must produce a different cache hash.
     */
    public function test_hash_source_changes_when_contents_change(): void {
        $first = faculty_cache::hash_source('{"slug":"grand-jeu-social"}');
        $second = faculty_cache::hash_source('{"slug":"grand-jeu-social","status":"published"}');

        $this->assertNotSame($first, $second);
    }

    /**
     * Faculty profile cache keys must follow the DOC_12 canonical format.
     */
    public function test_profile_key_uses_canonical_format(): void {
        $hash = str_repeat('a', 64);

        $this->assertSame(
            'faculty_profile:grand-jeu-social:' . $hash,
            faculty_cache::profile_key('grand-jeu-social', $hash)
        );
    }

    /**
     * Atlas Voie cache keys must follow the DOC_12 canonical format.
     */
    public function test_atlas_voie_key_uses_canonical_format(): void {
        $hash = str_repeat('b', 64);

        $this->assertSame(
            'atlas_voie:voie_grand_jeu_social:' . $hash,
            faculty_cache::atlas_voie_key('voie_grand_jeu_social', $hash)
        );
    }

    /**
     * Merged faculty page cache keys must include both Atlas and Faculty source hashes.
     */
    public function test_page_key_uses_canonical_format(): void {
        $atlashash = str_repeat('c', 64);
        $facultyhash = str_repeat('d', 64);

        $this->assertSame(
            'faculty_page:grand-jeu-social:' . $atlashash . ':' . $facultyhash,
            faculty_cache::page_key('grand-jeu-social', $atlashash, $facultyhash)
        );
    }

    /**
     * Dynamic block cache keys must include slug, block id, provider, and source hash.
     */
    public function test_dynamic_block_key_uses_canonical_format(): void {
        $hash = str_repeat('e', 64);

        $this->assertSame(
            'dynamic_block:grand-jeu-social:annonces:moodle_forum:' . $hash,
            faculty_cache::dynamic_block_key('grand-jeu-social', 'annonces', 'moodle_forum', $hash)
        );
    }

    /**
     * Faculty profile values can be stored and fetched by slug/hash key.
     */
    public function test_profile_cache_round_trip(): void {
        $hash = faculty_cache::hash_source('faculty-profile-source');
        $payload = [
            'faculty_id' => 'faculty_grand_jeu_social',
            'voie_id' => 'voie_grand_jeu_social',
            'slug' => 'grand-jeu-social',
            'status' => 'published',
            'visibility' => 'public',
            'faculty_source_hash' => $hash,
        ];

        faculty_cache::set_profile('grand-jeu-social', $hash, $payload);

        $this->assertSame($payload, faculty_cache::get_profile('grand-jeu-social', $hash));
    }

    /**
     * Atlas Voie values can be stored and fetched by voie_id/hash key.
     */
    public function test_atlas_voie_cache_round_trip(): void {
        $hash = faculty_cache::hash_source('atlas-voie-source');
        $payload = [
            'voie_id' => 'voie_grand_jeu_social',
            'code' => 'GJS',
            'schema_version' => 'UCKK-ATLAS-0.2-draft',
            'atlas_source_hash' => $hash,
        ];

        faculty_cache::set_atlas_voie('voie_grand_jeu_social', $hash, $payload);

        $this->assertSame($payload, faculty_cache::get_atlas_voie('voie_grand_jeu_social', $hash));
    }

    /**
     * Merged page values can be stored and fetched by slug and source hashes.
     */
    public function test_page_cache_round_trip(): void {
        $atlashash = faculty_cache::hash_source('atlas-source');
        $facultyhash = faculty_cache::hash_source('faculty-source');
        $payload = [
            'slug' => 'grand-jeu-social',
            'faculty_id' => 'faculty_grand_jeu_social',
            'voie_id' => 'voie_grand_jeu_social',
            'atlas_source_hash' => $atlashash,
            'faculty_source_hash' => $facultyhash,
            'merged_page_hash' => faculty_cache::hash_source($atlashash . ':' . $facultyhash),
            'sections' => [],
            'dynamic_blocks' => [],
        ];

        faculty_cache::set_page('grand-jeu-social', $atlashash, $facultyhash, $payload);

        $this->assertSame($payload, faculty_cache::get_page('grand-jeu-social', $atlashash, $facultyhash));
    }

    /**
     * Dynamic block values can be stored and fetched by slug/block/provider/hash key.
     */
    public function test_dynamic_block_cache_round_trip(): void {
        $hash = faculty_cache::hash_source('dynamic-block-source');
        $payload = [
            'id' => 'annonces',
            'type' => 'announcements',
            'provider' => 'moodle_forum',
            'items' => [
                [
                    'title' => 'Annonce publique de rentrée GJS',
                    'url' => '/mod/forum/discuss.php?d=1',
                    'source_label' => 'Moodle',
                ],
            ],
        ];

        faculty_cache::set_dynamic_block('grand-jeu-social', 'annonces', 'moodle_forum', $hash, $payload);

        $this->assertSame(
            $payload,
            faculty_cache::get_dynamic_block('grand-jeu-social', 'annonces', 'moodle_forum', $hash)
        );
    }

    /**
     * Changing the Faculty source hash must naturally miss the profile cache.
     */
    public function test_profile_cache_misses_when_hash_changes(): void {
        $oldhash = faculty_cache::hash_source('old faculty json');
        $newhash = faculty_cache::hash_source('new faculty json');

        faculty_cache::set_profile('grand-jeu-social', $oldhash, [
            'slug' => 'grand-jeu-social',
            'faculty_source_hash' => $oldhash,
        ]);

        $this->assertIsArray(faculty_cache::get_profile('grand-jeu-social', $oldhash));
        $this->assertNull(faculty_cache::get_profile('grand-jeu-social', $newhash));
    }

    /**
     * Changing either source hash must naturally miss the merged page cache.
     */
    public function test_page_cache_misses_when_any_source_hash_changes(): void {
        $atlashash = faculty_cache::hash_source('atlas v1');
        $facultyhash = faculty_cache::hash_source('faculty v1');
        $newatlashash = faculty_cache::hash_source('atlas v2');
        $newfacultyhash = faculty_cache::hash_source('faculty v2');

        faculty_cache::set_page('grand-jeu-social', $atlashash, $facultyhash, [
            'slug' => 'grand-jeu-social',
            'atlas_source_hash' => $atlashash,
            'faculty_source_hash' => $facultyhash,
        ]);

        $this->assertIsArray(faculty_cache::get_page('grand-jeu-social', $atlashash, $facultyhash));
        $this->assertNull(faculty_cache::get_page('grand-jeu-social', $newatlashash, $facultyhash));
        $this->assertNull(faculty_cache::get_page('grand-jeu-social', $atlashash, $newfacultyhash));
    }

    /**
     * Changing a dynamic block provider hash must naturally miss the dynamic block cache.
     */
    public function test_dynamic_block_cache_misses_when_hash_changes(): void {
        $oldhash = faculty_cache::hash_source('forum state v1');
        $newhash = faculty_cache::hash_source('forum state v2');

        faculty_cache::set_dynamic_block('grand-jeu-social', 'annonces', 'moodle_forum', $oldhash, [
            'id' => 'annonces',
            'provider' => 'moodle_forum',
            'items' => [],
        ]);

        $this->assertIsArray(
            faculty_cache::get_dynamic_block('grand-jeu-social', 'annonces', 'moodle_forum', $oldhash)
        );
        $this->assertNull(
            faculty_cache::get_dynamic_block('grand-jeu-social', 'annonces', 'moodle_forum', $newhash)
        );
    }

    /**
     * Purging faculty profile cache must not purge unrelated Atlas/page/block caches.
     */
    public function test_purge_profiles_only_purges_profile_cache(): void {
        $profilehash = faculty_cache::hash_source('profile');
        $atlashash = faculty_cache::hash_source('atlas');
        $facultyhash = faculty_cache::hash_source('faculty');
        $blockhash = faculty_cache::hash_source('block');

        faculty_cache::set_profile('grand-jeu-social', $profilehash, ['slug' => 'grand-jeu-social']);
        faculty_cache::set_atlas_voie('voie_grand_jeu_social', $atlashash, ['voie_id' => 'voie_grand_jeu_social']);
        faculty_cache::set_page('grand-jeu-social', $atlashash, $facultyhash, ['slug' => 'grand-jeu-social']);
        faculty_cache::set_dynamic_block('grand-jeu-social', 'annonces', 'moodle_forum', $blockhash, ['items' => []]);

        faculty_cache::purge_profiles();

        $this->assertNull(faculty_cache::get_profile('grand-jeu-social', $profilehash));
        $this->assertIsArray(faculty_cache::get_atlas_voie('voie_grand_jeu_social', $atlashash));
        $this->assertIsArray(faculty_cache::get_page('grand-jeu-social', $atlashash, $facultyhash));
        $this->assertIsArray(
            faculty_cache::get_dynamic_block('grand-jeu-social', 'annonces', 'moodle_forum', $blockhash)
        );
    }

    /**
     * Purging all faculty caches must clear every canonical faculty/Atlas cache area.
     */
    public function test_purge_all_clears_all_faculty_cache_areas(): void {
        $profilehash = faculty_cache::hash_source('profile');
        $atlashash = faculty_cache::hash_source('atlas');
        $facultyhash = faculty_cache::hash_source('faculty');
        $blockhash = faculty_cache::hash_source('block');

        faculty_cache::set_profile('grand-jeu-social', $profilehash, ['slug' => 'grand-jeu-social']);
        faculty_cache::set_atlas_voie('voie_grand_jeu_social', $atlashash, ['voie_id' => 'voie_grand_jeu_social']);
        faculty_cache::set_page('grand-jeu-social', $atlashash, $facultyhash, ['slug' => 'grand-jeu-social']);
        faculty_cache::set_dynamic_block('grand-jeu-social', 'annonces', 'moodle_forum', $blockhash, ['items' => []]);

        faculty_cache::purge_all();

        $this->assertNull(faculty_cache::get_profile('grand-jeu-social', $profilehash));
        $this->assertNull(faculty_cache::get_atlas_voie('voie_grand_jeu_social', $atlashash));
        $this->assertNull(faculty_cache::get_page('grand-jeu-social', $atlashash, $facultyhash));
        $this->assertNull(
            faculty_cache::get_dynamic_block('grand-jeu-social', 'annonces', 'moodle_forum', $blockhash)
        );
    }

    /**
     * Unsafe slugs must never become cache keys.
     *
     * @dataProvider invalid_slug_provider
     * @param string $slug Invalid slug.
     */
    public function test_profile_key_rejects_invalid_slugs(string $slug): void {
        $this->expectException(coding_exception::class);

        faculty_cache::profile_key($slug, str_repeat('a', 64));
    }

    /**
     * Unsafe voie ids must never become cache keys.
     *
     * @dataProvider invalid_voie_id_provider
     * @param string $voieid Invalid voie id.
     */
    public function test_atlas_voie_key_rejects_invalid_voie_ids(string $voieid): void {
        $this->expectException(coding_exception::class);

        faculty_cache::atlas_voie_key($voieid, str_repeat('a', 64));
    }

    /**
     * Unsafe block ids must never become cache keys.
     *
     * @dataProvider invalid_cache_token_provider
     * @param string $blockid Invalid block id.
     */
    public function test_dynamic_block_key_rejects_invalid_block_ids(string $blockid): void {
        $this->expectException(coding_exception::class);

        faculty_cache::dynamic_block_key('grand-jeu-social', $blockid, 'moodle_forum', str_repeat('a', 64));
    }

    /**
     * Unsafe provider ids must never become cache keys.
     *
     * @dataProvider invalid_cache_token_provider
     * @param string $provider Invalid provider id.
     */
    public function test_dynamic_block_key_rejects_invalid_providers(string $provider): void {
        $this->expectException(coding_exception::class);

        faculty_cache::dynamic_block_key('grand-jeu-social', 'annonces', $provider, str_repeat('a', 64));
    }

    /**
     * Hash parameters must be full lowercase SHA-256 strings.
     *
     * @dataProvider invalid_hash_provider
     * @param string $hash Invalid hash.
     */
    public function test_profile_key_rejects_invalid_hashes(string $hash): void {
        $this->expectException(coding_exception::class);

        faculty_cache::profile_key('grand-jeu-social', $hash);
    }

    /**
     * Invalid slugs.
     *
     * @return array<string, array{0: string}>
     */
    public static function invalid_slug_provider(): array {
        return [
            'empty' => [''],
            'uppercase' => ['Grand-Jeu-Social'],
            'underscore' => ['grand_jeu_social'],
            'relative path' => ['../content/faculties/grand-jeu-social.faculty'],
            'absolute path' => ['/local/uckk/content/faculties/grand-jeu-social.faculty.json'],
            'extension' => ['grand-jeu-social.faculty.json'],
            'query string' => ['grand-jeu-social?debug=1'],
            'fragment' => ['grand-jeu-social#cours'],
        ];
    }

    /**
     * Invalid voie ids.
     *
     * @return array<string, array{0: string}>
     */
    public static function invalid_voie_id_provider(): array {
        return [
            'empty' => [''],
            'slug' => ['grand-jeu-social'],
            'uppercase' => ['VOIE_GRAND_JEU_SOCIAL'],
            'relative path' => ['../atlas/voies/voie_grand_jeu_social'],
            'extension' => ['voie_grand_jeu_social.json'],
            'slash' => ['atlas/voies/voie_grand_jeu_social'],
        ];
    }

    /**
     * Invalid generic cache tokens.
     *
     * @return array<string, array{0: string}>
     */
    public static function invalid_cache_token_provider(): array {
        return [
            'empty' => [''],
            'uppercase' => ['Moodle_Forum'],
            'dash' => ['moodle-forum'],
            'space' => ['moodle forum'],
            'relative path' => ['../moodle_forum'],
            'slash' => ['local_uckk/moodle_forum'],
            'query string' => ['moodle_forum?debug=1'],
        ];
    }

    /**
     * Invalid hashes.
     *
     * @return array<string, array{0: string}>
     */
    public static function invalid_hash_provider(): array {
        return [
            'empty' => [''],
            'short' => [str_repeat('a', 63)],
            'long' => [str_repeat('a', 65)],
            'uppercase' => [str_repeat('A', 64)],
            'non hex' => [str_repeat('g', 64)],
            'pathlike' => ['../' . str_repeat('a', 61)],
        ];
    }

    /**
     * Purge all canonical definitions directly through MUC.
     */
    private function purge_all_faculty_cache_definitions(): void {
        cache_helper::purge_by_definition('local_uckk', 'faculty_profile');
        cache_helper::purge_by_definition('local_uckk', 'atlas_voie');
        cache_helper::purge_by_definition('local_uckk', 'faculty_page');
        cache_helper::purge_by_definition('local_uckk', 'faculty_dynamic_block');
    }
}