<?php
/* Copyright (C) 2001-2005  Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2019  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2005       Marc Barilley / Ocebo   <marc@ocebo.com>
 * Copyright (C) 2005-2012  Regis Houssin           <regis.houssin@inodbox.com>
 * Copyright (C) 2012       Juanjo Menent           <jmenent@2byte.es>
 * Copyright (C) 2013       Christophe Battarel     <christophe.battarel@altairis.fr>
 * Copyright (C) 2013       Cédric Salvador         <csalvador@gpcsolutions.fr>
 * Copyright (C) 2015-2018  Frédéric France         <frederic.france@netlogic.fr>
 * Copyright (C) 2015       Marcos García           <marcosgdf@gmail.com>
 * Copyright (C) 2015       Jean-François Ferry     <jfefe@aternatik.fr>
 * Copyright (C) 2016       Ferran Marcet           <fmarcet@2byte.es>
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
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *	\file       htdocs/reponse/list.php
 *	\ingroup    reponse
 *	\brief      Page to list orders
 */
$res=@include("../main.inc.php");                   // For root directory
if (! $res) $res=@include("../../main.inc.php");    // For "custom" directory

require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';

dol_include_once("/reponse/class/reponse.class.php");

if (!empty($conf->questionnaire->enabled))
{
    dol_include_once("/questionnaire/class/questionnaire.class.php");
    dol_include_once("/questionnaire/class/html.form.questionnaire.class.php");
}

$langs->load("reponse@reponse");

$action = GETPOST('action','aZ09');
$massaction = GETPOST('massaction','alpha');
$confirm = GETPOST('confirm','alpha');
$toselect = GETPOST('toselect', 'array');
$contextpage = GETPOST('contextpage','aZ') ? GETPOST('contextpage','aZ') : 'reponselist';

$search_form = array();


$timelaps = GETPOST('timelaps', 'int');

$search_year = GETPOST("search_year","int");
$search_month = GETPOST("search_month","int");
$search_day = GETPOST("search_day","int");
$search_fk_questionnaire = GETPOST('search_fk_questionnaire','int');
$search_fk_soc = GETPOST("search_fk_soc","int");

$search_ref = GETPOST('search_ref','alpha')!='' ? GETPOST('search_ref','alpha') : GETPOST('sref','alpha');
$search_envoi_ar = GETPOST("search_envoi_ar",'int');

$sall = trim((GETPOST('search_all', 'alphanohtml')!='') ? GETPOST('search_all', 'alphanohtml') : GETPOST('sall', 'alphanohtml'));
$search_user_author_id = GETPOST('search_user_author_id','int');

$optioncss = GETPOST('optioncss','alpha');
$search_btn = GETPOST('button_search','alpha');
$search_remove_btn = GETPOST('button_removefilter','alpha');

// Security check
$id = GETPOST('id','int');
$result = restrictedArea($user, 'reponse', $id,'');

$diroutputmassaction = $conf->reponse->dir_output . '/temp/massgeneration/'.$user->id;

// Load variable for pagination
$limit = GETPOST('limit','int')?GETPOST('limit','int'):$conf->liste_limit;
$sortfield = GETPOST("sortfield",'alpha');
$sortorder = GETPOST("sortorder",'alpha');
$page = GETPOST("page",'int');
if (empty($page) || $page == -1 || !empty($search_btn) || !empty($search_remove_btn) || (empty($toselect) && $massaction === '0')) { $page = 0; }     // If $page is not defined, or '' or -1
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (! $sortfield) $sortfield='s.ref';
if (! $sortorder) $sortorder='DESC';

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$object = new Reponse($db);
$hookmanager->initHooks(array('reponselist'));
$extrafields = new ExtraFields($db);

// fetch optionals attributes and labels
$extralabels = $extrafields->fetch_name_optionals_label('reponse');
$search_array_options=$extrafields->getOptionalsFromPost($object->table_element,'','search_');

// Get emails fields
$fieldstosearchall = array();

// List of fields to search into when doing a "search in all"
$fieldstosearchall['s.ref'] = 'Ref';
$fieldstosearchall['soc.nom'] = 'Société';

