<?php
// This file is part of Moodle - https://moodle.org/

namespace local_uckk\service;

use coding_exception;
use context;
use context_course;
use context_coursecat;
use context_system;
use local_uckk\event\pathway_assigned;
use local_uckk\local\constants;
use moodle_url;
use stdClass;

defined('MOODLE_INTERNAL') || die();

/**
 * Pathway service for local_uckk.
 *
 * @package    local_uckk
 */
final class pathway_service {
    private const TABLE = 'local_uckk_pathway';
    private const ASSIGNMENT_TABLE = 'local_uckk_map';
    private const STAT_TABLE = 'local_uckk_pathway_stat';

    public function create_pathway(array $payload, ?context $context = null, ?int $actorid = null): stdClass {
        global $DB, $USER;

        $program = $DB->get_record('local_uckk_program', ['id' => (int)($payload['programid'] ?? 0)], '*', MUST_EXIST);
        $context = $context
            ?? (!empty($payload['contextid']) ? context::instance_by_id((int)$payload['contextid']) : context::instance_by_id((int)$program->contextid, IGNORE_MISSING))
            ?? context_system::instance();
        require_capability('local/uckk:managepathways', $context);

        $actorid = $actorid ?? (int)($USER->id ?? 0);
        $shortname = trim((string)($payload['shortname'] ?? ''));
        $fullname = trim((string)($payload['fullname'] ?? ''));
        if ($shortname === '' || $fullname === '') {
            throw new \invalid_parameter_exception('Pathway shortname and fullname are required.');
        }

        $now = time();
        $record = (object)[
            'programid' => (int)$program->id,
            'shortname' => clean_param($shortname, PARAM_ALPHANUMEXT),
            'fullname' => $fullname,
            'pathwaytype' => constants::normalise_key((string)($payload['pathwaytype'] ?? $program->programtype ?? constants::TYPE_TRONC_COMMUN)),
            'requiredcourseids' => $this->encode_json($this->normalise_int_array($payload['requiredcourseids'] ?? [])),
            'requiredbadges' => $this->encode_json($this->normalise_string_array($payload['requiredbadges'] ?? [])),
            'requiredcompetencies' => $this->encode_json($this->normalise_string_array($payload['requiredcompetencies'] ?? [])),
            'description' => (string)($payload['description'] ?? ''),
            'descriptionformat' => (int)($payload['descriptionformat'] ?? FORMAT_HTML),
            'sortorder' => (int)($payload['sortorder'] ?? 0),
            'courseid' => empty($payload['courseid']) ? null : (int)$payload['courseid'],
            'cmid' => empty($payload['cmid']) ? null : (int)$payload['cmid'],
            'contextid' => $context->id,
            'userid' => empty($payload['userid']) ? null : (int)$payload['userid'],
            'createdby' => $actorid,
            'modifiedby' => $actorid,
            'timecreated' => $now,
            'timemodified' => $now,
            'status' => constants::assert_allowed((string)($payload['status'] ?? constants::STATUS_DRAFT), array_merge(constants::allowed_statuses(), [constants::STATUS_HIDDEN]), 'Invalid pathway status.'),
            'visibility' => constants::assert_allowed((string)($payload['visibility'] ?? constants::VISIBILITY_PROGRAM), constants::allowed_visibilities(), 'Invalid pathway visibility.'),
            'versionno' => 1,
            'metadata' => $this->encode_json($payload['metadata'] ?? []),
        ];
        $record->provenancehash = $this->build_hash('pathway', $record->shortname, $record->metadata, $now);

        $record->id = $DB->insert_record(self::TABLE, $record);

        return $this->export_pathway($DB->get_record(self::TABLE, ['id' => $record->id], '*', MUST_EXIST));
    }

