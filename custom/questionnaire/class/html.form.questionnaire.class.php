<?php
/* Copyright (C) 2004-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2017 Mikael Carlavan <contact@mika-carl.fr>
 * Copyright (C) 2024 Julien Marchand <julien.marchand@iouston.com>
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
 *  \file       htdocs/questionnaire/class/html.form.questionnaire.class.php
 *  \ingroup    questionnaire
 *  \brief      File of class to manage form for questionnaire
 */

require_once DOL_DOCUMENT_ROOT."/core/lib/functions2.lib.php";
require_once DOL_DOCUMENT_ROOT . '/core/class/doleditor.class.php';

dol_include_once("/questionnaire/class/questionnaire.class.php");
dol_include_once("/reponse/class/reponse.class.php");
dol_include_once('/product/class/product.class.php');
dol_include_once('/product/stock/class/entrepot.class.php');


class QuestionnaireForm
{
    var $db;
    var $error;


    var $cache = array();

    /**
     * Constructor
     * @param      $DB      Database handler
     */
	function __construct($DB)
    {
        $this->db = $DB;
    }

    /**
     *    Check if condition is satisfied to display page
     *    @return    bool           true if ok, else otherwise
     */

    function isConditionSatisfied($report, $line, $values = array())
    {
        global $user;

        $conditionSatisfied = true;
        
        $condition = $line->fk_op_cond;

        if ($condition == Questionnaire::CONDITION_ALWAYS) {
            $conditionSatisfied = true;
        } else if ($line->visibility && $user->rights->questionnaire->lire_extra) {
            $conditionSatisfied = true;
        } else {
            $lines = $report->lines;

            $questionnaire = new Questionnaire($this->db);
            $questionnaire->fetch($report->fk_questionnaire);
            $questionnairelines = $questionnaire->getFormLines();

            $conditionFormItem = null;
            if ($line->fk_cond > 0) {
                $conditionFormItemCode = isset($questionnairelines[$line->fk_cond]) ? $questionnairelines[$line->fk_cond] : null;
                $conditionFormItem = $conditionFormItemCode ? (isset($lines[$conditionFormItemCode]) ? $lines[$conditionFormItemCode] : null) : null;
            }

            $conditionValue = $line->val_cond;

            if ($conditionFormItem) {
                $value = isset($values[$conditionFormItem->code]) ? $values[$conditionFormItem->code] : null;


                switch ($condition) {
                    case Questionnaire::CONDITION_LESS:
                        $conditionSatisfied = $value < $conditionValue;
                        break;
                    case Questionnaire::CONDITION_LESS_OR_EQUAL:
                        $conditionSatisfied = $value <= $conditionValue;
                        break;
                    case Questionnaire::CONDITION_EQUAL:
                        $conditionSatisfied = $value == $conditionValue;
                        break;
                    case Questionnaire::CONDITION_GREATER:
                        $conditionSatisfied = $value > $conditionValue;
                        break;
                    case Questionnaire::CONDITION_GREATER_OR_EQUAL:

                        $conditionSatisfied = $value >= $conditionValue;
                        break;
                    case Questionnaire::CONDITION_DIFFERENT:
                        $conditionSatisfied = $value != $conditionValue;
                        break;
                    case Questionnaire::CONDITION_EMPTY:
                        $conditionSatisfied = empty($value);
                        break;
                    case Questionnaire::CONDITION_NOT_EMPTY:
                        $conditionSatisfied = !empty($value);
                        break;
                    case Questionnaire::CONDITION_NOT_EMPTY_DISPLAYED:
                        $condFormItem = isset($lines[$conditionFormItem->code]) ? $lines[$conditionFormItem->code] : null;

                        if ($condFormItem) {
                            $conditionSatisfied = $this->isConditionSatisfied($report, $condFormItem, $values) && !empty($value);
                        }
                        break;
                    case Questionnaire::CONDITION_EMPTY_DISPLAYED:
                        $condFormItem = isset($lines[$conditionFormItem->code]) ? $lines[$conditionFormItem->code] : null;

                        if ($condFormItem) {
                            $conditionSatisfied = $this->isConditionSatisfied($report, $condFormItem, $values) && empty($value);
                        }
                        break;
                    case Questionnaire::CONDITION_ALWAYS:
                    default:
                        $conditionSatisfied = true;
                        break;
                }
            }
        }

        return $conditionSatisfied;
    }

    /**
     *    Return value from field prefill
     *    @return    string           Result
     */

    function getPostFillValue($postfill, $user, $values)
    {
        global $conf, $langs;

        $value = "";

        if (!empty($postfill)) {
            $components = explode('.', $postfill);

            if (count($components) > 1) {
                $obj = $components[0];
                $attribute = $components[count($components)-1];

                if ($obj == "siret") {

                    switch ($attribute) {
                        case "nom_complet" :
                            $value = ucfirst($values['siret.nom_complet']);
                        break;
                        case "adresse" :
                            $value=ucwords($values['siret.adresse']);
                        break;
                        case "code_postal" :
                            $value = $values['siret.code_postal'];
                        break;
                        case "libelle_commune" :
                            $value = ucfirst($values['siret.libelle_commune']);
                        break;
                        case "code_ape" :
                            $value = $values['siret.code_ape'];
                        break;
                        case "code_siren" :
                            $value = $values['siret.code_siren'];
                        break;
                        case "dirigeant_nom" :
                            $value = ucfirst($values['siret.dirigeant_nom']);
                        break;
                        case "dirigeant_prenom" :
                            $value = ucfirst($values['siret.dirigeant_prenom']);
                        break;
                        case "est_association" :
                            $value = $values['siret.est_association'];
                        break;
                        case "est_bio" :
                            $value = $values['siret.est_bio'];
                        break;
                        case "est_entrepreneur_individuel" :
                            $value = $values['siret.est_entrepreneur_individuel'];
                        break;
                        case "est_rge" :
                            $value = $values['siret.est_rge'];
                        break;
                        case "identifiant_association" :
                            $value = $values['siret.identifiant_association'];
                        break;
                        case "latlong" :
                            $value = $values['siret.latlong'];
                        break;
                        case "tva_number" :
                            $value = $values['siret.tva_number'];
                        break;
                    }
                } else if ($obj == 'societe' || $obj == 'projet') {
                    $key = $obj.'.'.$attribute;
                    $value = $values[$key] ?? '';
                }
            } else {
                $value = $postfill;
            }
        }

        return $value;
    }

