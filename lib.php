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
 * Mandatory public API of gwpayments module
 *
 * File         lib.php
 * Encoding     UTF-8
 *
 * @package     mod_gwpayments
 *
 * @copyright   2021 RvD
 * @author      RvD <helpdesk@sebsoft.nl>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

// Include functions we whould deprecate in the "near" future.
require_once(__DIR__ . '/deprecatedlib.php');

/**
 * List of features supported in URL module
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed True if module supports feature, false if not, null if doesn't know
 */
function gwpayments_supports($feature) {
    switch($feature) {
        case FEATURE_MOD_ARCHETYPE:
            return MOD_ARCHETYPE_OTHER;
        case FEATURE_BACKUP_MOODLE2:
            return true;
        case FEATURE_MOD_INTRO:
            return true;
        case FEATURE_MODEDIT_DEFAULT_COMPLETION:
            return false;
        case FEATURE_COMPLETION_TRACKS_VIEWS:
            return false;  // Completion will not track views :D.
        case FEATURE_COMPLETION_HAS_RULES:
            return true;  // We have a custom completion mechanism :D.
        case FEATURE_SHOW_DESCRIPTION:
            return true;
        case FEATURE_GROUPS:
            return false;
        case FEATURE_GROUPINGS:
            return false;
        case FEATURE_GROUPMEMBERSONLY:
            return false;
        case FEATURE_GRADE_HAS_GRADE:
            return false; // We have no grading mechanism :D.
        case FEATURE_GRADE_OUTCOMES:
            return false;
        case FEATURE_NO_VIEW_LINK:
            return false;

        default:
            return null;
    }
}

/**
 * Returns all other caps used in module
 * @return array
 */
function gwpayments_get_extra_capabilities() {
    return ['moodle/site:accessallgroups'];
}

/**
 * This function is used by the reset_course_userdata function in moodlelib.
 * @param array $data the data submitted from the reset course.
 * @return array status array
 */
function gwpayments_reset_userdata($data) {
    return [];
}

/**
 * List the actions that correspond to a view of this module.
 * This is used by the participation report.
 *
 * Note: This is not used by new logging system. Event with
 *       crud = 'r' and edulevel = LEVEL_PARTICIPATING will
 *       be considered as view action.
 *
 * @return array
 */
function gwpayments_get_view_actions() {
    return ['view', 'view all'];
}

/**
 * List the actions that correspond to a post of this module.
 * This is used by the participation report.
 *
 * Note: This is not used by new logging system. Event with
 *       crud = ('c' || 'u' || 'd') and edulevel = LEVEL_PARTICIPATING
 *       will be considered as post action.
 *
 * @return array
 */
function gwpayments_get_post_actions() {
    return ['update', 'add'];
}

/**
 * Add gwpayments instance.
 * @param object $data
 * @param object $mform
 * @return int new gwpayments instance id
 */
function gwpayments_add_instance($data, $mform) {
    global $DB;

    $data->timecreated = time();
    $data->timemodified = $data->timecreated;
    $data->id = $DB->insert_record('gwpayments', $data);

    return $data->id;
}

/**
 * Update gwpayments instance.
 * @param object $data
 * @param object $mform
 * @return bool true
 */
function gwpayments_update_instance($data, $mform) {
    global $DB;

    $data->timemodified = time();
    $data->id           = $data->instance;
    $DB->update_record('gwpayments', $data);

    return true;
}

/**
 * Delete gwpayments instance.
 * @param int $id
 * @return bool true
 */
function gwpayments_delete_instance($id) {
    global $DB;

    if (!$gwpayments = $DB->get_record('gwpayments', ['id' => $id])) {
        return false;
    }

    // Note: all context files are deleted automatically.
    $DB->delete_records('gwpayments', ['id' => $gwpayments->id]);

    return true;
}

/**
 * Return a list of page types
 * @param string $pagetype current page type
 * @param stdClass $parentcontext Block's parent context
 * @param stdClass $currentcontext Current context of block
 */
function gwpayments_page_type_list($pagetype, $parentcontext, $currentcontext) {
    $modulepagetype = ['mod-gwpayments-*' => get_string('page-mod-gwpayments-x', 'mod_gwpayments')];
    return $modulepagetype;
}

/**
 * Get dynamic modinfo.
 *
 * This will manipulate the course module's visibility and how it's shown based on payment status.
 *
 * @param cm_info $modinfo
 */