    public function update_pathway(int $pathwayid, array $changes, ?context $context = null, ?int $actorid = null): stdClass {
        global $DB, $USER;

        $record = $DB->get_record(self::TABLE, ['id' => $pathwayid], '*', MUST_EXIST);
        $context = $context ?? context::instance_by_id((int)$record->contextid, IGNORE_MISSING) ?? context_system::instance();
        require_capability('local/uckk:managepathways', $context);
        $actorid = $actorid ?? (int)($USER->id ?? 0);

        foreach (['fullname', 'description'] as $field) {
            if (array_key_exists($field, $changes)) {
                $record->{$field} = (string)$changes[$field];
            }
        }
        if (array_key_exists('descriptionformat', $changes)) {
            $record->descriptionformat = (int)$changes['descriptionformat'];
        }
        if (array_key_exists('sortorder', $changes)) {
            $record->sortorder = (int)$changes['sortorder'];
        }
        if (array_key_exists('status', $changes)) {
            $record->status = constants::assert_allowed((string)$changes['status'], array_merge(constants::allowed_statuses(), [constants::STATUS_HIDDEN]), 'Invalid pathway status.');
        }
        if (array_key_exists('visibility', $changes)) {
            $record->visibility = constants::assert_allowed((string)$changes['visibility'], constants::allowed_visibilities(), 'Invalid pathway visibility.');
        }
        if (array_key_exists('requiredcourseids', $changes)) {
            $record->requiredcourseids = $this->encode_json($this->normalise_int_array($changes['requiredcourseids'] ?? []));
        }
        if (array_key_exists('requiredbadges', $changes)) {
            $record->requiredbadges = $this->encode_json($this->normalise_string_array($changes['requiredbadges'] ?? []));
        }
        if (array_key_exists('requiredcompetencies', $changes)) {
            $record->requiredcompetencies = $this->encode_json($this->normalise_string_array($changes['requiredcompetencies'] ?? []));
        }
        if (array_key_exists('metadata', $changes)) {
            $record->metadata = $this->encode_json($changes['metadata'] ?? []);
        }

        $record->modifiedby = $actorid;
        $record->timemodified = time();
        $record->versionno = (int)$record->versionno + 1;
        $record->provenancehash = $this->build_hash('pathway', $record->shortname, (string)$record->metadata, (int)$record->timemodified);
        $DB->update_record(self::TABLE, $record);

        return $this->export_pathway($DB->get_record(self::TABLE, ['id' => $pathwayid], '*', MUST_EXIST));
    }

    public function archive_pathway(int $pathwayid, ?context $context = null, ?int $actorid = null): stdClass {
        return $this->update_pathway($pathwayid, ['status' => constants::STATUS_ARCHIVED], $context, $actorid);
    }

    public function get_pathway(int $pathwayid, ?context $context = null): stdClass {
        global $DB;
        $record = $DB->get_record(self::TABLE, ['id' => $pathwayid], '*', MUST_EXIST);
        $context = $context ?? context::instance_by_id((int)$record->contextid, IGNORE_MISSING) ?? context_system::instance();
        require_capability('local/uckk:viewcampus', $context);
        if (!$this->can_view_pathway($record, $context)) {
            require_capability('local/uckk:viewrestricted', $context);
        }
        return $this->export_pathway($record);
    }

    public function get_pathways(array $filters = [], ?context $context = null, array $options = []): array {
        global $DB;
        $context = $context ?? context_system::instance();
        require_capability('local/uckk:viewcampus', $context);

        $records = $DB->get_records(self::TABLE, [], 'sortorder ASC, fullname ASC', '*', (int)($options['offset'] ?? 0), (int)($options['limit'] ?? 0));
        $includehidden = !empty($options['includehidden']);
        $output = [];
        foreach ($records as $record) {
            if (!$this->matches_filters($record, $filters)) {
                continue;
            }
            if (!$includehidden && !$this->can_view_pathway($record, $context)) {
                continue;
            }
            if ($includehidden && !$this->can_view_hidden_pathway($record, $context)) {
                continue;
            }
            $output[] = $this->export_pathway($record);
        }
        return $output;
    }

