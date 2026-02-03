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
 *  \file       htdocs/questionnaire/index.php
 *  \ingroup    questionnaire
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

dol_include_once("/questionnaire/class/questionnaire.class.php");


// Translations
$langs->load("errors");
$langs->load("admin");
$langs->load("other");

$langs->load("questionnaire@questionnaire");

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
$sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."questionnaire WHERE 1 ORDER BY rowid";
$result = $db->query($sql);
$total = $db->num_rows($result);


$sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."questionnaire WHERE 1 ORDER BY rowid";
$sql.= $db->plimit($limit, $donep);
$result = $db->query($sql);

if ($result) {
    $num = $db->num_rows($result);
    $i = 0;

    while ($i < $num) {
        $obj = $db->fetch_object($result);

        $object = new Questionnaire($db);
        $object->fetch($obj->rowid);


        $sql = "CREATE TABLE IF NOT EXISTS " . MAIN_DB_PREFIX . "questionnaireval".$object->id." (`rowid` int(11)  AUTO_INCREMENT, `fk_reponse` int(11) DEFAULT 0,`tms` timestamp NOT NULL, PRIMARY KEY (`rowid`))ENGINE=innodb DEFAULT CHARSET=utf8;";
        $resql = $db->query($sql);

        $sql = "CREATE TABLE IF NOT EXISTS " . MAIN_DB_PREFIX . "questionnairefval".$object->id." (`rowid` int(11)  AUTO_INCREMENT, `fk_reponse` int(11) DEFAULT 0,`tms` timestamp NOT NULL, PRIMARY KEY (`rowid`))ENGINE=innodb DEFAULT CHARSET=utf8;";
        $resql = $db->query($sql);

        $lines = $object->lines;

        if (count($lines)) {
            foreach ($lines as $line) {

                $type2 = "TEXT";

                if ($line->type == 'int') {
                    $type = "INT(11)";
                } else if ($line->type == 'numeric') {
                    $type = "DOUBLE";
                } else if ($line->type == 'date') {
                    $type = "DATE";
                } else if ($line->type == 'datetime') {
                    $type = "DATETIME";
                } else if ($line->type == 'radio' || $line->type == 'list' || $line->type == 'string' || $line->type == 'table' || $line->type == 'map' || $line->type == 'checkbox') {
                    $type = "VARCHAR(255)";
                } else {
                    $type = "TEXT"; // Default
                }

                if ($line->crypted) {
                    $type = 'TEXT'; // Default
                }

                $sql = "ALTER TABLE " . MAIN_DB_PREFIX . "questionnaireval".$object->id." ADD COLUMN `" . $line->code . "` $type NULL";
                $resql = $db->query($sql);

                $sql = "ALTER TABLE " . MAIN_DB_PREFIX . "questionnairefval".$object->id." ADD COLUMN `" . $line->code . "` $type2 NULL";
                $resql = $db->query($sql);
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
