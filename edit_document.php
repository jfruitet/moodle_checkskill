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
 * Edit a doccument attached to a Description
 *
 * @author  Jean Fruitet <jean.fruitet@univ-nantes.fr>
 * @package mod/checkskill
 * borrowed from packqge checklist
 * @author  David Smith <moodle@davosmith.co.uk>
 */


require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/lib.php');
require_once(dirname(__FILE__).'/locallib.php');

require_once(dirname(__FILE__).'/file_api.php');   // Moodle 2 file API
require_once(dirname(dirname(dirname(__FILE__))).'/repository/lib.php'); // Repository API

global $DB;

$id = optional_param('id', 0, PARAM_INT); // course_module ID, or
$checkskillid  = optional_param('checkskill', 0, PARAM_INT);  // checkskill instance ID
$itemid  = optional_param('itemid', 0, PARAM_INT);  // Item ID
$userid  = optional_param('userid', 0, PARAM_INT);  // userID
$documentid  = optional_param('documentid', 0, PARAM_INT);  // document ID
$cancel     = optional_param('cancel', 0, PARAM_BOOL);


$url = new moodle_url('/mod/checkskill/edit_document.php');
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

$returnurl=new moodle_url('/mod/checkskill/view.php?checkskill='.$checkskill->id);

if ($documentid){
    $document = $DB->get_record('checkskill_document', array("id" => $documentid));
}

if (empty($document)){
    redirect($returnurl);
}

    $PAGE->set_url($url);


    require_login($course, true, $cm);

    $context = get_context_instance(CONTEXT_MODULE, $cm->id);

    if (empty($userid)){
        if (has_capability('mod/checkskill:updateown', $context)) {
            $userid = $USER->id;
        }
    }


    /// If it's hidden then it's don't show anything.  :)
    /// Some capability checks.
    if (empty($cm->visible)
        && (
            !has_capability('moodle/course:viewhiddenactivities', $context)
            &&
            !has_capability('mod/checkskill:updateown', $context)
        )

    ) {
        print_error('activityiscurrentlyhidden','error',$returnurl);
    }


    if ($cancel) {
        if (!empty($SESSION->returnpage)) {
            $return = $SESSION->returnpage;
            unset($SESSION->returnpage);
            redirect($return);
        } else {
            redirect($returnurl);
        }
    }

    if ($chk = new checkskill_class($cm->id, 0, $checkskill, $cm, $course)) {
        $chk->edit_document($itemid, $userid, $document);
    }

