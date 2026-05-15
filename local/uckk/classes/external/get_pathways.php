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
use local_uckk\service\pathway_service;

defined('MOODLE_INTERNAL') || die();

/**
 * External function returning visible pathways.
 *
 * @package    local_uckk
 */
final class get_pathways extends external_api {
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'contextid' => new external_value(PARAM_INT, 'Context id', VALUE_DEFAULT, 0),
            'programid' => new external_value(PARAM_INT, 'Optional program id', VALUE_DEFAULT, 0),
            'status' => new external_value(PARAM_ALPHANUMEXT, 'Optional status filter', VALUE_DEFAULT, ''),
            'visibility' => new external_value(PARAM_ALPHANUMEXT, 'Optional visibility filter', VALUE_DEFAULT, ''),
            'offset' => new external_value(PARAM_INT, 'Offset', VALUE_DEFAULT, 0),
            'limit' => new external_value(PARAM_INT, 'Limit', VALUE_DEFAULT, 0),
        ]);
    }

    public static function execute(int $contextid = 0, int $programid = 0, string $status = '', string $visibility = '', int $offset = 0, int $limit = 0): array {
        $params = self::validate_parameters(self::execute_parameters(), [
            'contextid' => $contextid,
            'programid' => $programid,
            'status' => $status,
            'visibility' => $visibility,
            'offset' => $offset,
            'limit' => $limit,
        ]);

        $context = $params['contextid'] > 0 ? context::instance_by_id($params['contextid']) : context_system::instance();
        self::validate_context($context);

        $filters = [];
        if ($params['programid'] > 0) {
            $filters['programid'] = $params['programid'];
        }
        if ($params['status'] !== '') {
            $filters['status'] = $params['status'];
        }
        if ($params['visibility'] !== '') {
            $filters['visibility'] = $params['visibility'];
        }

        $items = (new pathway_service())->get_pathways($filters, $context, [
            'offset' => $params['offset'],
            'limit' => $params['limit'],
        ]);

        return array_map(static function(\stdClass $item): array {
            return [
                'id' => (int)$item->id,
                'programid' => (int)$item->programid,
                'shortname' => (string)$item->shortname,
                'fullname' => (string)$item->fullname,
                'status' => (string)$item->status,
                'visibility' => (string)$item->visibility,
                'programfullname' => (string)($item->programfullname ?? ''),
            ];
        }, $items);
    }

    public static function execute_returns(): external_multiple_structure {
        return new external_multiple_structure(new external_single_structure([
            'id' => new external_value(PARAM_INT, 'Pathway id'),
            'programid' => new external_value(PARAM_INT, 'Program id'),
            'shortname' => new external_value(PARAM_ALPHANUMEXT, 'Shortname'),
            'fullname' => new external_value(PARAM_TEXT, 'Full name'),
            'status' => new external_value(PARAM_ALPHANUMEXT, 'Status'),
            'visibility' => new external_value(PARAM_ALPHANUMEXT, 'Visibility'),
            'programfullname' => new external_value(PARAM_TEXT, 'Program label', VALUE_DEFAULT, ''),
        ]));
    }
}
