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
 * Visibility registry for local_uckk.
 *
 * This class defines stable UCKK visibility values used by programs, pathways,
 * profiles, proofs, archive items, challenges, assemblies, integrity summaries
 * and reports.
 *
 * It is intentionally not a permission engine.
 *
 * Moodle capabilities, role assignments and context checks must remain in the
 * API, external, report, activity, tool or renderer layers that need them.
 * This class only normalises, validates, compares and exports visibility values.
 *
 * @package    local_uckk
 * @copyright  2026 Univers-Cité King Klown
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_uckk\local;

use invalid_parameter_exception;
use stdClass;

defined('MOODLE_INTERNAL') || die();

/**
 * UCKK visibility registry.
 *
 * Visibility answers the question:
 *
 * "Who is the intended audience for this object?"
 *
 * It does not answer:
 *
 * "Does this exact Moodle user have permission to access this object?"
 *
 * That second question belongs to Moodle contexts and capabilities.
 *
 * @package local_uckk
 */
final class visibility {
    /** Component name. */
    public const COMPONENT = 'local_uckk';

    /** Private to the owner or explicitly authorized service. */
    public const PRIVATE = 'private';

    /** Visible to the owning user and authorized reviewers. */
    public const USER = 'user';

    /** Visible to a Moodle group or UCKK working group. */
    public const GROUP = 'group';

    /** Visible inside a Moodle course. */
    public const COURSE = 'course';

    /** Visible to a cohort. */
    public const COHORT = 'cohort';

    /** Visible inside a UCKK program. */
    public const PROGRAM = 'program';

    /** Visible institutionally inside UCKK. */
    public const INSTITUTION = 'institution';

    /** Publicly visible. */
    public const PUBLIC = 'public';

    /** Restricted to authorized staff or reviewers. */
    public const RESTRICTED = 'restricted';

    /** Restricted to Inquisiteur / integrity workflows. */
    public const RESTRICTED_INTEGRITY = 'restricted_integrity';

    /** Hidden from normal lists but retained. */
    public const HIDDEN = 'hidden';

    /** Archived and only visible according to archive policy. */
    public const ARCHIVED = 'archived';

    /**
     * Default visibility for most UCKK institutional objects.
     *
     * @var string
     */
    public const DEFAULT = self::INSTITUTION;

    /**
     * Default visibility for proofs and portfolio items.
     *
     * @var string
     */
    public const DEFAULT_PERSONAL = self::USER;

    /**
     * Default visibility for integrity objects.
     *
     * @var string
     */
    public const DEFAULT_INTEGRITY = self::RESTRICTED_INTEGRITY;

    /**
     * Default visibility for public catalogue records.
     *
     * @var string
     */
    public const DEFAULT_CATALOGUE = self::PUBLIC;

    /**
     * Prevent instantiation.
     */
    private function __construct() {
    }

    /**
     * Return all visibility values in canonical order.
     *
     * Order is from most restricted to most open, except ARCHIVED which is a
     * retention state and HIDDEN which is a display state.
     *
     * @return string[]
     */
    public static function all(): array {
        return [
            self::RESTRICTED_INTEGRITY,
            self::RESTRICTED,
            self::PRIVATE,
            self::USER,
            self::GROUP,
            self::COURSE,
            self::COHORT,
            self::PROGRAM,
            self::INSTITUTION,
            self::PUBLIC,
            self::HIDDEN,
            self::ARCHIVED,
        ];
    }

    /**
     * Return values suitable for ordinary user-created learning objects.
     *
     * @return string[]
     */
    public static function user_selectable(): array {
        return [
            self::PRIVATE,
            self::USER,
            self::GROUP,
            self::COURSE,
            self::COHORT,
            self::PROGRAM,
            self::INSTITUTION,
        ];
    }

    /**
     * Return values suitable for public catalogue/admin configuration.
     *
     * @return string[]
     */
    public static function catalogue_selectable(): array {
        return [
            self::HIDDEN,
            self::INSTITUTION,
            self::PUBLIC,
            self::ARCHIVED,
        ];
    }

