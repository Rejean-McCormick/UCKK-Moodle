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
 * Symbolic role value object and registry for local_uckk.
 *
 * A symbolic role is a UCKK identity, title or pedagogical distinction.
 * It is not automatically a Moodle role and it must not grant Moodle
 * capabilities by itself.
 *
 * Moodle roles remain permission-bearing technical objects assigned in Moodle
 * contexts. UCKK symbolic roles remain narrative, pedagogical, portfolio,
 * badge, pathway or archive distinctions.
 *
 * @package    local_uckk
 * @copyright  2026 Univers-Cité King Klown
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_uckk\local;

defined('MOODLE_INTERNAL') || die();

/**
 * UCKK symbolic role value object.
 *
 * This class centralizes the canonical symbolic roles used by UCKK-Moodle.
 * It helps dashboards, profiles, badges, seed data, reports and archive
 * summaries use the same vocabulary without confusing symbolic status with
 * Moodle permission-bearing roles.
 *
 * @package local_uckk
 */
final class symbolic_role {
    /** Component name. */
    public const COMPONENT = 'local_uckk';

    /** Symbolic role family: learning. */
    public const FAMILY_LEARNING = 'learning';

    /** Symbolic role family: building. */
    public const FAMILY_BUILDING = 'building';

    /** Symbolic role family: governance. */
    public const FAMILY_GOVERNANCE = 'governance';

    /** Symbolic role family: archive. */
    public const FAMILY_ARCHIVE = 'archive';

    /** Symbolic role family: method. */
    public const FAMILY_METHOD = 'method';

    /** Symbolic role family: narrative. */
    public const FAMILY_NARRATIVE = 'narrative';

    /** Symbolic role family: systems. */
    public const FAMILY_SYSTEMS = 'systems';

    /** Symbolic role key: Joueur. */
    public const ROLE_JOUEUR = 'joueur';

    /** Symbolic role key: Joueur lucide. */
    public const ROLE_JOUEUR_LUCIDE = 'joueur_lucide';

    /** Symbolic role key: Bâtisseur. */
    public const ROLE_BATISSEUR = 'batisseur';

    /** Symbolic role key: Archiviste. */
    public const ROLE_ARCHIVISTE = 'archiviste';

    /** Symbolic role key: Inquisiteur. */
    public const ROLE_INQUISITEUR = 'inquisiteur';

    /** Symbolic role key: Cartographe. */
    public const ROLE_CARTOGRAPHE = 'cartographe';

    /** Symbolic role key: Architecte du sens. */
    public const ROLE_ARCHITECTE_SENS = 'architecte_sens';

    /** Symbolic role key: Architecte d’opportunités. */
    public const ROLE_ARCHITECTE_OPPORTUNITES = 'architecte_opportunites';

    /** Symbolic role key: Gardien des systèmes vivants. */
    public const ROLE_GARDIEN_SYSTEMES_VIVANTS = 'gardien_systemes_vivants';

    /** Symbolic role key: Gardien de la preuve. */
    public const ROLE_GARDIEN_PREUVE = 'gardien_preuve';

    /** Symbolic role key: Stratège civique. */
    public const ROLE_STRATEGE_CIVIQUE = 'stratege_civique';

    /** Symbolic role key: Maître d’œuvre augmenté. */
    public const ROLE_CARTOGRAPHE_AUGMENTE = 'cartographe_augmente';

    /** Symbolic role key: Designer d’assemblées. */
    public const ROLE_DESIGNER_ASSEMBLEES = 'designer_assemblees';

    /** Symbolic role key: Réformateur du Grand Jeu. */
    public const ROLE_REFORMATEUR_GRAND_JEU = 'reformateur_grand_jeu';

    /** @var string Stable symbolic role key. */
    private string $key;

    /** @var string Human-readable name. */
    private string $name;

    /** @var string Short description. */
    private string $description;

    /** @var string Role family. */
    private string $family;

    /** @var string|null Related canonical badge key. */
    private ?string $badgekey;

    /** @var string|null Related Moodle technical role shortname, if any. */
    private ?string $technicalrole;

    /** @var string[] Capability hints. These do not grant permissions. */
    private array $capabilityhints;

    /** @var string[] Related UCKK competencies. */
    private array $competencies;

    /** @var string[] Related UCKK programs. */
    private array $programs;

    /** @var string[] Tags. */
    private array $tags;

    /** @var bool Whether this symbolic role may be visible on public profile surfaces. */
    private bool $public;

    /** @var bool Whether this symbolic role is governance-sensitive. */
    private bool $sensitive;

