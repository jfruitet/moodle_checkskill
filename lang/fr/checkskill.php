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

$string['addcomments'] = 'Ajouter des commentaires';

$string['additem'] = 'Ajouter';
$string['additemalt'] = 'Ajouter un nouvel item à la liste';
$string['additemhere'] = 'Insérer le nouvel item après celui-ci';
$string['addownitems'] = 'Ajouter vos propres items';
$string['addownitems-stop'] = 'Arr&ecirc;t d\'ajout d\'items';

$string['allowmodulelinks'] = 'Autoriser les liens vers les éléments';

$string['anygrade'] = 'Tout';
$string['autopopulate'] = 'Montrer les éléments du cours dans la liste des t&acirc;ches';
$string['autopopulate_help'] = 'Cela ajoutera automatiquement une liste de toutes les ressources et les activités dans le cadre actuel dans la liste. <br />
Cette liste sera mise à jour avec tous les changements en cours, lorsque vous visitez la page "Modifier" pour la liste des t&acirc;ches. <br />
Les items peuvent &ecirc;tre cachés dans la liste, en cliquant sur l\'ic&ocirc;ne "Cacher" à c&ocirc;té d\'eux.<br />
Pour supprimer les items automatiques de la liste, modifier cette option en cliquant sur "Non", puis cliquez sur "Supprimer des éléments de cours" sur la page "Modifier".';
$string['autoupdate'] = 'Cochez quand les modules sont complets';
$string['autoupdate_help'] = 'Cela va automatiquement cocher les éléments de votre liste des t&acirc;ches lorsque vous terminez l\'activité concernée dans le cours. <br />
"Finir" une activité varie d\'une activité à l\'autre - "voir" une ressource, "envoyer" un quiz ou un fichier, "répondre" à un forum ou participez à un chat, etc <br />
Si un suivi de fin de Moodle 2.0 est activé pour une activité particulière, il sera utilisé pour les cocher l\'élément dans la liste <br />
Pour plus de détails sur la cause exacte qu\'une activité peut &ecirc;tre marqué comme "achevée", demandez à votre administrateur du site pour regarder dans le fichier "mod/checkskill/autoupdate.php" <br />
Remarque: cela peut prendre jusqu\'à 60 secondes pour que l\'activité d\'un étudiant se mette à jour dans leur liste des t&acirc;ches';
$string['autoupdatenote'] = 'Ce sont les cases cochées \'étudiant\' qui sont mises à jour automatiquement - aucune mise à jour concernant les listes pour \'enseignants seulement\' ne sont affichées';

$string['autoupdatewarning_both'] = 'Il y a des items sur cette liste qui seront automatiquement mis à jour (comme ceux que les étudiants disent "complet"). Cependant, dans le cas d\'une liste des t&acirc;ches commune "étudiant et enseignant", les barres de progression ne seront pas mises à jour tant qu\'un enseignant accepte les notes attribuées.';
$string['autoupdatewarning_student'] = 'Il y a des items sur cette liste qui seront automatiquement mis à jour (comme ceux que les étudiants disent "complet").';
$string['autoupdatewarning_teacher'] = 'La mise à jour automatique a été activée pour cette liste, mais ces remarques ne seront pas affichée tant que l\'enseignant ne les montre pas.';

$string['canceledititem'] = 'Arrêter la saisie';

$string['calendardescription'] = 'Cet élément a été ajouté par la liste des t&acirc;ches : {$a}';

$string['changetextcolour'] = 'Prochaine couleur de texte';

$string['checkeditemsdeleted'] = 'Items de la liste des t&acirc;ches supprimés';

$string['checkskill'] = 'Liste de compétences';
$string['pluginadministration'] = 'Administration de la liste des compétences';

$string['checkskill:edit'] = 'Créer et éditer des liste des compétences';
$string['checkskill:emailoncomplete'] = 'Recevoir par mail quand c\'est complet';
$string['checkskill:preview'] = 'Prévisualisation d\'une liste des compétences';
$string['checkskill:updatelocked'] = 'Mise à jour des marques verrouillée';
$string['checkskill:updateother'] = 'Mise à jour des marques des liste des compétences des étudiants';
$string['checkskill:updateown'] = 'Mise à jour de vos marques des liste des compétences';
$string['checkskill:viewreports'] = 'Voir la progression des étudiants';

