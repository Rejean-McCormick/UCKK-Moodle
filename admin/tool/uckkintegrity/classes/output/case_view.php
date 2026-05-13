<?php
// This file is part of Moodle - http://moodle.org/

namespace tool_uckkintegrity\output;

defined('MOODLE_INTERNAL') || die();

use renderable;
use renderer_base;
use templatable;
use tool_uckkintegrity\local\integrity_case;

class case_view implements renderable, templatable {
    private \stdClass $case;

    public function __construct(\stdClass $case) {
        $this->case = $case;
    }

    public function export_for_template(renderer_base $output): \stdClass {
        global $DB;

        $case = $this->case;
        $notes = [];

        foreach (integrity_case::notes($case->id) as $note) {
            $user = $DB->get_record('user', ['id' => $note->userid], '*', IGNORE_MISSING);

            $notes[] = [
                'notetype' => get_string('note:' . $note->notetype, 'tool_uckkintegrity'),
                'body' => format_text($note->body, FORMAT_PLAIN),
                'visibility' => $note->visibility,
                'timecreated' => userdate($note->timecreated),
                'user' => $user ? fullname($user) : get_string('unknownuser', 'tool_uckkintegrity'),
            ];
        }

        return (object)[
            'id' => $case->id,
            'casetype' => get_string('type:' . $case->casetype, 'tool_uckkintegrity'),
            'subjectcomponent' => s($case->subjectcomponent),
            'subjectid' => $case->subjectid,
            'severity' => get_string('severity:' . $case->severity, 'tool_uckkintegrity'),
            'status' => get_string('status:' . $case->status, 'tool_uckkintegrity'),
            'summary' => format_text($case->summary, FORMAT_PLAIN),
            'decision' => !empty($case->decision) ? format_text($case->decision, FORMAT_PLAIN) : '',
            'correction' => !empty($case->correction) ? format_text($case->correction, FORMAT_PLAIN) : '',
            'appealpath' => !empty($case->appealpath) ? format_text($case->appealpath, FORMAT_PLAIN) : '',
            'archivesummary' => !empty($case->archivesummary) ? format_text($case->archivesummary, FORMAT_PLAIN) : '',
            'timecreated' => userdate($case->timecreated),
            'timemodified' => userdate($case->timemodified),
            'notes' => $notes,
            'hasnotes' => !empty($notes),
            'reviewurl' => (new \moodle_url('/admin/tool/uckkintegrity/review.php', ['id' => $case->id]))->out(false),
            'decisionurl' => (new \moodle_url('/admin/tool/uckkintegrity/decision.php', ['id' => $case->id]))->out(false),
            'appealurl' => (new \moodle_url('/admin/tool/uckkintegrity/appeal.php', ['id' => $case->id]))->out(false),
        ];
    }
}