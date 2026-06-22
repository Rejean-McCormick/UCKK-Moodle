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
 * Profile API for local_uckk.
 *
 * The profile API manages UCKK player profiles, symbolic roles, active pathways,
 * visibility, portfolio links and profile metadata.
 *
 * It must not:
 * - enrol users into courses;
 * - assign Moodle roles;
 * - grant Moodle capabilities;
 * - validate challenge submissions;
 * - publish assembly decisions;
 * - validate archive items;
 * - decide integrity cases.
 *
 * Moodle roles, capabilities and enrolments remain Moodle concerns.
 * UCKK symbolic roles are domain labels used by UCKK-Moodle only.
 *
 * @package    local_uckk
 * @copyright  2026 Univers-Cité King Klown
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_uckk\api;

use context;
use context_system;
use context_user;
use core_user;
use dml_exception;
use invalid_parameter_exception;
use moodle_exception;
use required_capability_exception;
use stdClass;

defined('MOODLE_INTERNAL') || die();

/**
 * API for UCKK player profiles.
 *
 * @package local_uckk
 */
final class profile_api {
    /** Main UCKK profile table. */
    private const TABLE_PROFILE = 'local_uckk_player';

    /** UCKK provenance table. Optional, used only when present. */
    private const TABLE_PROVENANCE = 'local_uckk_prov';

    /** Default profile status. */
    private const STATUS_ACTIVE = 'active';

    /** Default visibility. */
    private const DEFAULT_VISIBILITY = 'user';

    /** Capability required to manage profiles. */
    private const CAP_MANAGE_PROFILES = 'local/uckk:manageprofiles';

    /** Capability used as a broader read capability when available. */
    private const CAP_VIEW_REPORTS = 'local/uckk:viewreports';

    /** Capability used as general campus access when available. */
    private const CAP_VIEW_CAMPUS = 'local/uckk:viewcampus';

    /** Valid profile visibility values. */
    private const VALID_VISIBILITIES = [
        'private',
        'user',
        'group',
        'course',
        'cohort',
        'institution',
        'public',
    ];

    /** Valid symbolic roles. These are not Moodle roles. */
    private const VALID_SYMBOLIC_ROLES = [
        'joueur',
        'joueur_lucide',
        'batisseur',
        'archiviste',
        'inquisiteur',
        'cartographe',
        'architecte_sens',
        'architecte_opportunites',
        'gardien_systemes_vivants',
    ];

    /**
     * Get a UCKK profile for a user.
     *
     * @param int $userid Moodle user id.
     * @param bool $createifmissing Create the profile if it does not exist.
     * @return stdClass|null
     */
    public static function get_profile(int $userid, bool $createifmissing = false): ?stdClass {
        global $DB;

        self::validate_userid($userid);

        $record = $DB->get_record(self::TABLE_PROFILE, ['userid' => $userid]);

        if (!$record && $createifmissing) {
            return self::create_profile($userid);
        }

        if (!$record) {
            return null;
        }

        return self::normalise_profile_record($record);
    }

    /**
     * Get or create a profile.
     *
     * @param int $userid Moodle user id.
     * @return stdClass
     */
    public static function get_or_create_profile(int $userid): stdClass {
        return self::get_profile($userid, true);
    }

