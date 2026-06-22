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
 * Pathway API for the UCKK institutional core plugin.
 *
 * A UCKK pathway is an internal learning path attached to a UCKK program.
 * It may define required courses, competencies and badges. This class owns
 * pathway application logic for local_uckk, but it must not duplicate data
 * owned by course formats, activity modules, archives, integrity cases,
 * Moodle badges or Moodle competencies.
 *
 * This is an internal API class, not a Moodle external web service class.
 * External functions must live under local_uckk\external and be declared in
 * db/services.php.
 *
 * @package    local_uckk
 * @copyright  2026 Univers-Cité King Klown
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_uckk\api;

use coding_exception;
use context;
use context_system;
use dml_exception;
use invalid_parameter_exception;
use moodle_exception;
use required_capability_exception;
use stdClass;

defined('MOODLE_INTERNAL') || die();

/**
 * Internal API for UCKK pathways.
 *
 * The API is intentionally conservative:
 * - no direct UI output;
 * - no external web service declarations;
 * - no course completion side effects;
 * - no badge issuing;
 * - no competency rating;
 * - no integrity decision;
 * - no archive validation.
 *
 * It only manages the pathway registry and user-pathway assignment state
 * stored by local_uckk.
 */
final class pathway_api {
    /** Pathway table. */
    private const TABLE_PATHWAY = 'local_uckk_pathway';

    /** Program table. */
    private const TABLE_PROGRAM = 'local_uckk_program';

    /** Player profile table. */
    private const TABLE_PLAYER = 'local_uckk_player';

    /** Provenance table. */
    private const TABLE_PROVENANCE = 'local_uckk_prov';

    /** Active status. */
    public const STATUS_ACTIVE = 'active';

    /** Draft status. */
    public const STATUS_DRAFT = 'draft';

    /** Archived status. */
    public const STATUS_ARCHIVED = 'archived';

    /** Hidden status. */
    public const STATUS_HIDDEN = 'hidden';

    /** Deleted status. */
    public const STATUS_DELETED = 'deleted';

    /**
     * Return all supported pathway statuses.
     *
     * @return string[]
     */
    public static function get_supported_statuses(): array {
        return [
            self::STATUS_DRAFT,
            self::STATUS_ACTIVE,
            self::STATUS_HIDDEN,
            self::STATUS_ARCHIVED,
            self::STATUS_DELETED,
        ];
    }

    /**
     * Create a pathway.
     *
     * Expected keys:
     * - programid
     * - shortname
     * - fullname
     *
     * Optional keys:
     * - description
     * - requiredcourseids
     * - requiredbadges
     * - requiredcompetencies
     * - status
     * - sortorder
     * - metadata
     *
     * @param array<string, mixed>|stdClass $data Raw pathway data.
     * @param context|null $context Capability context.
     * @return stdClass Created pathway record.
     * @throws dml_exception
     * @throws invalid_parameter_exception
     * @throws moodle_exception
     * @throws required_capability_exception
     */
    public static function create_pathway($data, ?context $context = null): stdClass {
        global $DB, $USER;

        $context = $context ?? context_system::instance();
        self::require_manage_pathways($context);

        $record = self::normalise_pathway_record($data, true);

        if (!$DB->record_exists(self::TABLE_PROGRAM, ['id' => $record->programid])) {
            throw new invalid_parameter_exception('Invalid UCKK program id.');
        }

        if ($DB->record_exists(self::TABLE_PATHWAY, ['shortname' => $record->shortname])) {
            throw new moodle_exception('pathwayshortnameexists', 'local_uckk', '', $record->shortname);
        }

        $transaction = $DB->start_delegated_transaction();

        try {
            $record->timecreated = time();
            $record->timemodified = $record->timecreated;
            $record->createdby = $USER->id ?? 0;
            $record->modifiedby = $record->createdby;

            $record->id = $DB->insert_record(self::TABLE_PATHWAY, $record);

            self::write_provenance(
                'pathway',
                $record->id,
                'created',
                $context,
                [
                    'shortname' => $record->shortname,
                    'programid' => $record->programid,
                ]
            );

            self::trigger_event_if_available('pathway_created', $context, $record->id, [
                'shortname' => $record->shortname,
                'programid' => $record->programid,
            ]);

            $transaction->allow_commit();
        } catch (\Throwable $e) {
            $transaction->rollback($e);
        }

        return self::get_pathway($record->id, true);
    }

