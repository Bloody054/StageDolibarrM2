<?php
/* Copyright (C) 2004-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2017 Mikael Carlavan <contact@mika-carl.fr>
 * Copyright (C) 2024 Julien Marchand <julien.marchand@iouston.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *  \file       htdocs/reponse/class/reponse.class.php
 *  \ingroup    reponse
 *  \brief      File of class to manage slices
 */
require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';
require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/security.lib.php';

include_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
include_once DOL_DOCUMENT_ROOT.'/core/lib/price.lib.php';

if (!empty($conf->datamatrix->enabled))
{
	dol_include_once("/datamatrix/class/datamatrix.class.php");
}

if (!empty($conf->questionnaire->enabled))
{
	dol_include_once("/questionnaire/class/questionnaire.class.php");
	dol_include_once("/questionnaire/class/html.form.questionnaire.class.php");
}

/**
 * Class to manage products or services
 */
class Reponse extends CommonObject
{
	public $element='reponse';
	public $table_element='reponse';
	public $fk_element='fk_reponse';
	public $picto = 'reponse@reponse';
	public $ismultientitymanaged = 1;	// 0=No test on entity, 1=Test with field entity, 2=Test with link by societe

	/**
	 * {@inheritdoc}
	 */
	protected $table_ref_field = 'rowid';

	
	/**
     * Gestion id
     * @var int
     */
	public $id = 0;

	/**
	 * Reference
	 * @var string
	 */
	public $ref;

	/**
	 * Form id
	 * @var int
	 */
	public $fk_questionnaire = 0;

    /**
     * Soc id
     * @var int
     */
    public $fk_soc = 0;

    /**
     * Proj id
     * @var int
     */
    public $fk_projet = 0;

	/**
	 * Creation date
	 * @var int
	 */
	public $datec;

	/**
	 * Author id
	 * @var int
	 */
	public $user_author_id = 0;

	/**
     * Entity
     * @var int
     */
	public $entity;

	/**
     * Active
     * @var int
     */
	public $active = 1;

	/**
     * Draft
     * @var int
     */
	public $is_draft = 0;

    /**
     * Sent or not
     * @var int
     */
    public $envoi_ar = 0;

    /**
     * Note
     * @var string
     */
    public $note_private = null;

	/**
	 * @var ReponseLine[]
	 */
	public $lines = array();

	/**
     * Form
     * @var Questionnaire
     */
	public $questionnaire = null;


	/**
     * Email address of reponse
     * @var string
     */
	public $email = null;

	/**
     * Date of reponse
     * @var int
     */
	public $date = null;

	/**
     * Name of user
     * @var string
     */
	public $name = null;

    /**
     * Location of user
     * @var string
     */
    public $location = null;

    /**
	 * Origin
	 * @var string
	 */
	public $origin;

	/**
	 * Origin id
	 * @var int
	 */
	public $origin_id = 0;


	/**
	 *  Constructor
	 *
	 *  @param      DoliDB		$db      Database handler
	 */
	function __construct($db)
	{
		global $langs;

		$this->db = $db;
	}

    /**
     *	Insert reponse into database
     *
     *	@param	User	$user     		User making insert
     *  @param  int		$notrigger	    0=launch triggers after, 1=disable triggers
     *
     *	@return int			     		Id of gestion if OK, < 0 if KO
     */
    function clonefrom($user, $notrigger=0)
    {
        global $conf, $langs, $mysoc;

        $error=0;
        $oldlines = $this->lines;

        dol_syslog(get_class($this)."::create", LOG_DEBUG);

        $this->is_draft = 0;

        $id = $this->create($user);

        if ($id > 0)
        {
            $this->fetch_lines();

            $error = 0;

            $lines = $this->lines;
            //dol_syslog(get_class($this)."::fill serialize post= ".json_encode($post), LOG_DEBUG);

            if (count($lines)) {
                foreach ($lines as $code => $val) {
                    $line = isset($oldlines[$code]) ? $oldlines[$code] : null;
                    $l = new ReponseLine($this->db);

                    $l->fk_reponse   = $this->id;
                    $l->code 			 = $line->code;
                    $l->type 			 = $line->type;
                    $l->crypted 		= $line->crypted;
                    $l->param            = $line->param;

                    $l->value = $line->value;
                    $l->formatted_value = $line->formatted_value;

                    $l->update($user, $notrigger);
                }
            }

        }
        else
        {
            $error++;
            $this->error='ErrorFailedToGetInsertedId';
        }

        if (! $error)
        {
            if (! $notrigger)
            {
                // Call trigger
                $result = $this->call_trigger('REPONSE_CLONE',$user);
                if ($result < 0) $error++;
                // End call triggers
            }
        }

        if (! $error)
        {
            return $this->id;
        }
        else
        {
            return -$error;
        }
    }


    /**
	 *	Insert reponse into database
	 *
	 *	@param	User	$user     		User making insert
	 *  @param  int		$notrigger	    0=launch triggers after, 1=disable triggers
	 * 
	 *	@return int			     		Id of gestion if OK, < 0 if KO
	 */
	function create($user, $notrigger=0)
	{
		global $conf, $langs, $mysoc;

        $error=0;

		dol_syslog(get_class($this)."::create", LOG_DEBUG);

		$this->db->begin();

		$this->datec = dol_now();
		$this->entity = $conf->entity;
		$this->user_author_id = $user->id;
		$this->ref = $this->getNextNumRef($mysoc);
        $this->envoi_ar = 0;

		$now = dol_now();

		$sql = "INSERT INTO ".MAIN_DB_PREFIX."reponse (";
		$sql.= " ref";
		$sql.= " , fk_questionnaire";
        $sql.= " , fk_soc";
        $sql.= " , fk_projet";
		$sql.= " , datec";
		$sql.= " , user_author_id";
        $sql.= " , origin_id";
		$sql.= " , origin";
        $sql.= " , envoi_ar";
        $sql.= " , is_draft";
		$sql.= " , entity";
		$sql.= " , tms";
		$sql.= ") VALUES (";
		$sql.= " ".(!empty($this->ref) ? "'".$this->db->escape($this->ref)."'" : "null");
		$sql.= ", ".(!empty($this->fk_questionnaire) ? $this->fk_questionnaire : "0");
        $sql.= ", ".(!empty($this->fk_soc) ? $this->fk_soc : "0");
        $sql.= ", ".(!empty($this->fk_projet) ? $this->fk_projet : "0");
        $sql.= ", ".(!empty($this->datec) ? "'".$this->db->idate($this->datec)."'" : "null");
		$sql.= ", ".(!empty($this->user_author_id) ? $this->user_author_id : "0");
		$sql.= ", ".(!empty($this->origin_id) ? $this->origin_id : "0");
		$sql.= ", ".(!empty($this->origin) ? "'".$this->db->escape($this->origin)."'" : "null");
        $sql.= ", ".(!empty($this->envoi_ar) ? $this->envoi_ar : "0");
        $sql.= ", ".($this->is_draft > 0 ? "1" : "0");
		$sql.= ", ".(!empty($this->entity) ? $this->entity : "0");
		$sql.= ", '".$this->db->idate($now)."'";
		$sql.= ")";

		dol_syslog(get_class($this)."::Create", LOG_DEBUG);
		$result = $this->db->query($sql);
		if ( $result )
		{
			$id = $this->db->last_insert_id(MAIN_DB_PREFIX."reponse");
			
			if ($id > 0)
			{
				$this->id = $id;

                $sql = "INSERT INTO ".MAIN_DB_PREFIX."questionnairefval".$this->fk_questionnaire." (";
                $sql.= " fk_reponse";
                $sql.= " , tms";
                $sql.= ") VALUES (";
                $sql.= " ".(!empty($this->id) ? $this->id : "0");
                $sql.= ", '".$this->db->idate($now)."'";
                $sql.= ")";

                dol_syslog(get_class($this)."::Create", LOG_DEBUG);
                $this->db->query($sql);

				$sql = "INSERT INTO ".MAIN_DB_PREFIX."questionnaireval".$this->fk_questionnaire." (";
				$sql.= " fk_reponse";
				$sql.= " , tms";
				$sql.= ") VALUES (";
				$sql.= " ".(!empty($this->id) ? $this->id : "0");
				$sql.= ", '".$this->db->idate($now)."'";
				$sql.= ")";

				dol_syslog(get_class($this)."::Create", LOG_DEBUG);
				$result = $this->db->query($sql);
				if ( $result )
				{
					$id = $this->db->last_insert_id(MAIN_DB_PREFIX."questionnaireval".$this->fk_questionnaire);
		
					if ($id <= 0)
					{
						$error++;
						$this->error='ErrorFailedToGetInsertedId';
					}
				}
				else
				{
					$error++;
					$this->error=$this->db->lasterror();
				}

			}
			else
			{
				$error++;
				$this->error='ErrorFailedToGetInsertedId';
			}
		}
		else
		{
			$error++;
			$this->error=$this->db->lasterror();
		}

		if (! $error)
		{
			$result = $this->insertExtraFields();
			if ($result < 0) $error++;
		}
		
		if (!$error)
		{
			if (!empty($conf->datamatrix->enabled))
			{
				$ref = dol_sanitizeFileName($this->ref);

				$dir = $conf->reponse->dir_output . "/" . $ref ;
				dol_mkdir($dir);
				$file = $conf->reponse->dir_output . "/" . $ref . "/" . $ref . ".png";

                DataMatrix::createBarcode($this->ref, $file);
			}
		}

		if (! $error)
		{
			if (! $notrigger)
			{
	            // Call trigger
	            $result = $this->call_trigger('REPONSE_CREATE',$user);
	            if ($result < 0) $error++;
	            // End call triggers
			}
		}

		if (! $error)
		{
			$this->db->commit();
			return $this->id;
		}
		else
		{
			$this->db->rollback();
			return -$error;
		}

	}

