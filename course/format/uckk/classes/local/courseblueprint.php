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
 * Canonical UCKK course blueprint.
 *
 * This class provides a stable, portable description of the default UCKK course
 * structure. It is used by the course format, output classes, templates, seed
 * tools and tests to keep the same section semantics everywhere.
 *
 * It deliberately stores no Moodle ids, no course module ids, no file ids and no
 * external URLs. Moodle course format options are backed up as course or section
 * fields, so this blueprint must remain portable and deterministic.
 *
 * @package    format_uckk
 * @copyright  2026 Univers-Cité King Klown
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_uckk\local;

defined('MOODLE_INTERNAL') || die();

use coding_exception;
use stdClass;

/**
 * Canonical UCKK course blueprint.
 *
 * The canonical UCKK course sequence is:
 *
 * 0. Orientation
 * 1. Concepts
 * 2. Matière canonique
 * 3. Atelier
 * 4. Preuves
 * 5. Délibération
 * 6. Livrable
 * 7. Évaluation
 * 8. Archive
 *
 * This class is intentionally pure and deterministic.
 */
final class courseblueprint {
    /**
     * UCKK course mode: standard.
     */
    public const MODE_STANDARD = 'standard';

    /**
     * UCKK course mode: tronc commun.
     */
    public const MODE_TRONC_COMMUN = 'tronccommun';

    /**
     * UCKK course mode: internal program.
     */
    public const MODE_PROGRAM = 'program';

    /**
     * UCKK course mode: laboratory or seminar.
     */
    public const MODE_LAB = 'lab';

    /**
     * Section kind: orientation.
     */
    public const SECTION_ORIENTATION = 'orientation';

    /**
     * Section kind: concepts.
     */
    public const SECTION_CONCEPTS = 'concepts';

    /**
     * Section kind: canonical material.
     */
    public const SECTION_CANON = 'canon';

    /**
     * Section kind: workshop.
     */
    public const SECTION_WORKSHOP = 'atelier';

    /**
     * Section kind: proofs.
     */
    public const SECTION_PROOFS = 'preuves';

    /**
     * Section kind: deliberation.
     */
    public const SECTION_DELIBERATION = 'deliberation';

    /**
     * Section kind: final deliverable.
     */
    public const SECTION_DELIVERABLE = 'livrable';

    /**
     * Section kind: evaluation.
     */
    public const SECTION_EVALUATION = 'evaluation';

    /**
     * Section kind: archive.
     */
    public const SECTION_ARCHIVE = 'archive';

    /**
     * Section kind: custom.
     */
    public const SECTION_CUSTOM = 'custom';

    /**
     * Blueprint profile: default UCKK course.
     */
    public const PROFILE_DEFAULT = 'default';

    /**
     * Blueprint profile: tronc commun.
     */
    public const PROFILE_TRONC_COMMUN = 'tronccommun';

    /**
     * Blueprint profile: challenge-heavy course.
     */
    public const PROFILE_CHALLENGE = 'challenge';

    /**
     * Blueprint profile: assembly-heavy course.
     */
    public const PROFILE_ASSEMBLY = 'assembly';

    /**
     * Blueprint profile: archive-heavy course.
     */
    public const PROFILE_ARCHIVE = 'archive';

