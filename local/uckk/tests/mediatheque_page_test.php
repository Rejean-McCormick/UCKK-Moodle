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
 * PHPUnit tests for the public Médiathèque page contract.
 *
 * @package    local_uckk
 * @category   test
 * @copyright  2026 Univers-Cité King Klown
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace local_uckk;

use advanced_testcase;
use context_system;
use local_uckk\local\public_pages;
use local_uckk\output\public_page;

defined('MOODLE_INTERNAL') || die();

/**
 * Tests the local_uckk public-page contract for the Médiathèque.
 *
 * These tests intentionally stay at the local_uckk public-shell level.
 * They must not test the archive media engine, media policies, File API access,
 * content advisories, cultural protocols, or media search implementation.
 *
 * @covers \local_uckk\local\public_pages
 * @covers \local_uckk\output\public_page
 */
final class mediatheque_page_test extends advanced_testcase {
    /**
     * Reset Moodle state for each test.
     *
     * @return void
     */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
    }

    /**
     * The public controller must exist as a thin local_uckk route.
     *
     * @return void
     */
    public function test_mediatheque_controller_exists_and_uses_public_page_contract(): void {
        global $CFG;

        $path = $CFG->dirroot . '/local/uckk/mediatheque.php';

        $this->assertFileExists($path);

        $source = file_get_contents($path);
        $this->assertIsString($source);

        $this->assertStringContainsString("\$pagekey = 'mediatheque';", $source);
        $this->assertStringContainsString('context_system::instance()', $source);

        $this->assertStringContainsString("optional_param('cmid', 0, PARAM_INT)", $source);
        $this->assertStringContainsString("optional_param('archiveid', 0, PARAM_INT)", $source);

        $this->assertStringContainsString('public_pages::setup_page($pagekey, $context)', $source);
        $this->assertStringContainsString("js_call_amd('local_uckk/mediatheque_explorer', 'init'", $source);
        $this->assertStringContainsString("'service' => 'mod_uckkarchive_search_mediatheque'", $source);
        $this->assertStringContainsString("'mediatheque_initial_state' => \$initialstate", $source);
        $this->assertStringContainsString("'has_mediatheque_explorer' => true", $source);
        $this->assertStringContainsString('new \\local_uckk\\output\\public_page($pagekey', $source);

        // The local public controller must not become the media engine.
        $this->assertStringNotContainsString('uckkarchive_media', $source);
        $this->assertStringNotContainsString('$DB->', $source);
        $this->assertStringNotContainsString('get_records_sql', $source);
        $this->assertStringNotContainsString('SELECT ', $source);
        $this->assertStringNotContainsString('require_login()', $source);
        $this->assertStringNotContainsString('require_capability(', $source);
    }

    /**
     * The public controller must pass cmid/archiveid through the public initial state.
     *
     * @return void
     */
    public function test_mediatheque_controller_passes_scope_to_amd_initial_state(): void {
        global $CFG;

        $path = $CFG->dirroot . '/local/uckk/mediatheque.php';

        $this->assertFileExists($path);

        $source = file_get_contents($path);
        $this->assertIsString($source);

        $this->assertStringContainsString("'cmid' => max(0, \$cmid)", $source);
        $this->assertStringContainsString("'archiveid' => max(0, \$archiveid)", $source);
        $this->assertStringContainsString("'query' => \$urlparams['q']", $source);

        $this->assertStringContainsString("'filters' => \$filters", $source);
        $this->assertStringContainsString("'type' => \$detailtype", $source);
        $this->assertStringContainsString("'mediatype' => \$urlparams['mediatype']", $source);
        $this->assertStringContainsString("'collection' => \$urlparams['collection']", $source);
        $this->assertStringContainsString("'tag' => \$urlparams['tag']", $source);
        $this->assertStringContainsString("'source' => \$urlparams['source']", $source);
        $this->assertStringContainsString("'advisory' => \$urlparams['advisory']", $source);
        $this->assertStringContainsString("'cultural' => \$urlparams['cultural']", $source);
        $this->assertStringContainsString("'audience' => \$urlparams['audience']", $source);
        $this->assertStringContainsString("'lang' => \$urlparams['lang']", $source);
        $this->assertStringContainsString("'validation' => \$urlparams['validation']", $source);
        $this->assertStringContainsString("'item' => \$urlparams['item']", $source);

        $this->assertStringContainsString('$page = max(1, $urlparams[\'page\']);', $source);
        $this->assertStringContainsString('$perpage = min(48, max(1, $urlparams[\'perpage\']));', $source);
        $this->assertStringContainsString("'page' => \$page", $source);
        $this->assertStringContainsString("'perpage' => \$perpage", $source);
        $this->assertStringContainsString("'sort' => \$urlparams['sort']", $source);
        $this->assertStringContainsString("'sitewide' => \$cmid <= 0 && \$archiveid <= 0", $source);
    }

    /**
     * The public controller must route item requests to the public detail DTO service.
     *
     * @return void
     */
    public function test_mediatheque_controller_routes_item_requests_to_public_detail_service(): void {
        global $CFG;

        $path = $CFG->dirroot . '/local/uckk/mediatheque.php';

        $this->assertFileExists($path);

        $source = file_get_contents($path);
        $this->assertIsString($source);

        $this->assertStringContainsString("optional_param('item', '', PARAM_ALPHANUMEXT)", $source);
        $this->assertStringContainsString("\$isitemrequest = \$urlparams['item'] !== '';", $source);
        $this->assertStringContainsString('\\mod_uckkarchive\\local\\public_mediatheque_service', $source);
        $this->assertStringContainsString("method_exists(\$service, 'get_item')", $source);
        $this->assertStringContainsString('$service->get_item($detailrequest', $source);
        $this->assertStringContainsString("'uuid' => \$urlparams['item']", $source);
        $this->assertStringContainsString("'type' => \$detailtype", $source);

        $this->assertStringContainsString("'has_mediatheque_item'", $source);
        $this->assertStringContainsString("'mediatheque_item'", $source);
        $this->assertStringContainsString("'has_mediatheque_item_error'", $source);
        $this->assertStringContainsString("'mediatheque_item_error'", $source);
        $this->assertStringContainsString("'mediatheque_item_back_url'", $source);
        $this->assertStringContainsString("'has_mediatheque_explorer'", $source);
        $this->assertStringContainsString('!$isitemrequest', $source);

        // The detail route must stay thin and delegate media ownership to mod_uckkarchive.
        $this->assertStringNotContainsString('uckkarchive_media', $source);
        $this->assertStringNotContainsString('$DB->', $source);
        $this->assertStringNotContainsString('get_records_sql', $source);
        $this->assertStringNotContainsString('require_capability(', $source);
    }

    /**
     * The public page registry must expose a dedicated Médiathèque definition.
     *
     * @return void
     */
    public function test_public_pages_registers_mediatheque_definition(): void {
        $definition = public_pages::definition('mediatheque');

        $this->assertSame('mediatheque', $definition['slug']);
        $this->assertSame('Médiathèque UCKK', $definition['title']);
        $this->assertSame('Archives publiques', $definition['eyebrow']);

        $this->assertArrayHasKey('summary', $definition);
        $this->assertStringContainsString('médias', $definition['summary']);
        $this->assertStringContainsString('collections', $definition['summary']);

        $this->assertArrayHasKey('layout', $definition);
        $this->assertSame('wide', $definition['layout']);

        $this->assertArrayHasKey('navigation', $definition);
        $this->assertIsArray($definition['navigation']);

        $this->assertArrayHasKey('notices', $definition);
        $this->assertIsArray($definition['notices']);

        $this->assertArrayHasKey('metadata', $definition);
        $this->assertIsArray($definition['metadata']);
    }

    /**
     * The public navigation must include the Médiathèque route exactly once.
     *
     * @return void
     */
    public function test_public_navigation_contains_mediatheque_link(): void {
        $definition = public_pages::definition('mediatheque');

        $this->assertArrayHasKey('navigation', $definition);
        $this->assertIsArray($definition['navigation']);

        $matches = array_values(array_filter(
            $definition['navigation'],
            static function ($item): bool {
                $item = (array)$item;

                return ($item['key'] ?? '') === 'mediatheque';
            }
        ));

        $this->assertCount(1, $matches);

        $item = (array)$matches[0];

        $this->assertSame('mediatheque', $item['key']);
        $this->assertSame('Médiathèque', $item['label']);
        $this->assertSame('/local/uckk/mediatheque.php', $item['url']);
    }

    /**
     * setup_page() must configure the public Médiathèque URL and shell.
     *
     * @return void
     */
    public function test_setup_page_configures_mediatheque_public_page(): void {
        global $PAGE;

        $context = context_system::instance();

        public_pages::setup_page('mediatheque', $context);

        $this->assertEquals($context, $PAGE->context);
        $this->assertSame('/local/uckk/mediatheque.php', parse_url($PAGE->url->out(false), PHP_URL_PATH));
        $this->assertSame('local_uckk_public', $PAGE->pagelayout);

        $this->assertStringContainsString('Médiathèque', $PAGE->title);
    }

    /**
     * The renderable must export the optional Médiathèque explorer contract.
     *
     * @return void
     */
    public function test_public_page_renderable_exports_mediatheque_explorer_context(): void {
        global $PAGE;

        $rootid = 'local-uckk-mediatheque-explorer-test';

        $initialstate = [
            'rootId' => $rootid,
            'service' => 'mod_uckkarchive_search_mediatheque',
            'cmid' => 0,
            'archiveid' => 0,
            'query' => '',
            'filters' => [
                'type' => 'all',
                'mediatype' => 'all',
                'collection' => '',
                'tag' => '',
                'source' => '',
                'advisory' => 'all',
                'cultural' => 'all',
                'audience' => 'all',
                'lang' => '',
                'validation' => 'all',
                'item' => '',
            ],
            'page' => 1,
            'perpage' => 12,
            'sort' => 'relevance',
            'sitewide' => true,
        ];

        $page = new public_page('mediatheque', [
            'has_mediatheque_explorer' => true,
            'mediatheque_explorer_id' => $rootid,
            'mediatheque_initial_state' => $initialstate,
        ]);

        $data = $page->export_for_template($PAGE->get_renderer('core'));

        $this->assertSame('mediatheque', $data->slug);
        $this->assertTrue((bool)$data->has_mediatheque_explorer);
        $this->assertSame($rootid, $data->mediatheque_explorer_id);
        $this->assertSame($initialstate, $data->mediatheque_initial_state);

        $this->assertIsString($data->mediatheque_initial_state_json);
        $this->assertJson($data->mediatheque_initial_state_json);

        $decoded = json_decode($data->mediatheque_initial_state_json, true);

        $this->assertIsArray($decoded);
        $this->assertSame($rootid, $decoded['rootId']);
        $this->assertSame('mod_uckkarchive_search_mediatheque', $decoded['service']);
        $this->assertSame(0, $decoded['cmid']);
        $this->assertSame(0, $decoded['archiveid']);
        $this->assertSame('', $decoded['query']);
        $this->assertSame('all', $decoded['filters']['type']);
        $this->assertSame('all', $decoded['filters']['mediatype']);
        $this->assertSame('', $decoded['filters']['collection']);
        $this->assertSame('', $decoded['filters']['tag']);
        $this->assertSame('', $decoded['filters']['source']);
        $this->assertSame('all', $decoded['filters']['advisory']);
        $this->assertSame('all', $decoded['filters']['cultural']);
        $this->assertSame('all', $decoded['filters']['audience']);
        $this->assertSame('', $decoded['filters']['lang']);
        $this->assertSame('all', $decoded['filters']['validation']);
        $this->assertSame('', $decoded['filters']['item']);
        $this->assertSame(1, $decoded['page']);
        $this->assertSame(12, $decoded['perpage']);
        $this->assertSame('relevance', $decoded['sort']);
        $this->assertTrue((bool)$decoded['sitewide']);
    }

    /**
     * The renderable must export the optional Médiathèque item detail contract.
     *
     * @return void
     */
    public function test_public_page_renderable_exports_mediatheque_item_context(): void {
        global $PAGE;

        $item = [
            'uuid' => '622fb3c1-0958-424f-bf30-a7ea71c9f90e',
            'objecttype' => 'media',
            'title' => 'Média public de test',
            'subtitle' => '',
            'summary' => 'Résumé public de test.',
            'mediatype' => 'video',
            'mimetype' => '',
            'language' => 'fr',
            'thumbnailurl' => '',
            'detailurl' => '/local/uckk/mediatheque.php?item=622fb3c1-0958-424f-bf30-a7ea71c9f90e&type=media',
            'source' => [
                'value' => 'external_reference_only',
                'label' => 'Référence externe',
            ],
            'rights' => [
                'license' => '',
                'rightsstatement' => '',
                'copyallowed' => false,
            ],
            'status' => [
                'value' => 'active',
                'label' => 'active',
            ],
            'visibility' => [
                'value' => 'public',
                'label' => 'public',
            ],
            'validation' => [
                'value' => '',
                'label' => '',
            ],
            'badges' => [],
            'advisories' => [
                'haspublicadvisory' => false,
                'summary' => '',
            ],
            'culturalprotocol' => [
                'haspublicprotocol' => false,
                'summary' => '',
            ],
            'relations' => [
                'collectioncount' => 0,
                'markercount' => 0,
                'externalworkcount' => 0,
            ],
            'actions' => [
                'canviewdetail' => true,
                'canviewfile' => false,
                'candownload' => false,
                'canexport' => false,
            ],
        ];

        $page = new public_page('mediatheque', [
            'has_mediatheque_item' => true,
            'mediatheque_item' => $item,
            'mediatheque_item_payload' => [
                'item' => $item,
            ],
            'has_mediatheque_item_error' => false,
            'mediatheque_item_error' => '',
            'mediatheque_item_back_url' => '/local/uckk/mediatheque.php',
            'mediatheque_item_requested_uuid' => '622fb3c1-0958-424f-bf30-a7ea71c9f90e',
            'mediatheque_item_requested_type' => 'media',
            'has_mediatheque_explorer' => false,
        ]);

        $data = $page->export_for_template($PAGE->get_renderer('core'));

        $this->assertSame('mediatheque', $data->slug);
        $this->assertTrue((bool)$data->has_mediatheque_item);
        $this->assertFalse((bool)$data->has_mediatheque_item_error);
        $this->assertFalse((bool)$data->has_mediatheque_explorer);
        $this->assertSame($item, $data->mediatheque_item);
        $this->assertSame('Média public de test', $data->mediatheque_item['title']);
        $this->assertSame('/local/uckk/mediatheque.php', $data->mediatheque_item_back_url);
        $this->assertSame('622fb3c1-0958-424f-bf30-a7ea71c9f90e', $data->mediatheque_item_requested_uuid);
        $this->assertSame('media', $data->mediatheque_item_requested_type);
    }

    /**
     * The renderable must keep ordinary public pages free of the Médiathèque explorer.
     *
     * @return void
     */
    public function test_public_page_renderable_does_not_export_explorer_by_default(): void {
        global $PAGE;

        $page = new public_page('home');
        $data = $page->export_for_template($PAGE->get_renderer('core'));

        $this->assertSame('home', $data->slug);
        $this->assertFalse((bool)$data->has_mediatheque_explorer);
        $this->assertIsString($data->mediatheque_explorer_id);
        $this->assertSame([], $data->mediatheque_initial_state);
        $this->assertSame('[]', $data->mediatheque_initial_state_json);

        $this->assertFalse((bool)$data->has_mediatheque_item);
        $this->assertSame([], $data->mediatheque_item);
        $this->assertSame([], $data->mediatheque_item_payload);
        $this->assertFalse((bool)$data->has_mediatheque_item_error);
        $this->assertSame('', $data->mediatheque_item_error);
        $this->assertSame('/local/uckk/mediatheque.php', $data->mediatheque_item_back_url);
        $this->assertSame('', $data->mediatheque_item_requested_uuid);
        $this->assertSame('media', $data->mediatheque_item_requested_type);
    }

    /**
     * The public page shell must mount the item detail partial before the explorer.
     *
     * @return void
     */
    public function test_public_page_template_mounts_mediatheque_item_before_explorer(): void {
        global $CFG;

        $path = $CFG->dirroot . '/local/uckk/templates/public_page.mustache';

        $this->assertFileExists($path);

        $template = file_get_contents($path);
        $this->assertIsString($template);

        $itempos = strpos($template, '{{> local_uckk/pages/mediatheque_item }}');
        $explorerpos = strpos($template, '{{> local_uckk/pages/mediatheque_explorer }}');

        $this->assertNotFalse($itempos);
        $this->assertNotFalse($explorerpos);
        $this->assertLessThan($explorerpos, $itempos);
        $this->assertStringContainsString('{{#has_mediatheque_item}}', $template);
        $this->assertStringContainsString('{{#has_mediatheque_item_error}}', $template);
        $this->assertStringContainsString('{{^has_mediatheque_item}}', $template);
    }

    /**
     * The public page must include the dedicated Médiathèque item partial file.
     *
     * @return void
     */
    public function test_mediatheque_item_partial_exists(): void {
        global $CFG;

        $path = $CFG->dirroot . '/local/uckk/templates/pages/mediatheque_item.mustache';

        $this->assertFileExists($path);

        $template = file_get_contents($path);
        $this->assertIsString($template);

        $this->assertStringContainsString('@template local_uckk/pages/mediatheque_item', $template);
        $this->assertStringContainsString('mediatheque_item', $template);
        $this->assertStringContainsString('mediatheque_item_back_url', $template);
        $this->assertStringContainsString('Retour à la recherche', $template);
    }

    /**
     * The page contract must keep media ownership outside local_uckk.
     *
     * @return void
     */
    public function test_mediatheque_definition_declares_archive_owned_data_boundary(): void {
        $definition = public_pages::definition('mediatheque');

        $this->assertArrayHasKey('notices', $definition);
        $this->assertIsArray($definition['notices']);

        $noticebodies = implode("\n", array_map(
            static function ($notice): string {
                $notice = (array)$notice;

                return (string)($notice['body'] ?? '');
            },
            $definition['notices']
        ));

        $this->assertStringContainsString('mod_uckkarchive', $noticebodies);
        $this->assertStringContainsString('droits', $noticebodies);
        $this->assertStringContainsString('protocoles culturels', $noticebodies);

        $this->assertArrayHasKey('metadata', $definition);
        $this->assertIsArray($definition['metadata']);

        $metadata = [];
        foreach ($definition['metadata'] as $entry) {
            $entry = (array)$entry;
            $metadata[(string)($entry['label'] ?? '')] = (string)($entry['value'] ?? '');
        }

        $this->assertSame('local_uckk', $metadata['Surface publique'] ?? '');
        $this->assertSame('mod_uckkarchive', $metadata['Données et politiques'] ?? '');
    }

    /**
     * The page definition must not move media ownership into local_uckk.
     *
     * @return void
     */
    public function test_mediatheque_definition_does_not_claim_media_runtime_ownership(): void {
        $definition = public_pages::definition('mediatheque');

        $encoded = json_encode($definition, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $this->assertIsString($encoded);
        $this->assertStringContainsString('mod_uckkarchive', $encoded);
        $this->assertStringNotContainsString('uckkarchive_media', $encoded);
        $this->assertStringNotContainsString('get_records_sql', $encoded);
        $this->assertStringNotContainsString('require_capability', $encoded);
    }
}
