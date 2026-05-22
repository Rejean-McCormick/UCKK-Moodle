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
 * UCKK course format.
 *
 * The UCKK format provides a structured pedagogical layout for courses in the
 * Univers-Cité King Klown. It is intentionally conservative: it uses Moodle's
 * course format API, stores only format options in course_format_options, and
 * delegates all HTML rendering to output classes and Mustache templates.
 *
 * This file must not contain grading rules, challenge workflows, archive
 * validation, assembly procedures, integrity decisions, or AI authority.
 * Those responsibilities belong to their own plugins.
 *
 * @package    format_uckk
 * @copyright  2026 Univers-Cité King Klown
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

use core_courseformat\base as course_format_base;

/**
 * UCKK course format class.
 *
 * Standard UCKK section sequence:
 *
 * 0. Orientation
 * 1. Concepts
 * 2. Matière canonique
 * 3. Atelier
 * 4. Preuves
 * 5. Délibération
 * 6. Livrable
 * 7. Évaluation
 * 8. Archive
 *
 * @package format_uckk
 */
class format_uckk extends course_format_base {
    /**
     * Format option: canonical course mode.
     */
    public const OPTION_UCKK_MODE = 'uckkmode';

    /**
     * Format option: show the canonical UCKK guidance block.
     */
    public const OPTION_SHOW_CANON = 'showcanon';

    /**
     * Format option: show proof / archive affordances.
     */
    public const OPTION_SHOW_EVIDENCE_FLOW = 'showevidenceflow';

    /**
     * Format option: show integrity reminders.
     */
    public const OPTION_SHOW_INTEGRITY_NOTICE = 'showintegritynotice';

    /**
     * Format option: show internal recognition notice.
     */
    public const OPTION_SHOW_RECOGNITION_NOTICE = 'showrecognitionnotice';

    /**
     * Section option: semantic UCKK section type.
     */
    public const OPTION_SECTION_KIND = 'uckksectionkind';

    /**
     * Section option: whether the section requires evidence.
     */
    public const OPTION_SECTION_REQUIRES_PROOF = 'requiresproof';

    /**
     * Section option: whether the section should be linked to the course archive.
     */
    public const OPTION_SECTION_ARCHIVABLE = 'archivable';

    /**
     * Section option: whether the section should display integrity guidance.
     */
    public const OPTION_SECTION_INTEGRITY = 'integritysensitive';

    /**
     * UCKK mode: standard course.
     */
    public const MODE_STANDARD = 'standard';

    /**
     * UCKK mode: tronc commun course.
     */
    public const MODE_TRONC_COMMUN = 'tronccommun';

    /**
     * UCKK mode: internal program course.
     */
    public const MODE_PROGRAM = 'program';

    /**
     * UCKK mode: laboratory / seminar.
     */
    public const MODE_LAB = 'lab';

    /**
     * Section kind: orientation.
     */
    public const SECTION_ORIENTATION = 'orientation';

    /**
     * Section kind: concepts.
     */
    public const SECTION_CONCEPTS = 'concepts';

    /**
     * Section kind: canonical material.
     */
    public const SECTION_CANON = 'canon';

    /**
     * Section kind: workshop.
     */
    public const SECTION_WORKSHOP = 'atelier';

    /**
     * Section kind: proofs.
     */
    public const SECTION_PROOFS = 'preuves';

    /**
     * Section kind: deliberation.
     */
    public const SECTION_DELIBERATION = 'deliberation';

    /**
     * Section kind: final deliverable.
     */
    public const SECTION_DELIVERABLE = 'livrable';

    /**
     * Section kind: evaluation.
     */
    public const SECTION_EVALUATION = 'evaluation';

    /**
     * Section kind: archive.
     */
    public const SECTION_ARCHIVE = 'archive';

    /**
     * Default UCKK section order.
     *
     * The key is the section number. Section 0 is the general course section.
     *
     * @return array<int, string>
     */
    public static function get_default_section_kinds(): array {
        return [
            0 => self::SECTION_ORIENTATION,
            1 => self::SECTION_CONCEPTS,
            2 => self::SECTION_CANON,
            3 => self::SECTION_WORKSHOP,
            4 => self::SECTION_PROOFS,
            5 => self::SECTION_DELIBERATION,
            6 => self::SECTION_DELIVERABLE,
            7 => self::SECTION_EVALUATION,
            8 => self::SECTION_ARCHIVE,
        ];
    }

