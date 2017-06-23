<?php
if ($access['reservation']['MAX'] > 3) {
    $my_submenu['reservation']['reservationen']['vmfds_progmatic2014_home'] = array(
        'output' => getLL('my_vmfds_progmatic2014_menu'),
        'link' => $ko_path . 'reservation/index.php?action=vmfds_progmatic2014_home',
        'html' => '',
        'show' => '',
    );
}
