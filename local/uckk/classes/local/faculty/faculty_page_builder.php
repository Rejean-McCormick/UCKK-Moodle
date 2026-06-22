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
 * Faculty page builder for local_uckk.
 *
 * This class merges:
 * - the canonical faculty profile;
 * - the canonical Atlas voie JSON;
 * - public dynamic block data returned by providers.
 *
 * It prepares the exact root context expected by templates/faculty_page.mustache.
 *
 * It does not:
 * - accept file paths from request parameters;
 * - create courses;
 * - modify Moodle;
 * - decide private permissions;
 * - expose notes, participants, completion status or private progress;
 * - render HTML;
 * - filter final display output;
 * - invent template root variables outside DOC_12.
 *
 * @package    local_uckk
 * @copyright  2026 Univers-Cité King Klown
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace local_uckk\local\faculty;

use coding_exception;
use local_uckk\local\atlas\voie_repository;
use moodle_url;
use Throwable;

defined('MOODLE_INTERNAL') || die();

/**
 * Builds public faculty page data.
 */
final class faculty_page_builder {
    /** Moodle component name. */
    public const COMPONENT = 'local_uckk';

    /** Canonical institutional notice. */
    public const NOTICE_INSTITUTIONAL =
        'Les Parchemins UCKK sont des reconnaissances internes. Ils ne constituent pas des diplômes publics accrédités, '
        . 'des grades universitaires publics ou des titres professionnels reconnus par l’État, sauf reconnaissance officielle future.';

    /** Canonical anti-confusion notice. */
    public const NOTICE_ANTI_CONFUSION =
        'Cette page présente une Voie UCKK comme faculté interne de l’Univers-Cité King Klown. '
        . 'Elle ne prétend pas à un statut universitaire public accrédité.';

    /**
     * Faculty registry.
     *
     * @var faculty_registry
     */
    private faculty_registry $registry;

    /**
     * Faculty repository.
     *
     * @var faculty_repository
     */
    private faculty_repository $facultyrepository;

    /**
     * Faculty normalizer.
     *
     * @var faculty_normalizer
     */
    private faculty_normalizer $normalizer;

    /**
     * Atlas voie repository.
     *
     * @var voie_repository
     */
    private voie_repository $voierepository;

    /**
     * Dynamic block provider.
     *
     * @var faculty_dynamic_block_provider|null
     */
    private ?faculty_dynamic_block_provider $dynamicblockprovider;

    /**
     * Constructor.
     *
     * @param faculty_registry|null $registry Optional registry, useful for tests.
     * @param faculty_repository|null $facultyrepository Optional repository, useful for tests.
     * @param faculty_normalizer|null $normalizer Optional normalizer, useful for tests.
     * @param voie_repository|null $voierepository Optional Atlas repository, useful for tests.
     * @param faculty_dynamic_block_provider|null $dynamicblockprovider Optional provider, useful for tests.
     */
    public function __construct(
        ?faculty_registry $registry = null,
        ?faculty_repository $facultyrepository = null,
        ?faculty_normalizer $normalizer = null,
        ?voie_repository $voierepository = null,
        ?faculty_dynamic_block_provider $dynamicblockprovider = null
    ) {
        $this->registry = $registry ?? new faculty_registry();
        $this->facultyrepository = $facultyrepository ?? new faculty_repository($this->registry);
        $this->normalizer = $normalizer ?? new faculty_normalizer();
        $this->voierepository = $voierepository ?? new voie_repository();
        $this->dynamicblockprovider = $dynamicblockprovider ?? new faculty_dynamic_block_provider();
    }

    /**
     * Build a public faculty page context by slug.
     *
     * Canonical method required by DOC_12.
     *
     * @param string $slug Faculty slug.
     * @return array<string, mixed>
     */
    public function build(string $slug): array {
        $record = $this->registry->resolve_by_slug($slug);
        $profile = $this->facultyrepository->get_by_slug($record['slug']);
        $profile = $this->normalizer->normalize($profile, $record);
        $atlas = $this->load_atlas_voie($record, $profile);

        $this->assert_identity_consistency($record, $profile, $atlas);

        return $this->build_from_data($profile, $atlas, $record);
    }

