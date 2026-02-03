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
 * \file       htdocs/dolitour/admin/setup.php
 * \ingroup    dolitour
 * \brief      Admin page
 */


$res=@include("../../main.inc.php");                   // For root directory
if (! $res) $res=@include("../../../main.inc.php");    // For "custom" directory

// Libraries
require_once DOL_DOCUMENT_ROOT . "/core/lib/admin.lib.php";
require_once DOL_DOCUMENT_ROOT . "/core/class/html.formother.class.php";
dol_include_once("/dolitour/lib/dolitour.lib.php");
dol_include_once("/dolitour/class/dolitour.class.php");
dol_include_once("/dolitour/class/html_dolitour.class.php");

// Translations
$langs->load("dolitour@dolitour");
$langs->load("admin");
$langs->load("other");

// Access control
if (! $user->admin) accessforbidden();

// Parameters
$action = GETPOST('action', 'alpha');
$value = GETPOST('value', 'alpha');

/*
 * Actions
 */

// 1. Sauvegarde des options par défaut (Formulaire du bas)
if ($action == 'update_options')
{
    $db->begin();
    $res = 0;
    // Liste des constantes à sauvegarder
    $constants = array(
        'DOLITOUR_DEFAULT_FONT_SIZE',
        'DOLITOUR_DEFAULT_FONT_FAMILY',
        'DOLITOUR_DEFAULT_FONT_COLOR',
        'DOLITOUR_DEFAULT_BACKGROUND_COLOR',
        'DOLITOUR_DEFAULT_COLOR',
        'DOLITOUR_DEFAULT_SIDE',
        'DOLITOUR_DEFAULT_ALIGN',
        'DOLITOUR_DEFAULT_SHOW_PROGRESS', 
        'DOLITOUR_DEFAULT_SHOW_CROSS',
		'DOLITOUR_SHOW_ME_THE_CONTEXT',
        'DOLITOUR_DEBUG_FORCE_SHOW',      
        'DOLITOUR_DEBUG_HIGHLIGHT_TARGET', 
        'DOLITOUR_DEBUG_CONSOLE'     
    );

    foreach ($constants as $constname) {
        $constvalue = GETPOST($constname, 'alpha');
        $res += dolibarr_set_const($db, $constname, $constvalue, 'chaine', 0, '', $conf->entity);
    }
    
    if ($res >= 0) {
        $db->commit();
        setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
    } else {
        $db->rollback();
        setEventMessages($langs->trans("Error"), null, 'errors');
    }
}

// 2. Sauvegarde du masque de numérotation (Spécifique module Cookie)
// On reprend ici la logique de votre ancien fichier qui utilisait 'maskconstdolitour'
if ($action == 'updateMask')
{
    $maskconstdolitour = GETPOST('maskconstdolitour','alpha');
    $maskdolitour = GETPOST('maskdolitour','restricthtml'); // restricthtml pour autoriser les { }

    if ($maskconstdolitour) {
        $res = dolibarr_set_const($db,$maskconstdolitour,$maskdolitour,'chaine',0,'',$conf->entity);
        if ($res >= 0) {
            setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
        } else {
            setEventMessages($langs->trans("Error"), null, 'errors');
        }
    }
}

// 3. Activation module numérotation (Switch ON/OFF dans la liste)
if ($action == 'setmod')
{
	dolibarr_set_const($db, "DOLITOUR_ADDON",$value,'chaine',0,'',$conf->entity);
}


/*
 * View
 */

llxHeader('', $langs->trans('DoliTourSetup'));

// Subheader
$linkback = '<a href="' . DOL_URL_ROOT . '/admin/modules.php">' . $langs->trans("BackToModuleList") . '</a>';

print load_fiche_titre($langs->trans('DoliTourSetup'), $linkback);

// Configuration header
$head = dolitour_prepare_admin_head();
dol_fiche_head(
	$head,
	'settings',
	$langs->trans("ModuleDoliTourName"),
	0,
	"dolitour@dolitour"
);

$form = new Form($db);
$formother = new FormOther($db); 
$formdolitour = new FormDoliTour($db);

/*
 * Module numerotation
 */
print load_fiche_titre($langs->trans("DoliToursNumberingModules"),'','');

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Name")."</td>\n";
print '<td>'.$langs->trans("Description")."</td>\n";
print '<td class="nowrap">'.$langs->trans("Example")."</td>\n";
print '<td align="center" width="60">'.$langs->trans("Status").'</td>';
print '<td align="center" width="16">'.$langs->trans("ShortInfo").'</td>';
print '</tr>'."\n";

