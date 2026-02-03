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
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * \file 	htdocs/dolitour/card.php
 * \ingroup dolitour
 * \brief 	Page to show customer order
 */
$res=@include("../main.inc.php");                   // For root directory
if (! $res) $res=@include("../../main.inc.php");    // For "custom" directory

require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
include_once DOL_DOCUMENT_ROOT.'/core/class/html.formmail.class.php';

dol_include_once("/dolitour/class/dolitour.class.php");
dol_include_once("/dolitour/lib/dolitour.lib.php");

$langs->load("dolitour@dolitour");

$id = GETPOST('id', 'int');
$lineid = GETPOST('lineid', 'int');
$ref = GETPOST('ref', 'alpha');
$action = GETPOST('action', 'alpha');
$cancel = GETPOST('cancel', 'alpha');
$confirm = GETPOST('confirm', 'alpha');
$backtopage = GETPOST('backtopage','alpha');

$result = restrictedArea($user, 'dolitour', $id);

$object = new DoliTour($db);
$extrafields = new ExtraFields($db);

// fetch optionals attributes and labels
$extralabels = $extrafields->fetch_name_optionals_label($object->table_element);

// Load object
include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php';  // Must be include, not include_once

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('dolitourcard','globalcard'));

$permissiondellink = $user->rights->dolitour->creer; 	// Used by the include of actions_dellink.inc.php

