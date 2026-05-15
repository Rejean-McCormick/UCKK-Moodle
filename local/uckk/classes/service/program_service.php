<?php
// This file is part of Moodle - https://moodle.org/

namespace local_uckk\service;

use context;
use context_coursecat;
use context_system;
use local_uckk\local\constants;
use stdClass;

defined('MOODLE_INTERNAL') || die();

/**
 * Program registry service for local_uckk.
 *
 * @package    local_uckk
 */
final class program_service {
    private const TABLE = 'local_uckk_program';

    public function create_program(array $payload, ?context $context = null, ?int $actorid = null): stdClass {
        global $DB, $USER;

        $context = $context ?? context_system::instance();
        require_capability('local/uckk:manageprograms', $context);

        $now = time();
        $actorid = $actorid ?? (int)($USER->id ?? 0);

        $shortname = trim((string)($payload['shortname'] ?? ''));
        $fullname = trim((string)($payload['fullname'] ?? ''));
        $programtype = constants::assert_allowed((string)($payload['programtype'] ?? ''), constants::allowed_program_types(), 'Invalid program type.');

        if ($shortname === '' || $fullname === '') {
            throw new \invalid_parameter_exception('Program shortname and fullname are required.');
        }

        $status = $this->normalise_status((string)($payload['status'] ?? constants::STATUS_DRAFT));
        $visibility = $this->normalise_visibility((string)($payload['visibility'] ?? constants::VISIBILITY_INSTITUTION));
        $categoryid = empty($payload['categoryid']) ? null : (int)$payload['categoryid'];
        $recordcontext = $this->resolve_program_context($context, $categoryid, $payload);

        $record = (object)[
            'shortname' => clean_param($shortname, PARAM_ALPHANUMEXT),
            'fullname' => $fullname,
            'programtype' => $programtype,
            'categoryid' => $categoryid,
            'description' => (string)($payload['description'] ?? ''),
            'descriptionformat' => (int)($payload['descriptionformat'] ?? FORMAT_HTML),
            'sortorder' => (int)($payload['sortorder'] ?? 0),
            'courseid' => empty($payload['courseid']) ? null : (int)$payload['courseid'],
            'cmid' => empty($payload['cmid']) ? null : (int)$payload['cmid'],
            'contextid' => $recordcontext->id,
            'userid' => empty($payload['userid']) ? null : (int)$payload['userid'],
            'createdby' => $actorid,
            'modifiedby' => $actorid,
            'timecreated' => $now,
            'timemodified' => $now,
            'status' => $status,
            'visibility' => $visibility,
            'versionno' => 1,
            'metadata' => $this->encode_json($payload['metadata'] ?? []),
        ];
        $record->provenancehash = $this->build_hash('program', $record->shortname, $record->metadata, $now);

        $record->id = $DB->insert_record(self::TABLE, $record);

        return $this->export_record($DB->get_record(self::TABLE, ['id' => $record->id], '*', MUST_EXIST));
    }

    public function get_program(int $programid, ?context $context = null): stdClass {
        global $DB;

        $context = $context ?? context_system::instance();
        require_capability('local/uckk:viewcampus', $context);

        $record = $DB->get_record(self::TABLE, ['id' => $programid], '*', MUST_EXIST);
        $this->assert_visibility_access($record, $context);

        return $this->export_record($record);
    }

    public function get_program_by_shortname(string $shortname, ?context $context = null): stdClass {
        global $DB;

        $context = $context ?? context_system::instance();
        require_capability('local/uckk:viewcampus', $context);

        $record = $DB->get_record(self::TABLE, ['shortname' => clean_param($shortname, PARAM_ALPHANUMEXT)], '*', MUST_EXIST);
        $this->assert_visibility_access($record, $context);

        return $this->export_record($record);
    }

    public function list_programs(array $filters = [], ?context $context = null): array {
        global $DB;

        $context = $context ?? context_system::instance();
        require_capability('local/uckk:viewcampus', $context);

        $records = $DB->get_records(self::TABLE, [], 'sortorder ASC, fullname ASC');
        $output = [];

        foreach ($records as $record) {
            if (!$this->matches_filters($record, $filters)) {
                continue;
            }
            if (!$this->can_view_record($record, $context)) {
                continue;
            }
            $output[] = $this->export_record($record);
        }

        return $output;
    }

