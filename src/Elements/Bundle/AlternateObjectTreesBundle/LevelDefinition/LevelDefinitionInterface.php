<?php

namespace Elements\Bundle\AlternateObjectTreesBundle\LevelDefinition;

use Pimcore\Model\DataObject\ClassDefinition;

interface LevelDefinitionInterface
{
    /**
     * @param ClassDefinition $class
     * @param              $config
     */
    public function __construct(ClassDefinition $class, $config);

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