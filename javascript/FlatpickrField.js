(function ($) {
    var selector = "div.flatpickr input.flatpickr";

    $(selector).entwine({
        onmatch: function () {
            if (this.hasClass("flatpickr-init")) {
                return;
            }
            opts = this.data("flatpickr");
            if (!opts) {
                opts = {};
            }

            flatpickr("#" + this.attr("id"), opts);
            this.addClass("flatpickr-init");
        }
    });
})(jQuery);
