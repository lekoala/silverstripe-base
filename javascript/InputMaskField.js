(function($) {
  // We need to target precisely to avoid entwine triggering again when Inputmask is initialized
  $("div.inputmask input.inputmask").entwine({
    onadd: function() {
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
    },
    onchange: function() {
      this.updateValue();
    },
    unformattedValue: function() {
      var format = this.data("inputmask-dataformat");
      var val = this.inputmask('unmaskedvalue');
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
})(jQuery);
