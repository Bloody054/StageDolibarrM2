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
 * \file 	htdocs/questionnaire/action.php
 * \ingroup questionnaire
 * \brief 	Page to show customer order
 */

$res=@include("../main.inc.php");                   // For root directory
if (! $res) $res=@include("../../main.inc.php");    // For "custom" directory

include_once DOL_DOCUMENT_ROOT . '/core/class/html.formmail.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/doleditor.class.php';

dol_include_once("/questionnaire/class/questionnaire.class.php");
dol_include_once("/questionnaire/class/questionnaire.action.class.php");

dol_include_once("/questionnaire/lib/questionnaire.lib.php");
dol_include_once("/questionnaire/class/html.form.questionnaire.class.php");

$langs->load("questionnaire@questionnaire");

$id = GETPOST('id', 'int');
$lineid = GETPOST('lineid', 'int');
$actionid = GETPOST('actionid', 'int');

$ref = GETPOST('ref', 'alpha');
$action = GETPOST('action', 'aZ09');
$cancel = GETPOST('cancel', 'alpha');
$confirm = GETPOST('confirm', 'alpha');
$backtopage = GETPOST('backtopage','alpha');
$active = !empty(GETPOST('active','int')) ? GETPOST('active','int') : 1; //champs de formulaires actif par défaut

$result = restrictedArea($user, 'questionnaire', $id);

$object = new Questionnaire($db);
$extrafields = new ExtraFields($db);

// fetch optionals attributes and labels
$extralabels = $extrafields->fetch_name_optionals_label($object->table_element);

// Load object
include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php';  // Must be include, not include_once

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('questionnaireaction','globalcard'));

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
    if ($action == 'confirm_delete' && $confirm == 'yes' && $user->rights->questionnaire->supprimer)
    {
        $qAction = new QuestionnaireAction($db);
        $qAction->fetch($actionid);

        $result = $qAction->delete($user);
        if ($result < 0)
        {
            $action = '';
            setEventMessages($object->error, $object->errors, 'errors');
        }
    } else if ($action == 'confirm_add' && $confirm == 'yes' && $user->rights->questionnaire->creer) {
        $qAction = new QuestionnaireAction($db);

        $type = GETPOST('type', 'alpha');

        if (empty($type))
        {
            setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentities('Type')), null, 'errors');
            $action = 'create';
            $error++;
        }

        $qAction->type 		= $type;
        $qAction->active 	= 1;
        $qAction->fk_questionnaire = $object->id;

        $qActionId = $qAction->create($user);

        if ($qActionId < 0)
        {
            $action = 'add';
            setEventMessages($qAction->error, $qAction->errors, 'errors');
        }
    } else if ($action == 'confirm_deleteline' && $confirm == 'yes' && $user->rights->questionnaire->creer) {
        $qAction = new QuestionnaireAction($db);
        $qAction->fetch($actionid);

        $result = $qAction->deleteline($user, $lineid);
        if ($result > 0)
        {
            header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id);
            exit;
        }
        else
        {
            setEventMessages($qAction->error, $qAction->errors, 'errors');
        }
    }
    // Add a new line
    else if ($action == 'addline' && $user->rights->questionnaire->creer)
    {
        $langs->load('errors');
        $error = 0;

        $fk_line = GETPOST('fk_line', 'int');
        $use_for_fetch = GETPOST('use_for_fetch', 'int');
        $field = GETPOST('field', 'alpha');

        $qAction = new QuestionnaireAction($db);
        $qAction->fetch($actionid);

        if (! $error) {

            // Insert line
            $result = $qAction->addline($fk_line, $field, $use_for_fetch);

            if ($result > 0) {
                unset($_POST['fk_line']);
                unset($_POST['field']);
                unset($_POST['use_for_fetch']);
            } else {
                setEventMessages($qAction->error, $qAction->errors, 'errors');
            }

        }
    }
    else if ($action == 'updateline' && $user->rights->questionnaire->creer && !GETPOST('cancel'))
    {
        $langs->load('errors');
        $error = 0;

        $fk_line = GETPOST('fk_line', 'int');
        $use_for_fetch = GETPOST('use_for_fetch', 'int');
        $field = GETPOST('field', 'alpha');

        $qAction = new QuestionnaireAction($db);
        $qAction->fetch($actionid);

        if (! $error) {
            $result = $qAction->updateline($lineid, $fk_line, $field, $use_for_fetch);

            if ($result >= 0) {
                unset($_POST['fk_line']);
                unset($_POST['field']);
                unset($_POST['use_for_fetch']);
            } else {
                setEventMessages($qAction->error, $qAction->errors, 'errors');
            }
        }
    } else if ($action == 'updateline' && $user->rights->questionnaire->creer && GETPOST('cancel')) {
        header('Location: ' . $_SERVER['PHP_SELF'] . '?id=' . $object->id); // Pour reaffichage de la fiche en cours d'edition
        exit;
    } else if ($action == 'enable' && $user->rights->questionnaire->creer) {
        $qAction = new QuestionnaireAction($db);
        $qAction->fetch($actionid);

        $qAction->active = 1;
        $qAction->update($user);
    } else if ($action == 'disable' && $user->rights->questionnaire->creer) {
        $qAction = new QuestionnaireAction($db);
        $qAction->fetch($actionid);

        $qAction->active = 0;
        $qAction->update($user);
    }
}