    /**
     *    Return value from prefill
     *    @return    string           Result
     */

    function getPrefillValue($prefill, $user)
    {
        global $conf, $langs;
   
        $value = "";

        if (!empty($prefill)) {
            $components = explode('.', $prefill);

            if (count($components) > 1) {
                $obj = $components[0];
                $attribute = $components[count($components)-1];

                if ($obj == "user" && $user->isLoggedIn) {
                    switch ($attribute) {
                        case "firstname": 
                            $value = $user->firstname;
                        break;
                        case "lastname":
                            $value = $user->lastname;
                        break;
                        case "fullname":
                            $value = $user->getFullName($langs);
                        break;
                       case  "email":
                            $value = $user->email;
                       break;
                    }
                } else if ($obj == "date") {
                    switch ($attribute) {
                        case "now" : 
                            $value = dol_now();
                        break;
                    }
                } else if ($obj == "app") {
                    switch ($attribute) {
                        case "os" : 
                            $value = "Web ".$conf->global->CONNECT_API_VERSION;
                        break;
                    }
                }
            } else {
                $value = $prefill;
            }
        }

        return $value;
    }

    function select_line($selected='',$htmlname='fk_line', $htmloption='', $empty = true)
    {
        global $conf,$langs;

        $out = '';

        $sql = "SELECT l.rowid as id, l.code, l.fk_line, l.label, l.type, l.param, l.visibility, l.mandatory, l.crypted, l.inapp, f.ref, f.title";
        $sql .= " FROM " . MAIN_DB_PREFIX . "questionnairedet as l";
        $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "questionnaire as f ON f.rowid = l.fk_questionnaire";
        $sql .= " WHERE f.entity IN (" . getEntity('questionnaire') . ")"; // Dont't use entity if you use rowid
        $sql .= " AND l.fk_line = 0";
        $sql .= " ORDER BY l.label ASC";

        dol_syslog(get_class($this)."::select_line", LOG_DEBUG);
        $resql=$this->db->query($sql);
        if ($resql)
        {
            $out .= '<select class="flat" id = "'.$htmlname.'" name = "'.$htmlname.'" '.$htmloption.'>';
            if ($empty)
                    {
                        $out .= '<option value="" '.(empty($selected) ? 'selected="selected"' : '').'>&nbsp;</option>';  
                    }

            $num = $this->db->num_rows($resql);
            $i = 0;
            if ($num) {
                while ($i < $num) {
                    $obj = $this->db->fetch_object($resql);

                    //Build select
                    $out .= '<option value="'.$obj->id.'" title="'.$obj->code.' - '.$obj->type.'" '.($selected == $obj->id ? 'selected="selected"' : '').'>'.$obj->label.' - '.$obj->ref.'</option>';
                
                    $i++;
                }
            }
            $out .= '</select>';
        }
        else
        {
            dol_print_error($this->db);
        }

        // Make select dynamic
        include_once DOL_DOCUMENT_ROOT . '/core/lib/ajax.lib.php';
        $out .= ajax_combobox($htmlname);

        return $out;
    }

    /**
     *    Return combo list of prefill
     *    @param     selected         Id preselected order type
     *    @param     htmlname         Name of html select object
     *    @param     htmloption       Options html on select object
     *    @return    string           HTML string with select
     */
    function select_postfill($selected='', $htmlname='postfill', $htmloption='', $empty = false)
    {
        global $conf, $langs;

        $questionnaire = new Questionnaire($this->db);

        $postfills = $questionnaire->getPostFills();
        
        //Build select
        $select = '<select class="flat" id = "'.$htmlname.'" name = "'.$htmlname.'" '.$htmloption.'>';
        if ($empty)
        {
            $select .= '<option value="" '.(empty($selected) ? 'selected="selected"' : '').'>&nbsp;</option>';  
        }

        $found = false;
        foreach ($postfills as $group)
        {
            $select .= '<optgroup label="'.$group['label'].'">';

            if (count($group['postfills'])) {
                foreach ($group['postfills'] as $id => $prefill) {
                    if ($id == $selected) {
                        $found = true;
                    }
                    $select .= '<option value="'.$id.'" '.($id == $selected ? 'selected="selected"' : '').'>'.$prefill.'</option>';
                }
            }

            $select .= '</optgroup>';
        }
        
        $select .= '</select><br /><input type="text" class="flat" name="postfill_value" value="'.(!$found ? $selected : '').'" />';

        return $select;
    }

    /**
     *    Return combo list of prefill
     *    @param     selected         Id preselected order type
     *    @param     htmlname         Name of html select object
     *    @param     htmloption       Options html on select object
     *    @return    string           HTML string with select
     */
    function select_prefill($selected='', $htmlname='prefill', $htmloption='', $empty = false)
    {
        global $conf, $langs;

        $questionnaire = new Questionnaire($this->db);

        $prefills = $questionnaire->getPreFills();
        
        //Build select
        $select = '<select class="flat" id = "'.$htmlname.'" name = "'.$htmlname.'" '.$htmloption.'>';
        if ($empty)
        {
            $select .= '<option value="" '.(empty($selected) ? 'selected="selected"' : '').'>&nbsp;</option>';  
        }

        $found = false;
        foreach ($prefills as $group)
        {
            $select .= '<optgroup label="'.$group['label'].'">';

            if (count($group['prefills'])) {
                foreach ($group['prefills'] as $id => $prefill) {
                    if ($id == $selected) {
                        $found = true;
                    }
                    $select .= '<option value="'.$id.'" '.($id == $selected ? 'selected="selected"' : '').'>'.$prefill.'</option>';
                }
            }

            $select .= '</optgroup>';
        }

        $select .= '</select><br /><input type="text" class="flat" name="prefill_value" value="'.(!$found ? $selected : '').'" />';

        return $select;
    }

