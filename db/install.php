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

// This file replaces:
//   * STATEMENTS section in db/install.xml
//   * lib.php/modulename_install() post installation hook
//   * partially defaults.php

function xmldb_checkskill_install() {
    global $DB;

/// Install logging support
/*    update_log_display_entry('checkskill', 'view', 'checkskill', 'name');
    update_log_display_entry('checkskill', 'edit', 'checkskill', 'name');
    update_log_display_entry('checkskill', 'update checks', 'checkskill', 'name');
    update_log_display_entry('checkskill', 'complete', 'checkskill', 'name');
    update_log_display_entry('checkskill', 'report', 'checkskill', 'name');*/

}
