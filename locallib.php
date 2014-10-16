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
 * Stores all the functions for manipulating a checkskill
 * @package  mod/checkskill borrowed from package checkskill from David Smith <moodle@davosmith.co.uk> 
 * @author  Jean Fruitet <jean.fruitet@univ-nantes.fr>
 * 
 * New function view_select_export
 * New function select_items, *_description, *_document
 * Functions modified : view_tabs, view, deleteitem

 */

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/lib.php');

define("CHECKSKILL_TEXT_INPUT_WIDTH", 45);
define("CHECKSKILL_OPTIONAL_NO", 0);
define("CHECKSKILL_OPTIONAL_YES", 1);
define("CHECKSKILL_OPTIONAL_HEADING", 2);
//define("CHECKSKILL_OPTIONAL_DISABLED", 3);  // Removed as new 'hidden' field added
//define("CHECKSKILL_OPTIONAL_HEADING_DISABLED", 4);

define("CHECKSKILL_HIDDEN_NO", 0);
define("CHECKSKILL_HIDDEN_MANUAL", 1);
define("CHECKSKILL_HIDDEN_BYMODULE", 2);

class checkskill_class {
    var $cm;
    var $course;
    var $checkskill;
    var $strcheckskills;
    var $strcheckskill;
    var $context;
    var $userid;
    var $items;
    var $useritems;
    var $useredit;
    var $additemafter;
    var $editdates;
    var $groupings;

    var $description = array(array()); // for  each item, each user, id of description object;
    
    /**
     * @param int|string $cmid optional
     * @param int $userid optional
     * @param object $checkskill optional
     * @param object $cm optional
     * @param object $course optional
     */
    function __construct($cmid = 'staticonly', $userid = 0, $checkskill = null, $cm = null, $course = null) {
        global $COURSE, $DB, $CFG;

        if ($cmid == 'staticonly') {
            //use static functions only!
            return;
        }

        $this->userid = $userid;

        if ($cm) {
            $this->cm = $cm;
        } else if (! $this->cm = get_coursemodule_from_id('checkskill', $cmid)) {
            print_error(get_string('error_cmid', 'checkskill')); // 'Course Module ID was incorrect'
        }

        if ($CFG->version < 2011120100) {
            $this->context = get_context_instance(CONTEXT_MODULE, $this->cm->id);
        } else {
            $this->context = context_module::instance($this->cm->id);
        }

        if ($course) {
            $this->course = $course;
        } else if ($this->cm->course == $COURSE->id) {
            $this->course = $COURSE;
        } else if (! $this->course = $DB->get_record('course', array('id' => $this->cm->course) )) {
            print_error(get_string('error_course', 'checkskill')); // 'Course is misconfigured'
        }

        if ($checkskill) {
            $this->checkskill = $checkskill;
        } else if (! $this->checkskill = $DB->get_record('checkskill', array('id' => $this->cm->instance) )) {
            print_error(get_string('error_checkskill_id', 'checkskill')); // 'checkskill ID was incorrect');
        }

        if (isset($CFG->enablegroupmembersonly) && $CFG->enablegroupmembersonly && $checkskill->autopopulate && $userid) {
            $this->groupings = self::get_user_groupings($userid, $this->course->id);
        } else {
            $this->groupings = false;
        }

        $this->strcheckskill = get_string('modulename', 'checkskill');
        $this->strcheckskills = get_string('modulenameplural', 'checkskill');
        $this->pagetitle = strip_tags($this->course->shortname.': '.$this->strcheckskill.': '.format_string($this->checkskill->name,true));

        $this->get_items();

        if ($this->checkskill->autopopulate) {
            $this->update_items_from_course();
        }
    }

    /**
     * Get an array of the items in a checkskill
     *
     */
    function get_items() {
        global $DB;

        // Load all shared checkskill items
        $this->items = $DB->get_records('checkskill_item', array('checkskill' => $this->checkskill->id, 'userid' => 0), 'position');

        // Makes sure all items are numbered sequentially, starting at 1
        $this->update_item_positions();

        // Load student's own checkskill items
        if ($this->userid && $this->canaddown()) {
            $this->useritems = $DB->get_records('checkskill_item', array('checkskill' => $this->checkskill->id, 
																		'userid' => $this->userid), 'position, id');
        } else {
            $this->useritems = false;
        }

        // Load the currently checked-off items
        if ($this->userid) { // && ($this->canupdateown() || $this->canviewreports() )) {
            $sql = 'SELECT i.id, c.usertimestamp, c.teachermark, c.teachertimestamp, c.teacherid FROM {checkskill_item} i LEFT JOIN {checkskill_check} c ';
            $sql .= 'ON (i.id = c.item AND c.userid = ?) WHERE i.checkskill = ? ';

            $checks = $DB->get_records_sql($sql, array($this->userid, $this->checkskill->id));

            foreach ($checks as $check) {
                $id = $check->id;

                if (isset($this->items[$id])) {
                    $this->items[$id]->checked = $check->usertimestamp > 0;
                    $this->items[$id]->teachermark = $check->teachermark;
                    $this->items[$id]->usertimestamp = $check->usertimestamp;
                    $this->items[$id]->teachertimestamp = $check->teachertimestamp;
					$this->items[$id]->teacherid = $check->teacherid;
                } else if ($this->useritems && isset($this->useritems[$id])) {
                    $this->useritems[$id]->checked = $check->usertimestamp > 0;
                    $this->useritems[$id]->usertimestamp = $check->usertimestamp;
                    // User items never have a teacher mark to go with them
                }
            }
        }
    }

    /**
     * Loop through all activities / resources in course and check they
     * are in the current checkskill (in the right order)
     *
     */
    function update_items_from_course() {
        global $DB, $CFG;

        $mods = get_fast_modinfo($this->course);

        $section = 1;
        $nextpos = 1;
        $changes = false;
        reset($this->items);

        $importsection = -1;
        if ($this->checkskill->autopopulate == CHECKSKILL_AUTOPOPULATE_SECTION) {
            foreach ($mods->get_sections() as $num => $section) {
                if (in_array($this->cm->id, $section)) {
                    $importsection = $num;
                    $section = $importsection;
                    break;
                }
            }
        }

        $groupmembersonly = isset($CFG->enablegroupmembersonly) && $CFG->enablegroupmembersonly;

        $numsections = 1;
        $courseformat = null;
        if ($CFG->version >= 2012120300) {
            $courseformat = course_get_format($this->course);
            $opts = $courseformat->get_format_options();
            if (isset($opts['numsections'])) {
                $numsections = $opts['numsections'];
            }
        } else {
            $numsections = $this->course->numsections;
        }
        $sections = $mods->get_sections();
        while ($section <= $numsections || $section == $importsection) {
            if (!array_key_exists($section, $sections)) {
                $section++;
                continue;
            }

            if ($importsection >= 0 && $importsection != $section) {
                $section++; // Only importing the section with the checkskill in it
                continue;
            }

            $sectionheading = 0;
            while (list($itemid, $item) = each($this->items)) {
                // Search from current position
                if (($item->moduleid == $section) && ($item->itemoptional == CHECKSKILL_OPTIONAL_HEADING)) {
                    $sectionheading = $itemid;
                    break;
                }
            }

            if (!$sectionheading) {
                // Search again from the start
                foreach ($this->items as $item) {
                    if (($item->moduleid == $section) && ($item->itemoptional == CHECKSKILL_OPTIONAL_HEADING)) {
                        $sectionheading = $itemid;
                        break;
                    }
                }
                reset($this->items);
            }

            $sectionname = '';
            if ($CFG->version >= 2012120300) {
                $sectionname = $courseformat->get_section_name($section);
            }
            if (trim($sectionname) == '') {
                $sectionname = get_string('section').' '.$section;
            }
            if (!$sectionheading) {
                //echo 'adding section '.$section.'<br/>';
                $sectionheading = $this->additem($sectionname, 0, 0, false, false, $section, CHECKSKILL_OPTIONAL_HEADING);
                reset($this->items);
            } else {
                if ($this->items[$sectionheading]->displaytext != $sectionname) {
                    $this->updateitemtext($sectionheading, $sectionname);
                }
            }

            if ($sectionheading) {
                $this->items[$sectionheading]->stillexists = true;

                if ($this->items[$sectionheading]->position < $nextpos) {
                    $this->moveitemto($sectionheading, $nextpos, true);
                    reset($this->items);
                }
                $nextpos = $this->items[$sectionheading]->position + 1;
            }

            foreach($sections[$section] as $cmid) {
                if ($this->cm->id == $cmid) {
                    continue; // Do not include this checkskill in the list of modules
                }
                if ($mods->get_cm($cmid)->modname == 'label') {
                    continue; // Ignore any labels
                }

                $foundit = false;
                while(list($itemid, $item) = each($this->items)) {
                    // Search list from current position (will usually be the next item)
                    if (($item->moduleid == $cmid) && ($item->itemoptional != CHECKSKILL_OPTIONAL_HEADING)) {
                        $foundit = $item;
                        break;
                    }
                    if (($item->moduleid == 0) && ($item->position == $nextpos)) {
                        // Skip any items that are not linked to modules
                        $nextpos++;
                    }
                }
                if (!$foundit) {
                    // Search list again from the start (just in case)
                    foreach($this->items as $item) {
                        if (($item->moduleid == $cmid) && ($item->itemoptional != CHECKSKILL_OPTIONAL_HEADING)) {
                            $foundit = $item;
                            break;
                        }
                    }
                    reset($this->items);
                }
                $modname = $mods->get_cm($cmid)->name;
                if ($foundit) {
                    $item->stillexists = true;
                    if ($item->position != $nextpos) {
                        //echo 'reposition '.$item->displaytext.' => '.$nextpos.'<br/>';
                        $this->moveitemto($item->id, $nextpos, true);
                        reset($this->items);
                    }
                    if ($item->displaytext != $modname) {
                        $this->updateitemtext($item->id, $modname);
                    }
                    if (($item->hidden == CHECKSKILL_HIDDEN_BYMODULE) && $mods->get_cm($cmid)->visible) {
                        // Course module was hidden and now is not
                        $item->hidden = CHECKSKILL_HIDDEN_NO;
                        $upd = new stdClass;
                        $upd->id = $item->id;
                        $upd->hidden = $item->hidden;
                        $DB->update_record('checkskill_item', $upd);
                        $changes = true;

                    } else if (($item->hidden == CHECKSKILL_HIDDEN_NO) && !$mods->get_cm($cmid)->visible) {
                        // Course module is now hidden
                        $item->hidden = CHECKSKILL_HIDDEN_BYMODULE;
                        $upd = new stdClass;
                        $upd->id = $item->id;
                        $upd->hidden = $item->hidden;
                        $DB->update_record('checkskill_item', $upd);
                        $changes = true;
                    }

                    $groupingid = $mods->get_cm($cmid)->groupingid;
                    if ($groupmembersonly && $groupingid && $mods->get_cm($cmid)->groupmembersonly) {
                        if ($item->grouping != $groupingid) {
                            $item->grouping = $groupingid;
                            $upd = new stdClass;
                            $upd->id = $item->id;
                            $upd->grouping = $groupingid;
                            $DB->update_record('checkskill_item', $upd);
                            $changes = true;
                        }
                    } else {
                        if ($item->grouping) {
                            $item->grouping = 0;
                            $upd = new stdClass;
                            $upd->id = $item->id;
                            $upd->grouping = 0;
                            $DB->update_record('checkskill_item', $upd);
                            $changes = true;
                        }
                    }
                } else {
                    //echo '+++adding item '.$name.' at '.$nextpos.'<br/>';
                    $hidden = $mods->get_cm($cmid)->visible ? CHECKSKILL_HIDDEN_NO : CHECKSKILL_HIDDEN_BYMODULE;
                    $itemid = $this->additem($modname, 0, 0, $nextpos, false, $cmid, CHECKSKILL_OPTIONAL_NO, $hidden);
                    $changes = true;
                    reset($this->items);
                    $this->items[$itemid]->stillexists = true;
                    $this->items[$itemid]->grouping = ($groupmembersonly && $mods->get_cm($cmid)->groupmembersonly) ? $mods->get_cm($cmid)->groupingid : 0;
                    $item = $this->items[$itemid];
                }
                $item->modulelink = new moodle_url('/mod/'.$mods->get_cm($cmid)->modname.'/view.php', array('id' => $cmid));
                $nextpos++;
            }

            $section++;
        }

        // Delete any items that are related to activities / resources that have been deleted
        if ($this->items) {
            foreach($this->items as $item) {
                if ($item->moduleid && !isset($item->stillexists)) {
                    //echo '---deleting item '.$item->displaytext.'<br/>';
                    $this->deleteitem($item->id, true);
                    $changes = true;
                }
            }
        }

        if ($changes) {
            $this->update_all_autoupdate_checks();
        }
    }

    function removeauto() {
        if ($this->checkskill->autopopulate) {
            return; // Still automatically populating the checkskill, so don't remove the items
        }

        if (!$this->canedit()) {
            return;
        }

        if ($this->items) {
            foreach ($this->items as $item) {
                if ($item->moduleid) {
                    $this->deleteitem($item->id);
                }
            }
        }
    }

    /**
     * Check all items are numbered sequentially from 1
     * then, move any items between $start and $end
     * the number of places indicated by $move
     *
     * @param $move (optional) - how far to offset the current positions
     * @oaram $start (optional) - where to start offsetting positions
     * @param $end (optional) - where to stop offsetting positions
     */
    function update_item_positions($move=0, $start=1, $end=false) {
        global $DB;

        $pos = 1;

        if (!$this->items) {
            return;
        }
        foreach($this->items as $item) {
            if ($pos == $start) {
                $pos += $move;
                $start = -1;
            }
            if ($item->position != $pos) {
                $oldpos = $item->position;
                $item->position = $pos;
                $upditem = new stdClass;
                $upditem->id = $item->id;
                $upditem->position = $pos;
                $DB->update_record('checkskill_item', $upditem);
                if ($oldpos == $end) {
                    break;
                }
            }
            $pos++;
        }
    }

    /**
     * @param int $position
     * @return bool|object
     */
	 function get_item_at_position($position) {
        if (!$this->items) {
            return false;
        }
        foreach ($this->items as $item) {
            if ($item->position == $position) {
                return $item;
            }
        }
        return false;
    }

    function canupdateown() {
        global $USER;
        return (!$this->userid || ($this->userid == $USER->id)) && has_capability('mod/checkskill:updateown', $this->context);
    }

    function canaddown() {
        global $USER;
        return $this->checkskill->useritemsallowed && (!$this->userid || ($this->userid == $USER->id)) && has_capability('mod/checkskill:updateown', $this->context);
    }

    function canpreview() {
        return has_capability('mod/checkskill:preview', $this->context);
    }

    function canedit() {
        return has_capability('mod/checkskill:edit', $this->context);
    }

    function caneditother() {
        return has_capability('mod/checkskill:updateother', $this->context);
    }

    function canviewreports() {
        return has_capability('mod/checkskill:viewreports', $this->context) || has_capability('mod/checkskill:viewmenteereports', $this->context);
    }

    function only_view_mentee_reports() {
        return has_capability('mod/checkskill:viewmenteereports', $this->context) && !has_capability('mod/checkskill:viewreports', $this->context);
    }

    // Test if the current user is a mentor of the passed in user id
    static function is_mentor($userid) {
        global $USER, $DB;

        $sql = 'SELECT c.instanceid
                  FROM {role_assignments} ra
                  JOIN {context} c ON ra.contextid = c.id
                 WHERE c.contextlevel = '.CONTEXT_USER.'
                   AND ra.userid = ?
                   AND c.instanceid = ?';
        return $DB->record_exists_sql($sql, array($USER->id, $userid));
    }

    // Takes a skill of userids and returns only those that the current user
    // is a mentor for (ones where the current user is assigned a role in their
    // user context)
    static function filter_mentee_users($userids) {
        global $DB, $USER;

        list($usql, $uparams) = $DB->get_in_or_equal($userids);
        $sql = 'SELECT c.instanceid
                  FROM {role_assignments} ra
                  JOIN {context} c ON ra.contextid = c.id
                 WHERE c.contextlevel = '.CONTEXT_USER.'
                   AND ra.userid = ?
                   AND c.instanceid '.$usql;
        $params = array_merge(array($USER->id), $uparams);
        return $DB->get_fieldset_sql($sql, $params);
    }

    function view() {
        global $CFG, $OUTPUT;

        if ((!$this->items) && $this->canedit()) {
            redirect(new moodle_url('/mod/checkskill/edit.php', array('id' => $this->cm->id)) );
        }

        if ($this->canupdateown()) {
            $currenttab = 'view';
        } else if ($this->canpreview()) {
            $currenttab = 'preview';
        } else {
            if ($this->canviewreports()) { // No editing, but can view reports
                redirect(new moodle_url('/mod/checkskill/report.php', array('id' => $this->cm->id)));
            } else {
                $this->view_header();

                echo $OUTPUT->heading(format_string($this->checkskill->name));
                echo $OUTPUT->confirm('<p>' . get_string('guestsno', 'checkskill') . "</p>\n\n<p>" .
                                      get_string('liketologin') . "</p>\n", get_login_url(), get_referer(false));
                echo $OUTPUT->footer();
                die;
            }
            $currenttab = '';
        }

        $this->view_header();

        echo $OUTPUT->heading(format_string($this->checkskill->name));

        $this->view_tabs($currenttab);

		if ($CFG->version > 2014051200) { // Moodle 2.7+
            $params = array(
                'contextid' => $this->context->id,
                'objectid' => $this->checkskill->id,
            );
            $event = \mod_checkskill\event\course_module_viewed::create($params);
            $event->trigger();
        } else { // Before Moodle 2.7
        	add_to_log($this->course->id, 'checkskill', 'view', "view.php?id={$this->cm->id}", $this->checkskill->name, $this->cm->id);
        }

        if ($this->canupdateown()) {
            $this->process_view_actions();
        }

		// Modif JF where checkskill differs from checkskill ***********************************************************		
		// Mahara && Portofolio stuff
        if (!empty($CFG->enableportfolios) && !empty($this->userid)){
            require_once($CFG->libdir.'/portfoliolib.php');
            $button = new portfolio_add_button();
            if ($CFG->version < 2012120304){ // VERSION Moodle 2.3
				$button->set_callback_options('checkskill_portfolio_caller',
				array('instanceid' => $this->checkskill->id, 'userid' => $this->userid, 'export_format' => ''), '/mod/checkskill/mahara/locallib_portfolio.php');
			}
			else { // Version Moodle 2.4
				$button->set_callback_options('checkskill_portfolio_caller',
				array('instanceid' => $this->checkskill->id, 'userid' => $this->userid, 'export_format' => ''), 'mod_checkskill');
			}
			$button->set_formats(array(PORTFOLIO_FORMAT_PLAINHTML, PORTFOLIO_FORMAT_LEAP2A));
            echo "<div align=\"center\">".$button->to_html(PORTFOLIO_ADD_ICON_LINK)."</div>\n";
        }
	// ***************************************************************************************************************
        $this->view_items();

        $this->view_footer();
    }


    function edit() {
        global $OUTPUT, $CFG;

        if (!$this->canedit()) {
            redirect(new moodle_url('/mod/checkskill/view.php', array('id' => $this->cm->id)) );
        }

       if ($CFG->version > 2014051200) { // Moodle 2.7+
            $params = array(
                'contextid' => $this->context->id,
                'objectid' => $this->checkskill->id,
            );
            $event = \mod_checkskill\event\edit_page_viewed::create($params);
            $event->trigger();
        } else { // Before Moodle 2.7
	        add_to_log($this->course->id, "checkskill", "edit", "edit.php?id={$this->cm->id}", $this->checkskill->name, $this->cm->id);
		}

        $this->view_header();

        echo $OUTPUT->heading(format_string($this->checkskill->name));

        $this->view_tabs('edit');

        $this->process_edit_actions();

        if ($this->checkskill->autopopulate) {
            // Needs to be done again, just in case the edit actions have changed something
            $this->update_items_from_course();
        }

        $this->view_import_export();

        $this->view_edit_items();

        $this->view_footer();
    }

    function report() {
        global $OUTPUT, $CFG;

        if ((!$this->items) && $this->canedit()) {
            redirect(new moodle_url('/mod/checkskill/edit.php', array('id' => $this->cm->id)) );
        }

        if (!$this->canviewreports()) {
            redirect(new moodle_url('/mod/checkskill/view.php', array('id' => $this->cm->id)) );
        }

        if ($this->userid && $this->only_view_mentee_reports()) {
            // Check this user is a mentee of the logged in user
            if (!$this->is_mentor($this->userid)) {
                $this->userid = false;
            }

        } else if (!$this->caneditother()) {
            $this->userid = false;
        }

        $this->view_header();

        echo $OUTPUT->heading(format_string($this->checkskill->name));

        $this->view_tabs('report');

        $this->process_report_actions();

         if ($CFG->version > 2014051200) { // Moodle 2.7+
            $params = array(
                'contextid' => $this->context->id,
                'objectid' => $this->checkskill->id,
            );
            if ($this->userid) {
                $params['relateduserid'] = $this->userid;
            }
            $event = \mod_checkskill\event\report_viewed::create($params);
            $event->trigger();
        } else { // Before Moodle 2.7
            $url = "report.php?id={$this->cm->id}";
            if ($this->userid) {
                $url .= "&studentid={$this->userid}";
            }
            add_to_log($this->course->id, "checkskill", "report", $url, $this->checkskill->id, $this->cm->id);
        }

        if ($this->userid) {
            $this->view_items(true);
        } else {
            $this->view_report();
        }

        $this->view_footer();
    }

    function user_complete() {
        $this->view_items(false, true);
    }

