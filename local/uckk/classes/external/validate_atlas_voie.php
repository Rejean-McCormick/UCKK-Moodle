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

declare(strict_types=1);

/**
 * External function validating a UCKK Atlas Voie JSON document.
 *
 * This service validates an Atlas Voie JSON object or an existing canonical
 * Atlas Voie loaded by voie_id.
 *
 * It does not:
 * - accept filesystem paths;
 * - mutate Moodle;
 * - create categories, courses, badges or custom fields;
 * - expose private learner data;
 * - log the full JSON payload.
 *
 * @package    local_uckk
 * @copyright  2026 Univers-Cité King Klown
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_uckk\external;

use context_system;
use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;
use invalid_parameter_exception;
use local_uckk\event\atlas_voie_validated;
use local_uckk\local\atlas\voie_repository;
use local_uckk\local\atlas\voie_validator;

defined('MOODLE_INTERNAL') || die();

/**
 * Validate a UCKK Atlas Voie JSON document.
 *
 * Exposed service name:
 *
 * local_uckk_validate_atlas_voie
 */
final class validate_atlas_voie extends external_api {
    /**
     * Capability required to validate Atlas JSON.
     */
    private const CAPABILITY = 'local/uckk:validateatlasjson';

    /**
     * Maximum accepted JSON payload size.
     *
     * Canonical repository files are loaded by voie_id instead.
     */
    private const MAX_JSON_BYTES = 1048576;

    /**
     * Define external parameters.
     *
     * Either:
     * - pass voieid to validate a canonical Atlas Voie from atlas/voies; or
     * - pass voiejson to validate a submitted JSON payload.
     *
     * If voiejson is provided, it is validated instead of loading by voieid.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'voieid' => new external_value(
                PARAM_ALPHANUMEXT,
                'Canonical Atlas voie_id. Used only to load an existing Voie when voiejson is empty.',
                VALUE_DEFAULT,
                ''
            ),
            'voiejson' => new external_value(
                PARAM_RAW,
                'Optional raw Atlas Voie JSON payload to validate.',
                VALUE_DEFAULT,
                ''
            ),
        ]);
    }

    /**
     * Validate an Atlas Voie.
     *
     * @param string $voieid Canonical Atlas voie_id.
     * @param string $voiejson Optional raw JSON payload.
     * @return array<string, mixed>
     */
    public static function execute(string $voieid = '', string $voiejson = ''): array {
        $params = self::validate_parameters(self::execute_parameters(), [
            'voieid' => $voieid,
            'voiejson' => $voiejson,
        ]);

        $context = context_system::instance();
        self::validate_context($context);
        require_capability(self::CAPABILITY, $context);

        $voieid = self::clean_voie_id((string)$params['voieid']);
        $voiejson = trim((string)$params['voiejson']);

        if ($voieid === '' && $voiejson === '') {
            throw new invalid_parameter_exception('Either voieid or voiejson is required.');
        }

        $source = 'repository';

        if ($voiejson !== '') {
            $voie = self::decode_voie_json($voiejson);
            $source = 'payload';
        } else {
            $voie = self::load_voie_by_id($voieid);
        }

        $voie = self::normalise_voie_array($voie);

        $schema = self::get_string_value($voie, 'schema_version');
        $resolvedvoieid = self::get_string_value($voie, 'voie_id', $voieid);
        $code = self::get_string_value($voie, 'code');
        $nom = self::get_string_value($voie, 'nom');
        $statut = self::get_string_value($voie, 'statut');
        $coursecount = self::count_array_items($voie, 'cours_conceptuels');
        $checksum = self::hash_voie($voie);

        $serviceerrors = [];

        if ($voieid !== '' && $resolvedvoieid !== '' && $voieid !== $resolvedvoieid) {
            $serviceerrors[] = self::make_issue(
                'voie_id',
                'voie_id_mismatch',
                'The requested voieid does not match the JSON voie_id.'
            );
        }

        $validation = self::run_validator($voie);

        $errors = array_merge($serviceerrors, $validation['errors']);
        $warnings = $validation['warnings'];
        $valid = count($errors) === 0;

        self::trigger_validation_event(
            $context,
            $valid,
            $source,
            $resolvedvoieid,
            $code,
            $statut,
            $coursecount,
            count($errors),
            count($warnings),
            $checksum
        );

        return [
            'valid' => $valid,
            'source' => $source,
            'schema_version' => $schema,
            'voie_id' => $resolvedvoieid,
            'code' => $code,
            'nom' => $nom,
            'statut' => $statut,
            'course_count' => $coursecount,
            'error_count' => count($errors),
            'warning_count' => count($warnings),
            'checksum' => $checksum,
            'errors' => $errors,
            'warnings' => $warnings,
        ];
    }

