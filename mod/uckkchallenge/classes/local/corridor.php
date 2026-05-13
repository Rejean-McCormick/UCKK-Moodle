<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

/**
 * Corridor of action value object for UCKK Challenge.
 *
 * @package    mod_uckkchallenge
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace mod_uckkchallenge\local;

use coding_exception;
use JsonException;
use stdClass;

defined('MOODLE_INTERNAL') || die();

/**
 * Immutable corridor of action.
 *
 * A corridor describes one legitimate path of action inside a challenge:
 * the intended audience, entry point, permitted action mode, limits, risks,
 * and relation to evidence. It does not decide permissions, completion,
 * validation, grading, archive export, or integrity outcomes.
 */
final class corridor {
    /**
     * Draft status.
     */
    public const STATUS_DRAFT = 'draft';

    /**
     * Active status.
     */
    public const STATUS_ACTIVE = 'active';

    /**
     * Hidden status.
     */
    public const STATUS_HIDDEN = 'hidden';

    /**
     * Archived status.
     */
    public const STATUS_ARCHIVED = 'archived';

    /**
     * Low-risk corridor.
     */
    public const RISK_LOW = 'low';

    /**
     * Medium-risk corridor.
     */
    public const RISK_MEDIUM = 'medium';

    /**
     * High-risk corridor.
     */
    public const RISK_HIGH = 'high';

    /**
     * Restricted integrity corridor.
     */
    public const RISK_RESTRICTED_INTEGRITY = 'restricted_integrity';