    /**
     * Build all pages listed in the manifest.
     *
     * @return array<int, array<string, mixed>>
     */
    public function build_all(): array {
        $pages = [];

        foreach ($this->registry->get_all() as $record) {
            $pages[] = $this->build($record['slug']);
        }

        return $pages;
    }

    /**
     * Build all published public pages listed in the manifest.
     *
     * @return array<int, array<string, mixed>>
     */
    public function build_public(): array {
        $pages = [];

        foreach ($this->registry->get_public_items() as $record) {
            $pages[] = $this->build($record['slug']);
        }

        return $pages;
    }

    /**
     * Build a page context from already loaded data.
     *
     * This is intentionally public for unit tests and external service tests.
     * The normal controller flow should call build($slug).
     *
     * @param array<string, mixed> $profile Normalized faculty profile.
     * @param array<string, mixed> $atlas Decoded Atlas voie.
     * @param array<string, mixed>|null $record Optional registry record.
     * @return array<string, mixed>
     */
    public function build_from_data(array $profile, array $atlas, ?array $record = null): array {
        $profile = $this->normalizer->normalize($profile, $record);

        $projection = $profile['atlas_projection'];
        $context = $this->build_provider_context($profile, $atlas, $record);

        $navigation = $this->list_value($profile, 'navigation');
        $sections = $this->list_value($profile, 'sections');
        $courses = $this->build_courses($atlas, $projection);
        $projectfinal = $this->build_project_final($atlas, $projection);
        $limits = $this->build_limits($atlas, $projection);
        $relations = $this->build_relations($atlas, $projection);
        $dynamicblocks = $this->build_dynamic_blocks($profile['dynamic_blocks'], $context);
        $featuredblocks = $this->list_value($profile, 'featured_blocks');
        $faq = $this->list_value($profile, 'faq');
        $notices = $this->build_notices($profile);

        return [
            'page' => $this->build_page($profile),
            'hero' => $this->build_hero($profile),
            'navigation' => $navigation,
            'has_navigation' => !empty($navigation),
            'identity' => $profile['identity'],
            'sections' => $sections,
            'has_sections' => !empty($sections),
            'atlas' => $this->build_atlas_projection($atlas, $projection),
            'courses' => $courses,
            'has_courses' => !empty($courses),
            'project_final' => $projectfinal,
            'has_project_final' => !empty($projectfinal),
            'limits' => $limits,
            'has_limits' => !empty($limits),
            'relations' => $relations,
            'has_relations' => !empty($relations),
            'dynamic_blocks' => $dynamicblocks,
            'has_dynamic_blocks' => !empty($dynamicblocks),
            'featured_blocks' => $featuredblocks,
            'has_featured_blocks' => !empty($featuredblocks),
            'faq' => $faq,
            'has_faq' => !empty($faq),
            'contact' => $profile['contact'],
            'has_contact' => !empty($profile['contact']),
            'notices' => $notices,
            'has_notices' => !empty($notices),
            'metadata' => $this->build_metadata($profile, $atlas),
        ];
    }

    /**
     * Load the Atlas voie file declared by the manifest/profile.
     *
     * @param array<string, mixed> $record Registry record.
     * @param array<string, mixed> $profile Normalized profile.
     * @return array<string, mixed>
     */
    private function load_atlas_voie(array $record, array $profile): array {
        $filename = $this->string_value($record, 'atlas_file');

        if ($filename === '' && isset($profile['source_atlas']['file'])) {
            $filename = $this->string($profile['source_atlas']['file']);
        }

        if ($filename === '') {
            throw new coding_exception('Unable to resolve Atlas voie file for faculty page.');
        }

        try {
            if (method_exists($this->voierepository, 'get_by_file')) {
                $atlas = $this->voierepository->get_by_file($filename);
            } else if (method_exists($this->voierepository, 'get_by_voie_id')) {
                $atlas = $this->voierepository->get_by_voie_id($this->string_value($record, 'voie_id'));
            } else {
                throw new coding_exception('voie_repository must implement get_by_file() or get_by_voie_id().');
            }
        } catch (Throwable $exception) {
            throw new coding_exception('Unable to load Atlas voie for faculty page: ' . $exception->getMessage());
        }

        if (!is_array($atlas)) {
            throw new coding_exception('Atlas voie repository returned invalid data for: ' . $filename);
        }

        return $atlas;
    }