    /**
     * Update a pathway.
     *
     * @param int $pathwayid Pathway id.
     * @param array<string, mixed>|stdClass $data Raw update data.
     * @param context|null $context Capability context.
     * @return stdClass Updated pathway record.
     * @throws dml_exception
     * @throws invalid_parameter_exception
     * @throws moodle_exception
     * @throws required_capability_exception
     */
    public static function update_pathway(int $pathwayid, $data, ?context $context = null): stdClass {
        global $DB, $USER;

        $context = $context ?? context_system::instance();
        self::require_manage_pathways($context);

        $existing = self::get_pathway($pathwayid, true);
        $incoming = (object)(array)$data;

        $record = clone $existing;

        foreach ([
            'programid',
            'shortname',
            'fullname',
            'description',
            'requiredcourseids',
            'requiredbadges',
            'requiredcompetencies',
            'status',
            'sortorder',
            'metadata',
        ] as $field) {
            if (property_exists($incoming, $field)) {
                $record->{$field} = $incoming->{$field};
            }
        }

        $record = self::normalise_pathway_record($record, false);
        $record->id = $pathwayid;

        if (!$DB->record_exists(self::TABLE_PROGRAM, ['id' => $record->programid])) {
            throw new invalid_parameter_exception('Invalid UCKK program id.');
        }

        $duplicate = $DB->get_record(self::TABLE_PATHWAY, ['shortname' => $record->shortname], 'id', IGNORE_MULTIPLE);
        if ($duplicate && (int)$duplicate->id !== $pathwayid) {
            throw new moodle_exception('pathwayshortnameexists', 'local_uckk', '', $record->shortname);
        }

        $transaction = $DB->start_delegated_transaction();

        try {
            $record->timemodified = time();
            $record->modifiedby = $USER->id ?? 0;

            $DB->update_record(self::TABLE_PATHWAY, $record);

            self::write_provenance(
                'pathway',
                $record->id,
                'updated',
                $context,
                [
                    'shortname' => $record->shortname,
                    'programid' => $record->programid,
                    'status' => $record->status,
                ]
            );

            self::trigger_event_if_available('pathway_updated', $context, $record->id, [
                'shortname' => $record->shortname,
                'programid' => $record->programid,
                'status' => $record->status,
            ]);

            $transaction->allow_commit();
        } catch (\Throwable $e) {
            $transaction->rollback($e);
        }

        return self::get_pathway($pathwayid, true);
    }

    /**
     * Get a pathway by id.
     *
     * @param int $pathwayid Pathway id.
     * @param bool $required Whether the record is required.
     * @return stdClass|null
     * @throws dml_exception
     * @throws moodle_exception
     */
    public static function get_pathway(int $pathwayid, bool $required = false): ?stdClass {
        global $DB;

        $record = $DB->get_record(self::TABLE_PATHWAY, ['id' => $pathwayid], '*', $required ? MUST_EXIST : IGNORE_MISSING);

        if (!$record) {
            return null;
        }

        return self::format_pathway_record($record);
    }

    /**
     * Get a pathway by shortname.
     *
     * @param string $shortname Pathway shortname.
     * @param bool $required Whether the record is required.
     * @return stdClass|null
     * @throws dml_exception
     * @throws moodle_exception
     */
    public static function get_pathway_by_shortname(string $shortname, bool $required = false): ?stdClass {
        global $DB;

        $shortname = self::normalise_shortname($shortname);

        $record = $DB->get_record(
            self::TABLE_PATHWAY,
            ['shortname' => $shortname],
            '*',
            $required ? MUST_EXIST : IGNORE_MISSING
        );

        if (!$record) {
            return null;
        }

        return self::format_pathway_record($record);
    }

