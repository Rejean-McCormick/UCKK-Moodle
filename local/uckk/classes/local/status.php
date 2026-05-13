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
 * Canonical status registry for the UCKK institutional core plugin.
 *
 * This class centralises UCKK status values used across the distribution.
 * It does not write to the database, trigger workflow actions, validate
 * evidence, close integrity cases, issue badges, or grade users.
 *
 * It only provides stable machine names, display labels, grouping helpers,
 * CSS helpers and conservative transition checks.
 *
 * @package    local_uckk
 * @copyright  2026 Univers-Cité King Klown
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_uckk\local;

use invalid_parameter_exception;

defined('MOODLE_INTERNAL') || die();

/**
 * UCKK status registry.
 */
final class status {
    /** Component name. */
    public const COMPONENT = 'local_uckk';

    /** Draft status. */
    public const DRAFT = 'draft';

    /** Active status. */
    public const ACTIVE = 'active';

    /** Hidden status. */
    public const HIDDEN = 'hidden';

    /** Pending status. */
    public const PENDING = 'pending';

    /** Pending review status. */
    public const PENDING_REVIEW = 'pending_review';

    /** Submitted status. */
    public const SUBMITTED = 'submitted';

    /** Assigned status. */
    public const ASSIGNED = 'assigned';

    /** Under review status. */
    public const UNDER_REVIEW = 'under_review';

    /** Waiting for response status. */
    public const WAITING_FOR_RESPONSE = 'waiting_for_response';

    /** Correction required status. */
    public const CORRECTION_REQUIRED = 'correction_required';

    /** Validated status. */
    public const VALIDATED = 'validated';

    /** Verified status. */
    public const VERIFIED = 'verified';

    /** Rejected status. */
    public const REJECTED = 'rejected';

    /** Contested status. */
    public const CONTESTED = 'contested';

    /** Invalidated status. */
    public const INVALIDATED = 'invalidated';

    /** Published status. */
    public const PUBLISHED = 'published';

    /** Restricted status. */
    public const RESTRICTED = 'restricted';

    /** Superseded status. */
    public const SUPERSEDED = 'superseded';

    /** Resolved status. */
    public const RESOLVED = 'resolved';

    /** Dismissed status. */
    public const DISMISSED = 'dismissed';

    /** Escalated status. */
    public const ESCALATED = 'escalated';

    /** Paused status. */
    public const PAUSED = 'paused';

    /** Reopened status. */
    public const REOPENED = 'reopened';

    /** Closed status. */
    public const CLOSED = 'closed';

    /** Archived status. */
    public const ARCHIVED = 'archived';

    /** Deleted status. */
    public const DELETED = 'deleted';

    /** Cancelled status. */
    public const CANCELLED = 'cancelled';

    /** Recorded status. */
    public const RECORDED = 'recorded';

    /** Unverified validation status. */
    public const UNVERIFIED = 'unverified';

    /** Status group: preparation. */
    public const GROUP_PREPARATION = 'preparation';

    /** Status group: active. */
    public const GROUP_ACTIVE = 'active';

    /** Status group: review. */
    public const GROUP_REVIEW = 'review';

    /** Status group: positive. */
    public const GROUP_POSITIVE = 'positive';

    /** Status group: warning. */
    public const GROUP_WARNING = 'warning';

    /** Status group: negative. */
    public const GROUP_NEGATIVE = 'negative';

    /** Status group: final. */
    public const GROUP_FINAL = 'final';

    /** Status group: archival. */
    public const GROUP_ARCHIVAL = 'archival';

    /**
     * Return all canonical statuses.
     *
     * @return string[]
     */
    public static function get_all(): array {
        return [
            self::DRAFT,
            self::ACTIVE,
            self::HIDDEN,
            self::PENDING,
            self::PENDING_REVIEW,
            self::SUBMITTED,
            self::ASSIGNED,
            self::UNDER_REVIEW,
            self::WAITING_FOR_RESPONSE,
            self::CORRECTION_REQUIRED,
            self::VALIDATED,
            self::VERIFIED,
            self::REJECTED,
            self::CONTESTED,
            self::INVALIDATED,
            self::PUBLISHED,
            self::RESTRICTED,
            self::SUPERSEDED,
            self::RESOLVED,
            self::DISMISSED,
            self::ESCALATED,
            self::PAUSED,
            self::REOPENED,
            self::CLOSED,
            self::ARCHIVED,
            self::DELETED,
            self::CANCELLED,
            self::RECORDED,
            self::UNVERIFIED,
        ];
    }

