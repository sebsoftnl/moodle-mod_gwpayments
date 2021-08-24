<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Payments table class.
 *
 * File         payments.php
 * Encoding     UTF-8
 *
 * @package     mod_gwpayments
 *
 * @copyright   2021 Ing. R.J. van Dongen
 * @author      Ing. R.J. van Dongen <rogier@sebsoft.nl>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_gwpayments\local\payments;

defined('MOODLE_INTERNAL') or die('NO_ACCESS');

require_once($CFG->libdir . '/tablelib.php');

/**
 * mod_gwpayments\local\payments\table
 *
 * @package     mod_gwpayments
 *
 * @copyright   2021 Ing. R.J. van Dongen
 * @author      Ing. R.J. van Dongen <rogier@sebsoft.nl>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class table extends \table_sql {

    /**
     * @var \context
     */
    protected $context;

    /**
     * Create a new instance of the logtable
     *
     * @param \context $context the context in which we are looking.
     */
    public function __construct($context) {
        global $USER;
        parent::__construct(__CLASS__. '-' . $USER->id);
        $this->context = $context;
        $this->sortable(true, 'ud.timecreated', 'DESC');
        $this->collapsible(false);
    }

    /**
     * Set the sql to query the db.
     * This method is disabled for this class, since we use internal queries
     *
     * @param string $fields
     * @param string $from
     * @param string $where
     * @param array $params
     * @throws exception
     */
    public function set_sql($fields, $from, $where, array $params = null) {
        // We'll disable this method.
        throw new exception('err:table:set_sql');
    }

    /**
     * Display the general status log table.
     *
     * @param int $pagesize
     * @param bool $useinitialsbar
     */
    public function render($pagesize, $useinitialsbar = true) {
        $columns = array('fullname', 'cost', 'timecreated', 'timeexpire', 'status');
        $headers = array(
            get_string('fullname'),
            get_string('amount', 'mod_gwpayments'),
            get_string('timecreated', 'mod_gwpayments'),
            get_string('timeexpire', 'mod_gwpayments'),
            get_string('status', 'mod_gwpayments'),
        );

        $this->define_columns($columns);
        $this->define_headers($headers);

        $where = [];
        $params = [];

        // Generate SQL.
        if (class_exists('\core\user_fields', true)) {
            $usql = \core\user_fields::for_name()->get_sql('u', true, '', '', false);
            // REALLY, MOODLE? I need to write MORE code now? For this siple case you could have injected a SHORTCUT method.
            $fields = 'ud.*, ' . $usql->selects;
            $from = '{gwpayments_userdata} ud ';
            $from .= 'JOIN {gwpayments} gwp ON ud.gwpaymentsid = gwp.id ';
            $from .= 'JOIN {user} u ON ud.userid = u.id ';
            $from .= $usql->joins;
            $params += $usql->params;
        } else {
            $ufields = get_all_user_name_fields(true, 'u');
            $fields = 'ud.*, ' . $ufields;
            $from = '{gwpayments_userdata} ud ';
            $from .= 'JOIN {gwpayments} gwp ON ud.gwpaymentsid = gwp.id ';
            $from .= 'JOIN {user} u ON ud.userid = u.id ';
        }

        if ($this->context->contextlevel === CONTEXT_MODULE) {
            $from .= 'JOIN {course_modules} cm ON (cm.course = gwp.course AND cm.id = :cmid)';
            $params['cmid'] = $this->context->instanceid;
        } else if ($this->context->contextlevel === CONTEXT_COURSE) {
            $where[] = 'gwp.course = :courseid';
            $params['courseid'] = $this->context->instanceid;
        }

        if (empty($where)) {
            // Prevent bugs.
            $where[] = '1 = 1';
        }

        parent::set_sql($fields, $from, implode(' AND ', $where), $params);
        $this->out($pagesize, $useinitialsbar);
    }

    /**
     * Render visual representation of the 'amount' column for use in the table
     *
     * @param \stdClass $row
     * @return string time string
     */
    public function col_cost($row) {
        return \core_payment\helper::get_cost_as_string($row->cost, $row->currency);
    }

    /**
     * Render visual representation of the 'timecreated' column for use in the table
     *
     * @param \stdClass $row
     * @return string time string
     */
    public function col_timecreated($row) {
        return userdate($row->timecreated, get_string('strftimedatetime', 'langconfig'));
    }

    /**
     * Render visual representation of the 'timeexpire' column for use in the table
     *
     * @param \stdClass $row
     * @return string time string
     */
    public function col_timeexpire($row) {
        if (empty($row->timeexpire)) {
            return  '-';
        } else {
            return userdate($row->timeexpire, get_string('strftimedatetime', 'langconfig'));
        }
    }

    /**
     * Render visual representation of the 'timeexpire' column for use in the table
     *
     * @param \stdClass $row
     * @return string time string
     */
    public function col_status($row) {
        if (empty($row->timeexpire)) {
            return new \lang_string('status:active', 'mod_gwpayments');
        } else {
            return $row->timeexpire > time() ? new \lang_string('status:active', 'mod_gwpayments') :
                new \lang_string('status:expired', 'mod_gwpayments');
        }
    }

    /**
     * Get row class
     *
     * @param stdClass $row
     * @return string
     */
    public function get_row_class($row) {
        if ($row->timeexpire < time()) {
            return 'alert alert-danger';
        } else {
            return '';
        }
    }

}
