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
 * Global settings for the UCKK course format.
 *
 * These settings define default display and behaviour preferences for
 * courses using format_uckk.
 *
 * This file must remain configuration-only. It must not contain course
 * rendering logic, workflow logic, grading rules, integrity decisions,
 * archive validation, challenge rules, assembly decisions or AI policy
 * execution.
 *
 * @package    format_uckk
 * @copyright  2026 Univers-Cité King Klown
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {
    // -------------------------------------------------------------------------
    // General UCKK course format settings.
    // -------------------------------------------------------------------------

    $settings->add(new admin_setting_heading(
        'format_uckk/general',
        get_string('settings_general', 'format_uckk'),
        get_string('settings_general_desc', 'format_uckk')
    ));

    $settings->add(new admin_setting_configcheckbox(
        'format_uckk/showcourseidentity',
        get_string('showcourseidentity', 'format_uckk'),
        get_string('showcourseidentity_desc', 'format_uckk'),
        1
    ));

    $settings->add(new admin_setting_configcheckbox(
        'format_uckk/showcanonicalnotice',
        get_string('showcanonicalnotice', 'format_uckk'),
        get_string('showcanonicalnotice_desc', 'format_uckk'),
        1
    ));

    $settings->add(new admin_setting_configcheckbox(
        'format_uckk/showinternalrecognitionnotice',
        get_string('showinternalrecognitionnotice', 'format_uckk'),
        get_string('showinternalrecognitionnotice_desc', 'format_uckk'),
        1
    ));

    $settings->add(new admin_setting_configcheckbox(
        'format_uckk/showkoacycle',
        get_string('showkoacycle', 'format_uckk'),
        get_string('showkoacycle_desc', 'format_uckk'),
        1
    ));

    $settings->add(new admin_setting_configselect(
        'format_uckk/defaultcoursestyle',
        get_string('defaultcoursestyle', 'format_uckk'),
        get_string('defaultcoursestyle_desc', 'format_uckk'),
        'standard',
        [
            'standard' => get_string('coursestyle_standard', 'format_uckk'),
            'tronccommun' => get_string('coursestyle_tronccommun', 'format_uckk'),
            'laboratory' => get_string('coursestyle_laboratory', 'format_uckk'),
            'seminar' => get_string('coursestyle_seminar', 'format_uckk'),
            'minimal' => get_string('coursestyle_minimal', 'format_uckk'),
        ]
    ));

    // -------------------------------------------------------------------------
    // Standard UCKK section map.
    // -------------------------------------------------------------------------

    $settings->add(new admin_setting_heading(
        'format_uckk/sectionmap',
        get_string('settings_sectionmap', 'format_uckk'),
        get_string('settings_sectionmap_desc', 'format_uckk')
    ));

    $settings->add(new admin_setting_configcheckbox(
        'format_uckk/lockstandardsections',
        get_string('lockstandardsections', 'format_uckk'),
        get_string('lockstandardsections_desc', 'format_uckk'),
        0
    ));

    $settings->add(new admin_setting_configcheckbox(
        'format_uckk/autocreatestandardsections',
        get_string('autocreatestandardsections', 'format_uckk'),
        get_string('autocreatestandardsections_desc', 'format_uckk'),
        1
    ));

    $settings->add(new admin_setting_configselect(
        'format_uckk/defaultsectionset',
        get_string('defaultsectionset', 'format_uckk'),
        get_string('defaultsectionset_desc', 'format_uckk'),
        'uckk_standard',
        [
            'uckk_standard' => get_string('sectionset_standard', 'format_uckk'),
            'uckk_tronccommun' => get_string('sectionset_tronccommun', 'format_uckk'),
            'uckk_challenge' => get_string('sectionset_challenge', 'format_uckk'),
            'uckk_assembly' => get_string('sectionset_assembly', 'format_uckk'),
            'uckk_archive' => get_string('sectionset_archive', 'format_uckk'),
            'uckk_minimal' => get_string('sectionset_minimal', 'format_uckk'),
        ]
    ));

    $settings->add(new admin_setting_configtextarea(
        'format_uckk/standardsectionkeys',
        get_string('standardsectionkeys', 'format_uckk'),
        get_string('standardsectionkeys_desc', 'format_uckk'),
        "orientation\nconcepts\ncanon\natelier\npreuves\ndeliberation\nlivrable\nevaluation\narchive",
        PARAM_TEXT,
        60,
        9
    ));

    // -------------------------------------------------------------------------
    // Visual indicators.
    // -------------------------------------------------------------------------

    $settings->add(new admin_setting_heading(
        'format_uckk/indicators',
        get_string('settings_indicators', 'format_uckk'),
        get_string('settings_indicators_desc', 'format_uckk')
    ));

    $settings->add(new admin_setting_configcheckbox(
        'format_uckk/showsectionicons',
        get_string('showsectionicons', 'format_uckk'),
        get_string('showsectionicons_desc', 'format_uckk'),
        1
    ));

    $settings->add(new admin_setting_configcheckbox(
        'format_uckk/showcompletionoverview',
        get_string('showcompletionoverview', 'format_uckk'),
        get_string('showcompletionoverview_desc', 'format_uckk'),
        1
    ));

    $settings->add(new admin_setting_configcheckbox(
        'format_uckk/showproofindicators',
        get_string('showproofindicators', 'format_uckk'),
        get_string('showproofindicators_desc', 'format_uckk'),
        1
    ));

    $settings->add(new admin_setting_configcheckbox(
        'format_uckk/showarchiveindicators',
        get_string('showarchiveindicators', 'format_uckk'),
        get_string('showarchiveindicators_desc', 'format_uckk'),
        1
    ));

    $settings->add(new admin_setting_configcheckbox(
        'format_uckk/showintegritymarkers',
        get_string('showintegritymarkers', 'format_uckk'),
        get_string('showintegritymarkers_desc', 'format_uckk'),
        1
    ));

    $settings->add(new admin_setting_configcheckbox(
        'format_uckk/showassemblymarkers',
        get_string('showassemblymarkers', 'format_uckk'),
        get_string('showassemblymarkers_desc', 'format_uckk'),
        1
    ));

    $settings->add(new admin_setting_configcheckbox(
        'format_uckk/showchallengemarkers',
        get_string('showchallengemarkers', 'format_uckk'),
        get_string('showchallengemarkers_desc', 'format_uckk'),
        1
    ));

    // -------------------------------------------------------------------------
    // Course index and navigation.
    // -------------------------------------------------------------------------

    $settings->add(new admin_setting_heading(
        'format_uckk/navigation',
        get_string('settings_navigation', 'format_uckk'),
        get_string('settings_navigation_desc', 'format_uckk')
    ));

    $settings->add(new admin_setting_configcheckbox(
        'format_uckk/showuckknavigation',
        get_string('showuckknavigation', 'format_uckk'),
        get_string('showuckknavigation_desc', 'format_uckk'),
        1
    ));

    $settings->add(new admin_setting_configcheckbox(
        'format_uckk/showcourseindexmetadata',
        get_string('showcourseindexmetadata', 'format_uckk'),
        get_string('showcourseindexmetadata_desc', 'format_uckk'),
        1
    ));

    $settings->add(new admin_setting_configselect(
        'format_uckk/defaultsectiondisplay',
        get_string('defaultsectiondisplay', 'format_uckk'),
        get_string('defaultsectiondisplay_desc', 'format_uckk'),
        'expanded',
        [
            'expanded' => get_string('sectiondisplay_expanded', 'format_uckk'),
            'collapsed' => get_string('sectiondisplay_collapsed', 'format_uckk'),
            'currentfirst' => get_string('sectiondisplay_currentfirst', 'format_uckk'),
        ]
    ));

    $settings->add(new admin_setting_configtext(
        'format_uckk/maxsectiontitlelength',
        get_string('maxsectiontitlelength', 'format_uckk'),
        get_string('maxsectiontitlelength_desc', 'format_uckk'),
        80,
        PARAM_INT
    ));

    // -------------------------------------------------------------------------
    // Evidence, archive and integrity defaults.
    // -------------------------------------------------------------------------

    $settings->add(new admin_setting_heading(
        'format_uckk/evidence',
        get_string('settings_evidence', 'format_uckk'),
        get_string('settings_evidence_desc', 'format_uckk')
    ));

    $settings->add(new admin_setting_configcheckbox(
        'format_uckk/emphasizeproofsection',
        get_string('emphasizeproofsection', 'format_uckk'),
        get_string('emphasizeproofsection_desc', 'format_uckk'),
        1
    ));

    $settings->add(new admin_setting_configcheckbox(
        'format_uckk/emphasizearchivesection',
        get_string('emphasizearchivesection', 'format_uckk'),
        get_string('emphasizearchivesection_desc', 'format_uckk'),
        1
    ));

    $settings->add(new admin_setting_configcheckbox(
        'format_uckk/showprovenancenotice',
        get_string('showprovenancenotice', 'format_uckk'),
        get_string('showprovenancenotice_desc', 'format_uckk'),
        1
    ));

    $settings->add(new admin_setting_configcheckbox(
        'format_uckk/showintegritynotice',
        get_string('showintegritynotice', 'format_uckk'),
        get_string('showintegritynotice_desc', 'format_uckk'),
        1
    ));

    $settings->add(new admin_setting_configcheckbox(
        'format_uckk/showainonsovereignnotice',
        get_string('showainonsovereignnotice', 'format_uckk'),
        get_string('showainonsovereignnotice_desc', 'format_uckk'),
        1
    ));

    // -------------------------------------------------------------------------
    // Optional plugin bridges.
    // -------------------------------------------------------------------------

    $settings->add(new admin_setting_heading(
        'format_uckk/pluginbridges',
        get_string('settings_pluginbridges', 'format_uckk'),
        get_string('settings_pluginbridges_desc', 'format_uckk')
    ));

    $settings->add(new admin_setting_configcheckbox(
        'format_uckk/linktochallengeactivity',
        get_string('linktochallengeactivity', 'format_uckk'),
        get_string('linktochallengeactivity_desc', 'format_uckk'),
        1
    ));

    $settings->add(new admin_setting_configcheckbox(
        'format_uckk/linktoassemblyactivity',
        get_string('linktoassemblyactivity', 'format_uckk'),
        get_string('linktoassemblyactivity_desc', 'format_uckk'),
        1
    ));

    $settings->add(new admin_setting_configcheckbox(
        'format_uckk/linktoarchiveactivity',
        get_string('linktoarchiveactivity', 'format_uckk'),
        get_string('linktoarchiveactivity_desc', 'format_uckk'),
        1
    ));

    $settings->add(new admin_setting_configcheckbox(
        'format_uckk/linktointegritytool',
        get_string('linktointegritytool', 'format_uckk'),
        get_string('linktointegritytool_desc', 'format_uckk'),
        1
    ));

    // -------------------------------------------------------------------------
    // Developer and diagnostic settings.
    // -------------------------------------------------------------------------

    $settings->add(new admin_setting_heading(
        'format_uckk/diagnostics',
        get_string('settings_diagnostics', 'format_uckk'),
        get_string('settings_diagnostics_desc', 'format_uckk')
    ));

    $settings->add(new admin_setting_configcheckbox(
        'format_uckk/showdebugmarkers',
        get_string('showdebugmarkers', 'format_uckk'),
        get_string('showdebugmarkers_desc', 'format_uckk'),
        0
    ));

    $settings->add(new admin_setting_configcheckbox(
        'format_uckk/logformatdiagnostics',
        get_string('logformatdiagnostics', 'format_uckk'),
        get_string('logformatdiagnostics_desc', 'format_uckk'),
        0
    ));
}