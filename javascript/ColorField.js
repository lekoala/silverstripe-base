(function ($) {
    var selector = ".spectrum input";

    $(selector).entwine({
        onadd: function () {
            if (this.hasClass("spectrum-init")) {
                return;
            }
            var opts = this.data("config");
            this.spectrum(opts);
            this.addClass("spectrum-init");
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
