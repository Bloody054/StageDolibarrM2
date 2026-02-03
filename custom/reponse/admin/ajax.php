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
 *  \file       htdocs/reponse/index.php
 *  \ingroup    reponse
 *  \brief      Page to show product set
 */

$res=@include("../../main.inc.php");                   // For root directory
if (! $res) $res=@include("../../../main.inc.php");    // For "custom" directory


// Libraries
require_once DOL_DOCUMENT_ROOT . "/core/lib/admin.lib.php";
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php';
require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';

dol_include_once("/reponse/class/reponse.class.php");


// Translations
$langs->load("errors");
$langs->load("admin");
$langs->load("other");

$langs->load("reponse@reponse");

// Access control
if (! $user->admin) {
	accessforbidden();
}

$ended = false;
$message = null;
$error = false;

$donep = GETPOST('donep', 'int');

$limit = 1;

// Insert current propals
$sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."reponse WHERE 1 ORDER BY rowid";
$result = $db->query($sql);
$total = $db->num_rows($result);


$sql = "SELECT rowid as id, fk_questionnaire FROM ".MAIN_DB_PREFIX."reponse WHERE 1 ORDER BY rowid";
$sql.= $db->plimit($limit, $donep);
$result = $db->query($sql);

if ($result) {
    $num = $db->num_rows($result);
    $i = 0;

    while ($i < $num) {
        $obj = $db->fetch_object($result);

        $fk_questionnaire = $obj->fk_questionnaire;
        $fk_reponse = $obj->id;

        $fvalues = array();
        $values = array();

        $sql = "SELECT sd.*";
        $sql.= " FROM ".MAIN_DB_PREFIX."questionnaireval as sd";
        $sql.= " WHERE sd.fk_reponse = ".$fk_reponse;

        $resql = $db->query($sql);
        if ($resql) {
            if ($db->num_rows($resql) > 0) {
                $values = $db->fetch_array($resql);
            }
        }

        $sql = "SELECT sd.*";
        $sql.= " FROM ".MAIN_DB_PREFIX."questionnairefval as sd";
        $sql.= " WHERE sd.fk_reponse = ".$fk_reponse;

        $resql = $db->query($sql);
        if ($resql) {
            if ($db->num_rows($resql) > 0) {
                $fvalues = $db->fetch_array($resql);
            }
        }

        $now = dol_now();

        $sql = "INSERT INTO ".MAIN_DB_PREFIX."questionnairefval".$fk_questionnaire." (";
        $sql.= " fk_reponse";
        $sql.= " , tms";
        $sql.= ") VALUES (";
        $sql.= " ".$fk_reponse;
        $sql.= ", '".$db->idate($now)."'";
        $sql.= ")";

        $db->query($sql);

        $sql = "INSERT INTO ".MAIN_DB_PREFIX."questionnaireval".$fk_questionnaire." (";
        $sql.= " fk_reponse";
        $sql.= " , tms";
        $sql.= ") VALUES (";
        $sql.= " ".$fk_reponse;
        $sql.= ", '".$db->idate($now)."'";
        $sql.= ")";

        $db->query($sql);

        $questionnaire = new Questionnaire($db);
        $questionnaire->fetch($fk_questionnaire);

        $lines = $questionnaire->lines;

        if (count($lines)) {
            foreach ($lines as $line) {
                $code = $line->code;
                
                $value = isset($values[$code]) ? $values[$code] : null;
                $fvalue = isset($fvalues[$code]) ? $fvalues[$code] : null;

                
                $sql = "UPDATE ".MAIN_DB_PREFIX."questionnairefval".$fk_questionnaire." SET ";
                $sql.= " ".$line->code." = '".$db->escape($fvalue)."'";
                $sql.= " , tms = '" . $db->idate(dol_now()) . "'";
                $sql.= " WHERE fk_reponse = ".$fk_reponse;

                $db->query($sql);

                if ($line->type == 'int') {
                    $type = "INT";
                } else if ($line->type == 'date' || $line->type == 'datetime') {
                    $type = "DATE";
                } else if ($line->type == 'list' || $line->type == 'radio' || $line->type == 'string' || $line->type == 'table' || $line->type=='map' || $line->type=='checkbox') {
                    $type = "VARCHAR";
                } else {
                    $type = 'VARCHAR'; // Default
                }

                if ($line->crypted) {
                    $type = 'VARCHAR'; // Default
                }

                $sql = "UPDATE ".MAIN_DB_PREFIX."questionnaireval".$fk_questionnaire." SET ";
                if ($type == 'INT') {
                    $sql.= " ".$code." = ".(empty($value) ? '0' : intval($value));
                } elseif ($type == 'DATE') {
                    $sql.= " ".$code." = ".(empty($value) ? 'null' : "'".$db->idate($value)."'");
                } else {
                    $sql.= " ".$code." = ".($value === '' ? 'null' : "'".$db->escape($value)."'");
                }
                $sql.= " , tms = '" . $db->idate(dol_now()) . "'";
                $sql.= " WHERE fk_reponse = ".$fk_reponse;

                $db->query($sql);
            }
        }

        $i++;
    }

    $donep += $num;
}


if ($donep == $total) {
    // Done
    $ended = true;
    $message = $langs->trans('UpgradedDone');
} else {
    $message = $langs->trans('RecordsUpgraded', $donep, $total);
}

$data = new stdClass();
$data->error = $error;
$data->ended = $ended;

$data->donep = $donep;

$data->message = $message;

echo json_encode($data);

$db->close();
