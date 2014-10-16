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
 * Library of functions and constants for module checkskill
 * This file should have two well differenced parts:
 *   - All the core Moodle functions, neeeded to allow
 *     the module to work integrated in Moodle.
 *   - All the checkskill specific functions, needed
 *     to implement all the module logic. Please, note
 *     that, if the module become complex and this lib
 *     grows a lot, it's HIGHLY recommended to move all
 *     these module specific functions to a new php file,
 *     called "locallib.php" (see forum, quiz...). This will
 *     help to save some memory when Moodle is performing
 *     actions across all modules.
 */

define("CHECKSKILL_EMAIL_NO", 0);
define("CHECKSKILL_EMAIL_STUDENT", 1);
define("CHECKSKILL_EMAIL_TEACHER", 2);
define("CHECKSKILL_EMAIL_BOTH", 3);
define("CHECKSKILL_TEACHERMARK_NO", 2);
define("CHECKSKILL_TEACHERMARK_YES", 1);
define("CHECKSKILL_TEACHERMARK_UNDECIDED", 0);
define("CHECKSKILL_MARKING_STUDENT", 0);
define("CHECKSKILL_MARKING_TEACHER", 1);
define("CHECKSKILL_MARKING_BOTH", 2);

define("CHECKSKILL_AUTOUPDATE_NO", 0);
define("CHECKSKILL_AUTOUPDATE_YES", 2);
define("CHECKSKILL_AUTOUPDATE_YES_OVERRIDE", 1);

define("CHECKSKILL_AUTOPOPULATE_NO", 0);
define("CHECKSKILL_AUTOPOPULATE_SECTION", 2);
define("CHECKSKILL_AUTOPOPULATE_COURSE", 1);
define("CHECKSKILL_MAX_INDENT", 10);

// define ('CHECKSKILL_DEBUG', 0);    // INACTIVE DEBUG : if set to 1 cron trace many many things :))
define("CHECKSKILL_DEBUG", 1); // impact cron to checks outcomes from CHECKSKILL_OUTCOMES_DELAY to CHECKSKILL_OUTCOMES_DELAY weeks
define("CHECKSKILL_OUTCOMES_DELAY", 2); // how may days the cron examines the outcomes data
// Increase this value to take into account more former outcomes evaluations

global $CFG;
require_once(dirname(__FILE__).'/locallib.php');
require_once($CFG->libdir.'/completionlib.php');

// Add by JF
require_once('file_api.php');

/**
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will create a new instance and return the id number
 * of the new instance.
 *
 * @param object $checkskill An object from the form in mod_form.php
 * @return int The id of the newly inserted checkskill record
 */
function checkskill_add_instance($checkskill) {
    global $DB;

    $checkskill->timecreated = time();
    $checkskill->id = $DB->insert_record('checkskill', $checkskill);

    checkskill_grade_item_update($checkskill);

    return $checkskill->id;
}


/**
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will update an existing instance with new data.
 *
 * @param object $checkskill An object from the form in mod_form.php
 * @return boolean Success/Fail
 */
function checkskill_update_instance($checkskill) {
    global $DB, $CFG;

    $checkskill->timemodified = time();
    $checkskill->id = $checkskill->instance;

    $newmax = $checkskill->maxgrade;
    $oldmax = $DB->get_field('checkskill', 'maxgrade', array('id'=>$checkskill->id));

    $newcompletion = $checkskill->completionpercent;
    $oldcompletion = $DB->get_field('checkskill', 'completionpercent', array('id'=>$checkskill->id));

    $newautoupdate = $checkskill->autoupdate;
    $oldautoupdate = $DB->get_field('checkskill', 'autoupdate', array('id'=>$checkskill->id));

    $DB->update_record('checkskill', $checkskill);

    // Add or remove all calendar events, as needed
    $course = $DB->get_record('course', array('id' => $checkskill->course) );
    $cm = get_coursemodule_from_instance('checkskill', $checkskill->id, $course->id);
    $chk = new checkskill_class($cm->id, 0, $checkskill, $cm, $course);
    $chk->setallevents();

    checkskill_grade_item_update($checkskill);
    if ($newmax != $oldmax) {
        checkskill_update_grades($checkskill);
    } else if ($newcompletion != $oldcompletion) {
        // This will already be updated if checkskill_update_grades() is called
        $ci = new completion_info($course);
        if ($CFG->version < 2011120100) {
            $context = get_context_instance(CONTEXT_MODULE, $cm->id);
        } else {
            $context = context_module::instance($cm->id);
        }
        $users = get_users_by_capability($context, 'mod/checkskill:updateown', 'u.id', '', '', '', '', '', false);
        foreach ($users as $user) {
            $ci->update_state($cm, COMPLETION_UNKNOWN, $user->id);
        }
    }
    if ($newautoupdate && !$oldautoupdate) {
        $chk->update_all_autoupdate_checks();
    }

    return true;
}


