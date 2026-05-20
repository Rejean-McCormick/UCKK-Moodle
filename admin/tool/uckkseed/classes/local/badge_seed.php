<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

/**
 * Badge preset seeder for the UCKK seed tool.
 *
 * @package    tool_uckkseed
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace tool_uckkseed\local;

use html_writer;
use stdClass;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/badgeslib.php');

/**
 * Seeds canonical UCKK badge definitions.
 *
 * This class creates or updates badge definitions only. It must not award
 * badges, certify competencies, validate evidence, resolve integrity cases,
 * bypass archive validation, or convert symbolic UCKK distinctions into Moodle
 * technical roles.
 */
final class badge_seed {
    /** Component owning this seeder. */
    public const COMPONENT = 'tool_uckkseed';

    /** Preset id. */
    public const PRESET = 'badges';

    /** Target type for validation messages. */
    public const TARGET_TYPE = 'badge';

    /** Preset schema. */
    public const SCHEMA = 'uckkseed.preset.v1';

    /** Mode: dry run. */
    public const MODE_DRY_RUN = 'dry_run';

    /** Mode: apply. */
    public const MODE_APPLY = 'apply';

    /** Mode: report. */
    public const MODE_REPORT = 'report';

    /** Mode: rollback plan. */
    public const MODE_ROLLBACK_PLAN = 'rollback_plan';

    /** Status: completed. */
    public const STATUS_COMPLETED = 'completed';

    /** Status: failed. */
    public const STATUS_FAILED = 'failed';

    /** Status: warning. */
    public const STATUS_WARNING = 'warning';

    /** Severity: info. */
    public const SEVERITY_INFO = 'info';

    /** Severity: success. */
    public const SEVERITY_SUCCESS = 'success';

    /** Severity: warning. */
    public const SEVERITY_WARNING = 'warning';

    /** Severity: error. */
    public const SEVERITY_ERROR = 'error';

    /** Severity: blocker. */
    public const SEVERITY_BLOCKER = 'blocker';

    /** Default badge language. */
    private const DEFAULT_LANGUAGE = 'fr';

    /** Seed-managed metadata key. */
    private const METADATA_MANAGED_BY = 'managedby';

    /** Seed manager name. */
    private const MANAGED_BY = 'tool_uckkseed';

    /** Supported UCKK badge award criteria. */
    private const SUPPORTED_CRITERIA = [
        'pathway_completion',
        'program_completion',
        'course_completion',
        'challenge_completion',
        'assembly_participation',
        'decision_record',
        'integrity_review',
        'ai_log',
        'evidence_submission',
        'human_validation',
        'competency_threshold',
        'competency_completion',
        'archive_or_portfolio',
        'no_unresolved_integrity_block',
    ];

    /** Criterion aliases accepted from generated registry JSON and older defaults. */
    private const CRITERION_ALIASES = [
        'competency_completion' => 'competency_threshold',
        'competency_ids' => 'competency_threshold',
        'archive' => 'archive_or_portfolio',
        'portfolio' => 'archive_or_portfolio',
        'proof_submission' => 'evidence_submission',
        'proof' => 'evidence_submission',
        'mentor_validation' => 'human_validation',
        'human_review' => 'human_validation',
        'pathway' => 'pathway_completion',
        'program' => 'program_completion',
        'course' => 'course_completion',
        'challenge_validation' => 'challenge_completion',
        'challenge' => 'challenge_completion',
    ];

    /** Canonical badge keys. */
    private const CANONICAL_BADGES = [
        'joueur_initie',
        'joueur_lucide',
        'cartographe_systemes',
        'gardien_preuve',
        'participant_assemblee',
        'batisseur_prototype',
        'archiviste_decision',
        'defi_king_klown',
        'inquisition_methodologique',
        'architecte_sens',
        'architecte_opportunites',
        'gardien_systemes_vivants',
        'ia_gouvernable',
        'grand_jeu_social',
        'koa_digital_ecosystem',
        'architecture_sociotechnique',
        'sciences_politiques',
        'economie',
        'ecologie',
        'metaphysique',
        'linguistique_architecture_du_sens',
        'intervention_sociale',
        'medias_vivants_theatre_public',
    ];

    /** Canonical competency idnumbers. */
    private const COMPETENCY_IDS = [
        'UCKK-COMP-001',
        'UCKK-COMP-002',
        'UCKK-COMP-003',
        'UCKK-COMP-004',
        'UCKK-COMP-005',
        'UCKK-COMP-006',
        'UCKK-COMP-007',
        'UCKK-COMP-008',
        'UCKK-COMP-009',
        'UCKK-COMP-010',
        'UCKK-COMP-011',
        'UCKK-COMP-012',
        'UCKK-COMP-013',
        'UCKK-COMP-014',
    ];

