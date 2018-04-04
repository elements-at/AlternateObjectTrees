<?php

namespace Elements\Bundle\AlternateObjectTreesBundle\Model\Config;

use Elements\Bundle\AlternateObjectTreesBundle\Model\Config;
use Pimcore\Db;
use Pimcore\Db\Connection;

class Listing implements \Iterator, \Countable
{
    /**
     * @var Connection
     */
    private $db;

    /**
     * position in result set
     * @var int
     */
    private $cursor = 0;

    /**
     * @var array
     */
    private $rows = array();

    /**
     * @var array
     */
    private $conditions = array();


    /**
     *
     */
    public function __construct()
    {
        $this->db = Db::get();
    }

    /**
     * @param string $condition
     *
     * @return $this
     */
    public function addCondition($condition)
    {
        $this->conditions[] = $condition;
        return $this;
    }

    /**
     * resets all conditions of product list
     * @return $this
     */
    public function resetConditions()
    {
        $this->conditions = array();
        return $this;
    }


    /**
     * @link http://framework.zend.com/issues/browse/ZF-2388 (bug with multi statement)
     * @return $this
     */
    public function load()
    {
        // create sql where
        $condition = '';
        foreach($this->conditions as $cond)
            $condition .= ' AND '.$cond;

        // execute query
        $sql = sprintf('SELECT * FROM %s WHERE 1 %s', Dao::TABLE_NAME, $condition);
        #var_dump($sql);exit;
        $this->rows = $this->db->fetchAll($sql);

        return $this;
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Return the current element
     *
     * @link http://php.net/manual/en/iterator.current.php
     * @link http://framework.zend.com/manual/1.12/de/zend.db.statement.html
     * @return mixed Can return any type.
     */
    public function current()
    {
        return Config::getById( $this->rows[$this->cursor]['id'] );
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Move forward to next element
     *
     * @link http://php.net/manual/en/iterator.next.php
     * @return void Any returned value is ignored.
     */
    public function next()
    {
        $this->cursor++;
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Return the key of the current element
     *
     * @link http://php.net/manual/en/iterator.key.php
     * @return mixed scalar on success, or null on failure.
     */
    public function key()
    {
        return $this->cursor;
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Checks if current position is valid
     *
     * @link http://php.net/manual/en/iterator.valid.php
     * @return boolean The return value will be casted to boolean and then evaluated.
     *       Returns true on success or false on failure.
     */
    public function valid()
    {
        return $this->cursor < count($this->rows);
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Rewind the Iterator to the first element
     *
     * @link http://php.net/manual/en/iterator.rewind.php
     * @return void Any returned value is ignored.
     */
    public function rewind()
    {
        $this->cursor = 0;
    }

    /**
     * (PHP 5 &gt;= 5.1.0)<br/>
     * Count elements of an object
     *
     * @link http://php.net/manual/en/countable.count.php
     * @return int The custom count as an integer.
     * </p>
     * <p>
     *       The return value is cast to an integer.
     */
    public function count()
    {
        return count($this->rows);
    }
}