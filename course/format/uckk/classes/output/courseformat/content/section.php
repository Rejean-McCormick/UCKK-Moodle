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
 * Section output class for the UCKK course format.
 *
 * This class enriches Moodle's standard course section output with
 * UCKK-specific display metadata:
 *
 * - canonical section role;
 * - stable section key;
 * - section label;
 * - section CSS class;
 * - proof / archive / deliberation markers;
 * - optional integrity and AI guidance.
 *
 * It must remain presentation-oriented. It must not decide permissions,
 * completion, grading, integrity outcomes, challenge validation, archive
 * validation or assembly decisions.
 *
 * @package    format_uckk
 * @copyright  2026 Univers-Cité King Klown
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_uckk\output\courseformat\content;

use renderer_base;
use stdClass;

defined('MOODLE_INTERNAL') || die();

/**
 * UCKK section output.
 *
 * Moodle's course format subsystem detects this class because it mirrors the
 * core output path:
 *
 * core_courseformat\output\local\content\section
 * → format_uckk\output\courseformat\content\section
 *
 * The class delegates the core section structure to Moodle, then adds UCKK
 * metadata for the format_uckk/local/content/section template.
 */
class section extends \core_courseformat\output\local\content\section {
    /**
     * Standard UCKK course section map.
     *
     * Section 0 is kept as orientation because Moodle courses commonly use
     * section zero as the general / top section.
     */
    private const SECTION_MAP = [
        0 => [
            'key' => 'orientation',
            'label' => 'Orientation',
            'shortlabel' => 'Orientation',
            'role' => 'Entrée dans le cours',
            'tagline' => 'Situer le cours, ses règles, ses objectifs et son lien avec le Grand Jeu.',
            'cssclass' => 'uckk-section-orientation',
            'icon' => 'orientation',
            'requiresproof' => false,
            'requiresdeliberation' => false,
            'requiresarchive' => false,
            'showintegritynotice' => true,
            'showainotice' => false,
        ],
        1 => [
            'key' => 'concepts',
            'label' => 'Concepts clés',
            'shortlabel' => 'Concepts',
            'role' => 'Langage commun',
            'tagline' => 'Nommer les concepts nécessaires avant de lire le système.',
            'cssclass' => 'uckk-section-concepts',
            'icon' => 'concepts',
            'requiresproof' => false,
            'requiresdeliberation' => false,
            'requiresarchive' => false,
            'showintegritynotice' => false,
            'showainotice' => false,
        ],
        2 => [
            'key' => 'canon',
            'label' => 'Matière canonique',
            'shortlabel' => 'Canon',
            'role' => 'Source pédagogique',
            'tagline' => 'Lire la matière de référence, distinguer faits, hypothèses, récits et décisions.',
            'cssclass' => 'uckk-section-canon',
            'icon' => 'canon',
            'requiresproof' => false,
            'requiresdeliberation' => false,
            'requiresarchive' => true,
            'showintegritynotice' => false,
            'showainotice' => false,
        ],
        3 => [
            'key' => 'atelier',
            'label' => 'Atelier',
            'shortlabel' => 'Atelier',
            'role' => 'Mise en pratique',
            'tagline' => 'Transformer la matière en gestes, cartes, prototypes, analyses ou expérimentations.',
            'cssclass' => 'uckk-section-atelier',
            'icon' => 'atelier',
            'requiresproof' => true,
            'requiresdeliberation' => false,
            'requiresarchive' => false,
            'showintegritynotice' => false,
            'showainotice' => false,
        ],
        4 => [
            'key' => 'preuves',
            'label' => 'Preuves',
            'shortlabel' => 'Preuves',
            'role' => 'Production vérifiable',
            'tagline' => 'Déposer des traces, artefacts, observations et arguments pouvant être vérifiés.',
            'cssclass' => 'uckk-section-preuves',
            'icon' => 'proof',
            'requiresproof' => true,
            'requiresdeliberation' => false,
            'requiresarchive' => true,
            'showintegritynotice' => true,
            'showainotice' => true,
        ],
        5 => [
            'key' => 'deliberation',
            'label' => 'Délibération',
            'shortlabel' => 'Délibération',
            'role' => 'Lecture collective',
            'tagline' => 'Mettre les preuves et interprétations à l’épreuve du collectif.',
            'cssclass' => 'uckk-section-deliberation',
            'icon' => 'assembly',
            'requiresproof' => false,
            'requiresdeliberation' => true,
            'requiresarchive' => true,
            'showintegritynotice' => true,
            'showainotice' => true,
        ],
        6 => [
            'key' => 'livrable',
            'label' => 'Livrable',
            'shortlabel' => 'Livrable',
            'role' => 'Artefact final',
            'tagline' => 'Produire un résultat final clair, utile, contestable et transmissible.',
            'cssclass' => 'uckk-section-livrable',
            'icon' => 'deliverable',
            'requiresproof' => true,
            'requiresdeliberation' => false,
            'requiresarchive' => true,
            'showintegritynotice' => true,
            'showainotice' => true,
        ],
        7 => [
            'key' => 'evaluation',
            'label' => 'Évaluation',
            'shortlabel' => 'Évaluation',
            'role' => 'Rétroaction',
            'tagline' => 'Évaluer la qualité des preuves, de la méthode, de l’action et de la réflexion.',
            'cssclass' => 'uckk-section-evaluation',
            'icon' => 'evaluation',
            'requiresproof' => true,
            'requiresdeliberation' => false,
            'requiresarchive' => false,
            'showintegritynotice' => true,
            'showainotice' => false,
        ],
        8 => [
            'key' => 'archive',
            'label' => 'Archive',
            'shortlabel' => 'Archive',
            'role' => 'Mémoire du cours',
            'tagline' => 'Préserver les apprentissages, décisions, preuves et artefacts réutilisables.',
            'cssclass' => 'uckk-section-archive',
            'icon' => 'archive',
            'requiresproof' => false,
            'requiresdeliberation' => false,
            'requiresarchive' => true,
            'showintegritynotice' => true,
            'showainotice' => false,
        ],
    ];

