<?php
// This file is part of UCKK-Moodle.
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.
//
// UCKK-Moodle supports the technical Moodle implementation of the
// Univers-Cité King Klown.

/**
 * Public programs page definition for local_uckk.
 *
 * This class owns the public page definition for the Voies UCKK page.
 * It may read active public program records for display only.
 *
 * It must not create programs, mutate pathways, enrol users, award recognitions,
 * validate competencies, or make accreditation claims.
 *
 * @package    local_uckk
 * @copyright  2026 Univers-Cité King Klown
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_uckk\local\public_pages;

use moodle_url;

defined('MOODLE_INTERNAL') || die();

/**
 * Public programs page definition.
 *
 * @package local_uckk
 */
final class programs {
    /**
     * Return the public page definition.
     *
     * @return array<string, mixed>
     */
    public static function definition(): array {
        return self::with_program_cards([
            'layout' => 'wide',
            'typography' => 'institutional',
            'eyebrow' => 'Bibliothèque publique vivante',
            'title' => 'Voies UCKK',
            'subtitle' => 'Parcours ouverts pour explorer, relier et pratiquer les savoirs du Grand Jeu social.',
            'summary' => 'Les Voies UCKK organisent la diffusion du savoir en parcours lisibles : cours, repères, pratiques, archives, médiathèque, défis et assemblées. Elles offrent un cadre d’apprentissage familier, modernisé et ouvert pour comprendre, produire, vérifier et agir avec méthode.',
            'sections' => [
                [
                    'type' => 'role',
                    'title' => 'Rôle des voies',
                    'body' => 'Une voie aide à circuler dans la bibliothèque UCKK. Elle relie des cours, des notions, des lectures, des pratiques, des défis, des archives, des Assemblées et des repères de progression.',
                ],
                [
                    'type' => 'registry',
                    'eyebrow' => 'Répertoire public',
                    'title' => 'Voies actives',
                    'body' => 'Les cartes ci-dessous présentent les parcours actuellement ouverts au public. Les éléments en brouillon, cachés ou archivés ne sont pas affichés.',
                ],
            ],
            'cards' => [],
            'notices' => [
                [
                    'body' => 'Les éventuelles reconnaissances UCKK demeurent internes, sauf reconnaissance officielle future.',
                    'type' => 'institutional',
                ],
            ],
            'metadata' => [
                ['label' => 'Source', 'value' => 'Bibliothèque publique UCKK'],
                ['label' => 'Filtre public', 'value' => 'Voies ouvertes seulement'],
            ],
            'cta' => [
                'title' => 'Explorer les cours',
                'body' => 'Les espaces de cours associés aux Voies regroupent les savoirs, lectures, exercices et repères disponibles dans un cadre d’apprentissage familier et modernisé.',
                'url' => '/local/uckk/courses.php',
                'label' => 'Voir les cours ouverts',
            ],
        ]);
    }

    /**
     * Add live program cards from local_uckk_program to the public page definition.
     *
     * @param array<string, mixed> $definition Base page definition.
     * @return array<string, mixed>
     */
    private static function with_program_cards(array $definition): array {
        $cards = self::program_cards('active');

        // Program cards belong inside the public registry section only.
        // Keeping them at page level creates a second generic card section.
        $definition['cards'] = [];
        $definition['cardsheading'] = 'Repères publics';

        if (!isset($definition['sections']) || !is_array($definition['sections'])) {
            $definition['sections'] = [];
        }

        $attached = false;

        foreach ($definition['sections'] as $index => $section) {
            if (!is_array($section)) {
                continue;
            }

            $title = (string)($section['title'] ?? '');
            $eyebrow = (string)($section['eyebrow'] ?? '');

            if (
                $title === 'Répertoire public'
                || $title === 'Voies actives'
                || $title === 'Voies et programmes actifs'
                || $eyebrow === 'Répertoire public'
            ) {
                $definition['sections'][$index]['cards'] = $cards;
                $attached = true;
                break;
            }
        }

        if (!$attached) {
            $definition['sections'][] = [
                'type' => 'registry',
                'eyebrow' => 'Répertoire public',
                'title' => 'Voies actives',
                'body' => 'Ces cartes présentent les voies actuellement ouvertes au public. Les éléments en brouillon ou non publiés ne sont pas affichés ici.',
                'cards' => $cards,
            ];
        }

        if (!isset($definition['metadata']) || !is_array($definition['metadata'])) {
            $definition['metadata'] = [];
        }

        $definition['metadata'][] = [
            'label' => 'Voies actives affichées',
            'value' => (string)count($cards),
        ];

        if (empty($cards)) {
            if (!isset($definition['notices']) || !is_array($definition['notices'])) {
                $definition['notices'] = [];
            }

            $definition['notices'][] = [
                'title' => 'Aucune voie ouverte',
                'body' => 'Aucune voie ouverte n’est actuellement disponible dans la bibliothèque publique UCKK.',
                'type' => 'warning',
            ];
        }

        return $definition;
    }

    /**
     * Build public cards from active UCKK program records.
     *
     * @param string $status Program status to display.
     * @return array<int, array<string, mixed>>
     */
    private static function program_cards(string $status): array {
        global $CFG, $DB;

        if (!isset($CFG) || !isset($DB)) {
            return [];
        }

        if (!class_exists('xmldb_table')) {
            require_once($CFG->libdir . '/xmldb/xmldb_object.php');
        }

        if (!$DB->get_manager()->table_exists(new \xmldb_table('local_uckk_program'))) {
            return [];
        }

        $records = $DB->get_records_sql("
            SELECT
                p.id,
                p.shortname,
                p.fullname,
                p.programtype,
                p.status,
                p.visibility,
                p.sortorder,
                p.categoryid,
                c.name AS categoryname,
                c.idnumber AS categoryidnumber,
                c.visible AS categoryvisible
              FROM {local_uckk_program} p
         LEFT JOIN {course_categories} c ON c.id = p.categoryid
             WHERE p.status = :status
          ORDER BY p.programtype, p.sortorder, p.fullname
        ", [
            'status' => $status,
        ]);

