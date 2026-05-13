<?php
// This file is part of UCKK-Moodle.
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.
//
// UCKK-Moodle adapts Moodle as the pedagogical campus of the
// Univers-Cité King Klown.

/**
 * External function: get UCKK pathway status.
 *
 * This class exposes a read-only web service endpoint for retrieving the
 * pathway status of a Joueur.
 *
 * The external function is intentionally thin:
 *
 * - it declares Moodle web service parameters and return structures;
 * - it validates parameters;
 * - it validates Moodle context;
 * - it enforces access rules for own vs other-user pathway status;
 * - it delegates business logic to local_uckk\api\pathway_api.
 *
 * It must not duplicate pathway calculation logic, badge logic, competency
 * logic, course completion logic, archive validation, integrity decisions,
 * or program registry logic.
 *
 * @package    local_uckk
 * @copyright  2026 Univers-Cité King Klown
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_uckk\external;

use context;
use context_system;
use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;
use invalid_parameter_exception;
use local_uckk\api\pathway_api;
use moodle_exception;

defined('MOODLE_INTERNAL') || die();

/**
 * External function class for pathway status retrieval.
 *
 * Web service name recommendation in db/services.php:
 *
 * local_uckk_get_pathway_status
 *
 * @package local_uckk
 */
final class get_pathway_status extends external_api {
    /** Capability required to view the UCKK campus. */
    private const CAP_VIEW_CAMPUS = 'local/uckk:viewcampus';

    /** Capability required to manage pathways. */
    private const CAP_MANAGE_PATHWAYS = 'local/uckk:managepathways';

    /** Capability required to view broader reports. */
    private const CAP_VIEW_REPORTS = 'local/uckk:viewreports';

    /**
     * Define parameters for the external function.
     *
     * Top-level optional values are avoided. Optional inputs are nested inside
     * the required "request" structure, which is safer and aligns with Moodle
     * external API expectations.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'request' => new external_single_structure([
                'userid' => new external_value(
                    PARAM_INT,
                    'User id to inspect. Use 0 for the current user.',
                    VALUE_DEFAULT,
                    0
                ),
                'pathwayid' => new external_value(
                    PARAM_INT,
                    'UCKK pathway id. Use 0 when resolving by pathway shortname.',
                    VALUE_DEFAULT,
                    0
                ),
                'pathwayshortname' => new external_value(
                    PARAM_ALPHANUMEXT,
                    'UCKK pathway shortname. Used when pathwayid is 0.',
                    VALUE_DEFAULT,
                    ''
                ),
                'contextid' => new external_value(
                    PARAM_INT,
                    'Moodle context id. Use 0 for system context.',
                    VALUE_DEFAULT,
                    0
                ),
                'includeprogram' => new external_value(
                    PARAM_BOOL,
                    'Include program summary.',
                    VALUE_DEFAULT,
                    true
                ),
                'includecourses' => new external_value(
                    PARAM_BOOL,
                    'Include course completion items.',
                    VALUE_DEFAULT,
                    true
                ),
                'includecompetencies' => new external_value(
                    PARAM_BOOL,
                    'Include competency status items.',
                    VALUE_DEFAULT,
                    true
                ),
                'includebadges' => new external_value(
                    PARAM_BOOL,
                    'Include badge status items.',
                    VALUE_DEFAULT,
                    true
                ),
                'includeportfolio' => new external_value(
                    PARAM_BOOL,
                    'Include portfolio summary.',
                    VALUE_DEFAULT,
                    true
                ),
                'includearchives' => new external_value(
                    PARAM_BOOL,
                    'Include archive references if permitted.',
                    VALUE_DEFAULT,
                    false
                ),
                'includeintegrity' => new external_value(
                    PARAM_BOOL,
                    'Include integrity summary if permitted.',
                    VALUE_DEFAULT,
                    false
                ),
            ], 'Pathway status request.'),
        ]);
    }

    /**
     * Execute the external function.
     *
     * @param array $request Request structure.
     * @return array<string, mixed>
     */
    public static function execute(array $request): array {
        global $USER;

        [
            'request' => $request,
        ] = self::validate_parameters(self::execute_parameters(), [
            'request' => $request,
        ]);

        $userid = (int)$request['userid'];
        if ($userid <= 0) {
            $userid = (int)$USER->id;
        }

        $pathwayid = (int)$request['pathwayid'];
        $pathwayshortname = trim((string)$request['pathwayshortname']);
        $contextid = (int)$request['contextid'];

        if ($pathwayid <= 0 && $pathwayshortname === '') {
            throw new invalid_parameter_exception('Either pathwayid or pathwayshortname must be provided.');
        }

        if ($userid <= 0) {
            throw new invalid_parameter_exception('Invalid user id.');
        }

        $context = self::resolve_context($contextid);
        self::validate_context($context);
        self::require_access($context, $userid);

        $options = [
            'includeprogram' => (bool)$request['includeprogram'],
            'includecourses' => (bool)$request['includecourses'],
            'includecompetencies' => (bool)$request['includecompetencies'],
            'includebadges' => (bool)$request['includebadges'],
            'includeportfolio' => (bool)$request['includeportfolio'],
            'includearchives' => (bool)$request['includearchives'],
            'includeintegrity' => (bool)$request['includeintegrity'],
        ];

        if (!class_exists(pathway_api::class)) {
            throw new moodle_exception('missingpathwayapi', 'local_uckk');
        }

        if ($pathwayid > 0) {
            $status = pathway_api::get_user_pathway_status(
                $userid,
                $pathwayid,
                $options,
                $context
            );
        } else {
            $status = pathway_api::get_user_pathway_status_by_shortname(
                $userid,
                $pathwayshortname,
                $options,
                $context
            );
        }

        return self::normalise_status_response($status, $userid);
    }

