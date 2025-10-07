<?php
/**
 * Log table for displaying sync logs
 *
 * @package    local_studentmapper
 * @copyright  2024
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_studentmapper\table;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/tablelib.php');

/**
 * Table for displaying sync logs
 */
class log_table extends \table_sql {

    /**
     * Constructor
     * @param string $uniqueid Unique ID for the table
     */
    public function __construct($uniqueid) {
        parent::__construct($uniqueid);

        // Define columns.
        $columns = ['timecreated', 'userid', 'username', 'eventtype', 'success', 'httpcode', 'executiontime', 'errormessage', 'actions'];
        $headers = [
            get_string('timestamp', 'local_studentmapper'),
            get_string('userid', 'local_studentmapper'),
            get_string('username'),
            get_string('eventtype', 'local_studentmapper'),
            get_string('status'),
            get_string('httpcode', 'local_studentmapper'),
            get_string('executiontime', 'local_studentmapper'),
            get_string('errormessage', 'local_studentmapper'),
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
        $fields = 'l.id, l.userid, l.eventtype, l.success, l.http_code, l.execution_time, l.error_message, l.timecreated, ' .
                  'l.payload, l.response, ' .
                  'u.firstname, u.lastname, u.username';
        $from = '{local_studentmapper_log} l ' .
                'LEFT JOIN {user} u ON l.userid = u.id';
        $where = '1=1';

        $this->set_sql($fields, $from, $where);
    }

    /**
     * Format timecreated column
     * @param object $row
     * @return string
     */
    public function col_timecreated($row) {
        return userdate($row->timecreated, get_string('strftimedatetime', 'langconfig'));
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
     * Format success column with color
     * @param object $row
     * @return string
     */
    public function col_success($row) {
        if ($row->success) {
            return \html_writer::span(get_string('success'), 'badge badge-success');
        } else {
            return \html_writer::span(get_string('failed', 'local_studentmapper'), 'badge badge-danger');
        }
    }

    /**
     * Format httpcode column
     * @param object $row
     * @return string
     */
    public function col_httpcode($row) {
        if (empty($row->http_code)) {
            return '-';
        }

        $class = '';
        if ($row->http_code >= 200 && $row->http_code < 300) {
            $class = 'text-success';
        } else if ($row->http_code >= 400) {
            $class = 'text-danger';
        } else if ($row->http_code >= 300) {
            $class = 'text-warning';
        }

        return \html_writer::span($row->http_code, $class . ' font-weight-bold');
    }

    /**
     * Format executiontime column
     * @param object $row
     * @return string
     */
    public function col_executiontime($row) {
        if (empty($row->execution_time)) {
            return '-';
        }
        return round($row->execution_time, 2) . ' ms';
    }

    /**
     * Format errormessage column
     * @param object $row
     * @return string
     */
    public function col_errormessage($row) {
        if (empty($row->error_message)) {
            return '-';
        }
        // Truncate long errors.
        $error = \core_text::substr($row->error_message, 0, 100);
        if (\core_text::strlen($row->error_message) > 100) {
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
        $viewurl = new \moodle_url('/local/studentmapper/view_log.php', [
            'id' => $row->id,
        ]);
        return \html_writer::link($viewurl, get_string('viewdetails', 'local_studentmapper'), [
            'class' => 'btn btn-sm btn-secondary',
        ]);
    }

    /**
     * Apply filters to the table
     * @param bool $success Filter by success status
     * @param string $eventtype Filter by event type
     * @param int $datefrom Filter from date
     * @param int $dateto Filter to date
     * @param int $userid Filter by user ID
     */
    public function apply_filters($success = null, $eventtype = null, $datefrom = null, $dateto = null, $userid = null) {
        $where = '1=1';
        $params = [];

        if ($success !== null) {
            $where .= ' AND l.success = :success';
            $params['success'] = $success ? 1 : 0;
        }

        if (!empty($eventtype)) {
            $where .= ' AND l.eventtype = :eventtype';
            $params['eventtype'] = $eventtype;
        }

        if (!empty($datefrom)) {
            $where .= ' AND l.timecreated >= :datefrom';
            $params['datefrom'] = $datefrom;
        }

        if (!empty($dateto)) {
            $where .= ' AND l.timecreated <= :dateto';
            $params['dateto'] = $dateto;
        }

        if (!empty($userid)) {
            $where .= ' AND l.userid = :userid';
            $params['userid'] = $userid;
        }

        $fields = 'l.id, l.userid, l.eventtype, l.success, l.http_code, l.execution_time, l.error_message, l.timecreated, ' .
                  'l.payload, l.response, ' .
                  'u.firstname, u.lastname, u.username';
        $from = '{local_studentmapper_log} l ' .
                'LEFT JOIN {user} u ON l.userid = u.id';

        $this->set_sql($fields, $from, $where, $params);
    }
}
