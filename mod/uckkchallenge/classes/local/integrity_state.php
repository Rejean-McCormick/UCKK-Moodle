<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

/**
 * Integrity state helper for UCKK challenges.
 *
 * @package    mod_uckkchallenge
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace mod_uckkchallenge\local;

defined('MOODLE_INTERNAL') || die();

/**
 * Canonical integrity and validation states for Défis King Klown.
 *
 * This class is intentionally stateless. It defines allowed values and
 * transitions only. Database updates, event emission, integrity case handling,
 * corrections, invalidations, and archive export must be performed by
 * capability-checked services.
 */
final class integrity_state {
    /**
     * Evidence or submission has not yet been reviewed.
     */
    public const UNVERIFIED = 'unverified';

    /**
     * A human reviewer has reviewed the evidence but has not fully verified it.
     */
    public const HUMAN_REVIEWED = 'human_reviewed';

    /**
     * Evidence or submission has been verified by an authorized human process.
     */
    public const VERIFIED = 'verified';

    /**
     * Evidence, evaluation, or process is contested.
     */
    public const CONTESTED = 'contested';

    /**
     * Evidence or submission has been invalidated by an authorized integrity process.
     */
    public const INVALIDATED = 'invalidated';

    /**
     * Integrity state has been archived as part of the institutional memory.
     */
    public const ARCHIVED = 'archived';

    /**
     * Challenge workflow status: integrity review.
     */
    public const CHALLENGE_STATUS_INTEGRITY_REVIEW = 'integrity_review';

    /**
     * Challenge workflow status: correction required.
     */
    public const CHALLENGE_STATUS_CORRECTION_REQUIRED = 'correction_required';

    /**
     * Challenge workflow status: validated.
     */
    public const CHALLENGE_STATUS_VALIDATED = 'validated';

    /**
     * Challenge workflow status: contested.
     */
    public const CHALLENGE_STATUS_CONTESTED = 'contested';

    /**
     * Challenge workflow status: invalidated.
     */
    public const CHALLENGE_STATUS_INVALIDATED = 'invalidated';

    /**
     * Challenge workflow status: archived.
     */
    public const CHALLENGE_STATUS_ARCHIVED = 'archived';

    /**
     * Return all canonical integrity states.
     *
     * @return array<int, string>
     */
    public static function all(): array {
        return [
            self::UNVERIFIED,
            self::HUMAN_REVIEWED,
            self::VERIFIED,
            self::CONTESTED,
            self::INVALIDATED,
            self::ARCHIVED,
        ];
    }

    /**
     * Return states that represent a completed integrity decision.
     *
     * @return array<int, string>
     */
    public static function terminal_states(): array {
        return [
            self::VERIFIED,
            self::INVALIDATED,
            self::ARCHIVED,
        ];
    }

    /**
     * Return states that still require human attention.
     *
     * @return array<int, string>
     */
    public static function review_required_states(): array {
        return [
            self::UNVERIFIED,
            self::HUMAN_REVIEWED,
            self::CONTESTED,
        ];
    }

    /**
     * Return states that must not be treated as verified.
     *
     * @return array<int, string>
     */
    public static function non_verified_states(): array {
        return [
            self::UNVERIFIED,
            self::HUMAN_REVIEWED,
            self::CONTESTED,
            self::INVALIDATED,
        ];
    }

    /**
     * Return options for forms or renderable data.
     *
     * @return array<string, string>
     */
    public static function options(): array {
        $options = [];

        foreach (self::all() as $state) {
            $options[$state] = self::get_label($state);
        }

        return $options;
    }

    /**
     * Check whether a value is a canonical integrity state.
     *
     * @param string|null $state State to test.
     * @return bool
     */
    public static function is_valid(?string $state): bool {
        if ($state === null) {
            return false;
        }

        return in_array($state, self::all(), true);
    }

    /**
     * Normalize a state value.
     *
     * @param string|null $state Raw state.
     * @param string $fallback Fallback canonical state.
     * @return string
     */
    public static function normalise(?string $state, string $fallback = self::UNVERIFIED): string {
        $state = clean_param((string)$state, PARAM_ALPHANUMEXT);

        if (self::is_valid($state)) {
            return $state;
        }

        return self::is_valid($fallback) ? $fallback : self::UNVERIFIED;
    }

    /**
     * Return a localised state label.
     *
     * @param string $state Canonical state.
     * @return string
     */
    public static function get_label(string $state): string {
        $state = self::normalise($state);
        $key = 'integritystate:' . $state;

        if (get_string_manager()->string_exists($key, 'uckkchallenge')) {
            return get_string($key, 'uckkchallenge');
        }

        return ucfirst(str_replace('_', ' ', $state));
    }

    /**
     * Return a CSS-safe modifier for the state.
     *
     * @param string $state Canonical state.
     * @return string
     */
    public static function get_css_class(string $state): string {
        return 'integrity-state-' . str_replace('_', '-', self::normalise($state));
    }

    /**
     * Whether the state is final for ordinary challenge progression.
     *
     * Archived is terminal as a record state, but not equivalent to verified.
     *
     * @param string $state Canonical state.
     * @return bool
     */
    public static function is_terminal(string $state): bool {
        return in_array(self::normalise($state), self::terminal_states(), true);
    }

