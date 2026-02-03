<?php
$errors = $site->getErrors();
$messages = $site->getMessages();
$warnings = $site->getWarnings();
?>
<?php if (count($errors) || count($messages) || count($warnings)): ?>
<div class="pt-10 bg-primary">
    <?php if (count($errors)): ?>
        <?php foreach ($errors as $message): ?>
            <div class="row justify-content-center">
                <div class="col-8 col-md-8 col-lg-8 text-center">
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <span class="alert-inner--icon"><i class="fas fa-exclamation-circle"></i></span>
                        <span class="alert-inner--text"><?php echo $message; ?></span>
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <?php if (count($warnings)): ?>
        <?php foreach ($warnings as $message): ?>
            <div class="row justify-content-center">
                <div class="col-8 col-md-8 col-lg-8 text-center">
                    <div class="alert alert-warning alert-dismissible fade show" role="alert">
                        <span class="alert-inner--icon"><i class="fas fa-exclamation-circle"></i></span>
                        <span class="alert-inner--text"><?php echo $message; ?></span>
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <?php if (count($messages)): ?>
        <?php foreach ($messages as $message): ?>
            <div class="row justify-content-center">
                <div class="col-8 col-md-8 col-lg-8 text-center">
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <span class="alert-inner--icon"><i class="fas fa-check-circle"></i></span>
                        <span class="alert-inner--text"><?php echo $message; ?></span>
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
<?php endif; ?>

