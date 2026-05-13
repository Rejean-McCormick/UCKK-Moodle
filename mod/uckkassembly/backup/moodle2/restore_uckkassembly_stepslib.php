<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

/**
 * Restore steps for UCKK Assemblies.
 *
 * @package    mod_uckkassembly
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Restore structure step for mod_uckkassembly.
 *
 * This class restores the Assembly activity instance and its dependent
 * governance records. It must restore records and mappings only. It must not
 * re-publish decisions, re-open votes, re-run integrity workflow, or create new
 * archive exports as a side effect of restore.
 */
class restore_uckkassembly_activity_structure_step extends restore_activity_structure_step {
    /**
     * Deferred motion -> decision links.
     *
     * Motions may reference decisions, but decisions are restored after motions.
     * These links are resolved in after_execute().
     *
     * @var array<int, int>
     */
    protected array $motiondecisionlinks = [];

    /**
     * Define restore structure.
     *
     * @return restore_path_element[]
     */
    protected function define_structure(): array {
        $paths = [];

        $userinfo = $this->get_setting_value('userinfo');

        $paths[] = new restore_path_element('uckkassembly', '/activity/uckkassembly');

        if ($userinfo) {
            $paths[] = new restore_path_element('uckkassembly_motion', '/activity/uckkassembly/motions/motion');
            $paths[] = new restore_path_element('uckkassembly_amend', '/activity/uckkassembly/amendments/amendment');
            $paths[] = new restore_path_element('uckkassembly_object', '/activity/uckkassembly/objections/objection');
            $paths[] = new restore_path_element('uckkassembly_vote', '/activity/uckkassembly/votes/vote');
            $paths[] = new restore_path_element('uckkassembly_decision', '/activity/uckkassembly/decisions/decision');
            $paths[] = new restore_path_element('uckkassembly_minutes', '/activity/uckkassembly/minutesrecords/minutes');
            $paths[] = new restore_path_element('uckkassembly_contest', '/activity/uckkassembly/contests/contest');
        }

        return $this->prepare_activity_structure($paths);
    }

    /**
     * Restore main Assembly instance.
     *
     * @param array|stdClass $data Restored data.
     */
    protected function process_uckkassembly($data): void {
        global $DB;

        $data = (object)$data;
        $oldid = (int)$data->id;

        $data->course = $this->get_courseid();

        $this->offset_time_fields($data, [
            'timecreated',
            'timemodified',
            'timeopen',
            'timeclose',
            'contestableuntil',
            'timearchived',
        ]);

        $this->map_optional_user_fields($data, [
            'createdby',
            'modifiedby',
            'facilitatorid',
            'archivedby',
        ]);

        unset($data->id);

        $newitemid = $DB->insert_record('uckkassembly', $data);

        $this->apply_activity_instance($newitemid);
        $this->set_mapping('uckkassembly', $oldid, $newitemid, true);
    }

    /**
     * Restore one Assembly motion.
     *
     * @param array|stdClass $data Restored data.
     */
    protected function process_uckkassembly_motion($data): void {
        global $DB;

        $data = (object)$data;
        $oldid = (int)$data->id;

        $data->assemblyid = $this->get_mappingid('uckkassembly', (int)$data->assemblyid);

        if (!empty($data->decisionid)) {
            $this->motiondecisionlinks[$oldid] = (int)$data->decisionid;
            $data->decisionid = null;
        }

        $this->offset_time_fields($data, [
            'timecreated',
            'timemodified',
            'timeopen',
            'timeclose',
            'timepublished',
            'timearchived',
        ]);

        $this->map_optional_user_fields($data, [
            'userid',
            'proposerid',
            'createdby',
            'modifiedby',
            'publishedby',
            'archivedby',
        ]);

        unset($data->id);

        $newitemid = $DB->insert_record('uckkassembly_motion', $data);

        $this->set_mapping('uckkassembly_motion', $oldid, $newitemid, true);
    }

    /**
     * Restore one amendment.
     *
     * @param array|stdClass $data Restored data.
     */
    protected function process_uckkassembly_amend($data): void {
        global $DB;

        $data = (object)$data;
        $oldid = (int)$data->id;

        $data->assemblyid = $this->get_mappingid('uckkassembly', (int)$data->assemblyid);

        if (!empty($data->motionid)) {
            $data->motionid = $this->get_mappingid('uckkassembly_motion', (int)$data->motionid);
        }

        $this->offset_time_fields($data, [
            'timecreated',
            'timemodified',
            'timeaccepted',
            'timerejected',
        ]);

        $this->map_optional_user_fields($data, [
            'userid',
            'proposerid',
            'createdby',
            'modifiedby',
            'acceptedby',
            'rejectedby',
        ]);

        unset($data->id);

        $newitemid = $DB->insert_record('uckkassembly_amend', $data);

        $this->set_mapping('uckkassembly_amend', $oldid, $newitemid, true);
    }

