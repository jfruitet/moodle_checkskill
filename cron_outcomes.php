<?php
    // recupere les notes et objectifs en rapport avec les checkskills de competence
    
    
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

      // Exports selected outcomes in CSV format. 

// JF 
// utilise les tables grades_xx et scale_xx

define("CHECKL_MARKING_STUDENT", 0);
define("CHECKL_MARKING_TEACHER", 1);
define("CHECKL_MARKING_BOTH", 2);

define("CHECKL_TEACHERMARK_NO", 2);
define("CHECKL_TEACHERMARK_YES", 1);
define("CHECKL_TEACHERMARK_UNDECIDED", 0);

//define ('CHECKSKILL_DEBUG', 1);    // INACTIVE DEBUG : if set to 1 cron trace many many things and start time very old :))
define("CHECKSKILL_DEBUG", 0); // impact cron to checks outcomes from CHECKSKILL_OUTCOMES_DELAY to CHECKSKILL_OUTCOMES_DELAY weeks
//define("CHECKSKILL_SUPER_DEBUG", 1);     // very verbose
define("CHECKSKILL_SUPER_DEBUG", 0);
define("CHECKSKILL_OUTCOMES_DELAY", 2); // how may days the cron examines the outcomes data
// Increase this value to take into account more former outcomes evaluations

// Selectionner tous les outcomes generes depuis le module checkskill grace a export_grade_outcomes.php
/**
 * return cron timestamp
 *
 */
 // -------------------------------------------------
 function checkskill_get_cron_timestamp(){
	global $DB;
    if ($rec=$DB->get_record('modules', array('name' => 'checkskill'))){
		return $rec->lastcron;
	}
	return 0;
 }