    function view_header() {
        global $PAGE, $OUTPUT;

        $PAGE->set_title($this->pagetitle);
        $PAGE->set_heading($this->course->fullname);

        echo $OUTPUT->header();
    }

    function view_tabs($currenttab) {
        $tabs = array();
        $row = array();
        $inactive = array();
        $activated = array();

        if ($this->canupdateown()) {
            $row[] = new tabobject('view', new moodle_url('/mod/checkskill/view.php', array('id' => $this->cm->id)), get_string('view', 'checkskill'));
        } else if ($this->canpreview()) {
            $row[] = new tabobject('preview', new moodle_url('/mod/checkskill/view.php', array('id' => $this->cm->id)), get_string('preview', 'checkskill'));
        }
        if ($this->canviewreports()) {
            $row[] = new tabobject('report', new moodle_url('/mod/checkskill/report.php', array('id' => $this->cm->id)), get_string('report', 'checkskill'));
        }
        if ($this->canedit()) {
            $row[] = new tabobject('edit', new moodle_url('/mod/checkskill/edit.php', array('id' => $this->cm->id)), get_string('edit', 'checkskill'));
        }

        if (count($row) == 1) {
            // No tabs for students
        } else {
            $tabs[] = $row;
        }

        if ($currenttab == 'report') {
            $activated[] = 'report';
        }

        if ($currenttab == 'edit') {
            $activated[] = 'edit';

            if (!$this->items) {
                $inactive = array('view', 'report', 'preview');
            }
        }

        if ($currenttab == 'preview') {
            $activated[] = 'preview';
        }

        print_tabs($tabs, $currenttab, $inactive, $activated);
    }

    function view_progressbar() {
        global $OUTPUT;

        if (!$this->items) {
            return;
        }

        $teacherprogress = ($this->checkskill->teacheredit != CHECKSKILL_MARKING_STUDENT);

        $totalitems = 0;
        $requireditems = 0;
        $completeitems = 0;
        $allcompleteitems = 0;
        $checkgroupings = $this->checkskill->autopopulate && ($this->groupings !== false);
        foreach ($this->items as $item) {
            if (($item->itemoptional == CHECKSKILL_OPTIONAL_HEADING)||($item->hidden)) {
                continue;
            }
            if ($checkgroupings && $item->grouping) {
                if (!in_array($item->grouping, $this->groupings)) {
                    continue; // Current user is not a member of this item's grouping
                }
            }
            if ($item->itemoptional == CHECKSKILL_OPTIONAL_NO) {
                $requireditems++;
                if ($teacherprogress) {
                    if ($item->teachermark == CHECKSKILL_TEACHERMARK_YES) {
                        $completeitems++;
                        $allcompleteitems++;
                    }
                } else if ($item->checked) {
                    $completeitems++;
                    $allcompleteitems++;
                }
            } else if ($teacherprogress) {
                if ($item->teachermark == CHECKSKILL_TEACHERMARK_YES) {
                    $allcompleteitems++;
                }
            } else if ($item->checked) {
                $allcompleteitems++;
            }
            $totalitems++;
        }
        if (!$teacherprogress) {
            if ($this->useritems) {
                foreach ($this->useritems as $item) {
                    if ($item->checked) {
                        $allcompleteitems++;
                    }
                    $totalitems++;
                }
            }
        }
        if ($totalitems == 0) {
            return;
        }

        $allpercentcomplete = ($allcompleteitems * 100) / $totalitems;

        if ($requireditems > 0 && $totalitems > $requireditems) {
            $percentcomplete = ($completeitems * 100) / $requireditems;
            echo '<div style="display:block; float:left; width:150px;" class="checkskill_progress_heading">';
            echo get_string('percentcomplete','checkskill').':&nbsp;';
            echo '</div>';
            echo '<span id="checkskillprogressrequired">';
            echo '<div class="checkskill_progress_outer">';
            echo '<div class="checkskill_progress_inner" style="width:'.$percentcomplete.'%; background-image: url('.$OUTPUT->pix_url('progress','checkskill').');" >&nbsp;</div>';
            echo '<div class="checkskill_progress_anim" style="width:'.$percentcomplete.'%; background-image: url('.$OUTPUT->pix_url('progress-fade', 'checkskill').');" >&nbsp;</div>';
            echo '</div>';
            echo '<span class="checkskill_progress_percent">&nbsp;'.sprintf('%0d',$percentcomplete).'% </span>';
            echo '</span>';
            echo '<br style="clear:both"/>';
        }

        echo '<div style="display:block; float:left; width:150px;" class="checkskill_progress_heading">';
        echo get_string('percentcompleteall','checkskill').':&nbsp;';
        echo '</div>';
        echo '<span id="checkskillprogressall">';
        echo '<div class="checkskill_progress_outer">';
        echo '<div class="checkskill_progress_inner" style="width:'.$allpercentcomplete.'%; background-image: url('.$OUTPUT->pix_url('progress','checkskill').');" >&nbsp;</div>';
        echo '<div class="checkskill_progress_anim" style="width:'.$allpercentcomplete.'%; background-image: url('.$OUTPUT->pix_url('progress-fade', 'checkskill').');" >&nbsp;</div>';
        echo '</div>';
        echo '<span class="checkskill_progress_percent">&nbsp;'.sprintf('%0d',$allpercentcomplete).'% </span>';
        echo '</span>';
        echo '<br style="clear:both"/>';
    }

    function get_teachermark($itemid) {
        global $OUTPUT;

        if (!isset($this->items[$itemid])) {
            return array('','');
        }
        switch ($this->items[$itemid]->teachermark) {
        case CHECKSKILL_TEACHERMARK_YES:
            return array($OUTPUT->pix_url('tick_box','checkskill'),get_string('teachermarkyes','checkskill'));

        case CHECKSKILL_TEACHERMARK_NO:
            return array($OUTPUT->pix_url('cross_box','checkskill'),get_string('teachermarkno','checkskill'));

        default:
            return array($OUTPUT->pix_url('empty_box','checkskill'),get_string('teachermarkundecided','checkskill'));
        }
    }

    function view_items($viewother = false, $userreport = false) {
        global $DB, $OUTPUT, $PAGE, $CFG;
		global $USER; // Modif JF
        echo $OUTPUT->box_start('generalbox boxwidthwide boxaligncenter checkskillbox');

		echo html_writer::tag('div', '&nbsp;', array('id' => 'checkskillspinner'));
		
        $comments = $this->checkskill->teachercomments;
        $editcomments = false;
        $thispage = new moodle_url('/mod/checkskill/view.php', array('id' => $this->cm->id) );

        $teachermarklocked = false;
        $showcompletiondates = false;
        $strteachername = '';
        $struserdate = '';
        $strteacherdate = '';		
        if ($viewother) {
            if ($comments) {
                $editcomments = optional_param('editcomments', false, PARAM_BOOL);
            }
            $thispage = new moodle_url('/mod/checkskill/report.php', array('id' => $this->cm->id, 'studentid' => $this->userid) );

            if (!$student = $DB->get_record('user', array('id' => $this->userid) )) {
                print_error('error_user', 'checkskill');
            }

            echo '<h2>'.get_string('checkskillfor','checkskill').' '.fullname($student, true).'</h2>';
            echo '&nbsp;';
            echo '<form style="display: inline;" action="'.$thispage->out_omit_querystring().'" method="get">';
            echo html_writer::input_hidden_params($thispage, array('studentid'));
            echo '<input type="submit" name="viewall" value="'.get_string('viewall','checkskill').'" />';
            echo '</form>';

            if (!$editcomments) {
                echo '<form style="display: inline;" action="'.$thispage->out_omit_querystring().'" method="get">';
                echo html_writer::input_hidden_params($thispage);
                echo '<input type="hidden" name="editcomments" value="on" />';
                echo ' <input type="submit" name="viewall" value="'.get_string('addcomments','checkskill').'" />';
                echo '</form>';
            }
            echo '<form style="display: inline;" action="'.$thispage->out_omit_querystring().'" method="get">';
            echo html_writer::input_hidden_params($thispage);
            echo '<input type="hidden" name="sesskey" value="'.sesskey().'" />';
            echo '<input type="hidden" name="action" value="toggledates" />';
            echo ' <input type="submit" name="toggledates" value="'.get_string('toggledates','checkskill').'" />';
            echo '</form>';

            $teachermarklocked = $this->checkskill->lockteachermarks && !has_capability('mod/checkskill:updatelocked', $this->context);

            $reportsettings = $this->get_report_settings();
            $showcompletiondates = $reportsettings->showcompletiondates;

            $strteacherdate = get_string('teacherdate', 'mod_checkskill');
            $struserdate = get_string('userdate', 'mod_checkskill');
            $strteachername = get_string('teacherid', 'mod_checkskill');

            if ($showcompletiondates) {
                $teacherids = array();
                foreach ($this->items as $item) {
                    if ($item->teacherid) {
                        $teacherids[$item->teacherid] = $item->teacherid;
                    }
                }
                if ($CFG->version < 2013111800) {
                    $fields = 'firstname, lastname';
                } else {
                    $fields = get_all_user_name_fields(true);
                }
                $teachers = $DB->get_records_list('user', 'id', $teacherids, '', 'id, '.$fields);
                foreach ($this->items as $item) {
                    if (isset($teachers[$item->teacherid])) {
                        $item->teachername = fullname($teachers[$item->teacherid]);
                    } else {
                        $item->teachername = false;
                    }
                }
            }			
        }


        $intro = file_rewrite_pluginfile_urls($this->checkskill->intro, 'pluginfile.php', $this->context->id, 'mod_checkskill', 'intro', null);
        $opts = array('trusted' => $CFG->enabletrusttext);
        echo format_text($intro, $this->checkskill->introformat, $opts);
        echo '<br/>';

        $showteachermark = false;
        $showcheckbox = true;
        if ($this->canupdateown() || $viewother || $userreport) {
            $this->view_progressbar();
            $showteachermark = ($this->checkskill->teacheredit == CHECKSKILL_MARKING_TEACHER) || ($this->checkskill->teacheredit == CHECKSKILL_MARKING_BOTH);
            $showcheckbox = ($this->checkskill->teacheredit == CHECKSKILL_MARKING_STUDENT) || ($this->checkskill->teacheredit == CHECKSKILL_MARKING_BOTH);
            $teachermarklocked = $teachermarklocked && $showteachermark; // Make sure this is OFF, if not showing teacher marks
        }
        $overrideauto = ($this->checkskill->autoupdate != CHECKSKILL_AUTOUPDATE_YES);
        $checkgroupings = $this->checkskill->autopopulate && ($this->groupings !== false);

        if (!$this->items) {
            print_string('noitems','checkskill');
        } else {
            $focusitem = false;
            $updateform = ($showcheckbox && $this->canupdateown() && !$viewother && !$userreport) || ($viewother && ($showteachermark || $editcomments));
            $addown = $this->canaddown() && $this->useredit;
            if ($updateform) {
                if ($this->canaddown() && !$viewother) {
                    echo '<form style="display:inline;" action="'.$thispage->out_omit_querystring().'" method="get">';
                    echo html_writer::input_hidden_params($thispage);
                    if ($addown) {
                        $thispage->param('useredit','on'); // Switch on for any other forms on this page (but off if this form submitted)
                        echo '<input type="submit" name="submit" value="'.get_string('addownitems-stop','checkskill').'" />';
                    } else {
                        echo '<input type="hidden" name="useredit" value="on" />';
                        echo '<input type="submit" name="submit" value="'.get_string('addownitems','checkskill').'" />';
                    }
                    echo '</form>';
                }

                if (!$viewother) {
                    // Load the Javascript required to send changes back to the server (without clicking 'save')
                    if ($CFG->version < 2012120300) { // < Moodle 2.4
                        $jsmodule = array(
                            'name' => 'mod_checkskill',
                            'fullpath' => new moodle_url('/mod/checkskill/updatechecks.js')
                        );
                        $PAGE->requires->yui2_lib('dom');
                        $PAGE->requires->yui2_lib('event');
                        $PAGE->requires->yui2_lib('connection');
                        $PAGE->requires->yui2_lib('animation');
                    } else {
                        $jsmodule = array(
                            'name' => 'mod_checkskill',
                            'fullpath' => new moodle_url('/mod/checkskill/updatechecks24.js')
                        );
                    }
                    $updatechecksurl = new moodle_url('/mod/checkskill/updatechecks.php');
                    $updateprogress = $showteachermark ? 0 : 1; // Progress bars should only be updated with 'student only' checkskills
                    $PAGE->requires->js_init_call('M.mod_checkskill.init', array($updatechecksurl->out(), sesskey(), $this->cm->id, $updateprogress), true, $jsmodule);
                }

                echo '<form action="'.$thispage->out_omit_querystring().'" method="post">';
                echo html_writer::input_hidden_params($thispage);
                echo '<input type="hidden" name="action" value="updatechecks" />';
                echo '<input type="hidden" name="sesskey" value="'.sesskey().'" />';
            }

            if ($this->useritems) {
                reset($this->useritems);
            }

			$commentusers = array();
            if ($comments) {
                list($isql, $iparams) = $DB->get_in_or_equal(array_keys($this->items));
                $params = array_merge(array($this->userid), $iparams);
                $commentsunsorted = $DB->get_records_select('checkskill_comment',"userid = ? AND itemid $isql", $params);
                $commentuserids = array();
                if (!empty($commentsunsorted)) {
                    $comments = array();
                    foreach ($commentsunsorted as $comment) {
                        $comments[$comment->itemid] = $comment;
                        if ($comment->commentby) {
                            $commentuserids[] = $comment->commentby;
                        }
                    }
                    if (!empty($commentuserids)) {
                        list($csql, $cparams) = $DB->get_in_or_equal(array_unique($commentuserids, SORT_NUMERIC));
                        $commentusers = $DB->get_records_select('user', 'id '.$csql, $cparams);
                    }
                } else {
                    $comments = false;
                }
            }

            if ($teachermarklocked) {
                echo '<p style="checkskillwarning">'.get_string('lockteachermarkswarning', 'checkskill').'</p>';
            }
            $ol_count=0; // to close all ol tags : MODIF JF
            echo '<ol class="checkskill" id="checkskillouter">';
            $ol_count++; // MODIF JF
            $currindent = 0;
            foreach ($this->items as $item) {

                if ($item->hidden) {
                    continue;
                }

                if ($checkgroupings && $item->grouping) {
                    if (!in_array($item->grouping, $this->groupings)) {
                        continue; // Current user is not a member of this item's grouping, so skip
                    }
                }

                while ($item->indent > $currindent) {
                    $currindent++;
                    echo '<ol class="checkskill">';
                    $ol_count++;
                }
				
                while ($item->indent < $currindent) {
                    $currindent--;
                    echo '</ol>';
                    $ol_count--;
                }
				
                $itemname = '"item'.$item->id.'"';
                $checked = (($updateform || $viewother || $userreport) && $item->checked) ? ' checked="checked" ' : '';
                if ($viewother || $userreport) {
                    $checked .= ' disabled="disabled" ';
                } else if (!$overrideauto && $item->moduleid) {
                    $checked .= ' disabled="disabled" ';
                }
                switch ($item->colour) {
                case 'red':
                    $itemcolour = 'itemred';
                    break;
                case 'orange':
                    $itemcolour = 'itemorange';
                    break;
                case 'green':
                    $itemcolour = 'itemgreen';
                    break;
                case 'purple':
                    $itemcolour = 'itempurple';
                    break;
                default:
                    $itemcolour = 'itemblack';
                }

				$checkclass = '';
                if ($item->itemoptional == CHECKSKILL_OPTIONAL_HEADING) {
                    $optional = ' class="itemheading '.$itemcolour.'" ';
//                    $spacerimg = $OUTPUT->pix_url('check_spacer','checkskill');
                } else if ($item->itemoptional == CHECKSKILL_OPTIONAL_YES) {
                    $optional = ' class="itemoptional '.$itemcolour.'" ';
                    $checkclass = ' itemoptional';
                } else {
                    $optional = ' class="'.$itemcolour.'" ';
                }

                echo '<li>';
                if ($showteachermark) {
                    if ($item->itemoptional == CHECKSKILL_OPTIONAL_HEADING) {
                        //echo '<img src="'.$spacerimg.'" alt="" title="" />';
                        if ($viewother) {
                            $disabled = ($teachermarklocked && $item->teachermark == CHECKSKILL_TEACHERMARK_YES) ? 'disabled="disabled" ' : '';

                            $selu = ($item->teachermark == CHECKSKILL_TEACHERMARK_UNDECIDED) ? 'selected="selected" ' : '';
                            $sely = ($item->teachermark == CHECKSKILL_TEACHERMARK_YES) ? 'selected="selected" ' : '';
                            $seln = ($item->teachermark == CHECKSKILL_TEACHERMARK_NO) ? 'selected="selected" ' : '';

                            echo '<select name="items['.$item->id.']" '.$disabled.'>';
                            echo '<option value="'.CHECKSKILL_TEACHERMARK_UNDECIDED.'" '.$selu.'></option>';
                            echo '<option value="'.CHECKSKILL_TEACHERMARK_YES.'" '.$sely.'>'.get_string('yes').'</option>';
                            echo '<option value="'.CHECKSKILL_TEACHERMARK_NO.'" '.$seln.'>'.get_string('no').'</option>';
                            echo '</select>';
                        } else {
                            list($imgsrc, $titletext) = $this->get_teachermark($item->id);
                            echo '<img src="'.$imgsrc.'" alt="'.$titletext.'" title="'.$titletext.'" />';
                        }
                    }
                }
                if ($showcheckbox) {
                    if ($item->itemoptional == CHECKSKILL_OPTIONAL_HEADING) {
                        echo '<input class="checkskillitem'.$checkclass.'" type="checkbox" name="items[]" id='.$itemname.$checked.' value="'.$item->id.'" />';
                    }
                }
                echo '<label for='.$itemname.$optional.'>'.s($item->displaytext).'</label>';
                if (isset($item->modulelink)) {
                    echo '&nbsp;<a href="'.$item->modulelink.'"><img src="'.$OUTPUT->pix_url('follow_link','checkskill').'" alt="'.get_string('linktomodule','checkskill').'" /></a>';
                }

                if ($addown) {
                    echo '&nbsp;<a href="'.$thispage->out(true, array('itemid'=>$item->id, 'sesskey'=>sesskey(), 'action'=>'startadditem') ).'">';
                    $title = '"'.get_string('additemalt','checkskill').'"';
                    echo '<img src="'.$OUTPUT->pix_url('add','checkskill').'" alt='.$title.' title='.$title.' /></a>';
                }

                if ($item->duetime) {
                    if ($item->duetime > time()) {
                        echo '<span class="checkskill-itemdue"> '.userdate($item->duetime, get_string('strftimedate')).'</span>';
                    } else {
                        echo '<span class="checkskill-itemoverdue"> '.userdate($item->duetime, get_string('strftimedate')).'</span>';
                    }
                }

                 if ($showcompletiondates) {
                    if ($item->itemoptional != CHECKSKILL_OPTIONAL_HEADING) {
                        if ($showteachermark && $item->teachermark != CHECKSKILL_TEACHERMARK_UNDECIDED && $item->teachertimestamp) {
                            if ($item->teachername) {
                                echo '<span class="itemteachername" title="'.$strteachername.'">'.$item->teachername.'</span>';
                            }
                            echo '<span class="itemteacherdate" title="'.$strteacherdate.'">'.userdate($item->teachertimestamp, get_string('strftimedatetimeshort')).'</span>';
                        }
                        if ($showcheckbox && $item->checked && $item->usertimestamp) {
                            echo '<span class="itemuserdate" title="'.$struserdate.'">'.userdate($item->usertimestamp, get_string('strftimedatetimeshort')).'</span>';
                        }
                    }
                }

                $foundcomment = false;
                if ($comments) {
                    if (array_key_exists($item->id, $comments)) {
                        $comment =  $comments[$item->id];
                        $foundcomment = true;
                        echo ' <span class="teachercomment">&nbsp;';
                        if ($comment->commentby) {
                            $userurl = new moodle_url('/user/view.php', array('id'=>$comment->commentby, 'course'=>$this->course->id) );
                            echo '<a href="'.$userurl.'">'.fullname($commentusers[$comment->commentby]).'</a>: ';
                        }
                        if ($editcomments) {
                            $outid = '';
                            if (!$focusitem) {
                                $focusitem = 'firstcomment';
                                $outid = ' id="firstcomment" ';
                            }
                            echo '<input type="text" name="teachercomment['.$item->id.']" value="'.s($comment->text).'" '.$outid.'/>';
                        } else {
                            echo $comment->text; // echo s($comment->text);
                        }
                        echo '&nbsp;</span>';
                    }
                }
                if (!$foundcomment && $editcomments) {
                    echo '&nbsp;<input type="text" name="teachercomment['.$item->id.']" />';
                }
// MODIF JF ***************************************************				
                // Display descriptions
                $editdescription=$CFG->checkskill_description_display;
                if ($editdescription) {
                    $this->display_description_documents($item->id, $this->userid, ($USER->id==$this->userid));
                }
// ************************************************************
                echo '</li>';

				
                // Output any user-added items
                if ($this->useritems) {
                    $useritem = current($this->useritems);

                    if ($useritem && ($useritem->position == $item->position)) {
                        $thisitemurl = clone $thispage;
                        $thisitemurl->param('action', 'updateitem');
                        $thisitemurl->param('sesskey', sesskey());

                        echo '<ol class="checkskill">';
                        while ($useritem && ($useritem->position == $item->position)) {
                            $itemname = '"item'.$useritem->id.'"';
                            $checked = ($updateform && $useritem->checked) ? ' checked="checked" ' : '';
                            if (isset($useritem->editme)) {
                                $itemtext = explode("\n", $useritem->displaytext, 2);
                                $itemtext[] = '';
                                $text = $itemtext[0];
                                $note = $itemtext[1];
                                $thisitemurl->param('itemid', $useritem->id);

                                echo '<li>';
                                echo '<div style="float: left;">';
                                if ($showcheckbox) {
                                    echo '<input class="checkskillitem itemoptional" type="checkbox" name="items[]" id='.$itemname.$checked.' disabled="disabled" value="'.$useritem->id.'" />';
                                }
                                echo '<form style="display:inline" action="'.$thisitemurl->out_omit_querystring().'" method="post">';
                                echo html_writer::input_hidden_params($thisitemurl);
                                echo '<input type="text" size="'.CHECKSKILL_TEXT_INPUT_WIDTH.'" name="displaytext" value="'.s($text).'" id="updateitembox" />';
                                echo '<input type="submit" name="updateitem" value="'.get_string('updateitem','checkskill').'" />';
                                echo '<br />';
                                echo '<textarea name="displaytextnote" rows="3" cols="25">'.s($note).'</textarea>';
                                echo '</form>';
                                echo '</div>';

                                echo '<form style="display:inline;" action="'.$thispage->out_omit_querystring().'" method="get">';
                                echo html_writer::input_hidden_params($thispage);
                                echo '<input type="submit" name="canceledititem" value="'.get_string('canceledititem','checkskill').'" />';
                                echo '</form>';
                                echo '<br style="clear: both;" />';
                                echo '</li>';

                                $focusitem = 'updateitembox';
                            } else {
                                echo '<li>';
                                if ($showcheckbox) {
                                    echo '<input class="checkskillitem itemoptional" type="checkbox" name="items[]" id='.$itemname.$checked.' value="'.$useritem->id.'" />';
                                }
                                $splittext = explode("\n",s($useritem->displaytext),2);
                                $splittext[] = '';
                                $text = $splittext[0];
                                $note = str_replace("\n",'<br />',$splittext[1]);
                                echo '<label class="useritem" for='.$itemname.'>'.$text.'</label>';

                                if ($addown) {
                                    $baseurl = $thispage.'&amp;itemid='.$useritem->id.'&amp;sesskey='.sesskey().'&amp;action=';
                                    echo '&nbsp;<a href="'.$baseurl.'edititem">';
                                    $title = '"'.get_string('edititem','checkskill').'"';
                                    echo '<img src="'.$OUTPUT->pix_url('/t/edit').'" alt='.$title.' title='.$title.' /></a>';

                                    echo '&nbsp;<a href="'.$baseurl.'deleteitem" class="deleteicon">';
                                    $title = '"'.get_string('deleteitem','checkskill').'"';
                                    echo '<img src="'.$OUTPUT->pix_url('remove','checkskill').'" alt='.$title.' title='.$title.' /></a>';
                                }
                                if ($note != '') {
                                    echo '<div class="note">'.$note.'</div>';
                                }

                                echo '</li>';
                            }
                            $useritem = next($this->useritems);
                        }
                        echo '</ol>';
                    }
                }

                if ($addown && ($item->id == $this->additemafter)) {
                    $thisitemurl = clone $thispage;
                    $thisitemurl->param('action', 'additem');
                    $thisitemurl->param('position', $item->position);
                    $thisitemurl->param('sesskey', sesskey());

                    echo '<ol class="checkskill"><li>';
                    echo '<div style="float: left;">';
                    echo '<form action="'.$thispage->out_omit_querystring().'" method="post">';
                    echo html_writer::input_hidden_params($thisitemurl);
                    if ($showcheckbox) {
                        echo '<input type="checkbox" disabled="disabled" />';
                    }
                    echo '<input type="text" size="'.CHECKSKILL_TEXT_INPUT_WIDTH.'" name="displaytext" value="" id="additembox" />';
                    echo '<input type="submit" name="additem" value="'.get_string('additem','checkskill').'" />';
                    echo '<br />';
                    echo '<textarea name="displaytextnote" rows="3" cols="25"></textarea>';
                    echo '</form>';
                    echo '</div>';

                    echo '<form style="display:inline" action="'.$thispage->out_omit_querystring().'" method="get">';
                    echo html_writer::input_hidden_params($thispage);
                    echo '<input type="submit" name="canceledititem" value="'.get_string('canceledititem','checkskill').'" />';
                    echo '</form>';
                    echo '<br style="clear: both;" />';
                    echo '</li></ol>';

                    if (!$focusitem) {
                        $focusitem = 'additembox';
                    }
                }
            }
            while ($ol_count>0){
                echo '</ol>';
                $ol_count--;
            }
			
            if ($updateform) {
                echo '<input id="checkskillsavechecks" type="submit" name="submit" value="'.get_string('savechecks','checkskill').'" />';
                if ($viewother) {
                    echo '&nbsp;<input type="submit" name="save" value="'.get_string('savechecks', 'mod_checkskill').'" />';				
                    echo '&nbsp;<input type="submit" name="savenext" value="'.get_string('saveandnext').'" />';
                    echo '&nbsp;<input type="submit" name="viewnext" value="'.get_string('next').'" />';
                }
                echo '</form>';
            }

            if ($focusitem) {
                echo '<script type="text/javascript">document.getElementById("'.$focusitem.'").focus();</script>';
            }

            if ($addown) {
                echo '<script type="text/javascript">';
                echo 'function confirmdelete(url) {';
                echo 'if (confirm("'.get_string('confirmdeleteitem','checkskill').'")) { window.location = url; } ';
                echo '} ';
                echo 'var links = document.getElementById("checkskillouter").getElementsByTagName("a"); ';
                echo 'for (var i in links) { ';
                echo 'if (links[i].className == "deleteicon") { ';
                echo 'var url = links[i].href;';
                echo 'links[i].href = "#";';
                echo 'links[i].onclick = new Function( "confirmdelete(\'"+url+"\')" ) ';
                echo '}} ';
                echo '</script>';
            }
        }

        echo $OUTPUT->box_end();
    }

