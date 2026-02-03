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
 *	\file       htdocs/dolitour/list_logs.php
 *	\ingroup    dolitour
 *	\brief      Page to list orders
 */

/*** 
 * Page to list DoliTour Logs
 */

$res=@include("../main.inc.php");                   // For root directory
if (! $res) $res=@include("../../main.inc.php");    // For "custom" directory

require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php';
dol_include_once("/dolitour/class/dolitour.class.php");

// Protection (Lecteurs)
// if (!$user->rights->dolitour->lire) accessforbidden();

// Protection (Admins)
if (!$user->admin) accessforbidden();

$action = GETPOST('action','alpha');
$massaction = GETPOST('massaction','alpha');
$confirm = GETPOST('confirm','alpha');
$toselect = GETPOST('toselect', 'array');
$contextpage = GETPOST('contextpage','aZ') ? GETPOST('contextpage','aZ') : 'dolitourlogslist';

// Filtres de recherche
$search_user = GETPOST('search_user', 'nohtml');
$search_tour = GETPOST('search_tour', 'nohtml'); 
$search_tour_id = GETPOST('search_tour_id', 'int');

// Filtre Dates 
$search_sday   = GETPOST("search_sday", "int");
$search_smonth = GETPOST("search_smonth", "int");
$search_syear  = GETPOST("search_syear", "int");
$search_eday   = GETPOST("search_eday", "int");
$search_emonth = GETPOST("search_emonth", "int");
$search_eyear  = GETPOST("search_eyear", "int");

$limit = GETPOST('limit','int')?GETPOST('limit','int'):$conf->liste_limit;
$sortfield = GETPOST("sortfield",'alpha');
$sortorder = GETPOST("sortorder",'alpha');
$page = GETPOST("page",'int');

if (empty($page) || $page == -1) { $page = 0; }
$offset = $limit * $page;
if (! $sortfield) $sortfield='l.tms';
if (! $sortorder) $sortorder='DESC';

include DOL_DOCUMENT_ROOT.'/core/actions_changeselectedfields.inc.php';

$langs->load("dolitour@dolitour");
$langs->load("users");

$arrayfields = array(
    'l.tms' => array('label' => "Date de visionnage", 'checked' => 1),
    'u.lastname' => array('label' => "Utilisateur", 'checked' => 1),
    't.title' => array('label' => "Tour Consulté", 'checked' => 1),
);

$var_name = $contextpage.'_SELECTEDFIELDS';
if (!empty($user->conf->$var_name)) {
    $tmparray = explode(',', $user->conf->$var_name);
    foreach ($arrayfields as $key => $val) {
        // Si le champ est dans la liste sauvegardée, on coche, sinon on décoche
        $arrayfields[$key]['checked'] = (in_array($key, $tmparray) ? 1 : 0);
    }
}

/*
 * Actions
 */

// Suppression de masse
if ($action == 'confirm_delete' && $confirm == 'yes' && $user->rights->dolitour->supprimer) {
    if (is_array($toselect) && count($toselect) > 0) {
        $db->begin();
        $nb_deleted = 0;
        $error = 0;
        foreach ($toselect as $toselectid) {
            $sql = "DELETE FROM ".MAIN_DB_PREFIX."dolitour_logs WHERE rowid = ".((int)$toselectid);
            $res = $db->query($sql);
            if ($res) $nb_deleted++;
            else {
                $error++;
                setEventMessages($db->lasterror(), null, 'errors');
            }
        }
        if (!$error) {
            $db->commit();
            setEventMessages($nb_deleted . " historique(s) supprimé(s). Les utilisateurs reverront le tour.", null, 'mesgs');
        } else {
            $db->rollback();
        }
    }
}

// Purge des filtres
if (GETPOST('button_removefilter_x','alpha') || GETPOST('button_removefilter.x','alpha') || GETPOST('button_removefilter','alpha')) {
    $search_user = '';
    $search_tour = '';
    $search_sday = ''; $search_smonth = ''; $search_syear = '';
    $search_eday = ''; $search_emonth = ''; $search_eyear = '';
}

/*
 * View
 */

$form = new Form($db);

$title = "Historique des visites";

// Construction de la requête
$sql = "SELECT l.rowid, l.tms as date_view,";
$sql.= " u.rowid as uid, u.login, u.firstname, u.lastname, u.email, u.photo, u.statut as user_status,"; // Infos User
$sql.= " t.rowid as tid, t.title, t.ref"; // Infos Tour
$sql.= " FROM ".MAIN_DB_PREFIX."dolitour_logs as l";
// Jointure LEFT JOIN
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."user as u ON l.fk_user = u.rowid";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."dolitour as t ON l.fk_tour = t.rowid";
$sql.= " WHERE l.entity IN (".getEntity('dolitour').")";

// Application des filtres
if ($search_user) $sql .= natural_search(array('u.login', 'u.firstname', 'u.lastname'), $search_user);

