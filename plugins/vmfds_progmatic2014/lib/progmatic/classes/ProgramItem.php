<?php

namespace de\peregrinus\progmatic;

/**
 * ProgMatic 2014 Program Item
 *
 * @author Christoph
 */
class ProgramItem
{
    protected $data = array();

    public function __construct()
    {
        // sensible defaults
        $this->data = array(
            'startMin' => -128,
            'startHour' => 0,
            'endMin' => -128,
            'endHour' => 0,
        );
    }

    /**
     * Import raw data
     * @param mixed $data Data
     */
    public function importRawData($data)
    {
        $this->data = unpack('cstartMin/cstartHour/cendMin/cendHour', $data);
    }

    /**
     * Export raw data for binary file
     * @return string Data
     */
    public function exportRawData()
    {
        return pack('cccc', $this->data['startMin'], $this->data['startHour'],
            $this->data['endMin'], $this->data['endHour']);
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
     * @return \de\peregrinus\progmatic\ProgramItem
     */
    function setDataField($field, $data)
    {
        $this->data[$field] = $data;
        return $this;
    }

    /**
     * Set data manually
     * @param int $startMin Start (Minutes)
     * @param int $startHour Start (Hours)
     * @param int $endMin End (Minutes)
     * @param int $endHour End (Hours)
     */
    public function setDataManually($startMin, $startHour, $endMin, $endHour)
    {
        $startMin   = floor($startMin / 10) * 10;
        $endMin     = floor($endMin / 10) * 10;
        $this->data = array(
            'startMin' => $startMin,
            'startHour' => $startHour,
            'endMin' => $endMin,
            'endHour' => $endHour,
        );
    }
}