    /**
     * List pathways.
     *
     * Filters:
     * - programid
     * - status
     * - includehidden
     * - includedeleted
     *
     * @param array<string, mixed> $filters Filters.
     * @return stdClass[]
     * @throws dml_exception
     */
    public static function list_pathways(array $filters = []): array {
        global $DB;

        $conditions = [];
        $params = [];

        if (!empty($filters['programid'])) {
            $conditions[] = 'programid = :programid';
            $params['programid'] = (int)$filters['programid'];
        }

        if (!empty($filters['status'])) {
            $conditions[] = 'status = :status';
            $params['status'] = self::normalise_status((string)$filters['status']);
        } else {
            if (empty($filters['includehidden'])) {
                $conditions[] = 'status <> :hiddenstatus';
                $params['hiddenstatus'] = self::STATUS_HIDDEN;
            }

            if (empty($filters['includedeleted'])) {
                $conditions[] = 'status <> :deletedstatus';
                $params['deletedstatus'] = self::STATUS_DELETED;
            }
        }

        $where = $conditions ? implode(' AND ', $conditions) : '1 = 1';

        $records = $DB->get_records_select(
            self::TABLE_PATHWAY,
            $where,
            $params,
            'sortorder ASC, fullname ASC, id ASC'
        );

        return array_map([self::class, 'format_pathway_record'], array_values($records));
    }

    /**
     * Delete a pathway softly by marking it deleted.
     *
     * @param int $pathwayid Pathway id.
     * @param context|null $context Capability context.
     * @return bool
     * @throws dml_exception
     * @throws moodle_exception
     * @throws required_capability_exception
     */
    public static function delete_pathway(int $pathwayid, ?context $context = null): bool {
        return self::set_pathway_status($pathwayid, self::STATUS_DELETED, $context);
    }

    /**
     * Archive a pathway.
     *
     * @param int $pathwayid Pathway id.
     * @param context|null $context Capability context.
     * @return bool
     * @throws dml_exception
     * @throws moodle_exception
     * @throws required_capability_exception
     */
    public static function archive_pathway(int $pathwayid, ?context $context = null): bool {
        return self::set_pathway_status($pathwayid, self::STATUS_ARCHIVED, $context);
    }

    /**
     * Set pathway status.
     *
     * @param int $pathwayid Pathway id.
     * @param string $status New status.
     * @param context|null $context Capability context.
     * @return bool
     * @throws dml_exception
     * @throws invalid_parameter_exception
     * @throws moodle_exception
     * @throws required_capability_exception
     */
    public static function set_pathway_status(int $pathwayid, string $status, ?context $context = null): bool {
        global $DB, $USER;

        $context = $context ?? context_system::instance();
        self::require_manage_pathways($context);

        $status = self::normalise_status($status);
        $record = self::get_pathway($pathwayid, true);

        $record->status = $status;
        $record->timemodified = time();
        $record->modifiedby = $USER->id ?? 0;

        $transaction = $DB->start_delegated_transaction();

        try {
            $DB->update_record(self::TABLE_PATHWAY, $record);

            self::write_provenance(
                'pathway',
                $pathwayid,
                'status_changed',
                $context,
                [
                    'status' => $status,
                ]
            );

            self::trigger_event_if_available('pathway_status_changed', $context, $pathwayid, [
                'status' => $status,
            ]);

            $transaction->allow_commit();
        } catch (\Throwable $e) {
            $transaction->rollback($e);
        }

        return true;
    }

    /**
     * Assign a pathway to a Moodle user.
     *
     * This stores assignment in local_uckk_player.activepathwayids as JSON.
     * It does not enrol the user in courses and does not award badges.
     *
     * @param int $userid Moodle user id.
     * @param int $pathwayid Pathway id.
     * @param context|null $context Capability context.
     * @return stdClass Updated player profile.
     * @throws dml_exception
     * @throws moodle_exception
     * @throws required_capability_exception
     */
    public static function assign_to_user(int $userid, int $pathwayid, ?context $context = null): stdClass {
        global $DB, $USER;

        $context = $context ?? context_system::instance();
        self::require_manage_pathways($context);

        $pathway = self::get_pathway($pathwayid, true);
        if ($pathway->status !== self::STATUS_ACTIVE) {
            throw new moodle_exception('cannotassigninactivepathway', 'local_uckk', '', $pathway->shortname);
        }

        $profile = self::get_or_create_player_profile($userid);
        $activepathwayids = self::decode_int_list($profile->activepathwayids ?? '[]');

        if (!in_array($pathwayid, $activepathwayids, true)) {
            $activepathwayids[] = $pathwayid;
        }

        sort($activepathwayids);

        $profile->activepathwayids = self::encode_list($activepathwayids);
        $profile->timemodified = time();
        $profile->modifiedby = $USER->id ?? 0;

        $transaction = $DB->start_delegated_transaction();

        try {
            $DB->update_record(self::TABLE_PLAYER, $profile);

            self::write_provenance(
                'player',
                (int)$profile->id,
                'pathway_assigned',
                $context,
                [
                    'userid' => $userid,
                    'pathwayid' => $pathwayid,
                ]
            );

            self::trigger_event_if_available('pathway_assigned', $context, $pathwayid, [
                'userid' => $userid,
                'pathwayid' => $pathwayid,
                'profileid' => (int)$profile->id,
            ]);

            $transaction->allow_commit();
        } catch (\Throwable $e) {
            $transaction->rollback($e);
        }

        return self::format_player_profile($profile);
    }