    public function update_program(int $programid, array $changes, ?context $context = null, ?int $actorid = null): stdClass {
        global $DB, $USER;

        $record = $DB->get_record(self::TABLE, ['id' => $programid], '*', MUST_EXIST);
        $context = $context ?? context::instance_by_id((int)$record->contextid, IGNORE_MISSING) ?? context_system::instance();
        require_capability('local/uckk:manageprograms', $context);

        $actorid = $actorid ?? (int)($USER->id ?? 0);
        $record->fullname = array_key_exists('fullname', $changes) ? trim((string)$changes['fullname']) : $record->fullname;
        $record->description = array_key_exists('description', $changes) ? (string)$changes['description'] : $record->description;
        $record->descriptionformat = array_key_exists('descriptionformat', $changes) ? (int)$changes['descriptionformat'] : (int)$record->descriptionformat;
        $record->sortorder = array_key_exists('sortorder', $changes) ? (int)$changes['sortorder'] : (int)$record->sortorder;
        $record->status = array_key_exists('status', $changes) ? $this->normalise_status((string)$changes['status']) : $record->status;
        $record->visibility = array_key_exists('visibility', $changes) ? $this->normalise_visibility((string)$changes['visibility']) : $record->visibility;
        $record->programtype = array_key_exists('programtype', $changes)
            ? constants::assert_allowed((string)$changes['programtype'], constants::allowed_program_types(), 'Invalid program type.')
            : $record->programtype;
        $record->categoryid = array_key_exists('categoryid', $changes)
            ? (empty($changes['categoryid']) ? null : (int)$changes['categoryid'])
            : $record->categoryid;
        $record->contextid = $this->resolve_program_context($context, $record->categoryid, $changes + (array)$record)->id;
        if (array_key_exists('metadata', $changes)) {
            $record->metadata = $this->encode_json($changes['metadata'] ?? []);
        }

        if (trim((string)$record->fullname) === '') {
            throw new \invalid_parameter_exception('Program fullname is required.');
        }

        $record->versionno = (int)$record->versionno + 1;
        $record->modifiedby = $actorid;
        $record->timemodified = time();
        $record->provenancehash = $this->build_hash('program', $record->shortname, (string)$record->metadata, (int)$record->timemodified);

        $DB->update_record(self::TABLE, $record);

        return $this->export_record($DB->get_record(self::TABLE, ['id' => $programid], '*', MUST_EXIST));
    }

    public function archive_program(int $programid, ?context $context = null, ?int $actorid = null): stdClass {
        return $this->update_program($programid, ['status' => constants::STATUS_ARCHIVED], $context, $actorid);
    }

    private function matches_filters(stdClass $record, array $filters): bool {
        foreach (['status', 'visibility', 'programtype', 'shortname'] as $field) {
            if (!array_key_exists($field, $filters) || $filters[$field] === '' || $filters[$field] === null) {
                continue;
            }
            if ((string)$record->{$field} !== constants::normalise_key((string)$filters[$field])) {
                return false;
            }
        }
        return true;
    }

    private function can_view_record(stdClass $record, context $context): bool {
        $visibility = constants::normalise_key((string)$record->visibility);
        $status = constants::normalise_key((string)$record->status);

        if (in_array($visibility, [constants::VISIBILITY_RESTRICTED, constants::VISIBILITY_RESTRICTED_INTEGRITY, constants::VISIBILITY_HIDDEN], true)
            || $status === constants::STATUS_HIDDEN) {
            return has_capability('local/uckk:viewrestricted', $context);
        }

        return true;
    }

    private function assert_visibility_access(stdClass $record, context $context): void {
        if (!$this->can_view_record($record, $context)) {
            require_capability('local/uckk:viewrestricted', $context);
        }
    }

    private function resolve_program_context(context $defaultcontext, ?int $categoryid, array $payload): context {
        if (!empty($payload['contextid'])) {
            return context::instance_by_id((int)$payload['contextid']);
        }
        if (!empty($categoryid)) {
            return context_coursecat::instance((int)$categoryid);
        }
        return $defaultcontext;
    }

    private function export_record(stdClass $record): stdClass {
        $record->categoryid = empty($record->categoryid) ? null : (int)$record->categoryid;
        $record->contextid = (int)$record->contextid;
        $record->createdby = (int)$record->createdby;
        $record->modifiedby = empty($record->modifiedby) ? 0 : (int)$record->modifiedby;
        $record->versionno = (int)$record->versionno;
        $record->sortorder = (int)$record->sortorder;
        $record->metadata = $this->decode_json($record->metadata ?? '');
        return $record;
    }

    private function normalise_status(string $status): string {
        return constants::assert_allowed($status, constants::allowed_statuses(), 'Invalid program status.');
    }

    private function normalise_visibility(string $visibility): string {
        return constants::assert_allowed($visibility, constants::allowed_visibilities(), 'Invalid program visibility.');
    }

    private function decode_json(?string $value): array {
        if ($value === null || trim($value) == '') {
            return [];
        }
        $decoded = json_decode($value, true);
        return is_array($decoded) ? $decoded : [];
    }

    private function encode_json($value): string {
        $json = json_encode($value ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        return $json === false ? '{}' : $json;
    }

    private function build_hash(string $type, string $key, string $payload, int $time): string {
        return hash('sha256', implode('|', [constants::COMPONENT, $type, $key, $payload, (string)$time]));
    }
}