$arrayfields=array(
	's.ref'=>array('label'=>$langs->trans("Ref"), 'checked'=>1),
	's.fk_questionnaire'=>array('label'=>$langs->trans("Form"), 'checked'=>1),
);
$arrayfields['s.fk_soc'] = array('label'=>$langs->trans("Company"), 'checked'=>1);
$arrayfields['s.datec'] = array('label'=>$langs->trans("DateCreation"), 'checked'=>1);
$arrayfields['s.user_author_id'] = array('label'=>$langs->trans("UserAuthor"), 'checked'=>1);
$arrayfields['s.envoi_ar'] = array('label'=>$langs->trans("EnvoiAr"), 'checked'=>1);
$arrayfields['s.tms'] = array('label'=>$langs->trans("DateModificationShort"), 'checked'=>0, 'position'=>500);

// Extra fields
if (isset($extrafields->attribute_label) && is_array($extrafields->attribute_label) && count($extrafields->attribute_label))
{
	foreach($extrafields->attribute_label as $key => $val)
	{
		if (! empty($extrafields->attribute_list[$key])) $arrayfields["ef.".$key]=array('label'=>$extrafields->attribute_label[$key], 'checked'=>(($extrafields->attribute_list[$key]<0)?0:1), 'position'=>$extrafields->attribute_pos[$key], 'enabled'=>(abs($extrafields->attribute_list[$key])!=3 && $extrafields->attribute_perms[$key]));
	}
}

/*
 * Actions
 */

$error = 0;

if (GETPOST('cancel','alpha')) { $action='list'; $massaction=''; }
if (! GETPOST('confirmmassaction','alpha')) { $massaction=''; }

