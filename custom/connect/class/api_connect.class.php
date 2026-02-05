<?php
/* Copyright (C) 2015   Jean-François Ferry     <jfefe@aternatik.fr>
 * Copyright (C) 2016	Laurent Destailleur		<eldy@users.sourceforge.net>
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
 use Luracast\Restler\RestException;

 include_once DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php';
 include_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
 include_once DOL_DOCUMENT_ROOT.'/core/lib/security.lib.php';
 require_once DOL_DOCUMENT_ROOT.'/core/class/CMailFile.class.php';
 
 if (!empty($conf->alerte->enabled)) {
	dol_include_once("/alerte/class/api_alerte.class.php");
 }
 if (!empty($conf->profil->enabled)) {
	dol_include_once("/profil/class/api_profil.class.php");
 }
 if (!empty($conf->signalement->enabled)) {
	dol_include_once("/signalement/class/api_signalement.class.php");
 }
/**
 * API class for connects
 *
 * @access protected
 * @class  DolibarrApiAccess {@requires user,external}
 */
class ConnectApi extends DolibarrApi
{

    /**
     * @var array   $FIELDS     Mandatory fields, checked when create and update object
     */
    static $FIELDS = array();

	/**
	 * @var connect $connect {@type Connect}
	 */
	public $connect;

    /**
     * Constructor
     */
    function __construct()
    {
		global $db, $conf;
		$this->db = $db;
    }

	/**
	 * Get current version
	 * 
	 * @throws RestException
	 *
	 * @url GET /version
	 */
    function version()
    {
		global $conf, $langs;
		$langs->load('connect@connect');

		$version = $conf->global->CONNECT_API_VERSION;

		return array(
			'error' => false,
			'message' => '',
			'version' => $version
		);
	}

    /**
     * Get regions
     *
     * @throws RestException
     *
     * @url GET /regions
     */
    function regions()
    {
        global $conf, $langs;
        $langs->load('connect@connect');

        // Dictionnaires spécifiques
        $sql = "SELECT rowid, code_region as code, nom as label, active FROM ".MAIN_DB_PREFIX."c_regions ORDER BY label ASC";

        $objs = array();

        $result = $this->db->query($sql);

        if ($result) {
            $num = $this->db->num_rows($result);

            for ($i = 0; $i < $num; $i++) {
                $obj = $this->db->fetch_object($result);

                $obj->rowid = intval($obj->rowid);
                $obj->active = boolval($obj->active);

                $obj->dictionary = "c_regions";

                $objs[] = $obj;
            }
        }


        return $objs;
    }

    /**
     * Get states
     *
     * @throws RestException
     *
     * @url GET /states
     */
    function states()
    {
        global $conf, $langs;
        $langs->load('connect@connect');

        // Dictionnaires spécifiques
        $sql = "SELECT rowid, code_departement as code, nom as label, active FROM ".MAIN_DB_PREFIX."c_departements ORDER BY label ASC";

        $objs = array();

        $result = $this->db->query($sql);

        if ($result) {
            $num = $this->db->num_rows($result);

            for ($i = 0; $i < $num; $i++) {
                $obj = $this->db->fetch_object($result);

                $obj->rowid = intval($obj->rowid);
                $obj->active = boolval($obj->active);

                $obj->dictionary = "c_departements";

                $objs[] = $obj;
            }
        }


        return $objs;
    }

	/**
	 * Get countries
	 * 
	 * @throws RestException
	 *
	 * @url GET /countries
	 */
    function countries()
    {
		global $conf, $langs;
		$langs->load('connect@connect');

		// Dictionnaires spécifiques 
		$sql = "SELECT rowid, code, label, active FROM ".MAIN_DB_PREFIX."c_country ORDER BY label ASC";

		$objs = array();
		
		$result = $this->db->query($sql);

		if ($result) {
			$num = $this->db->num_rows($result);

			for ($i = 0; $i < $num; $i++) {
				$obj = $this->db->fetch_object($result);

				$obj->rowid = intval($obj->rowid);
				$obj->active = boolval($obj->active);

				$obj->dictionary = "c_country";

				$objs[] = $obj;
			}
		}	


		return $objs;
	}

