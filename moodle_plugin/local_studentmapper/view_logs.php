<?php
/**
 * View sync logs page
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

$PAGE->set_url(new moodle_url('/local/studentmapper/view_logs.php'));
$PAGE->set_title(get_string('viewlogs', 'local_studentmapper'));
$PAGE->set_heading(get_string('viewlogs', 'local_studentmapper'));

// Get filter parameters.
$success = optional_param('success', '', PARAM_TEXT);
$eventtype = optional_param('eventtype', '', PARAM_ALPHA);
$userid = optional_param('userid', 0, PARAM_INT);
$datefrom = optional_param('datefrom', 0, PARAM_INT);
$dateto = optional_param('dateto', 0, PARAM_INT);

echo $OUTPUT->header();

// Page heading.
echo $OUTPUT->heading(get_string('viewlogs', 'local_studentmapper'));

// Filters form.
echo html_writer::start_div('mb-3 border p-3 bg-light');
echo html_writer::tag('h4', get_string('filters'));
echo html_writer::start_tag('form', ['method' => 'get', 'action' => $PAGE->url->out_omit_querystring()]);

echo html_writer::start_div('row');

// Success filter.
echo html_writer::start_div('col-md-3');
echo html_writer::tag('label', get_string('status'), ['for' => 'id_success']);
$successoptions = [
    '' => get_string('all'),
    '1' => get_string('success'),
    '0' => get_string('failed', 'local_studentmapper'),
];
echo html_writer::select($successoptions, 'success', $success, false, ['id' => 'id_success', 'class' => 'form-control']);
echo html_writer::end_div();

// Event type filter.
echo html_writer::start_div('col-md-3');
echo html_writer::tag('label', get_string('eventtype', 'local_studentmapper'), ['for' => 'id_eventtype']);
$eventtypeoptions = [
    '' => get_string('all'),
    'user_created' => get_string('eventtype_user_created', 'local_studentmapper'),
    'user_updated' => get_string('eventtype_user_updated', 'local_studentmapper'),
    'manual' => get_string('eventtype_manual', 'local_studentmapper'),
];
echo html_writer::select($eventtypeoptions, 'eventtype', $eventtype, false, ['id' => 'id_eventtype', 'class' => 'form-control']);
echo html_writer::end_div();

// User ID filter.
echo html_writer::start_div('col-md-3');
echo html_writer::tag('label', get_string('userid', 'local_studentmapper'), ['for' => 'id_userid']);
echo html_writer::empty_tag('input', [
    'type' => 'number',
    'name' => 'userid',
    'id' => 'id_userid',
    'value' => $userid,
    'class' => 'form-control',
    'placeholder' => get_string('allusers', 'local_studentmapper'),
]);
echo html_writer::end_div();

// Date range filter.
echo html_writer::start_div('col-md-3');
echo html_writer::tag('label', get_string('daterange', 'local_studentmapper'), ['for' => 'id_daterange']);
$daterangeoptions = [
    '' => get_string('alltime', 'local_studentmapper'),
    'today' => get_string('today'),
    'week' => get_string('lastweek'),
    'month' => get_string('lastmonth'),
];
$selectedrange = '';
if ($datefrom && $dateto) {
    if ($datefrom >= strtotime('today')) {
        $selectedrange = 'today';
    } else if ($datefrom >= strtotime('-7 days')) {
        $selectedrange = 'week';
    } else if ($datefrom >= strtotime('-30 days')) {
        $selectedrange = 'month';
    }
}
echo html_writer::select($daterangeoptions, 'daterange', $selectedrange, false, [
    'id' => 'id_daterange',
    'class' => 'form-control',
    'onchange' => 'this.form.submit();',
]);
echo html_writer::end_div();

echo html_writer::end_div(); // End row.

echo html_writer::start_div('mt-2');
echo html_writer::empty_tag('input', [
    'type' => 'submit',
    'value' => get_string('filter'),
    'class' => 'btn btn-primary',
]);
echo html_writer::link($PAGE->url, get_string('clearfilters', 'local_studentmapper'), ['class' => 'btn btn-secondary ml-2']);
echo html_writer::end_div();

echo html_writer::end_tag('form');
echo html_writer::end_div();

// Process date range shortcuts.
$daterange = optional_param('daterange', '', PARAM_ALPHA);
if ($daterange) {
    switch ($daterange) {
        case 'today':
            $datefrom = strtotime('today');
            $dateto = time();
            break;
        case 'week':
            $datefrom = strtotime('-7 days');
            $dateto = time();
            break;
        case 'month':
            $datefrom = strtotime('-30 days');
            $dateto = time();
            break;
    }
}

// Log table.
$table = new \local_studentmapper\table\log_table('local_studentmapper_log');
$table->define_baseurl(new moodle_url($PAGE->url, [
    'success' => $success,
    'eventtype' => $eventtype,
    'userid' => $userid,
    'datefrom' => $datefrom,
    'dateto' => $dateto,
]));

// Apply filters.
$successfilter = null;
if ($success !== '') {
    $successfilter = (bool)$success;
}
$table->apply_filters($successfilter, $eventtype, $datefrom, $dateto, $userid);

ob_start();
$table->out(50, false);
$tablehtml = ob_get_clean();

if (empty($tablehtml) || strpos($tablehtml, 'Nothing to display') !== false) {
    echo html_writer::div(get_string('nologs', 'local_studentmapper'), 'alert alert-info');
} else {
    echo $tablehtml;
}

echo $OUTPUT->footer();
