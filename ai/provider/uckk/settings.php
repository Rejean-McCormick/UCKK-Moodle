<?php
// This file is part of Moodle - http://moodle.org/

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage(
        'aiprovider_uckk',
        get_string('pluginname', 'aiprovider_uckk'),
        'aiprovider/uckk:configure'
    );

    $settings->add(new admin_setting_heading(
        'aiprovider_uckk/generalheading',
        get_string('settings:general', 'aiprovider_uckk'),
        get_string('settings:general_desc', 'aiprovider_uckk')
    ));

    $settings->add(new admin_setting_configcheckbox(
        'aiprovider_uckk/enable_provider',
        get_string('settings:enable_provider', 'aiprovider_uckk'),
        get_string('settings:enable_provider_desc', 'aiprovider_uckk'),
        0
    ));

    $settings->add(new admin_setting_configtext(
        'aiprovider_uckk/provider_endpoint',
        get_string('settings:provider_endpoint', 'aiprovider_uckk'),
        get_string('settings:provider_endpoint_desc', 'aiprovider_uckk'),
        '',
        PARAM_URL,
        80
    ));

    $settings->add(new admin_setting_configtext(
        'aiprovider_uckk/provider_model',
        get_string('settings:provider_model', 'aiprovider_uckk'),
        get_string('settings:provider_model_desc', 'aiprovider_uckk'),
        '',
        PARAM_TEXT,
        50
    ));

    $settings->add(new admin_setting_configpasswordunmask(
        'aiprovider_uckk/provider_apikey',
        get_string('settings:provider_apikey', 'aiprovider_uckk'),
        get_string('settings:provider_apikey_desc', 'aiprovider_uckk'),
        ''
    ));

    $settings->add(new admin_setting_heading(
        'aiprovider_uckk/governanceheading',
        get_string('settings:governance', 'aiprovider_uckk'),
        get_string('settings:governance_desc', 'aiprovider_uckk')
    ));

    $settings->add(new admin_setting_configcheckbox(
        'aiprovider_uckk/log_prompts',
        get_string('settings:log_prompts', 'aiprovider_uckk'),
        get_string('settings:log_prompts_desc', 'aiprovider_uckk'),
        1
    ));

    $settings->add(new admin_setting_configcheckbox(
        'aiprovider_uckk/log_responses',
        get_string('settings:log_responses', 'aiprovider_uckk'),
        get_string('settings:log_responses_desc', 'aiprovider_uckk'),
        1
    ));

    $settings->add(new admin_setting_configcheckbox(
        'aiprovider_uckk/redact_user_data_before_send',
        get_string('settings:redact_user_data_before_send', 'aiprovider_uckk'),
        get_string('settings:redact_user_data_before_send_desc', 'aiprovider_uckk'),
        1
    ));

    $settings->add(new admin_setting_configcheckbox(
        'aiprovider_uckk/allow_in_integrity_contexts',
        get_string('settings:allow_in_integrity_contexts', 'aiprovider_uckk'),
        get_string('settings:allow_in_integrity_contexts_desc', 'aiprovider_uckk'),
        0
    ));

    $settings->add(new admin_setting_configcheckbox(
        'aiprovider_uckk/allow_in_public_challenges',
        get_string('settings:allow_in_public_challenges', 'aiprovider_uckk'),
        get_string('settings:allow_in_public_challenges_desc', 'aiprovider_uckk'),
        0
    ));

    $settings->add(new admin_setting_configcheckbox(
        'aiprovider_uckk/require_non_authority_label',
        get_string('settings:require_non_authority_label', 'aiprovider_uckk'),
        get_string('settings:require_non_authority_label_desc', 'aiprovider_uckk'),
        1
    ));

    $settings->add(new admin_setting_heading(
        'aiprovider_uckk/limitsheading',
        get_string('settings:limits', 'aiprovider_uckk'),
        get_string('settings:limits_desc', 'aiprovider_uckk')
    ));

    $settings->add(new admin_setting_configtext(
        'aiprovider_uckk/max_tokens',
        get_string('settings:max_tokens', 'aiprovider_uckk'),
        get_string('settings:max_tokens_desc', 'aiprovider_uckk'),
        2048,
        PARAM_INT,
        10
    ));

    $settings->add(new admin_setting_configduration(
        'aiprovider_uckk/retention_days',
        get_string('settings:retention_days', 'aiprovider_uckk'),
        get_string('settings:retention_days_desc', 'aiprovider_uckk'),
        90 * DAYSECS,
        DAYSECS
    ));

    $settings->add(new admin_setting_configtext(
        'aiprovider_uckk/request_timeout',
        get_string('settings:request_timeout', 'aiprovider_uckk'),
        get_string('settings:request_timeout_desc', 'aiprovider_uckk'),
        30,
        PARAM_INT,
        10
    ));

    $settings->add(new admin_setting_heading(
        'aiprovider_uckk/actionsheading',
        get_string('settings:actions', 'aiprovider_uckk'),
        get_string('settings:actions_desc', 'aiprovider_uckk')
    ));

    $actions = [
        'summarise_course_material',
        'map_problem',
        'extract_uncertainties',
        'draft_reflection',
        'summarise_assembly',
        'critique_ai_output',
        'prepare_integrity_review',
    ];

    foreach ($actions as $action) {
        $settings->add(new admin_setting_configcheckbox(
            'aiprovider_uckk/action_' . $action,
            get_string('settings:action_' . $action, 'aiprovider_uckk'),
            get_string('settings:action_' . $action . '_desc', 'aiprovider_uckk'),
            1
        ));
    }

    $ADMIN->add('aiproviders', $settings);
}