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
     * Evaluate proof submission.
     *
     * @package    mod_uckkchallenge
     * @copyright  2026 Univers-Cité King Klown
     * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
     */
    class evaluate_proof extends external_api {
        public static function execute_parameters(): external_function_parameters {
            return new external_function_parameters([
                'cmid' => new external_value(PARAM_INT, 'Course module id.'),
                'submissionid' => new external_value(PARAM_INT, 'Submission id.'),
                'decision' => new external_value(PARAM_ALPHAEXT, 'Evaluation decision.'),
                'mentorfeedback' => new external_value(PARAM_RAW, 'Mentor feedback.', VALUE_DEFAULT, ''),
                'privatefeedback' => new external_value(PARAM_RAW, 'Private feedback.', VALUE_DEFAULT, ''),
                'validationstate' => new external_value(PARAM_ALPHAEXT, 'Validation state.', VALUE_DEFAULT, 'unverified'),
                'grade' => new external_value(PARAM_FLOAT, 'Numeric grade.', VALUE_DEFAULT, 0),
                'rubricjson' => new external_value(PARAM_RAW, 'Rubric JSON.', VALUE_DEFAULT, ''),
                'requestintegrityreview' => new external_value(PARAM_BOOL, 'Whether integrity review is requested.', VALUE_DEFAULT, 0),
                'requestarchiveexport' => new external_value(PARAM_BOOL, 'Whether archive export is requested.', VALUE_DEFAULT, 0),
            ]);
        }

        public static function execute($cmid, $submissionid, $decision, $mentorfeedback = "", $privatefeedback = "", $validationstate = "unverified", $grade = 0, $rubricjson = "", $requestintegrityreview = 0, $requestarchiveexport = 0) : array {
            $params = self::validate_parameters(self::execute_parameters(), [
                'cmid' => $cmid,
                'submissionid' => $submissionid,
                'decision' => $decision,
                'mentorfeedback' => $mentorfeedback,
                'privatefeedback' => $privatefeedback,
                'validationstate' => $validationstate,
                'grade' => $grade,
                'rubricjson' => $rubricjson,
                'requestintegrityreview' => $requestintegrityreview,
                'requestarchiveexport' => $requestarchiveexport,
            ]);

            [$course, $cm, $context, $challenge] = submission_service::resolve_activity((int)$params['cmid']);
            require_login($course, false, $cm);
            require_capability('mod/uckkchallenge:evaluate', $context);

            $service = new submission_service();
            $result = $service->evaluate_proof($challenge, $cm, $course, $context, $GLOBALS['USER'], (int)$params['submissionid'], [
                'decision' => $params['decision'],
                'mentorfeedback' => $params['mentorfeedback'],
                'privatefeedback' => $params['privatefeedback'],
                'validationstate' => $params['validationstate'],
                'grade' => $params['grade'],
                'rubricjson' => $params['rubricjson'],
                'requestintegrityreview' => !empty($params['requestintegrityreview']),
                'requestarchiveexport' => !empty($params['requestarchiveexport']),
            ]);

            return [
                'status' => 'ok',
                'message' => get_string('evaluationsaved', 'uckkchallenge'),
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
