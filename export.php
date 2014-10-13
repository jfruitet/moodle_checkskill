<?php

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/importexportfields.php');
$id = required_param('id', PARAM_INT); // course module id

$cm = get_coursemodule_from_id('checkskill', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
$checkskill = $DB->get_record('checkskill', array('id' => $cm->instance), '*', MUST_EXIST);

$url = new moodle_url('/mod/checkskill/export.php', array('id' => $cm->id));
$url->param('id', $id);

$PAGE->set_url($url);
require_login($course, true, $cm);

if ($CFG->version < 2011120100) {
    $context = get_context_instance(CONTEXT_MODULE, $cm->id);
} else {
    $context = context_module::instance($cm->id);
}
if (!has_capability('mod/checkskill:edit', $context)) {
    print_error(get_string('error_export_items', 'checkskill')); // You do not have permission to export items from this checkskill'
}

$items = $DB->get_records_select('checkskill_item', "checkskill = ? AND userid = 0", array($checkskill->id), 'position');
if (!$items) {
    print_error(get_string('noitems', 'checkskill'));
}

if (strpos($CFG->wwwroot, 'https://') === 0) { //https sites - watch out for IE! KB812935 and KB316431
    @header('Cache-Control: max-age=10');
    @header('Expires: '. gmdate('D, d M Y H:i:s', 0) .' GMT');
    @header('Pragma: ');
} else { //normal http - prevent caching at all cost
    @header('Cache-Control: private, must-revalidate, pre-check=0, post-check=0, max-age=0');
    @header('Expires: '. gmdate('D, d M Y H:i:s', 0) .' GMT');
    @header('Pragma: no-cache');
}

$strcheckskill = get_string('checkskill', 'checkskill');

header("Content-Type: application/download\n");
$downloadfilename = clean_filename("{$course->shortname} $strcheckskill {$checkskill->name}");
header("Content-Disposition: attachment; filename=\"$downloadfilename.csv\"");

// Output the headings
echo implode($separator, $fields)."\n";

foreach ($items as $item) {
    $output = array();
    foreach ($fields as $field => $title) {
        $output[] = str_replace(',',' ',$item->$field);  // MODIF JF ',' is a separator
    }
    echo implode($separator, $output)."\n";
}

exit;
