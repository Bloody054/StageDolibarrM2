<?php
/* *
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
 *	\file       htdocs/dolitour/list.php
 *	\ingroup    dolitour
 *	\brief      Page to list orders
 */

$res=@include("../main.inc.php");                   // For root directory
if (! $res) $res=@include("../../main.inc.php");    // For "custom" directory

require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php';
include_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';

dol_include_once("/dolitour/class/dolitour.class.php");
dol_include_once("/dolitour/class/html_dolitour.class.php");

$langs->load("dolitour@dolitour");

$action = GETPOST('action','aZ09');
$massaction = GETPOST('massaction','alpha');
$confirm = GETPOST('confirm','alpha');
$toselect = GETPOST('toselect', 'array');
$contextpage = GETPOST('contextpage','aZ') ? GETPOST('contextpage','aZ') : 'dolitourlist';
$search_dyear = GETPOST("search_dyear","int");
$search_dmonth = GETPOST("search_dmonth","int");
$search_dday = GETPOST("search_dday","int");
$search_syear = GETPOST("search_syear","int");
$search_smonth = GETPOST("search_smonth","int");
$search_sday = GETPOST("search_sday","int");
$search_eyear = GETPOST("search_eyear","int");
$search_emonth = GETPOST("search_emonth","int");
$search_eday = GETPOST("search_eday","int");
$search_ref = GETPOST('search_ref','alpha')!='' ? GETPOST('search_ref','alpha') : GETPOST('sref','alpha');
$search_rank = GETPOST('search_rank','alpha')!='' ? GETPOST('search_rank','alpha') : GETPOST('srank','alpha');
$search_user_author_id = GETPOST('search_user_author_id','int');
$search_title = GETPOST('search_title');
$search_description = GETPOST('search_description');
$search_image = GETPOST('search_image');
$search_active = isset($_POST['search_active']) ? GETPOST('search_active','int') : -1;
$search_side = GETPOST('search_side', 'alpha');
$search_align = GETPOST('search_align', 'alpha');
$search_font_family = GETPOST('search_font_family', 'alpha');
$search_font_size = GETPOST('search_font_size', 'alpha');
$search_play_once = GETPOST('search_play_once', 'alpha');
$search_show_progress = GETPOST('search_show_progress', 'alpha');
$search_show_cross = GETPOST('search_show_cross', 'alpha');
$search_myear = GETPOST("search_myear","int");
$search_mmonth = GETPOST("search_mmonth","int");
$search_mday = GETPOST("search_mday","int");
$search_fk_user_group = GETPOST('search_fk_user_group', 'int');
$search_color         = GETPOST('search_color', 'alpha');
$search_context       = GETPOST('search_context', 'alpha');
$search_url           = GETPOST('search_url', 'alpha'); 
$search_elementtoselect = GETPOST('search_elementtoselect', 'alpha');

$sall = trim((GETPOST('search_all', 'alphanohtml')!='') ? GETPOST('search_all', 'alphanohtml') : GETPOST('sall', 'alphanohtml'));

$optioncss = GETPOST('optioncss','alpha');
$search_btn = GETPOST('button_search','alpha');
$search_remove_btn = GETPOST('button_removefilter','alpha');

// Security check
$id = GETPOST('id','int');
$result = restrictedArea($user, 'dolitour', $id,'');

$diroutputmassaction = $conf->dolitour->dir_output . '/temp/massgeneration/'.$user->id;

// Load variable for pagination
$limit = GETPOST('limit','int')?GETPOST('limit','int'):$conf->liste_limit;
$sortfield = GETPOST("sortfield",'alpha');
$sortorder = GETPOST("sortorder",'alpha');
$page = GETPOST("page",'int');
if (empty($page) || $page == -1 || !empty($search_btn) || !empty($search_remove_btn) || (empty($toselect) && $massaction === '0')) { $page = 0; }     // If $page is not defined, or '' or -1
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (! $sortfield) $sortfield='e.rank';
if (! $sortorder) $sortorder='ASC';

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$object = new DoliTour($db);
$formdolitour = new FormDoliTour($db);
$hookmanager->initHooks(array('dolitourlist'));
$extrafields = new ExtraFields($db);

// fetch optionals attributes and labels
$extralabels = $extrafields->fetch_name_optionals_label('dolitour');
$search_array_options=$extrafields->getOptionalsFromPost($object->table_element,'','search_');

// List of fields to search into when doing a "search in all"
$fieldstosearchall = array(
	'e.ref'=>'Ref',
);

