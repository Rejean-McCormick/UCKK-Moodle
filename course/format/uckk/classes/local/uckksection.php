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
 * UCKK section value object for the UCKK course format.
 *
 * This class adapts Moodle course section data to the canonical UCKK section
 * model. It does not write to the database, update course sections, evaluate
 * completion, validate evidence, handle integrity cases, or apply permissions.
 *
 * Moodle remains responsible for course sections. UCKK adds a semantic layer:
 * Orientation, Concepts, Matière canonique, Atelier, Preuves, Délibération,
 * Livrable, Évaluation and Archive.
 *
 * @package    format_uckk
 * @copyright  2026 Univers-Cité King Klown
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_uckk\local;

use context_course;
use section_info;
use stdClass;

defined('MOODLE_INTERNAL') || die();

/**
 * UCKK section adapter.
 *
 * This object is intentionally immutable from the outside. Use constructors
 * such as from_section_info(), from_record(), or from_number().
 *
 * @package    format_uckk
 */
final class uckksection {
    /** @var int Moodle course id. */
    private int $courseid;

    /** @var int Moodle course section id. */
    private int $id;

    /** @var int Moodle section number. */
    private int $number;

    /** @var string|null Raw section name. */
    private ?string $name;

    /** @var string Raw summary. */
    private string $summary;

    /** @var int Summary format. */
    private int $summaryformat;

    /** @var bool Section visible flag. */
    private bool $visible;

    /** @var bool User-visible flag, after Moodle availability rules. */
    private bool $uservisible;

    /** @var string Availability information prepared by Moodle, if any. */
    private string $availableinfo;

    /** @var string|null Raw sequence of course modules. */
    private ?string $sequence;

    /** @var array<string, mixed> UCKK semantic definition from sectionmap. */
    private array $definition;

    /**
     * Constructor.
     *
     * @param int $courseid Moodle course id.
     * @param int $id Moodle section id.
     * @param int $number Section number.
     * @param string|null $name Raw section name.
     * @param string $summary Raw summary.
     * @param int $summaryformat Summary format.
     * @param bool $visible Whether section is visible.
     * @param bool $uservisible Whether section is visible to current user.
     * @param string $availableinfo Availability information.
     * @param string|null $sequence Comma-separated cm ids.
     */
    private function __construct(
        int $courseid,
        int $id,
        int $number,
        ?string $name,
        string $summary,
        int $summaryformat,
        bool $visible,
        bool $uservisible,
        string $availableinfo,
        ?string $sequence
    ) {
        $this->courseid = $courseid;
        $this->id = $id;
        $this->number = $number;
        $this->name = $name !== null && trim($name) !== '' ? trim($name) : null;
        $this->summary = $summary;
        $this->summaryformat = $summaryformat;
        $this->visible = $visible;
        $this->uservisible = $uservisible;
        $this->availableinfo = $availableinfo;
        $this->sequence = $sequence;

        $this->definition = sectionmap::get_by_number($number) ?? self::get_custom_definition($number);
    }

    /**
     * Create from Moodle section_info.
     *
     * @param section_info $section Moodle section info.
     * @return self
     */
    public static function from_section_info(section_info $section): self {
        return new self(
            (int)($section->course ?? 0),
            (int)($section->id ?? 0),
            (int)($section->section ?? 0),
            isset($section->name) ? (string)$section->name : null,
            isset($section->summary) ? (string)$section->summary : '',
            isset($section->summaryformat) ? (int)$section->summaryformat : FORMAT_HTML,
            isset($section->visible) ? (bool)$section->visible : true,
            isset($section->uservisible) ? (bool)$section->uservisible : true,
            isset($section->availableinfo) ? (string)$section->availableinfo : '',
            isset($section->sequence) ? (string)$section->sequence : null
        );
    }

    /**
     * Create from a generic section record.
     *
     * This is useful for tests, seed previews and records from course_sections.
     *
     * @param stdClass $record Section-like record.
     * @return self
     */
    public static function from_record(stdClass $record): self {
        return new self(
            (int)($record->course ?? $record->courseid ?? 0),
            (int)($record->id ?? 0),
            (int)($record->section ?? $record->number ?? 0),
            isset($record->name) ? (string)$record->name : null,
            isset($record->summary) ? (string)$record->summary : '',
            isset($record->summaryformat) ? (int)$record->summaryformat : FORMAT_HTML,
            isset($record->visible) ? (bool)$record->visible : true,
            isset($record->uservisible) ? (bool)$record->uservisible : true,
            isset($record->availableinfo) ? (string)$record->availableinfo : '',
            isset($record->sequence) ? (string)$record->sequence : null
        );
    }

