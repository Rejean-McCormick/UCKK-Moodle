<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

/**
 * Backup structure step for mod_uckkchallenge.
 *
 * @package    mod_uckkchallenge
 * @category   backup
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/backup/moodle2/backup_activity_task.class.php');

/**
 * Defines the complete backup structure for a UCKK challenge activity.
 *
 * The backup includes:
 * - challenge instance configuration;
 * - rules;
 * - corridors of action;
 * - submissions;
 * - proof/evidence records;
 * - evaluations;
 * - state history.
 *
 * External archive items and integrity cases are not duplicated here. They are
 * owned by mod_uckkarchive and tool_uckkintegrity. This backup stores only
 * challenge-side references and metadata.
 */
class backup_uckkchallenge_activity_structure_step extends backup_activity_structure_step {
    /**
     * Define the backup XML structure.
     *
     * @return backup_nested_element
     */
    protected function define_structure(): backup_nested_element {
        $userinfo = $this->get_setting_value('userinfo');

        $challenge = new backup_nested_element('uckkchallenge', ['id'], [
            'course',
            'name',
            'intro',
            'introformat',
            'challengecode',
            'challengetype',
            'status',
            'visibility',
            'statement',
            'statementformat',
            'contexttext',
            'contexttextformat',
            'rules',
            'rulesformat',
            'corridors',
            'corridorsformat',
            'ethicalconstraints',
            'ethicalconstraintsformat',
            'evidencepolicy',
            'evidencepolicyformat',
            'criteria',
            'criteriaformat',
            'teamsubmissions',
            'maxsubmissions',
            'allowresubmission',
            'timeopen',
            'timeclose',
            'timereviewby',
            'integrityrequired',
            'integritynotes',
            'integritynotesformat',
            'aipolicy',
            'aipolicyformat',
            'archivepolicy',
            'publicsummary',
            'publicsummaryformat',
            'competencylinks',
            'badgelinks',
            'completionrequiresubmission',
            'completionrequirevalidation',
            'createdby',
            'modifiedby',
            'timecreated',
            'timemodified',
            'versionno',
            'provenancehash',
            'metadata',
        ]);

        $rules = new backup_nested_element('rules');
        $rule = new backup_nested_element('rule', ['id'], [
            'challengeid',
            'rulename',
            'ruletype',
            'description',
            'descriptionformat',
            'sortorder',
            'status',
            'visibility',
            'createdby',
            'modifiedby',
            'timecreated',
            'timemodified',
            'versionno',
            'metadata',
        ]);

        $corridors = new backup_nested_element('corridors');
        $corridor = new backup_nested_element('corridor', ['id'], [
            'challengeid',
            'corridorname',
            'corridortype',
            'description',
            'descriptionformat',
            'constraints',
            'constraintsformat',
            'sortorder',
            'status',
            'visibility',
            'createdby',
            'modifiedby',
            'timecreated',
            'timemodified',
            'versionno',
            'metadata',
        ]);

        $submissions = new backup_nested_element('submissions');
        $submission = new backup_nested_element('submission', ['id'], [
            'challengeid',
            'courseid',
            'cmid',
            'contextid',
            'userid',
            'groupid',
            'attemptno',
            'prooftype',
            'submissiontext',
            'submissiontextformat',
            'submissionurl',
            'relationtocriteria',
            'provenancestatement',
            'sourceauthor',
            'sourcedate',
            'visibility',
            'status',
            'integritystate',
            'provenance',
            'aiassisted',
            'ailog',
            'uncertaintynotes',
            'createdby',
            'modifiedby',
            'timecreated',
            'timemodified',
            'timesubmitted',
            'versionno',
            'provenancehash',
            'metadata',
        ]);

        $proofs = new backup_nested_element('proofs');
        $proof = new backup_nested_element('proof', ['id'], [
            'challengeid',
            'submissionid',
            'courseid',
            'cmid',
            'contextid',
            'userid',
            'prooftype',
            'title',
            'description',
            'descriptionformat',
            'sourceauthor',
            'sourcedate',
            'sourceurl',
            'relationtocriteria',
            'provenancestatement',
            'visibility',
            'status',
            'integritystate',
            'provenance',
            'aiassisted',
            'ailog',
            'uncertaintynotes',
            'createdby',
            'modifiedby',
            'timecreated',
            'timemodified',
            'versionno',
            'provenancehash',
            'metadata',
        ]);

        $evaluations = new backup_nested_element('evaluations');
        $evaluation = new backup_nested_element('evaluation', ['id'], [
            'challengeid',
            'submissionid',
            'proofid',
            'courseid',
            'cmid',
            'contextid',
            'userid',
            'evaluatorid',
            'evaluationtype',
            'score',
            'grade',
            'feedback',
            'feedbackformat',
            'competencyratings',
            'badgetriggers',
            'correctionrequired',
            'correctionnotes',
            'correctionnotesformat',
            'visibility',
            'status',
            'integritystate',
            'createdby',
            'modifiedby',
            'timecreated',
            'timemodified',
            'timeevaluated',
            'versionno',
            'metadata',
        ]);

        $states = new backup_nested_element('states');
        $state = new backup_nested_element('state', ['id'], [
            'challengeid',
            'submissionid',
            'proofid',
            'evaluationid',
            'courseid',
            'cmid',
            'contextid',
            'userid',
            'actorid',
            'fromstatus',
            'tostatus',
            'statetype',
            'reason',
            'reasonformat',
            'visibility',
            'integritystate',
            'timecreated',
            'metadata',
        ]);

        $challenge->add_child($rules);
        $rules->add_child($rule);

        $challenge->add_child($corridors);
        $corridors->add_child($corridor);

        $challenge->add_child($submissions);
        $submissions->add_child($submission);

        $submission->add_child($proofs);
        $proofs->add_child($proof);

        $submission->add_child($evaluations);
        $evaluations->add_child($evaluation);

        $challenge->add_child($states);
        $states->add_child($state);

        $challenge->set_source_table('uckkchallenge', [
            'id' => backup::VAR_ACTIVITYID,
        ]);

        $rule->set_source_table('uckkchallenge_rule', [
            'challengeid' => backup::VAR_PARENTID,
        ]);

        $corridor->set_source_table('uckkchallenge_corr', [
            'challengeid' => backup::VAR_PARENTID,
        ]);

        if ($userinfo) {
            $submission->set_source_table('uckkchallenge_sub', [
                'challengeid' => backup::VAR_PARENTID,
            ]);

            $proof->set_source_table('uckkchallenge_proof', [
                'submissionid' => backup::VAR_PARENTID,
            ]);

            $evaluation->set_source_table('uckkchallenge_eval', [
                'submissionid' => backup::VAR_PARENTID,
            ]);

            $state->set_source_table('uckkchallenge_state', [
                'challengeid' => backup::VAR_PARENTID,
            ]);
        }

        $challenge->annotate_ids('user', 'createdby');
        $challenge->annotate_ids('user', 'modifiedby');

        $rule->annotate_ids('user', 'createdby');
        $rule->annotate_ids('user', 'modifiedby');

        $corridor->annotate_ids('user', 'createdby');
        $corridor->annotate_ids('user', 'modifiedby');

        if ($userinfo) {
            $submission->annotate_ids('user', 'userid');
            $submission->annotate_ids('user', 'createdby');
            $submission->annotate_ids('user', 'modifiedby');
            $submission->annotate_ids('group', 'groupid');

            $proof->annotate_ids('user', 'userid');
            $proof->annotate_ids('user', 'createdby');
            $proof->annotate_ids('user', 'modifiedby');

            $evaluation->annotate_ids('user', 'userid');
            $evaluation->annotate_ids('user', 'evaluatorid');
            $evaluation->annotate_ids('user', 'createdby');
            $evaluation->annotate_ids('user', 'modifiedby');

            $state->annotate_ids('user', 'userid');
            $state->annotate_ids('user', 'actorid');
        }

        $challenge->annotate_files('mod_uckkchallenge', 'intro', null);
        $challenge->annotate_files('mod_uckkchallenge', 'statement', null);
        $challenge->annotate_files('mod_uckkchallenge', 'contexttext', null);
        $challenge->annotate_files('mod_uckkchallenge', 'rules', null);
        $challenge->annotate_files('mod_uckkchallenge', 'corridors', null);
        $challenge->annotate_files('mod_uckkchallenge', 'ethicalconstraints', null);
        $challenge->annotate_files('mod_uckkchallenge', 'evidencepolicy', null);
        $challenge->annotate_files('mod_uckkchallenge', 'criteria', null);
        $challenge->annotate_files('mod_uckkchallenge', 'integritynotes', null);
        $challenge->annotate_files('mod_uckkchallenge', 'aipolicy', null);
        $challenge->annotate_files('mod_uckkchallenge', 'publicsummary', null);

        if ($userinfo) {
            $submission->annotate_files('mod_uckkchallenge', 'submission_text', 'submission');
            $submission->annotate_files('mod_uckkchallenge', 'proof_files', 'submission');
            $proof->annotate_files('mod_uckkchallenge', 'proof_description', 'proof');
            $proof->annotate_files('mod_uckkchallenge', 'proof_files', 'proof');
            $evaluation->annotate_files('mod_uckkchallenge', 'evaluation_feedback', 'evaluation');
            $evaluation->annotate_files('mod_uckkchallenge', 'correction_notes', 'evaluation');
            $state->annotate_files('mod_uckkchallenge', 'state_reason', 'state');
        }

        return $this->prepare_activity_structure($challenge);
    }
}