    /**
     * Remove a pathway assignment from a Moodle user.
     *
     * This does not remove course enrolments, grades, submissions, archives,
     * badges or competencies.
     *
     * @param int $userid Moodle user id.
     * @param int $pathwayid Pathway id.
     * @param context|null $context Capability context.
     * @return stdClass Updated player profile.
     * @throws dml_exception
     * @throws moodle_exception
     * @throws required_capability_exception
     */
    public static function unassign_from_user(int $userid, int $pathwayid, ?context $context = null): stdClass {
        global $DB, $USER;

        $context = $context ?? context_system::instance();
        self::require_manage_pathways($context);

        self::get_pathway($pathwayid, true);

        $profile = self::get_or_create_player_profile($userid);
        $activepathwayids = self::decode_int_list($profile->activepathwayids ?? '[]');
        $activepathwayids = array_values(array_filter($activepathwayids, static function(int $id) use ($pathwayid): bool {
            return $id !== $pathwayid;
        }));

        sort($activepathwayids);

        $profile->activepathwayids = self::encode_list($activepathwayids);
        $profile->timemodified = time();
        $profile->modifiedby = $USER->id ?? 0;

        $transaction = $DB->start_delegated_transaction();

        try {
            $DB->update_record(self::TABLE_PLAYER, $profile);

            self::write_provenance(
                'player',
                (int)$profile->id,
                'pathway_unassigned',
                $context,
                [
                    'userid' => $userid,
                    'pathwayid' => $pathwayid,
                ]
            );

            self::trigger_event_if_available('pathway_unassigned', $context, $pathwayid, [
                'userid' => $userid,
                'pathwayid' => $pathwayid,
                'profileid' => (int)$profile->id,
            ]);

            $transaction->allow_commit();
        } catch (\Throwable $e) {
            $transaction->rollback($e);
        }

        return self::format_player_profile($profile);
    }

    /**
     * Get pathways assigned to a user.
     *
     * @param int $userid Moodle user id.
     * @param bool $includeinactive Include inactive pathway records.
     * @return stdClass[]
     * @throws dml_exception
     */
    public static function get_user_pathways(int $userid, bool $includeinactive = false): array {
        $profile = self::get_player_profile($userid);

        if (!$profile) {
            return [];
        }

        $ids = self::decode_int_list($profile->activepathwayids ?? '[]');
        $pathways = [];

        foreach ($ids as $id) {
            $pathway = self::get_pathway($id, false);
            if (!$pathway) {
                continue;
            }

            if (!$includeinactive && $pathway->status !== self::STATUS_ACTIVE) {
                continue;
            }

            $pathways[] = $pathway;
        }

        return $pathways;
    }

    /**
     * Calculate pathway progress for a user.
     *
     * This method returns a conservative summary based on Moodle course
     * completion records. It does not grade, award badges or rate competencies.
     *
     * @param int $userid Moodle user id.
     * @param int $pathwayid Pathway id.
     * @return stdClass Progress summary.
     * @throws dml_exception
     * @throws moodle_exception
     */
    public static function calculate_user_progress(int $userid, int $pathwayid): stdClass {
        global $DB;

        $pathway = self::get_pathway($pathwayid, true);
        $requiredcourseids = self::decode_int_list($pathway->requiredcourseids ?? '[]');
        $completedcourseids = [];

        foreach ($requiredcourseids as $courseid) {
            $completion = $DB->get_record('course_completions', [
                'userid' => $userid,
                'course' => $courseid,
            ]);

            if ($completion && !empty($completion->timecompleted)) {
                $completedcourseids[] = $courseid;
            }
        }

        $requiredcount = count($requiredcourseids);
        $completedcount = count($completedcourseids);
        $percent = $requiredcount > 0 ? (int)floor(($completedcount / $requiredcount) * 100) : 0;

        return (object)[
            'userid' => $userid,
            'pathwayid' => $pathwayid,
            'pathway' => $pathway,
            'requiredcourseids' => $requiredcourseids,
            'completedcourseids' => $completedcourseids,
            'requiredcoursecount' => $requiredcount,
            'completedcoursecount' => $completedcount,
            'percent' => $percent,
            'complete' => $requiredcount > 0 && $completedcount >= $requiredcount,
        ];
    }

