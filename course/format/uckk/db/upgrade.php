<?php
// This file is part of UCKK-Moodle.
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.
//
// UCKK-Moodle adapts Moodle as the pedagogical campus of the
// Univers-Cité King Klown.

/**
 * Upgrade steps for the UCKK course format.
 *
 * This file must remain self-contained. Moodle parses files in db/ in
 * performance-sensitive contexts, so do not add require_once, include,
 * autoload assumptions, output code, workflow logic, rendering logic, or
 * non-upgrade side effects here.
 *
 * The UCKK course format stores only portable course-format options:
 * - course-level options in course_format_options with sectionid = 0;
 * - section-level options in course_format_options with sectionid set to the
 *   course_sections id.
 *
 * This file does not create UCKK courses, challenges, assemblies, archives,
 * badges, competencies, reports, or seed data. Those responsibilities belong
 * to tool_uckkseed and the relevant UCKK plugins.
 *
 * @package    format_uckk
 * @copyright  2026 Univers-Cité King Klown
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Upgrade function for format_uckk.
 *
 * @param int $oldversion The version we are upgrading from.
 * @return bool
 */
function xmldb_format_uckk_upgrade($oldversion): bool {
    global $DB;

    $dbman = $DB->get_manager();

    // -------------------------------------------------------------------------
    // Initial stable upgrade step.
    // -------------------------------------------------------------------------
    //
    // Fresh installations do not normally execute this step because install and
    // version registration happen directly. This step is useful for development
    // installs, pre-release installs, or sites that received an earlier package
    // before the UCKK format options were finalised.
    //
    // No schema changes are required for the course format itself. We only repair
    // portable records in Moodle's core course_format_options table.
    if ($oldversion < 2026051200) {
        format_uckk_upgrade_seed_course_options();
        format_uckk_upgrade_seed_section_options();

        upgrade_plugin_savepoint(true, 2026051200, 'format', 'uckk');
    }

    return true;
}

/**
 * Ensure all courses using format_uckk have the required course-level options.
 *
 * Course-level options use sectionid = 0.
 *
 * @return void
 */
function format_uckk_upgrade_seed_course_options(): void {
    global $DB;

    if (!$DB->get_manager()->table_exists('course')) {
        return;
    }

    if (!$DB->get_manager()->table_exists('course_format_options')) {
        return;
    }

    $defaults = [
        'uckkmode' => 'standard',
        'showcanon' => '1',
        'showevidenceflow' => '1',
        'showintegritynotice' => '1',
        'showrecognitionnotice' => '1',
    ];

    $courses = $DB->get_records(
        'course',
        ['format' => 'uckk'],
        '',
        'id, shortname, idnumber, format'
    );

    foreach ($courses as $course) {
        $mode = format_uckk_upgrade_infer_course_mode($course);

        $courseoptions = $defaults;
        $courseoptions['uckkmode'] = $mode;

        foreach ($courseoptions as $name => $value) {
            format_uckk_upgrade_set_format_option((int)$course->id, 0, $name, $value);
        }
    }
}

/**
 * Ensure all sections in format_uckk courses have the required section options.
 *
 * Section-level options use sectionid = course_sections.id.
 *
 * @return void
 */
function format_uckk_upgrade_seed_section_options(): void {
    global $DB;

    $dbman = $DB->get_manager();

    if (!$dbman->table_exists('course')) {
        return;
    }

    if (!$dbman->table_exists('course_sections')) {
        return;
    }

    if (!$dbman->table_exists('course_format_options')) {
        return;
    }

    $courses = $DB->get_records(
        'course',
        ['format' => 'uckk'],
        '',
        'id, shortname, idnumber, format'
    );

    foreach ($courses as $course) {
        $sections = $DB->get_records(
            'course_sections',
            ['course' => $course->id],
            'section ASC',
            'id, course, section, name'
        );

        foreach ($sections as $section) {
            $sectiondefaults = format_uckk_upgrade_get_default_section_options((int)$section->section);

            foreach ($sectiondefaults as $name => $value) {
                format_uckk_upgrade_set_format_option((int)$course->id, (int)$section->id, $name, $value);
            }
        }
    }
}

