<style>
#lines {
    display: flex;
    flex-direction: column;
    gap: 10px;
}
.line {
    display: flex;
    align-items: center;
    gap: 10px;
}
.productref {
    flex: 2;  /* plus large */
}
.productqty {
    flex: 1;  /* plus étroit */
    display: flex;
    align-items: center;
    gap: 5px;
}
.line select {
    min-width: 200px;
}
.line input[type="text"] {
    width: 40px!important;
}
.line button {
    border: none;
    cursor: pointer;
    border-radius: 3px;
    padding: 5px 10px;
}

.line button.remove:hover {
    background: #c0392b;
}

</style>

<div class="section">
    <div class="container">
        <div class="row justify-content-center align-items-center">
            <div class="col-10 col-md-10 col-lg-8 text-center">
                 <?php 
                 $reponse->include_once(
                     'tpl/layouts/progress.tpl.php', 
                     array('progress' => $progress, 'show_progressbar'=>$reponse->questionnaire->progressbar)
                 ); 
                 ?>
                
                <?php 
                dol_include_once('/core/class/html.form.class.php');
                $form = new Form($db);

                // récupération paramètres
                $params = $reponse->fetchParameters($line->param);                                    
                $products = $form->select_produits_list(
                    $val, $line->code, $params->filtertype, 
                    0, 0, $params->filterkey, 1, 2, 1, 0, 1, 1
                );

                $productlist = array();
                foreach ($products as $p) {
                    $productlist[$p['key']] = $p['value'];
                }
                asort($productlist);

                // générer le select initial
                $selectHtml = $form->selectarray($line->code.'_0', $productlist, $val,
                    0, 0, 0, '', 0, 80, 0, '', 'minwidth75 product-select',
                    0, '', 0, 1
                );
                
                ?>

                <h2 class="h1 mb-5 font-weight-light"><?php echo $line->label; ?></h2>
                <p class="lead"><?php echo $line->help; ?></p>
                
                <form id="report" name="report" action="<?php echo $site->makeUrl('report.php'); ?>" method="post">
                    <input type="hidden" name="token" value="<?php echo $_SESSION['newtoken']; ?>" />
                    <input type="hidden" name="id" value="<?php echo $reponse->id; ?>">
                    <input type="hidden" name="current" value="<?php echo $current; ?>">

                    <div class="form-group mb-5">
                        <div id="lines">
                            <div class="line">
                                <div class="productref">
                                    <?php 
                                    // on insère le select et on stocke son HTML original dans data-template
                                    echo str_replace(
                                        '<select ',
                                        '<select data-template="'.htmlspecialchars($selectHtml, ENT_QUOTES).'" ',
                                        $selectHtml
                                    );
                                    ?>
                                </div>
                                <div class="productqty">
                                    <input type="text" name="qty_0" value="" size="5">
                                    <button type="button" class="add" onclick="addLine(this)">➕</button>
                                    <button type="button" class="remove" onclick="removeLine(this)">❌</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <button type="submit" name="next" class="btn btn-block btn-success">
                        <?php echo $langs->trans('ReponseNextQuestion'); ?>
                    </button>
                    <?php if ($displayPreviousButton): ?>
                        <button type="submit" name="previous" class="btn btn-block btn-outline-success mb-1">
                            <?php echo $langs->trans('ReponsePreviousQuestion'); ?>
                        </button>
                    <?php endif; ?>
                </form>

                <script type="text/javascript">
                $(document).ready(function() {
                    // Activer Select2 sur le premier select
                    $('.product-select').select2();
                });
                </script> 
            </div>
        </div>
    </div>
</div>

<script>
let lineIndex = 1;
let lineCode = "<?php echo dol_escape_js($line->code); ?>";

function addLine(btn) {
    let container = document.getElementById("lines");
    let firstLine = container.querySelector(".line");

    // nouvelle ligne
    let newLine = document.createElement("div");
    newLine.className = "line";

    // récupérer le HTML du select brut depuis data-template
    let template = firstLine.querySelector("select").getAttribute("data-template");

    // remplacer le suffixe _0 par _{lineIndex}, en fonction de ton code réel
    let regex = new RegExp(lineCode + "_0", "g");
    template = template.replace(regex, lineCode + "_" + lineIndex);

    // construire la ligne
    newLine.innerHTML = 
        '<div class="productref">'+ template +'</div>' +
        '<div class="productqty">' +
            '<input type="text" name="qty_'+lineIndex+'" value="" size="5">' +
            '<button type="button" class="add" onclick="addLine(this)">➕</button>' +
            '<button type="button" class="remove" onclick="removeLine(this)">❌</button>' +
        '</div>';

    // insérer la nouvelle ligne
    if (btn) {
        btn.parentElement.parentElement.insertAdjacentElement("afterend", newLine);
    } else {
        container.appendChild(newLine);
    }

    // activer Select2 sur le nouveau select
    $('#'+lineCode+'_'+lineIndex).select2();

    lineIndex++;
}

function removeLine(btn) {
    let container = document.getElementById("lines");
    if (container.querySelectorAll(".line").length > 1) {
        btn.parentElement.remove();
    } else {
        alert("Il doit rester au moins une ligne.");
    }
}
</script>
