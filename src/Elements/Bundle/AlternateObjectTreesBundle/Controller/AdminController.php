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
use Pimcore\Bundle\AdminBundle\Controller\Admin\DataObject\DataObjectHelperController;
use Pimcore\Bundle\AdminBundle\Helper\GridHelperService;
use Pimcore\Db;
use Pimcore\Model\DataObject\AbstractObject;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\GridConfig;
use Pimcore\Model\GridConfigFavourite;
use Pimcore\Model\User\Permission\Definition;
use Pimcore\Tool;
use Pimcore\Tool\Admin;
use Pimcore\Version;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/admin/elements-alternate-object-trees")
 */
class AdminController extends DataObjectHelperController
{
    /**
     * get a list of all defined trees
     *
     * @Route("/alternate-object-trees", methods={"GET"})
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

        return $this->adminJson($return);
    }

    /**
     * create new tree
     *
     * @Route("/alternate-object-tree", methods={"POST"})
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

        return $this->adminJson($return);
    }

    /**
     * delete a tree
     *
     * @Route("/alternate-object-tree", methods={"DELETE"})
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

        return $this->adminJson($return);
    }

    /**
     * get configured tree data
     *
     * @Route("/alternate-object-tree", methods={"GET"})
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

        return $this->adminJson($config);
    }

    /**
     * save tree configuration
     *
     * @Route("/alternate-object-tree", methods={"PUT"})
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

        return $this->adminJson(["success" => true, "id" => $tree->getId()]);
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

        return $this->adminJson($fields);
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
            return $this->adminJson([
                'total' => $childAmount,
                'nodes' => $objects
            ]);
        } else {
            return $this->adminJson($objects);
        }
    }

    /**
     * @param Request $request
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
            'o_key' => $levelDefinition->getGroupName($request->get('attributeValue')),
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
     *
     * @return \Pimcore\Bundle\AdminBundle\HttpFoundation\JsonResponse
     */
    public function gridProxyAction(Request $request) {
        $allParams = array_merge($request->request->all(), $request->query->all());
        $allParams['folderId'] = 1;

        $requestedLanguage = $this->extractLanguage($request);

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

        $classDefinition = ClassDefinition::getByName($tree->getO_Class());
        $allParams['classId'] = $classDefinition->getId();

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

    /**
     * @Route("/get-export-jobs")
     */
    public function exportJobsAction(Request $request) {
        $requestedLanguage = $this->extractLanguage($request);
        $allParams = array_merge($request->request->all(), $request->query->all());
        $allParams['folderId'] = 1;

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

        $classDefinition = ClassDefinition::getByName($tree->getO_Class());
        $allParams['classId'] = $classDefinition->getId();

        $list = $service->getListWithCondition(null, $request->get('level'), $request->get('attributeValue'));
        $condition = $list->getCondition();

        $gridHelperService = new GridHelperService();
        $list = $gridHelperService->prepareListingForGrid($allParams, $requestedLanguage, $this->getAdminUser());
        $list->setCondition($list->getCondition().' AND '.$condition);

        $ids = $list->loadIdList();

        $jobs = array_chunk($ids, 20);

        $fileHandle = uniqid('export-');
        file_put_contents(PIMCORE_SYSTEM_TEMP_DIRECTORY . '/' . $fileHandle . '.csv', '');

        return $this->adminJson(['success' => true, 'jobs' => $jobs, 'fileHandle' => $fileHandle]);
    }

    /**
     * @Route("/grid-save-column-config", methods={"POST"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function gridSaveColumnConfigAction(Request $request)
    {
        try {
            $classId = $request->get('classId');

            $searchType = $request->get('searchType');

            // grid config
            $gridConfigData = $this->decodeJson($request->get('gridconfig'));
            $gridConfigData['pimcore_version'] = Version::getVersion();
            $gridConfigData['pimcore_revision'] = Version::getRevision();
            unset($gridConfigData['settings']['isShared']);

            $metadata = $request->get('settings');
            $metadata = json_decode($metadata, true);

            $gridConfigId = $metadata['gridConfigId'];
            if ($gridConfigId) {
                try {
                    $gridConfig = GridConfig::getById($gridConfigId);
                } catch (\Exception $e) {
                }
            }
            if ($gridConfig && $gridConfig->getOwnerId() != $this->getAdminUser()->getId()) {
                throw new \Exception("don't mess around with somebody elses configuration");
            }

            $this->updateGridConfigShares($gridConfig, $metadata);

            if (!$gridConfig) {
                $gridConfig = new GridConfig();
                $gridConfig->setName(date('c'));
                $gridConfig->setClassId($classId);
                $gridConfig->setSearchType($searchType);

                $gridConfig->setOwnerId($this->getAdminUser()->getId());
            }

            if ($metadata) {
                $gridConfig->setName($metadata['gridConfigName']);
                $gridConfig->setDescription($metadata['gridConfigDescription']);
                $gridConfig->setShareGlobally($metadata['shareGlobally'] && $this->getAdminUser()->isAdmin());
            }

            $gridConfigData = json_encode($gridConfigData);
            $gridConfig->setConfig($gridConfigData);
            $gridConfig->save();

            $userId = $this->getAdminUser()->getId();

            $availableConfigs = $this->getMyOwnGridColumnConfigs($userId, $classId, $searchType);
            $sharedConfigs = $this->getSharedGridColumnConfigs($this->getAdminUser(), $classId, $searchType);

            $settings = $this->getShareSettings($gridConfig->getId());
            $settings['gridConfigId'] = (int)$gridConfig->getId();
            $settings['gridConfigName'] = $gridConfig->getName();
            $settings['gridConfigDescription'] = $gridConfig->getDescription();
            $settings['shareGlobally'] = $gridConfig->isShareGlobally();
            $settings['isShared'] = !$gridConfig || ($gridConfig->getOwnerId() != $this->getAdminUser()->getId());

            return $this->adminJson(['success' => true,
                                     'settings' => $settings,
                                     'availableConfigs' => $availableConfigs,
                                     'sharedConfigs' => $sharedConfigs,
                ]
            );
        } catch (\Exception $e) {
            return $this->adminJson(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * @Route("/grid-get-column-config", methods={"GET"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function gridGetColumnConfigAction(Request $request)
    {
        $isDelete = false;
        $tree = null;
        // load / create tree service
        if ($request->get('alternateTreeId')) {
            $tree = Config::getById($request->get('alternateTreeId'));
        }

        $gridConfigId = null;
        $gridType = 'search';
        if ($request->get('gridtype')) {
            $gridType = $request->get('gridtype');
        }

        $fields = [];

        $class = ClassDefinition::getByName($tree->getO_Class());

        $context = ['purpose' => 'gridconfig'];
        if ($class) {
            $context['class'] = $class;
        }

        if (!$fields && $class) {
            $fields = $class->getFieldDefinitions();
        }

        $types = [];
        if ($request->get('types')) {
            $types = explode(',', $request->get('types'));
        }

        $userId = $this->getAdminUser()->getId();

        $requestedGridConfigId = $isDelete ? null : $request->get('gridConfigId');

        // grid config
        $gridConfig = [];
        $searchType = $request->get('searchType');

        if (strlen($requestedGridConfigId) == 0 && $class) {
            // check if there is a favourite view
            $favourite = null;
            try {
                try {
                    $favourite = GridConfigFavourite::getByOwnerAndClassAndObjectId($userId, $class->getId(), 0, $searchType);
                } catch (\Exception $e) {
                }

                if ($favourite) {
                    $requestedGridConfigId = $favourite->getGridConfigId();
                }
            } catch (\Exception $e) {
            }
        }

        if (is_numeric($requestedGridConfigId) && $requestedGridConfigId > 0) {
            $db = Db::get();
            $configListingConditionParts = [];
            $configListingConditionParts[] = 'ownerId = ' . $userId;
            $configListingConditionParts[] = 'classId = ' . $db->quote($class->getId());

            if ($searchType) {
                $configListingConditionParts[] = 'searchType = ' . $db->quote($searchType);
            }

            try {
                $savedGridConfig = GridConfig::getById($requestedGridConfigId);
            } catch (\Exception $e) {
            }

            if ($savedGridConfig) {
                try {
                    $userIds = [$this->getAdminUser()->getId()];
                    if ($this->getAdminUser()->getRoles()) {
                        $userIds = array_merge($userIds, $this->getAdminUser()->getRoles());
                    }
                    $userIds = implode(',', $userIds);
                    $shared = ($savedGridConfig->getOwnerId() != $userId && $savedGridConfig->isShareGlobally()) || $db->fetchOne('select * from gridconfig_shares where sharedWithUserId IN (' . $userIds . ') and gridConfigId = ' . $savedGridConfig->getId());

//                    $shared = $savedGridConfig->isShareGlobally() ||GridConfigShare::getByGridConfigAndSharedWithId($savedGridConfig->getId(), $this->getUser()->getId());
                } catch (\Exception $e) {
                }

                if (!$shared && $savedGridConfig->getOwnerId() != $this->getAdminUser()->getId()) {
                    throw new \Exception('you are neither the onwner of this config nor it is shared with you');
                }
                $gridConfigId = $savedGridConfig->getId();
                $gridConfig = $savedGridConfig->getConfig();
                $gridConfig = json_decode($gridConfig, true);
                $gridConfigName = $savedGridConfig->getName();
                $gridConfigDescription = $savedGridConfig->getDescription();
                $sharedGlobally = $savedGridConfig->isShareGlobally();
            }
        }

        $localizedFields = [];
        $objectbrickFields = [];
        if (is_array($fields)) {
            foreach ($fields as $key => $field) {
                if ($field instanceof ClassDefinition\Data\Localizedfields) {
                    $localizedFields[] = $field;
                } elseif ($field instanceof ClassDefinition\Data\Objectbricks) {
                    $objectbrickFields[] = $field;
                }
            }
        }

        $availableFields = [];

        if (empty($gridConfig)) {
            $availableFields = $this->getDefaultGridFields(
                $request->get('no_system_columns'),
                $class,
                $gridType,
                $request->get('no_brick_columns'),
                $fields,
                $context,
                null,
                $types);
        } else {
            $savedColumns = $gridConfig['columns'];
            foreach ($savedColumns as $key => $sc) {
                if (!$sc['hidden']) {
                    if (in_array($key, self::SYSTEM_COLUMNS)) {
                        $colConfig = [
                            'key' => $key,
                            'type' => 'system',
                            'label' => $key,
                            'position' => $sc['position']];
                        if (isset($sc['width'])) {
                            $colConfig['width'] = $sc['width'];
                        }
                        $availableFields[] = $colConfig;
                    } else {
                        $keyParts = explode('~', $key);

                        if (substr($key, 0, 1) == '~') {
                            // not needed for now
                            $type = $keyParts[1];
                            //                            $field = $keyParts[2];
                            $groupAndKeyId = explode('-', $keyParts[3]);
                            $keyId = $groupAndKeyId[1];

                            if ($type == 'classificationstore') {
                                $keyDef = DataObject\Classificationstore\KeyConfig::getById($keyId);
                                if ($keyDef) {
                                    $keyFieldDef = json_decode($keyDef->getDefinition(), true);
                                    if ($keyFieldDef) {
                                        $keyFieldDef = \Pimcore\Model\DataObject\Classificationstore\Service::getFieldDefinitionFromJson($keyFieldDef, $keyDef->getType());
                                        $fieldConfig = $this->getFieldGridConfig($keyFieldDef, $gridType, $sc['position'], true, null, $class, $objectId);
                                        if ($fieldConfig) {
                                            $fieldConfig['key'] = $key;
                                            $fieldConfig['label'] = '#' . $keyFieldDef->getTitle();
                                            $availableFields[] = $fieldConfig;
                                        }
                                    }
                                }
                            }
                        } elseif (count($keyParts) > 1) {
                            $brick = $keyParts[0];
                            $brickDescriptor = null;

                            if (strpos($brick, '?') !== false) {
                                $brickDescriptor = substr($brick, 1);
                                $brickDescriptor = json_decode($brickDescriptor, true);
                                $keyPrefix = $brick . '~';
                                $brick = $brickDescriptor['containerKey'];
                            } else {
                                $keyPrefix = $brick . '~';
                            }

                            $fieldname = $keyParts[1];

                            $brickClass = \Pimcore\Model\DataObject\Objectbrick\Definition::getByKey($brick);

                            if ($brickDescriptor) {
                                $innerContainer = $brickDescriptor['innerContainer'] ? $brickDescriptor['innerContainer'] : 'localizedfields';
                                $localizedFields = $brickClass->getFieldDefinition($innerContainer);
                                $fd = $localizedFields->getFieldDefinition($brickDescriptor['brickfield']);
                            } else {
                                $fd = $brickClass->getFieldDefinition($fieldname);
                            }

                            if (!empty($fd)) {
                                $fieldConfig = $this->getFieldGridConfig($fd, $gridType, $sc['position'], true, $keyPrefix, $class, null);
                                if (!empty($fieldConfig)) {
                                    if (isset($sc['width'])) {
                                        $fieldConfig['width'] = $sc['width'];
                                    }
                                    $availableFields[] = $fieldConfig;
                                }
                            }
                        } else {
                            if (\Pimcore\Model\DataObject\Service::isHelperGridColumnConfig($key)) {
                                $calculatedColumnConfig = $this->getCalculatedColumnConfig($savedColumns[$key]);
                                if ($calculatedColumnConfig) {
                                    $availableFields[] = $calculatedColumnConfig;
                                }
                            } else {
                                $fd = $class->getFieldDefinition($key);
                                //if not found, look for localized fields
                                if (empty($fd)) {
                                    foreach ($localizedFields as $lf) {
                                        $fd = $lf->getFieldDefinition($key);
                                        if (!empty($fd)) {
                                            break;
                                        }
                                    }
                                }

                                if (!empty($fd)) {
                                    $fieldConfig = $this->getFieldGridConfig($fd, $gridType, $sc['position'], true, null, $class, null);
                                    if (!empty($fieldConfig)) {
                                        if (isset($sc['width'])) {
                                            $fieldConfig['width'] = $sc['width'];
                                        }

                                        $availableFields[] = $fieldConfig;
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        usort($availableFields, function ($a, $b) {
            if ($a['position'] == $b['position']) {
                return 0;
            }

            return ($a['position'] < $b['position']) ? -1 : 1;
        });

        $config = \Pimcore\Config::getSystemConfig();
        $frontendLanguages = Admin::reorderWebsiteLanguages(Admin::getCurrentUser(), $config->general->validLanguages);
        if ($frontendLanguages) {
            $language = explode(',', $frontendLanguages)[0];
        } else {
            $language = $request->getLocale();
        }

        if (!Tool::isValidLanguage($language)) {
            $validLanguages = Tool::getValidLanguages();
            $language = $validLanguages[0];
        }

        if (!empty($gridConfig) && !empty($gridConfig['language'])) {
            $language = $gridConfig['language'];
        }

        if (!empty($gridConfig) && !empty($gridConfig['pageSize'])) {
            $pageSize = $gridConfig['pageSize'];
        }

        $classId = $request->get('id');
        $availableConfigs = $classId ? $this->getMyOwnGridColumnConfigs($userId, $classId, $searchType) : [];
        $sharedConfigs = $classId ? $this->getSharedGridColumnConfigs($this->getAdminUser(), $classId, $searchType) : [];
        $settings = $this->getShareSettings((int)$gridConfigId);
        $settings['gridConfigId'] = (int)$gridConfigId;
        $settings['gridConfigName'] = $gridConfigName;
        $settings['gridConfigDescription'] = $gridConfigDescription;
        $settings['shareGlobally'] = $sharedGlobally;
        $settings['isShared'] = (!$gridConfigId || $shared) ? true : false;

        $result = [
            'sortinfo' => isset($gridConfig['sortinfo']) ? $gridConfig['sortinfo'] : false,
            'language' => $language,
            'availableFields' => $availableFields,
            'settings' => $settings,
            'onlyDirectChildren' => isset($gridConfig['onlyDirectChildren']) ? $gridConfig['onlyDirectChildren'] : false,
            'pageSize' => isset($gridConfig['pageSize']) ? $gridConfig['pageSize'] : false,
            'availableConfigs' => $availableConfigs,
            'sharedConfigs' => $sharedConfigs

        ];

        return $this->adminJson($result);
    }
}
