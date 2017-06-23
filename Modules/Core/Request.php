<?php

namespace Peregrinus\Flockr\Core;

class Request
{
    static $instance = NULL;
    protected $data  = array();

    /**
     * Get an instance of the request object
     * @return \Peregrinus\Flockr\Core\Request Instance of session object
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
        $this->data = $_REQUEST;
    }

    final private function __clone()
    {

    }

    /**
     * Return a request variable
     * @param string $varName Variable name
     * @return variant Value
     */
    static public function GPVar($varName)
    {
        return $_REQUEST[$varName];
    }

    /**
     * Checks if a specific argument is present in the request
     * @param \string $argument Argument name
     * @param bool $mayBeEmpty May be empty? default: no
     * @return \bool True if argument exists
     */
    public function hasArgument($argument, $mayBeEmpty = false)
    {
        return (isset($this->data[$argument]) && (($this->data[$argument] != '') || $mayBeEmpty));
    }

    /**
     * Get a specific argument from the request
     * @param \string $argument Argument name
     * @param mixed $argument Argument value or FALSE if argument not present
     * @param mxied $default Optional default value (default: false)
     */
    public function getArgument($argument, $default = false)
    {
        return ($this->hasArgument($argument) ? $this->data[$argument] : $default);
    }

    /**
     * Get all request arguments
     * @return array Arguments
     */
    public function getArguments()
    {
        return $this->data;
    }

    /**
     * Checks if $_FILES array is present
     * @return bool True if files array is present
     */
    public function hasFilesArray()
    {
        return is_array($_FILES);
    }

    /**
     * Returns files array with information about uploaded files
     * @return array File upload information
     */
    public function getFilesArray()
    {
        return $_FILES;
    }

    /**
     * Parse request data from nice URL
     */
    public function parseUri()
    {
        $uri                = $_SERVER['REQUEST_URI'];
        $this->data['_ext'] = pathinfo($uri, PATHINFO_EXTENSION);
        $uri                = str_replace('.'.$this->data['_ext'], '', $uri);
        $uri                = parse_url($uri, PHP_URL_PATH);
        if (substr($uri, 0, 1)=='/') $uri = substr($uri, 1);
        \Peregrinus\Flockr\Core\Logger::getLogger()->addDebug('Parsing URI '.$uri);
        if ($uri != '') {
            $this->data['_raw'] = explode('/', $uri);
        } else {
            $this->data['_raw'] = array();
        }
        \Peregrinus\Flockr\Core\Logger::getLogger()->addDebug('URI parsed',
            $this->data);
    }

    /**
     * Get named parameters from request according to a uri pattern
     * @param array $pattern Array with names of uri sections
     */
    public function applyUriPattern($pattern)
    {
        $uriItems = $this->data['_raw'];
        foreach ($pattern as $key) {
            if (isset($uriItems[0])) {
                $this->data[$key] = $uriItems[0];
                unset($uriItems[0]);
            } else {
                $this->data[$key] = '';
            }
            $uriItems = array_values($uriItems);
        }
        $this->data['_raw'] = $uriItems;
    }

    /**
     * Get multiple arguments at once
     * @param array $args Argument keys
     * @return array Argument values
     */
    public function getArgumentsArray($args)
    {
        $data = array();
        foreach ($args as $arg) {
            if ($this->hasArgument($arg)) {
                $data[$arg] = $this->getArgument($arg);
            }
        }
        return $data;
    }

    public function requireArguments($args)
    {
        foreach ($args as $arg) {
            if (!$this->hasArgument($arg)) {
                \Peregrinus\Flockr\Core\Logger::getLogger()->addDebug('FATAL: Missing argument \''.$arg.'\'');
                die('FATAL: Missing argument \''.$arg.'\'');
            }
        }
    }
}