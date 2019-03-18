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

namespace Elements\Bundle\AlternateObjectTreesBundle\Model;

use Pimcore\Model\AbstractModel;

class Config extends AbstractModel
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
     * @var string
     */
    private $customTreeBuilderClass;

    /**
     * what object class should be used?
     *
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
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @param string $description
     *
     * @return $this
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @param string $path
     *
     * @return $this
     */
    public function setBasePath($path)
    {
        $this->basepath = $path;

        return $this;
    }

    /**
     * @return $this
     */
    public function save()
    {
        $this->getDao()->save();

        return $this;
    }

    /**
     * delete object
     */
    public function delete()
    {
        $this->getDao()->delete();
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
     * @return string
     */
    public function getCustomTreeBuilderClass()
    {
        return $this->customTreeBuilderClass;
    }

    /**
     * @param string $customTreeBuilderClass
     */
    public function setCustomTreeBuilderClass($customTreeBuilderClass): void
    {
        $this->customTreeBuilderClass = $customTreeBuilderClass;
    }



    /**
     * @param int $id
     *
     * @return Config|null
     */
    public static function getById($id)
    {
        // create tree object
        $treeClass = get_called_class();
        $tree = new $treeClass;

        try {
            $tree->getDao()->getById($id);
        } catch (\Exception $e) {
            return null;
        }

        return $tree;
    }
}
