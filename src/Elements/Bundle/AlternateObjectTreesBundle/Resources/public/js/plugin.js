/**
 * Pimcore
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.pimcore.org/license
 *
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 */

pimcore.registerNS("pimcore.plugin.alternateObjectTrees.plugin");
pimcore.plugin.alternateObjectTrees.plugin = Class.create(pimcore.plugin.admin, {

    getClassName: function (){
        return "pimcore.plugin.AlternateObjectTrees";
    },

    initialize: function(){
        pimcore.plugin.broker.registerPlugin(this);
    },

    uninstall: function(){
        //TODO remove from menu
    },

    pimcoreReady: function (params,broker) {

        var treeList = [];

        // load defined trees
        Ext.Ajax.request({
            url: "/admin/elements-alternate-object-trees/alternate-object-trees",
            params: {
                checkValid: 1
            },
            method: "GET",
            success: function(response){
                var list = Ext.util.JSON.decode(response.responseText);
                Ext.each(list, function(tree) {

                    // add lang for security settings
                    var key = "plugin_alternateobjecttrees_tree_" + tree.name;
                    pimcore.system_i18n[ key ] = "Alternate Object Tree '" + tree.name + "'";

                    // show only valid trees
                    if(!tree.valid)
                        return;

                    // add tree tab
                    var opt = {
                        id: tree.id,
                        name: tree.name
                    };

                    if(tree.label)
                        opt.label = tree.label;

                    if(tree.icon)
                        opt.icon = tree.icon;

                    var t = new pimcore.plugin.alternateObjectTrees.tree(opt);
                    treeList.push(t);
                });
            }
        });

        pimcore.globalmanager.add("layout_alternateobject_tree", treeList);


        // add config panel to settings menu
        pimcore.globalmanager.get("layout_toolbar").settingsMenu.items.each(function(item) {
            if(item.iconCls == 'pimcore_icon_object' || item.iconCls == 'pimcore_nav_icon_object') {
                item.menu.add({
                    text: t("plugin_alternate_object_trees_config"),
                    iconCls: "plugin_alternate_object_trees",
                    handler: function () {
                        try {
                            pimcore.globalmanager.get("plugin_alternate_object_trees_config").activate();
                        }
                        catch (e) {
                            //console.log(e);
                            pimcore.globalmanager.add("plugin_alternate_object_trees_config", new pimcore.plugin.alternateObjectTrees.config.panel());
                        }

                    }
                });
            }
        });
    }
});

new pimcore.plugin.alternateObjectTrees.plugin();