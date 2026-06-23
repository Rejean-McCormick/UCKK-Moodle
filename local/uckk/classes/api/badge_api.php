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
 * Badge API for local_uckk.
 *
 * This API coordinates canonical UCKK badge definitions with Moodle badges.
 *
 * It does not replace Moodle's badge subsystem. It provides:
 * - canonical UCKK badge metadata;
 * - stable badge idnumbers;
 * - lookup helpers;
 * - user badge status helpers;
 * - safe award helpers where Moodle's badge library is available;
 * - template-ready exports for dashboards, reports and seed tools.
 *
 * UCKK badges are internal recognitions. They are not public university
 * degrees or state-accredited credentials unless future official recognition
 * exists outside this plugin.
 *
 * @package    local_uckk
 * @copyright  2026 Univers-Cité King Klown
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_uckk\api;

use context;
use context_course;
use context_system;
use core_component;
use moodle_exception;
use stdClass;

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->libdir . '/badgeslib.php');

/**
 * Local UCKK badge API.
 *
 * This class is intentionally static because it behaves as a registry and
 * coordination layer. It must not store independent badge award records that
 * duplicate Moodle's badge_issued data.
 *
 * @package local_uckk
 */
final class badge_api {
    /** Component name. */
    public const COMPONENT = 'local_uckk';

    /** Canonical idnumber prefix for UCKK badges. */
    public const IDNUMBER_PREFIX = 'UCKK-BADGE-';

    /** Badge key: Joueur initié. */
    public const BADGE_JOUEUR_INITIE = 'joueur_initie';

    /** Badge key: Joueur lucide. */
    public const BADGE_JOUEUR_LUCIDE = 'joueur_lucide';

    /** Badge key: Cartographe de systèmes. */
    public const BADGE_CARTOGRAPHE_SYSTEMES = 'cartographe_systemes';

    /** Badge key: Gardien de la preuve. */
    public const BADGE_GARDIEN_PREUVE = 'gardien_preuve';

    /** Badge key: Participant d’Assemblée. */
    public const BADGE_PARTICIPANT_ASSEMBLEE = 'participant_assemblee';

    /** Badge key: Bâtisseur de prototype. */
    public const BADGE_BATISSEUR_PROTOTYPE = 'batisseur_prototype';

    /** Badge key: Archiviste de décision. */
    public const BADGE_ARCHIVISTE_DECISION = 'archiviste_decision';

    /** Badge key: Défi King Klown validé. */
    public const BADGE_DEFI_KING_KLOWN = 'defi_king_klown';

    /** Badge key: Inquisition méthodologique réussie. */
    public const BADGE_INQUISITION_METHODOLOGIQUE = 'inquisition_methodologique';

    /** Badge key: Architecte du sens. */
    public const BADGE_ARCHITECTE_SENS = 'architecte_sens';

    /** Badge key: Architecte d’opportunités. */
    public const BADGE_ARCHITECTE_OPPORTUNITES = 'architecte_opportunites';

    /** Badge key: Gardien des systèmes vivants. */
    public const BADGE_GARDIEN_SYSTEMES_VIVANTS = 'gardien_systemes_vivants';

    /** Badge key: Production IA. */
    public const BADGE_IA_GOUVERNABLE = 'ia_gouvernable';

    /** Badge key: Grand Jeu social. */
    public const BADGE_GRAND_JEU_SOCIAL = 'grand_jeu_social';

    /** Badge key: kOA Digital Ecosystem. */
    public const BADGE_KOA_DIGITAL = 'koa_digital_ecosystem';

    /** Badge level: foundation. */
    public const LEVEL_FOUNDATION = 'foundation';

    /** Badge level: practice. */
    public const LEVEL_PRACTICE = 'practice';

    /** Badge level: program. */
    public const LEVEL_PROGRAM = 'program';

    /** Badge level: governance. */
    public const LEVEL_GOVERNANCE = 'governance';

    /** Badge level: advanced. */
    public const LEVEL_ADVANCED = 'advanced';

