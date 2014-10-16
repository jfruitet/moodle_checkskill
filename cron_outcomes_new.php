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
function checkskill_cron_outcomes($starttime=0){
// Update items of CheckSkill by the way of outcomes from Moodle activities
global $CFG;
global $DB; 
global $scales;
global $OUTPUT; // for icons

    $notations = array();
    $scales = array();
    $n_maj=0;

	$timenow   = time();

	// Record olders than CHECKSKILL_OUTCOMES_DELAY (2) days not examined.
	// This is to avoid the problem where cron has not been running for a long time
    // Enregistrements anterieurs a CHECKSKILL_OUTCOMES_DELAY (2) jours non traites.
	if (CHECKSKILL_DEBUG){
    	$starttime = $endtime - CHECKSKILL_OUTCOMES_DELAY * 7 * 24 * 3600;   // Two weeks earlier
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

    $scales_list = '';     // for items evaluated with a scaleid (not yet supported)
  
    // DEBUG
    mtrace("\nCHECKSKILL OUTCOMES CRON BEGINNING.");
    if (CHECKSKILL_DEBUG){
        mtrace("\nSTARTTIME: ".date("Y/m/d H-i-s", $starttime)." ENDTIME: ".date("Y/m/d H-i-s", $endtime));   
    }	

    $notations=checkskill_get_outcomes($starttime, $endtime);

    if ($notations){
        foreach($notations as $notation){
            if ($notation){
                if (CHECKSKILL_DEBUG){
                    mtrace("\nDEBUG :: cron_outcomes.php Line 98 :: USERID ".$notation->userid." ; COURSEID ".$notation->courseid."\nNOTATION :\n");
                    print_r($notation);
                }
        		if (!empty($notation->scaleid) && !preg_match("/ ".$notation->scaleid."\,/", $scales_list)){
          			$scales_list .= " $notation->scaleid,";
        		}

                if ($m = checkskill_get_module_info($notation->module, $notation->moduleinstance, $notation->courseid)){
                    // DEBUG
                    if (CHECKSKILL_DEBUG){
                    	mtrace("\nDEBUG :: cron_outcomes.php Line 194 :: MODULES \n");
                    	print_r($m);
                    }

                    $checkskill_object= new Object();

                    $checkskill_object->competences_activite=$notation->outcomeshortname;
                    $checkskill_object->checkskill->id=$notation->instanceid;
                    $checkskill_object->checkskill->course=$notation->courseid;

                    $checkskill_object->checkskill_item->checkskill=$notation->instanceid;
                    $checkskill_object->checkskill_item->id=$notation->itemid;

                    $checkskill_object->checkskill_check->item=$notation->itemid;
                    $checkskill_object->checkskill_check->userid=$notation->userid;
                    $checkskill_object->checkskill_check->usertimestamp=$m->date;
                    $checkskill_object->checkskill_check->teachermark=CHECKL_MARKING_TEACHER; // A VERIFIER
                    $checkskill_object->checkskill_check->teachertimestamp=$m->date;
					$checkskill_object->checkskill_check->teacherid=$notation->teacherid;
							
                    $checkskill_object->checkskill_comment->itemid=$notation->itemid;
                    $checkskill_object->checkskill_comment->userid=$notation->userid;
                    $checkskill_object->checkskill_comment->commentby=$notation->teacherid;
                    // add follow_link icon
                    $checkskill_object->checkskill_comment->text='[<a href="'.$m->link.'">'.get_string('modulename', $m->type).' N '.$m->id
                            .' <img src="'.$OUTPUT->pix_url('follow_link','checkskill').'" alt="'.get_string('linktomodule','checkskill').'" />
 </a> '.$m->userdate.'] '.$m->name;


                    $scale  = checkskill_get_scale($notation->scaleid);
                    if (CHECKSKILL_DEBUG){
                        // DEBUG
                        // print_object($scale);
					}
                    // ------------------
                    if ($scale){
                    	// echo "\n $scale->scale\n";
                        // print_r($scale->scaleopt);
                        // echo $scale->scaleopt[(int)$val]."\n";

                        if ($notation->finalgrade>=$scale->grademax){
                            // echo " ---&gt; VALIDE \n";
                            $checkskill_object->valide=1;
                            if (checkskill_set_outcomes($checkskill_object)){
                            	if (CHECKSKILL_DEBUG){
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
                    if (CHECKSKILL_DEBUG){
						mtrace("\nDEBUG :: cron_outcomes.php Line 274 ; CHECKSKILL OBJECTS\n");
                        print_r($checkskill_object);
                    }
                }
            }
        }
    }

    // echo "\n\n";
    mtrace($n_maj.' OUTCOMES-ITEMS CREATED OR MODIFIED.');
    mtrace('END CHECKSKILL OUTCOMES CRON.');
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
  }
  elseif ($modulename=='assign'){
    if (! $assign= $DB->get_record("assign", array("id" => "$cm->instance"))) {
      // print_error("DEBUG :: checkskill_get_module_info :: This forum module doesn't exist");
      return false;
    }
    $mid=$assign->id;
    $mname=$assign->name;
    $mdescription=$assign->intro;
    $mlink = new moodle_url('/mod/'.$modulename.'/view.php', array('id' => $cm->id));
  }

  elseif ($modulename=='assignment'){
    if (! $assignment = $DB->get_record("assignment", array("id" => "$cm->instance"))) {
      // print_error("DEBUG :: checkskill_get_module_info :: This assignment doesn't exist");
      return false;
    }
    $mid=$assignment->id;
    $mname=$assignment->name;
    $mdescription=$assignment->intro;
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
    $mlink = new moodle_url('/mod/'.$modulename.'/view.php', array('id' => $cm->id));
  }
  elseif ($modulename=='glossary'){
    if (! $glossary = $DB->get_record("glossary",array("id" => "$cm->instance"))) {
        // print_error("DEBUG :: checkskill_get_module_info :: This glossary module doesn't exist");
        return false;
    }
    $mid=$glossary->id;
    $mname=$glossary->name;
    $mdescription=$glossary->intro;
    $mlink = new moodle_url('/mod/'.$modulename.'/view.php', array('id' => $cm->id));
  }
  else{
    // generic module
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
    $mlink = new moodle_url('/mod/'.$modulename.'/view.php', array('id' => $cm->id));
  }

  $m=new Object();
  $m->id=$mid;
  $m->type=$modulename;
  $m->instance=$moduleinstance;
  $m->course=$courseid;
  $m->date=$cm->added;
  $m->userdate=userdate($cm->added);
  $m->name=$mname;
  $m->description=$mdescription;
  $m->link=$mlink;

  return $m;
}

// -------------------------------------------------
function checkskill_get_outcomes($starttime, $endtime){
// genere la liste des notations
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
    if  (CHECKSKILL_DEBUG){
        mtrace("\nDEBUG :: ./mod/checkskill/cron_outcomes.php\nLine 372 :: SQL:$sql\n");
        print_r($params);
    }
   
  
    $r_checkskills=$DB->get_records_sql($sql, $params);
    if ($r_checkskills){
        /*
        // DEBUG
        if   (CHECKSKILL_DEBUG){
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
            if   (CHECKSKILL_DEBUG){
                mtrace("DEBUG :: ./mod/checkskill/cron_outcomes.php\nLine 380 :: COMPOSITE DATA\n");
                print_r($r_checkskill);
            }

            // First extract outcomes from items skill
            // Searched matches
            // C2i2e-2011 A.1-1 :: Identifier les personnes resso...
            if (preg_match('/(.*)::(.*)/i', $r_checkskill->displaytext, $matches)){
                // DEBUG

                if   (CHECKSKILL_DEBUG){
                    mtrace("DEBUG :: ./mod/checkskill/cron_outcomes.php\nLine 391 :: MATCHES\n");
                    print_r($matches);
                }

                if ($matches[1]){
                    //
                    $item_outcome->outcome=trim($matches[1]);
                    if ($keywords = preg_split("/[\s]+/",$matches[1],-1,PREG_SPLIT_NO_EMPTY)){

                        if   (CHECKSKILL_DEBUG){
                            mtrace("DEBUG :: ./mod/checkskill/cron_outcomes.php\nLine 401 :: CHECKSKILLS\n");
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
                if   (CHECKSKILL_DEBUG){
                    mtrace("DEBUG :: ./mod/checkskill/cron_outcomes.php\nLine 417 :: ITEM_OUTCOME\n");
                    print_r($item_outcome);
                }
            }
            
            if (!empty($item_outcome->code_referentiel)){

/* OLD VERSION *********************************************************
                    $params=array("fullname" => "$item_outcome->outcome%") ;
                    $sql = "SELECT id, courseid, shortname, fullname, scaleid
      FROM {grade_outcomes}
      WHERE fullname LIKE :fullname
      ORDER BY fullname ASC ";	
                    // DEBUG
                    if   (CHECKSKILL_DEBUG){
                        mtrace("\nDEBUG :: ./mod/checkskill/cron_outcomes.php\nLine 458 :: SQL:$sql\n");
                        print_r($params);
                    }

                    $r_outcomes=$DB->get_records_sql($sql, $params);
                    if ($r_outcomes){
                        foreach($r_outcomes as $r_outcome){
                            // select items (activities using these outcomes)
                            // DEBUG
                            if   (CHECKSKILL_DEBUG){
                                mtrace("DEBUG :: ./mod/checkskill/cron_outcomes.php Line 496 :: R_OUTCOMES\n");
                                print_r($r_outcome);
                                echo "\n\n";
                            }

                            $params=array("outcomeid" => $r_outcome->id, "courseid" => $item_outcome->courseid);
                            $sql = "SELECT `id`, `courseid`, `categoryid`, `itemname`, `itemtype`, `itemmodule`, `iteminstance`, `itemnumber`, `iteminfo`, `idnumber`, `calculation`, `gradetype`, `grademax`, `grademin`, `scaleid`, `outcomeid`, `timemodified`
 FROM {grade_items}  WHERE outcomeid= :outcomeid  AND courseid=:courseid
 ORDER BY courseid, outcomeid ASC ";
 
                            $r_items=$DB->get_records_sql($sql, $params);
                            if ($r_items){
                                foreach($r_items as $r_item){
                                    // selectionner les items (activites) utilisant ces outcomes
                                    // DEBUG
                                    if   (CHECKSKILL_DEBUG){
                                        mtrace("DEBUG :: ./mod/checkskill/cron_outcomes.php\nLine 675 :: ITEMS\n");
                                        print_r($r_item);
                                    }

                                    // selectionner les grades (notes attribuées aux utilisateur de ces activités)

                                    // DEBUG
                                    if   (CHECKSKILL_DEBUG){
                                        mtrace("DEBUG :: ./mod/checkskill/cron_outcomes.php Line 738\n");
                                        mtrace ("CHECKSKILL INSTANCE : ".$item_outcome->instanceid."\nCOURSE ID: ".$item_outcome->courseid."\n");
                                        mtrace ("DISPLAY : ".$item_outcome->displaytext."\n");
                                        mtrace ("ITEM CHECKSKILL : ".$item_outcome->itemid."\n");
                                        mtrace ("CHECKSKILL : ".$item_outcome->code_referentiel."\n");
                                        mtrace ("COMPETENCE : ".$item_outcome->code_competence."\n");
                                        mtrace ("OUTCOME : ".$item_outcome->outcome."\n");
                                        mtrace ("OBJECTIF : Id:".$r_outcome->id." Nom:".$r_outcome->fullname."\n");
                                        mtrace ("GRADE ITEM: Num_Cours:".$r_item->courseid.", Nom_Item:".$r_item->itemname.", module:".$r_item->itemmodule.", instance:".$r_item->iteminstance.", Num_Objectif:".$r_item->outcomeid);
                                    }
                                    
                                    $params=array("itemid"=>$r_item->id, "starttime" =>$starttime, "endtime" => $endtime);
                                    $sql = "SELECT id, itemid, userid, usermodified, rawscaleid, finalgrade, timemodified
 FROM {grade_grades} WHERE itemid=:itemid AND ((timemodified>=:starttime)
 AND (timemodified < :endtime)) ORDER BY itemid ASC, timemodified DESC "; // , userid ASC
 
                                    // DEBUG
                                    if   (CHECKSKILL_DEBUG){
                                        mtrace("DEBUG :: ./mod/checkskill/cron_outcomes.php Line 739 ::\nSQL = $sql\n");
                                        print_r($params);
                                    }

									$r_grades_recents=$DB->get_records_sql($sql, $params2);

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
 OLD VERSION *******************************/

 	    		// selectionner les outcomes
				$params=array("fullname" => "$item_outcome->code_referentiel%") ;
				$sql = "SELECT id, shortname, fullname, scaleid
					FROM {grade_outcomes}
					WHERE fullname LIKE :fullname
					ORDER BY fullname ASC ";
				if (OUTCOMES_SUPER_DEBUG){
  					mtrace("\nDEBUG :: Line 517\n");
            		print_r($params);
					mtrace("\nSQL:$sql\n");
  				}

				$r_outcomes=$DB->get_records_sql($sql, $params);
		        if (OUTCOMES_SUPER_DEBUG){
  					mtrace("\nDEBUG :: Line 524\n");
            		print_r($r_outcomes);
					mtrace("\n");
		        }

				if ($r_outcomes){
					$t_outcomes=array();  // liste des objectifs associés à cette occurrence

        			foreach ($r_outcomes as $r_outcome){
						// selectionner les items (activites utilisant ces outcomes)
						$a = new Object();
        		        $a->id=$r_outcome->id;
                		$a->shortname=$r_outcome->shortname;
		                $a->scaleid=$r_outcome->scaleid;
        		        $t_outcomes[$r_outcome->id]=$a;
					}

					$where1='';
					$params1=array();
        		    $params1[]='';  // item module not null
					if (!empty($t_outcomes)){
						foreach ($t_outcomes as $outcome) {
							if (!empty($outcome->id)){
                        		$params1[]=$item_outcome->courseid;
                    			$params1[]=$outcome->id;
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

							if (OUTCOMES_SUPER_DEBUG){
  								mtrace("\nDEBUG :: Line 605 :: PARAMETRES REQUETE \n");
								print_r($params1);
								mtrace("\nSQL : $sql1\n");
          					}

		            		$r_items_ids=$DB->get_records_sql($sql1, $params1);


        		  			if ($r_items_ids){
								// DEBUG
              					if (OUTCOMES_SUPER_DEBUG){
	                				mtrace("DEBUG :: Line 614 :: GRADE_ITEMS IDS Outcomes <br/>\n");
    	            				print_r($r_items_ids);
        		      			}

								// selectionner les grades_grades correspondants
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

									if (OUTCOMES_SUPER_DEBUG){
  										mtrace("\nDEBUG :: Line 644 :: PARAMETRES REQUETE\n");
										print_r($params2);
										mtrace("\nSQL : $sql2\n");
          							}
            						$r_grades_recents=$DB->get_records_sql($sql2, $params2);

									if ($r_grades_recents){
										// rechercher les activites correspondantes
										// drapeau pour eviter de traiter plusieurs fois la même ligne grade_grades
										$t_grades_traites=array();

										foreach ($r_grades_recents as $r_grades_recent) {
          									$params3=array("itemid" => "$r_grades_recent->itemid");
          									$sql3 = "SELECT id, courseid, categoryid, itemname, itemtype, itemmodule, iteminstance, itemnumber, iteminfo, idnumber, calculation, gradetype, grademax, grademin, scaleid, outcomeid, timemodified
 FROM {grade_items}  WHERE id= :itemid ORDER BY courseid, outcomeid ASC ";
											if (OUTCOMES_SUPER_DEBUG){
  												mtrace("\nDEBUG :: Line 652\n");
												print_r($params3);
												mtrace("\nSQL : $sql3\n");
        	  								}
          									$r_item_isole=$DB->get_record_sql($sql3, $params3);

											if ($r_item_isole){
    	                                        if (OUTCOMES_SUPER_DEBUG){
        	                                            mtrace("\nDEBUG :: Line 660");
														print_r($r_item_isole);
                	    	    						mtrace("\n");
												}

												// rechercher toutes les lignes similaires en dhors de la fenetre temporelle
          										$params4=array("courseid" => "$r_item_isole->courseid", "iteminstance" => "$r_item_isole->iteminstance", "scaleid" => $r_item_isole->scaleid, "outcomenull" => "" );
	          									$sql4 = "SELECT id, courseid, categoryid, itemname, itemtype, itemmodule, iteminstance, itemnumber, iteminfo, idnumber, calculation, gradetype, grademax, grademin, scaleid, outcomeid, timemodified
 FROM {grade_items}  WHERE courseid=:courseid AND iteminstance=:iteminstance AND scaleid=:scaleid AND outcomeid!=:outcomenull ORDER BY courseid, outcomeid ASC ";
												if (OUTCOMES_SUPER_DEBUG){
  													mtrace("\nDEBUG :: Line 670\n");
													print_r($params4);
													mtrace("\nSQL : $sql4\n");
	          									}

    	      									$r_items=$DB->get_records_sql($sql4, $params4);

												if (!empty($r_items)){
    	                                        	if (OUTCOMES_SUPER_DEBUG){
        	                                            mtrace("\nDEBUG :: Line 679");
														print_r($r_items);
                	    	    						mtrace("\n");
													}

													$t_items=array();
												 	$where5='';
													$params5=array();
													foreach ($r_items as $r_item) {
														$t_items[$r_item->id]=$r_item;   // stocker l'objet dans un tableau indexe par l'id (jointure itemid de grade_grades à l'etape suivante)
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
														if (OUTCOMES_SUPER_DEBUG){
  															mtrace("\nDEBUG :: \nLine 694\n");
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
                                    									// DEBUG
                                    									if   (CHECKSKILL_DEBUG){
                                        mtrace("DEBUG :: ./mod/checkskill/cron_outcomes.php Line 738\n");
                                        mtrace ("CHECKSKILL INSTANCE : ".$item_outcome->instanceid."\nCOURSE ID: ".$item_outcome->courseid."\n");
                                        mtrace ("DISPLAY : ".$item_outcome->displaytext."\n");
                                        mtrace ("ITEM CHECKSKILL : ".$item_outcome->itemid."\n");
                                        mtrace ("CHECKSKILL : ".$item_outcome->code_referentiel."\n");
                                        mtrace ("COMPETENCE : ".$item_outcome->code_competence."\n");
                                        mtrace ("OUTCOME : ".$item_outcome->outcome."\n");
                                        mtrace ("OBJECTIF : Id:".$r_outcome->id." Nom:".$r_outcome->fullname."\n");
                                        mtrace ("GRADE ITEM: Num_Cours:".$r_item->courseid.", Nom_Item:".$r_item->itemname.", module:".$r_item->itemmodule.", instance:".$r_item->iteminstance.", Num_Objectif:".$r_item->outcomeid);
                                    									}

                                                						// stocker l'activite pour traitement
						                                                $notation=new Object();
                        						                        $notation->instanceid=$item_outcome->instanceid;
                                                						$notation->courseid=$item_outcome->courseid;
						                                                $notation->itemid=$item_outcome->itemid;
                        						                        $notation->code_referentiel=$item_outcome->code_referentiel;
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
	if (CHECKSKILL_DEBUG){
        // DEBUG
        mtrace("\nDEBUG :: checkskill_activite_outcomes :: 705\nDEMANDE DE MISE A JOUR\n");
        print_r($checkskill_object);
    }

    // Cette Checkskill est-elle enregistree ?
    $params=array("id"=>$checkskill_object->checkskill->id,
        "course"=>$checkskill_object->checkskill->course);
        
	$sql = "SELECT * FROM {checkskill} WHERE id=:id AND course=:course";
    if (CHECKSKILL_DEBUG){
        mtrace("\n715 :: SQL:\n$sql\n");
        print_r($params);
	}

	$r_checkskill=$DB->get_record_sql($sql, $params);

    if ($r_checkskill) {
        if (CHECKSKILL_DEBUG){
		  // DEBUG
		  mtrace("\nDEBUG :: checkskill_activite_outcomes :: 697\n");
		  print_r($r_checkskill);
        }

        // Verify item existence
        $params=array("checkskill"=>$checkskill_object->checkskill->id,
            "id"=>$checkskill_object->checkskill_item->id);

    	$sql = "SELECT * FROM {checkskill_item} WHERE id=:id AND checkskill=:checkskill";
        if (CHECKSKILL_DEBUG){
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
            $checkskill_check->teacherid=$checkskill_object->checkskill_check->teacherid;

            // Verifier si cet utilisateur est deja reférencé pour cet ITEM
            $params=array("item"=>$checkskill_object->checkskill_item->id,
                "userid"=>$checkskill_object->checkskill_check->userid);

            $sql = "SELECT * FROM {checkskill_check} WHERE item=:item AND userid=:userid";
            if (CHECKSKILL_DEBUG){
                mtrace("\n728 :: SQL:\n$sql\n");
                print_r($params);
            }
            $checkskill_object_old=$DB->get_record_sql($sql, $params);
            if ($checkskill_object_old) {
                if (CHECKSKILL_DEBUG){
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
                    if (CHECKSKILL_DEBUG){
                        // DEBUG
                        mtrace("\n757 :: MISE A JOUR CHECKSKILL_CHECK\n");
                        print_r($checkskill_check);
                    }
                    $ok=$DB->update_record("checkskill_check", $checkskill_check);

                    if (CHECKSKILL_DEBUG){
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

                if (CHECKSKILL_DEBUG){
                    // DEBUG
                    mtrace("\n780 :: NEW CREATED\n");
                    print_r($checkskill_check);
                }
                $checkskill_check_id=$DB->insert_record("checkskill_check", $checkskill_check);
                if ($checkskill_check_id) {
                    $ok=true;
                }
                if (CHECKSKILL_DEBUG){
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
                if (CHECKSKILL_DEBUG){
                    mtrace("\n810 :: SQL:\n$sql\n");
                    print_r($params);
                }
                $checkskill_comment_old=$DB->get_record_sql($sql, $params);
                if ($checkskill_comment_old) {
                    if (CHECKSKILL_DEBUG){
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
                    if (CHECKSKILL_DEBUG){
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
