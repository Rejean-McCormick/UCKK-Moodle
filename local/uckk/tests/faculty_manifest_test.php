<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

/**
 * PHPUnit tests for the UCKK Faculty manifest contract.
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

defined('MOODLE_INTERNAL') || die();

/**
 * Tests for local/uckk/content/faculties/faculty_manifest.json.
 *
 * The Faculty manifest is the public routing allow-list for the 10 canonical
 * Faculty profiles. It must contain stable identifiers and file pointers only;
 * it must not duplicate Atlas course content or Faculty page content.
 *
 * @coversNothing
 */
final class faculty_manifest_test extends advanced_testcase {
    /** Faculty manifest schema version. */
    private const MANIFEST_SCHEMA_VERSION = 'UCKK-FACULTY-MANIFEST-0.1';

    /** Faculty profile schema version. */
    private const FACULTY_SCHEMA_VERSION = 'UCKK-FACULTY-0.1';

    /** Atlas schema version expected by Faculty source_atlas blocks. */
    private const ATLAS_SCHEMA_VERSION = 'UCKK-ATLAS-0.2-draft';

    /** Required top-level manifest keys. */
    private const MANIFEST_KEYS = [
        'schema_version',
        'generated_from',
        'items',
    ];

    /** Required item keys. */
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

    /** Allowed status values from the Faculty Profile contract. */
    private const ALLOWED_STATUS = [
        'draft',
        'published',
        'archived',
    ];

    /** Allowed visibility values from the Faculty Profile contract. */
    private const ALLOWED_VISIBILITY = [
        'public',
        'hidden',
        'restricted',
    ];

