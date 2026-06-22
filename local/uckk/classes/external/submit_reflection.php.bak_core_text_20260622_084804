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
 * External function for submitting a UCKK reflection.
 *
 * A reflection is a learner-authored trace used by UCKK-Moodle to support:
 *
 * - portfolio construction;
 * - pathway progression;
 * - evidence of learning;
 * - AI collaboration journals;
 * - assembly participation records;
 * - course and program reflection;
 * - future archive or integrity workflows.
 *
 * This external function does not grade, award badges, validate competencies,
 * create archive items, close integrity cases or make institutional decisions.
 *
 * @package    local_uckk
 * @copyright  2026 Univers-Cité King Klown
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_uckk\external;

use context;
use context_course;
use context_system;
use context_user;
use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use dml_exception;
use invalid_parameter_exception;
use moodle_exception;
use stdClass;

defined('MOODLE_INTERNAL') || die();

/**
 * Submit a UCKK reflection.
 *
 * This class is designed for use by AJAX, mobile, external integrations or
 * local front-end components. It must be declared in local/uckk/db/services.php
 * before it can be exposed as a Moodle web service.
 */
final class submit_reflection extends external_api {
    /** Reflection table. */
    private const TABLE_REFLECTION = 'local_uckk_reflect';

    /** Player profile table. */
    private const TABLE_PLAYER = 'local_uckk_player';

    /** Provenance table. */
    private const TABLE_PROVENANCE = 'local_uckk_prov';

    /** Component name. */
    private const COMPONENT = 'local_uckk';

    /** Reflection type: general. */
    private const TYPE_GENERAL = 'general';

    /** Reflection type: portfolio. */
    private const TYPE_PORTFOLIO = 'portfolio';

    /** Reflection type: AI journal. */
    private const TYPE_AI_JOURNAL = 'ai_journal';

    /** Reflection type: assembly. */
    private const TYPE_ASSEMBLY = 'assembly';

    /** Reflection type: challenge. */
    private const TYPE_CHALLENGE = 'challenge';

    /** Reflection type: course. */
    private const TYPE_COURSE = 'course';

    /** Reflection type: integrity. */
    private const TYPE_INTEGRITY = 'integrity';

    /** Visibility: private. */
    private const VISIBILITY_PRIVATE = 'private';

    /** Visibility: course. */
    private const VISIBILITY_COURSE = 'course';

    /** Visibility: cohort. */
    private const VISIBILITY_COHORT = 'cohort';

    /** Visibility: institution. */
    private const VISIBILITY_INSTITUTION = 'institution';

    /** Visibility: public. */
    private const VISIBILITY_PUBLIC = 'public';