    /**
     * Return the template used to render this section.
     *
     * @param renderer_base $renderer The renderer.
     * @return string Template name.
     */
    public function get_template_name(renderer_base $renderer): string {
        return 'format_uckk/local/content/section';
    }

    /**
     * Export section data for Mustache.
     *
     * The parent class provides the standard Moodle course-format data.
     * This method only appends UCKK display metadata.
     *
     * @param renderer_base $output The renderer.
     * @return stdClass Template data.
     */
    public function export_for_template(renderer_base $output): stdClass {
        $data = parent::export_for_template($output);

        $sectionnum = $this->get_section_number_from_export($data);
        $definition = $this->get_uckk_section_definition($sectionnum);

        $data->uckk = (object)[
            'sectionnum' => $sectionnum,
            'sectionkey' => $definition['key'],
            'sectionlabel' => $definition['label'],
            'sectionshortlabel' => $definition['shortlabel'],
            'sectionrole' => $definition['role'],
            'sectiontagline' => $definition['tagline'],
            'sectioncssclass' => $definition['cssclass'],
            'sectionicon' => $definition['icon'],

            'requiresproof' => $definition['requiresproof'],
            'requiresdeliberation' => $definition['requiresdeliberation'],
            'requiresarchive' => $definition['requiresarchive'],

            'showintegritynotice' => $definition['showintegritynotice'],
            'showainotice' => $definition['showainotice'],

            'isorientation' => $definition['key'] === 'orientation',
            'isconcepts' => $definition['key'] === 'concepts',
            'iscanon' => $definition['key'] === 'canon',
            'isatelier' => $definition['key'] === 'atelier',
            'isproofs' => $definition['key'] === 'preuves',
            'isdeliberation' => $definition['key'] === 'deliberation',
            'isdeliverable' => $definition['key'] === 'livrable',
            'isevaluation' => $definition['key'] === 'evaluation',
            'isarchive' => $definition['key'] === 'archive',

            'integritylabel' => $this->get_string_or_default(
                'integrity_guardrail',
                'theme_uckk',
                'Garde-fou éthique et méthodologique'
            ),
            'integritynotice' => $this->get_string_or_default(
                'integrity_notice',
                'theme_uckk',
                'Toute preuve, décision ou mise en scène peut être vérifiée si l’intégrité du jeu est en cause.'
            ),
            'aiwarning' => $this->get_string_or_default(
                'ai_warning',
                'theme_uckk',
                'Brouillon assisté par IA. Ce contenu n’est pas une autorité finale. Les faits, preuves et décisions doivent être validés avant usage.'
            ),
            'archiveword' => $this->get_string_or_default('archive', 'theme_uckk', 'Archive'),
            'proofword' => $this->get_string_or_default('proofs', 'theme_uckk', 'Preuves'),
            'deliberationword' => $this->get_string_or_default(
                'course_deliberation',
                'theme_uckk',
                'Délibération'
            ),
        ];

        // Convenience top-level keys for templates that do not want nested access.
        $data->uckksectionnum = $data->uckk->sectionnum;
        $data->uckksectionkey = $data->uckk->sectionkey;
        $data->uckksectionlabel = $data->uckk->sectionlabel;
        $data->uckksectionshortlabel = $data->uckk->sectionshortlabel;
        $data->uckksectionrole = $data->uckk->sectionrole;
        $data->uckksectiontagline = $data->uckk->sectiontagline;
        $data->uckksectioncssclass = $data->uckk->sectioncssclass;
        $data->uckksectionicon = $data->uckk->sectionicon;

        $data->uckkrequiresproof = $data->uckk->requiresproof;
        $data->uckkrequiresdeliberation = $data->uckk->requiresdeliberation;
        $data->uckkrequiresarchive = $data->uckk->requiresarchive;
        $data->uckkshowintegritynotice = $data->uckk->showintegritynotice;
        $data->uckkshowainotice = $data->uckk->showainotice;

        $data->uckksectionclasses = trim(
            'uckk-section ' .
            $data->uckk->sectioncssclass . ' ' .
            'uckk-section-' . $data->uckk->sectionkey . ' ' .
            'uckk-section-number-' . $sectionnum
        );

        return $data;
    }

