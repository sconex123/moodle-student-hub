<?php
/**
 * CLI script to cleanup old logs
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
        'days' => 90,
        'dryrun' => false,
        'verbose' => false,
    ],
    [
        'h' => 'help',
        'd' => 'days',
        'n' => 'dryrun',
        'v' => 'verbose',
    ]
);

if ($options['help']) {
    $help = "Cleanup old Student Mapper logs.

Options:
--days=INT          Days to retain (default: 90)
--dryrun            Show what would be deleted without deleting
--verbose           Show detailed output
-h, --help          Print this help

Examples:
\$ php cleanup.php
\$ php cleanup.php --days=30 --verbose
\$ php cleanup.php --days=60 --dryrun
";
    echo $help;
    exit(0);
}

$days = $options['days'];
$dryrun = $options['dryrun'];

if ($dryrun) {
    cli_writeln("DRY RUN MODE - No data will be deleted", CLI_ANSI_FG_YELLOW);
}

cli_writeln("Cleaning up logs older than $days days...");

$cutoff = time() - ($days * 86400);
$cutoffdate = userdate($cutoff);

if ($options['verbose']) {
    cli_writeln("Cutoff date: $cutoffdate");
}

// Count records to be deleted.
$logscount = $DB->count_records_select('local_studentmapper_log', 'timecreated < ?', [$cutoff]);
$webhookscount = $DB->count_records_select('local_studentmapper_webhook', 'timecreated < ?', [$cutoff]);
$queuecount = $DB->count_records_select('local_studentmapper_queue',
    'status = ? AND timemodified < ?',
    ['completed', $cutoff]);

cli_writeln("\nRecords to be deleted:");
cli_writeln("  Sync logs: $logscount");
cli_writeln("  Webhook logs: $webhookscount");
cli_writeln("  Completed queue items: $queuecount");

if (!$dryrun) {
    cli_writeln("\nDeleting...");

    // Delete sync logs.
    $deleted = \local_studentmapper\sync_logger::cleanup_old_logs($days);
    cli_writeln("  ✓ Deleted $deleted sync logs", CLI_ANSI_FG_GREEN);

    // Delete webhook logs.
    $deleted = $DB->delete_records_select('local_studentmapper_webhook', 'timecreated < ?', [$cutoff]);
    cli_writeln("  ✓ Deleted $deleted webhook logs", CLI_ANSI_FG_GREEN);

    // Delete completed queue items.
    $deleted = \local_studentmapper\queue_manager::cleanup_old_items($days);
    cli_writeln("  ✓ Deleted $deleted queue items", CLI_ANSI_FG_GREEN);

    cli_writeln("\nCleanup complete!", CLI_ANSI_FG_GREEN);
} else {
    cli_writeln("\nDry run complete - no data was deleted", CLI_ANSI_FG_YELLOW);
}

exit(0);
