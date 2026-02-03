<?php
global $langs;
$params = $reponse->fetchParameters($line->param);
?>

<div class="section">
    <div class="container">
        <div class="row justify-content-center align-items-center">
            <div class="col-10 col-md-10 col-lg-8 text-center">
                <?php $reponse->include_once('tpl/layouts/progress.tpl.php', array('progress' => $progress, 'show_progressbar'=>$reponse->questionnaire->progressbar)); ?>

                <h2 class="h1 mb-5 font-weight-light"><?php echo $line->label; ?></h2><?php echo $reponse->print_mandatory_info($line->mandatory); ?>
                <p class="lead"><?php echo $line->help; ?></p>
                <p class="lead help">
                    <?php 
                    print '<ul class="params">';
                    if(is_object($params)){
                        $predefinedvalue = $line->value; // Valeur par défaut
                        $disabled = '';
                        $readonly = ''; 
                        foreach($params as $key => $p){
                            $onlytranslation=0;

                            //on récupère la valeur pré-remplie.
                                if($key=='value'){
                                    $predefinedvalue = $p;
                                }
                                
                                //on regarde si déactivé ou non
                                if($key=='disabled' && $p==1){
                                    $disabled='disabled';
                                    $onlytranslation=1;
                                }

                                //on regarde si lecture seule ou non
                                if($key=='readonly' && $p==1){
                                    $readonly='readonly';
                                    $onlytranslation=1;
                                }

                            print '<li>';
                            if($onlytranslation==0){
                                print $langs->trans('Help'.$key).' '.$p;
                            }else{
                                print $langs->trans('Help'.$key);
                            }
                            
                            print '</li>';
                                
                                
                        
                        }
                    }
                    
                    
                    print '</ul>';
                    ?>
                </p> 
                <form id="report" name="report" action="<?php echo $site->makeUrl('report.php'); ?>" method="post">
                    <input type="hidden" name="token" value="<?php echo $_SESSION['newtoken']; ?>" />
                    <input type="hidden" name="id" value="<?php echo $reponse->id; ?>">
                    <input type="hidden" name="current" value="<?php echo $current; ?>">

                    <div class="form-group mb-5">
                        <input class="form-control" name="<?php echo $line->code; ?>" id="question" type="number" max="<?php echo $params->max; ?>" step="<?php echo $params->step; ?>" aria-label="<?php echo $line->code; ?>" value="<?php echo $predefinedvalue; ?>" <?php echo $disabled; ?> <?php echo $readonly; ?> <?php echo $line->mandatory ? 'required' : '' ?>>
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