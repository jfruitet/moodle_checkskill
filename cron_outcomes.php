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
 * Get grades and outcomes from modules that are items for checkskill module
 * uses tables grades_xx and scale_xx
 * Select any outcomes used in a Moodle activity
 * which belongs to item module checkskill
 * @author  David Smith <moodle@davosmith.co.uk>
 * @author  Jean Fruitet <jean.fruitet@univ-nantes.fr>
 * @package mod/checkskill
 */

define("CHECKL_MARKING_STUDENT", 0);
define("CHECKL_MARKING_TEACHER", 1);
define("CHECKL_MARKING_BOTH", 2);

define("CHECKL_TEACHERMARK_NO", 2);
define("CHECKL_TEACHERMARK_YES", 1);
define("CHECKL_TEACHERMARK_UNDECIDED", 0);

// DEBUG ?
define ('CHECKL_DEBUG', 0);    // DEBUG INACTIF
// define ('CHECKL_DEBUG', 1);       // DEBUG ACTIF  : if to 1 cron trace manythings :))

define('OUTCOMES_INTERVALLE_JOUR', 2); // cron on 2 last days
// Increase the value to take into account former evaluations

// -------------------------------------------------
function checkskill_cron_outcomes($starttime=0){
// Update items of Checkskill by the way of outcomes from Moodle activities
global $CFG;
global $DB; 
global $scales;
global $OUTPUT; // for icons

    // all users that are subscribed to any post that needs sending
    $notations = array();
    $scales = array();
    $n_maj=0;

    $timenow   = time();
    if (empty($endtime)){
        $endtime   = $timenow;
    }
    //if (empty($starttime)){
        $starttime = $endtime - OUTCOMES_INTERVALLE_JOUR * 24 *  3600;   // Two days earlier
    //}
    // DEBUG
    if (CHECKL_DEBUG){
        $endtime   = $timenow;
        $starttime = $endtime - OUTCOMES_INTERVALLE_JOUR * 7 * 24 * 3600;   // Two weeks earlier
    }

 
    $scales_skill = '';     // for items with a scaleid
  
    // DEBUG
    mtrace("\nCHECKskill OUTCOMES CRON BEGINNING.");
    if (CHECKL_DEBUG){
        mtrace("\nSTARTTIME: ".date("Y/m/d H-i-s", $starttime)." ENDTIME: ".date("Y/m/d H-i-s", $endtime));   
    }	

    $notations=checkskill_get_outcomes($starttime, $endtime);
    if (CHECKL_DEBUG){
        mtrace("\nDEBUG :: cron_outcomes.php Line 80 :: \nNOTATIONS\n");
        print_r($notations);
    }


    if ($notations){
        foreach($notations as $notation){
            if ($notation){
                if (CHECKL_DEBUG){
                    mtrace("\nDEBUG :: cron_outcomes.php Line 89 :: USERID ".$notation->userid." ; COURSEID ".$notation->courseid."\nNOTATION :\n");
                    print_r($notation);
                }
        		if (!empty($notation->scaleid) && !preg_match("/ ".$notation->scaleid."\,/", $scales_skill)){
          			$scales_skill .= " $notation->scaleid,";
        		}

                if ($m = checkskill_get_module_info($notation->module, $notation->moduleinstance, $notation->courseid)){
                            // DEBUG
                            if (CHECKL_DEBUG){
                                mtrace("\nDEBUG :: cron_outcomes.php Line 194 :: MODULES \n");
                                print_r($m);
                            }

                            $checkskill_object= new Object();

                            $checkskill_object->competences_activite=$notation->outcomeshortname;
                            $checkskill_object->checkskill->id=$notation->instanceid;
                            $checkskill_object->checkskill->course=$notation->courseid;

                            $checkskill_object->checkskill_item->checkskill=$notation->instanceid;
                            $checkskill_object->checkskill_item->id=$notation->itemid;

                            $checkskill_object->checkskill_check->item=$notation->itemid;;
                            $checkskill_object->checkskill_check->userid=$notation->userid;
                            $checkskill_object->checkskill_check->usertimestamp=$m->date;
                            $checkskill_object->checkskill_check->teachermark=CHECKL_MARKING_TEACHER; // A VERIFIER
                            $checkskill_object->checkskill_check->teachertimestamp=$m->date;
							$checkskill_object->checkskill_check->teacherid=$notation->teacherid;
							
                            $checkskill_object->checkskill_comment->itemid=$notation->itemid;
                            $checkskill_object->checkskill_comment->userid=$notation->userid;
                            $checkskill_object->checkskill_comment->commentby=$notation->teacherid;
                            // add follow_link icon
                            $checkskill_object->checkskill_comment->text='[<a href="'.$m->link.'">'.get_string('modulename', $m->type).' N '.$m->ref_activite
                            .' <img src="'.$OUTPUT->pix_url('follow_link','checkskill').'" alt="'.get_string('linktomodule','checkskill').'" />
 </a> '.$m->userdate.'] '.$m->name;


                            $scale  = checkskill_get_scale($notation->scaleid);
                            
                            // DEBUG
                            // print_object($scale);

                            // ------------------
                            if ($scale){
                                // echo "\n $scale->scale\n";
                                // print_r($scale->scaleopt);
                                // echo $scale->scaleopt[(int)$val]."\n";

                                if ($notation->finalgrade>=$scale->grademax){
                                    // echo " ---&gt; VALIDE \n";
                                    $checkskill_object->valide=1;
                                    if (checkskill_set_outcomes($checkskill_object)){
                                        if (CHECKL_DEBUG){
                                            mtrace("\nDEBUG :: cron_outcomes.php Line 280\n-----------------\nENREGISTREE\n");
                                        }
                                        $n_maj++;
                                    }
                                }
                                else{
                                    // echo " ---&gt; INVALIDE \n";
                                    $checkskill_object->valide=0;
                                }
                            }


                            
                            // enregistrer l'activite
                            // DEBUG
                            if (CHECKL_DEBUG){
                                mtrace("\nDEBUG :: cron_outcomes.php Line 274 ; CHECKskill OBJECTS\n");
                                print_r($checkskill_object);
                            }

                }
            }
        }
    }

    // echo "\n\n";
    mtrace($n_maj.' OUTCOMES-ITEMS CREATED OR MODIFIED.');
    mtrace('END CHECKskill OUTCOMES CRON.');
    return $n_maj;
}


