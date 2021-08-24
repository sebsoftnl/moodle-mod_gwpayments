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
 * gwpayments module main user interface
 *
 * File         view.php
 * Encoding     UTF-8
 *
 * @package     mod_gwpayments
 *
 * @copyright   2021 Ing. R.J. van Dongen
 * @author      Ing. R.J. van Dongen <rogier@sebsoft.nl>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require('../../config.php');
require_once($CFG->libdir . '/completionlib.php');
$id = required_param('id', PARAM_INT);
$redirect = optional_param('redirect', 0, PARAM_BOOL);
$referer = optional_param('referer', null, PARAM_URL);

list($course, $cm) = get_course_and_cm_from_cmid($id, 'gwpayments');
$gwpayment = $DB->get_record('gwpayments', array('id' => $cm->instance), '*', MUST_EXIST);

$PAGE->set_url('/mod/gwpayments/view.php', array('id' => $cm->id));

require_course_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/gwpayments:view', $context);

$PAGE->set_activity_record($gwpayment);
$PAGE->set_title($course->shortname.': '.$gwpayment->name);
$PAGE->set_heading($course->fullname);
$params = array(
    'context' => $context,
    'objectid' => $gwpayment->id
);

$event = \mod_gwpayments\event\course_module_viewed::create($params);
$event->add_record_snapshot('course_modules', $cm);
$event->add_record_snapshot('course', $course);
$event->add_record_snapshot('gwpayments', $gwpayment);
$event->trigger();

if (isguestuser()) {
    // Guest account.
    echo $OUTPUT->header();
    echo $OUTPUT->confirm(get_string('noguestchoose', 'choice').'<br /><br />'.get_string('liketologin'),
                 get_login_url(), new moodle_url('/course/view.php', array('id' => $course->id)));
    echo $OUTPUT->footer();

} else if (!is_enrolled($context) && !is_siteadmin()) {
    // Only people enrolled can do anything.
    $SESSION->wantsurl = qualified_me();
    $SESSION->enrolcancel = (!empty($_SERVER['HTTP_REFERER'])) ? $_SERVER['HTTP_REFERER'] : '';

    $coursecontext = context_course::instance($course->id);
    $courseshortname = format_string($course->shortname, true, array('context' => $coursecontext));

    echo $OUTPUT->header();
    echo $OUTPUT->box_start('generalbox', 'notice');
    echo '<p align="center">'. get_string('notenrolledchoose', 'mod_gwpayments') .'</p>';
    echo $OUTPUT->container_start('continuebutton');
    echo $OUTPUT->single_button(new moodle_url('/enrol/index.php?',
            array('id' => $course->id)), get_string('enrolme', 'core_enrol', $courseshortname));
    echo $OUTPUT->container_end();
    echo $OUTPUT->box_end();
    echo $OUTPUT->footer();

} else {

    $renderer = $PAGE->get_renderer('mod_gwpayments');
    // We can only see the overview when we have the correct capabilities.
    if (has_capability('mod/gwpayments:viewpayments', $context) || is_siteadmin()) {
        $table = new \mod_gwpayments\local\payments\table($context);
        $table->define_baseurl($PAGE->url);

        echo $OUTPUT->header();
        echo $table->render(25);
        echo $OUTPUT->footer();
    } else if (has_capability('mod/gwpayments:submitpayment', $context) && !is_siteadmin()) {
        // Display state.
        echo $OUTPUT->header();
        echo $renderer->paymentdetails($context, $USER->id);
        echo $OUTPUT->footer();
    }
}