/**
 * Given an ID of an instance of this module,
 * this function will permanently delete the instance
 * and any data that depends on it.
 *
 * @param int $id Id of the module instance
 * @return boolean Success/Failure
 */
function checkskill_delete_instance($id) {
    global $DB;

    if (! $checkskill = $DB->get_record('checkskill', array('id' => $id) )) {
        return false;
    }

    // Remove all calendar events
    if ($checkskill->duedatesoncalendar) {
        $checkskill->duedatesoncalendar = false;
        $course = $DB->get_record('course', array('id'=>$checkskill->course) );
        $cm = get_coursemodule_from_instance('checkskill', $checkskill->id, $course->id);
        if ($cm) { // Should not be false, but check, just in case...
            $chk = new checkskill_class($cm->id, 0, $checkskill, $cm, $course);
            $chk->setallevents();
        }
    }

    $items = $DB->get_records('checkskill_item', array('checkskill'=>$checkskill->id), '', 'id');
    if (!empty($items)) {
        $items = array_keys($items);
        $DB->delete_records_list('checkskill_check', 'item', $items);
        $DB->delete_records_list('checkskill_comment', 'itemid', $items);
        $DB->delete_records('checkskill_item', array('checkskill' => $checkskill->id) ); 
    }
    $DB->delete_records('checkskill', array('id' => $checkskill->id));

    checkskill_grade_item_delete($checkskill);

    return true;
}

/**
 *
 */
function checkskill_update_all_grades() {
    global $DB;

    $checkskills = $DB->get_records('checkskill');
    foreach ($checkskills as $checkskill) {
        checkskill_update_grades($checkskill);
    }
}

