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
 * Administration settings for local_uckk.
 *
 * local_uckk is the institutional core of UCKK-Moodle. It owns the shared
 * configuration, domain registry, symbolic role registry, pathway settings,
 * provenance rules, visibility defaults and integration switches used by the
 * UCKK plugin suite.
 *
 * It must not own challenge submissions, assembly motions, archive item
 * content or integrity case data. Those belong to their respective plugins.
 *
 * @package    local_uckk
 * @copyright  2026 Univers-Cité King Klown
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if (!$hassiteconfig) {
    return;
}

// -----------------------------------------------------------------------------
// Main settings page.
// -----------------------------------------------------------------------------

$settings = new admin_settingpage(
    'local_uckk',
    get_string('pluginname', 'local_uckk')
);

$ADMIN->add('localplugins', $settings);

// -----------------------------------------------------------------------------
// General campus settings.
// -----------------------------------------------------------------------------

$settings->add(new admin_setting_heading(
    'local_uckk/generalheading',
    get_string('settings_general', 'local_uckk'),
    get_string('settings_general_desc', 'local_uckk')
));

$settings->add(new admin_setting_configcheckbox(
    'local_uckk/enabled',
    get_string('enabled', 'local_uckk'),
    get_string('enabled_desc', 'local_uckk'),
    1
));

$settings->add(new admin_setting_configtext(
    'local_uckk/campustitle',
    get_string('campustitle', 'local_uckk'),
    get_string('campustitle_desc', 'local_uckk'),
    get_string('default_campustitle', 'local_uckk'),
    PARAM_TEXT
));

$settings->add(new admin_setting_configtext(
    'local_uckk/campusshortname',
    get_string('campusshortname', 'local_uckk'),
    get_string('campusshortname_desc', 'local_uckk'),
    'UCKK-Moodle',
    PARAM_TEXT
));

$settings->add(new admin_setting_configtext(
    'local_uckk/campustagline',
    get_string('campustagline', 'local_uckk'),
    get_string('campustagline_desc', 'local_uckk'),
    get_string('default_campustagline', 'local_uckk'),
    PARAM_TEXT
));

$settings->add(new admin_setting_configtextarea(
    'local_uckk/campusdescription',
    get_string('campusdescription', 'local_uckk'),
    get_string('campusdescription_desc', 'local_uckk'),
    get_string('default_campusdescription', 'local_uckk'),
    PARAM_TEXT
));

$settings->add(new admin_setting_configcheckbox(
    'local_uckk/showinternalrecognitionnotice',
    get_string('showinternalrecognitionnotice', 'local_uckk'),
    get_string('showinternalrecognitionnotice_desc', 'local_uckk'),
    1
));

$settings->add(new admin_setting_configtextarea(
    'local_uckk/internalrecognitionnotice',
    get_string('internalrecognitionnotice', 'local_uckk'),
    get_string('internalrecognitionnotice_desc', 'local_uckk'),
    get_string('default_internalrecognitionnotice', 'local_uckk'),
    PARAM_TEXT
));

// -----------------------------------------------------------------------------
// Canonical domain boundaries.
// -----------------------------------------------------------------------------

$settings->add(new admin_setting_heading(
    'local_uckk/domainheading',
    get_string('settings_domain', 'local_uckk'),
    get_string('settings_domain_desc', 'local_uckk')
));

$settings->add(new admin_setting_configcheckbox(
    'local_uckk/showboundarynotice',
    get_string('showboundarynotice', 'local_uckk'),
    get_string('showboundarynotice_desc', 'local_uckk'),
    1
));

$settings->add(new admin_setting_configtextarea(
    'local_uckk/boundarynotice',
    get_string('boundarynotice', 'local_uckk'),
    get_string('boundarynotice_desc', 'local_uckk'),
    get_string('default_boundarynotice', 'local_uckk'),
    PARAM_TEXT
));