	/**
	 *	Add line.
	 *
	 *	@param  User	$user       Object user making update
	 *  @param  int		$notrigger	    0=launch triggers after, 1=disable triggers
	 *	@return int         		1 if OK, -1 if ref already exists, -2 if other error
	 */
	function updateline($line, $notrigger=0)
	{
		global $langs, $conf, $hookmanager, $user;
		
		$questionnaire = new Questionnaire($this->db);
        $questionnaireform = new QuestionnaireForm($this->db);

		$line->fk_reponse = $this->id;
		$line->value = isset($this->lines[$line->code]) ? $this->lines[$line->code]->value : '';

		list($value, $uncrypted_value) = $questionnaire->processline($line);

		$l = new ReponseLine($this->db);
		$l->fk_reponse   = $this->id;
		$l->code 			 = $line->code;
		$l->value            = $value;
        $l->type 			 = $line->type;
        $l->crypted 		= $line->crypted;
        $l->param            = $line->param;
        $formatted_value =  $l->type != 'file' ? $questionnaireform->valueField($l) : $value;

        $l->formatted_value = $formatted_value;

        return $l->update($user, $notrigger);

	}

	/**
	 *	Add line.
	 *
	 *  @param  int		$notrigger	    0=launch triggers after, 1=disable triggers
	 *	@return int         		1 if OK, -1 if ref already exists, -2 if other error
	 */
	function addline($line, $notrigger=0)
	{
		global $langs, $conf, $hookmanager, $user;

		$l = new ReponseLine($this->db);
		$questionnaire = new Questionnaire($this->db);
		$questionnaireform = new QuestionnaireForm($this->db);

		list($value, $uncrypted_value) = $questionnaire->processline($line);

		$l->fk_reponse   = $this->id;
		$l->code 			 = $line->code;
        $l->type 			 = $line->type;
        $l->crypted 		= $line->crypted;
        $l->param            = $line->param;

		$l->value = $value;
        $formatted_value = $l->type != 'file' ? $questionnaireform->valueField($l) : $value;

        $l->formatted_value = $formatted_value;

        return $l->update($user, $notrigger);
	}
		
		
	/**
	 *	Update location
	 *
	 *	@return int         		1 if OK, -1 if ref already exists, -2 if other error
	 */

	function update_gps($base, $notrigger=0)
	{
	   global $user, $langs, $conf;

	   $questionnaire = new Questionnaire($this->db);
	   $questionnaireform = new QuestionnaireForm($this->db);

	   $lines = $this->lines;
	   
	   $ville = '';
	   $departement = ''; 
	   $region = '';
	   $pays = ''; 

	   $value = '';

	   foreach ($lines as $line) {

		   if ($line->code == $base.'_ville') {
			   list($dum, $value) = $questionnaire->processline($line);

			   $line->value = $value;
			   $ville = $questionnaireform->valueField($line);
		   }

		   if ($line->code == $base.'_departement') {
			   list($dum, $value) = $questionnaire->processline($line);

			   $line->value = $value;
			   $departement = $questionnaireform->valueField($line);
		   }

		   if ($line->code == $base.'_region') {
			   list($dum, $value) = $questionnaire->processline($line);

			   $line->value = $value;
			   $region = $questionnaireform->valueField($line);
		   }

		   if ($line->code == $base.'_pays') {
			   list($dum, $value) = $questionnaire->processline($line);

			   $line->value = $value;
			   $pays = $questionnaireform->valueField($line);
		   }
	   }

	   // Get latitude, longitude
	   $queries = array();
	   if (!empty($ville)) {
		   $queries[] = $ville;
	   }
	   if (!empty($departement)) {
		   $queries[] = $departement;
	   }
	   if (!empty($region)) {
		   $queries[] = $region;
	   }
	   if (!empty($pays)) {
		   $queries[] = $pays;
	   }
	   
	   if (count($queries) > 1) {
		   $query = implode(',', $queries);

		   list($lat, $lon, $s, $r) = $questionnaire->geocoder($query);

		   if ($lat != 0 && $lon != 0) {
			   $value = $lat.",".$lon;
		   } else {
		       // Try without city
               $queries = array();
               if (!empty($departement)) {
                   $queries[] = $departement;
               }
               if (!empty($region)) {
                   $queries[] = $region;
               }
               if (!empty($pays)) {
                   $queries[] = $pays;
               }

               $query = implode(',', $queries);

               list($lat, $lon, $s, $r) = $questionnaire->geocoder($query);

               if ($lat != 0 && $lon != 0) {
                   $value = $lat.",".$lon;
               }
           }
	   }
	   
	   return $value;
   }

	/**
	 *	Update location
	 *
	 *	@return int         		1 if OK, -1 if ref already exists, -2 if other error
	 */

	 function update_location($lat, $lon, $base, $skip_if_filled = true, $notrigger=0)
	 {
		global $user, $langs, $conf;

		$lines = $this->lines;
		
		$questionnaire = new Questionnaire($this->db);

		$lat = trim($lat);
		$lon = trim($lon);
		
		if ($lat != 0 && $lon != 0) {
			list($city, $region, $state, $country, $zip) = $questionnaire->reverse($lat, $lon);
	
			foreach ($lines as $line) {
	
				if (($line->code  == $base.'_ville')
				|| ($line->code  == $base.'_departement')
				|| ($line->code  == $base.'_region')
                 || ($line->code  == $base.'_pays')
                    || ($line->code  == $base.'_code_postal')
                ) {
					
					if (!empty($line->value) && $skip_if_filled) { // Filled by user
						continue;
					}
					
					$l = new ReponseLine($this->db);
					$l->fk_reponse   = $this->id;
					$l->code 			 = $line->code;
	
					if ($l->code == $base.'_ville') {
						$value = $city;
					} else if ($l->code == $base.'_region') {
						$value = $region;
					} else if ($l->code == $base.'_pays') {
						$value = $country;
					} else if ($l->code == $base.'_code_postal') {
                        $value = $zip;
                    }
                    else {
						$value = $state;
					}

                    $l->type 			 = $line->type;
                    $l->crypted 		= $line->crypted;
					$l->value = $value;
                    $l->formatted_value = $value;
					$result = $l->update($user, $notrigger);
				}
			}
		}	
	}

