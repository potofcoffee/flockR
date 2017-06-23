<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2003-2015 Renzo Lauper (renzo@churchtool.org)
*  All rights reserved
*
*  This script is part of the kOOL project. The kOOL project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*  A copy is found in the textfile GPL.txt and important notices to the license
*  from the author is found in LICENSE.txt distributed with these scripts.
*
*  kOOL is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

ob_start();  //Ausgabe-Pufferung starten

$ko_path = "../";
$ko_menu_akt = "tools";

include($ko_path.'inc/ko.inc');
include('inc/tools.inc');
include($ko_path.'inc/class.mcrypt.php');

//Redirect to SSL if needed
ko_check_ssl();

if(!ko_module_installed("tools") || $_SESSION["ses_username"] != "root") {
	header("Location: ".$BASE_URL."index.php");  //Absolute URL
}

ob_end_flush();  //Puffer flushen

$notifier = koNotifier::Instance();
$default_lang = $LIB_LANGS[0];

ko_get_access('tools');

//Smarty-Templates-Engine laden
require($ko_path.'inc/smarty.inc');

//kOOL Table Array
ko_include_kota(array('ko_scheduler_tasks'));


//*** Plugins einlesen:
$hooks = hook_include_main("tools");
if(sizeof($hooks) > 0) foreach($hooks as $hook) include_once($hook);


//***Action auslesen:
if($_POST["action"]) {
	$do_action = $_POST["action"];
	$action_mode = "POST";
} else if($_GET["action"]) {
	$do_action = $_GET["action"];
	$action_mode = "GET";
} else {
	$do_action = $action_mode = "";
}