    /**
     *    Return combo list of types
     *    @param     selected         Id preselected order type
     *    @param     htmlname         Name of html select object
     *    @param     htmloption       Options html on select object
     *    @return    string           HTML string with select
     */
    function select_type($selected='', $htmlname='type', $htmloption='', $empty = false)
    {
        global $conf, $langs;

        $questionnaire = new Questionnaire($this->db);

        $types = $questionnaire->getTypes();

        natsort($types);
        
        //Build select
        $select = '<select class="flat" id = "'.$htmlname.'" name = "'.$htmlname.'" '.$htmloption.'>';
        if ($empty)
        {
            $select .= '<option value="" '.(empty($selected) ? 'selected="selected"' : '').'>&nbsp;</option>';  
        }
        foreach ($types as $id => $type)
        {
            $select .= '<option value="'.$id.'" '.($id == $selected ? 'selected="selected"' : '').'>'.$type.'</option>';
        }
        
        $select .= '</select>';

        // Make select dynamic
        include_once DOL_DOCUMENT_ROOT . '/core/lib/ajax.lib.php';
        $select .= ajax_combobox($htmlname);

        return $select;
    }

        /**
     *    Return combo list of operators
     *    @param     selected         Id preselected order type
     *    @param     htmlname         Name of html select object
     *    @param     htmloption       Options html on select object
     *    @return    string           HTML string with select
     */
    function select_operator($selected='', $htmlname='fk_op_cond', $htmloption='', $empty = false)
    {
        global $conf, $langs;

        $questionnaire = new Questionnaire($this->db);

        $operators = $questionnaire->getOperators();
        
        //Build select
        $select = '<select class="flat" id = "'.$htmlname.'" name = "'.$htmlname.'" '.$htmloption.'>';
        if ($empty)
        {
            $select .= '<option value="" '.(empty($selected) ? 'selected="selected"' : '').'>&nbsp;</option>';  
        }
        foreach ($operators as $id => $operator)
        {
            $select .= '<option value="'.$id.'" '.($id == $selected ? 'selected="selected"' : '').'>'.$operator.'</option>';
        }
        
        $select .= '</select>';

        return $select;
    }

