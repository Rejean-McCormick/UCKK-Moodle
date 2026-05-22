<?php
// This file is part of Moodle - https://moodle.org/

declare(strict_types=1);

namespace mod_uckkassembly\local;

use context_module;
use moodle_url;
use stdClass;

defined('MOODLE_INTERNAL') || die();

/**
 * View-data service for the UCKK Assembly activity.
 *
 * The service is intentionally read-only. It prepares safe, capability-aware
 * data for view.php and the assembly_view renderable/template.
 *
 * @package    mod_uckkassembly
 */
final class assembly_service {
    /** Maximum rows shown on the activity overview. */
    private const DEFAULT_LIMIT = 20;

    /**
     * Build the view data for an assembly activity page.
     *
     * @param stdClass $assembly Assembly instance record.
     * @param stdClass $cm Course module record.
     * @param stdClass $course Course record.
     * @param context_module $context Module context.
     * @param stdClass $user Viewing user.
     * @param array<string,mixed> $options View options and capability flags.
     * @return array<string,mixed>
     */
    public function get_view_data(
        stdClass $assembly,
        stdClass $cm,
        stdClass $course,
        context_module $context,
        stdClass $user,
        array $options = []
    ): array {
        $assemblyid = (int)$assembly->id;
        $cmid = (int)$cm->id;
        $courseid = (int)$course->id;
        $contextid = (int)$context->id;
        $limit = max(1, (int)($options['limit'] ?? self::DEFAULT_LIMIT));

        $motions = $this->get_motion_rows($assemblyid, $cmid, $limit);
        $decisions = $this->get_decision_rows($assemblyid, $cmid, $limit);
        $minutes = $this->get_minutes_rows($assemblyid, $cmid, $limit);
        $contests = $this->get_contest_rows($assemblyid, $cmid, $limit);

        $counts = [
            'motions' => $this->count_table('uckkassembly_motion', $assemblyid),
            'amendments' => $this->count_table('uckkassembly_amend', $assemblyid),
            'objections' => $this->count_table('uckkassembly_object', $assemblyid),
            'votes' => $this->count_table('uckkassembly_vote', $assemblyid),
            'decisions' => $this->count_table('uckkassembly_decision', $assemblyid),
            'contests' => $this->count_table('uckkassembly_contest', $assemblyid),
        ];

        $status = $this->clean_token($assembly->status ?? 'active');
        $visibility = $this->clean_token($assembly->visibility ?? 'course');
        $assemblytype = $this->clean_token($assembly->assemblytype ?? 'savoirs');

        $data = [
            'uniqid' => 'uckkassembly-view-' . $cmid,
            'id' => $assemblyid,
            'assemblyid' => $assemblyid,
            'cmid' => $cmid,
            'courseid' => $courseid,
            'contextid' => $contextid,
            'viewurl' => (new moodle_url('/mod/uckkassembly/view.php', ['id' => $cmid]))->out(false),

            // Template-level identity fields.
            'name' => format_string((string)($assembly->name ?? '')),
            'title' => format_string((string)($assembly->name ?? '')),
            'intro' => format_module_intro('uckkassembly', $assembly, $cmid, false),
            'hasintro' => trim((string)($assembly->intro ?? '')) !== '',
            'assemblytype' => $assemblytype,
            'assemblytypelabel' => $this->lang_or_fallback('assemblytype:' . str_replace('_', '', $assemblytype), $assemblytype),
            'state' => $status,
            'statelabel' => $this->lang_or_fallback('status:' . str_replace('_', '', $status), $status),
            'stateclass' => 'state-' . str_replace('_', '-', $status),
            'status' => $status,
            'statuslabel' => $this->lang_or_fallback('status:' . str_replace('_', '', $status), $status),
            'statusclass' => 'status-' . str_replace('_', '-', $status),
            'visibility' => $visibility,
            'visibilitylabel' => $this->lang_or_fallback('visibility:' . str_replace('_', '', $visibility), $visibility),
            'purpose' => (string)($assembly->purpose ?? ''),
            'haspurpose' => trim((string)($assembly->purpose ?? '')) !== '',

            // Main lists.
            'summarycounts' => $counts,
            'motions' => $motions,
            'hasmotions' => !empty($motions),
            'motionlist' => $this->build_motion_list_context($motions, $cmid),
            'hasmotionlist' => !empty($motions),
            'decisions' => $decisions,
            'hasdecisions' => !empty($decisions),
            'minutes' => $minutes,
            'hasminutes' => !empty($minutes),
            'contests' => $contests,
            'hascontests' => !empty($contests),
            'archive' => $this->build_archive_context($assembly, $cmid),
            'hasarchive' => false,

            // Side panels.
            'timeline' => $this->build_timeline($assembly),
            'hastimeline' => $this->has_timeline($assembly),
            'canviewrestricted' => has_capability('mod/uckkassembly:viewrestricted', $context, $user),

            // Notices.
            'notices' => [],
            'hasnotices' => false,
            'warnings' => [],
            'haswarnings' => false,
        ];

        return array_merge(
            $data,
            $this->build_action_context($cmid, $context, $user, $options),
            $options
        );
    }