    /** Default badge definitions. */
    private const DEFAULT_BADGES = [
        [
            'key' => 'joueur_initie',
            'name' => 'Joueur initié',
            'description' => 'Première reconnaissance interne UCKK pour l’entrée documentée dans le campus, le tronc commun et le cycle Connaître → Choisir → Agir → Se souvenir.',
            'type' => 'site',
            'criteria' => [
                'course_completion',
                'evidence_submission',
                'human_validation',
                'competency_threshold',
                'archive_or_portfolio',
                'no_unresolved_integrity_block',
            ],
            'competencies' => ['UCKK-COMP-001', 'UCKK-COMP-014'],
            'requiredarchive' => true,
            'requireshumanvalidation' => true,
            'metadata' => [
                'category' => 'foundational',
                'symbolic_role' => 'joueur',
            ],
        ],
        [
            'key' => 'joueur_lucide',
            'name' => 'Joueur lucide',
            'description' => 'Reconnaissance interne pour un Joueur capable de lire les systèmes, distinguer fiction et fait, produire des preuves et documenter ses choix.',
            'type' => 'site',
            'criteria' => [
                'course_completion',
                'evidence_submission',
                'human_validation',
                'competency_threshold',
                'archive_or_portfolio',
                'no_unresolved_integrity_block',
            ],
            'competencies' => ['UCKK-COMP-001', 'UCKK-COMP-003', 'UCKK-COMP-005', 'UCKK-COMP-014'],
            'requiredarchive' => true,
            'requireshumanvalidation' => true,
            'metadata' => [
                'category' => 'foundational',
                'symbolic_role' => 'joueur_lucide',
                'not_moodle_role' => true,
            ],
        ],
        [
            'key' => 'cartographe_systemes',
            'name' => 'Cartographe de systèmes',
            'description' => 'Reconnaissance interne pour la capacité à cartographier des systèmes, flux, pouvoirs, preuves et zones d’incertitude.',
            'type' => 'site',
            'criteria' => [
                'evidence_submission',
                'human_validation',
                'competency_threshold',
                'archive_or_portfolio',
                'no_unresolved_integrity_block',
            ],
            'competencies' => ['UCKK-COMP-002', 'UCKK-COMP-011'],
            'requiredarchive' => true,
            'requireshumanvalidation' => true,
            'metadata' => [
                'category' => 'foundational',
                'symbolic_role' => 'cartographe',
                'not_moodle_role' => true,
            ],
        ],
        [
            'key' => 'gardien_preuve',
            'name' => 'Gardien de la preuve',
            'description' => 'Reconnaissance interne pour la production, la protection, la provenance et la discussion responsable des preuves.',
            'type' => 'site',
            'criteria' => [
                'evidence_submission',
                'human_validation',
                'competency_threshold',
                'archive_or_portfolio',
                'no_unresolved_integrity_block',
            ],
            'competencies' => ['UCKK-COMP-003', 'UCKK-COMP-005', 'UCKK-COMP-009'],
            'requiredarchive' => true,
            'requireshumanvalidation' => true,
            'metadata' => [
                'category' => 'foundational',
                'symbolic_role' => 'gardien_preuve',
                'not_moodle_role' => true,
            ],
        ],
        [
            'key' => 'participant_assemblee',
            'name' => 'Participant d’Assemblée',
            'description' => 'Reconnaissance interne pour participation documentée à une Assemblée UCKK avec motion, argument, vote, décision ou procès-verbal.',
            'type' => 'site',
            'criteria' => [
                'assembly_participation',
                'evidence_submission',
                'human_validation',
                'archive_or_portfolio',
                'no_unresolved_integrity_block',
            ],
            'competencies' => ['UCKK-COMP-006', 'UCKK-COMP-008', 'UCKK-COMP-013'],
            'requiredarchive' => true,
            'requireshumanvalidation' => true,
            'metadata' => [
                'category' => 'foundational',
                'source_component' => 'mod_uckkassembly',
            ],
        ],
        [
            'key' => 'batisseur_prototype',
            'name' => 'Bâtisseur de prototype',
            'description' => 'Reconnaissance interne pour la construction d’un artefact utile, documenté, contestable et archivable.',
            'type' => 'site',
            'criteria' => [
                'evidence_submission',
                'human_validation',
                'competency_threshold',
                'archive_or_portfolio',
                'no_unresolved_integrity_block',
            ],
            'competencies' => ['UCKK-COMP-012', 'UCKK-COMP-013'],
            'requiredarchive' => true,
            'requireshumanvalidation' => true,
            'metadata' => [
                'category' => 'foundational',
                'symbolic_role' => 'batisseur',
                'not_moodle_role' => true,
            ],
        ],
        [
            'key' => 'archiviste_decision',
            'name' => 'Archiviste de décision',
            'description' => 'Reconnaissance interne pour documentation et archivage fiable d’une décision, de sa provenance, de sa version et de son contexte.',
            'type' => 'site',
            'criteria' => [
                'decision_record',
                'evidence_submission',
                'human_validation',
                'archive_or_portfolio',
                'no_unresolved_integrity_block',
            ],
            'competencies' => ['UCKK-COMP-008', 'UCKK-COMP-009'],
            'requiredarchive' => true,
            'requireshumanvalidation' => true,
            'metadata' => [
                'category' => 'foundational',
                'source_component' => 'mod_uckkarchive',
            ],
        ],
        [
            'key' => 'defi_king_klown',
            'name' => 'Défi King Klown validé',
            'description' => 'Reconnaissance interne pour un Défi King Klown complété, évalué, validé humainement et archivé.',
            'type' => 'site',
            'criteria' => [
                'challenge_validation',
                'evidence_submission',
                'human_validation',
                'archive_or_portfolio',
                'no_unresolved_integrity_block',
            ],
            'competencies' => ['UCKK-COMP-005', 'UCKK-COMP-007', 'UCKK-COMP-013'],
            'requiredarchive' => true,
            'requireshumanvalidation' => true,
            'metadata' => [
                'category' => 'foundational',
                'source_component' => 'mod_uckkchallenge',
            ],
        ],
        [
            'key' => 'inquisition_methodologique',
            'name' => 'Inquisition méthodologique réussie',
            'description' => 'Reconnaissance interne pour une correction, contestation ou vérification méthodologique menée avec rigueur, dignité et traçabilité.',
            'type' => 'site',
            'criteria' => [
                'integrity_review',
                'evidence_submission',
                'human_validation',
                'archive_or_portfolio',
                'no_unresolved_integrity_block',
            ],
            'competencies' => ['UCKK-COMP-010', 'UCKK-COMP-011', 'UCKK-COMP-013'],
            'requiredarchive' => true,
            'requireshumanvalidation' => true,
            'metadata' => [
                'category' => 'foundational',
                'source_component' => 'tool_uckkintegrity',
            ],
        ],
        [
            'key' => 'architecte_sens',
            'name' => 'Architecte du sens',
            'description' => 'Reconnaissance interne pour la conception claire de relations, récits, concepts et décisions qui augmentent la compréhension collective.',
            'type' => 'site',
            'criteria' => [
                'evidence_submission',
                'human_validation',
                'competency_threshold',
                'archive_or_portfolio',
                'no_unresolved_integrity_block',
            ],
            'competencies' => ['UCKK-COMP-001', 'UCKK-COMP-003', 'UCKK-COMP-014'],
            'requiredarchive' => true,
            'requireshumanvalidation' => true,
            'metadata' => [
                'category' => 'symbolic_distinction',
                'symbolic_role' => 'architecte_sens',
                'not_moodle_role' => true,
            ],
        ],
        [
            'key' => 'architecte_opportunites',
            'name' => 'Architecte d’opportunités',
            'description' => 'Reconnaissance interne pour la transformation d’une lecture de système en possibilités d’action responsables.',
            'type' => 'site',
            'criteria' => [
                'evidence_submission',
                'human_validation',
                'competency_threshold',
                'archive_or_portfolio',
                'no_unresolved_integrity_block',
            ],
            'competencies' => ['UCKK-COMP-002', 'UCKK-COMP-007', 'UCKK-COMP-012'],
            'requiredarchive' => true,
            'requireshumanvalidation' => true,
            'metadata' => [
                'category' => 'symbolic_distinction',
                'symbolic_role' => 'architecte_opportunites',
                'not_moodle_role' => true,
            ],
        ],
        [
            'key' => 'gardien_systemes_vivants',
            'name' => 'Gardien des systèmes vivants',
            'description' => 'Reconnaissance interne pour la protection active des systèmes humains, écologiques, institutionnels ou sociotechniques.',
            'type' => 'site',
            'criteria' => [
                'evidence_submission',
                'human_validation',
                'competency_threshold',
                'archive_or_portfolio',
                'no_unresolved_integrity_block',
            ],
            'competencies' => ['UCKK-COMP-007', 'UCKK-COMP-010', 'UCKK-COMP-013'],
            'requiredarchive' => true,
            'requireshumanvalidation' => true,
            'metadata' => [
                'category' => 'symbolic_distinction',
                'symbolic_role' => 'gardien_systemes_vivants',
                'not_moodle_role' => true,
            ],
        ],
        [
            'key' => 'ia_gouvernable',
            'name' => 'IA gouvernable',
            'description' => 'Reconnaissance interne pour l’usage non souverain, vérifiable, incertain et humainement validé de l’intelligence artificielle.',
            'type' => 'site',
            'criteria' => [
                'ai_log',
                'evidence_submission',
                'human_validation',
                'competency_threshold',
                'archive_or_portfolio',
                'no_unresolved_integrity_block',
            ],
            'competencies' => ['UCKK-COMP-004', 'UCKK-COMP-010', 'UCKK-COMP-013'],
            'requiredarchive' => true,
            'requireshumanvalidation' => true,
            'metadata' => [
                'category' => 'program',
                'program' => 'intelligence_artificielle_gouvernable',
                'ai_policy_non_sovereign' => true,
            ],
        ],
        [
            'key' => 'grand_jeu_social',
            'name' => 'Grand Jeu social',
            'description' => 'Reconnaissance interne de programme pour le Grand Jeu social.',
            'type' => 'site',
            'criteria' => [
                'program_completion',
                'evidence_submission',
                'human_validation',
                'competency_threshold',
                'archive_or_portfolio',
                'no_unresolved_integrity_block',
            ],
            'competencies' => ['UCKK-COMP-001', 'UCKK-COMP-007', 'UCKK-COMP-014'],
            'requiredarchive' => true,
            'requireshumanvalidation' => true,
            'metadata' => [
                'category' => 'program',
                'program' => 'grand_jeu_social',
            ],
        ],
        [
            'key' => 'koa_digital_ecosystem',
            'name' => 'kOA Digital Ecosystem',
            'description' => 'Reconnaissance interne de programme pour l’architecture de l’écosystème digital kOA.',
            'type' => 'site',
            'criteria' => [
                'program_completion',
                'evidence_submission',
                'human_validation',
                'competency_threshold',
                'archive_or_portfolio',
                'no_unresolved_integrity_block',
            ],
            'competencies' => ['UCKK-COMP-002', 'UCKK-COMP-004', 'UCKK-COMP-012'],
            'requiredarchive' => true,
            'requireshumanvalidation' => true,
            'metadata' => [
                'category' => 'program',
                'program' => 'architecture_ecosysteme_digital_koa',
            ],
        ],
    ];

