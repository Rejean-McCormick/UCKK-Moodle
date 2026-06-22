<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

/**
 * PHPUnit tests for the UCKK Faculty dynamic block provider layer.
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
use local_uckk\local\faculty\dynamic\local_uckk_manual_provider;
use local_uckk\local\faculty\dynamic\moodle_calendar_provider;
use local_uckk\local\faculty\dynamic\moodle_category_provider;
use local_uckk\local\faculty\dynamic\moodle_forum_provider;
use local_uckk\local\faculty\dynamic\none_provider;
use local_uckk\local\faculty\dynamic\provider_interface;
use local_uckk\local\faculty\faculty_dynamic_block_provider;
use Throwable;

defined('MOODLE_INTERNAL') || die();

/**
 * Tests for local_uckk\local\faculty\faculty_dynamic_block_provider.
 *
 * Dynamic blocks are the only layer that may inject public Moodle/runtime data
 * into a Faculty page. They must filter private data, ignore hidden blocks,
 * honour limits and empty states, and never act as the Faculty Profile
 * repository, Atlas repository, page builder, renderer, or permission layer.
 *
 * @covers \local_uckk\local\faculty\faculty_dynamic_block_provider
 */
final class faculty_dynamic_block_provider_test extends advanced_testcase {
    /** Allowed dynamic block types from DOC_12. */
    private const ALLOWED_BLOCK_TYPES = [
        'announcements',
        'events',
        'moodle_course_list',
        'featured_courses',
        'faculty_news',
        'related_faculties',
        'public_resources',
        'cta_panel',
    ];

    /** Allowed source.provider values from DOC_12. */
    private const ALLOWED_SOURCE_PROVIDERS = [
        'moodle_forum',
        'moodle_calendar',
        'moodle_category',
        'moodle_course_customfield',
        'local_uckk_news',
        'local_uckk_manual',
        'none',
    ];

    /** Dynamic provider classes required by the canonical file list. */
    private const REQUIRED_PROVIDER_CLASSES = [
        'moodle_forum' => moodle_forum_provider::class,
        'moodle_calendar' => moodle_calendar_provider::class,
        'moodle_category' => moodle_category_provider::class,
        'local_uckk_manual' => local_uckk_manual_provider::class,
        'none' => none_provider::class,
    ];

    /** Providers documented in DOC_12 but backed by shared/manual providers in the file list. */
    private const PROVIDERS_WITHOUT_DEDICATED_CLASS = [
        'moodle_course_customfield',
        'local_uckk_news',
    ];

    /** Required dynamic block config keys. */
    private const REQUIRED_BLOCK_CONFIG_KEYS = [
        'id',
        'type',
        'title',
        'source',
        'limit',
        'visibility',
        'empty_state',
    ];

    /** Required source keys for provider routing. */
    private const REQUIRED_SOURCE_KEYS = [
        'provider',
    ];

    /** Output keys expected by the page builder / Mustache layer. */
    private const EXPECTED_OUTPUT_KEYS = [
        'id',
        'type',
        'title',
        'items',
        'has_items',
        'empty_state',
        'source_provider',
    ];

    /** Private data keys that must never be exposed by dynamic blocks. */
    private const FORBIDDEN_PRIVATE_OUTPUT_KEYS = [
        'userid',
        'user_id',
        'useremail',
        'email',
        'firstname',
        'lastname',
        'fullname',
        'grade',
        'grades',
        'score',
        'completion',
        'completionstate',
        'progress',
        'lastaccess',
        'timemodifiedby',
        'createdby',
        'modifiedby',
        'ip',
        'token',
        'sesskey',
        'secret',
        'password',
        'capabilities',
        'roles',
        'enrolments',
    ];

    /** Keys that belong to other layers, not dynamic block output. */
    private const FORBIDDEN_LAYER_LEAK_KEYS = [
        'schema_version',
        'faculty_id',
        'voie_id',
        'slug',
        'source_atlas',
        'moodle',
        'identity',
        'seo',
        'hero',
        'navigation',
        'sections',
        'atlas_projection',
        'featured_blocks',
        'faq',
        'contact',
        'governance',
        'cache',
        'cours_conceptuels',
        'concept_maitre',
        'concepts_associes',
        'criteres_passage',
        'html',
        'rendered',
    ];

