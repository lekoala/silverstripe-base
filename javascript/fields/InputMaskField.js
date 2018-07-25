/* global Inputmask */
(function ($) {
    $.fn.ModularBehaviour.beforeHooks.inputmask = function (config) {
        var $this = $(this);

        var name = $this.attr("name");
        var val = $this.val();
        var dataformat = $this.data("dataformat");
        var isDecimal = $this.data("isDecimal");

        // Duplicate input field to store data value
        var hiddenInput = $("<input/>", {
            type: "hidden",
            name: name
        });
        $this.parent().append(hiddenInput);

        // Avoid original field being saved (but send the formatted data as a convenience)
        $this.attr("name", $this.attr("name") + "Formatted");

        // Update real hidden field with unmasked value
        $this.on('keyup blur', function () {
            var val = null;
            // Apply a given formatting
            if (dataformat) {
                val = Inputmask.format(val, {
                    alias: dataformat
                });
            } else {
                val = $this.inputmask("unmaskedvalue");
            }
            // Decimal %
            if (isDecimal) {
                val = val / 100;
            }
            // Otherwise unmasked value is not using proper decimal separator
            if (config.radixPoint && config.radixPoint === ',') {
                val = val.replace(',', '.');
            }
            hiddenInput.val(val);
        });
        $.fn.ModularBehaviour.afterHooks.inputmask = function (config) {
            // Trigger blur to compute value
            $(this).trigger('blur');
        }
    };
})(jQuery);
