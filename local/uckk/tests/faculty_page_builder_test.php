<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

/**
 * Unit tests for the UCKK Faculty page builder.
 *
 * @package    local_uckk
 * @category   test
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace local_uckk\local\faculty;

use coding_exception;
use local_uckk\local\atlas\voie_repository;

defined('MOODLE_INTERNAL') || die();

/**
 * Unit tests for {@see faculty_page_builder}.
 *
 * @package local_uckk
 * @covers  \local_uckk\local\faculty\faculty_page_builder
 */
final class faculty_page_builder_test extends \advanced_testcase {
    /**
     * Prepare a clean Moodle test state.
     */
    protected function setUp(): void {
        parent::setUp();

        $this->resetAfterTest(true);
    }

    /**
     * The builder must emit only documented top-level Mustache variables.
     */
    public function test_build_returns_only_documented_top_level_template_keys(): void {
        $page = $this->build_page();

        $this->assertEqualsCanonicalizing([
            'page',
            'hero',
            'navigation',
            'identity',
            'sections',
            'atlas',
            'courses',
            'project_final',
            'limits',
            'relations',
            'dynamic_blocks',
            'featured_blocks',
            'faq',
            'contact',
            'notices',
            'metadata',
        ], array_keys($page));
    }

    /**
     * The page block must be derived from the Faculty profile, not from Atlas.
     */
    public function test_build_maps_page_metadata_from_faculty_profile(): void {
        $page = $this->build_page();

        $this->assertSame('grand-jeu-social', $page['page']['slug']);
        $this->assertSame('faculty_grand_jeu_social', $page['page']['faculty_id']);
        $this->assertSame('voie_grand_jeu_social', $page['page']['voie_id']);
        $this->assertSame('published', $page['page']['status']);
        $this->assertSame('public', $page['page']['visibility']);
        $this->assertSame('Voie du Grand Jeu social | UCKK', $page['page']['seo_title']);
        $this->assertSame(
            'Lire les systèmes, comprendre les règles du jeu, agir avec lucidité.',
            $page['page']['seo_description']
        );
        $this->assertSame('/local/uckk/faculty.php?slug=grand-jeu-social', $page['page']['canonical_url']);
    }

    /**
     * Hero and identity values must stay in their documented template regions.
     */
    public function test_build_maps_hero_and_identity_regions(): void {
        $page = $this->build_page();

        $this->assertSame('Voie UCKK', $page['hero']['eyebrow']);
        $this->assertSame('Voie du Grand Jeu social', $page['hero']['title']);
        $this->assertSame('Une faculté pour lire le jeu avant d’y jouer', $page['hero']['subtitle']);
        $this->assertSame('Explorer les cours', $page['hero']['primary_cta']['label']);
        $this->assertSame('#programme', $page['hero']['primary_cta']['target']);

        $this->assertSame('Voie du Grand Jeu social', $page['identity']['name']);
        $this->assertSame('Grand Jeu social', $page['identity']['short_name']);
        $this->assertSame('Stratège du Grand Jeu social', $page['identity']['title_symbolique']);
        $this->assertSame('Grand Jeu social', $page['identity']['domain']);
        $this->assertSame('Puissance opératoire', $page['identity']['level']);
        $this->assertSame('Faculté de lecture systémique', $page['identity']['faculty_role']);
        $this->assertSame(
            'Lire les systèmes, comprendre les règles du jeu, agir avec lucidité.',
            $page['identity']['one_sentence']
        );
    }

    /**
     * Navigation and static editorial sections must be preserved for Mustache rendering.
     */
    public function test_build_maps_navigation_and_sections(): void {
        $page = $this->build_page();

        $this->assertCount(3, $page['navigation']);
        $this->assertSame('Présentation', $page['navigation'][0]['label']);
        $this->assertSame('#presentation', $page['navigation'][0]['target']);
        $this->assertSame('Programme', $page['navigation'][1]['label']);
        $this->assertSame('#programme', $page['navigation'][1]['target']);

        $this->assertCount(2, $page['sections']);
        $this->assertSame('presentation', $page['sections'][0]['id']);
        $this->assertSame('text', $page['sections'][0]['type']);
        $this->assertSame('Présentation', $page['sections'][0]['title']);
        $this->assertSame('Cette faculté apprend à cartographier les règles visibles et invisibles.', $page['sections'][0]['body']);
    }