    /**
     * Return statuses used by programs.
     *
     * @return string[]
     */
    public static function get_program_statuses(): array {
        return [
            self::DRAFT,
            self::ACTIVE,
            self::HIDDEN,
            self::ARCHIVED,
            self::DELETED,
        ];
    }

    /**
     * Return statuses used by pathways.
     *
     * @return string[]
     */
    public static function get_pathway_statuses(): array {
        return [
            self::DRAFT,
            self::ACTIVE,
            self::HIDDEN,
            self::ARCHIVED,
            self::DELETED,
        ];
    }

    /**
     * Return statuses used by reflections.
     *
     * @return string[]
     */
    public static function get_reflection_statuses(): array {
        return [
            self::DRAFT,
            self::SUBMITTED,
            self::PENDING_REVIEW,
            self::VALIDATED,
            self::CORRECTION_REQUIRED,
            self::CONTESTED,
            self::INVALIDATED,
            self::ARCHIVED,
            self::DELETED,
        ];
    }

    /**
     * Return statuses used by provenance records.
     *
     * @return string[]
     */
    public static function get_provenance_statuses(): array {
        return [
            self::RECORDED,
            self::UNVERIFIED,
            self::VERIFIED,
            self::CONTESTED,
            self::INVALIDATED,
            self::ARCHIVED,
        ];
    }

    /**
     * Return statuses used by integrity cases.
     *
     * @return string[]
     */
    public static function get_integrity_statuses(): array {
        return [
            self::DRAFT,
            self::PENDING,
            self::ASSIGNED,
            self::UNDER_REVIEW,
            self::WAITING_FOR_RESPONSE,
            self::CORRECTION_REQUIRED,
            self::RESOLVED,
            self::DISMISSED,
            self::ESCALATED,
            self::PAUSED,
            self::REOPENED,
            self::CLOSED,
            self::ARCHIVED,
        ];
    }

    /**
     * Return statuses used by archive items.
     *
     * @return string[]
     */
    public static function get_archive_statuses(): array {
        return [
            self::DRAFT,
            self::SUBMITTED,
            self::UNDER_REVIEW,
            self::VALIDATED,
            self::PUBLISHED,
            self::RESTRICTED,
            self::CONTESTED,
            self::INVALIDATED,
            self::SUPERSEDED,
            self::ARCHIVED,
            self::DELETED,
        ];
    }

    /**
     * Return statuses grouped by semantic family.
     *
     * @return array<string, string[]>
     */
    public static function get_groups(): array {
        return [
            self::GROUP_PREPARATION => [
                self::DRAFT,
                self::PENDING,
                self::PENDING_REVIEW,
                self::SUBMITTED,
                self::ASSIGNED,
            ],
            self::GROUP_ACTIVE => [
                self::ACTIVE,
                self::PUBLISHED,
                self::REOPENED,
            ],
            self::GROUP_REVIEW => [
                self::UNDER_REVIEW,
                self::WAITING_FOR_RESPONSE,
                self::CORRECTION_REQUIRED,
                self::UNVERIFIED,
            ],
            self::GROUP_POSITIVE => [
                self::VALIDATED,
                self::VERIFIED,
                self::RESOLVED,
                self::RECORDED,
            ],
            self::GROUP_WARNING => [
                self::CONTESTED,
                self::ESCALATED,
                self::PAUSED,
                self::RESTRICTED,
                self::SUPERSEDED,
            ],
            self::GROUP_NEGATIVE => [
                self::REJECTED,
                self::INVALIDATED,
                self::DISMISSED,
                self::CANCELLED,
                self::DELETED,
            ],
            self::GROUP_FINAL => [
                self::CLOSED,
                self::ARCHIVED,
                self::DELETED,
                self::CANCELLED,
                self::INVALIDATED,
                self::DISMISSED,
            ],
            self::GROUP_ARCHIVAL => [
                self::ARCHIVED,
                self::SUPERSEDED,
                self::RECORDED,
                self::PUBLISHED,
            ],
        ];
    }