$settings->add(new admin_setting_configcheckbox(
    'local_uckk/enablekingklownlayer',
    get_string('enablekingklownlayer', 'local_uckk'),
    get_string('enablekingklownlayer_desc', 'local_uckk'),
    1
));

$settings->add(new admin_setting_configcheckbox(
    'local_uckk/requirelevelnaming',
    get_string('requirelevelnaming', 'local_uckk'),
    get_string('requirelevelnaming_desc', 'local_uckk'),
    1
));

$settings->add(new admin_setting_configcheckbox(
    'local_uckk/enforceinstitutionalclarity',
    get_string('enforceinstitutionalclarity', 'local_uckk'),
    get_string('enforceinstitutionalclarity_desc', 'local_uckk'),
    1
));

// -----------------------------------------------------------------------------
// Programs and pathways.
// -----------------------------------------------------------------------------

$settings->add(new admin_setting_heading(
    'local_uckk/pathwaysheading',
    get_string('settings_pathways', 'local_uckk'),
    get_string('settings_pathways_desc', 'local_uckk')
));

$settings->add(new admin_setting_configcheckbox(
    'local_uckk/enableprogramregistry',
    get_string('enableprogramregistry', 'local_uckk'),
    get_string('enableprogramregistry_desc', 'local_uckk'),
    1
));

$settings->add(new admin_setting_configcheckbox(
    'local_uckk/enablepathways',
    get_string('enablepathways', 'local_uckk'),
    get_string('enablepathways_desc', 'local_uckk'),
    1
));

$settings->add(new admin_setting_configcheckbox(
    'local_uckk/enableplayerprofiles',
    get_string('enableplayerprofiles', 'local_uckk'),
    get_string('enableplayerprofiles_desc', 'local_uckk'),
    1
));

$settings->add(new admin_setting_configcheckbox(
    'local_uckk/autocreateplayerprofile',
    get_string('autocreateplayerprofile', 'local_uckk'),
    get_string('autocreateplayerprofile_desc', 'local_uckk'),
    1
));

$settings->add(new admin_setting_configselect(
    'local_uckk/defaultpathwayvisibility',
    get_string('defaultpathwayvisibility', 'local_uckk'),
    get_string('defaultpathwayvisibility_desc', 'local_uckk'),
    'user',
    [
        'private' => get_string('visibility_private', 'local_uckk'),
        'user' => get_string('visibility_user', 'local_uckk'),
        'course' => get_string('visibility_course', 'local_uckk'),
        'cohort' => get_string('visibility_cohort', 'local_uckk'),
        'institution' => get_string('visibility_institution', 'local_uckk'),
        'public' => get_string('visibility_public', 'local_uckk'),
    ]
));

$settings->add(new admin_setting_configcheckbox(
    'local_uckk/allowpublicpathways',
    get_string('allowpublicpathways', 'local_uckk'),
    get_string('allowpublicpathways_desc', 'local_uckk'),
    0
));

// -----------------------------------------------------------------------------
// Symbolic roles.
// -----------------------------------------------------------------------------

$settings->add(new admin_setting_heading(
    'local_uckk/symbolicrolesheading',
    get_string('settings_symbolicroles', 'local_uckk'),
    get_string('settings_symbolicroles_desc', 'local_uckk')
));

$settings->add(new admin_setting_configcheckbox(
    'local_uckk/enablesymbolicroles',
    get_string('enablesymbolicroles', 'local_uckk'),
    get_string('enablesymbolicroles_desc', 'local_uckk'),
    1
));

$settings->add(new admin_setting_configcheckbox(
    'local_uckk/separatesymbolicandtechnicalroles',
    get_string('separatesymbolicandtechnicalroles', 'local_uckk'),
    get_string('separatesymbolicandtechnicalroles_desc', 'local_uckk'),
    1
));