	/**
	 *	Fill database.
	 *
	 *	@param  User	$user       Object user making update
	 *  @param  int		$notrigger	    0=launch triggers after, 1=disable triggers
	 *	@return int         		1 if OK, -1 if ref already exists, -2 if other error
	 */
	function fill($user, $notrigger=0)
	{
		global $langs, $conf, $hookmanager;

		$error=0;


		// Clean parameters
		$id = $this->id;

		// Check parameters
		if (empty($id))
		{
			$this->error = "Object must be fetched before calling update";
			return -1;
		}


		$this->fetch_lines();

		$lines = $this->lines;
		$error = 0;

		$post = $_POST;

		//dol_syslog(get_class($this)."::fill serialize post= ".json_encode($post), LOG_DEBUG);

		if (count($post)) {
			foreach ($post as $code => $val) {

				$line = isset($lines[$code]) ? $lines[$code] : null;

				if ($line) {
					$result = $this->addline($line, $notrigger);
		
					if ($result < 0)
					{
						$error++;
						$this->errors[]=$this->error;
                        $this->error = null;
					}
				}
			}
		}

		$this->fetch_lines();

		if (!$error)
		{
		    // Update location infos
            if (count($this->lines)) {
                foreach ($this->lines as $line) {

                    $isMap = $line->type == 'map';

                    if ($isMap) {
                        $base = $line->code;

                        if (empty($line->value)) {
                            $value = $this->update_gps($base);

                            $l = new ReponseLine($this->db);
                            $l->fk_reponse   = $this->id;
                            $l->code 			 = $line->code;
                            $l->value = $value;
                            $l->formatted_value = $value;
                            $l->type 			 = $line->type;
                            $l->crypted 		= $line->crypted;

                            $l->update($user, 1);


                        } else {
                            // Check if we need to update town, region, state or something else
                            list($lat, $lon) = explode(',', $line->value);
                            $this->update_location($lat, $lon, $base, true,1);
                        }
                    }
                }

                $this->fetch_lines();

                // Update also _gps field
                foreach ($this->lines as $line) {
                    if (strpos($line->code, '_gps') !== false && empty($line->value)) {
                        $base = substr($line->code, 0, strpos($line->code, '_gps'));

                        if (isset($this->lines[$base]) && !empty($this->lines[$base]->value)) {
                            $l = new ReponseLine($this->db);
                            $l->fk_reponse   = $this->id;
                            $l->code 			 = $line->code;
                            $l->type 			 = $line->type;
                            $l->crypted 		= $line->crypted;

                            $value = $this->lines[$base]->value;

                            $l->value = $value;
                            $l->formatted_value = $value;
                            $l->update($user, 1);
                        }
                    }

                    if (strpos($line->code, '_mois') !== false && empty($line->value)) {
                        $base = substr($line->code, 0, strpos($line->code, '_mois'));

                        if (isset($this->lines[$base]) && !empty($this->lines[$base]->value)) {
                            $l = new ReponseLine($this->db);
                            $l->fk_reponse   = $this->id;
                            $l->code 			 = $line->code;
                            $l->type 			 = $line->type;
                            $l->crypted 		= $line->crypted;

                            $value = $this->lines[$base]->value;
                            $value = dol_print_date($value, "%m");
                            $value = intval($value);

                            $l->value = $value;
                            $l->formatted_value = $value;
                            $l->update($user, 1);
                        }
                    }

                    if (strpos($line->code, '_annee') !== false && empty($line->value)) {
                        $base = substr($line->code, 0, strpos($line->code, '_annee'));

                        if (isset($this->lines[$base]) && !empty($this->lines[$base]->value)) {
                            $l = new ReponseLine($this->db);
                            $l->fk_reponse   = $this->id;
                            $l->code 			 = $line->code;
                            $l->type 			 = $line->type;
                            $l->crypted 		= $line->crypted;

                            $value = $this->lines[$base]->value;
                            $value = dol_print_date($value, "%Y");

                            $l->value = $value;
                            $l->formatted_value = $value;
                            $l->update($user, 1);
                        }
                    }

                    if (strpos($line->code, '_code_postal') !== false) {
                        $value = $line->value;

                        $base = substr($line->code, 0, strpos($line->code, '_code_postal'));
                        $questionnaire = new Questionnaire($this->db);

                        list($lat, $lon, $state, $region) = $questionnaire->geocoder($value);

                        $codes = array(
                            $base.'_departement' => $state,
                            $base.'_region' => $region
                        );

                        foreach ($codes as $code => $value) {
                            if (isset($object->lines[$code]) && empty($object->lines[$code]->value)) {
                                $l = new ReponseLine($this->db);
                                $l->fk_reponse   = $object->id;
                                $l->code 			 = $code;
                                $l->type 			 = $line->type;
                                $l->crypted 		= $line->crypted;
                                $l->value           = $value;
                                $l->formatted_value = $value;
                                $l->update($user, 1);
                            }
                        }
                    }
                }

                $this->fetch_lines();
            }

			if (! $notrigger)
			{
				// Call trigger
				$result = $this->call_trigger('REPONSE_FILL',$user);
				if ($result < 0) $error++;
				// End call triggers
			}

			return 1;
		}
		else
		{
			return -1;				
		}
	}


	/**
	 *	Update a record into database.
	 *
	 *	@param  User	$user       Object user making update
	 *  @param  int		$notrigger	    0=launch triggers after, 1=disable triggers
	 *	@return int         		1 if OK, -1 if ref already exists, -2 if other error
	 */
	function update($user, $notrigger=0)
	{
		global $langs, $conf, $hookmanager;

		$error=0;


		// Clean parameters
		$id = $this->id;

		// Check parameters
		if (empty($id))
		{
			$this->error = "Object must be fetched before calling update";
			return -1;
		}


		$this->db->begin();
		
		$sql = "UPDATE ".MAIN_DB_PREFIX."reponse";
		$sql.= " SET ref = ".(!empty($this->ref) ? "'".$this->db->escape($this->ref)."'" : "null");
		$sql.= ", fk_questionnaire = ".(!empty($this->fk_questionnaire) ? $this->fk_questionnaire : "0");
        $sql.= ", fk_soc = ".(!empty($this->fk_soc) ? $this->fk_soc : "0");
        $sql.= ", fk_projet = ".(!empty($this->fk_projet) ? $this->fk_projet : "0");
        $sql.= ", envoi_ar = ".(!empty($this->envoi_ar) ? $this->envoi_ar : "0");
        $sql.= ", note_private = ".(!empty($this->note_private) ? "'".$this->db->escape($this->note_private)."'" : "null");
		$sql.= ", is_draft = ".($this->is_draft > 0 ? "1" : "0");
		$sql.= ", origin_id = ".(!empty($this->origin_id) ? $this->origin_id : "0");
		$sql.= ", origin = ".(!empty($this->origin) ? "'".$this->db->escape($this->origin)."'" : "null");
		$sql.= ", user_author_id = ".(!empty($this->user_author_id) ? $this->user_author_id : "0");
		$sql.= ", tms = '".$this->db->idate(dol_now())."'";
		$sql.= " WHERE rowid = " . $id;

		dol_syslog(get_class($this)."::update", LOG_DEBUG);

		$resql = $this->db->query($sql);
		if ($resql)
		{
			if (! $notrigger)
			{
				// Call trigger
				$result = $this->call_trigger('REPONSE_MODIFY',$user);
				if ($result < 0) $error++;
				// End call triggers
			}

			$this->db->commit();
			return 1;
		}
		else
		{
			$this->error=$langs->trans("Error")." : ".$this->db->error()." - ".$sql;
			$this->errors[]=$this->error;
			$this->db->rollback();

			return -1;				
		}
	}