    public function count_pathways(array $filters = [], ?context $context = null): int {
        return count($this->get_pathways($filters, $context));
    }

    public function assign_pathway(int $userid, int $pathwayid, int $contextid, ?int $assignedby = null): stdClass {
        global $DB, $USER;

        $context = context::instance_by_id($contextid);
        require_capability('local/uckk:managepathways', $context);

        $assignedby = $assignedby ?? (int)($USER->id ?? 0);
        $pathway = $DB->get_record(self::TABLE, ['id' => $pathwayid], '*', MUST_EXIST);
        $profile = (new profile_service())->get_or_create_profile($userid);

        $existing = $this->get_assignment_record($userid, $pathwayid);
        if ($existing) {
            return $this->export_assignment($existing);
        }

        $now = time();
        $record = (object)[
            'mapkey' => 'pathway_assignment_' . $userid . '_' . $pathwayid,
            'name' => 'Pathway assignment',
            'maptype' => 'assignment',
            'sourcecomponent' => constants::COMPONENT . '_player',
            'sourceid' => $userid,
            'targetcomponent' => constants::COMPONENT . '_pathway',
            'targetid' => $pathwayid,
            'relationtype' => 'assigned_pathway',
            'weight' => 100,
            'description' => '',
            'descriptionformat' => FORMAT_HTML,
            'courseid' => null,
            'cmid' => null,
            'contextid' => $context->id,
            'userid' => $userid,
            'createdby' => $assignedby,
            'modifiedby' => $assignedby,
            'timecreated' => $now,
            'timemodified' => $now,
            'status' => constants::STATUS_ACTIVE,
            'visibility' => constants::VISIBILITY_USER,
            'versionno' => 1,
            'metadata' => $this->encode_json([
                'profileid' => (int)$profile->id,
                'progress' => constants::PROGRESS_NOT_STARTED,
            ]),
        ];
        $record->provenancehash = $this->build_hash('assignment', $record->mapkey, (string)$record->metadata, $now);
        $record->id = $DB->insert_record(self::ASSIGNMENT_TABLE, $record);

        (new profile_service())->add_active_pathway($userid, $pathwayid);
        $this->upsert_pathway_stat($userid, $pathwayid, $pathway, constants::PROGRESS_NOT_STARTED, $assignedby);

        $event = pathway_assigned::create([
            'context' => $context,
            'objectid' => $pathwayid,
            'relateduserid' => $userid,
            'other' => [
                'assignmentid' => (int)$record->id,
            ],
        ]);
        $event->trigger();

        return $this->export_assignment($record);
    }

    public function set_pathway_progress(int $userid, int $pathwayid, string $progress, ?int $modifiedby = null): stdClass {
        global $DB, $USER;

        $progress = constants::normalise_key($progress);
        if (!in_array($progress, constants::allowed_progress_states(), true)) {
            throw new coding_exception('Non-canonical pathway progress state: ' . $progress);
        }

        $modifiedby = $modifiedby ?? (int)($USER->id ?? 0);
        $assignment = $this->get_assignment_record($userid, $pathwayid, true);
        $metadata = $this->decode_json($assignment->metadata ?? '{}');
        $metadata['progress'] = $progress;
        $assignment->metadata = $this->encode_json($metadata);
        $assignment->modifiedby = $modifiedby;
        $assignment->timemodified = time();
        $assignment->versionno = (int)$assignment->versionno + 1;
        $assignment->provenancehash = $this->build_hash('assignment', $assignment->mapkey, (string)$assignment->metadata, (int)$assignment->timemodified);
        $DB->update_record(self::ASSIGNMENT_TABLE, $assignment);

        $pathway = $DB->get_record(self::TABLE, ['id' => $pathwayid], '*', MUST_EXIST);
        $this->upsert_pathway_stat($userid, $pathwayid, $pathway, $progress, $modifiedby);

        return $this->export_assignment($DB->get_record(self::ASSIGNMENT_TABLE, ['id' => $assignment->id], '*', MUST_EXIST));
    }

