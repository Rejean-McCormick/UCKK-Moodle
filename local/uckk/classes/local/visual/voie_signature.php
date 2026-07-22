<?php
// This file is part of Moodle - https://moodle.org/.
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

/**
 * Visual signatures for UCKK Voies.
 *
 * This class provides a conservative, UI-only mapping between existing UCKK
 * identifiers and CSS classes used by public cards.
 *
 * It does not:
 * - modify Faculty pages;
 * - modify Atlas JSON;
 * - modify Faculty JSON;
 * - write Moodle data;
 * - grant permissions;
 * - decide public visibility;
 * - expose private course or user data.
 *
 * @package    local_uckk
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace local_uckk\local\visual;

defined('MOODLE_INTERNAL') || die();

/**
 * Voie visual signature helper.
 */
final class voie_signature {
    /**
     * Canonical fallback signature.
     */
    private const FALLBACK = [
        'code' => '',
        'slug' => 'unknown',
        'voie_id' => '',
        'label' => '',
        'symbol' => '◆',
        'known' => false,
    ];

    /**
     * Canonical visual map by UCKK course/category code.
     *
     * Codes mirror the existing UCKK identifiers:
     * - category idnumbers such as UCKK-GJS, UCKK-EC, UCKK-ECL;
     * - course idnumbers / shortnames such as GJS101, EC203, IA105;
     * - program/faculty short codes when available.
     */
    private const MAP = [
        'GJS' => [
            'slug' => 'grand-jeu-social',
            'voie_id' => 'voie_grand_jeu_social',
            'label' => 'Grand Jeu social',
            'symbol' => '♟',
        ],
        'EC' => [
            'slug' => 'economie',
            'voie_id' => 'voie_economie',
            'label' => 'Économie',
            'symbol' => '◇',
        ],
        'ECL' => [
            'slug' => 'ecologie',
            'voie_id' => 'voie_ecologie',
            'label' => 'Écologie',
            'symbol' => '✦',
        ],
        'SP' => [
            'slug' => 'sciences-politiques',
            'voie_id' => 'voie_sciences_politiques',
            'label' => 'Sciences politiques',
            'symbol' => '⚖',
        ],
        'LI' => [
            'slug' => 'linguistique-architecture-du-sens',
            'voie_id' => 'voie_linguistique_architecture_du_sens',
            'label' => 'Linguistique & architecture du sens',
            'symbol' => '⌁',
        ],
        'ME' => [
            'slug' => 'metaphysique',
            'voie_id' => 'voie_metaphysique',
            'label' => 'Métaphysique',
            'symbol' => '◎',
        ],
        'IA' => [
            'slug' => 'ia-gouvernable',
            'voie_id' => 'voie_ia_gouvernable',
            'label' => 'IA gouvernable',
            'symbol' => '◈',
        ],
        'IS' => [
            'slug' => 'intervention-sociale-systemes-humains',
            'voie_id' => 'voie_intervention_sociale_systemes_humains',
            'label' => 'Intervention sociale & systèmes humains',
            'symbol' => '◌',
        ],
        'AS' => [
            'slug' => 'architecture-sociotechnique',
            'voie_id' => 'voie_architecture_sociotechnique',
            'label' => 'Architecture sociotechnique',
            'symbol' => '⌬',
        ],
        'KOA' => [
            'slug' => 'ecosysteme-digital-koa',
            'voie_id' => 'voie_ecosysteme_digital_koa',
            'label' => 'Architecture du kOA Digital Ecosystem',
            'symbol' => '⬡',
        ],
    ];

    /**
     * Return all canonical signatures.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function all(): array {
        $items = [];

        foreach (self::MAP as $code => $record) {
            $items[$code] = self::build_signature($code, $record);
        }

        return $items;
    }

    /**
     * Resolve a signature from a UCKK code.
     *
     * @param string $code UCKK code, for example GJS, EC, ECL, IA.
     * @return array<string, mixed>
     */
    public static function from_code(string $code): array {
        $code = self::normalize_code($code);

        if ($code === '' || !isset(self::MAP[$code])) {
            return self::fallback();
        }

        return self::build_signature($code, self::MAP[$code]);
    }

