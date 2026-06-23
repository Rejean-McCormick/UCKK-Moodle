<?php
// This file is part of UCKK-Moodle.
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.
//
// UCKK-Moodle provides the technical Moodle implementation for the
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
            'boundarynotice' => 'L’UCKK est une bibliothèque publique vivante et un établissement virtuel de puissance opératoire consacré à la diffusion du savoir, à l’apprentissage ouvert et à la lecture du Grand Jeu social.',

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

            /*
             * Page-specific optional blocks.
             *
             * These keys stay stable even when a page does not use the block.
             * The renderer can therefore export a predictable Mustache context.
             */
            'has_home_feature' => false,
            'home_feature' => [],

            /*
             * Public courses explorer.
             *
             * The static page registry defines the page-level contract only.
             * The live course list, filters and counts are injected by courses.php.
             */
            'has_course_explorer' => false,
            'course_explorer' => [],

            /*
             * Public Médiathèque explorer.
             *
             * local_uckk owns only the public surface. Media search, permissions,
             * access filters and media ownership stay in mod_uckkarchive.
             */
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

                'eyebrow' => 'Bibliothèque publique vivante',
                'title' => 'Univers-Cité King Klown',
                'subtitle' => 'Comprendre le jeu. Jouer avec lucidité. Changer les règles.',
                'summary' => 'L’Univers-Cité King Klown est un établissement virtuel de puissance opératoire consacré au Grand Jeu social. Elle ouvre une bibliothèque publique, des Voies, des cours, des Défis, des Assemblées, une Médiathèque et un Registraire pour diffuser le savoir dans un cadre d’apprentissage familier, modernisé et accessible.',

                'quicklinks' => [
                    [
                        'label' => 'Voie du Grand Jeu social',
                        'description' => 'Lire la société comme système de règles, récits, pouvoirs, institutions, flux, preuves et comportements.',
                        'url' => '/local/uckk/faculty.php?slug=grand-jeu-social',
                    ],
                    [
                        'label' => 'Voie d’Économie',
                        'description' => 'Comprendre les ressources, les flux, les incitatifs, les marchés, le travail, la valeur et les modèles viables.',
                        'url' => '/local/uckk/faculty.php?slug=economie',
                    ],
                    [
                        'label' => 'Voie d’Écologie',
                        'description' => 'Comprendre les systèmes vivants, les territoires, les ressources, les dépendances matérielles et la résilience.',
                        'url' => '/local/uckk/faculty.php?slug=ecologie',
                    ],
                    [
                        'label' => 'Voie des Sciences politiques',
                        'description' => 'Analyser le pouvoir, les institutions, les assemblées, les votes, la légitimité et les mécanismes de décision.',
                        'url' => '/local/uckk/faculty.php?slug=sciences-politiques',
                    ],
                    [
                        'label' => 'Voie de la Linguistique et de l’architecture du sens',
                        'description' => 'Étudier les mots, concepts, traductions, catégories, récits, identités et pouvoirs symboliques.',
                        'url' => '/local/uckk/faculty.php?slug=linguistique-architecture-du-sens',
                    ],
                    [
                        'label' => 'Voie de la Métaphysique',
                        'description' => 'Clarifier les structures invisibles : vérité, sens, conscience, liberté, pouvoir, destin, ordre, chaos, langage et croyance.',
                        'url' => '/local/uckk/faculty.php?slug=metaphysique',
                    ],
                    [
                        'label' => 'Voie de la Production augmentée par l’IA',
                        'description' => 'Étudier l’IA comme outil de lecture, création, cartographie, simulation et accélération, jamais comme autorité finale.',
                        'url' => '/local/uckk/faculty.php?slug=ia-gouvernable',
                    ],
                    [
                        'label' => 'Voie de l’Intervention sociale et systèmes humains',
                        'description' => 'Comprendre les humains dans les systèmes : vulnérabilité, exclusion, trauma, pauvreté, communauté, dignité et réparation.',
                        'url' => '/local/uckk/faculty.php?slug=intervention-sociale-systemes-humains',
                    ],
                    [
                        'label' => 'Voie de l’Architecture sociotechnique',
                        'description' => 'Étudier les systèmes qui combinent humains, technologies, institutions, données, règles et workflows.',
                        'url' => '/local/uckk/faculty.php?slug=architecture-sociotechnique',
                    ],
                    [
                        'label' => 'Voie de l’Architecture de l’écosystème digital kOA',
                        'description' => 'Comprendre, déployer, auditer et gouverner le kOA Digital Ecosystem et ses modules opératoires.',
                        'url' => '/local/uckk/faculty.php?slug=ecosysteme-digital-koa',
                    ],
                ],

                'sections' => [
                    [
                        'type' => 'positioning',
                        'eyebrow' => 'Position',
                        'title' => 'Une bibliothèque publique pour comprendre le jeu',
                        'body' => 'La société fonctionne déjà comme un jeu de règles, de récits, d’institutions, d’algorithmes, de statuts, de réputations, de preuves et de mémoires. L’UCKK existe pour rendre ce jeu lisible, transmettre des outils de compréhension et ouvrir des parcours d’apprentissage accessibles.',
                        'items' => [
                            'Diffuser le savoir sans le transformer en privilège fermé.',
                            'Rendre les systèmes sociaux, techniques, économiques et symboliques plus lisibles.',
                            'Offrir un cadre familier d’apprentissage : cours, Voies, archives, exercices, ressources et repères.',
                        ],
                    ],
                    [
                        'type' => 'architecture',
                        'eyebrow' => 'Architecture',
                        'title' => 'Un établissement virtuel de puissance opératoire',
                        'body' => 'UCKK forme et transmet. kOA mobilise. Le kOA Digital Ecosystem opère. King Klown attire. L’Inquisiteur protège. Les Assemblées légitiment. Le Registraire se souvient.',
                        'items' => [
                            'Les Voies structurent les grands domaines de lecture et de progression.',
                            'Les cours organisent les notions, pratiques, ressources et activités.',
                            'Les Défis transforment une question en action vérifiable.',
                            'Les Assemblées donnent une forme collective à l’évaluation et à l’orientation.',
                            'Le Registraire conserve les traces, preuves, décisions et leçons utiles.',
                        ],
                    ],
                    [
                        'type' => 'method',
                        'eyebrow' => 'Méthode',
                        'title' => 'Connaître, choisir, agir, se souvenir',
                        'body' => 'L’UCKK relie les Voies, les cours, les Défis, les Assemblées, les preuves et le Registraire pour transformer l’attention en compréhension, la compréhension en action, puis l’action en mémoire transmissible.',
                        'items' => [
                            'Connaître le jeu : observer, enquêter, cartographier et nommer.',
                            'Choisir ses coups : situer les options, les risques, les alliances et les limites.',
                            'Agir avec méthode : produire, tester, documenter, corriger.',
                            'Se souvenir : conserver les preuves, les décisions, les versions et les leçons utiles.',
                        ],
                    ],
                    [
                        'type' => 'boundary',
                        'eyebrow' => 'Clarté institutionnelle',
                        'title' => 'Un cadre ouvert, pas une barrière d’accès',
                        'body' => 'Le but immédiat de l’UCKK est la diffusion publique du savoir dans un cadre d’apprentissage familier, modernisé et accessible. Les éventuelles reconnaissances UCKK demeurent internes, sauf reconnaissance officielle future. Il n’y a pas de projet à court terme d’offrir des certifications, diplômes ou titres formels.',
                    ],
                ],

                'cardsheading' => 'Portes d’entrée publiques',
                'cards' => [
                    [
                        'title' => 'Voies',
                        'body' => 'La cartographie générale des parcours qui relient domaines, cours, défis, preuves, lectures et repères de progression.',
                        'url' => '/local/uckk/programs.php',
                        'actionlabel' => 'Voir les Voies',
                        'type' => 'programs',
                    ],
                    [
                        'title' => 'Cours',
                        'body' => 'Les espaces où les cours, ressources, activités et repères d’apprentissage sont organisés.',
                        'url' => '/local/uckk/courses.php',
                        'actionlabel' => 'Explorer les cours',
                        'type' => 'courses',
                    ],
                    [
                        'title' => 'Défis',
                        'body' => 'Les exercices publics ou internes qui transforment une question en action, production, preuve et révision.',
                        'url' => '/local/uckk/challenges.php',
                        'actionlabel' => 'Voir les Défis',
                        'type' => 'challenges',
                    ],
                    [
                        'title' => 'Assemblées',
                        'body' => 'Les lieux de discussion, d’orientation, de contestation, d’arbitrage et de légitimité collective.',
                        'url' => '/local/uckk/assemblies.php',
                        'actionlabel' => 'Voir les Assemblées',
                        'type' => 'assemblies',
                    ],
                    [
                        'title' => 'Médiathèque',
                        'body' => 'Les contenus publics, collections, références, médias et traces consultables.',
                        'url' => '/local/uckk/mediatheque.php',
                        'actionlabel' => 'Explorer la médiathèque',
                        'type' => 'media',
                    ],
                    [
                        'title' => 'Intégrité',
                        'body' => 'Le cadre qui protège la vérité des faits, la dignité des personnes, la qualité des preuves et la clarté des règles.',
                        'url' => '/local/uckk/integrity.php',
                        'actionlabel' => 'Voir le cadre',
                        'type' => 'integrity',
                    ],
                    [
                        'title' => 'Registraire',
                        'body' => 'La mémoire des traces publiques, décisions, versions, preuves, corrections et leçons utiles.',
                        'url' => '/local/uckk/archives.php',
                        'actionlabel' => 'Consulter le Registraire',
                        'type' => 'archives',
                    ],
                ],

                'notices' => [
                    [
                        'title' => 'Fiction pédagogique et faits',
                        'body' => 'King Klown peut rendre le projet visible, mémorable et spectaculaire. Les faits, preuves, règles, résultats et décisions doivent rester vérifiables.',
                        'type' => 'light',
                    ],
                ],

                'metadata' => [
                    [
                        'label' => 'Composant technique',
                        'value' => 'local_uckk',
                    ],
                    [
                        'label' => 'Type de page',
                        'value' => 'Page publique institutionnelle',
                    ],
                    [
                        'label' => 'Nature',
                        'value' => 'Bibliothèque publique et établissement virtuel de puissance opératoire',
                    ],
                    [
                        'label' => 'Domaine',
                        'value' => 'Grand Jeu social',
                    ],
                    [
                        'label' => 'Rôle',
                        'value' => 'Branche éducative du mouvement kOA',
                    ],
                ],

                'cta' => [
                    'title' => 'Entrer dans une Voie',
                    'body' => 'Commencer par une Voie fondatrice, puis relier les cours, les ressources, les Défis, les Assemblées et le Registraire.',
                    'url' => '/local/uckk/faculty.php?slug=grand-jeu-social',
                    'label' => 'Commencer par le Grand Jeu social',
                ],
            ],

            self::KEY_ABOUT => [
                'layout' => 'standard',
                'typography' => 'editorial',
                'eyebrow' => 'Situer l’UCKK',
                'title' => 'À propos',
                'subtitle' => 'L’Univers-Cité King Klown est un établissement virtuel de puissance opératoire consacré au Grand Jeu social.',
                'summary' => 'L’UCKK est une bibliothèque publique vivante, une branche éducative du mouvement kOA et un cadre d’apprentissage ouvert pour diffuser, organiser et pratiquer le savoir.',
                'sections' => [
                    [
                        'title' => 'Ce qu’est l’UCKK',
                        'body' => 'L’Univers-Cité King Klown est un établissement virtuel de puissance opératoire. Elle rassemble des cours, des Voies, des défis, des Assemblées, une Médiathèque, un Registraire, des archives, des portfolios, des preuves et des formes de reconnaissance interne.',
                    ],
                    [
                        'title' => 'Une bibliothèque publique vivante',
                        'body' => 'La première fonction de l’UCKK est la diffusion immédiate du savoir. Les pages publiques ouvrent un accès aux connaissances, aux méthodes, aux références et aux parcours d’apprentissage sans transformer le savoir en privilège fermé. L’UCKK s’inscrit dans l’esprit kOA : rendre les outils de compréhension plus accessibles, plus praticables et plus partageables.',
                    ],
                    [
                        'title' => 'Un cadre d’apprentissage modernisé',
                        'body' => 'L’UCKK utilise un cadre familier d’apprentissage — cours, Voies, niveaux, exercices, archives et traces — en le modernisant pour servir la lecture critique des systèmes, la production de preuves, la mémoire collective et l’action située. Les parcours ne sont pas conçus comme une barrière d’accès, mais comme une manière d’organiser le savoir pour mieux s’y orienter.',
                    ],
                    [
                        'title' => 'Ce que montre l’interface publique',
                        'body' => 'L’interface publique donne accès aux repères institutionnels de l’UCKK : Voies, cours, défis, Assemblées, Médiathèque, Registraire, règles d’intégrité et informations publiques. Les espaces internes, inscriptions, rôles, permissions, validations et dossiers privés restent gérés dans les espaces appropriés.',
                    ],
                    [
                        'title' => 'King Klown, Inquisiteur, Assemblées et Registraire',
                        'body' => 'King Klown ouvre la scène publique et rend les systèmes visibles. Il attire l’attention, rend les situations mémorables et invite à lire le Grand Jeu social par le théâtre public, sans devenir l’autorité finale. L’Inquisiteur protège l’intégrité du jeu : il interroge les faits, les preuves, les limites et les risques de confusion. Les Assemblées donnent une forme collective à l’orientation, à la contestation, à l’arbitrage et aux décisions. Le Registraire conserve les traces utiles, les versions, les preuves, les décisions et les corrections.',
                    ],
                ],
                'cardsheading' => 'Repères institutionnels',
                'cards' => [
                    [
                        'title' => 'kOA',
                        'body' => 'Le mouvement large : vision, culture, principes, stratégie et transformation des règles.',
                        'type' => 'koa',
                    ],
                    [
                        'title' => 'UCKK',
                        'body' => 'La branche éducative : bibliothèque publique, Voies, cours, ressources, défis, preuves et Assemblées.',
                        'type' => 'uckk',
                    ],
                    [
                        'title' => 'King Klown',
                        'body' => 'La figure narrative : il attire l’attention, rend les systèmes mémorables et ouvre des scènes de lucidité.',
                        'type' => 'king-klown',
                    ],
                    [
                        'title' => 'Inquisiteur',
                        'body' => 'Le garde-fou éthique : il protège la preuve, la dignité, la clarté et la critique.',
                        'type' => 'inquisiteur',
                    ],
                    [
                        'title' => 'Assemblées',
                        'body' => 'La légitimité collective : discussion, arbitrage, décisions, contestations et mémoire institutionnelle.',
                        'url' => '/local/uckk/assemblies.php',
                        'actionlabel' => 'Voir les Assemblées',
                        'type' => 'assemblies',
                    ],
                    [
                        'title' => 'Registraire',
                        'body' => 'La mémoire : conservation des traces, versions, preuves, corrections et décisions.',
                        'url' => '/local/uckk/archives.php',
                        'actionlabel' => 'Voir le Registraire',
                        'type' => 'archives',
                    ],
                ],
                'notices' => [
                    [
                        'title' => 'Clarté publique',
                        'body' => 'Les éventuelles reconnaissances UCKK demeurent internes, sauf reconnaissance officielle future. Il n’y a pas de projet à court terme d’offrir des certifications, diplômes ou titres formels.',
                        'type' => 'institutional',
                    ],
                ],
                'cta' => [
                    'title' => 'Comprendre l’architecture UCKK',
                    'body' => 'Commencer par les Voies, puis explorer les cours, les défis, les Assemblées et la médiathèque.',
                    'url' => '/local/uckk/programs.php',
                    'label' => 'Voir les Voies',
                ],
            ],

            self::KEY_PROGRAMS => [
                'layout' => 'wide',
                'typography' => 'institutional',
                'eyebrow' => 'Cartographie des voies',
                'title' => 'Voies UCKK',
                'subtitle' => 'Choisir un chemin de lecture, d’apprentissage et de pratique dans le Grand Jeu social.',
                'summary' => 'Les Voies UCKK structurent la bibliothèque publique de l’Univers-Cité King Klown : tronc commun, domaines fondateurs, cours, défis, ressources, portfolios, Assemblées et Registraire.',
                'sections' => [
                    [
                        'type' => 'orientation',
                        'eyebrow' => 'Orientation canonique',
                        'title' => 'Choisir une Voie dans l’Univers-Cité King Klown',
                        'body' => 'Les Voies UCKK sont des parcours de lecture, de pratique et d’orientation. Elles relient les cours, les compétences, les défis, les preuves, les portfolios, les Assemblées et les traces utiles. Une Voie n’est pas seulement une liste de cours : c’est une carte pour se situer dans un domaine d’action et organiser progressivement sa compréhension.',
                    ],
                    [
                        'type' => 'registry',
                        'eyebrow' => 'Voies publiques',
                        'title' => 'Tronc commun et voies actives',
                        'body' => 'Chaque carte présente une Voie ou un bloc de formation visible publiquement. Elle peut ouvrir vers les espaces de cours associés lorsque ceux-ci sont publiés. Les Voies servent d’architecture de savoir : elles aident à découvrir, relier, pratiquer et approfondir les contenus de l’UCKK.',
                    ],
                ],
                'cards' => [],
                'notices' => [
                    [
                        'title' => 'Accès au savoir',
                        'body' => 'Les Voies sont des cartes publiques d’apprentissage. Elles organisent l’accès aux contenus, aux cours, aux ressources et aux pratiques sans transformer le savoir en barrière d’entrée.',
                        'type' => 'light',
                    ],
                ],
                'metadata' => [
                    ['label' => 'Registre', 'value' => 'Voies et blocs de formation publics'],
                    ['label' => 'Filtre public', 'value' => 'Voies actives et publiables'],
                ],
                'cta' => [
                    'title' => 'Passer des Voies aux cours',
                    'body' => 'Ouvre les cours associés pour consulter les modules, activités, ressources et repères de progression.',
                    'url' => '/local/uckk/courses.php',
                    'label' => 'Voir les cours associés',
                ],
            ],

            self::KEY_COURSES => [
                'layout' => 'standard',
                'typography' => 'institutional',
                'eyebrow' => 'Explorer les cours',
                'title' => 'Cours UCKK',
                'subtitle' => 'Cours publics, Voies associées et repères de progression.',
                'summary' => 'Les cours contiennent les activités, ressources et repères d’apprentissage de l’UCKK.',
                'sections' => [
                    [
                        'title' => 'Fonction des cours',
                        'body' => 'Les cours structurent les activités concrètes de la bibliothèque publique UCKK. Ils organisent les notions, ressources, exercices, références et repères de progression pour rendre le savoir plus lisible et plus praticable.',
                    ],
                    [
                        'title' => 'Accès',
                        'body' => 'Certains cours sont visibles publiquement. D’autres peuvent demander une connexion, une inscription ou un rôle autorisé lorsque des activités, traces ou espaces internes doivent être protégés.',
                    ],
                ],
                'cardsheading' => '',
                'cards' => [],
                'has_course_explorer' => true,
                'course_explorer' => [
                    'title' => 'Explorer les cours',
                    'summary' => 'Filtrer les cours publics par mot-clé, Voie associée et ordre d’affichage.',
                    'query' => '',
                    'category' => 'all',
                    'sort' => 'pedagogical',
                    'filters' => [],
                    'sorts' => [],
                    'results' => [],
                    'total' => 0,
                    'emptytitle' => 'Aucun cours trouvé',
                    'emptybody' => 'Aucun cours public ne correspond aux filtres actuels.',
                    'indexurl' => '/course/index.php',
                    'indexlabel' => 'Ouvrir l’index des cours',
                ],
                'metadata' => [
                    [
                        'label' => 'Source',
                        'value' => 'Cours visibles de l’UCKK',
                    ],
                    [
                        'label' => 'Filtre public',
                        'value' => 'Cours visibles dans des catégories visibles',
                    ],
                ],
                'cta' => [
                    'title' => 'Index des cours',
                    'body' => 'L’index des cours permet de parcourir les catégories, espaces de cours et ressources accessibles.',
                    'url' => '/course/index.php',
                    'label' => 'Ouvrir l’index',
                ],
            ],

            self::KEY_CHALLENGES => [
                'layout' => 'standard',
                'typography' => 'institutional',
                'eyebrow' => 'Mettre la lucidité à l’épreuve',
                'title' => 'Défis UCKK',
                'subtitle' => 'Épreuves pédagogiques, traces, productions et révisions.',
                'summary' => 'Les défis servent à produire des traces, vérifier des preuves et rendre visibles des efforts structurés.',
                'sections' => [
                    [
                        'title' => 'Pourquoi des défis',
                        'body' => 'Un défi transforme une intention en action observable. Il peut demander une production, une preuve, une réflexion ou une validation.',
                    ],
                    [
                        'title' => 'Cycle général',
                        'body' => 'Un défi peut être proposé, accepté, réalisé, soumis, révisé, validé, contesté ou conservé selon les règles du contexte.',
                        'items' => [
                            'Comprendre la consigne.',
                            'Produire une trace.',
                            'Soumettre une preuve.',
                            'Recevoir une révision.',
                            'Conserver ce qui doit rester vérifiable.',
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
                        'body' => 'Les validations et contestations suivent les permissions et les rôles autorisés de l’UCKK.',
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
                'summary' => 'Les assemblées organisent les moments où l’UCKK discute, arbitre, documente et rend certaines décisions traçables.',
                'sections' => [
                    [
                        'title' => 'Rôle des Assemblées',
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
                        'title' => 'Mémoire institutionnelle',
                        'body' => 'Conserver les traces utiles sans exposer les informations privées.',
                        'type' => 'memory',
                    ],
                ],
                'notices' => [
                    [
                        'body' => 'Les assemblées publiques ne remplacent pas les espaces internes de gouvernance ou les permissions de l’UCKK.',
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
                        'body' => 'Ce qui est visible publiquement doit être intentionnel, utile, vérifiable et compatible avec les permissions de l’UCKK.',
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
                'eyebrow' => 'Tenir le registre public',
                'title' => 'Registraire UCKK',
                'subtitle' => 'Registre institutionnel, preuves publiques, décisions et corrections.',
                'summary' => 'Le registraire organise les traces publiques utiles à la compréhension de l’UCKK, de ses décisions et de ses transformations.',
                'sections' => [
                    [
                        'title' => 'Ce que le registraire conserve',
                        'body' => 'Le registraire peut conserver des versions publiques du canon, des décisions, des modèles, des corrections, des appels, des travaux publics et des traces institutionnelles.',
                    ],
                    [
                        'title' => 'Ce que le registraire ne doit pas exposer',
                        'body' => 'Il ne doit pas publier de données personnelles, dossiers sensibles, preuves privées ou informations réservées à certains rôles autorisés de l’UCKK.',
                    ],
                    [
                        'title' => 'Usage',
                        'body' => 'Le registraire sert à comprendre, vérifier, relier et corriger. Il n’est pas un simple dépôt de fichiers.',
                    ],
                ],
                'cardsheading' => 'Repères du registraire',
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
                        'title' => 'Registraire public',
                        'body' => 'Les contenus visibles ici doivent respecter la confidentialité et les limites institutionnelles.',
                        'type' => 'warning',
                    ],
                ],
            ],

            self::KEY_MEDIATHEQUE => [
                'layout' => 'wide',
                'typography' => 'institutional',
                'eyebrow' => 'Catalogue public',
                'title' => 'Médiathèque UCKK',
                'subtitle' => 'Explorer les médias, collections, références externes et passages documentés.',
                'summary' => 'Le catalogue donne accès aux contenus publics autorisés : vidéos, sons, images, documents, collections, références externes et extraits contextualisés. Les résultats sont filtrés selon les droits, les avis de contenu, la visibilité et les protocoles culturels.',
                'cardsheading' => 'Parcourir',
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
                        'item' => '',
                    ],
                    'page' => 1,
                    'perpage' => 12,
                    'sort' => 'relevance',
                    'sitewide' => true,
                ],
                'mediatheque' => [
                    'explorer' => [
                        'key' => 'mediatheque_explorer',
                        'title' => 'Recherche publique',
                        'service' => 'mod_uckkarchive_search_mediatheque',
                        'surface' => 'local_uckk',
                        'dataowner' => 'mod_uckkarchive',
                    ],
                ],
                'quicklinks' => [
                    [
                        'label' => 'Rechercher',
                        'description' => 'Trouver des médias publics, collections accessibles, références externes et passages documentés.',
                        'url' => '/local/uckk/mediatheque.php',
                    ],
                ],
                'sections' => [
                    [
                        'type' => 'orientation',
                        'eyebrow' => 'Exploration',
                        'title' => 'Naviguer dans les contenus publics',
                        'body' => 'L’explorateur permet de chercher, filtrer et ouvrir les fiches publiques sans exposer les fichiers originaux, les brouillons, les notes internes ni les métadonnées privées.',
                        'items' => [
                            'Recherche par texte, format, source, collection et mot-clé.',
                            'Affichage limité aux contenus publiables.',
                            'Respect des droits, avis de contenu et protocoles culturels.',
                        ],
                    ],
                    [
                        'type' => 'boundary',
                        'eyebrow' => 'Responsabilité',
                        'title' => 'Ce qui reste protégé',
                        'body' => 'Les médias restreints, brouillons, notes internes, protocoles culturels privés, fichiers originaux non autorisés et métadonnées sensibles restent contrôlés par le moteur média institutionnel.',
                    ],
                ],
                'cards' => [
                    [
                        'title' => 'Médias',
                        'body' => 'Voir les vidéos, sons, images, documents et références publiques filtrées.',
                        'type' => 'media',
                    ],
                    [
                        'title' => 'Collections',
                        'body' => 'Explorer les regroupements publics sans dupliquer le moteur média institutionnel.',
                        'type' => 'collection',
                    ],
                    [
                        'title' => 'Passages documentés',
                        'body' => 'Accéder à des moments, pages ou segments lorsque leur exposition publique est autorisée.',
                        'type' => 'marker',
                    ],
                ],
                'notices' => [
                    [
                        'title' => 'Données et politiques',
                        'body' => 'Cette page est une surface publique de local_uckk. Les médias, droits, avis de contenu, protocoles culturels et règles de visibilité sont filtrés par le moteur média institutionnel.',
                        'type' => 'institutional',
                    ],
                    [
                        'title' => 'Accès responsable',
                        'body' => 'Certains contenus peuvent être masqués, résumés ou limités selon les droits, les avis de contenu, les protocoles culturels et les permissions disponibles.',
                        'type' => 'light',
                    ],
                ],
                'metadata' => [
                    ['label' => 'Surface publique', 'value' => 'local_uckk'],
                    ['label' => 'Données et politiques', 'value' => 'Moteur média institutionnel'],
                    ['label' => 'Service', 'value' => 'Recherche du catalogue public'],
                    ['label' => 'Portée par défaut', 'value' => 'Contenus publics du site'],
                ],
                'cta' => [
                    'title' => 'Explorer les contenus publics',
                    'body' => 'Lance une recherche ou applique un filtre pour explorer les contenus publics selon le format, la source, la collection, les droits et les avis disponibles.',
                    'url' => '/local/uckk/mediatheque.php',
                    'label' => 'Ouvrir la recherche',
                ],
            ],

            self::KEY_NEWS => [
                'layout' => 'standard',
                'typography' => 'institutional',
                'eyebrow' => 'Nouvelles et appels',
                'title' => 'Actualités UCKK',
                'subtitle' => 'Informations publiques, appels, jalons et annonces institutionnelles.',
                'summary' => 'Les actualités présentent les informations publiques utiles au suivi de l’UCKK.',
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
                        'body' => 'Moments importants du développement de l’établissement.',
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
                'subtitle' => 'Orientation publique pour contacter ou rejoindre l’UCKK.',
                'summary' => 'Cette page indique les voies générales de contact et rappelle les limites des demandes publiques.',
                'sections' => [
                    [
                        'title' => 'Demandes générales',
                        'body' => 'Les demandes publiques peuvent concerner l’orientation, la compréhension de l’UCKK, les Voies, les cours, les portfolios ou la Médiathèque.',
                    ],
                    [
                        'title' => 'Demandes privées',
                        'body' => 'Les questions liées à un dossier personnel, une preuve, une inscription ou une décision doivent passer par les espaces appropriés de l’UCKK.',
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
                        'title' => 'Médiathèque',
                        'body' => 'Consulter les contenus, médias et collections publics disponibles.',
                        'url' => '/local/uckk/mediatheque.php',
                        'actionlabel' => 'Ouvrir le catalogue',
                        'type' => 'media',
                    ],
                ],
                'cta' => [
                    'title' => 'Besoin d’orientation?',
                    'body' => 'Commence par les pages publiques, puis connecte-toi si ta demande concerne un espace ou un rôle spécifique.',
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

            if ($title === 'Tronc commun et voies actives' || $title === 'Répertoire public' || $title === 'Voies et programmes actifs' || $eyebrow === 'Voies publiques' || $eyebrow === 'Répertoire public') {
                $definition['sections'][$index]['cards'] = $cards;
                $attached = true;
                break;
            }
        }

        if (!$attached) {
            $definition['sections'][] = [
                'type' => 'registry',
                'eyebrow' => 'Voies publiques',
                'title' => 'Tronc commun et voies actives',
                'body' => 'Ces cartes proviennent du registre UCKK des voies et blocs de formation actifs. Les éléments en brouillon ou non publiés ne sont pas affichés ici.',
                'cards' => $cards,
            ];
        }

        if (!isset($definition['metadata']) || !is_array($definition['metadata'])) {
            $definition['metadata'] = [];
        }

        $definition['metadata'][] = [
            'label' => 'Voies actives publiées',
            'value' => (string)count($cards),
        ];

        if (empty($cards)) {
            if (!isset($definition['notices']) || !is_array($definition['notices'])) {
                $definition['notices'] = [];
            }

            $definition['notices'][] = [
                'title' => 'Aucune voie active',
                'body' => 'Aucune voie active n’est actuellement disponible dans le registre public UCKK.',
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
                p.description,
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
            $description = trim((string)($record->description ?? ''));
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

            if ($description !== '') {
                $bodyparts[] = $description;
            } else if ($typelabel !== '') {
                $bodyparts[] = 'Structure publique de type ' . strtolower($typelabel) . ' dans l’UCKK.';
            } else {
                $bodyparts[] = 'Structure publique de formation dans l’UCKK.';
            }

            if ($categoryname !== '') {
                $bodyparts[] = 'Espace de cours associé : ' . $categoryname . '.';
            } else {
                $bodyparts[] = 'Aucun espace de cours public associé pour le moment.';
            }

            $url = '';
            $actionlabel = '';

            if ($categoryid > 0 && $categoryvisible === 1) {
                $url = (new moodle_url('/course/index.php', ['categoryid' => $categoryid]))->out(false);
                $actionlabel = 'Voir les cours associés';
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
            self::KEY_ARCHIVES => 'Registraire UCKK',
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
            self::KEY_ARCHIVES => 'public_registrar_title',
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