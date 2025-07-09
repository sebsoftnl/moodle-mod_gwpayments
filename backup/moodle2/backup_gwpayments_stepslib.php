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
 * Backup steps for mod_gwpayments are defined here.
 *
 * @package     mod_gwpayments
 * @copyright   2022 RvD <helpdesk@sebsoft.nl>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Define the complete structure for backup, with file and id annotations.
 *
 * For more information about the backup and restore process, please visit:
 * https://docs.moodle.org/dev/Backup_2.0_for_developers
 * https://docs.moodle.org/dev/Restore_2.0_for_developers
 *
 * @package     mod_gwpayments
 *
 * @copyright   2022 RvD <helpdesk@sebsoft.nl>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_gwpayments_activity_structure_step extends backup_activity_structure_step {

    /**
     * Defines the structure of the resulting xml file.
     *
     * @return backup_nested_element The structure wrapped by the common 'activity' element.
     */
    protected function define_structure() {

        // To know if we are including userinfo.
        $userinfo = $this->get_setting_value('userinfo');
        $groupinfo = $this->get_setting_value('groups');

        // Replace with the attributes and final elements that the element will handle.
        $gwpayments = new backup_nested_element('gwpayments', ['id'], [
            'name',
            'intro',
            'introformat',
            'accountid',
            'cost',
            'vat',
            'currency',
            'studentdisplayonpayments',
            'disablepaymentonmisconfig',
            'timecreated',
            'timemodified',
        ]);

        // For this base, define source, annotate IDs and file annotations.
        $gwpayments->set_source_table('gwpayments', ['id' => backup::VAR_ACTIVITYID]);

        // No idea how or where Moodle backs up gateway/account data, so we do not include it.
        // Did Moodle even think about this at all??

        $gwpayments->annotate_files('mod_gwpayments', 'intro', null);
        $gwpayments->annotate_files('mod_gwpayments', 'introattachment', null);

        // Add userdata.
        if ($userinfo) {
            $this->define_user_structure($gwpayments);
        }

        return $this->prepare_activity_structure($gwpayments);
    }

    /**
     * Define user structure for backup.
     *
     * @param backup_nested_element $entrypoint
     */
    private function define_user_structure(backup_nested_element $entrypoint) {
        $userdata = new backup_nested_element('userdata');
        $dataitem = new backup_nested_element('userpayment', ['id'], [
            'userid',
            'cost',
            'currency',
            'timeexpire',
            'timecreated',
            'timemodified',
        ]);
        // Setup tree.
        $entrypoint->add_child($userdata);
        $userdata->add_child($dataitem);
        // Define sources.
        $dataitem->set_source_table('gwpayments_userdata', ['gwpaymentsid' => backup::VAR_PARENTID]);
        // Define id annotations.
        $dataitem->annotate_ids('user', 'userid');
    }

}