    /**
     * Validate badge preset items.
     *
     * @param array $items Preset item rows.
     * @param array<string, mixed> $options Runtime options.
     * @return validation_result
     */
    public function validate(array $items, array $options = []): validation_result {
        $result = $this->new_result(get_string('badgesvalidated', 'tool_uckkseed'));

        if (!$this->badges_enabled()) {
            $this->add_message(
                $result,
                self::SEVERITY_BLOCKER,
                self::PRESET,
                '',
                get_string('badgesdisabled', 'tool_uckkseed')
            );
        }

        if (empty($items)) {
            $this->add_message(
                $result,
                self::SEVERITY_WARNING,
                self::PRESET,
                '',
                get_string('presetempty', 'tool_uckkseed', self::PRESET)
            );

            $this->finish_result($result);
            return $result;
        }

        $seen = [];

        foreach ($items as $index => $item) {
            $item = $this->normalise_item($item);
            $targetkey = $item['key'] !== '' ? $item['key'] : 'row_' . $index;

            if ($item['key'] === '') {
                $this->add_message(
                    $result,
                    self::SEVERITY_ERROR,
                    self::PRESET,
                    $targetkey,
                    get_string('badgeseedmissingkey', 'tool_uckkseed')
                );
            }

            if ($item['name'] === '') {
                $this->add_message(
                    $result,
                    self::SEVERITY_ERROR,
                    self::PRESET,
                    $targetkey,
                    get_string('badgeseedmissingname', 'tool_uckkseed')
                );
            }

            if ($item['description'] === '') {
                $this->add_message(
                    $result,
                    self::SEVERITY_ERROR,
                    self::PRESET,
                    $targetkey,
                    get_string('badgeseedmissingdescription', 'tool_uckkseed')
                );
            }

            if (!in_array($item['type'], ['site', 'course'], true)) {
                $this->add_message(
                    $result,
                    self::SEVERITY_ERROR,
                    self::PRESET,
                    $targetkey,
                    get_string('badgeseedinvalidtype', 'tool_uckkseed')
                );
            }

            if ($item['type'] === 'course' && empty($item['courseidnumber'])) {
                $this->add_message(
                    $result,
                    self::SEVERITY_ERROR,
                    self::PRESET,
                    $targetkey,
                    get_string('badgeseedmissingcourse', 'tool_uckkseed')
                );
            }

            if (isset($seen[$item['key']])) {
                $this->add_message(
                    $result,
                    self::SEVERITY_ERROR,
                    self::PRESET,
                    $targetkey,
                    get_string('badgeseedduplicatekey', 'tool_uckkseed', $item['key'])
                );
            }

            if ($item['key'] !== '') {
                $seen[$item['key']] = true;
            }

            if (!in_array($item['key'], self::CANONICAL_BADGES, true)) {
                $this->add_message(
                    $result,
                    self::SEVERITY_WARNING,
                    self::PRESET,
                    $targetkey,
                    get_string('badgeseednoncanonical', 'tool_uckkseed', $item['key'])
                );
            }

            $this->validate_required_award_rules($result, $item);

            foreach ($item['competencies'] as $competencyidnumber) {
                if (!$this->is_known_competency_reference($competencyidnumber)) {
                    $this->add_message(
                        $result,
                        self::SEVERITY_WARNING,
                        self::PRESET,
                        $targetkey,
                        get_string('badgeseedunknowncompetency', 'tool_uckkseed', $competencyidnumber)
                    );
                }
            }

            if (!empty($item['metadata']['not_moodle_role']) && !empty($item['metadata']['symbolic_role'])) {
                $this->add_message(
                    $result,
                    self::SEVERITY_INFO,
                    self::PRESET,
                    $targetkey,
                    get_string('badgeseedsymbolicroleprotected', 'tool_uckkseed', $item['metadata']['symbolic_role'])
                );
            }

            $this->increment($result, 'skipped');
        }

        $this->finish_result($result);
        return $result;
    }