clearstatcache();

// Chemin relatif robuste
$dir = dirname(__FILE__).'/../core/modules/dolitour/';

if (is_dir($dir))
{
	$handle = opendir($dir);
	if (is_resource($handle))
	{
		while (($file = readdir($handle))!==false)
		{
            // Détection des fichiers mod_dolitour_*.php
			if (substr($file, 0, 13) == 'mod_dolitour_' && substr($file, dol_strlen($file)-3, 3) == 'php')
			{
				$file = substr($file, 0, dol_strlen($file)-4);

				try {
                    include_once $dir.$file.'.php';
                } catch(Exception $e) {
                    continue;
                }

				$module = new $file;

				if ($module->isEnabled())
				{
					print '<tr class="oddeven"><td>'.$module->nom."</td><td>\n";
					print $module->info();
					print '</td>';

					// Show example
					print '<td class="nowrap">';
					$tmp=$module->getExample();
					if (preg_match('/^Error/',$tmp)) print '<div class="error">'.$langs->trans($tmp).'</div>';
					elseif ($tmp=='NotConfigured') print $langs->trans($tmp);
					else print $tmp;
					print '</td>'."\n";

					print '<td align="center">';
					if ($conf->global->DOLITOUR_ADDON == "$file")
					{
						print img_picto($langs->trans("Activated"),'switch_on');
					}
					else
					{
						print '<a href="'.$_SERVER["PHP_SELF"].'?token='.newtoken().'&action=setmod&amp;value='.$file.'">';
						print img_picto($langs->trans("Disabled"),'switch_off');
						print '</a>';
					}
					print '</td>';

					$dolitour = new DoliTour($db);

					// Info tooltip
					$htmltooltip=$langs->trans("Version").': <b>'.$module->getVersion().'</b><br>';
                    $nextval = $module->getNextValue($mysoc, $dolitour);
                    
					print '<td align="center">';
					print $form->textwithpicto('',$htmltooltip,1,0);
					print '</td>';

					print "</tr>\n";
				}
			}
		}
		closedir($handle);
	}
}

print "</table><br>\n";

/*
 * Options par défaut et context 
 */

// Context (debug)
print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="update_options">';

print load_fiche_titre("Configuration Générale", '', '');

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>Paramètre</td>';
print '<td colspan="2">Valeur</td>';
print '</tr>'."\n";

// Option : Afficher le contexte
print '<tr class="oddeven">';
print '<td>Afficher le contexte sur chaque page (Mode Debug)</td>';
print '<td colspan="2">';
print $form->selectyesno('DOLITOUR_SHOW_ME_THE_CONTEXT', (isset($conf->global->DOLITOUR_SHOW_ME_THE_CONTEXT) ? $conf->global->DOLITOUR_SHOW_ME_THE_CONTEXT : 0), 1);
print '</td>';
print '</tr>';

// Option : Forcer l'affichage (Ignorer l'historique)
print '<tr class="oddeven">';
print '<td>Forcer l\'affichage des tours (Ignorer l\'historique)</td>';
print '<td colspan="2">';
print $form->selectyesno('DOLITOUR_DEBUG_FORCE_SHOW', (isset($conf->global->DOLITOUR_DEBUG_FORCE_SHOW) ? $conf->global->DOLITOUR_DEBUG_FORCE_SHOW : 0), 1);
print ' <span class="opacitymedium">(Utile pour tester un tour en boucle sans vider les logs)</span>';
print '</td>';
print '</tr>';

// Option : Surligner la cible
print '<tr class="oddeven">';
print '<td>Surligner l\'élément cible (Bordure rouge)</td>';
print '<td colspan="2">';
print $form->selectyesno('DOLITOUR_DEBUG_HIGHLIGHT_TARGET', (isset($conf->global->DOLITOUR_DEBUG_HIGHLIGHT_TARGET) ? $conf->global->DOLITOUR_DEBUG_HIGHLIGHT_TARGET : 0), 1);
print ' <span class="opacitymedium">(Pour vérifier si votre sélecteur CSS est correct)</span>';
print '</td>';
print '</tr>';

