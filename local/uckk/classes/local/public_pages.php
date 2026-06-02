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
 * Public page setup and content registry for local_uckk.
 *
 * This class owns Moodle page setup and public institutional page definitions.
 * Rendering belongs to local_uckk\output\public_page.
 *
 * @package    local_uckk
 * @copyright  2026 Univers-Cité King Klown
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_uckk\local;

use context;
use context_system;
use moodle_url;

defined('MOODLE_INTERNAL') || die();

/**
 * Public page setup and definition registry.
 *
 * @package local_uckk
 */
final class public_pages {
    /** Component name. */
    private const COMPONENT = 'local_uckk';

    /** Public page slugs. */
    private const KEY_HOME = 'home';
    private const KEY_ABOUT = 'about';
    private const KEY_PROGRAMS = 'programs';
    private const KEY_COURSES = 'courses';
    private const KEY_CHALLENGES = 'challenges';
    private const KEY_ASSEMBLIES = 'assemblies';
    private const KEY_INTEGRITY = 'integrity';
    private const KEY_ARCHIVES = 'archives';
    private const KEY_MEDIATHEQUE = 'mediatheque';
    private const KEY_NEWS = 'news';
    private const KEY_CONTACT = 'contact';

    /**
     * Configure Moodle PAGE for a public UCKK page.
     *
     * @param string $slug Page slug.
     * @param context|null $context Moodle context.
     * @return void
     */
    public static function setup_page(string $slug, ?context $context = null): void {
        global $PAGE;

        $slug = self::clean_slug($slug);
        $context = $context ?? context_system::instance();
        $url = new moodle_url('/local/uckk/' . self::script_for_slug($slug));

        $PAGE->set_context($context);
        $PAGE->set_url($url);
        $PAGE->set_pagelayout('local_uckk_public');
        $PAGE->set_title(self::page_title($slug));
        $PAGE->set_heading(self::site_heading());
        $PAGE->set_cacheable(true);
        $PAGE->requires->css(new moodle_url('/local/uckk/styles.css'));

        self::setup_breadcrumb($slug);
    }

    /**
     * Configure a consistent Moodle breadcrumb for public UCKK pages.
     *
     * @param string $slug Page slug.
     * @return void
     */
    private static function setup_breadcrumb(string $slug): void {
        global $PAGE;

        $rootlabel = self::string_or_fallback('pluginname', 'UCKK core');

        $PAGE->navbar->ignore_active();

        if ($slug === self::KEY_HOME) {
            $PAGE->navbar->add($rootlabel);
            return;
        }

        $PAGE->navbar->add(
            $rootlabel,
            new moodle_url('/local/uckk/index.php')
        );

        $PAGE->navbar->add(self::page_title($slug));
    }

    /**
     * Return the public page definition consumed by local_uckk\output\public_page.
     *
     * @param string $slug Page slug.
     * @return array<string, mixed>
     */
    public static function definition(string $slug): array {
        $slug = self::clean_slug($slug);
        $definitions = self::page_definitions();

        $definition = self::merge_definition(
            self::base_definition($slug),
            $definitions[$slug] ?? []
        );

        if ($slug === self::KEY_PROGRAMS) {
            $definition = self::with_program_cards($definition);
        }

        return $definition;
    }

    /**
     * Base definition shared by all public pages.
     *
     * @param string $slug Page slug.
     * @return array<string, mixed>
     */
    private static function base_definition(string $slug): array {
        return [
            'slug' => $slug,
            'eyebrow' => '',
            'title' => self::page_title($slug),
            'subtitle' => '',
            'summary' => '',
            'boundarynotice' => 'UCKK-Moodle présente une cité-école expérimentale. Les reconnaissances UCKK ne doivent pas être présentées comme des diplômes publics accrédités.',

            /*
             * Public visual contract.
             *
             * These keys are consumed by local_uckk\output\public_page and
             * local_uckk/templates/public_page.mustache. They are deliberately
             * data-only: layout and typography choices stay here, while HTML
             * structure and CSS remain outside this registry.
             */
            'layout' => 'wide',
            'navigationlayout' => 'singleline',
            'typography' => 'institutional',
            'visualstyle' => 'civic-encyclopedic-retrofuturism',
            'fontstrategy' => 'libre-baskerville-primary-eb-garamond-accent',

            'navigation' => self::default_navigation(),
            'quicklinks' => [],
            'sections' => [],
            'cards' => [],
            'cardsheading' => 'Repères publics',
            'notices' => [],
            'metadata' => [],
            'cta' => [],
            'has_mediatheque_explorer' => false,
            'mediatheque_explorer_id' => '',
            'mediatheque_initial_state' => [],
            'mediatheque' => [],
        ];
    }

