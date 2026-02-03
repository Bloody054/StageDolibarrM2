<?php
/* Copyright (C) 2003-2006	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2004-2015	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2005		Marc Barilley / Ocebo	<marc@ocebo.com>
 * Copyright (C) 2005-2015	Regis Houssin			<regis.houssin@inodbox.com>
 * Copyright (C) 2006		Andre Cianfarani		<acianfa@free.fr>
 * Copyright (C) 2010-2013	Juanjo Menent			<jmenent@2byte.es>
 * Copyright (C) 2011-2018	Philippe Grand			<philippe.grand@atoo-net.com>
 * Copyright (C) 2012-2013	Christophe Battarel		<christophe.battarel@altairis.fr>
 * Copyright (C) 2012-2016	Marcos García			<marcosgdf@gmail.com>
 * Copyright (C) 2012       Cedric Salvador      	<csalvador@gpcsolutions.fr>
 * Copyright (C) 2013		Florian Henry			<florian.henry@open-concept.pro>
 * Copyright (C) 2014       Ferran Marcet			<fmarcet@2byte.es>
 * Copyright (C) 2015       Jean-François Ferry		<jfefe@aternatik.fr>
 * Copyright (C) 2018       Frédéric France         <frederic.france@netlogic.fr>
 * Copyright (C) 2017 		Mikael Carlavan 		<contact@mika-carl.fr>
 * Copyright (C) 2024 		Julien Marchand 		<julien.marchand@iouston.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * \file 	htdocs/reponse/card.php
 * \ingroup reponse
 * \brief 	Page to show customer order
 */

$res=@include("../main.inc.php");                   // For root directory
if (! $res) $res=@include("../../main.inc.php");    // For "custom" directory

include_once DOL_DOCUMENT_ROOT . '/core/class/html.formmail.class.php';
include_once DOL_DOCUMENT_ROOT . '/core/class/html.formprojet.class.php';

dol_include_once("/reponse/class/reponse.class.php");
dol_include_once("/reponse/lib/reponse.lib.php");


if (!empty($conf->questionnaire->enabled))
{
	dol_include_once("/questionnaire/class/questionnaire.class.php");
}

$langs->load("reponse@reponse");

$id = GETPOST('id', 'int');
$lineid = GETPOST('lineid', 'int');
$ref = GETPOST('ref', 'alpha');
$action = GETPOST('action', 'alpha');
$cancel = GETPOST('cancel', 'alpha');
$confirm = GETPOST('confirm', 'alpha');
$backtopage = GETPOST('backtopage','alpha');

$result = restrictedArea($user, 'reponse', $id);

$object = new Reponse($db);
$extrafields = new ExtraFields($db);

// fetch optionals attributes and labels
$extralabels = $extrafields->fetch_name_optionals_label($object->table_element);

// Load object
include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php';  // Must be include, not include_once

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('reponsecard','globalcard'));

$permissiondellink = $user->rights->reponse->creer; 	// Used by the include of actions_dellink.inc.php

/*
 * Actions
 */

