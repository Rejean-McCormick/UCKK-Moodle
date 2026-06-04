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
 * Course content output class for the UCKK course format.
 *
 * This class redirects Moodle's course content rendering to the UCKK
 * course content template and adds UCKK-specific display metadata.
 *
 * It must remain presentation-oriented:
 * - no grading logic;
 * - no enrolment logic;
 * - no integrity decision logic;
 * - no registry validation logic;
 * - no challenge or assembly workflow logic.
 *
 * @package    format_uckk
 * @copyright  2026 Univers-Cité King Klown
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace format_uckk\output\courseformat;

use context_course;
use core\output\renderer_base;
use core_courseformat\output\local\content as core_content;
use stdClass;

defined('MOODLE_INTERNAL') || die();

/**
 * UCKK course content output.
 *
 * Moodle's course format subsystem renders the full course through output
 * classes and Mustache templates. This class extends the core content output
 * class and redirects rendering to the UCKK template:
 *
 *     format_uckk/local/content
 *
 * The template must include the core course format template and override the
 * section block so Moodle can still refresh sections and activities correctly.
 */
class content extends core_content {
    /**
     * Standard UCKK course section map.
     *
     * Section 0 is intentionally included because Moodle commonly uses section 0
     * as the general course section.
     */
    private const STANDARD_SECTIONS = [
        0 => 'orientation',
        1 => 'concepts',
        2 => 'canon',
        3 => 'atelier',
        4 => 'preuves',
        5 => 'deliberation',
        6 => 'livrable',
        7 => 'evaluation',
        8 => 'archive',
    ];

    /**
     * Return the Mustache template used to render the full course content.
     *
     * @param renderer_base $renderer The renderer.
     * @return string Template name.
     */
    public function get_template_name(renderer_base $renderer): string {
        return 'format_uckk/local/content';
    }

    /**
     * Export course content data for the UCKK template.
     *
     * This method keeps Moodle's core course format data intact by starting from
     * the parent export and adding UCKK-specific metadata under safe names.
     *
     * @param renderer_base $output The renderer.
     * @return stdClass Template data.
     */
    public function export_for_template(renderer_base $output): stdClass {
        $data = parent::export_for_template($output);

        if (is_array($data)) {
            $data = (object) $data;
        }

        if (!$data instanceof stdClass) {
            $data = new stdClass();
        }

        $course = $this->format->get_course();
        $coursecontext = context_course::instance((int) $course->id);

        $rawidnumber = trim((string) ($course->idnumber ?? ''));
        $rawshortname = trim((string) ($course->shortname ?? ''));

        $courseidnumber = clean_param($rawidnumber, PARAM_TEXT);

        $courseshortname = format_string(
            $rawshortname,
            true,
            [
                'context' => $coursecontext,
                'escape' => false,
            ]
        );

        $coursefullname = format_string(
            (string) ($course->fullname ?? ''),
            true,
            [
                'context' => $coursecontext,
                'escape' => false,
            ]
        );

        $coursecode = $this->normalise_course_code($rawidnumber, $rawshortname);
        $isuckkcourse = $this->is_uckk_course($coursecode);
        $istronccommun = $this->is_tronc_commun_course($coursecode);
        $coursekind = $this->get_course_kind($coursecode);

        $data->uckk = (object) [
            'component' => 'format_uckk',
            'productname' => 'UCKK-Moodle',
            'fullname' => $this->safe_get_string(
                'uckkfullname',
                'theme_uckk',
                'Univers-Cité King Klown'
            ),
            'campus' => $this->safe_get_string(
                'uckkcampus',
                'theme_uckk',
                'Campus UCKK'
            ),
            'tagline' => $this->safe_get_string(
                'uckktagline',
                'theme_uckk',
                'Comprendre le jeu. Jouer avec lucidité. Changer les règles.'
            ),
            'cycle' => $this->safe_get_string(
                'formula_koa_cycle',
                'theme_uckk',
                'Connaître → Choisir → Agir → Se souvenir'
            ),
            'formula' => $this->safe_get_string(
                'formula_governance',
                'theme_uckk',
                'King Klown attire. L’Inquisiteur vérifie. Les Assemblées légitiment. Les Bâtisseurs réalisent. Le Registraire se souvient.'
            ),
            'boundarynotice' => $this->safe_get_string(
                'footer_canonicalwarning',
                'theme_uckk',
                'UCKK-Moodle est le campus pédagogique de l’UCKK, non la totalité du mouvement kOA.'
            ),
        ];

        $data->uckkcourse = (object) [
            'courseid' => (int) $course->id,
            'fullname' => $coursefullname,
            'shortname' => $courseshortname,
            'idnumber' => $courseidnumber,
            'code' => $coursecode,
            'format' => clean_param((string) ($course->format ?? 'uckk'), PARAM_ALPHANUMEXT),
            'isuckkcourse' => $isuckkcourse,
            'istronccommun' => $istronccommun,
            'isprogramcourse' => $isuckkcourse && !$istronccommun,
            'kind' => $coursekind,
            'kindlabel' => $this->get_course_kind_label($coursekind),
            'cssclass' => $this->get_course_css_class($coursekind),
        ];

        $data->uckkstandardsections = $this->get_standard_sections_for_template();
        $data->hasuckkstandardsections = !empty($data->uckkstandardsections);

        $data->uckknotices = (object) [
            'showboundarynotice' => true,
            'boundarynotice' => $data->uckk->boundarynotice,
            'showintegritynotice' => true,
            'integritynotice' => $this->safe_get_string(
                'integrity_notice',
                'theme_uckk',
                'Toute preuve, décision ou mise en scène peut être vérifiée si l’intégrité du jeu est en cause.'
            ),
            'showainotice' => true,
            'ainotice' => $this->safe_get_string(
                'ai_warning',
                'theme_uckk',
                'Brouillon assisté par IA. Ce contenu n’est pas une autorité finale. Les faits, preuves et décisions doivent être validés avant usage.'
            ),
        ];

        $data->uckkcapabilities = (object) [
            'canmanagecourse' => has_capability('moodle/course:update', $coursecontext),
            'canmanageactivities' => has_capability('moodle/course:manageactivities', $coursecontext),
            'canviewhiddenactivities' => has_capability('moodle/course:viewhiddenactivities', $coursecontext),
        ];

        $data->uckkclasses = implode(' ', array_filter([
            'format-uckk-content',
            $data->uckkcourse->cssclass,
            $istronccommun ? 'format-uckk-tronc-commun' : '',
            $isuckkcourse ? 'format-uckk-canonical-course' : '',
        ]));

        return $data;
    }