    /**
     * Restore one objection.
     *
     * @param array|stdClass $data Restored data.
     */
    protected function process_uckkassembly_object($data): void {
        global $DB;

        $data = (object)$data;
        $oldid = (int)$data->id;

        $data->assemblyid = $this->get_mappingid('uckkassembly', (int)$data->assemblyid);

        if (!empty($data->motionid)) {
            $data->motionid = $this->get_mappingid('uckkassembly_motion', (int)$data->motionid);
        }

        if (!empty($data->amendid)) {
            $data->amendid = $this->get_mappingid('uckkassembly_amend', (int)$data->amendid);
        }

        $this->offset_time_fields($data, [
            'timecreated',
            'timemodified',
            'timeresolved',
        ]);

        $this->map_optional_user_fields($data, [
            'userid',
            'objectorid',
            'createdby',
            'modifiedby',
            'resolvedby',
        ]);

        unset($data->id);

        $newitemid = $DB->insert_record('uckkassembly_object', $data);

        $this->set_mapping('uckkassembly_object', $oldid, $newitemid, true);
    }

    /**
     * Restore one vote.
     *
     * @param array|stdClass $data Restored data.
     */
    protected function process_uckkassembly_vote($data): void {
        global $DB;

        $data = (object)$data;
        $oldid = (int)$data->id;

        $data->assemblyid = $this->get_mappingid('uckkassembly', (int)$data->assemblyid);

        if (!empty($data->motionid)) {
            $data->motionid = $this->get_mappingid('uckkassembly_motion', (int)$data->motionid);
        }

        if (!empty($data->decisionid)) {
            $mappeddecisionid = $this->get_mappingid('uckkassembly_decision', (int)$data->decisionid, 0);
            $data->decisionid = $mappeddecisionid ?: null;
        }

        $this->offset_time_fields($data, [
            'timecreated',
            'timemodified',
            'timevoted',
        ]);

        $this->map_optional_user_fields($data, [
            'userid',
            'voterid',
            'createdby',
            'modifiedby',
        ]);

        unset($data->id);

        $newitemid = $DB->insert_record('uckkassembly_vote', $data);

        $this->set_mapping('uckkassembly_vote', $oldid, $newitemid, true);
    }

    /**
     * Restore one decision.
     *
     * @param array|stdClass $data Restored data.
     */
    protected function process_uckkassembly_decision($data): void {
        global $DB;

        $data = (object)$data;
        $oldid = (int)$data->id;

        $data->assemblyid = $this->get_mappingid('uckkassembly', (int)$data->assemblyid);

        if (!empty($data->motionid)) {
            $data->motionid = $this->get_mappingid('uckkassembly_motion', (int)$data->motionid);
        }

        // Archive items may come from another restored plugin. Keep the field empty
        // unless the archive mapping exists in this restore plan.
        if (!empty($data->archiveitemid)) {
            $archiveitemid = $this->get_mappingid('uckkarchive_item', (int)$data->archiveitemid, 0);
            $data->archiveitemid = $archiveitemid ?: null;
        }

        $this->offset_time_fields($data, [
            'timecreated',
            'timemodified',
            'timepublished',
            'contestableuntil',
            'timearchived',
        ]);

        $this->map_optional_user_fields($data, [
            'userid',
            'createdby',
            'modifiedby',
            'publishedby',
            'archivedby',
        ]);

        unset($data->id);

        $newitemid = $DB->insert_record('uckkassembly_decision', $data);

        $this->set_mapping('uckkassembly_decision', $oldid, $newitemid, true);
    }

    /**
     * Restore one minutes/procès-verbal record.
     *
     * @param array|stdClass $data Restored data.
     */
    protected function process_uckkassembly_minutes($data): void {
        global $DB;

        $data = (object)$data;
        $oldid = (int)$data->id;

        $data->assemblyid = $this->get_mappingid('uckkassembly', (int)$data->assemblyid);

        if (!empty($data->motionid)) {
            $data->motionid = $this->get_mappingid('uckkassembly_motion', (int)$data->motionid);
        }

        if (!empty($data->decisionid)) {
            $data->decisionid = $this->get_mappingid('uckkassembly_decision', (int)$data->decisionid);
        }

        $this->offset_time_fields($data, [
            'timecreated',
            'timemodified',
            'timepublished',
            'timearchived',
        ]);

        $this->map_optional_user_fields($data, [
            'userid',
            'createdby',
            'modifiedby',
            'publishedby',
            'archivedby',
        ]);

        unset($data->id);

        $newitemid = $DB->insert_record('uckkassembly_minutes', $data);

        $this->set_mapping('uckkassembly_minutes', $oldid, $newitemid, true);
    }

