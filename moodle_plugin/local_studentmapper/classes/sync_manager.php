<?php
namespace local_studentmapper;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/user/profile/lib.php');

/**
 * Sync manager - orchestrates all sync operations
 *
 * @package    local_studentmapper
 * @copyright  2024
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class sync_manager {

    /**
     * Sync a user to the external API
     *
     * @param int $userid Moodle user ID
     * @param string $eventtype Event type (user_created, user_updated, manual)
     * @param int|null $queueid Queue ID if retrying from queue
     * @return array Result array with success status and details
     */
    public static function sync_user($userid, $eventtype = 'manual', $queueid = null) {
        global $DB;

        $starttime = microtime(true);

        try {
            // Fetch user record.
            $user = $DB->get_record('user', ['id' => $userid]);
            if (!$user) {
                $error = "User with ID $userid not found";
                sync_logger::log_error($userid, 'sync', $error);
                return [
                    'success' => false,
                    'error' => $error,
                ];
            }

            // Load custom profile fields.
            profile_load_data($user);

            // Build payload from field mappings.
            $payload = self::build_payload($user);

            // Apply transformations if enabled.
            $transformationsenabled = get_config('local_studentmapper', 'transformations_enabled');
            if ($transformationsenabled && class_exists('\local_studentmapper\transformer')) {
                $payload = transformer::apply_all_transformations($payload);
            }

            // Send to API.
            $result = api_client::send($payload);

            // Log the sync attempt.
            sync_logger::log_sync(
                $userid,
                $queueid,
                $eventtype,
                $payload,
                $result['body'],
                $result['http_code'],
                $result['success'],
                $result['error'],
                $result['execution_time']
            );

            // Handle failure by adding to queue.
            if (!$result['success']) {
                // Only add to queue if not already from queue (avoid infinite loop).
                if ($queueid === null) {
                    queue_manager::add_to_queue($userid, $payload, $eventtype, $result['error']);
                }
            }

            return $result;

        } catch (\Exception $e) {
            $executiontime = round((microtime(true) - $starttime) * 1000);
            $error = 'Exception during sync: ' . $e->getMessage();

            sync_logger::log_sync(
                $userid,
                $queueid,
                $eventtype,
                [],
                null,
                null,
                false,
                $error,
                $executiontime
            );

            return [
                'success' => false,
                'error' => $error,
            ];
        }
    }

    /**
     * Build payload from user object and field mappings
     *
     * @param object $user User object with profile fields loaded
     * @return array Payload array
     */
    private static function build_payload($user) {
        $mappingsraw = get_config('local_studentmapper', 'mappings');
        if (empty($mappingsraw)) {
            // Default mappings if none configured.
            $mappingsraw = "firstname:first_name\nlastname:last_name\nemail:email\nidnumber:student_id\nusername:username";
        }

        $lines = explode("\n", $mappingsraw);
        $payload = [];

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) {
                continue;
            }

            $parts = explode(':', $line);
            if (count($parts) < 2) {
                continue;
            }

            $moodlefield = trim($parts[0]);
            $externalfield = trim($parts[1]);

            // Check if property exists on user object.
            if (isset($user->$moodlefield)) {
                $payload[$externalfield] = $user->$moodlefield;
            }
        }

        // Always include moodle_id as identifier.
        if (!isset($payload['moodle_id'])) {
            $payload['moodle_id'] = $user->id;
        }

        return $payload;
    }

    /**
     * Sync multiple users
     *
     * @param array $userids Array of user IDs
     * @param string $eventtype Event type
     * @param int $delayms Delay between syncs in milliseconds
     * @return array Results array with success count and details
     */
    public static function sync_users($userids, $eventtype = 'manual', $delayms = 100) {
        $results = [
            'total' => count($userids),
            'success' => 0,
            'failed' => 0,
            'details' => [],
        ];

        foreach ($userids as $userid) {
            $result = self::sync_user($userid, $eventtype);

            if ($result['success']) {
                $results['success']++;
            } else {
                $results['failed']++;
            }

            $results['details'][$userid] = $result;

            // Delay between requests to avoid overwhelming API.
            if ($delayms > 0) {
                usleep($delayms * 1000); // Convert ms to microseconds.
            }
        }

        return $results;
    }

    /**
     * Process queue item
     *
     * @param object $queueitem Queue record
     * @return bool Success status
     */
    public static function process_queue_item($queueitem) {
        // Mark as processing.
        queue_manager::mark_processing($queueitem->id);

        // Decode payload.
        $payload = json_decode($queueitem->payload, true);
        if (!$payload) {
            $error = 'Invalid payload JSON in queue item';
            queue_manager::mark_failed($queueitem->id, $error);
            return false;
        }

        // Send to API.
        $result = api_client::send($payload);

        // Log the attempt.
        sync_logger::log_sync(
            $queueitem->userid,
            $queueitem->id,
            $queueitem->eventtype,
            $payload,
            $result['body'],
            $result['http_code'],
            $result['success'],
            $result['error'],
            $result['execution_time']
        );

        // Update queue status.
        if ($result['success']) {
            queue_manager::mark_completed($queueitem->id);
            return true;
        } else {
            queue_manager::mark_failed($queueitem->id, $result['error']);
            return false;
        }
    }

    /**
     * Sync all users matching criteria
     *
     * @param array $filters Filter criteria (cohortid, roleid, all)
     * @param int $limit Maximum number of users to sync
     * @param bool $usequeue Add to queue instead of immediate sync
     * @return array Results array
     */
    public static function sync_users_by_criteria($filters = [], $limit = 1000, $usequeue = false) {
        global $DB;

        $sql = "SELECT DISTINCT u.id FROM {user} u WHERE u.deleted = 0 AND u.suspended = 0";
        $params = [];

        // Filter by cohort.
        if (!empty($filters['cohortid'])) {
            $sql .= " AND u.id IN (
                SELECT userid FROM {cohort_members}
                WHERE cohortid = :cohortid
            )";
            $params['cohortid'] = $filters['cohortid'];
        }

        // Filter by role.
        if (!empty($filters['roleid'])) {
            $sql .= " AND u.id IN (
                SELECT userid FROM {role_assignments}
                WHERE roleid = :roleid
            )";
            $params['roleid'] = $filters['roleid'];
        }

        $sql .= " ORDER BY u.id ASC";

        $users = $DB->get_records_sql($sql, $params, 0, $limit);
        $userids = array_keys($users);

        if ($usequeue) {
            // Add all to queue.
            $count = 0;
            foreach ($userids as $userid) {
                $user = $DB->get_record('user', ['id' => $userid]);
                if ($user) {
                    profile_load_data($user);
                    $payload = self::build_payload($user);
                    queue_manager::add_to_queue($userid, $payload, 'manual');
                    $count++;
                }
            }
            return [
                'success' => true,
                'queued' => $count,
                'message' => "$count users added to queue",
            ];
        } else {
            // Immediate sync.
            return self::sync_users($userids, 'manual');
        }
    }
}