    /**
     * Central public page content registry.
     *
     * @return array<string, array<string, mixed>>
     */
    private static function page_definitions(): array {
        return [
            self::KEY_HOME => [
                'layout' => 'wide',
                'typography' => 'display',
                'eyebrow' => 'Cité-école expérimentale',
                'title' => 'Univers-Cité King Klown',
                'subtitle' => 'Un campus Moodle pour apprendre, prouver, délibérer et archiver.',
                'summary' => 'UCKK-Moodle organise les parcours, cours, défis, assemblées, traces et archives publiques de l’Univers-Cité King Klown.',
                'quicklinks' => [
                    [
                        'label' => 'Explorer les voies',
                        'description' => 'Voir les parcours publics et les structures pédagogiques UCKK.',
                        'url' => '/local/uckk/programs.php',
                    ],
                    [
                        'label' => 'Voir les cours',
                        'description' => 'Accéder aux espaces d’apprentissage disponibles.',
                        'url' => '/local/uckk/courses.php',
                    ],
                    [
                        'label' => 'Consulter les archives',
                        'description' => 'Lire les traces publiques et la mémoire institutionnelle.',
                        'url' => '/local/uckk/archives.php',
                    ],
                ],
                'sections' => [
                    [
                        'type' => 'orientation',
                        'eyebrow' => 'Orientation',
                        'title' => 'Une cité-école structurée',
                        'body' => 'Le campus relie les apprentissages, les défis, les preuves, les assemblées et les archives dans un même espace Moodle.',
                        'items' => [
                            'Former par des parcours et des cours.',
                            'Mettre les apprentissages à l’épreuve par des défis.',
                            'Documenter les traces utiles à la mémoire collective.',
                            'Encadrer les décisions publiques par des assemblées.',
                        ],
                    ],
                    [
                        'type' => 'boundary',
                        'eyebrow' => 'Limite publique',
                        'title' => 'Ce que la page publique montre',
                        'body' => 'Les pages publiques donnent une vue institutionnelle. Elles ne remplacent pas les espaces privés, les rôles Moodle, les permissions ou les dossiers internes.',
                    ],
                ],
                'cardsheading' => 'Portes d’entrée',
                'cards' => [
                    [
                        'title' => 'Voies',
                        'body' => 'Les chemins de formation et d’engagement proposés dans la cité-école.',
                        'url' => '/local/uckk/programs.php',
                        'actionlabel' => 'Voir les voies',
                        'type' => 'programs',
                    ],
                    [
                        'title' => 'Défis',
                        'body' => 'Les mises à l’épreuve pédagogiques et symboliques qui produisent des traces.',
                        'url' => '/local/uckk/challenges.php',
                        'actionlabel' => 'Voir les défis',
                        'type' => 'challenges',
                    ],
                    [
                        'title' => 'Assemblées',
                        'body' => 'Les espaces de délibération, d’orientation et de décision collective.',
                        'url' => '/local/uckk/assemblies.php',
                        'actionlabel' => 'Voir les assemblées',
                        'type' => 'assemblies',
                    ],
                ],
                'notices' => [
                    [
                        'title' => 'Statut expérimental',
                        'body' => 'UCKK-Moodle est un campus expérimental et pédagogique. Les contenus publics doivent rester clairs sur leur statut.',
                        'type' => 'institutional',
                    ],
                ],
                'metadata' => [
                    ['label' => 'Composant Moodle', 'value' => 'local_uckk'],
                    ['label' => 'Type de page', 'value' => 'Page publique institutionnelle'],
                ],
                'cta' => [
                    'title' => 'Entrer dans le campus',
                    'body' => 'Commencer par les cours, les voies ou les archives publiques.',
                    'url' => '/local/uckk/courses.php',
                    'label' => 'Voir les cours',
                ],
            ],

            self::KEY_ABOUT => [
                'layout' => 'standard',
                'typography' => 'editorial',
                'eyebrow' => 'Clarifier UCKK',
                'title' => 'À propos',
                'subtitle' => 'UCKK-Moodle adapte Moodle comme campus pédagogique de l’Univers-Cité King Klown.',
                'summary' => 'Cette page présente le rôle du campus, ses limites publiques et son organisation générale.',
                'sections' => [
                    [
                        'title' => 'Ce qu’est UCKK-Moodle',
                        'body' => 'UCKK-Moodle est une adaptation de Moodle pour soutenir une cité-école : parcours, apprentissages, défis, assemblées, preuves, archives et gouvernance pédagogique.',
                    ],
                    [
                        'title' => 'Ce que ce n’est pas',
                        'body' => 'Le campus public ne doit pas présenter les reconnaissances UCKK comme des diplômes publics accrédités. Les espaces privés et les permissions Moodle restent nécessaires pour les opérations internes.',
                    ],
                    [
                        'title' => 'Organisation',
                        'body' => 'Les contenus publics sont séparés du rendu visuel. Les définitions de page vivent dans public_pages.php, le rendu dans public_page.php, la structure HTML dans Mustache et l’apparence dans CSS.',
                    ],
                ],
                'cardsheading' => 'Repères institutionnels',
                'cards' => [
                    [
                        'title' => 'Campus',
                        'body' => 'Un espace Moodle pour organiser la formation et les traces.',
                        'type' => 'campus',
                    ],
                    [
                        'title' => 'Canon',
                        'body' => 'Un cadre de vocabulaire, de limites et de cohérence institutionnelle.',
                        'type' => 'canon',
                    ],
                    [
                        'title' => 'Archives',
                        'body' => 'Une mémoire structurée des traces publiques et des corrections.',
                        'url' => '/local/uckk/archives.php',
                        'actionlabel' => 'Consulter',
                        'type' => 'archives',
                    ],
                ],
                'notices' => [
                    [
                        'body' => 'Cette page est informative. Les règles de permissions, d’inscription et de validation restent gérées par Moodle et les composants UCKK appropriés.',
                        'type' => 'light',
                    ],
                ],
            ],

            self::KEY_PROGRAMS => [
                'layout' => 'wide',
                'typography' => 'institutional',
                'eyebrow' => 'Former par les voies',
                'title' => 'Voies UCKK',
                'subtitle' => 'Parcours publics, orientations pédagogiques et structures d’engagement.',
                'summary' => 'Les voies organisent les intentions de formation, les trajectoires et les grands ensembles d’apprentissage.',
                'sections' => [
                    [
                        'type' => 'role',
                        'title' => 'Rôle des voies',
                        'body' => 'Une voie aide à situer un parcours d’apprentissage dans la cité-école. Elle peut relier des cours, des compétences, des défis, des preuves et des archives.',
                    ],
                    [
                        'type' => 'registry',
                        'eyebrow' => 'Répertoire public',
                        'title' => 'Voies et programmes actifs',
                        'body' => 'Les cartes ci-dessous sont générées depuis le registre Moodle UCKK des programmes actifs. Les éléments en brouillon, cachés ou archivés ne sont pas affichés publiquement.',
                    ],
                ],
                'cards' => [],
                'notices' => [
                    [
                        'body' => 'Les voies décrivent des structures pédagogiques internes à UCKK. Elles ne constituent pas des diplômes publics accrédités.',
                        'type' => 'institutional',
                    ],
                ],
                'metadata' => [
                    ['label' => 'Source', 'value' => 'Registre local_uckk_program'],
                    ['label' => 'Filtre public', 'value' => 'status = active'],
                ],
                'cta' => [
                    'title' => 'Explorer les cours',
                    'body' => 'Les catégories Moodle liées aux voies regroupent les cours et unités d’apprentissage disponibles.',
                    'url' => '/local/uckk/courses.php',
                    'label' => 'Voir les cours',
                ],
            ],

            self::KEY_COURSES => [
                'layout' => 'standard',
                'typography' => 'institutional',
                'eyebrow' => 'Entrer dans les apprentissages',
                'title' => 'Cours UCKK',
                'subtitle' => 'Espaces Moodle publics ou accessibles selon les rôles.',
                'summary' => 'Les cours contiennent les activités, ressources et évaluations pédagogiques du campus.',
                'sections' => [
                    [
                        'title' => 'Fonction des cours',
                        'body' => 'Les cours structurent les apprentissages concrets. Ils peuvent être liés aux voies, défis, compétences, badges et preuves.',
                    ],
                    [
                        'title' => 'Accès',
                        'body' => 'Certains cours peuvent être visibles publiquement. D’autres demandent une connexion, une inscription ou un rôle Moodle.',
                    ],
                ],
                'cardsheading' => 'Accès aux cours',
                'cards' => [
                    [
                        'title' => 'Explorer',
                        'body' => 'Voir les cours disponibles dans le campus.',
                        'url' => '/course/index.php',
                        'actionlabel' => 'Catalogue Moodle',
                        'type' => 'course',
                    ],
                ],
            ],

            self::KEY_CHALLENGES => [
                'layout' => 'standard',
                'typography' => 'institutional',
                'eyebrow' => 'Mettre la lucidité à l’épreuve',
                'title' => 'Défis UCKK',
                'subtitle' => 'Épreuves pédagogiques, traces, productions et révisions.',
                'summary' => 'Les défis servent à produire des traces, vérifier des apprentissages et rendre visibles des efforts structurés.',
                'sections' => [
                    [
                        'title' => 'Pourquoi des défis',
                        'body' => 'Un défi transforme une intention d’apprentissage en action observable. Il peut demander une production, une preuve, une réflexion ou une validation.',
                    ],
                    [
                        'title' => 'Cycle général',
                        'body' => 'Un défi peut être proposé, accepté, réalisé, soumis, révisé, validé, contesté ou archivé selon les règles du contexte.',
                        'items' => [
                            'Comprendre la consigne.',
                            'Produire une trace.',
                            'Soumettre une preuve.',
                            'Recevoir une révision.',
                            'Archiver ce qui doit rester vérifiable.',
                        ],
                    ],
                ],
                'cardsheading' => 'Repères pour les défis',
                'cards' => [
                    [
                        'title' => 'Défis publics',
                        'body' => 'Présentation des défis visibles sans exposer les données privées.',
                        'type' => 'public',
                    ],
                    [
                        'title' => 'Révision',
                        'body' => 'Les validations et contestations suivent les permissions et les rôles Moodle.',
                        'type' => 'review',
                    ],
                ],
                'notices' => [
                    [
                        'title' => 'Protection des preuves',
                        'body' => 'Les preuves privées ne doivent pas être exposées dans les pages publiques.',
                        'type' => 'warning',
                    ],
                ],
            ],

            self::KEY_ASSEMBLIES => [
                'layout' => 'standard',
                'typography' => 'institutional',
                'eyebrow' => 'Délibérer, vérifier, orienter',
                'title' => 'Assemblées UCKK',
                'subtitle' => 'Espaces de discussion, décision, orientation et mémoire collective.',
                'summary' => 'Les assemblées organisent les moments où la cité-école discute, arbitre, documente et rend certaines décisions traçables.',
                'sections' => [
                    [
                        'title' => 'Rôle des assemblées',
                        'body' => 'Une assemblée peut soutenir une délibération, une décision, une orientation pédagogique ou une vérification collective.',
                    ],
                    [
                        'title' => 'Traces',
                        'body' => 'Les traces publiques doivent rester sobres, vérifiables et compatibles avec les règles de confidentialité.',
                    ],
                ],
                'cardsheading' => 'Repères d’assemblée',
                'cards' => [
                    [
                        'title' => 'Délibération',
                        'body' => 'Construire une décision à partir de contributions structurées.',
                        'type' => 'deliberation',
                    ],
                    [
                        'title' => 'Décision',
                        'body' => 'Documenter les décisions sans exposer les informations privées.',
                        'type' => 'decision',
                    ],
                    [
                        'title' => 'Archive',
                        'body' => 'Conserver les traces utiles à la mémoire institutionnelle.',
                        'url' => '/local/uckk/archives.php',
                        'actionlabel' => 'Voir les archives',
                        'type' => 'archive',
                    ],
                ],
                'notices' => [
                    [
                        'body' => 'Les assemblées publiques ne remplacent pas les espaces internes de gouvernance ou les permissions Moodle.',
                        'type' => 'institutional',
                    ],
                ],
            ],

            self::KEY_INTEGRITY => [
                'layout' => 'standard',
                'typography' => 'institutional',
                'eyebrow' => 'Protéger la preuve et la dignité',
                'title' => 'Intégrité UCKK',
                'subtitle' => 'Règles publiques de prudence, preuve, visibilité et responsabilité.',
                'summary' => 'L’intégrité protège les personnes, les preuves, les limites publiques et la cohérence institutionnelle.',
                'sections' => [
                    [
                        'title' => 'Principe',
                        'body' => 'Une trace utile ne doit pas devenir une exposition abusive. Les données privées, sensibles ou non nécessaires restent protégées.',
                    ],
                    [
                        'title' => 'Visibilité',
                        'body' => 'Ce qui est visible publiquement doit être intentionnel, utile, vérifiable et compatible avec les permissions Moodle.',
                    ],
                    [
                        'title' => 'Corrections',
                        'body' => 'Quand une erreur est trouvée, elle doit être corrigée sans effacer la possibilité de comprendre ce qui a changé lorsque la trace est institutionnellement utile.',
                    ],
                ],
                'cardsheading' => 'Repères d’intégrité',
                'cards' => [
                    [
                        'title' => 'Confidentialité',
                        'body' => 'Ne pas exposer les informations privées ou sensibles.',
                        'type' => 'privacy',
                    ],
                    [
                        'title' => 'Provenance',
                        'body' => 'Conserver l’origine des traces importantes.',
                        'type' => 'provenance',
                    ],
                    [
                        'title' => 'Limites',
                        'body' => 'Éviter les promesses ou statuts non autorisés.',
                        'type' => 'boundary',
                    ],
                ],
                'notices' => [
                    [
                        'title' => 'Règle publique',
                        'body' => 'Les pages publiques doivent éviter les données personnelles, les dossiers privés et les statuts non validés.',
                        'type' => 'integrity',
                    ],
                ],
            ],

            self::KEY_ARCHIVES => [
                'layout' => 'standard',
                'typography' => 'institutional',
                'eyebrow' => 'Conserver les traces publiques',
                'title' => 'Archives UCKK',
                'subtitle' => 'Mémoire institutionnelle, preuves publiques, décisions et corrections.',
                'summary' => 'Les archives conservent les traces publiques utiles à la compréhension du campus, de ses décisions et de ses transformations.',
                'sections' => [
                    [
                        'title' => 'Ce que les archives conservent',
                        'body' => 'Les archives peuvent conserver des versions publiques du canon, des décisions, des modèles, des corrections, des appels, des travaux publics et des traces institutionnelles.',
                    ],
                    [
                        'title' => 'Ce que les archives ne doivent pas exposer',
                        'body' => 'Elles ne doivent pas publier de données personnelles, dossiers sensibles, preuves privées ou informations réservées à des rôles Moodle.',
                    ],
                    [
                        'title' => 'Usage',
                        'body' => 'Les archives servent à comprendre, vérifier, relier et corriger. Elles ne sont pas un simple dépôt de fichiers.',
                    ],
                ],
                'cardsheading' => 'Repères d’archive',
                'cards' => [
                    [
                        'title' => 'Mémoire',
                        'body' => 'Conserver les traces publiques importantes.',
                        'type' => 'memory',
                    ],
                    [
                        'title' => 'Preuves',
                        'body' => 'Relier les preuves publiques à leur contexte.',
                        'type' => 'evidence',
                    ],
                    [
                        'title' => 'Corrections',
                        'body' => 'Rendre les changements importants compréhensibles.',
                        'type' => 'correction',
                    ],
                ],
                'notices' => [
                    [
                        'title' => 'Archives publiques',
                        'body' => 'Les contenus d’archives visibles ici doivent respecter la confidentialité et les limites institutionnelles.',
                        'type' => 'warning',
                    ],
                ],
            ],

            self::KEY_MEDIATHEQUE => [
                'layout' => 'wide',
                'typography' => 'institutional',
                'eyebrow' => 'Archives publiques',
                'title' => 'Médiathèque UCKK',
                'subtitle' => 'Explorer les médias, collections, œuvres externes et passages documentés.',
                'summary' => 'La Médiathèque donne accès aux médias publics, collections, œuvres externes et passages documentés de l’archive UCKK, avec filtrage par droits, avis de contenu et protocoles culturels.',
                'cardsheading' => 'Explorer la Médiathèque',
                'has_mediatheque_explorer' => true,
                'mediatheque_explorer_id' => 'local-uckk-mediatheque-explorer',
                'mediatheque_initial_state' => [
                    'rootId' => 'local-uckk-mediatheque-explorer',
                    'service' => 'mod_uckkarchive_search_mediatheque',
                    'cmid' => 0,
                    'archiveid' => 0,
                    'query' => '',
                    'filters' => [
                        'type' => 'all',
                        'mediatype' => 'all',
                        'collection' => '',
                        'tag' => '',
                        'source' => '',
                        'advisory' => 'all',
                        'cultural' => 'all',
                        'audience' => 'all',
                        'lang' => '',
                        'validation' => 'all',
                    ],
                    'page' => 1,
                    'perpage' => 12,
                    'sort' => 'relevance',
                    'sitewide' => true,
                ],
                'mediatheque' => [
                    'explorer' => [
                        'key' => 'mediatheque_explorer',
                        'title' => 'Explorateur Médiathèque',
                        'service' => 'mod_uckkarchive_search_mediatheque',
                        'surface' => 'local_uckk',
                        'dataowner' => 'mod_uckkarchive',
                    ],
                ],
                'quicklinks' => [
                    [
                        'label' => 'Rechercher',
                        'description' => 'Chercher dans les médias publics et les collections accessibles.',
                        'url' => '/local/uckk/mediatheque.php',
                    ],
                    [
                        'label' => 'Archives',
                        'description' => 'Revenir aux repères généraux d’archive publique.',
                        'url' => '/local/uckk/archives.php',
                    ],
                ],
                'sections' => [
                    [
                        'type' => 'orientation',
                        'eyebrow' => 'Exploration',
                        'title' => 'Naviguer dans les médias publics',
                        'body' => 'L’explorateur permet de chercher, filtrer et ouvrir les fiches publiques sans exposer les fichiers originaux ni les métadonnées privées.',
                        'items' => [
                            'Recherche par texte, format, source, collection et mot-clé.',
                            'Affichage des médias publics seulement.',
                            'Respect des droits, avis de contenu et protocoles culturels.',
                        ],
                    ],
                    [
                        'type' => 'boundary',
                        'eyebrow' => 'Responsabilité',
                        'title' => 'Ce que la Médiathèque ne publie pas',
                        'body' => 'Les médias restreints, brouillons, notes internes, protocoles culturels privés, fichiers originaux non autorisés et métadonnées sensibles restent contrôlés par mod_uckkarchive.',
                    ],
                ],
                'cards' => [
                    [
                        'title' => 'Médias',
                        'body' => 'Voir les vidéos, sons, images, documents et livres publics filtrés.',
                        'type' => 'media',
                    ],
                    [
                        'title' => 'Collections',
                        'body' => 'Parcourir les regroupements publics sans dupliquer le moteur média interne.',
                        'type' => 'collection',
                    ],
                    [
                        'title' => 'Passages documentés',
                        'body' => 'Pointer vers des moments, pages ou segments quand ils peuvent être exposés publiquement.',
                        'type' => 'marker',
                    ],
                ],
                'notices' => [
                    [
                        'title' => 'Données et politiques',
                        'body' => 'La page publique appartient à local_uckk, mais les médias, droits, avis de contenu et protocoles culturels sont filtrés par mod_uckkarchive.',
                        'type' => 'institutional',
                    ],
                    [
                        'title' => 'Accès responsable',
                        'body' => 'Certains contenus peuvent être masqués ou résumés selon les droits, les avis de contenu et les protocoles culturels.',
                        'type' => 'light',
                    ],
                ],
                'metadata' => [
                    ['label' => 'Surface publique', 'value' => 'local_uckk'],
                    ['label' => 'Données et politiques', 'value' => 'mod_uckkarchive'],
                    ['label' => 'Service AJAX', 'value' => 'mod_uckkarchive_search_mediatheque'],
                    ['label' => 'Portée par défaut', 'value' => 'site-wide public'],
                ],
                'cta' => [
                    'title' => 'Explorer les médias publics',
                    'body' => 'Lance une recherche ou filtre la Médiathèque selon le format, la source, les collections et les avis publics.',
                    'url' => '/local/uckk/mediatheque.php',
                    'label' => 'Ouvrir la Médiathèque',
                ],
            ],

            self::KEY_NEWS => [
                'layout' => 'standard',
                'typography' => 'institutional',
                'eyebrow' => 'Nouvelles et appels',
                'title' => 'Actualités UCKK',
                'subtitle' => 'Informations publiques, appels, jalons et annonces institutionnelles.',
                'summary' => 'Les actualités présentent les informations publiques utiles au suivi de la cité-école.',
                'sections' => [
                    [
                        'title' => 'Fonction',
                        'body' => 'Les actualités servent à annoncer des jalons, appels, corrections, publications ou événements publics.',
                    ],
                    [
                        'title' => 'Sobriété',
                        'body' => 'Une actualité publique doit rester claire, datée, vérifiable et limitée à ce qui peut être rendu public.',
                    ],
                ],
                'cardsheading' => 'Repères d’actualité',
                'cards' => [
                    [
                        'title' => 'Appels',
                        'body' => 'Invitations publiques à participer, contribuer ou consulter.',
                        'type' => 'calls',
                    ],
                    [
                        'title' => 'Jalons',
                        'body' => 'Moments importants du développement du campus.',
                        'type' => 'milestones',
                    ],
                    [
                        'title' => 'Corrections',
                        'body' => 'Informations sur les ajustements publics importants.',
                        'type' => 'corrections',
                    ],
                ],
                'notices' => [
                    [
                        'body' => 'Les actualités publiques ne doivent pas exposer de données personnelles ou de décisions internes non publiées.',
                        'type' => 'light',
                    ],
                ],
            ],

            self::KEY_CONTACT => [
                'layout' => 'standard',
                'typography' => 'editorial',
                'eyebrow' => 'Entrer en relation',
                'title' => 'Contact',
                'subtitle' => 'Orientation publique pour contacter ou rejoindre le campus UCKK.',
                'summary' => 'Cette page indique les voies générales de contact et rappelle les limites des demandes publiques.',
                'sections' => [
                    [
                        'title' => 'Demandes générales',
                        'body' => 'Les demandes publiques peuvent concerner l’orientation, la compréhension du campus, les voies, les cours ou les archives.',
                    ],
                    [
                        'title' => 'Demandes privées',
                        'body' => 'Les questions liées à un dossier personnel, une preuve, une inscription ou une décision doivent passer par les espaces Moodle appropriés.',
                    ],
                ],
                'cardsheading' => 'Repères de contact',
                'cards' => [
                    [
                        'title' => 'Cours',
                        'body' => 'Commencer par consulter les cours publics ou accessibles.',
                        'url' => '/local/uckk/courses.php',
                        'actionlabel' => 'Voir les cours',
                        'type' => 'courses',
                    ],
                    [
                        'title' => 'Archives',
                        'body' => 'Consulter les traces publiques disponibles.',
                        'url' => '/local/uckk/archives.php',
                        'actionlabel' => 'Voir les archives',
                        'type' => 'archives',
                    ],
                ],
                'cta' => [
                    'title' => 'Besoin d’orientation?',
                    'body' => 'Commence par les pages publiques, puis connecte-toi à Moodle si ta demande concerne un espace ou un rôle spécifique.',
                    'url' => '/login/index.php',
                    'label' => 'Se connecter',
                ],
            ],
        ];
    }

