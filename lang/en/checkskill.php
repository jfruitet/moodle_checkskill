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

$string['checkskill:addinstance'] = 'Add a CheckSkill instance';


$string['addcomments'] = 'Add comments';

$string['additem'] = 'Add';
$string['additemalt'] = 'Add a new item to the list';
$string['additemhere'] = 'Insert new item after this one';
$string['addownitems'] = 'Add your own items';
$string['addownitems-stop'] = 'Stop adding your own items';

$string['allowmodulelinks'] = 'Allow module links';

$string['anygrade'] = 'Any';
$string['autopopulate'] = 'Show course modules in Checkskill';
$string['autopopulate_help'] = 'This will automatically add a list of all the resources and activities in the current course into the CheckSkill list.<br />
This list will be updated with any changes in the course, whenever you visit the \'Edit\' page for the CheckSkill list.<br />
Items can be hidden from the list, by clicking on the \'hide\' icon beside them.<br />
To remove the automatic items from the list, change this option back to \'No\', then click on \'Remove course module items\' on the \'Edit\' page.';
$string['autoupdate'] = 'Check-off when modules complete';
$string['autoupdate_help'] = 'This will automatically check-off items in your list when you complete the relevant activity in the course.<br />
\'Completing\' an activity varies from one activity to another - \'view\' a resource, \'submit\' a quiz or assignment, \'post\' to a forum or join in with a chat, etc.<br />
If a Moodle 2.0 completion tracking is switched on for a particular activity, that will be used to tick-off the item in the list<br />
For details of exactly what causes an activity to be marked as \'complete\', ask your site administrator to look in the file \'mod/checkskill/autoupdate.php\'<br />
Note: it can take up to 60 seconds for a student\'s activity to be reflected in their CheckSkill list';
$string['autoupdatenote'] = 'It is the \'student\' mark that is automatically updated - no updates will be displayed for \'Teacher only\' list';

$string['autoupdatewarning_both'] = 'There are items on this CheckSkill list that will be automatically updated (as students complete the related activity). However, as this is a \'student and teacher\' checkskill the progress bars will not update until a teacher agrees the marks given.';
$string['autoupdatewarning_student'] = 'There are items on this skill that will be automatically updated (as students complete the related activity).';
$string['autoupdatewarning_teacher'] = 'Automatic updating has been switched on for this CheckSkill list, but these marks will not be displayed as only \'teacher\' marks are shown.';

$string['canceledititem'] = 'Cancel';

$string['calendardescription'] = 'This event was added by the CheckSkill list: {$a}';

$string['changetextcolour'] = 'Next text colour';

$string['checkeditemsdeleted'] = 'Checked items deleted';

$string['checkskill'] = 'CheckSkill';
$string['pluginadministration'] = 'Checkskill administration';

$string['checkskill:edit'] = 'Create and edit lists';
$string['checkskill:emailoncomplete'] = 'Receive completion emails';
$string['checkskill:preview'] = 'Preview a list';
$string['checkskill:updatelocked'] = 'Update locked list marks';
$string['checkskill:updateother'] = 'Update students\' list marks';
$string['checkskill:updateown'] = 'Update your list marks';
$string['checkskill:viewmenteereports'] = 'View mentee progress (only)';
$string['checkskill:viewreports'] = 'View students\' progress';

$string['checkskillautoupdate'] = 'Allow lists to automatically update';

$string['checkskillfor'] = 'List for';

$string['checkskillintro'] = 'Introduction';
$string['checkskillsettings'] = 'Settings';

$string['checks'] = 'Check marks';
$string['comments'] = 'Comments';

$string['completionpercentgroup'] = 'Require checked-off';
$string['completionpercent'] = 'Percentage of items that should be checked-off:';

$string['configcheckskillautoupdate'] = 'Before allowing this you must make a few changes to the core Moodle code, please see mod/checkskill/README.txt for details';
$string['configshowcompletemymoodle'] = 'If this is unchecked then completed Checklists will be hidden from the \'My Moodle\' page';
$string['configshowmymoodle'] = 'If this is unchecked then Checklist activities (with progress bars) will no longer appear on the \'My Moodle\' page';

$string['confirmdeleteitem'] = 'Are you sure you want to permanently delete this CheckSkill item?';

$string['deleteitem'] = 'Delete this item';

