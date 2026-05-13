<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

/**
 * Admin settings for the UCKK Seed tool.
 *
 * @package    tool_uckkseed
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $ADMIN->add(
        'tools',
        new admin_category(
            'tool_uckkseed_category',
            new lang_string('pluginname', 'tool_uckkseed')
        )
    );

    $settings = new admin_settingpage(
        'tool_uckkseed_settings',
        new lang_string('settings', 'tool_uckkseed')
    );

    if ($ADMIN->fulltree) {
        // ---------------------------------------------------------------------
        // General settings.
        // ---------------------------------------------------------------------

        $settings->add(new admin_setting_heading(
            'tool_uckkseed/generalheading',
            new lang_string('settings_general', 'tool_uckkseed'),
            new lang_string('settings_general_desc', 'tool_uckkseed')
        ));

        $settings->add(new admin_setting_configcheckbox(
            'tool_uckkseed/enabletool',
            new lang_string('enabletool', 'tool_uckkseed'),
            new lang_string('enabletool_desc', 'tool_uckkseed'),
            1
        ));

        $settings->add(new admin_setting_configselect(
            'tool_uckkseed/defaultmode',
            new lang_string('defaultmode', 'tool_uckkseed'),
            new lang_string('defaultmode_desc', 'tool_uckkseed'),
            'dry_run',
            [
                'dry_run' => new lang_string('mode_dry_run', 'tool_uckkseed'),
                'apply' => new lang_string('mode_apply', 'tool_uckkseed'),
                'report' => new lang_string('mode_report', 'tool_uckkseed'),
                'rollback_plan' => new lang_string('mode_rollback_plan', 'tool_uckkseed'),
            ]
        ));

        $settings->add(new admin_setting_configcheckbox(
            'tool_uckkseed/allowdryrun',
            new lang_string('allowdryrun', 'tool_uckkseed'),
            new lang_string('allowdryrun_desc', 'tool_uckkseed'),
            1
        ));

        $settings->add(new admin_setting_configcheckbox(
            'tool_uckkseed/allowreset',
            new lang_string('allowreset', 'tool_uckkseed'),
            new lang_string('allowreset_desc', 'tool_uckkseed'),
            0
        ));

        $settings->add(new admin_setting_configcheckbox(
            'tool_uckkseed/requireconfirmation',
            new lang_string('requireconfirmation', 'tool_uckkseed'),
            new lang_string('requireconfirmation_desc', 'tool_uckkseed'),
            1
        ));

        // ---------------------------------------------------------------------
        // CLI and automation.
        // ---------------------------------------------------------------------

        $settings->add(new admin_setting_heading(
            'tool_uckkseed/cliheading',
            new lang_string('settings_cli', 'tool_uckkseed'),
            new lang_string('settings_cli_desc', 'tool_uckkseed')
        ));

        $settings->add(new admin_setting_configcheckbox(
            'tool_uckkseed/allowcli',
            new lang_string('allowcli', 'tool_uckkseed'),
            new lang_string('allowcli_desc', 'tool_uckkseed'),
            1
        ));

        $settings->add(new admin_setting_configcheckbox(
            'tool_uckkseed/autoseedoninstall',
            new lang_string('autoseedoninstall', 'tool_uckkseed'),
            new lang_string('autoseedoninstall_desc', 'tool_uckkseed'),
            0
        ));

        // ---------------------------------------------------------------------
        // Presets.
        // ---------------------------------------------------------------------

        $settings->add(new admin_setting_heading(
            'tool_uckkseed/presetsheading',
            new lang_string('settings_presets', 'tool_uckkseed'),
            new lang_string('settings_presets_desc', 'tool_uckkseed')
        ));

        $settings->add(new admin_setting_configtext(
            'tool_uckkseed/presetpath',
            new lang_string('presetpath', 'tool_uckkseed'),
            new lang_string('presetpath_desc', 'tool_uckkseed'),
            'admin/tool/uckkseed/presets',
            PARAM_PATH
        ));

        $settings->add(new admin_setting_configmulticheckbox(
            'tool_uckkseed/enabledpresets',
            new lang_string('enabledpresets', 'tool_uckkseed'),
            new lang_string('enabledpresets_desc', 'tool_uckkseed'),
            [
                'categories' => 1,
                'courses' => 1,
                'cohorts' => 1,
                'roles' => 1,
                'capabilities' => 1,
                'competencies' => 1,
                'badges' => 1,
                'reports' => 1,
                'course_templates' => 1,
                'challenge_templates' => 1,
                'assembly_templates' => 1,
                'archive_templates' => 1,
            ],
            [
                'categories' => new lang_string('preset_categories', 'tool_uckkseed'),
                'courses' => new lang_string('preset_courses', 'tool_uckkseed'),
                'cohorts' => new lang_string('preset_cohorts', 'tool_uckkseed'),
                'roles' => new lang_string('preset_roles', 'tool_uckkseed'),
                'capabilities' => new lang_string('preset_capabilities', 'tool_uckkseed'),
                'competencies' => new lang_string('preset_competencies', 'tool_uckkseed'),
                'badges' => new lang_string('preset_badges', 'tool_uckkseed'),
                'reports' => new lang_string('preset_reports', 'tool_uckkseed'),
                'course_templates' => new lang_string('preset_course_templates', 'tool_uckkseed'),
                'challenge_templates' => new lang_string('preset_challenge_templates', 'tool_uckkseed'),
                'assembly_templates' => new lang_string('preset_assembly_templates', 'tool_uckkseed'),
                'archive_templates' => new lang_string('preset_archive_templates', 'tool_uckkseed'),
            ]
        ));

        // ---------------------------------------------------------------------
        // Safety and retention.
        // ---------------------------------------------------------------------

        $settings->add(new admin_setting_heading(
            'tool_uckkseed/safetyheading',
            new lang_string('settings_safety', 'tool_uckkseed'),
            new lang_string('settings_safety_desc', 'tool_uckkseed')
        ));

        $settings->add(new admin_setting_configtext(
            'tool_uckkseed/logretentiondays',
            new lang_string('logretentiondays', 'tool_uckkseed'),
            new lang_string('logretentiondays_desc', 'tool_uckkseed'),
            365,
            PARAM_INT
        ));

        $settings->add(new admin_setting_configcheckbox(
            'tool_uckkseed/protectnonseededcontent',
            new lang_string('protectnonseededcontent', 'tool_uckkseed'),
            new lang_string('protectnonseededcontent_desc', 'tool_uckkseed'),
            1
        ));

        $settings->add(new admin_setting_configcheckbox(
            'tool_uckkseed/auditlogenabled',
            new lang_string('auditlogenabled', 'tool_uckkseed'),
            new lang_string('auditlogenabled_desc', 'tool_uckkseed'),
            1
        ));
    }

    $ADMIN->add('tool_uckkseed_category', $settings);

    // -------------------------------------------------------------------------
    // External administration pages.
    // -------------------------------------------------------------------------
    //
    // These are navigation targets only. Each target page must still enforce
    // require_login(), system context, sesskey for writes, and its own
    // capability checks.

    $ADMIN->add('tool_uckkseed_category', new admin_externalpage(
        'tool_uckkseed_index',
        new lang_string('seeddistribution', 'tool_uckkseed'),
        new moodle_url('/admin/tool/uckkseed/index.php'),
        'tool/uckkseed:validate'
    ));

    $ADMIN->add('tool_uckkseed_category', new admin_externalpage(
        'tool_uckkseed_seed',
        new lang_string('seeduckk', 'tool_uckkseed'),
        new moodle_url('/admin/tool/uckkseed/index.php', ['action' => 'seed']),
        'tool/uckkseed:seed'
    ));

    $ADMIN->add('tool_uckkseed_category', new admin_externalpage(
        'tool_uckkseed_validate',
        new lang_string('validatedistribution', 'tool_uckkseed'),
        new moodle_url('/admin/tool/uckkseed/index.php', ['action' => 'validate']),
        'tool/uckkseed:validate'
    ));

    $ADMIN->add('tool_uckkseed_category', new admin_externalpage(
        'tool_uckkseed_exportpreset',
        new lang_string('exportpreset', 'tool_uckkseed'),
        new moodle_url('/admin/tool/uckkseed/index.php', ['action' => 'export_preset']),
        'tool/uckkseed:exportpresets'
    ));

    $ADMIN->add('tool_uckkseed_category', new admin_externalpage(
        'tool_uckkseed_reset',
        new lang_string('resetdistribution', 'tool_uckkseed'),
        new moodle_url('/admin/tool/uckkseed/index.php', ['action' => 'reset']),
        'tool/uckkseed:reset'
    ));
}