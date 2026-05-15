<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

declare(strict_types=1);


namespace mod_uckkchallenge\output;

use renderable;
use renderer_base;
use stdClass;
use templatable;
use mod_uckkchallenge\local\archive_service;
use mod_uckkchallenge\local\integrity_service;
use mod_uckkchallenge\local\submission_service;

defined('MOODLE_INTERNAL') || die();

/**
 * Renderable challenge view page.
 *
 * @package    mod_uckkchallenge
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class view_page implements renderable, templatable {
    private stdClass $course;
    private stdClass $cm;
    private \context_module $context;
    private stdClass $challenge;
    private array $abilities;

    public function __construct(stdClass $course, stdClass $cm, \context_module $context, stdClass $challenge, array $abilities = []) {
        $this->course = $course;
        $this->cm = $cm;
        $this->context = $context;
        $this->challenge = $challenge;
        $this->abilities = $abilities;
    }

    public function export_for_template(renderer_base $output): array {
        global $USER;

        $submissionservice = new submission_service();
        $integrityservice = new integrity_service();
        $archiveservice = new archive_service();

        $data = $submissionservice->get_challenge_payload(
            $this->challenge,
            $this->cm,
            $this->course,
            $this->context,
            $USER,
            $this->abilities
        );

        $integrity = $integrityservice->get_integrity_summary(
            $this->challenge,
            $this->cm,
            $this->course,
            $this->context,
            $USER
        );
        $preview = $archiveservice->get_archive_preview(
            $this->challenge,
            $this->cm,
            $this->course,
            $this->context,
            $USER
        );

        $data['integritypanel'] = [(array)$integrity];
        $data['hasintegritypanel'] = true;
        $data['archive'] = [
            'statuslabel' => in_array((string)($this->challenge->status ?? ''), ['archived', 'closed'], true)
                ? get_string('challengearchived', 'uckkchallenge')
                : get_string('archiveoutput', 'uckkchallenge'),
            'items' => !empty($preview->items) ? $preview->items : [],
            'hasitems' => !empty($preview->items),
            'actionurl' => !empty($this->abilities['canarchive'])
                ? (new \moodle_url('/mod/uckkchallenge/archive.php', ['id' => $this->cm->id]))->out(false)
                : '',
            'actionlabel' => get_string('archivechallenge', 'uckkchallenge'),
        ];
        $data['hasarchive'] = true;

        return $data;
    }
}