// -------------------------------------------------
function checkskill_cron_outcomes(){
// genere des commentaires a partir des notation par objectif
// sur les activites du cours
global $CFG;
global $DB;
global $scales;
global $OUTPUT; // for icons
$debug=false;
	// all users that are subscribed to any post that needs sending
	$notations = array();
	$scales = array();
	$n_activites=0;

	$timenow   = time();
    $endtime   = $timenow;
	// Record olders than CHECKSKILL_OUTCOMES_DELAY (2) days not examined.
	// This is to avoid the problem where cron has not been running for a long time
    // Enregistrements anterieurs a CHECKSKILL_OUTCOMES_DELAY (2) jours non traites.
	if (CHECKSKILL_DEBUG){
    	$starttime = $endtime - CHECKSKILL_OUTCOMES_DELAY * 7 * 24 * 3600;   // n weeks earlier
	}
	else{
    	$starttime = $endtime - CHECKSKILL_OUTCOMES_DELAY * 24 *  3600;  // n days earlier
	}

	$cron_timestamp=checkskill_get_cron_timestamp();

	if (!empty($cron_timestamp)){
		if (CHECKSKILL_DEBUG){
        	$starttime = min($starttime, $cron_timestamp-60);
		}
		else{
        	$starttime = max($starttime, $cron_timestamp-60);
		}
	}

	$scales_list = '';     // for items with a scaleid

	// users
	$users = array();
  	$users_list = '';

  	// DEBUG
  	mtrace("\nCRON CHECKSKILL OUTCOMES.");
  	mtrace("\nSTART TIME : ".date("Y/m/d H:i:s",$starttime)." END TIME :  ".date("Y/m/d H:i:s",$endtime));


  	$notations=checkskill_get_outcomes($starttime, $endtime);
  	//$notations=checkskill_get_outcomes_old($endtime);
	if ($notations){
		foreach($notations as $notation){
      		if ($notation){
        
        		if ($debug || CHECKSKILL_SUPER_DEBUG){
          			mtrace("\nDEBUG :: grade/cron_outcomes.php Line 102 :: USERID ".$notation->userid." ; COURSEID ".$notation->courseid."\nNOTATION :\n");
          			print_r($notation);
        		}
        
        		if (!empty($notation->scaleid) && !preg_match("/ ".$notation->scaleid."\,/", $scales_list)){
          			$scales_list .= " $notation->scaleid,";
        		}

        		if (!empty($notation->userid) && !empty($notation->courseid)){
                    if (!preg_match("/ ".$notation->userid."\,/", $users_list)){
                        $users_list .= " $notation->userid,";
                        $user=new Object();
                        $user->userid = $notation->userid;
                        $user->courses = array();
                        $user->course_list = '';
                        $users[$notation->userid]=$user;
                    }

                    if (!preg_match("/ ".$notation->courseid."\,/", $users[$notation->userid]->course_list)){
                        $users[$notation->userid]->course_list .= " $notation->courseid,";
                        $course=new Object();
                        $course->courseid = $notation->courseid;
                        $course->checkskill_list = '';
                        $course->checkskills = array();
                        $users[$notation->userid]->courses[$notation->courseid] = $course;
                    }

                    if (!preg_match("/ ".$notation->checkskillid."\,/", $users[$notation->userid]->courses[$notation->courseid]->checkskill_list)){
                            $users[$notation->userid]->courses[$notation->courseid]->checkskill_list .= " $notation->checkskillid,";
                            $checkskill = new object();
                            $checkskill->checkskillid = $notation->checkskillid;
                            $checkskill->module_list ='';
                            $checkskill->modules = array();
                            // $checkskill->checkskills[$notation->checkskillid]->outcome_list = '';
                            $users[$notation->userid]->courses[$notation->courseid]->checkskills[$notation->checkskillid]=$checkskill;
                    }
/*

                    if (!preg_match("/ ".$notation->itemid."\,/", $users[$notation->userid]->courses[$notation->courseid]->checkskill_list[$notation->checkskillid]->itemid_list)){
                            $users[$notation->userid]->courses[$notation->courseid]->checkskill[$notation->checkskillid]->itemid_list .= " $notation->itemid,";
                            $item = new object();
                            $item->itemid = $notation->itemid;
                            $item->module_list ='';
                            $item->modules = array();
                            // $checkskill->checkskills[$notation->checkskillid]->outcome_list = '';
                            $users[$notation->userid]->courses[$notation->courseid]->checkskills[$notation->checkskillid]->item[$notation->itemid]=$item;
                    }
*/

                    if ((empty($users[$notation->userid]->courses[$notation->courseid]->checkskills[$notation->checkskillid]->module_list))
                            || (!preg_match("/ ".$notation->module.":".$notation->moduleinstance."\,/", $users[$notation->userid]->courses[$notation->courseid]->checkskills[$notation->checkskillid]->module_list))){
                            $users[$notation->userid]->courses[$notation->courseid]->checkskills[$notation->checkskillid]->module_list .= " $notation->module:$notation->moduleinstance,";
                            $module = new object();
                            $module->modulename = $notation->module;
                            $module->moduleinstance = $notation->moduleinstance;
                            $module->teacherid = $notation->teacherid;    // MODIF JF 2012/01/31
                            $module->itemid_list='';
							$module->outcome_list='';
                            $module->scaleid_list='';
                            $module->timemodified_list='';
                            $users[$notation->userid]->courses[$notation->courseid]->checkskills[$notation->checkskillid]->modules[$notation->moduleinstance]=$module;
                    }

                    if (!empty($users[$notation->userid]->courses[$notation->courseid]->checkskills[$notation->checkskillid]->modules[$notation->moduleinstance])){
                            // les notes
                            if ($notation->outcomeshortname!=''){
                                $users[$notation->userid]->courses[$notation->courseid]->checkskills[$notation->checkskillid]->modules[$notation->moduleinstance]->itemid_list.=" $notation->itemid,";
                                $users[$notation->userid]->courses[$notation->courseid]->checkskills[$notation->checkskillid]->modules[$notation->moduleinstance]->outcome_list.=" $notation->outcomeshortname:$notation->finalgrade,";
                                $users[$notation->userid]->courses[$notation->courseid]->checkskills[$notation->checkskillid]->modules[$notation->moduleinstance]->scaleid_list.=" $notation->scaleid,";
                                $users[$notation->userid]->courses[$notation->courseid]->checkskills[$notation->checkskillid]->modules[$notation->moduleinstance]->timemodified_list.=" $notation->timemodified,";
                            }
                    }
        		}
      		}
    	}
  	}
  
  
  	if (!empty($users)){
    	// DEBUG
    	if ($debug || CHECKSKILL_SUPER_DEBUG){
    		mtrace("\nDEBUG :: grade/cron_outcomes.php Line 171 :: USERS \n");
    		print_r($users);
    	}

  
    	foreach($users as $user) {
        	if ($debug || CHECKSKILL_SUPER_DEBUG){
            	mtrace("\nDEBUG :: grade/cron_outcomes.php Line 178 :: USER \n");
            	print_r($user);
        	}
        
        	foreach($user->courses as $course){
            	// echo "<br />COURSE_ID $course->courseid; \n";
            	foreach($course->checkskills  as $checkskill){
                	// echo "<br />REFERENTIEL_INSTANCE $checkskill->checkskill_checkskillid; REFERENTIEL_ID $checkskill->itemid\n";

                	// MODIF JF 2013/08/05
					// bareme
                	$threshold=-1;

					if (!empty($CFG->checkskill_use_scale)){
						if ($bareme=checkskill_get_bareme_checkskill($checkskill->itemid)){
                    		$threshold = $bareme->threshold;
						}
					}

                	foreach($checkskill->modules as $module){
                    	// echo "<br />MODULE $module->modulename ; Instance $module->moduleinstance ; \n";
                    	// preparer l'enregistrement
                    	// DEBUG
                    	// echo "<br />DEBUG :: 180 ; MODULE : $module->modulename, INSTANCE : $module->moduleinstance, COURS : $course->courseid\n";
                    	if ($module && !empty($module->modulename) && !empty($module->moduleinstance) && !empty($course->courseid)){
                        	$m = checkskill_get_module_info($module->modulename, $module->moduleinstance, $course->courseid);
/*
              // module
  $m->id;
  $m->type=$modulename;
  $m->instance=$moduleinstance;
  $m->course=$courseid;
  $m->date=$cm->added;
  $m->userdate=userdate($cm->added);
  $m->ref_activite=$mid;
  $m->name=$mname;
  $m->description=$mdescription;
  $m->link=$mlink;
*/
                        	// DEBUG
		                	if ($debug || CHECKSKILL_SUPER_DEBUG){
                            	mtrace("\nDEBUG :: grade/cron_outcomes.php Line 184 :: MODULE \n");
								print_r($m);
                        	}
		/****
                        	$record->text;
                        	$record->checkskillid
                        	$record->itemid
                        	$record->courseid
                        	$record->userid
                        	$record->teacherid
                            $record->teachermark
                            $record->teachertimestamp

		**********/

                        	$record= new Object();
                        	$record->checkskillid=$checkskill->checkskillid;
                            $record->courseid=$course->courseid;
                        	$record->userid=$user->userid;
                        	$record->teacherid=$module->teacherid;
                            $record->teachertimestamp=$m->date;
                            $record->teachermark=0;
                            $record->text='';
                            $record->bareme_list='';

                        	// $record->text='['.get_string('outcome_type', 'checkskill').' '.get_string('modulename', $m->type).' '.$m->ref_activite.'] '.get_string('outcome_date','checkskill').' '.$m->userdate;
                        	// $record->text.=get_string('outcome_description','checkskill', $m);

                            // add follow_link icon
							// $record->text.='[<a href="'.$m->link.'">'.get_string('modulename', $m->type).' N '.$m->id
							//                            .' <img src="'.$OUTPUT->pix_url('follow_link','checkskill').'" alt="'.get_string('linktomodule','checkskill').'" />
							// </a> '.$m->userdate.'] '.$m->name;
                            $record->text.='[<a href="'.$m->link.'"><img src="'.$OUTPUT->pix_url('follow_link','checkskill').'" alt="'.get_string('linktomodule','checkskill').'" />
 </a> <span class="small">'.$m->userdate.'</span>] ';


                    		// DEBUG

                    		if ($debug || CHECKSKILL_SUPER_DEBUG){
                        		mtrace("DEBUG :: cron_outcomes.php Line 257\nTIMEMODIFIED_LIST\n$module->timemodified_list\n");
                                mtrace("OUTCOME_LIST\n$module->outcome_list\n");
                                mtrace("SCALE_LIST\n$module->scaleid_list\n");
                                mtrace("ITEMID_LIST\n$module->itemid_list\n");
                    		}

                        	$t_datemodif=explode(',',$module->timemodified_list);
                        	sort($t_datemodif);
                        	$imax=count($t_datemodif)-1;
                        	$timemodified=$t_datemodif[$imax];
                        	if ($timemodified>$record->teachertimestamp){
                            	$record->teachertimestamp=$timemodified;
                        	}
              
                            // echo "<br />ITEMID_LIST $module->itemid_list\n";
                        	$t_itemids=explode(',',$module->itemid_list);

                        	// echo "<br />SCALE_LIST $module->scaleid_list\n";
                        	$t_scales=explode(',',$module->scaleid_list);

                        	// echo "<br />OUTCOME_LIST $module->outcome_list\n";
                        	$t_outcomes=explode(',',$module->outcome_list);

                        	$n=count($t_outcomes);
                        	if ($n>0){
                            	$i=0;
                            	if ($debug || CHECKSKILL_SUPER_DEBUG){
									mtrace ("\nDEBUG :: cron_outcomes.php Line 277\n");
                            	}

								while ($i<$n){
                                	if ($t_outcomes[$i]!=''){
                                    	list($cle, $val)=explode(':',$t_outcomes[$i]);
	                                    $cle=trim($cle);
    	                                $val=trim($val);

                                        $record->itemid=trim($t_itemids[$i]);

										$scaleid=trim($t_scales[$i]);
            	                        if ($debug || CHECKSKILL_SUPER_DEBUG){
											mtrace ("\nCODE : $cle ; VALEUR : $val ;");
                    	                }
										if ($threshold==-1){
											$scale  = checkskill_get_scale($scaleid);
                                	        if ($scale){
												$threshold=$scale->grademax;
											}
                                    	}
                                    	if ($val>=$threshold){     // baremes
                                        	if ($debug || CHECKSKILL_SUPER_DEBUG){
												mtrace (" ---> VALIDE \n");
											}
                                            $record->teachermark=CHECKL_TEACHERMARK_YES;
                                  		}
										else{
                                            $record->teachermark=CHECKL_TEACHERMARK_NO;
										}
                                		$record->bareme_list.=$cle.':'.(int)$val.'/';
                                	}
									// Sauvegarder
                                    if (checkskill_set_outcomes($record)){
                                        $n_activites++;
									}
                            		$i++;
								}
                        	}
                    	}
                	}
            	}
        	}
    	}
	}

  	// echo "<br />\n";
  	mtrace($n_activites.' COMMENTS & CHECKS CREATED OR MODIFIED.');
  	mtrace('END CRON CHECKSKILL OUTCOMES.');
    return($n_activites);
}


