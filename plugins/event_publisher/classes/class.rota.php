<?php

class EP_rota {
	static $teams = array();
	
	function getTeam($id) {
		if (!isset($team[$id])) {
			$team[$id] = db_select_data('ko_rota_teams', 'WHERE (id='.$id.')', '*', '','', TRUE);
		} 
		return $team[$id];
	}
}
