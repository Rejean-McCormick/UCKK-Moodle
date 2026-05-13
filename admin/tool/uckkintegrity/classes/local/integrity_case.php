<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

namespace tool_uckkintegrity\local;

defined('MOODLE_INTERNAL') || die();

use tool_uckkintegrity\event\case_opened;
use tool_uckkintegrity\event\case_reviewed;

/**
 * Service layer for UCKK integrity case records.
 *
 * Integrity cases are the auditable Inquisiteur workflow records used to
 * protect evidence quality, dignity, procedural justice, contestability,
 * archives, and institutional memory.
 *
 * @package    tool_uckkintegrity
 */
class integrity_case {
    /** Integrity case table. */
    public const TABLE = 'tool_uckkintegrity_case';

    /** Integrity case note table. */
    public const NOTE_TABLE = 'tool_uckkintegrity_note';

    /**
     * Create an integrity case.
     *
     * @param \stdClass $data Submitted case data.
     * @return int Created case id.
     */
    public static function create(\stdClass $data): int {
        global $DB, $USER;

        $context = \context::instance_by_id((int)$data->contextid, MUST_EXIST);
        require_capability('tool/uckkintegrity:opencase', $context);

        $now = time();

        $record = (object)[
            'casetype' => integrity_policy::validate_case_type((string)$data->casetype),
            'subjectcomponent' => clean_param((string)$data->subjectcomponent, PARAM_COMPONENT),
            'subjectid' => isset($data->subjectid) ? (int)$data->subjectid : 0,
            'contextid' => $context->id,
            'openedby' => $USER->id,
            'assignedto' => !empty($data->assignedto) ? (int)$data->assignedto : null,
            'severity' => self::normalise_severity($data->severity ?? severity::NORMAL),
            'status' => 'opened',
            'summary' => clean_text((string)$data->summary, FORMAT_PLAIN),
            'decision' => null,
            'correction' => null,
            'appealpath' => null,
            'archivesummary' => null,
            'archiveitemid' => null,
            'visibility' => self::normalise_visibility($data->visibility ?? confidentiality::RESTRICTED),
            'versionno' => 1,
            'provenancehash' => null,
            'metadata' => self::encode_metadata($data),
            'timecreated' => $now,
            'timemodified' => $now,
            'timeclosed' => null,
        ];

        $record->provenancehash = self::hash_record($record);

        $caseid = $DB->insert_record(self::TABLE, $record);

        if (!empty($data->casefiles)) {
            file_save_draft_area_files(
                $data->casefiles,
                $context->id,
                'tool_uckkintegrity',
                'case',
                $caseid,
                [
                    'subdirs' => 0,
                    'maxfiles' => 20,
                    'maxbytes' => 0,
                ]
            );
        }

        case_opened::create([
            'context' => $context,
            'objectid' => $caseid,
            'relateduserid' => $USER->id,
            'other' => [
                'casetype' => $record->casetype,
                'subjectcomponent' => $record->subjectcomponent,
                'subjectid' => $record->subjectid,
            ],
        ])->trigger();

        return $caseid;
    }

    /**
     * Fetch a case.
     *
     * @param int $id Case id.
     * @param bool $strict Whether to require an existing record.
     * @return \stdClass|null
     */
    public static function get(int $id, bool $strict = true): ?\stdClass {
        global $DB;

        $case = $DB->get_record(
            self::TABLE,
            ['id' => $id],
            '*',
            $strict ? MUST_EXIST : IGNORE_MISSING
        );

        if (!$case && $strict) {
            throw new \moodle_exception('invalidcaseid', 'tool_uckkintegrity');
        }

        return $case ?: null;
    }

    /**
     * Return filtered case records.
     *
     * @param array $filters Supported filters: status, severity, casetype, subjectcomponent, subjectid, assignedto, openedby.
     * @param int $page Page number.
     * @param int $perpage Records per page.
     * @return array
     */
    public static function get_cases(array $filters = [], int $page = 0, int $perpage = 50): array {
        global $DB;

        [$where, $params] = self::build_filter_sql($filters);

        $sql = "SELECT c.*
                  FROM {" . self::TABLE . "} c
                       $where
              ORDER BY c.timemodified DESC, c.id DESC";

        return array_values($DB->get_records_sql($sql, $params, $page * $perpage, $perpage));
    }

    /**
     * Count filtered case records.
     *
     * @param array $filters Supported filters.
     * @return int
     */
    public static function count_cases(array $filters = []): int {
        global $DB;

        [$where, $params] = self::build_filter_sql($filters);

        return (int)$DB->count_records_sql(
            "SELECT COUNT(1)
               FROM {" . self::TABLE . "} c
                    $where",
            $params
        );
    }