    /**
     * Resolve a signature from a canonical voie_id.
     *
     * @param string $voieid Canonical voie_id.
     * @return array<string, mixed>
     */
    public static function from_voie_id(string $voieid): array {
        $voieid = trim($voieid);

        if ($voieid === '') {
            return self::fallback();
        }

        foreach (self::MAP as $code => $record) {
            if (($record['voie_id'] ?? '') === $voieid) {
                return self::build_signature($code, $record);
            }
        }

        return self::fallback();
    }

    /**
     * Resolve a signature from a canonical faculty/voie slug.
     *
     * @param string $slug Faculty or Voie slug.
     * @return array<string, mixed>
     */
    public static function from_slug(string $slug): array {
        $slug = self::normalize_slug($slug);

        if ($slug === '') {
            return self::fallback();
        }

        foreach (self::MAP as $code => $record) {
            if (($record['slug'] ?? '') === $slug) {
                return self::build_signature($code, $record);
            }
        }

        return self::fallback();
    }

    /**
     * Resolve a signature from mixed course/program/category identifiers.
     *
     * This is intentionally tolerant because public cards may come from several
     * sources: program registry records, Moodle course records, category records,
     * AJAX results, or hand-built public page definitions.
     *
     * @param string $primary First identifier, usually shortname, idnumber, code, or slug.
     * @param string $categoryidnumber Moodle category idnumber, for example UCKK-GJS.
     * @param string $title Public title/fullname.
     * @param string $categoryname Moodle category name.
     * @return array<string, mixed>
     */
    public static function from_identifiers(
        string $primary = '',
        string $categoryidnumber = '',
        string $title = '',
        string $categoryname = ''
    ): array {
        $candidates = [
            $primary,
            $categoryidnumber,
            $title,
            $categoryname,
        ];

        foreach ($candidates as $candidate) {
            $signature = self::from_slug($candidate);
            if (!empty($signature['known'])) {
                return $signature;
            }
        }

        foreach ($candidates as $candidate) {
            $code = self::code_from_identifier($candidate);

            if ($code !== '') {
                $signature = self::from_code($code);

                if (!empty($signature['known'])) {
                    return $signature;
                }
            }
        }

        foreach ($candidates as $candidate) {
            $signature = self::from_text_label($candidate);

            if (!empty($signature['known'])) {
                return $signature;
            }
        }

        return self::fallback();
    }

    /**
     * Build CSS classes for a public card.
     *
     * @param array<string, mixed> $signature Signature returned by this class.
     * @param string $kind Card kind: faculty, program, course, or generic.
     * @param string $extra Additional class list.
     * @return string
     */
    public static function card_classes(array $signature, string $kind = 'generic', string $extra = ''): string {
        $slug = self::normalize_slug((string)($signature['slug'] ?? ''));
        $known = !empty($signature['known']);

        $classes = [
            'local-uckk-public-card',
            'local-uckk-public-card--voie-aware',
        ];

        switch (self::normalize_kind($kind)) {
            case 'faculty':
                $classes[] = 'local-uckk-faculty-link-card';
                $classes[] = 'local-uckk-voie-card';
                break;

            case 'program':
                $classes[] = 'local-uckk-public-card--program';
                $classes[] = 'local-uckk-voie-card';
                break;

            case 'course':
                $classes[] = 'local-uckk-public-card--course';
                $classes[] = 'local-uckk-course-card';
                $classes[] = 'local-uckk-course-card--voie';
                break;

            default:
                $classes[] = 'local-uckk-voie-card';
                break;
        }

        if ($known && $slug !== '') {
            $classes[] = 'local-uckk-voie--' . $slug;
            $classes[] = 'local-uckk-public-card--voie-' . $slug;

            if (self::normalize_kind($kind) === 'course') {
                $classes[] = 'local-uckk-course-card--voie-' . $slug;
            } else {
                $classes[] = 'local-uckk-voie-card--' . $slug;
            }
        } else {
            $classes[] = 'local-uckk-voie--unknown';

            if (self::normalize_kind($kind) === 'course') {
                $classes[] = 'local-uckk-course-card--voie-unknown';
            } else {
                $classes[] = 'local-uckk-voie-card--unknown';
            }
        }

        foreach (preg_split('/\s+/', trim($extra)) ?: [] as $class) {
            $class = self::clean_css_class($class);

            if ($class !== '') {
                $classes[] = $class;
            }
        }

        return implode(' ', array_values(array_unique(array_filter($classes))));
    }

