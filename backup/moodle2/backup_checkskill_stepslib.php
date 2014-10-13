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
 * Define all the backup steps that will be used by the backup_forum_activity_task
 */

/**
 * Define the complete checkskill structure for backup, with file and id annotations
 */
class backup_checkskill_activity_structure_step extends backup_activity_structure_step {

    protected function define_structure() {

        // To know if we are including userinfo
        $userinfo = $this->get_setting_value('userinfo');

        // Define each element separated

        $checkskill = new backup_nested_element('checkskill', array('id'), array(
            'name', 'intro', 'introformat', 'timecreated', 'timemodified', 'useritemsallowed',
            'teacheredit', 'theme', 'duedatesoncalendar', 'teachercomments', 'maxgrade',
            'autopopulate', 'autoupdate', 'completionpercent', 'emailoncomplete', 'lockteachermarks'));

        $items = new backup_nested_element('items');

        $item = new backup_nested_element('item', array('id'),
                                          array('userid', 'displaytext', 'position', 'indent',
                                                'itemoptional', 'duetime', 'colour', 'moduleid', 'hidden'));

        $checks = new backup_nested_element('checks');

        $check = new backup_nested_element('check', array('id'), array(
            'userid', 'usertimestamp', 'teachermark', 'teachertimestamp', 'teacherid'));

        $comments = new backup_nested_element('comments');

        $comment = new backup_nested_element('comment', array('id'), array(
            'userid', 'commentby', 'text'));

        $descriptions = new backup_nested_element('descriptions');

        $description = new backup_nested_element('description', array('id'), array(
            'userid', 'description', 'timestamp'));

        $documents = new backup_nested_element('documents');

        $document = new backup_nested_element('document', array('id'), array(
            'descriptionid', 'description_document', 'url_document', 'target', 'title', 'timestamp'));

			// Build the tree
        $checkskill->add_child($items);
        $items->add_child($item);

        $item->add_child($checks);
        $checks->add_child($check);

        $item->add_child($comments);
        $comments->add_child($comment);

        $item->add_child($descriptions);
        $descriptions->add_child($description);
		
        $description->add_child($documents);
        $documents->add_child($document);

        // Define sources
        $checkskill->set_source_table('checkskill', array('id' => backup::VAR_ACTIVITYID));

        if ($userinfo) {
            $item->set_source_table('checkskill_item', array('checkskill' => backup::VAR_PARENTID));
            $check->set_source_table('checkskill_check', array('item' => backup::VAR_PARENTID));
            $comment->set_source_table('checkskill_comment', array('itemid' => backup::VAR_PARENTID));
            $description->set_source_table('checkskill_description', array('itemid' => backup::VAR_PARENTID));
            $document->set_source_table('checkskill_document', array('descriptionid' => backup::VAR_PARENTID));
        } else {
            $item->set_source_sql('SELECT * FROM {checkskill_item} WHERE userid = 0 AND checkskill = ?', array(backup::VAR_PARENTID));
        }

        // Define id annotations
        $item->annotate_ids('user', 'userid');
        $item->annotate_ids('course_modules', 'moduleid');
        $check->annotate_ids('user', 'userid');
		$check->annotate_ids('user', 'teacherid');
        $comment->annotate_ids('user', 'userid');
        $comment->annotate_ids('user', 'commentby');
        $description->annotate_ids('user', 'userid');
		
        // Define file annotations

        $checkskill->annotate_files('mod_checkskill', 'intro', null); // This file area hasn't itemid
        $checkskill->annotate_files('mod_checkskill', 'document', 'id'); // This file area has itemid

        // Return the root element (forum), wrapped into standard activity structure
        return $this->prepare_activity_structure($checkskill);
    }

}
