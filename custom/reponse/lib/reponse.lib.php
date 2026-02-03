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
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/link.class.php';
/**
 * Prepare array with list of tabs
 *
 * @return  array				Array of tabs to show
 */
function reponse_prepare_admin_head()
{
	global $db, $langs, $conf, $user;
	$langs->load("reponse@reponse");

	$h = 0;
	$head = array();

	$head[$h][0] = dol_buildpath("/reponse/admin/setup.php", 1);
	$head[$h][1] = $langs->trans("Settings");
	$head[$h][2] = 'settings';
	$h++;

	//Necessary to move from v1 to v2 but everything is normally in v2 now
    // $head[$h][0] = dol_buildpath("/reponse/admin/upgrade.php", 1);
    // $head[$h][1] = $langs->trans("Upgrade");
    // $head[$h][2] = 'upgrade';
    // $h++;

	$head[$h][0] = dol_buildpath("/reponse/admin/extrafields.php", 1);
	$head[$h][1] = $langs->trans("Extrafields");
	$head[$h][2] = 'attributes';
	$h++;

	$head[$h][0] = dol_buildpath("/reponse/admin/about.php", 1);
	$head[$h][1] = $langs->trans("About");
	$head[$h][2] = 'about';
	$h++;

	return $head;
}

/**
 * Prepare array with list of tabs
 *
 * @return  array				Array of tabs to show
 */
function reponse_prepare_head($object)
{
	global $db, $langs, $conf, $user;
	$langs->load("reponse@reponse");

	$h = 0;
	$head = array();

	if ($user->rights->reponse->lire)
	{
		$head[$h][0] = dol_buildpath("/reponse/card.php?id=".$object->id, 1);
		$head[$h][1] = $langs->trans("ReponseCard");
		$head[$h][2] = 'reponse';
		$h++;
	}

	$upload_dir = $conf->reponse->dir_output . "/" . dol_sanitizeFileName($object->ref);
	$nbFiles = count(dol_dir_list($upload_dir,'files',0,'','(\.meta|_preview.*\.png)$'));
	$nbLinks = Link::count($db, $object->element, $object->id);
	
	$head[$h][0] = dol_buildpath("/reponse/document.php?id=".$object->id, 1);
	$head[$h][1] = $langs->trans('Documents');
	if (($nbFiles+$nbLinks) > 0) $head[$h][1].= ' <span class="badge">'.($nbFiles+$nbLinks).'</span>';
	$head[$h][2] = 'documents';
	$h++;

	$head[$h][0] = dol_buildpath("/reponse/info.php?id=".$object->id, 1);
	$head[$h][1] = $langs->trans("Info");
	$head[$h][2] = 'info';
	$h++;

	return $head;
}

function reponse_banner_tab($object)
{
    global $langs;

    $out = '<div class="arearef">';
    $out .= img_picto('', 'object_'.$object->element).' ';
    $out.= '<div class="inline-block floatleft">';
    	$out.= '<div class="floatleft inline-block valignmiddle divphotoref">';
    		$out.= '<div class="photoref">';
    
		    if(empty($object->icon) ? $object->icon=$object->questionnaire->icon : '');
		    if(empty($object->icon)){
		        		$out .= img_object('', 'reponse@reponse', 'class="classfortooltip" width=24', 0, 0);
		        	}else{
		        		$out .= '<img src="'.dol_buildpath('/questionnaire/icons/'.$object->icon,2).'">';
		        	}
    		$out.= '</div>';
    	$out.= '</div>';
    $out.= '</div>';
$out.= '<div class="inline-block floatleft valignmiddle maxwidth750 marginbottomonly refid refidpadding">';
	$out .= '<span class="ref">'.$object->ref.'</span>';
$out .= '</div>';
    
    $out .= '</div>';

    print $out;
}