        $cards = [];

        foreach ($records as $record) {
            $shortname = trim((string)($record->shortname ?? ''));
            $fullname = trim((string)($record->fullname ?? ''));
            $programtype = trim((string)($record->programtype ?? ''));
            $categoryname = trim((string)($record->categoryname ?? ''));
            $categoryidnumber = trim((string)($record->categoryidnumber ?? ''));
            $categoryvisible = (int)($record->categoryvisible ?? 0);
            $categoryid = (int)($record->categoryid ?? 0);

            $typelabel = self::program_type_label($programtype);
            $publicidentity = self::public_program_identity($shortname, $fullname, $categoryname, $categoryidnumber);

            if ($publicidentity !== null) {
                $title = $publicidentity['title'];
            } else {
                $title = $fullname !== '' ? $fullname : $shortname;
            }

            if ($title === '') {
                continue;
            }

            $bodyparts = [];

            if ($publicidentity !== null && $publicidentity['body'] !== '') {
                $bodyparts[] = $publicidentity['body'];
            } else {
                if ($typelabel !== '') {
                    $bodyparts[] = 'Parcours : ' . $typelabel . '.';
                }

                if ($categoryname !== '') {
                    $bodyparts[] = 'Cours et espaces d’apprentissage : ' . $categoryname . '.';
                } else {
                    $bodyparts[] = 'Les cours de cette voie seront reliés ici lorsqu’ils seront disponibles.';
                }
            }

            $url = '';
            $actionlabel = '';

            if ($categoryid > 0 && $categoryvisible === 1) {
                $url = (new moodle_url('/course/index.php', ['categoryid' => $categoryid]))->out(false);
                $actionlabel = 'Accéder aux cours';
            }

            $cards[] = [
                'eyebrow' => $publicidentity['eyebrow'] ?? '',
                'title' => $title,
                'body' => implode(' ', $bodyparts),
                'url' => $url,
                'actionlabel' => $actionlabel,
                'type' => $publicidentity['type'] ?? self::clean_modifier($programtype),
            ];
        }

        return $cards;
    }

    /**
     * Public editorial overrides for program cards.
     *
     * This protects public pages from legacy technical labels while keeping
     * existing Moodle, Atlas and database identifiers stable.
     *
     * @param string $shortname Program short name.
     * @param string $fullname Program full name.
     * @param string $categoryname Linked Moodle category name.
     * @param string $categoryidnumber Linked Moodle category idnumber.
     * @return array{eyebrow:string,title:string,body:string,type:string}|null
     */
    private static function public_program_identity(
        string $shortname,
        string $fullname,
        string $categoryname,
        string $categoryidnumber
    ): ?array {
        $uppercode = strtoupper(trim($shortname));
        $uppercategory = strtoupper(trim($categoryidnumber));
        $haystack = strtolower($shortname . ' ' . $fullname . ' ' . $categoryname . ' ' . $categoryidnumber);

        $isia =
            $uppercode === 'IA'
            || $uppercategory === 'UCKK-IA'
            || strpos($haystack, 'voie_ia_gouvernable') !== false
            || strpos($haystack, 'ia-gouvernable') !== false
            || strpos($haystack, 'production augmentée par l’IA') !== false
            || strpos($haystack, 'production augment') !== false
            || strpos($haystack, 'production ia') !== false;

        if ($isia) {
            return [
                'eyebrow' => 'Production IA',
                'title' => 'Voie de la Production augmentée par l’IA',
                'body' => 'Utiliser l’IA comme atelier de production pour écrire, concevoir, coder, documenter, créer des supports visuels, structurer des protocoles d’accompagnement et construire des outils vérifiables, sans déléguer la décision, la responsabilité ou le jugement humain.',
                'type' => 'production-ia',
            ];
        }

        return null;
    }

    /**
     * Human label for a program type.
     *
     * @param string $programtype Program type.
     * @return string
     */
    private static function program_type_label(string $programtype): string {
        $labels = [
            'tronc_commun' => 'Tronc commun',
            'voie_uckk' => 'Voie UCKK',
            'voie_secondaire' => 'Voie secondaire',
            'baccalaureat' => 'Voie UCKK — Puissance opératoire',
            'baccalauréat' => 'Voie UCKK — Puissance opératoire',
            'certificat' => 'Parcours d’initiation',
            'mineure' => 'Voie UCKK — Initiation',
            'seminaire' => 'Séminaire',
            'séminaire' => 'Séminaire',
            'laboratoire' => 'Laboratoire',
            'lab' => 'Laboratoire',
            'atelier' => 'Atelier',
        ];

        if (isset($labels[$programtype])) {
            return $labels[$programtype];
        }

        $label = trim(str_replace('_', ' ', $programtype));

        return $label !== '' ? ucfirst($label) : '';
    }

    /**
     * Clean CSS modifier value.
     *
     * @param mixed $modifier Modifier.
     * @return string
     */
    private static function clean_modifier($modifier): string {
        if (!is_scalar($modifier)) {
            return '';
        }

        $modifier = strtolower(trim((string)$modifier));
        $modifier = preg_replace('/[^a-z0-9_-]+/', '-', $modifier) ?? '';
        $modifier = trim($modifier, '-');

        return $modifier;
    }
}