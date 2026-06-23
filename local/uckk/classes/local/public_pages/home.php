<?php
// This file is part of UCKK-Moodle.
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.
//
// UCKK-Moodle uses Moodle as a technical platform for the
// Univers-Cité King Klown.

/**
 * Public home page definition for local_uckk.
 *
 * @package    local_uckk
 * @copyright  2026 Univers-Cité King Klown
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_uckk\local\public_pages;

defined('MOODLE_INTERNAL') || die();

/**
 * Public home page definition.
 *
 * @package local_uckk
 */
final class home {
    /**
     * Return the public page definition.
     *
     * @return array<string, mixed>
     */
    public static function definition(): array {
        return [
            'layout' => 'wide',
            'typography' => 'display',

            'eyebrow' => 'Bibliothèque publique vivante',
            'title' => 'Univers-Cité King Klown',
            'subtitle' => 'Comprendre le jeu. Jouer avec lucidité. Changer les règles.',
            'summary' => 'L’Univers-Cité King Klown est un établissement virtuel de puissance opératoire consacré au Grand Jeu social. Elle ouvre une bibliothèque publique vivante, organise des parcours de savoir et propose un cadre d’apprentissage familier, modernisé et accessible pour lire les systèmes, produire des preuves, discuter les idées et agir avec méthode.',

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
                    'label' => 'Voie de l’Intelligence artificielle gouvernable',
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
                    'title' => 'Ouvrir le savoir avant de promettre des titres',
                    'body' => 'La première fonction de l’UCKK est la diffusion immédiate du savoir. Elle ne place pas la connaissance derrière un mur d’accès : elle la rend consultable, navigable, discutable et praticable dans un cadre d’apprentissage structuré.',
                    'items' => [
                        'Rendre les connaissances publiques plus lisibles, reliées et utilisables.',
                        'Donner des repères pour comprendre les systèmes sociaux, techniques, politiques, économiques et symboliques.',
                        'Permettre à chacun d’entrer par une Voie, un cours, une archive, un média, un défi ou une assemblée.',
                    ],
                ],
                [
                    'type' => 'architecture',
                    'eyebrow' => 'Architecture',
                    'title' => 'Un cadre d’apprentissage familier, modernisé',
                    'body' => 'L’UCKK reprend des formes connues — cours, parcours, ressources, activités, archives, discussions — et les modernise autour d’une logique plus ouverte : apprendre, relier, produire, vérifier, transmettre.',
                    'items' => [
                        'Les Voies organisent les grands domaines de savoir et d’action.',
                        'Les cours offrent des points d’entrée structurés dans chaque domaine.',
                        'Les Défis transforment une question en exercice, production ou preuve.',
                        'Les Assemblées donnent une forme collective à la discussion, à l’orientation et à la correction.',
                        'Le Registraire conserve les traces, preuves, décisions, versions et leçons utiles.',
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
                    'eyebrow' => 'Note institutionnelle',
                    'title' => 'Reconnaissance et horizon public',
                    'body' => 'Il n’y a pas de projet à court terme d’offrir une certification formelle. Le but immédiat est d’ouvrir et d’organiser le savoir. Les éventuelles reconnaissances UCKK demeurent internes, sauf reconnaissance officielle future.',
                ],
            ],

            'cardsheading' => 'Portes d’entrée publiques',
            'cards' => [
                [
                    'title' => 'Voies',
                    'body' => 'Les grands parcours de lecture du monde : domaines, notions, cours, défis, preuves, archives et pratiques.',
                    'url' => '/local/uckk/programs.php',
                    'actionlabel' => 'Voir les Voies',
                    'type' => 'programs',
                ],
                [
                    'title' => 'Cours',
                    'body' => 'Les espaces structurés pour explorer les notions, ressources, activités et repères de progression.',
                    'url' => '/local/uckk/courses.php',
                    'actionlabel' => 'Explorer les cours',
                    'type' => 'courses',
                ],
                [
                    'title' => 'Défis',
                    'body' => 'Les exercices publics ou internes qui transforment une question en action, production, preuve et apprentissage.',
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
                    'title' => 'Bibliothèque ouverte',
                    'body' => 'La connaissance doit circuler. L’UCKK organise des ressources, parcours, archives et scènes d’apprentissage pour rendre le savoir plus accessible, plus relié et plus praticable.',
                    'type' => 'institutional',
                ],
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
                    'value' => 'Bibliothèque publique vivante et établissement virtuel de puissance opératoire',
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
                'title' => 'Entrer dans la bibliothèque vivante',
                'body' => 'Commencer par une Voie fondatrice, explorer les cours, consulter les traces publiques et relier les idées au Grand Jeu social.',
                'url' => '/local/uckk/faculty.php?slug=grand-jeu-social',
                'label' => 'Commencer par le Grand Jeu social',
            ],
        ];
    }
}