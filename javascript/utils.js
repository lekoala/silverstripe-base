/* global $, window, jQuery */
var utils = {
    smoothScrolling: function() {
        $("a[href^='#']").click(function(e) {
            if ($(this).data('toggle')) {
                return true;
            }
            e.preventDefault();
            var dest = $(this).attr('href');
            $('html,body').animate({
                scrollTop: $(dest).offset().top
            }, 'slow');
        });
    },
    canvi: function() {
        var canvi = new Canvi({
            openButton: '.canvi-open-button',
            pushContent: false
        });
    },
    checkTop: function() {
        var $body = $('body');
        $(window).on('scroll', function() {
            var scroll = $(window).scrollTop();
            if (scroll == 0) {
                $body.addClass('is-top');
            } else {
                $body.removeClass('is-top');
            }
        });
    }
};

// Init automatically
// Sample usage: <body class="$BodyClass" data-utils="smoothScrolling,canvi">
(function($) {
    var dataUtils = $('body').data('utils');
    var list = dataUtils.split(',');
    $.each(list, function(idx, item) {
        utils[item]();
    })
})(jQuery);
