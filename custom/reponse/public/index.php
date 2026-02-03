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
require_once DOL_DOCUMENT_ROOT.'/core/lib/security2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/CMailFile.class.php';

dol_include_once("/reponse/class/site.class.php");
dol_include_once("/reponse/class/reponse.class.php");
dol_include_once("/questionnaire/class/questionnaire.class.php");


$langs->loadLangs(array('main', 'errors'));
$langs->load('reponse@reponse');
$langs->load("other");

$reponse = new Reponse($db);
$questionnaire = new Questionnaire($db);
$ref_form = GETPOST('form-ref', 'alpha');
$fk_questionnaire = 0;
	if (empty($ref_form)) {
    // Get default form
     $fk_questionnaire = $questionnaire->get_default();
    }
$questionnaire->fetch($fk_questionnaire, $ref_form);
$site = new Site($db);
$site->start($user);

$openConfirmationModal = false;

if (GETPOST('action', 'aZ09') == 'login')
{
	$site->login($user);
}

if (GETPOST('action', 'aZ09') == 'register')
{
	$site->register($user);
}

if (GETPOST('action', 'aZ09') == 'passrequest')
{
	if ($site->passwordrequest($user) > 0) {
		$openConfirmationModal = true;
	}
}

if (GETPOST('action', 'aZ09') == 'passvalidation')
{
	$site->passwordvalidation($user);
}

$reponses = $user->isLoggedIn ? $reponse->user($user->id) : array();


if ($user->isLoggedIn && empty($user->rights->reponse->lire))
{
    $site->addWarning($langs->trans('AccessNotAllowed'));
}

?>
<?php $reponse->include_once('tpl/layouts/header.tpl.php'); ?>

<?php $reponse->include_once('tpl/layouts/error.tpl.php'); ?>

<?php //$reponse->include_once('tpl/layouts/content.tpl.php'); ?>

<?php if (!empty($user->rights->reponse->lire)): ?>

<!--Affichage des questionnaires et des liens-->
<div class="section">
	<div class="container">
		<div class="row justify-content-center align-items-center">
			<div class="col-10 col-md-10 col-lg-8 text-center">
				<?php
				$liste = $questionnaire->liste_array();
				foreach ($liste as $qid=> $form) {
					$questionnaire->fetch($qid);
					echo $questionnaire->getNomUrl();
				}
				
				?>
				
				<a href="<?php echo $site->makeUrl('report.php?action=create'); ?>" class="btn btn-block btn-success mr-3 animate-up-2"><?php echo $langs->trans('ReponseIMakeReport'); ?></a>

				
			</div>
		</div>
	</div>
</div>


<!--TODO ajouter une option globale pour indiquer si on affiche le formulaire par défaut-->
<div class="section">
	<div class="container">
		<div class="row justify-content-center align-items-center">
			<div class="col-10 col-md-10 col-lg-8 text-center">
				<h2 class="h1 mb-5 font-weight-light">
				<?php echo count($reponses) > 0 ? $langs->trans('ReponseMakeReportTitle') : $langs->trans('ReponseMakeFirstReportTitle'); ?>
				</h2>

				<a href="<?php echo $site->makeUrl('report.php?action=create'); ?>" class="btn btn-block btn-success mr-3 animate-up-2"><?php echo $langs->trans('ReponseIMakeReport'); ?></a>

				<p class="mt-5 lead"><?php echo $langs->trans('ReponseMakeReportDetails'); ?></p>
			</div>
		</div>
	</div>
</div>

<?php endif; ?>
<?php 
$reponse->include_once('tpl/layouts/footer.tpl.php',
	array('questionnaire'=>$questionnaire,)
	); 
?>