    /**
     * Define input parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'userid' => new external_value(
                PARAM_INT,
                'User id. Use 0 to submit for the current user.',
                VALUE_DEFAULT,
                0
            ),
            'courseid' => new external_value(
                PARAM_INT,
                'Optional Moodle course id linked to the reflection.',
                VALUE_DEFAULT,
                0
            ),
            'pathwayid' => new external_value(
                PARAM_INT,
                'Optional UCKK pathway id linked to the reflection.',
                VALUE_DEFAULT,
                0
            ),
            'title' => new external_value(
                PARAM_TEXT,
                'Reflection title.',
                VALUE_DEFAULT,
                ''
            ),
            'body' => new external_value(
                PARAM_RAW,
                'Reflection body.',
                VALUE_REQUIRED
            ),
            'reflectiontype' => new external_value(
                PARAM_ALPHANUMEXT,
                'Reflection type: general, portfolio, ai_journal, assembly, challenge, course, integrity.',
                VALUE_DEFAULT,
                self::TYPE_GENERAL
            ),
            'visibility' => new external_value(
                PARAM_ALPHANUMEXT,
                'Visibility: private, course, cohort, institution, public.',
                VALUE_DEFAULT,
                self::VISIBILITY_PRIVATE
            ),
            'sourcecomponent' => new external_value(
                PARAM_COMPONENT,
                'Optional Moodle component where the reflection originated.',
                VALUE_DEFAULT,
                self::COMPONENT
            ),
            'sourceitemid' => new external_value(
                PARAM_INT,
                'Optional source item id.',
                VALUE_DEFAULT,
                0
            ),
            'aiassisted' => new external_value(
                PARAM_BOOL,
                'Whether AI assisted the reflection.',
                VALUE_DEFAULT,
                false
            ),
            'metadata' => new external_value(
                PARAM_RAW,
                'Optional JSON metadata object.',
                VALUE_DEFAULT,
                '{}'
            ),
        ]);
    }

    /**
     * Execute the reflection submission.
     *
     * @param int $userid User id, or 0 for current user.
     * @param int $courseid Optional course id.
     * @param int $pathwayid Optional pathway id.
     * @param string $title Reflection title.
     * @param string $body Reflection body.
     * @param string $reflectiontype Reflection type.
     * @param string $visibility Reflection visibility.
     * @param string $sourcecomponent Source component.
     * @param int $sourceitemid Source item id.
     * @param bool $aiassisted Whether AI assisted.
     * @param string $metadata JSON metadata.
     * @return array<string, mixed>
     * @throws dml_exception
     * @throws invalid_parameter_exception
     * @throws moodle_exception
     */
    public static function execute(
        int $userid,
        int $courseid,
        int $pathwayid,
        string $title,
        string $body,
        string $reflectiontype = self::TYPE_GENERAL,
        string $visibility = self::VISIBILITY_PRIVATE,
        string $sourcecomponent = self::COMPONENT,
        int $sourceitemid = 0,
        bool $aiassisted = false,
        string $metadata = '{}'
    ): array {
        global $DB, $USER;

        [
            'userid' => $userid,
            'courseid' => $courseid,
            'pathwayid' => $pathwayid,
            'title' => $title,
            'body' => $body,
            'reflectiontype' => $reflectiontype,
            'visibility' => $visibility,
            'sourcecomponent' => $sourcecomponent,
            'sourceitemid' => $sourceitemid,
            'aiassisted' => $aiassisted,
            'metadata' => $metadata,
        ] = self::validate_parameters(self::execute_parameters(), [
            'userid' => $userid,
            'courseid' => $courseid,
            'pathwayid' => $pathwayid,
            'title' => $title,
            'body' => $body,
            'reflectiontype' => $reflectiontype,
            'visibility' => $visibility,
            'sourcecomponent' => $sourcecomponent,
            'sourceitemid' => $sourceitemid,
            'aiassisted' => $aiassisted,
            'metadata' => $metadata,
        ]);

        if (!isloggedin() || isguestuser()) {
            throw new moodle_exception('loginrequired');
        }

        $userid = $userid > 0 ? $userid : (int)$USER->id;

        if (!$DB->record_exists('user', ['id' => $userid, 'deleted' => 0])) {
            throw new invalid_parameter_exception('Invalid user id.');
        }

        $context = self::resolve_context($userid, $courseid);
        self::validate_context($context);

        if ((int)$userid !== (int)$USER->id) {
            require_capability('local/uckk:manageprofiles', $context);
        } else {
            self::require_basic_submit_access($context);
        }

        $reflectiontype = self::normalise_reflection_type($reflectiontype);
        $visibility = self::normalise_visibility($visibility);
        $body = trim($body);
        $title = trim($title);

        if ($body === '') {
            throw new invalid_parameter_exception('Reflection body cannot be empty.');
        }

        if ($title === '') {
            $title = self::build_default_title($reflectiontype);
        }

        $metadatadecoded = self::decode_metadata($metadata);

        if ($aiassisted) {
            $metadatadecoded['aiassisted'] = true;
            $metadatadecoded['ai_policy'] = 'non_sovereign_human_validation_required';
        }

        $transaction = $DB->start_delegated_transaction();

        try {
            $profileid = self::ensure_player_profile($userid);

            $record = (object)[
                'userid' => $userid,
                'courseid' => $courseid > 0 ? $courseid : null,
                'pathwayid' => $pathwayid > 0 ? $pathwayid : null,
                'profileid' => $profileid,
                'title' => $title,
                'body' => $body,
                'reflectiontype' => $reflectiontype,
                'visibility' => $visibility,
                'sourcecomponent' => $sourcecomponent,
                'sourceitemid' => $sourceitemid > 0 ? $sourceitemid : null,
                'aiassisted' => $aiassisted ? 1 : 0,
                'metadata' => self::encode_metadata($metadatadecoded),
                'status' => 'submitted',
                'validationstate' => 'unverified',
                'createdby' => (int)$USER->id,
                'modifiedby' => (int)$USER->id,
                'timecreated' => time(),
                'timemodified' => time(),
            ];

            $reflectionid = $DB->insert_record(self::TABLE_REFLECTION, $record);

            self::write_provenance($reflectionid, $context, [
                'userid' => $userid,
                'courseid' => $courseid,
                'pathwayid' => $pathwayid,
                'reflectiontype' => $reflectiontype,
                'visibility' => $visibility,
                'sourcecomponent' => $sourcecomponent,
                'sourceitemid' => $sourceitemid,
                'aiassisted' => $aiassisted,
            ]);

            self::trigger_event_if_available('reflection_submitted', $context, $reflectionid, [
                'userid' => $userid,
                'courseid' => $courseid,
                'pathwayid' => $pathwayid,
                'reflectiontype' => $reflectiontype,
                'visibility' => $visibility,
                'aiassisted' => $aiassisted,
            ]);

            $transaction->allow_commit();
        } catch (\Throwable $e) {
            $transaction->rollback($e);
        }

        return [
            'reflectionid' => (int)$reflectionid,
            'userid' => (int)$userid,
            'courseid' => (int)$courseid,
            'pathwayid' => (int)$pathwayid,
            'profileid' => (int)$profileid,
            'title' => $title,
            'reflectiontype' => $reflectiontype,
            'visibility' => $visibility,
            'status' => 'submitted',
            'validationstate' => 'unverified',
            'aiassisted' => (bool)$aiassisted,
            'timecreated' => (int)$record->timecreated,
            'warnings' => [],
        ];
    }

