<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

/**
 * Restore task for the UCKK Assembly activity module.
 *
 * @package    mod_uckkassembly
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/uckkassembly/backup/moodle2/restore_uckkassembly_stepslib.php');

/**
 * Restore task for mod_uckkassembly.
 *
 * The task wires the restore structure step, content decoding, activity-link
 * decoding, and legacy log restoration rules. Record creation, id mapping,
 * file-area restoration, and provenance-sensitive table handling belong in
 * restore_uckkassembly_activity_structure_step.
 */
class restore_uckkassembly_activity_task extends restore_activity_task {
    /**
     * Define activity-specific restore settings.
     *
     * No custom settings are required at task level. User-data inclusion is
     * governed by Moodle's standard activity restore settings and interpreted
     * by the structure step.
     */
    protected function define_my_settings(): void {
    }

    /**
     * Define restore steps.
     */
    protected function define_my_steps(): void {
        $this->add_step(new restore_uckkassembly_activity_structure_step(
            'uckkassembly_structure',
            'uckkassembly.xml'
        ));
    }

    /**
     * Define content fields requiring link decoding after restore.
     *
     * @return array
     */
    public static function define_decode_contents(): array {
        $contents = [];

        // Main Assembly instance.
        $contents[] = new restore_decode_content('uckkassembly', [
            'intro',
            'rules',
            'description',
            'summary',
            'agenda',
            'metadata',
        ], 'uckkassembly');

        // Motions.
        $contents[] = new restore_decode_content('uckkassembly_motion', [
            'title',
            'body',
            'rationale',
            'summary',
            'metadata',
        ], 'uckkassembly_motion');

        // Amendments.
        $contents[] = new restore_decode_content('uckkassembly_amend', [
            'title',
            'body',
            'rationale',
            'summary',
            'metadata',
        ], 'uckkassembly_amend');

        // Objections.
        $contents[] = new restore_decode_content('uckkassembly_object', [
            'title',
            'body',
            'proposedresolution',
            'summary',
            'metadata',
        ], 'uckkassembly_object');

        // Votes and readings.
        $contents[] = new restore_decode_content('uckkassembly_vote', [
            'rationale',
            'comment',
            'readingnote',
            'metadata',
        ], 'uckkassembly_vote');

        // Decisions.
        $contents[] = new restore_decode_content('uckkassembly_decision', [
            'title',
            'body',
            'summary',
            'publicsummary',
            'rationale',
            'metadata',
        ], 'uckkassembly_decision');

        // Minutes.
        $contents[] = new restore_decode_content('uckkassembly_minutes', [
            'title',
            'body',
            'summary',
            'minutes',
            'contribution',
            'metadata',
        ], 'uckkassembly_minutes');

        // Contestations.
        $contents[] = new restore_decode_content('uckkassembly_contest', [
            'title',
            'body',
            'reason',
            'summary',
            'requestedcorrection',
            'metadata',
        ], 'uckkassembly_contest');

        return $contents;
    }

