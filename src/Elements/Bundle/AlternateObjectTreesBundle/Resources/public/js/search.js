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

pimcore.registerNS("pimcore.plugin.alternateObjectTrees.search");
pimcore.plugin.alternateObjectTrees.search = Class.create(pimcore.object.search, {
    createGrid: function (fromConfig, response, settings, save) {
        var itemsPerPage = pimcore.helpers.grid.getDefaultPageSize(-1);

        var fields = [];

        if (response.responseText) {
            response = Ext.decode(response.responseText);

            if (response.pageSize) {
                itemsPerPage = response.pageSize;
            }

            fields = response.availableFields;
            this.gridLanguage = response.language;
            this.gridPageSize = response.pageSize;
            this.sortinfo = response.sortinfo;

            this.settings = response.settings || {};
            this.availableConfigs = response.availableConfigs;
            this.sharedConfigs = response.sharedConfigs;

            if (response.onlyDirectChildren) {
                this.onlyDirectChildren = response.onlyDirectChildren;
            }
        } else {
            itemsPerPage = this.gridPageSize;
            fields = response;
            this.settings = settings;
            this.buildColumnConfigMenu();
        }

        this.fieldObject = {};
        for (var i = 0; i < fields.length; i++) {
            this.fieldObject[fields[i].key] = fields[i];
        }

        this.cellEditing = Ext.create('Ext.grid.plugin.CellEditing', {
                clicksToEdit: 1,
                listeners: {
                    beforeedit: function (editor, context, eOpts) {
                        //need to clear cached editors of cell-editing editor in order to
                        //enable different editors per row
                        var editors = editor.editors;
                        editors.each(function (editor) {
                            if (typeof editor.column.config.getEditor !== "undefined") {
                                Ext.destroy(editor);
                                editors.remove(editor);
                            }
                        });
                    }
                }
            }
        );

        var plugins = [this.cellEditing, 'pimcore.gridfilters'];

        // get current class
        var classStore = pimcore.globalmanager.get("object_types_store");
        var klass = classStore.getById(this.classId);

        var gridHelper = new pimcore.object.helpers.grid(
            klass.data.text,
            fields,
            "/admin/elements-alternate-object-trees/grid-proxy?alternateTreeId=" + this.object.data.general.treeId + "&level="+this.object.data.general.level+"&attributeValue="+this.object.data.general.attributeValue,
            {
                language: this.gridLanguage,
                // limit: itemsPerPage
            },
            false
        );

        gridHelper.showSubtype = false;
        gridHelper.enableEditor = true;
        gridHelper.limit = itemsPerPage;


        var propertyVisibility = klass.get("propertyVisibility");

        var existingFilters;
        if (this.store) {
            existingFilters = this.store.getFilters();
        }

        this.store = gridHelper.getStore(this.noBatchColumns, this.batchAppendColumns);
        if (this.sortinfo) {
            this.store.sort(this.sortinfo.field, this.sortinfo.direction);
        }
        this.store.getProxy().setExtraParam("only_direct_children", this.onlyDirectChildren);
        this.store.setPageSize(itemsPerPage);
        if (existingFilters) {
            this.store.setFilters(existingFilters.items);
        }

        var gridColumns = gridHelper.getGridColumns();

        // add filters
        this.gridfilters = gridHelper.getGridFilters();

        this.searchQuery = function(field) {
            this.store.getProxy().setExtraParam("query", field.getValue());
            this.pagingtoolbar.moveFirst();
        }.bind(this);

        this.searchField = new Ext.form.TextField(
            {
                name: "query",
                width: 200,
                hideLabel: true,
                enableKeyEvents: true,
                triggers: {
                    search: {
                        weight: 1,
                        cls: 'x-form-search-trigger',
                        scope: 'this',
                        handler: function(field, trigger, e) {
                            this.searchQuery(field);
                        }.bind(this)
                    }
                },
                listeners: {
                    "keydown" : function (field, key) {
                        if (key.getKey() == key.ENTER) {
                            this.searchQuery(field);
                        }
                    }.bind(this)
                }
            }
        );

        this.languageInfo = new Ext.Toolbar.TextItem({
            text: t("grid_current_language") + ": " + (this.gridLanguage == "default" ? t("default") : pimcore.available_languages[this.gridLanguage])
        });

        this.toolbarFilterInfo = new Ext.Button({
            iconCls: "pimcore_icon_filter_condition",
            hidden: true,
            text: '<b>' + t("filter_active") + '</b>',
            tooltip: t("filter_condition"),
            handler: function (button) {
                Ext.MessageBox.alert(t("filter_condition"), button.pimcore_filter_condition);
            }.bind(this)
        });

        this.clearFilterButton = new Ext.Button({
            iconCls: "pimcore_icon_clear_filters",
            hidden: true,
            text: t("clear_filters"),
            tooltip: t("clear_filters"),
            handler: function (button) {
                this.grid.filters.clearFilters();
                this.toolbarFilterInfo.hide();
                this.clearFilterButton.hide();
            }.bind(this)
        });


        this.createSqlEditor();

        this.checkboxOnlyDirectChildren = new Ext.form.Checkbox({
            name: "onlyDirectChildren",
            style: "margin-bottom: 5px; margin-left: 5px",
            checked: this.onlyDirectChildren,
            boxLabel: t("only_children"),
            listeners: {
                "change": function (field, checked) {
                    this.grid.getStore().setRemoteFilter(false);
                    this.grid.filters.clearFilters();

                    this.store.getProxy().setExtraParam("only_direct_children", checked);

                    this.onlyDirectChildren = checked;
                    this.pagingtoolbar.moveFirst();

                    this.grid.getStore().setRemoteFilter(true);
                }.bind(this)
            }
        });

        var hideSaveColumnConfig = !fromConfig || save;

        this.saveColumnConfigButton = new Ext.Button({
            tooltip: t('save_grid_options'),
            iconCls: "pimcore_icon_publish",
            hidden: hideSaveColumnConfig,
            handler: function () {
                var asCopy = !(this.settings.gridConfigId > 0);
                this.saveConfig(asCopy)
            }.bind(this)
        });

        this.columnConfigButton = new Ext.SplitButton({
            text: t('grid_options'),
            iconCls: "pimcore_icon_table_col pimcore_icon_overlay_edit",
            handler: function () {
                this.openColumnConfig(true);
            }.bind(this),
            menu: []
        });

        this.buildColumnConfigMenu();

        // grid
        this.grid = Ext.create('Ext.grid.Panel', {
            frame: false,
            store: this.store,
            columns: gridColumns,
            columnLines: true,
            stripeRows: true,
            bodyCls: "pimcore_editable_grid",
            border: true,
            selModel: gridHelper.getSelectionColumn(),
            trackMouseOver: true,
            loadMask: true,
            plugins: plugins,
            viewConfig: {
                forceFit: false,
                xtype: 'patchedgridview'
            },
            listeners: {
                celldblclick: function(grid, td, cellIndex, record, tr, rowIndex, e, eOpts) {
                    var columnName = grid.ownerGrid.getColumns();
                    if(columnName[cellIndex].dataIndex == 'id' || columnName[cellIndex].dataIndex == 'fullpath') {
                        var data = this.store.getAt(rowIndex);
                        pimcore.helpers.openObject(data.get("id"), data.get("type"));
                    }
                }
            },
            cls: 'pimcore_object_grid_panel',
            tbar: [this.searchField, "-", this.languageInfo, "-", this.toolbarFilterInfo, this.clearFilterButton, "->", this.checkboxOnlyDirectChildren, "-", this.sqlEditor, this.sqlButton, "-", {
                text: t("search_and_move"),
                iconCls: "pimcore_icon_search pimcore_icon_overlay_go",
                handler: pimcore.helpers.searchAndMove.bind(this, this.object.id,
                    function () {
                        this.store.reload();
                    }.bind(this), "object")
            }, "-", {
                text: t("export_csv"),
                iconCls: "pimcore_icon_export",
                handler: function () {
                    pimcore.helpers.csvExportWarning(function(settings) {
                        this.exportPrepare(settings);
                    }.bind(this));
                }.bind(this)
            }, "-",
                this.columnConfigButton,
                this.saveColumnConfigButton
            ]
        });

        this.grid.on("columnmove", function () {
            this.saveColumnConfigButton.show()
        }.bind(this));
        this.grid.on("columnresize", function () {
            this.saveColumnConfigButton.show()
        }.bind(this));

        this.grid.on("rowcontextmenu", this.onRowContextmenu);

        this.grid.on("afterrender", function (grid) {
            this.updateGridHeaderContextMenu(grid);
        }.bind(this));

        this.grid.on("sortchange", function (ct, column, direction, eOpts) {
            this.sortinfo = {
                field: column.dataIndex,
                direction: direction
            };
        }.bind(this));

        // check for filter updates
        this.grid.on("filterchange", function () {
            this.filterUpdateFunction(this.grid, this.toolbarFilterInfo, this.clearFilterButton);
        }.bind(this));

        gridHelper.applyGridEvents(this.grid);

        this.pagingtoolbar = pimcore.helpers.grid.buildDefaultPagingToolbar(this.store, {pageSize: itemsPerPage});

        this.editor = new Ext.Panel({
            layout: "border",
            items: [new Ext.Panel({
                items: [this.grid],
                region: "center",
                layout: "fit",
                bbar: this.pagingtoolbar
            })]
        });

        this.layout.removeAll();
        this.layout.add(this.editor);
        this.layout.updateLayout();

        if (save) {
            if (this.settings.isShared) {
                this.settings.gridConfigId = null;
            }
            this.saveConfig(false);
        }
    },

    openColumnConfig: function(allowPreview) {
        var fields = this.getGridConfig().columns;

        var fieldKeys = Object.keys(fields);

        var visibleColumns = [];
        for(var i = 0; i < fieldKeys.length; i++) {
            var field = fields[fieldKeys[i]];
            if(!field.hidden) {
                var fc = {
                    key: fieldKeys[i],
                    label: field.fieldConfig.label,
                    dataType: field.fieldConfig.type,
                    layout: field.fieldConfig.layout
                };
                if (field.fieldConfig.width) {
                    fc.width = field.fieldConfig.width;
                }

                if (field.isOperator) {
                    fc.isOperator = true;
                    fc.attributes = field.fieldConfig.attributes;

                }

                visibleColumns.push(fc);
            }
        }

        var objectId;
        if(this["object"] && this.object["id"]) {
            objectId = this.object.id;
        } else if (this["element"] && this.element["id"]) {
            objectId = this.element.id;
        }

        var columnConfig = {
            language: this.gridLanguage,
            pageSize: this.gridPageSize,
            classid: this.classId,
            objectId: objectId,
            selectedGridColumns: visibleColumns,
            treeId: this.object.data.general.treeId,
            level: this.object.data.general.level,
            attributeValue: this.object.data.general.attributeValue
        };
        var dialog = new pimcore.plugin.alternateObjectTrees.gridConfigDialog(columnConfig, function(data, settings, save) {
                this.gridLanguage = data.language;
                this.gridPageSize = data.pageSize;
                this.createGrid(true, data.columns, settings, save);
            }.bind(this),
            function() {
                Ext.Ajax.request({
                    url: "/admin/object-helper/grid-get-column-config",
                    params: {
                        id: this.classId,
                        objectId: objectId,
                        gridtype: "grid",
                        searchType: this.searchType
                    },
                    success: function(response) {
                        response = Ext.decode(response.responseText);
                        if (response) {
                            fields = response.availableFields;
                            this.createGrid(false, fields, response.settings, false);
                            if (typeof this.saveColumnConfigButton !== "undefined") {
                                this.saveColumnConfigButton.hide();
                            }
                        } else {
                            pimcore.helpers.showNotification(t("error"), t("error_resetting_config"),
                                "error",t(rdata.message));
                        }
                    }.bind(this),
                    failure: function () {
                        pimcore.helpers.showNotification(t("error"), t("error_resetting_config"), "error");
                    }
                });
            }.bind(this),
            true,
            this.settings,
            {
                allowPreview: true,
                classId: this.classId,
                objectId: objectId
            }
        )
    },

    saveConfig: function (asCopy) {
        this.getSaveAsDialog();
    },

    getSaveAsDialog: function () {
        var defaultName = new Date();

        var nameField = new Ext.form.TextField({
            fieldLabel: t('name'),
            length: 50,
            allowBlank: false,
            value: this.settings.gridConfigName ? this.settings.gridConfigName : defaultName
        });

        var descriptionField = new Ext.form.TextArea({
            fieldLabel: t('description'),
            // height: 200,
            value: this.settings.gridConfigDescription
        });

        var configPanel = new Ext.Panel({
            layout: "form",
            bodyStyle: "padding: 10px;",
            items: [nameField, descriptionField],
            buttons: [{
                text: t("save"),
                iconCls: "pimcore_icon_apply",
                handler: function () {
                    this.settings.gridConfigId = null;
                    this.settings.gridConfigName = nameField.getValue();
                    this.settings.gridConfigDescription = descriptionField.getValue();

                    this.saveColumnConfig(this.getGridConfig(), this.searchType, this.saveColumnConfigButton,
                        this.columnConfigurationSavedHandler.bind(this), this.settings);
                    this.saveWindow.close();
                }.bind(this)
            }]
        });

        this.saveWindow = new Ext.Window({
            width: 600,
            height: 300,
            modal: true,
            title: t('save_as_copy'),
            layout: "fit",
            items: [configPanel]
        });

        this.saveWindow.show();
        nameField.focus();
        nameField.selectText();
        return this.window;
    },

    getTableDescription: function () {
        Ext.Ajax.request({
            url: "/admin/elements-alternate-object-trees/grid-get-column-config",
            params: {
                id: this.object.id,
                alternateTreeId: this.object.data.general.treeId,
                gridtype: "grid",
                gridConfigId: this.settings ? this.settings.gridConfigId : null,
                searchType: this.searchType
            },
            success: this.createGrid.bind(this, false)
        });
    },

    saveColumnConfig: function (configuration, searchType, button, callback, settings) {
        try {
            var data = {
                classId: this.object.id,
                gridconfig: Ext.encode(configuration),
                searchType: searchType,
                settings: Ext.encode(settings)
            };

            Ext.Ajax.request({
                url: '/admin/elements-alternate-object-trees/grid-save-column-config',
                method: "post",
                params: data,
                success: function (response) {
                    try {
                        var rdata = Ext.decode(response.responseText);
                        if (rdata && rdata.success) {
                            if (button) {
                                button.hide();
                            }
                            if (typeof callback == "function") {
                                callback(rdata);
                            }
                            pimcore.helpers.showNotification(t("success"), t("saved_successfully"), "success");
                        }
                        else {
                            pimcore.helpers.showNotification(t("error"), t("saving_failed"),
                                "error", t(rdata.message));
                        }
                    } catch (e) {
                        pimcore.helpers.showNotification(t("error"), t("saving_failed"), "error");
                    }
                }.bind(this),
                failure: function () {
                    pimcore.helpers.showNotification(t("error"), t("saving_failed"), "error");
                }
            });

        } catch (e3) {
            pimcore.helpers.showNotification(t("error"), t("saving_failed"), "error");
        }
    },

    exportPrepare: function(settings){
        var jobs = [];

        var filters = "";
        var condition = "";
        var searchQuery = this.searchField.getValue();

        if(this.sqlButton.pressed) {
            condition = this.sqlEditor.getValue();
        } else {
            var filterData = this.store.getFilters().items;
            if(filterData.length > 0) {
                filters = this.store.getProxy().encodeFilters(filterData);
            }
        }


        var fields = this.getGridConfig().columns;
        var fieldKeys = Object.keys(fields);

        //create the ids array which contains chosen rows to export
        ids = [];
        var selectedRows = this.grid.getSelectionModel().getSelection();
        for (var i = 0; i < selectedRows.length; i++) {
            ids.push(selectedRows[i].data.id);
        }

        settings = Ext.encode(settings);

        var params = {
            filter: filters,
            condition: condition,
            classId: this.classId,
            folderId: this.element.id,
            objecttype: this.objecttype,
            language: this.gridLanguage,
            "ids[]": ids,
            "fields[]": fieldKeys,
            settings: settings,
            query: searchQuery,
            batch: true, // to avoid limit for export
            alternateTreeId: this.object.data.general.treeId,
            level: this.object.data.general.level,
            attributeValue: this.object.data.general.attributeValue
        };


        Ext.Ajax.request({
            url: "/admin/elements-alternate-object-trees/get-export-jobs",
            params: params,
            success: function (response) {
                var rdata = Ext.decode(response.responseText);

                if (rdata.success && rdata.jobs) {
                    this.exportProcess(rdata.jobs, rdata.fileHandle, fieldKeys, true, settings);
                }

            }.bind(this)
        });
    },
});
