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
 * Dynamic block provider dispatcher for public UCKK faculty pages.
 *
 * This class resolves the dynamic_blocks declared in a Faculty Profile JSON,
 * delegates live data retrieval to the documented provider classes, applies
 * public-output normalisation, and returns render-ready data for Mustache.
 *
 * It must remain read-only. It must not create courses, enrol users, expose
 * private progress, expose grades, or modify Moodle data.
 *
 * @package    local_uckk
 * @copyright  2026 Univers-Cité King Klown
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace local_uckk\local\faculty;

use cache;
use coding_exception;
use local_uckk\local\faculty\dynamic\local_uckk_manual_provider;
use local_uckk\local\faculty\dynamic\local_uckk_news_provider;
use local_uckk\local\faculty\dynamic\moodle_calendar_provider;
use local_uckk\local\faculty\dynamic\moodle_category_provider;
use local_uckk\local\faculty\dynamic\moodle_forum_provider;
use local_uckk\local\faculty\dynamic\none_provider;
use local_uckk\local\faculty\dynamic\provider_interface;
use Throwable;

defined('MOODLE_INTERNAL') || die();

/**
 * Dynamic block dispatcher.
 *
 * @package local_uckk
 */
final class faculty_dynamic_block_provider {
    /** Component name. */
    private const COMPONENT = 'local_uckk';

    /** Cache area declared in db/caches.php. */
    private const CACHE_AREA = 'faculty_dynamic_block';

    /** Default item limit when a block does not declare one. */
    private const DEFAULT_LIMIT = 5;

    /** Maximum public items per block. */
    private const MAX_LIMIT = 50;

    /** Provider: Moodle forum. */
    public const PROVIDER_MOODLE_FORUM = 'moodle_forum';

    /** Provider: Moodle calendar. */
    public const PROVIDER_MOODLE_CALENDAR = 'moodle_calendar';

    /** Provider: Moodle category. */
    public const PROVIDER_MOODLE_CATEGORY = 'moodle_category';

    /** Provider: Moodle course custom field. */
    public const PROVIDER_MOODLE_COURSE_CUSTOMFIELD = 'moodle_course_customfield';

    /** Provider: local_uckk news. */
    public const PROVIDER_LOCAL_UCKK_NEWS = 'local_uckk_news';

    /** Provider: local_uckk manual JSON-controlled content. */
    public const PROVIDER_LOCAL_UCKK_MANUAL = 'local_uckk_manual';

    /** Provider: no active source. */
    public const PROVIDER_NONE = 'none';

    /** Dynamic block type: announcements. */
    public const TYPE_ANNOUNCEMENTS = 'announcements';

    /** Dynamic block type: events. */
    public const TYPE_EVENTS = 'events';

    /** Dynamic block type: Moodle course list. */
    public const TYPE_MOODLE_COURSE_LIST = 'moodle_course_list';

    /** Dynamic block type: featured courses. */
    public const TYPE_FEATURED_COURSES = 'featured_courses';

    /** Dynamic block type: faculty news. */
    public const TYPE_FACULTY_NEWS = 'faculty_news';

    /** Dynamic block type: related faculties. */
    public const TYPE_RELATED_FACULTIES = 'related_faculties';

    /** Dynamic block type: public resources. */
    public const TYPE_PUBLIC_RESOURCES = 'public_resources';

    /** Dynamic block type: CTA panel. */
    public const TYPE_CTA_PANEL = 'cta_panel';

    /**
     * Allowed providers.
     *
     * @var string[]
     */
    private const ALLOWED_PROVIDERS = [
        self::PROVIDER_MOODLE_FORUM,
        self::PROVIDER_MOODLE_CALENDAR,
        self::PROVIDER_MOODLE_CATEGORY,
        self::PROVIDER_MOODLE_COURSE_CUSTOMFIELD,
        self::PROVIDER_LOCAL_UCKK_NEWS,
        self::PROVIDER_LOCAL_UCKK_MANUAL,
        self::PROVIDER_NONE,
    ];