// -------------------------------------------------
function checkskill_get_scale($scaleid){
  // Preload scale objects for items with a scaleid
  global $scales;
  global $DB;
  if ($scaleid){
    if (!empty($scales[$scaleid])){
      // echo "<br />DEBUG :: 211 SCALE\n";
      return $scales[$scaleid];
    }    
    else {  
      $scale_r = $DB->get_record("scale", array("id" => "$scaleid"));
      if ($scale_r){
        $scale = new Object();
        $scale->scaleid = $scaleid;
        $scale->scale = $scale_r->scale;
        $tscales=explode(',',$scale_r->scale);
        // reindex because scale is off 1
        // MDL-12104 some previous scales might have taken up part of the array
        // so this needs to be reset  
        $scale->scaleopt = array();
        $i = 0;
        foreach ($tscales as $scaleoption) {
          $i++;
          $scale->scaleopt[$i] = trim($scaleoption);
        }
        $scale->grademin=1;
        $scale->grademax=$i;
        $scales[$scaleid]=$scale;
        return $scale;      
      }
    }
  }
  return NULL;
}

// -------------------------------------------------
function checkskill_get_module_info($modulename, $moduleinstance, $courseid){
// retourne les infos concernant ce module
global $CFG;
global $DB;
  if (! $course = $DB->get_record("course", array("id" => "$courseid"))) {;
    // error("DEBUG :: checkskill_get_module_info :: This course doesn't exist");
    return false;
  }
  if (! $module = $DB->get_record("modules", array("name" => "$modulename"))) {
    // error("DEBUG :: checkskill_get_module_info :: This module type doesn't exist");
    return false;
  }
  if (! $cm = $DB->get_record("course_modules", array("course" => "$course->id", "module" => "$module->id", "instance" => "$moduleinstance"))) {
    // error("DEBUG :: checkskill_get_module_info :: This course module doesn't exist");
    return false;
  }

  $mid=0;
  $mname='';
  $mdescription='';
  $mlink='';

  if ($modulename=='forum'){
    if (! $forum = $DB->get_record("forum", array("id" => "$cm->instance"))) {
      // error("DEBUG :: checkskill_get_module_info :: This forum module doesn't exist");
      return false;
    }
    $mid=$forum->id;
    $mname=$forum->name;
    $mdescription=$forum->intro;
    $mlink = $CFG->wwwroot.'/mod/forum/view.php?f='.$forum->id;
  }
  elseif ($modulename=='assign'){
    if (! $assign= $DB->get_record("assign", array("id" => "$cm->instance"))) {
      // error("DEBUG :: checkskill_get_module_info :: This assignment doesn't exist");
      return false;
    }
    $mid=$assign->id;
    $mname=$assign->name;
    $mdescription=$assign->intro;
    $mlink = $CFG->wwwroot.'/mod/assign/view.php?id='.$cm->id;
  }
  elseif ($modulename=='assignment'){
    if (! $assignment = $DB->get_record("assignment", array("id" => "$cm->instance"))) {
      // error("DEBUG :: checkskill_get_module_info :: This assignment doesn't exist");
      return false;
    }
    $mid=$assignment->id;
    $mname=$assignment->name;
    $mdescription=$assignment->intro;
    $mlink = $CFG->wwwroot.'/mod/assignment/view.php?id='.$cm->id;
  }
  elseif ($modulename=='chat'){
    if (! $chat = $DB->get_record("chat", array("id" => "$cm->instance"))) {
      //error("DEBUG :: checkskill_get_module_info :: This chat doesn't exist");
      return false;
    }
    $mid=$chat->id;
    $mname=$chat->name;
    $mdescription=$chat->intro;
    $mlink = $CFG->wwwroot.'/mod/chat/view.php?id='.$cm->id;
  }
  elseif ($modulename=='choice'){
    if (! $choice = $DB->get_record("choice", array("id" => "$cm->instance"))) {
      // error("DEBUG :: checkskill_get_module_info :: This choice module doesn't exist");
      return false;
    }
    $mid=$choice->id;
    $mname=$choice->name;
    $mdescription=$choice->intro;
    $mlink = $CFG->wwwroot.'/mod/choice/view.php?id='.$cm->id;
  }
  elseif ($modulename=='data'){
    if (! $data = $DB->get_record("data", array("id" => "$cm->instance"))) {
      // error("DEBUG :: checkskill_get_module_info :: This data module doesn't exist");
      return false;
    }
    $mid=$data->id;
    $mname=$data->name;
    $mdescription=$data->intro;
    $mlink = $CFG->wwwroot.'/mod/data/view.php?id='.$cm->id;

// http://tracker.moodle.org/browse/MDL-15566
// Notice: Undefined property: stdClass::$cmidnumber in C:\xampp\htdocs\moodle_dev\mod\data\lib.php on line 831
  }
  elseif ($modulename=='glossary'){
    if (! $glossary = $DB->get_record("glossary",array("id" => "$cm->instance"))) {
      print_error("DEBUG :: checkskill_get_module_info :: This glossary module doesn't exist");
    }
    $mid=$glossary->id;
    $mname=$glossary->name;
    $mdescription=$glossary->intro;
    $mlink = $CFG->wwwroot.'/mod/glossary/view.php?id='.$cm->id;
  }
  else{
    // tentative pour un module generique
    if (! $record_module = $DB->get_record($module->name,array("id" => "$cm->instance"))) {
      // error("DEBUG :: checkskill_get_module_info :: This ".$module->name." module doesn't exist");
      return false;
    }
    $mid=$record_module->id;
    $mname=$record_module->name;
    if (isset($record_module->intro)){
      $mdescription=$record_module->intro;
    }
    else if (isset($record_module->info)){
      $mdescription=$record_module->info;
    }
    else if (isset($record_module->description)){
      $mdescription=$record_module->description;
    }
    else if (isset($record_module->text)){
      $mdescription=$record_module->text;
    }
    else{
      $mdescription=get_string('unknowdescription','checkskill');
    }
    $mlink = $CFG->wwwroot.'/mod/'.$modulename.'/view.php?id='.$cm->id;
  }

  $m=new Object();
  $m->id=$mid;
  $m->type=$modulename;
  $m->instance=$moduleinstance;
  $m->course=$courseid;
  $m->date=$cm->added;
  $m->userdate=userdate($cm->added);
  $m->ref_activite=$mid;
  $m->name=$mname;
  $m->description=strip_tags($mdescription);
  $m->link=$mlink;

  return $m;
}

