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


use Peregrinus\Flockr\Core\Services\HookService;

class DB
{

    protected static $instance = null;
    protected $db = null;
    protected $statementCache = [];

    /**
     * DB constructor.
     */
    public function __construct()
    {
        $config = ConfigurationManager::getInstance()->getConfigurationSet('Setup')['database'];
        $this->db = new \PDO($config['dsn'], $config['user'], $config['pass']);
        if ($config['init']) {
            $this->db->query($config['init']);
        }
    }

    /**
     * Get an instance
     * @return DB instance
     */
    public static function getInstance()
    {
        if (null === self::$instance) self::$instance = new self();
        return self::$instance;
    }

    /**
     * Execute a simple query
     * @param string $sql SQL statement
     * @return \PDOStatement Query result
     */
    public function query($sql)
    {
        return $this->db->query($sql);
    }

    /**
     * Count the rows for a query
     * @param string $table Table
     * @param string $field Field (default: id)
     * @param string $whereCondition additional conditions
     * @param array $data Data for prepared statement
     * @return int count
     */
    public function count($table, $field = 'id', $whereCondition = '', $data = [])
    {
        $table = HookService::getInstance()->applyFilters('table_name', $table);
        $field = $field ? $field : 'id';
        $statement = $this->getStatement('SELECT COUNT(`$field`) AS count FROM `$table` ' . (($whereCondition) ? ' WHERE 1=1 ' . $whereCondition : ''));
        if ($statement->execute($data)) {
            return (int)$statement->fetchColumn();
        } else {
            return 0;
        }
    }

    /**
     * Prepare a statement
     * @param string $sql SQL statement
     * @param array $driverOptions Driver options
     * @return \PDOStatement Statement
     */
    public function getStatement($sql, $driverOptions = [])
    {
        $key = md5($sql . serialize($driverOptions));
        if (!isset($this->statementCache[$key])) {
            $this->statementCache[$key] = $this->db->prepare($sql, $driverOptions);
        }
        return $this->statementCache[$key];
    }

    /**
     * Inserts data into a database table
     *
     * This should be used instead of issuing INSERT queries directly
     *
     * @param string Table where the data should be inserted
     * @param array Data array with the keys beeing the name of the db columns
     * @return int id of the newly inserted row
     */
    public function insert($table, $data)
    {
        $table = HookService::getInstance()->applyFilters('table_name', $table);
        $data = $this->prepareDataForTable($table, $data);
        $fields = array_keys($data);
        if (count($fields) > 0) {
            $statement = $this->getStatement('INSERT INTO `' . $table . '` ('
                . join(',', $fields) . ') VALUES (:'
                . join(',:', $fields) . ');');
            if ($statement->execute($data)) {
                return $this->db->lastInsertId();
            } else {
                return false;
            }
        }
    }

    /**
     * Prepare data for a table
     *
     * This will remove all data fields without a corresponding column in the table.
     *
     * @param string $table Table name
     * @param array $data Data array
     * @return array Sanitized data array
     */
    protected function prepareDataForTable($table, array $data)
    {
        $table = HookService::getInstance()->applyFilters('table_name', $table);
        $columns = $this->getColumns($table, null, true);
        foreach ($data as $key => $value) {
            if (!in_array($key, $columns)) unset($data[$key]);
        }
        return $data;
    }

    /**
     * Get columns of a db table
     *
     * @param string $table Name of table
     * @param string $field A search string to only show columns that match this value
     * @param bool $nameOnly Return only an array of column names (default: false)
     * @return array Columns
     */
    public function getColumns($table, $field = '', $nameOnly = false)
    {
        $table = HookService::getInstance()->applyFilters('table_name', $table);
        $data = [];
        $query = 'SHOW COLUMNS FROM ' . $table;
        if ($field) {
            $query .= ' LIKE :field';
            $data['field'] = $field;
        }
        $statement = $this->getStatement($query);
        if ($statement->execute()) {
            $result = $statement->fetchAll(\PDO::FETCH_ASSOC);
            if ($nameOnly) {
                foreach ($result as $key => $column) {
                    $result[$key] = $column['Field'];
                }
            }
            return $result;
        } else {
            return [];
        }
    }

    /**
     * Update data in the database
     *
     * This should be used instead of issuing UPDATE queries directly
     *
     * @param string $table Table where the data should be stored
     * @param string $whereCondition WHERE statement that defines the rows to be updated
     * @param array $data Data array with the keys being the name of the db columns
     */
    public function update($table, $whereCondition, $data)
    {
        $table = HookService::getInstance()->applyFilters('table_name', $table);
        $data = $this->prepareDataForTable($table, $data);
        $fields = [];
        foreach ($data as $key => $value) {
            $fields[] = $key . ' = :' . $key;
        }
        $statement = $this->getStatement('UPDATE `' . $table . '` SET ' . join(', ', $fields) . ' ' . $whereCondition);
        return $statement->execute($data);
    }


    /**
     * Convert an array of column names into a comma-separated, backtick-quoted list
     * @param string|array $columns Column names
     * @return string List
     */
    protected function formatColumnList($columns) {
        $columns = explode(',', $columns);
        if (is_array($columns)) {
            foreach ($columns as $key => $column) {
                if ($columns[$key] != '*') $columns[$key] = '`'.$column.'`';
            }
            return join(',', $columns);
        } else {
            return ($columns == '*') ? $columns : '`'.$columns.'`';
        }
    }


    /**
     * Get data from the database
     *
     * This should be used instead of issuing SELECT queries directly
     *
     * @param string $table Table where the data should be selected from
     * @param string $whereConditionWHERE statement that defines the rows to be selected
     * @param string $columns Comma seperated value with the columns to be selected. * for all of them
     * @param string $order ORDER BY statement
     * @param string $limit LIMIT statement
     * @param array $data Data to fill prepared statemnt
     * @param boolean Returns a single entry if set, otherwise an array of entries is returned with their ids as keys
     */
    public function select ($table, $whereCondition, $columns = "*", $order = "", $limit = "", $single = false, $noIndex = false, $data=[]) {
        global $DEBUG_db;

        $table = HookService::getInstance()->applyFilters('table_name', $table);
        $columns = $this->formatColumnList($columns);

        $statement = $this->getStatement("SELECT $columns FROM $table $whereCondition $order $limit");
        if (defined('DEBUG_THIS_STATEMENT')) Debugger::dumpAndDie([$data, $statement]);
        if ($statement->execute($data)) {
            if ($single) {
                $result = $statement->fetch(\PDO::FETCH_ASSOC);
                if ($noIndex) unset($result['id']);
            } else {
                $result = $statement->fetchAll(\PDO::FETCH_ASSOC);
                if ($noIndex) {
                    foreach ($result as $key => $record) {
                        if (isset($record['id'])) unset($result[$key]['id']);
                    }
                }
            }
        } else {
            $result = [];
        }
        return $result;
    }

    /**
     * Delete data from the database
     *
     * This should be used instead of issuing DELETE queries directly
     *
     * @param string $tableTable where the data should be deleted
     * @param string $whereClause WHERE statement that defines the rows to be deleted
     * @param array $data Data for prepared statement
     * @return bool True on success
     */
    public function delete($table, $whereClause, $data = []) {
        $table = HookService::getInstance()->applyFilters('table_name', $table);
        $query = "DELETE FROM $table $whereClause";
        $statement = $this->getStatement($query);
        if (defined('DEBUG_THIS_STATEMENT')) Debugger::dumpAndDie($statement);
        return ($statement->execute($data) !== false);
    }
}