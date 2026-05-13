<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

/**
 * Restore steps for the UCKK Archive activity.
 *
 * @package    mod_uckkarchive
 * @category   backup
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Restore structure step for Archives UCKK.
 *
 * This restores archive-owned records only:
 * - archive activity configuration;
 * - archive items;
 * - Kristals;
 * - proof records;
 * - provenance records;
 * - revision records;
 * - export package records.
 *
 * It does not restore external challenge, assembly, integrity, report, badge,
 * competency, or seed records. Those are owned by their respective plugins.
 */
class restore_uckkarchive_activity_structure_step extends restore_activity_structure_step {
    /**
     * Define restore structure.
     *
     * @return restore_path_element[]
     */
    protected function define_structure(): array {
        $paths = [];
        $userinfo = $this->get_setting_value('userinfo');

        $paths[] = new restore_path_element('uckkarchive', '/activity/uckkarchive');

        if ($userinfo) {
            $paths[] = new restore_path_element(
                'uckkarchive_item',
                '/activity/uckkarchive/items/item'
            );

            $paths[] = new restore_path_element(
                'uckkarchive_kristal',
                '/activity/uckkarchive/items/item/kristals/kristal'
            );

            $paths[] = new restore_path_element(
                'uckkarchive_proof',
                '/activity/uckkarchive/items/item/proofs/proof'
            );

            $paths[] = new restore_path_element(
                'uckkarchive_prov',
                '/activity/uckkarchive/items/item/provenance/prov'
            );

            $paths[] = new restore_path_element(
                'uckkarchive_rev',
                '/activity/uckkarchive/items/item/revisions/revision'
            );

            $paths[] = new restore_path_element(
                'uckkarchive_export',
                '/activity/uckkarchive/exports/export'
            );
        }

        return $this->prepare_activity_structure($paths);
    }

    /**
     * Restore the main archive activity record.
     *
     * @param array $data Backup data.
     */
    protected function process_uckkarchive($data): void {
        global $DB;

        $data = (object)$data;
        $oldid = (int)$data->id;

        unset($data->id);

        $data->course = $this->get_courseid();

        $this->map_common_restore_fields($data, false);
        $this->offset_common_dates($data);

        $newitemid = $DB->insert_record('uckkarchive', $data);

        $this->apply_activity_instance($newitemid);
        $this->set_mapping('uckkarchive', $oldid, $newitemid);
    }

    /**
     * Restore an archive item.
     *
     * @param array $data Backup data.
     */
    protected function process_uckkarchive_item($data): void {
        global $DB;

        $data = (object)$data;
        $oldid = (int)$data->id;

        unset($data->id);

        $data->archiveid = $this->get_new_parentid('uckkarchive');
        $data->courseid = $this->get_courseid();

        $this->map_common_restore_fields($data);
        $this->map_subject_reference($data);
        $this->offset_common_dates($data);

        $newitemid = $DB->insert_record('uckkarchive_item', $data);

        $this->set_mapping('uckkarchive_item', $oldid, $newitemid, true);
    }

    /**
     * Restore a Kristal record.
     *
     * @param array $data Backup data.
     */
    protected function process_uckkarchive_kristal($data): void {
        global $DB;

        $data = (object)$data;
        $oldid = (int)$data->id;

        unset($data->id);

        $data->archiveid = $this->get_new_parentid('uckkarchive');
        $data->itemid = $this->get_new_parentid('uckkarchive_item');
        $data->courseid = $this->get_courseid();

        $this->map_common_restore_fields($data);
        $this->map_source_reference($data);
        $this->offset_common_dates($data);

        $newitemid = $DB->insert_record('uckkarchive_kristal', $data);

        $this->set_mapping('uckkarchive_kristal', $oldid, $newitemid, true);
    }

    /**
     * Restore a proof record.
     *
     * @param array $data Backup data.
     */
    protected function process_uckkarchive_proof($data): void {
        global $DB;

        $data = (object)$data;
        $oldid = (int)$data->id;

        unset($data->id);

        $data->archiveid = $this->get_new_parentid('uckkarchive');
        $data->itemid = $this->get_new_parentid('uckkarchive_item');
        $data->courseid = $this->get_courseid();

        $this->map_common_restore_fields($data);
        $this->map_source_reference($data);
        $this->offset_common_dates($data);

        $newitemid = $DB->insert_record('uckkarchive_proof', $data);

        $this->set_mapping('uckkarchive_proof', $oldid, $newitemid, true);
    }

