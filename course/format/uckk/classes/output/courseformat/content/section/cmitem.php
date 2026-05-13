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
 * Course module item output for the UCKK course format.
 *
 * This class extends Moodle's core course format cmitem output and adds
 * UCKK-specific display metadata for the activity card template.
 *
 * It must remain presentation-oriented:
 * - no grading decisions;
 * - no integrity decisions;
 * - no archive validation;
 * - no challenge or assembly workflow transitions;
 * - no direct database writes.
 *
 * @package    format_uckk
 * @copyright  2026 Univers-Cité King Klown
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_uckk\output\courseformat\content\section;

use cm_info;
use core\output\renderer_base;
use core_courseformat\output\local\content\section\cmitem as core_cmitem;
use stdClass;

defined('MOODLE_INTERNAL') || die();

/**
 * UCKK course module item output.
 *
 * The class keeps Moodle's core course module rendering data and adds a small,
 * stable UCKK layer that templates can use for visual markers.
 *
 * @package format_uckk
 */
class cmitem extends core_cmitem {
    /**
     * Standard UCKK course section kinds.
     *
     * These match the UCKK course format structure:
     * 0. Orientation
     * 1. Concepts
     * 2. Matière canonique
     * 3. Atelier
     * 4. Preuves
     * 5. Délibération
     * 6. Livrable
     * 7. Évaluation
     * 8. Archive
     */
    private const SECTION_KINDS = [
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
     * UCKK-native activity modules.
     */
    private const UCKK_MODULES = [
        'uckkchallenge',
        'uckkassembly',
        'uckkarchive',
    ];

    /**
     * Modules that normally produce or hold evidence.
     */
    private const PROOF_MODULES = [
        'assign',
        'workshop',
        'data',
        'forum',
        'glossary',
        'wiki',
        'quiz',
        'uckkchallenge',
        'uckkarchive',
    ];

    /**
     * Modules that normally support deliberation.
     */
    private const DELIBERATION_MODULES = [
        'forum',
        'workshop',
        'choice',
        'feedback',
        'survey',
        'uckkassembly',
    ];

    /**
     * Modules that normally support archive or memory.
     */
    private const ARCHIVE_MODULES = [
        'data',
        'glossary',
        'wiki',
        'book',
        'folder',
        'resource',
        'uckkarchive',
    ];

    /**
     * Export data for the UCKK cmitem template.
     *
     * Moodle core keeps the primary activity-card rendering data. This method
     * adds UCKK-specific visual markers so the template can present the activity
     * in relation to proofs, deliberation, archive, integrity and section role.
     *
     * @param renderer_base $output The renderer.
     * @return stdClass Data for Mustache.
     */
    public function export_for_template(renderer_base $output): stdClass {
        $data = parent::export_for_template($output);

        $cm = $this->get_course_module();
        $sectionkind = $this->get_section_kind();
        $modname = $this->get_modname($cm);

        $data->uckk = (object) [
            'enabled' => true,
            'cmid' => $cm->id,
            'module' => $modname,
            'sectionkind' => $sectionkind,
            'sectionlabel' => $this->get_section_label($sectionkind),
            'classes' => $this->get_uckk_classes($cm, $sectionkind),
            'isuckkmodule' => $this->is_uckk_module($modname),
            'ischallenge' => $modname === 'uckkchallenge',
            'isassembly' => $modname === 'uckkassembly',
            'isarchive' => $modname === 'uckkarchive',
            'isproofsource' => $this->is_proof_module($modname, $sectionkind),
            'isdeliberation' => $this->is_deliberation_module($modname, $sectionkind),
            'isarchivesource' => $this->is_archive_module($modname, $sectionkind),
            'integritysensitive' => $this->is_integrity_sensitive($modname, $sectionkind),
            'hasactivityurl' => !empty($cm->url),
            'activityurl' => $cm->url ? $cm->url->out(false) : '',
            'uservisible' => !empty($cm->uservisible),
            'visible' => !empty($cm->visible),
            'stealth' => method_exists($cm, 'is_stealth') ? $cm->is_stealth() : false,
            'showbadge' => true,
            'badge' => $this->get_uckk_badge_data($modname, $sectionkind),
            'attributes' => $this->get_uckk_data_attributes($cm, $sectionkind),
        ];

        return $data;
    }

    /**
     * Return the template used by the UCKK course format for a cm item.
     *
     * @param renderer_base $renderer The renderer.
     * @return string Template name.
     */
    public function get_template_name(renderer_base $renderer): string {
        return 'format_uckk/local/content/section/cmitem';
    }

    /**
     * Get the current course module.
     *
     * The parent core cmitem output stores the course module as a protected
     * property in Moodle 4.x/5.x. This wrapper keeps the rest of this file
     * readable and isolates that dependency in one place.
     *
     * @return cm_info
     */
    private function get_course_module(): cm_info {
        return $this->mod;
    }

    /**
     * Get the module name safely.
     *
     * @param cm_info $cm Course module.
     * @return string Module name.
     */
    private function get_modname(cm_info $cm): string {
        return clean_param($cm->modname ?? '', PARAM_PLUGIN);
    }

    /**
     * Get the UCKK section kind from the section number.
     *
     * @return string Section kind.
     */
    private function get_section_kind(): string {
        $sectionnumber = $this->section->section ?? null;

        if ($sectionnumber === null) {
            return 'standard';
        }

        return self::SECTION_KINDS[(int)$sectionnumber] ?? 'standard';
    }

    /**
     * Get a human label for a UCKK section kind.
     *
     * The method checks plugin language strings first and falls back to readable
     * French labels to avoid hard failures while the language file is evolving.
     *
     * @param string $sectionkind Section kind.
     * @return string Human label.
     */
    private function get_section_label(string $sectionkind): string {
        $key = 'section_' . $sectionkind;

        if (get_string_manager()->string_exists($key, 'format_uckk')) {
            return get_string($key, 'format_uckk');
        }

        $fallbacks = [
            'orientation' => 'Orientation',
            'concepts' => 'Concepts clés',
            'canon' => 'Matière canonique',
            'atelier' => 'Atelier',
            'preuves' => 'Preuves',
            'deliberation' => 'Délibération',
            'livrable' => 'Livrable',
            'evaluation' => 'Évaluation',
            'archive' => 'Archive',
            'standard' => 'Activité',
        ];

        return $fallbacks[$sectionkind] ?? ucfirst(str_replace('_', ' ', $sectionkind));
    }

    /**
     * Get visual UCKK classes for the activity item.
     *
     * @param cm_info $cm Course module.
     * @param string $sectionkind Section kind.
     * @return string CSS classes.
     */
    private function get_uckk_classes(cm_info $cm, string $sectionkind): string {
        $modname = $this->get_modname($cm);

        $classes = [
            'format-uckk-cmitem',
            'format-uckk-cmitem-' . $modname,
            'format-uckk-section-' . $sectionkind,
        ];

        if ($this->is_uckk_module($modname)) {
            $classes[] = 'format-uckk-native-module';
        }

        if ($this->is_proof_module($modname, $sectionkind)) {
            $classes[] = 'format-uckk-proof-source';
        }

        if ($this->is_deliberation_module($modname, $sectionkind)) {
            $classes[] = 'format-uckk-deliberation-source';
        }

        if ($this->is_archive_module($modname, $sectionkind)) {
            $classes[] = 'format-uckk-archive-source';
        }

        if ($this->is_integrity_sensitive($modname, $sectionkind)) {
            $classes[] = 'format-uckk-integrity-sensitive';
        }

        if (empty($cm->visible)) {
            $classes[] = 'format-uckk-hidden';
        }

        if (!empty($cm->uservisible)) {
            $classes[] = 'format-uckk-user-visible';
        } else {
            $classes[] = 'format-uckk-user-not-visible';
        }

        if (method_exists($cm, 'is_stealth') && $cm->is_stealth()) {
            $classes[] = 'format-uckk-stealth';
        }

        return implode(' ', array_unique($classes));
    }

    /**
     * Get UCKK data attributes for templates.
     *
     * These attributes support Moodle's course format component approach by
     * keeping selectors explicit and data-driven.
     *
     * @param cm_info $cm Course module.
     * @param string $sectionkind Section kind.
     * @return array Template-friendly attributes.
     */
    private function get_uckk_data_attributes(cm_info $cm, string $sectionkind): array {
        $modname = $this->get_modname($cm);

        return [
            [
                'name' => 'data-uckk-cmid',
                'value' => (string)$cm->id,
            ],
            [
                'name' => 'data-uckk-module',
                'value' => $modname,
            ],
            [
                'name' => 'data-uckk-section-kind',
                'value' => $sectionkind,
            ],
            [
                'name' => 'data-uckk-proof-source',
                'value' => $this->is_proof_module($modname, $sectionkind) ? '1' : '0',
            ],
            [
                'name' => 'data-uckk-deliberation-source',
                'value' => $this->is_deliberation_module($modname, $sectionkind) ? '1' : '0',
            ],
            [
                'name' => 'data-uckk-archive-source',
                'value' => $this->is_archive_module($modname, $sectionkind) ? '1' : '0',
            ],
            [
                'name' => 'data-uckk-integrity-sensitive',
                'value' => $this->is_integrity_sensitive($modname, $sectionkind) ? '1' : '0',
            ],
        ];
    }

    /**
     * Get badge data for UCKK activity cards.
     *
     * This is not a Moodle badge award. It is a visual marker used by the
     * course-format template.
     *
     * @param string $modname Activity module name.
     * @param string $sectionkind Section kind.
     * @return stdClass Badge data.
     */
    private function get_uckk_badge_data(string $modname, string $sectionkind): stdClass {
        $type = 'standard';
        $label = $this->get_safe_string('activity_standard', 'Activité');

        if ($modname === 'uckkchallenge') {
            $type = 'challenge';
            $label = $this->get_safe_string('activity_challenge', 'Défi');
        } else if ($modname === 'uckkassembly') {
            $type = 'assembly';
            $label = $this->get_safe_string('activity_assembly', 'Assemblée');
        } else if ($modname === 'uckkarchive') {
            $type = 'archive';
            $label = $this->get_safe_string('activity_archive', 'Archive');
        } else if ($sectionkind === 'preuves' || $this->is_proof_module($modname, $sectionkind)) {
            $type = 'proof';
            $label = $this->get_safe_string('activity_proof', 'Preuve');
        } else if ($sectionkind === 'deliberation' || $this->is_deliberation_module($modname, $sectionkind)) {
            $type = 'deliberation';
            $label = $this->get_safe_string('activity_deliberation', 'Délibération');
        } else if ($sectionkind === 'archive' || $this->is_archive_module($modname, $sectionkind)) {
            $type = 'archive';
            $label = $this->get_safe_string('activity_memory', 'Mémoire');
        } else if ($sectionkind === 'evaluation') {
            $type = 'evaluation';
            $label = $this->get_safe_string('activity_evaluation', 'Évaluation');
        } else if ($sectionkind === 'atelier') {
            $type = 'workshop';
            $label = $this->get_safe_string('activity_workshop', 'Atelier');
        } else if ($sectionkind === 'canon') {
            $type = 'canon';
            $label = $this->get_safe_string('activity_canon', 'Canon');
        }

        return (object) [
            'type' => $type,
            'label' => $label,
            'classes' => 'format-uckk-activity-badge format-uckk-activity-badge-' . $type,
        ];
    }

    /**
     * Check whether a module is native to UCKK.
     *
     * @param string $modname Activity module name.
     * @return bool
     */
    private function is_uckk_module(string $modname): bool {
        return in_array($modname, self::UCKK_MODULES, true);
    }

    /**
     * Check whether an activity should be visually marked as a proof source.
     *
     * @param string $modname Activity module name.
     * @param string $sectionkind Section kind.
     * @return bool
     */
    private function is_proof_module(string $modname, string $sectionkind): bool {
        if ($sectionkind === 'preuves' || $sectionkind === 'livrable') {
            return true;
        }

        return in_array($modname, self::PROOF_MODULES, true);
    }

    /**
     * Check whether an activity should be visually marked as deliberation.
     *
     * @param string $modname Activity module name.
     * @param string $sectionkind Section kind.
     * @return bool
     */
    private function is_deliberation_module(string $modname, string $sectionkind): bool {
        if ($sectionkind === 'deliberation') {
            return true;
        }

        return in_array($modname, self::DELIBERATION_MODULES, true);
    }

    /**
     * Check whether an activity should be visually marked as archive/memory.
     *
     * @param string $modname Activity module name.
     * @param string $sectionkind Section kind.
     * @return bool
     */
    private function is_archive_module(string $modname, string $sectionkind): bool {
        if ($sectionkind === 'archive') {
            return true;
        }

        return in_array($modname, self::ARCHIVE_MODULES, true);
    }

    /**
     * Check whether the activity is integrity-sensitive.
     *
     * This is only a visual marker. Integrity decisions are handled by the
     * dedicated integrity tool, not by this output class.
     *
     * @param string $modname Activity module name.
     * @param string $sectionkind Section kind.
     * @return bool
     */
    private function is_integrity_sensitive(string $modname, string $sectionkind): bool {
        if (in_array($sectionkind, ['preuves', 'deliberation', 'livrable', 'evaluation', 'archive'], true)) {
            return true;
        }

        return in_array($modname, ['assign', 'workshop', 'quiz', 'forum', 'uckkchallenge', 'uckkassembly', 'uckkarchive'], true);
    }

    /**
     * Get a string from format_uckk safely.
     *
     * @param string $key Language string key.
     * @param string $fallback Fallback label.
     * @return string
     */
    private function get_safe_string(string $key, string $fallback): string {
        if (get_string_manager()->string_exists($key, 'format_uckk')) {
            return get_string($key, 'format_uckk');
        }

        return $fallback;
    }
}