$arrayfields=array(
	'e.ref'=>array('label'=>$langs->trans("Référence"), 'checked'=>1),
	'e.rank'=>array('label'=>$langs->trans("ID"), 'checked'=>1),

	'e.title'=>array('label'=>$langs->trans("Titre"), 'checked'=>1),
	'e.description'=>array('label'=>$langs->trans("Description"), 'checked'=>1),
	'e.image'=>array('label'=>$langs->trans("Image"), 'checked'=>1),
	'e.elementtoselect'=>array('label'=>$langs->trans("Cible CSS"), 'checked'=>1),
	'e.context'=>array('label'=>$langs->trans("Contexte"), 'checked'=>1),
    'e.url'=>array('label'=>$langs->trans("Url"), 'checked'=>1), 
	'e.side'=>array('label'=>$langs->trans("Côté"), 'checked'=>1),
	'e.align'=>array('label'=>$langs->trans("Alignement"), 'checked'=>1),
	'e.fk_user_group'=>array('label'=>$langs->trans("Groupe Accès"),'checked'=>1),
	'e.play_once'=>array('label'=>$langs->trans("Jouer une seule fois ?"), 'checked'=>1),
	'e.show_progress'=>array('label'=>$langs->trans("Progression"), 'checked'=>1),
	'e.show_cross'=>array('label'=>$langs->trans("Croix de fermeture"), 'checked'=>1),
	'e.font_family'=>array('label'=>$langs->trans("Police d'écriture"), 'checked'=>1),
	'e.font_size'=>array('label'=>$langs->trans("Taille d'écriture"), 'checked'=>1),
	'e.font_color'=>array('label'=>$langs->trans("Couleur D'écriture"), 'checked'=>1),
	'e.background_color'=>array('label'=>$langs->trans("Couleur du fond"), 'checked'=>1),
	
    'e.date_start'=>array('label'=>$langs->trans("Date Début"), 'checked'=>1),
    'e.date_end'=>array('label'=>$langs->trans("Date Fin"), 'checked'=>1),
	'e.color'=>array('label'=>$langs->trans("Couleur du calque de recouvrement"), 'checked'=>1),
    'e.active'=>array('label'=>$langs->trans("Actif ?"), 'checked'=>1),

    'e.datec'=>array('label'=>$langs->trans("DateCreation"), 'checked'=>1),
	'e.tms'=>array('label'=>$langs->trans("Date de modif."), 'checked'=>0, 'position'=>500),
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
        $search_syear = '';
        $search_smonth = '';
        $search_sday = '';
        $search_eyear = '';
        $search_emonth = '';
        $search_eday = '';
		$search_title = '';
		$search_description = '';
		$search_image = '';
        $search_active = '';
		$search_font_size = '';
		$search_myear = '';
		$search_mmonth = '';
		$search_mday = '';
		$search_ref = '';
		$search_rank = '';
		$search_user_author_id = '';
		$search_play_once = '';
		$search_show_progress = '';
		$search_show_cross = '';
		$search_fk_user_group = '';
		$search_color = '';
		$search_context = '';
        $search_url = ''; 
		$search_elementtoselect = '';

		$toselect = '';
		$search_array_options = array();
	}
	if (GETPOST('button_removefilter_x','alpha') || GETPOST('button_removefilter.x','alpha') || GETPOST('button_removefilter','alpha')
	 || GETPOST('button_search_x','alpha') || GETPOST('button_search.x','alpha') || GETPOST('button_search','alpha'))
	{
		$massaction='';     // Protection to avoid mass action if we force a new search during a mass action confirmation
	}

	// Mass actions. Controls on number of lines checked.
	$maxformassaction=(empty($conf->global->MAIN_LIMIT_FOR_MASS_ACTIONS)?1000:$conf->global->MAIN_LIMIT_FOR_MASS_ACTIONS);
	if (! empty($massaction) && is_array($toselect) && count($toselect) < 1)
	{
		$error++;
		setEventMessages($langs->trans("NoRecordSelected"), null, "warnings");
	}
	if (! $error && is_array($toselect) && count($toselect) > $maxformassaction)
	{
		setEventMessages($langs->trans('TooManyRecordForMassAction', $maxformassaction), null, 'errors');
		$error++;
	}


	if ($action == "up" || $action == "down") {

		$object->fetch($id);

		$new_rank = $object->rank;
		$new_rank+= $action == "up" ? -1 : +1;

		$sql = "SELECT s.rowid FROM ".MAIN_DB_PREFIX."dolitour s WHERE s.rowid <> ".$id." AND s.entity IN (".getEntity('dolitour').") ORDER BY s.rank ASC";
		$res = $db->query($sql);
		
		$num = $db->num_rows($res);

		$i = 0;
		$ranks = array();
		while ($i < $num)
		{
			$obj = $db->fetch_object($res);
			$ranks[] = $obj->rowid;

			$i++;
		}
		
		$updated_ranks = array_slice($ranks, 0, $new_rank-1); // splice in at position 3
		$updated_ranks[] = $id;

		if (count(array_slice($ranks, $new_rank-1))) {
			foreach (array_slice($ranks, $new_rank-1) as $val) {
				$updated_ranks[] = $val;
			}
		}

		foreach ($updated_ranks as $rank => $rowid) {
			$sql = "UPDATE ".MAIN_DB_PREFIX."dolitour s SET s.rank = ".($rank+1)." WHERE s.rowid = ".$rowid;
			$db->query($sql);	
		}
	}
	if ($action == 'confirm_reset' && $confirm == 'yes' && $user->rights->dolitour->modifier) {
        $db->begin();
        foreach ($toselect as $toselectid) {
            $object_tmp = new DoliTour($db);
            $object_tmp->fetch($toselectid);

            // Config Globale
            $object_tmp->show_progress    = !empty($conf->global->DOLITOUR_DEFAULT_SHOW_PROGRESS) ? $conf->global->DOLITOUR_DEFAULT_SHOW_PROGRESS : 1;
            $object_tmp->show_cross       = !empty($conf->global->DOLITOUR_DEFAULT_SHOW_CROSS) ? $conf->global->DOLITOUR_DEFAULT_SHOW_CROSS : 1;
            $object_tmp->font_size        = !empty($conf->global->DOLITOUR_DEFAULT_FONT_SIZE) ? $conf->global->DOLITOUR_DEFAULT_FONT_SIZE : '';
            $object_tmp->font_family      = !empty($conf->global->DOLITOUR_DEFAULT_FONT_FAMILY) ? $conf->global->DOLITOUR_DEFAULT_FONT_FAMILY : '';
            $object_tmp->font_color       = !empty($conf->global->DOLITOUR_DEFAULT_FONT_COLOR) ? $conf->global->DOLITOUR_DEFAULT_FONT_COLOR : '';
            $object_tmp->background_color = !empty($conf->global->DOLITOUR_DEFAULT_BACKGROUND_COLOR) ? $conf->global->DOLITOUR_DEFAULT_BACKGROUND_COLOR : '';
            $object_tmp->side             = !empty($conf->global->DOLITOUR_DEFAULT_SIDE) ? $conf->global->DOLITOUR_DEFAULT_SIDE : 'bottom';
            $object_tmp->align            = !empty($conf->global->DOLITOUR_DEFAULT_ALIGN) ? $conf->global->DOLITOUR_DEFAULT_ALIGN : 'start';
            $object_tmp->color            = !empty($conf->global->DOLITOUR_DEFAULT_COLOR) ? $conf->global->DOLITOUR_DEFAULT_COLOR : '';

            $object_tmp->update($user);
        }
        $db->commit();
        setEventMessages("Le style des tours a été réinitialisé (le contenu a été conservé).", null, 'mesgs');
    }
	if ($action == 'confirm_clone' && $confirm == 'yes' && $user->rights->dolitour->creer) {
    if (is_array($toselect) && count($toselect) > 0) {
        $db->begin();
        $nb_cloned = 0;
        $error = 0;

        foreach ($toselect as $toselectid) {
            // On charge l'original
            $origin = new DoliTour($db);
            $result = $origin->fetch($toselectid);

            if ($result > 0) {
                // On prépare le clone
                $clone = new DoliTour($db);
                
                // Copie des propriétés (identique à card.php)
                $clone->title            = $origin->title . ' (Clone)';
                $clone->description      = $origin->description;
                $clone->image            = $origin->image; 
                $clone->elementtoselect  = $origin->elementtoselect;
                $clone->context          = $origin->context;
                $clone->url              = $origin->url;
                $clone->show_progress    = $origin->show_progress;
                $clone->show_cross       = $origin->show_cross;
                $clone->font_family      = $origin->font_family;
                $clone->font_size        = $origin->font_size;
                $clone->font_color       = $origin->font_color;
                $clone->background_color = $origin->background_color;
                $clone->side             = $origin->side;
                $clone->align            = $origin->align;
                $clone->color            = $origin->color;
                $clone->fk_user_group    = $origin->fk_user_group;
                $clone->play_once        = $origin->play_once;
                $clone->active           = 0; // Sécurité

                // Création en base
                $res = $clone->create($user);

                if ($res > 0) {
                    $nb_cloned++;
                    // Gestion de l'image physique (copie)
                    if (!empty($origin->image)) {
                        $dir_origin = $conf->dolitour->dir_output . '/' . dol_sanitizeFileName($origin->ref);
                        $dir_clone  = $conf->dolitour->dir_output . '/' . dol_sanitizeFileName($clone->ref);
                        
                        if (file_exists($dir_origin . '/' . $origin->image)) {
                            dol_mkdir($dir_clone);
                            dol_copy($dir_origin . '/' . $origin->image, $dir_clone . '/' . $origin->image);
                            // Copie des miniatures si besoin
                            if (file_exists($dir_origin . '/thumbs')) {
                                dol_mkdir($dir_clone . '/thumbs');
                                dol_copy($dir_origin . '/thumbs/' . $origin->image, $dir_clone . '/thumbs/' . $origin->image); // Simplifié
                            }
                        }
                    }
                } else {
                    $error++;
                    setEventMessages($clone->error, $clone->errors, 'errors');
                }
            }
        }

        if (!$error) {
            $db->commit();
            setEventMessages($nb_cloned . " tour(s) dupliqué(s) avec succès.", null, 'mesgs');
        } else {
            $db->rollback();
        }
    }
}
    // Mass actions
    $objectclass = 'DoliTour';
    $objectlabel = 'Onboardings';
    $permtoread = $user->rights->dolitour->lire;
    $permtodelete = $user->rights->dolitour->supprimer;
    $permtomodify = $user->rights->dolitour->modifier;
    $uploaddir = $conf->dolitour->dir_output;
    $trigger_name='DOLITOUR_SENTBYMAIL';
    include DOL_DOCUMENT_ROOT.'/core/actions_massactions.inc.php';
}

