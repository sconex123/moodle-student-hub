<?php
/**
 * View individual sync log details
 *
 * @package    local_studentmapper
 * @copyright  2024
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

admin_externalpage_setup('local_studentmapper_logs');

$context = context_system::instance();
require_capability('local/studentmapper:viewlogs', $context);

$id = required_param('id', PARAM_INT);

$log = $DB->get_record('local_studentmapper_log', ['id' => $id], '*', MUST_EXIST);
$user = $DB->get_record('user', ['id' => $log->userid]);

$PAGE->set_url(new moodle_url('/local/studentmapper/view_log.php', ['id' => $id]));
$PAGE->set_title(get_string('logdetails', 'local_studentmapper'));
$PAGE->set_heading(get_string('logdetails', 'local_studentmapper'));

echo $OUTPUT->header();

// Breadcrumbs.
echo html_writer::link(new moodle_url('/local/studentmapper/dashboard.php'), get_string('dashboard', 'local_studentmapper'));
echo ' / ';
echo html_writer::link(new moodle_url('/local/studentmapper/view_logs.php'), get_string('viewlogs', 'local_studentmapper'));
echo ' / ';
echo get_string('logdetails', 'local_studentmapper');

echo $OUTPUT->heading(get_string('logdetails', 'local_studentmapper'));

// Log details.
echo html_writer::start_div('card mb-3');
echo html_writer::start_div('card-header bg-primary text-white');
echo html_writer::tag('h5', get_string('generalinformation', 'local_studentmapper'), ['class' => 'm-0']);
echo html_writer::end_div();
echo html_writer::start_div('card-body');

$table = new html_table();
$table->attributes['class'] = 'table table-bordered';
$table->data = [];

$table->data[] = [html_writer::tag('strong', get_string('id', 'local_studentmapper')), $log->id];
$table->data[] = [html_writer::tag('strong', get_string('timestamp', 'local_studentmapper')),
    userdate($log->timecreated, get_string('strftimedatetime', 'langconfig'))];
$table->data[] = [html_writer::tag('strong', get_string('user')),
    $user ? fullname($user) . ' (' . html_writer::link(new moodle_url('/user/profile.php', ['id' => $user->id]), $log->userid) . ')' : $log->userid];
$table->data[] = [html_writer::tag('strong', get_string('eventtype', 'local_studentmapper')),
    get_string('eventtype_' . $log->eventtype, 'local_studentmapper')];

$statusbadge = $log->success ?
    html_writer::span(get_string('success'), 'badge badge-success') :
    html_writer::span(get_string('failed', 'local_studentmapper'), 'badge badge-danger');
$table->data[] = [html_writer::tag('strong', get_string('status')), $statusbadge];

if (!empty($log->http_code)) {
    $httpclass = '';
    if ($log->http_code >= 200 && $log->http_code < 300) {
        $httpclass = 'text-success';
    } else if ($log->http_code >= 400) {
        $httpclass = 'text-danger';
    }
    $table->data[] = [html_writer::tag('strong', get_string('httpcode', 'local_studentmapper')),
        html_writer::span($log->http_code, $httpclass . ' font-weight-bold')];
}

if (!empty($log->execution_time)) {
    $table->data[] = [html_writer::tag('strong', get_string('executiontime', 'local_studentmapper')),
        round($log->execution_time, 2) . ' ms'];
}

if ($log->queueid) {
    $queueurl = new moodle_url('/local/studentmapper/manage_queue.php', ['action' => 'view', 'id' => $log->queueid]);
    $table->data[] = [html_writer::tag('strong', get_string('queueid', 'local_studentmapper')),
        html_writer::link($queueurl, $log->queueid)];
}

echo html_writer::table($table);
echo html_writer::end_div();
echo html_writer::end_div();

// Error message (if any).
if (!empty($log->error_message)) {
    echo html_writer::start_div('card mb-3 border-danger');
    echo html_writer::start_div('card-header bg-danger text-white');
    echo html_writer::tag('h5', get_string('errormessage', 'local_studentmapper'), ['class' => 'm-0']);
    echo html_writer::end_div();
    echo html_writer::start_div('card-body');
    echo html_writer::tag('pre', $log->error_message, ['class' => 'text-danger mb-0']);
    echo html_writer::end_div();
    echo html_writer::end_div();
}

// Payload.
echo html_writer::start_div('card mb-3');
echo html_writer::start_div('card-header bg-info text-white');
echo html_writer::tag('h5', get_string('payload', 'local_studentmapper'), ['class' => 'm-0']);
echo html_writer::end_div();
echo html_writer::start_div('card-body');
$payload = json_decode($log->payload, true);
if ($payload) {
    echo html_writer::tag('pre', json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), ['class' => 'bg-light p-3 mb-0']);
} else {
    echo html_writer::tag('p', get_string('nopayload', 'local_studentmapper'), ['class' => 'text-muted']);
}
echo html_writer::end_div();
echo html_writer::end_div();

// Response.
if (!empty($log->response)) {
    echo html_writer::start_div('card mb-3');
    echo html_writer::start_div('card-header bg-secondary text-white');
    echo html_writer::tag('h5', get_string('response', 'local_studentmapper'), ['class' => 'm-0']);
    echo html_writer::end_div();
    echo html_writer::start_div('card-body');
    $response = json_decode($log->response, true);
    if ($response) {
        echo html_writer::tag('pre', json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), ['class' => 'bg-light p-3 mb-0']);
    } else {
        // Try to display as plain text.
        echo html_writer::tag('pre', $log->response, ['class' => 'bg-light p-3 mb-0']);
    }
    echo html_writer::end_div();
    echo html_writer::end_div();
}

// Actions.
echo html_writer::start_div('mt-4');
echo html_writer::link(new moodle_url('/local/studentmapper/view_logs.php'), get_string('back'), ['class' => 'btn btn-secondary']);
if (has_capability('local/studentmapper:manualsync', $context)) {
    $resyncurl = new moodle_url('/local/studentmapper/dashboard.php', [
        'synctype' => 'single',
        'identifiertype' => 'userid',
        'identifier' => $log->userid,
    ]);
    echo ' ';
    echo html_writer::link($resyncurl, get_string('resyncuser', 'local_studentmapper'), ['class' => 'btn btn-primary']);
}
echo html_writer::end_div();

echo $OUTPUT->footer();