    /**
     * Fetch notes for a case.
     *
     * @param int $caseid Case id.
     * @return array
     */
    public static function notes(int $caseid): array {
        global $DB;

        return array_values($DB->get_records(
            self::NOTE_TABLE,
            ['caseid' => $caseid],
            'timecreated ASC, id ASC'
        ));
    }

    /**
     * Add an auditable note to a case.
     *
     * @param int $caseid Case id.
     * @param \stdClass $data Note data.
     * @return int Created note id.
     */
    public static function add_note(int $caseid, \stdClass $data): int {
        global $DB, $USER;

        $case = self::get($caseid);
        $context = \context::instance_by_id($case->contextid, MUST_EXIST);

        $notetype = self::normalise_note_type($data->notetype ?? 'observation');

        if ($notetype === 'response' || $notetype === 'appeal') {
            integrity_policy::require_can_view_case($case);
        } else {
            require_capability('tool/uckkintegrity:reviewcase', $context);
        }

        $now = time();

        $note = (object)[
            'caseid' => $caseid,
            'userid' => $USER->id,
            'notetype' => $notetype,
            'body' => clean_text((string)$data->body, FORMAT_PLAIN),
            'visibility' => self::normalise_visibility($data->visibility ?? confidentiality::RESTRICTED),
            'metadata' => self::encode_metadata($data),
            'timecreated' => $now,
            'timemodified' => $now,
        ];

        $noteid = $DB->insert_record(self::NOTE_TABLE, $note);

        $case->timemodified = $now;
        $case->versionno = (int)$case->versionno + 1;
        $case->provenancehash = self::hash_record($case);

        $DB->update_record(self::TABLE, $case);

        case_reviewed::create([
            'context' => $context,
            'objectid' => $caseid,
            'relateduserid' => $USER->id,
            'other' => [
                'notetype' => $notetype,
            ],
        ])->trigger();

        return $noteid;
    }

