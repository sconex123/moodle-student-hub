<?php
namespace local_studentmapper\task;

defined('MOODLE_INTERNAL') || die();

/**
 * Scheduled task to cleanup old logs
 *
 * @package    local_studentmapper
 * @copyright  2024
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class cleanup_logs extends \core\task\scheduled_task {

    /**
     * Get a descriptive name for this task
     *
     * @return string
     */
    public function get_name() {
        return get_string('task_cleanup_logs', 'local_studentmapper');
    }

    /**
     * Execute the scheduled task
     */
    public function execute() {
        // Cleanup sync logs.
        $logretention = get_config('local_studentmapper', 'log_retention_days') ?: 90;
        $deletedlogs = \local_studentmapper\sync_logger::cleanup_old_logs($logretention);

        // Cleanup webhook logs.
        global $DB;
        $webhookretention = get_config('local_studentmapper', 'webhook_retention_days') ?: 30;
        $cutoff = time() - ($webhookretention * 86400);
        $deletedwebhooks = $DB->delete_records_select(
            'local_studentmapper_webhook',
            'timecreated < :cutoff',
            ['cutoff' => $cutoff]
        );

        // Cleanup completed queue items.
        $deletedqueue = \local_studentmapper\queue_manager::cleanup_old_items(7);

        mtrace("Student Mapper: Cleanup completed - Logs: $deletedlogs, Webhooks: $deletedwebhooks, Queue: $deletedqueue");
    }
}
