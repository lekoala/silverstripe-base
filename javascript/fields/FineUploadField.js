(function ($) {
    var selector = ".field.fineupload .fineupload";

    var behaviour = {
        onadd: function () {
            // This can be called twice
            if (this.hasClass("fineupload-init")) {
                return;
            }
            opts = this.data("fineupload");
            if (!opts) {
                opts = {};
            }

            schema = this.data("schema");

            opts.element = this[0];
            opts.request = {
                endpoint: schema.data.createFileEndpoint.url
            };
            opts.deleteFile = {
                enabled: true,
                endpoint: schema.data.deleteFileEndpoint.url
            };
            opts.session = {
                endpoint: schema.data.initialFilesEndpoint.url
            };
            opts.debug = true;

            var uploader = new qq.FineUploader(opts);

            console.log(opts);

            this.addClass("fineupload-init");
        }
    };

    // If we have entwine, define the behaviour
    if ($.entwine) {
        $(selector).entwine(behaviour);
    }

    // We need to rely on this pattern because:
    // - entwine may not be loaded
    // - on first load, the "onadd" method may never be called due to load order
    $(function () {
        var list = $(selector);
        if (list.length) {
            list.each(function () {
                behaviour.onadd.call($(this));
            });
        } else {
            console.log("Selector " + selector + " did not match anything");
        }
    });
})(jQuery);
