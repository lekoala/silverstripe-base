(function($) {
  var selector = "div.inputmask input.inputmask";

  $(selector).entwine({
    onadd: function() {
      if (this.hasClass("inputmask-init")) {
        return;
      }
      var name = this.attr("name");
      var val = this.val();

      // Duplicate input field to store data value
      var hiddenInput = $("<input/>", {
        type: "hidden",
        name: name,
        value: val
      });
      this.parent().append(hiddenInput);

      // Avoid original field being saved (but send the formatted data as a convenience)
      this.attr("name", this.attr("name") + "Formatted");

      this.inputmask();
      this.addClass("inputmask-init");
    },
    onchange: function() {
      this.updateValue();
    },
    unformattedValue: function() {
      var format = this.data("inputmask-dataformat");
      var val = this.inputmask("unmaskedvalue");
      if (format) {
        val = Inputmask.format(val, { alias: format });
      }
      return val;
    },
    updateValue: function() {
      this.parent()
        .find("input[type=hidden]")
        .val(this.unformattedValue());
    }
  });

  // We need to rely on this pattern otherwise on first load "onadd" is never called
  $(function() {
    var list = $(selector);
    if (list.length) {
      list.each(function() {
        $(this).onadd();
      });
    } else {
      console.log("Selector " + selector + " did not match anything");
    }
  });
})(jQuery);