/*
 * View
 */

$now=dol_now();

$form = new Form($db);
$formother = new FormOther($db);

$userstatic = new User($db);

$title = $langs->trans("DoliToursMenu");
$help_url = "";

$sql = 'SELECT';
if ($sall) $sql = 'SELECT DISTINCT';


$sql.= " e.rowid, e.rank, e.ref, e.title, e.description,e.image, e.elementtoselect, e.context, e.url, e.show_progress, e.show_cross, e.font_family, e.font_size, e.font_color, e.background_color, e.side, e.align, e.fk_user_group, e.play_once, e.color, e.datec, e.date_start, e.date_end, e.user_author_id, e.entity, e.active, e.tms ";

// Add fields from extrafields
if(!empty($extrafields->attribute_label)){
	foreach ($extrafields->attribute_label as $key => $val) $sql.=($extrafields->attribute_type[$key] != 'separate' ? ",ef.".$key.' as options_'.$key : '');	
}

// Add fields from hooks
$parameters=array();
$reshook=$hookmanager->executeHooks('printFieldListSelect',$parameters);    // Note that $action and $object may have been modified by hook
$sql.=$hookmanager->resPrint;
$sql.= ' FROM '.MAIN_DB_PREFIX.'dolitour as e';

if(!empty($extrafields->attribute_label)){
	if (is_array($extrafields->attribute_label) && count($extrafields->attribute_label)) $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."dolitour_extrafields as ef on (e.rowid = ef.fk_object)";
}
$sql.= ' WHERE e.entity IN ('.getEntity('dolitour').')';

if ($search_ref) $sql .= natural_search('e.ref', $search_ref);
if ($search_rank) $sql .= natural_search('e.rank', $search_rank);
if ($sall) $sql .= natural_search(array_keys($fieldstosearchall), $sall);
if ($search_side && $search_side != '-1') $sql .= natural_search('e.side', $search_side);
if ($search_align && $search_align != '-1') $sql .= natural_search('e.align', $search_align);
if ($search_font_family && $search_font_family != '-1') $sql .= natural_search('e.font_family', $search_font_family);
if ($search_font_size) $sql .= natural_search('e.font_size', $search_font_size);
if ($search_title) $sql .= natural_search('e.title', $search_title);
if ($search_description) $sql .= natural_search('e.description', $search_description);
if ($search_image) $sql .= natural_search('e.image', $search_image);
if ($search_context)       $sql .= natural_search('e.context', $search_context);
if ($search_url)           $sql .= natural_search('e.url', $search_url); 
if ($search_elementtoselect) $sql .= natural_search('e.elementtoselect', $search_elementtoselect);
if ($search_color)         $sql .= natural_search('e.color', $search_color);
if ($search_play_once != '' && $search_play_once != -1) $sql .= " AND e.play_once = " . $search_play_once;
if ($search_show_progress != '' && $search_show_progress != -1) $sql .= " AND e.show_progress = " . $search_show_progress;
if ($search_show_cross != '' && $search_show_cross != -1)       $sql .= " AND e.show_cross = " . $search_show_cross;
if ($search_fk_user_group > 0) $sql .= " AND e.fk_user_group = " . $search_fk_user_group;
if ($search_active >= 0) $sql.= " AND e.active = " .$search_active;
if ($search_user_author_id > 0) $sql.= " AND e.user_author_id = " .$search_user_author_id;