    /**
     * Return the canonical UCKK badge registry.
     *
     * The registry is used by seed tools, dashboards, reports and validation
     * helpers. The actual badge entities remain Moodle badges.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function get_canonical_badges(): array {
        return [
            self::BADGE_JOUEUR_INITIE => [
                'key' => self::BADGE_JOUEUR_INITIE,
                'idnumber' => self::get_idnumber(self::BADGE_JOUEUR_INITIE),
                'name' => 'Joueur initié',
                'description' => 'Reconnaît l’entrée dans le campus UCKK et la compréhension minimale de ses règles, limites et espaces.',
                'level' => self::LEVEL_FOUNDATION,
                'symbolicrole' => 'joueur',
                'requiresproof' => true,
                'requireshumanvalidation' => true,
                'requiresarchive' => false,
                'requiresintegrityclearance' => false,
                'competencies' => [
                    'UCKK-COMP-014',
                ],
                'tags' => [
                    'orientation',
                    'campus',
                    'uckk',
                ],
            ],
            self::BADGE_JOUEUR_LUCIDE => [
                'key' => self::BADGE_JOUEUR_LUCIDE,
                'idnumber' => self::get_idnumber(self::BADGE_JOUEUR_LUCIDE),
                'name' => 'Joueur lucide',
                'description' => 'Reconnaît la capacité à lire le Grand Jeu social, distinguer faits, hypothèses, récits et décisions, et agir avec responsabilité.',
                'level' => self::LEVEL_FOUNDATION,
                'symbolicrole' => 'joueur_lucide',
                'requiresproof' => true,
                'requireshumanvalidation' => true,
                'requiresarchive' => true,
                'requiresintegrityclearance' => true,
                'competencies' => [
                    'UCKK-COMP-001',
                    'UCKK-COMP-003',
                    'UCKK-COMP-010',
                    'UCKK-COMP-013',
                ],
                'tags' => [
                    'grand_jeu',
                    'lucidite',
                    'ethique',
                ],
            ],
            self::BADGE_CARTOGRAPHE_SYSTEMES => [
                'key' => self::BADGE_CARTOGRAPHE_SYSTEMES,
                'idnumber' => self::get_idnumber(self::BADGE_CARTOGRAPHE_SYSTEMES),
                'name' => 'Cartographe de systèmes',
                'description' => 'Reconnaît la capacité à cartographier un système social, technique ou institutionnel de manière claire, vérifiable et utile.',
                'level' => self::LEVEL_PRACTICE,
                'symbolicrole' => 'cartographe',
                'requiresproof' => true,
                'requireshumanvalidation' => true,
                'requiresarchive' => true,
                'requiresintegrityclearance' => true,
                'competencies' => [
                    'UCKK-COMP-002',
                    'UCKK-COMP-003',
                    'UCKK-COMP-005',
                ],
                'tags' => [
                    'cartographie',
                    'systemes',
                    'preuve',
                ],
            ],
            self::BADGE_GARDIEN_PREUVE => [
                'key' => self::BADGE_GARDIEN_PREUVE,
                'idnumber' => self::get_idnumber(self::BADGE_GARDIEN_PREUVE),
                'name' => 'Gardien de la preuve',
                'description' => 'Reconnaît la production, l’organisation et la vérification de preuves utiles, traçables et contestables.',
                'level' => self::LEVEL_PRACTICE,
                'symbolicrole' => 'gardien_preuve',
                'requiresproof' => true,
                'requireshumanvalidation' => true,
                'requiresarchive' => true,
                'requiresintegrityclearance' => true,
                'competencies' => [
                    'UCKK-COMP-005',
                    'UCKK-COMP-008',
                    'UCKK-COMP-013',
                ],
                'tags' => [
                    'preuve',
                    'integrite',
                    'traçabilite',
                ],
            ],
            self::BADGE_PARTICIPANT_ASSEMBLEE => [
                'key' => self::BADGE_PARTICIPANT_ASSEMBLEE,
                'idnumber' => self::get_idnumber(self::BADGE_PARTICIPANT_ASSEMBLEE),
                'name' => 'Participant d’Assemblée',
                'description' => 'Reconnaît la participation constructive à une assemblée structurée, avec arguments, objections, décisions ou procès-verbal.',
                'level' => self::LEVEL_GOVERNANCE,
                'symbolicrole' => 'participant_assemblee',
                'requiresproof' => true,
                'requireshumanvalidation' => true,
                'requiresarchive' => true,
                'requiresintegrityclearance' => true,
                'competencies' => [
                    'UCKK-COMP-006',
                    'UCKK-COMP-008',
                    'UCKK-COMP-013',
                ],
                'tags' => [
                    'assemblee',
                    'deliberation',
                    'decision',
                ],
            ],
            self::BADGE_BATISSEUR_PROTOTYPE => [
                'key' => self::BADGE_BATISSEUR_PROTOTYPE,
                'idnumber' => self::get_idnumber(self::BADGE_BATISSEUR_PROTOTYPE),
                'name' => 'Bâtisseur de prototype',
                'description' => 'Reconnaît la construction d’un artefact, prototype, outil ou dispositif utile, documenté et améliorable.',
                'level' => self::LEVEL_PRACTICE,
                'symbolicrole' => 'batisseur',
                'requiresproof' => true,
                'requireshumanvalidation' => true,
                'requiresarchive' => true,
                'requiresintegrityclearance' => true,
                'competencies' => [
                    'UCKK-COMP-012',
                    'UCKK-COMP-005',
                    'UCKK-COMP-009',
                ],
                'tags' => [
                    'prototype',
                    'construction',
                    'artefact',
                ],
            ],
            self::BADGE_ARCHIVISTE_DECISION => [
                'key' => self::BADGE_ARCHIVISTE_DECISION,
                'idnumber' => self::get_idnumber(self::BADGE_ARCHIVISTE_DECISION),
                'name' => 'Archiviste de décision',
                'description' => 'Reconnaît la capacité à documenter, versionner et archiver une décision ou un apprentissage collectif.',
                'level' => self::LEVEL_GOVERNANCE,
                'symbolicrole' => 'archiviste',
                'requiresproof' => true,
                'requireshumanvalidation' => true,
                'requiresarchive' => true,
                'requiresintegrityclearance' => true,
                'competencies' => [
                    'UCKK-COMP-008',
                    'UCKK-COMP-009',
                    'UCKK-COMP-013',
                ],
                'tags' => [
                    'archive',
                    'decision',
                    'memoire',
                ],
            ],
            self::BADGE_DEFI_KING_KLOWN => [
                'key' => self::BADGE_DEFI_KING_KLOWN,
                'idnumber' => self::get_idnumber(self::BADGE_DEFI_KING_KLOWN),
                'name' => 'Défi King Klown validé',
                'description' => 'Reconnaît la réussite d’un défi King Klown avec preuves, règles visibles, validation humaine et absence d’abus.',
                'level' => self::LEVEL_PRACTICE,
                'symbolicrole' => 'joueur',
                'requiresproof' => true,
                'requireshumanvalidation' => true,
                'requiresarchive' => true,
                'requiresintegrityclearance' => true,
                'competencies' => [
                    'UCKK-COMP-005',
                    'UCKK-COMP-007',
                    'UCKK-COMP-010',
                ],
                'tags' => [
                    'defi',
                    'king_klown',
                    'preuve',
                ],
            ],
            self::BADGE_INQUISITION_METHODOLOGIQUE => [
                'key' => self::BADGE_INQUISITION_METHODOLOGIQUE,
                'idnumber' => self::get_idnumber(self::BADGE_INQUISITION_METHODOLOGIQUE),
                'name' => 'Inquisition méthodologique réussie',
                'description' => 'Reconnaît la capacité à soumettre une production à l’examen méthodologique, corriger les failles et préserver la contestabilité.',
                'level' => self::LEVEL_GOVERNANCE,
                'symbolicrole' => 'inquisiteur_methodologique',
                'requiresproof' => true,
                'requireshumanvalidation' => true,
                'requiresarchive' => true,
                'requiresintegrityclearance' => true,
                'competencies' => [
                    'UCKK-COMP-010',
                    'UCKK-COMP-011',
                    'UCKK-COMP-013',
                ],
                'tags' => [
                    'inquisiteur',
                    'methode',
                    'contestabilite',
                ],
            ],
            self::BADGE_ARCHITECTE_SENS => [
                'key' => self::BADGE_ARCHITECTE_SENS,
                'idnumber' => self::get_idnumber(self::BADGE_ARCHITECTE_SENS),
                'name' => 'Architecte du sens',
                'description' => 'Reconnaît la capacité à organiser des récits, concepts et distinctions sans confondre fiction, fait, preuve et décision.',
                'level' => self::LEVEL_ADVANCED,
                'symbolicrole' => 'architecte_sens',
                'requiresproof' => true,
                'requireshumanvalidation' => true,
                'requiresarchive' => true,
                'requiresintegrityclearance' => true,
                'competencies' => [
                    'UCKK-COMP-003',
                    'UCKK-COMP-008',
                    'UCKK-COMP-013',
                ],
                'tags' => [
                    'sens',
                    'langage',
                    'recit',
                ],
            ],
            self::BADGE_ARCHITECTE_OPPORTUNITES => [
                'key' => self::BADGE_ARCHITECTE_OPPORTUNITES,
                'idnumber' => self::get_idnumber(self::BADGE_ARCHITECTE_OPPORTUNITES),
                'name' => 'Architecte d’opportunités',
                'description' => 'Reconnaît la capacité à transformer une contrainte, un conflit ou une faille institutionnelle en opportunité d’apprentissage ou d’action.',
                'level' => self::LEVEL_ADVANCED,
                'symbolicrole' => 'architecte_opportunites',
                'requiresproof' => true,
                'requireshumanvalidation' => true,
                'requiresarchive' => true,
                'requiresintegrityclearance' => true,
                'competencies' => [
                    'UCKK-COMP-001',
                    'UCKK-COMP-007',
                    'UCKK-COMP-012',
                ],
                'tags' => [
                    'opportunite',
                    'action',
                    'strategie',
                ],
            ],
            self::BADGE_GARDIEN_SYSTEMES_VIVANTS => [
                'key' => self::BADGE_GARDIEN_SYSTEMES_VIVANTS,
                'idnumber' => self::get_idnumber(self::BADGE_GARDIEN_SYSTEMES_VIVANTS),
                'name' => 'Gardien des systèmes vivants',
                'description' => 'Reconnaît l’attention portée aux systèmes humains, écologiques ou sociaux comme réalités vivantes, non comme abstractions manipulables.',
                'level' => self::LEVEL_ADVANCED,
                'symbolicrole' => 'gardien_systemes_vivants',
                'requiresproof' => true,
                'requireshumanvalidation' => true,
                'requiresarchive' => true,
                'requiresintegrityclearance' => true,
                'competencies' => [
                    'UCKK-COMP-001',
                    'UCKK-COMP-002',
                    'UCKK-COMP-010',
                ],
                'tags' => [
                    'systemes_vivants',
                    'ecologie',
                    'responsabilite',
                ],
            ],
            self::BADGE_IA_GOUVERNABLE => [
                'key' => self::BADGE_IA_GOUVERNABLE,
                'idnumber' => self::get_idnumber(self::BADGE_IA_GOUVERNABLE),
                'name' => 'Production IA',
                'description' => 'Reconnaît l’usage de l’IA comme outil non souverain, avec journal humain-IA, incertitudes explicites et validation humaine.',
                'level' => self::LEVEL_PROGRAM,
                'symbolicrole' => 'ia_gouvernable',
                'requiresproof' => true,
                'requireshumanvalidation' => true,
                'requiresarchive' => true,
                'requiresintegrityclearance' => true,
                'competencies' => [
                    'UCKK-COMP-004',
                    'UCKK-COMP-005',
                    'UCKK-COMP-011',
                ],
                'tags' => [
                    'ia',
                    'gouvernance',
                    'non_souveraine',
                ],
            ],
            self::BADGE_GRAND_JEU_SOCIAL => [
                'key' => self::BADGE_GRAND_JEU_SOCIAL,
                'idnumber' => self::get_idnumber(self::BADGE_GRAND_JEU_SOCIAL),
                'name' => 'Grand Jeu social',
                'description' => 'Reconnaît la compréhension avancée du Grand Jeu social, de ses règles visibles et invisibles, et des actions responsables possibles.',
                'level' => self::LEVEL_PROGRAM,
                'symbolicrole' => 'grand_jeu_social',
                'requiresproof' => true,
                'requireshumanvalidation' => true,
                'requiresarchive' => true,
                'requiresintegrityclearance' => true,
                'competencies' => [
                    'UCKK-COMP-001',
                    'UCKK-COMP-007',
                    'UCKK-COMP-014',
                ],
                'tags' => [
                    'grand_jeu_social',
                    'strategie',
                    'mobilisation',
                ],
            ],
            self::BADGE_KOA_DIGITAL => [
                'key' => self::BADGE_KOA_DIGITAL,
                'idnumber' => self::get_idnumber(self::BADGE_KOA_DIGITAL),
                'name' => 'kOA Digital Ecosystem',
                'description' => 'Reconnaît la compréhension de l’écosystème digital kOA comme infrastructure distincte du mouvement kOA et de l’école UCKK.',
                'level' => self::LEVEL_PROGRAM,
                'symbolicrole' => 'koa_digital_ecosystem',
                'requiresproof' => true,
                'requireshumanvalidation' => true,
                'requiresarchive' => true,
                'requiresintegrityclearance' => true,
                'competencies' => [
                    'UCKK-COMP-008',
                    'UCKK-COMP-012',
                    'UCKK-COMP-014',
                ],
                'tags' => [
                    'koa',
                    'digital_ecosystem',
                    'infrastructure',
                ],
            ],
        ];
    }

    /**
     * Return canonical badge keys in display order.
     *
     * @return string[]
     */
    public static function get_canonical_keys(): array {
        return array_keys(self::get_canonical_badges());
    }

