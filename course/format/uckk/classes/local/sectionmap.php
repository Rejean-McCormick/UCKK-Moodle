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
 * Canonical section map for the UCKK course format.
 *
 * This class defines the stable section structure used by format_uckk.
 * It must remain free of database writes, permissions, grading decisions,
 * archive validation, integrity review logic, or workflow state changes.
 *
 * Moodle course sections are still Moodle course sections. This class only
 * provides the UCKK semantic layer used by the course format renderer,
 * templates, seed tool, reports, and tests.
 *
 * @package    format_uckk
 * @copyright  2026 Univers-Cité King Klown
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_uckk\local;

defined('MOODLE_INTERNAL') || die();

/**
 * Canonical UCKK section map.
 *
 * The section numbers are intentionally stable. Section 0 is the general
 * course section in Moodle, and UCKK uses it as the course orientation gate.
 *
 * @package    format_uckk
 */
final class sectionmap {
    /** Section number: Orientation. */
    public const SECTION_ORIENTATION = 0;

    /** Section number: Concepts. */
    public const SECTION_CONCEPTS = 1;

    /** Section number: Matière canonique. */
    public const SECTION_CANON = 2;

    /** Section number: Atelier. */
    public const SECTION_WORKSHOP = 3;

    /** Section number: Preuves. */
    public const SECTION_PROOFS = 4;

    /** Section number: Délibération. */
    public const SECTION_DELIBERATION = 5;

    /** Section number: Livrable. */
    public const SECTION_DELIVERABLE = 6;

    /** Section number: Évaluation. */
    public const SECTION_EVALUATION = 7;

    /** Section number: Archive. */
    public const SECTION_ARCHIVE = 8;

    /** Stable key: Orientation. */
    public const KEY_ORIENTATION = 'orientation';

    /** Stable key: Concepts. */
    public const KEY_CONCEPTS = 'concepts';

    /** Stable key: Matière canonique. */
    public const KEY_CANON = 'canon';

    /** Stable key: Atelier. */
    public const KEY_WORKSHOP = 'atelier';

    /** Stable key: Preuves. */
    public const KEY_PROOFS = 'preuves';

    /** Stable key: Délibération. */
    public const KEY_DELIBERATION = 'deliberation';

    /** Stable key: Livrable. */
    public const KEY_DELIVERABLE = 'livrable';

    /** Stable key: Évaluation. */
    public const KEY_EVALUATION = 'evaluation';

    /** Stable key: Archive. */
    public const KEY_ARCHIVE = 'archive';

    /** Component name. */
    public const COMPONENT = 'format_uckk';

    /** First UCKK section number. */
    public const FIRST_SECTION = self::SECTION_ORIENTATION;

    /** Last UCKK section number. */
    public const LAST_SECTION = self::SECTION_ARCHIVE;

    /** Number of canonical UCKK sections. */
    public const SECTION_COUNT = 9;

