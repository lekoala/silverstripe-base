/* global confirmDatePlugin */
(function ($) {
  if (typeof $.fn.ModularBehaviourHooks === "undefined") {
    $.fn.ModularBehaviourHooks = {
      beforeHooks: {},
      afterHooks: {},
    };
  }

  $.fn.ModularBehaviourHooks.beforeHooks.flatpickr = function (config) {
    var $this = $(this);
    var plugins = [];
    if ($this.data("range")) {
      // Range plugin is not quite there yet
      // @link https://github.com/flatpickr/flatpickr/issues/1208
      /*plugins.push(new rangePlugin({
                'input': $this.data('range')
            }));*/
      var $other = $($this.data("range"));
      $other.data("rangeStart", "#" + $this.attr("id"));
    }
    if ($this.data("confirmDate")) {
      plugins.push(new confirmDatePlugin());
    }
    config.plugins = plugins;
  };
  $.fn.ModularBehaviourHooks.afterHooks.flatpickr = function () {
    var $this = $(this);
    var $alt = $this.parent().find(".flatpickr-alt");
    $alt.on("change", function () {
      var val = $alt.val();
      // without this, alternative input won't be cleared
      if (!val) {
        $this.val('');
      }
    });

    if ($this.data("rangeStart")) {
      var $other = $($this.data("rangeStart"));
      var isUpdating = false;
      $this
        .on("change", function () {
          if (isUpdating) {
            return;
          }
          isUpdating = true;
          var fp = document.querySelector("#" + $other.attr("id"))._flatpickr;
          fp.set("maxDate", $this.val());
          isUpdating = false;
        })
        .trigger("change");
      $other
        .on("change", function () {
          if (isUpdating) {
            return;
          }
          isUpdating = true;
          var fp = document.querySelector("#" + $this.attr("id"))._flatpickr;
          fp.set("minDate", $other.val());
          isUpdating = false;
        })
        .trigger("change");
    }
  };
})(jQuery);
