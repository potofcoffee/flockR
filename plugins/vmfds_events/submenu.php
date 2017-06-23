<?php
// Add whole new submenu
$sm['daten_left'][] = 'event_cats';
$sm['daten'][] = 'event_cats';

if (!function_exists('my_submenu_vmfds_events_event_cats')) {
function my_submenu_vmfds_events_event_cats($position, $state) {
	global $ko_path;

	//Build basic information for new submenu
	$submenu = array();
	$submenu['titel'] = getLL('my_vmfds_events_submenu_event_cats');
	$submenu['mod'] = 'daten';
	$submenu['id'] = 'event_cats';
	$submenu['sesid'] = session_id();
	$submenu['position'] = $position;
	$submenu['state'] = $state;


	//Create array holding the labels
	$submenu['output'] = array(
		0 => ($_SESSION['show']=='vmfds_events_cat_add' ? '<b>' : '').getLL('my_vmfds_events_add_category').($_SESSION['show']=='vmfds_events_cat_add' ? '</b>' : ''),
		1 => ($_SESSION['show']=='vmfds_events_cat_list' ? '<b>' : '').getLL('my_vmfds_events_list_categories').($_SESSION['show']=='vmfds_events_cat_list' ? '</b>' : ''),
	);
	//Array holding the links
	$submenu['link'] = array(
		0 => $GLOBALS['BASE_URL'].'daten/index.php?action=vmfds_events_cat_add',
		1 => $GLOBALS['BASE_URL'].'daten/index.php?action=vmfds_events_cat_list',
	);

	return $submenu;
}
}