    /**
     * Return one canonical badge definition.
     *
     * @param string $key Badge key.
     * @return array<string, mixed>|null
     */
    public static function get_canonical_badge(string $key): ?array {
        $key = self::normalise_key($key);
        $badges = self::get_canonical_badges();

        return $badges[$key] ?? null;
    }

    /**
     * Return a stable Moodle badge idnumber for a UCKK badge key.
     *
     * @param string $key Badge key.
     * @return string
     */
    public static function get_idnumber(string $key): string {
        $key = self::normalise_key($key);
        return self::IDNUMBER_PREFIX . strtoupper(str_replace('_', '-', $key));
    }

    /**
     * Find a Moodle badge record by canonical UCKK key.
     *
     * @param string $key Badge key.
     * @param int|null $courseid Optional course id. Null means any context.
     * @return stdClass|null
     */
    public static function get_badge_record_by_key(string $key, ?int $courseid = null): ?stdClass {
        return self::get_badge_record_by_idnumber(self::get_idnumber($key), $courseid);
    }

    /**
     * Find a Moodle badge record by idnumber.
     *
     * @param string $idnumber Badge idnumber.
     * @param int|null $courseid Optional course id. Null means any context.
     * @return stdClass|null
     */
    public static function get_badge_record_by_idnumber(string $idnumber, ?int $courseid = null): ?stdClass {
        global $DB;

        $conditions = [
            'idnumber' => $idnumber,
        ];

        if ($courseid !== null) {
            $conditions['courseid'] = $courseid;
        }

        $records = $DB->get_records('badge', $conditions, 'courseid ASC, id ASC', '*', 0, 2);

        if (empty($records)) {
            return null;
        }

        return reset($records);
    }

