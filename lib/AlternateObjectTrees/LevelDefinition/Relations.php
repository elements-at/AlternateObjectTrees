<?php

class AlternateObjectTrees_LevelDefinition_Relations implements AlternateObjectTrees_ILevelDefinition
{

    /**
     * @var Object_Class
     */
    protected $class;

    /**
     * @var string
     */
    protected $config;
    protected $fieldname;
    protected $label;
    protected $condition;

    public function __construct(Object_Class $class, $config) {
        $this->class = $class;
        $this->config = $config;
        $this->fieldname = $config['fieldname'];
        $this->condition = $config['condition'];

        if(array_key_exists('label', $config) && $config['label'] != '')
            $this->label = $config['label'];
    }

    public function getGroupedValues($condition, $offset = null, $limit = null) {
        $db = Pimcore_Resource::get();

        // create condition
        if($condition) {
            $condition = "WHERE " . $condition;
        }
        if($this->condition)
            $condition .=  ' AND ' . $this->condition;


        // relation objects query
        $query = sprintf('SELECT SQL_CALC_FOUND_ROWS dest_id as "value", "" as "label", count(*) as "count" FROM object_relations_%2$d
                          WHERE fieldname = %1$s AND src_id IN
                            (SELECT o_id FROM object_%2$s %3$s)
                          GROUP BY dest_id',
            $db->quote($this->fieldname), $this->class->getId(), $condition);

        // empty relation query
        if($this->config["showEmpty"]) {
            $conditionPart = "o_id NOT IN (SELECT src_id FROM object_relations_" . $this->class->getId() .
                " WHERE fieldname = " . $db->quote($this->fieldname) . " )";
            if($condition) {
                $condition .= " AND " . $conditionPart;
            } else {
                $condition = "WHERE " . $conditionPart;
            }
            $query = '(' . $query . ") UNION ALL ( SELECT '' as 'value', 'EMPTY' as 'label', count(*) as 'count' FROM object_" . $this->class->getId() . " " . $condition. ' HAVING count > 0 )';
        }

        // add limit
        if($offset !== NULL && $limit !== NULL)
            $query .= sprintf(' LIMIT %d, %d', $offset, $limit);


        // execute query
        $queryResult = $db->fetchAll($query);
        $groupedValues = array();
        foreach($queryResult as $row) {
            $object = Object_Abstract::getById($row['value']);
            $label = $row['label'];
            if($object) {
                $label = $object->getKey();
            }
            $groupedValues[] = array("value" => $row['value'], "label" => $label, "count" => $row['count']);
        }

        // get record count
        $count = $db->fetchOne('select FOUND_ROWS()');

        return array('count' => $count, 'list' => $groupedValues);
    }


    public function getCondition($value) {
        $db = Pimcore_Resource::get();

        $condition = $this->condition != '' ? ' AND ' . $this->condition : '';

        if(!empty($value)) {
            return "o_id IN (SELECT src_id FROM object_relations_" . $this->class->getId() .
                " WHERE fieldname = " . $db->quote($this->fieldname) . " AND dest_id = " . $db->quote($value) . " )" . $condition;
        } else {
            return "o_id NOT IN (SELECT src_id FROM object_relations_" . $this->class->getId() .
                " WHERE fieldname = " . $db->quote($this->fieldname) . " )" . $condition;
        }

    }

    public function getFieldname() {
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


    public static function getCompatibleFields(Object_Class $class)
    {
        $compatible = array('objects','href');
        $list = array();

        foreach($class->getFieldDefinitions() as $field)
        {
            /* @var Object_Class_Data $field */
            #var_dump($field->getFieldtype());
            if(in_array($field->getFieldtype(), $compatible))
                $list[] = $field;

        }

        return $list;
    }
}