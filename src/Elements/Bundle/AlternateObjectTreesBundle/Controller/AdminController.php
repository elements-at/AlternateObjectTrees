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

namespace Elements\Bundle\AlternateObjectTreesBundle\Controller;

use Elements\Bundle\AlternateObjectTreesBundle\CustomTreeBuilder\DefaultTreeBuilder;
use Elements\Bundle\AlternateObjectTreesBundle\CustomTreeBuilder\Factory;
use Elements\Bundle\AlternateObjectTreesBundle\Model\Config;
use Elements\Bundle\AlternateObjectTreesBundle\Model\Config\Listing;
use Elements\Bundle\AlternateObjectTreesBundle\Service;
use Pimcore\Bundle\AdminBundle\Helper\GridHelperService;
use Pimcore\Db;
use Pimcore\Model\DataObject\AbstractObject;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\User\Permission\Definition;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/admin/elements-alternate-object-trees/admin")
 */
class AdminController extends \Pimcore\Bundle\AdminBundle\Controller\AdminController
{
    /**
     * get a list of all defined trees
     *
     * @Route("/get-alternate-object-trees")
     */
    public function getAlternateObjectTreesAction(Request $request)
    {
        $return = [];

        // create output
        $trees = new Listing();
        foreach ($trees->load() as $tree) {
            /* @var Config $tree */

            // create json object
            $config = [
                'id' => $tree->getId(),
                'text' => $tree->getName(),
                'name' => $tree->getName(),
                'valid' => $tree->getActive()
            ];

            // add extra data
            if ($tree->getLabel() != '') {
                $config['label'] = $tree->getLabel();
            }

            if ($tree->getIcon() != '') {
                $config['icon'] = $tree->getIcon();
            }

            if ($tree->getCustomTreeBuilderClass() != '') {
                $config['customTreeBuilderClass'] = $tree->getCustomTreeBuilderClass();
            }

            // permission check
            $key = 'plugin_alternateobjecttrees_tree_' . $tree->getName();
            if (($request->get('checkValid') === '1' && $this->getUser()->getUser()->getPermission($key) === false) ||     // frontend
                ($request->get('checkValid') === null && $this->getUser()->getUser()->getPermission('classes') === false)) {  // backend
                $config['valid'] = false;
            }

            $config['leaf'] = true;
            $config['expandable'] = false;

            // add
            $return[] = $config;
        }

        return $this->json($return);
    }

    /**
     * create new tree
     *
     * @Route("/add-alternate-object-tree")
     */
    public function addAlternateObjectTreeAction(Request $request)
    {
        // save tree
        $tree = new Config();
        $tree->setName($request->get('name'));
        $tree->save();

        // create permission key for the new tree
        $key = 'plugin_alternateobjecttrees_tree_' . $tree->getName();
        $permission = new Definition();
        $permission->setKey($key);

        $res = new Definition\Dao();
        $res->configure();
        $res->setModel($permission);
        $res->save();

        // send json respone
        $return = [
            'success' => true,
            'id' => $tree->getId()
        ];

        return $this->json($return);
    }

    /**
     * delete a tree
     *
     * @Route("/delete-alternate-object-tree")
     */
    public function deleteAlternateObjectTreeAction(Request $request)
    {
        // load treeconfig
        $tree = Config::getById($request->get('id'));

        // delete permission key
        $key = 'plugin_alternateobjecttrees_tree_' . $tree->getName();
        $db = Db::get();
        $db->delete('users_permission_definitions', ['`key`' => $key]);

        // delete tree
        $tree->delete();

        // send json respone
        $return = [
            'success' => true
        ];

        return $this->json($return);
    }

    /**
     * get configured tree data
     *
     * @Route("/get-alternate-object-tree")
     */
    public function getAlternateObjectTreeAction(Request $request)
    {
        $tree = Config::getById($request->get('name'));

        $config = [
            'id' => $tree->getId(),
            'name' => $tree->getName(),
            'label' => $tree->getLabel(),
            'icon' => $tree->getIcon(),
            'description' => $tree->getDescription(),
            'o_class' => $tree->getO_Class(),
            'basepath' => $tree->getBasepath(),
            'active' => $tree->getActive(),
            'customTreeBuilderClass' => $tree->getCustomTreeBuilderClass(),
            'levelDefinitions' => json_decode($tree->getJsonLevelDefinitions(), true)
        ];

        return $this->json($config);
    }