/**
 * @param object $checklist
 * @param int $userid
 */
 function checkskill_update_grades($checkskill, $userid=0) {
    global $CFG, $DB;

    $items = $DB->get_records('checkskill_item',
                              array('checkskill' => $checkskill->id,
                                    'userid' => 0,
                                    'itemoptional' => CHECKSKILL_OPTIONAL_NO,
                                    'hidden' => CHECKSKILL_HIDDEN_NO ),
                              '', 'id, grouping');
    if (!$course = $DB->get_record('course', array('id' => $checkskill->course) )) {
        return;
    }
    if (!$cm = get_coursemodule_from_instance('checkskill', $checkskill->id, $course->id)) {
        return;
    }
    if (!$cm = get_coursemodule_from_instance('checkskill', $checkskill->id, $course->id)) {
        return;
    }	
    if ($CFG->version < 2011120100) {
        $context = get_context_instance(CONTEXT_MODULE, $cm->id);
    } else {
        $context = context_module::instance($cm->id);
    }

    $checkgroupings = false; // Don't check items against groupings unless we really have to
    if (isset($CFG->enablegroupmembersonly) && $CFG->enablegroupmembersonly && $checkskill->autopopulate) {
        foreach ($items as $item) {
            if ($item->grouping) {
                $checkgroupings = true;
                break;
            }
        }
    }

    if ($checkskill->teacheredit == CHECKSKILL_MARKING_STUDENT) {
        $date = ', MAX(c.usertimestamp) AS datesubmitted';
        $where = 'c.usertimestamp > 0';
    } else {
        $date = ', MAX(c.teachertimestamp) AS dategraded';
        $where = 'c.teachermark = '.CHECKSKILL_TEACHERMARK_YES;
    }

    if ($checkgroupings) {
        if ($userid) {
            $users = $DB->get_records('user', array('id'=>$userid), null, 'id, firstname, lastname');
        } else {
            if (!$users = get_users_by_capability($context, 'mod/checkskill:updateown', 'u.id, u.firstname, u.lastname', '', '', '', '', '', false)) {
                return;
            }
        }

        $grades = array();

        // With groupings, need to update each user individually (as each has different groupings)
        foreach ($users as $userid => $user) {
            $groupings = checkskill_class::get_user_groupings($userid, $course->id);

            $total = 0;
            $itemskill = '';
            foreach ($items as $item) {
                if ($item->grouping) {
                    if (!in_array($item->grouping, $groupings)) {
                        continue;
                    }
                }
                $itemskill .= $item->id.',';
                $total++;
            }

            if (!$total) { // No items - set score to 0
                $ugrade = new stdClass;
                $ugrade->userid = $userid;
                $ugrade->rawgrade = 0;
                $ugrade->date = time();

            } else {
                $itemskill = substr($itemskill, 0, -1); // Remove trailing ','

                $sql = 'SELECT (SUM(CASE WHEN '.$where.' THEN 1 ELSE 0 END) * ? / ? ) AS rawgrade'.$date;
                $sql .= " FROM {checkskill_check} c ";
                $sql .= " WHERE c.item IN ($itemlist)";
                $sql .= ' AND c.userid = ? ';

                $ugrade = $DB->get_record_sql($sql, array($checkskill->maxgrade, $total, $userid));
                if (!$ugrade) {
                    $ugrade = new stdClass;
                    $ugrade->rawgrade = 0;
                    $ugrade->date = time();
                }
                $ugrade->userid = $userid;				
            }

            $ugrade->firstname = $user->firstname;
            $ugrade->lastname = $user->lastname;

            $grades[$userid] = $ugrade;
        }

    } else {
        // No need to check groupings, so update all student grades at once

        if ($userid) {
            $users = $userid;
        } else {
            if (!$users = get_users_by_capability($context, 'mod/checkskill:updateown', 'u.id', '', '', '', '', '', false)) {
                return;
            }
            $users = array_keys($users);
        }

        $total = count($items);

        list($usql, $uparams) = $DB->get_in_or_equal($users);
        list($isql, $iparams) = $DB->get_in_or_equal(array_keys($items));

		if ($CFG->version < 2013111800) {
            $namefields = 'u.firstname, u.lastname ';
        } else {
            $namefields = get_all_user_name_fields(true, 'u');
        }

		$sql = 'SELECT u.id AS userid, (SUM(CASE WHEN '.$where.' THEN 1 ELSE 0 END) * ? / ? ) AS rawgrade'.$date;
        $sql .= ' , u.firstname, u.lastname ';
        $sql .= ' FROM {user} u LEFT JOIN {checkskill_check} c ON u.id = c.userid';
        $sql .= " WHERE u.id $usql";
        $sql .= " AND c.item $isql";
        $sql .= ' GROUP BY u.id, u.firstname, u.lastname';

        $params = array_merge($uparams, $iparams);
        $params = array_merge(array($checkskill->maxgrade, $total), $params);

        $grades = $DB->get_records_sql($sql, $params);
    }

    foreach ($grades as $grade) {
        // Log completion of checkskill
        if ($grade->rawgrade == $checkskill->maxgrade) {
            if ($checkskill->emailoncomplete) {
                $timelimit = time() - 1 * 60 * 60; // Do not send another email if this checkskill was already 'completed' in the last hour
                $filter = "l.time > ? AND l.cmid = ? AND l.userid = ? AND l.action = 'complete'";
                $logs = get_logs($filter, array($timelimit, $cm->id, $grade->userid), '', 1, 1, $logcount);
                if ($logcount == 0) {
                    if (!isset($context)) {
                        if ($CFG->version < 2011120100) {
                            $context = get_context_instance(CONTEXT_MODULE, $cm->id);
                        } else {
                            $context = context_module::instance($cm->id);
                        }
                    }
					                    
                    //prepare email content
                    $details = new stdClass();
                    $details->user = fullname($grade);
                    $details->checkskil = s($checkskill->name);
                    $details->coursename = $course->fullname;

                     if ($checkskil->emailoncomplete == CHECKLIST_EMAIL_TEACHER || $checkskill->emailoncomplete == CHECKSKILL_EMAIL_BOTH) {
                        //email will be sended to the all teachers who have capability
                        $subj = get_string('emailoncompletesubject', 'checkskill', $details);
                        $content = get_string('emailoncompletebody', 'checkskill', $details);
                        $content .= new moodle_url('/mod/checkskill/view.php', array('id' => $cm->id));

                        if ($recipients = get_users_by_capability($context, 'mod/checkskill:emailoncomplete', 'u.*', '', '', '', '', '', false)) {
                            foreach ($recipients as $recipient) {                                
                                email_to_user($recipient, $grade, $subj, $content, '', '', '', false);
                            }
                        }
                    }
                    if ($checkskill->emailoncomplete == CHECKSKILL_EMAIL_STUDENT || $checkskill->emailoncomplete == CHECKSKILL_EMAIL_BOTH) {
                        //email will be sended to the student who complete this checkskill
                        $subj = get_string('emailoncompletesubjectown', 'checkskill', $details);
                        $content = get_string('emailoncompletebodyown', 'checkskill', $details);
                        $content .= new moodle_url('/mod/checkskill/view.php', array('id' => $cm->id));

                        $recipient_stud = $DB->get_record('user', array('id' => $grade->userid) );
                        email_to_user($recipient_stud, $grade, $subj, $content, '', '', '', false);                        
                    }
                }
            }
            if ($CFG->version > 2014051200) { // Moodle 2.7+
                $params = array(
                    'contextid' => $context->id,
                    'objectid' => $checkskill->id,
                    'userid' => $grade->userid,
                );
                $event = \mod_checkskill\event\checkskill_completed::create($params);
                $event->trigger();
            } else { // Before Moodle 2.7
                add_to_log($checkskill->course, 'checkskill', 'complete', "view.php?id={$cm->id}", $checkskill->id, $cm->id, $grade->userid);
            }
        }
        $ci = new completion_info($course);
        if ($cm->completion == COMPLETION_TRACKING_AUTOMATIC) {
            $ci->update_state($cm, COMPLETION_UNKNOWN, $grade->userid);
        }
    }

    checkskill_grade_item_update($checkskill, $grades);
}

