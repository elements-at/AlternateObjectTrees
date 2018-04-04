<?php

namespace Elements\Bundle\AlternateObjectTreesBundle\Model\Config;

use Pimcore\Model\Dao\AbstractDao;

class Dao extends AbstractDao
{
    const TABLE_NAME = "plugin_alternativeobjecttrees";

    /**
     * Contains all valid columns in the database table
     *
     * @var array
     */
    protected $validColumns = array();

    protected $fieldsToSave = array("active", "name", "o_class", "description", "basepath", "jsonLevelDefinitions", "icon", "label");


    /**
     * Get the valid columns from the database
     */
    public function init()
    {
        $this->validColumns = $this->getValidTableColumns(self::TABLE_NAME);
    }

    /**
     * @param $id
     *
     * @throws \Exception
     */
    public function getById($id)
    {
        $classRaw = $this->db->fetchRow("SELECT * FROM " . self::TABLE_NAME . " WHERE id=" . $this->db->quote($id));
        if (empty($classRaw)) {
            throw new \Exception("Tree " . $id . " not found.");
        }
        $this->assignVariablesToModel($classRaw);
    }


    /**
     * Save object to database
     */
    public function save()
    {
        if ($this->model->getId()) {
            return $this->update();
        }
        return $this->create();
    }

    /**
     * @return void
     */
    public function update()
    {
        $data = array();

        foreach ($this->fieldsToSave as $field) {
            if (in_array($field, $this->validColumns)) {
                $getter = "get" . ucfirst($field);
                $value = $this->model->$getter();

                if (is_array($value) || is_object($value)) {
                    $value = serialize($value);
                } else if (is_bool($value)) {
                    $value = (int)$value;
                }
                $data[$field] = $value;
            }
        }

        $this->db->update(self::TABLE_NAME, $data, [
            "id" => $this->model->getId()
        ]);
    }


    /**
     * Create a new record for the object in database
     */
    public function create()
    {
        foreach ($this->fieldsToSave as $field) {
            if (in_array($field, $this->validColumns)) {
                $getter = "get" . ucfirst($field);
                $value = $this->model->$getter();

                if (is_array($value) || is_object($value)) {
                    $value = serialize($value);
                } else if (is_bool($value)) {
                    $value = (int)$value;
                }
                if ($value !== NULL) {
                    $data[$field] = $value;
                }
            }
        }

        $this->db->insert(self::TABLE_NAME, $data);
        $this->model->setId($this->db->lastInsertId());

        $this->save();
    }


    /**
     * Deletes object from database
     *
     * @throws \Exception
     */
    public function delete()
    {
        $this->db->delete(self::TABLE_NAME, [
            "id" => $this->model->getId()
        ]);
    }

    /**
     * @param array $fields
     */
    public function setFieldsToSave(array $fields)
    {
        $this->fieldsToSave = $fields;
    }

}
