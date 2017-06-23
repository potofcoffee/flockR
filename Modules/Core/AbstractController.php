<?php

namespace Peregrinus\Flockr\Core;

use Peregrinus\Flockr\Core\Exception\StopActionException;
use Peregrinus\Flockr\Core\Services\SessionService;

class AbstractController extends AbstractClass
{
    const REDIRECT_HEADER = 0x01;
    const REDIRECT_JAVASCRIPT = 0x02;
    public $showView = true;
    protected $module = null;
    protected $defaultAction = 'index';
    protected $view = null;
    protected $app = null;
    protected $request = null;
    protected $panels = [];
    protected $insecureActions = [];
    protected $config = array();
    protected $configurationManager = null;
    protected $redirect;
    protected $data = null;

    public function __construct($module)
    {
        parent::__construct();
        $this->module = $module;
        $this->configurationManager = ConfigurationManager::getInstance();
        $this->config = array_merge_recursive(
            $this->configurationManager->getConfigurationSet(str_replace('Controller', '',
                $this->moduleInfo['class']),
                'Modules/' . $this->moduleInfo['module'] . '/Configuration/'
            ),
            $this->module->getConfig()
        );
        $this->request = Request::getInstance();
        $this->app = App::getInstance();
    }

    /**
     * @return null
     */
    public function getView()
    {
        return $this->view;
    }

    /**
     * @param null $view
     */
    public function setView($view)
    {
        $this->view = $view;
    }


    /**
     * Process action routing
     *
     * @return void
     * @throws \Exception
     */
    public function dispatch($requestedAction, $data = null)
    {
        $actionMethod = $requestedAction . 'Action';

        // run the initialize and action methods
        $this->initializeController();
        // handle data storage so data survives redirects
        if (is_null($data) && (!is_null($this->data))) {
            $data = $this->getData();
        } elseif ((!is_null($data)) && (is_null($this->data))) {
            $this->setData($data);
        }
        return (is_null($data) ? $this->$actionMethod() : $this->$actionMethod($data));
    }

    protected function initializeController()
    {

    }

    /**
     * @return null
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param null $data
     */
    public function setData($data)
    {
        $this->data = $data;
    }

    public function getPanels()
    {
        return $this->panels;
    }

    /**
     * Render the view now
     * @param string $action Action
     * @param bool $show Output the view right away
     */
    public function renderView($action, $show = true)
    {
        $rendered = $this->view->render($action);
        if ($show) {
            echo $rendered;
        }
        // prevent showing twice:
        $this->dontShowView();
        return $rendered;
    }

    /**
     * Switch off view handling
     * @return void
     */
    public function dontShowView()
    {
        $this->showView = false;
    }

    /**
     * Get default action name for this controller
     * @return \string Default action name
     */
    function getDefaultAction()
    {
        return $this->defaultAction;
    }

    /**
     * Set default action name for this controller
     * @param \string $defaultAction Default action name
     * @return void
     */
    function setDefaultAction($defaultAction)
    {
        $this->defaultAction = $defaultAction;
    }

    /**
     * @return mixed
     */
    public function getRedirect()
    {
        return $this->redirect;
    }

    /**
     * @param mixed $redirect
     */
    public function setRedirect($redirect)
    {
        $this->redirect = $redirect;
    }

    /**
     * Check required access level and redirect to another action if not attained
     * @param string $object Permission object
     * @param int $level Minimum level
     * @param string $redirectAction Redirect to action if failed
     */
    public function assertPermission($object, $level, $redirectAction)
    {
        $hasPermission = App::getInstance()->getPermissionsService()->getPermission($this->moduleInfo['module'], $object);
        if ((!$hasPermission) || ($hasPermission < $level)) {
            App::getInstance()->getLayout()->addFlashMessage(new FlashMessage('Du hast nicht die nötigen Benutzerrechte für diese Aktion', FlashMessage::DANGER));
            $this->redirectToAction($redirectAction);
        }
    }

    /**
     * Redirect to another action
     * @param \string $action
     * @param \int $redirectMethod Method of redirecting
     * @param \int $delay Delay in ms (only with javascript redirect)
     */
    protected function redirectToAction(
        $action,
        $redirectMethod = self::REDIRECT_HEADER,
        $delay = 0
    )
    {
        \Peregrinus\Flockr\Core\Router::getInstance()->redirect(
            strtolower($this->moduleInfo['module']),
            strtolower($this->getName()), $action, null, null, $redirectMethod,
            $delay);
    }

    /**
     * Get this controllers's name (class without namespace and 'Provider')
     * @return \string
     */
    public function getName()
    {
        return str_replace('Controller', '', $this->moduleInfo['class']);
    }

    /**
     * Forward to another action
     */
    protected function forward($action)
    {
        $this->setRedirect($action);
        throw new StopActionException();
    }

    /**
     * Get an instance of the configuration manager
     * @return \Peregrinus\Flockr\Core\ConfigurationManager Configuration manager object
     */
    protected function getConfigurationManager()
    {
        if (is_null($this->configurationManager)) {
            $this->configurationManager = \Peregrinus\Flockr\Core\ConfigurationManager::getInstance();
        }
        return $this->configurationManager;
    }


    /**
     * Get an argument from request, or (if not set there) from session
     * @param string $key Key
     * @return mixed Argument value
     */
    protected function getFromRequestOrSession($key)
    {
        $sessionService = SessionService::getInstance();
        if ($this->request->hasArgument($key)) {
            $result = $this->request->getArgument($key);
            $sessionService->setArgument($key, $result);
        } else {
            $result = $sessionService->getArgument($key);
        }
        return $result;
    }

    /**
     * Forward to another action if required arguments are missing
     * @param array $arguments Required arguments
     * @param string $action Action to forward to
     * @return void
     */
    protected function forwardIfMissingArguments(array $arguments, string $action) {
        foreach ($arguments as $argument) {
            if (!$this->request->hasArgument($argument, true))
                $this->forward($action);
        }
    }


}