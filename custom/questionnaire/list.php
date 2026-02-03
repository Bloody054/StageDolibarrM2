<?php
/* 
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
 *	\file       htdocs/questionnaire/list.php
 *	\ingroup    questionnaire
 *	\brief      Page to list orders
 */


$res=@include("../main.inc.php");                   // For root directory
if (! $res) $res=@include("../../main.inc.php");    // For "custom" directory

require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';

dol_include_once("/questionnaire/class/questionnaire.class.php");
dol_include_once("/questionnaire/class/html.form.questionnaire.class.php");


$langs->load("questionnaire@questionnaire");

$action = GETPOST('action','aZ09');
$massaction = GETPOST('massaction','aZ09');
$confirm = GETPOST('confirm','alpha');
$toselect = GETPOST('toselect', 'array');
$contextpage = GETPOST('contextpage','aZ') ? GETPOST('contextpage','aZ') : 'questionnairelist';


$search_dyear = GETPOST("search_dyear","int");
$search_dmonth = GETPOST("search_dmonth","int");
$search_dday = GETPOST("search_dday","int");


$search_ref = GETPOST('search_ref','alpha')!='' ? GETPOST('search_ref','alpha') : GETPOST('sref','alpha');

$search_user_author_id = GETPOST('search_user_author_id','int');

$search_active = isset($_POST['search_active']) ? GETPOST('search_active','int') : -1;
$search_selected = isset($_POST['search_selected']) ? GETPOST('search_selected','int') : -1;

$search_title = GETPOST('search_title','alpha');

$sall = trim((GETPOST('search_all', 'alphanohtml')!='') ? GETPOST('search_all', 'alphanohtml') : GETPOST('sall', 'alphanohtml'));



$optioncss = GETPOST('optioncss','alpha');
$search_btn = GETPOST('button_search','alpha');
$search_remove_btn = GETPOST('button_removefilter','alpha');

// Security check
$id = GETPOST('id','int');
$result = restrictedArea($user, 'questionnaire', $id,'');

$diroutputmassaction = $conf->questionnaire->dir_output . '/temp/massgeneration/'.$user->id;

// Load variable for pagination
$limit = GETPOST('limit','int')?GETPOST('limit','int'):$conf->liste_limit;
$sortfield = GETPOST("sortfield",'alpha');
$sortorder = GETPOST("sortorder",'alpha');
$page = GETPOST("page",'int');
if (empty($page) || $page == -1 || !empty($search_btn) || !empty($search_remove_btn) || (empty($toselect) && $massaction === '0')) { $page = 0; }     // If $page is not defined, or '' or -1
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (! $sortfield) $sortfield='e.ref';
if (! $sortorder) $sortorder='DESC';

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$object = new Questionnaire($db);
$hookmanager->initHooks(array('questionnairelist'));
$extrafields = new ExtraFields($db);

// fetch optionals attributes and labels
$extralabels = $extrafields->fetch_name_optionals_label('questionnaire');
$search_array_options=$extrafields->getOptionalsFromPost($object->table_element,'','search_');

// List of fields to search into when doing a "search in all"
$fieldstosearchall = array(
	'e.ref'=>'Ref',
	'e.title'=>'Title',
);

$arrayfields=array(
	'e.ref'=>array('label'=>$langs->trans("Ref"), 'checked'=>1),
	'e.title'=>array('label'=>$langs->trans("Title"), 'checked'=>1),
	'e.active'=>array('label'=>$langs->trans("Visible"), 'checked'=>1),
	'e.selected'=>array('label'=>$langs->trans("Selected"), 'checked'=>1),
	'e.datec'=>array('label'=>$langs->trans("DateCreation"), 'checked'=>1),
	'e.tms'=>array('label'=>$langs->trans("DateModificationShort"), 'checked'=>0, 'position'=>500),
);

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
		$search_dyear = '';
		$search_dmonth = '';
		$search_dday = '';

		$search_active = '';
		$search_title = '';
		$search_selected = '';

        $search_ref = '';
		$search_user_author_id = '';

		$toselect = '';
		$search_array_options = array();
	}
	if (GETPOST('button_removefilter_x','alpha') || GETPOST('button_removefilter.x','alpha') || GETPOST('button_removefilter','alpha')
	 || GETPOST('button_search_x','alpha') || GETPOST('button_search.x','alpha') || GETPOST('button_search','alpha'))
	{
		$massaction='';     // Protection to avoid mass action if we force a new search during a mass action confirmation
	}

	// Mass actions
	$objectclass = 'Questionnaire';
	$objectlabel = 'Questionnaires';
	$permtoread = $user->rights->questionnaire->lire;
	$permtodelete = $user->rights->questionnaire->supprimer;
	$permtomodify = $user->rights->questionnaire->modifier;
	$uploaddir = $conf->questionnaire->dir_output;
	$trigger_name='QUESTIONNAIRE_SENTBYMAIL';
	include DOL_DOCUMENT_ROOT.'/core/actions_massactions.inc.php';	
}


