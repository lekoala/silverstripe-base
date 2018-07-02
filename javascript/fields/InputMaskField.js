/* global Inputmask */
(function ($) {
    $.fn.ModularBehaviour.beforeHooks.inputmask = function (config) {
        var $this = $(this);

        var name = $this.attr("name");
        var val = $this.val();
        var format = $this.data("dataformat");

        // Duplicate input field to store data value
        var hiddenInput = $("<input/>", {
            type: "hidden",
            name: name,
            value: val
        });
        $this.parent().append(hiddenInput);

        // Avoid original field being saved (but send the formatted data as a convenience)
        $this.attr("name", $this.attr("name") + "Formatted");

        // Update real hidden field with unmasked value
        $this.on('keyup blur', function () {
            var val = null;
            if (format) {
                val = Inputmask.format(val, {
                    alias: format
                });
            } else {
                val = $this.inputmask("unmaskedvalue");
            }
            // Otherwise unmasked value is not using proper decimal separator
            if (config.radixPoint && config.radixPoint === ',') {
                val = val.replace(',', '.');
            }
            hiddenInput.val(val);
        });

    };
})(jQuery);
