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
 * Provenance service and value helper for local_uckk.
 *
 * Provenance records answer the institutional questions:
 *
 * - Where did this item come from?
 * - Who created or modified it?
 * - What action produced it?
 * - Which component owns it?
 * - Which source or prior object supports it?
 * - Is it verified, contested, invalidated or archived?
 * - Can it be exported, audited and connected to memory?
 *
 * This class records traceability. It does not decide truth, validate archive
 * items, close integrity cases, publish canon changes, assign grades, award
 * badges or replace Assemblées/Inquisiteur workflows.
 *
 * @package    local_uckk
 * @copyright  2026 Univers-Cité King Klown
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_uckk\local;

use context;
use context_system;
use invalid_parameter_exception;
use moodle_exception;
use stdClass;
use xmldb_table;

defined('MOODLE_INTERNAL') || die();

/**
 * UCKK provenance helper.
 *
 * @package local_uckk
 */
final class provenance {
    /** Provenance table. */
    public const TABLE = 'local_uckk_prov';

    /** Component owning the provenance record. */
    public const COMPONENT = 'local_uckk';

    /** Source type: human. */
    public const SOURCE_HUMAN = 'human';

    /** Source type: AI-assisted. */
    public const SOURCE_AI_ASSISTED = 'ai_assisted';

    /** Source type: imported. */
    public const SOURCE_IMPORTED = 'imported';

    /** Source type: system. */
    public const SOURCE_SYSTEM = 'system';

    /** Source type: archive. */
    public const SOURCE_ARCHIVE = 'archive';

    /** Source type: assembly. */
    public const SOURCE_ASSEMBLY = 'assembly';

    /** Source type: challenge. */
    public const SOURCE_CHALLENGE = 'challenge';

    /** Source type: integrity. */
    public const SOURCE_INTEGRITY = 'integrity';

    /** State: recorded. */
    public const STATE_RECORDED = 'recorded';

    /** State: draft. */
    public const STATE_DRAFT = 'draft';

    /** State: verified. */
    public const STATE_VERIFIED = 'verified';

    /** State: contested. */
    public const STATE_CONTESTED = 'contested';

    /** State: invalidated. */
    public const STATE_INVALIDATED = 'invalidated';

    /** State: archived. */
    public const STATE_ARCHIVED = 'archived';

    /** Action: created. */
    public const ACTION_CREATED = 'created';

    /** Action: updated. */
    public const ACTION_UPDATED = 'updated';

    /** Action: deleted. */
    public const ACTION_DELETED = 'deleted';

    /** Action: imported. */
    public const ACTION_IMPORTED = 'imported';

    /** Action: exported. */
    public const ACTION_EXPORTED = 'exported';

    /** Action: verified. */
    public const ACTION_VERIFIED = 'verified';

    /** Action: contested. */
    public const ACTION_CONTESTED = 'contested';

    /** Action: invalidated. */
    public const ACTION_INVALIDATED = 'invalidated';

    /** Action: archived. */
    public const ACTION_ARCHIVED = 'archived';

    /** Maximum source text length. */
    private const MAX_SOURCE_TEXT_LENGTH = 1000;

    /** Maximum hash length. */
    private const MAX_HASH_LENGTH = 128;

    /**
     * Create a provenance record.
     *
     * Required options:
     * - component: owning component, e.g. local_uckk, mod_uckkchallenge
     * - itemtype: object type, e.g. canon_item, pathway, player_profile
     * - itemid: object id
     * - action: action name
     *
     * Optional options:
     * - contextid
     * - userid
     * - source
     * - sourcecomponent
     * - sourceitemid
     * - sourcetext
     * - state
     * - metadata
     * - hash
     * - createdby
     *
     * @param array<string, mixed> $options Provenance data.
     * @return stdClass Created provenance record.
     */
    public static function create(array $options): stdClass {
        global $DB;

        self::ensure_table_exists();

        $record = self::build_record($options);
        $record->id = $DB->insert_record(self::TABLE, $record);

        return self::get((int)$record->id);
    }

