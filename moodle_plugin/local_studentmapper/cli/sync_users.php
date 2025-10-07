<?php
/**
 * CLI script to sync multiple users
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
        'all' => false,
        'cohort' => null,
        'role' => null,
        'file' => null,
        'limit' => 1000,
        'delay' => 100,
        'queue' => false,
        'progress' => false,
    ],
    [
        'h' => 'help',
        'a' => 'all',
        'c' => 'cohort',
        'r' => 'role',
        'f' => 'file',
        'l' => 'limit',
        'd' => 'delay',
        'q' => 'queue',
        'p' => 'progress',
    ]
);

if ($options['help']) {
    $help = "Sync multiple users to external system.

Options:
--all               Sync all active users
--cohort=ID         Sync users in cohort
--role=ID           Sync users with role
--file=PATH         Sync users from file (one ID per line)
--limit=INT         Max users to sync (default: 1000)
--delay=INT         Delay between syncs in ms (default: 100)
--queue             Add to queue instead of immediate sync
--progress          Show progress bar
-h, --help          Print this help

Examples:
\$ php sync_users.php --all --limit=500
\$ php sync_users.php --cohort=5 --progress
\$ php sync_users.php --role=5 --queue
\$ php sync_users.php --file=/tmp/userids.txt --delay=200
";
    echo $help;
    exit(0);
}

$userids = [];

// Get user IDs based on criteria.
if ($options['all']) {
    cli_writeln("Fetching all active users...");
    $users = $DB->get_records_select('user', 'deleted = 0 AND suspended = 0', null, 'id ASC', 'id', 0, $options['limit']);
    $userids = array_keys($users);
} else if ($options['cohort']) {
    cli_writeln("Fetching users in cohort {$options['cohort']}...");
    $sql = "SELECT u.id FROM {user} u
            JOIN {cohort_members} cm ON u.id = cm.userid
            WHERE u.deleted = 0 AND u.suspended = 0 AND cm.cohortid = :cohortid
            ORDER BY u.id ASC";
    $users = $DB->get_records_sql($sql, ['cohortid' => $options['cohort']], 0, $options['limit']);
    $userids = array_keys($users);
} else if ($options['role']) {
    cli_writeln("Fetching users with role {$options['role']}...");
    $sql = "SELECT DISTINCT u.id FROM {user} u
            JOIN {role_assignments} ra ON u.id = ra.userid
            WHERE u.deleted = 0 AND u.suspended = 0 AND ra.roleid = :roleid
            ORDER BY u.id ASC";
    $users = $DB->get_records_sql($sql, ['roleid' => $options['role']], 0, $options['limit']);
    $userids = array_keys($users);
} else if ($options['file']) {
    cli_writeln("Reading user IDs from file...");
    if (!file_exists($options['file'])) {
        cli_error("File not found: {$options['file']}");
    }
    $lines = file($options['file'], FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $userids = array_map('intval', $lines);
    $userids = array_slice($userids, 0, $options['limit']);
} else {
    cli_error("Must specify one of: --all, --cohort, --role, or --file");
}

$total = count($userids);
cli_writeln("Found $total users to sync");

if ($total == 0) {
    cli_writeln("No users to sync");
    exit(0);
}

// Sync users.
if ($options['queue']) {
    cli_writeln("Adding users to queue...");
    $queued = 0;
    foreach ($userids as $userid) {
        $user = $DB->get_record('user', ['id' => $userid]);
        if ($user) {
            require_once($CFG->dirroot . '/user/profile/lib.php');
            profile_load_data($user);
            $payload = \local_studentmapper\sync_manager::build_payload($user);
            \local_studentmapper\queue_manager::add_to_queue($userid, $payload, 'manual');
            $queued++;
        }
        if ($options['progress'] && $queued % 10 == 0) {
            cli_write(".");
        }
    }
    cli_writeln("\n✓ $queued users added to queue", CLI_ANSI_FG_GREEN);
} else {
    cli_writeln("Syncing users...");
    $success = 0;
    $failed = 0;

    foreach ($userids as $index => $userid) {
        $result = \local_studentmapper\sync_manager::sync_user($userid, 'manual');
        if ($result['success']) {
            $success++;
        } else {
            $failed++;
        }

        if ($options['progress']) {
            $percent = round((($index + 1) / $total) * 100);
            cli_write("\rProgress: $percent% ($success success, $failed failed)");
        }

        if ($options['delay'] > 0) {
            usleep($options['delay'] * 1000);
        }
    }

    cli_writeln("\n\nSync complete:");
    cli_writeln("  Total: $total", CLI_ANSI_FG_CYAN);
    cli_writeln("  Success: $success", CLI_ANSI_FG_GREEN);
    cli_writeln("  Failed: $failed", $failed > 0 ? CLI_ANSI_FG_RED : CLI_ANSI_FG_GREEN);
}

exit(0);
