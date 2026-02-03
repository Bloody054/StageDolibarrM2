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
 *  \file       htdocs/reponse/admin/setup.php
 *  \ingroup    reponse
 *  \brief      Admin page
 */


$res=@include("../../main.inc.php");                   // For root directory
if (! $res) $res=@include("../../../main.inc.php");    // For "custom" directory

// Libraries
require_once DOL_DOCUMENT_ROOT . "/core/lib/admin.lib.php";
require_once DOL_DOCUMENT_ROOT ."/core/class/html.formmail.class.php";

dol_include_once("/reponse/lib/reponse.lib.php");
dol_include_once("/reponse/class/reponse.class.php");

// Translations
$langs->load("reponse@reponse");
$langs->load("admin");

// Access control
if (! $user->admin) accessforbidden();

// Parameters
$action = GETPOST('action', 'alpha');
$value = GETPOST('value', 'alpha');

$reg = array();

/*
 * Actions
 */


// include DOL_DOCUMENT_ROOT.'/core/actions_setmoduleoptions.inc.php';

$error=0;

// Action mise a jour ou ajout d'une constante
if ($action == 'update')
{
	$constname=GETPOST('constname','alpha');
	$constvalue=(GETPOST('constvalue_'.$constname) ? GETPOST('constvalue_'.$constname) : GETPOST('constvalue'));


	$consttype=GETPOST('consttype','alpha');
	$constnote=GETPOST('constnote', 'alpha');
	$res = dolibarr_set_const($db,$constname,$constvalue,'chaine',0,$constnote,$conf->entity);

	if (! $res > 0) $error++;

	if (! $error)
	{
		setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
	}
	else
	{
		setEventMessages($langs->trans("Error"), null, 'errors');
	}
}

if ($action == 'updateMask')
{
	$maskconstreponse=GETPOST('maskconstreponse','alpha');
	$maskreponse=GETPOST('maskreponse','alpha');
	if ($maskconstreponse) $res = dolibarr_set_const($db,$maskconstreponse,$maskreponse,'chaine',0,'',$conf->entity);

	if (! $res > 0) $error++;

 	if (! $error)
	{
		setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
	}
	else
	{
		setEventMessages($langs->trans("Error"), null, 'errors');
	}
}

if ($action == 'setmod')
{
	// TODO Verifier si module numerotation choisi peut etre active
	// par appel methode canBeActivated

	dolibarr_set_const($db, "REPONSE_ADDON",$value,'chaine',0,'',$conf->entity);
}


if (preg_match('/set_(.*)/',$action,$reg))
{
    $code=$reg[1];
    $value=(GETPOST($code) ? GETPOST($code) : 1);
    if (dolibarr_set_const($db, $code, $value, 'chaine', 0, '', $conf->entity) > 0)
    {
        Header("Location: ".$_SERVER["PHP_SELF"]);
        exit;
    }
    else
    {
        dol_print_error($db);
    }
}

else if (preg_match('/del_(.*)/',$action,$reg))
{
    $code=$reg[1];
    if (dolibarr_del_const($db, $code, $conf->entity) > 0)
    {
        Header("Location: ".$_SERVER["PHP_SELF"]);
        exit;
    }
    else
    {
        dol_print_error($db);
    }
}

/*
 * View
 */

llxHeader('', $langs->trans('ReponseSetup'));

// Subheader
$linkback = '<a href="' . DOL_URL_ROOT . '/admin/modules.php">' . $langs->trans("BackToModuleList") . '</a>';

print load_fiche_titre($langs->trans('ReponseSetup'), $linkback);

// Configuration header
$head = reponse_prepare_admin_head();
dol_fiche_head(
	$head,
	'settings',
	$langs->trans("ModuleReponseName"),
	0,
	"reponse@reponse"
);

$form = new Form($db);

$reponse = new Reponse($db);
$themes  = $reponse->getThemes();

print load_fiche_titre($langs->trans("ReponseOptions"),'','');

print '<table class="noborder" width="100%">';
print '<tbody>';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Name").'</td>';
print '<td align="left">'.$langs->trans("Action").'</td>';
print "</tr>\n";

