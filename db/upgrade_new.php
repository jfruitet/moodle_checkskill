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
 * Update checkskill tables
 *
 * @author  Jean FRUITET <jean.fruitet@univ-nantes.fr>
 * @package mod/checkskill
 */

function xmldb_checkskill_upgrade($oldversion=0) {

    global $CFG, $THEME, $DB;

    $dbman = $DB->get_manager();
    $result = true;

  	if ($oldversion < 2014101900) {
    /// Define table checkskill_item_modules to be created
        $table = new xmldb_table('checkskill_item_modules');

    /// Adding fields to table checkskill_item_modules
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null);
        $table->add_field('type', XMLDB_TYPE_CHAR, '80', null, XMLDB_NOTNULL, null, null, 'id');
        $table->add_field('moduleid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', 'type');
        $table->add_field('ref_item', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', 'moduleid');
        $table->add_field('ref_checkskill', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', 'ref_item');
        $table->add_field('ref_course', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', 'ref_checkskill');
    	/// Launch add field checkskill
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', 'ref_course');
        $table->add_field('ref_activity', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', 'userid');
        $table->add_field('ref_comment', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', 'ref_activity');
    /// Adding keys to table checkskill_item_modules
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
    /// Launch create table for checkskill_item_modules
        if (!$dbman->table_exists($table)){
            $dbman->create_table($table, true, true);
        }

        upgrade_mod_savepoint(true, 2014101800, 'checkskill');
    }

    return $result;

}
