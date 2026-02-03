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
 * \file 	htdocs/questionnaire/card.php
 * \ingroup questionnaire
 * \brief 	Page to show customer order
 */

$res=@include("../main.inc.php");                   // For root directory
if (! $res) $res=@include("../../main.inc.php");    // For "custom" directory

include_once DOL_DOCUMENT_ROOT . '/core/class/html.formmail.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/doleditor.class.php';
require_once  DOL_DOCUMENT_ROOT . '/user/class/usergroup.class.php';

dol_include_once('/questionnaire/class/questionnaire.class.php');
dol_include_once('/questionnaire/lib/questionnaire.lib.php');
dol_include_once('/questionnaire/class/html.form.questionnaire.class.php');
dol_include_once('/core/modules/barcode/doc/tcpdfbarcode.modules.php');
dol_include_once('/reponse/class/html.form.reponse.class.php');

$langs->load("questionnaire@questionnaire");

$id = GETPOST('id', 'int');
$lineid = GETPOST('lineid', 'int');
$ref = GETPOST('ref', 'alpha');
$action = GETPOST('action', 'aZ09');
$cancel = GETPOST('cancel', 'alpha');
$confirm = GETPOST('confirm', 'alpha');
$backtopage = GETPOST('backtopage','alpha');
$active = !empty(GETPOST('active','int')) ? GETPOST('active','int') : 1; //champs de formulaires actif par défaut

$result = restrictedArea($user, 'questionnaire', $id);

$object = new Questionnaire($db);
$extrafields = new ExtraFields($db);
$reponseform = new ReponseForm($db); 

// fetch optionals attributes and labels
$extralabels = $extrafields->fetch_name_optionals_label($object->table_element);

// Load object
include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php';  // Must be include, not include_once

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('questionnairecard','globalcard'));

$permissiondellink = $user->rights->questionnaire->creer; 	// Used by the include of actions_dellink.inc.php

/*
 * Actions
 */

$parameters = array();
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