    /**
     * Apply badge preset items.
     *
     * This creates or updates seed-managed Moodle badge definitions. It does not
     * award badges to users and does not create automatic criteria that would
     * bypass UCKK evidence, archive, and human validation workflows.
     *
     * @param array $items Preset item rows.
     * @param array<string, mixed> $options Runtime options.
     * @return validation_result
     */
    public function apply(array $items, array $options = []): validation_result {
        $mode = $this->normalise_mode((string)($options['mode'] ?? self::MODE_APPLY));
        $dryrun = $mode === self::MODE_DRY_RUN || !empty($options['dryrun']);
        $rollbackplan = $mode === self::MODE_ROLLBACK_PLAN || !empty($options['rollbackplan']);

        $validation = $this->validate($items, $options);

        if ($validation->has_errors()) {
            return $validation;
        }

        $result = $this->new_result(
            $dryrun
                ? get_string('badgesdryruncomplete', 'tool_uckkseed')
                : get_string('badgesseeded', 'tool_uckkseed')
        );

        foreach ($items as $item) {
            $item = $this->normalise_item($item);

            if ($item['key'] === '') {
                $this->increment($result, 'failed');
                continue;
            }

            $existing = $this->get_existing_badge($item);

            if ($dryrun || $rollbackplan) {
                $this->add_message(
                    $result,
                    self::SEVERITY_INFO,
                    self::PRESET,
                    $item['key'],
                    $existing === null
                        ? get_string('badgeseedwouldcreate', 'tool_uckkseed', $item['key'])
                        : get_string('badgeseedwouldupdate', 'tool_uckkseed', $item['key']),
                    [
                        'existing' => $existing,
                        'proposed' => $item,
                    ]
                );
                $this->increment($result, 'skipped');
                continue;
            }

            if ($existing === null) {
                $badgeid = $this->create_badge($item);

                $this->add_message(
                    $result,
                    self::SEVERITY_SUCCESS,
                    self::PRESET,
                    $item['key'],
                    get_string('badgeseedcreated', 'tool_uckkseed', $item['key']),
                    [
                        'badgeid' => $badgeid,
                    ]
                );

                $this->increment($result, 'created');
            } else {
                $this->update_badge((int)$existing->id, $item);

                $this->add_message(
                    $result,
                    self::SEVERITY_SUCCESS,
                    self::PRESET,
                    $item['key'],
                    get_string('badgeseedupdated', 'tool_uckkseed', $item['key']),
                    [
                        'badgeid' => (int)$existing->id,
                    ]
                );

                $this->increment($result, 'updated');
            }
        }

        $this->finish_result($result);
        return $result;
    }

    /**
     * Reset seed-managed badge definitions.
     *
     * This deletes only badges that carry the seed-managed uniquehash used by
     * this seeder. It must not remove manually created badges or revoke awards
     * outside Moodle's normal badge deletion behaviour.
     *
     * @param array $items Preset item rows.
     * @param array<string, mixed> $options Runtime options.
     * @return validation_result
     */
    public function reset(array $items, array $options = []): validation_result {
        global $DB;

        $mode = $this->normalise_mode((string)($options['mode'] ?? self::MODE_DRY_RUN));
        $dryrun = $mode === self::MODE_DRY_RUN || !empty($options['dryrun']);
        $confirmed = !empty($options['confirm']);

        $result = $this->new_result(
            $dryrun
                ? get_string('badgesresetdryruncomplete', 'tool_uckkseed')
                : get_string('badgesresetcomplete', 'tool_uckkseed')
        );

        if (!$dryrun && !$confirmed) {
            $this->add_message(
                $result,
                self::SEVERITY_BLOCKER,
                self::PRESET,
                '',
                get_string('confirmationrequired', 'tool_uckkseed')
            );
            $this->finish_result($result);
            return $result;
        }

        $keys = [];

        foreach ($items as $item) {
            $item = $this->normalise_item($item);

            if ($item['key'] !== '') {
                $keys[] = $item['key'];
            }
        }

        if (empty($keys)) {
            $keys = self::CANONICAL_BADGES;
        }

        foreach (array_unique($keys) as $key) {
            $uniquehash = $this->get_uniquehash($key);
            $badge = $DB->get_record('badge', ['uniquehash' => $uniquehash], '*', IGNORE_MISSING);

            if (!$badge) {
                $this->add_message(
                    $result,
                    self::SEVERITY_INFO,
                    self::PRESET,
                    $key,
                    get_string('badgeseedalreadyabsent', 'tool_uckkseed', $key)
                );
                $this->increment($result, 'skipped');
                continue;
            }

            if ($dryrun) {
                $this->add_message(
                    $result,
                    self::SEVERITY_INFO,
                    self::PRESET,
                    $key,
                    get_string('badgeseedwouldremove', 'tool_uckkseed', $key),
                    [
                        'badgeid' => (int)$badge->id,
                    ]
                );
                $this->increment($result, 'skipped');
                continue;
            }

            $this->delete_seeded_badge((int)$badge->id);

            $this->add_message(
                $result,
                self::SEVERITY_SUCCESS,
                self::PRESET,
                $key,
                get_string('badgeseedremoved', 'tool_uckkseed', $key),
                [
                    'badgeid' => (int)$badge->id,
                ]
            );

            $this->increment($result, 'updated');
        }

        $this->finish_result($result);
        return $result;
    }

    /**
     * Export canonical badge preset data.
     *
     * @param array<string, mixed> $options Runtime options.
     * @return array<string, mixed>
     */
    public function export(array $options = []): array {
        global $DB;

        $items = [];

        if (empty($options['defaults'])) {
            foreach (self::CANONICAL_BADGES as $key) {
                $badge = $DB->get_record('badge', ['uniquehash' => $this->get_uniquehash($key)], '*', IGNORE_MISSING);

                if (!$badge) {
                    continue;
                }

                $items[] = $this->badge_record_to_item($badge);
            }
        }

        if (empty($items)) {
            $items = self::DEFAULT_BADGES;
        }

        $normalised = [];

        foreach ($items as $item) {
            $normalised[] = $this->normalise_item($item);
        }

        return [
            'schema' => self::SCHEMA,
            'component' => self::COMPONENT,
            'preset' => self::PRESET,
            'version' => 2026051200,
            'items' => $normalised,
        ];
    }

