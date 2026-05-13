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
 * Section control menu output for the UCKK course format.
 *
 * This class extends Moodle's section action menu for the UCKK course format.
 * It keeps the native Moodle section controls intact and adds UCKK-specific
 * links when the corresponding UCKK plugins are installed.
 *
 * The class must not perform workflow decisions. It only exposes navigation
 * affordances toward the appropriate UCKK components:
 *
 * - mod_uckkarchive for memory, proof and archive work;
 * - mod_uckkassembly for deliberation and decision work;
 * - mod_uckkchallenge for challenge-oriented work;
 * - tool_uckkintegrity for Inquisiteur review.
 *
 * @package    format_uckk
 * @copyright  2026 Univers-Cité King Klown
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_uckk\output\courseformat\content\section;

use action_menu_link;
use context_course;
use core_component;
use core_courseformat\output\local\content\section\controlmenu as core_section_controlmenu;
use moodle_url;
use pix_icon;

defined('MOODLE_INTERNAL') || die();

/**
 * UCKK section control menu.
 *
 * Moodle detects this class because it mirrors the core course format output
 * path inside the format_uckk namespace:
 *
 * core_courseformat\output\local\content\section\controlmenu
 * → format_uckk\output\courseformat\content\section\controlmenu
 *
 * @package format_uckk
 */
class controlmenu extends core_section_controlmenu {
    /**
     * Add UCKK-specific section controls to Moodle's native section controls.
     *
     * Moodle 5.x expects section menu items to be compatible with action_menu.
     * This method therefore adds action_menu_link instances only.
     *
     * @return array Section control items.
     */
    public function section_control_items() {
        $controls = parent::section_control_items();

        if (!$this->should_add_uckk_controls()) {
            return $controls;
        }

        $archiveitem = $this->get_archive_item();
        if ($archiveitem !== null) {
            $controls = $this->add_control_after(
                $controls,
                'permalink',
                'uckkarchive',
                $archiveitem
            );
        }

        $assemblyitem = $this->get_assembly_item();
        if ($assemblyitem !== null) {
            $controls = $this->add_control_after(
                $controls,
                'uckkarchive',
                'uckkassembly',
                $assemblyitem
            );
        }

        $challengeitem = $this->get_challenge_item();
        if ($challengeitem !== null) {
            $controls = $this->add_control_after(
                $controls,
                'uckkassembly',
                'uckkchallenge',
                $challengeitem
            );
        }

        $integrityitem = $this->get_integrity_item();
        if ($integrityitem !== null) {
            $controls = $this->add_control_after(
                $controls,
                'uckkchallenge',
                'uckkintegrity',
                $integrityitem
            );
        }

        return $controls;
    }

    /**
     * Decide if UCKK controls should be added.
     *
     * This only checks display eligibility. Actual access control must still be
     * enforced by each target component.
     *
     * @return bool
     */
    protected function should_add_uckk_controls(): bool {
        if (empty($this->course) || empty($this->course->id)) {
            return false;
        }

        if (empty($this->section) || empty($this->section->id)) {
            return false;
        }

        if (!$this->is_uckk_course()) {
            return false;
        }

        $context = context_course::instance($this->course->id);

        return has_any_capability(
            [
                'moodle/course:update',
                'moodle/course:manageactivities',
            ],
            $context
        );
    }

    /**
     * Determine whether the current course should receive UCKK controls.
     *
     * @return bool
     */
    protected function is_uckk_course(): bool {
        if (!empty($this->course->format) && $this->course->format === 'uckk') {
            return true;
        }

        if (!empty($this->course->idnumber) && preg_match('/^UCKK-/i', $this->course->idnumber)) {
            return true;
        }

        if (!empty($this->course->shortname) && preg_match('/^UCKK-/i', $this->course->shortname)) {
            return true;
        }

        return false;
    }

    /**
     * Build the Archive menu item.
     *
     * @return action_menu_link|null
     */
    protected function get_archive_item(): ?action_menu_link {
        if (!$this->plugin_exists('mod', 'uckkarchive')) {
            return null;
        }

        $url = new moodle_url('/mod/uckkarchive/index.php', $this->get_common_url_params());

        return $this->make_menu_link(
            $url,
            $this->get_format_string('sectionmenu_archive', 'Archiver la section'),
            'i/files',
            'uckk-section-archive',
            [
                'data-uckk-action' => 'archive-section',
            ]
        );
    }

    /**
     * Build the Assembly menu item.
     *
     * @return action_menu_link|null
     */
    protected function get_assembly_item(): ?action_menu_link {
        if (!$this->plugin_exists('mod', 'uckkassembly')) {
            return null;
        }

        $url = new moodle_url('/mod/uckkassembly/index.php', $this->get_common_url_params());

        return $this->make_menu_link(
            $url,
            $this->get_format_string('sectionmenu_assembly', 'Ouvrir les assemblées'),
            'i/group',
            'uckk-section-assembly',
            [
                'data-uckk-action' => 'open-assemblies',
            ]
        );
    }