    /**
     * Return the public card symbol for a signature.
     *
     * @param array<string, mixed> $signature Signature returned by this class.
     * @return string
     */
    public static function symbol(array $signature): string {
        $symbol = trim((string)($signature['symbol'] ?? ''));

        return $symbol !== '' ? $symbol : self::FALLBACK['symbol'];
    }

    /**
     * Return the canonical slug for a signature.
     *
     * @param array<string, mixed> $signature Signature returned by this class.
     * @return string
     */
    public static function slug(array $signature): string {
        return self::normalize_slug((string)($signature['slug'] ?? ''));
    }

    /**
     * Return the canonical voie_id for a signature.
     *
     * @param array<string, mixed> $signature Signature returned by this class.
     * @return string
     */
    public static function voie_id(array $signature): string {
        return trim((string)($signature['voie_id'] ?? ''));
    }

    /**
     * Build a full signature array.
     *
     * @param string $code Canonical UCKK code.
     * @param array<string, string> $record Map record.
     * @return array<string, mixed>
     */
    private static function build_signature(string $code, array $record): array {
        $code = self::normalize_code($code);
        $slug = self::normalize_slug((string)($record['slug'] ?? ''));

        return [
            'code' => $code,
            'slug' => $slug,
            'voie_id' => trim((string)($record['voie_id'] ?? '')),
            'label' => trim((string)($record['label'] ?? '')),
            'symbol' => trim((string)($record['symbol'] ?? self::FALLBACK['symbol'])),
            'known' => $code !== '' && $slug !== '',
        ];
    }

    /**
     * Return fallback signature.
     *
     * @return array<string, mixed>
     */
    private static function fallback(): array {
        return self::FALLBACK;
    }

    /**
     * Resolve a signature from a human-readable label.
     *
     * @param string $text Raw label.
     * @return array<string, mixed>
     */
    private static function from_text_label(string $text): array {
        $normalized = self::normalize_search_text($text);

        if ($normalized === '') {
            return self::fallback();
        }

        $patterns = [
            'GJS' => [
                'grand jeu social',
                'jeu social',
            ],
            'ECL' => [
                'ecologie',
                'ecologie politique',
                'vivant',
            ],
            'EC' => [
                'economie',
                'economique',
            ],
            'SP' => [
                'sciences politiques',
                'politique',
            ],
            'LI' => [
                'linguistique',
                'architecture du sens',
                'sens',
            ],
            'ME' => [
                'metaphysique',
            ],
            'IA' => [
                'ia gouvernable',
                'intelligence artificielle',
                'gouvernable',
            ],
            'IS' => [
                'intervention sociale',
                'systemes humains',
                'systèmes humains',
            ],
            'AS' => [
                'architecture sociotechnique',
                'sociotechnique',
            ],
            'KOA' => [
                'ecosysteme digital koa',
                'écosystème digital koa',
                'digital ecosystem',
                'koa digital ecosystem',
            ],
        ];

        foreach ($patterns as $code => $needles) {
            foreach ($needles as $needle) {
                if (strpos($normalized, self::normalize_search_text($needle)) !== false) {
                    return self::from_code($code);
                }
            }
        }

        return self::fallback();
    }