    /**
     * Restore a provenance record.
     *
     * @param array $data Backup data.
     */
    protected function process_uckkarchive_prov($data): void {
        global $DB;

        $data = (object)$data;
        $oldid = (int)$data->id;

        unset($data->id);

        $data->archiveid = $this->get_new_parentid('uckkarchive');
        $data->itemid = $this->get_new_parentid('uckkarchive_item');
        $data->courseid = $this->get_courseid();

        $this->map_common_restore_fields($data);
        $this->map_source_reference($data);
        $this->offset_common_dates($data);

        $newitemid = $DB->insert_record('uckkarchive_prov', $data);

        $this->set_mapping('uckkarchive_prov', $oldid, $newitemid);
    }

    /**
     * Restore a revision record.
     *
     * @param array $data Backup data.
     */
    protected function process_uckkarchive_rev($data): void {
        global $DB;

        $data = (object)$data;
        $oldid = (int)$data->id;

        unset($data->id);

        $data->archiveid = $this->get_new_parentid('uckkarchive');
        $data->itemid = $this->get_new_parentid('uckkarchive_item');
        $data->courseid = $this->get_courseid();

        if (!empty($data->kristalid)) {
            $data->kristalid = $this->get_mappingid('uckkarchive_kristal', $data->kristalid, 0);
        }

        if (!empty($data->proofid)) {
            $data->proofid = $this->get_mappingid('uckkarchive_proof', $data->proofid, 0);
        }

        if (!empty($data->previd)) {
            $data->previd = $this->get_mappingid('uckkarchive_rev', $data->previd, 0);
        }

        $this->map_common_restore_fields($data);
        $this->offset_common_dates($data);

        $newitemid = $DB->insert_record('uckkarchive_rev', $data);

        $this->set_mapping('uckkarchive_rev', $oldid, $newitemid, true);
    }

    /**
     * Restore an archive export record.
     *
     * @param array $data Backup data.
     */
    protected function process_uckkarchive_export($data): void {
        global $DB;

        $data = (object)$data;
        $oldid = (int)$data->id;

        unset($data->id);

        $data->archiveid = $this->get_new_parentid('uckkarchive');
        $data->courseid = $this->get_courseid();

        if (!empty($data->itemid)) {
            $data->itemid = $this->get_mappingid('uckkarchive_item', $data->itemid, 0);
        }

        if (!empty($data->kristalid)) {
            $data->kristalid = $this->get_mappingid('uckkarchive_kristal', $data->kristalid, 0);
        }

        $this->map_common_restore_fields($data);
        $this->map_source_reference($data);
        $this->offset_common_dates($data);

        $newitemid = $DB->insert_record('uckkarchive_export', $data);

        $this->set_mapping('uckkarchive_export', $oldid, $newitemid, true);
    }

    /**
     * Restore related file areas after all records have been restored.
     */
    protected function after_execute(): void {
        $this->add_related_files('mod_uckkarchive', 'intro', null);

        // Archive-level file areas.
        $this->add_related_files('mod_uckkarchive', 'archive_attachments', null);

        // Archive item file areas.
        $this->add_related_files('mod_uckkarchive', 'item_content', 'uckkarchive_item');
        $this->add_related_files('mod_uckkarchive', 'item_attachments', 'uckkarchive_item');

        // Kristal file areas.
        $this->add_related_files('mod_uckkarchive', 'kristal_content', 'uckkarchive_kristal');
        $this->add_related_files('mod_uckkarchive', 'kristal_attachments', 'uckkarchive_kristal');

        // Proof file areas.
        $this->add_related_files('mod_uckkarchive', 'proof_content', 'uckkarchive_proof');
        $this->add_related_files('mod_uckkarchive', 'proof_files', 'uckkarchive_proof');
        $this->add_related_files('mod_uckkarchive', 'proof_attachments', 'uckkarchive_proof');

        // Provenance, revision, and export file areas.
        $this->add_related_files('mod_uckkarchive', 'provenance_attachments', 'uckkarchive_prov');
        $this->add_related_files('mod_uckkarchive', 'revision_attachments', 'uckkarchive_rev');
        $this->add_related_files('mod_uckkarchive', 'export_files', 'uckkarchive_export');
    }

    /**
     * Map common user/context/course references.
     *
     * @param stdClass $data Restore record.
     * @param bool $hascontext Whether the table owns a contextid field.
     */
    private function map_common_restore_fields(stdClass $data, bool $hascontext = true): void {
        if (property_exists($data, 'courseid')) {
            $data->courseid = $this->get_courseid();
        }

        if ($hascontext && property_exists($data, 'contextid')) {
            $data->contextid = $this->map_contextid((int)$data->contextid);
        }

        foreach ($this->get_user_fields() as $field) {
            if (property_exists($data, $field) && !empty($data->{$field})) {
                $data->{$field} = $this->get_mappingid('user', $data->{$field}, $data->{$field});
            }
        }

        if (property_exists($data, 'cmid')) {
            $data->cmid = $this->task->get_moduleid();
        }
    }