    /**
     * Create a profile for a user.
     *
     * @param int $userid Moodle user id.
     * @param array<string, mixed> $data Optional initial data.
     * @return stdClass
     */
    public static function create_profile(int $userid, array $data = []): stdClass {
        global $DB, $USER;

        self::validate_userid($userid);
        self::require_manage_or_self($userid);

        if ($DB->record_exists(self::TABLE_PROFILE, ['userid' => $userid])) {
            return self::get_profile($userid);
        }

        $now = time();
        $actorid = self::get_actor_id();

        $record = new stdClass();
        $record->userid = $userid;
        $record->displaytitle = self::clean_optional_text($data['displaytitle'] ?? '');
        $record->symbolicroles = self::encode_json_list(self::normalise_symbolic_roles($data['symbolicroles'] ?? ['joueur']));
        $record->activepathwayids = self::encode_json_list(self::normalise_integer_list($data['activepathwayids'] ?? []));
        $record->portfolioarchiveid = self::normalise_optional_int($data['portfolioarchiveid'] ?? null);
        $record->integrityflags = self::encode_json_object($data['integrityflags'] ?? []);
        $record->visibility = self::normalise_visibility($data['visibility'] ?? self::get_default_visibility());
        $record->status = self::clean_status($data['status'] ?? self::STATUS_ACTIVE);
        $record->metadata = self::encode_json_object($data['metadata'] ?? []);
        $record->createdby = $actorid;
        $record->modifiedby = $actorid;
        $record->timecreated = $now;
        $record->timemodified = $now;

        $record->id = $DB->insert_record(self::TABLE_PROFILE, $record);

        self::record_provenance('profile_created', $record->id, $userid, [
            'createdby' => $actorid,
            'source' => 'profile_api',
        ]);

        return self::get_profile($userid);
    }

    /**
     * Update a user profile.
     *
     * @param int $userid Moodle user id.
     * @param array<string, mixed> $data Profile data.
     * @return stdClass
     */
    public static function update_profile(int $userid, array $data): stdClass {
        global $DB;

        self::validate_userid($userid);
        self::require_manage_or_self($userid);

        $profile = self::get_or_create_profile($userid);
        $before = clone($profile);

        if (array_key_exists('displaytitle', $data)) {
            $profile->displaytitle = self::clean_optional_text($data['displaytitle']);
        }

        if (array_key_exists('symbolicroles', $data)) {
            self::require_manage_profiles();
            $profile->symbolicroles = self::encode_json_list(self::normalise_symbolic_roles($data['symbolicroles']));
        }

        if (array_key_exists('activepathwayids', $data)) {
            self::require_manage_profiles();
            $profile->activepathwayids = self::encode_json_list(self::normalise_integer_list($data['activepathwayids']));
        }

        if (array_key_exists('portfolioarchiveid', $data)) {
            $profile->portfolioarchiveid = self::normalise_optional_int($data['portfolioarchiveid']);
        }

        if (array_key_exists('integrityflags', $data)) {
            self::require_manage_profiles();
            $profile->integrityflags = self::encode_json_object($data['integrityflags']);
        }

        if (array_key_exists('visibility', $data)) {
            $profile->visibility = self::normalise_visibility($data['visibility']);
        }

        if (array_key_exists('status', $data)) {
            self::require_manage_profiles();
            $profile->status = self::clean_status($data['status']);
        }

        if (array_key_exists('metadata', $data)) {
            $profile->metadata = self::encode_json_object($data['metadata']);
        }

        $profile->modifiedby = self::get_actor_id();
        $profile->timemodified = time();

        $DB->update_record(self::TABLE_PROFILE, self::prepare_record_for_storage($profile));

        self::record_provenance('profile_updated', (int)$profile->id, $userid, [
            'modifiedby' => $profile->modifiedby,
            'changedfields' => self::detect_changed_fields($before, $profile),
        ]);

        return self::get_profile($userid);
    }

    /**
     * Delete a profile.
     *
     * This method deletes the UCKK profile only. It does not delete the Moodle
     * user account and does not remove course enrolments or Moodle roles.
     *
     * @param int $userid Moodle user id.
     * @return bool
     */
    public static function delete_profile(int $userid): bool {
        global $DB;

        self::validate_userid($userid);
        self::require_manage_profiles();

        $profile = self::get_profile($userid);
        if (!$profile) {
            return false;
        }

        self::record_provenance('profile_deleted', (int)$profile->id, $userid, [
            'deletedby' => self::get_actor_id(),
        ]);

        return $DB->delete_records(self::TABLE_PROFILE, ['userid' => $userid]);
    }

