<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace de\peregrinus\progmatic;

require_once('Program.php');
require_once('Vacation.php');

/**
 * ProgMatic 2014 individual room profile
 *
 * @author Christoph
 */
class roomProfile
{
    /*
     * @var \string Title
     */
    protected $title;
    protected $data;
    protected $programs = array();
    protected $vacations = array();

    const FLAG_DEFAULT = 2;
    const FLAG_LOCK = 4;

    /**
     * Constructor
     */
    public function __construct()
    {
        // sensible defaults:
        for ($i = 0; $i < 7; $i++) {
            $this->programs[$i] = new \de\peregrinus\progmatic\Program();
        }
        for ($i = 0; $i < 8; $i++) {
            $this->vacations[$i] = new \de\peregrinus\progmatic\Vacation();
        }
        $this->data['flags'] = self::FLAG_DEFAULT;
        $this->setLowTemperature(15);
        $this->setHighTemperature(21);
        $this->setOffsetTemperature(0);
    }

    /**
     * Set the title
     * @param \string $title Title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * Get the title
     * @return \string Title
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Import raw data
     * @param mixed $data Data
     */
    public function importRawData($data)
    {
        $data = unpack('A112days/A72vacation/c1flags/c1low/c1high/c1fill1/c1offset/c1offset2/A10fill2',
            $data);
        for ($i = 0; $i < 7; $i++) {
            $this->programs[$i]->importRawData(substr($data['days'], ($i * 16),
                    16));
        }
        unset($data['days']);
        for ($i = 0; $i < 8; $i++) {
            $this->vacations[$i]->importRawData(substr($data['vacation'],
                    ($i * 9), 9));
        }
        unset($data['vacation']);
        $this->data = $data;
    }

    /**
     * Export raw data for binary file
     * @return string Data
     */
    public function exportRawData()
    {
        $o = '';
        for ($j = 0; $j < 7; $j++) {
            $o.=$this->programs[$j]->exportRawData();
        }
        for ($j = 0; $j < 8; $j++) {
            $o.=$this->vacations[$j]->exportRawData();
        }

        $o .= pack('ccccccA10', $this->data['flags'], $this->data['low'],
            $this->data['high'], $this->data['fill1'], $this->data['offset'],
            $this->data['offset2'], $this->data['fill2']);

        return $o;
    }

    /**
     * Export raw title for binary file
     * @return string Data
     */
    public function exportRawTitle()
    {
        $title = str_replace(chr(0), '', $this->title);
        if (($title == '') || ($title == 'empty')) {
            $title = '###empty';
        }
        $title = str_pad($title, 32, '#');
        for ($i = 0; $i < strlen($title); $i++) {
            $o .= substr($title, $i, 1).chr(0);
        }
        return $o;
    }

    /**
     * Get a specific program
     * @param int $index Index
     * @return \de\peregrinus\progmatic\Program Program
     */
    public function getProgram($index)
    {
        return $this->programs[$index];
    }

    /**
     * Set a specific program
     * @param int $index
     * @param \de\peregrinus\progmatic\Program $program
     * @return \de\peregrinus\progmatic\roomProfile
     */
    public function setProgram($index, \de\peregrinus\progmatic\Program $program)
    {
        $this->programs[$index] = $program;
        return $this;
    }

    /**
     * Get a specific vacation
     * @param int $index Index
     * @return \de\peregrinus\progmatic\Vacation Vacation
     */
    public function getVacation($index)
    {
        return $this->vacations[$index];
    }

    /**
     * Set a specific vacation
     * @param int $index
     * @param \de\peregrinus\progmatic\Vacation Vacation
     * @return \de\peregrinus\progmatic\roomProfile
     */
    public function setVacation($index,
                                \de\peregrinus\progmatic\Vacation $vacation)
    {
        $this->vacations[$index] = $vacation;
        return $this;
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
     * Get high temperature setting
     * @return float High temperature
     */
    public function getHighTemperature()
    {
        return $this->getDataField('high') / 2;
    }

    /**
     * Set high temperature setting
     * @param float $value High temperature
     * @return \de\peregrinus\progmatic\ProgramItem
     */
    public function setHighTemperature($value)
    {
        $this->setDataField('high', $value * 2);
        return $this;
    }

    /**
     * Get low temperature setting
     * @return float low temperature
     */
    public function getLowTemperature()
    {
        return $this->getDataField('low') / 2;
    }

    /**
     * Set low temperature setting
     * @param float $value Low temperature
     * @return \de\peregrinus\progmatic\ProgramItem
     */
    public function setLowTemperature($value)
    {
        $this->setDataField('low', $value * 2);
        return $this;
    }

    /**
     * Get offset temperature setting
     * @return float Offset temperature
     */
    public function getOffsetTemperature()
    {
        return $this->getDataField('offset') / 2;
    }

    /**
     * Set offset temperature setting
     * @param float $value Offset temperature
     * @return \de\peregrinus\progmatic\ProgramItem
     */
    public function setOffsetTemperature($value)
    {
        $this->setDataField('offset', $value * 2);
        return $this;
    }

    /**
     * Returns true if flag is sert
     * @param byte $flag Flag
     * @return bool True, if flag is set
     */
    protected function isFlagSet($flag)
    {
        return ($this->data & $flag);
    }

    /**
     * Set a specific flag
     * @param byte $flag Flag
     * @return \de\peregrinus\progmatic\roomProfile RoomProfile object
     */
    protected function setFlag($flag)
    {
        $this->data['flags'] = $this->data['flags'] | $flag;
        return $this;
    }

    /**
     * Unset a specific flag
     * @param byte $flag Flag
     * @return \de\peregrinus\progmatic\roomProfile RoomProfile object
     */
    protected function unsetFlag($flag)
    {
        $this->data['flags'] = $this->data['flags'] & (~$flag);
        return $this;
    }

    /**
     * Sets the child protection (key lock)
     * @return \de\peregrinus\progmatic\roomProfile RoomProfile object
     */
    public function setLock()
    {
        $this->setFlag(self::FLAG_LOCK);
        return $this;
    }

    /**
     * Unsets the child protection (key lock)
     * @return \de\peregrinus\progmatic\roomProfile RoomProfile object
     */
    public function unsetLock()
    {
        $this->unsetFlag(self::FLAG_LOCK);
        return $this;
    }

    /**
     * Gets the state of the child protection (key lock)
     * @return bool True, if child protection is enabled
     */
    public function getLock()
    {
        return $this->isFlagSet(self::FLAG_LOCK);
    }

    /**
     * Sets the state of the child protection from a bool variable
     * @param bool $state State
     * @return \de\peregrinus\progmatic\roomProfile RoomProfile object
     */
    public function setLockState($state)
    {
        if ($state) $this->setLock();
        else $this->unsetLock();
        return $this;
    }
}