    /**
     * Normalise the course code used for UCKK detection.
     *
     * @param string $idnumber Course idnumber.
     * @param string $shortname Course shortname.
     * @return string Normalised course code.
     */
    protected function normalise_course_code(string $idnumber, string $shortname): string {
        $code = trim($idnumber !== '' ? $idnumber : $shortname);
        $code = clean_param($code, PARAM_TEXT);

        return strtoupper($code);
    }

    /**
     * Determine whether the course is an UCKK course.
     *
     * @param string $coursecode Normalised course code.
     * @return bool
     */
    protected function is_uckk_course(string $coursecode): bool {
        return preg_match('/^UCKK-/i', $coursecode) === 1;
    }

    /**
     * Determine whether the course belongs to the UCKK tronc commun.
     *
     * @param string $coursecode Normalised course code.
     * @return bool
     */
    protected function is_tronc_commun_course(string $coursecode): bool {
        return preg_match('/^UCKK-TC/i', $coursecode) === 1;
    }

    /**
     * Return the UCKK course kind.
     *
     * @param string $coursecode Normalised course code.
     * @return string
     */
    protected function get_course_kind(string $coursecode): string {
        if (preg_match('/^UCKK-TC/', $coursecode)) {
            return 'tronccommun';
        }

        if (preg_match('/^UCKK-GJS/', $coursecode)) {
            return 'grandjeusocial';
        }

        if (preg_match('/^UCKK-KOA|^UCKK-DIGITAL/', $coursecode)) {
            return 'koadigital';
        }

        if (preg_match('/^UCKK-AS/', $coursecode)) {
            return 'architecture';
        }

        if (preg_match('/^UCKK-SP/', $coursecode)) {
            return 'sociopolitique';
        }

        if (preg_match('/^UCKK-ECO/', $coursecode)) {
            return 'ecologie';
        }

        if (preg_match('/^UCKK-EC/', $coursecode)) {
            return 'educationcertification';
        }

        if (preg_match('/^UCKK-ME/', $coursecode)) {
            return 'metaphysique';
        }

        if (preg_match('/^UCKK-IA|^UCKK-AI/', $coursecode)) {
            return 'iagouvernable';
        }

        if (preg_match('/^UCKK-LI/', $coursecode)) {
            return 'linguistique';
        }

        if (preg_match('/^UCKK-IS/', $coursecode)) {
            return 'intervention';
        }

        if (preg_match('/^UCKK-MV/', $coursecode)) {
            return 'mediasvivants';
        }

        if (preg_match('/^UCKK-/', $coursecode)) {
            return 'program';
        }

        return 'standard';
    }

