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
  if (typeof $.fn.ModularBehaviourHooks === "undefined") {
    $.fn.ModularBehaviourHooks = {
      beforeHooks: {},
      afterHooks: {},
    };
  }

  $.fn.ModularBehaviour = function (opts) {
    // default configuration
    var config = $.extend(
      {},
      {
        moduleKey: "module",
        configKey: "config",
        initClass: "module-initialized",
        failedClass: "module-failed",
      },
      opts
    );

    var timeout;
    var retries = 0;

    /**
     * Instantiate objects dynamically
     *
     * @param object constructor Object reference
     * @param array args Array of arguments to pass to the constructor
     * @return object The instantiated object
     */
    function instantiate(constructor, args) {
      // Available since IE>=9
      var instance = Object.create(constructor.prototype);
      constructor.apply(instance, args);
      return instance;
    }

    /**
     * @return string
     */
    function getNewModuleId() {
      var counter = 0;
      counter++;
      return "ModularBehaviour" + counter.toString();
    }

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
      var noConfig = false;
      var windowRef = window[module];
      var jqueryRef = $.fn[module];
      var inst = null;
      var moduleId = e.attr("id");

      // we need an id!
      if (!moduleId) {
        moduleId = getNewModuleId();
        e.attr("id", moduleId);
      }

      // Config parsing
      if (typeof moduleConfig === "string") {
        // External config
        if (moduleConfig.charAt(0) === "#") {
          moduleConfig = $.parseJSON($(moduleConfig).text());
        } else if (moduleConfig.charAt(0) === "{") {
          console.log(
            "Invalid config. Make sure it's properly JSON encoded.",
            moduleConfig
          );
        } else {
          console.log("Weird config detected", moduleConfig);
        }
      }

      if (typeof jqueryRef !== "undefined") {
        isJqueryModule = true;
      }

      // Prevent undefined config
      if (!moduleConfig) {
        moduleConfig = {};
        noConfig = true;
      }

      // Dispatch beforeHooks
      if (
        typeof $.fn.ModularBehaviourHooks.beforeHooks[module] !== "undefined"
      ) {
        $.fn.ModularBehaviourHooks.beforeHooks[module].call(e, moduleConfig);
      }

      // apply = array of args
      // call = comma separated list of args
      // here, we pass as the first argument a config object
      if (isJqueryModule) {
        // console.log("Initialise jq module " + module);
        // It's a jquery module
        inst = jqueryRef.call(e, moduleConfig);
      } else if (typeof windowRef !== "undefined") {
        // console.log("Initialise module " + module);

        // It's a global object, expecting the "new" keyword to be used
        // if you don't want to use the "new" keyword, consider wrapping the function in a jQuery plugin
        if (noConfig) {
          inst = instantiate(windowRef, ["#" + moduleId]);
        } else {
          inst = instantiate(windowRef, ["#" + moduleId, moduleConfig]);
        }
      } else {
        // console.log(module + " is not defined");
        e.addClass(config.failedClass);
        initFailed = true;
      }

      if (
        typeof $.fn.ModularBehaviourHooks.afterHooks[module] !== "undefined"
      ) {
        $.fn.ModularBehaviourHooks.afterHooks[module].call(
          e,
          moduleConfig,
          inst
        );
      }

      // If init failed, we may need to try again later (ajax requirements can be delayed...)
      if (!initFailed) {
        e.addClass(config.initClass);
        e.removeClass(config.failedClass);
      } else {
        // This is a bit of a hack, dom might not be properly parsed (after ajax load for instance)
        if (timeout) {
          clearTimeout(timeout);
        }
        retries++;
        if (retries > 4) {
          timeout = setTimeout(function () {
            $("[data-module]").ModularBehaviour();
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
      document.addEventListener("DOMContentLoaded", fn);
    }
  }
  ready(function () {
    // console.log("ready");
    $("[data-module]").ModularBehaviour();
  });

  // after each successfull ajax request, try to init modules again after requirements are loaded
  $(document).ajaxSuccess(function (event, xhr, settings) {
    // Always try to init everything
    retries = 0;
    $("[data-module]").ModularBehaviour();
    // console.log("ajax success");
  });
})(jQuery);
