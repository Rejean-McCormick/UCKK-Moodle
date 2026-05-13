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
 * Player profile value object for local_uckk.
 *
 * This class represents the UCKK profile attached to a Moodle user.
 *
 * It does not replace Moodle's user profile. Moodle remains the source of
 * identity, authentication, enrolment and account-level profile data.
 *
 * The UCKK player profile stores the UCKK semantic layer:
 *
 * - display title;
 * - symbolic roles;
 * - active pathways;
 * - portfolio reference;
 * - badge summary;
 * - competency summary;
 * - archive summary;
 * - integrity flags;
 * - visibility;
 * - metadata.
 *
 * This class should remain a local value object / adapter. Persistence should
 * be handled by API or service classes.
 *
 * @package    local_uckk
 * @copyright  2026 Univers-Cité King Klown
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_uckk\local;

use coding_exception;
use context_user;
use moodle_url;
use stdClass;

defined('MOODLE_INTERNAL') || die();

/**
 * UCKK player profile.
 *
 * @package local_uckk
 */
final class player_profile {
    /** Default profile status. */
    public const STATUS_ACTIVE = 'active';

    /** Draft profile status. */
    public const STATUS_DRAFT = 'draft';

    /** Suspended profile status. */
    public const STATUS_SUSPENDED = 'suspended';

    /** Archived profile status. */
    public const STATUS_ARCHIVED = 'archived';

    /** Visibility: private. */
    public const VISIBILITY_PRIVATE = 'private';

    /** Visibility: user. */
    public const VISIBILITY_USER = 'user';

    /** Visibility: cohort. */
    public const VISIBILITY_COHORT = 'cohort';

    /** Visibility: institution. */
    public const VISIBILITY_INSTITUTION = 'institution';

    /** Visibility: public. */
    public const VISIBILITY_PUBLIC = 'public';

    /** Symbolic role: Joueur. */
    public const ROLE_JOUEUR = 'joueur';

    /** Symbolic role: Joueur lucide. */
    public const ROLE_JOUEUR_LUCIDE = 'joueur_lucide';

    /** Symbolic role: Bâtisseur. */
    public const ROLE_BATISSEUR = 'batisseur';

    /** Symbolic role: Archiviste. */
    public const ROLE_ARCHIVISTE = 'archiviste';

    /** Symbolic role: Inquisiteur. */
    public const ROLE_INQUISITEUR = 'inquisiteur';

    /** Symbolic role: Cartographe. */
    public const ROLE_CARTOGRAPHE = 'cartographe';

    /** Symbolic role: Architecte du sens. */
    public const ROLE_ARCHITECTE_SENS = 'architecte_sens';

    /** Symbolic role: Architecte d’opportunités. */
    public const ROLE_ARCHITECTE_OPPORTUNITES = 'architecte_opportunites';

    /** Symbolic role: Gardien des systèmes vivants. */
    public const ROLE_GARDIEN_SYSTEMES_VIVANTS = 'gardien_systemes_vivants';

    /** Database table expected for player profiles. */
    public const TABLE = 'local_uckk_player';

    /** Moodle component name. */
    public const COMPONENT = 'local_uckk';

    /** @var int Profile id. */
    private int $id;

    /** @var int Moodle user id. */
    private int $userid;

    /** @var string Public or semi-public UCKK display title. */
    private string $displaytitle;

    /** @var string[] Symbolic UCKK roles. */
    private array $symbolicroles;

    /** @var string[] Active pathway keys or ids. */
    private array $activepathways;

    /** @var int|null Linked archive item or archive root id for portfolio. */
    private ?int $portfolioarchiveid;

    /** @var array<string, mixed> Badge summary data. */
    private array $badgesummary;

    /** @var array<string, mixed> Competency summary data. */
    private array $competencysummary;

    /** @var array<string, mixed> Archive summary data. */
    private array $archivesummary;

    /** @var array<string, mixed> Integrity flags. */
    private array $integrityflags;