// Priorité à l'ID
if ($search_tour_id > 0) {
    // Si on a un ID précis, on ne filtre QUE par l'ID
    $sql .= " AND l.fk_tour = " . $search_tour_id;
} elseif ($search_tour) {
    // Sinon (recherche manuelle), on filtre par le nom
    $sql .= natural_search(array('t.title', 't.ref'), $search_tour);
}

// Filtre Date (Début / Fin)
if ($search_smonth > 0) {
    if ($search_syear > 0 && empty($search_sday))
        $sql.= " AND l.tms >= '".$db->idate(dol_get_first_day($search_syear, $search_smonth, false))."'";
    else if ($search_syear > 0 && ! empty($search_sday))
        $sql.= " AND l.tms >= '".$db->idate(dol_mktime(0, 0, 0, $search_smonth, $search_sday, $search_syear))."'";
} else if ($search_syear > 0) {
    $sql.= " AND l.tms >= '".$db->idate(dol_get_first_day($search_syear, 1, false))."'";
}
if ($search_emonth > 0) {
    if ($search_eyear > 0 && empty($search_eday))
        $sql.= " AND l.tms <= '".$db->idate(dol_get_last_day($search_eyear, $search_emonth, false))."'";
    else if ($search_eyear > 0 && ! empty($search_eday))
        $sql.= " AND l.tms <= '".$db->idate(dol_mktime(23, 59, 59, $search_emonth, $search_eday, $search_eyear))."'";
} else if ($search_eyear > 0) {
    $sql.= " AND l.tms <= '".$db->idate(dol_get_last_day($search_eyear, 12, false))."'";
}


$sql.= $db->order($sortfield, $sortorder);

// Count total
$nbtotalofrecords = '';
if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST)) {
    $result = $db->query($sql);
    $nbtotalofrecords = $db->num_rows($result);
}

$sql.= $db->plimit($limit + 1, $offset);