    /**
     * Move a case through the permitted integrity state machine.
     *
     * @param \stdClass $case Case record.
     * @param string $status Target status.
     * @param string|null $reason Optional transition reason.
     */
    public static function transition(\stdClass $case, string $status, ?string $reason = null): void {
        global $DB, $USER;

        $context = \context::instance_by_id($case->contextid, MUST_EXIST);
        require_capability('tool/uckkintegrity:reviewcase', $context);

        integrity_policy::require_transition($case, $status);

        $metadata = self::decode_metadata($case->metadata);

        $metadata['transitions'][] = [
            'from' => $case->status,
            'to' => $status,
            'userid' => $USER->id,
            'timecreated' => time(),
            'reason' => $reason,
        ];

        $case->status = $status;
        $case->timemodified = time();
        $case->versionno = (int)$case->versionno + 1;
        $case->metadata = json_encode($metadata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if (self::is_terminal_status($status)) {
            $case->timeclosed = $case->timeclosed ?: time();
        }

        $case->provenancehash = self::hash_record($case);

        $DB->update_record(self::TABLE, $case);
    }

    /**
     * Assign a case to an Inquisiteur.
     *
     * @param \stdClass $case Case record.
     * @param int $userid Reviewer user id.
     */
    public static function assign(\stdClass $case, int $userid): void {
        global $DB, $USER;

        $context = \context::instance_by_id($case->contextid, MUST_EXIST);
        require_capability('tool/uckkintegrity:assigncase', $context);

        $metadata = self::decode_metadata($case->metadata);
        $metadata['assignments'][] = [
            'from' => $case->assignedto ? (int)$case->assignedto : null,
            'to' => $userid,
            'userid' => $USER->id,
            'timecreated' => time(),
        ];

        $case->assignedto = $userid;
        $case->timemodified = time();
        $case->versionno = (int)$case->versionno + 1;
        $case->metadata = json_encode($metadata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $case->provenancehash = self::hash_record($case);

        $DB->update_record(self::TABLE, $case);
    }

    /**
     * Return grouped summary counts.
     *
     * @param string $field status, severity, or casetype.
     * @return array
     */
    public static function summary_counts(string $field): array {
        global $DB;

        if (!in_array($field, ['status', 'severity', 'casetype'], true)) {
            throw new \coding_exception('Unsupported integrity summary field.');
        }

        $sql = "SELECT $field, COUNT(1) AS total
                  FROM {" . self::TABLE . "}
              GROUP BY $field
              ORDER BY $field";

        return array_values($DB->get_records_sql($sql));
    }

    /**
     * Get case file metadata.
     *
     * @param \stdClass $case Case record.
     * @return array
     */
    public static function get_case_files(\stdClass $case): array {
        $context = \context::instance_by_id($case->contextid, MUST_EXIST);
        $fs = get_file_storage();

        $files = $fs->get_area_files(
            $context->id,
            'tool_uckkintegrity',
            'case',
            $case->id,
            'filename',
            false
        );

        $result = [];
        foreach ($files as $file) {
            $result[] = [
                'filename' => $file->get_filename(),
                'filepath' => $file->get_filepath(),
                'mimetype' => $file->get_mimetype(),
                'filesize' => $file->get_filesize(),
                'timecreated' => $file->get_timecreated(),
            ];
        }

        return $result;
    }

    /**
     * Build SQL for supported filters.
     *
     * @param array $filters Filters.
     * @return array SQL fragment and params.
     */
    private static function build_filter_sql(array $filters): array {
        $where = [];
        $params = [];

        foreach (['status', 'severity', 'casetype', 'subjectcomponent'] as $field) {
            if (!empty($filters[$field])) {
                $where[] = "c.$field = :$field";
                $params[$field] = $filters[$field];
            }
        }

        foreach (['subjectid', 'assignedto', 'openedby', 'contextid'] as $field) {
            if (isset($filters[$field]) && $filters[$field] !== '') {
                $where[] = "c.$field = :$field";
                $params[$field] = (int)$filters[$field];
            }
        }

        return [
            $where ? 'WHERE ' . implode(' AND ', $where) : '',
            $params,
        ];
    }

    /**
     * Convert variable submitted metadata into JSON.
     *
     * @param \stdClass $data Submitted data.
     * @return string|null
     */
    private static function encode_metadata(\stdClass $data): ?string {
        $metadata = [];

        if (!empty($data->metadata)) {
            $decoded = json_decode((string)$data->metadata, true);
            if (is_array($decoded)) {
                $metadata = $decoded;
            }
        }

        if (!empty($data->parties)) {
            $metadata['parties'] = is_array($data->parties)
                ? array_values($data->parties)
                : preg_split('/\s*,\s*/', (string)$data->parties, -1, PREG_SPLIT_NO_EMPTY);
        }

        if (!empty($data->evidencelinks)) {
            $metadata['evidencelinks'] = is_array($data->evidencelinks)
                ? array_values($data->evidencelinks)
                : preg_split('/\s*\R\s*/', (string)$data->evidencelinks, -1, PREG_SPLIT_NO_EMPTY);
        }

        return $metadata
            ? json_encode($metadata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            : null;
    }

    /**
     * Decode metadata safely.
     *
     * @param string|null $metadata Metadata JSON.
     * @return array
     */
    public static function decode_metadata(?string $metadata): array {
        $decoded = json_decode((string)$metadata, true);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Produce a provenance hash for a case state.
     *
     * @param \stdClass $record Case record.
     * @return string
     */
    public static function hash_record(\stdClass $record): string {
        $payload = [
            'casetype' => $record->casetype ?? null,
            'subjectcomponent' => $record->subjectcomponent ?? null,
            'subjectid' => $record->subjectid ?? null,
            'contextid' => $record->contextid ?? null,
            'openedby' => $record->openedby ?? null,
            'assignedto' => $record->assignedto ?? null,
            'severity' => $record->severity ?? null,
            'status' => $record->status ?? null,
            'summary' => $record->summary ?? null,
            'decision' => $record->decision ?? null,
            'correction' => $record->correction ?? null,
            'archiveitemid' => $record->archiveitemid ?? null,
            'versionno' => $record->versionno ?? null,
            'timemodified' => $record->timemodified ?? null,
        ];

        return hash('sha256', json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    /**
     * Normalise severity.
     *
     * @param string $value Submitted severity.
     * @return string
     */
    private static function normalise_severity(string $value): string {
        return in_array($value, severity::values(), true) ? $value : severity::NORMAL;
    }

    /**
     * Normalise visibility.
     *
     * @param string $value Submitted visibility.
     * @return string
     */
    private static function normalise_visibility(string $value): string {
        return in_array($value, confidentiality::values(), true) ? $value : confidentiality::RESTRICTED;
    }

    /**
     * Normalise note type.
     *
     * @param string $value Submitted note type.
     * @return string
     */
    private static function normalise_note_type(string $value): string {
        return in_array($value, integrity_policy::NOTE_TYPES, true) ? $value : 'observation';
    }

    /**
     * Whether the status represents a final or near-final case outcome.
     *
     * @param string $status Status.
     * @return bool
     */
    private static function is_terminal_status(string $status): bool {
        return in_array($status, ['resolved', 'dismissed', 'archived', 'closed'], true);
    }
}