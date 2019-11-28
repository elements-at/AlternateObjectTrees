/**
 * Elements.at
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
                container.updateLayout();
                tmpContainer.updateLayout();

                // move the element to the right position
                tmpContainer.remove(blockElement,false);
                container.insert(newIndex, blockElement);
                container.updateLayout();
                tmpContainer.updateLayout();

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
                container.updateLayout();
                tmpContainer.updateLayout();

                // move the element to the right position
                tmpContainer.remove(blockElement,false);
                container.insert(index+1, blockElement);
                container.updateLayout();
                tmpContainer.updateLayout();

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
            layout: "form",
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
                        autoLoad: true,
                        // baseParams: {
                        //     type: "input"
                        // },
                        proxy: {
                            type: 'ajax',
                            url: "/admin/elements-alternate-object-trees/get-valid-fields",
                            fields: ["name", "title"],
                            reader: {
                                type: 'json',
                                rootProperty: "name"
                            },
                            extraParams: {
                                type: 'input',
                                name: panel.settings.getForm().findField("o_class").getValue()
                            }
                        }
                    }),
                    editable: false,
                    value: data.fieldname,
                    valueField: 'name',
                    queryMode: 'remote',
                    displayField: 'title',
                    allowBlank: false,
                    triggerAction: 'all',
                    listeners: {
                        select: function(combo, record, index) {
                            // console.log( panel.settings.getForm().findField("o_class").getValue() );
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
            layout: "form",
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
                        autoLoad:true,
                        proxy: {
                            url: "/admin/elements-alternate-object-trees/get-valid-fields",
                            type: 'ajax',
                            fields: ["name", "title"],
                            reader: {
                                type: 'json',
                                rootProperty: "name"
                            },
                            extraParams: {
                                type: 'relations',
                                name: panel.settings.getForm().findField("o_class").getValue()
                            }
                        }
                    }),
                    queryMode: 'remote',
                    editable: false,
                    value: data.fieldname,
                    valueField: 'name',
                    displayField: 'title',
                    allowBlank: false,
                    triggerAction: 'all',
                    listeners: {
                        select: function(combo, record, index) {
                            // console.log( panel.settings.getForm().findField("o_class").getValue() );
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
