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
use Pimcore\Model\DataObject\ClassDefinition;

class Input implements LevelDefinitionInterface
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
        $where = $this->condition == '' ? 1 : $this->condition;
        if ($condition) {
            $where = 'LIKE \'%'.$where.'%\'';
            $where .= ' AND ' . $condition;
        }
        // TODO: this is such a bonkers way to pass down values.
        // When ViCA is built, we have to do this clean and simple.

        // create query
        $sql = 'SELECT SQL_CALC_FOUND_ROWS %1$s as "value", %1$s as "label", count(*) as "count"
                FROM object_%2$s
                WHERE object_%2$s.%1$s %3$s
                GROUP BY %1$s
                ORDER BY %1$s';

        if ($offset && $limit) {
            $sql .= sprintf(' LIMIT %d, %d', $offset, $limit);
        }

        // get data
        $groupedValues = $db->fetchAll(sprintf($sql, $this->fieldname, $this->class->getId(), $where));

        // get record count
        $count = $db->fetchOne('select FOUND_ROWS()');

        return ['count' => $count, 'list' => $groupedValues];
    }

    public function getCondition($value)
    {
        $db = Db::get();
        if (!empty($value)) {
            return $db->quoteIdentifier($this->fieldname) . ' = ' . $db->quote($value);
        } else {
            return '(isnull(' . $db->quoteIdentifier($this->fieldname) . ') OR ' . $db->quoteIdentifier($this->fieldname) . ' = ' . $db->quote($value) . ')';
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
        $compatible = ['input', 'numeric', 'slider', 'textarea', 'slider', 'datetime', 'time', 'select', 'multiselect', 'checkbox']; // wysiwyg
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
        $label = $this->hasLabel() ? $this->getLabel() : sprintf('objects %s = %s', $this->getFieldname(), $attributeValue);

        return sprintf($label, $attributeValue);
    }
}
