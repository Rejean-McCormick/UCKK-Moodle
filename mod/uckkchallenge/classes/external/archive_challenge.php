<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

declare(strict_types=1);

namespace mod_uckkchallenge\external;


use external_api;
use external_function_parameters;
use external_multiple_structure;
use external_single_structure;
use external_value;
use external_warnings;

defined('MOODLE_INTERNAL') || die();

    use mod_uckkchallenge\local\archive_service;

    /**
     * Archive a validated challenge.
     *
     * @package    mod_uckkchallenge
     * @copyright  2026 Univers-Cité King Klown
     * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
     */
    class archive_challenge extends external_api {
        public static function execute_parameters(): external_function_parameters {
            return new external_function_parameters([
                'cmid' => new external_value(PARAM_INT, 'Course module id.'),
                'visibility' => new external_value(PARAM_ALPHAEXT, 'Archive visibility.', VALUE_DEFAULT, 'course'),
                'reason' => new external_value(PARAM_TEXT, 'Archive reason.', VALUE_DEFAULT, ''),
            ]);
        }

        public static function execute($cmid, $visibility = "course", $reason = "") : array {
            $params = self::validate_parameters(self::execute_parameters(), [
                'cmid' => $cmid,
                'visibility' => $visibility,
                'reason' => $reason,
            ]);

            [$course, $cm, $context, $challenge] = submission_service::resolve_activity((int)$params['cmid']);
            require_login($course, false, $cm);
            require_capability('mod/uckkchallenge:archive', $context);

            $service = new archive_service();
            $result = $service->archive_challenge($challenge, $cm, $course, $context, $GLOBALS['USER'], [
                'visibility' => $params['visibility'],
                'reason' => $params['reason'],
            ]);

            return [
                'status' => 'ok',
                'message' => get_string('challengearchived', 'uckkchallenge'),
                'id' => (int)($result->id),
                'payload' => json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'warnings' => [],
            ];
        }

        public static function execute_returns(): external_single_structure {
            return new external_single_structure([
                'status' => new external_value(PARAM_ALPHA, 'Execution status.'),
                'message' => new external_value(PARAM_TEXT, 'Optional message.', VALUE_DEFAULT, ''),
                'id' => new external_value(PARAM_INT, 'Primary record id.', VALUE_DEFAULT, 0),
                'payload' => new external_value(PARAM_RAW, 'JSON encoded payload.', VALUE_DEFAULT, ''),
                'warnings' => new external_warnings(),
            ]);
        }
    }
