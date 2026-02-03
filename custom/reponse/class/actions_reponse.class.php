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
 *  \file       htdocs/reponse/class/actions_reponse.class.php
 *  \ingroup    reponse
 *  \brief      File of class to manage actions
 */
require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/usergroup.class.php';
require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';

dol_include_once("/reponse/class/reponse.class.php");
dol_include_once("/reponse/lib/reponse.lib.php");

class ActionsReponse
{ 
	function addSearchEntry($parameters, &$object, &$action, $hookmanager)
	{
		global $langs, $db, $mysoc, $conf, $user;

		$langs->load('reponse@reponse');
	
		$arrayresult = array();
		$search_boxvalue = isset($parameters['search_boxvalue']) ? $parameters['search_boxvalue'] : '';

		if (! empty($conf->reponse->enabled) && $user->rights->reponse->lire)
		{
			$arrayresult['searchintoreponse'] = array(
				'position'=> 900, 
				'img' => 'object_reponse@reponse', 
				'label' => $langs->trans("SearchIntoReponses", $search_boxvalue), 
				'text' => img_picto('','object_reponse@reponse').' '.$langs->trans("SearchIntoReponses", $search_boxvalue), 
				'url'=> dol_buildpath('/reponse/list.php', 1).'?mainmenu=reponse'.($search_boxvalue?'&sall='.urlencode($search_boxvalue):'')
			);
		}

		$this->results = $arrayresult;
		return 0;
	}

	function emailElementlist($parameters, &$object, &$action, $hookmanager)
	{
		global $langs, $db, $mysoc, $conf, $user;

		$langs->load('reponse@reponse');
		
		$elementList = isset($parameters['elementList']) ? $parameters['elementList'] : array();
		$elementList['reponse_send'] = $langs->trans('MailToSendReponse');

		$this->results = $elementList;
		return 0;
	}

	//Afficher un bouton vers la liste des réponses du tiers
	function addMoreActionsButtons($parameters, &$object, &$action, $hookmanager)
    {
        global $langs, $db, $mysoc, $conf, $user;

        $langs->load('reponse@reponse');
		$contexts = explode(':',$parameters['context']);
		$context_to_display = array('thirdpartycard','projectcard','userdao');
        if ($user->rights->reponse->lire && array_intersect($context_to_display, $contexts))
        {
	print '<div class="inline-block divButAction"><a class="butAction gotoanswerbutton" href="' . dol_buildpath('/reponse/list.php',2) . '?search_fk_soc=' . $object->id . '"><i class="fas fa-question"></i> ' . $langs->trans('SeeResponse') . '</a></div>';
        }  
     }  
}


