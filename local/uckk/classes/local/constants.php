<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

namespace local_uckk\local;

defined('MOODLE_INTERNAL') || die();

/**
 * Canonical constants for local_uckk.
 *
 * These constants intentionally separate symbolic UCKK distinctions from
 * Moodle technical roles and keep standalone-core values explicit.
 *
 * @package    local_uckk
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class constants {
    public const COMPONENT = 'local_uckk';

    public const PROGRAM_TC = 'tronc_commun';

    public const TYPE_TRONC_COMMUN = 'tronc_commun';
    public const TYPE_BACCALAUREAT = 'baccalaureat';
    public const TYPE_MINEURE = 'mineure';
    public const TYPE_LABORATOIRE = 'laboratoire';
    public const TYPE_SEMINAIRE = 'seminaire';

    public const STATUS_DRAFT = 'draft';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_HIDDEN = 'hidden';
    public const STATUS_ARCHIVED = 'archived';
    public const STATUS_PENDING = 'pending';
    public const STATUS_PENDING_REVIEW = 'pending_review';
    public const STATUS_VALIDATED = 'validated';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_CORRECTION_REQUIRED = 'correction_required';
    public const STATUS_CONTESTED = 'contested';
    public const STATUS_INVALIDATED = 'invalidated';
    public const STATUS_CLOSED = 'closed';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_BLOCKED = 'blocked';

    public const PROGRESS_NOT_STARTED = 'not_started';
    public const PROGRESS_IN_PROGRESS = 'in_progress';
    public const PROGRESS_COMPLETED = 'completed';
    public const PROGRESS_BLOCKED = 'blocked';

    public const VISIBILITY_PRIVATE = 'private';
    public const VISIBILITY_USER = 'user';
    public const VISIBILITY_GROUP = 'group';
    public const VISIBILITY_COURSE = 'course';
    public const VISIBILITY_COHORT = 'cohort';
    public const VISIBILITY_PROGRAM = 'program';
    public const VISIBILITY_INSTITUTION = 'institution';
    public const VISIBILITY_PUBLIC = 'public';
    public const VISIBILITY_RESTRICTED = 'restricted';
    public const VISIBILITY_RESTRICTED_INTEGRITY = 'restricted_integrity';
    public const VISIBILITY_HIDDEN = 'hidden';
    public const VISIBILITY_ARCHIVED = 'archived';

    public const PROVENANCE_HUMAN = 'human';
    public const PROVENANCE_AI_ASSISTED = 'ai_assisted';
    public const PROVENANCE_IMPORTED = 'imported';
    public const PROVENANCE_SYSTEM = 'system';
    public const PROVENANCE_ARCHIVE = 'archive';
    public const PROVENANCE_ASSEMBLY = 'assembly';
    public const PROVENANCE_CHALLENGE = 'challenge';
    public const PROVENANCE_INTEGRITY = 'integrity';

    public const SYMBOLIC_ROLE_JOUEUR = 'joueur';
    public const SYMBOLIC_ROLE_JOUEUR_LUCIDE = 'joueur_lucide';
    public const SYMBOLIC_ROLE_BATISSEUR = 'batisseur';
    public const SYMBOLIC_ROLE_ARCHIVISTE = 'archiviste';
    public const SYMBOLIC_ROLE_INQUISITEUR = 'inquisiteur';
    public const SYMBOLIC_ROLE_CARTOGRAPHE = 'cartographe';
    public const SYMBOLIC_ROLE_ARCHITECTE_SENS = 'architecte_sens';
    public const SYMBOLIC_ROLE_ARCHITECTE_OPPORTUNITES = 'architecte_opportunites';
    public const SYMBOLIC_ROLE_GARDIEN_SYSTEMES_VIVANTS = 'gardien_systemes_vivants';

    public const BADGE_JOUEUR_INITIE = 'joueur_initie';

    public const COMP_READ_GAME = 'UCKK-COMP-001';
    public const COMP_MAP_SYSTEM = 'UCKK-COMP-002';
    public const COMP_KNOW_CHOOSE_ACT_REMEMBER = 'UCKK-COMP-014';

    public const COURSE_TC101 = 'UCKK-TC101';
    public const COURSE_TC102 = 'UCKK-TC102';

    public static function allowed_program_types(): array {
        return [
            self::TYPE_TRONC_COMMUN,
            self::TYPE_BACCALAUREAT,
            self::TYPE_MINEURE,
            self::TYPE_LABORATOIRE,
            self::TYPE_SEMINAIRE,
        ];
    }

    public static function allowed_statuses(): array {
        return [
            self::STATUS_DRAFT,
            self::STATUS_ACTIVE,
            self::STATUS_HIDDEN,
            self::STATUS_ARCHIVED,
            self::STATUS_PENDING,
            self::STATUS_PENDING_REVIEW,
            self::STATUS_VALIDATED,
            self::STATUS_REJECTED,
            self::STATUS_CORRECTION_REQUIRED,
            self::STATUS_CONTESTED,
            self::STATUS_INVALIDATED,
            self::STATUS_CLOSED,
            self::STATUS_CANCELLED,
        ];
    }

    public static function allowed_progress_states(): array {
        return [
            self::PROGRESS_NOT_STARTED,
            self::PROGRESS_IN_PROGRESS,
            self::PROGRESS_COMPLETED,
            self::PROGRESS_BLOCKED,
        ];
    }

    public static function allowed_visibilities(): array {
        return [
            self::VISIBILITY_PRIVATE,
            self::VISIBILITY_USER,
            self::VISIBILITY_GROUP,
            self::VISIBILITY_COURSE,
            self::VISIBILITY_COHORT,
            self::VISIBILITY_PROGRAM,
            self::VISIBILITY_INSTITUTION,
            self::VISIBILITY_PUBLIC,
            self::VISIBILITY_RESTRICTED,
            self::VISIBILITY_RESTRICTED_INTEGRITY,
            self::VISIBILITY_HIDDEN,
            self::VISIBILITY_ARCHIVED,
        ];
    }

    public static function allowed_provenances(): array {
        return [
            self::PROVENANCE_HUMAN,
            self::PROVENANCE_AI_ASSISTED,
            self::PROVENANCE_IMPORTED,
            self::PROVENANCE_SYSTEM,
            self::PROVENANCE_ARCHIVE,
            self::PROVENANCE_ASSEMBLY,
            self::PROVENANCE_CHALLENGE,
            self::PROVENANCE_INTEGRITY,
        ];
    }

    public static function symbolic_roles(): array {
        return [
            self::SYMBOLIC_ROLE_JOUEUR,
            self::SYMBOLIC_ROLE_JOUEUR_LUCIDE,
            self::SYMBOLIC_ROLE_BATISSEUR,
            self::SYMBOLIC_ROLE_ARCHIVISTE,
            self::SYMBOLIC_ROLE_INQUISITEUR,
            self::SYMBOLIC_ROLE_CARTOGRAPHE,
            self::SYMBOLIC_ROLE_ARCHITECTE_SENS,
            self::SYMBOLIC_ROLE_ARCHITECTE_OPPORTUNITES,
            self::SYMBOLIC_ROLE_GARDIEN_SYSTEMES_VIVANTS,
        ];
    }

    public static function normalise_key(string $value): string {
        $value = trim(\core_text::strtolower($value));
        $value = str_replace([' ', '-', '.', '/', '\\'], '_', $value);
        $value = preg_replace('/[^a-z0-9_]/', '', $value);
        $value = preg_replace('/_+/', '_', $value);

        return trim((string)$value, '_');
    }

    public static function assert_allowed(string $value, array $allowed, string $message): string {
        $value = self::normalise_key($value);
        if (!in_array($value, $allowed, true)) {
            throw new \invalid_parameter_exception($message);
        }
        return $value;
    }

    public static function status_label(string $status): string {
        $status = self::normalise_key($status);
        $id = 'status_' . $status;
        if (get_string_manager()->string_exists($id, self::COMPONENT)) {
            return get_string($id, self::COMPONENT);
        }
        return ucwords(str_replace('_', ' ', $status));
    }

    public static function visibility_label(string $visibility): string {
        $visibility = self::normalise_key($visibility);
        $id = 'visibility_' . $visibility;
        if (get_string_manager()->string_exists($id, self::COMPONENT)) {
            return get_string($id, self::COMPONENT);
        }
        return ucwords(str_replace('_', ' ', $visibility));
    }
}
