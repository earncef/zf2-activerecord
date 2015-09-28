<?php

/**
 * Author: earncef
 * Date: 9/28/15
 * Desc: ActiveRecord class
 * License: GPL v2
 */

namespace ActiveRecord;

use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql;
use Zend\Db\TableGateway\Feature;
use Zend\Log\Exception;

/**
 * You can create an instance of this class by passing the
 * primary key, table and db adapter to the constructor.
 */

class ActiveRecord extends AbstractActiveRecord
{
    /**
     * Constructor
     *
     * @param string $primaryKeyColumn
     * @param string|\Zend\Db\Sql\TableIdentifier $table
     * @param Adapter|Sql $adapterOrSql
     * @throws Exception\InvalidArgumentException
     */
    public function __construct($primaryKeyColumn, $table, $adapterOrSql = null)
    {
        // setup primary key
        $this->primaryKeyColumn = empty($primaryKeyColumn) ? null : (array) $primaryKeyColumn;

        // set table
        $this->table = $table;

        // set Sql object
        if ($adapterOrSql instanceof Sql) {
            $this->sql = $adapterOrSql;
        } elseif ($adapterOrSql instanceof Adapter) {
            $this->sql = new Sql($adapterOrSql, $this->table);
        } else {
            throw new Exception\InvalidArgumentException('A valid Sql object was not provided.');
        }

        if ($this->sql->getTable() !== $this->table) {
            throw new Exception\InvalidArgumentException(
                'The Sql object provided does not have a table that matches this row object'
            );
        }

        $this->resultSetPrototype = new ResultSet($this);

        $this->initialize();
    }
}
