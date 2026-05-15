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
     * Integrity workflow service for UCKK challenges.
     *
     * @package    mod_uckkchallenge
     * @copyright  2026 Univers-Cité King Klown
     * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
     */
    class integrity_service {
        public function can_open_case(stdClass $challenge, stdClass $cm, context_module $context, stdClass $user): bool {
            return has_capability('tool/uckkintegrity:opencase', $context);
        }

        public function can_contest(stdClass $challenge, stdClass $cm, context_module $context, stdClass $user): bool {
            return has_capability('mod/uckkchallenge:contest', $context);
        }

        public function can_issue_correction(stdClass $challenge, stdClass $cm, context_module $context, stdClass $user, int $caseid): bool {
            return has_capability('tool/uckkintegrity:issuecorrection', $context);
        }

        public function can_invalidate(stdClass $challenge, stdClass $cm, context_module $context, stdClass $user, int $caseid): bool {
            return has_capability('tool/uckkintegrity:invalidate', $context);
        }

        public function can_close_case(stdClass $challenge, stdClass $cm, context_module $context, stdClass $user, int $caseid): bool {
            return has_capability('tool/uckkintegrity:closecase', $context);
        }

        public function open_case(stdClass $challenge, stdClass $cm, stdClass $course, context_module $context, stdClass $user, array $data): stdClass {
            $case = $this->create_case($challenge, $context, $user, [
                'type' => (string)($data['type'] ?? 'challenge_dispute'),
                'summary' => (string)($data['summary'] ?? ''),
                'notes' => (string)($data['notes'] ?? ''),
                'severity' => (string)($data['severity'] ?? 'medium'),
                'visibility' => (string)($data['visibility'] ?? 'restricted'),
                'status' => 'open',
            ]);

            $this->trigger_event('\mod_uckkchallenge\event\integrity_review_started', $challenge, $context, $challenge, [
                'other' => ['caseid' => (int)$case->id],
            ]);

            return $case;
        }

        public function contest_challenge(stdClass $challenge, stdClass $cm, stdClass $course, context_module $context, stdClass $user, array $data): stdClass {
            global $DB;

            $case = $this->create_case($challenge, $context, $user, [
                'type' => 'challenge_dispute',
                'summary' => (string)($data['summary'] ?? ''),
                'notes' => (string)($data['notes'] ?? ''),
                'severity' => 'medium',
                'visibility' => 'restricted',
                'status' => 'open',
            ]);

            $DB->update_record('uckkchallenge', self::filter_record('uckkchallenge', [
                'id' => (int)$challenge->id,
                'status' => 'contested',
                'timemodified' => time(),
                'modifiedby' => (int)$user->id,
            ]));

            $this->record_state($challenge, $user, 'contest', 'contested', (string)($data['summary'] ?? ''), integrity_state::UNDER_REVIEW, (int)$case->id);
            $this->trigger_event('\mod_uckkchallenge\event\challenge_contested', $challenge, $context, $challenge, [
                'other' => ['caseid' => (int)$case->id],
            ]);

            return $case;
        }

        public function issue_correction(stdClass $challenge, stdClass $cm, stdClass $course, context_module $context, stdClass $user, int $caseid, array $data): void {
            $this->append_case_note($caseid, $user, 'correction', (string)($data['summary'] ?? ''), (string)($data['notes'] ?? ''));
            $this->record_state($challenge, $user, 'correction', 'revision_required', (string)($data['summary'] ?? ''), integrity_state::UNDER_REVIEW, $caseid);
            $this->trigger_event('\mod_uckkchallenge\event\correction_required', $challenge, $context, $challenge, [
                'other' => ['caseid' => $caseid],
            ]);
        }

        public function invalidate_challenge(stdClass $challenge, stdClass $cm, stdClass $course, context_module $context, stdClass $user, int $caseid, array $data): void {
            global $DB;

            $this->append_case_note($caseid, $user, 'decision', (string)($data['summary'] ?? ''), (string)($data['notes'] ?? ''));
            $this->update_case_status($caseid, 'invalidated', (string)($data['summary'] ?? ''));
            $DB->update_record('uckkchallenge', self::filter_record('uckkchallenge', [
                'id' => (int)$challenge->id,
                'status' => 'invalidated',
                'timemodified' => time(),
                'modifiedby' => (int)$user->id,
            ]));
            $this->record_state($challenge, $user, 'invalidate', 'invalidated', (string)($data['summary'] ?? ''), integrity_state::INVALIDATED, $caseid);
            $this->trigger_event('\mod_uckkchallenge\event\challenge_invalidated', $challenge, $context, $challenge, [
                'other' => ['caseid' => $caseid],
            ]);
        }

        public function close_case(stdClass $challenge, stdClass $cm, stdClass $course, context_module $context, stdClass $user, int $caseid, array $data): void {
            $this->append_case_note($caseid, $user, 'closure', (string)($data['summary'] ?? ''), (string)($data['notes'] ?? ''));
            $this->update_case_status($caseid, 'closed', (string)($data['summary'] ?? ''));
        }

        public function validate_integrity(stdClass $challenge, stdClass $cm, stdClass $course, context_module $context, stdClass $user, array $data): stdClass {
            global $DB;

            $caseid = (int)($data['caseid'] ?? 0);
            if ($caseid > 0) {
                $this->append_case_note($caseid, $user, 'decision', (string)($data['summary'] ?? ''), (string)($data['notes'] ?? ''));
                $this->update_case_status($caseid, 'resolved', (string)($data['summary'] ?? ''));
            }

            $DB->update_record('uckkchallenge', self::filter_record('uckkchallenge', [
                'id' => (int)$challenge->id,
                'status' => 'validated',
                'timemodified' => time(),
                'modifiedby' => (int)$user->id,
            ]));

            $this->record_state($challenge, $user, 'validate', 'validated', (string)($data['summary'] ?? ''), integrity_state::HUMAN_REVIEWED, $caseid);
            $updated = $DB->get_record('uckkchallenge', ['id' => (int)$challenge->id], '*', MUST_EXIST);
            $this->trigger_event('\mod_uckkchallenge\event\challenge_validated', $updated, $context, $updated, [
                'other' => ['caseid' => $caseid],
            ]);

            return $updated;
        }

        public function get_integrity_summary(stdClass $challenge, stdClass $cm, stdClass $course, context_module $context, stdClass $user, int $caseid = 0): stdClass {
            global $DB;

            $summary = (object)[
                'uniqid' => 'uckkchallenge-integrity-' . (int)$challenge->id,
                'challengeid' => (int)$challenge->id,
                'cmid' => (int)$cm->id,
                'integritystate' => integrity_state::HUMAN_REVIEWED,
                'integritystatelabel' => integrity_state::label(integrity_state::HUMAN_REVIEWED),
                'integritystateclass' => integrity_state::css_class(integrity_state::HUMAN_REVIEWED),
                'challengestatus' => (string)($challenge->status ?? 'draft'),
                'hasrestricteddata' => false,
                'restrictedlabel' => get_string('restrictedvisibility', 'uckkchallenge'),
                'canviewrestricted' => has_capability('tool/uckkintegrity:view', $context),
                'opencasecount' => 0,
                'warnings' => [],
                'notices' => [[
                    'message' => get_string_manager()->string_exists('nonsovereignnotice', 'aiprovider_uckk')
                        ? get_string('nonsovereignnotice', 'aiprovider_uckk')
                        : 'Integrity actions remain subject to human review.',
                ]],
                'cases' => [],
                'actions' => [
                    'canopen' => has_capability('tool/uckkintegrity:opencase', $context),
                    'openurl' => (new \moodle_url('/mod/uckkchallenge/integrity.php', ['id' => $cm->id]))->out(false),
                    'cancontest' => has_capability('mod/uckkchallenge:contest', $context),
                    'contesturl' => (new \moodle_url('/mod/uckkchallenge/integrity.php', ['id' => $cm->id]))->out(false),
                    'canreview' => has_capability('tool/uckkintegrity:view', $context),
                    'reviewurl' => (new \moodle_url('/mod/uckkchallenge/integrity.php', ['id' => $cm->id]))->out(false),
                ],
            ];

            if (self::table_exists('tool_uckkintegrity_case')) {
                $cases = $DB->get_records('tool_uckkintegrity_case', [
                    'subjectcomponent' => 'mod_uckkchallenge',
                    'subjectid' => (int)$challenge->id,
                    'contextid' => (int)$context->id,
                ], 'timemodified DESC, id DESC');

                foreach ($cases as $case) {
                    $status = (string)($case->status ?? 'open');
                    $summary->cases[] = [
                        'id' => (int)$case->id,
                        'casetype' => (string)$case->casetype,
                        'casetypelabel' => ucwords(str_replace('_', ' ', (string)$case->casetype)),
                        'status' => $status,
                        'statuslabel' => ucwords(str_replace('_', ' ', $status)),
                        'statusclass' => 'status-' . str_replace('_', '-', $status),
                        'summary' => s((string)($case->summary ?? '')),
                        'timemodifiedlabel' => !empty($case->timemodified) ? userdate((int)$case->timemodified) : '',
                        'hasurl' => true,
                        'url' => (new \moodle_url('/mod/uckkchallenge/integrity.php', ['id' => $cm->id, 'caseid' => $case->id]))->out(false),
                    ];
                    if (!in_array($status, ['closed', 'resolved', 'invalidated'], true)) {
                        $summary->opencasecount++;
                    }
                }
            }

            $summary->hasopencases = $summary->opencasecount > 0;
            $summary->haswarnings = !empty($summary->warnings);
            $summary->hasnotices = !empty($summary->notices);
            $summary->hascases = !empty($summary->cases);
            $summary->hasactions = !empty($summary->actions);
            return $summary;
        }

        private function create_case(stdClass $challenge, context_module $context, stdClass $user, array $data): stdClass {
            global $DB;

            $now = time();
            if (!self::table_exists('tool_uckkintegrity_case')) {
                $record = (object)[
                    'id' => $now,
                    'casetype' => (string)$data['type'],
                    'subjectcomponent' => 'mod_uckkchallenge',
                    'subjectid' => (int)$challenge->id,
                    'contextid' => (int)$context->id,
                    'openedby' => (int)$user->id,
                    'severity' => (string)$data['severity'],
                    'status' => (string)$data['status'],
                    'summary' => (string)$data['summary'],
                    'visibility' => (string)$data['visibility'],
                    'timecreated' => $now,
                    'timemodified' => $now,
                ];
                return $record;
            }

            $metadata = json_encode(['notes' => (string)$data['notes']], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}';
            $id = (int)$DB->insert_record('tool_uckkintegrity_case', self::filter_record('tool_uckkintegrity_case', [
                'casetype' => (string)$data['type'],
                'subjectcomponent' => 'mod_uckkchallenge',
                'subjectid' => (int)$challenge->id,
                'contextid' => (int)$context->id,
                'openedby' => (int)$user->id,
                'assignedto' => 0,
                'severity' => (string)$data['severity'],
                'status' => (string)$data['status'],
                'summary' => (string)$data['summary'],
                'decision' => '',
                'correction' => '',
                'appealpath' => '',
                'archivesummary' => '',
                'archiveitemid' => 0,
                'visibility' => (string)$data['visibility'],
                'versionno' => 1,
                'provenancehash' => hash('sha256', $metadata),
                'metadata' => $metadata,
                'timecreated' => $now,
                'timemodified' => $now,
                'timeclosed' => 0,
            ]));

            if (self::table_exists('tool_uckkintegrity_note') && trim((string)$data['notes']) !== '') {
                $this->append_case_note($id, $user, 'opening_note', (string)$data['summary'], (string)$data['notes']);
            }

            return $DB->get_record('tool_uckkintegrity_case', ['id' => $id], '*', MUST_EXIST);
        }

        private function append_case_note(int $caseid, stdClass $user, string $type, string $summary, string $notes): void {
            global $DB;
            if (!self::table_exists('tool_uckkintegrity_note')) {
                return;
            }
            $body = trim($summary . "

" . $notes);
            $now = time();
            $DB->insert_record('tool_uckkintegrity_note', self::filter_record('tool_uckkintegrity_note', [
                'caseid' => $caseid,
                'userid' => (int)$user->id,
                'notetype' => $type,
                'body' => $body,
                'visibility' => 'restricted',
                'metadata' => json_encode(['source' => 'mod_uckkchallenge'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'timecreated' => $now,
                'timemodified' => $now,
            ]));
        }

        private function update_case_status(int $caseid, string $status, string $decision): void {
            global $DB;
            if (!self::table_exists('tool_uckkintegrity_case')) {
                return;
            }
            $DB->update_record('tool_uckkintegrity_case', self::filter_record('tool_uckkintegrity_case', [
                'id' => $caseid,
                'status' => $status,
                'decision' => $decision,
                'timemodified' => time(),
                'timeclosed' => in_array($status, ['closed', 'resolved', 'invalidated'], true) ? time() : 0,
            ]));
        }

        private function record_state(stdClass $challenge, stdClass $user, string $action, string $tostatus, string $reason, string $integritystate, int $caseid = 0): void {
            global $DB;
            if (!self::table_exists('uckkchallenge_state')) {
                return;
            }
            $now = time();
            $DB->insert_record('uckkchallenge_state', self::filter_record('uckkchallenge_state', [
                'challengeid' => (int)$challenge->id,
                'submissionid' => 0,
                'evaluationid' => 0,
                'userid' => (int)$user->id,
                'fromstatus' => (string)($challenge->status ?? ''),
                'tostatus' => $tostatus,
                'action' => $action,
                'reason' => $reason,
                'reasonformat' => FORMAT_PLAIN,
                'integritystate' => $integritystate,
                'provenancehash' => hash('sha256', $action . ':' . $reason . ':' . $now),
                'timecreated' => $now,
                'createdby' => (int)$user->id,
                'metadata' => json_encode(['caseid' => $caseid], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ]));
        }

        private function trigger_event(string $eventclass, stdClass $challenge, context_module $context, stdClass $record, array $options = []): void {
            if (!class_exists($eventclass)) {
                return;
            }
            $event = $eventclass::create([
                'objectid' => (int)$record->id,
                'context' => $context,
                'other' => (array)($options['other'] ?? []),
            ]);
            $event->add_record_snapshot('uckkchallenge', $challenge);
            $event->trigger();
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