    /**
     * Return the canonical section map.
     *
     * The returned structure is intentionally simple so it can be reused by
     * format.php, renderers, output classes, tests, seeders and CLI tools.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function get_map(): array {
        return [
            self::SECTION_ORIENTATION => [
                'number' => self::SECTION_ORIENTATION,
                'key' => self::KEY_ORIENTATION,
                'titlekey' => 'section_orientation',
                'descriptionkey' => 'section_orientation_desc',
                'purposekey' => 'section_orientation_purpose',
                'cssclass' => 'uckk-section-orientation',
                'icon' => 'orientation',
                'required' => true,
                'evidencebearing' => false,
                'deliberative' => false,
                'archival' => false,
            ],
            self::SECTION_CONCEPTS => [
                'number' => self::SECTION_CONCEPTS,
                'key' => self::KEY_CONCEPTS,
                'titlekey' => 'section_concepts',
                'descriptionkey' => 'section_concepts_desc',
                'purposekey' => 'section_concepts_purpose',
                'cssclass' => 'uckk-section-concepts',
                'icon' => 'concepts',
                'required' => true,
                'evidencebearing' => false,
                'deliberative' => false,
                'archival' => false,
            ],
            self::SECTION_CANON => [
                'number' => self::SECTION_CANON,
                'key' => self::KEY_CANON,
                'titlekey' => 'section_canon',
                'descriptionkey' => 'section_canon_desc',
                'purposekey' => 'section_canon_purpose',
                'cssclass' => 'uckk-section-canon',
                'icon' => 'canon',
                'required' => true,
                'evidencebearing' => false,
                'deliberative' => false,
                'archival' => false,
            ],
            self::SECTION_WORKSHOP => [
                'number' => self::SECTION_WORKSHOP,
                'key' => self::KEY_WORKSHOP,
                'titlekey' => 'section_workshop',
                'descriptionkey' => 'section_workshop_desc',
                'purposekey' => 'section_workshop_purpose',
                'cssclass' => 'uckk-section-workshop',
                'icon' => 'workshop',
                'required' => true,
                'evidencebearing' => true,
                'deliberative' => false,
                'archival' => false,
            ],
            self::SECTION_PROOFS => [
                'number' => self::SECTION_PROOFS,
                'key' => self::KEY_PROOFS,
                'titlekey' => 'section_proofs',
                'descriptionkey' => 'section_proofs_desc',
                'purposekey' => 'section_proofs_purpose',
                'cssclass' => 'uckk-section-proofs',
                'icon' => 'proofs',
                'required' => true,
                'evidencebearing' => true,
                'deliberative' => false,
                'archival' => false,
            ],
            self::SECTION_DELIBERATION => [
                'number' => self::SECTION_DELIBERATION,
                'key' => self::KEY_DELIBERATION,
                'titlekey' => 'section_deliberation',
                'descriptionkey' => 'section_deliberation_desc',
                'purposekey' => 'section_deliberation_purpose',
                'cssclass' => 'uckk-section-deliberation',
                'icon' => 'assembly',
                'required' => true,
                'evidencebearing' => true,
                'deliberative' => true,
                'archival' => false,
            ],
            self::SECTION_DELIVERABLE => [
                'number' => self::SECTION_DELIVERABLE,
                'key' => self::KEY_DELIVERABLE,
                'titlekey' => 'section_deliverable',
                'descriptionkey' => 'section_deliverable_desc',
                'purposekey' => 'section_deliverable_purpose',
                'cssclass' => 'uckk-section-deliverable',
                'icon' => 'deliverable',
                'required' => true,
                'evidencebearing' => true,
                'deliberative' => false,
                'archival' => false,
            ],
            self::SECTION_EVALUATION => [
                'number' => self::SECTION_EVALUATION,
                'key' => self::KEY_EVALUATION,
                'titlekey' => 'section_evaluation',
                'descriptionkey' => 'section_evaluation_desc',
                'purposekey' => 'section_evaluation_purpose',
                'cssclass' => 'uckk-section-evaluation',
                'icon' => 'evaluation',
                'required' => true,
                'evidencebearing' => true,
                'deliberative' => false,
                'archival' => false,
            ],
            self::SECTION_ARCHIVE => [
                'number' => self::SECTION_ARCHIVE,
                'key' => self::KEY_ARCHIVE,
                'titlekey' => 'section_archive',
                'descriptionkey' => 'section_archive_desc',
                'purposekey' => 'section_archive_purpose',
                'cssclass' => 'uckk-section-archive',
                'icon' => 'archive',
                'required' => true,
                'evidencebearing' => true,
                'deliberative' => false,
                'archival' => true,
            ],
        ];
    }

    /**
     * Return a section definition by section number.
     *
     * @param int $sectionnumber Section number.
     * @return array<string, mixed>|null
     */
    public static function get_by_number(int $sectionnumber): ?array {
        $map = self::get_map();

        return $map[$sectionnumber] ?? null;
    }

    /**
     * Return a section definition by stable section key.
     *
     * @param string $key Stable section key.
     * @return array<string, mixed>|null
     */
    public static function get_by_key(string $key): ?array {
        $key = self::normalise_key($key);

        foreach (self::get_map() as $section) {
            if ($section['key'] === $key) {
                return $section;
            }
        }

        return null;
    }

    /**
     * Return the section key for a section number.
     *
     * @param int $sectionnumber Section number.
     * @return string|null
     */
    public static function get_key(int $sectionnumber): ?string {
        $section = self::get_by_number($sectionnumber);

        return $section['key'] ?? null;
    }

    /**
     * Return the section number for a stable section key.
     *
     * @param string $key Stable section key.
     * @return int|null
     */
    public static function get_number(string $key): ?int {
        $section = self::get_by_key($key);

        return $section['number'] ?? null;
    }