print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
print '<tr class="oddeven">';
print '<td>';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="update">';
print '<input type="hidden" name="constname" value="REPONSE_ROOT_URL">';
print '<input type="hidden" name="constnote" value="">';
print $langs->trans('DescREPONSE_ROOT_URL');
print '</td>';
print '<td>';
print '<input size="50" type="text" class="flat" name="constvalue" value="'.(isset($conf->global->REPONSE_ROOT_URL) ? $conf->global->REPONSE_ROOT_URL : '').'" />';
print '<input type="hidden" name="consttype" value="chaine">';
print '</td>';
print '<td align="center">';
print '<input type="submit" class="button" value="'.$langs->trans("Update").'" name="Button">';
print '</td>';
print '</tr>';
print '</form>';

print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
print '<tr class="oddeven">';
print '<td>';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="update">';
print '<input type="hidden" name="constname" value="REPONSE_THEME">';
print '<input type="hidden" name="constnote" value="">';
print $langs->trans('DescREPONSE_THEME');
print '</td>';
print '<td>';
print $form->selectarray('constvalue', $themes, (isset($conf->global->REPONSE_THEME) ? $conf->global->REPONSE_THEME : 'default'), 0, 0, 1);
print '<input type="hidden" name="consttype" value="chaine">';
print '</td>';
print '<td align="center">';
print '<input type="submit" class="button" value="'.$langs->trans("Update").'" name="Button">';
print '</td>';
print '</tr>';
print '</form>';

print '</tbody>';
print '</table>';

/*
 *  Module numerotation
 */
print load_fiche_titre($langs->trans("ReponsesNumberingModules"),'','');

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Name")."</td>\n";
print '<td>'.$langs->trans("Description")."</td>\n";
print '<td class="nowrap">'.$langs->trans("Example")."</td>\n";
print '<td align="center" width="60">'.$langs->trans("Status").'</td>';
print '<td align="center" width="16">'.$langs->trans("ShortInfo").'</td>';
print '</tr>'."\n";

clearstatcache();

$dir = './../core/modules/reponse/';
if (is_dir($dir))
{
	$handle = opendir($dir);
	if (is_resource($handle))
	{
		$var=true;

		while (($file = readdir($handle))!==false)
		{

			if (substr($file, 0, 12) == 'mod_reponse_' && substr($file, dol_strlen($file)-3, 3) == 'php')
			{
				$file = substr($file, 0, dol_strlen($file)-4);

				require_once $dir.$file.'.php';

				$module = new $file;

				// Show modules according to features level
				if ($module->version == 'development'  && $conf->global->MAIN_FEATURES_LEVEL < 2) continue;
				if ($module->version == 'experimental' && $conf->global->MAIN_FEATURES_LEVEL < 1) continue;

				if ($module->isEnabled())
				{

					print '<tr class="oddeven"><td>'.$module->nom."</td><td>\n";
					print $module->info();
					print '</td>';

					// Show example of numbering module
					print '<td class="nowrap">';
					$tmp=$module->getExample();
					if (preg_match('/^Error/',$tmp)) print '<div class="error">'.$langs->trans($tmp).'</div>';
					elseif ($tmp=='NotConfigured') print $langs->trans($tmp);
					else print $tmp;
					print '</td>'."\n";

					print '<td align="center">';
                    if (isset($conf->global->REPONSE_ADDON) && $conf->global->REPONSE_ADDON == "$file")
                    {
						print img_picto($langs->trans("Activated"),'switch_on');
					}
					else
					{
						print '<a href="'.$_SERVER["PHP_SELF"].'?action=setmod&value='.$file.'&token='.newToken().'">';
						print img_picto($langs->trans("Disabled"),'switch_off');
						print '</a>';
					}
					print '</td>';

					$reponse = new Reponse($db);

					// Info
					$htmltooltip='';
					$htmltooltip.=''.$langs->trans("Version").': <b>'.$module->getVersion().'</b><br>';
					$nextval = $module->getNextValue($mysoc, $reponse);
					if ("$nextval" != $langs->trans("NotAvailable")) {  // Keep " on nextval
						$htmltooltip.=''.$langs->trans("NextValue").': ';
						if ($nextval) {
							if (preg_match('/^Error/',$nextval) || $nextval=='NotConfigured')
								$nextval = $langs->trans($nextval);
							$htmltooltip.=$nextval.'<br>';
						} else {
							$htmltooltip.=$langs->trans($module->error).'<br>';
						}
					}

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


// Page end
dol_fiche_end();
llxFooter();