    function print_edit_date($ts=0) {
        // TODO - use fancy JS calendar instead

        $id=rand();
        if ($ts == 0) {
            $disabled = true;
            $date = usergetdate(time());
        } else {
            $disabled = false;
            $date = usergetdate($ts);
        }
        $day = $date['mday'];
        $month = $date['mon'];
        $year = $date['year'];

        echo '<select name="duetime[day]" id="timedueday'.$id.'" >';
        for ($i=1; $i<=31; $i++) {
            $selected = ($i == $day) ? 'selected="selected" ' : '';
            echo '<option value="'.$i.'" '.$selected.'>'.$i.'</option>';
        }
        echo '</select>';
        echo '<select name="duetime[month]" id="timeduemonth'.$id.'" >';
        for ($i=1; $i<=12; $i++) {
            $selected = ($i == $month) ? 'selected="selected" ' : '';
            echo '<option value="'.$i.'" '.$selected.'>'.userdate(gmmktime(12,0,0,$i,15,2000), "%B").'</option>';
        }
        echo '</select>';
        echo '<select name="duetime[year]" id="timedueyear'.$id.'" >';
        $today = usergetdate(time());
        $thisyear = $today['year'];
        for ($i=$thisyear-5; $i<=($thisyear + 10); $i++) {
            $selected = ($i == $year) ? 'selected="selected" ' : '';
            echo '<option value="'.$i.'" '.$selected.'>'.$i.'</option>';
        }
        echo '</select>';
        $checked = $disabled ? 'checked="checked" ' : '';
        echo '<input type="checkbox" name="duetimedisable" '.$checked.' id="timeduedisable'.$id.'" onclick="toggledate'.$id.'()" /><label for="timeduedisable'.$id.'">'.get_string('disable').' </label>'."\n";
        echo '<script type="text/javascript">'."\n";
        echo "function toggledate{$id}() {\n var disable = document.getElementById('timeduedisable{$id}').checked;\n var day = document.getElementById('timedueday{$id}');\n var month = document.getElementById('timeduemonth{$id}');\n var year = document.getElementById('timedueyear{$id}');\n";
        echo "if (disable) { \nday.setAttribute('disabled','disabled');\nmonth.setAttribute('disabled', 'disabled');\nyear.setAttribute('disabled', 'disabled');\n } ";
        echo "else {\nday.removeAttribute('disabled');\nmonth.removeAttribute('disabled');\nyear.removeAttribute('disabled');\n }";
        echo "} toggledate{$id}(); </script>\n";
    }

    function view_import_export() {
	global $CFG;
        $importurl = new moodle_url('/mod/checkskill/import.php', array('id' => $this->cm->id));
        $exporturl = new moodle_url('/mod/checkskill/export.php', array('id' => $this->cm->id));

        $importstr = get_string('import', 'checkskill');
        $exportstr = get_string('export', 'checkskill');

        echo "<div class='checkskillimportexport'>";
// MODIF JF *****************************************************************
        if (!empty($CFG->checkskill_outcomes_input)){
            $importoutcomesurl = new moodle_url('/mod/checkskill/import_outcomes.php', array('id' => $this->cm->id));
            $importoutcomesstr = get_string('import_outcomes', 'checkskill');
            $exportoutcomesurl = new moodle_url('/mod/checkskill/select_export.php', array('id' => $this->cm->id));
            $exportoutcomesstr = get_string('export_outcomes', 'checkskill');
            echo "<a href='$importurl'>$importstr</a>&nbsp;&nbsp;&nbsp;<a href='$importoutcomesurl'>$importoutcomesstr</a>&nbsp;&nbsp;&nbsp;&nbsp;<a href='$exporturl'>$exportstr</a>&nbsp;&nbsp;&nbsp;<a href='$exportoutcomesurl'>$exportoutcomesstr</a>";
        }
        else{
			echo "<a href='$importurl'>$importstr</a>&nbsp;&nbsp;&nbsp;<a href='$exporturl'>$exportstr</a>";
		}
// ******************************************************************************		
        echo "</div>";
    }

    function view_edit_items() {
        global $OUTPUT;

        echo $OUTPUT->box_start('generalbox boxwidthwide boxaligncenter');

        $currindent = 0;
        $addatend = true;
        $focusitem = false;
        $hasauto = false;

        $thispage = new moodle_url('/mod/checkskill/edit.php', array('id'=>$this->cm->id, 'sesskey'=>sesskey()));
        if ($this->additemafter) {
            $thispage->param('additemafter', $this->additemafter);
        }
        if ($this->editdates) {
            $thispage->param('editdates', 'on');
        }

        if ($this->checkskill->autoupdate && $this->checkskill->autopopulate) {
            if ($this->checkskill->teacheredit == CHECKSKILL_MARKING_STUDENT) {
                echo '<p>'.get_string('autoupdatewarning_student', 'checkskill').'</p>';
            } else if ($this->checkskill->teacheredit == CHECKSKILL_MARKING_TEACHER) {
                echo '<p class="checkskillwarning">'.get_string('autoupdatewarning_teacher', 'checkskill').'</p>';
            } else {
                echo '<p class="checkskillwarning">'.get_string('autoupdatewarning_both', 'checkskill').'</p>';
            }
        }

        echo '<ol class="checkskill">';
        if ($this->items) {
            $lastitem = count($this->items);
            $lastindent = 0;
            foreach ($this->items as $item) {

                while ($item->indent > $currindent) {
                    $currindent++;
                    echo '<ol class="checkskill">';
                }
                while ($item->indent < $currindent) {
                    $currindent--;
                    echo '</ol>';
                }

                $itemname = '"item'.$item->id.'"';
                $thispage->param('itemid',$item->id);

                switch ($item->colour) {
                case 'red':
                    $itemcolour = 'itemred';
                    $nexticon = 'colour_orange';
                    break;
                case 'orange':
                    $itemcolour = 'itemorange';
                    $nexticon = 'colour_green';
                    break;
                case 'green':
                    $itemcolour = 'itemgreen';
                    $nexticon = 'colour_purple';
                    break;
                case 'purple':
                    $itemcolour = 'itempurple';
                    $nexticon = 'colour_black';
                    break;
                default:
                    $itemcolour = 'itemblack';
                    $nexticon = 'colour_red';
                }

                $autoitem = ($this->checkskill->autopopulate) && ($item->moduleid != 0);
                if ($autoitem) {
                    $autoclass = ' itemauto';
                } else {
                    $autoclass = '';
                }
                $hasauto = $hasauto || ($item->moduleid != 0);

                echo '<li>';
                if ($item->itemoptional == CHECKSKILL_OPTIONAL_YES) {
                    $title = '"'.get_string('optionalitem','checkskill').'"';
                    echo '<a href="'.$thispage->out(true, array('action'=>'makeheading')).'">';
                    echo '<img src="'.$OUTPUT->pix_url('empty_box','checkskill').'" alt='.$title.' title='.$title.' /></a>&nbsp;';
                    $optional = ' class="itemoptional '.$itemcolour.$autoclass.'" ';
                } else if ($item->itemoptional == CHECKSKILL_OPTIONAL_HEADING) {
                    if ($item->hidden) {
                        $title = '"'.get_string('headingitem','checkskill').'"';
                        echo '<img src="'.$OUTPUT->pix_url('no_box','checkskill').'" alt='.$title.' title='.$title.' />&nbsp;';
                        $optional = ' class="'.$itemcolour.$autoclass.' itemdisabled"';
                    } else {
                        $title = '"'.get_string('headingitem','checkskill').'"';
                        if (!$autoitem) {
                            echo '<a href="'.$thispage->out(true, array('action'=>'makerequired')).'">';
                        }
                        echo '<img src="'.$OUTPUT->pix_url('no_box','checkskill').'" alt='.$title.' title='.$title.' />';
                        if (!$autoitem) {
                            echo '</a>';
                        }
                        echo '&nbsp;';
                        $optional = ' class="itemheading '.$itemcolour.$autoclass.'" ';
                    }
                } else if ($item->hidden) {
                    $title = '"'.get_string('requireditem','checkskill').'"';
                    echo '<img src="'.$OUTPUT->pix_url('tick_box','checkskill').'" alt='.$title.' title='.$title.' />&nbsp;';
                    $optional = ' class="'.$itemcolour.$autoclass.' itemdisabled"';
                } else {
                    $title = '"'.get_string('requireditem','checkskill').'"';
                    echo '<a href="'.$thispage->out(true, array('action'=>'makeoptional')).'">';
                    echo '<img src="'.$OUTPUT->pix_url('tick_box','checkskill').'" alt='.$title.' title='.$title.' /></a>&nbsp;';
                    $optional = ' class="'.$itemcolour.$autoclass.'"';
                }

                if (isset($item->editme)) {
                    echo '<form style="display:inline" action="'.$thispage->out_omit_querystring().'" method="post">';
                    echo '<input type="text" size="'.CHECKSKILL_TEXT_INPUT_WIDTH.'" name="displaytext" value="'.s($item->displaytext).'" id="updateitembox" />';
                    echo '<input type="hidden" name="action" value="updateitem" />';
                    echo html_writer::input_hidden_params($thispage);
                    if ($this->editdates) {
                        $this->print_edit_date($item->duetime);
                    }
                    echo '<input type="submit" name="updateitem" value="'.get_string('updateitem','checkskill').'" />';
                    echo '</form>';

                    $focusitem = 'updateitembox';

                    echo '<form style="display:inline" action="'.$thispage->out_omit_querystring().'" method="get">';
                    echo html_writer::input_hidden_params($thispage, array('sesskey', 'itemid') );
                    echo '<input type="submit" name="canceledititem" value="'.get_string('canceledititem','checkskill').'" />';
                    echo '</form>';

                    $addatend = false;

                } else {
                    echo '<label for='.$itemname.$optional.'>'.s($item->displaytext).'</label>&nbsp;';

                    echo '<a href="'.$thispage->out(true, array('action'=>'nextcolour')).'">';
                    $title = '"'.get_string('changetextcolour','checkskill').'"';
                    echo '<img src="'.$OUTPUT->pix_url($nexticon,'checkskill').'" alt='.$title.' title='.$title.' /></a>';

                    if (!$autoitem) {
                        echo '<a href="'.$thispage->out(true, array('action'=>'edititem')).'">';
                        $title = '"'.get_string('edititem','checkskill').'"';
                        echo '<img src="'.$OUTPUT->pix_url('/t/edit').'"  alt='.$title.' title='.$title.' /></a>&nbsp;';
                    }

                    if (!$autoitem && $item->indent > 0) {
                        echo '<a href="'.$thispage->out(true, array('action'=>'unindentitem')).'">';
                        $title = '"'.get_string('unindentitem','checkskill').'"';
                        echo '<img src="'.$OUTPUT->pix_url('/t/left').'" alt='.$title.' title='.$title.'  /></a>';
                    }

                    if (!$autoitem && ($item->indent < CHECKSKILL_MAX_INDENT) && (($lastindent+1) > $currindent)) {
                        echo '<a href="'.$thispage->out(true, array('action'=>'indentitem')).'">';
                        $title = '"'.get_string('indentitem','checkskill').'"';
                        echo '<img src="'.$OUTPUT->pix_url('/t/right').'" alt='.$title.' title='.$title.' /></a>';
                    }

                    echo '&nbsp;';

                    // TODO more complex checks to take into account indentation
                    if (!$autoitem && $item->position > 1) {
                        echo '<a href="'.$thispage->out(true, array('action'=>'moveitemup')).'">';
                        $title = '"'.get_string('moveitemup','checkskill').'"';
                        echo '<img src="'.$OUTPUT->pix_url('/t/up').'" alt='.$title.' title='.$title.' /></a>';
                    }

                    if (!$autoitem && $item->position < $lastitem) {
                        echo '<a href="'.$thispage->out(true, array('action'=>'moveitemdown')).'">';
                        $title = '"'.get_string('moveitemdown','checkskill').'"';
                        echo '<img src="'.$OUTPUT->pix_url('/t/down').'" alt='.$title.' title='.$title.' /></a>';
                    }

                    if ($autoitem) {
                        if ($item->hidden != CHECKSKILL_HIDDEN_BYMODULE) {
                            echo '&nbsp;<a href="'.$thispage->out(true, array('action'=>'deleteitem')).'">';
                            if ($item->hidden == CHECKSKILL_HIDDEN_MANUAL) {
                                $title = '"'.get_string('show').'"';
                                echo '<img src="'.$OUTPUT->pix_url('/t/show').'" alt='.$title.' title='.$title.' /></a>';
                            } else {
                                $title = '"'.get_string('hide').'"';
                                echo '<img src="'.$OUTPUT->pix_url('/t/hide').'" alt='.$title.' title='.$title.' /></a>';
                            }
                        }
                    } else {
                        echo '&nbsp;<a href="'.$thispage->out(true, array('action'=>'deleteitem')).'">';
                        $title = '"'.get_string('deleteitem','checkskill').'"';
                        echo '<img src="'.$OUTPUT->pix_url('/t/delete').'" alt='.$title.' title='.$title.' /></a>';
                    }

                    echo '&nbsp;&nbsp;&nbsp;<a href="'.$thispage->out(true, array('action'=>'startadditem')).'">';
                    $title = '"'.get_string('additemhere','checkskill').'"';
                    echo '<img src="'.$OUTPUT->pix_url('add','checkskill').'" alt='.$title.' title='.$title.' /></a>';
                    if ($item->duetime) {
                        if ($item->duetime > time()) {
                            echo '<span class="checkskill-itemdue"> '.userdate($item->duetime, get_string('strftimedate')).'</span>';
                        } else {
                            echo '<span class="checkskill-itemoverdue"> '.userdate($item->duetime, get_string('strftimedate')).'</span>';
                        }
                    }

                }

                $thispage->remove_params(array('itemid'));

                if ($this->additemafter == $item->id) {
                    $addatend = false;
                    echo '<li>';
                    echo '<form style="display:inline;" action="'.$thispage->out_omit_querystring().'" method="post">';
                    echo html_writer::input_hidden_params($thispage);
                    echo '<input type="hidden" name="action" value="additem" />';
                    echo '<input type="hidden" name="position" value="'.($item->position+1).'" />';
                    echo '<input type="hidden" name="indent" value="'.$item->indent.'" />';
                    echo '<img src="'.$OUTPUT->pix_url('tick_box','checkskill').'" /> ';
                    echo '<input type="text" size="'.CHECKSKILL_TEXT_INPUT_WIDTH.'" name="displaytext" value="" id="additembox" />';
                    if ($this->editdates) {
                        $this->print_edit_date();
                    }
                    echo '<input type="submit" name="additem" value="'.get_string('additem','checkskill').'" />';
                    echo '</form>';

                    echo '<form style="display:inline" action="'.$thispage->out_omit_querystring().'" method="get">';
                    echo html_writer::input_hidden_params($thispage, array('sesskey','additemafter'));
                    echo '<input type="submit" name="canceledititem" value="'.get_string('canceledititem','checkskill').'" />';
                    echo '</form>';
                    echo '</li>';

                    if (!$focusitem) {
                        $focusitem = 'additembox';
                    }

                    $lastindent = $currindent;
                }

                echo '</li>';
            }
        }

        $thispage->remove_params(array('itemid'));

        if ($addatend) {
            echo '<li>';
            echo '<form action="'.$thispage->out_omit_querystring().'" method="post">';
            echo html_writer::input_hidden_params($thispage);
            echo '<input type="hidden" name="action" value="additem" />';
            echo '<input type="hidden" name="indent" value="'.$currindent.'" />';
            echo '<input type="text" size="'.CHECKSKILL_TEXT_INPUT_WIDTH.'" name="displaytext" value="" id="additembox" />';
            if ($this->editdates) {
                $this->print_edit_date();
            }
            echo '<input type="submit" name="additem" value="'.get_string('additem','checkskill').'" />';
            echo '</form>';
            echo '</li>';
            if (!$focusitem) {
                $focusitem = 'additembox';
            }
        }
        echo '</ol>';
        while ($currindent) {
            $currindent--;
            echo '</ol>';
        }

        echo '<form action="'.$thispage->out_omit_querystring().'" method="get">';
        echo html_writer::input_hidden_params($thispage, array('sesskey','editdates'));
        if (!$this->editdates) {
            echo '<input type="hidden" name="editdates" value="on" />';
            echo '<input type="submit" value="'.get_string('editdatesstart','checkskill').'" />';
        } else {
            echo '<input type="submit" value="'.get_string('editdatesstop','checkskill').'" />';
        }
        if (!$this->checkskill->autopopulate && $hasauto) {
            echo '<input type="hidden" name="sesskey" value="'.sesskey().'" />';
            echo '<input type="submit" value="'.get_string('removeauto', 'checkskill').'" name="removeauto" />';
        }
        echo '</form>';

        if ($focusitem) {
            echo '<script type="text/javascript">document.getElementById("'.$focusitem.'").focus();</script>';
        }

        echo $OUTPUT->box_end();
    }