$string['checkskillautoupdate'] = 'Autoriser les liste des compétences à se mettre à jour automatiquement';

$string['checkskillfor'] = 'Liste des compétences pour';

$string['checkskillintro'] = 'Introduction';
$string['checkskillsettings'] = 'Paramètres';

$string['checks'] = 'Marques';
$string['comments'] = 'Commentaires';

$string['completionpercentgroup'] = 'A cocher obligatoirement';
$string['completionpercent'] = 'Pourcentage d\'items qui doivent &ecirc;tre cochés :';

$string['configcheckskillautoupdate'] = 'Avant de permettre cela, vous devez faire quelques modifications au code Moodle, merci de voir le "mod / liste / README.txt" pour plus de détails';
$string['configshowcompletemymoodle'] = 'Si non coché les liste de compétences seront masquées dans la page \'Mon Moodle\'';
$string['configshowmymoodle'] = 'Si non coché les activité Checkskill (et leurs barres de progression) n\'apparaitront plus sur la page  \'Mon Moodle\'';

$string['confirmdeleteitem'] = 'Etes-vous s&ucirc;r de vouloir effacer définitivement cet item de la liste des compétences ?';

$string['deleteitem'] = 'Effacer cet item';

$string['duedatesoncalendar'] = 'Ajouter les dates d\'échéance au calendrier';

$string['edit'] = 'Editer la liste des compétences';
$string['editchecks'] = 'Editer les coches';
$string['editdatesstart'] = 'Editer les dates';
$string['editdatesstop'] = 'Arr&ecirc;t de l\édition des dates';
$string['edititem'] = 'Editer cet item';

$string['emailoncomplete'] = 'Envoyer un courriel quand la liste de compétences et de tâches est complète';
$string['emailoncomplete_help'] = 'Quand une liste est complète, un courriel de notification est envoyé soit à l\'étudiant qui l\'a achevée, soit à tous les enseignants du cours soit aux deux. <br />
Un administrateur peut contr&ocirc;ler qui re&ccedil;oit ce courriel en utilisant la capacité "mod:checkskill/emailoncomplete" - par défaut, tous les enseignants et enseignants non éditeurs ont cette capacité.';
$string['emailoncompletesubject'] = 'L\'utilisateur {$a->user} a complété sa liste de compétence \'{$a->checkskill}\'';
$string['emailoncompletesubjectown'] = 'Vous avez complété la liste de compétences et de tâches \'{$a->checkskill}\'';
$string['emailoncompletebody'] = 'L\'utilisateur {$a->user} a complété sa liste de compétences et de tâches \'{$a->checkskill}\' dans le cours \'{$a->coursename}\'
Voir la liste des compétences ici :';
$string['eventcheckskillcomplete'] = 'Cett liste de compétences et de tâches est complète';
$string['eventeditpageviewed'] = 'Editer la page affichée';
$string['eventreportviewed'] = 'Rapport affiché';
$string['eventstudentchecksupdated'] = 'Coches de l\'étudiant mises à jour';
$string['eventstudentdescriptionupdated'] = 'Description d\'un item de CheckSkill mise à jour';
$string['eventteacherchecksupdated'] = 'Coches de l\'enseignant mises à jour';

$string['export'] = 'Exporter des items';

$string['forceupdate'] = 'Mise à jour des coches pour les items automatiques';

$string['gradetocomplete'] = 'Evaluation pour terminer';
$string['guestsno'] = 'Vous n\'avez pas la permission de voir cette liste des compétences';

$string['headingitem'] = 'Cet item est une étiquette, il n\'y aura pas de case à cocher à c&ocirc;té';

$string['import'] = 'Importer des items';
$string['importfile'] = 'Choisir le fichier à importer';
$string['importfromsection'] = 'Section courante';
$string['importfromcourse'] = 'Tout le cours';
$string['indentitem'] = 'Décaller l\'item';
$string['itemcomplete'] = 'Terminé';
$string['items'] = 'Items de la liste des compétences';

$string['linktomodule'] = 'Lien de la ressource ou de l\'activité';

