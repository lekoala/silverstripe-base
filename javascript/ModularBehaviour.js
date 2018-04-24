/**
 * Simply bind jquery plugins to dom nodes
 *
 * Specify data-module="myJqueryPlugin" and pass config
 * options in data-config=""
 *
 * Plugins are rebound on ajax load automatically
 */
(function ($) {
    $.fn.ModularBehaviour = function (opts) {
        // default configuration
        var config = $.extend({}, {
            moduleKey: 'module',
            configKey: 'config',
            initClass: 'module-initialized'
        }, opts);

        // main function
        function init(e) {
            if (e.hasClass(config.initClass)) {
                return;
            }
            var module = e.data(config.moduleKey);
            var moduleConfig = e.data(config.configKey);
            if (!$.fn[module]) {
                console.log(module + " is not defined");
            } else {
                $.fn[module].apply(e, [moduleConfig]);
            }
            e.addClass(config.initClass);
        }
        // initialize every element
        this.each(function () {
            init($(this));
        });
        return this;
    };

    //
    $(function () {
        $('[data-module]').ModularBehaviour();
    });

    $(document).ajaxComplete(function (event, xhr, settings) {
        if (xhr.status != 200) {
            return;
        }
        $('[data-module]').ModularBehaviour();
    });
})(jQuery);