    public function get_user_pathway_state(int $userid, int $pathwayid): stdClass {
        return $this->export_assignment($this->get_assignment_record($userid, $pathwayid, true));
    }

    public function get_user_pathways(int $userid, bool $includehidden = false): array {
        global $DB;

        $assignments = $DB->get_records(self::ASSIGNMENT_TABLE, [
            'sourcecomponent' => constants::COMPONENT . '_player',
            'sourceid' => $userid,
            'relationtype' => 'assigned_pathway',
        ], 'timemodified DESC');

        $output = [];
        $viewercontext = context_system::instance();
        foreach ($assignments as $assignment) {
            $pathway = $DB->get_record(self::TABLE, ['id' => (int)$assignment->targetid], '*', IGNORE_MISSING);
            if (!$pathway) {
                continue;
            }
            if (!$includehidden && !$this->can_view_pathway($pathway, $viewercontext)) {
                continue;
            }
            if ($includehidden && !$this->can_view_hidden_pathway($pathway, $viewercontext)) {
                continue;
            }
            $item = $this->export_assignment($assignment);
            $item->shortname = $pathway->shortname;
            $item->fullname = $pathway->fullname;
            $item->programid = (int)$pathway->programid;
            $item->url = self::get_pathway_url((int)$pathway->id)->out(false);
            $stat = $this->get_pathway_stat($userid, (int)$pathway->id);
            $item->progresspercent = (int)($stat->progresspercent ?? $this->progress_to_percent($item->progress));
            $output[] = $item;
        }
        return $output;
    }

    public function get_pathway_map(int $pathwayid, int $userid = 0, bool $includecompleted = true): array {
        $pathway = $this->get_pathway($pathwayid);
        $state = $userid > 0 ? $this->get_assignment_record($userid, $pathwayid) : null;
        $progress = constants::PROGRESS_NOT_STARTED;
        if ($state) {
            $progress = $this->extract_progress($state);
        }
        $stat = $userid > 0 ? $this->get_pathway_stat($userid, $pathwayid) : null;
        $programname = '';
        if (!empty($pathway->programid)) {
            try {
                $programname = (new program_service())->get_program((int)$pathway->programid)->fullname;
            } catch (\Throwable $e) {
                $programname = '';
            }
        }

        $requirements = [
            [
                'label' => get_string('courses', constants::COMPONENT),
                'value' => (($stat->completedcourses ?? 0) . ' / ' . count($pathway->requiredcourseids)),
                'complete' => (($stat->completedcourses ?? 0) >= count($pathway->requiredcourseids)) && count($pathway->requiredcourseids) > 0,
            ],
            [
                'label' => get_string('competencies', constants::COMPONENT),
                'value' => (($stat->completedcompetencies ?? 0) . ' / ' . count($pathway->requiredcompetencies)),
                'complete' => (($stat->completedcompetencies ?? 0) >= count($pathway->requiredcompetencies)) && count($pathway->requiredcompetencies) > 0,
            ],
            [
                'label' => get_string('badges', constants::COMPONENT),
                'value' => (($stat->earnedbadges ?? 0) . ' / ' . count($pathway->requiredbadges)),
                'complete' => (($stat->earnedbadges ?? 0) >= count($pathway->requiredbadges)) && count($pathway->requiredbadges) > 0,
            ],
        ];

        return [
            'templatecontext' => [
                'id' => (int)$pathway->id,
                'shortname' => (string)$pathway->shortname,
                'fullname' => (string)$pathway->fullname,
                'programname' => $programname,
                'status' => (string)$pathway->status,
                'statuslabel' => constants::status_label((string)$pathway->status),
                'statusclass' => 'status-' . constants::normalise_key((string)$pathway->status),
                'progresspercent' => (int)($stat->progresspercent ?? $this->progress_to_percent($progress)),
                'progresslabel' => (int)($stat->progresspercent ?? $this->progress_to_percent($progress)) . ' %',
                'coursescompleted' => (int)($stat->completedcourses ?? 0),
                'coursestotal' => count($pathway->requiredcourseids),
                'competenciesachieved' => (int)($stat->completedcompetencies ?? 0),
                'competenciestotal' => count($pathway->requiredcompetencies),
                'badgesearned' => (int)($stat->earnedbadges ?? 0),
                'badgestotal' => count($pathway->requiredbadges),
                'url' => self::get_pathway_url((int)$pathway->id)->out(false),
                'hasdescription' => trim((string)$pathway->description) !== '',
                'description' => format_text((string)$pathway->description, (int)($pathway->descriptionformat ?? FORMAT_HTML)),
                'hasnextaction' => false,
                'nextactionlabel' => '',
                'nextactionurl' => '',
                'hasintegritynotice' => false,
                'integritynotice' => '',
                'hasrequirements' => true,
                'requirements' => array_values(array_filter($requirements, static fn(array $item): bool => (string)$item['value'] !== '0 / 0')),
                'statusbadge' => [
                    'status' => (string)$pathway->status,
                    'label' => constants::status_label((string)$pathway->status),
                ],
            ],
        ];
    }

