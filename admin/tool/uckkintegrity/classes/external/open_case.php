<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

namespace tool_uckkintegrity\external;

defined('MOODLE_INTERNAL') || die();

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use tool_uckkintegrity\local\confidentiality;
use tool_uckkintegrity\local\integrity_case;
use tool_uckkintegrity\local\severity;

/**
 * External service to open a new integrity case.
 *
 * @package    tool_uckkintegrity
 * @copyright  2026 Univers-Cité King Klown
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class open_case extends external_api {
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'contextid' => new external_value(PARAM_INT, 'Moodle context id'),
            'casetype' => new external_value(PARAM_ALPHANUMEXT, 'Canonical integrity case type'),
            'subjectcomponent' => new external_value(PARAM_COMPONENT, 'Affected plugin/component'),
            'subjectid' => new external_value(PARAM_INT, 'Affected item id'),
            'summary' => new external_value(PARAM_RAW, 'Case summary'),
            'severity' => new external_value(PARAM_ALPHANUMEXT, 'Severity', VALUE_DEFAULT, severity::NORMAL),
            'visibility' => new external_value(PARAM_ALPHANUMEXT, 'Visibility', VALUE_DEFAULT, confidentiality::RESTRICTED),
            'assignedto' => new external_value(PARAM_INT, 'Assigned reviewer user id', VALUE_DEFAULT, 0),
            'parties' => new external_value(PARAM_RAW, 'Parties involved', VALUE_DEFAULT, ''),
            'metadata' => new external_value(PARAM_RAW, 'Optional JSON metadata', VALUE_DEFAULT, ''),
        ]);
    }

    public static function execute(
        int $contextid,
        string $casetype,
        string $subjectcomponent,
        int $subjectid,
        string $summary,
        string $severity = severity::NORMAL,
        string $visibility = confidentiality::RESTRICTED,
        int $assignedto = 0,
        string $parties = '',
        string $metadata = ''
    ): array {
        $params = self::validate_parameters(self::execute_parameters(), [
            'contextid' => $contextid,
            'casetype' => $casetype,
            'subjectcomponent' => $subjectcomponent,
            'subjectid' => $subjectid,
            'summary' => $summary,
            'severity' => $severity,
            'visibility' => $visibility,
            'assignedto' => $assignedto,
            'parties' => $parties,
            'metadata' => $metadata,
        ]);

        $context = \context::instance_by_id($params['contextid'], MUST_EXIST);
        self::validate_context($context);
        require_capability('tool/uckkintegrity:opencase', $context);

        $caseid = integrity_case::create((object) [
            'contextid' => $params['contextid'],
            'casetype' => $params['casetype'],
            'subjectcomponent' => $params['subjectcomponent'],
            'subjectid' => $params['subjectid'],
            'summary' => $params['summary'],
            'severity' => $params['severity'],
            'visibility' => $params['visibility'],
            'assignedto' => $params['assignedto'],
            'parties' => $params['parties'],
            'metadata' => $params['metadata'],
        ]);

        $case = integrity_case::get($caseid);

        return [
            'caseid' => $caseid,
            'status' => $case->status,
            'url' => (new \moodle_url('/admin/tool/uckkintegrity/case.php', ['id' => $caseid]))->out(false),
        ];
    }

    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'caseid' => new external_value(PARAM_INT, 'Created case id'),
            'status' => new external_value(PARAM_ALPHANUMEXT, 'Case status'),
            'url' => new external_value(PARAM_URL, 'Case URL'),
        ]);
    }
}
