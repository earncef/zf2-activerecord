<?php
/**
 * Author: earncef
 * Date: 9/28/15
 * Desc: ActiveRecord abstract class
 * License: GPL v2
 */

namespace ActiveRecord;

use Zend\Db\RowGateway\AbstractRowGateway;
use Zend\Db\Sql;
use Zend\Log\Exception;
use Zend\Db\RowGateway\Exception\RuntimeException;

/**
 * ActiveRecord class. Your activerecord classes should extend
 * this class along and pass along the primary key, table and
 * db adapter to the parent.
 */
abstract class AbstractActiveRecord extends AbstractRowGateway
{

    /**
     * @var Zend\Db\ResultSet\ResultSetInterface
     */
    protected $resultSetPrototype = null;

    /**
     * @var Zend\Db\Sql\Select
     */
    protected $select = null;

    /**
     * Loads the instance by primary keys. The arguments specify
     * one or more primary key values.
     *
     * The method accepts a variable number of arguments.
     * The number of primary key values passed must be exactly
     * equal to the number of primary key columns in the table.
     *
     * The method will return a false if no data exists for given
     * primary key values
     *
     * @return bool|ActiveRecord\AbstractActiveRecord
     * @throws Zend\Log\Exception\RuntimeException
     */
    public function load()
    {
        $this->initialize();
        $this->clean();

        $args = func_get_args();

        if (count($args) < count($this->primaryKeyColumn)) {
            throw new Exception\RuntimeException('Too few columns for the primary key');
        }

        if (count($args) > count($this->primaryKeyColumn)) {
            throw new Exception\RuntimeException('Too many columns for the primary key');
        }

        // primary key is always an array even if its a single column
        foreach ($this->primaryKeyColumn as $position => $pkColumn) {
            $where[$pkColumn] = $args[$position];
        }

        $this->reset();
        $this->where($where);
        $result = $this->fetch();
        return $result->current();
    }

    /**
     * Get the instance of select.
     * This instance will be used for the following methods
     * unless it is reset:
     * columns, join, where, group, having, limit, offset
     *
     * @return Zend\Db\Sql\Select
     */
    public function select()
    {
        if (!$this->select instanceof Sql\Select) {
            $this->select = $this->sql->select();
        }
        return $this->select;
    }

    /**
     * Reset the instance of select that is used in the select method if part is not set
     * Reset the defined part in the select if part is set
     *
     * @param string $part
     */
    public function reset($part = null)
    {
        if ($part) {
            $this->select->reset($part);
        } else {
            $this->select = null;
        }
        return $this;
    }

    /**
     * Clear all data
     */
    public function clean()
    {
        $this->populate(array());
    }

    /**
     * Fetch the data for the instance of select.
     * Optionally a different instance of select can be passed
     * as a parameter in which case it will be used to do the fetch.
     * A new instance of select can be generated calling the
     * select method of the sql property.
     *
     * @param Zend\Db\Sql\Select $select
     * @return ActiveRecord\ResultSet
     */
    public function fetch($select = null)
    {
        if ($select instanceof Sql\Select) {
            $statement = $this->sql->prepareStatementForSqlObject($select);
        } else {
            $statement = $this->sql->prepareStatementForSqlObject($this->select());
        }

        $result = $statement->execute();

        $resultSet = clone $this->resultSetPrototype;
        $resultSet->initialize($result);

        return $resultSet;
    }

    /**
     * Update the columns of the table that satisfy the given condition(s).
     *
     * @param array $set
     * @param Where|\Closure|string|array|Predicate\PredicateInterface $predicate
     * @return mixed
     */
    public function update(array $set, $predicate)
    {
        $update = $this->sql->update()->set($set)->where($predicate);
        $statement = $this->sql->prepareStatementForSqlObject($update);

        $result = $statement->execute();

        return $this;
    }

    /**
     * Delete rows of table that satisfy the given condition(s).
     *
     * @param array $set
     * @param Where|\Closure|string|array|Predicate\PredicateInterface $predicate
     * @return mixed
     */
    public function deleteWhere($predicate)
    {
        $delete = $this->sql->delete()->where($predicate);
        $statement = $this->sql->prepareStatementForSqlObject($delete);

        $result = $statement->execute();

        return $this;
    }

    /**
     * Fetch a single row for the instance of select.
     *
     * @return ActiveRecord\ResultSet
     */
    public function fetchOne()
    {
        $this->limit(1);
        return $this->fetch()->current();
    }

    /**
     * Fetch key => value pairs from the key and value columns
     *
     * @param string $keyColumn
     * @param string $valueColumn
     * @return ActiveRecord\ResultSet
     */
    public function fetchPairs($keyColumn, $valueColumn)
    {
        $pairs = array();

        $this->columns(array($keyColumn, $valueColumn));
        $result = $this->fetch();

        foreach ($result as $data) {
            $pairs[$data->$keyColumn] = $data->$valueColumn;
        }

        return $pairs;
    }

    /**
     * Set the columns from which to select
     * More details at zend framework documentation for Zend\Db\Sql\Select
     *
     * @param array $columns
     * @param bool $prefixColumnsWithTable
     * @return mixed
     */
    public function columns(array $columns, $prefixColumnsWithTable = true)
    {
        $this->select()->columns($columns, $prefixColumnsWithTable);
        return $this;
    }

    /**
     * Set the join clause
     * More details at zend framework documentation for Zend\Db\Sql\Select
     *
     * @param string $name
     * @param string $on
     * @param array $columns
     * @param string $type
     * @return mixed
     */
    public function join($name, $on, $columns = Sql\Select::SQL_STAR, $type = Sql\Select::JOIN_INNER)
    {
        $this->select()->join($name, $on, $columns);
        return $this;
    }

    /**
     * Set the where clause
     * More details at zend framework documentation for Zend\Db\Sql\Select
     *
     * @param Where|\Closure|string|array|Predicate\PredicateInterface $predicate
     * @param string $combination
     * @return mixed
     */
    public function where($predicate, $combination = Sql\Predicate\PredicateSet::OP_AND)
    {
        $this->select()->where($predicate, $combination);
        return $this;
    }

    /**
     * Set the group on select
     * More details at zend framework documentation for Zend\Db\Sql\Select
     *
     * @param string|array $group
     * @return mixed
     */
    public function group($group)
    {
        $this->select()->group($group);
        return $this;
    }

    /**
     * Set having on select
     * More details at zend framework documentation for Zend\Db\Sql\Select
     *
     * @param Where|\Closure|string|array|Predicate\PredicateInterface $predicate
     * @param string $combination
     * @return mixed
     */
    public function having($predicate, $combination = Predicate\PredicateSet::OP_AND)
    {
        $this->select()->having($predicate, $combination);
        return $this;
    }

    /**
     * Set the order on select
     * More details at zend framework documentation for Zend\Db\Sql\Select
     *
     * @param string $order
     * @return mixed
     */
    public function order($order)
    {
        $this->select()->order($order);
        return $this;
    }

    /**
     * Set the limit on select
     * More details at zend framework documentation for Zend\Db\Sql\Select
     *
     * @param int $limit
     * @return mixed
     */
    public function limit($limit)
    {
        $this->select()->limit($limit);
        return $this;
    }

    /**
     * Set the offset on select
     * More details at zend framework documentation for Zend\Db\Sql\Select
     *
     * @param int $offset
     * @return mixed
     */
    public function offset($offset)
    {
        $this->select()->offset($offset);
        return $this;
    }
}