    /**
     * Allowed dynamic block types.
     *
     * @var string[]
     */
    private const ALLOWED_TYPES = [
        self::TYPE_ANNOUNCEMENTS,
        self::TYPE_EVENTS,
        self::TYPE_MOODLE_COURSE_LIST,
        self::TYPE_FEATURED_COURSES,
        self::TYPE_FACULTY_NEWS,
        self::TYPE_RELATED_FACULTIES,
        self::TYPE_PUBLIC_RESOURCES,
        self::TYPE_CTA_PANEL,
    ];

    /**
     * Provider class map.
     *
     * The moodle_course_customfield provider is allowed by the contract. If its
     * concrete class is not present yet, blocks using it fail closed to an empty
     * public block.
     *
     * @var array<string, class-string>
     */
    private const PROVIDER_CLASSES = [
        self::PROVIDER_MOODLE_FORUM => moodle_forum_provider::class,
        self::PROVIDER_MOODLE_CALENDAR => moodle_calendar_provider::class,
        self::PROVIDER_MOODLE_CATEGORY => moodle_category_provider::class,
        self::PROVIDER_MOODLE_COURSE_CUSTOMFIELD =>
            '\\local_uckk\\local\\faculty\\dynamic\\moodle_course_customfield_provider',
        self::PROVIDER_LOCAL_UCKK_NEWS => local_uckk_news_provider::class,
        self::PROVIDER_LOCAL_UCKK_MANUAL => local_uckk_manual_provider::class,
        self::PROVIDER_NONE => none_provider::class,
    ];

    /**
     * Runtime provider instances.
     *
     * @var array<string, provider_interface>
     */
    private array $providers = [];

    /** Dynamic block cache, if available. */
    private ?cache $cache = null;

    /**
     * Constructor.
     *
     * @param array<string, provider_interface>|null $providers Optional provider overrides for tests.
     * @param cache|null $cache Optional cache override for tests.
     */
    public function __construct(?array $providers = null, ?cache $cache = null) {
        if ($providers !== null) {
            foreach ($providers as $name => $provider) {
                $providername = self::clean_provider_name((string)$name);

                if ($providername !== '' && $this->is_allowed_provider($providername)) {
                    $this->providers[$providername] = $provider;
                }
            }
        }

        $this->cache = $cache ?? self::make_cache();
    }

    /**
     * Resolve dynamic blocks from a full faculty profile.
     *
     * @param array<string, mixed> $faculty Faculty profile.
     * @param array<string, mixed> $pagecontext Optional page builder context.
     * @return array<int, array<string, mixed>>
     */
    public function get_blocks(array $faculty, array $pagecontext = []): array {
        $blocks = $faculty['dynamic_blocks'] ?? [];

        if (!is_array($blocks)) {
            return [];
        }

        return $this->resolve_blocks($blocks, $faculty, $pagecontext);
    }

    /**
     * Resolve a list of dynamic block definitions.
     *
     * @param array<int, mixed> $blocks Dynamic block definitions.
     * @param array<string, mixed> $faculty Faculty profile.
     * @param array<string, mixed> $pagecontext Optional page builder context.
     * @return array<int, array<string, mixed>>
     */
    public function resolve_blocks(array $blocks, array $faculty, array $pagecontext = []): array {
        $resolved = [];

        foreach ($blocks as $block) {
            if (!is_array($block)) {
                continue;
            }

            if (!self::is_public_visibility($block['visibility'] ?? 'public')) {
                continue;
            }

            $resolved[] = $this->resolve_block($block, $faculty, $pagecontext);
        }

        return $resolved;
    }

    /**
     * Resolve one dynamic block.
     *
     * @param array<string, mixed> $block Dynamic block definition from *.faculty.json.
     * @param array<string, mixed> $faculty Faculty profile.
     * @param array<string, mixed> $pagecontext Optional page builder context.
     * @return array<string, mixed>
     */
    public function resolve_block(array $block, array $faculty, array $pagecontext = []): array {
        $base = self::normalise_block_shell($block);
        $type = $base['type'];
        $providername = self::get_block_provider_name($block);

        if (!$this->is_allowed_type($type) || !$this->is_allowed_provider($providername)) {
            return $base;
        }

        if (!self::is_public_visibility($block['visibility'] ?? 'public')) {
            return $base;
        }

        $cachekey = $this->cache_key($block, $faculty, $pagecontext, $providername);

        if ($this->should_use_cache($faculty) && $this->cache !== null) {
            $cached = $this->cache->get($cachekey);

            if (is_array($cached)) {
                return $cached;
            }
        }

        $resolved = $this->resolve_uncached_block($block, $faculty, $pagecontext, $base, $providername);

        if ($this->should_use_cache($faculty) && $this->cache !== null) {
            $this->cache->set($cachekey, $resolved);
        }

        return $resolved;
    }