    /**
     * Determine whether a canonical UCKK badge exists as a Moodle badge.
     *
     * @param string $key Badge key.
     * @param int|null $courseid Optional course id.
     * @return bool
     */
    public static function badge_exists(string $key, ?int $courseid = null): bool {
        return self::get_badge_record_by_key($key, $courseid) !== null;
    }

    /**
     * Determine whether a user has been issued a canonical UCKK badge.
     *
     * @param int $userid User id.
     * @param string $key Badge key.
     * @param int|null $courseid Optional course id.
     * @return bool
     */
    public static function user_has_badge(int $userid, string $key, ?int $courseid = null): bool {
        global $DB;

        $badge = self::get_badge_record_by_key($key, $courseid);
        if ($badge === null) {
            return false;
        }

        return $DB->record_exists('badge_issued', [
            'badgeid' => $badge->id,
            'userid' => $userid,
        ]);
    }

    /**
     * Return issue record for a user and canonical badge.
     *
     * @param int $userid User id.
     * @param string $key Badge key.
     * @param int|null $courseid Optional course id.
     * @return stdClass|null
     */
    public static function get_user_badge_issue(int $userid, string $key, ?int $courseid = null): ?stdClass {
        global $DB;

        $badge = self::get_badge_record_by_key($key, $courseid);
        if ($badge === null) {
            return null;
        }

        $record = $DB->get_record('badge_issued', [
            'badgeid' => $badge->id,
            'userid' => $userid,
        ]);

        return $record ?: null;
    }

