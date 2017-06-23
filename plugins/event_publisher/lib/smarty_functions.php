<?php

function smarty_resource_string_source($tpl_name, &$tpl_source, &$smarty)
{
	$tpl_source = $tpl_name;
    //$tpl_source = utf8_encode($tpl_name);
    return true;
}

function smarty_resource_string_timestamp($tpl_name, &$tpl_timestamp, &$smarty)
{
    $tpl_timestamp = time(); 
    return true;
}

function smarty_resource_string_secure($tpl_name, &$smarty)
{
    return true;
}

function smarty_resource_string_trusted($tpl_name, &$smarty)
{
    return true;
}

$smarty->register_resource('string', array(
										'smarty_resource_string_source',
										'smarty_resource_string_timestamp',
										'smarty_resource_string_secure',
										'smarty_resource_string_trusted',
						   ));