    /**
     * Create a virtual section from a canonical section number.
     *
     * This is useful for seed previews before Moodle sections exist.
     *
     * @param int $number Section number.
     * @param int $courseid Course id.
     * @return self
     */
    public static function from_number(int $number, int $courseid = 0): self {
        return new self(
            $courseid,
            0,
            $number,
            null,
            '',
            FORMAT_HTML,
            true,
            true,
            '',
            null
        );
    }

    /**
     * Return Moodle course id.
     *
     * @return int
     */
    public function get_courseid(): int {
        return $this->courseid;
    }

    /**
     * Return Moodle section id.
     *
     * @return int
     */
    public function get_id(): int {
        return $this->id;
    }

    /**
     * Return Moodle section number.
     *
     * @return int
     */
    public function get_number(): int {
        return $this->number;
    }

    /**
     * Return stable UCKK section key.
     *
     * @return string
     */
    public function get_key(): string {
        return (string)$this->definition['key'];
    }

    /**
     * Return raw section name, if explicitly set.
     *
     * @return string|null
     */
    public function get_raw_name(): ?string {
        return $this->name;
    }

    /**
     * Return display name.
     *
     * If the teacher renamed the section, the custom name is used. Otherwise,
     * the canonical UCKK section title is used.
     *
     * @return string
     */
    public function get_display_name(): string {
        if ($this->name !== null) {
            return format_string($this->name, true);
        }

        return sectionmap::get_title($this->number);
    }

    /**
     * Return canonical title, ignoring custom Moodle section name.
     *
     * @return string
     */
    public function get_canonical_title(): string {
        return sectionmap::get_title($this->number);
    }

    /**
     * Return section description from the canonical map.
     *
     * @return string
     */
    public function get_description(): string {
        return sectionmap::get_description($this->number);
    }

    /**
     * Return section purpose from the canonical map.
     *
     * @return string
     */
    public function get_purpose(): string {
        return sectionmap::get_purpose($this->number);
    }

    /**
     * Return raw section summary.
     *
     * @return string
     */
    public function get_summary(): string {
        return $this->summary;
    }

    /**
     * Return formatted section summary.
     *
     * @param context_course|null $context Optional course context.
     * @return string
     */
    public function get_formatted_summary(?context_course $context = null): string {
        if ($this->summary === '') {
            return '';
        }

        $options = [
            'overflowdiv' => true,
            'noclean' => false,
        ];

        if ($context !== null) {
            $options['context'] = $context;
        }

        return format_text($this->summary, $this->summaryformat, $options);
    }

    /**
     * Return summary format.
     *
     * @return int
     */
    public function get_summary_format(): int {
        return $this->summaryformat;
    }

    /**
     * Determine whether this section has a raw summary.
     *
     * @return bool
     */
    public function has_summary(): bool {
        return trim($this->summary) !== '';
    }

    /**
     * Determine whether section has an explicit teacher-defined name.
     *
     * @return bool
     */
    public function has_custom_name(): bool {
        return $this->name !== null;
    }

    /**
     * Determine whether the section is a canonical UCKK section.
     *
     * @return bool
     */
    public function is_standard(): bool {
        return sectionmap::is_standard_section($this->number);
    }

    /**
     * Determine whether this is the orientation section.
     *
     * @return bool
     */
    public function is_orientation(): bool {
        return $this->number === sectionmap::SECTION_ORIENTATION;
    }

    /**
     * Determine whether this is the concepts section.
     *
     * @return bool
     */
    public function is_concepts(): bool {
        return $this->number === sectionmap::SECTION_CONCEPTS;
    }

    /**
     * Determine whether this is the canon section.
     *
     * @return bool
     */
    public function is_canon(): bool {
        return $this->number === sectionmap::SECTION_CANON;
    }

    /**
     * Determine whether this is the workshop section.
     *
     * @return bool
     */
    public function is_workshop(): bool {
        return $this->number === sectionmap::SECTION_WORKSHOP;
    }

    /**
     * Determine whether this is the proofs section.
     *
     * @return bool
     */
    public function is_proofs(): bool {
        return $this->number === sectionmap::SECTION_PROOFS;
    }

    /**
     * Determine whether this is the deliberation section.
     *
     * @return bool
     */
    public function is_deliberation(): bool {
        return $this->number === sectionmap::SECTION_DELIBERATION;
    }

    /**
     * Determine whether this is the deliverable section.
     *
     * @return bool
     */
    public function is_deliverable(): bool {
        return $this->number === sectionmap::SECTION_DELIVERABLE;
    }

    /**
     * Determine whether this is the evaluation section.
     *
     * @return bool
     */
    public function is_evaluation(): bool {
        return $this->number === sectionmap::SECTION_EVALUATION;
    }

