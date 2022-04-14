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
 * The mod_gwpayments custom completion implementation.
 *
 * File         custom_completion.php
 * Encoding     UTF-8
 *
 * @package     mod_gwpayments
 *
 * @copyright   2021 Ing. R.J. van Dongen
 * @author      Ing. R.J. van Dongen <rogier@sebsoft.nl>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_gwpayments\completion;

use core_completion\activity_custom_completion;

/**
 * The mod_gwpayments custom completion implementation.
 *
 * @package     mod_gwpayments
 *
 * @copyright   2021 Ing. R.J. van Dongen
 * @author      Ing. R.J. van Dongen <rogier@sebsoft.nl>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class custom_completion extends activity_custom_completion {

    /**
     * Fetch the list of custom completion rules that this module defines.
     *
     * @return array
     */
    public static function get_defined_custom_rules(): array {
        return [
            'completionsubmit'
        ];
    }

    /**
     * Returns an associative array of the descriptions of custom completion rules.
     *
     * @return array
     */
    public function get_custom_rule_descriptions(): array {
        return [
            'completionsubmit' => get_string('completiondetail:submit', 'mod_gwpayments')
        ];
    }

    /**
     * Returns an array of all completion rules, in the order they should be displayed to users.
     *
     * @return array
     */
    public function get_sort_order(): array {
        return [
            'completionsubmit',
        ];
    }

    /**
     * Fetches the completion state for a given completion rule.
     *
     * @param string $rule The completion rule.
     * @return int The completion state.
     */
    public function get_state(string $rule): int {
        global $DB;

        $this->validate_rule($rule);

        $context = \context_module::instance($this->cm->id);
        $cansubmitpayment = has_capability('mod/gwpayments:submitpayment', $context, $this->userid);

        if ($cansubmitpayment && !is_siteadmin($this->userid)) {
            // Get user payment details.
            $gwpayments = $DB->get_record('gwpayments', ['id' => $this->cm->instance], '*', MUST_EXIST);
            // We're only "complete" if there's a record and expiry limitations are not met.
            $userdata = $DB->get_record('gwpayments_userdata', ['gwpaymentsid' => $gwpayments->id, 'userid' => $this->userid]);
            $result = false;
            if (!empty($userdata)) {
                $result = ((int)$userdata->timeexpire === 0) ? true : ($userdata->timeexpire > time());
            }
            return $result ? COMPLETION_COMPLETE : COMPLETION_INCOMPLETE;
        }
        return COMPLETION_INCOMPLETE;
    }

}
