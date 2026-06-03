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
 * Public Médiathèque page definition for local_uckk.
 *
 * This class owns the public Médiathèque page content definition.
 * Media records, permissions, public filtering, rights, content advisories
 * and cultural protocol decisions remain owned by mod_uckkarchive.
 *
 * It must not:
 * - query media tables;
 * - expose private archive fields;
 * - decide access rights;
 * - bypass content advisories;
 * - bypass cultural protocols;
 * - build private file URLs;
 * - mutate Moodle data.
 *
 * @package    local_uckk
 * @copyright  2026 Univers-Cité King Klown
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_uckk\local\public_pages;

defined('MOODLE_INTERNAL') || die();

/**
 * Public Médiathèque page definition.
 *
 * @package local_uckk
 */
final class mediatheque {
    /**
     * Médiathèque explorer DOM id.
     */
    private const EXPLORER_ID = 'local-uckk-mediatheque-explorer';

    /**
     * Public Médiathèque search service.
     */
    private const SEARCH_SERVICE = 'mod_uckkarchive_search_mediatheque';

    /**
     * Return the public Médiathèque page definition.
     *
     * @return array<string, mixed>
     */
    public static function definition(): array {
        return [
            'layout' => 'wide',
            'typography' => 'institutional',
            'eyebrow' => 'Archives publiques',
            'title' => 'Médiathèque UCKK',
            'subtitle' => 'Explorer les médias, collections, œuvres externes et passages documentés.',
            'summary' => 'La Médiathèque donne accès aux médias publics, collections, œuvres externes et passages documentés de l’archive UCKK, avec filtrage par droits, avis de contenu et protocoles culturels.',
            'cardsheading' => 'Explorer la Médiathèque',

            'has_mediatheque_explorer' => true,
            'mediatheque_explorer_id' => self::EXPLORER_ID,
            'mediatheque_initial_state' => [
                'rootId' => self::EXPLORER_ID,
                'service' => self::SEARCH_SERVICE,
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
                ],
                'page' => 1,
                'perpage' => 12,
                'sort' => 'relevance',
                'sitewide' => true,
            ],

            'mediatheque' => [
                'explorer' => [
                    'key' => 'mediatheque_explorer',
                    'title' => 'Explorateur Médiathèque',
                    'service' => self::SEARCH_SERVICE,
                    'surface' => 'local_uckk',
                    'dataowner' => 'mod_uckkarchive',
                ],
            ],

            'quicklinks' => [
                [
                    'label' => 'Rechercher',
                    'description' => 'Chercher dans les médias publics et les collections accessibles.',
                    'url' => '/local/uckk/mediatheque.php',
                ],
                [
                    'label' => 'Archives',
                    'description' => 'Revenir aux repères généraux d’archive publique.',
                    'url' => '/local/uckk/archives.php',
                ],
            ],

            'sections' => [
                [
                    'type' => 'orientation',
                    'eyebrow' => 'Exploration',
                    'title' => 'Naviguer dans les médias publics',
                    'body' => 'L’explorateur permet de chercher, filtrer et ouvrir les fiches publiques sans exposer les fichiers originaux ni les métadonnées privées.',
                    'items' => [
                        'Recherche par texte, format, source, collection et mot-clé.',
                        'Affichage des médias publics seulement.',
                        'Respect des droits, avis de contenu et protocoles culturels.',
                    ],
                ],
                [
                    'type' => 'boundary',
                    'eyebrow' => 'Responsabilité',
                    'title' => 'Ce que la Médiathèque ne publie pas',
                    'body' => 'Les médias restreints, brouillons, notes internes, protocoles culturels privés, fichiers originaux non autorisés et métadonnées sensibles restent contrôlés par mod_uckkarchive.',
                ],
            ],

            'cards' => [
                [
                    'title' => 'Médias',
                    'body' => 'Voir les vidéos, sons, images, documents et livres publics filtrés.',
                    'type' => 'media',
                ],
                [
                    'title' => 'Collections',
                    'body' => 'Parcourir les regroupements publics sans dupliquer le moteur média interne.',
                    'type' => 'collection',
                ],
                [
                    'title' => 'Passages documentés',
                    'body' => 'Pointer vers des moments, pages ou segments quand ils peuvent être exposés publiquement.',
                    'type' => 'marker',
                ],
            ],

            'notices' => [
                [
                    'title' => 'Données et politiques',
                    'body' => 'La page publique appartient à local_uckk, mais les médias, droits, avis de contenu et protocoles culturels sont filtrés par mod_uckkarchive.',
                    'type' => 'institutional',
                ],
                [
                    'title' => 'Accès responsable',
                    'body' => 'Certains contenus peuvent être masqués ou résumés selon les droits, les avis de contenu et les protocoles culturels.',
                    'type' => 'light',
                ],
            ],

            'metadata' => [
                [
                    'label' => 'Surface publique',
                    'value' => 'local_uckk',
                ],
                [
                    'label' => 'Données et politiques',
                    'value' => 'mod_uckkarchive',
                ],
                [
                    'label' => 'Service AJAX',
                    'value' => self::SEARCH_SERVICE,
                ],
                [
                    'label' => 'Portée par défaut',
                    'value' => 'site-wide public',
                ],
            ],

            'cta' => [
                'title' => 'Explorer les médias publics',
                'body' => 'Lance une recherche ou filtre la Médiathèque selon le format, la source, les collections et les avis publics.',
                'url' => '/local/uckk/mediatheque.php',
                'label' => 'Ouvrir la Médiathèque',
            ],
        ];
    }
}