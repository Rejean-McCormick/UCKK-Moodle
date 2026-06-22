<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

/**
 * PHPUnit tests for the UCKK Atlas manifest contract.
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
 * Tests for local_uckk/atlas/atlas_manifest.json.
 *
 * This test protects the Atlas manifest as a stable pointer file. The manifest
 * must list the 10 canonical Voies and their stable Moodle mapping variables,
 * without duplicating Atlas course content or public Faculty page content.
 *
 * @coversNothing
 */
final class atlas_manifest_test extends advanced_testcase {
    /** Atlas manifest schema version. */
    private const MANIFEST_SCHEMA_VERSION = 'UCKK-ATLAS-MANIFEST-0.1';

    /** Atlas Voie schema version referenced by the manifest. */
    private const ATLAS_SCHEMA_VERSION = 'UCKK-ATLAS-0.2-draft';

    /** Required top-level manifest keys. */
    private const MANIFEST_KEYS = [
        'schema_version',
        'atlas_schema_version',
        'items',
    ];

    /** Required stable item keys. */
    private const ITEM_KEYS = [
        'voie_id',
        'code',
        'nom',
        'file',
        'course_prefix',
        'category_idnumber',
        'sortorder',
    ];

    /** Keys that must not appear in the Atlas manifest. */
    private const FORBIDDEN_MANIFEST_KEYS = [
        'courses',
        'cours_conceptuels',
        'concept_maitre',
        'concepts_associes',
        'criteres_passage',
        'sections',
        'navigation',
        'atlas_projection',
        'dynamic_blocks',
        'featured_blocks',
        'faq',
        'contact',
        'annonces',
        'announcements',
        'events',
    ];

    /** Forbidden legacy or drifted voie_id aliases. */
    private const FORBIDDEN_VOIE_IDS = [
        'voie_intelligence_artificielle_gouvernable',
        'voie_linguistique',
        'voie_koa',
    ];

    /**
     * Canonical Atlas manifest items from DOC_12.
     *
     * @var array<int, array<string, int|string>>
     */
    private const EXPECTED_ITEMS = [
        [
            'voie_id' => 'voie_grand_jeu_social',
            'code' => 'GJS',
            'file' => 'voie_grand_jeu_social.json',
            'course_prefix' => 'GJS',
            'category_idnumber' => 'UCKK-GJS',
            'sortorder' => 1,
        ],
        [
            'voie_id' => 'voie_economie',
            'code' => 'EC',
            'file' => 'voie_economie.json',
            'course_prefix' => 'EC',
            'category_idnumber' => 'UCKK-EC',
            'sortorder' => 2,
        ],
        [
            'voie_id' => 'voie_ecologie',
            'code' => 'ECL',
            'file' => 'voie_ecologie.json',
            'course_prefix' => 'ECL',
            'category_idnumber' => 'UCKK-ECL',
            'sortorder' => 3,
        ],
        [
            'voie_id' => 'voie_sciences_politiques',
            'code' => 'SP',
            'file' => 'voie_sciences_politiques.json',
            'course_prefix' => 'SP',
            'category_idnumber' => 'UCKK-SP',
            'sortorder' => 4,
        ],
        [
            'voie_id' => 'voie_linguistique_architecture_du_sens',
            'code' => 'LI',
            'file' => 'voie_linguistique_architecture_du_sens.json',
            'course_prefix' => 'LI',
            'category_idnumber' => 'UCKK-LI',
            'sortorder' => 5,
        ],
        [
            'voie_id' => 'voie_metaphysique',
            'code' => 'ME',
            'file' => 'voie_metaphysique.json',
            'course_prefix' => 'ME',
            'category_idnumber' => 'UCKK-ME',
            'sortorder' => 6,
        ],
        [
            'voie_id' => 'voie_ia_gouvernable',
            'code' => 'IA',
            'file' => 'voie_ia_gouvernable.json',
            'course_prefix' => 'IA',
            'category_idnumber' => 'UCKK-IA',
            'sortorder' => 7,
        ],
        [
            'voie_id' => 'voie_intervention_sociale_systemes_humains',
            'code' => 'IS',
            'file' => 'voie_intervention_sociale_systemes_humains.json',
            'course_prefix' => 'IS',
            'category_idnumber' => 'UCKK-IS',
            'sortorder' => 8,
        ],
        [
            'voie_id' => 'voie_architecture_sociotechnique',
            'code' => 'AS',
            'file' => 'voie_architecture_sociotechnique.json',
            'course_prefix' => 'AS',
            'category_idnumber' => 'UCKK-AS',
            'sortorder' => 9,
        ],
        [
            'voie_id' => 'voie_ecosysteme_digital_koa',
            'code' => 'KOA',
            'file' => 'voie_ecosysteme_digital_koa.json',
            'course_prefix' => 'KOA',
            'category_idnumber' => 'UCKK-KOA',
            'sortorder' => 10,
        ],
    ];