    /**
     * Add active program cards to the programs page definition.
     *
     * Program cards belong to the public registry section only. Keeping the same
     * cards at page level also makes public_page.mustache render the generic
     * "Fonctions UCKK" card block, which duplicates the program grid.
     *
     * @param array<string, mixed> $definition Page definition.
     * @return array<string, mixed>
     */
    private static function with_program_cards(array $definition): array {
        $cards = self::program_cards('active');

        // Program cards belong inside the public registry section only.
        // Keeping them at page level creates a second generic card section.
        $definition['cards'] = [];
        $definition['cardsheading'] = 'Repères publics';

        // Section-level cards, rendered inside the "Répertoire public" section.
        if (!isset($definition['sections']) || !is_array($definition['sections'])) {
            $definition['sections'] = [];
        }

        $attached = false;

        foreach ($definition['sections'] as $index => $section) {
            if (!is_array($section)) {
                continue;
            }

            $title = (string)($section['title'] ?? '');
            $eyebrow = (string)($section['eyebrow'] ?? '');

            if ($title === 'Répertoire public' || $title === 'Voies et programmes actifs' || $eyebrow === 'Répertoire public') {
                $definition['sections'][$index]['cards'] = $cards;
                $attached = true;
                break;
            }
        }

        if (!$attached) {
            $definition['sections'][] = [
                'type' => 'registry',
                'eyebrow' => 'Répertoire public',
                'title' => 'Voies et programmes actifs',
                'body' => 'Ces cartes proviennent du registre UCKK des programmes actifs. Les éléments en brouillon ou non publiés ne sont pas affichés ici.',
                'cards' => $cards,
            ];
        }

        if (!isset($definition['metadata']) || !is_array($definition['metadata'])) {
            $definition['metadata'] = [];
        }

        $definition['metadata'][] = [
            'label' => 'Programmes actifs affichés',
            'value' => (string)count($cards),
        ];

        if (empty($cards)) {
            if (!isset($definition['notices']) || !is_array($definition['notices'])) {
                $definition['notices'] = [];
            }

            $definition['notices'][] = [
                'title' => 'Aucun programme actif',
                'body' => 'Aucun programme actif n’est actuellement disponible dans le registre public UCKK.',
                'type' => 'warning',
            ];
        }

        return $definition;
    }

