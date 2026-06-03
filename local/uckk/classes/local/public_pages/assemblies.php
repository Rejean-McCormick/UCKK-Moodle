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
 * Public assemblies page definition for local_uckk.
 *
 * This class owns the public page definition for the Assemblées UCKK page.
 * It defines presentation data only.
 *
 * It must not publish decisions, mutate assembly records, expose private
 * deliberation data, assign roles, validate recognitions, or replace Moodle
 * permissions and governance spaces.
 *
 * @package    local_uckk
 * @copyright  2026 Univers-Cité King Klown
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_uckk\local\public_pages;

defined('MOODLE_INTERNAL') || die();

/**
 * Public assemblies page definition.
 *
 * @package local_uckk
 */
final class assemblies {
    /**
     * Return the public page definition.
     *
     * @return array<string, mixed>
     */
    public static function definition(): array {
        return [
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
        ];
    }
}