    /**
     * Return allowed provider names.
     *
     * @return string[]
     */
    public function allowed_providers(): array {
        return self::ALLOWED_PROVIDERS;
    }

    /**
     * Return allowed dynamic block types.
     *
     * @return string[]
     */
    public function allowed_types(): array {
        return self::ALLOWED_TYPES;
    }

    /**
     * Whether a provider is allowed by the contract.
     *
     * @param string $provider Provider name.
     * @return bool
     */
    public function is_allowed_provider(string $provider): bool {
        return in_array(self::clean_provider_name($provider), self::ALLOWED_PROVIDERS, true);
    }

    /**
     * Whether a dynamic block type is allowed by the contract.
     *
     * @param string $type Block type.
     * @return bool
     */
    public function is_allowed_type(string $type): bool {
        return in_array(self::clean_type_name($type), self::ALLOWED_TYPES, true);
    }

    /**
     * Resolve one block without using cache.
     *
     * @param array<string, mixed> $block Dynamic block definition.
     * @param array<string, mixed> $faculty Faculty profile.
     * @param array<string, mixed> $pagecontext Page builder context.
     * @param array<string, mixed> $base Normalised block shell.
     * @param string $providername Provider name.
     * @return array<string, mixed>
     */
    private function resolve_uncached_block(
        array $block,
        array $faculty,
        array $pagecontext,
        array $base,
        string $providername
    ): array {
        $provider = $this->provider_for($providername);

        if ($provider === null) {
            return $base;
        }

        try {
            $result = $provider->get_items($block, $faculty, $pagecontext);
        } catch (Throwable $e) {
            debugging(
                'local_uckk faculty dynamic block provider failed for provider ' . $providername,
                DEBUG_DEVELOPER
            );

            return $base;
        }

        $items = self::extract_items($result);
        $items = self::normalise_items($base['type'], $items, self::normalise_limit($block['limit'] ?? null));

        $base['items'] = $items;
        $base['has_items'] = !empty($items);

        return $base;
    }

    /**
     * Get a provider instance.
     *
     * @param string $providername Provider name.
     * @return provider_interface|null
     */
    private function provider_for(string $providername): ?provider_interface {
        $providername = self::clean_provider_name($providername);

        if (!$this->is_allowed_provider($providername)) {
            return null;
        }

        if (isset($this->providers[$providername])) {
            return $this->providers[$providername];
        }

        $classname = self::PROVIDER_CLASSES[$providername] ?? null;

        if ($classname === null || !class_exists($classname)) {
            return null;
        }

        $provider = new $classname();

        if (!$provider instanceof provider_interface) {
            return null;
        }

        $this->providers[$providername] = $provider;

        return $provider;
    }