    /**
     * Build action flags and URLs.
     *
     * @param int $cmid Course module id.
     * @param context_module $context Module context.
     * @param stdClass $user Viewing user.
     * @param array<string,mixed> $options View options.
     * @return array<string,mixed>
     */
    private function build_action_context(int $cmid, context_module $context, stdClass $user, array $options): array {
        $canpropose = (bool)($options['canproposemotion'] ?? has_capability('mod/uckkassembly:proposemotion', $context, $user));
        $canvote = (bool)($options['canvote'] ?? has_capability('mod/uckkassembly:vote', $context, $user));
        $candecision = (bool)($options['canpublishdecision'] ?? has_capability('mod/uckkassembly:publishdecision', $context, $user));
        $cancontest = (bool)($options['cancontestdecision'] ?? has_capability('mod/uckkassembly:contestdecision', $context, $user));
        $canarchive = (bool)($options['canarchive'] ?? has_capability('mod/uckkassembly:archive', $context, $user));
        $canamend = (bool)($options['canamendmotion'] ?? has_capability('mod/uckkassembly:amendmotion', $context, $user));
        $cancreate = (bool)($options['cancreateassembly'] ?? has_capability('mod/uckkassembly:createassembly', $context, $user));

        return [
            'canproposemotion' => $canpropose,
            'canamendmotion' => $canamend,
            'canvote' => $canvote,
            'canpublishdecision' => $candecision,
            'cancontestdecision' => $cancontest,
            'canarchive' => $canarchive,
            'cancreateassembly' => $cancreate,
            'motionurl' => (new moodle_url('/mod/uckkassembly/propose.php', ['id' => $cmid]))->out(false),
            'voteurl' => (new moodle_url('/mod/uckkassembly/vote.php', ['id' => $cmid]))->out(false),
            'decisionurl' => (new moodle_url('/mod/uckkassembly/decision.php', ['id' => $cmid]))->out(false),
            'contesturl' => (new moodle_url('/mod/uckkassembly/contest.php', ['id' => $cmid]))->out(false),
            'minutesurl' => (new moodle_url('/mod/uckkassembly/minutes.php', ['id' => $cmid]))->out(false),
            'archiveurl' => (new moodle_url('/mod/uckkassembly/archive.php', ['id' => $cmid]))->out(false),
        ];
    }

    /**
     * Return motion rows.
     *
     * @param int $assemblyid Assembly id.
     * @param int $cmid Course module id.
     * @param int $limit Maximum rows.
     * @return array<int,array<string,mixed>>
     */
    private function get_motion_rows(int $assemblyid, int $cmid, int $limit): array {
        $records = $this->get_records('uckkassembly_motion', $assemblyid, 'sortorder ASC, timecreated DESC', $limit);
        $rows = [];

        foreach ($records as $record) {
            $status = $this->clean_token($record->status ?? 'draft');
            $decisiontype = $this->clean_token($record->decisiontype ?? 'recommendation');
            $rows[] = [
                'id' => (int)$record->id,
                'title' => format_string((string)($record->title ?? '')),
                'summary' => format_text((string)($record->summary ?? ''), FORMAT_HTML),
                'hassummary' => trim((string)($record->summary ?? '')) !== '',
                'status' => $status,
                'statuslabel' => $this->lang_or_fallback('motionstatus:' . str_replace('_', '', $status), $status),
                'statusclass' => 'motion-status-' . str_replace('_', '-', $status),
                'decisiontype' => $decisiontype,
                'decisiontypelabel' => $this->lang_or_fallback('decisiontype:' . str_replace('_', '', $decisiontype), $decisiontype),
                'url' => (new moodle_url('/mod/uckkassembly/propose.php', ['id' => $cmid, 'motionid' => (int)$record->id]))->out(false),
                'hasurl' => true,
                'timecreated' => (int)($record->timecreated ?? 0),
                'timecreatedlabel' => !empty($record->timecreated) ? userdate((int)$record->timecreated) : '',
                'hastimecreated' => !empty($record->timecreated),
                'timemodified' => (int)($record->timemodified ?? 0),
                'timemodifiedlabel' => !empty($record->timemodified) ? userdate((int)$record->timemodified) : '',
                'hastimemodified' => !empty($record->timemodified),
            ];
        }

        return $rows;
    }