    /**
     * Return restricted visibility values.
     *
     * @return string[]
     */
    public static function restricted_values(): array {
        return [
            self::RESTRICTED_INTEGRITY,
            self::RESTRICTED,
            self::PRIVATE,
            self::HIDDEN,
        ];
    }

    /**
     * Return values that may be shown outside a single course.
     *
     * @return string[]
     */
    public static function broad_values(): array {
        return [
            self::COHORT,
            self::PROGRAM,
            self::INSTITUTION,
            self::PUBLIC,
        ];
    }

    /**
     * Return values considered public or externally publishable.
     *
     * @return string[]
     */
    public static function public_values(): array {
        return [
            self::PUBLIC,
        ];
    }

    /**
     * Return a stable rank for comparison.
     *
     * Lower number means more restricted.
     * Higher number means more open.
     *
     * HIDDEN and ARCHIVED are intentionally conservative.
     *
     * @return array<string, int>
     */
    public static function ranks(): array {
        return [
            self::RESTRICTED_INTEGRITY => 0,
            self::RESTRICTED => 1,
            self::PRIVATE => 2,
            self::USER => 3,
            self::GROUP => 4,
            self::COURSE => 5,
            self::COHORT => 6,
            self::PROGRAM => 7,
            self::INSTITUTION => 8,
            self::PUBLIC => 9,
            self::HIDDEN => 1,
            self::ARCHIVED => 2,
        ];
    }

    /**
     * Return Moodle context level normally associated with a visibility value.
     *
     * This is a hint for service classes and privacy providers. It does not
     * grant access and does not replace capability checks.
     *
     * @param string $visibility Visibility value.
     * @return int Moodle CONTEXT_* constant.
     */
    public static function contextlevel_for(string $visibility): int {
        $visibility = self::normalise($visibility);

        switch ($visibility) {
            case self::PRIVATE:
            case self::USER:
                return CONTEXT_USER;

            case self::GROUP:
            case self::COURSE:
                return CONTEXT_COURSE;

            case self::COHORT:
            case self::PROGRAM:
                return CONTEXT_COURSECAT;

            case self::INSTITUTION:
            case self::PUBLIC:
            case self::RESTRICTED:
            case self::RESTRICTED_INTEGRITY:
            case self::HIDDEN:
            case self::ARCHIVED:
            default:
                return CONTEXT_SYSTEM;
        }
    }

    /**
     * Return a readable Moodle context level name.
     *
     * @param string $visibility Visibility value.
     * @return string
     */
    public static function contextlevel_name_for(string $visibility): string {
        $level = self::contextlevel_for($visibility);

        switch ($level) {
            case CONTEXT_USER:
                return 'CONTEXT_USER';

            case CONTEXT_COURSECAT:
                return 'CONTEXT_COURSECAT';

            case CONTEXT_COURSE:
                return 'CONTEXT_COURSE';

            case CONTEXT_MODULE:
                return 'CONTEXT_MODULE';

            case CONTEXT_BLOCK:
                return 'CONTEXT_BLOCK';

            case CONTEXT_SYSTEM:
            default:
                return 'CONTEXT_SYSTEM';
        }
    }

    /**
     * Normalise a visibility value.
     *
     * @param mixed $visibility Raw visibility.
     * @param string $default Default value used when raw value is empty.
     * @return string
     */
    public static function normalise($visibility, string $default = self::DEFAULT): string {
        $value = trim((string)$visibility);

        if ($value === '') {
            $value = $default;
        }

        $value = \core_text::strtolower($value);
        $value = str_replace([' ', '-', '.', '/', '\\'], '_', $value);
        $value = clean_param($value, PARAM_ALPHANUMEXT);
        $value = preg_replace('/_+/', '_', $value) ?? '';
        $value = trim($value, '_');

        $aliases = self::aliases();

        if (array_key_exists($value, $aliases)) {
            $value = $aliases[$value];
        }

        return $value;
    }