    /**
     * The orchestrator class and provider interface must exist.
     */
    public function test_dynamic_block_provider_layer_exposes_required_classes(): void {
        $this->assertTrue(
            class_exists(faculty_dynamic_block_provider::class),
            faculty_dynamic_block_provider::class . ' must exist.'
        );

        $this->assertTrue(
            interface_exists(provider_interface::class),
            provider_interface::class . ' must exist.'
        );
    }

    /**
     * Concrete provider classes required by the canonical file list must exist
     * and implement provider_interface.
     */
    public function test_concrete_dynamic_provider_classes_implement_interface(): void {
        foreach (self::REQUIRED_PROVIDER_CLASSES as $providername => $classname) {
            $this->assertTrue(
                class_exists($classname),
                'Missing provider class for ' . $providername . ': ' . $classname
            );

            $this->assertTrue(
                is_subclass_of($classname, provider_interface::class),
                $classname . ' must implement ' . provider_interface::class
            );
        }
    }

    /**
     * The dynamic block config shape used by Faculty Profiles must stay stable.
     */
    public function test_dynamic_block_fixture_uses_documented_shape(): void {
        $block = self::manual_block();

        $this->assertSameCanonicalizing(self::REQUIRED_BLOCK_CONFIG_KEYS, array_keys($block));

        foreach (self::REQUIRED_SOURCE_KEYS as $key) {
            $this->assertArrayHasKey($key, $block['source']);
        }

        $this->assertContains($block['type'], self::ALLOWED_BLOCK_TYPES);
        $this->assertContains($block['source']['provider'], self::ALLOWED_SOURCE_PROVIDERS);
        $this->assertSame('public', $block['visibility']);
        $this->assertIsInt($block['limit']);
    }

    /**
     * The provider layer must process a public manual block into a render-safe
     * dynamic block output.
     */
    public function test_resolves_public_manual_block_to_render_safe_output(): void {
        $provider = self::provider();
        $profile = self::faculty_profile_with_blocks([
            self::manual_block(),
        ]);

        $resolved = self::resolve_blocks($provider, $profile);

        $this->assertIsArray($resolved);
        $this->assertCount(1, $resolved);

        $block = self::first_block($resolved);

        $this->assert_dynamic_block_output_shape($block);
        $this->assertSame('resources', $block['id']);
        $this->assertSame('public_resources', $block['type']);
        $this->assertSame('Ressources publiques', $block['title']);
        $this->assertSame('local_uckk_manual', $block['source_provider']);
        $this->assertTrue($block['has_items']);
        $this->assertCount(2, $block['items']);

        foreach ($block['items'] as $item) {
            $this->assert_public_item_shape($item);
        }
    }

    /**
     * Hidden dynamic blocks must not be exposed on public Faculty pages.
     */
    public function test_filters_hidden_dynamic_blocks(): void {
        $provider = self::provider();

        $hidden = self::manual_block();
        $hidden['id'] = 'hidden-resources';
        $hidden['visibility'] = 'hidden';

        $profile = self::faculty_profile_with_blocks([
            self::manual_block(),
            $hidden,
        ]);

        $resolved = self::resolve_blocks($provider, $profile);

        $this->assertCount(1, $resolved);

        $block = self::first_block($resolved);
        $this->assertSame('resources', $block['id']);
    }

    /**
     * Restricted dynamic blocks must not be exposed in the anonymous/public
     * rendering context.
     */
    public function test_filters_restricted_dynamic_blocks_for_public_context(): void {
        $provider = self::provider();

        $restricted = self::manual_block();
        $restricted['id'] = 'restricted-resources';
        $restricted['visibility'] = 'restricted';

        $profile = self::faculty_profile_with_blocks([
            $restricted,
        ]);

        $resolved = self::resolve_blocks($provider, $profile);

        $this->assertSame([], array_values($resolved));
    }

    /**
     * The provider layer must enforce block limits after provider resolution.
     */
    public function test_applies_dynamic_block_limit(): void {
        $provider = self::provider();

        $block = self::manual_block();
        $block['limit'] = 1;

        $profile = self::faculty_profile_with_blocks([
            $block,
        ]);

        $resolved = self::resolve_blocks($provider, $profile);

        $block = self::first_block($resolved);

        $this->assertCount(1, $block['items']);
        $this->assertSame('Ressource publique 1', $block['items'][0]['title']);
    }