    /**
     * The Atlas manifest file must exist, decode as JSON, and expose only the
     * documented top-level contract.
     */
    public function test_manifest_file_exists_and_has_documented_top_level_contract(): void {
        $this->assertFileExists(self::manifest_path());

        $manifest = self::load_manifest();

        $this->assertSameCanonicalizing(self::MANIFEST_KEYS, array_keys($manifest));
        $this->assertSame(self::MANIFEST_SCHEMA_VERSION, $manifest['schema_version']);
        $this->assertSame(self::ATLAS_SCHEMA_VERSION, $manifest['atlas_schema_version']);
        $this->assertIsArray($manifest['items']);
        $this->assertCount(10, $manifest['items']);
    }

    /**
     * The Atlas schema file referenced by the manifest contract must exist.
     */
    public function test_atlas_schema_file_exists(): void {
        $this->assertFileExists(self::schema_path());

        $schema = self::read_json_file(self::schema_path());

        $this->assertIsArray($schema);
    }

    /**
     * The manifest must contain the exact 10 canonical Voie entries.
     */
    public function test_manifest_contains_exact_canonical_voie_entries(): void {
        $itemsbyid = self::manifest_items_by_voie_id();

        foreach (self::EXPECTED_ITEMS as $expected) {
            $voieid = (string) $expected['voie_id'];

            $this->assertArrayHasKey($voieid, $itemsbyid);

            $actual = $itemsbyid[$voieid];

            $this->assertSameCanonicalizing(self::ITEM_KEYS, array_keys($actual));
            $this->assertSame($expected['code'], $actual['code']);
            $this->assertSame($expected['file'], $actual['file']);
            $this->assertSame($expected['course_prefix'], $actual['course_prefix']);
            $this->assertSame($expected['category_idnumber'], $actual['category_idnumber']);
            $this->assertSame($expected['sortorder'], $actual['sortorder']);

            $this->assertArrayHasKey('nom', $actual);
            $this->assertIsString($actual['nom']);
            $this->assertNotSame('', trim($actual['nom']));
        }
    }

    /**
     * Stable manifest identity fields must be unique and ordered from 1 to 10.
     */
    public function test_manifest_items_have_unique_stable_fields_and_sortorder(): void {
        $items = self::load_manifest_items();

        $voieids = [];
        $codes = [];
        $files = [];
        $courseprefixes = [];
        $categoryidnumbers = [];
        $sortorders = [];

        foreach ($items as $item) {
            $this->assertSameCanonicalizing(self::ITEM_KEYS, array_keys($item));

            $voieids[] = $item['voie_id'];
            $codes[] = $item['code'];
            $files[] = $item['file'];
            $courseprefixes[] = $item['course_prefix'];
            $categoryidnumbers[] = $item['category_idnumber'];
            $sortorders[] = $item['sortorder'];
        }

        $this->assertSame($voieids, array_values(array_unique($voieids)));
        $this->assertSame($codes, array_values(array_unique($codes)));
        $this->assertSame($files, array_values(array_unique($files)));
        $this->assertSame($courseprefixes, array_values(array_unique($courseprefixes)));
        $this->assertSame($categoryidnumbers, array_values(array_unique($categoryidnumbers)));

        sort($sortorders);
        $this->assertSame(range(1, 10), $sortorders);
    }

