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
            var isJqueryModule = false;
            var module = e.data(config.moduleKey);
            var moduleConfig = e.data(config.configKey);

            if (typeof $.fn[module] !== 'undefined') {
                isJqueryModule = true;
            }

            if (isJqueryModule) {
                e.trigger('moduleBeforeInit');
            } else {
                e[0].dispatchEvent('moduleBeforeInit');
            }

            // Prevent undefined config
            if (!moduleConfig) {
                moduleConfig = {};
            }

            // apply = array of args
            // call = comma separated list of args
            // here, we pass as the first argument a config object
            if ($.fn[module]) {
                // It's a jquery module
                $.fn[module].call(e, moduleConfig);
            } else if (typeof window[module] !== "undefined") {
                // It's a global var
                window[module].call('#' + e.attr('id'), moduleConfig);
            } else {
                console.log(module + " is not defined");
                e.addClass(config.failedClass);
            }

            e.addClass(config.initClass);

            if (isJqueryModule) {
                e.trigger('moduleAfterInit');
            } else {
                e[0].dispatchEvent('moduleAfterInit');
            }
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
