<?php

$url = $site->makeUrl('index.php');

$openConfirmationModal = isset($openConfirmationModal) ? $openConfirmationModal : false;

$logo = '';
if (!empty($mysoc->logo_small) && is_readable($conf->mycompany->dir_output . '/logos/thumbs/' . $mysoc->logo_small)) {
    $logo = DOL_URL_ROOT . '/viewimage.php?cache=1&amp;modulepart=mycompany&amp;file=' . urlencode('logos/thumbs/' . $mysoc->logo_small);
} elseif (!empty($mysoc->logo) && is_readable($conf->mycompany->dir_output . '/logos/' . $mysoc->logo)) {
    $logo = DOL_URL_ROOT . '/viewimage.php?cache=1&amp;modulepart=mycompany&amp;file=' . urlencode('logos/' . $mysoc->logo);
}
?>
<section class="section-header pb-10 pb-lg-11 mb-4 mb-lg-6 bg-primary text-white">
	<div class="container">
		<div class="row justify-content-center">
			<div class="col-12 text-center mb-4 mb-lg-5">
				<h1 class="display-2 font-weight-extreme mb-4">Theme title</h1>
				<div class="d-flex flex-column flex-lg-row justify-content-center">
					<span class="h5 mb-3 mb-lg-0 mr-5">
						<span class="fas fa-map-marker-alt"></span>
						<span class="ms-3">Subtitle1</span>
					</span>
					<span class="ms-lg-5 mb-3 mb-lg-0 h5 mr-5">
						<span class="fas fa-bolt"></span>
						<span class="ms-3">Subtitle2</span>
					</span>
					<span class="ms-lg-5 mb-3 mb-lg-0 h5">
						<span class="fas fa-water"></span>
						<span class="ms-3">Subtitle3</span>
					</span>
					
				</div>
			</div>
		 
		</div>
	</div>
	<div class="pattern bottom"></div>
</section>

<section class="section section-lg pt-0"><div class="container mt-n8 mt-lg-n11 z-2"><div class="row justify-content-center"><div class="col"><div class="card border-gray-300 p-3 p-md-5"><p class="lead mb-5"><strong class="font-weight-extreme">
<h2>H2 TITLE</h2>
<p class="lead">Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod
tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam,
quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo
consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse
cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non
proident, sunt in culpa qui officia deserunt mollit anim id est laborum.</p>

<h2>H2 title</h2>
<p>Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod</p>

<ul class="list-unstyled mb-5">
	<li class="py-3">
		<div class="d-flex">
			<span class="icon icon-sm me-3">
				<span class="fas fa-arrow-alt-circle-right"></span>
			</span>
		<div>lorem....</div>
		</div>
	</li>
	<li class="py-3">
		<div class="d-flex">
			<span class="icon icon-sm me-3">
				<span class="fas fa-arrow-alt-circle-right"></span>
			</span>
		<div>IIpsum...</div>
		</div>
	</li>
</ul>

<h2>H2 title2</h2>
<p class="lead">Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod
tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam,
quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo
consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse
cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non
proident, sunt in culpa qui officia deserunt mollit anim id est laborum.</p>

<div class="container">
  <div class="row">
    <div class="col-sm text-center">
      <img class="rounded" src="https://www.iouston.com/image_fictive/image.php" alt="">
      <p class="h4 card-title mb-2">Title img</p>
      <span class="card-subtitle text-gray fw-normal">Subtitle</span>
      <p><a href="mailto:<?php echo strrev('email@domaine.tld'); ?>"><?php echo strrev('email@domaine.tld'); ?></a></p>
    </div>
    <div class="col-sm text-center">
      <img class="rounded" src="https://www.iouston.com/image_fictive/image.php" alt="">
      <p class="h4 card-title mb-2">Title img</p>
      <span class="card-subtitle text-gray fw-normal">Subtitle</span>
      <p><a href="mailto:<?php echo strrev('email@domaine.tld'); ?>"><?php echo strrev('email@domaine.tld'); ?></a></p>
    </div>
    <div class="col-sm text-center">
      <img class="rounded" src="https://www.iouston.com/image_fictive/image.php" alt="">
      <p class="h4 card-title mb-2">Title img</p>
      <span class="card-subtitle text-gray fw-normal">Subtitle</span>
      <p><a href="mailto:<?php echo strrev('email@domaine.tld'); ?>"><?php echo strrev('email@domaine.tld'); ?></a></p>
    </div>
  </div>
</div>
</section>
<section class="section section-partenaires section-lg pb-5 bg-gray-200"><div class="container"><div class="row justify-content-center mb-4 mb-lg-5"><div class="col"><h2 class="h2">Financeurs et partenaires</h2>
<p>Text</p>
<div class="container">
  <div class="row">
    <div class="col-sm">
      <img src="https://www.iouston.com/image_fictive/image.php" alt="">
    </div>
    <div class="col-sm">
      <img src="https://www.iouston.com/image_fictive/image.php" alt="">
    </div>
    <div class="col-sm">
      <img src="https://www.iouston.com/image_fictive/image.php" alt="">
    </div>
    <div class="col-sm">
      <img src="https://www.iouston.com/image_fictive/image.php" alt="">
    </div>
     <div class="col-sm">
      <img src="https://www.iouston.com/image_fictive/image.php" alt="">
    </div>
    <div class="col-sm">
      <img src="https://www.iouston.com/image_fictive/image.php" alt="">
    </div>
  </div>
</div>
<p class="pt-50">Other text</p>
<div class="container">
  <div class="row">
   <div class="col-sm">
      <img src="https://www.iouston.com/image_fictive/image.php" alt="">
    </div>
    <div class="col-sm">
      <img src="https://www.iouston.com/image_fictive/image.php" alt="">
    </div>
    <div class="col-sm">
      <img src="https://www.iouston.com/image_fictive/image.php" alt="">
    </div>
    <div class="col-sm">
      <img src="https://www.iouston.com/image_fictive/image.php" alt="">
    </div>
    <div class="col-sm">
      <img src="https://www.iouston.com/image_fictive/image.php" alt="">
    </div>

  </div>
</div>
</section>