    /**
     * Return the canonical section definitions.
     *
     * The array key is the default Moodle section number.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function sections(): array {
        return [
            0 => [
                'sectionnum' => 0,
                'kind' => self::SECTION_ORIENTATION,
                'stringkey' => 'course_orientation',
                'titlekey' => 'course_orientation',
                'shortkey' => 'sectionkind_orientation',
                'cssclass' => 'uckk-section-orientation',
                'icon' => 'i/course',
                'requiresproof' => false,
                'archivable' => false,
                'integritysensitive' => false,
                'completioncritical' => true,
                'purpose' => 'course_entry',
                'recommendedactivities' => [
                    'page',
                    'book',
                    'forum',
                ],
            ],
            1 => [
                'sectionnum' => 1,
                'kind' => self::SECTION_CONCEPTS,
                'stringkey' => 'course_concepts',
                'titlekey' => 'course_concepts',
                'shortkey' => 'sectionkind_concepts',
                'cssclass' => 'uckk-section-concepts',
                'icon' => 'i/info',
                'requiresproof' => false,
                'archivable' => false,
                'integritysensitive' => false,
                'completioncritical' => true,
                'purpose' => 'conceptual_foundation',
                'recommendedactivities' => [
                    'page',
                    'glossary',
                    'quiz',
                    'forum',
                ],
            ],
            2 => [
                'sectionnum' => 2,
                'kind' => self::SECTION_CANON,
                'stringkey' => 'course_canon',
                'titlekey' => 'course_canon',
                'shortkey' => 'sectionkind_canon',
                'cssclass' => 'uckk-section-canon',
                'icon' => 'i/book',
                'requiresproof' => false,
                'archivable' => true,
                'integritysensitive' => true,
                'completioncritical' => true,
                'purpose' => 'canonical_material',
                'recommendedactivities' => [
                    'book',
                    'page',
                    'resource',
                    'url',
                ],
            ],
            3 => [
                'sectionnum' => 3,
                'kind' => self::SECTION_WORKSHOP,
                'stringkey' => 'course_workshop',
                'titlekey' => 'course_workshop',
                'shortkey' => 'sectionkind_workshop',
                'cssclass' => 'uckk-section-workshop',
                'icon' => 'i/group',
                'requiresproof' => false,
                'archivable' => true,
                'integritysensitive' => false,
                'completioncritical' => true,
                'purpose' => 'practice_and_application',
                'recommendedactivities' => [
                    'assign',
                    'workshop',
                    'forum',
                    'database',
                ],
            ],
            4 => [
                'sectionnum' => 4,
                'kind' => self::SECTION_PROOFS,
                'stringkey' => 'course_proofs',
                'titlekey' => 'course_proofs',
                'shortkey' => 'sectionkind_proofs',
                'cssclass' => 'uckk-section-proofs',
                'icon' => 'i/checked',
                'requiresproof' => true,
                'archivable' => true,
                'integritysensitive' => true,
                'completioncritical' => true,
                'purpose' => 'evidence_production',
                'recommendedactivities' => [
                    'assign',
                    'database',
                    'uckkarchive',
                    'uckkchallenge',
                ],
            ],
            5 => [
                'sectionnum' => 5,
                'kind' => self::SECTION_DELIBERATION,
                'stringkey' => 'course_deliberation',
                'titlekey' => 'course_deliberation',
                'shortkey' => 'sectionkind_deliberation',
                'cssclass' => 'uckk-section-deliberation',
                'icon' => 'i/forum',
                'requiresproof' => false,
                'archivable' => true,
                'integritysensitive' => true,
                'completioncritical' => true,
                'purpose' => 'collective_deliberation',
                'recommendedactivities' => [
                    'forum',
                    'choice',
                    'workshop',
                    'uckkassembly',
                ],
            ],
            6 => [
                'sectionnum' => 6,
                'kind' => self::SECTION_DELIVERABLE,
                'stringkey' => 'course_deliverable',
                'titlekey' => 'course_deliverable',
                'shortkey' => 'sectionkind_deliverable',
                'cssclass' => 'uckk-section-deliverable',
                'icon' => 'i/assign',
                'requiresproof' => true,
                'archivable' => true,
                'integritysensitive' => true,
                'completioncritical' => true,
                'purpose' => 'final_artifact',
                'recommendedactivities' => [
                    'assign',
                    'workshop',
                    'uckkchallenge',
                    'uckkarchive',
                ],
            ],
            7 => [
                'sectionnum' => 7,
                'kind' => self::SECTION_EVALUATION,
                'stringkey' => 'course_evaluation',
                'titlekey' => 'course_evaluation',
                'shortkey' => 'sectionkind_evaluation',
                'cssclass' => 'uckk-section-evaluation',
                'icon' => 'i/grades',
                'requiresproof' => false,
                'archivable' => true,
                'integritysensitive' => true,
                'completioncritical' => true,
                'purpose' => 'evaluation_and_feedback',
                'recommendedactivities' => [
                    'assign',
                    'quiz',
                    'workshop',
                    'feedback',
                ],
            ],
            8 => [
                'sectionnum' => 8,
                'kind' => self::SECTION_ARCHIVE,
                'stringkey' => 'course_archive',
                'titlekey' => 'course_archive',
                'shortkey' => 'sectionkind_archive',
                'cssclass' => 'uckk-section-archive',
                'icon' => 'i/files',
                'requiresproof' => false,
                'archivable' => true,
                'integritysensitive' => true,
                'completioncritical' => true,
                'purpose' => 'memory_and_reuse',
                'recommendedactivities' => [
                    'database',
                    'folder',
                    'glossary',
                    'uckkarchive',
                ],
            ],
        ];
    }

    /**
     * Return all known section kinds.
     *
     * @return string[]
     */
    public static function section_kinds(): array {
        return [
            self::SECTION_ORIENTATION,
            self::SECTION_CONCEPTS,
            self::SECTION_CANON,
            self::SECTION_WORKSHOP,
            self::SECTION_PROOFS,
            self::SECTION_DELIBERATION,
            self::SECTION_DELIVERABLE,
            self::SECTION_EVALUATION,
            self::SECTION_ARCHIVE,
            self::SECTION_CUSTOM,
        ];
    }