/*
 * View
 */

$now=dol_now();

$form = new Form($db);
$formother = new FormOther($db);
$questionnaireform = new QuestionnaireForm($db);

$userstatic = new User($db);

$title = $langs->trans("Questionnaires");
$help_url = "";

$sql = 'SELECT';
if ($sall) $sql = 'SELECT DISTINCT';
$sql.= " e.rowid, e.ref, e.datec, e.title, e.user_author_id, e.active, e.selected, e.entity, e.tms";

// Add fields from extrafields
if (isset($extrafields->attribute_label)) {
    foreach ($extrafields->attribute_label as $key => $val) $sql.=($extrafields->attribute_type[$key] != 'separate' ? ",ef.".$key.' as options_'.$key : '');
}
// Add fields from hooks
$parameters=array();
$reshook=$hookmanager->executeHooks('printFieldListSelect',$parameters);    // Note that $action and $object may have been modified by hook
$sql.=$hookmanager->resPrint;
$sql.= ' FROM '.MAIN_DB_PREFIX.'questionnaire as e';

if (isset($extrafields->attribute_label)) {
    if (is_array($extrafields->attribute_label) && count($extrafields->attribute_label)) $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."questionnaire_extrafields as ef on (e.rowid = ef.fk_reponse)";
}
$sql.= ' WHERE e.entity IN ('.getEntity('questionnaire').')';
if ($search_ref) $sql .= natural_search('e.ref', $search_ref);
if ($search_title) $sql .= natural_search('e.title', $search_title);

if ($sall) $sql .= natural_search(array_keys($fieldstosearchall), $sall);

if ($search_dmonth > 0)
{
	if ($search_dyear > 0 && empty($search_dday))
	$sql.= " AND e.datec BETWEEN '".$db->idate(dol_get_first_day($search_dyear, $search_dmonth, false))."' AND '".$db->idate(dol_get_last_day($search_dyear, $search_dmonth, false))."'";
	else if ($search_dyear > 0 && ! empty($search_dday))
	$sql.= " AND e.datec BETWEEN '".$db->idate(dol_mktime(0, 0, 0, $search_dmonth, $search_dday, $search_dyear))."' AND '".$db->idate(dol_mktime(23, 59, 59, $search_dmonth, $search_dday, $search_dyear))."'";
	else
	$sql.= " AND date_format(e.datec, '%m') = '".$search_dmonth."'";
}
else if ($search_dyear > 0)
{
	$sql.= " AND e.datec BETWEEN '".$db->idate(dol_get_first_day($search_dyear, 1, false))."' AND '".$db->idate(dol_get_last_day($search_dyear, 12, false))."'";
}

if ($search_user_author_id > 0) $sql.= " AND e.user_author_id = " .$search_user_author_id;
if ($search_active >= 0) $sql.= " AND e.active = " .$search_active;
if ($search_selected >= 0) $sql.= " AND e.selected = " .$search_selected;

// Add where from extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_sql.tpl.php';
// Add where from hooks
$parameters=array();
$reshook=$hookmanager->executeHooks('printFieldListWhere',$parameters);    // Note that $action and $object may have been modified by hook
$sql.=$hookmanager->resPrint;

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
//print $sql;