    /**
     * Get a list of symbolic roles for a user.
     *
     * @param int $userid Moodle user id.
     * @return string[]
     */
    public static function get_symbolic_roles(int $userid): array {
        $profile = self::get_or_create_profile($userid);

        return self::decode_json_list($profile->symbolicroles);
    }

    /**
     * Replace symbolic roles for a user.
     *
     * Symbolic roles do not assign Moodle roles or capabilities.
     *
     * @param int $userid Moodle user id.
     * @param string[] $roles Symbolic role keys.
     * @return stdClass Updated profile.
     */
    public static function set_symbolic_roles(int $userid, array $roles): stdClass {
        self::require_manage_profiles();

        return self::update_profile($userid, [
            'symbolicroles' => self::normalise_symbolic_roles($roles),
        ]);
    }

    /**
     * Add one symbolic role to a user.
     *
     * @param int $userid Moodle user id.
     * @param string $role Symbolic role.
     * @return stdClass Updated profile.
     */
    public static function add_symbolic_role(int $userid, string $role): stdClass {
        self::require_manage_profiles();

        $role = self::normalise_symbolic_role($role);
        $roles = self::get_symbolic_roles($userid);

        if (!in_array($role, $roles, true)) {
            $roles[] = $role;
        }

        return self::set_symbolic_roles($userid, $roles);
    }

    /**
     * Remove one symbolic role from a user.
     *
     * @param int $userid Moodle user id.
     * @param string $role Symbolic role.
     * @return stdClass Updated profile.
     */
    public static function remove_symbolic_role(int $userid, string $role): stdClass {
        self::require_manage_profiles();

        $role = self::normalise_symbolic_role($role);
        $roles = array_values(array_filter(
            self::get_symbolic_roles($userid),
            static fn(string $existing): bool => $existing !== $role
        ));

        if (empty($roles)) {
            $roles[] = 'joueur';
        }

        return self::set_symbolic_roles($userid, $roles);
    }

    /**
     * Determine whether a user has a symbolic role.
     *
     * @param int $userid Moodle user id.
     * @param string $role Symbolic role.
     * @return bool
     */
    public static function has_symbolic_role(int $userid, string $role): bool {
        $role = self::normalise_symbolic_role($role);

        return in_array($role, self::get_symbolic_roles($userid), true);
    }

    /**
     * Get active pathway ids for a user.
     *
     * @param int $userid Moodle user id.
     * @return int[]
     */
    public static function get_active_pathways(int $userid): array {
        $profile = self::get_or_create_profile($userid);

        return self::normalise_integer_list(self::decode_json_list($profile->activepathwayids));
    }

    /**
     * Replace active pathways for a user.
     *
     * This does not enrol the user in courses.
     *
     * @param int $userid Moodle user id.
     * @param int[] $pathwayids Pathway ids.
     * @return stdClass Updated profile.
     */
    public static function set_active_pathways(int $userid, array $pathwayids): stdClass {
        self::require_manage_profiles();

        return self::update_profile($userid, [
            'activepathwayids' => self::normalise_integer_list($pathwayids),
        ]);
    }

    /**
     * Add an active pathway to a user.
     *
     * @param int $userid Moodle user id.
     * @param int $pathwayid Pathway id.
     * @return stdClass Updated profile.
     */
    public static function add_active_pathway(int $userid, int $pathwayid): stdClass {
        self::require_manage_profiles();

        if ($pathwayid <= 0) {
            throw new invalid_parameter_exception('Invalid pathway id.');
        }

        $pathways = self::get_active_pathways($userid);

        if (!in_array($pathwayid, $pathways, true)) {
            $pathways[] = $pathwayid;
        }

        return self::set_active_pathways($userid, $pathways);
    }

