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
 * Unit tests for the UCKK course format.
 *
 * @package    format_uckk
 * @category   test
 * @copyright  2026 Univers-Cité King Klown
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_uckk;

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/course/format/uckk/lib.php');

use advanced_testcase;
use context_course;
use core_text;
use format_uckk\local\courseblueprint;
use moodle_url;

/**
 * Unit tests for format_uckk.
 *
 * These tests intentionally validate only the course format contract.
 * They must not depend on future UCKK activity modules, archive modules,
 * assembly modules, challenge modules, dashboard blocks or integrity tools.
 *
 * @covers \format_uckk
 * @covers \format_uckk\local\courseblueprint
 */
final class format_uckk_test extends advanced_testcase {
    /**
     * Test that the format declares stable course-level options.
     */
    public function test_course_format_options_are_declared(): void {
        $this->resetAfterTest(true);

        $format = $this->create_uckk_format();

        $options = $format->course_format_options(false);

        $this->assertArrayHasKey('uckkmode', $options);
        $this->assertArrayHasKey('showcanon', $options);
        $this->assertArrayHasKey('showevidenceflow', $options);
        $this->assertArrayHasKey('showintegritynotice', $options);
        $this->assertArrayHasKey('showrecognitionnotice', $options);

        $this->assertSame('standard', $options['uckkmode']['default']);
        $this->assertSame(PARAM_ALPHANUMEXT, $options['uckkmode']['type']);

        $this->assertSame(1, $options['showcanon']['default']);
        $this->assertSame(PARAM_BOOL, $options['showcanon']['type']);

        $this->assertSame(1, $options['showevidenceflow']['default']);
        $this->assertSame(PARAM_BOOL, $options['showevidenceflow']['type']);

        $this->assertSame(1, $options['showintegritynotice']['default']);
        $this->assertSame(PARAM_BOOL, $options['showintegritynotice']['type']);

        $this->assertSame(1, $options['showrecognitionnotice']['default']);
        $this->assertSame(PARAM_BOOL, $options['showrecognitionnotice']['type']);
    }

    /**
     * Test that course-level options expose edit form metadata.
     */
    public function test_course_format_options_for_edit_form_include_labels(): void {
        $this->resetAfterTest(true);

        $format = $this->create_uckk_format();

        $options = $format->course_format_options(true);

        $this->assertSame('select', $options['uckkmode']['element_type']);
        $this->assertArrayHasKey('label', $options['uckkmode']);
        $this->assertArrayHasKey('help', $options['uckkmode']);

        $this->assertSame('advcheckbox', $options['showcanon']['element_type']);
        $this->assertSame('advcheckbox', $options['showevidenceflow']['element_type']);
        $this->assertSame('advcheckbox', $options['showintegritynotice']['element_type']);
        $this->assertSame('advcheckbox', $options['showrecognitionnotice']['element_type']);
    }

    /**
     * Test that the format declares stable section-level options.
     */
    public function test_section_format_options_are_declared(): void {
        $this->resetAfterTest(true);

        $format = $this->create_uckk_format();

        $options = $format->section_format_options(false);

        $this->assertArrayHasKey('uckksectionkind', $options);
        $this->assertArrayHasKey('requiresproof', $options);
        $this->assertArrayHasKey('archivable', $options);
        $this->assertArrayHasKey('integritysensitive', $options);

        $this->assertSame('', $options['uckksectionkind']['default']);
        $this->assertSame(PARAM_ALPHANUMEXT, $options['uckksectionkind']['type']);

        $this->assertSame(0, $options['requiresproof']['default']);
        $this->assertSame(PARAM_BOOL, $options['requiresproof']['type']);

        $this->assertSame(0, $options['archivable']['default']);
        $this->assertSame(PARAM_BOOL, $options['archivable']['type']);

        $this->assertSame(0, $options['integritysensitive']['default']);
        $this->assertSame(PARAM_BOOL, $options['integritysensitive']['type']);
    }