    /**
     * Default UCKK section names.
     *
     * @return array<int, string>
     */
    public static function get_default_section_string_keys(): array {
        return [
            0 => 'course_orientation',
            1 => 'course_concepts',
            2 => 'course_canon',
            3 => 'course_workshop',
            4 => 'course_proofs',
            5 => 'course_deliberation',
            6 => 'course_deliverable',
            7 => 'course_evaluation',
            8 => 'course_archive',
        ];
    }

    /**
     * Course format options.
     *
     * Do not store absolute ids here. Moodle backs course format options as
     * additional course fields, so these values must remain portable.
     *
     * @param bool $foreditform Whether this definition is for an edit form.
     * @return array
     */
    public function course_format_options($foreditform = false): array {
        $options = [
            self::OPTION_UCKK_MODE => [
                'default' => self::MODE_STANDARD,
                'type' => PARAM_ALPHANUMEXT,
            ],
            self::OPTION_SHOW_CANON => [
                'default' => 1,
                'type' => PARAM_BOOL,
            ],
            self::OPTION_SHOW_EVIDENCE_FLOW => [
                'default' => 1,
                'type' => PARAM_BOOL,
            ],
            self::OPTION_SHOW_INTEGRITY_NOTICE => [
                'default' => 1,
                'type' => PARAM_BOOL,
            ],
            self::OPTION_SHOW_RECOGNITION_NOTICE => [
                'default' => 1,
                'type' => PARAM_BOOL,
            ],
        ];

        if ($foreditform) {
            $options[self::OPTION_UCKK_MODE]['label'] = get_string('option_uckkmode', 'format_uckk');
            $options[self::OPTION_UCKK_MODE]['element_type'] = 'select';
            $options[self::OPTION_UCKK_MODE]['element_attributes'] = [
                [
                    self::MODE_STANDARD => get_string('mode_standard', 'format_uckk'),
                    self::MODE_TRONC_COMMUN => get_string('mode_tronccommun', 'format_uckk'),
                    self::MODE_PROGRAM => get_string('mode_program', 'format_uckk'),
                    self::MODE_LAB => get_string('mode_lab', 'format_uckk'),
                ],
            ];
            $options[self::OPTION_UCKK_MODE]['help'] = 'option_uckkmode';

            $options[self::OPTION_SHOW_CANON]['label'] = get_string('option_showcanon', 'format_uckk');
            $options[self::OPTION_SHOW_CANON]['element_type'] = 'advcheckbox';
            $options[self::OPTION_SHOW_CANON]['help'] = 'option_showcanon';

            $options[self::OPTION_SHOW_EVIDENCE_FLOW]['label'] = get_string('option_showevidenceflow', 'format_uckk');
            $options[self::OPTION_SHOW_EVIDENCE_FLOW]['element_type'] = 'advcheckbox';
            $options[self::OPTION_SHOW_EVIDENCE_FLOW]['help'] = 'option_showevidenceflow';

            $options[self::OPTION_SHOW_INTEGRITY_NOTICE]['label'] = get_string('option_showintegritynotice', 'format_uckk');
            $options[self::OPTION_SHOW_INTEGRITY_NOTICE]['element_type'] = 'advcheckbox';
            $options[self::OPTION_SHOW_INTEGRITY_NOTICE]['help'] = 'option_showintegritynotice';

            $options[self::OPTION_SHOW_RECOGNITION_NOTICE]['label'] = get_string(
                'option_showrecognitionnotice',
                'format_uckk'
            );
            $options[self::OPTION_SHOW_RECOGNITION_NOTICE]['element_type'] = 'advcheckbox';
            $options[self::OPTION_SHOW_RECOGNITION_NOTICE]['help'] = 'option_showrecognitionnotice';
        }

        return $options;
    }

