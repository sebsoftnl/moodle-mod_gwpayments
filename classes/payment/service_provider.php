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
 * Payment subsystem callback implementation for mod_gwpayments.
 *
 * File         service_provider.php
 * Encoding     UTF-8
 *
 * @package     mod_gwpayments
 *
 * @copyright   2021 Ing. R.J. van Dongen
 * @author      Ing. R.J. van Dongen <rogier@sebsoft.nl>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_gwpayments\payment;

/**
 * Payment subsystem callback implementation for mod_gwpayments.
 *
 * @package     mod_gwpayments
 *
 * @copyright   2021 Ing. R.J. van Dongen
 * @author      Ing. R.J. van Dongen <rogier@sebsoft.nl>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class service_provider implements \core_payment\local\callback\service_provider {

    /**
     * Generate payable data.
     *
     * This is a utility method that can modify the actual variables and modify the payment
     * amount. This is where we transform the initial cost for e.g. coupons, discounts etc etc.
     *
     * @param \stdClass $instance course module instance.
     * @param string $paymentarea Payment area
     * @param int $itemid The item id
     * @return stdClass
     */
    private static function generate_payabledata(\stdClass $instance, string $paymentarea, int $itemid) {
        $result = (object) [
            'amount' => $instance->cost,
            'currency' => $instance->currency,
            'accountid' => $instance->accountid
        ];

        // For now we do NOT yet modify any data (such as discount codes).

        // And return result.
        return $result;
    }

    /**
     * Callback function that returns the cost and the accountid
     * for the course that $instanceid instance belongs to.
     *
     * @param string $paymentarea Payment area
     * @param int $instanceid The instance id
     * @return \core_payment\local\entities\payable
     */
    public static function get_payable(string $paymentarea, int $instanceid): \core_payment\local\entities\payable {
        global $DB;

        $instance = $DB->get_record('gwpayments', ['id' => $instanceid], '*', MUST_EXIST);

        $payabledata = static::generate_payabledata($instance, $paymentarea, $instanceid);

        return new \core_payment\local\entities\payable($payabledata->amount, $payabledata->currency, $payabledata->accountid);
    }

    /**
     * Callback function that returns the URL of the page the user should be redirected to in the case of a successful payment.
     *
     * @param string $paymentarea Payment area
     * @param int $instanceid The instance id
     * @return \moodle_url
     */
    public static function get_success_url(string $paymentarea, int $instanceid): \moodle_url {
        global $DB;

        $courseid = $DB->get_field('gwpayments', 'course', ['id' => $instanceid], MUST_EXIST);

        return new \moodle_url('/course/view.php', ['id' => $courseid]);
    }

    /**
     * Callback function that delivers what the user paid for to them.
     *
     * @param string $paymentarea
     * @param int $instanceid The  instance id
     * @param int $paymentid payment id as inserted into the 'gwpayments' table, if needed for reference
     * @param int $userid The userid the order is going to deliver to
     * @return bool Whether successful or not
     */
    public static function deliver_order(string $paymentarea, int $instanceid, int $paymentid, int $userid): bool {
        global $CFG, $DB;
        require_once($CFG->libdir . '/completionlib.php');

        // Delivering the order means we inject a tracking record for the user.
        $instance = $DB->get_record('gwpayments', ['id' => $instanceid], '*', MUST_EXIST);
        $userdata = $DB->get_record('gwpayments_userdata', ['userid' => $userid, 'gwpaymentsid' => $instance->id]);
        $data = $DB->get_record('payments', ['id' => $paymentid]);
        if (empty($userdata)) {
            $userdata = (object)[
                'id' => 0,
                'gwpaymentsid' => $instance->id,
                'userid' => $userid,
                'cost' => $data->amount,
                'currency' => $instance->currency,
                'timeexpire' => 0,
                'timecreated' => 0,
                'timemodified' => 0
            ];
        }
        if (!empty($instance->costduration)) {
            // Append expiration.
            $userdata->timeexpire = (max(time(), $userdata->timeexpire)) + $instance->costduration;
        } else {
            // Set exp to 0.
            $userdata->timeexpire = 0;
        }

        if (empty($userdata->id)) {
            $userdata->timecreated = time();
            $DB->insert_record('gwpayments_userdata', $userdata);
        } else {
            $userdata->timemodified = time();
            $DB->update_record('gwpayments_userdata', $userdata);
        }

        // We should update completion state.
        $completion = new \completion_info(get_course($instance->course));
        $cm = get_coursemodule_from_instance('gwpayments', $instance->id, $instance->course, false, MUST_EXIST);
        $completion->update_state($cm, COMPLETION_COMPLETE, $userid);

        // We will dispatch en event.
        \mod_gwpayments\event\order_delivered::trigger_from_data($instance->id, $userid);

        return true;
    }

}