    /**
     * Create a provenance record for an item.
     *
     * @param string $component Owning component.
     * @param string $itemtype Item type.
     * @param int $itemid Item id.
     * @param string $action Action.
     * @param array<string, mixed> $options Additional provenance options.
     * @return stdClass Created provenance record.
     */
    public static function create_for_item(
        string $component,
        string $itemtype,
        int $itemid,
        string $action,
        array $options = []
    ): stdClass {
        $options['component'] = $component;
        $options['itemtype'] = $itemtype;
        $options['itemid'] = $itemid;
        $options['action'] = $action;

        return self::create($options);
    }

    /**
     * Create a provenance record for a canon item.
     *
     * @param int $canonitemid Canon item id.
     * @param string $action Action.
     * @param array<string, mixed> $options Additional options.
     * @return stdClass Created provenance record.
     */
    public static function create_for_canon_item(int $canonitemid, string $action, array $options = []): stdClass {
        return self::create_for_item('local_uckk', 'canon_item', $canonitemid, $action, $options);
    }

    /**
     * Create a provenance record for a player profile.
     *
     * @param int $profileid Profile id.
     * @param int $userid Moodle user id.
     * @param string $action Action.
     * @param array<string, mixed> $options Additional options.
     * @return stdClass Created provenance record.
     */
    public static function create_for_profile(
        int $profileid,
        int $userid,
        string $action,
        array $options = []
    ): stdClass {
        $options['userid'] = $userid;

        return self::create_for_item('local_uckk', 'player_profile', $profileid, $action, $options);
    }

    /**
     * Get a provenance record.
     *
     * @param int $id Provenance id.
     * @return stdClass
     */
    public static function get(int $id): stdClass {
        global $DB;

        if ($id <= 0) {
            throw new invalid_parameter_exception('Invalid provenance id.');
        }

        $record = $DB->get_record(self::TABLE, ['id' => $id], '*', MUST_EXIST);

        return self::normalise_record($record);
    }

    /**
     * Get provenance records for a specific item.
     *
     * @param string $component Owning component.
     * @param string $itemtype Item type.
     * @param int $itemid Item id.
     * @param int $limit Maximum records.
     * @param int $offset Offset.
     * @return stdClass[]
     */
    public static function get_for_item(
        string $component,
        string $itemtype,
        int $itemid,
        int $limit = 100,
        int $offset = 0
    ): array {
        global $DB;

        self::ensure_table_exists();

        $component = self::clean_component($component);
        $itemtype = self::clean_key($itemtype, 'itemtype');
        $itemid = self::clean_positive_int($itemid, 'itemid');
        $limit = max(1, min(500, $limit));
        $offset = max(0, $offset);

        $records = $DB->get_records(
            self::TABLE,
            [
                'component' => $component,
                'itemtype' => $itemtype,
                'itemid' => $itemid,
            ],
            'timecreated DESC, id DESC',
            '*',
            $offset,
            $limit
        );

        return array_values(array_map([self::class, 'normalise_record'], $records));
    }

    /**
     * Get latest provenance record for an item.
     *
     * @param string $component Owning component.
     * @param string $itemtype Item type.
     * @param int $itemid Item id.
     * @return stdClass|null
     */
    public static function get_latest_for_item(string $component, string $itemtype, int $itemid): ?stdClass {
        $records = self::get_for_item($component, $itemtype, $itemid, 1);

        return $records[0] ?? null;
    }

    /**
     * Update the state of a provenance record.
     *
     * @param int $id Provenance id.
     * @param string $state New state.
     * @param array<string, mixed> $metadata Additional metadata.
     * @return stdClass Updated record.
     */
    public static function update_state(int $id, string $state, array $metadata = []): stdClass {
        global $DB;

        $record = self::get($id);
        $existingmetadata = self::decode_json_object($record->metadata);

        $record->state = self::clean_state($state);
        $record->metadata = self::encode_json_object(array_merge($existingmetadata, [
            'statechange' => [
                'state' => $record->state,
                'time' => time(),
                'userid' => self::get_actor_id(),
                'metadata' => $metadata,
            ],
        ]));
        $record->modifiedby = self::get_actor_id();
        $record->timemodified = time();

        $DB->update_record(self::TABLE, self::prepare_for_storage($record));

        return self::get($id);
    }