    /**
     * Define return values for the external function.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'success' => new external_value(
                PARAM_BOOL,
                'Whether the request completed successfully.'
            ),
            'message' => new external_value(
                PARAM_TEXT,
                'Human-readable status message.',
                VALUE_DEFAULT,
                ''
            ),
            'userid' => new external_value(
                PARAM_INT,
                'User id.'
            ),
            'pathway' => new external_single_structure([
                'id' => new external_value(PARAM_INT, 'Pathway id.'),
                'shortname' => new external_value(PARAM_ALPHANUMEXT, 'Pathway shortname.'),
                'fullname' => new external_value(PARAM_TEXT, 'Pathway full name.'),
                'status' => new external_value(PARAM_ALPHANUMEXT, 'Pathway status.'),
                'visibility' => new external_value(PARAM_ALPHANUMEXT, 'Pathway visibility.'),
                'programid' => new external_value(PARAM_INT, 'Related program id.', VALUE_DEFAULT, 0),
                'programshortname' => new external_value(PARAM_ALPHANUMEXT, 'Related program shortname.', VALUE_DEFAULT, ''),
                'programfullname' => new external_value(PARAM_TEXT, 'Related program full name.', VALUE_DEFAULT, ''),
            ], 'Pathway summary.'),
            'progress' => new external_single_structure([
                'status' => new external_value(PARAM_ALPHANUMEXT, 'Computed pathway progress status.'),
                'completionpercent' => new external_value(PARAM_FLOAT, 'Completion percentage from 0 to 100.'),
                'requiredcount' => new external_value(PARAM_INT, 'Total required item count.'),
                'completedcount' => new external_value(PARAM_INT, 'Completed required item count.'),
                'pendingcount' => new external_value(PARAM_INT, 'Pending required item count.'),
                'blockedcount' => new external_value(PARAM_INT, 'Blocked required item count.'),
                'lastupdated' => new external_value(PARAM_INT, 'Timestamp of latest relevant update.', VALUE_DEFAULT, 0),
            ], 'Progress summary.'),
            'program' => new external_single_structure([
                'included' => new external_value(PARAM_BOOL, 'Whether program data is included.'),
                'id' => new external_value(PARAM_INT, 'Program id.', VALUE_DEFAULT, 0),
                'shortname' => new external_value(PARAM_ALPHANUMEXT, 'Program shortname.', VALUE_DEFAULT, ''),
                'fullname' => new external_value(PARAM_TEXT, 'Program full name.', VALUE_DEFAULT, ''),
                'programtype' => new external_value(PARAM_ALPHANUMEXT, 'Program type.', VALUE_DEFAULT, ''),
                'summary' => new external_value(PARAM_RAW, 'Program summary.', VALUE_DEFAULT, ''),
                'internalnotice' => new external_value(
                    PARAM_TEXT,
                    'Notice that UCKK recognitions are internal unless formally recognised.',
                    VALUE_DEFAULT,
                    ''
                ),
            ], 'Program data.'),
            'courses' => new external_multiple_structure(
                new external_single_structure([
                    'id' => new external_value(PARAM_INT, 'Course id.'),
                    'shortname' => new external_value(PARAM_TEXT, 'Course shortname.'),
                    'fullname' => new external_value(PARAM_TEXT, 'Course full name.'),
                    'idnumber' => new external_value(PARAM_TEXT, 'Course idnumber.', VALUE_DEFAULT, ''),
                    'required' => new external_value(PARAM_BOOL, 'Whether the course is required.'),
                    'completed' => new external_value(PARAM_BOOL, 'Whether the course is completed.'),
                    'completionstate' => new external_value(PARAM_ALPHANUMEXT, 'Completion state.', VALUE_DEFAULT, ''),
                    'completiontime' => new external_value(PARAM_INT, 'Completion timestamp.', VALUE_DEFAULT, 0),
                    'url' => new external_value(PARAM_URL, 'Course URL.', VALUE_DEFAULT, ''),
                ]),
                'Course status items.'
            ),
            'competencies' => new external_multiple_structure(
                new external_single_structure([
                    'id' => new external_value(PARAM_INT, 'Competency id.'),
                    'idnumber' => new external_value(PARAM_TEXT, 'Competency idnumber.'),
                    'shortname' => new external_value(PARAM_TEXT, 'Competency shortname.', VALUE_DEFAULT, ''),
                    'fullname' => new external_value(PARAM_TEXT, 'Competency full name.'),
                    'required' => new external_value(PARAM_BOOL, 'Whether the competency is required.'),
                    'proficient' => new external_value(PARAM_BOOL, 'Whether the user is proficient.'),
                    'grade' => new external_value(PARAM_FLOAT, 'Competency grade.', VALUE_DEFAULT, 0),
                    'status' => new external_value(PARAM_ALPHANUMEXT, 'Competency status.', VALUE_DEFAULT, ''),
                ]),
                'Competency status items.'
            ),
            'badges' => new external_multiple_structure(
                new external_single_structure([
                    'id' => new external_value(PARAM_INT, 'Badge id.'),
                    'name' => new external_value(PARAM_TEXT, 'Badge name.'),
                    'required' => new external_value(PARAM_BOOL, 'Whether the badge is required.'),
                    'awarded' => new external_value(PARAM_BOOL, 'Whether the badge has been awarded.'),
                    'dateissued' => new external_value(PARAM_INT, 'Issue timestamp.', VALUE_DEFAULT, 0),
                    'imageurl' => new external_value(PARAM_URL, 'Badge image URL.', VALUE_DEFAULT, ''),
                ]),
                'Badge status items.'
            ),
            'portfolio' => new external_single_structure([
                'included' => new external_value(PARAM_BOOL, 'Whether portfolio data is included.'),
                'itemcount' => new external_value(PARAM_INT, 'Number of portfolio items.', VALUE_DEFAULT, 0),
                'validatedcount' => new external_value(PARAM_INT, 'Number of validated portfolio items.', VALUE_DEFAULT, 0),
                'pendingcount' => new external_value(PARAM_INT, 'Number of pending portfolio items.', VALUE_DEFAULT, 0),
                'url' => new external_value(PARAM_URL, 'Portfolio URL.', VALUE_DEFAULT, ''),
            ], 'Portfolio summary.'),
            'archives' => new external_multiple_structure(
                new external_single_structure([
                    'id' => new external_value(PARAM_INT, 'Archive item id.'),
                    'title' => new external_value(PARAM_TEXT, 'Archive item title.'),
                    'itemtype' => new external_value(PARAM_ALPHANUMEXT, 'Archive item type.'),
                    'validationstate' => new external_value(PARAM_ALPHANUMEXT, 'Validation state.'),
                    'visibility' => new external_value(PARAM_ALPHANUMEXT, 'Visibility.'),
                    'url' => new external_value(PARAM_URL, 'Archive item URL.', VALUE_DEFAULT, ''),
                ]),
                'Archive references.'
            ),
            'integrity' => new external_single_structure([
                'included' => new external_value(PARAM_BOOL, 'Whether integrity summary is included.'),
                'hasopenissues' => new external_value(PARAM_BOOL, 'Whether there are open integrity issues.', VALUE_DEFAULT, false),
                'opencount' => new external_value(PARAM_INT, 'Open integrity case count.', VALUE_DEFAULT, 0),
                'restricted' => new external_value(PARAM_BOOL, 'Whether details are restricted.', VALUE_DEFAULT, true),
                'summary' => new external_value(PARAM_TEXT, 'Safe integrity summary.', VALUE_DEFAULT, ''),
            ], 'Integrity summary.'),
            'warnings' => new external_multiple_structure(
                new external_single_structure([
                    'item' => new external_value(PARAM_TEXT, 'Warning item.', VALUE_DEFAULT, ''),
                    'itemid' => new external_value(PARAM_INT, 'Warning item id.', VALUE_DEFAULT, 0),
                    'warningcode' => new external_value(PARAM_ALPHANUMEXT, 'Warning code.'),
                    'message' => new external_value(PARAM_TEXT, 'Warning message.'),
                ]),
                'Warnings.'
            ),
        ]);
    }

    /**
     * Resolve Moodle context from id.
     *
     * @param int $contextid Context id, or 0 for system context.
     * @return context
     */
    private static function resolve_context(int $contextid): context {
        if ($contextid > 0) {
            return context::instance_by_id($contextid, MUST_EXIST);
        }

        return context_system::instance();
    }