	/**
	 *  Load a slice in memory from database
	 *
	 *  @param	int		$id      			Id of slide
	 *  @return int     					<0 if KO, 0 if not found, >0 if OK
	 */
	function fetch($id, $ref='')
	{
		global $langs, $conf;

		dol_syslog(get_class($this)."::fetch id=".$id);


		// Check parameters
		if (empty($id) && empty($ref))
		{
			$this->error='ErrorWrongParameters';
			//dol_print_error(get_class($this)."::fetch ".$this->error);
			return -1;
		}

		$sql = "SELECT s.rowid, s.ref, s.envoi_ar, s.origin, s.origin_id, s.fk_soc, s.fk_projet, s.fk_questionnaire, s.note_private, s.datec, s.user_author_id, s.entity, s.is_draft, s.active";
		$sql.= " FROM ".MAIN_DB_PREFIX."reponse s";

		if ($id > 0) {
			$sql.= " WHERE s.rowid=".$id;
		} else {
			$sql.= " WHERE s.entity IN (".getEntity('reponse').") AND s.ref='".$this->db->escape($ref)."'";
		}

		$resql = $this->db->query($sql);
		if ( $resql )
		{
			if ($this->db->num_rows($resql) > 0)
			{
				$obj = $this->db->fetch_object($resql);

				$this->id				= $obj->rowid;
				$this->fk_questionnaire 			= $obj->fk_questionnaire;
                $this->fk_soc 			= $obj->fk_soc;
                $this->fk_projet 			= $obj->fk_projet;

				$this->user_author_id 	= $obj->user_author_id;

				$this->ref 				= $obj->ref;
				$this->datec 			= $this->db->jdate($obj->datec);
				$this->entity			= $obj->entity;
                $this->envoi_ar			= $obj->envoi_ar;
                $this->origin_id		= $obj->origin_id;
				$this->origin			= $obj->origin;
                $this->active			= $obj->active;
				$this->is_draft			= $obj->is_draft;
                $this->note_private   = $obj->note_private;

				$questionnaire = null;

				if (!empty($conf->questionnaire->enabled) && $obj->fk_questionnaire > 0) {
					$questionnaire = new Questionnaire($this->db);
					$questionnaire->fetch($obj->fk_questionnaire);
				}
				
				$this->questionnaire = $questionnaire;

				$this->fetch_optionals();

				/*
				 * Lines
				 */
				$result = $this->fetch_lines();

				$this->db->free($resql);

				return 1;
			}
			else
			{
				return 0;
			}
		}
		else
		{
			dol_print_error($this->db);
			return -1;
		}
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.NotCamelCaps
	/**
	 *	Load array lines
	 *
	 *	@return		int						<0 if KO, >0 if OK
	 */
	function fetch_lines()
	{
		global $conf, $user;

        // phpcs:enable
		$this->lines = array();

        $sql = "SELECT sd.*";
        $sql.= " FROM ".MAIN_DB_PREFIX."questionnairefval".$this->fk_questionnaire." as sd";
        $sql.= " WHERE sd.fk_reponse = ".$this->id;

        dol_syslog(get_class($this)."::fetch_lines", LOG_DEBUG);
        $result = $this->db->query($sql);
        $fvalues = array();
        if ($result)
        {
            if ($this->db->num_rows($result) > 0)
            {
                $fvalues = $this->db->fetch_array($result);
            }

            $this->db->free($result);
        }
        else
        {
            $this->error=$this->db->error();
            return -3;
        }

		$sql = "SELECT sd.*";
		$sql.= " FROM ".MAIN_DB_PREFIX."questionnaireval".$this->fk_questionnaire." as sd";
		$sql.= " WHERE sd.fk_reponse = ".$this->id;

		dol_syslog(get_class($this)."::fetch_lines", LOG_DEBUG);
		$result = $this->db->query($sql);
		$values = array();
		if ($result)
		{
			if ($this->db->num_rows($result) > 0)
			{
				$values = $this->db->fetch_array($result);
			}

			$this->db->free($result);
		}
		else
		{
			$this->error=$this->db->error();
			return -3;
		}

		if (!empty($conf->questionnaire->enabled) && $this->fk_questionnaire > 0) {
			$questionnaire = new Questionnaire($this->db);
			$questionnaire->fetch($this->fk_questionnaire);
	
			$lines = $questionnaire->lines;

			if (count($lines)) {
				foreach ($lines as $fline) {
					$line = new stdClass();

					$code = $fline->code;

					$value = isset($values[$code]) ? $values[$code] : null;
                    $fvalue = isset($fvalues[$code]) ? $fvalues[$code] : null;

                    $line->fk_reponse   = $this->id;

                    $line->id 				= $fline->id;
					$line->code             = $fline->code;
					$line->label            = $fline->label;
					$line->type             = $fline->type;
					$line->rang      		= $fline->rang;		
					$line->param     		= $fline->param;
					$line->mandatory        = $fline->mandatory;
					$line->postfill         = $fline->postfill;
					$line->prefill          = $fline->prefill;
					$line->fk_cond          = $fline->fk_cond;
					$line->fk_op_cond       = $fline->fk_op_cond;
					$line->val_cond         = $fline->val_cond;
					$line->crypted          = $fline->crypted;
                    $line->inapp          = $fline->inapp;
                    $line->help             = $fline->help;
                    $line->visibility          = $fline->visibility;

                    if (!empty($line->crypted)) {
						if (strpos($value, ':') !== false && ($user->rights->reponse->decrypter || $this->user_author_id == $user->id)) {
							list($key, $value) = explode(':', $value);
							$value  = mb_dol_decode($value, $key);
							$fvalue = $value;
						}
					}

					$line->value            = $value;
	                $line->formatted_value = $fvalue;

					$this->lines[$line->code] = $line;

					if ($questionnaire->fk_email == $fline->id) {
						$this->email = $line->value;
					}

                    if ($questionnaire->fk_name == $fline->id) {
                        $this->name = $line->value;
                    }

					if ($questionnaire->fk_date == $fline->id) {
						$this->date = $this->db->jdate($line->value);
					}

                    if ($questionnaire->fk_location == $fline->id) {
                        $this->location = $line->value;
                    }
                }
			}
        }

		return 1;
	}

	/**
	 *  Delete a gestion from database (if not used)
	 *
	 *	@param      User	$user       
	 *  @param  	int		$notrigger	    0=launch triggers after, 1=disable triggers
	 * 	@return		int					< 0 if KO, 0 = Not possible, > 0 if OK
	 */
	function delete(User $user, $notrigger=0)
	{
		global $conf, $langs;

		$error=0;

		// Clean parameters
		$id = $this->id;

		// Check parameters
		if (empty($id))
		{
			$this->error = "Object must be fetched before calling delete";
			return -1;
		}
		
		$this->db->begin();

		$sqlz = "DELETE FROM ".MAIN_DB_PREFIX."reponse";
		$sqlz.= " WHERE rowid = ".$id;
		dol_syslog(get_class($this).'::delete', LOG_DEBUG);
		$resultz = $this->db->query($sqlz);

		if ( ! $resultz )
		{
			$error++;
			$this->errors[] = $this->db->lasterror();
		}		

		$sqlz = "DELETE FROM ".MAIN_DB_PREFIX."questionnaireval".$this->fk_questionnaire;
		$sqlz.= " WHERE fk_reponse = ".$id;
		dol_syslog(get_class($this).'::delete', LOG_DEBUG);
		$resultz = $this->db->query($sqlz);

		if ( ! $resultz )
		{
			$error++;
			$this->errors[] = $this->db->lasterror();
		}

        $sqlz = "DELETE FROM ".MAIN_DB_PREFIX."questionnairefval".$this->fk_questionnaire;
        $sqlz.= " WHERE fk_reponse = ".$id;
        dol_syslog(get_class($this).'::delete', LOG_DEBUG);
        $resultz = $this->db->query($sqlz);

        if ( ! $resultz )
        {
            $error++;
            $this->errors[] = $this->db->lasterror();
        }

        if (! $error)
		{
			$dir = $conf->reponse->dir_output . '/' . dol_sanitizeFileName($this->ref);
			if (@is_dir($dir))
			{
				dol_delete_dir_recursive($dir);
			}

			if (! $notrigger)
			{
	            // Call trigger
	            $result = $this->call_trigger('REPONSE_DELETE',$user);
	            if ($result < 0) $error++;
	            // End call triggers
			}
		}

		if (! $error)
		{
			$this->db->commit();
			return 1;
		}
		else
		{
			foreach($this->errors as $errmsg)
			{
				dol_syslog(get_class($this)."::delete ".$errmsg, LOG_ERR);
				$this->error.=($this->error?', '.$errmsg:$errmsg);
			}
			$this->db->rollback();
			return -$error;
		}

	}

	/**
	 *  Delete a gestion from database (if not used)
	 *
	 *	@param      User	$user       
	 *  @param  	int		$notrigger	    0=launch triggers after, 1=disable triggers
	 * 	@return		int					< 0 if KO, 0 = Not possible, > 0 if OK
	 */
	function disable(User $user, $notrigger=0)
	{
		global $conf, $langs;

		$error=0;

		// Clean parameters
		$id = $this->id;

		// Check parameters
		if (empty($id))
		{
			$this->error = "Object must be fetched before calling delete";
			return -1;
		}
		
		$this->db->begin();

		$sqlz = "UPDATE ".MAIN_DB_PREFIX."reponse SET active = 0";
		$sqlz.= " WHERE rowid = ".$id;
		dol_syslog(get_class($this).'::disable', LOG_DEBUG);
		$resultz = $this->db->query($sqlz);

		if ( ! $resultz )
		{
			$error++;
			$this->errors[] = $this->db->lasterror();
		}		

		if (! $error)
		{
			if (! $notrigger)
			{
	            // Call trigger
	            $result = $this->call_trigger('REPONSE_DISABLE',$user);
	            if ($result < 0) $error++;
	            // End call triggers
			}
		}

		if (! $error)
		{
			$this->db->commit();
			return 1;
		}
		else
		{
			foreach($this->errors as $errmsg)
			{
				dol_syslog(get_class($this)."::delete ".$errmsg, LOG_ERR);
				$this->error.=($this->error?', '.$errmsg:$errmsg);
			}
			$this->db->rollback();
			return -$error;
		}

	}
	/**
	 *  Set sending confirmation and insert event
	 *
	 *	@param      User	$user       
	 * 	@return		int					< 0 if KO, 0 = Not possible, > 0 if OK
	 */

	function confirm($user)
	{
		global $conf, $langs;

		$error=0;

		// Clean parameters
		$id = $this->id;

		// Check parameters
		if (empty($id))
		{
			$this->error = "Object must be fetched before calling delete";
			return -1;
		}

		$this->envoi_ar = 1;
		$this->update($user);

		$now = dol_now();

		$text = $langs->transnoentities("ReponseEnvoiAR");
		$code = 'AC_CONFIRMED';

		$contactforaction = new Contact($this->db);
		$societeforaction = new Societe($this->db);
        $societeforaction->fetch($this->fk_soc);

		$actioncomm = new ActionComm($this->db);
		$actioncomm->type_code   = 'AC_OTH_AUTO';		// Type of event ('AC_OTH', 'AC_OTH_AUTO', 'AC_XXX'...)
		$actioncomm->code        = $code;
		$actioncomm->label       = $text;
		$actioncomm->note        = $text;     
		$actioncomm->fk_project  = $this->fk_projet;
		$actioncomm->datep       = $now;
		$actioncomm->datef       = $now;
		$actioncomm->durationp   = 0;
		$actioncomm->punctual    = 1;
		$actioncomm->percentage  = -1;   // Not applicable
		$actioncomm->societe     = $societeforaction;
		$actioncomm->contact     = $contactforaction;
		$actioncomm->socid       = $societeforaction->id;
		$actioncomm->contactid   = $contactforaction->id;
		$actioncomm->authorid    = $user->id;   // User saving action
		$actioncomm->userownerid = $user->id;	// Owner of action
		$actioncomm->fk_element  = $this->id;
		$actioncomm->elementtype = $this->element;

		$ret = $actioncomm->create($user);       // User creating action

		return $ret;
	}


	/**
	 * 	Create an array of form lines
	 *
	 * 	@return int		>0 if OK, <0 if KO
	 */
	function getLinesArray()
	{
		return $this->fetch_lines();
	}

     /**
     *      \brief Return next reference of confirmation not already used (or last reference)
     *      @param	   soc  		           objet company
     *      @param     mode                    'next' for next value or 'last' for last value
     *      @return    string                  free ref or last ref
     */
    function getNextNumRef($soc, $mode = 'next')
    {
        global $conf, $langs;

        $langs->load("reponse@reponse");

        // Clean parameters (if not defined or using deprecated value)
        if (empty($conf->global->REPONSE_ADDON)){
            $conf->global->REPONSE_ADDON = 'mod_reponse_rosana';
        }else if ($conf->global->REPONSE_ADDON == 'rosana'){
            $conf->global->REPONSE_ADDON = 'mod_reponse_rosana';
        }else if ($conf->global->REPONSE_ADDON == 'vichy'){
            $conf->global->REPONSE_ADDON = 'mod_reponse_vichy';
        }

        $included = false;

        $classname = $conf->global->REPONSE_ADDON;
        $file = $classname.'.php';

        // Include file with class
        $dir = '/reponse/core/modules/reponse/';
        $included = dol_include_once($dir.$file);

        if (! $included)
        {
            $this->error = $langs->trans('FailedToIncludeNumberingFile');
            return -1;
        }

        $obj = new $classname();

        $numref = "";
        $numref = $obj->getNumRef($soc, $this, $mode);

        if ($numref != "")
        {
            return $numref;
        }
        else
        {
            return -1;
        }
	}

	
	/**
	 *	Return HTML table for object lines
	 *	TODO Move this into an output class file (htmlline.class.php)
	 *	If lines are into a template, title must also be into a template
	 *	But for the moment we don't know if it's possible as we keep a method available on overloaded objects.
	 *
	 *	@param	string		$action				Action code
	 *	@param  string		$seller            	Object of seller third party
	 *	@param  string  	$buyer             	Object of buyer third party
	 *	@param	int			$selected		   	Object line selected
	 *	@param  int	    	$dateSelector      	1=Show also date range input fields
	 *	@return	void
	 */
	function printObjectLines($action, $seller, $buyer, $selected=0, $dateSelector=0,$defaulttpldir = '/core/tpl')
	{
		global $conf, $hookmanager, $langs, $user;

		$num = count($this->lines);

		// Title line
		print "<thead>\n";

		print '<tr class="liste_titre nodrag nodrop">';

		// Adds a line numbering column
		if (! empty($conf->global->MAIN_VIEW_LINE_NUMBER)) print '<td class="linecolnum" align="center" width="5">&nbsp;</td>';

		// Label
		print '<td class="linecollabel">'.$langs->trans('ReponseDetails').'</td>';

		// Name
		print '<td class="linecolvalue">&nbsp;</td>';

		print '<td class="linecoledit"></td>';  // No width to allow autodim

		print "</tr>\n";
		print "</thead>\n";
		
		$var = true;
		$i	 = 0;

		print "<tbody>\n";
		foreach ($this->lines as $line)
		{
            if (($line->type == 'file' && $user->rights->reponse->telecharger) || $line->type != 'file') {
                $this->printObjectLine($action,$line,$var,$num,$i,$dateSelector,$seller,$buyer,$selected,'',$defaulttpldir);
                $i++;
            }

		}
		print "</tbody>\n";
	}

	/**
	 *	Return HTML content of a detail line
	 *	TODO Move this into an output class file (htmlline.class.php)
	 *
	 *	@param	string		$action				GET/POST action
	 *	@param CommonObjectLine $line		       	Selected object line to output
	 *	@param  string	    $var               	Is it a an odd line (true)
	 *	@param  int		    $num               	Number of line (0)
	 *	@param  int		    $i					I
	 *	@param  int		    $dateSelector      	1=Show also date range input fields
	 *	@param  string	    $seller            	Object of seller third party
	 *	@param  string	    $buyer             	Object of buyer third party
	 *	@param	int			$selected		   	Object line selected
	 *  @param  int			$extrafieldsline	Object of extrafield line attribute
	 *	@return	void
	 */
	function printObjectLine($action,$line,$var,$num,$i,$dateSelector,$seller,$buyer,$selected=0,$extrafieldsline=0, $defaulttpldir = '/core/tpl')
	{
		global $conf, $langs, $user, $object, $hookmanager, $bc;

		$domData = ' data-id="'.$line->id.'"';

		$questionnaireform = new QuestionnaireForm($this->db);
		$form = new Form($this->db);

		// Ligne en mode visu
		//if ($action != 'addline' && ($action != 'editline' || $selected != $line->id))
		//{
			print '<tr  id="row-'.$line->id.'" class="drag drop oddeven" '.$domData.' >';
			if (! empty($conf->global->MAIN_VIEW_LINE_NUMBER)) {
				print '<td class="linecolnum" align="center">'.($i+1).'</td>';
			}

			/*
            print '<form action="' . $_SERVER["PHP_SELF"] . '#line_'.$line->id.'" method="POST" enctype="multipart/form-data">';
            print '<input type="hidden" name="token" value="' . $_SESSION ['newtoken'] . '">';
            print '<input type="hidden" name="action" value="updateline">';
            print '<input type="hidden" name="id" value="'.$this->id.'">';
            print '<input type="hidden" name="lineid" value="'.$line->id.'">';
            */
			print '<td class="linecollabel '.($line->mandatory ? 'fieldrequired' : '').'"><div id="line_'.$line->id.'"></div>';
            if ($line->help) {
                print $form->textwithtooltip($line->label, $line->help, 2, 1, img_help(1, ''));
            } else {
                print $line->label;
            }
			print '</td>';

			print '<td class="linecolvalue nowrap">';
            print $questionnaireform->editField($line, 'edit')."<br />";
			print '</td>';

			if ($action != 'selectlines' ) { 
				print '<td class="linecoledit" align="center">';
                // print '	<input type="submit" class="button" name="save" value="'.$langs->trans("Save").'"><br>'."\n";
                // print '	<input type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'">'."\n";
				print '</td>';
			} else {
				print '<td>&nbsp;</td>';
			} 

			if ($action == 'selectlines') {
				print '<td class="linecolcheck" align="center"><input type="checkbox" class="linecheckbox" name="line_checkbox['.($i+1).']" value="'.$line->id.'" ></td>';
			}

			//print '</form>';
			print '</tr>';
		//}

	}

	/**
	 *	Charge les informations d'ordre info dans l'objet commande
	 *
	 *	@param  int		$id       Id of order
	 *	@return	void
	 */
	function info($id)
	{
		$sql = 'SELECT s.rowid, s.datec as datec, s.tms as datem,';
		$sql.= ' s.user_author_id as fk_user_author';
		$sql.= ' FROM '.MAIN_DB_PREFIX.'reponse as s';
		$sql.= ' WHERE s.rowid = '.$id;
		$result=$this->db->query($sql);
		if ($result)
		{
			if ($this->db->num_rows($result))
			{
				$obj = $this->db->fetch_object($result);
				$this->id = $obj->rowid;
				if ($obj->fk_user_author)
				{
					$cuser = new User($this->db);
					$cuser->fetch($obj->fk_user_author);
					$this->user_creation   = $cuser;
				}

				$this->date_creation     = $this->db->jdate($obj->datec);
				$this->date_modification = $this->db->jdate($obj->datem);
			}

			$this->db->free($result);
		}
		else
		{
			dol_print_error($this->db);
		}
	}

    /**
     *	Return clicable link of object (with eventually picto)
     *
     *	@param      int			$withpicto                Add picto into link
     *	@param      int			$max          	          Max length to show
     *	@param      int			$short			          ???
     *  @param	    int   	    $notooltip		          1=Disable tooltip
     *	@return     string          			          String with URL
     */
    function getNomUrl($withpicto=0, $option='', $max=0, $short=0, $notooltip=0)
    {
        global $conf, $langs, $user;

        if (! empty($conf->dol_no_mouse_hover)) $notooltip=1;   // Force disable tooltips

        $result='';

        $url = dol_buildpath('/reponse/card.php', 1).'?id='.$this->id;

        if ($short) return $url;

        $picto = 'reponse@reponse';
         
        $label = '';

		if ($user->rights->reponse->lire) {
			$label = '<u>'.$langs->trans("ShowReponse").'</u>';
			$label .= '<br><b>'.$langs->trans('Ref').':</b> '.$this->ref;
		}

		$linkclose='';
		if (empty($notooltip) && $user->rights->reponse->lire)
		{
		    if (! empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER))
		    {
		        $label=$langs->trans("ShowReponse");
		        $linkclose.=' alt="'.dol_escape_htmltag($label, 1).'"';
		    }
		    $linkclose.= ' title="'.dol_escape_htmltag($label, 1).'"';
		    $linkclose.=' class="classfortooltip"';
		}

        $linkstart = '<a href="'.$url.'"';
        $linkstart.= $linkclose.'>';
        $linkend = '</a>';


        if ($withpicto){
        	if(empty($this->icon)){
        		$result .= ($linkstart.img_object(($notooltip?'':$label), $picto, ($notooltip?'':'class="classfortooltip" width=24'), 0, 0, $notooltip?0:1).$linkend);
        	}else{
        		$result .= $linkstart.'<img src="'.dol_buildpath('/questionnaire/icons/'.$this->icon,2).'">'.$linkend;
        	}	
        } 
        
        if ($withpicto && $withpicto != 2) $result.=' ';
		$result .= $linkstart .$this->ref. $linkend;
		
        return $result;
	}
	