    /** @var bool Whether this symbolic role implies responsibility language in the UI. */
    private bool $responsibilitybearing;

    /**
     * Constructor.
     *
     * @param string $key Stable key.
     * @param string $name Display name.
     * @param string $description Short description.
     * @param string $family Role family.
     * @param string|null $badgekey Related badge key.
     * @param string|null $technicalrole Related Moodle technical role shortname.
     * @param string[] $capabilityhints Capability hints only.
     * @param string[] $competencies Related competencies.
     * @param string[] $programs Related programs.
     * @param string[] $tags Tags.
     * @param bool $public Whether public display is allowed.
     * @param bool $sensitive Whether role is sensitive.
     * @param bool $responsibilitybearing Whether role carries responsibility language.
     */
    private function __construct(
        string $key,
        string $name,
        string $description,
        string $family,
        ?string $badgekey = null,
        ?string $technicalrole = null,
        array $capabilityhints = [],
        array $competencies = [],
        array $programs = [],
        array $tags = [],
        bool $public = true,
        bool $sensitive = false,
        bool $responsibilitybearing = false
    ) {
        $this->key = self::normalise_key($key);
        $this->name = $name;
        $this->description = $description;
        $this->family = self::normalise_key($family);
        $this->badgekey = $badgekey !== null ? self::normalise_key($badgekey) : null;
        $this->technicalrole = $technicalrole !== null && trim($technicalrole) !== '' ? clean_param($technicalrole, PARAM_ALPHANUMEXT) : null;
        $this->capabilityhints = self::normalise_list($capabilityhints);
        $this->competencies = self::normalise_list($competencies, false);
        $this->programs = self::normalise_list($programs);
        $this->tags = self::normalise_list($tags);
        $this->public = $public;
        $this->sensitive = $sensitive;
        $this->responsibilitybearing = $responsibilitybearing;
    }

