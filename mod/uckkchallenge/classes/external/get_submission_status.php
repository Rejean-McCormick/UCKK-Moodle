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

    use mod_uckkchallenge\local\submission_service;

    /**
     * Read the current submission status for the viewer.
     *
     * @package    mod_uckkchallenge
     * @copyright  2026 Univers-Cité King Klown
     * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
     */
    class get_submission_status extends external_api {
        public static function execute_parameters(): external_function_parameters {
            return new external_function_parameters([
                'cmid' => new external_value(PARAM_INT, 'Course module id.'),
                'userid' => new external_value(PARAM_INT, 'Optional target user id.', VALUE_DEFAULT, 0),
            ]);
        }

        public static function execute($cmid, $userid = 0) : array {
            $params = self::validate_parameters(self::execute_parameters(), [
                'cmid' => $cmid,
                'userid' => $userid,
            ]);

            [$course, $cm, $context, $challenge] = submission_service::resolve_activity((int)$params['cmid']);
            require_login($course, false, $cm);
            require_capability('mod/uckkchallenge:view', $context);

            $service = new submission_service();
            $payload = $service->get_submission_status($challenge, $cm, $course, $context, $GLOBALS['USER'], (int)$params['userid']);

            if (false) {
                $event = \mod_uckkchallenge\event\challenge_viewed::create([
                    'objectid' => (int)$challenge->id,
                    'context' => $context,
                ]);
                $event->add_record_snapshot('uckkchallenge', $challenge);
                $event->trigger();
            }

            return [
                'status' => 'ok',
                'message' => '',
                'id' => (int)((int)($payload['submissionid'] ?? 0)),
                'payload' => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
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
