<?php
// This file is part of UCKK-Moodle.
//
// UCKK-Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

defined('MOODLE_INTERNAL') || die();

/**
 * Instance configuration form for the UCKK dashboard block.
 *
 * These settings only control presentation of this block instance. They do not
 * grant permissions, calculate progress, validate evidence, award badges, or
 * change canonical UCKK records.
 *
 * @package    block_uckk_dashboard
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_uckk_dashboard_edit_form extends block_edit_form {

    /**
     * Add block-specific configuration fields.
     *
     * Moodle stores fields named config_* in the block instance config object,
     * without the config_ prefix.
     *
     * @param MoodleQuickForm $mform
     * @return void
     */
    protected function specific_definition($mform): void {
        $mform->addElement('header', 'configheader', get_string('blocksettings', 'block'));

        $mform->addElement(
            'text',
            'config_title',
            get_string('customtitle', 'block_uckk_dashboard'),
            ['maxlength' => 255]
        );
        $mform->setType('config_title', PARAM_TEXT);
        $mform->addHelpButton('config_title', 'customtitle', 'block_uckk_dashboard');

        $variantoptions = [
            'auto' => get_string('variant_auto', 'block_uckk_dashboard'),
            'player' => get_string('variant_player', 'block_uckk_dashboard'),
            'mentor' => get_string('variant_mentor', 'block_uckk_dashboard'),
            'archivist' => get_string('variant_archivist', 'block_uckk_dashboard'),
            'inquisitor' => get_string('variant_inquisitor', 'block_uckk_dashboard'),
            'manager' => get_string('variant_manager', 'block_uckk_dashboard'),
        ];

        $mform->addElement(
            'select',
            'config_variant',
            get_string('dashboardvariant', 'block_uckk_dashboard'),
            $variantoptions
        );
        $mform->setDefault('config_variant', 'auto');
        $mform->setType('config_variant', PARAM_ALPHA);
        $mform->addHelpButton('config_variant', 'dashboardvariant', 'block_uckk_dashboard');

        $mform->addElement('header', 'configcards', get_string('dashboardcards', 'block_uckk_dashboard'));

        $this->add_checkbox(
            $mform,
            'config_showprogress',
            'showprogress',
            true
        );

        $this->add_checkbox(
            $mform,
            'config_showchallenges',
            'showchallenges',
            true
        );

        $this->add_checkbox(
            $mform,
            'config_showassemblies',
            'showassemblies',
            true
        );

        $this->add_checkbox(
            $mform,
            'config_showarchive',
            'showarchive',
            true
        );

        $this->add_checkbox(
            $mform,
            'config_showbadges',
            'showbadges',
            true
        );

        $this->add_checkbox(
            $mform,
            'config_showintegrity',
            'showintegrity',
            true
        );

        $this->add_checkbox(
            $mform,
            'config_showdeadlines',
            'showdeadlines',
            true
        );

        $this->add_checkbox(
            $mform,
            'config_showportfolio',
            'showportfolio',
            true
        );

        $mform->addElement('header', 'configdisplay', get_string('displayoptions', 'block_uckk_dashboard'));

        $densityoptions = [
            'standard' => get_string('density_standard', 'block_uckk_dashboard'),
            'compact' => get_string('density_compact', 'block_uckk_dashboard'),
        ];

        $mform->addElement(
            'select',
            'config_density',
            get_string('density', 'block_uckk_dashboard'),
            $densityoptions
        );
        $mform->setDefault('config_density', 'standard');
        $mform->setType('config_density', PARAM_ALPHA);

        $this->add_checkbox(
            $mform,
            'config_showfooterlink',
            'showfooterlink',
            false
        );

        $this->add_checkbox(
            $mform,
            'config_refreshenabled',
            'refreshenabled',
            true
        );

        $mform->addElement(
            'duration',
            'config_refreshrate',
            get_string('refreshrate', 'block_uckk_dashboard'),
            ['optional' => false, 'defaultunit' => MINSECS]
        );
        $mform->setDefault('config_refreshrate', 300);
        $mform->setType('config_refreshrate', PARAM_INT);
        $mform->hideIf('config_refreshrate', 'config_refreshenabled', 'notchecked');

        $mform->addElement(
            'static',
            'uckk_dashboard_scope_notice',
            '',
            get_string('instancescopenotice', 'block_uckk_dashboard')
        );
    }

    /**
     * Add a standard advanced checkbox.
     *
     * @param MoodleQuickForm $mform
     * @param string $name
     * @param string $stringkey
     * @param bool $default
     * @return void
     */
    private function add_checkbox(
        MoodleQuickForm $mform,
        string $name,
        string $stringkey,
        bool $default
    ): void {
        $mform->addElement(
            'advcheckbox',
            $name,
            get_string($stringkey, 'block_uckk_dashboard'),
            '',
            null,
            [0, 1]
        );
        $mform->setDefault($name, $default ? 1 : 0);
        $mform->setType($name, PARAM_BOOL);
    }
}