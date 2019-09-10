pimcore.registerNS("pimcore.plugin.alternateObjectTrees.folder");
pimcore.plugin.alternateObjectTrees.folder = Class.create(pimcore.object.folder, {
    initialize: function(treeId, level, attributeValue) {
        this.treeId = treeId;
        this.level = level;
        this.attributeValue = attributeValue;

        this.id = treeId+'-'+level+'-'+attributeValue;

        this.getData();
    },

    init: function () {
        this.search = new pimcore.plugin.alternateObjectTrees.search(this);
    },

    getLayoutToolbar : function () {
        if (!this.toolbar) {
            var buttons = [];
            buttons.push({
                tooltip: t('reload'),
                iconCls: "pimcore_material_icon_reload pimcore_material_icon",
                scale: "medium",
                handler: this.reload.bind(this)
            });

            this.toolbar = new Ext.Toolbar({
                id: "object_toolbar_" + this.id,
                region: "north",
                border: false,
                cls: "pimcore_main_toolbar",
                items: buttons,
                overflowHandler: 'scroller'
            });
        }

        return this.toolbar;
    },

    getTabPanel: function () {
        var items = [];

        var search = this.search.getLayout();
        if (search) {
            items.push(search);
        }

        this.tabbar = new Ext.TabPanel({
            tabPosition: "top",
            region:'center',
            deferredRender:true,
            enableTabScroll:true,
            border: false,
            items: items,
            activeTab: 0
        });

        return this.tabbar;
    },

    reload: function () {
        this.tab.on("close", function() {
            window.setTimeout(function (data) {
                if (pimcore.globalmanager.exists("tree_" + data.id) == false) {
                    pimcore.globalmanager.add("tree_" + data.id, new pimcore.plugin.alternateObjectTrees.folder(data.treeId, data.level, data.attributeValue));
                    pimcore.helpers.rememberOpenTab("tree_" + data.id);
                }
                else {
                    var tab = pimcore.globalmanager.get("tree_" + data.id);
                    tab.activate();
                }
            }.bind(window, this), 500);
        }.bind(this));

        pimcore.helpers.closeObject(this.id);
        pimcore.globalmanager.remove("tree_" + this.id);
    },

    getData: function () {
        var options = this.options || {};
        Ext.Ajax.request({
            url: "/admin/elements-alternate-object-trees/admin/grid-get-data",
            params: {alternateTreeId: this.treeId, level: this.level, attributeValue: this.attributeValue},
            ignoreErrors: options.ignoreNotFoundError,
            success: this.getDataComplete.bind(this),
            failure: function() {
                this.forgetOpenTab();
            }.bind(this)
        });
    },
});