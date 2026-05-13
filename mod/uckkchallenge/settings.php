<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

defined('MOODLE_INTERNAL') || die();

/**
 * Admin settings for the UCKK Challenge activity module.
 *
 * @package    mod_uckkchallenge
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

if ($ADMIN->fulltree) {
    $settings->add(new admin_setting_heading(
        'mod_uckkchallenge/generalsettings',
        get_string('settings:general', 'mod_uckkchallenge'),
        get_string('settings:general_desc', 'mod_uckkchallenge')
    ));

    $settings->add(new admin_setting_configcheckbox(
        'mod_uckkchallenge/enabled',
        get_string('settings:enabled', 'mod_uckkchallenge'),
        get_string('settings:enabled_desc', 'mod_uckkchallenge'),
        1
    ));

    $settings->add(new admin_setting_configselect(
        'mod_uckkchallenge/defaultvisibility',
        get_string('settings:defaultvisibility', 'mod_uckkchallenge'),
        get_string('settings:defaultvisibility_desc', 'mod_uckkchallenge'),
        'course',
        [
            'private' => get_string('visibility:private', 'mod_uckkchallenge'),
            'user' => get_string('visibility:user', 'mod_uckkchallenge'),
            'group' => get_string('visibility:group', 'mod_uckkchallenge'),
            'course' => get_string('visibility:course', 'mod_uckkchallenge'),
            'cohort' => get_string('visibility:cohort', 'mod_uckkchallenge'),
            'program' => get_string('visibility:program', 'mod_uckkchallenge'),
            'institution' => get_string('visibility:institution', 'mod_uckkchallenge'),
            'public' => get_string('visibility:public', 'mod_uckkchallenge'),
            'restricted' => get_string('visibility:restricted', 'mod_uckkchallenge'),
        ]
    ));

    $settings->add(new admin_setting_configselect(
        'mod_uckkchallenge/defaultprovenance',
        get_string('settings:defaultprovenance', 'mod_uckkchallenge'),
        get_string('settings:defaultprovenance_desc', 'mod_uckkchallenge'),
        'human',
        [
            'human' => get_string('provenance:human', 'mod_uckkchallenge'),
            'ai_assisted' => get_string('provenance:ai_assisted', 'mod_uckkchallenge'),
            'imported' => get_string('provenance:imported', 'mod_uckkchallenge'),
            'system' => get_string('provenance:system', 'mod_uckkchallenge'),
            'archive' => get_string('provenance:archive', 'mod_uckkchallenge'),
            'assembly' => get_string('provenance:assembly', 'mod_uckkchallenge'),
            'challenge' => get_string('provenance:challenge', 'mod_uckkchallenge'),
            'integrity' => get_string('provenance:integrity', 'mod_uckkchallenge'),
        ]
    ));

    $settings->add(new admin_setting_heading(
        'mod_uckkchallenge/workflowsettings',
        get_string('settings:workflow', 'mod_uckkchallenge'),
        get_string('settings:workflow_desc', 'mod_uckkchallenge')
    ));

    $settings->add(new admin_setting_configcheckbox(
        'mod_uckkchallenge/requireevidence',
        get_string('settings:requireevidence', 'mod_uckkchallenge'),
        get_string('settings:requireevidence_desc', 'mod_uckkchallenge'),
        1
    ));

    $settings->add(new admin_setting_configcheckbox(
        'mod_uckkchallenge/requireprovenance',
        get_string('settings:requireprovenance', 'mod_uckkchallenge'),
        get_string('settings:requireprovenance_desc', 'mod_uckkchallenge'),
        1
    ));

    $settings->add(new admin_setting_configcheckbox(
        'mod_uckkchallenge/requirehumanvalidation',
        get_string('settings:requirehumanvalidation', 'mod_uckkchallenge'),
        get_string('settings:requirehumanvalidation_desc', 'mod_uckkchallenge'),
        1
    ));

    $settings->add(new admin_setting_configcheckbox(
        'mod_uckkchallenge/allowpublicchallenges',
        get_string('settings:allowpublicchallenges', 'mod_uckkchallenge'),
        get_string('settings:allowpublicchallenges_desc', 'mod_uckkchallenge'),
        0
    ));

    $settings->add(new admin_setting_configcheckbox(
        'mod_uckkchallenge/allowcontestation',
        get_string('settings:allowcontestation', 'mod_uckkchallenge'),
        get_string('settings:allowcontestation_desc', 'mod_uckkchallenge'),
        1
    ));

    $settings->add(new admin_setting_configduration(
        'mod_uckkchallenge/defaultreviewdays',
        get_string('settings:defaultreviewdays', 'mod_uckkchallenge'),
        get_string('settings:defaultreviewdays_desc', 'mod_uckkchallenge'),
        7 * DAYSECS,
        DAYSECS
    ));

    $settings->add(new admin_setting_heading(
        'mod_uckkchallenge/integritysettings',
        get_string('settings:integrity', 'mod_uckkchallenge'),
        get_string('settings:integrity_desc', 'mod_uckkchallenge')
    ));

    $settings->add(new admin_setting_configcheckbox(
        'mod_uckkchallenge/enableintegrityreview',
        get_string('settings:enableintegrityreview', 'mod_uckkchallenge'),
        get_string('settings:enableintegrityreview_desc', 'mod_uckkchallenge'),
        1
    ));

    $settings->add(new admin_setting_configcheckbox(
        'mod_uckkchallenge/pausevalidationonintegritycase',
        get_string('settings:pausevalidationonintegritycase', 'mod_uckkchallenge'),
        get_string('settings:pausevalidationonintegritycase_desc', 'mod_uckkchallenge'),
        1
    ));

    $settings->add(new admin_setting_configcheckbox(
        'mod_uckkchallenge/archivevalidatedproofs',
        get_string('settings:archivevalidatedproofs', 'mod_uckkchallenge'),
        get_string('settings:archivevalidatedproofs_desc', 'mod_uckkchallenge'),
        1
    ));

    $settings->add(new admin_setting_heading(
        'mod_uckkchallenge/aisettings',
        get_string('settings:ai', 'mod_uckkchallenge'),
        get_string('settings:ai_desc', 'mod_uckkchallenge')
    ));

    $settings->add(new admin_setting_configcheckbox(
        'mod_uckkchallenge/allowaiassistance',
        get_string('settings:allowaiassistance', 'mod_uckkchallenge'),
        get_string('settings:allowaiassistance_desc', 'mod_uckkchallenge'),
        1
    ));

    $settings->add(new admin_setting_configcheckbox(
        'mod_uckkchallenge/logaiuse',
        get_string('settings:logaiuse', 'mod_uckkchallenge'),
        get_string('settings:logaiuse_desc', 'mod_uckkchallenge'),
        1
    ));

    $settings->add(new admin_setting_configcheckbox(
        'mod_uckkchallenge/requireaiuncertaintylabel',
        get_string('settings:requireaiuncertaintylabel', 'mod_uckkchallenge'),
        get_string('settings:requireaiuncertaintylabel_desc', 'mod_uckkchallenge'),
        1
    ));

    $settings->add(new admin_setting_configcheckbox(
        'mod_uckkchallenge/allowaidecisionautomation',
        get_string('settings:allowaidecisionautomation', 'mod_uckkchallenge'),
        get_string('settings:allowaidecisionautomation_desc', 'mod_uckkchallenge'),
        0
    ));

    $settings->hide_if(
        'mod_uckkchallenge/allowaidecisionautomation',
        'mod_uckkchallenge/requirehumanvalidation',
        'eq',
        1
    );
}