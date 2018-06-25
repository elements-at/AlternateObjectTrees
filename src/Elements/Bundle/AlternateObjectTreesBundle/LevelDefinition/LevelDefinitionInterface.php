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

use Pimcore\Model\DataObject\ClassDefinition;

interface LevelDefinitionInterface
{
    /**
     * @param ClassDefinition $class
     * @param              $config
     */
    public function __construct(ClassDefinition $class, $config);

    /**
     * return value, label and count(*) values
     *
     * @param string $condition
     * @param int $offset
     * @param int $limit
     *
     * @return array
     */
    public function getGroupedValues($condition, $offset = null, $limit = null);

    /**
     * @param $value
     *
     * @return string
     */
    public function getCondition($value);

    /**
     * @return string
     */
    public function getFieldname();

    /**
     * return label text for this level
     *
     * @return string
     */
    public function getLabel();

    /**
     * has this level a label caption?
     *
     * @return boolean
     */
    public function hasLabel();
}
