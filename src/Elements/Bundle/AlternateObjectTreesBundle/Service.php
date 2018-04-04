<?php

namespace Elements\Bundle\AlternateObjectTreesBundle;

use Elements\Bundle\AlternateObjectTreesBundle\LevelDefinition\Input;
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
    private $levelDefinitions = array();

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
    private function getClass() {
        return ClassDefinition::getByName($this->tree->getO_Class());
    }

    /**
     * @param $level
     * @return null|Input
     */
    public function getLevelDefinitionByLevel($level) {
        $levelDefinition = $this->levelDefinitions[$level-1];
        $class = $this->getClass();
        if($levelDefinition && $class) {
            $levelDefinitionClass = 'Elements\\Bundle\\AlternateObjectTreesBundle\\LevelDefinition\\'.ucfirst($levelDefinition['type']);
            return new $levelDefinitionClass($class, $levelDefinition['config']);
        } else {
            return null;
        }
    }

    /**
     * @param $filterValues
     * @param $currentLevel
     * @param $currentAttributeValue
     * @return string
     */
    public function buildCondition($filterValues, $currentLevel, $currentAttributeValue) {
        $filterValues = json_decode($filterValues, true);

        $condition = "o_classId = " . $this->getClass()->getId();

        if($this->tree->getBasepath()) {
            $db = Db::get();
            $condition .= " AND o_path LIKE " . $db->quote('%' . $this->tree->getBasepath() . '%');
        }

        if($filterValues) {
            foreach($filterValues as $level => $value) {
                $levelDefinition = $this->getLevelDefinitionByLevel($level);
                $condition .= " AND " . $levelDefinition->getCondition($value);
            }
        }

        if($currentLevel) {
            $levelDefinition = $this->getLevelDefinitionByLevel($currentLevel);
            $condition .= " AND " . $levelDefinition->getCondition($currentAttributeValue);
        }

        return $condition;

    }

    /**
     * @param $filterValues
     * @param $currentLevel
     * @param $currentAttributeValue
     * @return Listing
     */
    public function getListWithCondition($filterValues, $currentLevel, $currentAttributeValue) {
        $condition = $this->buildCondition($filterValues, $currentLevel, $currentAttributeValue);

        $listClass = "Pimcore\\Model\\DataObject\\" . ucfirst($this->getClass()->getName()) . "\\Listing";

        $objectList = new $listClass();
        $objectList->setCondition($condition);

        return $objectList;
    }





}