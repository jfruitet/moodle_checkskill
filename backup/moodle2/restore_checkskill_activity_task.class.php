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

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/checkskill/backup/moodle2/restore_checkskill_stepslib.php'); // Because it exists (must)

/**
 * checkskill restore task that provides all the settings and steps to perform one
 * complete restore of the activity
 */
class restore_checkskill_activity_task extends restore_activity_task {

    /**
     * Define (add) particular settings this activity can have
     */
    protected function define_my_settings() {
        // No particular settings for this activity
    }

    /**
     * Define (add) particular steps this activity can have
     */
    protected function define_my_steps() {
        // Choice only has one structure step
        $this->add_step(new restore_checkskill_activity_structure_step('checkskill_structure', 'checkskill.xml'));
    }

    /**
     * Define the contents in the activity that must be
     * processed by the link decoder
     */
    static public function define_decode_contents() {
        $contents = array();

        $contents[] = new restore_decode_content('checkskill', array('intro'), 'checkskill');

        return $contents;
    }

    /**
     * Define the decoding rules for links belonging
     * to the activity to be executed by the link decoder
     */
    static public function define_decode_rules() {
        $rules = array();

        // skill of checkskills in course
        $rules[] = new restore_decode_rule('CHECKSKILLINDEX', '/mod/checkskill/index.php?id=$1', 'course');
        // Checkskill by cm->id and checkskill->id
        $rules[] = new restore_decode_rule('CHECKSKILLVIEWBYID', '/mod/checkskill/view.php?id=$1', 'course_module');
        $rules[] = new restore_decode_rule('CHECKSKILLVIEWBYCHECKSKILL', '/mod/checkskill/view.php?checkskill=$1', 'checkskill');
        // Checkskill report by cm->id and checkskill->id
        $rules[] = new restore_decode_rule('CHECKSKILLREPORTBYID', '/mod/checkskill/report.php?id=$1', 'course_module');
        $rules[] = new restore_decode_rule('CHECKSKILLREPORTBYCHECKSKILL', '/mod/checkskill/report.php?checkskill=$1', 'checkskill');
        // Checkskill edit by cm->id and checkskill->id
        $rules[] = new restore_decode_rule('CHECKSKILLEDITBYID', '/mod/checkskill/edit.php?id=$1', 'course_module');
        $rules[] = new restore_decode_rule('CHECKSKILLEDITBYCHECKSKILL', '/mod/checkskill/edit.php?checkskill=$1', 'checkskill');
        $rules[] = new restore_decode_rule('CHECKSKILLEDITBYID', '/mod/checkskill/edit_description.php?id=$1', 'course_module');
        $rules[] = new restore_decode_rule('CHECKSKILLEDITBYCHECKSKILL', '/mod/checkskill/edit_description.php?checkskill=$1', 'checkskill');
        $rules[] = new restore_decode_rule('CHECKSKILLEDITBYID', '/mod/checkskill/edit_document.php?id=$1', 'course_module');
        $rules[] = new restore_decode_rule('CHECKSKILLEDITBYCHECKSKILL', '/mod/checkskill/edit_document.php?checkskill=$1', 'checkskill');
        $rules[] = new restore_decode_rule('CHECKSKILLEDITBYID', '/mod/checkskill/delete_description.php?id=$1', 'course_module');
        $rules[] = new restore_decode_rule('CHECKSKILLEDITBYCHECKSKILL', '/mod/checkskill/delete_description.php?checkskill=$1', 'checkskill');
        $rules[] = new restore_decode_rule('CHECKSKILLEDITBYID', '/mod/checkskill/delete_document.php?id=$1', 'course_module');
        $rules[] = new restore_decode_rule('CHECKSKILLEDITBYCHECKSKILL', '/mod/checkskill/delete_document.php?checkskill=$1', 'checkskill');

        return $rules;
    }
	
    public function after_restore() {
        global $DB;

        // Find all the items that have a 'moduleid' but are not headings and match them up to the newly-restored activities.
        $items = $DB->get_records_select('checkskill_item', 'checkskill = ? AND moduleid > 0 AND itemoptional <> 2', array($this->get_activityid()));

        foreach ($items as $item) {
            $moduleid = restore_dbops::get_backup_ids_record($this->get_restoreid(), 'course_module', $item->moduleid);
            if ($moduleid) {
                // Match up the moduleid to the restored activity module.
                $DB->set_field('checkskill_item', 'moduleid', $moduleid->newitemid, array('id' => $item->id));
            } else {
                // Does not match up to a restored activity module => delete the item + associated user data.
                $DB->delete_records('checkskill_check', array('item' => $item->id));
                $DB->delete_records('checkskill_comment', array('itemid' => $item->id));
				$DB->delete_records('checkskill_description', array('itemid' => $item->id));
				$DB->delete_records('checkskill_document', array('itemid' => $item->id));				
                $DB->delete_records('checkskill_item', array('id' => $item->id));
            }
        }
    }

    /**
     * Added fix from https://tracker.moodle.org/browse/MDL-34172
     */

    /**
     * Define the restore log rules that will be applied by the
     * {@link restore_logs_processor} when restoring
     * folder logs. It must return one array
     * of {@link restore_log_rule} objects
     */
    static public function define_restore_log_rules() {
        $rules = array();

        $rules[] = new restore_log_rule('checkskill', 'add', 'view.php?id={course_module}', '{folder}');
        $rules[] = new restore_log_rule('checkskill', 'edit', 'edit.php?id={course_module}', '{folder}');
        $rules[] = new restore_log_rule('checkskill', 'view', 'view.php?id={course_module}', '{folder}');

        return $rules;
    }

    /**
     * Define the restore log rules that will be applied
     * by the {@link restore_logs_processor} when restoring
     * course logs. It must return one array of
     * {@link restore_log_rule} objects
     *
     * Note this rules are applied when restoring course logs
     * by the restore final task, but are defined here at
     * activity level. All them are rules not linked to any module instance (cmid = 0)
     */
    static public function define_restore_log_rules_for_course() {
        $rules = array();

        $rules[] = new restore_log_rule('checkskill', 'view all', 'index.php?id={course}', null);

        return $rules;
    }
	
}
