<?php
namespace local_studentmapper;
defined('MOODLE_INTERNAL') || die();

/**
 * Event observer for user events
 *
 * @package    local_studentmapper
 * @copyright  2024
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class observer
{
    /**
     * Event observer for user creation and updates.
     *
     * @param \core\event\base $event
     */
    public static function store(\core\event\base $event)
    {
        // Ensure we handle only user events we expect.
        if (!($event instanceof \core\event\user_created) && !($event instanceof \core\event\user_updated)) {
            return;
        }

        $userid = $event->objectid;

        // Determine event type.
        if ($event instanceof \core\event\user_created) {
            $eventtype = 'user_created';
        } else {
            $eventtype = 'user_updated';
        }

        try {
            // Use sync_manager to orchestrate the sync.
            // This handles: payload building, transformations, API call, logging, queue fallback.
            sync_manager::sync_user($userid, $eventtype);

        } catch (\Exception $e) {
            // Log error but don't break the user operation.
            sync_logger::log_error($userid, 'observer', $e->getMessage());
            debugging('Student Mapper observer error: ' . $e->getMessage(), DEBUG_DEVELOPER);
        }
    }
}