    /**
     * Remove an active pathway from a user.
     *
     * @param int $userid Moodle user id.
     * @param int $pathwayid Pathway id.
     * @return stdClass Updated profile.
     */
    public static function remove_active_pathway(int $userid, int $pathwayid): stdClass {
        self::require_manage_profiles();

        $pathways = array_values(array_filter(
            self::get_active_pathways($userid),
            static fn(int $existing): bool => $existing !== $pathwayid
        ));

        return self::set_active_pathways($userid, $pathways);
    }

    /**
     * Set the user profile display title.
     *
     * @param int $userid Moodle user id.
     * @param string $displaytitle Display title.
     * @return stdClass Updated profile.
     */
    public static function set_display_title(int $userid, string $displaytitle): stdClass {
        return self::update_profile($userid, [
            'displaytitle' => $displaytitle,
        ]);
    }

    /**
     * Set profile visibility.
     *
     * @param int $userid Moodle user id.
     * @param string $visibility Visibility key.
     * @return stdClass Updated profile.
     */
    public static function set_visibility(int $userid, string $visibility): stdClass {
        return self::update_profile($userid, [
            'visibility' => $visibility,
        ]);
    }

    /**
     * Set portfolio archive id.
     *
     * @param int $userid Moodle user id.
     * @param int|null $archiveitemid Archive item id.
     * @return stdClass Updated profile.
     */
    public static function set_portfolio_archive_id(int $userid, ?int $archiveitemid): stdClass {
        return self::update_profile($userid, [
            'portfolioarchiveid' => $archiveitemid,
        ]);
    }

    /**
     * Set metadata.
     *
     * @param int $userid Moodle user id.
     * @param array<string, mixed> $metadata Metadata.
     * @return stdClass Updated profile.
     */
    public static function set_metadata(int $userid, array $metadata): stdClass {
        return self::update_profile($userid, [
            'metadata' => $metadata,
        ]);
    }

    /**
     * Merge metadata into the existing profile metadata.
     *
     * @param int $userid Moodle user id.
     * @param array<string, mixed> $metadata Metadata patch.
     * @return stdClass Updated profile.
     */
    public static function merge_metadata(int $userid, array $metadata): stdClass {
        $profile = self::get_or_create_profile($userid);
        $existing = self::decode_json_object($profile->metadata);

        return self::set_metadata($userid, array_merge($existing, $metadata));
    }

    /**
     * Get profile metadata.
     *
     * @param int $userid Moodle user id.
     * @return array<string, mixed>
     */
    public static function get_metadata(int $userid): array {
        $profile = self::get_or_create_profile($userid);

        return self::decode_json_object($profile->metadata);
    }

    /**
     * Determine whether current user can view a profile.
     *
     * @param int $userid Profile owner id.
     * @param context|null $context Optional context.
     * @return bool
     */
    public static function can_view_profile(int $userid, ?context $context = null): bool {
        global $USER;

        self::validate_userid($userid, false);

        if (isloggedin() && !isguestuser() && (int)$USER->id === $userid) {
            return true;
        }

        $profile = self::get_profile($userid);
        if (!$profile) {
            return self::can_manage_profiles($context);
        }

        if ($profile->visibility === 'public') {
            return true;
        }

        $context = $context ?? context_user::instance($userid, IGNORE_MISSING);
        if (!$context) {
            $context = context_system::instance();
        }

        if (self::can_manage_profiles($context)) {
            return true;
        }

        if ($profile->visibility === 'institution') {
            return has_capability(self::CAP_VIEW_CAMPUS, context_system::instance());
        }

        return false;
    }

    /**
     * Require that current user can view a profile.
     *
     * @param int $userid Profile owner id.
     * @param context|null $context Optional context.
     * @return void
     */
    public static function require_can_view_profile(int $userid, ?context $context = null): void {
        if (!self::can_view_profile($userid, $context)) {
            $context = $context ?? context_user::instance($userid, IGNORE_MISSING) ?: context_system::instance();

            throw new required_capability_exception(
                $context,
                self::CAP_VIEW_CAMPUS,
                'nopermissions',
                ''
            );
        }
    }