    /**
     * Atlas public summary fields must be projected with their visibility flags.
     */
    public function test_build_applies_atlas_summary_projection(): void {
        $page = $this->build_page();

        $this->assertSame('Déchiffrer les systèmes sociaux comme des jeux de règles.', $page['atlas']['definition_courte']);
        $this->assertSame('Tout système social produit des règles explicites et implicites.', $page['atlas']['angle_fondamental']);
        $this->assertSame('Cartographier acteurs, règles, enjeux et tensions.', $page['atlas']['competence_centrale']);
        $this->assertSame(['Lire', 'Modéliser', 'Intervenir'], $page['atlas']['seuils_progression']);

        $this->assertTrue($page['atlas']['show_definition_courte']);
        $this->assertTrue($page['atlas']['show_angle_fondamental']);
        $this->assertTrue($page['atlas']['show_competence_centrale']);
        $this->assertTrue($page['atlas']['show_seuils_progression']);
    }

    /**
     * Courses must be projected from Atlas and sorted by ordre.
     */
    public function test_build_projects_courses_from_atlas_in_order(): void {
        $page = $this->build_page();

        $this->assertCount(3, $page['courses']);
        $this->assertSame('GJS101', $page['courses'][0]['cours_id']);
        $this->assertSame(1, $page['courses'][0]['ordre']);
        $this->assertSame('Cartographie du Grand Jeu social', $page['courses'][0]['nom']);
        $this->assertSame('GJS101 — Cartographie du Grand Jeu social', $page['courses'][0]['fullname']);

        $this->assertSame('GJS102', $page['courses'][1]['cours_id']);
        $this->assertSame(2, $page['courses'][1]['ordre']);

        $this->assertSame('GJS103', $page['courses'][2]['cours_id']);
        $this->assertSame(3, $page['courses'][2]['ordre']);
    }

    /**
     * Public course cards must expose documented public course fields only.
     */
    public function test_build_course_cards_expose_only_public_fields(): void {
        $page = $this->build_page();
        $course = $page['courses'][0];

        $this->assertSame('Cartographie du Grand Jeu social', $course['concept_maitre_nom']);
        $this->assertSame('Lire le terrain social comme une carte de forces.', $course['concept_maitre_definition']);
        $this->assertSame('carte', $course['artefact_type']);
        $this->assertSame('Carte initiale du Grand Jeu social', $course['artefact_nom']);
        $this->assertSame('Une carte synthétique des acteurs, règles et enjeux.', $course['artefact_description']);
        $this->assertSame('', $course['moodle_url']);
        $this->assertFalse($course['is_moodle_available']);

        $this->assertArrayNotHasKey('concepts_associes', $course);
        $this->assertArrayNotHasKey('criteres_passage', $course);
        $this->assertArrayNotHasKey('notes', $course);
        $this->assertArrayNotHasKey('participants', $course);
        $this->assertArrayNotHasKey('completion_status', $course);
    }

    /**
     * show_courses=false must remove the course projection.
     */
    public function test_build_hides_courses_when_atlas_projection_disables_courses(): void {
        $faculty = $this->sample_faculty_profile();
        $faculty['atlas_projection']['show_courses'] = false;

        $page = $this->build_page($faculty);

        $this->assertSame([], $page['courses']);
    }

    /**
     * show_concept_maitre=false must remove concept master text from course cards.
     */
    public function test_build_hides_concept_maitre_when_projection_disables_it(): void {
        $faculty = $this->sample_faculty_profile();
        $faculty['atlas_projection']['show_concept_maitre'] = false;

        $page = $this->build_page($faculty);

        $this->assertSame('', $page['courses'][0]['concept_maitre_nom']);
        $this->assertSame('', $page['courses'][0]['concept_maitre_definition']);
    }

    /**
     * show_artefacts=false must remove public artefact text from course cards.
     */
    public function test_build_hides_artefacts_when_projection_disables_them(): void {
        $faculty = $this->sample_faculty_profile();
        $faculty['atlas_projection']['show_artefacts'] = false;

        $page = $this->build_page($faculty);

        $this->assertSame('', $page['courses'][0]['artefact_type']);
        $this->assertSame('', $page['courses'][0]['artefact_nom']);
        $this->assertSame('', $page['courses'][0]['artefact_description']);
    }