    public function rebuild_pathway_statistics(?int $userid = null, ?int $pathwayid = null): void {
        global $DB;

        $conditions = [
            'sourcecomponent' => constants::COMPONENT . '_player',
            'relationtype' => 'assigned_pathway',
        ];
        if ($userid !== null) {
            $conditions['sourceid'] = $userid;
        }
        $assignments = $DB->get_records(self::ASSIGNMENT_TABLE, $conditions);
        foreach ($assignments as $assignment) {
            if ($pathwayid !== null && (int)$assignment->targetid !== $pathwayid) {
                continue;
            }
            $pathway = $DB->get_record(self::TABLE, ['id' => (int)$assignment->targetid], '*', IGNORE_MISSING);
            if (!$pathway) {
                continue;
            }
            $this->upsert_pathway_stat((int)$assignment->sourceid, (int)$assignment->targetid, $pathway, $this->extract_progress($assignment), (int)$assignment->modifiedby);
        }
    }

    public static function get_allowed_statuses(): array {
        return array_merge(constants::allowed_statuses(), [constants::STATUS_HIDDEN]);
    }

    public static function get_allowed_visibilities(): array {
        return constants::allowed_visibilities();
    }

    public static function get_pathway_url(int $pathwayid): moodle_url {
        return new moodle_url('/local/uckk/pathways.php', ['id' => $pathwayid]);
    }

    private function matches_filters(stdClass $record, array $filters): bool {
        foreach (['programid', 'shortname', 'status', 'visibility'] as $field) {
            if (!array_key_exists($field, $filters) || $filters[$field] === '' || $filters[$field] === null) {
                continue;
            }
            $wanted = $field === 'programid' ? (int)$filters[$field] : constants::normalise_key((string)$filters[$field]);
            $actual = $field === 'programid' ? (int)$record->{$field} : constants::normalise_key((string)$record->{$field});
            if ($wanted !== $actual) {
                return false;
            }
        }
        return true;
    }

    private function can_view_pathway(stdClass $pathway, context $context): bool {
        if (in_array(constants::normalise_key((string)$pathway->visibility), [constants::VISIBILITY_RESTRICTED, constants::VISIBILITY_RESTRICTED_INTEGRITY, constants::VISIBILITY_HIDDEN], true)
            || constants::normalise_key((string)$pathway->status) === constants::STATUS_HIDDEN) {
            return has_capability('local/uckk:viewrestricted', $context);
        }
        return true;
    }