    /**
     * Assert minimal identity consistency before building public data.
     *
     * @param array<string, mixed>|null $record Registry record.
     * @param array<string, mixed> $profile Normalized profile.
     * @param array<string, mixed> $atlas Atlas voie.
     */
    private function assert_identity_consistency(?array $record, array $profile, array $atlas): void {
        if ($record !== null) {
            foreach (['faculty_id', 'voie_id', 'slug'] as $field) {
                if ($this->string_value($record, $field) !== $this->string_value($profile, $field)) {
                    throw new coding_exception('Faculty page identity mismatch on field: ' . $field);
                }
            }
        }

        if ($this->string_value($profile, 'voie_id') !== $this->string_value($atlas, 'voie_id')) {
            throw new coding_exception('Faculty profile voie_id does not match Atlas voie_id.');
        }

        if ($this->string_value($profile['moodle'] ?? [], 'course_prefix') !== $this->string_value($atlas, 'code')) {
            throw new coding_exception('Faculty course_prefix does not match Atlas code.');
        }
    }

    /**
     * Build the page root context.
     *
     * @param array<string, mixed> $profile Normalized profile.
     * @return array<string, mixed>
     */
    private function build_page(array $profile): array {
        $slug = $this->string_value($profile, 'slug');

        return [
            'slug' => $slug,
            'faculty_id' => $this->string_value($profile, 'faculty_id'),
            'voie_id' => $this->string_value($profile, 'voie_id'),
            'status' => $this->string_value($profile, 'status'),
            'visibility' => $this->string_value($profile, 'visibility'),
            'seo_title' => $this->string_value($profile['seo'] ?? [], 'title'),
            'seo_description' => $this->string_value($profile['seo'] ?? [], 'description'),
            'canonical_url' => $this->canonical_url($slug),
        ];
    }

    /**
     * Build the hero context.
     *
     * @param array<string, mixed> $profile Normalized profile.
     * @return array<string, mixed>
     */
    private function build_hero(array $profile): array {
        $hero = $this->array_value($profile, 'hero');
        $identity = $this->array_value($profile, 'identity');

        return [
            'eyebrow' => $this->string_value($identity, 'eyebrow'),
            'title' => $this->string_value($hero, 'title'),
            'subtitle' => $this->string_value($hero, 'subtitle'),
            'summary' => $this->string_value($hero, 'summary'),
            'primary_cta' => $this->cta_value($hero['primary_cta'] ?? []),
            'secondary_cta' => $this->cta_value($hero['secondary_cta'] ?? []),
        ];
    }

    /**
     * Build projected Atlas top-level context.
     *
     * @param array<string, mixed> $atlas Atlas voie.
     * @param array<string, bool> $projection Atlas projection flags.
     * @return array<string, mixed>
     */
    private function build_atlas_projection(array $atlas, array $projection): array {
        return [
            'definition_courte' => $this->projection_string($atlas, $projection, 'definition_courte', 'show_definition_courte'),
            'angle_fondamental' => $this->projection_string($atlas, $projection, 'angle_fondamental', 'show_angle_fondamental'),
            'competence_centrale' => $this->projection_string($atlas, $projection, 'competence_centrale', 'show_competence_centrale'),
            'seuils_progression' => $this->projection_list($atlas, $projection, 'seuils_progression', 'show_seuils_progression'),
            'show_definition_courte' => $this->projection_enabled($projection, 'show_definition_courte'),
            'show_angle_fondamental' => $this->projection_enabled($projection, 'show_angle_fondamental'),
            'show_competence_centrale' => $this->projection_enabled($projection, 'show_competence_centrale'),
            'show_seuils_progression' => $this->projection_enabled($projection, 'show_seuils_progression'),
        ];
    }