$resql = $db->query($sql);
if ($resql) {
    $num = $db->num_rows($resql);
    
    llxHeader('', $title);
    if ($search_tour_id > 0 && empty($search_tour)) {
        $tour_static = new DoliTour($db);
        $tour_static->fetch($search_tour_id);
        $search_tour = $tour_static->title;
    }

    // Construction des paramètres de pagination
    $param='';
    if ($search_user) $param.='&search_user='.urlencode($search_user);
    if ($search_tour) $param.='&search_tour='.urlencode($search_tour);
    if ($search_tour_id) $param.='&search_tour_id='.urlencode($search_tour_id);

    // Formulaire principal
    print '<form method="POST" id="searchFormList" action="'.$_SERVER["PHP_SELF"].'">';
    print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
    print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
    print '<input type="hidden" name="action" value="list">';
    print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
    print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
    print '<input type="hidden" name="page" value="'.$page.'">';

    // Menu Actions de masse (Supprimer)
    $arrayofmassactions =  array();
    if ($user->rights->dolitour->supprimer) $arrayofmassactions['predelete']=$langs->trans("MassActionDelete");
    if (in_array($massaction, array('presend', 'predelete'))) $arrayofmassactions=array();
    $massactionbutton=$form->selectMassAction('', $arrayofmassactions);

    // Barre de titre
    print_barre_liste($title, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, $massactionbutton, $num, $nbtotalofrecords, 'dolitour@dolitour', 0, '', '', $limit);

    // Confirmation
    if ($massaction == 'predelete') {
         print '<div class="commitrequest" style="margin: 15px 0; padding: 15px; border-left: 5px solid #E00; background-color: #fffbf0; border-radius: 4px; box-shadow: 0 2px 5px rgba(0,0,0,0.05);">';
        print '<div style="font-weight: bold; font-size: 1.1em; margin-bottom: 5px; color: #E00;">Suppression de l\'historique</div>';
        print '<div style="margin-bottom: 10px;">Êtes-vous sûr de vouloir supprimer les logs sélectionnés ?<br>Les utilisateurs concernés reverront le tour si l\'option "Jouer une seule fois" est activée.</div>';
        
        if (is_array($toselect) && count($toselect) > 0) {
            foreach($toselect as $t) {
                print '<input type="hidden" name="toselect[]" value="'.$t.'">';
            }
        }
        // Bouton OUI qui change l'action JS
        print '<button type="submit" class="button" onclick="this.form.querySelector(\'input[name=action]\').value=\'confirm_delete\';">'.$langs->trans("Yes").'</button>';
        
        print ' &nbsp; ';
        print '<a class="button button-cancel" href="'.$_SERVER["PHP_SELF"].'">'.$langs->trans("No").'</a>';
        print '<input type="hidden" name="confirm" value="yes">'; 
        print '</div>';
    }
    
    $varpage = empty($contextpage) ? $_SERVER["PHP_SELF"] : $contextpage;
    $selectedfields = $form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage);
    
    if ($massactionbutton) {
    $selectedfields .= $form->showCheckAddButtons('checkforselect', 1);
    }

    print '<div class="div-table-responsive">';

    print '<table class="tagtable liste">';

    // Filtres
    print '<tr class="liste_titre_filter">';
    
    // Filtre Dates 
    if (! empty($arrayfields['l.tms']['checked'])) {
        print '<td class="liste_titre">';
        print '<div class="nowrap">';
        // On ajoute 'search_' devant le 's' pour que le champ s'appelle 'search_sday'
        print $form->selectDate($search_sday ? dol_mktime(0, 0, 0, $search_smonth, $search_sday, $search_syear) : '', 'search_s', 0, 0, 1, '', 1, 0, 0, 0, '', '', '', 1, '', $langs->trans('From'));
        print '</div><div class="nowrap">';
        // Idem pour la date de fin
        print $form->selectDate($search_eday ? dol_mktime(0, 0, 0, $search_emonth, $search_eday, $search_eyear) : '', 'search_e', 0, 0, 1, '', 1, 0, 0, 0, '', '', '', 1, '', $langs->trans('to'));
        print '</div>';
        print '</td>';
    }

    // Filtre User
    if (! empty($arrayfields['u.lastname']['checked'])) {
        print '<td class="liste_titre"><input class="flat" type="text" name="search_user" value="'.$search_user.'"></td>';
    }

    // Filtre Tour 
    if (! empty($arrayfields['t.title']['checked'])) {
        print '<td class="liste_titre"><input class="flat" type="text" name="search_tour" value="'.$search_tour.'"></td>';
    }

    // Action
    print '<td class="liste_titre" align="middle">';
    $searchpicto=$form->showFilterButtons();
    print $searchpicto;
    print '</td>';
    print '</tr>';

    // Ligne des Titres de colonnes 
    print '<tr class="liste_titre">';
    
    if (! empty($arrayfields['l.tms']['checked']))      print_liste_field_titre($arrayfields['l.tms']['label'], $_SERVER["PHP_SELF"], "l.tms", "", $param, "", $sortfield, $sortorder);
    if (! empty($arrayfields['u.lastname']['checked'])) print_liste_field_titre($arrayfields['u.lastname']['label'], $_SERVER["PHP_SELF"], "u.lastname", "", $param, "", $sortfield, $sortorder);
    if (! empty($arrayfields['t.title']['checked']))    print_liste_field_titre($arrayfields['t.title']['label'], $_SERVER["PHP_SELF"], "t.title", "", $param, "", $sortfield, $sortorder);
    
    // Le bouton de sélection s'affiche ici ($selectedfields)
    print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"],"",'','','align="center"',$sortfield,$sortorder,'maxwidthsearch ');
    print '</tr>';

    // Boucle d'affichage 
    $i = 0;
    while ($i < min($num, $limit)) {
        $obj = $db->fetch_object($resql);
        
        print '<tr class="oddeven">';
        
        // Date
        if (! empty($arrayfields['l.tms']['checked'])) {
            print '<td>'.dol_print_date($db->jdate($obj->date_view), 'dayhour').'</td>';
        }

        // Utilisateur 
        if (! empty($arrayfields['u.lastname']['checked'])) {
            print '<td>';
            if ($obj->uid) {
                $user_static = new User($db);
                $user_static->fetch($obj->uid);
                print $user_static->getNomUrl(1);
            } else {
                print '<span class="opacitymedium">Utilisateur inconnu (ID: '.$obj->fk_user.')</span>';
            }
            print '</td>';
        }

        // Tour 
        if (! empty($arrayfields['t.title']['checked'])) {
            print '<td>';
            if ($obj->tid) {
                print '<a href="card.php?id='.$obj->tid.'">';
                print img_picto('', 'dolitour@dolitour') . ' ' . $obj->title;
                print '</a>';
            } else {
                print '<span class="opacitymedium">Tour supprimé</span>';
            }
            print '</td>';
        }

        // Checkbox Mass Action
        print '<td align="center">';
        if ($massactionbutton || $massaction) {
            $selected=0;
            if (in_array($obj->rowid, (is_array($toselect)?$toselect:array()))) $selected=1;
            print '<input id="cb'.$obj->rowid.'" class="flat checkforselect" type="checkbox" name="toselect[]" value="'.$obj->rowid.'"'.($selected?' checked="checked"':'').'>';
        }
        print '</td>';

        print '</tr>';
        $i++;
    }

    if ($num == 0) {
        $colspan = 1; 
        foreach($arrayfields as $val) { if(!empty($val['checked'])) $colspan++; }
        
        print '<tr><td colspan="'.$colspan.'" class="opacitymedium">Aucun historique de visite trouvé.</td></tr>';
    }

    print '</table>';
    print '</div>';
    print '</form>';
}
else {
    dol_print_error($db);
}

llxFooter();
$db->close();