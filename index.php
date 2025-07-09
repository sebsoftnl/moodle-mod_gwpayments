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
 * List of gwpayments in course
 *
 * File         index.php
 * Encoding     UTF-8
 *
 * @package     mod_gwpayments
 *
 * @copyright   2021 RvD
 * @author      RvD <helpdesk@sebsoft.nl>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');

$id = required_param('id', PARAM_INT); // Course id.
$course = $DB->get_record('course', ['id' => $id], '*', MUST_EXIST);

require_course_login($course, true);
$PAGE->set_pagelayout('incourse');

$params = ['context' => context_course::instance($course->id)];
$event = \mod_gwpayments\event\course_module_instance_list_viewed::create($params);
$event->add_record_snapshot('course', $course);
$event->trigger();

$strmodname       = get_string('modulename', 'mod_gwpayments');
$strmodnameplural = get_string('modulenameplural', 'mod_gwpayments');
$strname         = get_string('name');
$strintro        = get_string('moduleintro');
$strlastmodified = get_string('lastmodified');

$PAGE->set_url('/mod/gwpayments/index.php', ['id' => $course->id]);
$PAGE->set_title($course->shortname.': '.$strmodnameplural);
$PAGE->set_heading($course->fullname);
$PAGE->navbar->add($strmodnameplural);

echo $OUTPUT->header();
echo $OUTPUT->heading($strmodnameplural);

if (!$gwpayments = get_all_instances_in_course('gwpayments', $course)) {
    notice(get_string('thereareno', 'moodle', $strmodnameplural), "$CFG->wwwroot/course/view.php?id=$course->id");
    exit;
}

$usesections = course_format_uses_sections($course->format);

$table = new html_table();
$table->attributes['class'] = 'generaltable mod_index';

if ($usesections) {
    $strsectionname = get_string('sectionname', 'format_'.$course->format);
    $table->head  = [$strsectionname, $strname, $strintro];
    $table->align = ['center', 'left', 'left'];
} else {
    $table->head  = [$strlastmodified, $strname, $strintro];
    $table->align = ['left', 'left', 'left'];
}

$modinfo = get_fast_modinfo($course);
$currentsection = '';
foreach ($gwpayments as $gwpayment) {
    $cm = $modinfo->cms[$gwpayment->coursemodule];
    if ($usesections) {
        $printsection = '';
        if ($gwpayment->section !== $currentsection) {
            if ($gwpayment->section) {
                $printsection = get_section_name($course, $gwpayment->section);
            }
            if ($currentsection !== '') {
                $table->data[] = 'hr';
            }
            $currentsection = $gwpayment->section;
        }
    } else {
        $printsection = '<span class="smallinfo">'.userdate($gwpayment->timemodified)."</span>";
    }

    $extra = empty($cm->extra) ? '' : $cm->extra;
    $icon = '';
    if (!empty($cm->icon)) {
        $icon = '<img src="'.$OUTPUT->pix_url($cm->icon, $cm->iconcomponent) .
                '" class="activityicon" alt="'.get_string('modulename', $cm->modname).'" /> ';
    }

    $class = $gwpayment->visible ? '' : 'class="dimmed"'; // Hidden modules are dimmed.
    $table->data[] = [
        $printsection,
        "<a $class $extra href=\"view.php?id=$cm->id\">".$icon.format_string($gwpayment->name)."</a>",
        format_module_intro('gwpayments', $gwpayment, $cm->id),
    ];
}

echo html_writer::table($table);

echo $OUTPUT->footer();