    /**
     *	Return status label of Reponse
     *
     *	@param      int		$mode       0=libelle long, 1=libelle court, 2=Picto + Libelle court, 3=Picto, 4=Picto + Libelle long, 5=Libelle court + Picto
     *	@return     string      		Libelle
     */
    function getLibStatut($mode)
    {
        return '';
	}

	/**
	 *  Return list of reponses
	 *
	 *  @return     int             		-1 if KO, array with result if OK
	 */
	function liste_array($sortfield='s.ref', $sortorder='DESC', $origin='', $origin_id=0)
	{
		global $user;

		$reponses = array();

		$sql = "SELECT s.rowid as id, s.ref, s.datec";
		$sql.= " FROM ".MAIN_DB_PREFIX."reponse as s";
        $sql.= " WHERE s.entity IN (".getEntity('reponse').")";
        if ($origin_id > 0 && !empty($origin)) {
			$sql.= " AND s.origin = '".$this->db->escape($origin)."' AND s.origin_id = ".$origin_id;
		}
		$sql.= $this->db->order($sortfield,$sortorder);

		$result=$this->db->query($sql);
		if ($result)
		{
			$num = $this->db->num_rows($result);
			if ($num)
			{
				$i = 0;
				while ($i < $num) {
					$obj = $this->db->fetch_object($result);

					$datec = $this->db->jdate($obj->datec);

					$ref = $obj->ref.' - '.dol_print_date($datec, 'day');

					$reponses[$obj->id] = $ref;
					$i++;
				}
			}
			return $reponses;
		}
		else
		{
			dol_print_error($this->db);
			return -1;
		}
	}

