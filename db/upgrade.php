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

function xmldb_checkskill_upgrade($oldversion=0) {

    global $CFG, $THEME, $DB;

    $dbman = $DB->get_manager();
    $result = true;

    if ($result && $oldversion < 2010022500) {
        // Adjust (currently unused) 'teachermark' fields to be 0 when unmarked, not 2
        $sql = 'UPDATE {checkskill_check} ';
        $sql .= 'SET teachermark=0 ';
        $sql .= 'WHERE teachermark=2';
        $DB->execute($sql);

        upgrade_mod_savepoint($result, 2010022500, 'checkskill');
    }

    if ($result && $oldversion < 2010022800) {
        // All checkskills created before this point were 'student only' checkskills
        // Update the default & previously created checkskills to reflect this

        $sql = 'UPDATE {checkskill} ';
        $sql .= 'SET teacheredit=0 ';
        $sql .= 'WHERE teacheredit=2';
        $DB->execute($sql);

        $table = new xmldb_table('checkskill');
        $field = new xmldb_field('teacheredit', XMLDB_TYPE_INTEGER, '4', XMLDB_UNSIGNED, null, null, '0', 'useritemsallowed');
        $dbman->change_field_type($table, $field);

        /// checkskill savepoint reached
        upgrade_mod_savepoint($result, 2010022800, 'checkskill');
    }

    if ($result && $oldversion < 2010031600) {
        notify('Processing checkskill grades, this may take a while if there are many checkskills...', 'notifysuccess');

        require_once(dirname(dirname(__FILE__)).'/lib.php');

        // too much debug output
        $olddebug = $DB->get_debug();
        $DB->set_debug(false);
        checkskill_update_all_grades();
        $DB->set_debug($olddebug);

        /// checkskill savepoint reached
        upgrade_mod_savepoint($result, 2010031600, 'checkskill');
    }

    if ($result && $oldversion < 2010041800) {
        $table = new xmldb_table('checkskill_item');
        $field = new xmldb_field('duetime', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, '0', 'itemoptional');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        /// checkskill savepoint reached
        upgrade_mod_savepoint($result, 2010041800, 'checkskill');
    }

    if ($result && $oldversion < 2010041801) {
        $table = new xmldb_table('checkskill');
        $field = new xmldb_field('duedatesoncalendar', XMLDB_TYPE_INTEGER, '4', XMLDB_UNSIGNED, null, null, '0', 'theme');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        /// checkskill savepoint reached
        upgrade_mod_savepoint($result, 2010041801, 'checkskill');
    }

    if ($result && $oldversion < 2010041900) {

        /// Define field eventid to be added to checkskill_item
        $table = new xmldb_table('checkskill_item');
        $field = new xmldb_field('eventid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, '0', 'duetime');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        /// checkskill savepoint reached
        upgrade_mod_savepoint($result, 2010041900, 'checkskill');
    }

    if ($result && $oldversion < 2010050100) {

        /// Define field teachercomments to be added to checkskill
        $table = new xmldb_table('checkskill');
        $field = new xmldb_field('teachercomments', XMLDB_TYPE_INTEGER, '4', XMLDB_UNSIGNED, null, null, '1', 'duedatesoncalendar');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        /// Define table checkskill_comment to be created
        $table = new xmldb_table('checkskill_comment');

        /// Adding fields to table checkskill_comment
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('itemid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');
        $table->add_field('commentby', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, '0');
        $table->add_field('text', XMLDB_TYPE_TEXT, 'medium', null, null, null, null);

        /// Adding keys to table checkskill_comment
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        /// Adding indexes to table checkskill_comment
        $table->add_index('checkskill_item_user', XMLDB_INDEX_UNIQUE, array('itemid', 'userid'));

        /// Conditionally launch create table for checkskill_comment
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        /// checkskill savepoint reached
        upgrade_mod_savepoint($result, 2010050100, 'checkskill');
    }

    if ($result && $oldversion < 2010091003) {
        $table = new xmldb_table('checkskill_item');
        $field = new xmldb_field('colour', XMLDB_TYPE_CHAR, '15', null, XMLDB_NOTNULL, null, 'black');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_mod_savepoint($result, 2010091003, 'checkskill');
    }

    if ($result && $oldversion < 2010102703) {
        $table = new xmldb_table('checkskill');
        $field = new xmldb_field('maxgrade', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '100');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_mod_savepoint($result, 2010102703, 'checkskill');
    }

    if ($result && $oldversion < 2010112000) {
        $table = new xmldb_table('checkskill');
        $field = new xmldb_field('autopopulate', XMLDB_TYPE_INTEGER, '4', XMLDB_UNSIGNED, null, null, '0');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        $field = new xmldb_field('autoupdate', XMLDB_TYPE_INTEGER, '4', XMLDB_UNSIGNED, null, null, '1');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $table = new xmldb_table('checkskill_item');
        $field = new xmldb_field('moduleid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, '0');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $table->add_index('item_module', XMLDB_INDEX_NOTUNIQUE, array('moduleid'));

        upgrade_mod_savepoint($result, 2010112000, 'checkskill');
    }

    if ($result && $oldversion < 2010113000) {
        $table = new xmldb_table('checkskill');
        $field = new xmldb_field('completionpercent', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, '0');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_mod_savepoint($result, 2010113000, 'checkskill');
    }

    if ($result && $oldversion < 2011021600) {

        // I really should not have to update the 'cron' field manually
        $chkmod = $DB->get_record('modules', array('name' => 'checkskill'));
        if ($chkmod) {
            $chkmod_upd = new stdClass;
            $chkmod_upd->id = $chkmod->id;
            $chkmod_upd->cron = 60;
            $DB->update_record('modules', $chkmod_upd);
        }

    }

    if ($result && $oldversion < 2011021900) {
        $table = new xmldb_table('checkskill_item');
        $field = new xmldb_field('hidden', XMLDB_TYPE_INTEGER, '4', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Switch alll 'hidden headings' to being headings & hidden
        $sql = 'UPDATE {checkskill_item} ';
        $sql .= 'SET hidden=1, itemoptional=2 ';
        $sql .= 'WHERE itemoptional=4';
        $DB->execute($sql);

        // Switch all 'hidden items' to being required items & hidden
        $sql = 'UPDATE {checkskill_item} ';
        $sql .= 'SET hidden=1, itemoptional=0 ';
        $sql .= 'WHERE itemoptional=3';
        $DB->execute($sql);

        upgrade_mod_savepoint($result, 2011021900, 'checkskill');
    }

    if ($result && $oldversion < 2011022700) {
        $table = new xmldb_table('checkskill_item');
        $field = new xmldb_field('grouping', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, '0', 'hidden');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_mod_savepoint($result, 2011022700, 'checkskill');
    }

    if ($result && $oldversion < 2011052901) {
        $table = new xmldb_table('checkskill');
        $field = new xmldb_field('emailoncomplete', XMLDB_TYPE_INTEGER, '4', XMLDB_UNSIGNED, null, null, '0');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_mod_savepoint($result, 2011052901, 'checkskill');
    }

    if ($result && $oldversion < 2011082001) {
        $table = new xmldb_table('checkskill');
        $field = new xmldb_field('lockteachermarks', XMLDB_TYPE_INTEGER, '4', XMLDB_UNSIGNED, null, null, '0');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_mod_savepoint($result, 2011082001, 'checkskill');
    }



    if ($result && $oldversion < 2012072614) {

        /// Define table checkskill_description to be created
        $table = new xmldb_table('checkskill_description');

        /// Adding fields to table checkskill_description
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('itemid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');
        $table->add_field('description', XMLDB_TYPE_TEXT, 'medium', null, null, null, null);
        $table->add_field('timestamp', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');

        /// Adding keys to table checkskill_description
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        /// Adding indexes to table checkskill_description
        $table->add_index('checkskill_item_user', XMLDB_INDEX_UNIQUE, array('itemid', 'userid'));

        /// Conditionally launch create table for checkskill_description
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        /// Define table checkskill_document to be created
        $table = new xmldb_table('checkskill_document');

        /// Adding fields to table checkskill_document
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('descriptionid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');
        $table->add_field('description_document', XMLDB_TYPE_TEXT, 'medium', null, null, null, null);
        $table->add_field('url_document', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('target', XMLDB_TYPE_INTEGER, '4', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');
        $table->add_field('title', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timestamp', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');

        /// Adding keys to table checkskill_document
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        /// Adding indexes to table checkskill_document
        $table->add_index('index_description', XMLDB_INDEX_NOTUNIQUE, array('descriptionid'));

        /// Conditionally launch create table for checkskill_document
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        upgrade_mod_savepoint($result, 2012072614, 'checkskill');
    
    }

    if ($oldversion < 2014101300) {

        // Define field teacherid to be added to checkskill_check
        $table = new xmldb_table('checkskill_check');
        $field = new xmldb_field('teacherid', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'teachertimestamp');

        // Conditionally launch add field teacherid
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // checklist savepoint reached
        upgrade_mod_savepoint(true, 2014101300, 'checklist');
    }

    return $result;

}