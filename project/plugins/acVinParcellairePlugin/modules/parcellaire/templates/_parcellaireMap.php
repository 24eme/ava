<?php use_javascript('lib/leaflet/leaflet.js'); ?>
<?php use_javascript('lib/leaflet-gps/dist/leaflet-gps.min.js'); ?>

<?php use_stylesheet('/js/lib/leaflet/leaflet.css'); ?>
<?php use_stylesheet('/js/lib/leaflet/marker.css'); ?>
<?php use_stylesheet('/js/lib/leaflet-gps/dist/leaflet-gps.min.css'); ?>

<div id="map" class="col-12" style="height: 580px; margin-bottom: 20px;">
	<div class="leaflet-touch leaflet-bar"><a id="refreshButton" onclick="zoomOnMap(); return false;" href="#"><span class="glyphicon glyphicon-resize-small"></span></a></div>
</div>
<style>
.sectionlabel, .parcellelabel {
	text-shadow: 1px 1px #fff,-1px 1px #fff,1px -1px #fff,-1px -1px #fff,1px 1px 5px #555;
}
</style>
<script type="text/javascript">
<?php $geo = $parcellaire->getRawValue()->getGeoJson(); ?>
<?php if ($geo): ?>
	var parcelles = '<?php echo addslashes(json_encode($geo)) ?>';
<?php else: ?>
	var parcelles = '';
<?php endif; ?>
    var aires = [];
    <?php foreach($parcellaire->getMergedAires() as $aire): ?>
    aires.push({'color': '<?php echo $aire->getColor(); ?>', 'name': '<?php echo $aire->getName() ?>', 'geojson': '<?php echo addslashes($aire->getRawValue()->geojson); ?>'});
    <?php endforeach; ?>
</script>
<?php use_javascript('parcelles-maker.js?'.$_ENV['GIT_LAST_COMMIT']); ?>