    /**
     * save tree configuration
     *
     * @Route("/save-alternate-object-tree")
     */
    public function saveAlternateObjectTreeAction(Request $request)
    {
        $tree = Config::getById($request->get('id'));
        $settings = json_decode($request->get('settings'), true);

        if (array_key_exists('name', $settings)) {
            $tree->setName($settings['name']);
        }

        if (array_key_exists('config', $settings)) {
            $tree->setName($settings['config']);
        }

        if (array_key_exists('description', $settings)) {
            $tree->setDescription($settings['description']);
        }

        if (array_key_exists('basePath', $settings)) {
            $tree->setBasePath($settings['basePath']);
        }

        if (array_key_exists('o_class', $settings)) {
            $tree->setO_Class($settings['o_class']);
        }

        if (array_key_exists('active', $settings)) {
            $tree->setActive($settings['active'] == 'true');
        }

        if (array_key_exists('icon', $settings)) {
            $tree->setIcon($settings['icon']);
        }

        if (array_key_exists('label', $settings)) {
            $tree->setLabel($settings['label']);
        }

        if (array_key_exists('customTreeBuilderClass', $settings)) {
            $tree->setCustomTreeBuilderClass($settings['customTreeBuilderClass']);
        }

        if ($request->get('levelDefinitions')) {
            $tree->setJsonLevelDefinitions($request->get('levelDefinitions'));
        }

        $tree->save();

        return new Response();
    }

    /**
     * @Route("/get-valid-fields")
     */
    public function getValidFieldsAction(Request $request)
    {
        $fields = [];
        $class = ClassDefinition::getByName($request->get('name'));

        if(!$class instanceof ClassDefinition) {
            return $this->json([]);
        }

        $lib = 'Elements\Bundle\AlternateObjectTreesBundle\LevelDefinition\\'.ucfirst($request->get('type'));
        $compatible = $lib::getCompatibleFields($class);

        foreach ($compatible as $def) {
            /* @var ClassDefinition\Data $def */
            $field = [
                'name' => $def->getName(),
                'title' => $def->getTitle(),
                'type' => $def->getFieldtype()
            ];

            $fields[] = $field;
        }

        return $this->json($fields);
    }

    /**
     * @Route("/tree-get-children-by-id")
     */
    public function treeGetChildrenByIdAction(Request $request)
    {
        $tree = null;

        // load / create tree service
        if ($request->get('alternateTreeId')) {
            $tree = Config::getById($request->get('alternateTreeId'));
            if ($tree) {
                $service = new Service($tree);
            }
        }

        if ($tree) {
            if ($treeBuilderClass = $tree->getCustomTreeBuilderClass()) {
                $treeBuilder = Factory::create($tree, $this->getUser());
                return $this->json(
                    $treeBuilder->handleNodeRequest($request)
                );
            }
        }
        $treeBuilder = new DefaultTreeBuilder($tree, $this->getUser());

        $objects = [];
        if ($request->get('isConfigNode')) {
            $level = $request->get('level');

            $filterValues = $request->get('filterValues');
            $filterValues = json_decode($filterValues, true);

            $levelConfig = $this->getLevelConfig($request, $service, $level);
            if ($levelConfig !== null) {
                // level filter

                $objects = $levelConfig['list'];

                foreach ($objects as &$object) {
                    if ($request->get('attributeValue')) {
                        $filterValues[$level] = $request->get('attributeValue');
                    }
                    $object['filterValues'] = $filterValues;
                }

                $childAmount = $levelConfig['count'];
            } else {
                // object list

                $object = AbstractObject::getById($request->get('node'));
                if (!$object) {
                    $objectList = $service->getListWithCondition($request->get('filterValues'), $request->get('level'), $request->get('attributeValue'));

                    $limit = intval($request->get('limit', 100000000));
                    $offset = intval($request->get('start'));
                    $objectList->setLimit($limit);
                    $objectList->setOffset($offset);
                    $objectList->setOrderKey('o_key');
                    $objectList->setOrder('asc');

                    foreach ($objectList->load() as $object) {
                        /* @var AbstractObject $object */
                        $tmpObject = $treeBuilder->getTreeNodeConfig($object);

                        if ($object->isAllowed('list')) {
                            $objects[] = $tmpObject;
                        }
                    }

                    $childAmount = $objectList->getTotalCount();
                }
            }
        }

        if ($request->get('limit')) {
            return $this->json([
                'total' => $childAmount,
                'nodes' => $objects
            ]);
        } else {
            return $this->json($objects);
        }
    }