		/**
	 * Get towns
	 * 
	 * @throws RestException
	 *
	 * @url GET /towns
	 */
    function towns()
    {
		global $conf, $langs;
		$langs->load('connect@connect');

		// Dictionnaires spécifiques 
		$sql = "SELECT rowid, code, label, active FROM ".MAIN_DB_PREFIX."c_ville ORDER BY label ASC";

		$objs = array();

		$result = $this->db->query($sql);

		if ($result) {
			$num = $this->db->num_rows($result);

			for ($i = 0; $i < $num; $i++) {
				$obj = $this->db->fetch_object($result);

				$obj->rowid = intval($obj->rowid);
				$obj->active = boolval($obj->active);

				$obj->dictionary = "c_ville";

				$objs[] = $obj;
			}
		}	


		return $objs;
	}


	/**
	 * Get dictionaries
	 * 
	 * @throws RestException
	 *
	 * @url GET /mindictionaries
	 */
    function mindictionaries()
    {
		global $conf, $langs;
		$langs->load('connect@connect');


		// Dictionnaires liés aux formulaires
		$modules = array(
			"formulaire",
			"profil"
		);

		$objs = array();
		$dir = $conf->formulaire->dir_output;


		// Dictionnaires liés aux formulaires
		foreach ($modules as $module) {
			$file = dol_buildpath("/".$module."/core/modules/mod".ucfirst($module).".class.php");

			if (file_exists($file)) {
				include_once($file);

				$classname = "mod".ucfirst($module);

				$mod = new $classname($this->db);

				$dictionaries = $mod->dictionaries;

				if (count($dictionaries)) {
					$tabname = $dictionaries['tabname'];
					$tabsql = $dictionaries['tabsql'];

					if (count($tabname)) {
						foreach ($tabname as $tabi => $name) {
							$dictionary = str_replace(MAIN_DB_PREFIX, '', $name);

							if (in_array($dictionary, array("c_ville", "c_departements", "c_regions"))) {
								continue;
							}

							$sql = $tabsql[$tabi];

							$result = $this->db->query($sql);

							if ($result) {
								$num = $this->db->num_rows($result);

								for ($i = 0; $i < $num; $i++) {
									$obj = $this->db->fetch_object($result);

									$file = $obj->thumbnail;
									$thumbnail = $dir."/".$file;

									if (file_exists($thumbnail)) {
										$obj->thumbnail_rawdata = base64_encode(file_get_contents($thumbnail));
									} else {
										$obj->thumbnail_rawdata = null;
									}

									$obj->rowid = intval($obj->rowid);
									$obj->active = boolval($obj->active);
									
									$obj->dictionary = $dictionary;


									$objs[] = $obj;
								}

							}						
						}
					}
				}
			}
		}

		return $objs;
	}
	/**
	 * Get dictionaries
	 * 
	 * @throws RestException
	 *
	 * @url GET /dictionaries
	 */
    function dictionaries()
    {
		global $conf, $langs;
		$langs->load('connect@connect');

		// Dictionnaires spécifiques 
		$commons = array(
			array(
				"dictionary" => "c_country",
				"sql" => "SELECT rowid, code, label, active FROM ".MAIN_DB_PREFIX."c_country ORDER BY label ASC"
			)
		);

		// Dictionnaires liés aux formulaires
		$modules = array(
			"formulaire",
			"profil"
		);

		$objs = array();
		$dir = $conf->formulaire->dir_output;

		// Dictionnaires spécifiques 
		if (count($commons)) {
			foreach ($commons as $common) {
				$sql = $common["sql"];
				$dictionary = $common["dictionary"];

				$result = $this->db->query($sql);

				if ($result) {
					$num = $this->db->num_rows($result);

					for ($i = 0; $i < $num; $i++) {
						$obj = $this->db->fetch_object($result);

						$obj->rowid = intval($obj->rowid);
						$obj->active = boolval($obj->active);

						$obj->dictionary = $dictionary;

						$objs[] = $obj;
					}
				}	
			}
		}


		// Dictionnaires liés aux formulaires
		foreach ($modules as $module) {
			$file = dol_buildpath("/".$module."/core/modules/mod".ucfirst($module).".class.php");

			if (file_exists($file)) {
				include_once($file);

				$classname = "mod".ucfirst($module);

				$mod = new $classname($this->db);

				$dictionaries = $mod->dictionaries;

				if (count($dictionaries)) {
					$tabname = $dictionaries['tabname'];
					$tabsql = $dictionaries['tabsql'];

					if (count($tabname)) {
						foreach ($tabname as $tabi => $name) {
							$dictionary = str_replace(MAIN_DB_PREFIX, '', $name);

							$sql = $tabsql[$tabi];

							$result = $this->db->query($sql);

							if ($result) {
								$num = $this->db->num_rows($result);

								for ($i = 0; $i < $num; $i++) {
									$obj = $this->db->fetch_object($result);

									$file = $obj->thumbnail;
									$thumbnail = $dir."/".$file;

									if (file_exists($thumbnail)) {
										$obj->thumbnail_rawdata = base64_encode(file_get_contents($thumbnail));
									} else {
										$obj->thumbnail_rawdata = null;
									}

									$obj->rowid = intval($obj->rowid);
									$obj->active = boolval($obj->active);
									
									$obj->dictionary = $dictionary;


									$objs[] = $obj;
								}

							}						
						}
					}
				}
			}
		}

		return $objs;
	}