    /**
     * Return Moodle form/select options for a status list.
     *
     * @param string[]|null $statuses Optional status list.
     * @return array<string, string>
     */
    public static function get_options(?array $statuses = null): array {
        $statuses = $statuses ?? self::get_all();
        $options = [];

        foreach ($statuses as $status) {
            $status = self::normalise($status);
            $options[$status] = self::get_label($status);
        }

        return $options;
    }

    /**
     * Return options for programs.
     *
     * @return array<string, string>
     */
    public static function get_program_options(): array {
        return self::get_options(self::get_program_statuses());
    }

    /**
     * Return options for pathways.
     *
     * @return array<string, string>
     */
    public static function get_pathway_options(): array {
        return self::get_options(self::get_pathway_statuses());
    }

    /**
     * Return options for reflections.
     *
     * @return array<string, string>
     */
    public static function get_reflection_options(): array {
        return self::get_options(self::get_reflection_statuses());
    }

    /**
     * Return options for provenance records.
     *
     * @return array<string, string>
     */
    public static function get_provenance_options(): array {
        return self::get_options(self::get_provenance_statuses());
    }

    /**
     * Return options for integrity cases.
     *
     * @return array<string, string>
     */
    public static function get_integrity_options(): array {
        return self::get_options(self::get_integrity_statuses());
    }

    /**
     * Return options for archive items.
     *
     * @return array<string, string>
     */
    public static function get_archive_options(): array {
        return self::get_options(self::get_archive_statuses());
    }

    /**
     * Normalise a status key.
     *
     * @param string|null $status Raw status.
     * @param string $default Default status.
     * @return string
     */
    public static function normalise(?string $status, string $default = self::DRAFT): string {
        $status = trim(\core_text::strtolower((string)$status));
        $status = str_replace(['-', ' '], '_', $status);
        $status = clean_param($status, PARAM_ALPHANUMEXT);

        if ($status === '') {
            return $default;
        }

        return $status;
    }

    /**
     * Validate and return a status.
     *
     * @param string|null $status Raw status.
     * @param string[]|null $allowed Allowed statuses.
     * @param string $default Default status.
     * @return string
     * @throws invalid_parameter_exception
     */
    public static function require_valid(
        ?string $status,
        ?array $allowed = null,
        string $default = self::DRAFT
    ): string {
        $status = self::normalise($status, $default);
        $allowed = $allowed ?? self::get_all();

        if (!in_array($status, $allowed, true)) {
            throw new invalid_parameter_exception('Invalid UCKK status: ' . $status);
        }

        return $status;
    }

    /**
     * Determine whether a status is known.
     *
     * @param string|null $status Raw status.
     * @param string[]|null $allowed Optional allowed statuses.
     * @return bool
     */
    public static function is_supported(?string $status, ?array $allowed = null): bool {
        $status = self::normalise($status);
        $allowed = $allowed ?? self::get_all();

        return in_array($status, $allowed, true);
    }

    /**
     * Get status display label.
     *
     * @param string|null $status Raw status.
     * @return string
     */
    public static function get_label(?string $status): string {
        $status = self::normalise($status);
        $stringkey = 'status_' . $status;

        if (get_string_manager()->string_exists($stringkey, self::COMPONENT)) {
            return get_string($stringkey, self::COMPONENT);
        }

        return ucfirst(str_replace('_', ' ', $status));
    }

    /**
     * Get the semantic group of a status.
     *
     * @param string|null $status Raw status.
     * @return string
     */
    public static function get_group(?string $status): string {
        $status = self::normalise($status);

        foreach (self::get_groups() as $group => $statuses) {
            if (in_array($status, $statuses, true)) {
                return $group;
            }
        }

        return self::GROUP_PREPARATION;
    }