    /**
     * Manifest file pointers must be safe basenames and must point to existing
     * JSON files inside local/uckk/atlas/voies/.
     */
    public function test_manifest_points_only_to_existing_voie_json_files(): void {
        $this->assertDirectoryExists(self::voies_dir());

        foreach (self::load_manifest_items() as $item) {
            $file = $item['file'];

            $this->assertIsString($file);
            $this->assertSame($file, basename($file));
            $this->assertStringEndsWith('.json', $file);
            $this->assertMatchesRegularExpression('/^voie_[a-z0-9_]+\.json$/', $file);
            $this->assertStringNotContainsString('/', $file);
            $this->assertStringNotContainsString('\\', $file);

            $voiepath = self::voies_dir() . DIRECTORY_SEPARATOR . $file;
            $this->assertFileExists($voiepath);

            $voie = self::read_json_file($voiepath);

            $this->assertSame(self::ATLAS_SCHEMA_VERSION, $voie['schema_version'] ?? null);
            $this->assertSame($item['voie_id'], $voie['voie_id'] ?? null);
            $this->assertSame($item['code'], $voie['code'] ?? null);
        }
    }

    /**
     * The manifest must not duplicate Atlas course internals or Faculty public
     * page content.
     */
    public function test_manifest_does_not_duplicate_voie_or_faculty_content(): void {
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
     * Drifted legacy voie_id aliases must never enter the Atlas manifest.
     */
    public function test_manifest_rejects_forbidden_voie_id_aliases(): void {
        $items = self::load_manifest_items();
        $voieids = array_column($items, 'voie_id');

        foreach (self::FORBIDDEN_VOIE_IDS as $forbidden) {
            $this->assertNotContains($forbidden, $voieids);
        }
    }

    /**
     * The manifest must map each canonical voie_id to its canonical file.
     */
    public function test_manifest_voie_ids_match_canonical_file_names(): void {
        $itemsbyid = self::manifest_items_by_voie_id();

        foreach (self::EXPECTED_ITEMS as $expected) {
            $voieid = (string) $expected['voie_id'];

            $this->assertArrayHasKey($voieid, $itemsbyid);
            $this->assertSame($expected['file'], $itemsbyid[$voieid]['file']);
        }
    }

    /**
     * Return the Atlas root directory.
     *
     * @return string
     */
    private static function atlas_root(): string {
        global $CFG;

        return $CFG->dirroot . DIRECTORY_SEPARATOR . 'local' . DIRECTORY_SEPARATOR . 'uckk'
            . DIRECTORY_SEPARATOR . 'atlas';
    }

    /**
     * Return the Atlas manifest path.
     *
     * @return string
     */
    private static function manifest_path(): string {
        return self::atlas_root() . DIRECTORY_SEPARATOR . 'atlas_manifest.json';
    }

    /**
     * Return the Atlas schema path.
     *
     * @return string
     */
    private static function schema_path(): string {
        return self::atlas_root() . DIRECTORY_SEPARATOR . 'atlas_schema.json';
    }

    /**
     * Return the Atlas Voies directory.
     *
     * @return string
     */
    private static function voies_dir(): string {
        return self::atlas_root() . DIRECTORY_SEPARATOR . 'voies';
    }

    /**
     * Load the Atlas manifest.
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
     * Load manifest items indexed by voie_id.
     *
     * @return array<string, array<string, mixed>>
     */
    private static function manifest_items_by_voie_id(): array {
        $itemsbyid = [];

        foreach (self::load_manifest_items() as $item) {
            self::assertArrayHasKey('voie_id', $item);
            self::assertIsString($item['voie_id']);

            $itemsbyid[$item['voie_id']] = $item;
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