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
 * Faculty course card output object for local_uckk.
 *
 * This renderable is the output boundary for one public course card inside a
 * faculty page.
 *
 * It receives one course record already prepared by:
 * - local_uckk\local\faculty\faculty_page_builder;
 * - local_uckk\local\faculty\faculty_moodle_mapper;
 * - or another caller that respects the documented faculty page contract.
 *
 * It must not:
 * - read Atlas JSON files;
 * - read Faculty JSON files;
 * - query Moodle tables;
 * - decide visibility or permissions;
 * - create or update courses;
 * - expose notes, participants, completion status, grades, or private criteria;
 * - expose concepts_associes or criteres_passage on the public page by default.
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
 * Renderable public faculty course card.
 *
 * Expected template:
 *
 *     local_uckk/faculty_course_card
 *
 * Documented Mustache variables:
 * - cours_id
 * - ordre
 * - nom
 * - fullname
 * - concept_maitre_nom
 * - concept_maitre_definition
 * - artefact_type
 * - artefact_nom
 * - artefact_description
 * - moodle_url
 * - is_moodle_available
 */
final class faculty_course_card implements renderable, templatable, named_templatable {
    /** Moodle component. */
    private const COMPONENT = 'local_uckk';

    /** Mustache template name. */
    public const TEMPLATE = 'local_uckk/faculty_course_card';

    /** Canonical public course-card keys. */
    private const COURSE_KEYS = [
        'cours_id',
        'ordre',
        'nom',
        'fullname',
        'concept_maitre_nom',
        'concept_maitre_definition',
        'artefact_type',
        'artefact_nom',
        'artefact_description',
        'moodle_url',
        'is_moodle_available',
    ];

    /** Fields that must never be exported by this public card. */
    private const FORBIDDEN_KEYS = [
        'concepts_associes' => true,
        'criteres_passage' => true,
        'notes' => true,
        'participants' => true,
        'completion' => true,
        'completion_status' => true,
        'completionstatus' => true,
        'grades' => true,
        'grade' => true,
        'enrolments' => true,
        'enrolment' => true,
        'users' => true,
    ];

    /** @var array<string, mixed> Raw course card data. */
    private array $course;

    /**
     * Constructor.
     *
     * @param array<string, mixed>|stdClass $course Public course card data.
     */
    public function __construct($course) {
        if ($course instanceof stdClass) {
            $course = (array) $course;
        }

        if (!is_array($course)) {
            $course = [];
        }

        $this->course = $course;
    }

    /**
     * Return the template name used when calling $OUTPUT->render($card).
     *
     * @param renderer_base $renderer Renderer.
     * @return string Template name.
     */
    public function get_template_name(renderer_base $renderer): string {
        return self::TEMPLATE;
    }

    /**
     * Export one public course card for Mustache.
     *
     * @param renderer_base $output Renderer.
     * @return stdClass Mustache-ready course card context.
     */
    public function export_for_template(renderer_base $output): stdClass {
        $data = new stdClass();

        foreach (self::COURSE_KEYS as $key) {
            $data->{$key} = $this->export_field($key, $this->course[$key] ?? $this->default_value($key));
        }

        $data->is_moodle_available = $this->normalise_availability(
            $data->is_moodle_available,
            $data->moodle_url
        );

        return $data;
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
     * Get a safe default value for a documented field.
     *
     * @param string $key Field key.
     * @return mixed
     */
    private function default_value(string $key) {
        switch ($key) {
            case 'ordre':
                return 0;

            case 'is_moodle_available':
                return false;

            case 'cours_id':
            case 'nom':
            case 'fullname':
            case 'concept_maitre_nom':
            case 'concept_maitre_definition':
            case 'artefact_type':
            case 'artefact_nom':
            case 'artefact_description':
            case 'moodle_url':
            default:
                return '';
        }
    }

    /**
     * Export a documented field.
     *
     * @param string $key Field key.
     * @param mixed $value Raw value.
     * @return mixed
     */
    private function export_field(string $key, $value) {
        if (isset(self::FORBIDDEN_KEYS[$key])) {
            return '';
        }

        switch ($key) {
            case 'ordre':
                return $this->clean_order($value);

            case 'cours_id':
                return $this->clean_identifier($value);

            case 'moodle_url':
                return $this->clean_url_value($value);

            case 'is_moodle_available':
                return (bool) $value;

            case 'nom':
            case 'fullname':
            case 'concept_maitre_nom':
            case 'concept_maitre_definition':
            case 'artefact_type':
            case 'artefact_nom':
            case 'artefact_description':
            default:
                return $this->clean_text_value($value);
        }
    }

    /**
     * Normalise the public Moodle availability flag.
     *
     * A card is publicly linkable only when the upstream builder says it is
     * available and a safe URL is present.
     *
     * @param mixed $available Availability flag.
     * @param string $url Moodle URL.
     * @return bool
     */
    private function normalise_availability($available, string $url): bool {
        return (bool) $available && $url !== '';
    }

    /**
     * Clean the public pedagogical order.
     *
     * @param mixed $value Raw value.
     * @return int
     */
    private function clean_order($value): int {
        if (is_numeric($value)) {
            return max(0, (int) $value);
        }

        return 0;
    }

    /**
     * Clean a course identifier.
     *
     * @param mixed $value Raw identifier.
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
     * Clean a public Moodle course URL.
     *
     * Dangerous schemes are rejected. Relative Moodle URLs and HTTP(S) URLs
     * prepared upstream are preserved.
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