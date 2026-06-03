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

namespace local_uckk\local\public_pages;

defined('MOODLE_INTERNAL') || die();

/**
 * Public integrity page definition.
 *
 * @package    local_uckk
 * @copyright  2026 Univers-Cité King Klown
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class integrity {
    /**
     * Return the public page definition.
     *
     * @return array<string, mixed>
     */
    public static function definition(): array {
        return [
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
        ];
    }
}