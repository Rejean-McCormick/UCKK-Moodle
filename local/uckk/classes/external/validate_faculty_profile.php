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
 * External function validating a UCKK faculty public profile.
 *
 * This service validates a Faculty Profile JSON object or an existing
 * canonical profile loaded by slug.
 *
 * It does not:
 * - accept filesystem paths;
 * - mutate Moodle;
 * - create courses;
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
use local_uckk\event\faculty_profile_validated;
use local_uckk\local\faculty\faculty_repository;
use local_uckk\local\faculty\faculty_validator;

defined('MOODLE_INTERNAL') || die();

/**
 * Validate a UCKK faculty public profile.
 *
 * Exposed service name:
 *
 * local_uckk_validate_faculty_profile
 */
final class validate_faculty_profile extends external_api {
    /**
     * Capability required to validate public faculty profiles.
     */
    private const CAPABILITY = 'local/uckk:managefacultyprofiles';

    /**
     * Maximum accepted JSON payload size.
     *
     * This protects the external function from receiving arbitrary large
     * editor payloads. Canonical repository files are loaded by slug instead.
     */
    private const MAX_JSON_BYTES = 1048576;

    /**
     * Define external parameters.
     *
     * Either:
     * - pass slug to validate a canonical profile from content/faculties; or
     * - pass profilejson to validate a submitted JSON payload.
     *
     * If profilejson is provided, it is validated instead of loading by slug.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'slug' => new external_value(
                PARAM_ALPHANUMEXT,
                'Canonical faculty slug. Used only to load an existing profile when profilejson is empty.',
                VALUE_DEFAULT,
                ''
            ),
            'profilejson' => new external_value(
                PARAM_RAW,
                'Optional raw Faculty Profile JSON payload to validate.',
                VALUE_DEFAULT,
                ''
            ),
        ]);
    }

    /**
     * Validate a Faculty Profile.
     *
     * @param string $slug Canonical faculty slug.
     * @param string $profilejson Optional raw JSON payload.
     * @return array<string, mixed>
     */
    public static function execute(string $slug = '', string $profilejson = ''): array {
        $params = self::validate_parameters(self::execute_parameters(), [
            'slug' => $slug,
            'profilejson' => $profilejson,
        ]);

        $context = context_system::instance();
        self::validate_context($context);
        require_capability(self::CAPABILITY, $context);

        $slug = self::clean_slug((string)$params['slug']);
        $profilejson = trim((string)$params['profilejson']);

        if ($slug === '' && $profilejson === '') {
            throw new invalid_parameter_exception('Either slug or profilejson is required.');
        }

        $source = 'repository';

        if ($profilejson !== '') {
            $profile = self::decode_profile_json($profilejson);
            $source = 'payload';
        } else {
            $profile = self::load_profile_by_slug($slug);
        }

        $profile = self::normalise_profile_array($profile);

        $schema = self::get_string_value($profile, 'schema_version');
        $profileslug = self::get_string_value($profile, 'slug', $slug);
        $facultyid = self::get_string_value($profile, 'faculty_id');
        $voieid = self::get_string_value($profile, 'voie_id');
        $status = self::get_string_value($profile, 'status');
        $checksum = self::hash_profile($profile);

        $serviceerrors = [];

        if ($slug !== '' && $profileslug !== '' && $slug !== $profileslug) {
            $serviceerrors[] = self::make_issue(
                'slug',
                'slug_mismatch',
                'The requested slug does not match the profile slug.'
            );
        }

        $validation = self::run_validator($profile);

        $errors = array_merge($serviceerrors, $validation['errors']);
        $warnings = $validation['warnings'];
        $valid = count($errors) === 0;

        self::trigger_validation_event(
            $context,
            $valid,
            $source,
            $profileslug,
            $facultyid,
            $voieid,
            $status,
            count($errors),
            count($warnings),
            $checksum
        );

        return [
            'valid' => $valid,
            'source' => $source,
            'schema_version' => $schema,
            'slug' => $profileslug,
            'faculty_id' => $facultyid,
            'voie_id' => $voieid,
            'status' => $status,
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
            'valid' => new external_value(PARAM_BOOL, 'Whether the faculty profile is valid.'),
            'source' => new external_value(PARAM_ALPHANUMEXT, 'Validation source: repository or payload.'),
            'schema_version' => new external_value(PARAM_TEXT, 'Profile schema version.'),
            'slug' => new external_value(PARAM_ALPHANUMEXT, 'Canonical faculty slug.'),
            'faculty_id' => new external_value(PARAM_ALPHANUMEXT, 'Canonical faculty id.'),
            'voie_id' => new external_value(PARAM_ALPHANUMEXT, 'Canonical Atlas voie id.'),
            'status' => new external_value(PARAM_ALPHANUMEXT, 'Faculty profile publication status.'),
            'error_count' => new external_value(PARAM_INT, 'Number of validation errors.'),
            'warning_count' => new external_value(PARAM_INT, 'Number of validation warnings.'),
            'checksum' => new external_value(PARAM_ALPHANUM, 'SHA-256 checksum of the validated profile payload.'),
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
     * Clean and validate a canonical slug.
     *
     * Slugs are identifiers, not file paths.
     *
     * @param string $slug Raw slug.
     * @return string
     */
    private static function clean_slug(string $slug): string {
        $slug = strtolower(trim($slug));

        if ($slug === '') {
            return '';
        }

        if (!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug)) {
            throw new invalid_parameter_exception('Invalid faculty slug.');
        }

        return $slug;
    }

    /**
     * Decode a raw JSON payload.
     *
     * @param string $profilejson Raw JSON payload.
     * @return array<string, mixed>
     */
    private static function decode_profile_json(string $profilejson): array {
        if (strlen($profilejson) > self::MAX_JSON_BYTES) {
            throw new invalid_parameter_exception('Faculty profile JSON payload is too large.');
        }

        $decoded = json_decode($profilejson, true, 64);

        if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
            throw new invalid_parameter_exception('Invalid faculty profile JSON: ' . json_last_error_msg());
        }

        return $decoded;
    }