    /**
     * Build projected course cards from Atlas cours_conceptuels.
     *
     * This method intentionally does not expose concepts_associes, criteres_passage,
     * notes, participants or completion status.
     *
     * @param array<string, mixed> $atlas Atlas voie.
     * @param array<string, bool> $projection Atlas projection flags.
     * @return array<int, array<string, mixed>>
     */
    private function build_courses(array $atlas, array $projection): array {
        if (!$this->projection_enabled($projection, 'show_courses')) {
            return [];
        }

        $courses = $this->list_value($atlas, 'cours_conceptuels');
        $cards = [];

        foreach ($courses as $course) {
            if (!is_array($course)) {
                continue;
            }

            $concept = $this->array_value($course, 'concept_maitre');
            $artefact = $this->array_value($course, 'artefact_maitrise');

            $coursid = $this->string_value($course, 'cours_id');
            $name = $this->string_value($course, 'nom');

            $artefacttype = $this->string_value($artefact, 'type');

            $cards[] = [
                'cours_id' => $this->projection_enabled($projection, 'show_course_codes') ? $coursid : '',
                'ordre' => $this->int_value($course, 'ordre', count($cards) + 1),
                'nom' => $name,
                'fullname' => $this->build_course_fullname($coursid, $name, $projection),
                'concept_maitre_nom' => $this->projection_enabled($projection, 'show_concept_maitre')
                    ? $this->string_value($concept, 'nom')
                    : '',
                'concept_maitre_definition' => $this->projection_enabled($projection, 'show_concept_maitre')
                    ? $this->string_value($concept, 'definition_courte')
                    : '',
                'artefact_type' => $this->projection_enabled($projection, 'show_artefacts')
                    ? $this->public_label_from_key($artefacttype)
                    : '',
                'artefact_type_raw' => $this->projection_enabled($projection, 'show_artefacts')
                    ? $artefacttype
                    : '',
                'artefact_nom' => $this->projection_enabled($projection, 'show_artefacts')
                    ? $this->string_value($artefact, 'nom')
                    : '',
                'artefact_description' => $this->projection_enabled($projection, 'show_artefacts')
                    ? $this->string_value($artefact, 'description')
                    : '',
                'moodle_url' => '',
                'is_moodle_available' => false,
            ];
        }

        usort($cards, static function (array $left, array $right): int {
            return ((int)$left['ordre']) <=> ((int)$right['ordre']);
        });

        return array_values($cards);
    }

    /**
     * Build the project_final context.
     *
     * @param array<string, mixed> $atlas Atlas voie.
     * @param array<string, bool> $projection Atlas projection flags.
     * @return array<string, mixed>
     */
    private function build_project_final(array $atlas, array $projection): array {
        if (!$this->projection_enabled($projection, 'show_projet_final')) {
            return [];
        }

        $project = $this->array_value($atlas, 'projet_final');

        $artefacttype = $this->string_value($project, 'artefact_type');

        return [
            'title' => $this->string_value($project, 'titre', $this->string_value($project, 'title')),
            'body' => $this->string_value(
                $project,
                'description',
                $this->string_value($project, 'body', $this->string_value($project, 'objectif'))
            ),
            'artefact_type' => $this->public_label_from_key($artefacttype),
            'artefact_type_raw' => $artefacttype,
            'artefact_nom' => $this->string_value($project, 'artefact_nom'),
            'criteres' => $this->string_list($project['criteres'] ?? $project['criteres_reussite'] ?? []),
        ];
    }

    /**
     * Build projected ethical limits.
     *
     * @param array<string, mixed> $atlas Atlas voie.
     * @param array<string, bool> $projection Atlas projection flags.
     * @return array<int, string>
     */
    private function build_limits(array $atlas, array $projection): array {
        if (!$this->projection_enabled($projection, 'show_limites_ethiques')) {
            return [];
        }

        return $this->string_list($atlas['limites_ethiques'] ?? []);
    }

    /**
     * Build projected inter-voie relations.
     *
     * @param array<string, mixed> $atlas Atlas voie.
     * @param array<string, bool> $projection Atlas projection flags.
     * @return array<int, array<string, mixed>>
     */
    private function build_relations(array $atlas, array $projection): array {
        if (!$this->projection_enabled($projection, 'show_relations_intervoies')) {
            return [];
        }

        $relations = $this->list_value($atlas, 'relations_intervoies');
        $items = [];

        foreach ($relations as $relation) {
            if (!is_array($relation)) {
                continue;
            }

            $voieid = $this->string_value($relation, 'voie_id');
            $title = $this->string_value($relation, 'nom', $this->string_value($relation, 'title'));
            if ($title === '') {
                $title = $this->public_label_from_voie_id($voieid);
            }

            $body = $this->string_value($relation, 'relation', $this->string_value($relation, 'description'));

            $items[] = [
                'voie_id' => $voieid,
                'voie_label' => $title,
                'display_voie' => $title,
                'title' => $title,
                'body' => $body,
                'relation' => $body,
                'type' => $this->string_value($relation, 'type'),
                'slug' => $this->slug_from_voie_id($voieid),
                'url' => $this->url_from_voie_id($voieid),
            ];
        }

        return $items;
    }

