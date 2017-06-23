<?php
/*
 * FLOCKR
 * Multi-Purpose Church Administration Suite
 * http://github.com/potofcoffee/flockr
 * http://flockr.org
 *
 * Copyright (c) 2016+ Christoph Fischer (chris@toph.de)
 *
 * Parts copyright 2003-2015 Renzo Lauper, renzo@churchtool.org
 * FlockR is a fork from the kOOL project (www.churchtool.org). kOOL is available
 * under the terms of the GNU General Public License (see below).
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */


namespace Peregrinus\Flockr\Core;


use Peregrinus\Flockr\Core\Utility\StringUtility;

abstract class AbstractRepository
{

    /**
     * @var DB
     */
    protected $db = null;
    protected $table = '';
    protected $columns = [];
    protected $order = '';
    protected $limit = 0;
    protected $identifier = 'id';

    public function __construct()
    {
        $this->db = DB::getInstance();
        if ($this->table != '') {
            $this->columns = $this->db->getColumns($this->table, null, true);
        }
    }

    /**
     * @return string
     */
    public function getTable()
    {
        return $this->table;
    }

    /**
     * @param string $table
     */
    public function setTable($table)
    {
        $this->table = $table;
    }


    /**
     * Find all records
     * @return array Records
     */
    public function findAll()
    {
        return $this->db->select($this->table, '', '*', $this->order ? 'ORDER BY '.$this->order : '');
    }

    public function find($whereClause, $data = [])
    {
        $result = $this->db->select(
            $this->table,
            $whereClause,
            '*',
            $this->getOrder() ? 'ORDER BY ' . $this->getOrder() : '',
            $this->getLimit() ? 'LIMIT ' . $this->getLimit() : '',
            false, false,
            $data
        );
        return $result;
    }

    /**
     * @return string
     */
    public function getOrder()
    {
        return $this->order;
    }

    /**
     * @param string $order
     */
    public function setOrder($order)
    {
        $this->order = $order;
    }

    /**
     * @return int
     */
    public function getLimit()
    {
        return $this->limit;
    }

    /**
     * @param int $limit
     */
    public function setLimit($limit)
    {
        $this->limit = $limit;
    }

    /**
     * Provide magic methods
     *
     * This implements findByField() and findOneByField() methods
     *
     * @param string $method Method name
     * @param array $parameters Parameters
     * @return array Query results
     */
    public function __call($method, $parameters)
    {
        if (substr($method, 0, 6) == 'findBy') {
            return $this->findBy(StringUtility::camelCaseToUnderscore(substr($method, 6)), $parameters[0], true);
        } elseif (substr($method, 0, 9) == 'findOneBy') {
            return $this->findBy(StringUtility::camelCaseToUnderscore(substr($method, 9)), $parameters[0], false);
        } else {
            return [];
        }
    }

    /**
     * Find by specific field
     * @param string $field Field name
     * @param string $value Value
     * @param bool $all Return all values? (default: true)
     * @return array Query results
     */
    public function findBy($field, $value, $all = true, $whereClause = '')
    {
        if (($field == 'uid') || ($field == 'identifier')) $field = $this->identifier;
        if (!in_array($field, $this->columns)) return [];

        return $this->db->select($this->table,
            "WHERE `{$field}` = :{$field} " . ($whereClause ? $whereClause : ''),
            '*',
            ($this->order ? 'ORDER BY ' . $this->order : ''),
            ($this->limit ? 'LIMIT ' . $this->limit : ''),
            !$all, false,
            [$field => $value]);
    }

    /**
     * Get the IDs of a set of records
     * @param array $records Records
     * @return array IDs
     */
    public function getIds(array $records) : array
    {
        $ids = [];
        foreach ($records as $record) {
            $ids[] = $record['id'];
        }
        return $ids;
    }

    /**
     * Add a record to the repository
     * @param array $record Record
     * @return int Insert id
     */
    public function add($record) {
       return $this->db->insert($this->table, $record);
    }

    /**
     * Delete a record from the repository
     * @param array $record Record
     * @return bool True on success
     */
    public function delete($record) {
        return $this->db->delete($this->table,
            "WHERE `{$this->identifier}` = :{$this->identifier}",
            [$this->identifier => $record[$this->identifier]]
        );
    }


}