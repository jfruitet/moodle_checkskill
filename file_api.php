<?php

// This file is part of Moodle - http://moodle.org/
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
 * Stores all the functions for manipulating a checkskill
 *
 * @author   David Smith <moodle@davosmith.co.uk>
 * @package  mod/checkskill
 * file api package
 * @author   Jean Fruitet <jean.fruitet@univ-nantes.fr>
 */


require_once($CFG->libdir.'/formslib.php');//putting this is as a safety as i got a class not found error.

// ############################ MOODLE 2.0 FILE API #########################
/**
 * @author JF 2012/03/18
 * @author jean.fruitet@univ-nantes.fr
 **/


/**
 * Serves activite documents and other files.
 *
 * @param object $course
 * @param object $cm
 * @param object $context
 * @param string $filearea
 * @param array $args
 * @param bool $forcedownload
 * @return bool false if file not found, does not return if found - just send the file
 */
function checkskill_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload) {
// fonction appel�e par le gestionnaire de fichier
    global $CFG, $DB;

    if ($context->contextlevel != CONTEXT_MODULE) {
        return false;
    }

    require_login($course, false, $cm);

    // verify that a checkskill is installed in this course
    if (! $checkskill = $DB->get_record("checkskill", array("id" => "$cm->instance"))) {
        return false;
    }

    return checkskill_send_file($course, $cm, $context, $filearea, $args);
}

/**
 * Serves activite documents and other files.
 *
 * @param object $course
 * @param object $cm
 * @param object $context
 * @param string $filearea
 * @param array $args
 * @return bool false if file not found, does not return if found - just send the file
 */

function checkskill_send_file($course, $cm, $context, $filearea, $args) {
// affiche le contenu d'un fichier
        global $CFG, $DB, $USER;
        require_once($CFG->libdir.'/filelib.php');

        require_login($course, false, $cm);

        // DEBUG
        // echo "<br />DEBUG :: lib.php :: 110 :: <br />CONTEXT : $context->id :: FILEAREA : $filearea<br />ARGS:\n";
        // print_r($args);
        //
        $docid = (int)array_shift($args);

        // verifier les fileareas acceptables
        $fileareas = array('checkskill', 'document');
        if (!in_array($filearea, $fileareas)) {
            return false;
        }

        if ($filearea=='document'){
            if (!$document = $DB->get_record('checkskill_document', array('id'=>$docid))) {
                return false;
            }
        }

        $relativepath = implode('/', $args);
        $fullpath = '/'.$context->id.'/mod_checkskill/'.$filearea.'/'.$docid.'/'.$relativepath;

        $fs = get_file_storage();

        if (!$file = $fs->get_file_by_hash(sha1($fullpath)) or $file->is_directory()) {
            return false;
        }

        send_stored_file($file, 0, 0, true); // download MUST be forced - security!
}
// END FILE API
// ###############################################################################################