    /**
     * Test that section-level options expose edit form metadata.
     */
    public function test_section_format_options_for_edit_form_include_labels(): void {
        $this->resetAfterTest(true);

        $format = $this->create_uckk_format();

        $options = $format->section_format_options(true);

        $this->assertSame('select', $options['uckksectionkind']['element_type']);
        $this->assertArrayHasKey('label', $options['uckksectionkind']);
        $this->assertArrayHasKey('help', $options['uckksectionkind']);

        $this->assertSame('advcheckbox', $options['requiresproof']['element_type']);
        $this->assertSame('advcheckbox', $options['archivable']['element_type']);
        $this->assertSame('advcheckbox', $options['integritysensitive']['element_type']);
    }

    /**
     * Test course format feature flags.
     */
    public function test_course_format_feature_flags(): void {
        $this->resetAfterTest(true);

        $format = $this->create_uckk_format();

        $this->assertTrue($format->uses_sections());
        $this->assertTrue($format->uses_course_index());
        $this->assertTrue($format->supports_components());
        $this->assertFalse($format->uses_indentation());
        $this->assertFalse($format->supports_news());
        $this->assertSame(COURSE_DISPLAY_SINGLEPAGE, $format->get_course_display());
        $this->assertSame(52, $format->get_max_sections());
    }

