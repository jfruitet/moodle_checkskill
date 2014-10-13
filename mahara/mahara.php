<?php

require_once($CFG->libdir.'/formslib.php');


/**
 * @package   mod-checkskill
 * @copyright 1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_checkskill_mahara_upload_form extends moodleform {
    function definition() {
        global $CFG, $COURSE, $DB;
        
        $mform = & $this->_form;
        $instance = $this->_customdata;
        // print_object($instance);
        // exit;
        // visible elements
        $mform->addElement('header', 'general', $instance['msg']);
        $mform->addHelpButton('general', 'documenth','checkskill');


        // Get Mahara hosts we are doing SSO with
        $sql = "
             SELECT DISTINCT
                 h.id,
                 h.name
             FROM
                 {mnet_host} h,
                 {mnet_application} a,
                 {mnet_host2service} h2s_IDP,
                 {mnet_service} s_IDP,
                 {mnet_host2service} h2s_SP,
                 {mnet_service} s_SP
             WHERE
                 h.id != :mnet_localhost_id AND
                 h.id = h2s_IDP.hostid AND
                 h.deleted = 0 AND
                 h.applicationid = a.id AND
                 h2s_IDP.serviceid = s_IDP.id AND
                 s_IDP.name = 'sso_idp' AND
                 h2s_IDP.publish = '1' AND
                 h.id = h2s_SP.hostid AND
                 h2s_SP.serviceid = s_SP.id AND
                 s_SP.name = 'sso_idp' AND
                 h2s_SP.publish = '1' AND
                 a.name = 'mahara'
             ORDER BY
                 h.name";

        if ($hosts = $DB->get_records_sql($sql, array('mnet_localhost_id'=>$CFG->mnet_localhost_id))) {
            foreach ($hosts as &$h) {
                $h = $h->name;
            }
            $mform->addElement('select', 'hostid', get_string("site"), $hosts);
            $mform->addHelpButton('hostid', 'site', 'checkskill');
            $mform->setDefault('hostid', key($hosts));
        }
        else {
            // TODO: Should probably error out if no mahara hosts found?
            $mform->addElement('static', '', '', get_string('nomaharahostsfound', 'checkskill'));
        }

        $ynoptions = array( 0 => get_string('no'), 1 => get_string('yes'));

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

        $mform->addElement('hidden', 'action', 'uploadfile');
        $mform->setType('action', PARAM_ALPHA);

        // buttons
        $this->add_action_buttons(true, get_string('savechanges', 'admin'));

    }
}


/**
 * checkskill class for mahara portfolio
 *
 */
class checkskill_mahara_class extends checkskill_class {

    private $remotehost;
    private $remotehostid;
    
    function checkskill_mahara_class($cmid='staticonly', $userid=0, $checkskill=NULL, $cm=NULL, $course=NULL) {
        parent::checkskill_class($cmid, $userid, $checkskill, $cm, $course);
    }

