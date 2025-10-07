<?php
/**
 * Queue management page
 *
 * @package    local_studentmapper
 * @copyright  2024
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

admin_externalpage_setup('local_studentmapper_queue');

$context = context_system::instance();
require_capability('local/studentmapper:managequeue', $context);

$PAGE->set_url(new moodle_url('/local/studentmapper/manage_queue.php'));
$PAGE->set_title(get_string('managequeue', 'local_studentmapper'));
$PAGE->set_heading(get_string('managequeue', 'local_studentmapper'));

// Handle actions.
$action = optional_param('action', '', PARAM_ALPHA);
$id = optional_param('id', 0, PARAM_INT);

if ($action && confirm_sesskey()) {
    switch ($action) {
        case 'retry':
            if ($id) {
                $item = $DB->get_record('local_studentmapper_queue', ['id' => $id], '*', MUST_EXIST);
                \local_studentmapper\queue_manager::retry_now($id);
                \core\notification::success(get_string('queueitemretried', 'local_studentmapper'));
            }
            redirect($PAGE->url);
            break;

        case 'delete':
            if ($id) {
                $DB->delete_records('local_studentmapper_queue', ['id' => $id]);
                \core\notification::success(get_string('queueitemdeleted', 'local_studentmapper'));
            }
            redirect($PAGE->url);
            break;

        case 'retryall':
            $pending = $DB->get_records('local_studentmapper_queue', ['status' => 'failed']);
            foreach ($pending as $item) {
                \local_studentmapper\queue_manager::retry_now($item->id);
            }
            \core\notification::success(get_string('allitemsretried', 'local_studentmapper', count($pending)));
            redirect($PAGE->url);
            break;

        case 'deleteall':
            $count = $DB->count_records('local_studentmapper_queue', ['status' => 'failed']);
            $DB->delete_records('local_studentmapper_queue', ['status' => 'failed']);
            \core\notification::success(get_string('allitemsdeleted', 'local_studentmapper', $count));
            redirect($PAGE->url);
            break;
    }
}

// Get filter parameters.
$status = optional_param('status', '', PARAM_ALPHA);
$eventtype = optional_param('eventtype', '', PARAM_ALPHA);

echo $OUTPUT->header();

// Page heading.
echo $OUTPUT->heading(get_string('managequeue', 'local_studentmapper'));

// Filters.
echo html_writer::start_div('mb-3');
echo html_writer::tag('h4', get_string('filters'));

// Status filter.
$statusoptions = [
    '' => get_string('all'),
    'pending' => get_string('status_pending', 'local_studentmapper'),
    'processing' => get_string('status_processing', 'local_studentmapper'),
    'completed' => get_string('status_completed', 'local_studentmapper'),
    'failed' => get_string('status_failed', 'local_studentmapper'),
];
$select = new single_select(new moodle_url($PAGE->url, ['eventtype' => $eventtype]), 'status', $statusoptions, $status, null);
$select->set_label(get_string('status') . ': ');
echo $OUTPUT->render($select);

// Event type filter.
$eventtypeoptions = [
    '' => get_string('all'),
    'user_created' => get_string('eventtype_user_created', 'local_studentmapper'),
    'user_updated' => get_string('eventtype_user_updated', 'local_studentmapper'),
    'manual' => get_string('eventtype_manual', 'local_studentmapper'),
];
$select = new single_select(new moodle_url($PAGE->url, ['status' => $status]), 'eventtype', $eventtypeoptions, $eventtype, null);
$select->set_label(get_string('eventtype', 'local_studentmapper') . ': ');
echo $OUTPUT->render($select);

echo html_writer::end_div();

// Bulk actions.
if (!empty($status) && $status === 'failed') {
    echo html_writer::start_div('mb-3');
    $retryallurl = new moodle_url($PAGE->url, ['action' => 'retryall', 'sesskey' => sesskey()]);
    echo html_writer::link($retryallurl, get_string('retryall', 'local_studentmapper'), [
        'class' => 'btn btn-primary mr-2',
    ]);
    $deleteallurl = new moodle_url($PAGE->url, ['action' => 'deleteall', 'sesskey' => sesskey()]);
    echo html_writer::link($deleteallurl, get_string('deleteall'), [
        'class' => 'btn btn-danger',
        'onclick' => 'return confirm("' . get_string('confirmdeleteall', 'local_studentmapper') . '");',
    ]);
    echo html_writer::end_div();
}

// Queue table.
$table = new \local_studentmapper\table\queue_table('local_studentmapper_queue');
$table->define_baseurl($PAGE->url);
$table->apply_filters($status, $eventtype);

ob_start();
$table->out(20, false);
$tablehtml = ob_get_clean();

if (empty($tablehtml) || strpos($tablehtml, 'Nothing to display') !== false) {
    echo html_writer::div(get_string('noqueueitems', 'local_studentmapper'), 'alert alert-success');
} else {
    echo $tablehtml;
}

// View details modal handling.
if ($action === 'view' && $id) {
    $item = $DB->get_record('local_studentmapper_queue', ['id' => $id], '*', MUST_EXIST);
    $user = $DB->get_record('user', ['id' => $item->userid]);

    echo html_writer::start_div('modal fade show', ['style' => 'display: block;']);
    echo html_writer::start_div('modal-dialog modal-lg');
    echo html_writer::start_div('modal-content');

    // Header.
    echo html_writer::start_div('modal-header');
    echo html_writer::tag('h5', get_string('queueitemdetails', 'local_studentmapper'), ['class' => 'modal-title']);
    echo html_writer::link($PAGE->url, '×', ['class' => 'close']);
    echo html_writer::end_div();

    // Body.
    echo html_writer::start_div('modal-body');
    echo html_writer::tag('p', html_writer::tag('strong', get_string('id', 'local_studentmapper') . ': ') . $item->id);
    echo html_writer::tag('p', html_writer::tag('strong', get_string('user') . ': ') . fullname($user) . ' (' . $item->userid . ')');
    echo html_writer::tag('p', html_writer::tag('strong', get_string('eventtype', 'local_studentmapper') . ': ') . get_string('eventtype_' . $item->eventtype, 'local_studentmapper'));
    echo html_writer::tag('p', html_writer::tag('strong', get_string('status') . ': ') . get_string('status_' . $item->status, 'local_studentmapper'));
    echo html_writer::tag('p', html_writer::tag('strong', get_string('attempts', 'local_studentmapper') . ': ') . $item->attempts . ' / ' . $item->max_attempts);
    echo html_writer::tag('p', html_writer::tag('strong', get_string('timecreated', 'local_studentmapper') . ': ') . userdate($item->timecreated));
    echo html_writer::tag('p', html_writer::tag('strong', get_string('timemodified', 'local_studentmapper') . ': ') . userdate($item->timemodified));

    if (!empty($item->last_error)) {
        echo html_writer::tag('p', html_writer::tag('strong', get_string('lasterror', 'local_studentmapper') . ': '), ['class' => 'text-danger']);
        echo html_writer::tag('pre', $item->last_error, ['class' => 'bg-light p-2 border']);
    }

    echo html_writer::tag('p', html_writer::tag('strong', get_string('payload', 'local_studentmapper') . ': '));
    $payload = json_decode($item->payload, true);
    echo html_writer::tag('pre', json_encode($payload, JSON_PRETTY_PRINT), ['class' => 'bg-light p-2 border']);

    echo html_writer::end_div();

    // Footer.
    echo html_writer::start_div('modal-footer');
    echo html_writer::link($PAGE->url, get_string('close'), ['class' => 'btn btn-secondary']);
    echo html_writer::end_div();

    echo html_writer::end_div();
    echo html_writer::end_div();
    echo html_writer::end_div();

    // Modal backdrop.
    echo html_writer::div('', 'modal-backdrop fade show');
}

echo $OUTPUT->footer();
