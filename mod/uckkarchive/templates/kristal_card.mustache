<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

/**
 * Restore task for the UCKK Archive activity module.
 *
 * @package    mod_uckkarchive
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/uckkarchive/backup/moodle2/restore_uckkarchive_stepslib.php');

/**
 * Restore task for mod_uckkarchive.
 *
 * The task wires the restore structure step, content decoding, activity-link
 * decoding, and legacy log restoration rules. Record creation, id mapping,
 * file-area restoration, provenance reconstruction, revision mapping, and
 * export metadata handling belong in restore_uckkarchive_activity_structure_step.
 */
class restore_uckkarchive_activity_task extends restore_activity_task {
    /**
     * Define activity-specific restore settings.
     *
     * No custom task-level settings are required. User-data inclusion is
     * governed by Moodle's standard activity restore settings and interpreted
     * by the structure step.
     */
    protected function define_my_settings(): void {
    }

    /**
     * Define restore steps.
     */
    protected function define_my_steps(): void {
        $this->add_step(new restore_uckkarchive_activity_structure_step(
            'uckkarchive_structure',
            'uckkarchive.xml'
        ));
    }

    /**
     * Define content fields requiring link decoding after restore.
     *
     * @return array
     */
    public static function define_decode_contents(): array {
        $contents = [];

        // Main archive instance.
        $contents[] = new restore_decode_content('uckkarchive', [
            'intro',
            'summary',
            'policytext',
            'retentionpolicy',
            'metadata',
        ], 'uckkarchive');

        // Archive items.
        $contents[] = new restore_decode_content('uckkarchive_item', [
            'title',
            'summary',
            'content',
            'sourceref',
            'metadata',
        ], 'uckkarchive_item');

        // Kristals.
        $contents[] = new restore_decode_content('uckkarchive_kristal', [
            'title',
            'summary',
            'content',
            'sourceref',
            'metadata',
        ], 'uckkarchive_kristal');

        // Proof records.
        $contents[] = new restore_decode_content('uckkarchive_proof', [
            'title',
            'summary',
            'content',
            'sourceref',
            'metadata',
        ], 'uckkarchive_proof');

        // Provenance records.
        $contents[] = new restore_decode_content('uckkarchive_prov', [
            'summary',
            'statement',
            'sourcecomponent',
            'sourceref',
            'metadata',
        ], 'uckkarchive_prov');

        // Revision records.
        $contents[] = new restore_decode_content('uckkarchive_rev', [
            'summary',
            'reason',
            'beforejson',
            'afterjson',
            'metadata',
        ], 'uckkarchive_rev');

        // Export records.
        $contents[] = new restore_decode_content('uckkarchive_export', [
            'title',
            'summary',
            'exportmanifest',
            'metadata',
        ], 'uckkarchive_export');

        return $contents;
    }

    /**
     * Define rules for decoding encoded activity links after restore.
     *
     * These tokens should match backup_uckkarchive_activity_task::encode_content_links().
     *
     * @return array
     */
    public static function define_decode_rules(): array {
        $rules = [];

        $rules[] = new restore_decode_rule(
            'UCKKARCHIVEINDEX',
            '/mod/uckkarchive/index.php?id=$1',
            'course'
        );

        $rules[] = new restore_decode_rule(
            'UCKKARCHIVEVIEWBYCMID',
            '/mod/uckkarchive/view.php?id=$1',
            'course_module'
        );

        $rules[] = new restore_decode_rule(
            'UCKKARCHIVEVIEWBYID',
            '/mod/uckkarchive/view.php?a=$1',
            'uckkarchive'
        );

        $rules[] = new restore_decode_rule(
            'UCKKARCHIVEITEMBYCMID',
            '/mod/uckkarchive/item.php?id=$1',
            'course_module'
        );

        $rules[] = new restore_decode_rule(
            'UCKKARCHIVEITEMBYID',
            '/mod/uckkarchive/item.php?itemid=$1',
            'uckkarchive_item'
        );

        $rules[] = new restore_decode_rule(
            'UCKKARCHIVEADDBYCMID',
            '/mod/uckkarchive/add.php?id=$1',
            'course_module'
        );

        $rules[] = new restore_decode_rule(
            'UCKKARCHIVEVALIDATEBYCMID',
            '/mod/uckkarchive/validate.php?id=$1',
            'course_module'
        );

        $rules[] = new restore_decode_rule(
            'UCKKARCHIVEEXPORTBYCMID',
            '/mod/uckkarchive/export.php?id=$1',
            'course_module'
        );

        $rules[] = new restore_decode_rule(
            'UCKKARCHIVEEXPORTBYID',
            '/mod/uckkarchive/export.php?exportid=$1',
            'uckkarchive_export'
        );

        return $rules;
    }

    /**
     * Define legacy activity log restore rules.
     *
     * @return restore_log_rule[]
     */
    public static function define_restore_log_rules(): array {
        $rules = [];

        $rules[] = new restore_log_rule(
            'uckkarchive',
            'add',
            'view.php?id={course_module}',
            '{uckkarchive}'
        );

        $rules[] = new restore_log_rule(
            'uckkarchive',
            'update',
            'view.php?id={course_module}',
            '{uckkarchive}'
        );

        $rules[] = new restore_log_rule(
            'uckkarchive',
            'view',
            'view.php?id={course_module}',
            '{uckkarchive}'
        );

        $rules[] = new restore_log_rule(
            'uckkarchive',
            'add item',
            'add.php?id={course_module}',
            '{uckkarchive_item}'
        );

        $rules[] = new restore_log_rule(
            'uckkarchive',
            'view item',
            'item.php?id={course_module}&itemid={uckkarchive_item}',
            '{uckkarchive_item}'
        );

        $rules[] = new restore_log_rule(
            'uckkarchive',
            'validate item',
            'validate.php?id={course_module}&itemid={uckkarchive_item}',
            '{uckkarchive_item}'
        );

        $rules[] = new restore_log_rule(
            'uckkarchive',
            'revise item',
            'item.php?id={course_module}&itemid={uckkarchive_item}',
            '{uckkarchive_rev}'
        );

        $rules[] = new restore_log_rule(
            'uckkarchive',
            'export',
            'export.php?id={course_module}',
            '{uckkarchive_export}'
        );

        return $rules;
    }

    /**
     * Define legacy course-level log restore rules.
     *
     * @return restore_log_rule[]
     */
    public static function define_restore_log_rules_for_course(): array {
        $rules = [];

        $rules[] = new restore_log_rule(
            'uckkarchive',
            'view all',
            'index.php?id={course}',
            null
        );

        return $rules;
    }

    /**
     * Return file areas used by the activity.
     *
     * Actual file restoration is performed by the restore structure step. These
     * names must stay aligned with mod_uckkarchive::pluginfile(), backup steps,
     * and the generated archive lib.php.
     *
     * @return string[]
     */
    public function get_fileareas(): array {
        return [
            'intro',
            'proof_files',
            'decision_attachments',
            'minutes_files',
            'kristal_files',
            'portfolio_files',
            'integrity_exports',
        ];
    }

    /**
     * Return encoded config-data attributes.
     *
     * @return string[]
     */
    public function get_configdata_encoded_attributes(): array {
        return [];
    }
}