    /**
     * Build public cards from active UCKK program records.
     *
     * @param string $status Program status to display.
     * @return array<int, array<string, mixed>>
     */
    private static function program_cards(string $status): array {
        global $CFG, $DB;

        if (!isset($CFG) || !isset($DB)) {
            return [];
        }

        if (!class_exists('xmldb_table')) {
            require_once($CFG->libdir . '/xmldb/xmldb_object.php');
        }

        if (!$DB->get_manager()->table_exists(new \xmldb_table('local_uckk_program'))) {
            return [];
        }

        $records = $DB->get_records_sql("
            SELECT
                p.id,
                p.shortname,
                p.fullname,
                p.programtype,
                p.status,
                p.visibility,
                p.sortorder,
                p.categoryid,
                c.name AS categoryname,
                c.visible AS categoryvisible
              FROM {local_uckk_program} p
         LEFT JOIN {course_categories} c ON c.id = p.categoryid
             WHERE p.status = :status
          ORDER BY p.programtype, p.sortorder, p.fullname
        ", [
            'status' => $status,
        ]);

        $cards = [];

        foreach ($records as $record) {
            $shortname = trim((string)($record->shortname ?? ''));
            $fullname = trim((string)($record->fullname ?? ''));
            $programtype = trim((string)($record->programtype ?? ''));
            $categoryname = trim((string)($record->categoryname ?? ''));
            $categoryvisible = (int)($record->categoryvisible ?? 0);
            $categoryid = (int)($record->categoryid ?? 0);

            $typelabel = self::program_type_label($programtype);
            $title = $fullname !== '' ? $fullname : $shortname;

            if ($title === '') {
                continue;
            }

            $bodyparts = [];

            if ($shortname !== '') {
                $bodyparts[] = 'Code : ' . $shortname . '.';
            }

            if ($typelabel !== '') {
                $bodyparts[] = 'Type : ' . $typelabel . '.';
            }

            if ($categoryname !== '') {
                $bodyparts[] = 'Catégorie Moodle : ' . $categoryname . '.';
            } else {
                $bodyparts[] = 'Aucune catégorie Moodle liée.';
            }

            $url = '';
            $actionlabel = '';

            if ($categoryid > 0 && $categoryvisible === 1) {
                $url = (new moodle_url('/course/index.php', ['categoryid' => $categoryid]))->out(false);
                $actionlabel = 'Voir les cours';
            }

            $cards[] = [
                'eyebrow' => '',
                'title' => $title,
                'body' => implode(' ', $bodyparts),
                'url' => $url,
                'actionlabel' => $actionlabel,
                'type' => self::clean_modifier($programtype),
            ];
        }