    /**
     *    Return edit field
     *  
     */
    function editField($line, $mode = 'add')
    {
        global $conf, $langs, $user;

        $form = new Form($this->db);

        $value = '';

        $canedit = true;

        if (!empty($line->crypted) && strpos($line->value, ':') !== false && !$user->rights->reponse->decrypter) {
            $canedit = false;
        }

        $loading = false;

        if (strpos($line->code, '_ville') !== false || strpos($line->code, '_departement') !== false || strpos($line->code, '_region') !== false || strpos($line->code, '_pays') !== false || strpos($line->code, '_code_postal') !== false) {
            $loading = true;
        }

        if ($canedit) {

            $object = new Reponse($this->db);
            $object->fetch($line->fk_reponse);
            $params = $object->fetchParameters($line->param);

            switch ($line->type) {
                
                case 'file':

                    $values = $line->value ? explode(',', $line->value) : array();        
                    $dir = dol_sanitizeFileName($object->ref);
                    foreach ($values as $val) {
                        $filepath = $dir .'/'. $val;

                        $value .= '<a class="paddingright" href="'.DOL_URL_ROOT.'/document.php?modulepart=reponse';
                        if (! empty($object->entity)) $value .=  '&entity='.$object->entity;
                        $value .=  '&file='.urlencode($filepath);
                        $value .=  '">';
                        $value .=  $val;
                        $value .= '</a>&nbsp;<a class="button" href="'.$_SERVER["PHP_SELF"] . '?id=' . $object->id.'&action=deletefile&urlfile='.urlencode($val).'&lineid='.$line->id.'&token='.newToken().'"><i class="fa fa-minus"></i></a><br />';
                    }

                    $value .= '<input name="'.$line->code.'" type="hidden" class="flat" />';
                    $value .= '<input name="'.$line->code.'[]" id="'.$line->code.'" type="file" class="flat" />&nbsp;<a id="add-'.$line->code.'" class="button"><i class="fa fa-plus"></i></a><br />';

                    $value .= '<script type="text/javascript">'."\r\n";
                    $value .= '$(document).ready(function() {'."\r\n";
                    $value .= ' $("#add-'.$line->code.'").click(function(e){'."\r\n";
                    $value .= '     var $i = $(\'<input name=\"'.$line->code.'[]" type="file" class="flat" />\'); '."\r\n";
                    $value .= '     var $br = $(\'<br />\'); '."\r\n";
                    $value .= '     var $a = $(\'<a class="button"><i class="fa fa-minus"></i></a>\'); '."\r\n";
                    $value .= '     $a.click(function(e){'."\r\n";
                    $value .= '         $i.remove(); '."\r\n";
                    $value .= '         $br.remove(); '."\r\n";
                    $value .= '         $(this).remove(); '."\r\n";
                    $value .= '     }); '."\r\n";
                    $value .= '     $(this).parent(\'td\').append($i); '."\r\n";
                    $value .= '     $(this).parent(\'td\').append($a); '."\r\n";
                    $value .= '     $(this).parent(\'td\').append($br); '."\r\n";
                    $value .= ' }); '."\r\n";
                    $value .= '});'."\r\n";
                    $value .= '</script>';
                    break;

                case 'int':
                case 'numeric':

                case 'sign':
                    $values = $line->value ? explode(',', $line->value) : array();        
                    $dir = dol_sanitizeFileName($object->ref);
                    foreach ($values as $val) {
                        $filepath = $dir .'/'. $val;

                        $value.='<img src="'.DOL_URL_ROOT.'/document.php?modulepart=reponse';
                        if (! empty($object->entity)) $value .=  '&entity='.$object->entity;
                        $value .=  '&file='.urlencode($filepath);
                        $value .=  '" alt="'.$line->value.'"/>';
                        // $value .= '<a class="paddingright" href="'.DOL_URL_ROOT.'/document.php?modulepart=reponse';
                        // if (! empty($object->entity)) $value .=  '&entity='.$object->entity;
                        // $value .=  '&file='.urlencode($filepath);
                        // $value .=  '">';
                        //$value .=  $val;
                        $value .= '</a>&nbsp;<a href="'.$_SERVER["PHP_SELF"] . '?id=' . $object->id.'&action=deletefile&urlfile='.urlencode($val).'&lineid='.$line->id.'&token='.newToken().'">'.img_delete().'</a><br />';
                    }

                    $value .= '<input name="'.$line->code.'" type="hidden" class="flat" />';
                    //$value .= '<input name="'.$line->code.'[]" id="'.$line->code.'" type="file" class="flat" />&nbsp;<a id="add-'.$line->code.'" class="button"><i class="fa fa-plus"></i></a><br />';

                    $value .= '<script type="text/javascript">'."\r\n";
                    $value .= '$(document).ready(function() {'."\r\n";
                    $value .= ' $("#add-'.$line->code.'").click(function(e){'."\r\n";
                    $value .= '     var $i = $(\'<input name=\"'.$line->code.'[]" type="file" class="flat" />\'); '."\r\n";
                    $value .= '     var $br = $(\'<br />\'); '."\r\n";
                    $value .= '     var $a = $(\'<a class="button"><i class="fa fa-minus"></i></a>\'); '."\r\n";
                    $value .= '     $a.click(function(e){'."\r\n";
                    $value .= '         $i.remove(); '."\r\n";
                    $value .= '         $br.remove(); '."\r\n";
                    $value .= '         $(this).remove(); '."\r\n";
                    $value .= '     }); '."\r\n";
                    $value .= '     $(this).parent(\'td\').append($i); '."\r\n";
                    $value .= '     $(this).parent(\'td\').append($a); '."\r\n";
                    $value .= '     $(this).parent(\'td\').append($br); '."\r\n";
                    $value .= ' }); '."\r\n";
                    $value .= '});'."\r\n";
                    $value .= '</script>';
                    break;

                case 'string':
                    $val = GETPOSTISSET($line->code) ? GETPOST($line->code) : $line->value;
                    $value .= '<input size="40" name="'.$line->code.'" id="'.$line->code.'" type="text" class="flat" value="'.$val.'" />';
                    if ($loading) {
                        $value .= '&nbsp;<span id="'.$line->code.'_loading" style="display:inline-block; visibility: hidden;">'.img_picto('', dol_buildpath('/questionnaire/img/loading.gif', 1), ' width="20px"', true).'</span>';
                     }
                    break;

                case 'siret':
                case 'societe':
                case 'projet':
                    $val = GETPOSTISSET($line->code) ? GETPOST($line->code) : $line->value;
                    $value .= '<input size="40" name="'.$line->code.'" id="'.$line->code.'" type="text" minlenght="14" maxlength="14" class="flat" value="'.$val.'" />';

                    if ($loading) {
                        $value .= '&nbsp;<span id="'.$line->code.'_loading" style="display:inline-block; visibility: hidden;">'.img_picto('', dol_buildpath('/questionnaire/img/loading.gif', 1), ' width="20px"', true).'</span>';
                     }
                    break;

                case 'product':
                    $val = GETPOSTISSET($line->code) ? GETPOST($line->code) : $line->value;
                        $products = $form->select_produits_list($val, $line->code, $params->filtertype, 0, 0, $params->filterkey, 1, 2, 1, 0, 1, 1);
                        // $products est trop complexe pour le select array après donc on parse et on simplifie le tableau
                        $productlist = array();
                        foreach ($products as $p){
                            $productlist[$p['key']] = $p['value'];
                        }
                        asort($productlist);
                        //affiche le champ de formulaire
                        print $form->selectarray($line->code, $productlist, $val, 0, 0, 0, '', 0, 80, 0, '', 'minwidth75', 0, '', 0, 1);
                        
                        //affiche le nom du produit et le lien vers
                        $product = new Product($this->db);
                        $product->fetch($val);
                        print ' - '.$product->getnomUrl();
                    
                    if ($loading) {
                        $value .= '&nbsp;<span id="'.$line->code.'_loading" style="display:inline-block; visibility: hidden;">'.img_picto('', dol_buildpath('/questionnaire/img/loading.gif', 1), ' width="20px"', true).'</span>';
                     }
                    break;

                case 'productmultiple':
                   //le json récupéré n'est pas conforme, on le corrige
                    $json = preg_replace('/(\d+):/', '"$1":', $line->value);
                    $vals = json_decode($json, true);
                    $i=1;
                    print '<em>Attention, une modification au niveau de ce champ n\'entrainera pas de modifications au niveau des stocks</em><br>';
                    
                   foreach($vals as $pid=>$qty){
                                            $products = $form->select_produits_list($pid, $line->code, $params->filtertype, 0, 0, $params->filterkey, 1, 2, 1, 0, 1, 1);
                        // $products est trop complexe pour le select array après donc on parse et on simplifie le tableau
                        $productlist = array();
                        foreach ($products as $p){
                            $productlist[$p['key']] = $p['value'];
                        }
                        asort($productlist);
                        //affiche le champ de formulaire
                        print $form->selectarray($line->code.'['.$i.']', $productlist, $pid, 0, 0, 0, '', 0, 80, 0, '', 'minwidth75', 0, '', 0, 1);
                        print '&nbsp;<input type="text" name="'.$line->code.'qty['.$i.']" value="'.$qty.'">';
                        //affiche le nom du produit et le lien vers
                        $product = new Product($this->db);
                        $product->fetch($pid);
                        print ' - '.$product->getnomUrl();
                        print '<br>';
                        $i++;
                   }

                    
                        
                        
                        
                        
                    
                    if ($loading) {
                        $value .= '&nbsp;<span id="'.$line->code.'_loading" style="display:inline-block; visibility: hidden;">'.img_picto('', dol_buildpath('/questionnaire/img/loading.gif', 1), ' width="20px"', true).'</span>';
                     }
                    break;

                case 'text' :
                    $val = GETPOSTISSET($line->code) ? GETPOST($line->code) : $line->value;
                    $doleditor = new DolEditor($line->code, $val, '', 100, 'dolibarr_notes', '', false, true, 0, ROWS_2, '98%');
                    $doleditor->Create();
                    break;

                case 'date':
                case 'datetime':
                    $val = $line->value > 0 ? $this->db->jdate($line->value) : -1;

                    if (GETPOSTISSET($line->code.'day')) {
                        $hour = GETPOST($line->code.'hour', 'int');
                        $minute = GETPOST($line->code.'min', 'int');
                        $day = GETPOST($line->code.'day', 'int');
                        $month = GETPOST($line->code.'month', 'int');
                        $year = GETPOST($line->code.'year', 'int');

                        $val = dol_mktime($hour, $minute, 0, $month, $day, $year);
                    }



                    $hours = $line->type == 'datetime' ? 1 : 0;
                    $minutes = $line->type == 'datetime' ? 1 : 0;

                    //$val = $line->value ? $line->value : dol_now();

                    //$val = $line->value > 0 ? $this->db->jdate($line->value) : -1;
                    $value .= $form->selectDate($val, $line->code, $hours, $minutes, '', '', 1, 1);
                    break;

                case 'table' :
                    $val = GETPOSTISSET($line->code) ? GETPOST($line->code) : $line->value;

                    $options = array();
                    $param = $line->param;
                    if (!empty($param)) {
                        $params = explode(':', $param); // c_typent:libelle:id:parent_list_code|parent_column:filter
                        $table = $params[0];
                        $cond = null;
                        $filter = null;
                        if (count($params) == 1)
                        {
                            $rowid = 'rowid';
                            $label = 'label';
                        }
                        else
                        {
                            $rowid = $params[1];
                            $label = $params[2];
                            if (isset($params[4])) {
                                $cond = $params[3];
                                $filter = $params[4];
                            } else if (isset($params[3])) {
                                // c_typent
                                // libelle
                                // id
                                // parent_list_code|parent_column
                                // filter
                                if (strpos($cond, '|')) {
                                    $cond = $params[3];
                                } else {
                                    $filter = $params[3];
                                }
                            }
                        }

                        // Build where condition
                        $where = null;

                        if (!empty($cond)) {
                            list($code, $col) = explode('|', $cond);

                            if (GETPOSTISSET($code)) {
                                list($code, $col) = explode('|', $filter);

                                if (GETPOSTISSET($code)) {
                                    $where = ($col."=".GETPOST($filter, 'int'));
                                }
                            }
                        }

                        if (!empty($filter)) {
                            $where = empty($where) ? $filter : " AND ".$filter;
                        }

                        $sql = "SELECT $rowid, $label FROM ".MAIN_DB_PREFIX.$table;
                        if ($where) {
                            $sql .= " WHERE ".$where;
                        }

                        $result = $this->db->query($sql);
                        if ($result)
                        {
                            $num = $this->db->num_rows($result);

                            $i = 0;
                            while ($i < $num)
                            {
                                $objp = $this->db->fetch_object($result);
                                $options[$objp->{$rowid}] = $objp->{$label};
                                $i++;
                            }
                        }
                    }


                    $value .= $form->selectarray($line->code, $options, $val, 1, 0, 0, '', 0, 0, 0, '', '', 1);
                    if ($loading) {
                        $value .= '&nbsp;<span id="'.$line->code.'_loading" style="display:inline-block; visibility: hidden;">'.img_picto('', dol_buildpath('/questionnaire/img/loading.gif', 1), ' width="20px"', true).'</span>';
                    }
                    break;

                case 'list' :
                case 'radio' :
                case 'checkbox' :
                    $options = array();
                    $params = explode("\r\n", $line->param);
                    if (count($params)) {
                        foreach ($params as $param) {
                            if (!empty($param) && strpos($param, ',') !== null) {
                                $paramsArray = explode(',', $param);
                                if (count($paramsArray) == 2) {
                                    $val = $paramsArray[0];
                                    $label = $paramsArray[1];
                                } else {
                                    $val = $paramsArray[0];
                                    $label = $paramsArray[2];
                                }

                                $options[$val] = $label;
                            }

                        }
                    }

                    if ($line->type == 'radio') {
                        $v = GETPOSTISSET($line->code) ? GETPOST($line->code) : $line->value;

                        if (count($options)) {
                            foreach ($options as $val => $label) {
                                $value .= '<input type="radio" name="'.$line->code.'" value="'.$val.'" '.($v == $val ? 'checked' : '').'/>&nbsp;<label for="'.$line->code.'">'.$label.'</label><br />';
                            }
                        }
                    } else if ($line->type == 'list') {
                        $val = GETPOSTISSET($line->code) ? GETPOST($line->code) : $line->value;

                        $value .= $form->selectarray($line->code, $options, $val, 1, 0, 0, '', 0, 0, 0, '', '', 1);
                    } else {
                        if (count($options)) {
                            $values = GETPOSTISSET($line->code) ? GETPOST($line->code, 'array') : ($line->value ? explode(',', $line->value) : array());
                            foreach ($options as $val => $label) {
                                $value .= '<input type="checkbox" name="'.$line->code.'[]" value="'.$val.'" '.(in_array($val, $values) ? 'checked' : '').'/>&nbsp;<label for="'.$line->code.'">'.$label.'</label><br />';
                            }
                        }
                    }

                    if ($loading) {
                        $value .= '&nbsp;<span id="'.$line->code.'_loading" style="display:inline-block; visibility: hidden;">'.img_picto('', dol_buildpath('/questionnaire/img/loading.gif', 1), ' width="20px"', true).'</span>';
                    }

                    break;

                case 'user':
                    $val = GETPOSTISSET($line->code) ? GETPOST($line->code) : $line->value;
                        $user = new User($this->db);      
                        if(empty($val) && !empty($params->selected)){
                            $selected = $params->selected;
                        }else{
                            if(is_array($val)){ // $val est un tableau
                                $selected = $val;
                            }else if(strpos($val,',')){ // $val est un string avec ,
                                $array_vals = explode(',',$val); //passe sous forme de array();
                                $selected = $array_vals;          
                            }else{ // si c'est une simple valeur
                                $selected = $val;
                            }
                            $output='';
                            if(is_array($selected)){
                                foreach($selected as $u){
                                    $user->fetch($u);
                                    $output.= ' | '.$user->getnomUrl();
                                }
                            }else{
                                $user->fetch($selected);
                                $ouput =' - '.$user->getnomUrl();
                            }
                            
                            
                        }

                        print $form->select_dolusers($selected, $line->code,1, null, 0, $params->include,$params->enableonly, '', 0, 0, '', 0, '', '', 0, 0, true, 0);
                        print $output;
                                        
                    if ($loading) {
                        $value .= '&nbsp;<span id="'.$line->code.'_loading" style="display:inline-block; visibility: hidden;">'.img_picto('', dol_buildpath('/questionnaire/img/loading.gif', 1), ' width="20px"', true).'</span>';
                     }
                    break;

                case 'warehouse':
                    $entrepot = new entrepot($this->db);
                    $val = GETPOSTISSET($line->code) ? GETPOST($line->code) : $line->value;
                    $status = 1; //valeur par défaut, que les entrepôt actifs.
                        $selectedval = $val;
                        if(is_object($params)){
                            foreach($params as $key=>$p){
                                if($key=='status'){
                                    $status=$p;
                                }
                                if($key=='selected' && empty($val)){
                                    $selectedval = $p;
                                }
                            }
                        }                                                                    
                        $warehouses = $entrepot->list_array($status);                                                                
                        asort($warehouses);
                        
                        //affiche le champ de formulaire
                        print $form->selectarray($line->code, $warehouses, $selectedval, 0, 0, 0, '', 0, 80, 0, '', 'minwidth75', 0, '', 0, 1); 
                        $entrepot->fetch($selectedval);
                        print ' - '.$entrepot->getnomUrl();
                    if ($loading) {
                        $value .= '&nbsp;<span id="'.$line->code.'_loading" style="display:inline-block; visibility: hidden;">'.img_picto('', dol_buildpath('/questionnaire/img/loading.gif', 1), ' width="20px"', true).'</span>';
                     }
                    break;

                case 'map':
                    $val = GETPOSTISSET($line->code) ? GETPOST($line->code) : $line->value;

                    $value .= '<input size="40" name="'.$line->code.'" id="'.$line->code.'" type="text" class="flat" value="'.$val.'" /><br />';

                    /*if ($mode == 'edit') {
                        $value .= '<input name="'.$line->code.'_update_all" id="'.$line->code.'_update_all" type="checkbox" value="1" checked /><label for="'.$line->code.'_update_all"> '.$langs->trans('UpdateOtherFields').'</label><br />';
                    }*/

                    $value .= '<div id="'.$line->code.'-map" class="map" style="width:400px; height:400px; z-index: 200;"></div>';

                    $value .= '<script type="text/javascript">'."\r\n";

                    if ($mode == 'edit' && !empty($val)) {
                        list($lat, $lon) = explode(',', $val);

                        if (!is_numeric($lat)) {
                            $lat = 0;
                        }

                        if (!is_numeric($lon)) {
                            $lon = 0;
                        }
                    } else {
                        $lat = 48.852969;
                        $lon = 2.349903;
                    }

                    $value .= 'var lat = '.$lat.';'."\r\n";
                    $value .= 'var lon = '.$lon.';'."\r\n";

                    $value .= 'var macarte = null;'."\r\n";
                    $value .= 'var marker = null;'."\r\n";

                    $value .= 'function initMap() {'."\r\n";
                    $value .= '    macarte = L.map(\''.$line->code.'-map\').setView([lat, lon], 11);'."\r\n";
                    $value .= '    L.tileLayer(\'https://{s}.tile.openstreetmap.fr/osmfr/{z}/{x}/{y}.png\', {'."\r\n";
                    $value .= '       attribution: \'Données &copy; Contributeurs <a href="http://openstreetmap.org">OpenStreetMap</a> | <a href="https://creativecommons.org/licenses/by/2.0/">CC-BY</a>\','."\r\n";
                    $value .= '       minZoom: 1,'."\r\n";
                    $value .= '       maxZoom: 20'."\r\n";
                    $value .= '   }).addTo(macarte);'."\r\n";
                    $value .= '   macarte.on(\'click\', onMapClick);'."\r\n";

                    if ($val)
                    {
                        $value .= '   marker = L.marker(['.$val.']);'."\r\n";
                        $value .= '   marker.addTo(macarte);'."\r\n";
                    }

                    $value .= '}'."\r\n";
                    $value .= 'function onMapClick(e) {'."\r\n";
                    $value .= '    $("#'.$line->code.'").val(e.latlng.lat + \',\' + e.latlng.lng);'."\r\n";
                    $value .= '    $("#'.$line->code.'_gps").val(e.latlng.lat + \',\' + e.latlng.lng);'."\r\n";

                    $value .= '    if (marker) {'."\r\n";
                    $value .= '         marker.setLatLng(e.latlng);'."\r\n";
                    $value .= '    } else {'."\r\n";
                    $value .= '         marker = L.marker(e.latlng);'."\r\n";
                    $value .= '         marker.addTo(macarte);'."\r\n";
                    $value .= '    }'."\r\n";

                    $value .= '    if (macarte) {'."\r\n";
                    $value .= '         macarte.setView(e.latlng, 11);'."\r\n";
                    $value .= '    }'."\r\n";

                    $value .= '    $("#'.$line->code.'_ville_loading").css(\'visibility\', \'visible\');'."\r\n";
                    $value .= '    $("#'.$line->code.'_departement_loading").css(\'visibility\', \'visible\');'."\r\n";
                    $value .= '    $("#'.$line->code.'_region_loading").css(\'visibility\', \'visible\');'."\r\n";
                    $value .= '    $("#'.$line->code.'_pays_loading").css(\'visibility\', \'visible\');'."\r\n";
                    $value .= '    $("#'.$line->code.'_code_postal_loading").css(\'visibility\', \'visible\');'."\r\n";

                    $value .= '    $.ajax({'."\r\n";
                    $value .= '        url:"'.dol_buildpath('/questionnaire/ajax.php',1).'",'."\r\n";
                    $value .= '        data: {lat: e.latlng.lat, lon: e.latlng.lng},'."\r\n";
                    $value .= '        dataType: \'json\''."\r\n";
                    $value .= '    }).done(function(data) {'."\r\n";
                    $value .= '         $("#'.$line->code.'_ville_loading").css(\'visibility\', \'hidden\');'."\r\n";
                    $value .= '         $("#'.$line->code.'_departement_loading").css(\'visibility\', \'hidden\');'."\r\n";
                    $value .= '         $("#'.$line->code.'_region_loading").css(\'visibility\', \'hidden\');'."\r\n";
                    $value .= '         $("#'.$line->code.'_pays_loading").css(\'visibility\', \'hidden\');'."\r\n";
                    $value .= '         $("#'.$line->code.'_code_postal_loading").css(\'visibility\', \'hidden\');'."\r\n";
                    $value .= '        if(data.error){'."\r\n";
                    $value .= '            alert(data.message);'."\r\n";
                    $value .= '       }else{'."\r\n";
                    $value .= '            $("#'.$line->code.'_ville").val(data.city).trigger(\'change\');'."\r\n";
                    $value .= '            $("#'.$line->code.'_departement").val(data.state).trigger(\'change\');'."\r\n";
                    $value .= '            $("#'.$line->code.'_region").val(data.region).trigger(\'change\');'."\r\n";
                    $value .= '            $("#'.$line->code.'_pays").val(data.country).trigger(\'change\');'."\r\n";
                    $value .= '            $("#'.$line->code.'_cost_postal").val(data.zip).trigger(\'change\');'."\r\n";
                    $value .= '        }'."\r\n";
                    $value .= '    });'."\r\n";
                    $value .= '}'."\r\n";

                    $value .= '$(document).ready(function() {'."\r\n";
                    $value .= ' initMap(); '."\r\n";

                    $value .= ' $("#'.$line->code.'_gps").change(function(e){'."\r\n";
                    $value .= '     var val = $(this).val(); '."\r\n";
                    $value .= '     var coords = val.split(\',\'); '."\r\n";
                    $value .= '     var e = new Object();'."\r\n";
                    $value .= '     e.latlng = new Object();'."\r\n";
                    $value .= '     e.latlng.lat = coords[0];'."\r\n";
                    $value .= '     e.latlng.lng = coords[1];'."\r\n";
                    $value .= '     onMapClick(e);'."\r\n";
                    $value .= ' }); '."\r\n";
                    $value .= '});'."\r\n";
                    $value .= '</script>';

                    break;
            }
        } else {
            $value .= $this->viewField($line);
        }

        
        return $value;
    }