    /**
     * Associated concepts and passage criteria must remain hidden by default on public pages.
     */
    public function test_build_does_not_expose_default_hidden_atlas_detail(): void {
        $page = $this->build_page();
        $flattened = json_encode($page, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);

        $this->assertStringNotContainsString('concept_gjs_acteurs', $flattened);
        $this->assertStringNotContainsString('Acteurs sociaux', $flattened);
        $this->assertStringNotContainsString('Identifier les principaux acteurs, règles et tensions.', $flattened);
        $this->assertStringNotContainsString('Critère privé de passage', $flattened);
    }

    /**
     * Project final, ethical limits, and relations must follow atlas_projection flags.
     */
    public function test_build_applies_project_limits_and_relations_projection_flags(): void {
        $page = $this->build_page();

        $this->assertSame('Carte stratégique complète du Grand Jeu social', $page['project_final']['title']);
        $this->assertSame('Produire une carte opératoire d’un système réel.', $page['project_final']['description']);
        $this->assertSame(['Ne pas manipuler les acteurs cartographiés.'], $page['limits']);
        $this->assertSame('voie_sciences_politiques', $page['relations'][0]['voie_id']);

        $faculty = $this->sample_faculty_profile();
        $faculty['atlas_projection']['show_projet_final'] = false;
        $faculty['atlas_projection']['show_limites_ethiques'] = false;
        $faculty['atlas_projection']['show_relations_intervoies'] = false;

        $hidden = $this->build_page($faculty);

        $this->assertSame([], $hidden['project_final']);
        $this->assertSame([], $hidden['limits']);
        $this->assertSame([], $hidden['relations']);
    }

    /**
     * Dynamic blocks must be delegated to the provider with the Faculty block definitions.
     */
    public function test_build_delegates_dynamic_blocks_to_public_provider(): void {
        $faculty = $this->sample_faculty_profile();
        $atlas = $this->sample_atlas_voie();
        $expectedblocks = [
            [
                'id' => 'annonces',
                'type' => 'announcements',
                'title' => 'Annonces de la faculté',
                'items' => [
                    [
                        'title' => 'Annonce publique de rentrée GJS',
                        'url' => '/mod/forum/discuss.php?d=1',
                        'source_label' => 'Moodle',
                    ],
                ],
                'has_items' => true,
                'empty_state' => 'Aucune annonce publique pour le moment.',
                'visibility' => 'public',
            ],
        ];

        $facultyrepo = $this->createMock(faculty_repository::class);
        $facultyrepo->expects($this->once())
            ->method('get_by_slug')
            ->with('grand-jeu-social')
            ->willReturn($faculty);

        $voierepo = $this->createMock(voie_repository::class);
        $voierepo->expects($this->once())
            ->method('get_by_file')
            ->with('voie_grand_jeu_social.json')
            ->willReturn($atlas);

        $provider = $this->createMock(faculty_dynamic_block_provider::class);
        $provider->expects($this->once())
            ->method('build_public_blocks')
            ->with(
                $this->callback(static function(array $passedfaculty): bool {
                    return $passedfaculty['slug'] === 'grand-jeu-social';
                }),
                $this->callback(static function(array $passedatlas): bool {
                    return $passedatlas['voie_id'] === 'voie_grand_jeu_social';
                }),
                $this->callback(static function(array $blockdefs): bool {
                    return count($blockdefs) === 1
                        && $blockdefs[0]['id'] === 'annonces'
                        && $blockdefs[0]['source']['provider'] === 'moodle_forum';
                })
            )
            ->willReturn($expectedblocks);

        $builder = new faculty_page_builder($facultyrepo, $voierepo, $provider);
        $page = $builder->build('grand-jeu-social');

        $this->assertSame($expectedblocks, $page['dynamic_blocks']);
    }