    /**
     * Return all known course modes.
     *
     * @return string[]
     */
    public static function modes(): array {
        return [
            self::MODE_STANDARD,
            self::MODE_TRONC_COMMUN,
            self::MODE_PROGRAM,
            self::MODE_LAB,
        ];
    }

    /**
     * Return all known blueprint profiles.
     *
     * @return string[]
     */
    public static function profiles(): array {
        return [
            self::PROFILE_DEFAULT,
            self::PROFILE_TRONC_COMMUN,
            self::PROFILE_CHALLENGE,
            self::PROFILE_ASSEMBLY,
            self::PROFILE_ARCHIVE,
        ];
    }

    /**
     * Return the canonical section definition for a section number.
     *
     * @param int $sectionnum Section number.
     * @return array<string, mixed>
     */
    public static function section(int $sectionnum): array {
        $sections = self::sections();

        if (array_key_exists($sectionnum, $sections)) {
            return $sections[$sectionnum];
        }

        return self::custom_section($sectionnum);
    }

    /**
     * Return the canonical section definition for a section kind.
     *
     * @param string $kind Section kind.
     * @return array<string, mixed>|null
     */
    public static function section_by_kind(string $kind): ?array {
        $kind = self::clean_key($kind);

        foreach (self::sections() as $section) {
            if ($section['kind'] === $kind) {
                return $section;
            }
        }

        if ($kind === self::SECTION_CUSTOM) {
            return self::custom_section(-1);
        }

        return null;
    }

    /**
     * Return the default section kind for a section number.
     *
     * @param int $sectionnum Section number.
     * @return string
     */
    public static function kind_for_section(int $sectionnum): string {
        return self::section($sectionnum)['kind'];
    }

    /**
     * Return the default string key for a section number.
     *
     * @param int $sectionnum Section number.
     * @return string
     */
    public static function string_key_for_section(int $sectionnum): string {
        return self::section($sectionnum)['stringkey'];
    }

    /**
     * Return the default CSS class for a section number.
     *
     * @param int $sectionnum Section number.
     * @return string
     */
    public static function css_class_for_section(int $sectionnum): string {
        return self::section($sectionnum)['cssclass'];
    }

    /**
     * Return the default purpose key for a section number.
     *
     * @param int $sectionnum Section number.
     * @return string
     */
    public static function purpose_for_section(int $sectionnum): string {
        return self::section($sectionnum)['purpose'];
    }

    /**
     * Return whether a section kind requires proof by default.
     *
     * @param string $kind Section kind.
     * @return bool
     */
    public static function kind_requires_proof(string $kind): bool {
        $section = self::section_by_kind($kind);

        if ($section === null) {
            return false;
        }

        return !empty($section['requiresproof']);
    }

    /**
     * Return whether a section kind is archivable by default.
     *
     * @param string $kind Section kind.
     * @return bool
     */
    public static function kind_is_archivable(string $kind): bool {
        $section = self::section_by_kind($kind);

        if ($section === null) {
            return false;
        }

        return !empty($section['archivable']);
    }

