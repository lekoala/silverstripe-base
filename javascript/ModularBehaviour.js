/**
 * Simply bind jquery plugins to dom nodes
 *
 * Specify data-module="myJqueryPlugin" and pass config
 * options in data-config=""
 *
 * data-config can also store reference to a node with the
 * config as json data
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

        var timeout;
        var retries = 0;

        // main function
        function init(e) {
            // prevent multiple inits
            if (e.hasClass(config.initClass)) {
                return;
            }
            var isJqueryModule = false;
            var initFailed = false;
            var module = e.data(config.moduleKey);
            var moduleConfig = e.data(config.configKey);

            // External config selector
            if (typeof moduleConfig === 'string' && moduleConfig.charAt(0) === '#') {
                moduleConfig = $.parseJSON($(moduleConfig).text());
            }

            if (typeof $.fn[module] !== 'undefined') {
                isJqueryModule = true;
            }

            // Prevent undefined config
            if (!moduleConfig) {
                moduleConfig = {};
            }

            // Dispatch beforeHooks
            if (typeof $.fn.ModularBehaviour.beforeHooks[module] !== 'undefined') {
                $.fn.ModularBehaviour.beforeHooks[module].call(e, moduleConfig);
            }

            // apply = array of args
            // call = comma separated list of args
            // here, we pass as the first argument a config object
            if (isJqueryModule) {
                // It's a jquery module
                $.fn[module].call(e, moduleConfig);
            } else if (typeof window[module] !== "undefined") {
                // It's a global var
                window[module].call('#' + e.attr('id'), moduleConfig);
            } else {
                // console.log(module + " is not defined");
                e.addClass(config.failedClass);
                initFailed = true;
            }

            if (typeof $.fn.ModularBehaviour.afterHooks[module] !== 'undefined') {
                $.fn.ModularBehaviour.afterHooks[module].call(e, moduleConfig);
            }

            // If init failed, we may need to try again later (ajax requirements can be delayed...)
            if (!initFailed) {
                e.addClass(config.initClass);
                e.removeClass(config.failedClass);
            } else {
                // This is a bit of a hack
                if (timeout) {
                    clearTimeout(timeout);
                }
                retries++;
                if (retries > 4) {
                    timeout = setTimeout(function () {
                        $('[data-module]').ModularBehaviour();
                    }, 250);
                }
            }
        }

        // initialize every element
        this.each(function () {
            init($(this));
        });
        return this;
    };

    // Define hooks
    $.fn.ModularBehaviour.beforeHooks = {};
    $.fn.ModularBehaviour.afterHooks = {};

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

    // after each successfull ajax request, try to init modules again after requirements are loaded
    var decodePath = function (str) {
        return str.replace(/%2C/g, ',').replace(/\&amp;/g, '&').replace(/^\s+|\s+$/g, '');
    };
    $(document).ajaxSuccess(function (event, xhr, settings) { // eslint-disable-line no-unused-vars
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
        // Always try to init everything
        retries = 0;
        $('[data-module]').ModularBehaviour();
    });
})(jQuery);
