<?php
/* Copyright (C) 2004-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2017 Mikael Carlavan <contact@mika-carl.fr>
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
 *  \file       htdocs/questionnaire/index.php
 *  \ingroup    questionnaire
 *  \brief      Page to show product set
 */


$res=@include("../../main.inc.php");                   // For root directory
if (! $res) $res=@include("../../../main.inc.php");    // For "custom" directory


// Libraries
require_once DOL_DOCUMENT_ROOT . "/core/lib/admin.lib.php";
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';

dol_include_once("/questionnaire/lib/questionnaire.lib.php");

// Translations
$langs->load("questionnaire@questionnaire");

// Translations
$langs->load("errors");
$langs->load("admin");
$langs->load("other");

// Access control
if (! $user->admin) {
    accessforbidden();
}

/*
 * View
 */

$form = new Form($db);

llxHeader('', $langs->trans('QuestionnaireUpgrade'));

// Subheader
$linkback = '<a href="' . DOL_URL_ROOT . '/admin/modules.php">'. $langs->trans("BackToModuleList") . '</a>';
print load_fiche_titre($langs->trans('QuestionnaireUpgrade'), $linkback);

// Configuration header
$head = questionnaire_prepare_admin_head();
dol_fiche_head(
    $head,
    'upgrade',
    $langs->trans("ModuleQuestionnaireName"),
    0,
    'questionnaire@questionnaire'
);

// About page goes here
echo $langs->trans("QuestionnaireUpgradePage");

echo '<br />';

print '<h2>'.$langs->trans("Upgrade").'</h2>';
print $langs->trans("QuestionnaireUpgradePageDescLong");

echo '<br />';

print '<button class="flat" name="start" id="start">'.$langs->trans('StartUpgrade').'</button>';

print '<hr />';

print '<div id="log"></div>';

?>

<script type="text/javascript">
    $(document).ready(function(){
        var donep = 0;

        function upgrade() {
            $.ajax({
                url:"<?php echo dol_buildpath('/questionnaire/admin/ajax.php',1) ?>?donep="+donep,
                dataType: 'json'
            }).done(function(data) {
                if(data.error){
                    alert(data.message);
                }else{
                    donep = parseInt(data.donep);

                    $("#log").append(data.message + '<br />');

                    if (!data.ended) {
                        upgrade();
                    }
                }
            });
        }

        $('#start').click(function(e){
            e.preventDefault();
            upgrade();
        });
    });
</script>

<?php

// Page end
dol_fiche_end();
llxFooter();
$db->close();
