
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
            layout: "pimcoreform",
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
                                if (data.node.attributes.elementType == "object") {
                                    this.setValue(data.node.attributes.path);
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
            id: "plugin_alternate_object_trees_config_panel_" + this.data.name,
            items: [this.settings, this.itemContainer],
            buttons: panelButtons
        });


        this.parentPanel.getEditPanel().add(this.panel);
        this.parentPanel.getEditPanel().activate(this.panel);

        pimcore.layout.refresh();
    },


    addItem: function (type, data) {
        console.log(data);
        var item = pimcore.plugin.alternateObjectTrees.config.items[type](this, data);
        this.itemContainer.add(item);
        this.itemContainer.doLayout();

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
            url: "/plugin/AlternateObjectTrees/admin/save-alternate-object-tree",
            method: "post",
            params: {
                settings: Ext.encode(this.settings.getForm().getFieldValues()),
                levelDefinitions: Ext.encode(levels),
                id: this.data.id
            },
            success: this.saveOnComplete.bind(this)
        });
    },

    saveOnComplete: function () {
        this.parentPanel.tree.getRootNode().reload();
        pimcore.helpers.showNotification(t("success"), t("plugin_alternate_object_trees_config_saved_successfully"), "success");

        // update gui
        var label = this.settings.find("name","label")[0].getValue()
        var active = this.settings.find("name","active")[0].getValue()
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
                    icon: this.settings.find("name","icon")[0].getValue()
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


/** ITEM TYPES **/

pimcore.registerNS("pimcore.plugin.alternateObjectTrees.config.items");

pimcore.plugin.alternateObjectTrees.config.items = {

    detectBlockIndex: function (blockElement, container) {
        // detect index
        var index;

        for(var s=0; s<container.items.items.length; s++) {
            if(container.items.items[s].getId() == blockElement.getId()) {
                index = s;
                break;
            }
        }
        return index;
    },

    getTopBar: function (name, index, parent) {
        return [{
            xtype: "tbtext",
            text: "<b>" + name + "</b>"
        },"-",{
            iconCls: "pimcore_icon_up",
            handler: function (blockId, parent) {

                var container = parent.itemContainer;
                var blockElement = Ext.getCmp(blockId);
                var index = pimcore.plugin.alternateObjectTrees.config.items.detectBlockIndex(blockElement, container);
                var tmpContainer = pimcore.viewport;

                var newIndex = index-1;
                if(newIndex < 0) {
                    newIndex = 0;
                }

                // move this node temorary to an other so ext recognizes a change
                container.remove(blockElement, false);
                tmpContainer.add(blockElement);
                container.doLayout();
                tmpContainer.doLayout();

                // move the element to the right position
                tmpContainer.remove(blockElement,false);
                container.insert(newIndex, blockElement);
                container.doLayout();
                tmpContainer.doLayout();

                pimcore.layout.refresh();
            }.bind(window, index, parent)
        },{
            iconCls: "pimcore_icon_down",
            handler: function (blockId, parent) {

                var container = parent.itemContainer;
                var blockElement = Ext.getCmp(blockId);
                var index = pimcore.settings.thumbnail.items.detectBlockIndex(blockElement, container);
                var tmpContainer = pimcore.viewport;

                // move this node temorary to an other so ext recognizes a change
                container.remove(blockElement, false);
                tmpContainer.add(blockElement);
                container.doLayout();
                tmpContainer.doLayout();

                // move the element to the right position
                tmpContainer.remove(blockElement,false);
                container.insert(index+1, blockElement);
                container.doLayout();
                tmpContainer.doLayout();

                pimcore.layout.refresh();
            }.bind(window, index, parent)
        },"->",{
            iconCls: "pimcore_icon_delete",
            handler: function (index, parent) {
                parent.itemContainer.remove(Ext.getCmp(index));
            }.bind(window, index, parent)
        }];
    },

    /**
     * handle input type
     * @param panel
     * @param data
     * @param getName
     * @returns {*}
     */
    itemInput: function (panel, data, getName) {

        var niceName = t("plugin_alternate_object_trees_input");
        if(typeof getName != "undefined" && getName) {
            return niceName;
        }

        if(typeof data == "undefined") {
            data = {};
        }
        var myId = Ext.id();

        var item =  new Ext.form.FormPanel({
            layout: "pimcoreform",
            id: myId,
            type: "input",
            style: "margin: 10px 0 0 0",
            bodyStyle: "padding: 10px;",
            tbar: this.getTopBar(niceName, myId, panel),
            items: [
                {
                    xtype: "combo",
                    fieldLabel: t("plugin_alternate_object_trees_field"),
                    name: "fieldname",
                    width: 400,
                    store: new Ext.data.JsonStore ({
                        // store configs
                        autoDestroy: true,
                        url: "/plugin/AlternateObjectTrees/admin/get-valid-fields",
                        baseParams: {
                            type: "input"
                        },
                        listeners: {
                            beforeload: function(store, options) {
                                options.params.name = panel.settings.find('name', 'o_class')[0].getValue()
                            }
                        },
                        // reader configs
                        idProperty: "name",
                        fields: ["name", "title"]
                    }),
                    editable: false,
                    value: data.fieldname,
                    valueField: 'name',
                    displayField: 'title',
                    allowBlank: false,
                    triggerAction: 'all',
                    listeners: {
                        select: function(combo, record, index) {
                            console.log( panel.settings.find('name', 'o_class')[0].getValue() );
                        }
                    }
                }, {
                    xtype: "textarea",
                    fieldLabel: t("plugin_alternate_object_trees_condition"),
                    name: "condition",
                    width: 400,
                    value: data.condition
                }, {
                    xtype: 'textfield',
                    name: "label",
                    fieldLabel: t("plugin_alternate_object_trees_label"),
                    width: 400,
                    value: data.label
                }
            ]
        });

        return item;
    },

    /**
     * handle relation type
     * @param panel
     * @param data
     * @param getName
     * @returns {*}
     */
    itemRelations: function (panel, data, getName) {

        var niceName = t("plugin_alternate_object_trees_relations");
        if(typeof getName != "undefined" && getName) {
            return niceName;
        }

        if(typeof data == "undefined") {
            data = {};
        }
        var myId = Ext.id();

        var item =  new Ext.form.FormPanel({
            layout: "pimcoreform",
            id: myId,
            type: "relations",
            style: "margin: 10px 0 0 0",
            bodyStyle: "padding: 10px;",
            tbar: this.getTopBar(niceName, myId, panel),
            items: [
                {
                    xtype: "combo",
                    fieldLabel: t("plugin_alternate_object_trees_field"),
                    name: "fieldname",
                    width: 400,
                    store: new Ext.data.JsonStore ({
                        // store configs
                        autoDestroy: true,
                        url: "/plugin/AlternateObjectTrees/admin/get-valid-fields",
                        baseParams: {
                            type: "relations"
                        },
                        listeners: {
                            beforeload: function(store, options) {
                                options.params.name = panel.settings.find('name', 'o_class')[0].getValue()
                            }
                        },
                        // reader configs
                        idProperty: "name",
                        fields: ["name", "title"]
                    }),
                    editable: false,
                    value: data.fieldname,
                    valueField: 'name',
                    displayField: 'title',
                    allowBlank: false,
                    triggerAction: 'all',
                    listeners: {
                        select: function(combo, record, index) {
                            console.log( panel.settings.find('name', 'o_class')[0].getValue() );
                        }
                    }
                }, {
                    xtype: "textarea",
                    fieldLabel: t("plugin_alternate_object_trees_condition"),
                    name: "condition",
                    width: 400,
                    value: data.condition
                }, {
                    xtype: 'textfield',
                    name: "label",
                    fieldLabel: t("plugin_alternate_object_trees_label"),
                    width: 400,
                    value: data.label
                },{
                    xtype: "checkbox",
                    name: "showEmpty",
                    checked: data.showEmpty,
                    fieldLabel: t("plugin_alternate_object_trees_showEmpty")
                }
            ]
        });

        return item;
    }
};