    /**
     * Define return structure.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'reflectionid' => new external_value(PARAM_INT, 'Created reflection id.'),
            'userid' => new external_value(PARAM_INT, 'Reflection owner user id.'),
            'courseid' => new external_value(PARAM_INT, 'Linked course id, or 0.'),
            'pathwayid' => new external_value(PARAM_INT, 'Linked pathway id, or 0.'),
            'profileid' => new external_value(PARAM_INT, 'UCKK player profile id.'),
            'title' => new external_value(PARAM_TEXT, 'Reflection title.'),
            'reflectiontype' => new external_value(PARAM_ALPHANUMEXT, 'Reflection type.'),
            'visibility' => new external_value(PARAM_ALPHANUMEXT, 'Reflection visibility.'),
            'status' => new external_value(PARAM_ALPHANUMEXT, 'Reflection status.'),
            'validationstate' => new external_value(PARAM_ALPHANUMEXT, 'Validation state.'),
            'aiassisted' => new external_value(PARAM_BOOL, 'Whether AI assisted this reflection.'),
            'timecreated' => new external_value(PARAM_INT, 'Creation timestamp.'),
            'warnings' => new \core_external\external_multiple_structure(
                new external_single_structure([
                    'item' => new external_value(PARAM_TEXT, 'Warning item.', VALUE_DEFAULT, ''),
                    'itemid' => new external_value(PARAM_INT, 'Warning item id.', VALUE_DEFAULT, 0),
                    'warningcode' => new external_value(PARAM_ALPHANUMEXT, 'Warning code.'),
                    'message' => new external_value(PARAM_TEXT, 'Warning message.'),
                ]),
                'Warnings.',
                VALUE_DEFAULT,
                []
            ),
        ]);
    }

    /**
     * Resolve Moodle context for submission.
     *
     * @param int $userid User id.
     * @param int $courseid Course id.
     * @return context
     */
    private static function resolve_context(int $userid, int $courseid): context {
        if ($courseid > 0) {
            return context_course::instance($courseid);
        }

        if ($userid > 0) {
            return context_user::instance($userid);
        }

        return context_system::instance();
    }

    /**
     * Require basic access for own reflection submission.
     *
     * If local/uckk:viewcampus is not configured in the current context, this
     * falls back to login validation only. Cross-user submission remains guarded
     * by local/uckk:manageprofiles.
     *
     * @param context $context Context.
     * @return void
     */
    private static function require_basic_submit_access(context $context): void {
        if (has_capability('local/uckk:viewcampus', $context)) {
            require_capability('local/uckk:viewcampus', $context);
        }
    }

    /**
     * Ensure a player profile exists for the user.
     *
     * @param int $userid User id.
     * @return int Player profile id.
     * @throws dml_exception
     */
    private static function ensure_player_profile(int $userid): int {
        global $DB, $USER;

        $profile = $DB->get_record(self::TABLE_PLAYER, ['userid' => $userid], 'id', IGNORE_MISSING);

        if ($profile) {
            return (int)$profile->id;
        }

        $now = time();

        $profile = (object)[
            'userid' => $userid,
            'displaytitle' => '',
            'symbolicroles' => '[]',
            'activepathwayids' => '[]',
            'portfolioarchiveid' => null,
            'integrityflags' => '[]',
            'visibility' => 'private',
            'createdby' => (int)($USER->id ?? 0),
            'modifiedby' => (int)($USER->id ?? 0),
            'timecreated' => $now,
            'timemodified' => $now,
        ];

        return (int)$DB->insert_record(self::TABLE_PLAYER, $profile);
    }

