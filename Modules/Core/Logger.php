<?php

namespace Peregrinus\Flockr\Core;

/**
 * Description of Logger
 *
 * @author chris
 */
class Logger
{
    static protected $instance = null;
    protected $logger          = null;

    /**
     * Get an instance of the request object
     * @return \Peregrinus\Flockr\Core\Logger Instance of logger object
     */
    static public function getInstance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    static public function getLogger()
    {
        $me = self::getInstance();
        return $me->logger;
    }

    static public function initialize()
    {
        // call getInstance to force construction of new instance
        $me = self::getInstance();
    }

    protected function __construct()
    {
        $this->logger = new \Monolog\Logger('flockr');
        if (FLOCKR_debug) {
            $this->logger->pushHandler(new \Monolog\Handler\StreamHandler(
                FLOCKR_basePath.'Logs/flockr.debug.log', \Monolog\Logger::DEBUG));
        }
        $this->logger->pushHandler(new \Monolog\Handler\StreamHandler(
            FLOCKR_basePath.'Logs/flockr.notice.log', \Monolog\Logger::NOTICE));
    }

    final private function __clone()
    {

    }
}