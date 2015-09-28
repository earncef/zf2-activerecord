<?php
/**
 * Author: earncef
 * Date: 9/28/15
 * Desc: ActiveRecord results class
 * License: GPL v2
 */

namespace ActiveRecord;

use ArrayObject;
use Zend\Db\ResultSet\AbstractResultSet;
use Zend\Log\Exception;

class ResultSet extends AbstractResultSet
{
    /**
     * @var ArrayObject
     */
    protected $resultPrototype = null;

    /**
     * Constructor
     *
     * @param null|ArrayObject $resultPrototype
     */
    public function __construct($resultPrototype)
    {
        if (!is_object($resultPrototype) || !method_exists($resultPrototype, 'exchangeArray')) {
            throw new Exception\InvalidArgumentException(
                'Result prototype must be an object and implement exchangeArray'
            );
        }
        $this->resultPrototype = $resultPrototype;
    }

    /**
     * @return array|\ArrayObject|null
     */
    public function current()
    {
        $data = parent::current();

        if (is_array($data)) {
            $result = $this->resultPrototype;
            $result->populate(array());
            $result->exchangeArray($data);
            return $result;
        }

        return $data;
    }
}