    /**
     * Return profile data for templates or external services.
     *
     * @param int $userid Moodle user id.
     * @param bool $createifmissing Create profile if missing.
     * @return array<string, mixed>|null
     */
    public static function export_profile(int $userid, bool $createifmissing = false): ?array {
        self::require_can_view_profile($userid);

        $profile = self::get_profile($userid, $createifmissing);
        if (!$profile) {
            return null;
        }

        $user = core_user::get_user($userid, 'id, firstname, lastname, email, picture, imagealt', MUST_EXIST);
        $symbolicroles = self::decode_json_list($profile->symbolicroles);
        $pathways = self::normalise_integer_list(self::decode_json_list($profile->activepathwayids));

        return [
            'id' => (int)$profile->id,
            'userid' => $userid,
            'fullname' => fullname($user),
            'displaytitle' => $profile->displaytitle,
            'hasdisplaytitle' => $profile->displaytitle !== '',
            'symbolicroles' => array_map(static fn(string $role): array => [
                'key' => $role,
                'label' => self::get_symbolic_role_label($role),
            ], $symbolicroles),
            'hassymbolicroles' => !empty($symbolicroles),
            'activepathwayids' => $pathways,
            'hasactivepathways' => !empty($pathways),
            'portfolioarchiveid' => self::normalise_optional_int($profile->portfolioarchiveid),
            'hasportfolioarchive' => !empty($profile->portfolioarchiveid),
            'visibility' => $profile->visibility,
            'visibilitylabel' => self::get_visibility_label($profile->visibility),
            'status' => $profile->status,
            'metadata' => self::decode_json_object($profile->metadata),
            'timecreated' => (int)$profile->timecreated,
            'timemodified' => (int)$profile->timemodified,
        ];
    }

    /**
     * Search profiles.
     *
     * @param string $query Search query.
     * @param int $limit Maximum records.
     * @param int $offset Offset.
     * @return stdClass[]
     */
    public static function search_profiles(string $query = '', int $limit = 50, int $offset = 0): array {
        global $DB;

        self::require_manage_profiles();

        $limit = max(1, min(200, $limit));
        $offset = max(0, $offset);
        $query = trim($query);

        if ($query === '') {
            $records = $DB->get_records(
                self::TABLE_PROFILE,
                null,
                'timemodified DESC, userid ASC',
                '*',
                $offset,
                $limit
            );

            return array_map([self::class, 'normalise_profile_record'], array_values($records));
        }

        $like = $DB->sql_like('displaytitle', ':query', false, false);
        $params = ['query' => '%' . $DB->sql_like_escape($query) . '%'];

        $records = $DB->get_records_select(
            self::TABLE_PROFILE,
            $like,
            $params,
            'timemodified DESC, userid ASC',
            '*',
            $offset,
            $limit
        );

        return array_map([self::class, 'normalise_profile_record'], array_values($records));
    }

    /**
     * Return valid symbolic roles.
     *
     * @return string[]
     */
    public static function get_valid_symbolic_roles(): array {
        return self::VALID_SYMBOLIC_ROLES;
    }

    /**
     * Return valid visibility values.
     *
     * @return string[]
     */
    public static function get_valid_visibilities(): array {
        return self::VALID_VISIBILITIES;
    }

    /**
     * Check whether current user can manage profiles.
     *
     * @param context|null $context Optional context.
     * @return bool
     */
    public static function can_manage_profiles(?context $context = null): bool {
        $context = $context ?? context_system::instance();

        return has_capability(self::CAP_MANAGE_PROFILES, $context);
    }

    /**
     * Require manage profile capability.
     *
     * @param context|null $context Optional context.
     * @return void
     */
    public static function require_manage_profiles(?context $context = null): void {
        $context = $context ?? context_system::instance();
        require_capability(self::CAP_MANAGE_PROFILES, $context);
    }

