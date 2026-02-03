<?php

$value = $line->value ? $line->value : null;

$latitude = 48.8534;
$longitude = 2.3488;

if (!empty($value)) 
{
    list($latitude, $longitude) = explode(',', $value);
}

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

                    
                    <div class="row justify-content-center mb-5">  
                        <div class="col-12 col-md-12 col-lg-12 text-center">                      
                            <div id="map" style="height:80vh"></div>
                        </div>
                        <div class="col-6 col-md-6 col-lg-6 text-left">                      
                            <button id="mylocation-button" type="button" class="btn"><?php echo $langs->trans('ReponseGetMyLocation'); ?></button>
                        </div>
                        <div class="col-6 col-md-6 col-lg-6 text-right">                      
                            <button type="button" class="btn" data-toggle="modal" data-target="#modal-location"><?php echo $langs->trans('ReponseRecenterMap'); ?></button>
                        </div>
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

<div class="modal fade" id="modal-location" tabindex="-1" role="dialog" aria-labelledby="modal-location" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close ml-auto" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span>
                </button>
            </div>
            <div class="modal-body p-0">
                <div class="card p-4">	
                    <div class="card-header text-center pb-0">
                        <h2 class="h4"><?php echo $langs->trans('ReponseLocationTitle'); ?></h2>
                        <span><?php echo $langs->trans('ReponseLocationDesc'); ?></span>   
                    </div>
                    <div class="card-body">
                        <form id="passrequest" name="passrequest" action="#" method="post" class="mt-4">
                            <input type="hidden" name="token" value="<?php echo $_SESSION['newtoken']; ?>" />
                            <!-- Form -->
                            <div class="form-group">
                                <label for="location"><?php echo $langs->trans('ReponseLocation'); ?></label>
                                <div class="input-group mb-4">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><span class="fas fa-map"></span></span>
                                    </div>
                                    <input class="form-control" name="location" id="location" placeholder="Paris, France" type="text" aria-label="location" value="" required>
                                </div>
                            </div>
                            <button id="location-button" data-dismiss="modal" type="button" class="btn btn-block btn-success"><?php echo $langs->trans('ReponseValidate'); ?></button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
 
<div class="modal fade" id="modal-location-error" tabindex="-1" role="dialog" aria-labelledby="modal-location-error" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close ml-auto" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span>
                </button>
            </div>
            <div class="modal-body p-0">
                <div class="card p-4">	
                    <div class="card-header text-center">
                        <h2 class="h4"><?php echo $langs->trans('ReponseLocationNotFoundTitle'); ?></h2>
                        <span><?php echo $langs->trans('ReponseLocationNotFoundDesc'); ?></span>   
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" data-dismiss="modal" class="btn btn-block btn-success"><?php echo $langs->trans('ReponseClose'); ?></button>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function(){

        var latitude = <?php echo $latitude; ?>;
        var longitude = <?php echo $longitude; ?>;
        var zoom = 15;
        var geodecoder = "https://nominatim.openstreetmap.org/search?format=json";

        var marker = null;
        function getLocation() {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(showPosition);
            }
        }

        function showPosition(position) {
            latitude = position.coords.latitude;
            longitude = position.coords.longitude; 

            map.setView([latitude, longitude], zoom);
        }

        function setMarker(lat, lng) {
            if (marker != null) {
                marker.setLatLng(L.latLng(lat, lng));
            } else {
                marker = L.marker([lat, lng]).addTo(map);
            }

            $("#value").val(lat + "," + lng); 
        }

        function onMapClick(e) {
            setMarker(e.latlng.lat, e.latlng.lng);
        }

        // Init MAP
        var map = L.map('map').setView([latitude, longitude], zoom);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
        }).addTo(map);

        map.on('click', onMapClick);

        <?php if (!empty($value)): ?>
            marker = L.marker([latitude, longitude]).addTo(map);
        <?php endif; ?>

        getLocation();

        $("#location-button").click(function(e){
            var location = $("#location").val();

            if (location) {
                $.get(geodecoder + "&q=" + location, function( data ) {
                    if (data.length > 0) {
                        var result = data[0];
                        var lat = result.lat;
                        var lon = result.lon;

                        map.setView([lat, lon], zoom);
                    } else {
                        $('#modal-location-error').modal('toggle');
                    }               
                });
            }
        });

        $("#mylocation-button").click(function(e){
            getLocation();
        });
    });
</script>