    /**
     * Build dynamic block contexts.
     *
     * @param array<int, array<string, mixed>> $blocks Normalized dynamic block definitions.
     * @param array<string, mixed> $context Provider context.
     * @return array<int, array<string, mixed>>
     */
    private function build_dynamic_blocks(array $blocks, array $context): array {
        $built = [];

        foreach ($blocks as $block) {
            if (!is_array($block)) {
                continue;
            }

            if ($this->string_value($block, 'visibility') !== 'public') {
                continue;
            }

            $items = $this->resolve_dynamic_block_items($block, $context);

            $built[] = [
                'id' => $this->string_value($block, 'id'),
                'type' => $this->string_value($block, 'type'),
                'title' => $this->string_value($block, 'title'),
                'items' => $items,
                'has_items' => !empty($items),
                'empty_state' => $this->string_value($block, 'empty_state'),
                'visibility' => 'public',
            ];
        }

        return $built;
    }

    /**
     * Resolve public items for one dynamic block.
     *
     * @param array<string, mixed> $block Dynamic block definition.
     * @param array<string, mixed> $context Provider context.
     * @return array<int, array<string, mixed>>
     */
    private function resolve_dynamic_block_items(array $block, array $context): array {
        if ($this->dynamicblockprovider === null) {
            return [];
        }

        try {
            if (method_exists($this->dynamicblockprovider, 'get_block_data')) {
                $data = $this->dynamicblockprovider->get_block_data($block, $context);
            } else if (method_exists($this->dynamicblockprovider, 'resolve_block')) {
                $data = $this->dynamicblockprovider->resolve_block($block, $context);
            } else if (method_exists($this->dynamicblockprovider, 'provide')) {
                $data = $this->dynamicblockprovider->provide($block, $context);
            } else {
                return [];
            }
        } catch (Throwable $exception) {
            return [];
        }

        if (!is_array($data)) {
            return [];
        }

        if (array_key_exists('items', $data) && is_array($data['items'])) {
            return $this->public_item_list($data['items']);
        }

        if ($this->is_list_array($data)) {
            return $this->public_item_list($data);
        }

        return [];
    }

    /**
     * Build canonical public notices.
     *
     * @param array<string, mixed> $profile Normalized profile.
     * @return array<int, array<string, string>>
     */
    private function build_notices(array $profile): array {
        $notices = [
            [
                'type' => 'institutional',
                'title' => 'Reconnaissance interne',
                'body' => self::NOTICE_INSTITUTIONAL,
            ],
            [
                'type' => 'light',
                'title' => 'Statut public',
                'body' => self::NOTICE_ANTI_CONFUSION,
            ],
        ];

        $guardrails = $profile['governance']['public_claims_guardrails'] ?? [];
        $publicguardrails = [];

        if (is_array($guardrails)) {
            foreach ($guardrails as $guardrail) {
                if (!is_string($guardrail) || trim($guardrail) === '') {
                    continue;
                }

                $publicguardrails[] = trim($guardrail);
            }
        }

        $publicguardrails = array_values(array_unique($publicguardrails));
        if (!empty($publicguardrails)) {
            $notices[] = [
                'type' => 'integrity',
                'title' => 'Garde-fous publics',
                'body' => implode(' · ', $publicguardrails),
            ];
        }

        return $notices;
    }