    /**
     * Require profile management capability or self-service access.
     *
     * @param int $userid Profile owner id.
     * @return void
     */
    private static function require_manage_or_self(int $userid): void {
        global $USER;

        if (isloggedin() && !isguestuser() && (int)$USER->id === $userid) {
            return;
        }

        self::require_manage_profiles();
    }

    /**
     * Validate user id.
     *
     * @param int $userid User id.
     * @param bool $mustexist Whether the user must exist.
     * @return void
     */
    private static function validate_userid(int $userid, bool $mustexist = true): void {
        global $DB;

        if ($userid <= 0) {
            throw new invalid_parameter_exception('Invalid user id.');
        }

        if ($mustexist && !$DB->record_exists('user', ['id' => $userid, 'deleted' => 0])) {
            throw new invalid_parameter_exception('User does not exist.');
        }
    }

    /**
     * Normalise profile record for API use.
     *
     * @param stdClass $record Raw DB record.
     * @return stdClass
     */
    private static function normalise_profile_record(stdClass $record): stdClass {
        $record->id = (int)($record->id ?? 0);
        $record->userid = (int)($record->userid ?? 0);
        $record->displaytitle = (string)($record->displaytitle ?? '');
        $record->symbolicroles = self::encode_json_list(self::decode_json_list($record->symbolicroles ?? '[]'));
        $record->activepathwayids = self::encode_json_list(
            self::normalise_integer_list(self::decode_json_list($record->activepathwayids ?? '[]'))
        );
        $record->portfolioarchiveid = self::normalise_optional_int($record->portfolioarchiveid ?? null);
        $record->integrityflags = self::encode_json_object(self::decode_json_object($record->integrityflags ?? '{}'));
        $record->visibility = self::normalise_visibility($record->visibility ?? self::DEFAULT_VISIBILITY);
        $record->status = self::clean_status($record->status ?? self::STATUS_ACTIVE);
        $record->metadata = self::encode_json_object(self::decode_json_object($record->metadata ?? '{}'));
        $record->createdby = (int)($record->createdby ?? 0);
        $record->modifiedby = (int)($record->modifiedby ?? 0);
        $record->timecreated = (int)($record->timecreated ?? 0);
        $record->timemodified = (int)($record->timemodified ?? 0);

        return $record;
    }

    /**
     * Prepare profile record for DB storage.
     *
     * @param stdClass $record Profile record.
     * @return stdClass
     */
    private static function prepare_record_for_storage(stdClass $record): stdClass {
        $stored = new stdClass();
        $stored->id = (int)$record->id;
        $stored->userid = (int)$record->userid;
        $stored->displaytitle = self::clean_optional_text($record->displaytitle ?? '');
        $stored->symbolicroles = self::encode_json_list(self::decode_json_list($record->symbolicroles ?? '[]'));
        $stored->activepathwayids = self::encode_json_list(
            self::normalise_integer_list(self::decode_json_list($record->activepathwayids ?? '[]'))
        );
        $stored->portfolioarchiveid = self::normalise_optional_int($record->portfolioarchiveid ?? null);
        $stored->integrityflags = self::encode_json_object(self::decode_json_object($record->integrityflags ?? '{}'));
        $stored->visibility = self::normalise_visibility($record->visibility ?? self::DEFAULT_VISIBILITY);
        $stored->status = self::clean_status($record->status ?? self::STATUS_ACTIVE);
        $stored->metadata = self::encode_json_object(self::decode_json_object($record->metadata ?? '{}'));
        $stored->createdby = (int)($record->createdby ?? 0);
        $stored->modifiedby = (int)($record->modifiedby ?? 0);
        $stored->timecreated = (int)($record->timecreated ?? time());
        $stored->timemodified = (int)($record->timemodified ?? time());

        return $stored;
    }