    function view_report() {
        global $DB, $OUTPUT, $CFG;

        $reportsettings = $this->get_report_settings();

        $editchecks = $this->caneditother() && optional_param('editchecks', false, PARAM_BOOL);

        $page = optional_param('page', 0, PARAM_INT);
        $perpage = optional_param('perpage', 30, PARAM_INT);

        $thisurl = new moodle_url('/mod/checkskill/report.php', array('id'=>$this->cm->id, 'sesskey'=>sesskey()) );
        if ($editchecks) { $thisurl->param('editchecks','on'); }

        if ($this->checkskill->autoupdate && $this->checkskill->autopopulate) {
            if ($this->checkskill->teacheredit == CHECKSKILL_MARKING_TEACHER) {
                echo '<p>'.get_string('autoupdatewarning_teacher', 'checkskill').'</p>';
            } else if ($this->checkskill->teacheredit == CHECKSKILL_MARKING_BOTH) {
                echo '<p>'.get_string('autoupdatewarning_both', 'checkskill').'</p>';
            }
        }

        groups_print_activity_menu($this->cm, $thisurl);
        $activegroup = groups_get_activity_group($this->cm, true);
        if ($activegroup == 0) {
            if (groups_get_activity_groupmode($this->cm) == SEPARATEGROUPS) {
                if (!has_capability('moodle/site:accessallgroups', $this->context)) {
                    $activegroup = -1; // Not allowed to access any groups.
                }
            }
        }

        echo '&nbsp;&nbsp;<form style="display: inline;" action="'.$thisurl->out_omit_querystring().'" method="get">';
        echo html_writer::input_hidden_params($thisurl, array('action'));
        if ($reportsettings->showoptional) {
            echo '<input type="hidden" name="action" value="hideoptional" />';
            echo '<input type="submit" name="submit" value="'.get_string('optionalhide','checkskill').'" />';
        } else {
            echo '<input type="hidden" name="action" value="showoptional" />';
            echo '<input type="submit" name="submit" value="'.get_string('optionalshow','checkskill').'" />';
        }
        echo '</form>';

        echo '&nbsp;&nbsp;<form style="display: inline;" action="'.$thisurl->out_omit_querystring().'" method="get">';
        echo html_writer::input_hidden_params($thisurl);
        if ($reportsettings->showprogressbars) {
            $editchecks = false;
            echo '<input type="hidden" name="action" value="hideprogressbars" />';
            echo '<input type="submit" name="submit" value="'.get_string('showfulldetails','checkskill').'" />';
        } else {
            echo '<input type="hidden" name="action" value="showprogressbars" />';
            echo '<input type="submit" name="submit" value="'.get_string('showprogressbars','checkskill').'" />';
        }
        echo '</form>';

        if ($editchecks) {
            echo '&nbsp;&nbsp;<form style="display: inline;" action="'.$thisurl->out_omit_querystring().'" method="post">';
            echo html_writer::input_hidden_params($thisurl);
            echo '<input type="hidden" name="action" value="updateallchecks"/>';
            echo '<input type="submit" name="submit" value="'.get_string('savechecks','checkskill').'" />';
        } else if (!$reportsettings->showprogressbars && $this->caneditother() && $this->checkskill->teacheredit != CHECKSKILL_MARKING_STUDENT) {
            echo '&nbsp;&nbsp;<form style="display: inline;" action="'.$thisurl->out_omit_querystring().'" method="get">';
            echo html_writer::input_hidden_params($thisurl);
            echo '<input type="hidden" name="editchecks" value="on" />';
            echo '<input type="submit" name="submit" value="'.get_string('editchecks','checkskill').'" />';
            echo '</form>';
        }

        echo '<br style="clear:both"/>';

        switch ($reportsettings->sortby) {
        case 'firstdesc':
            $orderby = 'u.firstname DESC';
            break;

        case 'lastasc':
            $orderby = 'u.lastname';
            break;

        case 'lastdesc':
            $orderby = 'u.lastname DESC';
            break;

        default:
            $orderby = 'u.firstname';
            break;
        }

        $ausers = false;
        if ($activegroup == -1) {
            $users = array();
        } else if ($users = get_users_by_capability($this->context, 'mod/checkskill:updateown', 'u.id', $orderby, '', '', $activegroup, '', false)) {
            $users = array_keys($users);
            if ($this->only_view_mentee_reports()) {
                // Filter to only show reports for users who this user mentors (ie they have been assigned to them in a context)
                $users = $this->filter_mentee_users($users);
            }
        }
        if ($users && !empty($users)) {
            if (count($users) < $page*$perpage) {
                $page = 0;
            }
            echo $OUTPUT->paging_bar(count($users), $page, $perpage, new moodle_url($thisurl, array('perpage'=>$perpage)));
            $users = array_slice($users, $page*$perpage, $perpage);

            list($usql, $uparams) = $DB->get_in_or_equal($users);
            if ($CFG->version < 2013111800) {
                $fields = 'u.firstname, u.lastname';
            } else {
                $fields = get_all_user_name_fields(true, 'u');
            }			
			$ausers = $DB->get_records_sql("SELECT u.id, $fields FROM {user} u WHERE u.id ".$usql.' ORDER BY '.$orderby, $uparams);
		}

        if ($reportsettings->showprogressbars) {
            if ($ausers) {
                // Show just progress bars
                if ($reportsettings->showoptional) {
                    $itemstocount = array();
                    foreach ($this->items as $item) {
                        if (!$item->hidden) {
                            if (($item->itemoptional == CHECKSKILL_OPTIONAL_YES) || ($item->itemoptional == CHECKSKILL_OPTIONAL_NO)) {
                                $itemstocount[] = $item->id;
                            }
                        }
                    }
                } else {
                    $itemstocount = array();
                    foreach ($this->items as $item) {
                        if (!$item->hidden) {
                            if ($item->itemoptional == CHECKSKILL_OPTIONAL_NO) {
                                $itemstocount[] = $item->id;
                            }
                        }
                    }
                }
                $totalitems = count($itemstocount);

                $sql = '';
                if ($totalitems) {
                    list($isql, $iparams) = $DB->get_in_or_equal($itemstocount, SQL_PARAMS_NAMED);
                    if ($this->checkskill->teacheredit == CHECKSKILL_MARKING_STUDENT) {
                        $sql = 'usertimestamp > 0 AND item '.$isql.' AND userid = :user ';
                    } else {
                        $sql = 'teachermark = '.CHECKSKILL_TEACHERMARK_YES.' AND item '.$isql.' AND userid = :user ';
                    }
                }
                echo '<div>';
                foreach ($ausers as $auser) {
                    if ($totalitems) {
                        $iparams['user'] = $auser->id;
                        $tickeditems = $DB->count_records_select('checkskill_check', $sql, $iparams);
                        $percentcomplete = ($tickeditems * 100) / $totalitems;
                    } else {
                        $percentcomplete = 0;
                        $tickeditems = 0;
                    }

                    if ($this->caneditother()) {
                        $vslink = ' <a href="'.$thisurl->out(true, array('studentid'=>$auser->id) ).'" ';
                        $vslink .= 'alt="'.get_string('viewsinglereport','checkskill').'" title="'.get_string('viewsinglereport','checkskill').'">';
                        $vslink .= '<img src="'.$OUTPUT->pix_url('/t/preview').'" /></a>';
                    } else {
                        $vslink = '';
                    }
                    $userurl = new moodle_url('/user/view.php', array('id'=>$auser->id, 'course'=>$this->course->id) );
                    $userlink = '<a href="'.$userurl.'">'.fullname($auser).'</a>';
                    echo '<div style="float: left; width: 30%; text-align: right; margin-right: 8px; ">'.$userlink.$vslink.'</div>';

                    echo '<div class="checkskill_progress_outer">';
                    echo '<div class="checkskill_progress_inner" style="width:'.$percentcomplete.'%; background-image: url('.$OUTPUT->pix_url('progress','checkskill').');" >&nbsp;</div>';
                    echo '</div>';
                    echo '<div style="float:left; width: 3em;">&nbsp;'.sprintf('%0d%%',$percentcomplete).'</div>';
                    echo '<div style="float:left;">&nbsp;('.$tickeditems.'/'.$totalitems.')</div>';
                    echo '<br style="clear:both;" />';
                }
                echo '</div>';
            }

        } else {

            // Show full table
            $firstlink = 'firstasc';
            $lastlink = 'lastasc';
            $firstarrow = '';
            $lastarrow = '';
            if ($reportsettings->sortby == 'firstasc') {
                $firstlink = 'firstdesc';
                $firstarrow = '<img src="'.$OUTPUT->pix_url('/t/down').'" alt="'.get_string('asc').'" />';
            } else if ($reportsettings->sortby == 'lastasc') {
                $lastlink = 'lastdesc';
                $lastarrow = '<img src="'.$OUTPUT->pix_url('/t/down').'" alt="'.get_string('asc').'" />';
            } else if ($reportsettings->sortby == 'firstdesc') {
                $firstarrow = '<img src="'.$OUTPUT->pix_url('/t/up').'" alt="'.get_string('desc').'" />';
            } else if ($reportsettings->sortby == 'lastdesc') {
                $lastarrow = '<img src="'.$OUTPUT->pix_url('/t/up').'" alt="'.get_string('desc').'" />';
            }
            $firstlink = new moodle_url($thisurl, array('sortby' => $firstlink));
            $lastlink = new moodle_url($thisurl, array('sortby' => $lastlink));
            $nameheading = ' <a href="'.$firstlink.'" >'.get_string('firstname').'</a> '.$firstarrow;
            $nameheading .= ' / <a href="'.$lastlink.'" >'.get_string('lastname').'</a> '.$lastarrow;

            $table = new stdClass;
            $table->head = array($nameheading);
            $table->level = array(-1);
            $table->size = array('100px');
            $table->skip = array(false);
            foreach ($this->items as $item) {
                if ($item->hidden) {
                    continue;
                }

                $table->head[] = s($item->displaytext);
                $table->level[] = ($item->indent < 3) ? $item->indent : 2;
                $table->size[] = '80px';
                $table->skip[] = (!$reportsettings->showoptional) && ($item->itemoptional == CHECKSKILL_OPTIONAL_YES);
            }

            $table->data = array();
            if ($ausers) {
                foreach ($ausers as $auser) {
                    $row = array();

                    $vslink = ' <a href="'.$thisurl->out(true, array('studentid'=>$auser->id) ).'" ';
                    $vslink .= 'alt="'.get_string('viewsinglereport','checkskill').'" title="'.get_string('viewsinglereport','checkskill').'">';
                    $vslink .= '<img src="'.$OUTPUT->pix_url('/t/preview').'" /></a>';
                    $userurl = new moodle_url('/user/view.php', array('id'=>$auser->id, 'course'=>$this->course->id) );
                    $userlink = '<a href="'.$userurl.'">'.fullname($auser).'</a>';

                    $row[] = $userlink.$vslink;

                    $sql = 'SELECT i.id, i.itemoptional, i.hidden, c.usertimestamp, c.teachermark FROM {checkskill_item} i LEFT JOIN {checkskill_check} c ';
                    $sql .= 'ON (i.id = c.item AND c.userid = ? ) WHERE i.checkskill = ? AND i.userid=0 ORDER BY i.position';
                    $checks = $DB->get_records_sql($sql, array($auser->id, $this->checkskill->id) );

                    foreach ($checks as $check) {
                        if ($check->hidden) {
                            continue;
                        }

                        if ($check->itemoptional == CHECKSKILL_OPTIONAL_HEADING) {
                            $row[] = array(false, false, true, 0, 0);
                        } else {
                            if ($check->usertimestamp > 0) {
                                $row[] = array($check->teachermark,true,false, $auser->id, $check->id);
                            } else {
                                $row[] = array($check->teachermark,false,false, $auser->id, $check->id);
                            }
                        }
                    }

                    $table->data[] = $row;
					
                    if ($editchecks) {
                        echo '<input type="hidden" name="userids[]" value="'.$auser->id.'" />';
                    }					
                }
            }

            echo '<div style="overflow:auto">';
            $this->print_report_table($table, $editchecks);
            echo '</div>';

            if ($editchecks) {
                echo '<input type="submit" name="submit" value="'.get_string('savechecks','checkskill').'" />';
                echo '</form>';
            }
        }
    }

    /**
     * This function gets called when we are in editing mode
     * adding the button the the row
     *
     * @table object object being parsed
     * @param $table
     * @return string Return ammended code to output
     */
    function report_add_toggle_button_row($table) {
        global $PAGE;

        if (!$table->data) {
            return '';
        }

        $PAGE->requires->yui_module('moodle-mod_checkskill-buttons', 'M.mod_checkskill.buttons.init');
        $passed_row = $table->data;
        $ret_output = '';
        $ret_output .= '<tr class="r1">';
        foreach ($passed_row[0] as $key => $item) {
            if ($key == 0) {
                // Left align + colspan of 2 (overlapping the button column).
                $ret_output .= '<td colspan="2" style=" text-align: left; width: '.$table->size[0].';" class="cell c0"></td>';
            } else {
                $size = $table->size[$key];
                $cellclass = 'cell c'.$key.' level'.$table->level[$key];
                list($teachermark, $studentmark, $heading, $userid, $checkid) = $item;
                if ($heading) {
                    // 'Heading' items have no buttons.
                    $ret_output .= '<td style=" text-align: center; width: '.$size.';" class="cell c0">&nbsp;</td>';
                } else {
                    // Not a 'heading' item => add a button.
                    $ret_output .= '<td style=" text-align: center; width: '.$size.';" class="'.$cellclass.'">';
                    $ret_output .= html_writer::tag('button', get_string('togglecolumn', 'checkskill'),
                                                    array('class' => 'make_col_c',
                                                          'id' => $checkid,
                                                          'type' => 'button'));
                    $ret_output .= '</td>';
                }
            }
        }
        $ret_output .= '</tr>';
        return $ret_output;
    }


	
    function print_report_table($table, $editchecks) {
        global $OUTPUT, $CFG;

        $output = '';

        $output .= '<table summary="'.get_string('reporttablesummary','checkskill').'"';
        $output .= ' cellpadding="5" cellspacing="1" class="generaltable boxaligncenter checkskillreport">';

        $showteachermark = !($this->checkskill->teacheredit == CHECKSKILL_MARKING_STUDENT);
        $showstudentmark = !($this->checkskill->teacheredit == CHECKSKILL_MARKING_TEACHER);
        $teachermarklocked = $this->checkskill->lockteachermarks && !has_capability('mod/checkskill:updatelocked', $this->context);

        // Sort out the heading row
        $output .= '<tr>';
        $keys = array_keys($table->head);
        $lastkey = end($keys);
        foreach ($table->head as $key => $heading) {
            if ($table->skip[$key]) {
                continue;
            }
            $size = $table->size[$key];
            $levelclass = ' head'.$table->level[$key];
            if ($key == $lastkey) {
                $levelclass .= ' lastcol';
            }
            // If statement to judge if the header is the first cell in the row, if so the <th> needs colspan=2 added
            // to cover the extra column added (containing the toggle button) to retain the correct table structure
            $colspan = '';
            if ($key == 0  && $editchecks) {
                $colspan = 'colspan="2"';
            }
            $output .= '<th '.$colspan.' style="vertical-align:top; text-align: center; width:'.$size.'" class="header c'.$key.$levelclass.'" scope="col">';
            $output .= $heading.'</th>';
        }
        $output .= '</tr>';

        // if we are in editing mode, run the add_row function that adds the button and necessary code to the document
        if ($editchecks) {
            $output .= $this->report_add_toggle_button_row($table);
        }
        // Output the data
        if ($CFG->version < 2013111800) {
            $tickimg = '<img src="'.$OUTPUT->pix_url('i/tick_green_big').'" alt="'.get_string('itemcomplete','checkskill').'" />';
        } else {
            $tickimg = '<img src="'.$OUTPUT->pix_url('i/grade_correct').'" alt="'.get_string('itemcomplete','checkskill').'" />';
        }
        $teacherimg = array(CHECKSKILL_TEACHERMARK_UNDECIDED => '<img src="'.$OUTPUT->pix_url('empty_box','checkskill').'" alt="'.get_string('teachermarkundecided','checkskill').'" />',
                            CHECKSKILL_TEACHERMARK_YES => '<img src="'.$OUTPUT->pix_url('tick_box','checkskill').'" alt="'.get_string('teachermarkyes','checkskill').'" />',
                            CHECKSKILL_TEACHERMARK_NO => '<img src="'.$OUTPUT->pix_url('cross_box','checkskill').'" alt="'.get_string('teachermarkno','checkskill').'" />');
        $oddeven = 1;
        $keys = array_keys($table->data);
        $lastrowkey = end($keys);
        foreach ($table->data as $key => $row) {
            $oddeven = $oddeven ? 0 : 1;
            $class = '';
            if ($key == $lastrowkey) {
                $class = ' lastrow';
            }

            $output .= '<tr class="r'.$oddeven.$class.'">';
            $keys2 = array_keys($row);
            $lastkey = end($keys2);
            foreach ($row as $colkey => $item) {
                if ($table->skip[$colkey]) {
                    continue;
                }
                if ($colkey == 0) {
                    // First item is the name
                    $output .= '<td style=" text-align: left; width: '.$table->size[0].';" class="cell c0">'.$item.'</td>';
                } else {
                    $size = $table->size[$colkey];
                    $img = '&nbsp;';
                    $cellclass = 'level'.$table->level[$colkey];
                    list($teachermark, $studentmark, $heading, $userid, $checkid) = $item;
                    // if statement to add button at beginning of row in edting mode.
                    if ($colkey == 1 && $editchecks) {
                        $output .= '<td style=" text-align: center; width: '.$size.';" class="'.$cellclass.'">';
                        $output .= html_writer::tag('button', get_string('togglerow', 'checkskill'),
                                                    array('class' => 'make_c',
                                                          'id' => $userid,
                                                          'type' => 'button'));
                        $output .= '</td>';
                    }
                    if ($heading) {
                        $output .= '<td style=" text-align: center; width: '.$size.';" class="cell c'.$colkey.' reportheading">&nbsp;</td>';
                    } else {
                        if ($showteachermark) {
                            if ($teachermark == CHECKSKILL_TEACHERMARK_YES) {
                                $cellclass .= '-checked';
                                $img = $teacherimg[$teachermark];
                            } else if ($teachermark == CHECKSKILL_TEACHERMARK_NO) {
                                $cellclass .= '-unchecked';
                                $img = $teacherimg[$teachermark];
                            } else {
                                $img = $teacherimg[CHECKSKILL_TEACHERMARK_UNDECIDED];
                            }

                            if ($editchecks) {
                                $disabled = ($teachermarklocked && $teachermark == CHECKSKILL_TEACHERMARK_YES) ? 'disabled="disabled" ' : '';

                                $selu = ($teachermark == CHECKSKILL_TEACHERMARK_UNDECIDED) ? 'selected="selected" ' : '';
                                $sely = ($teachermark == CHECKSKILL_TEACHERMARK_YES) ? 'selected="selected" ' : '';
                                $seln = ($teachermark == CHECKSKILL_TEACHERMARK_NO) ? 'selected="selected" ' : '';

                                $img = '<select name="items_'.$userid.'['.$checkid.']" '.$disabled.'>';
                                $img .= '<option value="'.CHECKSKILL_TEACHERMARK_UNDECIDED.'" '.$selu.'></option>';
                                $img .= '<option value="'.CHECKSKILL_TEACHERMARK_YES.'" '.$sely.'>'.get_string('yes').'</option>';
                                $img .= '<option value="'.CHECKSKILL_TEACHERMARK_NO.'" '.$seln.'>'.get_string('no').'</option>';
                                $img .= '</select>';
                            }
                        }
                        if ($showstudentmark) {
                            if ($studentmark) {
                                if (!$showteachermark) {
                                    $cellclass .= '-checked';
                                }
                                $img .= $tickimg;
                            }
                        }

                        $cellclass .= ' cell c'.$colkey;

                        if ($colkey == $lastkey) {
                            $cellclass .= ' lastcol';
                        }

                        $output .= '<td style=" text-align: center; width: '.$size.';" class="'.$cellclass.'">'.$img.'</td>';
                    }
                }
            }
            $output .= '</tr>';
        }

        $output .= '</table>';

        echo $output;
    }