$string['duedatesoncalendar'] = 'Add due dates to calendar';

$string['edit'] = 'Edit checkskill';
$string['editchecks'] = 'Edit checks';
$string['editdatesstart'] = 'Edit dates';
$string['editdatesstop'] = 'Stop editing dates';
$string['edititem'] = 'Edit this item';

$string['emailoncomplete'] = 'Email when checkskill is complete:';
$string['emailoncomplete_help'] = 'When a checkskill is complete, a notification email can be sent: to the student who completed it, to all the teachers on the course or to both.<br />
An administrator can control who receives this email using the capability \'mod:checkskill/emailoncomplete\' - by default all teachers and non-editing teachers have this capability.';
$string['emailoncompletesubject'] = 'User {$a->user} has completed checkskill \'{$a->checkskill}\'';
$string['emailoncompletesubjectown'] = 'You have completed checkskill \'{$a->checkskill}\'';
$string['emailoncompletebody'] = 'User {$a->user} has completed checkskill \'{$a->checkskill}\' in the course \'{$a->coursename}\' 
View the checkskill here:';
$string['emailoncompletebodyown'] = 'You have completed checkskill \'{$a->checkskill}\' in the course \'{$a->coursename}\' 
View the checkskill here:';

$string['eventcheckskillcomplete'] = 'Checkskill complete';
$string['eventeditpageviewed'] = 'Edit page viewed';
$string['eventreportviewed'] = 'Report viewed';
$string['eventstudentchecksupdated'] = 'Student checks updated';
$string['eventstudentdescriptionupdated'] = 'CheckSkill Item description updated';
$string['eventteacherchecksupdated'] = 'Teacher checks updated';

$string['export'] = 'Export items';

$string['forceupdate'] = 'Update checks for all automatic items';

$string['gradetocomplete'] = 'Grade to complete:';
$string['guestsno'] = 'You do not have permission to view this CheckSkill list';

$string['headingitem'] = 'This item is a heading - it will not have a checkbox beside it';

$string['import'] = 'Import items';
$string['importfile'] = 'Choose file to import';
$string['importfromsection'] = 'Current section';
$string['importfromcourse'] = 'Whole course';
$string['indentitem'] = 'Indent item';
$string['itemcomplete'] = 'Completed';
$string['items'] = 'Checkskill items';

$string['linktomodule'] = 'Link to this module';

$string['lockteachermarks'] = 'Lock teacher marks';
$string['lockteachermarks_help'] = 'When this setting is enabled, once a teacher has saved a \'Yes\' mark, they will be unable to change it. Users with the capability \'mod/checkskill:updatelocked\' will still be able to change the mark.';
$string['lockteachermarkswarning'] = 'Note: Once you have saved these marks, you will be unable to change any \'Yes\' marks';

$string['modulename'] = 'Checkskill';
$string['modulenameplural'] = 'Checkskills';

$string['moveitemdown'] = 'Move item down';
$string['moveitemup'] = 'Move item up';

$string['noitems'] = 'No items in the checkskill';

$string['optionalitem'] = 'This item is optional';
$string['optionalhide'] = 'Hide optional items';
$string['optionalshow'] = 'Show optional items';

$string['percentcomplete'] = 'Required items';
$string['percentcompleteall'] = 'All items';
$string['pluginname'] = 'Checkskill';
$string['preview'] = 'Preview';
$string['progress'] = 'Progress';

$string['removeauto'] = 'Remove course module items';

$string['report'] = 'View Progress';
$string['reporttablesummary'] = 'Table showing the items on the checkskill that each student has completed';

$string['requireditem'] = 'This item is required - it must be completed';

$string['resetcheckskillprogress'] = 'Reset CheckSkill progress and user items';

$string['savechecks'] = 'Save';

$string['showcompletemymoodle'] = 'Show completed CheckSkills on \'My Moodle\' page';
$string['showfulldetails'] = 'Show full details';
$string['showmymoodle'] = 'Show CheckSkills on \'My Moodle\' page';
$string['showprogressbars'] = 'Show progress bars';

$string['teachercomments'] = 'Teachers can add comments';
$string['teacherdate'] = 'Date a teacher last updated this item';

$string['teacheredit'] = 'Updates by';
$string['teacherid'] = 'The teacher who last updated this mark';

