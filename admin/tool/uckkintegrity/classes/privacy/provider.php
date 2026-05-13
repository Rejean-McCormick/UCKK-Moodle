<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

namespace tool_uckkintegrity\privacy;

defined('MOODLE_INTERNAL') || die();

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\transform;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

/**
 * Privacy provider for UCKK integrity cases.
 *
 * Integrity cases may contain personal data about openers, assigned
 * Inquisiteurs, parties, notes, appeals, evidence summaries, decisions,
 * corrections, and archive summaries.
 *
 * The provider exports directly related user data and applies a conservative
 * deletion strategy: private notes and appeals are deleted, while institutional
 * case records are anonymised when they must remain for audit, provenance,
 * contestability, and retention.
 *
 * @package    tool_uckkintegrity
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\plugin\provider,
    \core_privacy\local\request\core_userlist_provider {

    /**
     * Describe stored personal data.
     *
     * @param collection $collection Metadata collection.
     * @return collection
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table(
            'tool_uckkintegrity_case',
            [
                'contextid' => 'privacy:metadata:case:contextid',
                'openedby' => 'privacy:metadata:case:openedby',
                'assignedto' => 'privacy:metadata:case:assignedto',
                'summary' => 'privacy:metadata:case:summary',
                'decision' => 'privacy:metadata:case:decision',
                'correction' => 'privacy:metadata:case:correction',
                'appealpath' => 'privacy:metadata:case:appealpath',
                'archivesummary' => 'privacy:metadata:case:archivesummary',
                'metadata' => 'privacy:metadata:case:metadata',
            ],
            'privacy:metadata:case'
        );

        $collection->add_database_table(
            'tool_uckkintegrity_note',
            [
                'caseid' => 'privacy:metadata:note:caseid',
                'userid' => 'privacy:metadata:note:userid',
                'notetype' => 'privacy:metadata:note:notetype',
                'body' => 'privacy:metadata:note:body',
                'visibility' => 'privacy:metadata:note:visibility',
                'metadata' => 'privacy:metadata:note:metadata',
            ],
            'privacy:metadata:note'
        );

        $collection->add_database_table(
            'tool_uckkintegrity_appeal',
            [
                'caseid' => 'privacy:metadata:appeal:caseid',
                'userid' => 'privacy:metadata:appeal:userid',
                'body' => 'privacy:metadata:appeal:body',
                'status' => 'privacy:metadata:appeal:status',
                'decision' => 'privacy:metadata:appeal:decision',
                'decidedby' => 'privacy:metadata:appeal:decidedby',
            ],
            'privacy:metadata:appeal'
        );

        $collection->add_subsystem_link(
            'core_files',
            [],
            'privacy:metadata:files'
        );

        return $collection;
    }

    /**
     * Return contexts containing data for a user.
     *
     * @param int $userid User id.
     * @return contextlist
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();

        $sql = "SELECT DISTINCT ctx.id
                  FROM {context} ctx
                  JOIN {tool_uckkintegrity_case} c
                    ON c.contextid = ctx.id
             LEFT JOIN {tool_uckkintegrity_note} n
                    ON n.caseid = c.id
             LEFT JOIN {tool_uckkintegrity_appeal} a
                    ON a.caseid = c.id
                 WHERE c.openedby = :openedby
                    OR c.assignedto = :assignedto
                    OR n.userid = :noteuserid
                    OR a.userid = :appealuserid
                    OR a.decidedby = :decidedby";

        $contextlist->add_from_sql($sql, [
            'openedby' => $userid,
            'assignedto' => $userid,
            'noteuserid' => $userid,
            'appealuserid' => $userid,
            'decidedby' => $userid,
        ]);

        return $contextlist;
    }

    /**
     * Export user data from approved contexts.
     *
     * @param approved_contextlist $contextlist Approved context list.
     */
    public static function export_user_data(approved_contextlist $contextlist): void {
        global $DB;

        $userid = $contextlist->get_user()->id;

        foreach ($contextlist->get_contexts() as $context) {
            $cases = self::get_related_cases($context->id, $userid);

            foreach ($cases as $case) {
                $casepath = [
                    get_string('privacy:pathcases', 'tool_uckkintegrity'),
                    $case->id,
                ];

                writer::with_context($context)->export_data($casepath, (object)[
                    'casetype' => $case->casetype,
                    'subjectcomponent' => $case->subjectcomponent,
                    'subjectid' => $case->subjectid,
                    'openedby' => $case->openedby,
                    'assignedto' => $case->assignedto,
                    'severity' => $case->severity,
                    'status' => $case->status,
                    'summary' => $case->summary,
                    'decision' => $case->decision,
                    'correction' => $case->correction,
                    'appealpath' => $case->appealpath,
                    'archivesummary' => $case->archivesummary,
                    'archiveitemid' => $case->archiveitemid,
                    'visibility' => $case->visibility,
                    'metadata' => $case->metadata,
                    'timecreated' => transform::datetime($case->timecreated),
                    'timemodified' => transform::datetime($case->timemodified),
                    'timeclosed' => $case->timeclosed ? transform::datetime($case->timeclosed) : null,
                ]);

                $notes = $DB->get_records(
                    'tool_uckkintegrity_note',
                    [
                        'caseid' => $case->id,
                        'userid' => $userid,
                    ],
                    'timecreated ASC, id ASC'
                );

                foreach ($notes as $note) {
                    writer::with_context($context)->export_data(
                        array_merge($casepath, [
                            get_string('privacy:pathnotes', 'tool_uckkintegrity'),
                            $note->id,
                        ]),
                        (object)[
                            'notetype' => $note->notetype,
                            'body' => $note->body,
                            'visibility' => $note->visibility,
                            'metadata' => $note->metadata,
                            'timecreated' => transform::datetime($note->timecreated),
                            'timemodified' => transform::datetime($note->timemodified),
                        ]
                    );
                }

                $appeals = $DB->get_records_select(
                    'tool_uckkintegrity_appeal',
                    'caseid = :caseid AND (userid = :userid OR decidedby = :decidedby)',
                    [
                        'caseid' => $case->id,
                        'userid' => $userid,
                        'decidedby' => $userid,
                    ],
                    'timecreated ASC, id ASC'
                );

                foreach ($appeals as $appeal) {
                    writer::with_context($context)->export_data(
                        array_merge($casepath, [
                            get_string('privacy:pathappeals', 'tool_uckkintegrity'),
                            $appeal->id,
                        ]),
                        (object)[
                            'body' => $appeal->body,
                            'status' => $appeal->status,
                            'decision' => $appeal->decision,
                            'decidedby' => $appeal->decidedby,
                            'timecreated' => transform::datetime($appeal->timecreated),
                            'timemodified' => transform::datetime($appeal->timemodified),
                        ]
                    );
                }

                writer::with_context($context)->export_area_files(
                    $casepath,
                    'tool_uckkintegrity',
                    'case',
                    $case->id
                );
            }
        }
    }

    /**
     * Delete all integrity data in a context.
     *
     * @param \context $context Moodle context.
     */
    public static function delete_data_for_all_users_in_context(\context $context): void {
        global $DB;

        $caseids = $DB->get_fieldset_select(
            'tool_uckkintegrity_case',
            'id',
            'contextid = :contextid',
            ['contextid' => $context->id]
        );

        if (empty($caseids)) {
            return;
        }

        [$casesql, $params] = $DB->get_in_or_equal($caseids, SQL_PARAMS_NAMED);

        $DB->delete_records_select('tool_uckkintegrity_note', "caseid $casesql", $params);
        $DB->delete_records_select('tool_uckkintegrity_appeal', "caseid $casesql", $params);
        $DB->delete_records_select('tool_uckkintegrity_case', "id $casesql", $params);

        $fs = get_file_storage();
        $fs->delete_area_files($context->id, 'tool_uckkintegrity', 'case');
    }

    /**
     * Delete or anonymise data for one user in approved contexts.
     *
     * @param approved_contextlist $contextlist Approved context list.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist): void {
        global $DB;

        $userid = $contextlist->get_user()->id;

        foreach ($contextlist->get_contexts() as $context) {
            $caseids = $DB->get_fieldset_select(
                'tool_uckkintegrity_case',
                'id',
                'contextid = :contextid',
                ['contextid' => $context->id]
            );

            if (empty($caseids)) {
                continue;
            }

            [$casesql, $caseparams] = $DB->get_in_or_equal($caseids, SQL_PARAMS_NAMED);

            $noteparams = $caseparams + ['userid' => $userid];
            $DB->delete_records_select(
                'tool_uckkintegrity_note',
                "caseid $casesql AND userid = :userid",
                $noteparams
            );

            $appealparams = $caseparams + [
                'userid' => $userid,
                'decidedby' => $userid,
            ];
            $DB->delete_records_select(
                'tool_uckkintegrity_appeal',
                "caseid $casesql AND (userid = :userid OR decidedby = :decidedby)",
                $appealparams
            );

            $cases = $DB->get_records('tool_uckkintegrity_case', [
                'contextid' => $context->id,
            ]);

            foreach ($cases as $case) {
                $changed = false;

                if ((int)$case->openedby === (int)$userid) {
                    $case->openedby = 0;
                    $changed = true;
                }

                if (!empty($case->assignedto) && (int)$case->assignedto === (int)$userid) {
                    $case->assignedto = null;
                    $changed = true;
                }

                if ($changed) {
                    $metadata = json_decode((string)$case->metadata, true);
                    if (!is_array($metadata)) {
                        $metadata = [];
                    }

                    $metadata['privacy_redactions'][] = [
                        'userid' => $userid,
                        'action' => 'user_anonymised',
                        'timecreated' => time(),
                    ];

                    $case->metadata = json_encode($metadata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                    $case->timemodified = time();

                    $DB->update_record('tool_uckkintegrity_case', $case);
                }
            }
        }
    }

    /**
     * Add users represented in a context to the supplied user list.
     *
     * @param userlist $userlist User list.
     */
    public static function get_users_in_context(userlist $userlist): void {
        $context = $userlist->get_context();

        $userlist->add_from_sql(
            'openedby',
            "SELECT openedby
               FROM {tool_uckkintegrity_case}
              WHERE contextid = :contextid
                AND openedby <> 0",
            ['contextid' => $context->id]
        );

        $userlist->add_from_sql(
            'assignedto',
            "SELECT assignedto
               FROM {tool_uckkintegrity_case}
              WHERE contextid = :contextid
                AND assignedto IS NOT NULL",
            ['contextid' => $context->id]
        );

        $userlist->add_from_sql(
            'userid',
            "SELECT n.userid
               FROM {tool_uckkintegrity_note} n
               JOIN {tool_uckkintegrity_case} c
                 ON c.id = n.caseid
              WHERE c.contextid = :contextid",
            ['contextid' => $context->id]
        );

        $userlist->add_from_sql(
            'userid',
            "SELECT a.userid
               FROM {tool_uckkintegrity_appeal} a
               JOIN {tool_uckkintegrity_case} c
                 ON c.id = a.caseid
              WHERE c.contextid = :contextid",
            ['contextid' => $context->id]
        );

        $userlist->add_from_sql(
            'decidedby',
            "SELECT a.decidedby
               FROM {tool_uckkintegrity_appeal} a
               JOIN {tool_uckkintegrity_case} c
                 ON c.id = a.caseid
              WHERE c.contextid = :contextid
                AND a.decidedby IS NOT NULL",
            ['contextid' => $context->id]
        );
    }

    /**
     * Delete or anonymise data for multiple users in one approved context.
     *
     * @param approved_userlist $userlist Approved user list.
     */
    public static function delete_data_for_users(approved_userlist $userlist): void {
        global $DB;

        $userids = $userlist->get_userids();

        if (empty($userids)) {
            return;
        }

        $context = $userlist->get_context();

        $caseids = $DB->get_fieldset_select(
            'tool_uckkintegrity_case',
            'id',
            'contextid = :contextid',
            ['contextid' => $context->id]
        );

        if (empty($caseids)) {
            return;
        }

        [$casesql, $caseparams] = $DB->get_in_or_equal($caseids, SQL_PARAMS_NAMED);
        [$usersql, $userparams] = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);

        $DB->delete_records_select(
            'tool_uckkintegrity_note',
            "caseid $casesql AND userid $usersql",
            $caseparams + $userparams
        );

        $DB->delete_records_select(
            'tool_uckkintegrity_appeal',
            "caseid $casesql AND (userid $usersql OR decidedby $usersql)",
            $caseparams + $userparams
        );

        $cases = $DB->get_records('tool_uckkintegrity_case', [
            'contextid' => $context->id,
        ]);

        foreach ($cases as $case) {
            $changed = false;

            foreach ($userids as $userid) {
                if ((int)$case->openedby === (int)$userid) {
                    $case->openedby = 0;
                    $changed = true;
                }

                if (!empty($case->assignedto) && (int)$case->assignedto === (int)$userid) {
                    $case->assignedto = null;
                    $changed = true;
                }
            }

            if ($changed) {
                $metadata = json_decode((string)$case->metadata, true);
                if (!is_array($metadata)) {
                    $metadata = [];
                }

                $metadata['privacy_redactions'][] = [
                    'userids' => array_values($userids),
                    'action' => 'users_anonymised',
                    'timecreated' => time(),
                ];

                $case->metadata = json_encode($metadata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                $case->timemodified = time();

                $DB->update_record('tool_uckkintegrity_case', $case);
            }
        }
    }

    /**
     * Fetch cases connected to a user in one context.
     *
     * @param int $contextid Context id.
     * @param int $userid User id.
     * @return array
     */
    private static function get_related_cases(int $contextid, int $userid): array {
        global $DB;

        $sql = "SELECT DISTINCT c.*
                  FROM {tool_uckkintegrity_case} c
             LEFT JOIN {tool_uckkintegrity_note} n
                    ON n.caseid = c.id
             LEFT JOIN {tool_uckkintegrity_appeal} a
                    ON a.caseid = c.id
                 WHERE c.contextid = :contextid
                   AND (
                        c.openedby = :openedby
                     OR c.assignedto = :assignedto
                     OR n.userid = :noteuserid
                     OR a.userid = :appealuserid
                     OR a.decidedby = :decidedby
                   )
              ORDER BY c.timecreated ASC, c.id ASC";

        return array_values($DB->get_records_sql($sql, [
            'contextid' => $contextid,
            'openedby' => $userid,
            'assignedto' => $userid,
            'noteuserid' => $userid,
            'appealuserid' => $userid,
            'decidedby' => $userid,
        ]));
    }
}