    private function can_view_hidden_pathway(stdClass $pathway, context $context): bool {
        if ($this->can_view_pathway($pathway, $context)) {
            return true;
        }
        return has_capability('local/uckk:viewrestricted', $context);
    }

    private function get_assignment_record(int $userid, int $pathwayid, bool $mustexist = false): ?stdClass {
        global $DB;
        $record = $DB->get_record(self::ASSIGNMENT_TABLE, [
            'sourcecomponent' => constants::COMPONENT . '_player',
            'sourceid' => $userid,
            'targetcomponent' => constants::COMPONENT . '_pathway',
            'targetid' => $pathwayid,
            'relationtype' => 'assigned_pathway',
        ], '*', $mustexist ? MUST_EXIST : IGNORE_MISSING);
        return $record ?: null;
    }

    private function export_pathway(stdClass $record): stdClass {
        global $DB;
        $record->requiredcourseids = array_map('intval', $this->decode_json($record->requiredcourseids ?? '[]'));
        $record->requiredbadges = $this->normalise_string_array($this->decode_json($record->requiredbadges ?? '[]'));
        $record->requiredcompetencies = $this->normalise_string_array($this->decode_json($record->requiredcompetencies ?? '[]'));
        $record->metadata = $this->decode_json($record->metadata ?? '{}');
        $record->coursecount = count($record->requiredcourseids);
        $record->requiredcoursecount = $record->coursecount;
        $record->requiredcompetencycount = count($record->requiredcompetencies);
        $record->requiredbadgecount = count($record->requiredbadges);
        if (!empty($record->programid)) {
            $program = $DB->get_record('local_uckk_program', ['id' => (int)$record->programid], 'id, shortname, fullname', IGNORE_MISSING);
            if ($program) {
                $record->programshortname = $program->shortname;
                $record->programfullname = $program->fullname;
            }
        }
        return $record;
    }

    private function export_assignment(stdClass $record): stdClass {
        $metadata = $this->decode_json($record->metadata ?? '{}');
        $record->userid = (int)$record->sourceid;
        $record->pathwayid = (int)$record->targetid;
        $record->progress = (string)($metadata['progress'] ?? constants::PROGRESS_NOT_STARTED);
        $record->status = (string)$record->status;
        $record->visibility = (string)$record->visibility;
        $record->metadata = $metadata;
        return $record;
    }