	/**
	 * Register
	 *
	 * @param string $mail   Email
	 * @param string $login   Login
	 * @param string $password   Password

	 * @return object      Authenticated user
	 *
	 * @throws RestException
	 *
	 * @url POST /register
	 */
    function register($mail = null, $login = null, $password = null)
    {
		global $conf, $langs;
		$langs->load('connect@connect');

		// check user authorization
		
	    /*if(! DolibarrApiAccess::$user->rights->user->user->creer) {
	       throw new RestException(401, $langs->transnoentities('ConnectAccessNotAllowed'));
		}*/
		
		// check mandatory fields
		if (empty($mail))
		{
			throw new RestException(400, $langs->transnoentities('ConnectEmailFieldIsMissing'));
		}
		else
		{
			$this->_checkEmail($mail);
		}

		if (empty($login))
		{
			throw new RestException(400, $langs->transnoentities('ConnectLoginFieldIsMissing'));
		}
		else
		{
			$this->_checkLogin($login);
		}

		if (empty($password))
		{
			throw new RestException(400, $langs->transnoentities('ConnectPasswordFieldIsMissing'));
		}

		$user = new User($this->db);
		$user->login = $login;
		$user->pass = $password;
		$user->email = $mail;
		$user->api_key = dol_hash($login.uniqid().$conf->global->MAIN_API_KEY, 1);


	    if ($user->create(DolibarrApiAccess::$user) < 0) {
             throw new RestException(500, $langs->transnoentities('ConnectErrorWhileCreatingUser'), array_merge(array($user->error), $user->errors));
		}
		
		$user->fetch($user->id);

		// Ajout au groupe
		if ($conf->global->CONNECT_USERS_GROUP_ID > 0) {
			$user->SetInGroup($conf->global->CONNECT_USERS_GROUP_ID, $conf->entity);
		}

	    return $this->_cleanObjectDatas($user);
	}