    /**
     * Section format options.
     *
     * These options describe the pedagogical function of a section. They do not
     * store links to activities, archives, challenges, assemblies or files.
     *
     * @param bool $foreditform Whether this definition is for an edit form.
     * @return array
     */
    public function section_format_options($foreditform = false): array {
        $options = [
            self::OPTION_SECTION_KIND => [
                'default' => '',
                'type' => PARAM_ALPHANUMEXT,
            ],
            self::OPTION_SECTION_REQUIRES_PROOF => [
                'default' => 0,
                'type' => PARAM_BOOL,
            ],
            self::OPTION_SECTION_ARCHIVABLE => [
                'default' => 0,
                'type' => PARAM_BOOL,
            ],
            self::OPTION_SECTION_INTEGRITY => [
                'default' => 0,
                'type' => PARAM_BOOL,
            ],
        ];

        if ($foreditform) {
            $options[self::OPTION_SECTION_KIND]['label'] = get_string('sectionkind', 'format_uckk');
            $options[self::OPTION_SECTION_KIND]['element_type'] = 'select';
            $options[self::OPTION_SECTION_KIND]['element_attributes'] = [
                [
                    '' => get_string('sectionkind_auto', 'format_uckk'),
                    self::SECTION_ORIENTATION => get_string('sectionkind_orientation', 'format_uckk'),
                    self::SECTION_CONCEPTS => get_string('sectionkind_concepts', 'format_uckk'),
                    self::SECTION_CANON => get_string('sectionkind_canon', 'format_uckk'),
                    self::SECTION_WORKSHOP => get_string('sectionkind_workshop', 'format_uckk'),
                    self::SECTION_PROOFS => get_string('sectionkind_proofs', 'format_uckk'),
                    self::SECTION_DELIBERATION => get_string('sectionkind_deliberation', 'format_uckk'),
                    self::SECTION_DELIVERABLE => get_string('sectionkind_deliverable', 'format_uckk'),
                    self::SECTION_EVALUATION => get_string('sectionkind_evaluation', 'format_uckk'),
                    self::SECTION_ARCHIVE => get_string('sectionkind_archive', 'format_uckk'),
                ],
            ];
            $options[self::OPTION_SECTION_KIND]['help'] = 'sectionkind';

            $options[self::OPTION_SECTION_REQUIRES_PROOF]['label'] = get_string('sectionrequiresproof', 'format_uckk');
            $options[self::OPTION_SECTION_REQUIRES_PROOF]['element_type'] = 'advcheckbox';
            $options[self::OPTION_SECTION_REQUIRES_PROOF]['help'] = 'sectionrequiresproof';

            $options[self::OPTION_SECTION_ARCHIVABLE]['label'] = get_string('sectionarchivable', 'format_uckk');
            $options[self::OPTION_SECTION_ARCHIVABLE]['element_type'] = 'advcheckbox';
            $options[self::OPTION_SECTION_ARCHIVABLE]['help'] = 'sectionarchivable';

            $options[self::OPTION_SECTION_INTEGRITY]['label'] = get_string('sectionintegritysensitive', 'format_uckk');
            $options[self::OPTION_SECTION_INTEGRITY]['element_type'] = 'advcheckbox';
            $options[self::OPTION_SECTION_INTEGRITY]['help'] = 'sectionintegritysensitive';
        }

        return $options;
    }

    /**
     * UCKK uses sections.
     *
     * @return bool
     */
    public function uses_sections(): bool {
        return true;
    }

    /**
     * UCKK uses the course index.
     *
     * @return bool
     */
    public function uses_course_index(): bool {
        return true;
    }

    /**
     * UCKK supports the modern reactive course editor components.
     *
     * @return bool
     */
    public function supports_components(): bool {
        return true;
    }

    /**
     * UCKK does not use legacy activity indentation.
     *
     * @return bool
     */
    public function uses_indentation(): bool {
        return false;
    }

    /**
     * News forum is not mandatory for this format.
     *
     * @return bool
     */
    public function supports_news(): bool {
        return false;
    }