    /**
     * Non-public dynamic block definitions must not be passed through to the provider.
     */
    public function test_build_filters_non_public_dynamic_block_definitions_before_provider_call(): void {
        $faculty = $this->sample_faculty_profile();
        $faculty['dynamic_blocks'][] = [
            'id' => 'coordination',
            'type' => 'announcements',
            'title' => 'Coordination interne',
            'source' => [
                'provider' => 'moodle_forum',
                'course_idnumber' => 'GJS-HUB',
                'forum_name' => 'Forum privé',
            ],
            'limit' => 5,
            'visibility' => 'restricted',
            'empty_state' => '',
        ];

        $page = $this->build_page($faculty);

        $this->assertCount(1, $page['dynamic_blocks']);
        $this->assertSame('annonces', $page['dynamic_blocks'][0]['id']);

        $flattened = json_encode($page, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
        $this->assertStringNotContainsString('coordination', $flattened);
        $this->assertStringNotContainsString('Coordination interne', $flattened);
        $this->assertStringNotContainsString('Forum privé', $flattened);
    }

    /**
     * Featured blocks, FAQ, contact and guardrail notices must be mapped to public template data.
     */
    public function test_build_maps_featured_blocks_faq_contact_and_notices(): void {
        $page = $this->build_page();

        $this->assertSame('principle', $page['featured_blocks'][0]['type']);
        $this->assertSame('Lire avant d’agir', $page['featured_blocks'][0]['title']);

        $this->assertSame('Est-ce un diplôme public accrédité ?', $page['faq'][0]['question']);
        $this->assertStringContainsString('reconnaissance interne UCKK', $page['faq'][0]['answer']);

        $this->assertSame('Contact', $page['contact']['label']);
        $this->assertSame('', $page['contact']['email']);
        $this->assertSame('Voir les annonces', $page['contact']['cta']['label']);
        $this->assertSame('#annonces', $page['contact']['cta']['target']);

        $this->assertNotEmpty($page['notices']);
        $notices = json_encode($page['notices'], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
        $this->assertStringContainsString('Reconnaissance interne UCKK', $notices);
        $this->assertStringContainsString('ne constitue pas un diplôme public accrédité', $notices);
    }

    /**
     * Build metadata must expose safe ids and hashes only, not raw JSON.
     */
    public function test_build_metadata_is_safe_and_summary_only(): void {
        $page = $this->build_page();

        $this->assertSame('UCKK-FACULTY-0.1', $page['metadata']['faculty_schema_version']);
        $this->assertSame('UCKK-ATLAS-0.2-draft', $page['metadata']['atlas_schema_version']);
        $this->assertSame('UCKK-GJS', $page['metadata']['category_idnumber']);
        $this->assertSame('GJS', $page['metadata']['course_prefix']);
        $this->assertArrayHasKey('faculty_source_hash', $page['metadata']);
        $this->assertArrayHasKey('atlas_source_hash', $page['metadata']);

        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $page['metadata']['faculty_source_hash']);
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $page['metadata']['atlas_source_hash']);

        $this->assertArrayNotHasKey('raw_faculty_json', $page['metadata']);
        $this->assertArrayNotHasKey('raw_atlas_json', $page['metadata']);
    }

    /**
     * The public page payload must not leak private learner or operational data.
     */
    public function test_build_does_not_expose_private_or_operational_data(): void {
        $page = $this->build_page();
        $flattened = json_encode($page, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);

        $this->assertStringNotContainsString('private_notes', $flattened);
        $this->assertStringNotContainsString('Internal editorial note', $flattened);
        $this->assertStringNotContainsString('student@example.test', $flattened);
        $this->assertStringNotContainsString('completion_status', $flattened);
        $this->assertStringNotContainsString('grade', $flattened);
        $this->assertStringNotContainsString('participants', $flattened);
        $this->assertStringNotContainsString('Badge personnel obtenu', $flattened);
    }

    /**
     * Building a page is read-only and must not create Moodle categories or courses.
     */
    public function test_build_does_not_mutate_moodle_database(): void {
        global $DB;

        $categorycountbefore = $DB->count_records('course_categories');
        $coursecountbefore = $DB->count_records('course');

        $this->build_page();

        $this->assertSame($categorycountbefore, $DB->count_records('course_categories'));
        $this->assertSame($coursecountbefore, $DB->count_records('course'));
    }

