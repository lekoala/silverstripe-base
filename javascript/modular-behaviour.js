/**
 * ModularBehaviour.js
 * Bind jQuery plugins (optional) or vanilla functions to your dom (recommended)
 *
 * https://github.com/lekoala/modular-behaviour.js
 * MIT License
 */
(function (global) {
  "use strict";

  // Just in case we used AMD
  if (typeof jQuery === "undefined" && typeof require !== "undefined" && require.defined && require.defined("jquery")) {
    jQuery = require("jquery");
  }

  var document = global.document;

  var config = {
    attr: "data-mb",
    failedClass: "mb-failed",
    initClass: "mb-init",
    optionsKey: "options",
    maxTries: 3,
    retryInterval: 250,
    observeDom: true,
    runAfterDomChanges: true,
    debug: false,
  };

  var globalRetries = {};

  // Track new scripts
  // This helps to limit failed attempts due to yet to be loaded scripts
  var scriptsLoading = 0;
  // Set this to true if you want to trigger run after scripts are loaded
  var shouldRun = false;
  /**
   * @param {HTMLScriptElement} script
   */
  function trackScript(script, increment) {
    if (script.hasAttribute("async")) {
      return;
    }
    if (script.hasAttribute("nomodule")) {
      return;
    }
    debug("tracking " + script.getAttribute("href"));
    scriptsLoading++;

    var prevOnload = script.onload;
    script.onload = function (e) {
      if (prevOnload) {
        prevOnload(e);
      }
      scriptsLoading--;
      debug(scriptsLoading + " remaining scripts");
      if (scriptsLoading <= 0) {
        scriptsLoading = 0;
        debug("all scripts loaded");

        // A run attempt has been prevented due to missing scripts
        if (shouldRun) {
          ModularBehaviour.run();
        }
      }
    };
  }

  /**
   * Instantiate objects dynamically
   *
   * @param {Object} constructor Object reference
   * @param {Array} args Array of arguments to pass to the constructor
   * @param {string} method An optional factory to use instead of Object.create
   * @return {Object} The instantiated object
   */
  function instantiate(constructor, args, method) {
    var instance;
    if (method) {
      instance = constructor[method].apply(document, args);
    } else {
      // Available since IE>=9
      instance = Object.create(constructor.prototype);
      // The function will receive the node instance and an array of options
      constructor.apply(instance, args);
    }
    return instance;
  }

  /**
   * Execute code when the dom is fully loaded but before any images or css is loaded
   * @param {Function} fn
   */
  function ready(fn) {
    if (document.readyState === "complete" || (document.readyState !== "loading" && !document.documentElement.doScroll)) {
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
    domObserver: null,
    initialized: false,
    /**
     * This is simply a wrapper of run inside a dom ready function
     * @param {Object} newConfig
     */
    init: function (newConfig) {
      if (this.initialized) {
        return;
      }
      var self = this;
      if (newConfig) {
        for (var newConfigKey in newConfig) {
          this.setConfig(newConfigKey, newConfig[newConfigKey]);
        }
      }
      if (scriptsLoading) {
        debug(scriptsLoading + " scripts loading");
      }
      ready(function () {
        self.run();
        if (config.observeDom) {
          self.startObserver();
        }
      });
      this.initialized = true;
    },
    /**
     * Watches the dom for new nodes after run
     */
    startObserver: function () {
      var self = this;
      var domTimer = null;
      if (self.domObserver) {
        return;
      }
      // Warning : browser extensions can trigger external mutations
      self.domObserver = new MutationObserver(function (mutations) {
        for (var i = 0; i < mutations.length; i++) {
          var mutation = mutations[i];
          // Somehow this creates a loop
          if (mutation.target.classList.contains("search-form__wrapper")) {
            return;
          }
          for (var j = 0; j < mutation.addedNodes.length; j++) {
            var node = mutation.addedNodes[j];
            // Don't bother with nodes without a tag name or not connected to the dom
            // NOTE: IE 11 does not understand isConnected => check === false
            if (!node.tagName || node.isConnected === false) {
              return;
            }
            // Track new scripts. If new scripts are added, we will run through all nodes
            if (node.tagName.toLowerCase() === "script") {
              trackScript(node);
              shouldRun = true;
            }
            // Check if our node or it's children has our attribute and configure if necessary
            if (!shouldRun) {
              // Configure element if it's a single node
              if (node.hasAttribute(config.attr)) {
                self.configureElement(node);
              }
              // Also run on all children since we might not get all dom changes
              if (config.runAfterDomChanges) {
                if (domTimer) {
                  clearTimeout(domTimer);
                }
                domTimer = setTimeout(function () {
                  self.run();
                }, 100);
              }
            }
          }
        }
      });
      // start observing on body or element
      var container = document.documentElement || document.body;
      self.domObserver.observe(container, {
        subtree: true, // We watch the whole body, not only direct children
        attributes: false,
        childList: true,
        characterData: false,
      });
      debug("Observing dom");
    },
    stopObserver: function () {
      if (this.domObserver) {
        this.domObserver.disconnect();
      }
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
     * Traverse the dom and configure any element with data-mb attribute
     */
    run: function () {
      var self = this;
      if (scriptsLoading) {
        debug("should run");
        shouldRun = true;
        // Make sure we are not stuck due to a failed script
        setTimeout(function () {
          if (scriptsLoading) {
            scriptsLoading = 0;
            self.run();
            debug("run anyway");
          }
        }, 500);
        return;
      }
      // Collect a static NodeList (need to query again after each ajax call or dom update)
      var nodeList = document.querySelectorAll("[" + config.attr + "]:not(." + config.initClass + ")");
      debug("run : " + nodeList.length + " modules to configure");
      for (var i = 0; i < nodeList.length; i++) {
        this.configureElement(nodeList[i]);
      }
      shouldRun = false;
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
      if (!elementConfig || elementConfig === "undefined") {
        return {};
      }
      // Deal with string configurations, it may very well be json objects already
      if (typeof elementConfig === "string") {
        // It can be base64 encode
        if (elementConfig.indexOf("base64:") === 0) {
          elementConfig = atob(elementConfig.substring(7));
        }

        if (elementConfig.charAt(0) === "#") {
          // External config in an html node
          var externalConfigNode = document.getElementById(elementConfig.substring(1));
          // Support template nodes
          if (externalConfigNode && externalConfigNode.tagName.toLowerCase() === "template") {
            externalConfigNode = externalConfigNode.content;
          }
          var externalConfig = externalConfigNode && externalConfigNode.textContent;
          if (externalConfig) {
            elementConfig = JSON.parse(externalConfig);
          } else {
            debug("Config not found with selector " + elementConfig);
          }
        } else if (elementConfig.slice(-2) === "()") {
          // Config is provided through a js function
          var callbackConfig = elementConfig.substring(0, elementConfig.length - 2);
          if (callbackConfig in global) {
            elementConfig = global[callbackConfig]();
          } else {
            debug("Config callback not found with name " + callbackConfig);
          }
        } else if (elementConfig.charAt(0) === "{") {
          elementConfig = JSON.parse(elementConfig);
          if (!elementConfig) {
            debug("Invalid config. Make sure it's properly JSON encoded.");
          }
        } else {
          debug("Weird config detected", elementConfig);
        }
      }
      return elementConfig;
    },
    /**
     * Configure an element with a given module and a set of options
     * @param {HTMLElement} element
     * @param {string} module
     * @param {Object} options
     */
    configureModule: function (element, module, options) {
      var inst;
      var self = this;
      var namespace = global;
      var moduleName = module;
      var factoryMethod = null;

      // It is namespaced
      if (module.indexOf(".") !== -1) {
        var parts = module.split(".");
        namespace = global[parts[0]];
        moduleName = parts[1];
      }
      // Uses factory
      if (moduleName.indexOf(":") !== -1) {
        var nameParts = moduleName.split(":");
        moduleName = nameParts[0];
        factoryMethod = nameParts[1];
      }

      // Determine our type of plugin/module
      try {
        if (namespace && typeof namespace[moduleName] !== "undefined") {
          // It's a global object, expecting the "new" keyword to be used
          // if you don't want to use the "new" keyword, consider wrapping the function in a function
          // You can use a factory method using : notation
          debug("Configuring js module " + module);
          inst = instantiate(namespace[moduleName], [element, options], factoryMethod);
        } else if (typeof jQuery !== "undefined" && typeof jQuery.fn[moduleName] !== "undefined") {
          // It's a jQuery module
          debug("Configuring jQuery module " + module);
          // Wrap element and call the plugin with the config
          inst = jQuery.fn[moduleName].call(jQuery(element), options);
        } else {
          // Not defined
          debug("Undefined module " + module);
          element.classList.add(config.failedClass);
        }
      } catch (error) {
        element.parentNode.innerText = error;
        element.classList.add(config.initClass);
        inst = null;
        globalRetries[element.getAttribute("id")] = config.maxTries;
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
        var retries = globalRetries[element.getAttribute("id")] || 0;
        if (retries < config.maxTries) {
          retries++;
          globalRetries[element.getAttribute("id")] = retries;

          global.setTimeout(function () {
            debug("try again for " + module);
            self.configureModule(element, module, options);
          }, config.retryInterval);
        }
      }
    },
    /**
     * Configure a given dom element. An element can have multiple modules.
     * @param {HTMLElement} element
     */
    configureElement: function (element) {
      // Already initialized
      if (element.classList.contains(config.initClass)) {
        debug(element.getAttribute("id") + " is already initialized");
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
        var options = element.getAttribute(config.attr + "-" + module + "-" + config.optionsKey);
        // Fallback to simple notation : data-mb-options
        if (!options) {
          options = element.getAttribute(config.attr + "-" + config.optionsKey);
        }
        options = this.parseOptions(options);
        // Apply transformer if any
        if (module in this.optionsTransformers) {
          debug("Using option transformer for " + module);
          var newOptions = this.optionsTransformers[module](options, element);
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
})(this);
