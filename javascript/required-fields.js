/**
 * RequiredFields.js
 * Deal with required fields in vanilla js
 *
 * https://github.com/lekoala/required-fields.js
 * MIT License
 */
(function (global) {
  "use strict";

  var document = global.document;

  // Default settings
  var defaults = {};
  var config = {
    debug: false,
    ignoreValidationClass: "ignore-validation",
    allRequiredClass: "required-all",
  };
  // Actual settings
  var RequiredFields = {
    options: {},
    clickedButton: null,
  };

  /**
   * Debug message
   * @param {string} msg
   */
  function debug(msg) {
    if (config.debug) {
      console.log("[rf] " + msg);
    }
  }

  /**
   * @param {object} input
   * @returns {boolean}
   */
  function getInputValue(input) {
    if (input.type == "radio" || input.type == "checkbox") {
      if (input.checked) {
        return true;
      }
    } else {
      if (input.value) {
        return true;
      }
    }
    return false;
  }

  /**
   * Create the Constructor object
   */
  var RequiredFields = function (selector, opts) {
    this.setOptions(opts);
    if (selector instanceof HTMLFormElement) {
      this.nodes = [selector];
    } else {
      this.nodes = document.querySelectorAll(selector);
    }
    this.setRequire();
    this.handleFormSubmit();

    debug("apply on " + selector);
  };

  /**
   * Loop through each element
   */
  RequiredFields.prototype.forEach = function (callback) {
    for (var i = 0; i < this.nodes.length; i++) {
      callback(this.nodes[i], i);
    }
  };

  /**
   * Set options
   */
  RequiredFields.prototype.setOptions = function (opts) {
    this.options = defaults;
    if (opts) {
      for (var k in opts) {
        this.options[k] = opts[k];
      }
    }
  };

  /**
   * Remove require attributes and replace them with class
   */
  RequiredFields.prototype.setRequire = function () {
    var self = this;

    this.forEach(function (node) {
      var list = node.querySelectorAll("[required]");
      for (var i = 0; i < list.length; i++) {
        var el = list[i];
        var parent = el.parentNode;
        // Maybe we need to skip one level
        if (self.options.skipParentClass) {
          if (parent.classList.contains(self.options.skipParentClass)) {
            parent = parent.parentNode;
          }
        }
        if (el.hasAttribute("required")) {
          el.removeAttribute("required");
          parent.classList.add("required");

          // Update status
          el.addEventListener("blur", function () {
            console.log(el);
          });
        }
      }
    });
  };

  /**
   * Catch submit event and triggers validation
   */
  RequiredFields.prototype.handleFormSubmit = function () {
    var self = this;

    this.forEach(function (node) {
      // Find submit buttons and track them
      var buttons = node.querySelectorAll("[type=submit]");
      for (var i = 0; i < buttons.length; i++) {
        var btn = buttons[i];
        btn.addEventListener("click", function (btnEvent) {
          self.clickedButton = this;
          debug("click on " + this.name);
        });
        // Set default button for enter
        if (!self.clickedButton) {
          self.clickedButton = btn;
          debug("default click on " + btn.name);
        }
      }

      // Intercept submit event
      node.addEventListener("submit", function (e) {
        debug("submit");
        var hasErrors = false;

        // No need to validate
        if (self.clickedButton && self.clickedButton.classList.contains(config.ignoreValidationClass)) {
          debug("ignore validation");
          return;
        }

        // Loop over required fields
        var list = node.querySelectorAll(".required");
        debug("validating " + list.length + " elements");
        for (var i = 0; i < list.length; i++) {
          var parent = list[i];
          var allRequired = false;
          if (parent.classList.contains(config.allRequiredClass)) {
            allRequired = true;
          }

          if (window.getComputedStyle(parent).display === "none") {
            parent.classList.remove("error");
            continue;
          }

          var inputs = parent.querySelectorAll("input,textarea,select");
          if (inputs.length > 0) {
            var hasValue = false;
            if (allRequired) {
              hasValue = true;
            }
            for (var j = 0; j < inputs.length; j++) {
              var listInput = inputs[j];
              if (allRequired) {
                // One is enough to invalidate
                if (!getInputValue(listInput)) {
                  hasValue = false;
                }
              } else {
                // At least one input must have a value or be checked
                if (getInputValue(listInput)) {
                  hasValue = true;
                }
              }
            }
            if (hasValue) {
              parent.classList.remove("error");
            } else {
              hasErrors = true;
              parent.classList.add("error");
            }
          } else {
            parent.classList.remove("error");
            continue;
          }
        }

        // It has errors, prevents submit and scroll to issue
        if (hasErrors) {
          debug("Has errors");
          e.preventDefault();

          var elWithError = node.querySelector(".error");
          elWithError.scrollIntoView();
        } else {
          debug("No errors");
        }
      });
    });
  };

  // AMD support
  if (typeof define === "function" && define.amd) {
    define(function () {
      return RequiredFields;
    });
  } else if (typeof exports !== "undefined") {
    // Support Node.js specific 'module.exports' (which can be a function)
    if (typeof module !== "undefined" && module.exports) {
      exports = module.exports = RequiredFields;
    }
    // But always support CommonJS module 1.1.1 spec ('exports' cannot be a function)
    exports.RequiredFields = RequiredFields;
  } else {
    global.RequiredFields = RequiredFields;
  }
})(this);