    /* ****************
    function view() {
        global $CFG, $OUTPUT;

        if ((!$this->items) && $this->canedit()) {
            redirect(new moodle_url('/mod/checkskill/edit.php', array('id' => $this->cm->id)) );
        }

        $currenttab = 'view';

        $this->view_header();

        echo $OUTPUT->heading(format_string($this->checkskill->name));

        $this->view_tabs($currenttab);

        add_to_log($this->course->id, 'checkskill', 'view', "view.php?id={$this->cm->id}", $this->checkskill->name, $this->cm->id);

         // Mahara && Portofolio stuff
        if (empty($CFG->enableportfolios)){
            redirect(new moodle_url('/mod/checkskill/view.php', array('id' => $this->cm->id)) );
        }
        else{
            // DEBUG
            // echo "<br />DEBUG :: assigment/type/mahara/assigment.class.php :: 75\n";

            $query = optional_param('q', null, PARAM_TEXT);
            // echo "<br />QUERY : $query\n";
            
            list($error, $views) = $this->get_views($query);

            // DEBUG
            // echo "<br />DEBUG :: assigment/type/mahara/assigment.class.php :: 83<br />ERROR<br />\n";
            // print_object($error);
            // echo "<br />VIEWS\n";
            // print_object($views);
            // echo "<br />\n";
            // exit;
            
            if ($error) {
                echo $error;
            } else {
                $this->remotehost = $DB->get_record('mnet_host', array('id'=>$this->remote_mnet_host_id()));
                $this->remotehost->jumpurl = $CFG->wwwroot . '/auth/mnet/jump.php?hostid=' . $this->remotehost->id;
                echo '<form><div>' . get_string('selectmaharaview', 'checkskill', $this->remotehost) . '</div><br/>'
                  . '<input type="hidden" name="id" value="' . $this->cm->id . '">'
                  . '<label for="q">' . get_string('search') . ':</label> <input type="text" name="q" value="' . $query . '">'
                  . '</form>';
                if ($views['count'] < 1) {
                    if ($query){
                        echo get_string('noviewsfound', 'checkskill', $this->remotehost->name);
                    } else {
                        echo get_string('noviewscreated', 'checkskill', $this->remotehost->name);
                    }
                } else {
                    echo '<h4>' . $this->remotehost->name . ': ' . get_string('viewsby', 'checkskill', $views['displayname']) . '</h4>';
                    echo '<table class="formtable"><thead>'
                      . '<tr><th>' . get_string('previewmahara', 'checkskill') . '</th>'
                      . '<th>' . get_string('submit') . '</th></tr>'
                      . '<tr><td style="padding:0 5px 0 5px;">(' . get_string('clicktopreview', 'checkskill') . ')</td>'
                      . '<td style="padding:0 5px 0 5px;">(' . get_string('clicktoselect', 'checkskill') . ')</td></tr>'
                      . '</thead><tbody>';
                    foreach ($views['data'] as &$v) {
                        $windowname = 'view' . $v['id'];
                        $viewurl = $this->remotehost->jumpurl . '&wantsurl=' . urlencode($v['url']);
                        $js = "this.target='$windowname';window.open('" . $viewurl . "', '$windowname', 'resizable,scrollbars,width=920,height=600');return false;";
                        echo '<tr><td><a href="' . $viewurl . '" target="_blank" onclick="' . $js . '">'
                          . '<img align="top" src="' . $OUTPUT->pix_url('f/html') . '" height="16" width="16" alt="html" /> ' . $v['title'] . '</a></td>'
                          . '<td><a href="?id=' . $this->cm->id. '&view=' . $v['id'] . '">' . get_string('submit') . '</a></td></tr>';
                    }
                    echo '</tbody></table>';
                }
            }
        }


        $this->view_footer();
    }
    ***************/
    
    function add_mahara_document($itemid, $userid, $descriptionid, $hostid=0, $viewid=0) {
        global $DB;
        global $CFG;
        global $OUTPUT;
        global $PAGE;

        $document=NULL;

        $context = get_context_instance(CONTEXT_MODULE, $this->cm->id);

        $thispage = new moodle_url('/mod/checkskill/mahara/upload_mahara.php', array('id' => $this->cm->id) );
        $returnurl = new moodle_url('/mod/checkskill/view.php', array('id' => $this->cm->id));

        $currenttab = 'view';

        add_to_log($this->course->id, 'checkskill', 'view', "upload_mahara.php?id={$this->cm->id}", $this->checkskill->name, $this->cm->id);

        // Mahara && Portofolio stuff
        if (empty($CFG->enableportfolios)){
            redirect($returnurl);
        }

        if (empty($descriptionid) && $itemid && $userid) {
            $description = $DB->get_record('checkskill_description', array("itemid"=>$itemid, "userid"=>$userid));
            if ($description){
                $descriptionid=$description;
            }
        }

        if ($descriptionid){
            if (!empty($hostid) && !empty($viewid)) {
                $this->set_remote_mnet_host_id($hostid);
                if ($this->submit_view($descriptionid, $hostid, $viewid)) {

                    //TODO fix log actions - needs db upgrade
                    //redirect
                    redirect($returnurl);
                }
            }
            else{

                $options = array();

                $mform = new mod_checkskill_mahara_upload_form(null,
                array('checkskill'=>$this->checkskill->id,
                    'contextid'=>$context->id,
                    'itemid'=>$itemid,
                    'userid'=>$userid,
                    'descriptionid'=>$descriptionid,
                    'msg' => get_string('typemahara', 'checkskill'),
                    'options'=>$options));

                if ($mform->is_cancelled()) {
                    redirect($returnurl);
                } else if ($mform->get_data()) {
                    $this->upload($mform, $this->checkskill->id);
                    die();
                    //redirect(new moodle_url('/mod/checkskill/view.php', array('id'=>$cm->id)));
                }
            }
            $this->view_header();
            echo '<div align="center"><h3>'.get_string('edit_document', 'checkskill').'</h3></div>'."\n";
            echo $OUTPUT->box_start('generalbox boxwidthwide boxaligncenter');
            echo format_text($this->checkskill->intro, $this->checkskill->introformat);
            echo '<br/>';

            $mform->display();

            echo $OUTPUT->box_end();
            $this->view_footer();
        }
    }