// ------------------------------------------
function checkskill_user($user_id) {
// retourne le NOM prenom Ã Â  partir de l'id
global $DB;
	$user_info="";
	if (!empty($user_id)){
        $params=array("userid" => "$user_id");
		$sql = "SELECT firstname, lastname FROM {user}  WHERE id = :userid ";
		$user = $DB->get_record_sql($sql, $params);
		if ($user){
			$user_info=mb_convert_case($user->firstname, MB_CASE_TITLE, 'UTF-8').' '.mb_strtoupper($user->lastname,'UTF-8');
		}
	}
	return $user_info;
}

// ------------------
function checkskill_url_file($afile) {
	global $CFG;
	// retourne le chemin du fichier
    $fullpath = '/'.$afile->contextid.'/'.$afile->component.'/'.$afile->filearea.'/'.$afile->itemid.$afile->filepath.$afile->filename;
    return(new moodle_url($CFG->wwwroot.'/pluginfile.php'.$fullpath));
}

// ------------------
function checkskill_get_mahara_link($maharalink) {     // Portland version assign mahara plugin
	// retourne l'url du lien
	return(new moodle_url('/auth/mnet/jump.php', array('hostid'=>$maharalink->host, 'wantsurl'=>$maharalink->url)));
}


// -------------------------------------------------
function checkskill_get_assign($m, $userid){
/*
  $m->id;
  $m->type=$modulename;
  $m->instance=$moduleinstance;
  $m->course=$courseid;
  $m->date=$cm->added;
  $m->userdate=userdate($cm->added);
  $m->ref_activite=$mid;
  $m->name=$mname;
  $m->description=$mdescription;
  $m->link=$mlink;

mdl_assign_plugin_config

*/
global $DB;
$debug=false;
    $mdata=new Object();
    $mdata->submission='';
    $mdata->comment=array();
    $mdata->feedback='';
    $mdata->file=array();
    $mdata->link=array();  // array of assign mahara plugin object
    
	if ($m){
        if ($debug || CHECKSKILL_SUPER_DEBUG){
        	mtrace("\nDEBUG :: grade/cron_outcomes.php Line 371 ; USER : $userid \nASSIGN MODULE\n");
			print_r($m);
		}

		// rechercher le type
		$assign_plugins = $DB->get_records("assign_plugin_config", array("assignment" => $m->id));
		if ($assign_plugins){
			/*
plugin subtype name value
onlinetext assignsubmission enabled 0|1
file assignsubmission enabled 0|1
file assignsubmission maxfilesubmissions 2
file assignsubmission maxsubmissionsizebytes 0
comments assignsubmission enabled 0|1
comments assignfeedback enabled 0|1
offline assignfeedback enabled 0|1
file assignfeedback enabled 0|1
Lancaster version

// Lancaster  version
--
-- Structure de la table `mdl_assignsubmission_mahara`
--

CREATE TABLE IF NOT EXISTS `mdl_assignsubmission_mahara` (
  `id` bigint(10) NOT NULL AUTO_INCREMENT,
  `assignment` bigint(10) NOT NULL DEFAULT '0',
  `submission` bigint(10) NOT NULL DEFAULT '0',
  `viewid` bigint(10) NOT NULL,
  `viewurl` longtext COLLATE utf8_unicode_ci,
  `viewtitle` longtext COLLATE utf8_unicode_ci,
  `viewaccesskey` longtext COLLATE utf8_unicode_ci,
  PRIMARY KEY (`id`),
  KEY `mdl_assimaha_ass_ix` (`assignment`),
  KEY `mdl_assimaha_sub_ix` (`submission`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Data for Mahara submission' AUTO_INCREMENT=2 ;

--
-- Contenu de la table `mdl_assignsubmission_mahara`
--

INSERT INTO `mdl_assignsubmission_mahara` (`id`, `assignment`, `submission`, `viewid`, `viewurl`, `viewtitle`, `viewaccesskey`) VALUES
(1, 1, 1, 27, '/view/view.php?id=27&mt=Mr7cuhqLkgFR8jvSJPpO', 'Enfants', 'Mr7cuhqLkgFR8jvSJPpO');

id	assignment	plugin	subtype	name	value
1	1	mahara	assignsubmission	enabled	0|1
2	1	mahara	assignsubmission	mnethostid	3


// Portland version assign mahara plugin
mahara	assignsubmission	enabled	0|1
mahara	assignsubmission	mahara_host	3
mahara assignfeedback enabled 0|1

http://localhost/moodle24/mod/assign/view.php?id=51&rownum=2&action=grade#
--
-- Structure de la table `mdl24_mahara_portfolio`
--

CREATE TABLE IF NOT EXISTS `mdl24_mahara_portfolio` (
  `id` bigint(10) NOT NULL AUTO_INCREMENT,
  `page` bigint(10) NOT NULL,
  `host` bigint(10) NOT NULL,
  `userid` bigint(10) NOT NULL,
  `title` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `url` longtext COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `mdl24_mahaport_paghos_uix` (`page`,`host`),
  KEY `mdl24_mahaport_pag_ix` (`page`),
  KEY `mdl24_mahaport_paguse_ix` (`page`,`userid`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='A table containing Mahara portfolios' AUTO_INCREMENT=3 ;

--
-- Contenu de la table `mdl24_mahara_portfolio`
--

INSERT INTO `mdl24_mahara_portfolio` (`id`, `page`, `host`, `userid`, `title`, `url`) VALUES
(1, 17, 3, 4, 'Hiver arrive', '/view/view.php?id=17&mt=y6gBTQ0rtp9cUfKlaoVE'),
(2, 22, 3, 3, 'Enfants', '/view/view.php?id=22&mt=JG7YCHagL91XfuwP3hQZ');

			*/
			foreach ($assign_plugins as $ap){
				// DEBUG
                if ($debug || CHECKSKILL_SUPER_DEBUG){
                    mtrace("\nDEBUG :: grade/cron_outcomes.php Line 394 ; ASSIGN PLUGIN\n");
					print_r($ap);
				}
				if (($ap->plugin=='onlinetext') && ($ap->subtype=='assignsubmission') && ($ap->name=='enabled')  && ($ap->value=='1')){
					// recuperer le texte soumis
                    if ($as = $DB->get_record("assign_submission", array("assignment" => $m->id, "userid" => $userid))){
                        if ($onlinetext = $DB->get_record("assignsubmission_onlinetext", array("assignment" => $m->id, "submission" => $as->id))){
			            	if ($debug || CHECKSKILL_SUPER_DEBUG){
                    			mtrace("\nDEBUG :: grade/cron_outcomes.php Line 402 ; ASSIGN ONLINETEXT\n");
								print_r($as);
							}
							$mdata->submission.=strip_tags($onlinetext->onlinetext);
						}
					}
				}
				
				// Assign mahara plugin
				if (($ap->plugin=='mahara') && ($ap->subtype=='assignsubmission') && ($ap->name=='enabled')  && ($ap->value=='1')){
					// recuperer le lien soumis
                    if ($as = $DB->get_record("assign_submission", array("assignment" => $m->id, "userid" => $userid))){
                        // Lancaster version  assign mahara plugin
                        if ($dbman = $DB->get_manager()){ // loads ddl manager and xmldb classes
                            $table = new xmldb_table("assignsubmission_mahara");
		                    if ($dbman->table_exists($table)){
                                if ($maharaobject = $DB->get_record("assignsubmission_mahara", array("assignment" => $m->id, "submission" => $as->id))){ 
                                // Look for hostid                                                                        plugin	subtype	name
                                    if ($apmaharahost = $DB->get_record("assign_plugin_config", array("assignment" => $m->id, "plugin" => "mahara", "subtype" => "assignsubmission", "name" => "mnethostid" ))){
                                        $maharalink= new Object();
                                        $maharalink->page=$maharaobject->viewid;
                                        $maharalink->host=$apmaharahost->value;
                                        $maharalink->userid=$userid;
                                        $maharalink->title=$maharaobject->viewtitle;
                                        $maharalink->url=$maharaobject->viewurl;
                                                           
                                        $mdata->link[]=$maharalink;
                                    }                                                                                                               
				        		}
                            }
                        }
                        
                        // Portland version assign mahara plugin
                        if ($dbman = $DB->get_manager()){ // loads ddl manager and xmldb classes
                            $table = new xmldb_table("assign_mahara_submit_views");
		                    if ($dbman->table_exists($table)){
                                if ($maharaview = $DB->get_record("assign_mahara_submit_views", array("assignment" => $m->id, "submission" => $as->id))){
                                    if ($maharalink = $DB->get_record("mahara_portfolio", array("id" => $maharaview->portfolio, "userid" => $userid))){
                                        $mdata->link[]=$maharalink;
                                    }
                                }
                            }
                        }
                    }                        
				}

				if (($ap->plugin=='comments') && ($ap->subtype=='assignsubmission') && ($ap->name=='enabled')  && ($ap->value=='1')){
					// recuperer les commentaires soumis
					if ($as = $DB->get_record("assign_submission", array("assignment" => $m->id, "userid" => $userid))){
                    	if ($comments = $DB->get_records("comments", array("commentarea" => "submission_comments", "itemid" => $as->id))){
			            	if ($debug || CHECKSKILL_SUPER_DEBUG){
                    			mtrace("\nDEBUG :: grade/cron_outcomes.php Line 413 ; ASSIGN COMMENTS\n");
								print_r($comments);
							}
							foreach ($comments as $comment){
                        		if (!empty($comment)) {
        							$mdata->comment[]=get_string('commentby','checkskill'). checkskill_user($comment->userid). ' ('.userdate($comment->timecreated).') : '.strip_tags($comment->content);
								}
							}
						}
					}
				}

				if (($ap->plugin=='comments') && ($ap->subtype=='assignfeedback') && ($ap->name=='enabled')  && ($ap->value=='1')){
					// recuperer le feedback soumis par l'enseignant
                    if ($ag = $DB->get_record("assign_grades", array("assignment" => $m->id, "userid" => $userid))){
                        if ($feedback = $DB->get_record("assignfeedback_comments", array("assignment" => $m->id, "grade" => $ag->id))){
			            	if ($debug || CHECKSKILL_SUPER_DEBUG){
                    			mtrace("\nDEBUG :: grade/cron_outcomes.php Line 430 ; ASSIGN FEEDBACK\n");
								print_r($feedback);
							}
							$mdata->feedback=strip_tags($feedback->commenttext);
						}
					}
				}

                if (($ap->plugin=='file') && ($ap->subtype=='assignsubmission') && ($ap->name=='enabled')  && ($ap->value=='1')){
					// recuperer le fichier soumis par l'etudiant
                    if ($as = $DB->get_record("assign_submission", array("assignment" => $m->id, "userid" => $userid))){
                        if ($af = $DB->get_record("assignsubmission_file", array("assignment" => $m->id, "submission" => $as->id))){
			                if ($debug || CHECKSKILL_SUPER_DEBUG){
                    			mtrace("\nDEBUG :: grade/cron_outcomes.php Line 442 ; ASSIGN FILE\n");
								print_r($af);
							}
							if ($af->numfiles>0){
								// recuperer l'url du fichier
                                if ($files = $DB->get_records("files", array("component" => "assignsubmission_file", "filearea" => "submission_files", "itemid" => $as->id, "userid" => $userid))){
					                if ($debug || CHECKSKILL_SUPER_DEBUG){
        		            			mtrace("\nDEBUG :: grade/cron_outcomes.php Line 449 ; FILES\n");
										print_r($files);
									}
									foreach ($files as $afile){
                                        if (!empty($afile)) {
											$mdata->file[]=$afile;
										}
									}
								}
							}
						}
					}
				}
			}
		}
	}
	if ($debug || CHECKSKILL_SUPER_DEBUG){
		mtrace("\nDEBUG :: grade/cron_outcomes.php Line 467 ; MDATA\n");
		print_r($mdata);
	}

    return ($mdata);
}


