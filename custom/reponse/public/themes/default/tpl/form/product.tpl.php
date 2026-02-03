<div class="section">
    <div class="container">
        <div class="row justify-content-center align-items-center">
            <div class="col-10 col-md-10 col-lg-8 text-center">
                 <?php $reponse->include_once('tpl/layouts/progress.tpl.php', array('progress' => $progress, 'show_progressbar'=>$reponse->questionnaire->progressbar)); ?>
                
                <?php 
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
                        //récupère les paramètres du champ
                        $params = $reponse->fetchParameters($line->param);                                    
                                                
                        $products = $form->select_produits_list($val, $line->code, $params->filtertype, 0, 0, $params->filterkey, 1, 2, 1, 0, 1, 1);
                        
                        // $products est trop complexe pour le select array après donc on parse et on simplifie le tableau
                        $productlist = array();
                        foreach ($products as $p){
                        $productlist[$p['key']] = $p['value'];
                        }
                        asort($productlist);
                        
                        //affiche le champ de formulaire
                        print $form->selectarray($line->code, $productlist, $val, 0, 0, 0, '', 0, 80, 0, '', 'minwidth75', 0, '', 0, 1); 
                      ?>
                    </div>

                    <button type="submit" name="next" class="btn btn-block btn-success"><?php echo $langs->trans('ReponseNextQuestion'); ?></button>
                    <?php if ($displayPreviousButton): ?>
                        <button type="submit" name="previous" class="btn btn-block btn-outline-success mb-1"><?php echo $langs->trans('ReponsePreviousQuestion'); ?></button>
                    <?php endif; ?>
                </form>
                <script type="text/javascript">
                    $(document).ready(function() {
                        $('#<?php echo $line->code; ?>').select2();
                    });
                </script>
            </div>
        </div>
    </div>
</div>