    /**
     * Allowed statuses.
     */
    private const ALLOWED_STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_ACTIVE,
        self::STATUS_HIDDEN,
        self::STATUS_ARCHIVED,
    ];

    /**
     * Allowed risk levels.
     */
    private const ALLOWED_RISK_LEVELS = [
        self::RISK_LOW,
        self::RISK_MEDIUM,
        self::RISK_HIGH,
        self::RISK_RESTRICTED_INTEGRITY,
    ];

    /**
     * Allowed visibilities.
     */
    private const ALLOWED_VISIBILITIES = [
        'private',
        'user',
        'group',
        'course',
        'cohort',
        'program',
        'institution',
        'public',
        'restricted',
        'restricted_integrity',
        'hidden',
        'archived',
    ];

    /**
     * Corridor id.
     *
     * @var int
     */
    private int $id;

    /**
     * Parent challenge id.
     *
     * @var int
     */
    private int $challengeid;

    /**
     * Stable corridor key.
     *
     * @var string
     */
    private string $shortname;

    /**
     * Display name.
     *
     * @var string
     */
    private string $name;

    /**
     * Corridor description.
     *
     * @var string
     */
    private string $description;

    /**
     * Intended audience.
     *
     * @var string
     */
    private string $audience;

    /**
     * Entry point.
     *
     * @var string
     */
    private string $entrypoint;

    /**
     * Action mode.
     *
     * @var string
     */
    private string $actionmode;

    /**
     * Evidence expectation.
     *
     * @var string
     */
    private string $evidencerequirement;

    /**
     * Ethical limits.
     *
     * @var string
     */
    private string $ethicallimits;

    /**
     * Risk level.
     *
     * @var string
     */
    private string $risklevel;

    /**
     * Visibility.
     *
     * @var string
     */
    private string $visibility;

    /**
     * Status.
     *
     * @var string
     */
    private string $status;

    /**
     * Whether this corridor is required.
     *
     * @var bool
     */
    private bool $required;

    /**
     * Sort order.
     *
     * @var int
     */
    private int $sortorder;

    /**
     * Created timestamp.
     *
     * @var int
     */
    private int $timecreated;

    /**
     * Modified timestamp.
     *
     * @var int
     */
    private int $timemodified;

    /**
     * Variable metadata.
     *
     * @var array<string, mixed>
     */
    private array $metadata;

    /**
     * Constructor.
     *
     * @param int $id Corridor id.
     * @param int $challengeid Parent challenge id.
     * @param string $shortname Stable key.
     * @param string $name Display name.
     * @param string $description Description.
     * @param string $audience Intended audience.
     * @param string $entrypoint Entry point.
     * @param string $actionmode Action mode.
     * @param string $evidencerequirement Evidence expectation.
     * @param string $ethicallimits Ethical limits.
     * @param string $risklevel Risk level.
     * @param string $visibility Visibility.
     * @param string $status Status.
     * @param bool $required Whether this corridor is required.
     * @param int $sortorder Sort order.
     * @param int $timecreated Created timestamp.
     * @param int $timemodified Modified timestamp.
     * @param array<string, mixed> $metadata Metadata.
     */
    private function __construct(
        int $id,
        int $challengeid,
        string $shortname,
        string $name,
        string $description,
        string $audience,
        string $entrypoint,
        string $actionmode,
        string $evidencerequirement,
        string $ethicallimits,
        string $risklevel,
        string $visibility,
        string $status,
        bool $required,
        int $sortorder,
        int $timecreated,
        int $timemodified,
        array $metadata
    ) {
        $this->id = max(0, $id);
        $this->challengeid = max(0, $challengeid);
        $this->shortname = self::normalise_shortname($shortname);
        $this->name = self::normalise_text($name);
        $this->description = trim($description);
        $this->audience = self::normalise_text($audience);
        $this->entrypoint = self::normalise_text($entrypoint);
        $this->actionmode = self::normalise_text($actionmode);
        $this->evidencerequirement = trim($evidencerequirement);
        $this->ethicallimits = trim($ethicallimits);
        $this->risklevel = self::normalise_choice($risklevel, self::ALLOWED_RISK_LEVELS, self::RISK_MEDIUM);
        $this->visibility = self::normalise_choice($visibility, self::ALLOWED_VISIBILITIES, 'course');
        $this->status = self::normalise_choice($status, self::ALLOWED_STATUSES, self::STATUS_DRAFT);
        $this->required = $required;
        $this->sortorder = max(0, $sortorder);
        $this->timecreated = max(0, $timecreated);
        $this->timemodified = max(0, $timemodified);
        $this->metadata = self::normalise_metadata($metadata);

        $this->validate();
    }

    /**
     * Create a corridor from an array.
     *
     * @param array<string, mixed> $data Input data.
     * @return self
     */
    public static function from_array(array $data): self {
        return new self(
            (int)($data['id'] ?? 0),
            (int)($data['challengeid'] ?? 0),
            (string)($data['shortname'] ?? ''),
            (string)($data['name'] ?? ''),
            (string)($data['description'] ?? ''),
            (string)($data['audience'] ?? ''),
            (string)($data['entrypoint'] ?? ''),
            (string)($data['actionmode'] ?? ''),
            (string)($data['evidencerequirement'] ?? ''),
            (string)($data['ethicallimits'] ?? ''),
            (string)($data['risklevel'] ?? self::RISK_MEDIUM),
            (string)($data['visibility'] ?? 'course'),
            (string)($data['status'] ?? self::STATUS_DRAFT),
            self::to_bool($data['required'] ?? false),
            (int)($data['sortorder'] ?? 0),
            (int)($data['timecreated'] ?? 0),
            (int)($data['timemodified'] ?? 0),
            self::metadata_from_mixed($data['metadata'] ?? [])
        );
    }

    /**
     * Create a corridor from a database record.
     *
     * Expected source table: {uckkchallenge_corr}.
     *
     * @param stdClass $record Database record.
     * @return self
     */
    public static function from_record(stdClass $record): self {
        return self::from_array((array)$record);
    }

    /**
     * Create a new active corridor with canonical defaults.
     *
     * @param int $challengeid Challenge id.
     * @param string $shortname Stable corridor key.
     * @param string $name Display name.
     * @param string $description Description.
     * @param string $audience Intended audience.
     * @param string $entrypoint Entry point.
     * @param string $actionmode Action mode.
     * @param string $evidencerequirement Evidence expectation.
     * @param string $ethicallimits Ethical limits.
     * @param int $sortorder Sort order.
     * @param array<string, mixed> $metadata Metadata.
     * @return self
     */
    public static function create_active(
        int $challengeid,
        string $shortname,
        string $name,
        string $description,
        string $audience,
        string $entrypoint,
        string $actionmode,
        string $evidencerequirement,
        string $ethicallimits,
        int $sortorder = 0,
        array $metadata = []
    ): self {
        $now = time();

        return new self(
            0,
            $challengeid,
            $shortname,
            $name,
            $description,
            $audience,
            $entrypoint,
            $actionmode,
            $evidencerequirement,
            $ethicallimits,
            self::RISK_MEDIUM,
            'course',
            self::STATUS_ACTIVE,
            false,
            $sortorder,
            $now,
            $now,
            $metadata
        );
    }

    /**
     * Return the corridor id.
     *
     * @return int
     */
    public function get_id(): int {
        return $this->id;
    }

    /**
     * Return the parent challenge id.
     *
     * @return int
     */
    public function get_challengeid(): int {
        return $this->challengeid;
    }

    /**
     * Return the stable corridor key.
     *
     * @return string
     */
    public function get_shortname(): string {
        return $this->shortname;
    }

    /**
     * Return the display name.
     *
     * @return string
     */
    public function get_name(): string {
        return $this->name;
    }

    /**
     * Return the description.
     *
     * @return string
     */
    public function get_description(): string {
        return $this->description;
    }

    /**
     * Return the audience.
     *
     * @return string
     */
    public function get_audience(): string {
        return $this->audience;
    }

    /**
     * Return the entry point.
     *
     * @return string
     */
    public function get_entrypoint(): string {
        return $this->entrypoint;
    }

    /**
     * Return the action mode.
     *
     * @return string
     */
    public function get_actionmode(): string {
        return $this->actionmode;
    }

    /**
     * Return the evidence requirement.
     *
     * @return string
     */
    public function get_evidencerequirement(): string {
        return $this->evidencerequirement;
    }

    /**
     * Return the ethical limits.
     *
     * @return string
     */
    public function get_ethicallimits(): string {
        return $this->ethicallimits;
    }

    /**
     * Return the risk level.
     *
     * @return string
     */
    public function get_risklevel(): string {
        return $this->risklevel;
    }

    /**
     * Return the visibility.
     *
     * @return string
     */
    public function get_visibility(): string {
        return $this->visibility;
    }

    /**
     * Return the status.
     *
     * @return string
     */
    public function get_status(): string {
        return $this->status;
    }

    /**
     * Whether this corridor is required.
     *
     * @return bool
     */
    public function is_required(): bool {
        return $this->required;
    }

    /**
     * Whether this corridor is active.
     *
     * @return bool
     */
    public function is_active(): bool {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Whether this corridor is restricted to integrity workflows.
     *
     * @return bool
     */
    public function is_integrity_restricted(): bool {
        return $this->visibility === 'restricted_integrity'
            || $this->risklevel === self::RISK_RESTRICTED_INTEGRITY;
    }

    /**
     * Return sort order.
     *
     * @return int
     */
    public function get_sortorder(): int {
        return $this->sortorder;
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
     * Return a copy with a database id.
     *
     * @param int $id Database id.
     * @return self
     */
    public function with_id(int $id): self {
        return new self(
            $id,
            $this->challengeid,
            $this->shortname,
            $this->name,
            $this->description,
            $this->audience,
            $this->entrypoint,
            $this->actionmode,
            $this->evidencerequirement,
            $this->ethicallimits,
            $this->risklevel,
            $this->visibility,
            $this->status,
            $this->required,
            $this->sortorder,
            $this->timecreated,
            $this->timemodified,
            $this->metadata
        );
    }

    /**
     * Return a copy with a different status.
     *
     * @param string $status New status.
     * @return self
     */
    public function with_status(string $status): self {
        return new self(
            $this->id,
            $this->challengeid,
            $this->shortname,
            $this->name,
            $this->description,
            $this->audience,
            $this->entrypoint,
            $this->actionmode,
            $this->evidencerequirement,
            $this->ethicallimits,
            $this->risklevel,
            $this->visibility,
            $status,
            $this->required,
            $this->sortorder,
            $this->timecreated,
            time(),
            $this->metadata
        );
    }

    /**
     * Return a copy with a different risk level.
     *
     * @param string $risklevel New risk level.
     * @return self
     */
    public function with_risklevel(string $risklevel): self {
        return new self(
            $this->id,
            $this->challengeid,
            $this->shortname,
            $this->name,
            $this->description,
            $this->audience,
            $this->entrypoint,
            $this->actionmode,
            $this->evidencerequirement,
            $this->ethicallimits,
            $risklevel,
            $this->visibility,
            $this->status,
            $this->required,
            $this->sortorder,
            $this->timecreated,
            time(),
            $this->metadata
        );
    }

    /**
     * Return a copy with different visibility.
     *
     * @param string $visibility New visibility.
     * @return self
     */
    public function with_visibility(string $visibility): self {
        return new self(
            $this->id,
            $this->challengeid,
            $this->shortname,
            $this->name,
            $this->description,
            $this->audience,
            $this->entrypoint,
            $this->actionmode,
            $this->evidencerequirement,
            $this->ethicallimits,
            $this->risklevel,
            $visibility,
            $this->status,
            $this->required,
            $this->sortorder,
            $this->timecreated,
            time(),
            $this->metadata
        );
    }

    /**
     * Convert to a database record for {uckkchallenge_corr}.
     *
     * @return stdClass
     * @throws JsonException
     */
    public function to_record(): stdClass {
        $record = new stdClass();

        if ($this->id > 0) {
            $record->id = $this->id;
        }

        $record->challengeid = $this->challengeid;
        $record->shortname = $this->shortname;
        $record->name = $this->name;
        $record->description = $this->description;
        $record->audience = $this->audience;
        $record->entrypoint = $this->entrypoint;
        $record->actionmode = $this->actionmode;
        $record->evidencerequirement = $this->evidencerequirement;
        $record->ethicallimits = $this->ethicallimits;
        $record->risklevel = $this->risklevel;
        $record->visibility = $this->visibility;
        $record->status = $this->status;
        $record->required = $this->required ? 1 : 0;
        $record->sortorder = $this->sortorder;
        $record->timecreated = $this->timecreated;
        $record->timemodified = $this->timemodified;
        $record->metadata = json_encode($this->metadata, JSON_THROW_ON_ERROR);

        return $record;
    }

    /**
     * Export to template/API-safe data.
     *
     * @return stdClass
     */
    public function export(): stdClass {
        return (object)[
            'id' => $this->id,
            'challengeid' => $this->challengeid,
            'shortname' => $this->shortname,
            'name' => $this->name,
            'description' => $this->description,
            'audience' => $this->audience,
            'entrypoint' => $this->entrypoint,
            'actionmode' => $this->actionmode,
            'evidencerequirement' => $this->evidencerequirement,
            'ethicallimits' => $this->ethicallimits,
            'risklevel' => $this->risklevel,
            'visibility' => $this->visibility,
            'status' => $this->status,
            'required' => $this->required,
            'sortorder' => $this->sortorder,
            'timecreated' => $this->timecreated,
            'timemodified' => $this->timemodified,
            'metadata' => $this->metadata,
            'active' => $this->is_active(),
            'integrityrestricted' => $this->is_integrity_restricted(),
        ];
    }

    /**
     * Validate corridor invariants.
     */
    private function validate(): void {
        if ($this->challengeid <= 0) {
            throw new coding_exception('A corridor requires a valid challenge id.');
        }

        if ($this->shortname === '') {
            throw new coding_exception('A corridor requires a shortname.');
        }

        if ($this->name === '') {
            throw new coding_exception('A corridor requires a name.');
        }

        if ($this->actionmode === '') {
            throw new coding_exception('A corridor requires an action mode.');
        }

        if (!in_array($this->status, self::ALLOWED_STATUSES, true)) {
            throw new coding_exception('Invalid corridor status.');
        }

        if (!in_array($this->visibility, self::ALLOWED_VISIBILITIES, true)) {
            throw new coding_exception('Invalid corridor visibility.');
        }

        if (!in_array($this->risklevel, self::ALLOWED_RISK_LEVELS, true)) {
            throw new coding_exception('Invalid corridor risk level.');
        }
    }

    /**
     * Normalise a shortname.
     *
     * @param string $value Raw value.
     * @return string
     */
    private static function normalise_shortname(string $value): string {
        $value = core_text::strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9_-]+/', '_', $value) ?? '';
        $value = preg_replace('/_+/', '_', $value) ?? '';
        return trim($value, '_-');
    }

    /**
     * Normalise display text.
     *
     * @param string $value Raw value.
     * @return string
     */
    private static function normalise_text(string $value): string {
        return trim(preg_replace('/\s+/', ' ', $value) ?? '');
    }

    /**
     * Normalise an allowed choice.
     *
     * @param string $value Raw value.
     * @param array<int, string> $allowed Allowed values.
     * @param string $default Default value.
     * @return string
     */
    private static function normalise_choice(string $value, array $allowed, string $default): string {
        $value = self::normalise_shortname($value);

        return in_array($value, $allowed, true) ? $value : $default;
    }

    /**
     * Convert mixed value to boolean.
     *
     * @param mixed $value Raw value.
     * @return bool
     */
    private static function to_bool(mixed $value): bool {
        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value)) {
            return $value === 1;
        }

        if (is_string($value)) {
            return in_array(core_text::strtolower(trim($value)), ['1', 'true', 'yes', 'on'], true);
        }

        return false;
    }

    /**
     * Convert metadata from mixed input.
     *
     * @param mixed $value Raw metadata.
     * @return array<string, mixed>
     */
    private static function metadata_from_mixed(mixed $value): array {
        if (is_array($value)) {
            return self::normalise_metadata($value);
        }

        if ($value instanceof stdClass) {
            return self::normalise_metadata((array)$value);
        }

        if (is_string($value) && trim($value) !== '') {
            try {
                $decoded = json_decode($value, true, 512, JSON_THROW_ON_ERROR);
                return is_array($decoded) ? self::normalise_metadata($decoded) : [];
            } catch (JsonException) {
                return [];
            }
        }

        return [];
    }

    /**
     * Normalise metadata to JSON-safe array values.
     *
     * @param array<string|int, mixed> $metadata Raw metadata.
     * @return array<string, mixed>
     */
    private static function normalise_metadata(array $metadata): array {
        $normalised = [];

        foreach ($metadata as $key => $value) {
            $key = clean_param((string)$key, PARAM_ALPHANUMEXT);

            if ($key === '') {
                continue;
            }

            if (is_scalar($value) || $value === null) {
                $normalised[$key] = $value;
                continue;
            }

            if (is_array($value)) {
                $normalised[$key] = self::normalise_nested_metadata($value);
            }
        }

        return $normalised;
    }

    /**
     * Normalise nested metadata.
     *
     * @param array<string|int, mixed> $metadata Nested metadata.
     * @return array<string|int, mixed>
     */
    private static function normalise_nested_metadata(array $metadata): array {
        $normalised = [];

        foreach ($metadata as $key => $value) {
            if (is_scalar($value) || $value === null) {
                $normalised[$key] = $value;
                continue;
            }

            if (is_array($value)) {
                $normalised[$key] = self::normalise_nested_metadata($value);
            }
        }

        return $normalised;
    }
}