function gwpayments_cm_info_dynamic(cm_info $modinfo) {
    global $DB, $USER, $OUTPUT;

    $instance = $DB->get_record('gwpayments', ['id' => $modinfo->instance], '*', MUST_EXIST);
    $studentdisplayonpayments = (bool)$instance->studentdisplayonpayments;
    $disablepaymentonmisconfig = (bool)$instance->disablepaymentonmisconfig;

    $notifications = [];
    $canpaymentbemade = \mod_gwpayments\local\helper::can_payment_be_made($modinfo, $notifications);

    $origuservisible = $modinfo->get_user_visible();
    $origavailable = $modinfo->available;

    // We're "complete" if there's a record and expiry limitations are not met.
    $uservisible = false;
    $available = true;
    $noviewlink = false;
    $injectpaymentbutton = false;
    if (has_capability('mod/gwpayments:submitpayment', $modinfo->context) && !is_siteadmin()) {
        // For those that can submit gwpayments.
        $noviewlink = !$studentdisplayonpayments;
        $userdata = $DB->get_record_sql('SELECT * FROM {gwpayments_userdata}
                WHERE gwpaymentsid = ?
                AND userid = ?',
                [$modinfo->instance, $USER->id]);
        if (empty($userdata)) {
            $uservisible = true;
            $injectpaymentbutton = true;
        } else if ((int)$userdata->timeexpire > 0 && (int)$userdata->timeexpire < time()) {
            $uservisible = true;
            $injectpaymentbutton = true;
        } else if ((int)$userdata->timeexpire === 0) {
            $uservisible = $studentdisplayonpayments;
            $available = $studentdisplayonpayments;
        }
    } else {
        // For eveyone else.
        $uservisible = true;
        $available = true;
    }

    $finalvisible = $uservisible && $origuservisible;
    $finalavailable = $available && $origavailable;

    // We first must set availability/visibility before setting dynamic content (as this changes state)!
    $modinfo->set_user_visible($finalvisible);
    $modinfo->set_available($finalavailable);
    if ($noviewlink) {
        $modinfo->set_no_view_link();
    }
    $injectedcontent = '';
    if ($injectpaymentbutton) {
        // Create the payment button.
        $data = (object)[
            'isguestuser' => isguestuser(),
            'cost' => \core_payment\helper::get_cost_as_string($instance->cost, $instance->currency),
            'instanceid' => $instance->id,
            'description' => $modinfo->get_formatted_name(),
            'successurl' => \mod_gwpayments\payment\service_provider::get_success_url('gwpayments', $instance->id)->out(false),
        ];
        $data->userid = $USER->id;
        $data->currency = $instance->currency;
        $data->vat = (int)$instance->vat;
        $data->localisedcost = format_float($instance->cost, 2, true);
        $data->locale = $USER->lang;
        $data->component = 'mod_gwpayments';
        $data->paymentarea = 'unlockfee';
        $data->disablepaymentbutton = false;
        $data->hasnotifications = false;
        if (!$canpaymentbemade && $disablepaymentonmisconfig) {
            $data->disablepaymentbutton = true;
        }
        if (!$canpaymentbemade) {
            $data->hasnotifications = true;
            $data->notifications = [get_string('err:payment:misconfiguration', 'mod_gwpayments')];
        }
        $injectedcontent .= $OUTPUT->render_from_template('mod_gwpayments/payment_region', $data);
    }

    if (!empty($notifications) && (has_capability('mod/gwpayments:addinstance', $modinfo->context) || is_siteadmin())) {
        $injectedcontent = html_writer::div(implode('<br/>', $notifications), 'alert alert-warning');
    }
    if (!empty($injectedcontent) && $finalvisible) {
        $modinfo->set_content($modinfo->content . $injectedcontent, true);
    }
}

/**
 * Given a course_module object, this function returns any
 * "extra" information that may be needed when printing
 * this activity in a course listing.
 *
 * @param object $coursemodule
 * @return cached_cm_info info
 */
function gwpayments_get_coursemodule_info($coursemodule) {
    global $DB;

    $params = ['id' => $coursemodule->instance];
    if (!$gwpayment = $DB->get_record('gwpayments', $params)) {
        return false;
    }

    $result = new cached_cm_info();
    $result->name = $gwpayment->name;

    if ($coursemodule->showdescription) {
        // Convert intro to html. Do not filter cached version, filters run at display time.
        $result->content = format_module_intro('gwpayments', $gwpayment, $coursemodule->id, false);
    }

    // Populate the custom completion rules as key => value pairs.
    // Because we force automatic completion and payment is a mandate, there are no extra checks.
    $result->customdata['customcompletionrules']['completionsubmit'] = 1;

    return $result;
}

/**
 * Callback which returns human-readable strings describing the active completion custom rules for the module instance.
 *
 * @param cm_info|stdClass $cm object with fields ->completion and ->customdata['customcompletionrules']
 * @return array $descriptions the array of descriptions for the custom rules.
 */
function mod_gwpayments_get_completion_active_rule_descriptions($cm) {
    // We perform these checks even though automatic completion is forced..
    if (empty($cm->customdata['customcompletionrules'])
        || $cm->completion != COMPLETION_TRACKING_AUTOMATIC) {
        return [];
    }

    $descriptions = [];
    foreach ($cm->customdata['customcompletionrules'] as $key => $val) {
        switch ($key) {
            case 'completionsubmit':
                if (!empty($val)) {
                    $descriptions[] = get_string('completionsubmit', 'mod_gwpayments');
                }
                break;
            default:
                break;
        }
    }
    return $descriptions;
}