$settings->add(new admin_setting_configcheckbox(
    'local_uckk/showjoueurlucide',
    get_string('showjoueurlucide', 'local_uckk'),
    get_string('showjoueurlucide_desc', 'local_uckk'),
    1
));

$settings->add(new admin_setting_configcheckbox(
    'local_uckk/showbatisseur',
    get_string('showbatisseur', 'local_uckk'),
    get_string('showbatisseur_desc', 'local_uckk'),
    1
));

$settings->add(new admin_setting_configcheckbox(
    'local_uckk/showcartographe',
    get_string('showcartographe', 'local_uckk'),
    get_string('showcartographe_desc', 'local_uckk'),
    1
));

// -----------------------------------------------------------------------------
// Provenance, proof and visibility.
// -----------------------------------------------------------------------------

$settings->add(new admin_setting_heading(
    'local_uckk/provenanceheading',
    get_string('settings_provenance', 'local_uckk'),
    get_string('settings_provenance_desc', 'local_uckk')
));

$settings->add(new admin_setting_configcheckbox(
    'local_uckk/requireprovenance',
    get_string('requireprovenance', 'local_uckk'),
    get_string('requireprovenance_desc', 'local_uckk'),
    1
));

$settings->add(new admin_setting_configcheckbox(
    'local_uckk/requirehumanvalidation',
    get_string('requirehumanvalidation', 'local_uckk'),
    get_string('requirehumanvalidation_desc', 'local_uckk'),
    1
));

$settings->add(new admin_setting_configselect(
    'local_uckk/defaultvisibility',
    get_string('defaultvisibility', 'local_uckk'),
    get_string('defaultvisibility_desc', 'local_uckk'),
    'course',
    [
        'private' => get_string('visibility_private', 'local_uckk'),
        'user' => get_string('visibility_user', 'local_uckk'),
        'group' => get_string('visibility_group', 'local_uckk'),
        'course' => get_string('visibility_course', 'local_uckk'),
        'cohort' => get_string('visibility_cohort', 'local_uckk'),
        'institution' => get_string('visibility_institution', 'local_uckk'),
        'public' => get_string('visibility_public', 'local_uckk'),
    ]
));

$settings->add(new admin_setting_configselect(
    'local_uckk/defaultvalidationstate',
    get_string('defaultvalidationstate', 'local_uckk'),
    get_string('defaultvalidationstate_desc', 'local_uckk'),
    'unverified',
    [
        'unverified' => get_string('validation_unverified', 'local_uckk'),
        'human_reviewed' => get_string('validation_humanreviewed', 'local_uckk'),
        'verified' => get_string('validation_verified', 'local_uckk'),
        'contested' => get_string('validation_contested', 'local_uckk'),
        'invalidated' => get_string('validation_invalidated', 'local_uckk'),
        'archived' => get_string('validation_archived', 'local_uckk'),
    ]
));

$settings->add(new admin_setting_configcheckbox(
    'local_uckk/allowpublicevidence',
    get_string('allowpublicevidence', 'local_uckk'),
    get_string('allowpublicevidence_desc', 'local_uckk'),
    0
));

// -----------------------------------------------------------------------------
// Integrity and Inquisiteur.
// -----------------------------------------------------------------------------

$settings->add(new admin_setting_heading(
    'local_uckk/integrityheading',
    get_string('settings_integrity', 'local_uckk'),
    get_string('settings_integrity_desc', 'local_uckk')
));

$settings->add(new admin_setting_configcheckbox(
    'local_uckk/enableintegrityguardrails',
    get_string('enableintegrityguardrails', 'local_uckk'),
    get_string('enableintegrityguardrails_desc', 'local_uckk'),
    1
));

$settings->add(new admin_setting_configcheckbox(
    'local_uckk/requirecontestability',
    get_string('requirecontestability', 'local_uckk'),
    get_string('requirecontestability_desc', 'local_uckk'),
    1
));

