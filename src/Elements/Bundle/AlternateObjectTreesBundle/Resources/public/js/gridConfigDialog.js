pimcore.registerNS("pimcore.plugin.alternateObjectTrees.gridConfigDialog");
pimcore.plugin.alternateObjectTrees.gridConfigDialog = Class.create(pimcore.object.helpers.gridConfigDialog, {
    requestPreview: function () {
        var language = this.languageField.getValue();
        var fields = this.data.columns;
        var count = fields.length;
        var i;
        var keys = [];
        for (i = 0; i < count; i++) {
            var item = fields[i];
            keys.push(item.key);
        }

        Ext.Ajax.request({
            url: "/admin/elements-alternate-object-trees/admin/grid-proxy?alternateTreeId=" + this.config.treeId + "&level="+this.config.level+"&attributeValue="+this.config.attributeValue,
            method: 'POST',
            params: {
                "fields[]": keys,
                language: language,
                limit: 1
            },
            success: function (response) {
                var responseData = Ext.decode(response.responseText);
                if (responseData && responseData.data && responseData.data.length == 1) {
                    var rootNode = this.selectionPanel.getRootNode()
                    var childNodes = rootNode.childNodes;
                    var previewItem = responseData.data[0];
                    var store = this.selectionPanel.getStore()
                    var i;
                    var count = childNodes.length;

                    for (i = 0; i < count; i++) {
                        var node = childNodes[i];
                        var nodeId = node.id;
                        var column = this.data.columns[i];

                        var columnKey = column.key;
                        var value = previewItem[columnKey];

                        var record = store.getById(nodeId);
                        record.set("preview", value, {
                            commit: true
                        });
                    }
                }

            }.bind(this)
        });
    },
});