switch($do_action) {

	case "testmail":
		if (!isset($MAIL_TRANSPORT)) {
			$notifier->addError(9);
		}
		else {
			$_SESSION['show'] = 'testmail';
		}
	break;

	case "submit_mail_transport":
		$data = $_POST['mail_transport'];
		$data['ssl'] = $data['ssl'] == '1' ? 'true' : 'false';
		$data['tls'] = $data['tls'] == '1' ? 'true' : 'false';

		// save changes to ko_config
		$dataS = sprintf('$MAIL_TRANSPORT' . " = array('method' => '%s', 'host' => '%s', 'port' => %d, 'ssl' => %s, 'tls' => %s, 'auth_user' => '%s', 'auth_pass' => '%s');",
				$data['method'],
				$data['host'],
				$data['port'],
				$data['ssl'],
				$data['tls'],
				$data['auth_user'],
				$data['auth_pass']) . "\n";
		$success = ko_update_ko_config("mail_transport", $dataS);

		if (!$success) {
			$notifier->addError(11);
		}
		else {
			$notifier->addInfo(7);
			// update $MAIL_TRANSPORT variable
			$data['ssl'] = $data['ssl'] == 'true' ? true : false;
			$data['tls'] = $data['tls'] == 'true' ? true : false;
			$MAIL_TRANSPORT = $data;
		}
		break;

	case "submit_testmail":
		if (!isset($MAIL_TRANSPORT)) {
			$notifier->addError(9);
		}
		else {
			$success = ko_send_mail('noreply@churchtool.org', $_POST['testmail']['receiver'], $_POST['testmail']['subject'], $_POST['testmail']['text']);
			if (!$notifier->hasNotifications(koNotifier::DEBUG | koNotifier::ERROR) && $success) {
				$notifier->addInfo(6, '', array($_POST['testmail']['receiver']));
			}
			else if (!$notifier->hasNotifications(koNotifier::DEBUG | koNotifier::ERROR) && !$success) {
				$notifier->addError(10);
			}

		}
		break;

	case "list_submenus":
  	$_SESSION["show_start"] = 1;
		$_SESSION["show"] = "list_submenus";
	break;


	case "show_leute_db":
		$_SESSION["show"] = "show_leute_db";
	break;


	case "show_leute_formular":
		$_SESSION["show"] = "show_leute_formular";
	break;


	case "show_familie_db":
		$_SESSION["show"] = "show_familie_db";
	break;


	case "save_leute_db":
	case "save_familie_db":
		//Get old values for logging and to keep the languages not present in this installation
		if($do_action == "save_leute_db") {
			$setting = "leute_col_name";
		} else {
			$setting = "familie_col_name";
		}
		$old = unserialize(ko_get_setting($setting));

		//Store new values for web languages
		$col_names = array();
		foreach($_POST["txt_col_name"] as $lang => $value) {
			foreach($value as $col_i => $col) {
				$col_names[$lang][$col_i] = format_userinput($col, "js");
			}
		}

		//Add unchanged values for other languages
		$weblangs = array();
		foreach($WEB_LANGS as $lang) {
			list($l, $l2) = explode('_', $lang);
			if(!in_array($l, $weblangs)) $weblangs[] = $l;
		}
		$diff_lang = array_diff($LIB_LANGS, $weblangs);
		foreach($diff_lang as $lang) {
			if(!$lang) continue;
			$col_names[$lang] = $old[$lang];
		}

		//Store new names
		$new_col_names = serialize($col_names);
		if($new_col_names) {
			ko_set_setting($setting, $new_col_names);
			foreach($WEB_LANGS as $lang) {
				list($l, $l2) = explode('_', $lang);
				$log_message = '';
				foreach($old[$l] as $i => $o) {
					if($o != $col_names[$l][$i]) $log_message .= $o.' --> '.$col_names[$l][$i].', ';
				}
				if($log_message != '') ko_log($setting, $l.': '.$log_message);
			}
			$notifier->addInfo(2, $do_action);
		}
	break;


	case "plugins_list":
		$_SESSION["show"] = "plugins_list";
	break;


	case "plugins_install":
		//ID of the plugin to be installed
		$new_plugin = format_userinput(($action_mode=="POST"?$_POST["id"]:$_GET["id"]), "alphanum+");
		$plugins_available = ko_tools_plugins_get_available();
		$plugins_installed = ko_tools_plugins_get_installed($plugins_available);
		//Not in list of available plugins
		if(!in_array($new_plugin, $plugins_available)) $notifier->addError(2, $do_action);
		//Already installed
		if(in_array($new_plugin, $plugins_installed)) continue;
		//Check for config-file
		$conf_file = $ko_path."plugins/".$new_plugin."/config.php";
		if(!file_exists($conf_file)) $notifier->addError(3, $do_action);
		else include($conf_file);
		//Check for a type
		if(!isset($PLUGIN_CONF[$new_plugin]["type"]) || $PLUGIN_CONF[$new_plugin]["type"] == "") $notifier->addError(4, $do_action);
		//Check for all dependencies
		foreach(explode(",", $PLUGIN_CONF[$new_plugin]["dependencies"]) as $dep) {
			if(!$dep) continue;
			if(!in_array($dep, $plugins_installed)) $notifier->addError(5, $do_action);
		}
		//check for DB-Query to be performed for this extension
		$sql_filename = $ko_path."plugins/".$new_plugin."/db.sql";
		if(file_exists($sql_filename)) {
			$sql = file_get_contents($sql_filename);
			db_import_sql($sql);
		}

		if(!$notifier->hasErrors()) {
			$PLUGINS[] = array("name" => $new_plugin, "type" => $PLUGIN_CONF[$new_plugin]["type"]);
			$data = '$PLUGINS = array('."\n";
			foreach($PLUGINS as $plugin) {
				$data .= "\t".sprintf('array("name" => "%s", "type" => "%s"),', $plugin["name"], $plugin["type"])."\n";
			}
			$data .= ');'."\n";
			ko_update_ko_config("plugins", $data);
		}
	break;


	case "plugins_delete":
		//ID of the plugin to be deinstalled
		$del_plugin = format_userinput(($action_mode=="POST"?$_POST["id"]:$_GET["id"]), "alphanum+");
		$plugins_available = ko_tools_plugins_get_available();
		$plugins_installed = ko_tools_plugins_get_installed($plugins_available);
		//Not in list of available plugins or not installed
		if(!in_array($del_plugin, $plugins_available) || !in_array($del_plugin, $plugins_installed)) continue;
		//Check for config-file
		$conf_file = $ko_path."plugins/".$del_plugin."/config.php";
		if(!file_exists($conf_file)) $notifier->addError(3, $do_action);
		else include($conf_file);
		//Check for all dependencies
		foreach($plugins_installed as $plugin) {
			//Read in config
			$conf_file = $ko_path."plugins/".$plugin."/config.php";
			if(file_exists($conf_file)) {
				include($conf_file);
				foreach(explode(",", $PLUGIN_CONF[$plugin]["dependencies"]) as $dep) if($dep && $dep == $del_plugin) $notifier->addError(6, $do_action);
			}
		}

		if(!$notifier->hasErrors()) {
			foreach($PLUGINS as $i => $plugin) {
				if($plugin["name"] == $del_plugin) unset($PLUGINS[$i]);
			}
			$data = '$PLUGINS = array('."\n";
			foreach($PLUGINS as $plugin) {
				$data .= "\t".sprintf('array("name" => "%s", "type" => "%s"),', $plugin["name"], $plugin["type"])."\n";
			}
			$data .= ');'."\n";
			ko_update_ko_config("plugins", $data);
		}
	break;


	case "list_ldap_logins":
		if(ko_do_ldap()) {
			$_SESSION["show"] = "list_ldap_logins";
		}
	break;

	
	//Show Exportbutton
	case "ldap_export":
		if(ko_do_ldap()) {
			$_SESSION["show"] = "ldap_export";
		}
	break;


	//Export all DB people entries to LDAP
	case "ldap_do_export":
		if(ko_do_ldap()) {
			$success = 0;
			$errors = array();
			$ldap = ko_ldap_connect();
			ko_get_leute($alle_leute);
			foreach($alle_leute as $p) {
				$ldap_entry = array();
				$id = $p["id"];
				if(ko_ldap_check_person($ldap, $id)) ko_ldap_del_person($ldap, $id);
				//Delete deleted persons
				if($p["deleted"] == 1) {
          continue;
        }
				//Re-add the entry
				$r = ko_ldap_add_person($ldap, $p, $p['id']);
				if($r) $success++;
				else {
					$errors[] = $id;
					print ldap_error($ldap);
					print ldap_errno($ldap);
				}
			}//foreach(alle_leute as p)
			ko_ldap_close($ldap);

			$info_errorcount = sizeof($errors);
			$info_successcount = $success;
			if ( $info_errorcount > 0 ) {
				$notifier->addError(7, $do_action);
			} else {
				$notifier->addInfo(3, $do_action);
			}
		}
	break;


	//Delete old LDAP-Login manually
	case "delete_from_ldap":
		if(ko_do_ldap()) {
			$id = format_userinput($_GET["id"], "text");

			$ldap = ko_ldap_connect();
			//Delete old Login
			if(ko_ldap_check_login($ldap, $id)) {
        ko_ldap_del_login($ldap, $id);
      }
			ko_ldap_close($ldap);
		}
	break;

	//Export a kOOL-Login zu LDAP
	case "export_to_ldap":
		if(ko_do_ldap()) {
			$id = format_userinput($_GET["id"], "uint");
			ko_get_login($id, $login);
			if(!$login["login"] && $login["password"]) continue;

			$ldap = ko_ldap_connect();
			//Delete old Login
			if(ko_ldap_check_login($ldap, $login["login"])) {
        ko_ldap_del_login($ldap, $login["login"]);
      }

			//Save new login
			$data["cn"] = $login["login"];
			$data["sn"] = $login["login"];
			$data["userPassword"] = $login["password"];
			//Add name and email if a person from the db is assigned to this login
			if($login['leute_id'] > 0) {
				ko_get_person_by_id($login['leute_id'], $p);
				if($p['email']) $data['mail'] = $p['email'];
        if($p['vorname'] || $p['nachname']) $data['displayName'] = $p['vorname'].' '.$p['nachname'];
			}
			ko_ldap_add_login($ldap, $data);
			ko_ldap_close($ldap);
		}
	break;


	case "delete_leute_col":
		if(FALSE === $value = format_userinput($_POST["id"], "alphanum+", TRUE)) {
			trigger_error("Ung�ltige id f�r delete_leute_col: ".$_POST["id"], E_USER_ERROR);
		}

		//Zu l�schende Spalten finden
		$cols = db_get_columns("ko_leute");
		$col_namen = unserialize(ko_get_setting("leute_col_name"));
		$found = FALSE;
		foreach($cols as $c) {
			if($c["Field"] == $value) $found = TRUE;
		}
		if(!$found) continue;

		//Spalte aus DB l�schen
		$query = "ALTER TABLE `ko_leute` DROP `$value`";
		mysql_query($query);

		//Eintrag in leute_col_namen l�schen
		foreach($LIB_LANGS as $lang) {
			unset($col_namen[$lang][$value]);
		}
		$new_col_names = serialize($col_namen);
		ko_set_setting("leute_col_name", $new_col_names);

		//Userprefs anpassen
		ko_get_logins($logins);
		$logins[] = array('id' => '-1');  //Add pseudo login to check for global presets as well (user_id = -1)
		foreach($logins as $l) {
			$id = $l["id"];
			$prefs = ko_get_userpref($id, "", "leute_itemset");
			foreach($prefs as $pref_) {
				$pref = explode(",", $pref_["value"]);
				$new_pref = $pref;
				foreach($pref as $p_i => $p) {
					if($p == $value) {
						unset($new_pref[$p_i]);
					}
				}//foreach(pref)
				ko_save_userpref($id, $pref_["key"], implode(",", $new_pref), "leute_itemset");
			}//foreach(prefs)
		}//foraech(logins)

		ko_log("del_leute_col", $value);
		$notifier->addInfo(2, $do_action);
	break;


	case "delete_familie_col":
		if(FALSE === $value = format_userinput($_POST["id"], "alphanum+", TRUE)) {
			trigger_error("Ung�ltige id f�r delete_familie_col: ".$_POST["id"], E_USER_ERROR);
		}

		//Zu l�schende Spalten finden
		$cols = db_get_columns("ko_familie");
		$col_namen = unserialize(ko_get_setting("familie_col_name"));
		$found = FALSE;
		foreach($cols as $c) {
			if($c["Field"] == $value) $found = TRUE;
		}
		if(!$found) continue;

		//Spalte aus DB l�schen
		$query = "ALTER TABLE `ko_familie` DROP `$value`";
		mysql_query($query);

		//Eintrag in familie_col_namen l�schen
		foreach($LIB_LANGS as $lang) {
			unset($col_namen[$lang][$value]);
		}
		$new_col_names = serialize($col_namen);
		ko_set_setting("familie_col_name", $new_col_names);

		ko_log("del_family_col", $value);
		$notifier->addInfo(2, $do_action);
	break;



	case "delete_leute_filter":
		if(FALSE === $id = format_userinput($_POST["id"], "uint", TRUE)) {
			trigger_error("Ung�ltige id f�r delete_leute_filter: ".$_POST["id"], E_USER_ERROR);
		}
		db_delete_data('ko_filter', "WHERE `typ` = 'leute' AND `id` = '$id'");
	break;



	case "add_leute_filter":
	case "reload_leute_filter":
		if(FALSE === $id = format_userinput($_POST["id"], "alphanum+", TRUE)) {
			trigger_error("Ung�ltige id f�r add_leute_filter: ".$_POST["id"], E_USER_ERROR);
		}
		if($do_action == "reload_leute_filter") {
			if(FALSE === $fid = format_userinput($_POST["fid"], "alphanum+", TRUE)) {
				trigger_error("Ung�ltige fid f�r reload_leute_filter: ".$_POST["fid"], E_USER_ERROR);
			}
			$del_query = "DELETE FROM ko_filter WHERE `id` = '$fid' LIMIT 1";
		} else $fid = "";

		$table_cols = db_get_columns("ko_leute");
		foreach($table_cols as $c) {
			if($c["Field"] == $id) $col = $c;
		}
		$col_names = ko_get_leute_col_name();
		$col_name = $col_names[$id];
		//Maxlength
		$endpos = strpos($col["Type"], "(") ? strpos($col["Type"], "(") : strlen($col["Type"]);
		$endpos2 = strpos($col["Type"], ")") ? strpos($col["Type"], ")") : strlen($col["Type"]);
		$max_length = ($endpos && $endpos2) ? substr($col["Type"], ($endpos+1), ($endpos2-$endpos-1)) : 0;

		//find type
		$type  = "";
		$type_ = strtolower($col["Type"]);
		if(substr($type_, 0, 4) == "enum") {
			$type = "enum";
		} else {
			for($i=0; $i<strlen($type_); $i++) {
				if(in_array(substr($type_, $i, 1), explode(",", "a,b,c,d,e,f,g,h,i,j,k,l,m,n,o,p,q,r,s,t,u,v,w,x,y,z"))) {
					$type .= substr($type_, $i, 1);
				}
			}
		}

		switch($type) {
			//Enum-Filter mit Select
			case "enum":
				if($fid != "") mysql_query($del_query);
				$code1  = '<select name="var1" size="0"><option value=""></option>';
				$enums = explode("','",preg_replace("/(enum|set)\('(.+?)'\)/","\\2",$col["Type"]));
				foreach($enums as $e) {
					$code1 .= '<option value="'.$e.'">'.$e.'</option>';
				}
				$code1 .= '</select>';
				$query  = "INSERT INTO `ko_filter` (`id`,`typ`,`dbcol`,`name`,`allow_neg`,`sql1`,`numvars`,`var1`,`code1`)";
				$query .= " VALUES ('$fid', 'leute', '".$col['Field']."', '$col_name', '1', '".$col["Field"]." REGEXP ''[VAR1]''', '1', '$col_name', '$code1')";
				mysql_query($query);
				$notifier->addInfo(4, $do_action);
			break;
			//Text-Filter mit Textfeld
			case "varchar":
			case "tinytext":
			case "mediumtext":
			case "text":
			case "longtext":
			case "blob":
			case "tinyint":
			case "smallint":
			case "int":
			case "mediumint":
			case "bigint":
				if($fid != "") mysql_query($del_query);
				$query  = "INSERT INTO `ko_filter` (`id`,`typ`,`dbcol`,`name`,`allow_neg`,`sql1`,`numvars`,`var1`,`code1`)";
				$query .= " VALUES ('$fid', 'leute', '".$col['Field']."', '$col_name', '1', '".$col["Field"]." REGEXP ''[VAR1]''', '1', '$col_name', '<input type=\"text\" name=\"var1\" size=\"12\" maxlength=\"$max_length\" onkeydown=\"if ((event.which == 13) || (event.keyCode == 13)) { this.form.submit_filter.click(); return false;} else return true;\" />');";
				mysql_query($query);
				$notifier->addInfo(4, $do_action);
			break;
			//Datums-Filter mit Ober- und Untergrenze
			case "date":
				if($fid != "") mysql_query($del_query);
				$query  = "INSERT INTO `ko_filter` (`id`, `typ`,`dbcol`,`name`,`allow_neg`,`sql1`,`sql2`,`numvars`,`var1`,`code1`,`var2`,`code2`)";
				$query .= " VALUES ('$fid', 'leute', '".$col['Field']."', '$col_name', '1', '".$col['Field']." >= \'[VAR1]\'', '".$col["Field"]." <= \'[VAR2]\'', '2', 'lower (YYYY-MM-DD)', '<input type=\"text\" name=\"var1\" size=\"12\" maxlength=\"10\" />', 'upper (YYYY-MM-DD)', '<input type=\"text\" name=\"var2\" size=\"12\" maxlength=\"10\" />');";
				mysql_query($query);
				$notifier->addInfo(4, $do_action);
			break;
			default:
				$notifier->addError(1, $do_action);
		}//switch(type)
	break;



	case "add_sm":
		$sm = format_userinput($_GET["sm"], "alphanum+");
		$lid = format_userinput($_GET["lid"], "uint");
		$mid = format_userinput($_GET["mid"], "alpha");
		$pos = format_userinput($_GET["pos"], "alpha", FALSE, 5);

		//Auf vorhandenes Submenu und Login testen
		if(!ko_check_submenu($sm, $mid)) continue;
		ko_get_login($lid, $login);
		if(!$login["login"]) continue;

		//Submenu hinzuf�gen
		ko_tools_add_submenu(array($sm), array($lid), $mid, $pos);

		$notifier->addInfo(1, $do_action);
		$_SESSION["show"] = "list_submenus";
	break;


	case "add_to_modul":
		$modul = format_userinput($_POST["sel_add_modul"], "alpha");
		if(!in_array($modul, $MODULES)) continue;

		$logins = array();
		foreach($_POST["chk"] as $c_i => $c) {
      if($c) {
				if(FALSE === ($value = format_userinput($c_i, "uint", TRUE, 4))) {
          trigger_error("Not allowed logins selection: $c_i", E_USER_ERROR);
        }
        $logins[] = $value;
			}
		}

		ko_tools_add_submenu(ko_get_submenus($modul), $logins, $modul);

		$notifier->addInfo(1, $do_action);
		$_SESSION["show"] = "list_submenus";
	break;



	case "submit_save_leute_formular":
		$file = $_POST['txt_leute_formular'];
		if(get_magic_quotes_gpc()) $file = stripslashes($file);
		$fp = fopen($ko_path."config/leute_formular.inc", "w");
		fputs($fp, $file);
		fclose($fp);
		$notifier->addInfo(5, $do_action);
	break;




	case "ll_overview":
		$_SESSION["show"] = "ll_overview";
	break;


	case "ll_edit":
	case "ll_edit_all":
		$edit_lang = format_userinput($_GET["lang"], "alpha", FALSE, 2);
		$edit_mode = $do_action == "ll_edit" ? "empty" : "all";
		if(!in_array($edit_lang, $LIB_LANGS)) continue;
		else $_SESSION["show"] = "ll_edit";
	break;


	case "ll_edit_submit":
		$edit_lang = format_userinput($_POST["id"], "alpha", FALSE, 2);
		if(!in_array($edit_lang, $LIB_LANGS)) continue;

		//Include default language definitions
		include($ko_path."locallang/locallang.".$default_lang.".php");
		//Include language file of edited language
		include($ko_path."locallang/locallang.$edit_lang.php");
		foreach($LL[$default_lang] as $key => $value) {
			$post_key = str_replace(".", "@@", str_replace(" ", "@", $key));
			if(isset($_POST["ll_txt_".$post_key]) && $_POST["ll_txt_".$post_key]) {
				$LL[$edit_lang][$key] = ko_tools_ll_value($_POST["ll_txt_".$post_key]);
			}
		}
		ko_tools_write_ll_file($LL[$edit_lang], $edit_lang);
		$_SESSION["show"] = "ll_overview";
	break;





	case 'scheduler_add':
		$_SESSION['show'] = 'scheduler_add';
	break;


	case 'submit_new_task':
	case 'submit_edit_task':
		$mode = $do_action == 'submit_edit_task' ? 'edit' : 'new';

		list($table, $cols, $id, $hash) = explode('@', $_POST['id']);
		if($mode == 'edit' && !$id) {
			$notifier->addError(1, $do_action);
		} else {
			$new_id = kota_submit_multiedit('', ($mode == 'edit' ? 'edit_task' : 'new_task'));
			$if = $mode == 'edit' ? $id : $new_id;
			$error = ko_scheduler_set_next_call($id);  // anschauen !!
			if ($error != 0 && $error != NULL) {
				$notifier->addError($error, $do_action);
			}

			$_SESSION['show'] = 'scheduler_list';
		}
	break;


	case 'edit_task':
		$id = format_userinput($_POST['id'], 'uint');
		$_SESSION['show'] = 'edit_task';
		$onload_code = 'form_set_first_input();'.$onload_code;
	break;


	case 'delete_task':
		$id = format_userinput($_POST['id'], 'uint');
		if(!$id) continue;

		db_delete_data('ko_scheduler_tasks', "WHERE `id` = '$id'");
	break;



	case 'scheduler_list':
		$_SESSION['show'] = 'scheduler_list';
	break;


	case 'call_task':
		$taskid = format_userinput($_GET['id'], 'uint');
		$task = db_select_data('ko_scheduler_tasks', "WHERE `id` = '$taskid'", '*', '', '', TRUE);

		$my_tasks = hook_include_scheduler_task();
		foreach($my_tasks as $mt) {
			include_once($mt);
		}

		if(function_exists($task['call'])) {
			call_user_func($task['call']);
			db_update_data('ko_scheduler_tasks', "WHERE `id` = '$taskid'", array('last_call' => date('Y-m-d H:i:s')));
		}
	break;




	case 'typo3_connection':
		$_SESSION['show'] = 'typo3_connection';
	break;


	case 'submit_typo3_connection':
		ko_set_setting('typo3_host', format_userinput($_POST['typo3_host'], 'text'));
		ko_set_setting('typo3_db', format_userinput($_POST['typo3_db'], 'text'));
		ko_set_setting('typo3_user', format_userinput($_POST['typo3_user'], 'text'));

		//Store password encrypted
		$crypt = new mcrypt('aes');
		$crypt->setKey(KOOL_ENCRYPTION_KEY);
		$pwd_enc = $crypt->encrypt($_POST['typo3_pwd']);
		ko_set_setting('typo3_pwd', $pwd_enc);
		unset($pwd_enc);
		unset($_POST['typo3_pwd']);
	break;




	case 'multiedit':
		//Zu bearbeitende Spalten
		$columns = explode(',', format_userinput($_POST['id'], 'alphanumlist'));
		foreach($columns as $column) {
			$do_columns[] = $column;
		}
		if(sizeof($do_columns) < 1) $notifier->addError(4, $do_action);

		//Zu bearbeitende Eintr�ge
		$do_ids = array();
		foreach($_POST['chk'] as $c_i => $c) {
			if($c) {
				if(FALSE === ($edit_id = format_userinput($c_i, 'uint', TRUE))) {
					trigger_error('Not allowed multiedit_id: '.$c_i, E_USER_ERROR);
				}
				$do_ids[] = $edit_id;
			}
		}
		if(sizeof($do_ids) < 1) $notifier->addError(4, $do_action);

		//Daten f�r Formular-Aufruf vorbereiten
		if(!$notifier->hasErrors()) {
			$_SESSION['show_back'] = $_SESSION['show'];

			$order = 'ORDER BY '.$_SESSION['sort'].' '.$_SESSION['sort_order'];
			$_SESSION['show'] = 'multiedit';
		}

		$onload_code = 'form_set_first_input();'.$onload_code;
	break;



	case 'submit_multiedit':
		kota_submit_multiedit(3);

		$_SESSION['show'] = $_SESSION['show_back'] ? $_SESSION['show_back'] : 'scheduler_list';
	break;




	case 'misc':
		$_SESSION['show'] = 'tools_misc';
	break;


	case 'submit_userpref':
		$key = $_POST['sel_userpref_key'];
		$value = $_POST['txt_userpref_value'];
		ko_get_logins($logins);
		foreach($logins as $lid => $login) {
			ko_save_userpref($lid, $key, $value);
		}
		$_SESSION['show'] = 'tools_misc';
	break;





	//Submenus
  case "move_sm_left":
  case "move_sm_right":
    ko_submenu_actions("tools", $do_action);
  break;


	//Default:
  default:
		if(!hook_action_handler($do_action))
	    include($ko_path."inc/abuse.inc");
  break;
}//switch(do_action)