    /**
     * Map source component/id references when the source belongs to this restore.
     *
     * External sources are preserved as references and not duplicated.
     *
     * @param stdClass $data Restore record.
     */
    private function map_source_reference(stdClass $data): void {
        if (empty($data->sourcecomponent) || empty($data->sourceid)) {
            return;
        }

        switch ($data->sourcecomponent) {
            case 'mod_uckkarchive:item':
            case 'mod_uckkarchive:archive_item':
            case 'uckkarchive_item':
                $data->sourceid = $this->get_mappingid('uckkarchive_item', $data->sourceid, $data->sourceid);
                break;

            case 'mod_uckkarchive:kristal':
            case 'uckkarchive_kristal':
                $data->sourceid = $this->get_mappingid('uckkarchive_kristal', $data->sourceid, $data->sourceid);
                break;

            case 'mod_uckkarchive:proof':
            case 'uckkarchive_proof':
                $data->sourceid = $this->get_mappingid('uckkarchive_proof', $data->sourceid, $data->sourceid);
                break;

            case 'mod_uckkarchive:provenance':
            case 'uckkarchive_prov':
                $data->sourceid = $this->get_mappingid('uckkarchive_prov', $data->sourceid, $data->sourceid);
                break;

            case 'mod_uckkarchive:revision':
            case 'uckkarchive_rev':
                $data->sourceid = $this->get_mappingid('uckkarchive_rev', $data->sourceid, $data->sourceid);
                break;
        }
    }

    /**
     * Map subject component/id references when they point to restored archive data.
     *
     * @param stdClass $data Restore record.
     */
    private function map_subject_reference(stdClass $data): void {
        if (empty($data->subjectcomponent) || empty($data->subjectid)) {
            return;
        }

        switch ($data->subjectcomponent) {
            case 'mod_uckkarchive:item':
            case 'mod_uckkarchive:archive_item':
            case 'uckkarchive_item':
                $data->subjectid = $this->get_mappingid('uckkarchive_item', $data->subjectid, $data->subjectid);
                break;

            case 'mod_uckkarchive:kristal':
            case 'uckkarchive_kristal':
                $data->subjectid = $this->get_mappingid('uckkarchive_kristal', $data->subjectid, $data->subjectid);
                break;

            case 'mod_uckkarchive:proof':
            case 'uckkarchive_proof':
                $data->subjectid = $this->get_mappingid('uckkarchive_proof', $data->subjectid, $data->subjectid);
                break;

            case 'mod_uckkarchive:provenance':
            case 'uckkarchive_prov':
                $data->subjectid = $this->get_mappingid('uckkarchive_prov', $data->subjectid, $data->subjectid);
                break;

            case 'mod_uckkarchive:revision':
            case 'uckkarchive_rev':
                $data->subjectid = $this->get_mappingid('uckkarchive_rev', $data->subjectid, $data->subjectid);
                break;
        }
    }

    /**
     * Offset common date fields.
     *
     * @param stdClass $data Restore record.
     */
    private function offset_common_dates(stdClass $data): void {
        foreach ($this->get_date_fields() as $field) {
            if (property_exists($data, $field) && !empty($data->{$field})) {
                $data->{$field} = $this->apply_date_offset($data->{$field});
            }
        }
    }

    /**
     * Map an old context id to the restored context id when possible.
     *
     * @param int $contextid Original context id.
     * @return int Restored context id.
     */
    private function map_contextid(int $contextid): int {
        if ($contextid <= 0) {
            return \context_module::instance($this->task->get_moduleid())->id;
        }

        return $this->get_mappingid(
            'context',
            $contextid,
            \context_module::instance($this->task->get_moduleid())->id
        );
    }

    /**
     * User reference fields used across archive tables.
     *
     * @return string[]
     */
    private function get_user_fields(): array {
        return [
            'userid',
            'authorid',
            'ownerid',
            'submittedby',
            'validatedby',
            'reviewedby',
            'revisedby',
            'exportedby',
            'publishedby',
            'createdby',
            'modifiedby',
        ];
    }

    /**
     * Date fields used across archive tables.
     *
     * @return string[]
     */
    private function get_date_fields(): array {
        return [
            'timeopen',
            'timeclose',
            'sourcedate',
            'validatedtime',
            'reviewedtime',
            'revisedtime',
            'exportedtime',
            'publishedtime',
            'timecreated',
            'timemodified',
        ];
    }
}