    /**
     * Extract a section number from the parent exported data.
     *
     * The core output data can vary slightly between Moodle versions and
     * templates. This method checks common exported field names without
     * depending on private parent internals.
     *
     * @param stdClass $data Parent exported data.
     * @return int Section number.
     */
    private function get_section_number_from_export(stdClass $data): int {
        $candidates = [
            'num',
            'section',
            'sectionnum',
            'number',
        ];

        foreach ($candidates as $candidate) {
            if (isset($data->{$candidate}) && is_numeric($data->{$candidate})) {
                return max(0, (int)$data->{$candidate});
            }
        }

        if (isset($data->sectioninfo) && is_object($data->sectioninfo)) {
            foreach ($candidates as $candidate) {
                if (isset($data->sectioninfo->{$candidate}) && is_numeric($data->sectioninfo->{$candidate})) {
                    return max(0, (int)$data->sectioninfo->{$candidate});
                }
            }
        }

        return 0;
    }

    /**
     * Get the UCKK section definition.
     *
     * If a course has more than the standard nine sections, the extra
     * sections are treated as advanced / laboratory sections while preserving
     * Moodle's normal section behaviour.
     *
     * @param int $sectionnum Section number.
     * @return array Section definition.
     */
    private function get_uckk_section_definition(int $sectionnum): array {
        if (array_key_exists($sectionnum, self::SECTION_MAP)) {
            return self::SECTION_MAP[$sectionnum];
        }

        return [
            'key' => 'advanced',
            'label' => 'Section avancée',
            'shortlabel' => 'Avancé',
            'role' => 'Approfondissement',
            'tagline' => 'Approfondir, prototyper, documenter ou relier ce cours à un défi, une assemblée ou une archive.',
            'cssclass' => 'uckk-section-advanced',
            'icon' => 'advanced',
            'requiresproof' => false,
            'requiresdeliberation' => false,
            'requiresarchive' => true,
            'showintegritynotice' => false,
            'showainotice' => false,
        ];
    }

    /**
     * Return a Moodle language string if it exists, otherwise use a default.
     *
     * This keeps the output class safe while theme_uckk and format_uckk language
     * files are being generated in parallel.
     *
     * @param string $identifier String identifier.
     * @param string $component Moodle component.
     * @param string $default Fallback label.
     * @return string
     */
    private function get_string_or_default(string $identifier, string $component, string $default): string {
        if (get_string_manager()->string_exists($identifier, $component)) {
            return get_string($identifier, $component);
        }

        return $default;
    }
}