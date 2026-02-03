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

$reponse = new Reponse($db);

$site = new Site($db);
$site->start($user);


$reponses = $user->isLoggedIn ? $reponse->user($user->id) : array();

?>
<?php $reponse->include_once('tpl/layouts/header.tpl.php'); ?>

<?php $reponse->include_once('tpl/layouts/error.tpl.php'); ?>

<div class="section">
	<div class="container">
		<div class="row justify-content-center align-items-center">
			<div class="col-10 col-md-10 col-lg-8 text-center">
                <h2 class="h1 mb-5 font-weight-light"><?php echo $langs->trans('ReponseHistory'); ?></h2>
                <table class="table table-hover">
                    <?php if (count($reponses)): ?>
                        <?php foreach ($reponses as $s): ?>
                        <tr>
                            <td scope="row">
                                <div class="row">
                                    <div class="col-6 text-left text-muted"><?php echo  $langs->trans('ReponseHistoryRef', $s->ref); ?></div>
                                    <div class="col-6 text-right font-weight-bold"><?php echo dol_print_date($s->date, '%A %d %B %Y'); ?></div>
                                </div>
                                <div class="row">
                                    <div class="col-6 text-left font-weight-bold"><?php echo ($s->name ? $s->name : ""); ?></div>
                                    <div class="col-6 text-right">
                                        <?php if (!empty($s->location)): ?>
                                            <i class="fas fa-map-marker mr-2"></i><?php echo  $langs->trans('ReponseHistoryLocation', $s->location); ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td scope="row">
                                <?php echo  $langs->trans('ReponseHistoryIsEmpty'); ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </table>
			</div>
		</div>
	</div>
</div>


<?php $reponse->include_once('tpl/layouts/footer.tpl.php'); ?>