    /**
     * Determine whether a status belongs to a group.
     *
     * @param string|null $status Status.
     * @param string $group Group.
     * @return bool
     */
    public static function is_in_group(?string $status, string $group): bool {
        $status = self::normalise($status);
        $group = self::normalise($group, self::GROUP_PREPARATION);
        $groups = self::get_groups();

        return in_array($status, $groups[$group] ?? [], true);
    }

    /**
     * Determine whether the status is an active state.
     *
     * @param string|null $status Status.
     * @return bool
     */
    public static function is_active_state(?string $status): bool {
        return self::is_in_group($status, self::GROUP_ACTIVE);
    }

    /**
     * Determine whether the status is a review state.
     *
     * @param string|null $status Status.
     * @return bool
     */
    public static function is_review_state(?string $status): bool {
        return self::is_in_group($status, self::GROUP_REVIEW);
    }

    /**
     * Determine whether the status is a positive state.
     *
     * @param string|null $status Status.
     * @return bool
     */
    public static function is_positive_state(?string $status): bool {
        return self::is_in_group($status, self::GROUP_POSITIVE);
    }

    /**
     * Determine whether the status is a warning state.
     *
     * @param string|null $status Status.
     * @return bool
     */
    public static function is_warning_state(?string $status): bool {
        return self::is_in_group($status, self::GROUP_WARNING);
    }

    /**
     * Determine whether the status is a negative state.
     *
     * @param string|null $status Status.
     * @return bool
     */
    public static function is_negative_state(?string $status): bool {
        return self::is_in_group($status, self::GROUP_NEGATIVE);
    }

    /**
     * Determine whether the status is final.
     *
     * @param string|null $status Status.
     * @return bool
     */
    public static function is_final_state(?string $status): bool {
        return self::is_in_group($status, self::GROUP_FINAL);
    }

    /**
     * Determine whether the status is archival.
     *
     * @param string|null $status Status.
     * @return bool
     */
    public static function is_archival_state(?string $status): bool {
        return self::is_in_group($status, self::GROUP_ARCHIVAL);
    }

    /**
     * Determine whether status can be displayed publicly by default.
     *
     * This does not replace capability or privacy checks. It only expresses
     * conservative default visibility.
     *
     * @param string|null $status Status.
     * @return bool
     */
    public static function is_public_safe(?string $status): bool {
        $status = self::normalise($status);

        return in_array($status, [
            self::ACTIVE,
            self::VALIDATED,
            self::VERIFIED,
            self::PUBLISHED,
            self::ARCHIVED,
            self::RECORDED,
        ], true);
    }

    /**
     * Determine whether the status should be treated as sensitive.
     *
     * Sensitive means UI and reports should check capabilities before display.
     *
     * @param string|null $status Status.
     * @return bool
     */
    public static function is_sensitive(?string $status): bool {
        $status = self::normalise($status);

        return in_array($status, [
            self::CONTESTED,
            self::INVALIDATED,
            self::CORRECTION_REQUIRED,
            self::UNDER_REVIEW,
            self::WAITING_FOR_RESPONSE,
            self::ESCALATED,
            self::RESTRICTED,
            self::PAUSED,
        ], true);
    }

    /**
     * Get Bootstrap-like visual type for the status.
     *
     * @param string|null $status Status.
     * @return string
     */
    public static function get_visual_type(?string $status): string {
        if (self::is_positive_state($status) || self::is_active_state($status)) {
            return 'success';
        }

        if (self::is_warning_state($status) || self::is_review_state($status)) {
            return 'warning';
        }

        if (self::is_negative_state($status)) {
            return 'danger';
        }

        if (self::is_archival_state($status) || self::is_final_state($status)) {
            return 'info';
        }

        return 'secondary';
    }

    /**
     * Get Bootstrap 4 badge class.
     *
     * @param string|null $status Status.
     * @return string
     */
    public static function get_badge_class(?string $status): string {
        return 'badge-' . self::get_visual_type($status);
    }

    /**
     * Get Bootstrap 5 background class.
     *
     * @param string|null $status Status.
     * @return string
     */
    public static function get_background_class(?string $status): string {
        return 'bg-' . self::get_visual_type($status);
    }

