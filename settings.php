<?php 

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {
	$options = array();

    $settings->add(new admin_setting_configcheckbox('checkskill/showmymoodle',
                                                    get_string('showmymoodle', 'mod_checkskill'),
                                                    get_string('configshowmymoodle', 'mod_checkskill'), 1));
    $settings->add(new admin_setting_configcheckbox('checkskill/showcompletemymoodle',
                                                    get_string('showcompletemymoodle', 'mod_checkskill'),
                                                    get_string('configshowcompletemymoodle', 'mod_checkskill'), 1));

	// user can add description and documents
	$options[0] = 0;
	$options[1] = 1;
	if (isset($CFG->checkskill_description_display)){
		$settings->add(new admin_setting_configselect('checkskill_description_display', get_string('checkskill_description', 'checkskill'),
                   get_string('config_description', 'checkskill'), $CFG->checkskill_description_display, $options));
	}
	else{
		$settings->add(new admin_setting_configselect('checkskill_description_display', get_string('checkskill_description', 'checkskill'),
                   get_string('config_description', 'checkskill'), 1, $options));
	}

	// user can import or export outcomes files as items skills
	unset($options);
	$options[0] = 0;
	$options[1] = 1;
	if (isset($CFG->checkskill_outcomes_input)){
		$settings->add(new admin_setting_configselect('checkskill_outcomes_input', get_string('outcomes_input', 'checkskill'),
                   get_string('config_outcomes_input', 'checkskill'), $CFG->checkskill_outcomes_input, $options));
	}
	else{
		$settings->add(new admin_setting_configselect('checkskill_outcomes_input', get_string('outcomes_input', 'checkskill'),
                   get_string('config_outcomes_input', 'checkskill'), 1, $options));
	}
}
?>