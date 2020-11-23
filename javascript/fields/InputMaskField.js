/* global Inputmask */
(function ($) {
  if (typeof $.fn.ModularBehaviourHooks === "undefined") {
    $.fn.ModularBehaviourHooks = {
      beforeHooks: {},
      afterHooks: {},
    };
  }

  $.fn.ModularBehaviourHooks.beforeHooks.inputmask = function (config) {
    var $this = $(this);

    // raw name is irrelevant, use data attribute
    var name = $this.data("name");
    if (name == undefined) {
      name = $this.attr("name");
    }
    var val = $this.val();
    var dataformat = $this.data("dataformat");
    var isDecimal = $this.data("isDecimal");
    var config = $this.data("config");

    // Duplicate input field to store data value
    var hiddenInput = $("<input/>", {
      type: "hidden",
      name: name,
    });
    $this.parent().append(hiddenInput);

    // Avoid original field being saved (but send the formatted data as a convenience)
    var formattedName = name + "Formatted";
    // Support array notation
    if (name.indexOf("[") !== -1) {
      formattedName = name + "[Formatted]";
    }
    $this.attr("name", formattedName);

    // Update real hidden field with unmasked value
    $this.on("keyup blur", function () {
      var val = $this.inputmask("unmaskedvalue");
      // Apply a given formatting
      if (dataformat) {
        // Keep the masked input in case you want it
        if (dataformat == "masked") {
          val = $this.val();
        } else {
          val = Inputmask.format(val, {
            alias: dataformat,
          });
        }
      }
      // Decimal %
      if (isDecimal) {
        val = val / 100;
      }
      // Otherwise unmasked value is not using proper decimal separator
      if (config && config.radixPoint === ",") {
        val = val.replace(",", ".");
      }
      hiddenInput.val(val);
    });
    $.fn.ModularBehaviourHooks.afterHooks.inputmask = function (config) {
      // Trigger blur to compute value
      $(this).trigger("blur");
    };
  };
})(jQuery);