    /**
     * Return known aliases.
     *
     * @return array<string, string>
     */
    public static function aliases(): array {
        return [
            'me' => self::USER,
            'owner' => self::USER,
            'personal' => self::USER,
            'self' => self::USER,
            'only_me' => self::PRIVATE,
            'private_owner' => self::PRIVATE,
            'team' => self::GROUP,
            'workgroup' => self::GROUP,
            'class' => self::COURSE,
            'course_visible' => self::COURSE,
            'cohorte' => self::COHORT,
            'programme' => self::PROGRAM,
            'uckk' => self::INSTITUTION,
            'institutional' => self::INSTITUTION,
            'institutionnelle' => self::INSTITUTION,
            'publicly' => self::PUBLIC,
            'published' => self::PUBLIC,
            'visible_public' => self::PUBLIC,
            'staff' => self::RESTRICTED,
            'restricted_staff' => self::RESTRICTED,
            'integrity' => self::RESTRICTED_INTEGRITY,
            'inquisiteur' => self::RESTRICTED_INTEGRITY,
            'inquisitor' => self::RESTRICTED_INTEGRITY,
            'integrity_restricted' => self::RESTRICTED_INTEGRITY,
            'invisible' => self::HIDDEN,
            'retained' => self::ARCHIVED,
        ];
    }

    /**
     * Validate a visibility value.
     *
     * @param mixed $visibility Raw visibility.
     * @return bool
     */
    public static function is_valid($visibility): bool {
        return in_array(self::normalise($visibility), self::all(), true);
    }

    /**
     * Require a valid visibility value and return its normalised form.
     *
     * @param mixed $visibility Raw visibility.
     * @param string $default Default value.
     * @return string
     */
    public static function require_valid($visibility, string $default = self::DEFAULT): string {
        $visibility = self::normalise($visibility, $default);

        if (!in_array($visibility, self::all(), true)) {
            throw new invalid_parameter_exception('Invalid UCKK visibility value.');
        }

        return $visibility;
    }

    /**
     * Determine whether the value is public.
     *
     * @param mixed $visibility Raw visibility.
     * @return bool
     */
    public static function is_public($visibility): bool {
        return self::normalise($visibility) === self::PUBLIC;
    }

    /**
     * Determine whether the value is institution-visible.
     *
     * @param mixed $visibility Raw visibility.
     * @return bool
     */
    public static function is_institutional($visibility): bool {
        return in_array(self::normalise($visibility), [self::INSTITUTION, self::PUBLIC], true);
    }

    /**
     * Determine whether the value is restricted.
     *
     * @param mixed $visibility Raw visibility.
     * @return bool
     */
    public static function is_restricted($visibility): bool {
        return in_array(self::normalise($visibility), self::restricted_values(), true);
    }

    /**
     * Determine whether the value is explicitly integrity-restricted.
     *
     * @param mixed $visibility Raw visibility.
     * @return bool
     */
    public static function is_integrity_restricted($visibility): bool {
        return self::normalise($visibility) === self::RESTRICTED_INTEGRITY;
    }

    /**
     * Determine whether the value is private.
     *
     * @param mixed $visibility Raw visibility.
     * @return bool
     */
    public static function is_private($visibility): bool {
        return self::normalise($visibility) === self::PRIVATE;
    }

    /**
     * Determine whether the value is hidden.
     *
     * @param mixed $visibility Raw visibility.
     * @return bool
     */
    public static function is_hidden($visibility): bool {
        return self::normalise($visibility) === self::HIDDEN;
    }

    /**
     * Determine whether the value is archived.
     *
     * @param mixed $visibility Raw visibility.
     * @return bool
     */
    public static function is_archived($visibility): bool {
        return self::normalise($visibility) === self::ARCHIVED;
    }

    /**
     * Determine whether the value is suitable for public catalogue listing.
     *
     * @param mixed $visibility Raw visibility.
     * @return bool
     */
    public static function allows_catalogue_listing($visibility): bool {
        return in_array(self::normalise($visibility), [self::INSTITUTION, self::PUBLIC], true);
    }