    /**
     * Empty public blocks must render safely with empty_state instead of leaking
     * errors or disappearing unexpectedly.
     */
    public function test_empty_public_block_renders_empty_state_safely(): void {
        $provider = self::provider();

        $block = self::manual_block();
        $block['source']['items'] = [];

        $profile = self::faculty_profile_with_blocks([
            $block,
        ]);

        $resolved = self::resolve_blocks($provider, $profile);

        $block = self::first_block($resolved);

        $this->assert_dynamic_block_output_shape($block);
        $this->assertSame([], $block['items']);
        $this->assertFalse($block['has_items']);
        $this->assertSame('Aucune ressource publique pour le moment.', $block['empty_state']);
    }

    /**
     * Unknown providers must fail closed.
     */
    public function test_rejects_unknown_source_provider(): void {
        $provider = self::provider();

        $block = self::manual_block();
        $block['source']['provider'] = 'invented_provider';

        $profile = self::faculty_profile_with_blocks([
            $block,
        ]);

        $this->assert_resolve_blocks_fails($provider, $profile, 'Unknown provider must be rejected.');
    }

    /**
     * Unknown dynamic block types must fail closed.
     */
    public function test_rejects_unknown_dynamic_block_type(): void {
        $provider = self::provider();

        $block = self::manual_block();
        $block['type'] = 'invented_block_type';

        $profile = self::faculty_profile_with_blocks([
            $block,
        ]);

        $this->assert_resolve_blocks_fails($provider, $profile, 'Unknown dynamic block type must be rejected.');
    }

    /**
     * Missing required block config keys must fail closed.
     */
    public function test_rejects_missing_required_dynamic_block_fields(): void {
        foreach (self::REQUIRED_BLOCK_CONFIG_KEYS as $field) {
            $provider = self::provider();

            $block = self::manual_block();
            unset($block[$field]);

            $profile = self::faculty_profile_with_blocks([
                $block,
            ]);

            $this->assert_resolve_blocks_fails(
                $provider,
                $profile,
                'Missing dynamic block field must be rejected: ' . $field
            );
        }
    }

    /**
     * Missing source.provider must fail closed.
     */
    public function test_rejects_missing_source_provider(): void {
        $provider = self::provider();

        $block = self::manual_block();
        unset($block['source']['provider']);

        $profile = self::faculty_profile_with_blocks([
            $block,
        ]);

        $this->assert_resolve_blocks_fails($provider, $profile, 'Missing source.provider must be rejected.');
    }

    /**
     * Provider names documented in DOC_12 must be either backed by a dedicated
     * provider class or explicitly routed by the orchestrator.
     */
    public function test_all_documented_source_providers_are_supported_or_explicitly_routed(): void {
        $provider = self::provider();

        foreach (self::ALLOWED_SOURCE_PROVIDERS as $providername) {
            $block = self::manual_block();
            $block['id'] = 'block-' . str_replace('_', '-', $providername);
            $block['type'] = self::block_type_for_provider($providername);
            $block['source']['provider'] = $providername;

            if ($providername === 'none') {
                unset($block['source']['items']);
            }

            $profile = self::faculty_profile_with_blocks([
                $block,
            ]);

            try {
                $resolved = self::resolve_blocks($provider, $profile);
            } catch (Throwable $exception) {
                $this->fail('Documented provider was not supported: ' . $providername . '. ' . $exception->getMessage());
            }

            $this->assertIsArray($resolved);
        }
    }