    function view_footer() {
        global $OUTPUT;
        echo $OUTPUT->footer();
    }

	
    function process_view_actions() {
        global $CFG;

        $this->useredit = optional_param('useredit', false, PARAM_BOOL);

        $action = optional_param('action', false, PARAM_TEXT);
        if (!$action) {
            return;
        }

        if (!confirm_sesskey()) {
            print_error('error_sesskey', 'checkskill');
        }

        $itemid = optional_param('itemid', 0, PARAM_INT);

        switch($action) {
        case 'updatechecks':
            if ($CFG->version < 2011120100) {
                $newchecks = optional_param('items', array(), PARAM_INT);
            } else {
                $newchecks = optional_param_array('items', array(), PARAM_INT);
            }
            $this->updatechecks($newchecks);
            break;

        case 'startadditem':
            $this->additemafter = $itemid;
            break;

        case 'edititem':
            if ($this->useritems && isset($this->useritems[$itemid])) {
                $this->useritems[$itemid]->editme = true;
            }
            break;

        case 'additem':
            $displaytext = optional_param('displaytext', '', PARAM_TEXT);
            $displaytext .= "\n".optional_param('displaytextnote', '', PARAM_TEXT);
            $position = optional_param('position', false, PARAM_INT);
            $this->additem($displaytext, $this->userid, 0, $position);
            $item = $this->get_item_at_position($position);
            if ($item) {
                $this->additemafter = $item->id;
            }
            break;

        case 'deleteitem':
            $this->deleteitem($itemid);
            break;

        case 'updateitem':
            $displaytext = optional_param('displaytext', '', PARAM_TEXT);
            $displaytext .= "\n".optional_param('displaytextnote', '', PARAM_TEXT);
            $this->updateitemtext($itemid, $displaytext);
            break;

        default:
            print_error(get_string('error_action', 'checkskill', s($action))); // 'Invalid action - "{a}"'
        }

        if ($action != 'updatechecks') {
            $this->useredit = true;
        }
    }

    function process_edit_actions() {
	global $CFG;
        $this->editdates = optional_param('editdates', false, PARAM_BOOL);
        $additemafter = optional_param('additemafter', false, PARAM_INT);
        $removeauto = optional_param('removeauto', false, PARAM_TEXT);

        if ($removeauto) {
            // Remove any automatically generated items from the skill
            // (if no longer using automatic items)
            if (!confirm_sesskey()) {
                print_error('error_sesskey', 'checkskill');
            }
            $this->removeauto();
            return;
        }

        $action = optional_param('action', false, PARAM_TEXT);
        if (!$action) {
            $this->additemafter = $additemafter;
            return;
        }

        if (!confirm_sesskey()) {
            print_error('error_sesskey', 'checkskill');
        }

        $itemid = optional_param('itemid', 0, PARAM_INT);

        switch ($action) {
        case 'additem':
            $displaytext = optional_param('displaytext', '', PARAM_TEXT);
            $indent = optional_param('indent', 0, PARAM_INT);
            $position = optional_param('position', false, PARAM_INT);
            if (optional_param('duetimedisable', false, PARAM_BOOL)) {
                $duetime = false;
            } else {
            	if ($CFG->version < 2011120100) {
                	$duetime = optional_param('duetime', false, PARAM_INT);
            	} else {
                	$duetime = optional_param_array('duetime', false, PARAM_INT);
            	}
            }
            $this->additem($displaytext, 0, $indent, $position, $duetime);
            if ($position) {
                $additemafter = false;
            }
            break;
        case 'startadditem':
            $additemafter = $itemid;
            break;
        case 'edititem':
            if (isset($this->items[$itemid])) {
                $this->items[$itemid]->editme = true;
            }
            break;
        case 'updateitem':
            $displaytext = optional_param('displaytext', '', PARAM_TEXT);
            if (optional_param('duetimedisable', false, PARAM_BOOL)) {
                $duetime = false;
            } else {
            	if ($CFG->version < 2011120100) {
                	$duetime = optional_param('duetime', false, PARAM_INT);
            	} else {
                	$duetime = optional_param_array('duetime', false, PARAM_INT);
            	}
            }
            $this->updateitemtext($itemid, $displaytext, $duetime);
            break;
        case 'deleteitem':
            if (($this->checkskill->autopopulate) && (isset($this->items[$itemid])) && ($this->items[$itemid]->moduleid)) {
                $this->toggledisableitem($itemid);
            } else {
                $this->deleteitem($itemid);
            }
            break;
        case 'moveitemup':
            $this->moveitemup($itemid);
            break;
        case 'moveitemdown':
            $this->moveitemdown($itemid);
            break;
        case 'indentitem':
            $this->indentitem($itemid);
            break;
        case 'unindentitem':
            $this->unindentitem($itemid);
            break;
        case 'makeoptional':
            $this->makeoptional($itemid, true);
            break;
        case 'makerequired':
            $this->makeoptional($itemid, false);
            break;
        case 'makeheading':
            $this->makeoptional($itemid, true, true);
            break;
        case 'nextcolour':
            $this->nextcolour($itemid);
            break;
        default:
            print_error(get_string('error_action', 'checkskill', s($action))); // 'Invalid action - "{a}"'
        }

        if ($additemafter) {
            $this->additemafter = $additemafter;
        }
    }

    function get_report_settings() {
        global $SESSION;

        if (!isset($SESSION->checkskill_report)) {
            $settings = new stdClass;
            $settings->showcompletiondates = false;
            $settings->showoptional = true;
            $settings->showprogressbars = false;
            $settings->sortby = 'firstasc';
            $SESSION->checkskill_report = $settings;
        }
        return clone $SESSION->checkskill_report; // We want changes to settings to be explicit
    }

    function set_report_settings($settings) {
        global $SESSION, $CFG;

        $currsettings = $this->get_report_settings();
        foreach ($currsettings as $key => $currval) {
            if (isset($settings->$key)) {
                $currsettings->$key = $settings->$key; // Only set values if they already exist
            }
        }
        if ($CFG->debug == DEBUG_DEVELOPER) { // Show dev error if attempting to set non-existent setting
            foreach ($settings as $key => $val) {
                if (!isset($currsettings->$key)) {
                    debugging("Attempting to set invalid setting '$key'", DEBUG_DEVELOPER);
                }
            }
        }

        $SESSION->checkskill_report = $currsettings;
    }

    function process_report_actions() {
        $settings = $this->get_report_settings();

        if ($sortby = optional_param('sortby', false, PARAM_TEXT)) {
            $settings->sortby = $sortby;
            $this->set_report_settings($settings);
        }

        $savenext = optional_param('savenext', false, PARAM_TEXT);
        $viewnext = optional_param('viewnext', false, PARAM_TEXT);
        $action = optional_param('action', false, PARAM_TEXT);
        if (!$action) {
            return;
        }

        if (!confirm_sesskey()) {
            print_error('error_sesskey', 'checkskill'); // 'Invalid sesskey';
        }

        switch ($action) {
        case 'showprogressbars':
            $settings->showprogressbars = true;
            break;
        case 'hideprogressbars':
            $settings->showprogressbars = false;
            break;
        case 'showoptional':
            $settings->showoptional = true;
            break;
        case 'hideoptional':
            $settings->showoptional = false;
            break;
        case 'updatechecks':
            if ($this->caneditother() && !$viewnext) {
                $this->updateteachermarks();
            }
            break;
        case 'updateallchecks':
            if ($this->caneditother()) {
                $this->updateallteachermarks();
            }
            break;
        case 'toggledates':
            $settings->showcompletiondates = !$settings->showcompletiondates;
            break;
        }

        $this->set_report_settings($settings);

        if ($viewnext || $savenext) {
            $this->getnextuserid();
            $this->get_items();
        }
    }

    function additem($displaytext, $userid=0, $indent=0, $position=false, $duetime=false, $moduleid=0, $optional=CHECKSKILL_OPTIONAL_NO, $hidden=CHECKSKILL_HIDDEN_NO) {
        global $DB;

        $displaytext = trim($displaytext);
        if ($displaytext == '') {
            return false;
        }

        if ($userid) {
            if (!$this->canaddown()) {
                return false;
            }
        } else {
            if (!$moduleid && !$this->canedit()) {
                // $moduleid entries are added automatically, if the activity exists; ignore canedit check
                return false;
            }
        }

        $item = new stdClass;
        $item->checkskill = $this->checkskill->id;
        $item->displaytext = $displaytext;
        if ($position) {
            $item->position = $position;
        } else {
            $item->position = count($this->items) + 1;
        }
        $item->indent = $indent;
        $item->userid = $userid;
        $item->itemoptional = $optional;
        $item->hidden = $hidden;
        $item->duetime = 0;
        if ($duetime) {
            $item->duetime = make_timestamp($duetime['year'], $duetime['month'], $duetime['day']);
        }
        $item->eventid = 0;
        $item->colour = 'black';
        $item->moduleid = $moduleid;
        $item->checked = false;

        $item->id = $DB->insert_record('checkskill_item', $item);
        if ($item->id) {
            if ($userid) {
                $this->useritems[$item->id] = $item;
                $this->useritems[$item->id]->checked = false;
                if ($position) {
                    uasort($this->useritems, 'checkskill_itemcompare');
                }
            } else {
                if ($position) {
                    $this->additemafter = $item->id;
                    $this->update_item_positions(1, $position);
                }
                $this->items[$item->id] = $item;
                $this->items[$item->id]->checked = false;
                $this->items[$item->id]->teachermark = CHECKSKILL_TEACHERMARK_UNDECIDED;
                uasort($this->items, 'checkskill_itemcompare');
                if ($this->checkskill->duedatesoncalendar) {
                    $this->setevent($item->id, true);
                }
            }
        }

        return $item->id;
    }

    function setevent($itemid, $add) {
        global $DB;

        $item = $this->items[$itemid];
        $update = false;

        if  ((!$add) || ($item->duetime == 0)) {  // Remove the event (if any)
            if (!$item->eventid) {
                return; // No event to remove
            }

            delete_event($item->eventid);
            $this->items[$itemid]->eventid = 0;
            $update = true;

        } else {  // Add/update event
            $event = new stdClass;
            $event->name = $item->displaytext;
            $event->description = get_string('calendardescription', 'checkskill', $this->checkskill->name);
            $event->courseid = $this->course->id;
            $event->modulename = 'checkskill';
            $event->instance = $this->checkskill->id;
            $event->eventtype = 'due';
            $event->timestart = $item->duetime;

            if ($item->eventid) {
                $event->id = $item->eventid;
                update_event($event);
            } else {
                $this->items[$itemid]->eventid = add_event($event);
                $update = true;
            }
        }

        if ($update) { // Event added or removed
            $upditem = new stdClass;
            $upditem->id = $itemid;
            $upditem->eventid = $this->items[$itemid]->eventid;
            $DB->update_record('checkskill_item', $upditem);
        }
    }

    function setallevents() {
        if (!$this->items) {
            return;
        }

        $add = $this->checkskill->duedatesoncalendar;
        foreach ($this->items as $key => $value) {
            $this->setevent($key, $add);
        }
    }

    function updateitemtext($itemid, $displaytext, $duetime=false) {
        global $DB;

        $displaytext = trim($displaytext);
        if ($displaytext == '') {
            return;
        }

        if (isset($this->items[$itemid])) {
            if ($this->canedit()) {
                $this->items[$itemid]->displaytext = $displaytext;
                $upditem = new stdClass;
                $upditem->id = $itemid;
                $upditem->displaytext = $displaytext;

                $upditem->duetime = 0;
                if ($duetime) {
                    $upditem->duetime = make_timestamp($duetime['year'], $duetime['month'], $duetime['day']);
                }
                $this->items[$itemid]->duetime = $upditem->duetime;

                $DB->update_record('checkskill_item', $upditem);

                if ($this->checkskill->duedatesoncalendar) {
                    $this->setevent($itemid, true);
                }
            }
        } else if (isset($this->useritems[$itemid])) {
            if ($this->canaddown()) {
                $this->useritems[$itemid]->displaytext = $displaytext;
                $upditem = new stdClass;
                $upditem->id = $itemid;
                $upditem->displaytext = $displaytext;
                $DB->update_record('checkskill_item', $upditem);
            }
        }
    }

    function toggledisableitem($itemid) {
        global $DB;

        if (isset($this->items[$itemid])) {
            if (!$this->canedit()) {
                return;
            }

            $item = $this->items[$itemid];
            if ($item->hidden == CHECKSKILL_HIDDEN_NO) {
                $item->hidden = CHECKSKILL_HIDDEN_MANUAL;
            } else if ($item->hidden == CHECKSKILL_HIDDEN_MANUAL) {
                $item->hidden = CHECKSKILL_HIDDEN_NO;
            }

            $upditem = new stdClass;
            $upditem->id = $itemid;
            $upditem->hidden = $item->hidden;
            $DB->update_record('checkskill_item', $upditem);

            // If the item is a section heading, then show/hide all items in that section
            if ($item->itemoptional == CHECKSKILL_OPTIONAL_HEADING) {
                if ($item->hidden) {
                    foreach ($this->items as $it) {
                        if ($it->position <= $item->position) {
                            continue;
                        }
                        if ($it->itemoptional == CHECKSKILL_OPTIONAL_HEADING) {
                            break;
                        }
                        if (!$it->moduleid) {
                            continue;
                        }
                        if ($it->hidden == CHECKSKILL_HIDDEN_NO) {
                            $it->hidden = CHECKSKILL_HIDDEN_MANUAL;
                            $upditem = new stdClass;
                            $upditem->id = $it->id;
                            $upditem->hidden = $it->hidden;
                            $DB->update_record('checkskill_item', $upditem);
                        }
                    }

                } else {

                    foreach ($this->items as $it) {
                        if ($it->position <= $item->position) {
                            continue;
                        }
                        if ($it->itemoptional == CHECKSKILL_OPTIONAL_HEADING) {
                            break;
                        }
                        if (!$it->moduleid) {
                            continue;
                        }
                        if ($it->hidden == CHECKSKILL_HIDDEN_MANUAL) {
                            $it->hidden = CHECKSKILL_HIDDEN_NO;
                            $upditem = new stdClass;
                            $upditem->id = $it->id;
                            $upditem->hidden = $it->hidden;
                            $DB->update_record('checkskill_item', $upditem);
                        }
                    }
                }
            }
            checkskill_update_grades($this->checkskill);
        }
    }

	function deleteitem($itemid, $forcedelete=false) {
        global $DB;

        if (isset($this->items[$itemid])) {
            if (!$forcedelete && !$this->canedit()) {
                return;
            }
            $this->setevent($itemid, false); // Remove any calendar events
            unset($this->items[$itemid]);
        } else if (isset($this->useritems[$itemid])) {
            if (!$this->canaddown()) {
                return;
            }
            unset($this->useritems[$itemid]);
        } else {
            // Item for deletion is not currently available
            return;
        }

        $DB->delete_records('checkskill_item', array('id' => $itemid) );
        $DB->delete_records('checkskill_check', array('item' => $itemid) );

		//  MODIF JF Checkskill ******************************************************
        // Descriptions
        $descriptions = $DB->get_records('checkskill_description', array('itemid' => $itemid));
        if ($descriptions) {
            foreach($descriptions as $description){
                if (!empty($description)){
                    // Documents
                    $DB->delete_records('checkskill_document', array('descriptionid' => $description->id));
                    // Descriptions
                    $DB->delete_records('checkskill_description', array('id' => $description->id));
                }
            }
        }
        // *****************************************************************************
        $this->update_item_positions();
    }

    function moveitemto($itemid, $newposition, $forceupdate=false) {
        global $DB;

        if (!isset($this->items[$itemid])) {
            if (isset($this->useritems[$itemid])) {
                if ($this->canupdateown()) {
                    $this->useritems[$itemid]->position = $newposition;
                    $upditem = new stdClass;
                    $upditem->id = $itemid;
                    $upditem->position = $newposition;
                    $DB->update_record('checkskill_item', $upditem);
                }
            }
            return;
        }

        if (!$forceupdate && !$this->canedit()) {
            return;
        }

        $itemcount = count($this->items);
        if ($newposition < 1) {
            $newposition = 1;
        } else if ($newposition > $itemcount) {
            $newposition = $itemcount;
        }

        $oldposition = $this->items[$itemid]->position;
        if ($oldposition == $newposition) {
            return;
        }

        if ($newposition < $oldposition) {
            $this->update_item_positions(1, $newposition, $oldposition); // Move items down
        } else {
            $this->update_item_positions(-1, $oldposition, $newposition); // Move items up (including this one)
        }

        $this->items[$itemid]->position = $newposition; // Move item to new position
        uasort($this->items, 'checkskill_itemcompare'); // Sort the array by position
        $upditem = new stdClass;
        $upditem->id = $itemid;
        $upditem->position = $newposition;
        $DB->update_record('checkskill_item', $upditem); // Update the database
    }

    function moveitemup($itemid) {
        // TODO If indented, only allow move if suitable space for 'reparenting'

        if (!isset($this->items[$itemid])) {
            if (isset($this->useritems[$itemid])) {
                $this->moveitemto($itemid, $this->useritems[$itemid]->position - 1);
            }
            return;
        }
        $this->moveitemto($itemid, $this->items[$itemid]->position - 1);
    }

    function moveitemdown($itemid) {
        // TODO If indented, only allow move if suitable space for 'reparenting'

        if (!isset($this->items[$itemid])) {
            if (isset($this->useritems[$itemid])) {
                $this->moveitemto($itemid, $this->useritems[$itemid]->position + 1);
            }
            return;
        }
        $this->moveitemto($itemid, $this->items[$itemid]->position + 1);
    }

    function indentitemto($itemid, $indent) {
        global $DB;

        if (!isset($this->items[$itemid])) {
            // Not able to indent useritems, as they are always parent + 1
            return;
        }

        $position = $this->items[$itemid]->position;
        if ($position == 1) {
            $indent = 0;
        }

        if ($indent < 0) {
            $indent = 0;
        } else if ($indent > CHECKSKILL_MAX_INDENT) {
            $indent = CHECKSKILL_MAX_INDENT;
        }

        $oldindent = $this->items[$itemid]->indent;
        $adjust = $indent - $oldindent;
        if ($adjust == 0) {
            return;
        }
        $this->items[$itemid]->indent = $indent;
        $upditem = new stdClass;
        $upditem->id = $itemid;
        $upditem->indent = $indent;
        $DB->update_record('checkskill_item', $upditem);

        // Update all 'children' of this item to new indent
        foreach ($this->items as $item) {
            if ($item->position > $position) {
                if ($item->indent > $oldindent) {
                    $item->indent += $adjust;
                    $upditem = new stdClass;
                    $upditem->id = $item->id;
                    $upditem->indent = $item->indent;
                    $DB->update_record('checkskill_item', $upditem);
                } else {
                    break;
                }
            }
        }
    }

    function indentitem($itemid) {
        if (!isset($this->items[$itemid])) {
            // Not able to indent useritems, as they are always parent + 1
            return;
        }
        $this->indentitemto($itemid, $this->items[$itemid]->indent + 1);
    }

    function unindentitem($itemid) {
        if (!isset($this->items[$itemid])) {
            // Not able to indent useritems, as they are always parent + 1
            return;
        }
        $this->indentitemto($itemid, $this->items[$itemid]->indent - 1);
    }

    function makeoptional($itemid, $optional, $heading=false) {
        global $DB;

        if (!isset($this->items[$itemid])) {
            return;
        }

        if ($heading) {
            $optional = CHECKSKILL_OPTIONAL_HEADING;
        } else if ($optional) {
            $optional = CHECKSKILL_OPTIONAL_YES;
        } else {
            $optional = CHECKSKILL_OPTIONAL_NO;
        }

        if ($this->items[$itemid]->moduleid) {
            $op = $this->items[$itemid]->itemoptional;
            if ($op == CHECKSKILL_OPTIONAL_HEADING) {
                return; // Topic headings must stay as headings
            } else if ($this->items[$itemid]->itemoptional == CHECKSKILL_OPTIONAL_YES) {
                $optional = CHECKSKILL_OPTIONAL_NO; // Module links cannot become headings
            } else {
                $optional = CHECKSKILL_OPTIONAL_YES;
            }
        }

        $this->items[$itemid]->itemoptional = $optional;
        $upditem = new stdClass;
        $upditem->id = $itemid;
        $upditem->itemoptional = $optional;
        $DB->update_record('checkskill_item', $upditem);
    }

    function nextcolour($itemid) {
        global $DB;

        if (!isset($this->items[$itemid])) {
            return;
        }

        switch ($this->items[$itemid]->colour) {
        case 'black':
            $nextcolour='red';
            break;
        case 'red':
            $nextcolour='orange';
            break;
        case 'orange':
            $nextcolour='green';
            break;
        case 'green':
            $nextcolour='purple';
            break;
        default:
            $nextcolour='black';
        }

        $upditem = new stdClass;
        $upditem->id = $itemid;
        $upditem->colour = $nextcolour;
        $DB->update_record('checkskill_item', $upditem);
        $this->items[$itemid]->colour = $nextcolour;
    }

    function ajaxupdatechecks($changechecks) {
        // Convert array of itemid=>true/false, into array of all 'checked' itemids
        $newchecks = array();
        foreach ($this->items as $item) {
            if (array_key_exists($item->id, $changechecks)) {
                if ($changechecks[$item->id]) {
                    // Include in array if new status is true
                    $newchecks[] = $item->id;
                }
            } else {
                // If no new status, include in array if checked
                if ($item->checked) {
                    $newchecks[] = $item->id;
                }
            }
        }
        if ($this->useritems) {
            foreach ($this->useritems as $item) {
                if (array_key_exists($item->id, $changechecks)) {
                    if ($changechecks[$item->id]) {
                        // Include in array if new status is true
                        $newchecks[] = $item->id;
                    }
                } else {
                    // If no new status, include in array if checked
                    if ($item->checked) {
                        $newchecks[] = $item->id;
                    }
                }
            }
        }

        $this->updatechecks($newchecks);
    }

