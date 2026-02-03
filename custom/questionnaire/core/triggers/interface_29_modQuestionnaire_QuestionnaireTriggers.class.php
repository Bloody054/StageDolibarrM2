<?php
/* Copyright (C) 2017 Mikael Carlavan <contact@mika-carl.fr>
 * Copyright (C) 2022 Julien Marchand <julien.marchand@iouston.com>
 * 
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * \file    core/triggers/interface_99_modQuestionnaire_QuestionnaireTriggers.class.php
 * \ingroup questionnaire
 * \brief   Example trigger.
 *
 */

require_once DOL_DOCUMENT_ROOT.'/core/triggers/dolibarrtriggers.class.php';
require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';
require_once DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/security2.lib.php';
include_once DOL_DOCUMENT_ROOT.'/core/class/html.formmail.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/task.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/stock/class/mouvementstock.class.php';

dol_include_once("/questionnaire/class/questionnaire.class.php");
dol_include_once("/questionnaire/class/questionnaire.action.class.php");

/**
 *  Class of triggers for Questionnaire module
 */
class InterfaceQuestionnaireTriggers extends DolibarrTriggers
{
	/**
	 * @var DoliDB Database handler
	 */
	protected $db;

	/**
	 * Constructor
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;

		$this->name = preg_replace('/^Interface/i', '', get_class($this));
		$this->family = "crm";
		$this->description = "Questionnaire triggers.";
		// 'development', 'experimental', 'dolibarr' or version
		$this->version = '1.0.0';
		$this->picto = 'questionnaire@questionnaire';
	}

	/**
	 * Trigger name
	 *
	 * @return string Name of trigger file
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * Trigger description
	 *
	 * @return string Description of trigger file
	 */
	public function getDesc()
	{
		return $this->description;
	}