$string['teachermarkundecided'] = 'Teacher has not yet marked this';
$string['teachermarkyes'] = 'Teacher states that you have completed this';
$string['teachermarkno'] = 'Teacher states that you have NOT completed this';

$string['teachernoteditcheck'] = 'Student only';
$string['teacheroverwritecheck'] = 'Teacher only';
$string['teacheralongsidecheck'] = 'Student and teacher';

$string['togglecolumn'] = 'Toggle Column';
$string['toggledates'] = 'Toggle dates';
$string['togglerow'] = 'Toggle Row';

$string['theme'] = 'Checkskill display theme';

$string['updatecompletescore'] = 'Save completion grades';
$string['unindentitem'] = 'Unindent item';
$string['updateitem'] = 'Update';
$string['userdate'] = 'Date the user last updated this item';
$string['useritemsallowed'] = 'User can add their own items';
$string['useritemsdeleted'] = 'User items deleted';

$string['view'] = 'View Checkskill';
$string['viewall'] = 'View all students';
$string['viewallcancel'] = 'Cancel';
$string['viewallsave'] = 'Save';

$string['viewsinglereport'] = 'View progress for this user';
$string['viewsingleupdate'] = 'Update progress for this user';

$string['yesnooverride'] = 'Yes, cannot override';
$string['yesoverride'] = 'Yes, can override';

// CheckskillPlus = CheckSkill specific strings

$string['modulename_help'] = '"CheckSkill" implements a validation of skills (or of tasks) activity borrowed from the Checklist plugin.

This module allows :

* to edit lists of tasks and skills (or to load them);

* to validate / comment / proove tasks ansd skills;

* If Outcomes are set, you can import an Outcomes file (CSV format) as a task / skills list;
The evaluations of any Moodle activity (Forum, DB, Assignments...) which uses these ouotcomes will be uploaded automaticalley in the CheckSkill module.';
$string['modulename_link'] = 'mod/checkskill/view';

$string['a_completer'] = 'TO COMPLETE';
$string['add_link'] = 'Add a link or a document';
$string['addreferentielname'] = 'Skill repository code name';
$string['argumentation'] = 'Argumentation';
$string['checkskill_check'] = 'Evaluation ';
$string['checkskill_description'] = 'Allows students to upload files as prove of practice';
$string['clicktopreview'] = 'click to preview in full-size popup';
$string['clicktoselect'] = 'click to select page';
$string['commentby'] = 'Comment by ';
$string['config_description'] = 'User can load documents as activity proof.';
$string['config_outcomes_input'] = 'Allows teachers to import in Checkskill the outcomes checked in course\'s activities';
$string['confirmreferentielname'] = 'Confirm skills repository code name ';
$string['delete_description'] = 'Delete the description';
$string['delete_document'] = 'Delete a document';
$string['delete_link'] = 'Delete a link';
$string['description_document'] = 'Document description ';
$string['description'] = 'Type in your argumentation';

$string['descriptionh_help'] = 'Indicate in a brief way the motives which allow you to assert that this task is finished or the skill acquired.';
$string['descriptionh'] = 'Argumentation Help';
$string['doc_num'] = 'Document N�{$a} ';
$string['document_associe'] = 'Linked Document';
$string['documenth'] = 'Document Help';
$string['documenth_help'] = 'Documents linked to a description are "prove of pratice oriented".

You may link to each Item a description and one or many files ou URLs (Web links).
* Document description : a short notice.

* URL : Web link (or a file uploaded from your computer).

* Title of the link