    /** @var string Profile visibility. */
    private string $visibility;

    /** @var string Profile status. */
    private string $status;

    /** @var array<string, mixed> Additional metadata. */
    private array $metadata;

    /** @var int Creation timestamp. */
    private int $timecreated;

    /** @var int Modification timestamp. */
    private int $timemodified;

    /**
     * Constructor.
     *
     * @param int $userid Moodle user id.
     * @param int $id Profile id.
     * @param string $displaytitle UCKK display title.
     * @param string[] $symbolicroles Symbolic roles.
     * @param string[] $activepathways Active pathway keys or ids.
     * @param int|null $portfolioarchiveid Portfolio archive id.
     * @param array<string, mixed> $badgesummary Badge summary.
     * @param array<string, mixed> $competencysummary Competency summary.
     * @param array<string, mixed> $archivesummary Archive summary.
     * @param array<string, mixed> $integrityflags Integrity flags.
     * @param string $visibility Visibility.
     * @param string $status Status.
     * @param array<string, mixed> $metadata Metadata.
     * @param int|null $timecreated Created time.
     * @param int|null $timemodified Modified time.
     */
    private function __construct(
        int $userid,
        int $id = 0,
        string $displaytitle = '',
        array $symbolicroles = [],
        array $activepathways = [],
        ?int $portfolioarchiveid = null,
        array $badgesummary = [],
        array $competencysummary = [],
        array $archivesummary = [],
        array $integrityflags = [],
        string $visibility = self::VISIBILITY_PRIVATE,
        string $status = self::STATUS_ACTIVE,
        array $metadata = [],
        ?int $timecreated = null,
        ?int $timemodified = null
    ) {
        if ($userid <= 0) {
            throw new coding_exception('UCKK player profile requires a valid Moodle userid.');
        }

        $this->id = $id;
        $this->userid = $userid;
        $this->displaytitle = trim($displaytitle);
        $this->symbolicroles = self::normalise_symbolic_roles($symbolicroles);
        $this->activepathways = self::normalise_list($activepathways);
        $this->portfolioarchiveid = $portfolioarchiveid;
        $this->badgesummary = $badgesummary;
        $this->competencysummary = $competencysummary;
        $this->archivesummary = $archivesummary;
        $this->integrityflags = $integrityflags;
        $this->visibility = self::normalise_visibility($visibility);
        $this->status = self::normalise_status($status);
        $this->metadata = $metadata;
        $this->timecreated = $timecreated ?? time();
        $this->timemodified = $timemodified ?? time();

        if (empty($this->symbolicroles)) {
            $this->symbolicroles = [self::ROLE_JOUEUR];
        }
    }

    /**
     * Create a default UCKK profile for a Moodle user.
     *
     * @param int $userid Moodle user id.
     * @return self
     */
    public static function create_default(int $userid): self {
        return new self(
            userid: $userid,
            displaytitle: 'Joueur',
            symbolicroles: [self::ROLE_JOUEUR],
            visibility: self::VISIBILITY_PRIVATE,
            status: self::STATUS_ACTIVE
        );
    }

    /**
     * Create from a database record.
     *
     * Supported fields:
     * - id
     * - userid
     * - displaytitle
     * - symbolicroles JSON
     * - activepathways JSON
     * - portfolioarchiveid
     * - badgesummary JSON
     * - competencysummary JSON
     * - archivesummary JSON
     * - integrityflags JSON
     * - visibility
     * - status
     * - metadata JSON
     * - timecreated
     * - timemodified
     *
     * @param stdClass $record Database record.
     * @return self
     */
    public static function from_record(stdClass $record): self {
        return new self(
            userid: (int)($record->userid ?? 0),
            id: (int)($record->id ?? 0),
            displaytitle: (string)($record->displaytitle ?? ''),
            symbolicroles: self::decode_json_array($record->symbolicroles ?? null),
            activepathways: self::decode_json_array($record->activepathways ?? null),
            portfolioarchiveid: isset($record->portfolioarchiveid) && $record->portfolioarchiveid !== null
                ? (int)$record->portfolioarchiveid
                : null,
            badgesummary: self::decode_json_array($record->badgesummary ?? null),
            competencysummary: self::decode_json_array($record->competencysummary ?? null),
            archivesummary: self::decode_json_array($record->archivesummary ?? null),
            integrityflags: self::decode_json_array($record->integrityflags ?? null),
            visibility: (string)($record->visibility ?? self::VISIBILITY_PRIVATE),
            status: (string)($record->status ?? self::STATUS_ACTIVE),
            metadata: self::decode_json_array($record->metadata ?? null),
            timecreated: isset($record->timecreated) ? (int)$record->timecreated : null,
            timemodified: isset($record->timemodified) ? (int)$record->timemodified : null
        );
    }

