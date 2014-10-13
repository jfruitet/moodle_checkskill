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
 * This file defines the main checkskill configuration form
 * It uses the standard core Moodle (>1.8) formslib. For
 * more info about them, please visit:
 *
 * http://docs.moodle.org/en/Development:lib/formslib.php
 *
 * The form must provide support for, at least these fields:
 *   - name: text element of 64cc max
 *
 * Also, it's usual to use these fields:
 *   - intro: one htmlarea element to describe the activity
 *            (will be showed in the skill of activities of
 *             newmodule type (index.php) and in the header
 *             of the checkskill main page (view.php).
 *   - introformat: The format used to write the contents
 *             of the intro field. It automatically defaults
 *             to HTML when the htmleditor is used and can be
 *             manually selected if the htmleditor is not used
 *             (standard formats are: MOODLE, HTML, PLAIN, MARKDOWN)
 *             See lib/weblib.php Constants and the format_text()
 *             function for more info
 */

require_once($CFG->dirroot.'/course/moodleform_mod.php');

class mod_checkskill_mod_form extends moodleform_mod {

    function definition() {

        global $COURSE, $CFG;
        $mform =& $this->_form;

//-------------------------------------------------------------------------------
    /// Adding the "general" fieldset, where all the common settings are showed
        $mform->addElement('header', 'general', get_string('general', 'form'));

    /// Adding the standard "name" field
        $mform->addElement('text', 'name', get_string('modulename', 'checkskill'), array('size'=>'64'));
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        $this->add_intro_editor(true, get_string('checkskillintro', 'checkskill'));

//-------------------------------------------------------------------------------

        $mform->addElement('header', 'checkskillsettings', get_string('checkskillsettings', 'checkskill'));

        $ynoptions = array( 0 => get_string('no'), 1 => get_string('yes'));
        $mform->addElement('select', 'useritemsallowed', get_string('useritemsallowed', 'checkskill'), $ynoptions);

        $teditoptions = array();
        $teditoptions[CHECKSKILL_MARKING_STUDENT] = get_string('teachernoteditcheck','checkskill');
        $teditoptions[CHECKSKILL_MARKING_TEACHER] = get_string('teacheroverwritecheck', 'checkskill');
        $teditoptions[CHECKSKILL_MARKING_BOTH] = get_string('teacheralongsidecheck', 'checkskill');
        $mform->addElement('select', 'teacheredit', get_string('teacheredit', 'checkskill'), $teditoptions);

        $mform->addElement('select', 'duedatesoncalendar', get_string('duedatesoncalendar', 'checkskill'), $ynoptions);
        $mform->setDefault('duedatesoncalendar', 0);

        // These settings are all disabled, as they are not currently implemented

        /*
        $themes = array('default' => 'default');
        $mform->addElement('select', 'theme', get_string('theme', 'checkskill'), $themes);
        */

        $mform->addElement('select', 'teachercomments', get_string('teachercomments', 'checkskill'), $ynoptions);
        $mform->setDefault('teachercomments', 1);
        $mform->setAdvanced('teachercomments');

        $mform->addElement('text', 'maxgrade', get_string('maximumgrade'), array('size'=>'10'));
        $mform->setType('maxgrade', PARAM_INT);
		$mform->setDefault('maxgrade', 100);
        $mform->setAdvanced('maxgrade');

        $emailrecipients = array(   CHECKSKILL_EMAIL_NO => get_string('no'),
                                    CHECKSKILL_EMAIL_STUDENT => get_string('teachernoteditcheck', 'checkskill'),
                                    CHECKSKILL_EMAIL_TEACHER => get_string('teacheroverwritecheck', 'checkskill'),
                                    CHECKSKILL_EMAIL_BOTH => get_string('teacheralongsidecheck', 'checkskill'));
        $mform->addElement('select', 'emailoncomplete', get_string('emailoncomplete', 'checkskill'), $emailrecipients);
        $mform->setDefault('emailoncomplete', 0);
        $mform->addHelpButton('emailoncomplete', 'emailoncomplete', 'checkskill');

        $autopopulateoptions = array (CHECKSKILL_AUTOPOPULATE_NO => get_string('no'),
                                      CHECKSKILL_AUTOPOPULATE_SECTION => get_string('importfromsection','checkskill'),
                                      CHECKSKILL_AUTOPOPULATE_COURSE => get_string('importfromcourse', 'checkskill'));
        $mform->addElement('select', 'autopopulate', get_string('autopopulate', 'checkskill'), $autopopulateoptions);
        $mform->setDefault('autopopulate', 0);
        $mform->addHelpButton('autopopulate', 'autopopulate', 'checkskill');

        $autoupdate_options = array( CHECKSKILL_AUTOUPDATE_NO => get_string('no'),
                                     CHECKSKILL_AUTOUPDATE_YES => get_string('yesnooverride', 'checkskill'),
                                     CHECKSKILL_AUTOUPDATE_YES_OVERRIDE => get_string('yesoverride', 'checkskill'));
        $mform->addElement('select', 'autoupdate', get_string('autoupdate', 'checkskill'), $autoupdate_options);
        $mform->setDefault('autoupdate', 1);
        $mform->disabledIf('autoupdate', 'autopopulate', 'eq', 0);
        $mform->addHelpButton('autoupdate', 'autoupdate', 'checkskill');
        $mform->addElement('static', 'autoupdatenote', '', get_string('autoupdatenote', 'checkskill'));

        $mform->addElement('selectyesno', 'lockteachermarks', get_string('lockteachermarks', 'checkskill'));
        $mform->setDefault('lockteachermarks', 0);
        $mform->setAdvanced('lockteachermarks');
        $mform->addHelpButton('lockteachermarks', 'lockteachermarks', 'checkskill');

//-------------------------------------------------------------------------------
        // add standard elements, common to all modules
        $this->standard_coursemodule_elements();

//-------------------------------------------------------------------------------
        // add standard buttons, common to all modules
        $this->add_action_buttons();

    }

    function data_preprocessing(&$default_values) {
        parent::data_preprocessing($default_values);

        // Set up the completion checkboxes which aren't part of standard data.
        // We also make the default value (if you turn on the checkbox) for those
        // numbers to be 1, this will not apply unless checkbox is ticked.
        $default_values['completionpercentenabled']=
            !empty($default_values['completionpercent']) ? 1 : 0;
        if (empty($default_values['completionpercent'])) {
            $default_values['completionpercent']=100;
        }
    }

    function add_completion_rules() {
        $mform =& $this->_form;

        $group=array();
        $group[] =& $mform->createElement('checkbox', 'completionpercentenabled', '', get_string('completionpercent','checkskill'));
        $group[] =& $mform->createElement('text', 'completionpercent', '', array('size'=>3));
        $mform->setType('completionpercent',PARAM_INT);
        $mform->addGroup($group, 'completionpercentgroup', get_string('completionpercentgroup','checkskill'), array(' '), false);
        $mform->disabledIf('completionpercent','completionpercentenabled','notchecked');

        return array('completionpercentgroup');
    }

    function completion_rule_enabled($data) {
        return (!empty($data['completionpercentenabled']) && $data['completionpercent']!=0);
    }

    function get_data() {
        $data = parent::get_data();
        if (!$data) {
            return false;
        }
        // Turn off completion settings if the checkboxes aren't ticked
        $autocompletion = !empty($data->completion) && $data->completion==COMPLETION_TRACKING_AUTOMATIC;
        if (empty($data->completionpercentenabled) || !$autocompletion) {
            $data->completionpercent = 0;
        }
        return $data;
    }

}
