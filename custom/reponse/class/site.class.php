<?php
/* Copyright (C) 2004-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2017 Mikael Carlavan <contact@mika-carl.fr>
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
 *  \file       htdocs/reponse/class/site.class.php
 *  \ingroup    reponse
 *  \brief      File of class to manage site
 */

require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php';


/**
 * Class to manage products or services
 */
class Site extends CommonObject
{
	public $element='site';
	public $table_element='';
	public $fk_element='';
	public $picto = '';
	public $ismultientitymanaged = 0;	// 0=No test on entity, 1=Test with field entity, 2=Test with link by societe


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
	 *	Start
	 * 
	 *	@return int			     		Id of gestion if OK, < 0 if KO
	 */
	function start(&$user)
	{
		global $conf, $langs, $mysoc, $db;

        $error = 0;

        // Load conf
        $entity = 1;
        if (GETPOSTISSET('entity')) {
            $entity = GETPOST('entity', 'int');
        } else if (!empty($_SESSION['dol_entity'])) {
            $entity = $_SESSION['dol_entity'];
        }

        $conf->entity = $entity;
        $conf->setValues($db);

        $_SESSION['dol_entity'] = $conf->entity;

        // Default user
        if (empty($conf->global->CONNECT_AGENT_ID)) {
            $this->addError($langs->trans('ErrorCantLoadAgentUser'));
            return -1;
        }
        $user->fetch($conf->global->CONNECT_AGENT_ID);

        if (isset($_SESSION["rep_login"]))
        {
            // We are already into an authenticated session
            $login = $_SESSION["rep_login"];
            $entity = $conf->entity;

            $resultFetchUser = $user->fetch('', $login, '', 1, ($entity > 0 ? $entity : -1));

            if ($resultFetchUser <= 0)
            {
                $prefix = dol_getprefix('');
                $sessionname = 'DOLSESSID_'.$prefix;

                $error++;
                // Account has been removed after login
                dol_syslog("Can't load user even if session logged. _SESSION['rep_login']=".$login, LOG_WARNING);
                session_destroy();
                session_name($sessionname);
                session_set_cookie_params(0, '/', null, false, true); // Add tag httponly on session cookie
                session_start();

                $message = $resultFetchUser == 0 ? $langs->trans("ErrorCantLoadUserFromDolibarrDatabase", $login) : $user->error;
                $this->addError($message);
            }
        }

        return $error > 0 ? -$error : $this->load($user);
    }
    

    /**
	 *	Start
	 * 
	 *	@return int			     		Id of gestion if OK, < 0 if KO
	 */
	function load(&$user)
	{
		global $conf, $langs, $mysoc;

        $error = 0;   
             
        // Store value into session (values always stored)
        $_SESSION["rep_login"] = $user->login;
        $_SESSION["dol_authmode"] = '';
		$_SESSION["dol_tz"] = '';
		$_SESSION["dol_tz_string"] = '';
		$_SESSION["dol_dst"] = 3;
		$_SESSION["dol_dst_observed"] = 3;
		$_SESSION["dol_dst_first"] = 3;
		$_SESSION["dol_dst_second"] = 3;
		$_SESSION["dol_screenwidth"] = 3;
		$_SESSION["dol_screenheight"] = 3;
		$_SESSION["dol_company"] = $conf->global->MAIN_INFO_SOCIETE_NOM;
        $_SESSION["dol_entity"] = $conf->entity;
        
        $user->update_last_login_date();

        // Check if user is active
        if ($user->statut < 1)
        {
            $error++;
            $this->addError($langs->trans("ErrorLoginDisabled"));
        }
        else
        {

            // Load permissions
            $user->clearrights();
            $user->getrights();
                
            $user->isAgent = $user->id == $conf->global->CONNECT_AGENT_ID;
            $user->isLoggedIn = !$user->isAgent;
            if (!empty($user->firstname) && !empty($user->lastname)) {
                $user->initials = substr($user->firstname, 0, 1).substr($user->lastname, 0, 1);
            } else {
                $user->initials = substr($user->email, 0, 1);
            }
        }

        return $error > 0 ? -$error : 1;
    }

