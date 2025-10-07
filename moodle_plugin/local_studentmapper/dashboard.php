<?php
/**
 * Student Mapper Dashboard
 *
 * @package    local_studentmapper
 * @copyright  2024
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

admin_externalpage_setup('local_studentmapper_dashboard');

$context = context_system::instance();
require_capability('local/studentmapper:viewdashboard', $context);

$PAGE->set_url(new moodle_url('/local/studentmapper/dashboard.php'));
$PAGE->set_title(get_string('dashboard', 'local_studentmapper'));
$PAGE->set_heading(get_string('dashboard', 'local_studentmapper'));

// Get filter parameters.
$daterange = optional_param('daterange', '7', PARAM_INT); // Days.
$refresh = optional_param('refresh', 0, PARAM_INT);

// Calculate date range.
$datefrom = strtotime("-{$daterange} days");
$dateto = time();

// Get statistics.
$stats = \local_studentmapper\sync_logger::get_statistics($datefrom, $dateto);

// Get queue statistics.
$queuestats = \local_studentmapper\queue_manager::get_statistics();

// Prepare data for template.
$data = new stdClass();
$data->stats = $stats;
$data->queuestats = $queuestats;
$data->daterange = $daterange;
$data->lastupdate = userdate(time(), get_string('strftimedatetime', 'langconfig'));

// Date range options.
$data->daterangeoptions = [
    ['value' => 1, 'label' => get_string('last24hours', 'local_studentmapper'), 'selected' => $daterange == 1],
    ['value' => 7, 'label' => get_string('last7days', 'local_studentmapper'), 'selected' => $daterange == 7],
    ['value' => 30, 'label' => get_string('last30days', 'local_studentmapper'), 'selected' => $daterange == 30],
    ['value' => 90, 'label' => get_string('last90days', 'local_studentmapper'), 'selected' => $daterange == 90],
];

// Recent logs (last 20).
$logs = $DB->get_records_sql("
    SELECT l.*, u.firstname, u.lastname, u.username
    FROM {local_studentmapper_log} l
    LEFT JOIN {user} u ON l.userid = u.id
    ORDER BY l.timecreated DESC
    LIMIT 20
");

$data->recentlogs = array_values(array_map(function($log) {
    return [
        'id' => $log->id,
        'userid' => $log->userid,
        'username' => fullname($log),
        'eventtype' => get_string('eventtype_' . $log->eventtype, 'local_studentmapper'),
        'success' => $log->success,
        'successtext' => $log->success ? get_string('success') : get_string('failed', 'local_studentmapper'),
        'httpcode' => $log->http_code,
        'executiontime' => round($log->execution_time, 2),
        'timecreated' => userdate($log->timecreated, get_string('strftimetime', 'langconfig')),
        'errormessage' => !empty($log->error_message) ? \core_text::substr($log->error_message, 0, 50) : '',
    ];
}, $logs));

// Pending queue items (up to 10).
$queueitems = $DB->get_records_sql("
    SELECT q.*, u.firstname, u.lastname, u.username
    FROM {local_studentmapper_queue} q
    LEFT JOIN {user} u ON q.userid = u.id
    WHERE q.status IN ('pending', 'processing')
    ORDER BY q.next_retry ASC
    LIMIT 10
");

$data->queueitems = array_values(array_map(function($item) {
    return [
        'id' => $item->id,
        'userid' => $item->userid,
        'username' => fullname($item),
        'eventtype' => get_string('eventtype_' . $item->eventtype, 'local_studentmapper'),
        'status' => $item->status,
        'statustext' => get_string('status_' . $item->status, 'local_studentmapper'),
        'attempts' => $item->attempts . ' / ' . $item->max_attempts,
        'nextretry' => $item->next_retry <= time() ? get_string('ready', 'local_studentmapper') : userdate($item->next_retry, get_string('strftimetime', 'langconfig')),
        'canretry' => in_array($item->status, ['pending', 'failed']),
    ];
}, $queueitems));

// Manual sync form.
require_once($CFG->dirroot . '/local/studentmapper/classes/form/manual_sync_form.php');
$mform = new \local_studentmapper\form\manual_sync_form();

if ($mform->is_cancelled()) {
    redirect($PAGE->url);
} else if ($formdata = $mform->get_data()) {
    // Process manual sync.
    $result = process_manual_sync($formdata);
    if ($result['success']) {
        \core\notification::success($result['message']);
    } else {
        \core\notification::error($result['message']);
    }
    redirect($PAGE->url);
}

echo $OUTPUT->header();

// Statistics cards.
echo html_writer::start_div('row mb-4');

// Total syncs card.
echo html_writer::start_div('col-md-3');
echo html_writer::start_div('card');
echo html_writer::start_div('card-body text-center');
echo html_writer::tag('h5', get_string('totalsyncs', 'local_studentmapper'), ['class' => 'card-title']);
echo html_writer::tag('p', $stats['total_syncs'], ['class' => 'display-4 text-primary']);
echo html_writer::end_div();
echo html_writer::end_div();
echo html_writer::end_div();

// Success rate card.
echo html_writer::start_div('col-md-3');
echo html_writer::start_div('card');
echo html_writer::start_div('card-body text-center');
echo html_writer::tag('h5', get_string('successrate', 'local_studentmapper'), ['class' => 'card-title']);
$successrateclass = $stats['success_rate'] >= 95 ? 'text-success' : ($stats['success_rate'] >= 80 ? 'text-warning' : 'text-danger');
echo html_writer::tag('p', round($stats['success_rate'], 1) . '%', ['class' => 'display-4 ' . $successrateclass]);
echo html_writer::end_div();
echo html_writer::end_div();
echo html_writer::end_div();

// Queue size card.
echo html_writer::start_div('col-md-3');
echo html_writer::start_div('card');
echo html_writer::start_div('card-body text-center');
echo html_writer::tag('h5', get_string('queuesize', 'local_studentmapper'), ['class' => 'card-title']);
$queueclass = $queuestats['pending'] > 100 ? 'text-danger' : ($queuestats['pending'] > 10 ? 'text-warning' : 'text-success');
echo html_writer::tag('p', $queuestats['pending'], ['class' => 'display-4 ' . $queueclass]);
echo html_writer::end_div();
echo html_writer::end_div();
echo html_writer::end_div();

// Failed syncs card.
echo html_writer::start_div('col-md-3');
echo html_writer::start_div('card');
echo html_writer::start_div('card-body text-center');
echo html_writer::tag('h5', get_string('failedsyncs', 'local_studentmapper'), ['class' => 'card-title']);
$failedclass = $queuestats['failed'] > 0 ? 'text-danger' : 'text-success';
echo html_writer::tag('p', $queuestats['failed'], ['class' => 'display-4 ' . $failedclass]);
echo html_writer::end_div();
echo html_writer::end_div();
echo html_writer::end_div();

echo html_writer::end_div(); // End row.

// Date range filter.
echo html_writer::start_div('mb-3');
echo html_writer::tag('label', get_string('daterange', 'local_studentmapper') . ': ', ['class' => 'mr-2']);
$select = new single_select($PAGE->url, 'daterange', [
    1 => get_string('last24hours', 'local_studentmapper'),
    7 => get_string('last7days', 'local_studentmapper'),
    30 => get_string('last30days', 'local_studentmapper'),
    90 => get_string('last90days', 'local_studentmapper'),
], $daterange);
echo $OUTPUT->render($select);
echo html_writer::span(get_string('lastupdated', 'local_studentmapper') . ': ' . $data->lastupdate, 'ml-3 text-muted');
echo html_writer::link(new moodle_url($PAGE->url, ['refresh' => time()]), get_string('refresh'), ['class' => 'btn btn-sm btn-secondary ml-2']);
echo html_writer::end_div();

// Recent logs section.
echo html_writer::tag('h3', get_string('recentlogs', 'local_studentmapper'), ['class' => 'mt-4']);
if (!empty($data->recentlogs)) {
    echo html_writer::start_tag('table', ['class' => 'table table-striped table-sm']);
    echo html_writer::start_tag('thead');
    echo html_writer::start_tag('tr');
    echo html_writer::tag('th', get_string('time'));
    echo html_writer::tag('th', get_string('user'));
    echo html_writer::tag('th', get_string('eventtype', 'local_studentmapper'));
    echo html_writer::tag('th', get_string('status'));
    echo html_writer::tag('th', get_string('httpcode', 'local_studentmapper'));
    echo html_writer::tag('th', get_string('executiontime', 'local_studentmapper'));
    echo html_writer::end_tag('tr');
    echo html_writer::end_tag('thead');
    echo html_writer::start_tag('tbody');
    foreach ($data->recentlogs as $log) {
        echo html_writer::start_tag('tr');
        echo html_writer::tag('td', $log['timecreated']);
        echo html_writer::tag('td', html_writer::link(new moodle_url('/user/profile.php', ['id' => $log['userid']]), $log['username']));
        echo html_writer::tag('td', $log['eventtype']);
        $statusclass = $log['success'] ? 'badge-success' : 'badge-danger';
        echo html_writer::tag('td', html_writer::span($log['successtext'], 'badge ' . $statusclass));
        echo html_writer::tag('td', $log['httpcode']);
        echo html_writer::tag('td', $log['executiontime'] . ' ms');
        echo html_writer::end_tag('tr');
    }
    echo html_writer::end_tag('tbody');
    echo html_writer::end_tag('table');
    echo html_writer::link(new moodle_url('/local/studentmapper/view_logs.php'), get_string('viewalllogs', 'local_studentmapper'), ['class' => 'btn btn-secondary']);
} else {
    echo html_writer::div(get_string('nologs', 'local_studentmapper'), 'alert alert-info');
}

// Queue items section.
echo html_writer::tag('h3', get_string('queueitems', 'local_studentmapper'), ['class' => 'mt-4']);
if (!empty($data->queueitems)) {
    echo html_writer::start_tag('table', ['class' => 'table table-striped table-sm']);
    echo html_writer::start_tag('thead');
    echo html_writer::start_tag('tr');
    echo html_writer::tag('th', get_string('user'));
    echo html_writer::tag('th', get_string('eventtype', 'local_studentmapper'));
    echo html_writer::tag('th', get_string('status'));
    echo html_writer::tag('th', get_string('attempts', 'local_studentmapper'));
    echo html_writer::tag('th', get_string('nextretry', 'local_studentmapper'));
    echo html_writer::tag('th', get_string('actions'));
    echo html_writer::end_tag('tr');
    echo html_writer::end_tag('thead');
    echo html_writer::start_tag('tbody');
    foreach ($data->queueitems as $item) {
        echo html_writer::start_tag('tr');
        echo html_writer::tag('td', html_writer::link(new moodle_url('/user/profile.php', ['id' => $item['userid']]), $item['username']));
        echo html_writer::tag('td', $item['eventtype']);
        echo html_writer::tag('td', html_writer::span($item['statustext'], 'badge badge-warning'));
        echo html_writer::tag('td', $item['attempts']);
        echo html_writer::tag('td', $item['nextretry']);
        $actions = '';
        if ($item['canretry']) {
            $retryurl = new moodle_url('/local/studentmapper/manage_queue.php', ['action' => 'retry', 'id' => $item['id'], 'sesskey' => sesskey()]);
            $actions = html_writer::link($retryurl, get_string('retry', 'local_studentmapper'), ['class' => 'btn btn-sm btn-primary']);
        }
        echo html_writer::tag('td', $actions);
        echo html_writer::end_tag('tr');
    }
    echo html_writer::end_tag('tbody');
    echo html_writer::end_tag('table');
    echo html_writer::link(new moodle_url('/local/studentmapper/manage_queue.php'), get_string('managequeue', 'local_studentmapper'), ['class' => 'btn btn-secondary']);
} else {
    echo html_writer::div(get_string('noqueueitems', 'local_studentmapper'), 'alert alert-success');
}

// Manual sync form.
if (has_capability('local/studentmapper:manualsync', $context)) {
    echo html_writer::tag('h3', get_string('manualsync', 'local_studentmapper'), ['class' => 'mt-4']);
    $mform->display();
}

echo $OUTPUT->footer();

/**
 * Process manual sync form submission
 * @param stdClass $formdata
 * @return array
 */