      /**
     *    Return value of field
     *  
     */
    function valueField($line)
    {  
        global $conf, $langs;

        $value = '';

        switch ($line->type) {
            case 'text' :
                $value = dol_nl2br($line->value);
            break;

            case 'siret' :
                $value = $line->value;
            break;

            case 'date':
            case 'datetime':
                $val = $line->value ? $this->db->jdate($line->value) : null;

                $value = dol_print_date($val, $line->type == 'datetime' ? 'dayhour' : 'day');
            break;

            case 'table' :
                $options = array();
                $params = explode(':', $line->param);
                $table = $params[0];
                if (count($params) == 1)
                {
                    $rowid = 'rowid';
                    $label = 'label';
                }
                else 
                {
                    $rowid = $params[1];
                    $label = $params[2];
                }

                $value = !empty($line->value) ? dol_getIdFromCode($this->db, $line->value, $table, $rowid, $label) : '';
            break;
            
            case 'list' :
            case 'radio' :
            case 'checkbox' :
                $options = array();
                $params = explode("\r\n", $line->param);
                if (count($params)) {
                    foreach ($params as $param) {
                        if (!empty($param) && strpos($param, ',') !== null) {
                            $paramsArray = explode(',', $param);
                            if (count($paramsArray) == 2) {
                                $val = $paramsArray[0];
                                $label = $paramsArray[1];
                            } else {
                                $val = $paramsArray[0];
                                $label = $paramsArray[2];                  
                            }
    
                            $options[$val] = $label;
                        }
                    }
                }

                if ($line->type == 'checkbox') {
                    $values = !empty($line->value) ? explode(',', $line->value) : array();
                    if (count($values)) {
                        $optionsval = array();
                        foreach ($values as $val) {
                            if (isset($options[$val])) {
                                $optionsval[] = $options[$val];
                            }
                            //$optionsval[] .= isset($options[$val]) ? $options[$val].'<br />' : '';
                        }

                        $value = implode(', ', $optionsval);
                    }
                } else {
                    $value = isset($options[$line->value]) ? $options[$line->value] : '';
                }
            break;             

            case 'file':

                $values = $line->value ? explode(',', $line->value) : array();

                $object = new Reponse($this->db);
                $object->fetch($line->fk_reponse);


                $dir = $conf->reponse->dir_output.'/'.dol_sanitizeFileName($object->ref);
                $addlinktofullsize = 1;

                foreach ($values as $file) {
                    
                    if (file_exists($dir.'/'.$file)) {
                        $originalfile = dol_sanitizeFileName($object->ref)."/".$file;
                        
                        $a = @getimagesize($dir.'/'.$file);
                        $image_type = isset($a[2]) ? $a[2] : null;
                        
                        $isimage = $image_type ? in_array($image_type, array(IMAGETYPE_GIF , IMAGETYPE_JPEG ,IMAGETYPE_PNG , IMAGETYPE_BMP)) : false;

                        if ($isimage)
                        {
                            $urladvanced = getAdvancedPreviewUrl('reponse', $originalfile, 0, '&entity='.$object->entity);
                            if ($urladvanced) $value .= '<a href="'.$urladvanced.'">';
                            else $value .= '<a href="'.DOL_URL_ROOT.'/viewimage.php?modulepart=reponse&entity='.$object->entity.'&file='.urlencode($originalfile).'&cache=0">';
                            
                            $thumbnail = dol_sanitizeFileName($object->ref)."/".getImageFileNameForSize($file, '_small');
                
                            $value.= '<img class="photoinformation" alt="Photo" id="photologo'.(preg_replace('/[^a-z]/i','_',$file)).'" src="'.DOL_URL_ROOT.'/viewimage.php?modulepart=reponse&entity='.$object->entity.'&file='.urlencode($thumbnail).'&cache=0">';
                            $value.= '</a>';
                        }
                        else
                        {
                            $value .= '<a class="paddingright" href="'.DOL_URL_ROOT.'/document.php?modulepart=reponse';
                            if (! empty($object->entity)) $value .=  '&entity='.$object->entity;
                            $value .=  '&file='.urlencode($originalfile);
                            $value .=  '">';
                            $value .=  $file;
                            $value .=  '</a>&nbsp;';
                        }

                    }

                    $value .=  '&nbsp;';
                    
                }
            break;

            case 'numeric':
                $value = is_null($line->value) || $line->value === '' ? '' : price($line->value);
                break;

            default:
                $value = $line->value;
            break;

        }

        return $value;

    }