/**
 * input : starttime, endtime : fenetre d'exploration
 * output : notation array
 */
// -------------------------------------------------
function checkskill_get_outcomes($starttime, $endtime){
// genere le liste des notations
global $CFG;
global $DB;
$debug=false;
	$notations=array();
	// selection all checkskill
	// SELECT mdl_checkskill_item.id AS itemid, mdl_checkskill_item.displaytext AS displaytext, mdl_checkskill.id AS checkskillid, mdl_checkskill.course AS courseid FROM mdl_checkskill, mdl_checkskill_item WHERE mdl_checkskill.id=mdl_checkskill_item.checkskill ORDER BY mdl_checkskill.course ASC, mdl_checkskill.id ASC, mdl_checkskill_item.displaytext ASC
    $params=array();
	$sql = "SELECT {checkskill_item}.id AS itemid,
        {checkskill_item}.displaytext AS displaytext,
        {checkskill}.id AS checkskillid,
        {checkskill}.course AS courseid
  FROM {checkskill}, {checkskill_item}
  WHERE {checkskill}.id={checkskill_item}.checkskill
  ORDER BY {checkskill}.course ASC, {checkskill}.id ASC, {checkskill_item}.displaytext ASC ";
	if (false && CHECKSKILL_SUPER_DEBUG){
  		mtrace("\nDEBUG :: ./mod/checkskill/cron_outcomes.php :: Line 806\n");
  		mtrace("Fonction checkskill_get_outcomes \n");
		mtrace("SQL:$sql\n");
  	}
	$r_checkskills=$DB->get_records_sql($sql, $params);

	if ($r_checkskills){
		foreach($r_checkskills as $r_checkskill){            // Pour tous les checkskill_items
      		// DEBUG
      		if (false && CHECKSKILL_SUPER_DEBUG){
        		mtrace("DEBUG :: Line 815 :: CHECKSKILLS\n");
        		print_r($r_checkskill);
      		}

			if (false && CHECKSKILL_SUPER_DEBUG){
  				mtrace("\nDEBUG :: Line 821\n");
                mtrace("\nDISPLAYTEXT:$r_checkskill->displaytext\n");
	  		}

			$item_outcome=new Object();
            $item_outcome->itemid=$r_checkskill->itemid;
            $item_outcome->checkskillid=$r_checkskill->checkskillid;
            $item_outcome->courseid=$r_checkskill->courseid;
            $item_outcome->outcome='';
            $item_outcome->code_referentiel='';
            $item_outcome->code_competence='';

			// First extrac outcomes from items list
            // Searched matches
            // C2i2e-2011 A.1-1 :: Identifier les personnes resso...
            if (preg_match('/(.*)::(.*)/i', $r_checkskill->displaytext, $matches)){
                // DEBUG
                if   ($debug || CHECKSKILL_SUPER_DEBUG){
                    mtrace("DEBUG :: ./mod/checkskill/cron_outcomes.php\nLine 839 :: MATCHES\n");
                    print_r($matches);
                }
                if ($matches[1]){
                    $item_outcome->outcome=trim($matches[1]);
                    if ($keywords = preg_split("/[\s]+/",$matches[1],-1,PREG_SPLIT_NO_EMPTY)){
                        if ($debug || CHECKSKILL_SUPER_DEBUG){
                            mtrace("DEBUG :: ./mod/checkskill/cron_outcomes.php\nLine 846 :: KEYWORDS\n");
                            print_r($keywords);
                        }
                        if ($keywords[0]){
                            $item_outcome->code_referentiel=trim($keywords[0]);
                        }
                        if ($keywords[1]){
                            $item_outcome->code_competence=trim($keywords[1]);
                        }
                    }
                }
                // DEBUG
                if   ($debug || CHECKSKILL_SUPER_DEBUG){
                    mtrace("DEBUG :: ./mod/checkskill/cron_outcomes.php\nLine 859 :: ITEM_OUTCOME\n");
                    print_r($item_outcome);
                }
            }

            if (!empty($item_outcome->code_referentiel)){    // A REVISER si on veut charger d'autres outcomes dans CheckSkill
				// rechercher les outcomes correspondant...
 /*
   SELECT id, shortname, fullname, scaleid
      FROM mdl_grade_outcomes
      WHERE fullname LIKE 'C2i1 D1.1.1%'
      ORDER BY fullname ASC
 */
                $params=array("fullname" => "$item_outcome->outcome%") ;
      			$sql = "SELECT id, shortname, fullname, scaleid
      FROM {grade_outcomes}
      WHERE fullname LIKE :fullname
      ORDER BY fullname ASC ";
               	// DEBUG
                if ($debug || CHECKSKILL_SUPER_DEBUG){
		  			mtrace("\nDEBUG :: Line 879\n");
        		    print_r($params);
					mtrace("\nSQL:$sql\n");
  				}

				$r_outcomes=$DB->get_records_sql($sql, $params);
        		if ($debug || CHECKSKILL_SUPER_DEBUG){
  					mtrace("\nDEBUG :: Line 886 :: R_OUTCOMES \n");
	            	print_r($r_outcomes);
					mtrace("\n");
        		}

				if ($r_outcomes){
					$t_outcomes=array();  // liste des outcomes

		        	foreach ($r_outcomes as $r_outcome){
		        		if ($debug || CHECKSKILL_SUPER_DEBUG){
  							mtrace("\nDEBUG :: Line 896 :: R_OUTCOME\n");
            				print_r($r_outcome);
							mtrace("\n");
        				}

						// selectionner les items (activites) utilisant ces outcomes
						$a = new Object();
		                $a->outcomeid=$r_outcome->id;
        		        $a->shortname=$r_outcome->shortname;
                		$a->scaleid=$r_outcome->scaleid;
		                $t_outcomes[$r_outcome->id]=$a;
					}
		        	if ($debug || CHECKSKILL_SUPER_DEBUG){
  						mtrace("\nDEBUG :: Line 909\n");
            			print_r($t_outcomes);
						mtrace("\n");
        			}

					$where1='';
					$params1=array();
   		    		$params1[]='';  // item module not null
					if (!empty($t_outcomes)){
						// Table  grade_items
		        		if ($debug || CHECKSKILL_SUPER_DEBUG){
  							mtrace("\nDEBUG :: Line 920\n");
            				print_r($t_outcomes);
							mtrace("\n");
        				}
						foreach ($t_outcomes as $outcome) {
				        	if ($debug || CHECKSKILL_SUPER_DEBUG){
  								mtrace("\nDEBUG :: Line 926 :: OUTCOME \n");
            					print_r($outcome);
								mtrace("\n");
		        			}
							if (!empty($outcome->outcomeid)){
                   				$params1[]=$r_checkskill->courseid;
		                    	$params1[]=$outcome->outcomeid;
        		                $params1[]=$outcome->scaleid;      // jointure scaleid identique
								if (!empty($where1)){
									$where1=$where1 . ' OR ((courseid=?) AND (outcomeid=?) AND (scaleid=?)) ';
								}
								else {
        	    		    		$where1=' ((courseid=?) AND (outcomeid=?) AND (scaleid=?)) ';
								}
							}
						}

    	    			if (!empty($where1)){
							$where1=' (itemmodule != ?) AND ('. $where1 . ')';
        		            $sql1='SELECT id, courseid, itemmodule, iteminstance, grademin, grademax, scaleid FROM {grade_items} WHERE '.$where1.' ORDER BY iteminstance, outcomeid  ';

							if ($debug || CHECKSKILL_SUPER_DEBUG){
  								mtrace("\nDEBUG :: Line 948 :: PARAMETRES REQUETE \n");
								print_r($params1);
								mtrace("\nSQL : $sql1\n");
          					}

		            		$r_items_ids=$DB->get_records_sql($sql1, $params1);


		        		  	if ($r_items_ids){
								// DEBUG
       							if ($debug || CHECKSKILL_SUPER_DEBUG){
              						mtrace("DEBUG :: Line 959 :: GRADE_ITEMS IDS Outcomes <br/>\n");
		            				print_r($r_items_ids);
   				      			}

								// selectionner les grades_grades correspondants
								// Table grade_grades
								$where2='';
								$params2=array();

								if (!empty($r_items_ids)){
									foreach ($r_items_ids as $r_items_id) {
           		    					$params2[]=$r_items_id->id;        // jointure itemid identique
		               					$params2[]=$starttime;
	    		               			$params2[]=$endtime;
										if (!empty($where2)){
											$where2=$where2 . ' OR ((itemid=?) AND(timemodified >= ?) AND (timemodified < ?)) ';
										}
										else {
	    		   	        				$where2=' ((itemid=?) AND (timemodified >= ?) AND (timemodified < ?)) ';
										}
									}
		                           	if (!empty($where2)){
										$where2=' ('. $where2 . ')';
										$sql2='SELECT id, itemid, userid, rawgrademax, rawgrademin, rawscaleid, usermodified, finalgrade, timemodified
 FROM {grade_grades}
 WHERE '.$where2.' ORDER BY itemid, timemodified DESC ';

										if ($debug || CHECKSKILL_SUPER_DEBUG){
  											mtrace("\nDEBUG :: Line 986 :: PARAMETRES REQUETE\n");
											print_r($params2);
											mtrace("\nSQL : $sql2\n");
											mtrace ("TIMES : START : ".date("Y/m/d H:i:s",$starttime)." END : ".date("Y/m/d H:i:s",$endtime)."\n");

       									}
           								$r_grades_recents=$DB->get_records_sql($sql2, $params2);

										if ($r_grades_recents){  // Une ligne au moins reperee dans la fenetre temporelle
											if ($debug || CHECKSKILL_SUPER_DEBUG){
  												mtrace("\nDEBUG :: Line 997 :: GRADE_GRADES RECENTS\n");
                        		                print_r($r_grades_recents);
		  										mtrace ("\n");
		       								}

											// rechercher les lignes de la table grade_items ayant le meme id d'activite
											// pour recuperer les modifications anterieures

											// drapeau pour eviter de traiter plusieurs fois la meme ligne grade_grades
											$t_grades_traites=array();

											foreach ($r_grades_recents as $r_grades_recent) {
        		  								$params3=array("itemid" => "$r_grades_recent->itemid");
          										$sql3 = "SELECT id, courseid, categoryid, itemname, itemtype, itemmodule, iteminstance, itemnumber, iteminfo, idnumber, calculation, gradetype, grademax, grademin, scaleid, outcomeid, timemodified
 FROM {grade_items}  WHERE id= :itemid ORDER BY courseid, outcomeid ASC ";
												if ($debug || CHECKSKILL_SUPER_DEBUG){
  													mtrace("\nDEBUG :: Line 1012\n");
													print_r($params3);
													mtrace("\nSQL : $sql3\n");
        										}
          										$r_item_isole=$DB->get_record_sql($sql3, $params3);
            									if ($r_item_isole){
													if ($debug || CHECKSKILL_SUPER_DEBUG){
        	                            		    	mtrace("\nDEBUG :: Line 975");
														print_r($r_item_isole);
		                	    	    				mtrace("\n");
													}

													// rechercher toutes les lignes similaires en dehors de la fenetre temporelle
          											$params4=array("courseid" => "$r_item_isole->courseid", "iteminstance" => "$r_item_isole->iteminstance", "scaleid" => $r_item_isole->scaleid, "outcomenull" => "" );
	          										$sql4 = "SELECT id, courseid, categoryid, itemname, itemtype, itemmodule, iteminstance, itemnumber, iteminfo, idnumber, calculation, gradetype, grademax, grademin, scaleid, outcomeid, timemodified
 FROM {grade_items}  WHERE courseid=:courseid AND iteminstance=:iteminstance AND scaleid=:scaleid AND outcomeid!=:outcomenull ORDER BY courseid, outcomeid ASC ";
													if ($debug || CHECKSKILL_SUPER_DEBUG){
  														mtrace("\nDEBUG :: Line 1029\n");
														print_r($params4);
														mtrace("\nSQL : $sql4\n");
	          										}

    	      										$r_items=$DB->get_records_sql($sql4, $params4);

													if (!empty($r_items)){
    	                                		       	if ($debug || CHECKSKILL_SUPER_DEBUG){
        	                                    		    mtrace("\nDEBUG :: Line 993");
															print_r($r_items);
        		        	    	    					mtrace("\n");
														}

														$t_items=array();
													 	$where5='';
														$params5=array();
														foreach ($r_items as $r_item) {
															$t_items[$r_item->id]=$r_item;   // stocker l'objet dans un tableau indexe par l'id (jointure itemid de grade_grades Ã  l'etape suivante)
                    										$params5[]=$r_item->id;
                    										$params5[]=$endtime;
	                                    		            $params5[]=$r_grades_recent->userid;
															if (!empty($where5)){
																$where5=$where5 . ' OR ((itemid=?) AND (timemodified < ?) AND (userid=?)) ';
															}
															else {
        	    		    									$where5=' ((itemid=?) AND (timemodified < ?) AND (userid=?)) ';
															}
														}

    	    											if (!empty($where5)){
															$where5=' ('. $where5 . ')';
															$sql5='SELECT id, itemid, userid, rawgrademax, rawgrademin, rawscaleid, usermodified, finalgrade, timemodified
 FROM {grade_grades}
 WHERE  '.$where5.'  ORDER BY itemid, timemodified ';
															if ($debug || CHECKSKILL_SUPER_DEBUG){
  																mtrace("\nDEBUG :: \nLine 1066\n");
																print_r($params5);
																mtrace("\nSQL : $sql5\n");
          													}

	                                            		    $r_grades=$DB->get_records_sql($sql5, $params5);

															if ($r_grades){
 								                				foreach($r_grades as $r_grade){
																	if (($r_grade) && (isset($t_outcomes[$t_items[$r_grade->itemid]->outcomeid]))){
                	    		                        	            if (!isset($t_grades_traites[$r_grade->id])){
																			$t_grades_traites[$r_grade->id]=1;
																		}
                														else{
                                	                			            $t_grades_traites[$r_grade->id]=0;
																		}

																		if (!empty($t_grades_traites[$r_grade->id])){
	   			          													if ($debug || CHECKSKILL_SUPER_DEBUG){
    	    			        												mtrace("DEBUG :: Line 1085 :: ITEMS");
	    	        							    							print_r($r_grades);
            	        	    												mtrace("\n");

	            			       												mtrace("CHECKSKILL : ".$r_checkskill->checkskillid.", Course_id: ".$r_checkskill->courseid);
						            	    			  						mtrace("CHECKSKILL ITEM : ".$r_checkskill->displaytext);
        		    					      									mtrace("OUTCOMES : Id:".$t_items[$r_grade->itemid]->outcomeid." Nom:".$t_outcomes[$t_items[$r_grade->itemid]->outcomeid]->shortname);
						    		              								mtrace("ACTIVITY : Num_Cours:".$t_items[$r_grade->itemid]->courseid.", Nom_Item:".$t_items[$r_grade->itemid]->itemname.", module:".$t_items[$r_grade->itemid]->itemmodule.", instance:".$t_items[$r_grade->itemid]->iteminstance);
																			}

																			// stocker l'activite pour traitement
								                			    			$notation=new Object();
        	            							  						$notation->checkskillid=$r_checkskill->checkskillid;
	                            		                                    $notation->courseid=$t_items[$r_item->id]->courseid;
									    		            	  			$notation->itemid=$r_checkskill->itemid;
        	    										        			$notation->displaytext=$r_checkskill->displaytext;
																			// outcome
					            	          								$notation->outcomeid= $t_items[$r_grade->itemid]->outcomeid;
				        						              				$notation->outcomeshortname= $t_outcomes[$t_items[$r_grade->itemid]->outcomeid]->shortname;
	    		                                                            $notation->scaleid= $t_outcomes[$t_items[$r_grade->itemid]->outcomeid]->scaleid;
																			// activity
    	            										      			$notation->itemname= $t_items[$r_grade->itemid]->itemname;
										                    	  			$notation->module=  $t_items[$r_grade->itemid]->itemmodule;
        			    							          				$notation->moduleinstance= $t_items[$r_grade->itemid]->iteminstance;
																			// grade
																			$notation->userid=$r_grade->userid;
								                      						$notation->teacherid=$r_grade->usermodified;
																			$notation->scaleid= $t_outcomes[$t_items[$r_grade->itemid]->outcomeid]->scaleid;
				        								              		$notation->finalgrade=$r_grade->finalgrade;
								    		            		      		$notation->timemodified=$r_grade->timemodified;
																			// archiver
																			$notations[]= $notation;
																		}
																	}
																}
															}
														}
													}
												}
											}
										}
									}
								}
							}
  						}
  					}
				}
			}
		}
	}
  	return $notations;
}

