
</main>


<footer class="footer pt-6 pb-5 bg-primary text-white">
    <div class="container">
        <div class="row">
            <div class="col-md-4">
                <a href="<?php echo $url; ?>">
                    <?php if (!empty($logo)): ?>
                        <img src="<?php echo $logo; ?>" alt="<?php echo $appli; ?>">
                    <?php endif; ?>
                </a>
                <p>
                    
                <?php
                //Footer description
                echo $questionnaire->footerdescription;
                ?>

                </p>
                <ul class="social-buttons mb-5 mb-lg-0">
                    <li><a href="https://twitter.com/themesberg" aria-label="twitter social link" class="icon-white me-2"><span class="fab fa-twitter"></span></a></li>
                    <li><a href="https://www.facebook.com/themesberg/" class="icon-white me-2" aria-label="facebook social link"><span class="fab fa-facebook"></span></a></li>
                    <li><a href="https://github.com/themesberg" aria-label="github social link" class="icon-white me-2"><span class="fab fa-github"></span></a></li>

                    <li><a href="https://dribbble.com/themesberg" class="icon-white" aria-label="dribbble social link"><span class="fab fa-dribbble"></span></a></li>
                </ul>
            </div>
        </div>
        <hr class="bg-secondary my-3 my-lg-5">

        <div class="row">
            <div class="col mb-md-0">
                <div class="d-flex text-center justify-content-center align-items-center" role="contentinfo">
                    <p class="fw-normal font-small mb-0">Mise en orbite par <a href="https://www.iouston.com" title="Spécialiste ERP dolibarr">iouston informatique</a>
                        <span class="current-year">2024</span>. Tous droits réservés.
                    </p>
                </div>
            </div>
        </div>
</footer>

<!-- Vendor JS -->
<script src="./themes/default/vendor/onscreen/dist/on-screen.umd.min.js"></script>
<script src="./themes/default/vendor/nouislider/distribute/nouislider.min.js"></script>
<script src="./themes/default/vendor/moment/dist/moment-with-locales.min.js"></script>
<script src="./themes/default/vendor/bootstrap-datepicker/dist/js/bootstrap-datepicker.min.js"></script>
<script src="./themes/default/vendor/bootstrap-datepicker/dist/locales/bootstrap-datepicker.fr.min.js"></script>
<script src="./themes/default/vendor/bootstrap-datetimepicker/dist/js/bootstrap-datetimepicker.min.js"></script>
<script src="./themes/default/vendor/bootstrap-tagsinput/dist/bootstrap-tagsinput.min.js"></script>
<script src="./themes/default/vendor/waypoints/lib/jquery.waypoints.min.js"></script>
<script src="./themes/default/vendor/owl.carousel/dist/owl.carousel.min.js"></script>
<script src="./themes/default/vendor/jarallax/dist/jarallax.min.js"></script>
<script src="./themes/default/vendor/jquery.counterup/jquery.counterup.min.js"></script>
<script src="./themes/default/vendor/jquery-countdown/dist/jquery.countdown.min.js"></script>
<script src="./themes/default/vendor/smooth-scroll/dist/smooth-scroll.polyfills.min.js"></script>
<script src="./themes/default/vendor/prismjs/prism.js"></script>
<script src="./themes/default/vendor/chart.js/dist/Chart.min.js"></script>
<script src="./themes/default/vendor/vivus/dist/vivus.min.js"></script>
<script src="./themes/default/vendor/jquery-form/jquery.form.js"></script>
<script src="./themes/default/vendor/select2/dist/js/select2.min.js"></script>

<script async defer src="https://buttons.github.io/buttons.js"></script>

<!-- pixel JS -->
<script src="./themes/default/assets/js/pixel.js"></script>
</body>

</html>