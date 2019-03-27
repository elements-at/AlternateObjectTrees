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

namespace Elements\Bundle\AlternateObjectTreesBundle\Model\Config;

use Pimcore\Model\Dao\AbstractDao;

class Dao extends AbstractDao
{
    const TABLE_NAME = 'plugin_alternativeobjecttrees';

    /**
     * Contains all valid columns in the database table
     *
     * @var array
     */
    protected $validColumns = [];

    protected $fieldsToSave = ['active', 'name', 'o_class', 'description', 'basepath', 'jsonLevelDefinitions', 'icon', 'label', 'customTreeBuilderClass'];

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
        $classRaw = $this->db->fetchRow('SELECT * FROM ' . self::TABLE_NAME . ' WHERE id=' . $this->db->quote($id));
        if (empty($classRaw)) {
            throw new \Exception('Tree ' . $id . ' not found.');
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
        $data = [];

        foreach ($this->fieldsToSave as $field) {
            if (in_array($field, $this->validColumns)) {
                $getter = 'get' . ucfirst($field);
                $value = $this->model->$getter();

                if (is_array($value) || is_object($value)) {
                    $value = serialize($value);
                } elseif (is_bool($value)) {
                    $value = (int)$value;
                }
                $data[$field] = $value;
            }
        }

        $this->db->update(self::TABLE_NAME, $data, [
            'id' => $this->model->getId()
        ]);
    }

    /**
     * Create a new record for the object in database
     */
    public function create()
    {
        foreach ($this->fieldsToSave as $field) {
            if (in_array($field, $this->validColumns)) {
                $getter = 'get' . ucfirst($field);
                $value = $this->model->$getter();

                if (is_array($value) || is_object($value)) {
                    $value = serialize($value);
                } elseif (is_bool($value)) {
                    $value = (int)$value;
                }
                if ($value !== null) {
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
            'id' => $this->model->getId()
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
