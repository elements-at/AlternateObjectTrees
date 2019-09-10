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
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Listing;
use Symfony\Component\HttpFoundation\Request;
use Pimcore\Model\DataObject\AbstractObject;

abstract class AbstractTreeBuilder
{
    /**
     * @var Config
     */
    private $tree;

    private $user;


    /**
     * @param Config $tree
     */
    public function __construct(Config $tree, $user)
    {
        $this->tree = $tree;
        $this->user = $user;
    }

    protected function getUser() {
        return $this->user;
    }


    protected function getTree(): Config
    {
        return $this->tree;
    }

    public function handleNodeRequest(Request $request) {
        $currentNode = AbstractObject::getById($request->get('node'));
        if (!$currentNode) {
            $currentNode = AbstractObject::getByPath($this->tree->getBasepath());
        }

        $limit = intval($request->get('limit', 25));
        $offset = intval($request->get('start'), 0);

        $filter = "";
        if ($request->get('inSearch', '0') == '1') {
            $filter = $request->get('filter', '');
        }


        $response = $this->buildCustomTree($currentNode, $filter, $limit, $offset);
        return array_merge($response,
            [
            "offset"        => $offset,
            "limit"         => $limit,
            "overflow"      => false,
            "fromPaging"    => $request->get('fromPaging', 0),
            "filter"        => $request->get('filter', ''),
            "inSearch"      => $request->get('inSearch', '0') == '1',
        ]
        );
    }

    /**
     * Build a custom tree for a specific (child) node.
     * @param AbstractObject $currentNode
     * @param string $filter
     * @param int $limit
     * @param int $offset
     * @return array which is compatible with Pimcore tree response (@see{DefaultTreeBuilder.php}).
     */
    public abstract function buildCustomTree(AbstractObject $currentNode, string $filter, int $limit, int $offset) : array;

    protected function toTreeNodeList($objectList) : array {
        $objects = [];
        foreach ($objectList as $object) {
            /* @var AbstractObject $object */
            $tmpObject = $this->getTreeNodeConfig($object);

            if ($object->isAllowed('list')) {
                $objects[] = $tmpObject;
            }
        }
        return $objects;
    }

    /**
     * @param Concrete $child
     * @return array
     */
    public function getTreeNodeConfig($child)
    {
        $tmpObject = [
            'objectId' => $child->getId(),
            'text' => $child->getKey(),
            'type' => $child->getType(),
            'path' => $child->getFullPath(),
            'basePath' => $child->getPath(),
            'elementType' => 'object',
            'locked' => $child->isLocked(),
            'lockOwner' => $child->getLocked() ? true : false,
            'data' => [
                'permissions' => []
            ]
        ];

        $tmpObject['leaf'] = !$child->hasChildren();

        $tmpObject['isTarget'] = false;
        $tmpObject['allowDrop'] = false;
        $tmpObject['allowChildren'] = false;
        $tmpObject['cls'] = '';

        if ($child->getType() === 'folder') {
            $tmpObject['qtipCfg'] = [
                'title' => 'ID: ' . $child->getId()
            ];
        } else {
            $tmpObject['published'] = $child->isPublished();
            $tmpObject['className'] = $child->getClass()->getName();
            $tmpObject['qtipCfg'] = [
                'title' => 'ID: ' . $child->getId(),
                'text' => 'Type: ' . $child->getClass()->getName()
            ];

            if (!$child->isPublished()) {
                $tmpObject['cls'] .= 'pimcore_unpublished ';
            }
        }
        if ($child->getElementAdminStyle()->getElementIcon()) {
            $tmpObject['icon'] = $child->getElementAdminStyle()->getElementIcon();
        }
        if ($child->getElementAdminStyle()->getElementIconClass()) {
            $tmpObject['iconCls'] = $child->getElementAdminStyle()->getElementIconClass();
        }
        if ($child->getElementAdminStyle()->getElementCssClass()) {
            $tmpObject['cls'] .= $child->getElementAdminStyle()->getElementCssClass() . ' ';
        }

        $tmpObject['expanded'] = !$child->hasChildren();
        $tmpObject['permissions'] = $child->getUserPermissions();

        if ($child->isLocked()) {
            $tmpObject['cls'] .= 'pimcore_treenode_locked ';
        }
        if ($child->getLocked()) {
            $tmpObject['cls'] .= 'pimcore_treenode_lockOwner ';
        }

        return $tmpObject;
    }
}
