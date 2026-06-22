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
 * Manual JSON dynamic block provider for public UCKK faculty pages.
 *
 * This provider exposes static public items already present in a Faculty
 * Profile dynamic block. It is intentionally read-only and fail-closed.
 *
 * It does not:
 * - read Moodle data;
 * - read private user data;
 * - create tables;
 * - introduce another JSON source contract;
 * - expose raw HTML from JSON;
 * - bypass the dynamic block dispatcher.
 *
 * The canonical source declaration is:
 *
 * {
 *     "provider": "local_uckk_manual"
 * }
 *
 * Manual items, when used, must already be declared on the block as documented
 * public block items. The dispatcher still performs the final Mustache-shape
 * normalisation.
 *
 * @package    local_uckk
 * @copyright  2026 Univers-Cité King Klown
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace local_uckk\local\faculty\dynamic;

use moodle_url;
use Throwable;

defined('MOODLE_INTERNAL') || die();

/**
 * Public manual JSON provider.
 *
 * @package local_uckk
 */
final class local_uckk_manual_provider implements provider_interface {
    /** Source provider name. */
    private const PROVIDER = 'local_uckk_manual';

    /** Default number of manual items to expose. */
    private const DEFAULT_LIMIT = 5;

    /** Maximum public manual items returned from one block. */
    private const MAX_LIMIT = 50;

    /** Dynamic block type: announcements. */
    private const TYPE_ANNOUNCEMENTS = 'announcements';

    /** Dynamic block type: events. */
    private const TYPE_EVENTS = 'events';

    /** Dynamic block type: Moodle course list. */
    private const TYPE_MOODLE_COURSE_LIST = 'moodle_course_list';

    /** Dynamic block type: featured courses. */
    private const TYPE_FEATURED_COURSES = 'featured_courses';

    /** Dynamic block type: faculty news. */
    private const TYPE_FACULTY_NEWS = 'faculty_news';

    /** Dynamic block type: related faculties. */
    private const TYPE_RELATED_FACULTIES = 'related_faculties';

    /** Dynamic block type: public resources. */
    private const TYPE_PUBLIC_RESOURCES = 'public_resources';

    /** Dynamic block type: CTA panel. */
    private const TYPE_CTA_PANEL = 'cta_panel';

    /**
     * Return public manual items for one dynamic block.
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

            $rawitems = $this->raw_items($block);

            if (empty($rawitems)) {
                return ['items' => $items];
            }

            $type = self::clean_key((string)($block['type'] ?? ''));

            foreach ($rawitems as $item) {
                if (!is_array($item)) {
                    continue;
                }

                $normalised = $this->normalise_item($type, $item);

                if (self::is_empty_item($normalised)) {
                    continue;
                }

                $items[] = $normalised;

                if (count($items) >= $limit) {
                    break;
                }
            }
        } catch (Throwable $e) {
            debugging('local_uckk manual dynamic block provider failed.', DEBUG_DEVELOPER);
            return ['items' => []];
        }

        return ['items' => $items];
    }

    /**
     * Return raw manual items from the block.
     *
     * This uses the documented block-level items array. No additional JSON key
     * such as source.items, manual_items, payload, content, or data is introduced.
     *
     * @param array<string, mixed> $block Dynamic block definition.
     * @return array<int, mixed>
     */
    private function raw_items(array $block): array {
        $items = $block['items'] ?? [];

        if (!is_array($items)) {
            return [];
        }

        return array_values($items);
    }

    /**
     * Normalise one public item by dynamic block type.
     *
     * The returned fields intentionally match the dispatcher-supported fields.
     *
     * @param string $type Dynamic block type.
     * @param array<string, mixed> $item Raw manual item.
     * @return array<string, string>
     */
    private function normalise_item(string $type, array $item): array {
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
                    'summary' => self::clean_summary($item['summary'] ?? ''),
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
                    'summary' => self::clean_summary($item['summary'] ?? ''),
                    'date' => self::clean_text($item['date'] ?? ''),
                    'url' => self::clean_url($item['url'] ?? ''),
                    'source_label' => self::clean_text($item['source_label'] ?? ''),
                ];
        }
    }

    /**
     * Whether an exported item has no public value.
     *
     * @param array<string, string> $item Public item.
     * @return bool
     */
    private static function is_empty_item(array $item): bool {
        foreach ($item as $value) {
            if ($value !== '') {
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
     * Clean a source key or type key.
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
     * Clean a public id.
     *
     * @param string $value Raw id.
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
     * Clean a public slug.
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
     * Clean public plain text.
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
     * Clean and shorten a public summary.
     *
     * @param mixed $value Raw summary.
     * @return string
     */
    private static function clean_summary($value): string {
        $summary = self::clean_text($value);

        if ($summary === '') {
            return '';
        }

        return shorten_text($summary, 280);
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

        if (str_starts_with($value, '#')) {
            return clean_param($value, PARAM_TEXT);
        }

        if (str_starts_with($value, '/') && defined('PARAM_LOCALURL')) {
            return clean_param($value, PARAM_LOCALURL);
        }

        if (preg_match('/^[a-z0-9_-]+\.php(?:[?#].*)?$/i', $value) === 1) {
            return (new moodle_url('/local/uckk/' . $value))->out(false);
        }

        return clean_param($value, PARAM_URL);
    }
}