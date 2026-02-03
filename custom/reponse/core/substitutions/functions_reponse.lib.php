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
 *	\file       htdocs/reponse/lib/reponse.lib.php
 *	\brief      Ensemble de fonctions de base pour le module reponse
 * 	\ingroup	reponse
 */
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php';

/**
 * Prepare array
 *
 * @return  array				Array of tabs to show
 */
function reponse_completesubstitutionarray(&$substitutionarray,$outputlangs,$object,$parameters)
{
	global $db, $langs, $conf, $user;
	$outputlangs->load("reponse@reponse");

	if (empty($object)) {
        $substitutionarray['__REPONSE_REF__'] = $outputlangs->trans('ReponseRef');
        $substitutionarray['__REPONSE_NAME__'] = $outputlangs->trans('ReponseName');
        $substitutionarray['__REPONSE_DATE__'] = $outputlangs->trans('ReponseDate');
        $substitutionarray['__REPONSE_ID__'] = $outputlangs->trans('ReponseID');
        foreach($object->lines as $l){
            $substitutionarray['__REPONSE_'.$l->code.'__'] = $outputlangs->trans('ReponseCODE');
        }
    } else if ($object->element == 'reponse') {

        $name = ($object->name ? $object->name : "");
        $date = dol_print_date($object->datec, "%d/%m/%Y");

        $substitutionarray['__REPONSE_NAME__'] = $name;
        $substitutionarray['__REPONSE_DATE__'] = $date;
        $substitutionarray['__REPONSE_REF__'] = $object->ref;
        $substitutionarray['__REPONSE_ID__'] = $object->id;
        foreach($object->lines as $l){
            $substitutionarray['__REPONSE_'.strtoupper($l->code).'__'] = $l->value;
        }
    }

	return $substitutionarray;
}