    /**
     * Builder must fail closed for file-path-like slugs.
     */
    public function test_build_rejects_file_path_like_slug(): void {
        $builder = $this->builder();

        $this->expectException(coding_exception::class);

        $builder->build('../content/faculties/grand-jeu-social.faculty');
    }

    /**
     * Builder must fail closed when Faculty and Atlas voie ids do not align.
     */
    public function test_build_rejects_faculty_atlas_voie_id_mismatch(): void {
        $atlas = $this->sample_atlas_voie();
        $atlas['voie_id'] = 'voie_economie';

        $builder = $this->builder(null, $atlas);

        $this->expectException(coding_exception::class);

        $builder->build('grand-jeu-social');
    }

    /**
     * Builder must fail closed when Faculty course prefix and Atlas code do not align.
     */
    public function test_build_rejects_faculty_atlas_course_prefix_mismatch(): void {
        $atlas = $this->sample_atlas_voie();
        $atlas['code'] = 'EC';

        $builder = $this->builder(null, $atlas);

        $this->expectException(coding_exception::class);

        $builder->build('grand-jeu-social');
    }

    /**
     * Build a page with optional sample overrides.
     *
     * @param array<string, mixed>|null $faculty Faculty profile override.
     * @param array<string, mixed>|null $atlas Atlas Voie override.
     * @return array<string, mixed>
     */
    private function build_page(?array $faculty = null, ?array $atlas = null): array {
        return $this->builder($faculty, $atlas)->build('grand-jeu-social');
    }

    /**
     * Create a builder with mocked repositories and provider.
     *
     * @param array<string, mixed>|null $faculty Faculty profile override.
     * @param array<string, mixed>|null $atlas Atlas Voie override.
     * @return faculty_page_builder
     */
    private function builder(?array $faculty = null, ?array $atlas = null): faculty_page_builder {
        $faculty ??= $this->sample_faculty_profile();
        $atlas ??= $this->sample_atlas_voie();

        $facultyrepo = $this->createMock(faculty_repository::class);
        $facultyrepo->method('get_by_slug')->willReturn($faculty);

        $voierepo = $this->createMock(voie_repository::class);
        $voierepo->method('get_by_file')->willReturn($atlas);

        $provider = $this->createMock(faculty_dynamic_block_provider::class);
        $provider->method('build_public_blocks')
            ->willReturn([
                [
                    'id' => 'annonces',
                    'type' => 'announcements',
                    'title' => 'Annonces de la faculté',
                    'items' => [],
                    'has_items' => false,
                    'empty_state' => 'Aucune annonce publique pour le moment.',
                    'visibility' => 'public',
                ],
            ]);

        return new faculty_page_builder($facultyrepo, $voierepo, $provider);
    }