$error = 0;
if (empty($reshook))
{
	/*if ($cancel)
	{
		if (! empty($backtopage))
		{
			header("Location: ".$backtopage);
			exit;
		}
		else
		{
			header('Location: ' . $_SERVER['PHP_SELF'] . '?id=' . $object->id);
			exit;
		}
	}*/

	include DOL_DOCUMENT_ROOT.'/core/actions_dellink.inc.php';		// Must be include, not include_once
	
	if ($action == 'confirm_delete' && $confirm == 'yes' && $user->rights->questionnaire->supprimer)
	{
		$result = $object->delete($user);
		if ($result > 0)
		{
			header('Location: list.php?restore_lastsearch_values=1');
			exit;
		}
		else
		{
			$action = '';
			setEventMessages($object->error, $object->errors, 'errors');
		}
	}
	// Add 
	else if ($action == 'confirm_clone' && $confirm == 'yes' && $user->rights->questionnaire->creer)
	{
		$id = $object->clone($user);		

		if ($id > 0 && ! $error)
		{
			header('Location: ' . $_SERVER["PHP_SELF"] . '?id=' . $id);
			exit;
		} else {
			$action = '';
			setEventMessages($object->error, $object->errors, 'errors');
		}
	}
	// Add 
	else if ($action == 'add' && $user->rights->questionnaire->creer)
	{
		$title = GETPOST('title', 'alpha');

		if (empty($title)) 
		{
			setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentities('Title')), null, 'errors');
			$action = 'create';
			$error++;
		}

		$ret = $extrafields->setOptionalsFromPost($extralabels, $object);
		if ($ret < 0) $error++;

		if (!$error)
		{
			$object->title 		= $title;
            $object->selected 		= GETPOST('selected', 'int');
            $object->active 		= GETPOST('active', 'int');

			$id = $object->create($user);
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
	else if ($action == 'settitle' && !GETPOST('cancel','alpha'))
	{
		$object->title = GETPOST('title', 'alpha');
		$result = $object->update($user);
		
		if ($result < 0) setEventMessages($object->error, $object->errors, 'errors');
	}
    else if ($action == 'setcolor' && !GETPOST('cancel','alpha'))
    {
        if(!empty(GETPOST('background'))){$object->background = GETPOST('background');}
        if(!empty(GETPOST('buttonbackground'))){$object->buttonbackground = GETPOST('buttonbackground');}
        if(!empty(GETPOST('buttonbackgroundhover'))){$object->buttonbackgroundhover = GETPOST('buttonbackgroundhover');}
        if(!empty(GETPOST('coloraccent'))){$object->coloraccent = GETPOST('coloraccent');}
        $result = $object->update($user);

        if ($result < 0) setEventMessages($object->error, $object->errors, 'errors');
    }
    else if ($action == 'setfooterdescription' && !GETPOST('cancel','alpha'))
    {
        $object->footerdescription = GETPOST('footerdescription');
        $result = $object->update($user);
        if ($result < 0) setEventMessages($object->error, $object->errors, 'errors');
    }
    else if ($action == 'setcustomcss' && !GETPOST('cancel','alpha'))
    {
        $object->customcss = GETPOST('customcss');
        $result = $object->update($user);
        if ($result < 0) setEventMessages($object->error, $object->errors, 'errors');
    }
    else if ($action == 'show_progressbar' && !GETPOST('cancel','alpha'))
	{
		$object->progressbar = 1;
		$result = $object->update($user);
		
		if ($result < 0) setEventMessages($object->error, $object->errors, 'errors');
	}
	else if ($action == 'hide_progressbar' && !GETPOST('cancel','alpha'))
	{
		$object->progressbar = 0;
		$result = $object->update($user);
		if ($result < 0) setEventMessages($object->error, $object->errors, 'errors');
	}
    else if ($action == 'setprogressbarduration' && !GETPOST('cancel','alpha'))
	{
		$object->progressbarduration = GETPOST('progressbarduration');
		$result = $object->update($user);
		if ($result < 0) setEventMessages($object->error, $object->errors, 'errors');
	}else if ($action == 'seticon' && !GETPOST('cancel','alpha'))
	{
		$object->icon = GETPOST('icon');
		$result = $object->update($user);
		if ($result < 0) setEventMessages($object->error, $object->errors, 'errors');
	}
	
	// Add a new line
	else if ($action == 'addline' && $user->rights->questionnaire->creer)
	{
		$langs->load('errors');
		$error = 0;

		$fk_cond = GETPOST('fk_cond', 'int');
		$fk_op_cond = GETPOST('fk_op_cond', 'alpha');
		$val_cond = GETPOST('val_cond');

		$code = GETPOST('code');
		$label = GETPOST('label');
		$help = GETPOST('help');
		$param = GETPOST('param');
		$postfill = GETPOST('postfill');
		$prefill = GETPOST('prefill');

        $postfill_value = GETPOST('postfill_value', 'alpha');
        $prefill_value = GETPOST('prefill_value', 'alpha');


        $type = GETPOST('type');
        $visibility = GETPOST('visibility', 'int');

		$mandatory = GETPOST('mandatory', 'int');
		$crypted = GETPOST('crypted', 'int');
        $inapp = GETPOST('inapp', 'int');


		if (empty($label)) {
			setEventMessages($langs->trans('ErrorFieldRequired', $langs->trans('QuestionnaireLAbel')), null, 'errors');
			$error++;
		}

        $postfill = empty($postfill) ? $postfill_value : $postfill;
        $prefill = empty($prefill) ? $prefill_value : $postfill;

		if (! $error) {

			// Insert line
			$result = $object->addline($label, $code, $type, $postfill, $prefill, $param, $crypted, $inapp, $mandatory, $help, $fk_cond, $fk_op_cond, $val_cond, $visibility);

			if ($result > 0) {
				$ret = $object->fetch($object->id); // Reload to get new records

				unset($_POST['fk_cond']);
				unset($_POST['fk_op_cond']);
				unset($_POST['val_cond']);

				unset($_POST['label']);
				unset($_POST['code']);
				unset($_POST['type']);
				unset($_POST['postfill']);
				unset($_POST['prefill']);

                unset($_POST['postfill_value']);
                unset($_POST['prefill_value']);

				unset($_POST['param']);
                unset($_POST['visibility']);
				unset($_POST['mandatory']);
				unset($_POST['crypted']);
                unset($_POST['inapp']);

                unset($_POST['help']);
			} else {
				setEventMessages($object->error, $object->errors, 'errors');
			}
			
		}
	}
	/*
	 *  Update a line
	 */
	else if ($action == 'updateline' && $user->rights->questionnaire->creer && GETPOST('save'))
	{
		$langs->load('errors');
		$error = 0;

		$fk_cond = GETPOST('fk_cond', 'int');
		$fk_op_cond = GETPOST('fk_op_cond', 'alpha');
		$val_cond = GETPOST('val_cond');

		// Clean parameters
		$code = GETPOST('code');
		$label = GETPOST('label');
		$help = GETPOST('help');
		$param = GETPOST('param');
		$postfill = GETPOST('postfill');
		$prefill = GETPOST('prefill');

        $postfill_value = GETPOST('postfill_value', 'alpha');
        $prefill_value = GETPOST('prefill_value', 'alpha');

		$type = GETPOST('type');
        $visibility = GETPOST('visibility', 'int');
		$mandatory = GETPOST('mandatory', 'int');
		$crypted = GETPOST('crypted', 'int');
        $inapp = GETPOST('inapp', 'int');

        $postfill = empty($postfill) ? $postfill_value : $postfill;
        $prefill = empty($prefill) ? $prefill_value : $postfill;

        if (empty($label)) {
			setEventMessages($langs->trans('ErrorFieldRequired', $langs->trans('QuestionnaireLAbel')), null, 'errors');
			$error++;
		}

		if (! $error) {


			$result = $object->updateline($lineid, $label, $code, $type, $postfill, $prefill, $param, $crypted, $inapp, $mandatory, $help, $fk_cond, $fk_op_cond, $val_cond, $visibility);

			if ($result >= 0) {

				unset($_POST['fk_cond']);
				unset($_POST['fk_op_cond']);
				unset($_POST['val_cond']);

				unset($_POST['label']);
				unset($_POST['code']);
				unset($_POST['type']);
				unset($_POST['postfill']);
				unset($_POST['prefill']);

                unset($_POST['postfill_value']);
                unset($_POST['prefill_value']);

				unset($_POST['param']);
                unset($_POST['visibility']);
				unset($_POST['mandatory']);

				unset($_POST['crypted']);
                unset($_POST['inapp']);
                unset($_POST['help']);
			} else {
				setEventMessages($object->error, $object->errors, 'errors');
			}
		}
	}
	else if ($action == 'updateline' && $user->rights->questionnaire->creer && GETPOST('cancel')) {
		header('Location: ' . $_SERVER['PHP_SELF'] . '?id=' . $object->id); // Pour reaffichage de la fiche en cours d'edition
		exit;
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
			$result = $object->insertExtraFields('QUESTIONNAIRE_MODIFY');
			if ($result < 0)
			{
				setEventMessages($object->error, $object->errors, 'errors');
				$error++;
			}
		}

		if ($error) $action = 'edit_extras';
	}

	// Actions when printing a doc from card
	include DOL_DOCUMENT_ROOT.'/core/actions_printing.inc.php';

	// Actions to send emails
	$trigger_name='QUESTIONNAIRE_SENTBYMAIL';
	$paramname='id';
	$autocopy='MAIN_MAIL_AUTOCOPY_QUESTIONNAIRE_TO';		// used to know the automatic BCC to add
	$trackid='sig'.$object->id;
	include DOL_DOCUMENT_ROOT.'/core/actions_sendmails.inc.php';
}


