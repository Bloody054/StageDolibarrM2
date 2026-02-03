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
 * \file       htdocs/dolitour/admin/about.php
 * \ingroup    dolitour
 * \brief      Page to show module info
 */

$res=@include("../../main.inc.php");                   // For root directory
if (! $res) $res=@include("../../../main.inc.php");    // For "custom" directory


// Libraries
require_once DOL_DOCUMENT_ROOT . "/core/lib/admin.lib.php";
require_once DOL_DOCUMENT_ROOT . '/core/lib/functions2.lib.php';
dol_include_once("/dolitour/lib/dolitour.lib.php"); 

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */

// Translations
$langs->load("dolitour@dolitour");
$langs->load("errors");
$langs->load("admin");
$langs->load("other");

// Access control
if (! $user->admin) {
	accessforbidden();
}

// Historique des commits
$versions = array(
    array('version' => 'Dev',   'date' => '29/01/2026', 'updates' => 'List_Logs DATE AND TRI'),
    array('version' => 'Dev',   'date' => '28/01/2026', 'updates' => 'Logs + Bouton réinitialisation + Modifications Logs par utilisateurs BDD'),
    array('version' => 'Dev',   'date' => '26/01/2026', 'updates' => 'Modifications Image + Text + Reset button'),
    array('version' => 'Dev',   'date' => '23/01/2026', 'updates' => 'List AND Search done'),
    array('version' => 'Dev',   'date' => '22/01/2026', 'updates' => 'HTML new file, Drag and Drop, Progress and cross, Font settings, Colors'),
    array('version' => 'Dev',   'date' => '20/01/2026', 'updates' => 'Card.php Left and Right, Trad Onboard'),
    array('version' => 'Dev',   'date' => '19/01/2026', 'updates' => 'Image Ecran, Html + Switch URL/Context, Clone'),
    array('version' => 'Dev',   'date' => '16/01/2026', 'updates' => 'Initial Saves (01, 02, 03)'),
    array('version' => '1.0.0', 'date' => '28/07/2025', 'updates' => 'First commit / Initial Release'),
);

/*
 * View
 */

$form = new Form($db);

llxHeader('', $langs->trans('About'));

// Subheader
$linkback = '<a href="' . DOL_URL_ROOT . '/admin/modules.php">'. $langs->trans("BackToModuleList") . '</a>';
print load_fiche_titre($langs->trans('About'), $linkback);

// Configuration header
$head = dolitour_prepare_admin_head(); 
dol_fiche_head(
	$head,
	'about',
	$langs->trans("ModuleDoliTourName"),
	0,
	'dolitour@dolitour'
);

// --- DESIGN PERSONNALISÉ ---

// 1. Bannière de titre (Style moderne)
print '<div style="background-color:#fff; border-left: 5px solid #005f87; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.12); margin-bottom: 20px;">';
print '<div style="font-size: 1.6em; font-weight: bold; color: #333;">DoliTour</div>';
print '<div style="color: #666; font-size: 1.1em; margin-top: 5px;">Module de visites guidées et d\'onboarding pour Dolibarr</div>';
print '</div>';

print '<div class="fichecenter">';
print '<div class="underbanner clearboth"></div>';

// --- COLONNE GAUCHE : Description ---
print '<div class="fichehalfleft">';
    print '<table class="border" width="100%">';
    print '<tr class="liste_titre"><td>'.$langs->trans("Description").'</td></tr>';
    print '<tr class="oddeven"><td style="padding: 15px; line-height: 1.6em; vertical-align:top;">';
    
    print "<p><strong>DoliTour</strong> permet de créer des guides interactifs étape par étape directement sur l'interface de Dolibarr.</p>";
    print "<ul>";
    print "<li>Créez des scénarios d'accueil pour les nouveaux utilisateurs.</li>";
    print "<li>Expliquez les nouvelles fonctionnalités après une mise à jour.</li>";
    print "<li>Guidez vos employés dans les procédures complexes.</li>";
    print "</ul>";
    
    print '</td></tr>';
    print '</table>';
print '</div>';

// --- COLONNE DROITE : Infos Techniques & Auteur ---
print '<div class="fichehalfright">';
    print '<table class="border" width="100%">';
    print '<tr class="liste_titre"><td colspan="2">'.$langs->trans("Informations").'</td></tr>';
    
    // Logo
    $logo_path = dol_buildpath('/dolitour/img/logo.png', 1);
    print '<tr class="oddeven"><td colspan="2" align="center" style="padding: 20px;">';
    print '<img src="'.$logo_path.'" style="max-width:150px; height:auto;" onerror="this.style.display=\'none\'">'; 
    print '</td></tr>';

    print '<tr class="oddeven"><td width="50%">'.$langs->trans("Version").'</td><td align="right"><span class="badge">Dev</span></td></tr>';
    print '<tr class="oddeven"><td>'.$langs->trans("Développé par").'</td><td align="right"><strong>Théo Massompierre</strong><br><span class="opacitymedium">Bloody054</span></td></tr>';
    print '<tr class="oddeven"><td>'.$langs->trans("Licence").'</td><td align="right">GPL v3+</td></tr>';
    print '</table>';
print '</div>';

print '</div>';
print '<div class="clearboth"></div>';
print '<br>';

// --- CHANGELOG ---

print load_fiche_titre($langs->trans("ChangeLog"), '', 'title_generic');

print '<div class="div-table-responsive-no-min">';
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td width="100">'.$langs->trans("Version").'</td>';
print '<td width="120">'.$langs->trans("Date").'</td>';
print '<td>'.$langs->trans("Détails des modifications").'</td>';
print "</tr>\n";

foreach ($versions as $version)
{
	print '<tr class="oddeven">';
	print '<td><span class="badge badge-status4">'.$version['version'].'</span></td>';
	print '<td>'.$version['date'].'</td>';
	print '<td>'.$version['updates'].'</td>';
	print '</tr>';
}
print '</table>';
print '</div>';

// Page end
dol_fiche_end();
llxFooter();
$db->close();