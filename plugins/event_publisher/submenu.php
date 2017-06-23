<?php

// Add whole new submenu
$sm['daten_left'][] = 'publishers';
$sm['daten'][] = 'publishers';

function my_submenu_event_publisher_publishers($position, $state) {
	global $ko_path;

	//Build basic information for new submenu
	$submenu = array();
	$submenu['titel'] = getLL('my_event_publisher_submenu_publishers');
	$submenu['mod'] = 'daten';
	$submenu['id'] = 'publishers';
	$submenu['sesid'] = session_id();
	$submenu['position'] = $position;
	$submenu['state'] = $state;


	//Create array holding the labels
	$submenu['output'] = array(
		0 => ($_SESSION['show']=='event_publisher_add' ? '<b>' : '').getLL('my_event_publisher_add_publisher').($_SESSION['show']=='event_publisher_add' ? '</b>' : ''),
		1 => ($_SESSION['show']=='event_publisher_list' ? '<b>' : '').getLL('my_event_publisher_list_publishers').($_SESSION['show']=='event_publisher_list' ? '</b>' : ''),
	);
	//Array holding the links
	$submenu['link'] = array(
		0 => $GLOBALS['BASE_URL'].'daten/index.php?action=event_publisher_add',
		1 => $GLOBALS['BASE_URL'].'daten/index.php?action=event_publisher_list',
	);

	return $submenu;
}
?>