    /**
     * Return all canonical UCKK badge statuses for a user.
     *
     * @param int $userid User id.
     * @param int|null $courseid Optional course id.
     * @return array<int, array<string, mixed>>
     */
    public static function get_user_badge_statuses(int $userid, ?int $courseid = null): array {
        $statuses = [];

        foreach (self::get_canonical_badges() as $key => $definition) {
            $badge = self::get_badge_record_by_key($key, $courseid);
            $issue = $badge !== null ? self::get_user_badge_issue($userid, $key, $courseid) : null;

            $statuses[] = [
                'key' => $key,
                'idnumber' => $definition['idnumber'],
                'name' => $definition['name'],
                'description' => $definition['description'],
                'level' => $definition['level'],
                'symbolicrole' => $definition['symbolicrole'],
                'exists' => $badge !== null,
                'badgeid' => $badge->id ?? null,
                'awarded' => $issue !== null,
                'issuedid' => $issue->id ?? null,
                'dateissued' => $issue->dateissued ?? null,
                'uniquehash' => $issue->uniquehash ?? null,
                'requiresproof' => !empty($definition['requiresproof']),
                'requireshumanvalidation' => !empty($definition['requireshumanvalidation']),
                'requiresarchive' => !empty($definition['requiresarchive']),
                'requiresintegrityclearance' => !empty($definition['requiresintegrityclearance']),
                'competencies' => $definition['competencies'],
                'tags' => $definition['tags'],
            ];
        }

        return $statuses;
    }