    /**
     * Determine whether the value may be indexed by public search.
     *
     * @param mixed $visibility Raw visibility.
     * @return bool
     */
    public static function allows_public_indexing($visibility): bool {
        return self::normalise($visibility) === self::PUBLIC;
    }

    /**
     * Determine whether the value should be excluded from ordinary lists.
     *
     * @param mixed $visibility Raw visibility.
     * @return bool
     */
    public static function should_hide_from_lists($visibility): bool {
        return in_array(self::normalise($visibility), [
            self::HIDDEN,
            self::RESTRICTED,
            self::RESTRICTED_INTEGRITY,
            self::PRIVATE,
        ], true);
    }

    /**
     * Determine whether the value should be exportable in institutional archives.
     *
     * This is a policy hint only. Privacy providers and archive services must
     * still apply Moodle privacy and capability rules.
     *
     * @param mixed $visibility Raw visibility.
     * @return bool
     */
    public static function can_be_institutionally_exported($visibility): bool {
        return in_array(self::normalise($visibility), [
            self::COURSE,
            self::COHORT,
            self::PROGRAM,
            self::INSTITUTION,
            self::PUBLIC,
            self::ARCHIVED,
        ], true);
    }

    /**
     * Determine whether a visibility requires an explicit provenance record.
     *
     * UCKK treats broader visibility as requiring stronger provenance.
     *
     * @param mixed $visibility Raw visibility.
     * @return bool
     */
    public static function requires_provenance($visibility): bool {
        return in_array(self::normalise($visibility), [
            self::COURSE,
            self::COHORT,
            self::PROGRAM,
            self::INSTITUTION,
            self::PUBLIC,
            self::ARCHIVED,
            self::RESTRICTED_INTEGRITY,
        ], true);
    }

    /**
     * Determine whether a visibility requires human validation before publication.
     *
     * @param mixed $visibility Raw visibility.
     * @return bool
     */
    public static function requires_human_validation($visibility): bool {
        return in_array(self::normalise($visibility), [
            self::PROGRAM,
            self::INSTITUTION,
            self::PUBLIC,
            self::ARCHIVED,
        ], true);
    }

    /**
     * Determine whether a visibility should be treated as privacy-sensitive.
     *
     * @param mixed $visibility Raw visibility.
     * @return bool
     */
    public static function is_privacy_sensitive($visibility): bool {
        return in_array(self::normalise($visibility), [
            self::PRIVATE,
            self::USER,
            self::GROUP,
            self::RESTRICTED,
            self::RESTRICTED_INTEGRITY,
        ], true);
    }

    /**
     * Compare two visibility values.
     *
     * Returns:
     * - -1 when $a is more restricted than $b;
     * - 0 when they have the same rank;
     * - 1 when $a is more open than $b.
     *
     * @param mixed $a First visibility.
     * @param mixed $b Second visibility.
     * @return int
     */
    public static function compare($a, $b): int {
        $a = self::require_valid($a);
        $b = self::require_valid($b);

        $ranks = self::ranks();
        $ranka = $ranks[$a] ?? 0;
        $rankb = $ranks[$b] ?? 0;

        if ($ranka === $rankb) {
            return 0;
        }

        return $ranka < $rankb ? -1 : 1;
    }

    /**
     * Determine whether $a is at least as restrictive as $b.
     *
     * @param mixed $a First visibility.
     * @param mixed $b Second visibility.
     * @return bool
     */
    public static function is_at_least_as_restrictive_as($a, $b): bool {
        return self::compare($a, $b) <= 0;
    }

    /**
     * Determine whether $a is at least as open as $b.
     *
     * @param mixed $a First visibility.
     * @param mixed $b Second visibility.
     * @return bool
     */
    public static function is_at_least_as_open_as($a, $b): bool {
        return self::compare($a, $b) >= 0;
    }

    /**
     * Choose the most restrictive visibility from a list.
     *
     * @param array<int, mixed> $values Visibility values.
     * @param string $default Default when list is empty.
     * @return string
     */
    public static function most_restrictive(array $values, string $default = self::DEFAULT): string {
        if (empty($values)) {
            return self::require_valid($default);
        }

        $selected = null;

        foreach ($values as $value) {
            $value = self::require_valid($value, $default);

            if ($selected === null || self::compare($value, $selected) < 0) {
                $selected = $value;
            }
        }

        return $selected ?? self::require_valid($default);
    }

