<?php
// This file is part of Moodle - http://moodle.org/

defined('MOODLE_INTERNAL') || die();

$systemcontext = context_system::instance();

if ($hassiteconfig || has_capability('report/uckk:view', $systemcontext)) {
    $ADMIN->add('reports', new admin_externalpage(
        'report_uckk_index',
        get_string('pluginname', 'report_uckk'),
        new moodle_url('/report/uckk/index.php'),
        'report/uckk:view'
    ));
}

if ($hassiteconfig) {
    $settings = new admin_settingpage(
        'report_uckk_settings',
        get_string('settings', 'report_uckk')
    );

    $settings->add(new admin_setting_configcheckbox(
        'report_uckk/allowjsonexport',
        get_string('settings:allowjsonexport', 'report_uckk'),
        get_string('settings:allowjsonexport_desc', 'report_uckk'),
        1
    ));

    $settings->add(new admin_setting_configcheckbox(
        'report_uckk/showemptyreports',
        get_string('settings:showemptyreports', 'report_uckk'),
        get_string('settings:showemptyreports_desc', 'report_uckk'),
        1
    ));

    $settings->add(new admin_setting_configtext(
        'report_uckk/defaultlimit',
        get_string('settings:defaultlimit', 'report_uckk'),
        get_string('settings:defaultlimit_desc', 'report_uckk'),
        100,
        PARAM_INT
    ));

    $ADMIN->add('reports', $settings);
}