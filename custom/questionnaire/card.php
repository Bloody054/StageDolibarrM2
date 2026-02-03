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

$langs->load("questionnaire@questionnaire");

$id = GETPOST('id', 'int');
$lineid = GETPOST('lineid', 'int');
$ref = GETPOST('ref', 'alpha');
$action = GETPOST('action', 'aZ09');
$cancel = GETPOST('cancel', 'alpha');
$confirm = GETPOST('confirm', 'alpha');
$backtopage = GETPOST('backtopage','alpha');
$active = !empty(GETPOST('active','int')) ? GETPOST('active','int') : 1; //champs de formulaires actif par défaut
$needtobeconnected = !empty(GETPOST('needtobeconnected','int')) ? GETPOST('needtobeconnected','int') : 1; //champs de formulaires besoin d'être connecté par défaut

$result = restrictedArea($user, 'questionnaire', $id);

$object = new Questionnaire($db);
$extrafields = new ExtraFields($db);
$after_submission_choices=$object->after_submission_choices;

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
            $object->needtobeconnected 		= GETPOST('needtobeconnected', 'int');

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
    else if ($action == 'setfkconfirmationemailmodel' && !GETPOST('cancel','alpha'))
    {
        $object->fk_confirmation_email_model = GETPOST('fk_confirmation_email_model', 'int');
        $result = $object->update($user);

        if ($result < 0) setEventMessages($object->error, $object->errors, 'errors');
    }
    else if ($action == 'setfknotifcationemailmodel' && !GETPOST('cancel','alpha'))
    {
        $object->fk_notification_email_model = GETPOST('fk_notification_email_model', 'int');
        $result = $object->update($user);

        if ($result < 0) setEventMessages($object->error, $object->errors, 'errors');
    }
    else if ($action == 'setfknotificationusergroup' && !GETPOST('cancel','alpha'))
    {
        $object->fk_notification_usergroup = GETPOST('fk_notification_usergroup', 'int');
        $result = $object->update($user);

        if ($result < 0) setEventMessages($object->error, $object->errors, 'errors');
    }
	else if ($action == 'setactive' && !GETPOST('cancel','alpha'))
	{
		$object->active = GETPOST('active', 'int');
		$result = $object->update($user);
		
		if ($result < 0) setEventMessages($object->error, $object->errors, 'errors');
	}else if ($action == 'setneedtobeconnected' && !GETPOST('cancel','alpha'))
	{
		$object->needtobeconnected = GETPOST('needtobeconnected', 'int');
		$result = $object->update($user);
		
		if ($result < 0) setEventMessages($object->error, $object->errors, 'errors');
	}else if ($action == 'setaftersubmission' && !GETPOST('cancel','alpha'))
	{
		$object->aftersubmission = GETPOST('aftersubmission');
		$result = $object->update($user);
		
		if ($result < 0) setEventMessages($object->error, $object->errors, 'errors');
	}else if ($action == 'setaftersubmissioncustompage' && !GETPOST('cancel','alpha'))
	{
		$object->aftersubmissioncustompage = GETPOST('aftersubmissioncustompage');
		$result = $object->update($user);
		
		if ($result < 0) setEventMessages($object->error, $object->errors, 'errors');
	}else if ($action == 'setcustomconfirmmessage' && !GETPOST('cancel','alpha'))
	{
		$object->customconfirmmessage = GETPOST('customconfirmmessage');
		$result = $object->update($user);
		
		if ($result < 0) setEventMessages($object->error, $object->errors, 'errors');
	}else if ($action == 'select' && !GETPOST('cancel','alpha'))
	{
        $sql = "UPDATE ".MAIN_DB_PREFIX . "questionnaire SET selected = 0";
        $db->query($sql);
        
		$object->selected = 1;
		$result = $object->update($user);
		
		if ($result < 0) setEventMessages($object->error, $object->errors, 'errors');
	}
	else if ($action == 'unselect' && !GETPOST('cancel','alpha'))
	{
		$object->selected = 0;
		$result = $object->update($user);
		
		if ($result < 0) setEventMessages($object->error, $object->errors, 'errors');
	}
	else if ($action == 'setfkemail' && !GETPOST('cancel','alpha'))
    {
        $object->fk_email = GETPOST('fk_email', 'int');
        $result = $object->update($user);

        if ($result < 0) setEventMessages($object->error, $object->errors, 'errors');

        $object->fetch($object->id);
    }
    else if ($action == 'setfklocation' && !GETPOST('cancel','alpha'))
    {
        $object->fk_location = GETPOST('fk_location', 'int');
        $result = $object->update($user);

        if ($result < 0) setEventMessages($object->error, $object->errors, 'errors');

        $object->fetch($object->id);
    }
    else if ($action == 'setfkdate' && !GETPOST('cancel','alpha'))
    {
        $object->fk_date = GETPOST('fk_date', 'int');
        $result = $object->update($user);

        if ($result < 0) setEventMessages($object->error, $object->errors, 'errors');

        $object->fetch($object->id);
    }
    else if ($action == 'setfkname' && !GETPOST('cancel','alpha'))
    {
        $object->fk_name = GETPOST('fk_name', 'int');
        $result = $object->update($user);

        if ($result < 0) setEventMessages($object->error, $object->errors, 'errors');

        $object->fetch($object->id);
    }
	else if ($action == 'enable')
	{
		$lineid = GETPOST('lineid', 'int');
		$field = GETPOST('field', 'alpha');

		$line = new QuestionnaireLine($db);
		$result = $line->fetch($lineid);
		if ($result > 0) {
			if (isset($line->{$field})) {
				$staticline = clone $line;

				$line->oldline = $staticline;
				$line->{$field} = 1;
				$result = $line->update($user);
			} 
		}
		
		if ($result < 0) setEventMessages($line->error, $line->errors, 'errors');
	}
	else if ($action == 'disable')
	{
		$lineid = GETPOST('lineid', 'int');
		$field = GETPOST('field', 'alpha');

		$line = new QuestionnaireLine($db);
		$result = $line->fetch($lineid);
		if ($result > 0) {
			if (isset($line->{$field})) {
				$staticline = clone $line;

				$line->oldline = $staticline;
				$line->{$field} = 0;
				$result = $line->update($user);
			} 
		}
		
		if ($result < 0) setEventMessages($line->error, $line->errors, 'errors');
	}
	// Remove a product line
	else if ($action == 'confirm_deleteline' && $confirm == 'yes' && $user->rights->questionnaire->creer)
	{
		$result = $object->deleteline($user, $lineid);
		if ($result > 0)
		{
			header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id);
			exit;
		}
		else
		{
			setEventMessages($object->error, $object->errors, 'errors');
		}
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