    /**
     * Normalise symbolic roles.
     *
     * @param mixed $roles Roles.
     * @return string[]
     */
    private static function normalise_symbolic_roles($roles): array {
        if (is_string($roles)) {
            $roles = self::decode_json_list($roles);
        }

        if (!is_array($roles)) {
            throw new invalid_parameter_exception('Symbolic roles must be an array.');
        }

        $normalised = [];

        foreach ($roles as $role) {
            $normalised[] = self::normalise_symbolic_role((string)$role);
        }

        $normalised = array_values(array_unique($normalised));

        if (empty($normalised)) {
            $normalised[] = 'joueur';
        }

        return $normalised;
    }

    /**
     * Normalise one symbolic role.
     *
     * @param string $role Symbolic role.
     * @return string
     */
    private static function normalise_symbolic_role(string $role): string {
        $role = clean_param(\core_text::strtolower(trim($role)), PARAM_ALPHANUMEXT);

        if (!in_array($role, self::VALID_SYMBOLIC_ROLES, true)) {
            throw new invalid_parameter_exception('Invalid UCKK symbolic role: ' . $role);
        }

        return $role;
    }

    /**
     * Normalise visibility.
     *
     * @param mixed $visibility Visibility value.
     * @return string
     */
    private static function normalise_visibility($visibility): string {
        $visibility = clean_param(\core_text::strtolower(trim((string)$visibility)), PARAM_ALPHANUMEXT);

        if (!in_array($visibility, self::VALID_VISIBILITIES, true)) {
            throw new invalid_parameter_exception('Invalid UCKK visibility: ' . $visibility);
        }

        return $visibility;
    }

    /**
     * Get default visibility.
     *
     * @return string
     */
    private static function get_default_visibility(): string {
        $visibility = get_config('local_uckk', 'defaultpathwayvisibility');

        if (!$visibility || !in_array($visibility, self::VALID_VISIBILITIES, true)) {
            return self::DEFAULT_VISIBILITY;
        }

        return $visibility;
    }

    /**
     * Clean status.
     *
     * @param mixed $status Status.
     * @return string
     */
    private static function clean_status($status): string {
        $status = clean_param(\core_text::strtolower(trim((string)$status)), PARAM_ALPHANUMEXT);

        if ($status === '') {
            return self::STATUS_ACTIVE;
        }

        return $status;
    }

    /**
     * Clean optional text.
     *
     * @param mixed $value Value.
     * @return string
     */
    private static function clean_optional_text($value): string {
        return clean_param(trim((string)$value), PARAM_TEXT);
    }

    /**
     * Normalise optional integer.
     *
     * @param mixed $value Value.
     * @return int|null
     */
    private static function normalise_optional_int($value): ?int {
        if ($value === null || $value === '' || $value === false) {
            return null;
        }

        $value = (int)$value;

        return $value > 0 ? $value : null;
    }

    /**
     * Normalise integer list.
     *
     * @param mixed $values Values.
     * @return int[]
     */
    private static function normalise_integer_list($values): array {
        if (is_string($values)) {
            $values = self::decode_json_list($values);
        }

        if (!is_array($values)) {
            throw new invalid_parameter_exception('Expected integer list.');
        }

        $clean = [];

        foreach ($values as $value) {
            $value = (int)$value;
            if ($value > 0) {
                $clean[] = $value;
            }
        }

        return array_values(array_unique($clean));
    }