	/**
	 * Request password
	 *
	 * @param string $login   Login
	 * 
	 * @throws RestException
	 *
	 * @url POST /request/password
	 */
    function requestpassword($login = null)
    {
		global $conf, $langs;
		$langs->load('connect@connect');

		if ($login)
		{
			$sql = "SELECT u.*";
			$sql.= " FROM ".MAIN_DB_PREFIX."user as u";
			$sql.= " WHERE u.email = '".$this->db->escape($login)."' OR u.login = '".$this->db->escape($login)."'";
			$sql.= " LIMIT 1";
			$result = $this->db->query($sql);

			if ($this->db->num_rows($result) <= 0)
			{
				throw new RestException(404, $langs->transnoentities('ConnectUserNotFound'),array());
			}
			else
			{
				$obj = $this->db->fetch_object($result);

				$id = $obj->rowid;

				$user = new User($this->db);
				if ($user->fetch($id) > 0)
				{
					$newpassword = $user->setPassword($user, '', 1);

					if (!empty($newpassword)) {

						$outputlangs = new Translate("", $conf);
						if (isset($conf->global->MAIN_LANG_DEFAULT) && $conf->gobal->MAIN_LANG_DEFAULT != 'auto')
						{	// If user has defined its own language (rare because in most cases, auto is used)
							$outputlangs->setDefaultLang($conf->global->MAIN_LANG_DEFAULT);
						}
						else
						{	// If user has not defined its own language, we used current language
							$outputlangs = $langs;
						}

						$confirmation_code = dol_hash($newpassword, 'sha1');
						$confirmation_code = substr($confirmation_code, -4);

						// Load translation files required by the page
						$outputlangs->loadLangs(array("main", "errors", "users", "other"));

						$outputlangs->load('connect@connect');

						$subject = $outputlangs->transnoentitiesnoconv("ConnectSubjectRequestPassword");

						$mesg = $outputlangs->transnoentitiesnoconv("ConnectRequestPasswordReceived")."<br /><br />";
						$mesg.= $outputlangs->transnoentitiesnoconv("ConnectConfirmationCode", $confirmation_code)."<br /><br />";
						$mesg.= $outputlangs->transnoentitiesnoconv("ConnectForgetIfNothing")."<br /><br />";

						$msgishtml = 1;
									
						$mailfile = new CMailFile(
							$subject,
							$user->email,
							$conf->global->MAIN_MAIL_EMAIL_FROM,
							$mesg,
							array(),
							array(),
							array(),
							'',
							'',
							0,
							$msgishtml
						);
				
						// Success
						if ($mailfile->sendfile())
						{
							throw new RestException(200, $langs->transnoentities('ConnectConfirmationCodeSent'));
						}
						else
						{
							throw new RestException(500, $langs->transnoentities('ConnectErrorWhileSensingConfirmationCode'));
						}
					} else {
						throw new RestException(401, $langs->transnoentities('ConnectErrorWhileGeneratingConfirmationCode'),array());
					}
				}
				else
				{
					throw new RestException(404, $langs->transnoentities('ConnectUserNotFound'),array());
				}
			}
		}
		else
		{
			throw new RestException(404, $langs->transnoentities('ConnectUserNotFound'),array());
		}

	    return true;
	}

	/**
	 * Reset password
	 *
	 * @param string $login   Login
	 * @param string $confirmation_code   Confirmation code

	 * @throws RestException
	 *
	 * @url POST /reset/password
	 */
    function resetpassword($login = null, $confirmation_code = null)
    {
		global $conf, $langs;
		$langs->load('connect@connect');

		if ($login)
		{
			$sql = "SELECT u.*";
			$sql.= " FROM ".MAIN_DB_PREFIX."user as u";
			$sql.= " WHERE u.email = '".$this->db->escape($login)."' OR u.login = '".$this->db->escape($login)."'";
			$sql.= " LIMIT 1";
			$result = $this->db->query($sql);

			if ($this->db->num_rows($result) <= 0)
			{
				throw new RestException(404, $langs->transnoentities('ConnectUserNotFound'),array());
			}
			else
			{
				$obj = $this->db->fetch_object($result);

				$id = $obj->rowid;

				$user = new User($this->db);
				if ($user->fetch($id) > 0)
				{
					$newpassword = $user->pass_temp;

					$confirmation = dol_hash($newpassword, 'sha1');
					$confirmation = substr($confirmation, -4);

					if ($confirmation_code == $confirmation)
					{
						$newpassword = $user->setPassword($user, $user->pass_temp, 0);

						$outputlangs = new Translate("", $conf);
						if (isset($conf->global->MAIN_LANG_DEFAULT) && $conf->gobal->MAIN_LANG_DEFAULT != 'auto')
						{	// If user has defined its own language (rare because in most cases, auto is used)
							$outputlangs->setDefaultLang($conf->global->MAIN_LANG_DEFAULT);
						}
						else
						{	// If user has not defined its own language, we used current language
							$outputlangs = $langs;
						}

						// Load translation files required by the page
						$outputlangs->loadLangs(array("main", "errors", "users", "other"));

						$outputlangs->load('connect@connect');

						$subject = $outputlangs->transnoentitiesnoconv("ConnectSubjectResetPassword");

						$mesg = $outputlangs->transnoentitiesnoconv("ConnectResetPassword")."<br /><br />";
						$mesg.= $outputlangs->transnoentitiesnoconv("ConnectNewPassword", $newpassword)."<br /><br />";

						$msgishtml = 1;
			
						$mailfile = new CMailFile(
							$subject,
							$user->email,
							$conf->global->MAIN_MAIL_EMAIL_FROM,
							$mesg,
							array(),
							array(),
							array(),
							'',
							'',
							0,
							$msgishtml
						);
				
						// Success
						if ($mailfile->sendfile())
						{
							throw new RestException(200, $langs->transnoentities('ConnectPasswordSent'));
						}
						else
						{
							throw new RestException(500, $langs->transnoentities('ConnectErrorWhileSendingNewPassword'));
						}
					}
					else
					{
						throw new RestException(401, $langs->transnoentities('ConnectConfirmationCodeDoesNotMatch'));
					}
				}
				else
				{
					throw new RestException(404, $langs->transnoentities('ConnectUserNotFound'),array());
				}
			}
		}
		else
		{
			throw new RestException(404, $langs->transnoentities('ConnectUserNotFound'),array());
		}

	    return true;
	}