    /**
     * Normalise reflection type.
     *
     * @param string $type Raw type.
     * @return string
     * @throws invalid_parameter_exception
     */
    private static function normalise_reflection_type(string $type): string {
        $type = clean_param(trim(core_text::strtolower($type)), PARAM_ALPHANUMEXT);

        $allowed = [
            self::TYPE_GENERAL,
            self::TYPE_PORTFOLIO,
            self::TYPE_AI_JOURNAL,
            self::TYPE_ASSEMBLY,
            self::TYPE_CHALLENGE,
            self::TYPE_COURSE,
            self::TYPE_INTEGRITY,
        ];

        if (!in_array($type, $allowed, true)) {
            throw new invalid_parameter_exception('Invalid UCKK reflection type.');
        }

        return $type;
    }

    /**
     * Normalise visibility.
     *
     * @param string $visibility Raw visibility.
     * @return string
     * @throws invalid_parameter_exception
     */
    private static function normalise_visibility(string $visibility): string {
        $visibility = clean_param(trim(core_text::strtolower($visibility)), PARAM_ALPHANUMEXT);

        $allowed = [
            self::VISIBILITY_PRIVATE,
            self::VISIBILITY_COURSE,
            self::VISIBILITY_COHORT,
            self::VISIBILITY_INSTITUTION,
            self::VISIBILITY_PUBLIC,
        ];

        if (!in_array($visibility, $allowed, true)) {
            throw new invalid_parameter_exception('Invalid UCKK reflection visibility.');
        }

        return $visibility;
    }

    /**
     * Build a default reflection title.
     *
     * @param string $reflectiontype Reflection type.
     * @return string
     */
    private static function build_default_title(string $reflectiontype): string {
        $date = userdate(time(), get_string('strftimedatetimeshort', 'core_langconfig'));

        return get_string('reflection', 'local_uckk') . ' — ' . $reflectiontype . ' — ' . $date;
    }

    /**
     * Decode metadata JSON.
     *
     * @param string $metadata JSON metadata.
     * @return array<string, mixed>
     * @throws invalid_parameter_exception
     */
    private static function decode_metadata(string $metadata): array {
        $metadata = trim($metadata);

        if ($metadata === '') {
            return [];
        }

        $decoded = json_decode($metadata, true);

        if (!is_array($decoded)) {
            throw new invalid_parameter_exception('Invalid metadata JSON object.');
        }

        return $decoded;
    }

    /**
     * Encode metadata.
     *
     * @param array<string, mixed> $metadata Metadata.
     * @return string
     */
    private static function encode_metadata(array $metadata): string {
        return json_encode((object)$metadata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Write provenance if the provenance table exists.
     *
     * @param int $reflectionid Reflection id.
     * @param context $context Context.
     * @param array<string, mixed> $metadata Metadata.
     * @return void
     */
    private static function write_provenance(int $reflectionid, context $context, array $metadata): void {
        global $DB, $USER;

        $dbman = $DB->get_manager();

        if (!$dbman->table_exists(self::TABLE_PROVENANCE)) {
            return;
        }

        $record = (object)[
            'component' => self::COMPONENT,
            'itemtype' => 'reflection',
            'itemid' => $reflectionid,
            'contextid' => $context->id,
            'action' => 'submitted',
            'sourcecomponent' => self::COMPONENT,
            'sourceid' => $reflectionid,
            'sourcetext' => self::encode_metadata($metadata),
            'hash' => sha1(json_encode($metadata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)),
            'state' => 'recorded',
            'createdby' => (int)($USER->id ?? 0),
            'timecreated' => time(),
        ];

        try {
            $DB->insert_record(self::TABLE_PROVENANCE, $record);
        } catch (\Throwable $e) {
            debugging('Unable to write UCKK reflection provenance: ' . $e->getMessage(), DEBUG_DEVELOPER);
        }
    }

    /**
     * Trigger a local_uckk event if the event class exists.
     *
     * This keeps the function usable while event classes are being generated.
     *
     * @param string $eventname Event short name.
     * @param context $context Event context.
     * @param int $objectid Object id.
     * @param array<string, mixed> $other Other data.
     * @return void
     */
    private static function trigger_event_if_available(
        string $eventname,
        context $context,
        int $objectid,
        array $other = []
    ): void {
        $classname = '\\local_uckk\\event\\' . clean_param($eventname, PARAM_ALPHANUMEXT);

        if (!class_exists($classname)) {
            return;
        }

        $event = $classname::create([
            'context' => $context,
            'objectid' => $objectid,
            'other' => $other,
        ]);

        $event->trigger();
    }
}