    /**
     * Build the Challenge menu item.
     *
     * @return action_menu_link|null
     */
    protected function get_challenge_item(): ?action_menu_link {
        if (!$this->plugin_exists('mod', 'uckkchallenge')) {
            return null;
        }

        $url = new moodle_url('/mod/uckkchallenge/index.php', $this->get_common_url_params());

        return $this->make_menu_link(
            $url,
            $this->get_format_string('sectionmenu_challenge', 'Voir les défis'),
            'i/star',
            'uckk-section-challenge',
            [
                'data-uckk-action' => 'open-challenges',
            ]
        );
    }

    /**
     * Build the Integrity menu item.
     *
     * @return action_menu_link|null
     */
    protected function get_integrity_item(): ?action_menu_link {
        if (!$this->plugin_exists('tool', 'uckkintegrity')) {
            return null;
        }

        $context = context_course::instance($this->course->id);

        if (!has_any_capability(
            [
                'moodle/course:update',
                'moodle/course:manageactivities',
                'tool/uckkintegrity:view',
                'tool/uckkintegrity:opencase',
                'tool/uckkintegrity:reviewcase',
            ],
            $context
        )) {
            return null;
        }

        $url = new moodle_url('/admin/tool/uckkintegrity/index.php', $this->get_common_url_params());

        return $this->make_menu_link(
            $url,
            $this->get_format_string('sectionmenu_integrity', 'Révision d’intégrité'),
            'i/report',
            'uckk-section-integrity',
            [
                'data-uckk-action' => 'integrity-review',
            ]
        );
    }

    /**
     * Create a standard Moodle action menu link.
     *
     * @param moodle_url $url Target URL.
     * @param string $label Link label.
     * @param string $icon Moodle icon key.
     * @param string $extraclass Extra CSS class.
     * @param array $attributes Extra attributes.
     * @return action_menu_link
     */
    protected function make_menu_link(
        moodle_url $url,
        string $label,
        string $icon,
        string $extraclass,
        array $attributes = []
    ): action_menu_link {
        $attributes = array_merge(
            [
                'class' => 'dropdown-item uckk-section-control ' . $extraclass,
                'data-courseid' => (string)$this->course->id,
                'data-sectionid' => (string)$this->section->id,
                'data-sectionnum' => (string)$this->get_section_number(),
                'data-sectiontype' => $this->get_section_type(),
            ],
            $attributes
        );

        return new action_menu_link(
            $url,
            new pix_icon($icon, '', 'moodle'),
            $label,
            false,
            $attributes
        );
    }

    /**
     * Common URL parameters for UCKK section tools.
     *
     * @return array
     */
    protected function get_common_url_params(): array {
        return [
            'id' => $this->course->id,
            'courseid' => $this->course->id,
            'sectionid' => $this->section->id,
            'sectionnum' => $this->get_section_number(),
            'sectiontype' => $this->get_section_type(),
            'returnurl' => $this->get_return_url()->out(false),
        ];
    }

    /**
     * Get section number safely.
     *
     * @return int
     */
    protected function get_section_number(): int {
        if (isset($this->section->section)) {
            return (int)$this->section->section;
        }

        return 0;
    }

    /**
     * Get the canonical UCKK section type.
     *
     * This maps the default UCKK course format section order:
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
     * @return string
     */
    protected function get_section_type(): string {
        $sectionnum = $this->get_section_number();

        $types = [
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

        if (isset($types[$sectionnum])) {
            return $types[$sectionnum];
        }

        return 'extension';
    }

    /**
     * Return to the course page, anchored to the current section when possible.
     *
     * @return moodle_url
     */
    protected function get_return_url(): moodle_url {
        $url = new moodle_url('/course/view.php', ['id' => $this->course->id]);

        $sectionnum = $this->get_section_number();
        if ($sectionnum > 0) {
            $url->set_anchor('section-' . $sectionnum);
        }

        return $url;
    }

    /**
     * Check if a Moodle plugin is installed.
     *
     * @param string $type Plugin type.
     * @param string $name Plugin name.
     * @return bool
     */
    protected function plugin_exists(string $type, string $name): bool {
        return core_component::get_plugin_directory($type, $name) !== null;
    }

    /**
     * Get a format_uckk string with a safe fallback.
     *
     * This keeps the class usable during early installation or development,
     * before all language strings have been generated.
     *
     * @param string $identifier String identifier.
     * @param string $fallback Fallback label.
     * @return string
     */
    protected function get_format_string(string $identifier, string $fallback): string {
        if (get_string_manager()->string_exists($identifier, 'format_uckk')) {
            return get_string($identifier, 'format_uckk');
        }

        return $fallback;
    }
}