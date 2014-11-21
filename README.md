
# Checkskill - Moodle module

Checkskill Version : $module->release  = 'JF-2.x (Build: 2014101300)';

By jean.fruitet@univ-nantes.fr

CheckSkill  is a fork on Checklist plugin  : https://github.com/davosmith/moodle-checklist

I have made some additions to the original code to get some new functionnalities, so:

* Student may comment their Skills / Tasks and upload files or URL as prove of practice.
* Student can add descriptions to items
* Student can add file to descriptions
* Student can export (if configured) the list of items to Mahara
* Teacher may import a list of Outcomes as list of tasks / skills
* Teacher may export items as Outcomes
* When outcomes are imported in CheckSkill, these outcomes are checked automaticaly in CheckSkill when checked in any Moodle activity using these outcomes in the same course.

## Installation

* Download Zip archive in ".YOUR_MOODLE_SERVER/mod/" directory
* Log as admin
* Notification, update database.
* Go to any course
* Add a new activity "CheckSkill"
* Import any Outcommes file of any kind as Item list.
* Let Students check their skills, upload documents, etc.
* Let Teachers add comments / evaluate.


## How CheckSkill differs from CheckList

1. New data description and document
	* New DB tables 'checkskill_description' and 'checkskill_document'
	* New config parameter :  $CFG->checkskill_description_display <br />New script : settings.php
    * New scripts : edit_description.php, edit_document.php, delete_description.php, delete_document.php, file_api.php

2. Portfolio Mahara export added:
	* lib.php : MOODLE 2.0 FILE API
    * function checkskill_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload) serves activite documents and other files.
    * locallib.php : many functions added or  modified

3. Import of Outcomes file (csv format) to get Outcomes as Items in Checkskill
    * Teachers may import Outcomes (outcomes.csv) files in Checkskill to get these outcomes as Items.
    * Furthermore any Item of Checkskill may be validated by the way of Moodle activity (Assignment or Quiz for exemple) which uses the same Outcomes.
    * This does not affect any Checkskill DB tables
	* New config parameter: $CFG->checkskill_outcomes_input
	* New scripts : importexportoutcomes.php, import_outcomes.php, export_outcomes.php, export_selected_outcomes.php, select_export.php, cron_outcomes.php, file_api.php
	* Scripts modification: lib.php // Process Outcomes as Items, locallib.php : function view_import_export(), autoupdate.php
	* New localisation strings: lang/en/checkskill.php: new strings, lang/fr/checkskill.php: new strings and translation

4. Backup / Restore script modified: backup/moodle2 scripts completed for new tables

5. Installation script modified: ./mod/checkskill/install.xml, ./mod/checkskill/upgrade.php