/**
 * @package   mod-checkskill
 * @copyright 1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_checkskill_description_form extends moodleform {
    function definition() {
        $mform = & $this->_form;
        $instance = $this->_customdata;
        // visible elements
        $mform->addElement('header', 'general', $instance['msg']);
        $mform->addHelpButton('general', 'descriptionh','checkskill');

        $mform->addElement('textarea', 'description', get_string('description','checkskill'), 'wrap="virtual" rows="6" cols="70"');
        if (!empty($instance['description']) && !empty($instance['description']->description)){
            $mform->setDefault('description', stripslashes($instance['description']->description));
        }

        // hidden params
        $mform->addElement('hidden', 'checkskill', $instance['checkskill']);
        $mform->setType('checkskill', PARAM_INT);

        $mform->addElement('hidden', 'descriptionid');
        $mform->setType('descriptionid', PARAM_INT);
        if (!empty($instance['description']) && !empty($instance['description']->id)){
            $mform->setDefault('descriptionid', $instance['description']->id);
        }
        else{
            $mform->setDefault('descriptionid',0);
        }

        $mform->addElement('hidden', 'contextid', $instance['contextid']);
        $mform->setType('contextid', PARAM_INT);

        $mform->addElement('hidden', 'itemid', $instance['itemid']);
        $mform->setType('itemid', PARAM_INT);

        $mform->addElement('hidden', 'userid', $instance['userid']);
        $mform->setType('userid', PARAM_INT);

        // buttons
        $this->add_action_buttons(true, get_string('savechanges', 'admin'));
    }
}



/**
 * @package   mod-checkskill
 * @copyright 1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_checkskill_add_document_upload_form extends moodleform {
    function definition() {
        $mform = &$this->_form;
        $instance = $this->_customdata;
        // print_object($instance);
        // exit;
        // visible elements
        $mform->addElement('header', 'general', $instance['msg']);
        $mform->addHelpButton('general', 'documenth','checkskill');

        $mform->addElement('text','description_document',get_string('description_document','checkskill'));
        $mform->setType('description_document', PARAM_TEXT);

        if (!empty($instance['document']) && isset($instance['document']->description_document)){
            $mform->setDefault('description_document', stripslashes($instance['document']->description_document));
        }

        $mform->addElement('text','title',get_string('title','checkskill'));
        $mform->setType('title', PARAM_TEXT);

        if (!empty($instance['document']) && isset($instance['document']->title)){
            $mform->setDefault('title', $instance['document']->title);
        }

        $mform->addElement('text','url',get_string('url','checkskill'));
        $mform->setType('url', PARAM_URL);

        if (!empty($instance['document']) && !empty($instance['document']->url_document)){
            $mform->setDefault('url', $instance['document']->url_document);
        }

        // $mform->addHelpButton('url', 'documenth','checkskill');

        $radioarray=array();
        $radioarray[] = $mform->createElement('radio', 'target', '', get_string('yes'), 1, NULL);
        $radioarray[] = $mform->createElement('radio', 'target', '', get_string('no'), 0, NULL);
        $mform->addGroup($radioarray, 'target', get_string('target','checkskill'), array(' '), false);
        if (!empty($instance['document']) && isset($instance['document']->target)){
            $mform->setDefault('target', $instance['document']->target);
        }else{
            $mform->setDefault('target', 1);
        }
        
        // Get a file
        $mform->addElement('filepicker', 'checkskill_file', get_string('uploadafile'), null, $instance['options']);
        // $mform->addElement('filemanager', 'checkskill_file', get_string('uploadafile'), null, $instance['options']);

        // hidden params

        $mform->addElement('hidden', 'checkskill', $instance['checkskill']);
        $mform->setType('checkskill', PARAM_INT);


        $mform->addElement('hidden', 'descriptionid');
        $mform->setType('descriptionid', PARAM_INT);
        if ($instance['descriptionid']){
            $mform->setDefault('descriptionid', $instance['descriptionid']);
        }
        
        $mform->addElement('hidden', 'contextid', $instance['contextid']);
        $mform->setType('contextid', PARAM_INT);

        $mform->addElement('hidden', 'itemid', $instance['itemid']);
        $mform->setType('itemid', PARAM_INT);

        $mform->addElement('hidden', 'userid', $instance['userid']);
        $mform->setType('userid', PARAM_INT);

        $mform->addElement('hidden', 'filearea', $instance['filearea']);
        $mform->setType('filearea', PARAM_ALPHA);

        $mform->addElement('hidden', 'action', 'uploadfile');
        $mform->setType('action', PARAM_ALPHA);

        // buttons
        $this->add_action_buttons(true, get_string('savechanges', 'admin'));
    }
}



/**
 * @package   mod-checkskill
 * @copyright 1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_checkskill_update_document_upload_form extends moodleform {
    function definition() {
        $mform = & $this->_form;
        $instance = $this->_customdata;
        // print_object($instance);
        // exit;

        // visible elements
        $mform->addElement('header', 'general', $instance['msg']);
        $mform->addHelpButton('general', 'documenth','checkskill');

        $mform->addElement('text','description_document',get_string('description_document','checkskill'));
        $mform->setType('description_document', PARAM_TEXT);

        if (!empty($instance['document']) && isset($instance['document']->description_document)){
            $mform->setDefault('description_document', stripslashes($instance['document']->description_document));
        }

        $mform->addElement('text','title',get_string('title','checkskill'));
        $mform->setType('title', PARAM_TEXT);

        if (!empty($instance['document']) && isset($instance['document']->title)){
            $mform->setDefault('title', $instance['document']->title);
        }

        if (!empty($instance['document']) && !empty($instance['document']->url_document)){
            $mform->addElement('html', '&nbsp;  &nbsp;  &nbsp;  &nbsp; '.get_string('url','checkskill').' <span class="small"><i>'.$instance['document']->url_document.'</i></span>'."\n");
            $mform->addElement('hidden', 'url_old', $instance['document']->url_document);
            $mform->setType('url_old', PARAM_URL);
        }
        $mform->addElement('text','url',get_string('url','checkskill'));
        $mform->setType('url', PARAM_URL);
        $mform->setDefault('url', '');
        $mform->addHelpButton('url', 'urlh','checkskill');

        // $mform->addHelpButton('url', 'documenth','checkskill');

        $radioarray=array();
        $radioarray[] = $mform->createElement('radio', 'target', '', get_string('yes'), 1, NULL);
        $radioarray[] = $mform->createElement('radio', 'target', '', get_string('no'), 0, NULL);
        $mform->addGroup($radioarray, 'target', get_string('target','checkskill'), array(' '), false);
        if (!empty($instance['document']) && isset($instance['document']->target)){
            $mform->setDefault('target', $instance['document']->target);
        }else{
            $mform->setDefault('target', 1);
        }

        // get a file
        $mform->addElement('filepicker', 'checkskill_file', get_string('uploadafile'), null, $instance['options']);

        // hidden params

        $mform->addElement('hidden', 'checkskill', $instance['checkskill']);
        $mform->setType('checkskill', PARAM_INT);

        $mform->addElement('hidden', 'documentid');
        $mform->setType('documentid', PARAM_INT);
        if (!empty($instance['document']) && !empty($instance['document']->id)){
            $mform->setDefault('documentid', $instance['document']->id);
        }

        $mform->addElement('hidden', 'descriptionid');
        $mform->setType('descriptionid', PARAM_INT);
        if ($instance['descriptionid']){
            $mform->setDefault('descriptionid', $instance['descriptionid']);
        }

        $mform->addElement('hidden', 'contextid', $instance['contextid']);
        $mform->setType('contextid', PARAM_INT);

        $mform->addElement('hidden', 'itemid', $instance['itemid']);
        $mform->setType('itemid', PARAM_INT);

        $mform->addElement('hidden', 'userid', $instance['userid']);
        $mform->setType('userid', PARAM_INT);

        $mform->addElement('hidden', 'filearea', $instance['filearea']);
        $mform->setType('filearea', PARAM_ALPHA);

        $mform->addElement('hidden', 'action', 'uploadfile');
        $mform->setType('action', PARAM_ALPHA);

        // buttons
        $this->add_action_buttons(true, get_string('savechanges', 'admin'));
    }
}

// ############################ MOODLE 2.0 FILE API #########################

//------------------
function checkskill_set_description($mform, $checkskillid){
// file form managing
// table checkskill_document update
global $CFG, $USER, $DB, $OUTPUT;

    $viewurl=new moodle_url('/mod/checkskill/view.php');
    $viewurl->param('checkskill', $checkskillid);

    if ($formdata = $mform->get_data()) {
        if (empty($formdata->descriptionid)){
                    $description = new object();
                    $description->itemid=$formdata->itemid;
                    $description->userid=$formdata->userid;
                    $description->description=addslashes($formdata->description);
                    $description->timestamp=time();
                    // Insert Description
                    if ($descid = $DB->insert_record("checkskill_description", $description)){
                        $description->id=$descid;
                        return true;
                    }
                    else{
                        return false;
                    }
        }
        else{
                    $description = new object();
                    $descid =$formdata->descriptionid;
                    $description->id=$descid;
                    $description->itemid=$formdata->itemid;
                    $description->userid=$formdata->userid;
                    $description->description=addslashes($formdata->description);
                    $description->timestamp=time();
                    // Update Description
                    $DB->update_record("checkskill_description", $description);
                    return true;
        }
        // DEBUG
        /*
        echo "<br />file_api.php :: 301\n";
        print_object($description);
        echo "<br />\n";
        //exit;
        */
        /*
        if ($description->itemid && $description->userid && $description->id){
            //$documenturl=new moodle_url('/mod/checkskill/edit_document.php');
            $documenturl=new moodle_url('/mod/checkskill/view.php');
            $documenturl->param('checkskill', $checkskillid);
            $documenturl->param('itemid', $description->itemid);
            $documenturl->param('userid', $description->userid);
            $documenturl->param('descriptionid', $descid);

            //echo "<br />URL: ".$documenturl;
            //exit;

            // redirect($documenturl);
        }
        */
    }

    //redirect($viewurl);
    return false;
}


