<?php

// form filler demo

require_once('lib/forge_fdf.php');  

// leave this blank if we're associating the FDF w/ the PDF via URL
//$pdf_form_url= 'http://www.volksmission-freudenstadt.de/intranet/plugins/form_filler/demo/Mitgliedsantrag.pdf';
$pdf_form_url = '';

// default data; these two arrays must ultimately list all of the fields
// you desire to alter, even if you just want to set the 'hidden' flag;
//
//
$fdf_data_names= array(); // none of these in this example
$fdf_data_strings= array(); // none of these in this example

$fdf_data_strings = array(
	'Nachname' => 'Mustermann',
	'Vorname' => 'Karl',
	'Datenschutz' => 'Yes',
);

$fields_hidden= array();
$fields_readonly= array();

// set this to retry the previous state
$retry_b= false;

header( 'Content-type: application/vnd.fdf' );
//header( 'Content-Disposition: attachment; filename="Download.fdf' );

echo forge_fdf( $pdf_form_url,
        $fdf_data_strings, 
        $fdf_data_names,
        $fields_hidden,
        $fields_readonly );