    /**
     * Create from submitted data.
     *
     * @param stdClass|array $data Submitted data.
     * @return self
     */
    public static function from_data($data): self {
        $data = (object)$data;

        return new self(
            userid: (int)($data->userid ?? 0),
            id: (int)($data->id ?? 0),
            displaytitle: (string)($data->displaytitle ?? ''),
            symbolicroles: self::normalise_mixed_list($data->symbolicroles ?? []),
            activepathways: self::normalise_mixed_list($data->activepathways ?? []),
            portfolioarchiveid: isset($data->portfolioarchiveid) && $data->portfolioarchiveid !== ''
                ? (int)$data->portfolioarchiveid
                : null,
            badgesummary: self::normalise_array($data->badgesummary ?? []),
            competencysummary: self::normalise_array($data->competencysummary ?? []),
            archivesummary: self::normalise_array($data->archivesummary ?? []),
            integrityflags: self::normalise_array($data->integrityflags ?? []),
            visibility: (string)($data->visibility ?? self::VISIBILITY_PRIVATE),
            status: (string)($data->status ?? self::STATUS_ACTIVE),
            metadata: self::normalise_array($data->metadata ?? []),
            timecreated: isset($data->timecreated) ? (int)$data->timecreated : null,
            timemodified: isset($data->timemodified) ? (int)$data->timemodified : null
        );
    }

    /**
     * Load a profile by Moodle user id if the table exists.
     *
     * This is a convenience read method only. Persistence belongs in API/service
     * classes.
     *
     * @param int $userid Moodle user id.
     * @return self|null
     */
    public static function find_by_userid(int $userid): ?self {
        global $DB;

        if ($userid <= 0 || !$DB->get_manager()->table_exists(self::TABLE)) {
            return null;
        }

        $record = $DB->get_record(self::TABLE, ['userid' => $userid], '*', IGNORE_MISSING);

        if (!$record) {
            return null;
        }

        return self::from_record($record);
    }

    /**
     * Load an existing profile or return a default virtual profile.
     *
     * @param int $userid Moodle user id.
     * @return self
     */
    public static function get_or_default(int $userid): self {
        $profile = self::find_by_userid($userid);

        return $profile ?? self::create_default($userid);
    }

    /**
     * Return profile id.
     *
     * @return int
     */
    public function get_id(): int {
        return $this->id;
    }

    /**
     * Return Moodle user id.
     *
     * @return int
     */
    public function get_userid(): int {
        return $this->userid;
    }

    /**
     * Return display title.
     *
     * @return string
     */
    public function get_displaytitle(): string {
        if ($this->displaytitle !== '') {
            return $this->displaytitle;
        }

        if ($this->has_symbolic_role(self::ROLE_JOUEUR_LUCIDE)) {
            return 'Joueur lucide';
        }

        return 'Joueur';
    }

    /**
     * Return symbolic roles.
     *
     * @return string[]
     */
    public function get_symbolicroles(): array {
        return $this->symbolicroles;
    }

    /**
     * Return active pathway keys or ids.
     *
     * @return string[]
     */
    public function get_activepathways(): array {
        return $this->activepathways;
    }