/**
 * @param $checklist
 * @return int
 */
 function checkskill_grade_item_delete($checkskill) {
    global $CFG;
    require_once($CFG->libdir.'/gradelib.php');
    if (!isset($checkskill->courseid)) {
        $checkskill->courseid = $checkskill->course;
    }

    return grade_update('mod/checkskill', $checkskill->courseid, 'mod', 'checkskill', $checkskill->id, 0, null, array('deleted'=>1));
}

/**
 * @param $checklist
 * @param null $grades
 * @return int
 */
function checkskill_grade_item_update($checkskill, $grades=null) {
    global $CFG;
    if (!function_exists('grade_update')) { //workaround for buggy PHP versions
        require_once($CFG->libdir.'/gradelib.php');
    }

    if (!isset($checkskill->courseid)) {
        $checkskill->courseid = $checkskill->course;
    }

    $params = array('itemname'=>$checkskill->name);
    $params['gradetype'] = GRADE_TYPE_VALUE;
    $params['grademax']  = $checkskill->maxgrade;
    $params['grademin']  = 0;

    if ($grades  === 'reset') {
        $params['reset'] = true;
        $grades = null;
    }

    return grade_update('mod/checkskill', $checkskill->courseid, 'mod', 'checkskill', $checkskill->id, 0, $grades, $params);
}

/**
 * Return a small object with summary information about what a
 * user has done with a given particular instance of this module
 * Used for user activity reports.
 * $return->time = the time they did it
 * $return->info = a short text description
 *
 * @param $course
 * @param $user
 * @param $mod
 * @param $checkskill
 * @return null
 */
