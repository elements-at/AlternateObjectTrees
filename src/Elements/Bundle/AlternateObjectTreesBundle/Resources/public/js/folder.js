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
        var user = pimcore.globalmanager.get("user");

        this.search = new pimcore.plugin.alternateObjectTrees.search(this);

        if (this.isAllowed("properties")) {
            this.properties = new pimcore.element.properties(this, "object");
        }

        if (user.isAllowed("notes_events")) {
            this.notes = new pimcore.element.notes(this, "object");
        }

        this.dependencies = new pimcore.element.dependencies(this, "object");
        this.tagAssignment = new pimcore.element.tag.assignment(this, "object");
        this.workflows = new pimcore.element.workflows(this, "object");
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