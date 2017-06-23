<?php

namespace Peregrinus\Flockr\Core;

class AbstractModule extends AbstractClass
{

    protected static $instance = [];

    protected $permittedActions = [];
    protected $repositoryConfig = [];
    protected $configurationManager = null;
    protected $config = [];
    protected $name;

    public function __construct()
    {
        parent::__construct();
        $this->name = $this->moduleInfo['module'];
        $this->configurationManager = \Peregrinus\Flockr\Core\ConfigurationManager::getInstance();
        $this->config = $this->configurationManager->getConfigurationSet($this->moduleInfo['module'],
            'Modules/' . $this->moduleInfo['module'] . '/Configuration');
    }

    public static function getInstance()
    {
        $className = get_called_class();
        if (!isset(self::$instance[$className])) {
            self::$instance[$className] = new $className();
        }
        return self::$instance[$className];
    }

    /**
     * Get the menu for this module
     * @return array Menu configuration
     */
    public static function getMenu()
    {
        $class = get_called_class();
        $moduleKey = str_replace('Module', '', (new \ReflectionClass($class))->getShortName());
        $moduleMenu = ConfigurationManager::getInstance()->getConfigurationSet('Menu',
            'Modules/' . $moduleKey . '/Configuration/');
        // check permissions for each menu entry
        $permissionsService = App::getInstance()->getPermissionsService();
        if ((isset($moduleMenu['menu'])) && (is_array($moduleMenu['menu']))) {
            foreach ($moduleMenu['menu'] as $key => $item) {
                $expectedAccess = App::getInstance()->getPermissionsService()->getExpectedAccessLevel(
                    $moduleKey, $item['controller'], $item['action']
                );


                if (isset($expectedAccess['level'])) {
                    if (!isset($expectedAccess['module'])) $expectedAccess['module'] = $moduleKey;
                    if (!isset($expectedAccess['object'])) $expectedAccess['object'] = '*';

                    // check permission
                    $hasPermission = App::getInstance()->getPermissionsService()->getPermission(
                        $expectedAccess['module'],
                        $expectedAccess['object']
                    );
                    if ((!$hasPermission) || ($hasPermission < $expectedAccess['level'])) {
                        unset ($moduleMenu['menu'][$key]);
                    }
                }
            }
            if (count($moduleMenu['menu'])) {
                return [
                    $key => $moduleMenu,
                ];
            } else {
                return [];
            }
        } else {
            return [];
        }
    }

    public function init()
    {

    }

    public function getName()
    {
        return $this->name;
    }

    public function getDefaultController()
    {
        if (count($this->permittedActions)) {
            reset($this->permittedActions);
            return key($this->permittedActions);
        } else {
            return false;
        }
    }

    public function getDefaultAction($controllerName)
    {
        $tmp = explode(',', $this->permittedActions[$controllerName]);
        if (count($tmp)) {
            return $tmp[0];
        } else {
            return false;
        }
    }

    public function getControllerClass($controller)
    {
        return $this->moduleInfo['ns'] . 'Controllers\\' . ucfirst($controller) . 'Controller';
    }

    /**
     * @return array
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * @param array $config
     */
    public function setConfig($config)
    {
        $this->config = $config;
    }

    public function hasRepositoryConfig()
    {
        return (count(glob($this->getBasePath() . 'Configuration/Repository/*')) > 0);
    }

    public function getBasePath()
    {
        return FLOCKR_basePath . 'Modules/' . ucfirst($this->moduleInfo['module']) . '/';
    }

    /**
     * Get repository configuration (formerly: KOTA)
     * @return array Repository configuration
     */
    public function getRepositoryConfig()
    {
        if (count($this->repositoryConfig)) {
            // cached configuration
            return $this->repositoryConfig;
        } else {
            $this->repositoryConfig = [];
            $confFiles = glob($this->getBasePath() . 'Configuration/Repository/*.php');
            foreach ($confFiles as $confFile) {
                $this->repositoryConfig[strtolower(pathinfo($confFile, PATHINFO_FILENAME))] = include($confFile);
            }
            return $this->repositoryConfig;
        }
    }

    public function getRepositories()
    {
        return [$this->getBasePath() . 'Configuration/Repository/'];
    }

    public function getDomain()
    {
        $domain = [];
        foreach (glob($this->getBasePath() . 'Configuration/Domain/*.yaml') as $domainFile) {
            $domain = array_merge_recursive(yaml_parse_file($domainFile), $domain);
        }
        return $domain;
    }


    /**
     * Called on app initialization
     * @param App $app App
     */
    public function onInitializeApp(\Peregrinus\Flockr\Core\App $app)
    {

    }

    public function getUserLevels()
    {
        return $this->configurationManager->getConfigurationSet('UserRights',
            'Modules/' . $this->moduleInfo['module'] . '/Configuration');
    }


    public function getPermissionObjects()
    {
        return ['*'];
    }
}