function checkskill_user_outline($course, $user, $mod, $checkskill) {
    global $DB, $CFG;

    $groupins_sel = '';
    if (isset($CFG->enablegroupmembersonly) && $CFG->enablegroupmembersonly && $checkskill->autopopulate) {
        $groupings = checkskill_class::get_user_groupings($user->id, $checkskill->course);
        $groupings[] = 0;
        $groupings_sel = ' AND grouping IN ('.implode(',', $groupings).') ';
    }
    $sel = 'checkskill = ? AND userid = 0 AND itemoptional = '.CHECKSKILL_OPTIONAL_NO;
    $sel .= ' AND hidden = '.CHECKSKILL_HIDDEN_NO.$groupings_sel;
    $items = $DB->get_records_select('checkskill_item', $sel, array($checkskill->id), '', 'id');
    if (!$items) {
        return null;
    }

    $total = count($items);
    list($isql, $iparams) = $DB->get_in_or_equal(array_keys($items));

    $sql = "userid = ? AND item $isql AND ";
    if ($checkskill->teacheredit == CHECKSKILL_MARKING_STUDENT) {
        $sql .= 'usertimestamp > 0';
        $order = 'usertimestamp DESC';
    } else {
        $sql .= 'teachermark = '.CHECKSKILL_TEACHERMARK_YES;
        $order = 'teachertimestamp DESC';
    }
    $params = array_merge(array($user->id), $iparams);

    $checks = $DB->get_records_select('checkskill_check', $sql, $params, $order);

    $return = null;
    if ($checks) {
        $return = new stdClass;

        $ticked = count($checks);
        $check = reset($checks);
        if ($checkskill->teacheredit == CHECKSKILL_MARKING_STUDENT) {
            $return->time = $check->usertimestamp;
        } else {
            $return->time = $check->teachertimestamp;
        }
        $percent = sprintf('%0d', ($ticked * 100) / $total);
        $return->info = get_string('progress', 'checkskill').': '.$ticked.'/'.$total.' ('.$percent.'%)';
    }

    return $return;
}

/**
 * Print a detailed representation of what a user has done with
 * a given particular instance of this module, for user activity reports.
 *
 * @param $course
 * @param $user
 * @param $mod
 * @param $checklist
 * @return boolean
 */
function checkskill_user_complete($course, $user, $mod, $checkskill) {
    $chk = new checkskill_class($mod->id, $user->id, $checkskill, $mod, $course);

    $chk->user_complete();

    return true;
}

/**
 * Given a course and a time, this module should find recent activity
 * that has occurred in checklist activities and print it out.
 * Return true if there was output, or false is there was none.
 *
 * @param $course
 * @param $isteacher
 * @param $timestart
 * @return boolean
 */
function checkskill_print_recent_activity($course, $isteacher, $timestart) {
    return false;  //  True if anything was printed, otherwise false
}

/**
 * @param $courses
 * @param $htmlarray
 */
