<?php

class EP_range {
	var $start;
	var $end;
	
	function where() {
		$startDate = date('Y-m-d', $this->start);
		$endDate = date('Y-m-d', $this->end);
		$where = ' WHERE (startdatum>=\''.$startDate.'\') AND (startdatum<=\''.$endDate.'\')';
		return $where; 
	}
	
	function toArray($dateFormat = '') {
		if ($dateFormat) {
			return array('start' => date($dateFormat, $this->start), 'end' => date($dateFormat, $this->end));
		} else {
			return array('start' => $this->start, 'end' => $this->end);
		}
	}
}

