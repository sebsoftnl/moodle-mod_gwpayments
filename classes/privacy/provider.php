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
 * Privacy Subsystem implementation for mod_gwpayments.
 *
 * File         provider.php
 * Encoding     UTF-8
 *
 * @package     mod_gwpayments
 *
 * @copyright   Ing. R.J. van Dongen
 * @author      R.J. van Dongen <rogier@sebsoft.nl>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_gwpayments\privacy;

use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\userlist;
use core_privacy\local\metadata\collection;

/**
 * Privacy Subsystem for mod_gwpayments implementing null_provider.
 *
 * @package     mod_gwpayments
 *
 * @copyright   Ing. R.J. van Dongen
 * @author      R.J. van Dongen <rogier@sebsoft.nl>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_payment\privacy\consumer_provider,
    \core_privacy\local\request\data_provider {

    /**
     * Provides meta data that is stored about a user with block_coupon
     *
     * @param  collection $collection A collection of meta data items to be added to.
     * @return  collection Returns the collection of metadata.
     */
    public static function get_metadata(collection $collection) : collection {
        $collection->add_database_table(
            'gwpayments_userdata',
            [
                'userid' => 'privacy:metadata:database:gwpayments:userid',
                'amount' => 'privacy:metadata:database:gwpayments:amount',
                'currency' => 'privacy:metadata:database:gwpayments:currency',
                'timecreated' => 'privacy:metadata:database:gwpayments:timecreated',
                'timemodified' => 'privacy:metadata:database:gwpayments:timemodified',
                'timeexpire' => 'privacy:metadata:database:gwpayments:timeexpire',
            ],
            'privacy:metadata:database:gwpayments'
        );
        return $collection;
    }

    /**
     * Return contextid for the provided payment data
     *
     * @param string $paymentarea Payment area
     * @param int $itemid The item id
     * @return int|null
     */
    public static function get_contextid_for_payment(string $paymentarea, int $itemid): ?int {
        global $DB;

        $sql = "SELECT ctx.id
                  FROM {gwpayments} gwp
                  FROM {course_modules} cm ON cm.instance = gwp.id
                  JOIN {context} ctx ON (ctx.contextlevel = :contextmodule AND ctx.instanceid = cm.id)
                 WHERE gwp.id = :gwpid";
        $params = [
            'contextmodule' => CONTEXT_MODULE,
            'gwpid' => $itemid,
        ];
        $contextid = $DB->get_field_sql($sql, $params);

        return $contextid ?: null;
    }

    /**
     * Get the list of users who have data within a context.
     *
     * @param userlist $userlist The userlist containing the list of users who have data in this context/plugin combination.
     */
    public static function get_users_in_context(userlist $userlist) {
        $context = $userlist->get_context();

        if ($context instanceof \context_course) {
            $sql = "SELECT ud.userid
                      FROM {gwpayments} gwp
                      JOIN {gwpayments_userdata} ud ON gwp.id = ud.gwpaymentsid
                     WHERE gwp.course = :courseid";
            $params = [
                'courseid' => $context->instanceid,
            ];
            $userlist->add_from_sql('userid', $sql, $params);
        } else if ($context instanceof \context_module) {
            $sql = "SELECT ud.userid
                      FROM {course_modules} cm
                      JOIN {gwpayments} gwp ON cm.instance = gwp.id
                      JOIN {gwpayments_userdata} ud ON gwp.id = ud.gwpaymentsid
                     WHERE cm.id = :cmid";
            $params = [
                'cmid' => $context->instanceid,
            ];
            $userlist->add_from_sql('userid', $sql, $params);
        }
    }

    /**
     * Export all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts to export information for.
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;

        $subcontext = [
            get_string('modulename', 'mod_gwpayments'),
        ];
        foreach ($contextlist as $context) {
            if (!$context instanceof \context_course && !$context instanceof \context_course) {
                continue;
            }

            if ($context instanceof \context_course) {
                $gwpaymentinstances = $DB->get_records('gwpayments', ['course' => $context->instanceid]);
            } else if ($context instanceof \context_module) {
                $gwpaymentinstances = $DB->get_records_sql('SELECT gwp.* FROM {gwpayments}
                        JOIN {course_modules} cm ON (cm.instance = gwp.id)
                        WHERE cm.id = :cmid', ['cmid' => $context->instanceid]);
            }

            foreach ($gwpaymentinstances as $gwpaymentinstance) {
                \core_payment\privacy\provider::export_payment_data_for_user_in_context(
                    $context,
                    $subcontext,
                    $contextlist->get_user()->id,
                    'mod_gwpayments',
                    'unlockfee',
                    $gwpaymentinstance->id
                );
            }
        }

        if (in_array(SYSCONTEXTID, $contextlist->get_contextids())) {
            // Orphaned gwpayments.
            $sql = "SELECT p.*
                    FROM {payments} p
                    LEFT JOIN {gwpayments} gwp ON p.itemid = gwp.id
                    WHERE p.userid = :userid AND p.component = :component AND gwp.id IS NULL";
            $params = [
                'component' => 'mod_gwpayments',
                'userid' => $contextlist->get_user()->id,
            ];

            $orphanedgwpayments = $DB->get_recordset_sql($sql, $params);
            foreach ($orphanedgwpayments as $payment) {
                \core_payment\privacy\provider::export_payment_data_for_user_in_context(
                    \context_system::instance(),
                    $subcontext,
                    $payment->userid,
                    $payment->component,
                    $payment->paymentarea,
                    $payment->itemid
                );
            }
            $orphanedgwpayments->close();
        }
    }

    /**
     * Delete all data for all users in the specified context.
     *
     * @param context $context The specific context to delete data for.
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        if ($context instanceof \context_course) {
            $sql = "SELECT p.id
                    FROM {payments} p
                    JOIN {gwpayments} gwp ON p.itemid = gwp.id
                    WHERE gwp.courseid = :courseid AND p.component = :component";
            $params = [
                'component' => 'mod_gwpayments',
                'courseid' => $context->instanceid,
            ];

            \core_payment\privacy\provider::delete_data_for_payment_sql($sql, $params);
        } else if ($context instanceof \context_module) {
            $sql = "SELECT p.id
                    FROM {payments} p
                    JOIN {gwpayments} gwp ON (p.component = :component AND p.itemid = gwp.id)
                    JOIN {course_modules} cm ON (p.itemid = gwp.id AND cm.id = :cmid)";
            $params = [
                'component' => 'mod_gwpayments',
                'cmid' => $context->instanceid,
            ];

            \core_payment\privacy\provider::delete_data_for_payment_sql($sql, $params);
        } else if ($context instanceof \context_system) {
            $sql = "SELECT p.id
                    FROM {payments} p
                    LEFT JOIN {gwpayments} gwp ON (p.component = :component AND p.itemid = gwp.id)
                    WHERE gwp.id IS NULL";
            $params = [
                'component' => 'mod_gwpayments',
            ];

            \core_payment\privacy\provider::delete_data_for_payment_sql($sql, $params);
        }
    }

    /**
     * Delete all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts and user information to delete information for.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;

        if (empty($contextlist->count())) {
            return;
        }

        $contexts = $contextlist->get_contexts();

        $courseids = [];
        $cmids = [];
        foreach ($contexts as $context) {
            if ($context instanceof \context_course) {
                $courseids[] = $context->instanceid;
            }
            if ($context instanceof \context_module) {
                $cmids[] = $context->instanceid;
            }
        }

        // Delete course levels.
        [$insql, $inparams] = $DB->get_in_or_equal($courseids, SQL_PARAMS_NAMED);
        $sql = "SELECT p.id
                FROM {payments} p
                JOIN {gwpayments} gwp ON (p.component = :component AND p.itemid = gwp.id)
                WHERE p.userid = :userid AND gwp.course $insql";
        $params = $inparams + [
            'component' => 'mod_gwpayments',
            'userid' => $contextlist->get_user()->id,
        ];
        \core_payment\privacy\provider::delete_data_for_payment_sql($sql, $params);

        // Delete module levels.
        [$insql, $inparams] = $DB->get_in_or_equal($cmids, SQL_PARAMS_NAMED);
        $sql = "SELECT p.id
                FROM {payments} p
                JOIN {course_modules} cm ON (p.component = :component AND p.itemid = cm.instanceid)
                WHERE p.userid = :userid AND cm.id $insql";
        $params = $inparams + [
            'component' => 'mod_gwpayments',
            'userid' => $contextlist->get_user()->id,
        ];
        \core_payment\privacy\provider::delete_data_for_payment_sql($sql, $params);

        if (in_array(SYSCONTEXTID, $contextlist->get_contextids())) {
            // Orphaned payments.
            $sql = "SELECT p.id
                    FROM {payments} p
                    LEFT JOIN {course_modules} cm ON (p.component = :component AND p.itemid = cm.instanceid)
                    WHERE p.component = :component AND p.userid = :userid AND cm.id IS NULL";
            $params = [
                'component' => 'mod_gwpayments',
                'userid' => $contextlist->get_user()->id,
            ];

            \core_payment\privacy\provider::delete_data_for_payment_sql($sql, $params);
        }
    }

    /**
     * Delete multiple users within a single context.
     *
     * @param approved_userlist $userlist The approved context and user information to delete information for.
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        global $DB;

        $context = $userlist->get_context();

        if ($context instanceof \context_course) {
            [$usersql, $userparams] = $DB->get_in_or_equal($userlist->get_userids(), SQL_PARAMS_NAMED);
            $sql = "SELECT p.id
                    FROM {payments} p
                    JOIN {gwpayments} gwp ON (p.component = :component AND p.itemid = gwp.id)
                    WHERE gwp.course = :courseid AND p.userid $usersql";
            $params = $userparams + [
                'component' => 'mod_payments',
                'courseid' => $context->instanceid,
            ];

            \core_payment\privacy\provider::delete_data_for_payment_sql($sql, $params);
        } else if ($context instanceof \context_module) {
            [$usersql, $userparams] = $DB->get_in_or_equal($userlist->get_userids(), SQL_PARAMS_NAMED);
            $sql = "SELECT p.id
                    FROM {payments} p
                    JOIN {course_modules} cm ON (p.component = :component AND p.itemid = cm.instance)
                    JOIN {gwpayments} gwp ON (gwp.id = cm.instance)
                    WHERE cm.id = :cmid AND p.userid $usersql";
            $params = $userparams + [
                'component' => 'mod_payments',
                'courseid' => $context->instanceid,
            ];

            \core_payment\privacy\provider::delete_data_for_payment_sql($sql, $params);
        } else if ($context instanceof \context_system) {
            // Orphaned payments.
            [$usersql, $userparams] = $DB->get_in_or_equal($userlist->get_userids(), SQL_PARAMS_NAMED);
            $sql = "SELECT p.id
                    FROM {payments} p
                    LEFT JOIN {course_modules} cm ON (p.itemid = cm.instance)
                    WHERE p.component = :component AND p.userid $usersql AND cm.id IS NULL";
            $params = $userparams + [
                'component' => 'mod_payments',
            ];

            \core_payment\privacy\provider::delete_data_for_payment_sql($sql, $params);
        }
    }

}