    /**
     * desc?
     *
     * @param Service $service
     * @param $currentLevel
     *
     * @return array|null
     */
    private function getLevelConfig(Request $request, Service $service, $currentLevel)
    {
        $nextLevel = $currentLevel + 1;

        $levelDefinition = $service->getLevelDefinitionByLevel($nextLevel);
        if ($levelDefinition) {
            $objects = [];

            if ($currentLevel = ! 0) {
                $condition = $service->buildCondition($request->get('filterValues'), $request->get('level'), $request->get('attributeValue'));
            } else {
                $condition = $service->buildCondition($request->get('filterValues'), null, null);
            }

            $groupedValues = $levelDefinition->getGroupedValues($condition, (int)$request->get('start'), (int)$request->get('limit'));
            if ($groupedValues['count'] > 0) {
                foreach ($groupedValues['list'] as $value) {
                    $text = $levelDefinition->hasLabel() ? $levelDefinition->getLabel() : sprintf('objects %s = %s', $levelDefinition->getFieldname(), $value['label']);
                    $objects[] = [
                        'text' => sprintf($text, $value['label']) . ' (' . $value['count'] . ')',
                        'type' => 'folder',
                        'elementType' => 'object',
                        'isTarget' => false,
                        'allowDrop' => false,
                        'allowChildren' => false,
                        'level' => $nextLevel,
                        'attributeValue' => $value['value'],
                        'isConfigNode' => true,
                        'permissions' => [],
                        'filter' => $levelDefinition->getFieldname(),
                        'treeId' => $service->getTreeId()
                    ];
                }
            }

            return ['count' => $groupedValues['count'], 'list' => $objects];
        }

        return null;
    }

    /**
     * @Route("/grid-get-data")
     * @param Request $request
     *
     * @return \Pimcore\Bundle\AdminBundle\HttpFoundation\JsonResponse
     */
    public function gridGetDataAction(Request $request) {
        $tree = null;

        // load / create tree service
        if ($request->get('alternateTreeId')) {
            $tree = Config::getById($request->get('alternateTreeId'));
            if ($tree) {
                $service = new Service($tree);
            }
        }

        if(!isset($service)) {
            return $this->adminJson([], JsonResponse::HTTP_NOT_FOUND);
        }

        $levelDefinition = $service->getLevelDefinitionByLevel($request->get('level', 1));

        $objectData = [];

        $objectData['general'] = [
            'o_key' => $tree->getLabel(),
            'treeId' => $tree->getId(),
            'level' => $request->get('level'),
            'attributeValue' => $request->get('attributeValue')
        ];


        $objectData['userPermissions'] = [];

        $classDefinition = ClassDefinition::getByName($tree->getO_Class());
        if($classDefinition instanceof ClassDefinition) {
            $objectData['classes'] = [
                [
                    'id'   => $classDefinition->getId(),
                    'name' => $classDefinition->getName(),
                ],
            ];
        }


        return $this->adminJson($objectData);
    }

    /**
     * @Route("/grid-proxy")
     * @param Request $request
     */
    public function gridProxyAction(Request $request) {
        $allParams = array_merge($request->request->all(), $request->query->all());
        $allParams['folderId'] = 1;

        $requestedLanguage = $allParams['language'];
        if ($requestedLanguage) {
            if ($requestedLanguage != 'default') {
                //                $this->get('translator')->setLocale($requestedLanguage);
                $request->setLocale($requestedLanguage);
            }
        } else {
            $requestedLanguage = $request->getLocale();
        }

        $tree = null;
        // load / create tree service
        if ($request->get('alternateTreeId')) {
            $tree = Config::getById($request->get('alternateTreeId'));
            if ($tree) {
                $service = new Service($tree);
            }
        }

        $classDefinition = ClassDefinition::getByName($tree->getO_Class());
        $allParams['classId'] = $classDefinition->getId();

        if(!isset($service)) {
            return $this->adminJson([], JsonResponse::HTTP_NOT_FOUND);
        }

        $list = $service->getListWithCondition(null, $request->get('level'), $request->get('attributeValue'));
        $condition = $list->getCondition();

        $gridHelperService = new GridHelperService();
        $list = $gridHelperService->prepareListingForGrid($allParams, $requestedLanguage, $this->getAdminUser());
        $list->setCondition($list->getCondition().' AND '.$condition);

        $objects = [];
        foreach ($list as $object) {
            $o = \Pimcore\Model\DataObject\Service::gridObjectData($object, $allParams['fields'], $requestedLanguage);
            // Like for treeGetChildsByIdAction, so we respect isAllowed method which can be extended (object DI) for custom permissions, so relying only users_workspaces_object is insufficient and could lead security breach
            if ($object->isAllowed('list')) {
                $objects[] = $o;
            }
        }

        $result = ['data' => $objects, 'success' => true, 'total' => $list->getTotalCount()];

        return $this->adminJson($result);
    }

    /**
     * @param array $fieldsParameter
     *
     * @return array
     */
    protected function extractBricks(array $fields)
    {
        $bricks = [];
        if ($fields) {
            foreach ($fields as $f) {
                $fieldName = $f;
                $parts = explode('~', $f);
                if (substr($f, 0, 1) == '~') {
                    // key value, ignore for now
                } elseif (count($parts) > 1) {
                    $brickType = $parts[0];

                    if (strpos($brickType, '?') !== false) {
                        $brickDescriptor = substr($brickType, 1);
                        $brickDescriptor = json_decode($brickDescriptor, true);
                        $brickType = $brickDescriptor['containerKey'];
                    }

                    $bricks[$brickType] = $brickType;
                }
                $newFields[] = $fieldName;
            }
        }

        return $bricks;
    }
}