    /**
     * Return portfolio archive id.
     *
     * @return int|null
     */
    public function get_portfolioarchiveid(): ?int {
        return $this->portfolioarchiveid;
    }

    /**
     * Return badge summary.
     *
     * @return array<string, mixed>
     */
    public function get_badgesummary(): array {
        return $this->badgesummary;
    }

    /**
     * Return competency summary.
     *
     * @return array<string, mixed>
     */
    public function get_competencysummary(): array {
        return $this->competencysummary;
    }

    /**
     * Return archive summary.
     *
     * @return array<string, mixed>
     */
    public function get_archivesummary(): array {
        return $this->archivesummary;
    }

    /**
     * Return integrity flags.
     *
     * @return array<string, mixed>
     */
    public function get_integrityflags(): array {
        return $this->integrityflags;
    }

    /**
     * Return visibility.
     *
     * @return string
     */
    public function get_visibility(): string {
        return $this->visibility;
    }

    /**
     * Return status.
     *
     * @return string
     */
    public function get_status(): string {
        return $this->status;
    }

    /**
     * Return metadata.
     *
     * @return array<string, mixed>
     */
    public function get_metadata(): array {
        return $this->metadata;
    }

    /**
     * Return time created.
     *
     * @return int
     */
    public function get_timecreated(): int {
        return $this->timecreated;
    }

    /**
     * Return time modified.
     *
     * @return int
     */
    public function get_timemodified(): int {
        return $this->timemodified;
    }

    /**
     * Return whether profile has a symbolic role.
     *
     * @param string $role Symbolic role.
     * @return bool
     */
    public function has_symbolic_role(string $role): bool {
        return in_array(self::normalise_key($role), $this->symbolicroles, true);
    }

    /**
     * Return whether profile has an active pathway.
     *
     * @param string|int $pathway Pathway key or id.
     * @return bool
     */
    public function has_active_pathway($pathway): bool {
        return in_array(self::normalise_key((string)$pathway), $this->activepathways, true);
    }

    /**
     * Return whether profile is visible publicly.
     *
     * @return bool
     */
    public function is_public(): bool {
        return $this->visibility === self::VISIBILITY_PUBLIC;
    }

    /**
     * Return whether profile is visible institutionally.
     *
     * @return bool
     */
    public function is_institution_visible(): bool {
        return in_array($this->visibility, [self::VISIBILITY_INSTITUTION, self::VISIBILITY_PUBLIC], true);
    }

    /**
     * Return whether profile is private.
     *
     * @return bool
     */
    public function is_private(): bool {
        return $this->visibility === self::VISIBILITY_PRIVATE;
    }

