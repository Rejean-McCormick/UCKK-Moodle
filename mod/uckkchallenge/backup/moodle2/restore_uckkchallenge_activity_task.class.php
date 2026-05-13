<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

/**
 * Restore task for the UCKK Challenge activity module.
 *
 * @package    mod_uckkchallenge
 * @category   backup
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/uckkchallenge/backup/moodle2/restore_uckkchallenge_stepslib.php');

/**
 * Restore task for Défis King Klown.
 *
 * This class wires Moodle restore machinery to the UCKK Challenge structure
 * restore step. It does not process records directly; record processing belongs
 * in restore_uckkchallenge_stepslib.php.
 */
class restore_uckkchallenge_activity_task extends restore_activity_task {
    /**
     * Define task-specific restore settings.
     *
     * UCKK Challenge currently uses Moodle's standard activity restore settings.
     */
    protected function define_my_settings(): void {
    }

    /**
     * Define the restore steps for this activity.
     */
    protected function define_my_steps(): void {
        $this->add_step(new restore_uckkchallenge_activity_structure_step(
            'uckkchallenge_structure',
            'uckkchallenge.xml'
        ));
    }

    /**
     * Define content fields that may contain encoded links.
     *
     * Moodle will scan these fields and rewrite links during restore.
     *
     * @return restore_decode_content[]
     */
    public static function define_decode_contents(): array {
        $contents = [];

        $contents[] = new restore_decode_content('uckkchallenge', [
            'intro',
            'statement',
            'contexttext',
            'rules',
            'corridors',
            'ethicalconstraints',
            'evidencepolicy',
            'criteria',
            'integritynotes',
            'aipolicy',
            'publicsummary',
            'competencylinks',
            'badgelinks',
            'metadata',
        ], 'uckkchallenge');

        $contents[] = new restore_decode_content('uckkchallenge_rule', [
            'description',
            'metadata',
        ], 'uckkchallenge_rule');

        $contents[] = new restore_decode_content('uckkchallenge_corr', [
            'description',
            'requirements',
            'metadata',
        ], 'uckkchallenge_corr');

        $contents[] = new restore_decode_content('uckkchallenge_sub', [
            'submissiontext',
            'submissionurl',
            'relationtocriteria',
            'provenancestatement',
            'ailog',
            'uncertaintynotes',
            'metadata',
        ], 'uckkchallenge_sub');

        $contents[] = new restore_decode_content('uckkchallenge_proof', [
            'description',
            'source',
            'url',
            'relationtocriteria',
            'provenance',
            'metadata',
        ], 'uckkchallenge_proof');

        $contents[] = new restore_decode_content('uckkchallenge_eval', [
            'rubricjson',
            'feedback',
            'privatefeedback',
            'metadata',
        ], 'uckkchallenge_eval');

        $contents[] = new restore_decode_content('uckkchallenge_state', [
            'reason',
            'metadata',
        ], 'uckkchallenge_state');

        return $contents;
    }

    /**
     * Define rules for restoring links pointing to this activity.
     *
     * These rules decode links generated in backed-up content and map them to
     * the restored course module.
     *
     * @return restore_decode_rule[]
     */
    public static function define_decode_rules(): array {
        $rules = [];

        $rules[] = new restore_decode_rule(
            'UCKKCHALLENGEINDEX',
            '/mod/uckkchallenge/index.php?id=$1',
            'course'
        );

        $rules[] = new restore_decode_rule(
            'UCKKCHALLENGEVIEWBYID',
            '/mod/uckkchallenge/view.php?id=$1',
            'course_module'
        );

        $rules[] = new restore_decode_rule(
            'UCKKCHALLENGESUBMITBYID',
            '/mod/uckkchallenge/submit.php?id=$1',
            'course_module'
        );

        $rules[] = new restore_decode_rule(
            'UCKKCHALLENGEEVALUATEBYID',
            '/mod/uckkchallenge/evaluate.php?id=$1',
            'course_module'
        );

        $rules[] = new restore_decode_rule(
            'UCKKCHALLENGEINTEGRITYBYID',
            '/mod/uckkchallenge/integrity.php?id=$1',
            'course_module'
        );

        $rules[] = new restore_decode_rule(
            'UCKKCHALLENGEARCHIVEBYID',
            '/mod/uckkchallenge/archive.php?id=$1',
            'course_module'
        );

        return $rules;
    }

    /**
     * Define legacy log restore rules.
     *
     * @return restore_log_rule[]
     */
    public static function define_restore_log_rules(): array {
        $rules = [];

        $rules[] = new restore_log_rule(
            'uckkchallenge',
            'add',
            'view.php?id={course_module}',
            '{uckkchallenge}'
        );

        $rules[] = new restore_log_rule(
            'uckkchallenge',
            'update',
            'view.php?id={course_module}',
            '{uckkchallenge}'
        );

        $rules[] = new restore_log_rule(
            'uckkchallenge',
            'view',
            'view.php?id={course_module}',
            '{uckkchallenge}'
        );

        $rules[] = new restore_log_rule(
            'uckkchallenge',
            'submit',
            'submit.php?id={course_module}',
            '{uckkchallenge_sub}'
        );

        $rules[] = new restore_log_rule(
            'uckkchallenge',
            'evaluate',
            'evaluate.php?id={course_module}',
            '{uckkchallenge_eval}'
        );

        $rules[] = new restore_log_rule(
            'uckkchallenge',
            'integrity',
            'integrity.php?id={course_module}',
            '{uckkchallenge}'
        );

        $rules[] = new restore_log_rule(
            'uckkchallenge',
            'archive',
            'archive.php?id={course_module}',
            '{uckkchallenge}'
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
            'uckkchallenge',
            'view all',
            'index.php?id={course}',
            null
        );

        return $rules;
    }

    /**
     * Return file areas used by the activity.
     *
     * The actual file restoration is performed by the restore structure step.
     *
     * @return string[]
     */
    public function get_fileareas(): array {
        return [
            'intro',
            'statement',
            'contexttext',
            'rules',
            'corridors',
            'ethicalconstraints',
            'evidencepolicy',
            'criteria',
            'integritynotes',
            'aipolicy',
            'publicsummary',
            'submission_text',
            'proof_files',
            'proof_description',
            'feedback',
            'privatefeedback',
            'state_reason',
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