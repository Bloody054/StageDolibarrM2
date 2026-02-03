<?php
/* Copyright (C) 2019      Mikael Carlavan        <contact@mika-carl.fr>
 *                                                http://www.mikael-carlavan.fr
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
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

$res=@include("../main.inc.php");                   // For root directory
if (! $res) $res=@include("../../main.inc.php");


require_once(DOL_DOCUMENT_ROOT."/core/lib/functions2.lib.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/date.lib.php");
require_once(DOL_DOCUMENT_ROOT."/core/class/doleditor.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/files.lib.php");

dol_include_once("/questionnaire/class/questionnaire.class.php");

$langs->load('questionnaire@questionnaire');
$langs->load('main');

$lat = GETPOST('lat', 'alpha');
$lon = GETPOST('lon', 'alpha');

/*
 * View
 */
$questionnaire = new Questionnaire($db);

$data = new stdClass();

$error = false;
$message = null;

list($city, $region, $state, $country, $zip) = $questionnaire->reverse($lat, $lon);

$data->city = $city;
$data->region = $region;
$data->state = $state;
$data->country = $country;
$data->zip = $zip;

$data->error = $error;
$data->message = $message;

echo json_encode($data);

$db->close();

