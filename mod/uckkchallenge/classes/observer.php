<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

declare(strict_types=1);


namespace mod_uckkchallenge;

use context_module;
use core\event\base as base_event;
use stdClass;
use xmldb_table;

defined('MOODLE_INTERNAL') || die();

/**
 * Event observers for mod_uckkchallenge.
 *
 * @package    mod_uckkchallenge
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class observer {
    public static function challenge_viewed(base_event $event): void {
        self::record_event_state($event, 'view', '');
    }

    public static function challenge_created(base_event $event): void {
        self::record_event_state($event, 'create', 'draft');
    }

    public static function challenge_updated(base_event $event): void {
        self::record_event_state($event, 'update', '');
    }

    public static function challenge_published(base_event $event): void {
        self::record_event_state($event, 'publish', 'published');
    }

    public static function submission_created(base_event $event): void {
        self::record_event_state($event, 'submission_create', 'draft');
    }

    public static function submission_updated(base_event $event): void {
        self::record_event_state($event, 'submission_update', '');
    }

    public static function submission_submitted(base_event $event): void {
        self::record_event_state($event, 'submission_submit', 'submitted');
    }

    public static function evaluation_created(base_event $event): void {
        self::record_event_state($event, 'evaluation_create', 'under_review');
    }

    public static function evaluation_updated(base_event $event): void {
        self::record_event_state($event, 'evaluation_update', 'under_review');
    }

    public static function integrity_review_started(base_event $event): void {
        self::record_event_state($event, 'integrity_review', 'integrity_review');
    }

    public static function correction_required(base_event $event): void {
        self::record_event_state($event, 'correction_required', 'revision_required');
    }

    public static function challenge_contested(base_event $event): void {
        self::record_event_state($event, 'contest', 'contested');
    }

    public static function challenge_invalidated(base_event $event): void {
        self::record_event_state($event, 'invalidate', 'invalidated');
    }

    public static function challenge_validated(base_event $event): void {
        self::record_event_state($event, 'validate', 'validated');
    }

    public static function challenge_archived(base_event $event): void {
        self::record_event_state($event, 'archive', 'archived');
    }

    public static function challenge_closed(base_event $event): void {
        self::record_event_state($event, 'close', 'closed');
    }

    public static function course_module_deleted(base_event $event): void {
        if (!isset($event->other['modulename']) || $event->other['modulename'] !== 'uckkchallenge') {
            return;
        }
        self::record_event_state($event, 'course_module_deleted', 'deleted');
    }

    private static function record_event_state(base_event $event, string $action, string $tostatus): void {
        global $DB;

        if ($event->contextlevel !== CONTEXT_MODULE) {
            return;
        }
        if (!self::table_exists('uckkchallenge_state')) {
            return;
        }

        $challengeid = 0;
        $submissionid = 0;
        $evaluationid = 0;
        $challenge = self::load_challenge_from_event($event);
        if ($challenge) {
            $challengeid = (int)$challenge->id;
        }
        if ($event instanceof \mod_uckkchallenge\event\submission_created ||
            $event instanceof \mod_uckkchallenge\event\submission_updated ||
            $event instanceof \mod_uckkchallenge\event\submission_submitted) {
            $submissionid = (int)$event->objectid;
        }
        if ($event instanceof \mod_uckkchallenge\event\evaluation_created ||
            $event instanceof \mod_uckkchallenge\event\evaluation_updated) {
            $evaluationid = (int)$event->objectid;
        }
        if (!$challengeid && !empty($event->other['challengeid'])) {
            $challengeid = (int)$event->other['challengeid'];
        }
        if (!$challengeid) {
            return;
        }

        $record = [
            'challengeid' => $challengeid,
            'submissionid' => $submissionid,
            'evaluationid' => $evaluationid,
            'userid' => (int)($event->relateduserid ?: $event->userid),
            'fromstatus' => '',
            'tostatus' => $tostatus,
            'action' => $action,
            'reason' => $event->get_description(),
            'reasonformat' => FORMAT_PLAIN,
            'integritystate' => '',
            'provenancehash' => hash('sha256', $action . ':' . $challengeid . ':' . $event->timecreated),
            'timecreated' => (int)$event->timecreated,
            'createdby' => (int)$event->userid,
            'metadata' => json_encode(['eventname' => $event->eventname], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ];

        $DB->insert_record('uckkchallenge_state', self::filter_record('uckkchallenge_state', $record));
    }

    private static function load_challenge_from_event(base_event $event): ?stdClass {
        global $DB;

        if ($event instanceof \mod_uckkchallenge\event\challenge_created ||
            $event instanceof \mod_uckkchallenge\event\challenge_updated ||
            $event instanceof \mod_uckkchallenge\event\challenge_published ||
            $event instanceof \mod_uckkchallenge\event\challenge_validated ||
            $event instanceof \mod_uckkchallenge\event\challenge_archived ||
            $event instanceof \mod_uckkchallenge\event\challenge_closed ||
            $event instanceof \mod_uckkchallenge\event\challenge_contested ||
            $event instanceof \mod_uckkchallenge\event\challenge_invalidated ||
            $event instanceof \mod_uckkchallenge\event\challenge_viewed ||
            $event instanceof \mod_uckkchallenge\event\course_module_viewed) {
            return $DB->get_record('uckkchallenge', ['id' => (int)$event->objectid]) ?: null;
        }

        if ($event instanceof \mod_uckkchallenge\event\submission_created ||
            $event instanceof \mod_uckkchallenge\event\submission_updated ||
            $event instanceof \mod_uckkchallenge\event\submission_submitted) {
            $submission = $DB->get_record('uckkchallenge_sub', ['id' => (int)$event->objectid]);
            if ($submission) {
                return $DB->get_record('uckkchallenge', ['id' => (int)$submission->challengeid]) ?: null;
            }
        }

        if ($event instanceof \mod_uckkchallenge\event\evaluation_created ||
            $event instanceof \mod_uckkchallenge\event\evaluation_updated) {
            $evaluation = $DB->get_record('uckkchallenge_eval', ['id' => (int)$event->objectid]);
            if ($evaluation) {
                return $DB->get_record('uckkchallenge', ['id' => (int)$evaluation->challengeid]) ?: null;
            }
        }

        return null;
    }

    private static function table_exists(string $table): bool {
        global $DB;
        return $DB->get_manager()->table_exists(new xmldb_table($table));
    }

    private static function filter_record(string $table, array $values): stdClass {
        global $DB;
        $columns = $DB->get_columns($table);
        $record = new stdClass();
        foreach ($values as $key => $value) {
            if (array_key_exists($key, $columns)) {
                $record->{$key} = $value;
            }
        }
        return $record;
    }
}