$settings->add(new admin_setting_configcheckbox(
    'local_uckk/showintegritynotices',
    get_string('showintegritynotices', 'local_uckk'),
    get_string('showintegritynotices_desc', 'local_uckk'),
    1
));

$settings->add(new admin_setting_configselect(
    'local_uckk/defaultintegrityvisibility',
    get_string('defaultintegrityvisibility', 'local_uckk'),
    get_string('defaultintegrityvisibility_desc', 'local_uckk'),
    'restricted',
    [
        'private' => get_string('visibility_private', 'local_uckk'),
        'course' => get_string('visibility_course', 'local_uckk'),
        'institution' => get_string('visibility_institution', 'local_uckk'),
        'restricted' => get_string('visibility_restricted', 'local_uckk'),
        'public' => get_string('visibility_public', 'local_uckk'),
    ]
));

$settings->add(new admin_setting_configcheckbox(
    'local_uckk/preventsilentdeletion',
    get_string('preventsilentdeletion', 'local_uckk'),
    get_string('preventsilentdeletion_desc', 'local_uckk'),
    1
));

// -----------------------------------------------------------------------------
// Archives and memory.
// -----------------------------------------------------------------------------

$settings->add(new admin_setting_heading(
    'local_uckk/archiveheading',
    get_string('settings_archives', 'local_uckk'),
    get_string('settings_archives_desc', 'local_uckk')
));

$settings->add(new admin_setting_configcheckbox(
    'local_uckk/enablememorylayer',
    get_string('enablememorylayer', 'local_uckk'),
    get_string('enablememorylayer_desc', 'local_uckk'),
    1
));

$settings->add(new admin_setting_configcheckbox(
    'local_uckk/enablekristals',
    get_string('enablekristals', 'local_uckk'),
    get_string('enablekristals_desc', 'local_uckk'),
    1
));

$settings->add(new admin_setting_configcheckbox(
    'local_uckk/requirearchiveprovenance',
    get_string('requirearchiveprovenance', 'local_uckk'),
    get_string('requirearchiveprovenance_desc', 'local_uckk'),
    1
));

$settings->add(new admin_setting_configselect(
    'local_uckk/defaultarchivevisibility',
    get_string('defaultarchivevisibility', 'local_uckk'),
    get_string('defaultarchivevisibility_desc', 'local_uckk'),
    'course',
    [
        'private' => get_string('visibility_private', 'local_uckk'),
        'course' => get_string('visibility_course', 'local_uckk'),
        'cohort' => get_string('visibility_cohort', 'local_uckk'),
        'institution' => get_string('visibility_institution', 'local_uckk'),
        'public' => get_string('visibility_public', 'local_uckk'),
        'restricted' => get_string('visibility_restricted', 'local_uckk'),
    ]
));

$settings->add(new admin_setting_configcheckbox(
    'local_uckk/allowpublicarchives',
    get_string('allowpublicarchives', 'local_uckk'),
    get_string('allowpublicarchives_desc', 'local_uckk'),
    0
));

// -----------------------------------------------------------------------------
// Dashboard and navigation.
// -----------------------------------------------------------------------------

$settings->add(new admin_setting_heading(
    'local_uckk/navigationheading',
    get_string('settings_navigation', 'local_uckk'),
    get_string('settings_navigation_desc', 'local_uckk')
));

$settings->add(new admin_setting_configcheckbox(
    'local_uckk/enablenavigationregistry',
    get_string('enablenavigationregistry', 'local_uckk'),
    get_string('enablenavigationregistry_desc', 'local_uckk'),
    1
));

$settings->add(new admin_setting_configcheckbox(
    'local_uckk/enabledashboardintegration',
    get_string('enabledashboardintegration', 'local_uckk'),
    get_string('enabledashboardintegration_desc', 'local_uckk'),
    1
));