$string['lockteachermarks'] = 'Verrouillage des coches de l\'enseignant';
$string['lockteachermarks_help'] = 'Lorsque ce paramètre est activé, une fois qu\'un enseignant a sauvé une coche "Oui", il ne sera plus possible de changer la valeur. Les utilisateurs ayant la capacité "mod/checkskill:updatelocked" sera toujours en mesure de changer la coche.';
$string['lockteachermarkswarning'] = 'Remarque: Une fois que vous avez enregistré ces coches, il vous sera impossible de changer toutes les coches "Oui"';

$string['modulename'] = 'Liste de compétences';
$string['modulenameplural'] = 'Listes de compétences';

$string['moveitemdown'] = 'Descendre l\'item';
$string['moveitemup'] = 'Monter l\'item';

$string['noitems'] = 'Pas d\'items dans la liste des compétences';

$string['optionalitem'] = 'Cet item est optionnel';
$string['optionalhide'] = 'Cacher les options des items';
$string['optionalshow'] = 'Montrer les options des items';

$string['percentcomplete'] = 'Items obligatoires';
$string['percentcompleteall'] = 'Tous les items';
$string['pluginname'] = 'CheckSkill';
$string['preview'] = 'Prévisualisation';
$string['progress'] = 'Progression';

$string['removeauto'] = 'Supprimer les items des éléments du cours';

$string['report'] = 'Voir la progression';
$string['reporttablesummary'] = 'Tableau montrant les éléments de la liste que chaque étudiant a terminé';

$string['requireditem'] = 'Cet item est requis';

$string['resetcheckskillprogress'] = 'Réinitialiser la progression et les items de l\'utilisateur';

$string['savechecks'] = 'Sauvegarder';

$string['showcompletemymoodle'] = 'Afficher les listes achevées sur la page  \'Mon Moodle\'';
$string['showfulldetails'] = 'Afficher tous les détails';
$string['showmymoodle'] = 'Afficher les listes achevées sur la page \'Mon Moodle\'';
$string['showprogressbars'] = 'Afficher les barres de progression';

$string['teachercomments'] = 'Les enseignants peuvent ajouter des commentaires';
$string['teacherdate'] = 'Date à laquelle un enseignant a mis à jour cet item';
$string['teacheredit'] = 'Mises à jour par';
$string['teacherid'] = 'The teacher who last updated this mark';

$string['teachermarkundecided'] = 'L\'enseignant n\'a pas encore coché cet item';
$string['teachermarkyes'] = 'L\'enseignant confirme que cet item est achevé';
$string['teachermarkno'] = 'L\'enseignant confirme pas que vous n\'avez pas achevé cette item';

$string['teachernoteditcheck'] = 'Seulement l\'étudiant';
$string['teacheroverwritecheck'] = 'Seulement l\'enseignant';
$string['teacheralongsidecheck'] = 'Etudiant et Enseignant';

$string['toggledates'] = 'Inverser les dates';
$string['togglecolumn'] = 'Inverser les colonnes';
$string['togglerow'] = 'Inverser les lignes';
$string['theme'] = 'Thème graphique pour afficher la liste des compétences';


$string['updatecompletescore'] = 'Sauvegarder les notes d\'achèvement';
$string['unindentitem'] = 'Item non indenté';
$string['updateitem'] = 'Mise à jour';
$string['userdate'] = 'Date de mise à jour de cet item';
$string['useritemsallowed'] = 'L\'utilisateur peut ajouter ses propres items';
$string['useritemsdeleted'] = 'Items de l\'utilisateur supprimés';

$string['view'] = 'Voir la liste des compétences';
$string['viewall'] = 'Voir tous les étudiants';
$string['viewallcancel'] = 'Effacer';
$string['viewallsave'] = 'Sauvegarder';

$string['viewsinglereport'] = 'Voir la progression de cet utilisateur';
$string['viewsingleupdate'] = 'Mettre à jour la progression de cet utilisateur';

$string['yesnooverride'] = 'Oui ne peut pas remplacer';
$string['yesoverride'] = 'Oui, peut remplacer';

// Checkskill specific
// Begin help strings for Moodle2
$string['modulename_help'] = '"CheckSkill" est un module Moodle dérivé du plugin Checklist et destiné à implanter une activité de type validation de listes de compétences et de tâches.

Ce module permet :

* de créer des listes de tâches et de compétences (ou de les télécharger) ;

* de valider / commenter / fournir des preuves de la réalisation de ces tâches et compétences ;