function checkskill_print_overview($courses, &$htmlarray) {
    global $USER, $CFG, $DB;

    $config = get_config('checkskill');
    if (isset($config->showmymoodle) && !$config->showmymoodle) {
        return; // Disabled via global config.
    }
    if (!isset($config->showcompletemymoodle)) {
        $config->showcompletemymoodle = 1;
    }
    
	if (empty($courses) || !is_array($courses) || count($courses) == 0) {
        return;
    }

    if (!$checkskills = get_all_instances_in_courses('checkskill', $courses)) {
        return;
    }

    $strcheckskill = get_string('modulename', 'checkskill');

    foreach ($checkskills as $key => $checkskill) {
        $show_all = true;
        if ($checkskill->teacheredit == CHECKSKILL_MARKING_STUDENT) {
            if ($CFG->version < 2011120100) {
                $context = get_context_instance(CONTEXT_MODULE, $checkskill->coursemodule);
            } else {
                $context = context_module::instance($checkskill->coursemodule);
            }
            $show_all = !has_capability('mod/checkskill:updateown', $context);
        }

        $progressbar = checkskill_class::print_user_progressbar($checkskill->id, $USER->id,
                                                               '270px', true, true,
                                                               !$config->showcompletemymoodle);
        if (empty($progressbar)) {
            continue;
        }

        // Do not worry about hidden items / groupings as automatic items cannot have dates
        // (and manual items cannot be hidden / have groupings)
        if ($show_all) { // Show all items whether or not they are checked off (as this user is unable to check them off)
            $date_items = $DB->get_records_select('checkskill_item',
                                                  'checkskill = ? AND duetime > 0',
                                                  array($checkskill->id),
                                                  'duetime');
        } else { // Show only items that have not been checked off
            $date_items = $DB->get_records_sql('SELECT i.* FROM {checkskill_item} i JOIN {checkskill_check} c ON c.item = i.id '.
                                          'WHERE i.checkskill = ? AND i.duetime > 0 AND c.userid = ? AND usertimestamp = 0 '.
                                          'ORDER BY i.duetime', array($checkskill->id, $USER->id));
        }

        $str = '<div class="checkskill overview"><div class="name">'.$strcheckskill.': '.
            '<a title="'.$strcheckskill.'" href="'.$CFG->wwwroot.'/mod/checkskill/view.php?id='.$checkskill->coursemodule.'">'.
            $checkskill->name.'</a></div>';
        $str .= '<div class="info">'.$progressbar.'</div>';
        foreach ($date_items as $item) {
            $str .= '<div class="info">'.$item->displaytext.': ';
            if ($item->duetime > time()) {
                $str .= '<span class="itemdue">';
            } else {
                $str .= '<span class="itemoverdue">';
            }
            $str .= date('j M Y', $item->duetime).'</span></div>';
        }
        $str .= '</div>';
        if (empty($htmlarray[$checkskill->course]['checkskill'])) {
            $htmlarray[$checkskill->course]['checkskill'] = $str;
        } else {
            $htmlarray[$checkskill->course]['checkskill'] .= $str;
        }
    }
}

/**
 * Function to be run periodically according to the moodle cron
 * This function searches for things that need to be done, such
 * as sending out mail, toggling flags etc ...
 *
 * @return boolean
 * @todo Finish documenting this function
 **/
function checkskill_cron () {
    global $CFG, $DB;

    $lastcron = $DB->get_field('modules', 'lastcron', array('name' => 'checkskill'));
    if (!$lastcron) {
        // First time run - checkskills will take care of any updates before now
        return true;
    }

    require_once($CFG->dirroot.'/mod/checkskill/autoupdate.php');
    if (!$CFG->checkskill_autoupdate_use_cron) {
        mtrace("Checkskill cron updates disabled");
        return true;
    }

    $lastlogtime = $lastcron - 5; // Subtract 5 seconds just in case a log slipped through during the last cron update

	// Modif JF where checkskill differs from checklist ***********************************************************
    // Process Outcomes as Items
    $outcomesupdate = 0;
	// mtrace(" OUTOMES: ".$CFG->enableoutcomes);

    if (!empty($CFG->enableoutcomes)){
        require_once($CFG->dirroot.'/mod/checkskill/cron_outcomes.php');
        $outcomesupdate+=checkskill_cron_outcomes($lastlogtime);
    }
    if ($outcomesupdate) {
        mtrace(" Updated $outcomesupdate checkmark(s) from outcomes changes");
    }
	// *************************************************************************************************************
	
    // Find all autoupdating checkskills
    $checkskills = $DB->get_records_select('checkskill', 'autopopulate > 0 AND autoupdate > 0');
    if (!$checkskills) {
        // No checkskills to update
        mtrace("No automatic update checkskills found");
        return true;
    }

    // Match up these checkskills with the courses they are in
    $courses = array();
    foreach ($checkskills as $checkskill) {
        if (array_key_exists($checkskill->course, $courses)) {
            $courses[$checkskill->course][$checkskill->id] = $checkskill;
        } else {
            $courses[$checkskill->course] = array($checkskill->id => $checkskill);
        }
    }
    $courseids = implode(',', array_keys($courses));

    if (defined("DEBUG_CHECKSKILL_AUTOUPDATE")) {
        mtrace("Looking for updates in courses: $courseids");
    }

    // Process all logs since the last cron update
    $logupdate = 0;
    $totalcount = 0;
    $logs = get_logs("l.time >= ? AND l.course IN ($courseids) AND cmid > 0", array($lastlogtime), 'l.time ASC', '', '', $totalcount);
    if ($logs) {
        if (defined("DEBUG_CHECKSKILL_AUTOUPDATE")) {
            mtrace("Found ".count($logs)." log updates to check");
        }
        foreach ($logs as $log) {
            $logupdate += checkskill_autoupdate($log->course, $log->module, $log->action, $log->cmid, $log->userid, $log->url, $courses[$log->course]);
        }
    }

    if ($logupdate) {
        mtrace(" Updated $logupdate checkmark(s) from log changes");
    } else {
        mtrace(" No checkmarks need updating from log changes");
    }

    // Process all the completion changes since the last cron update
    // Need the cmid, userid and newstate
    $completionupdate = 0;
    list($msql, $mparam) = $DB->get_in_or_equal(array_keys($courses));
    $sql = 'SELECT c.id, c.coursemoduleid, c.userid, c.completionstate FROM {course_modules_completion} c ';
    $sql .= 'JOIN {course_modules} m ON c.coursemoduleid = m.id ';
    $sql .= "WHERE c.timemodified > ? AND m.course $msql ";
    $params = array_merge(array($lastlogtime), $mparam);
    $completions = $DB->get_records_sql($sql, $params);
    if (defined("DEBUG_CHECKSKILL_AUTOUPDATE")) {
        mtrace("Found ".count($completions)." completion updates to check");
    }
    foreach ($completions as $completion) {
        $completionupdate += checkskill_completion_autoupdate($completion->coursemoduleid,
                                                             $completion->userid,
                                                             $completion->completionstate);
    }

    if ($completionupdate) {
        mtrace(" Updated $completionupdate checkmark(s) from completion changes");
    } else {
        mtrace(" No checkmarks need updating from completion changes");
    }
    return true;
}

