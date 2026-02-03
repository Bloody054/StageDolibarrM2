<div class="section">
	<div class="container">
		<div class="row justify-content-center align-items-center">
			<div class="col-10 col-md-10 col-lg-8 text-center">
                 <?php $reponse->include_once('tpl/layouts/progress.tpl.php', array('progress' => $progress, 'show_progressbar'=>$reponse->questionnaire->progressbar)); ?>

                <h2 class="h1 mb-5 font-weight-light">
                    <?php echo $line->label; ?>
				</h2>
				<p class="lead"><?php echo $line->help; ?></p>
                <p class="mt-5"><?php echo $langs->trans('ReponseUnknownType'); ?></p>
			</div>
		</div>
	</div>
</div>