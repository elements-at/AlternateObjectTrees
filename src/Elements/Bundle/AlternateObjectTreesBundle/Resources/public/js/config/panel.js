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

pimcore.registerNS("pimcore.plugin.alternateObjectTrees.config.panel");
pimcore.plugin.alternateObjectTrees.config.panel = Class.create({

    initialize: function () {
        this.getTabPanel();
    },

    activate: function () {
        var tabPanel = Ext.getCmp("pimcore_panel_tabs");
        tabPanel.activate("plugin_alternate_object_trees_config");
    },

    getTabPanel: function () {

        if (!this.panel) {
            this.panel = new Ext.Panel({
                id: "plugin_alternate_object_trees_config",
                title: t("plugin_alternate_object_trees_config"),
                iconCls: "plugin_alternate_object_trees",
                border: false,
                layout: "border",
                closable: true,
                items: [this.getTree(), this.getEditPanel()]
            });

            var tabPanel = Ext.getCmp("pimcore_panel_tabs");
            tabPanel.add(this.panel);
            tabPanel.setActiveItem("plugin_alternate_object_trees_config");

            this.panel.on("destroy", function () {
                pimcore.globalmanager.remove("plugin_alternate_object_trees_config");
            }.bind(this));

            pimcore.layout.refresh();
        }

        return this.panel;
    },

    getTree: function () {
        if (!this.tree) {
            var store = Ext.create('Ext.data.TreeStore', {
                autoLoad: true,
                autoSync: false,
                proxy: {
                    type: 'ajax',
                    url: '/admin/elements-alternate-object-trees/alternate-object-trees',
                    reader: {
                        type: 'json'
                    }
                }
            });

            this.tree = new Ext.tree.TreePanel({
                store: store,
                id: "plugin_alternate_object_trees_config_tree",
                region: "west",
                useArrows: true,
                scrollable: true,
                // animate: true,
                // containerScroll: true,
                border: true,
                width: 200,
                split: true,
                root: {
                    // nodeType: 'async',
                    id: '0'
                },
                draggable: false,
                listeners: this.getTreeNodeListeners(),
//                 loader: new Ext.tree.TreeLoader({
//                     dataUrl: '/admin/elements-alternate-object-trees/admin/get-alternate-object-trees',
//                     requestMethod: "GET",
// //                    baseParams: {
// //                        active: 0
// //                    },
//                     baseAttrs: {
//                         listeners: this.getTreeNodeListeners(),
//                         reference: this,
//                         allowDrop: false,
//                         allowChildren: false,
//                         isTarget: false,
//                         iconCls: "plugin_alternate_object_trees_config",
//                         leaf: true
//                     }
//                 }),
                rootVisible: false,
                tbar: {
                    items: [
                        {
                            text: t("plugin_alternate_object_trees_add_config"),
                            iconCls: "pimcore_icon_add",
                            handler: this.addField.bind(this)
                        }
                    ]
                }
            });

            this.tree.on("render", function () {
                this.getRootNode().expand();
            });
        }

        return this.tree;
    },

    getEditPanel: function () {
        if (!this.editPanel) {
            this.editPanel = new Ext.TabPanel({
                region: "center"
            });
        }

        return this.editPanel;
    },

    getTreeNodeListeners: function () {
        var treeNodeListeners = {
            'itemclick': this.onTreeNodeClick.bind(this),
            "itemcontextmenu": this.onTreeNodeContextmenu.bind(this)
        };

        return treeNodeListeners;
    },

    onTreeNodeClick: function (node, model) {
        this.openTreeConfig(model.get('id'));
    },

    openTreeConfig: function (id) {

        var existingPanel = Ext.getCmp("plugin_alternate_object_trees_config_panel_" + id);
        if (existingPanel) {
            this.editPanel.activate(existingPanel);
            return;
        }

        Ext.Ajax.request({
            url: "/admin/elements-alternate-object-trees/alternate-object-tree",
            method: "GET",
            params: {
                name: id
            },
            success: function (response) {
                var data = Ext.decode(response.responseText);
//                data.text = data.name;

                var fieldPanel = new pimcore.plugin.alternateObjectTrees.config.item(data, this);
                pimcore.layout.refresh();
            }.bind(this)
        });
    },

    onTreeNodeContextmenu: function (view, model, el, index, event) {
        event.preventDefault();

        view.select();

        var menu = new Ext.menu.Menu();
        menu.add(new Ext.menu.Item({
            text: t('delete'),
            iconCls: "pimcore_icon_delete",
            handler: this.deleteField.bind(this, view, model)
        }));

        menu.showAt(event.pageX + 1, event.pageY + 1);
    },

    addField: function () {
        Ext.MessageBox.prompt(t('plugin_alternate_object_trees_add_tree'), t('plugin_alternate_object_trees_name'),
            this.addFieldComplete.bind(this), null, null, "");
    },

    addFieldComplete: function (button, value, object) {
        var regresult = value.match(/[a-zA-Z0-9_\-]+/);

        if (button == "ok" && value.length > 2 && regresult == value) {

            var list = this.tree.getRootNode().childNodes;
            for (var i = 0; i < list.length; i++) {
                if (list[i].text == value) {
                    Ext.MessageBox.alert(t('plugin_alternate_object_trees_add_tree'),
                        t('the_key_is_already_in_use_in_this_level_please_choose_an_other_key'));
                    return;
                }
            }

            Ext.Ajax.request({
                url: "/admin/elements-alternate-object-trees/alternate-object-tree",
                method: "POST",
                params: {
                    name: value
                },
                success: function (response) {
                    var data = Ext.decode(response.responseText);

                    // this.tree.getRootNode().reload();
                    this.tree.getStore().load();

                    if (!data || !data.success) {
                        Ext.Msg.alert(t('plugin_alternate_object_trees_add_tree'), t('problem_creating_new_thumbnail'));
                    } else {
                        this.openTreeConfig(data.id);
                    }
                }.bind(this)
            });
        }
        else if (button == "cancel") {
            return;
        }
        else {
            Ext.Msg.alert(t('plugin_alternate_object_trees_add_tree'), t('problem_creating_new_thumbnail'));
        }
    },

    deleteField: function (view, model) {
        Ext.Ajax.request({
            url: "/admin/elements-alternate-object-trees/delete-alternate-object-tree",
            method: "DELETE",
            params: {
                id: model.get('id')
            }
        });

        this.getEditPanel().removeAll();
        this.tree.getStore().remove(model);
    }
});