    /**
     * Validate UCKK badge award rules.
     *
     * Criteria are policy descriptors, not automatic award bypasses. The final
     * registry supports badges with different combinations of course, pathway,
     * programme, challenge, assembly, evidence, archive, integrity and human
     * validation criteria.
     *
     * @param validation_result $result Result object.
     * @param array<string, mixed> $item Normalised badge item.
     */
    private function validate_required_award_rules(validation_result $result, array $item): void {
        if (empty($item['criteria'])) {
            $this->add_message(
                $result,
                self::SEVERITY_ERROR,
                self::PRESET,
                $item['key'],
                get_string('badgeseedmissingcriterion', 'tool_uckkseed', 'supported_uckk_criterion')
            );
            return;
        }

        foreach ($item['criteria'] as $criterion) {
            if (!in_array($criterion, self::SUPPORTED_CRITERIA, true)) {
                $this->add_message(
                    $result,
                    self::SEVERITY_WARNING,
                    self::PRESET,
                    $item['key'],
                    'Unsupported UCKK badge criterion: ' . $criterion,
                    [
                        'criterion' => $criterion,
                        'supported' => self::SUPPORTED_CRITERIA,
                    ]
                );
            }
        }

        if (!$item['requiredarchive'] && in_array('archive_or_portfolio', $item['criteria'], true)) {
            $this->add_message(
                $result,
                self::SEVERITY_ERROR,
                self::PRESET,
                $item['key'],
                get_string('badgeseedrequiresarchive', 'tool_uckkseed')
            );
        }

        if (!$item['requireshumanvalidation'] && in_array('human_validation', $item['criteria'], true)) {
            $this->add_message(
                $result,
                self::SEVERITY_ERROR,
                self::PRESET,
                $item['key'],
                get_string('badgeseedrequireshumanvalidation', 'tool_uckkseed')
            );
        }
    }


    /**
     * Create a Moodle badge record.
     *
     * @param array<string, mixed> $item Normalised badge item.
     * @return int Badge id.
     */
    private function create_badge(array $item): int {
        global $DB, $USER, $SITE;

        $now = time();
        $badge = new stdClass();

        $badge->name = $item['name'];
        $badge->description = $item['description'];
        $badge->timecreated = $now;
        $badge->timemodified = $now;
        $badge->usercreated = (int)($USER->id ?? 0);
        $badge->usermodified = (int)($USER->id ?? 0);
        $badge->issuername = format_string($SITE->fullname);
        $badge->issuerurl = $this->get_site_url();
        $badge->issuercontact = $this->get_support_email();
        $badge->expiredate = null;
        $badge->expireperiod = null;
        $badge->type = $item['type'] === 'course' ? BADGE_TYPE_COURSE : BADGE_TYPE_SITE;
        $badge->courseid = $item['type'] === 'course' ? $this->get_course_id_by_idnumber($item['courseidnumber']) : null;
        $badge->message = get_string('badgeseeddefaultmessage', 'tool_uckkseed', $item['name']);
        $badge->messagesubject = get_string('badgeseeddefaultsubject', 'tool_uckkseed', $item['name']);
        $badge->attachment = 1;
        $badge->notification = 0;
        $badge->status = BADGE_STATUS_INACTIVE;
        $badge->nextcron = 0;
        $badge->version = '1.0';
        $badge->language = $item['language'];
        $badge->imageauthorname = 'Univers-Cité King Klown';
        $badge->imageauthoremail = $this->get_support_email();
        $badge->imageauthorurl = $this->get_site_url();
        $badge->imagecaption = get_string('badgeseedimagecaption', 'tool_uckkseed');
        $badge->uniquehash = $this->get_uniquehash($item['key']);

        $this->add_optional_field($badge, 'visible', 1);
        $this->add_optional_field($badge, 'metadata', $this->encode_metadata($item));

        $badgeid = (int)$DB->insert_record('badge', $badge);

        $this->replace_seeded_criteria($badgeid, $item);
        $this->set_config_marker($item, $badgeid);

        return $badgeid;
    }

    /**
     * Update a Moodle badge record.
     *
     * @param int $badgeid Badge id.
     * @param array<string, mixed> $item Normalised badge item.
     */
    private function update_badge(int $badgeid, array $item): void {
        global $DB, $USER;

        $badge = $DB->get_record('badge', ['id' => $badgeid], '*', MUST_EXIST);

        $badge->name = $item['name'];
        $badge->description = $item['description'];
        $badge->timemodified = time();
        $badge->usermodified = (int)($USER->id ?? 0);
        $badge->issuerurl = $this->get_site_url();
        $badge->issuercontact = $this->get_support_email();
        $badge->message = get_string('badgeseeddefaultmessage', 'tool_uckkseed', $item['name']);
        $badge->messagesubject = get_string('badgeseeddefaultsubject', 'tool_uckkseed', $item['name']);
        $badge->attachment = 1;
        $badge->notification = 0;
        $badge->language = $item['language'];
        $badge->imagecaption = get_string('badgeseedimagecaption', 'tool_uckkseed');

        if (property_exists($badge, 'visible')) {
            $badge->visible = 1;
        }

        if (property_exists($badge, 'metadata')) {
            $badge->metadata = $this->encode_metadata($item);
        }

        $DB->update_record('badge', $badge);

        $this->replace_seeded_criteria($badgeid, $item);
        $this->set_config_marker($item, $badgeid);
    }

    /**
     * Replace seed-managed badge criteria.
     *
     * Criteria are intentionally descriptive/manual. UCKK award authority stays
     * with evidence, archive, competency and human review workflows.
     *
     * @param int $badgeid Badge id.
     * @param array<string, mixed> $item Normalised badge item.
     */
    private function replace_seeded_criteria(int $badgeid, array $item): void {
        global $DB;

        if (!$DB->get_manager()->table_exists('badge_criteria')) {
            return;
        }

        $existing = $DB->get_records('badge_criteria', ['badgeid' => $badgeid]);

        foreach ($existing as $criterion) {
            if ($DB->get_manager()->table_exists('badge_criteria_param')) {
                $DB->delete_records('badge_criteria_param', ['critid' => $criterion->id]);
            }

            $DB->delete_records('badge_criteria', ['id' => $criterion->id]);
        }

        $criterion = new stdClass();
        $criterion->badgeid = $badgeid;
        $criterion->criteriatype = defined('BADGE_CRITERIA_TYPE_MANUAL')
            ? BADGE_CRITERIA_TYPE_MANUAL
            : 1;
        $criterion->method = 1;
        $criterion->description = $this->build_criteria_description($item);
        $criterion->descriptionformat = FORMAT_HTML;

        $critid = (int)$DB->insert_record('badge_criteria', $criterion);

        if ($DB->get_manager()->table_exists('badge_criteria_param')) {
            foreach ($item['criteria'] as $criterionkey) {
                $param = new stdClass();
                $param->critid = $critid;
                $param->name = 'uckkcriterion';
                $param->value = $criterionkey;
                $DB->insert_record('badge_criteria_param', $param);
            }

            foreach ($item['competencies'] as $competencyidnumber) {
                $param = new stdClass();
                $param->critid = $critid;
                $param->name = 'uckkcompetency';
                $param->value = $competencyidnumber;
                $DB->insert_record('badge_criteria_param', $param);
            }
        }
    }