    /**
     * Return canonical role definitions.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function get_registry(): array {
        return [
            self::ROLE_JOUEUR => [
                'key' => self::ROLE_JOUEUR,
                'name' => 'Joueur',
                'description' => 'Personne qui entre dans le campus UCKK, apprend les règles du jeu, produit des preuves et construit son parcours.',
                'family' => self::FAMILY_LEARNING,
                'badgekey' => 'joueur_initie',
                'technicalrole' => 'uckkplayer',
                'capabilityhints' => [
                    'local/uckk:viewcampus',
                    'mod/uckkchallenge:submitproof',
                    'mod/uckkassembly:proposemotion',
                ],
                'competencies' => [
                    'UCKK-COMP-014',
                ],
                'programs' => [
                    'tronc_commun',
                ],
                'tags' => [
                    'apprentissage',
                    'campus',
                    'participation',
                ],
                'public' => true,
                'sensitive' => false,
                'responsibilitybearing' => false,
            ],
            self::ROLE_JOUEUR_LUCIDE => [
                'key' => self::ROLE_JOUEUR_LUCIDE,
                'name' => 'Joueur lucide',
                'description' => 'Personne capable de lire le Grand Jeu social, distinguer faits, hypothèses, récits et décisions, et agir avec responsabilité.',
                'family' => self::FAMILY_LEARNING,
                'badgekey' => 'joueur_lucide',
                'technicalrole' => null,
                'capabilityhints' => [],
                'competencies' => [
                    'UCKK-COMP-001',
                    'UCKK-COMP-003',
                    'UCKK-COMP-010',
                    'UCKK-COMP-013',
                ],
                'programs' => [
                    'grand_jeu_social',
                ],
                'tags' => [
                    'lucidite',
                    'grand_jeu',
                    'ethique',
                ],
                'public' => true,
                'sensitive' => false,
                'responsibilitybearing' => true,
            ],
            self::ROLE_BATISSEUR => [
                'key' => self::ROLE_BATISSEUR,
                'name' => 'Bâtisseur',
                'description' => 'Personne qui transforme une idée, une preuve ou une décision en prototype, outil, structure ou artefact utile.',
                'family' => self::FAMILY_BUILDING,
                'badgekey' => 'batisseur_prototype',
                'technicalrole' => null,
                'capabilityhints' => [],
                'competencies' => [
                    'UCKK-COMP-012',
                    'UCKK-COMP-005',
                    'UCKK-COMP-009',
                ],
                'programs' => [
                    'architecture_sociotechnique',
                    'architecture_ecosysteme_digital_koa',
                ],
                'tags' => [
                    'prototype',
                    'construction',
                    'artefact',
                ],
                'public' => true,
                'sensitive' => false,
                'responsibilitybearing' => true,
            ],
            self::ROLE_ARCHIVISTE => [
                'key' => self::ROLE_ARCHIVISTE,
                'name' => 'Archiviste',
                'description' => 'Gardien de la mémoire UCKK : preuves, décisions, versions, corrections, apprentissages, Kristals et archives d’assemblées.',
                'family' => self::FAMILY_ARCHIVE,
                'badgekey' => 'archiviste_decision',
                'technicalrole' => 'uckkarchivist',
                'capabilityhints' => [
                    'mod/uckkarchive:validateitem',
                    'mod/uckkarchive:reviseitem',
                    'mod/uckkarchive:export',
                ],
                'competencies' => [
                    'UCKK-COMP-008',
                    'UCKK-COMP-009',
                    'UCKK-COMP-013',
                ],
                'programs' => [],
                'tags' => [
                    'archive',
                    'memoire',
                    'preuve',
                    'version',
                ],
                'public' => true,
                'sensitive' => true,
                'responsibilitybearing' => true,
            ],
            self::ROLE_INQUISITEUR => [
                'key' => self::ROLE_INQUISITEUR,
                'name' => 'Inquisiteur',
                'description' => 'Garde-fou éthique et méthodologique qui vérifie les preuves, demande correction, protège la dignité et rend les décisions contestables.',
                'family' => self::FAMILY_METHOD,
                'badgekey' => 'inquisition_methodologique',
                'technicalrole' => 'uckkinquisitor',
                'capabilityhints' => [
                    'tool/uckkintegrity:opencase',
                    'tool/uckkintegrity:reviewcase',
                    'tool/uckkintegrity:issuecorrection',
                    'tool/uckkintegrity:invalidate',
                    'tool/uckkintegrity:viewrestricted',
                ],
                'competencies' => [
                    'UCKK-COMP-010',
                    'UCKK-COMP-011',
                    'UCKK-COMP-013',
                ],
                'programs' => [],
                'tags' => [
                    'integrite',
                    'methode',
                    'contestation',
                    'correction',
                ],
                'public' => true,
                'sensitive' => true,
                'responsibilitybearing' => true,
            ],
            self::ROLE_CARTOGRAPHE => [
                'key' => self::ROLE_CARTOGRAPHE,
                'name' => 'Cartographe',
                'description' => 'Personne qui rend visibles les systèmes, règles, flux, acteurs, dépendances et tensions cachées.',
                'family' => self::FAMILY_SYSTEMS,
                'badgekey' => 'cartographe_systemes',
                'technicalrole' => null,
                'capabilityhints' => [],
                'competencies' => [
                    'UCKK-COMP-002',
                    'UCKK-COMP-003',
                    'UCKK-COMP-005',
                ],
                'programs' => [
                    'grand_jeu_social',
                    'architecture_sociotechnique',
                ],
                'tags' => [
                    'cartographie',
                    'systemes',
                    'flux',
                ],
                'public' => true,
                'sensitive' => false,
                'responsibilitybearing' => true,
            ],
            self::ROLE_ARCHITECTE_SENS => [
                'key' => self::ROLE_ARCHITECTE_SENS,
                'name' => 'Architecte du sens',
                'description' => 'Personne qui gouverne les mots, concepts, récits, distinctions et traductions sans confondre fiction, fait, preuve et décision.',
                'family' => self::FAMILY_NARRATIVE,
                'badgekey' => 'architecte_sens',
                'technicalrole' => null,
                'capabilityhints' => [],
                'competencies' => [
                    'UCKK-COMP-003',
                    'UCKK-COMP-008',
                    'UCKK-COMP-013',
                ],
                'programs' => [
                    'linguistique_architecture_du_sens',
                ],
                'tags' => [
                    'sens',
                    'langage',
                    'recit',
                    'semantique',
                ],
                'public' => true,
                'sensitive' => false,
                'responsibilitybearing' => true,
            ],
            self::ROLE_ARCHITECTE_OPPORTUNITES => [
                'key' => self::ROLE_ARCHITECTE_OPPORTUNITES,
                'name' => 'Architecte d’opportunités',
                'description' => 'Personne qui transforme contraintes, blocages, ressources et flux en possibilités d’action vérifiables et responsables.',
                'family' => self::FAMILY_SYSTEMS,
                'badgekey' => 'architecte_opportunites',
                'technicalrole' => null,
                'capabilityhints' => [],
                'competencies' => [
                    'UCKK-COMP-001',
                    'UCKK-COMP-007',
                    'UCKK-COMP-012',
                ],
                'programs' => [
                    'economie',
                    'grand_jeu_social',
                ],
                'tags' => [
                    'opportunite',
                    'ressources',
                    'strategie',
                ],
                'public' => true,
                'sensitive' => false,
                'responsibilitybearing' => true,
            ],
            self::ROLE_GARDIEN_SYSTEMES_VIVANTS => [
                'key' => self::ROLE_GARDIEN_SYSTEMES_VIVANTS,
                'name' => 'Gardien des systèmes vivants',
                'description' => 'Personne qui protège les milieux, dépendances, vulnérabilités et systèmes vivants contre les abstractions destructrices.',
                'family' => self::FAMILY_SYSTEMS,
                'badgekey' => 'gardien_systemes_vivants',
                'technicalrole' => null,
                'capabilityhints' => [],
                'competencies' => [
                    'UCKK-COMP-001',
                    'UCKK-COMP-002',
                    'UCKK-COMP-010',
                ],
                'programs' => [
                    'ecologie',
                    'intervention_sociale',
                ],
                'tags' => [
                    'vivant',
                    'ecologie',
                    'responsabilite',
                    'dignite',
                ],
                'public' => true,
                'sensitive' => false,
                'responsibilitybearing' => true,
            ],
            self::ROLE_GARDIEN_PREUVE => [
                'key' => self::ROLE_GARDIEN_PREUVE,
                'name' => 'Gardien de la preuve',
                'description' => 'Personne qui protège la qualité, la traçabilité, la vérification et la contestabilité des preuves.',
                'family' => self::FAMILY_METHOD,
                'badgekey' => 'gardien_preuve',
                'technicalrole' => null,
                'capabilityhints' => [],
                'competencies' => [
                    'UCKK-COMP-005',
                    'UCKK-COMP-008',
                    'UCKK-COMP-013',
                ],
                'programs' => [],
                'tags' => [
                    'preuve',
                    'verification',
                    'contestabilite',
                ],
                'public' => true,
                'sensitive' => true,
                'responsibilitybearing' => true,
            ],
            self::ROLE_STRATEGE_CIVIQUE => [
                'key' => self::ROLE_STRATEGE_CIVIQUE,
                'name' => 'Stratège civique',
                'description' => 'Personne capable de lire les pouvoirs diffus, protéger la légitimité et proposer des réformes responsables.',
                'family' => self::FAMILY_GOVERNANCE,
                'badgekey' => null,
                'technicalrole' => null,
                'capabilityhints' => [],
                'competencies' => [
                    'UCKK-COMP-001',
                    'UCKK-COMP-006',
                    'UCKK-COMP-007',
                    'UCKK-COMP-013',
                ],
                'programs' => [
                    'sciences_politiques',
                ],
                'tags' => [
                    'politique',
                    'legitimite',
                    'reforme',
                ],
                'public' => true,
                'sensitive' => false,
                'responsibilitybearing' => true,
            ],
            self::ROLE_CARTOGRAPHE_AUGMENTE => [
                'key' => self::ROLE_CARTOGRAPHE_AUGMENTE,
                'name' => 'Maître d’œuvre augmenté',
                'description' => 'Personne qui utilise l’IA comme outil de cartographie, clarification et accélération sans lui déléguer l’autorité finale.',
                'family' => self::FAMILY_METHOD,
                'badgekey' => 'ia_gouvernable',
                'technicalrole' => null,
                'capabilityhints' => [],
                'competencies' => [
                    'UCKK-COMP-004',
                    'UCKK-COMP-005',
                    'UCKK-COMP-011',
                ],
                'programs' => [
                    'intelligence_artificielle_gouvernable',
                ],
                'tags' => [
                    'ia',
                    'cartographie',
                    'validation_humaine',
                ],
                'public' => true,
                'sensitive' => false,
                'responsibilitybearing' => true,
            ],
            self::ROLE_DESIGNER_ASSEMBLEES => [
                'key' => self::ROLE_DESIGNER_ASSEMBLEES,
                'name' => 'Designer d’assemblées',
                'description' => 'Personne qui conçoit des espaces de délibération, d’objection, de décision et de mémoire collective.',
                'family' => self::FAMILY_GOVERNANCE,
                'badgekey' => 'participant_assemblee',
                'technicalrole' => null,
                'capabilityhints' => [
                    'mod/uckkassembly:createassembly',
                    'mod/uckkassembly:publishdecision',
                ],
                'competencies' => [
                    'UCKK-COMP-006',
                    'UCKK-COMP-008',
                    'UCKK-COMP-013',
                ],
                'programs' => [
                    'sciences_politiques',
                    'grand_jeu_social',
                ],
                'tags' => [
                    'assemblee',
                    'deliberation',
                    'decision',
                ],
                'public' => true,
                'sensitive' => true,
                'responsibilitybearing' => true,
            ],
            self::ROLE_REFORMATEUR_GRAND_JEU => [
                'key' => self::ROLE_REFORMATEUR_GRAND_JEU,
                'name' => 'Réformateur du Grand Jeu',
                'description' => 'Personne qui identifie les règles du Grand Jeu social et propose des corrections responsables, vérifiables et contestables.',
                'family' => self::FAMILY_GOVERNANCE,
                'badgekey' => 'grand_jeu_social',
                'technicalrole' => null,
                'capabilityhints' => [],
                'competencies' => [
                    'UCKK-COMP-001',
                    'UCKK-COMP-007',
                    'UCKK-COMP-014',
                ],
                'programs' => [
                    'grand_jeu_social',
                ],
                'tags' => [
                    'grand_jeu',
                    'reforme',
                    'regles',
                ],
                'public' => true,
                'sensitive' => false,
                'responsibilitybearing' => true,
            ],
        ];
    }

    /**
     * Create a symbolic role object from a registry definition.
     *
     * @param string $key Symbolic role key.
     * @return self|null
     */
    public static function from_key(string $key): ?self {
        $key = self::normalise_key($key);
        $registry = self::get_registry();

        if (!array_key_exists($key, $registry)) {
            return null;
        }

        return self::from_definition($registry[$key]);
    }