    /**
     * Mark a provenance record as verified.
     *
     * @param int $id Provenance id.
     * @param array<string, mixed> $metadata Additional metadata.
     * @return stdClass
     */
    public static function mark_verified(int $id, array $metadata = []): stdClass {
        return self::update_state($id, self::STATE_VERIFIED, $metadata);
    }

    /**
     * Mark a provenance record as contested.
     *
     * @param int $id Provenance id.
     * @param array<string, mixed> $metadata Additional metadata.
     * @return stdClass
     */
    public static function mark_contested(int $id, array $metadata = []): stdClass {
        return self::update_state($id, self::STATE_CONTESTED, $metadata);
    }

    /**
     * Mark a provenance record as invalidated.
     *
     * @param int $id Provenance id.
     * @param array<string, mixed> $metadata Additional metadata.
     * @return stdClass
     */
    public static function mark_invalidated(int $id, array $metadata = []): stdClass {
        return self::update_state($id, self::STATE_INVALIDATED, $metadata);
    }

    /**
     * Mark a provenance record as archived.
     *
     * @param int $id Provenance id.
     * @param array<string, mixed> $metadata Additional metadata.
     * @return stdClass
     */
    public static function mark_archived(int $id, array $metadata = []): stdClass {
        return self::update_state($id, self::STATE_ARCHIVED, $metadata);
    }

