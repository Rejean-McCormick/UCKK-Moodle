<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

/**
 * PHPUnit tests for the UCKK Faculty registry.
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
use local_uckk\local\faculty\faculty_registry;
use Throwable;

defined('MOODLE_INTERNAL') || die();

/**
 * Tests for local_uckk\local\faculty\faculty_registry.
 *
 * The registry is the public routing allow-list for Faculty pages. It resolves
 * only canonical slugs and faculty ids declared in faculty_manifest.json. It
 * must return minimal metadata only, and must never accept a file path from
 * request-facing input.
 *
 * @covers \local_uckk\local\faculty\faculty_registry
 */
final class faculty_registry_test extends advanced_testcase {
    /** Faculty manifest schema version. */
    private const MANIFEST_SCHEMA_VERSION = 'UCKK-FACULTY-MANIFEST-0.1';

    /** Required stable metadata keys returned by the registry. */
    private const ITEM_KEYS = [
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

    /** Keys that belong to Faculty Profiles, Atlas Voies, Moodle runtime, or rendering. */
    private const FORBIDDEN_REGISTRY_KEYS = [
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
        'courses',
        'cours_conceptuels',
        'concept_maitre',
        'concepts_associes',
        'criteres_passage',
        'projet_final',
        'limites_ethiques',
        'relations_intervoies',
        'tags',
        'html',
        'rendered',
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
        'content/faculties/grand-jeu-social',
        'content/faculties/grand-jeu-social.faculty.json',
        'voies/voie_grand_jeu_social.json',
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
     * Canonical Faculty registry items from DOC_12.
     *
     * @var array<int, array<string, int|string>>
     */
    private const EXPECTED_ITEMS = [
        [
            'faculty_id' => 'faculty_grand_jeu_social',
            'voie_id' => 'voie_grand_jeu_social',
            'slug' => 'grand-jeu-social',
            'faculty_file' => 'grand-jeu-social.faculty.json',
            'atlas_file' => 'voie_grand_jeu_social.json',
            'status' => 'published',
            'visibility' => 'public',
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
            'status' => 'published',
            'visibility' => 'public',
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
            'status' => 'published',
            'visibility' => 'public',
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
            'status' => 'published',
            'visibility' => 'public',
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
            'status' => 'published',
            'visibility' => 'public',
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
            'status' => 'published',
            'visibility' => 'public',
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
            'status' => 'published',
            'visibility' => 'public',
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
            'status' => 'published',
            'visibility' => 'public',
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
            'status' => 'published',
            'visibility' => 'public',
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
            'status' => 'published',
            'visibility' => 'public',
            'category_idnumber' => 'UCKK-KOA',
            'course_prefix' => 'KOA',
            'sortorder' => 10,
        ],
    ];

    /**
     * The registry class and its canonical static methods must exist.
     */
    public function test_registry_exposes_canonical_static_methods(): void {
        $this->assertTrue(
            class_exists(faculty_registry::class),
            faculty_registry::class . ' must exist.'
        );

        $this->assertTrue(method_exists(faculty_registry::class, 'all'));
        $this->assertTrue(method_exists(faculty_registry::class, 'get_by_slug'));
        $this->assertTrue(method_exists(faculty_registry::class, 'get_by_faculty_id'));
        $this->assertTrue(method_exists(faculty_registry::class, 'exists_slug'));
    }

    /**
     * all() must return exactly the 10 canonical registry items.
     */
    public function test_all_returns_exactly_ten_canonical_faculty_items(): void {
        $items = self::registry_items();

        $this->assertCount(10, $items);

        $itemsbyid = self::items_by_field($items, 'faculty_id');

        foreach (self::EXPECTED_ITEMS as $expected) {
            $facultyid = (string) $expected['faculty_id'];

            $this->assertArrayHasKey($facultyid, $itemsbyid);
            $this->assert_registry_entry_matches_expected($expected, $itemsbyid[$facultyid]);
        }
    }

    /**
     * all() must preserve canonical manifest sortorder.
     */
    public function test_all_returns_items_in_canonical_sortorder(): void {
        $items = self::registry_items();

        $actualids = array_map(
            static fn(array $item): string => (string) $item['faculty_id'],
            $items
        );

        $expectedids = array_map(
            static fn(array $item): string => (string) $item['faculty_id'],
            self::EXPECTED_ITEMS
        );

        $this->assertSame($expectedids, $actualids);
    }

    /**
     * all() must match faculty_manifest.json and not invent routes.
     */
    public function test_all_matches_faculty_manifest_json(): void {
        $manifest = self::load_manifest();

        $this->assertSame(self::MANIFEST_SCHEMA_VERSION, $manifest['schema_version']);
        $this->assertArrayHasKey('items', $manifest);
        $this->assertIsArray($manifest['items']);

        $manifestitems = self::normalize_item_list($manifest['items']);
        $registryitems = self::registry_items();

        $this->assertSame(
            self::items_by_field($manifestitems, 'faculty_id'),
            self::items_by_field($registryitems, 'faculty_id')
        );
    }

    /**
     * get_by_slug() must resolve every canonical slug.
     */
    public function test_get_by_slug_resolves_all_ten_canonical_slugs(): void {
        foreach (self::EXPECTED_ITEMS as $expected) {
            $entry = faculty_registry::get_by_slug((string) $expected['slug']);

            $this->assertIsArray($entry);
            $this->assert_registry_entry_matches_expected($expected, $entry);
        }
    }

    /**
     * get_by_faculty_id() must resolve every canonical Faculty id.
     */
    public function test_get_by_faculty_id_resolves_all_ten_canonical_faculty_ids(): void {
        foreach (self::EXPECTED_ITEMS as $expected) {
            $entry = faculty_registry::get_by_faculty_id((string) $expected['faculty_id']);

            $this->assertIsArray($entry);
            $this->assert_registry_entry_matches_expected($expected, $entry);
        }
    }

    /**
     * get_by_slug() and get_by_faculty_id() must resolve the same metadata.
     */
    public function test_get_by_slug_and_get_by_faculty_id_return_same_entry(): void {
        foreach (self::EXPECTED_ITEMS as $expected) {
            $byslug = faculty_registry::get_by_slug((string) $expected['slug']);
            $byfacultyid = faculty_registry::get_by_faculty_id((string) $expected['faculty_id']);

            $this->assertSame($byfacultyid, $byslug);
        }
    }

    /**
     * exists_slug() must return true for every canonical slug.
     */
    public function test_exists_slug_returns_true_for_all_canonical_slugs(): void {
        foreach (self::EXPECTED_ITEMS as $expected) {
            $this->assertTrue(
                faculty_registry::exists_slug((string) $expected['slug']),
                'Canonical slug should exist: ' . $expected['slug']
            );
        }
    }

    /**
     * Unknown and unsafe slugs must fail closed.
     */
    public function test_get_by_slug_rejects_unknown_alias_and_path_like_slugs(): void {
        foreach (self::FORBIDDEN_SLUGS as $slug) {
            $this->assert_get_by_slug_fails($slug);
        }

        $this->assert_get_by_slug_fails('unknown-faculty');
    }

    /**
     * exists_slug() must not treat unknown aliases or paths as valid.
     */
    public function test_exists_slug_rejects_unknown_alias_and_path_like_slugs(): void {
        foreach (array_merge(self::FORBIDDEN_SLUGS, ['unknown-faculty']) as $slug) {
            try {
                $this->assertFalse(
                    faculty_registry::exists_slug($slug),
                    'exists_slug() must return false for invalid slug: ' . $slug
                );
            } catch (Throwable $exception) {
                $this->assertNotSame('', $exception->getMessage());
            }
        }
    }

    /**
     * Unknown and unsafe Faculty ids must fail closed.
     */
    public function test_get_by_faculty_id_rejects_unknown_alias_and_path_like_ids(): void {
        foreach (self::FORBIDDEN_FACULTY_IDS as $facultyid) {
            $this->assert_get_by_faculty_id_fails($facultyid);
        }

        $this->assert_get_by_faculty_id_fails('faculty_unknown');
    }

    /**
     * Registry items must contain only minimal manifest metadata.
     */
    public function test_registry_returns_minimal_metadata_only(): void {
        foreach (self::registry_items() as $item) {
            $this->assertSameCanonicalizing(self::ITEM_KEYS, array_keys($item));

            foreach (self::FORBIDDEN_REGISTRY_KEYS as $key) {
                $this->assertArrayNotHasKey($key, $item, 'Registry leaked non-metadata key: ' . $key);
            }
        }
    }

    /**
     * Registry file pointers must be safe basenames and must point to existing files.
     */
    public function test_registry_file_pointers_are_safe_and_existing(): void {
        foreach (self::registry_items() as $item) {
            $facultyfile = $item['faculty_file'];
            $atlasfile = $item['atlas_file'];

            $this->assertIsString($facultyfile);
            $this->assertSame($facultyfile, basename($facultyfile));
            $this->assertStringEndsWith('.faculty.json', $facultyfile);
            $this->assertMatchesRegularExpression('/^[a-z0-9]+(?:-[a-z0-9]+)*\.faculty\.json$/', $facultyfile);
            $this->assertStringNotContainsString('/', $facultyfile);
            $this->assertStringNotContainsString('\\', $facultyfile);
            $this->assertFileExists(self::faculty_dir() . DIRECTORY_SEPARATOR . $facultyfile);

            $this->assertIsString($atlasfile);
            $this->assertSame($atlasfile, basename($atlasfile));
            $this->assertStringEndsWith('.json', $atlasfile);
            $this->assertMatchesRegularExpression('/^voie_[a-z0-9_]+\.json$/', $atlasfile);
            $this->assertStringNotContainsString('/', $atlasfile);
            $this->assertStringNotContainsString('\\', $atlasfile);
            $this->assertFileExists(self::atlas_voie_dir() . DIRECTORY_SEPARATOR . $atlasfile);
        }
    }

    /**
     * Registry output must match the identity fields in each Faculty Profile.
     */
    public function test_registry_items_match_faculty_profile_identity_fields(): void {
        foreach (self::registry_items() as $item) {
            $profile = self::read_json_file(self::faculty_dir() . DIRECTORY_SEPARATOR . $item['faculty_file']);

            $this->assertSame($item['faculty_id'], $profile['faculty_id'] ?? null);
            $this->assertSame($item['voie_id'], $profile['voie_id'] ?? null);
            $this->assertSame($item['slug'], $profile['slug'] ?? null);
            $this->assertSame($item['status'], $profile['status'] ?? null);
            $this->assertSame($item['visibility'], $profile['visibility'] ?? null);
            $this->assertSame($item['atlas_file'], $profile['source_atlas']['file'] ?? null);
            $this->assertSame($item['category_idnumber'], $profile['moodle']['category_idnumber'] ?? null);
            $this->assertSame($item['course_prefix'], $profile['moodle']['course_prefix'] ?? null);
        }
    }

    /**
     * Registry output must match the identity fields in each Atlas Voie JSON file.
     */
    public function test_registry_items_match_atlas_voie_identity_fields(): void {
        foreach (self::registry_items() as $item) {
            $atlas = self::read_json_file(self::atlas_voie_dir() . DIRECTORY_SEPARATOR . $item['atlas_file']);

            $this->assertSame($item['voie_id'], $atlas['voie_id'] ?? null);
            $this->assertSame($item['course_prefix'], $atlas['code'] ?? null);
        }
    }

    /**
     * Assert a registry entry against its canonical expected metadata.
     *
     * @param array<string, int|string> $expected Expected metadata.
     * @param array<string, mixed> $actual Actual metadata.
     */
    private function assert_registry_entry_matches_expected(array $expected, array $actual): void {
        $this->assertSameCanonicalizing(self::ITEM_KEYS, array_keys($actual));

        foreach ($expected as $key => $value) {
            $this->assertArrayHasKey($key, $actual);
            $this->assertSame($value, $actual[$key], 'Mismatch for registry field: ' . $key);
        }
    }

    /**
     * Assert get_by_slug() fails closed.
     *
     * @param string $slug Slug candidate.
     */
    private function assert_get_by_slug_fails(string $slug): void {
        try {
            faculty_registry::get_by_slug($slug);
            $this->fail('get_by_slug() accepted invalid slug: ' . $slug);
        } catch (Throwable $exception) {
            $this->assertNotSame('', $exception->getMessage());
        }
    }

    /**
     * Assert get_by_faculty_id() fails closed.
     *
     * @param string $facultyid Faculty id candidate.
     */
    private function assert_get_by_faculty_id_fails(string $facultyid): void {
        try {
            faculty_registry::get_by_faculty_id($facultyid);
            $this->fail('get_by_faculty_id() accepted invalid Faculty id: ' . $facultyid);
        } catch (Throwable $exception) {
            $this->assertNotSame('', $exception->getMessage());
        }
    }

    /**
     * Return registry items as a numeric list.
     *
     * @return array<int, array<string, mixed>>
     */
    private static function registry_items(): array {
        $items = faculty_registry::all();

        self::assertIsArray($items);

        return self::normalize_item_list($items);
    }

    /**
     * Normalize possible array return forms into a numeric list of item arrays.
     *
     * @param array<mixed> $items Items or manifest-like array.
     * @return array<int, array<string, mixed>>
     */
    private static function normalize_item_list(array $items): array {
        if (array_key_exists('items', $items) && is_array($items['items'])) {
            $items = $items['items'];
        }

        $normalized = [];

        foreach ($items as $item) {
            self::assertIsArray($item);
            $normalized[] = $item;
        }

        return $normalized;
    }

    /**
     * Index items by a string field.
     *
     * @param array<int, array<string, mixed>> $items Items.
     * @param string $field Field name.
     * @return array<string, array<string, mixed>>
     */
    private static function items_by_field(array $items, string $field): array {
        $indexed = [];

        foreach ($items as $item) {
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
     * Return the Faculty manifest path.
     *
     * @return string
     */
    private static function manifest_path(): string {
        return self::faculty_dir() . DIRECTORY_SEPARATOR . 'faculty_manifest.json';
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
     * Load the Faculty manifest.
     *
     * @return array<string, mixed>
     */
    private static function load_manifest(): array {
        return self::read_json_file(self::manifest_path());
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