//------------------
function checkskill_add_upload_document($mform, $checkskillid){
// Document creation form
// checkskill_document update
// sets checkskill_document table
global $CFG, $USER, $DB, $OUTPUT;

    $viewurl=new moodle_url('/mod/checkskill/view.php', array('checkskill'=>$checkskillid));

    if ($formdata = $mform->get_data()) {
        // document
        $fileareas = array('checkskill', 'document');

        if (empty($formdata->filearea) || !in_array($formdata->filearea, $fileareas)) {
            return false;
        }

        if (($formdata->filearea=='document') && !empty($formdata->descriptionid)) {

            $document = new object();
            $document->descriptionid=$formdata->descriptionid;
            if (isset($formdata->description_document)){
                $document->description_document=$formdata->description_document;
            }
            else{
                $document->description_document='';
            }
            $document->url_document=''; // sera mis à jour plus bas
            $document->target=$formdata->target;
            $document->timestamp=time();

            $fs = get_file_storage();
            // suppression du fichier existant ?   NON
            // $fs->delete_area_files($formdata->contextid, 'mod_checkskill', $formdata->filearea, $docid);

            // Verifier si un fichier est depose
            if ($newfilename = $mform->get_new_filename('checkskill_file')) {
                // gestion d'un fichier à la fois
                
                if (!empty($formdata->title)){
                    $document->title=$formdata->title;
                }
                else{
                    $document->title=$newfilename;
                }

                // get the id for url
                if ($docid = $DB->insert_record("checkskill_document", $document)){
                    $document->id=$docid;
                    $file = $mform->save_stored_file('checkskill_file', $formdata->contextid,
                        'mod_checkskill', $formdata->filearea, $docid, '/', $newfilename);
                    // file adresse calculation
                    $fullpath = "/$formdata->contextid/mod_checkskill/$formdata->filearea/$docid/$newfilename";
                    $link = new moodle_url($CFG->wwwroot.'/pluginfile.php'.$fullpath);
                    // Update
                    $DB->set_field("checkskill_document", "url_document", $fullpath, array("id" => "$docid"));
                }
            }
            else if (!empty($formdata->url)){
                $document->url_document = $formdata->url;
                $link = $formdata->url;
                if (!empty($formdata->title)){
                    $document->title=$formdata->title;
                }
                else{
                    $document->title=get_string('url', 'checkskill');
                }
                if ($docid = $DB->insert_record("checkskill_document", $document)){
                    $document->id=$docid;
                }
            }
            else if (!empty($formdata->url_old)){
                $document->url_document = $formdata->url_old;
                $link = $formdata->url_old;
                if (!empty($formdata->title)){
                    $document->title=$formdata->title;
                }
                else{
                    $document->title=get_string('url', 'checkskill');
                }
                if ($docid = $DB->insert_record("checkskill_document", $document)){
                    $document->id=$docid;
                }
            }
            // echo link ?? NOP
            // echo  '<div align="center"><a href="'.$link.'" target="_blank">'.$link.'</a>'."</div>\n";
        }
    }

    redirect($viewurl);
}