llxHeader('', $langs->trans('Questionnaire'), '', '', 0, 0, array('/questionnaire/js/functions.js.php'), array('/questionnaire/css/style.css'));


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

	// Active
	print '<tr><td>' . $langs->trans('IsActive') . '</td><td>';
	print $form->selectyesno('active', $active, 1);
	print '</td></tr>';

	// NeedToBeConnected
	print '<tr><td>' . $langs->trans('NeedtoBeConnected') . '</td><td>';
	print $form->selectyesno('needtobeconnected', $needtobeconnected, 1);
	print '</td></tr>';

	// Défault
	print '<tr><td>' . $langs->trans('Selected') . '</td><td>';
	print $form->selectyesno('selected', GETPOST('selected'), 1);
	print '</td></tr>';

	// After Submission
	print '<tr><td>' . $langs->trans('AfterSubmission') . '</td><td>';
	print $form->selectarray('aftersubmission', $after_submission_choices, GETPOST('aftersubmission'), 0);
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
		

		dol_fiche_head($head, 'questionnaire', $langs->trans("Questionnaire"), -1, 'questionnaire@questionnaire');

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
		print '<div class="fichehalfleft">';
		print '<div class="underbanner clearboth"></div>';

		print '<table class="border" width="100%">';

		// Titre
		print '<tr><td>';
		print '<table class="nobordernopadding" width="100%"><tr><td>';
		print $langs->trans('Title');
		print '</td>';

		if ($action != 'edittitle')
			print '<td align="right"><a href="' . $_SERVER["PHP_SELF"] . '?action=edittitle&id=' . $object->id . '&token='.newToken().'">' . img_edit($langs->trans('SetTitle'), 1) . '</a></td>';
		print '</tr></table>';
		print '</td><td>';
		if ($action == 'edittitle') {
			print '<form name="setdate" action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '" method="post">';
			print '<input type="hidden" name="token" value="' . $_SESSION ['newtoken'] . '">';
			print '<input type="hidden" name="action" value="settitle">';	
			print '<input type="text" name="title" value="'.$object->title.'">';
			print '<input type="submit" class="button" value="' . $langs->trans('Modify') . '">';
			print '</form>';
		} else {
			print $object->title ? $object->title : '&nbsp;';
		}
		print '</td>';
		print '</tr>';

        print '<tr><td>';
        print '<table class="nobordernopadding" width="100%"><tr><td>';
        print $langs->trans('QuestionnaireConfirmationEmailModel');
        print '</td>';

        if ($action != 'editfkconfirmationemailmodel')
            print '<td align="right"><a href="' . $_SERVER["PHP_SELF"] . '?action=editfkconfirmationemailmodel&amp;id=' . $object->id . '">' . img_edit($langs->trans('SetQuestionnaireConfirmationEmailModel'), 1) . '</a></td>';
        print '</tr></table>';
        print '</td><td>';
        if ($action == 'editfkconfirmationemailmodel') {
        	print '<form name="setfkconfirmationemailmodel" action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '" method="post">';
            print '<input type="hidden" name="token" value="' . newToken() . '">';
            print '<input type="hidden" name="action" value="setfkconfirmationemailmodel">';
            print $form->selectarray('fk_confirmation_email_model', $models, $object->fk_confirmation_email_model, 0);
            print '<input type="submit" class="button" value="' . $langs->trans('Modify') . '">';
            print '</form>';
        } else {
            print isset($models[$object->fk_confirmation_email_model]) ? $models[$object->fk_confirmation_email_model] : '';
        }
        print '</td>';
        print '</tr>';

        print '<tr><td>';
        print '<table class="nobordernopadding" width="100%"><tr><td>';
        print $langs->trans('QuestionnaireNotificationGroup');
        print '</td>';

        if ($action != 'editfknotificationusergroup')
            print '<td align="right"><a href="' . $_SERVER["PHP_SELF"] . '?action=editfknotificationusergroup&amp;id=' . $object->id . '">' . img_edit($langs->trans('SetQuestionnaireNotificationGroup'), 1) . '</a></td>';
        print '</tr></table>';
        print '</td><td>';
        if ($action == 'editfknotificationusergroup') {
            print '<form name="setfknotificationusergroup" action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '" method="post">';
            print '<input type="hidden" name="token" value="' . newToken() . '">';
            print '<input type="hidden" name="action" value="setfknotificationusergroup">';
            print $form->select_dolgroups($object->fk_notification_usergroup, 'fk_notification_usergroup');
            print '<input type="submit" class="button" value="' . $langs->trans('Modify') . '">';
            print '</form>';
        } else {
            $usergroup = new UserGroup($db);
            $usergroup->fetch($object->fk_notification_usergroup);
            print $object->fk_notification_usergroup > 0 ? $usergroup->getNomUrl() : '';
        }
        print '</td>';
        print '</tr>';


        print '<tr><td>';
        print '<table class="nobordernopadding" width="100%"><tr><td>';
        print $langs->trans('QuestionnaireNotificationEmailModel');
        print '</td>';

        if ($action != 'editfknotificationemailmodel')
            print '<td align="right"><a href="' . $_SERVER["PHP_SELF"] . '?action=editfknotificationemailmodel&amp;id=' . $object->id . '">' . img_edit($langs->trans('SetQuestionnaireNotificationEmailModel'), 1) . '</a></td>';
        print '</tr></table>';
        print '</td><td>';
        if ($action == 'editfknotificationemailmodel') {
            print '<form name="setfknotificationemailmodel" action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '" method="post">';
            print '<input type="hidden" name="token" value="' . newToken() . '">';
            print '<input type="hidden" name="action" value="setfknotificationemailmodel">';
            print $form->selectarray('fk_notification_email_model', $models, $object->fk_notification_email_model, 0);
            print '<input type="submit" class="button" value="' . $langs->trans('Modify') . '">';
            print '</form>';
        } else {
            print isset($models[$object->fk_notification_email_model]) ? $models[$object->fk_notification_email_model] : '';
        }
        print '</td>';
        print '</tr>';

		// Actif
		print '<tr><td>';
		print '<table class="nobordernopadding" width="100%"><tr><td>';
		print $langs->trans('IsActive').' '.img_help(1,$langs->trans('ActiveHelp'));
		print '</td>';

		if ($action != 'editactive')
			print '<td align="right"><a href="' . $_SERVER["PHP_SELF"] . '?action=editactive&id=' . $object->id . '&token='.newToken().'">' . img_edit($langs->trans('SetForHuman'), 1) . '</a></td>';
		print '</tr></table>';
		print '</td><td>';
		if ($action == 'editactive') {
			print '<form name="setactive" action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '" method="post">';
			print '<input type="hidden" name="token" value="' . $_SESSION ['newtoken'] . '">';
			print '<input type="hidden" name="action" value="setactive">';
			print $form->selectyesno('active', $object->active, 1);
			print '<input type="submit" class="button" value="' . $langs->trans('Modify') . '">';
			print '</form>';
		} else {
			print yn($object->active);
		}
		print '</td>';
		print '</tr>';

		// Need to be connected
		print '<tr><td>';
		print '<table class="nobordernopadding" width="100%"><tr><td>';
		print $langs->trans('NeedToBeConnected').' '.img_help(1,$langs->trans('NeedToBeConnectedHelp'));
		print '</td>';

		if ($action != 'editneedtobeconnected')
			print '<td align="right"><a href="' . $_SERVER["PHP_SELF"] . '?action=editneedtobeconnected&id=' . $object->id . '&token='.newToken().'">' . img_edit($langs->trans('SetForActive'), 1) . '</a></td>';
		print '</tr></table>';
		print '</td><td>';
		if ($action == 'editneedtobeconnected') {
			print '<form name="setneedtobeconnected" action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '" method="post">';
			print '<input type="hidden" name="token" value="' . $_SESSION ['newtoken'] . '">';
			print '<input type="hidden" name="action" value="setneedtobeconnected">';
			print $form->selectyesno('needtobeconnected', $object->needtobeconnected, 1);
			print '<input type="submit" class="button" value="' . $langs->trans('Modify') . '">';
			print '</form>';
		} else {
			print yn($object->needtobeconnected);
		}
		print '</td>';
		print '</tr>';



		// Défaut
		print '<tr><td>';
		print '<table class="nobordernopadding" width="100%"><tr><td>';
		print $langs->trans('Selected').img_help(1,$langs->trans('SelectedHelp'));
		print '</td>';
		print '</tr></table>';
		print '</td><td>';
		if (empty($object->selected)) {
			print '<a href="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&action=select&token='.newToken().'">';
			print img_picto($langs->trans("Disabled"), 'switch_off');
			print '</a>';
		} else {
			print '<a href="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&action=unselect&token='.newToken().'">';
			print img_picto($langs->trans("Activated"), 'switch_on');
			print '</a>';
		}
		print '</td>';
		print '</tr>';

		// After submission
		print '<tr><td>';
		print '<table class="nobordernopadding" width="100%"><tr><td>';
		print $langs->trans('AfterSubmission').' '.img_help(1,$langs->trans('AfterSubmissionHelp'));
		print '</td>';

		if ($action != 'editaftersubmission')
			print '<td align="right"><a href="' . $_SERVER["PHP_SELF"] . '?action=editaftersubmission&id=' . $object->id . '&token='.newToken().'">' . img_edit($langs->trans('SetAftersubmission'), 1) . '</a></td>';
		print '</tr></table>';
		print '</td><td>';
		if ($action == 'editaftersubmission') {
			print '<form name="setaftersubmission" action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '" method="post">';
			print '<input type="hidden" name="token" value="' . $_SESSION ['newtoken'] . '">';
			print '<input type="hidden" name="action" value="setaftersubmission">';
			print $form->selectarray('aftersubmission', $after_submission_choices, GETPOST('aftersubmission'), 0);
			print '<input type="submit" class="button" value="' . $langs->trans('Modify') . '">';
			print '</form>';
		} else {
			print (! empty($object->aftersubmission) ? $after_submission_choices[$object->aftersubmission] : '');
		}
		print '</td>';
		print '</tr>';

		// After submission custom page
		if($object->aftersubmission=='custompage'){
			print '<tr><td>';
			print '<table class="nobordernopadding" width="100%"><tr><td>';
			print $langs->trans('AfterSubmissionCustomPage').' '.img_help(1,$langs->trans('AfterSubmissionCustomPageHelp'));
			print '</td>';

			if ($action != 'editaftersubmissioncustompage')
				print '<td align="right"><a href="' . $_SERVER["PHP_SELF"] . '?action=editaftersubmissioncustompage&id=' . $object->id . '&token='.newToken().'">' . img_edit($langs->trans('SetAftersubmissionCustomPage'), 1) . '</a></td>';
			print '</tr></table>';
			print '</td><td>';
			if ($action == 'editaftersubmissioncustompage') {
				print '<form name="setaftersubmissioncustompage" action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '" method="post">';
				print '<input type="hidden" name="token" value="' . $_SESSION ['newtoken'] . '">';
				print '<input type="hidden" name="action" value="setaftersubmissioncustompage">';
				print '<input type="text" name="aftersubmissioncustompage" value="'.$object->aftersubmissioncustompage.'">';
				print '<input type="submit" class="button" value="' . $langs->trans('Modify') . '">';
				print '</form>';
			} else {
				print $object->aftersubmissioncustompage;
			}
			print '</td>';
			print '</tr>';
		}

		// Custom confirm message
		print '<tr><td>';
		print '<table class="nobordernopadding" width="100%"><tr><td>';
		print $langs->trans('CustomConfirmMessage').' '.img_help(1,$langs->trans('CustomConfirmMessageHelp'));
		print '</td>';

		if ($action != 'editcustomconfirmmessage')
			print '<td align="right"><a href="' . $_SERVER["PHP_SELF"] . '?action=editcustomconfirmmessage&id=' . $object->id . '&token='.newToken().'">' . img_edit($langs->trans('SetCustomConfirmMessage'), 1) . '</a></td>';
		print '</tr></table>';
		print '</td><td>';
		if ($action == 'editcustomconfirmmessage') {
			print '<form name="setcustomconfirmmessage" action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '" method="post">';
			print '<input type="hidden" name="token" value="' . $_SESSION ['newtoken'] . '">';
			print '<input type="hidden" name="action" value="setcustomconfirmmessage">';
			print'<textarea id="story" name="customconfirmmessage" rows="5" cols="33">';
				print $object->customconfirmmessage;
			print'</textarea>';
			print '<input type="submit" class="button" value="' . $langs->trans('Modify') . '">';
			print '</form>';
		} else {
			if($object->aftersubmission=='custompage' && !empty($object->customconfirmmessage))
			{
				print img_warning($langs->trans('ThisCustomMessageWillNotDisplayed'));
			}
			print $object->customconfirmmessage;
		}
		print '</td>';
		print '</tr>';
		
		// Other attributes
        include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_view.tpl.php';

        print '</table>';

        print '</div>';

        $formlines = $object->getFormLines();

        print '<div class="fichehalfright">';
        print '<div class="ficheaddleft">';
        print '<div class="underbanner clearboth"></div>';

        print '<table class="border" width="100%">';

        // Email
        print '<tr><td>';
        print '<table class="nobordernopadding" width="100%"><tr><td>';
        print $langs->trans('QuestionnaireEmail').img_help(1,$langs->trans('QuestionnaierEmailHelp'));
        print '</td>';

        if ($action != 'editfkemail')
            print '<td align="right"><a href="' . $_SERVER["PHP_SELF"] . '?action=editfkemail&id=' . $object->id . '&token='.newToken().'">' . img_edit($langs->trans('SetQuestionnaireEmail'), 1) . '</a></td>';
        print '</tr></table>';
        print '</td><td>';
        if ($action == 'editfkemail') {
            print '<form name="setdate" action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '" method="post">';
            print '<input type="hidden" name="token" value="' . $_SESSION ['newtoken'] . '">';
            print '<input type="hidden" name="action" value="setfkemail">';
            print $form->selectarray("fk_email", $formlines, $object->fk_email, 1);
            print '<input type="submit" class="button" value="' . $langs->trans('Modify') . '">';
            print '</form>';
        } else {
            print $object->email_label ?: '&nbsp;';
        }
        print '</td>';
        print '</tr>';

        print '<tr><td>';
        print '<table class="nobordernopadding" width="100%"><tr><td>';
        print $langs->trans('QuestionnaireDate').img_help(1,$langs->trans('QuestionnaireDateHelp'));
        print '</td>';

        if ($action != 'editfkdate')
            print '<td align="right"><a href="' . $_SERVER["PHP_SELF"] . '?action=editfkdate&id=' . $object->id . '&token='.newToken().'">' . img_edit($langs->trans('SetQuestionnaireDate'), 1) . '</a></td>';
        print '</tr></table>';
        print '</td><td>';
        if ($action == 'editfkdate') {
            print '<form name="setdate" action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '" method="post">';
            print '<input type="hidden" name="token" value="' . $_SESSION ['newtoken'] . '">';
            print '<input type="hidden" name="action" value="setfkdate">';
            print $form->selectarray("fk_date", $formlines, $object->fk_date, 1);
            print '<input type="submit" class="button" value="' . $langs->trans('Modify') . '">';
            print '</form>';
        } else {
            print $object->date_label ?: '&nbsp;';
        }
        print '</td>';
        print '</tr>';

        print '<tr><td>';
        print '<table class="nobordernopadding" width="100%"><tr><td>';
        print $langs->trans('QuestionnaireName').img_help(1,$langs->trans('QuestionnaireNameHelp'));
        print '</td>';

        if ($action != 'editfkname')
            print '<td align="right"><a href="' . $_SERVER["PHP_SELF"] . '?action=editfkname&id=' . $object->id . '&token='.newToken().'">' . img_edit($langs->trans('SetQuestionnaireName'), 1) . '</a></td>';
        print '</tr></table>';
        print '</td><td>';
        if ($action == 'editfkname') {
            print '<form name="setdate" action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '" method="post">';
            print '<input type="hidden" name="token" value="' . $_SESSION ['newtoken'] . '">';
            print '<input type="hidden" name="action" value="setfkname">';
            print $form->selectarray("fk_name", $formlines, $object->fk_name, 1);
            print '<input type="submit" class="button" value="' . $langs->trans('Modify') . '">';
            print '</form>';
        } else {
            print $object->name_label ?: '&nbsp;';
        }
        print '</td>';
        print '</tr>';

        print '<tr><td>';
        print '<table class="nobordernopadding" width="100%"><tr><td>';
        print $langs->trans('QuestionnaireLocation').img_help(1,$langs->trans('QuestionnaireLocationHelp'));;
        print '</td>';

        if ($action != 'editfklocation')
            print '<td align="right"><a href="' . $_SERVER["PHP_SELF"] . '?action=editfklocation&id=' . $object->id . '&token='.newToken().'">' . img_edit($langs->trans('SetQuestionnaireLocation'), 1) . '</a></td>';
        print '</tr></table>';
        print '</td><td>';
        if ($action == 'editfklocation') {
            print '<form name="setdate" action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '" method="post">';
            print '<input type="hidden" name="token" value="' . $_SESSION ['newtoken'] . '">';
            print '<input type="hidden" name="action" value="setfklocation">';
            print $form->selectarray("fk_location", $formlines, $object->fk_location, 1);
            print '<input type="submit" class="button" value="' . $langs->trans('Modify') . '">';
            print '</form>';
        } else {
            print $object->location_label ?: '&nbsp;';
        }
        print '</td>';
        print '</tr>';     
        print '</table>';


        // URL publique
        if(isset($conf->global->REPONSE_ROOT_URL)){
			$pathtoform = $conf->global->REPONSE_ROOT_URL . '/report.php?entity='.$object->entity.'&action=create&form-ref='.$object->ref;
			//Display QRCode link
			print '<div class="flexcontainer">';
				print '<div class="flexcontent flexcontentleft">';
					if (class_exists('modTcpdfbarcode')) {
								$module = new modTcpdfbarcode($db);
								if ($module->encodingIsSupported('QRCODE')) {
									$result = $module->writeBarCode($pathtoform, 'QRCODE', 'Y');
									$url = DOL_URL_ROOT.'/viewimage.php?modulepart=barcode&amp;generator='.urlencode('tcpdfbarcode').'&amp;code='.urlencode($pathtoform).'&amp;encoding='.urlencode('QRCODE');
									print '<img src="'.$url.'" title="'.$pathtoform.'" border="0">';
								unset($_GET['code']);	
								} 
					}
				print '</div>';
					
				print '<div class="flexcontent flexcontentright">';
					print '<a href="'.$pathtoform.'" target="_blank">'.img_view().'</a><br>';
				print '</div>';
			print '</div>';
			print '<div>';
			print showValueWithClipboardCPButton($pathtoform);
			print '</div>';
		}else{
			$pathtoform='';
			echo img_warning().' '.$langs->trans('YouNeedSetupReponseAddon'); 
		}
        print '</div>';
        print '</div>';
        print '</div>';

        print '<div class="clearboth"></div><br>';

		/*
		 * Lines
		 */
		$result = $object->getLinesArray();
			
		print '	<form name="addfield" id="addfield" action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . (($action != 'editline') ? '#addline' : '#qline_' . $lineid) . '" method="POST">
		<input type="hidden" name="token" value="' . newToken() . '">
		<input type="hidden" name="action" value="' . (($action != 'editline') ? 'addline' : 'updateline') . '">
		<input type="hidden" name="id" value="' . $object->id . '">';

		if (! empty($conf->use_javascript_ajax)) {
			include DOL_DOCUMENT_ROOT . '/core/tpl/ajaxrow.tpl.php';
		}

        $class = count($object->lines) > 15 ? 'divhead' : '';

		print '<div id="'.$class.'" class="div-table-responsive-no-min">';
		print '<table id="tablelines" class="noborder noshadow ui-sortable" width="100%">';

		// Show object lines
		if (count($object->lines))
			$ret = $object->printObjectLines($action, $mysoc, '', $lineid, 1);

		$numlines = count($object->lines);

		/*
		 * Form to add new line
		 */
		if ($user->rights->questionnaire->creer && $action != 'selectlines')
		{
			if ($action != 'editline')
			{
				// Add free products/services
				$object->formAddObjectLine(1, $mysoc, '');
			}
		}
		print '</table>';
		print '</div>';

		print "</form>\n";

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