$settings->add(new admin_setting_configcheckbox(
    'local_uckk/showdashboardpathwaycard',
    get_string('showdashboardpathwaycard', 'local_uckk'),
    get_string('showdashboardpathwaycard_desc', 'local_uckk'),
    1
));

$settings->add(new admin_setting_configcheckbox(
    'local_uckk/showdashboardchallengecard',
    get_string('showdashboardchallengecard', 'local_uckk'),
    get_string('showdashboardchallengecard_desc', 'local_uckk'),
    1
));

$settings->add(new admin_setting_configcheckbox(
    'local_uckk/showdashboardassemblycard',
    get_string('showdashboardassemblycard', 'local_uckk'),
    get_string('showdashboardassemblycard_desc', 'local_uckk'),
    1
));

$settings->add(new admin_setting_configcheckbox(
    'local_uckk/showdashboardarchivecard',
    get_string('showdashboardarchivecard', 'local_uckk'),
    get_string('showdashboardarchivecard_desc', 'local_uckk'),
    1
));

$settings->add(new admin_setting_configcheckbox(
    'local_uckk/showdashboardintegritycard',
    get_string('showdashboardintegritycard', 'local_uckk'),
    get_string('showdashboardintegritycard_desc', 'local_uckk'),
    1
));

// -----------------------------------------------------------------------------
// AI governance.
// -----------------------------------------------------------------------------

$settings->add(new admin_setting_heading(
    'local_uckk/aiheading',
    get_string('settings_ai', 'local_uckk'),
    get_string('settings_ai_desc', 'local_uckk')
));

$settings->add(new admin_setting_configcheckbox(
    'local_uckk/enableaigovernance',
    get_string('enableaigovernance', 'local_uckk'),
    get_string('enableaigovernance_desc', 'local_uckk'),
    1
));

$settings->add(new admin_setting_configcheckbox(
    'local_uckk/ainonsovereign',
    get_string('ainonsovereign', 'local_uckk'),
    get_string('ainonsovereign_desc', 'local_uckk'),
    1
));

$settings->add(new admin_setting_configcheckbox(
    'local_uckk/requireaihumandecision',
    get_string('requireaihumandecision', 'local_uckk'),
    get_string('requireaihumandecision_desc', 'local_uckk'),
    1
));

$settings->add(new admin_setting_configcheckbox(
    'local_uckk/logaiprovenance',
    get_string('logaiprovenance', 'local_uckk'),
    get_string('logaiprovenance_desc', 'local_uckk'),
    1
));

$settings->add(new admin_setting_configcheckbox(
    'local_uckk/showaiwarning',
    get_string('showaiwarning', 'local_uckk'),
    get_string('showaiwarning_desc', 'local_uckk'),
    1
));

$settings->add(new admin_setting_configtextarea(
    'local_uckk/aiwarning',
    get_string('aiwarning', 'local_uckk'),
    get_string('aiwarning_desc', 'local_uckk'),
    get_string('default_aiwarning', 'local_uckk'),
    PARAM_TEXT
));

// -----------------------------------------------------------------------------
// Integration switches.
// -----------------------------------------------------------------------------

$settings->add(new admin_setting_heading(
    'local_uckk/integrationsheading',
    get_string('settings_integrations', 'local_uckk'),
    get_string('settings_integrations_desc', 'local_uckk')
));

$settings->add(new admin_setting_configcheckbox(
    'local_uckk/integratechallenge',
    get_string('integratechallenge', 'local_uckk'),
    get_string('integratechallenge_desc', 'local_uckk'),
    1
));

$settings->add(new admin_setting_configcheckbox(
    'local_uckk/integrateassembly',
    get_string('integrateassembly', 'local_uckk'),
    get_string('integrateassembly_desc', 'local_uckk'),
    1
));

$settings->add(new admin_setting_configcheckbox(
    'local_uckk/integratearchive',
    get_string('integratearchive', 'local_uckk'),
    get_string('integratearchive_desc', 'local_uckk'),
    1
));

