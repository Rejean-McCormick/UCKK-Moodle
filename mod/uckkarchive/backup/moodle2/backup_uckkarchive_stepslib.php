<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

/**
 * Backup structure step for UCKK Archive.
 *
 * @package    mod_uckkarchive
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Defines the complete backup structure for one UCKK Archive activity.
 *
 * The structure keeps archive memory, provenance, proof records, Kristals,
 * revisions, and export records together under the restored activity instance.
 * User-created evidence and audit records are included only when Moodle's
 * standard activity user-data setting is enabled.
 */
class backup_uckkarchive_activity_structure_step extends backup_activity_structure_step {
    /**
     * Define the backup structure.
     *
     * @return backup_nested_element
     */
    protected function define_structure() {
        $userinfo = $this->get_setting_value('userinfo');

        $archive = new backup_nested_element('uckkarchive', ['id'], [
            'course',
            'cmid',
            'contextid',
            'name',
            'intro',
            'introformat',
            'archivepolicy',
            'defaultvisibility',
            'allowpublicitems',
            'allowstudentitems',
            'requirevalidation',
            'enablekristals',
            'enableexports',
            'status',
            'visibility',
            'provenance',
            'versionno',
            'createdby',
            'modifiedby',
            'timecreated',
            'timemodified',
            'metadata',
        ]);

        $items = new backup_nested_element('items');

        $item = new backup_nested_element('item', ['id'], [
            'archiveid',
            'courseid',
            'cmid',
            'contextid',
            'userid',
            'itemtype',
            'title',
            'content',
            'contentformat',
            'publicsummary',
            'publicsummaryformat',
            'sourceurl',
            'sourcetitle',
            'sourceauthor',
            'sourcedate',
            'origincomponent',
            'originarea',
            'originid',
            'status',
            'visibility',
            'validationstate',
            'provenance',
            'provenancehash',
            'versionno',
            'createdby',
            'modifiedby',
            'timecreated',
            'timemodified',
            'metadata',
        ]);

        $kristals = new backup_nested_element('kristals');

        $kristal = new backup_nested_element('kristal', ['id'], [
            'archiveid',
            'itemid',
            'courseid',
            'cmid',
            'contextid',
            'userid',
            'shortname',
            'title',
            'content',
            'contentformat',
            'summary',
            'summaryformat',
            'concepts',
            'relations',
            'sourceurl',
            'sourcetitle',
            'sourceauthor',
            'sourcedate',
            'status',
            'visibility',
            'validationstate',
            'provenance',
            'provenancehash',
            'versionno',
            'createdby',
            'modifiedby',
            'timecreated',
            'timemodified',
            'metadata',
        ]);

        $proofs = new backup_nested_element('proofs');

        $proof = new backup_nested_element('proof', ['id'], [
            'archiveid',
            'itemid',
            'courseid',
            'cmid',
            'contextid',
            'userid',
            'prooftype',
            'title',
            'description',
            'descriptionformat',
            'sourceurl',
            'sourcetitle',
            'sourceauthor',
            'sourcedate',
            'status',
            'visibility',
            'validationstate',
            'provenance',
            'provenancehash',
            'versionno',
            'createdby',
            'modifiedby',
            'timecreated',
            'timemodified',
            'metadata',
        ]);

        $provenances = new backup_nested_element('provenances');

        $provenance = new backup_nested_element('provenance', ['id'], [
            'archiveid',
            'itemid',
            'proofid',
            'kristalid',
            'courseid',
            'cmid',
            'contextid',
            'userid',
            'sourcecomponent',
            'sourcearea',
            'sourceid',
            'sourcetype',
            'sourcetitle',
            'sourceurl',
            'sourceauthor',
            'sourcedate',
            'statement',
            'statementformat',
            'status',
            'visibility',
            'validationstate',
            'provenance',
            'provenancehash',
            'versionno',
            'createdby',
            'modifiedby',
            'timecreated',
            'timemodified',
            'metadata',
        ]);

        $revisions = new backup_nested_element('revisions');

        $revision = new backup_nested_element('revision', ['id'], [
            'archiveid',
            'itemid',
            'courseid',
            'cmid',
            'contextid',
            'userid',
            'revisiontype',
            'previousstatus',
            'newstatus',
            'previousvisibility',
            'newvisibility',
            'previousvalidationstate',
            'newvalidationstate',
            'reason',
            'status',
            'visibility',
            'provenance',
            'provenancehash',
            'versionno',
            'createdby',
            'modifiedby',
            'timecreated',
            'timemodified',
            'metadata',
        ]);

        $exports = new backup_nested_element('exports');

        $export = new backup_nested_element('export', ['id'], [
            'archiveid',
            'itemid',
            'courseid',
            'cmid',
            'contextid',
            'userid',
            'exporttype',
            'exportformat',
            'title',
            'summary',
            'summaryformat',
            'status',
            'visibility',
            'validationstate',
            'provenance',
            'provenancehash',
            'versionno',
            'createdby',
            'modifiedby',
            'timecreated',
            'timemodified',
            'metadata',
        ]);

        $archive->add_child($items);
        $items->add_child($item);

        $archive->add_child($kristals);
        $kristals->add_child($kristal);

        $item->add_child($proofs);
        $proofs->add_child($proof);

        $item->add_child($provenances);
        $provenances->add_child($provenance);

        $item->add_child($revisions);
        $revisions->add_child($revision);

        $item->add_child($exports);
        $exports->add_child($export);

        $archive->set_source_table('uckkarchive', [
            'id' => backup::VAR_ACTIVITYID,
        ]);

        if ($userinfo) {
            $item->set_source_table('uckkarchive_item', [
                'archiveid' => backup::VAR_PARENTID,
            ]);

            $kristal->set_source_table('uckkarchive_kristal', [
                'archiveid' => backup::VAR_PARENTID,
            ]);

            $proof->set_source_table('uckkarchive_proof', [
                'itemid' => backup::VAR_PARENTID,
            ]);

            $provenance->set_source_table('uckkarchive_prov', [
                'itemid' => backup::VAR_PARENTID,
            ]);

            $revision->set_source_table('uckkarchive_rev', [
                'itemid' => backup::VAR_PARENTID,
            ]);

            $export->set_source_table('uckkarchive_export', [
                'itemid' => backup::VAR_PARENTID,
            ]);
        }

        $archive->annotate_ids('course', 'course');
        $archive->annotate_ids('context', 'contextid');
        $archive->annotate_ids('user', 'createdby');
        $archive->annotate_ids('user', 'modifiedby');

        if ($userinfo) {
            $item->annotate_ids('course', 'courseid');
            $item->annotate_ids('context', 'contextid');
            $item->annotate_ids('user', 'userid');
            $item->annotate_ids('user', 'createdby');
            $item->annotate_ids('user', 'modifiedby');

            $kristal->annotate_ids('course', 'courseid');
            $kristal->annotate_ids('context', 'contextid');
            $kristal->annotate_ids('user', 'userid');
            $kristal->annotate_ids('user', 'createdby');
            $kristal->annotate_ids('user', 'modifiedby');

            $proof->annotate_ids('course', 'courseid');
            $proof->annotate_ids('context', 'contextid');
            $proof->annotate_ids('user', 'userid');
            $proof->annotate_ids('user', 'createdby');
            $proof->annotate_ids('user', 'modifiedby');

            $provenance->annotate_ids('course', 'courseid');
            $provenance->annotate_ids('context', 'contextid');
            $provenance->annotate_ids('user', 'userid');
            $provenance->annotate_ids('user', 'createdby');
            $provenance->annotate_ids('user', 'modifiedby');

            $revision->annotate_ids('course', 'courseid');
            $revision->annotate_ids('context', 'contextid');
            $revision->annotate_ids('user', 'userid');
            $revision->annotate_ids('user', 'createdby');
            $revision->annotate_ids('user', 'modifiedby');

            $export->annotate_ids('course', 'courseid');
            $export->annotate_ids('context', 'contextid');
            $export->annotate_ids('user', 'userid');
            $export->annotate_ids('user', 'createdby');
            $export->annotate_ids('user', 'modifiedby');
        }

        $archive->annotate_files('mod_uckkarchive', 'intro', null);

        if ($userinfo) {
            $item->annotate_files('mod_uckkarchive', 'item_content', 'id');
            $item->annotate_files('mod_uckkarchive', 'public_summary', 'id');
            $item->annotate_files('mod_uckkarchive', 'item_files', 'id');
            $item->annotate_files('mod_uckkarchive', 'proof_files', 'id');

            $kristal->annotate_files('mod_uckkarchive', 'kristal_content', 'id');
            $kristal->annotate_files('mod_uckkarchive', 'kristal_summary', 'id');
            $kristal->annotate_files('mod_uckkarchive', 'kristal_files', 'id');

            $proof->annotate_files('mod_uckkarchive', 'proof_description', 'id');
            $proof->annotate_files('mod_uckkarchive', 'proof_files', 'id');

            $provenance->annotate_files('mod_uckkarchive', 'provenance_statement', 'id');

            $export->annotate_files('mod_uckkarchive', 'export_files', 'id');
        }

        return $this->prepare_activity_structure($archive);
    }
}