$error = 0;
$parameters = array();
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook))
{
	if ($cancel)
	{
		if (! empty($backtopage))
		{
			header("Location: ".$backtopage);
			exit;
		}
		$action='';
	}

	if ($action == 'add' && !GETPOST('button', 'alpha'))
	{
		$action = 'create';
	}

	include DOL_DOCUMENT_ROOT.'/core/actions_dellink.inc.php';		// Must be include, not include_once

	if ($action == 'confirm_delete' && $confirm == 'yes' && $user->rights->reponse->supprimer)
	{
		$result = $object->delete($user);
		if ($result > 0)
		{
			header('Location: list.php?restore_lastsearch_values=1');
			exit;
		}
		else
		{
			setEventMessages($object->error, $object->errors, 'errors');
		}
	}
	else if ($action == 'add' && $user->rights->reponse->creer)
	{
		$ret = $extrafields->setOptionalsFromPost($extralabels, $object);
		if ($ret < 0) $error++;

		$fk_questionnaire = GETPOST('fk_questionnaire', 'int');

		if (!empty($conf->questionnaire->enabled) && $fk_questionnaire > 0) {
			$questionnaire = new Questionnaire($db);
			$questionnaire->fetch($fk_questionnaire);
	
			$lines = $questionnaire->lines;

			if (count($lines)) {
				foreach ($lines as $line) {

					$notFilled = true;

					if ($line->mandatory) {
						switch ($line->type) {
							case 'table':
								$notFilled = GETPOST($line->code, 'int') < 1;
							break;
							case 'date':
							case 'datetime':
								$notfilled = empty(GETPOST($line->code, 'alpha'));
                            break;

                            case 'file':
                                $notFilled = !isset($_FILES[$line->code]);
                            break;

							default:
								$notFilled = empty(GETPOST($line->code));
							break;

						}
						
						if ($notFilled) {
							setEventMessages($langs->trans('ErrorFieldRequired', $line->label), null, 'errors');
							$error++;
						}
					}
				}
			}
		}

		if (!$error)
		{
			$object->fk_questionnaire = GETPOST('fk_questionnaire', 'int');

			$id = $object->create($user);
		}
		
		if ($id > 0 && ! $error)
		{
			$object->fill($user);
		}

		if ($id > 0 && ! $error)
		{
			header('Location: ' . $_SERVER["PHP_SELF"] . '?id=' . $id);
			exit;
		} else {
			$action = 'create';
			setEventMessages($object->error, $object->errors, 'errors');
		}
	}
    if ($action == 'confirm_clone' && $confirm == 'yes' && $user->rights->reponse->creer)
    {
        $id = $object->clonefrom($user);
        if ($id > 0)
        {
            setEventMessages($langs->trans("ReponseCloned"), null, 'mesgs');
            header('Location: ' . $_SERVER["PHP_SELF"] . '?id=' . $id);
            exit;
        }
        else
        {
            setEventMessages($object->error, $object->errors, 'errors');
        }
    }
	else if ($action == 'download' && $user->rights->reponse->lire)
	{
		$diroutput = $conf->reponse->dir_output . '/temp/'.$user->id;

		$files[$object->ref] = $object->getAttachedFiles();
		
		if (count($files) > 0)
		{
			$relpath = Reponse::createZipArchive($files, $diroutput);
			if ($relpath)
			{
				// Download file				
				header("Location: ".DOL_URL_ROOT."/document.php?modulepart=reponse&file=".$relpath);
				exit;
			}
		}
		else
		{
			setEventMessages($langs->trans('NoFilesToDownload'), '', 'warnings');	
		}		
	}
    else if ($action == 'setnoteprivate' && !GETPOST('cancel','alpha'))
    {
        $object->note_private = dol_html_entity_decode(GETPOST('note_private', 'none'), ENT_QUOTES);
        $result = $object->update($user);

        if ($result < 0) setEventMessages($object->error, $object->errors, 'errors');

        $object->fetch($object->id);
    }
	else if ($action == 'setfkform' && !GETPOST('cancel','alpha'))
	{
		$object->fk_questionnaire = GETPOST('fk_questionnaire', 'int');
		$result = $object->update($user);
		
		if ($result < 0) setEventMessages($object->error, $object->errors, 'errors');

		$object->fetch($object->id);
	}
    else if ($action == 'setfksoc' && !GETPOST('cancel','alpha'))
    {
        $object->fk_soc = GETPOST('fk_soc', 'int');
        $result = $object->update($user);

        if ($result < 0) setEventMessages($object->error, $object->errors, 'errors');

        $object->fetch($object->id);
    }
    else if ($action == 'setfkprojet' && !GETPOST('cancel','alpha'))
    {
        $object->fk_projet = GETPOST('fk_projet', 'int');
        $result = $object->update($user);

        if ($result < 0) setEventMessages($object->error, $object->errors, 'errors');

        $object->fetch($object->id);
    }
	else if ($action == 'updateline' && !GETPOST('cancel','alpha'))
	{

		if (!empty($conf->questionnaire->enabled)) {
			$line = new QuestionnaireLine($db);
			if ($line->fetch($lineid) > 0) {
				$result = $object->updateline($line);
			}
		}		
		
		if ($result < 0) setEventMessages($object->error, $object->errors, 'errors');
	}
    else if ($action == 'savereponse')
    {
        if (!empty($conf->questionnaire->enabled)) {
            foreach ($object->lines as $l) {
                $line = new QuestionnaireLine($db);
                if ($line->fetch($l->id) > 0) {
                    $result = $object->updateline($line);
                }
            }

            $object->fetch_lines();
            if (count($object->lines)) {
                foreach ($object->lines as $line) {

                    $isMap = $line->type == 'map';
                    $isProductMultiple = $line->type == 'productmultiple';

                    if ($isMap) {
                        $base = $line->code;

                        if (empty($line->value)) {
                            $value = $object->update_gps($base);

                            $l = new ReponseLine($db);
                            $l->fk_reponse   = $object->id;
                            $l->code 			 = $line->code;
                            $l->value = $value;
                            $l->formatted_value = $value;
                            $l->update($user, 1);
                        } else {
                            // Check if we need to update town, region, state or something else
                            $update = GETPOST($line->code.'_update_all') ? true : false;

                            if ($update) {
                                list($lat, $lon) = explode(',', $line->value);
                                $object->update_location($lat, $lon, $base, false, 1);
                            }
                        }
                    }

                    if ($isProductMultiple){
                    	$produits = GETPOST('produits','array');
						$produitsqty = GETPOST('produitsqty','array');
                    	$productmultiple=array();

                    	foreach ($produits as $i => $id) {
    						$productmultiple[$id] = isset($produitsqty[$i]) ? (int) $produitsqty[$i] : 0;
                    	}

                    	$value = json_encode($productmultiple);
                    	$l = new ReponseLine($db);
                            $l->fk_reponse   = $object->id;
                            $l->code 			 = $line->code;
                            $l->value = $value;
                            $l->formatted_value = $value;
                            $l->update($user, 1);                    	
                    }

                }

                $object->fetch_lines();

                foreach ($object->lines as $line) {
                    if (strpos($line->code, '_gps') !== false) {
                        $base = substr($line->code, 0, strpos($line->code, '_gps'));

                        if (isset($object->lines[$base]) && !empty($object->lines[$base]->value)) {
                            $l = new ReponseLine($db);
                            $l->fk_reponse   = $object->id;
                            $l->code 			 = $line->code;

                            $value = $object->lines[$base]->value;

                            $l->value = $value;
                            $l->formatted_value = $value;
                            $l->update($user, 1);
                        }
                    }

                    if (strpos($line->code, '_mois') !== false) {
                        $base = substr($line->code, 0, strpos($line->code, '_mois'));

                        if (isset($object->lines[$base]) && !empty($object->lines[$base]->value)) {
                            $l = new ReponseLine($db);
                            $l->fk_reponse   = $object->id;
                            $l->code 			 = $line->code;

                            $value = $object->lines[$base]->value;
                            $value = dol_print_date($value, "%m");
                            $value = intval($value);

                            $l->value = $value;
                            $l->formatted_value = $value;
                            $l->update($user, 1);
                        }
                    }

                    if (strpos($line->code, '_annee') !== false) {
                        $base = substr($line->code, 0, strpos($line->code, '_annee'));

                        if (isset($object->lines[$base]) && !empty($object->lines[$base]->value)) {
                            $l = new ReponseLine($db);
                            $l->fk_reponse   = $object->id;
                            $l->code 			 = $line->code;

                            $value = $object->lines[$base]->value;
                            $value = dol_print_date($value, "%Y");
                            $value = intval($value);

                            $l->value = $value;
                            $l->formatted_value = $value;
                            $l->update($user, 1);
                        }
                    }

                    if (strpos($line->code, '_code_postal') !== false) {
                        $value = $line->value;

                        $base = substr($line->code, 0, strpos($line->code, '_code_postal'));
                        $questionnaire = new Questionnaire($db);

                        list($lat, $lon, $state, $region) = $questionnaire->geocoder($value);

                        $codes = array(
                             '_departement' => $state,
                            '_region' => $region
                        );

                        foreach ($codes as $code => $value) {
                            if (isset($object->lines[$code]) && empty($object->lines[$code]->value)) {
                                $l = new ReponseLine($db);
                                $l->fk_reponse   = $object->id;
                                $l->code 			 = $code;
                                $l->value           = $value;
                                $l->formatted_value = $value;
                                $l->update($user, 1);
                            }
                        }
                    }
                }

                $object->fetch_lines();
            }
        }

        if ($result < 0) setEventMessages($object->error, $object->errors, 'errors');
    }

	if ($action == 'update_extras')
	{
		$object->oldcopy = dol_clone($object);

		// Fill array 'array_options' with data from update form
		$extralabels = $extrafields->fetch_name_optionals_label($object->table_element);
		$ret = $extrafields->setOptionalsFromPost($extralabels, $object, GETPOST('attribute','none'));
		if ($ret < 0) $error++;

		if (! $error)
		{
			// Actions on extra fields
			$result = $object->insertExtraFields('REPONSE_MODIFY');
			if ($result < 0)
			{
				setEventMessages($object->error, $object->errors, 'errors');
				$error++;
			}
		}

		if ($error) $action = 'edit_extras';
	}


	// Delete file/link
	if ($action == 'confirm_deletefile' && $confirm == 'yes')
	{
		$urlfile = GETPOST('urlfile', 'alpha', 0, null, null, 1);				// Do not use urldecode here ($_GET and $_REQUEST are already decoded by PHP).
		$upload_dir = $conf->reponse->dir_output . '/' . $object->ref;

		$file = $upload_dir . '/' . $urlfile;

		if ($urlfile && !empty($conf->questionnaire->enabled))
		{
			$fline = new QuestionnaireLine($db);

			if ($fline->fetch($lineid) > 0)
			{
				$line = isset($object->lines[$fline->code]) ? $object->lines[$fline->code] : null;

				
				if ($line)
				{
					$values = $line->value ? explode(',', $line->value) : array();

					foreach ($values as $k => $val) {
						if ($val == $urlfile) {
							unset($values[$k]);
						}
					}
	
					$value = count($values) ? implode(',', $values) : '';
					$line->value = $value;
					$result = $object->updateline($line);
					
					$object->fetch_lines();
	
					$ret = dol_delete_file($file, 0, 0, 0, $object);
	
					if ($ret) {
						setEventMessages($langs->trans("FileWasRemoved", $urlfile), null, 'mesgs');
					} else {
						setEventMessages($langs->trans("ErrorFailToDeleteFile", $urlfile), null, 'errors');
					}
				}
			}
		}
	}

	// Actions when printing a doc from card
	include DOL_DOCUMENT_ROOT.'/core/actions_printing.inc.php';

	// Actions to send emails
	$trigger_name='REPONSE_SENTBYMAIL';
	$paramname='id';
	$autocopy='MAIN_MAIL_AUTOCOPY_REPONSE_TO';		// used to know the automatic BCC to add
	$trackid='rep'.$object->id;
	include DOL_DOCUMENT_ROOT.'/core/actions_sendmails.inc.php';

}


