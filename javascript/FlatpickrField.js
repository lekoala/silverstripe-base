(function($) {
  $("div.flatpickr input.flatpickr").entwine({
    onadd: function() {
      opts = this.data("flatpickr");
      if (!opts) {
        opts = {};
      }

      flatpickr("#" + this.attr("id"), opts);
    }
  });
})(jQuery);
