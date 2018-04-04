<?php

namespace Elements\Bundle\AlternateObjectTreesBundle\Controller;

use Elements\Bundle\AlternateObjectTreesBundle\Model\Config;
use Elements\Bundle\AlternateObjectTreesBundle\Model\Config\Listing;
use Elements\Bundle\AlternateObjectTreesBundle\Service;
use Pimcore\Db;
use Pimcore\Model\DataObject\AbstractObject;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\User\Permission\Definition;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/admin/elements-alternate-object-trees/admin")
 */
class AdminController extends \Pimcore\Bundle\AdminBundle\Controller\AdminController {

    /**
     * get a list of all defined trees
     *
     * @Route("/get-alternate-object-trees")
     */
    public function getAlternateObjectTreesAction(Request $request)
    {
        $return = array();

        // create output
        $trees = new Listing();
        foreach($trees->load() as $tree)
        {
            /* @var Config $tree */

            // create json object
            $config = array(
                'id' => $tree->getId(),
                'text' => $tree->getName(),
                'name' => $tree->getName(),
                'valid' => $tree->getActive()
            );

            // add extra data
            if($tree->getLabel() != '')
                $config['label'] = $tree->getLabel();

            if($tree->getIcon() != '')
                $config['icon'] = $tree->getIcon();

            // permission check
            $key = 'plugin_alternateobjecttrees_tree_' . $tree->getName();
            if( ($request->get('checkValid') === '1' && $this->getUser()->getUser()->getPermission( $key ) === false) ||     // frontend
                ($request->get('checkValid') === null && $this->getUser()->getUser()->getPermission('classes') === false) )  // backend
            {
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
        $tree->setName( $request->get('name') );
        $tree->save();

        // create permission key for the new tree
        $key = 'plugin_alternateobjecttrees_tree_' . $tree->getName();
        $permission = new Definition();
        $permission->setKey( $key );

        $res = new Definition\Dao();
        $res->configure();
        $res->setModel( $permission );
        $res->save();

        // send json respone
        $return = array(
            'success' => true,
            'id' => $tree->getId()
        );

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
        $db->delete('users_permission_definitions', ["`key`" => $key]);

        // delete tree
        $tree->delete();

        // send json respone
        $return = array(
            'success' => true
        );

        return $this->json($return);
    }


    /**
     * get configured tree data
     *
     * @Route("/get-alternate-object-tree")
     */
    public function getAlternateObjectTreeAction(Request $request)
    {
        $tree = Config::getById( $request->get('name') );

        $config = array(
            "id" => $tree->getId(),
            "name" => $tree->getName(),
            "label" => $tree->getLabel(),
            "icon" => $tree->getIcon(),
            "description" => $tree->getDescription(),
            "o_class" => $tree->getO_Class(),
            "basepath" => $tree->getBasepath(),
            "active" => $tree->getActive(),
            "levelDefinitions" => json_decode($tree->getJsonLevelDefinitions(), true)
        );

        return $this->json($config);
    }


    /**
     * save tree configuration
     *
     * @Route("/save-alternate-object-tree")
     */
    public function saveAlternateObjectTreeAction(Request $request)
    {
        $tree = Config::getById( $request->get('id') );
        $settings = json_decode($request->get('settings'), true);

        if(array_key_exists('name', $settings))
            $tree->setName( $settings['name'] );

        if(array_key_exists('config', $settings))
            $tree->setName( $settings['config'] );

        if(array_key_exists('description', $settings))
            $tree->setDescription( $settings['description'] );

        if(array_key_exists('basePath', $settings))
            $tree->setBasePath( $settings['basePath'] );

        if(array_key_exists('o_class', $settings))
            $tree->setO_Class( $settings['o_class'] );

        if(array_key_exists('active', $settings))
            $tree->setActive( $settings['active'] == 'true' );

        if(array_key_exists('icon', $settings))
            $tree->setIcon( $settings['icon'] );

        if(array_key_exists('label', $settings))
            $tree->setLabel( $settings['label'] );

        if($request->get('levelDefinitions'))
            $tree->setJsonLevelDefinitions( $request->get('levelDefinitions') );

        $tree->save();
        return new Response();
    }


    /**
     * @Route("/get-valid-fields")
     */
    public function getValidFieldsAction(Request $request)
    {
        $fields = array();
        $class = ClassDefinition::getByName( $request->get('name') );

        $lib = 'Elements\Bundle\AlternateObjectTreesBundle\LevelDefinition\\'.ucfirst($request->get('type'));
        $compatible = $lib::getCompatibleFields($class);

        foreach($compatible as $def)
        {
            /* @var ClassDefinition\Data $def */
            $field = array(
                'name' => $def->getName(),
                'title' => $def->getTitle(),
                'type' => $def->getFieldtype()
            );

            $fields[] = $field;

        }

        return $this->json($fields);
    }


    /**
     * @Route("/tree-get-children-by-id")
     */
    public function treeGetChildrenByIdAction(Request $request)
    {

        // load / create tree service
        if($request->get("alternateTreeId"))
        {
            $tree = Config::getById( $request->get("alternateTreeId") );
            if($tree)
                $service = new Service( $tree );
        }


        $objects = array();
        if($request->get("isConfigNode")) {

            $level = $request->get("level");

            $filterValues = $request->get("filterValues");
            $filterValues = json_decode($filterValues, true);

            $levelConfig = $this->getLevelConfig($request, $service, $level);
            if($levelConfig !== null) {
                // level filter

                $objects = $levelConfig['list'];

                foreach($objects as &$object) {
                    if($request->get("attributeValue")) {
                        $filterValues[$level] = $request->get("attributeValue");
                    }
                    $object["filterValues"] = $filterValues;
                }

                $childAmount = $levelConfig['count'];

            } else {
                // object list

                $object = AbstractObject::getById($request->get("node"));
                if(!$object) {
                    $objectList = $service->getListWithCondition($request->get("filterValues"), $request->get("level"), $request->get("attributeValue"));

                    $limit = intval($request->get("limit", 100000000));
                    $offset = intval($request->get("start"));
                    $objectList->setLimit($limit);
                    $objectList->setOffset($offset);
                    $objectList->setOrderKey("o_key");
                    $objectList->setOrder("asc");

                    foreach ($objectList->load() as $object) {
                        /* @var AbstractObject $object */
                        $tmpObject = $this->getTreeNodeConfig($object);

                        if ($object->isAllowed("list")) {
                            $objects[] = $tmpObject;
                        }
                    }

                    $childAmount = $objectList->getTotalCount();
                }

            }
        }


        if ($request->get("limit")) {
            return $this->json(array(
                "total" => $childAmount,
                "nodes" => $objects
            ));
        }
        else {
            return $this->json($objects);
        }

    }


    /**
     * desc?
     * @param Service $service
     * @param $currentLevel
     *
     * @return array|null
     */
    private function getLevelConfig(Request $request, Service $service, $currentLevel) {
        $nextLevel = $currentLevel + 1;

        $levelDefinition = $service->getLevelDefinitionByLevel($nextLevel);
        if($levelDefinition) {
            $objects = array();

            if($currentLevel =! 0) {
                $condition = $service->buildCondition($request->get("filterValues"), $request->get("level"), $request->get("attributeValue"));
            } else {
                $condition = $service->buildCondition($request->get("filterValues"), null, null);
            }

            $groupedValues = $levelDefinition->getGroupedValues($condition, (int)$request->get("start"), (int)$request->get("limit"));
            if($groupedValues['count'] > 0) {

                foreach($groupedValues['list'] as $value) {
                    $text = $levelDefinition->hasLabel() ? $levelDefinition->getLabel() : sprintf("objects %s = %s", $levelDefinition->getFieldname(), $value["label"]);
                    $objects[] = array(
                        "text" => sprintf($text, $value["label"]) . " (" . $value["count"] . ")",
                        "type" => "folder",
                        "elementType" => "object",
                        "isTarget" => false,
                        "allowDrop" => false,
                        "allowChildren" => false,
                        "level" => $nextLevel,
                        "attributeValue" => $value["value"],
                        "isConfigNode" => true,
                        "permissions" => array()
                    );
                }
            }
            return array('count' => $groupedValues['count'], 'list' => $objects);
        } else {
            return null;
        }
    }


    /**
     * @param AbstractObject $child
     * @return array
     */
    protected function getTreeNodeConfig($child)
    {
        $tmpObject = array(
            "id" => $child->getId(),
            "text" => $child->getKey(),
            "type" => $child->getType(),
            "path" => $child->getFullPath(),
            "basePath" => $child->getPath(),
            "elementType" => "object",
            "locked" => $child->isLocked(),
            "lockOwner" => $child->getLocked() ? true : false,
            "data" => [
                "permissions" => []
            ]
        );

        $tmpObject["isTarget"] = false;
        $tmpObject["allowDrop"] = false;
        $tmpObject["allowChildren"] = false;

        $tmpObject["leaf"] = $child->hasNoChilds();

        $tmpObject["isTarget"] = false;
        $tmpObject["allowDrop"] = false;
        $tmpObject["allowChildren"] = false;
        $tmpObject["cls"] = "";

        if ($child->getType() == "folder") {
            $tmpObject["qtipCfg"] = array(
                "title" => "ID: " . $child->getId()
            );
        }
        else {
            $tmpObject["published"] = $child->isPublished();
            $tmpObject["className"] = $child->getClass()->getName();
            $tmpObject["qtipCfg"] = array(
                "title" => "ID: " . $child->getId(),
                "text" => 'Type: ' . $child->getClass()->getName()
            );

            if (!$child->isPublished()) {
                $tmpObject["cls"] .= "pimcore_unpublished ";
            }
        }
        if($child->getElementAdminStyle()->getElementIcon()) {
            $tmpObject["icon"] = $child->getO_elementAdminStyle()->getElementIcon();
        }
        if($child->getElementAdminStyle()->getElementIconClass()) {
            $tmpObject["iconCls"] = $child->getO_elementAdminStyle()->getElementIconClass();
        }
        if($child->getElementAdminStyle()->getElementCssClass()) {
            $tmpObject["cls"] .= $child->getO_elementAdminStyle()->getElementCssClass() . " ";
        }


        $tmpObject["expanded"] = $child->hasNoChilds();
        $tmpObject["permissions"] = $child->getUserPermissions($this->getUser());


        if ($child->isLocked()) {
            $tmpObject["cls"] .= "pimcore_treenode_locked ";
        }
        if ($child->getLocked()) {
            $tmpObject["cls"] .= "pimcore_treenode_lockOwner ";
        }

        return $tmpObject;
    }
}