    /**
     * Delete a seed-managed badge and related criteria/config markers.
     *
     * @param int $badgeid Badge id.
     */
    private function delete_seeded_badge(int $badgeid): void {
        global $DB;

        $badge = $DB->get_record('badge', ['id' => $badgeid], '*', MUST_EXIST);
        $key = $this->get_key_from_uniquehash((string)$badge->uniquehash);

        if ($DB->get_manager()->table_exists('badge_criteria')) {
            $criteria = $DB->get_records('badge_criteria', ['badgeid' => $badgeid]);

            foreach ($criteria as $criterion) {
                if ($DB->get_manager()->table_exists('badge_criteria_param')) {
                    $DB->delete_records('badge_criteria_param', ['critid' => $criterion->id]);
                }

                $DB->delete_records('badge_criteria', ['id' => $criterion->id]);
            }
        }

        foreach ([
            'badge_issued',
            'badge_manual_award',
            'badge_backpack',
            'badge_external',
        ] as $table) {
            if ($DB->get_manager()->table_exists($table)) {
                $DB->delete_records($table, ['badgeid' => $badgeid]);
            }
        }

        $DB->delete_records('badge', ['id' => $badgeid]);

        if ($key !== '') {
            unset_config('badge_' . $key . '_id', self::COMPONENT);
            unset_config('badge_' . $key . '_definition', self::COMPONENT);
        }
    }

    /**
     * Get an existing seed-managed badge.
     *
     * @param array<string, mixed> $item Normalised item.
     * @return stdClass|null
     */
    private function get_existing_badge(array $item): ?stdClass {
        global $DB;

        $uniquehash = $this->get_uniquehash($item['key']);

        return $DB->get_record('badge', ['uniquehash' => $uniquehash], '*', IGNORE_MISSING) ?: null;
    }

    /**
     * Convert a Moodle badge record to a preset item.
     *
     * @param stdClass $badge Badge record.
     * @return array<string, mixed>
     */
    private function badge_record_to_item(stdClass $badge): array {
        $key = $this->get_key_from_uniquehash((string)$badge->uniquehash);
        $stored = (string)get_config(self::COMPONENT, 'badge_' . $key . '_definition');
        $decoded = json_decode($stored, true);

        if (is_array($decoded)) {
            return $decoded;
        }

        return [
            'key' => $key,
            'name' => (string)$badge->name,
            'description' => (string)$badge->description,
            'type' => ((int)$badge->type === BADGE_TYPE_COURSE) ? 'course' : 'site',
            'criteria' => [
                'evidence_submission',
                'human_validation',
                'competency_threshold',
                'archive_or_portfolio',
                'no_unresolved_integrity_block',
            ],
            'competencies' => [],
            'requiredarchive' => true,
            'requireshumanvalidation' => true,
            'metadata' => [
                self::METADATA_MANAGED_BY => self::MANAGED_BY,
            ],
        ];
    }

    /**
     * Normalise one badge item.
     *
     * Supports both the original hand-written badge seed shape and the final
     * academic_registry_json badge shape:
     * - key/name/description
     * - id/idnumber/title/short_title
     * - badge_type/recognition
     * - criteria or award_criteria as strings or objects with a type field
     * - competencies or linked_competency_ids as registry competency references
     *
     * @param mixed $item Raw item.
     * @return array<string, mixed>
     */
    private function normalise_item(mixed $item): array {
        if ($item instanceof stdClass) {
            $item = (array)$item;
        }

        if (!is_array($item)) {
            $item = [];
        }

        $recognition = $item['recognition'] ?? [];
        if ($recognition instanceof stdClass) {
            $recognition = (array)$recognition;
        }
        if (!is_array($recognition)) {
            $recognition = [];
        }

        $moodle = $item['moodle'] ?? [];
        if ($moodle instanceof stdClass) {
            $moodle = (array)$moodle;
        }
        if (!is_array($moodle)) {
            $moodle = [];
        }

        $rawkey = $item['key']
            ?? $item['shortname']
            ?? $item['idnumber']
            ?? $item['id']
            ?? '';
        $key = clean_param($this->normalise_key((string)$rawkey), PARAM_ALPHANUMEXT);

        $rawtype = (string)($item['type'] ?? $moodle['type'] ?? $item['badge_type'] ?? 'site');
        $type = $this->normalise_badge_type($rawtype);

        $metadata = $this->normalise_metadata($item['metadata'] ?? []);
        $metadata[self::METADATA_MANAGED_BY] = self::MANAGED_BY;

        foreach ([
            'id',
            'object_type',
            'idnumber',
            'badge_type',
            'status',
            'program_id',
            'pathway_id',
            'linked_course_ids',
            'recognition',
            'ai_metadata',
            'source',
        ] as $field) {
            if (array_key_exists($field, $item) && !array_key_exists($field, $metadata)) {
                $metadata[$field] = $item[$field];
            }
        }

        $criteriaitems = $this->normalise_criteria_payload($item['criteria'] ?? $item['award_criteria'] ?? []);
        $criteria = $this->normalise_criteria($item['criteria'] ?? $item['award_criteria'] ?? []);
        $competencies = $this->normalise_competencies(
            $item['competencies']
                ?? $item['linked_competency_ids']
                ?? $this->extract_competencies_from_criteria_payload($criteriaitems)
        );

        if (empty($criteria) && !empty($competencies)) {
            $criteria[] = 'competency_threshold';
        }

        if (empty($criteria)) {
            $criteria[] = 'human_validation';
        }

        $description = (string)(
            $item['description']
            ?? $recognition['public_status_notice']
            ?? $recognition['title']
            ?? $item['summary']
            ?? ''
        );

        return [
            'key' => $key,
            'name' => trim(clean_param((string)($item['name'] ?? $item['title'] ?? $item['short_title'] ?? $recognition['title'] ?? $this->fallback_name($key)), PARAM_TEXT)),
            'description' => trim(clean_param($description, PARAM_TEXT)),
            'type' => $type,
            'courseidnumber' => clean_param((string)($item['courseidnumber'] ?? $item['course'] ?? $moodle['courseidnumber'] ?? ''), PARAM_TEXT),
            'criteria' => array_values(array_unique($criteria)),
            'criteriaitems' => $criteriaitems,
            'competencies' => array_values(array_unique($competencies)),
            'requiredarchive' => $this->normalise_bool($item['requiredarchive'] ?? $item['required_archive'] ?? true),
            'requireshumanvalidation' => $this->normalise_bool($item['requireshumanvalidation'] ?? $item['requires_human_validation'] ?? true),
            'language' => clean_param((string)($item['language'] ?? $moodle['language'] ?? self::DEFAULT_LANGUAGE), PARAM_ALPHANUMEXT),
            'metadata' => $metadata,
        ];
    }



