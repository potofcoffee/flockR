<?php

ini_set('error_reporting', 1);
//error_reporting (E_ALL);

require_once('../../../config/ko-config.php');
require_once('../vmfds_events.php');

$cache = [];

$db = new mysqli($mysql_server, $mysql_user, $mysql_pass, $mysql_db);

// find all events
$res = $db->query('SELECT * FROM ko_event WHERE my_vmfds_events_nav_address_lat=\'\';');
while ($row = $res->fetch_assoc()) $events[] = $row;

foreach ($events as $event) {
    $loc = $event['my_vmfds_events_location'] ? $event['my_vmfds_events_location'] : GEOCODE_DEFAULT_LOCATION;
    $address = $event['my_vmfds_events_nav_address'] ? $event['my_vmfds_events_nav_address'] : GEOCODE_DEFAULT_ADDRESS;
    $address = str_replace("\n", ', ', str_replace("\r\n", "\n", $address));
    if (isset($cache[$address])) {
        $geo = $cache[$address];
    } else {
        $geo = vmfds_events_geocode($address, GOOGLE_API_KEY);
        echo '<pre>'.$address.'<br />'.print_r($geo, 1).'</pre><hr />';
    }
    $cache[$address] = $geo;
    $sql = 'UPDATE ko_event SET '
        .'my_vmfds_events_location = \''.$loc.'\', '
        .'my_vmfds_events_nav_address = \''.$address.'\', '
        .'my_vmfds_events_nav_address_lat = \''.str_replace(',', '.', $geo['lat']).'\', '
        .'my_vmfds_events_nav_address_lon = \''.str_replace(',', '.', $geo['lon']).'\' '
        .' WHERE (id='.$event['id'].')';
    echo $sql.'<hr />';
    $db->query($sql);
}