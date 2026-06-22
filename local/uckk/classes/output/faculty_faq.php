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
 * Faculty FAQ output object for local_uckk.
 *
 * This renderable is the output boundary for one public FAQ entry inside a
 * faculty page.
 *
 * It receives one FAQ entry already prepared by:
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
 * - answer questions about individual learner progression;
 * - promise public accreditation for UCKK internal recognitions.
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
 * Renderable public faculty FAQ entry.
 *
 * Expected template:
 *
 *     local_uckk/faculty_faq
 *
 * Documented Mustache variables:
 * - question
 * - answer
 */
final class faculty_faq implements renderable, templatable, named_templatable {
    /** Moodle component. */
    private const COMPONENT = 'local_uckk';

    /** Mustache template name. */
    public const TEMPLATE = 'local_uckk/faculty_faq';

    /** Canonical public FAQ keys. */
    private const FAQ_KEYS = [
        'question',
        'answer',
    ];

    /** Fields that must never be exported by this public FAQ entry. */
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
        'progress' => true,
        'progression' => true,
        'enrolment' => true,
        'enrolments' => true,
        'submission' => true,
        'submissions' => true,
        'private' => true,
        'internal_comments' => true,
        'integrity_case' => true,
        'konnaxion_personal_data' => true,
    ];

    /** @var array<string, mixed> Raw public FAQ entry data. */
    private array $faq;

    /**
     * Constructor.
     *
     * @param array<string, mixed>|stdClass|string $faq Public FAQ entry data.
     * @param string $answer Optional answer when the first argument is a question string.
     */
    public function __construct($faq, string $answer = '') {
        if ($faq instanceof stdClass) {
            $faq = (array) $faq;
        }

        if (is_string($faq)) {
            $faq = [
                'question' => $faq,
                'answer' => $answer,
            ];
        }

        if (!is_array($faq)) {
            $faq = [];
        }

        $this->faq = $faq;
    }

    /**
     * Return the template name used when calling $OUTPUT->render($faq).
     *
     * @param renderer_base $renderer Renderer.
     * @return string Template name.
     */
    public function get_template_name(renderer_base $renderer): string {
        return self::TEMPLATE;
    }

    /**
     * Export one public FAQ entry for Mustache.
     *
     * @param renderer_base $output Renderer.
     * @return stdClass Mustache-ready FAQ entry context.
     */
    public function export_for_template(renderer_base $output): stdClass {
        $data = new stdClass();

        foreach (self::FAQ_KEYS as $key) {
            if (isset(self::FORBIDDEN_KEYS[$key])) {
                continue;
            }

            $data->{$key} = $this->clean_text_value($this->faq[$key] ?? '');
        }

        return $this->filter_faq_keys($data);
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
     * Keep only documented FAQ keys.
     *
     * @param stdClass $data Exported data.
     * @return stdClass Filtered data.
     */
    private function filter_faq_keys(stdClass $data): stdClass {
        $filtered = new stdClass();

        foreach (self::FAQ_KEYS as $key) {
            $filtered->{$key} = $data->{$key} ?? '';
        }

        return $filtered;
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