	/**
	 * Get authenticated user
	 *
	 * @return object      Authenticated user
	 *
	 * @throws RestException
	 *
	 * @url GET /user
	 */
	public function user()
	{
		global $conf, $langs;
		$langs->load('connect@connect');

		$user = DolibarrApiAccess::$user;

		$dir = $conf->user->dir_output;
		$photo_rawdata = '';
		if (! empty($user->photo))
		{
			$file = get_exdir(0, 0, 0, 0, $user, 'user').$user->id.'/'.getImageFileNameForSize($user->photo, '');

			//$file = get_exdir(0, 0, 0, 0, $user, 'user').'/'.$user->id.'/'.$user->photo;
			$entity = (! empty($user->entity) ? $user->entity : $conf->entity);

			if ($file && file_exists($dir."/".$file))
			{
				$fullpath_original_file = $dir."/".$file;//DOL_MAIN_URL_ROOT.'/viewimage.php?modulepart=userphoto&entity='.$entity.'&file='.urlencode($file).'&cache=0';

				$fullpath_original_file_osencoded = dol_osencode($fullpath_original_file);

				$photo_rawdata = base64_encode(file_get_contents($fullpath_original_file_osencoded));
			}
		}

		$user->photo_rawdata = $photo_rawdata;
		$user->is_agent = $user->id == $conf->global->CONNECT_AGENT_ID;

		$alerts = array();
		$reports = array();
		$profiles = array();

		if (!$user->is_agent) {
			if (!empty($conf->alerte->enabled)) {
				$api = new AlerteApi();
				try {
					$alerts = $api->index();
				} catch (\Exception $e) {
					//var_dump($e);
				}
			 }
			 if (!empty($conf->profil->enabled)) {
				$api = new ProfilApi();
				try {
					$profiles = $api->index();
				} catch (\Exception $e) {
					//var_dump($e);
				}
			 }
			 if (!empty($conf->signalement->enabled)) {
				$api = new SignalementApi();
				try {
					$reports = $api->index();
				} catch (\Exception $e) {
					//var_dump($e);
				}		 
			}		
		}

		$user->alerts = $alerts;
		$user->reports = $reports;
		$user->profiles = $profiles;

		$obj = $this->_cleanObjectDatas($user);
		
		return $obj;
	}

