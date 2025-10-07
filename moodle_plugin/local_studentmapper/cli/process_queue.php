<?php
/**
 * CLI script to manually process the queue
 *
 * @package    local_studentmapper
 * @copyright  2024
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../../config.php');
require_once($CFG->libdir . '/clilib.php');

// Get cli options.
list($options, $unrecognized) = cli_get_params(
    [
        'help' => false,
        'limit' => 100,
        'verbose' => false,
    ],
    [
        'h' => 'help',
        'l' => 'limit',
        'v' => 'verbose',
    ]
);

if ($options['help']) {
    $help = "Manually process the Student Mapper queue.

Options:
--limit=INT         Max items to process (default: 100)
--verbose           Show detailed output
-h, --help          Print this help

Example:
\$ php process_queue.php
\$ php process_queue.php --limit=50 --verbose
";
    echo $help;
    exit(0);
}

cli_writeln("Processing Student Mapper queue...");

// Get pending items.
$items = \local_studentmapper\queue_manager::get_pending_items($options['limit']);
$total = count($items);

if ($total == 0) {
    cli_writeln("No pending items in queue");
    exit(0);
}

cli_writeln("Found $total pending items");

$processed = 0;
$succeeded = 0;
$failed = 0;

foreach ($items as $item) {
    $processed++;

    if ($options['verbose']) {
        cli_writeln("[$processed/$total] Processing queue item {$item->id} (User ID: {$item->userid}, Attempt: {$item->attempts})");
    }

    try {
        $success = \local_studentmapper\sync_manager::process_queue_item($item);

        if ($success) {
            $succeeded++;
            if ($options['verbose']) {
                cli_writeln("  ✓ Success", CLI_ANSI_FG_GREEN);
            }
        } else {
            $failed++;
            if ($options['verbose']) {
                cli_writeln("  ✗ Failed", CLI_ANSI_FG_RED);
            }
        }
    } catch (\Exception $e) {
        $failed++;
        $error = 'Exception: ' . $e->getMessage();
        \local_studentmapper\queue_manager::mark_failed($item->id, $error);
        if ($options['verbose']) {
            cli_writeln("  ✗ Exception: " . $e->getMessage(), CLI_ANSI_FG_RED);
        }
    }
}

cli_writeln("\nQueue processing complete:");
cli_writeln("  Processed: $processed", CLI_ANSI_FG_CYAN);
cli_writeln("  Succeeded: $succeeded", CLI_ANSI_FG_GREEN);
cli_writeln("  Failed: $failed", $failed > 0 ? CLI_ANSI_FG_RED : CLI_ANSI_FG_GREEN);

exit(0);