    function updatechecks($newchecks) {
        global $DB, $CFG;

        if (!is_array($newchecks)) {
            // Something has gone wrong, so update nothing
            return;
        }

       if ($CFG->version > 2014051200) { // Moodle 2.7+
            $params = array(
                'contextid' => $this->context->id,
                'objectid' => $this->checkskill->id,
            );
            $event = \mod_checkskill\event\student_checks_updated::create($params);
            $event->trigger();
        } else { // Before Moodle 2.7
	        add_to_log($this->course->id, 'checkskill', 'update checks', "report.php?id={$this->cm->id}&studentid={$this->userid}", $this->checkskill->name, $this->cm->id);
		}

        $updategrades = false;
        if ($this->items) {
            foreach ($this->items as $item) {
                if (($this->checkskill->autoupdate == CHECKSKILL_AUTOUPDATE_YES) && ($item->moduleid)) {
                    continue; // Shouldn't get updated anyway, but just in case...
                }

                $newval = in_array($item->id, $newchecks);

                if ($newval != $item->checked) {
                    $updategrades = true;
                    $item->checked = $newval;

                    $check = $DB->get_record('checkskill_check', array('item' => $item->id, 'userid' => $this->userid) );
                    if ($check) {
                        if ($newval) {
                            $check->usertimestamp = time();
                        } else {
                            $check->usertimestamp = 0;
                        }

                        $DB->update_record('checkskill_check', $check);

                    } else {
                        $check = new stdClass;
                        $check->item = $item->id;
                        $check->userid = $this->userid;
                        $check->usertimestamp = time();
                        $check->teachertimestamp = 0;
                        $check->teachermark = CHECKSKILL_TEACHERMARK_UNDECIDED;

                        $check->id = $DB->insert_record('checkskill_check', $check);
                    }
                }
            }
        }
        if ($updategrades) {
            checkskill_update_grades($this->checkskill, $this->userid);
        }

        if ($this->useritems) {
            foreach ($this->useritems as $item) {
                $newval = in_array($item->id, $newchecks);

                if ($newval != $item->checked) {
                    $item->checked = $newval;

                    $check = $DB->get_record('checkskill_check', array('item' => $item->id, 'userid' => $this->userid) );
                    if ($check) {
                        if ($newval) {
                            $check->usertimestamp = time();
                        } else {
                            $check->usertimestamp = 0;
                        }
                        $DB->update_record('checkskill_check', $check);

                    } else {
                        $check = new stdClass;
                        $check->item = $item->id;
                        $check->userid = $this->userid;
                        $check->usertimestamp = time();
                        $check->teachertimestamp = 0;
                        $check->teachermark = CHECKSKILL_TEACHERMARK_UNDECIDED;

                        $check->id = $DB->insert_record('checkskill_check', $check);
                    }
                }
            }
        }
    }

    function updateteachermarks() {
        global $USER, $DB, $CFG;

        if ($CFG->version < 2011120100) {
            $newchecks = optional_param('items', array(), PARAM_TEXT);
        } else {
            $newchecks = optional_param_array('items', array(), PARAM_TEXT);
        }
        if (!is_array($newchecks)) {
            // Something has gone wrong, so update nothing
            return;
        }

        $updategrades = false;
        if ($this->checkskill->teacheredit != CHECKSKILL_MARKING_STUDENT) {
            if (!$student = $DB->get_record('user', array('id' => $this->userid))) {
                print_error('error_user', 'checkskill');
            }
            if ($CFG->version > 2014051200) { // Moodle 2.7+
                $params = array(
                    'contextid' => $this->context->id,
                    'objectid' => $this->checkskill->id,
                    'relateduserid' => $this->userid,
                );
                $event = \mod_checkskill\event\teacher_checks_updated::create($params);
                $event->trigger();
            } else { // Before Moodle 2.7
                add_to_log($this->course->id, 'checkskill', 'update checks', "report.php?id={$this->cm->id}&studentid={$this->userid}",
                           $this->checkskill->id, $this->cm->id);
            }

            $teachermarklocked = $this->checkskill->lockteachermarks && !has_capability('mod/checkskill:updatelocked', $this->context);

            foreach ($newchecks as $itemid => $newval) {
                if (isset($this->items[$itemid])) {
                    $item = $this->items[$itemid];

                    if ($teachermarklocked && $item->teachermark == CHECKSKILL_TEACHERMARK_YES) {
                        continue; // Does not have permission to update marks that are already 'Yes'
                    }
                    if ($newval != $item->teachermark) {
                        $updategrades = true;

                        $newcheck = new stdClass;
                        $newcheck->teachertimestamp = time();
                        $newcheck->teachermark = $newval;
						$newcheck->teacherid = $USER->id;

                        $item->teachermark = $newcheck->teachermark;
                        $item->teachertimestamp = $newcheck->teachertimestamp;
						$item->teacherid = $newcheck->teacherid;
						
                        $oldcheck = $DB->get_record('checkskill_check', array('item' => $item->id, 'userid' => $this->userid) );
                        if ($oldcheck) {
                            $newcheck->id = $oldcheck->id;
                            $DB->update_record('checkskill_check', $newcheck);
                        } else {
                            $newcheck->item = $itemid;
                            $newcheck->userid = $this->userid;
                            $newcheck->id = $DB->insert_record('checkskill_check', $newcheck);
                        }
                    }
                }
            }
            if ($updategrades) {
                checkskill_update_grades($this->checkskill, $this->userid);
            }
        }

        if ($CFG->version < 2011120100) {
            $newcomments = optional_param('teachercomment', false, PARAM_TEXT);
        } else {
            $newcomments = optional_param_array('teachercomment', false, PARAM_TEXT);
        }
        if (!$this->checkskill->teachercomments || !$newcomments || !is_array($newcomments)) {
            return;
        }

        list($isql, $iparams) = $DB->get_in_or_equal(array_keys($this->items));
        $commentsunsorted = $DB->get_records_select('checkskill_comment',"userid = ? AND itemid $isql", array_merge(array($this->userid), $iparams) );
        $comments = array();
        foreach ($commentsunsorted as $comment) {
            $comments[$comment->itemid] = $comment;
        }
        foreach ($newcomments as $itemid => $newcomment) {
            $newcomment = trim($newcomment);
            if ($newcomment == '') {
                if (array_key_exists($itemid, $comments)) {
                    $DB->delete_records('checkskill_comment', array('id' => $comments[$itemid]->id) );
                    unset($comments[$itemid]); // Should never be needed, but just in case...
                }
            } else {
                if (array_key_exists($itemid, $comments)) {
                    if ($comments[$itemid]->text != $newcomment) {
                        $updatecomment = new stdClass;
                        $updatecomment->id = $comments[$itemid]->id;
                        $updatecomment->userid = $this->userid;
                        $updatecomment->itemid = $itemid;
                        $updatecomment->commentby = $USER->id;
                        $updatecomment->text = $newcomment;

                        $DB->update_record('checkskill_comment',$updatecomment);
                    }
                } else {
                    $addcomment = new stdClass;
                    $addcomment->itemid = $itemid;
                    $addcomment->userid = $this->userid;
                    $addcomment->commentby = $USER->id;
                    $addcomment->text = $newcomment;

                    $DB->insert_record('checkskill_comment',$addcomment);
                }
            }
        }
    }

    function updateallteachermarks() {
        global $DB, $CFG, $USER;

        if ($this->checkskill->teacheredit == CHECKSKILL_MARKING_STUDENT) {
            // Student only lists do not have teacher marks to update
            return;
        }


        if ($CFG->version < 2011120100) {
            $userids = optional_param('userids', array(), PARAM_INT);
        } else {
            $userids = optional_param_array('userids', array(), PARAM_INT);
        }
        if (!is_array($userids)) {
            // Something has gone wrong, so update nothing
            return;
        }

        $userchecks = array();
        foreach ($userids as $userid) {
            if ($CFG->version < 2011120100) {
                $checkdata = optional_param('items_'.$userid, array(), PARAM_INT);
            } else {
                $checkdata = optional_param_array('items_'.$userid, array(), PARAM_INT);
            }
            if (!is_array($checkdata)) {
                continue;
            }
            foreach ($checkdata as $itemid => $val) {
                if ($val != CHECKSKILL_TEACHERMARK_NO && $val != CHECKSKILL_TEACHERMARK_YES && $val != CHECKSKILL_TEACHERMARK_UNDECIDED) {
                    continue; // Invalid value
                }
                if (!$itemid) {
                    continue;
                }
                if (!array_key_exists($itemid, $this->items)) {
                    continue; // Item is not part of this checkskill
                }
                if (!array_key_exists($userid, $userchecks)) {
                    $userchecks[$userid] = array();
                }
                $userchecks[$userid][$itemid] = $val;
            }
        }

        if (empty($userchecks)) {
            return;
        }

        $teachermarklocked = $this->checkskill->lockteachermarks && !has_capability('mod/checkskill:updatelocked', $this->context);

        foreach ($userchecks as $userid => $items) {
            list($isql, $iparams) = $DB->get_in_or_equal(array_keys($items));
            $params = array_merge(array($userid), $iparams);
            $currentchecks = $DB->get_records_select('checkskill_check', "userid = ? AND item $isql", $params, '', 'item, id, teachermark');
            $updategrades = false;
            foreach ($items as $itemid => $val) {
                if (!array_key_exists($itemid, $currentchecks)) {
                    if ($val == CHECKSKILL_TEACHERMARK_UNDECIDED) {
                        continue; // Do not create an entry for blank marks
                    }

                    // No entry for this item - need to create it
                    $newcheck = new stdClass;
                    $newcheck->item = $itemid;
                    $newcheck->userid = $userid;
                    $newcheck->teachermark = $val;
                    $newcheck->teachertimestamp = time();
                    $newcheck->usertimestamp = 0;
                    $newcheck->teacherid = $USER->id;

                    $DB->insert_record('checkskill_check', $newcheck);
                    $updategrades = true;

                } else if ($currentchecks[$itemid]->teachermark != $val) {
                    if ($teachermarklocked && $currentchecks[$itemid]->teachermark == CHECKSKILL_TEACHERMARK_YES) {
                        continue;
                    }

                    $updcheck = new stdClass;
                    $updcheck->id = $currentchecks[$itemid]->id;
                    $updcheck->teachermark = $val;
                    $updcheck->teachertimestamp = time();
                    $updcheck->teacherid = $USER->id;

                    $DB->update_record('checkskill_check', $updcheck);
                    $updategrades = true;
                }
            }
            if ($updategrades) {
                if ($CFG->version > 2014051200) { // Moodle 2.7+
                    $params = array(
                        'contextid' => $this->context->id,
                        'objectid' => $this->checkskill->id,
                        'relateduserid' => $userid,
                    );
                    $event = \mod_checkskill\event\teacher_checks_updated::create($params);
                    $event->trigger();
                }

                checkskill_update_grades($this->checkskill, $userid);
            }
        }
    }

    function update_all_autoupdate_checks() {
        global $DB;

        if (!$this->checkskill->autoupdate) {
            return;
        }

        $users = get_users_by_capability($this->context, 'mod/checkskill:updateown', 'u.id', '', '', '', '', '', false);
        if (!$users) {
            return;
        }
        $userids = implode(',',array_keys($users));

        // Get a list of all the checkskill items with a module linked to them (ignoring headings)
        $sql = "SELECT cm.id AS cmid, m.name AS mod_name, i.id AS itemid, cm.completion AS completion
        FROM {modules} m, {course_modules} cm, {checkskill_item} i
        WHERE m.id = cm.module AND cm.id = i.moduleid AND i.moduleid > 0 AND i.checkskill = ? AND i.itemoptional != 2";

        $completion = new completion_info($this->course);
        $using_completion = $completion->is_enabled();

        $items = $DB->get_records_sql($sql, array($this->checkskill->id));
        foreach ($items as $item) {
            if ($using_completion && $item->completion) {
                $fakecm = new stdClass();
                $fakecm->id = $item->cmid;

                foreach ($users as $user) {
                    $comp_data = $completion->get_data($fakecm, false, $user->id);
                    if ($comp_data->completionstate == COMPLETION_COMPLETE || $comp_data->completionstate == COMPLETION_COMPLETE_PASS) {
                        $check = $DB->get_record('checkskill_check', array('item' => $item->itemid, 'userid' => $user->id));
                        if ($check) {
                            if ($check->usertimestamp) {
                                continue;
                            }
                            $check->usertimestamp = time();
                            $DB->update_record('checkskill_check', $check);
                        } else {
                            $check = new stdClass;
                            $check->item = $item->itemid;
                            $check->userid = $user->id;
                            $check->usertimestamp = time();
                            $check->teachertimestamp = 0;
                            $check->teachermark = CHECKSKILL_TEACHERMARK_UNDECIDED;

                            $check->id = $DB->insert_record('checkskill_check', $check);
                        }
                    }
                }

                continue;
            }

            $logaction = '';
            $logaction2 = false;

            switch($item->mod_name) {
            case 'survey':
                $logaction = 'submit';
                break;
            case 'quiz':
                $logaction = 'close attempt';
                break;
            case 'forum':
                $logaction = 'add post';
                $logaction2 = 'add discussion';
                break;
            case 'resource':
                $logaction = 'view';
                break;
            case 'hotpot':
                $logaction = 'submit';
                break;
            case 'wiki':
                $logaction = 'edit';
                break;
            case 'checkskill':
                $logaction = 'complete';
                break;
            case 'choice':
                $logaction = 'choose';
                break;
            case 'lams':
                $logaction = 'view';
                break;
            case 'scorm':
                $logaction = 'view';
                break;
            case 'assignment':
                $logaction = 'upload';
                break;
            case 'journal':
                $logaction = 'add entry';
                break;
            case 'lesson':
                $logaction = 'end';
                break;
            case 'realtimequiz':
                $logaction = 'submit';
                break;
            case 'workshop':
                $logaction = 'submit';
                break;
            case 'glossary':
                $logaction = 'add entry';
                break;
            case 'data':
                $logaction = 'add';
                break;
            case 'chat':
                $logaction = 'talk';
                break;
            case 'feedback':
                $logaction = 'submit';
                break;
            default:
                continue 2;
                break;
            }

            $sql = 'SELECT DISTINCT userid ';
            $sql .= "FROM {log} ";
            $sql .= "WHERE cmid = ? AND (action = ?";
            if ($logaction2) {
                $sql .= ' OR action = ?';
            }
            $sql .= ") AND userid IN ($userids)";
            $log_entries = $DB->get_records_sql($sql, array($item->cmid, $logaction, $logaction2));

            if (!$log_entries) {
                continue;
            }

            foreach ($log_entries as $entry) {
                $check = $DB->get_record('checkskill_check', array('item' => $item->itemid, 'userid' => $entry->userid));
                if ($check) {
                    if ($check->usertimestamp) {
                        continue;
                    }
                    $check->usertimestamp = time();
                    $DB->update_record('checkskill_check', $check);
                } else {
                    $check = new stdClass;
                    $check->item = $item->itemid;
                    $check->userid = $entry->userid;
                    $check->usertimestamp = time();
                    $check->teachertimestamp = 0;
                    $check->teachermark = CHECKSKILL_TEACHERMARK_UNDECIDED;

                    $check->id = $DB->insert_record('checkskill_check', $check);
                }
            }

            // Always update the grades
            checkskill_update_grades($this->checkskill);
        }
    }


    // Update the userid to point to the next user to view
    function getnextuserid() {
        global $DB;

        $activegroup = groups_get_activity_group($this->cm, true);
        $settings = $this->get_report_settings();
        switch ($settings->sortby) {
        case 'firstdesc':
            $orderby = 'ORDER BY u.firstname DESC';
            break;

        case 'lastasc':
            $orderby = 'ORDER BY u.lastname';
            break;

        case 'lastdesc':
            $orderby = 'ORDER BY u.lastname DESC';
            break;

        default:
            $orderby = 'ORDER BY u.firstname';
            break;
        }

        $ausers = false;
        if ($users = get_users_by_capability($this->context, 'mod/checkskill:updateown', 'u.id', '', '', '', $activegroup, '', false)) {
            $users = array_keys($users);
            if ($this->only_view_mentee_reports()) {
                $users = $this->filter_mentee_users($users);
            }
            if (!empty($users)) {
                list($usql, $uparams) = $DB->get_in_or_equal($users);
                $ausers = $DB->get_records_sql('SELECT u.id FROM {user} u WHERE u.id '.$usql.$orderby, $uparams);
            }
        }

        $stoponnext = false;
        foreach ($ausers as $user) {
            if ($stoponnext) {
                $this->userid = $user->id;
                return;
            }
            if ($user->id == $this->userid) {
                $stoponnext = true;
            }
        }
        $this->userid = false;
    }

    static function print_user_progressbar($checkskillid, $userid, $width='300px', $showpercent=true, $return=false, $hidecomplete=false) {
        global $OUTPUT;

        list($ticked, $total) = checkskill_class::get_user_progress($checkskillid, $userid);
        if (!$total) {
            return '';
        }
        if ($hidecomplete && ($ticked == $total)) {
            return '';
        }
        $percent = $ticked * 100 / $total;

        // TODO - fix this now that styles.css is included
        $output = '<div class="checkskill_progress_outer" style="width: '.$width.';" >';
        $output .= '<div class="checkskill_progress_inner" style="width:'.$percent.'%; background-image: url('.$OUTPUT->pix_url('progress','checkskill').');" >&nbsp;</div>';
        $output .= '</div>';
        if ($showpercent) {
            $output .= '<span class="checkskill_progress_percent">&nbsp;'.sprintf('%0d%%', $percent).'</span>';
        }
        $output .= '<br style="clear:both;" />';
        if ($return) {
            return $output;
        }

        echo $output;
        return '';
    }

    static function get_user_progress($checkskillid, $userid) {
        global $DB, $CFG;

        $userid = intval($userid); // Just to be on the safe side...

        $checkskill = $DB->get_record('checkskill', array('id' => $checkskillid) );
        if (!$checkskill) {
            return array(false, false);
        }
        $groupings_sel = '';
        if (isset($CFG->enablegroupmembersonly) && $CFG->enablegroupmembersonly && $checkskill->autopopulate) {
            $groupings = checkskill_class::get_user_groupings($userid, $checkskill->course);
            $groupings[] = 0;
            $groupings_sel = ' AND grouping IN ('.implode(',',$groupings).') ';
        }
        $items = $DB->get_records_select('checkskill_item', 'checkskill = ? AND userid = 0 AND itemoptional = '.CHECKSKILL_OPTIONAL_NO.' AND hidden = '.CHECKSKILL_HIDDEN_NO.$groupings_sel, array($checkskill->id), '', 'id');
        if (empty($items)) {
            return array(false, false);
        }
        $total = count($items);
        list($isql, $iparams) = $DB->get_in_or_equal(array_keys($items));
        $params = array_merge(array($userid), $iparams);

        $sql = "userid = ? AND item $isql AND ";
        if ($checkskill->teacheredit == CHECKSKILL_MARKING_STUDENT) {
            $sql .= 'usertimestamp > 0';
        } else {
            $sql .= 'teachermark = '.CHECKSKILL_TEACHERMARK_YES;
        }
        $ticked = $DB->count_records_select('checkskill_check', $sql, $params);

        return array($ticked, $total);
    }

    function get_user_groupings($userid, $courseid) {
        global $DB;
        $sql = "SELECT gg.groupingid
                  FROM ({groups} g JOIN {groups_members} gm ON g.id = gm.groupid)
                  JOIN {groupings_groups} gg ON gg.groupid = g.id
                  WHERE gm.userid = ? AND g.courseid = ? ";
        $groupings = $DB->get_records_sql($sql, array($userid, $courseid));
        if (!empty($groupings)) {
            return array_keys($groupings);
        }
        return array();
    }


/**
 * Extract skill repository and competency codes from a displaytext field
 * outcome_name;outcome_shortname;outcome_description;scale_name;scale_items;scale_description
 * "C2i2e-2011 A.1-1 :: Identifier les personnes ressources Tic et leurs rôles respectifs (...)";A.1-1;"Identifier les personnes ressources Tic et leurs rôles respectifs au niveau local, régional et national.";"Item référentiel";"Non pertinent,Non validé,Validé";"Ce barème est destiné à évaluer l'acquisition des compétences du module référentiel."
 *  |          |        | description
 *  |          |     ^  separator 2 '::'
 *            ^ separator 1 ' '
 *  |          | competence_code
 *  | referentiel_code
 * @imput displaytext string
 * @output object
 **/
    function get_referentiel_code($displaytext){
    // extract skill repository code and competency code from an outcome_name field
        $item_outcome = new stdClass;
        if (!empty($displaytext)){
            if (preg_match('/(.*)::(.*)/i', trim($displaytext), $matches)){
                if ($matches[1]){
                    if ($keywords = preg_split("/[\s]+/",$matches[1],-1,PREG_SPLIT_NO_EMPTY)){
                        if ($keywords[0] && $keywords[1]){
                            $item_outcome->code_referentiel=trim($keywords[0]);
                            $item_outcome->code_competence=trim($keywords[1]);
                        }
                        else{
                            return NULL;
                        }
                    }
                }
            }
        }
        return $item_outcome;
    }

/**
 * Items selection for exporting outcomes
 *
 **/
    function view_select_export() {
        global $CFG, $OUTPUT;

        if (!$this->canedit()) {
            redirect(new moodle_url('/mod/checkskill/view.php', array('id' => $this->cm->id)) );
        }

        if ((!$this->items) && $this->canedit()) {
            redirect(new moodle_url('/mod/checkskill/edit.php', array('id' => $this->cm->id)) );
        }

        // $currenttab = 'selectexport';
        $currenttab = 'edit';

        $this->view_header();

        echo $OUTPUT->heading(format_string($this->checkskill->name));

        $this->view_tabs($currenttab);

        add_to_log($this->course->id, 'checkskill', 'view', "selectexport.php?id={$this->cm->id}", $this->checkskill->name, $this->cm->id);

        $this->select_items();

        $this->view_footer();
    }
    
