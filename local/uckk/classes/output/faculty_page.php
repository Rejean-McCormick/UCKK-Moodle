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
 * Faculty public page output object for local_uckk.
 *
 * This renderable is the output boundary between:
 * - local_uckk\local\faculty\faculty_page_builder;
 * - local_uckk/templates/faculty_page.mustache;
 * - the faculty partial templates.
 *
 * It receives an already-built public faculty page context and exports only
 * the documented Mustache variables for the faculty page contract.
 *
 * It must not:
 * - read JSON files;
 * - query Moodle tables;
 * - decide visibility or permissions;
 * - create courses;
 * - mutate Moodle data;
 * - expose private completion, grades, notes, participants, or enrolment data;
 * - present UCKK internal recognitions as public accredited degrees.
 *
 * @package    local_uckk
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace local_uckk\output;

use moodle_url;
use renderable;
use renderer_base;
use stdClass;
use templatable;

defined('MOODLE_INTERNAL') || die();

/**
 * Renderable faculty public page.
 *
 * Official top-level Mustache context:
 * - page
 * - hero
 * - navigation
 * - identity
 * - sections
 * - atlas
 * - courses
 * - project_final
 * - limits
 * - relations
 * - dynamic_blocks
 * - featured_blocks
 * - faq
 * - contact
 * - notices
 * - metadata
 */
final class faculty_page implements renderable, templatable {
    /** Moodle component. */
    private const COMPONENT = 'local_uckk';

    /** Mustache template name. */
    public const TEMPLATE = 'local_uckk/faculty_page';

    /** Canonical top-level variables accepted by templates/faculty_page.mustache. */
    private const TOP_LEVEL_KEYS = [
        'page',
        'hero',
        'navigation',
        'identity',
        'sections',
        'atlas',
        'courses',
        'project_final',
        'limits',
        'relations',
        'dynamic_blocks',
        'featured_blocks',
        'faq',
        'contact',
        'notices',
        'metadata',
    ];

    /** Keys that represent URL-like public targets. */
    private const URL_KEYS = [
        'canonical_url' => true,
        'target' => true,
        'url' => true,
        'moodle_url' => true,
    ];

    /** Keys that represent email addresses. */
    private const EMAIL_KEYS = [
        'email' => true,
    ];

    /** @var array<string, mixed> Already-built page data from faculty_page_builder. */
    private array $context;

    /**
     * Constructor.
     *
     * @param array<string, mixed> $context Public faculty page context.
     */
    public function __construct(array $context) {
        $this->context = $context;
    }

    /**
     * Export the page context for Mustache.
     *
     * The builder owns data composition. This class only normalises the final
     * render contract and drops undocumented top-level keys.
     *
     * @param renderer_base $output Renderer.
     * @return stdClass Mustache-ready context.
     */
    public function export_for_template(renderer_base $output): stdClass {
        $data = new stdClass();

        foreach (self::TOP_LEVEL_KEYS as $key) {
            $value = $this->context[$key] ?? $this->default_value($key);
            $data->{$key} = $this->export_value($value, $key);
        }

        return $data;
    }

    /**
     * Return the template name used by this renderable.
     *
     * @return string Moodle template name.
     */
    public static function template_name(): string {
        return self::TEMPLATE;
    }

    /**
     * Get a safe default for a documented top-level key.
     *
     * @param string $key Top-level key.
     * @return mixed
     */
    private function default_value(string $key) {
        switch ($key) {
            case 'page':
                return [
                    'slug' => '',
                    'faculty_id' => '',
                    'voie_id' => '',
                    'status' => '',
                    'visibility' => '',
                    'seo_title' => '',
                    'seo_description' => '',
                    'canonical_url' => '',
                ];

            case 'hero':
                return [
                    'eyebrow' => '',
                    'title' => '',
                    'subtitle' => '',
                    'summary' => '',
                    'primary_cta' => [
                        'label' => '',
                        'target' => '',
                    ],
                    'secondary_cta' => [
                        'label' => '',
                        'target' => '',
                    ],
                ];

            case 'identity':
                return [
                    'name' => '',
                    'short_name' => '',
                    'title_symbolique' => '',
                    'domain' => '',
                    'level' => '',
                    'faculty_role' => '',
                    'one_sentence' => '',
                ];

            case 'atlas':
                return [
                    'definition_courte' => '',
                    'angle_fondamental' => '',
                    'competence_centrale' => '',
                    'seuils_progression' => [],
                    'show_definition_courte' => true,
                    'show_angle_fondamental' => true,
                    'show_competence_centrale' => true,
                    'show_seuils_progression' => true,
                ];

            case 'contact':
                return [
                    'label' => '',
                    'body' => '',
                    'email' => '',
                    'cta' => [
                        'label' => '',
                        'target' => '',
                    ],
                ];

            case 'navigation':
            case 'sections':
            case 'courses':
            case 'project_final':
            case 'limits':
            case 'relations':
            case 'dynamic_blocks':
            case 'featured_blocks':
            case 'faq':
            case 'notices':
            case 'metadata':
                return [];

            default:
                return '';
        }
    }