    /**
     * Sample Faculty profile.
     *
     * @return array<string, mixed>
     */
    private function sample_faculty_profile(): array {
        return [
            'schema_version' => 'UCKK-FACULTY-0.1',
            'faculty_id' => 'faculty_grand_jeu_social',
            'voie_id' => 'voie_grand_jeu_social',
            'slug' => 'grand-jeu-social',
            'status' => 'published',
            'visibility' => 'public',
            'source_atlas' => [
                'file' => 'voie_grand_jeu_social.json',
                'schema_version_expected' => 'UCKK-ATLAS-0.2-draft',
                'sync_mode' => 'read_only',
            ],
            'moodle' => [
                'category_id' => null,
                'category_idnumber' => 'UCKK-GJS',
                'course_prefix' => 'GJS',
                'public_course_listing' => true,
                'enrolment_visibility' => 'public_info_only',
                'hub_course_idnumber' => 'GJS-HUB',
            ],
            'identity' => [
                'eyebrow' => 'Voie UCKK',
                'name' => 'Voie du Grand Jeu social',
                'short_name' => 'Grand Jeu social',
                'title_symbolique' => 'Stratège du Grand Jeu social',
                'domain' => 'Grand Jeu social',
                'level' => 'Puissance opératoire',
                'faculty_role' => 'Faculté de lecture systémique',
                'one_sentence' => 'Lire les systèmes, comprendre les règles du jeu, agir avec lucidité.',
            ],
            'seo' => [
                'title' => 'Voie du Grand Jeu social | UCKK',
                'description' => 'Lire les systèmes, comprendre les règles du jeu, agir avec lucidité.',
                'keywords' => ['UCKK', 'Voie', 'Grand Jeu social'],
            ],
            'hero' => [
                'title' => 'Voie du Grand Jeu social',
                'subtitle' => 'Une faculté pour lire le jeu avant d’y jouer',
                'summary' => 'Une entrée publique vers les concepts, cours et pratiques du Grand Jeu social.',
                'primary_cta' => [
                    'label' => 'Explorer les cours',
                    'target' => '#programme',
                ],
                'secondary_cta' => [
                    'label' => 'Voir les annonces',
                    'target' => '#annonces',
                ],
            ],
            'navigation' => [
                [
                    'label' => 'Présentation',
                    'target' => '#presentation',
                ],
                [
                    'label' => 'Programme',
                    'target' => '#programme',
                ],
                [
                    'label' => 'Annonces',
                    'target' => '#annonces',
                ],
            ],
            'sections' => [
                [
                    'id' => 'presentation',
                    'type' => 'text',
                    'title' => 'Présentation',
                    'body' => 'Cette faculté apprend à cartographier les règles visibles et invisibles.',
                ],
                [
                    'id' => 'ethique',
                    'type' => 'notice',
                    'title' => 'Cadre éthique',
                    'body' => 'Lire les jeux sociaux sans manipuler les personnes.',
                ],
            ],
            'atlas_projection' => [
                'show_definition_courte' => true,
                'show_angle_fondamental' => true,
                'show_competence_centrale' => true,
                'show_seuils_progression' => true,
                'show_courses' => true,
                'show_course_codes' => true,
                'show_concept_maitre' => true,
                'show_concepts_associes' => false,
                'show_artefacts' => true,
                'show_criteres_passage' => false,
                'show_projet_final' => true,
                'show_limites_ethiques' => true,
                'show_relations_intervoies' => true,
                'show_tags' => false,
            ],
            'dynamic_blocks' => [
                [
                    'id' => 'annonces',
                    'type' => 'announcements',
                    'title' => 'Annonces de la faculté',
                    'source' => [
                        'provider' => 'moodle_forum',
                        'course_idnumber' => 'GJS-HUB',
                        'forum_name' => 'Annonces',
                    ],
                    'limit' => 5,
                    'visibility' => 'public',
                    'empty_state' => 'Aucune annonce publique pour le moment.',
                ],
            ],
            'featured_blocks' => [
                [
                    'type' => 'principle',
                    'title' => 'Lire avant d’agir',
                    'body' => 'La carte précède l’intervention.',
                ],
            ],
            'faq' => [
                [
                    'question' => 'Est-ce un diplôme public accrédité ?',
                    'answer' => 'Non. Le Parchemin est une reconnaissance interne UCKK.',
                ],
            ],
            'contact' => [
                'label' => 'Contact',
                'body' => 'Pour toute question sur cette Voie, consultez les annonces publiques.',
                'email' => '',
                'cta' => [
                    'label' => 'Voir les annonces',
                    'target' => '#annonces',
                ],
            ],
            'governance' => [
                'owner' => 'local_uckk',
                'editorial_status' => 'approved',
                'last_reviewed' => null,
                'review_notes' => 'Internal editorial note that must not be rendered publicly.',
                'public_claims_guardrails' => [
                    'Ne pas présenter les Parchemins UCKK comme diplômes publics accrédités.',
                    'Ne pas afficher de progression, notes ou données privées d’étudiants.',
                    'Ne pas présenter une Voie expérimentale comme officiellement reconnue.',
                ],
            ],
            'cache' => [
                'enabled' => true,
                'ttl_seconds' => 3600,
            ],
            'private_notes' => 'Internal editorial note',
        ];
    }