$parameters=array('socid'=>'');
$reshook=$hookmanager->executeHooks('doActions',$parameters,$object,$action);    // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook))
{
	// Selection of new fields
	include DOL_DOCUMENT_ROOT.'/core/actions_changeselectedfields.inc.php';

	// Purge search criteria
	if (GETPOST('button_removefilter_x','alpha') || GETPOST('button_removefilter.x','alpha') || GETPOST('button_removefilter','alpha')) // All tests are required to be compatible with all browsers
	{
		$search_year = '';
		$search_month = '';
		$search_day = '';
		$search_ref = '';
		$search_fk_questionnaire = '';
        $search_fk_soc = '';
		$search_envoi_ar = -1;
		$search_user_author_id = '';

		$search_form = array();

		$toselect = '';
		$search_array_options = array();
	}
	if (GETPOST('button_removefilter_x','alpha') || GETPOST('button_removefilter.x','alpha') || GETPOST('button_removefilter','alpha')
	 || GETPOST('button_search_x','alpha') || GETPOST('button_search.x','alpha') || GETPOST('button_search','alpha'))
	{
		$massaction='';     // Protection to avoid mass action if we force a new search during a mass action confirmation
	}

	// Mass actions
	$objectclass = 'Reponse';
	$objectlabel = 'Reponses';
	$permtoread = $user->rights->reponse->lire;
	$permtodelete = $user->rights->reponse->supprimer;
	$permtomodify = $user->rights->reponse->modifier;


	$uploaddir = $conf->reponse->dir_output;
	$trigger_name='REPONSE_SENTBYMAIL';

	include DOL_DOCUMENT_ROOT.'/core/actions_massactions.inc.php';

	if (($massaction == 'confirm' || ($action == 'confirm' && $confirm == 'yes')) && $permtomodify)
	{
		$db->begin();
	
		$objecttmp = new Reponse($db);
		$nbok = 0;
		foreach($toselect as $toselectid)
		{
			$result=$objecttmp->fetch($toselectid);
			if ($result > 0)
			{	
				$result = $objecttmp->confirm($user);
	
				if ($result <= 0)
				{
					setEventMessages($objecttmp->error, $objecttmp->errors, 'errors');
					$error++;
					break;
				}
				else $nbok++;
			}
			else
			{
				setEventMessages($objecttmp->error, $objecttmp->errors, 'errors');
				$error++;
				break;
			}
		}
	
		if (! $error)
		{
			if ($nbok > 1) setEventMessages($langs->trans("RecordsConfirmed", $nbok), null, 'mesgs');
			else setEventMessages($langs->trans("RecordConfirmed", $nbok), null, 'mesgs');
			$db->commit();
		}
		else
		{
			$db->rollback();
		}
	}

    if (($massaction == 'location' || ($action == 'location' && $confirm == 'yes')) && $permtomodify)
    {
        $db->begin();

        $objecttmp = new Reponse($db);
        $nbok = 0;
        foreach($toselect as $toselectid)
        {
            $result=$objecttmp->fetch($toselectid);
            if ($result > 0)
            {
                if (count($objecttmp->lines)) {
                    foreach ($objecttmp->lines as $line) {

                        $isMap = $line->type == 'map';

                        if ($isMap) {
                            $base = $line->code;

                            if (!empty($line->value)) {
                                // Check if we need to update town, region, state or something else
                                list($lat, $lon) = explode(',', $line->value);
                                $objecttmp->update_location($lat, $lon, $base, false, 1);
                            }
                        }
                    }
                }

                $objecttmp->fetch_lines();

                if ($result <= 0) {
                    setEventMessages($objecttmp->error, $objecttmp->errors, 'errors');
                    $error++;
                    break;
                } else {
                    $nbok++;
                }
            }
            else
            {
                setEventMessages($objecttmp->error, $objecttmp->errors, 'errors');
                $error++;
                break;
            }
        }

        if (! $error)
        {
            if ($nbok > 1) setEventMessages($langs->trans("RecordsUpdated", $nbok), null, 'mesgs');
            else setEventMessages($langs->trans("RecordUpdated", $nbok), null, 'mesgs');
            $db->commit();
        }
        else
        {
            $db->rollback();
        }
    }

    if ($massaction == 'download')
	{
		$objecttmp = new Reponse($db);
		$files = array();
		foreach($toselect as $toselectid)
		{
			$result=$objecttmp->fetch($toselectid);
			if ($result > 0)
			{	
				$files[$objecttmp->ref] = $objecttmp->getAttachedFiles();
			}
			else
			{
				setEventMessages($objecttmp->error, $objecttmp->errors, 'errors');
				$error++;
				break;
			}
		}
		
		if (count($files) > 0)
		{
			$relpath = Reponse::createZipArchive($files, $diroutputmassaction);
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
}


/*
 * View
 */

$now=dol_now();

$form = new Form($db);
$formother = new FormOther($db);

$userstatic = new User($db);

$title = $langs->trans("Reponses");
$help_url = "";

$sql = 'SELECT DISTINCT';
if ($sall) $sql = 'SELECT DISTINCT';
$sql.= ' s.rowid, s.ref, s.fk_soc, s.datec, s.fk_questionnaire, s.tms, s.user_author_id, s.envoi_ar, s.entity, soc.nom';
$sql.= ", u.rowid as user_id, u.lastname as user_lastname, u.firstname as user_firstname";
if (!empty($conf->questionnaire->enabled))
{
	$sql.= ", f.rowid as form_id, f.ref as form_ref, f.title as form_title, f.icon as form_icon ";
}

// Add fields from extrafields
if (isset($extrafields->attribute_label)) {
    foreach ($extrafields->attribute_label as $key => $val) $sql.=($extrafields->attribute_type[$key] != 'separate' ? ",ef.".$key.' as options_'.$key : '');
}
// Add fields from hooks
$parameters=array();
$reshook=$hookmanager->executeHooks('printFieldListSelect',$parameters);    // Note that $action and $object may have been modified by hook
$sql.=$hookmanager->resPrint;
$sql.= ' FROM '.MAIN_DB_PREFIX.'reponse as s';
$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'user as u ON u.rowid = s.user_author_id';
$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'societe as soc ON soc.rowid = s.fk_soc';

if (isset($extrafields->attribute_label)) {
    if (is_array($extrafields->attribute_label) && count($extrafields->attribute_label)) $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."reponse_extrafields as ef on (s.rowid = ef.fk_object)";
}

if (!empty($conf->questionnaire->enabled))
{
	$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."questionnaire as f on f.rowid = s.fk_questionnaire";
}

$sql.= ' WHERE s.is_draft = 0 AND s.entity IN ('.getEntity('reponse').')';
if ($search_ref) $sql .= natural_search('s.ref', $search_ref);

if ($sall) $sql .= natural_search(array_keys($fieldstosearchall), $sall);
if ($search_envoi_ar != '' && $search_envoi_ar >= 0) $sql.=' AND s.envoi_ar = '.$search_envoi_ar;
if ($search_fk_questionnaire != '' && $search_fk_questionnaire >= 0) $sql.=' AND s.fk_questionnaire = '.$search_fk_questionnaire;
if ($search_fk_soc != '' && $search_fk_soc >= 0) $sql.=' AND s.fk_soc = '.$search_fk_soc;

if ($search_month > 0)
{
	if ($search_year > 0 && empty($search_day))
	$sql.= " AND s.datec BETWEEN '".$db->idate(dol_get_first_day($search_year, $search_month, false))."' AND '".$db->idate(dol_get_last_day($search_year, $search_month, false))."'";
	else if ($search_year > 0 && ! empty($search_day))
	$sql.= " AND s.datec BETWEEN '".$db->idate(dol_mktime(0, 0, 0, $search_month, $search_day, $search_year))."' AND '".$db->idate(dol_mktime(23, 59, 59, $search_month, $search_day, $search_year))."'";
	else
	$sql.= " AND date_format(s.datec, '%m') = '".$search_month."'";
}
else if ($search_year > 0)
{
	$sql.= " AND s.datec BETWEEN '".$db->idate(dol_get_first_day($search_year, 1, false))."' AND '".$db->idate(dol_get_last_day($search_year, 12, false))."'";
}

if (!$user->rights->reponse->readall) {
	$sql.= " AND s.user_author_id = " .$user->id;
} else {
	if ($search_user_author_id > 0) $sql.= " AND s.user_author_id = " .$search_user_author_id;
}


// Add where from extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_sql.tpl.php';
// Add where from hooks
$parameters=array();
$reshook=$hookmanager->executeHooks('printFieldListWhere',$parameters);    // Note that $action and $object may have been modified by hook
$sql.=$hookmanager->resPrint;

$sql.= " GROUP BY s.rowid ";

$sql.= $db->order($sortfield,$sortorder);

// Count total nb of records
$nbtotalofrecords = '';
if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST))
{
    $result = $db->query($sql);
    $nbtotalofrecords = $db->num_rows($result);

    if (($page * $limit) > $nbtotalofrecords)	// if total resultset is smaller then paging size (filtering), goto and load page 0
    {
        $page = 0;
        $offset = 0;
    }
}

