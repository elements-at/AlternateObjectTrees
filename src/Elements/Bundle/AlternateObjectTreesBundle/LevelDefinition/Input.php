<?php

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

        if (array_key_exists('label', $config) && $config['label'] != '')
            $this->label = $config['label'];
    }

    public function getGroupedValues($condition, $offset = null, $limit = null)
    {
        $db = Db::get();

        // create condition
        $where = $this->condition == '' ? 1 : $this->condition;
        if ($condition) {
            $where .= " AND " . $condition;
        }

        // create query
        $sql = 'SELECT SQL_CALC_FOUND_ROWS %1$s as "value", %1$s as "label", count(*) as "count"
                FROM object_%2$d
                WHERE %3$s
                GROUP BY %1$s';
        if ($offset && $limit)
            $sql .= sprintf(' LIMIT %d, %d', $offset, $limit);

        // get data
        $groupedValues = $db->fetchAll(sprintf($sql, $this->fieldname, $this->class->getId(), $where));

        // get record count
        $count = $db->fetchOne('select FOUND_ROWS()');

        return array('count' => $count, 'list' => $groupedValues);
    }


    public function getCondition($value)
    {
        $db = Db::get();
        if (!empty($value)) {
            return $db->quoteIdentifier($this->fieldname) . " = " . $db->quote($value);
        } else {
            return "(isnull(" . $db->quoteIdentifier($this->fieldname) . ") OR " . $db->quoteIdentifier($this->fieldname) . " = " . $db->quote($value) . ")";
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
        $compatible = array('input', 'numeric', 'slider', 'textarea', 'slider', 'datetime', 'time', 'select', 'multiselect', 'checkbox'); # wysiwyg
        $list = array();

        foreach ($class->getFieldDefinitions() as $field) {
            /* @var ClassDefinition\Data $field */
            if (in_array($field->getFieldtype(), $compatible))
                $list[] = $field;

        }

        return $list;
    }

}