    function upload($mform, $checkskillid) {
    // Document creation form
    // checkskill_document update
    // sets checkskill_document table
    global $CFG, $USER, $DB, $OUTPUT;

        $currenttab = 'view';
        $viewurl=new moodle_url('/mod/checkskill/view.php', array('checkskill'=>$checkskillid));

        if ($formdata = $mform->get_data()) {
            // DEBUG
            // print_object($formdata);
            // exit;
            if (!empty($formdata->descriptionid)) {

                if (!empty($formdata->hostid))   {
                    $this->set_remote_mnet_host_id($formdata->hostid);
                
                    $this->view_header();
                    echo $OUTPUT->heading(format_string($this->checkskill->name));
                    $this->view_tabs($currenttab);

                    // DEBUG
                    // echo "<br />DEBUG :: assigment/type/mahara/assigment.class.php :: 75\n";

                    $query = optional_param('q', null, PARAM_TEXT);
                    // echo "<br />QUERY : $query\n";

                    list($error, $views) = $this->get_views($query);

                    // DEBUG
                    // echo "<br />DEBUG :: assigment/type/mahara/assigment.class.php :: 83<br />ERROR<br />\n";
                    // print_object($error);
                    // echo "<br />VIEWS\n";
                    // print_object($views);
                    // echo "<br />\n";
                    // exit;

                    if ($error) {
                        echo $error;
                    }
                    else {
                        $this->remotehost = $DB->get_record('mnet_host', array('id'=>$this->remote_mnet_host_id()));
                        $this->remotehost->jumpurl = $CFG->wwwroot . '/auth/mnet/jump.php?hostid=' . $this->remotehost->id;
                        echo '<form><div>' . get_string('selectmaharaview', 'checkskill', $this->remotehost) . '</div><br/>'
                          . '<input type="hidden" name="id" value="' . $this->cm->id . '">'
                          . '<label for="q">' . get_string('search') . ':</label> <input type="text" name="q" value="' . $query . '">'
                          . '</form>';
                        if ($views['count'] < 1) {
                            if ($query){
                                echo get_string('noviewsfound', 'checkskill', $this->remotehost->name);
                            } else {
                                echo get_string('noviewscreated', 'checkskill', $this->remotehost->name);
                            }
                        } else {
                            echo '<h4>' . $this->remotehost->name . ': ' . get_string('viewsby', 'checkskill', $views['displayname']) . '</h4>';
                            echo '<table class="formtable"><thead>'
                              . '<tr><th>' . get_string('previewmahara', 'checkskill') . '</th>'
                              . '<th>' . get_string('submit') . '</th></tr>'
                              . '<tr><td style="padding:0 5px 0 5px;">(' . get_string('clicktopreview', 'checkskill') . ')</td>'
                              . '<td style="padding:0 5px 0 5px;">(' . get_string('clicktoselect', 'checkskill') . ')</td></tr>'
                              . '</thead><tbody>';
                            foreach ($views['data'] as &$v) {
                                $windowname = 'view' . $v['id'];
                                $viewurl = $this->remotehost->jumpurl . '&wantsurl=' . urlencode($v['url']);
                                $js = "this.target='$windowname';window.open('" . $viewurl . "', '$windowname', 'resizable,scrollbars,width=920,height=600');return false;";
                                echo '<tr><td><a href="' . $viewurl . '" target="_blank" onclick="' . $js . '">'
                                  . '<img align="top" src="' . $OUTPUT->pix_url('f/html') . '" height="16" width="16" alt="html" /> ' . $v['title'] . '</a></td>'
                                  . '<td><a href="?id=' . $this->cm->id.'&itemid='.$formdata->itemid.'&userid='.$formdata->userid.'&descriptionid='.$formdata->descriptionid.'&hostid='.$this->remotehost->id.'&view=' . $v['id'] . '">' . get_string('submit') . '</a></td></tr>';
                            }
                            echo '</tbody></table>';
                        }
                    }
                    $this->view_footer();
                }
            }
        }
    }




    function set_remote_mnet_host_id($var) {
        $this->remotehostid=$var;
    }

    function remote_mnet_host_id() {
        return $this->remotehostid;
    }