$sql.= $db->plimit($limit + 1,$offset);

$title = $langs->trans('ListOfReponses');

$arrayofselected=is_array($toselect)?$toselect:array();

$resql = $db->query($sql);
$num = $db->num_rows($resql);

if ($num == 1 && ! empty($conf->global->MAIN_SEARCH_DIRECT_OPEN_IF_ONLY_ONE) && $sall)
{
    $obj = $db->fetch_object($resql);
    $id = $obj->rowid;
    $url = dol_buildpath('/reponse/card.php', 1).'?id='.$id;

    header("Location: ".$url);		exit;
}

llxHeader('',$title);

$param='&timelaps='.$timelaps;

if (! empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) $param.='&contextpage='.urlencode($contextpage);
if ($limit > 0 && $limit != $conf->liste_limit) $param.='&limit='.urlencode($limit);
if ($sall)					$param.='&sall='.urlencode($sall);

if ($search_day)      		$param.='&search_day='.urlencode($search_day);
if ($search_month)      		$param.='&search_month='.urlencode($search_month);
if ($search_year)       		$param.='&search_year='.urlencode($search_year);

if ($search_ref)      		$param.='&search_ref='.urlencode($search_ref);
if ($search_fk_questionnaire > 0)      		$param.='&search_fk_questionnaire='.urlencode($search_fk_questionnaire);

if ($search_user_author_id > 0) 		$param.='&search_user_author_id='.urlencode($search_user_author_id);
if ($search_envoi_ar != '') $param.='&search_envoi_ar='.urlencode($search_envoi_ar);

if ($optioncss != '')       $param.='&optioncss='.urlencode($optioncss);

// Add $param from extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_param.tpl.php';

// List of mass actions available
$arrayofmassactions =  array(
    'preconfirm'=>$langs->trans("MassActionEnvoiAR"),
    'prelocation' => $langs->trans("MassLocation")
);

if ($user->rights->reponse->telecharger) {
    $arrayofmassactions['download'] = $langs->trans("MassDownload");
}

