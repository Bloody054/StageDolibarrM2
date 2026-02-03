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
 *  \file       htdocs/veriflog/class/actions_veriflog.class.php
 *  \ingroup    veriflog
 *  \brief      File of class to manage actions
 */
require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/usergroup.class.php';
require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
include_once DOL_DOCUMENT_ROOT.'/core/class/interfaces.class.php';

dol_include_once("/veriflog/lib/veriflog.lib.php");

class ActionsVerifLog
{ 
	function afterLogin($parameters, &$object, &$action, $hookmanager)
	{
		global $langs, $db, $mysoc, $conf, $user;

		if (is_object($user) && $user->id > 0) {
            $user->getrights();

            if (empty($user->rights->veriflog->lire) && !$user->admin) {
                $interface=new Interfaces($db);
                $interface->run_triggers('USER_LOGOUT', $user, $user, $langs, $conf);

                $action='';
                if (is_object($hookmanager)) {
                    $hookmanager->initHooks(array('logout'));
                    $parameters = array();
                    $hookmanager->executeHooks('afterLogout', $parameters, $user, $action);    // Note that $action and $object may have been modified by some hooks
                }

                // Define url to go after disconnect
                $urlfrom = dol_buildpath('veriflog/public/index.php', 1);

                // Define url to go
                $url = DOL_URL_ROOT."/index.php";		// By default go to login page
                if ($urlfrom) $url = DOL_URL_ROOT.$urlfrom;

                if (session_status() === PHP_SESSION_ACTIVE)
                {
                    session_destroy();
                }

                // Not sure this is required
                unset($_SESSION['dol_login']);
                unset($_SESSION['dol_entity']);
                unset($_SESSION['urlfrom']);

                header("Location: ".$url);
                exit;
            }
        }


		$this->results = '';
		return 0;
	}

    function updateSession($parameters, &$object, &$action, $hookmanager)
    {
        return $this->afterLogin($parameters,$object,$action, $hookmanager);
    }
}