//------------------
function checkskill_update_upload_document($mform, $checkskillid){
// Form document
// sets checkskill_document table
global $CFG, $USER, $DB, $OUTPUT;

    $viewurl=new moodle_url('/mod/checkskill/view.php', array('checkskill'=>$checkskillid));

    if ($formdata = $mform->get_data()) {
        // document
        $fileareas = array('checkskill', 'document');

        if (empty($formdata->filearea) || !in_array($formdata->filearea, $fileareas)) {
            return false;
        }

        if (($formdata->filearea=='document') && !empty($formdata->descriptionid) && !empty($formdata->documentid)) {

            $document = new object();
            $document->id=$formdata->documentid;
            $document->descriptionid=$formdata->descriptionid;
            if (isset($formdata->description_document)){
                $document->description_document=$formdata->description_document;
            }
            else{
                $document->description_document='';
            }
            $document->url_document=''; // sera mis à jour plus bas
            $document->target=$formdata->target;
            $document->timestamp=time();

            $fs = get_file_storage();
            // suppression du fichier existant ?   NON
            // $fs->delete_area_files($formdata->contextid, 'mod_checkskill', $formdata->filearea, $docid);

            // Verifier si un fichier est depose
            if ($newfilename = $mform->get_new_filename('checkskill_file')) {
                // gestion d'un fichier à la fois

                if (!empty($formdata->title)){
                    $document->title=$formdata->title;
                }
                else{
                    $document->title=$newfilename;
                }
                // echo "<br />";
                $file = $mform->save_stored_file('checkskill_file', $formdata->contextid,
                        'mod_checkskill', $formdata->filearea, $formdata->documentid, '/', $newfilename);

                // File adress
                $fullpath = "/$formdata->contextid/mod_checkskill/$formdata->filearea/$formdata->documentid/$newfilename";
                $link = new moodle_url($CFG->wwwroot.'/pluginfile.php'.$fullpath);
                // Update
                $document->url_document=$fullpath;
            }
            else if (!empty($formdata->url)){
                $document->url_document = $formdata->url;
                $link = $formdata->url;
                if (!empty($formdata->title)){
                    $document->title=$formdata->title;
                }
                else{
                    $document->title=get_string('url', 'checkskill');
                }
            }
            else if (!empty($formdata->url_old)){
                $document->url_document = $formdata->url_old;
                $link = $formdata->url_old;
                if (!empty($formdata->title)){
                    $document->title=$formdata->title;
                }
                else{
                    $document->title=get_string('url', 'checkskill');
                }
            }
            $DB->update_record("checkskill_document", $document);

            // Display link ???
            // echo  '<div align="center"><a href="'.$link.'" target="_blank">'.$link.'</a>'."</div>\n";
        }
    }

    redirect($viewurl);
}