$resql = $db->query($sql);
if ($resql)
{
	$title = $langs->trans('ListOfQuestionnaires');

	$num = $db->num_rows($resql);

	$arrayofselected=is_array($toselect)?$toselect:array();

	if ($num == 1 && ! empty($conf->global->MAIN_SEARCH_DIRECT_OPEN_IF_ONLY_ONE) && $sall)
	{
		$obj = $db->fetch_object($resql);
		$id = $obj->rowid;
		$url = dol_buildpath('/questionnaire/card.php', 1).'?id='.$id;

		header("Location: ".$url);
		exit;
	}

	llxHeader('',$title,$help_url);

	$param='';

	if (! empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) $param.='&contextpage='.urlencode($contextpage);
	if ($limit > 0 && $limit != $conf->liste_limit) $param.='&limit='.urlencode($limit);
	if ($sall)					$param.='&sall='.urlencode($sall);

	if ($search_dday)      		$param.='&search_dday='.urlencode($search_dday);
	if ($search_dmonth)      		$param.='&search_dmonth='.urlencode($search_dmonth);
	if ($search_dyear)       		$param.='&search_dyear='.urlencode($search_dyear);

	if ($search_ref)      		$param.='&search_ref='.urlencode($search_ref);

	if ($search_title)      $param.='&search_title='.urlencode($search_title);
	if ($search_active >= 0) 		$param.='&search_active='.urlencode($search_active);

	if ($search_user_author_id > 0) 		$param.='&search_user_author_id='.urlencode($search_user_author_id);

	if ($optioncss != '')       $param.='&optioncss='.urlencode($optioncss);

	// Add $param from extra fields
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_param.tpl.php';

	// List of mass actions available
	$arrayofmassactions =  array(
	);
	
	if ($user->rights->questionnaire->supprimer) $arrayofmassactions['predelete']=$langs->trans("MassActionDelete");
	if (in_array($massaction, array('predelete'))) $arrayofmassactions=array();
	$massactionbutton=$form->selectMassAction('', $arrayofmassactions);

	$newcardbutton='';
	if ($contextpage == 'questionnairelist' && $user->rights->questionnaire->creer)
	{
		$newcardbutton='<a class="butActionNew" href="'.dol_buildpath('/questionnaire/card.php?action=create', 2).'"><span class="valignmiddle">'.$langs->trans('NewQuestionnaire').'</span>';
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


	print_barre_liste($title, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, $massactionbutton, $num, $nbtotalofrecords, 'questionnaire@questionnaire', 0, $newcardbutton, '', $limit);

	$topicmail = "SendQuestionnaireRef";
	$modelmail = "questionnaire_send";
	$objecttmp = new Questionnaire($db);
	$trackid = 'sig'.$object->id;
	include DOL_DOCUMENT_ROOT.'/core/tpl/massactions_pre.tpl.php';
	
	if ($sall)
	{
		foreach($fieldstosearchall as $key => $val) $fieldstosearchall[$key]=$langs->trans($val);
		print '<div class="divsearchfieldfilter">'.$langs->trans("FilterOnInto", $sall) . join(', ',$fieldstosearchall).'</div>';
	}

	$moreforfilter='';

	// If the user can view other users
	if ($user->rights->user->user->lire)
	{
		$moreforfilter.='<div class="divsearchfield">';
		$moreforfilter.=$langs->trans('CreatedByUsers'). ': ';
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
	
	// Public URL
	if (! empty($arrayfields['e.ref']['checked']))
	{
		print '<td class="liste_titre">';
		print '</td>';
	}

	// Ref
	if (! empty($arrayfields['e.ref']['checked']))
	{
		print '<td class="liste_titre">';
		print '<input class="flat" size="6" type="text" name="search_ref" value="'.$search_ref.'">';
		print '</td>';
	}

	// Titre
	if (! empty($arrayfields['e.title']['checked']))
	{
		print '<td class="liste_titre">';
		print '<input class="flat" size="6" type="text" name="search_title" value="'.$search_title.'">';
		print '</td>';
	}

	// Actif
	if (! empty($arrayfields['e.active']['checked']))
	{
		print '<td class="liste_titre">';
		print $form->selectyesno('search_active', GETPOST('search_active'), 1, false, 1);
		print '</td>';
	}

	// Défaut
	if (! empty($arrayfields['e.selected']['checked']))
	{
		print '<td class="liste_titre">';
		print $form->selectyesno('search_selected', GETPOST('search_selected'), 1, false, 1);
		print '</td>';
	}

	// Extra fields
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_input.tpl.php';
	// Fields from hook
	$parameters=array('arrayfields'=>$arrayfields);
	$reshook=$hookmanager->executeHooks('printFieldListOption',$parameters);    // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;

	// Date de saisie
	if (! empty($arrayfields['e.datec']['checked']))
	{
		print '<td class="liste_titre nowraponall" align="left">';
		if (! empty($conf->global->MAIN_LIST_FILTER_ON_DAY)) print '<input class="flat width25 valignmiddle" type="text" maxlength="2" name="search_dday" value="'.$search_dday.'">';
		print '<input class="flat width25 valignmiddle" type="text" maxlength="2" name="search_dmonth" value="'.$search_dmonth.'">';
		$formother->select_year($search_dyear?$search_dyear:-1,'search_dyear',1, 20, 5);
		print '</td>';
	}

	// Date modification
	if (! empty($arrayfields['e.tms']['checked']))
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
	if (! empty($arrayfields['e.ref']['checked']))            print_liste_field_titre($langs->trans('PublicURL'),$_SERVER["PHP_SELF"],'e.ref','',$param,'',$sortfield,$sortorder);
	if (! empty($arrayfields['e.ref']['checked']))            print_liste_field_titre($arrayfields['e.ref']['label'],$_SERVER["PHP_SELF"],'e.ref','',$param,'',$sortfield,$sortorder);
	if (! empty($arrayfields['e.title']['checked']))      print_liste_field_titre($arrayfields['e.title']['label'],$_SERVER["PHP_SELF"],'e.title','',$param,'',$sortfield,$sortorder);
	if (! empty($arrayfields['e.active']['checked']))        print_liste_field_titre($arrayfields['e.active']['label'],$_SERVER["PHP_SELF"],'e.active','',$param,'',$sortfield,$sortorder);
	if (! empty($arrayfields['e.selected']['checked']))        print_liste_field_titre($arrayfields['e.selected']['label'],$_SERVER["PHP_SELF"],'e.selected','',$param,'',$sortfield,$sortorder);

	// Extra fields
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_title.tpl.php';
	// Hook fields
	$parameters=array('arrayfields'=>$arrayfields,'param'=>$param,'sortfield'=>$sortfield,'sortorder'=>$sortorder);
	$reshook=$hookmanager->executeHooks('printFieldListTitle',$parameters);    // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;

	if (! empty($arrayfields['e.datec']['checked']))     print_liste_field_titre($arrayfields['e.datec']['label'],$_SERVER["PHP_SELF"],'e.datec','',$param,'',$sortfield,$sortorder);
	if (! empty($arrayfields['e.tms']['checked']))       print_liste_field_titre($arrayfields['e.tms']['label'],$_SERVER["PHP_SELF"],"e.tms","",$param,'align="left" class="nowrap"',$sortfield,$sortorder);

	print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"],"",'',$param,'align="center"',$sortfield,$sortorder,'maxwidthsearch ');
	print '</tr>'."\n";

	$productstat_cache=array();

	$generic_questionnaire = new Questionnaire($db);
	$generic_user = new User($db);


	$i=0;
	$totalarray=array('nbfield' => 0);
	while ($i < min($num,$limit))
	{
		$obj = $db->fetch_object($resql);


		$generic_questionnaire->id = $obj->rowid;
		$generic_questionnaire->ref = $obj->ref;
		$generic_questionnaire->title = $obj->title;
		$generic_questionnaire->datec = $db->jdate($obj->datec);
		$generic_questionnaire->entity = $obj->entity;
		$pathtoform = $conf->global->REPONSE_ROOT_URL . '/report.php?entity='.$generic_questionnaire->entity.'&action=create&form-ref='.$generic_questionnaire->ref;
		
		
		print '<tr class="oddeven">';

		// Public URL
		if (! empty($arrayfields['e.ref']['checked']))
		{
			print '<td class="nowrap">';
			print showValueWithClipboardCPButton($pathtoform,1,$langs->trans('PublicURL'));
			print '</td>';
			if (! $i) $totalarray['nbfield']++;
		}

		// Ref
		if (! empty($arrayfields['e.ref']['checked']))
		{
			print '<td class="nowrap">';
			print $generic_questionnaire->getNomUrl(1);
			print '</td>';
			if (! $i) $totalarray['nbfield']++;
		}

		// 
		if (! empty($arrayfields['e.title']['checked']))
		{
			print '<td align="left">';
			print $obj->title;
			print '</td>';
			if (! $i) $totalarray['nbfield']++;
		}

		// 
		if (! empty($arrayfields['e.active']['checked']))
		{
			print '<td align="left">';
			print yn($obj->active);
			print '</td>';
			if (! $i) $totalarray['nbfield']++;
		}

		// 
		if (! empty($arrayfields['e.selected']['checked']))
		{
			print '<td align="left">';
			print yn($obj->selected);
			print '</td>';
			if (! $i) $totalarray['nbfield']++;
		}

		// Extra fields
		include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_print_fields.tpl.php';
		// Fields from hook
		$parameters=array('arrayfields'=>$arrayfields, 'obj'=>$obj);
		$reshook=$hookmanager->executeHooks('printFieldListValue',$parameters);    // Note that $action and $object may have been modified by hook
		print $hookmanager->resPrint;

		// 
		if (! empty($arrayfields['e.datec']['checked']))
		{
			print '<td align="left">';
			print dol_print_date($db->jdate($obj->datec), 'day');
			print '</td>';
			if (! $i) $totalarray['nbfield']++;
		}

		// Date modification
		if (! empty($arrayfields['e.tms']['checked']))
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

}
else
{
	dol_print_error($db);
}

// End of page
llxFooter();
$db->close();
