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
 * Public catalogue page definition for local_uckk.
 *
 * This class owns the public page content definition for the UCKK Médiathèque.
 * Media records, permissions, public filtering, rights, content advisories
 * and cultural protocol decisions remain owned by mod_uckkarchive.
 *
 * It must not:
 * - query media tables;
 * - expose private internal fields;
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
 * Public catalogue page definition.
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
     * Return the public catalogue page definition.
     *
     * @return array<string, mixed>
     */
    public static function definition(): array {
        return [
            'layout' => 'wide',
            'typography' => 'institutional',

            'eyebrow' => 'Catalogue public',
            'title' => 'Médiathèque UCKK',
            'subtitle' => 'Explorer les médias, collections, références externes et passages documentés.',
            'summary' => 'Le catalogue donne accès aux contenus publics autorisés : vidéos, sons, images, documents, collections, références externes et extraits contextualisés. Les résultats sont filtrés selon les droits, les avis de contenu, la visibilité et les protocoles culturels.',
            'cardsheading' => 'Parcourir',

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
                    'item' => '',
                ],
                'page' => 1,
                'perpage' => 12,
                'sort' => 'relevance',
                'sitewide' => true,
            ],

            'mediatheque' => [
                'explorer' => [
                    'key' => 'mediatheque_explorer',
                    'title' => 'Recherche publique',
                    'service' => self::SEARCH_SERVICE,
                    'surface' => 'local_uckk',
                    'dataowner' => 'mod_uckkarchive',
                ],
            ],

            'quicklinks' => [
                [
                    'label' => 'Rechercher',
                    'description' => 'Trouver des médias publics, collections accessibles, références externes et passages documentés.',
                    'url' => '/local/uckk/mediatheque.php',
                ],
            ],

            'sections' => [
                [
                    'type' => 'orientation',
                    'eyebrow' => 'Exploration',
                    'title' => 'Naviguer dans les contenus publics',
                    'body' => 'L’explorateur permet de chercher, filtrer et ouvrir les fiches publiques sans exposer les fichiers originaux, les brouillons, les notes internes ni les métadonnées privées.',
                    'items' => [
                        'Recherche par texte, format, source, collection et mot-clé.',
                        'Affichage limité aux contenus publiables.',
                        'Respect des droits, avis de contenu et protocoles culturels.',
                    ],
                ],
                [
                    'type' => 'boundary',
                    'eyebrow' => 'Responsabilité',
                    'title' => 'Ce qui reste protégé',
                    'body' => 'Les médias restreints, brouillons, notes internes, protocoles culturels privés, fichiers originaux non autorisés et métadonnées sensibles restent contrôlés par le moteur média institutionnel.',
                ],
            ],

            'cards' => [
                [
                    'title' => 'Médias',
                    'body' => 'Voir les vidéos, sons, images, documents et références publiques filtrées.',
                    'type' => 'media',
                ],
                [
                    'title' => 'Collections',
                    'body' => 'Explorer les regroupements publics sans dupliquer le moteur média institutionnel.',
                    'type' => 'collection',
                ],
                [
                    'title' => 'Passages documentés',
                    'body' => 'Accéder à des moments, pages ou segments lorsque leur exposition publique est autorisée.',
                    'type' => 'marker',
                ],
            ],

            'notices' => [
                [
                    'title' => 'Données et politiques',
                    'body' => 'Cette page est une surface publique de local_uckk. Les médias, droits, avis de contenu, protocoles culturels et règles de visibilité sont filtrés par le moteur média institutionnel.',
                    'type' => 'institutional',
                ],
                [
                    'title' => 'Accès responsable',
                    'body' => 'Certains contenus peuvent être masqués, résumés ou limités selon les droits, les avis de contenu, les protocoles culturels et les permissions disponibles.',
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
                    'value' => 'Moteur média institutionnel',
                ],
                [
                    'label' => 'Service',
                    'value' => 'Recherche du catalogue public',
                ],
                [
                    'label' => 'Portée par défaut',
                    'value' => 'Contenus publics du site',
                ],
            ],

            'cta' => [
                'title' => 'Explorer les contenus publics',
                'body' => 'Lance une recherche ou applique un filtre pour explorer les contenus publics selon le format, la source, la collection, les droits et les avis disponibles.',
                'url' => '/local/uckk/mediatheque.php',
                'label' => 'Ouvrir la recherche',
            ],
        ];
    }
}