    function get_mnet_sp() {
        global $CFG, $MNET;
        require_once $CFG->dirroot . '/mnet/peer.php';
        $mnet_sp = new mnet_peer();
        $mnet_sp->set_id($this->remote_mnet_host_id());
        return $mnet_sp;
    }

    function submit_view($descriptionid, $hostid, $viewid) {
        global $CFG, $USER, $MNET, $DB;

        require_once $CFG->dirroot . '/mnet/xmlrpc/client.php';
        $mnet_sp = $this->get_mnet_sp();
        $mnetrequest = new mnet_xmlrpc_client();
        $mnetrequest->set_method('mod/mahara/rpclib.php/submit_view_for_assessment');
        $mnetrequest->add_param($USER->username);
        $mnetrequest->add_param($viewid);

        if ($mnetrequest->send($mnet_sp) !== true) {
            return false;
        }
        $data = $mnetrequest->response;

        // DEBUG
        //echo "<br />DEBUG :: ./mod/checkskill/mahara/mahara.php :: 378 :: DATA<br />\n";
        //print_object($data);
        //echo "<br />\n";

        
/****************
        $rawoutcomes = isset($data['outcome']) ? $data['outcome'] : array();

        $mahara_outcomes = array();

        foreach ($rawoutcomes as &$o) {
            $scale1 = array();
            foreach ($o['scale'] as &$item) {
                $scale1[$item['value']] = $item['name'];
            }
            $mahara_outcomes[$o['outcome']][] = array('scale' => $scale1, 'grade' => $o['grade']);
        }

        unset($data['outcome']);
        unset($rawoutcomes);
*************************/

        $document = new object();
        $document->descriptionid=$descriptionid;
        $document->description_document=get_string('viewmahara','checkskill');
        // $document->url_document="http://localhost/moodle2/auth/mnet/jump.php?hostid=".$hostid."&wantsurl=".urlencode($data['url']);
        $document->url_document=$CFG->wwwroot ."/auth/mnet/jump.php?hostid=".$hostid."&wantsurl=".urlencode($data['url']);

        $document->title=clean_text($data['title']);
        $document->target=1;
        $document->timestamp=time();
        // document
        if ($newdocid=$DB->insert_record("checkskill_document", $document)){
            // DEBUG
            $document->id=$newdocid;
        }
        //print_object($document);
        //echo "<br />EXIT :: 410\n";
        //exit;


        // Release Mahara page
        // We don't lock the page ; that's not an assigment stuff
        $mnet_sp = $this->get_mnet_sp();
        $mnetrequest = new mnet_xmlrpc_client();
        $mnetrequest->set_method('mod/mahara/rpclib.php/release_submitted_view');
        $mnetrequest->add_param($data['id']);
        $mnetrequest->add_param($viewid);
        $mnetrequest->add_param($USER->username);
        // Do something if this fails?  Or use cron to export the same data later?
        if ($mnetrequest->send($mnet_sp) === false) {
            $error = "RPC mod/mahara/rpclib.php/release_submitted_view:<br/>";
            foreach ($mnetrequest->error as $errormessage) {
                list($code, $errormessage) = array_map('trim',explode(':', $errormessage, 2));
                $error .= "ERROR $code:<br/>$errormessage<br/>";
            }
        }

        return $newdocid;
    }

    function get_views($query) {
        global $CFG, $USER, $MNET;

        $error = false;
        $viewdata = array();
        if (!is_enabled_auth('mnet')) {
            $error = get_string('authmnetdisabled', 'mnet');
        } else if (!has_capability('moodle/site:mnetlogintoremote', get_context_instance(CONTEXT_SYSTEM), NULL, false)) {
            $error = get_string('notpermittedtojump', 'mnet');
        } else {
            // set up the RPC request
            require_once $CFG->dirroot . '/mnet/xmlrpc/client.php';
            $mnet_sp = $this->get_mnet_sp();
            $mnetrequest = new mnet_xmlrpc_client();
            $mnetrequest->set_method('mod/mahara/rpclib.php/get_views_for_user');
            $mnetrequest->add_param($USER->username);
            $mnetrequest->add_param($query);

            if ($mnetrequest->send($mnet_sp) === true) {
                $viewdata = $mnetrequest->response;
            } else {
                $error = "RPC mod/mahara/rpclib.php/get_views_for_user:<br/>";
                foreach ($mnetrequest->error as $errormessage) {
                    list($code, $errormessage) = array_map('trim',explode(':', $errormessage, 2));
                    $error .= "ERROR $code:<br/>$errormessage<br/>";
                }
            }
        }
        return array($error, $viewdata);
    }


}