    /**
     * Return decision rows.
     *
     * @param int $assemblyid Assembly id.
     * @param int $cmid Course module id.
     * @param int $limit Maximum rows.
     * @return array<int,array<string,mixed>>
     */
    private function get_decision_rows(int $assemblyid, int $cmid, int $limit): array {
        $records = $this->get_records('uckkassembly_decision', $assemblyid, 'timecreated DESC', $limit);
        $rows = [];

        foreach ($records as $record) {
            $status = $this->clean_token($record->status ?? $record->resultstatus ?? 'pending_review');
            $decisiontype = $this->clean_token($record->decisiontype ?? 'recommendation');
            $rationale = (string)($record->decisiontext ?? '');
            $rows[] = [
                'id' => (int)$record->id,
                'title' => format_string((string)($record->title ?? '')),
                'summary' => format_text((string)($record->summary ?? ''), FORMAT_HTML),
                'hassummary' => trim((string)($record->summary ?? '')) !== '',
                'rationale' => format_text($rationale, FORMAT_HTML),
                'hasrationale' => trim($rationale) !== '',
                'minorityreport' => '',
                'hasminorityreport' => false,
                'status' => $status,
                'statuslabel' => $this->lang_or_fallback('decisionstatus:' . str_replace('_', '', $status), $status),
                'decisiontype' => $decisiontype,
                'decisiontypelabel' => $this->lang_or_fallback('decisiontype:' . str_replace('_', '', $decisiontype), $decisiontype),
                'url' => (new moodle_url('/mod/uckkassembly/decision.php', ['id' => $cmid, 'decisionid' => (int)$record->id]))->out(false),
                'hasurl' => true,
            ];
        }

        return $rows;
    }

    /**
     * Return minutes rows.
     *
     * @param int $assemblyid Assembly id.
     * @param int $cmid Course module id.
     * @param int $limit Maximum rows.
     * @return array<int,array<string,mixed>>
     */
    private function get_minutes_rows(int $assemblyid, int $cmid, int $limit): array {
        $records = $this->get_records('uckkassembly_minutes', $assemblyid, 'timecreated DESC', $limit);
        $rows = [];

        foreach ($records as $record) {
            $status = $this->clean_token($record->status ?? 'draft');
            $summary = (string)($record->summary ?? '');
            $rows[] = [
                'id' => (int)$record->id,
                'title' => format_string((string)($record->title ?? '')),
                'summary' => format_text($summary, FORMAT_HTML),
                'hassummary' => trim($summary) !== '',
                'status' => $status,
                'statuslabel' => $this->lang_or_fallback('minutesstatus:' . str_replace('_', '', $status), $status),
                'files' => [],
                'hasfiles' => false,
                'url' => (new moodle_url('/mod/uckkassembly/minutes.php', ['id' => $cmid, 'minutesid' => (int)$record->id]))->out(false),
            ];
        }

        return $rows;
    }

    /**
     * Return contest rows.
     *
     * @param int $assemblyid Assembly id.
     * @param int $cmid Course module id.
     * @param int $limit Maximum rows.
     * @return array<int,array<string,mixed>>
     */
    private function get_contest_rows(int $assemblyid, int $cmid, int $limit): array {
        $records = $this->get_records('uckkassembly_contest', $assemblyid, 'timecreated DESC', $limit);
        $rows = [];

        foreach ($records as $record) {
            $status = $this->clean_token($record->status ?? 'opened');
            $title = trim((string)($record->summary ?? ''));
            $summary = trim((string)($record->rationale ?? ''));
            $rows[] = [
                'id' => (int)$record->id,
                'title' => format_string($title !== '' ? $title : $this->lang_or_fallback('contest', 'Contest')),
                'summary' => format_text($summary, FORMAT_HTML),
                'hassummary' => $summary !== '',
                'status' => $status,
                'statuslabel' => $this->lang_or_fallback('conteststatus:' . str_replace('_', '', $status), $status),
                'url' => (new moodle_url('/mod/uckkassembly/contest.php', ['id' => $cmid, 'contestid' => (int)$record->id]))->out(false),
            ];
        }

        return $rows;
    }

    /**
     * Build context for the motion_list partial.
     *
     * @param array<int,array<string,mixed>> $motions Motion rows.
     * @param int $cmid Course module id.
     * @return array<string,mixed>
     */
    private function build_motion_list_context(array $motions, int $cmid): array {
        return [
            'motions' => $motions,
            'hasmotions' => !empty($motions),
            'cmid' => $cmid,
            'emptylabel' => $this->lang_or_fallback('motions:none', 'No motions'),
        ];
    }