if ($user->rights->reponse->supprimer) $arrayofmassactions['predelete']=$langs->trans("MassActionDelete");
if (in_array($massaction, array('preconfirm','predelete'))) $arrayofmassactions=array();
$massactionbutton=$form->selectMassAction('', $arrayofmassactions);

$newcardbutton='';
if ($contextpage == 'reponselist' && $user->rights->reponse->creer)
{
    $newcardbutton='<a class="butActionNew" href="'.dol_buildpath('/reponse/card.php?action=create', 2).'"><span class="valignmiddle">'.$langs->trans('NewReponse').'</span>';
    $newcardbutton.= '<span class="fa fa-plus-circle valignmiddle"></span>';
    $newcardbutton.= '</a>';
}

// Lines of title fields
print '<form method="POST" id="searchFormList" action="'.$_SERVER["PHP_SELF"].'">';
if ($optioncss != '') print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
print '<input type="hidden" name="action" value="list">';
print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
print '<input type="hidden" name="page" value="'.$page.'">';
print '<input type="hidden" name="contextpage" value="'.$contextpage.'">';

print_barre_liste($title, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, $massactionbutton, $num, $nbtotalofrecords, 'reponse@reponse', 0, $newcardbutton, '', $limit);

$topicmail = "SendReponseRef";
$modelmail = "reponse_send";
$objecttmp = new Reponse($db);
$trackid = 'rep'.$object->id;
include DOL_DOCUMENT_ROOT.'/core/tpl/massactions_pre.tpl.php';

if ($massaction == 'premerge')
{
    print $form->formconfirm($_SERVER["PHP_SELF"].'?search_duplicates=1', $langs->trans("ConfirmMassMerge"), $langs->trans("ConfirmMassMergeQuestion"), "merge", null, '', 0, 200, 500, 1);
}

if ($massaction == 'preconfirm')
{
    print $form->formconfirm($_SERVER["PHP_SELF"], $langs->trans("ConfirmMassConfirmation"), $langs->trans("ConfirmMassConfirmationQuestion", count($toselect)), "confirm", null, '', 0, 200, 500, 1);
}

if ($massaction == 'prelocation' && !empty($conf->echantillon->enabled))
{
    print $form->formconfirm($_SERVER["PHP_SELF"], $langs->trans("ConfirmMassLocation"), $langs->trans("ConfirmMassLocationQuestion", count($toselect)), "location", '', '', 0, 200, 500, 1);
}

if ($sall)
{
    foreach($fieldstosearchall as $key => $val) $fieldstosearchall[$key]=$langs->trans($val);
    print '<div class="divsearchfieldfilter">'.$langs->trans("FilterOnInto", $sall) . join(', ',$fieldstosearchall).'</div>';
}

$moreforfilter='';

// If the user can view other users
if ($user->rights->reponse->readall)
{
    $moreforfilter.='<div class="divsearchfield">';
    $moreforfilter.=$langs->trans('CreateByUsers'). ': ';
    $moreforfilter.=$form->select_dolusers($search_user_author_id, 'search_user_author_id', 1, '', 0, '', '', 0, 0, 0, '', 0, '', 'maxwidth200');
    $moreforfilter.='</div>';
}

$parameters=array();
$reshook=$hookmanager->executeHooks('printFieldPreListTitle',$parameters);    // Note that $action and $object may have been modified by hook
if (empty($reshook)) $moreforfilter .= $hookmanager->resPrint;
else $moreforfilter = $hookmanager->resPrint;

if (! empty($moreforfilter))
{
    print '<div class="liste_titre liste_titre_bydiv centpercent">';
    print $moreforfilter;
    print '</div>';
}

$varpage=empty($contextpage)?$_SERVER["PHP_SELF"]:$contextpage;
$selectedfields=$form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage);	// This also change content of $arrayfields
$selectedfields.=$form->showCheckAddButtons('checkforselect', 1);

print '<div class="div-table-responsive">';
print '<table class="tagtable liste'.($moreforfilter?" listwithfilterbefore":"").'">'."\n";

print '<tr class="liste_titre_filter">';

// Ref
if (! empty($arrayfields['s.ref']['checked']))
{
    print '<td class="liste_titre">';
    print '<input class="flat" size="6" type="text" name="search_ref" value="'.$search_ref.'">';
    print '</td>';
}