    /**
     * Sample Atlas Voie.
     *
     * @return array<string, mixed>
     */
    private function sample_atlas_voie(): array {
        return [
            'schema_version' => 'UCKK-ATLAS-0.2-draft',
            'voie_id' => 'voie_grand_jeu_social',
            'code' => 'GJS',
            'nom' => 'Voie du Grand Jeu social',
            'domaine_operatoire' => 'Grand Jeu social',
            'niveau_vise' => 'Puissance opératoire',
            'titre_symbolique' => 'Stratège du Grand Jeu social',
            'parchemin' => [
                'nom' => 'Parchemin de Puissance opératoire — Voie du Grand Jeu social',
            ],
            'statut' => 'Voie fondatrice UCKK',
            'definition_courte' => 'Déchiffrer les systèmes sociaux comme des jeux de règles.',
            'angle_fondamental' => 'Tout système social produit des règles explicites et implicites.',
            'competence_centrale' => 'Cartographier acteurs, règles, enjeux et tensions.',
            'seuils_progression' => ['Lire', 'Modéliser', 'Intervenir'],
            'cours_conceptuels' => [
                [
                    'cours_id' => 'GJS103',
                    'ordre' => 3,
                    'nom' => 'Positions, rôles et masques',
                    'concept_maitre' => [
                        'concept_id' => 'concept_gjs_roles',
                        'nom' => 'Rôles sociaux',
                        'type' => 'concept_maitre',
                        'definition_courte' => 'Identifier les positions jouées dans un système.',
                        'fonction_pedagogique' => 'Distinguer personne, rôle et fonction.',
                    ],
                    'concepts_associes' => [],
                    'artefact_maitrise' => [
                        'type' => 'schéma',
                        'nom' => 'Schéma des rôles',
                        'description' => 'Une représentation des rôles et masques.',
                    ],
                    'criteres_passage' => ['Critère privé de passage'],
                    'relations' => [],
                ],
                [
                    'cours_id' => 'GJS101',
                    'ordre' => 1,
                    'nom' => 'Cartographie du Grand Jeu social',
                    'concept_maitre' => [
                        'concept_id' => 'concept_gjs_cartographie',
                        'nom' => 'Cartographie du Grand Jeu social',
                        'type' => 'concept_maitre',
                        'definition_courte' => 'Lire le terrain social comme une carte de forces.',
                        'fonction_pedagogique' => 'Construire une première carte du système.',
                    ],
                    'concepts_associes' => [
                        [
                            'concept_id' => 'concept_gjs_acteurs',
                            'nom' => 'Acteurs sociaux',
                            'type' => 'concept_associe',
                            'notions_fines' => [],
                        ],
                    ],
                    'artefact_maitrise' => [
                        'type' => 'carte',
                        'nom' => 'Carte initiale du Grand Jeu social',
                        'description' => 'Une carte synthétique des acteurs, règles et enjeux.',
                    ],
                    'criteres_passage' => [
                        'Identifier les principaux acteurs, règles et tensions.',
                    ],
                    'relations' => [],
                    'notes' => 'Private course note',
                    'participants' => ['student@example.test'],
                    'completion_status' => 'private',
                    'grade' => 100,
                ],
                [
                    'cours_id' => 'GJS102',
                    'ordre' => 2,
                    'nom' => 'Règles explicites et règles implicites',
                    'concept_maitre' => [
                        'concept_id' => 'concept_gjs_regles',
                        'nom' => 'Règles du jeu',
                        'type' => 'concept_maitre',
                        'definition_courte' => 'Repérer les règles visibles et invisibles.',
                        'fonction_pedagogique' => 'Identifier ce qui encadre les actions.',
                    ],
                    'concepts_associes' => [],
                    'artefact_maitrise' => [
                        'type' => 'analyse',
                        'nom' => 'Analyse des règles visibles et invisibles',
                        'description' => 'Un document court de distinction des règles.',
                    ],
                    'criteres_passage' => [],
                    'relations' => [],
                ],
            ],
            'projet_final' => [
                'title' => 'Carte stratégique complète du Grand Jeu social',
                'description' => 'Produire une carte opératoire d’un système réel.',
            ],
            'limites_ethiques' => [
                'Ne pas manipuler les acteurs cartographiés.',
            ],
            'relations_intervoies' => [
                [
                    'voie_id' => 'voie_sciences_politiques',
                    'type' => 'complément',
                    'label' => 'Pouvoir et institutions',
                ],
            ],
            'tags' => ['jeu social', 'systèmes'],
            'private_notes' => 'Internal Atlas note',
        ];
    }
}