    /**
     * Return whether a section kind is integrity sensitive by default.
     *
     * @param string $kind Section kind.
     * @return bool
     */
    public static function kind_is_integrity_sensitive(string $kind): bool {
        $section = self::section_by_kind($kind);

        if ($section === null) {
            return false;
        }

        return !empty($section['integritysensitive']);
    }

    /**
     * Export the full blueprint for Mustache templates.
     *
     * @param string $profile Blueprint profile.
     * @return array<string, mixed>
     */
    public static function export_for_template(string $profile = self::PROFILE_DEFAULT): array {
        $profile = self::normalise_profile($profile);
        $sections = [];

        foreach (self::sections_for_profile($profile) as $section) {
            $sections[] = self::export_section_for_template($section);
        }

        return [
            'profile' => $profile,
            'sections' => $sections,
            'sectioncount' => count($sections),
            'hassections' => !empty($sections),
            'requiresproof' => self::profile_requires_proof($profile),
            'archivable' => self::profile_is_archivable($profile),
            'integritysensitive' => self::profile_is_integrity_sensitive($profile),
        ];
    }

    /**
     * Export one section definition for Mustache templates.
     *
     * @param array<string, mixed> $section Section definition.
     * @return array<string, mixed>
     */
    public static function export_section_for_template(array $section): array {
        $sectionnum = (int)($section['sectionnum'] ?? -1);
        $kind = self::clean_key($section['kind'] ?? self::SECTION_CUSTOM);

        return [
            'sectionnum' => $sectionnum,
            'kind' => $kind,
            'stringkey' => $section['stringkey'] ?? 'sectionname',
            'titlekey' => $section['titlekey'] ?? 'sectionname',
            'shortkey' => $section['shortkey'] ?? 'sectionkind_custom',
            'cssclass' => $section['cssclass'] ?? 'uckk-section-custom',
            'icon' => $section['icon'] ?? 'i/course',
            'requiresproof' => !empty($section['requiresproof']),
            'archivable' => !empty($section['archivable']),
            'integritysensitive' => !empty($section['integritysensitive']),
            'completioncritical' => !empty($section['completioncritical']),
            'purpose' => $section['purpose'] ?? self::SECTION_CUSTOM,
            'recommendedactivities' => self::normalise_activity_list($section['recommendedactivities'] ?? []),
            'hasrecommendedactivities' => !empty($section['recommendedactivities']),
        ];
    }

    /**
     * Return sections for a blueprint profile.
     *
     * Profiles do not remove the canonical spine. They only add profile flags
     * and recommendation emphasis.
     *
     * @param string $profile Blueprint profile.
     * @return array<int, array<string, mixed>>
     */
    public static function sections_for_profile(string $profile = self::PROFILE_DEFAULT): array {
        $profile = self::normalise_profile($profile);
        $sections = self::sections();

        foreach ($sections as $sectionnum => $section) {
            $sections[$sectionnum]['profile'] = $profile;
            $sections[$sectionnum]['profileemphasis'] = self::profile_emphasis_for_section($profile, $section['kind']);
        }

        return $sections;
    }

    /**
     * Return whether a profile requires proof by design.
     *
     * @param string $profile Blueprint profile.
     * @return bool
     */
    public static function profile_requires_proof(string $profile): bool {
        $profile = self::normalise_profile($profile);

        return in_array($profile, [
            self::PROFILE_DEFAULT,
            self::PROFILE_TRONC_COMMUN,
            self::PROFILE_CHALLENGE,
            self::PROFILE_ARCHIVE,
        ], true);
    }

    /**
     * Return whether a profile is archivable by design.
     *
     * @param string $profile Blueprint profile.
     * @return bool
     */
    public static function profile_is_archivable(string $profile): bool {
        $profile = self::normalise_profile($profile);

        return in_array($profile, [
            self::PROFILE_DEFAULT,
            self::PROFILE_TRONC_COMMUN,
            self::PROFILE_CHALLENGE,
            self::PROFILE_ASSEMBLY,
            self::PROFILE_ARCHIVE,
        ], true);
    }