    /**
     * Define external return structure.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'valid' => new external_value(PARAM_BOOL, 'Whether the Atlas Voie JSON is valid.'),
            'source' => new external_value(PARAM_ALPHANUMEXT, 'Validation source: repository or payload.'),
            'schema_version' => new external_value(PARAM_TEXT, 'Atlas schema version.'),
            'voie_id' => new external_value(PARAM_ALPHANUMEXT, 'Canonical Atlas voie_id.'),
            'code' => new external_value(PARAM_ALPHANUMEXT, 'Canonical Atlas Voie code.'),
            'nom' => new external_value(PARAM_TEXT, 'Public Atlas Voie name.'),
            'statut' => new external_value(PARAM_TEXT, 'Atlas Voie status.'),
            'course_count' => new external_value(PARAM_INT, 'Number of conceptual courses in the Voie.'),
            'error_count' => new external_value(PARAM_INT, 'Number of validation errors.'),
            'warning_count' => new external_value(PARAM_INT, 'Number of validation warnings.'),
            'checksum' => new external_value(PARAM_ALPHANUM, 'SHA-256 checksum of the validated Atlas payload.'),
            'errors' => new external_multiple_structure(
                self::issue_return_structure(),
                'Validation errors.'
            ),
            'warnings' => new external_multiple_structure(
                self::issue_return_structure(),
                'Validation warnings.'
            ),
        ]);
    }

    /**
     * Shared return structure for validation issues.
     *
     * @return external_single_structure
     */
    private static function issue_return_structure(): external_single_structure {
        return new external_single_structure([
            'path' => new external_value(PARAM_TEXT, 'JSON path or field name.'),
            'code' => new external_value(PARAM_ALPHANUMEXT, 'Machine-readable issue code.'),
            'message' => new external_value(PARAM_TEXT, 'Human-readable issue message.'),
        ]);
    }

    /**
     * Clean and validate a canonical voie_id.
     *
     * voie_id is an identifier, not a file path.
     *
     * @param string $voieid Raw voie_id.
     * @return string
     */
    private static function clean_voie_id(string $voieid): string {
        $voieid = strtolower(trim($voieid));

        if ($voieid === '') {
            return '';
        }

        if (!preg_match('/^voie_[a-z0-9]+(?:_[a-z0-9]+)*$/', $voieid)) {
            throw new invalid_parameter_exception('Invalid Atlas voie_id.');
        }

        return $voieid;
    }

    /**
     * Decode a raw Atlas Voie JSON payload.
     *
     * @param string $voiejson Raw JSON payload.
     * @return array<string, mixed>
     */
    private static function decode_voie_json(string $voiejson): array {
        if (strlen($voiejson) > self::MAX_JSON_BYTES) {
            throw new invalid_parameter_exception('Atlas Voie JSON payload is too large.');
        }

        $decoded = json_decode($voiejson, true, 128);

        if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
            throw new invalid_parameter_exception('Invalid Atlas Voie JSON: ' . json_last_error_msg());
        }

