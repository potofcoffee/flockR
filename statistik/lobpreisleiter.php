<pre>
<?php

class koolDB extends PDO {
	
	private $qLeaders;
	
	function __construct() {
		parent::__construct('mysql:host=localhost;dbname=usrdb_vmfredbb_kool', 'vmfredbb', 'fdsvmec');
		// 		'SELECT event.startdatum, rota.schedule FROM `ko_rota_schedulling` rota LEFT JOIN `ko_event` event ON (rota.event_id=event.id) WHERE (rota.team_id=3) AND (event.startdatum>=\':start\') AND (event.enddatum<=\':end\') ORDER BY event.startdatum'
	}
	
	function findLeaders($year) {
		return $this->query('SELECT event.startdatum, rota.schedule FROM `ko_rota_schedulling` rota LEFT JOIN `ko_event` event ON (rota.event_id=event.id) WHERE (rota.team_id=3) AND (event.startdatum>=\''.$year.'-01-01\') AND (event.enddatum<=\''.$year.'-12-31\') ORDER BY event.startdatum')->fetchAll(PDO::FETCH_ASSOC);
	}

	
	function findPersonById($id) {
		$p = $this->query('SELECT * FROM ko_leute WHERE id='.$id)->fetch();
		return $p['vorname'];
	}
}


//////////////////////////////////////////////////////////////////////////////////////

try {
$db = new koolDB();


$year = $_GET['year'] ? $_GET['year'] : date('Y');
	$leaders = $db->findLeaders($year);
	foreach ($leaders as $leader) {
		if (is_numeric($leader['schedule'])) 
			$leader['schedule'] = $db->findPersonById($leader['schedule']);
		$tmp = explode('-', $leader['startdatum']);
		echo $tmp[2].'.'.$tmp[1].'.'.$tmp[0].';'.$leader['schedule']."\r\n";
	}
} catch (PDOException $e) {
	print "Error!: " . $e->getMessage() . "<br/>";
	die();	
}