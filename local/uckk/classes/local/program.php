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
 * Program value object for the UCKK institutional core plugin.
 *
 * A UCKK program is an internal academic structure of the Univers-Cité
 * King Klown. It can represent the tronc commun, an internal baccalauréat,
 * a mineure, a seminar, a laboratory or a transversal learning structure.
 *
 * This class is intentionally a local domain object:
 *
 * - it does not write to the database;
 * - it does not create Moodle courses;
 * - it does not assign users to pathways;
 * - it does not issue badges;
 * - it does not validate competencies;
 * - it does not make accreditation claims;
 * - it does not replace program_api or pathway_api.
 *
 * @package    local_uckk
 * @copyright  2026 Univers-Cité King Klown
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_uckk\local;

use context;
use context_coursecat;
use context_system;
use invalid_parameter_exception;
use stdClass;

defined('MOODLE_INTERNAL') || die();

/**
 * UCKK program value object.
 */
final class program {
    /** Database table name. */
    public const TABLE = 'local_uckk_program';

    /** Component name. */
    public const COMPONENT = 'local_uckk';

    /** Program type: tronc commun. */
    public const TYPE_TRONC_COMMUN = 'tronccommun';

    /** Program type: internal baccalauréat. */
    public const TYPE_BACCALAUREAT = 'baccalaureat';

    /** Program type: internal mineure. */
    public const TYPE_MINEURE = 'mineure';

    /** Program type: laboratory. */
    public const TYPE_LAB = 'lab';

    /** Program type: seminar. */
    public const TYPE_SEMINAR = 'seminar';

    /** Program type: transversal program. */
    public const TYPE_TRANSVERSAL = 'transversal';

    /** Status: draft. */
    public const STATUS_DRAFT = 'draft';

    /** Status: active. */
    public const STATUS_ACTIVE = 'active';

    /** Status: hidden. */
    public const STATUS_HIDDEN = 'hidden';

    /** Status: archived. */
    public const STATUS_ARCHIVED = 'archived';

    /** Status: deleted. */
    public const STATUS_DELETED = 'deleted';

    /** Internal recognition notice key. */
    public const INTERNAL_RECOGNITION_STRING = 'warning_internalrecognition';

    /** @var int Program id. */
    private int $id;

    /** @var string Stable shortname. */
    private string $shortname;

    /** @var string Display name. */
    private string $fullname;

    /** @var string Program type. */
    private string $programtype;

    /** @var int Moodle course category id, or 0. */
    private int $categoryid;

    /** @var string Program description. */
    private string $description;

    /** @var string Status. */
    private string $status;

    /** @var int Display order. */
    private int $sortorder;

    /** @var array<string, mixed> Metadata. */
    private array $metadata;

    /** @var int Created timestamp. */
    private int $timecreated;

    /** @var int Modified timestamp. */
    private int $timemodified;

    /** @var int User who created the record. */
    private int $createdby;

    /** @var int User who last modified the record. */
    private int $modifiedby;

    /**
     * Constructor.
     *
     * @param int $id Program id.
     * @param string $shortname Stable shortname.
     * @param string $fullname Program display name.
     * @param string $programtype Program type.
     * @param int $categoryid Moodle category id, or 0.
     * @param string $description Program description.
     * @param string $status Program status.
     * @param int $sortorder Display order.
     * @param array<string, mixed> $metadata Metadata.
     * @param int $timecreated Created timestamp.
     * @param int $timemodified Modified timestamp.
     * @param int $createdby Created by user id.
     * @param int $modifiedby Modified by user id.
     */
    private function __construct(
        int $id,
        string $shortname,
        string $fullname,
        string $programtype,
        int $categoryid,
        string $description,
        string $status,
        int $sortorder,
        array $metadata,
        int $timecreated,
        int $timemodified,
        int $createdby,
        int $modifiedby
    ) {
        $this->id = $id;
        $this->shortname = self::normalise_shortname($shortname);
        $this->fullname = self::normalise_fullname($fullname);
        $this->programtype = self::normalise_program_type($programtype);
        $this->categoryid = max(0, $categoryid);
        $this->description = $description;
        $this->status = self::normalise_status($status);
        $this->sortorder = $sortorder;
        $this->metadata = $metadata;
        $this->timecreated = $timecreated;
        $this->timemodified = $timemodified;
        $this->createdby = $createdby;
        $this->modifiedby = $modifiedby;
    }