		/**
	 *  Return list of reponses
	 *
	 *  @return     int             		-1 if KO, array with result if OK
	 */
	function user($user_id, $sortfield='s.ref', $sortorder='DESC')
	{
		global $user;

		$reponses = array();

		$sql = "SELECT s.rowid as id, s.ref, s.datec";
		$sql.= " FROM ".MAIN_DB_PREFIX."reponse as s";
		$sql.= " WHERE s.is_draft = 0 AND s.user_author_id = ".(int)$user_id;

		$sql.= $this->db->order($sortfield,$sortorder);

		$result=$this->db->query($sql);
		if ($result)
		{
			$num = $this->db->num_rows($result);
			if ($num)
			{
				$i = 0;
				while ($i < $num)
				{
					$obj = $this->db->fetch_object($result);

					$reponse = new Reponse($this->db);
					$reponse->fetch($obj->id);
					
					$reponses[$obj->id] = $reponse;

					$i++;
				}
			}
			return $reponses;
		}
		else
		{
			dol_print_error($this->db);
			return -1;
		}
	}

	/**
	 *  Return list of upload files for this reponse
	 *
	 *  @return     array     
	 */
	function getAttachedFiles()
	{
		global $conf, $langs;

		$lines = is_array($this->lines) ? $this->lines : array();
		$files = array();

		$dir = $conf->reponse->dir_output;

		foreach ($lines as $line) 
		{
			if ($line->type == 'file')
			{
				$values = !empty($line->value) ? explode(',', $line->value) : array();

				if (is_array($values))
				{
					foreach ($values as $file)
					{
						$relpath = $this->ref ."/" .$file;
						$fullpath = $dir ."/" .$relpath;

						if (file_exists($fullpath))
						{
							$files[] = $relpath;
						}
					}
				}
			}
		}
		
		return $files;
	}