    /**
     * Return all stable section keys in canonical order.
     *
     * @return string[]
     */
    public static function get_keys(): array {
        return array_values(array_map(static function(array $section): string {
            return $section['key'];
        }, self::get_map()));
    }

    /**
     * Return all canonical section numbers.
     *
     * @return int[]
     */
    public static function get_numbers(): array {
        return array_keys(self::get_map());
    }

    /**
     * Return the last canonical section number.
     *
     * This is useful for format_uckk::get_last_section_number().
     *
     * @return int
     */
    public static function get_last_section_number(): int {
        return self::LAST_SECTION;
    }

    /**
     * Return the maximum number of canonical sections.
     *
     * @return int
     */
    public static function get_section_count(): int {
        return self::SECTION_COUNT;
    }

    /**
     * Determine whether a Moodle section number belongs to the UCKK map.
     *
     * @param int $sectionnumber Section number.
     * @return bool
     */
    public static function is_standard_section(int $sectionnumber): bool {
        return self::get_by_number($sectionnumber) !== null;
    }

    /**
     * Determine whether a stable section key belongs to the UCKK map.
     *
     * @param string $key Stable section key.
     * @return bool
     */
    public static function is_standard_key(string $key): bool {
        return self::get_by_key($key) !== null;
    }

    /**
     * Determine whether a section is expected to carry evidence.
     *
     * @param int $sectionnumber Section number.
     * @return bool
     */
    public static function is_evidencebearing(int $sectionnumber): bool {
        $section = self::get_by_number($sectionnumber);

        return !empty($section['evidencebearing']);
    }

    /**
     * Determine whether a section is deliberative.
     *
     * @param int $sectionnumber Section number.
     * @return bool
     */
    public static function is_deliberative(int $sectionnumber): bool {
        $section = self::get_by_number($sectionnumber);

        return !empty($section['deliberative']);
    }

    /**
     * Determine whether a section is archival.
     *
     * @param int $sectionnumber Section number.
     * @return bool
     */
    public static function is_archival(int $sectionnumber): bool {
        $section = self::get_by_number($sectionnumber);

        return !empty($section['archival']);
    }

    /**
     * Return the display title for a section.
     *
     * @param int $sectionnumber Section number.
     * @return string
     */
    public static function get_title(int $sectionnumber): string {
        $section = self::get_by_number($sectionnumber);

        if ($section === null) {
            return self::get_fallback_title($sectionnumber);
        }

        return self::get_component_string($section['titlekey'], self::get_fallback_title($sectionnumber));
    }

    /**
     * Return the description text for a section.
     *
     * @param int $sectionnumber Section number.
     * @return string
     */
    public static function get_description(int $sectionnumber): string {
        $section = self::get_by_number($sectionnumber);

        if ($section === null) {
            return '';
        }

        return self::get_component_string($section['descriptionkey'], '');
    }

    /**
     * Return the purpose text for a section.
     *
     * @param int $sectionnumber Section number.
     * @return string
     */
    public static function get_purpose(int $sectionnumber): string {
        $section = self::get_by_number($sectionnumber);

        if ($section === null) {
            return '';
        }

        return self::get_component_string($section['purposekey'], '');
    }

    /**
     * Return the CSS class for a section.
     *
     * @param int $sectionnumber Section number.
     * @return string
     */
    public static function get_css_class(int $sectionnumber): string {
        $section = self::get_by_number($sectionnumber);

        if ($section === null) {
            return 'uckk-section-custom';
        }

        return $section['cssclass'];
    }

    /**
     * Return the icon key for a section.
     *
     * The returned value is a symbolic key. Rendering code may map it to pix_url()
     * or to a CSS class depending on the output context.
     *
     * @param int $sectionnumber Section number.
     * @return string
     */
    public static function get_icon_key(int $sectionnumber): string {
        $section = self::get_by_number($sectionnumber);

        if ($section === null) {
            return 'section';
        }

        return $section['icon'];
    }

