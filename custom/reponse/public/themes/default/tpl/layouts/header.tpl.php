<?php
global $dolibarr_main_url_root;
// Displays title
$appli = !empty($conf->global->MAIN_APPLICATION_TITLE) ? $conf->global->MAIN_APPLICATION_TITLE : $conf->global->MAIN_INFO_SOCIETE_NOM;
$titletoshow = dol_htmlentities($appli);

$url = $site->makeUrl('index.php');

$openConfirmationModal = isset($openConfirmationModal) ? $openConfirmationModal : false;

$logo = '';
if (!empty($mysoc->logo_small) && is_readable($conf->mycompany->dir_output . '/logos/thumbs/' . $mysoc->logo_small)) {
    $logo = DOL_URL_ROOT . '/viewimage.php?cache=1&amp;modulepart=mycompany&amp;file=' . urlencode('logos/thumbs/' . $mysoc->logo_small);
} elseif (!empty($mysoc->logo) && is_readable($conf->mycompany->dir_output . '/logos/' . $mysoc->logo)) {
    $logo = DOL_URL_ROOT . '/viewimage.php?cache=1&amp;modulepart=mycompany&amp;file=' . urlencode('logos/' . $mysoc->logo);
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <!-- Primary Meta Tags -->
    <title><?php echo $titletoshow; ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="title" content="<?php echo $appli; ?>">
    <meta name="author" content="<?php echo $conf->global->MAIN_INFO_SOCIETE_NOM; ?>">
    <meta name="description" content="">
    <meta name="keywords" content="" />
    
    <link rel="canonical" href="<?php echo $url; ?>">

    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?php echo $url; ?>">
    <meta property="og:title" content="<?php echo $appli; ?>">
    <meta property="og:description" content="">
    <meta property="og:image" content="<?php echo $logo; ?>">

    <!-- Twitter -->
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:url" content="<?php echo $url; ?>">
    <meta property="twitter:title" content="<?php echo $appli; ?>">
    <meta property="twitter:description" content="">
    <meta property="twitter:image" content="<?php echo $logo; ?>">

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="<?php echo $logo; ?>">
    <meta name="msapplication-TileColor" content="#ffffff">
    <meta name="theme-color" content="#ffffff">

	<!-- Fontawesome -->
	<!--<link type="text/css" href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css" rel="stylesheet">-->
	
    <link type="text/css" href="./themes/default/vendor/@fortawesome/fontawesome-free/css/all.min.css" rel="stylesheet">
    
    <!-- Pixel CSS -->
	<link type="text/css" href="./themes/default/css/pixel.css" rel="stylesheet">
    <link type="text/css" href="./themes/default/vendor/select2/dist/css/select2.min.css" rel="stylesheet">

	<!-- LeafLet CSS -->
	<link rel="stylesheet" href="https://unpkg.com/leaflet@1.6.0/dist/leaflet.css" integrity="sha512-xwE/Az9zrjBIphAcBb3F6JVqxf46+CDLwfLMHloNu6KEQCAWi6HcDUbeOfBIptF7tcCzusKFjFw2yuvEpDL9wQ==" crossorigin=""/>
    <script src="https://unpkg.com/leaflet@1.6.0/dist/leaflet.js" integrity="sha512-gZwIG9x3wUXg2hdXF6+rVkLF/0Vi9U8D2Ntg4Ga5I5BZpVkVxlJWbSQtXPSiUTtC0TjtGOmxa1AJPuV0CPthew==" crossorigin=""></script>

	<!-- Core -->
	<script src="./themes/default/vendor/jquery/dist/jquery.min.js"></script>
    <script src="./themes/default/vendor/popper.js/dist/umd/popper.min.js"></script>
	<script src="./themes/default/vendor/bootstrap/dist/js/bootstrap.min.js"></script>
	<script src="./themes/default/vendor/headroom.js/dist/headroom.min.js"></script>
	
	
	<script src="<?php echo $dolibarr_main_url_root.'/includes/jquery/plugins/select2/select2.min.js'; ?>"></script>
	
	<!--Feuille de style propre au thème chargée en dernier-->
    <link type="text/css" href="./themes/default/css/styles.css" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="<?php echo $dolibarr_main_url_root.'/includes/jquery/plugins/select2/select2.css'; ?>">

</head>

<body>
<style>
[href^='mailto'] {
direction: rtl;
unicode-bidi: bidi-override;
}

.navbar-brand-dark{background:white;}

.scrollable-list {
    width: 100%;
    max-height: 70vh;
    overflow-y: scroll;
    margin-bottom: 1.5rem;
}
</style>

	<?php $this->include_once('tpl/layouts/nav.tpl.php', array(
        'logo' => $logo,
        'url' => $url,
        'appli' => $appli
    )); ?>

	<!-- Modal Content -->
	<div class="modal fade" id="modal-login" tabindex="-1" role="dialog" aria-labelledby="modal-login" aria-hidden="true">
		<div class="modal-dialog modal-dialog-centered" role="document">
			<div class="modal-content">
				<div class="modal-header">
					<button type="button" class="close ml-auto" data-dismiss="modal" aria-label="Close">
						<span aria-hidden="true">×</span>
					</button>
				</div>
				<div class="modal-body p-0">
					<div class="card p-4">	
						<div class="card-header text-center pb-0">
							<h2 class="h4"><?php echo $langs->trans('ReponseSignInTitle'); ?></h2>
							<span><?php echo $langs->trans('ReponseSignInDesc'); ?></span>   
						</div>
						<div class="card-body">
							<form id="login" name="login" action="<?php echo $site->makeUrl('index.php'); ?>" method="post" class="mt-4">
								<input type="hidden" name="token" value="<?php echo $_SESSION['newtoken']; ?>" />

                                <input type="hidden" name="action" value="login">

								<!-- Form -->
								<div class="form-group">
									<label for="login-username"><?php echo $langs->trans('ReponseSignInEmail'); ?></label>
									<div class="input-group mb-4">
										<div class="input-group-prepend">
											<span class="input-group-text"><span class="fas fa-envelope"></span></span>
										</div>
										<input class="form-control" name="login-username" id="login-username" placeholder="" type="text" aria-label="email adress" value="<?php echo GETPOST('login-username', 'alpha'); ?>" required>
									</div>
								</div>
								<!-- End of Form -->
								<div class="form-group">
									<!-- Form -->
									<div class="form-group">
										<label for="login-password"><?php echo $langs->trans('ReponseSignInPassword'); ?></label>
										<div class="input-group mb-4">
											<div class="input-group-prepend">
												<span class="input-group-text"><span class="fas fa-unlock-alt"></span></span>
											</div>
											<input class="form-control" name="login-password" id="login-password" placeholder="" type="password" aria-label="password" required>
										</div>
										<div><a href="#" id="password-forgotten" class="small text-right"><?php echo $langs->trans('ReponsePasswordForgotten'); ?></a></div>
									</div>
								</div>
								<button type="submit" class="btn btn-block btn-success"><?php echo $langs->trans('ReponseConnect'); ?></button>
							</form>
							<div class="d-block d-sm-flex justify-content-center align-items-center mt-4">
								<span class="font-weight-normal">
								<?php echo $langs->trans('ReponseFirstTimeHere'); ?>
									<a href="#" id="register" class="font-weight-bold"><?php echo $langs->trans('ReponseRegister'); ?></a>
								</span>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>

	<div class="modal fade" id="modal-register" tabindex="-1" role="dialog" aria-labelledby="modal-register" aria-hidden="true">
		<div class="modal-dialog modal-dialog-centered" role="document">
			<div class="modal-content">
				<div class="modal-header">
					<button type="button" class="close ml-auto" data-dismiss="modal" aria-label="Close">
						<span aria-hidden="true">×</span>
					</button>
				</div>
				<div class="modal-body p-0">
					<div class="card p-4">
						<div class="card-header text-center pb-0">
							<h2 class="mb-0 h5"><?php echo $langs->trans('ReponseSignUpTitle'); ?></h2>                               
						</div>
						<div class="card-body">
							<form id="register" name="register" action="<?php echo $site->makeUrl('index.php'); ?>" method="post" class="mt-4">
								<input type="hidden" name="token" value="<?php echo $_SESSION['newtoken']; ?>" />
								<input type="hidden" name="action" value="register">
								
								<!-- Form -->
								<div class="form-group">
									<label for="register-login"><?php echo $langs->trans('ReponseSignUpLogin'); ?></label>
									<div class="input-group mb-4">
										<div class="input-group-prepend">
											<span class="input-group-text"><span class="fas fa-user"></span></span>
										</div>
										<input class="form-control" name="register-login" id="register-login" placeholder="" type="text" aria-label="login" value="<?php echo GETPOST('register-username', 'alpha'); ?>" required>
									</div>
								</div>
								<!-- End of Form -->
								<!-- Form -->
								<div class="form-group">
									<label for="register-email"><?php echo $langs->trans('ReponseSignUpEmail'); ?></label>
									<div class="input-group mb-4">
										<div class="input-group-prepend">
											<span class="input-group-text"><span class="fas fa-envelope"></span></span>
										</div>
										<input class="form-control" id="register-email" name="register-email" placeholder="" type="text" aria-label="email adress" value="<?php echo GETPOST('register-email', 'alpha'); ?>" required>
									</div>
								</div>
								<!-- End of Form -->
								<div class="form-group">
									<!-- Form -->
									<div class="">
										<label for="register-password"><?php echo $langs->trans('ReponseSignUpPassword'); ?></label>
										<div class="input-group">
											<div class="input-group-prepend">
												<span class="input-group-text"><span class="fas fa-unlock-alt"></span></span>
											</div>
											<input class="form-control" id="register-password" name="register-password" placeholder="" type="password" aria-label="password" required>
										</div>
									</div>
                                    <!-- End of Form -->
                                    <p class="text-muted mb-4" style="font-size: 0.8rem">
                                        <?php echo $langs->trans('ReponsePasswordTooltip'); ?>
                                    </p>
									<!-- End of Form -->
									<div class="form-check mb-4">
									<?php echo $langs->trans('ReponseTermsAndConditions'); ?>
									</div>
								</div>
								<button type="submit" class="btn btn-block btn-success"><?php echo $langs->trans('ReponseSignUpButton'); ?></button>
							</form>
							<div class="d-block d-sm-flex justify-content-center align-items-center mt-4">
								<span class="font-weight-normal">
									<?php echo $langs->trans('ReponseAlreadyHaveAccount'); ?>
									<a id="register-connect" href="#" class="font-weight-bold"><?php echo $langs->trans('ReponseSignUpConnect'); ?></a>
								</span>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>

	<div class="modal fade" id="modal-password-forgotten" tabindex="-1" role="dialog" aria-labelledby="modal-password-forgotten" aria-hidden="true">
		<div class="modal-dialog modal-dialog-centered" role="document">
			<div class="modal-content">
				<div class="modal-header">
					<button type="button" class="close ml-auto" data-dismiss="modal" aria-label="Close">
						<span aria-hidden="true">×</span>
					</button>
				</div>
				<div class="modal-body p-0">
					<div class="card p-4">	
						<div class="card-header text-center pb-0">
							<h2 class="h4"><?php echo $langs->trans('ReponsePasswordRequestTitle'); ?></h2>
							<span><?php echo $langs->trans('ReponsePasswordRequestDesc'); ?></span>   
						</div>
						<div class="card-body">
							<form id="passrequest" name="passrequest" action="<?php echo $site->makeUrl('index.php'); ?>" method="post" class="mt-4">
								<input type="hidden" name="token" value="<?php echo $_SESSION['newtoken']; ?>" />
								<input type="hidden" name="action" value="passrequest">

								<!-- Form -->
								<div class="form-group">
									<label for="password-username"><?php echo $langs->trans('ReponseSignInEmail'); ?></label>
									<div class="input-group mb-4">
										<div class="input-group-prepend">
											<span class="input-group-text"><span class="fas fa-envelope"></span></span>
										</div>
										<input class="form-control" name="password-username" id="password-username" placeholder="" type="text" aria-label="email adress" value="<?php echo GETPOST('password-username', 'alpha'); ?>" required>
									</div>
								</div>
								<button type="submit" class="btn btn-block btn-success"><?php echo $langs->trans('ReponseValidate'); ?></button>
							</form>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>

	<div class="modal fade" id="modal-password-validation" tabindex="-1" role="dialog" aria-labelledby="modal-password-validation" aria-hidden="true">
		<div class="modal-dialog modal-dialog-centered" role="document">
			<div class="modal-content">
				<div class="modal-header">
					<button type="button" class="close ml-auto" data-dismiss="modal" aria-label="Close">
						<span aria-hidden="true">×</span>
					</button>
				</div>
				<div class="modal-body p-0">
					<div class="card p-4">	
						<div class="card-header text-center pb-0">
							<h2 class="h4"><?php echo $langs->trans('ReponsePasswordValidationTitle'); ?></h2>
							<span><?php echo $langs->trans('ReponsePasswordValidationDesc'); ?></span>   
						</div>
						<div class="card-body">
							<form id="passvalidation" name="passvalidation" action="<?php echo $site->makeUrl('index.php'); ?>" method="post" class="mt-4">
								<input type="hidden" name="token" value="<?php echo $_SESSION['newtoken']; ?>" />
								<input type="hidden" name="action" value="passvalidation">
								<input type="hidden" name="validation-username" value="<?php echo GETPOST('password-username', 'alpha'); ?>">

								<!-- Form -->
								<div class="form-group">
									<label for="validation-code"><?php echo $langs->trans('ReponseValidationCode'); ?></label>
									<div class="input-group mb-4">
										<div class="input-group-prepend">
											<span class="input-group-text"><span class="fas fa-key"></span></span>
										</div>
										<input class="form-control" name="validation-code" id="validation-code" placeholder="" type="text" aria-label="valdiation code" value="<?php echo GETPOST('validation-code', 'alpha'); ?>" required>
									</div>
								</div>
								<!-- End of Form -->
								<button type="submit" class="btn btn-block btn-success"><?php echo $langs->trans('ReponseValidate'); ?></button>
							</form>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
	<!-- End of Modal Content -->

	<script type="text/javascript">
		$(document).ready(function(){
			$('#password-forgotten').click(function(){
				$('#modal-login').modal('hide');
				$('#modal-password-forgotten').modal('toggle');
			})

			$('#register').click(function(){
				$('#modal-login').modal('hide');
				$('#modal-register').modal('toggle');
			})

			$('#register-connect').click(function(){
				$('#modal-register').modal('hide');
				$('#modal-login').modal('toggle');
			})

			<?php if ($openConfirmationModal): ?>
				$('#modal-password-validation').modal('toggle');
			<?php endif; ?>
		});
	</script>

	<main>
 