<?php

namespace de\peregrinus\progmatic;

/**
 * Description of Vacation
 *
 * @author Christoph
 */
class Vacation
{
    protected $data = array();

    /**
     * Constructor
     */
    public function __construct()
    {
        //TODO: sensible defaults
        $this->data = array(
            'startFlag' => -128,
            'startDay' => 1,
            'startMonth' => 1,
            'startYear' => 47,
            'endFlag' => -128,
            'endDay' => 1,
            'endMonth' => 1,
            'endYear' => 47,
            'temp' => 20,
        );
    }

    /**
     * Import raw data
     * @param mixed $data Data
     */
    public function importRawData($data)
    {
        $this->data = unpack('cstartFlag/cstartDay/cstartMonth/cstartYear/cendFlag/cendDay/cendMonth/cendYear/ctemp',
            $data);
    }

    /**
     * Export raw data for binary file
     * @return string Data
     */
    public function exportRawData()
    {
        $o = pack('ccccccccc', $this->data['startFlag'],
            $this->data['startDay'], $this->data['startMonth'],
            $this->data['startYear'], $this->data['endFlag'],
            $this->data['endDay'], $this->data['endMonth'],
            $this->data['endYear'], $this->data['temp']);
        return $o;
    }

    /**
     * Get whole data array
     * @return array
     */
    function getData()
    {
        return $this->data;
    }

    /**
     * Set whole data array
     * @param array $data
     * @return \de\peregrinus\progmatic\ProgramItem
     */
    function setData($data)
    {
        $this->data = $data;
        return $this;
    }

    /**
     * Get specific data field
     * @param string $field Field name
     * @return array
     */
    function getDataField($field)
    {
        return $this->data[$field];
    }

    /**
     * Set specific data field
     * @param string $field Field name
     * @param array $data Data
     * @return \de\peregrinus\progmatic\Vacation
     */
    function setDataField($field, $data)
    {
        $this->data[$field] = $data;
        return $this;
    }

    /**
     * Set all data fields manually
     * @param bool $enabled Is this record enabled?
     * @param int $startDay First day
     * @param int $startMonth First month
     * @param int $startYear First year
     * @param int $endDay Last day
     * @param int $endMonth Last month
     * @param int $endYear Last year
     */
    function setDataManually($enabled, $startDay, $startMonth, $startYear,
                             $endDay, $endMonth, $endYear)
    {
        $this->data = array(
            'startFlag' => ($enabled ? 0 : -128),
            'startDay' => $startDay,
            'startMonth' => $startMonth,
            'startYear' => $startYear,
            'endFlag' => ($enabled ? 0 : -128),
            'endDay' => $endDay,
            'endMonth' => $endMonth,
            'endYear' => $endYear
        );
    }
}