    /**
     * Build a Mustache-friendly section context.
     *
     * @param int $sectionnumber Section number.
     * @param array<string, mixed> $overrides Optional overrides prepared by renderers.
     * @return array<string, mixed>
     */
    public static function export_for_template(int $sectionnumber, array $overrides = []): array {
        $section = self::get_by_number($sectionnumber);

        $context = [
            'number' => $sectionnumber,
            'index' => $sectionnumber,
            'key' => $section['key'] ?? 'custom',
            'title' => self::get_title($sectionnumber),
            'description' => self::get_description($sectionnumber),
            'purpose' => self::get_purpose($sectionnumber),
            'cssclass' => self::get_css_class($sectionnumber),
            'icon' => self::get_icon_key($sectionnumber),
            'isstandard' => $section !== null,
            'isorientation' => $sectionnumber === self::SECTION_ORIENTATION,
            'isconcepts' => $sectionnumber === self::SECTION_CONCEPTS,
            'iscanon' => $sectionnumber === self::SECTION_CANON,
            'isworkshop' => $sectionnumber === self::SECTION_WORKSHOP,
            'isproofs' => $sectionnumber === self::SECTION_PROOFS,
            'isdeliberation' => $sectionnumber === self::SECTION_DELIBERATION,
            'isdeliverable' => $sectionnumber === self::SECTION_DELIVERABLE,
            'isevaluation' => $sectionnumber === self::SECTION_EVALUATION,
            'isarchive' => $sectionnumber === self::SECTION_ARCHIVE,
            'evidencebearing' => self::is_evidencebearing($sectionnumber),
            'deliberative' => self::is_deliberative($sectionnumber),
            'archival' => self::is_archival($sectionnumber),
        ];

        return array_merge($context, $overrides);
    }

    /**
     * Build a template-ready list of all canonical sections.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function export_all_for_template(): array {
        $sections = [];

        foreach (self::get_numbers() as $sectionnumber) {
            $sections[] = self::export_for_template($sectionnumber);
        }

        return $sections;
    }

    /**
     * Return seed-ready section names.
     *
     * This method is intended for tool_uckkseed or tests. It returns section
     * numbers mapped to their canonical display names.
     *
     * @return array<int, string>
     */
    public static function get_seed_section_names(): array {
        $names = [];

        foreach (self::get_numbers() as $sectionnumber) {
            $names[$sectionnumber] = self::get_title($sectionnumber);
        }

        return $names;
    }

    /**
     * Return a stable fallback title for a section.
     *
     * @param int $sectionnumber Section number.
     * @return string
     */
    private static function get_fallback_title(int $sectionnumber): string {
        $fallbacks = [
            self::SECTION_ORIENTATION => 'Orientation',
            self::SECTION_CONCEPTS => 'Concepts',
            self::SECTION_CANON => 'Matière canonique',
            self::SECTION_WORKSHOP => 'Atelier',
            self::SECTION_PROOFS => 'Preuves',
            self::SECTION_DELIBERATION => 'Délibération',
            self::SECTION_DELIVERABLE => 'Livrable',
            self::SECTION_EVALUATION => 'Évaluation',
            self::SECTION_ARCHIVE => 'Archive',
        ];

        if (array_key_exists($sectionnumber, $fallbacks)) {
            return $fallbacks[$sectionnumber];
        }

        return get_string('section') . ' ' . $sectionnumber;
    }

    /**
     * Resolve a string from format_uckk with safe fallback.
     *
     * @param string $identifier String identifier.
     * @param string $fallback Fallback text.
     * @return string
     */
    private static function get_component_string(string $identifier, string $fallback): string {
        if (get_string_manager()->string_exists($identifier, self::COMPONENT)) {
            return get_string($identifier, self::COMPONENT);
        }

        return $fallback;
    }

    /**
     * Normalise a section key.
     *
     * @param string $key Raw key.
     * @return string
     */
    private static function normalise_key(string $key): string {
        $key = trim(core_text::strtolower($key));
        $key = str_replace('-', '_', $key);

        $aliases = [
            'matiere_canonique' => self::KEY_CANON,
            'canonical' => self::KEY_CANON,
            'canonical_material' => self::KEY_CANON,
            'workshop' => self::KEY_WORKSHOP,
            'proofs' => self::KEY_PROOFS,
            'evidence' => self::KEY_PROOFS,
            'deliverable' => self::KEY_DELIVERABLE,
            'evaluation' => self::KEY_EVALUATION,
            'archive_finale' => self::KEY_ARCHIVE,
        ];

        return $aliases[$key] ?? $key;
    }
}