if ($search_mmonth > 0)
{
	if ($search_myear > 0 && empty($search_mday))
	$sql.= " AND e.tms BETWEEN '".$db->idate(dol_get_first_day($search_myear, $search_mmonth, false))."' AND '".$db->idate(dol_get_last_day($search_myear, $search_mmonth, false))."'";
	else if ($search_myear > 0 && ! empty($search_mday))
	$sql.= " AND e.tms BETWEEN '".$db->idate(dol_mktime(0, 0, 0, $search_mmonth, $search_mday, $search_myear))."' AND '".$db->idate(dol_mktime(23, 59, 59, $search_mmonth, $search_mday, $search_myear))."'";
	else
	$sql.= " AND date_format(e.tms, '%m') = '".$search_mmonth."'";
}

else if ($search_myear > 0)
{
	$sql.= " AND e.tms BETWEEN '".$db->idate(dol_get_first_day($search_myear, 1, false))."' AND '".$db->idate(dol_get_last_day($search_myear, 12, false))."'";
}
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

if ($search_smonth > 0)
{
    if ($search_syear > 0 && empty($search_sday))
        $sql.= " AND e.date_start BETWEEN '".$db->idate(dol_get_first_day($search_syear, $search_smonth, false))."' AND '".$db->idate(dol_get_last_day($search_syear, $search_smonth, false))."'";
    else if ($search_syear > 0 && ! empty($search_sday))
        $sql.= " AND e.date_start BETWEEN '".$db->idate(dol_mktime(0, 0, 0, $search_smonth, $search_sday, $search_syear))."' AND '".$db->idate(dol_mktime(23, 59, 59, $search_smonth, $search_sday, $search_syear))."'";
    else
        $sql.= " AND date_format(e.date_start, '%m') = '".$search_smonth."'";
}
else if ($search_syear > 0)
{
    $sql.= " AND e.date_start BETWEEN '".$db->idate(dol_get_first_day($search_syear, 1, false))."' AND '".$db->idate(dol_get_last_day($search_syear, 12, false))."'";
}

if ($search_emonth > 0)
{
    if ($search_eyear > 0 && empty($search_eday))
        $sql.= " AND e.date_end BETWEEN '".$db->idate(dol_get_first_day($search_eyear, $search_emonth, false))."' AND '".$db->idate(dol_get_last_day($search_eyear, $search_emonth, false))."'";
    else if ($search_eyear > 0 && ! empty($search_eday))
        $sql.= " AND e.date_end BETWEEN '".$db->idate(dol_mktime(0, 0, 0, $search_emonth, $search_eday, $search_eyear))."' AND '".$db->idate(dol_mktime(23, 59, 59, $search_emonth, $search_eday, $search_eyear))."'";
    else
        $sql.= " AND date_format(e.date_end, '%m') = '".$search_emonth."'";
}
else if ($search_eyear > 0)
{
    $sql.= " AND e.date_end BETWEEN '".$db->idate(dol_get_first_day($search_eyear, 1, false))."' AND '".$db->idate(dol_get_last_day($search_eyear, 12, false))."'";
}

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
//print $sql; exit;