    /**
     * Choose the most open visibility from a list.
     *
     * @param array<int, mixed> $values Visibility values.
     * @param string $default Default when list is empty.
     * @return string
     */
    public static function most_open(array $values, string $default = self::DEFAULT): string {
        if (empty($values)) {
            return self::require_valid($default);
        }

        $selected = null;

        foreach ($values as $value) {
            $value = self::require_valid($value, $default);

            if ($selected === null || self::compare($value, $selected) > 0) {
                $selected = $value;
            }
        }

        return $selected ?? self::require_valid($default);
    }

    /**
     * Restrict a requested visibility by a maximum allowed visibility.
     *
     * If the requested value is more open than the maximum, the maximum wins.
     *
     * @param mixed $requested Requested visibility.
     * @param mixed $maximum Maximum allowed visibility.
     * @return string
     */
    public static function clamp_to_maximum($requested, $maximum): string {
        $requested = self::require_valid($requested);
        $maximum = self::require_valid($maximum);

        if (self::compare($requested, $maximum) > 0) {
            return $maximum;
        }

        return $requested;
    }

    /**
     * Return a display label.
     *
     * @param mixed $visibility Raw visibility.
     * @return string
     */
    public static function label($visibility): string {
        $visibility = self::require_valid($visibility);
        $identifier = 'visibility_' . str_replace('-', '', $visibility);

        if (get_string_manager()->string_exists($identifier, self::COMPONENT)) {
            return get_string($identifier, self::COMPONENT);
        }

        $fallbacks = [
            self::PRIVATE => 'Privé',
            self::USER => 'Utilisateur',
            self::GROUP => 'Groupe',
            self::COURSE => 'Cours',
            self::COHORT => 'Cohorte',
            self::PROGRAM => 'Programme',
            self::INSTITUTION => 'Institution',
            self::PUBLIC => 'Public',
            self::RESTRICTED => 'Restreint',
            self::RESTRICTED_INTEGRITY => 'Intégrité restreinte',
            self::HIDDEN => 'Masqué',
            self::ARCHIVED => 'Archivé',
        ];

        return $fallbacks[$visibility] ?? ucfirst(str_replace('_', ' ', $visibility));
    }

    /**
     * Return a short description.
     *
     * @param mixed $visibility Raw visibility.
     * @return string
     */
    public static function description($visibility): string {
        $visibility = self::require_valid($visibility);
        $identifier = 'visibility_' . str_replace('-', '', $visibility) . '_desc';

        if (get_string_manager()->string_exists($identifier, self::COMPONENT)) {
            return get_string($identifier, self::COMPONENT);
        }

        $fallbacks = [
            self::PRIVATE => 'Visible uniquement par le propriétaire ou les services explicitement autorisés.',
            self::USER => 'Visible par l’utilisateur concerné et les personnes autorisées à l’accompagner ou l’évaluer.',
            self::GROUP => 'Visible dans un groupe ou une équipe de travail.',
            self::COURSE => 'Visible dans un cours Moodle.',
            self::COHORT => 'Visible dans une cohorte.',
            self::PROGRAM => 'Visible dans un programme UCKK.',
            self::INSTITUTION => 'Visible à l’intérieur de l’institution UCKK.',
            self::PUBLIC => 'Visible publiquement.',
            self::RESTRICTED => 'Visible uniquement par les rôles explicitement autorisés.',
            self::RESTRICTED_INTEGRITY => 'Visible uniquement dans les workflows d’intégrité et d’Inquisiteur.',
            self::HIDDEN => 'Masqué des listes ordinaires, mais conservé.',
            self::ARCHIVED => 'Conservé selon la politique d’archive.',
        ];

        return $fallbacks[$visibility] ?? '';
    }

