<?php

namespace Peregrinus\Flockr\Core;


class View
{
    private $viewFile       = '';
    private $viewPath       = '';
    private $viewExtension  = 'html';
    private $loader         = null;
    private $renderer       = null;
    private $arguments      = array();
    private $renderMultiple = false;
    private $rendered       = false;
    private $contentType    = 'text/html';

    public function __construct($actionName)
    {
        $this->viewFile = ucfirst($actionName);
        // assign baseUrl
        $this->assign('baseUrl', FLOCKR_baseUrl);
    }

	/**
	 * Get the current view path
	 * @return string
	 */
	public function getViewPath() {
		return $this->viewPath;
	}


    /**
     * Set a new view path
     * @param \string $viewPath Path to views
     */
    function setViewPath($viewPath)
    {
        $this->viewPath = $viewPath;
    }

	/**
	 * @return string
	 */
	public function getViewFile() {
		return $this->viewFile;
	}

	/**
	 * @param string $viewFile
	 */
	public function setViewFile( $viewFile ) {
		$this->viewFile = $viewFile;
	}



    /**
     * Assign a view argument
     * @param \string $argument Argument name
     * @param variant $value Value
     * @return void
     */
    public function assign($argument, $value)
    {
        $this->arguments[$argument] = $value;
    }

    /**
     * Render the view
     * @return \string Rendered view
     */
    public function render()
    {
        $viewFile = $this->viewFile.'.'.$this->viewExtension;
        if (!$this->rendered || $this->renderMultiple) {
            $cacheConfig = array();
            if (!FLOCKR_debug) {
                $cacheConfig = array('cache' => FLOCKR_basePath.'Temp/Cache');
            }
            $this->loader   = new \Twig_Loader_Filesystem($this->viewPath);
            $this->renderer = new \Twig_Environment($this->loader, $cacheConfig);
            $this->rendered = true;
            return $this->renderer->render($viewFile, $this->arguments);
        }
    }

    /**
     * Set the file extension for the view template (normally html)
     * @param \string $viewExtension file extension
     */
    public function setViewExtension($viewExtension)
    {
        $this->viewExtension = $viewExtension;
    }

    /**
     * Get this view's content type
     * @return \string content type
     */
    public function getContentType()
    {
        return $this->contentType;
    }

    /**
     * Set this view's content type
     * @param \string $contentType Content type
     */
    public function setContentType($contentType)
    {
        $this->contentType = $contentType;
    }

    /**
     * Send content type header
     */
    public function sendContentTypeHeader()
    {
        Header('Content-Type: '.$this->contentType);
    }
}