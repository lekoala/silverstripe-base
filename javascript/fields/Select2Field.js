(function ($) {
    var selector = "div.select2 select";

    $(selector).entwine({
        onadd: function () {
            if (this.hasClass("select2-init")) {
                return;
            }
            var opts = this.data("config");
            opts.width = "100%";
            opts.createTag = function (params) {
                var term = $.trim(params.term);

                if (term === "") {
                    return null;
                }

                // Disallow small tags
                if (term.length <= 2) {
                    return null;
                }

                // Disallow numeric tags (which can be confused with IDs)
                if (!isNaN(term - parseFloat(term))) {
                    return null;
                }

                return {
                    id: term,
                    text: term
                };
            };
            this.select2(opts);
            this.addClass("select2-init");
        }
    });

    // We need to rely on this pattern otherwise on first load "onadd" is never called
    $(function () {
        var list = $(selector);
        if (list.length) {
            list.each(function () {
                $(this).onadd();
            });
        } else {
            console.log("Selector " + selector + " did not match anything");
        }
    });
})(jQuery);