    /**
     * Build the documented cache key.
     *
     * @param array<string, mixed> $block Dynamic block definition.
     * @param array<string, mixed> $faculty Faculty profile.
     * @param array<string, mixed> $pagecontext Page builder context.
     * @param string $providername Provider name.
     * @return string
     */
    private function cache_key(array $block, array $faculty, array $pagecontext, string $providername): string {
        $slug = self::clean_slug((string)($faculty['slug'] ?? ''));
        $blockid = self::clean_id((string)($block['id'] ?? ''));
        $hash = self::context_hash($block, $faculty, $pagecontext);

        if ($slug === '') {
            $slug = self::clean_id((string)($faculty['faculty_id'] ?? 'faculty'));
        }

        if ($blockid === '') {
            $blockid = hash('sha256', json_encode($block, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        }

        return 'dynamic_block:' . $slug . ':' . $blockid . ':' . $providername . ':' . $hash;
    }

    /**
     * Return the cache hash part.
     *
     * @param array<string, mixed> $block Dynamic block definition.
     * @param array<string, mixed> $faculty Faculty profile.
     * @param array<string, mixed> $pagecontext Page builder context.
     * @return string
     */
    private static function context_hash(array $block, array $faculty, array $pagecontext): string {
        foreach (['merged_page_hash', 'faculty_source_hash', 'atlas_source_hash'] as $key) {
            $value = (string)($pagecontext[$key] ?? $faculty[$key] ?? '');

            if ($value !== '') {
                return hash('sha256', $value);
            }
        }

        return hash(
            'sha256',
            json_encode(
                [
                    'faculty_id' => $faculty['faculty_id'] ?? '',
                    'slug' => $faculty['slug'] ?? '',
                    'block' => $block,
                ],
                JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
            )
        );
    }

    /**
     * Whether cache should be used for this faculty page.
     *
     * @param array<string, mixed> $faculty Faculty profile.
     * @return bool
     */
    private function should_use_cache(array $faculty): bool {
        $cacheconfig = $faculty['cache'] ?? [];

        if (!is_array($cacheconfig)) {
            return true;
        }

        return (bool)($cacheconfig['enabled'] ?? true);
    }

    /**
     * Make dynamic block cache.
     *
     * @return cache|null
     */
    private static function make_cache(): ?cache {
        try {
            return cache::make(self::COMPONENT, self::CACHE_AREA);
        } catch (coding_exception $e) {
            debugging('local_uckk faculty dynamic block cache is not configured.', DEBUG_DEVELOPER);
            return null;
        }
    }

    /**
     * Build the documented render shell for one dynamic block.
     *
     * @param array<string, mixed> $block Dynamic block definition.
     * @return array<string, mixed>
     */
    private static function normalise_block_shell(array $block): array {
        return [
            'id' => self::clean_id((string)($block['id'] ?? '')),
            'type' => self::clean_type_name((string)($block['type'] ?? '')),
            'title' => self::clean_text($block['title'] ?? ''),
            'items' => [],
            'has_items' => false,
            'empty_state' => self::clean_text($block['empty_state'] ?? ''),
            'visibility' => 'public',
        ];
    }

    /**
     * Extract provider name from a dynamic block definition.
     *
     * @param array<string, mixed> $block Dynamic block definition.
     * @return string
     */
    private static function get_block_provider_name(array $block): string {
        $source = $block['source'] ?? [];

        if (!is_array($source)) {
            return self::PROVIDER_NONE;
        }

        $provider = self::clean_provider_name((string)($source['provider'] ?? ''));

        return $provider !== '' ? $provider : self::PROVIDER_NONE;
    }

    /**
     * Extract item list from provider result.
     *
     * @param mixed $result Provider result.
     * @return array<int, mixed>
     */
    private static function extract_items($result): array {
        if (!is_array($result)) {
            return [];
        }

        if (array_key_exists('items', $result) && is_array($result['items'])) {
            return array_values($result['items']);
        }

        return array_values($result);
    }

    /**
     * Normalise public items for a dynamic block type.
     *
     * @param string $type Dynamic block type.
     * @param array<int, mixed> $items Raw items.
     * @param int $limit Item limit.
     * @return array<int, array<string, mixed>>
     */
    private static function normalise_items(string $type, array $items, int $limit): array {
        if ($limit <= 0) {
            return [];
        }

        $out = [];

        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }

            $normalised = self::normalise_item($type, $item);

            if (!empty(array_filter($normalised, static fn($value): bool => $value !== '' && $value !== null))) {
                $out[] = $normalised;
            }

            if (count($out) >= $limit) {
                break;
            }
        }

        return $out;
    }

