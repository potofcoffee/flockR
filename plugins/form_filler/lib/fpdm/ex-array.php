<?php

/***************************
  Sample using a PHP array
****************************/

require('fpdm.php');

$fields = array(
	"name"    => "My name",
	"address" => "My address",
	"city"    => "My city",
	"phone"   => "My phone number"
);

$pdf = new FPDM('template.pdf');
$pdf->Load($fields);
$pdf->Merge();
$pdf->Output();
?>
