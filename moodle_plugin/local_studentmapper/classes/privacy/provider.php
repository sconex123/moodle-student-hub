<?php
/**
 * Privacy provider for Student Mapper plugin (GDPR compliance)
 *
 * @package    local_studentmapper
 * @copyright  2024
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_studentmapper\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

defined('MOODLE_INTERNAL') || die();

/**
 * Privacy provider implementation for GDPR compliance
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\core_userlist_provider,
    \core_privacy\local\request\plugin\provider {

    /**
     * Return metadata about data stored by this plugin
     *
     * @param collection $collection Collection to add metadata to
     * @return collection Updated collection
     */
    public static function get_metadata(collection $collection): collection {
        // Sync logs table.
        $collection->add_database_table(
            'local_studentmapper_log',
            [
                'userid' => 'privacy:metadata:log:userid',
                'eventtype' => 'privacy:metadata:log:eventtype',
                'payload' => 'privacy:metadata:log:payload',
                'response' => 'privacy:metadata:log:response',
                'http_code' => 'privacy:metadata:log:http_code',
                'success' => 'privacy:metadata:log:success',
                'error_message' => 'privacy:metadata:log:error_message',
                'execution_time' => 'privacy:metadata:log:execution_time',
                'timecreated' => 'privacy:metadata:log:timecreated',
            ],
            'privacy:metadata:log'
        );

        // Queue table.
        $collection->add_database_table(
            'local_studentmapper_queue',
            [
                'userid' => 'privacy:metadata:queue:userid',
                'payload' => 'privacy:metadata:queue:payload',
                'eventtype' => 'privacy:metadata:queue:eventtype',
                'attempts' => 'privacy:metadata:queue:attempts',
                'status' => 'privacy:metadata:queue:status',
                'last_error' => 'privacy:metadata:queue:last_error',
                'timecreated' => 'privacy:metadata:queue:timecreated',
                'timemodified' => 'privacy:metadata:queue:timemodified',
            ],
            'privacy:metadata:queue'
        );

        // External API.
        $collection->add_external_location_link(
            'external_api',
            [
                'userid' => 'privacy:metadata:external:userid',
                'userdata' => 'privacy:metadata:external:userdata',
            ],
            'privacy:metadata:external'
        );

        return $collection;
    }

    /**
     * Get list of contexts that contain user data
     *
     * @param int $userid User ID
     * @return contextlist List of contexts
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();

        // Student Mapper stores data in system context only.
        $sql = "SELECT DISTINCT ctx.id
                FROM {context} ctx
                WHERE ctx.contextlevel = :contextlevel
                  AND EXISTS (
                      SELECT 1 FROM {local_studentmapper_log} l WHERE l.userid = :userid1
                      UNION
                      SELECT 1 FROM {local_studentmapper_queue} q WHERE q.userid = :userid2
                  )";

        $params = [
            'contextlevel' => CONTEXT_SYSTEM,
            'userid1' => $userid,
            'userid2' => $userid,
        ];

        $contextlist->add_from_sql($sql, $params);

        return $contextlist;
    }

    /**
     * Get list of users in a context
     *
     * @param userlist $userlist User list to populate
     */
    public static function get_users_in_context(userlist $userlist) {
        $context = $userlist->get_context();

        if ($context->contextlevel != CONTEXT_SYSTEM) {
            return;
        }

        // Add users from sync logs.
        $sql = "SELECT userid FROM {local_studentmapper_log} WHERE userid > 0";
        $userlist->add_from_sql('userid', $sql, []);

        // Add users from queue.
        $sql = "SELECT userid FROM {local_studentmapper_queue} WHERE userid > 0";
        $userlist->add_from_sql('userid', $sql, []);
    }

    /**
     * Export user data
     *
     * @param approved_contextlist $contextlist Approved contexts to export
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;

        $userid = $contextlist->get_user()->id;

        foreach ($contextlist as $context) {
            if ($context->contextlevel != CONTEXT_SYSTEM) {
                continue;
            }

            // Export sync logs.
            $logs = $DB->get_records('local_studentmapper_log', ['userid' => $userid], 'timecreated DESC');
            if ($logs) {
                $logdata = [];
                foreach ($logs as $log) {
                    $logdata[] = [
                        'eventtype' => $log->eventtype,
                        'success' => $log->success ? get_string('yes') : get_string('no'),
                        'http_code' => $log->http_code,
                        'execution_time' => $log->execution_time . ' ms',
                        'error_message' => $log->error_message,
                        'payload' => $log->payload,
                        'response' => $log->response,
                        'timecreated' => transform::datetime($log->timecreated),
                    ];
                }

                writer::with_context($context)->export_data(
                    [get_string('privacy:path:synclogs', 'local_studentmapper')],
                    (object)['logs' => $logdata]
                );
            }

            // Export queue items.
            $queueitems = $DB->get_records('local_studentmapper_queue', ['userid' => $userid], 'timecreated DESC');
            if ($queueitems) {
                $queuedata = [];
                foreach ($queueitems as $item) {
                    $queuedata[] = [
                        'eventtype' => $item->eventtype,
                        'status' => $item->status,
                        'attempts' => $item->attempts . ' / ' . $item->max_attempts,
                        'last_error' => $item->last_error,
                        'payload' => $item->payload,
                        'timecreated' => transform::datetime($item->timecreated),
                        'timemodified' => transform::datetime($item->timemodified),
                        'next_retry' => transform::datetime($item->next_retry),
                    ];
                }

                writer::with_context($context)->export_data(
                    [get_string('privacy:path:queue', 'local_studentmapper')],
                    (object)['queue' => $queuedata]
                );
            }
        }
    }

    /**
     * Delete all user data for a context
     *
     * @param \context $context Context to delete data from
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;

        if ($context->contextlevel != CONTEXT_SYSTEM) {
            return;
        }

        // Delete all sync logs.
        $DB->delete_records('local_studentmapper_log', []);

        // Delete all queue items.
        $DB->delete_records('local_studentmapper_queue', []);
    }

    /**
     * Delete all user data for specific user
     *
     * @param approved_contextlist $contextlist Approved contexts
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;

        $userid = $contextlist->get_user()->id;

        foreach ($contextlist as $context) {
            if ($context->contextlevel != CONTEXT_SYSTEM) {
                continue;
            }

            // Delete sync logs for user.
            $DB->delete_records('local_studentmapper_log', ['userid' => $userid]);

            // Delete queue items for user.
            $DB->delete_records('local_studentmapper_queue', ['userid' => $userid]);
        }
    }

    /**
     * Delete data for multiple users
     *
     * @param approved_userlist $userlist Approved user list
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        global $DB;

        $context = $userlist->get_context();

        if ($context->contextlevel != CONTEXT_SYSTEM) {
            return;
        }

        $userids = $userlist->get_userids();

        if (empty($userids)) {
            return;
        }

        list($insql, $inparams) = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);

        // Delete sync logs for users.
        $DB->delete_records_select('local_studentmapper_log', "userid $insql", $inparams);

        // Delete queue items for users.
        $DB->delete_records_select('local_studentmapper_queue', "userid $insql", $inparams);
    }
}