    /**
     * Determine whether the user is assigned to a pathway.
     *
     * @param int $userid Moodle user id.
     * @param int $pathwayid Pathway id.
     * @return bool
     * @throws dml_exception
     */
    public static function is_user_assigned(int $userid, int $pathwayid): bool {
        $profile = self::get_player_profile($userid);

        if (!$profile) {
            return false;
        }

        $ids = self::decode_int_list($profile->activepathwayids ?? '[]');

        return in_array($pathwayid, $ids, true);
    }

    /**
     * Export a pathway as a plain array for templates or JSON.
     *
     * @param stdClass $pathway Pathway record.
     * @return array<string, mixed>
     */
    public static function export_pathway(stdClass $pathway): array {
        $pathway = self::format_pathway_record($pathway);

        return [
            'id' => (int)$pathway->id,
            'programid' => (int)$pathway->programid,
            'shortname' => $pathway->shortname,
            'fullname' => $pathway->fullname,
            'description' => $pathway->description,
            'status' => $pathway->status,
            'sortorder' => (int)$pathway->sortorder,
            'requiredcourseids' => $pathway->requiredcourseidsdecoded,
            'requiredbadges' => $pathway->requiredbadgesdecoded,
            'requiredcompetencies' => $pathway->requiredcompetenciesdecoded,
            'metadata' => $pathway->metadatadecoded,
            'timecreated' => (int)$pathway->timecreated,
            'timemodified' => (int)$pathway->timemodified,
        ];
    }

    /**
     * Require capability to manage pathways.
     *
     * @param context $context Context.
     * @return void
     * @throws required_capability_exception
     */
    private static function require_manage_pathways(context $context): void {
        require_capability('local/uckk:managepathways', $context);
    }

    /**
     * Normalise a pathway record before insert/update.
     *
     * @param array<string, mixed>|stdClass $data Raw data.
     * @param bool $creating Whether creating a new record.
     * @return stdClass
     * @throws invalid_parameter_exception
     */
    private static function normalise_pathway_record($data, bool $creating): stdClass {
        $record = (object)(array)$data;

        if ($creating && empty($record->programid)) {
            throw new invalid_parameter_exception('Missing UCKK program id.');
        }

        if ($creating && empty($record->shortname)) {
            throw new invalid_parameter_exception('Missing UCKK pathway shortname.');
        }

        if ($creating && empty($record->fullname)) {
            throw new invalid_parameter_exception('Missing UCKK pathway fullname.');
        }

        $record->programid = (int)($record->programid ?? 0);
        $record->shortname = self::normalise_shortname((string)($record->shortname ?? ''));
        $record->fullname = clean_param((string)($record->fullname ?? ''), PARAM_TEXT);
        $record->description = clean_param((string)($record->description ?? ''), PARAM_RAW);
        $record->status = self::normalise_status((string)($record->status ?? self::STATUS_DRAFT));
        $record->sortorder = (int)($record->sortorder ?? 0);

        $record->requiredcourseids = self::encode_list(self::normalise_int_list($record->requiredcourseids ?? []));
        $record->requiredbadges = self::encode_list(self::normalise_string_list($record->requiredbadges ?? []));
        $record->requiredcompetencies = self::encode_list(self::normalise_string_list($record->requiredcompetencies ?? []));
        $record->metadata = self::encode_metadata($record->metadata ?? []);

        if ($record->shortname === '') {
            throw new invalid_parameter_exception('Invalid UCKK pathway shortname.');
        }

        if ($record->fullname === '') {
            throw new invalid_parameter_exception('Invalid UCKK pathway fullname.');
        }

        return $record;
    }