    /**
     * Get UCKK CSS class.
     *
     * @param string|null $status Status.
     * @return string
     */
    public static function get_css_class(?string $status): string {
        $status = self::normalise($status);

        return 'uckk-status-' . $status;
    }

    /**
     * Get all CSS classes for a status badge.
     *
     * @param string|null $status Status.
     * @param string[] $extra Extra CSS classes.
     * @return string
     */
    public static function get_css_classes(?string $status, array $extra = []): string {
        $classes = [
            'uckk-status-badge',
            self::get_css_class($status),
            self::get_badge_class($status),
        ];

        if (self::is_sensitive($status)) {
            $classes[] = 'uckk-status-sensitive';
        }

        if (self::is_final_state($status)) {
            $classes[] = 'uckk-status-final';
        }

        $classes = array_merge($classes, $extra);
        $classes = array_filter(array_map('trim', $classes));

        return implode(' ', array_unique($classes));
    }

    /**
     * Export a status for templates.
     *
     * @param string|null $status Status.
     * @param array<string, mixed> $overrides Optional overrides.
     * @return array<string, mixed>
     */
    public static function export_for_template(?string $status, array $overrides = []): array {
        $status = self::normalise($status);

        $data = [
            'status' => $status,
            'label' => self::get_label($status),
            'group' => self::get_group($status),
            'visualtype' => self::get_visual_type($status),
            'badgeclass' => self::get_badge_class($status),
            'backgroundclass' => self::get_background_class($status),
            'cssclass' => self::get_css_classes($status),
            'isactive' => self::is_active_state($status),
            'isreview' => self::is_review_state($status),
            'ispositive' => self::is_positive_state($status),
            'iswarning' => self::is_warning_state($status),
            'isnegative' => self::is_negative_state($status),
            'isfinal' => self::is_final_state($status),
            'isarchival' => self::is_archival_state($status),
            'issensitive' => self::is_sensitive($status),
            'ispublicsafe' => self::is_public_safe($status),
        ];

        return array_merge($data, $overrides);
    }

    /**
     * Export multiple statuses for templates.
     *
     * @param string[]|null $statuses Optional status list.
     * @return array<int, array<string, mixed>>
     */
    public static function export_options_for_template(?array $statuses = null): array {
        $statuses = $statuses ?? self::get_all();
        $items = [];

        foreach ($statuses as $status) {
            $items[] = self::export_for_template($status);
        }

        return $items;
    }

