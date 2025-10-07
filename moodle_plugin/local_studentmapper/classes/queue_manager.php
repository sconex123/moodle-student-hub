<?php
namespace local_studentmapper;

defined('MOODLE_INTERNAL') || die();

/**
 * Queue manager for handling failed sync retries
 *
 * @package    local_studentmapper
 * @copyright  2024
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class queue_manager {
    /** @var string Queue status constants */
    const STATUS_PENDING = 'pending';
    const STATUS_PROCESSING = 'processing';
    const STATUS_FAILED = 'failed';
    const STATUS_COMPLETED = 'completed';

    /**
     * Add a failed sync to the queue for retry
     *
     * @param int $userid Moodle user ID
     * @param array $payload JSON payload data
     * @param string $eventtype Event type (user_created, user_updated, manual)
     * @param string|null $error Error message
     * @return int Queue entry ID
     */
    public static function add_to_queue($userid, $payload, $eventtype, $error = null) {
        global $DB;

        $maxattempts = get_config('local_studentmapper', 'max_queue_attempts') ?: 5;
        $now = time();

        $record = new \stdClass();
        $record->userid = $userid;
        $record->payload = json_encode($payload);
        $record->eventtype = $eventtype;
        $record->attempts = 0;
        $record->max_attempts = $maxattempts;
        $record->next_retry = $now + 300; // First retry in 5 minutes.
        $record->last_error = $error;
        $record->status = self::STATUS_PENDING;
        $record->timecreated = $now;
        $record->timemodified = $now;

        return $DB->insert_record('local_studentmapper_queue', $record);
    }

    /**
     * Get pending queue items ready for retry
     *
     * @param int $limit Maximum number of items to fetch
     * @return array Array of queue records
     */
    public static function get_pending_items($limit = 100) {
        global $DB;

        $now = time();
        $sql = "SELECT * FROM {local_studentmapper_queue}
                WHERE status = :status
                  AND next_retry <= :now
                ORDER BY next_retry ASC";

        $params = [
            'status' => self::STATUS_PENDING,
            'now' => $now,
        ];

        return $DB->get_records_sql($sql, $params, 0, $limit);
    }

    /**
     * Mark a queue item as processing
     *
     * @param int $queueid Queue entry ID
     * @return bool Success status
     */
    public static function mark_processing($queueid) {
        global $DB;

        $record = new \stdClass();
        $record->id = $queueid;
        $record->status = self::STATUS_PROCESSING;
        $record->timemodified = time();

        return $DB->update_record('local_studentmapper_queue', $record);
    }

    /**
     * Mark a queue item as completed (successful retry)
     *
     * @param int $queueid Queue entry ID
     * @return bool Success status
     */
    public static function mark_completed($queueid) {
        global $DB;

        $record = new \stdClass();
        $record->id = $queueid;
        $record->status = self::STATUS_COMPLETED;
        $record->timemodified = time();

        return $DB->update_record('local_studentmapper_queue', $record);
    }

    /**
     * Mark a queue item as failed (update retry info)
     *
     * @param int $queueid Queue entry ID
     * @param string $error Error message
     * @return bool Success status
     */
    public static function mark_failed($queueid, $error) {
        global $DB;

        $item = $DB->get_record('local_studentmapper_queue', ['id' => $queueid], '*', MUST_EXIST);
        $item->attempts++;
        $item->last_error = $error;
        $item->timemodified = time();

        // Check if max attempts reached.
        if ($item->attempts >= $item->max_attempts) {
            $item->status = self::STATUS_FAILED;
        } else {
            $item->status = self::STATUS_PENDING;
            $item->next_retry = self::calculate_next_retry($item->attempts);
        }

        return $DB->update_record('local_studentmapper_queue', $item);
    }

    /**
     * Calculate next retry timestamp using exponential backoff
     *
     * @param int $attempts Number of previous attempts
     * @return int Timestamp for next retry
     */
    public static function calculate_next_retry($attempts) {
        $basedelay = 300; // 5 minutes.
        $multiplier = get_config('local_studentmapper', 'queue_backoff_multiplier') ?: 2;

        // Exponential backoff: 5min, 15min (5*3), 45min (5*9), 2h15min (5*27), 6h45min (5*81).
        $delay = $basedelay * pow($multiplier, $attempts);

        // Cap at 24 hours.
        $maxdelay = 86400;
        $delay = min($delay, $maxdelay);

        return time() + $delay;
    }

    /**
     * Cleanup old completed queue items
     *
     * @param int $days Number of days to retain completed items
     * @return int Number of records deleted
     */
    public static function cleanup_old_items($days = 7) {
        global $DB;

        $cutoff = time() - ($days * 86400);

        return $DB->delete_records_select(
            'local_studentmapper_queue',
            'status = :status AND timemodified < :cutoff',
            ['status' => self::STATUS_COMPLETED, 'cutoff' => $cutoff]
        );
    }

    /**
     * Get queue item by ID
     *
     * @param int $queueid Queue entry ID
     * @return object|false Queue record or false
     */
    public static function get_item($queueid) {
        global $DB;
        return $DB->get_record('local_studentmapper_queue', ['id' => $queueid]);
    }

    /**
     * Delete a queue item
     *
     * @param int $queueid Queue entry ID
     * @return bool Success status
     */
    public static function delete_item($queueid) {
        global $DB;
        return $DB->delete_records('local_studentmapper_queue', ['id' => $queueid]);
    }

    /**
     * Get queue statistics
     *
     * @return array Statistics array with counts by status
     */
    public static function get_statistics() {
        global $DB;

        $sql = "SELECT status, COUNT(*) as count
                FROM {local_studentmapper_queue}
                GROUP BY status";

        $records = $DB->get_records_sql($sql);
        $stats = [
            'pending' => 0,
            'processing' => 0,
            'failed' => 0,
            'completed' => 0,
            'total' => 0,
        ];

        foreach ($records as $record) {
            $stats[$record->status] = $record->count;
            $stats['total'] += $record->count;
        }

        return $stats;
    }

    /**
     * Retry a specific queue item immediately
     *
     * @param int $queueid Queue entry ID
     * @return bool Success status
     */
    public static function retry_now($queueid) {
        global $DB;

        $record = new \stdClass();
        $record->id = $queueid;
        $record->status = self::STATUS_PENDING;
        $record->next_retry = time(); // Retry immediately.
        $record->timemodified = time();

        return $DB->update_record('local_studentmapper_queue', $record);
    }
}
