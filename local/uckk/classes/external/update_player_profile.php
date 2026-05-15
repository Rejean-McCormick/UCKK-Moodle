<?php
// This file is part of Moodle - https://moodle.org/

namespace local_uckk\external;

use context;
use context_system;
use external_api;
use external_function_parameters;
use external_multiple_structure;
use external_single_structure;
use external_value;
use local_uckk\service\profile_service;

defined('MOODLE_INTERNAL') || die();

/**
 * External function updating one player profile.
 *
 * @package    local_uckk
 */
final class update_player_profile extends external_api {
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'userid' => new external_value(PARAM_INT, 'Target user id'),
            'contextid' => new external_value(PARAM_INT, 'Context id', VALUE_DEFAULT, 0),
            'fields' => new external_multiple_structure(new external_single_structure([
                'name' => new external_value(PARAM_ALPHANUMEXT, 'Field name'),
                'value' => new external_value(PARAM_RAW, 'Field value'),
            ])),
        ]);
    }

    public static function execute(int $userid, int $contextid = 0, array $fields = []): array {
        $params = self::validate_parameters(self::execute_parameters(), [
            'userid' => $userid,
            'contextid' => $contextid,
            'fields' => $fields,
        ]);

        $context = $params['contextid'] > 0 ? context::instance_by_id($params['contextid']) : context_system::instance();
        self::validate_context($context);

        $payload = [];
        foreach ($params['fields'] as $field) {
            $payload[$field['name']] = $field['value'];
        }

        if (isset($payload['symbolicroles']) && !isset($payload['symbolicrole']) && is_array($payload['symbolicroles']) && !empty($payload['symbolicroles'])) {
            $payload['symbolicrole'] = reset($payload['symbolicroles']);
        }
        if (!isset($payload['symbolicrole']) && isset($payload['symbolicrole'])) {
            $payload['symbolicrole'] = (string)$payload['symbolicrole'];
        }

        $profile = (new profile_service())->create_or_update_profile($params['userid'], $payload);

        return [
            'message' => get_string('profilesaved', 'local_uckk'),
            'profileid' => (int)$profile->id,
            'versionno' => (int)$profile->versionno,
        ];
    }

    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'message' => new external_value(PARAM_TEXT, 'Status message'),
            'profileid' => new external_value(PARAM_INT, 'Profile id'),
            'versionno' => new external_value(PARAM_INT, 'Version number'),
        ]);
    }
}
