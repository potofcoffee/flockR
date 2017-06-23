<?php
/*
 * FLOCKR
 * Multi-Purpose Church Administration Suite
 * http://github.com/potofcoffee/flockr
 * http://flockr.org
 *
 * Copyright (c) 2016+ Christoph Fischer (chris@toph.de)
 *
 * Parts copyright 2003-2015 Renzo Lauper, renzo@churchtool.org
 * FlockR is a fork from the kOOL project (www.churchtool.org). kOOL is available
 * under the terms of the GNU General Public License (see below).
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */


namespace Peregrinus\Flockr\Legacy;


use Peregrinus\Flockr\Core\App;
use Peregrinus\Flockr\Core\ConfigurationManager;
use Peregrinus\Flockr\Core\Debugger;
use Peregrinus\Flockr\Core\Request;
use Peregrinus\Flockr\Core\Router;
use Peregrinus\Flockr\Core\Services\SessionService;
use Peregrinus\Flockr\Core\Session;
use Peregrinus\Flockr\Core\Loaders\ViewHelperNamespaceLoader;

class LegacyApp
{

    const LEGACYAPP_SUCCESS = 1;

    static $instance = null;
    protected $action = '';
    protected $moduleName = '';
    protected $basePath = '';
    protected $module;
    protected $layout;
    protected $viewHelperNamespaces;
    protected $hasOwnModule = false;
    protected $legacyModule = null;

    public function __construct($moduleName, $path)
    {
        self::$instance = $this;
        global $ko_menu_akt, $ko_path;
/*
        $router = Router::getInstance();
        if ($module = $router->getModule()) {
            $this->module = $module;
            $moduleName = $this->module->moduleInfo['module'];
            $this->legacyModule = new LegacyModule();
            $this->hasOwnModule = true;
        } else {
*/
            // fall back to given default module
            if (class_exists($moduleClass = FLOCKR_NS . ucfirst($moduleName) . '\\' . ucfirst($moduleName) . 'Module')) {
                $this->module = new $moduleClass;
                $this->legacyModule = new LegacyModule();
                $this->hasOwnModule = true;
            } else {
                // fall back to legacyModule
                $this->module = $this->legacyModule = new LegacyModule();
            }
 //       }

        $this->moduleName = $ko_menu_akt = $moduleName;
        $this->basePath = $ko_path = $path;

        $this->setLayout(new LegacyLayout($this));
        $this->layout->load(ConfigurationManager::getInstance()->getConfigurationSet('Layout', 'Modules/Legacy/Configuration/'));

        // ViewHelper namespaces
        $this->viewHelperNamespaces = ViewHelperNamespaceLoader::loadAll();

    }

    /**
     * Get an instance of the app object
     * @return LegacyApp Instance of app object
     */
    static public function getInstance()
    {
        if (null === self::$instance) {
            throw new \Exception('App not initialized. Aborting.');
        }
        return self::$instance;
    }

    public function bootstrap()
    {
        global $do_action, $smarty, $smarty_dir, $ko_path, $BASE_PATH;

        if ($this->moduleName !== 'home') {
            if (!\ko_module_installed($this->moduleName)) {
                header('Location: ' . FLOCKR_baseUrl);
            }
        }

        // include <module>/inc/<module>.inc
        if (file_exists($localInclude = FLOCKR_basePath . $this->moduleName . '/inc/' . $this->moduleName . '.inc')) {
            require_once($localInclude);
        }

        // try the same from LocalApi folder
        if (($this->hasOwnModule) && (file_exists($localInclude = $this->module->getBasePath() . 'LocalApi/' . $this->moduleName . '.inc'))) {
            require_once($localInclude);
        }

        //Redirect to SSL if needed
        ko_check_ssl(); // TODO: move to Router?

        //Handle login/logout
        ko_check_login(); // TODO: move to legacy security api?

        SessionService::getInstance()->setArgument('show', '');

        //Plugins einlesen:
        $hooks = hook_include_main("_all");
        if (sizeof($hooks) > 0) {
            foreach ($hooks as $hook) {
                include_once($hook);
            }
        }

        $do_action = Request::getInstance()->getArgument('action', 'index');
        if (false === format_userinput($do_action, "alpha+", true, 50)) {
            trigger_error("invalid action: " . $do_action, E_USER_ERROR);
        }

        $this->action = ($do_action ? $do_action : 'index');

        // include Smarty (legacy)
        require_once($this->getBasePath() . 'inc/smarty.inc');
    }

    /**
     * @return string
     */
    public function getBasePath()
    {
        return $this->basePath;
    }

    /**
     * @param string $basePath
     */
    public function setBasePath($basePath)
    {
        $this->basePath = $basePath;
    }

