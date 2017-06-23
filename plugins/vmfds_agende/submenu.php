<?php

if (!function_exists('my_plugin_vmfds_agende_register_submenu')) {
    if ($access['rota']['MAX'] > 3) {
        \Peregrinus\Flockr\Core\Services\HookService::getInstance()->addFilter('build_menu', 'my_plugin_vmfds_agende_register_submenu');
    }

    function my_plugin_vmfds_agende_register_submenu($menu)
    {
        $menu['rota']['menu'][] = [
            'name' => getLL('my_vmfds_agende_list'),
            'link' => FLOCKR_baseUrl.'rota/index.php?action=vmfds_agende_list',
        ];
        $menu['rota']['menu'][] = [
            'name' => getLL('my_vmfds_agende_serviceplan'),
            'link' => FLOCKR_baseUrl.'rota/index.php?action=vmfds_agende_serviceplan',
        ];
        return $menu;
    }
}