// Humain
if (! empty($arrayfields['s.fk_questionnaire']['checked']) && !empty($conf->questionnaire->enabled))
{
    $questionnaire = new Questionnaire($db);
    $questionnaires = $questionnaire->liste_array();

    print '<td class="liste_titre">';
    print $form->selectarray('search_fk_questionnaire', $questionnaires, $search_fk_questionnaire, 1, 0, 0, '', 0, 0, 0, '', '', 1);
    print '</td>';
}

if (! empty($arrayfields['s.fk_soc']['checked']))
{
    print '<td class="liste_titre">';
    print $form->select_company($search_fk_soc, 'search_fk_soc', '', 1);
    print '</td>';
}

// Date
if (! empty($arrayfields['s.datec']['checked']))
{
    print '<td class="liste_titre nowraponall" align="left">';
    if (! empty($conf->global->MAIN_LIST_FILTER_ON_DAY)) print '<input class="flat width25 valignmiddle" type="text" maxlength="2" name="search_day" value="'.$search_day.'">';
    print '<input class="flat width25 valignmiddle" type="text" maxlength="2" name="search_month" value="'.$search_month.'">';
    $formother->select_year($search_year?$search_year:-1,'search_year',1, 20, 5);
    print '</td>';
}

// Auteur
if (! empty($arrayfields['s.user_author_id']['checked']))
{
    print '<td class="liste_titre nowraponall" align="left">';
    print '&nbsp;';
    print '</td>';
}
// Extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_input.tpl.php';
// Fields from hook
$parameters=array('arrayfields'=>$arrayfields);
$reshook=$hookmanager->executeHooks('printFieldListOption',$parameters);    // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;

// Status sent
if (! empty($arrayfields['s.envoi_ar']['checked']))
{
    print '<td class="liste_titre maxwidthonsmartphone" align="left">';
    print $form->selectyesno('search_envoi_ar', $search_envoi_ar, 1, 0, 1);
    print '</td>';
}

// Date modification
if (! empty($arrayfields['s.tms']['checked']))
{
    print '<td class="liste_titre">';
    print '</td>';
}


// Action column
print '<td class="liste_titre" align="middle">';
$searchpicto=$form->showFilterButtons();
print $searchpicto;
print '</td>';

print "</tr>\n";

// Fields title
print '<tr class="liste_titre">';
if (! empty($arrayfields['s.ref']['checked']))            print_liste_field_titre($arrayfields['s.ref']['label'],$_SERVER["PHP_SELF"],'s.ref','',$param,'',$sortfield,$sortorder);
if (! empty($arrayfields['s.fk_questionnaire']['checked']))            print_liste_field_titre($arrayfields['s.fk_questionnaire']['label'],$_SERVER["PHP_SELF"],'s.fk_questionnaire','',$param,'',$sortfield,$sortorder);
if (! empty($arrayfields['s.fk_soc']['checked']))            print_liste_field_titre($arrayfields['s.fk_soc']['label'],$_SERVER["PHP_SELF"],'s.fk_soc','',$param,'',$sortfield,$sortorder);
if (! empty($arrayfields['s.datec']['checked']))     print_liste_field_titre($arrayfields['s.datec']['label'],$_SERVER["PHP_SELF"],'s.datec','',$param,'',$sortfield,$sortorder);
if (! empty($arrayfields['s.user_author_id']['checked']))     print_liste_field_titre($arrayfields['s.user_author_id']['label'],$_SERVER["PHP_SELF"],'s.datec','',$param,'',$sortfield,$sortorder);

// Extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_title.tpl.php';
// Hook fields
$parameters=array('arrayfields'=>$arrayfields,'param'=>$param,'sortfield'=>$sortfield,'sortorder'=>$sortorder);
$reshook=$hookmanager->executeHooks('printFieldListTitle',$parameters);    // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;

if (! empty($arrayfields['s.envoi_ar']['checked']))   print_liste_field_titre($arrayfields['s.envoi_ar']['label'],$_SERVER["PHP_SELF"],'s.envoi_ar','',$param,'align="left"',$sortfield,$sortorder,'');
if (! empty($arrayfields['s.tms']['checked']))       print_liste_field_titre($arrayfields['s.tms']['label'],$_SERVER["PHP_SELF"],"s.tms","",$param,'align="left" class="nowrap"',$sortfield,$sortorder);
print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"],"",'',$param,'align="center"',$sortfield,$sortorder,'maxwidthsearch ');
print '</tr>'."\n";

