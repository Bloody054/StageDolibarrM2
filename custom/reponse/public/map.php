<?php
/* Copyright (C) 2009 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *     	\file       htdocs/reponse/public/index.php
 *		\ingroup    core
 */

define('NOREQUIREMENU', 1);
define('NOLOGIN', 1);

$res=@include("../../main.inc.php");                   // For root directory
if (! $res) $res=@include("../../../main.inc.php");    // For "custom" directory

require_once DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php';
include_once DOL_DOCUMENT_ROOT.'/core/lib/security2.lib.php';

dol_include_once("/reponse/class/site.class.php");
dol_include_once("/reponse/class/reponse.class.php");

$langs->loadLangs(array('main', 'errors'));
$langs->load('reponse@reponse');
$langs->load("other");

$action = GETPOST('action', 'alpha');

$reponse = new Reponse($db);


$hookmanager->initHooks(array('reponsemap'));

$site = new Site($db);
$site->start($user);


$formFilter = null;
$markers = array();
$parameters = array();
$reshook = $hookmanager->executeHooks('loadMarkers', $parameters, $reponse, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook > 0) {
    $markers = $hookmanager->resArray;
}

$reshook = $hookmanager->executeHooks('getFormFilters', $parameters, $reponse, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook > 0) {
    $formFilter = $hookmanager->resPrint;
}
?>
<?php $reponse->include_once('tpl/layouts/header.tpl.php'); ?>

<?php $reponse->include_once('tpl/layouts/error.tpl.php'); ?>

    <div class="section">
        <div class="container-fluid">
            <div class="row justify-content-center align-items-center">
                <?php if ($formFilter): ?>
                <div class="mt-5 col-10 col-md-10 col-lg-10 text-center">
                    <h2><?php echo $langs->trans('ReponseMapFilters'); ?></h2>
                    <form id="filters" name="filters" action="<?php echo $site->makeUrl('map.php'); ?>" method="post">
                        <input type="hidden" name="token" value="<?php echo $_SESSION['newtoken']; ?>" />
                        <?php echo $formFilter; ?>
                    </form>
                </div>
                <?php endif; ?>
                <div class="mt-5 col-12 text-center">
                    <h1 class="h1 mb-5 font-weight-light"><?php echo $langs->trans('ReponseMap'); ?></h1>

                    <div id="map" style="height: 80vh">

                    </div>
                </div>
            </div>
        </div>
    </div>


    <script type="text/javascript">
        let map;

        function onMarkerClick(e) {
            var popup = e.target.getPopup();
            if (popup.isPopupOpen()) {
                popup.closePopup();
            } else {
                popup.openPopup();
            }
        }

        $(document).ready(function(){

            let latitude = 46.232192999999995;
            let longitude = 2.209666999999996;
            let zoom = 6;

            // Init MAP
            map = L.map('map').setView([latitude, longitude], zoom);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
            }).addTo(map);

            <?php if (is_array($markers) && count($markers)): ?>
                <?php foreach ($markers as $marker): ?>
                        var marker = L.marker([<?php echo $marker['gps'][0]; ?>, <?php echo $marker['gps'][1]; ?>]).addTo(map);
                        marker.bindPopup("<?php echo addslashes($marker['content']); ?>");
                        marker.on('click', onMarkerClick);
                <?php endforeach; ?>
            <?php endif; ?>
        });
    </script>

<?php $reponse->include_once('tpl/layouts/footer.tpl.php'); ?>