    /**
     * Return conservative allowed transitions.
     *
     * These transitions are generic and must not replace workflow-specific
     * validation in challenge, assembly, archive or integrity plugins.
     *
     * @return array<string, string[]>
     */
    public static function get_default_transitions(): array {
        return [
            self::DRAFT => [
                self::ACTIVE,
                self::SUBMITTED,
                self::PENDING,
                self::ARCHIVED,
                self::DELETED,
                self::CANCELLED,
            ],
            self::ACTIVE => [
                self::HIDDEN,
                self::PENDING_REVIEW,
                self::UNDER_REVIEW,
                self::ARCHIVED,
                self::CLOSED,
            ],
            self::HIDDEN => [
                self::ACTIVE,
                self::ARCHIVED,
                self::DELETED,
            ],
            self::PENDING => [
                self::ACTIVE,
                self::UNDER_REVIEW,
                self::REJECTED,
                self::CANCELLED,
            ],
            self::PENDING_REVIEW => [
                self::UNDER_REVIEW,
                self::VALIDATED,
                self::CORRECTION_REQUIRED,
                self::REJECTED,
                self::CONTESTED,
            ],
            self::SUBMITTED => [
                self::PENDING_REVIEW,
                self::UNDER_REVIEW,
                self::VALIDATED,
                self::CORRECTION_REQUIRED,
                self::REJECTED,
                self::ARCHIVED,
            ],
            self::ASSIGNED => [
                self::UNDER_REVIEW,
                self::WAITING_FOR_RESPONSE,
                self::RESOLVED,
                self::CLOSED,
            ],
            self::UNDER_REVIEW => [
                self::WAITING_FOR_RESPONSE,
                self::CORRECTION_REQUIRED,
                self::VALIDATED,
                self::VERIFIED,
                self::RESOLVED,
                self::CONTESTED,
                self::INVALIDATED,
                self::DISMISSED,
                self::ESCALATED,
                self::PAUSED,
            ],
            self::WAITING_FOR_RESPONSE => [
                self::UNDER_REVIEW,
                self::CORRECTION_REQUIRED,
                self::RESOLVED,
                self::PAUSED,
                self::CLOSED,
            ],
            self::CORRECTION_REQUIRED => [
                self::SUBMITTED,
                self::UNDER_REVIEW,
                self::VALIDATED,
                self::CONTESTED,
                self::INVALIDATED,
            ],
            self::VALIDATED => [
                self::PUBLISHED,
                self::CONTESTED,
                self::ARCHIVED,
                self::SUPERSEDED,
            ],
            self::VERIFIED => [
                self::PUBLISHED,
                self::CONTESTED,
                self::ARCHIVED,
                self::SUPERSEDED,
            ],
            self::REJECTED => [
                self::DRAFT,
                self::SUBMITTED,
                self::ARCHIVED,
                self::DELETED,
            ],
            self::CONTESTED => [
                self::UNDER_REVIEW,
                self::CORRECTION_REQUIRED,
                self::VALIDATED,
                self::INVALIDATED,
                self::RESOLVED,
                self::ARCHIVED,
            ],
            self::INVALIDATED => [
                self::ARCHIVED,
                self::REOPENED,
            ],
            self::PUBLISHED => [
                self::CONTESTED,
                self::SUPERSEDED,
                self::ARCHIVED,
            ],
            self::RESTRICTED => [
                self::PUBLISHED,
                self::CONTESTED,
                self::ARCHIVED,
            ],
            self::SUPERSEDED => [
                self::ARCHIVED,
            ],
            self::RESOLVED => [
                self::CLOSED,
                self::ARCHIVED,
                self::REOPENED,
            ],
            self::DISMISSED => [
                self::CLOSED,
                self::ARCHIVED,
                self::REOPENED,
            ],
            self::ESCALATED => [
                self::UNDER_REVIEW,
                self::WAITING_FOR_RESPONSE,
                self::RESOLVED,
                self::CLOSED,
            ],
            self::PAUSED => [
                self::UNDER_REVIEW,
                self::REOPENED,
                self::CANCELLED,
            ],
            self::REOPENED => [
                self::UNDER_REVIEW,
                self::PENDING_REVIEW,
                self::ACTIVE,
            ],
            self::CLOSED => [
                self::ARCHIVED,
                self::REOPENED,
            ],
            self::ARCHIVED => [
                self::REOPENED,
                self::DELETED,
            ],
            self::DELETED => [],
            self::CANCELLED => [
                self::DRAFT,
                self::ARCHIVED,
            ],
            self::RECORDED => [
                self::VERIFIED,
                self::CONTESTED,
                self::INVALIDATED,
                self::ARCHIVED,
            ],
            self::UNVERIFIED => [
                self::VERIFIED,
                self::CONTESTED,
                self::INVALIDATED,
            ],
        ];
    }

    /**
     * Determine whether a generic transition is allowed.
     *
     * Specific plugins may impose stricter workflow rules.
     *
     * @param string|null $from Current status.
     * @param string|null $to Target status.
     * @return bool
     */
    public static function can_transition(?string $from, ?string $to): bool {
        $from = self::normalise($from);
        $to = self::normalise($to);

        if ($from === $to) {
            return true;
        }

        $transitions = self::get_default_transitions();

        return in_array($to, $transitions[$from] ?? [], true);
    }

    /**
     * Require that a transition is allowed.
     *
     * @param string|null $from Current status.
     * @param string|null $to Target status.
     * @return void
     * @throws invalid_parameter_exception
     */
    public static function require_transition(?string $from, ?string $to): void {
        if (!self::can_transition($from, $to)) {
            throw new invalid_parameter_exception(
                'Invalid UCKK status transition: ' . self::normalise($from) . ' → ' . self::normalise($to)
            );
        }
    }
}