    /**
     * Return a compact summary of canonical UCKK badge status for a user.
     *
     * @param int $userid User id.
     * @param int|null $courseid Optional course id.
     * @return array<string, int>
     */
    public static function get_user_badge_summary(int $userid, ?int $courseid = null): array {
        $statuses = self::get_user_badge_statuses($userid, $courseid);

        $total = count($statuses);
        $existing = 0;
        $awarded = 0;

        foreach ($statuses as $status) {
            if (!empty($status['exists'])) {
                $existing++;
            }

            if (!empty($status['awarded'])) {
                $awarded++;
            }
        }

        return [
            'total' => $total,
            'existing' => $existing,
            'missing' => $total - $existing,
            'awarded' => $awarded,
            'notawarded' => $total - $awarded,
        ];
    }

    /**
     * Export canonical badge statuses for Mustache templates.
     *
     * @param int $userid User id.
     * @param int|null $courseid Optional course id.
     * @return array<string, mixed>
     */
    public static function export_user_badges_for_template(int $userid, ?int $courseid = null): array {
        $badges = self::get_user_badge_statuses($userid, $courseid);
        $summary = self::get_user_badge_summary($userid, $courseid);

        foreach ($badges as $index => $badge) {
            $badges[$index]['hasdateissued'] = !empty($badge['dateissued']);
            $badges[$index]['dateissuedformatted'] = !empty($badge['dateissued'])
                ? userdate((int)$badge['dateissued'])
                : '';
            $badges[$index]['hascompetencies'] = !empty($badge['competencies']);
            $badges[$index]['hastags'] = !empty($badge['tags']);
        }

        return [
            'badges' => $badges,
            'hasbadges' => !empty($badges),
            'summary' => $summary,
            'totalbadges' => $summary['total'],
            'awardedbadges' => $summary['awarded'],
            'notawardedbadges' => $summary['notawarded'],
            'existingbadges' => $summary['existing'],
            'missingbadges' => $summary['missing'],
        ];
    }

    /**
     * Award a canonical UCKK badge to a user.
     *
     * This method delegates to Moodle's badge class when available. It should
     * be called only after UCKK evidence, human validation, archive and
     * integrity rules have already been checked by the owning workflow.
     *
     * @param int $userid User id.
     * @param string $key Badge key.
     * @param int|null $courseid Optional course id.
     * @param bool $notify Whether Moodle should notify the recipient.
     * @return bool True when the badge is already awarded or successfully issued.
     * @throws moodle_exception
     */
    public static function award_canonical_badge(
        int $userid,
        string $key,
        ?int $courseid = null,
        bool $notify = true
    ): bool {
        $badge = self::get_badge_record_by_key($key, $courseid);

        if ($badge === null) {
            throw new moodle_exception('error_badgenotfound', self::COMPONENT, '', self::get_idnumber($key));
        }

        if (self::user_has_badge($userid, $key, $courseid)) {
            return true;
        }

        if (!class_exists('\badge')) {
            throw new moodle_exception('error_badgeslibmissing', self::COMPONENT);
        }

        $moodlebadge = new \badge($badge->id);

        if (!method_exists($moodlebadge, 'issue')) {
            throw new moodle_exception('error_badgeissueunsupported', self::COMPONENT);
        }

        $result = $moodlebadge->issue($userid, $notify);

        return (bool)$result || self::user_has_badge($userid, $key, $courseid);
    }