    /**
     * Determine whether this is the archive section.
     *
     * @return bool
     */
    public function is_archive(): bool {
        return $this->number === sectionmap::SECTION_ARCHIVE;
    }

    /**
     * Determine whether the section is expected to carry evidence.
     *
     * @return bool
     */
    public function is_evidencebearing(): bool {
        return sectionmap::is_evidencebearing($this->number);
    }

    /**
     * Determine whether the section is deliberative.
     *
     * @return bool
     */
    public function is_deliberative(): bool {
        return sectionmap::is_deliberative($this->number);
    }

    /**
     * Determine whether the section is archival.
     *
     * @return bool
     */
    public function is_archival(): bool {
        return sectionmap::is_archival($this->number);
    }

    /**
     * Determine whether the section is visible in Moodle.
     *
     * @return bool
     */
    public function is_visible(): bool {
        return $this->visible;
    }

    /**
     * Determine whether the section is visible to the current user.
     *
     * @return bool
     */
    public function is_user_visible(): bool {
        return $this->uservisible;
    }

    /**
     * Determine whether the section is hidden from current user.
     *
     * @return bool
     */
    public function is_hidden(): bool {
        return !$this->visible || !$this->uservisible;
    }

    /**
     * Return availability information.
     *
     * @return string
     */
    public function get_available_info(): string {
        return $this->availableinfo;
    }

    /**
     * Determine whether Moodle has availability information for this section.
     *
     * @return bool
     */
    public function has_available_info(): bool {
        return trim($this->availableinfo) !== '';
    }

    /**
     * Return raw course module sequence.
     *
     * @return string|null
     */
    public function get_sequence(): ?string {
        return $this->sequence;
    }

    /**
     * Return course module ids in this section.
     *
     * @return int[]
     */
    public function get_course_module_ids(): array {
        if ($this->sequence === null || trim($this->sequence) === '') {
            return [];
        }

        $ids = array_filter(array_map('trim', explode(',', $this->sequence)), static function(string $value): bool {
            return $value !== '' && ctype_digit($value);
        });

        return array_values(array_map('intval', $ids));
    }

    /**
     * Determine whether section contains course modules.
     *
     * @return bool
     */
    public function has_course_modules(): bool {
        return !empty($this->get_course_module_ids());
    }

    /**
     * Return UCKK CSS classes.
     *
     * These classes are for styling only. Moodle reactive behavior must rely on
     * the data attributes exported by get_data_attributes().
     *
     * @param string[] $extra Extra classes.
     * @return string
     */
    public function get_css_classes(array $extra = []): string {
        $classes = [
            'uckk-section',
            sectionmap::get_css_class($this->number),
            'uckk-section-' . $this->get_key(),
        ];

        if (!$this->is_standard()) {
            $classes[] = 'uckk-section-custom';
        }

        if ($this->is_hidden()) {
            $classes[] = 'uckk-section-hidden';
        }

        if ($this->is_evidencebearing()) {
            $classes[] = 'uckk-section-evidencebearing';
        }

        if ($this->is_deliberative()) {
            $classes[] = 'uckk-section-deliberative';
        }

        if ($this->is_archival()) {
            $classes[] = 'uckk-section-archival';
        }

        $classes = array_merge($classes, $extra);
        $classes = array_filter(array_map('trim', $classes));

        return implode(' ', array_unique($classes));
    }

    /**
     * Return icon key.
     *
     * @return string
     */
    public function get_icon_key(): string {
        return sectionmap::get_icon_key($this->number);
    }

    /**
     * Return Moodle reactive data attributes for a section wrapper.
     *
     * Moodle course format components use data attributes to identify sections.
     *
     * @return array<string, string|int>
     */
    public function get_data_attributes(): array {
        $attributes = [
            'data-for' => 'section',
            'data-id' => $this->id,
            'data-number' => $this->number,
            'data-uckk-section-key' => $this->get_key(),
        ];

        if ($this->courseid > 0) {
            $attributes['data-courseid'] = $this->courseid;
        }

        return $attributes;
    }

    /**
     * Return Moodle reactive data attributes for a section title.
     *
     * @return array<string, string|int>
     */
    public function get_title_data_attributes(): array {
        return [
            'data-for' => 'section_title',
            'data-id' => $this->id,
            'data-number' => $this->number,
            'data-uckk-section-key' => $this->get_key(),
        ];
    }

    /**
     * Return Moodle reactive data attributes for a section info area.
     *
     * @return array<string, string|int>
     */
    public function get_info_data_attributes(): array {
        return [
            'data-for' => 'sectioninfo',
            'data-id' => $this->id,
            'data-number' => $this->number,
            'data-uckk-section-key' => $this->get_key(),
        ];
    }