        return $cards;
    }

    /**
     * Human label for a program type.
     *
     * @param string $programtype Program type.
     * @return string
     */
    private static function program_type_label(string $programtype): string {
        $labels = [
            'tronc_commun' => 'Tronc commun',
            'voie_uckk' => 'Voie UCKK',
            'voie_secondaire' => 'Voie secondaire',
            'baccalaureat' => 'Baccalauréat',
            'baccalauréat' => 'Baccalauréat',
            'mineure' => 'Mineure',
            'seminaire' => 'Séminaire',
            'séminaire' => 'Séminaire',
            'laboratoire' => 'Laboratoire',
            'lab' => 'Laboratoire',
            'atelier' => 'Atelier',
        ];

        if (isset($labels[$programtype])) {
            return $labels[$programtype];
        }

        $label = trim(str_replace('_', ' ', $programtype));

        return $label !== '' ? ucfirst($label) : '';
    }

    /**
     * Default public navigation.
     *
     * @return array<int, array<string, mixed>>
     */
    private static function default_navigation(): array {
        return [
            ['key' => self::KEY_HOME, 'label' => 'Accueil', 'url' => '/local/uckk/index.php'],
            ['key' => self::KEY_ABOUT, 'label' => 'À propos', 'url' => '/local/uckk/about.php'],
            ['key' => self::KEY_PROGRAMS, 'label' => 'Voies', 'url' => '/local/uckk/programs.php'],
            ['key' => self::KEY_COURSES, 'label' => 'Cours', 'url' => '/local/uckk/courses.php'],
            ['key' => self::KEY_CHALLENGES, 'label' => 'Défis', 'url' => '/local/uckk/challenges.php'],
            ['key' => self::KEY_ASSEMBLIES, 'label' => 'Assemblées', 'url' => '/local/uckk/assemblies.php'],
            ['key' => self::KEY_INTEGRITY, 'label' => 'Intégrité', 'url' => '/local/uckk/integrity.php'],
            ['key' => self::KEY_ARCHIVES, 'label' => 'Archives', 'url' => '/local/uckk/archives.php'],
            ['key' => self::KEY_MEDIATHEQUE, 'label' => 'Médiathèque', 'url' => '/local/uckk/mediatheque.php'],
            ['key' => self::KEY_NEWS, 'label' => 'Actualités', 'url' => '/local/uckk/news.php'],
            ['key' => self::KEY_CONTACT, 'label' => 'Contact', 'url' => '/local/uckk/contact.php'],
        ];
    }

    /**
     * Merge page definitions while replacing list arrays instead of corrupting them.
     *
     * @param array<string, mixed> $base Base definition.
     * @param array<string, mixed> $overrides Overrides.
     * @return array<string, mixed>
     */
    private static function merge_definition(array $base, array $overrides): array {
        foreach ($overrides as $key => $value) {
            if (
                is_array($value)
                && isset($base[$key])
                && is_array($base[$key])
                && !array_is_list($value)
                && !array_is_list($base[$key])
            ) {
                $base[$key] = self::merge_definition($base[$key], $value);
                continue;
            }

            $base[$key] = $value;
        }

        return $base;
    }

    /**
     * Clean a public page slug.
     *
     * @param string $slug Raw slug.
     * @return string
     */
    private static function clean_slug(string $slug): string {
        $slug = strtolower(trim($slug));
        $slug = preg_replace('/[^a-z0-9_]+/', '_', $slug) ?? '';

        $allowed = [
            self::KEY_HOME,
            self::KEY_ABOUT,
            self::KEY_PROGRAMS,
            self::KEY_COURSES,
            self::KEY_CHALLENGES,
            self::KEY_ASSEMBLIES,
            self::KEY_INTEGRITY,
            self::KEY_ARCHIVES,
            self::KEY_MEDIATHEQUE,
            self::KEY_NEWS,
            self::KEY_CONTACT,
        ];

        if (!in_array($slug, $allowed, true)) {
            return self::KEY_HOME;
        }

        return $slug;
    }

    /**
     * Clean CSS modifier value.
     *
     * @param mixed $modifier Modifier.
     * @return string
     */
    private static function clean_modifier($modifier): string {
        if (!is_scalar($modifier)) {
            return '';
        }

        $modifier = strtolower(trim((string)$modifier));
        $modifier = preg_replace('/[^a-z0-9_-]+/', '-', $modifier) ?? '';
        $modifier = trim($modifier, '-');

        return $modifier;
    }

    /**
     * Convert page slug to public controller filename.
     *
     * @param string $slug Page slug.
     * @return string
     */
    private static function script_for_slug(string $slug): string {
        if ($slug === self::KEY_HOME) {
            return 'index.php';
        }

        return $slug . '.php';
    }

    /**
     * Resolve public page title.
     *
     * @param string $slug Page slug.
     * @return string
     */
    private static function page_title(string $slug): string {
        $fallbacks = [
            self::KEY_HOME => 'UCKK',
            self::KEY_ABOUT => 'À propos',
            self::KEY_PROGRAMS => 'Voies UCKK',
            self::KEY_COURSES => 'Cours UCKK',
            self::KEY_CHALLENGES => 'Défis UCKK',
            self::KEY_ASSEMBLIES => 'Assemblées UCKK',
            self::KEY_INTEGRITY => 'Intégrité UCKK',
            self::KEY_ARCHIVES => 'Archives UCKK',
            self::KEY_MEDIATHEQUE => 'Médiathèque UCKK',
            self::KEY_NEWS => 'Actualités UCKK',
            self::KEY_CONTACT => 'Contact UCKK',
        ];

        $keys = [
            self::KEY_HOME => 'public_home_title',
            self::KEY_ABOUT => 'public_about_title',
            self::KEY_PROGRAMS => 'public_programs_title',
            self::KEY_COURSES => 'public_courses_title',
            self::KEY_CHALLENGES => 'public_challenges_title',
            self::KEY_ASSEMBLIES => 'public_assemblies_title',
            self::KEY_INTEGRITY => 'public_integrity_title',
            self::KEY_ARCHIVES => 'public_archives_title',
            self::KEY_MEDIATHEQUE => 'public_mediatheque_title',
            self::KEY_NEWS => 'public_news_title',
            self::KEY_CONTACT => 'public_contact_title',
        ];

        $key = $keys[$slug] ?? 'pluginname';
        $fallback = $fallbacks[$slug] ?? 'UCKK';

        return self::string_or_fallback($key, $fallback);
    }

    /**
     * Resolve site heading.
     *
     * @return string
     */
    private static function site_heading(): string {
        return self::string_or_fallback('uckkfullname', 'Univers-Cité King Klown');
    }

    /**
     * Safe get_string wrapper.
     *
     * @param string $identifier String identifier.
     * @param string $fallback Fallback value.
     * @return string
     */
    private static function string_or_fallback(string $identifier, string $fallback): string {
        if (get_string_manager()->string_exists($identifier, self::COMPONENT)) {
            return get_string($identifier, self::COMPONENT);
        }

        return $fallback;
    }
}