    /**
     *    Return view field
     *  
     */
    function viewField($line)
    {
        global $conf, $langs;

        $value = '';

        if ($line->type == 'map') {
            $value .=  '<div id="'.$line->code.'-map" class="map" style="width:400px; height:400px"></div>';

            if (!empty($line->value)) {
                list($lat, $lon) = explode(',', $line->value);
            } else {
                $lat = 48.852969;
                $lon = 2.349903;
            }

            $value .=   '<script type="text/javascript">'."\r\n";
            $value .=   'var lat = '.$lat.';'."\r\n";
            $value .=   'var lon = '.$lon.';'."\r\n";
            $value .=   'var macarte = null;'."\r\n";
            $value .=   'var marker = null;'."\r\n";
        
            $value .=   'function initMap() {'."\r\n";
            $value .=   '    macarte = L.map(\''.$line->code.'-map\').setView([lat, lon], 11);'."\r\n";
            $value .=   '    L.tileLayer(\'https://{s}.tile.openstreetmap.fr/osmfr/{z}/{x}/{y}.png\', {'."\r\n";
            $value .=   '       attribution: \'Données &copy; Contributeurs <a href="http://openstreetmap.org">OpenStreetMap</a> | <a href="https://creativecommons.org/licenses/by/2.0/">CC-BY</a>\','."\r\n";
            $value .=   '       minZoom: 1,'."\r\n";
            $value .=   '       maxZoom: 20'."\r\n";
            $value .=   '   }).addTo(macarte);'."\r\n";

            if ($line->value) 
            {
                $value .=   '   marker = L.marker(['.$line->value.']);'."\r\n";
                $value .=   '   marker.addTo(macarte);'."\r\n";
            }

            $value .=   '}'."\r\n";
            
            $value .=   '$(document).ready(function() {'."\r\n";
            $value .=   ' initMap(); '."\r\n";
            $value .=   '});'."\r\n";
            $value .=   '</script>';
        } else {
            $value = $this->valueField($line);
        }
      
        return $value;
    }

