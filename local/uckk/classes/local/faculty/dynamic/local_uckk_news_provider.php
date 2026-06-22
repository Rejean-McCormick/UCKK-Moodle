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
 * Local UCKK news dynamic block provider for public faculty pages.
 *
 * This provider exposes public UCKK news references from the existing
 * local_uckk public news definition. It is intentionally read-only and
 * fail-closed.
 *
 * It does not:
 * - expose private news drafts;
 * - expose personal data;
 * - query private workflow records;
 * - create or modify news;
 * - decide template permissions;
 * - duplicate Moodle forum announcements.
 *
 * Moodle forum announcements belong to moodle_forum_provider.
 * Manual editorial blocks belong to local_uckk_manual_provider.
 *
 * @package    local_uckk
 * @copyright  2026 Univers-Cité King Klown
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace local_uckk\local\faculty\dynamic;

use local_uckk\local\public_pages\news;
use moodle_url;
use Throwable;

defined('MOODLE_INTERNAL') || die();

/**
 * Public local_uckk news provider.
 *
 * @package local_uckk
 */
final class local_uckk_news_provider implements provider_interface {
    /** Source provider name. */
    private const PROVIDER = 'local_uckk_news';

    /** Public news page URL. */
    private const NEWS_URL = '/local/uckk/news.php';

    /** Default number of news items to expose. */
    private const DEFAULT_LIMIT = 5;

    /** Maximum public news items returned from one block. */
    private const MAX_LIMIT = 50;

    /**
     * Return public local_uckk news items for one dynamic block.
     *
     * @param array<string, mixed> $block Dynamic block definition from *.faculty.json.
     * @param array<string, mixed> $faculty Resolved and normalised faculty profile.
     * @param array<string, mixed> $pagecontext Optional page builder context.
     * @return array<string, mixed> Payload containing public items.
     */
    public function get_items(array $block, array $faculty, array $pagecontext = []): array {
        $items = [];

        try {
            $source = self::get_source($block);

            if (self::clean_key((string)($source['provider'] ?? '')) !== self::PROVIDER) {
                return ['items' => $items];
            }

            $limit = self::normalise_limit($block['limit'] ?? null);

            if ($limit <= 0) {
                return ['items' => $items];
            }

            $definition = $this->news_definition();

            if (empty($definition)) {
                return ['items' => $items];
            }

            $items = $this->items_from_definition($definition, $limit);
        } catch (Throwable $e) {
            debugging('local_uckk news dynamic block provider failed.', DEBUG_DEVELOPER);
            return ['items' => []];
        }

        return ['items' => $items];
    }

    /**
     * Return the existing local_uckk public news page definition.
     *
     * @return array<string, mixed>
     */
    private function news_definition(): array {
        if (!class_exists(news::class)) {
            return [];
        }

        $definition = news::definition();

        return is_array($definition) ? $definition : [];
    }

    /**
     * Convert the public news page definition into dynamic block items.
     *
     * The dispatcher normalises final fields to:
     * title, summary, date, url, source_label.
     *
     * @param array<string, mixed> $definition Public news page definition.
     * @param int $limit Public item limit.
     * @return array<int, array<string, string>>
     */
    private function items_from_definition(array $definition, int $limit): array {
        $items = [];

        foreach (self::definition_cards($definition) as $card) {
            $item = $this->item_from_card($card);

            if (self::is_empty_item($item)) {
                continue;
            }

            $items[] = $item;

            if (count($items) >= $limit) {
                break;
            }
        }

        return $items;
    }

    /**
     * Extract cards from the public news definition.
     *
     * @param array<string, mixed> $definition Public news page definition.
     * @return array<int, array<string, mixed>>
     */
    private static function definition_cards(array $definition): array {
        $cards = $definition['cards'] ?? [];

        if (!is_array($cards)) {
            return [];
        }

        $out = [];

        foreach ($cards as $card) {
            if (is_array($card)) {
                $out[] = $card;
            }
        }

        return $out;
    }

    /**
     * Convert one public news card into a dynamic block item.
     *
     * @param array<string, mixed> $card Public news card.
     * @return array<string, string>
     */
    private function item_from_card(array $card): array {
        $title = self::clean_text($card['title'] ?? '');
        $summary = self::clean_text($card['body'] ?? $card['summary'] ?? '');
        $url = self::clean_url($card['url'] ?? '');

        if ($url === '') {
            $url = (new moodle_url(self::NEWS_URL))->out(false);
        }

        return [
            'title' => $title,
            'summary' => $summary,
            'date' => '',
            'url' => $url,
            'source_label' => self::clean_text(get_string('facultyannouncements', 'local_uckk')),
        ];
    }

    /**
     * Whether an exported item has no public value.
     *
     * @param array<string, string> $item Public item.
     * @return bool
     */
    private static function is_empty_item(array $item): bool {
        foreach (['title', 'summary', 'url'] as $key) {
            if (($item[$key] ?? '') !== '') {
                return false;
            }
        }

        return true;
    }

    /**
     * Extract source config from a block.
     *
     * @param array<string, mixed> $block Dynamic block definition.
     * @return array<string, mixed>
     */
    private static function get_source(array $block): array {
        $source = $block['source'] ?? [];

        return is_array($source) ? $source : [];
    }

    /**
     * Normalise a public block limit.
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
     * Clean a source key.
     *
     * @param string $value Raw key.
     * @return string
     */
    private static function clean_key(string $value): string {
        $value = trim(\core_text::strtolower($value));

        if ($value === '') {
            return '';
        }

        return clean_param($value, PARAM_ALPHANUMEXT);
    }

    /**
     * Clean public text.
     *
     * @param mixed $value Raw value.
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