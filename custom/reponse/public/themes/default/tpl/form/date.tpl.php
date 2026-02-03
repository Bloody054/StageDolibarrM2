<?php

$value = $line->value > 0 ?  $line->value : dol_now();

$year = 0;
$month = 0;
$day = 0;

if ($value > 0) {
    $year = dol_print_date($value, "%Y");
    $month = dol_print_date($value, "%m");
    $day = dol_print_date($value, "%d");
}

$value = dol_print_date($value, "%d/%m/%Y"); 

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
                    <input type="hidden" id="year" name="<?php echo $line->code; ?>year" value="<?php echo $year; ?>">
                    <input type="hidden" id="month" name="<?php echo $line->code; ?>month" value="<?php echo $month; ?>">
                    <input type="hidden" id="day" name="<?php echo $line->code; ?>day" value="<?php echo $day; ?>">

                    <div class="form-group mb-5">
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><span class="fas fa-calendar-alt"></span></span>
                            </div>
                            <input class="form-control datepicker" name="<?php echo $line->code; ?>" id="question" placeholder="<?php echo $langs->trans('ReponseSelectDate'); ?>" type="text" aria-label="Date with icon left" value="<?php echo $value; ?>">
                        </div>
                    </div>

                    <button type="submit" id="next" name="next" class="btn btn-block btn-success"><?php echo $langs->trans('ReponseNextQuestion'); ?></button>
                    <?php if ($displayPreviousButton): ?>
                        <button type="submit" name="previous" class="btn btn-block btn-outline-success mb-1"><?php echo $langs->trans('ReponsePreviousQuestion'); ?></button>
                    <?php endif; ?>
                </form>
			</div>
		</div>
	</div>
</div>

<script type="text/javascript">
    $(document).ready(function(){
        $('.datepicker').on('changeDate', function(e){
            $("#year").val(e.date.getFullYear());
            $("#month").val(e.date.getMonth()+1);
            $("#day").val(e.date.getDate());
        })
    });
</script>