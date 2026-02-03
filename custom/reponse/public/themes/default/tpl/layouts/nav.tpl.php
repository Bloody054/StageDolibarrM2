<header class="header-global">
    <!--     <nav class="navbar navbar-expand-lg navbar-transparent navbar-light navbar-theme-soft mb-4">
            <div class="container position-relative"> -->
    <nav id="navbar-main" aria-label="Primary navigation"
         class="navbar navbar-main navbar-expand-lg navbar-theme-primary headroom navbar-dark navbar-transparent navbar-theme-secondary headroom--not-top headroom--not-bottom headroom--pinned">
        <div class="container position-relative">
            <a class="navbar-brand mr-lg-5" href="<?php echo $url; ?>">
                <?php if (!empty($logo)): ?>
                    <img class="navbar-brand-dark" src="<?php echo $logo; ?>" alt="<?php echo $appli; ?>">
                    <img class="navbar-brand-light" src="<?php echo $logo; ?>" alt="<?php echo $appli; ?>">
                <?php endif; ?>
            </a>
            <div class="navbar-collapse collapse" id="navbar-soft">
                <div class="navbar-collapse-header">
                    <div class="row">
                        <div class="col-6 collapse-brand">
                            <a href="<?php echo $url; ?>">
                                <?php if (!empty($logo)): ?>
                                    <img src="<?php echo $logo; ?>" alt="<?php echo $appli; ?>">
                                <?php endif; ?>
                            </a>
                        </div>
                        <div class="col-6 collapse-close">
                            <span class="fas fa-times" data-toggle="collapse" role="button" data-target="#navbar-soft"
                                  aria-controls="navbar-soft" aria-expanded="false"
                                  aria-label="Toggle navigation"></span>
                        </div>
                    </div>
                </div>
                <ul class="navbar-nav navbar-nav-hover align-items-lg-center">
                    <li class="nav-item">
                        <a href="<?php echo $site->makeUrl('index.php'); ?>" class="nav-link" role="button">
                            <span class="nav-link-inner-text"><?php echo $langs->trans('ReponseForm'); ?></span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="<?php echo $site->makeUrl('map.php'); ?>" class="nav-link" role="button">
                            <span class="nav-link-inner-text"><?php echo $langs->trans('ReponseMap'); ?></span>
                        </a>
                    </li>
                    <?php if ($user->isLoggedIn): ?>
                        <li class="nav-item">
                            <a href="<?php echo $site->makeUrl('history.php'); ?>" class="nav-link" role="button">
                                <span class="nav-link-inner-text"><?php echo $langs->trans('ReponseHistory'); ?></span>
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
            <div class="d-flex align-items-center">
                <div class="dropdown pl-1">
                    <?php if ($user->isLoggedIn): ?>
                        <div class="d-flex align-items-center" id="dropdownMenuButton" data-toggle="dropdown">
                            <?php echo $user->initials; ?>
                        </div>
                        <div class="dropdown-menu dropdown-menu-md" aria-labelledby="dropdownMenuButton">
                            <a class="dropdown-item"
                               href="<?php echo $site->makeUrl('account.php'); ?>"><?php echo $langs->trans('ReponseMyProfile'); ?></a>
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item" href="<?php echo $site->makeUrl('logout.php'); ?>"><span
                                        class="fas fa-sign-out-alt mr-2"></span><?php echo $langs->trans('ReponseLogout'); ?>
                            </a>
                        </div>
                    <?php else: ?>
                        <button data-toggle="modal" data-target="#modal-login"
                                class="btn btn-pill btn-circle btn-outline-primary btn-soft">
                            <span class="fa fa-user"></span>
                        </button>
                    <?php endif; ?>

                </div>
                <button class="navbar-toggler ml-2" type="button" data-toggle="collapse" data-target="#navbar-soft"
                        aria-controls="navbar-soft" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
            </div>
        </div>
    </nav>
</header>