	/**
	 * Get agent 
	 *
	 * @return object      Agent
	 *
	 * @throws RestException
	 *
	 * @url GET /agent
	 */
	public function agent()
	{
		global $conf, $langs;
		$langs->load('connect@connect');

		$user = new User($this->db);

		if ($user->fetch($conf->global->CONNECT_AGENT_ID) > 0) {
			$dir = $conf->user->dir_output;
			$photo_rawdata = '';
			if (! empty($user->photo))
			{
				$file = get_exdir(0, 0, 0, 0, $user, 'user').$user->id.'/'.getImageFileNameForSize($user->photo, '_mini');
	
				//$file = get_exdir(0, 0, 0, 0, $user, 'user').'/'.$user->id.'/'.$user->photo;
				$entity = (! empty($user->entity) ? $user->entity : $conf->entity);
	
				if ($file && file_exists($dir."/".$file))
				{
					$fullpath_original_file = $dir."/".$file;//DOL_MAIN_URL_ROOT.'/viewimage.php?modulepart=userphoto&entity='.$entity.'&file='.urlencode($file).'&cache=0';
	
					$fullpath_original_file_osencoded = dol_osencode($fullpath_original_file);
	
					$photo_rawdata = base64_encode(file_get_contents($fullpath_original_file_osencoded));
				}
			}
	
			$user->photo_rawdata = $photo_rawdata;
			$user->is_agent = true;
	
			$user->alerts = array();
			$user->reports = array();
			$user->profiles = array();

			$obj = $this->_cleanObjectDatas($user);

			return $obj;
		} else {
			throw new RestException(500, $langs->transnoentities('ConnectUserNotFound'));
		}
	}

	/**
	 * Create user account
	 *
	 * @param string $mail   Email
	 * @param string $firstname   Firstname
	 * @param string $lastname   Lastname
	 * 
	 * @throws RestException
	 *
	 * @url POST /user
	 */
	/*
    function post($mail = null, $firstname = null, $lastname = null)
    {
		global $conf, $langs;
		$langs->load('connect@connect');

		$user = new User($this->db);

		$this->_checkEmail($mail);

		if ($mail)
		{
			$user->email = $mail;
		}

		if ($firstname)
		{
			$user->firstname = $firstname;
		}		
		
		if ($lastname)
		{
			$user->lastname = $lastname;
		}

	    if ($user->create(DolibarrApiAccess::$user) < 0) {
             throw new RestException(500, $langs->transnoentities('ConnectErrorWhileCreatingUser'), array_merge(array($user->error), $user->errors));
	    }
	    return $user->id;
    }*/

	/**
	 * Update user email
	 * 
	 * @param string $mail   Email
	 * 
	 * @throws RestException
	 *
	 * @url PUT /user/email
	 */
    function updateemail($mail = null)
    {
		global $conf, $langs;
		$langs->load('connect@connect');


		$user = DolibarrApiAccess::$user;

		if ($user->id != $conf->global->CONNECT_AGENT_ID) {
			if (!DolibarrApi::_checkAccessToResource('user', $user->id, 'user'))
			{
				throw new RestException(401, $langs->transnoentities('ConnectAccessNotAllowed'));
			}
			
			$this->_checkEmail($mail);
	
			if ($mail)
			{
				$user->email = $mail;
			}
	
			// If there is no error, update() returns the number of affected
			// rows so if the update is a no op, the return value is zezo.
			if ($user->update(DolibarrApiAccess::$user) >= 0)
			{
				return $this->user();
			}
			else
			{
				throw new RestException(500, $user->error);
			}
		} else {
			throw new RestException(401, $langs->transnoentities('ConnectAccessNotAllowed'));
		}
	}

	/**
	 * Update user lastname
	 * 
	 * @param string $lastname   Lastname
	 * 
	 * @throws RestException
	 *
	 * @url PUT /user/lastname
	 */
    function updatelastname($lastname = null)
    {
		global $conf, $langs;
		$langs->load('connect@connect');

		$user = DolibarrApiAccess::$user;

		if ($user->id != $conf->global->CONNECT_AGENT_ID) {
			if (!DolibarrApi::_checkAccessToResource('user', $user->id, 'user'))
			{
				throw new RestException(401, $langs->transnoentities('ConnectAccessNotAllowed'));
			}	
			
			if ($lastname)
			{
				$user->lastname = $lastname;
			}
			// If there is no error, update() returns the number of affected
			// rows so if the update is a no op, the return value is zezo.
			if ($user->update(DolibarrApiAccess::$user) >= 0)
			{
				return $this->user();
			}
			else
			{
				throw new RestException(500, $user->error);
			}
		} else {
			throw new RestException(401, $langs->transnoentities('ConnectAccessNotAllowed'));
		}
	}

