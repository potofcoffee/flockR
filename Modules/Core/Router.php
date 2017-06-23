<?php

namespace Peregrinus\Flockr\Core;

/**
 * Description of Router
 *
 * @author chris
 */
class Router
{
    const REDIRECT_HEADER = 0x01;
    const REDIRECT_JAVASCRIPT = 0x02;

    static $instance = null;
    protected $defaultController = '';
    protected $module;

    /**
     * Get an instance of the request object
     * @return Router Instance of session object
     */
    static public function getInstance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    protected function __construct()
    {

    }

    final private function __clone()
    {

    }

    /**
     * Get the default controller
     * @return \string Default controller name
     */
    public function getDefaultController()
    {
        if ($this->defaultController != '') {
            return $this->defaultController;
        } else {
            \Peregrinus\Flockr\Core\Logger::getLogger()->addEmergency(
                'No default controller specified. Use Router->setDefaultController();');
            throw new \Exception('No default controller specified.');
        }
    }

    /**
     * Set the default controller
     * @param \string $defaultController Default controller name
     */
    public function setDefaultController($defaultController)
    {
        $this->defaultController = $defaultController;
    }


    public function getModuleName() {
        $request = \Peregrinus\Flockr\Core\Request::getInstance();
        $request->parseUri();
        $request->applyUriPattern(array('module', 'controller', 'action'));
        \Peregrinus\Flockr\Core\Debugger::toFile($request, 'request');
        // MODULE
        if ($request->hasArgument('module')) {
            return $request->getArgument('module');
        } else {
            return false;
        }
    }


    public function getModule() {
        // MODULE
        if ($moduleClass = $this->getModuleName()) {
            $moduleClass = '\\Peregrinus\\Flockr\\' . ucfirst($moduleClass) . '\\' . ucfirst($moduleClass) . 'Module';
            if (class_exists($moduleClass)) {
                $this->module = new $moduleClass();
                return $this->module;
            }
        }
        return false;
    }

    public function getController()
    {
        $request = \Peregrinus\Flockr\Core\Request::getInstance();
        // CONTROLLER
        if ($request->hasArgument('controller')) {
            $controllerName = $request->getArgument('controller');
        } else {
            $controllerName = $this->module->getDefaultController();
        }
        if (!$controllerName) {
            return false;
        }
        $controllerClass = $this->module->getControllerClass($controllerName);
        if (!class_exists($controllerClass)) {
            return false;
        }
        $controller = new $controllerClass($this->module);
        return $controller;
    }

    public function getAction($controller) {
        $request = \Peregrinus\Flockr\Core\Request::getInstance();
        // ACTION
        if ($request->hasArgument('action')) {
            $action = $request->getArgument('action');
        } else {
            $action = $this->module->getDefaultAction($controller->getName());
        }
        return $action;
    }


    /**
     * Redirect to Url
     * @param \string $targetUrl Url
     * @param \int $redirectMethod Method of redirecting
     * @param \int $delay Delay in ms (only with javascript redirect)
     */
    public function redirectToUrl(
        $targetUrl,
        $redirectMethod = self::REDIRECT_HEADER,
        $delay = 0
    ) {
        switch ($redirectMethod) {
            case self::REDIRECT_HEADER:
                Header('Location: ' . $targetUrl);
                break;
            case self::REDIRECT_JAVASCRIPT:
                echo '<script type="text/javascript"> setTimeout(function(){ window.location.href=\'' . $targetUrl . '\' }, ' . $delay . ');</script>';
                break;
        }
        die();
    }

    /**
     * Redirect to a controller/action pair
     * @param \string $controller Controller
     * @param \string $action Action
     * @param array $arguments Arguments
     * @param array $pattern Uri pattern
     * @param \int $redirectMethod Method of redirecting
     * @param \int $delay Delay in ms (only with javascript redirect)
     */
    public function redirect(
        $module,
        $controller,
        $action,
        $arguments = array(),
        $pattern = array(),
        $redirectMethod = self::REDIRECT_HEADER,
        $delay = 0
    ) {
        if (!count($pattern)) {
            $pattern = ['module', 'controller', 'action'];
        }
        $arguments['module'] = $module;
        $arguments['controller'] = $controller;
        $arguments['action'] = $action;
        $uri = \Peregrinus\Flockr\Core\Router::getInstance()->getUri($arguments,
            $pattern);


        $this->redirectToUrl($uri, $redirectMethod, $delay);
    }

    protected function getUri($arguments, $pattern)
    {
        $uriItems = array();
        foreach ($pattern as $key) {
            $uriItems[] = $arguments[$key];
            unset($arguments[$key]);
        }
        $uri = join('/', $uriItems);
        if (is_array($arguments)) {
            $uriItems = array();
            foreach ($arguments as $key => $value) {
                $uriItems[] = $key . '=' . $value;
            }
            if (count($uriItems)) {
                $uri .= '?' . join('&', $uriItems);
            }
            $uri = FLOCKR_baseUrl . $uri;
            return $uri;
        }
    }
}