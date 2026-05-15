<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

/**
 * Admin settings for the UCKK Archive activity module.
 *
 * @package    mod_uckkarchive
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {
    // -------------------------------------------------------------------------
    // General settings.
    // -------------------------------------------------------------------------

    $settings->add(new admin_setting_heading(
        'mod_uckkarchive/generalsettings',
        get_string('settings:general', 'mod_uckkarchive'),
        get_string('settings:general_desc', 'mod_uckkarchive')
    ));

    $settings->add(new admin_setting_configcheckbox(
        'mod_uckkarchive/enabled',
        get_string('settings:enabled', 'mod_uckkarchive'),
        get_string('settings:enabled_desc', 'mod_uckkarchive'),
        1
    ));

    $settings->add(new admin_setting_configselect(
        'mod_uckkarchive/defaultvisibility',
        get_string('settings:defaultvisibility', 'mod_uckkarchive'),
        get_string('settings:defaultvisibility_desc', 'mod_uckkarchive'),
        'course',
        [
            'private' => get_string('visibility:private', 'mod_uckkarchive'),
            'user' => get_string('visibility:user', 'mod_uckkarchive'),
            'group' => get_string('visibility:group', 'mod_uckkarchive'),
            'course' => get_string('visibility:course', 'mod_uckkarchive'),
            'cohort' => get_string('visibility:cohort', 'mod_uckkarchive'),
            'program' => get_string('visibility:program', 'mod_uckkarchive'),
            'institution' => get_string('visibility:institution', 'mod_uckkarchive'),
            'public' => get_string('visibility:public', 'mod_uckkarchive'),
            'restricted' => get_string('visibility:restricted', 'mod_uckkarchive'),
            'restricted_integrity' => get_string('visibility:restricted_integrity', 'mod_uckkarchive'),
            'hidden' => get_string('visibility:hidden', 'mod_uckkarchive'),
            'archived' => get_string('visibility:archived', 'mod_uckkarchive'),
        ]
    ));

    $settings->add(new admin_setting_configselect(
        'mod_uckkarchive/defaultprovenance',
        get_string('settings:defaultprovenance', 'mod_uckkarchive'),
        get_string('settings:defaultprovenance_desc', 'mod_uckkarchive'),
        'human',
        [
            'human' => get_string('provenance:human', 'mod_uckkarchive'),
            'ai_assisted' => get_string('provenance:ai_assisted', 'mod_uckkarchive'),
            'imported' => get_string('provenance:imported', 'mod_uckkarchive'),
            'system' => get_string('provenance:system', 'mod_uckkarchive'),
            'archive' => get_string('provenance:archive', 'mod_uckkarchive'),
            'assembly' => get_string('provenance:assembly', 'mod_uckkarchive'),
            'challenge' => get_string('provenance:challenge', 'mod_uckkarchive'),
            'integrity' => get_string('provenance:integrity', 'mod_uckkarchive'),
        ]
    ));

    $settings->add(new admin_setting_configselect(
        'mod_uckkarchive/defaultvalidationstate',
        get_string('settings:defaultvalidationstate', 'mod_uckkarchive'),
        get_string('settings:defaultvalidationstate_desc', 'mod_uckkarchive'),
        'unverified',
        [
            'unverified' => get_string('validation:unverified', 'mod_uckkarchive'),
            'human_reviewed' => get_string('validation:human_reviewed', 'mod_uckkarchive'),
            'verified' => get_string('validation:verified', 'mod_uckkarchive'),
            'contested' => get_string('validation:contested', 'mod_uckkarchive'),
            'invalidated' => get_string('validation:invalidated', 'mod_uckkarchive'),
            'archived' => get_string('validation:archived', 'mod_uckkarchive'),
        ]
    ));

    // -------------------------------------------------------------------------
    // Archive item policy.
    // -------------------------------------------------------------------------

    $settings->add(new admin_setting_heading(
        'mod_uckkarchive/itempolicysettings',
        get_string('settings:itempolicy', 'mod_uckkarchive'),
        get_string('settings:itempolicy_desc', 'mod_uckkarchive')
    ));

    $settings->add(new admin_setting_configcheckbox(
        'mod_uckkarchive/requireprovenance',
        get_string('settings:requireprovenance', 'mod_uckkarchive'),
        get_string('settings:requireprovenance_desc', 'mod_uckkarchive'),
        1
    ));

    $settings->add(new admin_setting_configcheckbox(
        'mod_uckkarchive/requirecontext',
        get_string('settings:requirecontext', 'mod_uckkarchive'),
        get_string('settings:requirecontext_desc', 'mod_uckkarchive'),
        1
    ));

    $settings->add(new admin_setting_configcheckbox(
        'mod_uckkarchive/requirevisibility',
        get_string('settings:requirevisibility', 'mod_uckkarchive'),
        get_string('settings:requirevisibility_desc', 'mod_uckkarchive'),
        1
    ));

    $settings->add(new admin_setting_configcheckbox(
        'mod_uckkarchive/requirehumanvalidation',
        get_string('settings:requirehumanvalidation', 'mod_uckkarchive'),
        get_string('settings:requirehumanvalidation_desc', 'mod_uckkarchive'),
        1
    ));

    $settings->add(new admin_setting_configcheckbox(
        'mod_uckkarchive/allowpublicitems',
        get_string('settings:allowpublicitems', 'mod_uckkarchive'),
        get_string('settings:allowpublicitems_desc', 'mod_uckkarchive'),
        0
    ));

    $settings->add(new admin_setting_configcheckbox(
        'mod_uckkarchive/protectrestricteditems',
        get_string('settings:protectrestricteditems', 'mod_uckkarchive'),
        get_string('settings:protectrestricteditems_desc', 'mod_uckkarchive'),
        1
    ));

    // -------------------------------------------------------------------------
    // Validation and revision policy.
    // -------------------------------------------------------------------------

    $settings->add(new admin_setting_heading(
        'mod_uckkarchive/validationsettings',
        get_string('settings:validation', 'mod_uckkarchive'),
        get_string('settings:validation_desc', 'mod_uckkarchive')
    ));

    $settings->add(new admin_setting_configcheckbox(
        'mod_uckkarchive/enablevalidation',
        get_string('settings:enablevalidation', 'mod_uckkarchive'),
        get_string('settings:enablevalidation_desc', 'mod_uckkarchive'),
        1
    ));

    $settings->add(new admin_setting_configcheckbox(
        'mod_uckkarchive/enablerevisions',
        get_string('settings:enablerevisions', 'mod_uckkarchive'),
        get_string('settings:enablerevisions_desc', 'mod_uckkarchive'),
        1
    ));

    $settings->add(new admin_setting_configcheckbox(
        'mod_uckkarchive/requirechangereason',
        get_string('settings:requirechangereason', 'mod_uckkarchive'),
        get_string('settings:requirechangereason_desc', 'mod_uckkarchive'),
        1
    ));

    $settings->add(new admin_setting_configcheckbox(
        'mod_uckkarchive/lockvalidateditems',
        get_string('settings:lockvalidateditems', 'mod_uckkarchive'),
        get_string('settings:lockvalidateditems_desc', 'mod_uckkarchive'),
        1
    ));

    $settings->add(new admin_setting_configcheckbox(
        'mod_uckkarchive/pausevalidationonintegritycase',
        get_string('settings:pausevalidationonintegritycase', 'mod_uckkarchive'),
        get_string('settings:pausevalidationonintegritycase_desc', 'mod_uckkarchive'),
        1
    ));

    $settings->hide_if(
        'mod_uckkarchive/lockvalidateditems',
        'mod_uckkarchive/enablerevisions',
        'eq',
        0
    );

    // -------------------------------------------------------------------------
    // Kristal policy.
    // -------------------------------------------------------------------------

    $settings->add(new admin_setting_heading(
        'mod_uckkarchive/kristalsettings',
        get_string('settings:kristal', 'mod_uckkarchive'),
        get_string('settings:kristal_desc', 'mod_uckkarchive')
    ));

    $settings->add(new admin_setting_configcheckbox(
        'mod_uckkarchive/enablekristals',
        get_string('settings:enablekristals', 'mod_uckkarchive'),
        get_string('settings:enablekristals_desc', 'mod_uckkarchive'),
        1
    ));

    $settings->add(new admin_setting_configcheckbox(
        'mod_uckkarchive/kristalsrequirevalidation',
        get_string('settings:kristalsrequirevalidation', 'mod_uckkarchive'),
        get_string('settings:kristalsrequirevalidation_desc', 'mod_uckkarchive'),
        1
    ));

    $settings->add(new admin_setting_configtext(
        'mod_uckkarchive/maxkristalitems',
        get_string('settings:maxkristalitems', 'mod_uckkarchive'),
        get_string('settings:maxkristalitems_desc', 'mod_uckkarchive'),
        50,
        PARAM_INT
    ));

    $settings->hide_if(
        'mod_uckkarchive/kristalsrequirevalidation',
        'mod_uckkarchive/enablekristals',
        'eq',
        0
    );

    $settings->hide_if(
        'mod_uckkarchive/maxkristalitems',
        'mod_uckkarchive/enablekristals',
        'eq',
        0
    );

    // -------------------------------------------------------------------------
    // Export policy.
    // -------------------------------------------------------------------------

    $settings->add(new admin_setting_heading(
        'mod_uckkarchive/exportsettings',
        get_string('settings:export', 'mod_uckkarchive'),
        get_string('settings:export_desc', 'mod_uckkarchive')
    ));

    $settings->add(new admin_setting_configcheckbox(
        'mod_uckkarchive/enableexports',
        get_string('settings:enableexports', 'mod_uckkarchive'),
        get_string('settings:enableexports_desc', 'mod_uckkarchive'),
        1
    ));

    $settings->add(new admin_setting_configselect(
        'mod_uckkarchive/defaultexportformat',
        get_string('settings:defaultexportformat', 'mod_uckkarchive'),
        get_string('settings:defaultexportformat_desc', 'mod_uckkarchive'),
        'json',
        [
            'json' => get_string('exportformat:json', 'mod_uckkarchive'),
            'html' => get_string('exportformat:html', 'mod_uckkarchive'),
            'csv' => get_string('exportformat:csv', 'mod_uckkarchive'),
            'mbz_manifest' => get_string('exportformat:mbz_manifest', 'mod_uckkarchive'),
        ]
    ));

    $settings->add(new admin_setting_configcheckbox(
        'mod_uckkarchive/includefilesinexports',
        get_string('settings:includefilesinexports', 'mod_uckkarchive'),
        get_string('settings:includefilesinexports_desc', 'mod_uckkarchive'),
        0
    ));

    $settings->add(new admin_setting_configcheckbox(
        'mod_uckkarchive/blockrestrictedexports',
        get_string('settings:blockrestrictedexports', 'mod_uckkarchive'),
        get_string('settings:blockrestrictedexports_desc', 'mod_uckkarchive'),
        1
    ));

    $settings->add(new admin_setting_configtext(
        'mod_uckkarchive/maxexportitems',
        get_string('settings:maxexportitems', 'mod_uckkarchive'),
        get_string('settings:maxexportitems_desc', 'mod_uckkarchive'),
        500,
        PARAM_INT
    ));

    $settings->hide_if(
        'mod_uckkarchive/defaultexportformat',
        'mod_uckkarchive/enableexports',
        'eq',
        0
    );

    $settings->hide_if(
        'mod_uckkarchive/includefilesinexports',
        'mod_uckkarchive/enableexports',
        'eq',
        0
    );

    $settings->hide_if(
        'mod_uckkarchive/blockrestrictedexports',
        'mod_uckkarchive/enableexports',
        'eq',
        0
    );

    $settings->hide_if(
        'mod_uckkarchive/maxexportitems',
        'mod_uckkarchive/enableexports',
        'eq',
        0
    );

    // -------------------------------------------------------------------------
    // Scheduled tasks.
    // -------------------------------------------------------------------------

    $settings->add(new admin_setting_heading(
        'mod_uckkarchive/tasksettings',
        get_string('settings:tasks', 'mod_uckkarchive'),
        get_string('settings:tasks_desc', 'mod_uckkarchive')
    ));

    $settings->add(new admin_setting_configcheckbox(
        'mod_uckkarchive/validatependingitems',
        get_string('settings:validatependingitems', 'mod_uckkarchive'),
        get_string('settings:validatependingitems_desc', 'mod_uckkarchive'),
        1
    ));

    $settings->add(new admin_setting_configcheckbox(
        'mod_uckkarchive/generateexports',
        get_string('settings:generateexports', 'mod_uckkarchive'),
        get_string('settings:generateexports_desc', 'mod_uckkarchive'),
        1
    ));

    $settings->add(new admin_setting_configtext(
        'mod_uckkarchive/taskbatchsize',
        get_string('settings:taskbatchsize', 'mod_uckkarchive'),
        get_string('settings:taskbatchsize_desc', 'mod_uckkarchive'),
        50,
        PARAM_INT
    ));

    // -------------------------------------------------------------------------
    // AI governance.
    // -------------------------------------------------------------------------

    $settings->add(new admin_setting_heading(
        'mod_uckkarchive/aisettings',
        get_string('settings:ai', 'mod_uckkarchive'),
        get_string('settings:ai_desc', 'mod_uckkarchive')
    ));

    $settings->add(new admin_setting_configcheckbox(
        'mod_uckkarchive/allowaiassistance',
        get_string('settings:allowaiassistance', 'mod_uckkarchive'),
        get_string('settings:allowaiassistance_desc', 'mod_uckkarchive'),
        1
    ));

    $settings->add(new admin_setting_configcheckbox(
        'mod_uckkarchive/logaiuse',
        get_string('settings:logaiuse', 'mod_uckkarchive'),
        get_string('settings:logaiuse_desc', 'mod_uckkarchive'),
        1
    ));

    $settings->add(new admin_setting_configcheckbox(
        'mod_uckkarchive/requireaiuncertaintylabel',
        get_string('settings:requireaiuncertaintylabel', 'mod_uckkarchive'),
        get_string('settings:requireaiuncertaintylabel_desc', 'mod_uckkarchive'),
        1
    ));

    $settings->add(new admin_setting_configcheckbox(
        'mod_uckkarchive/allowaivalidation',
        get_string('settings:allowaivalidation', 'mod_uckkarchive'),
        get_string('settings:allowaivalidation_desc', 'mod_uckkarchive'),
        0
    ));

    $settings->hide_if(
        'mod_uckkarchive/allowaivalidation',
        'mod_uckkarchive/requirehumanvalidation',
        'eq',
        1
    );
}
