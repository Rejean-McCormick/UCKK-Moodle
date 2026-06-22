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
 * Faculty dynamic block output object for local_uckk.
 *
 * This renderable is the output boundary for one public dynamic block inside a
 * faculty page.
 *
 * It receives one block already prepared and public-filtered by:
 * - local_uckk\local\faculty\faculty_page_builder;
 * - local_uckk\local\faculty\faculty_dynamic_block_provider;
 * - or a provider implementing the documented dynamic block contract.
 *
 * It must not:
 * - read Atlas JSON files;
 * - read Faculty JSON files;
 * - query Moodle tables;
 * - decide visibility or permissions;
 * - call provider services;
 * - create or update Moodle content;
 * - expose private forum posts, private calendar events, enrolments,
 *   completion, grades, submissions, private votes, integrity cases, or
 *   Konnaxion personal data.
 *
 * @package    local_uckk
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace local_uckk\output;

use moodle_url;
use named_templatable;
use renderable;
use renderer_base;
use stdClass;
use templatable;

defined('MOODLE_INTERNAL') || die();

/**
 * Renderable public faculty dynamic block.
 *
 * Expected template:
 *
 *     local_uckk/faculty_dynamic_block
 *
 * Documented Mustache variables:
 * - id
 * - type
 * - title
 * - items
 * - has_items
 * - empty_state
 * - visibility
 */
final class faculty_dynamic_block implements renderable, templatable, named_templatable {
    /** Moodle component. */
    private const COMPONENT = 'local_uckk';

    /** Mustache template name. */
    public const TEMPLATE = 'local_uckk/faculty_dynamic_block';

    /** Public visibility value. */
    private const VISIBILITY_PUBLIC = 'public';

    /** Canonical public block keys. */
    private const BLOCK_KEYS = [
        'id',
        'type',
        'title',
        'items',
        'has_items',
        'empty_state',
        'visibility',
    ];

    /** Allowed dynamic block types from the faculty contract. */
    private const ALLOWED_TYPES = [
        'announcements' => true,
        'events' => true,
        'moodle_course_list' => true,
        'featured_courses' => true,
        'faculty_news' => true,
        'related_faculties' => true,
        'public_resources' => true,
        'cta_panel' => true,
    ];

    /**
     * Allowed item keys by dynamic block type.
     *
     * `featured_courses`, `faculty_news`, `public_resources`, and `cta_panel`
     * intentionally reuse only fields already documented for public dynamic
     * block items. New item fields must be documented in DOC_12 before being
     * added here or used by the Mustache template.
     */
    private const ITEM_KEYS_BY_TYPE = [
        'announcements' => [
            'title' => true,
            'summary' => true,
            'date' => true,
            'url' => true,
            'source_label' => true,
        ],
        'faculty_news' => [
            'title' => true,
            'summary' => true,
            'date' => true,
            'url' => true,
            'source_label' => true,
        ],
        'events' => [
            'title' => true,
            'date_start' => true,
            'date_end' => true,
            'location' => true,
            'url' => true,
        ],
        'moodle_course_list' => [
            'cours_id' => true,
            'fullname' => true,
            'summary' => true,
            'url' => true,
            'availability_label' => true,
        ],
        'featured_courses' => [
            'cours_id' => true,
            'fullname' => true,
            'summary' => true,
            'url' => true,
            'availability_label' => true,
        ],
        'related_faculties' => [
            'faculty_id' => true,
            'slug' => true,
            'name' => true,
            'relation' => true,
            'url' => true,
        ],
        'public_resources' => [
            'title' => true,
            'summary' => true,
            'url' => true,
            'source_label' => true,
        ],
        'cta_panel' => [
            'title' => true,
            'summary' => true,
            'url' => true,
            'source_label' => true,
        ],
    ];

