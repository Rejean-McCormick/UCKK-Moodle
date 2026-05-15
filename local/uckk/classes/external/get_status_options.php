<?php
// This file is part of Moodle - https://moodle.org/

namespace local_uckk\external;

use context;
use context_system;
use external_api;
use external_function_parameters;
use external_multiple_structure;
use external_single_structure;
use external_value;
use local_uckk\local\constants;

defined('MOODLE_INTERNAL') || die();

/**
 * External function returning canonical options.
 *
 * @package    local_uckk
 */
final class get_status_options extends external_api {
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'contextid' => new external_value(PARAM_INT, 'Context id', VALUE_DEFAULT, 0),
        ]);
    }

    public static function execute(int $contextid = 0): array {
        $params = self::validate_parameters(self::execute_parameters(), ['contextid' => $contextid]);
        $context = $params['contextid'] > 0 ? context::instance_by_id($params['contextid']) : context_system::instance();
        self::validate_context($context);
        require_capability('local/uckk:viewcampus', $context);

        $pack = static function(array $values, callable $labeler): array {
            return array_map(static function(string $value) use ($labeler): array {
                return ['key' => $value, 'label' => $labeler($value)];
            }, $values);
        };

        return [
            'statuses' => $pack(array_merge(constants::allowed_statuses(), [constants::STATUS_HIDDEN]), [constants::class, 'status_label']),
            'visibilities' => $pack(constants::allowed_visibilities(), [constants::class, 'visibility_label']),
            'provenances' => $pack(constants::allowed_provenances(), static fn(string $key): string => ucwords(str_replace('_', ' ', $key))),
            'symbolicroles' => $pack(constants::symbolic_roles(), static fn(string $key): string => ucwords(str_replace('_', ' ', $key))),
            'programtypes' => $pack(constants::allowed_program_types(), static fn(string $key): string => ucwords(str_replace('_', ' ', $key))),
            'progressstates' => $pack(constants::allowed_progress_states(), static fn(string $key): string => constants::status_label($key)),
        ];
    }

    public static function execute_returns(): external_single_structure {
        $option = new external_multiple_structure(new external_single_structure([
            'key' => new external_value(PARAM_ALPHANUMEXT, 'Key'),
            'label' => new external_value(PARAM_TEXT, 'Label'),
        ]));

        return new external_single_structure([
            'statuses' => $option,
            'visibilities' => $option,
            'provenances' => $option,
            'symbolicroles' => $option,
            'programtypes' => $option,
            'progressstates' => $option,
        ]);
    }
}