    /**
     * Normalise one public item by block type.
     *
     * @param string $type Dynamic block type.
     * @param array<string, mixed> $item Raw item.
     * @return array<string, mixed>
     */
    private static function normalise_item(string $type, array $item): array {
        switch ($type) {
            case self::TYPE_EVENTS:
                return [
                    'title' => self::clean_text($item['title'] ?? ''),
                    'date_start' => self::clean_text($item['date_start'] ?? ''),
                    'date_end' => self::clean_text($item['date_end'] ?? ''),
                    'location' => self::clean_text($item['location'] ?? ''),
                    'url' => self::clean_url($item['url'] ?? ''),
                ];

            case self::TYPE_MOODLE_COURSE_LIST:
            case self::TYPE_FEATURED_COURSES:
                return [
                    'cours_id' => self::clean_id((string)($item['cours_id'] ?? '')),
                    'fullname' => self::clean_text($item['fullname'] ?? ''),
                    'summary' => self::clean_text($item['summary'] ?? ''),
                    'url' => self::clean_url($item['url'] ?? ''),
                    'availability_label' => self::clean_text($item['availability_label'] ?? ''),
                ];

            case self::TYPE_RELATED_FACULTIES:
                return [
                    'faculty_id' => self::clean_id((string)($item['faculty_id'] ?? '')),
                    'slug' => self::clean_slug((string)($item['slug'] ?? '')),
                    'name' => self::clean_text($item['name'] ?? ''),
                    'relation' => self::clean_text($item['relation'] ?? ''),
                    'url' => self::clean_url($item['url'] ?? ''),
                ];

            case self::TYPE_ANNOUNCEMENTS:
            case self::TYPE_FACULTY_NEWS:
            case self::TYPE_PUBLIC_RESOURCES:
            case self::TYPE_CTA_PANEL:
            default:
                return [
                    'title' => self::clean_text($item['title'] ?? ''),
                    'summary' => self::clean_text($item['summary'] ?? ''),
                    'date' => self::clean_text($item['date'] ?? ''),
                    'url' => self::clean_url($item['url'] ?? ''),
                    'source_label' => self::clean_text($item['source_label'] ?? ''),
                ];
        }
    }

    /**
     * Normalise item limit.
     *
     * @param mixed $value Raw limit.
     * @return int
     */
    private static function normalise_limit($value): int {
        if ($value === null || $value === '') {
            return self::DEFAULT_LIMIT;
        }

        $limit = (int)$value;

        if ($limit < 0) {
            return 0;
        }

        return min($limit, self::MAX_LIMIT);
    }

    /**
     * Whether the given visibility is public.
     *
     * @param mixed $visibility Raw visibility.
     * @return bool
     */
    private static function is_public_visibility($visibility): bool {
        return self::clean_id((string)$visibility) === 'public';
    }

    /**
     * Clean dynamic block id or public identifier.
     *
     * @param string $value Raw value.
     * @return string
     */
    private static function clean_id(string $value): string {
        $value = trim(\core_text::strtolower($value));

        if ($value === '') {
            return '';
        }

        return clean_param($value, PARAM_ALPHANUMEXT);
    }

    /**
     * Clean faculty slug.
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
     * Clean provider name.
     *
     * @param string $value Raw provider name.
     * @return string
     */
    private static function clean_provider_name(string $value): string {
        $value = trim(\core_text::strtolower($value));

        if ($value === '') {
            return '';
        }

        return clean_param($value, PARAM_ALPHANUMEXT);
    }

    /**
     * Clean dynamic block type.
     *
     * @param string $value Raw type.
     * @return string
     */
    private static function clean_type_name(string $value): string {
        $value = trim(\core_text::strtolower($value));

        if ($value === '') {
            return '';
        }

        return clean_param($value, PARAM_ALPHANUMEXT);
    }

    /**
     * Clean public plain text.
     *
     * @param mixed $value Raw text.
     * @return string
     */
    private static function clean_text($value): string {
        if (is_array($value) || is_object($value)) {
            return '';
        }

        $value = trim((string)$value);

        if ($value === '') {
            return '';
        }

        return clean_param(strip_tags($value), PARAM_TEXT);
    }

    /**
     * Clean a public URL.
     *
     * @param mixed $value Raw URL.
     * @return string
     */
    private static function clean_url($value): string {
        if (is_array($value) || is_object($value)) {
            return '';
        }

        $value = trim((string)$value);

        if ($value === '') {
            return '';
        }

        if (str_starts_with($value, '/') && defined('PARAM_LOCALURL')) {
            return clean_param($value, PARAM_LOCALURL);
        }

        return clean_param($value, PARAM_URL);
    }
}