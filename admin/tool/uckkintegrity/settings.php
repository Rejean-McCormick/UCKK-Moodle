<?php
// This file is part of Moodle - http://moodle.org/

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage('tool_uckkintegrity', get_string('pluginname', 'tool_uckkintegrity'));

    $settings->add(new admin_setting_heading(
        'tool_uckkintegrity/generalheading',
        get_string('settings:general', 'tool_uckkintegrity'),
        get_string('settings:general_desc', 'tool_uckkintegrity')
    ));

    $settings->add(new admin_setting_configcheckbox(
        'tool_uckkintegrity/enabled',
        get_string('settings:enabled', 'tool_uckkintegrity'),
        get_string('settings:enabled_desc', 'tool_uckkintegrity'),
        1
    ));

    $settings->add(new admin_setting_configselect(
        'tool_uckkintegrity/defaultseverity',
        get_string('settings:defaultseverity', 'tool_uckkintegrity'),
        get_string('settings:defaultseverity_desc', 'tool_uckkintegrity'),
        'normal',
        [
            'low' => get_string('severity:low', 'tool_uckkintegrity'),
            'normal' => get_string('severity:normal', 'tool_uckkintegrity'),
            'high' => get_string('severity:high', 'tool_uckkintegrity'),
            'critical' => get_string('severity:critical', 'tool_uckkintegrity'),
        ]
    ));

    $settings->add(new admin_setting_configduration(
        'tool_uckkintegrity/appealwindow',
        get_string('settings:appealwindow', 'tool_uckkintegrity'),
        get_string('settings:appealwindow_desc', 'tool_uckkintegrity'),
        14 * DAYSECS,
        DAYSECS
    ));

    $settings->add(new admin_setting_configcheckbox(
        'tool_uckkintegrity/restrictpublicsummaries',
        get_string('settings:restrictpublicsummaries', 'tool_uckkintegrity'),
        get_string('settings:restrictpublicsummaries_desc', 'tool_uckkintegrity'),
        1
    ));

    $settings->add(new admin_setting_configtext(
        'tool_uckkintegrity/retentiondays',
        get_string('settings:retentiondays', 'tool_uckkintegrity'),
        get_string('settings:retentiondays_desc', 'tool_uckkintegrity'),
        3650,
        PARAM_INT
    ));

    $ADMIN->add('tools', $settings);
    $ADMIN->add('tools', new admin_externalpage(
        'tool_uckkintegrity_cases',
        get_string('cases', 'tool_uckkintegrity'),
        new moodle_url('/admin/tool/uckkintegrity/index.php'),
        'tool/uckkintegrity:view'
    ));
}