    /**
     * Define link decoding rules.
     *
     * These rules rewrite backed-up links pointing to old course modules,
     * courses, and Assembly sub-pages so restored content points to the new
     * restored records.
     *
     * @return array
     */
    public static function define_decode_rules(): array {
        $rules = [];

        $rules[] = new restore_decode_rule(
            'UCKKASSEMBLYVIEWBYID',
            '/mod/uckkassembly/view.php?id=$1',
            'course_module'
        );

        $rules[] = new restore_decode_rule(
            'UCKKASSEMBLYVIEWBYA',
            '/mod/uckkassembly/view.php?a=$1',
            'uckkassembly'
        );

        $rules[] = new restore_decode_rule(
            'UCKKASSEMBLYINDEX',
            '/mod/uckkassembly/index.php?id=$1',
            'course'
        );

        $rules[] = new restore_decode_rule(
            'UCKKASSEMBLYMOTIONBYCMID',
            '/mod/uckkassembly/motion.php?id=$1',
            'course_module'
        );

        $rules[] = new restore_decode_rule(
            'UCKKASSEMBLYVOTEBYCMID',
            '/mod/uckkassembly/vote.php?id=$1',
            'course_module'
        );

        $rules[] = new restore_decode_rule(
            'UCKKASSEMBLYDECISIONBYCMID',
            '/mod/uckkassembly/decision.php?id=$1',
            'course_module'
        );

        $rules[] = new restore_decode_rule(
            'UCKKASSEMBLYCONTESTBYCMID',
            '/mod/uckkassembly/contest.php?id=$1',
            'course_module'
        );

        $rules[] = new restore_decode_rule(
            'UCKKASSEMBLYMINUTESBYCMID',
            '/mod/uckkassembly/minutes.php?id=$1',
            'course_module'
        );

        $rules[] = new restore_decode_rule(
            'UCKKASSEMBLYARCHIVEBYCMID',
            '/mod/uckkassembly/archive.php?id=$1',
            'course_module'
        );

        return $rules;
    }

    /**
     * Define restore rules for legacy activity logs.
     *
     * @return array
     */
    public static function define_restore_log_rules(): array {
        $rules = [];

        $rules[] = new restore_log_rule('uckkassembly', 'add', 'view.php?id={course_module}', '{uckkassembly}');
        $rules[] = new restore_log_rule('uckkassembly', 'update', 'view.php?id={course_module}', '{uckkassembly}');
        $rules[] = new restore_log_rule('uckkassembly', 'view', 'view.php?id={course_module}', '{uckkassembly}');

        $rules[] = new restore_log_rule('uckkassembly', 'motion submitted', 'motion.php?id={course_module}', '{uckkassembly_motion}');
        $rules[] = new restore_log_rule('uckkassembly', 'motion updated', 'motion.php?id={course_module}', '{uckkassembly_motion}');
        $rules[] = new restore_log_rule('uckkassembly', 'motion withdrawn', 'motion.php?id={course_module}', '{uckkassembly_motion}');

        $rules[] = new restore_log_rule('uckkassembly', 'amendment submitted', 'motion.php?id={course_module}', '{uckkassembly_amend}');
        $rules[] = new restore_log_rule('uckkassembly', 'amendment updated', 'motion.php?id={course_module}', '{uckkassembly_amend}');

        $rules[] = new restore_log_rule('uckkassembly', 'objection submitted', 'contest.php?id={course_module}', '{uckkassembly_object}');
        $rules[] = new restore_log_rule('uckkassembly', 'objection resolved', 'contest.php?id={course_module}', '{uckkassembly_object}');

        $rules[] = new restore_log_rule('uckkassembly', 'vote submitted', 'vote.php?id={course_module}', '{uckkassembly_vote}');
        $rules[] = new restore_log_rule('uckkassembly', 'reading submitted', 'vote.php?id={course_module}', '{uckkassembly_vote}');

        $rules[] = new restore_log_rule('uckkassembly', 'decision published', 'decision.php?id={course_module}', '{uckkassembly_decision}');
        $rules[] = new restore_log_rule('uckkassembly', 'decision contested', 'contest.php?id={course_module}', '{uckkassembly_contest}');

        $rules[] = new restore_log_rule('uckkassembly', 'minutes published', 'minutes.php?id={course_module}', '{uckkassembly_minutes}');
        $rules[] = new restore_log_rule('uckkassembly', 'archive exported', 'archive.php?id={course_module}', '{uckkassembly}');

        return $rules;
    }

    /**
     * Define restore rules for legacy course-level logs.
     *
     * @return array
     */
    public static function define_restore_log_rules_for_course(): array {
        $rules = [];

        $rules[] = new restore_log_rule('uckkassembly', 'view all', 'index.php?id={course}', null);

        return $rules;
    }
}