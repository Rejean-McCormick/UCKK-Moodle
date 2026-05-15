<?php
// This file is part of Moodle - https://moodle.org/

namespace local_uckk\external;

use context;
use external_api;
use external_function_parameters;
use external_single_structure;
use external_value;
use local_uckk\service\pathway_service;

defined('MOODLE_INTERNAL') || die();

/**
 * External function returning one pathway card context.
 *
 * @package    local_uckk
 */
final class get_pathway_map extends external_api {
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'contextid' => new external_value(PARAM_INT, 'Context id'),
            'userid' => new external_value(PARAM_INT, 'User id', VALUE_DEFAULT, 0),
            'pathwayid' => new external_value(PARAM_INT, 'Pathway id'),
            'includecompleted' => new external_value(PARAM_BOOL, 'Include completed items', VALUE_DEFAULT, true),
        ]);
    }

    public static function execute(int $contextid, int $userid = 0, int $pathwayid = 0, bool $includecompleted = true): array {
        $params = self::validate_parameters(self::execute_parameters(), [
            'contextid' => $contextid,
            'userid' => $userid,
            'pathwayid' => $pathwayid,
            'includecompleted' => $includecompleted,
        ]);

        $context = context::instance_by_id($params['contextid']);
        self::validate_context($context);
        require_capability('local/uckk:viewcampus', $context);

        return (new pathway_service())->get_pathway_map($params['pathwayid'], $params['userid'], $params['includecompleted']);
    }

    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'templatecontext' => new external_single_structure([
                'id' => new external_value(PARAM_INT, 'Pathway id'),
                'shortname' => new external_value(PARAM_ALPHANUMEXT, 'Shortname'),
                'fullname' => new external_value(PARAM_TEXT, 'Full name'),
                'programname' => new external_value(PARAM_TEXT, 'Program name', VALUE_DEFAULT, ''),
                'status' => new external_value(PARAM_ALPHANUMEXT, 'Status'),
                'statuslabel' => new external_value(PARAM_TEXT, 'Status label'),
                'statusclass' => new external_value(PARAM_TEXT, 'Status CSS class'),
                'progresspercent' => new external_value(PARAM_INT, 'Progress percent'),
                'progresslabel' => new external_value(PARAM_TEXT, 'Progress label'),
                'coursescompleted' => new external_value(PARAM_INT, 'Completed courses'),
                'coursestotal' => new external_value(PARAM_INT, 'Total courses'),
                'competenciesachieved' => new external_value(PARAM_INT, 'Achieved competencies'),
                'competenciestotal' => new external_value(PARAM_INT, 'Total competencies'),
                'badgesearned' => new external_value(PARAM_INT, 'Earned badges'),
                'badgestotal' => new external_value(PARAM_INT, 'Total badges'),
                'url' => new external_value(PARAM_URL, 'Details URL'),
                'hasdescription' => new external_value(PARAM_BOOL, 'Has description'),
                'description' => new external_value(PARAM_RAW, 'Description', VALUE_DEFAULT, ''),
                'hasnextaction' => new external_value(PARAM_BOOL, 'Has next action'),
                'nextactionlabel' => new external_value(PARAM_TEXT, 'Next action label', VALUE_DEFAULT, ''),
                'nextactionurl' => new external_value(PARAM_RAW, 'Next action URL', VALUE_DEFAULT, ''),
                'hasintegritynotice' => new external_value(PARAM_BOOL, 'Has integrity notice'),
                'integritynotice' => new external_value(PARAM_TEXT, 'Integrity notice', VALUE_DEFAULT, ''),
                'hasrequirements' => new external_value(PARAM_BOOL, 'Has requirements'),
                'requirements' => new \external_multiple_structure(new external_single_structure([
                    'label' => new external_value(PARAM_TEXT, 'Requirement label'),
                    'value' => new external_value(PARAM_TEXT, 'Requirement value'),
                    'complete' => new external_value(PARAM_BOOL, 'Whether complete'),
                ])),
                'statusbadge' => new external_single_structure([
                    'status' => new external_value(PARAM_ALPHANUMEXT, 'Badge status'),
                    'label' => new external_value(PARAM_TEXT, 'Badge label'),
                ]),
            ]),
        ]);
    }
}
