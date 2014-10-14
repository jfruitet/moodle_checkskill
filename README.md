
# Checkskill - Moodle module

Checkskill Version : $module->release  = 'JF-2.x (Build: 2014101300)';

By jean.fruitet@univ-nantes.fr

CheckSkill  is a fork on Checklist plugin

https://github.com/davosmith/moodle-checklist

I have made some additions to the original code to get some new functionnalities, so:

* Users may comment their Skills / Tasks and upload files or URL as prove of practice.
	* user can add descriptions to items
	* user can add file to descriptions
	* user can export (if configured) the list of items to Mahara

* Teacher may import a list of Outcomes as list of tasks / skills
* Teacher may export items as Outcomes

When outcomes are imported in CheckSkill, these outcomes are checked automaticaly in CheckSkill when checked in any Moodle activity using these outcomes in the same course.
 

## How CheckSkill differs from CheckList

1. New data description and document

	a. New DB tables 'checkskill_description' and 'checkskill_document'
	b. New config parameter :
$CFG->checkskill_description_display
New script
./mod/checkskill/settings.php
	c. New scripts
edit_description.php
edit_document.php
delete_description.php
delete_document.php
file_api.php
	d. Scripts modification
lib.php :

// MOODLE 2.0 FILE API
	function checkskill_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload) {
//Serves activite documents and other files.
	function checkskill_send_file($course, $cm, $context, $filearea, $args) {
// Serves activite documents and other files.

locallib.php : many functions added or  modified 
	New function view_select_export, select_items, *_description, *_document,  checkskill_affiche_url
	Functions modified : view_tabs, view, deleteitem, etc.


2. Import of Outcomes file (csv format) to get Outcomes as Items in Checkskill
Teachers may import Outcomes (outcomes.csv) files in Checkskill to get these outcomes as Items.
Furthermore any Item of Checkskill may be validated by the way of Moodle activity
(Assignment or Quizz for exemple) which uses the same Outcomes.

	a. This does not affect any Checkskill DB tables

	b. New config parameter :
$CFG->checkskill_outcomes_input

	c. New scripts :
importexportoutcomes.php
import_outcomes.php
export_outcomes.php
export_selected_outcomes.php
select_export.php
cron_outcomes.php

	d. Scripts modification
lib.php ::
// Process Outcomes as Items
    $outcomesupdate = 0;
    if (!empty($CFG->enableoutcomes)){
        require_once($CFG->dirroot.'/mod/checkskill/cron_outcomes.php');
        $outcomesupdate+=checkskill_cron_outcomes($lastlogtime);
    }
    if ($outcomesupdate) {
        mtrace(" Updated $outcomesupdate checkmark(s) from outcomes changes");
    }

// MOODLE 2.0 FILE API
    function checkskill_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload) {
//Serves activite documents and other files.
    function checkskill_send_file($course, $cm, $context, $filearea, $args) {
// Serves activite documents and other files.


locallib.php ::
function view_import_export() {
(...)
// MODIF JF 2012/03/18
        if (USES_OUTCOMES && !empty($CFG->checkskill_outcomes_input)){
            $importoutcomesurl = new moodle_url('/mod/checkskill/import_outcomes.php', array('id' => $this->cm->id));
            $importoutcomesstr = get_string('import_outcomes', 'checkskill');
            $exportoutcomesurl = new moodle_url('/mod/checkskill/select_export.php', array('id' => $this->cm->id));
            $exportoutcomesstr = get_string('export_outcomes', 'checkskill');
            echo "<a href='$importurl'>$importstr</a>&nbsp;&nbsp;<a href='$importoutcomesurl'>$importoutcomesstr</a>&nbsp;&nbsp;<a href='$exporturl'>$exportstr</a>&nbsp;&nbsp;<a href='$exportoutcomesurl'>$exportoutcomesstr</a>";
        }
        else{
            echo "<a href='$importurl'>$importstr</a> &nbsp;&nbsp;&nbsp; <a href='$exporturl'>$exportstr</a>";
        }
(...)
}

autoupdate.php
In function checkskill_autoupdate(
// MODIF JF 2012/03/18
    if ($module == 'referentiel') {
        return 0;
    }

New localisation strings

lang/en/checkskill.php   :: new strings
lang/fr/checkskill.php   :: new strings and translation

Functions replacement :
In all scripts
error('error_message') -> print_error(get_string('error_code', 'checkskill'));


	3. Backup / Restore :
./mod/checkskill/backup/moodle2 scripts completed for new tables

	4. Installation
./mod/checkskill/install.xml
./mod/checkskill/upgrade.php


