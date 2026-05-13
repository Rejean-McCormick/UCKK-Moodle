<?php
// This file is part of Moodle - http://moodle.org/

namespace aiprovider_uckk\privacy;

defined('MOODLE_INTERNAL') || die();

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\transform;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

/**
 * Privacy provider for the governed UCKK AI provider.
 *
 * The provider stores no data unless prompt/response logging is enabled.
 * When logs exist, they are exported and deleted through Moodle privacy APIs.
 *
 * @package    aiprovider_uckk
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\plugin\provider,
    \core_privacy\local\request\core_userlist_provider {

    /** @var string AI request log table. */
    private const LOG_TABLE = 'aiprovider_uckk_log';

    /**
     * Describe personal data stored by this plugin.
     *
     * @param collection $collection Metadata collection.
     * @return collection
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table(self::LOG_TABLE, [
            'userid' => 'privacy:metadata:log:userid',
            'contextid' => 'privacy:metadata:log:contextid',
            'action' => 'privacy:metadata:log:action',
            'prompt' => 'privacy:metadata:log:prompt',
            'response' => 'privacy:metadata:log:response',
            'redactedprompt' => 'privacy:metadata:log:redactedprompt',
            'provider_model' => 'privacy:metadata:log:provider_model',
            'timecreated' => 'privacy:metadata:log:timecreated',
        ], 'privacy:metadata:log');

        $collection->link_external_location('uckk_ai_provider', [
            'prompt' => 'privacy:metadata:external:prompt',
            'context' => 'privacy:metadata:external:context',
            'action' => 'privacy:metadata:external:action',
        ], 'privacy:metadata:external');

        return $collection;
    }

    /**
     * Get contexts containing AI logs for a user.
     *
     * @param int $userid User ID.
     * @return contextlist
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();

        if (!self::log_table_exists()) {
            return $contextlist;
        }

        $sql = "SELECT DISTINCT ctx.id
                  FROM {context} ctx
                  JOIN {" . self::LOG_TABLE . "} l ON l.contextid = ctx.id
                 WHERE l.userid = :userid";

        $contextlist->add_from_sql($sql, ['userid' => $userid]);

        return $contextlist;
    }

    /**
     * Export AI logs for approved contexts.
     *
     * @param approved_contextlist $contextlist Approved contexts.
     * @return void
     */
    public static function export_user_data(approved_contextlist $contextlist): void {
        global $DB;

        if (!self::log_table_exists()) {
            return;
        }

        $userid = $contextlist->get_user()->id;

        foreach ($contextlist->get_contexts() as $context) {
            $records = $DB->get_records(
                self::LOG_TABLE,
                [
                    'userid' => $userid,
                    'contextid' => $context->id,
                ],
                'timecreated ASC, id ASC'
            );

            foreach ($records as $record) {
                writer::with_context($context)->export_data([
                    get_string('privacy:path:ailogs', 'aiprovider_uckk'),
                    $record->id,
                ], self::export_record($record));
            }
        }
    }

    /**
     * Delete all AI logs in a context.
     *
     * @param \context $context Moodle context.
     * @return void
     */
    public static function delete_data_for_all_users_in_context(\context $context): void {
        global $DB;

        if (!self::log_table_exists()) {
            return;
        }

        $DB->delete_records(self::LOG_TABLE, ['contextid' => $context->id]);
    }

    /**
     * Delete AI logs for one user in approved contexts.
     *
     * @param approved_contextlist $contextlist Approved contexts.
     * @return void
     */
    public static function delete_data_for_user(approved_contextlist $contextlist): void {
        global $DB;

        if (!self::log_table_exists()) {
            return;
        }

        $userid = $contextlist->get_user()->id;

        foreach ($contextlist->get_contexts() as $context) {
            $DB->delete_records(self::LOG_TABLE, [
                'userid' => $userid,
                'contextid' => $context->id,
            ]);
        }
    }

    /**
     * Add users with AI logs in a context.
     *
     * @param userlist $userlist User list.
     * @return void
     */
    public static function get_users_in_context(userlist $userlist): void {
        if (!self::log_table_exists()) {
            return;
        }

        $context = $userlist->get_context();

        $sql = "SELECT userid
                  FROM {" . self::LOG_TABLE . "}
                 WHERE contextid = :contextid";

        $userlist->add_from_sql('userid', $sql, ['contextid' => $context->id]);
    }

    /**
     * Delete AI logs for approved users in one context.
     *
     * @param approved_userlist $userlist Approved user list.
     * @return void
     */
    public static function delete_data_for_users(approved_userlist $userlist): void {
        global $DB;

        if (!self::log_table_exists()) {
            return;
        }

        $userids = $userlist->get_userids();

        if (empty($userids)) {
            return;
        }

        [$insql, $params] = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);
        $params['contextid'] = $userlist->get_context()->id;

        $DB->delete_records_select(
            self::LOG_TABLE,
            "contextid = :contextid AND userid {$insql}",
            $params
        );
    }

    /**
     * Convert a DB record to privacy export data.
     *
     * @param \stdClass $record Log record.
     * @return \stdClass
     */
    private static function export_record(\stdClass $record): \stdClass {
        return (object)[
            'action' => $record->action ?? '',
            'provider_model' => $record->provider_model ?? '',
            'prompt' => self::export_prompt($record),
            'response' => self::export_response($record),
            'redactedprompt' => $record->redactedprompt ?? '',
            'status' => $record->status ?? '',
            'timecreated' => transform::datetime($record->timecreated ?? 0),
        ];
    }

    /**
     * Export prompt text only if prompt logging is enabled.
     *
     * @param \stdClass $record Log record.
     * @return string
     */
    private static function export_prompt(\stdClass $record): string {
        if (!get_config('aiprovider_uckk', 'log_prompts')) {
            return get_string('privacy:promptloggingdisabled', 'aiprovider_uckk');
        }

        return $record->prompt ?? '';
    }

    /**
     * Export response text only if response logging is enabled.
     *
     * @param \stdClass $record Log record.
     * @return string
     */
    private static function export_response(\stdClass $record): string {
        if (!get_config('aiprovider_uckk', 'log_responses')) {
            return get_string('privacy:responseloggingdisabled', 'aiprovider_uckk');
        }

        return $record->response ?? '';
    }

    /**
     * Check whether the log table exists.
     *
     * This lets privacy checks pass safely before the table is installed or
     * when logging is disabled in a deployment that never created records.
     *
     * @return bool
     */
    private static function log_table_exists(): bool {
        global $DB;

        return $DB->get_manager()->table_exists(self::LOG_TABLE);
    }
}