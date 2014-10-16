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
 * The mod_checkskill course module viewed event.
 * @package    mod_checkskill
 * @author  2014 Jean FRUITET <jean.fruitet@univ-nantes.fr>
 * borrowed from package checklist 
 * @copyright  2014 Davo Smith <moodle@davosmith.co.uk>
 * @copyright  2014 Davo Smith <moodle@davosmith.co.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_checkskill\event;

defined('MOODLE_INTERNAL') || die();

/**
 * The mod_checkskill student checks updated class.
 *
 * @package    mod_checkskill
 * @since      Moodle 2.7
 * borrowed from package checklist 
 * @copyright  2014 Davo Smith <moodle@davosmith.co.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class student_edit_description extends \core\event\base {

    /**
     * Init method.
     *
     * @return void
     */
    protected function init() {
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
        $this->data['objecttable'] = 'checkskill';
    }

    /**
     * Returns localised general event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('eventstudentdescriptionupdated', 'mod_checkskill');
    }

    /**
     * Returns description of what happened.
     *
     * @return string
     */
    public function get_description() {
        return "The user with id '$this->userid' has updated his description for the item '{$this->other['itemid']}' ".
        "of checkskill with the course module id '$this->contextinstanceid'";
    }

    /**
     * Get URL related to the action
     *
     * @return \moodle_url
     */
    public function get_url() {
        return new \moodle_url('/mod/checkskill/report.php', array('id' => $this->contextinstanceid,
                                                                  'studentid' => $this->userid));
    }

    /**
     * Return the legacy event log data.
     *
     * @return array|null
     */
    protected function get_legacy_logdata() {
        return array($this->courseid, 'checkskill', 'update description', 'edit_description.php?id='.$this->contextinstanceid, $this->objectid, $this->contextinstanceid);
    }

}

