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
 * Public about page definition for UCKK.
 *
 * @package    local_uckk
 * @copyright  2026 Univers-Cité King Klown
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_uckk\local\public_pages;

defined('MOODLE_INTERNAL') || die();

/**
 * Public about page definition.
 *
 * @package local_uckk
 */
final class about {
    /**
     * Return the public page definition.
     *
     * @return array<string, mixed>
     */
    public static function definition(): array {
        return [
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
                    'body' => 'Les contenus publics sont séparés du rendu visuel. Les définitions de page vivent dans des classes dédiées sous classes/local/public_pages, le rendu dans public_page.php, la structure HTML dans Mustache et l’apparence dans CSS.',
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
        ];
    }
}