    /**
	 *	Login
	 * 
	 *	@return int			     		Id of gestion if OK, < 0 if KO
	 */
	function login(&$user)
	{
		global $conf, $langs, $mysoc;

        $error = 0;

        $usertotest = GETPOST("login-username", "alpha");
        $passwordtotest = GETPOST('login-password', 'none');
        $entitytotest = (!empty($conf->entity) ? $conf->entity : 1);

        $result = checkLoginPassEntity($usertotest, $passwordtotest, $entitytotest, array('mc', 'dolibarr'));

        if ($result)
        {
            $resultFetchUser = $user->fetch('', $result, '', 1, ($entitytotest > 0 ? $entitytotest : -1));
            if ($resultFetchUser <= 0)
            {
                $prefix = dol_getprefix('');
                $sessionname = 'DOLSESSID_'.$prefix;

                dol_syslog('User not found, connexion refused');
                session_destroy();
                session_name($sessionname);
                session_set_cookie_params(0, '/', null, false, true); // Add tag httponly on session cookie
                session_start();
        
                $message = $resultFetchUser == 0 ? $langs->trans("ErrorCantLoadUserFromDolibarrDatabase", $result) : $user->error;
                $this->addError($message);
                $error++;
            }
            else
            {
                $_SESSION['rep_login'] = $user->login;
                $_SESSION['dol_entity'] = $entitytotest;
            }
        } 
        else 
        {
            $error++;
            // Bad password. No authmode has found a good password.
            // We set a generic message if not defined inside function checkLoginPassEntity or subfunctions
            $this->addError($langs->trans("ErrorBadLoginPassword"));
        }
    
        return $error > 0 ? -$error : $this->start($user);
    }


