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
 * Public archives page definition for local_uckk.
 *
 * @package    local_uckk
 * @copyright  2026 Univers-Cité King Klown
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_uckk\local\public_pages;

defined('MOODLE_INTERNAL') || die();

/**
 * Public archives page definition.
 *
 * @package local_uckk
 */
final class archives {
    /**
     * Return the public page definition.
     *
     * @return array<string, mixed>
     */
    public static function definition(): array {
        return [
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
        ];
    }
}