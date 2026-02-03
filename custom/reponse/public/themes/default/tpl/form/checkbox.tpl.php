<?php
$options = array();
$params = explode("\r\n", $line->param);
if (count($params)) {
    foreach ($params as $param) {
        if (!empty($param) && strpos($param, ',') !== null) {
            $paramsArray = explode(',', $param);
            if (count($paramsArray) == 2) {
                $val = $paramsArray[0];
                $label = $paramsArray[1];
            } else {
                $val = $paramsArray[0];
                $label = $paramsArray[2];                  
            }

            $options[$val] = $label;
        }
    }
}

$values = $line->value ? $line->value : array(); 

?>

<div class="section">
	<div class="container">
		<div class="row justify-content-center align-items-center">
			<div class="col-10 col-md-10 col-lg-8 text-center">
                 <?php $reponse->include_once('tpl/layouts/progress.tpl.php', array('progress' => $progress, 'show_progressbar'=>$reponse->questionnaire->progressbar)); ?>

                <h2 class="h1 mb-5 font-weight-light"><?php echo $line->label; ?></h2>
				<p class="lead"><?php echo $line->help; ?></p>
                <form id="report" name="report" action="<?php echo $site->makeUrl('report.php'); ?>" method="post">
                    <input type="hidden" name="token" value="<?php echo $_SESSION['newtoken']; ?>" />
                    <input type="hidden" name="id" value="<?php echo $reponse->id; ?>">
                    <input type="hidden" name="current" value="<?php echo $current; ?>">
                    <div class="form-group mb-5">
                        <?php if (count($options)): ?>
                            <?php foreach ($options as $val => $label): ?>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="<?php echo $val; ?>" name="<?php echo $line->code; ?>[]" id="check-<?php echo $val; ?>" <?php echo (in_array($val, $values) ? 'checked' : ''); ?>>
                                    <label class="form-check-label" for="check-<?php echo $val; ?>">
                                    <?php echo $label; ?>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <button type="submit" name="next" class="btn btn-block btn-success"><?php echo $langs->trans('ReponseNextQuestion'); ?></button>
                    <?php if ($displayPreviousButton): ?>
                        <button type="submit" name="previous" class="btn btn-block btn-outline-success mb-1"><?php echo $langs->trans('ReponsePreviousQuestion'); ?></button>
                    <?php endif; ?>
                </form>
			</div>
		</div>
	</div>
</div>