/**
 * Must return an array of user records (all data) who are participants
 * for a given instance of newmodule. Must include every user involved
 * in the instance, independient of his role (student, teacher, admin...)
 * See other modules as example.
 *
 * @param int $checkskillid ID of an instance of this module
 * @return mixed boolean/array of students
 */
function checkskill_get_participants($checkskillid) {
    global $DB;

    $params = array($checkskillid);
    $sql = 'SELECT DISTINCT u.id
              FROM {user} u
              JOIN {checklist_item} i ON i.userid = u.id
             WHERE i.checkskill = ?';
    $userids1 = $DB->get_records_sql($sql, $params);

    $sql = 'SELECT DISTINCT u.id
              FROM {user} u
              JOIN {checkskill_check} c ON c.userid = u.id
              JOIN {checkskill_item} i ON i.id = c.item
             WHERE i.checkskill = ?';
    $userids2 = $DB->get_records_sql($sql, $params);

    return $userids1 + $userids2;
}


/**
 * This function returns if a scale is being used by one checkskill
 * if it has support for grading and scales. Commented code should be
 * modified if necessary. See forum, glossary or journal modules
 * as reference.
 *
 * @param int $checkskillid ID of an instance of this module
 * @param int $scaleid
 * @return bool
 */
function checkskill_scale_used($checkskillid, $scaleid) {
    return false;
}

/**
 * Checks if scale is being used by any instance of checkskill.
 * This function was added in 1.9
 *
 * This is used to find out if scale used anywhere
 * @param $scaleid int
 * @return boolean True if the scale is used by any checkskill
 */
function checkskill_scale_used_anywhere($scaleid) {
    return false;
}

/**
 * Execute post-install custom actions for the module
 * This function was added in 1.9
 *
 * @return boolean true if success, false on error
 */
function checkskill_install() {
    return true;
}

/**
 * Execute post-uninstall custom actions for the module
 * This function was added in 1.9
 *
 * @return boolean true if success, false on error
 */
function checkskill_uninstall() {
    return true;
}

/**
 * @param HTML_QuickForm $mform
 */
