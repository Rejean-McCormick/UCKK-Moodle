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

defined('MOODLE_INTERNAL') || die();

/**
 * External function returning canon items.
 *
 * @package    local_uckk
 */
final class get_canon_items extends external_api {
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'canonid' => new external_value(PARAM_INT, 'Specific canon item id', VALUE_DEFAULT, 0),
            'contextid' => new external_value(PARAM_INT, 'Context id', VALUE_DEFAULT, 0),
            'canonarea' => new external_value(PARAM_ALPHANUMEXT, 'Canon area', VALUE_DEFAULT, ''),
            'language' => new external_value(PARAM_LANG, 'Language code', VALUE_DEFAULT, ''),
        ]);
    }

    public static function execute(int $canonid = 0, int $contextid = 0, string $canonarea = '', string $language = ''): array {
        global $DB;

        $params = self::validate_parameters(self::execute_parameters(), [
            'canonid' => $canonid,
            'contextid' => $contextid,
            'canonarea' => $canonarea,
            'language' => $language,
        ]);
        $context = $params['contextid'] > 0 ? context::instance_by_id($params['contextid']) : context_system::instance();
        self::validate_context($context);
        require_capability('local/uckk:viewcampus', $context);

        $conditions = [];
        if ($params['canonid'] > 0) {
            $conditions['id'] = $params['canonid'];
        }
        if ($params['canonarea'] !== '') {
            $conditions['canonarea'] = $params['canonarea'];
        }
        if ($params['language'] !== '') {
            $conditions['language'] = $params['language'];
        }

        $items = [];
        if ($DB->get_manager()->table_exists('local_uckk_canon')) {
            foreach ($DB->get_records('local_uckk_canon', $conditions, 'sortorder ASC, title ASC') as $record) {
                $items[] = [
                    'id' => (int)$record->id,
                    'canonkey' => (string)$record->canonkey,
                    'title' => format_string((string)$record->title),
                    'canonarea' => (string)$record->canonarea,
                    'language' => (string)$record->language,
                    'body' => format_text((string)$record->body, (int)($record->bodyformat ?? FORMAT_HTML)),
                    'status' => (string)$record->status,
                    'visibility' => (string)$record->visibility,
                ];
            }
        }

        return $items;
    }

    public static function execute_returns(): external_multiple_structure {
        return new external_multiple_structure(new external_single_structure([
            'id' => new external_value(PARAM_INT, 'Canon item id'),
            'canonkey' => new external_value(PARAM_ALPHANUMEXT, 'Canon key'),
            'title' => new external_value(PARAM_TEXT, 'Title'),
            'canonarea' => new external_value(PARAM_ALPHANUMEXT, 'Canon area'),
            'language' => new external_value(PARAM_LANG, 'Language'),
            'body' => new external_value(PARAM_RAW, 'Formatted body'),
            'status' => new external_value(PARAM_ALPHANUMEXT, 'Status'),
            'visibility' => new external_value(PARAM_ALPHANUMEXT, 'Visibility'),
        ]));
    }
}
