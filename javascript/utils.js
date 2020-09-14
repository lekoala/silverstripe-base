/* global $, window, jQuery */
var utils = {
  smoothScrolling: function () {
    $("a[href^='#']").click(function (e) {
      // some utilities may be used to toggle stuff (tabs, etc)
      if ($(this).data("toggle")) {
        return true;
      }
      e.preventDefault();
      var $dest = $($(this).attr("href"));

      // maybe anchor is not on this page?
      if (!$dest.length) {
        var page = $dest.data("page");
        if (!page) {
          page = "/";
        }
        window.location.href = page + $(this).attr("href");
        return true;
      }

      // scroll nicely
      $("html,body").animate(
        {
          scrollTop: $dest.offset().top,
        },
        "slow"
      );
    });
  },
  canvi: function () {
    var canvi = new Canvi({
      openButton: ".canvi-open-button",
      pushContent: false,
    });
  },
  checkTop: function () {
    var $body = $("body");
    $(window).on("scroll", function () {
      var scroll = $(window).scrollTop();
      if (scroll == 0) {
        $body.addClass("is-top");
      } else {
        $body.removeClass("is-top");
      }
    });
  },
};

// Init automatically
// Sample usage: <body class="$BodyClass" data-utils="smoothScrolling,canvi">
(function ($) {
  var dataUtils = $("body").data("utils");
  var list = dataUtils.split(",");
  $.each(list, function (idx, item) {
    utils[item]();
  });
})(jQuery);
