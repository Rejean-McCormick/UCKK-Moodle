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
     * Save a proof submission draft.
     *
     * @package    mod_uckkchallenge
     * @copyright  2026 Univers-Cité King Klown
     * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
     */
    class save_proof_draft extends external_api {
        public static function execute_parameters(): external_function_parameters {
            return new external_function_parameters([
                'cmid' => new external_value(PARAM_INT, 'Course module id.'),
                'prooftype' => new external_value(PARAM_ALPHAEXT, 'Proof type.'),
                'submissionurl' => new external_value(PARAM_RAW_TRIMMED, 'Optional submission URL.', VALUE_DEFAULT, ''),
                'relationtocriteria' => new external_value(PARAM_RAW, 'Relation to criteria.', VALUE_DEFAULT, ''),
                'provenancestatement' => new external_value(PARAM_RAW, 'Provenance statement.', VALUE_DEFAULT, ''),
                'sourceauthor' => new external_value(PARAM_TEXT, 'Source author.', VALUE_DEFAULT, ''),
                'sourcedate' => new external_value(PARAM_INT, 'Source date timestamp.', VALUE_DEFAULT, 0),
                'visibility' => new external_value(PARAM_ALPHAEXT, 'Visibility.', VALUE_DEFAULT, 'course'),
                'aiassisted' => new external_value(PARAM_BOOL, 'Whether AI assisted.', VALUE_DEFAULT, 0),
                'ailog' => new external_value(PARAM_RAW, 'AI use log.', VALUE_DEFAULT, ''),
                'uncertaintynotes' => new external_value(PARAM_RAW, 'Uncertainty notes.', VALUE_DEFAULT, ''),
                'submissionid' => new external_value(PARAM_INT, 'Existing submission id.', VALUE_DEFAULT, 0),
            ]);
        }

        public static function execute($cmid, $prooftype, $submissionurl = "", $relationtocriteria = "", $provenancestatement = "", $sourceauthor = "", $sourcedate = 0, $visibility = "course", $aiassisted = 0, $ailog = "", $uncertaintynotes = "", $submissionid = 0) : array {
            $params = self::validate_parameters(self::execute_parameters(), [
                'cmid' => $cmid,
                'prooftype' => $prooftype,
                'submissionurl' => $submissionurl,
                'relationtocriteria' => $relationtocriteria,
                'provenancestatement' => $provenancestatement,
                'sourceauthor' => $sourceauthor,
                'sourcedate' => $sourcedate,
                'visibility' => $visibility,
                'aiassisted' => $aiassisted,
                'ailog' => $ailog,
                'uncertaintynotes' => $uncertaintynotes,
                'submissionid' => $submissionid,
            ]);

            [$course, $cm, $context, $challenge] = submission_service::resolve_activity((int)$params['cmid']);
            require_login($course, false, $cm);
            require_capability('mod/uckkchallenge:submitproof', $context);

            $service = new submission_service();
            $result = $service->save_submission($challenge, $cm, $course, $context, $GLOBALS['USER'], [
                'submissionid' => (int)$params['submissionid'],
                'prooftype' => $params['prooftype'],
                'submissionurl' => $params['submissionurl'],
                'relationtocriteria' => $params['relationtocriteria'],
                'provenancestatement' => $params['provenancestatement'],
                'sourceauthor' => $params['sourceauthor'],
                'sourcedate' => (int)$params['sourcedate'],
                'visibility' => $params['visibility'],
                'aiassisted' => !empty($params['aiassisted']) ? 1 : 0,
                'ailog' => $params['ailog'],
                'uncertaintynotes' => $params['uncertaintynotes'],
                'status' => 'draft',
            ]);

            return [
                'status' => 'ok',
                'message' => get_string('submissiondraftsaved', 'uckkchallenge'),
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
