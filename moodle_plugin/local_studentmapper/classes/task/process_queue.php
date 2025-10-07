<?php
namespace local_studentmapper\task;

defined('MOODLE_INTERNAL') || die();

/**
 * Scheduled task to process the queue
 *
 * @package    local_studentmapper
 * @copyright  2024
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class process_queue extends \core\task\scheduled_task {

    /**
     * Get a descriptive name for this task
     *
     * @return string
     */
    public function get_name() {
        return get_string('task_process_queue', 'local_studentmapper');
    }

    /**
     * Execute the scheduled task
     */
    public function execute() {
        $limit = get_config('local_studentmapper', 'queue_processing_limit') ?: 100;

        // Get pending queue items.
        $items = \local_studentmapper\queue_manager::get_pending_items($limit);

        $processed = 0;
        $succeeded = 0;
        $failed = 0;

        foreach ($items as $item) {
            try {
                $success = \local_studentmapper\sync_manager::process_queue_item($item);

                if ($success) {
                    $succeeded++;
                } else {
                    $failed++;
                }

                $processed++;

            } catch (\Exception $e) {
                // Log error and mark as failed.
                $error = 'Exception processing queue item: ' . $e->getMessage();
                \local_studentmapper\queue_manager::mark_failed($item->id, $error);
                debugging($error, DEBUG_DEVELOPER);
                $failed++;
            }
        }

        if ($processed > 0) {
            mtrace("Student Mapper: Processed $processed queue items ($succeeded succeeded, $failed failed)");
        }
    }
}
