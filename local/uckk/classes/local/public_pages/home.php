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

            'eyebrow' => 'Cité d’apprentissage public',
            'title' => 'Univers-Cité King Klown',
            'subtitle' => 'Comprendre le jeu. Jouer avec lucidité. Changer les règles.',
            'summary' => 'UCKK-Moodle est le campus pédagogique de l’Univers-Cité King Klown. Il relie les voies, les cours, les défis, les assemblées, l’intégrité et les archives pour former des joueurs lucides du Grand Jeu social.',

            'quicklinks' => [
                [
                    'label' => 'Explorer les cours',
                    'description' => 'Entrer dans les espaces Moodle où les apprentissages sont organisés.',
                    'url' => '/local/uckk/courses.php',
                ],
                [
                    'label' => 'Situer les voies',
                    'description' => 'Comprendre les grands parcours de formation et d’engagement UCKK.',
                    'url' => '/local/uckk/programs.php',
                ],
                [
                    'label' => 'Lire les archives',
                    'description' => 'Retrouver les traces publiques, les preuves, les décisions et la mémoire.',
                    'url' => '/local/uckk/archives.php',
                ],
            ],

            'sections' => [
                [
                    'type' => 'positioning',
                    'eyebrow' => 'Position',
                    'title' => 'Former des joueurs lucides',
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
                    'title' => 'Une branche éducative du mouvement kOA',
                    'body' => 'UCKK forme. kOA mobilise. Le kOA Digital Ecosystem opère. King Klown attire. L’Inquisiteur protège. Les Assemblées légitiment. Les Archives se souviennent.',
                    'items' => [
                        'Les voies structurent les grands domaines d’apprentissage.',
                        'Les cours organisent les notions, pratiques et activités.',
                        'Les défis transforment une question en action vérifiable.',
                        'Les assemblées donnent une forme collective à l’évaluation et à l’orientation.',
                        'Les archives conservent les traces, preuves, décisions et leçons apprises.',
                    ],
                ],
                [
                    'type' => 'method',
                    'eyebrow' => 'Méthode',
                    'title' => 'Connaître, choisir, agir, se souvenir',
                    'body' => 'Le campus relie les apprentissages, les défis, les assemblées, les preuves et les archives pour transformer l’attention en apprentissage, l’apprentissage en action, puis l’action en mémoire transmissible.',
                    'items' => [
                        'Connaître le jeu : observer, enquêter, cartographier et nommer.',
                        'Choisir ses coups : situer les options, les risques, les alliances et les limites.',
                        'Agir avec méthode : produire, tester, documenter, corriger.',
                        'Se souvenir : conserver les preuves, les décisions, les versions et les leçons apprises.',
                    ],
                ],
                [
                    'type' => 'boundary',
                    'eyebrow' => 'Limite publique',
                    'title' => 'Clarté institutionnelle',
                    'body' => 'UCKK est une cité d’apprentissage public émergente. Ses Niveaux, Parchemins UCKK, dossiers de passage, portfolios et reconnaissances internes ne doivent pas être présentés comme des diplômes publics accrédités, des grades universitaires ou des titres professionnels reconnus par l’État, sauf reconnaissance officielle future.',
                ],
            ],

            'cardsheading' => 'Entrées principales',
            'cards' => [
                [
                    'title' => 'Cours',
                    'body' => 'Les espaces Moodle où les apprentissages, ressources, activités et évaluations sont organisés.',
                    'url' => '/local/uckk/courses.php',
                    'actionlabel' => 'Explorer les cours',
                    'type' => 'courses',
                ],
                [
                    'title' => 'Voies',
                    'body' => 'Les parcours qui relient domaines, cours, défis, preuves, Niveaux et Parchemins UCKK.',
                    'url' => '/local/uckk/programs.php',
                    'actionlabel' => 'Voir les voies',
                    'type' => 'programs',
                ],
                [
                    'title' => 'Défis',
                    'body' => 'Les épreuves publiques ou internes qui transforment une question en action, preuve et apprentissage.',
                    'url' => '/local/uckk/challenges.php',
                    'actionlabel' => 'Voir les défis',
                    'type' => 'challenges',
                ],
                [
                    'title' => 'Assemblées',
                    'body' => 'Les lieux de discussion, d’évaluation, de contestation, d’orientation et de légitimité collective.',
                    'url' => '/local/uckk/assemblies.php',
                    'actionlabel' => 'Voir les assemblées',
                    'type' => 'assemblies',
                ],
                [
                    'title' => 'Intégrité',
                    'body' => 'Le cadre qui protège la vérité des faits, la dignité des personnes, la qualité des preuves et la clarté des règles.',
                    'url' => '/local/uckk/integrity.php',
                    'actionlabel' => 'Voir le cadre',
                    'type' => 'integrity',
                ],
                [
                    'title' => 'Archives',
                    'body' => 'La mémoire des traces publiques, décisions, versions, preuves, corrections et apprentissages.',
                    'url' => '/local/uckk/archives.php',
                    'actionlabel' => 'Consulter les archives',
                    'type' => 'archives',
                ],
            ],

            'notices' => [
                [
                    'title' => 'Reconnaissance interne',
                    'body' => 'Les reconnaissances UCKK attestent une progression de compétence, de pratique, de recherche ou de contribution dans la cité d’apprentissage. Elles ne remplacent pas des diplômes publics accrédités.',
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
                    'label' => 'Composant Moodle',
                    'value' => 'local_uckk',
                ],
                [
                    'label' => 'Type de page',
                    'value' => 'Page publique institutionnelle',
                ],
                [
                    'label' => 'Cadre canonique',
                    'value' => 'Cité d’apprentissage public émergente',
                ],
                [
                    'label' => 'Rôle',
                    'value' => 'Branche éducative du mouvement kOA',
                ],
            ],

            'cta' => [
                'title' => 'Entrer dans l’apprentissage',
                'body' => 'Commencer par les cours, situer les voies, puis relier les apprentissages aux défis, aux assemblées et aux archives.',
                'url' => '/local/uckk/courses.php',
                'label' => 'Explorer les cours',
            ],
        ];
    }
}