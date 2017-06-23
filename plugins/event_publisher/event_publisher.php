<?php

function event_publisher_submission() {
	// extract form data from POST
	$data = $_POST["koi"]['ko_event_publishers'];
	kota_process_data('ko_event_publishers', $data, "post");
	return $data;
}

function event_publisher_form($id = 0) {
	global $smarty, $KOTA;
	global $access;

	if($access['daten']['MAX'] < 3) return;
	
	if (!$id) {
		// new publisher
		$mode = 'neu';
		$id = 0;
	} else {
		// editing, so: preload data
		$mode = 'edit';
	}

	//Get preset values for offset, daterange from db
	$values = db_select_distinct('ko_event_publishers', 'offset', '', '', TRUE);
	$KOTA['ko_event_publishers']['offset']['form']['values'] = $values;
	$KOTA['ko_event_publishers']['offset']['form']['descs'] = $values;
	$values = db_select_distinct('ko_event_publishers', 'daterange', '', '', TRUE);
	$KOTA['ko_event_publishers']['daterange']['form']['values'] = $values;
	$KOTA['ko_event_publishers']['daterange']['form']['descs'] = $values;

	
	$form_data['title'] = $mode == 'neu' ? getLL('my_event_publisher_add_publisher') : getLL('my_event_publisher_edit_publisher');
	$form_data['submit_value'] = getLL('save');
	$form_data['action'] = ($mode == 'neu' ? 'event_publisher_submit_a' : 'event_publisher_submit_e');
	$form_data['cancel'] = 'event_publisher_list';

	ko_multiedit_formular('ko_event_publishers', '', $id, '', $form_data);
}


function my_action_handler_event_publisher_edit() {
	$_SESSION['show'] = 'event_publisher_edit';
}


function my_action_handler_event_publisher_add() {
	$_SESSION['show'] = 'event_publisher_add';
}

function my_action_handler_event_publisher_list() {
	$_SESSION['show'] = 'event_publisher_list';
}

function my_action_handler_event_publisher_del() {
	global $info;
	
	db_delete_data('ko_event_publishers', 'WHERE (id='.$_POST['id'].')');
	$info = 'event_publisher_deleted';
	$_SESSION['show'] = 'event_publisher_list';
}


function my_action_handler_event_publisher_submit_a() {
	global $info;

	$publisher_data = event_publisher_submission();
	db_insert_data('ko_event_publishers', $publisher_data);

	// show list:
	$info = 'event_publisher_added';
	$_SESSION['show'] = 'event_publisher_list';
}

function my_action_handler_event_publisher_submit_e() {
	global $info; 
	
	//Check for edit rights
	//if($access['daten']['MAX'] < 3) return;

	list($table, $columns, $id, $hash) = explode("@", $_POST["id"]);
	if(FALSE === ($id = format_userinput($id, "uint", TRUE))) return;

	$publisher_data = event_publisher_submission();
	db_update_data('ko_event_publishers', 'WHERE (id='.$id.')', $publisher_data);

	// show list:
	$info = 'event_publisher_edited';
	$_SESSION['show'] = 'event_publisher_list';
}


function my_show_case_event_publisher_add() {
	event_publisher_form();
}

function my_show_case_event_publisher_edit() {
	event_publisher_form($_POST['id']);
}

function my_show_case_event_publisher_list() {
	global $ko_path;
	global $access;

	if($access['daten']['MAX'] < 3) return;

	$list = new kOOL_listview();

	// no filter!
	$z_where = '';

	// set limits and order
	$z_limit = 'LIMIT ' . ($_SESSION['show_start']-1) . ', ' . $_SESSION['show_limit'];
	$rows = db_get_count('ko_event_publishers', 'id', $z_where);
	$order = ($_SESSION['sort_publishers']) ? ' ORDER BY '.$_SESSION['sort_publishers'].' '.$_SESSION['sort_publishers_order'] : '';

	// get data
	//var_dump($z_where, $order, $z_limit);
	$data = db_select_data('ko_event_publishers', 'WHERE 1=1 '.$z_where, '*', $order, $z_limit);
	
	$list->init('daten', 'ko_event_publishers', array("chk", "edit", "delete"), $_SESSION["show_start"], $_SESSION["show_limit"]);
	$list->setTitle(getLL('my_event_publisher_list_title'));
	$list->setAccessRights(array('edit' => 3, 'delete' => 3), $access['daten']);
	$list->setActions(array("edit" 		=> array("action" => "event_publisher_edit"),
							"delete" 	=> array("action" => "event_publisher_del", "confirm" => TRUE))
										);
	$list->setStats($rows);
	$list->setSort(TRUE, "setsortpublishers", $_SESSION["sort_publishers"], $_SESSION["sort_publishers_order"]);

	if($output) {
		$list->render($data);
	} else {
		print $list->render($data);
	}
}