* Quand le cours active des Objectifs (Outcomes), l\'importation d\'un fichier d\'Objectifs (au format CSV) comme liste de compétences fera remonter dans CheckSkill les évaluations par objectifs faites dans les activités Moodle (forum, BD, devoir, etc.) du cours.';
$string['modulename_link'] = 'mod/checkskill/view';

$string['a_completer'] = 'A COMPLETER';
$string['add_link'] = 'Ajouter un lien ou un document';
$string['addreferentielname'] = 'Saisir un code de référentiel ';
$string['argumentation'] = 'Argumentation';
$string['checkskill_check'] = 'Evaluation ';
$string['checkskill_description'] = 'Autoriser le dépôt de fichiers';
$string['clicktopreview'] = 'cliquez pour un aperçu pleine taille dans un fenêtre surgissante';
$string['clicktoselect'] = 'cliquez pour sélectionner la vue';
$string['commentby'] = 'Commenté par ';
$string['config_description'] = 'Permet aux utilisateurs de déposer des documents comme trace de pratique.';
$string['config_outcomes_input'] = 'Permet d\'importer dans Checkskill les objectifs validés dans les activités Moodle du cours';
$string['confirmreferentielname'] = 'Confirmer le code de référentiel ';
$string['delete_description'] = 'Supprimer la description';
$string['delete_document'] = 'Supprimer un document';
$string['delete_link'] = 'Supprimer un lien';
$string['description_document'] = 'Information sur le document';
$string['description'] = 'Rédigez votre argumentaire';
$string['descriptionh_help'] = 'Indiquez de façon succincte les motifs qui vous permettent d\'affirmer que cette tâche est achevée ou la compétence acquise.';
$string['descriptionh'] = 'Aide pour l\'argumentation';
$string['doc_num'] = 'Document N°{$a} ';
$string['document_associe'] = 'Document associé';
$string['documenth'] = 'Aide pour les documents associés';
$string['documenth_help'] = 'Les documents attachés à une description sont destinés à fournir
des traces observables de votre pratique.

A chaque Item vous pouvez associer une description et un ou plusieurs documents, soit en recopiant son adresse Web (URL),
soit en déposant un fichier dans l\'espace Moodle du cours.

* Description du document : Une courte notice d\'information.