    /**
     * Return a Bootstrap-compatible badge class.
     *
     * @param mixed $visibility Raw visibility.
     * @return string
     */
    public static function badge_class($visibility): string {
        $visibility = self::require_valid($visibility);

        switch ($visibility) {
            case self::PUBLIC:
                return 'badge badge-primary';

            case self::INSTITUTION:
            case self::PROGRAM:
                return 'badge badge-info';

            case self::COURSE:
            case self::COHORT:
            case self::GROUP:
                return 'badge badge-secondary';

            case self::PRIVATE:
            case self::USER:
                return 'badge badge-dark';

            case self::RESTRICTED:
            case self::RESTRICTED_INTEGRITY:
                return 'badge badge-danger';

            case self::HIDDEN:
                return 'badge badge-warning';

            case self::ARCHIVED:
                return 'badge badge-light border';

            default:
                return 'badge badge-light';
        }
    }

    /**
     * Return UCKK CSS class.
     *
     * @param mixed $visibility Raw visibility.
     * @return string
     */
    public static function css_class($visibility): string {
        $visibility = self::require_valid($visibility);

        return 'uckk-visibility-' . str_replace('_', '-', $visibility);
    }

    /**
     * Export one visibility value.
     *
     * @param mixed $visibility Raw visibility.
     * @return stdClass
     */
    public static function export($visibility): stdClass {
        $visibility = self::require_valid($visibility);

        return (object)[
            'key' => $visibility,
            'label' => self::label($visibility),
            'description' => self::description($visibility),
            'badgeclass' => self::badge_class($visibility),
            'cssclass' => self::css_class($visibility),
            'contextlevel' => self::contextlevel_for($visibility),
            'contextlevelname' => self::contextlevel_name_for($visibility),
            'rank' => self::ranks()[$visibility] ?? 0,
            'public' => self::is_public($visibility),
            'institutional' => self::is_institutional($visibility),
            'restricted' => self::is_restricted($visibility),
            'integrityrestricted' => self::is_integrity_restricted($visibility),
            'privacysensitive' => self::is_privacy_sensitive($visibility),
            'requiresprovenance' => self::requires_provenance($visibility),
            'requireshumanvalidation' => self::requires_human_validation($visibility),
            'cataloguelisting' => self::allows_catalogue_listing($visibility),
            'publicindexing' => self::allows_public_indexing($visibility),
        ];
    }

    /**
     * Export one visibility value for Mustache templates.
     *
     * @param mixed $visibility Raw visibility.
     * @return array<string, mixed>
     */
    public static function export_for_template($visibility): array {
        return (array)self::export($visibility);
    }

    /**
     * Export all visibility values.
     *
     * @param string[]|null $values Optional subset.
     * @return stdClass[]
     */
    public static function export_all(?array $values = null): array {
        $values = $values ?? self::all();
        $out = [];

        foreach ($values as $value) {
            $out[] = self::export($value);
        }

        return $out;
    }

    /**
     * Export all visibility values for a select field.
     *
     * @param string[]|null $values Optional subset.
     * @return array<string, string>
     */
    public static function options(?array $values = null): array {
        $values = $values ?? self::all();
        $options = [];

        foreach ($values as $value) {
            $value = self::require_valid($value);
            $options[$value] = self::label($value);
        }

        return $options;
    }

    /**
     * Export all visibility values for Mustache templates.
     *
     * @param string[]|null $values Optional subset.
     * @param string|null $selected Selected value.
     * @return array<int, array<string, mixed>>
     */
    public static function options_for_template(?array $values = null, ?string $selected = null): array {
        $values = $values ?? self::all();
        $selected = $selected !== null ? self::normalise($selected) : null;
        $out = [];

        foreach ($values as $value) {
            $value = self::require_valid($value);
            $item = self::export_for_template($value);
            $item['selected'] = $selected !== null && $value === $selected;
            $out[] = $item;
        }

        return $out;
    }

