<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

/**
 * Restore steps for the UCKK Challenge activity.
 *
 * @package    mod_uckkchallenge
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Restore structure step for Défis King Klown.
 *
 * This restores the activity instance and its related rule, corridor,
 * submission, proof, evaluation, and state records.
 */
class restore_uckkchallenge_activity_structure_step extends restore_activity_structure_step {
    /**
     * Define the XML structure to restore.
     *
     * @return restore_path_element[]
     */
    protected function define_structure(): array {
        $paths = [];

        $userinfo = $this->get_setting_value('userinfo');

        $paths[] = new restore_path_element('uckkchallenge', '/activity/uckkchallenge');

        $paths[] = new restore_path_element(
            'uckkchallenge_rule',
            '/activity/uckkchallenge/rules/rule'
        );

        $paths[] = new restore_path_element(
            'uckkchallenge_corr',
            '/activity/uckkchallenge/corridors/corridor'
        );

        if ($userinfo) {
            $paths[] = new restore_path_element(
                'uckkchallenge_sub',
                '/activity/uckkchallenge/submissions/submission'
            );

            $paths[] = new restore_path_element(
                'uckkchallenge_proof',
                '/activity/uckkchallenge/submissions/submission/proofs/proof'
            );

            $paths[] = new restore_path_element(
                'uckkchallenge_eval',
                '/activity/uckkchallenge/submissions/submission/evaluations/evaluation'
            );

            $paths[] = new restore_path_element(
                'uckkchallenge_state',
                '/activity/uckkchallenge/states/state'
            );
        }

        return $this->prepare_activity_structure($paths);
    }

    /**
     * Restore the main challenge instance.
     *
     * @param array $data Restored XML data.
     */
    protected function process_uckkchallenge(array $data): void {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->course = $this->get_courseid();

        if (isset($data->intro)) {
            $data->intro = $data->intro;
        }

        if (!isset($data->introformat)) {
            $data->introformat = FORMAT_HTML;
        }

        if (!isset($data->timecreated)) {
            $data->timecreated = time();
        }

        $data->timemodified = time();

        if (isset($data->createdby)) {
            $data->createdby = $this->get_mappingid('user', $data->createdby, 0);
        }

        if (isset($data->modifiedby)) {
            $data->modifiedby = $this->get_mappingid('user', $data->modifiedby, 0);
        }

        $newitemid = $DB->insert_record('uckkchallenge', $data);

        $this->apply_activity_instance($newitemid);
        $this->set_mapping('uckkchallenge', $oldid, $newitemid, true);
    }

    /**
     * Restore a challenge rule.
     *
     * @param array $data Restored XML data.
     */
    protected function process_uckkchallenge_rule(array $data): void {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->challengeid = $this->get_new_parentid('uckkchallenge');

        if (isset($data->createdby)) {
            $data->createdby = $this->get_mappingid('user', $data->createdby, 0);
        }

        if (isset($data->modifiedby)) {
            $data->modifiedby = $this->get_mappingid('user', $data->modifiedby, 0);
        }

        if (!isset($data->timecreated)) {
            $data->timecreated = time();
        }

        $data->timemodified = time();

        $newitemid = $DB->insert_record('uckkchallenge_rule', $data);

        $this->set_mapping('uckkchallenge_rule', $oldid, $newitemid, true);
    }

    /**
     * Restore a challenge corridor of action.
     *
     * @param array $data Restored XML data.
     */
    protected function process_uckkchallenge_corr(array $data): void {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->challengeid = $this->get_new_parentid('uckkchallenge');

        if (isset($data->createdby)) {
            $data->createdby = $this->get_mappingid('user', $data->createdby, 0);
        }

        if (isset($data->modifiedby)) {
            $data->modifiedby = $this->get_mappingid('user', $data->modifiedby, 0);
        }

        if (!isset($data->timecreated)) {
            $data->timecreated = time();
        }

        $data->timemodified = time();

        $newitemid = $DB->insert_record('uckkchallenge_corr', $data);

        $this->set_mapping('uckkchallenge_corr', $oldid, $newitemid, true);
    }

    /**
     * Restore a challenge submission.
     *
     * @param array $data Restored XML data.
     */
    protected function process_uckkchallenge_sub(array $data): void {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->challengeid = $this->get_new_parentid('uckkchallenge');

        if (isset($data->userid)) {
            $data->userid = $this->get_mappingid('user', $data->userid, 0);
        }

        if (isset($data->groupid) && !empty($data->groupid)) {
            $data->groupid = $this->get_mappingid('group', $data->groupid, 0);
        }

        if (isset($data->createdby)) {
            $data->createdby = $this->get_mappingid('user', $data->createdby, 0);
        }

        if (isset($data->modifiedby)) {
            $data->modifiedby = $this->get_mappingid('user', $data->modifiedby, 0);
        }

        if (isset($data->integritycaseid) && !empty($data->integritycaseid)) {
            // Integrity cases are owned by tool_uckkintegrity. Keep a zero value unless
            // that tool establishes a matching restore mapping later.
            $data->integritycaseid = $this->get_mappingid('tool_uckkintegrity_case', $data->integritycaseid, 0);
        }

        if (isset($data->archiveitemid) && !empty($data->archiveitemid)) {
            // Archive items are owned by mod_uckkarchive. Keep a zero value unless
            // that plugin establishes a matching restore mapping later.
            $data->archiveitemid = $this->get_mappingid('uckkarchive_item', $data->archiveitemid, 0);
        }

        if (!isset($data->timecreated)) {
            $data->timecreated = time();
        }

        $data->timemodified = time();

        $newitemid = $DB->insert_record('uckkchallenge_sub', $data);

        $this->set_mapping('uckkchallenge_sub', $oldid, $newitemid, true);
    }