    /**
     * Return whether a profile is integrity sensitive by design.
     *
     * @param string $profile Blueprint profile.
     * @return bool
     */
    public static function profile_is_integrity_sensitive(string $profile): bool {
        $profile = self::normalise_profile($profile);

        return in_array($profile, [
            self::PROFILE_TRONC_COMMUN,
            self::PROFILE_CHALLENGE,
            self::PROFILE_ASSEMBLY,
            self::PROFILE_ARCHIVE,
        ], true);
    }

    /**
     * Infer the UCKK mode from a course-like object.
     *
     * @param stdClass $course Course object.
     * @return string
     */
    public static function infer_mode_from_course(stdClass $course): string {
        $shortname = $course->shortname ?? '';
        $idnumber = $course->idnumber ?? '';

        if (preg_match('/^UCKK-TC/i', $shortname) || preg_match('/^UCKK-TC/i', $idnumber)) {
            return self::MODE_TRONC_COMMUN;
        }

        if (preg_match('/^UCKK-/i', $shortname) || preg_match('/^UCKK-/i', $idnumber)) {
            return self::MODE_PROGRAM;
        }

        return self::MODE_STANDARD;
    }

    /**
     * Infer the blueprint profile from a course mode.
     *
     * @param string $mode UCKK mode.
     * @return string
     */
    public static function profile_from_mode(string $mode): string {
        $mode = self::normalise_mode($mode);

        if ($mode === self::MODE_TRONC_COMMUN) {
            return self::PROFILE_TRONC_COMMUN;
        }

        if ($mode === self::MODE_LAB) {
            return self::PROFILE_ARCHIVE;
        }

        return self::PROFILE_DEFAULT;
    }

    /**
     * Return default section option values for section creation or repair.
     *
     * @param int $sectionnum Section number.
     * @return array<string, mixed>
     */
    public static function default_section_options(int $sectionnum): array {
        $section = self::section($sectionnum);

        return [
            'uckksectionkind' => $section['kind'],
            'requiresproof' => !empty($section['requiresproof']) ? 1 : 0,
            'archivable' => !empty($section['archivable']) ? 1 : 0,
            'integritysensitive' => !empty($section['integritysensitive']) ? 1 : 0,
        ];
    }

    /**
     * Return default course format option values for a mode.
     *
     * @param string $mode UCKK course mode.
     * @return array<string, mixed>
     */
    public static function default_course_options(string $mode = self::MODE_STANDARD): array {
        $mode = self::normalise_mode($mode);

        return [
            'uckkmode' => $mode,
            'showcanon' => 1,
            'showevidenceflow' => 1,
            'showintegritynotice' => self::mode_is_integrity_sensitive($mode) ? 1 : 0,
            'showrecognitionnotice' => 1,
        ];
    }

    /**
     * Return whether a mode is integrity sensitive.
     *
     * @param string $mode UCKK mode.
     * @return bool
     */
    public static function mode_is_integrity_sensitive(string $mode): bool {
        $mode = self::normalise_mode($mode);

        return in_array($mode, [
            self::MODE_TRONC_COMMUN,
            self::MODE_PROGRAM,
            self::MODE_LAB,
        ], true);
    }

    /**
     * Return a Moodle language string label for a section kind.
     *
     * @param string $kind Section kind.
     * @return string
     */
    public static function language_key_for_kind(string $kind): string {
        $section = self::section_by_kind($kind);

        if ($section !== null) {
            return $section['stringkey'];
        }

        return 'sectionname';
    }

    /**
     * Return a Moodle language string label for a section number.
     *
     * @param int $sectionnum Section number.
     * @return string
     */
    public static function language_key_for_section(int $sectionnum): string {
        return self::section($sectionnum)['stringkey'];
    }

    /**
     * Validate a section kind.
     *
     * @param string $kind Section kind.
     * @return bool
     */
    public static function is_valid_section_kind(string $kind): bool {
        return in_array(self::clean_key($kind), self::section_kinds(), true);
    }

    /**
     * Validate a mode.
     *
     * @param string $mode UCKK mode.
     * @return bool
     */
    public static function is_valid_mode(string $mode): bool {
        return in_array(self::clean_key($mode), self::modes(), true);
    }