    /**
     * Normalise a badge key/id into the seed key shape.
     *
     * @param string $value Raw value.
     * @return string
     */
    private function normalise_key(string $value): string {
        $value = strtolower(trim($value));
        $value = preg_replace('/^[a-z]+:/', '', $value) ?? $value;
        $value = preg_replace('/[^a-z0-9_]+/', '_', $value) ?? $value;
        $value = trim($value, '_');

        return $value;
    }

    /**
     * Normalise Moodle badge type.
     *
     * @param string $type Raw type.
     * @return string
     */
    private function normalise_badge_type(string $type): string {
        $type = strtolower(trim($type));

        if ($type === 'course' || $type === 'course_badge') {
            return 'course';
        }

        return 'site';
    }

    /**
     * Normalise criterion objects/strings to criterion type keys.
     *
     * @param mixed $criteria Raw criteria.
     * @return string[]
     */
    private function normalise_criteria(mixed $criteria): array {
        $payload = $this->normalise_criteria_payload($criteria);
        $types = [];

        foreach ($payload as $criterion) {
            $type = $this->normalise_criterion_type((string)($criterion['type'] ?? ''));

            if ($type !== '') {
                $types[] = $type;
            }
        }

        return array_values(array_unique($types));
    }

    /**
     * Preserve criterion payload for metadata/export while accepting objects.
     *
     * @param mixed $criteria Raw criteria.
     * @return array<int, array<string, mixed>>
     */
    private function normalise_criteria_payload(mixed $criteria): array {
        if ($criteria instanceof stdClass) {
            $criteria = (array)$criteria;
        }

        if ($criteria === null || $criteria === '') {
            return [];
        }

        if (!is_array($criteria)) {
            $criteria = [$criteria];
        }

        $items = [];

        foreach ($criteria as $criterion) {
            if ($criterion instanceof stdClass) {
                $criterion = (array)$criterion;
            }

            if (is_array($criterion)) {
                $type = $this->normalise_criterion_type((string)($criterion['type'] ?? $criterion['criterion'] ?? ''));

                if ($type === '') {
                    continue;
                }

                $criterion['type'] = $type;
                $items[] = $criterion;
                continue;
            }

            $type = $this->normalise_criterion_type((string)$criterion);

            if ($type !== '') {
                $items[] = ['type' => $type];
            }
        }

        return $items;
    }

    /**
     * Normalise one criterion type, including aliases.
     *
     * @param string $type Raw type.
     * @return string
     */
    private function normalise_criterion_type(string $type): string {
        $type = strtolower(trim($type));
        $type = str_replace(['-', ' ', ':'], '_', $type);
        $type = preg_replace('/[^a-z0-9_]+/', '_', $type) ?? $type;
        $type = trim($type, '_');

        if ($type === '') {
            return '';
        }

        return self::CRITERION_ALIASES[$type] ?? $type;
    }

    /**
     * Normalise competency refs. Accepts final registry refs and old idnumbers.
     *
     * @param mixed $competencies Raw competencies.
     * @return string[]
     */
    private function normalise_competencies(mixed $competencies): array {
        if ($competencies instanceof stdClass) {
            $competencies = (array)$competencies;
        }

        if ($competencies === null || $competencies === '') {
            return [];
        }

        if (!is_array($competencies)) {
            $competencies = [$competencies];
        }

        $items = [];

        foreach ($competencies as $competency) {
            if ($competency instanceof stdClass) {
                $competency = (array)$competency;
            }

            if (is_array($competency)) {
                $value = (string)($competency['idnumber'] ?? $competency['id'] ?? $competency['key'] ?? '');
            } else {
                $value = (string)$competency;
            }

            $value = trim($value);

            if ($value !== '') {
                $items[] = clean_param($value, PARAM_TEXT);
            }
        }

        return array_values(array_unique($items));
    }

    /**
     * Extract competency refs from criteria payload.
     *
     * @param array<int, array<string, mixed>> $criteriaitems Criteria payload.
     * @return string[]
     */
    private function extract_competencies_from_criteria_payload(array $criteriaitems): array {
        $refs = [];

        foreach ($criteriaitems as $criterion) {
            foreach (['competency_ids', 'competencies', 'competency_idnumbers'] as $field) {
                if (empty($criterion[$field])) {
                    continue;
                }

                foreach ($this->normalise_competencies($criterion[$field]) as $ref) {
                    $refs[] = $ref;
                }
            }
        }

        return array_values(array_unique($refs));
    }

    /**
     * Check whether a competency reference is known or follows final syntax.
     *
     * @param string $idnumber Competency reference.
     * @return bool
     */
    private function is_known_competency_reference(string $idnumber): bool {
        if (in_array($idnumber, self::COMPETENCY_IDS, true)) {
            return true;
        }

        if (preg_match('/^UCKK-COMP-[A-Z0-9-]+$/', $idnumber) === 1) {
            return true;
        }

        if (preg_match('/^competency:[a-z0-9:_-]+$/', strtolower($idnumber)) === 1) {
            return true;
        }

        return false;
    }

    /**
     * Build human-readable criteria description.
     *
     * @param array<string, mixed> $item Normalised item.
     * @return string
     */
    private function build_criteria_description(array $item): string {
        $lines = [
            get_string('badgeseedcriterianotice', 'tool_uckkseed'),
            html_writer::start_tag('ul'),
        ];

        foreach ($item['criteria'] as $criterion) {
            $lines[] = html_writer::tag('li', s($criterion));
        }

        if (!empty($item['competencies'])) {
            $lines[] = html_writer::tag(
                'li',
                get_string('badgeseedcompetenciescriterion', 'tool_uckkseed', implode(', ', $item['competencies']))
            );
        }

        $lines[] = html_writer::end_tag('ul');

        return implode("\n", $lines);
    }