/*
 *	View
 */

llxHeader('', $langs->trans('Reponse'), '', '', 0, 0, array('/reponse/js/functions.js.php', '/questionnaire/js/leaflet.js'), array('/questionnaire/css/leaflet.css'));

$form = new Form($db);
$formprojet = new FormProjets($db);

// Mode creation
if ($action == 'create' && $user->rights->reponse->creer)
{
	print load_fiche_titre($langs->trans('NewReponse'),'','reponse@reponse');


	print '<form id="crea_reponse" name="crea_reponse" action="' . $_SERVER["PHP_SELF"] . '" method="POST" enctype="multipart/form-data">';
	print '<input type="hidden" name="token" value="' . $_SESSION ['newtoken'] . '">';
	print '<input type="hidden" name="action" value="add">';

	dol_fiche_head('', '', '', -1);

	print '<table class="border" width="100%">';

	// Reference
	print '<tr><td class="titlefieldcreate fieldrequired">' . $langs->trans('Ref') . '</td><td>' . $object->getNextNumRef($mysoc) . '</td></tr>';

	$fk_questionnaire = GETPOST('fk_questionnaire') ? GETPOST('fk_questionnaire', 'int') : 0;

	if (!empty($conf->questionnaire->enabled))
	{
		$questionnaire = new Questionnaire($db);
		$questionnaires = $questionnaire->liste_array();

		// Reponse
		print '<tr>';
		print '<td class="tdtop">' . $langs->trans('Questionnaire') . '</td>';
		print '<td>';
		print $form->selectarray('fk_questionnaire', $questionnaires, $fk_questionnaire, 1, 0, 0, '', 0, 0, 0, '', '', 1);
		print '</td></tr>';
	}

    print '<tr>';
    print '<td class="tdtop">' . $langs->trans('Company') . '</td>';
    print '<td>';
    print $form->select_company(GETPOST('fk_soc'), 'fk_soc', '', 1);
    print '</td></tr>';

    print '<tr>';
    print '<td class="tdtop">' . $langs->trans('Projet') . '</td>';
    print '<td>';
    print $formprojet->select_projects(GETPOSTISSET('fk_soc') ? GETPOST('fk_soc') : -1, GETPOST('fk_projet'), 'fk_projet');
    print '</td></tr>';

	// Other attributes
	$parameters = array('objectsrc' => '', 'socid'=> '');
	$reshook = $hookmanager->executeHooks('formObjectOptions', $parameters, $object, $action); // Note that $action and $object may have been modified by
	print $hookmanager->resPrint;
	if (empty($reshook)) {
		print $object->showOptionals($extrafields, 'edit');
	}

	print '</table>';


	/*
	* Lines
	*/
	$object->fk_questionnaire = $fk_questionnaire;
	
	$result = $object->getLinesArray();
	
	print '<br style="margin-top: 20px" />';
	if (empty($fk_questionnaire))
	{
		print '<p>'.$langs->trans('SelectForm').'</p>';
	}
	else if (count($object->lines) == 0)
	{
		print '<p>'.$langs->trans('SelectedFormIsEmpty').'</p>';
	}
	else
	{
		print '<div class="div-table-responsive-no-min">';
		print '<table id="tablelines" class="noborder noshadow ui-sortable" width="100%">';
	
		$ret = $object->printObjectLines('addline', $mysoc, '', 0, 1);

		print '</table>';
		print '</div>';
	
	}


	dol_fiche_end();


	print '<div class="center">';
	print '<input type="submit" class="button" name="button" value="' . $langs->trans('CreateReponse') . '">';
	print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
	print '<input type="button" class="button" name="cancel" value="' . $langs->trans("Cancel") . '" onclick="javascript:history.go(-1)">';
	print '</div>';

	print '</form>';

	?>
	<script type="text/javascript">
	$(document).ready(function() {
		$( "#fk_questionnaire" ).change(function(){
			$('#crea_reponse').submit();
		});
	});
	</script>
	<?php
} else {
	// Mode view
	$now = dol_now();

	if ($object->id > 0) 
	{
		$author = new User($db);
		$author->fetch($object->user_author_id);

		$res = $object->fetch_optionals();
		
		$head = reponse_prepare_head($object);
		
		dol_fiche_head($head, 'reponse', $langs->trans("Reponse"), -1, 'reponse@reponse');

		$formconfirm = '';

        // Confirmation to clone
        if ($action == 'clone') {
            $formconfirm = $form->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $object->id, $langs->trans('CloneReponse'), $langs->trans('ConfirmCloneReponse'), 'confirm_clone', '', 0, 1);
        }

		// Confirmation to delete
		if ($action == 'delete') {
			$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $object->id, $langs->trans('DeleteReponse'), $langs->trans('ConfirmDeleteReponse'), 'confirm_delete', '', 0, 1);
		}

		if ($action == 'deletefile')
		{
			$langs->load("companies");	// Need for string DeleteFile+ConfirmDeleteFiles
			$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $object->id . '&urlfile=' . urlencode(GETPOST("urlfile")) . '&lineid=' . GETPOST('lineid', 'int'), $langs->trans('DeleteFile'), $langs->trans('ConfirmDeleteFile'), 'confirm_deletefile', '', 0, 1);
		}


		// Call Hook formConfirm
		$parameters = array();
		$reshook = $hookmanager->executeHooks('formConfirm', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
		if (empty($reshook)) $formconfirm.=$hookmanager->resPrint;
		elseif ($reshook > 0) $formconfirm=$hookmanager->resPrint;

		// Print form confirm
		print $formconfirm;


		// Reponse card

		$url = dol_buildpath('/reponse/list.php', 1).'?restore_lastsearch_values=1';
		$linkback = '<a href="' . $url . '">' . $langs->trans("BackToList") . '</a>';

		// Datamatrix
		$url = '';
		if (!empty($conf->datamatrix->enabled))
		{
			$ref = dol_sanitizeFileName($object->ref);
			$file = $conf->reponse->dir_output . "/" . $ref . "/" . $ref . ".png";
			if (file_exists($file))
			{
				$url = DOL_URL_ROOT.'/viewimage.php?modulepart=reponse&file='.urlencode($ref . "/" . $ref . ".png");
			}
		}

		$morehtmlref = '';
		if ($object->user_author_id) {
		    $author = new User($db);
		    if ($author->fetch($object->user_author_id) > 0) {
                $morehtmlref .= '<br />'.$langs->trans('User'). ' : '.$author->getNomUrl();
            }
        }
        $morehtmlref.= '<br /><div id="object-datamatrix" data-image="'.$url.'" class="refidno">';
		$morehtmlref.= '</div>';

		//dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);
		reponse_banner_tab($object);

		print '<div class="fichecenter">';
		print '<div class="fichehalfleft">';
		print '<div class="underbanner clearboth"></div>';

		print '<table class="border" width="100%">';
		

		if (!empty($conf->questionnaire->enabled))
		{
			$questionnaire = new Questionnaire($db);
			$questionnaires = $questionnaire->liste_array();
		
			// Questionnaire
			print '<tr><td>';
			print '<table class="nobordernopadding" width="100%"><tr><td>';
			print $langs->trans('Form');
			print '</td>';

			if ($action != 'editfkform')
				print '<td align="right"><a href="' . $_SERVER["PHP_SELF"] . '?action=editfkform&id=' . $object->id . '">' . img_edit($langs->trans('SetForm'), 1) . '</a></td>';
			print '</tr></table>';
			print '</td><td>';
			if ($action == 'editfkform') {
				print '<form name="setdate" action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '" method="post">';
				print '<input type="hidden" name="token" value="' . $_SESSION ['newtoken'] . '">';
				print '<input type="hidden" name="action" value="setfkform">';
				print $form->selectarray('fk_questionnaire', $questionnaires, $object->fk_questionnaire, 1, 0, 0, '', 0, 0, 0, '', '', 1);
				print '<input type="submit" class="button" value="' . $langs->trans('Modify') . '">';
				print '</form>';
			} else {
				print $object->questionnaire ? $object->questionnaire->getNomUrl(1) : '&nbsp;';
			}
			print '</td>';
			print '</tr>';
		}

        // Questionnaire
        print '<tr><td>';
        print '<table class="nobordernopadding" width="100%"><tr><td>';
        print $langs->trans('Company');
        print '</td>';

        if ($action != 'editfksoc')
            print '<td align="right"><a href="' . $_SERVER["PHP_SELF"] . '?action=editfksoc&id=' . $object->id . '">' . img_edit($langs->trans('SetCompany'), 1) . '</a></td>';
        print '</tr></table>';
        print '</td><td>';
        if ($action == 'editfksoc') {
            print '<form name="setfksoc" action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '" method="post">';
            print '<input type="hidden" name="token" value="' . $_SESSION ['newtoken'] . '">';
            print '<input type="hidden" name="action" value="setfksoc">';
            print $form->select_company($object->fk_soc, 'fk_soc', '', 1);
            print '<input type="submit" class="button" value="' . $langs->trans('Modify') . '">';
            print '</form>';
        } else {
            $object->fetch_thirdparty();
            print $object->fk_soc > 0 ? $object->thirdparty->getNomUrl(1) : '&nbsp;';
        }
        print '</td>';
        print '</tr>';

        print '<tr><td>';
        print '<table class="nobordernopadding" width="100%"><tr><td>';
        print $langs->trans('Project');
        print '</td>';

        if ($action != 'editfkprojet')
            print '<td align="right"><a href="' . $_SERVER["PHP_SELF"] . '?action=editfkprojet&id=' . $object->id . '">' . img_edit($langs->trans('SetProject'), 1) . '</a></td>';
        print '</tr></table>';
        print '</td><td>';
        if ($action == 'editfkprojet') {
            print '<form name="setfkproject" action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '" method="post">';
            print '<input type="hidden" name="token" value="' . $_SESSION ['newtoken'] . '">';
            print '<input type="hidden" name="action" value="setfkprojet">';
            print $form->select_company($object->fk_soc, 'fk_soc', '', 1);
            print $formprojet->select_projects($object->fk_soc > 0 ? $object->fk_soc : -1, $object->fk_projet, 'fk_projet');
            print '<input type="submit" class="button" value="' . $langs->trans('Modify') . '">';
            print '</form>';
        } else {
            $object->fetch_projet();
               
            print $object->fk_projet > 0 ? $object->project->getNomUrl(1).' '.$object->project->title : '&nbsp;';
        }
        print '</td>';
        print '</tr>';

		// AR
		print '<tr><td>';
		print '<table class="nobordernopadding" width="100%"><tr><td>';
		print $langs->trans('EnvoiAr');
		print '</td>';
		print '</tr></table>';
		print '</td><td>';
		print yn($object->envoi_ar);
		print '</td>';
		print '</tr>';

		// Other attributes
		include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_view.tpl.php';

		print '</table>';

		print '</div>';
		print '<div class="fichehalfright">';
		print '<div class="ficheaddleft">';
		print '<div class="underbanner clearboth"></div>';

        print '<table class="border" width="100%">';

        print '<tr><td>';
        print '<table class="nobordernopadding" width="100%"><tr><td>';
        print $langs->trans('ReponsePrivateNote');
        print '</td>';

        if ($action != 'editnoteprivate')
            print '<td align="right"><a href="' . $_SERVER["PHP_SELF"] . '?action=editnoteprivate&id=' . $object->id . '">' . img_edit($langs->trans('SetIssue'), 1) . '</a></td>';
        print '</tr></table>';
        print '</td><td>';
        if ($action == 'editnoteprivate') {
            print '<form name="setdate" action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '" method="post">';
            print '<input type="hidden" name="token" value="' . $_SESSION ['newtoken'] . '">';
            print '<input type="hidden" name="action" value="setnoteprivate">';
            print '<textarea name="note_private" id="note_private" rows="9" cols="70">'.$object->note_private.'</textarea>';
            print '<input type="submit" class="button" value="' . $langs->trans('Modify') . '">';
            print '</form>';
        } else {
            print $object->note_private;
        }
        print '</td>';
        print '</tr>';
        print '<tr>';
        	print '<td>';
        		print $langs->trans('ReponseInformations');
        	print '</td>';
        	print '<td>';
        		$object->info($object->id);
        		dol_print_object_info($object);
        	print '</td>';
        print '</tr>';	
        print '</table>';

		print '</div>';
		print '</div>';
		print '</div>';

		print '<div class="clearboth"></div><br>';

		/*
		 * Lines
		 */
		$result = $object->getLinesArray();

        print '<form id="save-form" action="' . $_SERVER["PHP_SELF"] . '" method="POST" enctype="multipart/form-data">';
        print '<input type="hidden" name="token" value="' . $_SESSION ['newtoken'] . '">';
        print '<input type="hidden" name="action" value="savereponse">';
        print '<input type="hidden" name="id" value="'.$object->id.'">';


		print '<div class="div-table-responsive-no-min">';
		print '<table id="tablelines" class="noborder noshadow ui-sortable" width="100%">';
		
		// Show object lines
		if (count($object->lines))
			$ret = $object->printObjectLines($action, $mysoc, '', $lineid, 1);

		print '</table>';
		print '</div>';

		print '</form>';

		dol_fiche_end();

		/*
		 * Buttons for actions
		 */
		if ($action != 'presend') {
			print '<div class="tabsAction">';

			$parameters = array();
			$reshook = $hookmanager->executeHooks('addMoreActionsButtons', $parameters, $object, $action); // Note that $action and $object may have been
																									  // modified by hook
			// modified by hook
			if (empty($reshook)) {

			    // Mise à jour
                print '<div class="inline-block divButAction"><a id="save-form-button" class="butAction" href="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&token='.newToken().'">' . $langs->trans('SaveReponseDetails') . '</a></div>';

                // Send email
				if ($user->rights->reponse->creer) {
					$email = $object->email;

					print '<div class="inline-block divButAction"><a class="butAction" href="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&action=presend&sendto='.urlencode($email).'&token='.newToken().'">' . $langs->trans('EmailConfirmReponse') . '</a></div>';
				}

                if ($user->rights->reponse->creer) {
                    print '<div class="inline-block divButAction"><a class="butAction" href="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&action=clone&token='.newToken().'">' . $langs->trans('CloneReponse') . '</a></div>';
                }

                // Download
				if ($user->rights->reponse->lire && $user->rights->reponse->telecharger) {
					print '<div class="inline-block divButAction"><a class="butAction" href="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&action=download&token='.newToken().'">' . $langs->trans('DownloadFiles') . '</a></div>';
				}

                // Location
                if ($user->rights->reponse->creer) {
                    print '<div class="inline-block divButAction"><a class="butAction" href="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&action=prelocation&token='.newToken().'">' . $langs->trans('LocationReponse') . '</a></div>';
                }


                // Delete
				if ($user->rights->reponse->supprimer) {
					print '<div class="inline-block divButAction"><a class="butActionDelete" href="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&action=delete&token='.newToken().'">' . $langs->trans('DeleteReponse') . '</a></div>';
				}
			}

			print '</div>';
		}

		// Select mail models is same action as presend
		if (GETPOST('modelselected')) {
			$action = 'presend';
		}

		if ($action != 'presend')
		{
			print '<div class="fichecenter"><div class="fichehalfleft">';
			print '<a name="builddoc"></a>'; // ancre

			// Show links to link elements
			$linktoelem = $form->showLinkToObjectBlock($object, array(), array('reponse'));
            $parameters=array(
                'morehtmlright' => $linktoelem,
                'compatibleImportElementsList' => false,
            );

            $compatibleImportElementsList = false;
            $somethingshown = $form->showLinkedObjectBlock($object, $linktoelem, $compatibleImportElementsList);

			print '</div><div class="fichehalfright"><div class="ficheaddleft">';

			// List of actions on element
			include_once DOL_DOCUMENT_ROOT . '/core/class/html.formactions.class.php';
			$formactions = new FormActions($db);
			$somethingshown = $formactions->showactions($object, 'reponse', '', 1);

			print '</div></div></div>';
		}

		// Presend form
		$modelmail='reponse_send';
		$defaulttopic='SendReponseRef';
		$diroutput = $conf->reponse->dir_output;
		$trackid = 'rep'.$object->id;

		include DOL_DOCUMENT_ROOT.'/core/tpl/card_presend.tpl.php';
	}
}

// End of page
llxFooter();
$db->close();