    /**
     * Export any supported value into a JSON-serialisable Mustache value.
     *
     * @param mixed $value Input value.
     * @param string $key Current key name.
     * @return mixed
     */
    private function export_value($value, string $key = '') {
        if ($value instanceof moodle_url) {
            return $this->clean_url_value($value);
        }

        if (is_bool($value) || is_int($value) || is_float($value)) {
            return $value;
        }

        if ($value === null) {
            return '';
        }

        if (is_array($value)) {
            if ($this->is_list_array($value)) {
                return $this->export_list($value);
            }

            return $this->export_record($value);
        }

        if ($value instanceof stdClass) {
            return $this->export_record((array) $value);
        }

        if (isset(self::EMAIL_KEYS[$key])) {
            return $this->clean_email_value($value);
        }

        if (isset(self::URL_KEYS[$key])) {
            return $this->clean_url_value($value);
        }

        return $this->clean_text_value($value);
    }

    /**
     * Export a list of values.
     *
     * @param array<int, mixed> $values List values.
     * @return array<int, mixed>
     */
    private function export_list(array $values): array {
        $items = [];

        foreach (array_values($values) as $value) {
            $items[] = $this->export_value($value);
        }

        return $items;
    }

    /**
     * Export an associative array or object as stdClass.
     *
     * Nested keys are kept because they belong to the builder/template contract.
     * Invalid property names are ignored to avoid leaking malformed data into
     * Mustache.
     *
     * @param array<string, mixed> $record Associative record.
     * @return stdClass
     */
    private function export_record(array $record): stdClass {
        $data = new stdClass();

        foreach ($record as $key => $value) {
            if (!is_string($key) || !$this->is_valid_property_name($key)) {
                continue;
            }

            $data->{$key} = $this->export_value($value, $key);
        }

        return $data;
    }

    /**
     * Clean a text value.
     *
     * Mustache escapes normal variables by default. This cleaning step removes
     * unsafe HTML when templates intentionally use preformatted public text.
     *
     * @param mixed $value Raw value.
     * @return string Cleaned text.
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
     * Clean a public URL or anchor target.
     *
     * Dangerous schemes are rejected. Relative Moodle URLs, anchors and HTTP(S)
     * URLs prepared by upstream services are preserved.
     *
     * @param mixed $value Raw URL value.
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

    /**
     * Clean a public email value.
     *
     * @param mixed $value Raw email value.
     * @return string Valid email address or empty string.
     */
    private function clean_email_value($value): string {
        if (is_object($value) && !method_exists($value, '__toString')) {
            return '';
        }

        $email = trim((string) $value);

        if ($email === '') {
            return '';
        }

        if (function_exists('validate_email') && !validate_email($email)) {
            return '';
        }

        if (function_exists('clean_param')) {
            return clean_param($email, PARAM_EMAIL);
        }

        return $email;
    }

    /**
     * Check whether an array is a sequential list.
     *
     * This avoids relying on array_is_list() for broader Moodle/PHP runtime
     * compatibility.
     *
     * @param array<mixed> $value Array to inspect.
     * @return bool
     */
    private function is_list_array(array $value): bool {
        if ($value === []) {
            return true;
        }

        return array_keys($value) === range(0, count($value) - 1);
    }

    /**
     * Check whether a key is safe as a Mustache object property.
     *
     * @param string $key Property name.
     * @return bool
     */
    private function is_valid_property_name(string $key): bool {
        return (bool) preg_match('/^[a-z][a-z0-9_]*$/', $key);
    }
}