    /**
     * Restore a proof record.
     *
     * @param array $data Restored XML data.
     */
    protected function process_uckkchallenge_proof(array $data): void {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->challengeid = $this->get_new_parentid('uckkchallenge');

        if (isset($data->subid)) {
            $data->subid = $this->get_mappingid('uckkchallenge_sub', $data->subid, 0);
        }

        if (isset($data->submissionid)) {
            $data->submissionid = $this->get_mappingid('uckkchallenge_sub', $data->submissionid, 0);
        }

        if (isset($data->userid)) {
            $data->userid = $this->get_mappingid('user', $data->userid, 0);
        }

        if (isset($data->createdby)) {
            $data->createdby = $this->get_mappingid('user', $data->createdby, 0);
        }

        if (isset($data->modifiedby)) {
            $data->modifiedby = $this->get_mappingid('user', $data->modifiedby, 0);
        }

        if (!isset($data->timecreated)) {
            $data->timecreated = time();
        }

        $data->timemodified = time();

        $newitemid = $DB->insert_record('uckkchallenge_proof', $data);

        $this->set_mapping('uckkchallenge_proof', $oldid, $newitemid, true);
    }

    /**
     * Restore a proof evaluation.
     *
     * @param array $data Restored XML data.
     */
    protected function process_uckkchallenge_eval(array $data): void {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->challengeid = $this->get_new_parentid('uckkchallenge');

        if (isset($data->subid)) {
            $data->subid = $this->get_mappingid('uckkchallenge_sub', $data->subid, 0);
        }

        if (isset($data->submissionid)) {
            $data->submissionid = $this->get_mappingid('uckkchallenge_sub', $data->submissionid, 0);
        }

        if (isset($data->proofid)) {
            $data->proofid = $this->get_mappingid('uckkchallenge_proof', $data->proofid, 0);
        }

        if (isset($data->userid)) {
            $data->userid = $this->get_mappingid('user', $data->userid, 0);
        }

        if (isset($data->evaluatorid)) {
            $data->evaluatorid = $this->get_mappingid('user', $data->evaluatorid, 0);
        }

        if (isset($data->mentorid)) {
            $data->mentorid = $this->get_mappingid('user', $data->mentorid, 0);
        }

        if (isset($data->createdby)) {
            $data->createdby = $this->get_mappingid('user', $data->createdby, 0);
        }

        if (isset($data->modifiedby)) {
            $data->modifiedby = $this->get_mappingid('user', $data->modifiedby, 0);
        }

        if (!isset($data->timecreated)) {
            $data->timecreated = time();
        }

        $data->timemodified = time();

        $newitemid = $DB->insert_record('uckkchallenge_eval', $data);

        $this->set_mapping('uckkchallenge_eval', $oldid, $newitemid, true);
    }

    /**
     * Restore a challenge state/history record.
     *
     * @param array $data Restored XML data.
     */
    protected function process_uckkchallenge_state(array $data): void {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->challengeid = $this->get_new_parentid('uckkchallenge');

        if (isset($data->subid)) {
            $data->subid = $this->get_mappingid('uckkchallenge_sub', $data->subid, 0);
        }

        if (isset($data->submissionid)) {
            $data->submissionid = $this->get_mappingid('uckkchallenge_sub', $data->submissionid, 0);
        }

        if (isset($data->userid)) {
            $data->userid = $this->get_mappingid('user', $data->userid, 0);
        }

        if (isset($data->actorid)) {
            $data->actorid = $this->get_mappingid('user', $data->actorid, 0);
        }

        if (isset($data->createdby)) {
            $data->createdby = $this->get_mappingid('user', $data->createdby, 0);
        }

        if (isset($data->modifiedby)) {
            $data->modifiedby = $this->get_mappingid('user', $data->modifiedby, 0);
        }

        if (isset($data->integritycaseid) && !empty($data->integritycaseid)) {
            $data->integritycaseid = $this->get_mappingid('tool_uckkintegrity_case', $data->integritycaseid, 0);
        }

        if (isset($data->archiveitemid) && !empty($data->archiveitemid)) {
            $data->archiveitemid = $this->get_mappingid('uckkarchive_item', $data->archiveitemid, 0);
        }

        if (!isset($data->timecreated)) {
            $data->timecreated = time();
        }

        $data->timemodified = time();

        $newitemid = $DB->insert_record('uckkchallenge_state', $data);

        $this->set_mapping('uckkchallenge_state', $oldid, $newitemid, true);
    }

    /**
     * Restore related files after records have been restored.
     */
    protected function after_execute(): void {
        $this->add_related_files('mod_uckkchallenge', 'intro', null);

        // Canonical file areas from the UCKK data model and current controller code.
        $this->add_related_files('mod_uckkchallenge', 'submission', 'uckkchallenge_sub');
        $this->add_related_files('mod_uckkchallenge', 'submission_text', 'uckkchallenge_sub');
        $this->add_related_files('mod_uckkchallenge', 'proof_files', 'uckkchallenge_proof');
        $this->add_related_files('mod_uckkchallenge', 'feedback', 'uckkchallenge_eval');
    }
}