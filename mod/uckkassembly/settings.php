<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

/**
 * Admin settings for UCKK Assemblies.
 *
 * @package    mod_uckkassembly
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {
    // General defaults.
    $settings->add(new admin_setting_heading(
        'mod_uckkassembly/generalsettings',
        get_string('settings:general', 'uckkassembly'),
        get_string('settings:general_desc', 'uckkassembly')
    ));

    $settings->add(new admin_setting_configcheckbox(
        'mod_uckkassembly/defaultenabled',
        get_string('defaultenabled', 'uckkassembly'),
        get_string('defaultenabled_desc', 'uckkassembly'),
        1
    ));

    $settings->add(new admin_setting_configselect(
        'mod_uckkassembly/defaultassemblytype',
        get_string('defaultassemblytype', 'uckkassembly'),
        get_string('defaultassemblytype_desc', 'uckkassembly'),
        'savoirs',
        [
            'savoirs' => get_string('assemblytype:savoirs', 'uckkassembly'),
            'defis' => get_string('assemblytype:defis', 'uckkassembly'),
            'joueurs' => get_string('assemblytype:joueurs', 'uckkassembly'),
            'batisseurs' => get_string('assemblytype:batisseurs', 'uckkassembly'),
            'inquisiteurs' => get_string('assemblytype:inquisiteurs', 'uckkassembly'),
            'grand_jeu' => get_string('assemblytype:grandjeu', 'uckkassembly'),
        ]
    ));

    $settings->add(new admin_setting_configselect(
        'mod_uckkassembly/defaultvisibility',
        get_string('defaultvisibility', 'uckkassembly'),
        get_string('defaultvisibility_desc', 'uckkassembly'),
        'course',
        [
            'private' => get_string('visibility:private', 'uckkassembly'),
            'course' => get_string('visibility:course', 'uckkassembly'),
            'cohort' => get_string('visibility:cohort', 'uckkassembly'),
            'program' => get_string('visibility:program', 'uckkassembly'),
            'institution' => get_string('visibility:institution', 'uckkassembly'),
            'public' => get_string('visibility:public', 'uckkassembly'),
            'restricted_integrity' => get_string('visibility:restricted_integrity', 'uckkassembly'),
        ]
    ));

    // Motions and amendments.
    $settings->add(new admin_setting_heading(
        'mod_uckkassembly/motionsettings',
        get_string('settings:motions', 'uckkassembly'),
        get_string('settings:motions_desc', 'uckkassembly')
    ));

    $settings->add(new admin_setting_configcheckbox(
        'mod_uckkassembly/allowmotionsbydefault',
        get_string('allowmotionsbydefault', 'uckkassembly'),
        get_string('allowmotionsbydefault_desc', 'uckkassembly'),
        1
    ));

    $settings->add(new admin_setting_configcheckbox(
        'mod_uckkassembly/allowamendmentsbydefault',
        get_string('allowamendmentsbydefault', 'uckkassembly'),
        get_string('allowamendmentsbydefault_desc', 'uckkassembly'),
        1
    ));

    $settings->add(new admin_setting_configcheckbox(
        'mod_uckkassembly/allowobjectionsbydefault',
        get_string('allowobjectionsbydefault', 'uckkassembly'),
        get_string('allowobjectionsbydefault_desc', 'uckkassembly'),
        1
    ));

    $settings->add(new admin_setting_configtext(
        'mod_uckkassembly/maxamendmentspermotion',
        get_string('maxamendmentspermotion', 'uckkassembly'),
        get_string('maxamendmentspermotion_desc', 'uckkassembly'),
        20,
        PARAM_INT
    ));

    // Voting and readings.
    $settings->add(new admin_setting_heading(
        'mod_uckkassembly/votesettings',
        get_string('settings:votes', 'uckkassembly'),
        get_string('settings:votes_desc', 'uckkassembly')
    ));

    $settings->add(new admin_setting_configcheckbox(
        'mod_uckkassembly/enablevotesbydefault',
        get_string('enablevotesbydefault', 'uckkassembly'),
        get_string('enablevotesbydefault_desc', 'uckkassembly'),
        1
    ));

    $settings->add(new admin_setting_configselect(
        'mod_uckkassembly/defaultvotemethod',
        get_string('defaultvotemethod', 'uckkassembly'),
        get_string('defaultvotemethod_desc', 'uckkassembly'),
        'simple_majority',
        [
            'simple_majority' => get_string('votemethod:simplemajority', 'uckkassembly'),
            'qualified_majority' => get_string('votemethod:qualifiedmajority', 'uckkassembly'),
            'consent' => get_string('votemethod:consent', 'uckkassembly'),
            'consensus' => get_string('votemethod:consensus', 'uckkassembly'),
            'advisory' => get_string('votemethod:advisory', 'uckkassembly'),
        ]
    ));

    $settings->add(new admin_setting_configtext(
        'mod_uckkassembly/defaultquorumpercent',
        get_string('defaultquorumpercent', 'uckkassembly'),
        get_string('defaultquorumpercent_desc', 'uckkassembly'),
        50,
        PARAM_INT
    ));

    $settings->add(new admin_setting_configtext(
        'mod_uckkassembly/defaultapprovalthreshold',
        get_string('defaultapprovalthreshold', 'uckkassembly'),
        get_string('defaultapprovalthreshold_desc', 'uckkassembly'),
        50,
        PARAM_INT
    ));

    $settings->add(new admin_setting_configcheckbox(
        'mod_uckkassembly/allowvotechange',
        get_string('allowvotechange', 'uckkassembly'),
        get_string('allowvotechange_desc', 'uckkassembly'),
        1
    ));

    $settings->add(new admin_setting_configcheckbox(
        'mod_uckkassembly/requirevoterationale',
        get_string('requirevoterationale', 'uckkassembly'),
        get_string('requirevoterationale_desc', 'uckkassembly'),
        0
    ));

    // Decisions and publication.
    $settings->add(new admin_setting_heading(
        'mod_uckkassembly/decisionsettings',
        get_string('settings:decisions', 'uckkassembly'),
        get_string('settings:decisions_desc', 'uckkassembly')
    ));

    $settings->add(new admin_setting_configcheckbox(
        'mod_uckkassembly/requirehumanpublication',
        get_string('requirehumanpublication', 'uckkassembly'),
        get_string('requirehumanpublication_desc', 'uckkassembly'),
        1
    ));

    $settings->add(new admin_setting_configcheckbox(
        'mod_uckkassembly/allowminorityreports',
        get_string('allowminorityreports', 'uckkassembly'),
        get_string('allowminorityreports_desc', 'uckkassembly'),
        1
    ));

    $settings->add(new admin_setting_configcheckbox(
        'mod_uckkassembly/allowcontestation',
        get_string('allowcontestation', 'uckkassembly'),
        get_string('allowcontestation_desc', 'uckkassembly'),
        1
    ));

    $settings->add(new admin_setting_configselect(
        'mod_uckkassembly/defaultdecisiontype',
        get_string('defaultdecisiontype', 'uckkassembly'),
        get_string('defaultdecisiontype_desc', 'uckkassembly'),
        'recommendation',
        [
            'information' => get_string('decisiontype:information', 'uckkassembly'),
            'recommendation' => get_string('decisiontype:recommendation', 'uckkassembly'),
            'validation' => get_string('decisiontype:validation', 'uckkassembly'),
            'correction' => get_string('decisiontype:correction', 'uckkassembly'),
            'rejection' => get_string('decisiontype:rejection', 'uckkassembly'),
            'archival' => get_string('decisiontype:archival', 'uckkassembly'),
            'integrity' => get_string('decisiontype:integrity', 'uckkassembly'),
        ]
    ));

    // Minutes.
    $settings->add(new admin_setting_heading(
        'mod_uckkassembly/minutessettings',
        get_string('settings:minutes', 'uckkassembly'),
        get_string('settings:minutes_desc', 'uckkassembly')
    ));

    $settings->add(new admin_setting_configcheckbox(
        'mod_uckkassembly/enableminutesbydefault',
        get_string('enableminutesbydefault', 'uckkassembly'),
        get_string('enableminutesbydefault_desc', 'uckkassembly'),
        1
    ));

    $settings->add(new admin_setting_configcheckbox(
        'mod_uckkassembly/autogenerateminutesdraft',
        get_string('autogenerateminutesdraft', 'uckkassembly'),
        get_string('autogenerateminutesdraft_desc', 'uckkassembly'),
        1
    ));

    $settings->add(new admin_setting_configcheckbox(
        'mod_uckkassembly/requireminutesbeforepublication',
        get_string('requireminutesbeforepublication', 'uckkassembly'),
        get_string('requireminutesbeforepublication_desc', 'uckkassembly'),
        1
    ));

    $settings->add(new admin_setting_configselect(
        'mod_uckkassembly/defaultminutesvisibility',
        get_string('defaultminutesvisibility', 'uckkassembly'),
        get_string('defaultminutesvisibility_desc', 'uckkassembly'),
        'course',
        [
            'private' => get_string('visibility:private', 'uckkassembly'),
            'course' => get_string('visibility:course', 'uckkassembly'),
            'cohort' => get_string('visibility:cohort', 'uckkassembly'),
            'program' => get_string('visibility:program', 'uckkassembly'),
            'institution' => get_string('visibility:institution', 'uckkassembly'),
            'public' => get_string('visibility:public', 'uckkassembly'),
            'restricted_integrity' => get_string('visibility:restricted_integrity', 'uckkassembly'),
        ]
    ));

    // Archive.
    $settings->add(new admin_setting_heading(
        'mod_uckkassembly/archivesettings',
        get_string('settings:archive', 'uckkassembly'),
        get_string('settings:archive_desc', 'uckkassembly')
    ));

    $settings->add(new admin_setting_configcheckbox(
        'mod_uckkassembly/enablearchiveexport',
        get_string('enablearchiveexport', 'uckkassembly'),
        get_string('enablearchiveexport_desc', 'uckkassembly'),
        1
    ));

    $settings->add(new admin_setting_configselect(
        'mod_uckkassembly/defaultarchivepolicy',
        get_string('defaultarchivepolicy', 'uckkassembly'),
        get_string('defaultarchivepolicy_desc', 'uckkassembly'),
        'decision_and_minutes',
        [
            'none' => get_string('archivepolicy:none', 'uckkassembly'),
            'decision_only' => get_string('archivepolicy:decisiononly', 'uckkassembly'),
            'decision_and_minutes' => get_string('archivepolicy:decisionandminutes', 'uckkassembly'),
            'full_record' => get_string('archivepolicy:fullrecord', 'uckkassembly'),
            'restricted_integrity' => get_string('archivepolicy:restrictedintegrity', 'uckkassembly'),
        ]
    ));

    $settings->add(new admin_setting_configcheckbox(
        'mod_uckkassembly/versionarchivedrecords',
        get_string('versionarchivedrecords', 'uckkassembly'),
        get_string('versionarchivedrecords_desc', 'uckkassembly'),
        1
    ));

    // Integrity and safety.
    $settings->add(new admin_setting_heading(
        'mod_uckkassembly/integritysettings',
        get_string('settings:integrity', 'uckkassembly'),
        get_string('settings:integrity_desc', 'uckkassembly')
    ));

    $settings->add(new admin_setting_configcheckbox(
        'mod_uckkassembly/enableintegrityreview',
        get_string('enableintegrityreview', 'uckkassembly'),
        get_string('enableintegrityreview_desc', 'uckkassembly'),
        1
    ));

    $settings->add(new admin_setting_configcheckbox(
        'mod_uckkassembly/pausepublicationonintegritycase',
        get_string('pausepublicationonintegritycase', 'uckkassembly'),
        get_string('pausepublicationonintegritycase_desc', 'uckkassembly'),
        1
    ));

    $settings->add(new admin_setting_configcheckbox(
        'mod_uckkassembly/restrictintegritydetails',
        get_string('restrictintegritydetails', 'uckkassembly'),
        get_string('restrictintegritydetails_desc', 'uckkassembly'),
        1
    ));

    $settings->add(new admin_setting_configcheckbox(
        'mod_uckkassembly/logproceduralactions',
        get_string('logproceduralactions', 'uckkassembly'),
        get_string('logproceduralactions_desc', 'uckkassembly'),
        1
    ));

    // AI assistance.
    $settings->add(new admin_setting_heading(
        'mod_uckkassembly/aisettings',
        get_string('settings:ai', 'uckkassembly'),
        get_string('settings:ai_desc', 'uckkassembly')
    ));

    $settings->add(new admin_setting_configcheckbox(
        'mod_uckkassembly/allowaisummary',
        get_string('allowaisummary', 'uckkassembly'),
        get_string('allowaisummary_desc', 'uckkassembly'),
        0
    ));

    $settings->add(new admin_setting_configcheckbox(
        'mod_uckkassembly/labelaisummaries',
        get_string('labelaisummaries', 'uckkassembly'),
        get_string('labelaisummaries_desc', 'uckkassembly'),
        1
    ));

    $settings->add(new admin_setting_configcheckbox(
        'mod_uckkassembly/requirehumanvalidationforai',
        get_string('requirehumanvalidationforai', 'uckkassembly'),
        get_string('requirehumanvalidationforai_desc', 'uckkassembly'),
        1
    ));
}