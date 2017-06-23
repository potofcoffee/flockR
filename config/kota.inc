<?php

$tmp_social = array(
    'form' => array(
	'type' => 'text',
	'params' => 'size="60"',
    ),
);

foreach (array('facebook', 'twitter', 'myspace', 'linkedin', 'kwick') as $tmp_key) 
    $KOTA['ko_leute'][$tmp_key] = $social;
    
unset($tmp_social);
unset($tmp_key);