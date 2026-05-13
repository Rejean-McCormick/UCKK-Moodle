<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

/**
 * Backup task for the UCKK challenge activity.
 *
 * @package    mod_uckkchallenge
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/backup/moodle2/backup_activity_task.class.php');
require_once($CFG->dirroot . '/mod/uckkchallenge/backup/moodle2/backup_uckkchallenge_stepslib.php');

/**
 * Backup task for mod_uckkchallenge.
 *
 * The task wires Moodle backup to the activity structure step. The detailed
 * table/file structure belongs in backup_uckkchallenge_activity_structure_step.
 */
class backup_uckkchallenge_activity_task extends backup_activity_task {
    /**
     * Define task-specific backup settings.
     *
     * The challenge activity currently relies on the standard activity settings:
     * user data, files, grades, groups, completion, and activity inclusion.
     */
    protected function define_my_settings(): void {
    }

    /**
     * Define backup steps for this activity.
     */
    protected function define_my_steps(): void {
        $this->add_step(new backup_uckkchallenge_activity_structure_step(
            'uckkchallenge_structure',
            'uckkchallenge.xml'
        ));
    }

    /**
     * Encode links pointing to this activity before content is written to backup.
     *
     * These tokens are decoded by the restore task/rules after restore so links
     * point to the restored course module and restored challenge instance.
     *
     * @param string $content Content to encode.
     * @return string Encoded content.
     */
    public static function encode_content_links($content): string {
        global $CFG;

        $base = preg_quote($CFG->wwwroot, '/');

        // Course-level index links.
        $search = '/(' . $base . '\/mod\/uckkchallenge\/index\.php\?id=)([0-9]+)/';
        $content = preg_replace($search, '$@UCKKCHALLENGEINDEX*$2@$', $content);

        // Activity view links by course module id.
        $search = '/(' . $base . '\/mod\/uckkchallenge\/view\.php\?id=)([0-9]+)/';
        $content = preg_replace($search, '$@UCKKCHALLENGEVIEWBYCMID*$2@$', $content);

        // Activity view links by challenge instance id.
        $search = '/(' . $base . '\/mod\/uckkchallenge\/view\.php\?u=)([0-9]+)/';
        $content = preg_replace($search, '$@UCKKCHALLENGEVIEWBYID*$2@$', $content);

        // Submission controller links by course module id.
        $search = '/(' . $base . '\/mod\/uckkchallenge\/submit\.php\?id=)([0-9]+)/';
        $content = preg_replace($search, '$@UCKKCHALLENGESUBMITBYCMID*$2@$', $content);

        // Submission controller links by challenge instance id.
        $search = '/(' . $base . '\/mod\/uckkchallenge\/submit\.php\?u=)([0-9]+)/';
        $content = preg_replace($search, '$@UCKKCHALLENGESUBMITBYID*$2@$', $content);

        // Review controller links by course module id.
        $search = '/(' . $base . '\/mod\/uckkchallenge\/review\.php\?id=)([0-9]+)/';
        $content = preg_replace($search, '$@UCKKCHALLENGEREVIEWBYCMID*$2@$', $content);

        // Integrity controller links by course module id.
        $search = '/(' . $base . '\/mod\/uckkchallenge\/integrity\.php\?id=)([0-9]+)/';
        $content = preg_replace($search, '$@UCKKCHALLENGEINTEGRITYBYCMID*$2@$', $content);

        // Archive controller links by course module id.
        $search = '/(' . $base . '\/mod\/uckkchallenge\/archive\.php\?id=)([0-9]+)/';
        $content = preg_replace($search, '$@UCKKCHALLENGEARCHIVEBYCMID*$2@$', $content);

        return $content;
    }
}