$productstat_cache=array();

$generic_reponse = new Reponse($db);
$generic_user = new User($db);

$i=0;
$totalarray=array('nbfield' => 0);

while ($i < min($num,$limit))
{
    $obj = $db->fetch_object($resql);

    $generic_reponse->id = $obj->rowid;
    $generic_reponse->ref = $obj->ref;
    $generic_reponse->datec = $db->jdate($obj->datec);
    $generic_reponse->fk_soc = $obj->fk_soc;
    $generic_reponse->icon = $obj->form_icon;


    $generic_user->id = $obj->user_id;
    $generic_user->firstname = $obj->user_firstname;
    $generic_user->lastname = $obj->user_lastname;


    print '<tr class="oddeven">';


    // Ref
    if (! empty($arrayfields['s.ref']['checked']))
    {
        print '<td class="nowrap">';

        print $generic_reponse->getNomUrl(1);

        print '</td>';
        if (! $i) $totalarray['nbfield']++;
    }

    //
    if (! empty($arrayfields['s.fk_questionnaire']['checked']) && !empty($conf->questionnaire->enabled))
    {
        $questionnaire = new Questionnaire($db);

        $questionnaire->id = $obj->form_id;
        $questionnaire->ref = $obj->form_ref;
        $questionnaire->title = $obj->form_title;

        print '<td align="left">'.$questionnaire->getNomUrl(1).'</td>';
        if (! $i) $totalarray['nbfield']++;
    }

    if (! empty($arrayfields['s.fk_soc']['checked']))
    {
        $generic_reponse->fetch_thirdparty();

        print '<td align="left">'.($generic_reponse->fk_soc > 0 ? $generic_reponse->thirdparty->getNomUrl(1) : '').'</td>';

        if (! $i) $totalarray['nbfield']++;
    }

    // Order date
    if (! empty($arrayfields['s.datec']['checked']))
    {
        print '<td align="left">';
        print dol_print_date($db->jdate($obj->datec), 'day');
        print '</td>';
        if (! $i) $totalarray['nbfield']++;
    }

    // Auteur
    if (! empty($arrayfields['s.user_author_id']['checked']))
    {
        print '<td align="left">';
        print $obj->user_author_id > 0 ? $generic_user->getNomUrl(1) : '&nbsp;';
        print '</td>';
    }


    // Extra fields
    include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_print_fields.tpl.php';
    // Fields from hook
    $parameters=array('arrayfields'=>$arrayfields, 'obj'=>$obj);
    $reshook=$hookmanager->executeHooks('printFieldListValue',$parameters);    // Note that $action and $object may have been modified by hook
    print $hookmanager->resPrint;


    //
    if (! empty($arrayfields['s.envoi_ar']['checked']))
    {
        print '<td align="left">'.yn($obj->envoi_ar).'</td>';
        if (! $i) $totalarray['nbfield']++;
    }

    // Date modification
    if (! empty($arrayfields['s.tms']['checked']))
    {
        print '<td align="left" class="nowrap">';
        print dol_print_date($db->jdate($obj->tms), 'dayhour', 'tzuser');
        print '</td>';
        if (! $i) $totalarray['nbfield']++;
    }


    // Action column
    print '<td class="nowrap" align="center">';
    if ($massactionbutton || $massaction)   // If we are in select mode (massactionbutton defined) or if we have already selected and sent an action ($massaction) defined
    {
        $selected=0;
        if (in_array($obj->rowid, $arrayofselected)) $selected=1;
        print '<input id="cb'.$obj->rowid.'" class="flat checkforselect" type="checkbox" name="toselect[]" value="'.$obj->rowid.'"'.($selected?' checked="checked"':'').'>';
    }
    print '</td>';
    if (! $i) $totalarray['nbfield']++;


    print "</tr>\n";

    $i++;
}

$db->free($resql);

$parameters=array('arrayfields'=>$arrayfields, 'sql'=>$sql);
$reshook=$hookmanager->executeHooks('printFieldListFooter',$parameters);    // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;

print '</table>'."\n";
print '</div>';

print '</form>'."\n";

// End of page
llxFooter();
$db->close();