    /**
     * Create a symbolic role from a definition array.
     *
     * @param array<string, mixed> $definition Definition.
     * @return self
     */
    public static function from_definition(array $definition): self {
        return new self(
            (string)($definition['key'] ?? ''),
            (string)($definition['name'] ?? ''),
            (string)($definition['description'] ?? ''),
            (string)($definition['family'] ?? self::FAMILY_LEARNING),
            isset($definition['badgekey']) ? (string)$definition['badgekey'] : null,
            isset($definition['technicalrole']) ? (string)$definition['technicalrole'] : null,
            isset($definition['capabilityhints']) && is_array($definition['capabilityhints']) ? $definition['capabilityhints'] : [],
            isset($definition['competencies']) && is_array($definition['competencies']) ? $definition['competencies'] : [],
            isset($definition['programs']) && is_array($definition['programs']) ? $definition['programs'] : [],
            isset($definition['tags']) && is_array($definition['tags']) ? $definition['tags'] : [],
            !array_key_exists('public', $definition) || (bool)$definition['public'],
            !empty($definition['sensitive']),
            !empty($definition['responsibilitybearing'])
        );
    }

    /**
     * Create from a database record.
     *
     * Expected fields can include:
     * key, name, description, family, badgekey, technicalrole,
     * capabilityhints, competencies, programs, tags, public, sensitive,
     * responsibilitybearing.
     *
     * JSON fields are accepted for list-like properties.
     *
     * @param \stdClass $record Record.
     * @return self
     */
    public static function from_record(\stdClass $record): self {
        return new self(
            (string)($record->key ?? ''),
            (string)($record->name ?? ''),
            (string)($record->description ?? ''),
            (string)($record->family ?? self::FAMILY_LEARNING),
            isset($record->badgekey) ? (string)$record->badgekey : null,
            isset($record->technicalrole) ? (string)$record->technicalrole : null,
            self::decode_list($record->capabilityhints ?? []),
            self::decode_list($record->competencies ?? []),
            self::decode_list($record->programs ?? []),
            self::decode_list($record->tags ?? []),
            !isset($record->public) || (bool)$record->public,
            !empty($record->sensitive),
            !empty($record->responsibilitybearing)
        );
    }

