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
    /** Owning component. */
    public const COMPONENT = 'tool_uckkseed';

    /** Preset id. */
    public const PRESET = 'badges';

    /** Preset schema id. */
    public const SCHEMA = 'uckkseed.preset.v1';

    /** Preset version. */
    public const VERSION = 2026051200;

    /** Metadata owner marker. */
    private const MANAGED_BY = 'tool_uckkseed';

    /** Metadata key: managed by. */
    private const METADATA_MANAGED_BY = 'managedby';

    /** Mode: dry run. */
    public const MODE_DRY_RUN = 'dry_run';

    /** Mode: apply. */
    public const MODE_APPLY = 'apply';

    /** Mode: report. */
    public const MODE_REPORT = 'report';

    /** Mode: rollback plan. */
    public const MODE_ROLLBACK_PLAN = 'rollback_plan';

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

    /** Canonical badge keys managed by this seed. */
    private const CANONICAL_BADGES = [
        'joueur_lucide',
        'grand_jeu_social',
        'koa_digital_ecosystem',
        'architecture_sociotechnique',
        'sciences_politiques',
        'economie',
        'ecologie',
        'metaphysique',
        'ia_gouvernable',
        'linguistique_architecture_du_sens',
        'intervention_sociale',
        'medias_vivants_theatre_public',
    ];

    /**
     * Default canonical badge definitions.
     *
     * @var array<int, array<string, mixed>>
     */
    private const DEFAULT_BADGES = [
        [
            'key' => 'tronc_commun_completion',
            'name' => 'Tronc commun UCKK complété',
            'description' => 'Reconnaît la complétion du tronc commun obligatoire UCKK.',
            'type' => 'site',
            'criteria' => [
                'evidence_submission',
                'human_validation',
                'competency_threshold',
                'archive_or_portfolio',
                'no_unresolved_integrity_block',
            ],
            'competencies' => [
                'UCKK-COMM-FOUNDATION',
            ],
            'requiredarchive' => true,
            'requireshumanvalidation' => true,
            'metadata' => [
                self::METADATA_MANAGED_BY => self::MANAGED_BY,
                'source_family' => 'UCKK canon',
                'not_moodle_role' => true,
                'symbolic_role' => 'Aspirant UCKK',
            ],
        ],
        [
            'key' => 'parchemin_grand_jeu_social_magie_operable',
            'name' => 'Parchemin — Grand Jeu social',
            'description' => 'Reconnaît une progression validée dans la Voie du Grand Jeu social au Niveau de Puissance opératoire.',
            'type' => 'site',
            'criteria' => [
                'evidence_submission',
                'human_validation',
                'competency_threshold',
                'archive_or_portfolio',
                'no_unresolved_integrity_block',
            ],
            'competencies' => [
                'UCKK-GJS-MAGIE-OPERABLE',
            ],
            'requiredarchive' => true,
            'requireshumanvalidation' => true,
            'metadata' => [
                self::METADATA_MANAGED_BY => self::MANAGED_BY,
                'source_family' => 'UCKK canon',
                'program' => 'GJS',
                'palier' => 'magie_operable',
            ],
        ],
        [
            'key' => 'parchemin_architecture_ecosysteme_digital_koa_magie_operable',
            'name' => 'Parchemin — Architecture d’écosystème digital / KOA',
            'description' => 'Reconnaît une progression validée dans la Voie Architecture d’écosystème digital / KOA au Niveau de Puissance opératoire.',
            'type' => 'site',
            'criteria' => [
                'evidence_submission',
                'human_validation',
                'competency_threshold',
                'archive_or_portfolio',
                'no_unresolved_integrity_block',
            ],
            'competencies' => [
                'UCKK-KOA-MAGIE-OPERABLE',
            ],
            'requiredarchive' => true,
            'requireshumanvalidation' => true,
            'metadata' => [
                self::METADATA_MANAGED_BY => self::MANAGED_BY,
                'source_family' => 'UCKK canon',
                'program' => 'KOA',
                'palier' => 'magie_operable',
            ],
        ],
        [
            'key' => 'parchemin_architecture_sociotechnique_magie_operable',
            'name' => 'Parchemin — Architecture sociotechnique',
            'description' => 'Reconnaît une progression validée dans la Voie Architecture sociotechnique au Niveau de Puissance opératoire.',
            'type' => 'site',
            'criteria' => [
                'evidence_submission',
                'human_validation',
                'competency_threshold',
                'archive_or_portfolio',
                'no_unresolved_integrity_block',
            ],
            'competencies' => [
                'UCKK-AS-MAGIE-OPERABLE',
            ],
            'requiredarchive' => true,
            'requireshumanvalidation' => true,
            'metadata' => [
                self::METADATA_MANAGED_BY => self::MANAGED_BY,
                'source_family' => 'UCKK canon',
                'program' => 'AS',
                'palier' => 'magie_operable',
            ],
        ],
        [
            'key' => 'parchemin_sciences_politiques_magie_operable',
            'name' => 'Parchemin — Sciences politiques',
            'description' => 'Reconnaît une progression validée dans la Voie des Sciences politiques au Niveau de Puissance opératoire.',
            'type' => 'site',
            'criteria' => [
                'evidence_submission',
                'human_validation',
                'competency_threshold',
                'archive_or_portfolio',
                'no_unresolved_integrity_block',
            ],
            'competencies' => [
                'UCKK-SP-MAGIE-OPERABLE',
            ],
            'requiredarchive' => true,
            'requireshumanvalidation' => true,
            'metadata' => [
                self::METADATA_MANAGED_BY => self::MANAGED_BY,
                'source_family' => 'UCKK canon',
                'program' => 'SP',
                'palier' => 'magie_operable',
            ],
        ],
        [
            'key' => 'parchemin_metaphysique_magie_operable',
            'name' => 'Parchemin — Métaphysique',
            'description' => 'Reconnaît une progression validée dans la Voie de Métaphysique au Niveau de Puissance opératoire.',
            'type' => 'site',
            'criteria' => [
                'evidence_submission',
                'human_validation',
                'competency_threshold',
                'archive_or_portfolio',
                'no_unresolved_integrity_block',
            ],
            'competencies' => [
                'UCKK-ME-MAGIE-OPERABLE',
            ],
            'requiredarchive' => true,
            'requireshumanvalidation' => true,
            'metadata' => [
                self::METADATA_MANAGED_BY => self::MANAGED_BY,
                'source_family' => 'UCKK canon',
                'program' => 'ME',
                'palier' => 'magie_operable',
            ],
        ],
        [
            'key' => 'parchemin_linguistique_architecture_sens_magie_operable',
            'name' => 'Parchemin — Linguistique et architecture du sens',
            'description' => 'Reconnaît une progression validée dans la Voie de Linguistique et architecture du sens au Niveau de Puissance opératoire.',
            'type' => 'site',
            'criteria' => [
                'evidence_submission',
                'human_validation',
                'competency_threshold',
                'archive_or_portfolio',
                'no_unresolved_integrity_block',
            ],
            'competencies' => [
                'UCKK-LI-MAGIE-OPERABLE',
            ],
            'requiredarchive' => true,
            'requireshumanvalidation' => true,
            'metadata' => [
                self::METADATA_MANAGED_BY => self::MANAGED_BY,
                'source_family' => 'UCKK canon',
                'program' => 'LI',
                'palier' => 'magie_operable',
            ],
        ],
        [
            'key' => 'gardien_d_archive',
            'name' => 'Gardien d’archive',
            'description' => 'Reconnaît une pratique fiable de conservation et de contextualisation des traces.',
            'type' => 'site',
            'criteria' => [
                'archive_or_portfolio',
                'human_validation',
                'no_unresolved_integrity_block',
            ],
            'competencies' => [
                'UCKK-ARCHIVE-GOVERNANCE',
            ],
            'requiredarchive' => true,
            'requireshumanvalidation' => true,
            'metadata' => [
                self::METADATA_MANAGED_BY => self::MANAGED_BY,
                'source_family' => 'UCKK canon',
                'symbolic_role' => 'Gardien d’archive',
                'not_moodle_role' => true,
            ],
        ],
        [
            'key' => 'lecteur_des_traces',
            'name' => 'Lecteur des traces',
            'description' => 'Reconnaît la capacité à lire, relier et interpréter des traces documentées.',
            'type' => 'site',
            'criteria' => [
                'evidence_submission',
                'archive_or_portfolio',
                'human_validation',
            ],
            'competencies' => [
                'UCKK-TRACE-READING',
            ],
            'requiredarchive' => true,
            'requireshumanvalidation' => true,
            'metadata' => [
                self::METADATA_MANAGED_BY => self::MANAGED_BY,
                'source_family' => 'UCKK canon',
                'symbolic_role' => 'Lecteur des traces',
                'not_moodle_role' => true,
            ],
        ],
        [
            'key' => 'inquisiteur_certifie',
            'name' => 'Inquisiteur certifié',
            'description' => 'Reconnaît une capacité validée à conduire une lecture d’intégrité sous supervision humaine.',
            'type' => 'site',
            'criteria' => [
                'human_validation',
                'integrity_case_reviewed',
                'no_unresolved_integrity_block',
            ],
            'competencies' => [
                'UCKK-INTEGRITY-REVIEW',
            ],
            'requiredarchive' => false,
            'requireshumanvalidation' => true,
            'metadata' => [
                self::METADATA_MANAGED_BY => self::MANAGED_BY,
                'source_family' => 'UCKK canon',
                'symbolic_role' => 'Inquisiteur',
                'not_moodle_role' => true,
            ],
        ],
        [
            'key' => 'officiant_de_la_preuve',
            'name' => 'Officiant de la preuve',
            'description' => 'Reconnaît la capacité à accompagner la constitution et la validation de preuves.',
            'type' => 'site',
            'criteria' => [
                'evidence_submission',
                'human_validation',
                'archive_or_portfolio',
            ],
            'competencies' => [
                'UCKK-EVIDENCE-OFFICIANT',
            ],
            'requiredarchive' => true,
            'requireshumanvalidation' => true,
            'metadata' => [
                self::METADATA_MANAGED_BY => self::MANAGED_BY,
                'source_family' => 'UCKK canon',
                'symbolic_role' => 'Officiant de la preuve',
                'not_moodle_role' => true,
            ],
        ],
        [
            'key' => 'assembleur_de_kristaux',
            'name' => 'Assembleur de kristaux',
            'description' => 'Reconnaît une pratique validée de composition et d’assemblage de kristaux.',
            'type' => 'site',
            'criteria' => [
                'archive_or_portfolio',
                'human_validation',
                'competency_threshold',
            ],
            'competencies' => [
                'UCKK-KRISTAL-ASSEMBLY',
            ],
            'requiredarchive' => true,
            'requireshumanvalidation' => true,
            'metadata' => [
                self::METADATA_MANAGED_BY => self::MANAGED_BY,
                'source_family' => 'UCKK canon',
                'symbolic_role' => 'Assembleur de kristaux',
                'not_moodle_role' => true,
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
     * This deletes only badges that carry the seed-managed config marker used by
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
            $badge = $this->get_existing_badge(['key' => $key]);

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
        $items = [];

        if (empty($options['defaults'])) {
            foreach (self::CANONICAL_BADGES as $key) {
                $badge = $this->get_existing_badge(['key' => $key]);

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
            'version' => self::VERSION,
            'items' => $normalised,
            'metadata' => [
                'source' => self::COMPONENT,
                'exportedat' => date(DATE_ATOM),
                'managedby' => self::MANAGED_BY,
                'guardrails' => [
                    'badges_are_not_roles',
                    'badges_do_not_award_themselves',
                    'human_validation_required',
                    'archive_or_evidence_required',
                ],
            ],
        ];
    }

    /**
     * Validate required award rules.
     *
     * @param validation_result $result Result object.
     * @param array<string, mixed> $item Normalised badge item.
     */
    private function validate_required_award_rules(validation_result $result, array $item): void {
        $targetkey = $item['key'];

        if (empty($item['criteria'])) {
            $this->add_message(
                $result,
                self::SEVERITY_WARNING,
                self::PRESET,
                $targetkey,
                get_string('badgeseednocriteria', 'tool_uckkseed')
            );
        }

        if ($item['requireshumanvalidation'] === false) {
            $this->add_message(
                $result,
                self::SEVERITY_ERROR,
                self::PRESET,
                $targetkey,
                get_string('badgeseedrequiresvalidation', 'tool_uckkseed')
            );
        }

        if ($item['requiredarchive'] === false && !in_array('evidence_submission', $item['criteria'], true)) {
            $this->add_message(
                $result,
                self::SEVERITY_WARNING,
                self::PRESET,
                $targetkey,
                get_string('badgeseedrequiresarchiveorevidence', 'tool_uckkseed')
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

        $this->add_optional_field($badge, 'uniquehash', $this->get_uniquehash($item['key']));
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
        $key = $this->get_key_from_badge_record($badge);

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
            $this->unset_config_marker($key);
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

        $key = clean_param((string)($item['key'] ?? ''), PARAM_ALPHANUMEXT);

        if ($key === '') {
            return null;
        }

        $badgeid = (int)get_config(self::COMPONENT, 'badge_' . $key . '_id');

        if ($badgeid > 0) {
            $badge = $DB->get_record('badge', ['id' => $badgeid], '*', IGNORE_MISSING);

            if ($badge) {
                return $badge;
            }

            $this->unset_config_marker($key);
        }

        if ($this->badge_table_has_field('uniquehash')) {
            $badge = $DB->get_record(
                'badge',
                ['uniquehash' => $this->get_uniquehash($key)],
                '*',
                IGNORE_MISSING
            );

            if ($badge) {
                return $badge;
            }
        }

        return null;
    }

    /**
     * Convert a Moodle badge record to a preset item.
     *
     * @param stdClass $badge Badge record.
     * @return array<string, mixed>
     */
    private function badge_record_to_item(stdClass $badge): array {
        $key = $this->get_key_from_badge_record($badge);
        $stored = $key === '' ? '' : (string)get_config(self::COMPONENT, 'badge_' . $key . '_definition');
        $decoded = json_decode($stored, true);

        if (is_array($decoded)) {
            return $decoded;
        }

        return [
            'key' => $key !== '' ? $key : clean_param((string)$badge->name, PARAM_ALPHANUMEXT),
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

        $metadata = $this->normalise_metadata($item['metadata'] ?? []);
        $recognition = [];
        if (isset($item['recognition'])) {
            $recognition = $this->normalise_metadata($item['recognition']);
        }

        $key = (string)($item['key'] ?? '');
        if ($key === '') {
            $key = (string)($item['idnumber'] ?? '');
        }
        if ($key === '') {
            $key = (string)($item['id'] ?? '');
        }

        $name = (string)($item['name'] ?? '');
        if ($name === '') {
            $name = (string)($item['title'] ?? '');
        }
        if ($name === '') {
            $name = (string)($item['short_title'] ?? '');
        }

        $description = (string)($item['description'] ?? '');
        if ($description === '') {
            $description = (string)($item['summary'] ?? '');
        }
        if ($description === '' && !empty($recognition['description'])) {
            $description = (string)$recognition['description'];
        }
        if ($description === '') {
            $description = $this->fallback_name($key);
        }

        $badge_type = (string)($item['type'] ?? '');
        if ($badge_type === '') {
            $badge_type = (string)($item['badge_type'] ?? '');
        }
        if ($badge_type === '') {
            $badge_type = (string)($recognition['moodle_badge_type'] ?? '');
        }

        $criteria = $this->normalise_criteria($item['criteria'] ?? $item['award_criteria'] ?? []);
        $criteria_payload = $this->normalise_criteria_payload($item['criteria'] ?? $item['award_criteria'] ?? []);

        $competencies = $this->normalise_competencies(
            $item['competencies']
                ?? $item['linked_competency_ids']
                ?? $metadata['linked_competency_ids']
                ?? []
        );

        if (empty($competencies)) {
            $competencies = $this->extract_competencies_from_criteria_payload($criteria_payload);
        }

        $courseidnumber = (string)($item['courseidnumber'] ?? $item['course_idnumber'] ?? $item['course'] ?? '');

        $requiredarchive = $this->normalise_bool(
            $item['requiredarchive']
                ?? $item['requires_archive']
                ?? $item['requiresarchive']
                ?? $metadata['requires_archive']
                ?? true
        );

        $requireshumanvalidation = $this->normalise_bool(
            $item['requireshumanvalidation']
                ?? $item['requires_human_validation']
                ?? $metadata['requires_human_validation']
                ?? true
        );

        $language = (string)($item['language'] ?? $metadata['language'] ?? current_language() ?: 'en');

        $metadata[self::METADATA_MANAGED_BY] = $metadata[self::METADATA_MANAGED_BY] ?? self::MANAGED_BY;
        $metadata['source_preset'] = self::PRESET;
        if (!empty($recognition)) {
            $metadata['recognition'] = $recognition;
        }

        if (!empty($item['id'])) {
            $metadata['source_id'] = (string)$item['id'];
        }
        if (!empty($item['idnumber'])) {
            $metadata['source_idnumber'] = (string)$item['idnumber'];
        }
        if (!empty($item['code'])) {
            $metadata['code'] = (string)$item['code'];
        }

        return [
            'key' => clean_param($this->normalise_key($key), PARAM_ALPHANUMEXT),
            'name' => clean_param($name, PARAM_TEXT),
            'description' => clean_param($description, PARAM_TEXT),
            'type' => $this->normalise_badge_type($badge_type),
            'courseidnumber' => clean_param($courseidnumber, PARAM_TEXT),
            'criteria' => $criteria,
            'competencies' => $competencies,
            'requiredarchive' => $requiredarchive,
            'requireshumanvalidation' => $requireshumanvalidation,
            'language' => clean_param($language, PARAM_ALPHANUMEXT),
            'metadata' => $metadata,
        ];
    }

    /**
     * Normalise badge key.
     *
     * @param string $value Raw value.
     * @return string
     */
    private function normalise_key(string $value): string {
        $value = strtolower(trim($value));
        $value = str_replace([':', '-', ' ', '/', '\\'], '_', $value);
        $value = preg_replace('/[^a-z0-9_]+/', '_', $value) ?? $value;
        $value = trim($value, '_');

        return $value;
    }

    /**
     * Normalise badge type.
     *
     * @param string $type Raw type.
     * @return string
     */
    private function normalise_badge_type(string $type): string {
        $type = strtolower(trim($type));

        if (in_array($type, ['course', 'course_badge'], true)) {
            return 'course';
        }

        return 'site';
    }

    /**
     * Normalise criteria to a list of criterion keys.
     *
     * @param mixed $criteria Raw criteria.
     * @return string[]
     */
    private function normalise_criteria(mixed $criteria): array {
        $items = $this->normalise_list($criteria);
        $normalised = [];

        foreach ($items as $item) {
            if ($item instanceof stdClass) {
                $item = (array)$item;
            }

            if (is_array($item)) {
                $candidate = (string)($item['type'] ?? $item['key'] ?? $item['id'] ?? $item['criterion'] ?? '');
            } else {
                $candidate = (string)$item;
            }

            $candidate = $this->normalise_criterion_type($candidate);

            if ($candidate !== '') {
                $normalised[] = $candidate;
            }
        }

        return array_values(array_unique($normalised));
    }

    /**
     * Normalise criteria payload.
     *
     * @param mixed $criteria Raw criteria.
     * @return array<int, mixed>
     */
    private function normalise_criteria_payload(mixed $criteria): array {
        $items = $this->normalise_list($criteria);
        $normalised = [];

        foreach ($items as $item) {
            if ($item instanceof stdClass) {
                $item = (array)$item;
            }

            if (is_array($item)) {
                $normalised[] = $item;
            }
        }

        return $normalised;
    }

    /**
     * Normalise one criterion type.
     *
     * @param string $type Criterion type.
     * @return string
     */
    private function normalise_criterion_type(string $type): string {
        $type = strtolower(trim($type));
        $type = str_replace(['-', ' ', ':', '/'], '_', $type);
        $type = preg_replace('/[^a-z0-9_]+/', '_', $type) ?? $type;
        $type = trim($type, '_');

        return $type;
    }

    /**
     * Normalise competency references.
     *
     * @param mixed $competencies Raw competencies.
     * @return string[]
     */
    private function normalise_competencies(mixed $competencies): array {
        $items = $this->normalise_list($competencies);
        $normalised = [];

        foreach ($items as $item) {
            if ($item instanceof stdClass) {
                $item = (array)$item;
            }

            if (is_array($item)) {
                $candidate = (string)($item['idnumber'] ?? $item['id'] ?? $item['shortname'] ?? $item['key'] ?? '');
            } else {
                $candidate = (string)$item;
            }

            $candidate = clean_param(trim($candidate), PARAM_TEXT);

            if ($candidate !== '') {
                $normalised[] = $candidate;
            }
        }

        return array_values(array_unique($normalised));
    }

    /**
     * Extract competencies from structured criteria payload.
     *
     * @param array<int, mixed> $criteriaitems Criteria payload.
     * @return string[]
     */
    private function extract_competencies_from_criteria_payload(array $criteriaitems): array {
        $competencies = [];

        foreach ($criteriaitems as $criterion) {
            if (!is_array($criterion)) {
                continue;
            }

            $candidates = $criterion['competencies']
                ?? $criterion['linked_competency_ids']
                ?? $criterion['competency_ids']
                ?? $criterion['competency']
                ?? [];

            foreach ($this->normalise_competencies($candidates) as $competency) {
                $competencies[] = $competency;
            }
        }

        return array_values(array_unique($competencies));
    }

    /**
     * Return whether a competency reference is known.
     *
     * Validation intentionally accepts future references because competencies may
     * be seeded in the same run or on another environment.
     *
     * @param string $idnumber Competency idnumber/reference.
     * @return bool
     */
    private function is_known_competency_reference(string $idnumber): bool {
        global $DB;

        if ($idnumber === '') {
            return true;
        }

        $tables = [
            'competency',
            'tool_lp_competency',
        ];

        foreach ($tables as $table) {
            if ($DB->get_manager()->table_exists($table)
                && $DB->record_exists($table, ['idnumber' => $idnumber])) {
                return true;
            }
        }

        return true;
    }

    /**
     * Build criteria description HTML.
     *
     * @param array<string, mixed> $item Normalised item.
     * @return string
     */
    private function build_criteria_description(array $item): string {
        $lines = [
            html_writer::tag('p', get_string('badgeseedcriteriadescription', 'tool_uckkseed')),
        ];

        $items = [];

        foreach ($item['criteria'] as $criterion) {
            $items[] = html_writer::tag('li', s($criterion));
        }

        foreach ($item['competencies'] as $competency) {
            $items[] = html_writer::tag('li', get_string('badgeseedcriterioncompetency', 'tool_uckkseed', $competency));
        }

        if ($item['requiredarchive']) {
            $items[] = html_writer::tag('li', get_string('badgeseedcriterionarchive', 'tool_uckkseed'));
        }

        if ($item['requireshumanvalidation']) {
            $items[] = html_writer::tag('li', get_string('badgeseedcriterionhuman', 'tool_uckkseed'));
        }

        if (empty($items)) {
            $items[] = html_writer::tag('li', get_string('badgeseedcriterionmanual', 'tool_uckkseed'));
        }

        $lines[] = html_writer::start_tag('ul');
        $lines[] = implode("\n", $items);
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
     * Remove the config marker for a seed-managed badge key.
     *
     * @param string $key Badge key.
     */
    private function unset_config_marker(string $key): void {
        unset_config('badge_' . $key . '_id', self::COMPONENT);
        unset_config('badge_' . $key . '_definition', self::COMPONENT);
    }

    /**
     * Resolve the seed key that owns a Moodle badge record.
     *
     * @param stdClass $badge Badge record.
     * @return string Badge key, or empty string when not seed-managed.
     */
    private function get_key_from_badge_record(stdClass $badge): string {
        $badgeid = (int)($badge->id ?? 0);

        if ($badgeid > 0) {
            foreach (self::CANONICAL_BADGES as $key) {
                if ((int)get_config(self::COMPONENT, 'badge_' . $key . '_id') === $badgeid) {
                    return $key;
                }
            }
        }

        if ($this->badge_table_has_field('uniquehash') && property_exists($badge, 'uniquehash')) {
            $key = $this->get_key_from_uniquehash((string)$badge->uniquehash);

            if ($key !== '') {
                return $key;
            }
        }

        if ($this->badge_table_has_field('metadata') && property_exists($badge, 'metadata')) {
            $metadata = json_decode((string)$badge->metadata, true);

            if (is_array($metadata)) {
                $key = clean_param((string)($metadata['key'] ?? ''), PARAM_ALPHANUMEXT);

                if ($key !== '') {
                    return $key;
                }
            }
        }

        return '';
    }

    /**
     * Add optional object property only when Moodle badge table has the field.
     *
     * @param stdClass $record Record.
     * @param string $field Field name.
     * @param mixed $value Field value.
     */
    private function add_optional_field(stdClass $record, string $field, mixed $value): void {
        if ($this->badge_table_has_field($field)) {
            $record->{$field} = $value;
        }
    }

    /**
     * Return whether the Moodle badge table has a field.
     *
     * Moodle badge schema differs across supported versions. The seed tool must
     * never require non-core/generated columns such as uniquehash or metadata.
     *
     * @param string $field Field name.
     * @return bool
     */
    private function badge_table_has_field(string $field): bool {
        global $DB;

        static $columns = null;

        if ($columns === null) {
            $columns = $DB->get_columns('badge');
        }

        return array_key_exists($field, $columns);
    }

    /**
     * Encode badge item metadata.
     *
     * @param array<string, mixed> $item Badge item.
     * @return string
     */
    private function encode_metadata(array $item): string {
        $metadata = $item['metadata'];
        $metadata['key'] = $item['key'];
        $metadata[self::METADATA_MANAGED_BY] = self::MANAGED_BY;

        return json_encode($metadata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
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
            self::MODE_DRY_RUN,
            self::MODE_APPLY,
            self::MODE_REPORT,
            self::MODE_ROLLBACK_PLAN,
        ];

        return in_array($mode, $allowed, true) ? $mode : self::MODE_DRY_RUN;
    }

    /**
     * Normalise a list-like value.
     *
     * @param mixed $value Value.
     * @return array<int, mixed>
     */
    private function normalise_list(mixed $value): array {
        if ($value instanceof stdClass) {
            $value = (array)$value;
        }

        if ($value === null || $value === '') {
            return [];
        }

        if (is_array($value)) {
            $islist = array_keys($value) === range(0, count($value) - 1);

            if ($islist) {
                return array_values($value);
            }

            return [$value];
        }

        return [$value];
    }

    /**
     * Normalise metadata.
     *
     * @param mixed $metadata Metadata.
     * @return array<string, mixed>
     */
    private function normalise_metadata(mixed $metadata): array {
        if ($metadata instanceof stdClass) {
            $metadata = (array)$metadata;
        }

        if (!is_array($metadata)) {
            return [];
        }

        $normalised = [];

        foreach ($metadata as $key => $value) {
            if (!is_string($key)) {
                continue;
            }

            if ($value instanceof stdClass) {
                $value = (array)$value;
            }

            $normalised[clean_param($key, PARAM_ALPHANUMEXT)] = $value;
        }

        return $normalised;
    }

    /**
     * Normalise boolean.
     *
     * @param mixed $value Value.
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
            return in_array(strtolower(trim($value)), ['1', 'true', 'yes', 'enabled', 'on', 'required'], true);
        }

        return false;
    }

    /**
     * Build fallback display name.
     *
     * @param string $key Badge key.
     * @return string
     */
    private function fallback_name(string $key): string {
        $key = str_replace('_', ' ', $key);

        return $key === '' ? get_string('pluginname', 'tool_uckkseed') : ucfirst($key);
    }

    /**
     * Create validation result.
     *
     * @param string $summary Summary.
     * @return validation_result
     */
    private function new_result(string $summary): validation_result {
        return validation_result::success($summary, [
            'component' => self::COMPONENT,
            'preset' => self::PRESET,
        ]);
    }

    /**
     * Add result message.
     *
     * @param validation_result $result Result.
     * @param string $severity Severity.
     * @param string $preset Preset id.
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
            self::PRESET,
            $targetkey,
            $metadata
        );
    }

    /**
     * Increment validation result.
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