// Option : Logs Console
print '<tr class="oddeven">';
print '<td>Activer les logs détaillés (Console F12)</td>';
print '<td colspan="2">';
print $form->selectyesno('DOLITOUR_DEBUG_CONSOLE', (isset($conf->global->DOLITOUR_DEBUG_CONSOLE) ? $conf->global->DOLITOUR_DEBUG_CONSOLE : 0), 1);
print '</td>';
print '</tr>';

print '</table><br>';

// Option par défaut 
print load_fiche_titre("Options par défaut des Tours", '', '');
print '<span class="opacitymedium">Ces options définissent le style appliqué lors de la création d\'un nouveau tour ou lors d\'une réinitialisation.</span><br><br>';

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>Paramètre</td>';
print '<td colspan="2">Valeur par défaut</td>';
print '</tr>'."\n";

// Show Progress (Oui/Non)
print '<tr class="oddeven">';
print '<td>Afficher la progression</td>';
print '<td colspan="2">';
print $form->selectyesno('DOLITOUR_DEFAULT_SHOW_PROGRESS', (isset($conf->global->DOLITOUR_DEFAULT_SHOW_PROGRESS) ? $conf->global->DOLITOUR_DEFAULT_SHOW_PROGRESS : 1), 1);
print '</td>';
print '</tr>';

// Show Cross / Allow Close (Oui/Non)
print '<tr class="oddeven">';
print '<td>Autoriser la fermeture (Croix)</td>';
print '<td colspan="2">';
print $form->selectyesno('DOLITOUR_DEFAULT_SHOW_CROSS', (isset($conf->global->DOLITOUR_DEFAULT_SHOW_CROSS) ? $conf->global->DOLITOUR_DEFAULT_SHOW_CROSS : 1), 1);
print '</td>';
print '</tr>';

// Side (Côté)
print '<tr class="oddeven">';
print '<td>Position de la bulle</td>';
print '<td>';
print $formdolitour->selectSide($conf->global->DOLITOUR_DEFAULT_SIDE, 'DOLITOUR_DEFAULT_SIDE', 1);
print '</td>';
print '<td></td>';
print '</tr>';

// Align (Alignement)
print '<tr class="oddeven">';
print '<td>Alignement du texte</td>';
print '<td>';
print $formdolitour->selectAlign($conf->global->DOLITOUR_DEFAULT_ALIGN, 'DOLITOUR_DEFAULT_ALIGN', 1);
print '</td>';
print '<td></td>';
print '</tr>';

// Font Family
print '<tr class="oddeven">';
print '<td>Police d\'écriture</td>';
print '<td>';
print $formdolitour->selectFontFamily($conf->global->DOLITOUR_DEFAULT_FONT_FAMILY, 'DOLITOUR_DEFAULT_FONT_FAMILY', 1);
print '</td>';
print '<td></td>';
print '</tr>';

// Font Size
print '<tr class="oddeven">';
print '<td>Taille de police</td>';
print '<td>';
print '<input type="text" class="flat" size="5" name="DOLITOUR_DEFAULT_FONT_SIZE" value="'.$conf->global->DOLITOUR_DEFAULT_FONT_SIZE.'"> px';
print '</td>';
print '<td></td>';
print '</tr>';

// Font Color
print '<tr class="oddeven">';
print '<td>Couleur du texte</td>';
print '<td>';
print $formother->selectColor($conf->global->DOLITOUR_DEFAULT_FONT_COLOR, 'DOLITOUR_DEFAULT_FONT_COLOR', 'dolitour_form', 1);
print '</td>';
print '<td></td>';
print '</tr>';

// Background Color (Couleur du tour)
print '<tr class="oddeven">';
print '<td>Couleur de fond de la bulle</td>';
print '<td>';
print $formother->selectColor($conf->global->DOLITOUR_DEFAULT_BACKGROUND_COLOR, 'DOLITOUR_DEFAULT_BACKGROUND_COLOR', 'dolitour_form', 1);
print '</td>';
print '<td></td>';
print '</tr>';

// Overlay Color (Couleur du calque)
print '<tr class="oddeven">';
print '<td>Couleur du calque (Assombrissement)</td>';
print '<td>';
print $formother->selectColor($conf->global->DOLITOUR_DEFAULT_COLOR, 'DOLITOUR_DEFAULT_COLOR', 'dolitour_form', 1);
print '</td>';
print '<td></td>';
print '</tr>';

print '</table>';

print '<div class="center"><br><input type="submit" class="button" value="'.$langs->trans("Save").'"></div>';
print '</form>';

dol_fiche_end();
llxFooter();