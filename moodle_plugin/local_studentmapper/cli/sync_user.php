<?php
/**
 * CLI script to sync a single user
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
        'userid' => null,
        'username' => null,
        'email' => null,
        'force' => false,
        'verbose' => false,
    ],
    [
        'h' => 'help',
        'u' => 'userid',
        'n' => 'username',
        'e' => 'email',
        'f' => 'force',
        'v' => 'verbose',
    ]
);

if ($options['help'] || (!$options['userid'] && !$options['username'] && !$options['email'])) {
    $help = "Sync a single user to external system.

Options:
--userid=INT        User ID
--username=STRING   Username
--email=STRING      Email address
--force             Force sync even if recently synced
--verbose           Show detailed output
-h, --help          Print this help

Example:
\$ php sync_user.php --userid=123
\$ php sync_user.php --username=john.doe --verbose
\$ php sync_user.php --email=john@example.com --force
";
    echo $help;
    exit(0);
}

// Find user.
$user = null;
if ($options['userid']) {
    $user = $DB->get_record('user', ['id' => $options['userid']]);
} else if ($options['username']) {
    $user = $DB->get_record('user', ['username' => $options['username']]);
} else if ($options['email']) {
    $user = $DB->get_record('user', ['email' => $options['email']]);
}

if (!$user) {
    cli_error('User not found');
}

cli_writeln("Syncing user: {$user->firstname} {$user->lastname} (ID: {$user->id})");

// Perform sync.
$result = \local_studentmapper\sync_manager::sync_user($user->id, 'manual');

if ($result['success']) {
    cli_writeln("✓ Sync successful", CLI_ANSI_FG_GREEN);
    if ($options['verbose'] && isset($result['http_code'])) {
        cli_writeln("  HTTP Code: {$result['http_code']}");
        cli_writeln("  Execution Time: {$result['execution_time']}ms");
    }
    exit(0);
} else {
    cli_writeln("✗ Sync failed: {$result['error']}", CLI_ANSI_FG_RED);
    if ($options['verbose'] && isset($result['http_code'])) {
        cli_writeln("  HTTP Code: {$result['http_code']}");
    }
    exit(1);
}
