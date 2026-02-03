<?php
$options = array();
$param = $line->param;
$values = $_SESSION['values'] ?? array();

if (!empty($param)) {
    $params = explode(':', $param);
    $table = $params[0];
    $cond = null;
    $filter = null;
    if (count($params) == 1)
    {
        $rowid = 'rowid';
        $label = 'label';
    }
    else
    {
        $rowid = $params[1];
        $label = $params[2];
        if (isset($params[4])) {
            $cond = $params[3];
            $filter = $params[4];
        } else if (isset($params[3])) {
            if (strpos($params[3], '|')) {
                $cond = $params[3];
            } else {
                $filter = $params[3];
            }
        }
    }

    // Build where condition
    $where = null;

    if (!empty($cond)) {
        list($code, $col) = explode('|', $cond);

        if (isset($values[$code])) {
            $where = ($col."=".$values[$code]);
        }
    }

    if (!empty($filter)) {
        $where .= empty($where) ? $filter : " AND ".$filter;
    }

    $sql = "SELECT $rowid, $label FROM ".MAIN_DB_PREFIX.$table;
    if ($where) {
        $sql .= " WHERE ".$where;
    }

    $result = $db->query($sql);
    if ($result)
    {
        $num = $db->num_rows($result);

        $i = 0;
        while ($i < $num)
        {
            $objp = $db->fetch_object($result);
            $options[$objp->{$rowid}] = $objp->{$label};
            $i++;
        }
    }
}

$value = is_numeric($line->value) && $line->value > 0 ? intval($line->value) : 0; 

?>
<div class="section">
	<div class="container">
		<div class="row justify-content-center align-items-center">
			<div class="col-10 col-md-10 col-lg-8 text-center">
                 <?php $reponse->include_once('tpl/layouts/progress.tpl.php', array('progress' => $progress, 'show_progressbar'=>$reponse->questionnaire->progressbar)); ?>

                <h2 class="h1 mb-5 font-weight-light">
                    <?php echo $line->label; ?>
				</h2>
				<p class="lead"><?php echo $line->help; ?></p>
                <form id="report" name="report" action="<?php echo $site->makeUrl('report.php'); ?>" method="post">
                    <input type="hidden" name="token" value="<?php echo $_SESSION['newtoken']; ?>" />
                    <input type="hidden" name="id" value="<?php echo $reponse->id; ?>">
                    <input type="hidden" name="current" value="<?php echo $current; ?>">
                    <input type="hidden" id="value" name="<?php echo $line->code; ?>" value="<?php echo $value; ?>">

                    <div class="row justify-content-left">   
                        <?php if (count($options)): ?>
                            <div class="row justify-content-left scrollable-list">
                            <?php foreach ($options as $val => $label): ?>
                                <div class="col-6 col-sm-6 col-lg-6 mb-5">
                                    <div class="card <?php echo ($val == $value ? 'bg-success': ''); ?> border-light">
                                        <div class="card-header <?php echo ($val == $value ? 'bg-success': ''); ?> text-right border-bottom-0">
                                            <a class="select-item-button btn btn-icon-only btn-pill btn-white pt-2" data-value="<?php echo $val; ?>">
                                                <?php if ($val == $value): ?>
                                                    <span class="fas fa-check"></span>
                                                <?php endif; ?>
                                            </a>
                                        </div>
                                        <div class="card-footer <?php echo ($val == $value ? 'bg-success': ''); ?> text-left mt-4 p-3 border-top-0">
                                            <a class="select-item-button" data-value="<?php echo $val; ?>">
                                                <span class="h4 <?php echo ($val == $value ? 'text-white': 'text-gray'); ?>"><?php echo $label; ?></span>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>


                        <button type="submit" name="next" class="btn btn-block btn-success"><?php echo $langs->trans('ReponseNextQuestion'); ?></button>
                        <?php if ($displayPreviousButton): ?>
                            <button type="submit" name="previous" class="btn btn-block btn-outline-success mb-1"><?php echo $langs->trans('ReponsePreviousQuestion'); ?></button>
                        <?php endif; ?>
                    </div>
                </form>
			</div>
		</div>
	</div>
</div>

<script type="text/javascript">
    $(document).ready(function(){

        $(".select-item-button").click(function(e){

            var value = $(this).attr('data-value');

            $("#value").val(value);

            $('.card').removeClass('bg-success');
            $('.card').find('.card-header').removeClass('bg-success');
            $('.card').find('.card-header').find('a').html('');
            $('.card').find('.card-footer').removeClass('bg-success');
            $('.card').find('.card-footer').find('span').removeClass('text-white').addClass('text-gray');

            $(this).closest('.card').addClass('bg-success');
            $(this).closest('.card').find('.card-header').addClass('bg-success');
            $(this).closest('.card').find('.card-header').find('a').html('<span class="fas fa-check"></span>');
            $(this).closest('.card').find('.card-footer').addClass('bg-success');
            $(this).closest('.card').find('.card-footer').find('span').removeClass('text-gray').addClass('text-white');
        });

    });
</script>