    /**
     * Restore one contestation.
     *
     * @param array|stdClass $data Restored data.
     */
    protected function process_uckkassembly_contest($data): void {
        global $DB;

        $data = (object)$data;
        $oldid = (int)$data->id;

        $data->assemblyid = $this->get_mappingid('uckkassembly', (int)$data->assemblyid);

        if (!empty($data->motionid)) {
            $data->motionid = $this->get_mappingid('uckkassembly_motion', (int)$data->motionid);
        }

        if (!empty($data->decisionid)) {
            $data->decisionid = $this->get_mappingid('uckkassembly_decision', (int)$data->decisionid);
        }

        if (!empty($data->integritycaseid)) {
            $integritycaseid = $this->get_mappingid('tool_uckkintegrity_case', (int)$data->integritycaseid, 0);
            $data->integritycaseid = $integritycaseid ?: null;
        }

        $this->offset_time_fields($data, [
            'timecreated',
            'timemodified',
            'timeresolved',
            'timeclosed',
        ]);

        $this->map_optional_user_fields($data, [
            'userid',
            'contestantid',
            'createdby',
            'modifiedby',
            'resolvedby',
            'closedby',
        ]);

        unset($data->id);

        $newitemid = $DB->insert_record('uckkassembly_contest', $data);

        $this->set_mapping('uckkassembly_contest', $oldid, $newitemid, true);
    }

    /**
     * Post-processing after all records have been inserted.
     */
    protected function after_execute(): void {
        global $DB;

        $this->restore_deferred_motion_decision_links();

        // Main activity intro files.
        $this->add_related_files('mod_uckkassembly', 'intro', null);

        // File areas whose files are linked directly to restored item ids.
        $this->add_related_files('mod_uckkassembly', 'assembly_files', 'uckkassembly');
        $this->add_related_files('mod_uckkassembly', 'motion_attachments', 'uckkassembly_motion');
        $this->add_related_files('mod_uckkassembly', 'amendment_attachments', 'uckkassembly_amend');
        $this->add_related_files('mod_uckkassembly', 'objection_attachments', 'uckkassembly_object');
        $this->add_related_files('mod_uckkassembly', 'decision_attachments', 'uckkassembly_decision');
        $this->add_related_files('mod_uckkassembly', 'minutes_files', 'uckkassembly_minutes');
        $this->add_related_files('mod_uckkassembly', 'contest_attachments', 'uckkassembly_contest');

        // Some legacy/backward-compatible backup plans may use generic names.
        $this->add_related_files('mod_uckkassembly', 'attachments', null);
    }

    /**
     * Restore deferred motion -> decision references.
     */
    protected function restore_deferred_motion_decision_links(): void {
        global $DB;

        foreach ($this->motiondecisionlinks as $oldmotionid => $olddecisionid) {
            $newmotionid = $this->get_mappingid('uckkassembly_motion', $oldmotionid, 0);
            $newdecisionid = $this->get_mappingid('uckkassembly_decision', $olddecisionid, 0);

            if (!$newmotionid || !$newdecisionid) {
                continue;
            }

            $DB->set_field('uckkassembly_motion', 'decisionid', $newdecisionid, [
                'id' => $newmotionid,
            ]);
        }
    }

    /**
     * Apply date offset to existing timestamp fields.
     *
     * @param stdClass $data Record data.
     * @param string[] $fields Field names.
     */
    protected function offset_time_fields(stdClass $data, array $fields): void {
        foreach ($fields as $field) {
            if (property_exists($data, $field) && !empty($data->{$field})) {
                $data->{$field} = $this->apply_date_offset((int)$data->{$field});
            }
        }
    }

    /**
     * Map optional Moodle user id fields.
     *
     * If a user cannot be mapped, the original value is replaced with 0/null
     * depending on the existing field value. This avoids assigning restored
     * governance actions to the wrong local user.
     *
     * @param stdClass $data Record data.
     * @param string[] $fields User id field names.
     */
    protected function map_optional_user_fields(stdClass $data, array $fields): void {
        foreach ($fields as $field) {
            if (!property_exists($data, $field) || empty($data->{$field})) {
                continue;
            }

            $mappeduserid = $this->get_mappingid('user', (int)$data->{$field}, 0);
            $data->{$field} = $mappeduserid ?: 0;
        }
    }
}