    /**
     * Return canonical badge definitions that are missing from Moodle.
     *
     * This is intended for tool_uckkseed dry-run and diagnostics.
     *
     * @param int|null $courseid Optional course id.
     * @return array<int, array<string, mixed>>
     */
    public static function get_missing_canonical_badges(?int $courseid = null): array {
        $missing = [];

        foreach (self::get_canonical_badges() as $key => $definition) {
            if (!self::badge_exists($key, $courseid)) {
                $missing[] = $definition;
            }
        }

        return $missing;
    }

    /**
     * Return canonical badge definitions that exist as Moodle badge records.
     *
     * @param int|null $courseid Optional course id.
     * @return array<int, array<string, mixed>>
     */
    public static function get_existing_canonical_badges(?int $courseid = null): array {
        $existing = [];

        foreach (self::get_canonical_badges() as $key => $definition) {
            $record = self::get_badge_record_by_key($key, $courseid);

            if ($record !== null) {
                $definition['badgeid'] = $record->id;
                $definition['courseid'] = $record->courseid ?? null;
                $definition['status'] = $record->status ?? null;
                $existing[] = $definition;
            }
        }

        return $existing;
    }

    /**
     * Prepare a canonical badge definition for tool_uckkseed.
     *
     * This method returns seed data only. It does not create Moodle badges.
     *
     * @param string $key Badge key.
     * @param int $courseid Course id. Zero indicates site badge.
     * @return array<string, mixed>
     * @throws moodle_exception
     */
    public static function get_seed_definition(string $key, int $courseid = 0): array {
        $definition = self::get_canonical_badge($key);

        if ($definition === null) {
            throw new moodle_exception('error_unknownbadge', self::COMPONENT, '', $key);
        }

        return [
            'key' => $definition['key'],
            'idnumber' => $definition['idnumber'],
            'name' => $definition['name'],
            'description' => $definition['description'],
            'courseid' => $courseid,
            'issuername' => 'Univers-Cité King Klown',
            'issuercontact' => '',
            'issuerurl' => '',
            'language' => 'fr',
            'version' => '1.0',
            'imagecaption' => $definition['name'],
            'tags' => $definition['tags'],
            'competencies' => $definition['competencies'],
            'metadata' => [
                'uckklevel' => $definition['level'],
                'symbolicrole' => $definition['symbolicrole'],
                'requiresproof' => !empty($definition['requiresproof']),
                'requireshumanvalidation' => !empty($definition['requireshumanvalidation']),
                'requiresarchive' => !empty($definition['requiresarchive']),
                'requiresintegrityclearance' => !empty($definition['requiresintegrityclearance']),
                'recognitiontype' => 'internal_uckk_recognition',
            ],
        ];
    }

    /**
     * Prepare all canonical badge seed definitions.
     *
     * @param int $courseid Course id. Zero indicates site badges.
     * @return array<int, array<string, mixed>>
     */
    public static function get_all_seed_definitions(int $courseid = 0): array {
        $seeds = [];

        foreach (self::get_canonical_keys() as $key) {
            $seeds[] = self::get_seed_definition($key, $courseid);
        }

        return $seeds;
    }

    /**
     * Check whether a user may manage UCKK badge coordination.
     *
     * This does not replace Moodle's own badge management capabilities.
     *
     * @param context|null $context Context. Defaults to system context.
     * @param int|null $userid Optional user id.
     * @return bool
     */
    public static function can_manage(?context $context = null, ?int $userid = null): bool {
        $context = $context ?? context_system::instance();

        return has_capability('local/uckk:managepathways', $context, $userid)
            || has_capability('local/uckk:manageprograms', $context, $userid)
            || has_capability('moodle/badges:manageglobalsettings', context_system::instance(), $userid);
    }

