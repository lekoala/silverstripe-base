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

            // Dispatch beforeInit event
            // Pass the config along to define custom behaviour (moduleConfig is mutable)
            if (isJqueryModule) {
                e.trigger('moduleBeforeInit', [moduleConfig]);
            } else {
                var event = new CustomEvent('moduleBeforeInit', {
                    'detail': moduleConfig
                });
                e[0].dispatchEvent(event);
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
            } else {}
        }

        // initialize every element
        this.each(function () {
            init($(this));
        });
        return this;
    };

    // onDomReady...
    // we need the "complete" event since we may work with deferred scripts
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

    var pending = 0;
    var timeout;

    // after each successfull ajax request
    var decodePath = function (str) {
        return str.replace(/%2C/g, ',').replace(/\&amp;/g, '&').replace(/^\s+|\s+$/g, '');
    };
    $(document).ajaxSuccess(function (event, xhr, settings) {
        // Check if jquery ondemand will trigger script loading
        var newJsIncludes = [];
        if (xhr.getResponseHeader && xhr.getResponseHeader('X-Include-JS') && $.isItemLoaded) {
            var jsIncludes = xhr.getResponseHeader('X-Include-JS').split(',');
            for (var i = 0; i < jsIncludes.length; i++) {
                var jsIncludePath = decodePath(jsIncludes[i]);
                if (!$.isItemLoaded(jsIncludePath)) {
                    newJsIncludes.push(jsIncludePath);
                }
            }
        }
        // Only fire if no new js include
        if (!newJsIncludes.length) {
            $('[data-module]').ModularBehaviour();
        }
    });
})(jQuery);
