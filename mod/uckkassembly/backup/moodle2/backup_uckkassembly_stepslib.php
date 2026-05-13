<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

/**
 * Backup structure step for UCKK Assembly.
 *
 * @package    mod_uckkassembly
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Defines the complete backup structure for one UCKK Assembly activity.
 */
class backup_uckkassembly_activity_structure_step extends backup_activity_structure_step {
    /**
     * Define the backup structure.
     *
     * @return backup_nested_element
     */
    protected function define_structure() {
        $userinfo = $this->get_setting_value('userinfo');

        $assembly = new backup_nested_element('uckkassembly', ['id'], [
            'course',
            'cmid',
            'contextid',
            'name',
            'intro',
            'introformat',
            'assemblytype',
            'purpose',
            'status',
            'visibility',
            'timeopen',
            'timeclose',
            'timemotionclose',
            'timevoteopen',
            'timevoteclose',
            'quorum',
            'votingmethod',
            'decisionthreshold',
            'allowmotions',
            'allowamendments',
            'allowcontests',
            'integrityrequired',
            'archivepolicy',
            'createdby',
            'modifiedby',
            'timecreated',
            'timemodified',
            'versionno',
            'provenance',
            'provenancehash',
            'metadata',
        ]);

        $motions = new backup_nested_element('motions');
        $motion = new backup_nested_element('motion', ['id'], [
            'courseid',
            'cmid',
            'contextid',
            'proposedby',
            'title',
            'summary',
            'motiontext',
            'decisiontype',
            'status',
            'visibility',
            'timeopen',
            'timeclose',
            'sortorder',
            'createdby',
            'modifiedby',
            'timecreated',
            'timemodified',
            'versionno',
            'provenance',
            'provenancehash',
            'metadata',
        ]);

        $amendments = new backup_nested_element('amendments');
        $amendment = new backup_nested_element('amendment', ['id'], [
            'assemblyid',
            'parentamendid',
            'courseid',
            'cmid',
            'contextid',
            'proposedby',
            'title',
            'amendmenttext',
            'rationale',
            'status',
            'visibility',
            'sortorder',
            'createdby',
            'modifiedby',
            'timecreated',
            'timemodified',
            'versionno',
            'provenance',
            'provenancehash',
            'metadata',
        ]);

        $objections = new backup_nested_element('objections');
        $objection = new backup_nested_element('objection', ['id'], [
            'motionid',
            'decisionid',
            'courseid',
            'cmid',
            'contextid',
            'userid',
            'objecttype',
            'title',
            'rationale',
            'requestedchange',
            'status',
            'visibility',
            'createdby',
            'modifiedby',
            'timecreated',
            'timemodified',
            'versionno',
            'provenance',
            'provenancehash',
            'metadata',
        ]);

        $votes = new backup_nested_element('votes');
        $vote = new backup_nested_element('vote', ['id'], [
            'assemblyid',
            'courseid',
            'cmid',
            'contextid',
            'userid',
            'votechoice',
            'rationale',
            'confidence',
            'conflictdeclared',
            'conflictnotes',
            'aiassisted',
            'ailog',
            'status',
            'visibility',
            'createdby',
            'modifiedby',
            'timecreated',
            'timemodified',
            'versionno',
            'provenance',
            'provenancehash',
            'metadata',
        ]);

        $decisions = new backup_nested_element('decisions');
        $decision = new backup_nested_element('decision', ['id'], [
            'motionid',
            'courseid',
            'cmid',
            'contextid',
            'decisiontype',
            'title',
            'summary',
            'decisiontext',
            'resultstatus',
            'supportcount',
            'opposecount',
            'abstaincount',
            'amendmentcount',
            'blockcount',
            'totalvotes',
            'quorumreached',
            'thresholdreached',
            'publishedby',
            'timepublished',
            'archiveitemid',
            'integritycaseid',
            'status',
            'visibility',
            'createdby',
            'modifiedby',
            'timecreated',
            'timemodified',
            'versionno',
            'provenance',
            'provenancehash',
            'metadata',
        ]);

        $minutesrecords = new backup_nested_element('minutesrecords');
        $minutes = new backup_nested_element('minutes', ['id'], [
            'courseid',
            'cmid',
            'contextid',
            'title',
            'summary',
            'minutestext',
            'publishedby',
            'timepublished',
            'archiveitemid',
            'status',
            'visibility',
            'createdby',
            'modifiedby',
            'timecreated',
            'timemodified',
            'versionno',
            'provenance',
            'provenancehash',
            'metadata',
        ]);

        $contests = new backup_nested_element('contests');
        $contest = new backup_nested_element('contest', ['id'], [
            'decisionid',
            'motionid',
            'courseid',
            'cmid',
            'contextid',
            'openedby',
            'assignedto',
            'contesttype',
            'summary',
            'rationale',
            'evidencelinks',
            'resolution',
            'decisionnotes',
            'integritycaseid',
            'archiveitemid',
            'status',
            'visibility',
            'createdby',
            'modifiedby',
            'timecreated',
            'timemodified',
            'versionno',
            'provenance',
            'provenancehash',
            'metadata',
        ]);

        $assembly->add_child($motions);
        $motions->add_child($motion);

        $motion->add_child($amendments);
        $amendments->add_child($amendment);

        $motion->add_child($votes);
        $votes->add_child($vote);

        $assembly->add_child($objections);
        $objections->add_child($objection);

        $assembly->add_child($decisions);
        $decisions->add_child($decision);

        $assembly->add_child($minutesrecords);
        $minutesrecords->add_child($minutes);

        $assembly->add_child($contests);
        $contests->add_child($contest);

        $assembly->set_source_table('uckkassembly', [
            'id' => backup::VAR_ACTIVITYID,
        ]);

        $motion->set_source_table('uckkassembly_motion', [
            'assemblyid' => backup::VAR_PARENTID,
        ], 'sortorder ASC, id ASC');

        $amendment->set_source_table('uckkassembly_amend', [
            'motionid' => backup::VAR_PARENTID,
        ], 'sortorder ASC, id ASC');

        $decision->set_source_table('uckkassembly_decision', [
            'assemblyid' => backup::VAR_PARENTID,
        ], 'id ASC');

        $minutes->set_source_table('uckkassembly_minutes', [
            'assemblyid' => backup::VAR_PARENTID,
        ], 'timepublished ASC, id ASC');

        if ($userinfo) {
            $vote->set_source_table('uckkassembly_vote', [
                'motionid' => backup::VAR_PARENTID,
            ], 'id ASC');

            $objection->set_source_table('uckkassembly_object', [
                'assemblyid' => backup::VAR_PARENTID,
            ], 'id ASC');

            $contest->set_source_table('uckkassembly_contest', [
                'assemblyid' => backup::VAR_PARENTID,
            ], 'id ASC');
        }

        $assembly->annotate_ids('course', 'course');
        $assembly->annotate_ids('context', 'contextid');
        $assembly->annotate_ids('user', 'createdby');
        $assembly->annotate_ids('user', 'modifiedby');

        $motion->annotate_ids('course', 'courseid');
        $motion->annotate_ids('context', 'contextid');
        $motion->annotate_ids('user', 'proposedby');
        $motion->annotate_ids('user', 'createdby');
        $motion->annotate_ids('user', 'modifiedby');

        $amendment->annotate_ids('course', 'courseid');
        $amendment->annotate_ids('context', 'contextid');
        $amendment->annotate_ids('user', 'proposedby');
        $amendment->annotate_ids('user', 'createdby');
        $amendment->annotate_ids('user', 'modifiedby');

        $objection->annotate_ids('course', 'courseid');
        $objection->annotate_ids('context', 'contextid');
        $objection->annotate_ids('user', 'userid');
        $objection->annotate_ids('user', 'createdby');
        $objection->annotate_ids('user', 'modifiedby');

        $vote->annotate_ids('course', 'courseid');
        $vote->annotate_ids('context', 'contextid');
        $vote->annotate_ids('user', 'userid');
        $vote->annotate_ids('user', 'createdby');
        $vote->annotate_ids('user', 'modifiedby');

        $decision->annotate_ids('course', 'courseid');
        $decision->annotate_ids('context', 'contextid');
        $decision->annotate_ids('user', 'publishedby');
        $decision->annotate_ids('user', 'createdby');
        $decision->annotate_ids('user', 'modifiedby');

        $minutes->annotate_ids('course', 'courseid');
        $minutes->annotate_ids('context', 'contextid');
        $minutes->annotate_ids('user', 'publishedby');
        $minutes->annotate_ids('user', 'createdby');
        $minutes->annotate_ids('user', 'modifiedby');

        $contest->annotate_ids('course', 'courseid');
        $contest->annotate_ids('context', 'contextid');
        $contest->annotate_ids('user', 'openedby');
        $contest->annotate_ids('user', 'assignedto');
        $contest->annotate_ids('user', 'createdby');
        $contest->annotate_ids('user', 'modifiedby');

        $assembly->annotate_files('mod_uckkassembly', 'intro', null);
        $assembly->annotate_files('mod_uckkassembly', 'assembly_attachments', null);

        $motion->annotate_files('mod_uckkassembly', 'motion_attachments', 'id');
        $amendment->annotate_files('mod_uckkassembly', 'amendment_attachments', 'id');
        $objection->annotate_files('mod_uckkassembly', 'objection_attachments', 'id');
        $vote->annotate_files('mod_uckkassembly', 'vote_attachments', 'id');
        $decision->annotate_files('mod_uckkassembly', 'decision_attachments', 'id');
        $minutes->annotate_files('mod_uckkassembly', 'minutes_attachments', 'id');
        $contest->annotate_files('mod_uckkassembly', 'contest_attachments', 'id');

        return $this->prepare_activity_structure($assembly);
    }
}