    /**
     * Whether the state still requires human review.
     *
     * @param string $state Canonical state.
     * @return bool
     */
    public static function requires_review(string $state): bool {
        return in_array(self::normalise($state), self::review_required_states(), true);
    }

    /**
     * Whether the state allows a challenge to be considered integrity-validated.
     *
     * @param string $state Canonical state.
     * @return bool
     */
    public static function is_verified(string $state): bool {
        return self::normalise($state) === self::VERIFIED;
    }

    /**
     * Whether the state is contested.
     *
     * @param string $state Canonical state.
     * @return bool
     */
    public static function is_contested(string $state): bool {
        return self::normalise($state) === self::CONTESTED;
    }

    /**
     * Whether the state is invalidated.
     *
     * @param string $state Canonical state.
     * @return bool
     */
    public static function is_invalidated(string $state): bool {
        return self::normalise($state) === self::INVALIDATED;
    }

    /**
     * Determine whether a transition is allowed.
     *
     * This method only checks domain-valid transitions. The caller must still
     * enforce capabilities, sesskey checks, audit logging, event emission, and
     * integrity case rules.
     *
     * @param string $from Current state.
     * @param string $to Target state.
     * @return bool
     */
    public static function can_transition(string $from, string $to): bool {
        $from = self::normalise($from);
        $to = self::normalise($to);

        if ($from === $to) {
            return true;
        }

        $allowed = self::transition_map();

        return in_array($to, $allowed[$from] ?? [], true);
    }

    /**
     * Return the allowed transition map.
     *
     * @return array<string, array<int, string>>
     */
    public static function transition_map(): array {
        return [
            self::UNVERIFIED => [
                self::HUMAN_REVIEWED,
                self::VERIFIED,
                self::CONTESTED,
                self::INVALIDATED,
            ],
            self::HUMAN_REVIEWED => [
                self::VERIFIED,
                self::CONTESTED,
                self::INVALIDATED,
                self::UNVERIFIED,
            ],
            self::VERIFIED => [
                self::CONTESTED,
                self::ARCHIVED,
            ],
            self::CONTESTED => [
                self::HUMAN_REVIEWED,
                self::VERIFIED,
                self::INVALIDATED,
                self::UNVERIFIED,
            ],
            self::INVALIDATED => [
                self::CONTESTED,
                self::ARCHIVED,
            ],
            self::ARCHIVED => [
                self::CONTESTED,
            ],
        ];
    }

    /**
     * Return invalid transition error data for service-layer validation.
     *
     * @param string $from Current state.
     * @param string $to Target state.
     * @return array<string, string>
     */
    public static function invalid_transition_error(string $from, string $to): array {
        return [
            'errorcode' => 'invalidintegritytransition',
            'from' => self::normalise($from),
            'to' => self::normalise($to),
            'message' => get_string('invalidintegritytransition', 'uckkchallenge', (object)[
                'from' => self::get_label($from),
                'to' => self::get_label($to),
            ]),
        ];
    }

    /**
     * Map an integrity state to a recommended challenge workflow status.
     *
     * This is advisory only. The challenge service must decide whether the
     * workflow status should actually change.
     *
     * @param string $state Canonical integrity state.
     * @return string
     */
    public static function suggested_challenge_status(string $state): string {
        return match (self::normalise($state)) {
            self::UNVERIFIED,
            self::HUMAN_REVIEWED => self::CHALLENGE_STATUS_INTEGRITY_REVIEW,

            self::VERIFIED => self::CHALLENGE_STATUS_VALIDATED,
            self::CONTESTED => self::CHALLENGE_STATUS_CONTESTED,
            self::INVALIDATED => self::CHALLENGE_STATUS_INVALIDATED,
            self::ARCHIVED => self::CHALLENGE_STATUS_ARCHIVED,

            default => self::CHALLENGE_STATUS_INTEGRITY_REVIEW,
        };
    }

    /**
     * Return whether a state should block archive export.
     *
     * @param string $state Canonical integrity state.
     * @return bool
     */
    public static function blocks_archive_export(string $state): bool {
        return in_array(self::normalise($state), [
            self::UNVERIFIED,
            self::HUMAN_REVIEWED,
            self::CONTESTED,
            self::INVALIDATED,
        ], true);
    }

    /**
     * Return whether a state should block badge or competency recognition.
     *
     * @param string $state Canonical integrity state.
     * @return bool
     */
    public static function blocks_recognition(string $state): bool {
        return self::normalise($state) !== self::VERIFIED;
    }

    /**
     * Build a compact export object for renderables, external services, or logs.
     *
     * @param string $state Canonical integrity state.
     * @return \stdClass
     */
    public static function export(string $state): \stdClass {
        $state = self::normalise($state);

        return (object)[
            'state' => $state,
            'label' => self::get_label($state),
            'class' => self::get_css_class($state),
            'isverified' => self::is_verified($state),
            'iscontested' => self::is_contested($state),
            'isinvalidated' => self::is_invalidated($state),
            'isterminal' => self::is_terminal($state),
            'requiresreview' => self::requires_review($state),
            'blocksarchiveexport' => self::blocks_archive_export($state),
            'blocksrecognition' => self::blocks_recognition($state),
            'suggestedchallengestatus' => self::suggested_challenge_status($state),
        ];
    }

    /**
     * Constructor disabled: static helper only.
     */
    private function __construct() {
    }
}