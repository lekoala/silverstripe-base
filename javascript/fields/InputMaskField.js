/* global Inputmask */
(function ($) {
  function decimalmultiply(a, b) {
    return parseFloat((a * b).toFixed(12));
  }

  ModularBehaviour.addOptionsTransformer("inputmask", function (opts, el) {
    var $this = $(el);

    // don't process!
    if (typeof $.fn["inputmask"] === "undefined") {
      return;
    }

    // raw name is irrelevant, use data attribute
    var name = $this.data("name");
    if (name == undefined) {
      name = $this.attr("name");
    }

    if (!name) {
      return;
    }

    var val = $this.val();
    var dataformat = $this.data("dataformat");
    var isDecimal = $this.data("isDecimal");
    var config = opts;

    if (isDecimal) {
      $this.val(decimalmultiply(val, 100));
    }

    // Avoid original field being saved (but send the formatted data as a convenience)
    var formattedName = name + "Formatted";
    // Support array notation
    if (name.indexOf("[") !== -1) {
      formattedName = name + "[Formatted]";
    }
    $this.attr("name", formattedName);

    // Duplicate input field to store data value
    var hiddenInput = $("<input/>", {
      type: "hidden",
      name: name,
    });
    $this.parent().append(hiddenInput);

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
  });

  ModularBehaviour.addAfterInitHook("inputmask", function (inst, el, opts) {
    var $this = $(el);
    // Trigger blur to compute value
    $this.trigger("blur");
  });
})(jQuery);