//HOOK: Plugins erlauben, die bestehenden Actions zu erweitern
hook_action_handler_add($do_action);


//Defaults einlesen
if(!$_SESSION["show_start"]) {
  $_SESSION["show_start"] = 1;
}

$_SESSION["show_limit"] = ko_get_userpref($_SESSION["ses_userid"], "show_limit_logins");
if(!$_SESSION["show_limit"]) $_SESSION["show_limit"] = ko_get_setting("show_limit_logins");

if($_SESSION["sort_tools"] == "") {
  $_SESSION["sort_tools"] = "login";
}
if($_SESSION["sort_tools_order"] == "") {
  $_SESSION["sort_tools_order"] = "ASC";
}

//Include submenus
ko_set_submenues();

echo \Peregrinus\Flockr\Legacy\Services\LayoutService::pageHeader(
    $HTML_TITLE . ': ' . getLL('module_' . $ko_menu_akt),
    FLOCKR_baseUrl . 'images/kOOL_logo.ico'
);

print ko_include_css();
$js_files = [FLOCKR_baseUrl.'inc/jquery/jquery.js', FLOCKR_baseUrl.'inc/kOOL.js'];
if (\Peregrinus\Flockr\Legacy\Services\LoginService::getInstance()->isLoggedIn()) $js_files[] = FLOCKR_baseUrl.'core/js/sessionTimeout';
print ko_include_js();
//include("inc/js-tools.inc");

