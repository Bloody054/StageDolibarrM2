<?php

$values = $line->value ? explode(',', $line->value) : array();

?>

<div class="section">
	<div class="container">
		<div class="row justify-content-center align-items-center">
			<div class="col-10 col-md-10 col-lg-8 text-center">
                 <?php $reponse->include_once('tpl/layouts/progress.tpl.php', array('progress' => $progress, 'show_progressbar'=>$reponse->questionnaire->progressbar)); ?>

                <h2 class="h1 mb-5 font-weight-light"><?php echo $line->label; ?></h2>
				<p class="lead"><?php echo $line->help; ?></p>               
                <div class="form-group">
                    <?php if (count($values) > 0): ?>
                        <?php foreach ($values as $val): ?>
                            <form action="<?php echo $site->makeUrl('report.php'); ?>" method="post">
                            <input type="hidden" name="token" value="<?php echo $_SESSION['newtoken']; ?>" />
                            <input type="hidden" name="id" value="<?php echo $reponse->id; ?>">
                            <input type="hidden" name="current" value="<?php echo $current; ?>">
                            <input type="hidden" name="action" value="deletefile">
                            <input type="hidden" name="urlfile" value="<?php echo $val; ?>">

                            <div class="row ml-0 mr-0">
                                <div class="col-10 col-md-10 col-lg-10 custom-file">
                                    <?php echo $val; ?>
                                </div>
                                <div class="col-2 col-md-2 col-lg-2">
                                    <button type="submit" class="delete-file-button btn btn-icon-only btn-primary">
                                        <span aria-hidden="true" class="fas fa-trash"></span>
                                    </button>
                                </div>
                            </div>
                            </form>
                        <?php endforeach; ?>
                    <?php endif; ?>

                    <form id="report" name="report" action="<?php echo $site->makeUrl('report.php'); ?>" method="post" enctype="multipart/form-data">
                    <input type="hidden" name="token" value="<?php echo $_SESSION['newtoken']; ?>" />
                    <input type="hidden" name="id" value="<?php echo $reponse->id; ?>">
                    <input type="hidden" name="current" value="<?php echo $current; ?>">

                    <div id="files-container">
                        <div class="row ml-0 mr-0">
                            <div class="col-10 col-md-10 col-lg-10 custom-file">
                                <input type="file" name="<?php echo $line->code; ?>[]" class="custom-file-input" aria-label="File upload" accept="image/*" data-browse="<?php echo $langs->trans('ReponseBrowseFile'); ?>">
                                <label class="custom-file-label"><?php echo $langs->trans('ReponseSelectFile'); ?></label>
                            </div>
                            <div class="col-2 col-md-2 col-lg-2">
                                <button type="button" class="delete-file-input-button btn btn-icon-only btn-primary" disabled>
                                    <span aria-hidden="true" class="fas fa-trash"></span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div id="progressbar-container" class="progress progress-xl mt-2 mb-2" style="display: none">
                        <div class="progress-bar bg-success" id="progressbar" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"><span>0%</span></div>
                    </div>

                        <input type="submit" id="next" name="next" class="btn btn-block btn-success" value="<?php echo $langs->trans('ReponseNextQuestion'); ?>" />
                        <?php if ($displayPreviousButton): ?>
                            <input type="cancel" id="previous" name="previous" class="btn btn-block btn-outline-success mb-1" value="<?php echo $langs->trans('ReponsePreviousQuestion'); ?>" />
                        <?php endif; ?>
                    </form>


                    <form id="finalreport" name="finalreport" action="<?php echo $site->makeUrl('report.php'); ?>" method="post" enctype="multipart/form-data">
                        <input type="hidden" name="token" value="<?php echo $_SESSION['newtoken']; ?>" />
                        <input type="hidden" name="id" value="<?php echo $reponse->id; ?>">
                        <input type="hidden" name="current" value="<?php echo $current; ?>">
                        <input type="hidden" name="next" value="1">

                    </form>

                    <form id="cancelreport" name="cancelreport" action="<?php echo $site->makeUrl('report.php'); ?>" method="post" enctype="multipart/form-data">
                    <input type="hidden" name="token" value="<?php echo $_SESSION['newtoken']; ?>" />
                    <input type="hidden" name="id" value="<?php echo $reponse->id; ?>">
                    <input type="hidden" name="current" value="<?php echo $current; ?>">
                    <input type="hidden" name="previous" value="1">
                    </form>
                </div>
			</div>
		</div>
	</div>
</div>


<div id="file-input-template">
    <div class="row ml-0 mr-0 d-none">
        <div class="col-10 col-md-10 col-lg-10 custom-file">
            <input type="file" name="<?php echo $line->code; ?>[]" class="custom-file-input" aria-label="File upload" data-browse="<?php echo $langs->trans('ReponseBrowseFile'); ?>">
            <label class="custom-file-label""><?php echo $langs->trans('ReponseSelectFile'); ?></label>
        </div>
        <div class="col-2 col-md-2 col-lg-2">
            <button type="button" class="delete-file-input-button btn btn-icon-only btn-primary" disabled>
                <span aria-hidden="true" class="fas fa-trash"></span>
            </button>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function(){
        $('.custom-file-input').change(function(e){
            var filename = e.target.files[0].name;
            var $e = $("#file-input-template").children().clone(true);

            $(this).next("label").html(filename);
            $(this).closest(".row").find('.delete-file-input-button').removeAttr('disabled');

            $e.removeClass('d-none');
            $("#files-container").append($e);
        })

        $('.delete-file-input-button').click(function(e){
            $(this).closest(".row").remove();
        });

        var options = { 
            beforeSend: function(arr, $form, options) 
            {
                var cancel = false;
                for (var i = 0; i < arr.length; i++) {
                    if (arr[i].name == 'previous') {
                        cancel = true;
                    }
                }
                $("#progressbar-container").show();

                $("#next").attr('disabled', true);
                <?php if ($displayPreviousButton): ?>
                    $("#previous").attr('disabled', true);
                <?php endif; ?>

                return cancel;
            },
            uploadProgress: function(event, position, total, percentComplete) 
            {
                $("#progressbar").css('width', percentComplete + '%');
                $("#progressbar").find('span').html(percentComplete + '%');
            },
            success: function() 
            {
                $("#finalreport").submit();
            },
            complete: function(response) 
            {

            },
            error: function()
            {
                $("#next").removeAttr('disabled');
            }
        }; 
        
        $("#report").ajaxForm(options);

        $("#previous").click(function(e) {
            e.preventDefault();
            $("#cancelreport").submit();
        });
    });
</script>