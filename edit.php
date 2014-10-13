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

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/lib.php');
require_once(dirname(__FILE__).'/locallib.php');

global $DB;

$id = optional_param('id', 0, PARAM_INT); // course_module ID, or
$checkskillid  = optional_param('checkskill', 0, PARAM_INT);  // checkskill instance ID

$url = new moodle_url('/mod/checkskill/view.php');
if ($id) {
    if (!$cm = get_coursemodule_from_id('checkskill', $id)){
        print_error('error_cmid', 'checkskill'); // 'Course Module ID was incorrect'
    }
    $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $checkskill = $DB->get_record('checkskill', array('id' => $cm->instance), '*', MUST_EXIST);
    $url->param('id', $id);
} else if ($checkskillid) {
    $checkskill = $DB->get_record('checkskill', array('id' => $checkskillid), '*', MUST_EXIST);
    $course = $DB->get_record('course', array('id' => $checkskill->course), '*', MUST_EXIST);
    if (!$cm = get_coursemodule_from_instance('checkskill', $checkskill->id, $course->id)) {
        print_error('error_cmid', 'checkskill'); // 'Course Module ID was incorrect'
    }
    $url->param('checkskill', $checkskillid);
} else {
    print_error('error_specif_id', 'checkskill'); // 'You must specify a course_module ID or an instance ID'
}


$PAGE->set_url($url);
require_login($course, true, $cm);

if ($chk = new checkskill_class($cm->id, 0, $checkskill, $cm, $course)) {
    $chk->edit();
}
