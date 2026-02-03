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
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/link.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/security.lib.php';

/**
 *	\file       htdocs/questionnaire/lib/questionnaire.lib.php
 *	\brief      Ensemble de fonctions de base pour le module questionnaire
 * 	\ingroup	questionnaire
 */

/**
 * Prepare array with list of tabs
 *
 * @return  array				Array of tabs to show
 */
function questionnaire_prepare_admin_head()
{
	global $db, $langs, $conf, $user;
	$langs->load("questionnaire@questionnaire");

	$h = 0;
	$head = array();

	$head[$h][0] = dol_buildpath("/questionnaire/admin/setup.php", 1);
	$head[$h][1] = $langs->trans("Settings");
	$head[$h][2] = 'settings';
	$h++;

    $head[$h][0] = dol_buildpath("/questionnaire/admin/upgrade.php", 1);
    $head[$h][1] = $langs->trans("Upgrade");
    $head[$h][2] = 'upgrade';
    $h++;

	$head[$h][0] = dol_buildpath("/questionnaire/admin/extrafields.php", 1);
	$head[$h][1] = $langs->trans("Extrafields");
	$head[$h][2] = 'attributes';
	$h++;

	$head[$h][0] = dol_buildpath("/questionnaire/admin/about.php", 1);
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
function questionnaire_prepare_head($object)
{
	global $db, $langs, $conf, $user;
	$langs->load("questionnaire@questionnaire");

	$h = 0;
	$head = array();

	if ($user->rights->questionnaire->lire)
	{
		$head[$h][0] = dol_buildpath("/questionnaire/card.php?id=".$object->id, 1);
		$head[$h][1] = $langs->trans("QuestionnaireCard");
		$head[$h][2] = 'questionnaire';
		$h++;


        $head[$h][0] = dol_buildpath("/questionnaire/action.php?id=".$object->id, 1);
        $head[$h][1] = $langs->trans("QuestionnaireAction");
        $head[$h][2] = 'action';
        $h++;

        $head[$h][0] = dol_buildpath("/questionnaire/display.php?id=".$object->id, 1);
        $head[$h][1] = $langs->trans("QuestionnaireDisplay");
        $head[$h][2] = 'display';
        $h++;
	}

    if ($user->rights->questionnaire->exporter)
    {
        $head[$h][0] = dol_buildpath("/questionnaire/export.php?id=".$object->id, 1);
        $head[$h][1] = $langs->trans("QuestionnaireExport");
        $head[$h][2] = 'exporter';
        $h++;
    }

	$head[$h][0] = dol_buildpath("/questionnaire/info.php?id=".$object->id, 1);
	$head[$h][1] = $langs->trans("Info");
	$head[$h][2] = 'info';
	$h++;

	return $head;
}

/**
 *	Encode a string with base 64 algorithm + specific delta change.
 *
 *	@param   string		$chain		string to encode
 *	@param   string		$key		rule to use for delta ('0', '1' or 'myownkey')
 *	@return  string					encoded string
 *  @see mb_dol_encode()
 */
function mb_dol_encode($chain, $key = '1')
{
    // Remove accents
    $unwanted_array = array('Š'=>'S', 'š'=>'s', 'Ž'=>'Z', 'ž'=>'z', 'À'=>'A', 'Á'=>'A', 'Â'=>'A', 'Ã'=>'A', 'Ä'=>'A', 'Å'=>'A', 'Æ'=>'A', 'Ç'=>'C', 'È'=>'E', 'É'=>'E',
        'Ê'=>'E', 'Ë'=>'E', 'Ì'=>'I', 'Í'=>'I', 'Î'=>'I', 'Ï'=>'I', 'Ñ'=>'N', 'Ò'=>'O', 'Ó'=>'O', 'Ô'=>'O', 'Õ'=>'O', 'Ö'=>'O', 'Ø'=>'O', 'Ù'=>'U',
        'Ú'=>'U', 'Û'=>'U', 'Ü'=>'U', 'Ý'=>'Y', 'Þ'=>'B', 'ß'=>'Ss', 'à'=>'a', 'á'=>'a', 'â'=>'a', 'ã'=>'a', 'ä'=>'a', 'å'=>'a', 'æ'=>'a', 'ç'=>'c',
        'è'=>'e', 'é'=>'e', 'ê'=>'e', 'ë'=>'e', 'ì'=>'i', 'í'=>'i', 'î'=>'i', 'ï'=>'i', 'ð'=>'o', 'ñ'=>'n', 'ò'=>'o', 'ó'=>'o', 'ô'=>'o', 'õ'=>'o',
        'ö'=>'o', 'ø'=>'o', 'ù'=>'u', 'ú'=>'u', 'û'=>'u', 'ý'=>'y', 'þ'=>'b', 'ÿ'=>'y' );
    $chain = strtr($chain, $unwanted_array);

    return dol_encode($chain, $key);
    /*
    if (is_numeric($key) && $key == '1')	// rule 1 is offset of 17 for char
    {
        $output_tab=array();
        $i = 0;
        while (substr($chain, $i, 1) !== false) {
            $output_tab[$i] = chr(ord(substr($chain, $i, 1))+17);
            $i++;
        }
        $chain = implode("", $output_tab);
    }
    elseif ($key)
    {
        $result='';
        $i = 0;
        while (substr($chain, $i, 1) !== false) {
            $keychar = substr($key, ($i % strlen($key))-1, 1);
            $result.= chr(ord(substr($chain, $i, 1))+(ord($keychar)-65));
            $i++;
        }
        $chain=$result;
    }

    return base64_encode($chain);
    */
}

/**
 *	Decode a base 64 encoded + specific delta change.
 *  This function is called by filefunc.inc.php at each page call.
 *
 *	@param   string		$chain		string to decode
 *	@param   string		$key		rule to use for delta ('0', '1' or 'myownkey')
 *	@return  string					decoded string
 *  @see mb_dol_encode()
 */
function mb_dol_decode($chain, $key = '1')
{
    return dol_decode($chain, $key);

    /*
    $chain = base64_decode($chain);

    if (is_numeric($key) && $key == '1')	// rule 1 is offset of 17 for char
    {
        $output_tab=array();
        $i = 0;
        while (substr($chain, $i, 1) !== false) {
            $output_tab[$i] = chr(ord(substr($chain, $i, 1))-17);
            $i++;
        }

        $chain = implode("", $output_tab);
    }
    elseif ($key)
    {
        $result='';
        $i = 0;
        while (substr($chain, $i, 1) !== false) {
            $keychar = substr($key, ($i % strlen($key))-1, 1);
            $result.= chr(ord(substr($chain, $i, 1))-(ord($keychar)-65));
            $i++;
        }
        $chain=$result;
    }

    return $chain;
    */
}
