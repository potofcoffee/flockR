<?php

namespace Peregrinus\Flockr\Core;


// just a wrapper around PHP's session handling
class Session
{
    const SESSION_KEY = 'Peregrinus\\Flockr';

    static private $instance = NULL;
    static private $started  = false;
    protected $conf          = array();

    protected function __construct()
    {
        $this->initialize();
    }

    final private function __clone()
    {

    }

    /**
     * Get an instance of the session object
     * @return \Peregrinus\Flockr\Core\Session Instance of session object
     */
    static public function getInstance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Start session processing
     * @return void
     */
    static public function initialize()
    {
        if (session_status() != PHP_SESSION_ACTIVE) session_start();
    }

    /**
     * Checks if a specific argument is present in the session
     * @param \string $argument Argument name
     * @return \bool True if argument exists
     */
    public function hasArgument($argument)
    {
        return isset($_SESSION[self::SESSION_KEY][$argument]);
    }

    /**
     * Get a specific argument from the session
     * @param \string $argument Argument name
     * @param variant Argument value or FALSE if argument not present
     */
    public function getArgument($argument)
    {
        return ($this->hasArgument($argument) ? $_SESSION[self::SESSION_KEY][$argument]
                    : false);
    }

    /**
     * Set a session argument
     * @param \string $argument Argument name
     * @param variant $value Argument value
     * @return void
     */
    public function setArgument($argument, $value)
    {
        $_SESSION[self::SESSION_KEY][$argument] = $value;
    }

    /**
     * Get all session arguments
     * @return array Arguments
     */
    public function getArguments()
    {
        return $_SESSION[self::SESSION_KEY];
    }

    /**
     * Clear the session
     * @return void
     */
    public function clear()
    {
        $_SESSION[self::SESSION_KEY] = array();
    }
}