$settings->add(new admin_setting_configcheckbox(
    'local_uckk/integrateintegrity',
    get_string('integrateintegrity', 'local_uckk'),
    get_string('integrateintegrity_desc', 'local_uckk'),
    1
));

$settings->add(new admin_setting_configcheckbox(
    'local_uckk/integratereports',
    get_string('integratereports', 'local_uckk'),
    get_string('integratereports_desc', 'local_uckk'),
    1
));

$settings->add(new admin_setting_configcheckbox(
    'local_uckk/integrateai',
    get_string('integrateai', 'local_uckk'),
    get_string('integrateai_desc', 'local_uckk'),
    1
));

// -----------------------------------------------------------------------------
// External references.
// -----------------------------------------------------------------------------

$settings->add(new admin_setting_heading(
    'local_uckk/externalheading',
    get_string('settings_external', 'local_uckk'),
    get_string('settings_external_desc', 'local_uckk')
));

$settings->add(new admin_setting_configtext(
    'local_uckk/canonurl',
    get_string('canonurl', 'local_uckk'),
    get_string('canonurl_desc', 'local_uckk'),
    '',
    PARAM_URL
));

$settings->add(new admin_setting_configtext(
    'local_uckk/koadigitalecosystemurl',
    get_string('koadigitalecosystemurl', 'local_uckk'),
    get_string('koadigitalecosystemurl_desc', 'local_uckk'),
    '',
    PARAM_URL
));

$settings->add(new admin_setting_configtext(
    'local_uckk/publicarchiveurl',
    get_string('publicarchiveurl', 'local_uckk'),
    get_string('publicarchiveurl_desc', 'local_uckk'),
    '',
    PARAM_URL
));

// -----------------------------------------------------------------------------
// Advanced settings.
// -----------------------------------------------------------------------------

$settings->add(new admin_setting_heading(
    'local_uckk/advancedheading',
    get_string('settings_advanced', 'local_uckk'),
    get_string('settings_advanced_desc', 'local_uckk')
));

$settings->add(new admin_setting_configcheckbox(
    'local_uckk/debugmode',
    get_string('debugmode', 'local_uckk'),
    get_string('debugmode_desc', 'local_uckk'),
    0
));

$settings->add(new admin_setting_configcheckbox(
    'local_uckk/showdiagnostics',
    get_string('showdiagnostics', 'local_uckk'),
    get_string('showdiagnostics_desc', 'local_uckk'),
    0
));

$settings->add(new admin_setting_configtext(
    'local_uckk/cachettl',
    get_string('cachettl', 'local_uckk'),
    get_string('cachettl_desc', 'local_uckk'),
    300,
    PARAM_INT
));

$settings->add(new admin_setting_configtext(
    'local_uckk/defaultpagesize',
    get_string('defaultpagesize', 'local_uckk'),
    get_string('defaultpagesize_desc', 'local_uckk'),
    25,
    PARAM_INT
));

// -----------------------------------------------------------------------------
// External administration pages.
// -----------------------------------------------------------------------------
//
// These pages are declared here only as navigation targets. The actual pages
// must enforce their own require_login(), context checks and capability checks.

$ADMIN->add('localplugins', new admin_externalpage(
    'local_uckk_programs',
    get_string('manageprograms', 'local_uckk'),
    new moodle_url('/local/uckk/programs.php'),
    'local/uckk:manageprograms'
));

$ADMIN->add('localplugins', new admin_externalpage(
    'local_uckk_pathways',
    get_string('managepathways', 'local_uckk'),
    new moodle_url('/local/uckk/pathways.php'),
    'local/uckk:managepathways'
));

$ADMIN->add('localplugins', new admin_externalpage(
    'local_uckk_canon',
    get_string('managecanon', 'local_uckk'),
    new moodle_url('/local/uckk/canon.php'),
    'local/uckk:managecanon'
));