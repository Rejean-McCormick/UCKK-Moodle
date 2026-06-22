<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// any later version.

/**
 * Event fired when an UCKK Atlas Voie JSON file is validated.
 *
 * @package    local_uckk
 * @category   event
 * @copyright  2026 Univers-Cité King Klown
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace local_uckk\event;

use coding_exception;
use moodle_url;

defined('MOODLE_INTERNAL') || die();

/**
 * Event fired when an UCKK Atlas Voie JSON file is validated.
 *
 * This event is an audit record only. It must not contain the full Atlas JSON,
 * rendered content, course payloads, concept lists, private user data, grades,
 * progress data, enrolment state, validation internals, stack traces, or any
 * other private payload.
 *
 * Expected creation:
 *
 * ```php
 * $event = \local_uckk\event\atlas_voie_validated::create([
 *     'context' => \context_system::instance(),
 *     'other' => [
 *         'voie_id' => 'voie_metaphysique',
 *         'file' => 'voie_metaphysique.json',
 *         'schema_version' => 'UCKK-ATLAS-0.2-draft',
 *         'validation_result' => 'valid',
 *         'error_count' => 0,
 *         'warning_count' => 0,
 *         'atlas_hash' => 'sha256:...',
 *         'source' => 'validate_atlas_voie',
 *     ],
 * ]);
 * $event->trigger();
 * ```
 */
final class atlas_voie_validated extends \core\event\base {
    /**
     * Initialise event metadata.
     *
     * @return void
     */
    protected function init(): void {
        $this->data['crud'] = 'r';
        $this->data['edulevel'] = self::LEVEL_OTHER;
        $this->data['objecttable'] = null;
    }

    /**
     * Return localized event name.
     *
     * Required language string:
     * - event_atlas_voie_validated
     *
     * @return string
     */
    public static function get_name(): string {
        return get_string('event_atlas_voie_validated', 'local_uckk');
    }

    /**
     * Return human-readable event description.
     *
     * Keep this description limited to ids, schema versions, counts and hashes.
     *
     * @return string
     */
    public function get_description(): string {
        $userid = (int)$this->userid;
        $voieid = $this->get_other_string('voie_id');
        $file = $this->get_other_string('file');
        $schemaversion = $this->get_other_string('schema_version');
        $result = $this->get_other_string('validation_result');
        $errorcount = $this->get_other_int('error_count');
        $warningcount = $this->get_other_int('warning_count');
        $atlashash = $this->get_other_string('atlas_hash');
        $source = $this->get_other_string('source');

        $description = "The user with id '{$userid}' validated the UCKK Atlas Voie JSON "
            . "with voie id '{$voieid}', file '{$file}', schema version '{$schemaversion}', "
            . "result '{$result}', error count '{$errorcount}' and warning count '{$warningcount}'";

        if ($atlashash !== '') {
            $description .= " with atlas hash '{$atlashash}'";
        }

        if ($source !== '') {
            $description .= " from source '{$source}'";
        }

        return $description . '.';
    }

    /**
     * Return URL related to the Atlas validation page.
     *
     * The current contract exposes validation through local_uckk validation
     * entrypoints rather than per-Voie PHP files.
     *
     * @return moodle_url
     */
    public function get_url(): moodle_url {
        $voieid = $this->get_other_string('voie_id');

        if ($voieid === '') {
            return new moodle_url('/local/uckk/faculty_validate.php');
        }

        return new moodle_url('/local/uckk/faculty_validate.php', ['voie_id' => $voieid]);
    }

    /**
     * Validate event payload.
     *
     * @return void
     * @throws coding_exception
     */
    protected function validate_data(): void {
        parent::validate_data();

        $voieid = $this->require_other_string('voie_id');
        $file = $this->require_other_string('file');
        $schemaversion = $this->require_other_string('schema_version');
        $result = $this->require_other_string('validation_result');

        if (!preg_match('/^voie_[a-z0-9_]+$/', $voieid)) {
            throw new coding_exception('atlas_voie_validated requires a valid voie_id.');
        }

        if (!preg_match('/^voie_[a-z0-9_]+\.json$/', $file)) {
            throw new coding_exception('atlas_voie_validated requires a valid Atlas Voie file name.');
        }

        if (!preg_match('/^UCKK-ATLAS-[A-Za-z0-9._-]+$/', $schemaversion)) {
            throw new coding_exception('atlas_voie_validated requires a valid Atlas schema_version.');
        }

        if (!in_array($result, ['valid', 'invalid'], true)) {
            throw new coding_exception('atlas_voie_validated requires validation_result to be valid or invalid.');
        }

        $this->require_other_non_negative_int('error_count');
        $this->require_other_non_negative_int('warning_count');

        $atlashash = $this->get_other_string('atlas_hash');
        if ($atlashash !== '' && !preg_match('/^(sha256:)?[a-f0-9]{64}$/', $atlashash)) {
            throw new coding_exception('atlas_voie_validated requires a valid sha256 atlas_hash when provided.');
        }

        $source = $this->get_other_string('source');
        if ($source !== '' && !preg_match('/^[a-z0-9_:-]+$/', $source)) {
            throw new coding_exception('atlas_voie_validated requires a valid source when provided.');
        }
    }

    /**
     * Return a scalar string from the event other payload.
     *
     * @param string $key Payload key.
     * @param string $default Default value.
     * @return string
     */
    private function get_other_string(string $key, string $default = ''): string {
        $value = $this->other[$key] ?? $default;

        if (!is_scalar($value)) {
            return $default;
        }

        return trim((string)$value);
    }

    /**
     * Require a non-empty scalar string from the event other payload.
     *
     * @param string $key Payload key.
     * @return string
     * @throws coding_exception
     */
    private function require_other_string(string $key): string {
        if (!array_key_exists($key, $this->other)) {
            throw new coding_exception("atlas_voie_validated requires '{$key}' in other.");
        }

        if (!is_scalar($this->other[$key])) {
            throw new coding_exception("atlas_voie_validated requires scalar '{$key}' in other.");
        }

        $value = trim((string)$this->other[$key]);

        if ($value === '') {
            throw new coding_exception("atlas_voie_validated requires non-empty '{$key}' in other.");
        }

        return $value;
    }

    /**
     * Return an integer from the event other payload.
     *
     * @param string $key Payload key.
     * @param int $default Default value.
     * @return int
     */
    private function get_other_int(string $key, int $default = 0): int {
        if (!array_key_exists($key, $this->other)) {
            return $default;
        }

        if (!is_numeric($this->other[$key])) {
            return $default;
        }

        return (int)$this->other[$key];
    }

    /**
     * Require a non-negative integer from the event other payload.
     *
     * @param string $key Payload key.
     * @return int
     * @throws coding_exception
     */
    private function require_other_non_negative_int(string $key): int {
        if (!array_key_exists($key, $this->other)) {
            throw new coding_exception("atlas_voie_validated requires '{$key}' in other.");
        }

        if (!is_numeric($this->other[$key])) {
            throw new coding_exception("atlas_voie_validated requires numeric '{$key}' in other.");
        }

        $value = (int)$this->other[$key];

        if ($value < 0) {
            throw new coding_exception("atlas_voie_validated requires non-negative '{$key}' in other.");
        }

        return $value;
    }
}