* URL : Adresse Web du document (ou fichier déposé par vos soins dans l\'espace Moodle).

* Titre ou étiquette

* Fenêtre cible où s\'ouvrira le document';
$string['edit_description'] = 'Editer la description';
$string['edit_document'] = 'Editer le document';
$string['edit_link'] = 'Editer un lien';
$string['error_action'] = 'Erreur : Action invalide - "{a}"';
$string['error_checkskill_id'] = 'Checkskill ID incorrect';
$string['error_cm'] = 'Course Module incorrect';
$string['error_cmid'] = 'Course Module ID incorrect';
$string['error_course'] = 'Course ID incorrect';
$string['error_export_items'] = 'Vous n\'êtes pas autorisé à exporter des items dans cette  Liste de compétences';
$string['error_file_upload'] = 'Erreur au chargement du fichierd';
$string['error_import_items'] = 'Vous n\'êtes pas autorisé à importer des items dans cette  Liste de compétencesl';
$string['error_insert_db'] = 'Insertion d\'un item impossible dans la base de données';
$string['error_itemskill'] = 'Erreur : liste d\'items invalide ou absente';
$string['error_number_columns_outcomes'] = 'Cette ligne d\'Objectifs a un nombre incorrect de colonnes :<br />{$a}';
$string['error_number_columns'] = 'Nombre de colonnes incorrect pour cette ligne : <br />{$a}';
$string['error_select'] = 'Erreur: Veuillez sélectionner au moins un Item';
$string['error_sesskey'] = 'Erreur : Clé de session invalide';
$string['error_specif_id'] = 'Vous devez spécifier un course_module ID ou un instance ID';
$string['error_update'] = 'Erreur: Vous n\'êtes pas autorisé à mettre à jour cette Liste de compétences';
$string['error_user'] = 'Compte utilisateur inexistant !';
$string['export_outcomes'] = 'Exporter des objectifs';
$string['id'] = 'ID# ';
$string['import_outcomes'] = 'Importer des objectifs';
$string['input_description'] = 'Rédigez votre argumentaire';
$string['items_exporth_help'] = 'Les items sélectionnés seront exportés dans le même fichier d\'Objectifs.';
$string['items_exporth'] = 'Item exportés';
$string['mustprovideexportformat'] = 'Vous devez fournir un formaat d\'export';
$string['mustprovideinstanceid'] = 'Vous devez fournir un identifiant d\'instance';
$string['mustprovideuser'] = 'Vous devez fournir un identifiant d\'utilisateur';
$string['nomaharahostsfound'] = 'Aucun hôte Mahara n\'a été trouvé.';
$string['noviewscreated'] = 'Vous n\'avez créé aucune vue dans {$a}.';
$string['noviewsfound'] = 'Aucune vue ne correspond dans {$a}';
$string['OK'] = 'OK';
$string['old_comment'] = 'Commentaire antérieur:';
$string['outcome_date'] =  ' Date : ';
$string['outcome_description'] = 'Description';
$string['outcome_link'] = ' <a href="{$a->link}">{$a->name}</a> ';
$string['outcome_name'] = 'Nom d\'objectif';
$string['outcome_shortname'] = 'Code de compétence';
$string['outcome_type'] = 'Activité';
$string['outcomes_input'] = 'Activer les fichiers d\'objectifs';
$string['outcomes'] = 'outcomes'; // NE PAS TRADUIRE
$string['previewmahara'] = 'Aperçu';
$string['quit'] = 'Quitter';
$string['referentiel_codeh_help'] = 'Le code de référentiel (une chaîne de caractères non accentués sans virgule ni sans espace) permet d\'identifier les compétences (outcomes) participant du même référentiel de compétences. <br />Quand les intitulés d\'Items ne sont pas discriminants cocher <i>"Utiliser l\'ID de l\'Item comme clé"</i>';
$string['referentiel_codeh'] = 'Aide pour la saisie d\'un code de référentiel';
$string['scale_description'] = 'Ce barème est destiné à évaluer l\'acquisition d\'objectifs de compétences.';
$string['scale_items'] = 'Non pertinent,Non validé,Validé';
$string['scale_name'] = 'Item référentiel';
$string['select_all'] = 'Tout cocher';
$string['select_items_export'] = 'Sélectionnez des items à exporter';
$string['select_not_any'] = 'Tout décocher';
$string['select'] = 'Sélectionner';
$string['selectedview'] = 'Page soumissionnée';
$string['selectexport'] = 'Exporter Objectifs';
$string['selectmaharaview'] = 'Sélectionnez dans cette liste l\'une des vues de votre portfolio <i>{$a->name}</i> ou <a href="{$a->jumpurl}">cliquez ici</a> pour créer directement une nouvelle vue sur <i>{$a->name}</i>.';
$string['site_help'] = 'Ce paramètre vous permet de sélectionner depuis quel site Mahara vos étudiants pourront soumettre leurs pages. (Ce site Mahara doit être déjà configuré pour fonctionner en réseau MNET avec ce site Moodle.)';
$string['site'] = 'Site';
$string['submission'] = 'Soumission';
$string['target'] = 'Ouvrir ce lien dans une nouvelle fenêtre';
$string['teachermark'] = 'Appréciation ';
$string['teachertimestamp'] = 'Evalué le ';
$string['timecreated'] = 'Créé le ';
$string['timemodified'] = 'Modifié le ';
$string['title'] = 'Titre du document';
$string['titlemahara'] = 'Titre';
$string['typemahara'] = 'Portfolio Mahara';
$string['unknowdescription'] = 'Aucune description';
$string['upload_portfolio'] = 'Lier à une page de mon portfolio';
$string['url'] = 'URL';
$string['urlh_help'] = 'Vous pouvez copier / coller un lien <br />(commençant par "http://"" ou par "https://"") directement dans le champ URL ou bien vous pouvez télécharger un fichier depuis votre poste de travail';
$string['urlh'] = 'Sélection d\'un lien Web';
$string['useitemid'] = 'Utiliser l\'ID de l\'Item comme clé ';
$string['usertimestamp'] = 'Réclamé le ';
$string['viewmahara'] = 'Vue Mahara';
$string['views'] = 'Vues ';
$string['viewsby'] = 'Vues proposées par {$a}';