    function select_items($viewother = false) {
        global $DB, $OUTPUT, $PAGE;

        echo '<div align="center"><h3>'.get_string('export_outcomes', 'checkskill').'</h3></div>'."\n";

        echo $OUTPUT->box_start('generalbox boxwidthwide boxaligncenter');

        $thispage = new moodle_url('/mod/checkskill/export_selected_outcomes.php', array('id' => $this->cm->id) );

        echo format_text($this->checkskill->intro, $this->checkskill->introformat);
        echo '<br/>';

        $showcheckbox=true;
        $checkclass = '';
        //$optional = CHECKSKILL_OPTIONAL_NO;
        $optional = '';

        if (!$this->items) {
            print_string('noitems','checkskill');
        } else {
            // looks for referentiel_code
            $referentiel_code='';
            $useitemid=true;
            foreach ($this->items as $item) {
                $outcome=$this->get_referentiel_code($item->displaytext);
                if (!empty($outcome) && !empty($outcome->code_referentiel)){
                    $referentiel_code=$outcome->code_referentiel;
                    if (!empty($outcome->code_competence)){
                        $useitemid=false;
                    }
                    break;
                }
            }

            $focusitem = false;
            if ($referentiel_code){
                echo get_string('confirmreferentielname','checkskill').' &nbsp; &nbsp; '."\n";
            }
            else{
                echo get_string('addreferentielname','checkskill').' &nbsp; &nbsp; '."\n";
            }
            echo $OUTPUT->help_icon('referentiel_codeh','checkskill')."\n";

            echo '<form action="'.$thispage->out_omit_querystring().'" method="post">';
            echo html_writer::input_hidden_params($thispage);
            echo '<input type="text" name="referentielcode" size="30" maxsize="255" value="'.$referentiel_code.'" />';
            echo '<br /><i>'.get_string('useitemid','checkskill').'</i>&nbsp; &nbsp; ';
            if ($useitemid){
                echo '<input type="radio" name="useitemid" id="useitemid" value="1" checked="checked" />'.get_string('yes')."\n";
                echo '<input type="radio" name="useitemid" id="useitemid" value="0" />'.get_string('no')."\n";
            }
            else{
                echo '<input type="radio" name="useitemid" id="useitemid" value="1" />'.get_string('yes')."\n";
                echo '<input type="radio" name="useitemid" id="useitemid" value="0" checked="checked" />'.get_string('no')."\n";
            }
            echo '<br /> '."\n";

            echo '<input type="hidden" name="action" value="selectchecks" />';
            echo '<input type="hidden" name="sesskey" value="'.sesskey().'" />';
            echo '<br />'.get_string('select_items_export','checkskill');
            echo $OUTPUT->help_icon('items_exporth','checkskill')."\n";

            // selection des checkbox
            echo '<br />'."\n";
            echo '<input type="button" name="select_tout_item" id="select_tout_item" value="'.get_string('select_all', 'checkskill').'"  onClick="return checkAllCheckBox()" />'."\n";
            echo '&nbsp; &nbsp; &nbsp; <input type="button" name="select_aucun_items" id="select_aucun_item" value="'.get_string('select_not_any', 'checkskill').'"  onClick="return uncheckAllCheckBox()" />'."\n";
            echo '<br />'."\n";

            echo '<ol class="checkskill" id="checkskillouter">'."\n";
            $currindent = 0;
            $ok_ol=0;
            foreach ($this->items as $item) {
                echo '<li>'."\n";
                while ($item->indent > $currindent) {
                    $currindent++;
                    echo '<ol class="checkskill"><li>'."\n";
                    $ok_ol++;
                }
                $itemname = '"item'.$item->id.'"';
                $checked = ' checked="checked" ';
                switch ($item->colour) {
                case 'red':
                    $itemcolour = 'itemred';
                    break;
                case 'orange':
                    $itemcolour = 'itemorange';
                    break;
                case 'green':
                    $itemcolour = 'itemgreen';
                    break;
                case 'purple':
                    $itemcolour = 'itempurple';
                    break;
                default:
                    $itemcolour = 'itemblack';
                }

                echo '<input class="checkskillitem'.$checkclass.'" type="checkbox" name="items[]" id='.$itemname.$checked.' value="'.$item->id.'" />';
                echo '<label for='.$itemname.$optional.'>'.s($item->displaytext).'</label>';

                if (isset($item->modulelink)) {
                    echo '&nbsp;<a href="'.$item->modulelink.'"><img src="'.$OUTPUT->pix_url('follow_link','checkskill').'" alt="'.get_string('linktomodule','checkskill').'" /></a>';
                }
                echo "\n";


                // Output any user-added items
                if ($this->useritems) {
                    $useritem = current($this->useritems);

                    if ($useritem && ($useritem->position == $item->position)) {

                        echo '<ol class="checkskill">'."\n";
                        while ($useritem && ($useritem->position == $item->position)) {
                            $itemname = '"item'.$useritem->id.'"';
                            echo '<li>'."\n";
                            if ($showcheckbox) {
                                echo '<input class="checkskillitem itemoptional" type="checkbox" name="items[]" id='.$itemname.$checked.' value="'.$useritem->id.'" />';
                            }
                            $splittext = explode("\n",s($useritem->displaytext),2);
                            $splittext[] = '';
                            $text = $splittext[0];
                            $note = str_replace("\n",'<br />',$splittext[1]);
                            echo '<label class="useritem" for='.$itemname.'>'.$text.'</label>';
                            if ($note != '') {
                                echo '<div class="note">'.$note.'</div>';
                            }

                            echo '</li>'."\n";

                            $useritem = next($this->useritems);
                        }
                        echo '</ol>'."\n";
                    }
                }
                while ($item->indent < $currindent) {
                    $currindent--;
                }
                echo '</li>'."\n";
            }
            while ($ok_ol){
                echo '</ol>'."\n";
                $ok_ol--;
            }

            echo '</li></ol>'."\n";

            echo '<input id="checkskillsavechecks" type="submit" name="submit" value="'.get_string('savechecks','checkskill').'" />';
            echo '&nbsp; &nbsp; <input id="quit" type="submit" name="quit" value="'.get_string('quit','checkskill').'" />';

            if ($viewother) {
                    echo '&nbsp;<input type="submit" name="savenext" value="'.get_string('saveandnext').'" />';
                    echo '&nbsp;<input type="submit" name="viewnext" value="'.get_string('next').'" />';
            }
            echo '</form>';


            if ($focusitem) {
                echo '<script type="text/javascript">document.getElementById("'.$focusitem.'").focus();</script>';
            }
        }

        echo $OUTPUT->box_end();
    }
    

     /**
      *     @input an array of items ids
      * @output ?
      *
      **/

    function exportchecks($newchecks) {
        $t_selected_items=array();

        if (is_array($newchecks) && !empty($newchecks)) {

            if ($this->items) {
                foreach ($this->items as $item) {
                    $newval = in_array($item->id, $newchecks);
                    if ($newval) {
                        // stocker
                        $t_selected_items[]=$item;
                    }
                }
            }
            if ($this->useritems) {
                foreach ($this->useritems as $item) {
                    $newval = in_array($item->id, $newchecks);

                    if ($newval) {
                        // stocker
                        $t_selected_items[]=$item;
                    }
                }
            }
        }
        return $t_selected_items;
    }

// MODIF JF Checkskill differs from Checklist ***************************************************************************
	/**
	* New features : description and documents linked to items
	* @author  Jean Fruitet <jean.fruitet@univ-nantes.fr>
	* @package  mod/checkskill
	*/
	
	
    function get_item_description($itemid, $userid){
        //
        global $DB;
        return ($DB->select_record_sql("SELECT * FROM {checkskill_description} WHERE itemid=? AND userid=?", array($itemid, $userid)));
    }


    function edit_description($itemid, $userid) {
        global $DB;
        global $CFG;
        global $OUTPUT;
        global $PAGE;

        $description = NULL;
        $document = NULL;

        if ($CFG->version < 2011120100) {
            $this->context = get_context_instance(CONTEXT_MODULE, $this->cm->id);
        } else {
            $this->context = context_module::instance($this->cm->id);
        }
		

        $thispage = new moodle_url('/mod/checkskill/edit_description.php', array('id' => $this->cm->id) );
        $returnurl = new moodle_url('/mod/checkskill/view.php', array('id' => $this->cm->id));

        $currenttab = 'view';

		if ($CFG->version > 2014051200) { // Moodle 2.7+
            $params = array(
                'contextid' => $this->context->id,
                'objectid' => $this->checkskill->id,
				'other' => array('itemid' => $itemid),
            );
            $event = \mod_checkskill\event\student_edit_description::create($params);
            $event->trigger();
        } else { // Before Moodle 2.7
			add_to_log($this->course->id, 'checkskill', 'view', "edit_description.php?id={$this->cm->id}", $this->checkskill->id, $this->cm->id);
        }        

        if ($itemid && $userid) {
            $item = $DB->get_record('checkskill_item', array("id"=>$itemid));
            $description = $DB->get_record('checkskill_description', array("itemid"=>$itemid, "userid"=>$userid));
            if (!empty($description)){
                $documents = $DB->get_records('checkskill_document', array("descriptionid" => $description->id));
            }

            $mform = new mod_checkskill_description_form(null,
                array('checkskill'=>$this->checkskill->id,
                    'contextid'=>$this->context->id,
                    'itemid'=>$itemid,
                    'userid'=>$userid,
                    'description'=>$description,
                    'msg' => get_string('input_description', 'checkskill')));

            if ($mform->is_cancelled()) {
                redirect($returnurl);
            } else if ($mform->get_data()) {
                if (checkskill_set_description($mform, $this->checkskill->id)){
                    redirect($returnurl, '', 1);
                    die();
                }
            }

            $this->view_header();

            echo $OUTPUT->heading(format_string($this->checkskill->name));

            $this->view_tabs($currenttab);

            echo '<div align="center"><h3>'.get_string('edit_description', 'checkskill').'</h3></div>'."\n";
            echo $OUTPUT->box_start('generalbox boxwidthwide boxaligncenter');
            echo format_text($this->checkskill->intro, $this->checkskill->introformat);
            echo '<br/>';
            echo $item->displaytext;
            echo '<br/>';
            $mform->display();

            echo $OUTPUT->box_end();
            $this->view_footer();
        }
    }


    function edit_upload_document($itemid, $userid, $descriptionid, $documentid=0) {
        global $DB;
        global $CFG;
        global $OUTPUT;
        global $PAGE;

        $document=NULL;
        
       if ($CFG->version < 2011120100) {
            $this->context = get_context_instance(CONTEXT_MODULE, $this->cm->id);
        } else {
            $this->context = context_module::instance($this->cm->id);
        }
        
        $thispage = new moodle_url('/mod/checkskill/edit_document.php', array('id' => $this->cm->id) );
        $returl = new moodle_url('/mod/checkskill/view.php', array('id' => $this->cm->id));

        $currenttab = 'view';

		if ($CFG->version > 2014051200) { // Moodle 2.7+
            $params = array(
                'contextid' => $this->context->id,
                'objectid' => $this->checkskill->id,
            );
            $event = \mod_checkskill\event\edit_page_viewed::create($params);
            $event->trigger();
        } else { // Before Moodle 2.7
			add_to_log($this->course->id, 'checkskill', 'view', "edit_document.php?id={$this->cm->id}", $this->checkskill->id, $this->cm->id);
        }        

        

        if (empty($descriptionid) && $itemid && $userid) {
            $description = $DB->get_record('checkskill_description', array("itemid"=>$itemid, "userid"=>$userid));
            if ($description){
                $descriptionid=$description;
            }
        }
        
        if ($descriptionid){
            // Is there any document attached to that description
            if ($documentid) {
                $document = $DB->get_record('checkskill_document', array("descriptionid" => $description->id, "id" => $documentid));
            }
            else{
                // $documents = $DB->get_records('checkskill_document', array("descriptionid" => $description->id));
                $params=array("descriptionid" => $descriptionid);
                $sql="SELECT * FROM {checkskill_document} WHERE descriptionid=:descriptionid ORDER BY id DESC ";
                $documents = $DB->get_records_sql($sql, $params);
                if ($documents){
                    foreach($documents as $document){
                        break; // renvoyer le plus récent
                    }
                }
            }

            $options = array('subdirs'=>0, 'maxbytes'=>get_max_upload_file_size($CFG->maxbytes, $this->course->maxbytes), 'maxfiles'=>1, 'accepted_types'=>'*', 'return_types'=>FILE_INTERNAL);

            $mform = new mod_checkskill_add_document_upload_form(null,
                array('checkskill'=>$this->checkskill->id,
                    'contextid'=>$this->context->id,
                    'itemid'=>$itemid,
                    'userid'=>$userid,
                    'descriptionid'=>$descriptionid,
                    'document'=>$document,
                    'filearea'=>'document',
                    'msg' => get_string('document_associe', 'checkskill'),
                    'options'=>$options));

            if ($mform->is_cancelled()) {
                redirect($returnurl);
            } else if ($mform->get_data()) {
                checkskill_add_upload_document($mform, $this->checkskill->id);
                die();
                //    redirect(new moodle_url('/mod/checkskill/view.php', array('id'=>$cm->id)));
            }

            $this->view_header();
            echo '<div align="center"><h3>'.get_string('edit_document', 'checkskill').'</h3></div>'."\n";
            echo $OUTPUT->box_start('generalbox boxwidthwide boxaligncenter');
            echo format_text($this->checkskill->intro, $this->checkskill->introformat);
            echo '<br/>';

            $mform->display();
            
            echo $OUTPUT->box_end();
            $this->view_footer();
        }
    }


    function add_document($itemid, $userid, $descriptionid) {
        global $DB;
        global $CFG;
        global $OUTPUT;
        global $PAGE;

        $document=NULL;

       if ($CFG->version < 2011120100) {
            $this->context = get_context_instance(CONTEXT_MODULE, $this->cm->id);
        } else {
            $this->context = context_module::instance($this->cm->id);
        }


        $thispage = new moodle_url('/mod/checkskill/add_document.php', array('id' => $this->cm->id) );
        $returl = new moodle_url('/mod/checkskill/view.php', array('id' => $this->cm->id));

        $currenttab = 'view';

		if ($CFG->version > 2014051200) { // Moodle 2.7+
            $params = array(
                'contextid' => $this->context->id,
                'objectid' => $this->checkskill->id,
            );
            $event = \mod_checkskill\event\edit_page_viewed::create($params);
            $event->trigger();
        } else { // Before Moodle 2.7
			add_to_log($this->course->id, 'checkskill', 'view', "add_document.php?id={$this->cm->id}", $this->checkskill->id, $this->cm->id);
		}
		
        if (empty($descriptionid) && $itemid && $userid) {
            $description = $DB->get_record('checkskill_description', array("itemid"=>$itemid, "userid"=>$userid));
            if ($description){
                $descriptionid=$description;
            }
        }

        if ($descriptionid){
            $options = array('subdirs'=>0, 'maxbytes'=>get_max_upload_file_size($CFG->maxbytes, $this->course->maxbytes), 'maxfiles'=>1, 'accepted_types'=>'*', 'return_types'=>FILE_INTERNAL);

            $mform = new mod_checkskill_add_document_upload_form(null,
                array('checkskill'=>$this->checkskill->id,
                    'contextid'=>$this->context->id,
                    'itemid'=>$itemid,
                    'userid'=>$userid,
                    'descriptionid'=>$descriptionid,
                    'document'=>NULL,
                    'filearea'=>'document',
                    'msg' => get_string('document_associe', 'checkskill'),
                    'options'=>$options));

            if ($mform->is_cancelled()) {
                redirect($returnurl);
            } else if ($mform->get_data()) {
                checkskill_add_upload_document($mform, $this->checkskill->id);
                die();
                //    redirect(new moodle_url('/mod/checkskill/view.php', array('id'=>$cm->id)));
            }

            $this->view_header();
            echo '<div align="center"><h3>'.get_string('edit_document', 'checkskill').'</h3></div>'."\n";
            echo $OUTPUT->box_start('generalbox boxwidthwide boxaligncenter');
            echo format_text($this->checkskill->intro, $this->checkskill->introformat);
            echo '<br/>';

            $mform->display();

            echo $OUTPUT->box_end();
            $this->view_footer();
        }
    }



    function edit_document($itemid, $userid, $document) {
        global $DB;
        global $CFG;
        global $OUTPUT;
        global $PAGE;

		if ($CFG->version < 2011120100) {
            $this->context = get_context_instance(CONTEXT_MODULE, $this->cm->id);
        } else {
            $this->context = context_module::instance($this->cm->id);
        }

        $thispage = new moodle_url('/mod/checkskill/edit_document.php', array('id' => $this->cm->id) );
        $returl = new moodle_url('/mod/checkskill/view.php', array('id' => $this->cm->id));

        $currenttab = 'view';

		if ($CFG->version > 2014051200) { // Moodle 2.7+
            $params = array(
                'contextid' => $this->context->id,
                'objectid' => $this->checkskill->id,
            );
            $event = \mod_checkskill\event\edit_page_viewed::create($params);
            $event->trigger();
        } else { // Before Moodle 2.7
			add_to_log($this->course->id, 'checkskill', 'view', "add_document.php?id={$this->cm->id}", $this->checkskill->name, $this->cm->id);
		}
		
        if ($document){
            $options = array('subdirs'=>0, 'maxbytes'=>get_max_upload_file_size($CFG->maxbytes, $this->course->maxbytes), 'maxfiles'=>1, 'accepted_types'=>'*', 'return_types'=>FILE_INTERNAL);

            $mform = new mod_checkskill_update_document_upload_form(null,
                array('checkskill'=>$this->checkskill->id,
                    'contextid'=>$this->context->id,
                    'itemid'=>$itemid,
                    'userid'=>$userid,
                    'descriptionid'=>$document->descriptionid,
                    'document'=>$document,
                    'filearea'=>'document',
                    'msg' => get_string('document_associe', 'checkskill'),
                    'options'=>$options));



            if ($mform->is_cancelled()) {
                redirect($returnurl);
            } else if ($mform->get_data()) {
                checkskill_update_upload_document($mform, $this->checkskill->id);
                die();
                //    redirect(new moodle_url('/mod/checkskill/view.php', array('id'=>$cm->id)));
            }

            $this->view_header();
            echo '<div align="center"><h3>'.get_string('edit_document', 'checkskill').'</h3></div>'."\n";
            echo $OUTPUT->box_start('generalbox boxwidthwide boxaligncenter');
            echo format_text($this->checkskill->intro, $this->checkskill->introformat);
            echo '<br/>';
            $mform->display();
            echo $OUTPUT->box_end();
            $this->view_footer();
        }
    }



    function delete_document($documentid) {
		global $CFG;
		if ($CFG->version < 2011120100) {
            $this->context = get_context_instance(CONTEXT_MODULE, $this->cm->id);
        } else {
            $this->context = context_module::instance($this->cm->id);
        }

        // $thispage = new moodle_url('/mod/checkskill/delete_document.php', array('id' => $this->cm->id) );
        $returnurl = new moodle_url('/mod/checkskill/view.php', array('id' => $this->cm->id));
		if ($CFG->version > 2014051200) { // Moodle 2.7+
            $params = array(
                'contextid' => $this->context->id,
                'objectid' => $this->checkskill->id,
            );
            $event = \mod_checkskill\event\edit_page_viewed::create($params);
            $event->trigger();
        } else { // Before Moodle 2.7
			add_to_log($this->course->id, 'checkskill', 'view', "delete_document.php?id={$this->cm->id}&amp;documentid=$documentid", $this->checkskill->id, $this->cm->id);
		}

        if ($documentid){
            $this->delete_document_record($documentid);
        }
        redirect($returnurl);
    }
    
    
    function delete_document_record($documentid) {
        global $DB;
        if ($documentid){
            // get from table
            if ($document=$DB->get_record('checkskill_document', array("id" => $documentid))){
                // delete file
                // print_r($document);
                if ($document->url_document){
                    checkskill_delete_a_file($document->url_document);
                }
                // delete row entry
                $DB->delete_records('checkskill_document', array("id" => $documentid));
            }
        }
    }

    function delete_description($descriptionid) {
        global $CFG;
		
		$returnurl = new moodle_url('/mod/checkskill/view.php', array('id' => $this->cm->id));
		if ($CFG->version < 2011120100) {
            $this->context = get_context_instance(CONTEXT_MODULE, $this->cm->id);
        } else {
            $this->context = context_module::instance($this->cm->id);
        }

		if ($CFG->version > 2014051200) { // Moodle 2.7+
            $params = array(
                'contextid' => $this->context->id,
                'objectid' => $this->checkskill->id,
            );
            $event = \mod_checkskill\event\edit_page_viewed::create($params);
            $event->trigger();
        } else { // Before Moodle 2.7
			add_to_log($this->course->id, 'checkskill', 'view', "delete_description.php?id={$this->cm->id}&amp;descriptionid=$descriptionid", $this->checkskill->id, $this->cm->id);
		}
		
        if ($descriptionid){
            $this->delete_description_record($descriptionid);
        }
        redirect($returnurl);
    }

    function delete_description_record($descriptionid) {
        global $DB;
        if ($descriptionid){
            // get from table
            if ($description=$DB->get_record('checkskill_description', array("id" => $descriptionid))){
                // get all documents
                if ($documents = $DB->get_records('checkskill_document', array("descriptionid" => $description->id))){
                    foreach($documents as $document){
                        $this->delete_document_record($document->id);
                    }
                }
                // delete table checkskill_description record
                $DB->delete_records('checkskill_description', array("id" => $description->id));
            }
        }
    }

