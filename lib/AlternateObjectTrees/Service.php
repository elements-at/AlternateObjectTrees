<?php

class AlternateObjectTrees_Service
{
    /**
     * @var AlternateObjectTrees_Tree
     */
    private $tree;

    /**
     * @var array
     */
    private $levelDefinitions = array();

    /**
     * @param AlternateObjectTrees_Config $tree
     */
    public function __construct(AlternateObjectTrees_Config $tree)
    {
        $this->tree = $tree;
        $this->levelDefinitions = json_decode($tree->getJsonLevelDefinitions(), true);
    }

    /**
     * @return Object_Class
     */
    private function getClass() {
        return Object_Class::getByName($this->tree->getO_Class());
    }

    /**
     * @param $level
     * @return null | AlternateObjectTrees_LevelDefinition_Input
     */
    public function getLevelDefinitionByLevel($level) {
        $levelDefinition = $this->levelDefinitions[$level-1];
        $class = $this->getClass();
        if($levelDefinition && $class) {
            $levelDefinitionClass = 'AlternateObjectTrees_LevelDefinition_'.ucfirst($levelDefinition['type']);
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
            $db = Pimcore_Resource::get();
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
     * @return Object_List_Concrete
     */
    public function getListWithCondition($filterValues, $currentLevel, $currentAttributeValue) {
        $condition = $this->buildCondition($filterValues, $currentLevel, $currentAttributeValue);

        $listClass = "Object_" . ucfirst($this->getClass()->getName()) . "_List";


        $objectList = new $listClass();
        $objectList->setCondition($condition);

        return $objectList;
    }





}