    /**
     * Return section attributes as Mustache-friendly list.
     *
     * @return array<int, array{name: string, value: string|int}>
     */
    public function get_data_attributes_for_template(): array {
        return self::attributes_to_template($this->get_data_attributes());
    }

    /**
     * Return title attributes as Mustache-friendly list.
     *
     * @return array<int, array{name: string, value: string|int}>
     */
    public function get_title_data_attributes_for_template(): array {
        return self::attributes_to_template($this->get_title_data_attributes());
    }

    /**
     * Export this section for Mustache templates.
     *
     * @param context_course|null $context Optional course context for formatted summary.
     * @param array<string, mixed> $overrides Optional prepared overrides.
     * @return array<string, mixed>
     */
    public function export_for_template(?context_course $context = null, array $overrides = []): array {
        $summary = $this->get_formatted_summary($context);

        $data = [
            'id' => $this->id,
            'courseid' => $this->courseid,
            'number' => $this->number,
            'index' => $this->number,
            'key' => $this->get_key(),
            'name' => $this->get_display_name(),
            'canonicaltitle' => $this->get_canonical_title(),
            'hascustomname' => $this->has_custom_name(),
            'description' => $this->get_description(),
            'hasdescription' => $this->get_description() !== '',
            'purpose' => $this->get_purpose(),
            'haspurpose' => $this->get_purpose() !== '',
            'summary' => $summary,
            'hassummary' => $summary !== '',
            'rawsummary' => $this->summary,
            'summaryformat' => $this->summaryformat,
            'availableinfo' => $this->availableinfo,
            'hasavailableinfo' => $this->has_available_info(),
            'sequence' => $this->sequence,
            'hassequence' => $this->sequence !== null && $this->sequence !== '',
            'cmids' => $this->get_course_module_ids(),
            'hascms' => $this->has_course_modules(),
            'cssclass' => $this->get_css_classes(),
            'icon' => $this->get_icon_key(),
            'visible' => $this->visible,
            'uservisible' => $this->uservisible,
            'hidden' => $this->is_hidden(),
            'isstandard' => $this->is_standard(),
            'isorientation' => $this->is_orientation(),
            'isconcepts' => $this->is_concepts(),
            'iscanon' => $this->is_canon(),
            'isworkshop' => $this->is_workshop(),
            'isproofs' => $this->is_proofs(),
            'isdeliberation' => $this->is_deliberation(),
            'isdeliverable' => $this->is_deliverable(),
            'isevaluation' => $this->is_evaluation(),
            'isarchive' => $this->is_archive(),
            'evidencebearing' => $this->is_evidencebearing(),
            'deliberative' => $this->is_deliberative(),
            'archival' => $this->is_archival(),
            'dataattributes' => $this->get_data_attributes_for_template(),
            'titledataattributes' => $this->get_title_data_attributes_for_template(),
            'infodataattributes' => self::attributes_to_template($this->get_info_data_attributes()),
        ];

        return array_merge($data, $overrides);
    }

    /**
     * Convert to a generic object.
     *
     * @return stdClass
     */
    public function to_record(): stdClass {
        return (object)[
            'id' => $this->id,
            'course' => $this->courseid,
            'section' => $this->number,
            'name' => $this->name,
            'summary' => $this->summary,
            'summaryformat' => $this->summaryformat,
            'visible' => $this->visible ? 1 : 0,
            'uservisible' => $this->uservisible ? 1 : 0,
            'availableinfo' => $this->availableinfo,
            'sequence' => $this->sequence,
        ];
    }

    /**
     * Convert an attributes array into a Mustache-friendly list.
     *
     * @param array<string, string|int|bool> $attributes Attributes.
     * @return array<int, array{name: string, value: string|int}>
     */
    private static function attributes_to_template(array $attributes): array {
        $items = [];

        foreach ($attributes as $name => $value) {
            if ($value === null || $value === false) {
                continue;
            }

            $items[] = [
                'name' => $name,
                'value' => is_bool($value) ? 1 : $value,
            ];
        }

        return $items;
    }

    /**
     * Return a custom fallback definition for non-canonical sections.
     *
     * @param int $number Section number.
     * @return array<string, mixed>
     */
    private static function get_custom_definition(int $number): array {
        return [
            'number' => $number,
            'key' => 'custom',
            'titlekey' => '',
            'descriptionkey' => '',
            'purposekey' => '',
            'cssclass' => 'uckk-section-custom',
            'icon' => 'section',
            'required' => false,
            'evidencebearing' => false,
            'deliberative' => false,
            'archival' => false,
        ];
    }
}
