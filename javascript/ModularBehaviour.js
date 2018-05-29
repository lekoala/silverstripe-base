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
            initClass: 'module-initialized',
            failedClass: 'module-failed'
        }, opts);

        // main function
        function init(e) {
            // prevent multiple inits
            if (e.hasClass(config.initClass)) {
                return;
            }
            e.trigger(module + '.moduleBeforeInit');
            var module = e.data(config.moduleKey);
            var moduleConfig = e.data(config.configKey);
            // Prevent undefined config
            if (!moduleConfig) {
                moduleConfig = {};
            }
            if (!$.fn[module]) {
                console.log(module + " is not defined");
                e.addClass(config.failedClass);
            } else {
                $.fn[module].call(e, moduleConfig);
            }
            e.addClass(config.initClass);
            e.trigger(module + '.moduleAfterInit');
        }

        // initialize every element
        this.each(function () {
            init($(this));
        });
        return this;
    };

    // onDomReady...
    // ! we need the "complete" event since we work with deferred scripts
    function ready(fn) {
        if (document.readyState === "complete") {
            fn();
        } else {
            document.addEventListener('DOMContentLoaded', fn);
        }
    }
    ready(function () {
        $('[data-module]').ModularBehaviour();
    });

    // after each successfull ajax request
    // TODO: determine is this is accurate enough (maybe the content of the page takes some time to update)
    $(document).ajaxComplete(function (event, xhr, settings) {
        if (xhr.status != 200) {
            return;
        }
        $('[data-module]').ModularBehaviour();
    });
})(jQuery);
