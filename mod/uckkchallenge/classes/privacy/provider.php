<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

/**
 * Privacy provider for UCKK Challenge.
 *
 * @package    mod_uckkchallenge
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace mod_uckkchallenge\privacy;

use context;
use context_module;
use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\helper;
use core_privacy\local\request\transform;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;
use stdClass;

defined('MOODLE_INTERNAL') || die();

/**
 * Privacy provider for UCKK Challenge personal data.
 *
 * The challenge module stores learner proof submissions, proof files,
 * provenance statements, AI disclosure logs, mentor evaluations, and challenge
 * state records. Integrity cases remain owned by tool_uckkintegrity and archive
 * records remain owned by mod_uckkarchive.
 */
final class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\plugin\provider,
    \core_privacy\local\request\core_userlist_provider {

    /**
     * Component name.
     */
    private const COMPONENT = 'mod_uckkchallenge';

    /**
     * File area for submission editor text.
     */
    private const FILEAREA_SUBMISSION_TEXT = 'submission_text';

    /**
     * File area for proof files.
     */
    private const FILEAREA_PROOF_FILES = 'proof_files';

    /**
     * Describe personal data stored by this plugin.
     *
     * @param collection $collection Metadata collection.
     * @return collection
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table('uckkchallenge_sub', [
            'challengeid' => 'privacy:metadata:uckkchallenge_sub:challengeid',
            'userid' => 'privacy:metadata:uckkchallenge_sub:userid',
            'status' => 'privacy:metadata:uckkchallenge_sub:status',
            'visibility' => 'privacy:metadata:uckkchallenge_sub:visibility',
            'submissiontext' => 'privacy:metadata:uckkchallenge_sub:submissiontext',
            'submissionurl' => 'privacy:metadata:uckkchallenge_sub:submissionurl',
            'relationtocriteria' => 'privacy:metadata:uckkchallenge_sub:relationtocriteria',
            'provenancestatement' => 'privacy:metadata:uckkchallenge_sub:provenancestatement',
            'sourceauthor' => 'privacy:metadata:uckkchallenge_sub:sourceauthor',
            'sourcedate' => 'privacy:metadata:uckkchallenge_sub:sourcedate',
            'aiassisted' => 'privacy:metadata:uckkchallenge_sub:aiassisted',
            'ailog' => 'privacy:metadata:uckkchallenge_sub:ailog',
            'uncertaintynotes' => 'privacy:metadata:uckkchallenge_sub:uncertaintynotes',
            'timecreated' => 'privacy:metadata:uckkchallenge_sub:timecreated',
            'timemodified' => 'privacy:metadata:uckkchallenge_sub:timemodified',
        ], 'privacy:metadata:uckkchallenge_sub');

        $collection->add_database_table('uckkchallenge_proof', [
            'submissionid' => 'privacy:metadata:uckkchallenge_proof:submissionid',
            'userid' => 'privacy:metadata:uckkchallenge_proof:userid',
            'prooftype' => 'privacy:metadata:uckkchallenge_proof:prooftype',
            'title' => 'privacy:metadata:uckkchallenge_proof:title',
            'description' => 'privacy:metadata:uckkchallenge_proof:description',
            'source' => 'privacy:metadata:uckkchallenge_proof:source',
            'author' => 'privacy:metadata:uckkchallenge_proof:author',
            'sourcedate' => 'privacy:metadata:uckkchallenge_proof:sourcedate',
            'visibility' => 'privacy:metadata:uckkchallenge_proof:visibility',
            'relationtocriteria' => 'privacy:metadata:uckkchallenge_proof:relationtocriteria',
            'provenance' => 'privacy:metadata:uckkchallenge_proof:provenance',
            'integritystate' => 'privacy:metadata:uckkchallenge_proof:integritystate',
            'timecreated' => 'privacy:metadata:uckkchallenge_proof:timecreated',
            'timemodified' => 'privacy:metadata:uckkchallenge_proof:timemodified',
        ], 'privacy:metadata:uckkchallenge_proof');

        $collection->add_database_table('uckkchallenge_eval', [
            'submissionid' => 'privacy:metadata:uckkchallenge_eval:submissionid',
            'userid' => 'privacy:metadata:uckkchallenge_eval:userid',
            'reviewerid' => 'privacy:metadata:uckkchallenge_eval:reviewerid',
            'status' => 'privacy:metadata:uckkchallenge_eval:status',
            'validationstate' => 'privacy:metadata:uckkchallenge_eval:validationstate',
            'feedback' => 'privacy:metadata:uckkchallenge_eval:feedback',
            'privatefeedback' => 'privacy:metadata:uckkchallenge_eval:privatefeedback',
            'competencyrating' => 'privacy:metadata:uckkchallenge_eval:competencyrating',
            'badgetriggered' => 'privacy:metadata:uckkchallenge_eval:badgetriggered',
            'timecreated' => 'privacy:metadata:uckkchallenge_eval:timecreated',
            'timemodified' => 'privacy:metadata:uckkchallenge_eval:timemodified',
        ], 'privacy:metadata:uckkchallenge_eval');

        $collection->add_database_table('uckkchallenge_state', [
            'submissionid' => 'privacy:metadata:uckkchallenge_state:submissionid',
            'userid' => 'privacy:metadata:uckkchallenge_state:userid',
            'status' => 'privacy:metadata:uckkchallenge_state:status',
            'integritystate' => 'privacy:metadata:uckkchallenge_state:integritystate',
            'archived' => 'privacy:metadata:uckkchallenge_state:archived',
            'metadata' => 'privacy:metadata:uckkchallenge_state:metadata',
            'timecreated' => 'privacy:metadata:uckkchallenge_state:timecreated',
            'timemodified' => 'privacy:metadata:uckkchallenge_state:timemodified',
        ], 'privacy:metadata:uckkchallenge_state');

        $collection->add_subsystem_link('core_files', [], 'privacy:metadata:core_files');

        return $collection;
    }

    /**
     * Get contexts where the user has UCKK Challenge personal data.
     *
     * @param int $userid User id.
     * @return contextlist
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();

        $params = [
            'contextlevel' => CONTEXT_MODULE,
            'modulename' => 'uckkchallenge',
            'subuserid' => $userid,
            'proofuserid' => $userid,
            'evaluserid' => $userid,
            'reviewerid' => $userid,
            'stateuserid' => $userid,
        ];

        $sql = "
            SELECT DISTINCT ctx.id
              FROM {context} ctx
              JOIN {course_modules} cm ON cm.id = ctx.instanceid
              JOIN {modules} m ON m.id = cm.module AND m.name = :modulename
              JOIN {uckkchallenge} challenge ON challenge.id = cm.instance
         LEFT JOIN {uckkchallenge_sub} sub ON sub.challengeid = challenge.id
         LEFT JOIN {uckkchallenge_proof} proof ON proof.submissionid = sub.id
         LEFT JOIN {uckkchallenge_eval} eval ON eval.submissionid = sub.id
         LEFT JOIN {uckkchallenge_state} state ON state.submissionid = sub.id
             WHERE ctx.contextlevel = :contextlevel
               AND (
                    sub.userid = :subuserid
                    OR proof.userid = :proofuserid
                    OR eval.userid = :evaluserid
                    OR eval.reviewerid = :reviewerid
                    OR state.userid = :stateuserid
               )
        ";

        $contextlist->add_from_sql($sql, $params);

        return $contextlist;
    }

    /**
     * Get users with UCKK Challenge personal data in this context.
     *
     * @param userlist $userlist Userlist.
     */
    public static function get_users_in_context(userlist $userlist): void {
        $context = $userlist->get_context();

        if (!$context instanceof context_module) {
            return;
        }

        $params = [
            'contextid' => $context->id,
            'modulename' => 'uckkchallenge',
        ];

        $basesql = "
              FROM {context} ctx
              JOIN {course_modules} cm ON cm.id = ctx.instanceid
              JOIN {modules} m ON m.id = cm.module AND m.name = :modulename
              JOIN {uckkchallenge} challenge ON challenge.id = cm.instance
        ";

        $where = "
             WHERE ctx.id = :contextid
        ";

        $userlist->add_from_sql(
            'userid',
            "SELECT sub.userid
               {$basesql}
               JOIN {uckkchallenge_sub} sub ON sub.challengeid = challenge.id
               {$where}",
            $params
        );

        $userlist->add_from_sql(
            'userid',
            "SELECT proof.userid
               {$basesql}
               JOIN {uckkchallenge_sub} sub ON sub.challengeid = challenge.id
               JOIN {uckkchallenge_proof} proof ON proof.submissionid = sub.id
               {$where}",
            $params
        );

        $userlist->add_from_sql(
            'userid',
            "SELECT eval.userid
               {$basesql}
               JOIN {uckkchallenge_sub} sub ON sub.challengeid = challenge.id
               JOIN {uckkchallenge_eval} eval ON eval.submissionid = sub.id
               {$where}",
            $params
        );

        $userlist->add_from_sql(
            'userid',
            "SELECT eval.reviewerid
               {$basesql}
               JOIN {uckkchallenge_sub} sub ON sub.challengeid = challenge.id
               JOIN {uckkchallenge_eval} eval ON eval.submissionid = sub.id
               {$where}",
            $params
        );

        $userlist->add_from_sql(
            'userid',
            "SELECT state.userid
               {$basesql}
               JOIN {uckkchallenge_sub} sub ON sub.challengeid = challenge.id
               JOIN {uckkchallenge_state} state ON state.submissionid = sub.id
               {$where}",
            $params
        );
    }

    /**
     * Export user data for approved contexts.
     *
     * @param approved_contextlist $contextlist Approved contexts.
     */
    public static function export_user_data(approved_contextlist $contextlist): void {
        global $DB;

        $userid = (int)$contextlist->get_user()->id;

        foreach ($contextlist->get_contexts() as $context) {
            if (!$context instanceof context_module) {
                continue;
            }

            $challenge = self::get_challenge_from_context($context);

            if (!$challenge) {
                continue;
            }

            $submissions = $DB->get_records('uckkchallenge_sub', [
                'challengeid' => $challenge->id,
                'userid' => $userid,
            ], 'timecreated ASC, id ASC');

            if (empty($submissions)) {
                continue;
            }

            $path = [
                get_string('pluginname', 'uckkchallenge'),
                format_string($challenge->name),
            ];

            helper::export_context_files($context, $contextlist->get_user());

            $export = new stdClass();
            $export->challenge = format_string($challenge->name);
            $export->submissions = [];

            foreach ($submissions as $submission) {
                $submissionexport = self::export_submission($submission);
                $submissionexport->proofs = self::export_proofs((int)$submission->id);
                $submissionexport->evaluations = self::export_evaluations((int)$submission->id, $userid);
                $submissionexport->states = self::export_states((int)$submission->id, $userid);

                $export->submissions[] = $submissionexport;

                writer::with_context($context)->export_area_files(
                    array_merge($path, [
                        get_string('privacy:submission', 'uckkchallenge', $submission->id),
                    ]),
                    self::COMPONENT,
                    self::FILEAREA_SUBMISSION_TEXT,
                    (int)$submission->id
                );

                writer::with_context($context)->export_area_files(
                    array_merge($path, [
                        get_string('privacy:proofs', 'uckkchallenge', $submission->id),
                    ]),
                    self::COMPONENT,
                    self::FILEAREA_PROOF_FILES,
                    (int)$submission->id
                );
            }

            writer::with_context($context)->export_data($path, $export);
        }
    }

    /**
     * Delete all user data for all users in the specified context.
     *
     * @param context $context Context.
     */
    public static function delete_data_for_all_users_in_context(context $context): void {
        global $DB;

        if (!$context instanceof context_module) {
            return;
        }

        $challenge = self::get_challenge_from_context($context);

        if (!$challenge) {
            return;
        }

        $submissions = $DB->get_records('uckkchallenge_sub', [
            'challengeid' => $challenge->id,
        ]);

        foreach ($submissions as $submission) {
            self::delete_submission_data($context, $submission);
        }
    }

    /**
     * Delete all user data for the specified user in approved contexts.
     *
     * @param approved_contextlist $contextlist Approved contexts.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist): void {
        global $DB;

        $userid = (int)$contextlist->get_user()->id;

        foreach ($contextlist->get_contexts() as $context) {
            if (!$context instanceof context_module) {
                continue;
            }

            $challenge = self::get_challenge_from_context($context);

            if (!$challenge) {
                continue;
            }

            $submissions = $DB->get_records('uckkchallenge_sub', [
                'challengeid' => $challenge->id,
                'userid' => $userid,
            ]);

            foreach ($submissions as $submission) {
                self::delete_submission_data($context, $submission);
            }

            self::anonymise_reviewer_data($challenge, $userid);
        }
    }

    /**
     * Delete data for approved users in one context.
     *
     * @param approved_userlist $userlist Approved userlist.
     */
    public static function delete_data_for_users(approved_userlist $userlist): void {
        global $DB;

        $context = $userlist->get_context();

        if (!$context instanceof context_module) {
            return;
        }

        $challenge = self::get_challenge_from_context($context);

        if (!$challenge) {
            return;
        }

        foreach ($userlist->get_userids() as $userid) {
            $submissions = $DB->get_records('uckkchallenge_sub', [
                'challengeid' => $challenge->id,
                'userid' => (int)$userid,
            ]);

            foreach ($submissions as $submission) {
                self::delete_submission_data($context, $submission);
            }

            self::anonymise_reviewer_data($challenge, (int)$userid);
        }
    }

    /**
     * Get the challenge instance for a module context.
     *
     * @param context_module $context Module context.
     * @return stdClass|null
     */
    private static function get_challenge_from_context(context_module $context): ?stdClass {
        global $DB;

        $params = [
            'contextid' => $context->id,
            'modulename' => 'uckkchallenge',
        ];

        $sql = "
            SELECT challenge.*
              FROM {context} ctx
              JOIN {course_modules} cm ON cm.id = ctx.instanceid
              JOIN {modules} m ON m.id = cm.module AND m.name = :modulename
              JOIN {uckkchallenge} challenge ON challenge.id = cm.instance
             WHERE ctx.id = :contextid
        ";

        $challenge = $DB->get_record_sql($sql, $params);

        return $challenge ?: null;
    }

    /**
     * Export one submission record.
     *
     * @param stdClass $submission Submission record.
     * @return stdClass
     */
    private static function export_submission(stdClass $submission): stdClass {
        return (object)[
            'id' => (int)$submission->id,
            'status' => $submission->status ?? '',
            'visibility' => $submission->visibility ?? '',
            'submissiontext' => format_text(
                $submission->submissiontext ?? '',
                (int)($submission->submissiontextformat ?? FORMAT_HTML)
            ),
            'submissionurl' => $submission->submissionurl ?? '',
            'relationtocriteria' => $submission->relationtocriteria ?? '',
            'provenancestatement' => $submission->provenancestatement ?? '',
            'sourceauthor' => $submission->sourceauthor ?? '',
            'sourcedate' => !empty($submission->sourcedate) ? transform::datetime($submission->sourcedate) : '',
            'aiassisted' => !empty($submission->aiassisted),
            'ailog' => $submission->ailog ?? '',
            'uncertaintynotes' => $submission->uncertaintynotes ?? '',
            'timecreated' => transform::datetime($submission->timecreated ?? 0),
            'timemodified' => transform::datetime($submission->timemodified ?? 0),
        ];
    }

    /**
     * Export proof records for one submission.
     *
     * @param int $submissionid Submission id.
     * @return array<int, stdClass>
     */
    private static function export_proofs(int $submissionid): array {
        global $DB;

        $records = $DB->get_records('uckkchallenge_proof', [
            'submissionid' => $submissionid,
        ], 'timecreated ASC, id ASC');

        $proofs = [];

        foreach ($records as $record) {
            $proofs[] = (object)[
                'id' => (int)$record->id,
                'prooftype' => $record->prooftype ?? '',
                'title' => $record->title ?? '',
                'description' => $record->description ?? '',
                'source' => $record->source ?? '',
                'author' => $record->author ?? '',
                'sourcedate' => !empty($record->sourcedate) ? transform::datetime($record->sourcedate) : '',
                'visibility' => $record->visibility ?? '',
                'relationtocriteria' => $record->relationtocriteria ?? '',
                'provenance' => $record->provenance ?? '',
                'integritystate' => $record->integritystate ?? '',
                'timecreated' => transform::datetime($record->timecreated ?? 0),
                'timemodified' => transform::datetime($record->timemodified ?? 0),
            ];
        }

        return $proofs;
    }

    /**
     * Export evaluations linked to the user.
     *
     * @param int $submissionid Submission id.
     * @param int $userid User id.
     * @return array<int, stdClass>
     */
    private static function export_evaluations(int $submissionid, int $userid): array {
        global $DB;

        $select = 'submissionid = :submissionid AND (userid = :userid OR reviewerid = :reviewerid)';
        $records = $DB->get_records_select('uckkchallenge_eval', $select, [
            'submissionid' => $submissionid,
            'userid' => $userid,
            'reviewerid' => $userid,
        ], 'timecreated ASC, id ASC');

        $evaluations = [];

        foreach ($records as $record) {
            $evaluations[] = (object)[
                'id' => (int)$record->id,
                'userid' => (int)($record->userid ?? 0),
                'reviewerid' => (int)($record->reviewerid ?? 0),
                'status' => $record->status ?? '',
                'validationstate' => $record->validationstate ?? '',
                'feedback' => $record->feedback ?? '',
                'privatefeedback' => $record->privatefeedback ?? '',
                'competencyrating' => $record->competencyrating ?? '',
                'badgetriggered' => !empty($record->badgetriggered),
                'timecreated' => transform::datetime($record->timecreated ?? 0),
                'timemodified' => transform::datetime($record->timemodified ?? 0),
            ];
        }

        return $evaluations;
    }

    /**
     * Export challenge state records linked to the user.
     *
     * @param int $submissionid Submission id.
     * @param int $userid User id.
     * @return array<int, stdClass>
     */
    private static function export_states(int $submissionid, int $userid): array {
        global $DB;

        $records = $DB->get_records('uckkchallenge_state', [
            'submissionid' => $submissionid,
            'userid' => $userid,
        ], 'timecreated ASC, id ASC');

        $states = [];

        foreach ($records as $record) {
            $states[] = (object)[
                'id' => (int)$record->id,
                'status' => $record->status ?? '',
                'integritystate' => $record->integritystate ?? '',
                'archived' => !empty($record->archived),
                'metadata' => $record->metadata ?? '',
                'timecreated' => transform::datetime($record->timecreated ?? 0),
                'timemodified' => transform::datetime($record->timemodified ?? 0),
            ];
        }

        return $states;
    }

    /**
     * Delete or anonymise one submission and linked data.
     *
     * Draft/private submissions are deleted. Validated, archived, public, or
     * institutionally significant submissions are anonymised to preserve audit
     * and archive consistency.
     *
     * @param context_module $context Module context.
     * @param stdClass $submission Submission record.
     */
    private static function delete_submission_data(context_module $context, stdClass $submission): void {
        global $DB;

        $preserve = self::must_preserve_submission($submission);

        if ($preserve) {
            self::anonymise_submission($submission);
            return;
        }

        $DB->delete_records('uckkchallenge_state', ['submissionid' => $submission->id]);
        $DB->delete_records('uckkchallenge_eval', ['submissionid' => $submission->id]);
        $DB->delete_records('uckkchallenge_proof', ['submissionid' => $submission->id]);
        $DB->delete_records('uckkchallenge_sub', ['id' => $submission->id]);

        $fs = get_file_storage();
        $fs->delete_area_files($context->id, self::COMPONENT, self::FILEAREA_SUBMISSION_TEXT, (int)$submission->id);
        $fs->delete_area_files($context->id, self::COMPONENT, self::FILEAREA_PROOF_FILES, (int)$submission->id);
    }

    /**
     * Decide whether a submission must be preserved as an anonymised record.
     *
     * @param stdClass $submission Submission record.
     * @return bool
     */
    private static function must_preserve_submission(stdClass $submission): bool {
        $status = (string)($submission->status ?? '');
        $visibility = (string)($submission->visibility ?? '');

        return in_array($status, [
            'validated',
            'archived',
            'contested',
            'invalidated',
            'under_review',
            'integrity_review',
        ], true) || in_array($visibility, [
            'public',
            'institution',
            'program',
            'restricted_integrity',
        ], true);
    }

    /**
     * Anonymise a preserved submission and linked records.
     *
     * @param stdClass $submission Submission record.
     */
    private static function anonymise_submission(stdClass $submission): void {
        global $DB;

        $now = time();

        $DB->set_field('uckkchallenge_sub', 'userid', 0, ['id' => $submission->id]);
        $DB->set_field('uckkchallenge_sub', 'submissiontext', '', ['id' => $submission->id]);
        $DB->set_field('uckkchallenge_sub', 'submissionurl', '', ['id' => $submission->id]);
        $DB->set_field('uckkchallenge_sub', 'sourceauthor', get_string('privacy:anonymiseduser', 'uckkchallenge'), ['id' => $submission->id]);
        $DB->set_field('uckkchallenge_sub', 'ailog', '', ['id' => $submission->id]);
        $DB->set_field('uckkchallenge_sub', 'uncertaintynotes', '', ['id' => $submission->id]);
        $DB->set_field('uckkchallenge_sub', 'timemodified', $now, ['id' => $submission->id]);

        $proofs = $DB->get_records('uckkchallenge_proof', ['submissionid' => $submission->id]);
        foreach ($proofs as $proof) {
            $DB->set_field('uckkchallenge_proof', 'userid', 0, ['id' => $proof->id]);
            $DB->set_field('uckkchallenge_proof', 'author', get_string('privacy:anonymiseduser', 'uckkchallenge'), ['id' => $proof->id]);
            $DB->set_field('uckkchallenge_proof', 'description', '', ['id' => $proof->id]);
            $DB->set_field('uckkchallenge_proof', 'source', '', ['id' => $proof->id]);
            $DB->set_field('uckkchallenge_proof', 'timemodified', $now, ['id' => $proof->id]);
        }

        $evaluations = $DB->get_records('uckkchallenge_eval', ['submissionid' => $submission->id]);
        foreach ($evaluations as $evaluation) {
            $DB->set_field('uckkchallenge_eval', 'userid', 0, ['id' => $evaluation->id]);
            $DB->set_field('uckkchallenge_eval', 'privatefeedback', '', ['id' => $evaluation->id]);
            $DB->set_field('uckkchallenge_eval', 'timemodified', $now, ['id' => $evaluation->id]);
        }

        $states = $DB->get_records('uckkchallenge_state', ['submissionid' => $submission->id]);
        foreach ($states as $state) {
            $DB->set_field('uckkchallenge_state', 'userid', 0, ['id' => $state->id]);
            $DB->set_field('uckkchallenge_state', 'metadata', '', ['id' => $state->id]);
            $DB->set_field('uckkchallenge_state', 'timemodified', $now, ['id' => $state->id]);
        }
    }

    /**
     * Anonymise reviewer-specific data without deleting the reviewed learner's
     * institutional record.
     *
     * @param stdClass $challenge Challenge record.
     * @param int $userid User id.
     */
    private static function anonymise_reviewer_data(stdClass $challenge, int $userid): void {
        global $DB;

        $sql = "
            SELECT eval.*
              FROM {uckkchallenge_eval} eval
              JOIN {uckkchallenge_sub} sub ON sub.id = eval.submissionid
             WHERE sub.challengeid = :challengeid
               AND eval.reviewerid = :reviewerid
        ";

        $records = $DB->get_records_sql($sql, [
            'challengeid' => $challenge->id,
            'reviewerid' => $userid,
        ]);

        foreach ($records as $record) {
            $DB->set_field('uckkchallenge_eval', 'reviewerid', 0, ['id' => $record->id]);
            $DB->set_field('uckkchallenge_eval', 'privatefeedback', '', ['id' => $record->id]);
            $DB->set_field('uckkchallenge_eval', 'timemodified', time(), ['id' => $record->id]);
        }
    }
}