    /**
     * Filter a list and keep only valid visibility values.
     *
     * @param array<int, mixed> $values Raw values.
     * @return string[]
     */
    public static function filter_valid(array $values): array {
        $out = [];

        foreach ($values as $value) {
            $value = self::normalise($value);

            if (in_array($value, self::all(), true)) {
                $out[] = $value;
            }
        }

        return array_values(array_unique($out));
    }

    /**
     * Return a safe default for an object type.
     *
     * @param string $objecttype Object type.
     * @return string
     */
    public static function default_for_object_type(string $objecttype): string {
        $objecttype = self::normalise_key($objecttype);

        switch ($objecttype) {
            case 'player':
            case 'profile':
            case 'portfolio':
            case 'portfolio_item':
            case 'reflection':
                return self::DEFAULT_PERSONAL;

            case 'proof':
            case 'submission':
            case 'ai_log':
                return self::USER;

            case 'integrity_case':
            case 'integrity_note':
            case 'inquisiteur_review':
                return self::DEFAULT_INTEGRITY;

            case 'archive_item':
            case 'kristal':
            case 'decision':
            case 'minutes':
                return self::INSTITUTION;

            case 'program':
            case 'pathway':
            case 'course':
            case 'badge':
            case 'competency':
                return self::INSTITUTION;

            case 'public_archive':
            case 'catalogue':
            case 'public_summary':
                return self::PUBLIC;

            default:
                return self::DEFAULT;
        }
    }

    /**
     * Return whether a transition should be considered publication.
     *
     * @param mixed $from Previous visibility.
     * @param mixed $to New visibility.
     * @return bool
     */
    public static function is_publication_transition($from, $to): bool {
        $from = self::require_valid($from);
        $to = self::require_valid($to);

        return !self::is_public($from) && self::is_public($to);
    }

    /**
     * Return whether a transition makes data broader.
     *
     * @param mixed $from Previous visibility.
     * @param mixed $to New visibility.
     * @return bool
     */
    public static function broadens_visibility($from, $to): bool {
        return self::compare($to, $from) > 0;
    }

    /**
     * Return whether a transition narrows data visibility.
     *
     * @param mixed $from Previous visibility.
     * @param mixed $to New visibility.
     * @return bool
     */
    public static function narrows_visibility($from, $to): bool {
        return self::compare($to, $from) < 0;
    }

    /**
     * Return warnings for a visibility transition.
     *
     * This does not prevent the transition. It gives calling services a stable
     * way to produce warnings, logs or validation gates.
     *
     * @param mixed $from Previous visibility.
     * @param mixed $to New visibility.
     * @return array<int, array<string, string>>
     */
    public static function transition_warnings($from, $to): array {
        $from = self::require_valid($from);
        $to = self::require_valid($to);
        $warnings = [];

        if (self::is_publication_transition($from, $to)) {
            $warnings[] = [
                'code' => 'publication',
                'message' => 'This transition publishes the object publicly and should require human validation.',
            ];
        }

        if (self::broadens_visibility($from, $to) && self::is_privacy_sensitive($from)) {
            $warnings[] = [
                'code' => 'privacy_sensitive_broadening',
                'message' => 'This transition broadens visibility for privacy-sensitive content.',
            ];
        }

        if ($from === self::RESTRICTED_INTEGRITY && $to !== self::RESTRICTED_INTEGRITY) {
            $warnings[] = [
                'code' => 'integrity_restriction_removed',
                'message' => 'This transition removes integrity-restricted visibility.',
            ];
        }

        if ($to === self::PUBLIC && !self::requires_human_validation($from)) {
            $warnings[] = [
                'code' => 'public_validation_required',
                'message' => 'Public visibility should be validated before publication.',
            ];
        }

        return $warnings;
    }

    /**
     * Normalise a generic key.
     *
     * @param string $key Raw key.
     * @return string
     */
    private static function normalise_key(string $key): string {
        $key = trim($key);
        $key = \core_text::strtolower($key);
        $key = str_replace([' ', '-', '.', '/', '\\'], '_', $key);
        $key = clean_param($key, PARAM_ALPHANUMEXT);
        $key = preg_replace('/_+/', '_', $key) ?? '';

        return trim($key, '_');
    }
}