    /**
     * Create a program object from a database record.
     *
     * @param stdClass $record Program-like record.
     * @return self
     */
    public static function from_record(stdClass $record): self {
        return new self(
            (int)($record->id ?? 0),
            (string)($record->shortname ?? ''),
            (string)($record->fullname ?? ''),
            (string)($record->programtype ?? self::TYPE_TRANSVERSAL),
            (int)($record->categoryid ?? 0),
            (string)($record->description ?? ''),
            (string)($record->status ?? self::STATUS_DRAFT),
            (int)($record->sortorder ?? 0),
            self::decode_metadata($record->metadata ?? '{}'),
            (int)($record->timecreated ?? 0),
            (int)($record->timemodified ?? 0),
            (int)($record->createdby ?? 0),
            (int)($record->modifiedby ?? 0)
        );
    }

    /**
     * Create a program object from an array.
     *
     * @param array<string, mixed> $data Program data.
     * @return self
     */
    public static function from_array(array $data): self {
        return self::from_record((object)$data);
    }

    /**
     * Create a new unsaved program object.
     *
     * @param string $shortname Stable shortname.
     * @param string $fullname Display name.
     * @param string $programtype Program type.
     * @param array<string, mixed> $options Optional values.
     * @return self
     */
    public static function create_unsaved(
        string $shortname,
        string $fullname,
        string $programtype,
        array $options = []
    ): self {
        global $USER;

        $now = time();
        $userid = (int)($USER->id ?? 0);

        return new self(
            0,
            $shortname,
            $fullname,
            $programtype,
            (int)($options['categoryid'] ?? 0),
            (string)($options['description'] ?? ''),
            (string)($options['status'] ?? self::STATUS_DRAFT),
            (int)($options['sortorder'] ?? 0),
            is_array($options['metadata'] ?? null) ? $options['metadata'] : self::decode_metadata($options['metadata'] ?? '{}'),
            (int)($options['timecreated'] ?? $now),
            (int)($options['timemodified'] ?? $now),
            (int)($options['createdby'] ?? $userid),
            (int)($options['modifiedby'] ?? $userid)
        );
    }

    /**
     * Return all supported program types.
     *
     * @return string[]
     */
    public static function get_supported_program_types(): array {
        return [
            self::TYPE_TRONC_COMMUN,
            self::TYPE_BACCALAUREAT,
            self::TYPE_MINEURE,
            self::TYPE_LAB,
            self::TYPE_SEMINAR,
            self::TYPE_TRANSVERSAL,
        ];
    }

    /**
     * Return all supported statuses.
     *
     * @return string[]
     */
    public static function get_supported_statuses(): array {
        return [
            self::STATUS_DRAFT,
            self::STATUS_ACTIVE,
            self::STATUS_HIDDEN,
            self::STATUS_ARCHIVED,
            self::STATUS_DELETED,
        ];
    }

    /**
     * Get id.
     *
     * @return int
     */
    public function get_id(): int {
        return $this->id;
    }

    /**
     * Get shortname.
     *
     * @return string
     */
    public function get_shortname(): string {
        return $this->shortname;
    }

    /**
     * Get fullname.
     *
     * @return string
     */
    public function get_fullname(): string {
        return $this->fullname;
    }

    /**
     * Get program type.
     *
     * @return string
     */
    public function get_programtype(): string {
        return $this->programtype;
    }

    /**
     * Get category id.
     *
     * @return int
     */
    public function get_categoryid(): int {
        return $this->categoryid;
    }

    /**
     * Get description.
     *
     * @return string
     */
    public function get_description(): string {
        return $this->description;
    }

    /**
     * Get status.
     *
     * @return string
     */
    public function get_status(): string {
        return $this->status;
    }

    /**
     * Get sort order.
     *
     * @return int
     */
    public function get_sortorder(): int {
        return $this->sortorder;
    }

    /**
     * Get metadata.
     *
     * @return array<string, mixed>
     */
    public function get_metadata(): array {
        return $this->metadata;
    }

    /**
     * Get time created.
     *
     * @return int
     */
    public function get_timecreated(): int {
        return $this->timecreated;
    }

    /**
     * Get time modified.
     *
     * @return int
     */
    public function get_timemodified(): int {
        return $this->timemodified;
    }

