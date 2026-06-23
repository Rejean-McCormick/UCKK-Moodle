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
            'eyebrow' => 'Protéger les personnes, les traces et le savoir',
            'title' => 'Intégrité UCKK',
            'subtitle' => 'Repères publics pour une bibliothèque ouverte, fiable et responsable.',
            'summary' => 'L’intégrité UCKK protège la dignité des personnes, la provenance des traces, la lisibilité du savoir et la confiance nécessaire à une diffusion publique des connaissances.',
            'sections' => [
                [
                    'title' => 'Principe',
                    'body' => 'UCKK ouvre des connaissances au public sans transformer les personnes en objets d’exposition. Une trace utile doit rester proportionnée, contextualisée et respectueuse de celles et ceux qu’elle concerne.',
                ],
                [
                    'title' => 'Bibliothèque publique',
                    'body' => 'Les pages publiques servent d’abord à rendre le savoir accessible : cours, archives, médiathèque, assemblées, défis et repères de lecture. Ce qui est publié doit aider à comprendre, apprendre, vérifier ou s’orienter.',
                ],
                [
                    'title' => 'Preuve et provenance',
                    'body' => 'Une information publique gagne en valeur lorsqu’elle indique son origine, son contexte et son niveau de validation. Les traces importantes doivent pouvoir être comprises sans exposer inutilement des données privées.',
                ],
                [
                    'title' => 'Corrections',
                    'body' => 'Quand une erreur est trouvée, elle doit être corrigée clairement. Lorsque la trace est utile à la mémoire institutionnelle, la correction doit permettre de comprendre ce qui a changé sans amplifier inutilement l’erreur.',
                ],
            ],
            'cardsheading' => 'Repères d’intégrité',
            'cards' => [
                [
                    'title' => 'Confidentialité',
                    'body' => 'Protéger les informations privées, sensibles ou non nécessaires à la compréhension publique.',
                    'type' => 'privacy',
                ],
                [
                    'title' => 'Provenance',
                    'body' => 'Conserver l’origine des traces importantes et rendre leur contexte lisible.',
                    'type' => 'provenance',
                ],
                [
                    'title' => 'Justesse publique',
                    'body' => 'Publier des contenus utiles, vérifiables, proportionnés et alignés avec la mission ouverte de l’UCKK.',
                    'type' => 'boundary',
                ],
            ],
            'notices' => [
                [
                    'title' => 'Règle publique',
                    'body' => 'Les pages publiques doivent favoriser l’accès au savoir sans exposer de données personnelles, de dossiers privés ou de traces non nécessaires.',
                    'type' => 'integrity',
                ],
            ],
        ];
    }
}