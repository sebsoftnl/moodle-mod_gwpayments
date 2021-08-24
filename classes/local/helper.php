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
 * Helper class.
 *
 * File         helper.php
 * Encoding     UTF-8
 *
 * @package     mod_gwpayments
 *
 * @copyright   2021 Ing. R.J. van Dongen
 * @author      Ing. R.J. van Dongen <rogier@sebsoft.nl>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_gwpayments\local;

defined('MOODLE_INTERNAL') or die('NO_ACCESS');

/**
 * mod_gwpayments\local\helper
 *
 * @package     mod_gwpayments
 *
 * @copyright   2021 Ing. R.J. van Dongen
 * @author      Ing. R.J. van Dongen <rogier@sebsoft.nl>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class helper {

    /**
     * Returns the list of currencies that the payment subsystem supports and therefore we can work with.
     *
     * @return array[currencycode => currencyname]
     */
    public static function get_possible_currencies(): array {
        $codes = \core_payment\helper::get_supported_currencies();

        $currencies = [];
        foreach ($codes as $c) {
            $currencies[$c] = new \lang_string($c, 'core_currencies');
        }

        uasort($currencies, function($a, $b) {
            return strcmp($a, $b);
        });

        return $currencies;
    }

    /**
     * Process expiries.
     *
     * @param int $timestamp
     */
    public static function expire_user_payments($timestamp) {
        global $CFG, $DB;
        require_once($CFG->libdir . '/completionlib.php');
        // Process expiries.
        $list = $DB->get_recordset_sql('SELECT ud.*, a.course FROM {gwpayments_userdata} ud
                JOIN {gwpayments} a ON a.id = ud.gwpaymentsid
                WHERE COALESCE(timeexpire, 0) <> 0
                AND COALESCE(timeexpire, 0) < ?
                ', [$timestamp]);
        foreach ($list as $item) {
            // Adjust completion.
            $completion = new \completion_info(get_course($item->course));
            $cm = get_coursemodule_from_instance('gwpayments', $item->gwpaymentsid, $item->course, false, MUST_EXIST);
            $completion->update_state($cm, COMPLETION_INCOMPLETE, $item->userid);
        }
        $list->close();
    }

}