/**
 * Given an object containing all the necessary data,
 * this function will update an outcome-item and return the id number
 *
 * @param object $checkskill_object a special composite checkskill object
 * @param object $m a special module object
 * @return int the id of the newly inserted record or updated
 **/
function checkskill_set_outcomes($record) {
// creation / update item object
global $CFG;
global $DB;
$debug=false;
    $ok=false;
    if ($debug || CHECKSKILL_SUPER_DEBUG){
		  // DEBUG
		  mtrace("\nDEBUG :: checkskill_set_outcomes :: 1161\n");
		  print_r($record);
	}
	if (!empty($record) && !empty($record->checkskillid) && !empty($record->itemid)){
		/****
                        	$record->text;
                        	$record->checkskillid
                        	$record->itemid
                        	$record->courseid
                        	$record->userid
                        	$record->teacherid
                            $record->teachermark
                            $record->teachertimestamp

		**********/

    // Cette CheckList est-elle enregistree ?
    $params=array("id"=>$record->checkskillid,
        "course"=>$record->courseid);

	$sql = "SELECT * FROM {checkskill} WHERE id=:id AND course=:course";
    if ($debug || CHECKSKILL_SUPER_DEBUG){
        mtrace("\n715 :: SQL:\n$sql\n");
        print_r($params);
	}

	if ($DB->get_record("checkskill", array('id'=>$record->checkskillid, 'course'=>$record->courseid))){
        // Verify item existence
        if ($r_item=$DB->get_record("checkskill_item", array('id'=>$record->itemid))){
			$ok=true;
            $checkskill_check=new Object();
            $checkskill_check->item=$r_item->id;
            $checkskill_check->userid=$record->userid;
            $checkskill_check->usertimestamp=$record->teachertimestamp;
            $checkskill_check->teachermark=$record->teachermark;
            $checkskill_check->teachertimestamp=$record->teachertimestamp;
            $checkskill_check->teacherid=$record->teacherid;

            // Verifier si cet utilisateur est deja ref‚renc‚ pour cet ITEM
            $params=array("itemid"=>$checkskill_check->item,"userid"=>$checkskill_check->userid);
            $sql = "SELECT * FROM {checkskill_check} WHERE item=:itemid AND userid=:userid";
            if ($debug || CHECKSKILL_SUPER_DEBUG){
                mtrace("\n 1209 :: SQL:\n$sql\n");
                print_r($params);
            }
            $checkskill_object_old=$DB->get_record_sql($sql, $params);
            if ($checkskill_object_old) {
                if ($debug || CHECKSKILL_SUPER_DEBUG){
                    // DEBUG
                    mtrace("\n1216 :: OLD\n");
                    print_r($checkskill_object_old);
                }

                $checkskill_check->id=$checkskill_object_old->id;
                $ok=$DB->update_record("checkskill_check", $checkskill_check);
            }
            else{
                // add a new line in table
                $ok=$DB->insert_record("checkskill_check", $checkskill_check);
            }

            // Commentaires
            if ($ok){
                $checkskill_comment=new Object();
                $checkskill_comment->itemid=$r_item->id;
                $checkskill_comment->userid=$record->userid;
                $checkskill_comment->commentby=$record->teacherid;
                $checkskill_comment->text=$record->text;

                // Verifier si cet utilisateur est deja ref‚renc‚ pour un commentaire
                $params=array("itemid"=>$checkskill_comment->itemid,"userid"=>$checkskill_comment->userid);

                $sql = "SELECT * FROM {checkskill_comment} WHERE itemid=:itemid AND userid=:userid";
                if ($debug || CHECKSKILL_SUPER_DEBUG){
                    mtrace("\n1242 :: SQL:\n$sql\n");
                    print_r($params);
                }
                $checkskill_comment_old=$DB->get_record_sql($sql, $params);
                if ($checkskill_comment_old) {
                    $checkskill_comment->id=$checkskill_comment_old->id;
                    $ok=$ok && $DB->update_record("checkskill_comment", $checkskill_comment);
                }
                else{
                    $ok=$ok && $DB->insert_record("checkskill_comment", $checkskill_comment);
                }
            }
        }
    }
	}
    return $ok;
}

// -----------------------
function checkskill_get_bareme_checkskill($checkskill_id){
	global $DB;
	if (!empty($checkskill_id)){
		if ($bareme=$DB->get_record('checkskill_scale', array('checkskill'=>$checkskill_id))){
			return $bareme;
		}
	}
	return NULL;
}


?>
