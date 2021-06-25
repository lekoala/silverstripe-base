/* global confirmDatePlugin, ModularBehaviour */
(function ($) {
  ModularBehaviour.addOptionsTransformer("flatpickr", function (opts, el) {
    var $this = $(el);
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
    opts.plugins = plugins;

    // Hooks
    if ($this.data("hooks")) {
      var hooks = $this.data("hooks");
      for (var hookName in hooks) {
        opts[hookName] = window[hooks[hookName]];
      }
    }
  });

  ModularBehaviour.addAfterInitHook("flatpickr", function (inst, el, opts) {
    var $this = $(el);
    var $alt = $this.parent().find(".flatpickr-alt");
    var orgVal = $this.val();

    $alt.on("change", function () {
      var val = $(this).val();
      // without this, alternative input won't be cleared
      if (!val) {
        $this.val("");
      }
    });
    $alt.on("blur", function () {
      var val = $(this).val();
    });

    $this.on("change", function () {
      var val = $(this).val();
      if (val && val != orgVal) {
        $this.parents("form").addClass("changed");
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
  });
})(jQuery);