// ------------------
function checkskill_get_area_files($contextid, $filearea, $docid){
// Url skill of files in filearea
global $CFG;
    // fileareas autorisees
    $fileareas = array('checkskill', 'document');
    if (!in_array($filearea, $fileareas)) {
        return false;
    }

    $strfilename=get_string('filename', 'checkskill');
    $strfilesize=get_string('filesize', 'checkskill');
    $strtimecreated=get_string('timecreated', 'checkskill');
    $strtimemodified=get_string('timemodified', 'checkskill');
    $strmimetype=get_string('mimetype', 'checkskill');
    $strurl=get_string('url');

    $table = new html_table();

	$table->head  = array ($strfilename, $strfilesize, $strtimecreated, $strtimemodified, $strmimetype);
    $table->align = array ("center", "left", "left", "left");

    $fs = get_file_storage();
    if ($files = $fs->get_area_files($contextid, 'mod_checkskill', $filearea, $docid, "timemodified", false)) {
         foreach ($files as $file) {
            // print_object($file);
            $filesize = $file->get_filesize();
            $filename = $file->get_filename();
            $mimetype = $file->get_mimetype();
            $filepath = $file->get_filepath();
            $fullpath ='/'.$contextid.'/mod_checkskill/'.$filearea.'/'.$docid.$filepath.$filename;

            $timecreated =  userdate($file->get_timecreated(),"%Y/%m/%d-%H:%M",99,false);
            $timemodified = userdate($file->get_timemodified(),"%Y/%m/%d-%H:%M",99,false);
            $link= new moodle_url($CFG->wwwroot.'/pluginfile.php'.$fullpath);
            $url='<a href="'.$link.'" target="_blank">'.$filename.'</a><br />'."\n";
            $table->data[] = array ($url, $filesize, $timecreated, $timemodified, $mimetype);
        }
    }
    echo html_writer::table($table);
}

