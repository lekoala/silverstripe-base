/* global $, window, jQuery */

/**
 * Tweaks for the CMS
 * TODO: how to avoid attaching everything to document (use entwine?)
 */
(function ($) {
  // Helper function
  // @link https://stackoverflow.com/questions/5999118/how-can-i-add-or-update-a-query-string-parameter
  function UpdateQueryString(key, value, url) {
    if (!url) url = window.location.href;
    var re = new RegExp("([?&])" + key + "=.*?(&|#|$)(.*)", "gi"),
      hash;

    if (re.test(url)) {
      if (typeof value !== "undefined" && value !== null) {
        return url.replace(re, "$1" + key + "=" + value + "$2$3");
      } else {
        hash = url.split("#");
        url = hash[0].replace(re, "$1$3").replace(/(&|\?)$/, "");
        if (typeof hash[1] !== "undefined" && hash[1] !== null) {
          url += "#" + hash[1];
        }
        return url;
      }
    } else {
      if (typeof value !== "undefined" && value !== null) {
        var separator = url.indexOf("?") !== -1 ? "&" : "?";
        hash = url.split("#");
        url = hash[0] + separator + key + "=" + value;
        if (typeof hash[1] !== "undefined" && hash[1] !== null) {
          url += "#" + hash[1];
        }
        return url;
      } else {
        return url;
      }
    }
  }

  // Allow select with a data map attribute to update other selects
  $(document).on("change", "select[data-map]", function (event) {
    var el = $(this);
    var data = el.data("map");
    var target = el.data("target");
    if (!data || !target) {
      console.log("Empty data or no target");
      return;
    }
    var val = el.val();
    var input = $("#Form_ItemEditForm_" + target);
    if (data[val]) {
      input.val(data[val]).trigger("change").trigger("chosen:updated").trigger("liszt:updated");
    }
  });

  // Allow button that copy stuff to clipboard
  $(document).on("click", "button[data-clipboard]", function (event) {
    // Otherwise in a gridfield the row might be clicked
    event.stopPropagation();
    copyTextToClipboard($(this).data("value"));
  });

  // Confirmable stuff
  $(document).on("click", "button[data-confirm],a[data-confirm]", function (event) {
    return confirm($(this).data("confirm"));
  });

  // Confirmable boxes
  $(document).on("click", "input.confirmable", function (event) {
    var $cb = $(this);
    var res = true;
    var message = "Are you sure?";
    var securityID = $("#Form_ItemEditForm_SecurityID").val();
    if ($cb.data("confirm")) {
      message = $cb.data("confirm");
    }
    if ($cb.prop("checked")) {
      res = confirm(message);
      if (!res) {
        $cb.prop("checked", false);
      }
    }
    if (res) {
      $.ajax({
        url: $cb.data("url"),
        headers: {
          "X-CSRF-TOKEN": securityID,
        },
        type: "POST",
        dataType: "json",
        data: {
          checked: $cb.prop("checked") ? 1 : 0,
          data: $cb.data(),
          name: $cb.attr("name"),
        },
      }).done(function (res) {});
    }
  });

  // Promptable stuff
  $(document).on("click", "button[data-prompt]", function (event) {
    var result = prompt($(this).data("prompt"), $(this).data("promptDefault"));
    if (result) {
      $.cookie("prompt_result", result);
      return true;
    }
    return false;
  });

  // Prevent submit with enter
  $(document).on("keypress", ":input:not(textarea)", function (event) {
    if (event.keyCode == 13) {
      event.preventDefault();
      // This is just lazy excel like tab
      $(this).parents(".field").next(".field").find(":input").first().focus();
    }
    return true;
  });

  $.entwine("ss", function ($) {
    // Bulk manager shortcuts
    $(".col-bulkSelect").entwine({
      oncontextmenu: function (e) {
        var fieldset = this.closest("fieldset");
        var bulkSelect = fieldset.find("select.bulkActionName");
        var bulkContextMenu = fieldset.find(".bulkContextMenu");
        var goButton = fieldset.find(".doBulkActionButton ");
        var html = "";
        if (!bulkContextMenu.length) {
          html += '<div class="bulkContextMenu contextMenu">';
          bulkSelect.find("option").each(function (idx, item) {
            if (idx == 0) {
              return;
            }
            var $t = $(item);
            html += '<a href="#" data-value="' + $t.attr("value") + '">' + $t.text() + "</a>";
          });
          html += "</div>";
          var bulkContextMenu = $(html);
          fieldset.append(bulkContextMenu);
        }
        var styles = {
          width: "180px",
          left: e.pageX - 300,
          top: e.pageY - 20,
        };
        bulkContextMenu.show();
        bulkContextMenu.css(styles);
        bulkContextMenu.find("a").on("click", function (e) {
          e.preventDefault();
          if ($(this).hasClass("disabled")) {
            return;
          }
          var val = $(this).data("value");
          bulkSelect.val(val).trigger("change").trigger("chosen:updated").trigger("liszt:updated");
          goButton.trigger("click");
          $(this).text("Please wait...").addClass("disabled");
        });
        bulkContextMenu.one("mouseleave", function () {
          $(this).hide();
        });
        e.preventDefault();
      },
    });

    // Quick filters
    $(".quickfilters-action").entwine({
      onclick: function (e) {
        e.stopPropagation();

        this.parents("form").removeClass("changed");
        var inputs = this.parents(".quickfilters").find("label input");

        var url = window.location.href;
        var arr = [];
        inputs.each(function () {
          if ($(this).is(":checked")) {
            arr.push($(this).val());
          }
        });
        var val = arr.join(",");
        window.location.href = UpdateQueryString("quickfilters", val);
      },
    });

    // Let input work properly in grids
    $(".ss-gridfield-items td select, .ss-gridfield-items td input").entwine({
      onmatch: function () {},
      onunmatch: function () {},
      onclick: function (e) {
        // Prevent row click
        e.stopPropagation();
      },
    });

    // Clickable icons
    $(".uploadfield-item__thumbnail").entwine({
      onclick: function () {
        var id = this.parent().find("input").val();
        var uploadfield = this.parents("div.entwine-uploadfield").find("input.entwine-uploadfield");
        var state = uploadfield.data("state");
        var files = state.data.files;
        for (var i = 0; i < files.length; i++) {
          var item = files[i];
          if (item.id == id && item.url) {
            window.open(item.url);
          }
        }
      },
    });
  });
})(jQuery);

// @link https://stackoverflow.com/questions/400212/how-do-i-copy-to-the-clipboard-in-javascript
// Expose a global function copyTextToClipboard
function copyTextToClipboard(text) {
  var successful, msg, type;
  var textArea = document.createElement("textarea");
  textArea.style.position = "fixed";
  textArea.style.top = 0;
  textArea.style.left = 0;
  textArea.style.width = "2em";
  textArea.style.height = "2em";
  textArea.style.padding = 0;
  textArea.style.border = "none";
  textArea.style.outline = "none";
  textArea.style.boxShadow = "none";
  textArea.style.background = "transparent";
  textArea.value = text;

  document.body.appendChild(textArea);
  textArea.focus();
  textArea.select();

  try {
    successful = document.execCommand("copy");

    if (successful) {
      msg = "Data copied to the clipboard";
      type = "success";
    } else {
      msg = "Failed to copy data to the clipboard";
      type = "error";
    }
    jQuery.noticeAdd({
      text: msg,
      type: type,
      stayTime: 5000,
      inEffect: {
        left: "0",
        opacity: "show",
      },
    });
  } catch (err) {}

  document.body.removeChild(textArea);

  return false;
}
