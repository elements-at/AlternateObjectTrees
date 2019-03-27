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

class DefaultTreeBuilder extends AbstractTreeBuilder
{

    /**
     * @override
     * Build a custom tree for a specific (child) node.
     * @param AbstractObject $currentNode
     * @param string $filter
     * @param int $limit
     * @param int $offset
     * @return array which is compatible with Pimcore tree response (@see{DefaultTreeBuilder.php}).
     */
    public function buildCustomTree(AbstractObject $currentNode, string $filter, int $limit, int $offset) : array {
        $list = new \Pimcore\Model\DataObject\Listing();
        $list->setUnpublished(true);
        $list->setCondition('(o_parentId=?)', $currentNode->getId());
        if (!empty($filter)) {
            $list->setCondition($list->getCondition()." AND o_key like concat (?,'%')", [
                $currentNode->getId(),
                $filter]);
        }
        $list->setLimit($limit);
        $list->setOffset($offset);
        $list->setOrderKey('o_key');
        $list->setOrder('asc');

        $children = [];
        foreach ($list->load() ? : [] as $child) {
            $children[] = $child;
        }

        $objects = $this->toTreeNodeList($children);
        return [
            "offset"        => 0,
            "limit"         => 30,
            "total"         => $list->getTotalCount(),
            'nodes'         => $objects
        ];
    }

}