    /**
     * Validate a profile.
     *
     * @param string $profile Blueprint profile.
     * @return bool
     */
    public static function is_valid_profile(string $profile): bool {
        return in_array(self::clean_key($profile), self::profiles(), true);
    }

    /**
     * Normalise a mode.
     *
     * @param string $mode UCKK mode.
     * @return string
     */
    public static function normalise_mode(string $mode): string {
        $mode = self::clean_key($mode);

        if (!self::is_valid_mode($mode)) {
            return self::MODE_STANDARD;
        }

        return $mode;
    }

    /**
     * Normalise a section kind.
     *
     * @param string $kind Section kind.
     * @return string
     */
    public static function normalise_section_kind(string $kind): string {
        $kind = self::clean_key($kind);

        if (!self::is_valid_section_kind($kind)) {
            return self::SECTION_CUSTOM;
        }

        return $kind;
    }

    /**
     * Normalise a blueprint profile.
     *
     * @param string $profile Blueprint profile.
     * @return string
     */
    public static function normalise_profile(string $profile): string {
        $profile = self::clean_key($profile);

        if (!self::is_valid_profile($profile)) {
            return self::PROFILE_DEFAULT;
        }

        return $profile;
    }

    /**
     * Return profile emphasis for a section.
     *
     * @param string $profile Blueprint profile.
     * @param string $kind Section kind.
     * @return string
     */
    protected static function profile_emphasis_for_section(string $profile, string $kind): string {
        $profile = self::normalise_profile($profile);
        $kind = self::normalise_section_kind($kind);

        $emphasis = [
            self::PROFILE_TRONC_COMMUN => [
                self::SECTION_CONCEPTS => 'high',
                self::SECTION_PROOFS => 'high',
                self::SECTION_DELIBERATION => 'high',
                self::SECTION_ARCHIVE => 'high',
            ],
            self::PROFILE_CHALLENGE => [
                self::SECTION_WORKSHOP => 'high',
                self::SECTION_PROOFS => 'high',
                self::SECTION_DELIVERABLE => 'high',
                self::SECTION_ARCHIVE => 'high',
            ],
            self::PROFILE_ASSEMBLY => [
                self::SECTION_DELIBERATION => 'high',
                self::SECTION_EVALUATION => 'medium',
                self::SECTION_ARCHIVE => 'high',
            ],
            self::PROFILE_ARCHIVE => [
                self::SECTION_CANON => 'medium',
                self::SECTION_PROOFS => 'high',
                self::SECTION_ARCHIVE => 'high',
            ],
        ];

        return $emphasis[$profile][$kind] ?? 'normal';
    }

    /**
     * Return a custom section definition.
     *
     * @param int $sectionnum Section number.
     * @return array<string, mixed>
     */
    protected static function custom_section(int $sectionnum): array {
        return [
            'sectionnum' => $sectionnum,
            'kind' => self::SECTION_CUSTOM,
            'stringkey' => 'sectionname',
            'titlekey' => 'sectionname',
            'shortkey' => 'sectionkind_custom',
            'cssclass' => 'uckk-section-custom',
            'icon' => 'i/course',
            'requiresproof' => false,
            'archivable' => false,
            'integritysensitive' => false,
            'completioncritical' => false,
            'purpose' => self::SECTION_CUSTOM,
            'recommendedactivities' => [],
        ];
    }

    /**
     * Normalise an activity list for template export.
     *
     * @param array $activities Activity names.
     * @return array<int, array<string, string>>
     */
    protected static function normalise_activity_list(array $activities): array {
        $normalised = [];

        foreach ($activities as $activity) {
            $activity = self::clean_key((string)$activity);

            if ($activity === '') {
                continue;
            }

            $normalised[] = [
                'name' => $activity,
            ];
        }

        return $normalised;
    }

    /**
     * Clean a machine key.
     *
     * @param string $key Raw key.
     * @return string
     */
    protected static function clean_key(string $key): string {
        $key = trim(core_text::strtolower($key));
        $key = str_replace('-', '_', $key);
        $key = preg_replace('/[^a-z0-9_]/', '', $key);

        if ($key === null) {
            throw new coding_exception('Invalid key normalisation result.');
        }

        return $key;
    }
}