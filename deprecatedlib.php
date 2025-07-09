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
 * List of deprecated mod_gwpayment functions.
 *
 * This file should definitely be phased out and/.or completely removed by Moodle 4.x
 *
 * File         deprecatedlib.php
 * Encoding     UTF-8
 *
 * @package     mod_gwpayments
 *
 * @copyright   2021 RvD
 * @author      RvD <helpdesk@sebsoft.nl>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Obtains the automatic completion state for this gwpayments based on any conditions
 * in settings.
 *
 * @param object $course Course
 * @param object $cm Course-module
 * @param int $userid User ID
 * @param bool $type Type of comparison (or/and; can be used as return value if no conditions)
 * @return bool True if completed, false if not, $type if conditions not set.
 */
function gwpayments_get_completion_state($course, $cm, $userid, $type) {
    global $DB;
    $context = \context_module::instance($cm->id);
    $cansubmitpayment = has_capability('mod/gwpayments:submitpayment', $context, $userid);

    if ($cansubmitpayment && !is_siteadmin($userid)) {
        // Get user payment details.
        $gwpayments = $DB->get_record('gwpayments', ['id' => $cm->instance], '*', MUST_EXIST);
        // We're only "complete" if there's a record and expiry limitations are not met.
        $userdata = $DB->get_record('gwpayments_userdata', ['gwpaymentsid' => $gwpayments->id, 'userid' => $userid]);
        $result = false;
        if (!empty($userdata)) {
            $result = ((int)$userdata->timeexpire === 0) ? true : ($userdata->timeexpire > time());
        }
        return $result;
    }

    // I'm unsure here. We do not set completion ourself but rely on this method.
    // We'll simply return false always.
    return $type;
}