	/**
	 * Update user firstname
	 * 
	 * @param string $firstname   Firstname
	 * 
	 * @throws RestException
	 *
	 * @url PUT /user/firstname
	 */
    function updatefirstname($firstname = null)
    {
		global $conf, $langs;
		$langs->load('connect@connect');

		$user = DolibarrApiAccess::$user;

		if ($user->id != $conf->global->CONNECT_AGENT_ID) {
			if (!DolibarrApi::_checkAccessToResource('user', $user->id, 'user'))
			{
				throw new RestException(401, $langs->transnoentities('ConnectAccessNotAllowed'));
			}
	
			if ($firstname)
			{
				$user->firstname = $firstname;
			}		
	
			// If there is no error, update() returns the number of affected
			// rows so if the update is a no op, the return value is zezo.
			if ($user->update(DolibarrApiAccess::$user) >= 0)
			{
				return $this->user();
			}
			else
			{
				throw new RestException(500, $user->error);
			}
		} else {
			throw new RestException(401, $langs->transnoentities('ConnectAccessNotAllowed'));
		}
	}

	/**
	 * Update user password
	 * 
	 * @param string $password   Password
	 * 
	 * @throws RestException
	 *
	 * @url PUT /user/password
	 */
    function updatepassword($password = null)
    {
		global $conf, $langs;
		$langs->load('connect@connect');

		$user = DolibarrApiAccess::$user;

		if ($user->id != $conf->global->CONNECT_AGENT_ID) {
			if (!DolibarrApi::_checkAccessToResource('user', $user->id, 'user'))
			{
				throw new RestException(401, $langs->transnoentities('ConnectAccessNotAllowed'));
			}
	
			if ($password)
			{
				$user->pass = $password;
			}		
	
			// If there is no error, update() returns the number of affected
			// rows so if the update is a no op, the return value is zezo.
			if ($user->update(DolibarrApiAccess::$user) >= 0)
			{
				return $this->user();
			}
			else
			{
				throw new RestException(500, $user->error);
			}
		} else {
			throw new RestException(401, $langs->transnoentities('ConnectAccessNotAllowed'));
		}
	}

	/**
	 * Update user photo
	 * 
	 * 
	 * @throws RestException
	 *
	 * @url POST /user/photo
	 */
    function updatephoto()
    {
		global $conf, $langs;
		$langs->load('connect@connect');

		$user = DolibarrApiAccess::$user;

		if ($user->id != $conf->global->CONNECT_AGENT_ID) {
			$result = 0;
			if (isset($_FILES['file']['tmp_name']) && trim($_FILES['file']['tmp_name'])) {
	
				$dir = $conf->user->dir_output.'/'.get_exdir(0, 0, 0, 0, $user, 'user').$user->id;
	
				// Remove old
				@dol_delete_dir_recursive($dir);
				dol_mkdir($dir);
	
				if (@is_dir($dir)) {
					$photo = dol_sanitizeFileName($_FILES['file']['name']);
					$fullpath = $dir.'/'.$photo;
	
					$result = dol_move_uploaded_file($_FILES['file']['tmp_name'], $fullpath, 1, 0, $_FILES['file']['error']);
	
					if (!$result > 0) {
						throw new RestException(500, $langs->transnoentities('ConnectErrorWhileSavingFile'));
					} else {		
						// Create thumbs
						$user->addThumbs($fullpath);
	
						$user->photo = $photo;
						$result = $user->update($user);
					}
				} else {
					throw new RestException(500, $langs->transnoentities('ConnectErrorWhileCreatingUserDirectory'));
				}
			}

			return $this->user();
		} else {
			return $this->agent();
		}
	}

	/**
	 * Update user device token for push notifications
	 * 
	 * @param string $device_token   Device token
	 * @param string $ios_device   Device is iOS
	 * 
	 * @throws RestException
	 *
	 * @url PUT /user/device
	 */
    function updatedevice($device_token = null, $ios_device = false)
    {
		global $conf, $langs;
		$langs->load('connect@connect');

		$user = DolibarrApiAccess::$user;

		if (!DolibarrApi::_checkAccessToResource('user', $user->id, 'user'))
		{
			throw new RestException(401, $langs->transnoentities('ConnectAccessNotAllowed'));
		}

		$isAgent = $user->id == $conf->global->CONNECT_AGENT_ID;

		$array_options = $user->array_options;

		if (!$isAgent) {
			$array_options['options_device_token'] = $device_token;
			$array_options['options_ios_device'] = $ios_device;			
		}

		$user->array_options = $array_options;

		if ($user->insertExtraFields()	 >= 0)
		{
			return $this->user();
		}
		else
		{
			throw new RestException(500, $user->error);
		}
	}

