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
 * External function returning one player profile.
 *
 * @package    local_uckk
 */
final class get_player_profile extends external_api {
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'userid' => new external_value(PARAM_INT, 'Target user id', VALUE_DEFAULT, 0),
            'viewerid' => new external_value(PARAM_INT, 'Viewer user id', VALUE_DEFAULT, 0),
            'contextid' => new external_value(PARAM_INT, 'Context id', VALUE_DEFAULT, 0),
        ]);
    }

    public static function execute(int $userid = 0, int $viewerid = 0, int $contextid = 0): array {
        global $USER;

        $params = self::validate_parameters(self::execute_parameters(), [
            'userid' => $userid,
            'viewerid' => $viewerid,
            'contextid' => $contextid,
        ]);
        $context = $params['contextid'] > 0 ? context::instance_by_id($params['contextid']) : context_system::instance();
        self::validate_context($context);

        $userid = $params['userid'] > 0 ? $params['userid'] : (int)$USER->id;
        $viewerid = $params['viewerid'] > 0 ? $params['viewerid'] : (int)$USER->id;
        $profile = (new profile_service())->get_profile_for_viewer($userid, $viewerid);

        return [
            'id' => (int)$profile->id,
            'userid' => (int)$profile->userid,
            'displayname' => (string)$profile->displayname,
            'displaytitle' => (string)$profile->displaytitle,
            'symbolicrole' => (string)$profile->symbolicrole,
            'symbolicroles' => array_map('strval', $profile->symbolicroles),
            'status' => (string)$profile->status,
            'visibility' => (string)$profile->visibility,
            'provenance' => (string)$profile->provenance,
            'versionno' => (int)$profile->versionno,
            'activepathwayids' => array_map('intval', $profile->activepathwayids),
        ];
    }

    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'id' => new external_value(PARAM_INT, 'Profile id'),
            'userid' => new external_value(PARAM_INT, 'User id'),
            'displayname' => new external_value(PARAM_TEXT, 'Display name'),
            'displaytitle' => new external_value(PARAM_TEXT, 'Display title'),
            'symbolicrole' => new external_value(PARAM_ALPHANUMEXT, 'Primary symbolic role'),
            'symbolicroles' => new external_multiple_structure(new external_value(PARAM_ALPHANUMEXT, 'Symbolic role')),
            'status' => new external_value(PARAM_ALPHANUMEXT, 'Status'),
            'visibility' => new external_value(PARAM_ALPHANUMEXT, 'Visibility'),
            'provenance' => new external_value(PARAM_ALPHANUMEXT, 'Provenance'),
            'versionno' => new external_value(PARAM_INT, 'Version'),
            'activepathwayids' => new external_multiple_structure(new external_value(PARAM_INT, 'Active pathway id')),
        ]);
    }
}
