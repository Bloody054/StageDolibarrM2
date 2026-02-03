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
 *  \file       htdocs/questionnaire/class/actions_questionnaire.class.php
 *  \ingroup    questionnaire
 *  \brief      File of class to manage actions
 */
require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/usergroup.class.php';
require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';

dol_include_once("/questionnaire/class/questionnaire.class.php");
dol_include_once("/questionnaire/lib/questionnaire.lib.php");

class ActionsQuestionnaire
{ 
	function checkRowPerms($parameters, &$object, &$action, $hookmanager)
	{
		global $langs, $db, $mysoc, $conf, $user;

		$table_element_line = isset($parameters['table_element_line']) ? $parameters['table_element_line'] : ($object->table_element_line ?: '');
        $perm = isset($parameters['perm']) ? $parameters['perm'] : null;

        $result = 0;

		if ($table_element_line == 'questionnairedet') {
            $this->results = array('perm' => $user->rights->questionnaire->creer);
            $perm = $user->rights->questionnaire->creer;
            $result = 1;
		}

		return $result;
	}

    function emailElementlist($parameters, &$object, &$action, $hookmanager)
    {
        global $langs, $db, $mysoc, $conf, $user;

        $langs->load('questionnaire@questionnaire');

        $elementList = isset($parameters['elementList']) ? $parameters['elementList'] : array();
        $elementList['questionnaire_send'] = $langs->trans('MailToSendQuestionnaire');

        $this->results = $elementList;
        return 0;
    }
}


