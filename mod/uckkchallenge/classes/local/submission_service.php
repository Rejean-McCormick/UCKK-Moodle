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
 * Submission service for UCKK challenges.
 *
 * @package    mod_uckkchallenge
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class submission_service {
    /**
     * Resolve activity records from a course module id.
     *
     * @param int $cmid Course module id.
     * @return array{0:stdClass,1:stdClass,2:context_module,3:stdClass}
     */
    public static function resolve_activity(int $cmid): array {
        global $DB;

        $cm = get_coursemodule_from_id('uckkchallenge', $cmid, 0, false, MUST_EXIST);
        $course = get_course($cm->course);
        $context = context_module::instance($cm->id);
        $challenge = $DB->get_record('uckkchallenge', ['id' => $cm->instance], '*', MUST_EXIST);

        return [$course, $cm, $context, $challenge];
    }

    /**
     * Get the current user's latest submission.
     */
    public function get_current_user_submission(stdClass $challenge, stdClass $cm, context_module $context, stdClass $user): ?stdClass {
        global $DB;

        $sql = 'challengeid = :challengeid AND userid = :userid';
        return $DB->get_record_select('uckkchallenge_sub', $sql, [
            'challengeid' => (int)$challenge->id,
            'userid' => (int)$user->id,
        ], '*', IGNORE_MULTIPLE) ?: null;
    }

    /**
     * Get a submission by id.
     */
    public function get_submission_by_id(stdClass $challenge, int $submissionid): ?stdClass {
        global $DB;

        return $DB->get_record('uckkchallenge_sub', [
            'id' => $submissionid,
            'challengeid' => (int)$challenge->id,
        ]) ?: null;
    }

    /**
     * Save a submission draft or update an existing one.
     */
    public function save_submission(stdClass $challenge, stdClass $cm, stdClass $course, context_module $context, stdClass $user, array $data): stdClass {
        global $DB;

        require_once(__DIR__ . '/../../locallib.php');

        $now = time();
        $submissionid = (int)($data['submissionid'] ?? 0);
        $status = function_exists('uckkchallenge_normalise_status')
            ? uckkchallenge_normalise_status((string)($data['status'] ?? 'draft'))
            : (string)($data['status'] ?? 'draft');
        $visibility = function_exists('uckkchallenge_normalise_visibility')
            ? uckkchallenge_normalise_visibility((string)($data['visibility'] ?? 'course'))
            : (string)($data['visibility'] ?? 'course');

        $metadata = [
            'aiassisted' => !empty($data['aiassisted']),
            'ailog' => (string)($data['ailog'] ?? ''),
            'uncertaintynotes' => (string)($data['uncertaintynotes'] ?? ''),
            'savedfrom' => 'submission_service',
        ];
        $metadatajson = json_encode($metadata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}';
        $provenancehash = function_exists('uckkchallenge_build_provenance_hash')
            ? uckkchallenge_build_provenance_hash($metadatajson)
            : hash('sha256', $metadatajson);

        $record = [
            'challengeid' => (int)$challenge->id,
            'courseid' => (int)$course->id,
            'cmid' => (int)$cm->id,
            'contextid' => (int)$context->id,
            'userid' => (int)$user->id,
            'groupid' => 0,
            'attemptno' => 1,
            'prooftype' => (string)($data['prooftype'] ?? 'text'),
            'submissionurl' => (string)($data['submissionurl'] ?? ''),
            'relationtocriteria' => (string)($data['relationtocriteria'] ?? ''),
            'provenancestatement' => (string)($data['provenancestatement'] ?? ''),
            'sourceauthor' => (string)($data['sourceauthor'] ?? ''),
            'sourcedate' => (int)($data['sourcedate'] ?? 0),
            'visibility' => $visibility,
            'integritystate' => integrity_state::UNVERIFIED,
            'aiassisted' => !empty($data['aiassisted']) ? 1 : 0,
            'ailog' => (string)($data['ailog'] ?? ''),
            'uncertaintynotes' => (string)($data['uncertaintynotes'] ?? ''),
            'status' => $status,
            'timemodified' => $now,
            'modifiedby' => (int)$user->id,
            'versionno' => 1,
            'provenancehash' => $provenancehash,
            'metadata' => $metadatajson,
        ];

        $existing = null;
        if ($submissionid > 0) {
            $existing = $this->get_submission_by_id($challenge, $submissionid);
        }

        if ($existing) {
            $record['id'] = (int)$existing->id;
            $DB->update_record('uckkchallenge_sub', self::filter_record('uckkchallenge_sub', $record));
            $submission = $DB->get_record('uckkchallenge_sub', ['id' => (int)$existing->id], '*', MUST_EXIST);
            $this->trigger_event('\mod_uckkchallenge\event\submission_updated', $challenge, $context, $submission, [
                'relateduserid' => (int)$submission->userid,
            ]);
        } else {
            $record['timecreated'] = $now;
            $record['createdby'] = (int)$user->id;
            $submissionid = (int)$DB->insert_record('uckkchallenge_sub', self::filter_record('uckkchallenge_sub', $record));
            $submission = $DB->get_record('uckkchallenge_sub', ['id' => $submissionid], '*', MUST_EXIST);
            $this->trigger_event('\mod_uckkchallenge\event\submission_created', $challenge, $context, $submission, [
                'relateduserid' => (int)$submission->userid,
            ]);
        }

        return $submission;
    }

    /**
     * Update the formatted submission text after editor processing.
     */
    public function update_submission_text(stdClass $submission, string $text, int $format, stdClass $user): void {
        global $DB;

        $DB->update_record('uckkchallenge_sub', self::filter_record('uckkchallenge_sub', [
            'id' => (int)$submission->id,
            'submissiontext' => $text,
            'submissiontextformat' => $format,
            'timemodified' => time(),
            'modifiedby' => (int)$user->id,
        ]));
    }

    /**
     * Transition a submission to submitted/under review.
     */
    public function submit_for_review(stdClass $submission, stdClass $challenge, stdClass $cm, stdClass $course, context_module $context, stdClass $user): stdClass {
        global $DB;

        require_once(__DIR__ . '/../../locallib.php');

        $now = time();
        $fromstatus = (string)($submission->status ?? 'draft');
        $tostatus = 'submitted';

        $DB->update_record('uckkchallenge_sub', self::filter_record('uckkchallenge_sub', [
            'id' => (int)$submission->id,
            'status' => $tostatus,
            'submittedtime' => $now,
            'timemodified' => $now,
            'modifiedby' => (int)$user->id,
        ]));

        if (self::table_exists('uckkchallenge_state')) {
            $state = [
                'challengeid' => (int)$challenge->id,
                'submissionid' => (int)$submission->id,
                'evaluationid' => 0,
                'userid' => (int)$submission->userid,
                'fromstatus' => $fromstatus,
                'tostatus' => $tostatus,
                'action' => 'submit',
                'reason' => 'Submission sent for human review.',
                'reasonformat' => FORMAT_PLAIN,
                'integritystate' => integrity_state::UNVERIFIED,
                'provenancehash' => hash('sha256', (string)$submission->id . ':' . $now . ':submit'),
                'timecreated' => $now,
                'createdby' => (int)$user->id,
                'metadata' => json_encode(['source' => 'submission_service'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ];
            $DB->insert_record('uckkchallenge_state', self::filter_record('uckkchallenge_state', $state));
        }

        $updated = $DB->get_record('uckkchallenge_sub', ['id' => (int)$submission->id], '*', MUST_EXIST);
        $this->trigger_event('\mod_uckkchallenge\event\submission_submitted', $challenge, $context, $updated, [
            'relateduserid' => (int)$updated->userid,
        ]);

        return $updated;
    }

    /**
     * Get a compact submission status payload.
     */
    public function get_submission_status(stdClass $challenge, stdClass $cm, stdClass $course, context_module $context, stdClass $user, int $userid = 0): array {
        require_once(__DIR__ . '/../../locallib.php');

        $targetuserid = $userid > 0 ? $userid : (int)$user->id;
        $submission = $this->get_latest_submission($challenge, $targetuserid);
        if (!$submission) {
            return [
                'submissionid' => 0,
                'status' => 'none',
                'statuslabel' => get_string('nosubmissionyet', 'uckkchallenge'),
                'cansubmit' => has_capability('mod/uckkchallenge:submitproof', $context),
                'timemodified' => 0,
            ];
        }

        return [
            'submissionid' => (int)$submission->id,
            'status' => (string)$submission->status,
            'statuslabel' => function_exists('uckkchallenge_get_status_label')
                ? uckkchallenge_get_status_label((string)$submission->status)
                : ucwords(str_replace('_', ' ', (string)$submission->status)),
            'cansubmit' => has_capability('mod/uckkchallenge:submitproof', $context),
            'timemodified' => (int)$submission->timemodified,
            'submittedtime' => (int)($submission->submittedtime ?? 0),
        ];
    }

    /**
     * Get proof/submission list for the viewer.
     */
    public function get_proof_list(stdClass $challenge, stdClass $cm, stdClass $course, context_module $context, stdClass $user): array {
        global $DB;

        require_once(__DIR__ . '/../../locallib.php');

        $params = ['challengeid' => (int)$challenge->id];
        $where = 'challengeid = :challengeid';
        if (!has_capability('mod/uckkchallenge:viewallsubmissions', $context)) {
            $where .= ' AND userid = :userid';
            $params['userid'] = (int)$user->id;
        }

        $submissions = $DB->get_records_select('uckkchallenge_sub', $where, $params, 'timemodified DESC, id DESC');
        $items = [];
        foreach ($submissions as $submission) {
            $items[] = $this->format_submission_for_display($submission, $context);
        }

        return [
            'uniqid' => 'uckkchallenge-proof-list-' . $challenge->id,
            'hasproofs' => !empty($items),
            'canreview' => has_capability('mod/uckkchallenge:evaluate', $context),
            'cansubmit' => has_capability('mod/uckkchallenge:submitproof', $context),
            'submiturl' => (new \moodle_url('/mod/uckkchallenge/submit.php', ['id' => $cm->id]))->out(false),
            'proofs' => array_values($items),
        ];
    }

    /**
     * Build a display payload for a single submission.
     */
    public function format_submission_for_display(stdClass $submission, context_module $context): array {
        require_once(__DIR__ . '/../../locallib.php');

        $files = $this->get_submission_files($context, (int)$submission->id);
        $status = (string)($submission->status ?? 'draft');
        $integrity = integrity_state::normalise((string)($submission->integritystate ?? integrity_state::UNVERIFIED));

        return [
            'id' => (int)$submission->id,
            'anchor' => 'proof-' . (int)$submission->id,
            'challengeid' => (int)$submission->challengeid,
            'submissionid' => (int)$submission->id,
            'userid' => (int)$submission->userid,
            'title' => get_string('submission', 'uckkchallenge') . ' #' . (int)$submission->id,
            'status' => $status,
            'statuslabel' => function_exists('uckkchallenge_get_status_label')
                ? uckkchallenge_get_status_label($status)
                : ucwords(str_replace('_', ' ', $status)),
            'statusclass' => function_exists('uckkchallenge_get_status_class')
                ? uckkchallenge_get_status_class($status)
                : 'status-' . str_replace('_', '-', $status),
            'prooftype' => (string)($submission->prooftype ?? 'text'),
            'prooftypelabel' => ucwords(str_replace('_', ' ', (string)($submission->prooftype ?? 'text'))),
            'visibility' => (string)($submission->visibility ?? 'course'),
            'visibilitylabel' => function_exists('uckkchallenge_get_visibility_label')
                ? uckkchallenge_get_visibility_label((string)$submission->visibility)
                : ucwords((string)($submission->visibility ?? 'course')),
            'integritystate' => $integrity,
            'integritylabel' => integrity_state::label($integrity),
            'integrityclass' => integrity_state::css_class($integrity),
            'hastext' => trim((string)($submission->submissiontext ?? '')) !== '',
            'submissionhtml' => format_text((string)($submission->submissiontext ?? ''), (int)($submission->submissiontextformat ?? FORMAT_HTML), ['context' => $context]),
            'hascontent' => trim((string)($submission->submissiontext ?? '')) !== '',
            'content' => format_text((string)($submission->submissiontext ?? ''), (int)($submission->submissiontextformat ?? FORMAT_HTML), ['context' => $context]),
            'hasurl' => trim((string)($submission->submissionurl ?? '')) !== '',
            'url' => (string)($submission->submissionurl ?? ''),
            'submissionurl' => (string)($submission->submissionurl ?? ''),
            'hasfiles' => !empty($files),
            'files' => $files,
            'hasrelationtocriteria' => trim((string)($submission->relationtocriteria ?? '')) !== '',
            'relationtocriteria' => s((string)($submission->relationtocriteria ?? '')),
            'hasprovenance' => trim((string)($submission->provenancestatement ?? '')) !== '',
            'hasprovenancestatement' => trim((string)($submission->provenancestatement ?? '')) !== '',
            'provenance' => format_text((string)($submission->provenancestatement ?? ''), FORMAT_PLAIN, ['context' => $context]),
            'provenancestatement' => s((string)($submission->provenancestatement ?? '')),
            'hassourceauthor' => trim((string)($submission->sourceauthor ?? '')) !== '',
            'sourceauthor' => s((string)($submission->sourceauthor ?? '')),
            'hassourcedate' => !empty($submission->sourcedate),
            'sourcedate' => !empty($submission->sourcedate) ? userdate((int)$submission->sourcedate) : '',
            'sourcedatelabel' => !empty($submission->sourcedate) ? userdate((int)$submission->sourcedate) : '',
            'aiassisted' => !empty($submission->aiassisted),
            'hasailog' => trim((string)($submission->ailog ?? '')) !== '',
            'ailog' => s((string)($submission->ailog ?? '')),
            'hasuncertaintynotes' => trim((string)($submission->uncertaintynotes ?? '')) !== '',
            'uncertaintynotes' => s((string)($submission->uncertaintynotes ?? '')),
            'timecreatedlabel' => !empty($submission->timecreated) ? userdate((int)$submission->timecreated) : '',
            'timemodifiedlabel' => !empty($submission->timemodified) ? userdate((int)$submission->timemodified) : '',
            'timesubmitted' => !empty($submission->submittedtime) ? userdate((int)$submission->submittedtime) : '',
            'authorname' => fullname(\core_user::get_user((int)$submission->userid, '*', MUST_EXIST)),
            'canreview' => has_capability('mod/uckkchallenge:evaluate', $context),
            'reviewurl' => (new \moodle_url('/mod/uckkchallenge/evaluate.php', ['id' => $context->instanceid, 'submissionid' => $submission->id]))->out(false),
            'canview' => true,
            'viewurl' => (new \moodle_url('/mod/uckkchallenge/view.php', ['id' => $context->instanceid]))->out(false),
            'actions' => [
                [
                    'label' => get_string('edit'),
                    'url' => (new \moodle_url('/mod/uckkchallenge/submit.php', ['id' => $context->instanceid, 'submissionid' => $submission->id]))->out(false),
                ],
            ],
            'hasactions' => true,
        ];
    }

    /**
     * Build evaluation panel payload.
     */
    public function get_evaluation_panel(stdClass $challenge, stdClass $cm, stdClass $course, context_module $context, stdClass $user, int $submissionid = 0): array {
        global $DB;

        require_once(__DIR__ . '/../../locallib.php');

        $submission = $submissionid > 0 ? $this->get_submission_by_id($challenge, $submissionid) : $this->get_latest_submission($challenge, (int)$user->id);
        if (!$submission) {
            return [
                'uniqid' => 'uckkchallenge-evaluation-' . $challenge->id,
                'haspanel' => false,
                'title' => get_string('review', 'uckkchallenge'),
            ];
        }

        $evaluation = $DB->get_record('uckkchallenge_eval', ['submissionid' => (int)$submission->id], '*', IGNORE_MULTIPLE);
        $status = (string)($evaluation->status ?? $submission->status ?? 'under_review');
        $criteria = [];
        if (!empty($evaluation->rubricjson)) {
            $decoded = json_decode((string)$evaluation->rubricjson, true);
            if (is_array($decoded)) {
                foreach ($decoded as $item) {
                    $criteria[] = [
                        'name' => s((string)($item['name'] ?? 'Criterion')),
                        'description' => s((string)($item['description'] ?? '')),
                        'rating' => s((string)($item['rating'] ?? '')),
                        'score' => s((string)($item['score'] ?? '')),
                        'statusclass' => 'status-' . str_replace('_', '-', strtolower((string)($item['rating'] ?? ''))),
                    ];
                }
            }
        }

        return [
            'uniqid' => 'uckkchallenge-evaluation-' . $challenge->id,
            'haspanel' => true,
            'title' => get_string('review', 'uckkchallenge'),
            'status' => $status,
            'statuslabel' => function_exists('uckkchallenge_get_status_label')
                ? uckkchallenge_get_status_label($status)
                : ucwords(str_replace('_', ' ', $status)),
            'statusclass' => function_exists('uckkchallenge_get_status_class')
                ? uckkchallenge_get_status_class($status)
                : 'status-' . str_replace('_', '-', $status),
            'hasevaluation' => !empty($evaluation),
            'evaluatorname' => !empty($evaluation->evaluatorid) ? fullname(\core_user::get_user((int)$evaluation->evaluatorid, '*', MUST_EXIST)) : '',
            'timemodified' => !empty($evaluation->timemodified) ? userdate((int)$evaluation->timemodified) : '',
            'hasrubric' => !empty($criteria),
            'rubricintro' => !empty($criteria) ? get_string('evaluationcriteria', 'uckkchallenge') : '',
            'criteria' => $criteria,
            'hasmentorfeedback' => !empty($evaluation->feedback),
            'mentorfeedback' => format_text((string)($evaluation->feedback ?? ''), (int)($evaluation->feedbackformat ?? FORMAT_HTML), ['context' => $context]),
            'hasfeedback' => !empty($evaluation->feedback),
            'feedback' => format_text((string)($evaluation->feedback ?? ''), (int)($evaluation->feedbackformat ?? FORMAT_HTML), ['context' => $context]),
            'hasprivatefeedback' => !empty($evaluation->privatefeedback),
            'privatefeedback' => format_text((string)($evaluation->privatefeedback ?? ''), (int)($evaluation->privatefeedbackformat ?? FORMAT_HTML), ['context' => $context]),
            'haspublicsummary' => !empty($challenge->publicsummary),
            'publicsummary' => format_text((string)($challenge->publicsummary ?? ''), (int)($challenge->publicsummaryformat ?? FORMAT_HTML), ['context' => $context]),
            'hasintegrity' => true,
            'integritystate' => integrity_state::normalise((string)($evaluation->integritystate ?? $submission->integritystate ?? integrity_state::UNVERIFIED)),
            'integritystatelabel' => integrity_state::label((string)($evaluation->integritystate ?? $submission->integritystate ?? integrity_state::UNVERIFIED)),
            'hassubmission' => true,
            'submissionid' => (int)$submission->id,
            'actionurl' => (new \moodle_url('/mod/uckkchallenge/evaluate.php', ['id' => $cm->id, 'submissionid' => $submission->id]))->out(false),
        ];
    }

    /**
     * Create/update an evaluation for a submission.
     */
    public function evaluate_proof(stdClass $challenge, stdClass $cm, stdClass $course, context_module $context, stdClass $user, int $submissionid, array $data): stdClass {
        global $DB;

        $submission = $this->get_submission_by_id($challenge, $submissionid);
        if (!$submission) {
            throw new \moodle_exception('invalidsubmissionid', 'uckkchallenge');
        }

        $decision = trim((string)($data['decision'] ?? 'reviewed'));
        $newstatus = $this->resolve_evaluation_status($decision, !empty($data['requestintegrityreview']));
        $now = time();
        $metadata = [
            'source' => 'human',
            'decision' => $decision,
            'validationstate' => (string)($data['validationstate'] ?? integrity_state::UNVERIFIED),
            'integrityreviewrequested' => !empty($data['requestintegrityreview']),
            'archiveexportrequested' => !empty($data['requestarchiveexport']),
        ];
        $metadatajson = json_encode($metadata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}';

        $existing = $DB->get_record('uckkchallenge_eval', [
            'submissionid' => (int)$submission->id,
            'evaluatorid' => (int)$user->id,
        ]);

        $record = [
            'challengeid' => (int)$challenge->id,
            'submissionid' => (int)$submission->id,
            'userid' => (int)$submission->userid,
            'evaluatorid' => (int)$user->id,
            'rubricjson' => (string)($data['rubricjson'] ?? ''),
            'feedback' => (string)($data['mentorfeedback'] ?? ''),
            'feedbackformat' => FORMAT_HTML,
            'privatefeedback' => (string)($data['privatefeedback'] ?? ''),
            'privatefeedbackformat' => FORMAT_HTML,
            'competencyrating' => (string)($data['competencyrating'] ?? ''),
            'badgetriggered' => !empty($data['badgetriggered']) ? 1 : 0,
            'integritystate' => integrity_state::normalise((string)($data['validationstate'] ?? integrity_state::UNVERIFIED)),
            'integritycaseid' => (int)($data['integritycaseid'] ?? 0),
            'grade' => (float)($data['grade'] ?? 0),
            'previousstatus' => (string)($submission->status ?? ''),
            'status' => $newstatus,
            'timemodified' => $now,
            'modifiedby' => (int)$user->id,
            'versionno' => 1,
            'metadata' => $metadatajson,
        ];

        if ($existing) {
            $record['id'] = (int)$existing->id;
            $DB->update_record('uckkchallenge_eval', self::filter_record('uckkchallenge_eval', $record));
            $evaluationid = (int)$existing->id;
            $eventclass = '\mod_uckkchallenge\event\evaluation_updated';
        } else {
            $record['timecreated'] = $now;
            $record['createdby'] = (int)$user->id;
            $evaluationid = (int)$DB->insert_record('uckkchallenge_eval', self::filter_record('uckkchallenge_eval', $record));
            $eventclass = '\mod_uckkchallenge\event\evaluation_created';
        }

        $DB->update_record('uckkchallenge_sub', self::filter_record('uckkchallenge_sub', [
            'id' => (int)$submission->id,
            'status' => $newstatus,
            'integritystate' => integrity_state::normalise((string)($data['validationstate'] ?? integrity_state::UNVERIFIED)),
            'timemodified' => $now,
            'reviewedtime' => $now,
            'mentorfeedback' => (string)($data['mentorfeedback'] ?? ''),
            'modifiedby' => (int)$user->id,
        ]));

        if (self::table_exists('uckkchallenge_state')) {
            $DB->insert_record('uckkchallenge_state', self::filter_record('uckkchallenge_state', [
                'challengeid' => (int)$challenge->id,
                'submissionid' => (int)$submission->id,
                'evaluationid' => $evaluationid,
                'userid' => (int)$submission->userid,
                'fromstatus' => (string)$submission->status,
                'tostatus' => $newstatus,
                'action' => 'evaluate',
                'reason' => (string)($data['mentorfeedback'] ?? ''),
                'reasonformat' => FORMAT_HTML,
                'integritystate' => integrity_state::normalise((string)($data['validationstate'] ?? integrity_state::UNVERIFIED)),
                'provenancehash' => hash('sha256', $metadatajson),
                'timecreated' => $now,
                'createdby' => (int)$user->id,
                'metadata' => $metadatajson,
            ]));
        }

        $evaluation = $DB->get_record('uckkchallenge_eval', ['id' => $evaluationid], '*', MUST_EXIST);
        $this->trigger_event($eventclass, $challenge, $context, $evaluation, [
            'relateduserid' => (int)$submission->userid,
            'other' => ['submissionid' => (int)$submission->id],
        ]);

        return $evaluation;
    }

    /**
     * Build a complete payload for the challenge view renderable.
     */
    public function get_challenge_payload(stdClass $challenge, stdClass $cm, stdClass $course, context_module $context, stdClass $user, array $abilities = []): array {
        global $DB;

        require_once(__DIR__ . '/../../locallib.php');

        $submission = $this->get_current_user_submission($challenge, $cm, $context, $user);
        $review = $submission ? $this->get_evaluation_panel($challenge, $cm, $course, $context, $user, (int)$submission->id) : ['hasfeedback' => false];

        $rules = [];
        if (self::table_exists('uckkchallenge_rule')) {
            foreach ($DB->get_records('uckkchallenge_rule', ['challengeid' => (int)$challenge->id], 'sortorder ASC, id ASC') as $rule) {
                $rules[] = [
                    'name' => s((string)$rule->rulename),
                    'description' => format_text((string)($rule->description ?? ''), (int)($rule->descriptionformat ?? FORMAT_HTML), ['context' => $context]),
                ];
            }
        }

        $corridors = [];
        if (self::table_exists('uckkchallenge_corr')) {
            foreach ($DB->get_records('uckkchallenge_corr', ['challengeid' => (int)$challenge->id], 'sortorder ASC, id ASC') as $corridor) {
                $corridors[] = [
                    'title' => s((string)$corridor->title),
                    'description' => format_text((string)($corridor->description ?? ''), (int)($corridor->descriptionformat ?? FORMAT_HTML), ['context' => $context]),
                ];
            }
        }

        $actions = [];
        if (!empty($abilities['cansubmitproof'])) {
            $actions[] = [
                'label' => get_string('submitchallengeproof', 'uckkchallenge'),
                'url' => (new \moodle_url('/mod/uckkchallenge/submit.php', ['id' => $cm->id]))->out(false),
            ];
        }
        if (!empty($abilities['canevaluate'])) {
            $actions[] = [
                'label' => get_string('review', 'uckkchallenge'),
                'url' => (new \moodle_url('/mod/uckkchallenge/evaluate.php', ['id' => $cm->id]))->out(false),
            ];
        }
        if (!empty($abilities['canvalidateintegrity'])) {
            $actions[] = [
                'label' => get_string('integrityreview', 'uckkchallenge'),
                'url' => (new \moodle_url('/mod/uckkchallenge/integrity.php', ['id' => $cm->id]))->out(false),
            ];
        }
        if (!empty($abilities['canarchive'])) {
            $actions[] = [
                'label' => get_string('archivechallenge', 'uckkchallenge'),
                'url' => (new \moodle_url('/mod/uckkchallenge/archive.php', ['id' => $cm->id]))->out(false),
            ];
        }

        return [
            'uniqid' => 'uckkchallenge-view-' . (int)$challenge->id,
            'id' => (int)$challenge->id,
            'cmid' => (int)$cm->id,
            'courseid' => (int)$course->id,
            'title' => format_string((string)$challenge->name, true, ['context' => $context]),
            'intro' => format_module_intro('uckkchallenge', $challenge, $cm->id),
            'hasintro' => trim((string)($challenge->intro ?? '')) !== '',
            'challengecode' => s((string)($challenge->challengecode ?? '')),
            'haschallengecode' => trim((string)($challenge->challengecode ?? '')) !== '',
            'challengetype' => (string)($challenge->challengetype ?? ''),
            'challengetypelabel' => ucwords(str_replace('_', ' ', (string)($challenge->challengetype ?? ''))),
            'status' => (string)($challenge->status ?? 'draft'),
            'statuslabel' => function_exists('uckkchallenge_get_status_label')
                ? uckkchallenge_get_status_label((string)$challenge->status)
                : ucwords(str_replace('_', ' ', (string)($challenge->status ?? 'draft'))),
            'statusclass' => function_exists('uckkchallenge_get_status_class')
                ? uckkchallenge_get_status_class((string)$challenge->status)
                : 'status-' . str_replace('_', '-', (string)($challenge->status ?? 'draft')),
            'visibility' => (string)($challenge->visibility ?? 'course'),
            'visibilitylabel' => function_exists('uckkchallenge_get_visibility_label')
                ? uckkchallenge_get_visibility_label((string)$challenge->visibility)
                : ucwords((string)($challenge->visibility ?? 'course')),
            'statement' => format_text((string)($challenge->statement ?? ''), (int)($challenge->statementformat ?? FORMAT_HTML), ['context' => $context]),
            'hasstatement' => trim((string)($challenge->statement ?? '')) !== '',
            'contexttext' => format_text((string)($challenge->contexttext ?? ''), (int)($challenge->contexttextformat ?? FORMAT_HTML), ['context' => $context]),
            'hascontexttext' => trim((string)($challenge->contexttext ?? '')) !== '',
            'rules' => $rules,
            'hasrules' => !empty($rules),
            'corridors' => $corridors,
            'hascorridors' => !empty($corridors),
            'evidencepolicy' => format_text((string)($challenge->evidencepolicy ?? ''), (int)($challenge->evidencepolicyformat ?? FORMAT_HTML), ['context' => $context]),
            'hasevidencepolicy' => trim((string)($challenge->evidencepolicy ?? '')) !== '',
            'criteria' => format_text((string)($challenge->criteria ?? ''), (int)($challenge->criteriaformat ?? FORMAT_HTML), ['context' => $context]),
            'hascriteria' => trim((string)($challenge->criteria ?? '')) !== '',
            'ethicalconstraints' => format_text((string)($challenge->ethicalconstraints ?? ''), (int)($challenge->ethicalconstraintsformat ?? FORMAT_HTML), ['context' => $context]),
            'hasethicalconstraints' => trim((string)($challenge->ethicalconstraints ?? '')) !== '',
            'timeline' => [
                'open' => !empty($challenge->timeopen) ? userdate((int)$challenge->timeopen) : '',
                'close' => !empty($challenge->timeclose) ? userdate((int)$challenge->timeclose) : '',
                'reviewby' => !empty($challenge->timereviewby) ? userdate((int)$challenge->timereviewby) : '',
                'hasreviewby' => !empty($challenge->timereviewby),
            ],
            'hastimeline' => !empty($challenge->timeopen) || !empty($challenge->timeclose) || !empty($challenge->timereviewby),
            'actions' => $actions,
            'hasactions' => !empty($actions),
            'submission' => $submission ? [
                'status' => (string)$submission->status,
                'statuslabel' => function_exists('uckkchallenge_get_status_label') ? uckkchallenge_get_status_label((string)$submission->status) : ucwords(str_replace('_', ' ', (string)$submission->status)),
            ] + $this->format_submission_for_display($submission, $context) : [],
            'hassubmission' => !empty($submission),
            'review' => [
                'statuslabel' => (string)($review['statuslabel'] ?? ''),
                'feedback' => (string)($review['feedback'] ?? ''),
                'hasfeedback' => !empty($review['hasfeedback']),
                'competencyratings' => [],
                'hascompetencyratings' => false,
            ],
            'hasreview' => !empty($submission),
            'notices' => [],
            'hasnotices' => false,
            'warnings' => [],
            'haswarnings' => false,
        ];
    }

    /**
     * Get the latest submission for a user.
     */
    public function get_latest_submission(stdClass $challenge, int $userid): ?stdClass {
        global $DB;

        $sql = 'challengeid = :challengeid AND userid = :userid';
        return $DB->get_record_select('uckkchallenge_sub', $sql, [
            'challengeid' => (int)$challenge->id,
            'userid' => $userid,
        ], '*', IGNORE_MULTIPLE) ?: null;
    }

    /**
     * Resolve the target status for an evaluation.
     */
    private function resolve_evaluation_status(string $decision, bool $requestintegrityreview): string {
        $decision = core_text::strtolower(trim($decision));
        if ($requestintegrityreview || in_array($decision, ['integrity', 'integrity_review', 'escalate'], true)) {
            return 'integrity_review';
        }
        if (in_array($decision, ['revise', 'revision', 'revision_required'], true)) {
            return 'revision_required';
        }
        if (in_array($decision, ['validated', 'validate', 'approved', 'approve', 'pass'], true)) {
            return 'validated';
        }
        if (in_array($decision, ['invalidated', 'invalidate'], true)) {
            return 'invalidated';
        }
        if (in_array($decision, ['closed', 'close'], true)) {
            return 'closed';
        }
        return 'under_review';
    }

    /**
     * Get files attached to a submission.
     */
    private function get_submission_files(context_module $context, int $submissionid): array {
        $fs = get_file_storage();
        $files = $fs->get_area_files($context->id, 'mod_uckkchallenge', 'proof_files', $submissionid, 'filename', false);
        $results = [];
        foreach ($files as $file) {
            $results[] = [
                'filename' => $file->get_filename(),
                'url' => \moodle_url::make_pluginfile_url(
                    $file->get_contextid(),
                    $file->get_component(),
                    $file->get_filearea(),
                    $file->get_itemid(),
                    $file->get_filepath(),
                    $file->get_filename()
                )->out(false),
                'filesize' => display_size($file->get_filesize()),
                'mimetype' => $file->get_mimetype(),
            ];
        }
        return $results;
    }

    /**
     * Trigger an event if the class exists.
     */
    private function trigger_event(string $eventclass, stdClass $challenge, context_module $context, stdClass $record, array $options = []): void {
        if (!class_exists($eventclass)) {
            return;
        }

        $data = [
            'objectid' => (int)$record->id,
            'context' => $context,
            'relateduserid' => (int)($options['relateduserid'] ?? 0),
            'other' => (array)($options['other'] ?? []),
        ];
        if (empty($data['relateduserid'])) {
            unset($data['relateduserid']);
        }
        if (empty($data['other'])) {
            unset($data['other']);
        }

        $event = $eventclass::create($data);
        $event->add_record_snapshot('uckkchallenge', $challenge);
        $event->add_record_snapshot($event::get_objectid_mapping()['db'] ?? 'uckkchallenge', $record);
        $event->trigger();
    }

    /**
     * Check if a database table exists.
     */
    private static function table_exists(string $table): bool {
        global $DB;
        return $DB->get_manager()->table_exists(new xmldb_table($table));
    }

    /**
     * Filter values to the target table columns.
     */
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
