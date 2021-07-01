/**
 * ModularBehaviour.js
 * Bind jQuery plugins or vanilla functions to your dom
 *
 * https://github.com/lekoala/modular-behaviour.js
 * MIT License
 */
(function (global, $) {
    "use strict";

    // Just in case we used AMD
    if (!$ && require && require.defined && require.defined("jquery")) {
        $ = require("jquery");
    }

    var document = global.document;
    var config = {
        attr: "data-mb",
        failedClass: "mb-failed",
        initClass: "mb-init",
        optionsKey: "options",
        maxTries: 5,
        retryInterval: 250,
        debug: true,
    };

    /**
     * Instantiate objects dynamically
     *
     * @param {Object} constructor Object reference
     * @param {Array} args Array of arguments to pass to the constructor
     * @return {Object} The instantiated object
     */
    function instantiate(constructor, args) {
        // Available since IE>=9
        var instance = Object.create(constructor.prototype);
        // The function will receive the node instance and an array of options
        constructor.apply(instance, args);
        return instance;
    }

    /**
     * Execute code when the dom is fully loaded but before any images or css is loaded
     * @param {Function} fn
     */
    function ready(fn) {
        if (
            document.readyState === "complete" ||
            (document.readyState !== "loading" &&
                !document.documentElement.doScroll)
        ) {
            fn();
        } else {
            document.addEventListener("DOMContentLoaded", fn);
        }
    }

    /**
     * Debug message
     * @param {string} msg
     */
    function debug(msg) {
        if (config.debug) {
            console.log("[mb] " + msg);
        }
    }

    var getNewIdCounter = 0;
    /**
     * @return {string}
     */
    function getNewId() {
        getNewIdCounter++;
        return "ModularBehaviour" + getNewIdCounter.toString();
    }

    var ModularBehaviour = {
        optionsTransformers: {},
        afterInitHooks: {},
        /**
         * This is simply a wrapper of run inside a dom ready function
         * Any requirements should be loaded before this is called
         * @param {Object} newConfig
         */
        init: function (newConfig) {
            var self = this;
            if (newConfig) {
                for (var newConfigKey in newConfig) {
                    this.setConfig(newConfig[newConfigKey]);
                }
            }
            ready(function () {
                self.run();
            });
        },
        /**
         * Get a global config key
         * @param {string} k
         */
        getConfig: function (k) {
            return config[k];
        },
        /**
         * Set a global config value
         * @param {string} k
         * @param {*} v
         */
        setConfig: function (k, v) {
            config[k] = v;
        },
        /**
         * This can be used instad of init to run on page load
         * This will make sure that requirements can be loaded after load() is called
         */
        load: function () {
            var self = this;
            global.addEventListener("load", function () {
                self.run();
            });
        },
        /**
         * Traverse the dom and configure any element with data-mb attribute
         */
        run: function () {
            debug("run");
            // Collect a static NodeList (need to query again after each ajax call or dom update)
            var nodeList = document.querySelectorAll("[" + config.attr + "]");
            for (var i = 0; i < nodeList.length; i++) {
                this.configureElement(nodeList[i]);
            }
        },
        /**
         * Add a callback to apply on a module instance after init
         * @param {string} id
         * @param {Function} fn A callback that accepts the instance, the element and the options object
         */
        addAfterInitHook: function (id, fn) {
            this.afterInitHooks[id] = fn;
        },
        /**
         * Remove an after init hook
         * @param {string} id
         */
        removeAfterInitHook: function (id) {
            delete this.afterInitHooks[id];
        },
        /**
         * Add a callback to apply on a given module's options
         * @param {string} id
         * @param {Function} fn A callback that accepts the options object
         */
        addOptionsTransformer: function (id, fn) {
            this.optionsTransformers[id] = fn;
        },
        /**
         * Remove an option transformer
         * @param {string} id
         */
        removeOptionsTransformer: function (id) {
            delete this.optionsTransformers[id];
        },
        /**
         * Parse and validate the options
         * @param {string|Object} elementConfig
         * @return {Object}
         */
        parseOptions: function (elementConfig) {
            if (!elementConfig || elementConfig === 'undefined') {
                return {};
            }
            // Deal with string configurations, it may very well be json objects already
            if (typeof elementConfig === "string") {
                if (elementConfig.charAt(0) === "#") {
                    // External config in an html node
                    var externalConfigNode = document.getElementById(
                        elementConfig
                    );
                    var externalConfig = externalConfigNode.textContent;
                    if (externalConfig) {
                        elementConfig = JSON.parse(externalConfig);
                    } else {
                        debug("Config not found with id " + elementConfig);
                    }
                } else if (elementConfig.slice(-2) === "()") {
                    // Config is provided through a js function
                    var callbackConfig = elementConfig.substring(
                        0,
                        elementConfig.length - 2
                    );
                    if (callbackConfig in global) {
                        elementConfig = global[callbackConfig]();
                    } else {
                        debug(
                            "Config callback not found with name " +
                                callbackConfig
                        );
                    }
                } else if (elementConfig.charAt(0) === "{") {
                    elementConfig = JSON.parse(elementConfig);
                    if(!elementConfig) {
                        debug(
                            "Invalid config. Make sure it's properly JSON encoded."
                        );
                    }
                } else {
                    debug("Weird config detected", elementConfig);
                }
            }
            return elementConfig;
        },
        /**
         * Configure an element with a given module and a set of options
         * @param {Element} element
         * @param {string} module
         * @param {Object} options
         */
        configureModule: function (element, module, options) {
            var inst;
            var self = this;
            var namespace;

            // It is namespaced
            if (module.indexOf(".") !== -1) {
                var parts = module.split(".");
                namespace = parts[0];
            }

            // Determine our type of plugin/module
            if (namespace && typeof global[namespace] !== "undefined") {
                // It's a namespaced global object
                debug("Configuring namespaced js module " + module);
                inst = instantiate(global[namespace][parts[1]], [
                    "#" + element.getAttribute("id"),
                    options,
                ]);
            } else if (!namespace && typeof global[module] !== "undefined") {
                // It's a global object, expecting the "new" keyword to be used
                // if you don't want to use the "new" keyword, consider wrapping the function in a jQuery plugin
                debug("Configuring js module " + module + " on #" + element.getAttribute("id"));
                inst = instantiate(global[module], [
                    "#" + element.getAttribute("id"),
                    options,
                ]);
            } else if ($ && typeof $.fn[module] !== "undefined") {
                // It's a jQuery module
                debug("Configuring jQuery module " + module);
                // Wrap element and call the plugin with the config
                inst = $.fn[module].call($(element), options);
            } else {
                // Not defined
                debug("Undefined module " + module);
                element.classList.add(config.failedClass);
            }

            if (inst) {
                element.classList.remove(config.failedClass);
                element.classList.add(config.initClass);

                // Apply after init hook if any
                if (module in this.afterInitHooks) {
                    this.afterInitHooks[module](inst, element, options);
                }

                debug(module + " initialized");
            } else {
                // This is a bit of a hack, libs might not be loaded yet (after ajax load for instance)
                var retries = element.getAttribute(config.attr + "-retries");
                if (!retries) {
                    retries = 0;
                }
                if (retries < config.maxTries) {
                    retries++;
                    element.setAttribute(config.attr + "-retries", retries);

                    global.setTimeout(function () {
                        debug("try again for " + module);
                        self.configureModule(element, module, options);
                    }, config.retryInterval);
                }
            }
        },
        /**
         * Configure a given dom element. An element can have multiple modules.
         * @param {Element} element
         */
        configureElement: function (element) {
            // Already initialized
            if (element.classList.contains(config.initClass)) {
                debug("already initialized");
                return;
            }

            // Some plugins need an id to work
            if (!element.getAttribute("id")) {
                debug("an id has been set for an element");
                element.setAttribute("id", getNewId());
            }

            var modules = element.getAttribute(config.attr).split(" ");
            for (var i = 0; i < modules.length; i++) {
                var module = modules[i];
                // Full option notation : data-mb-moduleName-options
                var options = element.getAttribute(
                    config.attr + "-" + module + "-" + config.optionsKey
                );
                // Fallback to simple notation : data-mb-options
                if (!options) {
                    options = element.getAttribute(
                        config.attr + "-" + config.optionsKey
                    );
                }
                options = this.parseOptions(options);
                // Apply transformer if any
                if (module in this.optionsTransformers) {
                    var newOptions = this.optionsTransformers[module](
                        options,
                        element
                    );
                    if (newOptions) {
                        options = newOptions;
                    }
                }
                this.configureModule(element, module, options);
            }
        },
    };

    // AMD support
    if (typeof define === "function" && define.amd) {
        define(function () {
            return ModularBehaviour;
        });
    } else if (typeof exports !== "undefined") {
        // Support Node.js specific 'module.exports' (which can be a function)
        if (typeof module !== "undefined" && module.exports) {
            exports = module.exports = ModularBehaviour;
        }
        // But always support CommonJS module 1.1.1 spec ('exports' cannot be a function)
        exports.ModularBehaviour = ModularBehaviour;
    } else {
        global.ModularBehaviour = ModularBehaviour;
    }
})(this, jQuery);
