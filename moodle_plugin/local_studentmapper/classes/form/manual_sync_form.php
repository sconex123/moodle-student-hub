<?php
/**
 * Manual sync form
 *
 * @package    local_studentmapper
 * @copyright  2024
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_studentmapper\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Form for manually triggering user sync
 */
class manual_sync_form extends \moodleform {

    /**
     * Form definition
     */
    protected function definition() {
        $mform = $this->_form;

        // Sync type selection.
        $mform->addElement('header', 'synctypeheader', get_string('synctype', 'local_studentmapper'));

        $synctypes = [
            'single' => get_string('synctype_single', 'local_studentmapper'),
            'bulk' => get_string('synctype_bulk', 'local_studentmapper'),
            'cohort' => get_string('synctype_cohort', 'local_studentmapper'),
            'role' => get_string('synctype_role', 'local_studentmapper'),
        ];
        $mform->addElement('select', 'synctype', get_string('synctype', 'local_studentmapper'), $synctypes);
        $mform->setDefault('synctype', 'single');

        // Single user sync options.
        $mform->addElement('header', 'singleheader', get_string('singleusersync', 'local_studentmapper'));

        $identifiertypes = [
            'userid' => get_string('userid', 'local_studentmapper'),
            'username' => get_string('username'),
            'email' => get_string('email'),
        ];
        $mform->addElement('select', 'identifiertype', get_string('identifiertype', 'local_studentmapper'), $identifiertypes);
        $mform->setDefault('identifiertype', 'userid');
        $mform->hideIf('identifiertype', 'synctype', 'neq', 'single');

        $mform->addElement('text', 'identifier', get_string('identifier', 'local_studentmapper'));
        $mform->setType('identifier', PARAM_TEXT);
        $mform->hideIf('identifier', 'synctype', 'neq', 'single');

        // Bulk sync options.
        $mform->addElement('header', 'bulkheader', get_string('bulkusersync', 'local_studentmapper'));

        $mform->addElement('textarea', 'userids', get_string('userids', 'local_studentmapper'), ['rows' => 5, 'cols' => 50]);
        $mform->setType('userids', PARAM_TEXT);
        $mform->addHelpButton('userids', 'userids', 'local_studentmapper');
        $mform->hideIf('userids', 'synctype', 'neq', 'bulk');

        // Cohort sync options.
        $mform->addElement('header', 'cohortheader', get_string('cohortsync', 'local_studentmapper'));

        $cohorts = $this->get_cohorts();
        $mform->addElement('select', 'cohortid', get_string('cohort', 'cohort'), $cohorts);
        $mform->hideIf('cohortid', 'synctype', 'neq', 'cohort');

        // Role sync options.
        $mform->addElement('header', 'roleheader', get_string('rolesync', 'local_studentmapper'));

        $roles = $this->get_roles();
        $mform->addElement('select', 'roleid', get_string('role'), $roles);
        $mform->hideIf('roleid', 'synctype', 'neq', 'role');

        // Common options.
        $mform->addElement('header', 'optionsheader', get_string('options', 'local_studentmapper'));

        $mform->addElement('advcheckbox', 'queue', get_string('addtoqueue', 'local_studentmapper'),
            get_string('addtoqueue_desc', 'local_studentmapper'));
        $mform->setDefault('queue', 0);

        $mform->addElement('advcheckbox', 'force', get_string('forcesync', 'local_studentmapper'),
            get_string('forcesync_desc', 'local_studentmapper'));
        $mform->setDefault('force', 0);

        $mform->addElement('text', 'limit', get_string('limit', 'local_studentmapper'));
        $mform->setType('limit', PARAM_INT);
        $mform->setDefault('limit', 1000);
        $mform->addHelpButton('limit', 'limit', 'local_studentmapper');
        $mform->hideIf('limit', 'synctype', 'eq', 'single');

        // Action buttons.
        $this->add_action_buttons(true, get_string('sync', 'local_studentmapper'));
    }

    /**
     * Form validation
     * @param array $data
     * @param array $files
     * @return array
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        if ($data['synctype'] === 'single') {
            if (empty($data['identifier'])) {
                $errors['identifier'] = get_string('required');
            }
        } else if ($data['synctype'] === 'bulk') {
            if (empty($data['userids'])) {
                $errors['userids'] = get_string('required');
            } else {
                // Validate user IDs format.
                $userids = preg_split('/[\s,]+/', trim($data['userids']), -1, PREG_SPLIT_NO_EMPTY);
                foreach ($userids as $userid) {
                    if (!is_numeric($userid) || $userid <= 0) {
                        $errors['userids'] = get_string('invaliduserids', 'local_studentmapper');
                        break;
                    }
                }
            }
        } else if ($data['synctype'] === 'cohort') {
            if (empty($data['cohortid'])) {
                $errors['cohortid'] = get_string('required');
            }
        } else if ($data['synctype'] === 'role') {
            if (empty($data['roleid'])) {
                $errors['roleid'] = get_string('required');
            }
        }

        if (!empty($data['limit']) && ($data['limit'] < 1 || $data['limit'] > 10000)) {
            $errors['limit'] = get_string('invalidlimit', 'local_studentmapper');
        }

        return $errors;
    }

    /**
     * Get available cohorts
     * @return array
     */
    private function get_cohorts() {
        global $DB;

        $cohorts = $DB->get_records_menu('cohort', null, 'name ASC', 'id, name');
        return [0 => get_string('choosedots')] + $cohorts;
    }

    /**
     * Get available roles
     * @return array
     */
    private function get_roles() {
        global $DB;

        $roles = $DB->get_records_menu('role', null, 'sortorder ASC', 'id, name');
        return [0 => get_string('choosedots')] + $roles;
    }
}