// ------------------
function checkskill_get_manage_files($contextid, $filearea, $docid, $titre, $appli){
// Url skill of files in filearea
// Delete proposal
global $CFG;
global $OUTPUT;
    $total_size=0;
    $nfile=0;
    // fileareas autorisees
    $fileareas = array('checkskill', 'document');
    if (!in_array($filearea, $fileareas)) {
        return false;
    }
    $strfilepath='filepath';
    $strfilename=get_string('filename', 'checkskill');
    $strfilesize=get_string('filesize', 'checkskill');
    $strtimecreated=get_string('timecreated', 'checkskill');
    $strtimemodified=get_string('timemodified', 'checkskill');
    $strmimetype=get_string('mimetype', 'checkskill');
    $strmenu=get_string('delete');

    $strurl=get_string('url');


    $fs = get_file_storage();
    if ($files = $fs->get_area_files($contextid, 'mod_checkskill', $filearea, $docid, "timemodified", false)) {
        $table = new html_table();
	    $table->head  = array ($strfilename, $strfilesize, $strtimecreated, $strtimemodified, $strmimetype, $strmenu);
        $table->align = array ("center", "left", "left", "left", "center");

        foreach ($files as $file) {
            // print_object($file);
            $filesize = $file->get_filesize();
            $filename = $file->get_filename();
            $mimetype = $file->get_mimetype();
            $filepath = $file->get_filepath();
            $fullpath ='/'.$contextid.'/mod_checkskill/'.$filearea.'/'.$docid.$filepath.$filename;
            // echo "<br />FULPATH :: $fullpath \n";
            $timecreated =  userdate($file->get_timecreated(),"%Y/%m/%d-%H:%M",99,false);
            $timemodified = userdate($file->get_timemodified(),"%Y/%m/%d-%H:%M",99,false);

            $link= new moodle_url($CFG->wwwroot.'/pluginfile.php'.$fullpath);
            $url='<a href="'.$link.'" target="_blank">'.$filename.'</a><br />'."\n";

            $delete_link='<input type="checkbox" name="deletefile[]"  value="'.$fullpath.'" />'."\n";
            $table->data[] = array ($url, display_size($filesize), $timecreated, $timemodified, $mimetype, $delete_link);
            $total_size+=$filesize;
            $nfile++;
        }
        $table->data[] = array (get_string('nbfile', 'checkskill',$nfile), get_string('totalsize', 'checkskill', display_size($total_size)),'','','','');

        echo $OUTPUT->box_start('generalbox  boxaligncenter');
        echo '<div align="center">'."\n";
        echo '<h3>'.$titre.'</h3>'."\n";
        echo '<form method="post" action="'.$appli.'">'."\n";
        echo html_writer::table($table);
        echo "\n".'<input type="hidden" name="sesskey" value="'.sesskey().'" />'."\n";
        echo '<input type="submit" value="'.get_string('delete').'" />'."\n";
        echo '</form>'."\n";
        echo '</div>'."\n";
        echo $OUTPUT->box_end();
    }
}

