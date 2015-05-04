pimcore.registerNS("pimcore.plugin.alternateObjectTrees.tree");
pimcore.plugin.alternateObjectTrees.tree = Class.create(pimcore.object.tree, {

    treeDataUrl: "/plugin/AlternateObjectTrees/admin/tree-get-childs-by-id/",

    initialize: function(config) {

        // TODO add permission translation
//        pimcore.system_i18n["plugin_alternateobjecttrees_tree_" + config.name] = "Alternate Object Tree: '" + config.name + "'";

        // ...
        this.position = "right";

        this.config = {
            rootId: 1,
            rootVisible: true,
            allowedClasses: "all",
            loaderBaseParams: {
                alternateTreeId: config.id
            },
            treeId: "pimcore_panel_tree_alternate_object_tree_" + config.id,
            treeIconCls: "pimcore_panel_tree_objects_alternate_tree",
            treeTitle: config.name, //t('pimcore_panel_tree_objects_alternate_tree'),
            parentPanel: Ext.getCmp("pimcore_panel_tree_left"),
            index: 4
        };

        // create temporary css icon
        if(config.icon)
        {
            this.config.treeIcon = config.icon;
            this.config.treeIconCls = 'pimcore_panel_tree_objects_alternate_tree_custom_' + config.id;
            Ext.util.CSS.createStyleSheet(
                '.' + this.config.treeIconCls + ' {background: url(' + config.icon + ') left center no-repeat !important;}'
            );
        }

        // update label
        if(config.label)
            this.config.treeTitle = config.label;


        pimcore.layout.treepanelmanager.register(this.config.treeId);

        var rootNode = {
            id: config.id,
            text: "",
            type: "folder",
            level: 0,
            attributeValue: null,
            isConfigNode: true
//            "elementType":"object",
//            "isTarget":false,
//            "allowDrop":false,
//            "allowChildren":false,
//            "leaf":false,
//            "iconCls":"folder_database", // pimcore_icon_folder
//            "expanded":false,
//            "permissions":{}
        };


        this.init(rootNode);
    },

    init: function(rootNodeConfig) {

        rootNodeConfig.nodeType = "async";
        rootNodeConfig.text = t("home");
        rootNodeConfig.draggable = true;
        rootNodeConfig.iconCls = "pimcore_icon_home";

        this.tree = new Ext.tree.TreePanel({
            region: "center",
            useArrows:true,
            id: this.config.treeId,
            title: this.config.treeTitle,
            iconCls: this.config.treeIconCls,
            autoScroll:true,
            animate:true,
            enableDD:true,
            ddAppendOnly: true,
            ddGroup: "element",
            containerScroll: true,
            rootVisible: this.config.rootVisible,
            border: false,
            tools: [{
                id: "right",
                handler: pimcore.layout.treepanelmanager.toRight.bind(this)
            },{
                id: "left",
                handler: pimcore.layout.treepanelmanager.toLeft.bind(this),
                hidden: true
            }],
            root: rootNodeConfig,
            plugins: new Ext.ux.tree.TreeNodeMouseoverPlugin(),
            loader: new Ext.ux.tree.PagingTreeLoader({
                dataUrl:this.treeDataUrl,
                pageSize:30,
                enableTextPaging:false,
                pagingModel:'remote',
                requestMethod: "GET",
                baseAttrs: {
                    listeners: this.getTreeNodeListeners(),
                    reference: this,
                    nodeType: "async"
                },
                listeners: {
                    beforeload: function( loader, node, callback ) {
                        var baseParams = loader.baseParams;

                        baseParams.level = node.attributes.level;
                        baseParams.attributeValue = node.attributes.attributeValue;
                        baseParams.isConfigNode = node.attributes.isConfigNode;
                        baseParams.filterValues = Ext.encode(node.attributes.filterValues);
                        loader.baseParams = baseParams;

                    }
                },
                baseParams: this.config.loaderBaseParams
            })
        });

        this.tree.on("render", function () {
            this.getRootNode().expand();
        });
        this.tree.on("startdrag", this.onDragStart.bind(this));
        this.tree.on("enddrag", this.onDragEnd.bind(this));
        this.tree.on("nodedragover", this.onTreeNodeOver.bind(this));
        this.tree.on("afterrender", function () {
            this.tree.loadMask = new Ext.LoadMask(this.tree.getEl(), {msg: t("please_wait")});
            this.tree.loadMask.enable();
        }.bind(this));

        this.config.parentPanel.insert(this.config.index, this.tree);
        this.config.parentPanel.doLayout();
    }



});