echo \Peregrinus\Flockr\Legacy\Services\LayoutService::renderSnippet('openBody',
    [
        'onloadCode' => $onload_code,
    ]);

/*
 * Gibt bei erfolgreichem Login das Men� aus, sonst einfach die Loginfelder
 */
include($ko_path . "menu.php");


echo \Peregrinus\Flockr\Legacy\Services\LayoutService::renderSnippet('openSidebar', []);

ko_get_submenu_code("tools", "left");
ko_get_submenu_code("tools", "right");

echo \Peregrinus\Flockr\Legacy\Services\LayoutService::renderSnippet('closeSidebar', []);
echo \Peregrinus\Flockr\Legacy\Services\LayoutService::renderSnippet('openMain', []);


?>
<!-- Hauptbereich -->

<form action="index.php" method="post" name="formular">
<input type="hidden" name="action" id="action" value="" />
<input type="hidden" name="id" id="id" value="" />
<input type="hidden" name="fid" id="fid" value="" />  <!-- Filter-ID f�r Leute-Modul -->
<div name="main_content" id="main_content">

<?php
if($notifier->hasNotifications(koNotifier::ALL)) {
	$notifier->notify();
}

hook_show_case_pre($_SESSION["show"]);

switch($_SESSION["show"]) {
	case "testmail":
		ko_show_testmail();
	break;

	case "list_submenus":
		ko_tools_list_submenus();
	break;

	case "show_leute_db":
		ko_tools_list_leute_db();
	break;

	case "show_leute_formular":
		ko_tools_leute_formular();
	break;

	case "show_familie_db":
		ko_tools_list_familie_db();
	break;

	case "ldap_export":
		if(!$ldap_enabled) continue;
		else ko_tools_ldap_export();
	break;

	case "list_ldap_logins":
		if(!$ldap_enabled) continue;
		else ko_tools_ldap_logins();
	break;

	case "ll_overview":
		ko_tools_ll_overview();
	break;

	case "ll_edit":
		ko_tools_ll_edit($edit_lang, $edit_mode);
	break;

	case "plugins_list":
		ko_tools_plugins_list();
	break;

	case 'scheduler_add':
		ko_formular_task('new');
	break;

	case 'scheduler_list':
		ko_list_tasks();
	break;

	case 'edit_task':
		ko_formular_task('edit', $id);
	break;

	case 'typo3_connection':
		ko_tools_typo3_connection();
	break;

	case 'multiedit':
		ko_multiedit_formular('ko_scheduler_tasks', $do_columns, $do_ids, $order, array('cancel' => 'scheduler_list'));
	break;

	case 'tools_misc':
		ko_tools_misc();
	break;


	default:
		//HOOK: Plugins erlauben, neue Show-Cases zu definieren
    hook_show_case($_SESSION["show"]);
	break;
}//switch(show)

//HOOK: Plugins erlauben, die bestehenden Show-Cases zu erweitern
hook_show_case_add($_SESSION["show"]);

?>
&nbsp;
</div>
</form>


<?php
echo \Peregrinus\Flockr\Legacy\Services\LayoutService::renderSnippet('closeMain', []);
echo \Peregrinus\Flockr\Legacy\Services\LayoutService::renderSnippet('closeBody', []);