    function passwordrequest()
    {
        global $conf, $langs;

        $langs->load('connect@connect');

        $login = GETPOST("password-username", "alpha");

        $error = 0;
        if ($login)
		{
			$sql = "SELECT u.*";
			$sql.= " FROM ".MAIN_DB_PREFIX."user as u";
			$sql.= " WHERE u.email = '".$this->db->escape($login)."' OR u.login = '".$this->db->escape($login)."'";
			$sql.= " LIMIT 1";
			$result = $this->db->query($sql);

			if ($this->db->num_rows($result) <= 0)
			{
				$this->addError($langs->trans('ConnectUserNotFound'));
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
							$this->addMessage($langs->trans('ConnectConfirmationCodeSent'));
						}
						else
						{
                            $this->addError($langs->trans('ConnectErrorWhileSensingConfirmationCode'));
                            $error++;
						}
                    } 
                    else 
                    {
                        $this->addError($langs->trans('ConnectErrorWhileGeneratingConfirmationCode'));
                        $error++;
					}
				}
				else
				{
                    $this->addError($langs->trans('ConnectUserNotFound'));
                    $error++;
				}
			}
		}
		else
		{
            $this->addError($langs->trans('ConnectUserNotFound'));
            $error++;
		}

	    return $error > 0 ? -$error : 1;
    }


    function passwordvalidation()
    {
		global $conf, $langs;
		$langs->load('connect@connect');

        $login = GETPOST("validation-username", "alpha");
        $confirmation_code = GETPOST("validation-code", "alpha");

        $error = 0;
		if ($login)
		{
			$sql = "SELECT u.*";
			$sql.= " FROM ".MAIN_DB_PREFIX."user as u";
			$sql.= " WHERE u.email = '".$this->db->escape($login)."' OR u.login = '".$this->db->escape($login)."'";
			$sql.= " LIMIT 1";
			$result = $this->db->query($sql);

			if ($this->db->num_rows($result) <= 0)
			{
                $this->addError($langs->transnoentities('ConnectUserNotFound'));
                $error++;
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
							//$this->addMessage($langs->trans('ConnectPasswordSent'));
						}
						else
						{
                            $this->addError($langs->trans('ConnectErrorWhileSendingNewPassword'));
                            $error++;
						}
					}
					else
					{
                        $this->addError($langs->trans('ConnectConfirmationCodeDoesNotMatch'));
                        $error++;
					}
				}
				else
				{
                    $this->addError($langs->trans('ConnectUserNotFound'));
                    $error++;
				}
			}
		}
		else
		{
            $this->addError($langs->trans('ConnectUserNotFound'));
            $error++;
		}

	    return $error > 0 ? -$error : 1;
    }


    function register(&$user)
    {
        global $conf, $langs;

        $error = 0;

        $email = GETPOST('register-email', 'alpha');
        $login = GETPOST('register-login', 'alpha');
        $password = GETPOST('register-password', 'alpha');

         // check mandatory fields
         if (empty($email))
         {
             $error++;
             $this->addError($langs->transnoentities('ConnectEmailFieldIsMissing'));
         }        
 
         if ($this->checkEmail($email) < 0)
         {
             $error++;
         }

         if (empty($login))
         {
             $error++;
             $this->addError($langs->transnoentities('ConnectLoginFieldIsMissing'));
         }
 
         if (empty($password))
         {
            $error++;
            $this->addError($langs->transnoentities('ConnectPasswordFieldIsMissing'));
         }
 
         if (!$error)
         {
            $fuser = new User($this->db);
            $fuser->login = $login;
            $fuser->pass = $password;
            $fuser->email = $email;
            $fuser->api_key = dol_hash($login.uniqid().$conf->global->MAIN_API_KEY, 1);

            if ($fuser->create($user) < 0) 
            {
                $error++;
                if (!empty($fuser->error)) {
                    $this->addError($fuser->error);
                } else {
                    $this->addError($langs->transnoentities('ConnectErrorWhileCreatingUser'));
                }

                return -$error;
            } 
            else 
            {
                $user->fetch($fuser->id);
                // Ajout au groupe
                if ($conf->global->CONNECT_USERS_GROUP_ID > 0) {
                    $user->SetInGroup($conf->global->CONNECT_USERS_GROUP_ID, $conf->entity);
                }

                // Set reports and profiles
                $reportsIds = isset($_SESSION['dol_reports']) ? $_SESSION['dol_reports'] : array();

                if (count($reportsIds)) {
                    foreach ($reportsIds as $id) {
                        $reponse = new Reponse($this->db);

		                if ($reponse->fetch($id) > 0) {
                            $reponse->user_author_id = $user->id;
                            $reponse->update($user);
                        }
                    }
                }

                return $this->load($user);
            }
         }
         else
         {
             return -$error;
         }

    }

	/**
	 *	Check email
	 * 
	 *	@return int			     		Id if OK, < 0 if KO
	 */
    function checkEmail($email)
    {
        global $conf, $user, $langs;

        $langs->load('connect@connect');

        if (!isValidEmail($email))
        {
            $this->addError($langs->trans('ConnectIncorrectEmailAddress'));
            return -1;
        }

        $sql = "SELECT u.*";
        $sql.= " FROM ".MAIN_DB_PREFIX."user as u";
        $sql.= " WHERE u.email = '".$this->db->escape($email)."'";
        $sql.= " AND u.rowid <> ".$user->id;

        $result = $this->db->query($sql);

        if ($this->db->num_rows($result) > 0)
        {
            $this->addError($langs->trans('ConnectEmailAddressAlreadyUsed'));
            return -1;
        }

        return 1;
    }

    	/**
	 *	Check login
	 * 
	 *	@return int			     		Id if OK, < 0 if KO
	 */
    function checkLogin($login)
    {
        global $conf, $user, $langs;

        $langs->load('connect@connect');

        $sql = "SELECT u.*";
        $sql.= " FROM ".MAIN_DB_PREFIX."user as u";
        $sql.= " WHERE u.login = '".$this->db->escape($login)."'";
        $sql.= " AND u.rowid <> ".$user->id;

        $result = $this->db->query($sql);

        if ($this->db->num_rows($result) > 0)
        {
            $this->addError($langs->trans('ConnectLoginAlreadyUsed'));
            return -1;
        }

        return 1;
    }

    function addError($message)
    {
        global $conf, $langs, $user;

        $messages = isset($_SESSION['dol_errors']) ? $_SESSION['dol_errors'] : array();

        $messages[] = $message;
        $_SESSION['dol_errors'] = $messages;
    }

    function addMessage($message)
    {
        global $conf, $langs, $user;

        $messages = isset($_SESSION['dol_messages']) ? $_SESSION['dol_messages'] : array();

        $messages[] = $message;
        $_SESSION['dol_messages'] = $messages;
    }

    function addWarning($message)
    {
        global $conf, $langs, $user;

        $messages = isset($_SESSION['dol_warnings']) ? $_SESSION['dol_warnings'] : array();

        $messages[] = $message;
        $_SESSION['dol_warnings'] = $messages;
    }

    function getErrors()
    {
        global $conf, $langs, $user;

        $messages = isset($_SESSION['dol_errors']) ? $_SESSION['dol_errors'] : array();
        unset($_SESSION['dol_errors']);
        return $messages;
    }

    function getMessages()
    {
        global $conf, $langs, $user;
        $messages = isset($_SESSION['dol_messages']) ? $_SESSION['dol_messages'] : array();
        unset($_SESSION['dol_messages']);
        return $messages;
    }

    function getWarnings()
    {
        global $conf, $langs, $user;
        $messages = isset($_SESSION['dol_warnings']) ? $_SESSION['dol_warnings'] : array();
        unset($_SESSION['dol_warnings']);
        return $messages;
    }

    function makeUrl($endpoint)
    {
        global $conf;

        $url = $conf->global->REPONSE_ROOT_URL;
        $url = trim($url, '/');

        $url = $url . '/' .$endpoint;

        return $url;
    }
}