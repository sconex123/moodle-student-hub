<?php
namespace local_studentmapper;

defined('MOODLE_INTERNAL') || die();

/**
 * Logger for sync operations
 *
 * @package    local_studentmapper
 * @copyright  2024
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class sync_logger {

    /**
     * Log a sync attempt
     *
     * @param int $userid Moodle user ID
     * @param int|null $queueid Queue entry ID if from queue
     * @param string $eventtype Event type
     * @param array $payload Payload sent
     * @param string|null $response API response
     * @param int|null $httpcode HTTP status code
     * @param bool $success Success status
     * @param string|null $errormessage Error message if failed
     * @param int|null $executiontime Execution time in milliseconds
     * @return int Log entry ID
     */
    public static function log_sync($userid, $queueid, $eventtype, $payload, $response,
                                     $httpcode, $success, $errormessage = null, $executiontime = null) {
        global $DB;

        $record = new \stdClass();
        $record->userid = $userid;
        $record->queueid = $queueid;
        $record->eventtype = $eventtype;
        $record->payload = json_encode($payload);
        $record->response = $response;
        $record->http_code = $httpcode;
        $record->success = $success ? 1 : 0;
        $record->error_message = $errormessage;
        $record->execution_time = $executiontime;
        $record->timecreated = time();

        return $DB->insert_record('local_studentmapper_log', $record);
    }

    /**
     * Log an error
     *
     * @param int $userid User ID
     * @param string $context Context of the error
     * @param string $error Error message
     */
    public static function log_error($userid, $context, $error) {
        // Use Moodle's debugging function for development errors.
        debugging("Student Mapper Error [$context] for user $userid: $error", DEBUG_DEVELOPER);

        // Also log to database if it's a sync error.
        if ($context === 'sync') {
            self::log_sync($userid, null, 'error', [], null, null, false, $error, null);
        }
    }

    /**
     * Get logs with filters and pagination
     *
     * @param array $filters Filter criteria (userid, success, datefrom, dateto, eventtype)
     * @param int $page Page number (0-based)
     * @param int $perpage Records per page
     * @return array Array of log records
     */
    public static function get_logs($filters = [], $page = 0, $perpage = 50) {
        global $DB;

        $where = [];
        $params = [];

        if (!empty($filters['userid'])) {
            $where[] = 'userid = :userid';
            $params['userid'] = $filters['userid'];
        }

        if (isset($filters['success']) && $filters['success'] !== '') {
            $where[] = 'success = :success';
            $params['success'] = $filters['success'];
        }

        if (!empty($filters['datefrom'])) {
            $where[] = 'timecreated >= :datefrom';
            $params['datefrom'] = $filters['datefrom'];
        }

        if (!empty($filters['dateto'])) {
            $where[] = 'timecreated <= :dateto';
            $params['dateto'] = $filters['dateto'];
        }

        if (!empty($filters['eventtype'])) {
            $where[] = 'eventtype = :eventtype';
            $params['eventtype'] = $filters['eventtype'];
        }

        $wheresql = '';
        if (!empty($where)) {
            $wheresql = 'WHERE ' . implode(' AND ', $where);
        }

        $sql = "SELECT * FROM {local_studentmapper_log}
                $wheresql
                ORDER BY timecreated DESC";

        $offset = $page * $perpage;
        return $DB->get_records_sql($sql, $params, $offset, $perpage);
    }

    /**
     * Get count of logs matching filters
     *
     * @param array $filters Filter criteria
     * @return int Count of matching records
     */
    public static function count_logs($filters = []) {
        global $DB;

        $where = [];
        $params = [];

        if (!empty($filters['userid'])) {
            $where[] = 'userid = :userid';
            $params['userid'] = $filters['userid'];
        }

        if (isset($filters['success']) && $filters['success'] !== '') {
            $where[] = 'success = :success';
            $params['success'] = $filters['success'];
        }

        if (!empty($filters['datefrom'])) {
            $where[] = 'timecreated >= :datefrom';
            $params['datefrom'] = $filters['datefrom'];
        }

        if (!empty($filters['dateto'])) {
            $where[] = 'timecreated <= :dateto';
            $params['dateto'] = $filters['dateto'];
        }

        if (!empty($filters['eventtype'])) {
            $where[] = 'eventtype = :eventtype';
            $params['eventtype'] = $filters['eventtype'];
        }

        $wheresql = '';
        if (!empty($where)) {
            $wheresql = 'WHERE ' . implode(' AND ', $where);
        }

        $sql = "SELECT COUNT(*) FROM {local_studentmapper_log} $wheresql";
        return $DB->count_records_sql($sql, $params);
    }

    /**
     * Get sync statistics for a date range
     *
     * @param int|null $datefrom Start timestamp
     * @param int|null $dateto End timestamp
     * @return array Statistics array
     */
    public static function get_statistics($datefrom = null, $dateto = null) {
        global $DB;

        $where = [];
        $params = [];

        if ($datefrom) {
            $where[] = 'timecreated >= :datefrom';
            $params['datefrom'] = $datefrom;
        }

        if ($dateto) {
            $where[] = 'timecreated <= :dateto';
            $params['dateto'] = $dateto;
        }

        $wheresql = '';
        if (!empty($where)) {
            $wheresql = 'WHERE ' . implode(' AND ', $where);
        }

        // Get success/failure counts.
        $sql = "SELECT success, COUNT(*) as count
                FROM {local_studentmapper_log}
                $wheresql
                GROUP BY success";

        $records = $DB->get_records_sql($sql, $params);

        $stats = [
            'total' => 0,
            'success' => 0,
            'failed' => 0,
            'success_rate' => 0,
        ];

        foreach ($records as $record) {
            if ($record->success == 1) {
                $stats['success'] = $record->count;
            } else {
                $stats['failed'] = $record->count;
            }
            $stats['total'] += $record->count;
        }

        // Calculate success rate.
        if ($stats['total'] > 0) {
            $stats['success_rate'] = round(($stats['success'] / $stats['total']) * 100, 2);
        }

        // Get average execution time.
        $sql = "SELECT AVG(execution_time) as avg_time
                FROM {local_studentmapper_log}
                $wheresql
                AND execution_time IS NOT NULL";

        $avgtime = $DB->get_field_sql($sql, $params);
        $stats['avg_execution_time'] = $avgtime ? round($avgtime, 2) : 0;

        return $stats;
    }

    /**
     * Cleanup old log entries
     *
     * @param int $days Number of days to retain logs
     * @return int Number of records deleted
     */
    public static function cleanup_old_logs($days = 90) {
        global $DB;

        $cutoff = time() - ($days * 86400);

        return $DB->delete_records_select(
            'local_studentmapper_log',
            'timecreated < :cutoff',
            ['cutoff' => $cutoff]
        );
    }

    /**
     * Get recent logs for dashboard display
     *
     * @param int $limit Number of logs to retrieve
     * @return array Array of recent log records
     */
    public static function get_recent_logs($limit = 20) {
        global $DB;

        $sql = "SELECT l.*, u.firstname, u.lastname, u.email
                FROM {local_studentmapper_log} l
                LEFT JOIN {user} u ON l.userid = u.id
                ORDER BY l.timecreated DESC";

        return $DB->get_records_sql($sql, null, 0, $limit);
    }

    /**
     * Get log entry by ID
     *
     * @param int $logid Log entry ID
     * @return object|false Log record or false
     */
    public static function get_log($logid) {
        global $DB;
        return $DB->get_record('local_studentmapper_log', ['id' => $logid]);
    }
}
