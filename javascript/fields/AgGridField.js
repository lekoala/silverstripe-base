;
(function ($, window, document) {

    "use strict";

    // Create the defaults once
    var pluginName = "AgGridField",
        defaults = {};

    // The actual plugin constructor
    function Plugin(element, options) {
        this.element = element;

        this.settings = $.extend({}, defaults, options);
        this.gridOptions = null;
        this._defaults = defaults;
        this._name = pluginName;
        this.init();
    }

    // Define our plugin behaviour
    $.extend(Plugin.prototype, {
        init: function () {
            var self = this;
            var $el = $(this.element);

            // let the grid know which columns and what data to use
            this.gridOptions = $.extend({}, this.settings);
            // create the grid passing in the div to use together with the columns & data we want to use
            new agGrid.Grid(this.element, this.gridOptions);

            // Attach events
            var parent = $(this.element).parent();
            parent.find('.ag-add-row').on('click', function () {
                self.addRow();
            });
            parent.find('.ag-remove-selected').on('click', function () {
                self.removeSelected();
            });
        },
        log: function (text) {
            console.log(text);
        },
        getRowData: function () {
            var rowData = [];
            this.gridOptions.api.forEachNode(function (node) {
                rowData.push(node.data);
            });
            console.log('Row Data:');
            console.log(rowData);
        },
        clearData: function () {
            this.gridOptions.api.setRowData([]);
        },
        createNewRowData: function () {
            return [];
        },
        addRow: function () {
            var newItem = this.createNewRowData();
            var res = this.gridOptions.api.updateRowData({
                add: [newItem]
            });
        },
        removeSelected: function () {
            var selectedData = this.gridOptions.api.getSelectedRows();
            var res = this.gridOptions.api.updateRowData({
                remove: selectedData
            });
        }
    });

    // Register the plugin in $ namespace
    $.fn[pluginName] = function (options) {
        return this.each(function () {
            if (!$.data(this, "plugin_" + pluginName)) {
                $.data(this, "plugin_" +
                    pluginName, new Plugin(this, options));
            }
        });
    };

})(jQuery, window, document);
