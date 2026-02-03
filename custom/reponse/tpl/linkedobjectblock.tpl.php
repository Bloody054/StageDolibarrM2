<?php
/* Copyright (C) 2010-2011  Regis Houssin <regis.houssin@inodbox.com>
 * Copyright (C) 2013       Juanjo Menent <jmenent@2byte.es>
 * Copyright (C) 2014       Marcos García <marcosgdf@gmail.com>
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
 *  \file		htdocs/reponse/tpl/linkedobjectblock.tpl.php
 *  \ingroup	reponse
 *  \brief		Template to show objects linked to proposals
 */

// Protection to avoid direct call of template
if (empty($conf) || ! is_object($conf))
{
	print "Error, template page can't be called as URL";
	exit;
}

?>

<!-- BEGIN PHP TEMPLATE -->

<?php

global $user;

$langs = $GLOBALS['langs'];
$linkedObjectBlock = $GLOBALS['linkedObjectBlock'];

// Load translation files required by the page
$langs->load("reponse@reponse");

$total=0; $ilink=0;
foreach($linkedObjectBlock as $key => $objectlink)
{
    $ilink++;

    $trclass='oddeven';
    if ($ilink == count($linkedObjectBlock) && empty($noMoreLinkedObjectBlockAfter) && count($linkedObjectBlock) <= 1) $trclass.=' liste_sub_total';
?>
    <tr class="<?php echo $trclass; ?>"  data-element="<?php echo $objectlink->element; ?>"  data-id="<?php echo $objectlink->id; ?>" >
        <td class="linkedcol-element" ><?php echo $langs->trans("Reponse"); ?>
        <?php if(!empty($showImportButton) && $conf->global->MAIN_ENABLE_IMPORT_LINKED_OBJECT_LINES)
            {
                $url = dol_buildpath('/reponse/card.php', 1).'?id='.$objectlink->id;
                print '<a class="objectlinked_importbtn" href="'.$url.'&action=selectlines"  data-element="'.$objectlink->element.'"  data-id="'.$objectlink->id.'"  > <i class="fa fa-indent"></i> </a>';
            }
        ?>
        </td>
        <td class="linkedcol-name" ><?php echo $objectlink->getNomUrl(1); ?></td>
    	<td class="linkedcol-ref" >&nbsp;</td>
    	<td class="linkedcol-date" align="center"><?php echo dol_print_date($objectlink->datec,'day'); ?></td>
    	<td class="linkedcol-amount" align="right">&nbsp;</td>
    	<td class="linkedcol-statut" align="right"><?php echo $objectlink->getLibStatut(3); ?></td>
    	<td class="linkedcol-action" align="right"><a href="<?php echo $_SERVER["PHP_SELF"].'?id='.$object->id.'&action=dellink&dellinkid='.$key; ?>"><?php echo img_picto($langs->transnoentitiesnoconv("RemoveLink"), 'unlink'); ?></a></td>
    </tr>
<?php
}
?>

<!-- END PHP TEMPLATE -->