    /**
     * Return all canonical symbolic role objects.
     *
     * @return self[]
     */
    public static function all(): array {
        return array_map(static function(array $definition): self {
            return self::from_definition($definition);
        }, self::get_registry());
    }

    /**
     * Return all canonical symbolic role keys.
     *
     * @return string[]
     */
    public static function keys(): array {
        return array_keys(self::get_registry());
    }

    /**
     * Determine whether a symbolic role exists in the canonical registry.
     *
     * @param string $key Symbolic role key.
     * @return bool
     */
    public static function exists(string $key): bool {
        return self::from_key($key) !== null;
    }

    /**
     * Return roles for a specific family.
     *
     * @param string $family Family key.
     * @return self[]
     */
    public static function by_family(string $family): array {
        $family = self::normalise_key($family);

        return array_values(array_filter(self::all(), static function(self $role) use ($family): bool {
            return $role->get_family() === $family;
        }));
    }

    /**
     * Return governance-sensitive roles.
     *
     * @return self[]
     */
    public static function sensitive_roles(): array {
        return array_values(array_filter(self::all(), static function(self $role): bool {
            return $role->is_sensitive();
        }));
    }

    /**
     * Return public symbolic roles.
     *
     * @return self[]
     */
    public static function public_roles(): array {
        return array_values(array_filter(self::all(), static function(self $role): bool {
            return $role->is_public();
        }));
    }