    /**
     * Check whether a user may view UCKK badge status.
     *
     * @param context|null $context Context. Defaults to system context.
     * @param int|null $userid Optional user id.
     * @return bool
     */
    public static function can_view(?context $context = null, ?int $userid = null): bool {
        $context = $context ?? context_system::instance();

        return has_capability('local/uckk:viewcampus', $context, $userid)
            || has_capability('moodle/badges:viewbadges', $context, $userid);
    }

    /**
     * Return a context appropriate for a badge.
     *
     * @param stdClass $badge Badge record.
     * @return context
     */
    public static function get_badge_context(stdClass $badge): context {
        if (!empty($badge->courseid)) {
            return context_course::instance((int)$badge->courseid);
        }

        return context_system::instance();
    }

    /**
     * Determine whether badges subsystem seems available.
     *
     * @return bool
     */
    public static function badges_available(): bool {
        return class_exists('\badge') && core_component::get_component_directory('core') !== null;
    }

    /**
     * Normalise a badge key.
     *
     * @param string $key Raw key.
     * @return string
     */
    public static function normalise_key(string $key): string {
        $key = trim(\core_text::strtolower($key));
        $key = str_replace(['-', ' ', '.'], '_', $key);
        $key = preg_replace('/[^a-z0-9_]/', '', $key);

        $aliases = [
            'joueur' => self::BADGE_JOUEUR_INITIE,
            'initie' => self::BADGE_JOUEUR_INITIE,
            'joueur_lucide_badge' => self::BADGE_JOUEUR_LUCIDE,
            'cartographe' => self::BADGE_CARTOGRAPHE_SYSTEMES,
            'cartographe_de_systemes' => self::BADGE_CARTOGRAPHE_SYSTEMES,
            'gardien_de_la_preuve' => self::BADGE_GARDIEN_PREUVE,
            'preuve' => self::BADGE_GARDIEN_PREUVE,
            'participant_assemblee' => self::BADGE_PARTICIPANT_ASSEMBLEE,
            'participant_d_assemblee' => self::BADGE_PARTICIPANT_ASSEMBLEE,
            'assemblee' => self::BADGE_PARTICIPANT_ASSEMBLEE,
            'batisseur' => self::BADGE_BATISSEUR_PROTOTYPE,
            'batisseur_de_prototype' => self::BADGE_BATISSEUR_PROTOTYPE,
            'prototype' => self::BADGE_BATISSEUR_PROTOTYPE,
            'archiviste' => self::BADGE_ARCHIVISTE_DECISION,
            'archiviste_de_decision' => self::BADGE_ARCHIVISTE_DECISION,
            'defi' => self::BADGE_DEFI_KING_KLOWN,
            'defi_king_klown_valide' => self::BADGE_DEFI_KING_KLOWN,
            'king_klown' => self::BADGE_DEFI_KING_KLOWN,
            'inquisition' => self::BADGE_INQUISITION_METHODOLOGIQUE,
            'inquisition_methodologique_reussie' => self::BADGE_INQUISITION_METHODOLOGIQUE,
            'architecte_du_sens' => self::BADGE_ARCHITECTE_SENS,
            'architecte_d_opportunites' => self::BADGE_ARCHITECTE_OPPORTUNITES,
            'gardien_des_systemes_vivants' => self::BADGE_GARDIEN_SYSTEMES_VIVANTS,
            'ia' => self::BADGE_IA_GOUVERNABLE,
            'intelligence_artificielle_gouvernable' => self::BADGE_IA_GOUVERNABLE,
            'grand_jeu' => self::BADGE_GRAND_JEU_SOCIAL,
            'grand_jeu_social_badge' => self::BADGE_GRAND_JEU_SOCIAL,
            'koa_digital' => self::BADGE_KOA_DIGITAL,
            'koa_digital_ecosystem_badge' => self::BADGE_KOA_DIGITAL,
        ];

        return $aliases[$key] ?? $key;
    }

    /**
     * Return a canonical badge as a simple object.
     *
     * @param string $key Badge key.
     * @return stdClass|null
     */
    public static function get_canonical_badge_object(string $key): ?stdClass {
        $definition = self::get_canonical_badge($key);

        if ($definition === null) {
            return null;
        }

        return (object)$definition;
    }

    /**
     * Return all canonical badges as objects.
     *
     * @return stdClass[]
     */
    public static function get_canonical_badge_objects(): array {
        return array_map(static function(array $definition): stdClass {
            return (object)$definition;
        }, self::get_canonical_badges());
    }
}