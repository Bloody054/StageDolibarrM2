<?php
if($show_progressbar==1){
?>
<div class="progress-wrapper">
    <div class="progress-info  info-xl">
        <div class="progress-percentage">
            <span><?php echo $progress; ?>%</span>
        </div>
    </div>
    <div class="progress progress-xl">
        <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $progress; ?>%;" aria-valuenow="<?php echo $progress; ?>" aria-valuemin="0" aria-valuemax="100"></div>
    </div>
</div>
<?php
}
?>