	/**
	 * Delete user photo
	 * 
	 * 
	 * @throws RestException
	 *
	 * @url GET /user/photo
	 */
    function deletephoto()
    {
		global $conf, $langs;
		$langs->load('connect@connect');

		$user = DolibarrApiAccess::$user;

		if ($user->id != $conf->global->CONNECT_AGENT_ID) {
			$dir = $conf->user->dir_output;
			$result = 0;
			if (! empty($user->photo))
			{
				$fileimg = $conf->user->dir_output.'/'.get_exdir(0, 0, 0, 0, $user, 'user').'/'.$user->id.'/'.$user->photo;
				$dirthumbs = $conf->user->dir_output.'/'.get_exdir(0, 0, 0, 0, $user, 'user').'/'.$user->id.'/thumbs';
				
				@dol_delete_file($fileimg);
				@dol_delete_dir_recursive($dirthumbs);
	
				 $user->photo = '';
				$result = $user->update($user);
			}
	
			if ($result >= 0)
			{
				return $this->user();
			}
			else
			{
				throw new RestException(500, $user->error);
			}
		} else {
			throw new RestException(401, $langs->transnoentities('ConnectAccessNotAllowed'));
		}
	}
	
    /**
     * Clean sensible object datas
     *
     * @param   object  $object    Object to clean
     * @return    array    Array of cleaned object properties
     */
    function _cleanObjectDatas($object)
    {
		global $conf, $langs;
		$langs->load('connect@connect');

		$array_options = $object->array_options;
		

		$ret = new stdClass();
		$ret->id = intval($object->id);
		$ret->login = $object->login;
		$ret->email = $object->email;
		$ret->firstname = $object->firstname;
		$ret->lastname = $object->lastname;
		$ret->datec = intval($object->datec);
		$ret->photo_rawdata = $object->photo_rawdata;
		$ret->is_agent = $object->is_agent ? true : false;
		$ret->api_token = $object->api_key;
		$ret->device_token = isset($array_options['options_device_token']) ? $array_options['options_device_token'] : '';

		$ret->alerts = isset($object->alerts) ? $object->alerts : array();
		$ret->reports = isset($object->reports) ? $object->reports : array();
		$ret->profiles = isset($object->profiles) ? $object->profiles : array();

		return $ret;
	}
	
    /**
     * Check email
     *
     */
    function _checkEmail($email)
    {
		global $conf, $langs;

		$langs->load('connect@connect');

		if ($email)
		{
			$sql = "SELECT u.*";
			$sql.= " FROM ".MAIN_DB_PREFIX."user as u";
			$sql.= " WHERE u.email = '".$this->db->escape($email)."'";
			$sql.= " AND u.rowid <> ".DolibarrApiAccess::$user->id;

			$result = $this->db->query($sql);

			if ($this->db->num_rows($result) > 0)
			{
				throw new RestException(400, $langs->transnoentities('ConnectEmailAddressAlreadyUsed'), array());
			}

			if (!isValidEmail($email))
			{
				throw new RestException(400, $langs->transnoentities('ConnectIncorrectEmailAddress'), array());
			}
		}

		return true;
	}	

	    	/**
	 *	Check login
	 * 
	 *	@return int			     		Id if OK, < 0 if KO
	 */
    function _checkLogin($login)
    {
        global $conf, $user, $langs;

        $langs->load('connect@connect');

		if ($login)
		{
			$sql = "SELECT u.*";
			$sql.= " FROM ".MAIN_DB_PREFIX."user as u";
			$sql.= " WHERE u.login = '".$this->db->escape($login)."'";
			$sql.= " AND u.rowid <> ".$user->id;
	
			$result = $this->db->query($sql);
	
			if ($this->db->num_rows($result) > 0)
			{
				throw new RestException(400, $langs->transnoentities('ConnectLoginAlreadyUsed'), array());
			}
		}

        return true;
    }
}