    /**
     * Load a canonical Faculty Profile by slug.
     *
     * @param string $slug Canonical slug.
     * @return array<string, mixed>
     */
    private static function load_profile_by_slug(string $slug): array {
        if ($slug === '') {
            throw new invalid_parameter_exception('Slug is required when profilejson is empty.');
        }

        if (!class_exists(faculty_repository::class)) {
            throw new \coding_exception('The faculty_repository class is required.');
        }

        $repository = new faculty_repository();
        $profile = $repository->get_by_slug($slug);

        if (!is_array($profile)) {
            throw new \coding_exception('faculty_repository::get_by_slug() must return an array.');
        }

        return $profile;
    }

    /**
     * Normalize profile array shape.
     *
     * @param array<string, mixed> $profile Faculty profile.
     * @return array<string, mixed>
     */
    private static function normalise_profile_array(array $profile): array {
        return $profile;
    }

    /**
     * Run the canonical Faculty Profile validator.
     *
     * The canonical validator method is expected to be validate(array $profile).
     * A small compatibility adapter is kept here so parallel implementations can
     * converge without changing the external service contract.
     *
     * @param array<string, mixed> $profile Faculty profile.
     * @return array{errors: array<int, array<string, string>>, warnings: array<int, array<string, string>>}
     */
    private static function run_validator(array $profile): array {
        if (!class_exists(faculty_validator::class)) {
            return [
                'errors' => [
                    self::make_issue(
                        '',
                        'validator_unavailable',
                        'The faculty_validator class is not available.'
                    ),
                ],
                'warnings' => [],
            ];
        }

        try {
            $validator = new faculty_validator();

            foreach (['validate', 'validate_profile', 'validate_array'] as $method) {
                if (method_exists($validator, $method)) {
                    return self::normalise_validation_result($validator->{$method}($profile));
                }
            }

            foreach (['validate', 'validate_profile', 'validate_array'] as $method) {
                if (is_callable([faculty_validator::class, $method])) {
                    return self::normalise_validation_result(call_user_func([faculty_validator::class, $method], $profile));
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
                    'The faculty_validator class must expose validate(array $profile).'
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
                    self::make_issue('', 'validation_failed', 'The faculty profile is invalid.'),
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
                $errors[] = self::make_issue('', 'validation_failed', 'The faculty profile is invalid.');
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
                $errors[] = self::make_issue('', 'validation_failed', 'The faculty profile is invalid.');
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
     * Get a string value from the profile.
     *
     * @param array<string, mixed> $profile Faculty profile.
     * @param string $key Field key.
     * @param string $fallback Fallback value.
     * @return string
     */
    private static function get_string_value(array $profile, string $key, string $fallback = ''): string {
        if (!array_key_exists($key, $profile)) {
            return $fallback;
        }

        $value = $profile[$key];

        if (is_scalar($value)) {
            return trim((string)$value);
        }

        return $fallback;
    }

    /**
     * Compute a stable SHA-256 hash for the validated profile.
     *
     * @param array<string, mixed> $profile Faculty profile.
     * @return string
     */
    private static function hash_profile(array $profile): string {
        $copy = $profile;
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
     * Trigger the faculty_profile_validated event when available.
     *
     * The event intentionally receives only ids, status, counts and hash.
     * It must not receive or log the full JSON payload.
     *
     * @param context_system $context System context.
     * @param bool $valid Validation result.
     * @param string $source Validation source.
     * @param string $slug Faculty slug.
     * @param string $facultyid Faculty id.
     * @param string $voieid Atlas voie id.
     * @param string $status Profile status.
     * @param int $errorcount Error count.
     * @param int $warningcount Warning count.
     * @param string $checksum Payload checksum.
     * @return void
     */
    private static function trigger_validation_event(
        context_system $context,
        bool $valid,
        string $source,
        string $slug,
        string $facultyid,
        string $voieid,
        string $status,
        int $errorcount,
        int $warningcount,
        string $checksum
    ): void {
        if (!class_exists(faculty_profile_validated::class)) {
            return;
        }

        try {
            $event = faculty_profile_validated::create([
                'context' => $context,
                'other' => [
                    'valid' => $valid ? 1 : 0,
                    'source' => $source,
                    'slug' => $slug,
                    'facultyid' => $facultyid,
                    'voieid' => $voieid,
                    'status' => $status,
                    'errorcount' => $errorcount,
                    'warningcount' => $warningcount,
                    'checksum' => $checksum,
                ],
            ]);

            $event->trigger();
        } catch (\Throwable $exception) {
            debugging(
                'Unable to trigger faculty_profile_validated event: ' . $exception->getMessage(),
                DEBUG_DEVELOPER
            );
        }
    }
}