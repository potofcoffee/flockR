<?php

namespace Peregrinus\Flockr\Core;

class AbstractClass {
    public $moduleInfo = [];

    public function __construct() {
        $class = get_class($this);
        $tmp = explode('\\', $class);
        list($this->moduleInfo['vendor'], $this->moduleInfo['app'], $this->moduleInfo['module'], $this->moduleInfo['class']) = $tmp;
        $this->moduleInfo['class'] = $tmp[count($tmp)-1];
        $this->moduleInfo['moduleClass'] = join('\\', [$this->moduleInfo['vendor'], $this->moduleInfo['app'], $this->moduleInfo['module']]);
        $this->moduleInfo['fullClass'] = $class;
        $this->moduleInfo['ns'] = '\\'.str_replace($this->moduleInfo['class'], '', $class);
        $this->moduleInfo['basePath'] = FLOCKR_basePath.'Modules/'.$this->moduleInfo['module'].'/';
        $this->moduleInfo['relativePath'] = 'Modules/'.$this->moduleInfo['module'].'/';
    }

}