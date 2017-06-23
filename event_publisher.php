#!/usr/local/pd-admin2/bin/php5
<?php
/*
* EventPublisher -- This file should not be called directly,
* but via a cronjob to process the queue ...
*/
header('Content-type: text/html; charset=iso-8859-1');
//mb_detect_order(array('UTF-8', 'ASCII')); 
//mb_internal_encoding('UTF-8'); 
//mb_http_output('UTF-8'); 
//mb_regex_encoding('UTF-8');

// EP_DEBUG: deviate all mails to chris@toph.de
define ('EP_DEBUG', false);
// EP_SENDANYWAY: send even if already sent
define ('EP_SENDANYWAY', false);

/**
 * Autoloader for auxiliary classes
 * @param class name of the class to be autoloaded
 */
function ep_autoloader($class) {
	$classFile = str_replace('EP_', 'class.', $class).'.php';
    require 'plugins/event_publisher/classes/'.$classFile;
}

spl_autoload_register('ep_autoloader');

// connect to kOOL
require_once('inc/ko.inc');

// load event access rights
ko_get_access('daten');


// prepare Smarty
echo $BASE_PATH;
require_once($BASE_PATH.'inc/smarty.inc');
$smarty = new Smarty();
require_once($BASE_PATH.'plugins/event_publisher/lib/smarty_functions.php');

// force UTF-8:
mysql_query("SET NAMES 'latin1' COLLATE 'latin1_swedish_ci'") or die(mysql_error());  
mysql_query("SET CHARACTER SET 'latin1'") or die(mysql_error());  

// get all scheduled publishers:
$pubs = db_select_data('ko_event_publishers', '');

echo '<pre>'; // this just for debug!



foreach ($pubs as $publisherConfig) {	
	// instantiate publisher object
	$publisher = new EP_publisher($publisherConfig);
//	print_r($publisher);
	
	echo 'This publisher should have sent something on: '.date('Y-m-d H:i:s', $publisher->sendTime). '('.$publisher->sendTime.'), <br />';
	echo 'including events from '.date('Y-m-d H:i:s', $publisher->range->start).' to '.date('Y-m-d H:i:s', $publisher->range->end).'<br />';
	echo 'It was last sent on '.date('Y-m-d H:i:s', $publisher->config['lastsent']).' ('.$publisher->config['lastsent'].')<br />';
	//print_r($publisher);

	if (EP_SENDANYWAY || $publisher->shouldPublish()) {
		$publisher->publish($smarty);
	} else {
		echo 'Therefore, it is not necessary to publish anything right now.';
	}
		
	
	echo '<br /><br />';
}