    /**
     * Format a pathway record after fetching from DB.
     *
     * @param stdClass $record Pathway record.
     * @return stdClass
     */
    private static function format_pathway_record(stdClass $record): stdClass {
        $record->id = (int)$record->id;
        $record->programid = (int)$record->programid;
        $record->shortname = (string)$record->shortname;
        $record->fullname = (string)$record->fullname;
        $record->description = (string)($record->description ?? '');
        $record->status = (string)($record->status ?? self::STATUS_DRAFT);
        $record->sortorder = (int)($record->sortorder ?? 0);
        $record->timecreated = (int)($record->timecreated ?? 0);
        $record->timemodified = (int)($record->timemodified ?? 0);

        $record->requiredcourseidsdecoded = self::decode_int_list($record->requiredcourseids ?? '[]');
        $record->requiredbadgesdecoded = self::decode_string_list($record->requiredbadges ?? '[]');
        $record->requiredcompetenciesdecoded = self::decode_string_list($record->requiredcompetencies ?? '[]');
        $record->metadatadecoded = self::decode_metadata($record->metadata ?? '{}');

        return $record;
    }

    /**
     * Get a player profile.
     *
     * @param int $userid Moodle user id.
     * @return stdClass|null
     * @throws dml_exception
     */
    private static function get_player_profile(int $userid): ?stdClass {
        global $DB;

        $profile = $DB->get_record(self::TABLE_PLAYER, ['userid' => $userid]);

        if (!$profile) {
            return null;
        }

        return self::format_player_profile($profile);
    }

    /**
     * Get or create a player profile.
     *
     * @param int $userid Moodle user id.
     * @return stdClass
     * @throws dml_exception
     */
    private static function get_or_create_player_profile(int $userid): stdClass {
        global $DB, $USER;

        $profile = self::get_player_profile($userid);

        if ($profile) {
            return $profile;
        }

        $profile = (object)[
            'userid' => $userid,
            'displaytitle' => '',
            'symbolicroles' => self::encode_list([]),
            'activepathwayids' => self::encode_list([]),
            'portfolioarchiveid' => null,
            'integrityflags' => self::encode_list([]),
            'visibility' => 'private',
            'timecreated' => time(),
            'timemodified' => time(),
            'createdby' => $USER->id ?? 0,
            'modifiedby' => $USER->id ?? 0,
        ];

        $profile->id = $DB->insert_record(self::TABLE_PLAYER, $profile);

        return self::format_player_profile($profile);
    }

    /**
     * Format a player profile record.
     *
     * @param stdClass $profile Profile record.
     * @return stdClass
     */
    private static function format_player_profile(stdClass $profile): stdClass {
        $profile->id = (int)$profile->id;
        $profile->userid = (int)$profile->userid;
        $profile->displaytitle = (string)($profile->displaytitle ?? '');
        $profile->symbolicrolesdecoded = self::decode_string_list($profile->symbolicroles ?? '[]');
        $profile->activepathwayidsdecoded = self::decode_int_list($profile->activepathwayids ?? '[]');
        $profile->integrityflagsdecoded = self::decode_string_list($profile->integrityflags ?? '[]');

        return $profile;
    }

    /**
     * Normalise a shortname.
     *
     * @param string $shortname Raw shortname.
     * @return string
     */
    private static function normalise_shortname(string $shortname): string {
        $shortname = trim(\core_text::strtolower($shortname));
        $shortname = str_replace(' ', '_', $shortname);
        $shortname = clean_param($shortname, PARAM_ALPHANUMEXT);

        return $shortname;
    }

    /**
     * Normalise a status.
     *
     * @param string $status Raw status.
     * @return string
     * @throws invalid_parameter_exception
     */
    private static function normalise_status(string $status): string {
        $status = clean_param(trim(\core_text::strtolower($status)), PARAM_ALPHANUMEXT);

        if (!in_array($status, self::get_supported_statuses(), true)) {
            throw new invalid_parameter_exception('Invalid UCKK pathway status.');
        }

        return $status;
    }

