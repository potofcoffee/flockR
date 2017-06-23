<?php

class EP_event {
	var $data = array();
	var $group = array();
	var $rota = array();
	
	/**
	 * constructor
	 *
	 * @return void
	 */
	function __construct($event) {
		$this->data = $event;
		$this->loadGroup();
		$this->loadRota();
		$this->formatData();
	}
	
	/**
	 * load event group information
	 *
	 * @return void
	 */
	function loadGroup() {
		$this->group = db_select_data('ko_eventgruppen', 'WHERE (id='.$this->data['eventgruppen_id'].')', '*', '', '', TRUE);
	}
	
	/**
	 * load rota schedules for the event
	 * 
	 * @return void
	 */
	function loadRota() {
		$this->rota = array();
		$schedules = db_select_data('ko_rota_schedulling', 'WHERE (event_id='.$this->data['id'].')','*','','',FALSE, TRUE);
		foreach ($schedules as $schedule) {
			$people = array();
			$list = array();
			$team = EP_rota::getTeam($schedule['team_id']);
			$operators = explode(',', $schedule['schedule']);
			if (!is_array($operators)) $operators = array($operators);
			foreach ($operators as $key => $operator) {
				if (is_numeric($operator)) {
					$person = db_select_data('ko_leute', 'WHERE (id='.$operator.')', 'vorname, nachname', '', '', TRUE);
					$people[] = $person['vorname'].' '.$person['nachname'];
					$list[] = $person; 					
				} elseif ($operator) {
					$people[] = $operator;
					if (strpos($operator, ' ')) {
						$op = array();
						list($op['vorname'], $op['nachname']) = explode(' ', $operator);
						$operator = $op;
					} 
					$list[] = $operator;
				}
			}
			
			if (count($people)) {
				$out = array('text' => join(', ', $people), 'list' => $list);
				$this->rota[$team['name']] = $out;
			}
		}
	}

	function formatData() {
		// start and end date
		$this->data['start'] = strtotime($this->data['startdatum'].' '.$this->data['startzeit']);
		$this->data['end'] = strtotime($this->data['enddatum'].' '.$this->data['endzeit']);
		$this->data['day'] = strtotime($this->data['startdatum'].' 0:00:00');
	}
	
	function toArray() {
		return array_merge(
							$this->data, 
							array(
								'group'		=> $this->group,
								'rota'		=> $this->rota,
							)				
						  ); 
	}	
	
}