    /**
     * Test default section kinds.
     */
    public function test_default_section_kinds_are_canonical(): void {
        $expected = [
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

        $this->assertSame($expected, \format_uckk::get_default_section_kinds());
    }

    /**
     * Test default section string keys.
     */
    public function test_default_section_string_keys_are_canonical(): void {
        $expected = [
            0 => 'course_orientation',
            1 => 'course_concepts',
            2 => 'course_canon',
            3 => 'course_workshop',
            4 => 'course_proofs',
            5 => 'course_deliberation',
            6 => 'course_deliverable',
            7 => 'course_evaluation',
            8 => 'course_archive',
        ];

        $this->assertSame($expected, \format_uckk::get_default_section_string_keys());
    }

    /**
     * Test that custom section names are preserved.
     */
    public function test_get_section_name_preserves_custom_section_name(): void {
        global $DB;

        $this->resetAfterTest(true);

        $course = $this->getDataGenerator()->create_course([
            'format' => 'uckk',
            'numsections' => 3,
            'shortname' => 'UCKK-TEST-CUSTOM',
            'idnumber' => 'UCKK-TEST-CUSTOM',
        ]);

        $section = $DB->get_record('course_sections', [
            'course' => $course->id,
            'section' => 1,
        ], '*', MUST_EXIST);

        $section->name = 'Section personnalisée';
        $DB->update_record('course_sections', $section);

        rebuild_course_cache($course->id, true);

        $format = course_get_format($course);

        $this->assertSame('Section personnalisée', $format->get_section_name(1));
    }

    /**
     * Test that default section names resolve through language strings.
     */
    public function test_get_default_section_name_uses_canonical_labels(): void {
        $this->resetAfterTest(true);

        $format = $this->create_uckk_format();

        $this->assertSame(get_string('course_orientation', 'format_uckk'), $format->get_default_section_name(0));
        $this->assertSame(get_string('course_concepts', 'format_uckk'), $format->get_default_section_name(1));
        $this->assertSame(get_string('course_canon', 'format_uckk'), $format->get_default_section_name(2));
        $this->assertSame(get_string('course_workshop', 'format_uckk'), $format->get_default_section_name(3));
        $this->assertSame(get_string('course_proofs', 'format_uckk'), $format->get_default_section_name(4));
        $this->assertSame(get_string('course_deliberation', 'format_uckk'), $format->get_default_section_name(5));
        $this->assertSame(get_string('course_deliverable', 'format_uckk'), $format->get_default_section_name(6));
        $this->assertSame(get_string('course_evaluation', 'format_uckk'), $format->get_default_section_name(7));
        $this->assertSame(get_string('course_archive', 'format_uckk'), $format->get_default_section_name(8));
    }

    /**
     * Test semantic section kind resolution.
     */
    public function test_get_uckk_section_kind_uses_canonical_default(): void {
        $this->resetAfterTest(true);

        $format = $this->create_uckk_format();

        $this->assertSame('orientation', $format->get_uckk_section_kind(0));
        $this->assertSame('concepts', $format->get_uckk_section_kind(1));
        $this->assertSame('canon', $format->get_uckk_section_kind(2));
        $this->assertSame('atelier', $format->get_uckk_section_kind(3));
        $this->assertSame('preuves', $format->get_uckk_section_kind(4));
        $this->assertSame('deliberation', $format->get_uckk_section_kind(5));
        $this->assertSame('livrable', $format->get_uckk_section_kind(6));
        $this->assertSame('evaluation', $format->get_uckk_section_kind(7));
        $this->assertSame('archive', $format->get_uckk_section_kind(8));
        $this->assertSame('custom', $format->get_uckk_section_kind(99));
    }

    /**
     * Test tronc commun mode inference.
     */
    public function test_get_uckk_mode_infers_tronc_commun_from_shortname(): void {
        $this->resetAfterTest(true);

        $course = $this->getDataGenerator()->create_course([
            'format' => 'uckk',
            'shortname' => 'UCKK-TC101',
            'idnumber' => '',
        ]);

        $format = course_get_format($course);

        $this->assertSame('tronccommun', $format->get_uckk_mode());
        $this->assertTrue($format->is_tronc_commun());
        $this->assertFalse($format->is_program_course());
    }

    /**
     * Test tronc commun mode inference from idnumber.
     */
    public function test_get_uckk_mode_infers_tronc_commun_from_idnumber(): void {
        $this->resetAfterTest(true);

        $course = $this->getDataGenerator()->create_course([
            'format' => 'uckk',
            'shortname' => 'Course without UCKK code',
            'idnumber' => 'UCKK-TC108',
        ]);

        $format = course_get_format($course);

        $this->assertSame('tronccommun', $format->get_uckk_mode());
        $this->assertTrue($format->is_tronc_commun());
    }

    /**
     * Test program mode inference.
     */
    public function test_get_uckk_mode_infers_program_course(): void {
        $this->resetAfterTest(true);

        $course = $this->getDataGenerator()->create_course([
            'format' => 'uckk',
            'shortname' => 'UCKK-GJS101',
            'idnumber' => 'UCKK-GJS101',
        ]);

        $format = course_get_format($course);

        $this->assertSame('program', $format->get_uckk_mode());
        $this->assertFalse($format->is_tronc_commun());
        $this->assertTrue($format->is_program_course());
    }

    /**
     * Test standard mode inference.
     */
    public function test_get_uckk_mode_defaults_to_standard(): void {
        $this->resetAfterTest(true);

        $course = $this->getDataGenerator()->create_course([
            'format' => 'uckk',
            'shortname' => 'STANDARD-COURSE',
            'idnumber' => '',
        ]);

        $format = course_get_format($course);

        $this->assertSame('standard', $format->get_uckk_mode());
        $this->assertFalse($format->is_tronc_commun());
        $this->assertFalse($format->is_program_course());
    }

    /**
     * Test format options can override inferred mode.
     */
    public function test_get_uckk_mode_uses_format_option_when_present(): void {
        $this->resetAfterTest(true);

        $course = $this->getDataGenerator()->create_course([
            'format' => 'uckk',
            'shortname' => 'STANDARD-COURSE',
            'idnumber' => '',
            'uckkmode' => 'lab',
        ]);

        $format = course_get_format($course);

        $this->assertSame('lab', $format->get_uckk_mode());
    }

    /**
     * Test display option helpers.
     */
    public function test_display_option_helpers_read_course_options(): void {
        $this->resetAfterTest(true);

        $course = $this->getDataGenerator()->create_course([
            'format' => 'uckk',
            'shortname' => 'UCKK-OPTIONS',
            'idnumber' => 'UCKK-OPTIONS',
            'showcanon' => 1,
            'showevidenceflow' => 0,
            'showintegritynotice' => 1,
            'showrecognitionnotice' => 0,
        ]);

        $format = course_get_format($course);

        $this->assertTrue($format->show_canon());
        $this->assertFalse($format->show_evidence_flow());
        $this->assertTrue($format->show_integrity_notice());
        $this->assertFalse($format->show_recognition_notice());
    }

    /**
     * Test view URL for whole course.
     */
    public function test_get_view_url_for_course(): void {
        $this->resetAfterTest(true);

        $course = $this->getDataGenerator()->create_course([
            'format' => 'uckk',
        ]);

        $format = course_get_format($course);
        $url = $format->get_view_url(0);

        $this->assertInstanceOf(moodle_url::class, $url);
        $this->assertStringContainsString('/course/view.php', $url->out(false));
        $this->assertStringContainsString('id=' . $course->id, $url->out(false));
        $this->assertStringNotContainsString('section=', $url->out(false));
    }

    /**
     * Test view URL for specific section.
     */
    public function test_get_view_url_for_section(): void {
        $this->resetAfterTest(true);

        $course = $this->getDataGenerator()->create_course([
            'format' => 'uckk',
            'numsections' => 8,
        ]);

        $format = course_get_format($course);
        $url = $format->get_view_url(4);

        $this->assertInstanceOf(moodle_url::class, $url);
        $this->assertStringContainsString('/course/view.php', $url->out(false));
        $this->assertStringContainsString('id=' . $course->id, $url->out(false));
        $this->assertStringContainsString('section=4', $url->out(false));
    }

    /**
     * Test page title.
     */
    public function test_page_title_returns_course_fullname(): void {
        $this->resetAfterTest(true);

        $course = $this->getDataGenerator()->create_course([
            'format' => 'uckk',
            'fullname' => 'Cours UCKK de test',
        ]);

        $format = course_get_format($course);

        $this->assertSame('Cours UCKK de test', $format->page_title());
    }

    /**
     * Test default blocks returns a non-empty string.
     */
    public function test_get_default_blocks_returns_non_empty_string(): void {
        $this->resetAfterTest(true);

        $format = $this->create_uckk_format();

        $this->assertNotEmpty($format->get_default_blocks());
        $this->assertStringContainsString('completionstatus', $format->get_default_blocks());
    }

    /**
     * Test export for template.
     */
    public function test_export_for_template_contains_canonical_structure(): void {
        $this->resetAfterTest(true);

        $course = $this->getDataGenerator()->create_course([
            'format' => 'uckk',
            'shortname' => 'UCKK-TC101',
            'idnumber' => 'UCKK-TC101',
            'showcanon' => 1,
            'showevidenceflow' => 1,
            'showintegritynotice' => 1,
            'showrecognitionnotice' => 1,
        ]);

        $format = course_get_format($course);
        $export = $format->export_for_template();

        $this->assertSame((int)$course->id, $export['courseid']);
        $this->assertSame('tronccommun', $export['mode']);
        $this->assertTrue($export['istronccommun']);
        $this->assertFalse($export['isprogramcourse']);
        $this->assertTrue($export['showcanon']);
        $this->assertTrue($export['showevidenceflow']);
        $this->assertTrue($export['showintegritynotice']);
        $this->assertTrue($export['showrecognitionnotice']);

        $this->assertCount(9, $export['sections']);
        $this->assertSame(0, $export['sections'][0]['sectionnum']);
        $this->assertSame('orientation', $export['sections'][0]['kind']);
        $this->assertSame('preuves', $export['sections'][4]['kind']);
        $this->assertSame('archive', $export['sections'][8]['kind']);

        $this->assertFalse($export['sections'][0]['requiresproof']);
        $this->assertTrue($export['sections'][4]['requiresproof']);
        $this->assertTrue($export['sections'][6]['requiresproof']);

        $this->assertTrue($export['sections'][4]['archivable']);
        $this->assertTrue($export['sections'][8]['archivable']);

        $this->assertTrue($export['sections'][4]['integritysensitive']);
        $this->assertTrue($export['sections'][5]['integritysensitive']);
        $this->assertTrue($export['sections'][8]['integritysensitive']);
    }

    /**
     * Test delete_format_data is intentionally a no-op.
     */
    public function test_delete_format_data_is_safe_noop(): void {
        $this->resetAfterTest(true);

        $format = $this->create_uckk_format();

        $format->delete_format_data();

        $this->assertTrue(true);
    }

    /**
     * Test courseblueprint canonical sections.
     */
    public function test_courseblueprint_sections_are_canonical(): void {
        $sections = courseblueprint::sections();

        $this->assertCount(9, $sections);
        $this->assertSame('orientation', $sections[0]['kind']);
        $this->assertSame('concepts', $sections[1]['kind']);
        $this->assertSame('canon', $sections[2]['kind']);
        $this->assertSame('atelier', $sections[3]['kind']);
        $this->assertSame('preuves', $sections[4]['kind']);
        $this->assertSame('deliberation', $sections[5]['kind']);
        $this->assertSame('livrable', $sections[6]['kind']);
        $this->assertSame('evaluation', $sections[7]['kind']);
        $this->assertSame('archive', $sections[8]['kind']);
    }

    /**
     * Test courseblueprint validates known section kinds.
     */
    public function test_courseblueprint_validates_section_kinds(): void {
        $this->assertTrue(courseblueprint::is_valid_section_kind('orientation'));
        $this->assertTrue(courseblueprint::is_valid_section_kind('preuves'));
        $this->assertTrue(courseblueprint::is_valid_section_kind('deliberation'));
        $this->assertTrue(courseblueprint::is_valid_section_kind('archive'));
        $this->assertTrue(courseblueprint::is_valid_section_kind('custom'));

        $this->assertFalse(courseblueprint::is_valid_section_kind('not_a_section'));
    }

    /**
     * Test courseblueprint normalises modes.
     */
    public function test_courseblueprint_normalises_modes(): void {
        $this->assertSame('standard', courseblueprint::normalise_mode('standard'));
        $this->assertSame('tronccommun', courseblueprint::normalise_mode('tronccommun'));
        $this->assertSame('program', courseblueprint::normalise_mode('program'));
        $this->assertSame('lab', courseblueprint::normalise_mode('lab'));

        $this->assertSame('standard', courseblueprint::normalise_mode('unknown'));
    }

    /**
     * Test courseblueprint normalises section kinds.
     */
    public function test_courseblueprint_normalises_section_kinds(): void {
        $this->assertSame('orientation', courseblueprint::normalise_section_kind('orientation'));
        $this->assertSame('preuves', courseblueprint::normalise_section_kind('preuves'));
        $this->assertSame('deliberation', courseblueprint::normalise_section_kind('deliberation'));
        $this->assertSame('archive', courseblueprint::normalise_section_kind('archive'));

        $this->assertSame('custom', courseblueprint::normalise_section_kind('unknown'));
    }

    /**
     * Test courseblueprint default course options.
     */
    public function test_courseblueprint_default_course_options(): void {
        $standard = courseblueprint::default_course_options('standard');
        $this->assertSame('standard', $standard['uckkmode']);
        $this->assertSame(1, $standard['showcanon']);
        $this->assertSame(1, $standard['showevidenceflow']);
        $this->assertSame(0, $standard['showintegritynotice']);
        $this->assertSame(1, $standard['showrecognitionnotice']);

        $tronccommun = courseblueprint::default_course_options('tronccommun');
        $this->assertSame('tronccommun', $tronccommun['uckkmode']);
        $this->assertSame(1, $tronccommun['showintegritynotice']);

        $program = courseblueprint::default_course_options('program');
        $this->assertSame('program', $program['uckkmode']);
        $this->assertSame(1, $program['showintegritynotice']);
    }

    /**
     * Test courseblueprint default section options.
     */
    public function test_courseblueprint_default_section_options(): void {
        $orientation = courseblueprint::default_section_options(0);
        $this->assertSame('orientation', $orientation['uckksectionkind']);
        $this->assertSame(0, $orientation['requiresproof']);
        $this->assertSame(0, $orientation['archivable']);
        $this->assertSame(0, $orientation['integritysensitive']);

        $proofs = courseblueprint::default_section_options(4);
        $this->assertSame('preuves', $proofs['uckksectionkind']);
        $this->assertSame(1, $proofs['requiresproof']);
        $this->assertSame(1, $proofs['archivable']);
        $this->assertSame(1, $proofs['integritysensitive']);

        $archive = courseblueprint::default_section_options(8);
        $this->assertSame('archive', $archive['uckksectionkind']);
        $this->assertSame(0, $archive['requiresproof']);
        $this->assertSame(1, $archive['archivable']);
        $this->assertSame(1, $archive['integritysensitive']);
    }

    /**
     * Test courseblueprint mode inference from course identifiers.
     */
    public function test_courseblueprint_infers_mode_from_course(): void {
        $tc = (object)[
            'shortname' => 'UCKK-TC107',
            'idnumber' => '',
        ];
        $this->assertSame('tronccommun', courseblueprint::infer_mode_from_course($tc));

        $program = (object)[
            'shortname' => 'UCKK-GJS101',
            'idnumber' => '',
        ];
        $this->assertSame('program', courseblueprint::infer_mode_from_course($program));

        $standard = (object)[
            'shortname' => 'OTHER101',
            'idnumber' => '',
        ];
        $this->assertSame('standard', courseblueprint::infer_mode_from_course($standard));
    }

    /**
     * Test courseblueprint profile export.
     */
    public function test_courseblueprint_export_for_template(): void {
        $export = courseblueprint::export_for_template('tronccommun');

        $this->assertSame('tronccommun', $export['profile']);
        $this->assertSame(9, $export['sectioncount']);
        $this->assertTrue($export['hassections']);
        $this->assertTrue($export['requiresproof']);
        $this->assertTrue($export['archivable']);
        $this->assertTrue($export['integritysensitive']);

        $this->assertSame('orientation', $export['sections'][0]['kind']);
        $this->assertSame('preuves', $export['sections'][4]['kind']);
        $this->assertSame('archive', $export['sections'][8]['kind']);

        $this->assertTrue($export['sections'][4]['requiresproof']);
        $this->assertTrue($export['sections'][4]['archivable']);
        $this->assertTrue($export['sections'][4]['integritysensitive']);
        $this->assertTrue($export['sections'][4]['hasrecommendedactivities']);
    }

    /**
     * Test courseblueprint language keys.
     */
    public function test_courseblueprint_language_keys(): void {
        $this->assertSame('course_orientation', courseblueprint::language_key_for_section(0));
        $this->assertSame('course_proofs', courseblueprint::language_key_for_section(4));
        $this->assertSame('course_archive', courseblueprint::language_key_for_section(8));

        $this->assertSame('course_orientation', courseblueprint::language_key_for_kind('orientation'));
        $this->assertSame('course_proofs', courseblueprint::language_key_for_kind('preuves'));
        $this->assertSame('course_archive', courseblueprint::language_key_for_kind('archive'));
        $this->assertSame('sectionname', courseblueprint::language_key_for_kind('unknown'));
    }

    /**
     * Test the canonical Moodle context is available for UCKK courses.
     */
    public function test_course_context_is_available(): void {
        $this->resetAfterTest(true);

        $course = $this->getDataGenerator()->create_course([
            'format' => 'uckk',
            'shortname' => 'UCKK-CONTEXT',
            'idnumber' => 'UCKK-CONTEXT',
        ]);

        $context = context_course::instance($course->id);

        $this->assertSame((int)$course->id, (int)$context->instanceid);
        $this->assertSame(CONTEXT_COURSE, (int)$context->contextlevel);
    }

    /**
     * Create a Moodle course using the UCKK course format and return its format instance.
     *
     * @param array $overrides Course overrides.
     * @return \format_uckk
     */
    private function create_uckk_format(array $overrides = []): \format_uckk {
        $course = $this->getDataGenerator()->create_course(array_merge([
            'format' => 'uckk',
            'numsections' => 8,
            'fullname' => 'UCKK Test Course',
            'shortname' => 'UCKK-TEST',
            'idnumber' => 'UCKK-TEST',
        ], $overrides));

        $format = course_get_format($course);

        $this->assertInstanceOf(\format_uckk::class, $format);

        return $format;
    }
}