<?php

// define ko_event_publishers table
$KOTA['ko_event_publishers'] = array(
	"_listview" => array(
						10 => array("name" => "title", "sort" => "startdatum", "multiedit" => "startdatum,enddatum"),
						20 => array("name" => "recipients", "sort" => "recipients"),
						30 => array("name" => "frequency", "sort" => "frequency"),
						40 => array("name" => "weekday", "sort" => "weekday", "multiedit" => "startzeit,endzeit"),
						50 => array("name" => "sendtime", "sort" => "sendtime"),
					),
	'_listview_default' => array(
						'title', 
						'recipients', 
						'frequency', 
						'weekday', 
						'sendtime', 
					),
	'title' 	=> 	array(
						'post' 		=> 'format_userinput("@VALUE@", "text")',
						'form'		=> array(
											'type'		=> 'text',
											'params'	=> 'size=60',
											'new_row'	=> TRUE,
										),
					),
	'recipients' 	=> 	array(
						'list' 		=> 'FCN:kota_listview_people',
						'form'		=> array(
											'type'		=> 'peopleselect',
											'params'	=> 'size="7" style="width:150px;"',
											'new_row'	=> TRUE,
										),
					),
	'recipient_groups' 	=> 	array(
						'post' 		=> 'format_userinput("@VALUE@", "group_role")',
//						'list' 		=> 'FCN:kota_listview_people',
						'form'		=>  array_merge(
										array(
											'type'		=> 'doubleselect',
											'params'	=> 'size="7" style="width:150px;"',
											'new_row'	=> TRUE,
										),
										kota_get_form('ko_rota_teams', 'groupid')
										),
					),
	'frequency' 	=> 	array(
						'list'		=> 'FCN:kota_listview_event_publisher_select',
						'form'		=> array(
											'type'		=> 'select',
											'values'	=> array(0,1,2),
											'descs'		=> array(
																	getLL('my_event_publisher_freq_daily'),
																	getLL('my_event_publisher_freq_weekly'),
																	getLL('my_event_publisher_freq_monthly'),
																),
										),
					),
	'weekday' 	=> 	array(
						'list'		=> 'FCN:kota_listview_event_publisher_weekdays',
						'form'		=> array(
											'type'		=> 'select',
											'values'	=> array(1,2,3,4,5,6,0),
										),
					),
	'sendtime'	=> array(
						"list" 		=> 'FCN:kota_listview_time',
						"pre" 		=> "sql_zeit('@VALUE@')",
						"post" 		=> "sql_zeit('@VALUE@')",
						"list" 		=> "sql_zeit('@VALUE@')",
						"form" 		=> array(
											"type" 		=> "text",
											"params" => 'size="11" maxlength="11"',
											),
			),
	'offset' 	=> 	array(
						'post' 		=> 'format_userinput("@VALUE@", "text")',
						'form'		=> array(
											'type'		=> 'textplus',
											'params'	=> 'size=60',
											'new_row'	=> TRUE,
										),
					),
	'daterange' 	=> 	array(
						'post' 		=> 'format_userinput("@VALUE@", "text")',
						'form'		=> array(
											'type'		=> 'textplus',
											'params'	=> 'size=60',
											'new_row'	=> TRUE,
										),
					),
	'categories' 	=> 	array(
						'form'		=> array(
											'type'		=> 'doubleselect',
											'params'	=> 'size="7" style="width:150px;"'
										),
					),
	'groupbyday'	=> array(
						'list'		=> 'FCN:kota_listview_boolyesno',
						'form'		=> array(
											'type'		=> 'checkbox',
										),
					),
	'template'		=> array(
						'form'		=> array(
											'type'		=> 'textarea',
											'params'	=> 'cols="60" rows="10"',
										),
					),
	'reply_to' 	=> 	array(
						'list' 		=> 'FCN:kota_listview_people',
						'form'		=> array(
											'type'		=> 'peopleselect',
											'params'	=> 'size="7" style="width:150px;"',
											'new_row'	=> TRUE,
										),
					),
);



// fill in weekdays: 
if (!function_exists('kota_listview_event_publisher_weekdays')) {
	function kota_listview_event_publisher_weekdays(&$value) {
		$monday = date_find_last_monday(date('Y-m-d'));
		$w = array();
		for($i=0; $i<7; $i++) {
			$w[] = strftime('%A', strtotime(add2date($monday, 'tag', $i, TRUE)));
		}
		$value = $w[$value];
		return $w;
	}
}

$KOTA['ko_event_publishers']['weekday']['form']['descs'] = kota_listview_event_publisher_weekdays($myValue); 



if (!function_exists('kota_listview_event_publisher_select')) {
	function kota_listview_event_publisher_select(&$value, $data) {
		global $KOTA;
		$config = $KOTA[$data['table']][$data['col']]['form'];
		$value = $config['descs'][$value];
		//$value = print_r($data, true);
	}
}

// get categories select:
$cats = db_select_data('ko_event_categories','');
foreach ($cats as $cat) {
	$KOTA['ko_event_publishers']['categories']['form']['values'][] = $cat['id'];
	$KOTA['ko_event_publishers']['categories']['form']['descs'][] = $cat['title'];
}