    /**
     * Build metadata list.
     *
     * @param array<string, mixed> $profile Normalized profile.
     * @param array<string, mixed> $atlas Atlas voie.
     * @return array<int, array<string, string>>
     */
    private function build_metadata(array $profile, array $atlas): array {
        return [
            [
                'label' => 'Faculté',
                'value' => $this->string_value($profile['identity'] ?? [], 'name'),
            ],
            [
                'label' => 'Voie',
                'value' => $this->string_value($atlas, 'nom'),
            ],
            [
                'label' => 'Code',
                'value' => $this->string_value($atlas, 'code'),
            ],
            [
                'label' => 'Statut',
                'value' => $this->string_value($profile, 'status'),
            ],
            [
                'label' => 'Visibilité',
                'value' => $this->string_value($profile, 'visibility'),
            ],
            [
                'label' => 'Cours Atlas projetés',
                'value' => (string)count($this->build_courses($atlas, $profile['atlas_projection'] ?? [])),
            ],
        ];
    }

    /**
     * Build provider context.
     *
     * @param array<string, mixed> $profile Normalized profile.
     * @param array<string, mixed> $atlas Atlas voie.
     * @param array<string, mixed>|null $record Registry record.
     * @return array<string, mixed>
     */
    private function build_provider_context(array $profile, array $atlas, ?array $record): array {
        return [
            'component' => self::COMPONENT,
            'slug' => $this->string_value($profile, 'slug'),
            'faculty_id' => $this->string_value($profile, 'faculty_id'),
            'voie_id' => $this->string_value($profile, 'voie_id'),
            'course_prefix' => $this->string_value($profile['moodle'] ?? [], 'course_prefix'),
            'category_idnumber' => $this->string_value($profile['moodle'] ?? [], 'category_idnumber'),
            'hub_course_idnumber' => $this->string_value($profile['moodle'] ?? [], 'hub_course_idnumber'),
            'faculty' => $profile,
            'atlas' => $atlas,
            'registry' => $record ?? [],
        ];
    }

    /**
     * Build a course full name.
     *
     * @param string $coursid Course id.
     * @param string $name Course name.
     * @param array<string, bool> $projection Projection flags.
     * @return string
     */
    private function build_course_fullname(string $coursid, string $name, array $projection): string {
        if ($name === '') {
            return $coursid;
        }

        if ($coursid === '' || !$this->projection_enabled($projection, 'show_course_codes')) {
            return $name;
        }

        return $coursid . ' — ' . $name;
    }

    /**
     * Return canonical Moodle URL for a faculty page slug.
     *
     * @param string $slug Faculty slug.
     * @return string
     */
    private function canonical_url(string $slug): string {
        if ($slug === '') {
            return '';
        }

        return (new moodle_url('/local/uckk/faculty.php', ['slug' => $slug]))->out(false);
    }

    /**
     * Return projected string when the flag is enabled.
     *
     * @param array<string, mixed> $data Source data.
     * @param array<string, bool> $projection Projection flags.
     * @param string $field Source field.
     * @param string $flag Projection flag.
     * @return string
     */
    private function projection_string(array $data, array $projection, string $field, string $flag): string {
        if (!$this->projection_enabled($projection, $flag)) {
            return '';
        }

        return $this->string_value($data, $field);
    }

    /**
     * Return projected list when the flag is enabled.
     *
     * @param array<string, mixed> $data Source data.
     * @param array<string, bool> $projection Projection flags.
     * @param string $field Source field.
     * @param string $flag Projection flag.
     * @return array<int, mixed>
     */
    private function projection_list(array $data, array $projection, string $field, string $flag): array {
        if (!$this->projection_enabled($projection, $flag)) {
            return [];
        }

        return $this->list_value($data, $field);
    }

    /**
     * Return whether a projection flag is enabled.
     *
     * @param array<string, mixed> $projection Projection flags.
     * @param string $flag Flag.
     * @return bool
     */
    private function projection_enabled(array $projection, string $flag): bool {
        return array_key_exists($flag, $projection) && $projection[$flag] === true;
    }

    /**
     * Convert dynamic provider records into a conservative public item list.
     *
     * @param array<int, mixed> $items Items.
     * @return array<int, array<string, mixed>>
     */
    private function public_item_list(array $items): array {
        $public = [];

        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }

            $title = $this->string_value($item, 'title');
            $summary = $this->string_value($item, 'summary', $this->string_value($item, 'body'));
            $body = $this->string_value($item, 'body', $summary);
            $relation = $this->string_value($item, 'relation');
            $url = $this->safe_public_url($this->string_value($item, 'url'));

