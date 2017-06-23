<?php

namespace Peregrinus\Flockr\Core;


use Peregrinus\Flockr\Core\Utility\StringUtility;

class ConfigurationManager
{
    static private $instance = NULL;
    protected $conf          = array();

    protected function __construct()
    {

    }

    final private function __clone()
    {

    }

    /**
     * Get an instance of the configuration manager
     * @return \Peregrinus\Flockr\Core\ConfigurationManager Instance of configuration manager
     */
    static public function getInstance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Return a specific configuration set
     * @param \string $setTitle Key for the configuration set
     * @param \strung $folder Subfolder for configuration
     * @return array Configuration set
     */
    public function getConfigurationSet($setTitle, $folder = 'Configuration/')
    {
        $folder = $folder ? ucfirst($folder).'/' : '';
        if (!isset($this->conf['_'.$folder][$setTitle])) {
            $yamlFile = FLOCKR_basePath.$folder.ucfirst($setTitle).'.yaml';
            if (file_exists($yamlFile)) {
                $this->conf['_'.$folder][$setTitle] = yaml_parse_file($yamlFile);
            } else {
                $this->conf['_'.$folder][$setTitle] = array();
            }
        }
        return $this->conf['_'.$folder][$setTitle];
    }

    /**
     * Set default values from another array
     *
     * @param array lc Array to process
     * @param array c Array with default values
     * @return array New array with default values set
     */
    function setDefaults($existingConfiguration, $defaultConfiguration)
    {
        $existingConfiguration = $this->arrayMergeRecursiveDistinct($defaultConfiguration['defaults'], $existingConfiguration);
        return $existingConfiguration;
    }

    /**
     * Merge arrays
     *
     * arrayMergeRecursiveDistinct does indeed merge arrays, but it converts values with duplicate
     * keys to arrays rather than overwriting the value in the first array with the duplicate
     * value in the second array, as array_merge does. I.e., with array_merge_recursive,
     * this happens (documented behavior):
     *
     * arrayMergeRecursiveDistinct(array('key' => 'org value'), array('key' => 'new value'));
     * 	 => array('key' => array('org value', 'new value'));
     *
     * arrayMergeRecursiveDistinct does not change the datatypes of the values in the arrays.
     * Matching keys' values in the second array overwrite those in the first array, as is the
     * case with array_merge, i.e.:
     *
     * arrayMergeRecursiveDistinct(array('key' => 'org value'), array('key' => 'new value'));
     * 	 => array('key' => 'new value');
     *
     * Parameters are passed by reference, though only for performance reasons. They're not
     * altered by this function.
     *
     * @param array $array1
     * @param mixed $array2
     * @author daniel@danielsmedegaardbuus.dk
     * @return array
     */
    protected function arrayMergeRecursiveDistinct($array1, $array2 = null)
    {
        $merged       = $array1;
        if (is_array($array2))
                foreach ($array2 as $key => $val)
                if (is_array($array2[$key]))
                        $merged[$key] = is_array($merged[$key]) ? $this->arrayMergeRecursiveDistinct($merged[$key],
                            $array2[$key]) : $array2[$key];
                else $merged[$key] = $val;
        return $merged;
    }

    protected function setGlobal($key, $value) {
        global $$key;
        $$key = $value;
    }

    public function loadIntoGlobalSpace($configuration) {
        foreach ($configuration as $key => $value) {
            $this->setGlobal($key, $value);
            $key = StringUtility::camelCaseToUnderscore($key);
            $this->setGlobal($key, $value);
            $this->setGlobal(strtoupper($key), $value);
        }
    }

    public function loadAsConstants($configuration) {
        foreach ($configuration as $key => $value) {
            if (!defined($key)) define($key, $value);
            $key = StringUtility::camelCaseToUnderscore($key);
            if (!defined($key)) define($key, $value);
            $key = strtoupper($key);
            if (!defined($key))define($key, $value);
        }

    }
}