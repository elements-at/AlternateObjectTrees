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

namespace Elements\Bundle\AlternateObjectTreesBundle\CustomTreeBuilder;

use Elements\Bundle\AlternateObjectTreesBundle\LevelDefinition\Input;
use Elements\Bundle\AlternateObjectTreesBundle\Model\Config;
use Elements\Bundle\AlternateObjectTreesBundle\Model\Node;
use Pimcore\Db;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\Listing;
use Symfony\Component\HttpFoundation\Request;
use Pimcore\Model\DataObject\AbstractObject;

class Factory
{
    public static function create(Config $tree, $user)
    {
        $treeBuilderClass = $tree->getCustomTreeBuilderClass();
        $treeBuilder = new $treeBuilderClass($tree, $user);
        if ($treeBuilderClass = $tree->getCustomTreeBuilderClass()) {
                return $treeBuilder;
            }
    }
}