    /**
     * Dynamic block output must not expose private user, enrolment, completion,
     * grade, session, or capability data.
     */
    public function test_filters_private_data_from_dynamic_block_items(): void {
        $provider = self::provider();

        $block = self::manual_block();
        $block['source']['items'][0] = array_merge($block['source']['items'][0], [
            'userid' => 42,
            'user_id' => 42,
            'email' => 'private@example.test',
            'fullname' => 'Private User',
            'grade' => 99,
            'completion' => true,
            'progress' => 75,
            'lastaccess' => time(),
            'roles' => ['student'],
            'capabilities' => ['moodle/course:view'],
            'sesskey' => 'private-sesskey',
            'token' => 'private-token',
        ]);

        $profile = self::faculty_profile_with_blocks([
            $block,
        ]);

        $resolved = self::resolve_blocks($provider, $profile);
        $block = self::first_block($resolved);

        foreach ($block['items'] as $item) {
            foreach (self::FORBIDDEN_PRIVATE_OUTPUT_KEYS as $key) {
                $this->assertArrayNotHasKey($key, $item, 'Private key leaked from dynamic item: ' . $key);
            }
        }
    }

    /**
     * Dynamic block output must not leak whole Faculty Profile, Atlas, or
     * rendered-page data structures.
     */
    public function test_dynamic_block_output_does_not_leak_other_layer_payloads(): void {
        $provider = self::provider();
        $profile = self::faculty_profile_with_blocks([
            self::manual_block(),
        ]);

        $resolved = self::resolve_blocks($provider, $profile);
        $block = self::first_block($resolved);

        foreach (self::FORBIDDEN_LAYER_LEAK_KEYS as $key) {
            $this->assertArrayNotHasKey($key, $block, 'Dynamic block leaked other-layer key: ' . $key);
        }

        foreach ($block['items'] as $item) {
            foreach (self::FORBIDDEN_LAYER_LEAK_KEYS as $key) {
                $this->assertArrayNotHasKey($key, $item, 'Dynamic block item leaked other-layer key: ' . $key);
            }
        }
    }

    /**
     * The none provider must produce a safe empty block.
     */
    public function test_none_provider_returns_safe_empty_block(): void {
        $provider = self::provider();

        $block = self::manual_block();
        $block['id'] = 'empty';
        $block['type'] = 'cta_panel';
        $block['title'] = 'Bloc vide';
        $block['source'] = [
            'provider' => 'none',
        ];
        $block['empty_state'] = 'Aucun contenu public pour le moment.';

        $profile = self::faculty_profile_with_blocks([
            $block,
        ]);

        $resolved = self::resolve_blocks($provider, $profile);
        $block = self::first_block($resolved);

        $this->assert_dynamic_block_output_shape($block);
        $this->assertSame('none', $block['source_provider']);
        $this->assertSame([], $block['items']);
        $this->assertFalse($block['has_items']);
        $this->assertSame('Aucun contenu public pour le moment.', $block['empty_state']);
    }

    /**
     * All shipped Faculty Profile dynamic_blocks must use documented providers
     * and block types.
     */
    public function test_all_shipped_faculty_profiles_use_documented_dynamic_block_contract(): void {
        foreach (self::faculty_profiles() as $profile) {
            $this->assertArrayHasKey('dynamic_blocks', $profile);
            $this->assertIsArray($profile['dynamic_blocks']);

            foreach ($profile['dynamic_blocks'] as $block) {
                $this->assertIsArray($block);

                foreach (self::REQUIRED_BLOCK_CONFIG_KEYS as $key) {
                    $this->assertArrayHasKey($key, $block, 'Missing dynamic block key: ' . $key);
                }

                $this->assertContains($block['type'], self::ALLOWED_BLOCK_TYPES);
                $this->assertIsArray($block['source']);
                $this->assertArrayHasKey('provider', $block['source']);
                $this->assertContains($block['source']['provider'], self::ALLOWED_SOURCE_PROVIDERS);
                $this->assertContains($block['visibility'], ['public', 'hidden', 'restricted']);
                $this->assertIsInt($block['limit']);
                $this->assertGreaterThanOrEqual(0, $block['limit']);
                $this->assertIsString($block['empty_state']);
            }
        }
    }

    /**
     * The dynamic block layer must be able to resolve every shipped public
     * Faculty Profile without throwing, even when Moodle runtime data is empty.
     */
    public function test_resolves_all_shipped_public_faculty_profile_dynamic_blocks_safely(): void {
        $provider = self::provider();

        foreach (self::faculty_profiles() as $profile) {
            if (($profile['status'] ?? null) !== 'published' || ($profile['visibility'] ?? null) !== 'public') {
                continue;
            }

            try {
                $resolved = self::resolve_blocks($provider, $profile);
            } catch (Throwable $exception) {
                $this->fail(
                    'Public dynamic blocks failed for ' . ($profile['slug'] ?? '[unknown]') . ': ' .
                    $exception->getMessage()
                );
            }

            $this->assertIsArray($resolved);

            foreach ($resolved as $block) {
                $this->assertIsArray($block);
                $this->assert_dynamic_block_output_shape($block);
            }
        }
    }

