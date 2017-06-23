<?php
//die('*');

function my_action_handler_address_map_map() {
	$_SESSION['show'] = 'address_map_map';
}


function address_map_geocode($location, $app_id='3zcZuHHV34GKHG2oez0FybObxSddaN0dtHXG9dUSL4.P_Zg5de3ZCXLL8fxbIzDcSIOl8NBViS4nok_5C2c7FnZWwoAiJ_g-') {
	$api_gateway = "http://where.yahooapis.com/geocode?";
	$app_str = ( $app_id )? "appid=" . $app_id : "appid=YahooDem";
	$location_str = "location=" . urlencode(str_replace(' ', '+', utf8_encode($location)));
	$output_str = "flags=P";

	$request = $api_gateway . "?" . $app_str . "&" . $location_str . "&" . $output_str;

	$response = @file_get_contents($request);

	if( $response ){
		$result = unserialize($response);
		$result =(isset($result['ResultSet']['Result'][0])) ? $result['ResultSet']['Result'][0] : $result;
	} else {
		$result = FALSE;
	}

	return $result;

}

function address_map_coordinates($person, $debug =false) {
	$address = $person['adresse'].' '.$person['plz'].' '.$person['ort'];
	$geocodeFromCache = db_select_data('address_maps_geocode', 'WHERE (address = \''.$address.'\')', 'lat, lng', '', '', TRUE); 
	if ($debug) $debugger[] = print_r($geocodeFromCache, true);
	
	if (!$geocodeFromCache['lat']) {
		// looks like we don't have a cached geocode yet
		$geocodeFromYahoo = address_map_geocode($address);
		if ($debug) $debugger[] = print_r($geocodeFromYahoo, true);
		$sql =db_insert_data('address_maps_geocode', array(
															'address' => $address,
															'lat' => $geocodeFromYahoo['latitude'],
															'lng' => $geocodeFromYahoo['longitude'],
														  )
							);
		$result = array(
							'lat' => $geocodeFromYahoo['latitude'],
							'lng' => $geocodeFromYahoo['longitude'],
						);
	} else {
		$result = $geocodeFromCache;
	}
	if ($debug) $result['debug'] = $debugger;
	return $result;
}


function my_show_case_address_map_map() {
	// geocode addresses, if necessary
		
	// get the necessary javascripts

	echo '<pre>
	<script type="text/javascript" src="http://maps.google.com/maps/api/js?sensor=false"></script>
	<script type="text/javascript">
		var map;
		var marker =[];
		var myLatlng;
		var infowindow = [];
		var ctr;
	
		function initialize() {
			ctr = 0;
			var latlng = new google.maps.LatLng(48.46632, 8.416357);
			var myOptions = {
				zoom: 8,
				center: latlng,
				mapTypeId: google.maps.MapTypeId.ROADMAP
			};
			map = new google.maps.Map(document.getElementById("map_canvas"), myOptions);
			';
	
	$cache =array();
	
	$pids = join(', ', $_SESSION['my_list']);
	$res = ko_get_leute($p, 'AND `id` in ('.$pids.')');
	foreach ($p as $person) {
		if ($person['adresse']) {
			$geocode = address_map_coordinates($person);
			if ($geocode['lat']) {
				$key = $geocode['lat'].'.'.$geocode['lng'];
				$cache[$key][] = $person['vorname'].' '.$person['nachname'];
				$title = join(', ', $cache[$key]);
				$infotext = $title.'<br /><br />'.$person['adresse'].'<br />'.$person['plz'].' '.$person['ort'];
				echo '
					ctr++;
					myLatlng = new google.maps.LatLng('.$geocode['lat'].','.$geocode['lng'].');
			
					marker[ctr] = new google.maps.Marker({
					  position: myLatlng, 
					  map: map, 
					  title:"'.$title.'"
					});   
					
					infowindow[ctr] = new google.maps.InfoWindow({
									content: "'.$infotext.'"
								});
					google.maps.event.addListener(marker, "click", function() {
						infowindow[ctr].open(map, marker[ctr]);
					});
					
					';
			} else {
				$missing[] = $person;
			}
		}
	}
	echo '}	</script>';

	echo '<div style="clear: all;"></div>';
	echo '<h3>Encoding errors:</h3><ul>';
	foreach ($missing as $person) {
		echo '<li>'.$person['vorname'].' '.$person['nachname'].'<br />'
			.$person['adresse'].' '.$person['plz'].' '.$person['ort'].'<br />'
			.print_r(address_map_coordinates($person, true), true)
			.'</li>';
	}
	echo '</ul>';
	
	echo '<div id="map_canvas" style="width:100%; height:500px">';
	echo '<script>initialize();</script>';
}