/*
 *	View
 */

llxHeader('', $langs->trans('Questionnaire'), '', '', 0, 0, array(
	'/questionnaire/js/functions.js.php',
	'/includes/ace/src/ace.js',
	'/includes/ace/src/ext-statusbar.js',
	'/includes/ace/src/ext-language_tools.js'), 
array('/questionnaire/css/style.css'));


$form = new Form($db);
$questionnaireform = new QuestionnaireForm($db);

$formmail = new FormMail($db);
$formmail->fetchAllEMailTemplate('reponse_send', $user, $langs);

$lines = $formmail->lines_model;
$models = array();
if (count($lines)) {
    foreach ($lines as $line) {
        $models[$line->id] = $line->label;
    }
}

// Mode creation
if ($action == 'create' && $user->rights->questionnaire->creer)
{
	print load_fiche_titre($langs->trans('NewQuestionnaire'),'','questionnaire@questionnaire');


	print '<form name="crea_questionnaire" action="' . $_SERVER["PHP_SELF"] . '" method="POST">';
	print '<input type="hidden" name="token" value="' . $_SESSION ['newtoken'] . '">';
	print '<input type="hidden" name="action" value="add">';

	dol_fiche_head('');

	print '<table class="border" width="100%">';

	// Reference
	print '<tr><td class="titlefieldcreate fieldrequired">' . $langs->trans('Ref') . '</td><td>' . $object->getNextNumRef($mysoc) . '</td></tr>';

	// Titre
	print '<tr><td>' . $langs->trans('Title') . '</td><td>';
	print '<input type="text" name="title" value="'.GETPOST('title').'"></td>';
	print '</tr>';

    print '<tr><td>' . $langs->trans('QuestionnaireConfirmationEmailModel') . '</td><td>';
    print $form->selectarray('fk_confirmation_email_model', $models, GETPOST('fk_confirmation_email_model', 'int'), 0);
    print '</td></tr>';


    print '<tr><td>' . $langs->trans('QuestionnaireNotificationGroup') . '</td><td>';
    print $form->select_dolgroups(GETPOST('fk_notification_usergroup', 'int'), 'fk_notification_usergroup',1);
    print '</td></tr>';

    print '<tr><td>' . $langs->trans('QuestionnaireNotificationEmailModel') . '</td><td>';
    print $form->selectarray('fk_notification_email_model', $models, GETPOST('fk_notification_email_model', 'int'), 0);
    print '</td></tr>';

	// Visible
	print '<tr><td>' . $langs->trans('Visible') . '</td><td>';
	print $form->selectyesno('active', $active, 1);
	print '</td></tr>';

	// Défault
	print '<tr><td>' . $langs->trans('Selected') . '</td><td>';
	print $form->selectyesno('selected', GETPOST('selected'), 1);
	print '</td></tr>';

	// Other attributes
	$parameters = array('objectsrc' => '', 'socid'=> '');
	$reshook = $hookmanager->executeHooks('formObjectOptions', $parameters, $object, $action); // Note that $action and $object may have been modified by
	print $hookmanager->resPrint;
	if (empty($reshook)) {
		print $object->showOptionals($extrafields, 'edit');
	}

	print '</table>';

	dol_fiche_end();

	print '<div class="center">';
	print '<input type="submit" class="button" name="bouton" value="' . $langs->trans('CreateQuestionnaire') . '">';
	print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
	print '<input type="button" class="button" name="cancel" value="' . $langs->trans("Cancel") . '" onclick="javascript:history.go(-1)">';
	print '</div>';

	print '</form>';

} else {
	// Mode view
	$now = dol_now();

	if ($object->id > 0) 
	{

		$res = $object->fetch_optionals();
		
		$head = questionnaire_prepare_head($object);
		

		dol_fiche_head($head, 'display', $langs->trans("QuestionnaireDisplay"), -1, 'questionnaire@questionnaire');

		$formconfirm = '';

		// Confirmation to clone
		if ($action == 'clone') {
			$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $object->id, $langs->trans('CloneQuestionnaire'), $langs->trans('ConfirmCloneQuestionnaire'), 'confirm_clone', '', 0, 1);
		}

		// Confirmation to delete
		if ($action == 'delete') {
			$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $object->id, $langs->trans('DeleteQuestionnaire'), $langs->trans('ConfirmDeleteQuestionnaire'), 'confirm_delete', '', 0, 1);
		}

		// Confirmation to delete line
		if ($action == 'ask_deleteline')
		{
			$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id.'&token='.newToken().'&lineid='.$lineid, $langs->trans('DeleteQuestionnaireLine'), $langs->trans('ConfirmDeleteQuestionnaireLine'), 'confirm_deleteline', '', 0, 1);
		}

		// Call Hook formConfirm
		$parameters = array();
		$reshook = $hookmanager->executeHooks('formConfirm', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
		if (empty($reshook)) $formconfirm.=$hookmanager->resPrint;
		elseif ($reshook > 0) $formconfirm=$hookmanager->resPrint;

		// Print form confirm
		print $formconfirm;


		// Questionnaire card
		$url = dol_buildpath('/questionnaire/list.php', 1).'?restore_lastsearch_values=1';
		$linkback = '<a href="' . $url . '">' . $langs->trans("BackToList") . '</a>';
		
		$morehtml = ' - '.$object->title;

		dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref',$morehtml);
		
		print '<div class="fichecenter">';
		print '<div class="underbanner clearboth"></div>';

		print '<table class="border" width="100%">';

		// Choix du thème
		print '<tr><td>';
		print '<table class="nobordernopadding" width="100%"><tr><td>';
		print $langs->trans('Theme');
		print '</td>';

		if ($action != 'edittheme')
			print '<td align="right"><a href="' . $_SERVER["PHP_SELF"] . '?action=edittheme&id=' . $object->id . '&token='.newToken().'">' . img_edit($langs->trans('SetBackghround'), 1) . '</a></td>';
		print '</tr></table>';
		print '</td><td>';
		if ($action == 'edittheme') {
			print '<form name="settheme" action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '" method="post">';
			print '<input type="hidden" name="token" value="' . $_SESSION ['newtoken'] . '">';
			print '<input type="hidden" name="action" value="settheme">';	
			
			$reponse = new Reponse($db);
			$themes = $reponse->getThemes();
			print $reponseform->select_color($object->backgthemeround,'theme');
			print '<input type="submit" class="button" value="' . $langs->trans('Modify') . '">';
			print '</form>';
		} else {
			print $object->theme ? $object->theme : '&nbsp;';
		}
		print '</td>';
		print '</tr>';

		// Choix de l'icone
		print '<tr><td>';
		print '<table class="nobordernopadding" width="100%"><tr><td>';
		print $langs->trans('Icon');
		print '</td>';

		if ($action != 'editicon')
			print '<td align="right"><a href="' . $_SERVER["PHP_SELF"] . '?action=editicon&id=' . $object->id . '&token='.newToken().'">' . img_edit($langs->trans('SetIcon'), 1) . '</a></td>';
		print '</tr></table>';
		print '</td><td>';
		if ($action == 'editicon') {
			print '<form name="seticon" action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '" method="post">';
			print '<input type="hidden" name="token" value="' . $_SESSION ['newtoken'] . '">';
			print '<input type="hidden" name="action" value="seticon">';	
			
			print $questionnaireform->select_icon(GETPOST('icon'), 'icon', '', true);
			print '<input type="submit" class="button" value="' . $langs->trans('Modify') . '">';
			print '</form>';
		} else {
			print $object->icon ? '<img src="'.dol_buildpath('/questionnaire/icons/'.$object->icon,2).'" alt="'.$object->icon.'">' : '&nbsp;';
		}
		print '</td>';
		print '</tr>';

		// Couleur de l'icône
		print '<tr><td>';
		print '<table class="nobordernopadding" width="100%"><tr><td>';
		print $langs->trans('IconColor');
		print '</td>';

		if ($action != 'editiconcolor')
			print '<td align="right"><a href="' . $_SERVER["PHP_SELF"] . '?action=editiconcolor&id=' . $object->id . '&token='.newToken().'">' . img_edit($langs->trans('SetIconColo'), 1) . '</a></td>';
		print '</tr></table>';
		print '</td><td>';
		if ($action == 'editbackground') {
			print '<form name="setbackground" action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '" method="post">';
			print '<input type="hidden" name="token" value="' . $_SESSION ['newtoken'] . '">';
			print '<input type="hidden" name="action" value="setcolor">';	
			print $reponseform->select_color($object->background,'background');
			print '<input type="submit" class="button" value="' . $langs->trans('Modify') . '">';
			print '</form>';
		} else {
			print $object->background ? '<span class="colorbuble" style="background:'.$object->background.';">'.$object->background.'</span>' : '&nbsp;';
		}
		print '</td>';
		print '</tr>';

		// Show progress barr
		print '<tr><td width="30%">';
		print '<table class="nobordernopadding" width="100%"><tr><td>';
		print $langs->trans('ShowProgressBar').img_help(1,$langs->trans('ShowProgressBarHelp'));
		print '</td>';
		print '</tr></table>';
		print '</td><td>';
		if (empty($object->progressbar)) {
			print '<a href="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&action=show_progressbar&token='.newToken().'">';
			print img_picto($langs->trans("Disabled"), 'switch_off');
			print '</a>';
		} else {
			print '<a href="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&action=hide_progressbar&token='.newToken().'">';
			print img_picto($langs->trans("Activated"), 'switch_on');
			print '</a>';
		}
		print '</td>';
		
		//progressbar duration
		print '<td>';
		print $langs->trans('ProgressBarDuration').img_help(1,$langs->trans('ProgressBarDurationHelp'));
		print '</td>';
		print '<td>';
			if ($action != 'editprogressbarduration'){
				print $object->progressbarduration.'s';
				print '<a href="' . $_SERVER["PHP_SELF"] . '?action=editprogressbarduration&id=' . $object->id . '&token='.newToken().'">' . img_edit($langs->trans('SetProgressBarDuration'), 1) . '</a>';
			}
			if ($action == 'editprogressbarduration') {
				print '<form name="setprogressbarduration" action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '" method="post">';
				print '<input type="hidden" name="token" value="' . $_SESSION ['newtoken'] . '">';
				print '<input type="hidden" name="action" value="setprogressbarduration">';	
				print '<input type="text" name="progressbarduration" value="'.$questionnaire->progressbarduration.'">';
				print '<input type="submit" class="button" value="' . $langs->trans('Modify') . '">';
				print '</form>';
			}
		print '</td>';
		print '</tr>';

		// Background color
		print '<tr><td>';
		print '<table class="nobordernopadding" width="100%"><tr><td>';
		print $langs->trans('Background');
		print '</td>';

		if ($action != 'editbackground')
			print '<td align="right"><a href="' . $_SERVER["PHP_SELF"] . '?action=editbackground&id=' . $object->id . '&token='.newToken().'">' . img_edit($langs->trans('SetBackghround'), 1) . '</a></td>';
		print '</tr></table>';
		print '</td><td>';
		if ($action == 'editbackground') {
			print '<form name="setbackground" action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '" method="post">';
			print '<input type="hidden" name="token" value="' . $_SESSION ['newtoken'] . '">';
			print '<input type="hidden" name="action" value="setcolor">';	
			print $reponseform->select_color($object->background,'background');
			print '<input type="submit" class="button" value="' . $langs->trans('Modify') . '">';
			print '</form>';
		} else {
			print $object->background ? '<span class="colorbuble" style="background:'.$object->background.';">'.$object->background.'</span>' : '&nbsp;';
		}
		print '</td>';
		print '</tr>';

		// Accent color
		print '<tr><td>';
		print '<table class="nobordernopadding" width="100%"><tr><td>';
		print $langs->trans('ColorAccent').img_help(1,$langs->trans('ColorAccentHelp'));
		print '</td>';

		if ($action != 'editcoloraccent')
			print '<td align="right"><a href="' . $_SERVER["PHP_SELF"] . '?action=editcoloraccent&id=' . $object->id . '&token='.newToken().'">' . img_edit($langs->trans('SetColorAccent'), 1) . '</a></td>';
		print '</tr></table>';
		print '</td><td>';
		if ($action == 'editcoloraccent') {
			print '<form name="setcoloraccent" action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '" method="post">';
			print '<input type="hidden" name="token" value="' . $_SESSION ['newtoken'] . '">';
			print '<input type="hidden" name="action" value="setcolor">';	
			print $reponseform->select_color($object->coloraccent,'coloraccent');
			print '<input type="submit" class="button" value="' . $langs->trans('Modify') . '">';
			print '</form>';
		} else {
			print $object->coloraccent ? '<span class="colorbuble" style="background:'.$object->coloraccent.';">'.$object->coloraccent.'</span>' : '&nbsp;';
		}
		print '</td>';
		print '</tr>';

		// Button background
		print '<tr><td>';
		print '<table class="nobordernopadding" width="100%"><tr><td>';
		print $langs->trans('ButtonBackground');
		print '</td>';

		if ($action != 'editbuttonbackground')
			print '<td align="right"><a href="' . $_SERVER["PHP_SELF"] . '?action=editbuttonbackground&id=' . $object->id . '&token='.newToken().'">' . img_edit($langs->trans('SetBackghround'), 1) . '</a></td>';
		print '</tr></table>';
		print '</td><td>';
		if ($action == 'editbuttonbackground') {
			print '<form name="setbuttonbackground" action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '" method="post">';
			print '<input type="hidden" name="token" value="' . $_SESSION ['newtoken'] . '">';
			print '<input type="hidden" name="action" value="setcolor">';	
			print $reponseform->select_color($object->buttonbackground,'buttonbackground');
			print '<input type="submit" class="button" value="' . $langs->trans('Modify') . '">';
			print '</form>';
		} else {
			print $object->buttonbackground ? '<span class="colorbuble" style="background:'.$object->buttonbackground.';">'.$object->buttonbackground.'</span>' : '&nbsp;';
		}
		print '</td>';
		print '</tr>';

		// Button background hover
		print '<tr><td>';
		print '<table class="nobordernopadding" width="100%"><tr><td>';
		print $langs->trans('ButtonBackgroundHover');
		print '</td>';

		if ($action != 'editbuttonbackgroundhover')
			print '<td align="right"><a href="' . $_SERVER["PHP_SELF"] . '?action=editbuttonbackgroundhover&id=' . $object->id . '&token='.newToken().'">' . img_edit($langs->trans('SetBackghround'), 1) . '</a></td>';
		print '</tr></table>';
		print '</td><td>';
		if ($action == 'editbuttonbackgroundhover') {
			print '<form name="setbuttonbackgroundhover" action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '" method="post">';
			print '<input type="hidden" name="token" value="' . $_SESSION ['newtoken'] . '">';
			print '<input type="hidden" name="action" value="setcolor">';	
			print $reponseform->select_color($object->buttonbackgroundhover,'buttonbackgroundhover');
			print '<input type="submit" class="button" value="' . $langs->trans('Modify') . '">';
			print '</form>';
		} else {
			print $object->buttonbackgroundhover ? '<span class="colorbuble" style="background:'.$object->buttonbackgroundhover.';">'.$object->buttonbackgroundhover.'</span>' : '&nbsp;';
		}
		print '</td>';
		print '</tr>';

		// Footer description
		print '<tr><td>';
		print '<table class="nobordernopadding" width="100%"><tr><td>';
		print $langs->trans('FooterDescription');
		print '</td>';

		if ($action != 'editfooterdescription')
			print '<td align="right"><a href="' . $_SERVER["PHP_SELF"] . '?action=editfooterdescription&id=' . $object->id . '&token='.newToken().'">' . img_edit($langs->trans('SetTitle'), 1) . '</a></td>';
		print '</tr></table>';
		print '</td><td>';
		if ($action == 'editfooterdescription') {
			print '<form name="setfooterdescription" action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '" method="post">';
			print '<input type="hidden" name="token" value="' . $_SESSION ['newtoken'] . '">';
			print '<input type="hidden" name="action" value="setfooterdescription">';	
			print '<textarea name="footerdescription">'.$object->footerdescription.'</textarea>';
			print '<input type="submit" class="button" value="' . $langs->trans('Modify') . '">';
			print '</form>';
		} else {
			print $object->footerdescription ? $object->footerdescription : '&nbsp;';
		}
		print '</td>';
		print '</tr>';

		// Custom css
		print '<tr><td>';
		print '<table class="nobordernopadding" width="100%"><tr><td>';
		print $langs->trans('Customcss');
		print '</td>';

		if ($action != 'editcustomcss')
			print '<td align="right"><a href="' . $_SERVER["PHP_SELF"] . '?action=editcustomcss&id=' . $object->id . '&token='.newToken().'">' . img_edit($langs->trans('SetTitle'), 1) . '</a></td>';
		print '</tr></table>';
		print '</td><td>';
		if ($action == 'editcustomcss') {
			print '<form name="setcustomcss" action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '" method="post">';
			print '<input type="hidden" name="token" value="' . $_SESSION ['newtoken'] . '">';
			print '<input type="hidden" name="action" value="setcustomcss">';	
			
				print '<div class="div-table-responsive-no-min">';
				print '<table summary="edit" class="noborder centpercent editmode tableforfield">';
				print '<tr class="liste_titre">';
				print '<td colspan="2">';
				$doleditor = new DolEditor('customcss', $object->customcss, '80%', 400, 'Basic', 'In', true, false, 'ace', 10, '100%',0,array(1,1));
				$doleditor->Create(0, '', true, 'css', 'css');
				print '</td></tr>'."\n";
				print '</table>'."\n";
				print '</div>';


			print '<input type="submit" class="button buttonforacesave" value="' . $langs->trans('Modify') . '">';
			print '</form>';
		} else {
			print $object->customcss ? $object->customcss : '&nbsp;';
		}
		print '</td>';
		print '</tr>';    
        
		// Other attributes
        include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_view.tpl.php';

        print '</table>';

        print '</div>';


		dol_fiche_end();

		/*
		 * Buttons for actions
		 */
		print '<div class="tabsAction">';

		$parameters = array();
		$reshook = $hookmanager->executeHooks('addMoreActionsButtons', $parameters, $object, $action); // Note that $action and $object may have been
		
		// modified by hook
		if (empty($reshook)) {
			// Clone questionnaire
			if ($user->rights->questionnaire->creer) {
				print '<div class="inline-block divButAction"><a class="butAction" href="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&action=clone&token='.newToken().'">' . $langs->trans('CloneQuestionnaire') . '</a></div>';
			}

			// Delete order
			if ($user->rights->questionnaire->supprimer) {
				print '<div class="inline-block divButAction"><a class="butActionDelete" href="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&action=delete&token='.newToken().'">' . $langs->trans('DeleteQuestionnaire') . '</a></div>';
			}
		}

		print '</div>';
	


		print '<div class="fichecenter"><div class="fichehalfleft">';
		print '<a name="builddoc"></a>'; // ancre


		print '</div><div class="fichehalfright"><div class="ficheaddleft">';

		// List of actions on element
		include_once DOL_DOCUMENT_ROOT . '/core/class/html.formactions.class.php';
		$formactions = new FormActions($db);
		$somethingshown = $formactions->showactions($object, 'questionnaire', '', 1);

		print '</div></div></div>';


	}
}

// End of page
llxFooter();
$db->close();