    /**
     *    Return combo list of order types
     *    @param     selected         Id preselected order type
     *    @param     htmlname         Name of html select object
     *    @param     htmloption       Options html on select object
     *    @return    string           HTML string with select
     */
    function select_icon($selected='', $htmlname='icon', $htmloption='', $empty = false)
    {
    global $conf, $langs;
    $questionnaire = new Questionnaire($this->db);
    $icons = $questionnaire->getIcons();

    // conteneur flex pour aligner select + aperçu
    $select = '<div style="display:flex; align-items:center; gap:10px;">';

    // select
    $select .= '<select class="flat" id="'.$htmlname.'" name="'.$htmlname.'" '.$htmloption.'>';
    if ($empty) {
        $select.= '<option value="" data-icon=""></option>';
    }
    if (count($icons)) {
        foreach ($icons as $id => $icon) {
            $select.= '<option value="'.$icon->name.'" data-icon="'.$icon->image.'" '.($icon->name == $selected ? 'selected="selected"': '').'>'.$icon->name.'</option>';
        }
    }
    $select .= '</select>';

    // aperçu à droite
    $select .= '<img id="preview_'.$htmlname.'" src="" alt="aperçu" style="max-width:50px; height:auto; border:1px solid #ccc; padding:2px;">';

    $select .= '</div>'; // fin du flex

    // script
    $select .= '<script>
        const select_'.$htmlname.' = document.getElementById("'.$htmlname.'");
        const preview_'.$htmlname.' = document.getElementById("preview_'.$htmlname.'");

        function updatePreview_'.$htmlname.'() {
            const opt = select_'.$htmlname.'.options[select_'.$htmlname.'.selectedIndex];
            preview_'.$htmlname.'.src = opt.dataset.icon || "";
        }

        select_'.$htmlname.'.addEventListener("change", updatePreview_'.$htmlname.');
        updatePreview_'.$htmlname.'();
    </script>';

    return $select;
    }
}