function process_manual_sync($formdata) {
    global $DB;

    $userids = [];

    try {
        switch ($formdata->synctype) {
            case 'single':
                // Find user by identifier.
                $field = $formdata->identifiertype === 'userid' ? 'id' : $formdata->identifiertype;
                $user = $DB->get_record('user', [$field => $formdata->identifier]);
                if (!$user) {
                    return ['success' => false, 'message' => get_string('usernotfound', 'local_studentmapper')];
                }
                $userids = [$user->id];
                break;

            case 'bulk':
                // Parse user IDs.
                $userids = preg_split('/[\s,]+/', trim($formdata->userids), -1, PREG_SPLIT_NO_EMPTY);
                $userids = array_map('intval', $userids);
                break;

            case 'cohort':
                // Get users in cohort.
                $sql = "SELECT u.id FROM {user} u
                        JOIN {cohort_members} cm ON u.id = cm.userid
                        WHERE cm.cohortid = :cohortid AND u.deleted = 0 AND u.suspended = 0
                        ORDER BY u.id ASC";
                $users = $DB->get_records_sql($sql, ['cohortid' => $formdata->cohortid], 0, $formdata->limit);
                $userids = array_keys($users);
                break;

            case 'role':
                // Get users with role.
                $sql = "SELECT DISTINCT u.id FROM {user} u
                        JOIN {role_assignments} ra ON u.id = ra.userid
                        WHERE ra.roleid = :roleid AND u.deleted = 0 AND u.suspended = 0
                        ORDER BY u.id ASC";
                $users = $DB->get_records_sql($sql, ['roleid' => $formdata->roleid], 0, $formdata->limit);
                $userids = array_keys($users);
                break;
        }

        if (empty($userids)) {
            return ['success' => false, 'message' => get_string('nouserstosync', 'local_studentmapper')];
        }

        // Sync users.
        $success = 0;
        $failed = 0;

        foreach ($userids as $userid) {
            if (!empty($formdata->queue)) {
                // Add to queue.
                $user = $DB->get_record('user', ['id' => $userid]);
                if ($user) {
                    require_once(__DIR__ . '/../../user/profile/lib.php');
                    profile_load_data($user);
                    $payload = \local_studentmapper\sync_manager::build_payload($user);
                    \local_studentmapper\queue_manager::add_to_queue($userid, $payload, 'manual');
                    $success++;
                }
            } else {
                // Immediate sync.
                $result = \local_studentmapper\sync_manager::sync_user($userid, 'manual');
                if ($result['success']) {
                    $success++;
                } else {
                    $failed++;
                }
            }
        }

        if (!empty($formdata->queue)) {
            return ['success' => true, 'message' => get_string('usersqueuedsuccess', 'local_studentmapper', $success)];
        } else {
            return ['success' => true, 'message' => get_string('syncresult', 'local_studentmapper', ['success' => $success, 'failed' => $failed])];
        }

    } catch (Exception $e) {
        return ['success' => false, 'message' => get_string('syncerror', 'local_studentmapper') . ': ' . $e->getMessage()];
    }
}
