<?php
$values = $_SESSION['values'] ?? array();

if(!empty($values) && $line->postfill=='projet.rowid'){
    $fk_project = $values['projet.rowid'];
    $settinghelp = '';
}else{
    $fk_project='';
    $settinghelp = $langs->trans('YouNeedAddAPostFillOnYourTaskField');
}

?>

<div class="section">
    <div class="container">
        <div class="row justify-content-center align-items-center">
            <div class="col-10 col-md-10 col-lg-8 text-center">
                 <?php $reponse->include_once('tpl/layouts/progress.tpl.php', array('progress' => $progress, 'show_progressbar'=>$reponse->questionnaire->progressbar)); ?>
                <?php 
                dol_include_once('/core/class/html.formprojet.class.php');
                $formproject = new FormProjets($db);
                dol_include_once('/core/class/html.form.class.php');
                $form = new form($db);
                ?>
                <h2 class="h1 mb-5 font-weight-light"><?php echo $line->label; ?></h2>
                <p class="lead"><?php echo $line->help; ?></p>
                <form id="report" name="report" action="<?php echo $site->makeUrl('report.php'); ?>" method="post">
                    <input type="hidden" name="token" value="<?php echo $_SESSION['newtoken']; ?>" />
                    <input type="hidden" name="id" value="<?php echo $reponse->id; ?>">
                    <input type="hidden" name="current" value="<?php echo $current; ?>">

                    <div class="form-group mb-5">
                        <?php
                        print $formproject->selectTasks(-1, $val, $line->code, 64, 0, 1, 0, 0, 0, '', $fk_project, '', null, 0);
                        print '<br>'.$settinghelp;
                        ?>
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