    /**
     * Return role key.
     *
     * @return string
     */
    public function get_key(): string {
        return $this->key;
    }

    /**
     * Return role name.
     *
     * @return string
     */
    public function get_name(): string {
        return $this->name;
    }

    /**
     * Return role description.
     *
     * @return string
     */
    public function get_description(): string {
        return $this->description;
    }

    /**
     * Return role family.
     *
     * @return string
     */
    public function get_family(): string {
        return $this->family;
    }

    /**
     * Return related badge key.
     *
     * @return string|null
     */
    public function get_badge_key(): ?string {
        return $this->badgekey;
    }

    /**
     * Return related Moodle technical role shortname, if any.
     *
     * This value is a reference only. It does not grant permissions.
     *
     * @return string|null
     */
    public function get_technical_role(): ?string {
        return $this->technicalrole;
    }

    /**
     * Return capability hints.
     *
     * These are documentation and UI hints only.
     *
     * @return string[]
     */
    public function get_capability_hints(): array {
        return $this->capabilityhints;
    }

    /**
     * Return related competencies.
     *
     * @return string[]
     */
    public function get_competencies(): array {
        return $this->competencies;
    }

    /**
     * Return related programs.
     *
     * @return string[]
     */
    public function get_programs(): array {
        return $this->programs;
    }

    /**
     * Return tags.
     *
     * @return string[]
     */
    public function get_tags(): array {
        return $this->tags;
    }

    /**
     * Whether this role can be displayed publicly.
     *
     * @return bool
     */
    public function is_public(): bool {
        return $this->public;
    }

    /**
     * Whether this role is governance-sensitive.
     *
     * @return bool
     */
    public function is_sensitive(): bool {
        return $this->sensitive;
    }

    /**
     * Whether this role implies responsibility language.
     *
     * @return bool
     */
    public function is_responsibility_bearing(): bool {
        return $this->responsibilitybearing;
    }

    /**
     * Whether this symbolic role has a related badge.
     *
     * @return bool
     */
    public function has_badge(): bool {
        return $this->badgekey !== null;
    }

    /**
     * Whether this symbolic role has a related technical Moodle role.
     *
     * @return bool
     */
    public function has_technical_role(): bool {
        return $this->technicalrole !== null;
    }

    /**
     * Whether this symbolic role has capability hints.
     *
     * @return bool
     */
    public function has_capability_hints(): bool {
        return !empty($this->capabilityhints);
    }

    /**
     * Whether this role is the Inquisiteur symbolic role.
     *
     * @return bool
     */
    public function is_inquisiteur(): bool {
        return $this->key === self::ROLE_INQUISITEUR;
    }

    /**
     * Whether this role is the Archiviste symbolic role.
     *
     * @return bool
     */
    public function is_archiviste(): bool {
        return $this->key === self::ROLE_ARCHIVISTE;
    }

    /**
     * Whether this role is a learner-facing role.
     *
     * @return bool
     */
    public function is_learner_facing(): bool {
        return in_array($this->key, [
            self::ROLE_JOUEUR,
            self::ROLE_JOUEUR_LUCIDE,
            self::ROLE_CARTOGRAPHE,
            self::ROLE_BATISSEUR,
            self::ROLE_ARCHITECTE_SENS,
            self::ROLE_ARCHITECTE_OPPORTUNITES,
            self::ROLE_GARDIEN_SYSTEMES_VIVANTS,
            self::ROLE_GARDIEN_PREUVE,
            self::ROLE_CARTOGRAPHE_AUGMENTE,
        ], true);
    }

    /**
     * Whether this role is governance-facing.
     *
     * @return bool
     */
    public function is_governance_facing(): bool {
        return in_array($this->family, [
            self::FAMILY_GOVERNANCE,
            self::FAMILY_ARCHIVE,
            self::FAMILY_METHOD,
        ], true);
    }