    /**
     * Return whether the profile is active.
     *
     * @return bool
     */
    public function is_active(): bool {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Return whether the profile is suspended.
     *
     * @return bool
     */
    public function is_suspended(): bool {
        return $this->status === self::STATUS_SUSPENDED;
    }

    /**
     * Return whether the profile is archived.
     *
     * @return bool
     */
    public function is_archived(): bool {
        return $this->status === self::STATUS_ARCHIVED;
    }

    /**
     * Return whether profile has integrity flags.
     *
     * @return bool
     */
    public function has_integrity_flags(): bool {
        return !empty($this->integrityflags);
    }

    /**
     * Return whether profile has blocking integrity flags.
     *
     * @return bool
     */
    public function has_blocking_integrity_flags(): bool {
        foreach ($this->integrityflags as $flag) {
            if (is_array($flag) && !empty($flag['blocking'])) {
                return true;
            }

            if (is_object($flag) && !empty($flag->blocking)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Return whether profile has a portfolio archive.
     *
     * @return bool
     */
    public function has_portfolio(): bool {
        return $this->portfolioarchiveid !== null && $this->portfolioarchiveid > 0;
    }

    /**
     * Return whether profile has badge summary.
     *
     * @return bool
     */
    public function has_badgesummary(): bool {
        return !empty($this->badgesummary);
    }

    /**
     * Return whether profile has competency summary.
     *
     * @return bool
     */
    public function has_competencysummary(): bool {
        return !empty($this->competencysummary);
    }

    /**
     * Return whether profile has archive summary.
     *
     * @return bool
     */
    public function has_archivesummary(): bool {
        return !empty($this->archivesummary);
    }

    /**
     * Return a user context for this profile.
     *
     * @return context_user
     */
    public function get_context(): context_user {
        return context_user::instance($this->userid);
    }

    /**
     * Return Moodle profile URL.
     *
     * @return moodle_url
     */
    public function get_moodle_profile_url(): moodle_url {
        return new moodle_url('/user/profile.php', ['id' => $this->userid]);
    }

    /**
     * Return a stable local profile URL.
     *
     * @return moodle_url
     */
    public function get_uckk_profile_url(): moodle_url {
        return new moodle_url('/local/uckk/profile.php', ['userid' => $this->userid]);
    }

    /**
     * Convert profile to a database record.
     *
     * @return stdClass
     */
    public function to_record(): stdClass {
        $record = new stdClass();

        if ($this->id > 0) {
            $record->id = $this->id;
        }

        $record->userid = $this->userid;
        $record->displaytitle = $this->get_displaytitle();
        $record->symbolicroles = self::encode_json($this->symbolicroles);
        $record->activepathways = self::encode_json($this->activepathways);
        $record->portfolioarchiveid = $this->portfolioarchiveid;
        $record->badgesummary = self::encode_json($this->badgesummary);
        $record->competencysummary = self::encode_json($this->competencysummary);
        $record->archivesummary = self::encode_json($this->archivesummary);
        $record->integrityflags = self::encode_json($this->integrityflags);
        $record->visibility = $this->visibility;
        $record->status = $this->status;
        $record->metadata = self::encode_json($this->metadata);
        $record->timecreated = $this->timecreated;
        $record->timemodified = $this->timemodified;

        return $record;
    }

    /**
     * Export profile for templates.
     *
     * @param bool $includeprivate Whether private fields may be exported.
     * @return array<string, mixed>
     */
    public function export_for_template(bool $includeprivate = false): array {
        $data = [
            'id' => $this->id,
            'userid' => $this->userid,
            'displaytitle' => $this->get_displaytitle(),
            'symbolicroles' => self::export_roles_for_template($this->symbolicroles),
            'hassymbolicroles' => !empty($this->symbolicroles),
            'primaryrole' => $this->get_primary_role(),
            'primaryrolelabel' => self::get_symbolic_role_label($this->get_primary_role()),
            'activepathways' => self::export_list_for_template($this->activepathways),
            'hasactivepathways' => !empty($this->activepathways),
            'portfolioarchiveid' => $this->portfolioarchiveid,
            'hasportfolio' => $this->has_portfolio(),
            'badgesummary' => $this->badgesummary,
            'hasbadgesummary' => $this->has_badgesummary(),
            'competencysummary' => $this->competencysummary,
            'hascompetencysummary' => $this->has_competencysummary(),
            'archivesummary' => $this->archivesummary,
            'hasarchivesummary' => $this->has_archivesummary(),
            'visibility' => $this->visibility,
            'visibilitylabel' => self::get_visibility_label($this->visibility),
            'status' => $this->status,
            'statuslabel' => self::get_status_label($this->status),
            'isactive' => $this->is_active(),
            'issuspended' => $this->is_suspended(),
            'isarchived' => $this->is_archived(),
            'ispublic' => $this->is_public(),
            'isinstitutionvisible' => $this->is_institution_visible(),
            'isprivate' => $this->is_private(),
            'profileurl' => $this->get_uckk_profile_url()->out(false),
            'moodleprofileurl' => $this->get_moodle_profile_url()->out(false),
            'timecreated' => $this->timecreated,
            'timemodified' => $this->timemodified,
        ];

        if ($includeprivate) {
            $data['integrityflags'] = self::export_integrity_flags_for_template($this->integrityflags);
            $data['hasintegrityflags'] = $this->has_integrity_flags();
            $data['hasblockingintegrityflags'] = $this->has_blocking_integrity_flags();
            $data['metadata'] = $this->metadata;
            $data['hasmetadata'] = !empty($this->metadata);
        } else {
            $data['integrityflags'] = [];
            $data['hasintegrityflags'] = false;
            $data['hasblockingintegrityflags'] = false;
            $data['metadata'] = [];
            $data['hasmetadata'] = false;
        }

        return $data;
    }

    /**
     * Export profile for privacy provider.
     *
     * @return stdClass
     */
    public function export_for_privacy(): stdClass {
        return (object)[
            'userid' => $this->userid,
            'displaytitle' => $this->get_displaytitle(),
            'symbolicroles' => $this->symbolicroles,
            'activepathways' => $this->activepathways,
            'portfolioarchiveid' => $this->portfolioarchiveid,
            'badgesummary' => $this->badgesummary,
            'competencysummary' => $this->competencysummary,
            'archivesummary' => $this->archivesummary,
            'integrityflags' => $this->integrityflags,
            'visibility' => $this->visibility,
            'status' => $this->status,
            'metadata' => $this->metadata,
            'timecreated' => $this->timecreated,
            'timemodified' => $this->timemodified,
        ];
    }

    /**
     * Return primary symbolic role.
     *
     * @return string
     */
    public function get_primary_role(): string {
        $priority = [
            self::ROLE_JOUEUR_LUCIDE,
            self::ROLE_BATISSEUR,
            self::ROLE_CARTOGRAPHE,
            self::ROLE_ARCHITECTE_SENS,
            self::ROLE_ARCHITECTE_OPPORTUNITES,
            self::ROLE_GARDIEN_SYSTEMES_VIVANTS,
            self::ROLE_ARCHIVISTE,
            self::ROLE_INQUISITEUR,
            self::ROLE_JOUEUR,
        ];

        foreach ($priority as $role) {
            if ($this->has_symbolic_role($role)) {
                return $role;
            }
        }

        return self::ROLE_JOUEUR;
    }

    /**
     * Return known symbolic role options.
     *
     * @return array<string, string>
     */
    public static function get_symbolic_role_options(): array {
        return [
            self::ROLE_JOUEUR => 'Joueur',
            self::ROLE_JOUEUR_LUCIDE => 'Joueur lucide',
            self::ROLE_BATISSEUR => 'Bâtisseur',
            self::ROLE_ARCHIVISTE => 'Archiviste',
            self::ROLE_INQUISITEUR => 'Inquisiteur',
            self::ROLE_CARTOGRAPHE => 'Cartographe',
            self::ROLE_ARCHITECTE_SENS => 'Architecte du sens',
            self::ROLE_ARCHITECTE_OPPORTUNITES => 'Architecte d’opportunités',
            self::ROLE_GARDIEN_SYSTEMES_VIVANTS => 'Gardien des systèmes vivants',
        ];
    }

    /**
     * Return known status options.
     *
     * @return array<string, string>
     */
    public static function get_status_options(): array {
        return [
            self::STATUS_DRAFT => 'Brouillon',
            self::STATUS_ACTIVE => 'Actif',
            self::STATUS_SUSPENDED => 'Suspendu',
            self::STATUS_ARCHIVED => 'Archivé',
        ];
    }

    /**
     * Return known visibility options.
     *
     * @return array<string, string>
     */
    public static function get_visibility_options(): array {
        return [
            self::VISIBILITY_PRIVATE => 'Privé',
            self::VISIBILITY_USER => 'Utilisateur',
            self::VISIBILITY_COHORT => 'Cohorte',
            self::VISIBILITY_INSTITUTION => 'Institution',
            self::VISIBILITY_PUBLIC => 'Public',
        ];
    }

    /**
     * Return symbolic role display label.
     *
     * @param string $role Symbolic role.
     * @return string
     */
    public static function get_symbolic_role_label(string $role): string {
        $role = self::normalise_key($role);
        $options = self::get_symbolic_role_options();

        return $options[$role] ?? ucfirst(str_replace('_', ' ', $role));
    }

    /**
     * Return status display label.
     *
     * @param string $status Status.
     * @return string
     */
    public static function get_status_label(string $status): string {
        $status = self::normalise_status($status);
        $options = self::get_status_options();

        return $options[$status] ?? ucfirst(str_replace('_', ' ', $status));
    }

    /**
     * Return visibility display label.
     *
     * @param string $visibility Visibility.
     * @return string
     */
    public static function get_visibility_label(string $visibility): string {
        $visibility = self::normalise_visibility($visibility);
        $options = self::get_visibility_options();

        return $options[$visibility] ?? ucfirst(str_replace('_', ' ', $visibility));
    }

    /**
     * Validate a profile record/data structure.
     *
     * @param stdClass|array $data Data.
     * @return array<string, string> Validation errors.
     */
    public static function validate($data): array {
        $data = (object)$data;
        $errors = [];

        $userid = (int)($data->userid ?? 0);
        if ($userid <= 0) {
            $errors['userid'] = 'Invalid Moodle user id.';
        }

        $displaytitle = trim((string)($data->displaytitle ?? ''));
        if (core_text::strlen($displaytitle) > 255) {
            $errors['displaytitle'] = 'Display title must not exceed 255 characters.';
        }

        $roles = self::normalise_mixed_list($data->symbolicroles ?? []);
        foreach ($roles as $role) {
            if (!array_key_exists($role, self::get_symbolic_role_options())) {
                $errors['symbolicroles'] = 'Unknown symbolic role: ' . $role;
                break;
            }
        }

        $visibility = (string)($data->visibility ?? self::VISIBILITY_PRIVATE);
        if (!array_key_exists(self::normalise_key($visibility), self::get_visibility_options())) {
            $errors['visibility'] = 'Unknown visibility: ' . $visibility;
        }

        $status = (string)($data->status ?? self::STATUS_ACTIVE);
        if (!array_key_exists(self::normalise_key($status), self::get_status_options())) {
            $errors['status'] = 'Unknown status: ' . $status;
        }

        return $errors;
    }

    /**
     * Normalise symbolic roles.
     *
     * @param array<int|string, mixed> $roles Raw roles.
     * @return string[]
     */
    private static function normalise_symbolic_roles(array $roles): array {
        $roles = self::normalise_list($roles);
        $valid = array_keys(self::get_symbolic_role_options());

        $roles = array_values(array_filter($roles, static function(string $role) use ($valid): bool {
            return in_array($role, $valid, true);
        }));

        if (empty($roles)) {
            return [self::ROLE_JOUEUR];
        }

        if (!in_array(self::ROLE_JOUEUR, $roles, true)) {
            array_unshift($roles, self::ROLE_JOUEUR);
        }

        return array_values(array_unique($roles));
    }

    /**
     * Normalise status.
     *
     * @param string $status Raw status.
     * @return string
     */
    private static function normalise_status(string $status): string {
        $status = self::normalise_key($status);

        if (array_key_exists($status, self::get_status_options())) {
            return $status;
        }

        return self::STATUS_ACTIVE;
    }

    /**
     * Normalise visibility.
     *
     * @param string $visibility Raw visibility.
     * @return string
     */
    private static function normalise_visibility(string $visibility): string {
        $visibility = self::normalise_key($visibility);

        if (array_key_exists($visibility, self::get_visibility_options())) {
            return $visibility;
        }

        return self::VISIBILITY_PRIVATE;
    }

    /**
     * Normalise a mixed list input.
     *
     * @param mixed $value Raw value.
     * @return string[]
     */
    private static function normalise_mixed_list($value): array {
        if (is_string($value)) {
            if (trim($value) === '') {
                return [];
            }

            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return self::normalise_list($decoded);
            }

            return self::normalise_list(explode(',', $value));
        }

        if (is_array($value)) {
            return self::normalise_list($value);
        }

        return [];
    }

    /**
     * Normalise a list of string-like values.
     *
     * @param array<int|string, mixed> $items Raw items.
     * @return string[]
     */
    private static function normalise_list(array $items): array {
        $normalised = [];

        foreach ($items as $item) {
            if (is_scalar($item)) {
                $key = self::normalise_key((string)$item);

                if ($key !== '') {
                    $normalised[] = $key;
                }
            }
        }

        return array_values(array_unique($normalised));
    }

    /**
     * Normalise an arbitrary metadata-like value into array.
     *
     * @param mixed $value Raw value.
     * @return array<string, mixed>
     */
    private static function normalise_array($value): array {
        if (is_array($value)) {
            return $value;
        }

        if (is_object($value)) {
            return (array)$value;
        }

        if (is_string($value) && trim($value) !== '') {
            $decoded = json_decode($value, true);

            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded;
            }
        }

        return [];
    }

    /**
     * Decode a JSON array safely.
     *
     * @param string|null $json JSON.
     * @return array<string|int, mixed>
     */
    private static function decode_json_array(?string $json): array {
        if ($json === null || trim($json) === '') {
            return [];
        }

        $decoded = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
            return [];
        }

        return $decoded;
    }

    /**
     * Encode data as JSON.
     *
     * @param mixed $data Data.
     * @return string
     */
    private static function encode_json($data): string {
        $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if ($json === false) {
            throw new coding_exception('Unable to encode UCKK player profile data as JSON: ' . json_last_error_msg());
        }

        return $json;
    }

    /**
     * Normalise a key.
     *
     * @param string $value Raw value.
     * @return string
     */
    private static function normalise_key(string $value): string {
        $value = trim(core_text::strtolower($value));
        $value = str_replace([' ', '-', '.', '/', '\\'], '_', $value);
        $value = preg_replace('/[^a-z0-9_]/', '', $value);
        $value = preg_replace('/_+/', '_', $value);

        return trim((string)$value, '_');
    }

    /**
     * Export symbolic roles for template.
     *
     * @param string[] $roles Roles.
     * @return array<int, array<string, string>>
     */
    private static function export_roles_for_template(array $roles): array {
        $items = [];

        foreach ($roles as $role) {
            $items[] = [
                'key' => $role,
                'label' => self::get_symbolic_role_label($role),
                'cssclass' => 'uckk-symbolic-role-' . $role,
            ];
        }

        return $items;
    }

    /**
     * Export a simple list for template.
     *
     * @param string[] $items Items.
     * @return array<int, array<string, string>>
     */
    private static function export_list_for_template(array $items): array {
        $output = [];

        foreach ($items as $item) {
            $output[] = [
                'key' => $item,
                'label' => ucfirst(str_replace('_', ' ', $item)),
            ];
        }

        return $output;
    }

    /**
     * Export integrity flags for template.
     *
     * @param array<string, mixed> $flags Flags.
     * @return array<int, array<string, mixed>>
     */
    private static function export_integrity_flags_for_template(array $flags): array {
        $output = [];

        foreach ($flags as $key => $flag) {
            if (is_array($flag)) {
                $output[] = [
                    'key' => is_string($key) ? $key : ($flag['key'] ?? 'integrity_flag'),
                    'label' => $flag['label'] ?? ucfirst(str_replace('_', ' ', (string)$key)),
                    'blocking' => !empty($flag['blocking']),
                    'status' => $flag['status'] ?? 'active',
                    'summary' => $flag['summary'] ?? '',
                ];
                continue;
            }

            if (is_scalar($flag)) {
                $output[] = [
                    'key' => is_string($key) ? $key : self::normalise_key((string)$flag),
                    'label' => is_string($key) ? ucfirst(str_replace('_', ' ', $key)) : (string)$flag,
                    'blocking' => false,
                    'status' => 'active',
                    'summary' => '',
                ];
            }
        }

        return $output;
    }
}