    /**
     * Get creator user id.
     *
     * @return int
     */
    public function get_createdby(): int {
        return $this->createdby;
    }

    /**
     * Get modifier user id.
     *
     * @return int
     */
    public function get_modifiedby(): int {
        return $this->modifiedby;
    }

    /**
     * Whether this program is saved.
     *
     * @return bool
     */
    public function is_saved(): bool {
        return $this->id > 0;
    }

    /**
     * Whether this program is active.
     *
     * @return bool
     */
    public function is_active(): bool {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Whether this program is draft.
     *
     * @return bool
     */
    public function is_draft(): bool {
        return $this->status === self::STATUS_DRAFT;
    }

    /**
     * Whether this program is hidden.
     *
     * @return bool
     */
    public function is_hidden(): bool {
        return $this->status === self::STATUS_HIDDEN;
    }

    /**
     * Whether this program is archived.
     *
     * @return bool
     */
    public function is_archived(): bool {
        return $this->status === self::STATUS_ARCHIVED;
    }

    /**
     * Whether this program is deleted.
     *
     * @return bool
     */
    public function is_deleted(): bool {
        return $this->status === self::STATUS_DELETED;
    }

    /**
     * Whether this is the tronc commun.
     *
     * @return bool
     */
    public function is_tronc_commun(): bool {
        return $this->programtype === self::TYPE_TRONC_COMMUN;
    }

    /**
     * Whether this is an internal baccalauréat.
     *
     * @return bool
     */
    public function is_baccalaureat(): bool {
        return $this->programtype === self::TYPE_BACCALAUREAT;
    }

    /**
     * Whether this is an internal mineure.
     *
     * @return bool
     */
    public function is_mineure(): bool {
        return $this->programtype === self::TYPE_MINEURE;
    }

    /**
     * Whether this is a laboratory.
     *
     * @return bool
     */
    public function is_lab(): bool {
        return $this->programtype === self::TYPE_LAB;
    }

    /**
     * Whether this is a seminar.
     *
     * @return bool
     */
    public function is_seminar(): bool {
        return $this->programtype === self::TYPE_SEMINAR;
    }

    /**
     * Whether this is a transversal program.
     *
     * @return bool
     */
    public function is_transversal(): bool {
        return $this->programtype === self::TYPE_TRANSVERSAL;
    }

    /**
     * Whether this program has a linked Moodle category.
     *
     * @return bool
     */
    public function has_category(): bool {
        return $this->categoryid > 0;
    }

    /**
     * Whether this program has metadata.
     *
     * @return bool
     */
    public function has_metadata(): bool {
        return !empty($this->metadata);
    }

    /**
     * Return a metadata value.
     *
     * @param string $key Metadata key.
     * @param mixed $default Default value.
     * @return mixed
     */
    public function get_metadata_value(string $key, $default = null) {
        return $this->metadata[$key] ?? $default;
    }

    /**
     * Get display label for program type.
     *
     * @return string
     */
    public function get_programtype_label(): string {
        return self::get_type_label($this->programtype);
    }

    /**
     * Get display label for status.
     *
     * @return string
     */
    public function get_status_label(): string {
        return self::get_status_display_label($this->status);
    }

    /**
     * Get context for this program.
     *
     * @return context
     */
    public function get_context(): context {
        if ($this->categoryid > 0) {
            return context_coursecat::instance($this->categoryid);
        }

        return context_system::instance();
    }

    /**
     * Convert to a database-ready record.
     *
     * @return stdClass
     */
    public function to_record(): stdClass {
        $record = (object)[
            'shortname' => $this->shortname,
            'fullname' => $this->fullname,
            'programtype' => $this->programtype,
            'categoryid' => $this->categoryid > 0 ? $this->categoryid : null,
            'description' => $this->description,
            'status' => $this->status,
            'sortorder' => $this->sortorder,
            'metadata' => self::encode_metadata($this->metadata),
            'timecreated' => $this->timecreated,
            'timemodified' => $this->timemodified,
            'createdby' => $this->createdby,
            'modifiedby' => $this->modifiedby,
        ];

        if ($this->id > 0) {
            $record->id = $this->id;
        }

        return $record;
    }

    /**
     * Convert to a plain array.
     *
     * @return array<string, mixed>
     */
    public function to_array(): array {
        return [
            'id' => $this->id,
            'shortname' => $this->shortname,
            'fullname' => $this->fullname,
            'programtype' => $this->programtype,
            'programtypelabel' => $this->get_programtype_label(),
            'categoryid' => $this->categoryid,
            'description' => $this->description,
            'status' => $this->status,
            'statuslabel' => $this->get_status_label(),
            'sortorder' => $this->sortorder,
            'metadata' => $this->metadata,
            'timecreated' => $this->timecreated,
            'timemodified' => $this->timemodified,
            'createdby' => $this->createdby,
            'modifiedby' => $this->modifiedby,
            'isactive' => $this->is_active(),
            'isdraft' => $this->is_draft(),
            'ishidden' => $this->is_hidden(),
            'isarchived' => $this->is_archived(),
            'isdeleted' => $this->is_deleted(),
            'istronccommun' => $this->is_tronc_commun(),
            'isbaccalaureat' => $this->is_baccalaureat(),
            'ismineure' => $this->is_mineure(),
            'islab' => $this->is_lab(),
            'isseminar' => $this->is_seminar(),
            'istransversal' => $this->is_transversal(),
        ];
    }

    /**
     * Export for Mustache templates.
     *
     * @param array<string, mixed> $overrides Optional overrides prepared by renderer/API.
     * @return array<string, mixed>
     */
    public function export_for_template(array $overrides = []): array {
        $data = $this->to_array();

        $data['metadatajson'] = self::encode_metadata($this->metadata);
        $data['hascategory'] = $this->has_category();
        $data['hasdescription'] = trim($this->description) !== '';
        $data['hasmetadata'] = $this->has_metadata();
        $data['internalrecognitionnotice'] = get_string(self::INTERNAL_RECOGNITION_STRING, self::COMPONENT);
        $data['cssclass'] = $this->get_css_classes();

        return array_merge($data, $overrides);
    }

    /**
     * Get CSS classes for display.
     *
     * @param string[] $extra Extra classes.
     * @return string
     */
    public function get_css_classes(array $extra = []): string {
        $classes = [
            'uckk-program',
            'uckk-program-' . $this->programtype,
            'uckk-program-status-' . $this->status,
        ];

        if ($this->is_active()) {
            $classes[] = 'uckk-program-active';
        }

        if ($this->is_archived()) {
            $classes[] = 'uckk-program-archived';
        }

        if ($this->is_hidden()) {
            $classes[] = 'uckk-program-hidden';
        }

        if ($this->is_deleted()) {
            $classes[] = 'uckk-program-deleted';
        }

        $classes = array_merge($classes, $extra);
        $classes = array_filter(array_map('trim', $classes));

        return implode(' ', array_unique($classes));
    }

    /**
     * Return a cloned object with a new status.
     *
     * @param string $status New status.
     * @param int|null $modifiedby Modifier user id.
     * @return self
     */
    public function with_status(string $status, ?int $modifiedby = null): self {
        return new self(
            $this->id,
            $this->shortname,
            $this->fullname,
            $this->programtype,
            $this->categoryid,
            $this->description,
            $status,
            $this->sortorder,
            $this->metadata,
            $this->timecreated,
            time(),
            $this->createdby,
            $modifiedby ?? $this->modifiedby
        );
    }

    /**
     * Return a cloned object with updated metadata.
     *
     * @param array<string, mixed> $metadata Metadata.
     * @param int|null $modifiedby Modifier user id.
     * @return self
     */
    public function with_metadata(array $metadata, ?int $modifiedby = null): self {
        return new self(
            $this->id,
            $this->shortname,
            $this->fullname,
            $this->programtype,
            $this->categoryid,
            $this->description,
            $this->status,
            $this->sortorder,
            $metadata,
            $this->timecreated,
            time(),
            $this->createdby,
            $modifiedby ?? $this->modifiedby
        );
    }

    /**
     * Return a cloned object with updated category.
     *
     * @param int $categoryid Moodle category id.
     * @param int|null $modifiedby Modifier user id.
     * @return self
     */
    public function with_categoryid(int $categoryid, ?int $modifiedby = null): self {
        return new self(
            $this->id,
            $this->shortname,
            $this->fullname,
            $this->programtype,
            max(0, $categoryid),
            $this->description,
            $this->status,
            $this->sortorder,
            $this->metadata,
            $this->timecreated,
            time(),
            $this->createdby,
            $modifiedby ?? $this->modifiedby
        );
    }

    /**
     * Validate this program object.
     *
     * @return void
     * @throws invalid_parameter_exception
     */
    public function validate(): void {
        if ($this->shortname === '') {
            throw new invalid_parameter_exception('Missing UCKK program shortname.');
        }

        if ($this->fullname === '') {
            throw new invalid_parameter_exception('Missing UCKK program fullname.');
        }

        if (!in_array($this->programtype, self::get_supported_program_types(), true)) {
            throw new invalid_parameter_exception('Invalid UCKK program type.');
        }

        if (!in_array($this->status, self::get_supported_statuses(), true)) {
            throw new invalid_parameter_exception('Invalid UCKK program status.');
        }
    }

    /**
     * Get type label.
     *
     * @param string $programtype Program type.
     * @return string
     */
    public static function get_type_label(string $programtype): string {
        $programtype = self::normalise_optional_alphanumext($programtype);

        if ($programtype === '') {
            return '';
        }

        $stringkey = 'programtype_' . $programtype;

        if (get_string_manager()->string_exists($stringkey, self::COMPONENT)) {
            return get_string($stringkey, self::COMPONENT);
        }

        return ucfirst(str_replace('_', ' ', $programtype));
    }

    /**
     * Get status display label.
     *
     * @param string $status Status.
     * @return string
     */
    public static function get_status_display_label(string $status): string {
        $status = self::normalise_optional_alphanumext($status);

        if ($status === '') {
            return '';
        }

        $stringkey = 'status_' . $status;

        if (get_string_manager()->string_exists($stringkey, self::COMPONENT)) {
            return get_string($stringkey, self::COMPONENT);
        }

        return ucfirst(str_replace('_', ' ', $status));
    }

    /**
     * Decode metadata from JSON or array.
     *
     * @param mixed $metadata Metadata.
     * @return array<string, mixed>
     */
    public static function decode_metadata($metadata): array {
        if (is_array($metadata)) {
            return $metadata;
        }

        if (!is_string($metadata) || trim($metadata) === '') {
            return [];
        }

        $decoded = json_decode($metadata, true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Encode metadata.
     *
     * @param array<string, mixed> $metadata Metadata.
     * @return string
     */
    public static function encode_metadata(array $metadata): string {
        return json_encode((object)$metadata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Normalise shortname.
     *
     * @param string $shortname Shortname.
     * @return string
     */
    private static function normalise_shortname(string $shortname): string {
        $shortname = trim(\core_text::strtolower($shortname));
        $shortname = str_replace(' ', '_', $shortname);

        return clean_param($shortname, PARAM_ALPHANUMEXT);
    }

    /**
     * Normalise fullname.
     *
     * @param string $fullname Fullname.
     * @return string
     */
    private static function normalise_fullname(string $fullname): string {
        return clean_param(trim($fullname), PARAM_TEXT);
    }

    /**
     * Normalise program type.
     *
     * @param string $programtype Program type.
     * @return string
     * @throws invalid_parameter_exception
     */
    private static function normalise_program_type(string $programtype): string {
        $programtype = self::normalise_optional_alphanumext($programtype);

        if ($programtype === '') {
            $programtype = self::TYPE_TRANSVERSAL;
        }

        if (!in_array($programtype, self::get_supported_program_types(), true)) {
            throw new invalid_parameter_exception('Invalid UCKK program type.');
        }

        return $programtype;
    }

    /**
     * Normalise status.
     *
     * @param string $status Status.
     * @return string
     * @throws invalid_parameter_exception
     */
    private static function normalise_status(string $status): string {
        $status = self::normalise_optional_alphanumext($status);

        if ($status === '') {
            $status = self::STATUS_DRAFT;
        }

        if (!in_array($status, self::get_supported_statuses(), true)) {
            throw new invalid_parameter_exception('Invalid UCKK program status.');
        }

        return $status;
    }

    /**
     * Normalise optional alphanumext-like value.
     *
     * @param mixed $value Raw value.
     * @return string
     */
    private static function normalise_optional_alphanumext($value): string {
        $value = trim(\core_text::strtolower((string)$value));

        if ($value === '') {
            return '';
        }

        return clean_param($value, PARAM_ALPHANUMEXT);
    }
}