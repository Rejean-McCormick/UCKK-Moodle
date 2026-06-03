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
        ];
    }
}