        return $decoded;
    }

    /**
     * Load a canonical Atlas Voie by voie_id.
     *
     * @param string $voieid Canonical voie_id.
     * @return array<string, mixed>
     */
    private static function load_voie_by_id(string $voieid): array {
        if ($voieid === '') {
            throw new invalid_parameter_exception('voieid is required when voiejson is empty.');
        }

        if (!class_exists(voie_repository::class)) {
            throw new \coding_exception('The voie_repository class is required.');
        }

        $repository = new voie_repository();

        foreach (['get_by_voie_id', 'get_by_id', 'get', 'load_by_voie_id', 'load'] as $method) {
            if (method_exists($repository, $method)) {
                $voie = $repository->{$method}($voieid);

                if (is_array($voie)) {
                    return $voie;
                }

                if ($voie instanceof \stdClass) {
                    return self::stdclass_to_array($voie);
                }

                throw new \coding_exception('voie_repository::' . $method . '() must return an array or stdClass.');
            }
        }

        foreach (['get_by_voie_id', 'get_by_id', 'get', 'load_by_voie_id', 'load'] as $method) {
            if (is_callable([voie_repository::class, $method])) {
                $voie = call_user_func([voie_repository::class, $method], $voieid);

                if (is_array($voie)) {
                    return $voie;
                }

                if ($voie instanceof \stdClass) {
                    return self::stdclass_to_array($voie);
                }

                throw new \coding_exception('voie_repository::' . $method . '() must return an array or stdClass.');
            }
        }

        throw new \coding_exception('The voie_repository class must expose get_by_voie_id(string $voieid).');
    }

    /**
     * Normalize Atlas Voie array shape.
     *
     * @param array<string, mixed> $voie Atlas Voie data.
     * @return array<string, mixed>
     */
    private static function normalise_voie_array(array $voie): array {
        return $voie;
    }

    /**
     * Convert stdClass recursively to array.
     *
     * @param \stdClass $value Object to convert.
     * @return array<string, mixed>
     */
    private static function stdclass_to_array(\stdClass $value): array {
        $encoded = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if ($encoded === false) {
            return [];
        }

        $decoded = json_decode($encoded, true);

        if (!is_array($decoded)) {
            return [];
        }

        return $decoded;
    }

    /**
     * Run the canonical Atlas Voie validator.
     *
     * The canonical validator method is expected to be validate(array $voie).
     * A small compatibility adapter is kept here so parallel implementations can
     * converge without changing the external service contract.
     *
     * @param array<string, mixed> $voie Atlas Voie data.
     * @return array{errors: array<int, array<string, string>>, warnings: array<int, array<string, string>>}
     */
    private static function run_validator(array $voie): array {
        if (!class_exists(voie_validator::class)) {
            return [
                'errors' => [
                    self::make_issue(
                        '',
                        'validator_unavailable',
                        'The voie_validator class is not available.'
                    ),
                ],
                'warnings' => [],
            ];
        }

        try {
            $validator = new voie_validator();

            foreach (['validate', 'validate_voie', 'validate_array'] as $method) {
                if (method_exists($validator, $method)) {
                    return self::normalise_validation_result($validator->{$method}($voie));
                }
            }

            foreach (['validate', 'validate_voie', 'validate_array'] as $method) {
                if (is_callable([voie_validator::class, $method])) {
                    return self::normalise_validation_result(call_user_func([voie_validator::class, $method], $voie));
                }
            }
        } catch (\Throwable $exception) {
            return [
                'errors' => [
                    self::make_issue(
                        '',
                        'validator_exception',
                        $exception->getMessage()
                    ),
                ],
                'warnings' => [],
            ];
        }

        return [
            'errors' => [
                self::make_issue(
                    '',
                    'validator_method_missing',
                    'The voie_validator class must expose validate(array $voie).'
                ),
            ],
            'warnings' => [],
        ];
    }

    /**
     * Normalize a validator result into service errors and warnings.
     *
     * Supported result shapes:
     * - null or true: valid;
     * - false: invalid with generic error;
     * - ['valid' => bool, 'errors' => [], 'warnings' => []];
     * - list of errors;
     * - object with is_valid(), get_errors(), get_warnings();
     * - object with to_array().
     *
     * @param mixed $result Validator result.
     * @return array{errors: array<int, array<string, string>>, warnings: array<int, array<string, string>>}
     */
    private static function normalise_validation_result($result): array {
        if ($result === null || $result === true) {
            return [
                'errors' => [],
                'warnings' => [],
            ];
        }

        if ($result === false) {
            return [
                'errors' => [
                    self::make_issue('', 'validation_failed', 'The Atlas Voie JSON is invalid.'),
                ],
                'warnings' => [],
            ];
        }

        if (is_object($result)) {
            if (method_exists($result, 'to_array')) {
                return self::normalise_validation_result($result->to_array());
            }

            $errors = [];
            $warnings = [];

            if (method_exists($result, 'get_errors')) {
                $errors = self::normalise_issue_list($result->get_errors());
            }

            if (method_exists($result, 'get_warnings')) {
                $warnings = self::normalise_issue_list($result->get_warnings());
            }

            if (method_exists($result, 'is_valid') && !$result->is_valid() && empty($errors)) {
                $errors[] = self::make_issue('', 'validation_failed', 'The Atlas Voie JSON is invalid.');
            }

            return [
                'errors' => $errors,
                'warnings' => $warnings,
            ];
        }

        if (is_array($result)) {
            $errors = [];
            $warnings = [];

            if (array_key_exists('errors', $result)) {
                $errors = self::normalise_issue_list($result['errors']);
            } else if (array_key_exists('valid', $result) && !$result['valid']) {
                $errors[] = self::make_issue('', 'validation_failed', 'The Atlas Voie JSON is invalid.');
            } else if (self::is_list_array($result)) {
                $errors = self::normalise_issue_list($result);
            }

            if (array_key_exists('warnings', $result)) {
                $warnings = self::normalise_issue_list($result['warnings']);
            }

            return [
                'errors' => $errors,
                'warnings' => $warnings,
            ];
        }

        return [
            'errors' => [
                self::make_issue('', 'validator_result_invalid', 'The validator returned an unsupported result.'),
            ],
            'warnings' => [],
        ];
    }

    /**
     * Normalize a list of issues.
     *
     * @param mixed $issues Raw issues.
     * @return array<int, array<string, string>>
     */
    private static function normalise_issue_list($issues): array {
        if ($issues === null || $issues === false || $issues === '') {
            return [];
        }

        if (!is_array($issues)) {
            return [
                self::make_issue('', 'validation_issue', (string)$issues),
            ];
        }

        $normalised = [];

        foreach ($issues as $key => $issue) {
            $normalised[] = self::normalise_issue($issue, $key);
        }

        return $normalised;
    }

    /**
     * Normalize a single validation issue.
     *
     * @param mixed $issue Raw issue.
     * @param mixed $key Optional array key.
     * @return array<string, string>
     */
    private static function normalise_issue($issue, $key = null): array {
        $fallbackpath = is_string($key) ? $key : '';

        if (is_string($issue)) {
            return self::make_issue($fallbackpath, 'validation_issue', $issue);
        }

        if (is_object($issue)) {
            if (method_exists($issue, 'to_array')) {
                return self::normalise_issue($issue->to_array(), $key);
            }

            return self::make_issue($fallbackpath, 'validation_issue', (string)$issue);
        }

        if (is_array($issue)) {
            $path = isset($issue['path'])
                ? (string)$issue['path']
                : (isset($issue['field']) ? (string)$issue['field'] : $fallbackpath);

            $code = isset($issue['code'])
                ? (string)$issue['code']
                : (isset($issue['type']) ? (string)$issue['type'] : 'validation_issue');

            if (isset($issue['message'])) {
                $message = (string)$issue['message'];
            } else if (isset($issue['error'])) {
                $message = (string)$issue['error'];
            } else if (isset($issue['description'])) {
                $message = (string)$issue['description'];
            } else {
                $message = 'Validation issue.';
            }

            return self::make_issue($path, $code, $message);
        }

        return self::make_issue($fallbackpath, 'validation_issue', (string)$issue);
    }

    /**
     * Create a sanitized validation issue.
     *
     * @param string $path JSON path or field.
     * @param string $code Machine-readable code.
     * @param string $message Human-readable message.
     * @return array<string, string>
     */
    private static function make_issue(string $path, string $code, string $message): array {
        $code = strtolower(trim($code));
        $code = preg_replace('/[^a-z0-9_-]+/', '_', $code);
        $code = trim((string)$code, '_');

        if ($code === '') {
            $code = 'validation_issue';
        }

        return [
            'path' => clean_param($path, PARAM_TEXT),
            'code' => clean_param($code, PARAM_ALPHANUMEXT),
            'message' => clean_param($message, PARAM_TEXT),
        ];
    }

    /**
     * Get a string value from the Atlas Voie.
     *
     * @param array<string, mixed> $voie Atlas Voie data.
     * @param string $key Field key.
     * @param string $fallback Fallback value.
     * @return string
     */
    private static function get_string_value(array $voie, string $key, string $fallback = ''): string {
        if (!array_key_exists($key, $voie)) {
            return $fallback;
        }

        $value = $voie[$key];

        if (is_scalar($value)) {
            return trim((string)$value);
        }

        return $fallback;
    }

    /**
     * Count array items at a top-level key.
     *
     * @param array<string, mixed> $voie Atlas Voie data.
     * @param string $key Field key.
     * @return int
     */
    private static function count_array_items(array $voie, string $key): int {
        if (!isset($voie[$key]) || !is_array($voie[$key])) {
            return 0;
        }

        return count($voie[$key]);
    }

    /**
     * Compute a stable SHA-256 hash for the validated Atlas Voie.
     *
     * @param array<string, mixed> $voie Atlas Voie data.
     * @return string
     */
    private static function hash_voie(array $voie): string {
        $copy = $voie;
        self::recursive_ksort($copy);

        $encoded = json_encode($copy, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if ($encoded === false) {
            $encoded = '';
        }

        return hash('sha256', $encoded);
    }

    /**
     * Sort associative arrays recursively for stable hashing.
     *
     * @param mixed $value Value to sort.
     * @return void
     */
    private static function recursive_ksort(&$value): void {
        if (!is_array($value)) {
            return;
        }

        foreach ($value as &$child) {
            self::recursive_ksort($child);
        }
        unset($child);

        if (!self::is_list_array($value)) {
            ksort($value);
        }
    }

    /**
     * Test whether an array is list-like.
     *
     * This replaces array_is_list() for compatibility with older supported
     * Moodle PHP baselines.
     *
     * @param array<mixed> $array Array to inspect.
     * @return bool
     */
    private static function is_list_array(array $array): bool {
        $expected = 0;

        foreach (array_keys($array) as $key) {
            if ($key !== $expected) {
                return false;
            }

            $expected++;
        }

        return true;
    }

    /**
     * Trigger the atlas_voie_validated event when available.
     *
     * The event intentionally receives only ids, status, counts and hash.
     * It must not receive or log the full JSON payload.
     *
     * @param context_system $context System context.
     * @param bool $valid Validation result.
     * @param string $source Validation source.
     * @param string $voieid Atlas voie_id.
     * @param string $code Atlas Voie code.
     * @param string $statut Atlas Voie status.
     * @param int $coursecount Number of conceptual courses.
     * @param int $errorcount Error count.
     * @param int $warningcount Warning count.
     * @param string $checksum Payload checksum.
     * @return void
     */
    private static function trigger_validation_event(
        context_system $context,
        bool $valid,
        string $source,
        string $voieid,
        string $code,
        string $statut,
        int $coursecount,
        int $errorcount,
        int $warningcount,
        string $checksum
    ): void {
        if (!class_exists(atlas_voie_validated::class)) {
            return;
        }

        try {
            $event = atlas_voie_validated::create([
                'context' => $context,
                'other' => [
                    'valid' => $valid ? 1 : 0,
                    'source' => $source,
                    'voieid' => $voieid,
                    'code' => $code,
                    'statut' => $statut,
                    'coursecount' => $coursecount,
                    'errorcount' => $errorcount,
                    'warningcount' => $warningcount,
                    'checksum' => $checksum,
                ],
            ]);

            $event->trigger();
        } catch (\Throwable $exception) {
            debugging(
                'Unable to trigger atlas_voie_validated event: ' . $exception->getMessage(),
                DEBUG_DEVELOPER
            );
        }
    }
}