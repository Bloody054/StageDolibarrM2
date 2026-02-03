<?php
/* Copyright (C) 2004-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2017 Mikael Carlavan <contact@mika-carl.fr>
 * Copyright (C) 2022 Julien Marchand <julien.marchand@iouston.com>
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
 *  \file       htdocs/reponse/class/html.reponse.zones.class.php
 *  \ingroup    zones
 *  \brief      File of class to manage form for reponses
 */
dol_include_once("/reponse/class/reponse.class.php");

class ReponseForm
{
    var $db;
    var $error;

    /**
     * Constructor
     * @param      $DB      Database handler
     */
    function __construct($DB)
    {
        $this->db = $DB;
    }

    /**
     *    Return combo list of commandes
     *    @param     selected         Id preselected products set
     *    @param     htmlname         Name of html select object
     *    @param     htmloption       Options html on select object
     *    @return    string           HTML string with select
     */
    function select_commandes($selected='',$htmlname='fk_commande', $htmloption='', $empty = true)
    {
        global $conf, $langs, $db;
        
        $reponse = new Reponse($db);
        $commandes = $reponse->getCommandes();
        
        //Build select
        $select = '<select class="flat ui-autocomplete-input autocomplete" id = "'.$htmlname.'" name = "'.$htmlname.'" '.$htmloption.'>';
        if ($empty)
        {
            $select .= '<option value="0" '.(empty($selected) ? 'selected="selected"' : '').'>&nbsp;</option>';

        }
        if (is_array($commandes) && sizeof($commandes))
        {
            foreach ($commandes as $id => $commande)
            {
                $select .= '<option value="'.$id.'" '.($id == $selected ? 'selected="selected"' : '').'>'.$commande->ref.' - '.$commande->ref_client.' - '.price($commande->total_ht).'</option>';
            }            
        }

        
        $select .= '</select>';


        return $select;
    }

    /**
     *    Return combo list for reponse selection
     *    @param     selected         Id preselected order type
     *    @param     htmlname         Name of html select object
     *    @param     htmloption       Options html on select object
     *    @return    string           HTML string with select
     */
    function select_reponse($fk_questionnaire, $fieldname, $fieldvalue, $selected='', $htmlname='fk_reponse', $htmloption='')
    {
        global $conf, $langs;
        
        $reponse = new Reponse($this->db);
        $reponses = $reponse->getReponsesbyField($fk_questionnaire,$fieldname,$fieldvalue);

        //Build select
        $select = '<select class="flat ui-autocomplete-input autocomplete" id = "'.$htmlname.'" name = "'.$htmlname.'" '.$htmloption.'>';
        if (count($reponses))
        {
            foreach ($reponses as $rid => $ref)
            {
                $select .= '<option value="'.$rid.'" '.($w == $selected ? 'selected="selected"' : '').'>'.$ref.'</option>';
            }
        }

        
        $select .= '</select>';


        return $select;
    }

    function select_color($selected='',$htmlname='fk_color', $htmloption='', $empty = true){
    //colors designed from pixel.css
    $colors = array(
        '#004666' => 'blue',
        '#00a0b6' =>         'indigo',
        '#6f42c1' =>         'purple',
        '#e83e8c' =>         'pink',
        '#A91E2C' =>         'red',
        '#fd7e14' =>         'orange',
        '#F0B400' =>         'yellow',
        '#18634B' =>         'green',
        '#0056B3' =>         'teal',
        '#17a2b8' =>         'cyan',
        '#ffffff' =>         'white',
        '#93a5be' =>         'gray',
        '#4E5079' =>         'gray-dark',
        '#242e4c' =>         'primary',
        '#1c2540' =>         'secondary',
        '#18634B' =>         'success',
        '#0056B3' =>         'info',
        '#faedc8' =>         'warning',
        '#A91E2C' =>         'danger',
        '#e6e7e8' =>         'light',
        '#1c2540' =>         'dark',
        '#893168' =>         'tertiary',
        '#ffffff' =>         'lighten',
        '#ffffff' =>         'white',
        '#424767' =>         'gray',
        '#ffffff' =>         'neutral',
        '#FAFAFB' =>         'soft',
        '#171f38' =>         'black',
        '#6f42c1' =>         'purple',
        '#ffffff' =>         'gray-100',
        '#fafbfe' =>         'gray-200',
        '#FAFAFB' =>         'gray-300',
        '#e6e7e8' =>         'gray-400',
        '#B7C3D2' =>         'gray-500',
        '#93a5be' =>         'gray-600',
        '#52547a' =>         'gray-700',
        '#4E5079' =>         'gray-800',
    );
    asort($colors);

    //Build select
        $select = '<select class="flat" id = "'.$htmlname.'" name = "'.$htmlname.'" '.$htmloption.'>';
        if ($empty)
        {
            $select .= '<option value="0" '.(empty($selected) ? 'selected="selected"' : '').'>&nbsp;</option>';
        }
        if (is_array($colors) && sizeof($colors))
        {
            foreach ($colors as $value => $label)
            {
                $select .= '<option value="'.$value.'" '.($value == $selected ? 'selected="selected"' : '').' style="background:'.$value.';">'.$label.'</option>';
            }            
        }
        $select .= '</select>';
        return $select;    
    } 

}

?>