    public static function createZipArchive($files, $diroutput, $filename = "download.zip")
	{
		global $conf, $langs;

		$error = 0;
		dol_mkdir($diroutput);
		$zip = new ZipArchive();
		
		$fullpath = $diroutput . "/" .$filename;
		$dir = $conf->reponse->dir_output;

		if ($zip->open($fullpath, ZipArchive::CREATE | ZipArchive::OVERWRITE)!==TRUE) {
			$error++;
			setEventMessages($langs->trans('CanNotCreateZip'), '', 'errors');
		} else {
			foreach ($files as $ref => $fs)
			{
				$zip->addEmptyDir($ref);

				if (count($fs))
				{
					foreach ($fs as $f)
					{
						if (file_exists($dir ."/" . $f))
						{
							$zip->addFile($dir . "/". $f, $f);
						}	
					}
				}
			}
		}

		$zip->close();

		$relpath = str_replace($dir, '', $fullpath);
		return $error > 0 ? '' : $relpath;
	}

	function generateTrackId($length = 20) {
		$characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
		$charactersLength = strlen($characters);
		$randomString = '';
		for ($i = 0; $i < $length; $i++) {
			$randomString .= $characters[rand(0, $charactersLength - 1)];
		}
		return $randomString;
	}

	
	function sendSiretRequest ($value,$parameter) {
		$apiUrl = 'https://recherche-entreprises.api.gouv.fr/search?';
		$request = $apiUrl.$parameter.'='.$value;

		$ch = curl_init();
		// Configurer les options cURL
		curl_setopt($ch, CURLOPT_URL, $request);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, [
		    'Content-Type: application/json'
		]);
		
		$response = curl_exec($ch);

