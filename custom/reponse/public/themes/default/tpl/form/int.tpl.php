<?php


$value = $line->value > 0 ? intval($line->value) : 0; 

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
                    <input type="hidden" id="value" name="<?php echo $line->code; ?>" value="<?php echo $value; ?>">

                    
                    <div class="row justify-content-center mb-5">                        
                        <div class="col-5 col-sm-5 col-lg-5 text-right">
                            <a href="#" class="minus-button btn btn-icon-only btn-pill btn-white pt-2">
                                <span class="fas fa-minus"></span>
                            </a>
                        </div>
                        <div class="col-2 col-sm-2 col-lg-2 pt-1">
                            <span id="text-value" class="h3 text-gray"><?php echo $value; ?></span>
                        </div>
                        <div class="col-5 col-sm-5 col-lg-5 text-left">
                            <a href="#" class="plus-button btn btn-icon-only btn-pill btn-white pt-2"">
                            <span class="fas fa-plus"></span>
                            </a>
                        </div>
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

<script type="text/javascript">
    $(document).ready(function(){
        $('.plus-button').click(function(e){
            var value = parseInt($("#value").val());
            value += 1;
            $("#value").val(value);
            $("#text-value").html(value);
        })

        $('.minus-button').click(function(e){
            var value = parseInt($("#value").val());
            if (value > 0) {
                value -= 1;
            }   
            $("#value").val(value);
            $("#text-value").html(value);
        })
    });
</script>