* Targeted frame';
$string['edit_description'] = 'Edit the description';
$string['edit_document'] = 'Edit the document';
$string['edit_link'] = 'Edit a link';
$string['error_action'] = 'Error: Invalid action - "{a}"';
$string['error_checkskill_id'] = 'Checkskill ID was incorrect';
$string['error_cm'] = 'Course Module is incorrect';
$string['error_cmid'] = 'Course Module ID was incorrect';
$string['error_course'] = 'Course is misconfigured';
$string['error_export_items'] = 'You do not have permission to export items from this Checkskill list';
$string['error_file_upload'] = 'Something went wrong with the file upload';
$string['error_import_items'] = 'You do not have permission to import items to this CheckSkill list';
$string['error_insert_db'] = 'Unable to insert DB record for item';
$string['error_itemskill'] = 'Error: invalid (or missing) items skill';
$string['error_number_columns_outcomes'] = 'Row Outcome has incorrect number of columns in it:<br />{$a}';
$string['error_number_columns'] = 'Row has incorrect number of columns in it:<br />{$a}';
$string['error_select'] = 'Error: At least one Item has to be selected';
$string['error_sesskey'] = 'Error: Invalid sesskey';
$string['error_specif_id'] = 'You must specify a course_module ID or an instance ID';
$string['error_update'] = 'Error: you do not have permission to update this CheckSkill list';
$string['error_user'] = 'No such user!';
$string['export_outcomes'] = 'Export items as outcomes';
$string['id'] = 'ID# ';
$string['import_outcomes'] = 'Import outcomes as items';
$string['input_description'] = 'Draft your argument';
$string['items_exporth_help'] = 'Selected items will be exported in the same Outcomes file.';
$string['items_exporth'] = 'Exported Items';
$string['mustprovideexportformat'] = 'You must provide an export format';
$string['mustprovideinstanceid'] = 'You must provide an instance id';
$string['mustprovideuser'] = 'You must provide an user id';
$string['nomaharahostsfound'] = 'No mahara hosts found.';
$string['noviewscreated'] = 'You have not created any pages in {$a}.';
$string['noviewsfound'] = 'No matching pages found in {$a}.';
$string['OK'] = 'OK';
$string['old_comment'] = 'Previous comment:';
$string['outcome_date'] =  ' Date: ';
$string['outcome_description'] = 'Outcome Description';
$string['outcome_link'] = ' <a href="{$a->link}">{$a->name}</a> ';
$string['outcome_name'] = 'Outcome name';
$string['outcome_shortname'] = 'Outcome shortname';
$string['outcome_type'] = 'Activity';
$string['outcomes_input'] = 'Activate Outcomes files';
$string['outcomes'] = 'outcomes';   // DO NOT TRANSLATE. NE PAS TRADUIRE
$string['pluginname'] = 'Mahara portfolio';
$string['previewmahara'] = 'Preview';
$string['quit'] = 'Quit';
$string['referentiel_codeh_help'] = 'This code name identify the outcomes matching the same Skills repository. <br />If Items names are not keys check <i>"Use Item ID as key"</i>';
$string['referentiel_codeh'] = 'Type in a skills repository code name (Optional)';
$string['scale_description'] = 'This scale is intended to estimate the acquisition of the skills by the way of Outcomes.';
$string['scale_items'] = 'Not relevent,Non validated,Validated';
$string['scale_name'] = 'Skill Item';
$string['select_all'] = 'Check all Items';
$string['select_items_export'] = 'Selection items to exporte';
$string['select_not_any'] = 'Uncheck all Items';
$string['select'] = 'Select';
$string['selectedview'] = 'Submitted Page';
$string['selectexport'] = 'Export Outcomes';
$string['selectmaharaview'] = 'Select one of your {$a->name} portfolio pages from this complete skill, or <a href="{$a->jumpurl}">click here</a> to visit {$a->name} and create a page right now.';
$string['site_help'] = 'This setting lets you select which Mahara site your students should submit their pages from. (The Mahara site must already be configured for mnet networking with this Moodle site.)';
$string['site'] = 'Site';
$string['submission'] = 'Submission';
$string['target'] = 'Open that link in a new window';
$string['teachermark'] = 'Appreciation ';
$string['teachertimestamp'] = 'Evaluated the ';
$string['timecreated'] = 'Created the ';
$string['timemodified'] = 'Modified the ';
$string['title'] = 'Document title';
$string['titlemahara'] = 'Title';
$string['typemahara'] = 'Mahara portfolio';
$string['unknowdescription'] = 'No description';
$string['upload_portfolio'] = 'Link to a page of my portfolio';
$string['url'] = 'URL';
$string['urlh_help'] = 'You may copy / paste a link <br />(beginning by "<i>http://</i>" or "<i>https://</i>") inthe field URL, or you may upload a file from your computer...';
$string['urlh'] = 'Selection of a Web link';
$string['useitemid'] = 'Use Item ID as key ';
$string['usertimestamp'] = 'Demanded the ';
$string['viewmahara'] = 'Mahara view';
$string['views'] = 'Pages';
$string['viewsby'] = 'Pages by {$a}';