    /**
     * Assert dynamic block output shape.
     *
     * @param array<string, mixed> $block Resolved block.
     */
    private function assert_dynamic_block_output_shape(array $block): void {
        foreach (self::EXPECTED_OUTPUT_KEYS as $key) {
            $this->assertArrayHasKey($key, $block, 'Missing dynamic block output key: ' . $key);
        }

        $this->assertIsString($block['id']);
        $this->assertContains($block['type'], self::ALLOWED_BLOCK_TYPES);
        $this->assertIsString($block['title']);
        $this->assertIsArray($block['items']);
        $this->assertIsBool($block['has_items']);
        $this->assertIsString($block['empty_state']);
        $this->assertContains($block['source_provider'], self::ALLOWED_SOURCE_PROVIDERS);

        $this->assertSame(count($block['items']) > 0, $block['has_items']);
    }

    /**
     * Assert public item output shape.
     *
     * @param array<string, mixed> $item Dynamic item.
     */
    private function assert_public_item_shape(array $item): void {
        $this->assertArrayHasKey('title', $item);
        $this->assertIsString($item['title']);
        $this->assertNotSame('', trim($item['title']));

        if (array_key_exists('summary', $item)) {
            $this->assertIsString($item['summary']);
        }

        if (array_key_exists('url', $item)) {
            $this->assertIsString($item['url']);
        }

        if (array_key_exists('date', $item)) {
            $this->assertIsString($item['date']);
        }

        foreach (self::FORBIDDEN_PRIVATE_OUTPUT_KEYS as $key) {
            $this->assertArrayNotHasKey($key, $item, 'Private key leaked from public item: ' . $key);
        }
    }

    /**
     * Assert that resolving blocks fails closed.
     *
     * @param faculty_dynamic_block_provider $provider Provider.
     * @param array<string, mixed> $profile Faculty Profile.
     * @param string $message Assertion message.
     */
    private function assert_resolve_blocks_fails(
        faculty_dynamic_block_provider $provider,
        array $profile,
        string $message
    ): void {
        try {
            self::resolve_blocks($provider, $profile);
            $this->fail($message);
        } catch (Throwable $exception) {
            $this->assertNotSame('', $exception->getMessage());
        }
    }

    /**
     * Instantiate the orchestrator under test.
     *
     * @return faculty_dynamic_block_provider
     */
    private static function provider(): faculty_dynamic_block_provider {
        return new faculty_dynamic_block_provider();
    }

    /**
     * Resolve dynamic blocks through the orchestrator.
     *
     * This helper allows the implementation to expose either of the expected
     * public method names while the test still enforces the same contract.
     *
     * @param faculty_dynamic_block_provider $provider Provider.
     * @param array<string, mixed> $profile Faculty Profile.
     * @return array<int, array<string, mixed>>
     */
    private static function resolve_blocks(faculty_dynamic_block_provider $provider, array $profile): array {
        foreach (['resolve_blocks', 'get_blocks', 'build_blocks', 'provide_blocks'] as $method) {
            if (method_exists($provider, $method)) {
                $result = $provider->{$method}($profile);
                self::assertIsArray($result);

                return self::normalize_block_list($result);
            }
        }

        self::fail(
            faculty_dynamic_block_provider::class .
            ' must expose resolve_blocks(), get_blocks(), build_blocks(), or provide_blocks().'
        );
    }

    /**
     * Normalize possible block list wrappers.
     *
     * @param array<mixed> $blocks Blocks.
     * @return array<int, array<string, mixed>>
     */
    private static function normalize_block_list(array $blocks): array {
        if (array_key_exists('dynamic_blocks', $blocks) && is_array($blocks['dynamic_blocks'])) {
            $blocks = $blocks['dynamic_blocks'];
        }

        if (array_key_exists('items', $blocks) && is_array($blocks['items'])) {
            $blocks = $blocks['items'];
        }

        $normalized = [];

        foreach ($blocks as $block) {
            self::assertIsArray($block);
            $normalized[] = $block;
        }

        return $normalized;
    }