    public function run()
    {
        $controllerClass = $this->module->moduleInfo['ns'] . 'Controllers\\' . ucfirst($this->moduleName) . 'Controller';
        if (class_exists($controllerClass)) {
            $controller = new $controllerClass($this->module);
            $actionMethod = lcfirst($this->action) . 'Action';
            if (method_exists($controller, $actionMethod)) {
                $this->dispatchControllerAction($this->module, $controller, $this->action);
                return self::LEGACYAPP_SUCCESS;
            } else {
                return false;
            }

        } else {
            return false;
        }
    }

    /**
     * Dispatch a controller action
     * @param AbstractModule $module Module
     * @param AbstractController $controller Controller object
     * @param string $action Action
     * @throws StopActionException to forward to another action
     */
    public function dispatchControllerAction($module, $controller, $action)
    {

        $this->activeModule = $module->moduleInfo['module'];
        $this->activeController = str_replace('Controller', '', $controller->moduleInfo['class']);
        $this->activeAction = $action;

        $actionMethod = $action . 'Action';
        if (method_exists($controller, $actionMethod)) {
            $view = $this->createView($module, $controller, $action);
            $controller->setView($view);

            try {
                $output = $controller->dispatch($action);
            } catch (StopActionException $e) {
                $action = $controller->getRedirect();
                $this->dispatchControllerAction($module, $controller, $action);
            }

            if (!is_null($output) && (!($output === false))) {
                echo $output;
                exit;
            }

            $view->getRenderingContext()->setControllerName($controller->getName());

            $view->assign('FLOCKR_baseUrl', FLOCKR_baseUrl);
            //$view->assign('layout', $this->getLayout()->getViewData());
            //$view->assign('panels', $controller->getPanels());

            $view->assign('module', $controller->moduleInfo['module']);
            $view->assign('controller', $controller->getName());
            $view->assign('action', $action);

            if ($controller->showView) {
                //$this->view->sendContentTypeHeader();
                $controller->renderView($action);
            }

            exit;
        } else {
            \Peregrinus\Flockr\Core\Logger::getLogger()->addEmergency(
                'Method "' . $actionMethod . '" not implemented in controller' . get_class($this) . ' .');
            throw new \Exception('Method "' . $actionMethod . '" not implemented in this controller.',
                0x01);
        }
    }

    public function createView($module, $controller, $requestedAction, $subFolder = '')
    {
        $view = new \TYPO3Fluid\Fluid\View\TemplateView();
        //$view->setCache(new \TYPO3Fluid\Fluid\Core\Cache\SimpleFileCache(FLOCKR_basePath.'Temp/Cache/'));

        $renderingContext = $view->getRenderingContext();
        //$renderingContext->setLegacyMode(false);
        $viewHelperResolver = $renderingContext->getViewHelperResolver();
        // extend f: namespace with some core VieweHelpers
        $viewHelperResolver->addNamespace('f', FLOCKR_NS . 'Core\\ViewHelpers\\Fluid');
        foreach ($this->viewHelperNamespaces as $ns) {
            $viewHelperResolver->addNamespace('fx', $ns);
        }

        $paths = $view->getTemplatePaths();
        $paths->setTemplateRootPaths($this->getTemplatesPath());
        $paths->setLayoutRootPaths($this->getLayoutsPath());
        $paths->setPartialRootPaths($this->getPartialsPath());
        return $view;
    }

    public function getTemplatesPath()
    {
        $paths = [FLOCKR_basePath . 'Modules/Core/Resources/Private/Templates/'];
        if ($this->hasOwnModule) {
            $paths[] = $this->legacyModule->getBasePath() . 'Resources/Private/Templates';
        }
        $paths[] = $this->module->getBasePath() . 'Resources/Private/Templates';
        return $paths;

    }

    public function getLayoutsPath()
    {
        $paths = [FLOCKR_basePath . 'Modules/Core/Resources/Private/Layouts/'];
        if ($this->hasOwnModule) {
            $paths[] = $this->legacyModule->getBasePath() . 'Resources/Private/Layouts';
        }
        $paths[] = $this->module->getBasePath() . 'Resources/Private/Layouts';
        return $paths;
    }

    public function getPartialsPath()
    {
        $paths = [FLOCKR_basePath . 'Modules/Core/Resources/Private/Partials/'];
        if ($this->hasOwnModule) {
            $paths[] = $this->legacyModule->getBasePath() . 'Resources/Private/Partials';
        }
        $paths[] = $this->module->getBasePath() . 'Resources/Private/Partials';
        return $paths;
    }

    /**
     * @return mixed
     */
    public function getLayout()
    {
        return $this->layout;
    }

    /**
     * @param mixed $layout
     */
    public function setLayout($layout)
    {
        $this->layout = $layout;
    }


}