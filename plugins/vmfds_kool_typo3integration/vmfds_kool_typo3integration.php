<?php

// HOOK: extend group form

function my_form_vmfds_kool_typo3integration_ko_groups($data, $mode, $id, $additional_data) {
	if ($mode=='edit') {
		$group = db_select_data('ko_groups','WHERE id='.$id,'*','','',TRUE);
	}
	$group['vmfds_kool_typo3integration_usergroup'] ? $group['vmfds_kool_typo3integration_usergroup'] : '_'; 
	
	$section = array(
		'titel' => 'typo3 Integration',
		'state' => 'closed',
		'colspan' => 2,
	);
	
	$section['row'] = array(
		0 => array(
			'inputs' => array(
				0 => array(
					'desc' => 'Zugeh&ouml;rige Benutzergruppe in typo3:',
					'type' => 'select',
					'name' => 'sel_typo3_usergroup',
					'params' => array('size' => 7),
					'values' => array('_'),
					'descs' => array(''),
					'value' => $group['vmfds_kool_typo3integration_usergroup'],
				), 
			),
		)
	);
	
	
	
	// get categories select:
	$cats = db_select_data('usrdb_vmfredbb_t6.fe_groups','','*','','',FALSE,TRUE);
	foreach ($cats as $cat) {
		$section['row'][0]['inputs'][0]['values'][] = $cat['uid'];
		$section['row'][0]['inputs'][0]['descs'][] = $cat['title'];
	}
		
	$data[] = $section;
}

// HOOK: save new group

function my_action_handler_add_vmfds_kool_typo3integration_submit_new_group() {
	$typo3 = format_userinput($_POST['sel_typo3_usergroup'], 'intlist');
	if ($typo3=='_') $typo3=''; 
	db_update_data('ko_groups',
				   'WHERE id='.sprintf('%06d',$GLOBALS['new_id']), 
				   array(
				   		'vmfds_kool_typo3integration_usergroup' => $typo3,
				   ));
}

// HOOK: save edited group

function my_action_handler_add_vmfds_kool_typo3integration_submit_edit_group() {
	$id = format_userinput($_POST['id'], 'uint');
		
	// check: was this group assigned to a typo3 group before?
	$group = db_select_data('ko_groups', 'WHERE id='.sprintf('%06d', $id), 'vmfds_kool_typo3integration_usergroup', '', '', TRUE);
	$oldTypo3Group = $group['vmfds_kool_typo3integration_usergroup'];
	if ($oldTypo3Group=='_') $oldTypo3Group=''; 
	
	// set new group
	$typo3 = format_userinput($_POST['sel_typo3_usergroup'], 'intlist');
	if ($typo3=='_') $typo3=''; 
	db_update_data('ko_groups',
				   'WHERE id='.sprintf('%06d',$id), 
				   array(
				   		'vmfds_kool_typo3integration_usergroup' => $typo3,
				   ));
				   
	// remove the old group from all users
	if ($oldTypo3Group) {
		$users = db_select_data('usrdb_vmfredbb_t6.fe_users', 'WHERE FIND_IN_SET('.$oldTypo3Group.', usergroup)', 'uid,usergroup', '', '', FALSE, TRUE);
		foreach ($users as $user) {
			$ugs = explode(',', $user['usergroup']);
			$key = array_search($oldTypo3Group, $ugs);
			unset($ugs[$key]);
			db_update_data('usrdb_vmfredbb_t6.fe_users', 'WHERE uid='.$user['uid'], array('usergroup' => join(',', $ugs)));			
		}	
	}

	// add the new usergroup to all users in this group
	$thisGid = sprintf('%06d', $id);
	ko_get_leute($people, 'AND (groups REGEXP \'g'.$thisGid.'\') AND NOT (groups REGEXP \'g'.$thisGid.':g\')');
	foreach ($people as $person) {
		if ($person['typo3_feuser']) {
			vmfds_kool_typo3integration_fix_typo3_user_groups($person['id']);
		}
	} 	
}


//////////////////////////////////////////////////////////////////////////////////////////////////

// HOOK: save edited person

function my_action_handler_add_vmfds_kool_typo3integration_submit_edit_person() {
	$id = format_userinput($_POST["leute_id"], "uint");
	vmfds_kool_typo3integration_fix_typo3_user_groups($id);
}


// HOOK: save new person

function my_action_handler_add_vmfds_kool_typo3integration_submit_neue_person() {
	$id = $GLOBALS["leute_id"];
	vmfds_kool_typo3integration_fix_typo3_user_groups($id);
}

function my_action_handler_add_vmfds_kool_typo3integration_submit_als_neue_person() {
	$id = $GLOBALS["leute_id"];
	vmfds_kool_typo3integration_fix_typo3_user_groups($id);
}



function vmfds_kool_typo3integration_get_typo3_user($userName) {
	return db_select_data('usrdb_vmfredbb_t6.fe_users', 'WHERE (username=\''.$userName.'\')', '*','','',TRUE,TRUE);
}

function vmfds_kool_typo3integration_fix_typo3_user_groups($id) {
	ko_get_person_by_id($id, $person);
	
	if ($person['typo3_feuser']) {
		// get corresponding typo3 user
		$t3User = vmfds_kool_typo3integration_get_typo3_user($person['typo3_feuser']);
		$t3Groups = explode(',',$t3User['usergroup']);
		
		// get all typo3 usergroups
		ko_get_groups($all_groups, 'AND (vmfds_kool_typo3integration_usergroup>0)');
		foreach ($all_groups as $g) {
			$all[] = $g['vmfds_kool_typo3integration_usergroup'];
		}
		
		// get all allowed typo3 usergroups
		$gids = explode(',', $person['groups']);
		foreach ($gids as $gid) {
			$group = ko_groups_decode($gid, 'group');
			if ($group['vmfds_kool_typo3integration_usergroup']) $groups[] = $group['vmfds_kool_typo3integration_usergroup'];
		}		
		
		// first remove all defined typo3 usergroups from the t3User
		foreach ($all as $g) {
			if ($key = array_search($g, $t3Groups)) {
				unset($t3Groups[$key]);
			}
		}
		
		// then add back those that are allowed
		foreach ($groups as $g) {
			$t3Groups[] = $g;
		}

		// write the user back to the typo3 database (only if changed)		
		$t3Groups = join(',', $t3Groups);
		if ($t3Groups != $t3User['usergroup'])
			db_update_data('usrdb_vmfredbb_t6.fe_users', 'WHERE (username=\''.$person['typo3_feuser'].'\')', array('usergroup' => $t3Groups));
	}
	
}
