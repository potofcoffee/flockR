<?php
$KOTA['ko_groups']['vmfds_kool_typo3integration_usergroup'] = array(
		'form' => array('type' => 'doubleselect',
				'params' => 'size="10"',
				'values' => array(),
				'descs' => array(),
		)
);


// get categories select:
$cats = db_select_data('usrdb_vmfredbb_t6.fe_groups','','*','','',FALSE,TRUE);
foreach ($cats as $cat) {
	$KOTA['ko_groups']['vmfds_kool_typo3integration_usergroup']['form']['values'][] = $cat['uid'];
	$KOTA['ko_groups']['vmfds_kool_typo3integration_usergroup']['form']['descs'][] = $cat['title'];
}