    /**
     * Normalise an int list.
     *
     * @param mixed $value Raw value.
     * @return int[]
     */
    private static function normalise_int_list($value): array {
        if (is_string($value)) {
            $value = self::decode_int_list($value);
        }

        if (!is_array($value)) {
            return [];
        }

        $items = array_map('intval', $value);
        $items = array_filter($items, static function(int $item): bool {
            return $item > 0;
        });

        return array_values(array_unique($items));
    }

    /**
     * Normalise a string list.
     *
     * @param mixed $value Raw value.
     * @return string[]
     */
    private static function normalise_string_list($value): array {
        if (is_string($value)) {
            $value = self::decode_string_list($value);
        }

        if (!is_array($value)) {
            return [];
        }

        $items = array_map(static function($item): string {
            return clean_param((string)$item, PARAM_TEXT);
        }, $value);

        $items = array_filter($items, static function(string $item): bool {
            return trim($item) !== '';
        });

        return array_values(array_unique($items));
    }

    /**
     * Encode a list as JSON.
     *
     * @param array<int|string, mixed> $items Items.
     * @return string
     */
    private static function encode_list(array $items): string {
        return json_encode(array_values($items), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Decode an integer list.
     *
     * @param string|null $json JSON.
     * @return int[]
     */
    private static function decode_int_list(?string $json): array {
        $decoded = json_decode($json ?: '[]', true);

        if (!is_array($decoded)) {
            return [];
        }

        return self::normalise_int_list($decoded);
    }

    /**
     * Decode a string list.
     *
     * @param string|null $json JSON.
     * @return string[]
     */
    private static function decode_string_list(?string $json): array {
        $decoded = json_decode($json ?: '[]', true);

        if (!is_array($decoded)) {
            return [];
        }

        return self::normalise_string_list($decoded);
    }

    /**
     * Encode metadata as JSON object.
     *
     * @param mixed $metadata Metadata.
     * @return string
     */
    private static function encode_metadata($metadata): string {
        if (is_string($metadata)) {
            $decoded = json_decode($metadata, true);
            $metadata = is_array($decoded) ? $decoded : [];
        }

        if (!is_array($metadata)) {
            $metadata = [];
        }

        return json_encode((object)$metadata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Decode metadata JSON.
     *
     * @param string|null $json JSON.
     * @return array<string, mixed>
     */
    private static function decode_metadata(?string $json): array {
        $decoded = json_decode($json ?: '{}', true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Write a provenance record if the table exists.
     *
     * The method is fail-soft because provenance should not break the core
     * pathway operation during early installation or table migration.
     *
     * @param string $itemtype Item type.
     * @param int $itemid Item id.
     * @param string $action Action.
     * @param context $context Context.
     * @param array<string, mixed> $metadata Metadata.
     * @return void
     */
    private static function write_provenance(
        string $itemtype,
        int $itemid,
        string $action,
        context $context,
        array $metadata = []
    ): void {
        global $DB, $USER;

        $dbman = $DB->get_manager();
        if (!$dbman->table_exists(self::TABLE_PROVENANCE)) {
            return;
        }

        $record = (object)[
            'component' => 'local_uckk',
            'itemtype' => clean_param($itemtype, PARAM_ALPHANUMEXT),
            'itemid' => $itemid,
            'contextid' => $context->id,
            'action' => clean_param($action, PARAM_ALPHANUMEXT),
            'sourcecomponent' => 'local_uckk',
            'sourceid' => $itemid,
            'sourcetext' => self::encode_metadata($metadata),
            'hash' => sha1(json_encode($metadata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)),
            'state' => 'recorded',
            'createdby' => $USER->id ?? 0,
            'timecreated' => time(),
        ];

        try {
            $DB->insert_record(self::TABLE_PROVENANCE, $record);
        } catch (\Throwable $e) {
            debugging('Unable to write UCKK provenance record: ' . $e->getMessage(), DEBUG_DEVELOPER);
        }
    }

    /**
     * Trigger an event if its class exists.
     *
     * This lets the API remain usable while event classes are generated.
     *
     * @param string $eventname Event short name.
     * @param context $context Context.
     * @param int $objectid Object id.
     * @param array<string, mixed> $other Additional event data.
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

        if (!method_exists($classname, 'create')) {
            throw new coding_exception('Invalid UCKK event class: ' . $classname);
        }

        $event = $classname::create([
            'context' => $context,
            'objectid' => $objectid,
            'other' => $other,
        ]);

        $event->trigger();
    }
}

