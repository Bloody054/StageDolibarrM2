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

if (!$user->isLoggedIn) {
    $site->addError($langs->trans('AccessNotAllowed'));
} else {
    if (GETPOST('action', 'alpha') == 'save')
    {
        $email = GETPOST('email', 'alpha');
        $firstname = GETPOST('firstname', 'alpha');
        $lastname = GETPOST('lastname', 'alpha');
        $password = GETPOST('password', 'alpha');

        if ($site->checkEmail($email) > 0)
        {
            $user->email = $email;
            $user->firstname = $firstname;
            $user->lastname = $lastname;
            if (!empty($password))
            {
                $user->pass = $password;
            }

            if ($user->update($user) >= 0)
            {
                $site->addMessage($langs->trans('ReponseAccountUpdated'));
            }
        }
    }
}
?>
<?php $reponse->include_once('tpl/layouts/header.tpl.php'); ?>

<?php $reponse->include_once('tpl/layouts/error.tpl.php'); ?>

<?php if ($user->isLoggedIn): ?>

<div class="section">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-10 col-md-10 col-lg-8 text-center">
                <h2 class="h1 mb-5 font-weight-light"><?php echo $langs->trans('ReponseMyProfile'); ?></h2>
                <div class="row">
                    <div class="col-lg-12 text-left">
                        <div>
                            <h1 class="h5 mb-4"><?php echo $langs->trans('ReponseUserInformation'); ?></h1>
							<form id="register" name="register" action="<?php echo $site->makeUrl('account.php'); ?>" method="post">
								<input type="hidden" name="token" value="<?php echo $_SESSION['newtoken']; ?>" />
								<input type="hidden" name="action" value="save">                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="h6" for="firstname"><?php echo $langs->trans('ReponseFirstName'); ?></label>
                                            <input class="form-control" id="firstname" name="firstname" type="text" value="<?php echo $user->firstname; ?>" required />
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="h6" for="lastname"><?php echo $langs->trans('ReponseLastName'); ?></label>
                                            <input class="form-control" id="lastname" name="lastname" type="text" value="<?php echo $user->lastname; ?>" required />
                                        </div>
                                    </div>
                                </div>
                                <h2 class="h5 mt-5 mb-4"><?php echo $langs->trans('ReponseAccountInformation'); ?></h2>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="h6" for="email"><?php echo $langs->trans('ReponseEmail'); ?></label>
                                            <input class="form-control" id="email" name="email" type="email" value="<?php echo $user->email; ?>" required />
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="h6" for="password"><?php echo $langs->trans('ReponsePassword'); ?></label>
                                            <input class="form-control" id="password" name="password" type="password" />
                                            <small class="form-text text-muted mt-2"><?php echo $langs->trans('ReponsePlaceHolder'); ?></small>

                                        </div>
                                    </div>
                                </div>

                                <div class="mt-3 text-center">
                                    <button type="submit"  class="btn btn-block btn-success"><?php echo $langs->trans('ReponseSave'); ?></button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php endif; ?>

<?php $reponse->include_once('tpl/layouts/footer.tpl.php'); ?>