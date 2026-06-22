<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

/**
 * PHPUnit tests for the UCKK Faculty repository.
 *
 * @package    local_uckk
 * @category   test
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace local_uckk;

use advanced_testcase;
use JsonException;
use local_uckk\local\faculty\faculty_repository;
use Throwable;

defined('MOODLE_INTERNAL') || die();

/**
 * Tests for local_uckk\local\faculty\faculty_repository.
 *
 * The Faculty repository is the read boundary for public Faculty Profile JSON
 * files. It loads only manifest-declared *.faculty.json files, validates or
 * normalizes public profile data, resolves public visibility, and must not
 * fetch Moodle announcements/events or build Mustache-ready page payloads.
 *
 * @covers \local_uckk\local\faculty\faculty_repository
 */
final class faculty_repository_test extends advanced_testcase {
    /** Faculty profile schema version. */
    private const FACULTY_SCHEMA_VERSION = 'UCKK-FACULTY-0.1';

    /** Atlas schema version expected in source_atlas. */
    private const ATLAS_SCHEMA_VERSION = 'UCKK-ATLAS-0.2-draft';

    /** Required top-level Faculty Profile fields. */
    private const REQUIRED_PROFILE_FIELDS = [
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

    /** Required source_atlas keys. */
    private const REQUIRED_SOURCE_ATLAS_FIELDS = [
        'file',
        'schema_version_expected',
        'sync_mode',
    ];

    /** Required Moodle mapping keys. */
    private const REQUIRED_MOODLE_FIELDS = [
        'category_id',
        'category_idnumber',
        'course_prefix',
        'public_course_listing',
        'enrolment_visibility',
        'hub_course_idnumber',
    ];

    /** Allowed public profile status values. */
    private const ALLOWED_STATUS = [
        'draft',
        'published',
        'archived',
    ];

    /** Allowed public profile visibility values. */
    private const ALLOWED_VISIBILITY = [
        'public',
        'hidden',
        'restricted',
    ];

    /** Allowed source_atlas sync modes. */
    private const ALLOWED_SYNC_MODES = [
        'read_only',
        'preview_only',
        'moodle_sync_allowed',
    ];

    /** Fields that must not be returned by the repository. */
    private const FORBIDDEN_REPOSITORY_OUTPUT_KEYS = [
        'courses',
        'cours_conceptuels',
        'concept_maitre',
        'concepts_associes',
        'criteres_passage',
        'project_final',
        'project_final',
        'limits',
        'relations',
        'atlas',
        'page',
        'notices',
        'metadata',
        'html',
        'rendered',
        'announcements',
        'annonces',
        'events',
        'calendar_events',
        'forum_posts',
        'course_instances',
    ];

    /** Forbidden slug aliases and path-like inputs. */
    private const FORBIDDEN_SLUGS = [
        '',
        'grand_jeu_social',
        'voie-grand-jeu-social',
        'uckk-grand-jeu-social',
        'faculte-grand-jeu-social',
        'grandjeusocial',
        'GRAND-JEU-SOCIAL',
        ' grand-jeu-social ',
        'grand-jeu-social.faculty.json',
        'content/faculties/grand-jeu-social.faculty.json',
        '../grand-jeu-social',
        '..\\grand-jeu-social',
        '/grand-jeu-social',
        'grand-jeu-social/../../economie',
        'https://example.test/grand-jeu-social',
    ];

    /** Forbidden Faculty id aliases and path-like inputs. */
    private const FORBIDDEN_FACULTY_IDS = [
        '',
        'grand-jeu-social',
        'faculty-grand-jeu-social',
        'faculty_grandjeusocial',
        'FACULTY_GRAND_JEU_SOCIAL',
        ' faculty_grand_jeu_social ',
        'faculty_koa',
        'faculty_linguistique',
        'faculty_intelligence_artificielle_gouvernable',
        '../faculty_grand_jeu_social',
        '..\\faculty_grand_jeu_social',
        '/faculty_grand_jeu_social',
        'content/faculties/grand-jeu-social.faculty.json',
    ];

    /**
     * Canonical Faculty profiles from DOC_12.
     *
     * @var array<int, array<string, int|string>>
     */
    private const EXPECTED_PROFILES = [
        [
            'faculty_id' => 'faculty_grand_jeu_social',
            'voie_id' => 'voie_grand_jeu_social',
            'slug' => 'grand-jeu-social',
            'faculty_file' => 'grand-jeu-social.faculty.json',
            'atlas_file' => 'voie_grand_jeu_social.json',
            'category_idnumber' => 'UCKK-GJS',
            'course_prefix' => 'GJS',
            'sortorder' => 1,
        ],
        [
            'faculty_id' => 'faculty_economie',
            'voie_id' => 'voie_economie',
            'slug' => 'economie',
            'faculty_file' => 'economie.faculty.json',
            'atlas_file' => 'voie_economie.json',
            'category_idnumber' => 'UCKK-EC',
            'course_prefix' => 'EC',
            'sortorder' => 2,
        ],
        [
            'faculty_id' => 'faculty_ecologie',
            'voie_id' => 'voie_ecologie',
            'slug' => 'ecologie',
            'faculty_file' => 'ecologie.faculty.json',
            'atlas_file' => 'voie_ecologie.json',
            'category_idnumber' => 'UCKK-ECL',
            'course_prefix' => 'ECL',
            'sortorder' => 3,
        ],
        [
            'faculty_id' => 'faculty_sciences_politiques',
            'voie_id' => 'voie_sciences_politiques',
            'slug' => 'sciences-politiques',
            'faculty_file' => 'sciences-politiques.faculty.json',
            'atlas_file' => 'voie_sciences_politiques.json',
            'category_idnumber' => 'UCKK-SP',
            'course_prefix' => 'SP',
            'sortorder' => 4,
        ],
        [
            'faculty_id' => 'faculty_linguistique_architecture_du_sens',
            'voie_id' => 'voie_linguistique_architecture_du_sens',
            'slug' => 'linguistique-architecture-du-sens',
            'faculty_file' => 'linguistique-architecture-du-sens.faculty.json',
            'atlas_file' => 'voie_linguistique_architecture_du_sens.json',
            'category_idnumber' => 'UCKK-LI',
            'course_prefix' => 'LI',
            'sortorder' => 5,
        ],
        [
            'faculty_id' => 'faculty_metaphysique',
            'voie_id' => 'voie_metaphysique',
            'slug' => 'metaphysique',
            'faculty_file' => 'metaphysique.faculty.json',
            'atlas_file' => 'voie_metaphysique.json',
            'category_idnumber' => 'UCKK-ME',
            'course_prefix' => 'ME',
            'sortorder' => 6,
        ],
        [
            'faculty_id' => 'faculty_ia_gouvernable',
            'voie_id' => 'voie_ia_gouvernable',
            'slug' => 'ia-gouvernable',
            'faculty_file' => 'ia-gouvernable.faculty.json',
            'atlas_file' => 'voie_ia_gouvernable.json',
            'category_idnumber' => 'UCKK-IA',
            'course_prefix' => 'IA',
            'sortorder' => 7,
        ],
        [
            'faculty_id' => 'faculty_intervention_sociale_systemes_humains',
            'voie_id' => 'voie_intervention_sociale_systemes_humains',
            'slug' => 'intervention-sociale-systemes-humains',
            'faculty_file' => 'intervention-sociale-systemes-humains.faculty.json',
            'atlas_file' => 'voie_intervention_sociale_systemes_humains.json',
            'category_idnumber' => 'UCKK-IS',
            'course_prefix' => 'IS',
            'sortorder' => 8,
        ],
        [
            'faculty_id' => 'faculty_architecture_sociotechnique',
            'voie_id' => 'voie_architecture_sociotechnique',
            'slug' => 'architecture-sociotechnique',
            'faculty_file' => 'architecture-sociotechnique.faculty.json',
            'atlas_file' => 'voie_architecture_sociotechnique.json',
            'category_idnumber' => 'UCKK-AS',
            'course_prefix' => 'AS',
            'sortorder' => 9,
        ],
        [
            'faculty_id' => 'faculty_ecosysteme_digital_koa',
            'voie_id' => 'voie_ecosysteme_digital_koa',
            'slug' => 'ecosysteme-digital-koa',
            'faculty_file' => 'ecosysteme-digital-koa.faculty.json',
            'atlas_file' => 'voie_ecosysteme_digital_koa.json',
            'category_idnumber' => 'UCKK-KOA',
            'course_prefix' => 'KOA',
            'sortorder' => 10,
        ],
    ];

    /**
     * Repository class and canonical methods must exist.
     */
    public function test_repository_exposes_canonical_methods(): void {
        $this->assertTrue(
            class_exists(faculty_repository::class),
            faculty_repository::class . ' must exist.'
        );

        $this->assertTrue(method_exists(faculty_repository::class, 'get_by_slug'));
        $this->assertTrue(method_exists(faculty_repository::class, 'get_by_faculty_id'));
        $this->assertTrue(method_exists(faculty_repository::class, 'all_public'));
    }

    /**
     * get_by_slug() must return the requested canonical Faculty Profile.
     */
    public function test_get_by_slug_returns_canonical_faculty_profile(): void {
        $repository = self::repository();

        $profile = $repository->get_by_slug('grand-jeu-social');

        $this->assertIsArray($profile);
        $this->assertSame(self::FACULTY_SCHEMA_VERSION, $profile['schema_version']);
        $this->assertSame('faculty_grand_jeu_social', $profile['faculty_id']);
        $this->assertSame('voie_grand_jeu_social', $profile['voie_id']);
        $this->assertSame('grand-jeu-social', $profile['slug']);

        $this->assert_has_required_faculty_profile_shape($profile);
    }

    /**
     * get_by_faculty_id() must return the requested canonical Faculty Profile.
     */
    public function test_get_by_faculty_id_returns_canonical_faculty_profile(): void {
        $repository = self::repository();

        $profile = $repository->get_by_faculty_id('faculty_ia_gouvernable');

        $this->assertIsArray($profile);
        $this->assertSame(self::FACULTY_SCHEMA_VERSION, $profile['schema_version']);
        $this->assertSame('faculty_ia_gouvernable', $profile['faculty_id']);
        $this->assertSame('voie_ia_gouvernable', $profile['voie_id']);
        $this->assertSame('ia-gouvernable', $profile['slug']);

        $this->assert_has_required_faculty_profile_shape($profile);
    }

    /**
     * get_by_slug() and get_by_faculty_id() must resolve to the same profile.
     */
    public function test_get_by_slug_and_get_by_faculty_id_return_same_profile(): void {
        $repository = self::repository();

        foreach (self::EXPECTED_PROFILES as $expected) {
            $byslug = $repository->get_by_slug((string) $expected['slug']);
            $byfacultyid = $repository->get_by_faculty_id((string) $expected['faculty_id']);

            $this->assertSame($byfacultyid, $byslug);
        }
    }

    /**
     * all_public() must return only published public profiles.
     */
    public function test_all_public_returns_only_published_public_profiles(): void {
        $repository = self::repository();

        $profiles = self::normalize_profile_list($repository->all_public());

        $this->assertNotEmpty($profiles);

        foreach ($profiles as $profile) {
            $this->assert_has_required_faculty_profile_shape($profile);
            $this->assertSame('published', $profile['status']);
            $this->assertSame('public', $profile['visibility']);
        }
    }

    /**
     * all_public() must return the canonical public profiles in stable order.
     */
    public function test_all_public_returns_canonical_profiles_in_manifest_order(): void {
        $repository = self::repository();

        $profiles = self::normalize_profile_list($repository->all_public());

        $actualslugs = array_map(
            static fn(array $profile): string => (string) $profile['slug'],
            $profiles
        );

        $expectedslugs = array_map(
            static fn(array $profile): string => (string) $profile['slug'],
            self::EXPECTED_PROFILES
        );

        $this->assertSame($expectedslugs, $actualslugs);
    }

    /**
     * Every canonical slug must resolve to the profile declared by the manifest.
     */
    public function test_get_by_slug_matches_faculty_manifest_entries(): void {
        $repository = self::repository();
        $manifestbyid = self::manifest_items_by_field('faculty_id');

        foreach (self::EXPECTED_PROFILES as $expected) {
            $profile = $repository->get_by_slug((string) $expected['slug']);
            $facultyid = (string) $expected['faculty_id'];

            $this->assertArrayHasKey($facultyid, $manifestbyid);
            $this->assert_profile_matches_manifest_item($profile, $manifestbyid[$facultyid]);
        }
    }

    /**
     * Every canonical Faculty id must resolve to the profile declared by the manifest.
     */
    public function test_get_by_faculty_id_matches_faculty_manifest_entries(): void {
        $repository = self::repository();
        $manifestbyid = self::manifest_items_by_field('faculty_id');

        foreach (self::EXPECTED_PROFILES as $expected) {
            $profile = $repository->get_by_faculty_id((string) $expected['faculty_id']);
            $facultyid = (string) $expected['faculty_id'];

            $this->assertArrayHasKey($facultyid, $manifestbyid);
            $this->assert_profile_matches_manifest_item($profile, $manifestbyid[$facultyid]);
        }
    }

    /**
     * Repository output must match the backing *.faculty.json files.
     */
    public function test_repository_output_matches_backing_faculty_json_files(): void {
        $repository = self::repository();

        foreach (self::EXPECTED_PROFILES as $expected) {
            $profile = $repository->get_by_slug((string) $expected['slug']);
            $rawprofile = self::read_json_file(self::faculty_dir() . DIRECTORY_SEPARATOR . $expected['faculty_file']);

            foreach (self::REQUIRED_PROFILE_FIELDS as $field) {
                $this->assertArrayHasKey($field, $profile);
                $this->assertArrayHasKey($field, $rawprofile);
            }

            $this->assertSame($rawprofile['faculty_id'], $profile['faculty_id']);
            $this->assertSame($rawprofile['voie_id'], $profile['voie_id']);
            $this->assertSame($rawprofile['slug'], $profile['slug']);
            $this->assertSame($rawprofile['status'], $profile['status']);
            $this->assertSame($rawprofile['visibility'], $profile['visibility']);
            $this->assertSame($rawprofile['source_atlas']['file'], $profile['source_atlas']['file']);
            $this->assertSame($rawprofile['moodle']['category_idnumber'], $profile['moodle']['category_idnumber']);
            $this->assertSame($rawprofile['moodle']['course_prefix'], $profile['moodle']['course_prefix']);
        }
    }

    /**
     * source_atlas blocks must point to the canonical Atlas JSON files.
     */
    public function test_source_atlas_blocks_point_to_existing_canonical_atlas_files(): void {
        $repository = self::repository();

        foreach (self::EXPECTED_PROFILES as $expected) {
            $profile = $repository->get_by_slug((string) $expected['slug']);

            $this->assertArrayHasKey('source_atlas', $profile);
            $this->assertIsArray($profile['source_atlas']);

            $this->assertSame($expected['atlas_file'], $profile['source_atlas']['file']);
            $this->assertSame(self::ATLAS_SCHEMA_VERSION, $profile['source_atlas']['schema_version_expected']);
            $this->assertContains($profile['source_atlas']['sync_mode'], self::ALLOWED_SYNC_MODES);

            $this->assertFileExists(self::atlas_voie_dir() . DIRECTORY_SEPARATOR . $profile['source_atlas']['file']);
        }
    }

    /**
     * Moodle mapping blocks must match the manifest and the Atlas code.
     */
    public function test_moodle_mapping_matches_manifest_and_atlas_identity(): void {
        $repository = self::repository();

        foreach (self::EXPECTED_PROFILES as $expected) {
            $profile = $repository->get_by_slug((string) $expected['slug']);
            $atlas = self::read_json_file(self::atlas_voie_dir() . DIRECTORY_SEPARATOR . $expected['atlas_file']);

            $this->assertArrayHasKey('moodle', $profile);
            $this->assertIsArray($profile['moodle']);

            $this->assertSame($expected['category_idnumber'], $profile['moodle']['category_idnumber']);
            $this->assertSame($expected['course_prefix'], $profile['moodle']['course_prefix']);
            $this->assertSame($profile['voie_id'], $atlas['voie_id']);
            $this->assertSame($profile['moodle']['course_prefix'], $atlas['code']);
        }
    }

    /**
     * Repository output must remain Faculty Profile data, not a rendered public
     * page payload and not an Atlas course dump.
     */
    public function test_repository_does_not_return_rendered_page_or_atlas_course_payload(): void {
        $repository = self::repository();

        $profile = $repository->get_by_slug('grand-jeu-social');

        foreach (self::FORBIDDEN_REPOSITORY_OUTPUT_KEYS as $key) {
            $this->assertArrayNotHasKey($key, $profile, 'Repository leaked forbidden key: ' . $key);
        }
    }

    /**
     * Repository output must not contain live Moodle announcement/event payloads.
     */
    public function test_repository_does_not_fetch_live_dynamic_block_content(): void {
        $repository = self::repository();

        foreach (self::normalize_profile_list($repository->all_public()) as $profile) {
            $this->assertArrayHasKey('dynamic_blocks', $profile);
            $this->assertIsArray($profile['dynamic_blocks']);

            foreach ($profile['dynamic_blocks'] as $block) {
                $this->assertIsArray($block);
                $this->assertArrayHasKey('source', $block);

                $this->assertArrayNotHasKey('items', $block);
                $this->assertArrayNotHasKey('announcements', $block);
                $this->assertArrayNotHasKey('events', $block);
                $this->assertArrayNotHasKey('posts', $block);
                $this->assertArrayNotHasKey('courses', $block);
                $this->assertArrayNotHasKey('html', $block);
            }
        }
    }

    /**
     * Unknown and unsafe slugs must fail closed.
     */
    public function test_get_by_slug_rejects_unknown_alias_and_path_like_slugs(): void {
        $repository = self::repository();

        foreach (self::FORBIDDEN_SLUGS as $slug) {
            $this->assert_get_by_slug_fails($repository, $slug);
        }

        $this->assert_get_by_slug_fails($repository, 'unknown-faculty');
    }

    /**
     * Unknown and unsafe Faculty ids must fail closed.
     */
    public function test_get_by_faculty_id_rejects_unknown_alias_and_path_like_ids(): void {
        $repository = self::repository();

        foreach (self::FORBIDDEN_FACULTY_IDS as $facultyid) {
            $this->assert_get_by_faculty_id_fails($repository, $facultyid);
        }

        $this->assert_get_by_faculty_id_fails($repository, 'faculty_unknown');
    }

    /**
     * all_public() must not include profiles that are not public and published
     * if such profiles are ever present in the manifest.
     */
    public function test_all_public_excludes_non_public_or_non_published_profiles(): void {
        $repository = self::repository();

        foreach (self::normalize_profile_list($repository->all_public()) as $profile) {
            $this->assertSame('published', $profile['status']);
            $this->assertSame('public', $profile['visibility']);
        }
    }

    /**
     * Assert the documented Faculty Profile shape.
     *
     * @param array<string, mixed> $profile Faculty Profile data.
     */
    private function assert_has_required_faculty_profile_shape(array $profile): void {
        foreach (self::REQUIRED_PROFILE_FIELDS as $field) {
            $this->assertArrayHasKey($field, $profile, 'Missing Faculty Profile field: ' . $field);
        }

        $this->assertSame(self::FACULTY_SCHEMA_VERSION, $profile['schema_version']);
        $this->assertIsString($profile['faculty_id']);
        $this->assertIsString($profile['voie_id']);
        $this->assertIsString($profile['slug']);
        $this->assertContains($profile['status'], self::ALLOWED_STATUS);
        $this->assertContains($profile['visibility'], self::ALLOWED_VISIBILITY);

        $this->assertIsArray($profile['source_atlas']);
        foreach (self::REQUIRED_SOURCE_ATLAS_FIELDS as $field) {
            $this->assertArrayHasKey($field, $profile['source_atlas']);
        }

        $this->assertIsString($profile['source_atlas']['file']);
        $this->assertSame(self::ATLAS_SCHEMA_VERSION, $profile['source_atlas']['schema_version_expected']);
        $this->assertContains($profile['source_atlas']['sync_mode'], self::ALLOWED_SYNC_MODES);

        $this->assertIsArray($profile['moodle']);
        foreach (self::REQUIRED_MOODLE_FIELDS as $field) {
            $this->assertArrayHasKey($field, $profile['moodle']);
        }

        $this->assertIsString($profile['moodle']['category_idnumber']);
        $this->assertIsString($profile['moodle']['course_prefix']);
        $this->assertIsBool($profile['moodle']['public_course_listing']);
        $this->assertIsString($profile['moodle']['enrolment_visibility']);
        $this->assertIsString($profile['moodle']['hub_course_idnumber']);

        $this->assertIsArray($profile['identity']);
        $this->assertIsArray($profile['seo']);
        $this->assertIsArray($profile['hero']);
        $this->assertIsArray($profile['navigation']);
        $this->assertIsArray($profile['sections']);
        $this->assertIsArray($profile['atlas_projection']);
        $this->assertIsArray($profile['dynamic_blocks']);
        $this->assertIsArray($profile['featured_blocks']);
        $this->assertIsArray($profile['faq']);
        $this->assertIsArray($profile['contact']);
        $this->assertIsArray($profile['governance']);
        $this->assertIsArray($profile['cache']);
    }

    /**
     * Assert that a repository profile matches a manifest item.
     *
     * @param array<string, mixed> $profile Profile returned by repository.
     * @param array<string, mixed> $manifestitem Manifest item.
     */
    private function assert_profile_matches_manifest_item(array $profile, array $manifestitem): void {
        $this->assertSame($manifestitem['faculty_id'], $profile['faculty_id']);
        $this->assertSame($manifestitem['voie_id'], $profile['voie_id']);
        $this->assertSame($manifestitem['slug'], $profile['slug']);
        $this->assertSame($manifestitem['status'], $profile['status']);
        $this->assertSame($manifestitem['visibility'], $profile['visibility']);
        $this->assertSame($manifestitem['atlas_file'], $profile['source_atlas']['file']);
        $this->assertSame($manifestitem['category_idnumber'], $profile['moodle']['category_idnumber']);
        $this->assertSame($manifestitem['course_prefix'], $profile['moodle']['course_prefix']);
    }

    /**
     * Assert get_by_slug() fails closed.
     *
     * @param faculty_repository $repository Repository.
     * @param string $slug Slug candidate.
     */
    private function assert_get_by_slug_fails(faculty_repository $repository, string $slug): void {
        try {
            $repository->get_by_slug($slug);
            $this->fail('get_by_slug() accepted invalid slug: ' . $slug);
        } catch (Throwable $exception) {
            $this->assertNotSame('', $exception->getMessage());
        }
    }

    /**
     * Assert get_by_faculty_id() fails closed.
     *
     * @param faculty_repository $repository Repository.
     * @param string $facultyid Faculty id candidate.
     */
    private function assert_get_by_faculty_id_fails(faculty_repository $repository, string $facultyid): void {
        try {
            $repository->get_by_faculty_id($facultyid);
            $this->fail('get_by_faculty_id() accepted invalid Faculty id: ' . $facultyid);
        } catch (Throwable $exception) {
            $this->assertNotSame('', $exception->getMessage());
        }
    }

    /**
     * Instantiate the repository under test.
     *
     * @return faculty_repository
     */
    private static function repository(): faculty_repository {
        return new faculty_repository();
    }

    /**
     * Normalize possible repository list return forms into a numeric profile list.
     *
     * @param array<mixed> $profiles Profiles or wrapper array.
     * @return array<int, array<string, mixed>>
     */
    private static function normalize_profile_list(array $profiles): array {
        if (array_key_exists('items', $profiles) && is_array($profiles['items'])) {
            $profiles = $profiles['items'];
        }

        $normalized = [];

        foreach ($profiles as $profile) {
            self::assertIsArray($profile);
            $normalized[] = $profile;
        }

        return $normalized;
    }

    /**
     * Load Faculty manifest items indexed by a string field.
     *
     * @param string $field Field to index by.
     * @return array<string, array<string, mixed>>
     */
    private static function manifest_items_by_field(string $field): array {
        $manifest = self::read_json_file(self::faculty_dir() . DIRECTORY_SEPARATOR . 'faculty_manifest.json');

        self::assertArrayHasKey('items', $manifest);
        self::assertIsArray($manifest['items']);

        $indexed = [];

        foreach ($manifest['items'] as $item) {
            self::assertIsArray($item);
            self::assertArrayHasKey($field, $item);
            self::assertIsString($item[$field]);

            $indexed[$item[$field]] = $item;
        }

        return $indexed;
    }

    /**
     * Return the Faculty content directory.
     *
     * @return string
     */
    private static function faculty_dir(): string {
        global $CFG;

        return $CFG->dirroot . DIRECTORY_SEPARATOR . 'local' . DIRECTORY_SEPARATOR . 'uckk'
            . DIRECTORY_SEPARATOR . 'content' . DIRECTORY_SEPARATOR . 'faculties';
    }

    /**
     * Return the Atlas Voie directory.
     *
     * @return string
     */
    private static function atlas_voie_dir(): string {
        global $CFG;

        return $CFG->dirroot . DIRECTORY_SEPARATOR . 'local' . DIRECTORY_SEPARATOR . 'uckk'
            . DIRECTORY_SEPARATOR . 'atlas' . DIRECTORY_SEPARATOR . 'voies';
    }

    /**
     * Read and decode a JSON file as an associative array.
     *
     * @param string $path Absolute path.
     * @return array<string, mixed>
     */
    private static function read_json_file(string $path): array {
        self::assertFileExists($path);
        self::assertFileIsReadable($path);

        $contents = file_get_contents($path);
        self::assertNotFalse($contents);

        try {
            $decoded = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            self::fail(sprintf(
                'Invalid JSON in %s: %s',
                $path,
                $exception->getMessage()
            ));
        }

        self::assertIsArray($decoded);

        return $decoded;
    }
}