            $publicitem = [
                'title' => $title,
                'summary' => $summary,
                'body' => $body,
                'relation' => $relation,
                'url' => $url,
                'date' => $this->string_value($item, 'date', $this->string_value($item, 'time')),
                'date_start' => $this->string_value($item, 'date_start'),
                'date_end' => $this->string_value($item, 'date_end'),
                'location' => $this->string_value($item, 'location'),
                'source_label' => $this->string_value($item, 'source_label'),
                'cours_id' => $this->string_value($item, 'cours_id'),
                'fullname' => $this->string_value($item, 'fullname'),
                'availability_label' => $this->string_value($item, 'availability_label'),
                'faculty_id' => $this->string_value($item, 'faculty_id'),
                'slug' => $this->string_value($item, 'slug'),
                'name' => $this->string_value($item, 'name'),
                'type' => $this->string_value($item, 'type'),
            ];

            if ($this->is_public_item_empty($publicitem)) {
                continue;
            }

            $public[] = $publicitem;
        }

        return $public;
    }

    /**
     * Return a CTA value.
     *
     * @param mixed $value CTA value.
     * @return array<string, string>
     */
    private function cta_value($value): array {
        if (!is_array($value)) {
            return [
                'label' => '',
                'target' => '',
            ];
        }

        return [
            'label' => $this->string_value($value, 'label'),
            'target' => $this->safe_public_url($this->string_value($value, 'target')),
        ];
    }

    /**
     * Return slug from a voie_id through the registry.
     *
     * @param string $voieid Voie id.
     * @return string
     */
    private function slug_from_voie_id(string $voieid): string {
        if ($voieid === '') {
            return '';
        }

        try {
            $record = $this->registry->resolve_by_voie_id($voieid);
        } catch (Throwable $exception) {
            return '';
        }

        return $this->string_value($record, 'slug');
    }

    /**
     * Return faculty URL from a voie_id through the registry.
     *
     * @param string $voieid Voie id.
     * @return string
     */
    private function url_from_voie_id(string $voieid): string {
        $slug = $this->slug_from_voie_id($voieid);

        if ($slug === '') {
            return '';
        }

        return $this->canonical_url($slug);
    }

    /**
     * Return a string value from an array.
     *
     * @param array<string, mixed> $data Data.
     * @param string $key Key.
     * @param string $default Default.
     * @return string
     */
    private function string_value(array $data, string $key, string $default = ''): string {
        if (!array_key_exists($key, $data)) {
            return $default;
        }

        return $this->string($data[$key], $default);
    }

    /**
     * Return a string.
     *
     * @param mixed $value Value.
     * @param string $default Default.
     * @return string
     */
    private function string($value, string $default = ''): string {
        if (!is_string($value)) {
            return $default;
        }

        return trim($value);
    }

    /**
     * Return an int value from an array.
     *
     * @param array<string, mixed> $data Data.
     * @param string $key Key.
     * @param int $default Default.
     * @return int
     */
    private function int_value(array $data, string $key, int $default = 0): int {
        if (!array_key_exists($key, $data)) {
            return $default;
        }

        if (is_int($data[$key])) {
            return $data[$key];
        }

        if (is_string($data[$key]) && preg_match('/^\d+$/', $data[$key])) {
            return (int)$data[$key];
        }

        return $default;
    }

    /**
     * Return an associative array value.
     *
     * @param array<string, mixed> $data Data.
     * @param string $key Key.
     * @return array<string, mixed>
     */
    private function array_value(array $data, string $key): array {
        if (!array_key_exists($key, $data) || !is_array($data[$key])) {
            return [];
        }

        if ($this->is_list_array($data[$key])) {
            return [];
        }

        return $data[$key];
    }

    /**
     * Return a list array value.
     *
     * @param array<string, mixed> $data Data.
     * @param string $key Key.
     * @return array<int, mixed>
     */
    private function list_value(array $data, string $key): array {
        if (!array_key_exists($key, $data) || !is_array($data[$key])) {
            return [];
        }

        return array_values($data[$key]);
    }

    /**
     * Return a string list.
     *
     * @param mixed $value Value.
     * @return array<int, string>
     */
    private function string_list($value): array {
        if (!is_array($value)) {
            return [];
        }

        $items = [];

        foreach ($value as $item) {
            if (!is_string($item)) {
                continue;
            }

            $item = trim($item);

            if ($item === '') {
                continue;
            }

            $items[] = $item;
        }

        return $items;
    }

    /**
     * Return a readable label for a technical key.
     *
     * @param string $key Technical key.
     * @return string
     */
    private function public_label_from_key(string $key): string {
        $key = trim($key);

        if ($key === '') {
            return '';
        }

        $label = str_replace(['_', '-'], ' ', $key);
        $label = preg_replace('/\s+/', ' ', $label) ?: $label;

        return ucfirst($label);
    }

    /**
     * Return a readable public label for a related voie id.
     *
     * @param string $voieid Voie id.
     * @return string
     */
    private function public_label_from_voie_id(string $voieid): string {
        $labels = [
            'voie_grand_jeu_social' => 'Voie du Grand Jeu social',
            'voie_economie' => 'Voie d’Économie',
            'voie_ecologie' => 'Voie d’Écologie',
            'voie_sciences_politiques' => 'Voie des Sciences politiques',
            'voie_linguistique_architecture_du_sens' => 'Voie de la Linguistique et de l’architecture du sens',
            'voie_metaphysique' => 'Voie de la Métaphysique',
            'voie_ia_gouvernable' => 'Voie de l’Intelligence artificielle gouvernable',
            'voie_intervention_sociale_systemes_humains' => 'Voie de l’Intervention sociale et des systèmes humains',
            'voie_architecture_sociotechnique' => 'Voie d’Architecture sociotechnique',
            'voie_ecosysteme_digital_koa' => 'Voie de l’Architecture de l’écosystème digital kOA',
        ];

        if (array_key_exists($voieid, $labels)) {
            return $labels[$voieid];
        }

        if ($voieid === '') {
            return '';
        }

        return $this->public_label_from_key(preg_replace('/^voie_/', '', $voieid) ?: $voieid);
    }

    /**
     * Return true when a public dynamic item has no displayable public content.
     *
     * @param array<string, mixed> $item Public item.
     * @return bool
     */
    private function is_public_item_empty(array $item): bool {
        $fields = [
            'title',
            'summary',
            'body',
            'relation',
            'url',
            'date',
            'date_start',
            'date_end',
            'location',
            'source_label',
            'cours_id',
            'fullname',
            'availability_label',
            'faculty_id',
            'slug',
            'name',
        ];

        foreach ($fields as $field) {
            if (!empty($item[$field]) && is_string($item[$field]) && trim($item[$field]) !== '') {
                return false;
            }
        }

        return true;
    }

    /**
     * Return a safe public URL or anchor.
     *
     * @param string $url URL.
     * @return string
     */
    private function safe_public_url(string $url): string {
        $url = trim($url);

        if ($url === '') {
            return '';
        }

        $lower = strtolower($url);

        if (
            $this->starts_with($lower, 'javascript:')
            || $this->starts_with($lower, 'data:')
            || $this->starts_with($lower, 'file:')
        ) {
            return '';
        }

        if ($this->starts_with($url, '#')) {
            return preg_match('/^#[a-z0-9]+(?:-[a-z0-9]+)*$/', $url) === 1 ? $url : '';
        }

        if ($this->starts_with($url, '/local/uckk/')) {
            return strpos($url, '..') === false ? $url : '';
        }

        if ($this->starts_with($url, 'https://')) {
            return filter_var($url, FILTER_VALIDATE_URL) !== false ? $url : '';
        }

        if ($url === 'contact.php') {
            return $url;
        }

        return '';
    }

    /**
     * Return whether an array is a list.
     *
     * @param array<mixed> $array Array.
     * @return bool
     */
    private function is_list_array(array $array): bool {
        if ($array === []) {
            return true;
        }

        return array_keys($array) === range(0, count($array) - 1);
    }

    /**
     * Return whether a string starts with a prefix.
     *
     * @param string $value Value.
     * @param string $prefix Prefix.
     * @return bool
     */
    private function starts_with(string $value, string $prefix): bool {
        return substr($value, 0, strlen($prefix)) === $prefix;
    }
}