    /**
     * Compute a stable hash for provenance payloads.
     *
     * This hash is not a cryptographic proof of truth. It only helps detect
     * changes in the stored provenance payload.
     *
     * @param array<string, mixed> $payload Payload.
     * @return string
     */
    public static function compute_hash(array $payload): string {
        ksort($payload);

        return hash('sha256', json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    /**
     * Export a provenance record for templates, external services or reports.
     *
     * @param int|stdClass $record Provenance id or record.
     * @return array<string, mixed>
     */
    public static function export_for_template($record): array {
        if (is_int($record)) {
            $record = self::get($record);
        } else {
            $record = self::normalise_record($record);
        }

        return [
            'id' => (int)$record->id,
            'component' => $record->component,
            'itemtype' => $record->itemtype,
            'itemid' => (int)$record->itemid,
            'contextid' => (int)$record->contextid,
            'userid' => (int)$record->userid,
            'action' => $record->action,
            'actionlabel' => self::get_action_label($record->action),
            'source' => $record->source,
            'sourcelabel' => self::get_source_label($record->source),
            'sourcecomponent' => $record->sourcecomponent,
            'sourceitemid' => (int)$record->sourceitemid,
            'sourcetext' => $record->sourcetext,
            'hassourcetext' => $record->sourcetext !== '',
            'hash' => $record->hash,
            'hashshort' => $record->hash !== '' ? substr($record->hash, 0, 12) : '',
            'hashasourcehash' => $record->hash !== '',
            'state' => $record->state,
            'statelabel' => self::get_state_label($record->state),
            'isrecorded' => $record->state === self::STATE_RECORDED,
            'isdraft' => $record->state === self::STATE_DRAFT,
            'isverified' => $record->state === self::STATE_VERIFIED,
            'iscontested' => $record->state === self::STATE_CONTESTED,
            'isinvalidated' => $record->state === self::STATE_INVALIDATED,
            'isarchived' => $record->state === self::STATE_ARCHIVED,
            'metadata' => self::decode_json_object($record->metadata),
            'createdby' => (int)$record->createdby,
            'modifiedby' => (int)$record->modifiedby,
            'timecreated' => (int)$record->timecreated,
            'timemodified' => (int)$record->timemodified,
        ];
    }

    /**
     * Export a list of provenance records for one item.
     *
     * @param string $component Owning component.
     * @param string $itemtype Item type.
     * @param int $itemid Item id.
     * @return array<int, array<string, mixed>>
     */
    public static function export_item_history(string $component, string $itemtype, int $itemid): array {
        $records = self::get_for_item($component, $itemtype, $itemid);

        return array_map([self::class, 'export_for_template'], $records);
    }

    /**
     * Determine whether the provenance table exists.
     *
     * @return bool
     */
    public static function table_exists(): bool {
        global $DB;

        $dbman = $DB->get_manager();
        $table = new xmldb_table(self::TABLE);

        return $dbman->table_exists($table);
    }

    /**
     * Return valid provenance sources.
     *
     * @return string[]
     */
    public static function get_valid_sources(): array {
        return [
            self::SOURCE_HUMAN,
            self::SOURCE_AI_ASSISTED,
            self::SOURCE_IMPORTED,
            self::SOURCE_SYSTEM,
            self::SOURCE_ARCHIVE,
            self::SOURCE_ASSEMBLY,
            self::SOURCE_CHALLENGE,
            self::SOURCE_INTEGRITY,
        ];
    }

    /**
     * Return valid provenance states.
     *
     * @return string[]
     */
    public static function get_valid_states(): array {
        return [
            self::STATE_DRAFT,
            self::STATE_RECORDED,
            self::STATE_VERIFIED,
            self::STATE_CONTESTED,
            self::STATE_INVALIDATED,
            self::STATE_ARCHIVED,
        ];
    }

    /**
     * Return valid common provenance actions.
     *
     * Other UCKK components may use additional action keys, but they must still
     * be clean PARAM_ALPHANUMEXT values.
     *
     * @return string[]
     */
    public static function get_common_actions(): array {
        return [
            self::ACTION_CREATED,
            self::ACTION_UPDATED,
            self::ACTION_DELETED,
            self::ACTION_IMPORTED,
            self::ACTION_EXPORTED,
            self::ACTION_VERIFIED,
            self::ACTION_CONTESTED,
            self::ACTION_INVALIDATED,
            self::ACTION_ARCHIVED,
        ];
    }

    /**
     * Build a storage-ready record.
     *
     * @param array<string, mixed> $options Provenance options.
     * @return stdClass
     */
    private static function build_record(array $options): stdClass {
        $now = time();
        $actorid = self::get_actor_id();

        $component = self::clean_component($options['component'] ?? self::COMPONENT);
        $itemtype = self::clean_key($options['itemtype'] ?? '', 'itemtype');
        $itemid = self::clean_positive_int($options['itemid'] ?? 0, 'itemid');
        $action = self::clean_key($options['action'] ?? self::ACTION_CREATED, 'action');
        $source = self::clean_source($options['source'] ?? self::SOURCE_HUMAN);

        $metadata = $options['metadata'] ?? [];
        if (!is_array($metadata)) {
            $metadata = [];
        }

        $hash = self::clean_hash($options['hash'] ?? '');
        if ($hash === '') {
            $hash = self::compute_hash([
                'component' => $component,
                'itemtype' => $itemtype,
                'itemid' => $itemid,
                'action' => $action,
                'source' => $source,
                'sourcecomponent' => $options['sourcecomponent'] ?? '',
                'sourceitemid' => $options['sourceitemid'] ?? 0,
                'sourcetext' => $options['sourcetext'] ?? '',
                'metadata' => $metadata,
                'timecreated' => $now,
            ]);
        }

        $record = new stdClass();
        $record->component = $component;
        $record->itemtype = $itemtype;
        $record->itemid = $itemid;
        $record->contextid = self::normalise_contextid($options['contextid'] ?? null);
        $record->userid = self::normalise_optional_int($options['userid'] ?? 0);
        $record->action = $action;
        $record->source = $source;
        $record->sourcecomponent = self::clean_optional_component($options['sourcecomponent'] ?? '');
        $record->sourceitemid = self::normalise_optional_int($options['sourceitemid'] ?? 0);
        $record->sourcetext = self::clean_source_text($options['sourcetext'] ?? '');
        $record->hash = $hash;
        $record->state = self::clean_state($options['state'] ?? self::STATE_RECORDED);
        $record->metadata = self::encode_json_object($metadata);
        $record->createdby = self::normalise_optional_int($options['createdby'] ?? $actorid);
        $record->modifiedby = self::normalise_optional_int($options['modifiedby'] ?? $actorid);
        $record->timecreated = self::normalise_optional_int($options['timecreated'] ?? $now);
        $record->timemodified = self::normalise_optional_int($options['timemodified'] ?? $now);

        return $record;
    }

    /**
     * Normalise a provenance record.
     *
     * @param stdClass $record Raw record.
     * @return stdClass
     */
    private static function normalise_record(stdClass $record): stdClass {
        $record->id = (int)($record->id ?? 0);
        $record->component = self::clean_component($record->component ?? self::COMPONENT);
        $record->itemtype = self::clean_key($record->itemtype ?? 'unknown', 'itemtype');
        $record->itemid = (int)($record->itemid ?? 0);
        $record->contextid = (int)($record->contextid ?? 0);
        $record->userid = (int)($record->userid ?? 0);
        $record->action = self::clean_key($record->action ?? self::ACTION_CREATED, 'action');
        $record->source = self::clean_source($record->source ?? self::SOURCE_HUMAN);
        $record->sourcecomponent = self::clean_optional_component($record->sourcecomponent ?? '');
        $record->sourceitemid = (int)($record->sourceitemid ?? 0);
        $record->sourcetext = self::clean_source_text($record->sourcetext ?? '');
        $record->hash = self::clean_hash($record->hash ?? '');
        $record->state = self::clean_state($record->state ?? self::STATE_RECORDED);
        $record->metadata = self::encode_json_object(self::decode_json_object($record->metadata ?? '{}'));
        $record->createdby = (int)($record->createdby ?? 0);
        $record->modifiedby = (int)($record->modifiedby ?? 0);
        $record->timecreated = (int)($record->timecreated ?? 0);
        $record->timemodified = (int)($record->timemodified ?? 0);

        return $record;
    }

    /**
     * Prepare record for database storage.
     *
     * @param stdClass $record Record.
     * @return stdClass
     */
    private static function prepare_for_storage(stdClass $record): stdClass {
        $record = self::normalise_record($record);

        $stored = new stdClass();
        $stored->id = $record->id;
        $stored->component = $record->component;
        $stored->itemtype = $record->itemtype;
        $stored->itemid = $record->itemid;
        $stored->contextid = $record->contextid;
        $stored->userid = $record->userid;
        $stored->action = $record->action;
        $stored->source = $record->source;
        $stored->sourcecomponent = $record->sourcecomponent;
        $stored->sourceitemid = $record->sourceitemid;
        $stored->sourcetext = $record->sourcetext;
        $stored->hash = $record->hash;
        $stored->state = $record->state;
        $stored->metadata = $record->metadata;
        $stored->createdby = $record->createdby;
        $stored->modifiedby = $record->modifiedby;
        $stored->timecreated = $record->timecreated;
        $stored->timemodified = $record->timemodified;

        return $stored;
    }

    /**
     * Ensure provenance table exists.
     *
     * @return void
     */
    private static function ensure_table_exists(): void {
        if (!self::table_exists()) {
            throw new moodle_exception('provenancetablenotfound', 'local_uckk');
        }
    }

    /**
     * Clean a Moodle component name.
     *
     * @param mixed $component Component.
     * @return string
     */
    private static function clean_component($component): string {
        $component = clean_param(trim((string)$component), PARAM_COMPONENT);

        if ($component === '') {
            throw new invalid_parameter_exception('Invalid provenance component.');
        }

        return $component;
    }

    /**
     * Clean an optional Moodle component name.
     *
     * @param mixed $component Component.
     * @return string
     */
    private static function clean_optional_component($component): string {
        $component = trim((string)$component);

        if ($component === '') {
            return '';
        }

        return self::clean_component($component);
    }

    /**
     * Clean a key.
     *
     * @param mixed $key Key.
     * @param string $field Field name.
     * @return string
     */
    private static function clean_key($key, string $field): string {
        $key = clean_param(\core_text::strtolower(trim((string)$key)), PARAM_ALPHANUMEXT);

        if ($key === '') {
            throw new invalid_parameter_exception('Invalid provenance ' . $field . '.');
        }

        return $key;
    }

    /**
     * Clean source value.
     *
     * @param mixed $source Source.
     * @return string
     */
    private static function clean_source($source): string {
        $source = self::clean_key($source, 'source');

        if (!in_array($source, self::get_valid_sources(), true)) {
            throw new invalid_parameter_exception('Invalid provenance source: ' . $source);
        }

        return $source;
    }

    /**
     * Clean state value.
     *
     * @param mixed $state State.
     * @return string
     */
    private static function clean_state($state): string {
        $state = self::clean_key($state, 'state');

        if (!in_array($state, self::get_valid_states(), true)) {
            throw new invalid_parameter_exception('Invalid provenance state: ' . $state);
        }

        return $state;
    }

    /**
     * Clean a positive integer.
     *
     * @param mixed $value Value.
     * @param string $field Field name.
     * @return int
     */
    private static function clean_positive_int($value, string $field): int {
        $value = (int)$value;

        if ($value <= 0) {
            throw new invalid_parameter_exception('Invalid provenance ' . $field . '.');
        }

        return $value;
    }

    /**
     * Normalise optional integer.
     *
     * @param mixed $value Value.
     * @return int
     */
    private static function normalise_optional_int($value): int {
        if ($value === null || $value === '' || $value === false) {
            return 0;
        }

        return max(0, (int)$value);
    }

    /**
     * Normalise context id.
     *
     * @param mixed $contextid Context id.
     * @return int
     */
    private static function normalise_contextid($contextid): int {
        if ($contextid instanceof context) {
            return (int)$contextid->id;
        }

        if ($contextid === null || $contextid === '' || $contextid === 0) {
            return (int)context_system::instance()->id;
        }

        return max(0, (int)$contextid);
    }

    /**
     * Clean source text.
     *
     * @param mixed $value Source text.
     * @return string
     */
    private static function clean_source_text($value): string {
        $value = clean_param(trim((string)$value), PARAM_TEXT);

        if (\core_text::strlen($value) > self::MAX_SOURCE_TEXT_LENGTH) {
            $value = \core_text::substr($value, 0, self::MAX_SOURCE_TEXT_LENGTH);
        }

        return $value;
    }

    /**
     * Clean hash.
     *
     * @param mixed $hash Hash.
     * @return string
     */
    private static function clean_hash($hash): string {
        $hash = clean_param(trim((string)$hash), PARAM_ALPHANUMEXT);

        if (\core_text::strlen($hash) > self::MAX_HASH_LENGTH) {
            $hash = \core_text::substr($hash, 0, self::MAX_HASH_LENGTH);
        }

        return $hash;
    }

    /**
     * Encode a JSON object.
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
     * Decode a JSON object.
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
     * Get actor id.
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
     * Get source display label.
     *
     * @param string $source Source key.
     * @return string
     */
    private static function get_source_label(string $source): string {
        $stringkey = 'provenance_source_' . $source;

        if (get_string_manager()->string_exists($stringkey, 'local_uckk')) {
            return get_string($stringkey, 'local_uckk');
        }

        return ucfirst(str_replace('_', ' ', $source));
    }

    /**
     * Get state display label.
     *
     * @param string $state State key.
     * @return string
     */
    private static function get_state_label(string $state): string {
        $stringkey = 'provenance_state_' . $state;

        if (get_string_manager()->string_exists($stringkey, 'local_uckk')) {
            return get_string($stringkey, 'local_uckk');
        }

        return ucfirst(str_replace('_', ' ', $state));
    }

    /**
     * Get action display label.
     *
     * @param string $action Action key.
     * @return string
     */
    private static function get_action_label(string $action): string {
        $stringkey = 'provenance_action_' . $action;

        if (get_string_manager()->string_exists($stringkey, 'local_uckk')) {
            return get_string($stringkey, 'local_uckk');
        }

        return ucfirst(str_replace('_', ' ', $action));
    }
}
