<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

declare(strict_types=1);


namespace mod_uckkchallenge\local;

use context_module;
use stdClass;
use xmldb_table;

defined('MOODLE_INTERNAL') || die();

/**
 * Archive handoff service for UCKK challenges.
 *
 * @package    mod_uckkchallenge
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class archive_service {
    public function can_archive(stdClass $challenge, stdClass $cm, context_module $context, stdClass $user): bool {
        return has_capability('mod/uckkchallenge:archive', $context)
            && in_array((string)($challenge->status ?? 'draft'), ['validated', 'archived', 'closed'], true);
    }

    public function get_archive_preview(stdClass $challenge, stdClass $cm, stdClass $course, context_module $context, stdClass $user): stdClass {
        global $DB;

        $preview = (object)[
            'challengeid' => (int)$challenge->id,
            'submissioncount' => self::table_exists('uckkchallenge_sub') ? $DB->count_records('uckkchallenge_sub', ['challengeid' => (int)$challenge->id]) : 0,
            'proofcount' => self::table_exists('uckkchallenge_proof') ? $DB->count_records('uckkchallenge_proof', ['challengeid' => (int)$challenge->id]) : 0,
            'evaluationcount' => self::table_exists('uckkchallenge_eval') ? $DB->count_records('uckkchallenge_eval', ['challengeid' => (int)$challenge->id]) : 0,
            'integritystate' => integrity_state::label(integrity_state::HUMAN_REVIEWED),
            'warnings' => [],
            'items' => [],
        ];

        if ((string)($challenge->status ?? '') !== 'validated') {
            $preview->warnings[] = get_string('cannotarchivechallenge', 'uckkchallenge');
        }

        return $preview;
    }

    public function archive_challenge(stdClass $challenge, stdClass $cm, stdClass $course, context_module $context, stdClass $user, array $data): stdClass {
        global $DB;

        $now = time();
        $visibility = (string)($data['visibility'] ?? 'course');
        $reason = (string)($data['reason'] ?? '');
        $archiveinstance = self::table_exists('uckkarchive') ? $DB->get_record('uckkarchive', ['course' => (int)$course->id], '*', IGNORE_MULTIPLE) : false;
        $archivecm = null;
        $item = (object)[
            'id' => 0,
            'title' => format_string((string)$challenge->name, true, ['context' => $context]),
            'status' => 'archived',
            'statuslabel' => get_string('challengearchived', 'uckkchallenge'),
            'url' => null,
        ];

        if ($archiveinstance && self::table_exists('uckkarchive_item')) {
            $archivecm = get_coursemodule_from_instance('uckkarchive', (int)$archiveinstance->id, (int)$course->id, false, IGNORE_MISSING);
            $content = json_encode([
                'challengeid' => (int)$challenge->id,
                'challengecode' => (string)($challenge->challengecode ?? ''),
                'statement' => (string)($challenge->statement ?? ''),
                'criteria' => (string)($challenge->criteria ?? ''),
                'publicsummary' => (string)($challenge->publicsummary ?? ''),
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $itemid = (int)$DB->insert_record('uckkarchive_item', self::filter_record('uckkarchive_item', [
                'archiveid' => (int)$archiveinstance->id,
                'courseid' => (int)$course->id,
                'cmid' => !empty($archivecm->id) ? (int)$archivecm->id : 0,
                'contextid' => !empty($archivecm->id) ? (int)context_module::instance((int)$archivecm->id)->id : (int)$context->id,
                'userid' => (int)$user->id,
                'parentitemid' => 0,
                'itemtype' => 'challenge_result',
                'title' => format_string((string)$challenge->name, true, ['context' => $context]),
                'summary' => (string)($challenge->publicsummary ?? $challenge->intro ?? ''),
                'content' => $content,
                'contentformat' => FORMAT_HTML,
                'publicsummary' => (string)($challenge->publicsummary ?? ''),
                'sourcecomponent' => 'mod_uckkchallenge',
                'sourceobjectid' => (int)$challenge->id,
                'origincomponent' => 'mod_uckkchallenge',
                'originobjectid' => (int)$challenge->id,
                'license' => 'GPL-3.0-or-later',
                'tags' => 'uckkchallenge,archive',
                'status' => 'submitted',
                'visibility' => $visibility,
                'validationstate' => 'human_reviewed',
                'provenance' => 'challenge',
                'provenancehash' => hash('sha256', (string)$challenge->id . ':' . $now . ':' . $reason),
                'timecreated' => $now,
                'timemodified' => $now,
                'createdby' => (int)$user->id,
                'modifiedby' => (int)$user->id,
                'metadata' => json_encode(['reason' => $reason], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ]));
            $item = (object)[
                'id' => $itemid,
                'title' => format_string((string)$challenge->name, true, ['context' => $context]),
                'status' => 'archived',
                'statuslabel' => get_string('challengearchived', 'uckkchallenge'),
                'url' => !empty($archivecm->id)
                    ? (new \moodle_url('/mod/uckkarchive/item.php', ['id' => (int)$archivecm->id, 'itemid' => $itemid]))->out(false)
                    : null,
            ];
        }

        $DB->update_record('uckkchallenge', self::filter_record('uckkchallenge', [
            'id' => (int)$challenge->id,
            'status' => 'archived',
            'timemodified' => $now,
            'modifiedby' => (int)$user->id,
        ]));

        if (self::table_exists('uckkchallenge_state')) {
            $DB->insert_record('uckkchallenge_state', self::filter_record('uckkchallenge_state', [
                'challengeid' => (int)$challenge->id,
                'submissionid' => 0,
                'evaluationid' => 0,
                'userid' => (int)$user->id,
                'fromstatus' => (string)($challenge->status ?? ''),
                'tostatus' => 'archived',
                'action' => 'archive',
                'reason' => $reason,
                'reasonformat' => FORMAT_PLAIN,
                'integritystate' => integrity_state::HUMAN_REVIEWED,
                'provenancehash' => hash('sha256', (string)$challenge->id . ':' . $reason . ':' . $now),
                'timecreated' => $now,
                'createdby' => (int)$user->id,
                'metadata' => json_encode(['archiveitemid' => (int)$item->id], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ]));
        }

        if (class_exists('\mod_uckkchallenge\event\challenge_archived')) {
            $event = \mod_uckkchallenge\event\challenge_archived::create([
                'objectid' => (int)$challenge->id,
                'context' => $context,
                'other' => ['archiveitemid' => (int)$item->id],
            ]);
            $event->add_record_snapshot('uckkchallenge', $challenge);
            $event->trigger();
        }

        return $item;
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
