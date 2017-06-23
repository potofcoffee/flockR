<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace de\peregrinus\progmatic;

require_once('ProgramItem.php');

/**
 * Description of Program
 *
 * @author Christoph
 */
class Program
{
    protected $items = array();

    public function __construct()
    {
        // sensible defaults:
        for ($i = 0; $i < 4; $i++) {
            $this->items[$i] = new \de\peregrinus\progmatic\programItem();
        }
    }

    /**
     * Import from raw data
     * @param type $data
     */
    public function importRawData($data)
    {
        for ($j = 0; $j < 4; $j++) {
            $this->items[$j]->importRawData(substr($data, ($j * 4), 4));
        }
    }

    /**
     * Export raw data for binary file
     * @return string Data
     */
    public function exportRawData()
    {
        $o = '';
        for ($j = 0; $j < 4; $j++) {
            $o.=$this->items[$j]->exportRawData();
        }
        return $o;
    }

    /**
     * Get all program items
     * @return array
     */
    function getItems()
    {
        return $this->items;
    }

    /**
     * Set all items
     * @param array $items
     * @return \de\peregrinus\progmatic\Program
     */
    function setItems($items)
    {
        $this->items = $items;
        return $this;
    }

    /**
     * Get specific item
     * @param int $index Index
     * @return \de\peregrinus\progmatic\ProgramItem Item
     */
    function getItem($index)
    {
        return $this->items[$index];
    }

    /**
     * Set specific item
     * @param int $index Index
     * @param \de\peregrinus\progmatic\ProgramItem $item Item
     * @return \de\peregrinus\progmatic\Program
     */
    function setItem($index, $item)
    {
        $this->items[$index] = $items;
        return $this;
    }
}