    /** Fields that must never be exported by this public dynamic block. */
    private const FORBIDDEN_KEYS = [
        'userid' => true,
        'user_id' => true,
        'username' => true,
        'email' => true,
        'author' => true,
        'authorid' => true,
        'author_id' => true,
        'participants' => true,
        'participantids' => true,
        'grades' => true,
        'grade' => true,
        'notes' => true,
        'feedback' => true,
        'completion' => true,
        'completion_status' => true,
        'completionstatus' => true,
        'enrolment' => true,
        'enrolments' => true,
        'submission' => true,
        'submissions' => true,
        'private' => true,
        'internal_comments' => true,
        'integrity_case' => true,
        'konnaxion_personal_data' => true,
    ];

    /** @var array<string, mixed> Raw public block data. */
    private array $block;

    /**
     * Constructor.
     *
     * @param array<string, mixed>|stdClass $block Public dynamic block data.
     */
    public function __construct($block) {
        if ($block instanceof stdClass) {
            $block = (array) $block;
        }

        if (!is_array($block)) {
            $block = [];
        }

        $this->block = $block;
    }

    /**
     * Return the template name used when calling $OUTPUT->render($block).
     *
     * @param renderer_base $renderer Renderer.
     * @return string Template name.
     */
    public function get_template_name(renderer_base $renderer): string {
        return self::TEMPLATE;
    }

    /**
     * Export one public dynamic block for Mustache.
     *
     * @param renderer_base $output Renderer.
     * @return stdClass Mustache-ready block context.
     */
    public function export_for_template(renderer_base $output): stdClass {
        $type = $this->normalise_type($this->block['type'] ?? '');
        $visibility = $this->normalise_visibility($this->block['visibility'] ?? self::VISIBILITY_PUBLIC);

        $items = [];
        if ($type !== '' && $visibility === self::VISIBILITY_PUBLIC) {
            $items = $this->export_items($this->block['items'] ?? [], $type);
        }

        $data = new stdClass();
        $data->id = $this->clean_identifier($this->block['id'] ?? '');
        $data->type = $type;
        $data->title = $this->clean_text_value($this->block['title'] ?? '');
        $data->items = $items;
        $data->has_items = !empty($items);
        $data->empty_state = $this->clean_text_value($this->block['empty_state'] ?? '');
        $data->visibility = $visibility;

        return $this->filter_top_level_keys($data);
    }

    /**
     * Return the template name as a static helper.
     *
     * @return string Moodle template name.
     */
    public static function template_name(): string {
        return self::TEMPLATE;
    }

    /**
     * Keep only documented top-level block keys.
     *
     * @param stdClass $data Exported data.
     * @return stdClass Filtered data.
     */
    private function filter_top_level_keys(stdClass $data): stdClass {
        $filtered = new stdClass();

        foreach (self::BLOCK_KEYS as $key) {
            $filtered->{$key} = $data->{$key} ?? $this->default_value($key);
        }

        return $filtered;
    }

    /**
     * Get a safe default value for a documented block key.
     *
     * @param string $key Block key.
     * @return mixed
     */
    private function default_value(string $key) {
        switch ($key) {
            case 'items':
                return [];

            case 'has_items':
                return false;

            case 'visibility':
                return self::VISIBILITY_PUBLIC;

            case 'id':
            case 'type':
            case 'title':
            case 'empty_state':
            default:
                return '';
        }
    }

    /**
     * Export block items according to the documented item schema for this type.
     *
     * @param mixed $items Raw items.
     * @param string $type Dynamic block type.
     * @return array<int, stdClass>
     */
    private function export_items($items, string $type): array {
        if (!is_array($items)) {
            return [];
        }

        $exported = [];

        foreach ($items as $item) {
            if ($item instanceof stdClass) {
                $item = (array) $item;
            }

            if (!is_array($item)) {
                continue;
            }

            $exported[] = $this->export_item($item, $type);
        }

        return $exported;
    }

    /**
     * Export one item using the whitelist for the current dynamic block type.
     *
     * @param array<string, mixed> $item Raw item.
     * @param string $type Dynamic block type.
     * @return stdClass Mustache-ready item.
     */
    private function export_item(array $item, string $type): stdClass {
        $data = new stdClass();
        $allowedkeys = self::ITEM_KEYS_BY_TYPE[$type] ?? [];

        foreach ($allowedkeys as $key => $enabled) {
            if (!$enabled || isset(self::FORBIDDEN_KEYS[$key])) {
                continue;
            }

            $data->{$key} = $this->export_item_field($key, $item[$key] ?? '');
        }

        return $data;
    }

