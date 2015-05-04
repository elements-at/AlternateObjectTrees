<?php

class AlternateObjectTrees_AdminController extends Pimcore_Controller_Action_Admin {

    /**
     * get a list of all defined trees
     */
    public function getAlternateObjectTreesAction()
    {
        $return = array();

        // create output
        $trees = new AlternateObjectTrees_Config_List();
        foreach($trees->load() as $tree)
        {
            /* @var AlternateObjectTrees_Config $tree */

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
            if( ($this->getParam('checkValid') === '1' && $this->getUser()->getPermission( $key ) === false) ||     // frontend
                ($this->getParam('checkValid') === null && $this->getUser()->getPermission('classes') === false) )  // backend
            {
                $config['valid'] = false;
            }

            // add
            $return[] = $config;
        }

        $this->_helper->json($return);
    }


    /**
     * create new tree
     */
    public function addAlternateObjectTreeAction()
    {
        // save tree
        $tree = new AlternateObjectTrees_Config();
        $tree->setName( $this->getRequest()->getParam('name') );
        $tree->save();

        // create permission key for the new tree
        $key = 'plugin_alternateobjecttrees_tree_' . $tree->getName();
        $permission = new User_Permission_Definition();
        $permission->setKey( $key );

        $res = new User_Permission_Definition_Resource();
        $res->configure( Pimcore_Resource::get() );
        $res->setModel( $permission );
        $res->save();

        // send json respone
        $return = array(
            'success' => true,
            'id' => $tree->getId()
        );

        $this->_helper->json($return);
    }


    /**
     * delete a tree
     */
    public function delAlternateObjectTreeAction()
    {
        // load treeconfig
        $tree = AlternateObjectTrees_Config::getById($this->getParam('id'));

        // delete permission key
        $key = 'plugin_alternateobjecttrees_tree_' . $tree->getName();
        $db = Pimcore_Resource::get();
        $db->delete('users_permission_definitions', '`key` = ' . $db->quote($key) );

        // delete tree
        $tree->delete();

        // send json respone
        $return = array(
            'success' => true
        );

        $this->_helper->json($return);
    }


    /**
     * get configured tree data
     */
    public function getAlternateObjectTreeAction()
    {
        $tree = AlternateObjectTrees_Config::getById( $this->getRequest()->getParam('name') );

        $config = array(
            "id" => $tree->getId(),
            "name" => $tree->getName(),
            "label" => $tree->getLabel(),
            "icon" => $tree->getIcon(),
            "description" => $tree->getDescription(),
            "o_class" => $tree->getO_Class(),
            "basepath" => $tree->getBasepath(),
            "active" => $tree->getActive(),
            "levelDefinitions" => json_decode($tree->getJsonLevelDefinitions())
        );

        $this->_helper->json($config);
    }


    /**
     * save tree configuration
     */
    public function saveAlternateObjectTreeAction()
    {
        $tree = AlternateObjectTrees_Config::getById( $this->getRequest()->getParam('id') );
        $settings = json_decode($this->getRequest()->getParam('settings'), true);

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

        if($this->getRequest()->getParam('levelDefinitions'))
            $tree->setJsonLevelDefinitions( $this->getRequest()->getParam('levelDefinitions') );

        $tree->save();
        exit;
    }


    /**
     *
     */
    public function getValidFieldsAction()
    {
        $fields = array();
        $class = Object_Class::getByName( $this->getParam('name') );

        $lib = 'AlternateObjectTrees_LevelDefinition_'.ucfirst($this->getParam('type'));
        $compatible = $lib::getCompatibleFields($class);

        foreach($compatible as $def)
        {
            /* @var Object_Class_Data $def */
            $field = array(
                'name' => $def->getName(),
                'title' => $def->getTitle(),
                'type' => $def->getFieldtype()
            );

            $fields[] = $field;

        }

        $this->_helper->json($fields);
    }


    /**
     *
     */
    public function treeGetChildsByIdAction()
    {

        // load / create tree service
        if($this->getParam("alternateTreeId"))
        {
            $tree = AlternateObjectTrees_Config::getById( $this->getParam("alternateTreeId") );
            if($tree)
                $service = new AlternateObjectTrees_Service( $tree );
        }


        $objects = array();
        if($this->getParam("isConfigNode")) {

            $level = $this->getParam("level");

            $filterValues = $this->getParam("filterValues");
            $filterValues = json_decode($filterValues, true);

            $levelConfig = $this->getLevelConfig($service, $level);
            if($levelConfig !== null) {
                // level filter

                $objects = $levelConfig['list'];

                foreach($objects as &$object) {
                    if($this->getParam("attributeValue")) {
                        $filterValues[$level] = $this->getParam("attributeValue");
                    }
                    $object["filterValues"] = $filterValues;
                }

                $childAmount = $levelConfig['count'];

            } else {
                // object list

                $object = Object_Abstract::getById($this->getParam("node"));
                if(!$object) {
                    $objectList = $service->getListWithCondition($this->getParam("filterValues"), $this->getParam("level"), $this->getParam("attributeValue"));

                    $limit = intval($this->getParam("limit"));
                    if (!$this->getParam("limit")) {
                        $limit = 100000000;
                    }
                    $offset = intval($this->getParam("start"));
                    $objectList->setLimit($limit);
                    $objectList->setOffset($offset);
                    $objectList->setOrderKey("o_key");
                    $objectList->setOrder("asc");

                    foreach ($objectList->load() as $object) {
                        /* @var Object_Abstract $object */
                        $tmpObject = $this->getTreeNodeConfig($object);

                        if ($object->isAllowed("list")) {
                            $objects[] = $tmpObject;
                        }
                    }

                    $childAmount = $objectList->getTotalCount();
                }

            }
        }


        if ($this->getParam("limit")) {
            $this->_helper->json(array(
                "total" => $childAmount,
                "nodes" => $objects
            ));
        }
        else {
            $this->_helper->json($objects);
        }

    }


    /**
     * desc?
     * @param AlternateObjectTrees_Service $service
     * @param $currentLevel
     *
     * @return array|null
     */
    private function getLevelConfig(AlternateObjectTrees_Service $service, $currentLevel) {
        $nextLevel = $currentLevel + 1;

        $levelDefinition = $service->getLevelDefinitionByLevel($nextLevel);
        if($levelDefinition) {
            $objects = array();

            if($currentLevel =! 0) {
                $condition = $service->buildCondition($this->getParam("filterValues"), $this->getParam("level"), $this->getParam("attributeValue"));
            } else {
                $condition = $service->buildCondition($this->getParam("filterValues"), null, null);
            }

            $groupedValues = $levelDefinition->getGroupedValues($condition, (int)$this->getParam("start"), (int)$this->getParam("limit"));
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
     * @param Object_Abstract $child
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
            "lockOwner" => $child->getLocked() ? true : false
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