<?php

class EP_publisher {
	var $config = array();
	var $sendTime = 0;
	var $range = array();
	var $events = array();

	function __construct($config) {
		$this -> config = $config;

		// fix time stamp, if necessary:
		$this -> config['lastsent'] = ($this -> config['lastsent']) ? strtotime($this -> config['lastsent']) : 0;

		//calculate normal sendTime:
		$this -> sendTime = $this -> getSendTime();

		//get time range:
		$this -> setRange();

		// load events
		$this -> loadEvents();
	}

	function getSendTime() {
		static $wkDays = array('sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday');

		//$now = CalX_email::dateCalc(time(), $config['base']['server']['timecorrection']);
		$now = time();
		list($rule['hour'], $rule['minute']) = explode(':', $this -> config['sendtime']);

		switch($this->config['frequency']) {
			case 0 :
			//daily
				$target = mktime($rule['hour'], $rule['minute'], 0, date('m', $now), date('d', $now), date('Y', $now));
				if ($now < $target)
					$target = EP_date::calc($target, '-1 day');
				break;
			case 1 :
			//weekly
				$target = mktime($rule['hour'], 0, 0, date('m', $now), date('d', $now), date('Y', $now));
				$today['wkDay'] = date('w', $now);
				//$rule['wkDay'] = array_search($day, $wkDays);
				$rule['wkDay'] = $this -> config['weekday'];
				$dist = $rule['wkDay'] - $today['wkDay'];
				if ($dist > 0)
					$dist = $dist - 7;
				$target = $target + ($dist * 86400);
				break;
			case 2 :
			//monthly
				$target = mktime($rule['hour'], 0, 0, date('m', $now), $day, date('Y', $now));
				if ($now < $target)
					$target = EP_date::calc($target, '-1 month');
				break;
		}
		return $target;
	}

	function setRange() {
		$this -> range = new EP_range();
		$this -> range -> start = EP_date::calc(EP_date::dayStart($this -> sendTime), $this -> config['offset']);
		$this -> range -> end = EP_date::calc($this -> range -> start, $this -> config['daterange']);
	}

	function loadEvents() {
		global $access;

		$eventData = db_select_data('ko_event', $this -> range -> where(), '*', '', 'ORDER BY startdatum, startzeit ASC');
		$this -> events = array();
		foreach ($eventData as $event) {
			// check access rights, must be at least READ (1)
			if ($access['daten'][$event['eventgruppen_id']] >= 1) {
				// filter by categories
				$permit = true;
				if ($this->config['categories']) {
					$permit = false;
					$eventCats = explode(',',$event['my_vmfds_events_categories']);
					$cats = explode(',', $this->config['categories']);
					foreach ($cats as $cat) {
						$permit = (($permit) || (in_array($cat, $eventCats)));
					}
				}
				if ($permit) $this -> events[] = new EP_event($event);
			}
		}
		return $this -> events;
	}

	function shouldPublish() {
		return ($this->sendTime!=$this->config['lastsent']);
	}
	
	function qualifiedTo($destination) {
		if ($destination['nachname']) {
			$to = '"'.($destination['vorname'] ? $destination['vorname'].' ' : '')
			      .$destination['nachname'].'" <'.$destination['to'].'>';
		} elseif ($destination['firm']) {
			$to = $destination['firm'].'" <'.$destination['to'].'>';
		} else {
			$to = $destination['to'];
		}
		return $to;
	}

	function publish($smarty) {
		if (count($this->events)) {
			$smarty->assign('pub', $this->config);
			$smarty->assign('events', $this->formatEvents());
			$smarty->assign('range', $this->range->toArray());
			$dest = $this->uniqueRecipients(array_merge($this->getRecipients(), $this->getGroupRecipients()));
			foreach ($dest as $destination) {
				$smarty->assign('mail', $destination);
				$mailText = $smarty->fetch('string:'.$this->config['template']);
				
				// build headers:
				$headers = array(
									'From: "'.ko_get_setting('info_name').'" <'.ko_get_setting('info_email').'>',
									'Organization: '.ko_get_setting('info_name'), 'Bcc: christoph_fischer@volksmission.de'		
								);
				if ($this->config['reply_to']) $headers[] = 'Reply-To: '.$this->getReplyTo();
				
				// send the mail:
				$to = $this->qualifiedTo($destination);
				echo 'Sending mail to '.htmlspecialchars($to).'<br />';
				
				if (EP_DEBUG) {
					// debug override: all mail deviated to chris@toph.de
					$mailText = 'Original-To: '.$to."\n\n".$mailText;
					$to = 'chris@toph.de';
				}	
				mail($to, $this->config['title'], $mailText, join("\n", $headers));
			}
		} else {
			echo 'No events.<br />';
		}
		$this->setPublicationFlag();
	}
	
	function setPublicationFlag() {
		db_update_data('ko_event_publishers', 'WHERE (id='.$this->config['id'].')', array('lastsent' => date('Y-m-d H:i:s', $this->sendTime)));
	}
	
	function uniqueRecipients ($people) {
		$ids = array();
		$out = array();
		foreach ($people as $person) {
			if (!in_array($person['id'], $ids)) {
				$ids[] = $person['id'];
				$out[] = $person;
			}
		}
		return $out;
	}
	
	function getRecipients($where = ''){
		$out = array();
		if (!$where) {
			$where = ' (id IN ('.$this->config['recipients'].'))';
		}
		$people = db_select_data('ko_leute', 'WHERE '.$where, '*');
		foreach ($people as $person) {
			ko_get_leute_email($person, $email);
			$email = (is_array($email) ? $email[0] : $email);
			if ($email) {
				$person['to'] = $email;
				$person['salutation'] = array(
											'general' 	=> getLL('my_event_publisher_salutation_general'),
											'formal'  	=> getLL('my_event_publisher_salutation_formal_'.$person['geschlecht']),
											'informal'	=> getLL('my_event_publisher_salutation_informal_'.$person['geschlecht']),
										);
				$out[] = $person;
			}
		}
		return $out;
	}
	
	function getReplyTo() {
		$replies = $this->getRecipients(' (id IN ('.$this->config['reply_to'].'))');
		foreach ($replies as $person) {
			$out[] = $this->qualifiedTo($person);
		}
		return join(',',$out);
	}
	
	function getGroupRecipients() {
		$out = array();
		$groups = explode(',', $this->config['recipient_groups']);
		foreach ($groups as $id) {
			if (trim($id)) {
				$where = '(groups REGEXP \''.$id.'\')';
				$out = array_merge($out, $this->getRecipients($where));
			}
		} 
		return $out;
	}
	
	function formatEvents() {
		$events = array();
		foreach ($this->events as $event) {
			$event = $event->toArray();
			if ($this->config['groupbyday']) { 
				$events[$event['day']][] = $event;
			} else {
				$events[] = $event;
			} 
		}
		return $events;
	}

}