    /**
     * Return the human label for a UCKK course kind.
     *
     * @param string $kind Course kind.
     * @return string
     */
    protected function get_course_kind_label(string $kind): string {
        $labels = [
            'tronccommun' => $this->safe_get_string(
                'tronccommun',
                'theme_uckk',
                'Tronc commun'
            ),
            'grandjeusocial' => $this->safe_get_string(
                'program_gjs',
                'theme_uckk',
                'Grand Jeu social'
            ),
            'koadigital' => $this->safe_get_string(
                'program_koa_digital',
                'theme_uckk',
                'kOA Digital Ecosystem'
            ),
            'architecture' => $this->safe_get_string(
                'program_sociotech',
                'theme_uckk',
                'Architecture sociotechnique'
            ),
            'sociopolitique' => $this->safe_get_string(
                'program_sociopolitical',
                'theme_uckk',
                'Systèmes sociaux et politiques'
            ),
            'educationcertification' => $this->safe_get_string(
                'program_education_certification',
                'theme_uckk',
                'Éducation ouverte et certification'
            ),
            'ecologie' => $this->safe_get_string(
                'program_ecology',
                'theme_uckk',
                'Écologie'
            ),
            'metaphysique' => $this->safe_get_string(
                'program_metaphysics',
                'theme_uckk',
                'Métaphysique'
            ),
            'iagouvernable' => $this->safe_get_string(
                'program_ai',
                'theme_uckk',
                'IA gouvernable'
            ),
            'linguistique' => $this->safe_get_string(
                'program_linguistics',
                'theme_uckk',
                'Linguistique et architecture du sens'
            ),
            'intervention' => $this->safe_get_string(
                'program_socialintervention',
                'theme_uckk',
                'Intervention sociale'
            ),
            'mediasvivants' => $this->safe_get_string(
                'program_livingmedia',
                'theme_uckk',
                'Médias vivants'
            ),
            'program' => $this->safe_get_string(
                'programs',
                'theme_uckk',
                'Programme UCKK'
            ),
            'standard' => $this->safe_get_string(
                'course',
                'moodle',
                'Cours'
            ),
        ];

        return $labels[$kind] ?? $labels['standard'];
    }

    /**
     * Return the CSS class for a course kind.
     *
     * @param string $kind Course kind.
     * @return string
     */
    protected function get_course_css_class(string $kind): string {
        $kind = strtolower($kind);
        $kind = preg_replace('/[^a-z0-9_-]+/', '-', $kind);
        $kind = trim((string) $kind, '-_');

        if ($kind === '') {
            $kind = 'standard';
        }

        return 'format-uckk-course-kind-' . $kind;
    }

    /**
     * Build the standard UCKK section map for Mustache templates.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function get_standard_sections_for_template(): array {
        $sections = [];

        foreach (self::STANDARD_SECTIONS as $sectionnum => $key) {
            $sections[] = [
                'number' => $sectionnum,
                'key' => $key,
                'label' => $this->get_section_label($key),
                'cssclass' => 'format-uckk-section-' . $key,
            ];
        }

        return $sections;
    }

    /**
     * Return the label for a standard UCKK section key.
     *
     * @param string $key Section key.
     * @return string
     */
    protected function get_section_label(string $key): string {
        $labels = [
            'orientation' => $this->safe_get_string(
                'course_orientation',
                'theme_uckk',
                'Orientation'
            ),
            'concepts' => $this->safe_get_string(
                'course_concepts',
                'theme_uckk',
                'Concepts clés'
            ),
            'canon' => $this->safe_get_string(
                'course_canon',
                'theme_uckk',
                'Matière canonique'
            ),
            'atelier' => $this->safe_get_string(
                'course_workshop',
                'theme_uckk',
                'Atelier'
            ),
            'preuves' => $this->safe_get_string(
                'course_proofs',
                'theme_uckk',
                'Preuves'
            ),
            'deliberation' => $this->safe_get_string(
                'course_deliberation',
                'theme_uckk',
                'Délibération'
            ),
            'livrable' => $this->safe_get_string(
                'course_deliverable',
                'theme_uckk',
                'Livrable'
            ),
            'evaluation' => $this->safe_get_string(
                'course_evaluation',
                'theme_uckk',
                'Évaluation'
            ),
            'archive' => $this->safe_get_string(
                'course_archive',
                'theme_uckk',
                'Registraire'
            ),
        ];

        return $labels[$key] ?? ucfirst($key);
    }

    /**
     * Safely get a language string with fallback.
     *
     * This keeps the output class usable while the UCKK language packs are being
     * built one file at a time.
     *
     * @param string $identifier String identifier.
     * @param string $component Component name.
     * @param string $fallback Fallback string.
     * @return string
     */
    protected function safe_get_string(string $identifier, string $component, string $fallback): string {
        if (get_string_manager()->string_exists($identifier, $component)) {
            return get_string($identifier, $component);
        }

        return $fallback;
    }
}