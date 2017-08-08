<?php

namespace Peregrinus\Flockr\Core;

use Doctrine\ORM\Tools\Setup;
use Doctrine\ORM\EntityManager;
use Peregrinus\Flockr\Core\Exception\StopActionException;
use Peregrinus\Flockr\Core\Loaders\ApiLoader;
use Peregrinus\Flockr\Core\Loaders\ViewHelperNamespaceLoader;
use Peregrinus\Flockr\Core\Services\PermissionsService;
use Peregrinus\Flockr\Core\Services\PreferencesService;
use Peregrinus\Flockr\Core\Services\TranslationService;
use Peregrinus\Flockr\Core\Services\SessionService;

class App
{

    static $instance = null;
    public $activeModule = '';
    public $activeController = '';
    public $activeAction = '';
    public $entityManager = null;
    protected $modules = [];
    protected $config = [];
    protected $repositoryConfig = [];
    protected $layout;
    protected $securityContext;
    protected $domainManager;
    protected $permissionsService;
    protected $preferencesService;
    protected $translationService;
    protected $viewHelperNamespaces;
    protected $moduleName;

    public function __construct($path = '')
    {
        global $ko_menu_akt, $ko_path;

        // set global vars for legacy kOOL
        $this->moduleName = $ko_menu_akt = Router::getInstance()->getModuleName();
        //$ko_path = $path;

        Logger::initialize();
        $confMan = ConfigurationManager::getInstance();
        $this->config = $confMan->getConfigurationSet('Flockr', 'Configuration/');

        // modules
        $this->modules = ModuleLoader::getModules();
        foreach ($this->modules as $module) {
            $module->init();
        }

        // apis
        $apiTime = microtime(true);
        ApiLoader::loadAll();

        $this->loadRepositoryConfig();
        $repositories = $this->loadRepositories();

        // database:
        $isDevMode = ($this->config['context'] == 'development');
        $ORMConfig = Setup::createYAMLMetadataConfiguration($repositories, $isDevMode);
        $connection = $confMan->getConfigurationSet('Database', 'Configuration/')[$this->config['context']];
        $this->setEntityManager(EntityManager::create($connection, $ORMConfig));

        // domain configuration
        $this->setDomainManager(new DomainManager($this->loadDomain()));

        // translation service
        $this->setTranslationService(new TranslationService());

        // permissions service
        $this->setPermissionsService(new PermissionsService($this->modules));

        // security context
        $this->setSecurityContext(new SecurityContext());

        // preferences service
        $this->setPreferencesService(new PreferencesService());

        $this->setLayout(new Layout($this));
        $this->layout->load($confMan->getConfigurationSet('Layout', 'Modules/Core/Configuration/'));

        // ViewHelper namespaces
        $this->viewHelperNamespaces = ViewHelperNamespaceLoader::loadAll();

        $this->initializeModules();
    }


