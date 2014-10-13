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

require_once($CFG->dirroot . '/mod/checkskill/backup/moodle2/backup_checkskill_stepslib.php'); // Because it exists (must)
require_once($CFG->dirroot . '/mod/checkskill/backup/moodle2/backup_checkskill_settingslib.php'); // Because it exists (optional)

/**
 * forum backup task that provides all the settings and steps to perform one
 * complete backup of the activity
 */
class backup_checkskill_activity_task extends backup_activity_task {

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
        // Forum only has one structure step
        $this->add_step(new backup_checkskill_activity_structure_step('checkskill structure', 'checkskill.xml'));
    }

    /**
     * Code the transformations to perform in the activity in
     * order to get transportable (encoded) links
     */
    static public function encode_content_links($content) {
        // I don't think there is anything needed here (but I could be wrong)
        global $CFG;

        $base = preg_quote($CFG->wwwroot,"/");

        // Link to the skill of checkskills
        $search="/(".$base."\/mod\/checkskill\/index.php\?id\=)([0-9]+)/";
        $content= preg_replace($search, '$@CHECKSKILLINDEX*$2@$', $content);

        // Link to checkskill view by moduleid
        $search="/(".$base."\/mod\/checkskill\/view.php\?id\=)([0-9]+)/";
        $content= preg_replace($search, '$@CHECKSKILLVIEWBYID*$2@$', $content);

        // Link to checkskill view by id
        $search="/(".$base."\/mod\/checkskill\/view.php\?checkskill\=)([0-9]+)/";
        $content= preg_replace($search, '$@CHECKSKILLVIEWBYCHECKSKILL*$2@$', $content);

        // Link to checkskill report by moduleid
        $search="/(".$base."\/mod\/checkskill\/report.php\?id\=)([0-9]+)/";
        $content= preg_replace($search, '$@CHECKSKILLREPORTBYID*$2@$', $content);

        // Link to checkskill report by id
        $search="/(".$base."\/mod\/checkskill\/report.php\?checkskill\=)([0-9]+)/";
        $content= preg_replace($search, '$@CHECKSKILLREPORTBYCHECKSKILL*$2@$', $content);

        // Link to checkskill edit by moduleid
        $search="/(".$base."\/mod\/checkskill\/edit.php\?id\=)([0-9]+)/";
        $content= preg_replace($search, '$@CHECKSKILLEDITBYID*$2@$', $content);

        // Link to checkskill edit by id
        $search="/(".$base."\/mod\/checkskill\/edit.php\?checkskill\=)([0-9]+)/";
        $content= preg_replace($search, '$@CHECKSKILLEDITBYCHECKSKILL*$2@$', $content);

        return $content;
    }
}