    /**
     * Return a warning explaining that this is not a Moodle technical role.
     *
     * @return string
     */
    public function get_permission_warning(): string {
        return get_string_manager()->string_exists('symbolicrole_permission_warning', self::COMPONENT)
            ? get_string('symbolicrole_permission_warning', self::COMPONENT)
            : 'Ce rôle est symbolique. Il ne donne aucune permission Moodle par lui-même.';
    }

    /**
     * Export this role as an array.
     *
     * @return array<string, mixed>
     */
    public function to_array(): array {
        return [
            'key' => $this->key,
            'name' => $this->name,
            'description' => $this->description,
            'family' => $this->family,
            'badgekey' => $this->badgekey,
            'technicalrole' => $this->technicalrole,
            'capabilityhints' => $this->capabilityhints,
            'competencies' => $this->competencies,
            'programs' => $this->programs,
            'tags' => $this->tags,
            'public' => $this->public,
            'sensitive' => $this->sensitive,
            'responsibilitybearing' => $this->responsibilitybearing,
        ];
    }

    /**
     * Export this role as a database-like record.
     *
     * @return \stdClass
     */
    public function to_record(): \stdClass {
        return (object)[
            'key' => $this->key,
            'name' => $this->name,
            'description' => $this->description,
            'family' => $this->family,
            'badgekey' => $this->badgekey,
            'technicalrole' => $this->technicalrole,
            'capabilityhints' => json_encode($this->capabilityhints, JSON_UNESCAPED_UNICODE),
            'competencies' => json_encode($this->competencies, JSON_UNESCAPED_UNICODE),
            'programs' => json_encode($this->programs, JSON_UNESCAPED_UNICODE),
            'tags' => json_encode($this->tags, JSON_UNESCAPED_UNICODE),
            'public' => $this->public ? 1 : 0,
            'sensitive' => $this->sensitive ? 1 : 0,
            'responsibilitybearing' => $this->responsibilitybearing ? 1 : 0,
        ];
    }

    /**
     * Export for Mustache templates.
     *
     * @param array<string, mixed> $overrides Extra prepared values.
     * @return array<string, mixed>
     */
    public function export_for_template(array $overrides = []): array {
        $data = [
            'key' => $this->key,
            'name' => $this->name,
            'description' => $this->description,
            'family' => $this->family,
            'badgekey' => $this->badgekey,
            'hasbadge' => $this->has_badge(),
            'technicalrole' => $this->technicalrole,
            'hastechnicalrole' => $this->has_technical_role(),
            'capabilityhints' => self::list_for_template($this->capabilityhints, 'capability'),
            'hascapabilityhints' => $this->has_capability_hints(),
            'competencies' => self::list_for_template($this->competencies, 'competency'),
            'hascompetencies' => !empty($this->competencies),
            'programs' => self::list_for_template($this->programs, 'program'),
            'hasprograms' => !empty($this->programs),
            'tags' => self::list_for_template($this->tags, 'tag'),
            'hastags' => !empty($this->tags),
            'public' => $this->public,
            'sensitive' => $this->sensitive,
            'responsibilitybearing' => $this->responsibilitybearing,
            'isinquisiteur' => $this->is_inquisiteur(),
            'isarchiviste' => $this->is_archiviste(),
            'learnerfacing' => $this->is_learner_facing(),
            'governancefacing' => $this->is_governance_facing(),
            'permissionwarning' => $this->get_permission_warning(),
        ];

        return array_merge($data, $overrides);
    }

    /**
     * Export all roles for Mustache templates.
     *
     * @param self[]|null $roles Optional role list.
     * @return array<string, mixed>
     */
    public static function export_list_for_template(?array $roles = null): array {
        $roles = $roles ?? self::all();

        $items = array_map(static function(self $role): array {
            return $role->export_for_template();
        }, $roles);

        return [
            'symbolicroles' => $items,
            'hassymbolicroles' => !empty($items),
            'totalroles' => count($items),
        ];
    }

    /**
     * Return whether a symbolic role can be shown to a specific audience.
     *
     * Audience keys:
     * - private
     * - course
     * - cohort
     * - institution
     * - public
     *
     * @param string $audience Audience key.
     * @return bool
     */
    public function can_show_to_audience(string $audience): bool {
        $audience = self::normalise_key($audience);

        if ($audience === 'public') {
            return $this->public && !$this->sensitive;
        }

        if ($audience === 'institution') {
            return $this->public || $this->sensitive;
        }

        if (in_array($audience, ['private', 'course', 'cohort'], true)) {
            return true;
        }

        return false;
    }

