<?php
/* Copyright (C) 2010     Regis Houssin       <regis.houssin@inodbox.com>
 * Copyright (C) 2011-204 Laurent Destailleur <eldy@users.sourceforge.net>
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
 * \file 	htdocs/reponse/card.php
 * \ingroup reponse
 * \brief 	Page to show customer order
 */

if (! defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL',1); // Disables token renewal
if (! defined('NOREQUIREMENU'))  define('NOREQUIREMENU','1');
if (! defined('NOREQUIREHTML'))  define('NOREQUIREHTML','1');
if (! defined('NOREQUIREAJAX'))  define('NOREQUIREAJAX','1');
if (! defined('NOREQUIRESOC'))   define('NOREQUIRESOC','1');
if (! defined('NOCSRFCHECK'))    define('NOCSRFCHECK','1');

$res=@include("../main.inc.php");                   // For root directory
if (! $res) $res=@include("../../main.inc.php");    // For "custom" directory

/*
 * View
 */

top_httphead();

$search_reponse = GETPOST('search_reponse', 'alpha');

$data = array();

if (! empty($search_reponse))
{
    // Get emails fields
    $fieldstosearchall = array();
    $fieldstosearchall['s.ref'] = 'Ref';
    $fields = array();
    $crypted = array();

    $sql = "SELECT DISTINCT(fd.code) as email, fd.crypted";
    $sql.= " FROM ".MAIN_DB_PREFIX."questionnaire as f";
    $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."questionnairedet as fd ON fd.rowid = f.fk_email";
    $sql.= " WHERE f.entity IN (".getEntity('reponse').")";

	$resql = $db->query($sql);
	if ($resql)
	{
		while ($row = $db->fetch_object($resql))
		{
            if (!empty($row->email)) {
                $fieldstosearchall['fv.'.$row->email] = 'Email';
                $fields[] = $row->email;
                $crypted[] = $row->crypted;
            }
		}
	}
    
    $sql = "SELECT s.rowid as id, s.datec, ".implode(',', array_keys($fieldstosearchall));
    $sql.= " FROM ".MAIN_DB_PREFIX."reponse as s";
    $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."questionnaireval as fv ON fv.fk_reponse = s.rowid";
    $sql.= " WHERE s.entity IN (".getEntity('reponse').")";

    $sql.= natural_search(array_keys($fieldstosearchall), $search_reponse);

    $sql.= $db->order('s.rowid','DESC');

    $sql.= $db->plimit(100); 
    
	$resql = $db->query($sql);
	if ($resql)
	{
		while ($obj = $db->fetch_object($resql))
		{
            $datec = $db->jdate($obj->datec);
            $email = '';
            if (count($fields)) {
                foreach ($fields as $i => $f) {
                    if (!empty($obj->$f)) {
                        $email = $obj->$f;
                        if ($crypted[$i]) {
                            if (strpos($email, ':') !== false && $user->rights->reponse->decrypter) {
                                list($key, $value) = explode(':', $email);
                                $email  = mb_dol_decode($value, $key);
                            }
                        }
                    }
                }
            }
            $ref = $obj->ref.' - '.dol_print_date($datec, 'day');
            $ref.= !empty($email) ? ' ('.$email.')' : '';

            $data[] = array(
                'label' => $ref,
                'value' => $obj->ref,
                'fk_reponse' => $obj->id
            );
		}
	}
}

echo json_encode($data);


$db->close();