    /**
     * Enforce access to a pathway status.
     *
     * Users may view their own status if they can access the UCKK campus.
     * Viewing another user requires pathway management or report visibility.
     *
     * @param context $context Moodle context.
     * @param int $userid User id being inspected.
     * @return void
     */
    private static function require_access(context $context, int $userid): void {
        global $USER;

        if ((int)$USER->id === $userid) {
            if (
                has_capability(self::CAP_VIEW_CAMPUS, $context)
                || has_capability(self::CAP_MANAGE_PATHWAYS, $context)
                || has_capability(self::CAP_VIEW_REPORTS, $context)
            ) {
                return;
            }

            require_capability(self::CAP_VIEW_CAMPUS, $context);
            return;
        }

        if (has_capability(self::CAP_MANAGE_PATHWAYS, $context) || has_capability(self::CAP_VIEW_REPORTS, $context)) {
            return;
        }

        require_capability(self::CAP_VIEW_REPORTS, $context);
    }

    /**
     * Normalise the response returned by pathway_api.
     *
     * This function protects the external API contract from small differences
     * in the internal API return object. The internal API remains the source of
     * truth; this method only fills missing optional fields.
     *
     * @param array|object $status Raw status object or array.
     * @param int $userid User id.
     * @return array<string, mixed>
     */
    private static function normalise_status_response($status, int $userid): array {
        $status = json_decode(json_encode($status), true);
        if (!is_array($status)) {
            $status = [];
        }

        $pathway = $status['pathway'] ?? [];
        $progress = $status['progress'] ?? [];
        $program = $status['program'] ?? [];
        $portfolio = $status['portfolio'] ?? [];
        $integrity = $status['integrity'] ?? [];

        return [
            'success' => (bool)($status['success'] ?? true),
            'message' => (string)($status['message'] ?? ''),
            'userid' => (int)($status['userid'] ?? $userid),
            'pathway' => [
                'id' => (int)($pathway['id'] ?? $status['pathwayid'] ?? 0),
                'shortname' => (string)($pathway['shortname'] ?? $status['pathwayshortname'] ?? ''),
                'fullname' => (string)($pathway['fullname'] ?? $status['pathwayname'] ?? ''),
                'status' => (string)($pathway['status'] ?? 'active'),
                'visibility' => (string)($pathway['visibility'] ?? 'institution'),
                'programid' => (int)($pathway['programid'] ?? $program['id'] ?? 0),
                'programshortname' => (string)($pathway['programshortname'] ?? $program['shortname'] ?? ''),
                'programfullname' => (string)($pathway['programfullname'] ?? $program['fullname'] ?? ''),
            ],
            'progress' => [
                'status' => (string)($progress['status'] ?? $status['progressstatus'] ?? 'pending'),
                'completionpercent' => (float)($progress['completionpercent'] ?? 0),
                'requiredcount' => (int)($progress['requiredcount'] ?? 0),
                'completedcount' => (int)($progress['completedcount'] ?? 0),
                'pendingcount' => (int)($progress['pendingcount'] ?? 0),
                'blockedcount' => (int)($progress['blockedcount'] ?? 0),
                'lastupdated' => (int)($progress['lastupdated'] ?? 0),
            ],
            'program' => [
                'included' => (bool)($program['included'] ?? !empty($program)),
                'id' => (int)($program['id'] ?? 0),
                'shortname' => (string)($program['shortname'] ?? ''),
                'fullname' => (string)($program['fullname'] ?? ''),
                'programtype' => (string)($program['programtype'] ?? ''),
                'summary' => (string)($program['summary'] ?? ''),
                'internalnotice' => (string)($program['internalnotice'] ?? get_string('program_internal_notice', 'local_uckk')),
            ],
            'courses' => self::normalise_list($status['courses'] ?? [], [
                'id' => 0,
                'shortname' => '',
                'fullname' => '',
                'idnumber' => '',
                'required' => false,
                'completed' => false,
                'completionstate' => '',
                'completiontime' => 0,
                'url' => '',
            ]),
            'competencies' => self::normalise_list($status['competencies'] ?? [], [
                'id' => 0,
                'idnumber' => '',
                'shortname' => '',
                'fullname' => '',
                'required' => false,
                'proficient' => false,
                'grade' => 0,
                'status' => '',
            ]),
            'badges' => self::normalise_list($status['badges'] ?? [], [
                'id' => 0,
                'name' => '',
                'required' => false,
                'awarded' => false,
                'dateissued' => 0,
                'imageurl' => '',
            ]),
            'portfolio' => [
                'included' => (bool)($portfolio['included'] ?? !empty($portfolio)),
                'itemcount' => (int)($portfolio['itemcount'] ?? 0),
                'validatedcount' => (int)($portfolio['validatedcount'] ?? 0),
                'pendingcount' => (int)($portfolio['pendingcount'] ?? 0),
                'url' => (string)($portfolio['url'] ?? ''),
            ],
            'archives' => self::normalise_list($status['archives'] ?? [], [
                'id' => 0,
                'title' => '',
                'itemtype' => '',
                'validationstate' => '',
                'visibility' => '',
                'url' => '',
            ]),
            'integrity' => [
                'included' => (bool)($integrity['included'] ?? !empty($integrity)),
                'hasopenissues' => (bool)($integrity['hasopenissues'] ?? false),
                'opencount' => (int)($integrity['opencount'] ?? 0),
                'restricted' => (bool)($integrity['restricted'] ?? true),
                'summary' => (string)($integrity['summary'] ?? ''),
            ],
            'warnings' => self::normalise_list($status['warnings'] ?? [], [
                'item' => '',
                'itemid' => 0,
                'warningcode' => '',
                'message' => '',
            ]),
        ];
    }

    /**
     * Normalise a list of associative arrays according to defaults.
     *
     * @param mixed $items Raw list.
     * @param array<string, mixed> $defaults Default values.
     * @return array<int, array<string, mixed>>
     */
    private static function normalise_list($items, array $defaults): array {
        if (!is_array($items)) {
            return [];
        }

        $normalised = [];

        foreach ($items as $item) {
            if (is_object($item)) {
                $item = (array)$item;
            }

            if (!is_array($item)) {
                continue;
            }

            $normalised[] = array_merge($defaults, $item);
        }

        return $normalised;
    }
}