    private function upsert_pathway_stat(int $userid, int $pathwayid, stdClass $pathway, string $progress, int $actorid): void {
        global $DB;

        $requiredcourses = array_map('intval', $this->decode_json($pathway->requiredcourseids ?? '[]'));
        $requiredbadges = $this->normalise_string_array($this->decode_json($pathway->requiredbadges ?? '[]'));
        $requiredcompetencies = $this->normalise_string_array($this->decode_json($pathway->requiredcompetencies ?? '[]'));

        $completedcourses = $this->count_completed_courses($userid, $requiredcourses);
        $totalcourses = count($requiredcourses);
        $earnedbadges = 0;
        $completedcompetencies = 0;

        $percent = $this->progress_to_percent($progress);
        if ($totalcourses > 0) {
            $percent = max($percent, (int)floor(($completedcourses / max(1, $totalcourses)) * 100));
        }
        if ($progress === constants::PROGRESS_COMPLETED) {
            $percent = 100;
        }

        $stat = $DB->get_record(self::STAT_TABLE, ['userid' => $userid, 'pathwayid' => $pathwayid]);
        $now = time();
        if (!$stat) {
            $stat = (object)[
                'programid' => (int)$pathway->programid,
                'pathwayid' => $pathwayid,
                'totalcourses' => $totalcourses,
                'completedcourses' => $completedcourses,
                'totalbadges' => count($requiredbadges),
                'earnedbadges' => $earnedbadges,
                'totalcompetencies' => count($requiredcompetencies),
                'completedcompetencies' => $completedcompetencies,
                'progresspercent' => $percent,
                'lastcalculated' => $now,
                'statdata' => $this->encode_json(['progress' => $progress]),
                'courseid' => null,
                'cmid' => null,
                'contextid' => (int)$pathway->contextid,
                'userid' => $userid,
                'createdby' => $actorid,
                'modifiedby' => $actorid,
                'timecreated' => $now,
                'timemodified' => $now,
                'status' => constants::STATUS_ACTIVE,
                'visibility' => constants::VISIBILITY_USER,
                'versionno' => 1,
                'metadata' => $this->encode_json([]),
            ];
            $stat->provenancehash = $this->build_hash('pathway_stat', $userid . '_' . $pathwayid, $stat->statdata, $now);
            $DB->insert_record(self::STAT_TABLE, $stat);
            return;
        }

        $stat->programid = (int)$pathway->programid;
        $stat->totalcourses = $totalcourses;
        $stat->completedcourses = $completedcourses;
        $stat->totalbadges = count($requiredbadges);
        $stat->earnedbadges = $earnedbadges;
        $stat->totalcompetencies = count($requiredcompetencies);
        $stat->completedcompetencies = $completedcompetencies;
        $stat->progresspercent = $percent;
        $stat->lastcalculated = $now;
        $stat->statdata = $this->encode_json(['progress' => $progress]);
        $stat->modifiedby = $actorid;
        $stat->timemodified = $now;
        $stat->versionno = (int)$stat->versionno + 1;
        $stat->provenancehash = $this->build_hash('pathway_stat', $userid . '_' . $pathwayid, $stat->statdata, $now);
        $DB->update_record(self::STAT_TABLE, $stat);
    }

    private function get_pathway_stat(int $userid, int $pathwayid): ?stdClass {
        global $DB;
        return $DB->get_record(self::STAT_TABLE, ['userid' => $userid, 'pathwayid' => $pathwayid]) ?: null;
    }

    private function count_completed_courses(int $userid, array $courseids): int {
        global $DB;
        if (empty($courseids) || !$DB->get_manager()->table_exists('course_completions')) {
            return 0;
        }
        [$insql, $params] = $DB->get_in_or_equal($courseids, SQL_PARAMS_NAMED);
        $params['userid'] = $userid;
        return $DB->count_records_select('course_completions', "userid = :userid AND course $insql AND timecompleted IS NOT NULL", $params);
    }

    private function extract_progress(stdClass $assignment): string {
        $metadata = $this->decode_json($assignment->metadata ?? '{}');
        return (string)($metadata['progress'] ?? constants::PROGRESS_NOT_STARTED);
    }

    private function progress_to_percent(string $progress): int {
        return match (constants::normalise_key($progress)) {
            constants::PROGRESS_COMPLETED => 100,
            constants::PROGRESS_IN_PROGRESS => 50,
            constants::PROGRESS_BLOCKED => 0,
            default => 0,
        };
    }

    private function normalise_int_array($values): array {
        $values = is_array($values) ? $values : [];
        $values = array_values(array_unique(array_map('intval', $values)));
        return array_values(array_filter($values, static fn(int $value): bool => $value > 0));
    }

    private function normalise_string_array($values): array {
        $values = is_array($values) ? $values : [];
        $values = array_map(static fn($value): string => trim((string)$value), $values);
        $values = array_values(array_unique(array_filter($values, static fn(string $value): bool => $value !== '')));
        return $values;
    }

    private function decode_json(?string $value): array {
        if ($value === null || trim($value) === '') {
            return [];
        }
        $decoded = json_decode($value, true);
        return is_array($decoded) ? $decoded : [];
    }

    private function encode_json($value): string {
        $json = json_encode($value ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        return $json === false ? '[]' : $json;
    }

    private function build_hash(string $type, string $key, string $payload, int $time): string {
        return hash('sha256', implode('|', [constants::COMPONENT, $type, $key, $payload, (string)$time]));
    }
}