    /**
     * Return the first block from a resolved block list.
     *
     * @param array<int, array<string, mixed>> $blocks Blocks.
     * @return array<string, mixed>
     */
    private static function first_block(array $blocks): array {
        self::assertNotEmpty($blocks);

        $first = array_values($blocks)[0];
        self::assertIsArray($first);

        return $first;
    }

    /**
     * Return the closest documented block type for a provider.
     *
     * @param string $providername Provider name.
     * @return string
     */
    private static function block_type_for_provider(string $providername): string {
        return match ($providername) {
            'moodle_forum' => 'announcements',
            'moodle_calendar' => 'events',
            'moodle_category', 'moodle_course_customfield' => 'moodle_course_list',
            'local_uckk_news' => 'faculty_news',
            'local_uckk_manual' => 'public_resources',
            'none' => 'cta_panel',
            default => 'public_resources',
        };
    }

    /**
     * Build a minimal Faculty Profile containing dynamic blocks.
     *
     * @param array<int, array<string, mixed>> $blocks Dynamic blocks.
     * @return array<string, mixed>
     */
    private static function faculty_profile_with_blocks(array $blocks): array {
        return [
            'schema_version' => 'UCKK-FACULTY-0.1',
            'faculty_id' => 'faculty_grand_jeu_social',
            'voie_id' => 'voie_grand_jeu_social',
            'slug' => 'grand-jeu-social',
            'status' => 'published',
            'visibility' => 'public',
            'source_atlas' => [
                'file' => 'voie_grand_jeu_social.json',
                'schema_version_expected' => 'UCKK-ATLAS-0.2-draft',
                'sync_mode' => 'read_only',
            ],
            'moodle' => [
                'category_id' => null,
                'category_idnumber' => 'UCKK-GJS',
                'course_prefix' => 'GJS',
                'public_course_listing' => true,
                'enrolment_visibility' => 'login_required',
                'hub_course_idnumber' => 'GJS-HUB',
            ],
            'identity' => [],
            'seo' => [],
            'hero' => [],
            'navigation' => [],
            'sections' => [],
            'atlas_projection' => [],
            'dynamic_blocks' => $blocks,
            'featured_blocks' => [],
            'faq' => [],
            'contact' => [],
            'governance' => [],
            'cache' => [
                'enabled' => true,
                'ttl_seconds' => 3600,
            ],
        ];
    }

    /**
     * Build a valid manual public_resources dynamic block.
     *
     * @return array<string, mixed>
     */
    private static function manual_block(): array {
        return [
            'id' => 'resources',
            'type' => 'public_resources',
            'title' => 'Ressources publiques',
            'source' => [
                'provider' => 'local_uckk_manual',
                'items' => [
                    [
                        'title' => 'Ressource publique 1',
                        'summary' => 'Première ressource publique.',
                        'url' => 'https://example.test/resource-1',
                    ],
                    [
                        'title' => 'Ressource publique 2',
                        'summary' => 'Deuxième ressource publique.',
                        'url' => 'https://example.test/resource-2',
                    ],
                ],
            ],
            'limit' => 5,
            'visibility' => 'public',
            'empty_state' => 'Aucune ressource publique pour le moment.',
        ];
    }

    /**
     * Load all shipped Faculty Profiles.
     *
     * @return array<int, array<string, mixed>>
     */
    private static function faculty_profiles(): array {
        $manifest = self::read_json_file(self::faculty_dir() . DIRECTORY_SEPARATOR . 'faculty_manifest.json');

        self::assertArrayHasKey('items', $manifest);
        self::assertIsArray($manifest['items']);

        $profiles = [];

        foreach ($manifest['items'] as $item) {
            self::assertIsArray($item);
            self::assertArrayHasKey('faculty_file', $item);
            self::assertIsString($item['faculty_file']);

            $profiles[] = self::read_json_file(
                self::faculty_dir() . DIRECTORY_SEPARATOR . $item['faculty_file']
            );
        }

        self::assertCount(10, $profiles);

        return $profiles;
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
     * Read and decode a JSON file.
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
