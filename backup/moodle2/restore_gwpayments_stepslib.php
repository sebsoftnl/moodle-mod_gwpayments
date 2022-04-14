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
 * All the steps to restore mod_gwpayments are defined here.
 *
 * @package     mod_gwpayments
 * @copyright   2022 R.J. van Dongen <rogier@sebsoft.nl>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Defines the structure step to restore one mod_gwpayments activity.
 *
 * For more information about the backup and restore process, please visit:
 * https://docs.moodle.org/dev/Backup_2.0_for_developers
 * https://docs.moodle.org/dev/Restore_2.0_for_developers
 *
 * @package     mod_gwpayments
 *
 * @copyright   2022 R.J. van Dongen <rogier@sebsoft.nl>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_gwpayments_activity_structure_step extends restore_activity_structure_step {

    /**
     * Defines the structure to be restored.
     *
     * @return restore_path_element[].
     */
    protected function define_structure() {
        $paths = array();
        $userinfo = $this->get_setting_value('userinfo');

        // Define each element separated.
        $paths[] = new restore_path_element('gwpayments', '/activity/gwpayments');

        // User data.
        if ($userinfo) {
            $paths[] = new restore_path_element('userpayment', '/activity/gwpayments/userdata/userpayment');
        }

        return $this->prepare_activity_structure($paths);
    }

    /**
     * Process an gwpayments restore.
     *
     * @param \stdClass $data The data in object form
     * @return void
     */
    protected function process_gwpayments($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->course = $this->get_courseid();

        // TODO: revise this when more is known about account/pgw backup/restore.
        // For now we completely set the account to "NULL".
        // I know; this forces the activity module into "inactive" mode. So be it.
        $data->accountid = null;

        $newitemid = $DB->insert_record('gwpayments', $data);
        $this->apply_activity_instance($newitemid);

        $this->set_mapping('gwpayments', $oldid, $newitemid);
    }

    /**
     * Process user payment data
     *
     * @param \stdClass $data The data in object form
     * @return void
     */
    protected function process_userpayment($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        unset($data->id);

        $data->gwpaymentsid = $this->get_new_parentid('gwpayments');
        $newitemid = $DB->insert_record('gwpayments_userdata', $data);

        $this->set_mapping('userpayment', $oldid, $newitemid);
    }

    /**
     * Defines post-execution actions.
     */
    protected function after_execute() {
        $this->add_related_files('mod_assign', 'intro', null);
        $this->add_related_files('mod_assign', 'introattachment', null);
    }
}