    function display_description_documents($itemid, $userid, $edition=false){
        // Display a description record and any document linked with it
        global $DB;
        global $OUTPUT;
        global $CFG;

        if ($itemid && $userid) {
            $description = $DB->get_record('checkskill_description', array("itemid"=>$itemid, "userid"=>$userid));
            if (!empty($description)){
                echo '<br /><span class="usercomment"> &nbsp; ';
                $this->display_description($description);
                echo ' &nbsp; </span>';
                if ($edition){
                    // icon edition
                    $editurl = new moodle_url('/mod/checkskill/edit_description.php', array('id' => $this->cm->id) );
                    $baseurl = $editurl.'&amp;itemid='.$itemid.'&amp;userid='.$userid.'&amp;sesskey='.sesskey();
                    echo '&nbsp;<a href="'.$baseurl.'">';
                    $title = '"'.get_string('edit_description','checkskill').'"';
                    echo '<img src="'.$OUTPUT->pix_url('/i/edit').'" alt='.$title.' title='.$title.' /></a>';
                    // icon delete
                    $editurl = new moodle_url('/mod/checkskill/delete_description.php', array('id' => $this->cm->id) );
                    $baseurl = $editurl.'&amp;descriptionid='.$description->id.'&amp;sesskey='.sesskey();
                    echo '&nbsp;<a href="'.$baseurl.'">';
                    $title = '"'.get_string('delete_description','checkskill').'"';
                    echo '<img src="'.$OUTPUT->pix_url('/t/delete').'" alt='.$title.' title='.$title.' /></a>';
                    // icon edition link
                    $editurl = new moodle_url('/mod/checkskill/add_document.php', array('id' => $this->cm->id) );
                    $baseurl = $editurl.'&amp;itemid='.$itemid.'&amp;userid='.$userid.'&amp;descriptionid='.$description->id.'&amp;sesskey='.sesskey();
                    echo '&nbsp;<a href="'.$baseurl.'">';
                    $title = '"'.get_string('add_link','checkskill').'"';
                    echo '<img src="'.$OUTPUT->pix_url('link','checkskill').'" alt='.$title.' title='.$title.' /></a>';

                    if (!empty($CFG->enableportfolios)){
                        // portfolio upload link
                        $editurl = new moodle_url('/mod/checkskill/mahara/upload_mahara.php', array('id' => $this->cm->id) );
                        $baseurl = $editurl.'&amp;itemid='.$itemid.'&amp;userid='.$userid.'&amp;descriptionid='.$description->id.'&amp;sesskey='.sesskey();
                        echo '&nbsp;<a href="'.$baseurl.'">';
                        $title = '"'.get_string('upload_portfolio','checkskill').'"';
                        echo '<img src="'.$OUTPUT->pix_url('upload_portfolio','checkskill').'" alt='.$title.' title='.$title.' /></a>';
                    }

                }
                $documents = $DB->get_records('checkskill_document', array("descriptionid" => $description->id));
                if ($documents){
                    echo '<ol class="checkskill">'."\n";
                    foreach($documents as $document){
                        if ($document){
                            // Display link
                            $this->display_document($itemid, $userid, $document, $edition);
                        }
                    }
                    echo '</ol>'."\n";
                }

            }
            else{
                if ($edition){
                    // icon edition
                    $editurl = new moodle_url('/mod/checkskill/edit_description.php', array('id' => $this->cm->id) );
                    $baseurl = $editurl.'&amp;itemid='.$itemid.'&amp;userid='.$userid.'&amp;sesskey='.sesskey();
                    echo '&nbsp;<a href="'.$baseurl.'">';
                    $title = '"'.get_string('edit_description','checkskill').'"';
                    echo '<img src="'.$OUTPUT->pix_url('edit','checkskill').'" alt='.$title.' title='.$title.' /></a>';
                }
            }
        }
    }

    function display_description($description){
        // Display a description record
        if (!empty($description)){
            echo stripslashes($description->description);
            echo ' [<i><span class="small">'.userdate($description->timestamp).'</span></i>] '."\n";
        }
    }

    function display_document($itemid, $userid, $document, $edition=false){
        // Display a document record
        global $CFG;
        global $OUTPUT;
        if (!empty($document)){
			if ($document->target==1){
				$cible_document='_blank'; // fenêtre cible
			}
			else{
                $cible_document='';
			}
			if ($document->title){
                $etiquette_document=$document->title; // fenêtre cible
            }
			else{
                $etiquette_document='';
			}
            echo '<li>'.get_string('doc_num','checkskill',$document->id).' &nbsp; '.$document->description_document."\n";
            echo checkskill_affiche_url($document->url_document, $etiquette_document, $cible_document);
            echo ' [<i><span class="small">'.userdate($document->timestamp).'</span></i>] '."\n";
            if ($edition){
                // icon edition
                $editurl = new moodle_url('/mod/checkskill/edit_document.php', array('id' => $this->cm->id) );
                $baseurl = $editurl.'&amp;itemid='.$itemid.'&amp;userid='.$userid.'&amp;documentid='.$document->id.'&amp;sesskey='.sesskey();
                echo '&nbsp;<a href="'.$baseurl.'">';
                $title = '"'.get_string('edit_link','checkskill').'"';
                echo '<img src="'.$OUTPUT->pix_url('/i/edit').'" alt='.$title.' title='.$title.' /></a>';
                // icon delete
                $editurl = new moodle_url('/mod/checkskill/delete_document.php', array('id' => $this->cm->id) );
                $baseurl = $editurl.'&amp;documentid='.$document->id.'&amp;sesskey='.sesskey();
                echo '&nbsp;<a href="'.$baseurl.'">';
                $title = '"'.get_string('delete_link','checkskill').'"';
                echo '<img src="'.$OUTPUT->pix_url('/t/delete').'" alt='.$title.' title='.$title.' /></a>';
            }
            echo '</li>'."\n";
        }
    }


} // End of class

// ################################ URL  ###############################

/**
     * display an url according to moodle file management API
     * @return string active link
	 * @ input $url : an uri
	 * @ input $etiquette : a label
	 * @ input $cible : a targeted frame
*/
function checkskill_affiche_url($url, $etiquette="", $cible="") {
    // MOODLE2 API
	global $CFG;
	   // Moodle 1.9
		/*
		$importfile = "{$CFG->dataroot}/{$url}";
		if (file_exists($importfile)) {
	        if ($CFG->slasharguments) {
    	    	$efile = "{$CFG->wwwroot}/file.php/$url";
        	}
		    else {
				$efile = "{$CFG->wwwroot}/file.php?file=/$url";
        	}
		}
		else{
			$efile = "$url";
		}
		*/
		// Moodle 2.0
		if (!preg_match("/http/i",$url)){ // load file - fichier telecharge
            // a correct Url - l'URL a été correctement formée lors de la création du fichier
            $efile =  $CFG->wwwroot.'/pluginfile.php'.$url;
        }
        else{
            $efile = $url;
        }

		if ($etiquette==""){
			$l=strlen($url);
			$posr=strrpos($url,'/');
			if ($posr===false){ // no separator
				$etiquette=$url;
			}
			else if ($posr==$l-1){ // ending separator
				$etiquette=get_string("etiquette_inconnue", "checkskill");
			}
			else if ($posr==0){ // heading and ending separator !
				$etiquette=get_string("etiquette_inconnue", "checkskill");
			}
			else {
				$etiquette=substr($url,$posr+1);
			}
		}

        if ($cible){
            return "<a href=\"$efile\" target=\"".$cible."\">$etiquette</a>";
        }
        else{
            return "<a href=\"$efile\">$etiquette</a>";
        }

}


function checkskill_itemcompare($item1, $item2) {
    if ($item1->position < $item2->position) {
        return -1;
    } else if ($item1->position > $item2->position) {
        return 1;
    }
    if ($item1->id < $item2->id) {
        return -1;
    } else if ($item1->id > $item2->id) {
        return 1;
    }
    return 0;
}

// ################################## MAHARA PACKAGE ####################
/** @author  Jean Fruitet <jean.fruitet@univ-nantes.fr>
 * @package mod/checkskill
 **/
 
require_once($CFG->libdir . '/portfolio/caller.php');
require_once($CFG->libdir . '/filelib.php');


/**
 * @package   mod-checkskill+
 * @copyright 1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @copyright 2011 Jean Fruitet  {@link http://univ-nantes.fr}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class checkskill_portfolio_caller extends portfolio_module_caller_base {

    protected $instanceid;
    protected $export_format;

    protected $cm;
    protected $course;
    protected $checkskill;
    protected $userid;

    private $adresse_retour;

    /**
     * @return array
     */
    public static function expected_callbackargs() {
        return array(
            'instanceid' => false,
            'userid'   => false,
            'export_format'  => false,
        );
    }
    /**
     * @param array $callbackargs
     */
    function __construct($callbackargs) {
        parent::__construct($callbackargs);
        if (!$this->instanceid) {
            throw new portfolio_caller_exception('mustprovideinstanceid', 'checkskill');
        }
        if (!$this->userid) {
            throw new portfolio_caller_exception('mustprovideuser', 'checkskill');
        }
        if (!isset($this->export_format)) {
            throw new portfolio_caller_exception('mustprovideexportformat', 'checkskill');
        }
        else{
            // echo "<br />:: 86 ::$this->export_format\n";
            if ($this->export_format!=PORTFOLIO_FORMAT_FILE) {
                // depending on whether there are files or not, we might have to change richhtml/plainhtml
                $this->supportedformats = array_merge(array($this->supportedformats), array(PORTFOLIO_FORMAT_RICH, PORTFOLIO_FORMAT_LEAP2A));
            }
        }
    }
    /**
     * @global object
     */
    public function load_data() {
        global $DB;
        global $CFG;
        if ($this->instanceid) {
            if (!$this->checkskill = $DB->get_record('checkskill', array('id' => $this->instanceid))) {
                throw new portfolio_caller_exception('invalidinstanceid', 'checkskill');
            }
        }

        if (!$this->cm = get_coursemodule_from_instance('checkskill', $this->checkskill->id)) {
            throw new portfolio_caller_exception('invalidcoursemodule');
        }
        $this->adresse_retour= '/mod/checkskill/view.php?id='.$this->cm->id;
    }

    /**
     * @global object
     * @return string
     */
    function get_return_url() {
        global $CFG;
        return $CFG->wwwroot . $this->adresse_retour;
    }

    /**
     * @global object
     * @return array
     */
    function get_navigation() {
        global $CFG;

        $navlinks = array();
        $navlinks[] = array(
            'name' => format_string($this->checkskill->name),
            'link' => $CFG->wwwroot . $this->adresse_retour,
            'type' => 'title'
        );
        return array($navlinks, $this->cm);
    }

    /**
     * 
     * @global object
     * @global object
     * @uses PORTFOLIO_FORMAT_HTML
     * @return mixed
     */
    function prepare_package() {
        global $CFG;
        global $OUTPUT;
        global $USER;
                // exporting a single HTML certificat
                $content_to_export = $this->prepare_checkskill();
                $name = 'checkskill'.'_'.$this->checkskill->name.'_'.$this->checkskill->id.'_'.$this->userid.'.html';
                // $manifest = ($this->exporter->get('format') instanceof PORTFOLIO_FORMAT_PLAINHTML);
                $manifest = ($this->exporter->get('format') instanceof PORTFOLIO_FORMAT_RICH);

                // DEBUG
                /*
                echo "<br />DEBUG :: 179 :: CONTENT<br />\n";
                echo($content_to_export);
                echo "<br />MANIFEST : $manifest<br />\n";
                echo "<br />FORMAT ".$this->exporter->get('formatclass')."\n";
                */

                $content=$content_to_export;

                if ($this->exporter->get('formatclass') == PORTFOLIO_FORMAT_LEAP2A) {
                    $leapwriter = $this->exporter->get('format')->leap2a_writer($USER);
                    // DEBUG
                    //echo "<br />DEBUG :: 169 :: LEAPWRITER<br />\n";
                    //print_object($leapwriter);
                    // exit;
                    if ($leapwriter){
                        if ($this->prepare_certificat_leap2a($leapwriter, $content_to_export)){
                            // echo "<br />DEBUG :: 175\n";
                            $content = $leapwriter->to_xml();
                            // DEBUG
                            // echo "<br /><br />DEBUG :: mod/checkskill/mahara/locallib_portfolio.php :: 167<br />\n";
                            // echo htmlspecialchars($content);
                            $name = $this->exporter->get('format')->manifest_name();
                        }
                    }
                }
                /*
                // DEBUG
                echo "<br />DEBUG :: 176<br />\n";
                print_object($content);
                */
                $this->get('exporter')->write_new_file($content, $name, $manifest);

    }



    /**
     * @return string
     */
    function get_sha1() {
        $filesha = '';
        try {
            $filesha = $this->get_sha1_file();
        } catch (portfolio_caller_exception $e) { } // no files

        if ($this->checkskill && $this->userid){
            return sha1($filesha . ',' . $this->checkskill->id. ',' . $this->checkskill->name. ',' . $this->userid);
        }
        return 0;
    }

    function expected_time() {
        // a file based export
        if ($this->singlefile) {
            return portfolio_expected_time_file($this->singlefile);
        }
        else{
            return PORTFOLIO_TIME_LOW;
        }
    }

    /**
     * @uses CONTEXT_MODULE
     * @return bool
     */
    function check_permissions() {
        if ($CFG->version < 2011120100) {
            $context = get_context_instance(CONTEXT_MODULE, $this->cm->id);
        } else {
            $context = context_module::instance($this->cm->id);
        }

        return true;
    }

    /**
     * @return string
     */
    public static function display_name() {
        return get_string('modulename', 'checkskill');
    }

    public static function base_supported_formats() {
        //return array(PORTFOLIO_FORMAT_FILE, PORTFOLIO_FORMAT_PLAINHTML, PORTFOLIO_FORMAT_LEAP2A);
        return array(PORTFOLIO_FORMAT_FILE);
    }

    /**
     * helper function to add a leap2a entry element
     * that corresponds to a single certificate,
     *
     * the entry/ies are added directly to the leapwriter, which is passed by ref
     *
     * @global object $checkskill $userid the stdclass object representing the database record
     * @param portfolio_format_leap2a_writer $leapwriter writer object to add entries to
     * @param string $content  the content of the certificate (prepared by {@link prepare_checkskill}
     *
     * @return int id of new entry
     */
    private function prepare_certificat_leap2a(portfolio_format_leap2a_writer $leapwriter, $content) {
    global $USER;
        $order   = array( "&nbsp;",  "\r\n", "\n", "\r");
        $replace = ' ';
        $content=str_replace($order, $replace, $content);

        $title=get_string('modulename', 'checkskill').' '.$this->checkskill->name. ' '. $this->userid;
        $entry = new portfolio_format_leap2a_entry('checkskill_id' . $this->checkskill->id .'_user'. $this->userid, $title, 'leap2', $content); // proposer ability ?
        $entry->published = time();
        $entry->updated = time();
        $entry->author->id = $this->userid;
        $entry->summary = $this->checkskill->name.' '.strip_tags($this->checkskill->intro);
        $entry->add_category('web', 'any_type', 'Checkskill');
        // DEBUG
        /*
        echo "<br />246 :: ENTRY<br />\n";
        print_object($entry);
        */
        $leapwriter->add_entry($entry);
        /*
        echo "<br />286 :: LEAPWRITER<br />\n";
        print_object($leapwriter);
        */
        return $entry->id;
    }

    /**
     * this is a very cut down version of what is in print_lib
     *
     * @global object
     * @return string
     */
    private function prepare_checkskill() {
        global $DB;
        $output='';
        $info_checkskill='';
        $info_items='';
        $fullname ='';
        $login='';

        if(!empty($this->userid)){
            $user= $DB->get_record('user', array('id' => $this->userid));
            if ($user){
                $fullname = fullname($user, true);
                $login=$user->username;
            }
        }

        if (!empty($this->checkskill) && !empty($this->userid) ) {
            $info_checkskill = "<h2>".$this->checkskill->name."</h2>\n<p>".strip_tags($this->checkskill->intro)."\n";
            if (!empty($this->checkskill->timecreated)){
                $info_checkskill .= "<br />".get_string('timecreated', 'checkskill')." ".userdate($this->checkskill->timecreated)." ";
            }
            if (!empty($this->checkskill->timemodified)){
                $info_checkskill .= "<br />".get_string('timemodified', 'checkskill')." ".userdate($this->checkskill->timemodified)." ";
            }
            $info_checkskill .= "</p>\n";

            $info_items='';
            $items=$DB->get_records('checkskill_item', array('checkskill' => $this->checkskill->id), 'position ASC', '*');
            if ($items){
                $info_items.= "<h3>".get_string('items', 'checkskill')."</h3>\n";
                $info_items.= "<ul>\n";
                foreach ($items as $item){
                    if ($item){
                        $info_items.="<li><i>".get_string('id', 'checkskill').$item->id."</i> <b>".stripslashes($item->displaytext)."</b>\n";

                        // checks
                        $checks=$DB->get_records('checkskill_check', array('item' => $item->id, 'userid' => $this->userid));
                        if ($checks){
                            foreach ($checks as $check){
                                switch ($check->teachermark) {
                                    case CHECKSKILL_TEACHERMARK_YES:
                                        $info_items.="<br /> ".get_string('teachermarkyes','checkskill');
                                        break;
                                    case CHECKSKILL_TEACHERMARK_NO:
                                        $info_items.="<br />  ".get_string('teachermarkno','checkskill');
                                        break;
                                    default:
                                        $info_items.="<br />  ".get_string('teachermarkundecided','checkskill');
                                        break;
                                }

                                if (!empty($check->usertimestamp)){
                                    $info_items.= " (".get_string('usertimestamp', 'checkskill')." ".userdate($check->usertimestamp).") ";
                                }
                                if (!empty($check->teachertimestamp)){
                                    $info_items.= " (".get_string('teachertimestamp', 'checkskill')." ".userdate($check->teachertimestamp).") ";
                                }
                                $info_items.= "<br />\n";
                            }
                        }

                        // comments
                        $comments=$DB->get_records('checkskill_comment', array('itemid' => $item->id, 'userid' => $this->userid));
                        if ($comments){
                            $info_items.= "<h4>".get_string('comments', 'checkskill')."</h4>\n";
                            foreach ($comments as $comment){
                                if (!empty($comment->text)){
                                    $info_items.= "<br />&nbsp;  &nbsp;  &nbsp; ". stripslashes($comment->text)." ";
                                }
                                if ($comment->commentby){
                                    $teacher= $DB->get_record('user', array('id' => $comment->commentby));
                                    if ($teacher){
                                        $fullnameteacher =fullname($teacher, true);
                                    }
                                }
                                if (!empty($fullnameteacher)){
                                    $info_items.="<br />(".get_string('commentby', 'checkskill')." ".$fullnameteacher.") ";
                                }
                                $info_items.= "\n";
                            }
                        }
                        // description
                        $descriptions=$DB->get_records('checkskill_description', array('itemid' => $item->id, 'userid' => $this->userid));
                        if ($descriptions){
                            $info_items.= "<h4>".get_string('argumentation', 'checkskill')."</h4>\n";

                            foreach ($descriptions as $description){
                                if (!empty($description->description)){
                                    $info_items.= "<p>".stripslashes($description->description)." ";
                                }
                                if (!empty($description->timestamp)){
                                    $info_items.= " (".userdate($description->timestamp).") \n";
                                }
                                // documents
                                $documents=$DB->get_records('checkskill_document', array('descriptionid' => $description->id));
                                if ($documents){
                                    $info_items.= "<ol>\n";
                                    foreach ($documents as $document){
                                        if (!empty($document)){
			                                if ($document->target==1){
                                                $cible_document='_blank'; // fenêtre cible
			                                }
                                			else{
                                                $cible_document='';
                                			}
                                  			if ($document->title){
                                                $etiquette_document=$document->title; // fenêtre cible
                                            }
                                			else{
                                                $etiquette_document='';
			                                }
			                                $info_items.="<li>&nbsp;  &nbsp;  &nbsp;  &nbsp; ".get_string('doc_num','checkskill',$document->id).' &nbsp; '.stripslashes($document->description_document)." ";
                                            $info_items.=checkskill_affiche_url($document->url_document, $etiquette_document, $cible_document);
                                            $info_items.=' [<i><span class="small">'.userdate($document->timestamp).'</span></i>] '."</li>\n";
                                        }
                                    }
                                    $info_items.= "</ol>\n";
                                }
                                $info_items.= "</p>\n";
                            }

                        }
                        $info_items.= "</li>\n";
                    }
                }
                $info_items.= "</ul>\n";
            }
            // format the body
            $s='<h3>'.get_string('modulename','checkskill').'</h3>'."\n";
            $s.='<p><b>'.$fullname.'</b> (<i>'.$login.'</i>)</p>';
            $s.=$info_checkskill;
            $s.=$info_items;

            // DEBUG
            // echo $s;
            // exit;
            $options = portfolio_format_text_options();
            $format = $this->get('exporter')->get('format');
            $formattedtext = format_text($s, FORMAT_HTML, $options);

            // $formattedtext = portfolio_rewrite_pluginfile_urls($formattedtext, $this->context->id, 'mod_checkskill', 'document', $item->id, $format);

            $output = '<table border="0" cellpadding="3" cellspacing="1" bgcolor="#333300">';
            $output .= '<tr valign="top" bgcolor="#ffffff"><td>';
            $output .= '<div><b>'.get_string('modulename', 'checkskill').' '. format_string($this->checkskill->name).'</b></div>';
            $output .= '</td></tr>';
            $output .= '<tr valign="top" bgcolor="#ffffff"><td align="left">';
            $output .= $formattedtext;
            $output .= '</td></tr></table>'."\n\n";

        }
        return $output;
    }



}

