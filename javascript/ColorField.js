(function ($) {
    var selector = "div.field.spectrum input";

    $(selector).entwine({
        onmatch: function () {
            if (this.hasClass("spectrum-init")) {
                return;
            }
            var opts = this.data("config");
            this.spectrum(opts);
            this.addClass("spectrum-init");
        }
    });
})(jQuery);
