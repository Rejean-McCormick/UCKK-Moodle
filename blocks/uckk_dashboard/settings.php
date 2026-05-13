<?php
// This file is part of UCKK-Moodle.
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.

/**
 * Admin settings for the UCKK dashboard block.
 *
 * These settings control display defaults only. They do not grant access,
 * bypass capabilities, validate workflow states, expose restricted records,
 * or make AI / integrity / archive decisions.
 *
 * @package    block_uckk_dashboard
 * @copyright  2026 Univers-Cité King Klown
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {
    $settings->add(new admin_setting_heading(
        'block_uckk_dashboard/displayheading',
        get_string('settingsdisplayheading', 'block_uckk_dashboard'),
        get_string('settingsdisplayheading_desc', 'block_uckk_dashboard')
    ));

    $settings->add(new admin_setting_configselect(
        'block_uckk_dashboard/defaultviewmode',
        get_string('defaultviewmode', 'block_uckk_dashboard'),
        get_string('defaultviewmode_desc', 'block_uckk_dashboard'),
        'auto',
        [
            'auto' => get_string('viewmode_auto', 'block_uckk_dashboard'),
            'player' => get_string('viewmode_player', 'block_uckk_dashboard'),
            'mentor' => get_string('viewmode_mentor', 'block_uckk_dashboard'),
            'archivist' => get_string('viewmode_archivist', 'block_uckk_dashboard'),
            'inquisitor' => get_string('viewmode_inquisitor', 'block_uckk_dashboard'),
            'manager' => get_string('viewmode_manager', 'block_uckk_dashboard'),
        ]
    ));

    $settings->add(new admin_setting_configcheckbox(
        'block_uckk_dashboard/showpathway',
        get_string('showpathway', 'block_uckk_dashboard'),
        get_string('showpathway_desc', 'block_uckk_dashboard'),
        1
    ));

    $settings->add(new admin_setting_configcheckbox(
        'block_uckk_dashboard/showtronccommun',
        get_string('showtronccommun', 'block_uckk_dashboard'),
        get_string('showtronccommun_desc', 'block_uckk_dashboard'),
        1
    ));

    $settings->add(new admin_setting_configcheckbox(
        'block_uckk_dashboard/showcompetencies',
        get_string('showcompetencies', 'block_uckk_dashboard'),
        get_string('showcompetencies_desc', 'block_uckk_dashboard'),
        1
    ));

    $settings->add(new admin_setting_configcheckbox(
        'block_uckk_dashboard/showbadges',
        get_string('showbadges', 'block_uckk_dashboard'),
        get_string('showbadges_desc', 'block_uckk_dashboard'),
        1
    ));

    $settings->add(new admin_setting_configcheckbox(
        'block_uckk_dashboard/showchallenges',
        get_string('showchallenges', 'block_uckk_dashboard'),
        get_string('showchallenges_desc', 'block_uckk_dashboard'),
        1
    ));

    $settings->add(new admin_setting_configcheckbox(
        'block_uckk_dashboard/showassemblies',
        get_string('showassemblies', 'block_uckk_dashboard'),
        get_string('showassemblies_desc', 'block_uckk_dashboard'),
        1
    ));

    $settings->add(new admin_setting_configcheckbox(
        'block_uckk_dashboard/showarchive',
        get_string('showarchive', 'block_uckk_dashboard'),
        get_string('showarchive_desc', 'block_uckk_dashboard'),
        1
    ));

    $settings->add(new admin_setting_configcheckbox(
        'block_uckk_dashboard/showintegrity',
        get_string('showintegrity', 'block_uckk_dashboard'),
        get_string('showintegrity_desc', 'block_uckk_dashboard'),
        1
    ));

    $settings->add(new admin_setting_configcheckbox(
        'block_uckk_dashboard/showdeadlines',
        get_string('showdeadlines', 'block_uckk_dashboard'),
        get_string('showdeadlines_desc', 'block_uckk_dashboard'),
        1
    ));

    $settings->add(new admin_setting_configcheckbox(
        'block_uckk_dashboard/showportfolio',
        get_string('showportfolio', 'block_uckk_dashboard'),
        get_string('showportfolio_desc', 'block_uckk_dashboard'),
        1
    ));

    $settings->add(new admin_setting_heading(
        'block_uckk_dashboard/behaviourheading',
        get_string('settingsbehaviourheading', 'block_uckk_dashboard'),
        get_string('settingsbehaviourheading_desc', 'block_uckk_dashboard')
    ));

    $settings->add(new admin_setting_configselect(
        'block_uckk_dashboard/refreshinterval',
        get_string('refreshinterval', 'block_uckk_dashboard'),
        get_string('refreshinterval_desc', 'block_uckk_dashboard'),
        300,
        [
            0 => get_string('refreshinterval_none', 'block_uckk_dashboard'),
            60 => get_string('refreshinterval_1min', 'block_uckk_dashboard'),
            300 => get_string('refreshinterval_5min', 'block_uckk_dashboard'),
            900 => get_string('refreshinterval_15min', 'block_uckk_dashboard'),
            1800 => get_string('refreshinterval_30min', 'block_uckk_dashboard'),
        ]
    ));

    $settings->add(new admin_setting_configtext(
        'block_uckk_dashboard/maxsummaryitems',
        get_string('maxsummaryitems', 'block_uckk_dashboard'),
        get_string('maxsummaryitems_desc', 'block_uckk_dashboard'),
        5,
        PARAM_INT
    ));

    $settings->add(new admin_setting_configcheckbox(
        'block_uckk_dashboard/showrestrictedindicators',
        get_string('showrestrictedindicators', 'block_uckk_dashboard'),
        get_string('showrestrictedindicators_desc', 'block_uckk_dashboard'),
        1
    ));

    $settings->add(new admin_setting_configcheckbox(
        'block_uckk_dashboard/allowmanualrefresh',
        get_string('allowmanualrefresh', 'block_uckk_dashboard'),
        get_string('allowmanualrefresh_desc', 'block_uckk_dashboard'),
        1
    ));
}