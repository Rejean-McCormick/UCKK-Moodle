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
 * Faculty notice output object for local_uckk.
 *
 * This renderable is the output boundary for one public faculty notice.
 *
 * It receives one notice already prepared by:
 * - local_uckk\local\faculty\faculty_page_builder;
 * - local_uckk\local\faculty\faculty_normalizer;
 * - or another caller that respects the documented faculty page contract.
 *
 * It must not:
 * - read Atlas JSON files;
 * - read Faculty JSON files;
 * - query Moodle tables;
 * - decide visibility or permissions;
 * - create or update Moodle content;
 * - expose grades, completion, enrolments, users, private notes, private votes,
 *   integrity case details, or Konnaxion personal data;
 * - present UCKK internal recognitions as accredited public degrees.
 *
 * @package    local_uckk
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace local_uckk\output;

use named_templatable;
use renderable;
use renderer_base;
use stdClass;
use templatable;

defined('MOODLE_INTERNAL') || die();

/**
 * Renderable public faculty notice.
 *
 * Expected template:
 *
 *     local_uckk/faculty_notice
 *
 * Documented Mustache variables:
 * - title
 * - body
 * - type
 * - classes
 * - hastitle
 */
final class faculty_notice implements renderable, templatable, named_templatable {
    /** Moodle component. */
    private const COMPONENT = 'local_uckk';

    /** Mustache template name. */
    public const TEMPLATE = 'local_uckk/faculty_notice';

    /** Default notice type. */
    private const TYPE_INSTITUTIONAL = 'institutional';

    /** Canonical public notice keys. */
    private const NOTICE_KEYS = [
        'title',
        'body',
        'type',
        'classes',
        'hastitle',
    ];

    /** Allowed public notice types. */
    private const ALLOWED_TYPES = [
        'institutional' => true,
        'integrity' => true,
        'warning' => true,
        'light' => true,
    ];

    /** Aliases accepted from upstream faculty data. */
    private const TYPE_ALIASES = [
        'notice' => self::TYPE_INSTITUTIONAL,
        'internal' => self::TYPE_INSTITUTIONAL,
        'recognition' => self::TYPE_INSTITUTIONAL,
        'guardrail' => 'integrity',
        'ethical' => 'integrity',
        'ethics' => 'integrity',
        'info' => 'light',
    ];

    /** Fields that must never be exported by this public notice. */
    private const FORBIDDEN_KEYS = [
        'userid' => true,
        'user_id' => true,
        'username' => true,
        'email' => true,
        'participants' => true,
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

    /** @var array<string, mixed> Raw public notice data. */
    private array $notice;

    /**
     * Constructor.
     *
     * @param array<string, mixed>|stdClass|string $notice Public notice data.
     */
    public function __construct($notice) {
        if ($notice instanceof stdClass) {
            $notice = (array) $notice;
        }

        if (is_string($notice)) {
            $notice = [
                'title' => '',
                'body' => $notice,
                'type' => self::TYPE_INSTITUTIONAL,
            ];
        }

        if (!is_array($notice)) {
            $notice = [];
        }

        $this->notice = $notice;
    }

    /**
     * Return the template name used when calling $OUTPUT->render($notice).
     *
     * @param renderer_base $renderer Renderer.
     * @return string Template name.
     */
    public function get_template_name(renderer_base $renderer): string {
        return self::TEMPLATE;
    }

    /**
     * Export one public faculty notice for Mustache.
     *
     * @param renderer_base $output Renderer.
     * @return stdClass Mustache-ready notice context.
     */
    public function export_for_template(renderer_base $output): stdClass {
        $title = $this->clean_text_value($this->notice['title'] ?? '');
        $body = $this->clean_text_value($this->notice['body'] ?? $this->notice['text'] ?? '');
        $type = $this->normalise_type($this->notice['type'] ?? self::TYPE_INSTITUTIONAL);

        $data = new stdClass();
        $data->title = $title;
        $data->body = $body;
        $data->type = $type;
        $data->classes = $this->notice_classes($type);
        $data->hastitle = $title !== '';

        return $this->filter_notice_keys($data);
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
     * Keep only documented top-level notice keys.
     *
     * @param stdClass $data Exported data.
     * @return stdClass Filtered data.
     */
    private function filter_notice_keys(stdClass $data): stdClass {
        $filtered = new stdClass();

        foreach (self::NOTICE_KEYS as $key) {
            if (isset(self::FORBIDDEN_KEYS[$key])) {
                continue;
            }

            $filtered->{$key} = $data->{$key} ?? $this->default_value($key);
        }

        return $filtered;
    }

    /**
     * Get a safe default value for a documented notice key.
     *
     * @param string $key Notice key.
     * @return mixed
     */
    private function default_value(string $key) {
        switch ($key) {
            case 'type':
                return self::TYPE_INSTITUTIONAL;

            case 'classes':
                return $this->notice_classes(self::TYPE_INSTITUTIONAL);

            case 'hastitle':
                return false;

            case 'title':
            case 'body':
            default:
                return '';
        }
    }

    /**
     * Normalise a public notice type.
     *
     * @param mixed $type Raw notice type.
     * @return string Canonical notice type.
     */
    private function normalise_type($type): string {
        if (is_object($type) && !method_exists($type, '__toString')) {
            return self::TYPE_INSTITUTIONAL;
        }

        $type = trim((string) $type);

        if ($type === '') {
            return self::TYPE_INSTITUTIONAL;
        }

        if (function_exists('clean_param')) {
            $type = clean_param($type, PARAM_ALPHANUMEXT);
        } else {
            $type = preg_replace('/[^A-Za-z0-9_.-]/', '', $type) ?? '';
        }

        $type = strtolower(str_replace('_', '-', $type));
        $type = str_replace('-', '_', $type);

        if (isset(self::TYPE_ALIASES[$type])) {
            $type = self::TYPE_ALIASES[$type];
        }

        if (!isset(self::ALLOWED_TYPES[$type])) {
            return self::TYPE_INSTITUTIONAL;
        }

        return $type;
    }

    /**
     * Build CSS classes for a public faculty notice.
     *
     * @param string $type Canonical notice type.
     * @return string CSS class list.
     */
    private function notice_classes(string $type): string {
        $type = $this->normalise_type($type);

        return 'local-uckk-faculty-notice local-uckk-faculty-notice--' . $type;
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
}