    /**
     * Export one item field.
     *
     * @param string $key Item field key.
     * @param mixed $value Raw value.
     * @return string
     */
    private function export_item_field(string $key, $value): string {
        if (isset(self::FORBIDDEN_KEYS[$key])) {
            return '';
        }

        switch ($key) {
            case 'url':
                return $this->clean_url_value($value);

            case 'cours_id':
            case 'faculty_id':
            case 'slug':
                return $this->clean_identifier($value);

            case 'title':
            case 'summary':
            case 'date':
            case 'date_start':
            case 'date_end':
            case 'location':
            case 'source_label':
            case 'fullname':
            case 'availability_label':
            case 'name':
            case 'relation':
            default:
                return $this->clean_text_value($value);
        }
    }

    /**
     * Normalise the dynamic block type.
     *
     * @param mixed $type Raw type.
     * @return string Canonical type or empty string.
     */
    private function normalise_type($type): string {
        if (is_object($type) && !method_exists($type, '__toString')) {
            return '';
        }

        $type = trim((string) $type);

        if ($type === '' || !isset(self::ALLOWED_TYPES[$type])) {
            return '';
        }

        return $type;
    }

    /**
     * Normalise block visibility.
     *
     * Output objects do not decide permissions. Any non-public block is treated
     * as non-renderable data and exported with no items.
     *
     * @param mixed $visibility Raw visibility.
     * @return string
     */
    private function normalise_visibility($visibility): string {
        if (is_object($visibility) && !method_exists($visibility, '__toString')) {
            return '';
        }

        $visibility = trim((string) $visibility);

        if ($visibility !== self::VISIBILITY_PUBLIC) {
            return '';
        }

        return self::VISIBILITY_PUBLIC;
    }

    /**
     * Clean a public identifier.
     *
     * @param mixed $value Raw value.
     * @return string
     */
    private function clean_identifier($value): string {
        if (is_object($value) && !method_exists($value, '__toString')) {
            return '';
        }

        $identifier = trim((string) $value);

        if ($identifier === '') {
            return '';
        }

        if (function_exists('clean_param')) {
            return clean_param($identifier, PARAM_ALPHANUMEXT);
        }

        return preg_replace('/[^A-Za-z0-9_.-]/', '', $identifier) ?? '';
    }

    /**
     * Clean a public text field.
     *
     * Mustache escapes normal variables by default. This method still strips
     * unsafe HTML from values that may originate in JSON or Moodle text fields.
     *
     * @param mixed $value Raw value.
     * @return string Cleaned value.
     */
    private function clean_text_value($value): string {
        if (is_object($value) && !method_exists($value, '__toString')) {
            return '';
        }

        $text = trim((string) $value);

        if ($text === '') {
            return '';
        }

        if (function_exists('clean_text')) {
            return clean_text($text, FORMAT_HTML);
        }

        if (function_exists('clean_param')) {
            return clean_param($text, PARAM_RAW_TRIMMED);
        }

        return $text;
    }

    /**
     * Clean a public URL.
     *
     * Dangerous schemes are rejected. Relative Moodle URLs, anchors and HTTP(S)
     * URLs prepared upstream are preserved.
     *
     * @param mixed $value Raw URL.
     * @return string Cleaned URL.
     */
    private function clean_url_value($value): string {
        if ($value instanceof moodle_url) {
            $url = $value->out(false);
        } else if (is_object($value) && !method_exists($value, '__toString')) {
            return '';
        } else {
            $url = trim((string) $value);
        }

        if ($url === '') {
            return '';
        }

        if (preg_match('/^\s*(javascript|data|file|vbscript):/i', $url)) {
            return '';
        }

        if (function_exists('clean_param')) {
            return clean_param($url, PARAM_RAW_TRIMMED);
        }

        return $url;
    }
}