    /**
     * Store a config marker for a seed-managed badge.
     *
     * @param array<string, mixed> $item Normalised item.
     * @param int $badgeid Badge id.
     */
    private function set_config_marker(array $item, int $badgeid): void {
        set_config('badge_' . $item['key'] . '_id', $badgeid, self::COMPONENT);
        set_config(
            'badge_' . $item['key'] . '_definition',
            json_encode($item, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            self::COMPONENT
        );
    }

    /**
     * Add optional object property only when Moodle badge table has the field.
     *
     * @param stdClass $record Record.
     * @param string $field Field name.
     * @param mixed $value Field value.
     */
    private function add_optional_field(stdClass $record, string $field, mixed $value): void {
        global $DB;

        $columns = $DB->get_columns('badge');

        if (array_key_exists($field, $columns)) {
            $record->{$field} = $value;
        }
    }

    /**
     * Encode badge item metadata.
     *
     * @param array<string, mixed> $item Badge item.
     * @return string
     */
    private function encode_metadata(array $item): string {
        return json_encode($item['metadata'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Get course id by course idnumber.
     *
     * @param string $idnumber Course idnumber.
     * @return int|null
     */
    private function get_course_id_by_idnumber(string $idnumber): ?int {
        global $DB;

        if ($idnumber === '') {
            return null;
        }

        $courseid = $DB->get_field('course', 'id', ['idnumber' => $idnumber], IGNORE_MISSING);

        return $courseid ? (int)$courseid : null;
    }

    /**
     * Return whether Moodle badges are enabled.
     *
     * @return bool
     */
    private function badges_enabled(): bool {
        global $CFG;

        return empty($CFG->disablebadges);
    }

    /**
     * Get site URL.
     *
     * @return string
     */
    private function get_site_url(): string {
        global $CFG;

        return (string)$CFG->wwwroot;
    }

    /**
     * Get support email.
     *
     * @return string
     */
    private function get_support_email(): string {
        global $CFG;

        return (string)($CFG->supportemail ?? '');
    }

    /**
     * Generate deterministic badge unique hash.
     *
     * @param string $key Badge key.
     * @return string
     */
    private function get_uniquehash(string $key): string {
        return sha1(self::COMPONENT . ':' . self::PRESET . ':' . clean_param($key, PARAM_ALPHANUMEXT));
    }

    /**
     * Recover known badge key from uniquehash.
     *
     * @param string $uniquehash Unique hash.
     * @return string
     */
    private function get_key_from_uniquehash(string $uniquehash): string {
        foreach (self::CANONICAL_BADGES as $key) {
            if ($this->get_uniquehash($key) === $uniquehash) {
                return $key;
            }
        }

        return '';
    }

    /**
     * Normalise mode.
     *
     * @param string $mode Raw mode.
     * @return string
     */
    private function normalise_mode(string $mode): string {
        $mode = clean_param($mode, PARAM_ALPHANUMEXT);

        $allowed = [
            self::MODE_APPLY,
            self::MODE_DRY_RUN,
            self::MODE_REPORT,
            self::MODE_ROLLBACK_PLAN,
        ];

        return in_array($mode, $allowed, true) ? $mode : self::MODE_DRY_RUN;
    }

    /**
     * Normalise a list of strings.
     *
     * @param mixed $value Raw value.
     * @return string[]
     */
    private function normalise_list(mixed $value): array {
        if (is_string($value)) {
            $decoded = json_decode($value, true);

            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $value = $decoded;
            } else {
                $value = array_map('trim', explode(',', $value));
            }
        }

        if (!is_array($value)) {
            return [];
        }

        $items = [];

        foreach ($value as $item) {
            $item = clean_param((string)$item, PARAM_ALPHANUMEXT);

            if ($item !== '') {
                $items[] = $item;
            }
        }

        return array_values(array_unique($items));
    }

    /**
     * Normalise metadata.
     *
     * @param mixed $metadata Raw metadata.
     * @return array<string, mixed>
     */
    private function normalise_metadata(mixed $metadata): array {
        if ($metadata === null || $metadata === '') {
            return [];
        }

        if (is_string($metadata)) {
            $decoded = json_decode($metadata, true);

            if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
                return [];
            }

            return $decoded;
        }

        if ($metadata instanceof stdClass) {
            return (array)$metadata;
        }

        if (is_array($metadata)) {
            return $metadata;
        }

        return [];
    }

    /**
     * Normalise boolean values.
     *
     * @param mixed $value Raw value.
     * @return bool
     */
    private function normalise_bool(mixed $value): bool {
        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value)) {
            return $value === 1;
        }

        if (is_string($value)) {
            return in_array(strtolower($value), ['1', 'true', 'yes', 'enabled', 'on'], true);
        }

        return false;
    }

    /**
     * Build fallback display name from key.
     *
     * @param string $key Badge key.
     * @return string
     */
    private function fallback_name(string $key): string {
        $key = trim(str_replace('_', ' ', $key));

        if ($key === '') {
            return '';
        }

        return ucfirst($key);
    }

    /**
     * Create a validation result.
     *
     * @param string $summary Summary.
     * @return validation_result
     */
    private function new_result(string $summary): validation_result {
        return new validation_result(self::STATUS_COMPLETED, $summary, [
            'component' => self::COMPONENT,
            'preset' => self::PRESET,
            'targettype' => self::TARGET_TYPE,
        ]);
    }

    /**
     * Add a message to a validation result.
     *
     * @param validation_result $result Result object.
     * @param string $severity Severity.
     * @param string $preset Preset.
     * @param string $targetkey Target key.
     * @param string $message Message.
     * @param array<string, mixed> $metadata Metadata.
     */
    private function add_message(
        validation_result $result,
        string $severity,
        string $preset,
        string $targetkey,
        string $message,
        array $metadata = []
    ): void {
        $result->add_message(
            $severity,
            $message,
            self::COMPONENT,
            $preset,
            self::TARGET_TYPE,
            $targetkey,
            $metadata
        );
    }

    /**
     * Increment a count in a validation result.
     *
     * @param validation_result $result Result object.
     * @param string $key Count key.
     */
    private function increment(validation_result $result, string $key): void {
        $result->increment($key);
    }

    /**
     * Finalise validation result status flags.
     *
     * @param validation_result $result Result object.
     */
    private function finish_result(validation_result $result): void {
        $result->complete($result->get_summary());
    }
}