    public function bootstrap()
    {
        global $do_action, $smarty, $smarty_dir, $ko_path, $BASE_PATH;

        if (!in_array($this->moduleName, ['home', 'core', 'scheduler'])) {
            if (!\ko_module_installed($this->moduleName)) {
                if (!ConfigurationManager::getInstance()->getConfigurationSet('modules')[ucfirst($this->moduleName)]) {
                    if (!defined('FLOCKR_preventRedirectToRoot')) {
                        header('Location: ' . FLOCKR_baseUrl);
                    }
                }
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

        if (!in_array($this->moduleName, ['scheduler'])) {
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
            require_once(FLOCKR_basePath . 'inc/smarty.inc');
        }
    }



    public function initialize() {
    }

    public function initializeModules() {
        foreach ($this->modules as $module) {
            $module->onInitializeApp($this);
        }
    }

    public function loadRepositoryConfig()
    {
        foreach ($this->modules as $module) {
            if ($module->hasRepositoryConfig()) {
                $this->repositoryConfig = array_merge_recursive($this->repositoryConfig,
                    $module->getRepositoryConfig());
            }
        }
    }

    public function loadRepositories()
    {
        $repositories = [];
        foreach ($this->modules as $module) {
            $repositories = array_merge($module->getRepositories(), $repositories);
        }
        return $repositories;
    }

    public function loadDomain()
    {
        $domain = [];
        foreach ($this->modules as $module) {
            $domain = array_merge($module->getDomain(), $domain);
        }
        return $domain;
    }

    /**
     * Get an instance of the app object
     * @return \Peregrinus\Flockr\Core\App Instance of app object
     */
    static public function getInstance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function run()
    {
        $router = Router::getInstance();
        $module = $router->getModule();
        \Peregrinus\Flockr\Core\Debugger::toFile($module, 'module');
        if ($module) {
            Session::initialize();
            $controller = $router->getController();
            $action = $router->getAction($controller);
            $this->dispatchControllerAction($module, $controller, $action);

        } else {
            // no module?
            // leave this to kOOL legacy code ...
        }
    }

    /**
     * Dispatch a controller action
     * @param AbstractModule $module Module
     * @param AbstractController $controller Controller object
     * @param string $action Action
     * @throws StopActionException to forward to another action
     */
    public function dispatchControllerAction(AbstractModule $module, AbstractController $controller, $action) {
        $this->activeModule = $module->moduleInfo['module'];
        $this->activeController = str_replace('Controller', '', $controller->moduleInfo['class']);
        $this->activeAction = $action;

        // check permission
        $expectedAccess = $this->permissionsService->getExpectedAccessLevel(
            $this->activeModule,
            $this->activeController,
            $this->activeAction
        );
        if ($expectedAccess['level']) {
            $hasAccess = $this->permissionsService->getPermission($expectedAccess['module'], $expectedAccess['object']);
        }

        if ((!$expectedAccess['level']) || ($hasAccess >= $expectedAccess['level'])) {
            $actionMethod = $action.'Action';
            if (method_exists($controller, $actionMethod)) {
                $view = $this->createView($module, $controller, $action);
                $controller->setView($view);

                try {
                    $output = $controller->dispatch($action);
                }
                catch (StopActionException $e) {
                    $action = $controller->getRedirect();
                    $this->dispatchControllerAction($module, $controller, $action);
                }

                if (!is_null($output) && (!($output === false))) {
                    echo $output;
                    exit;
                }

                $view->getRenderingContext()->setControllerName($controller->getName());

                $view->assign('layout', $this->getLayout()->getViewData());
                $view->assign('panels', $controller->getPanels());

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
        } else {
            die ('Panic: no permission? '.print_r($this->getSecurityContext()->getUser(), 1));
            //$router->redirectToUrl(FLOCKR_baseUrl);
        }
    }

    public function createView($module, $controller, $requestedAction, $subFolder='') {
        $view = new \TYPO3Fluid\Fluid\View\TemplateView();
        //$view->setCache(new \TYPO3Fluid\Fluid\Core\Cache\SimpleFileCache(FLOCKR_basePath.'Temp/Cache/'));

        $renderingContext = $view->getRenderingContext();
        //$renderingContext->setLegacyMode(false);
        $viewHelperResolver = $renderingContext->getViewHelperResolver();
        // extend f: namespace with some core VieweHelpers
        $viewHelperResolver->addNamespace('f', FLOCKR_NS.'Core\\ViewHelpers\\Fluid');
        if (!count($this->viewHelperNamespaces)) {
            echo 'Loading namespaces...<br />';
            $this->viewHelperNamespaces = ViewHelperNamespaceLoader::loadAll();
        }
        foreach ($this->viewHelperNamespaces as $ns) {
            $viewHelperResolver->addNamespace('fx', $ns);
        }

        $paths = $view->getTemplatePaths();
        $paths->setTemplateRootPaths([
            $this->getTemplatesPath(),
            $module->getBasePath() . 'Resources/Private/Templates/'.$subFolder
        ]);
        $paths->setLayoutRootPaths([
            $this->getLayoutsPath(),
            $module->getBasePath() . 'Resources/Private/Layouts/'
        ]);
        $paths->setPartialRootPaths([
            $this->getPartialsPath(),
            $module->getBasePath() . 'Resources/Private/Partials/'
        ]);
        $view->assign('FLOCKR_baseUrl', FLOCKR_baseUrl);
        $flockrInfo = [
            'baseUrl' => FLOCKR_baseUrl,
            'version' => FLOCKR_version,
            'software' => FLOCKR_software,
            'uploadPath' => FLOCKR_uploadPath,
            'basePath' => FLOCKR_basePath,
            'namespace' => FLOCKR_NS,
            'debug' => FLOCKR_debug,
        ];
        if (!is_null($module)) $flockrInfo['module'] = $module->moduleInfo;
        $view->assign('flockr', $flockrInfo);
        $view->assign('flockR', $flockrInfo);

        if(is_string($controller)) $renderingContext->setControllerName($controller);

        return $view;
    }

    public function getTemplatesPath()
    {
        return FLOCKR_basePath . 'Modules/Core/Resources/Private/Templates/';
    }


    public function getPartialsPath()
    {
        return FLOCKR_basePath . 'Modules/Core/Resources/Private/Partials/';
    }


    public function getLayoutsPath()
    {
        return FLOCKR_basePath . 'Modules/Core/Resources/Private/Layouts/';
    }

    /**
     * Get the app's layout object
     * @return \Peregrinus\Flockr\Core\Layout Layout object
     */
    public function getLayout()
    {
        return $this->layout;
    }

    /**
     * @param Layout $layout
     */
    public function setLayout($layout)
    {
        $this->layout = $layout;
    }


    /**
     * Get the app's security context
     * @return SecurityContext
     */
    public function getSecurityContext()
    {
        return $this->securityContext;
    }

    /**
     * @param SecurityContext $securityContext
     */
    public function setSecurityContext($securityContext)
    {
        $this->securityContext = $securityContext;
    }


    /**
     * @return DomainManager
     */
    public function getDomainManager()
    {
        return $this->domainManager;
    }

    /**
     * @param DomainManager $domainManager
     */
    public function setDomainManager($domainManager)
    {
        $this->domainManager = $domainManager;
    }

    /**
     * @return null
     */
    public function getEntityManager()
    {
        return $this->entityManager;
    }

    /**
     * @param null $entityManager
     */
    public function setEntityManager($entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @return \Peregrinus\Flockr\Core\Services\PermissionsService
     */
    public function getPermissionsService()
    {
        return $this->permissionsService;
    }

    /**
     * @param \Peregrinus\Flockr\Core\Services\PermissionsService $permissionsService
     */
    public function setPermissionsService($permissionsService)
    {
        $this->permissionsService = $permissionsService;
    }

    /**
     * @return \Peregrinus\Flockr\Core\Services\PreferencesService
     */
    public function getPreferencesService()
    {
        return $this->preferencesService;
    }

    /**
     * @param \Peregrinus\Flockr\Core\Services\PreferencesService $preferencesService
     */
    public function setPreferencesService($preferencesService)
    {
        $this->preferencesService = $preferencesService;
    }

    /**
     * Get the translation service
     * @return \Peregrinus\Flockr\Core\Services\TranslationService TranslationService
     */
    public function getTranslationService()
    {
        return $this->translationService;
    }

    /**
     * Set the translation service
     * @param \Peregrinus\Flockr\Core\Services\TranslationService $translationService TranslationService
     */
    public function setTranslationService(\Peregrinus\Flockr\Core\Services\TranslationService $translationService)
    {
        $this->translationService = $translationService;
    }

    public function canHandleRequest() {
        $handler = false;
        $router = Router::getInstance();
        if ($module = $router->getModule()) {
            if ($controller = $router->getController()) {
                if ($action = $router->getAction($controller)) {
                    if (method_exists($controller, $action.'Action')) {
                        $handler = true;
                    }
                }
            }
        }
        return $handler;
    }

    /**
     * Get the name of the currently active module
     * @return string Name of the active module
     */
    public function getModuleName()
    {
        return $this->moduleName;
    }

    /**
     * Set the name of the active module
     *
     * Normally, this function should not be needed, as the module name is automatically determined
     * from the current route. However, special modules such as the scheduler need a way to manually set
     * the module name in order to work correctly
     * @param string $moduleName Module name
     */
    public function setModuleName($moduleName)
    {
        $this->moduleName = $moduleName;
    }



}