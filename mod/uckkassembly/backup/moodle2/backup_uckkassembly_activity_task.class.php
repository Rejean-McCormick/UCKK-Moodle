<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

/**
 * Backup task for the UCKK Assembly activity.
 *
 * @package    mod_uckkassembly
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/backup/moodle2/backup_activity_task.class.php');
require_once($CFG->dirroot . '/mod/uckkassembly/backup/moodle2/backup_uckkassembly_stepslib.php');

/**
 * Backup task for mod_uckkassembly.
 *
 * This class wires the activity backup task. Data structure details belong in
 * backup_uckkassembly_stepslib.php. Restore mappings belong in the matching
 * restore task and stepslib files.
 */
class backup_uckkassembly_activity_task extends backup_activity_task {
    /**
     * Define activity-specific backup settings.
     *
     * The Assembly module does not currently declare custom backup settings.
     */
    protected function define_my_settings(): void {
        // No custom settings.
    }

    /**
     * Define backup steps.
     */
    protected function define_my_steps(): void {
        $this->add_step(new backup_uckkassembly_activity_structure_step(
            'uckkassembly_structure',
            'uckkassembly.xml'
        ));
    }

    /**
     * Return the file areas used by this activity.
     *
     * These names must match the component/filearea values used by the module
     * controllers, forms, output classes, and pluginfile callback.
     *
     * @return array<int, string>
     */
    public function get_fileareas(): array {
        return [
            'intro',
            'motion_text',
            'motion_rationale',
            'motion_files',
            'amendment_text',
            'amendment_files',
            'objection_text',
            'objection_files',
            'vote_comment',
            'decision_text',
            'decision_attachments',
            'minutes_text',
            'minutes_files',
            'contestation_summary',
            'contestation_grounds',
            'contestation_outcome',
            'contestation_files',
            'archive_summary',
            'archive_files',
        ];
    }

    /**
     * Return config data attributes requiring link encoding.
     *
     * Activity modules usually store most content in their own tables instead
     * of instance config. No config attributes require special encoding here.
     *
     * @return array<int, string>
     */
    public function get_configdata_encoded_attributes(): array {
        return [];
    }

    /**
     * Encode links to this activity inside backed-up content.
     *
     * This makes internal links portable during backup/restore.
     *
     * @param string $content Content to encode.
     * @return string Encoded content.
     */
    public static function encode_content_links($content): string {
        global $CFG;

        $base = preg_quote($CFG->wwwroot, '/');

        // Course-level assembly index links.
        $search = "/(" . $base . "\/mod\/uckkassembly\/index\.php\?id=)([0-9]+)/";
        $content = preg_replace($search, '$@UCKKASSEMBLYINDEX*$2@$', $content);

        // Main activity view links by course module id.
        $search = "/(" . $base . "\/mod\/uckkassembly\/view\.php\?id=)([0-9]+)/";
        $content = preg_replace($search, '$@UCKKASSEMBLYVIEWBYID*$2@$', $content);

        // Motion view links.
        $search = "/(" . $base . "\/mod\/uckkassembly\/motion\.php\?id=)([0-9]+)((?:&|&amp;)motionid=)([0-9]+)/";
        $content = preg_replace($search, '$@UCKKASSEMBLYMOTION*$2*$4@$', $content);

        // Motion proposal links.
        $search = "/(" . $base . "\/mod\/uckkassembly\/propose\.php\?id=)([0-9]+)/";
        $content = preg_replace($search, '$@UCKKASSEMBLYPROPOSE*$2@$', $content);

        // Amendment links.
        $search = "/(" . $base . "\/mod\/uckkassembly\/amend\.php\?id=)([0-9]+)((?:&|&amp;)amendid=)([0-9]+)/";
        $content = preg_replace($search, '$@UCKKASSEMBLYAMENDMENT*$2*$4@$', $content);

        // Objection links.
        $search = "/(" . $base . "\/mod\/uckkassembly\/object\.php\?id=)([0-9]+)((?:&|&amp;)objectid=)([0-9]+)/";
        $content = preg_replace($search, '$@UCKKASSEMBLYOBJECTION*$2*$4@$', $content);

        // Vote links by vote id.
        $search = "/(" . $base . "\/mod\/uckkassembly\/vote\.php\?id=)([0-9]+)((?:&|&amp;)voteid=)([0-9]+)/";
        $content = preg_replace($search, '$@UCKKASSEMBLYVOTE*$2*$4@$', $content);

        // Decision links by decision id.
        $search = "/(" . $base . "\/mod\/uckkassembly\/decision\.php\?id=)([0-9]+)((?:&|&amp;)decisionid=)([0-9]+)/";
        $content = preg_replace($search, '$@UCKKASSEMBLYDECISION*$2*$4@$', $content);

        // Minutes links.
        $search = "/(" . $base . "\/mod\/uckkassembly\/minutes\.php\?id=)([0-9]+)((?:&|&amp;)minutesid=)([0-9]+)/";
        $content = preg_replace($search, '$@UCKKASSEMBLYMINUTES*$2*$4@$', $content);

        // Contestation links.
        $search = "/(" . $base . "\/mod\/uckkassembly\/contest\.php\?id=)([0-9]+)((?:&|&amp;)contestationid=)([0-9]+)/";
        $content = preg_replace($search, '$@UCKKASSEMBLYCONTESTATION*$2*$4@$', $content);

        // Archive export links from the assembly module.
        $search = "/(" . $base . "\/mod\/uckkassembly\/archive\.php\?id=)([0-9]+)/";
        $content = preg_replace($search, '$@UCKKASSEMBLYARCHIVE*$2@$', $content);

        return $content;
    }
}