/**
 * Insert or update a course format option.
 *
 * This helper is intentionally local to upgrade.php to keep the file
 * self-contained and compatible with Moodle db/ file rules.
 *
 * @param int $courseid Course id.
 * @param int $sectionid Section id, or 0 for course-level options.
 * @param string $name Option name.
 * @param string $value Option value.
 * @return void
 */
function format_uckk_upgrade_set_format_option(int $courseid, int $sectionid, string $name, string $value): void {
    global $DB;

    $params = [
        'courseid' => $courseid,
        'format' => 'uckk',
        'sectionid' => $sectionid,
        'name' => $name,
    ];

    $existing = $DB->get_record('course_format_options', $params, 'id, value', IGNORE_MISSING);

    if ($existing) {
        if ((string)$existing->value !== $value) {
            $existing->value = $value;
            $DB->update_record('course_format_options', $existing);
        }

        return;
    }

    $record = (object)[
        'courseid' => $courseid,
        'format' => 'uckk',
        'sectionid' => $sectionid,
        'name' => $name,
        'value' => $value,
    ];

    $DB->insert_record('course_format_options', $record);
}

/**
 * Infer the UCKK course mode from course identifiers.
 *
 * This mirrors the portable inference used by the course format:
 * - UCKK-TC* means tronc commun;
 * - UCKK-* means internal UCKK program;
 * - otherwise standard.
 *
 * @param stdClass $course Course record.
 * @return string
 */
function format_uckk_upgrade_infer_course_mode(stdClass $course): string {
    $shortname = $course->shortname ?? '';
    $idnumber = $course->idnumber ?? '';

    if (preg_match('/^UCKK-TC/i', $shortname) || preg_match('/^UCKK-TC/i', $idnumber)) {
        return 'tronccommun';
    }

    if (preg_match('/^UCKK-/i', $shortname) || preg_match('/^UCKK-/i', $idnumber)) {
        return 'program';
    }

    return 'standard';
}

/**
 * Return default UCKK section options for a Moodle section number.
 *
 * The values are portable: they use section numbers and semantic keys only.
 * No course module ids, archive ids, challenge ids, assembly ids, file ids or
 * external ids are stored here.
 *
 * @param int $sectionnum Moodle section number.
 * @return array<string, string>
 */
function format_uckk_upgrade_get_default_section_options(int $sectionnum): array {
    $kind = format_uckk_upgrade_get_default_section_kind($sectionnum);

    return [
        'uckksectionkind' => $kind,
        'requiresproof' => format_uckk_upgrade_section_requires_proof($kind) ? '1' : '0',
        'archivable' => format_uckk_upgrade_section_is_archivable($kind) ? '1' : '0',
        'integritysensitive' => format_uckk_upgrade_section_is_integrity_sensitive($kind) ? '1' : '0',
    ];
}

/**
 * Return the default UCKK section kind for a Moodle section number.
 *
 * @param int $sectionnum Moodle section number.
 * @return string
 */
function format_uckk_upgrade_get_default_section_kind(int $sectionnum): string {
    $sections = [
        0 => 'orientation',
        1 => 'concepts',
        2 => 'canon',
        3 => 'atelier',
        4 => 'preuves',
        5 => 'deliberation',
        6 => 'livrable',
        7 => 'evaluation',
        8 => 'archive',
    ];

    return $sections[$sectionnum] ?? 'custom';
}

/**
 * Return whether a UCKK section kind requires proof by default.
 *
 * @param string $kind Section kind.
 * @return bool
 */
function format_uckk_upgrade_section_requires_proof(string $kind): bool {
    return in_array($kind, ['preuves', 'livrable'], true);
}

/**
 * Return whether a UCKK section kind is archivable by default.
 *
 * @param string $kind Section kind.
 * @return bool
 */
function format_uckk_upgrade_section_is_archivable(string $kind): bool {
    return in_array($kind, ['canon', 'atelier', 'preuves', 'deliberation', 'livrable', 'evaluation', 'archive'], true);
}

/**
 * Return whether a UCKK section kind is integrity-sensitive by default.
 *
 * @param string $kind Section kind.
 * @return bool
 */
function format_uckk_upgrade_section_is_integrity_sensitive(string $kind): bool {
    return in_array($kind, ['canon', 'preuves', 'deliberation', 'livrable', 'evaluation', 'archive'], true);
}