	/**
	 * Function called when a Dolibarrr business event is done.
	 * All functions "runTrigger" are triggered if file
	 * is inside directory core/triggers
	 *
	 * @param string 		$action 	Event action code
	 * @param CommonObject 	$object 	Object
	 * @param User 			$user 		Object user
	 * @param Translate 	$langs 		Object langs
	 * @param Conf 			$conf 		Object conf
	 * @return int              		<0 if KO, 0 if no triggered ran, >0 if OK
	 */
	public function runTrigger($action, $object, User $user, Translate $langs, Conf $conf)
	{
        if (empty($conf->questionnaire->enabled)) return 0;     // Module not active, we do nothing

	    // Put here code you want to execute when a Dolibarr business events occurs.
		// Data and type of action are stored into $object and $action

        $langs->load("other");

        switch ($action) {
		    case 'REPONSE_FILL': // New response
		        dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);

                $fk_questionnaire = $object->fk_questionnaire;

                $questionnaireaction = new QuestionnaireAction($this->db);
                $qActions = $questionnaireaction->liste_array($fk_questionnaire);

                if (count($qActions)) {
                    $object->fetch($object->id);


                    foreach ($qActions as $qAction) {
                        if (count($qAction->lines)) {
                            $newObject = null;
                            if ($qAction->type == QuestionnaireAction::SOCIETE_TYPE) {
                                $newObject = new Societe($this->db);
                            } else if ($qAction->type == QuestionnaireAction::PROJECT_TYPE) {
                                $newObject = new Project($this->db);
                            } else if ($qAction->type == QuestionnaireAction::TASK_TIMESPENT_TYPE) {
                                $newObject = new Task($this->db);
                            } else if ($qAction->type == QuestionnaireAction::STOCK_INCREMENT_TYPE) {
                                $newObject = new MouvementStock($this->db);
                            }

                            if ($newObject) {
                                // Try to load object
                                foreach ($qAction->lines as $line) {
                                    if ($line->use_for_fetch && empty($newObject->id)) {
                                        $value = null;
                                        if (isset($object->lines[$line->code])) {
                                            $value = $object->lines[$line->code]->value;
                                        }

                                        if (!empty($value)) {
                                            $sql = "SELECT rowid FROM ".MAIN_DB_PREFIX.$newObject->table_element." WHERE ".$line->field." LIKE '".$this->db->escape($value)."'";
                                            $result = $this->db->query($sql);
                                            if ($result) {
                                                $num = $this->db->num_rows($result);
                                                if ($num) {
                                                    $objp = $this->db->fetch_object($result);
                                                    $id = $objp->rowid;
                                                    $newObject->fetch($id);
                                                }
                                            }
                                        }
                                    }
                                }

                                foreach ($qAction->lines as $line) {
                                    $value = null;
                                    $field = $line->field;
                                    if (isset($object->lines[$line->code])) {
                                        $value = $object->lines[$line->code]->value;
                                    }

                                    if ($value !== null) {
                                        $newObject->{$field} = $value;
                                    }
                                }

                                $url = dol_buildpath('/reponse/card.php?id='.$object->id, 3);

                                $newObject->array_options['options_reponse_url'] = $url;

                                $r = 0;
                                if ($qAction->type == QuestionnaireAction::SOCIETE_TYPE) {
                                    $r = $newObject->id > 0 ? $newObject->update($newObject->id, $user) : $newObject->create($user);

                                    if ($r > 0) {
                                        $object->fk_soc = $newObject->id;
                                        $object->update($user);
                                    }
                                } else if ($qAction->type == QuestionnaireAction::PROJECT_TYPE) {
                                    if (empty($newObject->id)) {

                                        $thirdparty = new Societe($this->db);

                                        if ($object->fk_soc > 0) {
                                            $thirdparty->fetch($object->fk_soc);
                                        }

                                        // Create new ref
                                        $defaultref = '';
                                        $modele = !getDolGlobalString('PROJECT_ADDON') ? 'mod_project_simple' : $conf->global->PROJECT_ADDON;

                                        // Search template files
                                        $file = '';
                                        $classname = '';
                                        $filefound = 0;
                                        $dirmodels = array_merge(array('/'), (array) $conf->modules_parts['models']);
                                        foreach ($dirmodels as $reldir) {
                                            $file = dol_buildpath($reldir."core/modules/project/".$modele.'.php', 0);
                                            if (file_exists($file)) {
                                                $filefound = 1;
                                                $classname = $modele;
                                                break;
                                            }
                                        }

                                        if ($filefound) {
                                            $result = dol_include_once($reldir."core/modules/project/".$modele.'.php');
                                            $modProject = new $classname();

                                            $defaultref = $modProject->getNextValue($thirdparty, $newObject);
                                        }

                                        if (is_numeric($defaultref) && $defaultref <= 0) {
                                            $defaultref = '';
                                        }

                                        $newObject->ref = $defaultref;
                                    }

                                    $r = $newObject->id > 0 ? $newObject->update($user) : $newObject->create($user);
                                    if ($r > 0) {
                                        $object->fk_projet = $newObject->id;
                                        $object->update($user);
                                    }
                                }else if ($qAction->type == QuestionnaireAction::TASK_TIMESPENT_TYPE) {
                                    
                                        $genericuser = new User($this->db);
                                        //on prépare les infos
                                        $newObject->element_type='task';                        
                                        $newObject->datec = dol_now();                                                                   
                                       //convertion de la durée d'heures en secondes
                                       $newObject->timespent_duration =  $newObject->timespent_duration * 3600;
                                       //comment by default with ref of form - answer
                                       if(empty($newObject->timespent_note)){ 
                                            $newObject->timespent_note = $object->questionnaire->ref.' - '.$object->ref;
                                        }else{
                                            $newObject->timespent_note = $object->questionnaire->ref.' - '.$object->ref.' # '.$newObject->timespent_note;
                                        }
                                        //le champ field_user retourne un string. Si plusieusr valeurs, séparés par une virgule.
                                       if(strpos($newObject->field_user,',')>0){
                                            $users= explode(',',$newObject->field_user);
                                        }else{
                                            $users = array($newObject->field_user);//sinon c'est qu'il y a seul utilisateur
                                        }
                                                                                                                                                                                                                                            
                                       //puis on boucle pour ajouter autant de temps passé que d'utilisateurs 
                                       foreach($users as $userid){
                                        $genericuser->fetch($userid);
                                        $newObject->timespent_fk_user=$userid;
                                        $newObject->timespent_thm=$user->thm;
                                        $r = $newObject->addTimeSpent($user);
                                       }                      
                                    
                                }else if ($qAction->type == QuestionnaireAction::STOCK_INCREMENT_TYPE) {
                                    
                                    // D'abord on regarde si on a un champ de produit simple ou un champ de produit multiple
                                    
                                    if(strpos($newObject->fk_product,',')>0){
                                            $pmultiples = preg_replace('/(\{|,)(\d+):/', '$1"$2":', $newObject->fk_product); //remet des guillemets devant les clés numérique pour pouvoir json_decode()
                                            $products = json_decode($pmultiples);
                                        }else{
                                            $products = array($newObject->fk_product);//sinon c'est qu'il y a seul produit
                                        }
                                    //BUG Si une seul ref de produit le mouvement n'est pas créé !!!!                                  
                                    //maintenant on boucle pour incrémenter en stock
                                    foreach($products as $pid=>$qty){
                                        $product = new Product($this->db);
                                        $product->fetch($fk_product);
                                        $fk_product = $pid;
                                        $entrepot_id = $newObject->fk_entrepot;
                                        $type = 3; //stock increase
                                        $price = $product->cost_price; 
                                        $label = $object->questionnaire->ref.' - '.$object->ref.' # Ajout en stock';
                                        $inventorycode = $object->questionnaire->ref.'@'.dol_print_date(dol_now(), 'dayhourlog');
                                        $datem = $newObject->datem;
                                        $eatby = '' ;
                                        $sellby = ''; 
                                        $batch = '' ;
                                        $skip_batch = false ;
                                        $id_product_batch = 0;
                                        $disablestockchangeforsubproduct = 0 ;
                                        $donotcleanemptylines = 0;
                                        $origin_element='reponse';
                                        $origin_id=$object->id;
                                        $newObject->_create($user, $fk_product, $entrepot_id, $qty, $type, $price, $label, $inventorycode, $datem, $eatby, $sellby, $batch, $skip_batch, $id_product_batch, $disablestockchangeforsubproduct, $donotcleanemptylines);
                                        $newObject->setOrigin($origin_element, $origin_id);
                                    }      
                                    
                                }
                            }
                        }
                    }
                }

                if ($object->fk_soc > 0 && $object->fk_projet > 0) {
                    // Link project to society
                    $newObject = new Project($this->db);
                    $newObject->fetch($object->fk_projet);
                    $newObject->socid = $object->fk_soc;
                    $newObject->update($user);
                }
			break;
            
            // case 'LINEREPONSE_UPDATE': //Mise à jour d'une réponse !

            // $fk_questionnaire = $object->fk_questionnaire;
            //     $questionnaireaction = new QuestionnaireAction($this->db);
            //     $qActions = $questionnaireaction->liste_array($fk_questionnaire);
            //     if (count($qActions)) {
            //         $object->fetch($object->id);
            //         foreach ($qActions as $qAction) {
            //             if (count($qAction->lines)) {
            //                 $newObject = null;
            //                 if ($qAction->type == QuestionnaireAction::SOCIETE_TYPE) {
            //                     $newObject = new Societe($this->db);
            //                 } else if ($qAction->type == QuestionnaireAction::PROJECT_TYPE) {
            //                     $newObject = new Project($this->db);
            //                 } else if ($qAction->type == QuestionnaireAction::TASK_TIMESPENT_TYPE) {
            //                     $newObject = new Task($this->db);
            //                 } else if ($qAction->type == QuestionnaireAction::STOCK_INCREMENT_TYPE) {
            //                     $newObject = new MouvementStock($this->db);
            //                 }

            //             }
            //         }
            //     }

            // echo '<pre>';
            //      print_r($object);
            //     echo '</pre>';
            //     exit;
                    


            // break;
		}

		return 0;
	}

}