    /**
     * Encode a JSON list.
     *
     * @param array<int, mixed> $values Values.
     * @return string
     */
    private static function encode_json_list(array $values): string {
        return json_encode(array_values($values), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Decode a JSON list.
     *
     * @param mixed $json JSON string or array.
     * @return array<int, mixed>
     */
    private static function decode_json_list($json): array {
        if (is_array($json)) {
            return array_values($json);
        }

        if (!is_string($json) || trim($json) === '') {
            return [];
        }

        $decoded = json_decode($json, true);

        if (!is_array($decoded)) {
            return [];
        }

        return array_values($decoded);
    }

    /**
     * Encode JSON object.
     *
     * @param mixed $value Value.
     * @return string
     */
    private static function encode_json_object($value): string {
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            $value = is_array($decoded) ? $decoded : [];
        }

        if (!is_array($value)) {
            $value = [];
        }

        return json_encode((object)$value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Decode JSON object.
     *
     * @param mixed $json JSON string or array.
     * @return array<string, mixed>
     */
    private static function decode_json_object($json): array {
        if (is_array($json)) {
            return $json;
        }

        if (!is_string($json) || trim($json) === '') {
            return [];
        }

        $decoded = json_decode($json, true);

        if (!is_array($decoded)) {
            return [];
        }

        return $decoded;
    }

    /**
     * Detect changed fields between two records.
     *
     * @param stdClass $before Before.
     * @param stdClass $after After.
     * @return string[]
     */
    private static function detect_changed_fields(stdClass $before, stdClass $after): array {
        $fields = [
            'displaytitle',
            'symbolicroles',
            'activepathwayids',
            'portfolioarchiveid',
            'integrityflags',
            'visibility',
            'status',
            'metadata',
        ];

        $changed = [];

        foreach ($fields as $field) {
            if (($before->{$field} ?? null) !== ($after->{$field} ?? null)) {
                $changed[] = $field;
            }
        }

        return $changed;
    }

    /**
     * Record provenance if the provenance table exists.
     *
     * This method is deliberately best-effort. Provenance failures must not
     * break profile creation or updates unless the database itself fails.
     *
     * @param string $action Action name.
     * @param int $itemid Related profile id.
     * @param int $userid Profile owner id.
     * @param array<string, mixed> $metadata Metadata.
     * @return void
     */
    private static function record_provenance(string $action, int $itemid, int $userid, array $metadata = []): void {
        global $DB;

        try {
            $dbman = $DB->get_manager();
            $table = new \xmldb_table(self::TABLE_PROVENANCE);

            if (!$dbman->table_exists($table)) {
                return;
            }

            $now = time();

            $record = new stdClass();
            $record->component = 'local_uckk';
            $record->itemtype = 'player_profile';
            $record->itemid = $itemid;
            $record->userid = $userid;
            $record->action = clean_param($action, PARAM_ALPHANUMEXT);
            $record->source = 'profile_api';
            $record->state = 'recorded';
            $record->metadata = self::encode_json_object($metadata);
            $record->createdby = self::get_actor_id();
            $record->timecreated = $now;
            $record->timemodified = $now;

            $DB->insert_record(self::TABLE_PROVENANCE, $record);
        } catch (dml_exception $exception) {
            debugging('Unable to record UCKK profile provenance: ' . $exception->getMessage(), DEBUG_DEVELOPER);
        }
    }

    /**
     * Get current actor id.
     *
     * @return int
     */
    private static function get_actor_id(): int {
        global $USER;

        if (!empty($USER->id)) {
            return (int)$USER->id;
        }

        return 0;
    }

    /**
     * Get a symbolic role display label.
     *
     * @param string $role Role key.
     * @return string
     */
    private static function get_symbolic_role_label(string $role): string {
        $stringkey = 'symbolicrole_' . $role;

        if (get_string_manager()->string_exists($stringkey, 'local_uckk')) {
            return get_string($stringkey, 'local_uckk');
        }

        return ucfirst(str_replace('_', ' ', $role));
    }

    /**
     * Get visibility label.
     *
     * @param string $visibility Visibility key.
     * @return string
     */
    private static function get_visibility_label(string $visibility): string {
        $stringkey = 'visibility_' . $visibility;

        if (get_string_manager()->string_exists($stringkey, 'local_uckk')) {
            return get_string($stringkey, 'local_uckk');
        }

        return ucfirst(str_replace('_', ' ', $visibility));
    }
}
