<?php

// This file is part of the Checkskill plugin for Moodle - http://moodle.org/
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
 * This page skills all the instances of checkskill in a particular course
 *
 * @package mod/checkskill borrowed from package checklist author  David Smith <moodle@davosmith.co.uk>
 * @author Jean FRUITET <jean.fruitet@univ-nantes.fr>
 * @package mod/checkskill
 */

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/lib.php');
require_once(dirname(__FILE__).'/locallib.php');

global DB, $PAGE, $OUTPUT, $CFG, $USER;

$id = required_param('id', PARAM_INT);   // course

$course = $DB->get_record('course', array('id' => $id), '*', MUST_EXIST);

$PAGE->set_url('/mod/checkskill/index.php', array('id'=>$course->id));
require_course_login($course);
$PAGE->set_pagelayout('incourse');

if ($CFG->version > 2014051200) { // Moodle 2.7+
    $params = array(
        'context' => context_course::instance($course->id)
    );
    $event = \mod_checkskill\event\course_module_instance_list_viewed::create($params);
    $event->add_record_snapshot('course', $course);
    $event->trigger();
} 
else { // Before Moodle 2.7
	add_to_log($course->id, 'checkskill', 'view all', "index.php?id=$course->id", '');
}

/// Get all required stringsnewmodule

$strcheckskills = get_string('modulenameplural', 'checkskill');
$strcheckskill  = get_string('modulename', 'checkskill');


/// Print the header

$PAGE->navbar->add($strcheckskills);
$PAGE->set_title($strcheckskills);
echo $OUTPUT->header();

/// Get all the appropriate data

if (! $checkskills = get_all_instances_in_course('checkskill', $course)) {
    notice('There are no instances of checkskill', "../../course/view.php?id=$course->id");
    die;
}

/// Print the list of instances (your module will probably extend this)

$timenow  = time();
$strname  = get_string('name');
$strweek  = get_string('week');
$strtopic = get_string('topic');
$strprogress = get_string('progress', 'checkskill');

$table = new html_table();

if ($course->format == 'weeks') {
    $table->head  = array ($strweek, $strname);
    $table->align = array ('center', 'left', 'left');
} else if ($course->format == 'topics') {
    $table->head  = array ($strtopic, $strname);
    $table->align = array ('center', 'left', 'left');
} else {
    $table->head  = array ($strname);
    $table->align = array ('left', 'left');
}

if ($CFG->version < 2011120100) {
    $context = get_context_instance(CONTEXT_COURSE, $course->id);
} else {
    $context = context_course::instance($course->id);
}
$canupdateown = has_capability('mod/checkskill:updateown', $context);
if ($canupdateown) {
    $table->head[] = $strprogress;
}

foreach ($checkskills as $checkskill) {
    if (!$checkskill->visible) {
        //Show dimmed if the mod is hidden
        $link = '<a class="dimmed" href="view.php?id='.$checkskill->coursemodule.'">'.format_string($checkskill->name).'</a>';
    } else {
        //Show normal if the mod is visible
        $link = '<a href="view.php?id='.$checkskill->coursemodule.'">'.format_string($checkskill->name).'</a>';
    }


    if ($course->format == 'weeks' or $course->format == 'topics') {
        $row = array ($checkskill->section, $link);
    } else {
        $row = array ($link);
    }

    if ($canupdateown) {
        $row[] = checkskill_class::print_user_progressbar($checkskill->id, $USER->id, '300px', true, true);
    }

    $table->data[] = $row;
}

echo $OUTPUT->heading($strcheckskills);
echo html_writer::table($table);

/// Finish the page

echo $OUTPUT->footer();