/*
 * Actions
 */

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

	if ($action == 'confirm_delete' && $confirm == 'yes' && $user->rights->dolitour->supprimer)
	{
		$result = $object->delete($user);
		if ($result > 0)
		{
			// Remove old one and create thumbs
			if ($object->image) {
				$fileimg = $conf->dolitour->dir_output.'/'.dol_sanitizeFileName($object->ref).'/'.$object->image;
				$dirthumbs = $conf->dolitour->dir_output.'/'.dol_sanitizeFileName($object->ref).'/thumbs';
				
				dol_delete_file($fileimg);
				dol_delete_dir_recursive($dirthumbs);
			}

			header('Location: list.php?restore_lastsearch_values=1');
			exit;
		}
		else
		{
			setEventMessages($object->error, $object->errors, 'errors');
		}
	}
	if ($action == 'confirm_clone' && $confirm == 'yes' && $user->rights->dolitour->creer)
	{
		$new_object = new DoliTour($db);
		
		// Récupération des données actuelles
		$new_object->title            = $object->title . ' (Clone)'; 
		$new_object->description      = $object->description;
		$new_object->image		 	  = $object->image;
		$new_object->elementtoselect  = $object->elementtoselect;
		$new_object->context          = $object->context;
        $new_object->url              = $object->url; 
		$new_object->show_progress    = $object->show_progress;
		$new_object->show_cross       = $object->show_cross;
		$new_object->font_family      = $object->font_family;
		$new_object->font_size        = $object->font_size;
		$new_object->font_color       = $object->font_color;
		$new_object->background_color = $object->background_color;
		$new_object->side             = $object->side;
		$new_object->align            = $object->align;
		$new_object->color            = $object->color;
		$new_object->fk_user_group    = $object->fk_user_group;
		$new_object->active           = $object->active;
		
		//Création dans la BDD
		$id_new = $new_object->create($user);

		if ($id_new > 0)
		{
			// Redirection 
			header('Location: ' . $_SERVER["PHP_SELF"] . '?id=' . $id_new);
			exit;
		}
		else
		{
			setEventMessages($new_object->error, $new_object->errors, 'errors');
		}
	}
	if ($action == 'confirm_reset' && $confirm == 'yes' && $user->rights->dolitour->modifier) {
        
        // Config Globale : On applique UNIQUEMENT le style par défaut (Sans toucher au contenu)
        $object->show_progress    = !empty($conf->global->DOLITOUR_DEFAULT_SHOW_PROGRESS) ? $conf->global->DOLITOUR_DEFAULT_SHOW_PROGRESS : 1;
        $object->show_cross       = !empty($conf->global->DOLITOUR_DEFAULT_SHOW_CROSS) ? $conf->global->DOLITOUR_DEFAULT_SHOW_CROSS : 1;
        $object->font_size        = !empty($conf->global->DOLITOUR_DEFAULT_FONT_SIZE) ? $conf->global->DOLITOUR_DEFAULT_FONT_SIZE : '';
        $object->font_family      = !empty($conf->global->DOLITOUR_DEFAULT_FONT_FAMILY) ? $conf->global->DOLITOUR_DEFAULT_FONT_FAMILY : '';
        $object->font_color       = !empty($conf->global->DOLITOUR_DEFAULT_FONT_COLOR) ? $conf->global->DOLITOUR_DEFAULT_FONT_COLOR : '';
        $object->background_color = !empty($conf->global->DOLITOUR_DEFAULT_BACKGROUND_COLOR) ? $conf->global->DOLITOUR_DEFAULT_BACKGROUND_COLOR : '';
        $object->side             = !empty($conf->global->DOLITOUR_DEFAULT_SIDE) ? $conf->global->DOLITOUR_DEFAULT_SIDE : 'bottom';
        $object->align            = !empty($conf->global->DOLITOUR_DEFAULT_ALIGN) ? $conf->global->DOLITOUR_DEFAULT_ALIGN : 'start';
        $object->color            = !empty($conf->global->DOLITOUR_DEFAULT_COLOR) ? $conf->global->DOLITOUR_DEFAULT_COLOR : '';

        $result = $object->update($user);

        if ($result > 0)
        {
            setEventMessages("Le style du tour a été réinitialisé.", null, 'mesgs');
            header('Location: ' . $_SERVER["PHP_SELF"] . '?id=' . $object->id);
            exit;
        }
        else
        {
            setEventMessages($object->error, $object->errors, 'errors');
        }
    }

	else if ($action == 'add' && $user->rights->dolitour->creer)
	{
		$ret = $extrafields->setOptionalsFromPost($extralabels, $object);
		if ($ret < 0) $error++;
		
		// Date du début
        $date_start = dol_mktime(0, 0, 0, GETPOST('smonth'), GETPOST('sday'), GETPOST('syear'));
        // Date de la fin
		$date_end = dol_mktime(0, 0, 0, GETPOST('emonth'), GETPOST('eday'), GETPOST('eyear'));
        //Titre
		$title = GETPOST('title', 'alpha');
        //Description + Ajout de l'HTML 
		$description = GETPOST('description','restricthtml');
		//Photo 
		$image = GETPOST('image','alpha');
		//CIBLE CSS
        $elementtoselect = GETPOST('elementtoselect', 'alpha');
		//Context
		$context = GETPOST('context', 'alpha');
        //URL
        $url = GETPOST('url', 'alpha'); 
		// Progression
		$show_progress = GETPOST('show_progress', 'alpha');
		// Croix
		$show_cross = GETPOST('show_cross', 'alpha');
		// Taille écriture
		$font_size = GETPOST('font_size', 'alpha');
		// Police d'écriture
		$font_family = GETPOST('font_family', 'restricthtml');
		// Couleur écriture 
		$font_color = GETPOST('font_color', 'alpha');
		// Couleur bulle
		$background_color = GETPOST('background_color', 'alpha');
		//Position
		$side  = GETPOST('side', 'alpha');
		//Hauteur
        $align = GETPOST('align', 'alpha');
		//Couleur du fond
        $color = GETPOST('color', 'string');
		//Groupe d'utilisateur qui ont l'accès (Si vide = tout le monde)
        $fk_user_group = GETPOST('fk_user_group', 'int');

        if (empty($title) < 0)
        {
            setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentities('Title')), null, 'errors');
            $action = 'create';
            $error++;
        }

        if (!$error)
		{
			$object->title = $title;
			$object->description = $description;
			$object->image = $image;
			//On donne l'info à la classe DoliTour
            $object->elementtoselect = $elementtoselect;
            $object->date_start = GETPOST('sday') ? $date_start : null;
            $object->date_end = GETPOST('eday') ? $date_end : null;
			//Context
			$object->context = $context;
            //URL
            $object->url = $url; 
			//Progresion
			$object->show_progress = $show_progress;
			//Croix 
			$object->show_cross= $show_cross;
			// Taille écriture 
			$object->font_size = $font_size;
			// Police écriture
			$object->font_family = $font_family;
			// Couleur écriture
			$object->font_color = $font_color; 
			//Couleur bulle
			$object->background_color = $background_color;
			//Position
			$object->side = $side;
        	//Hauteur
			$object->align = $align;
			//Couleur de fond
        	$object->color = GETPOST('color','alpha');
			//Rejouabilité
			$object->play_once = GETPOST('play_once', 'int');
			//Groupe Accès
        	$object->fk_user_group = GETPOST('fk_user_group', 'int');
			$id = $object->create($user);
		}
		
		if ($id > 0 && ! $error)
		{
			if (isset($_FILES['element']['tmp_name']) && trim($_FILES['element']['tmp_name'])) {
				$dir = $conf->dolitour->dir_output.'/'.dol_sanitizeFileName($object->ref);

				dol_mkdir($dir);

				if (@is_dir($dir)) {
					$newfile = $dir.'/'.dol_sanitizeFileName($_FILES['element']['name']);
					$result = dol_move_uploaded_file($_FILES['element']['tmp_name'], $newfile, 1, 0, $_FILES['element']['error']);

					if (!$result > 0) {
						setEventMessages($langs->trans("ErrorFailedToSaveFile"), null, 'errors');
					} else {
						// Create thumbs
						$object->addThumbs($newfile);
					}

					$object->image = dol_sanitizeFileName($_FILES['element']['name']);
					$object->update($user);

				} else {
					$error ++;
					$langs->load("errors");
					setEventMessages($langs->trans("ErrorFailedToCreateDir", $dir), $mesgs, 'errors');
				}
			}		
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
    else if ($action == 'setdates' && !GETPOST('cancel','alpha'))
    {
        $date_start = dol_mktime(0, 0, 0, GETPOST('smonth'), GETPOST('sday'), GETPOST('syear'));

        $object->date_start = $date_start;
        $result = $object->update($user);

        if ($result < 0) setEventMessages($object->error, $object->errors, 'errors');
    }
    else if ($action == 'setdatee' && !GETPOST('cancel','alpha'))
    {
        $date_end = dol_mktime(0, 0, 0, GETPOST('emonth'), GETPOST('eday'), GETPOST('eyear'));

        $object->date_end = $date_end;
        $result = $object->update($user);

        if ($result < 0) setEventMessages($object->error, $object->errors, 'errors');
    }
    else if ($action == 'setactive' && !GETPOST('cancel','alpha'))
    {
        $object->active = GETPOST('active', 'int');
        $result = $object->update($user);

        if ($result < 0) setEventMessages($object->error, $object->errors, 'errors');
    }
	else if ($action == 'setdescription' && !GETPOST('cancel','alpha'))
	{
		$object->description = GETPOST('description');
		$result = $object->update($user);
		
		if ($result < 0) setEventMessages($object->error, $object->errors, 'errors');
	}
	else if ($action == 'settitle' && !GETPOST('cancel','alpha'))
	{
		$object->title = GETPOST('title', 'alpha');
		$result = $object->update($user);
		
		if ($result < 0) setEventMessages($object->error, $object->errors, 'errors');
	}
	else if ($action == 'setelement' && !GETPOST('cancel','alpha'))
	{
		$object->elementtoselect = GETPOST('elementtoselect', 'alpha');
		$result = $object->update($user);
		
		if ($result < 0) setEventMessages($object->error, $object->errors, 'errors');
	}
	else if ($action == 'setcontext' && !GETPOST('cancel','alpha'))
	{
		$object->context = GETPOST('context', 'alpha');
		$result = $object->update($user);
		
		if ($result < 0) setEventMessages($object->error, $object->errors, 'errors');
	}
	else if ($action == 'seturl' && !GETPOST('cancel','alpha'))
	{
		$object->url = GETPOST('url', 'alpha');
		$result = $object->update($user);
		
		if ($result < 0) setEventMessages($object->error, $object->errors, 'errors');
	}
	else if ($action == 'setside' && !GETPOST('cancel','alpha'))
	{
		$object->side = GETPOST('side', 'alpha');
		$result = $object->update($user);
		
		if ($result < 0) setEventMessages($object->error, $object->errors, 'errors');
	}
	else if ($action == 'setalign' && !GETPOST('cancel','alpha'))
	{
		$object->align = GETPOST('align', 'alpha');
		$result = $object->update($user);
		
		if ($result < 0) setEventMessages($object->error, $object->errors, 'errors');
	}
	// Pour la modification de groupe 
	else if ($action == 'setfk_user_group' && !GETPOST('cancel','alpha'))
	{
		$object->fk_user_group = GETPOST('fk_user_group', 'int');
		$result = $object->update($user);
		
		if ($result < 0) setEventMessages($object->error, $object->errors, 'errors');
	}
	// Pour la modification de la couleur 
	else if ($action == 'setcolor' && !GETPOST('cancel','alpha'))
	{
   		$object->color = GETPOST('color', 'alpha');
    	$object->update($user);
	}
	// Pour la modification de l'image 
	// Action mise à jour image
	else if ($action == 'setimage' && !GETPOST('cancel','alpha'))
	{
		if (isset($_FILES['element']['tmp_name']) && trim($_FILES['element']['tmp_name'])) {
			$dir = $conf->dolitour->dir_output.'/'.dol_sanitizeFileName($object->ref);
			dol_mkdir($dir);

			if (@is_dir($dir)) {
				$newfile = $dir.'/'.dol_sanitizeFileName($_FILES['element']['name']);
				$result = dol_move_uploaded_file($_FILES['element']['tmp_name'], $newfile, 1, 0, $_FILES['element']['error']);
				
				if ($result > 0) {
					$object->image = dol_sanitizeFileName($_FILES['element']['name']);
					$object->addThumbs($newfile); // Création des miniatures si nécessaire
					$object->update($user);
				} else {
					setEventMessages($langs->trans("ErrorFailedToSaveFile"), null, 'errors');
				}
			}
		}
	}
	else if ($action == 'delete_image' && !GETPOST('cancel','alpha'))
    {
        if ($object->image) {
            // Chemins des fichiers
            $fileimg = $conf->dolitour->dir_output.'/'.dol_sanitizeFileName($object->ref).'/'.$object->image;
            $dirthumbs = $conf->dolitour->dir_output.'/'.dol_sanitizeFileName($object->ref).'/thumbs';
            
            // Suppression physique du fichier et des miniatures
            dol_delete_file($fileimg);
            if (file_exists($dirthumbs)) {
                dol_delete_dir_recursive($dirthumbs);
            }

            // Mise à jour de la base de données (champ vide)
            $object->image = '';
            $object->update($user);
        }
    }
	// Progression
	else if ($action == 'setshow_progress' && !GETPOST('cancel','alpha')) {
		$object->show_progress = GETPOST('show_progress', 'int');
		$object->update($user);
	}
	// Croix
	else if ($action == 'setshow_cross' && !GETPOST('cancel','alpha')) {
		$object->show_cross = GETPOST('show_cross', 'int');
		$object->update($user);
	}
	// Taille écriture
	else if ($action == 'setfont_size' && !GETPOST('cancel','alpha')) {
		$object->font_size = GETPOST('font_size', 'alpha');
		$object->update($user);
	}
	// Police écriture
	else if ($action == 'setfont_family' && !GETPOST('cancel','alpha')) {
		$object->font_family = GETPOST('font_family', 'restricthtml');
		$object->update($user);
	}
	// Couleur écriture
	else if ($action == 'setfont_color' && !GETPOST('cancel','alpha')) {
		$object->font_color = GETPOST('font_color', 'alpha');
		$object->update($user);
	}
	// Couleur Bulle 
	else if ($action == 'setbackground_color' && !GETPOST('cancel','alpha')) {
		$object->background_color = GETPOST('background_color', 'alpha');
		$object->update($user);
	}
	else if ($action == 'setplay_once' && !GETPOST('cancel','alpha')) {
        $object->play_once = GETPOST('play_once', 'int');
        $object->update($user);
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
			$result = $object->insertExtraFields('DOLITOUR_MODIFY');
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
}


/*
 *	View
 */

llxHeader('', $langs->trans('DoliTour'));

$form = new Form($db);
$formother = new FormOther($db);

// Mode creation
if ($action == 'create' && $user->rights->dolitour->creer)
{
	print load_fiche_titre($langs->trans('NewDoliTour'),'','dolitour_small@dolitour');


	print '<form id="crea_dolitour" name="crea_dolitour" action="' . $_SERVER["PHP_SELF"] . '" method="POST" enctype="multipart/form-data">';
	print '<input type="hidden" name="token" value="' . $_SESSION ['newtoken'] . '">';
	print '<input type="hidden" name="action" value="add">';

	dol_fiche_head('', '', '', -1);

    $date_start = dol_mktime(12, 0, 0, GETPOST('smonth'), GETPOST('sday'), GETPOST('syear'));
    $date_end = dol_mktime(12, 0, 0, GETPOST('emonth'), GETPOST('eday'), GETPOST('eyear'));

    print '<table class="border" width="100%">';

	// Reference
	print '<tr><td class="titlefieldcreate fieldrequired">' . $langs->trans('Référence du Tour :') . '</td><td>' . $object->getNextNumRef($mysoc) . '</td></tr>';

	// Titre
	print '<tr><td class="fieldrequired">' . $langs->trans('Titre du Tour :') . '</td><td>';
	print '<input type="text" size="60" name="title" value="'.GETPOST('title').'"></td>';
	print '</tr>';

	// Description
	print '<tr><td>' . $langs->trans('Description du Tour :') . '</td><td>';
	print '<textarea name="description" cols="60" rows="8">'.GETPOST('description').'</textarea></td>';
	print '</tr>';

	// Date Début
    print '<tr><td>' . $langs->trans('DateStart') . '</td><td>';
    print $form->selectDate($date_start ? $date_start : -1, 's', '', '', '', "dates", 1, 1);			// Always autofill date with current date
    print '</td></tr>';

	// Date Fin
    print '<tr><td>' . $langs->trans('DateEnd') . '</td><td>';
    print $form->selectDate($date_end ? $date_end : -1, 'e', '', '', '', "datee", 1, 1);			// Always autofill date with current date
    print '</td></tr>';

	// Groupe Utilisateur
    print '<tr><td>Groupe autorisé : </td><td>';
    print $form->select_dolgroups($object->fk_user_group, 'fk_user_group', 1);
    print '</td></tr>';

	// Cible CSS (elementtoselect)
    //print '<tr><td>Cible CSS (ID ou Class)</td><td>';
	print '<tr><td>' . $langs->trans('Cible CSS de la page :') . '</td><td>';
    // On met un input TEXTE standard. Note le nom : elementtoselect
    print '<input type="text" size="60" name="elementtoselect" value="'.GETPOST('elementtoselect').'">';
    print '</td></tr>';

	// Context 
	print '<tr><td>' . $langs->trans('Contexte (ex: invoice_card) :') . '</td><td>';
	print '<input type="text" size="60" name="context" value="'.GETPOST('context').'">';
    print '</td></tr>';

    // Champs Url 
	print '<tr><td>' . $langs->trans('Url (ex: /compta/facture.php) :') . '</td><td>';
	print '<input type="text" size="60" name="url" value="'.GETPOST('url').'">';
    print '</td></tr>';
	
	// Taille écriture
	print '<tr><td>' . $langs->trans('Taille de l\'écriture (de 10 à 24) :') . '</td><td>';
	print '<input type="text" size="60" name="font_size" value="'.GETPOST('font_size').'" placeholder="Ex: 14px">';
    print '</td></tr>';
	
	// Police écriture
	print '<tr><td>' . $langs->trans('Police d\'écriture :') . '</td><td>';
    
    // Liste des polices disponibles
    $fonts = array('Arial', 'Helvetica', 'Verdana', 'Times New Roman', 'Courier New', 'Roboto', 'Open Sans', 'Tahoma', 'Georgia', 'Trebuchet MS');
    
    print '<select name="font_family" class="flat">';
    print '<option value="">' . $langs->trans("Par défaut") . '</option>';
    
    foreach($fonts as $f) {
        $selected = (GETPOST('font_family') == $f ? 'selected' : '');
        print '<option value="'.$f.'" style="font-family: \''.$f.'\', sans-serif;" '.$selected.'>'.$f.'</option>';
    }
    print '</select>';
    print '</td></tr>';

	// Visibilité
    print '<tr><td>' . $langs->trans('Actif :') . '</td><td>';
    print $form->selectyesno('active', GETPOST('active'), 1);
    print '</td></tr>';

	// Rejouabilité
	print '<tr><td>' . $langs->trans('Jouer une seule fois ?') . '</td><td>';
    print $form->selectyesno('play_once', 1, 1);
    print ' <span class="opacitymedium">( Si "non" est sélectionné, le tour sera rejoué à chaque chargement de la page )</span>';
    print '</td></tr>';

	// Progression
	print '<tr><td>' . $langs->trans('Affichage de la progression :') . '</td><td>';
    print $form->selectyesno('show_progress', (GETPOST('show_progress') !== '' ? GETPOST('show_progress') : 0), 1);
    print '</td></tr>';

	// Croix de fermeture
	print '<tr><td>' . $langs->trans('Possibilité de fermer le Tour :') . '</td><td>';
    // On utilise 'show_cross' comme nom
    print $form->selectyesno('show_cross', (GETPOST('show_cross') !== '' ? GETPOST('show_cross') : 0), 1);
    print '</td></tr>';

	// Position (Side)
	// HTML 
    print '<tr><td>' . $langs->trans('Côté :') . '</td><td>'; 
    print '<select name="side" class="flat">';
    print '<option value="top" '.($object->side=='top'?'selected':'').'>' . $langs->trans("Haut") . '</option>';
    print '<option value="bottom" '.($object->side=='bottom'?'selected':'').'>' . $langs->trans("Bas") . '</option>';
    print '<option value="left" '.($object->side=='left'?'selected':'').'>' . $langs->trans("Gauche") . '</option>';
    print '<option value="right" '.($object->side=='right'?'selected':'').'>' . $langs->trans("Droite") . '</option>';
    print '</select></td></tr>';

    // Alignement
	// HTML
    print '<tr><td>' . $langs->trans('Alignement :') . '</td><td>';
    print '<select name="align" class="flat">';
    print '<option value="start" '.($object->align=='start'?'selected':'').'>' . $langs->trans("Début") . '</option>';
    print '<option value="center" '.($object->align=='center'?'selected':'').'>' . $langs->trans("Centre") . '</option>';
    print '<option value="end" '.($object->align=='end'?'selected':'').'>' . $langs->trans("Fin") . '</option>';
    print '</select></td></tr>';

 	// Couleur de l'écriture
	print '<tr><td>' . $langs->trans('Couleur de l\'écriture :') . '</td><td>';
	print '<input type="color" name="font_color" value="'.(!empty($object->font_color)?$object->font_color:'#1d2b36').'">';
    print '</td></tr>';

	// Couleur tour
	print '<tr><td>' . $langs->trans('Couleur du Fond :') . '</td><td>';
    print '<input type="color" name="background_color" value="'.(!empty($object->background_color)?$object->background_color:'#ffffff').'">';
    print '</td></tr>';
	
	// Couleur du fond
    print '<tr><td>' . $langs->trans('Couleur du calque de recouvrement :') . '</td><td>';
    print '<input type="color" name="color" value="'.(!empty($object->color)?$object->color:'#1d2b36').'">';
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
	print '<input type="submit" class="button" name="button" value="' . $langs->trans('CreateDoliTour') . '">';
	print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
	print '<input type="button" class="button" name="cancel" value="' . $langs->trans("Cancel") . '" onclick="javascript:history.go(-1)">';
	print '</div>';

	print '</form>';

} else {
	// Mode view
	$now = dol_now();

	if ($object->id > 0) 
	{
		$author = new User($db);
		$author->fetch($object->user_author_id);

		$res = $object->fetch_optionals();
		
		$head = dolitour_prepare_head($object);
		
		dol_fiche_head($head, 'dolitour', $langs->trans("DoliTour"), -1, 'dolitour@dolitour');

		$formconfirm = '';

		// Confirmation to delete and clone 
		if ($action == 'clone') {
			$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $object->id, $langs->trans('CloneDolitour'), $langs->trans('ConfirmCloneDolitour'), 'confirm_clone', '', 0, 1);
		}
		if ($action == 'delete') {
			$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $object->id, $langs->trans('DeleteDoliTour'), $langs->trans('ConfirmDeleteDoliTour'), 'confirm_delete', '', 0, 1);
		}
		if ($action == 'reset') {
			$formconfirm = $form->formconfirm(
                $_SERVER["PHP_SELF"] . '?id=' . $object->id, 
                "Réinitialiser le style",              
                "Êtes-vous sûr de vouloir réinitialiser le style (couleurs, polices, position) de ce tour ?<br>Le contenu (titre, description, image, cible) NE SERA PAS modifié.", 
                "confirm_reset",                        
                null, 
                0,                                      
                0                                       
            );
		}
		
		// Call Hook formConfirm
		$parameters = array();
		$reshook = $hookmanager->executeHooks('formConfirm', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
		if (empty($reshook)) $formconfirm.=$hookmanager->resPrint;
		elseif ($reshook > 0) $formconfirm=$hookmanager->resPrint;

		// Print form confirm
		print $formconfirm;


		// DOLITOUR CARD
		$url = dol_buildpath('/dolitour/list.php', 1).'?restore_lastsearch_values=1';
		$linkback = '<a href="' . $url . '">' . $langs->trans("BackToList") . '</a>';
		$backup_ref = $object->ref;
        if (!empty($object->title)) {
            $object->ref = $object->ref . ' - ' . $object->title;
        }
		dol_banner_tab($object, 'ref', $linkback, '', 1);
        $object->ref = $backup_ref;

		print '<div class="fichecenter">';
		print '<div class="underbanner clearboth"></div>';

		// --- COLONNE GAUCHE (Infos de base) ---
		print '<div class="fichehalfleft">';
		print '<table class="border" width="100%">';
		
		// Titre (LEFT)
		print '<tr><td class="titlefield">';
		print '<table class="nobordernopadding" width="100%"><tr><td>';
		print $langs->trans('Title');
		print '</td>';
		if ($action != 'edittitle')
			print '<td align="right"><a href="' . $_SERVER["PHP_SELF"] . '?action=edittitle&amp;id=' . $object->id . '">' . img_edit($langs->trans('SetTitle'), 1) . '</a></td>';
		print '</tr></table>';
		print '</td><td>';
		if ($action == 'edittitle') {
			print '<form name="settitle" action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '" method="post">';
			print '<input type="hidden" name="token" value="' . $_SESSION ['newtoken'] . '">';
			print '<input type="hidden" name="action" value="settitle">';
			print '<input type="text" size="40" name="title" value="'.$object->title.'">';
			print '<input type="submit" class="button" value="' . $langs->trans('Modify') . '">';
			print '</form>';
		} else {
			print $object->title;
		}
		print '</td></tr>';

		// Description (LEFT)
		print '<tr><td>';
		print '<table class="nobordernopadding" width="100%"><tr><td>';
		print $langs->trans('Description');
		print '</td>';
		if ($action != 'editdescription')
			print '<td align="right"><a href="' . $_SERVER["PHP_SELF"] . '?action=editdescription&amp;id=' . $object->id . '">' . img_edit($langs->trans('SetURL'), 1) . '</a></td>';
		print '</tr></table>';
		print '</td><td>';
		if ($action == 'editdescription') {
			print '<form name="seturl" action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '" method="post">';
			print '<input type="hidden" name="token" value="' . $_SESSION ['newtoken'] . '">';
            print '<input type="hidden" name="action" value="setdescription">';
            print '<textarea name="description" cols="40" rows="8">'.$object->description.'</textarea>';
			print '<input type="submit" class="button" value="' . $langs->trans('Modify') . '">';
			print '</form>';
		} else {
			print dol_nl2br($object->description);
		}
		print '</td></tr>';

        // Actif (LEFT)
        print '<tr><td>';
        print '<table class="nobordernopadding" width="100%"><tr><td>';
        print $langs->trans('Active');
        print '</td>';
        if ($action != 'editactive')
        	print '<td align="right"><a href="' . $_SERVER["PHP_SELF"] . '?action=editactive&amp;id=' . $object->id . '">' . img_edit($langs->trans('SetForHuman'), 1) . '</a></td>';
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
        print '</td></tr>';

		// Rejouabilité (LEFT) 
		print '<tr><td>';
        print '<table class="nobordernopadding" width="100%"><tr><td>Jouer une seule fois ?</td>';
        if ($action != 'editplay_once') print '<td align="right"><a href="' . $_SERVER["PHP_SELF"] . '?action=editplay_once&amp;id=' . $object->id . '">' . img_edit($langs->trans('Modify'), 1) . '</a></td>';
        print '</tr></table>';
        print '</td><td>';
        if ($action == 'editplay_once') {
            print '<form action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '" method="post">';
            print '<input type="hidden" name="token" value="' . $_SESSION ['newtoken'] . '">';
            print '<input type="hidden" name="action" value="setplay_once">';
            print $form->selectyesno('play_once', $object->play_once, 1);
            print '<input type="submit" class="button" value="' . $langs->trans('Modify') . '">';
            print '</form>';
        } else {
            print yn($object->play_once);
        }
        print '</td></tr>';

        // Date start (LEFT)
        print '<tr><td>';
        print '<table class="nobordernopadding" width="100%"><tr><td>';
        print $langs->trans('DateStart');
        print '</td>';
        if ($action != 'editdates' && $user->rights->dolitour->modifier)
            print '<td align="right"><a href="' . $_SERVER["PHP_SELF"] . '?action=editdates&amp;id=' . $object->id . '">' . img_edit($langs->trans('IntentionSetDate'), 1) . '</a></td>';
        print '</tr></table>';
        print '</td><td>';
        if ($action == 'editdates') {
            print '<form name="setdates" action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '" method="post">';
            print '<input type="hidden" name="token" value="' . $_SESSION ['newtoken'] . '">';
            print '<input type="hidden" name="action" value="setdates">';
            print $form->selectDate($object->date_start ? $object->date_start : -1, 's', '', '', '', "setdates");
            print '<input type="submit" class="button" value="' . $langs->trans('Modify') . '">';
            print '</form>';
        } else {
            print $object->date_start ? dol_print_date($object->date_start, 'day') : '&nbsp;';
        }
        print '</td></tr>';

        // Date end (LEFT)
        print '<tr><td>';
        print '<table class="nobordernopadding" width="100%"><tr><td>';
        print $langs->trans('DateEnd');
        print '</td>';
        if ($action != 'editdatee' && $user->rights->dolitour->modifier)
            print '<td align="right"><a href="' . $_SERVER["PHP_SELF"] . '?action=editdatee&amp;id=' . $object->id . '">' . img_edit($langs->trans('IntentionSetDate'), 1) . '</a></td>';
        print '</tr></table>';
        print '</td><td>';
        if ($action == 'editdatee') {
            print '<form name="setdatee" action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '" method="post">';
            print '<input type="hidden" name="token" value="' . $_SESSION ['newtoken'] . '">';
            print '<input type="hidden" name="action" value="setdatee">';
            print $form->selectDate($object->date_end ? $object->date_end : -1, 'e', '', '', '', "setdatee");
            print '<input type="submit" class="button" value="' . $langs->trans('Modify') . '">';
            print '</form>';
        } else {
            print $object->date_end ? dol_print_date($object->date_end, 'day') : '&nbsp;';
        }
        print '</td></tr>';

        // Groupe Utilisateur (LEFT)
        print '<tr><td>';
        print '<table class="nobordernopadding" width="100%"><tr><td>';
        print $langs->trans('Groupe Accès'); 
        print '</td>';
        if ($action != 'editfk_user_group')
        	print '<td align="right"><a href="' . $_SERVER["PHP_SELF"] . '?action=editfk_user_group&amp;id=' . $object->id . '">' . img_edit($langs->trans('SetFk_user_group'), 1) . '</a></td>';
        print '</tr></table>';
        print '</td><td>';
        if ($action == 'editfk_user_group') {
            print '<form name="setfk_user_group" action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '" method="post">';
            print '<input type="hidden" name="token" value="' . $_SESSION ['newtoken'] . '">';
            print '<input type="hidden" name="action" value="setfk_user_group">';
            print $form->select_dolgroups($object->fk_user_group, 'fk_user_group', 1);
            print '<input type="submit" class="button" value="' . $langs->trans('Modify') . '">';
            print '</form>';
        } else {
            if ($object->fk_user_group > 0) {
                $group_static = new UserGroup($db);
                $group_static->fetch($object->fk_user_group);
                print $group_static->name;
            } else {
                print $langs->trans("Tout le monde");
            }
        }
        print '</td></tr>';

		// Nombre de vues (LEFT) 
		$nb_views = 0;
        $sql_stats = "SELECT count(rowid) as nb FROM ".MAIN_DB_PREFIX."dolitour_logs WHERE fk_tour = ".((int)$object->id);
        $res_stats = $db->query($sql_stats);
        if ($res_stats && $db->num_rows($res_stats) > 0) {
            $obj_stats = $db->fetch_object($res_stats);
            $nb_views = $obj_stats->nb;
        }

        print '<tr><td>';
        print $langs->trans("Nombre de vues");
        if (!$langs->trans("Nombre de vues") || $langs->trans("Nombre de vues") == "Nombre de vues");
        print '</td><td>';
        
        print '<span class="badge badge-status badge-info" style="font-size: 1.1em; padding: 5px 10px;">'.$nb_views.'</span>';
        
        if ($nb_views > 0 && $user->admin) { 
			print ' &nbsp; <a href="'.dol_buildpath('/dolitour/list_logs.php', 1).'?search_tour_id='.$object->id.'&search_tour='.urlencode($object->title).'" title="Voir le détail des logs">';
            print '<span class="fa fa-list-alt"></span> Voir les détails';
            print '</a>';
        }
        print '</td></tr>';

		// Aperçu visuel (Left) 
		print '<tr><td valign="top" style="padding-top:15px;">';
		print 'Aperçu du rendu';
		print '</td><td style="padding-top:15px;">';

		// Définition des styles par défaut si vides
		$pv_bg = !empty($object->background_color) ? $object->background_color : '#ffffff';
		$pv_color = !empty($object->font_color) ? $object->font_color : '#333333';
		$pv_font = !empty($object->font_family) ? $object->font_family : 'inherit';
		$pv_size = !empty($object->font_size) ? $object->font_size : 'inherit';
		
		// Style du conteneur (Imitation Driver.js)
		$style_container = "background-color: ".$pv_bg."; ";
		$style_container.= "color: ".$pv_color."; ";
		$style_container.= "font-family: '".$pv_font."', sans-serif; ";
		$style_container.= "font-size: ".$pv_size."; ";
		$style_container.= "width: 280px; padding: 15px; border-radius: 5px; ";
		$style_container.= "box-shadow: 0 2px 15px rgba(0,0,0,0.2); position: relative;";

		print '<div style="'.$style_container.'">';
		
		// Croix (si activée)
		if ($object->show_cross) {
			print '<div style="position:absolute; top:5px; right:10px; cursor:default; font-size:1.2em; opacity:0.6;">&times;</div>';
		}

		// Titre
		print '<div style="font-weight: bold; font-size: 1.1em; margin-bottom: 8px; line-height:1.2;">';
		print !empty($object->title) ? $object->title : 'Titre du tour';
		print '</div>';

		// Description
		print '<div style="font-size: 0.9em; line-height: 1.4; opacity: 0.9;">';
		print !empty($object->description) ? dol_nl2br($object->description) : 'Ceci est un aperçu du texte...';
		print '</div>';

		// Image
		if ($object->image) {
			$relative_path = dol_sanitizeFileName($object->ref) . '/' . $object->image;
            $image_url = DOL_URL_ROOT . '/viewimage.php?modulepart=dolitour&file=' . urlencode($relative_path);
			print '<div style="margin-top: 10px; text-align: center;">';
			print '<img src="'.$image_url.'" style="max-width: 100%; border-radius: 3px; border: 1px solid rgba(0,0,0,0.1);">';
			print '</div>';
		}

		// Footer (Boutons + Progression)
		print '<div style="margin-top: 15px; display: flex; justify-content: space-between; align-items: center; font-size: 0.8em;">';
		
		// Progression
		if ($object->show_progress) {
			print '<span style="opacity: 0.7;">1 / 4</span>';
		} else {
			print '<span></span>';
		}

		// Boutons fictifs
		print '<div>';
		print '<span style="background: #f1f1f1; color: #333; padding: 4px 8px; border-radius: 3px; margin-right: 5px; border:1px solid #ddd; cursor:default;">Préc.</span>';
		print '<span style="background: #e1e1e1; color: #333; padding: 4px 8px; border-radius: 3px; border:1px solid #ccc; cursor:default;">Suivant</span>';
		print '</div>';
		
		print '</div>'; 

		// Indicateur de position (Petite flèche CSS)
		$arrow_style = "position:absolute; width:0; height:0; border-style:solid; ";
		// On simule une flèche en bas par défaut ou selon le réglage
		if ($object->side == 'top') {
			$arrow_style .= "border-width: 8px 8px 0 8px; border-color: $pv_bg transparent transparent transparent; bottom: -8px; left: 50%; margin-left:-8px;";
		} elseif ($object->side == 'left') {
			$arrow_style .= "border-width: 8px 0 8px 8px; border-color: transparent transparent transparent $pv_bg; right: -8px; top: 50%; margin-top:-8px;";
		} elseif ($object->side == 'right') {
			$arrow_style .= "border-width: 8px 8px 8px 0; border-color: transparent $pv_bg transparent transparent; left: -8px; top: 50%; margin-top:-8px;";
		} else {
			// Default bottom
			$arrow_style .= "border-width: 0 8px 8px 8px; border-color: transparent transparent $pv_bg transparent; top: -8px; left: 50%; margin-left:-8px;";
		}
		print '<div style="'.$arrow_style.'"></div>';

		print '</table>'; 
		print '</div>'; 
		
		// --- COLONNE DROITE (Personnalisation) ---
		
		print '<div class="fichehalfright">';
		print '<div class="ficheaddleft">'; 
		print '<table class="border" width="100%">';

		// Cible CSS (RIGHT)
		print '<tr><td>';
		print '<table class="nobordernopadding" width="100%"><tr><td>';
		print $langs->trans('Cible CSS');
		print '</td>';
		if ($action != 'editelement')
			print '<td align="right"><a href="' . $_SERVER["PHP_SELF"] . '?action=editelement&amp;id=' . $object->id . '">' . img_edit($langs->trans('SetElement'), 1) . '</a></td>';
		print '</tr></table>';
		print '</td><td>';
		if ($action == 'editelement') {
			print '<form name="setelement" action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '" method="post">';
			print '<input type="hidden" name="token" value="' . $_SESSION ['newtoken'] . '">';
			print '<input type="hidden" name="action" value="setelement">';
			print '<input type="text" size="30" name="elementtoselect" value="'.$object->elementtoselect.'">';
			print '<input type="submit" class="button" value="' . $langs->trans('Modify') . '">';
			print '</form>';
		} else {
			print $object->elementtoselect;
		}
		print '</td></tr>';		

		// URL (RIGHT) 
		print '<tr><td>';
		print '<table class="nobordernopadding" width="100%"><tr><td>';
		print $langs->trans('Url');
		print '</td>';
		if ($action != 'editurl')
			print '<td align="right"><a href="' . $_SERVER["PHP_SELF"] . '?action=editurl&amp;id=' . $object->id . '">' . img_edit($langs->trans('SetURL'), 1) . '</a></td>';
		print '</tr></table>';
		print '</td><td>';
		if ($action == 'editurl') {
			print '<form name="seturl" action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '" method="post">';
			print '<input type="hidden" name="token" value="' . $_SESSION ['newtoken'] . '">';
			print '<input type="hidden" name="action" value="seturl">';
			print '<input type="text" size="30" name="url" value="'.$object->url.'">';
			print '<input type="submit" class="button" value="' . $langs->trans('Modify') . '">';
			print '</form>';
		} else {
			print $object->url;
		}
		print '</td></tr>';

		// Contexte (RIGHT)
		print '<tr><td>';
		print '<table class="nobordernopadding" width="100%"><tr><td>';
		print $langs->trans('Contexte');
		print '</td>';
		if ($action != 'editcontext')
			print '<td align="right"><a href="' . $_SERVER["PHP_SELF"] . '?action=editcontext&amp;id=' . $object->id . '">' . img_edit($langs->trans('SetContext'), 1) . '</a></td>';
		print '</tr></table>';
		print '</td><td>';
		if ($action == 'editcontext') {
			print '<form name="setcontext" action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '" method="post">';
			print '<input type="hidden" name="token" value="' . $_SESSION ['newtoken'] . '">';
			print '<input type="hidden" name="action" value="setcontext">';
			print '<input type="text" size="30" name="context" value="'.$object->context.'">';
			print '<input type="submit" class="button" value="' . $langs->trans('Modify') . '">';
			print '</form>';
		} else {
			print $object->context;
		}
		print '</td></tr>';

		// Side (Côté) (RIGHT)
		print '<tr><td>';
		print '<table class="nobordernopadding" width="100%"><tr><td>';
		print $langs->trans('Side');
		print '</td>';
		if ($action != 'editside')
			print '<td align="right"><a href="' . $_SERVER["PHP_SELF"] . '?action=editside&amp;id=' . $object->id . '">' . img_edit($langs->trans('SetSide'), 1) . '</a></td>';
		print '</tr></table>';
		print '</td><td>';
		if ($action == 'editside') {
            print '<form name="setside" action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '" method="post">';
			print '<input type="hidden" name="token" value="' . $_SESSION ['newtoken'] . '">';
			print '<input type="hidden" name="action" value="setside">';
            print '<select name="side" class="flat">';
            print '<option value="top" '.($object->side=='top'?'selected':'').'>'.$langs->trans("Haut").'</option>';
            print '<option value="bottom" '.($object->side=='bottom'?'selected':'').'>'.$langs->trans("Bas").'</option>';
            print '<option value="left" '.($object->side=='left'?'selected':'').'>'.$langs->trans("Gauche").'</option>';
            print '<option value="right" '.($object->side=='right'?'selected':'').'>'.$langs->trans("Right").'</option>';
            print '<option value="center" '.($object->side=='center'?'selected':'').'>Centre</option>'; // Ajout du centre
            print '</select>';
			print '<input type="submit" class="button" value="' . $langs->trans('Modify') . '">';
			print '</form>';
		} else {
            $lib_side = $object->side;
            if ($object->side == 'top') $lib_side = $langs->trans("Haut");
            if ($object->side == 'bottom') $lib_side = $langs->trans("Bas");
            if ($object->side == 'left') $lib_side = $langs->trans("Gauche");
            if ($object->side == 'right') $lib_side = $langs->trans("Droite");
            if ($object->side == 'center') $lib_side = "Centre";
			print $lib_side;
		}
        print '</td></tr>';

		// Align (Alignement) (RIGHT)
		print '<tr><td>';
		print '<table class="nobordernopadding" width="100%"><tr><td>';
		print $langs->trans('Align');
		print '</td>';
		if ($action != 'editalign')
			print '<td align="right"><a href="' . $_SERVER["PHP_SELF"] . '?action=editalign&amp;id=' . $object->id . '">' . img_edit($langs->trans('SetAlign'), 1) . '</a></td>';
		print '</tr></table>';
		print '</td><td>';
		if ($action == 'editalign') {
            print '<form name="setalign" action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '" method="post">';
			print '<input type="hidden" name="token" value="' . $_SESSION ['newtoken'] . '">';
			print '<input type="hidden" name="action" value="setalign">';
            print '<select name="align" class="flat">';
            print '<option value="start" '.($object->align=='start'?'selected':'').'>Début</option>';
            print '<option value="center" '.($object->align=='center'?'selected':'').'>Centré</option>';
            print '<option value="end" '.($object->align=='end'?'selected':'').'>Fin</option>';
            print '</select>';
			print '<input type="submit" class="button" value="' . $langs->trans('Modify') . '">';
			print '</form>';
		} else {
            $lib_align = $object->align;
            if ($object->align == 'start') $lib_align = $langs->trans("Début");
            if ($object->align == 'center') $lib_align = $langs->trans("Centre");
            if ($object->align == 'end') $lib_align = $langs->trans("Fin");
			print $lib_align;
		}
        print '</td></tr>';
		
		// Progression (RIGHT)
		print '<tr><td>';
		print '<table class="nobordernopadding" width="100%"><tr><td>Affichage de la progression</td>';
		if ($action != 'editshow_progress') print '<td align="right"><a href="' . $_SERVER["PHP_SELF"] . '?action=editshow_progress&amp;id=' . $object->id . '">' . img_edit($langs->trans('Modify'), 1) . '</a></td>';
		print '</tr></table>';
		print '</td><td>';
		if ($action == 'editshow_progress') {
			print '<form action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '" method="post">';
			print '<input type="hidden" name="token" value="' . $_SESSION ['newtoken'] . '">';
			print '<input type="hidden" name="action" value="setshow_progress">';
			print $form->selectyesno('show_progress', $object->show_progress, 1);
			print '<input type="submit" class="button" value="' . $langs->trans('Modify') . '">';
			print '</form>';
		} else {
			print yn($object->show_progress);
		}
		print '</td></tr>';

		// Croix de fermeture (RIGHT)
		print '<tr><td>';
		print '<table class="nobordernopadding" width="100%"><tr><td>Autoriser à quitter le tour</td>';
		if ($action != 'editshow_cross') print '<td align="right"><a href="' . $_SERVER["PHP_SELF"] . '?action=editshow_cross&amp;id=' . $object->id . '">' . img_edit($langs->trans('Modify'), 1) . '</a></td>';
		print '</tr></table>';
		print '</td><td>';
		if ($action == 'editshow_cross') {
			print '<form action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '" method="post">';
			print '<input type="hidden" name="token" value="' . $_SESSION ['newtoken'] . '">';
			print '<input type="hidden" name="action" value="setshow_cross">';
			print $form->selectyesno('show_cross', $object->show_cross, 1);
			print '<input type="submit" class="button" value="' . $langs->trans('Modify') . '">';
			print '</form>';
		} else {
			print yn($object->show_cross);
		}
		print '</td></tr>';

		// Taille Police
		print '<tr><td>';
		print '<table class="nobordernopadding" width="100%"><tr><td>Taille de l\'écriture</td>';
		if ($action != 'editfont_size') print '<td align="right"><a href="' . $_SERVER["PHP_SELF"] . '?action=editfont_size&amp;id=' . $object->id . '">' . img_edit($langs->trans('Modify'), 1) . '</a></td>';
		print '</tr></table>';
		print '</td><td>';
		if ($action == 'editfont_size') {
			print '<form action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '" method="post">';
			print '<input type="hidden" name="token" value="' . $_SESSION ['newtoken'] . '">';
			print '<input type="hidden" name="action" value="setfont_size">';
			print '<input type="text" size="10" name="font_size" value="'.$object->font_size.'" placeholder="ex: 14px">';
			print '<input type="submit" class="button" value="' . $langs->trans('Modify') . '">';
			print '</form>';
		} else {
			print $object->font_size;
		}
		print '</td></tr>';
		
		// Police d'écriture (Font Family)
		print '<tr><td>';
		print '<table class="nobordernopadding" width="100%"><tr><td>';
		print $langs->trans('Police d\'écriture');
		print '</td>';
		
		if ($action != 'editfont_family') {
			print '<td align="right"><a href="' . $_SERVER["PHP_SELF"] . '?action=editfont_family&amp;id=' . $object->id . '">' . img_edit($langs->trans('Modify'), 1) . '</a></td>';
		}
		print '</tr></table>';
		
		print '</td><td>';
		
		if ($action == 'editfont_family') {
			// Liste déroulante
			print '<form action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '" method="post">';
			print '<input type="hidden" name="token" value="' . $_SESSION ['newtoken'] . '">';
			print '<input type="hidden" name="action" value="setfont_family">';
			
            // Liste des polices
			$fonts = array('Arial', 'Helvetica', 'Verdana', 'Times New Roman', 'Courier New', 'Roboto', 'Open Sans', 'Tahoma', 'Georgia', 'Trebuchet MS');
			
            print '<select name="font_family" class="flat">';
            print '<option value="">' . $langs->trans("Par défaut") . '</option>';
            
			foreach($fonts as $f) {
				$selected = ($object->font_family == $f ? 'selected' : '');
                // On applique le style visuel dans la liste
				print '<option value="'.$f.'" style="font-family: \''.$f.'\', sans-serif;" '.$selected.'>'.$f.'</option>';
			}
			print '</select>';
			print '<input type="submit" class="button" value="' . $langs->trans('Modify') . '">';
			print '</form>';
		} else {
			// Lecture
            if (!empty($object->font_family)) {
			    print '<span style="font-family:\''.$object->font_family.'\'">'.$object->font_family.'</span>';
            } else {
                print $langs->trans("Par défaut");
            }
		}
		print '</td></tr>';

		// Couleur Texte
		print '<tr><td>';
		print '<table class="nobordernopadding" width="100%"><tr><td>Couleur du texte</td>';
		if ($action != 'editfont_color') print '<td align="right"><a href="' . $_SERVER["PHP_SELF"] . '?action=editfont_color&amp;id=' . $object->id . '&amp;token='.newToken().'">' . img_edit($langs->trans('Modify'), 1) . '</a></td>';
		print '</tr></table>';
		print '</td><td>';
		if ($action == 'editfont_color') {
			print '<form method="post" action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '">';
			print '<input type="hidden" name="action" value="setfont_color">';
			print '<input type="hidden" name="token" value="' . newToken() . '">';
			print $formother->selectColor($object->font_color, "font_color", null, 1);
			print '<input type="submit" class="button" value="' . $langs->trans("Modify") . '">';
			print '</form>';
		} else {
			if ($object->font_color) print '<span style="display:inline-block; width:24px; height:14px; background-color:'.$object->font_color.'; vertical-align:middle; border:1px solid #aaa; margin-right:8px; border-radius:3px;"></span>' . $object->font_color;
		}
		print '</td></tr>';

		// Couleur Bulle (Background)
		print '<tr><td>';
		print '<table class="nobordernopadding" width="100%"><tr><td>Couleur du fond</td>';
		if ($action != 'editbackground_color') print '<td align="right"><a href="' . $_SERVER["PHP_SELF"] . '?action=editbackground_color&amp;id=' . $object->id . '&amp;token='.newToken().'">' . img_edit($langs->trans('Modify'), 1) . '</a></td>';
		print '</tr></table>';
		print '</td><td>';
		if ($action == 'editbackground_color') {
			print '<form method="post" action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '">';
			print '<input type="hidden" name="action" value="setbackground_color">';
			print '<input type="hidden" name="token" value="' . newToken() . '">';
			print $formother->selectColor($object->background_color, "background_color", null, 1);
			print '<input type="submit" class="button" value="' . $langs->trans("Modify") . '">';
			print '</form>';
		} else {
			if ($object->background_color) print '<span style="display:inline-block; width:24px; height:14px; background-color:'.$object->background_color.'; vertical-align:middle; border:1px solid #aaa; margin-right:8px; border-radius:3px;"></span>' . $object->background_color;
		}
		print '</td></tr>';

		// Couleur du fond (RIGHT)
		print '<tr><td>';
		print '<table class="nobordernopadding" width="100%"><tr><td>';
		print $langs->trans("Couleur du calque de recouvrement");
		print '</td>';
		if ($action != 'editcolor') {
			print '<td align="right"><a href="' . $_SERVER["PHP_SELF"] . '?action=editcolor&amp;id=' . $object->id . '&amp;token='.newToken().'">' . img_edit($langs->trans('SetColor'), 1) . '</a></td>';
		}
		print '</tr></table>';
		print '</td><td>';
		if ($action == 'editcolor') {
			print '<form method="post" action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '">';
			print '<input type="hidden" name="action" value="setcolor">';
			print '<input type="hidden" name="token" value="' . newToken() . '">';
			print $formother->selectColor($object->color, "color", null, 1);
			print '<input type="submit" class="button" value="' . $langs->trans("Modify") . '">';
			print '</form>';
		} else {
            if ($object->color) {
                print '<span style="display:inline-block; width:24px; height:14px; background-color:'.$object->color.'; vertical-align:middle; border:1px solid #aaa; margin-right:8px; border-radius:3px;"></span>';
                print $object->color;
            }
		}

		// Image (RIGHT)
		print '<tr><td class="titlefield">';
		print '<table class="nobordernopadding" width="100%"><tr><td>';
		print $langs->trans('Image');
		print '</td>';
		if ($action != 'editimage')
			print '<td align="right"><a href="' . $_SERVER["PHP_SELF"] . '?action=editimage&amp;id=' . $object->id . '">' . img_edit($langs->trans('Modify'), 1) . '</a></td>';
		print '</tr></table>';
		print '</td><td>';
		if ($action == 'editimage') {
			print '<form name="setimage" action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '" method="post" enctype="multipart/form-data">';
			print '<input type="hidden" name="token" value="' . $_SESSION ['newtoken'] . '">';
			print '<input type="hidden" name="action" value="setimage">';
			print '<input type="file" name="element">';
			print '<input type="submit" class="button" value="' . $langs->trans('Modify') . '">';
			print '</form>';
		
		} else {
			if ($object->image) {
				echo $object->image;
				print ' &nbsp; ';
				print '<a href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=delete_image&token='.newToken().'" title="'.$langs->trans("Delete").'">';
				print img_delete();
				print '</a>';
			}
			else echo '<span class="opacitymedium">Aucune image</span>';
		}
		print '</td></tr>';

		include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_view.tpl.php';

		print '</table>';
		print '</div>'; 
		print '</div>'; 
		print '</div>'; 

		print '<div class="clearboth"></div><br>';
		dol_fiche_end();

		dol_fiche_end();

		/*
		 * Buttons for actions
		 */

		print '<div class="tabsAction">';

		$parameters = array();
		$reshook = $hookmanager->executeHooks('addMoreActionsButtons', $parameters, $object, $action); // Note that $action and $object may have been
																									// modified by hook
		// modified by hook
		if (empty($reshook)) {
			// Delete 
			if ($user->rights->dolitour->creer) {
				print '<div class="inline-block divButAction"><a class="butAction" href="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&amp;action=clone&amp;token='.newToken().'">' . $langs->trans('CloneDolitour') . '</a></div>';
			}
			if ($user->rights->dolitour->modifier) {
				print '<div class="inline-block divButAction"><a class="butAction" href="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&amp;action=reset&amp;token='.newToken().'">Réinitialiser le style</a></div>';
			}
			if ($user->rights->dolitour->supprimer) {
				print '<div class="inline-block divButAction"><a class="butActionDelete" href="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&amp;action=delete&amp;token='.newToken().'">' . $langs->trans('DeleteDoliTour') . '</a></div>';
			}
		}

        print '</div>';

       
        print '<div class="fichecenter"><div class="fichehalfleft">';
        print '<a name="builddoc"></a>';

		// Show links to link elements
		$somethingshown = $form->showLinkedObjectBlock($object, '');

		print '</div><div class="fichehalfright"><div class="ficheaddleft">';

		// List of actions on element
		include_once DOL_DOCUMENT_ROOT . '/core/class/html.formactions.class.php';
		$formactions = new FormActions($db);
		$somethingshown = $formactions->showactions($object, 'dolitour', '', 1);

		print '</div></div></div>';
		
	}
}

// End of page
llxFooter();
$db->close();