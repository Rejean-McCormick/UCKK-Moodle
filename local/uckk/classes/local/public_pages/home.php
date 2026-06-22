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

            'eyebrow' => 'Établissement virtuel de puissance opératoire',
            'title' => 'Univers-Cité King Klown',
            'subtitle' => 'Comprendre le jeu. Jouer avec lucidité. Changer les règles.',
            'summary' => 'L’Univers-Cité King Klown est un établissement virtuel de puissance opératoire consacré au Grand Jeu social. Elle relie les Voies, les cours, les Défis, les Assemblées, l’Intégrité et le Registraire pour former des Joueurs lucides capables de lire les systèmes, produire des preuves et agir avec méthode.',

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
                    'title' => 'Former des Joueurs lucides',
                    'body' => 'La société fonctionne déjà comme un jeu de règles, de récits, d’institutions, d’algorithmes, de statuts, de réputations, de preuves et de mémoires. L’Univers-Cité King Klown existe pour rendre ce jeu lisible et pour former des personnes capables d’y agir avec méthode, panache et intégrité.',
                    'items' => [
                        'Comprendre les règles visibles et invisibles qui organisent la vie collective.',
                        'Jouer avec lucidité, sans naïveté, sans cynisme et sans perte d’intégrité.',
                        'Changer les règles lorsque le jeu devient injuste, opaque ou destructeur.',
                    ],
                ],
                [
                    'type' => 'architecture',
                    'eyebrow' => 'Architecture',
                    'title' => 'Un établissement virtuel de puissance opératoire',
                    'body' => 'UCKK forme. kOA mobilise. Le kOA Digital Ecosystem opère. King Klown attire. L’Inquisiteur protège. Les Assemblées légitiment. Le Registraire se souvient.',
                    'items' => [
                        'Les Voies structurent les grands domaines de progression.',
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
                    'eyebrow' => 'Limite publique',
                    'title' => 'Clarté institutionnelle',
                    'body' => 'L’UCKK est un établissement virtuel émergent de puissance opératoire. Ses Niveaux, Parchemins UCKK, dossiers de passage, portfolios et reconnaissances internes ne doivent pas être présentés comme des diplômes publics accrédités, des grades universitaires ou des titres professionnels reconnus par l’État, sauf reconnaissance officielle future.',
                ],
            ],

            'cardsheading' => 'Portes d’entrée publiques',
            'cards' => [
                [
                    'title' => 'Voies',
                    'body' => 'La cartographie générale des parcours qui relient domaines, cours, défis, preuves, Niveaux et Parchemins UCKK.',
                    'url' => '/local/uckk/programs.php',
                    'actionlabel' => 'Voir les Voies',
                    'type' => 'programs',
                ],
                [
                    'title' => 'Cours',
                    'body' => 'Les espaces où les cours, ressources, activités et repères de progression sont organisés.',
                    'url' => '/local/uckk/courses.php',
                    'actionlabel' => 'Explorer les cours',
                    'type' => 'courses',
                ],
                [
                    'title' => 'Défis',
                    'body' => 'Les épreuves publiques ou internes qui transforment une question en action, production, preuve et progression.',
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
                    'title' => 'Reconnaissance interne',
                    'body' => 'Les reconnaissances UCKK attestent une progression de compétence, de pratique, de recherche ou de contribution au sein de l’Univers-Cité King Klown. Elles ne remplacent pas des diplômes publics accrédités.',
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
                    'value' => 'Établissement virtuel de puissance opératoire',
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
                'body' => 'Commencer par une Voie fondatrice, puis relier les cours, les preuves, les Défis, les Assemblées et le Registraire.',
                'url' => '/local/uckk/faculty.php?slug=grand-jeu-social',
                'label' => 'Commencer par le Grand Jeu social',
            ],
        ];
    }
}