// -------------------------------------------------
function checkskill_get_scale($scaleid){
// Preload scale objects for items with a scaleid
global $scales;
global $DB;
    if ($scaleid){
        if (!empty($scales[$scaleid])){
            // echo "\nDEBUG :: 211 SCALE\n";
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
    // print_error("DEBUG :: checkskill_get_module_info :: This course doesn't exist");
    return false;
  }
  if (! $module = $DB->get_record("modules", array("name" => "$modulename"))) {
    // print_error("DEBUG :: checkskill_get_module_info :: This module type doesn't exist");
    return false;
  }
  if (! $cm = $DB->get_record("course_modules", array("course" => "$course->id", "module" => "$module->id", "instance" => "$moduleinstance"))) {
    // print_error("DEBUG :: checkskill_get_module_info :: This course module doesn't exist");
    return false;
  }

  $mid=0;
  $mname='';
  $mdescription='';
  $mlink='';

  if ($modulename=='forum'){
    if (! $forum = $DB->get_record("forum", array("id" => "$cm->instance"))) {
      // print_error("DEBUG :: checkskill_get_module_info :: This forum module doesn't exist");
      return false;
    }
    $mid=$forum->id;
    $mname=$forum->name;
    $mdescription=$forum->intro;
    $mlink = new moodle_url('/mod/'.$modulename.'/view.php', array('id' => $cm->id));
    // $mlink = $CFG->wwwroot.'/mod/forum/view.php?f='.$forum->id;
  }
  elseif ($modulename=='assignment'){
    if (! $assignment = $DB->get_record("assignment", array("id" => "$cm->instance"))) {
      // print_error("DEBUG :: checkskill_get_module_info :: This assignment doesn't exist");
      return false;
    }
    $mid=$assignment->id;
    $mname=$assignment->name;
    $mdescription=$assignment->intro;
    // $mlink = $CFG->wwwroot.'/mod/assignment/view.php?a='.$assignment->id;
    $mlink = new moodle_url('/mod/'.$modulename.'/view.php', array('id' => $cm->id));
  }
  elseif ($modulename=='chat'){
    if (! $chat = $DB->get_record("chat", array("id" => "$cm->instance"))) {
      //error("DEBUG :: checkskill_get_module_info :: This chat doesn't exist");
      return false;
    }
    $mid=$chat->id;
    $mname=$chat->name;
    $mdescription=$chat->intro;
    // $mlink = $CFG->wwwroot.'/mod/chat/view.php?id='.$cm->id;
    $mlink = new moodle_url('/mod/'.$modulename.'/view.php', array('id' => $cm->id));
  }
  elseif ($modulename=='choice'){
    if (! $choice = $DB->get_record("choice", array("id" => "$cm->instance"))) {
      // print_error("DEBUG :: checkskill_get_module_info :: This choice module doesn't exist");
      return false;
    }
    $mid=$choice->id;
    $mname=$choice->name;
    $mdescription=$choice->intro;
    // $mlink = $CFG->wwwroot.'/mod/choice/view.php?id='.$cm->id;
    $mlink = new moodle_url('/mod/'.$modulename.'/view.php', array('id' => $cm->id));
  }
  elseif ($modulename=='data'){
    if (! $data = $DB->get_record("data", array("id" => "$cm->instance"))) {
      // print_error("DEBUG :: checkskill_get_module_info :: This data module doesn't exist");
      return false;
    }
    $mid=$data->id;
    $mname=$data->name;
    $mdescription=$data->intro;
    // $mlink = $CFG->wwwroot.'/mod/data/view.php?id='.$cm->id;
    $mlink = new moodle_url('/mod/'.$modulename.'/view.php', array('id' => $cm->id));

// http://tracker.moodle.org/browse/MDL-15566
// Notice: Undefined property: stdClass::$cmidnumber in C:\xampp\htdocs\moodle_dev\mod\data\lib.php on line 831
  }
  elseif ($modulename=='glossary'){
    if (! $glossary = $DB->get_record("glossary",array("id" => "$cm->instance"))) {
        // print_error("DEBUG :: checkskill_get_module_info :: This glossary module doesn't exist");
        return false;
    }
    $mid=$glossary->id;
    $mname=$glossary->name;
    $mdescription=$glossary->intro;
    // $mlink = $CFG->wwwroot.'/mod/glossary/view.php?id='.$cm->id;
    $mlink = new moodle_url('/mod/'.$modulename.'/view.php', array('id' => $cm->id));
  }
  else{
    // tentative pour un module generique
    if (! $record_module = $DB->get_record($module->name,array("id" => "$cm->instance"))) {
      // print_error("DEBUG :: checkskill_get_module_info :: This ".$module->name." module doesn't exist");
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
      $mdescription=get_string('description_inconnue','checkskill');
    }
    // $mlink = $CFG->wwwroot.'/mod/'.$modulename.'/view.php?id='.$cm->id;
    $mlink = new moodle_url('/mod/'.$modulename.'/view.php', array('id' => $cm->id));
  }

  $m=new Object();
  $m->id=$module->id;
  $m->type=$modulename;
  $m->instance=$moduleinstance;
  $m->course=$courseid;
  $m->date=$cm->added;
  $m->userdate=userdate($cm->added);
  $m->ref_activite=$mid;
  $m->name=$mname;
  $m->description=$mdescription;
  $m->link=$mlink;

  return $m;
}

// -------------------------------------------------
function checkskill_get_outcomes($starttime, $endtime){
// genere le skille des notations
global $CFG;
global $DB;
global $t_items_records;

    $notations=array();
    $t_referentiels=array();

    $t_items_records=array();
    
    // selectionner tous les codes outcomes
    $params=array();
	$sql = "SELECT {checkskill_item}.id AS itemid,
        {checkskill_item}.displaytext AS displaytext,
        {checkskill}.id AS instanceid,
        {checkskill}.course AS courseid
  FROM {checkskill}, {checkskill_item}
  WHERE {checkskill}.id={checkskill_item}.checkskill
  ORDER BY {checkskill}.course ASC, {checkskill}.id ASC, {checkskill_item}.displaytext ASC ";
    
    // DEBUG
    if  (CHECKL_DEBUG){
        mtrace("\nDEBUG :: ./mod/checkskill/cron_outcomes.php\nLine 372 :: SQL:$sql\n");
        print_r($params);
    }
   
  
    $r_checkskills=$DB->get_records_sql($sql, $params);
    if ($r_checkskills){
        /*
        // DEBUG
        if   (CHECKL_DEBUG){
                mtrace("DEBUG :: ./mod/checkskill/cron_outcomes.php\nLine 363 :: COMPOSITE DATA\n");
                print_r($r_checkskills);
        }
        */
        foreach($r_checkskills as $r_checkskill){
            $item_outcome=new Object();
            $item_outcome->itemid=$r_checkskill->itemid;
            $item_outcome->displaytext=$r_checkskill->displaytext;
            $item_outcome->courseid=$r_checkskill->courseid;
            $item_outcome->instanceid=$r_checkskill->instanceid;
            $item_outcome->outcome='';
            $item_outcome->code_referentiel='';
            $item_outcome->code_competence='';


            // DEBUG
            if   (CHECKL_DEBUG){
                mtrace("DEBUG :: ./mod/checkskill/cron_outcomes.php\nLine 380 :: COMPOSITE DATA\n");
                print_r($r_checkskill);
            }

            // First extrac outcomes from items skill
            // Searched matches
            // C2i2e-2011 A.1-1 :: Identifier les personnes resso...
            if (preg_match('/(.*)::(.*)/i', $r_checkskill->displaytext, $matches)){
                // DEBUG

                if   (CHECKL_DEBUG){
                    mtrace("DEBUG :: ./mod/checkskill/cron_outcomes.php\nLine 391 :: MATCHES\n");
                    print_r($matches);
                }

                if ($matches[1]){
                    //
                    $item_outcome->outcome=trim($matches[1]);
                    if ($keywords = preg_split("/[\s]+/",$matches[1],-1,PREG_SPLIT_NO_EMPTY)){

                        if   (CHECKL_DEBUG){
                            mtrace("DEBUG :: ./mod/checkskill/cron_outcomes.php\nLine 401 :: REFERENTIELS\n");
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
                if   (CHECKL_DEBUG){
                    mtrace("DEBUG :: ./mod/checkskill/cron_outcomes.php\nLine 417 :: ITEM_OUTCOME\n");
                    print_r($item_outcome);
                }


            }
            
            if (!empty($item_outcome->code_referentiel)){

/*

--
-- Structure of table 'mdl_grade_outcomes'
--

CREATE TABLE mdl_grade_outcomes (
  id bigint(10) unsigned NOT NULL AUTO_INCREMENT,
  courseid bigint(10) unsigned DEFAULT NULL,
  shortname varchar(255) NOT NULL DEFAULT '',
  fullname text NOT NULL,
  scaleid bigint(10) unsigned DEFAULT NULL,
  description text,
  timecreated bigint(10) unsigned DEFAULT NULL,
  timemodified bigint(10) unsigned DEFAULT NULL,
  usermodified bigint(10) unsigned DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY mdl_gradoutc_cousho_uix (courseid,shortname),
  KEY mdl_gradoutc_cou_ix (courseid),
  KEY mdl_gradoutc_sca_ix (scaleid),
  KEY mdl_gradoutc_use_ix (usermodified)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT='This table describes the outcomes used in the system. An out';
*/

                    $params=array("fullname" => "$item_outcome->outcome%") ;
                    $sql = "SELECT id, courseid, shortname, fullname, scaleid
      FROM {grade_outcomes}
      WHERE fullname LIKE :fullname
      ORDER BY fullname ASC ";	
                    // DEBUG

                    if   (CHECKL_DEBUG){
                        mtrace("\nDEBUG :: ./mod/checkskill/cron_outcomes.php\nLine 458 :: SQL:$sql\n");
                        print_r($params);
                    }


                    $r_outcomes=$DB->get_records_sql($sql, $params);
                    if ($r_outcomes){
                        foreach($r_outcomes as $r_outcome){
                            // selectionner les items (activites utilisant ces outcomes)
                            // DEBUG
                            /*
                            if   (CHECKL_DEBUG){
                                mtrace("DEBUG :: ./mod/checkskill/cron_outcomes.php Line 610 :: R_OUTCOMES\n");
                                print_r($r_outcome);
                                echo "\n\n";
                            }
                            */

/*

CREATE TABLE mdl_grade_items (
  id bigint(10) unsigned NOT NULL AUTO_INCREMENT,
  courseid bigint(10) unsigned DEFAULT NULL,
  categoryid bigint(10) unsigned DEFAULT NULL,
  itemname varchar(255) DEFAULT NULL,
  itemtype varchar(30) NOT NULL DEFAULT '',
  itemmodule varchar(30) DEFAULT NULL,
  iteminstance bigint(10) unsigned DEFAULT NULL,
  itemnumber bigint(10) unsigned DEFAULT NULL,
  iteminfo mediumtext,
  idnumber varchar(255) DEFAULT NULL,
  calculation mediumtext,
  gradetype smallint(4) NOT NULL DEFAULT '1',
  grademax decimal(10,5) NOT NULL DEFAULT '100.00000',
  grademin decimal(10,5) NOT NULL DEFAULT '0.00000',
  scaleid bigint(10) unsigned DEFAULT NULL,
  outcomeid bigint(10) unsigned DEFAULT NULL,
  gradepass decimal(10,5) NOT NULL DEFAULT '0.00000',
  multfactor decimal(10,5) NOT NULL DEFAULT '1.00000',
  plusfactor decimal(10,5) NOT NULL DEFAULT '0.00000',
  aggregationcoef decimal(10,5) NOT NULL DEFAULT '0.00000',
  sortorder bigint(10) NOT NULL DEFAULT '0',
  display bigint(10) NOT NULL DEFAULT '0',
  decimals tinyint(1) unsigned DEFAULT NULL,
  hidden bigint(10) NOT NULL DEFAULT '0',
  locked bigint(10) NOT NULL DEFAULT '0',
  locktime bigint(10) unsigned NOT NULL DEFAULT '0',
  needsupdate bigint(10) NOT NULL DEFAULT '0',
  timecreated bigint(10) unsigned DEFAULT NULL,
  timemodified bigint(10) unsigned DEFAULT NULL,
  PRIMARY KEY (id),
  KEY mdl_graditem_locloc_ix (locked,locktime),
  KEY mdl_graditem_itenee_ix (itemtype,needsupdate),
  KEY mdl_graditem_gra_ix (gradetype),
  KEY mdl_graditem_idncou_ix (idnumber,courseid),
  KEY mdl_graditem_cou_ix (courseid),
  KEY mdl_graditem_cat_ix (categoryid),
  KEY mdl_graditem_sca_ix (scaleid),
  KEY mdl_graditem_out_ix (outcomeid)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT='This table keeps information about gradeable items (ie colum';          

INSERT INTO `mdl_grade_items` (`id`, `courseid`, `categoryid`, `itemname`, `itemtype`, `itemmodule`, `iteminstance`, `itemnumber`, `iteminfo`, `idnumber`, `calculation`, `gradetype`, `grademax`, `grademin`, `scaleid`, `outcomeid`, `gradepass`, `multfactor`, `plusfactor`, `aggregationcoef`, `sortorder`, `display`, `decimals`, `hidden`, `locked`, `locktime`, `needsupdate`, `timecreated`, `timemodified`) 
VALUES(1, 2, NULL, NULL, 'course', NULL, 1, NULL, NULL, NULL, NULL, 1, '100.00000', '0.00000', NULL, NULL, '0.00000', '1.00000', '0.00000', '0.00000', 1, 0, NULL, 0, 0, 0, 0, 1260780703, 1260780703);
...
INSERT INTO `mdl_grade_items` (`id`, `courseid`, `categoryid`, `itemname`,     `itemtype`, `itemmodule`, `iteminstance`, `itemnumber`, `iteminfo`, `idnumber`, `calculation`, `gradetype`, `grademax`, `grademin`, `scaleid`, `outcomeid`, `gradepass`, `multfactor`, `plusfactor`, `aggregationcoef`, `sortorder`, `display`, `decimals`, `hidden`, `locked`, `locktime`, `needsupdate`, `timecreated`, `timemodified`) 
VALUES                        (9,     2,          1,            'C2i2e B.4.1', 'mod',      'assignment',  1,             1003,         NULL,        NULL,       NULL,         2,           '3.00000',  '1.00000',   2,        27,           '0.00000',   '1.00000',   '0.00000',    '0.00000',          5, 0, NULL, 0, 0, 0, 0, 1266785659, 1266785659);
*/
                            $params=array("outcomeid" => $r_outcome->id, "courseid" => $item_outcome->courseid);
                            $sql = "SELECT `id`, `courseid`, `categoryid`, `itemname`, `itemtype`, `itemmodule`, `iteminstance`, `itemnumber`, `iteminfo`, `idnumber`, `calculation`, `gradetype`, `grademax`, `grademin`, `scaleid`, `outcomeid`, `timemodified`
 FROM {grade_items}  WHERE outcomeid= :outcomeid  AND courseid=:courseid
 ORDER BY courseid, outcomeid ASC ";
 
                            $r_items=$DB->get_records_sql($sql, $params);
                            if ($r_items){
                                foreach($r_items as $r_item){
                                    // selectionner les items (activites) utilisant ces outcomes
                                    // DEBUG
                                    if   (CHECKL_DEBUG){
                                        mtrace("DEBUG :: ./mod/checkskill/cron_outcomes.php\nLine 675 :: ITEMS\n");
                                        print_r($r_item);
                                    }

                                    // selectionner les grades (notes attribuées aux utilisateur de ces activités)
/*
--
-- Structure de la table 'mdl_grade_grades'
--

CREATE TABLE mdl_grade_grades (
  id bigint(10) unsigned NOT NULL AUTO_INCREMENT,
  itemid bigint(10) unsigned NOT NULL,
  userid bigint(10) unsigned NOT NULL,
  rawgrade decimal(10,5) DEFAULT NULL,
  rawgrademax decimal(10,5) NOT NULL DEFAULT '100.00000',
  rawgrademin decimal(10,5) NOT NULL DEFAULT '0.00000',
  rawscaleid bigint(10) unsigned DEFAULT NULL,
  usermodified bigint(10) unsigned DEFAULT NULL,
  finalgrade decimal(10,5) DEFAULT NULL,
  hidden bigint(10) unsigned NOT NULL DEFAULT '0',
  locked bigint(10) unsigned NOT NULL DEFAULT '0',
  locktime bigint(10) unsigned NOT NULL DEFAULT '0',
  exported bigint(10) unsigned NOT NULL DEFAULT '0',
  overridden bigint(10) unsigned NOT NULL DEFAULT '0',
  excluded bigint(10) unsigned NOT NULL DEFAULT '0',
  feedback mediumtext,
  feedbackformat bigint(10) unsigned NOT NULL DEFAULT '0',
  information mediumtext,
  informationformat bigint(10) unsigned NOT NULL DEFAULT '0',
  timecreated bigint(10) unsigned DEFAULT NULL,
  timemodified bigint(10) unsigned DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY mdl_gradgrad_useite_uix (userid,itemid),
  KEY mdl_gradgrad_locloc_ix (locked,locktime),
  KEY mdl_gradgrad_ite_ix (itemid),
  KEY mdl_gradgrad_use_ix (userid),
  KEY mdl_gradgrad_raw_ix (rawscaleid),
  KEY mdl_gradgrad_use2_ix (usermodified)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT='grade_grades  This table keeps individual grades for each us';

--
-- Contenu de la table 'mdl_grade_grades'
--

INSERT INTO mdl_grade_grades (id, itemid, userid, rawgrade, rawgrademax, rawgrademin, rawscaleid, usermodified, finalgrade, hidden, locked, locktime, exported, overridden, excluded, feedback, feedbackformat, information, informationformat, timecreated, timemodified) VALUES
(1, 3, 2, '2.00000', '3.00000', '1.00000', 1, 4, '2.00000', 0, 0, 0, 0, 0, 0, NULL, 0, NULL, 0, NULL, 1266662583),
(2, 1, 2, NULL, '100.00000', '0.00000', NULL, NULL, '50.00000', 0, 0, 0, 0, 0, 0, NULL, 0, NULL, 0, NULL, NULL),
(3, 3, 3, '3.00000', '3.00000', '1.00000', 1, 2, '3.00000', 0, 0, 0, 0, 0, 0, NULL, 0, NULL, 0, NULL, 1266664474),
(4, 1, 3, NULL, '100.00000', '0.00000', NULL, NULL, '100.00000', 0, 0, 0, 0, 0, 0, NULL, 0, NULL, 0, NULL, NULL),
(5, 4, 3, NULL, '100.00000', '0.00000', NULL, 2, '3.00000', 0, 0, 0, 0, 0, 0, NULL, 0, NULL, 0, NULL, 1266663872),
(6, 5, 3, '3.00000', '3.00000', '1.00000', 2, 4, '3.00000', 0, 0, 0, 0, 0, 0, 'OK ', 1, NULL, 0, 1266785814, 1266785949),
(7, 6, 3, NULL, '100.00000', '0.00000', NULL, 4, '2.00000', 0, 0, 0, 0, 0, 0, NULL, 0, NULL, 0, NULL, 1266785948),
(8, 7, 3, NULL, '100.00000', '0.00000', NULL, 4, '3.00000', 0, 0, 0, 0, 0, 0, NULL, 0, NULL, 0, NULL, 1266785949),
(9, 8, 3, NULL, '100.00000', '0.00000', NULL, 4, '3.00000', 0, 0, 0, 0, 0, 0, NULL, 0, NULL, 0, NULL, 1266785949),
(10, 9, 3, NULL, '100.00000', '0.00000', NULL, 4, '3.00000', 0, 0, 0, 0, 0, 0, NULL, 0, NULL, 0, NULL, 1266785949);

*/

                    
  
                                    // DEBUG
                                    if   (CHECKL_DEBUG){
                                        mtrace("DEBUG :: ./mod/checkskill/cron_outcomes.php Line 738\n");
                                        mtrace ("CHECKskill INSTANCE : ".$item_outcome->instanceid."\nCOURSE ID: ".$item_outcome->courseid."\n");
                                        mtrace ("DISPLAY : ".$item_outcome->displaytext."\n");
                                        mtrace ("ITEM CHECKskill : ".$item_outcome->itemid."\n");
                                        mtrace ("REFERENTIEL : ".$item_outcome->code_referentiel."\n");
                                        mtrace ("COMPETENCE : ".$item_outcome->code_competence."\n");
                                        mtrace ("OUTCOME : ".$item_outcome->outcome."\n");
                                        mtrace ("OBJECTIF : Id:".$r_outcome->id." Nom:".$r_outcome->fullname."\n");
                                        mtrace ("GRADE ITEM: Num_Cours:".$r_item->courseid.", Nom_Item:".$r_item->itemname.", module:".$r_item->itemmodule.", instance:".$r_item->iteminstance.", Num_Objectif:".$r_item->outcomeid);
                                    }
                                    
                                    $params=array("itemid"=>$r_item->id, "starttime" =>$starttime, "endtime" => $endtime);
                                    $sql = "SELECT id, itemid, userid, usermodified, rawscaleid, finalgrade, timemodified
 FROM {grade_grades} WHERE itemid=:itemid AND ((timemodified>=:starttime)
 AND (timemodified < :endtime)) ORDER BY itemid ASC, userid ASC ";
 
                                    // DEBUG
                                    if   (CHECKL_DEBUG){
                                        mtrace("DEBUG :: ./mod/checkskill/cron_outcomes.php Line 739 ::\nSQL = $sql\n");
                                        print_r($params);
                                    }

                                    $r_grades=$DB->get_records_sql($sql, $params);
                                    if ($r_grades){
                                        foreach($r_grades as $r_grade){
                                            if ($r_grade){
                                                // stocker l'activite pour traitement
                                                $notation=new Object();
                                                $notation->instanceid=$item_outcome->instanceid;
                                                $notation->courseid=$item_outcome->courseid;
                                                $notation->itemid=$item_outcome->itemid;
                                                $notation->code_referentiel=$item_outcome->code_referentiel;
                                                $notation->outcomeid= $r_outcome->id;
                                                $notation->outcomeshortname= $r_outcome->shortname;
                                                $notation->scaleid= $r_outcome->scaleid;
                                                $notation->itemname= $r_item->itemname;
                                                $notation->module=  $r_item->itemmodule;
                                                $notation->moduleinstance= $r_item->iteminstance;              
                                                $notation->userid=$r_grade->userid;  
                                                $notation->teacherid=$r_grade->usermodified;
                                                $notation->finalgrade=$r_grade->finalgrade; 
                                                $notation->timemodified=$r_grade->timemodified;
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
function checkskill_set_outcomes($checkskill_object) {
// creation / update item object
global $CFG;
global $DB;

    $ok=false;
	if (CHECKL_DEBUG){
        // DEBUG
        mtrace("\nDEBUG :: checkskill_activite_outcomes :: 705\nDEMANDE DE MISE A JOUR\n");
        print_r($checkskill_object);
    }

    // Cette Checkskill est-elle enregistree ?
    $params=array("id"=>$checkskill_object->checkskill->id,
        "course"=>$checkskill_object->checkskill->course);
        
	$sql = "SELECT * FROM {checkskill} WHERE id=:id AND course=:course";
    if (CHECKL_DEBUG){
        mtrace("\n715 :: SQL:\n$sql\n");
        print_r($params);
	}

	$r_checkskill=$DB->get_record_sql($sql, $params);

    if ($r_checkskill) {
        if (CHECKL_DEBUG){
		  // DEBUG
		  mtrace("\nDEBUG :: checkskill_activite_outcomes :: 697\n");
		  print_r($r_checkskill);
        }

        // Verify item existence
        $params=array("checkskill"=>$checkskill_object->checkskill->id,
            "id"=>$checkskill_object->checkskill_item->id);

    	$sql = "SELECT * FROM {checkskill_item} WHERE id=:id AND checkskill=:checkskill";
        if (CHECKL_DEBUG){
            mtrace("\n710 :: SQL:\n$sql\n");
            print_r($params);
        }
        $r_checkskill_item=$DB->get_record_sql($sql, $params);
        if ($r_checkskill_item) {
            $checkskill_check=new Object();
            $checkskill_check->item=$checkskill_object->checkskill_check->item;
            $checkskill_check->userid=$checkskill_object->checkskill_check->userid;
            $checkskill_check->usertimestamp=$checkskill_object->checkskill_check->usertimestamp;
            $checkskill_check->teachermark=$checkskill_object->checkskill_check->teachermark;
            $checkskill_check->teachertimestamp=$checkskill_object->checkskill_check->teachertimestamp;

            // Verifier si cet utilisateur est deja reférencé pour cet ITEM
            $params=array("item"=>$checkskill_object->checkskill_item->id,
                "userid"=>$checkskill_object->checkskill_check->userid);

            $sql = "SELECT * FROM {checkskill_check} WHERE item=:item AND userid=:userid";
            if (CHECKL_DEBUG){
                mtrace("\n728 :: SQL:\n$sql\n");
                print_r($params);
            }
            $checkskill_object_old=$DB->get_record_sql($sql, $params);
            if ($checkskill_object_old) {
                if (CHECKL_DEBUG){
                    // DEBUG
                    mtrace("\n735 :: OLD\n");
                    print_r($checkskill_object_old);
                }

                $checkskill_check->id=$checkskill_object_old->id;
                $checkskill_check->usertimestamp=$checkskill_object_old->usertimestamp;
                if (empty($checkskill_check->usertimestamp)){
                    $checkskill_check->usertimestamp=time();
                }

                if ($checkskill_object_old->teachermark==CHECKL_MARKING_STUDENT){
                    $checkskill_check->teachermark=CHECKL_MARKING_BOTH;
                }
                else{
                    $checkskill_check->teachermark=CHECKL_MARKING_TEACHER;
                }

                if (empty($checkskill_object_old->teachertimestamp)
                        || ($checkskill_object_old->teachertimestamp<$checkskill_check->teachertimestamp)){
                    $checkskill_check->teachertimestamp=time();
                    if (CHECKL_DEBUG){
                        // DEBUG
                        mtrace("\n757 :: MISE A JOUR CHECKSKILL_CHECK\n");
                        print_r($checkskill_check);
                    }
                    $ok=$DB->update_record("checkskill_check", $checkskill_check);

                    if (CHECKL_DEBUG){
                        // DEBUG
                        if ($ok) mtrace("\n764 :: UPDATE CHECK\n");
                        else  mtrace("\n65 :: ERREUR UPDATE CHECK\n");
                        print_r($checkskill_check);
                    }
                }
            }
            else{
                // add a new line in table
                $checkskill_check->usertimestamp=0;
                if ($checkskill_check->teachertimestamp==0){
                    $checkskill_check->teachertimestamp=time();
                }
                $checkskill_check->teachermark=CHECKL_TEACHERMARK_YES;

                if (CHECKL_DEBUG){
                    // DEBUG
                    mtrace("\n780 :: NEW CREATED\n");
                    print_r($checkskill_check);
                }
                $checkskill_check_id=$DB->insert_record("checkskill_check", $checkskill_check);
                if ($checkskill_check_id) {
                    $ok=true;
                }
                if (CHECKL_DEBUG){
                    // DEBUG
                    if ($ok) mtrace("\n789 :: INSERT CHECK\n");
                    else  mtrace("\n790 :: ERREUR INSERT CHECK\n");
                    print_r($checkskill_check);
                }
            }

            // Commentaires
            if ($ok){
                $checkskill_comment=new Object();
                $checkskill_comment->id=0;
                $checkskill_comment->itemid=$checkskill_object->checkskill_comment->itemid;
                $checkskill_comment->userid=$checkskill_object->checkskill_comment->userid;
                $checkskill_comment->commentby=$checkskill_object->checkskill_comment->commentby;
                $checkskill_comment->text=$checkskill_object->checkskill_comment->text;

                // Verifier si cet utilisateur est deja reférencé pour un commentaire
                $params=array("item"=>$checkskill_object->checkskill_item->id,
                    "userid"=>$checkskill_object->checkskill_comment->userid);

                $sql = "SELECT * FROM {checkskill_comment} WHERE itemid=:item AND userid=:userid";
                if (CHECKL_DEBUG){
                    mtrace("\n810 :: SQL:\n$sql\n");
                    print_r($params);
                }
                $checkskill_comment_old=$DB->get_record_sql($sql, $params);
                if ($checkskill_comment_old) {
                    if (CHECKL_DEBUG){
                        // DEBUG
                        mtrace("\n817 :: OLD COMMENT\n");
                        print_r($checkskill_comment_old);
                    }

                    $checkskill_comment->id=$checkskill_comment_old->id;
                    /*
                    if (!empty($checkskill_comment_old->text)){
                        $checkskill_comment->text='['.get_string('old_comment', 'checkskill').' '.userdate($checkskill_comment_old->commentby).' '.$checkskill_comment_old->text.']<br />'.$checkskill_comment->text;
                    }
                    */
                    if (CHECKL_DEBUG){
                        // DEBUG
                        mtrace("\n829 :: MISE A JOUR CHECKSKILL_COMMENT\n");
                        print_r($checkskill_comment);
                    }
                    $ok=$ok && $DB->update_record("checkskill_comment", $checkskill_comment);
                }
                else{
                    $ok=$ok && $DB->insert_record("checkskill_comment", $checkskill_comment);
                }
            }
        }
        else {
                // NOTHING TO DO
                // We 'll not add any ITEM to CHECKSKILL_ITEM NOW... Nop !
                $ok=false;
        }
    }
    else{
        $ok=false;
    }
    return $ok;
}


?>
