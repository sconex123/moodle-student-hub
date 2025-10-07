<?php
defined('MOODLE_INTERNAL') || die();

/**
 * Scheduled tasks for local_studentmapper plugin
 *
 * @package    local_studentmapper
 * @copyright  2024
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$tasks = [
    [
        'classname' => 'local_studentmapper\task\process_queue',
        'blocking' => 0,
        'minute' => '*/5',  // Every 5 minutes.
        'hour' => '*',
        'day' => '*',
        'month' => '*',
        'dayofweek' => '*',
    ],
    [
        'classname' => 'local_studentmapper\task\cleanup_logs',
        'blocking' => 0,
        'minute' => '0',
        'hour' => '2',      // 2 AM daily.
        'day' => '*',
        'month' => '*',
        'dayofweek' => '*',
    ],
];