    /**
     * Return the display mode.
     *
     * UCKK courses are intentionally single-page by default because the course
     * structure functions as a complete pedagogical map.
     *
     * @return int
     */
    public function get_course_display(): int {
        return COURSE_DISPLAY_SINGLEPAGE;
    }

    /**
     * Maximum number of sections.
     *
     * The canonical sequence is 0–8. Additional sections remain possible for
     * seminars or extended programs, but the default pedagogical spine remains
     * stable.
     *
     * @return int
     */
    public function get_max_sections(): int {
        return 52;
    }

    /**
     * Default blocks for newly created UCKK courses.
     *
     * Moodle core expects this method to return an array of block names.
     *
     * @return array<int, string>
     */
    public function get_default_blocks(): array {
        $blocks = ['completionstatus'];

        if (core_component::get_plugin_directory('block', 'uckk_dashboard') !== null) {
            array_unshift($blocks, 'uckk_dashboard');
        }

        return $blocks;
    }

    /**
     * Return the section name.
     *
     * If the teacher has provided a custom section name, Moodle's custom name is
     * preserved. Otherwise the UCKK canonical section name is used for the first
     * nine sections.
     *
     * @param section_info|stdClass|int $section Section object or section number.
     * @return string
     */
    public function get_section_name($section): string {
        $section = $this->normalise_section($section);

        if (!empty($section->name)) {
            return format_string(
                $section->name,
                true,
                ['context' => context_course::instance($this->get_courseid())]
            );
        }

        return $this->get_default_section_name($section);
    }

    /**
     * Return the default section name.
     *
     * @param section_info|stdClass|int $section Section object or section number.
     * @return string
     */
    public function get_default_section_name($section): string {
        $section = $this->normalise_section($section);
        $sectionnum = (int)$section->section;

        $keys = self::get_default_section_string_keys();

        if (array_key_exists($sectionnum, $keys)) {
            return get_string($keys[$sectionnum], 'format_uckk');
        }

        return get_string('sectionname', 'format_uckk', $sectionnum);
    }

    /**
     * Return the semantic UCKK section kind.
     *
     * @param section_info|stdClass|int $section Section object or section number.
     * @return string
     */
    public function get_uckk_section_kind($section): string {
        $section = $this->normalise_section($section);

        if (!empty($section->{self::OPTION_SECTION_KIND})) {
            return clean_param($section->{self::OPTION_SECTION_KIND}, PARAM_ALPHANUMEXT);
        }

        $defaults = self::get_default_section_kinds();
        $sectionnum = (int)$section->section;

        return $defaults[$sectionnum] ?? 'custom';
    }

    /**
     * Return the URL for a course or section.
     *
     * @param int|section_info|stdClass $section Section number or section object.
     * @param array $options Extra options.
     * @return moodle_url
     */
    public function get_view_url($section, $options = []): moodle_url {
        $course = $this->get_course();
        $url = new moodle_url('/course/view.php', ['id' => $course->id]);

        if ($section !== null) {
            $section = $this->normalise_section($section);
            $sectionnum = (int)$section->section;

            if ($sectionnum > 0) {
                $url->param('section', $sectionnum);
            }
        }

        if (!empty($options['navigation'])) {
            $url->param('navigation', clean_param($options['navigation'], PARAM_ALPHANUMEXT));
        }

        return $url;
    }

    /**
     * Return a display title for the page.
     *
     * @return string
     */
    public function page_title(): string {
        $course = $this->get_course();

        return format_string(
            $course->fullname,
            true,
            ['context' => context_course::instance($course->id)]
        );
    }

    /**
     * Is this course configured as tronc commun?
     *
     * @return bool
     */
    public function is_tronc_commun(): bool {
        return $this->get_uckk_mode() === self::MODE_TRONC_COMMUN;
    }

    /**
     * Is this course configured as a program course?
     *
     * @return bool
     */
    public function is_program_course(): bool {
        return $this->get_uckk_mode() === self::MODE_PROGRAM;
    }

