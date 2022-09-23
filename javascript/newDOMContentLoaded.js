function newDOMContentLoaded(container = null) {
  if (!container) {
    container = document.body;
  }

  function dispatchEvent() {
    container.dispatchEvent(
      new CustomEvent("newDOMContentLoaded", {
        detail: addedNodes,
      })
    );
  }

  var scriptsLoading = [];
  var scriptsTriggerEvent = false;
  /**
   * @param {HTMLScriptElement} script
   */
  function resolveScript(script) {
    scriptsLoading.splice(scriptsLoading.indexOf(script.getAttribute("src")), 2);
    if (scriptsLoading.length === 0) {
      if (scriptsTriggerEvent) {
        dispatchEvent();
        scriptsTriggerEvent = false;
      }
    }
  }
  /**
   * @param {HTMLScriptElement} script
   */
  function trackScript(script) {
    if (script.hasAttribute("async") || script.hasAttribute("nomodule") || !script.hasAttribute("src")) {
      return;
    }
    scriptsLoading.push(script.getAttribute("src"));

    var prevOnload = script.onload;
    var prevOnerror = script.onerror;
    script.onload = function (e) {
      if (prevOnload) {
        prevOnload(e);
      }
      resolveScript(script);
    };
    script.onerror = function (e) {
      if (prevOnerror) {
        prevOprevOnerrornload(e);
      }
      resolveScript(script);
    };
  }

  var addedNodes = [];
  var observerTimer;
  var domObserver = new MutationObserver(function (mutations) {
    clearTimeout(observerTimer);
    for (var i = 0; i < mutations.length; i++) {
      var mutation = mutations[i];
      if (mutation.addedNodes.length > 0) {
        for (var j = 0; j < mutation.addedNodes.length; j++) {
          var node = mutation.addedNodes[j];
          if (!node.tagName) {
            continue;
          }
          if (node.tagName.toLowerCase() === "script") {
            trackScript(node);
          }
          addedNodes.push(node);
        }
      }
    }
    observerTimer = setTimeout(function () {
      if (scriptsLoading.length > 0) {
        scriptsTriggerEvent = true;
      } else {
        dispatchEvent();
      }
      addedNodes = [];
    }, 300);
  });
  domObserver.observe(container, {
    childList: true,
    subtree: true,
  });
}
