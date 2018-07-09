;
(function ($, window, document) {

    "use strict";

    // Create the defaults once
    var pluginName = "RequiredFields",
        defaults = {
            propertyName: "value"
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
            var $el = $(this.element);
            $el.on('submit', function (e) {
                var hasErrors = false;

                $(this).find('.required').each(function () {
                    var $holder = $(this);

                    if ($holder.hasClass('optionset')) {
                        if ($holder.find(':checked').length == 0) {
                            hasErrors = self.error($holder);
                        } else {
                            self.valid($holder);
                        }
                    } else {
                        if ($holder.find('input').val() == '') {
                            hasErrors = self.error($holder);
                        } else {
                            self.valid($holder);
                        }
                    }
                });

                if (hasErrors) {
                    e.preventDefault();

                    var $elementWithErrors = $el.find('.error').first();
                    $('html, body').animate({
                        scrollTop: $elementWithErrors.offset().top - 100
                    }, 500);
                }
            });
        },
        log: function (text) {
            console.log(text);
        },
        error: function ($el) {
            $el.addClass('error');
            return true;
        },
        valid: function ($el) {
            $el.removeClass('error');
            return true;
        }
    });

    // Register the plugin in $ namespace
    $.fn[pluginName] = function (options) {
        return this.each(function () {
            if (!$.data(this, "plugin_" + pluginName)) {
                $.data(this, "plugin_" +
                    pluginName, new Plugin(this, options));
            }
        });
    };

})(jQuery, window, document);