/*
 *	View
 */

llxHeader('', $langs->trans('Questionnaire'));


$form = new Form($db);
$questionnaireform = new QuestionnaireForm($db);

// Mode view
$now = dol_now();

if ($object->id > 0)
{
    $res = $object->fetch_optionals();

    $head = questionnaire_prepare_head($object);


    dol_fiche_head($head, 'action', $langs->trans("Questionnaire"), -1, 'questionnaire@questionnaire');

    $formconfirm = '';


    // Confirmation to delete
    if ($action == 'delete') {
        $formconfirm = $form->formconfirm($_SERVER["PHP_SELF"] . '?actionid='.$actionid.'&id=' . $object->id.'&token='.newToken(), $langs->trans('DeleteQuestionnaireAction'), $langs->trans('ConfirmDeleteQuestionnaireAction'), 'confirm_delete', '', 0, 1);
    }

    // Confirmation to delete line
    if ($action == 'ask_deleteline')
    {
        $formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?actionid='.$actionid.'&id='.$object->id.'&token='.newToken().'&lineid='.$lineid, $langs->trans('DeleteQuestionnaireActionLine'), $langs->trans('ConfirmDeleteQuestionnaireActionLine'), 'confirm_deleteline', '', 0, 1);
    }

    if ($action == 'add') {
        $qAction = new QuestionnaireAction($db);
        $types = $qAction->getTypes();

        $formquestions = array();
        $formquestions[] = array('type' => 'other', 'name' => 'type', 'label' =>$langs->trans("QuestionnaireActionType"),'value' => $form->selectarray('type', $types, GETPOST('type', 'alpha'), 0, 0, 0));

        $formconfirm = $form->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $object->id.'&token='.newToken(), $langs->trans('ActionQuestionnaire'), $langs->trans('ConfirmActionQuestionnaire'), 'confirm_add', $formquestions, 0, 0);
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
    print '</tr></table>';
    print '</td><td>';
    print $object->title ? $object->title : '&nbsp;';
    print '</td>';
    print '</tr>';

    // Actif
    print '<tr><td>';
    print '<table class="nobordernopadding" width="100%"><tr><td>';
    print $langs->trans('Visible');
    print '</td>';
    print '</tr></table>';
    print '</td><td>';
    print yn($object->active);
    print '</td>';
    print '</tr>';

    // URL publique
    if(!isset($conf->global->REPONSE_ROOT_URL)){
        echo img_warning().' '.$langs->trans('YouNeedSetupReponseAddon');
        $pathtoform ='';
    }else{
        $pathtoform = $conf->global->REPONSE_ROOT_URL . '/report.php?action=create&form-ref='.$object->ref;    
    }
    print '<tr><td>';
    print '<table class="nobordernopadding" width="100%"><tr><td>';
    print $langs->trans('URLPublic');
    print '</td>';
    print '</tr></table>';
    print '<td>';
    print '<a href="'.$pathtoform.'" target="_blank">'.$pathtoform.'</a>';
    print '</td>';
    print '</tr>';

    // Défaut
    print '<tr><td>';
    print '<table class="nobordernopadding" width="100%"><tr><td>';
    print $langs->trans('Selected');
    print '</td>';
    print '</tr></table>';
    print '</td><td>';
    if (empty($object->selected)) {
        print img_picto($langs->trans("Disabled"), 'switch_off');
    } else {
        print img_picto($langs->trans("Activated"), 'switch_on');
    }
    print '</td>';
    print '</tr>';


    // Other attributes
    include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_view.tpl.php';

    print '</table>';

    print '</div>';

    print '<div class="fichehalfright">';
    print '<div class="ficheaddleft">';
    print '<div class="underbanner clearboth"></div>';

    // print '<table class="border" width="100%">';

    // print '<tr><td>';
    // print '<table class="nobordernopadding" width="100%"><tr><td>';
    // print $langs->trans('QuestionnaireEmail');
    // print '</td>';
    // print '</tr></table>';
    // print '</td><td>';
    // print $object->email_label ?: '&nbsp;';
    // print '</td>';
    // print '</tr>';

    // print '<tr><td>';
    // print '<table class="nobordernopadding" width="100%"><tr><td>';
    // print $langs->trans('QuestionnaireDate');
    // print '</td>';
    // print '</tr></table>';
    // print '</td><td>';
    // print $object->date_label ?: '&nbsp;';
    // print '</td>';
    // print '</tr>';

    // print '<tr><td>';
    // print '<table class="nobordernopadding" width="100%"><tr><td>';
    // print $langs->trans('QuestionnaireName');
    // print '</td>';
    // print '</tr></table>';
    // print '</td><td>';
    // print $object->name_label ?: '&nbsp;';
    // print '</td>';
    // print '</tr>';

    // print '<tr><td>';
    // print '<table class="nobordernopadding" width="100%"><tr><td>';
    // print $langs->trans('QuestionnaireLocation');
    // print '</td>';

    // print '</tr></table>';
    // print '</td><td>';
    // print $object->location_label ?: '&nbsp;';
    // print '</td>';
    // print '</tr>';

    // print '</table>';

    print '</div>';
    print '</div>';
    print '</div>';

    print '<div class="clearboth"></div><br>';

    /*
     * Lines
     */
    $questionnaireaction = new QuestionnaireAction($db);
    $types = $questionnaireaction->getTypes();

    $qActions = $questionnaireaction->liste_array($object->id);

    if (count($qActions)) {
        print '<div class="div-table-responsive-no-min">';
        print '<table class="noborder noshadow ui-sortable" width="100%">';
        print "<thead id=\"tabhead\">\n";

        print '<tr class="liste_titre nodrag nodrop">';

        // Adds a line numbering column
        if (!empty($conf->global->MAIN_VIEW_LINE_NUMBER)) print '<th class="linecolnum" align="center" width="5">&nbsp;</td>';

        print '<th class="linecoltype">' . $langs->trans('QuestionnaireType') . '</th>';
        print '<th class="linecolactive">' . $langs->trans('QuestionnaireActive') . '</th>';
        print '<th class="linecoldelete" width="10"></th>';

        print "</tr>\n";
        print "</thead>\n";

        $var = true;
        $i = 0;

        print "<tbody>\n";
        foreach ($qActions as $line) {
            $domData = ' data-element="' . $line->element . '"';
            $domData .= ' data-id="' . $line->id . '"';

            $types = $line->getTypes();
            $typeshelp = $line->getTypesHelp();

            print '<tr  id="row-' . $line->id . '" class="drag drop oddeven" ' . $domData . ' >';
            if (!empty($conf->global->MAIN_VIEW_LINE_NUMBER)) {
                print '<td class="linecolnum" align="center">' . ($i + 1) . '</td>';
            }

            print '<td class="linecollabel"><div id="line_' . $line->id . '"></div>';
            print $types[$line->type] ?? '';
            print '</td>';


            print '<td class="linecolcrypted nowrap">';
            if (empty($line->active)) {
                print '<a class="reposition" href="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&actionid=' . $line->id . '&action=enable&token='.newToken().'">';
                print img_picto($langs->trans("Disabled"), 'switch_off');
                print '</a>';
            } else {
                print '<a class="reposition" href="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&actionid=' . $line->id . '&action=disable&token='.newToken().'">';
                print img_picto($langs->trans("Activated"), 'switch_on');
                print '</a>';
            }
            print '</td>';

            print '<td class="linecoldelete" align="center">';
            print '<a href="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&action=delete&actionid=' . $line->id . '&token=' . newToken() . '">';
            print img_delete();
            print '</a>';
            print '</td>';

            print '</tr>';
        }
        print "</tbody>\n";

        print '</table>';
        print '</div>';
    }


    if (count($qActions)) {
        foreach ($qActions as $qAction) {

            print '<b>'.($types[$qAction->type] ?? '').'</b>';
            print '<br><em>'.$typeshelp[$line->type].'</em>';
            print '	<form name="addfield" id="addfield" action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . (($action != 'editline') ? '#addline' : '#qline_' . $lineid) . '" method="POST">
                    <input type="hidden" name="token" value="' . newToken() . '">
                    <input type="hidden" name="action" value="' . (($action != 'editline') ? 'addline' : 'updateline') . '">
                    <input type="hidden" name="id" value="' . $object->id . '">';

            print ' <input type="hidden" name="actionid" value="'.$qAction->id.'" />';

            print '<div class="div-table-responsive-no-min">';
            print '<table id="tablelines" class="noborder noshadow ui-sortable" width="100%">';

            // Show object lines
            if (count($qAction->lines))
                $ret = $qAction->printObjectLines($action, $mysoc, '', $lineid, 1);

            $numlines = count($qAction->lines);

            if ($user->rights->questionnaire->creer && $action != 'selectlines')
            {
                if ($action != 'editline')
                {
                    // Add free products/services
                    $qAction->formAddObjectLine(1, $mysoc, '');
                }
            }
            print '</table>';
            print '</div>';

            print "</form>\n";
        }
    }
    /*

    */

    dol_fiche_end();

    /*
     * Buttons for actions
     */
    print '<div class="tabsAction">';

    $parameters = array();
    $reshook = $hookmanager->executeHooks('addMoreActionsButtons', $parameters, $object, $action); // Note that $action and $object may have been

    // modified by hook
    if (empty($reshook)) {
        // Add action
        if ($user->rights->questionnaire->creer) {
            print '<div class="inline-block divButAction"><a class="butAction" href="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&action=add&token='.newToken().'">' . $langs->trans('AddQuestionnaireAction') . '</a></div>';
        }
    }

    print '</div>';
}

// End of page
llxFooter();
$db->close();