		if (curl_errno($ch)) {
            $this->error = curl_error($ch);
            return -2;
		} else {
		    // Vérifier le code de statut HTTP
		    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		    if ($httpCode == 200) {
		        // Convertir la réponse JSON en tableau ou objet PHP
		        $data = json_decode($response, true); // Utiliser true pour un tableau, false pour un objet

		        // Vérifier si la conversion JSON a réussi
		        if(json_last_error() === JSON_ERROR_NONE) {
                    $result = array();
                    if (isset($data['results']) && count($data['results'])) {
                        $results = $data['results'][0];

                        $result['nom_complet'] = $results['nom_complet'];
                        $result['adresse'] = $results['siege']['numero_voie']. ' '.$results['siege']['type_voie']. ' '.$results['siege']['libelle_voie'];
                        $result['code_postal'] = $results['siege']['code_postal'];
                        $result['libelle_commune'] = $results['siege']['libelle_commune'];
                        $result['code_ape'] = $results['siege']['activite_principale'];
                        $result['code_siren'] = $results['siren'];
                        $result['dirigeant_nom'] = ucfirst($results['dirigeants'][0]['nom']);
                        $result['dirigeant_prenom'] = ucfirst($results['dirigeants'][0]['prenoms']);
                        $result['est_association'] = !empty($results['complements']['est_association']) ? $results['complements']['est_association'] : 0;
                        $result['est_bio'] = !empty($results['complements']['est_bio']) ? $results['complements']['est_bio'] : 0;
                        $result['est_entrepreneur_individuel'] = !empty($results['complements']['est_entrepreneur_individuel']) ?$results['complements']['est_entrepreneur_individuel'] : 0 ;
                        $result['est_rge'] = !empty($results['complements']['est_rge']) ? $results['complements']['est_rge'] : 0;
                        $result['identifiant_association'] = $results['complements']['identifiant_association'];
                        $result['latlong'] = $results['siege']['coordonnees'];
                    }

                    curl_close($ch);
		            return $result;
		        } else {
		            $this->error = json_last_error_msg();
		            return -3;
		        }
		    } else {
		        return -1;
		    }
		}
	}

    /**
     *  Load theme for reponse
     *
     *  @return array
     */
    function getThemes()
    {
        global $langs, $conf;

        dol_syslog(get_class($this)."::getThemes");

        $themes = array();
        $data = dol_dir_list(dol_buildpath('/reponse/public/themes'), 'directories');

        if (count($data) && is_array($data)) {
            foreach ($data as $dir) {
                $themes[] = $dir['name'];
            }
        }


        return $themes;
    }

    /**
     *  Load template
     *
     */
    function include_once($tpl, $data = array())
    {
        global $conf, $langs, $site, $user, $db;

        if (count($data)) {
            foreach ($data as $var => $value) {
                $$var = $value;
            }
        }

        $theme = isset($conf->global->REPONSE_THEME) ? $conf->global->REPONSE_THEME : 'default';

        $path = dol_buildpath(sprintf('/reponse/public/themes/%s/%s', $theme, $tpl));

        include_once($path);
    }

    /**
	 *  Return list of reponses for a form, filter on a field and a value of this field
	 *
	 *  @return     int             		-1 if KO, array with result if OK
	 */
	function getReponsesbyField($fk_questionnaire, $fieldname, $fieldvalue,$sortfield='ref',$sortorder='ASC')
	{
		global $user;

		$reponses = array();
		
		//$sql = "SELECT s.rowid as id, s.ref, s.datec, ".implode(',', $codes);
		$sql = "SELECT s.rowid as id, s.ref, s.datec";
		$sql.= " FROM ".MAIN_DB_PREFIX."reponse as s";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."questionnaireval".$fk_questionnaire." as fv ON fv.fk_reponse = s.rowid";
        $sql.= " WHERE s.entity IN (".getEntity('reponse').")";
        $sql.= " AND s.fk_questionnaire = ".$fk_questionnaire;
        $sql.= " AND fv.".$fieldname." = ".$fieldvalue;
		$sql.= $this->db->order($sortfield,$sortorder);

		$result=$this->db->query($sql);
		if ($result)
		{
			$num = $this->db->num_rows($result);
			if ($num)
			{
				$i = 0;
				while ($i < $num)
				{
					$obj = $this->db->fetch_object($result);
					$datec = $this->db->jdate($obj->datec);
					$ref = $obj->ref.' - '.dol_print_date($datec, 'day');
					$reponses[$obj->id] = $ref;
					$i++;
				}
			}
			return $reponses;
		}
		else
		{
			dol_print_error($this->db);
			return -1;
		}
	
	}

	/**
	 *  Return object with param from settings field form
	 *	$params string list of params from questionnaire field. one param per line / separated by a comma. each param on this format : key=value,
	 *  @return     int -1 if KO, object with result if OK
	 */
	function fetchParameters($parameters)
	{
	    if (!empty($parameters)) {
	        // Récupère les paramètres sous forme de tableau dans $params
	        $field_param = explode(chr(10), $parameters);
	        $params = new stdClass();

	        foreach ($field_param as $param) {
	            list($key, $value) = explode('=', $param, 2); // Limiter à 2 éléments

	            $key = trim($key);
	            $value = trim($value);

	            // Vérifier si la valeur commence par "array("
	            if (strpos($value, 'array(') === 0) {
	                // Supprimer "array(" et ")" et diviser les valeurs par des virgules
	                $arrayValue = trim($value, 'array()');
	                $arrayValues = explode(',', $arrayValue);
	                // Convertir les valeurs en tableau d'entiers ou de chaînes
	                $arrayValues = array_map('trim', $arrayValues);
	                // Ajouter le tableau à l'objet
	                $params->$key = $arrayValues;
	            } else {
	                // Ajouter la valeur simple à l'objet
	                $params->$key = $value;
	            }
	        }
	    }

	    if (!empty($params)) {
	        return $params;
	    } else {
	        return -1;
	    }
	}		

	/**
	 * Prints a mandatory information.
	 *
	 * @param      bool  $is_mandatory  Indicates if mandatory
	 * @return 	   string an html string with mandatory info or ''
	 */
	function print_mandatory_info($is_mandatory)
	{
		global $langs;
		if($is_mandatory==1){
			return '<span class="mandatoryinfo">'.$langs->trans('MandatoryInfo').'</span>';
		}else{
			return '';
		}
	}
	
}


/**
 *  Class to manage questionnaire lines
 */
class ReponseLine extends CommonObjectLine
{
	/**
	 * @var string ID to identify managed object
	 */
	public $element='reponsedet';

	public $table_element='';

	var $oldline;

	/**
	 * Id of reponse 
	 * @var int
	 */
	public $fk_reponse;

	/**
	 * Code of line
	 * @var int
	 */
	public $code;

    /**
     * Type
     * @var string
     */
    public $type;

    /**
     * Crypted
     * @var boolean
     */
    public $crypted;

	/**
	 * Value
	 * @var string 
	 */
	public $value;

    /**
     * Param
     * @var string
     */
    public $param;
    /**
     * Formatted value
     * @var string
     */
    public $formatted_value;
	/**
	 *      Constructor
	 *
	 *      @param     DoliDB	$db      handler d'acces base de donnee
	 */
	function __construct($db)
	{
		$this->db= $db;
	}


	/**
	 *	Update the line object into db
	 *
	 *	@param      User	$user        	User that modify
	 *	@param      int		$notrigger		1 = disable triggers
	 *	@return		int		<0 si ko, >0 si ok
	 */
	function update(User $user, $notrigger=0)
	{
		global $conf,$langs;

		$error=0;

		// Clean parameters
		if (empty($this->rang)) $this->rang = 0;

		$this->db->begin();

        $reponse = new Reponse($this->db);
        $reponse->fetch($this->fk_reponse);

        // special case for user field which store data in a array
        if(is_array($this->formatted_value)){
			$this->formatted_value = implode(',',$this->formatted_value);
			$this->value = $this->formatted_value;
		}
		// INTERVIENT SUR QUESTIONNAIRE ! FVAL
        $sql = "UPDATE ".MAIN_DB_PREFIX."questionnairefval".$reponse->fk_questionnaire." SET ";
        $sql.= " ".$this->code." = '".$this->db->escape($this->formatted_value)."'";
        $sql.= " , tms = '" . $this->db->idate(dol_now()) . "'";
        $sql.= " WHERE fk_reponse = ".$this->fk_reponse;           

        dol_syslog(get_class($this)."::update", LOG_DEBUG);
        $this->db->query($sql);

        if ($this->type == 'int') {
            $type = "INT";
        } else if ($this->type == 'date' || $this->type == 'datetime') {
            $type = "DATE";
        } else if ($this->type == 'list' || $this->type == 'radio' || $this->type == 'string' || $this->type == 'table' || $this->type=='map' || $this->type=='checkbox') {
            $type = "VARCHAR";
        } else {
            $type = 'VARCHAR'; // Default
        }

        if ($this->crypted) {
            $type = 'VARCHAR'; // Default
        }
        //Intervient sur questionnaire ! val
		$sql = "UPDATE ".MAIN_DB_PREFIX."questionnaireval".$reponse->fk_questionnaire." SET ";
        if ($type == 'INT') {
            $sql.= " ".$this->code." = ".(empty($this->value) ? '0' : intval($this->value));
        } elseif ($type == 'DATE') {
            $sql.= " ".$this->code." = ".(empty($this->value) ? 'null' : "'".$this->db->idate($this->value)."'");
        } else {
            $sql.= " ".$this->code." = ".($this->value === '' ? 'null' : "'".$this->db->escape($this->value)."'");
        }
		$sql.= " , tms = '" . $this->db->idate(dol_now()) . "'";
		$sql.= " WHERE fk_reponse = ".$this->fk_reponse;
		
		dol_syslog(get_class($this)."::update", LOG_DEBUG);
		$resql=$this->db->query($sql);
		if ($resql)
		{

			if (! $error && ! $notrigger)
			{
				// Call trigger
				$result=$this->call_trigger('LINEREPONSE_UPDATE',$user);
				if ($result < 0) $error++;
				// End call triggers
			}

			if (!$error) {
				$this->db->commit();
				return 1;
			}

			foreach($this->errors as $errmsg)
			{
				dol_syslog(get_class($this)."::update ".$errmsg, LOG_ERR);
				$this->error.=($this->error?', '.$errmsg:$errmsg);
			}
			$this->db->rollback();
			return -1*$error;
		}
		else
		{
			$this->error=$this->db->error();
			$this->db->rollback();
			return -2;
		}
	}
}


