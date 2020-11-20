// The plugin for JsRequiredFields
(function ($, window, document) {
  "use strict";

  // Create the defaults once
  var pluginName = "RequiredFields",
    defaults = {
      propertyName: "value",
    };

  // The actual plugin constructor
  function Plugin(element, options) {
    this.element = element;

    this.settings = $.extend({}, defaults, options);
    this._defaults = defaults;
    this._name = pluginName;
    this.init();
  }

  // Define our plugin behaviour
  $.extend(Plugin.prototype, {
    init: function () {
      var self = this;

      // our form
      var $el = $(this.element);

      // Transform required attr to .required
      $el.find("[required]").each(function () {
        var $holder = $(this).parents(".field");
        $(this).removeAttr("required");
        $holder.addClass("required");
        $(this).on("blur", function () {
          if ($(this).val()) {
            self.valid($holder);
          }
        });
      });

      // Simple conditional validation
      $el.find("[data-show-if]").each(function () {
        var tagName = $(this)[0].tagName.toLowerCase();
        var $holder = $(this);
        if (tagName == "input" || tagName == "select" || tagName == "textarea") {
          $holder = $(this).parents(".field");
        }
        var expr = $(this).data("show-if");
        var parts = expr.split("=");
        var $target = $(this)
          .parents("form")
          .find("input[name=" + parts[0] + "]");
        var val = parts[1];

        $holder.hide();
        $target
          .on("change", function () {
            var type = $(this).attr("type");
            if (type == "radio" || type == "checkbox") {
              if (!$(this).is(":checked")) {
                return;
              }
            }
            if ($(this).val() == val) {
              $holder.show();
            } else {
              $holder.hide();
            }
          })
          .trigger("change");
      });

      // Validation on submit
      $el.on("submit", function (e) {
        var hasErrors = false;

        $(this)
          .find(".required")
          .each(function () {
            var $holder = $(this);

            if (!$holder.is(":visible")) {
              self.valid($holder);
              return;
            }

            if ($holder.hasClass("optionset")) {
              if ($holder.find(":checked").length == 0) {
                hasErrors = self.error($holder);
              } else {
                self.valid($holder);
              }
            } else {
              if ($holder.find("input").val() == "") {
                hasErrors = self.error($holder);
              } else {
                self.valid($holder);
              }
            }
          });

        if (hasErrors) {
          e.preventDefault();

          var $elementWithErrors = $el.find(".error").first();
          $("html, body").animate(
            {
              scrollTop: $elementWithErrors.offset().top - 100,
            },
            500
          );
        }
      });
    },
    log: function (text) {
      console.log(text);
    },
    error: function ($el) {
      $el.addClass("error");
      return true;
    },
    valid: function ($el) {
      $el.removeClass("error");
      return true;
    },
  });

  // Register the plugin in $ namespace
  $.fn[pluginName] = function (options) {
    return this.each(function () {
      if (!$.data(this, "plugin_" + pluginName)) {
        $.data(this, "plugin_" + pluginName, new Plugin(this, options));
      }
    });
  };
})(jQuery, window, document);
