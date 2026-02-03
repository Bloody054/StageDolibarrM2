<?php
/* Copyright (C) 2004-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2017 Mikael Carlavan <contact@mika-carl.fr>
 * Copyright (C) 2022 Julien Marchand <julien.marchand@iouston.com>
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

dol_include_once("/reponse/lib/reponse.lib.php");

// Translations
$langs->load("reponse@reponse");

// Translations
$langs->load("errors");
$langs->load("admin");
$langs->load("other");

// Access control
if (! $user->admin) {
	accessforbidden();
}

$versions = array(
    array('version' => '2.0.0', 'date' => '12/09/2024', 'updates' => $langs->trans('Reponse200Version')),
    array('version' => '1.0.0', 'date' => '21/03/2024', 'updates' => $langs->trans('ReponseFirstVersion')),
);

/*
 * View
 */

$form = new Form($db);

llxHeader('', $langs->trans('ReponseAbout'));

// Subheader
$linkback = '<a href="' . DOL_URL_ROOT . '/admin/modules.php">'. $langs->trans("BackToModuleList") . '</a>';
print load_fiche_titre($langs->trans('ReponseAbout'), $linkback);

// Configuration header
$head = reponse_prepare_admin_head();
dol_fiche_head(
	$head,
	'about',
	$langs->trans("ModuleReponseName"),
	0,
	'reponse@reponse'
);

// About page goes here
echo $langs->trans("ReponseAboutPage");

echo '<br>';

$url = 'http://www.iouston.com/systeme-gestion-entreprise-dolibarr/modules-dolibarr/reponse';

print '<h2>'.$langs->trans("About").'</h2>';
print $langs->trans("ReponseAboutDescLong", $url, $url);

print '<h2>'.$langs->trans("MaintenanceAndSupportTitle").'</h2>';
print $langs->trans("MaintenanceAndSupportDescLong");

print '<h2>'.$langs->trans("UpdateTitle").'</h2>';
print $langs->trans("UpdateDescLong");

print '<h2>'.$langs->trans("ModulesTitle").'</h2>';
print $langs->trans("ModulesDescLong");

echo '<br>';

print '<a href="http://www.dolistore.com">'.img_picto('dolistore', dol_buildpath('/reponse/img/dolistore.png', 1), '', 1).'</a>';

print '<hr />';

print '<a href="http://www.iouston.com">'.img_picto('iouston', dol_buildpath('/reponse/img/iouston.png', 1), '', 1).'</a>';

echo '<br>';

print $langs->trans("IoustonDesc");

print '<hr />';
print '<h2>'.$langs->trans("ChangeLog").'</h2>';


print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("ChnageLogVersion").'</td>';
print '<td>'.$langs->trans("ChnageLogDate").'</td>';
print '<td>'.$langs->trans("ChnageLogUpdates").'</td>';
print "</tr>\n";

foreach ($versions as $version)
{
	print '<tr class="oddeven">';
	print '<td>';
	print $version['version'];
	print '</td>';
	print '<td>';
	print $version['date'];
	print '</td>';
	print '<td>';
	print $version['updates'];
	print '</td>';
	print '</tr>';
}


print '</table>';

// Page end
dol_fiche_end();
llxFooter();
$db->close();