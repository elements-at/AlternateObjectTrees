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

pimcore.registerNS("pimcore.plugin.alternateObjectTrees.config.item");
pimcore.plugin.alternateObjectTrees.config.item = Class.create({

    initialize: function (data, parentPanel) {
        this.parentPanel = parentPanel;
        this.data = data;
        this.currentIndex = 0;

        this.addLayout();

        if(this.data.levelDefinitions && this.data.levelDefinitions.length > 0) {
            for(var i=0; i<this.data.levelDefinitions.length; i++) {
                this.addItem("item" + ucfirst(this.data.levelDefinitions[i].type), this.data.levelDefinitions[i].config);
            }
        }
    },


    addLayout: function () {

        this.editpanel = new Ext.Panel({
            region: "center",
            bodyStyle: "padding: 20px;",
            autoScroll: true
        });

        var panelButtons = [];
        panelButtons.push({
            text: t("save"),
            iconCls: "pimcore_icon_apply",
            handler: this.save.bind(this)
        }); 


        var addMenu = [];
        var itemTypes = Object.keys(pimcore.plugin.alternateObjectTrees.config.items);
        for(var i=0; i<itemTypes.length; i++) {
            if(itemTypes[i].indexOf("item") == 0) {
                addMenu.push({
                    iconCls: "pimcore_icon_add",
                    handler: this.addItem.bind(this, itemTypes[i]),
                    text: pimcore.plugin.alternateObjectTrees.config.items[itemTypes[i]](null, null,true)
                });
            }
        }

        this.itemContainer = new Ext.Panel({
            title: t("plugin_alternate_object_trees_levels"),
            style: "margin: 20px 0 0 0;",
            tbar: [{
                iconCls: "pimcore_icon_add",
                menu: addMenu
            }],
            border: false
        });

        var itemContainer = this.itemContainer;

        this.settings = new Ext.form.FormPanel({
            layout: "form",
            border: false,
            items: [{
                xtype: "panel",
                autoHeight: true,
                border: false
            }, {
                xtype: "hidden",
                name: "id",
                value: this.data.id
            }, {
                xtype: "hidden",
                name: "name",
                value: this.data.name,
                fieldLabel: t("name"),
                width: 400
            }, {
                xtype: "textfield",
                name: "label",
                value: this.data.label,
                fieldLabel: t("label"),
                width: 400
            }, {
                xtype: "textarea",
                name: "description",
                value: this.data.description,
                fieldLabel: t("description"),
                width: 400,
                height: 100
            }, {
                xtype: "textfield",
                fieldLabel: t("root_folder"),
                name: "basePath",
                width: 400,
                cls: "input_drop_target",
                value: this.data.basepath,
                listeners: {
                    "render": function (el) {
                        new Ext.dd.DropZone(el.getEl(), {
                            reference: this,
                            ddGroup: "element",
                            getTargetFromEvent: function(e) {
                                return this.getEl();
                            }.bind(el),

                            onNodeOver : function(target, dd, e, data) {
                                return Ext.dd.DropZone.prototype.dropAllowed;
                            },

                            onNodeDrop : function (target, dd, e, data) {
                                var node = data.records[0] || null;

                                if(!node) return false;

                                if (node.data.elementType == "object") {
                                    this.setValue(node.data.path);
                                    return true;
                                }
                                return false;
                            }.bind(el)
                        });
                    }
                }
            }, {
                xtype: "combo",
                fieldLabel: t("allowed_classes"),
                name: "o_class",
                width: 400,
                store: pimcore.globalmanager.get("object_types_store"),
                editable: false,
                value: this.data.o_class,
                valueField: 'text',
                displayField: 'text',
                allowBlank: false,
                triggerAction: 'all',
                listeners: {
                    select: function(combo, record, index) {
                        itemContainer.removeAll();
                        // reset level combo...
//                        Ext.each(itemContainer.find("xtype","combo"), function(combo) {
//                            combo.setValue("");
//                            combo.getStore().reload();
//                        });
                    }
                }
            }, {
                xtype: "textfield",
                name: "icon",
                value: this.data.icon,
                fieldLabel: t("icon"),
                width: 400
            }, {
                xtype: "checkbox",
                name: "active",
                fieldLabel: t("active"),
                checked: this.data.active == "1"
            },  {
                xtype: "textfield",
                name: "customTreeBuilderClass",
                value: this.data.customTreeBuilderClass,
                fieldLabel: t("customTreeBuilderClass"),
                width: 400
            }
            ]
        });


        this.panel = new Ext.Panel({
            border: false,
            closable: true,
            autoScroll: true,
            bodyStyle: "padding: 20px;",
            title: this.data.name,
//            iconCls: 'pimcore_panel_tree_objects_alternate_tree_custom_' + this.data.id,
//             id: "plugin_alternate_object_trees_config_panel_" + this.data.name,
            items: [this.settings, this.itemContainer],
            buttons: panelButtons
        });


        this.parentPanel.getEditPanel().add(this.panel);
        this.parentPanel.getEditPanel().setActiveItem(this.panel);

        pimcore.layout.refresh();
    },


    addItem: function (type, data) {
        // console.log(data);
        var item = pimcore.plugin.alternateObjectTrees.config.items[type](this, data);
        this.itemContainer.add(item);
        this.itemContainer.updateLayout();

        this.currentIndex++;
    },


    save: function () {
        var levels = [];
        var items = this.itemContainer.items.getRange();

        // ...
        for (var i=0; i<items.length; i++) {
            var definition = {};
            definition['type'] = items[i].type;
            definition['config'] = items[i].getForm().getFieldValues();

            levels.push( definition );
        }

        // save
        Ext.Ajax.request({
            url: "/admin/elements-alternate-object-trees/alternate-object-tree",
            method: "PUT",
            params: {
                settings: Ext.encode(this.settings.getForm().getFieldValues()),
                levelDefinitions: Ext.encode(levels),
                id: this.data.id
            },
            success: this.saveOnComplete.bind(this)
        });
    },

    saveOnComplete: function () {
        // this.parentPanel.tree.getRootNode().reload();
        this.parentPanel.tree.getStore().load();
        pimcore.helpers.showNotification(t("success"), t("plugin_alternate_object_trees_config_saved_successfully"), "success");

        // update gui
        var label = this.settings.getForm().findField("label").getValue()
        var active = this.settings.getForm().findField("active").getValue()
        var treeId = "pimcore_panel_tree_alternate_object_tree_" + this.data.id;
        var treeList = pimcore.globalmanager.get("layout_alternateobject_tree");

        // find panel
        var panel;
        Ext.each(treeList, function(item) {
//            console.log(item);
            if(item.config.treeId == treeId)
            {
                if(!active)
                {
                    // remove panel
                    item.tree.destroy();
                    treeList.remove(item);
                }
                else
                {
                    panel = item.tree;
                }
            }
        });


        if(active)
        {
            if(!panel)
            {
                // create new panel
                var opt = {
                    id: this.data.id,
                    label: label,
                    icon: this.settings.getForm().findField("icon").getValue()
                };

                var panel = new pimcore.plugin.alternateObjectTrees.tree(opt);
                treeList.push(panel);
            }
            else
            {
                // update existing
                panel.setTitle( label );
            }
        }
    },

    getCurrentIndex: function () {
        return this.currentIndex;
    }

});