function checkskill_reset_course_form_definition(&$mform) {
    //UT
    $mform->addElement('header', 'checkskillheader', get_string('modulenameplural', 'checkskill'));
    $mform->addElement('checkbox', 'reset_checkskill_progress', get_string('resetcheckskillprogress', 'checkskill'));
}

function checkskill_reset_course_form_defaults($course) {
    return array('reset_checkskill_progress' => 1);
}

/**
 * @param object $data
 * @return array
 */
function checkskill_reset_userdata($data) {
    global $DB;

    $status = array();
    $component = get_string('modulenameplural', 'checkskill');
    $typestr = get_string('resetcheckskillprogress', 'checkskill');
    $status[] = array('component'=>$component, 'item'=>$typestr, 'error'=>false);

    if (!empty($data->reset_checkskill_progress)) {
        $checkskills = $DB->get_records('checkskill', array('course' => $data->courseid));
        if (!$checkskills) {
            return $status;
        }

        list($csql, $cparams) = $DB->get_in_or_equal(array_keys($checkskills));
        $items = $DB->get_records_select('checkskill_item', 'checkskill '.$csql, $cparams);
        if (!$items) {
            return $status;
        }

		$itemids = array_keys($items);
        $DB->delete_records_list('checkskill_check', 'item', $items);
        $DB->delete_records_list('checkskill_comment', 'itemid', $items);

	// Modif JF where checkskill differs from checklist ***********************************************************		
        // Descriptions
        list($descsql, $descparams) = $DB->get_in_or_equal(array_keys($items));
        $descriptions = $DB->get_records_select('checkskill_description', 'itemid '.$descsql, $descparams);
        if ($descriptions) {
            // Documents
            $DB->delete_records_list('checkskill_document', 'descriptionid', $descriptions);
            // Descriptions
            $DB->delete_records_list('checkskill_description', 'id', $descriptions);
        }
        // *********************************************************************************************************

        $sql = "checklist $csql AND userid <> 0";
        $DB->delete_records_select('checkskill_item', $sql, $cparams);

        // Reset the grades
        foreach ($checkskills as $checkskill) {
            checkskill_grade_item_update($checkskill, 'reset');
        }
    }

    return $status;
}

/**
 * @param int $courseid
 * @return bool
 */
function checkskill_refresh_events($courseid = 0) {
    global $DB;

    if ($courseid) {
        $checkskills = $DB->get_records('checkskill', array('course'=> $courseid) );
        $course = $DB->get_record('course', array('id' => $courseid) );
    } else {
        $checkskills = $DB->get_records('checkskill');
        $course = null;
    }

    foreach ($checkskills as $checkskill) {
        if ($checkskill->duedatesoncalendar) {
            $cm = get_coursemodule_from_instance('checkskill', $checkskill->id, $checkskill->course);
            $chk = new checkskill_class($cm->id, 0, $checkskill, $cm, $course);
            $chk->setallevents();
        }
    }

    return true;
}

function checkskill_supports($feature) {
    switch($feature) {
    case FEATURE_GROUPS:                  return true;
    case FEATURE_GROUPINGS:               return true;
    case FEATURE_GROUPMEMBERSONLY:        return true;
    case FEATURE_MOD_INTRO:               return true;
    case FEATURE_GRADE_HAS_GRADE:         return true;
    case FEATURE_COMPLETION_HAS_RULES:    return true;
    case FEATURE_BACKUP_MOODLE2:          return true;
    case FEATURE_SHOW_DESCRIPTION:        return true;
	
    default: return null;
    }
}

function checkskill_get_completion_state($course, $cm, $userid, $type) {
    global $DB;

    if (!($checkskill=$DB->get_record('checkskill', array('id'=>$cm->instance)))) {
        throw new Exception("Can't find checkskill {$cm->instance}");
    }

    $result=$type; // Default return value

    if ($checkskill->completionpercent) {
        list($ticked, $total) = checkskill_class::get_user_progress($cm->instance, $userid);
        $value = $checkskill->completionpercent <= ($ticked * 100 / $total);
        if ($type == COMPLETION_AND) {
            $result = $result && $value;
        } else {
            $result = $result || $value;
        }
    }

    return $result;
}
