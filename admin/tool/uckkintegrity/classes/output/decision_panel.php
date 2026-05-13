<?php
// This file is part of Moodle - http://moodle.org/

namespace tool_uckkintegrity\output;

defined('MOODLE_INTERNAL') || die();

use renderable;
use renderer_base;
use templatable;

/**
 * Renderable decision panel for an integrity case.
 */
class decision_panel implements renderable, templatable {
    /**
     * @var \stdClass Integrity case record.
     */
    private \stdClass $case;

    /**
     * Constructor.
     *
     * @param \stdClass $case Integrity case record.
     */
    public function __construct(\stdClass $case) {
        $this->case = $case;
    }

    /**
     * Export data for Mustache.
     *
     * @param renderer_base $output
     * @return \stdClass
     */
    public function export_for_template(renderer_base $output): \stdClass {
        return (object) [
            'id' => (int) $this->case->id,
            'status' => get_string('status:' . $this->case->status, 'tool_uckkintegrity'),
            'decision' => !empty($this->case->decision)
                ? format_text($this->case->decision, FORMAT_PLAIN)
                : '',
            'correction' => !empty($this->case->correction)
                ? format_text($this->case->correction, FORMAT_PLAIN)
                : '',
            'appealpath' => !empty($this->case->appealpath)
                ? format_text($this->case->appealpath, FORMAT_PLAIN)
                : '',
            'archivesummary' => !empty($this->case->archivesummary)
                ? format_text($this->case->archivesummary, FORMAT_PLAIN)
                : '',
            'archiveitemid' => !empty($this->case->archiveitemid)
                ? (int) $this->case->archiveitemid
                : 0,
            'hasdecision' => !empty($this->case->decision),
            'hascorrection' => !empty($this->case->correction),
            'hasappealpath' => !empty($this->case->appealpath),
            'hasarchivesummary' => !empty($this->case->archivesummary),
            'hasarchiveitem' => !empty($this->case->archiveitemid),
        ];
    }
}