    /**
     * Return the UCKK mode for the course.
     *
     * @return string
     */
    public function get_uckk_mode(): string {
        $course = $this->get_course();

        if (!empty($course->{self::OPTION_UCKK_MODE})) {
            return clean_param($course->{self::OPTION_UCKK_MODE}, PARAM_ALPHANUMEXT);
        }

        $shortname = $course->shortname ?? '';
        $idnumber = $course->idnumber ?? '';

        if (preg_match('/^UCKK-TC/i', $shortname) || preg_match('/^UCKK-TC/i', $idnumber)) {
            return self::MODE_TRONC_COMMUN;
        }

        if (preg_match('/^UCKK-/i', $shortname) || preg_match('/^UCKK-/i', $idnumber)) {
            return self::MODE_PROGRAM;
        }

        return self::MODE_STANDARD;
    }

    /**
     * Return whether the format should display canonical guidance.
     *
     * @return bool
     */
    public function show_canon(): bool {
        $course = $this->get_course();

        return !empty($course->{self::OPTION_SHOW_CANON});
    }

    /**
     * Return whether the format should display evidence / archive flow.
     *
     * @return bool
     */
    public function show_evidence_flow(): bool {
        $course = $this->get_course();

        return !empty($course->{self::OPTION_SHOW_EVIDENCE_FLOW});
    }

    /**
     * Return whether the format should display integrity notices.
     *
     * @return bool
     */
    public function show_integrity_notice(): bool {
        $course = $this->get_course();

        return !empty($course->{self::OPTION_SHOW_INTEGRITY_NOTICE});
    }

    /**
     * Return whether the format should display internal recognition notices.
     *
     * @return bool
     */
    public function show_recognition_notice(): bool {
        $course = $this->get_course();

        return !empty($course->{self::OPTION_SHOW_RECOGNITION_NOTICE});
    }

    /**
     * Clean additional data when a course using this format is deleted.
     *
     * The current implementation stores all course-format data using Moodle's
     * standard course_format_options mechanism, so there is no separate custom
     * table cleanup required here.
     *
     * @return void
     */
    public function delete_format_data(): void {
        // No custom persistence in this file. Other UCKK plugins own their data.
    }

    /**
     * Return an exportable representation of the UCKK course structure.
     *
     * This helper is intended for output classes and templates.
     *
     * @return array
     */
    public function export_for_template(): array {
        $course = $this->get_course();

        return [
            'courseid' => (int)$course->id,
            'mode' => $this->get_uckk_mode(),
            'istronccommun' => $this->is_tronc_commun(),
            'isprogramcourse' => $this->is_program_course(),
            'showcanon' => $this->show_canon(),
            'showevidenceflow' => $this->show_evidence_flow(),
            'showintegritynotice' => $this->show_integrity_notice(),
            'showrecognitionnotice' => $this->show_recognition_notice(),
            'sections' => $this->export_default_sections_for_template(),
        ];
    }

    /**
     * Export default section labels for templates.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function export_default_sections_for_template(): array {
        $sections = [];
        $kinds = self::get_default_section_kinds();
        $keys = self::get_default_section_string_keys();

        foreach ($kinds as $sectionnum => $kind) {
            $sections[] = [
                'sectionnum' => $sectionnum,
                'kind' => $kind,
                'label' => get_string($keys[$sectionnum], 'format_uckk'),
                'requiresproof' => in_array($kind, [self::SECTION_PROOFS, self::SECTION_DELIVERABLE], true),
                'archivable' => in_array($kind, [self::SECTION_PROOFS, self::SECTION_DELIVERABLE, self::SECTION_ARCHIVE], true),
                'integritysensitive' => in_array(
                    $kind,
                    [self::SECTION_PROOFS, self::SECTION_DELIBERATION, self::SECTION_EVALUATION, self::SECTION_ARCHIVE],
                    true
                ),
            ];
        }

        return $sections;
    }

    /**
     * Normalise a section-like input into an object with a section property.
     *
     * @param section_info|stdClass|int $section Section object or number.
     * @return stdClass|section_info
     */
    protected function normalise_section($section) {
        if (is_object($section)) {
            return $section;
        }

        $sectioninfo = $this->get_section((int)$section);

        if ($sectioninfo) {
            return $sectioninfo;
        }

        return (object)[
            'section' => (int)$section,
            'name' => '',
        ];
    }
}