    /**
     * Build an archive panel placeholder.
     *
     * @param stdClass $assembly Assembly record.
     * @param int $cmid Course module id.
     * @return array<string,mixed>
     */
    private function build_archive_context(stdClass $assembly, int $cmid): array {
        $status = $this->clean_token($assembly->status ?? 'active');

        return [
            'status' => $status,
            'statuslabel' => $this->lang_or_fallback('status:' . str_replace('_', '', $status), $status),
            'items' => [],
            'hasitems' => false,
            'url' => (new moodle_url('/mod/uckkassembly/archive.php', ['id' => $cmid]))->out(false),
        ];
    }

    /**
     * Build timeline data from the assembly instance.
     *
     * @param stdClass $assembly Assembly record.
     * @return array<string,mixed>
     */
    private function build_timeline(stdClass $assembly): array {
        $timescheduled = (int)($assembly->timescheduled ?? 0);
        $timeopen = (int)($assembly->timeopen ?? 0);
        $timeclose = (int)($assembly->timeclose ?? 0);
        $contestuntil = (int)($assembly->contestuntil ?? 0);

        return [
            'timescheduled' => $timescheduled,
            'hasscheduled' => $timescheduled > 0,
            'scheduledlabel' => $timescheduled > 0 ? userdate($timescheduled) : '',
            'timeopen' => $timeopen,
            'hasopen' => $timeopen > 0,
            'openlabel' => $timeopen > 0 ? userdate($timeopen) : '',
            'timeclose' => $timeclose,
            'hasclose' => $timeclose > 0,
            'closelabel' => $timeclose > 0 ? userdate($timeclose) : '',
            'contestuntil' => $contestuntil,
            'hascontestuntil' => $contestuntil > 0,
            'contestuntillabel' => $contestuntil > 0 ? userdate($contestuntil) : '',
        ];
    }

    /**
     * Whether the assembly has timeline data.
     *
     * @param stdClass $assembly Assembly record.
     * @return bool
     */
    private function has_timeline(stdClass $assembly): bool {
        return !empty($assembly->timescheduled)
            || !empty($assembly->timeopen)
            || !empty($assembly->timeclose)
            || !empty($assembly->contestuntil);
    }

    /**
     * Count records in a table for one assembly.
     *
     * @param string $table Table name.
     * @param int $assemblyid Assembly id.
     * @return int
     */
    private function count_table(string $table, int $assemblyid): int {
        global $DB;

        if (!$this->table_exists($table) || !$this->field_exists($table, 'assemblyid')) {
            return 0;
        }

        return (int)$DB->count_records($table, ['assemblyid' => $assemblyid]);
    }

    /**
     * Fetch records by assemblyid when the table is present.
     *
     * @param string $table Table name.
     * @param int $assemblyid Assembly id.
     * @param string $sort Sort clause.
     * @param int $limit Maximum rows.
     * @return array<int,stdClass>
     */
    private function get_records(string $table, int $assemblyid, string $sort, int $limit): array {
        global $DB;

        if (!$this->table_exists($table) || !$this->field_exists($table, 'assemblyid')) {
            return [];
        }

        return array_values($DB->get_records($table, ['assemblyid' => $assemblyid], $sort, '*', 0, $limit));
    }

    /**
     * Check whether a table exists.
     *
     * @param string $table Table name.
     * @return bool
     */
    private function table_exists(string $table): bool {
        global $DB;

        return $DB->get_manager()->table_exists($table);
    }

    /**
     * Check whether a field exists.
     *
     * @param string $table Table name.
     * @param string $field Field name.
     * @return bool
     */
    private function field_exists(string $table, string $field): bool {
        global $DB;

        $dbman = $DB->get_manager();

        if (!$dbman->table_exists($table)) {
            return false;
        }

        return $dbman->field_exists($table, $field);
    }

    /**
     * Return a safe token.
     *
     * @param mixed $value Raw value.
     * @return string
     */
    private function clean_token(mixed $value): string {
        return clean_param((string)$value, PARAM_ALPHANUMEXT);
    }

    /**
     * Return a language string when present, otherwise a readable fallback.
     *
     * @param string $key String key.
     * @param string $fallback Fallback value.
     * @return string
     */
    private function lang_or_fallback(string $key, string $fallback): string {
        $manager = get_string_manager();

        if ($manager->string_exists($key, 'uckkassembly')) {
            return get_string($key, 'uckkassembly');
        }

        return ucfirst(str_replace('_', ' ', $fallback));
    }
}
