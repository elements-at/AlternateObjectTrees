<?php
/**
 * Created by JetBrains PhpStorm.
 * User: tballmann
 * Date: 27.03.13
 * Time: 13:56
 * To change this template use File | Settings | File Templates.
 */

interface AlternateObjectTrees_ILevelDefinition
{
    /**
     * @param Object_Class $class
     * @param              $config
     */
    public function __construct(Object_Class $class, $config);

    /**
     * return value, label and count(*) values
     * @param string $condition
     * @param int $offset
     * @param int $limit
     *
     * @return array
     */
    public function getGroupedValues($condition, $offset = null, $limit = null);

    /**
     * @param $value
     *
     * @return string
     */
    public function getCondition($value);

    /**
     * @return string
     */
    public function getFieldname();

    /**
     * return label text for this level
     * @return string
     */
    public function getLabel();

    /**
     * has this level a label caption?
     * @return boolean
     */
    public function hasLabel();
}