    /**
     * Extract a UCKK Voie code from an identifier.
     *
     * @param string $identifier Raw identifier.
     * @return string
     */
    private static function code_from_identifier(string $identifier): string {
        $identifier = trim($identifier);

        if ($identifier === '') {
            return '';
        }

        $upper = strtoupper($identifier);

        // Direct code, for example GJS, EC, ECL, IA.
        $direct = self::normalize_code($upper);
        if ($direct !== '' && isset(self::MAP[$direct])) {
            return $direct;
        }

        // Canonical category idnumber, for example UCKK-GJS.
        if (preg_match('/(?:^|[^A-Z0-9])UCKK[-_ ]?(GJS|ECL|EC|SP|LI|ME|IA|IS|AS|KOA)(?:[^A-Z0-9]|$)/', $upper, $matches)) {
            return self::normalize_code($matches[1]);
        }

        // Course idnumber / shortname prefixes, for example GJS101, UCKK-GJS101, IA205.
        // ECL must appear before EC to avoid matching Écologie as Économie.
        if (preg_match('/(?:^|[^A-Z0-9])(GJS|ECL|EC|SP|LI|ME|IA|IS|AS|KOA)[-_ ]?[0-9]{2,4}[A-Z]?(?:[^A-Z0-9]|$)/', $upper, $matches)) {
            return self::normalize_code($matches[1]);
        }

        // Slightly looser prefix form, for records that carry a code-like token.
        if (preg_match('/(?:^|[^A-Z0-9])(GJS|ECL|EC|SP|LI|ME|IA|IS|AS|KOA)(?:[-_ ][A-Z0-9]+)?(?:[^A-Z0-9]|$)/', $upper, $matches)) {
            return self::normalize_code($matches[1]);
        }

        return '';
    }

    /**
     * Normalize a UCKK code.
     *
     * @param string $code Raw code.
     * @return string
     */
    private static function normalize_code(string $code): string {
        $code = strtoupper(trim($code));
        $code = preg_replace('/[^A-Z]/', '', $code) ?? '';

        return $code;
    }

    /**
     * Normalize a card kind.
     *
     * @param string $kind Raw kind.
     * @return string
     */
    private static function normalize_kind(string $kind): string {
        $kind = strtolower(trim($kind));

        if (in_array($kind, ['faculty', 'program', 'course'], true)) {
            return $kind;
        }

        return 'generic';
    }

    /**
     * Normalize a slug-like value.
     *
     * @param string $slug Raw slug.
     * @return string
     */
    private static function normalize_slug(string $slug): string {
        $slug = trim(strtolower($slug));

        if ($slug === '') {
            return '';
        }

        // Convert canonical voie_id / faculty_id names into public slugs when possible.
        $slug = preg_replace('/^(voie|faculty)_/', '', $slug) ?? $slug;
        $slug = str_replace('_', '-', $slug);

        // Common French accent folding for existing UCKK labels/slugs.
        $slug = strtr($slug, [
            'à' => 'a',
            'â' => 'a',
            'ä' => 'a',
            'á' => 'a',
            'ã' => 'a',
            'å' => 'a',
            'ç' => 'c',
            'é' => 'e',
            'è' => 'e',
            'ê' => 'e',
            'ë' => 'e',
            'í' => 'i',
            'ì' => 'i',
            'î' => 'i',
            'ï' => 'i',
            'ó' => 'o',
            'ò' => 'o',
            'ô' => 'o',
            'ö' => 'o',
            'õ' => 'o',
            'ú' => 'u',
            'ù' => 'u',
            'û' => 'u',
            'ü' => 'u',
            'ÿ' => 'y',
            'œ' => 'oe',
            'æ' => 'ae',
        ]);

        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug) ?? '';
        $slug = trim($slug, '-');

        return $slug;
    }

    /**
     * Normalize free text for label matching.
     *
     * @param string $text Raw text.
     * @return string
     */
    private static function normalize_search_text(string $text): string {
        $text = self::normalize_slug($text);
        $text = str_replace('-', ' ', $text);
        $text = preg_replace('/\s+/', ' ', $text) ?? '';

        return trim($text);
    }

    /**
     * Clean one CSS class token.
     *
     * @param string $class Raw class.
     * @return string
     */
    private static function clean_css_class(string $class): string {
        $class = trim($class);

        if ($class === '') {
            return '';
        }

        $class = preg_replace('/[^A-Za-z0-9_-]/', '', $class) ?? '';

        return $class;
    }
}