    /** Keys that must not appear in the manifest because they belong elsewhere. */
    private const FORBIDDEN_MANIFEST_KEYS = [
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

    /** Forbidden slug aliases explicitly excluded by DOC_12. */
    private const FORBIDDEN_SLUGS = [
        'grand_jeu_social',
        'voie-grand-jeu-social',
        'uckk-grand-jeu-social',
        'faculte-grand-jeu-social',
        'grandjeusocial',
    ];

    /** Forbidden legacy or drifted Voie aliases. */
    private const FORBIDDEN_VOIE_IDS = [
        'voie_intelligence_artificielle_gouvernable',
        'voie_linguistique',
        'voie_koa',
    ];

    /**
     * Canonical Faculty manifest items from DOC_12.
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
     * The Faculty manifest must exist and expose the documented top-level shape.
     */
    public function test_manifest_file_exists_and_has_documented_top_level_contract(): void {
        $this->assertFileExists(self::manifest_path());

        $manifest = self::load_manifest();

        $this->assertSameCanonicalizing(self::MANIFEST_KEYS, array_keys($manifest));
        $this->assertSame(self::MANIFEST_SCHEMA_VERSION, $manifest['schema_version']);
        $this->assertSame('manual', $manifest['generated_from']);
        $this->assertIsArray($manifest['items']);
        $this->assertCount(10, $manifest['items']);
    }

    /**
     * The Faculty schema file must exist and decode as JSON.
     */
    public function test_faculty_schema_file_exists(): void {
        $this->assertFileExists(self::schema_path());

        $schema = self::read_json_file(self::schema_path());

        $this->assertIsArray($schema);
    }

    /**
     * The manifest must contain exactly the 10 canonical Faculty entries.
     */
    public function test_manifest_contains_exact_canonical_faculty_entries(): void {
        $itemsbyid = self::manifest_items_by_faculty_id();

        foreach (self::EXPECTED_ITEMS as $expected) {
            $facultyid = (string) $expected['faculty_id'];

            $this->assertArrayHasKey($facultyid, $itemsbyid);

            $actual = $itemsbyid[$facultyid];

            $this->assertSameCanonicalizing(self::ITEM_KEYS, array_keys($actual));
            $this->assertSame($expected['voie_id'], $actual['voie_id']);
            $this->assertSame($expected['slug'], $actual['slug']);
            $this->assertSame($expected['faculty_file'], $actual['faculty_file']);
            $this->assertSame($expected['atlas_file'], $actual['atlas_file']);
            $this->assertSame($expected['category_idnumber'], $actual['category_idnumber']);
            $this->assertSame($expected['course_prefix'], $actual['course_prefix']);
            $this->assertSame($expected['sortorder'], $actual['sortorder']);
        }
    }

    /**
     * Stable routing and mapping fields must be unique.
     */
    public function test_manifest_items_have_unique_stable_fields_and_sortorder(): void {
        $items = self::load_manifest_items();

        $facultyids = [];
        $voieids = [];
        $slugs = [];
        $facultyfiles = [];
        $atlasfiles = [];
        $categoryidnumbers = [];
        $courseprefixes = [];
        $sortorders = [];

        foreach ($items as $item) {
            $this->assertSameCanonicalizing(self::ITEM_KEYS, array_keys($item));

            $facultyids[] = $item['faculty_id'];
            $voieids[] = $item['voie_id'];
            $slugs[] = $item['slug'];
            $facultyfiles[] = $item['faculty_file'];
            $atlasfiles[] = $item['atlas_file'];
            $categoryidnumbers[] = $item['category_idnumber'];
            $courseprefixes[] = $item['course_prefix'];
            $sortorders[] = $item['sortorder'];
        }

        $this->assertSame($facultyids, array_values(array_unique($facultyids)));
        $this->assertSame($voieids, array_values(array_unique($voieids)));
        $this->assertSame($slugs, array_values(array_unique($slugs)));
        $this->assertSame($facultyfiles, array_values(array_unique($facultyfiles)));
        $this->assertSame($atlasfiles, array_values(array_unique($atlasfiles)));
        $this->assertSame($categoryidnumbers, array_values(array_unique($categoryidnumbers)));
        $this->assertSame($courseprefixes, array_values(array_unique($courseprefixes)));

        sort($sortorders);
        $this->assertSame(range(1, 10), $sortorders);
    }

    /**
     * The manifest order must match the canonical faculty order.
     */
    public function test_manifest_items_follow_canonical_sortorder(): void {
        $items = self::load_manifest_items();

        $actualfacultyids = array_map(
            static fn(array $item): string => (string) $item['faculty_id'],
            $items
        );

        $expectedfacultyids = array_map(
            static fn(array $item): string => (string) $item['faculty_id'],
            self::EXPECTED_ITEMS
        );

        $this->assertSame($expectedfacultyids, $actualfacultyids);
    }

    /**
     * Slugs must be canonical, safe, and public-routing compatible.
     */
    public function test_manifest_slugs_are_safe_canonical_and_not_forbidden_aliases(): void {
        foreach (self::load_manifest_items() as $item) {
            $slug = $item['slug'];

            $this->assertIsString($slug);
            $this->assertMatchesRegularExpression('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug);
            $this->assertSame($slug, basename($slug));
            $this->assertStringNotContainsString('/', $slug);
            $this->assertStringNotContainsString('\\', $slug);
            $this->assertNotContains($slug, self::FORBIDDEN_SLUGS);
        }
    }

    /**
     * Manifest file pointers must be basenames and point to existing JSON files
     * in the canonical directories.
     */
    public function test_manifest_points_only_to_existing_faculty_and_atlas_json_files(): void {
        $this->assertDirectoryExists(self::faculty_dir());
        $this->assertDirectoryExists(self::atlas_voie_dir());

        foreach (self::load_manifest_items() as $item) {
            $facultyfile = $item['faculty_file'];
            $atlasfile = $item['atlas_file'];

            $this->assert_safe_faculty_file_name($facultyfile);
            $this->assert_safe_atlas_file_name($atlasfile);

            $this->assertFileExists(self::faculty_dir() . DIRECTORY_SEPARATOR . $facultyfile);
            $this->assertFileExists(self::atlas_voie_dir() . DIRECTORY_SEPARATOR . $atlasfile);
        }
    }

    /**
     * Every manifest item must match its Faculty Profile identity fields.
     */
    public function test_manifest_items_match_faculty_profile_identity_fields(): void {
        foreach (self::load_manifest_items() as $item) {
            $profile = self::read_json_file(self::faculty_dir() . DIRECTORY_SEPARATOR . $item['faculty_file']);

            $this->assertSame(self::FACULTY_SCHEMA_VERSION, $profile['schema_version'] ?? null);
            $this->assertSame($item['faculty_id'], $profile['faculty_id'] ?? null);
            $this->assertSame($item['voie_id'], $profile['voie_id'] ?? null);
            $this->assertSame($item['slug'], $profile['slug'] ?? null);
            $this->assertSame($item['status'], $profile['status'] ?? null);
            $this->assertSame($item['visibility'], $profile['visibility'] ?? null);
        }
    }

    /**
     * Every manifest item must match its Faculty Profile Atlas source block.
     */
    public function test_manifest_items_match_faculty_profile_source_atlas_block(): void {
        foreach (self::load_manifest_items() as $item) {
            $profile = self::read_json_file(self::faculty_dir() . DIRECTORY_SEPARATOR . $item['faculty_file']);

            $this->assertArrayHasKey('source_atlas', $profile);
            $this->assertIsArray($profile['source_atlas']);

            $this->assertSame($item['atlas_file'], $profile['source_atlas']['file'] ?? null);
            $this->assertSame(
                self::ATLAS_SCHEMA_VERSION,
                $profile['source_atlas']['schema_version_expected'] ?? null
            );
            $this->assertArrayHasKey('sync_mode', $profile['source_atlas']);
            $this->assertContains($profile['source_atlas']['sync_mode'], [
                'read_only',
                'preview_only',
                'moodle_sync_allowed',
            ]);
        }
    }

    /**
     * Every manifest item must match its Faculty Profile Moodle mapping block.
     */
    public function test_manifest_items_match_faculty_profile_moodle_mapping_block(): void {
        foreach (self::load_manifest_items() as $item) {
            $profile = self::read_json_file(self::faculty_dir() . DIRECTORY_SEPARATOR . $item['faculty_file']);

            $this->assertArrayHasKey('moodle', $profile);
            $this->assertIsArray($profile['moodle']);

            $this->assertSame($item['category_idnumber'], $profile['moodle']['category_idnumber'] ?? null);
            $this->assertSame($item['course_prefix'], $profile['moodle']['course_prefix'] ?? null);
        }
    }

    /**
     * Every manifest item must match its Atlas Voie identity fields.
     */
    public function test_manifest_items_match_atlas_voie_identity_fields(): void {
        foreach (self::load_manifest_items() as $item) {
            $atlas = self::read_json_file(self::atlas_voie_dir() . DIRECTORY_SEPARATOR . $item['atlas_file']);

            $this->assertSame(self::ATLAS_SCHEMA_VERSION, $atlas['schema_version'] ?? null);
            $this->assertSame($item['voie_id'], $atlas['voie_id'] ?? null);
            $this->assertSame($item['course_prefix'], $atlas['code'] ?? null);
        }
    }

    /**
     * Manifest status and visibility must use documented values only.
     */
    public function test_manifest_status_and_visibility_values_are_documented(): void {
        foreach (self::load_manifest_items() as $item) {
            $this->assertContains($item['status'], self::ALLOWED_STATUS);
            $this->assertContains($item['visibility'], self::ALLOWED_VISIBILITY);
        }
    }

    /**
     * The manifest must not duplicate Faculty Profile or Atlas content.
     */
    public function test_manifest_does_not_duplicate_faculty_profile_or_atlas_content(): void {
        $manifest = self::load_manifest();

        foreach (self::FORBIDDEN_MANIFEST_KEYS as $key) {
            $this->assertArrayNotHasKey($key, $manifest);
        }

        foreach (self::load_manifest_items() as $item) {
            foreach (self::FORBIDDEN_MANIFEST_KEYS as $key) {
                $this->assertArrayNotHasKey($key, $item);
            }
        }
    }

    /**
     * Drifted legacy Voie aliases must never enter the Faculty manifest.
     */
    public function test_manifest_rejects_forbidden_voie_id_aliases(): void {
        $voieids = array_column(self::load_manifest_items(), 'voie_id');

        foreach (self::FORBIDDEN_VOIE_IDS as $forbidden) {
            $this->assertNotContains($forbidden, $voieids);
        }
    }

    /**
     * The manifest must map each canonical Faculty id to its canonical file.
     */
    public function test_manifest_faculty_ids_match_canonical_file_names(): void {
        $itemsbyid = self::manifest_items_by_faculty_id();

        foreach (self::EXPECTED_ITEMS as $expected) {
            $facultyid = (string) $expected['faculty_id'];

            $this->assertArrayHasKey($facultyid, $itemsbyid);
            $this->assertSame($expected['faculty_file'], $itemsbyid[$facultyid]['faculty_file']);
        }
    }

    /**
     * Assert a safe Faculty Profile file name.
     *
     * @param mixed $filename File name.
     */
    private function assert_safe_faculty_file_name(mixed $filename): void {
        $this->assertIsString($filename);
        $this->assertSame($filename, basename($filename));
        $this->assertStringEndsWith('.faculty.json', $filename);
        $this->assertMatchesRegularExpression('/^[a-z0-9]+(?:-[a-z0-9]+)*\.faculty\.json$/', $filename);
        $this->assertStringNotContainsString('/', $filename);
        $this->assertStringNotContainsString('\\', $filename);
    }

    /**
     * Assert a safe Atlas Voie file name.
     *
     * @param mixed $filename File name.
     */
    private function assert_safe_atlas_file_name(mixed $filename): void {
        $this->assertIsString($filename);
        $this->assertSame($filename, basename($filename));
        $this->assertStringEndsWith('.json', $filename);
        $this->assertMatchesRegularExpression('/^voie_[a-z0-9_]+\.json$/', $filename);
        $this->assertStringNotContainsString('/', $filename);
        $this->assertStringNotContainsString('\\', $filename);
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
     * Return the Faculty schema path.
     *
     * @return string
     */
    private static function schema_path(): string {
        return self::faculty_dir() . DIRECTORY_SEPARATOR . 'faculty_schema.json';
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
     * Load manifest items.
     *
     * @return array<int, array<string, mixed>>
     */
    private static function load_manifest_items(): array {
        $manifest = self::load_manifest();

        self::assertArrayHasKey('items', $manifest);
        self::assertIsArray($manifest['items']);

        $items = [];

        foreach ($manifest['items'] as $item) {
            self::assertIsArray($item);
            $items[] = $item;
        }

        return $items;
    }

    /**
     * Load manifest items indexed by faculty_id.
     *
     * @return array<string, array<string, mixed>>
     */
    private static function manifest_items_by_faculty_id(): array {
        $itemsbyid = [];

        foreach (self::load_manifest_items() as $item) {
            self::assertArrayHasKey('faculty_id', $item);
            self::assertIsString($item['faculty_id']);

            $itemsbyid[$item['faculty_id']] = $item;
        }

        return $itemsbyid;
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