<?php
/**
 * Queue table for displaying queue items
 *
 * @package    local_studentmapper
 * @copyright  2024
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_studentmapper\table;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/tablelib.php');

/**
 * Table for displaying queue items
 */
class queue_table extends \table_sql {

    /**
     * Constructor
     * @param string $uniqueid Unique ID for the table
     */
    public function __construct($uniqueid) {
        parent::__construct($uniqueid);

        // Define columns.
        $columns = ['id', 'userid', 'username', 'eventtype', 'status', 'attempts', 'nextretry', 'lasterror', 'actions'];
        $headers = [
            get_string('id', 'local_studentmapper'),
            get_string('userid', 'local_studentmapper'),
            get_string('username'),
            get_string('eventtype', 'local_studentmapper'),
            get_string('status'),
            get_string('attempts', 'local_studentmapper'),
            get_string('nextretry', 'local_studentmapper'),
            get_string('lasterror', 'local_studentmapper'),
            get_string('actions'),
        ];

        $this->define_columns($columns);
        $this->define_headers($headers);

        // Table settings.
        $this->sortable(true, 'timecreated', SORT_DESC);
        $this->collapsible(false);
        $this->pageable(true);
        $this->is_downloadable(true);
        $this->show_download_buttons_at([TABLE_P_BOTTOM]);

        // SQL query.
        $fields = 'q.id, q.userid, q.eventtype, q.status, q.attempts, q.max_attempts, q.next_retry, q.last_error, q.timecreated, q.timemodified, ' .
                  'u.firstname, u.lastname, u.username';
        $from = '{local_studentmapper_queue} q ' .
                'LEFT JOIN {user} u ON q.userid = u.id';
        $where = '1=1';

        $this->set_sql($fields, $from, $where);
    }

    /**
     * Format userid column
     * @param object $row
     * @return string
     */
    public function col_userid($row) {
        if (empty($row->userid)) {
            return '-';
        }
        $url = new \moodle_url('/user/profile.php', ['id' => $row->userid]);
        return \html_writer::link($url, $row->userid);
    }

    /**
     * Format username column
     * @param object $row
     * @return string
     */
    public function col_username($row) {
        if (empty($row->firstname) && empty($row->lastname)) {
            return '-';
        }
        return fullname($row);
    }

    /**
     * Format eventtype column
     * @param object $row
     * @return string
     */
    public function col_eventtype($row) {
        return get_string('eventtype_' . $row->eventtype, 'local_studentmapper');
    }

    /**
     * Format status column with color
     * @param object $row
     * @return string
     */
    public function col_status($row) {
        $statusclass = 'badge ';
        switch ($row->status) {
            case 'pending':
                $statusclass .= 'badge-warning';
                break;
            case 'processing':
                $statusclass .= 'badge-info';
                break;
            case 'completed':
                $statusclass .= 'badge-success';
                break;
            case 'failed':
                $statusclass .= 'badge-danger';
                break;
            default:
                $statusclass .= 'badge-secondary';
        }
        return \html_writer::span(get_string('status_' . $row->status, 'local_studentmapper'), $statusclass);
    }

    /**
     * Format attempts column
     * @param object $row
     * @return string
     */
    public function col_attempts($row) {
        return $row->attempts . ' / ' . $row->max_attempts;
    }

    /**
     * Format nextretry column
     * @param object $row
     * @return string
     */
    public function col_nextretry($row) {
        if ($row->status === 'completed' || $row->status === 'failed') {
            return '-';
        }
        if ($row->next_retry <= time()) {
            return \html_writer::span(get_string('ready', 'local_studentmapper'), 'badge badge-success');
        }
        return userdate($row->next_retry, get_string('strftimedatetime', 'langconfig'));
    }

    /**
     * Format lasterror column
     * @param object $row
     * @return string
     */
    public function col_lasterror($row) {
        if (empty($row->last_error)) {
            return '-';
        }
        // Truncate long errors.
        $error = \core_text::substr($row->last_error, 0, 100);
        if (\core_text::strlen($row->last_error) > 100) {
            $error .= '...';
        }
        return \html_writer::span($error, 'text-danger small');
    }

    /**
     * Format actions column
     * @param object $row
     * @return string
     */
    public function col_actions($row) {
        global $OUTPUT;

        $actions = [];

        // Retry action (only for pending/failed items).
        if (in_array($row->status, ['pending', 'failed'])) {
            $retryurl = new \moodle_url('/local/studentmapper/manage_queue.php', [
                'action' => 'retry',
                'id' => $row->id,
                'sesskey' => sesskey(),
            ]);
            $actions[] = \html_writer::link($retryurl, get_string('retry', 'local_studentmapper'), [
                'class' => 'btn btn-sm btn-primary',
            ]);
        }

        // View details action.
        $viewurl = new \moodle_url('/local/studentmapper/manage_queue.php', [
            'action' => 'view',
            'id' => $row->id,
        ]);
        $actions[] = \html_writer::link($viewurl, get_string('view'), [
            'class' => 'btn btn-sm btn-secondary',
        ]);

        // Delete action.
        $deleteurl = new \moodle_url('/local/studentmapper/manage_queue.php', [
            'action' => 'delete',
            'id' => $row->id,
            'sesskey' => sesskey(),
        ]);
        $actions[] = \html_writer::link($deleteurl, get_string('delete'), [
            'class' => 'btn btn-sm btn-danger',
            'onclick' => 'return confirm("' . get_string('confirmdelete', 'local_studentmapper') . '");',
        ]);

        return implode(' ', $actions);
    }

    /**
     * Apply filters to the table
     * @param string $status Filter by status
     * @param string $eventtype Filter by event type
     */
    public function apply_filters($status = null, $eventtype = null) {
        $where = '1=1';
        $params = [];

        if (!empty($status)) {
            $where .= ' AND q.status = :status';
            $params['status'] = $status;
        }

        if (!empty($eventtype)) {
            $where .= ' AND q.eventtype = :eventtype';
            $params['eventtype'] = $eventtype;
        }

        $fields = 'q.id, q.userid, q.eventtype, q.status, q.attempts, q.max_attempts, q.next_retry, q.last_error, q.timecreated, q.timemodified, ' .
                  'u.firstname, u.lastname, u.username';
        $from = '{local_studentmapper_queue} q ' .
                'LEFT JOIN {user} u ON q.userid = u.id';

        $this->set_sql($fields, $from, $where, $params);
    }
}