    /**
     * Normalise a symbolic role key.
     *
     * @param string $key Raw key.
     * @return string
     */
    public static function normalise_key(string $key): string {
        $key = trim(\core_text::strtolower($key));
        $key = str_replace(['-', ' ', '.', '’', '\''], '_', $key);
        $key = preg_replace('/_+/', '_', $key);
        $key = preg_replace('/[^a-z0-9_]/', '', $key);

        $aliases = [
            'player' => self::ROLE_JOUEUR,
            'joueuse' => self::ROLE_JOUEUR,
            'joueur_initie' => self::ROLE_JOUEUR,
            'lucide' => self::ROLE_JOUEUR_LUCIDE,
            'joueuse_lucide' => self::ROLE_JOUEUR_LUCIDE,
            'builder' => self::ROLE_BATISSEUR,
            'batisseuse' => self::ROLE_BATISSEUR,
            'archive_keeper' => self::ROLE_ARCHIVISTE,
            'archivist' => self::ROLE_ARCHIVISTE,
            'inquisitor' => self::ROLE_INQUISITEUR,
            'inquisition' => self::ROLE_INQUISITEUR,
            'cartographer' => self::ROLE_CARTOGRAPHE,
            'cartographe_de_systemes' => self::ROLE_CARTOGRAPHE,
            'architecte_du_sens' => self::ROLE_ARCHITECTE_SENS,
            'sense_architect' => self::ROLE_ARCHITECTE_SENS,
            'architecte_dopportunites' => self::ROLE_ARCHITECTE_OPPORTUNITES,
            'architecte_d_opportunites' => self::ROLE_ARCHITECTE_OPPORTUNITES,
            'opportunity_architect' => self::ROLE_ARCHITECTE_OPPORTUNITES,
            'gardien_des_systemes_vivants' => self::ROLE_GARDIEN_SYSTEMES_VIVANTS,
            'living_systems_keeper' => self::ROLE_GARDIEN_SYSTEMES_VIVANTS,
            'gardien_de_la_preuve' => self::ROLE_GARDIEN_PREUVE,
            'proof_keeper' => self::ROLE_GARDIEN_PREUVE,
            'strategist' => self::ROLE_STRATEGE_CIVIQUE,
            'stratege' => self::ROLE_STRATEGE_CIVIQUE,
            'cartographe_ia' => self::ROLE_CARTOGRAPHE_AUGMENTE,
            'cartographe_augmente_ia' => self::ROLE_CARTOGRAPHE_AUGMENTE,
            'assembly_designer' => self::ROLE_DESIGNER_ASSEMBLEES,
            'designer_d_assemblees' => self::ROLE_DESIGNER_ASSEMBLEES,
            'reformateur' => self::ROLE_REFORMATEUR_GRAND_JEU,
            'reformateur_du_grand_jeu' => self::ROLE_REFORMATEUR_GRAND_JEU,
        ];

        return $aliases[$key] ?? $key;
    }

    /**
     * Decode a list-like value.
     *
     * @param mixed $value Value.
     * @return string[]
     */
    private static function decode_list($value): array {
        if (is_array($value)) {
            return self::normalise_list($value, false);
        }

        if (is_string($value)) {
            $trimmed = trim($value);

            if ($trimmed === '') {
                return [];
            }

            if (substr($trimmed, 0, 1) === '[') {
                $decoded = json_decode($trimmed, true);

                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    return self::normalise_list($decoded, false);
                }
            }

            return self::normalise_list(preg_split('/[\r\n,]+/', $trimmed) ?: [], false);
        }

        return [];
    }

    /**
     * Normalise a list of values.
     *
     * @param array $items Items.
     * @param bool $lowercase Whether to lowercase values.
     * @return string[]
     */
    private static function normalise_list(array $items, bool $lowercase = true): array {
        $normalised = [];

        foreach ($items as $item) {
            if (!is_scalar($item)) {
                continue;
            }

            $value = trim((string)$item);

            if ($value === '') {
                continue;
            }

            if ($lowercase) {
                $value = self::normalise_key($value);
            }

            $normalised[] = $value;
        }

        return array_values(array_unique($normalised));
    }

    /**
     * Convert a list of strings to a Mustache-friendly list.
     *
     * @param string[] $items Items.
     * @param string $key Field key.
     * @return array<int, array<string, string>>
     */
    private static function list_for_template(array $items, string $key): array {
        return array_map(static function(string $value) use ($key): array {
            return [
                $key => $value,
                'value' => $value,
                'label' => $value,
            ];
        }, array_values($items));
    }
}

