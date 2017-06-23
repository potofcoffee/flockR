<?php
function my_map_maps_google($data) {
	global $ko_path;

	$link = "";

	$replace = array("�" => "oe", "�" => "ae", "�" => "ue", "�" => "e", "�" => "e", "�" => "a", "�" => "c");
	$address = strtr($data["adresse"], $replace);
	if($address) $link .= "+".$address;

	if($data["plz"]) $link .= "+".$data["plz"];
	if($data["ort"]) $link .= "+".$data["ort"];
	if($data["land"]) $link .= "+".$data["land"];

	if($link != "") {
		$link = "http://maps.google.com/maps?f=q&hl=".$_SESSION["lang"]."&q=".substr($link, 1);
	} else {
		return "";
	}

	$code  = '<a href="'.$link.'" target="_blank">';
	$code .= '<img src="'.$ko_path.'plugins/maps_google/icon_maps_google.gif" border="0" alt="maps_google" title="'.getLL("my_maps_google_show").'" />';
	$code .= '</a>&nbsp;';
	return $code;
}//my_map_maps_google()
?>
