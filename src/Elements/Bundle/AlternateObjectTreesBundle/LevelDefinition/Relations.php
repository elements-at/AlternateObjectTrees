<?php

/**
 * Elements.at
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) elements.at New Media Solutions GmbH (https://www.elements.at)
 *  @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Elements\Bundle\AlternateObjectTreesBundle\LevelDefinition;

use Pimcore\Db;
use Pimcore\Model\DataObject\AbstractObject;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\Concrete;

class Relations implements LevelDefinitionInterface
{
    /**
     * @var ClassDefinition
     */
    protected $class;

    /**
     * @var string
     */
    protected $config;
    protected $fieldname;
    protected $label;
    protected $condition;

    public function __construct(ClassDefinition $class, $config)
    {
        $this->class = $class;
        $this->config = $config;
        $this->fieldname = $config['fieldname'];
        $this->condition = $config['condition'];

        if (array_key_exists('label', $config) && $config['label'] != '') {
            $this->label = $config['label'];
        }
    }

    public function getGroupedValues($condition, $offset = null, $limit = null)
    {
        $db = Db::get();

        // create condition
        if ($condition) {
            $condition = 'WHERE ' . $condition;
        }
        if ($this->condition) {
            $condition .= ' AND ' . $this->condition;
        }

        // relation objects query
        $query = sprintf('SELECT SQL_CALC_FOUND_ROWS dest_id as "value", "" as "label", count(*) as "count" FROM object_relations_%2$s
                          WHERE fieldname = %1$s AND src_id IN
                            (SELECT o_id FROM object_%2$s %3$s)
                          GROUP BY dest_id',
            $db->quote($this->fieldname), $this->class->getId(), $condition);

        // empty relation query
        if ($this->config['showEmpty']) {
            $conditionPart = 'o_id NOT IN (SELECT src_id FROM object_relations_' . $this->class->getId() .
                ' WHERE fieldname = ' . $db->quote($this->fieldname) . ' )';
            if ($condition) {
                $condition .= ' AND ' . $conditionPart;
            } else {
                $condition = 'WHERE ' . $conditionPart;
            }
            $query = '(' . $query . ") UNION ALL ( SELECT '' as 'value', 'EMPTY' as 'label', count(*) as 'count' FROM object_" . $this->class->getId() . ' ' . $condition. ' HAVING count > 0 )';
        }

        // add limit
        if ($offset && $limit) {
            $query .= sprintf(' LIMIT %d, %d', $offset, $limit);
        }

        // execute query
        $queryResult = $db->fetchAll($query);
        $groupedValues = [];
        foreach ($queryResult as $row) {
            $object = AbstractObject::getById($row['value']);
            $label = $row['label'];
            if ($object) {
                $label = $object->getKey();
            }
            $groupedValues[] = ['value' => $row['value'], 'label' => $label, 'count' => $row['count']];
        }

        usort($groupedValues, static function ($group1, $group2) {
            return $group1['label'] <=> $group2['label'];
        });

        // get record count
        $count = $db->fetchOne('select FOUND_ROWS()');

        return ['count' => $count, 'list' => $groupedValues];
    }

    public function getCondition($value)
    {
        $db = Db::get();

        $condition = $this->condition != '' ? ' AND ' . $this->condition : '';

        if (!empty($value)) {
            return 'o_id IN (SELECT src_id FROM object_relations_' . $this->class->getId() .
                ' WHERE fieldname = ' . $db->quote($this->fieldname) . ' AND dest_id = ' . $db->quote($value) . ' )' . $condition;
        } else {
            return 'o_id NOT IN (SELECT src_id FROM object_relations_' . $this->class->getId() .
                ' WHERE fieldname = ' . $db->quote($this->fieldname) . ' )' . $condition;
        }
    }

    public function getFieldname()
    {
        return $this->fieldname;
    }

    public function getLabel()
    {
        return $this->label;
    }

    public function hasLabel()
    {
        return $this->label !== null;
    }

    public static function getCompatibleFields(ClassDefinition $class)
    {
        $compatible = ['objects', 'href', 'manyToManyObjectRelation', 'manyToOneRelation'];
        $list = [];

        foreach ($class->getFieldDefinitions() as $field) {
            /* @var ClassDefinition\Data $field */
            if (in_array($field->getFieldtype(), $compatible)) {
                $list[] = $field;
            }
        }

        return $list;
    }

    public function getGroupName($attributeValue)
    {
        $object = Concrete::getById($attributeValue);
        if(!$object instanceof Concrete) {
            return '';
        }

        $value = $object->getKey();

        $label = $this->hasLabel() ? $this->getLabel() : sprintf('objects %s = %s', $this->getFieldname(), $value);

        return sprintf($label, $value);
    }
}
