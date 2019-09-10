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

namespace Elements\Bundle\AlternateObjectTreesBundle;

use Elements\Bundle\AlternateObjectTreesBundle\LevelDefinition\Input;
use Elements\Bundle\AlternateObjectTreesBundle\LevelDefinition\LevelDefinitionInterface;
use Elements\Bundle\AlternateObjectTreesBundle\Model\Config;
use Pimcore\Db;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\Listing;

class Service
{
    /**
     * @var Config
     */
    private $tree;

    /**
     * @var array
     */
    private $levelDefinitions = [];

    /**
     * @param Config $tree
     */
    public function __construct(Config $tree)
    {
        $this->tree = $tree;
        $this->levelDefinitions = json_decode($tree->getJsonLevelDefinitions(), true);
    }

    /**
     * @return ClassDefinition
     */
    private function getClass()
    {
        return ClassDefinition::getByName($this->tree->getO_Class());
    }

    public function getTreeId() {
        return $this->tree->getId();
    }

    /**
     * @param $level
     *
     * @return null|LevelDefinitionInterface
     */
    public function getLevelDefinitionByLevel($level)
    {
        $levelDefinition = $this->levelDefinitions[$level - 1];
        $class = $this->getClass();
        if ($levelDefinition && $class) {
            $levelDefinitionClass = 'Elements\\Bundle\\AlternateObjectTreesBundle\\LevelDefinition\\'.ucfirst($levelDefinition['type']);

            return new $levelDefinitionClass($class, $levelDefinition['config']);
        }

        return null;
    }

    /**
     * @param $filterValues
     * @param $currentLevel
     * @param $currentAttributeValue
     *
     * @return string
     */
    public function buildCondition($filterValues, $currentLevel, $currentAttributeValue)
    {
        $filterValues = json_decode($filterValues, true);

        $condition = 'o_classId = ' . $this->getClass()->getId();

        if ($this->tree->getBasepath()) {
            $db = Db::get();
            $condition .= ' AND o_path LIKE ' . $db->quote('%' . $this->tree->getBasepath() . '%');
        }

        if ($filterValues) {
            foreach ($filterValues as $level => $value) {
                $levelDefinition = $this->getLevelDefinitionByLevel($level);
                $condition .= ' AND ' . $levelDefinition->getCondition($value);
            }
        }

        if ($currentLevel) {
            $levelDefinition = $this->getLevelDefinitionByLevel($currentLevel);
            $condition .= ' AND ' . $levelDefinition->getCondition($currentAttributeValue);
        }

        return $condition;
    }

    /**
     * @param $filterValues
     * @param $currentLevel
     * @param $currentAttributeValue
     *
     * @return Listing
     */
    public function getListWithCondition($filterValues, $currentLevel, $currentAttributeValue)
    {
        $condition = $this->buildCondition($filterValues, $currentLevel, $currentAttributeValue);

        $listClass = 'Pimcore\\Model\\DataObject\\' . ucfirst($this->getClass()->getName()) . '\\Listing';

        $objectList = new $listClass();
        $objectList->setCondition($condition);

        return $objectList;
    }
}
