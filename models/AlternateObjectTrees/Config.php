<?php
/**
 * Created by JetBrains PhpStorm.
 * User: tballmann
 * Date: 27.03.13
 * Time: 13:45
 * To change this template use File | Settings | File Templates.
 */

class AlternateObjectTrees_Config extends Pimcore_Model_Abstract
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var string
     */
    private $name;

    /**
     * @var int
     */
    private $active;

    /**
     * @var string
     */
    private $icon;

    /**
     * @var string
     */
    private $label;

    /**
     * what object class should be used?
     * @var string
     */
    private $o_class;

    /**
     * @var string
     */
    private $description;

    /**
     * @var string
     */
    private $basepath = '/';

    /**
     * @var string
     */
    private $jsonLevelDefinitions;


    /**
     * @param string $name
     *
     * @return AlternateObjectTrees_Config
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @param string $description
     *
     * @return AlternateObjectTrees_Config
     */
    public function setDescription($description)
    {
        $this->description = $description;
        return $this;
    }

    /**
     * @param string $path
     *
     * @return AlternateObjectTrees_Config
     */
    public function setBasePath($path)
    {
        $this->basepath = $path;
        return $this;
    }

    
    /**
     * @return AlternateObjectTrees_Config
     */
    public function save()
    {
        $this->getResource()->save();
        return $this;
    }


    /**
     * delete object
     */
    public function delete()
    {
        $this->getResource()->delete();
    }

    /**
     * @return string
     */
    public function getBasepath()
    {
        return $this->basepath;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @return string
     */
    public function getJsonLevelDefinitions()
    {
        return $this->jsonLevelDefinitions;
    }

    /**
     * @param string $jsonLevelDefinitions
     */
    public function setJsonLevelDefinitions($jsonLevelDefinitions)
    {
        $this->jsonLevelDefinitions = $jsonLevelDefinitions;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getO_Class()
    {
        return $this->o_class;
    }

    /**
     * @param string $o_class
     */
    public function setO_Class($o_class)
    {
        $this->o_class = $o_class;
    }


    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @param int $active
     */
    public function setActive($active)
    {
        $this->active = (int)$active;
    }

    /**
     * @return int
     */
    public function getActive()
    {
        return (int)$this->active;
    }

    /**
     * @param string $icon
     */
    public function setIcon($icon)
    {
        $this->icon = $icon;
    }

    /**
     * @return string
     */
    public function getIcon()
    {
        return $this->icon;
    }

    /**
     * @param string $label
     */
    public function setLabel($label)
    {
        $this->label = $label;
    }

    /**
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * @param int $id
     * @return AlternateObjectTrees_Config|null
     */
    public static function getById($id)
    {
        // create tree object
        $treeClass = get_called_class();
        $tree = new $treeClass;
        try {
            $tree->getResource()->getById($id);
        } catch(Exception $e)
        {
            return null;
        }

        return $tree;
    }
}