$resql = $db->query($sql);
if ($resql)
{
	$title = $langs->trans('ListOfDoliTours');

	$num = $db->num_rows($resql);

	$arrayofselected=is_array($toselect)?$toselect:array();

	if ($num == 1 && ! empty($conf->global->MAIN_SEARCH_DIRECT_OPEN_IF_ONLY_ONE) && $sall)
	{
		$obj = $db->fetch_object($resql);
		$id = $obj->rowid;
		
		$url = dol_buildpath('/dolitour/card.php', 1).'?id='.$id;

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

    if ($search_eday)      		$param.='&search_eday='.urlencode($search_eday);
    if ($search_emonth)      		$param.='&search_emonth='.urlencode($search_emonth);
    if ($search_eyear)       		$param.='&search_eyear='.urlencode($search_eyear);

    if ($search_sday)      		$param.='&search_sday='.urlencode($search_sday);
    if ($search_smonth)      		$param.='&search_smonth='.urlencode($search_smonth);
    if ($search_syear)       		$param.='&search_syear='.urlencode($search_syear);


    if ($search_ref)      		$param.='&search_ref='.urlencode($search_ref);
    if ($search_rank)      		$param.='&search_rank='.urlencode($search_rank);

	if ($search_title) 	$param.='&search_title='.urlencode($search_title);
	if ($search_description) 	$param.='&search_description='.urlencode($search_description);
	if ($search_image) 	$param.='&search_image='.urlencode($search_image);
    if ($search_active >= 0) 		$param.='&search_active='.urlencode($search_active);

    if ($search_user_author_id > 0) 		$param.='&search_user_author_id='.urlencode($search_user_author_id);
	if ($search_play_once != '' && $search_play_once != -1) $param.='&search_play_once='.urlencode($search_play_once);
	if ($search_context) $param.='&search_context='.urlencode($search_context);
    if ($search_url) $param.='&search_url='.urlencode($search_url); 
    if ($optioncss != '')       $param.='&optioncss='.urlencode($optioncss);

	// Add $param from extra fields
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_param.tpl.php';

	// List of mass actions available
	$arrayofmassactions =  array();
    // Réinitialiser
	if ($user->rights->dolitour->modifier) $arrayofmassactions['prereset']="Réinitialiser";
    
    // Dupliquer
	if ($user->rights->dolitour->creer)    $arrayofmassactions['preclone']="Dupliquer";
    
    // Supprimer 
    if ($user->rights->dolitour->supprimer) $arrayofmassactions['predelete']=$langs->trans("MassActionDelete");

    // Masquage du menu si une action est en cours de confirmation
	if (in_array($massaction, array('presend', 'predelete', 'prereset', 'preclone'))) $arrayofmassactions=array();
	
	$massactionbutton=$form->selectMassAction('', $arrayofmassactions);

	$newcardbutton='';
	if ($contextpage == 'dolitourlist' && $user->rights->dolitour->creer)
	{
		$newcardbutton='<a class="butActionNew" href="'.dol_buildpath('/dolitour/card.php?action=create', 2).'"><span class="valignmiddle">'.$langs->trans('NewDoliTour').'</span>';
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


	print_barre_liste($title, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, $massactionbutton, $num, $nbtotalofrecords, 'dolitour@dolitour', 0, $newcardbutton, '', $limit);

	// Fenêtre de confirmation réinitialisation.
	if ($massaction == 'prereset') {
        $array_toselect = array();
        if (is_array($toselect) && count($toselect) > 0) {
            foreach($toselect as $t) {
                $array_toselect[] = array(
                    'type' => 'hidden',
                    'name' => 'toselect[]',
                    'value' => $t
                );
            }
			$formconfirm = $form->formconfirm(
                $_SERVER["PHP_SELF"],                   
                "Réinitialiser le style",              
                "Êtes-vous sûr de vouloir réinitialiser le style (couleurs, polices, position) des tours sélectionnés ?<br>Le contenu (titre, description, image, cible) NE SERA PAS modifié.", 
                "confirm_reset",                        
                $array_toselect,                        
                0,                                      
                0                                       
            );
        }
    }
	if ($massaction == 'preclone') {
        $array_toselect = array();
        if (is_array($toselect) && count($toselect) > 0) {
            foreach($toselect as $t) {
                $array_toselect[] = array(
                    'type' => 'hidden',
                    'name' => 'toselect[]',
                    'value' => $t
                );
            }
            $formconfirm = $form->formconfirm(
                $_SERVER["PHP_SELF"],                   
                "Dupliquer les tours",              
                "Êtes-vous sûr de vouloir dupliquer les tours sélectionnés ?<br>Les copies seront créées avec le suffixe (Clone) et seront désactivées par défaut.", 
                "confirm_clone",                        
                $array_toselect,                        
                0,                                      
                0                                      
            );
        }
    }
	

    $topicmail = "SendDoliTourRef";
    $modelmail = "dolitour_send";
    $objecttmp = new DoliTour($db);
    $trackid = 'onb'.$object->id;
    include DOL_DOCUMENT_ROOT.'/core/tpl/massactions_pre.tpl.php';
	if (! empty($formconfirm)) print $formconfirm;
	
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
		$moreforfilter.=$langs->trans('Crée par l\'utilisateur'). ' : ';
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
	
	$array_years = array();
    $current_year = date("Y");
    // On génère de -20 ans à +5 ans 
    for ($i = $current_year + 5; $i >= $current_year - 20; $i--) {
        $array_years[$i] = $i;
    }
	
	print '<tr class="liste_titre_filter">';
	
	print '<td class="liste_titre"></td>';

	// Référence
	if (! empty($arrayfields['e.ref']['checked']))
	{
		print '<td class="liste_titre">';
		print '<input class="flat" size="6" type="text" name="search_ref" value="'.$search_ref.'">';
		print '</td>';
	}
	// ID (Rank)
	if (! empty($arrayfields['e.rank']['checked']))
	{
		print '<td class="liste_titre">';
		print '<input class="flat" size="6" type="text" name="search_rank" value="'.$search_rank.'">';
		print '</td>';
	}
	// Actif (Style Standard Forcé - HTML Manuel)
    if (! empty($arrayfields['e.active']['checked']))
    {
        print '<td class="liste_titre">';
        $array_yesno = array('1'=>$langs->trans("Yes"), '0'=>$langs->trans("No"));
        print $form->selectarray('search_active', $array_yesno, $search_active, 1, 0, 0, 'minwidth50', 0, 0, 0, '', '', 1);
        print '</td>';
    }
	// Groupe Accès
	if (! empty($arrayfields['e.fk_user_group']['checked']))
	{
		print '<td class="liste_titre">';
		print $form->select_dolgroups($search_fk_user_group, 'search_fk_user_group', 1);
		print '</td>';
	}
	// Titre
	if (! empty($arrayfields['e.title']['checked']))
	{
		print '<td class="liste_titre">';
		print '<input class="flat" size="20" type="text" name="search_title" value="'.$search_title.'">';
		print '</td>';
	}
	// Description
	if (! empty($arrayfields['e.description']['checked']))
	{
		print '<td class="liste_titre">';
		print '<input class="flat" size="20" type="text" name="search_description" value="'.$search_description.'">';
		print '</td>';
	}
	// Cible CSS
	if (! empty($arrayfields['e.elementtoselect']['checked']))
	{
		print '<td class="liste_titre">';
		print '<input class="flat" size="10" type="text" name="search_elementtoselect" value="'.$search_elementtoselect.'">';
		print '</td>';
	}
	// Contexte
	if (! empty($arrayfields['e.context']['checked']))
	{
		print '<td class="liste_titre">';
		print '<input class="flat" size="10" type="text" name="search_context" value="'.$search_context.'">';
		print '</td>';
	}
    // URL 
	if (! empty($arrayfields['e.url']['checked']))
	{
		print '<td class="liste_titre">';
		print '<input class="flat" size="10" type="text" name="search_url" value="'.$search_url.'">';
		print '</td>';
	}
	// Date Début
    if (! empty($arrayfields['e.date_start']['checked']))
    {
        print '<td class="liste_titre nowraponall" align="left">';
        print $form->selectarray('search_syear', $array_years, $search_syear, 1, 0, 0, 'maxwidth75', 0, 0, 0, '', '', 1);
        print '</td>';
    }
	// Date Fin 
    if (! empty($arrayfields['e.date_end']['checked']))
    {
        print '<td class="liste_titre nowraponall" align="left">';
        print $form->selectarray('search_eyear', $array_years, $search_eyear, 1, 0, 0, 'maxwidth75', 0, 0, 0, '', '', 1);
        print '</td>';
    }
	// Date Création
	if (! empty($arrayfields['e.datec']['checked']))
	{
		print '<td class="liste_titre nowraponall" align="left">';
		print $form->selectarray('search_dyear', $array_years, $search_dyear, 1, 0, 0, 'maxwidth75', 0, 0, 0, '', '', 1);
		print '</td>';
	}
	// Date Modification 
	if (! empty($arrayfields['e.tms']['checked']))
	{
		print '<td class="liste_titre nowraponall" align="left">';
		print $form->selectarray('search_myear', $array_years, $search_myear, 1, 0, 0, 'maxwidth75', 0, 0, 0, '', '', 1);
		print '</td>';
	}
	// Rejouabilité
	if (! empty($arrayfields['e.play_once']['checked']))
    {
        print '<td class="liste_titre">';
        $array_yesno = array('1'=>$langs->trans("Yes"), '0'=>$langs->trans("No"));
        print $form->selectarray('search_play_once', $array_yesno, $search_play_once, 1);
        print '</td>';
    }
	// Progression
	if (! empty($arrayfields['e.show_progress']['checked']))
	{
		print '<td class="liste_titre">';
		print $formdolitour->selectShowProgress(GETPOST('search_show_progress'), 'search_show_progress', 1);
		print '</td>';
	}
	// Croix de fermeture
	if (! empty($arrayfields['e.show_cross']['checked']))
	{
		print '<td class="liste_titre">';
		print $formdolitour->selectShowCross(GETPOST('search_show_cross'), 'search_show_cross', 1);
		print '</td>';
	}
	// Côté (Side)
	if (! empty($arrayfields['e.side']['checked']))
	{
		print '<td class="liste_titre">';
		print $formdolitour->selectSide($search_side, 'search_side', 1);
		print '</td>';
	}
	// Alignement
	if (! empty($arrayfields['e.align']['checked']))
	{
		print '<td class="liste_titre">';
		print $formdolitour->selectAlign($search_align, 'search_align', 1);
		print '</td>';
	}
	// Image
	if (! empty($arrayfields['e.image']['checked']))
	{
		print '<td class="liste_titre">';
		print '<input class="flat" size="10" type="text" name="search_image" value="'.$search_image.'">';
		print '</td>';
	}
	// Police (Type d'écriture)
	if (! empty($arrayfields['e.font_family']['checked']))
	{
		print '<td class="liste_titre">';
		print $formdolitour->selectFontFamily($search_font_family, 'search_font_family', 1);
		print '</td>';
	}
	// Taille d'écriture
	if (! empty($arrayfields['e.font_size']['checked']))
	{
		print '<td class="liste_titre">';
		print '<input class="flat" size="4" type="text" name="search_font_size" value="'.$search_font_size.'">';
		print '</td>';
	}
	// Couleur d'écriture
	if (! empty($arrayfields['e.font_color']['checked']))
	{
		print '<td class="liste_titre">';
		print '&nbsp;';
		print '</td>';
	}
	// Couleur du tour 
	if (! empty($arrayfields['e.background_color']['checked']))
	{
		print '<td class="liste_titre">';
		print '&nbsp;';
		print '</td>';
	}
	// Couleur du fond
	if (! empty($arrayfields['e.color']['checked']))
	{
		print '<td class="liste_titre">';
		print '&nbsp;';
		print '</td>';
	}

	// Extra fields
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_input.tpl.php';
	
	// Action column
	print '<td class="liste_titre" align="middle">'; // Retrait du colspan="2"
	$searchpicto=$form->showFilterButtons();
	print $searchpicto;
	print '</td>';

	print "</tr>\n";

	// Fields title
	print '<tr class="liste_titre">';
	print_liste_field_titre('',$_SERVER["PHP_SELF"],"",'','','',$sortfield,$sortorder);

	if (! empty($arrayfields['e.ref']['checked']))              print_liste_field_titre($arrayfields['e.ref']['label'],$_SERVER["PHP_SELF"],'e.ref','',$param,'style="min-width:25px;"',$sortfield,$sortorder);
	if (! empty($arrayfields['e.rank']['checked']))             print_liste_field_titre($arrayfields['e.rank']['label'],$_SERVER["PHP_SELF"],'e.rank','',$param,'style="min-width:25px;"',$sortfield,$sortorder);
	if (! empty($arrayfields['e.active']['checked']))           print_liste_field_titre($arrayfields['e.active']['label'],$_SERVER["PHP_SELF"],'e.active','',$param,'style="min-width:60px;"',$sortfield,$sortorder);
	if (! empty($arrayfields['e.fk_user_group']['checked']))    print_liste_field_titre($arrayfields['e.fk_user_group']['label'],$_SERVER["PHP_SELF"],'e.fk_user_group','',$param,'style="min-width:100px;"',$sortfield,$sortorder);
	if (! empty($arrayfields['e.title']['checked']))            print_liste_field_titre($arrayfields['e.title']['label'],$_SERVER["PHP_SELF"],'e.title','',$param,'style="min-width:100px;"',$sortfield,$sortorder);
	if (! empty($arrayfields['e.description']['checked']))      print_liste_field_titre($arrayfields['e.description']['label'],$_SERVER["PHP_SELF"],'e.description','style="min-width:150px;"',$param,'',$sortfield,$sortorder);
	if (! empty($arrayfields['e.elementtoselect']['checked']))  print_liste_field_titre($arrayfields['e.elementtoselect']['label'],$_SERVER["PHP_SELF"],'e.elementtoselect','style="min-width:100px;"',$param,'',$sortfield,$sortorder);
	if (! empty($arrayfields['e.context']['checked']))          print_liste_field_titre($arrayfields['e.context']['label'],$_SERVER["PHP_SELF"],'e.context','',$param,'style="min-width:100px;"',$sortfield,$sortorder);
    if (! empty($arrayfields['e.url']['checked']))              print_liste_field_titre($arrayfields['e.url']['label'],$_SERVER["PHP_SELF"],'e.url','',$param,'style="min-width:100px;"',$sortfield,$sortorder);
	if (! empty($arrayfields['e.date_start']['checked']))       print_liste_field_titre($arrayfields['e.date_start']['label'],$_SERVER["PHP_SELF"],'e.date_start','',$param,'style="min-width:100px;"',$sortfield,$sortorder);
    if (! empty($arrayfields['e.date_end']['checked']))         print_liste_field_titre($arrayfields['e.date_end']['label'],$_SERVER["PHP_SELF"],'e.date_end','',$param,'style="min-width:100px;"',$sortfield,$sortorder);
	if (! empty($arrayfields['e.datec']['checked']))            print_liste_field_titre($arrayfields['e.datec']['label'],$_SERVER["PHP_SELF"],'e.datec','',$param,'style="min-width:100px;"',$sortfield,$sortorder);
	if (! empty($arrayfields['e.tms']['checked']))              print_liste_field_titre($arrayfields['e.tms']['label'],$_SERVER["PHP_SELF"],"e.tms","",$param,'align="left" class="nowrap" style="min-width:100px;"',$sortfield,$sortorder);
	if (! empty($arrayfields['e.play_once']['checked']))        print_liste_field_titre($arrayfields['e.play_once']['label'],$_SERVER["PHP_SELF"],'e.play_once','',$param,'style="min-width:60px;"',$sortfield,$sortorder);
	if (! empty($arrayfields['e.show_progress']['checked']))    print_liste_field_titre($arrayfields['e.show_progress']['label'],$_SERVER["PHP_SELF"],'e.show_progress','',$param,'style="min-width:60px;"',$sortfield,$sortorder);
	if (! empty($arrayfields['e.show_cross']['checked']))       print_liste_field_titre($arrayfields['e.show_cross']['label'],$_SERVER["PHP_SELF"],'e.show_cross','',$param,'style="min-width:60px;"',$sortfield,$sortorder);
	if (! empty($arrayfields['e.side']['checked']))             print_liste_field_titre($arrayfields['e.side']['label'],$_SERVER["PHP_SELF"],'e.side','',$param,'style="min-width:80px;"',$sortfield,$sortorder);
	if (! empty($arrayfields['e.align']['checked']))            print_liste_field_titre($arrayfields['e.align']['label'],$_SERVER["PHP_SELF"],'e.align','',$param,'style="min-width:80px;"',$sortfield,$sortorder);
	if (! empty($arrayfields['e.image']['checked']))            print_liste_field_titre($arrayfields['e.image']['label'],$_SERVER["PHP_SELF"],'e.image','',$param,'style="min-width:100px;"',$sortfield,$sortorder);
	if (! empty($arrayfields['e.font_family']['checked']))      print_liste_field_titre($arrayfields['e.font_family']['label'],$_SERVER["PHP_SELF"],'e.font_family','',$param,'style="min-width:100px;"',$sortfield,$sortorder);
	if (! empty($arrayfields['e.font_size']['checked']))        print_liste_field_titre($arrayfields['e.font_size']['label'],$_SERVER["PHP_SELF"],'e.font_size','',$param,'style="min-width:80px;"',$sortfield,$sortorder);
	if (! empty($arrayfields['e.font_color']['checked']))       print_liste_field_titre($arrayfields['e.font_color']['label'],$_SERVER["PHP_SELF"],'e.font_color','',$param,'style="min-width:80px;"',$sortfield,$sortorder);
	if (! empty($arrayfields['e.background_color']['checked'])) print_liste_field_titre($arrayfields['e.background_color']['label'],$_SERVER["PHP_SELF"],'e.background_color','',$param,'style="min-width:80px;"',$sortfield,$sortorder);
    if (! empty($arrayfields['e.color']['checked']))            print_liste_field_titre($arrayfields['e.color']['label'],$_SERVER["PHP_SELF"],'e.color','',$param,'style="min-width:80px;"',$sortfield,$sortorder);

	// Extra fields
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_title.tpl.php';
	
	print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"],"",'',$param,'align="center"',$sortfield,$sortorder,'maxwidthsearch ');
	print '</tr>'."\n";

	$productstat_cache=array();

	$generic_dolitour = new DoliTour($db);
	$generic_user = new User($db);


	$i=0;
	$totalarray=array();
	$totalarray['nbfield']=0;
	while ($i < min($num,$limit))
	{
		$obj = $db->fetch_object($resql);


		$generic_dolitour->id = $obj->rowid;
		$generic_dolitour->ref = $obj->ref;
		$generic_dolitour->rank = $obj->rank;
		$generic_dolitour->datec = $db->jdate($obj->datec);
		$generic_dolitour->play_once = $obj->play_once;

		// Ajout d'un ID unique a la ligne pour le tri
		print '<tr class="oddeven" id="row_'.$obj->rowid.'">';
		print '<td class="tdlineupdown" align="center" style="cursor:move; min-width:30px;">';
        print '<span class="fa fa-bars fa-lg" style="color:#999;" title="Glisser-déposer pour réorganiser"></span>';
        print '</td>';

		// Ref
		if (! empty($arrayfields['e.ref']['checked']))
		{
			print '<td class="nowrap">';
			print $generic_dolitour->getNomUrl(0);
			print '</td>';
			if (! $i) $totalarray['nbfield']++;
		}
		// Rank
		if (! empty($arrayfields['e.rank']['checked']))
		{
			print '<td class="nowrap col_rank">';
			print $obj->rank;
			print '</td>';
			if (! $i) $totalarray['nbfield']++;
		}
        // Actif
        if (! empty($arrayfields['e.active']['checked']))
        {
            print '<td align="left">';
            print yn($obj->active);
            print '</td>';
            if (! $i) $totalarray['nbfield']++;
        }
		// Groupe Accès
        if (! empty($arrayfields['e.fk_user_group']['checked']))
        {
            print '<td align="left">';
            if ($obj->fk_user_group > 0) {
                $group_static = new UserGroup($db);
                $group_static->fetch($obj->fk_user_group);
                print $group_static->name;
            } else {
                print $langs->trans("Tout le monde");
            }
            print '</td>';
            if (! $i) $totalarray['nbfield']++;
        }
		// Titre
		if (! empty($arrayfields['e.title']['checked']))
		{
			print '<td align="left">';
			print $obj->title;
			print '</td>';
			if (! $i) $totalarray['nbfield']++;
		}
        // Description
        if (! empty($arrayfields['e.description']['checked']))
        {
            print '<td align="left">';
            print $obj->description;
            print '</td>';
            if (! $i) $totalarray['nbfield']++;
        }
        // Cible CSS (elementtoselect)
        if (! empty($arrayfields['e.elementtoselect']['checked']))
        {
            print '<td align="left">';
            print $obj->elementtoselect;
            print '</td>';
            if (! $i) $totalarray['nbfield']++;
        }
        // Context
        if (! empty($arrayfields['e.context']['checked']))
        {
            print '<td align="left">';
            print $obj->context;
            print '</td>';
            if (! $i) $totalarray['nbfield']++;
        }
        // URL 
        if (! empty($arrayfields['e.url']['checked']))
        {
            print '<td align="left">';
            print $obj->url;
            print '</td>';
            if (! $i) $totalarray['nbfield']++;
        }
        // Date Start
        if (! empty($arrayfields['e.date_start']['checked']))
        {
            print '<td align="left">';
            print dol_print_date($db->jdate($obj->date_start), 'day');
            print '</td>';
            if (! $i) $totalarray['nbfield']++;
        }
        // Date End
        if (! empty($arrayfields['e.date_end']['checked']))
        {
            print '<td align="left">';
            print dol_print_date($db->jdate($obj->date_end), 'day');
            print '</td>';
            if (! $i) $totalarray['nbfield']++;
        }
		// Date Création
		if (! empty($arrayfields['e.datec']['checked']))
		{
			print '<td align="left">';
			print dol_print_date($db->jdate($obj->datec), 'day');
			print '</td>';
			if (! $i) $totalarray['nbfield']++;
		}
		// Date Modification
		if (! empty($arrayfields['e.tms']['checked']))
		{
			print '<td align="left" class="nowrap">';
			print dol_print_date($db->jdate($obj->tms), 'dayhour', 'tzuser');
			print '</td>';
			if (! $i) $totalarray['nbfield']++;
		}
		// Rejouabilité
		if (! empty($arrayfields['e.play_once']['checked']))
    	{
            print '<td align="left">';
            print yn($obj->play_once);
            print '</td>';
            if (! $i) $totalarray['nbfield']++;
    	}
		// Progression
        if (! empty($arrayfields['e.show_progress']['checked']))
        {
            print '<td align="left">';
            print yn($obj->show_progress);
            print '</td>';
            if (! $i) $totalarray['nbfield']++;
        }
		// Croix de fermeture
        if (! empty($arrayfields['e.show_cross']['checked']))
        {
            print '<td align="left">';
            print yn($obj->show_cross);
            print '</td>';
            if (! $i) $totalarray['nbfield']++;
        }
        // Side
		if (! empty($arrayfields['e.side']['checked']))
        {
            print '<td align="left">';
            $liste_cotes = $formdolitour->getSideList();
            if (isset($liste_cotes[$obj->side])) { print $liste_cotes[$obj->side]; } else { print $obj->side; }
            print '</td>';
            if (! $i) $totalarray['nbfield']++;
        }
        // Align
        if (! empty($arrayfields['e.align']['checked']))
		{
			print '<td align="left">';
            $liste_align = $formdolitour->getAlignList();
            if (isset($liste_align[$obj->align])) { print $liste_align[$obj->align]; } else { print $obj->align; }
			print '</td>';
			if (! $i) $totalarray['nbfield']++;
		}
		// Image
		if (! empty($arrayfields['e.image']['checked']))
        {
            print '<td align="left">';
            if ($obj->image) {
                $relativepath = dol_sanitizeFileName($obj->ref) . '/' . $obj->image;
                $image_url = DOL_URL_ROOT . '/viewimage.php?modulepart=dolitour&file=' . urlencode($relativepath);
                print '<a href="'.$image_url.'" target="_blank" title="Voir l\'image en grand">';
                print '<img src="'.$image_url.'" style="height:24px; width:auto; vertical-align:middle; border:1px solid #ddd; border-radius:3px; margin-right:8px; background:#fff;">';
                print '</a>';
                print '<span class="opacitymedium">'.$obj->image.'</span>';
            }
            print '</td>';
            if (! $i) $totalarray['nbfield']++;
        }
		// Police d'écriture
        if (! empty($arrayfields['e.font_family']['checked']))
        {
            print '<td align="left">';
            if ($obj->font_family) {
                 print '<span style="font-family:\''.$obj->font_family.'\', sans-serif; font-size:1.1em;">'.$obj->font_family.'</span>';
            }
            print '</td>';
            if (! $i) $totalarray['nbfield']++;
        }
		// Taille d'écriture
        if (! empty($arrayfields['e.font_size']['checked']))
        {
            print '<td align="left">';
            print $obj->font_size;
            print '</td>';
            if (! $i) $totalarray['nbfield']++;
        }
        // Couleur d'écriture
        if (! empty($arrayfields['e.font_color']['checked']))
        {
            print '<td align="left">';
            if ($obj->font_color) {
                print '<span style="display:inline-block; width:20px; height:12px; background-color:'.$obj->font_color.'; border:1px solid #888; vertical-align:middle; margin-right:5px; border-radius:3px;"></span>';
                print '<span class="opacitymedium">'.$obj->font_color.'</span>';
            }
            print '</td>';
            if (! $i) $totalarray['nbfield']++;
        }
        // Couleur du tour
        if (! empty($arrayfields['e.background_color']['checked']))
        {
            print '<td align="left">';
            if ($obj->background_color) {
                print '<span style="display:inline-block; width:20px; height:12px; background-color:'.$obj->background_color.'; border:1px solid #888; vertical-align:middle; margin-right:5px; border-radius:3px;"></span>';
                print '<span class="opacitymedium">'.$obj->background_color.'</span>';
            }
            print '</td>';
            if (! $i) $totalarray['nbfield']++;
        }
		// Couleur du fond 
        if (! empty($arrayfields['e.color']['checked']))
        {
            print '<td align="left">';
            if ($obj->color) {
                print '<span style="display:inline-block; width:20px; height:12px; background-color:'.$obj->color.'; border:1px solid #888; vertical-align:middle; margin-right:5px; border-radius:3px;"></span>';
                print '<span class="opacitymedium">'.$obj->color.'</span>';
            }
            print '</td>';
            if (! $i) $totalarray['nbfield']++;
        }

		include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_print_fields.tpl.php';
		
		print '<td align="center">';
		if ($massactionbutton || $massaction)
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
?>
<script type="text/javascript">
    $(document).ready(function() {
        // Corps du tableau
        $(".tagtable tbody").sortable({
            handle: '.tdlineupdown', 
            cursor: 'move',          
            opacity: 0.7,            
            axis: 'y',               
            update: function(event, ui) {
                var new_order = $(this).sortable("serialize"); 
                var security_token = '<?php echo newToken(); ?>';

                $.ajax({
                    url: '<?php echo dol_buildpath('/dolitour/ajax.php', 1); ?>',
                    method: 'POST',
                    data: new_order + "&action=update_rank&token=" + security_token, 
                    
                    success: function(response) {
                        if (response.trim() == "OK") {
                             // Actualisation de L'ID en fonction de la page
                             var i = <?php echo ($offset + 1); ?>;
                             $('.tagtable tbody tr.oddeven').each(function() {
                                 $(this).find('.col_rank').text(i);
                                 i++;
                             });
                             if (typeof $.jnotify === 'function') {
                                $.jnotify("Ordre sauvegardé", "info");
                             }
                        } else {
                            console.error("Erreur : " + response);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error("Erreur AJAX : " + status);
                    }
                });
            }
        });
        
        $(".tdlineupdown").css("cursor", "move");
    });
</script>
<?php
// End of page
llxFooter();
$db->close();