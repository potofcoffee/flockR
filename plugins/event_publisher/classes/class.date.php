<?php

// date auxiliary class

class EP_date {
	
	/**
	 * 
	 */
	function calc($date, $factor) {
		return strtotime(date("Y-m-d H:i:s", $date) . ' '.$factor);
	}
	
	function dayStart($date) {
		return mktime(0,0,0, date('m', $date), date('d', $date), date('Y', $date));
	}
	
}