// ------------------
function checkskill_get_a_file($filename, $contextid, $filearea, $itemid=0){
// Get un file

global $CFG;
    // fileareas autorisees
    $fileareas = array('checkskill', 'document');
    if (!in_array($filearea, $fileareas)) {
        return false;
    }

    $strfilename=get_string('filename', 'checkskill');
    $strfilesize=get_string('filesize', 'checkskill');
    $strtimecreated=get_string('timecreated', 'checkskill');
    $strtimemodified=get_string('timemodified', 'checkskill');
    $strmimetype=get_string('mimetype', 'checkskill');
    $strurl=get_string('url');

    $table = new html_table();

	$table->head  = array ($strfilename, $strfilesize, $strtimecreated, $strtimemodified, $strmimetype);
    $table->align = array ("center", "left", "left", "left");
    $fs = get_file_storage();
    $file = $fs->get_file($contextid, 'mod_checkskill', $filearea, $itemid,'/', $filename);
    if ($file) {
        // DEBUG
        // echo "<br />DEBUG :: 621 :: $filename\n";
        // print_object($file);
        // echo "<br />CONTENU\n";
        // $contents = $file->get_content();
        // echo htmlspecialchars($contents);
        $filesize = $file->get_filesize();
        $filename = $file->get_filename();
        $mimetype = $file->get_mimetype();
        $filepath = $file->get_filepath();
        $fullpath ='/'.$contextid.'/mod_checkskill/'.$filearea.'/'.$docid.$filepath.$filename;

        $timecreated =  userdate($file->get_timecreated(),"%Y/%m/%d-%H:%M",99,false);
        $timemodified = userdate($file->get_timemodified(),"%Y/%m/%d-%H:%M",99,false);
        $link= new moodle_url($CFG->wwwroot.'/pluginfile.php'.$fullpath);
        $url='<a href="'.$link.'" target="_blank">'.$filename.'</a><br />'."\n";
        $table->data[] = array ($url, $filesize, $timecreated, $timemodified, $mimetype);
    }

    echo html_writer::table($table);
}

/**
 * This function wil delete a file
 * fullpath of the form /contextid/mod_checkskill/filearea/itemid.path.filename
 * path  : any path beginning and ending in / like '/' or '/rep1/rep2/'
 * @fullpath string
 * @return nothing
 */

// ---------------------------------
function checkskill_delete_a_file($fullpath){
// supprime un fichier
// case 0 : $fullpath matches "jf44.png";
// case 1 : $fullpath matches "/30/mod_checkskill/checkskill/0/rep1/rep2/jf44.png"
// case 2 : $fullpath matches "/51/mod_checkskill/checkskill/12/jf44.png"
global $CFG;

    // initialisation par defaut
    $contextid=0;
    $component='mod_checkskill';
    $filearea='checkskill';
    $itemid=0;
    $path='/';
    $filename=$fullpath;

    // Traitement de $fullpath
    if ($fullpath && preg_match('/\//', $fullpath)){
        $t_fullpath=explode('/',$fullpath,6);
        if (!empty($t_fullpath) && empty($t_fullpath[0])){
            $garbage=array_shift($t_fullpath);
        }
        if (!empty($t_fullpath)){
            list($contextid, $component, $filearea, $itemid, $path)  = $t_fullpath;
            if ($path){
                if (preg_match('/\//', $path)){
                    $filename=substr($path, strrpos($path, '/')+1);
                    $path='/'.substr($path, 0, strrpos($path, '/')+1);
                }
                else{
                    $filename=$path;
                    $path='/';
                }
            }
        }
    }

    // echo "<br />DEBUG :: lib.php :: Ligne 687 ::<br /> $contextid, $component, $filearea, $itemid, $path, $filename\n";
    // display case 0  :: 0, mod_checkskill, checkskill, 0, /, jf44.png
    // display case 1  :: 30, mod_checkskill, checkskill, 0, /rep1/rep2/, jf44.png
    // dispay case 2  :: 51, mod_checkskill, checkskill, 12, /, jf44.png

    require_once($CFG->libdir.'/filelib.php');
    $fs = get_file_storage();

    // Get file
    $file = $fs->get_file($contextid, $component, $filearea, $itemid, $path, $filename);

    // Delete it if exists
    if ($file) {
        $file->delete();
    }
}


